<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Services\AnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Tableau de bord principal des analytics
     */
    public function index(Request $request)
    {
        $owner = Auth::user();

        // Période par défaut: mois courant
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        // Statistiques du dashboard
        $stats = $this->analyticsService->getDashboardStats($owner, $startDate, $endDate);

        // Taux d'occupation
        $residenceIds = $owner->residences()->pluck('id');
        $occupancy = $this->analyticsService->getOccupancyRate($residenceIds, $startDate, $endDate);

        // Périodes rapides pour le sélecteur
        $quickPeriods = [
            'today' => ['label' => 'Aujourd\'hui', 'start' => now()->startOfDay(), 'end' => now()->endOfDay()],
            'week' => ['label' => '7 derniers jours', 'start' => now()->subDays(7)->startOfDay(), 'end' => now()->endOfDay()],
            'month' => ['label' => 'Ce mois', 'start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'quarter' => ['label' => '3 derniers mois', 'start' => now()->subMonths(3)->startOfMonth(), 'end' => now()->endOfMonth()],
            'year' => ['label' => 'Cette année', 'start' => now()->startOfYear(), 'end' => now()->endOfYear()],
        ];

        return view('owner.analytics.index', compact(
            'stats',
            'occupancy',
            'startDate',
            'endDate',
            'quickPeriods',
        ));
    }

    /**
     * Détail des revenus
     */
    public function revenue(Request $request)
    {
        $owner = Auth::user();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        $residenceIds = $owner->residences()->pluck('id');
        $revenueStats = $this->analyticsService->getRevenueStats($residenceIds, $startDate, $endDate);

        // Statistiques calculées
        $periodDays = $startDate->diffInDays($endDate) + 1;
        $revenueStats['total'] = $revenueStats['confirmed'];
        $revenueStats['average_daily'] = $periodDays > 0 ? round($revenueStats['confirmed'] / $periodDays) : 0;
        $revenueStats['bookings_count'] = collect($revenueStats['by_residence'])->sum('bookings');
        $revenueStats['average_booking'] = $revenueStats['average_per_booking'];
        $revenueStats['by_day'] = collect($revenueStats['daily'])->map(fn ($d) => [
            'date' => $d['date'],
            'revenue' => $d['revenue'],
            'count' => 0,
        ])->filter(fn ($d) => $d['revenue'] > 0)->values()->toArray();

        return view('owner.analytics.revenue', compact('revenueStats', 'startDate', 'endDate'));
    }

    /**
     * Détail des vues
     */
    public function views(Request $request)
    {
        $owner = Auth::user();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        $residenceIds = $owner->residences()->pluck('id');
        $viewsStats = $this->analyticsService->getViewsStats($residenceIds, $startDate, $endDate);

        // Statistiques calculées
        $periodDays = $startDate->diffInDays($endDate) + 1;
        $viewsStats['total_views'] = $viewsStats['total'];
        $viewsStats['average_daily'] = $periodDays > 0 ? round($viewsStats['total'] / $periodDays) : 0;

        // Taux de conversion
        $totalContacts = Contact::whereIn('residence_id', $residenceIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $viewsStats['conversion_rate'] = $viewsStats['total'] > 0
            ? round(($totalContacts / $viewsStats['total']) * 100, 1)
            : 0;
        $viewsStats['total_contacts'] = $totalContacts;

        // Normaliser by_residence en tableau simple
        $viewsStats['by_residence'] = $viewsStats['by_residence']->map(fn ($v) => [
            'name' => $v->residence?->title ?? $v->residence?->name ?? 'N/A',
            'views' => $v->count,
        ])->toArray();

        // Normaliser by_day
        $viewsStats['by_day'] = collect($viewsStats['daily'])->map(fn ($d) => [
            'date' => $d['date'],
            'views' => $d['views'],
            'unique' => 0,
        ])->filter(fn ($d) => $d['views'] > 0)->values()->toArray();

        return view('owner.analytics.views', compact('viewsStats', 'startDate', 'endDate'));
    }

    /**
     * API: Données du graphique en temps réel
     */
    public function chartData(Request $request)
    {
        $owner = Auth::user();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        $stats = $this->analyticsService->getDashboardStats($owner, $startDate, $endDate);
        $residenceIds = $owner->residences()->pluck('id');
        $occupancy = $this->analyticsService->getOccupancyRate($residenceIds, $startDate, $endDate);

        return response()->json([
            'revenue' => $stats['revenue'],
            'views' => $stats['views'],
            'contacts' => $stats['contacts'],
            'conversion' => $stats['conversion'],
            'occupancy' => $occupancy,
        ]);
    }

    /**
     * Export PDF
     */
    public function exportPdf(Request $request)
    {
        $owner = Auth::user();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        $type = $request->get('type', 'summary');
        $data = $this->analyticsService->getExportData($owner, $startDate, $endDate, $type);

        $residenceIds = $owner->residences()->pluck('id');
        $occupancy = $this->analyticsService->getOccupancyRate($residenceIds, $startDate, $endDate);
        $stats = $this->analyticsService->getDashboardStats($owner, $startDate, $endDate);

        $pdf = Pdf::loadView('owner.analytics.export-pdf', [
            'data' => $data,
            'stats' => $stats,
            'occupancy' => $occupancy,
            'type' => $type,
        ]);

        $filename = 'rapport-'.$startDate->format('Y-m').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Excel/CSV
     */
    public function exportExcel(Request $request)
    {
        $owner = Auth::user();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        $type = $request->get('type', 'detailed');
        $data = $this->analyticsService->getExportData($owner, $startDate, $endDate, $type);

        $filename = 'rapport-'.$startDate->format('Y-m').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($data, $type) {
            $file = fopen('php://output', 'w');

            // BOM UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($type === 'detailed' && isset($data['rows'])) {
                // En-têtes
                fputcsv($file, [
                    'Résidence',
                    'Commune',
                    'Type',
                    'Prix/nuit (FCFA)',
                    'Vues',
                    'Contacts',
                    'Réservations',
                    'Revenus (FCFA)',
                    'Taux conversion',
                ], ';');

                // Données
                foreach ($data['rows'] as $row) {
                    fputcsv($file, [
                        $row['residence'],
                        $row['commune'],
                        $row['type'],
                        $row['price_per_night'],
                        $row['views'],
                        $row['contacts'],
                        $row['bookings'],
                        $row['revenue'],
                        $row['conversion_rate'],
                    ], ';');
                }

                // Totaux
                fputcsv($file, [], ';');
                fputcsv($file, [
                    'TOTAL',
                    '',
                    '',
                    '',
                    $data['totals']['views'],
                    $data['totals']['contacts'],
                    $data['totals']['bookings'],
                    $data['totals']['revenue'],
                    '',
                ], ';');
            } else {
                // Export résumé
                fputcsv($file, ['Métrique', 'Valeur'], ';');

                foreach ($data['summary'] ?? [] as $item) {
                    fputcsv($file, [$item['metric'], $item['value']], ';');
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Historique fiscal
     */
    public function fiscal(Request $request)
    {
        $owner = Auth::user();
        $year = $request->get('year', now()->year);

        $fiscalData = $this->analyticsService->getFiscalYearData($owner, $year);

        // Années disponibles (basées sur l'ancienneté du compte)
        $startYear = $owner->created_at->year;
        $currentYear = now()->year;
        $availableYears = range($currentYear, max($startYear, $currentYear - 5), -1);

        return view('owner.analytics.fiscal', compact('fiscalData', 'year', 'availableYears'));
    }

    /**
     * Export fiscal PDF
     */
    public function exportFiscalPdf(Request $request)
    {
        $owner = Auth::user();
        $year = $request->get('year', now()->year);

        $fiscalData = $this->analyticsService->getFiscalYearData($owner, $year);

        $pdf = Pdf::loadView('owner.analytics.fiscal-pdf', [
            'data' => $fiscalData,
            'year' => $year,
        ]);

        $filename = 'recapitulatif-fiscal-'.$year.'.pdf';

        return $pdf->download($filename);
    }
}
