<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Residence;
use App\Services\BookingService;
use App\Services\BusinessEventService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * API Booking Controller — mobile-first, JSON only.
 *
 * Handles the complete booking lifecycle:
 *   POST /bookings           → create booking
 *   GET  /bookings           → list my bookings
 *   GET  /bookings/{id}      → booking detail
 *   POST /bookings/{id}/cancel → cancel booking
 *   POST /residences/{id}/price → calculate price
 */
class BookingApiController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected PricingService $pricingService,
    ) {
    }

    /**
     * Create a new booking.
     *
     * Mobile flow: user selects dates → calculates price → confirms → booking created → redirect to payment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'residence_id' => ['required', 'integer', 'exists:residences,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1', 'max:50'],
            'adults' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'children' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'infants' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'message' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        $residence = Residence::where('id', $validated['residence_id'])
            ->where('status', 'approved')
            ->where('is_available', true)
            ->first();

        if (! $residence) {
            return response()->json([
                'success' => false,
                'message' => 'Cette résidence n\'est pas disponible.',
                'error_code' => 'RESIDENCE_UNAVAILABLE',
            ], 404);
        }

        // Check availability before creating
        $availability = $this->bookingService->checkAvailability(
            $residence->id,
            Carbon::parse($validated['check_in']),
            Carbon::parse($validated['check_out']),
        );

        if (! $availability['available']) {
            return response()->json([
                'success' => false,
                'message' => $availability['message'],
                'error_code' => 'DATES_UNAVAILABLE',
                'blocked_dates' => $availability['blocked_dates'] ?? [],
            ], 409);
        }

        try {
            $bookingData = array_merge($validated, [
                'booking_type' => $residence->instant_book ? 'instant' : 'request',
                'adults' => $validated['adults'] ?? $validated['guests'],
                'children' => $validated['children'] ?? 0,
                'infants' => $validated['infants'] ?? 0,
            ]);

            $booking = $this->bookingService->createBooking(
                $residence,
                Auth::user(),
                $bookingData,
            );

            $booking->load('residence.photos');

            // Track business event
            BusinessEventService::bookingCreated(
                Auth::id(),
                $booking->id,
                (float) $booking->total_amount,
                ['type' => $bookingData['booking_type'], 'channel' => 'api'],
            );

            return response()->json([
                'success' => true,
                'message' => $residence->instant_book
                    ? 'Réservation créée. Procédez au paiement.'
                    : 'Demande de réservation envoyée. Procédez au paiement.',
                'data' => new BookingResource($booking),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\Exception $e) {
            report($e);
            Log::channel('critical')->error('API booking creation failed', [
                'user_id' => Auth::id(),
                'residence_id' => $validated['residence_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de créer la réservation. Réessayez.',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * List authenticated user's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->get('per_page', 20)));
        $status = $request->get('status'); // optional filter

        $bookings = Booking::where('user_id', Auth::id())
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with('residence.photos')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'current_page' => $bookings->currentPage(),
                'total_pages' => $bookings->lastPage(),
            ],
        ]);
    }

    /**
     * Get booking details.
     */
    public function show(Booking $booking): JsonResponse
    {
        if ($booking->user_id !== Auth::id() && $booking->residence?->owner_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $booking->load('residence.photos', 'residence.owner', 'payments');

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->bookingService->cancelBooking(
                $booking,
                $request->get('reason', ''),
                'user',
            );

            BusinessEventService::bookingCancelled(
                Auth::id(),
                $booking->id,
                'user',
                $result['refund_amount'],
                ['channel' => 'api'],
            );

            return response()->json([
                'success' => true,
                'message' => 'Réservation annulée.',
                'refund_amount' => $result['refund_amount'],
                'formatted_refund' => number_format((float) $result['refund_amount'], 0, ',', ' ').' FCFA',
            ]);
        } catch (\Exception $e) {
            Log::error('API booking cancellation failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Annulation impossible. Veuillez contacter le support.',
                'error_code' => 'CANCEL_FAILED',
            ], 400);
        }
    }

    /**
     * Calculate price for a residence + dates (pre-booking).
     */
    public function calculatePrice(Request $request, Residence $residence): JsonResponse
    {
        $validated = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $checkIn = Carbon::parse($validated['check_in']);
            $checkOut = Carbon::parse($validated['check_out']);

            // Check availability
            $availability = $this->bookingService->checkAvailability(
                $residence->id,
                $checkIn,
                $checkOut,
            );

            // Calculate price
            $price = $this->pricingService->calculatePrice(
                $residence,
                $checkIn,
                $checkOut,
                $validated['guests'],
                null,
                Auth::user(),
                $validated['coupon_code'] ?? null,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'price' => $price,
                    'availability' => $availability,
                    'nights' => $checkIn->diffInDays($checkOut),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de calculer le prix.',
                'error_code' => 'PRICE_ERROR',
            ], 422);
        }
    }
}
