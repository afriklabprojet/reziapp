<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\BookingInsurance;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\SponsoredListing;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\WebhookEvent;
use App\Services\JekoPaymentService;
use App\Services\JekoWebhook\PaymentHandlerRegistry;
use App\Services\JekoWebhook\TransactionCompletedData;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JekoWebhookController extends Controller
{
    public function __construct(
        protected JekoPaymentService $jekoService,
        protected PaymentService $paymentService,
        protected PaymentHandlerRegistry $paymentHandlerRegistry,
    ) {
    }

    /**
     * Handle incoming Jeko webhooks.
     *
     * Jeko sends a single event type: transaction.completed
     * Must respond HTTP 200 within 5 seconds.
     *
     * IDEMPOTENT: Uses WebhookEvent to prevent double processing.
     */
    public function handle(Request $request): Response
    {
        $rawBody = $request->getContent();
        $signature = $request->header('Jeko-Signature', '');
        $earlyResponse = null;

        // 1. Verify webhook signature
        if (! $this->jekoService->verifyWebhookSignature($rawBody, $signature)) {
            Log::channel('security')->warning('Jeko webhook: Invalid signature', [
                'ip' => $request->ip(),
                'signature' => substr($signature, 0, 20).'...',
            ]);

            $earlyResponse = response('Invalid signature', 401);
        }

        // 2. Parse the payload
        $payload = $request->json()->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $eventId = $data['id'] ?? $data['transactionDetails']['reference'] ?? null;

        if (! $earlyResponse && ! $eventId) {
            Log::channel('security')->error('Jeko webhook: Missing event ID', [
                'ip' => $request->ip(),
                'event' => $event,
                'reference' => $data['transactionDetails']['reference'] ?? null,
            ]);

            $earlyResponse = response('Bad Request: Missing event ID', 400);
        }

        if ($earlyResponse instanceof Response) {
            return $earlyResponse;
        }

        // 3. Idempotency check — prevent double processing
        if (! WebhookEvent::acquireLock('jeko', (string) $eventId, $event, $payload)) {
            Log::channel('payments')->info('Jeko webhook: Duplicate event ignored', [
                'event_id' => $eventId,
                'event' => $event,
            ]);

            return response('OK', 200); // Return 200 so Jeko doesn't retry
        }

        Log::channel('payments')->info('Jeko webhook received', [
            'event' => $event,
            'event_id' => $eventId,
            'status' => $data['status'] ?? null,
            'reference' => $data['transactionDetails']['reference'] ?? null,
            'ip' => $request->ip(),
        ]);

        // 4. Handle the event
        try {
            if ($event === 'transaction.completed') {
                $this->handleTransactionCompleted($data);
            } elseif ($event === 'transfer.completed' || $event === 'transfer.failed') {
                $this->handleTransferEvent($data, $event);
            }
        } catch (\Throwable $e) {
            // Mark as failed for potential re-processing
            WebhookEvent::markFailed('jeko', (string) $eventId);

            Log::channel('critical')->error('Jeko webhook: Processing error', [
                'event' => $event,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
        }

        // 4. Return 200 immediately (Jeko requires response within 5s)
        return response('OK', 200);
    }

    /**
     * Handle a completed transaction event.
     */
    protected function handleTransactionCompleted(array $data): void
    {
        $reference = $data['transactionDetails']['reference'] ?? null;

        if (! $reference) {
            Log::warning('Jeko webhook: Missing reference in transaction.completed', [
                'transaction_id' => $data['id'] ?? null,
            ]);

            return;
        }

        $transaction = TransactionCompletedData::fromWebhook($data);

        $this->paymentHandlerRegistry
            ->forReference($reference)
            ->handle($transaction);
    }

    // =========================================================================
    // TRANSFER (PAY-OUT) WEBHOOKS
    // =========================================================================

    /**
     * Handle transfer completion / failure webhooks from Jeko.
     *
     * Jeko envoie un webhook quand un transfert change de statut :
     *   - transfer.completed → le transfert est arrivé au bénéficiaire
     *   - transfer.failed → le transfert a échoué
     */
    protected function handleTransferEvent(array $data, string $event): void
    {
        $transferId = $data['id'] ?? null;
        $status = $data['status'] ?? null;

        if (! $transferId) {
            Log::warning('Jeko webhook: Missing transfer ID', ['event' => $event]);

            return;
        }

        // Trouver le payout correspondant via provider_reference (= jeko transfer ID)
        $payout = Payout::where('provider_reference', $transferId)->first();

        if (! $payout) {
            Log::warning('Jeko webhook: No payout found for transfer', [
                'transfer_id' => $transferId,
                'event' => $event,
            ]);

            return;
        }

        // Éviter le double traitement
        if ($payout->isCompleted()) {
            Log::info('Jeko webhook: Payout already completed', [
                'payout_id' => $payout->id,
                'transfer_id' => $transferId,
            ]);

            return;
        }

        if ($status === 'success' || $event === 'transfer.completed') {
            $payout->markAsCompleted($transferId);

            Log::info('Jeko webhook: Transfer completed → Payout marked as completed', [
                'payout_id' => $payout->id,
                'transfer_id' => $transferId,
                'amount' => $payout->net_amount,
                'owner_id' => $payout->user_id,
            ]);
        } else {
            $reason = $data['failureReason'] ?? $data['message'] ?? 'Transfert échoué';

            DB::transaction(function () use ($payout, $reason) {
                $payout->markAsFailed($reason);

                // Rembourser le solde du propriétaire de façon atomique
                $balance = \App\Models\OwnerBalance::where('user_id', $payout->user_id)
                    ->lockForUpdate()
                    ->first();

                if ($balance) {
                    $balance->increment('available_balance', $payout->gross_amount);
                    $balance->decrement('total_withdrawn', $payout->net_amount);
                }
            });

            Log::warning('Jeko webhook: Transfer failed → Balance refunded', [
                'payout_id' => $payout->id,
                'transfer_id' => $transferId,
                'reason' => $reason,
                'owner_id' => $payout->user_id,
            ]);
        }
    }
}
