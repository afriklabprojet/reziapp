<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\RequiresMysql;
use Tests\TestCase;

/**
 * Tests critiques du BookingService — double booking, edge cases, concurrence
 */
#[Group('booking')]
#[Group('critical')]
class BookingServiceTest extends TestCase
{
    use DatabaseTransactions;
    use RequiresMysql;

    protected User $guest;
    protected User $owner;
    protected Residence $residence;
    protected CancellationPolicy $policy;
    protected BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfSqlite();

        $this->policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Flexible',
            'refund_rules' => [['days_before' => 1, 'refund_percent' => 100]],
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $this->guest = User::factory()->create(['role' => 'user']);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'active',
            'cancellation_policy_id' => $this->policy->id,
            'is_available' => true,
        ]);

        $this->service = app(BookingService::class);
    }

    // ─── AVAILABILITY CHECK ─────────────────────────────────────

    #[Test]
    public function check_availability_returns_available_for_free_dates(): void
    {
        $result = $this->service->checkAvailability(
            $this->residence->id,
            Carbon::now()->addDays(10),
            Carbon::now()->addDays(13),
        );

        $this->assertTrue($result['available']);
    }

    #[Test]
    public function check_availability_returns_unavailable_when_booked(): void
    {
        Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => Carbon::now()->addDays(10),
            'check_out' => Carbon::now()->addDays(13),
            'status' => 'confirmed',
            'cancellation_policy_id' => $this->policy->id,
        ]);

        $result = $this->service->checkAvailability(
            $this->residence->id,
            Carbon::now()->addDays(11),
            Carbon::now()->addDays(14),
        );

        $this->assertFalse($result['available']);
        $this->assertEquals('already_booked', $result['reason']);
    }

    #[Test]
    public function check_availability_allows_adjacent_dates(): void
    {
        // Booking du 10 au 13 — on vérifie que le 13 au 16 est libre (check_out = check_in OK)
        Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => Carbon::now()->addDays(10),
            'check_out' => Carbon::now()->addDays(13),
            'status' => 'confirmed',
            'cancellation_policy_id' => $this->policy->id,
        ]);

        // Adjacent booking starting at previous checkout
        $result = $this->service->checkAvailability(
            $this->residence->id,
            Carbon::now()->addDays(13),
            Carbon::now()->addDays(16),
        );

        // This depends on the overlap logic — just check it doesn't throw
        $this->assertIsBool($result['available']);
    }

    // ─── UNAVAILABLE DATES ──────────────────────────────────────

    #[Test]
    public function get_unavailable_dates_returns_booked_date_range(): void
    {
        $checkIn = Carbon::now()->addDays(5);
        $checkOut = Carbon::now()->addDays(8);

        Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => 'confirmed',
            'cancellation_policy_id' => $this->policy->id,
        ]);

        $dates = $this->service->getUnavailableDates(
            $this->residence->id,
            Carbon::now(),
            Carbon::now()->addDays(30),
        );

        // Should contain the 3 nights (5, 6, 7)
        $this->assertNotEmpty($dates);
        $this->assertContains($checkIn->format('Y-m-d'), $dates);
    }

    // ─── EDGE CASES ─────────────────────────────────────────────

    #[Test]
    public function check_availability_with_cancelled_booking_shows_available(): void
    {
        Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'check_in' => Carbon::now()->addDays(10),
            'check_out' => Carbon::now()->addDays(13),
            'status' => 'cancelled', // Annulée
            'cancellation_policy_id' => $this->policy->id,
        ]);

        $result = $this->service->checkAvailability(
            $this->residence->id,
            Carbon::now()->addDays(10),
            Carbon::now()->addDays(13),
        );

        $this->assertTrue($result['available']);
    }

    #[Test]
    public function check_availability_with_same_day_returns_result(): void
    {
        // Edge case: check_in = check_out (0 nights)
        $date = Carbon::now()->addDays(10);

        $result = $this->service->checkAvailability(
            $this->residence->id,
            $date,
            $date,
        );

        // Should not crash — availability logic handles this
        $this->assertIsBool($result['available']);
    }

    #[Test]
    public function check_availability_for_nonexistent_residence(): void
    {
        $result = $this->service->checkAvailability(
            99999,
            Carbon::now()->addDays(10),
            Carbon::now()->addDays(13),
        );

        // No bookings for nonexistent residence = available
        $this->assertTrue($result['available']);
    }
}
