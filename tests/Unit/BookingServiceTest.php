<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\BlockedDate;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CouponService;
use App\Services\LoyaltyService;
use App\Services\PaymentService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests unitaires pour BookingService.
 *
 * Couvre : validation des dates, contraintes métier, calcul du split de paiement,
 * libération des dates à l'annulation.
 */
class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;
    private User $owner;
    private User $guest;
    private Residence $residence;
    private CancellationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Annulation flexible',
            'refund_rules' => [['days_before' => 7, 'refund_percent' => 100]],
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->guest = User::factory()->create(['role' => 'user']);

        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'cancellation_policy_id' => $this->policy->id,
            'status' => 'approved',
            'is_available' => true,
            'instant_book' => true,
            'price_per_day' => 20000,
            'cleaning_fee' => 5000,
            'max_guests' => 6,
        ]);

        $this->bookingService = new BookingService(
            new PricingService(app(LoyaltyService::class)),
            app(PaymentService::class),
            app(CouponService::class),
        );

        config([
            'rezi.pricing.state_tax' => 1000,
            'rezi.pricing.service_fee_rate' => 0,
        ]);
    }

    // ─── Validation des dates ────────────────────────────────────────────────

    #[Test]
    public function test_create_booking_validates_dates(): void
    {
        // Arrange — dates vides
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les dates de réservation sont obligatoires.');

        // Act
        $this->bookingService->createBooking($this->residence, $this->guest, []);
    }

    #[Test]
    public function test_create_booking_rejects_checkout_before_checkin(): void
    {
        // Arrange
        $checkIn = Carbon::tomorrow()->addDays(5);
        $checkOut = Carbon::tomorrow()->addDays(3); // avant le check-in

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de départ doit être après la date d\'arrivée.');

        // Act
        $this->bookingService->createBooking($this->residence, $this->guest, [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
        ]);
    }

    #[Test]
    public function test_create_booking_rejects_checkin_in_the_past(): void
    {
        // Arrange
        $checkIn = Carbon::yesterday();
        $checkOut = Carbon::tomorrow();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date d\'arrivée ne peut pas être dans le passé.');

        // Act
        $this->bookingService->createBooking($this->residence, $this->guest, [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
        ]);
    }

    #[Test]
    public function test_create_booking_rejects_stay_longer_than_365_days(): void
    {
        // Arrange
        $checkIn = Carbon::tomorrow()->addDays(2);
        $checkOut = $checkIn->copy()->addDays(366);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La durée maximale de réservation est de 365 jours.');

        // Act
        $this->bookingService->createBooking($this->residence, $this->guest, [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
        ]);
    }

    // ─── Contrainte métier : propriétaire ────────────────────────────────────

    #[Test]
    public function test_owner_cannot_book_own_residence(): void
    {
        // Arrange
        $checkIn = Carbon::tomorrow()->addDays(5);
        $checkOut = $checkIn->copy()->addDays(2);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vous ne pouvez pas réserver votre propre résidence.');

        // Act — le propriétaire tente de réserver sa propre résidence
        $this->bookingService->createBooking($this->residence, $this->owner, [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 1,
        ]);
    }

    // ─── Calcul du split de paiement ─────────────────────────────────────────

    #[Test]
    public function test_booking_calculates_payment_split_correctly(): void
    {
        // Arrange — check-in dans 40 jours (> 30j requis pour le split)
        $checkIn = Carbon::tomorrow()->addDays(40);
        $checkOut = $checkIn->copy()->addDays(3);

        $data = [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 2,
            'adults' => 2,
            'children' => 0,
            'infants' => 0,
            'payment_split' => true,
        ];

        // Act
        $booking = $this->bookingService->createBooking($this->residence, $this->guest, $data);

        // Assert — le split est activé, dépôt = 50% arrondi
        $this->assertTrue((bool) $booking->payment_split);
        $this->assertNotNull($booking->deposit_amount);
        $this->assertNotNull($booking->balance_amount);
        $this->assertNotNull($booking->balance_due_at);

        $expectedDeposit = (int) round($booking->total_amount * 0.5);
        $expectedBalance = (int) ($booking->total_amount - $expectedDeposit);

        $this->assertSame($expectedDeposit, (int) $booking->deposit_amount);
        $this->assertSame($expectedBalance, (int) $booking->balance_amount);

        // La date de solde doit être J-30 avant le check-in
        $expectedBalanceDue = $checkIn->copy()->subDays(30)->toDateString();
        $this->assertSame($expectedBalanceDue, $booking->balance_due_at->toDateString());
    }

    #[Test]
    public function test_booking_disables_split_when_checkin_within_30_days(): void
    {
        // Arrange — check-in dans 15 jours (< 30j, non éligible)
        $checkIn = Carbon::tomorrow()->addDays(15);
        $checkOut = $checkIn->copy()->addDays(3);

        $data = [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 1,
            'payment_split' => true, // demandé mais non éligible
        ];

        // Act
        $booking = $this->bookingService->createBooking($this->residence, $this->guest, $data);

        // Assert — split désactivé malgré la demande
        $this->assertFalse((bool) $booking->payment_split);
        $this->assertNull($booking->deposit_amount);
        $this->assertNull($booking->balance_amount);
    }

    // ─── Annulation et libération des dates ──────────────────────────────────

    #[Test]
    public function test_cancel_booking_unlocks_dates(): void
    {
        // Arrange — créer un booking puis simuler des BlockedDates
        $checkIn = Carbon::tomorrow()->addDays(5);
        $checkOut = $checkIn->copy()->addDays(3);

        $booking = Booking::factory()->create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'cancellation_policy_id' => $this->policy->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'total_amount' => 66000,
        ]);

        // Créer une BlockedDate associée à la réservation
        BlockedDate::create([
            'residence_id' => $this->residence->id,
            'start_date' => $checkIn->toDateString(),
            'end_date' => $checkOut->toDateString(),
            'reason' => 'booking',
        ]);

        $this->assertDatabaseHas('blocked_dates', [
            'residence_id' => $this->residence->id,
            'reason' => 'booking',
        ]);

        // Act
        $result = $this->bookingService->cancelBooking($booking, 'Changement de plans', 'user');

        // Assert — la BlockedDate a été supprimée
        $this->assertDatabaseMissing('blocked_dates', [
            'residence_id' => $this->residence->id,
            'start_date' => $checkIn->toDateString(),
            'end_date' => $checkOut->toDateString(),
            'reason' => 'booking',
        ]);

        // Le booking est annulé
        $this->assertStringStartsWith('cancelled_by_', $result['booking']->status);
        $this->assertNotNull($result['booking']->cancelled_at);
    }

    #[Test]
    public function test_cancel_booking_rejects_already_cancelled_booking(): void
    {
        // Arrange
        $booking = Booking::factory()->create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'cancellation_policy_id' => $this->policy->id,
            'status' => 'cancelled_by_user',
            'total_amount' => 50000,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cette réservation ne peut pas être annulée.');

        // Act
        $this->bookingService->cancelBooking($booking, 'Raison', 'user');
    }

    #[Test]
    public function test_cancel_booking_by_owner_returns_full_refund(): void
    {
        // Arrange
        $checkIn = Carbon::tomorrow()->addDays(5);
        $checkOut = $checkIn->copy()->addDays(3);

        $totalAmount = 66000;

        $booking = Booking::factory()->create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'cancellation_policy_id' => $this->policy->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'total_amount' => $totalAmount,
        ]);

        // Act — annulation par le propriétaire
        $result = $this->bookingService->cancelBooking($booking, 'Indisponibilité imprévue', 'owner');

        // Assert — remboursement total
        $this->assertSame((float) $totalAmount, $result['refund_amount']);
        $this->assertSame('cancelled_by_owner', $result['booking']->status);
    }

    // ─── Résidence non disponible ─────────────────────────────────────────────

    #[Test]
    public function test_create_booking_rejects_unavailable_residence(): void
    {
        // Arrange
        $unavailableResidence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'approved',
            'is_available' => false,
        ]);

        $checkIn = Carbon::tomorrow()->addDays(5);
        $checkOut = $checkIn->copy()->addDays(2);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cette résidence n\'est pas disponible à la réservation.');

        // Act
        $this->bookingService->createBooking($unavailableResidence, $this->guest, [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 1,
        ]);
    }
}
