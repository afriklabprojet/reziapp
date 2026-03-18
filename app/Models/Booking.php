<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'idempotency_key',
        'user_id',
        'residence_id',
        'cancellation_policy_id',
        'promo_code_id',
        'reference',
        'check_in',
        'check_out',
        'check_in_time',
        'check_out_time',
        'nights',
        'guests',
        'adults',
        'children',
        'infants',
        'booking_type',
        'price_per_night',
        'subtotal',
        'cleaning_fee',
        'service_fee',
        'long_stay_discount',
        'promo_discount',
        'taxes',
        'discount_amount',
        'coupon_code',
        'coupon_id',
        'coupon_discount',
        'total_amount',
        'currency',
        'price_breakdown',
        'payment_status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'status',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'guest_message',
        'owner_notes',
        'host_notes',
        'internal_notes',
        'total_discount',
        'security_deposit',
        'deposit_status',
        'special_requests',
        'confirmed_at',
        'completed_at',
        'owner_response_deadline',
        'actual_check_in',
        'actual_check_out',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'guests' => 'integer',
        'nights' => 'integer',
        'price_per_night' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'taxes' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'long_stay_discount' => 'decimal:2',
        'promo_discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'price_breakdown' => 'array',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'owner_response_deadline' => 'datetime',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Guest who made the booking
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user
     */
    public function guest()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Residence booked
     */
    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Owner of the residence
     */
    public function owner()
    {
        return $this->hasOneThrough(
            User::class,
            Residence::class,
            'id', // Foreign key on residences
            'id', // Foreign key on users
            'residence_id', // Local key on bookings
            'owner_id', // Local key on residences
        );
    }

    /**
     * Cancellation policy applied to this booking
     */
    public function cancellationPolicy()
    {
        return $this->belongsTo(CancellationPolicy::class);
    }

    /**
     * Coupon propriétaire appliqué
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Cancellation if any
     */
    public function cancellation()
    {
        return $this->hasOne(Cancellation::class);
    }

    /**
     * Refunds for this booking
     */
    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Disputes for this booking
     */
    public function disputes()
    {
        return $this->hasMany(Dispute::class);
    }

    /**
     * Support tickets for this booking
     */
    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Review for this booking
     */
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // ===== SCOPES =====

    /**
     * Pending bookings
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Confirmed bookings
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Active bookings (confirmed and not cancelled)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Cancelled bookings
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Completed bookings
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Upcoming bookings
     */
    public function scopeUpcoming($query)
    {
        return $query->where('check_in', '>', now())
                     ->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Past bookings
     */
    public function scopePast($query)
    {
        return $query->where('check_out', '<', now());
    }

    /**
     * Current/ongoing bookings
     */
    public function scopeOngoing($query)
    {
        return $query->where('check_in', '<=', now())
                     ->where('check_out', '>=', now())
                     ->where('status', 'confirmed');
    }

    /**
     * By guest
     */
    public function scopeForGuest($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * By owner
     */
    public function scopeForOwner($query, $ownerId)
    {
        return $query->whereHas('residence', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        });
    }

    // ===== ACCESSORS =====

    /**
     * Get hours until check-in
     */
    public function getHoursUntilCheckinAttribute(): int
    {
        if ($this->check_in->isPast()) {
            return 0;
        }

        return (int) now()->diffInHours($this->check_in->setTime(15, 0), false);
    }

    /**
     * Get days until check-in
     */
    public function getDaysUntilCheckinAttribute(): int
    {
        if ($this->check_in->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->check_in, false);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'cancelled' => 'Annulée',
            'completed' => 'Terminée',
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
            'confirmed' => 'green',
            'cancelled' => 'red',
            'completed' => 'blue',
            'rejected' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get payment status label
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'En attente',
            'paid' => 'Payé',
            'partially_refunded' => 'Partiellement remboursé',
            'refunded' => 'Remboursé',
            'failed' => 'Échoué',
            default => $this->payment_status,
        };
    }

    // ===== METHODS =====

    /**
     * Check if the booking has been paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' || $this->status === 'completed';
    }

    /**
     * Check if booking can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'])
            && $this->check_in->isFuture();
    }

    /**
     * Check if booking can be cancelled by guest
     */
    public function canBeCancelledByGuest(): bool
    {
        return $this->canBeCancelled();
    }

    /**
     * Check if booking can be cancelled by owner
     */
    public function canBeCancelledByOwner(): bool
    {
        return $this->canBeCancelled();
    }

    /**
     * Check if booking is modifiable
     */
    public function isModifiable(): bool
    {
        return $this->status === 'pending'
            || ($this->status === 'confirmed' && $this->days_until_checkin > 7);
    }

    /**
     * Check if review can be left
     */
    public function canBeReviewed(): bool
    {
        return $this->status === 'completed'
            && !$this->review
            && $this->check_out->diffInDays(now()) <= 14;
    }

    /**
     * Get cancellation policy
     */
    public function getCancellationPolicy(): ?CancellationPolicy
    {
        return $this->residence->cancellationPolicy;
    }

    /**
     * Calculate refund amount if cancelled now
     */
    public function calculateRefundAmount(): float
    {
        $policy = $this->getCancellationPolicy();
        if (!$policy) {
            return 0;
        }

        return $policy->calculateRefund($this->total_amount, $this->hours_until_checkin);
    }

    /**
     * Get refund percentage if cancelled now
     */
    public function getRefundPercentage(): int
    {
        $policy = $this->getCancellationPolicy();
        if (!$policy) {
            return 0;
        }

        return $policy->getRefundPercentage($this->hours_until_checkin);
    }

    /**
     * Mark as confirmed
     */
    public function confirm(): self
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as completed
     */
    public function complete(): self
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as cancelled
     */
    public function markCancelled(): self
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return $this;
    }

    /**
     * Check if has dispute
     */
    public function hasActiveDispute(): bool
    {
        return $this->disputes()
            ->whereNotIn('status', ['resolved', 'closed'])
            ->exists();
    }

    /**
     * Generate booking reference
     */
    public static function generateReference(): string
    {
        return 'RZI-'.strtoupper(substr(md5(uniqid()), 0, 8));
    }
}
