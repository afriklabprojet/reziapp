<?php

namespace App\Services;

use App\Jobs\VerifyPaymentStatus;
use App\Models\Booking;
use App\Models\OwnerBalance;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentProvider;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected JekoService $jekoService;
    protected InvoiceService $invoiceService;

    public function __construct(JekoService $jekoService, InvoiceService $invoiceService)
    {
        $this->jekoService = $jekoService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Créer un paiement pour une réservation.
     * IDEMPOTENT: Si un paiement pending/processing existe déjà pour ce booking, on le retourne.
     *
     * Options:
     *   - provider            (string)  Payment provider code, default 'jeko'
     *   - payment_method_id   (int)     Saved payment method to associate
     *   - use_wallet_credit   (bool)    Deduct from users.wallet_credit
     *   - use_referral_credit (bool)    Deduct from users.referral_balance
     */
    public function createBookingPayment(Booking $booking, User $user, array $options = []): Payment
    {
        return DB::transaction(function () use ($booking, $user, $options) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $existing = Payment::where('booking_id', $lockedBooking->id)
                ->where('user_id', $user->id)
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])
                ->first();

            if ($existing) {
                Log::channel('payments')->info('createBookingPayment: Returning existing payment (idempotent)', [
                    'payment_id' => $existing->id,
                    'booking_id' => $lockedBooking->id,
                ]);

                return $existing;
            }

            // Lock user row to prevent concurrent credit double-spend
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $provider = PaymentProvider::where('code', $options['provider'] ?? 'jeko')->first();

            $bookingAmount = (float) $lockedBooking->total_amount;

            // ── Credit deduction (applied before provider fees) ──────────────────────
            $walletCreditUsed   = 0.0;
            $referralCreditUsed = 0.0;
            $remainingAmount    = $bookingAmount;

            if (! empty($options['use_wallet_credit'])) {
                $available = max(0.0, (float) $lockedUser->wallet_credit);
                $walletCreditUsed = min($available, $remainingAmount);

                if ($walletCreditUsed > 0) {
                    $lockedUser->decrement('wallet_credit', $walletCreditUsed);
                    $remainingAmount -= $walletCreditUsed;
                }
            }

            if (! empty($options['use_referral_credit']) && $remainingAmount > 0) {
                $available = max(0.0, (float) $lockedUser->referral_balance);
                $referralCreditUsed = min($available, $remainingAmount);

                if ($referralCreditUsed > 0) {
                    $lockedUser->decrement('referral_balance', $referralCreditUsed);
                    $remainingAmount -= $referralCreditUsed;
                }
            }

            // Ensure amount charged to provider is never negative
            $chargeableAmount = max(0.0, $remainingAmount);

            // Provider fees are calculated on the post-credit chargeable amount
            $fees = $provider?->calculateFees($chargeableAmount) ?? [
                'total_fee' => 0,
                'total_amount' => $chargeableAmount,
            ];

            $attempt = Payment::withTrashed()
                ->where('booking_id', $lockedBooking->id)
                ->where('user_id', $user->id)
                ->where('type', Payment::TYPE_BOOKING)
                ->count() + 1;

            $payment = Payment::create([
                'idempotency_key'      => 'bk_'.$lockedBooking->id.'_'.$user->id.'_attempt_'.$attempt,
                'user_id'              => $user->id,
                'booking_id'           => $lockedBooking->id,
                'payment_provider_id'  => $provider?->id,
                'payment_method_id'    => $options['payment_method_id'] ?? null,
                'amount'               => $chargeableAmount,
                'fee'                  => $fees['total_fee'],
                'wallet_credit_used'   => $walletCreditUsed,
                'referral_credit_used' => $referralCreditUsed,
                'total_amount'         => $fees['total_amount'],
                'currency'             => 'XOF',
                'type'                 => Payment::TYPE_BOOKING,
                'status'               => Payment::STATUS_PENDING,
                'metadata'             => [
                    'booking_reference'    => $lockedBooking->reference,
                    'residence_id'         => $lockedBooking->residence_id,
                    'check_in'             => $lockedBooking->check_in->toDateString(),
                    'check_out'            => $lockedBooking->check_out->toDateString(),
                    'wallet_credit_used'   => $walletCreditUsed,
                    'referral_credit_used' => $referralCreditUsed,
                ],
            ]);

            Log::channel('payments')->info('Payment created', [
                'payment_id'           => $payment->id,
                'booking_id'           => $lockedBooking->id,
                'amount'               => $payment->total_amount,
                'wallet_credit_used'   => $walletCreditUsed,
                'referral_credit_used' => $referralCreditUsed,
                'user_id'              => $user->id,
            ]);

            return $payment;
        });
    }

    /**
     * Restore wallet and referral credits deducted for a payment.
     * Called when payment fails or is cancelled after credits were already deducted.
     * Safe to call multiple times — amounts are re-read fresh from the DB each time.
     */
    public function restoreCredits(Payment $payment): void
    {
        $walletCredit   = (float) $payment->wallet_credit_used;
        $referralCredit = (float) $payment->referral_credit_used;

        if ($walletCredit <= 0 && $referralCredit <= 0) {
            return;
        }

        DB::transaction(function () use ($payment, $walletCredit, $referralCredit) {
            $user = User::where('id', $payment->user_id)->lockForUpdate()->firstOrFail();

            if ($walletCredit > 0) {
                $user->increment('wallet_credit', $walletCredit);
            }

            if ($referralCredit > 0) {
                $user->increment('referral_balance', $referralCredit);
            }

            // Mark as restored so subsequent calls are no-ops
            $payment->update([
                'wallet_credit_used'   => 0,
                'referral_credit_used' => 0,
                'metadata'             => array_merge($payment->metadata ?? [], [
                    'credits_restored_at'            => now()->toIso8601String(),
                    'wallet_credit_restored'         => $walletCredit,
                    'referral_credit_restored'       => $referralCredit,
                ]),
            ]);

            Log::channel('payments')->info('Credits restored', [
                'payment_id'           => $payment->id,
                'user_id'              => $payment->user_id,
                'wallet_credit'        => $walletCredit,
                'referral_credit'      => $referralCredit,
            ]);
        });
    }

    /**
     * Initier un paiement Mobile Money
     */
    public function initiatePayment(Payment $payment, string $phoneNumber, ?string $operator = null): array
    {
        // Détecter l'opérateur si non fourni
        if (!$operator) {
            $operator = $this->jekoService->detectOperator($phoneNumber);

            if (!$operator) {
                return [
                    'success' => false,
                    'message' => 'Impossible de détecter l\'opérateur. Veuillez le sélectionner manuellement.',
                ];
            }
        }

        $result = $this->jekoService->initiateMobileMoneyPayment($payment, $phoneNumber, $operator);

        // Schedule automatic status verification (retries with backoff)
        if ($result['success']) {
            VerifyPaymentStatus::dispatch($payment)
                ->delay(now()->addMinutes(3))
                ->onQueue('payments');

            Log::channel('payments')->info('Payment initiated — verify job scheduled', [
                'payment_id' => $payment->id,
                'operator' => $operator,
            ]);
        }

        return $result;
    }

    /**
     * Vérifier un paiement avec OTP
     */
    public function verifyOtp(Payment $payment, string $otp): array
    {
        $result = $this->jekoService->verifyWithOtp($payment, $otp);

        if ($result['success']) {
            // Traiter le succès du paiement
            $this->onPaymentSuccess($payment);
        }

        return $result;
    }

    /**
     * Traiter le succès d'un paiement.
     * IDEMPOTENT: safe to call multiple times (webhook can fire twice).
     */
    public function onPaymentSuccess(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            // Recharger le paiement avec lock pour éviter le traitement concurrent
            $payment = Payment::lockForUpdate()->find($payment->id);

            if (! $payment) {
                Log::channel('critical')->error('onPaymentSuccess: Payment not found', [
                    'payment_id' => $payment?->id,
                ]);

                return;
            }

            // IDEMPOTENCY: Si la réservation est déjà confirmée, ne rien faire
            if ($payment->booking && $payment->booking->status === 'confirmed') {
                Log::channel('payments')->info('onPaymentSuccess: Booking already confirmed (idempotent skip)', [
                    'payment_id' => $payment->id,
                    'booking_id' => $payment->booking_id,
                ]);

                return;
            }

            // Confirmer la réservation si applicable
            if ($payment->booking) {
                $payment->booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'confirmed_at' => now(),
                ]);

                // Créditer le propriétaire (en attente)
                $this->creditOwner($payment);

                Log::channel('payments')->info('Payment success: Booking confirmed', [
                    'payment_id' => $payment->id,
                    'booking_id' => $payment->booking_id,
                    'amount' => $payment->total_amount,
                ]);

                // Invalidate caches for booking, residence availability, user data
                CacheInvalidationService::invalidateBooking(
                    $payment->booking->residence_id,
                    $payment->booking->user_id,
                );
                CacheInvalidationService::invalidatePayment($payment->booking->user_id);

                // Track business events
                BusinessEventService::paymentCompleted(
                    $payment->booking->user_id,
                    $payment->id,
                    (float) $payment->total_amount,
                );
                BusinessEventService::bookingConfirmed(
                    $payment->booking->user_id,
                    $payment->booking_id,
                    (float) $payment->total_amount,
                );
            }

            // Générer la facture (idempotent — InvoiceService checks for existing)
            try {
                $this->invoiceService->generateFromPayment($payment);
            } catch (\Throwable $e) {
                // Invoice failure should NOT block payment confirmation
                Log::channel('critical')->error('onPaymentSuccess: Invoice generation failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Envoyer les notifications (fire-and-forget, never block)
            try {
                $this->sendPaymentConfirmation($payment);
            } catch (\Throwable $e) {
                Log::channel('critical')->error('onPaymentSuccess: Notification failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Créditer le propriétaire (commission déduite).
     * Safe to call multiple times — OwnerBalance handles idempotency.
     */
    protected function creditOwner(Payment $payment): void
    {
        if (! $payment->booking?->residence?->owner_id) {
            Log::channel('payments')->warning('creditOwner: Missing owner relationship', [
                'payment_id' => $payment->id,
                'booking_id' => $payment->booking_id,
            ]);

            return;
        }

        $ownerId = $payment->booking->residence->owner_id;

        try {
            $balance = OwnerBalance::getOrCreateForUser($ownerId);

            // Calculer le montant propriétaire (moins commission Rezi Studio Meublé Faya depuis paramètres admin)
            $commissionRate = PlatformSetting::getCommissionRate() / 100;
            $ownerSubtotal = (float) $payment->booking->subtotal + (float) $payment->booking->cleaning_fee;
            $ownerAmount = round($ownerSubtotal * (1 - $commissionRate), 0);

            if ($ownerAmount <= 0) {
                Log::channel('payments')->warning('creditOwner: Owner amount is zero or negative', [
                    'payment_id' => $payment->id,
                    'owner_id' => $ownerId,
                    'amount' => $ownerAmount,
                ]);

                return;
            }

            $balance->addPendingEarnings($ownerAmount);

            Log::channel('payments')->info('Owner credited', [
                'owner_id' => $ownerId,
                'amount' => $ownerAmount,
                'payment_id' => $payment->id,
            ]);

            // Track revenue event
            $commission = round($ownerSubtotal * $commissionRate, 0);
            BusinessEventService::revenueEarned(
                $ownerId,
                (float) $payment->total_amount,
                $payment->booking_id,
                $commission,
            );
        } catch (\Throwable $e) {
            // Owner credit failure should not stop payment processing
            Log::channel('critical')->error('creditOwner: Failed', [
                'payment_id' => $payment->id,
                'owner_id' => $ownerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoyer la confirmation de paiement
     */
    protected function sendPaymentConfirmation(Payment $payment): void
    {
        // Notification à l'utilisateur
        $payment->user->notify(new \App\Notifications\PaymentConfirmed($payment));

        // Notification au propriétaire si réservation
        if ($payment->booking?->residence?->owner) {
            $owner = $payment->booking->residence->owner;
            $owner->notify(
                new \App\Notifications\NewBookingReceived($payment->booking, $payment->booking->residence),
            );

            // Notification in-app
            \App\Models\Notification::send(
                $owner,
                'booking',
                'Nouvelle réservation confirmée',
                ($payment->user?->name ?? 'Un client').' a réservé '.$payment->booking->residence->name,
                route('owner.bookings.show', $payment->booking),
                ['booking_id' => $payment->booking->id],
            );
        }
    }

    /**
     * Obtenir l'historique des paiements d'un utilisateur
     */
    public function getUserPayments(User $user, array $filters = [])
    {
        $query = Payment::where('user_id', $user->id)
            ->with(['booking.residence', 'provider', 'invoice']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Obtenir les statistiques de paiement
     */
    public function getStatistics(array $filters = []): array
    {
        $query = Payment::query();

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return [
            'total_payments' => (clone $query)->count(),
            'total_amount' => (clone $query)->completed()->sum('amount'),
            'total_fees' => (clone $query)->completed()->sum('fee'),
            'pending_amount' => (clone $query)->pending()->sum('amount'),
            'failed_count' => (clone $query)->failed()->count(),
            'success_rate' => $this->calculateSuccessRate(clone $query),
            'by_provider' => $this->getStatsByProvider($filters),
            'by_type' => $this->getStatsByType($filters),
        ];
    }

    protected function calculateSuccessRate(Builder $query): float
    {
        $total = (clone $query)->count();
        if ($total === 0) {
            return 0;
        }

        $successful = (clone $query)->completed()->count();

        return round(($successful / $total) * 100, 2);
    }

    protected function getStatsByProvider(array $filters): array
    {
        return Payment::query()
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->completed()
            ->selectRaw('payment_provider_id, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_provider_id')
            ->with('provider:id,name,code')
            ->get()
            ->map(fn ($item) => [
                'provider' => $item->provider?->name ?? 'Inconnu',
                'code' => $item->provider?->code,
                'count' => $item->count,
                'total' => $item->total,
            ])
            ->toArray();
    }

    protected function getStatsByType(array $filters): array
    {
        return Payment::query()
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->completed()
            ->selectRaw('type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->type => [
                    'count' => $item->count,
                    'total' => $item->total,
                ],
            ])
            ->toArray();
    }

    /**
     * Annuler un paiement
     */
    public function cancelPayment(Payment $payment, string $reason = ''): bool
    {
        if (!$payment->canBeCancelled()) {
            return false;
        }

        $payment->markAsCancelled($reason ?: 'Annulé par l\'utilisateur');

        return true;
    }

    /**
     * Obtenir les méthodes de paiement disponibles
     */
    public function getAvailablePaymentMethods(): array
    {
        $providers = PaymentProvider::active()
            ->forCountry('CI')
            ->ordered()
            ->get();

        return $providers->map(function ($provider) {
            return [
                'id' => $provider->id,
                'code' => $provider->code,
                'name' => $provider->name,
                'logo' => $provider->logo,
                'description' => $provider->description,
                'fee_percentage' => $provider->fee_percentage,
                'fee_fixed' => $provider->fee_fixed,
                'min_amount' => $provider->min_amount,
                'max_amount' => $provider->max_amount,
            ];
        })->toArray();
    }

    /**
     * Sauvegarder une méthode de paiement
     */
    public function savePaymentMethod(User $user, array $data): PaymentMethod
    {
        $provider = PaymentProvider::where('code', $data['provider_code'])->firstOrFail();

        // Vérifier si déjà existant
        $existing = PaymentMethod::where('user_id', $user->id)
            ->where('payment_provider_id', $provider->id)
            ->where('phone_number', $data['phone_number'] ?? null)
            ->first();

        if ($existing) {
            return $existing;
        }

        $method = PaymentMethod::create([
            'user_id' => $user->id,
            'payment_provider_id' => $provider->id,
            'type' => PaymentMethod::TYPE_MOBILE_MONEY,
            'label' => $data['label'] ?? null,
            'phone_number' => $data['phone_number'],
            'phone_country_code' => $data['phone_country_code'] ?? '+225',
            'is_default' => $data['is_default'] ?? false,
            'is_verified' => false,
        ]);

        if ($data['is_default'] ?? false) {
            $method->setAsDefault();
        }

        return $method;
    }

    /**
     * Handle payment failure: mark payment as failed and restore any deducted credits.
     * Credits are also restored automatically via PaymentObserver when markAsFailed() is
     * called directly on the model, but this method provides an explicit service-level hook.
     */
    public function onPaymentFailure(Payment $payment, string $reason = ''): void
    {
        if (! in_array($payment->status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED], true)) {
            $payment->markAsFailed($reason ?: 'Paiement échoué');
            // PaymentObserver::updated() will trigger restoreCredits() automatically
        } else {
            // Already in a terminal state — ensure credits are restored if not yet done
            $this->restoreCredits($payment);
        }
    }
}
