<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'amount',
        'currency',
        'status',
        'payment_provider',
        'transaction_id',
        'reference',
        'provider_reference',
        'period_start',
        'period_end',
        'paid_at',
        'provider_response',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'provider_response' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->reference)) {
                $payment->reference = 'SUB-' . strtoupper(Str::random(12));
            }
        });
    }

    /**
     * L'abonnement associé
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * L'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Paiements réussis
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Paiements en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Marquer comme payé
     */
    public function markAsPaid(?string $transactionId = null, array $metadata = []): bool
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'transaction_id' => $transactionId ?? $this->transaction_id,
            'provider_response' => array_merge($this->provider_response ?? [], $metadata),
        ]);

        // Activer l'abonnement si nécessaire
        $subscription = $this->subscription;
        if ($subscription && in_array($subscription->status, ['pending', 'past_due'])) {
            $subscription->update(['status' => 'active']);
            
            // Si c'était un upgrade, appliquer le changement de plan
            $pendingPlanId = $subscription->metadata['pending_plan_change'] ?? null;
            if ($pendingPlanId) {
                $newPlan = SubscriptionPlan::find($pendingPlanId);
                if ($newPlan) {
                    $subscription->update([
                        'subscription_plan_id' => $newPlan->id,
                        'metadata' => array_diff_key($subscription->metadata ?? [], ['pending_plan_change' => null]),
                    ]);
                }
            }
        }

        return true;
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(?string $reason = null, array $providerResponse = []): bool
    {
        $this->update([
            'status' => 'failed',
            'provider_response' => array_merge($providerResponse, ['failure_reason' => $reason]),
        ]);

        return true;
    }

    /**
     * Créer un paiement pour un abonnement
     */
    public static function createForSubscription(Subscription $subscription, string $provider = 'jeko'): self
    {
        return self::create([
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'amount' => $subscription->amount,
            'currency' => 'XOF',
            'status' => 'pending',
            'payment_provider' => $provider,
        ]);
    }
}
