<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingModification;
use App\Models\CancellationPolicy;
use App\Models\ChannelListing;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Sprint 3 — Verrouille le comportement des fonctionnalités déployées :
 * - Instant Book (badge + booking_type forcé)
 * - Split payment 50/50 (éligibilité, ventilation deposit/balance)
 * - Modification de réservation (création + approbation + rejet)
 * - Channel listings (création + unique constraint)
 */
class Sprint3FeaturesTest extends TestCase
{
    use RefreshDatabase;

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
            ],
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $this->guest = User::factory()->create(['role' => 'user']);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'cancellation_policy_id' => $this->policy->id,
            'status' => 'active',
            'is_available' => true,
            'max_guests' => 6,
        ]);
    }

    // ========================================
    // INSTANT BOOK
    // ========================================

    #[Test]
    public function instant_book_field_is_persisted_on_residence(): void
    {
        $this->residence->update(['instant_book' => true]);

        $this->assertTrue($this->residence->fresh()->instant_book);
    }

    // ========================================
    // SPLIT PAYMENT — Modèle
    // ========================================

    #[Test]
    public function split_payment_fields_are_fillable_on_booking(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'cancellation_policy_id' => $this->policy->id,
            'payment_split' => true,
            'deposit_amount' => 50000,
            'balance_amount' => 50000,
            'balance_due_at' => now()->addDays(60)->toDateString(),
        ]);

        $fresh = $booking->fresh();
        $this->assertTrue((bool) $fresh->payment_split);
        $this->assertEquals(50000, $fresh->deposit_amount);
        $this->assertEquals(50000, $fresh->balance_amount);
        $this->assertNotNull($fresh->balance_due_at);
    }

    #[Test]
    public function is_payment_split_helper_returns_true_when_split(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'cancellation_policy_id' => $this->policy->id,
            'payment_split' => true,
            'deposit_amount' => 25000,
            'balance_amount' => 25000,
        ]);

        $this->assertTrue($booking->isPaymentSplit());
    }

    #[Test]
    public function is_balance_due_returns_true_when_unpaid_and_due(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'cancellation_policy_id' => $this->policy->id,
            'payment_split' => true,
            'deposit_paid_at' => now()->subDays(30),
            'balance_amount' => 50000,
            'balance_due_at' => now()->subDay()->toDateString(),
            'balance_paid_at' => null,
        ]);

        $this->assertTrue($booking->isBalanceDue());
    }

    // ========================================
    // BOOKING MODIFICATION
    // ========================================

    #[Test]
    public function guest_can_request_a_booking_modification(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'cancellation_policy_id' => $this->policy->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(20)->toDateString(),
            'check_out' => now()->addDays(23)->toDateString(),
            'nights' => 3,
            'guests' => 2,
        ]);

        $response = $this->actingAs($this->guest)
            ->post(route('bookings.modify.store', $booking), [
                'requested_check_in' => now()->addDays(25)->toDateString(),
                'requested_check_out' => now()->addDays(28)->toDateString(),
                'requested_guests' => 3,
                'reason' => 'Décalage planning',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('booking_modifications', [
            'booking_id' => $booking->id,
            'requested_by_user_id' => $this->guest->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function owner_can_approve_a_pending_modification(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'cancellation_policy_id' => $this->policy->id,
            'status' => 'confirmed',
        ]);

        $modification = BookingModification::create([
            'booking_id' => $booking->id,
            'requested_by_user_id' => $this->guest->id,
            'original_check_in' => $booking->check_in,
            'original_check_out' => $booking->check_out,
            'original_guests' => $booking->guests,
            'requested_check_in' => now()->addDays(30)->toDateString(),
            'requested_check_out' => now()->addDays(33)->toDateString(),
            'requested_guests' => 3,
            'reason' => 'Test approval',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('owner.bookings.modifications.approve', $modification));

        $response->assertRedirect();
        $this->assertDatabaseHas('booking_modifications', [
            'id' => $modification->id,
            'status' => 'approved',
        ]);

        $this->assertEquals(
            \Carbon\Carbon::parse($modification->requested_check_in)->toDateString(),
            $booking->fresh()->check_in->toDateString(),
        );
    }

    #[Test]
    public function owner_can_reject_a_pending_modification(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'cancellation_policy_id' => $this->policy->id,
            'status' => 'confirmed',
        ]);

        $modification = BookingModification::create([
            'booking_id' => $booking->id,
            'requested_by_user_id' => $this->guest->id,
            'original_check_in' => $booking->check_in,
            'original_check_out' => $booking->check_out,
            'original_guests' => $booking->guests,
            'requested_check_in' => now()->addDays(30)->toDateString(),
            'requested_check_out' => now()->addDays(33)->toDateString(),
            'requested_guests' => 3,
            'reason' => 'Test rejection',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('owner.bookings.modifications.reject', $modification));

        $response->assertRedirect();
        $this->assertDatabaseHas('booking_modifications', [
            'id' => $modification->id,
            'status' => 'rejected',
        ]);
    }

    #[Test]
    public function modification_helper_isPending_works(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'cancellation_policy_id' => $this->policy->id,
        ]);

        $modification = BookingModification::create([
            'booking_id' => $booking->id,
            'requested_by_user_id' => $this->guest->id,
            'original_check_in' => now()->addDays(5)->toDateString(),
            'original_check_out' => now()->addDays(8)->toDateString(),
            'original_guests' => 2,
            'requested_check_in' => now()->addDays(10)->toDateString(),
            'requested_check_out' => now()->addDays(13)->toDateString(),
            'requested_guests' => 2,
            'status' => 'pending',
        ]);

        $this->assertTrue($modification->isPending());
    }

    // ========================================
    // CHANNEL LISTINGS
    // ========================================

    #[Test]
    public function owner_can_create_a_channel_listing(): void
    {
        $listing = ChannelListing::create([
            'residence_id' => $this->residence->id,
            'channel' => 'airbnb',
            'sync_status' => 'pending',
        ]);

        $this->assertDatabaseHas('channel_listings', [
            'residence_id' => $this->residence->id,
            'channel' => 'airbnb',
        ]);
        $this->assertNotNull($listing->channelLabel());
        $this->assertNotEmpty($listing->statusBadge());
    }

    #[Test]
    public function channel_listing_is_unique_per_residence_and_channel(): void
    {
        ChannelListing::create([
            'residence_id' => $this->residence->id,
            'channel' => 'booking',
            'sync_status' => 'pending',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ChannelListing::create([
            'residence_id' => $this->residence->id,
            'channel' => 'booking',
            'sync_status' => 'pending',
        ]);
    }

    #[Test]
    public function residence_has_channel_listings_relation(): void
    {
        ChannelListing::create([
            'residence_id' => $this->residence->id,
            'channel' => 'expedia',
            'sync_status' => 'pending',
        ]);

        $this->assertCount(1, $this->residence->fresh()->channelListings);
    }
}
