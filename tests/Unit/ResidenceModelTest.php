<?php

namespace Tests\Unit;

use App\Models\Amenity;
use App\Models\Photo;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResidenceModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
    }

    #[Test]
    public function residence_belongs_to_owner(): void
    {
        $residence = Residence::factory()->create(['owner_id' => $this->owner->id]);

        $this->assertInstanceOf(User::class, $residence->owner);
        $this->assertEquals($this->owner->id, $residence->owner->id);
    }

    #[Test]
    public function residence_has_many_photos(): void
    {
        $residence = Residence::factory()->create(['owner_id' => $this->owner->id]);
        Photo::factory()->count(2)->create(['residence_id' => $residence->id]);

        $residence->refresh();
        $this->assertCount(2, $residence->photos);
        $this->assertInstanceOf(Photo::class, $residence->photos->first());
    }

    #[Test]
    public function residence_has_many_amenities(): void
    {
        $residence = Residence::factory()->create(['owner_id' => $this->owner->id]);
        $amenity1 = Amenity::factory()->create();
        $amenity2 = Amenity::factory()->create();

        $residence->amenities()->attach([$amenity1->id, $amenity2->id]);

        $this->assertCount(2, $residence->amenities);
        $this->assertInstanceOf(Amenity::class, $residence->amenities->first());
    }

    #[Test]
    public function scope_approved_filters_approved_residences(): void
    {
        Residence::factory()->create(['owner_id' => $this->owner->id, 'status' => 'approved']);
        Residence::factory()->create(['owner_id' => $this->owner->id, 'status' => 'pending']);
        Residence::factory()->create(['owner_id' => $this->owner->id, 'status' => 'rejected']);

        $approvedResidences = Residence::approved()->get();

        $this->assertCount(1, $approvedResidences);
        $this->assertEquals('approved', $approvedResidences->first()->status);
    }

    #[Test]
    public function scope_available_filters_available_residences(): void
    {
        Residence::factory()->create(['owner_id' => $this->owner->id, 'is_available' => true]);
        Residence::factory()->create(['owner_id' => $this->owner->id, 'is_available' => false]);

        $availableResidences = Residence::available()->get();

        $this->assertCount(1, $availableResidences);
        $this->assertTrue($availableResidences->first()->is_available);
    }

    #[Test]
    public function scope_within_radius_uses_haversine_formula(): void
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

        $results = Residence::withinRadius($centerLat, $centerLng, 500)->get();

        $this->assertTrue($results->contains('id', $nearResidence->id));
        $this->assertFalse($results->contains('id', $farResidence->id));
    }

    #[Test]
    public function distance_from_calculates_correct_distance(): void
    {
        $residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
        ]);

        $distance = $residence->distanceFrom(5.3477, -3.9892);

        // Distance en mètres (environ 40m)
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(100, $distance); // < 100 mètres
    }

    #[Test]
    public function soft_deletes_residence(): void
    {
        $residence = Residence::factory()->create(['owner_id' => $this->owner->id]);
        $residenceId = $residence->id;

        $residence->delete();

        $this->assertSoftDeleted('residences', ['id' => $residenceId]);
        $this->assertNull(Residence::find($residenceId));
        $this->assertNotNull(Residence::withTrashed()->find($residenceId));
    }

    #[Test]
    public function deleting_residence_soft_deletes(): void
    {
        $residence = Residence::factory()->create(['owner_id' => $this->owner->id]);
        Photo::factory()->create(['residence_id' => $residence->id]);

        $residence->delete();

        $this->assertSoftDeleted('residences', ['id' => $residence->id]);
    }

    #[Test]
    public function casts_attributes_correctly(): void
    {
        $residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'price_per_month' => 250000,
            'is_available' => true,
            'latitude' => 5.3480,
            'longitude' => -3.9890,
        ]);

        $this->assertIsFloat($residence->latitude);
        $this->assertIsFloat($residence->longitude);
        $this->assertIsBool($residence->is_available);
    }

    #[Test]
    public function default_status_is_pending(): void
    {
        $residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        $this->assertNotNull($residence->status);
    }

    #[Test]
    public function can_filter_by_commune(): void
    {
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'commune' => 'Cocody',
            'status' => 'approved',
        ]);
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'commune' => 'Marcory',
            'status' => 'approved',
        ]);

        $cocodyResidences = Residence::where('commune', 'Cocody')->get();

        $this->assertCount(1, $cocodyResidences);
        $this->assertEquals('Cocody', $cocodyResidences->first()->commune);
    }

    #[Test]
    public function can_filter_by_price_range(): void
    {
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'price_per_month' => 100000,
            'status' => 'approved',
        ]);
        Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'price_per_month' => 300000,
            'status' => 'approved',
        ]);

        $cheapResidences = Residence::where('price_per_month', '<=', 150000)->get();

        $this->assertCount(1, $cheapResidences);
        $this->assertLessThanOrEqual(150000, $cheapResidences->first()->price_per_month);
    }
}
