<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmartLock extends Model
{
    const PROVIDER_TTLOCK    = 'ttlock';
    const PROVIDER_NUKI      = 'nuki';
    const PROVIDER_AUGUST    = 'august';
    const PROVIDER_IGLOOHOME = 'igloohome';
    const PROVIDER_OTHER     = 'other';

    const PROVIDERS = [
        self::PROVIDER_TTLOCK    => 'TTLock',
        self::PROVIDER_NUKI      => 'Nuki',
        self::PROVIDER_AUGUST    => 'August',
        self::PROVIDER_IGLOOHOME => 'Igloohome',
        self::PROVIDER_OTHER     => 'Autre',
    ];

    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_OFFLINE  = 'offline';
    const STATUS_ERROR    = 'error';

    protected $fillable = [
        'residence_id', 'user_id', 'provider', 'device_id',
        'device_name', 'status', 'credentials', 'last_synced_at',
    ];

    protected $casts = [
        'credentials'   => 'encrypted:array',
        'last_synced_at' => 'datetime',
    ];

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function codes(): HasMany
    {
        return $this->hasMany(SmartLockCode::class);
    }

    public function getProviderLabelAttribute(): string
    {
        return self::PROVIDERS[$this->provider] ?? $this->provider;
    }

    public function isOnline(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }
}
