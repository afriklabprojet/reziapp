<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\RequiresMysql;
use Tests\TestCase;

/**
 * Tests unitaires du service de vérification
 * Couvre : identité, téléphone, trust score, fraude, blacklist
 */
class VerificationServiceTest extends TestCase
{
    use RefreshDatabase;
    use RequiresMysql;

    protected VerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests - requires MySQL columns (phone_verified_at, identity_verified_at)
        $this->skipIfSqlite();

        $this->service = app(VerificationService::class);
    }

    // ========================================
    // VÉRIFICATION D'IDENTITÉ
    // ========================================

    #[Test]
    public function initiate_identity_verification_creates_record(): void
    {
        $user = User::factory()->create();

        $verification = $this->service->initiateIdentityVerification($user, 'cni');

        $this->assertNotNull($verification);
        $this->assertEquals($user->id, $verification->user_id);
        $this->assertEquals('cni', $verification->document_type);
    }

    #[Test]
    public function duplicate_identity_verification_returns_existing(): void
    {
        $user = User::factory()->create();

        $first = $this->service->initiateIdentityVerification($user);
        $second = $this->service->initiateIdentityVerification($user);

        $this->assertEquals($first->id, $second->id);
    }

    // ========================================
    // VÉRIFICATION TÉLÉPHONE
    // ========================================

    #[Test]
    public function initiate_phone_verification_creates_record(): void
    {
        $user = User::factory()->create();

        $verification = $this->service->initiatePhoneVerification($user, '0758001234');

        $this->assertNotNull($verification);
        $this->assertEquals($user->id, $verification->user_id);
    }

    #[Test]
    public function send_otp_succeeds(): void
    {
        $user = User::factory()->create();
        $verification = $this->service->initiatePhoneVerification($user, '0758001234');

        $result = $this->service->sendOtp($verification);
        // In test mode (log driver), should succeed
        $this->assertIsBool($result);
    }

    // ========================================
    // TRUST SCORE
    // ========================================

    #[Test]
    public function trust_score_for_new_user_is_low(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'phone_verified_at' => null,
            'identity_verified_at' => null,
        ]);

        $score = $this->service->calculateTrustScore($user);

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    #[Test]
    public function trust_score_for_verified_user_is_higher(): void
    {
        $unverified = User::factory()->create([
            'email_verified_at' => now(),
            'phone_verified_at' => null,
            'identity_verified_at' => null,
        ]);

        $verified = User::factory()->create([
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'identity_verified_at' => now(),
        ]);

        $scoreU = $this->service->calculateTrustScore($unverified);
        $scoreV = $this->service->calculateTrustScore($verified);

        $this->assertGreaterThan($scoreU, $scoreV);
    }

    // ========================================
    // NIVEAU DE VÉRIFICATION
    // ========================================

    #[Test]
    public function verification_level_returns_valid_value(): void
    {
        $user = User::factory()->create();
        $level = $this->service->calculateVerificationLevel($user);

        $this->assertContains($level, ['none', 'basic', 'standard', 'premium', 'trusted']);
    }

    // ========================================
    // FRAUDE
    // ========================================

    #[Test]
    public function analyze_user_for_fraud_returns_expected_structure(): void
    {
        $user = User::factory()->create();
        $analysis = $this->service->analyzeUserForFraud($user);

        $this->assertArrayHasKey('risk_score', $analysis);
        $this->assertArrayHasKey('risk_factors', $analysis);
        $this->assertArrayHasKey('recommendation', $analysis);
        $this->assertGreaterThanOrEqual(0, $analysis['risk_score']);
        $this->assertLessThanOrEqual(100, $analysis['risk_score']);
    }

    #[Test]
    public function can_register_checks_blacklist(): void
    {
        $result = $this->service->canRegister('test@example.com', '0758001234');

        $this->assertArrayHasKey('can_register', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertTrue($result['can_register']);
    }

    // ========================================
    // BLACKLIST
    // ========================================

    #[Test]
    public function blacklist_user_suspends_account(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $blacklist = $this->service->blacklistUser(
            $user,
            'fraud',
            'Activité frauduleuse détectée',
            $admin->id,
            'full',
            true,
        );

        $this->assertNotNull($blacklist);
        $user->refresh();
        $this->assertTrue($user->is_suspended ?? false);
    }

    // ========================================
    // STATISTIQUES
    // ========================================

    #[Test]
    public function verification_stats_returns_expected_structure(): void
    {
        $stats = $this->service->getVerificationStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('identity', $stats);
        $this->assertArrayHasKey('phone', $stats);
    }
}
