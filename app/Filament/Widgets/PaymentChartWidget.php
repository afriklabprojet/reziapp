<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PaymentChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Évolution des paiements';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 derniers jours',
            '30' => '30 derniers jours',
            '90' => '3 derniers mois',
            '365' => 'Cette année',
        ];
    }

    protected function getData(): array
    {
        $cacheKey = 'admin.payment_chart.' . $this->filter;
        return Cache::remember($cacheKey, 300, fn () => $this->computeData());
    }

    protected function computeData(): array
    {
        $days = (int) $this->filter;
        $startDate = Carbon::now()->subDays($days);

        $completedData = [];
        $pendingData = [];
        $refundedData = [];
        $labels = [];

        if ($days <= 30) {
            // Données par jour — 1 seule requête groupée
            $payments = Payment::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) as refunded"),
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->get()
            ->keyBy('date');

            for ($i = $days; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $key = $date->format('Y-m-d');
                $labels[] = $date->format('d/m');

                $row = $payments[$key] ?? null;
                $completedData[] = $row ? $row->completed / 1000 : 0;
                $pendingData[] = $row ? $row->pending / 1000 : 0;
                $refundedData[] = $row ? $row->refunded / 1000 : 0;
            }
        } else {
            // Données par semaine/mois
            $groupBy = $days > 90 ? 'month' : 'week';

            $payments = Payment::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) as refunded"),
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            foreach ($payments as $payment) {
                $labels[] = Carbon::createFromFormat('Y-m', $payment->period)->format('M Y');
                $completedData[] = $payment->completed / 1000;
                $pendingData[] = $payment->pending / 1000;
                $refundedData[] = $payment->refunded / 1000;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Complétés (k FCFA)',
                    'data' => $completedData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'En attente (k FCFA)',
                    'data' => $pendingData,
                    'backgroundColor' => 'rgba(234, 179, 8, 0.1)',
                    'borderColor' => 'rgb(234, 179, 8)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Remboursés (k FCFA)',
                    'data' => $refundedData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
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
                    'ticks' => [
                        'callback' => "function(value) { return value + 'k'; }",
                    ],
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
