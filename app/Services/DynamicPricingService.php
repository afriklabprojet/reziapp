<?php

namespace App\Services;

use App\Models\DailyPrice;
use App\Models\Residence;
use App\Models\SeasonalPrice;
use Carbon\Carbon;

/**
 * Service de suggestions de prix dynamiques
 *
 * Analyse les données de marché et la demande pour suggérer
 * des ajustements de prix optimaux.
 */
class DynamicPricingService
{
    /**
     * Facteurs d'influence sur les prix
     */
    private array $factors = [
        'weekend' => 1.15,           // +15% le week-end
        'holiday' => 1.30,           // +30% jours fériés
        'high_season' => 1.25,       // +25% haute saison
        'low_season' => 0.85,        // -15% basse saison
        'high_demand' => 1.20,       // +20% forte demande
        'low_demand' => 0.90,        // -10% faible demande
        'last_minute' => 0.80,       // -20% dernière minute
        'early_bird' => 0.95,        // -5% réservation anticipée
    ];

    /**
     * Jours fériés en Côte d'Ivoire
     */
    private array $holidays = [
        '01-01', // Jour de l'an
        '05-01', // Fête du travail
        '08-07', // Fête de l'indépendance
        '11-15', // Journée de la paix
        '12-25', // Noël
    ];

    /**
     * Haute saison (dates approximatives)
     */
    private array $highSeasonRanges = [
        ['12-15', '01-10'], // Noël/Nouvel An
        ['07-01', '08-31'], // Vacances d'été
        ['02-01', '02-28'], // Février (affaires)
    ];

    /**
     * Générer des suggestions de prix pour une résidence
     */
    public function generateSuggestions(Residence $residence, int $daysAhead = 90): array
    {
        $suggestions = [];
        $basePrice = $residence->price_per_day;

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays($daysAhead);

        // Analyser les données de la résidence
        $residenceData = $this->analyzeResidence($residence);

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');

            // Ignorer les dates déjà réservées ou ayant un prix custom
            if (in_array($dateStr, $residenceData['booked_dates'])
                || in_array($dateStr, $residenceData['existing_daily_prices'])) {
                $currentDate->addDay();
                continue;
            }

            $suggestion = $this->calculateDaySuggestion(
                $residence,
                $currentDate,
                $basePrice,
                $residenceData,
            );

            if ($suggestion['adjustment'] !== 0) {
                $suggestions[] = $suggestion;
            }

            $currentDate->addDay();
        }

