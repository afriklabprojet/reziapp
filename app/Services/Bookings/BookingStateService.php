<?php

declare(strict_types=1);

namespace App\Services\Bookings;

use App\Exceptions\BookingException;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Services\CacheInvalidationService;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingStateService
{
    public function __construct(
        private readonly ?LoyaltyService $loyaltyService = null,
    ) {}

    public function confirmBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            if ($booking->payment_status !== 'paid') {
                throw new BookingException('Le paiement n\'est pas confirmé.');
            }

            $booking->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            $this->blockDatesForBooking($booking);

            try {
                ($this->loyaltyService ?? app(LoyaltyService::class))->recordBooking($booking->user, $booking);
            } catch (\Throwable $e) {
                Log::warning('LoyaltyService::recordBooking failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);
            }

            try {
                $checkIn = \Carbon\Carbon::parse($booking->check_in)->startOfDay();
                $checkOut = \Carbon\Carbon::parse($booking->check_out)->startOfDay();

                if ($checkIn->lte(now()->startOfDay()) && $checkOut->gt(now()->startOfDay())) {
                    $booking->residence()->update(['is_available' => false]);
                }
            } catch (\Throwable $e) {
                Log::warning('Auto-occupancy on confirm failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);
            }

            return $booking;
        });
    }

    public function cancelBooking(Booking $booking, string $reason, string $cancelledBy = 'user'): array
    {
        return DB::transaction(function () use ($booking, $reason, $cancelledBy): array {
            if (! in_array($booking->status, ['pending', 'pending_payment', 'confirmed'])) {
                throw new BookingException('Cette réservation ne peut pas être annulée.');
            }

            $reason = substr(trim($reason) ?: 'Aucune raison spécifiée', 0, 500);
            $refundAmount = $this->calculateRefundAmount($booking, $cancelledBy);

            $booking->update([
                'status' => 'cancelled_by_'.$cancelledBy,
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy,
                'cancellation_reason' => $reason,
            ]);

            $this->unblockDatesForBooking($booking);

            try {
                $today = now()->toDateString();
                $checkIn = \Carbon\Carbon::parse($booking->check_in)->toDateString();
                $checkOut = \Carbon\Carbon::parse($booking->check_out)->toDateString();

                if ($checkIn <= $today && $checkOut > $today) {
                    $hasActiveBookingToday = Booking::where('residence_id', $booking->residence_id)
                        ->where('status', 'confirmed')
                        ->where('id', '!=', $booking->id)
                        ->where('check_in', '<=', $today)
                        ->where('check_out', '>', $today)
                        ->exists();

                    if (! $hasActiveBookingToday) {
                        $booking->residence()->update(['is_available' => true]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Auto-occupancy on cancel failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);
            }

            Log::info('Booking cancelled', [
                'booking_id' => $booking->id,
                'cancelled_by' => $cancelledBy,
                'refund_amount' => $refundAmount,
            ]);

            CacheInvalidationService::invalidateBooking($booking->residence_id, $booking->user_id);

            return [
                'booking' => $booking,
                'refund_amount' => $refundAmount,
            ];
        });
    }

    public function blockDatesForBooking(Booking $_booking): void
    {
        // Les dates restent implicitement bloquées par les requêtes de disponibilité.
        $_booking->getKey();
    }

    protected function calculateRefundAmount(Booking $booking, string $cancelledBy): float
    {
        $refundAmount = 0.0;
        $totalAmount = (float) $booking->total_amount;

        if ($cancelledBy === 'owner' || $booking->payment_status !== 'paid') {
            return $totalAmount;
        }

        $daysBeforeCheckIn = now()->diffInDays($booking->check_in);
        $policy = $booking->cancellationPolicy;

        if (! $policy) {
            return $totalAmount * 0.5;
        }

        if ($daysBeforeCheckIn >= 7) {
            $refundAmount = $totalAmount;
        } elseif ($daysBeforeCheckIn >= 3) {
            $refundAmount = $totalAmount * 0.5;
        }

        return $refundAmount;
    }

    protected function unblockDatesForBooking(Booking $booking): void
    {
        BlockedDate::where('residence_id', $booking->residence_id)
            ->where('reason', 'booking')
            ->where('start_date', $booking->check_in)
            ->where('end_date', $booking->check_out)
            ->delete();
    }
}

