<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\OwnerBalance;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentProvider;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
     * Créer un paiement pour une réservation
     */
    public function createBookingPayment(Booking $booking, User $user, array $options = []): Payment
    {
        $provider = PaymentProvider::where('code', $options['provider'] ?? 'jeko')->first();

        $fees = $provider?->calculateFees($booking->total_amount) ?? [
            'total_fee' => 0,
            'total_amount' => $booking->total_amount,
        ];

        return Payment::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'payment_provider_id' => $provider?->id,
            'payment_method_id' => $options['payment_method_id'] ?? null,
            'amount' => $booking->total_amount,
            'fee' => $fees['total_fee'],
            'total_amount' => $fees['total_amount'],
            'currency' => 'XOF',
            'type' => Payment::TYPE_BOOKING,
            'status' => Payment::STATUS_PENDING,
            'metadata' => [
                'booking_reference' => $booking->reference,
                'residence_id' => $booking->residence_id,
                'check_in' => $booking->check_in->toDateString(),
                'check_out' => $booking->check_out->toDateString(),
            ],
        ]);
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

        return $this->jekoService->initiateMobileMoneyPayment($payment, $phoneNumber, $operator);
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
     * Traiter le succès d'un paiement
     */
    public function onPaymentSuccess(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            // Recharger le paiement
            $payment->refresh();

            // Confirmer la réservation si applicable
            if ($payment->booking) {
                $payment->booking->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);

                // Créditer le propriétaire (en attente)
                $this->creditOwner($payment);
            }

            // Générer la facture
            $this->invoiceService->generateFromPayment($payment);

            // Envoyer les notifications
            $this->sendPaymentConfirmation($payment);
        });
    }

    /**
     * Créditer le propriétaire
     */
    protected function creditOwner(Payment $payment): void
    {
        if (!$payment->booking?->residence?->owner_id) {
            return;
        }

        $ownerId = $payment->booking->residence->owner_id;
        $balance = OwnerBalance::getOrCreateForUser($ownerId);

        // Calculer le montant propriétaire (moins commission REZI)
        $commissionRate = config('rezi.pricing.owner_commission_rate', 0.03);
        $ownerSubtotal = (float) $payment->booking->subtotal + (float) $payment->booking->cleaning_fee;
        $ownerAmount = round($ownerSubtotal * (1 - $commissionRate), 0);

        $balance->addPendingEarnings($ownerAmount);
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
                ($payment->user?->name ?? 'Un client') . ' a réservé ' . $payment->booking->residence->name,
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

    protected function calculateSuccessRate($query): float
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
}
