<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DamageReport extends Model
{
    use SoftDeletes;

    const CATEGORY_FURNITURE   = 'furniture';
    const CATEGORY_APPLIANCE   = 'appliance';
    const CATEGORY_PLUMBING    = 'plumbing';
    const CATEGORY_ELECTRICAL  = 'electrical';
    const CATEGORY_STRUCTURAL  = 'structural';
    const CATEGORY_COSMETIC    = 'cosmetic';
    const CATEGORY_OTHER       = 'other';

    const CATEGORIES = [
        self::CATEGORY_FURNITURE  => 'Mobilier',
        self::CATEGORY_APPLIANCE  => 'Électroménager',
        self::CATEGORY_PLUMBING   => 'Plomberie',
        self::CATEGORY_ELECTRICAL => 'Électricité',
        self::CATEGORY_STRUCTURAL => 'Structure',
        self::CATEGORY_COSMETIC   => 'Cosmétique',
        self::CATEGORY_OTHER      => 'Autre',
    ];

    const SEVERITY_MINOR    = 'minor';
    const SEVERITY_MODERATE = 'moderate';
    const SEVERITY_MAJOR    = 'major';
    const SEVERITY_CRITICAL = 'critical';

    const SEVERITIES = [
        self::SEVERITY_MINOR    => 'Mineur',
        self::SEVERITY_MODERATE => 'Modéré',
        self::SEVERITY_MAJOR    => 'Majeur',
        self::SEVERITY_CRITICAL => 'Critique',
    ];

    const STATUS_REPORTED         = 'reported';
    const STATUS_ASSESSED         = 'assessed';
    const STATUS_REPAIR_SCHEDULED = 'repair_scheduled';
    const STATUS_REPAIRED         = 'repaired';
    const STATUS_DEDUCTED         = 'deducted';
    const STATUS_CLOSED           = 'closed';

    const STATUSES = [
        self::STATUS_REPORTED         => 'Signalé',
        self::STATUS_ASSESSED         => 'Évalué',
        self::STATUS_REPAIR_SCHEDULED => 'Réparation planifiée',
        self::STATUS_REPAIRED         => 'Réparé',
        self::STATUS_DEDUCTED         => 'Déduit du dépôt',
        self::STATUS_CLOSED           => 'Clôturé',
    ];

    const SEVERITY_COLORS = [
        self::SEVERITY_MINOR    => 'green',
        self::SEVERITY_MODERATE => 'yellow',
        self::SEVERITY_MAJOR    => 'orange',
        self::SEVERITY_CRITICAL => 'red',
    ];

    protected $fillable = [
        'reference', 'residence_id', 'booking_id', 'reported_by',
        'security_deposit_id', 'title', 'description', 'category',
        'severity', 'estimated_cost', 'actual_cost', 'deducted_amount',
        'photos', 'status', 'assessed_at', 'repaired_at', 'repair_notes',
    ];

    protected $casts = [
        'photos'          => 'array',
        'estimated_cost'  => 'decimal:0',
        'actual_cost'     => 'decimal:0',
        'deducted_amount' => 'decimal:0',
        'assessed_at'     => 'datetime',
        'repaired_at'     => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (empty($report->reference)) {
                $report->reference = 'DMG-' . date('Y') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getSeverityLabelAttribute(): string
    {
        return self::SEVERITIES[$this->severity] ?? $this->severity;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSeverityColorAttribute(): string
    {
        return self::SEVERITY_COLORS[$this->severity] ?? 'gray';
    }

    public function scopeForResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    public function scopeForOwner($query, int $userId)
    {
        return $query->whereHas('residence', fn ($q) => $q->where('owner_id', $userId));
    }
}
