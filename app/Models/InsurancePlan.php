<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'rate',
        'min_amount',
        'max_coverage',
        'deductible',
        'coverage_types',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_coverage' => 'decimal:2',
        'coverage_types' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Les assurances souscrites avec ce plan
     */
    public function bookingInsurances(): HasMany
    {
        return $this->hasMany(BookingInsurance::class);
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
     * Calculer la prime pour un montant de réservation donné
     */
    public function calculatePremium(float $bookingAmount): float
    {
        $premium = $bookingAmount * ($this->rate / 100);

        return max($premium, $this->min_amount);
    }

    /**
     * Calculer la couverture pour un montant de réservation donné
     */
    public function calculateCoverage(float $bookingAmount): float
    {
        return min($bookingAmount, $this->max_coverage);
    }

    /**
     * Vérifier si un type de couverture est inclus
     */
    public function hasCoverageType(string $type): bool
    {
        return in_array($type, $this->coverage_types ?? []);
    }

    /**
     * Obtenir les plans prédéfinis
     */
    public static function getDefaultPlans(): array
    {
        return [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Protection essentielle pour votre séjour',
                'rate' => 3.00,
                'min_amount' => 2000,
                'max_coverage' => 100000,
                'deductible' => 10000,
                'coverage_types' => ['cancellation', 'damage_minor'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'description' => 'Protection complète recommandée',
                'rate' => 5.00,
                'min_amount' => 3500,
                'max_coverage' => 500000,
                'deductible' => 5000,
                'coverage_types' => ['cancellation', 'damage_minor', 'damage_major', 'theft', 'accident'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Protection maximale tout inclus',
                'rate' => 8.00,
                'min_amount' => 5000,
                'max_coverage' => 2000000,
                'deductible' => 0,
                'coverage_types' => ['cancellation', 'damage_minor', 'damage_major', 'theft', 'accident', 'medical', 'liability', 'travel_delay'],
                'sort_order' => 3,
            ],
        ];
    }

    /**
     * Labels des types de couverture
     */
    public static function getCoverageTypeLabels(): array
    {
        return [
            'cancellation' => 'Annulation',
            'damage_minor' => 'Dommages mineurs',
            'damage_major' => 'Dommages majeurs',
            'theft' => 'Vol',
            'accident' => 'Accident',
            'medical' => 'Frais médicaux',
            'liability' => 'Responsabilité civile',
            'travel_delay' => 'Retard de voyage',
        ];
    }
}
