<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Contact;
use App\Models\Residence;
use App\Models\ResidenceView;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Service d'analyse statistique pour les propriétaires
 *
 * Fournit des métriques de performance pour les résidences :
 * - Revenus estimés
 * - Taux d'occupation
 * - Vues et conversions
 * - Tendances et comparaisons
 */
class AnalyticsService
{
    /**
     * Obtenir les statistiques globales du tableau de bord
     */
    public function getDashboardStats(User $owner, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $residenceIds = $owner->residences()->pluck('id');

        return [
            'overview' => $this->getOverviewStats($residenceIds, $startDate, $endDate),
            'revenue' => $this->getRevenueStats($residenceIds, $startDate, $endDate),
            'views' => $this->getViewsStats($residenceIds, $startDate, $endDate),
            'contacts' => $this->getContactsStats($residenceIds, $startDate, $endDate),
            'conversion' => $this->getConversionStats($residenceIds, $startDate, $endDate),
            'top_residences' => $this->getTopResidences($owner, $startDate, $endDate),
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'label' => $startDate->locale('fr')->isoFormat('MMMM YYYY'),
            ],
        ];
    }

    /**
     * Vue d'ensemble générale
     */
    public function getOverviewStats(Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        $totalResidences = Residence::whereIn('id', $residenceIds)->count();
        $activeResidences = Residence::whereIn('id', $residenceIds)
            ->whereIn('status', ['active', 'approved'])
            ->count();

        $previousStart = $startDate->copy()->subMonth()->startOfMonth();
        $previousEnd = $startDate->copy()->subMonth()->endOfMonth();

        // Vues ce mois
        $currentViews = ResidenceView::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Vues mois précédent
        $previousViews = ResidenceView::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        // Contacts ce mois
        $currentContacts = Contact::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Contacts mois précédent
        $previousContacts = Contact::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        return [
            'total_residences' => $totalResidences,
            'active_residences' => $activeResidences,
            'total_views' => $currentViews,
            'views_change' => $this->calculatePercentChange($previousViews, $currentViews),
            'total_contacts' => $currentContacts,
            'contacts_change' => $this->calculatePercentChange($previousContacts, $currentContacts),
        ];
    }

    /**
     * Statistiques de revenus
     */
    public function getRevenueStats(Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        // Revenus basés sur les réservations
        $bookings = Booking::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $estimatedRevenue = $bookings->sum('total_amount');
        $confirmedRevenue = $bookings->whereIn('status', ['confirmed', 'completed'])->sum('total_amount');

        // Mois précédent pour comparaison
        $previousStart = $startDate->copy()->subMonth()->startOfMonth();
        $previousEnd = $startDate->copy()->subMonth()->endOfMonth();

        $previousRevenue = Booking::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('total_amount');

        // Revenus par jour du mois
        $dailyRevenue = $this->getDailyRevenue($residenceIds, $startDate, $endDate);

        // Revenus par résidence
        $revenueByResidence = $this->getRevenueByResidence($residenceIds, $startDate, $endDate);

        return [
            'estimated' => $estimatedRevenue,
            'confirmed' => $confirmedRevenue,
            'previous_month' => $previousRevenue,
            'change' => $this->calculatePercentChange($previousRevenue, $confirmedRevenue),
            'daily' => $dailyRevenue,
            'by_residence' => $revenueByResidence,
            'average_per_booking' => $bookings->count() > 0 ? round($estimatedRevenue / $bookings->count()) : 0,
        ];
    }

    /**
     * Revenus par jour
     */
    private function getDailyRevenue(Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $dailyData = [];

        foreach ($period as $date) {
            $dailyData[$date->format('Y-m-d')] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('d'),
                'revenue' => 0,
            ];
        }

        $bookings = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in', [$startDate, $endDate])
                    ->orWhereBetween('check_out', [$startDate, $endDate]);
            })
            ->get();

        foreach ($bookings as $booking) {
            if (!$booking->check_in || !$booking->check_out) {
                continue;
            }

            $checkIn = Carbon::parse($booking->check_in);
            $checkOut = Carbon::parse($booking->check_out);
            $pricePerNight = $booking->price_per_night ?? 0;

            $current = $checkIn->copy();
            while ($current < $checkOut) {
                $dateKey = $current->format('Y-m-d');
                if (isset($dailyData[$dateKey])) {
                    $dailyData[$dateKey]['revenue'] += $pricePerNight;
                }
                $current->addDay();
            }
        }

        return array_values($dailyData);
    }

    /**
     * Revenus par résidence
     */
    private function getRevenueByResidence(Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        $residences = Residence::whereIn('id', $residenceIds)
            ->with(['bookings' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->whereIn('status', ['confirmed', 'completed']);
            }])
            ->get();

        $data = [];

        foreach ($residences as $residence) {
            $revenue = $residence->bookings->sum('total_amount');

            $data[] = [
                'id' => $residence->id,
                'name' => $residence->title ?? $residence->name,
                'revenue' => $revenue,
                'bookings' => $residence->bookings->count(),
            ];
        }

        // Trier par revenus décroissants
        usort($data, fn ($a, $b) => $b['revenue'] - $a['revenue']);

        return array_slice($data, 0, 5);
    }

    /**
     * Statistiques de vues
     */
    public function getViewsStats(Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        // Vues par jour
        $dailyViews = ResidenceView::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $period = CarbonPeriod::create($startDate, $endDate);
        $viewsData = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $viewsData[] = [
                'date' => $dateStr,
                'label' => $date->format('d'),
                'views' => $dailyViews->get($dateStr)?->count ?? 0,
            ];
        }

        // Vues par source
        $viewsBySource = ResidenceView::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("COALESCE(source, 'direct') as source, COUNT(*) as count")
            ->groupBy('source')
            ->get();

        // Vues par résidence
        $viewsByResidence = ResidenceView::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('residence_id, COUNT(*) as count')
            ->groupBy('residence_id')
            ->with('residence:id,name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return [
            'total' => array_sum(array_column($viewsData, 'views')),
            'daily' => $viewsData,
            'by_source' => $viewsBySource,
            'by_residence' => $viewsByResidence,
            'unique_visitors' => ResidenceView::whereIn('residence_id', $residenceIds)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->distinct('ip_address')
                ->count('ip_address'),
        ];
    }

    /**
     * Statistiques de contacts
     */
    public function getContactsStats(Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        $contacts = Contact::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $byStatus = $contacts->groupBy('status')->map->count();

        // Contacts par jour
        $dailyContacts = $contacts->groupBy(fn ($c) => $c->created_at->format('Y-m-d'))
            ->map->count();

        $period = CarbonPeriod::create($startDate, $endDate);
        $contactsData = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $contactsData[] = [
                'date' => $dateStr,
                'label' => $date->format('d'),
                'contacts' => $dailyContacts->get($dateStr, 0),
            ];
        }

        // Durée moyenne de séjour (depuis les réservations)
        $avgStayDuration = Booking::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereNotNull('check_in')
            ->whereNotNull('check_out')
            ->get()
            ->map(fn ($b) => Carbon::parse($b->check_in)->diffInDays($b->check_out))
            ->average() ?? 0;

        // Délai moyen de réponse
        $avgResponseTime = $contacts->filter(fn ($c) => $c->responded_at)
            ->map(fn ($c) => $c->created_at->diffInHours($c->responded_at))
            ->average() ?? 0;

        return [
            'total' => $contacts->count(),
            'by_status' => [
                'pending' => $byStatus->get('pending', 0),
                'viewed' => $byStatus->get('viewed', 0),
                'responded' => $byStatus->get('responded', 0),
                'closed' => $byStatus->get('closed', 0),
            ],
            'daily' => $contactsData,
            'avg_stay_duration' => round($avgStayDuration, 1),
            'avg_response_time' => round($avgResponseTime, 1),
        ];
    }

    /**
     * Taux de conversion
     */
    public function getConversionStats(Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        $totalViews = ResidenceView::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalContacts = Contact::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $confirmedBookings = Booking::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        // Taux vue → contact
        $viewToContactRate = $totalViews > 0
            ? round(($totalContacts / $totalViews) * 100, 2)
            : 0;

        // Taux contact → réservation
        $contactToBookingRate = $totalContacts > 0
            ? round(($confirmedBookings / $totalContacts) * 100, 2)
            : 0;

        // Taux global vue → réservation
        $overallConversionRate = $totalViews > 0
            ? round(($confirmedBookings / $totalViews) * 100, 2)
            : 0;

        // Funnel data
        $funnel = [
            ['stage' => 'Vues', 'count' => $totalViews, 'rate' => 100],
            ['stage' => 'Contacts', 'count' => $totalContacts, 'rate' => $viewToContactRate],
            ['stage' => 'Réservations', 'count' => $confirmedBookings, 'rate' => $overallConversionRate],
        ];

        return [
            'view_to_contact' => $viewToContactRate,
            'contact_to_booking' => $contactToBookingRate,
            'overall' => $overallConversionRate,
            'funnel' => $funnel,
        ];
    }

    /**
     * Top résidences performantes
     */
    public function getTopResidences(User $owner, Carbon $startDate, Carbon $endDate): array
    {
        $residences = $owner->residences()
            ->withCount([
                'views' => fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
                'contacts' => fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
                'bookings' => fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
            ])
            ->with(['bookings' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->whereIn('status', ['confirmed', 'completed']);
            }])
            ->get();

        $data = $residences->map(function ($residence) {
            $revenue = $residence->bookings->sum('total_amount');

            return [
                'id' => $residence->id,
                'name' => $residence->title ?? $residence->name,
                'photo' => $residence->photos->first()?->url,
                'commune' => $residence->commune,
                'views' => $residence->views_count,
                'contacts' => $residence->contacts_count,
                'bookings' => $residence->bookings_count,
                'revenue' => $revenue,
                'conversion' => $residence->views_count > 0
                    ? round(($residence->bookings_count / $residence->views_count) * 100, 1)
                    : 0,
            ];
        });

        // Trier par revenus
        return $data->sortByDesc('revenue')->take(5)->values()->toArray();
    }

    /**
     * Taux d'occupation estimé
     */
    public function getOccupancyRate(Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $totalResidences = $residenceIds->count();
        $totalAvailableDays = $totalDays * $totalResidences;

        // Jours occupés (basé sur les réservations confirmées)
        $occupiedDays = 0;

        $bookings = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in', [$startDate, $endDate])
                    ->orWhereBetween('check_out', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('check_in', '<=', $startDate)
                            ->where('check_out', '>=', $endDate);
                    });
            })
            ->get();

        foreach ($bookings as $booking) {
            if (!$booking->check_in || !$booking->check_out) {
                continue;
            }

            $checkIn = Carbon::parse($booking->check_in)->max($startDate);
            $checkOut = Carbon::parse($booking->check_out)->min($endDate);
            $occupiedDays += $checkIn->diffInDays($checkOut);
        }

        $occupancyRate = $totalAvailableDays > 0
            ? round(($occupiedDays / $totalAvailableDays) * 100, 1)
            : 0;

        // Occupation par jour de la semaine
        $occupancyByDayOfWeek = $this->getOccupancyByDayOfWeek($residenceIds, $startDate, $endDate);

        return [
            'rate' => $occupancyRate,
            'occupied_days' => $occupiedDays,
            'available_days' => $totalAvailableDays,
            'by_day_of_week' => $occupancyByDayOfWeek,
        ];
    }

    /**
     * Occupation par jour de la semaine
     */
    private function getOccupancyByDayOfWeek(Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        $daysOfWeek = [
            0 => ['name' => 'Dimanche', 'short' => 'Dim', 'occupied' => 0, 'total' => 0],
            1 => ['name' => 'Lundi', 'short' => 'Lun', 'occupied' => 0, 'total' => 0],
            2 => ['name' => 'Mardi', 'short' => 'Mar', 'occupied' => 0, 'total' => 0],
            3 => ['name' => 'Mercredi', 'short' => 'Mer', 'occupied' => 0, 'total' => 0],
            4 => ['name' => 'Jeudi', 'short' => 'Jeu', 'occupied' => 0, 'total' => 0],
            5 => ['name' => 'Vendredi', 'short' => 'Ven', 'occupied' => 0, 'total' => 0],
            6 => ['name' => 'Samedi', 'short' => 'Sam', 'occupied' => 0, 'total' => 0],
        ];

        // Compter les jours totaux par jour de semaine
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $daysOfWeek[$date->dayOfWeek]['total'] += $residenceIds->count();
        }

        // Compter les jours occupés
        $bookings = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in', [$startDate, $endDate])
                    ->orWhereBetween('check_out', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('check_in', '<=', $startDate)
                            ->where('check_out', '>=', $endDate);
                    });
            })
            ->get();

        foreach ($bookings as $booking) {
            if (!$booking->check_in || !$booking->check_out) {
                continue;
            }

            $checkIn = Carbon::parse($booking->check_in)->max($startDate);
            $checkOut = Carbon::parse($booking->check_out)->min($endDate);

            $current = $checkIn->copy();
            while ($current < $checkOut) {
                $daysOfWeek[$current->dayOfWeek]['occupied']++;
                $current->addDay();
            }
        }

        // Calculer les taux
        foreach ($daysOfWeek as &$day) {
            $day['rate'] = $day['total'] > 0
                ? round(($day['occupied'] / $day['total']) * 100, 1)
                : 0;
        }

        return array_values($daysOfWeek);
    }

    /**
     * Données pour l'export
     */
    public function getExportData(User $owner, Carbon $startDate, Carbon $endDate, string $type = 'summary'): array
    {
        $residenceIds = $owner->residences()->pluck('id');

        if ($type === 'detailed') {
            return $this->getDetailedExportData($owner, $residenceIds, $startDate, $endDate);
        }

        return $this->getSummaryExportData($owner, $residenceIds, $startDate, $endDate);
    }

    /**
     * Export résumé
     */
    private function getSummaryExportData(User $owner, Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        $stats = $this->getDashboardStats($owner, $startDate, $endDate);
        $occupancy = $this->getOccupancyRate($residenceIds, $startDate, $endDate);

        return [
            'title' => 'Rapport de performance - '.$startDate->format('F Y'),
            'owner' => [
                'name' => $owner->name,
                'email' => $owner->email,
            ],
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y'),
            ],
            'summary' => [
                ['metric' => 'Nombre de résidences', 'value' => $stats['overview']['total_residences']],
                ['metric' => 'Résidences actives', 'value' => $stats['overview']['active_residences']],
                ['metric' => 'Vues totales', 'value' => $stats['overview']['total_views']],
                ['metric' => 'Contacts reçus', 'value' => $stats['overview']['total_contacts']],
                ['metric' => 'Revenus estimés', 'value' => number_format($stats['revenue']['estimated'], 0, ',', ' ').' FCFA'],
                ['metric' => 'Revenus confirmés', 'value' => number_format($stats['revenue']['confirmed'], 0, ',', ' ').' FCFA'],
                ['metric' => 'Taux d\'occupation', 'value' => $occupancy['rate'].'%'],
                ['metric' => 'Taux de conversion', 'value' => $stats['conversion']['overall'].'%'],
            ],
        ];
    }

    /**
     * Export détaillé
     */
    private function getDetailedExportData(User $owner, Collection $residenceIds, Carbon $startDate, Carbon $endDate): array
    {
        $residences = Residence::whereIn('id', $residenceIds)
            ->with([
                'bookings' => fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
            ])
            ->withCount([
                'views' => fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
                'contacts' => fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]),
            ])
            ->get();

        $rows = [];

        foreach ($residences as $residence) {
            $confirmedBookings = $residence->bookings->whereIn('status', ['confirmed', 'completed']);
            $revenue = $confirmedBookings->sum('total_amount');

            $rows[] = [
                'residence' => $residence->title ?? $residence->name,
                'commune' => $residence->commune,
                'type' => $residence->type,
                'price_per_night' => $residence->price_per_day,
                'views' => $residence->views_count,
                'contacts' => $residence->contacts_count,
                'bookings' => $confirmedBookings->count(),
                'revenue' => $revenue,
                'conversion_rate' => $residence->views_count > 0
                    ? round(($confirmedBookings->count() / $residence->views_count) * 100, 1).'%'
                    : '0%',
            ];
        }

        return [
            'title' => 'Rapport détaillé - '.$startDate->format('F Y'),
            'owner' => $owner->name,
            'period' => $startDate->format('d/m/Y').' - '.$endDate->format('d/m/Y'),
            'rows' => $rows,
            'totals' => [
                'views' => array_sum(array_column($rows, 'views')),
                'contacts' => array_sum(array_column($rows, 'contacts')),
                'bookings' => array_sum(array_column($rows, 'bookings')),
                'revenue' => array_sum(array_column($rows, 'revenue')),
            ],
        ];
    }

    /**
     * Données fiscales annuelles
     */
    public function getFiscalYearData(User $owner, int $year): array
    {
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 12, 31)->endOfDay();

        $residenceIds = $owner->residences()->pluck('id');

        // Revenus mensuels
        $monthlyRevenue = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = Carbon::create($year, $month, 1)->startOfDay();
            $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

            $bookings = Booking::whereIn('residence_id', $residenceIds)
                ->whereIn('status', ['confirmed', 'completed'])
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->get();

            $monthlyRevenue[] = [
                'month' => $monthStart->locale('fr')->isoFormat('MMMM'),
                'month_num' => $month,
                'revenue' => $bookings->sum('total_amount'),
                'bookings' => $bookings->count(),
            ];
        }

        $totalRevenue = array_sum(array_column($monthlyRevenue, 'revenue'));
        $totalBookings = array_sum(array_column($monthlyRevenue, 'bookings'));

        // Estimation fiscale (exemple: 5% de taxe de séjour)
        $taxeSejourRate = 0.05;
        $taxeSejour = $totalRevenue * $taxeSejourRate;

        return [
            'year' => $year,
            'owner' => [
                'name' => $owner->name,
                'email' => $owner->email,
                'phone' => $owner->phone,
            ],
            'monthly' => $monthlyRevenue,
            'totals' => [
                'revenue' => $totalRevenue,
                'bookings' => $totalBookings,
                'average_per_month' => round($totalRevenue / 12),
            ],
            'fiscal' => [
                'gross_revenue' => $totalRevenue,
                'taxe_sejour_rate' => ($taxeSejourRate * 100).'%',
                'taxe_sejour_amount' => round($taxeSejour),
                'net_revenue' => $totalRevenue - $taxeSejour,
            ],
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Calcul du pourcentage de changement
     */
    private function calculatePercentChange(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
