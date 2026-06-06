<?php

namespace Tests\Feature\Payment;

use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\SponsoredListing;
use App\Models\User;
use App\Services\JekoPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JekoSponsoredCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Residence $residence;

    protected function setUp(): void
    {
        parent::setUp();

        CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Annulation gratuite',
            'refund_rules' => [
                ['days_before' => 7, 'refund_percent' => 100],
            ],
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'approved',
            'cancellation_policy_id' => 1,
        ]);

        config([
            'services.jeko.enabled' => true,
            'services.jeko.api_key' => 'test_key',
            'services.jeko.api_key_id' => 'test_key_id',
            'services.jeko.store_id' => 'store_123',
            'services.jeko.callback_base_url' => 'https://reziapp.ci',
        ]);
    }

    public function test_signed_success_callback_is_accessible_without_authentication(): void
    {
        $sponsored = $this->makeSponsoredListing([
            'is_paid' => true,
            'status' => 'active',
            'payment_status' => 'success',
        ]);

        $url = app(JekoPaymentService::class)->signedSponsoredSuccessUrl($sponsored, $sponsored->jeko_reference);

        $response = $this->get($this->toTestUri($url));

        $response->assertRedirect(route('owner.marketing.sponsored.show', $sponsored, false));
    }

    public function test_signed_status_check_is_accessible_without_authentication(): void
    {
        $sponsored = $this->makeSponsoredListing([
            'is_paid' => true,
            'status' => 'active',
            'payment_status' => 'success',
        ]);

        $url = app(JekoPaymentService::class)->signedSponsoredCheckUrl($sponsored);

        $response = $this->getJson($this->toTestUri($url));

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'redirect' => route('owner.marketing.sponsored.show', $sponsored),
            ]);
    }

    public function test_status_check_rejects_invalid_signature_even_if_listing_exists(): void
    {
        $sponsored = $this->makeSponsoredListing();

        $response = $this->getJson(route('payment.jeko.check', [
            'sponsored' => $sponsored,
            'reference' => $sponsored->jeko_reference,
        ], false));

        $response->assertForbidden();
    }

    protected function makeSponsoredListing(array $overrides = []): SponsoredListing
    {
        return SponsoredListing::create(array_merge([
            'residence_id' => $this->residence->id,
            'user_id' => $this->owner->id,
            'type' => 'featured_home',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
            'duration_days' => 7,
            'total_budget' => 25000,
            'amount_spent' => 0,
            'billing_type' => 'flat_rate',
            'cost_per_unit' => 0,
            'impressions' => 0,
            'clicks' => 0,
            'contacts_generated' => 0,
            'status' => 'pending',
            'is_paid' => false,
            'jeko_reference' => 'REZI-SP-'.$this->residence->id.'-TEST1234',
            'payment_status' => 'pending',
        ], $overrides));
    }

    protected function toTestUri(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $query = parse_url($url, PHP_URL_QUERY);

        return $query ? $path.'?'.$query : $path;
    }
}
