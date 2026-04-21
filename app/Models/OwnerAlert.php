<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerAlert extends Model
{
    public const TYPE_RESPONSE_TIME_SLA = 'response_time_sla';
    public const TYPE_LOW_OCCUPANCY     = 'low_occupancy';
    public const TYPE_PRICE_SUGGESTION  = 'price_suggestion';
    public const TYPE_REVIEW_PENDING    = 'review_pending';
    public const TYPE_DOCUMENT_EXPIRY   = 'document_expiry';
    public const TYPE_HIGH_CONSUMPTION  = 'high_consumption';
    public const TYPE_BOOKING_GAP       = 'booking_gap';

    public const TYPES = [
        self::TYPE_RESPONSE_TIME_SLA => 'Temps de réponse SLA',
        self::TYPE_LOW_OCCUPANCY     => 'Faible occupation',
        self::TYPE_PRICE_SUGGESTION  => 'Suggestion de prix',
        self::TYPE_REVIEW_PENDING    => 'Avis en attente',
        self::TYPE_DOCUMENT_EXPIRY   => 'Document expirant',
        self::TYPE_HIGH_CONSUMPTION  => 'Consommation élevée',
        self::TYPE_BOOKING_GAP       => 'Nuit orpheline',
    ];

    public const SEVERITY_INFO     = 'info';
    public const SEVERITY_WARNING  = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    public const STATUS_ACTIVE       = 'active';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED     = 'resolved';
    public const STATUS_DISMISSED    = 'dismissed';

    protected $fillable = [
        'user_id', 'residence_id', 'alert_type', 'severity',
        'title', 'message', 'metadata', 'status', 'action_url',
        'acknowledged_at', 'resolved_at',
    ];

    protected $casts = [
        'metadata'         => 'array',
        'acknowledged_at'  => 'datetime',
        'resolved_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->alert_type] ?? $this->alert_type;
    }

    public function acknowledge(): void
    {
        $this->update([
            'status'          => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);
    }

    public function resolve(): void
    {
        $this->update([
            'status'      => self::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);
    }

    public function dismiss(): void
    {
        $this->update(['status' => self::STATUS_DISMISSED]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForOwner($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }
}
