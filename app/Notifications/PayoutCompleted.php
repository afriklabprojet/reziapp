<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected float $amount,
        protected string $paymentMethod,
        protected string $reference,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $formattedAmount = number_format($this->amount, 0, ',', ' ');
        $methodLabel = match ($this->paymentMethod) {
            'mobile_money' => 'Mobile Money',
            'bank_transfer' => 'Virement bancaire',
            'wallet' => 'Portefeuille ReziApp',
            default => $this->paymentMethod,
        };

        return (new MailMessage())
            ->subject("💰 Paiement effectué — {$formattedAmount} FCFA")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre paiement de **{$formattedAmount} FCFA** a été effectué avec succès.")
            ->line('**Détails :**')
            ->line("• Montant : {$formattedAmount} FCFA")
            ->line("• Méthode : {$methodLabel}")
            ->line("• Référence : {$this->reference}")
            ->action('Voir mes revenus', route('owner.earnings.index'))
            ->line('Le montant sera crédité sous 24-48h selon votre méthode de paiement.')
            ->salutation('L\'équipe ReziApp');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_completed',
            'amount' => $this->amount,
            'payment_method' => $this->paymentMethod,
            'reference' => $this->reference,
            'message' => 'Paiement de '.number_format($this->amount, 0, ',', ' ').' FCFA effectué',
        ];
    }
}
