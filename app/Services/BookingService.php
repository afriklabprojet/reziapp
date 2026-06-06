<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Residence;
use App\Models\User;
use App\Services\Bookings\BookingAvailabilityService;
use App\Services\Bookings\BookingCreationService;
use App\Services\Bookings\BookingNotificationService;
use App\Services\Bookings\BookingStateService;
use Carbon\Carbon;

class BookingService
{
    protected BookingAvailabilityService $availabilityService;

    protected BookingCreationService $creationService;

    protected BookingStateService $stateService;

    public function __construct(
        protected PricingService $pricingService,
        protected PaymentService $paymentService,
        protected CouponService $couponService,
        ?BookingAvailabilityService $availabilityService = null,
        ?BookingCreationService $creationService = null,
        ?BookingStateService $stateService = null,
        ?BookingNotificationService $notificationService = null,
    ) {
        $this->availabilityService = $availabilityService ?? new BookingAvailabilityService();
        $this->stateService = $stateService ?? new BookingStateService(app(LoyaltyService::class));
        $this->creationService = $creationService ?? new BookingCreationService(
            $this->pricingService,
            $this->couponService,
            $this->availabilityService,
            $this->stateService,
            $notificationService ?? app(BookingNotificationService::class),
        );
    }

    /**
     * Vérifier la disponibilité d'une résidence
     */
    public function checkAvailability(
        int $residenceId,
        Carbon $checkIn,
        Carbon $checkOut,
    ): array {
        return $this->availabilityService->checkAvailability($residenceId, $checkIn, $checkOut);
    }

    /**
     * Obtenir les dates indisponibles pour une résidence
     */
    public function getUnavailableDates(int $residenceId, Carbon $startDate, Carbon $endDate): array
    {
        return $this->availabilityService->getUnavailableDates($residenceId, $startDate, $endDate);
    }

    /**
     * Créer une réservation instantanée
     */
    public function createInstantBooking(
        Residence $residence,
        User $user,
        array $data,
    ): Booking {
        return $this->creationService->createInstantBooking($residence, $user, $data);
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
        return $this->creationService->createBooking($residence, $user, $data);
    }

    /**
     * Créer une demande de réservation
     */
    public function createBookingRequest(
        Residence $residence,
        User $user,
        array $data,
    ): BookingRequest {
        return $this->creationService->createBookingRequest($residence, $user, $data);
    }

    /**
     * Approuver une demande de réservation
     */
    public function approveBookingRequest(BookingRequest $request, ?string $response = null): Booking
    {
        return $this->creationService->approveBookingRequest($request, $response);
    }

    /**
     * Confirmer une réservation après paiement
     */
    public function confirmBooking(Booking $booking): Booking
    {
        return $this->stateService->confirmBooking($booking);
    }

    /**
     * Générer une référence unique de réservation.
     *
     * Utilise un UUID v4 tronqué pour garantir l'unicité sans SELECT.
     * L'espace de collision est 16^8 = ~4 milliards de valeurs possibles,
     * ce qui est suffisant pour éviter tout doublon en production sans boucle.
     */
    protected function generateBookingReference(): string
    {
        return $this->creationService->generateBookingReference();
    }

    /**
     * Obtenir le calendrier de disponibilité
     */
    public function getAvailabilityCalendar(int $residenceId, int $months = 3): array
    {
        return $this->availabilityService->getAvailabilityCalendar($residenceId, $months);
    }

    /**
     * Annuler une réservation
     */
    public function cancelBooking(
        Booking $booking,
        string $reason,
        string $cancelledBy = 'user',
    ): array {
        return $this->stateService->cancelBooking($booking, $reason, $cancelledBy);
    }

    /**
     * Obtenir les statistiques de réservation pour un propriétaire
     */
    public function getOwnerBookingStats(int $ownerId): array
    {
        return $this->availabilityService->getOwnerBookingStats($ownerId);
    }
}

