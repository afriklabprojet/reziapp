<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleaningTask extends Model
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_VERIFIED    = 'verified';

    public const STATUSES = [
        self::STATUS_PENDING     => 'En attente',
        self::STATUS_IN_PROGRESS => 'En cours',
        self::STATUS_COMPLETED   => 'Terminé',
        self::STATUS_VERIFIED    => 'Vérifié',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING     => 'yellow',
        self::STATUS_IN_PROGRESS => 'blue',
        self::STATUS_COMPLETED   => 'green',
        self::STATUS_VERIFIED    => 'emerald',
    ];

    protected $fillable = [
        'residence_id', 'owner_id', 'assigned_to', 'booking_id',
        'scheduled_date', 'scheduled_time', 'estimated_duration', 'status',
        'priority', 'checklist', 'special_instructions',
        'before_photos', 'after_photos', 'cost', 'notes',
        'completed_at', 'verified_at', 'started_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'checklist'      => 'array',
        'before_photos'  => 'array',
        'after_photos'   => 'array',
        'cost'           => 'decimal:0',
        'completed_at'   => 'datetime',
        'verified_at'    => 'datetime',
    ];

    // ===== RELATIONS =====

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // ===== ACCESSORS =====

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    // ===== HELPERS =====

    public function markCompleted(): void
    {
        $this->update([
            'status'       => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function verify(): void
    {
        $this->update([
            'status'      => self::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    // ===== SCOPES =====

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeUpcoming($query, int $days = 7)
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->whereBetween('scheduled_date', [now(), now()->addDays($days)]);
    }

    public function scopeForResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }
}
