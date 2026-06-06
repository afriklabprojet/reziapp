<?php

declare(strict_types=1);

namespace App\Services\JekoWebhook;

use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SubscriptionPaymentHandler extends AbstractJekoPaymentHandler
{
    public function supports(string $reference): bool
    {
        return str_starts_with($reference, 'REZI-SUB-')
            || str_starts_with($reference, 'SUB-');
    }

    protected function findByReference(string $reference): ?SubscriptionPayment
    {
        return SubscriptionPayment::where('reference', $reference)->first();
    }

    protected function wasAlreadyProcessed(mixed $subject): bool
    {
        return $subject->status === 'completed';
    }

    protected function processSuccess(mixed $subject, TransactionCompletedData $data): bool
    {
        return DB::transaction(function () use ($subject, $data): bool {
            $lockedPayment = SubscriptionPayment::query()
                ->whereKey($subject->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedPayment || $lockedPayment->status === 'completed') {
                return false;
            }

            $paidAmountCents = $this->extractPaidAmountCents($data);
            $expectedAmountCents = (int) round((float) $lockedPayment->amount * 100);

            if ($paidAmountCents === null || $paidAmountCents !== $expectedAmountCents) {
                $lockedPayment->markAsFailed('Montant de paiement invalide', [
                    'received_amount_cents' => $paidAmountCents,
                    'expected_amount_cents' => $expectedAmountCents,
                    'transaction_id' => $data->transactionId,
                    'payment_method' => $data->paymentMethod,
                    'executed_at' => $data->executedAt,
                ]);

                Log::warning('Jeko webhook: Subscription payment amount mismatch', [
                    'subscription_payment_id' => $lockedPayment->id,
                    'reference' => $data->reference,
                    'received_amount_cents' => $paidAmountCents,
                    'expected_amount_cents' => $expectedAmountCents,
                ]);

                return false;
            }

            $lockedPayment->markAsPaid($data->transactionId, [
                'payment_method' => $data->paymentMethod,
                'executed_at' => $data->executedAt,
                'verified_amount_cents' => $paidAmountCents,
            ]);

            return true;
        });
    }

    protected function processFailure(mixed $subject, TransactionCompletedData $data): void
    {
        $subject->markAsFailed('Paiement échoué via Jeko');
    }

    protected function missingLogMessage(): string
    {
        return 'Jeko webhook: No subscription payment found for reference';
    }

    protected function missingLogContext(TransactionCompletedData $data): array
    {
        return [
            'reference' => $data->reference,
        ];
    }

    protected function alreadyProcessedLogMessage(): string
    {
        return 'Jeko webhook: Subscription payment already processed';
    }

    protected function alreadyProcessedLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'subscription_payment_id' => $subject->id,
        ];
    }

    protected function successLogMessage(): string
    {
        return 'Jeko webhook: Subscription payment confirmed';
    }

    protected function successLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'subscription_payment_id' => $subject->id,
            'subscription_id' => $subject->subscription_id,
        ];
    }
}

