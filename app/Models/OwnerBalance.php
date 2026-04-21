<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerBalance extends Model
{
    protected $fillable = [
        'user_id',
        'available_balance',
        'pending_balance',
        'total_earned',
        'total_withdrawn',
        'currency',
        'last_payout_at',
    ];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'last_payout_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ===== ACCESSORS =====

    public function getFormattedAvailableAttribute(): string
    {
        return number_format($this->available_balance, 0, ',', ' ').' '.$this->currency;
    }

    public function getFormattedPendingAttribute(): string
    {
        return number_format($this->pending_balance, 0, ',', ' ').' '.$this->currency;
    }

    public function getFormattedTotalEarnedAttribute(): string
    {
        return number_format($this->total_earned, 0, ',', ' ').' '.$this->currency;
    }

    public function getFormattedTotalWithdrawnAttribute(): string
    {
        return number_format($this->total_withdrawn, 0, ',', ' ').' '.$this->currency;
    }

    public function getTotalBalanceAttribute(): float
    {
        return $this->available_balance + $this->pending_balance;
    }

    public function getFormattedTotalBalanceAttribute(): string
    {
        return number_format($this->total_balance, 0, ',', ' ').' '.$this->currency;
    }

    // ===== METHODS =====

    /**
     * Ajouter des gains (en attente)
     */
    public function addPendingEarnings(float $amount): void
    {
        $this->increment('pending_balance', $amount);
        $this->increment('total_earned', $amount);
    }

    /**
     * Convertir les gains en attente en disponibles
     */
    public function releasePendingEarnings(float $amount): void
    {
        $releaseAmount = min($amount, $this->pending_balance);

        $this->decrement('pending_balance', $releaseAmount);
        $this->increment('available_balance', $releaseAmount);
    }

    /**
     * Retirer du solde disponible (pour payout)
     */
    public function withdraw(float $amount): bool
    {
        if ($amount > $this->available_balance) {
            return false;
        }

        $this->decrement('available_balance', $amount);
        $this->increment('total_withdrawn', $amount);
        $this->update(['last_payout_at' => now()]);

        return true;
    }

    /**
     * Annuler des gains (en cas d'annulation)
     */
    public function cancelEarnings(float $amount, bool $isPending = true): void
    {
        if ($isPending) {
            $this->decrement('pending_balance', min($amount, $this->pending_balance));
        } else {
            $this->decrement('available_balance', min($amount, $this->available_balance));
        }
        $this->decrement('total_earned', $amount);
    }

    /**
     * Vérifier si un retrait est possible
     */
    public function canWithdraw(float $amount): bool
    {
        return $this->available_balance >= $amount;
    }

    /**
     * Obtenir ou créer le solde d'un propriétaire
     */
    public static function getOrCreateForUser(int $userId, string $currency = 'XOF'): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            ['currency' => $currency],
        );
    }
}
