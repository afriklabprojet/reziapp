<?php

namespace Tests\Feature\Owner;

use App\Models\AutoReply;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests des réponses automatiques
 * Couvre : CRUD, toggle, utilisation manuelle
 */
class AutoReplyTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $owner;
    protected Residence $residence;

    protected function setUp(): void
    {
        parent::setUp();

        $policy = CancellationPolicy::create([
            'name' => 'flexible',
            'display_name' => 'Flexible',
            'description' => 'Test',
            'refund_rules' => [['days_before' => 7, 'refund_percent' => 100]],
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'cancellation_policy_id' => $policy->id,
            'status' => 'approved',
        ]);
    }

    // ========================================
    // LISTING
    // ========================================

    #[Test]
    public function owner_can_view_auto_replies_index(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.auto-replies.index'));

        $response->assertStatus(200);
        $response->assertViewIs('owner.auto-replies.index');
    }

    // ========================================
    // CRÉATION
    // ========================================

    #[Test]
    public function owner_can_create_auto_reply(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.auto-replies.store'), [
                'name' => 'Bienvenue',
                'trigger_type' => 'first_contact',
                'message' => 'Bonjour {guest_name}, merci pour votre intérêt pour {residence_name} !',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('auto_replies', [
            'user_id' => $this->owner->id,
            'name' => 'Bienvenue',
            'trigger_type' => 'first_contact',
        ]);
    }

    #[Test]
    public function auto_reply_requires_valid_data(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.auto-replies.store'), []);

        $response->assertSessionHasErrors(['name', 'trigger_type', 'message']);
    }

    #[Test]
    public function auto_reply_can_be_linked_to_residence(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.auto-replies.store'), [
                'name' => 'Réponse spécifique',
                'trigger_type' => 'keywords',
                'trigger_conditions' => [
                    'keywords' => ['disponibilité', 'prix'],
                ],
                'message' => 'Merci pour votre message. La résidence est disponible.',
                'residence_id' => $this->residence->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('auto_replies', [
            'user_id' => $this->owner->id,
            'residence_id' => $this->residence->id,
        ]);
    }

    // ========================================
    // MODIFICATION
    // ========================================

    #[Test]
    public function owner_can_update_auto_reply(): void
    {
        $autoReply = AutoReply::create([
            'user_id' => $this->owner->id,
            'name' => 'Ancien nom',
            'trigger_type' => 'first_contact',
            'message' => 'Ancien message',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->put(route('owner.auto-replies.update', $autoReply), [
                'name' => 'Nouveau nom',
                'trigger_type' => 'manual',
                'message' => 'Nouveau message avec {guest_name}',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('auto_replies', [
            'id' => $autoReply->id,
            'name' => 'Nouveau nom',
        ]);
    }

    #[Test]
    public function owner_can_toggle_auto_reply(): void
    {
        $autoReply = AutoReply::create([
            'user_id' => $this->owner->id,
            'name' => 'Toggle test',
            'trigger_type' => 'first_contact',
            'message' => 'Message',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('owner.auto-replies.toggle', $autoReply));

        $response->assertRedirect();
        $autoReply->refresh();
        $this->assertFalse($autoReply->is_active);
    }

    // ========================================
    // SUPPRESSION
    // ========================================

    #[Test]
    public function owner_can_delete_auto_reply(): void
    {
        $autoReply = AutoReply::create([
            'user_id' => $this->owner->id,
            'name' => 'À supprimer',
            'trigger_type' => 'manual',
            'message' => 'Contenu',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('owner.auto-replies.destroy', $autoReply));

        $response->assertRedirect();
        $this->assertDatabaseMissing('auto_replies', ['id' => $autoReply->id]);
    }

    #[Test]
    public function other_owner_cannot_manage_auto_reply(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $autoReply = AutoReply::create([
            'user_id' => $this->owner->id,
            'name' => 'Not yours',
            'trigger_type' => 'first_contact',
            'message' => 'Private',
            'is_active' => true,
        ]);

        $response = $this->actingAs($otherOwner)
            ->put(route('owner.auto-replies.update', $autoReply), [
                'name' => 'Hacked',
                'trigger_type' => 'first_contact',
                'message' => 'Stolen',
            ]);

        $response->assertStatus(403);
    }

    // ========================================
    // UTILISATION MANUELLE
    // ========================================

    #[Test]
    public function owner_can_use_manual_auto_reply(): void
    {
        $autoReply = AutoReply::create([
            'user_id' => $this->owner->id,
            'name' => 'Manuel',
            'trigger_type' => 'manual',
            'message' => 'Bonjour {guest_name}, bienvenue à {residence_name} !',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson(route('owner.auto-replies.use', $autoReply), [
                'guest_name' => 'Jean',
                'residence_name' => 'Villa Cocody',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message']);
        $this->assertStringContainsString('Jean', $response->json('message'));
        $this->assertStringContainsString('Villa Cocody', $response->json('message'));
    }

    #[Test]
    public function non_manual_auto_reply_cannot_be_used_manually(): void
    {
        $autoReply = AutoReply::create([
            'user_id' => $this->owner->id,
            'name' => 'Premier contact',
            'trigger_type' => 'first_contact',
            'message' => 'Auto message',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson(route('owner.auto-replies.use', $autoReply), [
                'guest_name' => 'Test',
            ]);

        $response->assertStatus(400);
    }
}
