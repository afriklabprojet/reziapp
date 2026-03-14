<?php

namespace App\Filament\Pages;

use App\Models\Blacklist;
use App\Models\EmergencyAlert;
use App\Models\FraudReport;
use App\Models\IdentityVerification;
use Filament\Pages\Page;

class SecurityDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string $view = 'filament.pages.security-dashboard';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?string $navigationLabel = 'Sécurité & Vérification';

    protected static ?string $title = 'Tableau de bord Sécurité';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = IdentityVerification::needsReview()->count()
            + FraudReport::pending()->count()
            + EmergencyAlert::active()->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $alerts = EmergencyAlert::active()->count();

        if ($alerts > 0) {
            return 'danger';
        }

        return 'warning';
    }

    public function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'recentIdentityRequests' => $this->getRecentIdentityRequests(),
            'recentFraudReports' => $this->getRecentFraudReports(),
            'activeAlerts' => $this->getActiveAlerts(),
            'recentBlacklist' => $this->getRecentBlacklist(),
        ];
    }

    protected function getStats(): array
    {
        return [
            'identity_pending' => IdentityVerification::needsReview()->count(),
            'identity_approved' => IdentityVerification::approved()->count(),
            'identity_total' => IdentityVerification::count(),
            'fraud_pending' => FraudReport::pending()->count(),
            'fraud_confirmed' => FraudReport::confirmed()->count(),
            'fraud_total' => FraudReport::count(),
            'alerts_active' => EmergencyAlert::active()->count(),
            'alerts_total' => EmergencyAlert::count(),
            'blacklist_count' => Blacklist::where('is_active', true)->count(),
        ];
    }

    protected function getRecentIdentityRequests()
    {
        return IdentityVerification::needsReview()
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    protected function getRecentFraudReports()
    {
        return FraudReport::pending()
            ->with(['reporter', 'targetUser'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    protected function getActiveAlerts()
    {
        return EmergencyAlert::active()
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    protected function getRecentBlacklist()
    {
        return Blacklist::where('is_active', true)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}
