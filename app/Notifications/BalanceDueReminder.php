<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BalanceDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Booking $booking)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $residence = $this->booking->residence;
        $balance = number_format((float) $this->booking->balance_amount, 0, ',', ' ');
        $dueAt = optional($this->booking->balance_due_at)->format('d/m/Y');

        return (new MailMessage())
            ->subject("⏰ Solde à régler — {$residence->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre arrivée approche pour **{$residence->name}**.")
            ->line("Conformément à votre choix de paiement échelonné, le solde de **{$balance} FCFA** est à régler avant le **{$dueAt}**.")
            ->action('Régler le solde maintenant', route('bookings.show', $this->booking))
            ->line('Sans paiement à la date prévue, votre réservation pourrait être annulée automatiquement.')
            ->salutation('L\'équipe ReziApp');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'balance_due_reminder',
            'booking_id' => $this->booking->id,
            'residence_name' => $this->booking->residence->name,
            'balance_amount' => $this->booking->balance_amount,
            'balance_due_at' => optional($this->booking->balance_due_at)->toDateString(),
            'message' => 'Solde de réservation à régler avant '.optional($this->booking->balance_due_at)->format('d/m/Y'),
        ];
    }
}
