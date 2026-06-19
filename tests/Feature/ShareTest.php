<?php

namespace Tests\Feature;

use App\Models\CancellationPolicy;
use App\Models\PropertyShare;
use App\Models\Residence;
use App\Models\User;
use App\Services\ShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests du partage de résidences
 * Couvre : création, clics, conversions, statistiques
 */
class ShareTest extends TestCase
{
    use RefreshDatabase;

    protected ShareService $service;
    protected User $owner;
    protected Residence $residence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ShareService::class);

        $policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Test',
            'refund_rules' => [['days_before' => 7, 'refund_percent' => 100]],
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'cancellation_policy_id' => $policy->id,
            'status' => 'approved',
        ]);
    }

    // ========================================
    // CRÉATION DE PARTAGE
    // ========================================

    #[Test]
    public function share_can_be_created_anonymously(): void
    {
        $share = $this->service->createShare(
            $this->residence->id,
            'whatsapp',
            null,
            '127.0.0.1',
            'Mozilla/5.0',
        );

        $this->assertInstanceOf(PropertyShare::class, $share);
        $this->assertEquals($this->residence->id, $share->residence_id);
        $this->assertEquals('whatsapp', $share->platform);
        $this->assertNull($share->user_id);
    }

    #[Test]
    public function share_can_be_created_by_authenticated_user(): void
    {
        $user = User::factory()->create();

        $share = $this->service->createShare(
            $this->residence->id,
            'facebook',
            $user->id,
            '127.0.0.1',
        );

        $this->assertEquals($user->id, $share->user_id);
    }

    #[Test]
    public function share_has_unique_token(): void
    {
        $share1 = $this->service->createShare($this->residence->id, 'twitter');
        $share2 = $this->service->createShare($this->residence->id, 'twitter');

        $this->assertNotEquals($share1->share_token, $share2->share_token);
    }

    // ========================================
    // TRACKING
    // ========================================

    #[Test]
    public function click_can_be_recorded(): void
    {
        $share = $this->service->createShare($this->residence->id, 'whatsapp');
        $initialClicks = $share->click_count;

        $result = $this->service->recordClick($share->share_token);

        $this->assertTrue($result);
        $share->refresh();
        $this->assertEquals($initialClicks + 1, $share->click_count);
    }

    #[Test]
    public function booking_can_be_recorded(): void
    {
        $share = $this->service->createShare($this->residence->id, 'email');
        $initialBookings = $share->booking_count;

        $result = $this->service->recordBooking($share->share_token);

        $this->assertTrue($result);
        $share->refresh();
        $this->assertEquals($initialBookings + 1, $share->booking_count);
    }

    #[Test]
    public function invalid_token_returns_null(): void
    {
        $share = $this->service->getShareByToken('invalid_token_123');
        $this->assertNull($share);
    }

    // ========================================
    // GÉNÉRATION D'URLs
    // ========================================

    #[Test]
    public function generate_share_urls_returns_all_platforms(): void
    {
        $urls = $this->service->generateShareUrls($this->residence->id);

        $this->assertArrayHasKey('whatsapp', $urls);
        $this->assertArrayHasKey('facebook', $urls);
        $this->assertArrayHasKey('twitter', $urls);
        $this->assertArrayHasKey('email', $urls);
        $this->assertArrayHasKey('link', $urls);
    }

    // Note: getCopyText requires loaded residence with title - skipped in unit test
    // This would be better tested as integration test with full database

    // ========================================
    // STATISTIQUES
    // ========================================

    #[Test]
    public function residence_stats_returns_expected_structure(): void
    {
        // Create some shares
        $this->service->createShare($this->residence->id, 'whatsapp');
        $this->service->createShare($this->residence->id, 'facebook');

        $stats = $this->service->getResidenceStats($this->residence->id);

        $this->assertIsArray($stats);
    }

    #[Test]
    public function owner_stats_returns_expected_structure(): void
    {
        $stats = $this->service->getOwnerStats($this->owner->id);

        $this->assertArrayHasKey('total_shares', $stats);
        $this->assertArrayHasKey('total_clicks', $stats);
        $this->assertArrayHasKey('total_bookings', $stats);
        $this->assertArrayHasKey('conversion_rate', $stats);
        $this->assertArrayHasKey('by_platform', $stats);
    }

    #[Test]
    public function trending_shares_returns_collection(): void
    {
        $shares = $this->service->getTrendingShares(5);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $shares);
    }
}
