<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return Cache::remember('admin.stats_overview', 300, fn () => $this->computeStats());
    }

    protected function computeStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Utilisateurs
        $totalUsers = User::where('role', '!=', 'admin')->count();
        $newUsersThisMonth = User::where('role', '!=', 'admin')
            ->where('created_at', '>=', $startOfMonth)
            ->count();
        $usersLastMonth = User::where('role', '!=', 'admin')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();
        $userGrowth = $usersLastMonth > 0
            ? round((($newUsersThisMonth - $usersLastMonth) / $usersLastMonth) * 100, 1)
            : 100;

        // Propriétaires
        $totalOwners = User::where('role', 'owner')->count();

        // Résidences
        $totalResidences = Residence::count();
        $activeResidences = Residence::where('status', 'active')->count();
        $pendingResidences = Residence::where('status', 'pending')->count();

        // Réservations
        $totalBookings = Booking::count();
        $bookingsThisMonth = Booking::where('created_at', '>=', $startOfMonth)->count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();

        // Revenus
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $revenueThisMonth = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('amount');
        $revenueLastMonth = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');
        $revenueGrowth = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : 100;

        // Commission plateforme (dynamique depuis les paramètres)
        $commissionRate = \App\Models\PlatformSetting::getValue('commission_rate', 10) / 100;
        $platformCommission = $totalRevenue * $commissionRate;

        // Versements aux propriétaires
        $totalPayouts = Payout::where('status', 'completed')->sum('net_amount');
        $pendingPayouts = Payout::where('status', 'pending')->sum('net_amount');

        return [
            Stat::make('Utilisateurs', number_format($totalUsers, 0, ',', ' '))
                ->description($newUsersThisMonth.' ce mois ('.($userGrowth >= 0 ? '+' : '').$userGrowth.'%)')
                ->descriptionIcon($userGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($userGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getWeeklyData(User::class, 'created_at')),

            Stat::make('Propriétaires', number_format($totalOwners, 0, ',', ' '))
                ->description($activeResidences.' annonces actives')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('info'),

            Stat::make('Résidences', number_format($totalResidences, 0, ',', ' '))
                ->description($pendingResidences.' en attente de validation')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingResidences > 0 ? 'warning' : 'success')
                ->chart($this->getWeeklyData(Residence::class, 'created_at')),

            Stat::make('Réservations', number_format($totalBookings, 0, ',', ' '))
                ->description($bookingsThisMonth.' ce mois • '.$confirmedBookings.' confirmées')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->chart($this->getWeeklyData(Booking::class, 'created_at')),

            Stat::make('Revenus totaux', number_format($totalRevenue, 0, ',', ' ').' FCFA')
                ->description(number_format($revenueThisMonth, 0, ',', ' ').' ce mois ('.($revenueGrowth >= 0 ? '+' : '').$revenueGrowth.'%)')
                ->descriptionIcon($revenueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getWeeklyRevenueData()),

            Stat::make('Commission plateforme', number_format($platformCommission, 0, ',', ' ').' FCFA')
                ->description(round($commissionRate * 100).'% des transactions')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Versements effectués', number_format($totalPayouts, 0, ',', ' ').' FCFA')
                ->description(number_format($pendingPayouts, 0, ',', ' ').' FCFA en attente')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($pendingPayouts > 0 ? 'warning' : 'success'),
        ];
    }

    protected function getWeeklyData(string $model, string $dateColumn): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();

        $counts = $model::where($dateColumn, '>=', $startDate)
            ->selectRaw('DATE('.$dateColumn.') as date, COUNT(*) as count')
            ->groupByRaw('DATE('.$dateColumn.')')
            ->pluck('count', 'date');

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $data[] = $counts[$date] ?? 0;
        }

        return $data;
    }

    protected function getWeeklyRevenueData(): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();

        $revenues = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'date');

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $data[] = ($revenues[$date] ?? 0) / 1000;
        }

        return $data;
    }
}
