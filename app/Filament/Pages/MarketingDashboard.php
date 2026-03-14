<?php

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Models\Coupon;
use App\Models\Promotion;
use App\Models\Referral;
use App\Models\SponsoredListing;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MarketingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.marketing-dashboard';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static ?string $title = 'Tableau de bord Marketing';

    protected static ?int $navigationSort = 0;

    public function getViewData(): array
    {
        return [
            // Statistiques générales
            'stats' => $this->getGeneralStats(),

            // Campagnes
            'campaignStats' => $this->getCampaignStats(),
            'recentCampaigns' => $this->getRecentCampaigns(),

            // Coupons
            'couponStats' => $this->getCouponStats(),
            'topCoupons' => $this->getTopCoupons(),

            // Parrainages
            'referralStats' => $this->getReferralStats(),
            'topReferrers' => $this->getTopReferrers(),

            // Promotions
            'promotionStats' => $this->getPromotionStats(),

            // Annonces sponsorisées
            'sponsoredStats' => $this->getSponsoredStats(),
        ];
    }

    protected function getGeneralStats(): array
    {
        try {
            return [
                'total_campaigns' => Campaign::count(),
                'active_campaigns' => Campaign::whereIn('status', ['sending', 'scheduled'])->count(),
                'total_coupons' => Coupon::count(),
                'active_coupons' => Coupon::where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                    })->count(),
                'total_referrals' => Referral::count(),
                'pending_referrals' => Referral::where('status', 'qualified')->count(),
                'active_promotions' => Promotion::where('starts_at', '<=', now())
                    ->where('ends_at', '>=', now())->count(),
                'active_sponsored' => SponsoredListing::where('status', 'active')->count(),
            ];
        } catch (\Exception $e) {
            return [
                'total_campaigns' => 0,
                'active_campaigns' => 0,
                'total_coupons' => 0,
                'active_coupons' => 0,
                'total_referrals' => 0,
                'pending_referrals' => 0,
                'active_promotions' => 0,
                'active_sponsored' => 0,
            ];
        }
    }

    protected function getCampaignStats(): array
    {
        $campaigns = Campaign::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
            SUM(COALESCE(recipients_count, 0)) as total_sent,
            SUM(COALESCE(opened_count, 0)) as total_opened,
            SUM(COALESCE(clicked_count, 0)) as total_clicked
        ')->first();

        $openRate = $campaigns->total_sent > 0
            ? round(($campaigns->total_opened / $campaigns->total_sent) * 100, 1)
            : 0;
        $clickRate = $campaigns->total_opened > 0
            ? round(($campaigns->total_clicked / $campaigns->total_opened) * 100, 1)
            : 0;

        return [
            'total' => $campaigns->total,
            'sent' => $campaigns->sent,
            'draft' => $campaigns->draft,
            'scheduled' => $campaigns->scheduled,
            'total_sent' => $campaigns->total_sent,
            'total_opened' => $campaigns->total_opened,
            'total_clicked' => $campaigns->total_clicked,
            'open_rate' => $openRate,
            'click_rate' => $clickRate,
        ];
    }

    protected function getRecentCampaigns(): \Illuminate\Support\Collection
    {
        return Campaign::with('user')
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'type', 'status', 'recipients_count', 'opened_count', 'sent_at', 'created_at', 'user_id']);
    }

    protected function getCouponStats(): array
    {
        try {
            return [
                'total' => Coupon::count(),
                'active' => Coupon::where('is_active', true)->count(),
                'expired' => Coupon::where('expires_at', '<', now())->count(),
                'total_uses' => Coupon::sum('uses_count') ?? 0,
                'total_savings' => DB::table('coupon_uses')->exists()
                    ? (DB::table('coupon_uses')->sum('discount_amount') ?? 0)
                    : 0,
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'expired' => 0,
                'total_uses' => 0,
                'total_savings' => 0,
            ];
        }
    }

    protected function getTopCoupons(): \Illuminate\Support\Collection
    {
        return Coupon::orderByDesc('uses_count')
            ->take(5)
            ->get(['id', 'code', 'name', 'discount_type', 'discount_value', 'uses_count', 'max_uses']);
    }

    protected function getReferralStats(): array
    {
        $stats = Referral::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "qualified" THEN 1 ELSE 0 END) as qualified,
            SUM(CASE WHEN status = "rewarded" THEN 1 ELSE 0 END) as rewarded,
            SUM(COALESCE(referrer_reward, 0)) as total_referrer_rewards,
            SUM(COALESCE(referred_reward, 0)) as total_referred_rewards
        ')->first();

        return [
            'total' => $stats->total,
            'pending' => $stats->pending,
            'qualified' => $stats->qualified,
            'rewarded' => $stats->rewarded,
            'total_rewards' => $stats->total_referrer_rewards + $stats->total_referred_rewards,
            'conversion_rate' => $stats->total > 0
                ? round(($stats->rewarded / $stats->total) * 100, 1)
                : 0,
        ];
    }

    protected function getTopReferrers(): \Illuminate\Support\Collection
    {
        return DB::table('referrals')
            ->join('users', 'referrals.referrer_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.email', 'users.referral_code')
            ->selectRaw('COUNT(referrals.id) as referral_count')
            ->selectRaw('SUM(CASE WHEN referrals.status = "rewarded" THEN 1 ELSE 0 END) as successful_referrals')
            ->selectRaw('SUM(COALESCE(referrals.referrer_reward, 0)) as total_earned')
            ->groupBy('users.id', 'users.name', 'users.email', 'users.referral_code')
            ->orderByDesc('referral_count')
            ->take(5)
            ->get();
    }

    protected function getPromotionStats(): array
    {
        $now = now();

        return [
            'total' => Promotion::count(),
            'active' => Promotion::where('starts_at', '<=', $now)
                ->where('ends_at', '>=', $now)->count(),
            'upcoming' => Promotion::where('starts_at', '>', $now)->count(),
            'expired' => Promotion::where('ends_at', '<', $now)->count(),
        ];
    }

    protected function getSponsoredStats(): array
    {
        try {
            $stats = SponsoredListing::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN is_paid = 1 THEN total_budget ELSE 0 END) as total_revenue,
                SUM(COALESCE(impressions, 0)) as total_impressions,
                SUM(COALESCE(clicks, 0)) as total_clicks
            ')->first();

            return [
                'total' => $stats->total ?? 0,
                'active' => $stats->active ?? 0,
                'pending' => $stats->pending ?? 0,
                'total_revenue' => $stats->total_revenue ?? 0,
                'total_impressions' => $stats->total_impressions ?? 0,
                'total_clicks' => $stats->total_clicks ?? 0,
                'ctr' => ($stats->total_impressions ?? 0) > 0
                    ? round(($stats->total_clicks / $stats->total_impressions) * 100, 2)
                    : 0,
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'pending' => 0,
                'total_revenue' => 0,
                'total_impressions' => 0,
                'total_clicks' => 0,
                'ctr' => 0,
            ];
        }
    }
}
