<?php

namespace Tests\Unit;

use App\Models\Badge;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests unitaires du service de badges
 * Couvre : évaluation, attribution, révocation, métriques
 */
class BadgeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BadgeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BadgeService::class);
    }

    // ========================================
    // ÉVALUATION
    // ========================================

    #[Test]
    public function evaluate_all_badges_returns_array(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $badges = $this->service->evaluateAllBadges($user);

        $this->assertIsArray($badges);
    }

    #[Test]
    public function evaluate_verified_for_verified_email_user(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // With only email verified, should not get the badge
        // (requires phone and identity too)
        $result = $this->service->evaluateVerified($user);
        $this->assertFalse($result);
    }

    #[Test]
    public function evaluate_verified_fails_for_completely_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();

        $result = $this->service->evaluateVerified($user);
        $this->assertFalse($result);
    }

    #[Test]
    public function evaluate_trusted_guest_requires_minimum_stays(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // User with 0 completed bookings should not get badge
        $result = $this->service->evaluateTrustedGuest($user);
        $this->assertFalse($result);
    }

    // ========================================
    // ATTRIBUTION & RÉVOCATION
    // ========================================

    #[Test]
    public function award_badge_creates_or_updates(): void
    {
        $user = User::factory()->create();

        $badge = $this->service->awardBadge($user, Badge::TYPE_VERIFIED, [
            'email' => true,
            'phone' => true,
        ]);

        $this->assertInstanceOf(Badge::class, $badge);
        $this->assertEquals(Badge::TYPE_VERIFIED, $badge->badge_type);

        // Award again should update, not duplicate
        $badge2 = $this->service->awardBadge($user, Badge::TYPE_VERIFIED, [
            'email' => true,
            'phone' => true,
            'identity' => true,
        ]);

        $this->assertEquals($badge->id, $badge2->id);
        $this->assertDatabaseCount('user_badges', 1);
    }

    #[Test]
    public function revoke_badge_removes_it(): void
    {
        $user = User::factory()->create();
        $this->service->awardBadge($user, Badge::TYPE_VERIFIED, ['test' => true]);

        $result = $this->service->revokeBadge($user, Badge::TYPE_VERIFIED);
        $this->assertTrue($result);

        $this->assertDatabaseMissing('user_badges', [
            'user_id' => $user->id,
            'badge_type' => Badge::TYPE_VERIFIED,
        ]);
    }

    // ========================================
    // MÉTRIQUES
    // ========================================

    #[Test]
    public function response_metrics_returns_expected_keys(): void
    {
        $user = User::factory()->create();
        $metrics = $this->service->calculateResponseMetrics($user);

        $this->assertArrayHasKey('total_received', $metrics);
        $this->assertArrayHasKey('total_responded', $metrics);
        $this->assertArrayHasKey('response_rate', $metrics);
        $this->assertArrayHasKey('avg_response_minutes', $metrics);
        $this->assertArrayHasKey('avg_response_hours', $metrics);
    }

    #[Test]
    public function badge_summary_returns_expected_keys(): void
    {
        $user = User::factory()->create();
        $summary = $this->service->getBadgeSummary($user);

        $this->assertArrayHasKey('total', $summary);
        $this->assertArrayHasKey('badges', $summary);
        $this->assertArrayHasKey('is_superhost', $summary);
        $this->assertArrayHasKey('is_fast_responder', $summary);
    }

    // ========================================
    // MODÈLE
    // ========================================

    #[Test]
    public function badge_is_active_when_not_expired(): void
    {
        $user = User::factory()->create();
        $badge = Badge::create([
            'user_id' => $user->id,
            'badge_type' => Badge::TYPE_VERIFIED,
            'earned_at' => now(),
            'expires_at' => now()->addDays(30),
            'criteria_met' => ['test' => true],
        ]);

        $this->assertTrue($badge->isActive());
    }

    #[Test]
    public function badge_is_inactive_when_expired(): void
    {
        $user = User::factory()->create();
        $badge = Badge::create([
            'user_id' => $user->id,
            'badge_type' => Badge::TYPE_FAST_RESPONDER,
            'earned_at' => now()->subDays(100),
            'expires_at' => now()->subDays(1),
            'criteria_met' => ['test' => true],
        ]);

        $this->assertFalse($badge->isActive());
    }

    #[Test]
    public function badge_renew_extends_expiration(): void
    {
        $user = User::factory()->create();
        $badge = Badge::create([
            'user_id' => $user->id,
            'badge_type' => Badge::TYPE_SUPERHOST,
            'earned_at' => now()->subDays(300),
            'expires_at' => now()->subDays(1),
            'criteria_met' => ['test' => true],
        ]);

        $badge->renew(365);
        $badge->refresh();

        $this->assertTrue($badge->expires_at->isFuture());
    }

    #[Test]
    public function get_types_returns_all_badge_types(): void
    {
        $types = Badge::getTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey(Badge::TYPE_SUPERHOST, $types);
        $this->assertArrayHasKey(Badge::TYPE_VERIFIED, $types);
        $this->assertArrayHasKey(Badge::TYPE_FAST_RESPONDER, $types);
    }
}
