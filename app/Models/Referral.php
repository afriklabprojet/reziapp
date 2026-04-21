<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'referrer_id',
        'referred_id',
        'status',
        'qualified_at',
        'rewarded_at',
        'referrer_reward',
        'referred_reward',
        'reward_type',
        'notes',
    ];

    protected $casts = [
        'qualified_at' => 'datetime',
        'rewarded_at' => 'datetime',
        'referrer_reward' => 'decimal:2',
        'referred_reward' => 'decimal:2',
    ];

    // Relations
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }

    public function scopeRewarded($query)
    {
        return $query->where('status', 'rewarded');
    }

    // Actions
    public function qualify(): void
    {
        $this->update([
            'status' => 'qualified',
            'qualified_at' => now(),
        ]);
    }

    public function reward(float $referrerReward, float $referredReward, string $type = 'credit'): void
    {
        $this->update([
            'status' => 'rewarded',
            'rewarded_at' => now(),
            'referrer_reward' => $referrerReward,
            'referred_reward' => $referredReward,
            'reward_type' => $type,
        ]);

        // Créditer le parrain
        if ($type === 'credit' && $referrerReward > 0) {
            $this->referrer->increment('referral_balance', $referrerReward);
        }

        // Créditer le filleul
        if ($type === 'credit' && $referredReward > 0) {
            $this->referred->increment('referral_balance', $referredReward);
        }
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    // Status helpers
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'qualified' => 'Qualifié',
            'rewarded' => 'Récompensé',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'qualified' => 'blue',
            'rewarded' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }
}
