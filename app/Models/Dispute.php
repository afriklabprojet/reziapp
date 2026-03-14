<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'cancellation_id',
        'initiated_by',
        'initiator_id',
        'type',
        'reason',
        'detailed_description',
        'evidence',
        'status',
        'priority',
        'assigned_to',
        'resolution',
        'resolution_notes',
        'resolved_at',
        'escalated_at',
        'response_deadline',
    ];

    protected $casts = [
        'evidence' => 'array',
        'resolved_at' => 'datetime',
        'escalated_at' => 'datetime',
        'response_deadline' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Booking this dispute is about
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Cancellation this dispute is about (if any)
     */
    public function cancellation()
    {
        return $this->belongsTo(Cancellation::class);
    }

    /**
     * User who initiated the dispute
     */
    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    /**
     * Admin assigned to handle the dispute
     */
    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Support tickets for this dispute
     */
    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    // ===== SCOPES =====

    /**
     * Open disputes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Under review
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    /**
     * Awaiting response
     */
    public function scopeAwaitingResponse($query)
    {
        return $query->where('status', 'awaiting_response');
    }

    /**
     * Escalated disputes
     */
    public function scopeEscalated($query)
    {
        return $query->where('status', 'escalated');
    }

    /**
     * Resolved disputes
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Unresolved disputes
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }

    /**
     * By priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * High priority
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Unassigned
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Assigned to specific admin
     */
    public function scopeAssignedTo($query, $adminId)
    {
        return $query->where('assigned_to', $adminId);
    }

    /**
     * Overdue (past response deadline)
     */
    public function scopeOverdue($query)
    {
        return $query->where('response_deadline', '<', now())
                     ->whereNotIn('status', ['resolved', 'closed']);
    }

    // ===== ACCESSORS =====

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'cancellation' => 'Annulation',
            'property_issue' => 'Problème logement',
            'payment' => 'Paiement',
            'host_behavior' => 'Comportement hôte',
            'guest_behavior' => 'Comportement voyageur',
            'refund' => 'Remboursement',
            'other' => 'Autre',
            default => $this->type,
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open' => 'Ouvert',
            'under_review' => 'En examen',
            'awaiting_response' => 'En attente de réponse',
            'escalated' => 'Escaladé',
            'resolved' => 'Résolu',
            'closed' => 'Fermé',
            default => $this->status,
        };
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'yellow',
            'under_review' => 'blue',
            'awaiting_response' => 'purple',
            'escalated' => 'red',
            'resolved' => 'green',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente',
            default => $this->priority,
        };
    }

    /**
     * Get priority color
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'yellow',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get resolution label
     */
    public function getResolutionLabelAttribute(): ?string
    {
        if (!$this->resolution) {
            return null;
        }

        return match($this->resolution) {
            'favor_guest' => 'En faveur du voyageur',
            'favor_host' => 'En faveur de l\'hôte',
            'partial_refund' => 'Remboursement partiel',
            'full_refund' => 'Remboursement total',
            'no_refund' => 'Pas de remboursement',
            'mutual_agreement' => 'Accord mutuel',
            'dismissed' => 'Rejeté',
            default => $this->resolution,
        };
    }

    /**
     * Check if overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->response_deadline
            && $this->response_deadline->isPast()
            && !in_array($this->status, ['resolved', 'closed']);
    }

    // ===== METHODS =====

    /**
     * Check if dispute is open
     */
    public function isOpen(): bool
    {
        return !in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Check if can be escalated
     */
    public function canBeEscalated(): bool
    {
        return !in_array($this->status, ['escalated', 'resolved', 'closed']);
    }

    /**
     * Assign to admin
     */
    public function assignTo(int $adminId): self
    {
        $this->update([
            'assigned_to' => $adminId,
            'status' => 'under_review',
        ]);

        return $this;
    }

    /**
     * Escalate dispute
     */
    public function escalate(?string $reason = null): self
    {
        $this->update([
            'status' => 'escalated',
            'priority' => 'high',
            'escalated_at' => now(),
            'resolution_notes' => $this->resolution_notes."\n[Escaladé] ".($reason ?? ''),
        ]);

        return $this;
    }

    /**
     * Request response from party
     */
    public function requestResponse(int $hours = 48): self
    {
        $this->update([
            'status' => 'awaiting_response',
            'response_deadline' => now()->addHours($hours),
        ]);

        return $this;
    }

    /**
     * Resolve dispute
     */
    public function resolve(string $resolution, string $notes): self
    {
        $this->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Close dispute
     */
    public function close(?string $notes = null): self
    {
        $this->update([
            'status' => 'closed',
            'resolution_notes' => $notes ?? $this->resolution_notes,
            'resolved_at' => $this->resolved_at ?? now(),
        ]);

        return $this;
    }

    /**
     * Add evidence
     */
    public function addEvidence(array $newEvidence): self
    {
        $evidence = $this->evidence ?? [];
        $evidence[] = array_merge($newEvidence, [
            'added_at' => now()->toISOString(),
        ]);

        $this->update(['evidence' => $evidence]);

        return $this;
    }

    // ===== STATIC HELPERS =====

    /**
     * Get dispute types
     */
    public static function getTypes(): array
    {
        return [
            'cancellation' => 'Litige d\'annulation',
            'property_issue' => 'Problème avec le logement',
            'payment' => 'Problème de paiement',
            'host_behavior' => 'Comportement de l\'hôte',
            'guest_behavior' => 'Comportement du voyageur',
            'refund' => 'Problème de remboursement',
            'other' => 'Autre',
        ];
    }

    /**
     * Get resolutions
     */
    public static function getResolutions(): array
    {
        return [
            'favor_guest' => 'En faveur du voyageur',
            'favor_host' => 'En faveur de l\'hôte',
            'partial_refund' => 'Remboursement partiel',
            'full_refund' => 'Remboursement total',
            'no_refund' => 'Pas de remboursement',
            'mutual_agreement' => 'Accord mutuel',
            'dismissed' => 'Rejeté',
        ];
    }

    /**
     * Get priorities
     */
    public static function getPriorities(): array
    {
        return [
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente',
        ];
    }
}
