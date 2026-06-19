<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WalletCreditBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $guest;
    protected Residence $residence;

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

        $owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $this->guest = User::factory()->create([
            'role' => 'user',
            'wallet_credit' => 50000,
            'referral_balance' => 25000,
        ]);
        $this->residence = Residence::factory()->create([
            'owner_id' => $owner->id,
            'cancellation_policy_id' => $policy->id,
            'status' => 'approved',
            'is_available' => true,
            'instant_book' => true,
        ]);
    }

    #[Test]
    public function booking_form_shows_wallet_balance_when_user_has_credits(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('bookings.create', $this->residence));

        $response->assertStatus(200);
        $response->assertSee('wallet_credit');
    }

    #[Test]
    public function instant_booking_accepts_use_wallet_credit_flag(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('bookings.store.instant', $this->residence), [
                'check_in'          => now()->addDays(5)->format('Y-m-d'),
                'check_out'         => now()->addDays(8)->format('Y-m-d'),
                'guests'            => 1,
                'adults'            => 1,
                'children'          => 0,
                'infants'           => 0,
                'payment_method'    => 'wave',
                'use_wallet_credit' => true,
            ]);

        // Should not get a 422 validation error
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
    }

    #[Test]
    public function instant_booking_accepts_use_referral_credit_flag(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('bookings.store.instant', $this->residence), [
                'check_in'             => now()->addDays(5)->format('Y-m-d'),
                'check_out'            => now()->addDays(8)->format('Y-m-d'),
                'guests'               => 1,
                'adults'               => 1,
                'children'             => 0,
                'infants'              => 0,
                'payment_method'       => 'wave',
                'use_referral_credit'  => true,
            ]);

        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
    }
}
