<?php

namespace Tests\Feature;

use App\Models\CoHost;
use App\Models\Residence;
use App\Models\User;
use App\Notifications\CoHostInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests du flux de co-hôte / invitation
 * Couvre : création, acceptation, refus, révocation, renvoi
 * */
class CoHostInvitationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $owner;
    protected Residence $residence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
    }

    // ========================================
    // LISTING DES CO-HÔTES
    // ========================================

    #[Test]
    public function owner_can_view_cohosts_list(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.cohosts.index', $this->residence));

        $response->assertStatus(200);
        $response->assertViewIs('owner.cohosts.index');
        $response->assertViewHas('coHosts');
    }

    #[Test]
    public function non_owner_cannot_view_cohosts(): void
    {
        $otherUser = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);

        $response = $this->actingAs($otherUser)
            ->get(route('owner.cohosts.index', $this->residence));

        $response->assertStatus(403);
    }

    // ========================================
    // CRÉATION D'INVITATION
    // ========================================

    #[Test]
    public function owner_can_access_cohost_invitation_form(): void
    {
        $response = $this->actingAs($this->owner)
            ->get(route('owner.cohosts.create', $this->residence));

        $response->assertStatus(200);
        $response->assertViewIs('owner.cohosts.create');
    }

    #[Test]
    public function owner_can_invite_cohost(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->owner)
            ->post(route('owner.cohosts.store', $this->residence), [
                'email' => 'cohost@example.com',
                'name' => 'Jean Dupont',
                'phone' => '0707070707',
                'can_manage_calendar' => true,
                'can_respond_messages' => true,
            ]);

        $response->assertRedirect(route('owner.cohosts.index', $this->residence));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('co_hosts', [
            'residence_id' => $this->residence->id,
            'email' => 'cohost@example.com',
            'name' => 'Jean Dupont',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function invitation_email_uses_public_invitation_route(): void
    {
        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'cohost@example.com',
            'name' => 'Jean Dupont',
            'status' => 'pending',
            'invitation_token' => 'mail-token-'.Str::random(48),
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $message = (new CoHostInvitation($cohost, $this->residence))->toMail($this->owner);

        $this->assertSame(
            route('cohost.invitation', $cohost->invitation_token),
            $message->actionUrl,
        );
    }

    #[Test]
    public function invitation_requires_email_and_name(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.cohosts.store', $this->residence), []);

        $response->assertSessionHasErrors(['email', 'name']);
    }

    #[Test]
    public function invitation_requires_valid_email(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('owner.cohosts.store', $this->residence), [
                'email' => 'not-an-email',
                'name' => 'Test User',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function cannot_invite_already_invited_person(): void
    {
        Notification::fake();

        // Première invitation
        CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'already@example.com',
            'name' => 'Already Invited',
            'status' => 'pending',
            'invitation_token' => Str::random(64),
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        // Deuxième invitation au même email
        $response = $this->actingAs($this->owner)
            ->post(route('owner.cohosts.store', $this->residence), [
                'email' => 'already@example.com',
                'name' => 'Already Invited Again',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ========================================
    // ACCEPTATION D'INVITATION
    // ========================================

    #[Test]
    public function invitation_acceptance_page_loads(): void
    {
        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'cohost@example.com',
            'name' => 'Test CoHost',
            'status' => 'pending',
            'invitation_token' => 'valid-test-token-'.Str::random(48),
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->get(route('cohost.invitation', $cohost->invitation_token));

        $response->assertStatus(200);
        $response->assertViewIs('cohosts.accept');
    }

    #[Test]
    public function authenticated_user_can_accept_invitation(): void
    {
        $cohostUser = User::factory()->create(['email' => 'cohost@example.com']);

        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'cohost@example.com',
            'name' => 'Test CoHost',
            'status' => 'pending',
            'invitation_token' => 'accept-test-token-'.Str::random(48),
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($cohostUser)
            ->post(route('cohost.invitation.accept', $cohost->invitation_token));

        $response->assertRedirect();

        $cohost->refresh();
        $this->assertEquals('accepted', $cohost->status);
        $this->assertEquals($cohostUser->id, $cohost->user_id);
    }

    #[Test]
    public function wrong_email_user_cannot_accept_invitation(): void
    {
        $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);

        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'cohost@example.com',
            'name' => 'Test CoHost',
            'status' => 'pending',
            'invitation_token' => 'wrong-email-token-'.Str::random(48),
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($wrongUser)
            ->post(route('cohost.invitation.accept', $cohost->invitation_token));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $cohost->refresh();
        $this->assertEquals('pending', $cohost->status);
    }

    #[Test]
    public function expired_invitation_cannot_be_accepted(): void
    {
        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'expired@example.com',
            'name' => 'Expired CoHost',
            'status' => 'pending',
            'invitation_token' => 'expired-token-'.Str::random(48),
            'invited_at' => now()->subDays(10),
            'expires_at' => now()->subDays(3), // Expiré
        ]);

        $response = $this->get(route('cohost.invitation', $cohost->invitation_token));

        $response->assertStatus(404);
    }

    // ========================================
    // DÉCLINAISON D'INVITATION
    // ========================================

    #[Test]
    public function user_can_decline_invitation(): void
    {
        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'decline@example.com',
            'name' => 'Declined CoHost',
            'status' => 'pending',
            'invitation_token' => 'decline-token-'.Str::random(48),
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->post(route('cohost.invitation.decline', $cohost->invitation_token));

        $response->assertRedirect(route('home'));

        $cohost->refresh();
        $this->assertEquals('declined', $cohost->status);
    }

    // ========================================
    // RÉVOCATION
    // ========================================

    #[Test]
    public function owner_can_revoke_cohost(): void
    {
        $cohostUser = User::factory()->create();

        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'user_id' => $cohostUser->id,
            'email' => $cohostUser->email,
            'name' => $cohostUser->name,
            'status' => 'accepted',
            'invitation_token' => Str::random(64),
            'invited_at' => now()->subDays(5),
            'accepted_at' => now()->subDays(4),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('owner.cohosts.revoke', [$this->residence, $cohost]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $cohost->refresh();
        $this->assertEquals('revoked', $cohost->status);
    }

    // ========================================
    // RENVOI D'INVITATION
    // ========================================

    #[Test]
    public function owner_can_resend_pending_invitation(): void
    {
        Notification::fake();

        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'resend@example.com',
            'name' => 'Resend CoHost',
            'status' => 'pending',
            'invitation_token' => Str::random(64),
            'invited_at' => now()->subDays(5),
            'expires_at' => now()->addDays(2),
        ]);

        $oldToken = $cohost->invitation_token;

        $response = $this->actingAs($this->owner)
            ->post(route('owner.cohosts.resend', [$this->residence, $cohost]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $cohost->refresh();
        $this->assertNotEquals($oldToken, $cohost->invitation_token);
    }

    #[Test]
    public function owner_cannot_resend_accepted_invitation(): void
    {
        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'accepted@example.com',
            'name' => 'Accepted CoHost',
            'status' => 'accepted',
            'invitation_token' => Str::random(64),
            'invited_at' => now()->subDays(5),
            'accepted_at' => now()->subDays(3),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('owner.cohosts.resend', [$this->residence, $cohost]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ========================================
    // SUPPRESSION
    // ========================================

    #[Test]
    public function owner_can_delete_cohost(): void
    {
        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'delete@example.com',
            'name' => 'Delete Me',
            'status' => 'pending',
            'invitation_token' => Str::random(64),
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('owner.cohosts.destroy', [$this->residence, $cohost]));

        $response->assertRedirect(route('owner.cohosts.index', $this->residence));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('co_hosts', [
            'id' => $cohost->id,
        ]);
    }

    #[Test]
    public function non_owner_cannot_delete_cohost(): void
    {
        $otherOwner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);

        $cohost = CoHost::create([
            'residence_id' => $this->residence->id,
            'owner_id' => $this->owner->id,
            'email' => 'nodelete@example.com',
            'name' => 'Cannot Delete',
            'status' => 'pending',
            'invitation_token' => Str::random(64),
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($otherOwner)
            ->delete(route('owner.cohosts.destroy', [$this->residence, $cohost]));

        $response->assertStatus(403);
    }
}
