<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class SponsoredListing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'residence_id',
        'user_id',
        'type',
        'starts_at',
        'ends_at',
        'duration_days',
        'position',
        'daily_budget',
        'total_budget',
        'amount_spent',
        'billing_type',
        'cost_per_unit',
        'impressions',
        'clicks',
        'contacts_generated',
        'target_communes',
        'target_user_types',
        'status',
        'is_paid',
        'payment_reference',
        'jeko_payment_id',
        'jeko_reference',
        'payment_method',
        'payment_status',
        'paid_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'daily_budget' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'amount_spent' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'target_communes' => 'array',
        'target_user_types' => 'array',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    // Relations
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(SponsoredListingStat::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->where('is_paid', true);
    }

    public function scopeFeaturedHome($query)
    {
        return $query->active()->whereIn('type', ['featured_home', 'premium_listing']);
    }

    public function scopeTopSearch($query)
    {
        return $query->active()->whereIn('type', ['top_search', 'premium_listing']);
    }

    public function scopeForResidence($query, $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->is_paid
            && $this->starts_at <= now()
            && $this->ends_at >= now()
            && !$this->isBudgetExhausted();
    }

    public function isBudgetExhausted(): bool
    {
        if ($this->total_budget && $this->amount_spent >= $this->total_budget) {
            return true;
        }

        return false;
    }

    /**
     * Alias for isActive() — used by MarketingService
     */
    public function canRun(): bool
    {
        return $this->isActive();
    }

    /**
     * Check if the sponsored listing is expired
     */
    public function isExpired(): bool
    {
        return $this->ends_at && $this->ends_at < now();
    }

    public function getRemainingBudgetAttribute(): ?float
    {
        if (!$this->total_budget) {
            return null;
        }

        return max(0, $this->total_budget - $this->amount_spent);
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->ends_at || $this->ends_at < now()) {
            return 0;
        }

        return (int) now()->diffInDays($this->ends_at);
    }

    public function getClickRateAttribute(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    public function getConversionRateAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0;
        }

        return round(($this->contacts_generated / $this->clicks) * 100, 2);
    }

    public function getCostPerClickAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0;
        }

        return round($this->amount_spent / $this->clicks, 2);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'featured_home' => 'Page d\'accueil',
            'top_search' => 'Top recherche',
            'highlighted' => 'Mis en avant',
            'premium_listing' => 'Premium',
            default => $this->type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'active' => 'Actif',
            'paused' => 'En pause',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'active' => 'green',
            'paused' => 'gray',
            'completed' => 'blue',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    // Actions
    public function recordImpression(?string $ip = null, ?int $userId = null): void
    {
        // Anti-fraud: dedup by IP+user — max 1 impression per hour per visitor
        $dedupKey = "sponsored:{$this->id}:imp:".($ip ?? 'unknown').':'.($userId ?? 'anon');
        if (Cache::has($dedupKey)) {
            return; // Already counted this hour
        }
        Cache::put($dedupKey, true, now()->addHour());

        $this->increment('impressions');

        // Track daily stats
        $dailyStat = SponsoredListingStat::forToday($this->id);
        $dailyStat->increment('impressions');

        if ($this->billing_type === 'per_view' && $this->cost_per_unit > 0) {
            $this->increment('amount_spent', $this->cost_per_unit);
            $dailyStat->increment('amount_spent', $this->cost_per_unit);
            $this->checkBudget();
        }
    }

    public function recordClick(?string $ip = null, ?int $userId = null): void
    {
        // Anti-fraud: dedup by IP+user — max 1 click per hour per visitor
        $dedupKey = "sponsored:{$this->id}:click:".($ip ?? 'unknown').':'.($userId ?? 'anon');
        if (Cache::has($dedupKey)) {
            return; // Already counted this hour
        }
        Cache::put($dedupKey, true, now()->addHour());

        $this->increment('clicks');

        // Track daily stats
        $dailyStat = SponsoredListingStat::forToday($this->id);
        $dailyStat->increment('clicks');

        if ($this->billing_type === 'per_click' && $this->cost_per_unit > 0) {
            $this->increment('amount_spent', $this->cost_per_unit);
            $dailyStat->increment('amount_spent', $this->cost_per_unit);
            $this->checkBudget();
        }
    }

    public function recordContact(?string $ip = null, ?int $userId = null): void
    {
        // Anti-fraud: dedup by IP+user — max 1 contact per day per visitor
        $dedupKey = "sponsored:{$this->id}:contact:".($ip ?? 'unknown').':'.($userId ?? 'anon');
        if (Cache::has($dedupKey)) {
            return;
        }
        Cache::put($dedupKey, true, now()->addDay());

        $this->increment('contacts_generated');

        // Track daily stats
        $dailyStat = SponsoredListingStat::forToday($this->id);
        $dailyStat->increment('contacts');
    }

    private function checkBudget(): void
    {
        if ($this->isBudgetExhausted()) {
            $this->update(['status' => 'paused']);
        }
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
