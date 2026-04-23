<?php

namespace Tests\Unit;

use App\Models\Residence;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests unitaires pour le PricingService.
 *
 * Couvre : tarification intelligente, frais, taxes, réductions long séjour,
 * calcul part propriétaire, formatage prix, edge cases.
 */
class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PricingService();

        // Fixer les taux pour des tests déterministes
        config([
            'rezi.pricing.state_tax' => 1000,
            'rezi.pricing.owner_commission_rate' => 0.10,
        ]);
    }

    // ─── Tarification de base ───
    #[Test]
    public function it_calculates_basic_price_for_short_stay(): void
    {
        $residence = Residence::factory()->create([
            'price_per_day' => 25000,
            'price_per_week' => null,
            'price_per_month' => 0,
            'status' => 'active',
            'is_available' => true,
        ]);

        $checkIn = Carbon::parse('2026-03-01');
        $checkOut = Carbon::parse('2026-03-04'); // 3 nuits

        $result = $this->service->calculatePrice($residence, $checkIn, $checkOut);

        $this->assertEquals(3, $result['nights']);
        $this->assertEquals(25000, $result['base_price_per_night']);
        $this->assertEquals(75000, $result['subtotal']); // 3 × 25000
        $this->assertEquals(0, $result['cleaning_fee']); // pas de colonne cleaning_fee sur residences
        $this->assertEquals('XOF', $result['currency']);
        $this->assertNotEmpty($result['nightly_breakdown']);
        $this->assertCount(3, $result['nightly_breakdown']);
    }
    #[Test]
    public function it_uses_weekly_rate_for_7_plus_nights(): void
    {
        $residence = Residence::factory()->create([
            'price_per_day' => 25000,
            'price_per_week' => 140000, // 20000/nuit
            'price_per_month' => 0,
            'status' => 'active',
            'is_available' => true,
        ]);

        $checkIn = Carbon::parse('2026-03-01');
        $checkOut = Carbon::parse('2026-03-08'); // 7 nuits

        $result = $this->service->calculatePrice($residence, $checkIn, $checkOut);

        $this->assertEquals(7, $result['nights']);
        $this->assertEquals(20000, $result['base_price_per_night']); // 140000 / 7
        $this->assertEquals(140000, $result['subtotal']);
    }
    #[Test]
    public function it_uses_monthly_rate_for_30_plus_nights(): void
    {
        $residence = Residence::factory()->create([
            'price_per_day' => 25000,
            'price_per_week' => 140000,
            'price_per_month' => 450000, // 15000/nuit
            'status' => 'active',
            'is_available' => true,
        ]);

        $checkIn = Carbon::parse('2026-03-01');
        $checkOut = Carbon::parse('2026-03-31'); // 30 nuits

        $result = $this->service->calculatePrice($residence, $checkIn, $checkOut);

        $this->assertEquals(30, $result['nights']);
        $this->assertEquals(15000, $result['base_price_per_night']); // 450000 / 30
        $this->assertEquals(450000, $result['subtotal']);
    }

    // ─── Frais et taxes ───
    #[Test]
    public function it_calculates_service_fee_and_taxes_correctly(): void
    {
        $residence = Residence::factory()->create([
            'price_per_day' => 50000,
            'status' => 'active',
            'is_available' => true,
        ]);

        $checkIn = Carbon::parse('2026-03-01');
        $checkOut = Carbon::parse('2026-03-03'); // 2 nuits

        $result = $this->service->calculatePrice($residence, $checkIn, $checkOut);

        $subtotal = 100000; // 2 × 50000
        $cleaningFee = 0;
        $serviceFee = 0; // commission prélevée sur le propriétaire, pas sur le locataire
        $taxes = 1000; // taxe d'État fixe
        $total = $subtotal + $cleaningFee + $taxes; // 101000

        $this->assertEquals($subtotal, $result['subtotal']);
        $this->assertEquals($serviceFee, $result['service_fee']);
        $this->assertEquals($taxes, $result['taxes']);
        $this->assertEquals(0, $result['tax_rate']);
        $this->assertEquals($total, $result['total_amount']);
    }

    // ─── Calcul part propriétaire ───
    #[Test]
    public function it_calculates_owner_earnings_correctly(): void
    {
        $priceBreakdown = [
            'subtotal' => 150000,
            'total_discount' => 10000,
            'cleaning_fee' => 5000,
        ];

        $result = $this->service->calculateOwnerEarnings($priceBreakdown);

        // owner_subtotal = 150000 - 10000 + 5000 = 145000
        $this->assertEquals(145000, $result['owner_subtotal']);

        // commission = 145000 × 0.10 = 14500
        $this->assertEquals(14500, $result['rezi_commission']);
        $this->assertEquals(10, $result['rezi_commission_rate']);

        // earnings = 145000 - 14500 = 130500
        $this->assertEquals(130500, $result['owner_earnings']);
    }

    // ─── Edge cases ───
    #[Test]
    public function it_throws_on_zero_nights(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $residence = Residence::factory()->create([
            'price_per_day' => 25000,
            'status' => 'active',
            'is_available' => true,
        ]);

        $date = Carbon::parse('2026-03-01');
        $this->service->calculatePrice($residence, $date, $date);
    }
    #[Test]
    public function it_throws_on_checkout_before_checkin(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $residence = Residence::factory()->create([
            'price_per_day' => 25000,
            'status' => 'active',
            'is_available' => true,
        ]);

        $this->service->calculatePrice(
            $residence,
            Carbon::parse('2026-03-05'),
            Carbon::parse('2026-03-01'),
        );
    }
    #[Test]
    public function it_falls_back_to_weekly_rate_when_no_daily(): void
    {
        $residence = Residence::factory()->create([
            'price_per_day' => null,
            'price_per_week' => 105000, // 15000/nuit
            'price_per_month' => 0,
            'status' => 'active',
            'is_available' => true,
        ]);

        $result = $this->service->calculatePrice(
            $residence,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-04'), // 3 nuits (< 7, mais no daily)
        );

        $this->assertEquals(15000, $result['base_price_per_night']);
    }
    #[Test]
    public function it_falls_back_to_monthly_rate_when_no_daily_or_weekly(): void
    {
        $residence = Residence::factory()->create([
            'price_per_day' => null,
            'price_per_week' => null,
            'price_per_month' => 300000, // 10000/nuit
            'status' => 'active',
            'is_available' => true,
        ]);

        $result = $this->service->calculatePrice(
            $residence,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-04'),
        );

        $this->assertEquals(10000, $result['base_price_per_night']);
    }
    #[Test]
    public function it_returns_zero_price_when_no_rates_set(): void
    {
        $residence = Residence::factory()->create([
            'price_per_day' => null,
            'price_per_week' => null,
            'price_per_month' => 0,
            'status' => 'active',
            'is_available' => true,
        ]);

        $result = $this->service->calculatePrice(
            $residence,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-04'),
        );

        $this->assertEquals(0, $result['base_price_per_night']);
        $this->assertEquals(0, $result['subtotal']);
    }

    // ─── Formatage ───
    #[Test]
    public function it_formats_price_correctly(): void
    {
        $this->assertEquals('150 000 FCFA', PricingService::formatPrice(150000));
        $this->assertEquals('0 FCFA', PricingService::formatPrice(0));
        $this->assertEquals('1 000 000 FCFA', PricingService::formatPrice(1000000));
    }

    // ─── Structure de retour ───
    #[Test]
    public function it_returns_all_expected_keys(): void
    {
        $residence = Residence::factory()->create([
            'price_per_day' => 25000,
            'status' => 'active',
            'is_available' => true,
        ]);

        $result = $this->service->calculatePrice(
            $residence,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-03'),
        );

        $expectedKeys = [
            'residence_id', 'check_in', 'check_out', 'nights', 'guests',
            'base_price_per_night', 'avg_price_per_night', 'subtotal',
            'cleaning_fee', 'service_fee', 'service_fee_rate',
            'long_stay_discount', 'long_stay_discount_info',
            'promo_discount', 'promo_code',
            'coupon_discount', 'coupon',
            'total_discount', 'taxes', 'tax_rate',
            'total_amount', 'currency',
            'nightly_breakdown', 'summary',
            'calculated_at', 'valid_until',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Missing key: {$key}");
        }
    }
    #[Test]
    public function it_includes_nightly_breakdown_with_correct_structure(): void
    {
        $residence = Residence::factory()->create([
            'price_per_day' => 30000,
            'status' => 'active',
            'is_available' => true,
        ]);

        $result = $this->service->calculatePrice(
            $residence,
            Carbon::parse('2026-03-10'),
            Carbon::parse('2026-03-13'),
        );

        $this->assertCount(3, $result['nightly_breakdown']);

        foreach ($result['nightly_breakdown'] as $night) {
            $this->assertArrayHasKey('date', $night);
            $this->assertArrayHasKey('price', $night);
            $this->assertArrayHasKey('is_special', $night);
        }

        $this->assertEquals('2026-03-10', $result['nightly_breakdown'][0]['date']);
        $this->assertEquals('2026-03-12', $result['nightly_breakdown'][2]['date']);
    }
    #[Test]
    public function it_sets_valid_until_30_minutes_in_future(): void
    {
        $residence = Residence::factory()->create([
            'price_per_day' => 25000,
            'status' => 'active',
            'is_available' => true,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-01 12:00:00'));

        $result = $this->service->calculatePrice(
            $residence,
            Carbon::parse('2026-03-05'),
            Carbon::parse('2026-03-07'),
        );

        $validUntil = Carbon::parse($result['valid_until']);
        $this->assertTrue($validUntil->isAfter(Carbon::now()->addMinutes(29)));
        $this->assertTrue($validUntil->isBefore(Carbon::now()->addMinutes(31)));

        Carbon::setTestNow(); // Reset
    }
}
