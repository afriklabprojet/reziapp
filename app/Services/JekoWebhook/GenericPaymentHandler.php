<?php

declare(strict_types=1);

namespace App\Services\JekoWebhook;

use App\Models\Payment;
use App\Services\PaymentService;

final class GenericPaymentHandler extends AbstractJekoPaymentHandler
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    public function supports(string $reference): bool
    {
        return true;
    }

    protected function findByReference(string $reference): ?Payment
    {
        return Payment::where('reference', $reference)
            ->orWhere('provider_reference', $reference)
            ->first();
    }

    protected function wasAlreadyProcessed(mixed $subject): bool
    {
        return $subject->isCompleted();
    }

    protected function processSuccess(mixed $subject, TransactionCompletedData $data): bool
    {
        $subject->markAsCompleted([
            'jeko_transaction_id' => $data->transactionId,
            'payment_method' => $data->paymentMethod,
            'executed_at' => $data->executedAt,
        ]);

        if ($subject->booking_id) {
            $this->paymentService->onPaymentSuccess($subject);
        }

        return true;
    }

    protected function processFailure(mixed $subject, TransactionCompletedData $data): void
    {
        $subject->markAsFailed('Paiement échoué via Jeko');
    }

    protected function missingLogMessage(): string
    {
        return 'Jeko webhook: No payment found for reference';
    }

    protected function missingLogContext(TransactionCompletedData $data): array
    {
        return [
            'reference' => $data->reference,
        ];
    }

    protected function alreadyProcessedLogMessage(): string
    {
        return 'Jeko webhook: Generic payment already completed';
    }

    protected function alreadyProcessedLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'payment_id' => $subject->id,
        ];
    }

    protected function successLogMessage(): string
    {
        return 'Jeko webhook: Generic payment confirmed';
    }

    protected function successLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'payment_id' => $subject->id,
            'booking_id' => $subject->booking_id,
            'payment_type' => $subject->type,
        ];
    }
}
