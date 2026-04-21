<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MaintenanceRequest extends Model
{
    use SoftDeletes;

    public const CAT_PLUMBING    = 'plumbing';
    public const CAT_ELECTRICAL  = 'electrical';
    public const CAT_APPLIANCE   = 'appliance';
    public const CAT_STRUCTURAL  = 'structural';
    public const CAT_CLEANING    = 'cleaning';
    public const CAT_OTHER       = 'other';

    public const CATEGORIES = [
        self::CAT_PLUMBING   => 'Plomberie',
        self::CAT_ELECTRICAL => 'Électricité',
        self::CAT_APPLIANCE  => 'Électroménager',
        self::CAT_STRUCTURAL => 'Structure / Bâtiment',
        self::CAT_CLEANING   => 'Propreté',
        self::CAT_OTHER      => 'Autre',
    ];

    public const PRIORITY_LOW    = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH   = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW    => 'Basse',
        self::PRIORITY_MEDIUM => 'Moyenne',
        self::PRIORITY_HIGH   => 'Haute',
        self::PRIORITY_URGENT => 'Urgente',
    ];

    public const STATUS_REPORTED     = 'reported';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_IN_PROGRESS  = 'in_progress';
    public const STATUS_RESOLVED     = 'resolved';
    public const STATUS_CLOSED       = 'closed';

    public const STATUSES = [
        self::STATUS_REPORTED     => 'Signalé',
        self::STATUS_ACKNOWLEDGED => 'Pris en compte',
        self::STATUS_IN_PROGRESS  => 'En cours',
        self::STATUS_RESOLVED     => 'Résolu',
        self::STATUS_CLOSED       => 'Fermé',
    ];

    protected $fillable = [
        'reference', 'residence_id', 'reported_by', 'owner_id', 'assigned_to',
        'category', 'priority', 'title', 'description', 'photos',
        'status', 'estimated_cost', 'actual_cost',
        'acknowledged_at', 'resolved_at', 'resolution_notes',
        'satisfaction_rating',
    ];

    protected $casts = [
        'photos'              => 'array',
        'estimated_cost'      => 'decimal:2',
        'actual_cost'         => 'decimal:2',
        'acknowledged_at'     => 'datetime',
        'resolved_at'         => 'datetime',
        'satisfaction_rating' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (!$model->reference) {
                $model->reference = 'MNT-'.strtoupper(Str::random(8));
            }
        });
    }

    // ===== RELATIONS =====

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ===== ACCESSORS =====

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW    => 'gray',
            self::PRIORITY_MEDIUM => 'blue',
            self::PRIORITY_HIGH   => 'orange',
            self::PRIORITY_URGENT => 'red',
            default               => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_REPORTED     => 'yellow',
            self::STATUS_ACKNOWLEDGED => 'blue',
            self::STATUS_IN_PROGRESS  => 'orange',
            self::STATUS_RESOLVED     => 'green',
            self::STATUS_CLOSED       => 'gray',
            default                   => 'gray',
        };
    }

    // ===== SCOPES =====

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }
}
