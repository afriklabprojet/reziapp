<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenus (30 derniers jours)';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');
            $data[] = Payment::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount') / 1000; // En milliers
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenus (k FCFA)',
                    'data' => $data,
                    'fill' => true,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
