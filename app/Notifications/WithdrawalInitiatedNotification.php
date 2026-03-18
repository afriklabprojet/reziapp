<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée au propriétaire quand un retrait est initié depuis son compte.
 * Sécurité : permet à l'owner de détecter un retrait non autorisé.
 */
class WithdrawalInitiatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Payout $payout,
        private string $ipAddress,
        private string $userAgent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $method = match ($this->payout->payout_method) {
            'wave' => 'Wave',
            'orange_money' => 'Orange Money',
            'mtn_money' => 'MTN Money',
            'moov_money' => 'Moov Money',
            'bank_transfer' => 'Virement bancaire',
            default => $this->payout->payout_method,
        };

        $destination = $this->payout->phone_number
            ?? ($this->payout->bank_name . ' — ' . $this->payout->bank_account);

        return (new MailMessage)
            ->subject('⚠️ Retrait initié — ' . number_format((float) $this->payout->net_amount, 0, ',', ' ') . ' FCFA')
            ->greeting('Retrait initié depuis votre compte')
            ->line('Un retrait de **' . number_format((float) $this->payout->net_amount, 0, ',', ' ') . ' FCFA** vient d\'être initié depuis votre espace propriétaire.')
            ->line('**Méthode :** ' . $method)
            ->line('**Destination :** ' . $destination)
            ->line('**Référence :** ' . $this->payout->reference)
            ->line('**Date :** ' . now()->translatedFormat('d F Y à H:i'))
            ->line('**Adresse IP :** ' . $this->ipAddress)
            ->line('')
            ->line('Si vous n\'êtes **PAS** à l\'origine de cette demande, **contactez-nous immédiatement** pour bloquer le versement.')
            ->action('Voir mes revenus', route('owner.earnings.index'))
            ->line('Pour votre sécurité, changez votre mot de passe et votre PIN de retrait si ce retrait est suspect.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Retrait initié',
            'message' => 'Retrait de ' . number_format((float) $this->payout->net_amount, 0, ',', ' ') . ' FCFA initié vers ' . ($this->payout->phone_number ?? $this->payout->bank_name),
            'type' => 'withdrawal_initiated',
            'payout_id' => $this->payout->id,
            'amount' => $this->payout->net_amount,
            'ip_address' => $this->ipAddress,
            'url' => route('owner.earnings.index'),
        ];
    }
}
