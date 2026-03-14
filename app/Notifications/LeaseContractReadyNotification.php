<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LeaseContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée lors de la mise à disposition d'un contrat de bail à signer.
 */
class LeaseContractReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected LeaseContract $contract,
        protected string $recipientRole = 'tenant', // 'owner' | 'tenant'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $residence    = $this->contract->residence;
        $isTenant     = $this->recipientRole === 'tenant';
        $otherParty   = $isTenant ? $this->contract->owner : $this->contract->tenant;
        $signUrl      = route('owner.lease-contracts.show', $this->contract);

        $subject = $isTenant
            ? "📄 Contrat de bail à signer — {$residence->title}"
            : "✅ Contrat de bail signé par le locataire — {$residence->title}";

        $greeting = "Bonjour {$notifiable->name},";

        $intro = $isTenant
            ? "{$otherParty->name} vous a envoyé un contrat de bail pour la résidence **{$residence->title}** pour signature."
            : "**{$otherParty->name}** a signé le contrat de bail pour la résidence **{$residence->title}**.";

        $actionLabel = $isTenant ? 'Consulter et signer le contrat' : 'Voir le contrat';

        return (new MailMessage())
            ->subject($subject)
            ->greeting($greeting)
            ->line($intro)
            ->line('**Détails du contrat :')
            ->line("Référence : {$this->contract->reference}")
            ->line("Période : " . $this->contract->start_date->format('d/m/Y') . ' → ' . ($this->contract->end_date?->format('d/m/Y') ?? 'Indéterminée'))
            ->line("Loyer : " . number_format($this->contract->monthly_rent, 0, ',', ' ') . ' ' . $this->contract->currency)
            ->action($actionLabel, $signUrl)
            ->line('Ce contrat est disponible en ligne sur REZI.')
            ->salutation('L\'équipe REZI');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'              => 'lease_contract_ready',
            'contract_id'       => $this->contract->id,
            'contract_reference' => $this->contract->reference,
            'recipient_role'    => $this->recipientRole,
        ];
    }
}
