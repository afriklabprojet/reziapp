<?php

declare(strict_types=1);

namespace App\Services\JekoWebhook;

use App\Models\BookingInsurance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class InsurancePaymentHandler extends AbstractJekoPaymentHandler
{
    public function supports(string $reference): bool
    {
        return str_starts_with($reference, 'REZI-INS-');
    }

    protected function findByReference(string $reference): ?BookingInsurance
    {
        return BookingInsurance::where('payment_reference', $reference)->first();
    }

    protected function wasAlreadyProcessed(mixed $subject): bool
    {
        return $subject->status === 'active';
    }

    protected function processSuccess(mixed $subject, TransactionCompletedData $data): bool
    {
        return DB::transaction(function () use ($subject, $data): bool {
            $insurance = BookingInsurance::query()
                ->whereKey($subject->getKey())
                ->lockForUpdate()
                ->first();

            if (! $insurance || $insurance->status === 'active') {
                return false;
            }

            $paidAmountCents = $this->extractPaidAmountCents($data);
            $expectedAmountCents = (int) round((float) $insurance->premium_amount * 100);

            if ($paidAmountCents === null || $paidAmountCents !== $expectedAmountCents) {
                $insurance->update([
                    'status' => 'cancelled',
                    'metadata' => array_merge($insurance->metadata ?? [], [
                        'received_amount_cents' => $paidAmountCents,
                        'expected_amount_cents' => $expectedAmountCents,
                        'transaction_id' => $data->transactionId,
                        'payment_method' => $data->paymentMethod,
                        'executed_at' => $data->executedAt,
                    ]),
                ]);

                Log::warning('Jeko webhook: Insurance payment amount mismatch', [
                    'insurance_id' => $insurance->id,
                    'booking_id' => $insurance->booking_id,
                    'reference' => $data->reference,
                    'received_amount_cents' => $paidAmountCents,
                    'expected_amount_cents' => $expectedAmountCents,
                ]);

                return false;
            }

            $insurance->update([
                'status' => 'active',
                'paid_at' => $data->executedAt ? Carbon::parse($data->executedAt) : now(),
                'metadata' => array_merge($insurance->metadata ?? [], [
                    'jeko_transaction_id' => $data->transactionId,
                    'payment_method' => $data->paymentMethod,
                    'verified_amount_cents' => $paidAmountCents,
                ]),
            ]);

            return true;
        });
    }

    protected function processFailure(mixed $subject, TransactionCompletedData $data): void
    {
        $subject->update(['status' => 'cancelled']);
    }

    protected function missingLogMessage(): string
    {
        return 'Jeko webhook: No insurance found for reference';
    }

    protected function missingLogContext(TransactionCompletedData $data): array
    {
        return [
            'reference' => $data->reference,
        ];
    }

    protected function alreadyProcessedLogMessage(): string
    {
        return 'Jeko webhook: Insurance already active';
    }

    protected function alreadyProcessedLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'insurance_id' => $subject->id,
        ];
    }

    protected function successLogMessage(): string
    {
        return 'Jeko webhook: Insurance payment confirmed';
    }

    protected function successLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [
            'insurance_id' => $subject->id,
            'booking_id' => $subject->booking_id,
        ];
    }
}
