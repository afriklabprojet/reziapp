<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class PaymentStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return Cache::remember('admin.payment_stats', 300, fn () => $this->computeStats());
    }

    protected function computeStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfWeek = $now->copy()->startOfWeek();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Total des paiements complétés
        $totalCompleted = Payment::where('status', 'completed')->sum('amount');

        // Revenus ce mois
        $revenueThisMonth = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');

        // Revenus mois dernier
        $revenueLastMonth = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');

        // Croissance
        $growthPercent = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : 100;

        // Paiements en attente
        $pendingCount = Payment::where('status', 'pending')->count();
        $pendingAmount = Payment::where('status', 'pending')->sum('amount');

        // Paiements échoués
        $failedCount = Payment::where('status', 'failed')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        // Remboursements ce mois
        $refundedAmount = Payment::where('status', 'refunded')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');
        $refundedCount = Payment::where('status', 'refunded')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        // Revenus cette semaine
        $revenueThisWeek = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startOfWeek)
            ->sum('amount');

        // Données pour le graphique des 7 derniers jours
        $startChart = $now->copy()->subDays(6)->startOfDay();
        $dailyRevenues = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startChart)
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'date');

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->format('Y-m-d');
            $chartData[] = ($dailyRevenues[$date] ?? 0) / 1000;
        }

        // Frais de service moyens (commission plateforme)
        $avgFee = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->whereNotNull('fee')
            ->avg('fee') ?? 0;

        // Frais totaux ce mois
        $totalFees = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('fee');

        return [
            Stat::make('Revenus ce mois', number_format($revenueThisMonth, 0, ',', ' ').' FCFA')
                ->description($growthPercent >= 0 ? "+{$growthPercent}% vs mois dernier" : "{$growthPercent}% vs mois dernier")
                ->descriptionIcon($growthPercent >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growthPercent >= 0 ? 'success' : 'danger')
                ->chart($chartData),

            Stat::make('Revenus cette semaine', number_format($revenueThisWeek, 0, ',', ' ').' FCFA')
                ->description('7 derniers jours')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Paiements en attente', $pendingCount)
                ->description(number_format($pendingAmount, 0, ',', ' ').' FCFA')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 0 ? 'warning' : 'success'),

            Stat::make('Paiements échoués', $failedCount)
                ->description('Ce mois')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($failedCount > 0 ? 'danger' : 'success'),

            Stat::make('Remboursements', number_format($refundedAmount, 0, ',', ' ').' FCFA')
                ->description("{$refundedCount} remboursement(s) ce mois")
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('warning'),

            Stat::make('Frais perçus', number_format($totalFees, 0, ',', ' ').' FCFA')
                ->description('Frais moy. '.number_format($avgFee, 0, ',', ' ').' FCFA')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
