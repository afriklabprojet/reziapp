<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPayoutJob;
use App\Models\Booking;
use App\Models\OwnerBalance;
use App\Models\Payout;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Notifications\PayoutRequestedNotification;
use App\Notifications\WithdrawalInitiatedNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Controller pour la gestion des revenus propriétaires
 * Sécurité renforcée : PIN de retrait, rate limiting, IP logging, notifications
 */
class EarningsController extends Controller
{
    /**
     * Tableau de bord des revenus du propriétaire
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $commissionRate = PlatformSetting::getCommissionRate() / 100;

        // Période de filtrage (défaut : 30 derniers jours)
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(30)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        // Solde propriétaire
        $balance = OwnerBalance::getOrCreateForUser($user->id);

        // Réservations complétées dans la période
        $bookings = Booking::forOwner($user->id)
            ->completed()
            ->where('payment_status', 'paid')
            ->whereBetween('check_out', [$startDate, $endDate])
            ->with(['residence:id,name', 'user:id,name'])
            ->orderByDesc('check_out')
            ->get();

        // Calculer les gains par réservation
        $bookings->each(function ($booking) use ($commissionRate) {
            $ownerSubtotal = (float) $booking->subtotal + (float) $booking->cleaning_fee;
            $commission = round($ownerSubtotal * $commissionRate, 0);
            $booking->owner_earnings = $ownerSubtotal - $commission;
            $booking->commission_amount = $commission;
        });

        // Statistiques globales sur la période
        $totalEarnings = $bookings->sum('owner_earnings');
        $totalCommission = $bookings->sum('commission_amount');
        $bookingsCount = $bookings->count();
        $averageEarning = $bookingsCount > 0 ? round($totalEarnings / $bookingsCount, 0) : 0;

        // Revenus par mois (12 derniers mois)
        $monthlyEarnings = Booking::forOwner($user->id)
            ->completed()
            ->where('payment_status', 'paid')
            ->where('check_out', '>=', now()->subMonths(12))
            ->selectRaw('YEAR(check_out) as year, MONTH(check_out) as month, SUM(subtotal + cleaning_fee) as gross_total, COUNT(*) as count')
            ->groupByRaw('YEAR(check_out), MONTH(check_out)')
            ->orderByRaw('YEAR(check_out), MONTH(check_out)')
            ->get()
            ->map(function ($row) use ($commissionRate) {
                $gross = (float) $row->gross_total;
                $net = round($gross - ($gross * $commissionRate), 0);

                return [
                    'label' => Carbon::createFromDate($row->year, $row->month, 1)->translatedFormat('M Y'),
                    'gross' => $gross,
                    'net' => $net,
                    'count' => $row->count,
                ];
            });

        // Revenus par résidence
        $earningsByResidence = Booking::forOwner($user->id)
            ->where('bookings.status', 'completed')
            ->where('bookings.payment_status', 'paid')
            ->whereBetween('bookings.check_out', [$startDate, $endDate])
            ->join('residences', 'bookings.residence_id', '=', 'residences.id')
            ->selectRaw('residences.id, residences.name, SUM(bookings.subtotal + bookings.cleaning_fee) as gross_total, COUNT(*) as count')
            ->groupBy('residences.id', 'residences.name')
            ->orderByDesc('gross_total')
            ->get()
            ->map(function ($row) use ($commissionRate) {
                $gross = (float) $row->gross_total;

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'gross' => $gross,
                    'net' => round($gross - ($gross * $commissionRate), 0),
                    'count' => $row->count,
                ];
            });

        // Historique des retraits
        $payouts = Payout::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Demande de retrait en cours ?
        $pendingPayout = Payout::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        // Montant minimum de retrait
        $minWithdrawal = config('rezi.pricing.min_withdrawal', 5000);

        return view('owner.earnings.index', compact(
            'balance',
            'bookings',
            'totalEarnings',
            'totalCommission',
            'bookingsCount',
            'averageEarning',
            'monthlyEarnings',
            'earningsByResidence',
            'startDate',
            'endDate',
            'commissionRate',
            'payouts',
            'pendingPayout',
            'minWithdrawal',
        ));
    }

    /**
     * Configurer ou modifier le PIN de retrait.
     * Nécessite la confirmation du mot de passe.
     */
    public function setupPin(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'withdrawal_pin' => 'required|string|digits:4|confirmed',
        ], [
            'current_password.required' => 'Votre mot de passe actuel est obligatoire.',
            'withdrawal_pin.required' => 'Le PIN est obligatoire.',
            'withdrawal_pin.digits' => 'Le PIN doit comporter exactement 4 chiffres.',
            'withdrawal_pin.confirmed' => 'La confirmation du PIN ne correspond pas.',
        ]);

        $user = $request->user();

        // Vérifier le mot de passe actuel
        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Mot de passe incorrect.'])->withInput();
        }

        // Rate limit: max 5 changements de PIN par jour
        $rateLimitKey = 'pin-setup:' . $user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return back()->withErrors(['withdrawal_pin' => "Trop de tentatives. Réessayez dans " . ceil($seconds / 60) . " min."])->withInput();
        }
        RateLimiter::hit($rateLimitKey, 86400); // 24h

        $user->setWithdrawalPin($request->input('withdrawal_pin'));

        Log::info('Withdrawal PIN set/changed', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('owner.earnings.index')
            ->with('success', 'Votre PIN de retrait a été ' . ($user->wasRecentlyCreated ? 'configuré' : 'mis à jour') . ' avec succès.');
    }

    /**
     * Soumettre une demande de retrait.
     *
     * Sécurité :
     * - PIN de retrait obligatoire (4 chiffres)
     * - Rate limiting (3 demandes par heure)
     * - Cooldown entre retraits (1h)
     * - Logging IP + User-Agent
     * - Notification email au propriétaire
     * - Vérification du solde et des demandes en cours
     */
    public function requestPayout(Request $request): RedirectResponse
    {
        $user = $request->user();

        // ──── 1. Vérifier que le PIN est configuré ────
        if (! $user->hasWithdrawalPin()) {
            return back()->withErrors(['withdrawal_pin' => 'Vous devez d\'abord configurer votre PIN de retrait.'])->withInput();
        }

        // ──── 2. Vérifier que le PIN n'est pas verrouillé ────
        if ($user->isWithdrawalPinLocked()) {
            $minutes = $user->withdrawalPinLockRemainingMinutes();
            return back()->withErrors(['withdrawal_pin' => "Retraits verrouillés suite à trop de tentatives. Réessayez dans {$minutes} min."])->withInput();
        }

        // ──── 3. Rate limiting (3 demandes par heure) ────
        $rateLimitKey = 'payout-request:' . $user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Log::warning('Payout rate limit exceeded', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
            return back()->withErrors(['amount' => "Trop de demandes de retrait. Réessayez dans " . ceil($seconds / 60) . " min."])->withInput();
        }

        // ──── 4. Validation des champs ────
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payout_method' => 'required|string|in:wave,orange_money,mtn_money,moov_money,bank_transfer',
            'phone_number' => 'required_unless:payout_method,bank_transfer|nullable|string|max:20',
            'bank_name' => 'required_if:payout_method,bank_transfer|nullable|string|max:100',
            'bank_account' => 'required_if:payout_method,bank_transfer|nullable|string|max:50',
            'withdrawal_pin' => 'required|string|digits:4',
        ], [
            'amount.required' => 'Le montant est obligatoire.',
            'amount.min' => 'Le montant minimum est de 1 FCFA.',
            'payout_method.required' => 'Choisissez un mode de retrait.',
            'phone_number.required_unless' => 'Le numéro de téléphone est obligatoire pour Mobile Money.',
            'withdrawal_pin.required' => 'Le PIN de retrait est obligatoire.',
            'withdrawal_pin.digits' => 'Le PIN doit comporter 4 chiffres.',
        ]);

        // ──── 5. Vérifier le PIN de retrait ────
        if (! $user->verifyWithdrawalPin($request->input('withdrawal_pin'))) {
            $remaining = 5 - $user->fresh()->withdrawal_pin_attempts;
            $msg = 'PIN de retrait incorrect.';
            if ($remaining > 0 && $remaining <= 3) {
                $msg .= " Encore {$remaining} tentative(s) avant verrouillage.";
            }
            if ($remaining <= 0) {
                $msg = 'Trop de tentatives. Retraits verrouillés pendant 30 minutes.';
            }

            Log::warning('Invalid withdrawal PIN attempt', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->withErrors(['withdrawal_pin' => $msg])->withInput();
        }

        // ──── 6. Vérifications métier ────
        $amount = (float) $request->input('amount');
        $minWithdrawal = config('rezi.pricing.min_withdrawal', 5000);

        if ($amount < $minWithdrawal) {
            return back()->withErrors(['amount' => "Le montant minimum de retrait est de " . number_format($minWithdrawal, 0, ',', ' ') . " FCFA."])->withInput();
        }

        $balance = OwnerBalance::getOrCreateForUser($user->id);

        if (! $balance->canWithdraw($amount)) {
            return back()->withErrors(['amount' => 'Solde insuffisant. Vous avez ' . $balance->formatted_available . ' disponible.'])->withInput();
        }

        // Vérifier qu'il n'y a pas déjà une demande en cours
        $pendingPayout = Payout::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($pendingPayout) {
            return back()->withErrors(['amount' => 'Vous avez déjà une demande de retrait en cours. Veuillez patienter.'])->withInput();
        }

        // ──── 7. Cooldown : 1h minimum entre deux retraits réussis ────
        $lastCompletedPayout = Payout::where('user_id', $user->id)
            ->where('status', Payout::STATUS_COMPLETED)
            ->orderByDesc('completed_at')
            ->first();

        if ($lastCompletedPayout && $lastCompletedPayout->completed_at?->diffInMinutes(now()) < 60) {
            $waitMinutes = 60 - $lastCompletedPayout->completed_at->diffInMinutes(now());
            return back()->withErrors(['amount' => "Veuillez patienter encore {$waitMinutes} min avant un nouveau retrait."])->withInput();
        }

        // ──── 8. Compter la tentative dans le rate limiter ────
        RateLimiter::hit($rateLimitKey, 3600);

        // ──── 9. Exécuter le retrait ────
        $payout = null;
        DB::transaction(function () use ($user, $amount, $balance, $request, &$payout) {
            $payout = Payout::create([
                'user_id' => $user->id,
                'gross_amount' => $amount,
                'platform_fee' => 0,
                'transfer_fee' => 0,
                'net_amount' => $amount,
                'currency' => 'XOF',
                'status' => Payout::STATUS_PENDING,
                'payout_method' => $request->input('payout_method'),
                'phone_number' => $request->input('phone_number'),
                'bank_name' => $request->input('bank_name'),
                'bank_account' => $request->input('bank_account'),
                'requested_at' => now(),
                'metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'requested_from' => 'owner_dashboard',
                ],
            ]);

            // Débiter le solde disponible
            $balance->withdraw($amount);

            // Lancer le traitement automatique via Jeko Transfers
            ProcessPayoutJob::dispatch($payout)->afterCommit();

            // Notifier les admins (backup)
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new PayoutRequestedNotification($payout));
            }
        });

        // ──── 10. Notification sécurité au propriétaire ────
        if ($payout) {
            $user->notify(new WithdrawalInitiatedNotification(
                $payout,
                $request->ip(),
                $request->userAgent() ?? 'Unknown',
            ));

            Log::info('Payout requested successfully', [
                'user_id' => $user->id,
                'payout_id' => $payout->id,
                'amount' => $amount,
                'method' => $request->input('payout_method'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return redirect()->route('owner.earnings.index')
            ->with('success', 'Votre demande de retrait de ' . number_format($amount, 0, ',', ' ') . ' FCFA a été envoyée. Le versement sera traité automatiquement. Un email de confirmation vous a été envoyé.');
    }
}
