<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BalanceOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $residence = $this->booking->residence;
        $balance = number_format((float) $this->booking->balance_amount, 0, ',', ' ');

        return (new MailMessage())
            ->subject("🚨 Solde en retard — {$residence->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le solde de votre réservation **{$residence->name}** ({$balance} FCFA) est en retard de paiement.")
            ->line('Merci de régulariser sous 48 h pour ne pas perdre votre réservation.')
            ->action('Régler maintenant', route('bookings.show', $this->booking))
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'balance_overdue',
            'booking_id' => $this->booking->id,
            'residence_name' => $this->booking->residence->name,
            'balance_amount' => $this->booking->balance_amount,
            'message' => 'Solde de réservation en retard',
        ];
    }
}
