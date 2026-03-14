<?php

namespace App\Filament\Widgets;

use App\Models\Residence;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ResidencesByLocationChart extends ChartWidget
{
    protected static ?string $heading = 'Résidences par commune';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'half';

    protected function getData(): array
    {
        $data = Residence::query()
            ->select('commune', DB::raw('count(*) as count'))
            ->whereIn('status', ['active', 'approved'])
            ->groupBy('commune')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Résidences',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#ec4899',
                        '#14b8a6',
                        '#f97316',
                        '#06b6d4',
                        '#84cc16',
                    ],
                ],
            ],
            'labels' => $data->map(fn($r) => $r->commune ?? 'N/A')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
            ],
        ];
    }
}
