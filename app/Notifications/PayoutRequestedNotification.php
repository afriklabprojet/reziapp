<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Payout $payout,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $owner = $this->payout->user;
        $method = match ($this->payout->payout_method) {
            'wave' => 'Wave',
            'orange_money' => 'Orange Money',
            'mtn_money' => 'MTN Money',
            'moov_money' => 'Moov Money',
            'bank_transfer' => 'Virement bancaire',
            default => $this->payout->payout_method,
        };

        return (new MailMessage)
            ->subject('Nouvelle demande de retrait — ' . number_format((float) $this->payout->net_amount, 0, ',', ' ') . ' FCFA')
            ->greeting('Nouvelle demande de retrait')
            ->line('**' . $owner->name . '** a demandé un retrait de **' . number_format((float) $this->payout->net_amount, 0, ',', ' ') . ' FCFA**.')
            ->line('Méthode : ' . $method)
            ->line('Destination : ' . ($this->payout->phone_number ?? $this->payout->bank_name . ' — ' . $this->payout->bank_account))
            ->line('Référence : ' . $this->payout->reference)
            ->action('Gérer dans l\'admin', url('/admin/payouts'))
            ->line('Veuillez traiter cette demande dans les 48h.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Demande de retrait',
            'message' => $this->payout->user->name . ' demande un retrait de ' . number_format((float) $this->payout->net_amount, 0, ',', ' ') . ' FCFA',
            'type' => 'payout_request',
            'payout_id' => $this->payout->id,
            'amount' => $this->payout->net_amount,
            'url' => url('/admin/payouts'),
        ];
    }
}
