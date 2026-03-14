<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\OwnerBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller pour la gestion des revenus propriétaires
 */
class EarningsController extends Controller
{
    /**
     * Tableau de bord des revenus du propriétaire
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $commissionRate = config('rezi.pricing.owner_commission_rate', 0.03);

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
        ));
    }
}
