<?php

namespace Tests\Unit\Services\JekoWebhook;

use App\Models\SponsoredListing;
use App\Services\JekoWebhook\SponsoredListingPaymentHandler;
use App\Services\JekoWebhook\TransactionCompletedData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SponsoredListingPaymentHandlerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_rejects_sponsored_listing_payment_when_amount_does_not_match_budget(): void
    {
        $listing = $this->makeSponsoredListing();

        (new SponsoredListingPaymentHandler())->handle(new TransactionCompletedData(
            reference: $listing->jeko_reference,
            transactionId: 'tx-underpaid-sponsored',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 100],
            ],
        ));

        $listing->refresh();

        $this->assertSame('error', $listing->payment_status);
        $this->assertFalse($listing->is_paid);
        $this->assertSame('pending', $listing->status);
    }

    #[Test]
    public function it_confirms_sponsored_listing_payment_when_amount_matches_budget(): void
    {
        $listing = $this->makeSponsoredListing();

        (new SponsoredListingPaymentHandler())->handle(new TransactionCompletedData(
            reference: $listing->jeko_reference,
            transactionId: 'tx-sponsored-paid',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 5000000],
            ],
        ));

        $listing->refresh();

        $this->assertSame('success', $listing->payment_status);
        $this->assertTrue($listing->is_paid);
        $this->assertSame('active', $listing->status);
        $this->assertSame('tx-sponsored-paid', $listing->payment_reference);
    }

    #[Test]
    public function it_ignores_duplicate_success_webhooks_for_sponsored_listings(): void
    {
        $listing = $this->makeSponsoredListing();

        $handler = new SponsoredListingPaymentHandler();
        $firstPayload = new TransactionCompletedData(
            reference: $listing->jeko_reference,
            transactionId: 'tx-sponsored-once',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 5000000],
            ],
        );

        $secondPayload = new TransactionCompletedData(
            reference: $listing->jeko_reference,
            transactionId: 'tx-sponsored-twice',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 5000000],
            ],
        );

        $handler->handle($firstPayload);
        $handler->handle($secondPayload);

        $listing->refresh();

        $this->assertSame('success', $listing->payment_status);
        $this->assertSame('tx-sponsored-once', $listing->payment_reference);
    }

    private function makeSponsoredListing(): SponsoredListing
    {
        $residence = \App\Models\Residence::factory()->create();

        return SponsoredListing::create([
            'residence_id' => $residence->id,
            'user_id' => $residence->owner_id,
            'type' => 'highlighted',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(8),
            'duration_days' => 7,
            'position' => 1,
            'daily_budget' => 7142.86,
            'total_budget' => 50000,
            'amount_spent' => 0,
            'billing_type' => 'flat_rate',
            'cost_per_unit' => 0,
            'impressions' => 0,
            'clicks' => 0,
            'contacts_generated' => 0,
            'status' => 'pending',
            'is_paid' => false,
            'payment_status' => 'pending',
            'jeko_reference' => 'REZI-SP-TEST-1234',
        ]);
    }
}
