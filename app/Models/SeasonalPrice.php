<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prix saisonniers absolus d'une résidence.
 *
 * Responsabilité : stocker des tarifs nuitée/semaine/mois fixes pour une période
 * calendaire (ex : "Haute saison Noël : 25 000 FCFA/nuit"). Utilisé par
 * PricingController (CRUD propriétaire) et DynamicPricingService (calcul automatique).
 *
 * Table : seasonal_prices
 *
 * DISTINCT de SeasonalPricing : ce modèle porte des prix absolus en FCFA.
 * SeasonalPricing porte des multiplicateurs et des templates CI (price_multiplier).
 *
 * Actif dans les calculs de réservation : NON — PricingService s'appuie sur
 * SpecialPrice (tarifs journaliers) et les champs price_per_day/week/month de Residence.
 * Ce modèle est utilisé pour l'affichage du calendrier propriétaire et la
 * synchronisation DynamicPricingService.
 */
class SeasonalPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'name',
        'start_date',
        'end_date',
        'price_per_night',
        'price_per_week',
        'price_per_month',
        'min_nights',
        'priority',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price_per_night' => 'decimal:2',
        'price_per_week' => 'decimal:2',
        'price_per_month' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec la résidence
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Scope pour les prix actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour une date donnée
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)
                     ->where('end_date', '>=', $date);
    }

    /**
     * Scope pour une période
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Vérifie si la saison est en cours
     */
    public function isCurrentlyActive(): bool
    {
        $today = now()->toDateString();

        return $this->is_active
            && $this->start_date <= $today
            && $this->end_date >= $today;
    }

    /**
     * Calcule le prix pour un nombre de nuits
     */
    public function calculatePrice(int $nights): float
    {
        // All pricing is per-day. price_per_month and price_per_week are monthly/weekly
        // totals stored for reference; convert to daily rate before multiplying by nights.
        if ($nights >= 30 && $this->price_per_month) {
            return $nights * round($this->price_per_month / 30);
        }

        if ($nights >= 7 && $this->price_per_week) {
            return $nights * round($this->price_per_week / 7);
        }

        return $nights * $this->price_per_night;
    }
}
