<?php

namespace Tests\Feature;

use App\Models\CancellationPolicy;
use App\Models\Collection;
use App\Models\Favorite;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests des collections de favoris
 * Couvre : création, affichage, partage, suppression
 */
class CollectionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
    }

    // ========================================
    // LISTING
    // ========================================

    #[Test]
    public function authenticated_user_can_view_collections_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('collections.index'));

        $response->assertStatus(200);
        $response->assertViewIs('collections.index');
    }

    #[Test]
    public function unauthenticated_user_cannot_view_collections(): void
    {
        $response = $this->get(route('collections.index'));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // CRÉATION
    // ========================================

    #[Test]
    public function user_can_create_a_collection(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('collections.store'), [
                'name' => 'Mes coups de cœur Cocody',
                'description' => 'Les meilleurs apparts à Cocody',
                'is_public' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('collections', [
            'user_id' => $this->user->id,
            'name' => 'Mes coups de cœur Cocody',
            'is_public' => true,
        ]);
    }

    #[Test]
    public function collection_creation_requires_name(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('collections.store'), [
                'description' => 'Sans nom',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    #[Test]
    public function collection_name_max_length_is_100(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('collections.store'), [
                'name' => str_repeat('a', 101),
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    // ========================================
    // AFFICHAGE
    // ========================================

    #[Test]
    public function owner_can_view_their_collection(): void
    {
        $collection = Collection::create([
            'user_id' => $this->user->id,
            'name' => 'Ma collection',
            'is_public' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('collections.show', $collection));

        $response->assertStatus(200);
        $response->assertViewIs('collections.show');
    }

    #[Test]
    public function other_user_cannot_view_private_collection(): void
    {
        $other = User::factory()->create();
        $collection = Collection::create([
            'user_id' => $this->user->id,
            'name' => 'Collection privée',
            'is_public' => false,
        ]);

        $response = $this->actingAs($other)
            ->get(route('collections.show', $collection));

        $response->assertStatus(403);
    }

    #[Test]
    public function anyone_can_view_public_collection(): void
    {
        $other = User::factory()->create();
        $collection = Collection::create([
            'user_id' => $this->user->id,
            'name' => 'Collection publique',
            'is_public' => true,
        ]);

        $response = $this->actingAs($other)
            ->get(route('collections.show', $collection));

        $response->assertStatus(200);
    }

    // ========================================
    // PARTAGE
    // ========================================

    #[Test]
    public function public_collection_accessible_via_share_token(): void
    {
        $collection = Collection::create([
            'user_id' => $this->user->id,
            'name' => 'Partagée',
            'is_public' => true,
        ]);

        $response = $this->get(route('collections.shared', $collection->share_token));

        $response->assertStatus(200);
        $response->assertViewIs('collections.shared');
    }

    #[Test]
    public function private_collection_not_accessible_via_share_token(): void
    {
        $collection = Collection::create([
            'user_id' => $this->user->id,
            'name' => 'Privée',
            'is_public' => false,
        ]);

        $response = $this->get(route('collections.shared', $collection->share_token));

        $response->assertStatus(404);
    }

    #[Test]
    public function owner_can_regenerate_share_token(): void
    {
        $collection = Collection::create([
            'user_id' => $this->user->id,
            'name' => 'Partagée',
            'is_public' => true,
        ]);

        $oldToken = $collection->share_token;

        $response = $this->actingAs($this->user)
            ->postJson(route('collections.regenerate-token', $collection));

        $response->assertStatus(200);
        $response->assertJsonStructure(['share_url']);

        $collection->refresh();
        $this->assertNotEquals($oldToken, $collection->share_token);
    }

    // ========================================
    // MODIFICATION & SUPPRESSION
    // ========================================

    #[Test]
    public function owner_can_update_collection(): void
    {
        $collection = Collection::create([
            'user_id' => $this->user->id,
            'name' => 'Ancien nom',
            'is_public' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('collections.update', $collection), [
                'name' => 'Nouveau nom',
                'is_public' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'name' => 'Nouveau nom',
            'is_public' => true,
        ]);
    }

    #[Test]
    public function owner_can_delete_collection(): void
    {
        $collection = Collection::create([
            'user_id' => $this->user->id,
            'name' => 'À supprimer',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('collections.destroy', $collection));

        $response->assertRedirect();
        $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
    }

    #[Test]
    public function other_user_cannot_delete_collection(): void
    {
        $other = User::factory()->create();
        $collection = Collection::create([
            'user_id' => $this->user->id,
            'name' => 'Pas la tienne',
        ]);

        $response = $this->actingAs($other)
            ->delete(route('collections.destroy', $collection));

        $response->assertStatus(403);
    }
}
