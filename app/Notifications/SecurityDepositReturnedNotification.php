<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SecurityDeposit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée au locataire lors de la restitution de sa caution.
 */
class SecurityDepositReturnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SecurityDeposit $deposit,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $residence       = $this->deposit->residence;
        $totalAmount     = number_format($this->deposit->amount, 0, ',', ' ');
        $returnedAmount  = number_format($this->deposit->returned_amount ?? 0, 0, ',', ' ');
        $retainedAmount  = number_format($this->deposit->retained_amount, 0, ',', ' ');
        $currency        = $this->deposit->currency;
        $isFullReturn    = $this->deposit->status === SecurityDeposit::STATUS_RETURNED;

        $subject = $isFullReturn
            ? "✅ Caution restituée — {$residence->title}"
            : "⚠️ Restitution partielle de caution — {$residence->title}";

        $mail = (new MailMessage())
            ->subject($subject)
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre dépôt de garantie concernant la résidence **{$residence->title}** a été traité.");

        if ($isFullReturn) {
            $mail->line("Votre caution de **{$totalAmount} {$currency}** vous est restituée intégralement.")
                 ->line('Mode de restitution : '.($this->deposit->return_payment_method ?? 'Non renseigné'))
                 ->line('Référence : '.($this->deposit->return_reference ?? 'N/A'));
        } else {
            $mail->line("Caution initiale : **{$totalAmount} {$currency}**")
                 ->line("Montant restitué : **{$returnedAmount} {$currency}**")
                 ->line("Retenu pour dommages : **{$retainedAmount} {$currency}**");

            if ($this->deposit->deduction_reasons) {
                $mail->line("Motifs de retenue : {$this->deposit->deduction_reasons}");
            }
        }

        return $mail
            ->line('Pour toute contestation, contactez le support Rezi App.')
            ->action('Voir les détails', route('home'))
            ->salutation('L\'équipe Rezi App');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'security_deposit_returned',
            'deposit_id'     => $this->deposit->id,
            'amount'         => $this->deposit->amount,
            'returned_amount' => $this->deposit->returned_amount,
            'status'         => $this->deposit->status,
        ];
    }
}
