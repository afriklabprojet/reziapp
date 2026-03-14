<?php

namespace App\Services;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SupportService
{
    /**
     * Create a new support ticket
     */
    public function createTicket(
        int $userId,
        string $category,
        string $subject,
        string $message,
        ?int $bookingId = null,
        ?int $disputeId = null,
        string $priority = 'medium',
        ?array $attachments = null,
    ): SupportTicket {
        $ticket = SupportTicket::create([
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'dispute_id' => $disputeId,
            'category' => $category,
            'subject' => $subject,
            'priority' => $priority,
            'status' => 'open',
        ]);

        // Add initial message
        $ticket->addMessage($userId, $message, $attachments, false);

        Log::info('Support ticket created', [
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'category' => $category,
        ]);

        return $ticket;
    }

    /**
     * Add message to ticket
     */
    public function addMessage(
        SupportTicket $ticket,
        int $userId,
        string $message,
        ?array $attachments = null,
        bool $isInternalNote = false,
    ): SupportMessage {
        $supportMessage = $ticket->addMessage($userId, $message, $attachments, $isInternalNote);

        // Mark first response if this is from staff
        if ($userId !== $ticket->user_id && !$isInternalNote) {
            $ticket->markFirstResponse();
        }

        Log::info('Support message added', [
            'ticket_id' => $ticket->id,
            'message_id' => $supportMessage->id,
            'from_staff' => $userId !== $ticket->user_id,
        ]);

        return $supportMessage;
    }

    /**
     * Assign ticket to admin
     */
    public function assignTicket(SupportTicket $ticket, int $adminId): SupportTicket
    {
        $ticket->assignTo($adminId);

        Log::info('Ticket assigned', [
            'ticket_id' => $ticket->id,
            'assigned_to' => $adminId,
        ]);

        return $ticket;
    }

    /**
     * Resolve ticket
     */
    public function resolveTicket(SupportTicket $ticket): SupportTicket
    {
        $ticket->resolve();

        Log::info('Ticket resolved', ['ticket_id' => $ticket->id]);

        return $ticket;
    }

    /**
     * Close ticket
     */
    public function closeTicket(SupportTicket $ticket): SupportTicket
    {
        $ticket->close();

        Log::info('Ticket closed', ['ticket_id' => $ticket->id]);

        return $ticket;
    }

    /**
     * Reopen ticket
     */
    public function reopenTicket(SupportTicket $ticket): SupportTicket
    {
        $ticket->reopen();

        Log::info('Ticket reopened', ['ticket_id' => $ticket->id]);

        return $ticket;
    }

    /**
     * Add satisfaction rating
     */
    public function rateTicket(SupportTicket $ticket, int $rating, ?string $comment = null): SupportTicket
    {
        if ($rating < 1 || $rating > 5) {
            throw new \Exception('La note doit être entre 1 et 5.');
        }

        $ticket->rate($rating, $comment);

        Log::info('Ticket rated', [
            'ticket_id' => $ticket->id,
            'rating' => $rating,
        ]);

        return $ticket;
    }

    /**
     * Upload attachment for message
     */
    public function uploadAttachment($file, int $ticketId): array
    {
        $path = $file->store("support/tickets/{$ticketId}", 'public');

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Get user tickets
     */
    public function getUserTickets(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return SupportTicket::forUser($userId)
            ->with(['latestMessage', 'booking.residence'])
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Get ticket details with messages
     */
    public function getTicketWithMessages(SupportTicket $ticket): SupportTicket
    {
        return $ticket->load([
            'messages' => fn ($q) => $q->with('user')->orderBy('created_at'),
            'user',
            'booking.residence',
            'dispute',
            'assignedAdmin',
        ]);
    }

    /**
     * Get tickets needing attention
     */
    public function getTicketsNeedingAttention(): \Illuminate\Database\Eloquent\Collection
    {
        return SupportTicket::active()
            ->needsFirstResponse()
            ->with(['user', 'booking.residence'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get assigned tickets for admin
     */
    public function getAssignedTickets(int $adminId): \Illuminate\Database\Eloquent\Collection
    {
        return SupportTicket::assignedTo($adminId)
            ->active()
            ->with(['user', 'latestMessage', 'booking.residence'])
            ->orderBy('priority', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get unassigned tickets
     */
    public function getUnassignedTickets(): \Illuminate\Database\Eloquent\Collection
    {
        return SupportTicket::unassigned()
            ->active()
            ->with(['user', 'latestMessage'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Mark messages as read
     */
    public function markMessagesAsRead(SupportTicket $ticket, int $userId): void
    {
        $ticket->messages()
            ->where('user_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        return SupportMessage::whereHas('ticket', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->where('user_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get support statistics
     */
    public function getStats(?string $period = 'month'): array
    {
        $query = SupportTicket::query();

        if ($period === 'day') {
            $query->whereDate('created_at', today());
        } elseif ($period === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        }

        $total = (clone $query)->count();
        $open = (clone $query)->open()->count();
        $inProgress = (clone $query)->inProgress()->count();
        $resolved = (clone $query)->resolved()->count();
        $closed = (clone $query)->closed()->count();

        // Average response time
        $avgResponseTime = (clone $query)
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_minutes')
            ->value('avg_minutes');

        // Average satisfaction
        $avgSatisfaction = (clone $query)
            ->whereNotNull('satisfaction_rating')
            ->avg('satisfaction_rating');

        // By category
        $byCategory = (clone $query)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // By priority
        $byPriority = (clone $query)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        return [
            'total' => $total,
            'open' => $open,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'closed' => $closed,
            'resolution_rate' => $total > 0 ? round((($resolved + $closed) / $total) * 100, 2) : 0,
            'avg_response_minutes' => round($avgResponseTime ?? 0),
            'avg_satisfaction' => round($avgSatisfaction ?? 0, 1),
            'by_category' => $byCategory,
            'by_priority' => $byPriority,
            'needs_attention' => SupportTicket::active()->needsFirstResponse()->count(),
        ];
    }

    /**
     * Search tickets
     */
    public function searchTickets(string $query, ?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        return SupportTicket::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->where(function ($q) use ($query) {
                $q->where('ticket_number', 'like', "%{$query}%")
                  ->orWhere('subject', 'like', "%{$query}%")
                  ->orWhereHas('messages', function ($m) use ($query) {
                      $m->where('message', 'like', "%{$query}%");
                  });
            })
            ->with(['user', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();
    }
}
