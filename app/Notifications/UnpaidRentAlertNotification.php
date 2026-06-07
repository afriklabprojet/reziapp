<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte envoyée au propriétaire pour un paiement de location impayé.
 */
class UnpaidRentAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Booking $booking,
        protected int $daysOverdue,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $residence  = $this->booking->residence;
        $tenant     = $this->booking->user;
        $amount     = number_format($this->booking->total_amount, 0, ',', ' ');
        $currency   = 'XOF';
        $checkIn    = $this->booking->check_in->format('d/m/Y');
        $checkOut   = $this->booking->check_out->format('d/m/Y');
        $detailUrl  = route('owner.bookings.show', $this->booking);

        $urgency = match (true) {
            $this->daysOverdue >= 15 => '🔴 URGENT — ',
            $this->daysOverdue >= 7  => '⚠️ ',
            default                  => '',
        };

        return (new MailMessage())
            ->subject("{$urgency}Paiement en attente — {$residence->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un paiement pour votre résidence **{$residence->title}** est en attente depuis **{$this->daysOverdue} jour(s)**.")
            ->line('**Détails de la réservation :')
            ->line("Locataire : {$tenant->name}")
            ->line("Séjour : du {$checkIn} au {$checkOut}")
            ->line("Montant dû : **{$amount} {$currency}**")
            ->when($this->daysOverdue >= 7, fn (MailMessage $m) => $m->line('Nous vous recommandons de contacter votre locataire rapidement.'))
            ->action('Voir la réservation', $detailUrl)
            ->line('Si le paiement a déjà été effectué en dehors de la plateforme, vous pouvez marquer la réservation comme réglée.')
            ->salutation('L\'équipe Rezi Studio Meublé Faya');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'unpaid_rent_alert',
            'booking_id'  => $this->booking->id,
            'days_overdue' => $this->daysOverdue,
            'amount'      => $this->booking->total_amount,
        ];
    }
}
