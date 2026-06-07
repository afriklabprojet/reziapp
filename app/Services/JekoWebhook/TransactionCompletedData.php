<?php

declare(strict_types=1);

namespace App\Services\JekoWebhook;

final class TransactionCompletedData
{
    public function __construct(
        public readonly string $reference,
        public readonly ?string $transactionId,
        public readonly ?string $status,
        public readonly ?string $paymentMethod,
        public readonly ?string $executedAt,
        public readonly array $rawData,
    ) {
    }

    public static function fromWebhook(array $data): self
    {
        return new self(
            reference: (string) ($data['transactionDetails']['reference'] ?? ''),
            transactionId: $data['id'] ?? null,
            status: $data['status'] ?? null,
            paymentMethod: $data['paymentMethod'] ?? null,
            executedAt: $data['executedAt'] ?? null,
            rawData: $data,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
