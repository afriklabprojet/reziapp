<?php

namespace Tests\Unit\Services\JekoWebhook;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\JekoWebhook\BookingPaymentHandler;
use App\Services\JekoWebhook\TransactionCompletedData;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingPaymentHandlerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_booking_payment_as_failed_when_split_payment_amount_does_not_match_expected_deposit(): void
    {
        $payment = $this->makeBookingPayment();

        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldNotReceive('onPaymentSuccess');

        (new BookingPaymentHandler($paymentService))->handle(new TransactionCompletedData(
            reference: $payment->reference,
            transactionId: 'tx-underpaid-booking',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 100],
            ],
        ));

        $payment->refresh();

        $this->assertSame(Payment::STATUS_FAILED, $payment->status);
        $this->assertSame(100, $payment->provider_response['received_amount_cents']);
        $this->assertSame(2500000, $payment->provider_response['expected_amount_cents']);
    }

    #[Test]
    public function it_completes_booking_payment_when_split_payment_amount_matches_expected_deposit(): void
    {
        $payment = $this->makeBookingPayment();

        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('onPaymentSuccess')
            ->once()
            ->with(Mockery::on(fn (Payment $handledPayment): bool => $handledPayment->is($payment)));

        (new BookingPaymentHandler($paymentService))->handle(new TransactionCompletedData(
            reference: $payment->reference,
            transactionId: 'tx-booking-paid',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 2500000],
            ],
        ));

        $payment->refresh();

        $this->assertSame(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertSame('tx-booking-paid', $payment->provider_response['jeko_transaction_id']);
        $this->assertSame(2500000, $payment->provider_response['verified_amount_cents']);
    }

    #[Test]
    public function it_completes_booking_payment_when_full_payment_matches_booking_total(): void
    {
        $payment = $this->makeBookingPayment([
            'payment_split' => false,
            'deposit_amount' => 0,
            'balance_amount' => 0,
        ], [
            'reference' => 'REZI-BK-TEST-FULL',
        ]);

        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('onPaymentSuccess')
            ->once()
            ->with(Mockery::on(fn (Payment $handledPayment): bool => $handledPayment->is($payment)));

        (new BookingPaymentHandler($paymentService))->handle(new TransactionCompletedData(
            reference: $payment->reference,
            transactionId: 'tx-booking-full',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 10000000],
            ],
        ));

        $payment->refresh();

        $this->assertSame(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertSame('tx-booking-full', $payment->provider_response['jeko_transaction_id']);
        $this->assertSame(10000000, $payment->provider_response['verified_amount_cents']);
    }

    #[Test]
    public function it_ignores_duplicate_success_webhooks_for_booking_payments(): void
    {
        $payment = $this->makeBookingPayment();

        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('onPaymentSuccess')->once();

        $handler = new BookingPaymentHandler($paymentService);
        $payload = new TransactionCompletedData(
            reference: $payment->reference,
            transactionId: 'tx-booking-once',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 2500000],
            ],
        );

        $handler->handle($payload);
        $handler->handle($payload);

        $payment->refresh();

        $this->assertSame(Payment::STATUS_COMPLETED, $payment->status);
        $this->assertSame('tx-booking-once', $payment->provider_response['jeko_transaction_id']);
    }

    private function makeBookingPayment(array $bookingOverrides = [], array $paymentOverrides = []): Payment
    {
        $booking = Booking::factory()->pending()->create(array_merge([
            'payment_split' => true,
            'deposit_amount' => 25000,
            'balance_amount' => 75000,
            'total_amount' => 100000,
        ], $bookingOverrides));

        return Payment::factory()
            ->for($booking)
            ->for($booking->user, 'user')
            ->create(array_merge([
                'reference' => 'REZI-BK-TEST-1234',
                'type' => Payment::TYPE_BOOKING,
                'status' => Payment::STATUS_PENDING,
                'amount' => 100000,
                'total_amount' => 102000,
                'provider_response' => [],
            ], $paymentOverrides));
    }
}
