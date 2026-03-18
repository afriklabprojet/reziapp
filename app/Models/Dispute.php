<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'booking_id',
        'opened_by',
        'against_user_id',
        'category',
        'priority',
        'title',
        'description',
        'evidence_files',
        'claimed_amount',
        'claim_justification',
        'status',
        'response',
        'response_evidence',
        'responded_at',
        'resolution_type',
        'resolution_details',
        'resolution_amount',
        'resolved_at',
        'assigned_to',
        'assigned_at',
        'response_deadline',
        'resolution_deadline',
    ];

    protected $casts = [
        'evidence_files' => 'array',
        'response_evidence' => 'array',
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'assigned_at' => 'datetime',
        'response_deadline' => 'datetime',
        'resolution_deadline' => 'datetime',
        'claimed_amount' => 'decimal:2',
        'resolution_amount' => 'decimal:2',
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
     * Linked through shared booking_id (disputes table has no cancellation_id column)
     */
    public function cancellation()
    {
        return $this->hasOne(Cancellation::class, 'booking_id', 'booking_id');
    }

    /**
     * User who opened the dispute
     */
    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * Alias for backwards compatibility
     */
    public function initiator()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * User the dispute is against
     */
    public function againstUser()
    {
        return $this->belongsTo(User::class, 'against_user_id');
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
        return match($this->category) {
            'cancellation' => 'Annulation',
            'property_issue' => 'Problème logement',
            'payment' => 'Paiement',
            'host_behavior' => 'Comportement hôte',
            'guest_behavior' => 'Comportement voyageur',
            'refund' => 'Remboursement',
            'other' => 'Autre',
            default => $this->category ?? '',
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
        if (!$this->resolution_type) {
            return null;
        }

        return match($this->resolution_type) {
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
            'resolution_details' => ($this->resolution_details ? $this->resolution_details."\n" : '').'[Escaladé] '.($reason ?? ''),
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
    public function resolve(string $resolutionType, string $details, ?float $amount = null): self
    {
        $this->update([
            'status' => 'resolved',
            'resolution_type' => $resolutionType,
            'resolution_details' => $details,
            'resolution_amount' => $amount,
            'resolved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Close dispute
     */
    public function close(?string $details = null): self
    {
        $this->update([
            'status' => 'closed',
            'resolution_details' => $details ?? $this->resolution_details,
            'resolved_at' => $this->resolved_at ?? now(),
        ]);

        return $this;
    }

    /**
     * Add evidence
     */
    public function addEvidence(array $newEvidence): self
    {
        $evidence = $this->evidence_files ?? [];
        $evidence[] = array_merge($newEvidence, [
            'added_at' => now()->toISOString(),
        ]);

        $this->update(['evidence_files' => $evidence]);

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
