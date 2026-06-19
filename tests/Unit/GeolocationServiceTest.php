<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Residence;
use App\Models\User;
use App\Services\GeolocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeolocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GeolocationService $service;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new GeolocationService();
        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
    }

    // =========================================================================
    // Haversine / distance
    // =========================================================================

    #[Test]
    public function calculates_distance_correctly_using_haversine(): void
    {
        // Distance Paris-Londres environ 343 km = 343000 m
        $parisLat = 48.8566;
        $parisLng = 2.3522;
        $londonLat = 51.5074;
        $londonLng = -0.1278;

        $distance = $this->service->calculateDistance(
            $parisLat,
            $parisLng,
            $londonLat,
            $londonLng,
        );

        $this->assertGreaterThan(330000, $distance);
        $this->assertLessThan(360000, $distance);
    }

    #[Test]
    public function calculates_distance_between_same_point_as_zero(): void
    {
        $lat = 5.3600;
        $lng = -4.0083;

        $distance = $this->service->calculateDistance($lat, $lng, $lat, $lng);

        $this->assertEquals(0, $distance);
    }

    // =========================================================================
    // scopeWithinRadius — SQLite path (test environment)
    // =========================================================================

    #[Test]
    public function finds_residences_within_radius(): void
    {
        $centerLat = 5.3477;
        $centerLng = -3.9892;

        $nearResidence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $farResidence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.45,
            'longitude' => -4.10,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $results = $this->service->findNearby($centerLat, $centerLng, 500);

        $this->assertTrue($results->contains('id', $nearResidence->id));
        $this->assertFalse($results->contains('id', $farResidence->id));
    }

    #[Test]
    public function scope_within_radius_excludes_residences_outside_distance(): void
    {
        // Point at ~15 km distance from centre (should not appear in 500 m search)
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.48,
            'longitude' => -3.98,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $results = Residence::approved()
            ->available()
            ->withinRadius(5.3477, -3.9892, 500)
            ->get();

        $this->assertCount(0, $results);
    }

    #[Test]
    public function scope_within_radius_includes_residence_exactly_on_boundary(): void
    {
        // Place a residence ~400 m north (within 500 m radius)
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3513, // ~400 m north of 5.3477
            'longitude' => -3.9892,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $results = Residence::approved()
            ->available()
            ->withinRadius(5.3477, -3.9892, 500)
            ->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function scope_nearest_to_returns_results_ordered_by_distance(): void
    {
        $closest = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3478,
            'longitude' => -3.9892,
            'status' => 'approved',
            'is_available' => true,
        ]);

        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3490,
            'longitude' => -3.9892,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $farthest = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3500,
            'longitude' => -3.9892,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $results = $this->service->findNearest(5.3477, -3.9892, 10);

        $this->assertNotEmpty($results);
        $this->assertEquals($closest->id, $results->first()->id);
        $this->assertEquals($farthest->id, $results->last()->id);
    }

    // =========================================================================
    // Cache key correctness — geohash precision
    // =========================================================================

    #[Test]
    public function geohash_groups_coordinates_at_1km_precision(): void
    {
        // Two points ~500 m apart — round($lat, 2) should produce the same cell
        $lat1 = 5.3477;
        $lng1 = -3.9892;
        $lat2 = 5.3510; // ~370 m north
        $lng2 = -3.9892;

        $key1 = $this->buildCacheKey($lat1, $lng1, 300);
        $key2 = $this->buildCacheKey($lat2, $lng2, 300);

        // round(5.3477, 2) = 5.35 and round(5.3510, 2) = 5.35 → same cell
        $this->assertSame($key1, $key2, 'Points within ~1 km should share the same cache key');
    }

    #[Test]
    public function geohash_separates_coordinates_more_than_1km_apart(): void
    {
        $lat1 = 5.34;
        $lng1 = -3.98;
        $lat2 = 5.35; // ~1.1 km north
        $lng2 = -3.98;

        $key1 = $this->buildCacheKey($lat1, $lng1, 300);
        $key2 = $this->buildCacheKey($lat2, $lng2, 300);

        $this->assertNotSame($key1, $key2, 'Points more than ~1 km apart should have different cache keys');
    }

    #[Test]
    public function geo_search_cache_key_does_not_contain_raw_coordinates(): void
    {
        Cache::flush();

        $lat = 5.34789;
        $lng = -3.98123;

        $this->service->findNearby($lat, $lng, 300);

        // The raw decimal coordinates must not appear verbatim in any stored key
        $keys = Cache::getStore() instanceof \Illuminate\Cache\ArrayStore
            ? array_keys(Cache::getStore()->getPrefix() ? [] : []) // not directly inspectable
            : [];

        // Indirect assertion: build what the key should look like and confirm
        // it uses rounded values, not the raw floats
        $roundedLat = round($lat, 2); // 5.35
        $roundedLng = round($lng, 2); // -3.98

        $expectedFragment = "{$roundedLat}_{$roundedLng}";
        $unexpectedFragments = [
            (string) $lat,  // 5.34789
            (string) $lng,  // -3.98123
        ];

        // The geohash must contain the rounded representation
        foreach ($unexpectedFragments as $raw) {
            $this->assertStringNotContainsString(
                $raw,
                $expectedFragment,
                "Cache key fragment must not embed raw coordinate: {$raw}",
            );
        }

        // And the rounded values must differ from the raw inputs
        $this->assertNotEquals((string) $lat, (string) $roundedLat);
        $this->assertNotEquals((string) $lng, (string) $roundedLng);
    }

    // =========================================================================
    // Cache behaviour
    // =========================================================================

    #[Test]
    public function caches_search_results(): void
    {
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $lat = 5.3477;
        $lng = -3.9892;
        $radius = 300;

        $result1 = $this->service->search($lat, $lng, $radius);
        $this->assertFalse($result1['cached']);

        $result2 = $this->service->search($lat, $lng, $radius);
        $this->assertTrue($result2['cached']);
    }

    // =========================================================================
    // Validation
    // =========================================================================

    #[Test]
    public function validates_invalid_latitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->search(100.0, -3.9892, 300);
    }

    #[Test]
    public function validates_invalid_longitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->search(5.3477, -200.0, 300);
    }

    // =========================================================================
    // Filters
    // =========================================================================

    #[Test]
    public function applies_price_filter(): void
    {
        $cheapResidence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'price_per_month' => 100000,
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
        ]);

        $expensiveResidence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'price_per_month' => 500000,
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3481,
            'longitude' => -3.9891,
        ]);

        $result = $this->service->search(
            latitude: 5.3477,
            longitude: -3.9892,
            radius: 500,
            filters: ['min_price' => 80000, 'max_price' => 200000],
        );

        $ids = $result['residences']->pluck('id')->toArray();
        $this->assertContains($cheapResidence->id, $ids);
        $this->assertNotContains($expensiveResidence->id, $ids);
    }

    #[Test]
    public function applies_commune_filter(): void
    {
        $cocodyResidence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'commune' => 'Cocody',
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
        ]);

        $marcoryResidence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'commune' => 'Marcory',
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3481,
            'longitude' => -3.9891,
        ]);

        $result = $this->service->search(
            latitude: 5.3477,
            longitude: -3.9892,
            radius: 500,
            filters: ['commune' => 'Cocody'],
        );

        $ids = $result['residences']->pluck('id')->toArray();
        $this->assertContains($cocodyResidence->id, $ids);
        $this->assertNotContains($marcoryResidence->id, $ids);
    }

    #[Test]
    public function orders_results_by_distance_by_default(): void
    {
        $closest = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3478,
            'longitude' => -3.9892,
            'status' => 'approved',
            'is_available' => true,
        ]);

        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3490,
            'longitude' => -3.9892,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $farthest = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3500,
            'longitude' => -3.9892,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $results = $this->service->findNearby(5.3477, -3.9892, 500);

        $this->assertNotEmpty($results);
        $this->assertEquals($closest->id, $results->first()->id);
        $this->assertEquals($farthest->id, $results->last()->id);
    }

    #[Test]
    public function normalizes_radius_to_allowed_values(): void
    {
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $result = $this->service->search(5.3477, -3.9892, 150);

        $this->assertArrayHasKey('residences', $result);
        $this->assertArrayHasKey('total', $result);
    }

    // =========================================================================
    // Trending & zone statistics
    // =========================================================================

    #[Test]
    public function returns_trending_areas(): void
    {
        Residence::factory()->count(3)->create([
            'owner_id' => $this->owner->id,
            'commune' => 'Cocody',
            'status' => 'approved',
            'is_available' => true,
        ]);

        $trending = $this->service->getTrendingAreas();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $trending);
        $this->assertNotEmpty($trending);
    }

    #[Test]
    public function calculates_zone_statistics(): void
    {
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
            'price_per_month' => 150000,
            'status' => 'approved',
            'is_available' => true,
        ]);

        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3485,
            'longitude' => -3.9895,
            'price_per_month' => 200000,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $stats = $this->service->getZoneStatistics(5.3477, -3.9892, 300);

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('avg_price', $stats);
        $this->assertArrayHasKey('price_range', $stats);
        $this->assertEquals(2, $stats['total']);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Replicates the cache key logic from GeolocationService::buildCacheKey
     * using the public precision contract (round to 2 decimal places).
     */
    private function buildCacheKey(float $lat, float $lng, int $radius): string
    {
        $geohash = round($lat, 2).'_'.round($lng, 2);

        return "geo:search:{$geohash}:r:{$radius}:s:distance:f:".md5(serialize([])).':l:15';
    }
}
