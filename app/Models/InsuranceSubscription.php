<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InsuranceSubscription extends Model
{
    public const TYPE_BASIC    = 'basic';
    public const TYPE_STANDARD = 'standard';
    public const TYPE_PREMIUM  = 'premium';

    public const COVERAGE_TYPES = [
        self::TYPE_BASIC    => 'Basique',
        self::TYPE_STANDARD => 'Standard',
        self::TYPE_PREMIUM  => 'Premium',
    ];

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_ACTIVE    => 'Actif',
        self::STATUS_EXPIRED   => 'Expiré',
        self::STATUS_CANCELLED => 'Annulé',
    ];

    public const STATUS_COLORS = [
        self::STATUS_ACTIVE    => 'green',
        self::STATUS_EXPIRED   => 'red',
        self::STATUS_CANCELLED => 'gray',
    ];

    protected $fillable = [
        'owner_id', 'residence_id', 'provider', 'policy_number',
        'external_policy_ref', 'coverage_type', 'status', 'monthly_premium',
        'suggested_premium', 'currency', 'start_date', 'end_date',
        'coverage_details', 'auto_renew', 'risk_score', 'risk_factors',
        'claim_count', 'cancellation_reason', 'cancelled_at', 'renewed_from_id',
    ];

    protected $casts = [
        'monthly_premium'   => 'decimal:0',
        'suggested_premium' => 'decimal:0',
        'start_date'        => 'date',
        'end_date'          => 'date',
        'coverage_details'  => 'array',
        'risk_factors'      => 'array',
        'auto_renew'        => 'boolean',
        'claim_count'       => 'integer',
        'risk_score'        => 'integer',
        'cancelled_at'      => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $sub) {
            if (empty($sub->policy_number)) {
                $sub->policy_number = 'REZI-'.date('Y').'-'.strtoupper(Str::random(8));
            }
        });
    }

    // ===== RELATIONS =====

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'renewed_from_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InsuranceEvent::class, 'eventable_id')
            ->where('eventable_type', self::class)
            ->orderByDesc('created_at');
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

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->end_date || !$this->isActive()) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->end_date, false));
    }

    public function getRiskGradeAttribute(): string
    {
        if (!$this->risk_score) {
            return '—';
        }
        if ($this->risk_score <= 20) {
            return 'A';
        }
        if ($this->risk_score <= 35) {
            return 'B';
        }
        if ($this->risk_score <= 50) {
            return 'C';
        }
        if ($this->risk_score <= 65) {
            return 'D';
        }

        return 'E';
    }

    public function getRiskColorAttribute(): string
    {
        if (!$this->risk_score) {
            return 'gray';
        }
        if ($this->risk_score <= 20) {
            return 'green';
        }
        if ($this->risk_score <= 35) {
            return 'emerald';
        }
        if ($this->risk_score <= 50) {
            return 'yellow';
        }
        if ($this->risk_score <= 65) {
            return 'orange';
        }

        return 'red';
    }

    public function getRiskLabelAttribute(): string
    {
        if (!$this->risk_score) {
            return 'Non calculé';
        }
        if ($this->risk_score <= 20) {
            return 'Très faible';
        }
        if ($this->risk_score <= 35) {
            return 'Faible';
        }
        if ($this->risk_score <= 50) {
            return 'Modéré';
        }
        if ($this->risk_score <= 65) {
            return 'Élevé';
        }

        return 'Très élevé';
    }

    public function getPremiumVarianceAttribute(): int
    {
        if (!$this->suggested_premium || !$this->monthly_premium) {
            return 0;
        }

        return (int)((float)$this->monthly_premium - (float)$this->suggested_premium);
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

    public function canBeRenewed(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_EXPIRED]);
    }

    public function incrementClaimCount(): void
    {
        $this->increment('claim_count');
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
