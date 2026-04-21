<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacationMode extends Model
{
    protected $fillable = [
        'owner_id', 'start_date', 'end_date', 'auto_message',
        'affected_residences', 'is_active', 'activated_at', 'deactivated_at',
    ];

    protected $casts = [
        'start_date'           => 'date',
        'end_date'             => 'date',
        'affected_residences'  => 'array',
        'is_active'            => 'boolean',
        'activated_at'         => 'datetime',
        'deactivated_at'       => 'datetime',
    ];

    // ===== RELATIONS =====

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // ===== HELPERS =====

    public function activate(): void
    {
        $this->update([
            'is_active'    => true,
            'activated_at' => now(),
            'deactivated_at' => null,
        ]);
    }

    public function deactivate(): void
    {
        $this->update([
            'is_active'      => false,
            'deactivated_at' => now(),
        ]);
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && $this->start_date->lte(now())
            && $this->end_date->gte(now());
    }

    public function daysRemaining(): int
    {
        if (!$this->isCurrentlyActive()) {
            return 0;
        }

        return (int) now()->diffInDays($this->end_date, false);
    }

    public function affectsResidence(int $residenceId): bool
    {
        $affected = $this->affected_residences ?? [];

        return empty($affected) || in_array($residenceId, $affected);
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }
}
