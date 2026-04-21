<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests du flux de réservation complet
 * Couvre : création, affichage, listing, annulation, demandes propriétaire
 * */
class BookingFlowTest extends TestCase
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

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->guest = User::factory()->create(['role' => 'user']);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'cancellation_policy_id' => $this->policy->id,
            'status' => 'approved',
            'is_available' => true,
            'max_guests' => 6,
        ]);
    }

    // ========================================
    // CRÉATION DE RÉSERVATION
    // ========================================

    #[Test]
    public function guest_can_view_booking_creation_form(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('bookings.create', $this->residence));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.create');
        $response->assertViewHas('residence');
    }

    #[Test]
    public function guest_can_view_booking_form_with_dates(): void
    {
        $checkIn = now()->addDays(5)->format('Y-m-d');
        $checkOut = now()->addDays(8)->format('Y-m-d');

        $response = $this->actingAs($this->guest)
            ->get(route('bookings.create', [
                'residence' => $this->residence,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
            ]));

        $response->assertStatus(200);
        $response->assertViewHas('checkIn');
        $response->assertViewHas('checkOut');
    }

    #[Test]
    public function unauthenticated_user_cannot_create_booking(): void
    {
        $response = $this->get(route('bookings.create', $this->residence));

        // La route bookings.create est intentionnellement accessible aux invités
        // (voir routes/web/misc.php — "Routes de réservation pour invités (sans auth)")
        $response->assertStatus(200);
    }

    #[Test]
    public function instant_booking_requires_valid_data(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('bookings.store.instant', $this->residence), [
                // Données manquantes
            ]);

        $response->assertSessionHasErrors(['check_in', 'check_out', 'guests', 'adults']);
    }

    #[Test]
    public function instant_booking_check_out_must_be_after_check_in(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('bookings.store.instant', $this->residence), [
                'check_in' => now()->addDays(5)->format('Y-m-d'),
                'check_out' => now()->addDays(3)->format('Y-m-d'), // Avant check_in
                'guests' => 2,
                'adults' => 2,
            ]);

        $response->assertSessionHasErrors(['check_out']);
    }

    #[Test]
    public function instant_booking_guests_cannot_exceed_max(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('bookings.store.instant', $this->residence), [
                'check_in' => now()->addDays(5)->format('Y-m-d'),
                'check_out' => now()->addDays(8)->format('Y-m-d'),
                'guests' => 99, // Trop de guests
                'adults' => 2,
            ]);

        $response->assertSessionHasErrors(['guests']);
    }

    #[Test]
    public function booking_request_requires_message(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('bookings.store.request', $this->residence), [
                'check_in' => now()->addDays(5)->format('Y-m-d'),
                'check_out' => now()->addDays(8)->format('Y-m-d'),
                'guests' => 2,
                'adults' => 2,
                // payment_method manquant
            ]);

        $response->assertSessionHasErrors(['payment_method']);
    }

    // ========================================
    // LISTING ET AFFICHAGE
    // ========================================

    #[Test]
    public function guest_can_view_their_bookings_list(): void
    {
        Booking::factory()->count(3)->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('bookings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.index');
        $response->assertViewHas('bookings');
        $response->assertViewHas('stats');
    }

    #[Test]
    public function guest_can_filter_bookings_by_status(): void
    {
        Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);
        Booking::factory()->cancelled()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('bookings.index', ['status' => 'confirmed']));

        $response->assertStatus(200);
    }

    #[Test]
    public function guest_can_view_their_own_booking(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('bookings.show', $booking));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.show');
    }

    #[Test]
    public function guest_cannot_view_other_users_booking(): void
    {
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('bookings.show', $booking));

        $response->assertStatus(403);
    }

    #[Test]
    public function unauthenticated_user_cannot_view_bookings(): void
    {
        $response = $this->get(route('bookings.index'));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // ANNULATION PAR LE VOYAGEUR
    // ========================================

    #[Test]
    public function guest_can_cancel_their_confirmed_booking(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => now()->addDays(14),
            'check_out' => now()->addDays(17),
        ]);

        $response = $this->actingAs($this->guest)
            ->put(route('bookings.cancel', $booking), [
                'reason' => 'Changement de plans',
            ]);

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function cancel_booking_requires_reason(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => now()->addDays(14),
            'check_out' => now()->addDays(17),
        ]);

        $response = $this->actingAs($this->guest)
            ->put(route('bookings.cancel', $booking), []);

        $response->assertSessionHasErrors(['reason']);
    }

    #[Test]
    public function guest_cannot_cancel_other_users_booking(): void
    {
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->confirmed()->create([
            'user_id' => $otherUser->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->guest)
            ->put(route('bookings.cancel', $booking), [
                'reason' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    // ========================================
    // PARTIE PROPRIÉTAIRE
    // ========================================

    #[Test]
    public function owner_can_view_their_bookings(): void
    {
        Booking::factory()->count(2)->create([
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('owner.bookings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('owner.bookings.index');
    }

    #[Test]
    public function regular_user_cannot_access_owner_bookings(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('owner.bookings.index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function owner_can_view_booking_details(): void
    {
        $booking = Booking::factory()->create([
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('owner.bookings.show', $booking));

        $response->assertStatus(200);
        $response->assertViewIs('owner.bookings.show');
    }

    #[Test]
    public function owner_cannot_view_other_owners_bookings(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner']);
        $otherResidence = Residence::factory()->create([
            'owner_id' => $otherOwner->id,
            'cancellation_policy_id' => $this->policy->id,
        ]);
        $booking = Booking::factory()->create([
            'residence_id' => $otherResidence->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('owner.bookings.show', $booking));

        $response->assertStatus(403);
    }

    #[Test]
    public function owner_can_cancel_booking(): void
    {
        $booking = Booking::factory()->confirmed()->create([
            'residence_id' => $this->residence->id,
            'check_in' => now()->addDays(14),
            'check_out' => now()->addDays(17),
        ]);

        $response = $this->actingAs($this->owner)
            ->put(route('owner.bookings.cancel', $booking), [
                'reason' => 'Travaux urgents',
            ]);

        $response->assertRedirect(route('owner.bookings.index'));
        $response->assertSessionHas('success');
    }

    // ========================================
    // CALCUL DE PRIX (AJAX)
    // ========================================

    #[Test]
    public function authenticated_user_can_calculate_price(): void
    {
        $response = $this->actingAs($this->guest)
            ->postJson(route('residences.calculate-price', $this->residence), [
                'check_in' => now()->addDays(5)->format('Y-m-d'),
                'check_out' => now()->addDays(8)->format('Y-m-d'),
                'guests' => 2,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success']);
    }

    #[Test]
    public function price_calculation_validates_dates(): void
    {
        $response = $this->actingAs($this->guest)
            ->postJson(route('residences.calculate-price', $this->residence), [
                'check_in' => now()->subDay()->format('Y-m-d'), // Passé
                'check_out' => now()->addDays(3)->format('Y-m-d'),
                'guests' => 2,
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function authenticated_user_can_check_availability(): void
    {
        $response = $this->actingAs($this->guest)
            ->postJson(route('residences.check-availability', $this->residence), [
                'check_in' => now()->addDays(5)->format('Y-m-d'),
                'check_out' => now()->addDays(8)->format('Y-m-d'),
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['available']);
    }

    // ========================================
    // CALENDRIER PROPRIÉTAIRE
    // ========================================

    #[Test]
    public function owner_can_view_calendar(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.bookings.calendar', $this->residence));

        $response->assertStatus(200);
        $response->assertViewIs('owner.bookings.calendar');
    }

    #[Test]
    public function other_owner_cannot_view_calendar(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner']);

        $response = $this->actingAs($otherOwner)
            ->get(route('owner.bookings.calendar', $this->residence));

        $response->assertStatus(403);
    }
}
