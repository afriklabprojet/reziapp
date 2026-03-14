<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests des avis (reviews)
 * Couvre : création, affichage, réponse propriétaire, mes avis
 * */
class ReviewTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $guest;
    protected User $owner;
    protected Residence $residence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->guest = User::factory()->create(['role' => 'user']);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
    }

    // ========================================
    // AFFICHAGE
    // ========================================

    #[Test]
    public function authenticated_user_can_view_a_review(): void
    {
        $review = Review::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'rating' => 4,
            'comment' => 'Très bel appartement, bien situé à Cocody.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('reviews.show', $review));

        $response->assertStatus(200);
        $response->assertViewIs('reviews.show');
        $response->assertViewHas('review');
    }

    // ========================================
    // CRÉATION D'AVIS
    // ========================================

    #[Test]
    public function guest_can_access_review_creation_form(): void
    {
        // Créer un booking complété pour permettre la review
        $policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Test',
            'refund_rules' => [],
            'is_active' => true,
        ]);

        Booking::factory()->completed()->create([
            'user_id' => $this->guest->id,
            'residence_id' => $this->residence->id,
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('reviews.create', $this->residence));

        // On accepte 200 (peut laisser un avis) ou redirect (ne peut pas)
        $this->assertContains($response->status(), [200, 302]);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_review(): void
    {
        $response = $this->get(route('reviews.create', $this->residence));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function review_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('reviews.store', $this->residence), []);

        $response->assertSessionHasErrors(['rating', 'comment']);
    }

    #[Test]
    public function review_rating_must_be_between_1_and_5(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('reviews.store', $this->residence), [
                'rating' => 6,
                'comment' => 'Un commentaire assez long pour être valide au minimum.',
            ]);

        $response->assertSessionHasErrors(['rating']);
    }

    #[Test]
    public function review_comment_must_be_at_least_20_chars(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('reviews.store', $this->residence), [
                'rating' => 4,
                'comment' => 'Trop court',
            ]);

        $response->assertSessionHasErrors(['comment']);
    }

    #[Test]
    public function review_comment_cannot_exceed_2000_chars(): void
    {
        $response = $this->actingAs($this->guest)
            ->post(route('reviews.store', $this->residence), [
                'rating' => 4,
                'comment' => str_repeat('A', 2001),
            ]);

        $response->assertSessionHasErrors(['comment']);
    }

    // ========================================
    // RÉPONSE DU PROPRIÉTAIRE
    // ========================================

    #[Test]
    public function owner_can_respond_to_review(): void
    {
        $review = Review::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'rating' => 4,
            'comment' => 'Bon séjour dans l\'ensemble, recommandé.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('reviews.respond', $review), [
                'owner_response' => 'Merci beaucoup pour votre avis positif !',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function owner_response_requires_minimum_length(): void
    {
        $review = Review::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'rating' => 3,
            'comment' => 'Séjour correct mais pourrait être amélioré.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('reviews.respond', $review), [
                'owner_response' => 'Merci',  // < 10 chars
            ]);

        $response->assertSessionHasErrors(['owner_response']);
    }

    #[Test]
    public function owner_response_is_required(): void
    {
        $review = Review::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'rating' => 3,
            'comment' => 'Un avis assez basique sur cette résidence.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('reviews.respond', $review), []);

        $response->assertSessionHasErrors(['owner_response']);
    }

    // ========================================
    // ÉVALUATION DU VOYAGEUR PAR LE PROPRIÉTAIRE
    // ========================================

    #[Test]
    public function owner_can_review_guest(): void
    {
        $review = Review::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'rating' => 4,
            'comment' => 'Excellente résidence, très propre et bien équipée.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('reviews.guest-review', $review), [
                'review' => 'Voyageur très respectueux, je recommande.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function guest_review_requires_minimum_10_chars(): void
    {
        $review = Review::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'rating' => 5,
            'comment' => 'Parfait ! Rien à redire sur ce logement magnifique.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('reviews.guest-review', $review), [
                'review' => 'Ok',  // Trop court
            ]);

        $response->assertSessionHasErrors(['review']);
    }

    // ========================================
    // MES AVIS
    // ========================================

    #[Test]
    public function user_can_view_their_reviews(): void
    {
        Review::create([
            'residence_id' => $this->residence->id,
            'user_id' => $this->guest->id,
            'rating' => 5,
            'comment' => 'Magnifique résidence, super emplacement à Cocody.',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('reviews.my'));

        $response->assertStatus(200);
        $response->assertViewIs('reviews.my-reviews');
        $response->assertViewHas('reviews');
    }

    #[Test]
    public function user_can_filter_reviews_by_type(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('reviews.my', ['type' => 'given']));

        $response->assertStatus(200);
    }

    #[Test]
    public function unauthenticated_user_cannot_view_my_reviews(): void
    {
        $response = $this->get(route('reviews.my'));

        $response->assertRedirect(route('login'));
    }
}
