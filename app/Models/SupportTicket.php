<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'dispute_id',
        'ticket_number',
        'category',
        'subject',
        'priority',
        'status',
        'assigned_to',
        'first_response_at',
        'resolved_at',
        'satisfaction_rating',
        'satisfaction_comment',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'satisfaction_rating' => 'integer',
    ];

    // ===== BOOT =====

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (!$ticket->ticket_number) {
                $ticket->ticket_number = static::generateTicketNumber();
            }
        });
    }

    // ===== RELATIONSHIPS =====

    /**
     * User who created the ticket
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Related booking (if any)
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Related dispute (if any)
     */
    public function dispute()
    {
        return $this->belongsTo(Dispute::class);
    }

    /**
     * Admin assigned to this ticket
     */
    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Messages in this ticket
     */
    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    /**
     * Latest message
     */
    public function latestMessage()
    {
        return $this->hasOne(SupportMessage::class, 'ticket_id')->latest();
    }

    // ===== SCOPES =====

    /**
     * Open tickets
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * In progress tickets
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Waiting on customer
     */
    public function scopeWaitingOnCustomer($query)
    {
        return $query->where('status', 'waiting_on_customer');
    }

    /**
     * Resolved tickets
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Closed tickets
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Unresolved (active) tickets
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }

    /**
     * By category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * By priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
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
     * For user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * High priority
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Needs attention (no first response yet)
     */
    public function scopeNeedsFirstResponse($query)
    {
        return $query->whereNull('first_response_at')
                     ->where('status', '!=', 'closed');
    }

    // ===== ACCESSORS =====

    /**
     * Get category label
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'booking' => 'Réservation',
            'payment' => 'Paiement',
            'cancellation' => 'Annulation',
            'refund' => 'Remboursement',
            'property' => 'Logement',
            'account' => 'Compte',
            'technical' => 'Technique',
            'other' => 'Autre',
            default => $this->category,
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open' => 'Ouvert',
            'in_progress' => 'En cours',
            'waiting_on_customer' => 'En attente client',
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
            'in_progress' => 'blue',
            'waiting_on_customer' => 'purple',
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
     * Get response time (first response)
     */
    public function getResponseTimeAttribute(): ?int
    {
        if (!$this->first_response_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->first_response_at);
    }

    /**
     * Get formatted response time
     */
    public function getFormattedResponseTimeAttribute(): ?string
    {
        $minutes = $this->response_time;
        if (!$minutes) {
            return null;
        }

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours < 24) {
            return "{$hours}h".($mins > 0 ? " {$mins}min" : '');
        }

        $days = floor($hours / 24);
        $hrs = $hours % 24;

        return "{$days}j".($hrs > 0 ? " {$hrs}h" : '');
    }

    // ===== METHODS =====

    /**
     * Check if ticket is active
     */
    public function isActive(): bool
    {
        return !in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Check if needs first response
     */
    public function needsFirstResponse(): bool
    {
        return !$this->first_response_at && $this->isActive();
    }

    /**
     * Assign to admin
     */
    public function assignTo(int $adminId): self
    {
        $this->update([
            'assigned_to' => $adminId,
            'status' => 'in_progress',
        ]);

        return $this;
    }

    /**
     * Mark first response
     */
    public function markFirstResponse(): self
    {
        if (!$this->first_response_at) {
            $this->update(['first_response_at' => now()]);
        }

        return $this;
    }

    /**
     * Set waiting on customer
     */
    public function waitOnCustomer(): self
    {
        $this->update(['status' => 'waiting_on_customer']);

        return $this;
    }

    /**
     * Resolve ticket
     */
    public function resolve(): self
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Close ticket
     */
    public function close(): self
    {
        $this->update([
            'status' => 'closed',
            'resolved_at' => $this->resolved_at ?? now(),
        ]);

        return $this;
    }

    /**
     * Reopen ticket
     */
    public function reopen(): self
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
        ]);

        return $this;
    }

    /**
     * Add satisfaction rating
     */
    public function rate(int $rating, ?string $comment = null): self
    {
        $this->update([
            'satisfaction_rating' => $rating,
            'satisfaction_comment' => $comment,
        ]);

        return $this;
    }

    /**
     * Add message
     */
    public function addMessage(int $userId, string $message, ?array $attachments = null, bool $isInternal = false): SupportMessage
    {
        $supportMessage = $this->messages()->create([
            'user_id' => $userId,
            'message' => $message,
            'attachments' => $attachments,
            'is_internal_note' => $isInternal,
        ]);

        // Update ticket status if customer replied
        if ($this->status === 'waiting_on_customer' && $userId === $this->user_id) {
            $this->update(['status' => 'in_progress']);
        }

        return $supportMessage;
    }

    // ===== STATIC HELPERS =====

    /**
     * Generate ticket number
     */
    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $date = now()->format('ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Get categories
     */
    public static function getCategories(): array
    {
        return [
            'booking' => 'Réservation',
            'payment' => 'Paiement',
            'cancellation' => 'Annulation',
            'refund' => 'Remboursement',
            'property' => 'Logement',
            'account' => 'Compte',
            'technical' => 'Technique',
            'other' => 'Autre',
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
