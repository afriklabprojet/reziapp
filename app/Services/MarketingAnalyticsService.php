<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Promotion;
use App\Models\Referral;
use App\Models\SponsoredListing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketingAnalyticsService
{
    public function getGeneralDashboardStats(): array
    {
        try {
            return [
                'total_campaigns' => Campaign::count(),
                'active_campaigns' => Campaign::whereIn('status', ['sending', 'scheduled'])->count(),
                'total_coupons' => Coupon::count(),
                'active_coupons' => Coupon::active()->count(),
                'total_referrals' => Referral::count(),
                'pending_referrals' => Referral::where('status', 'qualified')->count(),
                'active_promotions' => Promotion::active()->count(),
                'active_sponsored' => SponsoredListing::active()->count(),
            ];
        } catch (Throwable $exception) {
            Log::error('MarketingAnalytics: general dashboard stats failed', [
                'error' => $exception->getMessage(),
            ]);
            report($exception);

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

    /**
     * Statistiques marketing globales
     */
    public function getMarketingStats(): array
    {
        return [
            'promotions' => [
                'active' => Promotion::active()->count(),
                'total' => Promotion::count(),
                'uses' => Promotion::sum('uses_count'),
            ],
            'coupons' => [
                'active' => Coupon::active()->count(),
                'total' => Coupon::count(),
                'uses' => CouponUse::count(),
                'total_discount' => CouponUse::sum('discount_applied'),
            ],
            'referrals' => [
                'total' => Referral::count(),
                'pending' => Referral::where('status', 'pending')->count(),
                'rewarded' => Referral::where('status', 'rewarded')->count(),
                'total_rewards' => Referral::where('status', 'rewarded')
                    ->sum(DB::raw('referrer_reward + referred_reward')),
            ],
            'campaigns' => [
                'total' => Campaign::count(),
                'sent' => Campaign::where('status', 'sent')->count(),
                'emails_sent' => CampaignSend::where('status', 'sent')->count(),
            ],
            'sponsored' => [
                'active' => SponsoredListing::active()->count(),
                'total' => SponsoredListing::count(),
                'impressions' => SponsoredListing::sum('impressions'),
                'clicks' => SponsoredListing::sum('clicks'),
                'revenue' => SponsoredListing::sum('amount_spent'),
            ],
        ];
    }

    public function getSponsoredDashboardStats(): array
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
                'total' => (int) ($stats->total ?? 0),
                'active' => (int) ($stats->active ?? 0),
                'pending' => (int) ($stats->pending ?? 0),
                'total_revenue' => (float) ($stats->total_revenue ?? 0),
                'total_impressions' => (int) ($stats->total_impressions ?? 0),
                'total_clicks' => (int) ($stats->total_clicks ?? 0),
                'ctr' => ($stats->total_impressions ?? 0) > 0
                    ? round(($stats->total_clicks / $stats->total_impressions) * 100, 2)
                    : 0,
            ];
        } catch (Throwable) {
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

    public function getSponsoredWidgetStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $activeCount = SponsoredListing::where('status', 'active')->count();
        $pendingCount = SponsoredListing::where('status', 'pending')->count();

        $revenueThisMonth = SponsoredListing::where('created_at', '>=', $startOfMonth)
            ->where('is_paid', true)
            ->sum('total_budget');

        $revenueLastMonth = SponsoredListing::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->where('is_paid', true)
            ->sum('total_budget');

        $growthPercent = 0;
        if ($revenueLastMonth > 0) {
            $growthPercent = round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1);
        } elseif ($revenueThisMonth > 0) {
            $growthPercent = 100;
        }

        $totalImpressions = SponsoredListing::sum('impressions');
        $totalClicks = SponsoredListing::sum('clicks');
        $totalContacts = SponsoredListing::sum('contacts_generated');

        $dailyCounts = SponsoredListing::where('created_at', '>=', $now->copy()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->format('Y-m-d');
            $chartData[] = $dailyCounts[$date] ?? 0;
        }

        return [
            'active' => $activeCount,
            'pending' => $pendingCount,
            'revenue_this_month' => (float) $revenueThisMonth,
            'growth_percent' => $growthPercent,
            'total_impressions' => (int) $totalImpressions,
            'total_clicks' => (int) $totalClicks,
            'global_ctr' => $totalImpressions > 0
                ? round(($totalClicks / $totalImpressions) * 100, 2)
                : 0,
            'total_contacts' => (int) $totalContacts,
            'chart' => $chartData,
        ];
    }
}
