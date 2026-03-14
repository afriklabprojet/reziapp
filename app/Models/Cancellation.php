<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'initiated_by',
        'initiated_by_user_id',
        'reason_category',
        'reason_details',
        'days_before_checkin',
        'refund_percent_applied',
        'original_amount',
        'refund_amount',
        'penalty_amount',
        'service_fee_refunded',
        'status',
        'owner_penalty_applied',
        'owner_penalty_amount',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'processed_at',
    ];

    protected $casts = [
        'refund_percent_applied' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'service_fee_refunded' => 'decimal:2',
        'owner_penalty_amount' => 'decimal:2',
        'owner_penalty_applied' => 'boolean',
        'reviewed_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Booking that was cancelled
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * User who cancelled (if user/owner)
     */
    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    /**
     * Refunds for this cancellation
     */
    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Disputes related to this cancellation (via booking)
     */
    public function disputes()
    {
        return $this->hasMany(Dispute::class, 'booking_id', 'booking_id');
    }

    // ===== SCOPES =====

    /**
     * Pending cancellations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Approved cancellations
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Processed cancellations
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * By guest
     */
    public function scopeByGuest($query)
    {
        return $query->where('initiated_by', 'user');
    }

    /**
     * By owner
     */
    public function scopeByOwner($query)
    {
        return $query->where('initiated_by', 'owner');
    }

    /**
     * By admin
     */
    public function scopeByAdmin($query)
    {
        return $query->where('initiated_by', 'admin');
    }

    // ===== ACCESSORS =====

    /**
     * Get cancelled by label
     */
    public function getCancelledByLabelAttribute(): string
    {
        return match($this->initiated_by) {
            'user' => 'Voyageur',
            'owner' => 'Propriétaire',
            'admin' => 'Administration',
            'system' => 'Système',
            default => $this->initiated_by,
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'processed' => 'Traitée',
            'rejected' => 'Refusée',
            default => $this->status,
        };
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'processed' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get reason label
     */
    public function getReasonLabelAttribute(): string
    {
        return match($this->reason_category) {
            'change_of_plans' => 'Changement de plans',
            'found_alternative' => 'Trouvé une alternative',
            'emergency' => 'Urgence',
            'property_issue' => 'Problème avec le logement',
            'host_issue' => 'Problème avec l\'hôte',
            'double_booking' => 'Double réservation',
            'property_unavailable' => 'Logement indisponible',
            'guest_issue' => 'Problème avec le voyageur',
            'maintenance' => 'Maintenance requise',
            'force_majeure' => 'Force majeure',
            'policy_violation' => 'Violation des règles',
            'fraud_suspected' => 'Fraude suspectée',
            'other' => 'Autre',
            default => $this->reason_category,
        };
    }

    // ===== METHODS =====

    /**
     * Check if was cancelled by guest
     */
    public function wasCancelledByGuest(): bool
    {
        return $this->initiated_by === 'user';
    }

    /**
     * Check if was cancelled by owner
     */
    public function wasCancelledByOwner(): bool
    {
        return $this->initiated_by === 'owner';
    }

    /**
     * Check if was cancelled by admin
     */
    public function wasCancelledByAdmin(): bool
    {
        return $this->initiated_by === 'admin';
    }

    /**
     * Check if refund is due
     */
    public function hasRefundDue(): bool
    {
        return $this->refund_amount > 0
            && $this->status !== 'rejected';
    }

    /**
     * Check if penalty applies
     */
    public function hasPenalty(): bool
    {
        return $this->penalty_amount > 0;
    }

    /**
     * Get total refunded amount
     */
    public function getTotalRefundedAttribute(): float
    {
        return (float) $this->refunds()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Check if fully refunded
     */
    public function isFullyRefunded(): bool
    {
        return $this->total_refunded >= $this->refund_amount;
    }

    /**
     * Get pending refund amount
     */
    public function getPendingRefundAmount(): float
    {
        return max(0, $this->refund_amount - $this->total_refunded);
    }

    /**
     * Approve cancellation
     */
    public function approve(?string $adminNotes = null): self
    {
        $this->update([
            'status' => 'approved',
            'admin_notes' => $adminNotes,
        ]);

        return $this;
    }

    /**
     * Mark as processed
     */
    public function markProcessed(): self
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Reject cancellation
     */
    public function reject(string $adminNotes): self
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $adminNotes,
        ]);

        return $this;
    }

    /**
     * Check if can be disputed
     */
    public function canBeDisputed(): bool
    {
        return in_array($this->status, ['approved', 'processed'])
            && $this->created_at->diffInDays(now()) <= 14;
    }

    // ===== STATIC HELPERS =====

    /**
     * Get guest cancellation reasons
     */
    public static function getGuestReasons(): array
    {
        return [
            'change_of_plans' => 'Changement de plans',
            'found_alternative' => 'Trouvé une alternative',
            'emergency' => 'Urgence personnelle',
            'property_issue' => 'Problème avec le logement',
            'host_issue' => 'Problème avec l\'hôte',
            'other' => 'Autre raison',
        ];
    }

    /**
     * Get owner cancellation reasons
     */
    public static function getOwnerReasons(): array
    {
        return [
            'double_booking' => 'Double réservation',
            'property_unavailable' => 'Logement indisponible',
            'guest_issue' => 'Problème avec le voyageur',
            'maintenance' => 'Travaux/Maintenance',
            'emergency' => 'Urgence personnelle',
            'force_majeure' => 'Force majeure',
            'other' => 'Autre raison',
        ];
    }

    /**
     * Get admin cancellation reasons
     */
    public static function getAdminReasons(): array
    {
        return [
            'policy_violation' => 'Violation des conditions',
            'fraud_suspected' => 'Fraude suspectée',
            'force_majeure' => 'Force majeure',
            'dispute_resolution' => 'Résolution de litige',
            'other' => 'Autre raison',
        ];
    }
}
