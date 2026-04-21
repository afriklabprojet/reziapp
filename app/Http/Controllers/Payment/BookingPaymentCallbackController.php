<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use App\Services\JekoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingPaymentCallbackController extends Controller
{
    /**
     * Handle successful payment callback from Jeko.
     */
    public function success(Request $request)
    {
        $booking = Booking::where('uuid', $request->query('booking'))->first();

        if (! $booking) {
            return redirect()->route('home')->with('error', 'Réservation introuvable.');
        }

        // Verify payment status with Jeko if we have a reference
        if ($booking->payment_reference) {
            try {
                $jeko = app(JekoPaymentService::class);
                $status = $jeko->getPaymentStatus($booking->payment_reference);
                Log::info('Booking payment callback - Jeko status', [
                    'booking_id' => $booking->id,
                    'status' => $status,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Could not verify Jeko payment status', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Mark booking as paid
        $booking->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        // Set booking status based on instant_book
        $residence = $booking->residence;

        if ($residence?->instant_book) {
            // Instant book → confirmed immediately
            $booking->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // Notify owner of new confirmed booking
            if ($residence->owner) {
                $residence->owner->notify(
                    new \App\Notifications\NewBookingReceived($booking, $residence),
                );

                Notification::send(
                    $residence->owner,
                    'booking',
                    'Nouvelle réservation confirmée',
                    ($booking->user?->name ?? 'Un client').' a réservé '.$residence->name,
                    route('owner.bookings.show', $booking),
                    ['booking_id' => $booking->id],
                );
            }

            // Notify guest
            if ($booking->user) {
                $booking->user->notify(
                    new \App\Notifications\PaymentConfirmed(
                        $this->findOrCreatePayment($booking),
                    ),
                );
            }
        } else {
            // Non-instant → pending owner approval
            $booking->update([
                'status' => 'pending',
                'owner_response_deadline' => now()->addHours(48),
            ]);

            // Notify owner of booking request (already paid)
            if ($residence?->owner) {
                $residence->owner->notify(
                    new \App\Notifications\BookingRequestReceived(
                        $booking,
                        $residence,
                    ),
                );

                Notification::send(
                    $residence->owner,
                    'booking',
                    'Nouvelle demande de réservation (payée)',
                    ($booking->user?->name ?? 'Un client').' a payé et demande à réserver '.$residence->name,
                    route('owner.bookings.requests'),
                    ['booking_id' => $booking->id, 'residence_id' => $residence->id],
                );
            }
        }

        // Send guest booking confirmation email if guest user
        if ($booking->user?->is_guest) {
            try {
                $booking->user->notify(
                    new \App\Notifications\GuestBookingConfirmation($booking, $residence),
                );
            } catch (\Throwable $e) {
                Log::warning('Could not send guest confirmation', ['error' => $e->getMessage()]);
            }
        }

        return view('bookings.payment-success', [
            'booking' => $booking->load('residence.photos', 'residence.owner', 'user'),
        ]);
    }

    /**
     * Handle failed/cancelled payment callback from Jeko.
     */
    public function error(Request $request)
    {
        $booking = Booking::where('uuid', $request->query('booking'))->first();

        if (! $booking) {
            return redirect()->route('home')->with('error', 'Réservation introuvable.');
        }

        Log::warning('Booking payment failed', [
            'booking_id' => $booking->id,
            'uuid' => $booking->uuid,
        ]);

        // Keep booking but mark payment as failed
        $booking->update([
            'payment_status' => 'failed',
        ]);

        return view('bookings.payment-error', [
            'booking' => $booking->load('residence.photos'),
        ]);
    }

    /**
     * Find or create a Payment record for a booking.
     */
    protected function findOrCreatePayment(Booking $booking)
    {
        $payment = \App\Models\Payment::where('booking_id', $booking->id)->first();

        if (! $payment) {
            $payment = \App\Models\Payment::create([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'amount' => $booking->total_amount,
                'total_amount' => $booking->total_amount,
                'currency' => 'XOF',
                'type' => 'booking',
                'status' => 'completed',
                'reference' => $booking->payment_reference,
                'completed_at' => now(),
            ]);
        }

        return $payment;
    }
}
