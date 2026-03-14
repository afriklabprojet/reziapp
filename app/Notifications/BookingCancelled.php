<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Residence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Booking $booking,
        protected Residence $residence,
        protected string $cancelledBy = 'guest',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $guestName = $this->booking->user?->name ?? 'Le client';
        $checkIn = $this->booking->check_in->format('d/m/Y');
        $checkOut = $this->booking->check_out->format('d/m/Y');
        $isGuestCancel = $this->cancelledBy === 'guest';

        $mail = (new MailMessage())
            ->subject("❌ Réservation annulée — {$this->residence->name}")
            ->greeting("Bonjour {$notifiable->name},");

        if ($isGuestCancel) {
            $mail->line("**{$guestName}** a annulé sa réservation pour votre résidence **{$this->residence->name}**.");
        } else {
            $mail->line("La réservation de **{$guestName}** pour **{$this->residence->name}** a été annulée.");
        }

        $mail->line("**Détails :**")
            ->line("• Arrivée : {$checkIn}")
            ->line("• Départ : {$checkOut}")
            ->line("• Réf. : {$this->booking->reference}");

        if ($this->booking->cancellation_reason) {
            $mail->line("**Motif :** {$this->booking->cancellation_reason}");
        }

        return $mail
            ->action('Voir les détails', route('owner.bookings.show', $this->booking))
            ->line('Les dates sont à nouveau disponibles sur votre calendrier.')
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_cancelled',
            'booking_id' => $this->booking->id,
            'residence_id' => $this->residence->id,
            'residence_name' => $this->residence->name,
            'guest_name' => $this->booking->user?->name ?? 'Client',
            'cancelled_by' => $this->cancelledBy,
            'check_in' => $this->booking->check_in->toDateString(),
            'check_out' => $this->booking->check_out->toDateString(),
            'message' => 'Réservation annulée pour ' . $this->residence->name,
        ];
    }
}
