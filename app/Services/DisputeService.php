<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cancellation;
use App\Models\Dispute;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisputeService
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Create a new dispute
     */
    public function createDispute(
        int $initiatorId,
        string $initiatedBy,
        string $type,
        string $reason,
        ?string $detailedDescription = null,
        ?int $bookingId = null,
        ?array $evidence = null,
    ): Dispute {
        // Validate that booking or cancellation exists
        if ($bookingId) {
            $booking = Booking::findOrFail($bookingId);

            // Check if user is part of this booking
            $isGuest = $booking->user_id === $initiatorId;
            $isOwner = $booking->residence->owner_id === $initiatorId;

            if (!$isGuest && !$isOwner) {
                throw new \Exception('Vous n\'êtes pas autorisé à ouvrir un litige pour cette réservation.');
            }
        }

        // Check for existing open dispute
        $existingDispute = Dispute::where('booking_id', $bookingId)
            ->unresolved()
            ->first();

        if ($existingDispute) {
            throw new \Exception('Un litige est déjà ouvert pour cette réservation.');
        }

        // Determine the against_user_id
        $againstUserId = null;
        if (isset($booking)) {
            $againstUserId = ($initiatedBy === 'host') ? $booking->user_id : $booking->residence->owner_id;
        }

        // Determine priority based on type
        $priority = $this->determinePriority($type, $booking ?? null);

        $dispute = Dispute::create([
            'reference' => 'DSP-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'booking_id' => $bookingId,
            'opened_by' => $initiatorId,
            'against_user_id' => $againstUserId,
            'category' => $type,
            'title' => $reason,
            'description' => $detailedDescription,
            'evidence_files' => $evidence,
            'status' => 'open',
            'priority' => $priority,
            'response_deadline' => now()->addHours(48),
        ]);

        // Create associated support ticket
        $this->createAssociatedTicket($dispute);

        Log::info('Dispute created', [
            'dispute_id' => $dispute->id,
            'type' => $type,
            'initiator' => $initiatorId,
        ]);

        return $dispute;
    }

    /**
     * Determine priority based on dispute type and context
     */
    protected function determinePriority(string $type, ?Booking $booking): string
    {
        // Urgent if check-in is within 24 hours
        if ($booking && $booking->hours_until_checkin < 24 && $booking->hours_until_checkin > 0) {
            return 'urgent';
        }

        // High priority for payment issues
        if (in_array($type, ['payment', 'refund'])) {
            return 'high';
        }

        // Medium for behavior issues
        if (in_array($type, ['host_behavior', 'guest_behavior'])) {
            return 'medium';
        }

        return 'medium';
    }

    /**
     * Create associated support ticket for dispute
     */
    protected function createAssociatedTicket(Dispute $dispute): SupportTicket
    {
        return SupportTicket::create([
            'user_id' => $dispute->opened_by,
            'booking_id' => $dispute->booking_id,
            'dispute_id' => $dispute->id,
            'category' => $this->mapDisputeTypeToCategory($dispute->category),
            'subject' => 'Litige: '.$dispute->type_label,
            'priority' => $dispute->priority,
            'status' => 'open',
        ]);
    }

    /**
     * Map dispute type to support category
     */
    protected function mapDisputeTypeToCategory(string $type): string
    {
        return match($type) {
            'cancellation' => 'cancellation',
            'payment', 'refund' => 'payment',
            'property_issue' => 'property',
            default => 'other',
        };
    }

    /**
     * Add evidence to dispute
     */
    public function addEvidence(Dispute $dispute, array $evidence, int $userId): Dispute
    {
        $evidence['added_by'] = $userId;
        $dispute->addEvidence($evidence);

        Log::info('Evidence added to dispute', [
            'dispute_id' => $dispute->id,
            'added_by' => $userId,
        ]);

        return $dispute;
    }

    /**
     * Assign dispute to admin
     */
    public function assignDispute(Dispute $dispute, int $adminId): Dispute
    {
        $dispute->assignTo($adminId);

        Log::info('Dispute assigned', [
            'dispute_id' => $dispute->id,
            'assigned_to' => $adminId,
        ]);

        return $dispute;
    }

    /**
     * Escalate dispute
     */
    public function escalateDispute(Dispute $dispute, string $reason, int $escalatedBy): Dispute
    {
        if (!$dispute->canBeEscalated()) {
            throw new \Exception('Ce litige ne peut pas être escaladé.');
        }

        $dispute->escalate($reason);

        Log::info('Dispute escalated', [
            'dispute_id' => $dispute->id,
            'escalated_by' => $escalatedBy,
            'reason' => $reason,
        ]);

        return $dispute;
    }

    /**
     * Request response from a party
     */
    public function requestResponse(Dispute $dispute, int $hours = 48): Dispute
    {
        $dispute->requestResponse($hours);

        // Notifier l'autre partie du litige
        $booking = $dispute->booking;
        if ($booking) {
            $initiatorId = $dispute->opened_by;
            $guestId = $booking->user_id;
            $ownerId = $booking->residence?->owner_id;

            // L'autre partie est celle qui n'a pas initié le litige
            $otherPartyId = ($initiatorId === $guestId) ? $ownerId : $guestId;
            $otherParty = $otherPartyId ? \App\Models\User::find($otherPartyId) : null;

            if ($otherParty) {
                $otherParty->notify(new \App\Notifications\DisputeResponseRequested($dispute));
                \App\Models\Notification::send(
                    $otherParty,
                    'system',
                    'Réponse requise — Litige',
                    "Votre réponse est requise pour le litige #{$dispute->id}. Vous avez {$hours}h pour répondre.",
                    route('disputes.show', $dispute),
                    ['dispute_id' => $dispute->id]
                );
            }
        }

        return $dispute;
    }

    /**
     * Resolve dispute
     */
    public function resolveDispute(
        Dispute $dispute,
        string $resolution,
        string $notes,
        int $resolvedBy,
        ?float $refundAmount = null,
    ): Dispute {
        return DB::transaction(function () use ($dispute, $resolution, $notes, $resolvedBy, $refundAmount) {
            // Process refund if applicable
            if ($refundAmount && $refundAmount > 0 && $dispute->booking) {
                $this->processDisputeRefund($dispute, $refundAmount, $resolution);
            }

            // Resolve the dispute
            $dispute->resolve($resolution, $notes);

            // Close associated support tickets
            $dispute->supportTickets()->active()->each(function ($ticket) {
                $ticket->resolve();
            });

            Log::info('Dispute resolved', [
                'dispute_id' => $dispute->id,
                'resolution' => $resolution,
                'resolved_by' => $resolvedBy,
                'refund_amount' => $refundAmount,
            ]);

            return $dispute;
        });
    }

    /**
     * Process refund as part of dispute resolution
     */
    protected function processDisputeRefund(Dispute $dispute, float $amount, string $resolution): void
    {
        $booking = $dispute->booking;
        $userId = match($resolution) {
            'favor_guest', 'full_refund', 'partial_refund' => $booking->user_id,
            default => $booking->user_id, // Default to guest
        };

        $this->refundService->createManualRefund(
            $booking,
            $userId,
            $amount,
            'credit', // Default to credit for dispute resolutions
            auth()->id() ?? 0,
            "Résolution de litige #{$dispute->id}",
        );
    }

    /**
     * Close dispute without resolution
     */
    public function closeDispute(Dispute $dispute, string $notes): Dispute
    {
        $dispute->close($notes);

        // Close associated support tickets
        $dispute->supportTickets()->active()->each(function ($ticket) {
            $ticket->close();
        });

        return $dispute;
    }

    /**
     * Get disputes for booking
     */
    public function getDisputesForBooking(int $bookingId): \Illuminate\Database\Eloquent\Collection
    {
        return Dispute::where('booking_id', $bookingId)
            ->with(['initiator', 'assignedAdmin'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get user disputes (as initiator)
     */
    public function getUserDisputes(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Dispute::where('opened_by', $userId)
            ->with(['booking.residence', 'cancellation'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get disputes for owner's bookings
     */
    public function getOwnerDisputes(int $ownerId): \Illuminate\Database\Eloquent\Collection
    {
        return Dispute::whereHas('booking.residence', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        })
            ->with(['booking.residence', 'initiator'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get disputes assigned to admin
     */
    public function getAssignedDisputes(int $adminId): \Illuminate\Database\Eloquent\Collection
    {
        return Dispute::assignedTo($adminId)
            ->unresolved()
            ->with(['booking.residence', 'initiator'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get unassigned disputes
     */
    public function getUnassignedDisputes(): \Illuminate\Database\Eloquent\Collection
    {
        return Dispute::unassigned()
            ->unresolved()
            ->with(['booking.residence', 'initiator'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get overdue disputes
     */
    public function getOverdueDisputes(): \Illuminate\Database\Eloquent\Collection
    {
        return Dispute::overdue()
            ->with(['booking.residence', 'initiator', 'assignedAdmin'])
            ->orderBy('response_deadline')
            ->get();
    }

    /**
     * Get dispute statistics
     */
    public function getStats(?string $period = 'month'): array
    {
        $query = Dispute::query();

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
        $underReview = (clone $query)->underReview()->count();
        $escalated = (clone $query)->escalated()->count();
        $resolved = (clone $query)->resolved()->count();
        $overdue = (clone $query)->overdue()->count();

        // Resolution stats
        $resolutions = (clone $query)->resolved()
            ->selectRaw('resolution, COUNT(*) as count')
            ->groupBy('resolution')
            ->pluck('count', 'resolution')
            ->toArray();

        // Average resolution time
        $avgResolutionTime = (clone $query)->resolved()
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'total' => $total,
            'open' => $open,
            'under_review' => $underReview,
            'escalated' => $escalated,
            'resolved' => $resolved,
            'overdue' => $overdue,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
            'avg_resolution_hours' => round($avgResolutionTime ?? 0, 1),
            'by_type' => $this->getStatsByType($query),
            'resolutions' => $resolutions,
        ];
    }

    /**
     * Get stats by dispute type
     */
    protected function getStatsByType($query): array
    {
        return (clone $query)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Auto-escalate overdue disputes
     */
    public function autoEscalateOverdue(): int
    {
        $overdue = $this->getOverdueDisputes()
            ->filter(fn ($d) => $d->canBeEscalated());

        foreach ($overdue as $dispute) {
            $dispute->escalate('Escaladé automatiquement - délai de réponse dépassé');
        }

        return $overdue->count();
    }
}
