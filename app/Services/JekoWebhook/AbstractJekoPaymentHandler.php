<?php

declare(strict_types=1);

namespace App\Services\JekoWebhook;

use Illuminate\Support\Facades\Log;

abstract class AbstractJekoPaymentHandler implements JekoPaymentHandler
{
    final public function handle(TransactionCompletedData $data): void
    {
        $subject = $this->findByReference($data->reference);

        if (! $subject) {
            Log::warning($this->missingLogMessage(), $this->missingLogContext($data));

            return;
        }

        if ($this->wasAlreadyProcessed($subject)) {
            Log::info($this->alreadyProcessedLogMessage(), $this->alreadyProcessedLogContext($subject, $data));

            return;
        }

        if ($data->isSuccess()) {
            if ($this->processSuccess($subject, $data)) {
                Log::info($this->successLogMessage(), $this->successLogContext($subject, $data));
            }

            return;
        }

        $this->processFailure($subject, $data);

        if ($this->failureLogMessage() !== null) {
            Log::warning($this->failureLogMessage(), $this->failureLogContext($subject, $data));
        }
    }

    abstract protected function findByReference(string $reference): mixed;

    abstract protected function wasAlreadyProcessed(mixed $subject): bool;

    abstract protected function processSuccess(mixed $subject, TransactionCompletedData $data): bool;

    abstract protected function processFailure(mixed $subject, TransactionCompletedData $data): void;

    abstract protected function missingLogMessage(): string;

    abstract protected function missingLogContext(TransactionCompletedData $data): array;

    abstract protected function alreadyProcessedLogMessage(): string;

    abstract protected function alreadyProcessedLogContext(mixed $subject, TransactionCompletedData $data): array;

    abstract protected function successLogMessage(): string;

    abstract protected function successLogContext(mixed $subject, TransactionCompletedData $data): array;

    protected function failureLogMessage(): ?string
    {
        return null;
    }

    protected function failureLogContext(mixed $subject, TransactionCompletedData $data): array
    {
        return [];
    }

    protected function extractPaidAmountCents(TransactionCompletedData $data): ?int
    {
        $amount = $data->rawData['amount']['amount']
            ?? $data->rawData['transactionDetails']['amount']['amount']
            ?? null;

        if ($amount === null || ! is_numeric($amount)) {
            return null;
        }

        $paidAmountCents = (int) $amount;

        return $paidAmountCents > 0 ? $paidAmountCents : null;
    }
}

