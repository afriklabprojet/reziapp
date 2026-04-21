<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Payout;
use App\Services\JekoPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Traitement automatique d'un payout via Jeko Transfers API.
 *
 * Flux :
 *   1. Créer/récupérer le contact bénéficiaire
 *   2. Vérifier le solde du magasin
 *   3. Exécuter le transfert
 *   4. Mettre à jour le statut du payout
 */
class ProcessPayoutJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1 minute entre les tentatives

    public function __construct(
        public Payout $payout,
    ) {
    }

    public function handle(JekoPaymentService $jekoService): void
    {
        $payout = $this->payout->fresh();

        // Ne traiter que les payouts en attente
        if (! $payout || ! $payout->isPending()) {
            Log::info('ProcessPayoutJob: Payout not pending, skipping', [
                'payout_id' => $payout?->id,
                'status' => $payout?->status,
            ]);

            return;
        }

        $owner = $payout->user;

        if (! $owner) {
            $payout->markAsFailed('Propriétaire introuvable.');

            return;
        }

        // Vérifier que le service est activé
        if (! $jekoService->isEnabled()) {
            Log::warning('ProcessPayoutJob: Jeko service not enabled, keeping payout pending', [
                'payout_id' => $payout->id,
            ]);

            // Ne pas marquer comme échoué — admin traitera manuellement
            return;
        }

        // Exécuter le transfert via Jeko
        $result = $jekoService->executeTransfer($payout, $owner);

        if (! $result['success']) {
            Log::warning('ProcessPayoutJob: Transfer failed', [
                'payout_id' => $payout->id,
                'error' => $result['error'] ?? 'Unknown',
            ]);
            // Le payout est déjà marqué failed dans executeTransfer()
        } else {
            Log::info('ProcessPayoutJob: Transfer initiated', [
                'payout_id' => $payout->id,
                'transfer_id' => $result['transfer_id'] ?? null,
                'status' => $result['status'] ?? 'pending',
            ]);
        }
    }

    /**
     * Si le job échoue définitivement.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPayoutJob: Final failure', [
            'payout_id' => $this->payout->id,
            'error' => $exception->getMessage(),
        ]);

        $this->payout->markAsFailed('Erreur système lors du transfert automatique.');
    }
}
