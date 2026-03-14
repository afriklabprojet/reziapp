<?php

namespace Tests\Feature;

use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\RequiresMysql;
use Tests\TestCase;

/**
 * Tests du support client (tickets)
 * Couvre : création, affichage, messages, évaluation
 */
class SupportTicketTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    use RequiresMysql;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip all tests in this file if using SQLite (SupportTicket has MySQL-specific queries)
        $this->skipIfSqlite();

        $this->user = User::factory()->create(['role' => 'user']);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // ========================================
    // CRÉATION
    // ========================================

    #[Test]
    public function user_can_access_support_creation_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('support.create'));

        $response->assertStatus(200);
    }

    #[Test]
    public function user_can_create_a_support_ticket(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('support.store'), [
                'category' => 'booking',
                'subject' => 'Problème avec ma réservation',
                'message' => 'Je n\'arrive pas à finaliser ma réservation. L\'erreur persiste après plusieurs tentatives.',
                'priority' => 'medium',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $this->user->id,
            'category' => 'booking',
            'subject' => 'Problème avec ma réservation',
        ]);
    }

    #[Test]
    public function support_ticket_requires_valid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('support.store'), []);

        $response->assertSessionHasErrors(['category', 'subject', 'message']);
    }

    #[Test]
    public function ticket_number_is_auto_generated(): void
    {
        $service = app(SupportService::class);
        $ticket = $service->createTicket(
            $this->user->id,
            'general',
            'Test ticket',
            'Content',
            priority: 'low'
        );

        $this->assertNotNull($ticket->ticket_number);
        $this->assertStringStartsWith('TKT-', $ticket->ticket_number);
    }

    // ========================================
    // AFFICHAGE
    // ========================================

    #[Test]
    public function user_can_view_their_ticket(): void
    {
        $service = app(SupportService::class);
        $ticket = $service->createTicket(
            $this->user->id,
            'general',
            'Mon ticket',
            'Description du problème',
            priority: 'medium'
        );

        $response = $this->actingAs($this->user)
            ->get(route('support.show', $ticket));

        $response->assertStatus(200);
        $response->assertViewHas('ticket');
    }

    #[Test]
    public function other_user_cannot_view_ticket(): void
    {
        $other = User::factory()->create();
        $service = app(SupportService::class);
        $ticket = $service->createTicket(
            $this->user->id,
            'general',
            'Ticket privé',
            'Contenu privé',
            priority: 'medium'
        );

        $response = $this->actingAs($other)
            ->get(route('support.show', $ticket));

        $response->assertStatus(403);
    }

    // ========================================
    // SERVICE - CYCLE DE VIE
    // ========================================

    #[Test]
    public function ticket_lifecycle_open_to_resolved_to_closed(): void
    {
        $service = app(SupportService::class);
        $ticket = $service->createTicket(
            $this->user->id,
            'payment',
            'Cycle de vie',
            'Testing lifecycle',
            priority: 'high'
        );

        $this->assertEquals('open', $ticket->status);

        // Assign
        $ticket = $service->assignTicket($ticket, $this->admin->id);
        $this->assertEquals('in_progress', $ticket->status);
        $this->assertEquals($this->admin->id, $ticket->assigned_to);

        // Resolve
        $ticket = $service->resolveTicket($ticket);
        $this->assertEquals('resolved', $ticket->status);
        $this->assertNotNull($ticket->resolved_at);

        // Close
        $ticket = $service->closeTicket($ticket);
        $this->assertEquals('closed', $ticket->status);
    }

    #[Test]
    public function closed_ticket_can_be_reopened(): void
    {
        $service = app(SupportService::class);
        $ticket = $service->createTicket(
            $this->user->id,
            'technical',
            'Reopen test',
            'Test de réouverture',
            priority: 'low'
        );

        $service->resolveTicket($ticket);
        $service->closeTicket($ticket);

        $ticket = $service->reopenTicket($ticket);
        $this->assertEquals('open', $ticket->status);
    }

    #[Test]
    public function ticket_can_be_rated(): void
    {
        $service = app(SupportService::class);
        $ticket = $service->createTicket(
            $this->user->id,
            'general',
            'Rate test',
            'Testing satisfaction rating',
            priority: 'medium'
        );
        $service->resolveTicket($ticket);

        $ticket = $service->rateTicket($ticket, 5, 'Excellent service !');
        $this->assertEquals(5, $ticket->satisfaction_rating);
        $this->assertEquals('Excellent service !', $ticket->satisfaction_comment);
    }

    #[Test]
    public function ticket_rating_must_be_between_1_and_5(): void
    {
        $service = app(SupportService::class);
        $ticket = $service->createTicket(
            $this->user->id,
            'general',
            'Invalid rating',
            'Testing boundary',
            priority: 'low'
        );

        $this->expectException(\Exception::class);
        $service->rateTicket($ticket, 6, 'Too high');
    }

    // ========================================
    // MESSAGES
    // ========================================

    #[Test]
    public function user_can_add_message_to_ticket(): void
    {
        $service = app(SupportService::class);
        $ticket = $service->createTicket(
            $this->user->id,
            'general',
            'Message test',
            'Initial message',
            priority: 'medium'
        );

        $message = $service->addMessage($ticket, $this->user->id, 'Réponse supplémentaire');

        $this->assertNotNull($message);
        $this->assertEquals($this->user->id, $message->user_id);
        $this->assertEquals('Réponse supplémentaire', $message->message);
    }

    #[Test]
    public function admin_reply_sets_first_response_time(): void
    {
        $service = app(SupportService::class);
        $ticket = $service->createTicket(
            $this->user->id,
            'general',
            'First response test',
            'Waiting for admin',
            priority: 'high'
        );

        $this->assertNull($ticket->first_response_at);

        $service->addMessage($ticket, $this->admin->id, 'Voici notre réponse.');
        $ticket->refresh();

        $this->assertNotNull($ticket->first_response_at);
    }

    // ========================================
    // STATISTIQUES
    // ========================================

    #[Test]
    public function support_stats_return_expected_structure(): void
    {
        $service = app(SupportService::class);
        $stats = $service->getStats();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('open', $stats);
        $this->assertArrayHasKey('resolution_rate', $stats);
        $this->assertArrayHasKey('by_category', $stats);
        $this->assertArrayHasKey('by_priority', $stats);
    }
}
