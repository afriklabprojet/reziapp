<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'user_id',
        'check_in',
        'check_out',
        'guests',
        'adults',
        'children',
        'infants',
        'message',
        'special_requests',
        'price_per_night',
        'total_nights',
        'subtotal',
        'cleaning_fee',
        'service_fee',
        'long_stay_discount',
        'promo_discount',
        'total_amount',
        'status', // pending, approved, rejected, expired, converted
        'owner_response',
        'rejected_reason',
        'responded_at',
        'expires_at',
        'booking_id',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'guests' => 'integer',
        'adults' => 'integer',
        'children' => 'integer',
        'infants' => 'integer',
        'special_requests' => 'array',
        'price_per_night' => 'decimal:2',
        'total_nights' => 'integer',
        'subtotal' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'long_stay_discount' => 'decimal:2',
        'promo_discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relations
    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->whereHas('residence', function ($q) use ($ownerId) {
            $q->where('owner_id', $ownerId);
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    // Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canBeApproved(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function approve(?string $response = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'owner_response' => $response,
            'responded_at' => now(),
        ]);

        return true;
    }

    public function reject(string $reason): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'rejected_reason' => $reason,
            'responded_at' => now(),
        ]);

        return true;
    }

    public function markAsConverted(Booking $booking): void
    {
        $this->update([
            'status' => 'converted',
            'booking_id' => $booking->id,
        ]);
    }

    public function markAsExpired(): void
    {
        if ($this->isPending()) {
            $this->update(['status' => 'expired']);
        }
    }

    // Helpers
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'rejected' => 'Refusée',
            'expired' => 'Expirée',
            'converted' => 'Convertie',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'expired' => 'gray',
            'converted' => 'blue',
            default => 'gray',
        };
    }

    public function getTimeRemaining(): ?string
    {
        if (!$this->expires_at || $this->status !== 'pending') {
            return null;
        }

        if ($this->isExpired()) {
            return 'Expirée';
        }

        return $this->expires_at->diffForHumans();
    }

    public function getOwner()
    {
        return $this->residence?->owner;
    }
}
