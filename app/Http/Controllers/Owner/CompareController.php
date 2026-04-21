<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PlatformSetting;
use App\Models\Residence;
use App\Models\ResidenceView;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Comparateur de résidences pour le propriétaire
 */
class CompareController extends Controller
{
    /**
     * Page de comparaison des résidences
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Toutes les résidences du propriétaire (actives + en attente)
        $residences = Residence::where('owner_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->with(['photos' => fn ($q) => $q->orderBy('order')->limit(1), 'amenities', 'category'])
            ->withCount(['bookings as total_bookings_count', 'reviews', 'contacts'])
            ->withCount(['bookings as completed_bookings_count' => fn ($q) => $q->where('status', 'completed')])
            ->orderBy('name')
            ->get();

        // IDs sélectionnés (max 4)
        $selectedIds = $request->input('ids', []);
        if (is_string($selectedIds)) {
            $selectedIds = explode(',', $selectedIds);
        }
        $selectedIds = array_map('intval', array_filter($selectedIds));

        // Si aucune sélection, prendre les 2 premières
        if (empty($selectedIds) && $residences->count() >= 2) {
            $selectedIds = $residences->take(2)->pluck('id')->toArray();
        }

        // Résidences sélectionnées avec données enrichies
        $compared = collect();
        if (!empty($selectedIds)) {
            $compared = $residences->whereIn('id', $selectedIds)->values();
            $compared = $this->enrichWithStats($compared);
        }

        // Période pour les stats (30 derniers jours)
        $period = $request->input('period', '30');
        $startDate = now()->subDays((int) $period)->startOfDay();
        $endDate = now()->endOfDay();

        // Enrichir avec les stats de la période
        if ($compared->isNotEmpty()) {
            $compared = $this->enrichWithPeriodStats($compared, $startDate, $endDate);
        }

        return view('owner.compare.index', compact(
            'residences',
            'compared',
            'selectedIds',
            'period',
        ));
    }

    /**
     * Enrichir les résidences avec les statistiques globales
     */
    private function enrichWithStats($residences)
    {
        $ids = $residences->pluck('id');

        // Revenus totaux par résidence (bookings complétées)
        $revenues = Booking::whereIn('residence_id', $ids)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->selectRaw('residence_id, SUM(subtotal + cleaning_fee) as total_revenue, AVG(subtotal + cleaning_fee) as avg_revenue, SUM(nights) as total_nights')
            ->groupBy('residence_id')
            ->pluck(null, 'residence_id');

        $commissionRate = PlatformSetting::getCommissionRate() / 100;

        return $residences->map(function ($residence) use ($revenues, $commissionRate) {
            $rev = $revenues[$residence->id] ?? null;
            $gross = (float) ($rev?->total_revenue ?? 0);
            $residence->total_revenue = round($gross * (1 - $commissionRate));
            $residence->avg_revenue = $rev ? round((float) $rev->avg_revenue * (1 - $commissionRate)) : 0;
            $residence->total_nights_booked = (int) ($rev?->total_nights ?? 0);

            // Taux de conversion (vues → contacts)
            $residence->conversion_rate = $residence->views_count > 0
                ? round(($residence->contacts_count / $residence->views_count) * 100, 1)
                : 0;

            // Taux de réservation (contacts → bookings)
            $residence->booking_rate = $residence->contacts_count > 0
                ? round(($residence->total_bookings_count / $residence->contacts_count) * 100, 1)
                : 0;

            return $residence;
        });
    }

    /**
     * Enrichir avec les stats sur une période donnée
     */
    private function enrichWithPeriodStats($residences, Carbon $startDate, Carbon $endDate)
    {
        $ids = $residences->pluck('id');

        // Vues sur la période
        $viewsByResidence = ResidenceView::whereIn('residence_id', $ids)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('residence_id, COUNT(*) as views_count')
            ->groupBy('residence_id')
            ->pluck('views_count', 'residence_id');

        // Bookings sur la période
        $bookingsByResidence = Booking::whereIn('residence_id', $ids)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->whereBetween('check_out', [$startDate, $endDate])
            ->selectRaw('residence_id, COUNT(*) as count, SUM(subtotal + cleaning_fee) as revenue')
            ->groupBy('residence_id')
            ->get()
            ->keyBy('residence_id');

        $commissionRate = PlatformSetting::getCommissionRate() / 100;
        $totalDays = $startDate->diffInDays($endDate);

        return $residences->map(function ($residence) use ($viewsByResidence, $bookingsByResidence, $commissionRate, $totalDays) {
            $residence->period_views = $viewsByResidence[$residence->id] ?? 0;

            $periodBooking = $bookingsByResidence[$residence->id] ?? null;
            $residence->period_bookings = (int) ($periodBooking?->count ?? 0);
            $periodGross = (float) ($periodBooking?->revenue ?? 0);
            $residence->period_revenue = round($periodGross * (1 - $commissionRate));

            // Taux d'occupation approximatif sur la période
            $residence->period_occupancy = $totalDays > 0
                ? round(($residence->total_nights_booked / max($totalDays, 1)) * 100, 1)
                : 0;
            $residence->period_occupancy = min($residence->period_occupancy, 100);

            return $residence;
        });
    }
}
