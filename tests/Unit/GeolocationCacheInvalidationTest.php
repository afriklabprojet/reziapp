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

/**
 * Tests for the cache key registry pattern in GeolocationService.
 *
 * Verifies that invalidateCache() correctly clears all registered keys
 * for a geohash zone without relying on wildcard Cache::forget (which
 * only works with Redis/Memcached, not the database or file drivers).
 */
class GeolocationCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private GeolocationService $service;

    private User $owner;

    // Abidjan – Cocody center
    private float $lat = 5.3477;

    private float $lng = -3.9892;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new GeolocationService();
        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
    }

    // -------------------------------------------------------------------------
    // Registry population
    // -------------------------------------------------------------------------

    #[Test]
    public function search_registers_its_cache_key_in_the_geohash_registry(): void
    {
        // Act: prime the cache via search
        $this->service->search($this->lat, $this->lng, 300);

        // Assert: registry entry exists and contains at least one key
        $geohash = $this->resolveGeohash($this->lat, $this->lng);
        $registryKey = "geo:keys:{$geohash}";

        $keys = Cache::get($registryKey, []);

        $this->assertNotEmpty($keys, 'Registry should contain at least one key after search()');
        $this->assertContains(
            $this->findKeyMatching($keys, 'geo:search:'),
            $keys,
            'Registry should include the geo:search key',
        );
    }

    #[Test]
    public function findNearby_registers_its_cache_key(): void
    {
        $this->service->findNearby($this->lat, $this->lng, 300);

        $geohash = $this->resolveGeohash($this->lat, $this->lng);
        $keys = Cache::get("geo:keys:{$geohash}", []);

        $nearbyKey = $this->findKeyMatching($keys, 'geo:nearby:');
        $this->assertNotNull($nearbyKey, 'Registry should include the geo:nearby key');
    }

    #[Test]
    public function countNearby_registers_its_cache_key(): void
    {
        $this->service->countNearby($this->lat, $this->lng, 300);

        $geohash = $this->resolveGeohash($this->lat, $this->lng);
        $keys = Cache::get("geo:keys:{$geohash}", []);

        $countKey = $this->findKeyMatching($keys, 'geo:count:');
        $this->assertNotNull($countKey, 'Registry should include the geo:count key');
    }

    #[Test]
    public function getRadiusCounts_registers_its_cache_key(): void
    {
        $this->service->getRadiusCounts($this->lat, $this->lng);

        $geohash = $this->resolveGeohash($this->lat, $this->lng);
        $keys = Cache::get("geo:keys:{$geohash}", []);

        $radiusKey = $this->findKeyMatching($keys, 'geo:radius_counts:');
        $this->assertNotNull($radiusKey, 'Registry should include the geo:radius_counts key');
    }

    #[Test]
    public function getZoneStatistics_registers_its_cache_key(): void
    {
        $this->service->getZoneStatistics($this->lat, $this->lng, 300);

        $geohash = $this->resolveGeohash($this->lat, $this->lng);
        $keys = Cache::get("geo:keys:{$geohash}", []);

        $statsKey = $this->findKeyMatching($keys, 'geo:zone_stats:');
        $this->assertNotNull($statsKey, 'Registry should include the geo:zone_stats key');
    }

    #[Test]
    public function repeated_calls_do_not_duplicate_keys_in_registry(): void
    {
        $this->service->countNearby($this->lat, $this->lng, 300);
        $this->service->countNearby($this->lat, $this->lng, 300);
        $this->service->countNearby($this->lat, $this->lng, 300);

        $geohash = $this->resolveGeohash($this->lat, $this->lng);
        $keys = Cache::get("geo:keys:{$geohash}", []);

        $countKeys = array_filter($keys, fn ($k) => str_contains($k, 'geo:count:'));

        $this->assertCount(1, $countKeys, 'Duplicate keys should not accumulate in the registry');
    }

    // -------------------------------------------------------------------------
    // Invalidation
    // -------------------------------------------------------------------------

    #[Test]
    public function invalidateCache_clears_all_registered_keys_for_the_geohash(): void
    {
        // Arrange: prime multiple cache entries for the same geohash
        $this->service->search($this->lat, $this->lng, 300);
        $this->service->countNearby($this->lat, $this->lng, 300);
        $this->service->getRadiusCounts($this->lat, $this->lng);
        $this->service->getZoneStatistics($this->lat, $this->lng, 300);

        $geohash = $this->resolveGeohash($this->lat, $this->lng);
        $registryKey = "geo:keys:{$geohash}";
        $keysBefore = Cache::get($registryKey, []);

        $this->assertNotEmpty($keysBefore, 'Pre-condition: registry must have keys before invalidation');

        // Verify all keys are actually in the cache store
        foreach ($keysBefore as $key) {
            $this->assertTrue(Cache::has($key), "Pre-condition: key {$key} should be cached");
        }

        // Act
        $this->service->invalidateCache($this->lat, $this->lng);

        // Assert: every previously registered key is gone
        foreach ($keysBefore as $key) {
            $this->assertFalse(Cache::has($key), "Key {$key} should have been evicted");
        }

        // Assert: registry itself is also gone
        $this->assertFalse(Cache::has($registryKey), 'Registry key itself should be evicted');
    }

    #[Test]
    public function invalidateCache_clears_geo_trending_areas(): void
    {
        Cache::put('geo:trending_areas', ['some' => 'data'], 3600);

        $this->service->invalidateCache($this->lat, $this->lng);

        $this->assertFalse(Cache::has('geo:trending_areas'));
    }

    #[Test]
    public function invalidateCache_is_idempotent_when_registry_is_empty(): void
    {
        // No prior search calls — registry is empty
        $this->expectNotToPerformAssertions(); // Should not throw

        $this->service->invalidateCache($this->lat, $this->lng);
    }

    // -------------------------------------------------------------------------
    // Post-invalidation freshness
    // -------------------------------------------------------------------------

    #[Test]
    public function search_returns_fresh_data_after_invalidation(): void
    {
        // Arrange: create a residence and prime the cache
        $residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $firstResult = $this->service->search($this->lat, $this->lng, 300);
        $this->assertTrue($firstResult['total'] > 0, 'Pre-condition: at least one result');

        // Make the residence unavailable while the cache is still hot
        $residence->update(['is_available' => false]);

        // Without invalidation, cache still returns stale data
        $cachedResult = $this->service->search($this->lat, $this->lng, 300);
        $this->assertTrue($cachedResult['cached'], 'Result should come from cache');
        $this->assertSame($firstResult['total'], $cachedResult['total'], 'Stale cache returns old count');

        // Act: invalidate
        $this->service->invalidateCache($this->lat, $this->lng);

        // Assert: fresh query returns updated count
        $freshResult = $this->service->search($this->lat, $this->lng, 300);
        $this->assertFalse($freshResult['cached'], 'Result should NOT come from cache after invalidation');
        $this->assertSame(0, $freshResult['total'], 'Fresh result should reflect the update');
    }

    #[Test]
    public function count_nearby_returns_fresh_data_after_invalidation(): void
    {
        // Arrange
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $countBefore = $this->service->countNearby($this->lat, $this->lng, 300);
        $this->assertSame(1, $countBefore);

        // Add a second residence while cache is still hot
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3481,
            'longitude' => -3.9891,
            'status' => 'approved',
            'is_available' => true,
        ]);

        // Without invalidation, cached count is still 1
        $cachedCount = $this->service->countNearby($this->lat, $this->lng, 300);
        $this->assertSame(1, $cachedCount, 'Stale cache should still return 1');

        // Act
        $this->service->invalidateCache($this->lat, $this->lng);

        // Assert
        $freshCount = $this->service->countNearby($this->lat, $this->lng, 300);
        $this->assertSame(2, $freshCount, 'Fresh count should include the new residence');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Replicate the private getGeohash() logic to build expected registry keys.
     */
    private function resolveGeohash(float $lat, float $lng): string
    {
        return round($lat, 2).'_'.round($lng, 2);
    }

    /**
     * Find the first key in the list that contains the given substring.
     */
    private function findKeyMatching(array $keys, string $substring): ?string
    {
        foreach ($keys as $key) {
            if (str_contains($key, $substring)) {
                return $key;
            }
        }

        return null;
    }
}
