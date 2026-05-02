<?php

namespace Tests\Feature\Api;

use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tests pour l'API de recherche géolocalisée
 *
 * Cœur de REZI : recherche géolocalisée avec formule Haversine
 * Zones couvertes : CI + Burkina Faso
 */
class GeoSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    // Endpoint API principal
    private const GEO_SEARCH_API = '/api/v1/geo/search';

    // Coordonnées de test (Centre de Cocody, Abidjan)
    private const COCODY_CENTER = [
        'latitude' => 5.3477,
        'longitude' => -3.9892,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner']);

        // Créer des résidences à différentes distances
        $this->createTestResidences();
    }

    /**
     * Créer des résidences de test à différentes distances
     */
    private function createTestResidences(): void
    {
        // Résidence 1: ~50m du centre (très proche)
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
            'commune' => 'Cocody',
            'quartier' => 'Riviera',
            'price_per_month' => 150000,
            'status' => 'approved',
            'is_available' => true,
        ]);

        // Résidence 2: ~200m du centre
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3495,
            'longitude' => -3.9880,
            'commune' => 'Cocody',
            'quartier' => 'Riviera',
            'price_per_month' => 200000,
            'status' => 'approved',
            'is_available' => true,
        ]);

        // Résidence 3: ~350m du centre
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3510,
            'longitude' => -3.9865,
            'commune' => 'Cocody',
            'quartier' => '2 Plateaux',
            'price_per_month' => 250000,
            'status' => 'approved',
            'is_available' => true,
        ]);

        // Résidence 4: ~450m du centre
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3506,
            'longitude' => -3.9863,
            'commune' => 'Cocody',
            'quartier' => '2 Plateaux',
            'price_per_month' => 300000,
            'status' => 'approved',
            'is_available' => true,
        ]);

        // Résidence 5: ~600m du centre (dans limite 2km)
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3540,
            'longitude' => -3.9840,
            'commune' => 'Cocody',
            'quartier' => 'Angré',
            'price_per_month' => 350000,
            'status' => 'approved',
            'is_available' => true,
        ]);

        // Résidence 6: ~100m mais non disponible
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3485,
            'longitude' => -3.9885,
            'commune' => 'Cocody',
            'quartier' => 'Riviera',
            'price_per_month' => 180000,
            'status' => 'approved',
            'is_available' => false, // Non disponible
        ]);

        // Résidence 7: ~100m mais en attente d'approbation
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3482,
            'longitude' => -3.9888,
            'commune' => 'Cocody',
            'quartier' => 'Riviera',
            'price_per_month' => 160000,
            'status' => 'pending', // Non approuvée
            'is_available' => true,
        ]);
    }

    /**
     * Test: Recherche basique par rayon fonctionne
     */
    public function test_search_returns_residences_within_radius(): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 5000,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'pagination' => ['total', 'per_page', 'current_page'],
                'search' => ['center', 'radius', 'radius_label'],
            ])
            ->assertJson(['success' => true]);

        // Avec 5km, doit trouver toutes les résidences proches
        $this->assertGreaterThanOrEqual(1, $response->json('pagination.total'));
    }

    /**
     * Test: Recherche avec rayon 10km retourne plus de résultats que 2km
     */
    public function test_search_with_larger_radius_returns_more_results(): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 10000,
        ]);

        $response->assertStatus(200);

        // Avec 10km, doit trouver toutes les résidences dans la zone
        $this->assertGreaterThanOrEqual(4, $response->json('pagination.total'));
    }

    /**
     * Test: Seules les résidences approuvées et disponibles sont retournées
     */
    public function test_search_only_returns_approved_and_available(): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 5000,
        ]);

        $response->assertStatus(200);

        $residenceIds = collect($response->json('data'))->pluck('id');

        // Vérifier que toutes les résidences retournées sont approuvées et disponibles
        $residences = Residence::whereIn('id', $residenceIds)->get();

        foreach ($residences as $residence) {
            $this->assertEquals('approved', $residence->status);
            $this->assertTrue($residence->is_available);
        }
    }

    /**
     * Test: Les résultats sont triés par distance par défaut
     */
    public function test_results_are_sorted_by_distance(): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 5000,
        ]);

        $response->assertStatus(200);

        $distances = collect($response->json('data'))
            ->pluck('location.distance_meters')
            ->filter()
            ->toArray();

        // Vérifier que les distances sont croissantes
        for ($i = 1; $i < count($distances); $i++) {
            $this->assertGreaterThanOrEqual(
                $distances[$i - 1],
                $distances[$i],
                'Les résidences doivent être triées par distance croissante',
            );
        }
    }

    /**
     * Test: Filtre par prix fonctionne
     */
    public function test_search_filters_by_price(): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 5000,
            'min_price' => 200000,
            'max_price' => 300000,
        ]);

        $response->assertStatus(200);

        $prices = collect($response->json('data'))->pluck('price');

        foreach ($prices as $price) {
            $this->assertGreaterThanOrEqual(200000, $price);
            $this->assertLessThanOrEqual(300000, $price);
        }
    }

    /**
     * Test: Validation des coordonnées hors Abidjan
     */
    public function test_rejects_coordinates_outside_coverage(): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => 48.8566, // Paris — hors zone
            'longitude' => 2.3522, // Paris — hors limites CI/BF
            'radius' => 5000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    /**
     * Test: Validation du rayon autorisé
     */
    public function test_rejects_invalid_radius(): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 750, // Non autorisé
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['radius']);
    }

    /**
     * Test: Rayons valides sont acceptés
     */
    #[DataProvider('validRadiiProvider')]
    public function test_accepts_valid_radii(int $radius): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => $radius,
        ]);

        $response->assertStatus(200);
    }

    public static function validRadiiProvider(): array
    {
        return [
            '2km' => [2000],
            '5km' => [5000],
            '10km' => [10000],
            '25km' => [25000],
            '50km' => [50000],
        ];
    }

    /**
     * Test: L'endpoint nearby fonctionne
     */
    public function test_nearby_endpoint_works(): void
    {
        $response = $this->getJson('/api/v1/geo/nearby?'.http_build_query([
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 5000,
            'limit' => 5,
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => ['center', 'radius', 'count'],
            ]);
    }

    /**
     * Test: L'endpoint autocomplete fonctionne
     */
    public function test_autocomplete_endpoint_works(): void
    {
        $response = $this->getJson('/api/v1/geo/autocomplete?q=coco');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);

        // Doit trouver Cocody
        $suggestions = collect($response->json('data'));
        $this->assertTrue(
            $suggestions->contains(fn ($s) => str_contains(strtolower($s['label']), 'cocody')),
        );
    }

    /**
     * Test: L'endpoint trending fonctionne
     */
    public function test_trending_endpoint_works(): void
    {
        $response = $this->getJson('/api/v1/geo/trending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ]);
    }

    /**
     * Test: L'endpoint zone stats fonctionne
     */
    public function test_zone_stats_endpoint_works(): void
    {
        $response = $this->getJson('/api/v1/geo/zones/cocody/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'zone',
                    'label',
                    'center',
                    'statistics',
                ],
            ]);
    }

    /**
     * Test: Zone invalide retourne 404
     */
    public function test_invalid_zone_returns_404(): void
    {
        $response = $this->getJson('/api/v1/geo/zones/paris/stats');

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    /**
     * Test: L'endpoint radius-counts fonctionne
     */
    public function test_radius_counts_endpoint_works(): void
    {
        $response = $this->getJson('/api/v1/geo/radius-counts?'.http_build_query([
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['radius', 'label', 'count'],
                ],
            ]);

        // Vérifier que les comptages sont cohérents (plus de résidences avec rayon plus grand)
        $counts = collect($response->json('data'));
        $count2000 = $counts->firstWhere('radius', 2000)['count'] ?? 0;
        $count5000 = $counts->firstWhere('radius', 5000)['count'] ?? 0;

        // Un rayon plus grand doit contenir au moins autant de résidences qu'un rayon plus petit
        $this->assertGreaterThanOrEqual($count2000, $count5000);
    }

    /**
     * Test: Le tri par prix fonctionne
     */
    public function test_sort_by_price_ascending(): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 5000,
            'sort' => 'price_asc',
        ]);

        $response->assertStatus(200);

        $prices = collect($response->json('data'))->pluck('price')->toArray();

        for ($i = 1; $i < count($prices); $i++) {
            $this->assertLessThanOrEqual(
                $prices[$i],
                $prices[$i - 1],
                'Les prix doivent être triés par ordre croissant',
            );
        }
    }

    /**
     * Test: Le tri par prix décroissant fonctionne
     */
    public function test_sort_by_price_descending(): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 5000,
            'sort' => 'price_desc',
        ]);

        $response->assertStatus(200);

        $prices = collect($response->json('data'))->pluck('price')->toArray();

        for ($i = 1; $i < count($prices); $i++) {
            $this->assertGreaterThanOrEqual(
                $prices[$i],
                $prices[$i - 1],
                'Les prix doivent être triés par ordre décroissant',
            );
        }
    }

    /**
     * Test: La pagination fonctionne
     */
    public function test_pagination_works(): void
    {
        // Créer plus de résidences pour tester la pagination
        for ($i = 0; $i < 10; $i++) {
            Residence::factory()->create([
                'owner_id' => $this->owner->id,
                'latitude' => 5.3477 + ($i * 0.0001),
                'longitude' => -3.9892,
                'commune' => 'Cocody',
                'quartier' => 'Riviera',
                'status' => 'approved',
                'is_available' => true,
            ]);
        }

        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 5000,
            'per_page' => 5,
            'page' => 1,
        ]);

        $response->assertStatus(200);

        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(1, $response->json('pagination.current_page'));
        $this->assertGreaterThan(1, $response->json('pagination.last_page'));
    }

    /**
     * Test: La structure de réponse est correcte
     */
    public function test_response_structure_is_correct(): void
    {
        $response = $this->postJson(self::GEO_SEARCH_API, [
            'latitude' => self::COCODY_CENTER['latitude'],
            'longitude' => self::COCODY_CENTER['longitude'],
            'radius' => 5000,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'price',
                        'price_formatted',
                        'location' => [
                            'address',
                            'commune',
                            'latitude',
                            'longitude',
                        ],
                        'thumbnail',
                        'available',
                    ],
                ],
                'pagination',
                'search',
                'zone_stats',
                'links',
            ]);
    }
}
