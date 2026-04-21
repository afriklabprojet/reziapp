<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class IcalFeed extends Model
{
    public const PLATFORM_AIRBNB   = 'airbnb';
    public const PLATFORM_BOOKING  = 'booking';
    public const PLATFORM_EXPEDIA  = 'expedia';
    public const PLATFORM_OTHER    = 'other';

    public const PLATFORMS = [
        self::PLATFORM_AIRBNB  => 'Airbnb',
        self::PLATFORM_BOOKING => 'Booking.com',
        self::PLATFORM_EXPEDIA => 'Expedia',
        self::PLATFORM_OTHER   => 'Autre',
    ];

    public const SYNC_STATUS_PENDING = 'pending';
    public const SYNC_STATUS_SYNCING = 'syncing';
    public const SYNC_STATUS_SYNCED  = 'synced';
    public const SYNC_STATUS_ERROR   = 'error';

    protected $fillable = [
        'residence_id', 'user_id', 'name', 'platform', 'import_url',
        'export_token', 'sync_status', 'last_synced_at', 'last_error',
        'imported_events_count', 'auto_sync', 'sync_interval_minutes',
    ];

    protected $casts = [
        'auto_sync'      => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($feed) {
            if (empty($feed->export_token)) {
                $feed->export_token = Str::random(64);
            }
        });
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(IcalBlockedDate::class);
    }

    public function getPlatformLabelAttribute(): string
    {
        return self::PLATFORMS[$this->platform] ?? $this->platform;
    }

    /**
     * Générer l'URL d'export public iCal
     */
    public function getExportUrlAttribute(): string
    {
        return url("/ical/{$this->export_token}.ics");
    }

    public function scopeForResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    public function scopeAutoSync($query)
    {
        return $query->where('auto_sync', true);
    }

    public function scopeNeedsSync($query)
    {
        return $query->autoSync()
            ->where(function ($q) {
                $q->whereNull('last_synced_at')
                    ->orWhereRaw('last_synced_at < DATE_SUB(NOW(), INTERVAL sync_interval_minutes MINUTE)');
            });
    }
}
