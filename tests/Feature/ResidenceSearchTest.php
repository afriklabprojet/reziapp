<?php

namespace Tests\Feature;

use App\Models\Residence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResidenceSearchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_search_residences_within_radius(): void
    {
        // Créer des résidences à différentes distances
        // Centre: Cocody (5.3600, -4.0083)
        $nearResidence = Residence::factory()->create([
            'name' => 'XNearResidenceUnique',
            'latitude' => 5.3650, // ~550m (dans limite 2km)
            'longitude' => -4.0100,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $farResidence = Residence::factory()->create([
            'name' => 'XFarResidenceUnique',
            'latitude' => 5.5000, // ~15km
            'longitude' => -4.2000,
            'status' => 'approved',
            'is_available' => true,
        ]);

        $response = $this->get(route('residences.search', [
            'latitude' => 5.3600,
            'longitude' => -4.0083,
            'radius' => 5000, // 5km en mètres (validation: min:100)
        ]));

        $response->assertStatus(200);
        $response->assertSee($nearResidence->name);
        $response->assertDontSee($farResidence->name);
    }

    #[Test]
    public function only_approved_residences_appear_in_search(): void
    {
        // Noms uniques pour éviter les collisions avec le contenu de la page
        $approvedResidence = Residence::factory()->create([
            'name' => 'XApprovedUniqueRes',
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3600,
            'longitude' => -4.0083,
        ]);

        $pendingResidence = Residence::factory()->create([
            'name' => 'XPendingUniqueRes',
            'status' => 'pending',
            'is_available' => true,
            'latitude' => 5.3650,
            'longitude' => -4.0100,
        ]);

        $rejectedResidence = Residence::factory()->create([
            'name' => 'XRejectedUniqueRes',
            'status' => 'rejected',
            'is_available' => true,
            'latitude' => 5.3620,
            'longitude' => -4.0090,
        ]);

        $response = $this->get(route('residences.search', [
            'latitude' => 5.3600,
            'longitude' => -4.0083,
            'radius' => 5000,
        ]));

        $response->assertStatus(200);
        $response->assertSee($approvedResidence->name);
        $response->assertDontSee($pendingResidence->name);
        $response->assertDontSee($rejectedResidence->name);
    }

    #[Test]
    public function can_filter_by_price_range(): void
    {
        $cheapResidence = Residence::factory()->create([
            'price_per_month' => 100000,
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3600,
            'longitude' => -4.0083,
        ]);

        $expensiveResidence = Residence::factory()->create([
            'price_per_month' => 500000,
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3650,
            'longitude' => -4.0100,
        ]);

        $response = $this->get(route('residences.search', [
            'latitude' => 5.3600,
            'longitude' => -4.0083,
            'radius' => 5000,
            'min_price' => 80000,
            'max_price' => 200000,
        ]));

        $response->assertStatus(200);
        $response->assertSee($cheapResidence->name);
        $response->assertDontSee($expensiveResidence->name);
    }

    #[Test]
    public function can_filter_by_type(): void
    {
        $villa = Residence::factory()->create([
            'type' => 'villa',
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3600,
            'longitude' => -4.0083,
        ]);

        $apartment = Residence::factory()->create([
            'type' => 'apartment',
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3650,
            'longitude' => -4.0100,
        ]);

        $response = $this->get(route('residences.search', [
            'latitude' => 5.3600,
            'longitude' => -4.0083,
            'radius' => 5000,
            'type' => 'villa',
        ]));

        $response->assertStatus(200);
        $response->assertSee($villa->name);
        $response->assertDontSee($apartment->name);
    }

    #[Test]
    public function can_filter_by_bedrooms_and_bathrooms(): void
    {
        $smallResidence = Residence::factory()->create([
            'bedrooms' => 1,
            'bathrooms' => 1,
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3600,
            'longitude' => -4.0083,
        ]);

        $largeResidence = Residence::factory()->create([
            'bedrooms' => 4,
            'bathrooms' => 3,
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3650,
            'longitude' => -4.0100,
        ]);

        $response = $this->get(route('residences.search', [
            'latitude' => 5.3600,
            'longitude' => -4.0083,
            'radius' => 5000,
            'bedrooms' => 3, // Minimum 3 chambres
        ]));

        $response->assertStatus(200);
        $response->assertDontSee($smallResidence->name);
        $response->assertSee($largeResidence->name);
    }

    #[Test]
    public function search_without_coordinates_returns_all_residences(): void
    {
        $residence = Residence::factory()->create([
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3600,
            'longitude' => -4.0083,
        ]);

        // Sans coordonnées, la recherche devrait afficher toutes les résidences
        $response = $this->get(route('residences.search'));

        $response->assertStatus(200);
        $response->assertSee($residence->name);
    }

    #[Test]
    public function api_search_returns_json(): void
    {
        $residence = Residence::factory()->create([
            'status' => 'approved',
            'is_available' => true,
            'latitude' => 5.3600,
            'longitude' => -4.0083,
        ]);

        $response = $this->getJson(route('api.residences.search', [
            'latitude' => 5.3600,
            'longitude' => -4.0083,
            'radius' => 5000, // 5000 mètres (min:100)
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'location',
                    'pricing',
                    'features',
                ],
            ],
            'meta',
            'links',
        ]);
    }
}
