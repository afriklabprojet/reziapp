<?php

namespace Tests\Feature;

use App\Models\Residence;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResidenceCrudTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $owner;
    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer le pays de test
        Country::firstOrCreate(
            ['code' => 'CI'],
            [
                'name' => 'Côte d\'Ivoire',
                'phone_code' => '+225',
                'is_active' => true,
                'latitude' => 7.5400,
                'longitude' => -5.5471,
                'min_lat' => 4.3500,
                'max_lat' => 10.7400,
                'min_lng' => -8.6000,
                'max_lng' => -2.4900,
            ]
        );

        // Créer des utilisateurs de test
        $this->owner = User::factory()->create([
            'role' => 'owner',
            'identity_verified' => true, // Required by identity.verified middleware
        ]);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->regularUser = User::factory()->create(['role' => 'user']);

        Storage::fake('public');
    }

    #[Test]
    public function owner_can_create_residence(): void
    {
        $residenceData = [
            'name' => 'Belle Villa Cocody',
            'description' => 'Belle villa située au cœur de Cocody avec jardin et piscine. Idéale pour une famille ou des professionnels. Proche de toutes commodités.',
            'address' => '123 Rue des Jardins',
            'country_code' => 'CI',
            'city' => 'Abidjan',
            'commune' => 'Cocody',
            'quartier' => 'Riviera',
            'latitude' => 5.3600,
            'longitude' => -4.0083,
            'type' => 'villa',
            'type_location' => 'apartment',
            'price_period' => 'month',
            'price_per_month' => 250000,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'max_guests' => 4,
            'surface_area' => 120,
            'is_available' => true,
        ];

        $response = $this->actingAs($this->owner)
            ->post(route('owner.residences.store'), $residenceData);

        $response->assertRedirect();
        $this->assertDatabaseHas('residences', [
            'name' => 'Belle Villa Cocody',
            'owner_id' => $this->owner->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function regular_user_cannot_create_residence(): void
    {
        $residenceData = [
            'name' => 'Test Residence',
            'description' => $this->faker->paragraph(5),
            'latitude' => 5.3600,
            'longitude' => -4.0083,
        ];

        $response = $this->actingAs($this->regularUser)
            ->post(route('owner.residences.store'), $residenceData);

        $response->assertStatus(403); // Forbidden
    }

    #[Test]
    public function owner_can_update_their_residence(): void
    {
        $residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        $updatedData = [
            'name' => 'Nom Mis à Jour',
            'price_per_month' => 300000,
        ];

        $response = $this->actingAs($this->owner)
            ->put(route('owner.residences.update', $residence), $updatedData);

        $response->assertRedirect();
        $this->assertDatabaseHas('residences', [
            'id' => $residence->id,
            'name' => 'Nom Mis à Jour',
            'price_per_month' => 300000,
        ]);
    }

    #[Test]
    public function owner_cannot_update_another_owners_residence(): void
    {
        $anotherOwner = User::factory()->create(['role' => 'owner']);
        $residence = Residence::factory()->create([
            'owner_id' => $anotherOwner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->put(route('owner.residences.update', $residence), ['name' => 'Hacked']);

        $response->assertStatus(403);
    }

    #[Test]
    public function owner_can_delete_their_residence(): void
    {
        $residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('owner.residences.destroy', $residence));

        $response->assertRedirect();
        $this->assertSoftDeleted('residences', ['id' => $residence->id]);
    }

    #[Test]
    public function admin_can_update_any_residence(): void
    {
        $residence = Residence::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('owner.residences.update', $residence), ['name' => 'Updated by Admin']);

        $response->assertRedirect();
        $this->assertDatabaseHas('residences', [
            'id' => $residence->id,
            'name' => 'Updated by Admin',
        ]);
    }

    #[Test]
    public function residence_requires_valid_coordinates(): void
    {
        $residenceData = [
            'name' => 'Test Residence',
            'description' => $this->faker->paragraph(5),
            'latitude' => 200, // Invalid
            'longitude' => -4.0083,
            'price' => 250000,
        ];

        $response = $this->actingAs($this->owner)
            ->post(route('owner.residences.store'), $residenceData);

        $response->assertSessionHasErrors(['latitude']);
    }

    #[Test]
    public function residence_requires_minimum_description_length(): void
    {
        $residenceData = [
            'name' => 'Test',
            'description' => 'Trop court', // < 50 caractères
            'latitude' => 5.3600,
            'longitude' => -4.0083,
        ];

        $response = $this->actingAs($this->owner)
            ->post(route('owner.residences.store'), $residenceData);

        $response->assertSessionHasErrors(['description']);
    }

    #[Test]
    public function can_upload_photos_with_residence(): void
    {
        Queue::fake(); // Éviter le job OptimizeResidencePhoto (colonne is_optimized absente en SQLite)

        $photo1 = UploadedFile::fake()->image('photo1.jpg');
        $photo2 = UploadedFile::fake()->image('photo2.jpg');

        $residenceData = [
            'name' => 'Villa with Photos',
            'description' => $this->faker->paragraph(5),
            'address' => '123 Rue Test',
            'country_code' => 'CI',
            'city' => 'Abidjan',
            'commune' => 'Cocody',
            'quartier' => 'Riviera',
            'latitude' => 5.3600,
            'longitude' => -4.0083,
            'type' => 'villa',
            'type_location' => 'apartment',
            'price_period' => 'month',
            'price_per_month' => 250000,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'max_guests' => 2,
            'photos' => [$photo1, $photo2],
        ];

        $response = $this->actingAs($this->owner)
            ->post(route('owner.residences.store'), $residenceData);

        $residence = Residence::where('name', 'Villa with Photos')->first();

        $this->assertNotNull($residence);
        $this->assertCount(2, $residence->photos);
        Storage::disk('public')->assertExists($residence->photos->first()->path);
    }
}
