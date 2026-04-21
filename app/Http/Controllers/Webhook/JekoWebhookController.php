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

        // 1. Verify webhook signature
        if (! $this->jekoService->verifyWebhookSignature($rawBody, $signature)) {
            Log::channel('security')->warning('Jeko webhook: Invalid signature', [
                'ip' => $request->ip(),
                'signature' => substr($signature, 0, 20).'...',
            ]);

            return response('Invalid signature', 401);
        }

        // 2. Parse the payload
        $payload = $request->json()->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $eventId = $data['id'] ?? $data['transactionDetails']['reference'] ?? null;

        // 3. Idempotency check — prevent double processing
        if ($eventId && ! WebhookEvent::acquireLock('jeko', (string) $eventId, $event, $payload)) {
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
            if ($eventId) {
                WebhookEvent::markFailed('jeko', (string) $eventId);
            }

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
        $transactionId = $data['id'] ?? null;
        $status = $data['status'] ?? null; // "success" or "error"
        $paymentMethod = $data['paymentMethod'] ?? null;
        $executedAt = $data['executedAt'] ?? null;

        if (! $reference) {
            Log::warning('Jeko webhook: Missing reference in transaction.completed', [
                'transaction_id' => $transactionId,
            ]);

            return;
        }

        // Detect payment type from reference prefix
        if (str_starts_with($reference, 'REZI-SP-')) {
            $this->handleSponsoredListingPayment($reference, $transactionId, $status, $paymentMethod, $executedAt, $data);
        } elseif (str_starts_with($reference, 'REZI-BK-')) {
            $this->handleBookingPayment($reference, $transactionId, $status, $paymentMethod, $executedAt, $data);
        } elseif (str_starts_with($reference, 'REZI-SUB-')) {
            $this->handleSubscriptionPayment($reference, $transactionId, $status, $paymentMethod, $executedAt, $data);
        } elseif (str_starts_with($reference, 'REZI-INS-')) {
            $this->handleInsurancePayment($reference, $transactionId, $status, $paymentMethod, $executedAt, $data);
        } else {
            // Try to find by reference in Payment table
            $this->handleGenericPayment($reference, $transactionId, $status, $paymentMethod, $executedAt, $data);
        }
    }

    /**
     * Handle sponsored listing payment
     */
    protected function handleSponsoredListingPayment(string $reference, ?string $transactionId, ?string $status, ?string $paymentMethod, ?string $executedAt, array $data): void
    {
        $sponsored = SponsoredListing::where('jeko_reference', $reference)->first();

        if (! $sponsored) {
            Log::warning('Jeko webhook: No sponsored listing found for reference', [
                'reference' => $reference,
                'transaction_id' => $transactionId,
            ]);

            return;
        }

        // Prevent double processing
        if ($sponsored->is_paid && $sponsored->payment_status === 'success') {
            Log::info('Jeko webhook: Payment already processed', [
                'sponsored_id' => $sponsored->id,
                'reference' => $reference,
            ]);

            return;
        }

        if ($status === 'success') {
            $sponsored->update([
                'is_paid' => true,
                'status' => 'active',
                'payment_status' => 'success',
                'payment_reference' => $transactionId,
                'payment_method' => $paymentMethod,
                'paid_at' => $executedAt ? \Carbon\Carbon::parse($executedAt) : now(),
            ]);

            Log::info('Jeko webhook: Sponsored listing payment confirmed', [
                'sponsored_id' => $sponsored->id,
                'reference' => $reference,
                'transaction_id' => $transactionId,
                'amount' => $data['amount']['amount'] ?? null,
            ]);
        } else {
            $sponsored->update([
                'payment_status' => 'error',
            ]);

            Log::warning('Jeko webhook: Sponsored payment failed', [
                'sponsored_id' => $sponsored->id,
                'reference' => $reference,
                'status' => $status,
            ]);
        }
    }

    /**
     * Handle booking payment
     */
    protected function handleBookingPayment(string $reference, ?string $transactionId, ?string $status, ?string $paymentMethod, ?string $executedAt, array $data): void
    {
        $payment = Payment::where('reference', $reference)
            ->where('type', Payment::TYPE_BOOKING)
            ->first();

        if (! $payment) {
            Log::warning('Jeko webhook: No booking payment found for reference', [
                'reference' => $reference,
            ]);

            return;
        }

        if ($payment->isCompleted()) {
            Log::info('Jeko webhook: Booking payment already completed', [
                'payment_id' => $payment->id,
            ]);

            return;
        }

        if ($status === 'success') {
            DB::transaction(function () use ($payment, $transactionId, $paymentMethod, $executedAt) {
                $payment->markAsCompleted([
                    'jeko_transaction_id' => $transactionId,
                    'payment_method' => $paymentMethod,
                    'executed_at' => $executedAt,
                ]);

                // onPaymentSuccess confirme la réservation et crédite le propriétaire.
                // Les deux opérations doivent être atomiques : si l'une échoue,
                // le paiement NE doit PAS être marqué complet sans confirmation booking.
                $this->paymentService->onPaymentSuccess($payment);
            });

            Log::info('Jeko webhook: Booking payment confirmed', [
                'payment_id' => $payment->id,
                'booking_id' => $payment->booking_id,
            ]);
        } else {
            $payment->markAsFailed('Paiement échoué via Jeko');
        }
    }

    /**
     * Handle subscription payment
     */
    protected function handleSubscriptionPayment(string $reference, ?string $transactionId, ?string $status, ?string $paymentMethod, ?string $executedAt, array $data): void
    {
        $subscriptionPayment = SubscriptionPayment::where('reference', $reference)->first();

        if (! $subscriptionPayment) {
            Log::warning('Jeko webhook: No subscription payment found for reference', [
                'reference' => $reference,
            ]);

            return;
        }

        if ($subscriptionPayment->status === 'paid') {
            Log::info('Jeko webhook: Subscription payment already processed', [
                'subscription_payment_id' => $subscriptionPayment->id,
            ]);

            return;
        }

        if ($status === 'success') {
            $subscriptionPayment->markAsPaid($transactionId, [
                'payment_method' => $paymentMethod,
                'executed_at' => $executedAt,
                'jeko_data' => $data,
            ]);

            // Activate subscription if first payment
            $subscription = $subscriptionPayment->subscription;
            if ($subscription && $subscription->status === 'pending') {
                $subscription->update([
                    'status' => 'active',
                    'started_at' => now(),
                ]);
            }

            Log::info('Jeko webhook: Subscription payment confirmed', [
                'subscription_payment_id' => $subscriptionPayment->id,
                'subscription_id' => $subscriptionPayment->subscription_id,
            ]);
        } else {
            $subscriptionPayment->markAsFailed('Paiement échoué via Jeko');
        }
    }

    /**
     * Handle insurance payment
     */
    protected function handleInsurancePayment(string $reference, ?string $transactionId, ?string $status, ?string $paymentMethod, ?string $executedAt, array $data): void
    {
        $insurance = BookingInsurance::where('payment_reference', $reference)->first();

        if (! $insurance) {
            Log::warning('Jeko webhook: No insurance found for reference', [
                'reference' => $reference,
            ]);

            return;
        }

        if ($insurance->status === 'active') {
            Log::info('Jeko webhook: Insurance already active', [
                'insurance_id' => $insurance->id,
            ]);

            return;
        }

        if ($status === 'success') {
            $insurance->update([
                'status' => 'active',
                'paid_at' => $executedAt ? \Carbon\Carbon::parse($executedAt) : now(),
                'metadata' => array_merge($insurance->metadata ?? [], [
                    'jeko_transaction_id' => $transactionId,
                    'payment_method' => $paymentMethod,
                ]),
            ]);

            Log::info('Jeko webhook: Insurance payment confirmed', [
                'insurance_id' => $insurance->id,
                'booking_id' => $insurance->booking_id,
            ]);
        } else {
            $insurance->update(['status' => 'cancelled']);
        }
    }

    /**
     * Handle generic payment (fallback)
     */
    protected function handleGenericPayment(string $reference, ?string $transactionId, ?string $status, ?string $paymentMethod, ?string $executedAt, array $data): void
    {
        $payment = Payment::where('reference', $reference)
            ->orWhere('provider_reference', $reference)
            ->first();

        if (! $payment) {
            Log::warning('Jeko webhook: No payment found for reference', [
                'reference' => $reference,
            ]);

            return;
        }

        if ($payment->isCompleted()) {
            return;
        }

        if ($status === 'success') {
            $payment->markAsCompleted([
                'jeko_transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
                'executed_at' => $executedAt,
            ]);

            if ($payment->booking_id) {
                $this->paymentService->onPaymentSuccess($payment);
            }
        } else {
            $payment->markAsFailed('Paiement échoué via Jeko');
        }
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
            $payout->markAsFailed($reason);

            // Rembourser le solde du propriétaire
            $balance = \App\Models\OwnerBalance::where('user_id', $payout->user_id)->first();
            if ($balance) {
                $balance->increment('available_balance', $payout->gross_amount);
                $balance->decrement('total_withdrawn', $payout->net_amount);
            }

            Log::warning('Jeko webhook: Transfer failed → Balance refunded', [
                'payout_id' => $payout->id,
                'transfer_id' => $transferId,
                'reason' => $reason,
                'owner_id' => $payout->user_id,
            ]);
        }
    }
}
