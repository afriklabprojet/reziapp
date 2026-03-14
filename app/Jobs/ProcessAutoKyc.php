<?php

namespace App\Jobs;

use App\Models\IdentityVerification;
use App\Services\AutoKycService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job asynchrone pour traiter la vérification KYC automatique.
 *
 * Dispatché par VerificationService::processAutomaticVerification()
 * après la soumission des documents + selfie.
 *
 * Si le queue driver est 'sync', le traitement est immédiat.
 * Si 'database' ou 'redis', le traitement est différé.
 */
class ProcessAutoKyc implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre max de tentatives.
     */
    public int $tries = 2;

    /**
     * Timeout en secondes (API calls can be slow).
     */
    public int $timeout = 120;

    /**
     * Backoff en secondes entre les tentatives.
     */
    public int $backoff = 30;

    public function __construct(
        protected IdentityVerification $verification,
    ) {
        $this->onQueue('kyc');
    }

    public function handle(AutoKycService $autoKycService): void
    {
        Log::info('ProcessAutoKyc: Début du traitement', [
            'verification_id' => $this->verification->id,
            'user_id' => $this->verification->user_id,
            'document_type' => $this->verification->document_type,
        ]);

        $result = $autoKycService->process($this->verification);

        Log::info('ProcessAutoKyc: Traitement terminé', [
            'verification_id' => $this->verification->id,
            'decision' => $result['decision'] ?? 'unknown',
            'score' => $result['score'] ?? 0,
        ]);
    }

    /**
     * En cas d'échec total, basculer en revue manuelle.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAutoKyc: Échec définitif, passage en revue manuelle', [
            'verification_id' => $this->verification->id,
            'error' => $exception->getMessage(),
        ]);

        $this->verification->update([
            'status' => 'manual_review',
            'admin_notes' => '🤖 KYC Auto — Job échoué: ' . $exception->getMessage(),
        ]);
    }
}
