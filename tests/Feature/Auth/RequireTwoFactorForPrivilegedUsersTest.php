<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequireTwoFactorForPrivilegedUsersTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function ownerWithout2fa(): User
    {
        return User::factory()->create([
            'role' => 'owner',
            'two_factor_enabled' => false,
            'email_verified_at' => now(),
        ]);
    }

    private function ownerWith2fa(): User
    {
        return User::factory()->create([
            'role' => 'owner',
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function adminWithout2fa(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'two_factor_enabled' => false,
            'email_verified_at' => now(),
        ]);
    }

    private function regularUser(): User
    {
        return User::factory()->create([
            'role' => 'user',
            'two_factor_enabled' => false,
            'email_verified_at' => now(),
        ]);
    }

    // ── Web owner routes ──────────────────────────────────────────────────────

    public function test_owner_without_2fa_is_redirected_to_setup_page(): void
    {
        $this->markTestSkipped('2fa.required middleware was removed from web owner routes (commit b71d1be).');
    }

    public function test_owner_without_2fa_sees_warning_flash(): void
    {
        $this->markTestSkipped('2fa.required middleware was removed from web owner routes (commit b71d1be).');
    }

    public function test_owner_with_2fa_passes_middleware(): void
    {
        $owner = $this->ownerWith2fa();

        // Must also have 2fa_verified in session to pass EnsureTwoFactor.
        // Dashboard may error in SQLite test env (DATE_FORMAT) — what matters
        // is that 2fa.required does NOT redirect to the setup page.
        $response = $this->actingAs($owner)
            ->withSession(['2fa_verified' => true])
            ->get('/owner/dashboard');

        $this->assertNotEquals(
            route('security.setup-2fa'),
            $response->headers->get('Location'),
        );
    }

    public function test_admin_without_2fa_is_redirected_to_setup_page(): void
    {
        $this->markTestSkipped('2fa.required middleware was removed from web owner routes (commit b71d1be).');
    }

    // ── API owner routes ──────────────────────────────────────────────────────

    public function test_api_owner_without_2fa_gets_403_json(): void
    {
        $owner = $this->ownerWithout2fa();

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/owner/residences');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'TWO_FACTOR_REQUIRED',
            ])
            ->assertJsonStructure(['setup_url']);
    }

    public function test_api_admin_without_2fa_gets_403_json(): void
    {
        $admin = $this->adminWithout2fa();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/statistics');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'TWO_FACTOR_REQUIRED',
            ]);
    }

    public function test_api_owner_with_2fa_passes_middleware(): void
    {
        $owner = $this->ownerWith2fa();

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/owner/residences');

        // Should not be blocked by RequireTwoFactorForPrivilegedUsers
        $response->assertStatus(200);
    }

    // ── Regular users are never blocked ──────────────────────────────────────

    public function test_regular_user_without_2fa_is_not_blocked(): void
    {
        $user = $this->regularUser();

        // Regular user cannot access /owner/* (blocked by role middleware first),
        // but the 2fa.required middleware must not interfere. Verify via direct
        // middleware invocation by hitting a route accessible to all auth users.
        $response = $this->actingAs($user)->get('/dashboard');

        // Redirected by dashboard controller to client dashboard, not to setup-2fa
        $response->assertRedirect(route('client.dashboard'));
    }

    // ── Setup page is always accessible (no redirect loop) ───────────────────

    public function test_setup_page_is_accessible_to_owner_without_2fa(): void
    {
        $owner = $this->ownerWithout2fa();

        $response = $this->actingAs($owner)->get(route('security.setup-2fa'));

        $response->assertOk();
    }

    public function test_two_factor_routes_are_accessible_to_owner_without_2fa(): void
    {
        $owner = $this->ownerWithout2fa();

        $response = $this->actingAs($owner)->get(route('two-factor.setup'));

        $response->assertOk();
    }
}
