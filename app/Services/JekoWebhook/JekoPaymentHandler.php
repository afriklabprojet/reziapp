<?php

declare(strict_types=1);

namespace App\Services\JekoWebhook;

interface JekoPaymentHandler
{
    public function supports(string $reference): bool;

    public function handle(TransactionCompletedData $data): void;
}
