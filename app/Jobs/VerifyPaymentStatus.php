<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Payment;
use App\Services\JekoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Vérifie le statut d'un paiement en attente auprès de Jeko.
 * Dispatché automatiquement quand un paiement reste en status "processing".
 *
 * Retry: 3 tentatives avec backoff exponentiel (1min, 2min, 4min).
 */
class VerifyPaymentStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 120, 240];
    public int $maxExceptions = 3;

    public function __construct(
        public Payment $payment,
    ) {}

    /**
     * Prevent concurrent status checks on the same payment.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('payment-verify-' . $this->payment->id))
                ->dontRelease()
                ->expireAfter(120),
        ];
    }

    public function handle(JekoService $jekoService): void
    {
        $payment = $this->payment->fresh();

        // Already in terminal state — nothing to do
        if (! $payment || $payment->isCompleted() || $payment->isFailed()) {
            Log::channel('payments')->info('VerifyPaymentStatus: Payment already in terminal state', [
                'payment_id' => $payment?->id,
                'status' => $payment?->status,
            ]);
            return;
        }

        // Check if payment is expired
        if ($payment->expires_at && $payment->expires_at->isPast()) {
            $payment->markAsFailed('Paiement expiré (timeout)');
            Log::channel('payments')->warning('VerifyPaymentStatus: Payment expired', [
                'payment_id' => $payment->id,
                'expires_at' => $payment->expires_at->toIso8601String(),
            ]);
            return;
        }

        // Query Jeko for actual status
        $result = $jekoService->checkPaymentStatus($payment);

        Log::channel('payments')->info('VerifyPaymentStatus: Status checked', [
            'payment_id' => $payment->id,
            'result_status' => $result['status'] ?? 'unknown',
            'attempt' => $this->attempts(),
        ]);

        // If still pending and we have retries left, the job will be retried automatically
        if (in_array($result['status'] ?? '', ['pending', 'processing'])) {
            if ($this->attempts() >= $this->tries) {
                // Max retries reached — mark as failed
                $payment->markAsFailed('Paiement timeout — aucune confirmation reçue');
                Log::channel('payments')->warning('VerifyPaymentStatus: Max retries, marking failed', [
                    'payment_id' => $payment->id,
                ]);
            } else {
                // Will be retried via backoff
                $this->release($this->backoff[$this->attempts() - 1] ?? 240);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('payments')->error('VerifyPaymentStatus: Job failed permanently', [
            'payment_id' => $this->payment->id,
            'error' => $exception->getMessage(),
        ]);

        // Don't mark as failed here — could be a temporary network issue
        // The scheduled command will catch stale payments
    }
}
