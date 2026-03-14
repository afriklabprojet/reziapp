<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AlertsWidget;
use App\Filament\Widgets\BookingsChartWidget;
use App\Filament\Widgets\PaymentChartWidget;
use App\Filament\Widgets\PaymentStatsWidget;
use App\Filament\Widgets\PendingApprovalsWidget;
use App\Filament\Widgets\RecentBookingsWidget;
use App\Filament\Widgets\RecentPaymentsWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static ?string $title = 'Tableau de bord';

    protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            PaymentStatsWidget::class,
            AlertsWidget::class,
            PendingApprovalsWidget::class,
            PaymentChartWidget::class,
            RecentBookingsWidget::class,
            RecentPaymentsWidget::class,
            BookingsChartWidget::class,
            RevenueChartWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 2,
        ];
    }
}
