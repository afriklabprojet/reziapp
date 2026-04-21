<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'status',
        'billing_cycle',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'cancellation_reason',
        'amount',
        'payment_method',
        'payment_reference',
        'auto_renew',
        'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'amount' => 'decimal:2',
        'auto_renew' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * L'utilisateur propriétaire de l'abonnement
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le plan d'abonnement
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Les paiements liés à cet abonnement
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Abonnements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Abonnements en période d'essai
     */
    public function scopeTrialing($query)
    {
        return $query->where('status', 'trialing')
            ->where('trial_ends_at', '>', now());
    }

    /**
     * Vérifier si l'abonnement est actif
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']) &&
               $this->current_period_end > now();
    }

    /**
     * Vérifier si en période d'essai
     */
    public function onTrial(): bool
    {
        return $this->status === 'trialing' &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Vérifier si l'abonnement est expiré
     */
    public function isExpired(): bool
    {
        return $this->current_period_end < now();
    }

    /**
     * Vérifier si l'abonnement est annulé
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || $this->cancelled_at !== null;
    }

    /**
     * Jours restants
     */
    public function daysRemaining(): int
    {
        if ($this->onTrial()) {
            return max(0, now()->diffInDays($this->trial_ends_at, false));
        }

        return max(0, now()->diffInDays($this->current_period_end, false));
    }

    /**
     * Annuler l'abonnement
     */
    public function cancel(?string $reason = null): bool
    {
        $this->update([
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);

        // L'abonnement reste actif jusqu'à la fin de la période
        return true;
    }

    /**
     * Annuler immédiatement
     */
    public function cancelImmediately(?string $reason = null): bool
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'current_period_end' => now(),
            'auto_renew' => false,
        ]);

        return true;
    }

    /**
     * Réactiver l'abonnement
     */
    public function resume(): bool
    {
        if (!$this->isCancelled() || $this->isExpired()) {
            return false;
        }

        $this->update([
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'auto_renew' => true,
        ]);

        return true;
    }

    /**
     * Renouveler l'abonnement
     */
    public function renew(): bool
    {
        $newPeriodStart = $this->current_period_end;
        $newPeriodEnd = $this->billing_cycle === 'yearly'
            ? $newPeriodStart->addYear()
            : $newPeriodStart->addMonth();

        $this->update([
            'status' => 'active',
            'current_period_start' => $newPeriodStart,
            'current_period_end' => $newPeriodEnd,
            'amount' => $this->plan->getPriceForCycle($this->billing_cycle),
        ]);

        return true;
    }

    /**
     * Changer de plan
     */
    public function changePlan(SubscriptionPlan $newPlan, ?string $cycle = null): bool
    {
        $cycle = $cycle ?? $this->billing_cycle;

        // Calculer le prorata si nécessaire
        $daysRemaining = $this->daysRemaining();
        $totalDays = $this->billing_cycle === 'yearly' ? 365 : 30;
        $unusedCredit = ($daysRemaining / $totalDays) * $this->amount;

        $this->update([
            'subscription_plan_id' => $newPlan->id,
            'billing_cycle' => $cycle,
            'amount' => $newPlan->getPriceForCycle($cycle),
            'metadata' => array_merge($this->metadata ?? [], [
                'previous_plan_credit' => $unusedCredit,
                'plan_changed_at' => now()->toISOString(),
            ]),
        ]);

        return true;
    }

    /**
     * Créer un nouvel abonnement pour un utilisateur
     */
    public static function createForUser(User $user, SubscriptionPlan $plan, string $cycle = 'monthly', int $trialDays = 14): self
    {
        $now = now();

        return self::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => $trialDays > 0 ? 'trialing' : 'active',
            'billing_cycle' => $cycle,
            'trial_ends_at' => $trialDays > 0 ? $now->copy()->addDays($trialDays) : null,
            'current_period_start' => $now,
            'current_period_end' => $cycle === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth(),
            'amount' => $plan->getPriceForCycle($cycle),
            'auto_renew' => true,
        ]);
    }
}
