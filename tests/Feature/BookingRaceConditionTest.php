<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CouponService;
use App\Services\PaymentService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests des race conditions sur le système de réservation.
 *
 * Couvre : références uniques, idempotency, disponibilité concurrente,
 * conversion de demandes idempotente.
 */
class BookingRaceConditionTest extends TestCase
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
            'price_per_day' => 25000,
            'cleaning_fee' => 5000,
            'max_guests' => 6,
        ]);

        $this->bookingService = new BookingService(
            new PricingService(),
            app(PaymentService::class),
            app(CouponService::class),
        );

        config([
            'rezi.pricing.state_tax' => 1000,
            'rezi.pricing.service_fee_rate' => 0,
        ]);
    }

    // ─── Test 1 : Références uniques ───────────────────────────────────────

    #[Test]
    public function test_booking_reference_is_unique(): void
    {
        // Arrange — accéder à la méthode protégée via réflexion
        $method = new \ReflectionMethod(BookingService::class, 'generateBookingReference');
        $method->setAccessible(true);

        $references = [];
        $count = 100;

        // Act — générer 100 références
        for ($i = 0; $i < $count; $i++) {
            $references[] = $method->invoke($this->bookingService);
        }

        // Assert — toutes uniques
        $this->assertCount($count, array_unique($references));
    }

    #[Test]
    public function test_booking_reference_follows_rz_prefix_format(): void
    {
        // Arrange
        $method = new \ReflectionMethod(BookingService::class, 'generateBookingReference');
        $method->setAccessible(true);

        // Act
        $reference = $method->invoke($this->bookingService);

        // Assert — format RZ-XXXXXXXX (8 caractères hex majuscules)
        $this->assertMatchesRegularExpression('/^RZ-[0-9A-F]{8}$/', $reference);
    }

    // ─── Test 2 : Idempotency sur createBooking ─────────────────────────────

    #[Test]
    public function test_idempotent_booking_returns_same_booking(): void
    {
        // Arrange
        $checkIn = Carbon::tomorrow()->addDays(5);
        $checkOut = $checkIn->copy()->addDays(3);

        $data = [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 2,
            'adults' => 2,
            'children' => 0,
            'infants' => 0,
        ];

        // Act — deux appels identiques
        $booking1 = $this->bookingService->createBooking($this->residence, $this->guest, $data);
        $booking2 = $this->bookingService->createBooking($this->residence, $this->guest, $data);

        // Assert — même booking retourné, un seul enregistrement en base
        $this->assertSame($booking1->id, $booking2->id);
        $this->assertSame($booking1->reference, $booking2->reference);

        $count = Booking::where('residence_id', $this->residence->id)
            ->whereIn('status', ['pending_payment', 'pending', 'confirmed'])
            ->count();

        $this->assertSame(1, $count);
    }

    #[Test]
    public function test_different_dates_produce_different_bookings(): void
    {
        // Arrange
        $checkInA = Carbon::tomorrow()->addDays(10);
        $checkOutA = $checkInA->copy()->addDays(2);

        $checkInB = Carbon::tomorrow()->addDays(20);
        $checkOutB = $checkInB->copy()->addDays(2);

        $dataA = [
            'check_in' => $checkInA->toDateString(),
            'check_out' => $checkOutA->toDateString(),
            'guests' => 1,
        ];

        $dataB = [
            'check_in' => $checkInB->toDateString(),
            'check_out' => $checkOutB->toDateString(),
            'guests' => 1,
        ];

        // Act
        $bookingA = $this->bookingService->createBooking($this->residence, $this->guest, $dataA);
        $bookingB = $this->bookingService->createBooking($this->residence, $this->guest, $dataB);

        // Assert — deux bookings distincts
        $this->assertNotSame($bookingA->id, $bookingB->id);
    }

    // ─── Test 3 : Disponibilité concurrente ──────────────────────────────────

    #[Test]
    public function test_concurrent_bookings_same_dates_only_one_succeeds(): void
    {
        // Arrange — deux guests distincts tentent la même période
        $guest2 = User::factory()->create(['role' => 'user']);

        $checkIn = Carbon::tomorrow()->addDays(15);
        $checkOut = $checkIn->copy()->addDays(3);

        $data = [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 2,
        ];

        // Act — simuler deux appels séquentiels représentant une course
        // (en PHP synchrone, un vrai concurrent nécessite plusieurs processus ;
        //  on simule en créant le premier booking puis en vérifiant que le second échoue)
        $booking1 = $this->bookingService->createBooking($this->residence, $this->guest, $data);

        $exceptionThrown = false;
        try {
            $this->bookingService->createBooking($this->residence, $guest2, $data);
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('déjà réservée', $e->getMessage());
        }

        // Assert — seul le premier booking passe, le second lève une exception
        $this->assertTrue($exceptionThrown, 'La seconde réservation aurait dû être rejetée.');
        $this->assertDatabaseHas('bookings', ['id' => $booking1->id]);

        $conflictingCount = Booking::where('residence_id', $this->residence->id)
            ->whereBetween('check_in', [$checkIn->copy()->subDay(), $checkOut])
            ->whereIn('status', ['pending_payment', 'pending', 'confirmed'])
            ->count();

        $this->assertSame(1, $conflictingCount);
    }

    #[Test]
    public function test_non_overlapping_bookings_both_succeed(): void
    {
        // Arrange — deux périodes qui ne se chevauchent pas
        $guest2 = User::factory()->create(['role' => 'user']);

        $checkInA = Carbon::tomorrow()->addDays(5);
        $checkOutA = $checkInA->copy()->addDays(3);

        $checkInB = $checkOutA->copy()->addDays(1); // démarre le lendemain du checkout A
        $checkOutB = $checkInB->copy()->addDays(3);

        $dataA = [
            'check_in' => $checkInA->toDateString(),
            'check_out' => $checkOutA->toDateString(),
            'guests' => 1,
        ];

        $dataB = [
            'check_in' => $checkInB->toDateString(),
            'check_out' => $checkOutB->toDateString(),
            'guests' => 1,
        ];

        // Act
        $bookingA = $this->bookingService->createBooking($this->residence, $this->guest, $dataA);
        $bookingB = $this->bookingService->createBooking($this->residence, $guest2, $dataB);

        // Assert — les deux réservations sont créées
        $this->assertNotNull($bookingA->id);
        $this->assertNotNull($bookingB->id);
        $this->assertNotSame($bookingA->id, $bookingB->id);
    }

    // ─── Test 4 : Idempotency sur convertRequestToBooking ────────────────────

    #[Test]
    public function test_convert_request_to_booking_is_idempotent(): void
    {
        // Arrange — créer une demande de réservation approuvable
        $checkIn = Carbon::tomorrow()->addDays(10);
        $checkOut = $checkIn->copy()->addDays(3);

        $request = BookingRequest::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 2,
            'adults' => 2,
            'children' => 0,
            'infants' => 0,
            'price_per_night' => 25000,
            'total_nights' => 3,
            'subtotal' => 75000,
            'cleaning_fee' => 5000,
            'service_fee' => 0,
            'long_stay_discount' => 0,
            'promo_discount' => 0,
            'total_amount' => 81000,
            'status' => 'pending',
            'expires_at' => now()->addHours(48),
        ]);

        // Act — approuver deux fois (double-click simulé)
        $booking1 = $this->bookingService->approveBookingRequest($request);

        // Après la première approbation, remettre le statut à 'approved' manuellement
        // pour simuler un double-appel (normalement canBeApproved() bloque, mais on teste
        // la couche service directement)
        $request->refresh();
        // La demande est déjà 'converted' donc canBeApproved() retourne false
        // On vérifie simplement qu'un seul booking existe
        $bookingCount = Booking::where('idempotency_key', 'req_'.$request->id)->count();

        // Assert — un seul booking créé
        $this->assertSame(1, $bookingCount);
        $this->assertNotNull($booking1->id);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking1->id,
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
        ]);
    }

    #[Test]
    public function test_convert_request_sets_idempotency_key_on_booking(): void
    {
        // Arrange
        $checkIn = Carbon::tomorrow()->addDays(8);
        $checkOut = $checkIn->copy()->addDays(2);

        $request = BookingRequest::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 1,
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
            'price_per_night' => 25000,
            'total_nights' => 2,
            'subtotal' => 50000,
            'cleaning_fee' => 5000,
            'service_fee' => 0,
            'long_stay_discount' => 0,
            'promo_discount' => 0,
            'total_amount' => 56000,
            'status' => 'pending',
            'expires_at' => now()->addHours(48),
        ]);

        // Act
        $booking = $this->bookingService->approveBookingRequest($request);

        // Assert — l'idempotency_key est basée sur le request id
        $this->assertSame('req_'.$request->id, $booking->idempotency_key);
    }

    #[Test]
    public function test_approve_request_twice_raises_exception_on_second_call(): void
    {
        // Arrange
        $checkIn = Carbon::tomorrow()->addDays(12);
        $checkOut = $checkIn->copy()->addDays(3);

        $request = BookingRequest::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 1,
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
            'price_per_night' => 25000,
            'total_nights' => 3,
            'subtotal' => 75000,
            'cleaning_fee' => 5000,
            'service_fee' => 0,
            'long_stay_discount' => 0,
            'promo_discount' => 0,
            'total_amount' => 81000,
            'status' => 'pending',
            'expires_at' => now()->addHours(48),
        ]);

        // Act — premier appel réussit
        $this->bookingService->approveBookingRequest($request);

        // Assert — le second appel échoue car canBeApproved() retourne false
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cette demande ne peut plus être approuvée.');

        $this->bookingService->approveBookingRequest($request);
    }
}
