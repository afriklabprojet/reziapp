<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class BookingsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Réservations (30 derniers jours)';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        return Cache::remember('admin.bookings_chart', 300, fn () => $this->computeData());
    }

    protected function computeData(): array
    {
        $startDate = Carbon::now()->subDays(29)->startOfDay();

        $counts = Booking::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        $data = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');
            $data[] = $counts[$date->format('Y-m-d')] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Réservations',
                    'data' => $data,
                    'fill' => true,
                    'backgroundColor' => 'rgba(251, 113, 133, 0.2)',
                    'borderColor' => 'rgb(251, 113, 133)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
