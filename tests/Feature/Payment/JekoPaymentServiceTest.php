<?php

namespace Tests\Feature\Payment;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\SponsoredListing;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\JekoPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests du JekoPaymentService — Redirect Flow
 *
 * Couvre : createBookingPaymentRequest, verifyWebhookSignature,
 *          isEnabled, montants XOF (mapping 1:1), gestion erreurs API.
 */
class JekoPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private const REDIRECT_URL = 'https://pay.jeko.africa/pr/jeko-uuid-abc123';
    private const WEBHOOK_URI = '/api/webhooks/jeko';
    private const CONTENT_TYPE_JSON = 'application/json';

    protected User $guest;
    protected User $owner;
    protected Residence $residence;
    protected Booking $booking;
    protected CancellationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Annulation gratuite',
            'refund_rules' => [
                ['days_before' => 7, 'refund_percent' => 100],
                ['days_before' => 1, 'refund_percent' => 50],
            ],
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->guest = User::factory()->create(['role' => 'user']);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'cancellation_policy_id' => $this->policy->id,
            'status' => 'approved',
        ]);
        $this->booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'total_amount' => 75000,
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
        ]);
    }

    protected function makeService(array $config = []): JekoPaymentService
    {
        config(array_merge([
            'services.jeko.enabled'           => true,
            'services.jeko.base_url'          => 'https://api.jeko.africa',
            'services.jeko.api_key'           => 'test_key',
            'services.jeko.api_key_id'        => 'test_key_id',
            'services.jeko.store_id'          => 'store_123',
            'services.jeko.currency'          => 'XOF',
            'services.jeko.webhook_secret'    => 'secret',
            'services.jeko.callback_base_url' => 'https://reziapp.ci',
        ], $config));

        return new JekoPaymentService();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // isEnabled()
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function is_enabled_returns_true_when_fully_configured(): void
    {
        $service = $this->makeService();
        $this->assertTrue($service->isEnabled());
    }

    #[Test]
    public function is_enabled_returns_false_when_disabled(): void
    {
        $service = $this->makeService(['services.jeko.enabled' => false]);
        $this->assertFalse($service->isEnabled());
    }

    #[Test]
    public function is_enabled_returns_false_when_api_key_missing(): void
    {
        $service = $this->makeService(['services.jeko.api_key' => '']);
        $this->assertFalse($service->isEnabled());
    }

    #[Test]
    public function is_enabled_returns_false_when_store_id_missing(): void
    {
        $service = $this->makeService(['services.jeko.store_id' => '']);
        $this->assertFalse($service->isEnabled());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // createBookingPaymentRequest()
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function create_booking_payment_request_returns_redirect_url(): void
    {
        Http::fake([
            'api.jeko.africa/partner_api/payment_requests' => Http::response([
                'id'          => 'jeko-uuid-abc123',
                'redirectUrl' => self::REDIRECT_URL,
                'status'      => 'pending',
            ], 200),
        ]);

        $service = $this->makeService();
        $result = $service->createBookingPaymentRequest($this->booking, 'wave');

        $this->assertTrue($result['success']);
        $this->assertEquals(self::REDIRECT_URL, $result['redirect_url']);
        $this->assertEquals('jeko-uuid-abc123', $result['payment_id']);
        $this->assertStringStartsWith('REZI-BK-', $result['reference']);
    }

    #[Test]
    public function create_booking_payment_request_sends_correct_payload(): void
    {
        Http::fake([
            'api.jeko.africa/partner_api/payment_requests' => Http::response([
                'id'          => 'jeko-uuid-abc123',
                'redirectUrl' => self::REDIRECT_URL,
                'status'      => 'pending',
            ], 200),
        ]);

        $service = $this->makeService();
        $service->createBookingPaymentRequest($this->booking, 'wave');

        Http::assertSent(function ($request) {
            $body = $request->data();

            // Vérifier le mapping XOF × 100 (amountCents = FCFA × 100)
            $this->assertEquals(7500000, $body['amountCents']);
            $this->assertEquals('XOF', $body['currency']);
            $this->assertEquals('store_123', $body['storeId']);
            $this->assertEquals('redirect', $body['paymentDetails']['type']);
            $this->assertEquals('wave', $body['paymentDetails']['data']['paymentMethod']);
            $this->assertStringContainsString(
                '/bookings/payment/success',
                $body['paymentDetails']['data']['successUrl'],
            );
            $this->assertStringContainsString(
                '/bookings/payment/error',
                $body['paymentDetails']['data']['errorUrl'] ?? '',
            );
            $this->assertStringContainsString(
                'booking='.$this->booking->uuid,
                $body['paymentDetails']['data']['successUrl'] ?? '',
            );

            return true;
        });
    }

    #[Test]
    public function create_booking_payment_request_multiplies_amount_by_100(): void
    {
        // Test critique : 50000 XOF → amountCents = 5000000 (× 100)
        $booking = Booking::factory()->create([
            'user_id'       => $this->guest->id,
            'residence_id'  => $this->residence->id,
            'total_amount'  => 50000,
        ]);

        Http::fake([
            'api.jeko.africa/*' => Http::response(['id' => 'x', 'redirectUrl' => 'https://pay.jeko.africa/pr/x', 'status' => 'pending'], 200),
        ]);

        $service = $this->makeService();
        $service->createBookingPaymentRequest($booking, 'orange');

        Http::assertSent(fn ($req) => $req->data()['amountCents'] === 5000000);
    }

    #[Test]
    public function create_booking_payment_request_stores_reference_on_booking(): void
    {
        Http::fake([
            'api.jeko.africa/*' => Http::response([
                'id'          => 'jeko-uuid',
                'redirectUrl' => 'https://pay.jeko.africa/pr/jeko-uuid',
                'status'      => 'pending',
            ], 200),
        ]);

        $service = $this->makeService();
        $service->createBookingPaymentRequest($this->booking, 'wave');

        $this->booking->refresh();
        $this->assertNotNull($this->booking->payment_reference);
        $this->assertStringStartsWith('REZI-BK-', $this->booking->payment_reference);
        $this->assertEquals('wave', $this->booking->payment_method);
    }

    #[Test]
    public function create_booking_payment_request_returns_error_when_api_returns_4xx(): void
    {
        Http::fake([
            'api.jeko.africa/*' => Http::response(['message' => 'Invalid store'], 422),
        ]);

        $service = $this->makeService();
        $result = $service->createBookingPaymentRequest($this->booking, 'wave');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid store', $result['error']);
    }

    #[Test]
    public function create_booking_payment_request_returns_error_on_connection_failure(): void
    {
        Http::fake([
            'api.jeko.africa/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            },
        ]);

        $service = $this->makeService();
        $result = $service->createBookingPaymentRequest($this->booking, 'wave');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function create_booking_payment_request_returns_error_when_jeko_disabled(): void
    {
        $service = $this->makeService(['services.jeko.enabled' => false]);
        $result = $service->createBookingPaymentRequest($this->booking, 'wave');

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    #[Test]
    public function create_booking_payment_request_rejects_amount_below_minimum(): void
    {
        $booking = Booking::factory()->create([
            'user_id'      => $this->guest->id,
            'residence_id' => $this->residence->id,
            'total_amount' => 50, // < 100 XOF minimum
        ]);

        $service = $this->makeService();
        $result = $service->createBookingPaymentRequest($booking, 'wave');

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    #[Test]
    public function create_booking_payment_request_uses_deposit_amount_when_payment_split(): void
    {
        // Quand payment_split=true, Jeko doit recevoir deposit_amount (50%) et non total_amount (100%)
        $booking = Booking::factory()->create([
            'user_id'        => $this->guest->id,
            'residence_id'   => $this->residence->id,
            'total_amount'   => 60000,
            'payment_split'  => true,
            'deposit_amount' => 30000,
            'balance_amount' => 30000,
        ]);

        Http::fake([
            'api.jeko.africa/*' => Http::response(['id' => 'x', 'redirectUrl' => 'https://pay.jeko.africa/pr/x', 'status' => 'pending'], 200),
        ]);

        $service = $this->makeService();
        $service->createBookingPaymentRequest($booking, 'wave');

        Http::assertSent(fn ($req) => $req->data()['amountCents'] === 3000000);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // verifyWebhookSignature()
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function verify_webhook_signature_returns_true_for_valid_signature(): void
    {
        $service = $this->makeService(['services.jeko.webhook_secret' => 'my_secret']);
        $rawBody = json_encode(['event' => 'transaction.completed']);
        $signature = hash_hmac('sha256', $rawBody, 'my_secret');

        $this->assertTrue($service->verifyWebhookSignature($rawBody, $signature));
    }

    #[Test]
    public function verify_webhook_signature_returns_false_for_invalid_signature(): void
    {
        $service = $this->makeService(['services.jeko.webhook_secret' => 'my_secret']);
        $rawBody = json_encode(['event' => 'transaction.completed']);

        $this->assertFalse($service->verifyWebhookSignature($rawBody, 'wrong_sig'));
    }

    #[Test]
    public function verify_webhook_signature_returns_false_when_secret_not_configured(): void
    {
        $service = $this->makeService(['services.jeko.webhook_secret' => '']);
        $this->assertFalse($service->verifyWebhookSignature('body', 'sig'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Webhook controller — /api/webhooks/jeko
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function webhook_rejects_invalid_signature(): void
    {
        config(['services.jeko.webhook_secret' => 'real_secret']);

        $response = $this->postJson(self::WEBHOOK_URI, ['event' => 'transaction.completed'], [
            'Jeko-Signature' => 'bad_sig',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function webhook_returns_200_for_valid_signature(): void
    {
        config([
            'services.jeko.webhook_secret' => 'real_secret',
            'services.jeko.enabled'        => true,
            'services.jeko.api_key'        => 'key',
            'services.jeko.api_key_id'     => 'kid',
            'services.jeko.store_id'       => 'sid',
        ]);

        $payload = json_encode([
            'event' => 'transaction.completed',
            'data'  => [
                'id'                 => 'txn-001',
                'status'             => 'success',
                'paymentMethod'      => 'wave',
                'executedAt'         => now()->toISOString(),
                'transactionDetails' => [
                    'reference' => 'REZI-BK-'.$this->booking->id.'-ABCD1234',
                ],
            ],
        ]);
        $signature = hash_hmac('sha256', $payload, 'real_secret');

        $response = $this->call('POST', self::WEBHOOK_URI, [], [], [], [
            'CONTENT_TYPE'       => self::CONTENT_TYPE_JSON,
            'HTTP_Jeko-Signature' => $signature,
        ], $payload);

        $response->assertStatus(200);
    }

    #[Test]
    public function webhook_activates_sponsored_listing_when_reference_matches(): void
    {
        config([
            'services.jeko.webhook_secret' => 'real_secret',
            'services.jeko.enabled'        => true,
            'services.jeko.api_key'        => 'key',
            'services.jeko.api_key_id'     => 'kid',
            'services.jeko.store_id'       => 'sid',
        ]);

        $sponsored = SponsoredListing::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->owner->id,
            'type' => 'highlighted',
            'starts_at' => null,
            'ends_at' => null,
            'duration_days' => 7,
            'daily_budget' => null,
            'total_budget' => 100,
            'amount_spent' => 0,
            'billing_type' => 'flat_rate',
            'cost_per_unit' => 0,
            'impressions' => 0,
            'clicks' => 0,
            'contacts_generated' => 0,
            'status' => 'pending',
            'is_paid' => false,
            'jeko_reference' => 'REZI-SP-42-ABCD1234',
            'payment_status' => 'processing',
        ]);

        $payload = json_encode([
            'event' => 'transaction.completed',
            'data'  => [
                'id' => 'txn-sp-001',
                'status' => 'success',
                'paymentMethod' => 'wave',
                'executedAt' => now()->toISOString(),
                'transactionDetails' => [
                    'reference' => $sponsored->jeko_reference,
                ],
                'amount' => [
                    'amount' => 100,
                ],
            ],
        ]);
        $signature = hash_hmac('sha256', $payload, 'real_secret');

        $response = $this->call('POST', self::WEBHOOK_URI, [], [], [], [
            'CONTENT_TYPE' => self::CONTENT_TYPE_JSON,
            'HTTP_Jeko-Signature' => $signature,
        ], $payload);

        $response->assertStatus(200);

        $sponsored->refresh();
        $this->assertTrue($sponsored->is_paid);
        $this->assertSame('active', $sponsored->status);
        $this->assertSame('success', $sponsored->payment_status);
        $this->assertSame('txn-sp-001', $sponsored->payment_reference);
        $this->assertSame('wave', $sponsored->payment_method);
        $this->assertNotNull($sponsored->paid_at);
        $this->assertNotNull($sponsored->starts_at);
        $this->assertNotNull($sponsored->ends_at);
    }

    #[Test]
    public function webhook_is_idempotent_on_duplicate_event(): void
    {
        config(['services.jeko.webhook_secret' => 'secret']);

        $payload = json_encode([
            'event' => 'transaction.completed',
            'data'  => [
                'id'     => 'dup-event-id-999',
                'status' => 'success',
                'transactionDetails' => ['reference' => 'REZI-BK-99-ZZZZZ'],
            ],
        ]);
        $signature = hash_hmac('sha256', $payload, 'secret');

        // First call — acquires lock
        $this->call('POST', self::WEBHOOK_URI, [], [], [], [
            'CONTENT_TYPE'       => self::CONTENT_TYPE_JSON,
            'HTTP_Jeko-Signature' => $signature,
        ], $payload);

        // Second call — duplicate, should still return 200 (not retry)
        $response = $this->call('POST', self::WEBHOOK_URI, [], [], [], [
            'CONTENT_TYPE'       => self::CONTENT_TYPE_JSON,
            'HTTP_Jeko-Signature' => $signature,
        ], $payload);

        $response->assertStatus(200);

        // Only one WebhookEvent created
        $this->assertDatabaseCount('webhook_events', 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Callback pages — /bookings/payment/success|error
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function payment_success_callback_is_accessible(): void
    {
        $response = $this->get('/bookings/payment/success?booking='.$this->booking->uuid);

        // Doit être 200 ou redirect (pas 404 ou 500)
        $this->assertContains($response->status(), [200, 302]);
    }

    #[Test]
    public function payment_error_callback_is_accessible(): void
    {
        $response = $this->get('/bookings/payment/error?booking='.$this->booking->uuid);

        $this->assertContains($response->status(), [200, 302]);
    }
}
