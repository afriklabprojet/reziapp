<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\BookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Expire les réservations en "pending_payment" dont le délai est dépassé.
 * Libère les dates pour d'autres utilisateurs.
 *
 * Dispatché toutes les 5 minutes par le scheduler ou manuellement.
 */
class ExpireStaleBookings implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('expire-stale-bookings'))
                ->dontRelease()
                ->expireAfter(300),
        ];
    }

    public function handle(BookingService $bookingService): void
    {
        // Bookings pending_payment for more than 30 minutes
        $staleBookings = Booking::where('status', 'pending_payment')
            ->where('created_at', '<', now()->subMinutes(30))
            ->limit(100)
            ->get();

        $expired = 0;

        foreach ($staleBookings as $booking) {
            try {
                $booking->update([
                    'status' => 'expired',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Paiement non reçu dans le délai imparti.',
                ]);

                // Cancel any associated pending/processing payments
                Payment::where('booking_id', $booking->id)
                    ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])
                    ->update([
                        'status' => Payment::STATUS_CANCELLED,
                        'failure_reason' => 'Réservation expirée — timeout paiement.',
                    ]);

                $expired++;
            } catch (\Throwable $e) {
                Log::channel('critical')->error('ExpireStaleBookings: Failed to expire booking', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Stale payments without bookings (orphaned)
        $stalePayments = Payment::whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])
            ->where('created_at', '<', now()->subHours(2))
            ->whereDoesntHave('booking', fn ($q) => $q->whereIn('status', ['confirmed', 'completed']))
            ->limit(50)
            ->get();

        foreach ($stalePayments as $payment) {
            $payment->markAsFailed('Paiement orphelin — expiré automatiquement.');
        }

        if ($expired > 0 || $stalePayments->count() > 0) {
            Log::channel('payments')->info('ExpireStaleBookings: Cleanup completed', [
                'expired_bookings' => $expired,
                'orphaned_payments' => $stalePayments->count(),
            ]);
        }
    }
}