        // Regrouper les suggestions par type
        return $this->groupSuggestions($suggestions);
    }

    /**
     * Calculer la suggestion pour un jour donné
     */
    private function calculateDaySuggestion(
        Residence $residence,
        Carbon $date,
        float $basePrice,
        array $residenceData,
    ): array {
        $factors = [];
        $multiplier = 1.0;
        $reasons = [];

        // Week-end
        if ($date->isWeekend()) {
            $factors['weekend'] = $this->factors['weekend'];
            $reasons[] = 'Week-end';
        }

        // Jour férié
        if ($this->isHoliday($date)) {
            $factors['holiday'] = $this->factors['holiday'];
            $reasons[] = 'Jour férié';
        }

        // Haute/Basse saison
        if ($this->isHighSeason($date)) {
            $factors['high_season'] = $this->factors['high_season'];
            $reasons[] = 'Haute saison';
        } elseif ($this->isLowSeason($date)) {
            $factors['low_season'] = $this->factors['low_season'];
            $reasons[] = 'Basse saison';
        }

        // Demande basée sur les vues/contacts récents
        $demandFactor = $this->calculateDemandFactor($residenceData, $date);
        if ($demandFactor > 1.1) {
            $factors['high_demand'] = min($demandFactor, $this->factors['high_demand']);
            $reasons[] = 'Forte demande';
        } elseif ($demandFactor < 0.9) {
            $factors['low_demand'] = max($demandFactor, $this->factors['low_demand']);
            $reasons[] = 'Faible demande';
        }

        // Dernière minute vs réservation anticipée
        $daysUntil = Carbon::today()->diffInDays($date);
        if ($daysUntil <= 3) {
            $factors['last_minute'] = $this->factors['last_minute'];
            $reasons[] = 'Dernière minute';
        } elseif ($daysUntil >= 60) {
            $factors['early_bird'] = $this->factors['early_bird'];
            $reasons[] = 'Réservation anticipée';
        }

        // Calculer le multiplicateur combiné
        if (!empty($factors)) {
            // Moyenne pondérée des facteurs
            $multiplier = array_sum($factors) / count($factors);
        }

        $suggestedPrice = round($basePrice * $multiplier, -2); // Arrondir à la centaine
        $adjustment = round(($multiplier - 1) * 100, 1);

        return [
            'date' => $date->format('Y-m-d'),
            'day_name' => $date->locale('fr')->isoFormat('dddd'),
            'base_price' => $basePrice,
            'suggested_price' => $suggestedPrice,
            'multiplier' => $multiplier,
            'adjustment' => $adjustment, // en pourcentage
            'factors' => $factors,
            'reasons' => $reasons,
        ];
    }

    /**
     * Analyser les données historiques de la résidence
     */
    private function analyzeResidence(Residence $residence): array
    {
        // Vues sur les 30 derniers jours
        $recentViews = $residence->views()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Contacts sur les 30 derniers jours
        $recentContacts = $residence->contacts()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Réservations sur les 30 derniers jours
        $recentBookings = $residence->bookings()
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        // Taux d'occupation sur les 30 derniers jours
        $occupiedDays = $residence->bookings()
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('check_out', '>=', now()->subDays(30))
            ->where('check_in', '<=', now())
            ->get()
            ->sum(function ($booking) {
                $start = Carbon::parse($booking->check_in)->max(now()->subDays(30));
                $end = Carbon::parse($booking->check_out)->min(now());

                return max(0, $start->diffInDays($end));
            });
        $occupancyRate = $occupiedDays / 30;

        // Dates déjà réservées (pour filtrer les suggestions inutiles)
        $bookedDates = $residence->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_out', '>=', now())
            ->get()
            ->flatMap(function ($booking) {
                $dates = [];
                $current = Carbon::parse($booking->check_in);
                $end = Carbon::parse($booking->check_out);
                while ($current < $end) {
                    $dates[] = $current->format('Y-m-d');
                    $current->addDay();
                }

                return $dates;
            })
            ->toArray();

        // Dates ayant déjà un prix spécial
        $existingDailyPrices = DailyPrice::where('residence_id', $residence->id)
            ->where('date', '>=', now())
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        // Moyenne des résidences similaires dans la même commune
        $avgPriceInArea = Residence::where('commune', $residence->commune)
            ->where('type', $residence->type)
            ->where('status', 'active')
            ->where('id', '!=', $residence->id)
            ->avg('price_per_day') ?? $residence->price_per_day;

        // Taux de conversion (contacts/vues)
        $conversionRate = $recentViews > 0
            ? $recentContacts / $recentViews
            : 0;

        return [
            'recent_views' => $recentViews,
            'recent_contacts' => $recentContacts,
            'recent_bookings' => $recentBookings,
            'occupancy_rate' => $occupancyRate,
            'booked_dates' => $bookedDates,
            'existing_daily_prices' => $existingDailyPrices,
            'avg_price_in_area' => $avgPriceInArea,
            'conversion_rate' => $conversionRate,
            'price_vs_market' => $avgPriceInArea > 0
                ? $residence->price_per_day / $avgPriceInArea
                : 1.0,
        ];
    }

    /**
     * Calculer le facteur de demande
     */
    private function calculateDemandFactor(array $residenceData, Carbon $date): float
    {
        $baseFactor = 1.0;

        // Plus de vues = plus de demande
        if ($residenceData['recent_views'] > 100) {
            $baseFactor += 0.1;
        } elseif ($residenceData['recent_views'] < 20) {
            $baseFactor -= 0.1;
        }

        // Bon taux de conversion = produit attractif
        if ($residenceData['conversion_rate'] > 0.05) {
            $baseFactor += 0.05;
        }

        // Taux d'occupation élevé = augmenter les prix
        if ($residenceData['occupancy_rate'] > 0.7) {
            $baseFactor += 0.15; // Très demandé
        } elseif ($residenceData['occupancy_rate'] > 0.5) {
            $baseFactor += 0.08;
        } elseif ($residenceData['occupancy_rate'] < 0.2) {
            $baseFactor -= 0.1; // Peu demandé, baisser pour attirer
        }

        // Beaucoup de réservations récentes = forte demande
        if ($residenceData['recent_bookings'] >= 5) {
            $baseFactor += 0.1;
        } elseif ($residenceData['recent_bookings'] === 0) {
            $baseFactor -= 0.05;
        }

        // Prix vs marché
        if ($residenceData['price_vs_market'] < 0.8) {
            // Prix bas = augmenter
            $baseFactor += 0.1;
        } elseif ($residenceData['price_vs_market'] > 1.2) {
            // Prix élevé = baisser
            $baseFactor -= 0.1;
        }

        return $baseFactor;
    }

    /**
     * Vérifier si c'est un jour férié
     */
    private function isHoliday(Carbon $date): bool
    {
        $monthDay = $date->format('m-d');

        if (in_array($monthDay, $this->holidays)) {
            return true;
        }

        // Fêtes mobiles (approximations)
        // Pâques, Ascension, etc. - simplifié
        return false;
    }

    /**
     * Vérifier si c'est la haute saison
     */
    private function isHighSeason(Carbon $date): bool
    {
        $monthDay = $date->format('m-d');

        foreach ($this->highSeasonRanges as $range) {
            $start = $range[0];
            $end = $range[1];

            // Gérer le cas où la période chevauche l'année
            if ($start > $end) {
                if ($monthDay >= $start || $monthDay <= $end) {
                    return true;
                }
            } else {
                if ($monthDay >= $start && $monthDay <= $end) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Vérifier si c'est la basse saison
     */
    private function isLowSeason(Carbon $date): bool
    {
        $month = $date->month;

        // Mai, Juin, Septembre, Octobre = basse saison
        return in_array($month, [5, 6, 9, 10]);
    }

    /**
     * Regrouper les suggestions par période/type
     */
    private function groupSuggestions(array $suggestions): array
    {
        $grouped = [
            'immediate' => [], // Prochains 7 jours
            'upcoming' => [],  // 7-30 jours
            'seasonal' => [],  // Saisons détectées
            'summary' => [],   // Résumé global
        ];

        $today = Carbon::today();

        foreach ($suggestions as $suggestion) {
            $date = Carbon::parse($suggestion['date']);
            $daysUntil = $today->diffInDays($date);

            if ($daysUntil <= 7) {
                $grouped['immediate'][] = $suggestion;
            } elseif ($daysUntil <= 30) {
                $grouped['upcoming'][] = $suggestion;
            }
        }

        // Créer des suggestions saisonnières
        $grouped['seasonal'] = $this->createSeasonalSuggestions($suggestions);

        // Résumé
        $avgAdjustment = collect($suggestions)->avg('adjustment') ?? 0;
        $maxIncrease = collect($suggestions)->max('adjustment') ?? 0;
        $maxDecrease = collect($suggestions)->min('adjustment') ?? 0;

        $grouped['summary'] = [
            'total_days_analyzed' => count($suggestions),
            'avg_adjustment' => round($avgAdjustment, 1),
            'max_increase' => round($maxIncrease, 1),
            'max_decrease' => round($maxDecrease, 1),
            'potential_revenue_change' => $avgAdjustment > 0 ? '+'.round($avgAdjustment, 1).'%' : round($avgAdjustment, 1).'%',
        ];

        return $grouped;
    }

    /**
     * Créer des suggestions de saisons tarifaires
     */
    private function createSeasonalSuggestions(array $suggestions): array
    {
        $seasons = [];

        // Regrouper par raison similaire
        $byReason = collect($suggestions)->filter(function ($s) {
            return !empty($s['reasons']);
        })->groupBy(function ($s) {
            return implode(',', $s['reasons']);
        });

        foreach ($byReason as $reason => $items) {
            if ($items->count() >= 3) {
                $dates = $items->pluck('date')->sort()->values();
                $avgAdjustment = $items->avg('adjustment');

                $seasons[] = [
                    'name' => $this->generateSeasonName($reason),
                    'start_date' => $dates->first(),
                    'end_date' => $dates->last(),
                    'days_count' => $items->count(),
                    'avg_adjustment' => round($avgAdjustment, 1),
                    'suggested_price' => round($items->avg('suggested_price'), -2),
                    'reasons' => explode(',', $reason),
                ];
            }
        }

        return $seasons;
    }

    /**
     * Générer un nom de saison
     */
    private function generateSeasonName(string $reason): string
    {
        $names = [
            'Haute saison' => 'Haute saison',
            'Basse saison' => 'Basse saison',
            'Week-end' => 'Tarif week-end',
            'Jour férié' => 'Jours fériés',
            'Forte demande' => 'Période de forte demande',
            'Faible demande' => 'Promotion basse saison',
        ];

        foreach ($names as $key => $name) {
            if (str_contains($reason, $key)) {
                return $name;
            }
        }

        return 'Période spéciale';
    }

    /**
     * Appliquer automatiquement les suggestions
     */
    public function applySuggestions(Residence $residence, array $suggestions): int
    {
        $applied = 0;

        foreach ($suggestions['seasonal'] ?? [] as $season) {
            // Créer une saison tarifaire
            SeasonalPrice::updateOrCreate(
                [
                    'residence_id' => $residence->id,
                    'start_date' => $season['start_date'],
                    'end_date' => $season['end_date'],
                ],
                [
                    'name' => $season['name'],
                    'price_per_night' => $season['suggested_price'],
                    'is_active' => true,
                    'notes' => 'Généré automatiquement par le système de prix dynamiques',
                ],
            );

            $applied++;
        }

        return $applied;
    }
}
