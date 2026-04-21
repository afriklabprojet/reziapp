<?php

namespace App\Filament\Widgets;

use App\Models\OwnerAnalytics;
use Filament\Widgets\ChartWidget;

class OwnerPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Performance des 30 derniers jours';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = OwnerAnalytics::getChartData(auth()->id(), 30);

        return [
            'datasets' => [
                [
                    'label' => 'Vues',
                    'data' => $data['views'],
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Réservations',
                    'data' => $data['bookings'],
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'elements' => [
                'line' => [
                    'tension' => 0.3,
                ],
            ],
        ];
    }
}
