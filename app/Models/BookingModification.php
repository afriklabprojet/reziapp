<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingModification extends Model
{
    protected $fillable = [
        'booking_id',
        'requested_by_user_id',
        'original_check_in',
        'original_check_out',
        'original_guests',
        'requested_check_in',
        'requested_check_out',
        'requested_guests',
        'price_diff',
        'status',
        'reason',
        'owner_response',
        'responded_at',
    ];

    protected $casts = [
        'original_check_in' => 'date',
        'original_check_out' => 'date',
        'requested_check_in' => 'date',
        'requested_check_out' => 'date',
        'price_diff' => 'decimal:2',
        'responded_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
