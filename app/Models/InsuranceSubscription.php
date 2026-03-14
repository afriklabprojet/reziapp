<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceSubscription extends Model
{
    const TYPE_BASIC    = 'basic';
    const TYPE_STANDARD = 'standard';
    const TYPE_PREMIUM  = 'premium';

    const COVERAGE_TYPES = [
        self::TYPE_BASIC    => 'Basique',
        self::TYPE_STANDARD => 'Standard',
        self::TYPE_PREMIUM  => 'Premium',
    ];

    const STATUS_ACTIVE    = 'active';
    const STATUS_EXPIRED   = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_ACTIVE    => 'Actif',
        self::STATUS_EXPIRED   => 'Expiré',
        self::STATUS_CANCELLED => 'Annulé',
    ];

    const STATUS_COLORS = [
        self::STATUS_ACTIVE    => 'green',
        self::STATUS_EXPIRED   => 'red',
        self::STATUS_CANCELLED => 'gray',
    ];

    protected $fillable = [
        'owner_id', 'residence_id', 'provider', 'policy_number',
        'coverage_type', 'status', 'monthly_premium', 'start_date',
        'end_date', 'coverage_details', 'auto_renew',
    ];

    protected $casts = [
        'monthly_premium'  => 'decimal:0',
        'start_date'       => 'date',
        'end_date'         => 'date',
        'coverage_details' => 'array',
        'auto_renew'       => 'boolean',
    ];

    // ===== RELATIONS =====

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    // ===== ACCESSORS =====

    public function getCoverageTypeLabelAttribute(): string
    {
        return self::COVERAGE_TYPES[$this->coverage_type] ?? $this->coverage_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getAnnualCostAttribute(): float
    {
        return (float) $this->monthly_premium * 12;
    }

    // ===== HELPERS =====

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->isActive()
            && $this->end_date
            && $this->end_date->diffInDays(now()) <= $days
            && $this->end_date->isFuture();
    }

    // ===== SCOPES =====

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }
}
