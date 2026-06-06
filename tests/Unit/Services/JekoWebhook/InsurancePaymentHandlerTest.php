<?php

namespace Tests\Unit\Services\JekoWebhook;

use App\Models\Booking;
use App\Models\BookingInsurance;
use App\Models\InsurancePlan;
use App\Services\JekoWebhook\InsurancePaymentHandler;
use App\Services\JekoWebhook\TransactionCompletedData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InsurancePaymentHandlerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_cancels_insurance_when_webhook_amount_is_lower_than_expected(): void
    {
        $insurance = $this->makeInsurance();

        (new InsurancePaymentHandler())->handle(new TransactionCompletedData(
            reference: $insurance->payment_reference,
            transactionId: 'tx-underpaid-insurance',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 100],
            ],
        ));

        $insurance->refresh();

        $this->assertSame('cancelled', $insurance->status);
        $this->assertSame(100, $insurance->metadata['received_amount_cents']);
        $this->assertSame(125000, $insurance->metadata['expected_amount_cents']);
    }

    #[Test]
    public function it_activates_insurance_when_webhook_amount_matches_expected_amount(): void
    {
        $insurance = $this->makeInsurance();

        (new InsurancePaymentHandler())->handle(new TransactionCompletedData(
            reference: $insurance->payment_reference,
            transactionId: 'tx-insurance-paid',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 125000],
            ],
        ));

        $insurance->refresh();

        $this->assertSame('active', $insurance->status);
        $this->assertSame('tx-insurance-paid', $insurance->metadata['jeko_transaction_id']);
        $this->assertSame(125000, $insurance->metadata['verified_amount_cents']);
    }

    #[Test]
    public function it_ignores_duplicate_success_webhooks_for_insurance_payments(): void
    {
        $insurance = $this->makeInsurance();

        $handler = new InsurancePaymentHandler();
        $payload = new TransactionCompletedData(
            reference: $insurance->payment_reference,
            transactionId: 'tx-insurance-once',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 125000],
            ],
        );

        $handler->handle($payload);
        $handler->handle($payload);

        $insurance->refresh();

        $this->assertSame('active', $insurance->status);
        $this->assertSame('tx-insurance-once', $insurance->metadata['jeko_transaction_id']);
    }

    private function makeInsurance(): BookingInsurance
    {
        $booking = Booking::factory()->create();

        $plan = InsurancePlan::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'description' => 'Plan standard',
            'rate' => 5,
            'min_amount' => 0,
            'max_coverage' => 500000,
            'deductible' => 0,
            'coverage_types' => ['damage'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return BookingInsurance::create([
            'booking_id' => $booking->id,
            'insurance_plan_id' => $plan->id,
            'user_id' => $booking->user_id,
            'premium_amount' => 1250,
            'coverage_amount' => 50000,
            'status' => 'cancelled',
            'coverage_start' => now(),
            'coverage_end' => now()->addDays(7),
            'covered_items' => ['damage'],
            'payment_reference' => 'REZI-INS-TEST-1234',
            'metadata' => [],
        ]);
    }
}
