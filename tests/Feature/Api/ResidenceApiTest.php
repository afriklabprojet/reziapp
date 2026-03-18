<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests critiques API Résidences — listing, pagination, cache, edge cases
 */
#[Group('api')]
#[Group('residences')]
class ResidenceApiTest extends TestCase
{
    use RefreshDatabase;

    protected CancellationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Flexible policy',
            'refund_rules' => [['days_before' => 1, 'refund_percent' => 100]],
            'is_active' => true,
        ]);
    }

    // ─── PUBLIC LISTING ─────────────────────────────────────────

    #[Test]
    public function index_returns_paginated_residences(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Residence::factory()->count(25)->create([
            'owner_id' => $owner->id,
            'status' => 'approved',
            'cancellation_policy_id' => $this->policy->id,
        ]);

        $response = $this->getJson('/api/v1/residences?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'count', 'per_page', 'current_page', 'total_pages'],
                'links' => ['first', 'last', 'prev', 'next'],
            ])
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    #[Test]
    public function index_caps_per_page_at_50(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Residence::factory()->count(3)->create([
            'owner_id' => $owner->id,
            'status' => 'approved',
            'cancellation_policy_id' => $this->policy->id,
        ]);

        $response = $this->getJson('/api/v1/residences?per_page=999');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 50);
    }

    #[Test]
    public function index_does_not_leak_pending_residences(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Residence::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'pending',
            'cancellation_policy_id' => $this->policy->id,
        ]);

        $response = $this->getJson('/api/v1/residences');

        $response->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    // ─── SHOW ───────────────────────────────────────────────────

    #[Test]
    public function show_returns_residence_with_relations(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $residence = Residence::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'approved',
            'cancellation_policy_id' => $this->policy->id,
        ]);

        $response = $this->getJson("/api/v1/residences/{$residence->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'location', 'pricing', 'features'],
            ]);
    }

    #[Test]
    public function show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/v1/residences/99999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // ─── OWNER ENDPOINTS ────────────────────────────────────────

    #[Test]
    public function owner_can_list_own_residences(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Residence::factory()->count(3)->create([
            'owner_id' => $owner->id,
            'cancellation_policy_id' => $this->policy->id,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/v1/owner/residences');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    #[Test]
    public function regular_user_cannot_access_owner_routes(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/owner/residences');

        $response->assertStatus(403);
    }

    // ─── REFERENCE DATA (cached) ────────────────────────────────

    #[Test]
    public function amenities_returns_cached_list(): void
    {
        $response = $this->getJson('/api/v1/amenities');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    #[Test]
    public function cancellation_policies_returns_list(): void
    {
        $response = $this->getJson('/api/v1/cancellation-policies');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    // ─── ADMIN MODERATION ───────────────────────────────────────

    #[Test]
    public function admin_can_approve_residence(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'owner']);
        $residence = Residence::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'pending',
            'cancellation_policy_id' => $this->policy->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/admin/residences/{$residence->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('residences', [
            'id' => $residence->id,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function non_admin_cannot_approve(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $residence = Residence::factory()->create([
            'owner_id' => $owner->id,
            'status' => 'pending',
            'cancellation_policy_id' => $this->policy->id,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/residences/{$residence->id}/approve");

        $response->assertStatus(403);
    }
}
