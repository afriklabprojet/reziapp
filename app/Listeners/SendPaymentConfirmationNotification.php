<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Notifications\NewBookingReceived;
use App\Notifications\PaymentConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Gère les actions post-paiement : notifications au client et au propriétaire.
 */
class SendPaymentConfirmationNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment->load(['user', 'booking.residence.owner']);

        // Notifier l'utilisateur que le paiement est confirmé
        if ($payment->user) {
            $payment->user->notify(new PaymentConfirmed($payment));
        }

        // Notifier le propriétaire de la nouvelle réservation
        $booking = $payment->booking;
        $residence = $booking?->residence;

        if ($booking && $residence?->owner) {
            $residence->owner->notify(
                new NewBookingReceived($booking, $residence),
            );

            // Notification in-app
            \App\Models\Notification::send(
                $residence->owner,
                'booking',
                'Nouvelle réservation confirmée',
                ($payment->user?->name ?? 'Un client').' a réservé '.$residence->name,
                route('owner.bookings.show', $booking),
                ['booking_id' => $booking->id],
            );
        }
    }
}
