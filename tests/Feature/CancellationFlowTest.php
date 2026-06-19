<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cancellation;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests du flux d'annulation
 * Couvre : preview, annulation voyageur, annulation propriétaire, historique
 * */
class CancellationFlowTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $guest;
    protected User $owner;
    protected Residence $residence;
    protected CancellationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Annulation gratuite jusqu\'à 24h avant',
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
    }

    // ========================================
    // PREVIEW D'ANNULATION
    // ========================================

    #[Test]
    public function guest_can_preview_cancellation(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => now()->addDays(14),
            'check_out' => now()->addDays(17),
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('cancellations.preview', $booking));

        $response->assertStatus(200);
        $response->assertViewIs('cancellations.preview');
        $response->assertViewHas('booking');
        $response->assertViewHas('preview');
        $response->assertViewHas('reasons');
    }

    #[Test]
    public function owner_can_preview_cancellation(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => now()->addDays(14),
            'check_out' => now()->addDays(17),
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('cancellations.preview', $booking));

        $response->assertStatus(200);
        $response->assertViewHas('isOwner', true);
    }

    #[Test]
    public function third_party_cannot_preview_cancellation(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)
            ->get(route('cancellations.preview', $booking));

        $response->assertStatus(403);
    }

    // ========================================
    // ANNULATION PAR LE VOYAGEUR
    // ========================================

    #[Test]
    public function guest_can_cancel_their_booking(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => now()->addDays(14),
            'check_out' => now()->addDays(17),
        ]);

        $response = $this->actingAs($this->guest)
            ->post(route('cancellations.cancel-guest', $booking), [
                'reason' => 'change_of_plans',
                'detailed_reason' => 'J\'ai changé mes plans de voyage.',
            ]);

        $response->assertRedirect(route('bookings.show', $booking));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function guest_cancel_requires_valid_reason(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => now()->addDays(10),
            'check_out' => now()->addDays(15),
        ]);

        $response = $this->actingAs($this->guest)
            ->post(route('cancellations.cancel-guest', $booking), [
                'reason' => 'invalid_reason_code',
            ]);

        $response->assertSessionHasErrors(['reason']);
    }

    #[Test]
    public function guest_cancel_requires_reason_field(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->guest)
            ->post(route('cancellations.cancel-guest', $booking), []);

        $response->assertSessionHasErrors(['reason']);
    }

    // ========================================
    // ANNULATION PAR LE PROPRIÉTAIRE
    // ========================================

    #[Test]
    public function owner_can_cancel_booking(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => now()->addDays(14),
            'check_out' => now()->addDays(17),
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('cancellations.cancel-owner', $booking), [
                'reason' => 'property_unavailable',
                'detailed_reason' => 'Travaux urgents nécessaires.',
            ]);

        $response->assertRedirect(route('owner.bookings.show', $booking));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function owner_cancel_requires_valid_reason(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('cancellations.cancel-owner', $booking), [
                'reason' => 'not_a_real_reason',
            ]);

        $response->assertSessionHasErrors(['reason']);
    }

    #[Test]
    public function guest_cannot_use_owner_cancel_route(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->guest)
            ->post(route('cancellations.cancel-owner', $booking), [
                'reason' => 'property_unavailable',
            ]);

        $response->assertStatus(403);
    }

    // ========================================
    // HISTORIQUE DES ANNULATIONS
    // ========================================

    #[Test]
    public function guest_can_view_cancellation_history(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('cancellations.history'));

        $response->assertStatus(200);
        $response->assertViewIs('cancellations.history');
        $response->assertViewHas('cancellations');
        $response->assertViewHas('stats');
    }

    #[Test]
    public function unauthenticated_user_cannot_view_history(): void
    {
        $response = $this->get(route('cancellations.history'));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // POLITIQUES D'ANNULATION (PUBLIC)
    // ========================================

    #[Test]
    public function anyone_can_view_cancellation_policies(): void
    {
        $response = $this->get(route('cancellations.policies'));

        $response->assertStatus(200);
        $response->assertViewIs('cancellations.policies');
        $response->assertViewHas('policies');
    }

    // ========================================
    // DÉTAILS D'UNE ANNULATION
    // ========================================

    #[Test]
    public function guest_can_view_their_cancellation_details(): void
    {
        $booking = Booking::factory()->cancelled()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);

        $cancellation = Cancellation::create([
            'booking_id' => $booking->id,
            'initiated_by' => 'user',
            'initiated_by_user_id' => $this->guest->id,
            'reason_category' => 'change_of_plans',
            'days_before_checkin' => 0,
            'refund_percent_applied' => 50,
            'original_amount' => $booking->total_amount,
            'refund_amount' => 50000,
            'penalty_amount' => 0,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('cancellations.show', $cancellation));

        $response->assertStatus(200);
        $response->assertViewIs('cancellations.show');
    }

    #[Test]
    public function stranger_cannot_view_cancellation_details(): void
    {
        $booking = Booking::factory()->cancelled()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);

        $cancellation = Cancellation::create([
            'booking_id' => $booking->id,
            'initiated_by' => 'user',
            'initiated_by_user_id' => $this->guest->id,
            'reason_category' => 'change_of_plans',
            'days_before_checkin' => 0,
            'refund_percent_applied' => 0,
            'original_amount' => $booking->total_amount,
            'refund_amount' => 0,
            'penalty_amount' => 0,
            'status' => 'pending',
        ]);

        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)
            ->get(route('cancellations.show', $cancellation));

        $response->assertStatus(403);
    }
}
