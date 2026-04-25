<?php

namespace App\Notifications;

use App\Models\LeaseContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractTerminationRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly LeaseContract $contract) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Demande de résiliation de contrat — ' . $this->contract->reference)
            ->greeting('Bonjour,')
            ->line('Le locataire **' . $this->contract->tenant?->full_name . '** a demandé la résiliation de son contrat.')
            ->line('**Contrat :** ' . $this->contract->reference)
            ->line('**Résidence :** ' . $this->contract->residence?->title)
            ->line('**Motif :** ' . $this->contract->termination_request_reason)
            ->action('Voir le contrat', route('filament.admin.resources.lease-contracts.index'))
            ->line('Veuillez traiter cette demande dans les meilleurs délais.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'termination_requested',
            'contract_id' => $this->contract->id,
            'reference'   => $this->contract->reference,
            'tenant'      => $this->contract->tenant?->full_name,
            'reason'      => $this->contract->termination_request_reason,
        ];
    }
}
