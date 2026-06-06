<?php

namespace Tests\Unit\Services\JekoWebhook;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\JekoWebhook\SubscriptionPaymentHandler;
use App\Services\JekoWebhook\TransactionCompletedData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionPaymentHandlerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_subscription_payment_as_failed_when_webhook_amount_is_lower_than_expected(): void
    {
        $payment = $this->makeSubscriptionPayment();

        app(SubscriptionPaymentHandler::class)->handle(new TransactionCompletedData(
            reference: $payment->reference,
            transactionId: 'tx-underpaid',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 100],
            ],
        ));

        $payment->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertSame(100, $payment->provider_response['received_amount_cents']);
        $this->assertSame(990000, $payment->provider_response['expected_amount_cents']);
    }

    #[Test]
    public function it_completes_subscription_payment_when_webhook_amount_matches_expected_amount(): void
    {
        $payment = $this->makeSubscriptionPayment();

        app(SubscriptionPaymentHandler::class)->handle(new TransactionCompletedData(
            reference: $payment->reference,
            transactionId: 'tx-paid',
            status: 'success',
            paymentMethod: 'wave',
            executedAt: now()->toIso8601String(),
            rawData: [
                'amount' => ['amount' => 990000],
            ],
        ));

        $payment->refresh();
        $payment->subscription->refresh();

        $this->assertSame('completed', $payment->status);
        $this->assertSame('tx-paid', $payment->transaction_id);
        $this->assertSame(990000, $payment->provider_response['verified_amount_cents']);
        $this->assertSame('active', $payment->subscription->status);
    }

    private function makeSubscriptionPayment(): SubscriptionPayment
    {
        $user = User::factory()->create(['role' => 'owner']);

        $plan = SubscriptionPlan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Plan Pro',
            'price_monthly' => 9900,
            'price_yearly' => 99000,
            'max_residences' => 5,
            'max_photos_per_residence' => 20,
            'max_sponsored_per_month' => 3,
            'commission_rate' => 3,
            'priority_support' => true,
            'analytics_advanced' => true,
            'auto_replies' => true,
            'calendar_sync' => true,
            'featured_badge' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'past_due',
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addMonth(),
            'amount' => 9900,
            'auto_renew' => true,
            'metadata' => [],
        ]);

        return SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'amount' => 9900,
            'currency' => 'XOF',
            'status' => 'pending',
            'payment_provider' => 'jeko',
            'reference' => 'REZI-SUB-TEST-1234',
            'provider_response' => [],
        ]);
    }
}
