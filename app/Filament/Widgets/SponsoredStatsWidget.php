<?php

namespace App\Filament\Widgets;

use App\Models\SponsoredListing;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SponsoredStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return Cache::remember('admin.sponsored_stats', 300, fn () => $this->computeStats());
    }

    protected function computeStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Campagnes actives
        $activeCount = SponsoredListing::where('status', 'active')->count();

        // En attente de paiement/activation
        $pendingCount = SponsoredListing::where('status', 'pending')->count();

        // Revenus ce mois (montants dépensés = revenus pour la plateforme)
        $revenueThisMonth = SponsoredListing::where('created_at', '>=', $startOfMonth)
            ->where('is_paid', true)
            ->sum('total_budget');

        $revenueLastMonth = SponsoredListing::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->where('is_paid', true)
            ->sum('total_budget');

        $growthPercent = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : 0);

        // Impressions totales
        $totalImpressions = SponsoredListing::sum('impressions');
        $impressionsThisMonth = SponsoredListing::where('status', 'active')->sum('impressions');

        // Clics totaux
        $totalClicks = SponsoredListing::sum('clicks');

        // CTR global
        $globalCtr = $totalImpressions > 0
            ? round(($totalClicks / $totalImpressions) * 100, 2)
            : 0;

        // Contacts générés
        $totalContacts = SponsoredListing::sum('contacts_generated');

        // Sparkline 7 jours (nouvelles campagnes créées par jour)
        $startChart = $now->copy()->subDays(6)->startOfDay();
        $dailyCounts = SponsoredListing::where('created_at', '>=', $startChart)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->format('Y-m-d');
            $chartData[] = $dailyCounts[$date] ?? 0;
        }

        return array_values([
            Stat::make('Campagnes actives', $activeCount)
                ->description($pendingCount > 0 ? $pendingCount.' en attente' : 'Tout est à jour')
                ->descriptionIcon($pendingCount > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingCount > 0 ? 'warning' : 'success')
                ->chart($chartData),

            Stat::make('Revenus ce mois', number_format($revenueThisMonth, 0, ',', ' ').' F')
                ->description($growthPercent >= 0 ? "+{$growthPercent}% vs mois dernier" : "{$growthPercent}% vs mois dernier")
                ->descriptionIcon($growthPercent >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growthPercent >= 0 ? 'success' : 'danger'),

            Stat::make('Impressions', number_format($totalImpressions))
                ->description('Total cumulé')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),

            Stat::make('Clics', number_format($totalClicks))
                ->description('CTR global : '.$globalCtr.'%')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color($globalCtr >= 2 ? 'success' : 'warning'),

            Stat::make('Contacts générés', number_format($totalContacts))
                ->description('Via les campagnes')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('En attente', $pendingCount)
                ->description($pendingCount > 0 ? 'À traiter' : 'Rien en attente')
                ->descriptionIcon($pendingCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check')
                ->color($pendingCount > 0 ? 'danger' : 'gray'),
        ]);
    }

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin';
    }
}
