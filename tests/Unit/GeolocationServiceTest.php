<?php

namespace Tests\Unit;

use App\Models\Residence;
use App\Models\User;
use App\Repositories\ResidenceRepository;
use App\Services\GeolocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeolocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GeolocationService $service;
    protected ResidenceRepository $repository;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(ResidenceRepository::class);
        $this->service = new GeolocationService($this->repository);
        $this->owner = User::factory()->create(['role' => 'owner']);
    }

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

        // Distance en mètres
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

    #[Test]
    public function validates_invalid_latitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Latitude > 90 est invalide
        $this->service->search(100.0, -3.9892, 300);
    }

    #[Test]
    public function validates_invalid_longitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Longitude < -180 est invalide
        $this->service->search(5.3477, -200.0, 300);
    }

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
}
