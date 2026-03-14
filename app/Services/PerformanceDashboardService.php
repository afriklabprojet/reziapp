<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PerformanceDashboardService
{
    /**
     * Calculer les KPIs RevPAR, ADR et taux d'occupation pour un propriétaire
     */
    public function getKPIs(User $owner, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to   = $to ?? now()->endOfMonth();
        $days = $from->diffInDays($to) ?: 1;

        $residences      = $owner->residences()->where('status', 'approved')->get();
        $residenceIds    = $residences->pluck('id');
        $totalResidences = $residences->count() ?: 1;

        $bookings = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('check_in', '<=', $to)
            ->where('check_out', '>=', $from)
            ->get();

        $totalRevenue  = $bookings->sum('total_amount');
        $totalNights   = $bookings->sum('nights');
        $totalBookings = $bookings->count();
        $availableNights = $days * $totalResidences;

        // ADR (Average Daily Rate) — revenu moyen par nuit réservée
        $adr = $totalNights > 0 ? round($totalRevenue / $totalNights) : 0;

        // Taux d'occupation
        $occupancyRate = $availableNights > 0
            ? round(($totalNights / $availableNights) * 100, 1)
            : 0;

        // RevPAR (Revenue Per Available Room) — revenu par nuit disponible
        $revpar = $availableNights > 0 ? round($totalRevenue / $availableNights) : 0;

        // Durée moyenne de séjour
        $avgStayDuration = $totalBookings > 0 ? round($totalNights / $totalBookings, 1) : 0;

        return [
            'revpar'             => $revpar,
            'adr'                => $adr,
            'occupancy_rate'     => $occupancyRate,
            'avg_stay_duration'  => $avgStayDuration,
            'total_revenue'      => (int) $totalRevenue,
            'total_bookings'     => $totalBookings,
            'total_nights'       => $totalNights,
            'available_nights'   => $availableNights,
            'total_residences'   => $totalResidences,
            'period'             => [
                'from' => $from->format('d/m/Y'),
                'to'   => $to->format('d/m/Y'),
            ],
        ];
    }

    /**
     * KPIs par résidence
     */
    public function getPerResidenceKPIs(User $owner, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from = $from ?? now()->startOfMonth();
        $to   = $to ?? now()->endOfMonth();
        $days = $from->diffInDays($to) ?: 1;

        return $owner->residences()
            ->where('status', 'approved')
            ->get()
            ->map(function (Residence $residence) use ($from, $to, $days) {
                $bookings = Booking::where('residence_id', $residence->id)
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->where('check_in', '<=', $to)
                    ->where('check_out', '>=', $from)
                    ->get();

                $revenue       = $bookings->sum('total_amount');
                $nights        = $bookings->sum('nights');
                $bookingCount  = $bookings->count();
                $occupancyRate = $days > 0 ? round(($nights / $days) * 100, 1) : 0;

                return [
                    'residence_id'   => $residence->id,
                    'name'           => $residence->name,
                    'commune'        => $residence->commune,
                    'revenue'        => (int) $revenue,
                    'nights'         => $nights,
                    'bookings'       => $bookingCount,
                    'adr'            => $nights > 0 ? round($revenue / $nights) : 0,
                    'revpar'         => $days > 0 ? round($revenue / $days) : 0,
                    'occupancy_rate' => $occupancyRate,
                ];
            })
            ->sortByDesc('revenue')
            ->values();
    }

    /**
     * Évolution mensuelle des KPIs (12 derniers mois)
     */
    public function getMonthlyTrend(User $owner, int $months = 12): array
    {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $from = now()->subMonths($i)->startOfMonth();
            $to   = now()->subMonths($i)->endOfMonth();
            $kpis = $this->getKPIs($owner, $from, $to);

            $trend[] = [
                'month'          => $from->format('M Y'),
                'month_key'      => $from->format('Y-m'),
                'revpar'         => $kpis['revpar'],
                'adr'            => $kpis['adr'],
                'occupancy_rate' => $kpis['occupancy_rate'],
                'revenue'        => $kpis['total_revenue'],
            ];
        }

        return $trend;
    }

    /**
     * Benchmarking: comparer avec des propriétés similaires dans la même zone
     */
    public function getBenchmark(Residence $residence, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to   = $to ?? now()->endOfMonth();
        $days = $from->diffInDays($to) ?: 1;

        // Trouver les résidences similaires (même commune, même type)
        $similarResidences = Residence::where('commune', $residence->commune)
            ->where('type_location', $residence->type_location)
            ->where('status', 'approved')
            ->where('id', '!=', $residence->id)
            ->limit(20)
            ->pluck('id');

        // KPIs de la résidence actuelle
        $myBookings = Booking::where('residence_id', $residence->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('check_in', '<=', $to)
            ->where('check_out', '>=', $from)
            ->get();

        $myRevenue  = $myBookings->sum('total_amount');
        $myNights   = $myBookings->sum('nights');
        $myAdr      = $myNights > 0 ? round($myRevenue / $myNights) : 0;
        $myOccupancy = $days > 0 ? round(($myNights / $days) * 100, 1) : 0;

        // KPIs moyens zone
        $zoneBookings = Booking::whereIn('residence_id', $similarResidences)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('check_in', '<=', $to)
            ->where('check_out', '>=', $from)
            ->get();

        $zoneResCount  = $similarResidences->count() ?: 1;
        $zoneRevenue   = $zoneBookings->sum('total_amount');
        $zoneNights    = $zoneBookings->sum('nights');
        $zoneAdr       = $zoneNights > 0 ? round($zoneRevenue / $zoneNights) : 0;
        $zoneOccupancy = ($days * $zoneResCount) > 0
            ? round(($zoneNights / ($days * $zoneResCount)) * 100, 1) : 0;

        return [
            'my_residence' => [
                'adr'            => $myAdr,
                'occupancy_rate' => $myOccupancy,
                'revenue'        => (int) $myRevenue,
                'nights'         => $myNights,
            ],
            'zone_average' => [
                'adr'            => $zoneAdr,
                'occupancy_rate' => $zoneOccupancy,
                'residences_compared' => $zoneResCount,
            ],
            'comparison' => [
                'adr_diff'       => $myAdr - $zoneAdr,
                'occupancy_diff' => round($myOccupancy - $zoneOccupancy, 1),
                'adr_better'     => $myAdr >= $zoneAdr,
                'occupancy_better' => $myOccupancy >= $zoneOccupancy,
            ],
            'zone' => $residence->commune,
            'type' => $residence->type_location,
        ];
    }
}
