<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LeaseContractSigned;
use App\Notifications\LeaseContractReadyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Envoie les notifications de confirmation quand un contrat est entièrement signé.
 */
class SendLeaseContractSignedNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(LeaseContractSigned $event): void
    {
        $contract = $event->contract->load(['owner', 'tenant', 'residence']);

        // Notifier le propriétaire — contrat pleinement signé
        $contract->owner->notify(
            new LeaseContractReadyNotification($contract, 'owner'),
        );

        // Notifier également le locataire — confirmation finale
        $contract->tenant->notify(
            new LeaseContractReadyNotification($contract, 'tenant'),
        );
    }
}
