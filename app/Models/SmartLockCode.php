<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartLockCode extends Model
{
    const TYPE_TEMPORARY = 'temporary';
    const TYPE_PERMANENT = 'permanent';
    const TYPE_ONE_TIME  = 'one_time';

    const STATUS_ACTIVE  = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'smart_lock_id', 'booking_id', 'code', 'code_type', 'status',
        'valid_from', 'valid_until', 'guest_name', 'last_used_at', 'usage_count',
    ];

    protected $casts = [
        'valid_from'   => 'datetime',
        'valid_until'  => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function smartLock(): BelongsTo
    {
        return $this->belongsTo(SmartLock::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }
        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    public function revoke(): void
    {
        $this->update(['status' => self::STATUS_REVOKED]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForBooking($query, int $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }
}
