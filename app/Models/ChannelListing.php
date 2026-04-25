<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelListing extends Model
{
    protected $fillable = [
        'residence_id',
        'channel',
        'external_id',
        'is_active',
        'last_sync_at',
        'sync_status',
        'sync_message',
        'sync_metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'sync_metadata' => 'array',
    ];

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function channelLabel(): string
    {
        return match ($this->channel) {
            'airbnb' => 'Airbnb',
            'booking' => 'Booking.com',
            'expedia' => 'Expedia',
            'vrbo' => 'Vrbo',
            default => ucfirst($this->channel),
        };
    }

    public function statusBadge(): string
    {
        return match ($this->sync_status) {
            'success' => 'bg-emerald-100 text-emerald-800',
            'syncing' => 'bg-blue-100 text-blue-800',
            'error' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
