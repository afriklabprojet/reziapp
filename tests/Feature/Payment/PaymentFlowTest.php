<?php

namespace Tests\Feature\Payment;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\PaymentProvider;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests du flux de paiement HTTP (contrôleur)
 * Complémente JekoPaymentTest qui teste le service directement
 */
#[Group('payment')]
#[Group('payment-flow')]
class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

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

        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $this->guest = User::factory()->create(['role' => 'user']);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'cancellation_policy_id' => $this->policy->id,
        ]);

        $this->booking = Booking::factory()->pending()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'total_amount' => 75000,
            'payment_status' => 'pending',
        ]);

        // Configurer Jeko en mode sandbox
        config([
            'services.jeko.sandbox' => true,
            'services.jeko.sandbox_url' => 'https://sandbox-api.jeko.ci/v1',
            'services.jeko.sandbox_key' => 'test_key',
            'services.jeko.sandbox_secret' => 'test_secret',
            'services.jeko.merchant_id' => 'test_merchant',
        ]);

        PaymentProvider::firstOrCreate(
            ['code' => 'jeko'],
            [
                'name' => 'Jeko Pay',
                'is_active' => true,
                'is_sandbox' => true,
            ],
        );
    }

    // ========================================
    // PAGE DE CHECKOUT
    // ========================================

    #[Test]
    public function guest_can_access_checkout_page(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('payments.checkout', $this->booking));

        $response->assertStatus(200);
        $response->assertViewIs('payments.checkout');
        $response->assertViewHas('booking');
        $response->assertViewHas('providers');
    }

    #[Test]
    public function other_user_cannot_access_checkout(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->get(route('payments.checkout', $this->booking));

        $response->assertStatus(403);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_checkout(): void
    {
        $response = $this->get(route('payments.checkout', $this->booking));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // INITIATION DE PAIEMENT
    // ========================================

    #[Test]
    public function initiate_payment_requires_phone_number(): void
    {
        $response = $this->actingAs($this->guest)
            ->postJson(route('payments.initiate', $this->booking), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_number']);
    }

    #[Test]
    public function initiate_payment_validates_phone_format(): void
    {
        $response = $this->actingAs($this->guest)
            ->postJson(route('payments.initiate', $this->booking), [
                'phone_number' => 'not-a-phone',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_number']);
    }

    #[Test]
    public function other_user_cannot_initiate_payment(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->postJson(route('payments.initiate', $this->booking), [
                'phone_number' => '0707070707',
                'operator' => 'orange_money',
            ]);

        $response->assertStatus(403);
    }

    // ========================================
    // HISTORIQUE DE PAIEMENTS
    // ========================================

    #[Test]
    public function guest_can_view_payment_history(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('payments.history'));

        $response->assertStatus(200);
    }

    #[Test]
    public function unauthenticated_user_cannot_view_payment_history(): void
    {
        $response = $this->get(route('payments.history'));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // WEBHOOK (SANS AUTH)
    // ========================================

    #[Test]
    public function webhook_endpoint_exists_and_accepts_post(): void
    {
        $response = $this->postJson(route('payments.webhook'), [
            'event' => 'payment.completed',
            'data' => [
                'reference' => 'FAKE-REF-123',
                'status' => 'success',
            ],
        ]);

        // Le webhook doit au minimum répondre (pas un 404 ou 405)
        $this->assertNotEquals(404, $response->status());
        $this->assertNotEquals(405, $response->status());
    }

    #[Test]
    public function webhook_does_not_require_csrf(): void
    {
        // POST sans token CSRF — ne doit pas retourner 419
        $response = $this->post('/payments/webhook', [
            'event' => 'payment.completed',
            'data' => ['reference' => 'TEST-123'],
        ]);

        $this->assertNotEquals(419, $response->status());
    }
}
