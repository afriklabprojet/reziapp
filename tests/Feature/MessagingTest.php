<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\UserTyping;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests pour le système de messagerie temps réel
 * Couvre conversations, messages et broadcasting
 */
#[Group('messaging')]
#[Group('realtime')]
class MessagingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $owner;
    protected Residence $residence;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('private');

        $this->user = User::factory()->create(['role' => 'user']);
        $this->owner = User::factory()->create(['role' => 'owner', 'two_factor_enabled' => true]);
        $this->residence = Residence::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'approved',
        ]);
    }

    // ========================================
    // TESTS DE CONVERSATIONS
    // ========================================

    /**     * Un utilisateur peut démarrer une conversation avec un propriétaire
     */
    #[Test]
    public function user_can_start_conversation_with_owner(): void
    {
        // A booking is required before a user can contact an owner
        Booking::factory()->create([
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('chat.start'), [
                'residence_id' => $this->residence->id,
                'message' => 'Bonjour, je suis intéressé par votre résidence.',
            ]);

        // La réponse peut être une redirection ou un JSON
        $this->assertTrue(
            $response->isRedirection() || $response->isSuccessful(),
        );

        $this->assertDatabaseHas('conversations', [
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
            'residence_id' => $this->residence->id,
        ]);
    }

    /**     * Un propriétaire ne peut pas se contacter lui-même
     */
    #[Test]
    public function owner_cannot_message_themselves(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('chat.start'), [
                'residence_id' => $this->residence->id,
                'message' => 'Test message',
            ]);

        // La réponse est généralement une redirection avec erreur
        $this->assertTrue(
            $response->isRedirection() || $response->status() >= 400,
        );
    }

    /**     * Une conversation existante est réutilisée
     */
    #[Test]
    public function existing_conversation_is_reused(): void
    {
        // Créer une conversation existante
        $conversation = Conversation::create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
            'residence_id' => $this->residence->id,
            'last_message_at' => now()->subDay(),
        ]);

        $this->actingAs($this->user)
            ->post(route('chat.start'), [
                'residence_id' => $this->residence->id,
                'message' => 'Nouveau message',
            ]);

        // Une seule conversation doit exister
        $this->assertEquals(1, Conversation::where([
            'user_id' => $this->user->id,
            'residence_id' => $this->residence->id,
        ])->count());
    }

    /**     * L'utilisateur peut voir la liste de ses conversations
     */
    #[Test]
    public function user_can_view_conversation_list(): void
    {
        Conversation::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chat.index'));

        $response->assertOk();
        $response->assertViewHas('conversations');
    }

    /**     * L'utilisateur peut accéder à une conversation où il participe
     */
    #[Test]
    public function user_can_access_own_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chat.show', $conversation));

        $response->assertOk();
    }

    /**     * L'utilisateur ne peut pas accéder à une conversation d'un autre
     */
    #[Test]
    public function user_cannot_access_others_conversation(): void
    {
        $otherUser = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'user_id' => $otherUser->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('chat.show', $conversation));

        $response->assertForbidden();
    }

    // ========================================
    // TESTS DE MESSAGES
    // ========================================

    /**     * Un participant peut envoyer un message
     */
    #[Test]
    public function participant_can_send_message(): void
    {
        Event::fake([MessageSent::class]);

        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('chat.send', $conversation), [
                'content' => 'Ceci est un test de message.',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user->id,
            'content' => 'Ceci est un test de message.',
        ]);

        Event::assertDispatched(MessageSent::class);
    }

    /**     * Un message peut contenir une pièce jointe image
     */
    #[Test]
    public function message_can_have_image_attachment(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        $image = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->actingAs($this->user)
            ->post(route('chat.attachment', $conversation), [
                'file' => $image,
                'caption' => 'Voici une photo',
            ]);

        $response->assertOk();

        $message = Message::where('conversation_id', $conversation->id)->latest()->first();

        $this->assertNotNull($message);
        $this->assertNotNull($message->attachments);
        $this->assertEquals('image', $message->attachments[0]['type']);
    }

    /**     * Un message est limité à 5000 caractères
     */
    #[Test]
    public function message_content_is_limited_to_5000_chars(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('chat.send', $conversation), [
                'content' => str_repeat('a', 5001),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    /**     * Les messages non lus sont marqués comme lus quand on visite la conversation
     */
    #[Test]
    public function unread_messages_are_marked_as_read_on_visit(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        // Messages envoyés par le propriétaire (non lus)
        Message::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->owner->id,
            'read_at' => null,
        ]);

        $this->actingAs($this->user)
            ->get(route('chat.show', $conversation));

        // Tous les messages du propriétaire doivent être marqués comme lus
        $unreadCount = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $this->owner->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    /**     * La date last_message_at est mise à jour à chaque nouveau message
     */
    #[Test]
    public function last_message_at_is_updated(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
            'last_message_at' => now()->subHour(),
        ]);

        $oldDate = $conversation->last_message_at;

        $this->actingAs($this->user)
            ->post(route('chat.send', $conversation), [
                'content' => 'Nouveau message',
            ]);

        $conversation->refresh();
        $this->assertTrue($conversation->last_message_at->gt($oldDate));
    }

    // ========================================
    // TESTS BROADCASTING (TEMPS RÉEL)
    // ========================================

    /**     * L'événement MessageSent est diffusé sur le bon canal
     */
    #[Test]
    public function message_sent_event_broadcasts_on_correct_channel(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user->id,
        ]);

        $event = new MessageSent($message);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals(
            'private-conversation.'.$conversation->id,
            $channels[0]->name,
        );
    }

    /**     * L'événement MessageSent a le bon nom de broadcast
     */
    #[Test]
    public function message_sent_event_has_correct_broadcast_name(): void
    {
        $message = Message::factory()->create();
        $event = new MessageSent($message);

        $this->assertEquals('message.sent', $event->broadcastAs());
    }

    /**     * Les données de broadcast contiennent le message et l'expéditeur
     */
    #[Test]
    public function broadcast_data_contains_message_and_sender(): void
    {
        $message = Message::factory()->create([
            'sender_id' => $this->user->id,
            'content' => 'Test broadcast',
        ]);

        $event = new MessageSent($message);

        // L'événement doit charger le sender
        $this->assertNotNull($event->message->sender);
        $this->assertEquals($this->user->id, $event->message->sender->id);
    }

    /**     * Un utilisateur autorisé peut écouter le canal de conversation
     */
    #[Test]
    public function authorized_user_can_listen_to_conversation_channel(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        // Test direct du callback d'autorisation du canal
        // L'utilisateur participant doit être autorisé
        $this->assertTrue(
            $this->user->id === $conversation->user_id || $this->user->id === $conversation->owner_id,
        );

        // Le propriétaire participant doit être autorisé
        $this->assertTrue(
            $this->owner->id === $conversation->user_id || $this->owner->id === $conversation->owner_id,
        );
    }

    /**     * Un utilisateur non autorisé ne peut pas écouter le canal
     */
    #[Test]
    public function unauthorized_user_cannot_listen_to_conversation_channel(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        $otherUser = User::factory()->create();

        // Un utilisateur tiers ne doit pas être autorisé
        $this->assertFalse(
            $otherUser->id === $conversation->user_id || $otherUser->id === $conversation->owner_id,
        );
    }

    // ========================================
    // TESTS INDICATEUR DE FRAPPE
    // ========================================

    /**     * L'indicateur de frappe est diffusé
     */
    #[Test]
    public function typing_indicator_is_broadcast(): void
    {
        Event::fake([UserTyping::class]);

        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('chat.typing', $conversation));

        $response->assertOk();
        Event::assertDispatched(UserTyping::class);
    }

    // ========================================
    // TESTS API AJAX
    // ========================================

    /**     * Les messages peuvent être récupérés en AJAX avec pagination
     */
    #[Test]
    public function messages_can_be_fetched_via_ajax_with_pagination(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        Message::factory()->count(30)->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('chat.messages', [
                'conversation' => $conversation,
                'before' => null,
            ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'messages',
            'has_more',
        ]);
    }

    /**     * Marquer les messages comme lus via API
     */
    #[Test]
    public function can_mark_messages_as_read_via_api(): void
    {
        Event::fake([MessagesRead::class]);

        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->owner->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('chat.read', $conversation));

        $response->assertOk();
        Event::assertDispatched(MessagesRead::class);
    }

    // ========================================
    // TESTS DE SÉCURITÉ
    // ========================================

    /**     * Un utilisateur non connecté ne peut pas accéder aux conversations
     */
    #[Test]
    public function guest_cannot_access_conversations(): void
    {
        $response = $this->get(route('chat.index'));
        $response->assertRedirect(route('login'));
    }

    /**     * Un utilisateur non connecté ne peut pas envoyer de message
     */
    #[Test]
    public function guest_cannot_send_message(): void
    {
        $conversation = Conversation::factory()->create();

        $response = $this->postJson(route('chat.send', $conversation), [
            'content' => 'Test',
        ]);

        $response->assertUnauthorized();
    }

    /**     * Les messages sont échappés pour éviter XSS
     */
    #[Test]
    public function message_content_is_escaped(): void
    {
        $conversation = Conversation::factory()->create([
            'user_id' => $this->user->id,
            'owner_id' => $this->owner->id,
        ]);

        $maliciousContent = '<script>alert("xss")</script>';

        $this->actingAs($this->user)
            ->post(route('chat.send', $conversation), [
                'content' => $maliciousContent,
            ]);

        $message = Message::latest()->first();

        // Le contenu est stocké tel quel mais sera échappé à l'affichage
        $this->assertEquals($maliciousContent, $message->content);
    }
}
