<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Residence;
use App\Models\User;
use App\Services\IdentityVerificationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Utilisateurs
        $totalUsers = User::count();
        $newUsersThisWeek = User::where('created_at', '>=', now()->subWeek())->count();
        $verifiedUsers = User::whereNotNull('identity_verified_at')->count();

        // Résidences
        $totalResidences = Residence::count();
        $pendingResidences = Residence::where('status', 'pending')->count();
        $approvedResidences = Residence::where('status', 'active')->count();

        // Réservations
        $bookingsThisMonth = Booking::where('created_at', '>=', now()->startOfMonth())->count();
        $revenueThisMonth = Payment::where('created_at', '>=', now()->startOfMonth())
            ->where('status', 'completed')
            ->sum('amount');

        // Vérifications en attente
        $verificationService = new IdentityVerificationService();
        $verificationStats = $verificationService->getVerificationStats();

        return [
            Stat::make('Utilisateurs', number_format($totalUsers))
                ->description("+{$newUsersThisWeek} cette semaine")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 12, 8, 15, 20, $newUsersThisWeek])
                ->color('success'),

            Stat::make('Identités vérifiées', number_format($verifiedUsers))
                ->description(round(($verifiedUsers / max(1, $totalUsers)) * 100).'% des utilisateurs')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),

            Stat::make('Vérifications en attente', $verificationStats['pending'])
                ->description('À traiter')
                ->descriptionIcon('heroicon-m-clock')
                ->color($verificationStats['pending'] > 0 ? 'warning' : 'success'),

            Stat::make('Résidences actives', number_format($approvedResidences))
                ->description("{$pendingResidences} en attente de validation")
                ->descriptionIcon('heroicon-m-home')
                ->color('primary'),

            Stat::make('Réservations du mois', number_format($bookingsThisMonth))
                ->description('Réservations ce mois')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('Revenus du mois', number_format($revenueThisMonth).' FCFA')
                ->description('Total des réservations terminées')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}
