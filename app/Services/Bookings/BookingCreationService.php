<?php

declare(strict_types=1);

namespace App\Services\Bookings;

use App\Exceptions\BookingException;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CancellationPolicy;
use App\Models\Coupon;
use App\Models\PromoCode;
use App\Models\Residence;
use App\Models\User;
use App\Services\CacheInvalidationService;
use App\Services\CouponService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingCreationService
{
    public function __construct(
        private readonly PricingService $pricingService,
        private readonly CouponService $couponService,
        private readonly BookingAvailabilityService $availabilityService,
        private readonly BookingStateService $bookingStateService,
        private readonly BookingNotificationService $bookingNotificationService,
    ) {}

    public function createInstantBooking(Residence $residence, User $user, array $data): Booking
    {
        if (! $residence->instant_book) {
            throw new BookingException('Cette résidence n\'accepte pas les réservations instantanées.');
        }

        $data['booking_type'] = 'instant';

        return $this->createBooking($residence, $user, $data);
    }

    public function createBooking(Residence $residence, User $user, array $data): Booking
    {
        [$checkIn, $checkOut] = $this->validateBookingDates($data);
        $this->assertResidenceCanBeBooked($residence, $user);

        $idempotencyKey = $this->buildBookingIdempotencyKey($residence, $user, $checkIn, $checkOut);
        $this->purgeSoftDeletedBookings($idempotencyKey);

        $priceBreakdown = $this->pricingService->calculatePrice(
            $residence,
            $checkIn,
            $checkOut,
            $data['guests'] ?? 1,
            $data['promo_code'] ?? null,
            $user,
            $data['coupon_code'] ?? null,
        );

        $bookingType = $data['booking_type'] ?? ($residence->instant_book ? 'instant' : 'request');
        $paymentSplit = $this->shouldUsePaymentSplit($data, $checkIn);
        $bookingContext = [
            'data' => $data,
            'price_breakdown' => $priceBreakdown,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'booking_type' => $bookingType,
            'idempotency_key' => $idempotencyKey,
            'payment_split' => $paymentSplit,
        ];

        return DB::transaction(fn () => $this->createBookingWithinTransaction($residence, $user, $bookingContext));
    }

    public function createBookingRequest(Residence $residence, User $user, array $data): BookingRequest
    {
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        if ($checkOut->lte($checkIn)) {
            throw new \InvalidArgumentException('La date de départ doit être après la date d\'arrivée.');
        }

        if ($checkIn->lt(Carbon::today())) {
            throw new \InvalidArgumentException('La date d\'arrivée ne peut pas être dans le passé.');
        }

        if ($residence->owner_id === $user->id) {
            throw new \InvalidArgumentException('Vous ne pouvez pas demander votre propre résidence.');
        }

        $availability = $this->availabilityService->checkAvailability($residence->id, $checkIn, $checkOut);
        if (! $availability['available']) {
            throw new BookingException($availability['message']);
        }

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

        $this->bookingNotificationService->notifyOwnerAboutBookingRequest($residence, $request);

        return $request;
    }

    public function approveBookingRequest(BookingRequest $request, ?string $response = null): Booking
    {
        if (! $request->canBeApproved()) {
            throw new BookingException('Cette demande ne peut plus être approuvée.');
        }

        return DB::transaction(function () use ($request, $response) {
            $request->approve($response);

            return $this->convertRequestToBooking($request);
        });
    }

    public function generateBookingReference(): string
    {
        return 'RZ-'.strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 8));
    }

    protected function convertRequestToBooking(BookingRequest $request): Booking
    {
        $idempotencyKey = 'req_'.$request->id;

        $existing = Booking::where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            Log::info('convertRequestToBooking: Returning existing booking (idempotent)', [
                'booking_id' => $existing->id,
                'booking_request_id' => $request->id,
            ]);

            return $existing;
        }

        $checkIn = $request->check_in instanceof Carbon ? $request->check_in : Carbon::parse($request->check_in);
        $checkOut = $request->check_out instanceof Carbon ? $request->check_out : Carbon::parse($request->check_out);

        $hasConflict = Booking::where('residence_id', $request->residence_id)
            ->whereIn('status', ['pending', 'confirmed', 'pending_payment'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut->copy()->subDay()])
                    ->orWhereBetween('check_out', [$checkIn->copy()->addDay(), $checkOut])
                    ->orWhere(function ($nestedQuery) use ($checkIn, $checkOut) {
                        $nestedQuery->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })
            ->lockForUpdate()
            ->exists();

        if ($hasConflict) {
            throw new BookingException('Cette résidence est déjà réservée pour ces dates.');
        }

        $booking = Booking::create([
            'uuid' => Str::uuid(),
            'idempotency_key' => $idempotencyKey,
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
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $request->markAsConverted($booking);

        return $booking;
    }

    private function validateBookingDates(array $data): array
    {
        if (empty($data['check_in']) || empty($data['check_out'])) {
            throw new \InvalidArgumentException('Les dates de réservation sont obligatoires.');
        }

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        if ($checkOut->lte($checkIn)) {
            throw new \InvalidArgumentException('La date de départ doit être après la date d\'arrivée.');
        }

        if ($checkIn->lt(Carbon::today())) {
            throw new \InvalidArgumentException('La date d\'arrivée ne peut pas être dans le passé.');
        }

        if ($checkIn->diffInDays($checkOut) > 365) {
            throw new \InvalidArgumentException('La durée maximale de réservation est de 365 jours.');
        }

        return [$checkIn, $checkOut];
    }

    private function assertResidenceCanBeBooked(Residence $residence, User $user): void
    {
        if ($residence->owner_id === $user->id) {
            throw new \InvalidArgumentException('Vous ne pouvez pas réserver votre propre résidence.');
        }

        if (! $residence->is_available || ! in_array($residence->status, ['approved', 'active'])) {
            throw new BookingException('Cette résidence n\'est pas disponible à la réservation.');
        }
    }

    private function buildBookingIdempotencyKey(Residence $residence, User $user, Carbon $checkIn, Carbon $checkOut): string
    {
        return 'bk_'.$residence->id.'_'.$user->id.'_'.$checkIn->format('Ymd').'_'.$checkOut->format('Ymd');
    }

    private function purgeSoftDeletedBookings(string $idempotencyKey): void
    {
        Booking::withTrashed()
            ->where('idempotency_key', $idempotencyKey)
            ->whereNotNull('deleted_at')
            ->forceDelete();
    }

    private function shouldUsePaymentSplit(array $data, Carbon $checkIn): bool
    {
        $paymentSplitRequested = ! empty($data['payment_split']) && (bool) $data['payment_split'];
        $splitEligible = now()->startOfDay()->diffInDays($checkIn->copy()->startOfDay(), false) > 30;

        return $paymentSplitRequested && $splitEligible;
    }

    private function createBookingWithinTransaction(Residence $residence, User $user, array $bookingContext): Booking
    {
        $idempotencyKey = $bookingContext['idempotency_key'];
        $checkIn = $bookingContext['check_in'];
        $checkOut = $bookingContext['check_out'];

        $existing = Booking::where('idempotency_key', $idempotencyKey)
            ->whereIn('status', ['pending_payment', 'pending', 'confirmed'])
            ->lockForUpdate()
            ->first();

        if ($existing) {
            Log::info('createBooking: Returning existing booking (idempotent)', [
                'booking_id' => $existing->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            return $existing;
        }

        $this->assertDatesAreStillAvailable($residence, $checkIn, $checkOut);

        $booking = Booking::create($this->buildBookingAttributes($residence, $user, $bookingContext));

        $this->recordDiscountUsage($booking, $user, $bookingContext['price_breakdown']);
        $this->bookingStateService->blockDatesForBooking($booking);

        Log::info('Réservation créée', [
            'booking_id' => $booking->id,
            'reference' => $booking->reference,
            'residence_id' => $residence->id,
            'user_id' => $user->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'total' => $booking->total_amount,
            'type' => $bookingContext['booking_type'],
        ]);

        CacheInvalidationService::invalidateBooking($residence->id, $user->id);

        return $booking;
    }

    private function assertDatesAreStillAvailable(Residence $residence, Carbon $checkIn, Carbon $checkOut): void
    {
        $hasConflict = Booking::where('residence_id', $residence->id)
            ->whereIn('status', ['pending', 'confirmed', 'pending_payment'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut->copy()->subDay()])
                    ->orWhereBetween('check_out', [$checkIn->copy()->addDay(), $checkOut])
                    ->orWhere(function ($nestedQuery) use ($checkIn, $checkOut) {
                        $nestedQuery->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })
            ->lockForUpdate()
            ->exists();

        if ($hasConflict) {
            throw new BookingException('Cette résidence est déjà réservée pour ces dates.');
        }

        if (BlockedDate::hasBlockedDatesInRange($residence->id, $checkIn, $checkOut)) {
            throw new BookingException('Certaines dates sont indisponibles.');
        }
    }

    private function buildBookingAttributes(Residence $residence, User $user, array $bookingContext): array
    {
        $data = $bookingContext['data'];
        $priceBreakdown = $bookingContext['price_breakdown'];
        $checkIn = $bookingContext['check_in'];
        $checkOut = $bookingContext['check_out'];
        $bookingType = $bookingContext['booking_type'];
        $idempotencyKey = $bookingContext['idempotency_key'];
        $paymentSplit = $bookingContext['payment_split'];
        $depositAmount = $paymentSplit ? (int) round($priceBreakdown['total_amount'] * 0.5) : null;
        $cancellationPolicyId = $residence->cancellation_policy_id
            ?? CancellationPolicy::where('is_default', true)->value('id')
            ?? CancellationPolicy::first()?->id;

        return [
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
            'payment_split' => $paymentSplit,
            'deposit_amount' => $depositAmount,
            'balance_amount' => $paymentSplit ? $priceBreakdown['total_amount'] - $depositAmount : null,
            'balance_due_at' => $paymentSplit ? $checkIn->copy()->subDays(30)->toDateString() : null,
        ];
    }

    private function recordDiscountUsage(Booking $booking, User $user, array $priceBreakdown): void
    {
        if ($priceBreakdown['promo_code']) {
            PromoCode::where('code', $priceBreakdown['promo_code']['code'])->first()?->recordUsage($user, $booking);
        }

        if (! $priceBreakdown['coupon'] || $priceBreakdown['coupon_discount'] <= 0) {
            return;
        }

        $coupon = Coupon::find($priceBreakdown['coupon']['id']);
        if ($coupon) {
            $this->couponService->recordUsage($coupon, $user, $booking, $priceBreakdown['coupon_discount']);
        }
    }
}

