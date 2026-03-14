<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'max_residences',
        'max_photos_per_residence',
        'max_sponsored_per_month',
        'commission_rate',
        'priority_support',
        'analytics_advanced',
        'auto_replies',
        'calendar_sync',
        'featured_badge',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'priority_support' => 'boolean',
        'analytics_advanced' => 'boolean',
        'auto_replies' => 'boolean',
        'calendar_sync' => 'boolean',
        'featured_badge' => 'boolean',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Les abonnements actifs pour ce plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Plans actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Triés par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Obtenir le prix selon le cycle
     */
    public function getPriceForCycle(string $cycle): float
    {
        return match ($cycle) {
            'yearly' => $this->price_yearly ?? ($this->price_monthly * 12 * 0.8), // 20% de réduction
            default => $this->price_monthly,
        };
    }

    /**
     * Calculer les économies annuelles
     */
    public function getYearlySavings(): float
    {
        $monthlyTotal = $this->price_monthly * 12;
        $yearlyPrice = $this->price_yearly ?? ($monthlyTotal * 0.8);
        return $monthlyTotal - $yearlyPrice;
    }

    /**
     * Vérifier si une fonctionnalité est incluse
     */
    public function hasFeature(string $feature): bool
    {
        // Vérifier les colonnes booléennes
        if (in_array($feature, ['priority_support', 'analytics_advanced', 'auto_replies', 'calendar_sync', 'featured_badge'])) {
            return (bool) $this->{$feature};
        }

        // Vérifier dans le JSON features
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Obtenir les plans prédéfinis
     */
    public static function getDefaultPlans(): array
    {
        return [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Idéal pour débuter avec une résidence',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_residences' => 1,
                'max_photos_per_residence' => 5,
                'max_sponsored_per_month' => 0,
                'commission_rate' => 5.00,
                'priority_support' => false,
                'analytics_advanced' => false,
                'auto_replies' => false,
                'calendar_sync' => false,
                'featured_badge' => false,
                'features' => ['basic_stats', 'messaging'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Pour les propriétaires avec plusieurs biens',
                'price_monthly' => 9900,
                'price_yearly' => 95000,
                'max_residences' => 5,
                'max_photos_per_residence' => 15,
                'max_sponsored_per_month' => 2,
                'commission_rate' => 3.00,
                'priority_support' => true,
                'analytics_advanced' => true,
                'auto_replies' => true,
                'calendar_sync' => false,
                'featured_badge' => false,
                'features' => ['basic_stats', 'messaging', 'export_data', 'promotions'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Solution complète pour professionnels',
                'price_monthly' => 24900,
                'price_yearly' => 239000,
                'max_residences' => -1, // Illimité
                'max_photos_per_residence' => 30,
                'max_sponsored_per_month' => 5,
                'commission_rate' => 2.00,
                'priority_support' => true,
                'analytics_advanced' => true,
                'auto_replies' => true,
                'calendar_sync' => true,
                'featured_badge' => true,
                'features' => ['basic_stats', 'messaging', 'export_data', 'promotions', 'api_access', 'white_label'],
                'sort_order' => 3,
            ],
        ];
    }
}
