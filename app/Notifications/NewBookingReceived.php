<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\Residence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Booking $booking,
        protected Residence $residence,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $guestName = $this->booking->user?->name ?? 'Un client';
        $checkIn = $this->booking->check_in->format('d/m/Y');
        $checkOut = $this->booking->check_out->format('d/m/Y');
        $total = number_format($this->booking->total_amount, 0, ',', ' ');

        return (new MailMessage())
            ->subject("🎉 Nouvelle réservation — {$this->residence->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Bonne nouvelle ! **{$guestName}** a réservé votre résidence **{$this->residence->name}**.")
            ->line('**Détails :**')
            ->line("• Arrivée : {$checkIn}")
            ->line("• Départ : {$checkOut}")
            ->line("• Nuits : {$this->booking->nights}")
            ->line("• Montant : {$total} FCFA")
            ->action('Voir la réservation', route('owner.bookings.show', $this->booking))
            ->line('Pensez à préparer votre résidence pour l\'accueil du client.')
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_booking',
            'booking_id' => $this->booking->id,
            'residence_id' => $this->residence->id,
            'residence_name' => $this->residence->name,
            'guest_name' => $this->booking->user?->name ?? 'Client',
            'check_in' => $this->booking->check_in->toDateString(),
            'check_out' => $this->booking->check_out->toDateString(),
            'total_amount' => $this->booking->total_amount,
            'message' => 'Nouvelle réservation pour '.$this->residence->name,
        ];
    }
}
