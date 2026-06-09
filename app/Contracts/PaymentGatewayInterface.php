<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;

interface PaymentGatewayInterface
{
    public function isEnabled(): bool;

    public function createBookingPaymentRequest(Booking $booking, string $paymentMethod, Payment $payment): array;

    public function getPaymentStatus(string $paymentRequestId): array;

    public function verifyWebhookSignature(string $rawBody, string $signature): bool;

    public function executeTransfer(Payout $payout, User $owner): array;

    public function getStoreBalance(): array;
}
