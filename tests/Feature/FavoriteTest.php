<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests des favoris
 * Couvre : toggle, ajout, suppression, listing, notes
 * */
class FavoriteTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;
    protected Residence $residence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->residence = Residence::factory()->create();
    }

    // ========================================
    // TOGGLE FAVORIS
    // ========================================

    #[Test]
    public function user_can_toggle_favorite(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('favorites.toggle', $this->residence));

        $response->assertRedirect();
        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
        ]);
    }

    #[Test]
    public function user_can_toggle_favorite_via_json(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('favorites.toggle', $this->residence));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_favorite' => true,
        ]);
    }

    #[Test]
    public function toggle_removes_existing_favorite(): void
    {
        // Ajouter d'abord
        Favorite::create([
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('favorites.toggle', $this->residence));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_favorite' => false,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_toggle_favorite(): void
    {
        $response = $this->post(route('favorites.toggle', $this->residence));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // AJOUT AVEC COLLECTION
    // ========================================

    #[Test]
    public function user_can_add_favorite_with_notes(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('favorites.store'), [
                'residence_id' => $this->residence->id,
                'notes' => 'Très belle vue sur la lagune',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
            'notes' => 'Très belle vue sur la lagune',
        ]);
    }

    #[Test]
    public function add_favorite_requires_residence_id(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('favorites.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['residence_id']);
    }

    #[Test]
    public function add_favorite_requires_existing_residence(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('favorites.store'), [
                'residence_id' => 99999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['residence_id']);
    }

    // ========================================
    // LISTING
    // ========================================

    #[Test]
    public function user_can_view_favorites_list(): void
    {
        Favorite::create([
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('favorites.index'));

        $response->assertStatus(200);
        $response->assertViewIs('favorites.index');
        $response->assertViewHas('favorites');
        $response->assertViewHas('collections');
    }

    #[Test]
    public function unauthenticated_user_cannot_view_favorites(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    // ========================================
    // SUPPRESSION
    // ========================================

    #[Test]
    public function user_can_delete_their_favorite(): void
    {
        $favorite = Favorite::create([
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('favorites.destroy', $favorite));

        $response->assertRedirect();
        $this->assertDatabaseMissing('favorites', [
            'id' => $favorite->id,
        ]);
    }

    // ========================================
    // NOTES
    // ========================================

    #[Test]
    public function user_can_update_favorite_note(): void
    {
        $favorite = Favorite::create([
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('favorites.note', $favorite), [
                'notes' => 'Mis à jour : parfait pour les vacances',
            ]);

        $response->assertRedirect();
    }

    #[Test]
    public function other_user_cannot_update_favorite_note(): void
    {
        $favorite = Favorite::create([
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->patch(route('favorites.note', $favorite), [
                'notes' => 'Je ne devrais pas pouvoir faire ça',
            ]);

        $response->assertStatus(403);
    }

    // ========================================
    // CHECK SI FAVORI
    // ========================================

    #[Test]
    public function user_can_check_if_residence_is_favorite(): void
    {
        Favorite::create([
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('favorites.check', $this->residence->id));

        $response->assertStatus(200);
    }
}
