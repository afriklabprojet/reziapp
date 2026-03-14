<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Booking $booking,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $residence = $this->booking->residence;
        $checkIn = $this->booking->check_in->format('d/m/Y');
        $checkOut = $this->booking->check_out->format('d/m/Y');
        $total = number_format($this->booking->total_amount, 0, ',', ' ');

        return (new MailMessage())
            ->subject("✅ Réservation confirmée — {$residence->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Excellente nouvelle ! Votre réservation a été **confirmée** par le propriétaire.")
            ->line("**Détails de votre séjour :**")
            ->line("• Résidence : {$residence->name}")
            ->line("• Arrivée : {$checkIn}")
            ->line("• Départ : {$checkOut}")
            ->line("• Nuits : {$this->booking->nights}")
            ->line("• Montant : {$total} FCFA")
            ->action('Voir ma réservation', route('bookings.show', $this->booking))
            ->line('Vous recevrez bientôt les instructions d\'arrivée.')
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_confirmed',
            'booking_id' => $this->booking->id,
            'residence_id' => $this->booking->residence_id,
            'residence_name' => $this->booking->residence->name,
            'check_in' => $this->booking->check_in->toDateString(),
            'check_out' => $this->booking->check_out->toDateString(),
            'total_amount' => $this->booking->total_amount,
            'message' => 'Votre réservation a été confirmée',
        ];
    }
}
