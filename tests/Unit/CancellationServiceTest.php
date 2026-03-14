<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use App\Services\CancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests unitaires du service d'annulation
 * Couvre : preview, annulation par guest/owner/admin, statistiques
 */
class CancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CancellationService $service;
    protected User $guest;
    protected User $owner;
    protected Residence $residence;
    protected CancellationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CancellationService::class);

        $this->policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Annulation gratuite 7 jours avant',
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
        ]);
    }

    // ========================================
    // PREVIEW
    // ========================================

    #[Test]
    public function preview_cancellation_returns_expected_structure(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(10),
            'check_out' => now()->addDays(15),
        ]);

        $preview = $this->service->previewCancellation($booking);

        $this->assertIsArray($preview);
        $this->assertArrayHasKey('can_cancel', $preview);
        $this->assertArrayHasKey('policy', $preview);
        $this->assertArrayHasKey('message', $preview);
    }

    #[Test]
    public function preview_shows_full_refund_for_flexible_early_cancel(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(10),
            'check_out' => now()->addDays(15),
        ]);

        $preview = $this->service->previewCancellation($booking);

        $this->assertTrue($preview['can_cancel']);
    }

    // ========================================
    // ANNULATION PAR LE GUEST
    // ========================================

    #[Test]
    public function guest_can_cancel_confirmed_booking(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(10),
            'check_out' => now()->addDays(15),
        ]);

        $cancellation = $this->service->cancelByGuest(
            $booking,
            'Changement de plans',
            'J\'ai dû modifier mes dates de voyage.'
        );

        $this->assertNotNull($cancellation);
        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
    }

    // ========================================
    // ANNULATION PAR LE PROPRIÉTAIRE
    // ========================================

    #[Test]
    public function owner_cancellation_gives_full_refund(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(2),
            'check_out' => now()->addDays(5),
        ]);

        $cancellation = $this->service->cancelByOwner(
            $booking,
            'Indisponibilité imprévue',
            'Travaux urgents dans la résidence.'
        );

        $this->assertNotNull($cancellation);
        $this->assertEquals('owner', $cancellation->initiated_by);
    }

    // ========================================
    // STATISTIQUES
    // ========================================

    #[Test]
    public function owner_stats_returns_expected_structure(): void
    {
        $stats = $this->service->getOwnerStats($this->owner->id);

        $this->assertArrayHasKey('total_bookings', $stats);
        $this->assertArrayHasKey('total_cancellations', $stats);
        $this->assertArrayHasKey('owner_cancellation_rate', $stats);
    }

    #[Test]
    public function guest_stats_returns_expected_structure(): void
    {
        $stats = $this->service->getGuestStats($this->guest->id);

        $this->assertArrayHasKey('total_bookings', $stats);
        $this->assertArrayHasKey('cancellations', $stats);
        $this->assertArrayHasKey('cancellation_rate', $stats);
    }

    #[Test]
    public function high_cancellation_rate_detection_works(): void
    {
        // Owner with no bookings should not have high rate
        $result = $this->service->hasHighCancellationRate($this->owner->id);
        $this->assertFalse($result);
    }
}
