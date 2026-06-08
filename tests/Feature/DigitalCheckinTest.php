<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\DigitalCheckin;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalCheckinTest extends TestCase
{
    use RefreshDatabase;

    protected User $guest;
    protected User $owner;
    protected Booking $booking;
    protected DigitalCheckin $checkin;

    protected function setUp(): void
    {
        parent::setUp();

        $policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Test',
            'refund_rules' => [['days_before' => 7, 'refund_percent' => 100]],
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->guest = User::factory()->create(['role' => 'user']);
        $residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'cancellation_policy_id' => $policy->id,
            'status' => 'approved',
        ]);

        $this->booking = Booking::factory()->create([
            'user_id'      => $this->guest->id,
            'residence_id' => $residence->id,
            'status'       => 'confirmed',
            'check_in'     => now()->addDay()->toDateString(),
            'check_out'    => now()->addDays(4)->toDateString(),
        ]);

        $this->checkin = DigitalCheckin::create([
            'booking_id'   => $this->booking->id,
            'residence_id' => $residence->id,
            'guest_id'     => $this->guest->id,
            'type'         => DigitalCheckin::TYPE_CHECK_IN,
            'status'       => DigitalCheckin::STATUS_PENDING,
            'qr_token'     => 'testtoken123secure',
        ]);
    }

    #[Test]
    public function guest_can_view_their_checkin_qr_page(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('bookings.checkin', $this->booking));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.checkin');
        $response->assertViewHas('checkin');
    }

    #[Test]
    public function unauthorized_user_cannot_view_checkin_qr(): void
    {
        $other = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($other)
            ->get(route('bookings.checkin', $this->booking));

        $response->assertStatus(403);
    }

    #[Test]
    public function owner_can_verify_qr_token(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('checkin.verify', $this->checkin->qr_token));

        $response->assertStatus(200);
        $response->assertViewIs('bookings.checkin-verify');
        $response->assertViewHas('checkin');
    }

    #[Test]
    public function owner_can_confirm_checkin(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('checkin.confirm', $this->checkin->qr_token));

        $response->assertRedirect();
        $this->assertDatabaseHas('digital_checkins', [
            'id'     => $this->checkin->id,
            'status' => DigitalCheckin::STATUS_CONFIRMED,
        ]);
    }

    #[Test]
    public function invalid_token_returns_404(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('checkin.verify', 'nonexistent-token'));

        $response->assertStatus(404);
    }
}
