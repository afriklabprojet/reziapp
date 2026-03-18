<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests critiques du flux de paiement — webhook, edge cases
 */
#[Group('payment')]
#[Group('critical')]
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected CancellationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Flexible',
            'refund_rules' => [['days_before' => 1, 'refund_percent' => 100]],
            'is_active' => true,
        ]);

        config(['services.jeko.webhook_secret' => 'test_webhook_secret_123']);
    }

    #[Test]
    public function jeko_webhook_rejects_without_signature(): void
    {
        $response = $this->postJson('/api/webhooks/jeko', [
            'event' => 'payment.success',
            'data' => ['id' => 'test123'],
        ]);

        // Should be rejected (no signature header)
        $response->assertStatus(401);
    }

    #[Test]
    public function jeko_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/webhooks/jeko', [
            'event' => 'payment.success',
            'data' => ['id' => 'test123'],
        ], [
            'X-Jeko-Signature' => 'invalid_signature_here',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function jeko_webhook_accepts_valid_signature(): void
    {
        $payload = json_encode([
            'event' => 'payment.success',
            'data' => ['id' => 'test123', 'status' => 'completed'],
        ]);

        $signature = hash_hmac('sha256', $payload, 'test_webhook_secret_123');

        $response = $this->call(
            'POST',
            '/api/webhooks/jeko',
            [],
            [],
            [],
            [
                'HTTP_X_JEKO_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload,
        );

        // May return 200 or 422 depending on if the payment exists,
        // but should NOT return 401 (signature accepted)
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    // ─── ERROR RESPONSES ────────────────────────────────────────

    #[Test]
    public function api_returns_json_for_404(): void
    {
        $response = $this->getJson('/api/v1/nonexistent-route');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 404,
            ]);
    }

    #[Test]
    public function api_returns_json_for_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 401,
            ]);
    }

    #[Test]
    public function api_returns_json_for_validation_error(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '', // Required
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }
}
