<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestScore extends Model
{
    public const RISK_LOW    = 'low';
    public const RISK_MEDIUM = 'medium';
    public const RISK_HIGH   = 'high';

    public const RISK_LEVELS = [
        self::RISK_LOW    => 'Faible risque',
        self::RISK_MEDIUM => 'Risque moyen',
        self::RISK_HIGH   => 'Risque élevé',
    ];

    public const RISK_COLORS = [
        self::RISK_LOW    => 'green',
        self::RISK_MEDIUM => 'yellow',
        self::RISK_HIGH   => 'red',
    ];

    protected $fillable = [
        'user_id', 'total_score', 'identity_score', 'booking_score',
        'review_score', 'seniority_score', 'risk_level',
        'total_bookings', 'completed_bookings', 'cancelled_bookings',
        'cancellation_rate', 'average_owner_rating', 'damage_reports_count',
        'flags', 'last_calculated_at',
    ];

    protected $casts = [
        'total_score'          => 'integer',
        'identity_score'       => 'integer',
        'booking_score'        => 'integer',
        'review_score'         => 'integer',
        'seniority_score'      => 'integer',
        'cancellation_rate'    => 'decimal:2',
        'average_owner_rating' => 'decimal:2',
        'flags'                => 'array',
        'last_calculated_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRiskLabelAttribute(): string
    {
        return self::RISK_LEVELS[$this->risk_level] ?? $this->risk_level;
    }

    public function getRiskColorAttribute(): string
    {
        return self::RISK_COLORS[$this->risk_level] ?? 'gray';
    }

    public function getScorePercentageAttribute(): int
    {
        return min(100, max(0, $this->total_score));
    }

    public function isHighRisk(): bool
    {
        return $this->risk_level === self::RISK_HIGH;
    }

    public function isLowRisk(): bool
    {
        return $this->risk_level === self::RISK_LOW;
    }

    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', self::RISK_HIGH);
    }
}
