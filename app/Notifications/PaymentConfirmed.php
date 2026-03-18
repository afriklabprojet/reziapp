<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Payment $payment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->payment->total_amount, 0, ',', ' ');
        $residence = $this->payment->booking?->residence;
        $residenceName = $residence?->name ?? 'votre réservation';

        return (new MailMessage)
            ->subject("✅ Paiement confirmé — {$amount} FCFA")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre paiement de **{$amount} FCFA** a été confirmé avec succès.")
            ->line("**Détails du paiement :**")
            ->line("• Résidence : {$residenceName}")
            ->line("• Montant : {$amount} FCFA")
            ->line("• Référence : {$this->payment->reference}")
            ->line("• Date : " . ($this->payment->completed_at?->format('d/m/Y à H:i') ?? now()->format('d/m/Y à H:i')))
            ->when($this->payment->booking, function ($mail) {
                $checkIn = $this->payment->booking->check_in?->format('d/m/Y');
                $checkOut = $this->payment->booking->check_out?->format('d/m/Y');

                return $mail->line("• Arrivée : {$checkIn}")
                    ->line("• Départ : {$checkOut}");
            })
            ->action('Voir ma réservation', $this->payment->booking
                ? route('bookings.show', $this->payment->booking)
                : url('/'))
            ->line('Une facture vous sera envoyée séparément.')
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_confirmed',
            'payment_id' => $this->payment->id,
            'booking_id' => $this->payment->booking_id,
            'amount' => $this->payment->total_amount,
            'reference' => $this->payment->reference,
            'residence_name' => $this->payment->booking?->residence?->name,
            'message' => 'Paiement de ' . number_format((float) $this->payment->total_amount, 0, ',', ' ') . ' FCFA confirmé',
        ];
    }
}
