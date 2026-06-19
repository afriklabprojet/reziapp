<?php

namespace Tests\Feature\Owner;

use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\SponsoredListing;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\RequiresMysql;
use Tests\TestCase;

/**
 * Tests des listings sponsorisés
 * Couvre : création, paiement, gestion (pause/resume/cancel)
 */
class SponsoredListingTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;
    use RequiresMysql;

    protected User $owner;
    protected Residence $residence;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests - SponsoredListing has MySQL-specific constraints
        $this->skipIfSqlite();

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
            'status' => 'active',
        ]);
    }

    // ========================================
    // LISTING
    // ========================================

    #[Test]
    public function owner_can_view_sponsored_index(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.marketing.sponsored.index'));

        $response->assertStatus(200);
        $response->assertViewIs('owner.marketing.sponsored.index');
    }

    #[Test]
    public function non_owner_cannot_access_sponsored_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->get(route('owner.marketing.sponsored.index'));

        $response->assertStatus(403);
    }

    // ========================================
    // CRÉATION
    // ========================================

    #[Test]
    public function owner_can_access_create_form(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.marketing.sponsored.create'));

        $response->assertStatus(200);
        $response->assertViewIs('owner.marketing.sponsored.create');
    }

    #[Test]
    public function owner_can_create_sponsored_listing(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.marketing.sponsored.store'), [
                'residence_id' => $this->residence->id,
                'type' => 'featured_home',
                'duration' => 7,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sponsored_listings', [
            'residence_id' => $this->residence->id,
            'type' => 'featured_home',
            'duration_days' => 7,
        ]);
    }

    #[Test]
    public function sponsored_creation_requires_valid_data(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.marketing.sponsored.store'), [
                'type' => 'invalid_type',
            ]);

        $response->assertSessionHasErrors(['residence_id', 'type']);
    }

    #[Test]
    public function owner_cannot_sponsor_another_owners_residence(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $otherResidence = Residence::factory()->create([
            'owner_id' => $otherOwner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('owner.marketing.sponsored.store'), [
                'residence_id' => $otherResidence->id,
                'type' => 'featured_home',
                'duration' => 7,
            ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function owner_can_view_sponsored_details(): void
    {
        $sponsored = SponsoredListing::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->owner->id,
            'type' => 'featured_home',
            'duration_days' => 7,
            'amount' => 25000,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('owner.marketing.sponsored.show', $sponsored));

        $response->assertStatus(200);
    }

    #[Test]
    public function owner_can_pause_active_sponsored(): void
    {
        $sponsored = SponsoredListing::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->owner->id,
            'type' => 'top_search',
            'duration_days' => 14,
            'amount' => 30000,
            'status' => 'active',
            'is_paid' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDays(14),
        ]);

        $response = $this->actingAs($this->owner)
            ->patch(route('owner.marketing.sponsored.pause', $sponsored));

        $response->assertRedirect();
        $sponsored->refresh();
        $this->assertEquals('paused', $sponsored->status);
    }

    #[Test]
    public function owner_can_resume_paused_sponsored(): void
    {
        $sponsored = SponsoredListing::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->owner->id,
            'type' => 'highlighted',
            'duration_days' => 7,
            'amount' => 7500,
            'status' => 'paused',
            'is_paid' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->owner)
            ->patch(route('owner.marketing.sponsored.resume', $sponsored));

        $response->assertRedirect();
        $sponsored->refresh();
        $this->assertEquals('active', $sponsored->status);
    }

    #[Test]
    public function owner_can_cancel_pending_sponsored(): void
    {
        $sponsored = SponsoredListing::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->owner->id,
            'type' => 'premium_listing',
            'duration_days' => 30,
            'amount' => 105000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->patch(route('owner.marketing.sponsored.cancel', $sponsored));

        $response->assertRedirect();
        $sponsored->refresh();
        $this->assertEquals('cancelled', $sponsored->status);
    }

    #[Test]
    public function other_owner_cannot_manage_sponsored(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $sponsored = SponsoredListing::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->owner->id,
            'type' => 'featured_home',
            'duration_days' => 7,
            'amount' => 25000,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($otherOwner)
            ->get(route('owner.marketing.sponsored.show', $sponsored));

        $response->assertStatus(403);
    }
}
