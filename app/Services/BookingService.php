<?php

namespace App\Services;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CancellationPolicy;
use App\Models\Coupon;
use App\Models\PromoCode;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingService
{
    protected PricingService $pricingService;
    protected PaymentService $paymentService;
    protected CouponService $couponService;

    public function __construct(PricingService $pricingService, PaymentService $paymentService, CouponService $couponService)
    {
        $this->pricingService = $pricingService;
        $this->paymentService = $paymentService;
        $this->couponService = $couponService;
    }

    /**
     * Vérifier la disponibilité d'une résidence
     */
    public function checkAvailability(
        int $residenceId,
        Carbon $checkIn,
        Carbon $checkOut,
    ): array {
        // Vérifier les dates bloquées
        $hasBlockedDates = BlockedDate::hasBlockedDatesInRange($residenceId, $checkIn, $checkOut);

        if ($hasBlockedDates) {
            $blockedDates = BlockedDate::getBlockedDatesArray($residenceId, $checkIn, $checkOut);

            return [
                'available' => false,
                'reason' => 'dates_blocked',
                'blocked_dates' => $blockedDates,
                'message' => 'Certaines dates sont indisponibles.',
            ];
        }

        // Vérifier les réservations existantes
        $hasExistingBooking = Booking::where('residence_id', $residenceId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut->copy()->subDay()])
                    ->orWhereBetween('check_out', [$checkIn->copy()->addDay(), $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })
            ->exists();

        if ($hasExistingBooking) {
            return [
                'available' => false,
                'reason' => 'already_booked',
                'message' => 'Cette résidence est déjà réservée pour ces dates.',
            ];
        }

        // Vérifier les demandes en attente
        $hasPendingRequest = BookingRequest::where('residence_id', $residenceId)
            ->where('status', 'pending')
            ->notExpired()
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut->copy()->subDay()])
                    ->orWhereBetween('check_out', [$checkIn->copy()->addDay(), $checkOut]);
            })
            ->exists();

        return [
            'available' => true,
            'has_pending_request' => $hasPendingRequest,
            'message' => $hasPendingRequest
                ? 'Disponible, mais une demande est en attente pour certaines dates.'
                : 'Disponible pour ces dates.',
        ];
    }

    /**
     * Obtenir les dates indisponibles pour une résidence
     */
    public function getUnavailableDates(int $residenceId, Carbon $startDate, Carbon $endDate): array
    {
        $dates = [];

        // Dates bloquées
        $blockedDates = BlockedDate::getBlockedDatesArray($residenceId, $startDate, $endDate);
        $dates = array_merge($dates, $blockedDates);

        // Réservations confirmées
        $bookings = Booking::where('residence_id', $residenceId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_in', '<=', $endDate)
            ->where('check_out', '>=', $startDate)
            ->get();

        foreach ($bookings as $booking) {
            $current = Carbon::parse($booking->check_in);
            $end = Carbon::parse($booking->check_out);

            while ($current < $end) {
                $dates[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        return array_unique($dates);
    }

    /**
     * Créer une réservation instantanée
     */
    public function createInstantBooking(
        Residence $residence,
        User $user,
        array $data,
    ): Booking {
        // Vérifier que la résidence accepte les réservations instantanées
        if (!$residence->instant_book) {
            throw new \Exception('Cette résidence n\'accepte pas les réservations instantanées.');
        }

        $data['booking_type'] = 'instant';

        return $this->createBooking($residence, $user, $data);
    }

    /**
     * Créer une réservation (unifié — instant ou demande).
     * Le paiement se fait pendant la réservation via Jeko redirect.
     * Le statut final (confirmed vs pending) est déterminé après paiement
     * selon $residence->instant_book.
     *
     * IDEMPOTENT: Uses idempotency_key to prevent duplicate bookings on rapid resubmit.
     */
    public function createBooking(
        Residence $residence,
        User $user,
        array $data,
    ): Booking {
        // ─── Edge case validation ───
        if (empty($data['check_in']) || empty($data['check_out'])) {
            throw new \InvalidArgumentException('Les dates de réservation sont obligatoires.');
        }

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        // Vérifier la cohérence des dates
        if ($checkOut->lte($checkIn)) {
            throw new \InvalidArgumentException('La date de départ doit être après la date d\'arrivée.');
        }

        if ($checkIn->lt(Carbon::today())) {
            throw new \InvalidArgumentException('La date d\'arrivée ne peut pas être dans le passé.');
        }

        // Limiter la durée (365 jours max)
        if ($checkIn->diffInDays($checkOut) > 365) {
            throw new \InvalidArgumentException('La durée maximale de réservation est de 365 jours.');
        }

        // Empêcher le propriétaire de réserver sa propre résidence
        if ($residence->owner_id === $user->id) {
            throw new \InvalidArgumentException('Vous ne pouvez pas réserver votre propre résidence.');
        }

        // Vérifier que la résidence est disponible
        if (!$residence->is_available || !in_array($residence->status, ['approved', 'active'])) {
            throw new \Exception('Cette résidence n\'est pas disponible à la réservation.');
        }

        // ─── Idempotency: detect duplicate submissions ───
        $idempotencyKey = 'bk_'.$residence->id.'_'.$user->id.'_'.$checkIn->format('Ymd').'_'.$checkOut->format('Ymd');

        // Purger les bookings soft-deleted avec la même clé pour permettre une nouvelle
        // tentative après un paiement échoué ou annulé (sinon la contrainte unique MySQL bloque)
        Booking::withTrashed()
            ->where('idempotency_key', $idempotencyKey)
            ->whereNotNull('deleted_at')
            ->forceDelete();

        $existing = Booking::where('idempotency_key', $idempotencyKey)
            ->whereIn('status', ['pending_payment', 'pending', 'confirmed'])
            ->first();

        if ($existing) {
            Log::info('createBooking: Returning existing booking (idempotent)', [
                'booking_id' => $existing->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            return $existing;
        }

        // Calculer le prix AVANT la transaction (lecture seule, pas besoin de lock)
        $priceBreakdown = $this->pricingService->calculatePrice(
            $residence,
            $checkIn,
            $checkOut,
            $data['guests'] ?? 1,
            $data['promo_code'] ?? null,
            $user,
            $data['coupon_code'] ?? null,
        );

        // Déterminer le type de réservation
        $bookingType = $data['booking_type'] ?? ($residence->instant_book ? 'instant' : 'request');

        // Sprint 3 — Paiement échelonné 50/50 (éligible si check-in > J+30)
        $paymentSplit = !empty($data['payment_split']) && (bool) $data['payment_split'];
        $splitEligible = now()->startOfDay()->diffInDays($checkIn->copy()->startOfDay(), false) > 30;
        $paymentSplit = $paymentSplit && $splitEligible;

        return DB::transaction(function () use ($residence, $user, $data, $priceBreakdown, $checkIn, $checkOut, $bookingType, $idempotencyKey, $paymentSplit) {
            // SECURITE : Vérification de disponibilité DANS la transaction avec lock pessimiste
            // Empêche les double bookings par requêtes concurrentes (race condition)
            $hasConflict = Booking::where('residence_id', $residence->id)
                ->whereIn('status', ['pending', 'confirmed', 'pending_payment'])
                ->where(function ($query) use ($checkIn, $checkOut) {
                    $query->whereBetween('check_in', [$checkIn, $checkOut->copy()->subDay()])
                        ->orWhereBetween('check_out', [$checkIn->copy()->addDay(), $checkOut])
                        ->orWhere(function ($q) use ($checkIn, $checkOut) {
                            $q->where('check_in', '<=', $checkIn)
                                ->where('check_out', '>=', $checkOut);
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw new \Exception('Cette résidence est déjà réservée pour ces dates.');
            }

            // Vérifier les dates bloquées
            $hasBlockedDates = BlockedDate::hasBlockedDatesInRange($residence->id, $checkIn, $checkOut);
            if ($hasBlockedDates) {
                throw new \Exception('Certaines dates sont indisponibles.');
            }
            // Résoudre la politique d'annulation (fallback sur la politique par défaut)
            $cancellationPolicyId = $residence->cancellation_policy_id
                ?? CancellationPolicy::where('is_default', true)->value('id')
                ?? CancellationPolicy::first()?->id;

            // Créer la réservation
            $booking = Booking::create([
                'uuid' => Str::uuid(),
                'idempotency_key' => $idempotencyKey,
                'reference' => $this->generateBookingReference(),
                'residence_id' => $residence->id,
                'user_id' => $user->id,
                'cancellation_policy_id' => $cancellationPolicyId,
                'promo_code_id' => $priceBreakdown['promo_code']['id'] ?? null,

                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'check_in_time' => $data['check_in_time'] ?? '14:00',
                'check_out_time' => $data['check_out_time'] ?? '11:00',
                'nights' => $priceBreakdown['nights'],
                'guests' => $data['guests'] ?? 1,
                'adults' => $data['adults'] ?? 1,
                'children' => $data['children'] ?? 0,
                'infants' => $data['infants'] ?? 0,

                'booking_type' => $bookingType,

                'price_per_night' => $priceBreakdown['avg_price_per_night'],
                'subtotal' => $priceBreakdown['subtotal'],
                'cleaning_fee' => $priceBreakdown['cleaning_fee'],
                'service_fee' => $priceBreakdown['service_fee'],
                'long_stay_discount' => $priceBreakdown['long_stay_discount'],
                'promo_discount' => $priceBreakdown['promo_discount'],
                'coupon_code' => $priceBreakdown['coupon']['code'] ?? null,
                'coupon_id' => $priceBreakdown['coupon']['id'] ?? null,
                'coupon_discount' => $priceBreakdown['coupon_discount'],
                'discount_amount' => $priceBreakdown['total_discount'],
                'taxes' => $priceBreakdown['taxes'],
                'total_amount' => $priceBreakdown['total_amount'],
                'currency' => 'XOF',
                'price_breakdown' => $priceBreakdown,

                'guest_message' => $data['message'] ?? null,
                'status' => 'pending_payment',
                'payment_status' => 'pending',

                // Sprint 3 — Paiement échelonné 50/50
                'payment_split' => $paymentSplit,
                'deposit_amount' => $paymentSplit ? (int) round($priceBreakdown['total_amount'] * 0.5) : null,
                'balance_amount' => $paymentSplit ? $priceBreakdown['total_amount'] - (int) round($priceBreakdown['total_amount'] * 0.5) : null,
                'balance_due_at' => $paymentSplit ? $checkIn->copy()->subDays(30)->toDateString() : null,
            ]);

            // Enregistrer l'utilisation du code promo
            if ($priceBreakdown['promo_code']) {
                $promoCode = PromoCode::where('code', $priceBreakdown['promo_code']['code'])->first();
                $promoCode?->recordUsage($user, $booking);
            }

            // Enregistrer l'utilisation du coupon propriétaire
            if ($priceBreakdown['coupon'] && $priceBreakdown['coupon_discount'] > 0) {
                $coupon = Coupon::find($priceBreakdown['coupon']['id']);
                if ($coupon) {
                    $this->couponService->recordUsage($coupon, $user, $booking, $priceBreakdown['coupon_discount']);
                }
            }

            // Bloquer les dates
            $this->blockDatesForBooking($booking);

            Log::info('Réservation créée', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'residence_id' => $residence->id,
                'user_id' => $user->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'total' => $booking->total_amount,
                'type' => $bookingType,
            ]);

            // Invalidate relevant caches
            CacheInvalidationService::invalidateBooking($residence->id, $user->id);

            return $booking;
        });
    }

    /**
     * Créer une demande de réservation
     */
    public function createBookingRequest(
        Residence $residence,
        User $user,
        array $data,
    ): BookingRequest {
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        // Edge case validation
        if ($checkOut->lte($checkIn)) {
            throw new \InvalidArgumentException('La date de départ doit être après la date d\'arrivée.');
        }

        if ($checkIn->lt(Carbon::today())) {
            throw new \InvalidArgumentException('La date d\'arrivée ne peut pas être dans le passé.');
        }

        if ($residence->owner_id === $user->id) {
            throw new \InvalidArgumentException('Vous ne pouvez pas demander votre propre résidence.');
        }

        // Vérifier la disponibilité
        $availability = $this->checkAvailability($residence->id, $checkIn, $checkOut);
        if (!$availability['available']) {
            throw new \Exception($availability['message']);
        }

        // Calculer le prix
        $priceBreakdown = $this->pricingService->calculatePrice(
            $residence,
            $checkIn,
            $checkOut,
            $data['guests'] ?? 1,
            $data['promo_code'] ?? null,
            $user,
            $data['coupon_code'] ?? null,
        );

        $request = BookingRequest::create([
            'residence_id' => $residence->id,
            'user_id' => $user->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $data['guests'] ?? 1,
            'adults' => $data['adults'] ?? 1,
            'children' => $data['children'] ?? 0,
            'infants' => $data['infants'] ?? 0,
            'message' => $data['message'] ?? null,
            'special_requests' => $data['special_requests'] ?? null,

            'price_per_night' => $priceBreakdown['avg_price_per_night'],
            'total_nights' => $priceBreakdown['nights'],
            'subtotal' => $priceBreakdown['subtotal'],
            'cleaning_fee' => $priceBreakdown['cleaning_fee'],
            'service_fee' => $priceBreakdown['service_fee'],
            'long_stay_discount' => $priceBreakdown['long_stay_discount'],
            'promo_discount' => $priceBreakdown['promo_discount'],
            'total_amount' => $priceBreakdown['total_amount'],

            'status' => 'pending',
            'expires_at' => now()->addHours(48),
        ]);

        // Notifier le propriétaire
        $residence->owner->notify(new \App\Notifications\BookingRequestReceived($request, $residence));

        // Notification in-app
        \App\Models\Notification::send(
            $residence->owner,
            'booking',
            'Nouvelle demande de réservation',
            ($request->user?->name ?? 'Un client').' souhaite réserver '.$residence->name,
            route('owner.bookings.requests'),
            ['booking_request_id' => $request->id, 'residence_id' => $residence->id],
        );

        return $request;
    }

    /**
     * Approuver une demande de réservation
     */
    public function approveBookingRequest(BookingRequest $request, ?string $response = null): Booking
    {
        if (!$request->canBeApproved()) {
            throw new \Exception('Cette demande ne peut plus être approuvée.');
        }

        return DB::transaction(function () use ($request, $response) {
            $request->approve($response);

            // Convertir en réservation
            $booking = $this->convertRequestToBooking($request);

            // Notifier le voyageur
            // $request->user->notify(new BookingRequestApproved($booking));

            return $booking;
        });
    }

    /**
     * Convertir une demande approuvée en réservation
     */
    protected function convertRequestToBooking(BookingRequest $request): Booking
    {
        $booking = Booking::create([
            'uuid' => Str::uuid(),
            'reference' => $this->generateBookingReference(),
            'residence_id' => $request->residence_id,
            'user_id' => $request->user_id,
            'cancellation_policy_id' => $request->residence->cancellation_policy_id,

            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'nights' => $request->total_nights,
            'guests' => $request->guests,
            'adults' => $request->adults,
            'children' => $request->children,
            'infants' => $request->infants,

            'booking_type' => 'request',

            'price_per_night' => $request->price_per_night,
            'subtotal' => $request->subtotal,
            'cleaning_fee' => $request->cleaning_fee,
            'service_fee' => $request->service_fee,
            'long_stay_discount' => $request->long_stay_discount,
            'promo_discount' => $request->promo_discount,
            'discount_amount' => $request->long_stay_discount + $request->promo_discount,
            'total_amount' => $request->total_amount,
            'currency' => 'XOF',

            'guest_message' => $request->message,
            'status' => 'pending', // En attente de paiement
            'payment_status' => 'pending',
        ]);

        $request->markAsConverted($booking);

        return $booking;
    }

    /**
     * Confirmer une réservation après paiement
     */
    public function confirmBooking(Booking $booking): Booking
    {
        if ($booking->payment_status !== 'paid') {
            throw new \Exception('Le paiement n\'est pas confirmé.');
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        // Bloquer définitivement les dates
        $this->blockDatesForBooking($booking);

        // Créditer la fidélité du locataire
        try {
            (new LoyaltyService())->recordBooking($booking->user, $booking);
        } catch (\Throwable $e) {
            Log::warning('LoyaltyService::recordBooking failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);
        }

        // Envoyer les confirmations
        // $booking->user->notify(new BookingConfirmation($booking));
        // $booking->residence->owner->notify(new BookingConfirmedForOwner($booking));

        return $booking;
    }

    /**
     * Bloquer les dates pour une réservation
     */
    protected function blockDatesForBooking(Booking $booking): void
    {
        // Les dates sont automatiquement bloquées via les requêtes de disponibilité
        // Cette méthode peut être utilisée pour créer des BlockedDate explicites si nécessaire
    }

    /**
     * Générer une référence unique de réservation
     */
    protected function generateBookingReference(): string
    {
        do {
            $reference = 'RZ-'.strtoupper(Str::random(8));
        } while (Booking::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Obtenir le calendrier de disponibilité
     */
    public function getAvailabilityCalendar(int $residenceId, int $months = 3): array
    {
        $startDate = today();
        $endDate = today()->addMonths($months);

        $unavailableDates = $this->getUnavailableDates($residenceId, $startDate, $endDate);

        // Prix spéciaux
        $specialPrices = \App\Models\SpecialPrice::getPricesForDateRange(
            $residenceId,
            $startDate,
            $endDate,
        );

        $residence = Residence::find($residenceId);
        $basePricePerNight = $residence->price_per_night;

        $calendar = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $calendar[$dateStr] = [
                'date' => $dateStr,
                'available' => !in_array($dateStr, $unavailableDates),
                'price' => $specialPrices[$dateStr] ?? $basePricePerNight,
                'is_special_price' => isset($specialPrices[$dateStr]),
                'is_weekend' => $current->isWeekend(),
            ];
            $current->addDay();
        }

        return $calendar;
    }

    /**
     * Annuler une réservation
     */
    public function cancelBooking(
        Booking $booking,
        string $reason,
        string $cancelledBy = 'user',
    ): array {
        if (!in_array($booking->status, ['pending', 'pending_payment', 'confirmed'])) {
            throw new \Exception('Cette réservation ne peut pas être annulée.');
        }

        // Guard: prevent empty reason
        $reason = trim($reason) ?: 'Aucune raison spécifiée';
        $reason = substr($reason, 0, 500); // Limit length

        // Calculer le remboursement (via le service d'annulation)
        $refundAmount = $this->calculateRefundAmount($booking, $cancelledBy);

        $booking->update([
            'status' => 'cancelled_by_'.$cancelledBy,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy,
            'cancellation_reason' => $reason,
        ]);

        // Débloquer les dates
        $this->unblockDatesForBooking($booking);

        Log::info('Booking cancelled', [
            'booking_id' => $booking->id,
            'cancelled_by' => $cancelledBy,
            'refund_amount' => $refundAmount,
        ]);

        // Invalidate caches (availability re-opens, stats change)
        CacheInvalidationService::invalidateBooking(
            $booking->residence_id,
            $booking->user_id,
        );

        return [
            'booking' => $booking,
            'refund_amount' => $refundAmount,
        ];
    }

    /**
     * Calculer le montant du remboursement
     */
    protected function calculateRefundAmount(Booking $booking, string $cancelledBy): float
    {
        if ($cancelledBy === 'owner' || $booking->payment_status !== 'paid') {
            return $booking->total_amount;
        }

        // Appliquer la politique d'annulation
        $daysBeforeCheckIn = now()->diffInDays($booking->check_in);
        $policy = $booking->cancellationPolicy;

        if (!$policy) {
            return $booking->total_amount * 0.5; // Par défaut 50%
        }

        // Logique selon la politique
        if ($daysBeforeCheckIn >= 7) {
            return $booking->total_amount;
        } elseif ($daysBeforeCheckIn >= 3) {
            return $booking->total_amount * 0.5;
        }

        return 0;
    }

    /**
     * Débloquer les dates d'une réservation annulée
     */
    protected function unblockDatesForBooking(Booking $booking): void
    {
        BlockedDate::where('residence_id', $booking->residence_id)
            ->where('reason', 'booking')
            ->where('start_date', $booking->check_in)
            ->where('end_date', $booking->check_out)
            ->delete();
    }

    /**
     * Obtenir les statistiques de réservation pour un propriétaire
     */
    public function getOwnerBookingStats(int $ownerId): array
    {
        $residenceIds = Residence::where('owner_id', $ownerId)->pluck('id');

        $pendingCount = Booking::whereIn('residence_id', $residenceIds)
            ->where('status', 'pending')
            ->count();

        $confirmedCount = Booking::whereIn('residence_id', $residenceIds)
            ->where('status', 'confirmed')
            ->count();

        $monthlyRevenue = Booking::whereIn('residence_id', $residenceIds)
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        $pendingRequests = BookingRequest::whereIn('residence_id', $residenceIds)
            ->where('status', 'pending')
            ->count();

        return [
            'pending_bookings' => $pendingCount,
            'confirmed_bookings' => $confirmedCount,
            'monthly_revenue' => $monthlyRevenue,
            'pending_requests' => $pendingRequests,
        ];
    }
}
