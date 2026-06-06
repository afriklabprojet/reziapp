<?php

namespace App\Filament\Widgets;

use App\Services\MarketingAnalyticsService;
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
        $stats = app(MarketingAnalyticsService::class)->getSponsoredWidgetStats();

        return array_values([
            Stat::make('Campagnes actives', $stats['active'])
                ->description($stats['pending'] > 0 ? $stats['pending'].' en attente' : 'Tout est à jour')
                ->descriptionIcon($stats['pending'] > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($stats['pending'] > 0 ? 'warning' : 'success')
                ->chart($stats['chart']),

            Stat::make('Revenus ce mois', number_format($stats['revenue_this_month'], 0, ',', ' ').' F')
                ->description($stats['growth_percent'] >= 0 ? "+{$stats['growth_percent']}% vs mois dernier" : "{$stats['growth_percent']}% vs mois dernier")
                ->descriptionIcon($stats['growth_percent'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($stats['growth_percent'] >= 0 ? 'success' : 'danger'),

            Stat::make('Impressions', number_format($stats['total_impressions']))
                ->description('Total cumulé')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),

            Stat::make('Clics', number_format($stats['total_clicks']))
                ->description('CTR global : '.$stats['global_ctr'].'%')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color($stats['global_ctr'] >= 2 ? 'success' : 'warning'),

            Stat::make('Contacts générés', number_format($stats['total_contacts']))
                ->description('Via les campagnes')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('En attente', $stats['pending'])
                ->description($stats['pending'] > 0 ? 'À traiter' : 'Rien en attente')
                ->descriptionIcon($stats['pending'] > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check')
                ->color($stats['pending'] > 0 ? 'danger' : 'gray'),
        ]);
    }

    public static function canView(): bool
    {
        return Auth::user()?->role === 'admin';
    }
}
