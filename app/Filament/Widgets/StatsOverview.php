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

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
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
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $data[] = $model::whereDate($dateColumn, $date)->count();
        }

        return $data;
    }

    protected function getWeeklyRevenueData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $data[] = Payment::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount') / 1000; // En milliers pour le graphique
        }

        return $data;
    }
}
