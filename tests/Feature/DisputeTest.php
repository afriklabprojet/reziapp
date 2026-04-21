<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Dispute;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\RequiresMysql;
use Tests\TestCase;

/**
 * Tests des litiges (disputes)
 * Couvre : création, affichage, listing, ajout de preuves, résolution
 */
class DisputeTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    use RequiresMysql;

    protected User $guest;
    protected User $owner;
    protected User $admin;
    protected Residence $residence;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests - Dispute creation requires SupportTicket which has MySQL-specific features
        $this->skipIfSqlite();

        $policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Politique flexible',
            'refund_rules' => [['days_before' => 7, 'refund_percent' => 100]],
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->guest = User::factory()->create(['role' => 'user']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'cancellation_policy_id' => $policy->id,
            'status' => 'approved',
        ]);
        $this->booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'status' => 'completed',
        ]);
    }

    // ========================================
    // LISTING
    // ========================================

    #[Test]
    public function guest_can_view_dispute_list(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('disputes.index'));

        $response->assertStatus(200);
        $response->assertViewIs('disputes.index');
    }

    #[Test]
    public function unauthenticated_user_cannot_view_disputes(): void
    {
        $response = $this->get(route('disputes.index'));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // CRÉATION
    // ========================================

    #[Test]
    public function guest_can_access_dispute_creation_form(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('disputes.create', ['booking_id' => $this->booking->id]));

        $response->assertStatus(200);
        $response->assertViewIs('disputes.create');
    }

    #[Test]
    public function guest_can_create_a_dispute(): void
    {
        $types = Dispute::getTypes();
        $type = array_key_first($types);

        $response = $this->actingAs($this->guest)
            ->post(route('disputes.store'), [
                'booking_id' => $this->booking->id,
                'type' => $type,
                'reason' => 'Logement non conforme à la description',
                'detailed_description' => 'La résidence ne correspondait pas aux photos et à la description. '
                    .'Il manquait la climatisation qui était annoncée.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('disputes', [
            'booking_id' => $this->booking->id,
            'initiator_id' => $this->guest->id,
            'type' => $type,
        ]);
    }

    #[Test]
    public function dispute_creation_requires_valid_data(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('disputes.store'), []);

        $response->assertSessionHasErrors(['booking_id', 'type', 'detailed_description']);
    }

    #[Test]
    public function cannot_create_duplicate_dispute_for_same_booking(): void
    {
        $type = array_key_first(Dispute::getTypes());

        // Create first dispute
        Dispute::create([
            'booking_id' => $this->booking->id,
            'initiated_by' => 'user',
            'initiator_id' => $this->guest->id,
            'type' => $type,
            'reason' => 'Test',
            'detailed_description' => 'Description du litige existant',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        // Try to create another
        $response = $this->actingAs($this->guest)
            ->post(route('disputes.store'), [
                'booking_id' => $this->booking->id,
                'type' => $type,
                'reason' => 'Duplicate',
                'detailed_description' => 'This should not be allowed.',
            ]);

        $this->assertDatabaseCount('disputes', 1);
    }

    // ========================================
    // AFFICHAGE
    // ========================================

    #[Test]
    public function initiator_can_view_dispute_details(): void
    {
        $dispute = Dispute::create([
            'booking_id' => $this->booking->id,
            'initiated_by' => 'user',
            'initiator_id' => $this->guest->id,
            'type' => array_key_first(Dispute::getTypes()),
            'reason' => 'Test',
            'detailed_description' => 'Détails du litige.',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('disputes.show', $dispute));

        $response->assertStatus(200);
        $response->assertViewIs('disputes.show');
        $response->assertViewHas('dispute');
    }

    #[Test]
    public function owner_can_view_dispute_on_their_booking(): void
    {
        $dispute = Dispute::create([
            'booking_id' => $this->booking->id,
            'initiated_by' => 'user',
            'initiator_id' => $this->guest->id,
            'type' => array_key_first(Dispute::getTypes()),
            'reason' => 'Test',
            'detailed_description' => 'Description.',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('disputes.show', $dispute));

        $response->assertStatus(200);
    }

    #[Test]
    public function unrelated_user_cannot_view_dispute(): void
    {
        $other = User::factory()->create(['role' => 'user']);
        $dispute = Dispute::create([
            'booking_id' => $this->booking->id,
            'initiated_by' => 'user',
            'initiator_id' => $this->guest->id,
            'type' => array_key_first(Dispute::getTypes()),
            'reason' => 'Test',
            'detailed_description' => 'Description.',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($other)
            ->get(route('disputes.show', $dispute));

        $response->assertStatus(403);
    }

    // ========================================
    // API
    // ========================================

    #[Test]
    public function can_get_dispute_types_via_api(): void
    {
        $response = $this->actingAs($this->guest)
            ->getJson(route('api.v1.disputes.types'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['types']);
    }
}
