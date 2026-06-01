<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\RentReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée au locataire lors de la génération d'un reçu de location.
 */
class RentReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected RentReceipt $receipt,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $residence  = $this->receipt->residence;
        $period     = $this->receipt->period_label;
        $amount     = number_format($this->receipt->total_amount, 0, ',', ' ');
        $currency   = $this->receipt->currency;
        $downloadUrl = route('owner.rent-receipts.download', $this->receipt);

        return (new MailMessage())
            ->subject("🧾 Reçu de location — {$period}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre reçu de location pour la période **{$period}** est disponible.")
            ->line('**Récapitulatif :')
            ->line("Résidence : {$residence->title}")
            ->line("Période : {$period}")
            ->line('Montant de location : '.number_format($this->receipt->rent_amount, 0, ',', ' ')." {$currency}")
            ->when($this->receipt->charges_amount > 0, function (MailMessage $mail) use ($currency) {
                return $mail->line('Charges : '.number_format($this->receipt->charges_amount, 0, ',', ' ')." {$currency}");
            })
            ->line("**Total payé : {$amount} {$currency}**")
            ->line('Mode de paiement : '.($this->receipt->payment_method ?? 'Non renseigné'))
            ->action('Télécharger le reçu PDF', $downloadUrl)
            ->line('Conservez ce document pour vos démarches administratives.')
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'rent_receipt',
            'receipt_id' => $this->receipt->id,
            'reference'  => $this->receipt->reference,
            'period'     => $this->receipt->period_label,
            'amount'     => $this->receipt->total_amount,
        ];
    }
}
