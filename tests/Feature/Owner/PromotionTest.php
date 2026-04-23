<?php

namespace Tests\Feature\Owner;

use App\Models\CancellationPolicy;
use App\Models\Promotion;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\RequiresMysql;
use Tests\TestCase;

/**
 * Tests Feature — Promotions propriétaires (Soldes)
 * Couvre : accès, création, validation, toggle, suppression, 403 inter-propriétaires, stats
 */
class PromotionTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;
    use RequiresMysql;

    protected User $owner;
    protected Residence $residence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfSqlite();

        $policy = CancellationPolicy::create([
            'name'         => 'flexible',
            'display_name' => 'Flexible',
            'description'  => 'Test',
            'refund_rules' => [['days_before' => 7, 'refund_percent' => 100]],
            'is_active'    => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->residence = Residence::factory()->create([
            'owner_id'                => $this->owner->id,
            'cancellation_policy_id'  => $policy->id,
            'status'                  => 'active',
        ]);
    }

    // =========================================================================
    // ACCÈS
    // =========================================================================

    #[Test]
    public function owner_can_view_promotions_index(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.marketing.promotions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('owner.marketing.promotions.index');
    }

    #[Test]
    public function non_owner_cannot_access_promotions_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('owner.marketing.promotions.index'))
            ->assertStatus(403);
    }

    #[Test]
    public function guest_is_redirected_from_promotions_index(): void
    {
        $this->get(route('owner.marketing.promotions.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function owner_can_access_create_form(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.marketing.promotions.create'));

        $response->assertStatus(200);
        $response->assertViewIs('owner.marketing.promotions.create');
    }

    // =========================================================================
    // CRÉATION
    // =========================================================================

    #[Test]
    public function owner_can_create_promotion(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.marketing.promotions.store'), [
                'residence_id'   => $this->residence->id,
                'title'          => 'Promo Test',
                'discount_type'  => 'percentage',
                'discount_value' => 20,
                'starts_at'      => now()->addDay()->format('Y-m-d'),
                'ends_at'        => now()->addDays(14)->format('Y-m-d'),
            ]);

        $response->assertRedirect(route('owner.marketing.promotions.index'));
        $this->assertDatabaseHas('promotions', [
            'residence_id'  => $this->residence->id,
            'user_id'       => $this->owner->id,
            'title'         => 'Promo Test',
            'discount_type' => 'percentage',
            'discount_value'=> 20,
            'is_active'     => true,
        ]);
    }

    #[Test]
    public function creation_requires_all_mandatory_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.marketing.promotions.store'), []);

        $response->assertSessionHasErrors(['residence_id', 'title', 'discount_type', 'discount_value', 'starts_at', 'ends_at']);
    }

    #[Test]
    public function creation_rejects_invalid_discount_type(): void
    {
        $this->actingAs($this->owner)
            ->post(route('owner.marketing.promotions.store'), [
                'residence_id'   => $this->residence->id,
                'title'          => 'Invalid Type',
                'discount_type'  => 'invalid_type',
                'discount_value' => 20,
                'starts_at'      => now()->addDay()->format('Y-m-d'),
                'ends_at'        => now()->addDays(10)->format('Y-m-d'),
            ])
            ->assertSessionHasErrors(['discount_type']);
    }

    #[Test]
    public function owner_cannot_exceed_90_percent_discount(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.marketing.promotions.store'), [
                'residence_id'   => $this->residence->id,
                'title'          => 'Trop généreux',
                'discount_type'  => 'percentage',
                'discount_value' => 95,
                'starts_at'      => now()->addDay()->format('Y-m-d'),
                'ends_at'        => now()->addDays(14)->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors(['discount_value']);
        $this->assertDatabaseMissing('promotions', ['title' => 'Trop généreux']);
    }

    #[Test]
    public function owner_cannot_create_promotion_for_other_owner_residence(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner']);
        $otherResidence = Residence::factory()->create(['owner_id' => $otherOwner->id]);

        $this->actingAs($this->owner)
            ->post(route('owner.marketing.promotions.store'), [
                'residence_id'   => $otherResidence->id,
                'title'          => 'Tentative hack',
                'discount_type'  => 'percentage',
                'discount_value' => 20,
                'starts_at'      => now()->addDay()->format('Y-m-d'),
                'ends_at'        => now()->addDays(14)->format('Y-m-d'),
            ])
            ->assertStatus(404);

        $this->assertDatabaseMissing('promotions', ['title' => 'Tentative hack']);
    }

    // =========================================================================
    // TOGGLE
    // =========================================================================

    #[Test]
    public function owner_can_toggle_own_promotion(): void
    {
        $promotion = Promotion::factory()->active()->create([
            'residence_id' => $this->residence->id,
            'user_id'      => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->patch(route('owner.marketing.promotions.toggle', $promotion))
            ->assertRedirect();

        $this->assertFalse($promotion->fresh()->is_active);

        // Retoggle → réactivée
        $this->actingAs($this->owner)
            ->patch(route('owner.marketing.promotions.toggle', $promotion))
            ->assertRedirect();

        $this->assertTrue($promotion->fresh()->is_active);
    }

    #[Test]
    public function owner_cannot_toggle_other_owner_promotion(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner']);
        $otherResidence = Residence::factory()->create(['owner_id' => $otherOwner->id]);
        $promotion = Promotion::factory()->active()->create([
            'residence_id' => $otherResidence->id,
            'user_id'      => $otherOwner->id,
        ]);

        $this->actingAs($this->owner)
            ->patch(route('owner.marketing.promotions.toggle', $promotion))
            ->assertStatus(403);
    }

    // =========================================================================
    // SUPPRESSION
    // =========================================================================

    #[Test]
    public function owner_can_delete_own_promotion(): void
    {
        $promotion = Promotion::factory()->create([
            'residence_id' => $this->residence->id,
            'user_id'      => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->delete(route('owner.marketing.promotions.destroy', $promotion))
            ->assertRedirect(route('owner.marketing.promotions.index'));

        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    #[Test]
    public function owner_cannot_delete_other_owner_promotion(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner']);
        $otherResidence = Residence::factory()->create(['owner_id' => $otherOwner->id]);
        $promotion = Promotion::factory()->create([
            'residence_id' => $otherResidence->id,
            'user_id'      => $otherOwner->id,
        ]);

        $this->actingAs($this->owner)
            ->delete(route('owner.marketing.promotions.destroy', $promotion))
            ->assertStatus(403);

        $this->assertDatabaseHas('promotions', ['id' => $promotion->id]);
    }

    // =========================================================================
    // STATS
    // =========================================================================

    #[Test]
    public function stats_counts_correctly(): void
    {
        // Nettoyer d'abord les promotions de cette résidence
        Promotion::where('residence_id', $this->residence->id)->delete();

        // Créer des promotions avec états précis
        Promotion::factory()->active()->create([
            'residence_id' => $this->residence->id,
            'user_id'      => $this->owner->id,
        ]);
        Promotion::factory()->active()->create([
            'residence_id' => $this->residence->id,
            'user_id'      => $this->owner->id,
        ]);
        Promotion::factory()->expired()->create([
            'residence_id' => $this->residence->id,
            'user_id'      => $this->owner->id,
        ]);
        Promotion::factory()->inactive()->create([
            'residence_id' => $this->residence->id,
            'user_id'      => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('owner.marketing.promotions.index'));

        $response->assertStatus(200);

        $stats = $response->viewData('stats');
        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['active']);
        $this->assertEquals(1, $stats['expired']);
        $this->assertEquals(1, $stats['inactive']);
    }
}
