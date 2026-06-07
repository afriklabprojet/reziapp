<?php

namespace App\Services;

use App\Models\PropertyShare;
use App\Models\Residence;
use Illuminate\Support\Collection;

class ShareService
{
    /**
     * Create a share link for a residence
     */
    public function createShare(int $residenceId, string $platform, ?int $userId = null, ?string $ipAddress = null, ?string $userAgent = null): PropertyShare
    {
        return PropertyShare::createShare($residenceId, $platform, $userId, $ipAddress, $userAgent);
    }

    /**
     * Get share by token
     */
    public function getShareByToken(string $token): ?PropertyShare
    {
        return PropertyShare::where('share_token', $token)->first();
    }

    /**
     * Record a click on shared link
     */
    public function recordClick(string $token): bool
    {
        $share = $this->getShareByToken($token);

        if ($share) {
            $share->recordClick();

            return true;
        }

        return false;
    }

    /**
     * Record a booking from shared link
     */
    public function recordBooking(string $token): bool
    {
        $share = $this->getShareByToken($token);

        if ($share) {
            $share->recordBooking();

            return true;
        }

        return false;
    }

    /**
     * Generate all share URLs for a residence
     */
    public function generateShareUrls(int $residenceId, ?int $userId = null): array
    {
        $residence = Residence::findOrFail($residenceId);
        $baseUrl = route('residences.show', $residence);

        $shareLinks = [];
        $platforms = ['whatsapp', 'facebook', 'twitter', 'email', 'link'];

        foreach ($platforms as $platform) {
            $share = $this->createShare($residenceId, $platform, $userId);
            $shareLinks[$platform] = [
                'share_url' => $share->getShareUrl(),
                'platform_url' => $this->getPlatformUrl($share, $residence),
            ];
        }

        return $shareLinks;
    }

    /**
     * Get platform-specific share URL
     */
    protected function getPlatformUrl(PropertyShare $share, Residence $residence): string
    {
        $shareUrl = $share->getShareUrl();
        $title = $residence->title ?? 'Logement sur Rezi App';
        $quartierName = $residence->quartier ?? $residence->commune ?? $residence->city ?? 'Rezi App';
        $description = "Découvrez ce logement à {$quartierName} sur Rezi App";

        switch ($share->platform) {
            case 'whatsapp':
                $text = urlencode("{$description}\n\n{$shareUrl}");

                return "https://wa.me/?text={$text}";

            case 'facebook':
                return 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($shareUrl);

            case 'twitter':
                $text = urlencode($description);

                return "https://twitter.com/intent/tweet?text={$text}&url=".urlencode($shareUrl);

            case 'email':
                $subject = urlencode("Logement à découvrir : {$title}");
                $body = urlencode("Bonjour,\n\nJe voulais te partager ce logement que j'ai trouvé sur Rezi App :\n\n{$shareUrl}\n\nÀ bientôt !");

                return "mailto:?subject={$subject}&body={$body}";

            case 'sms':
                $body = urlencode("{$description} {$shareUrl}");

                return "sms:?body={$body}";

            default:
                return $shareUrl;
        }
    }

    /**
     * Get share statistics for a residence
     */
    public function getResidenceStats(int $residenceId): array
    {
        return PropertyShare::getStats($residenceId);
    }

    /**
     * Get owner's share statistics
     */
    public function getOwnerStats(int $ownerId): array
    {
        $residenceIds = Residence::where('owner_id', $ownerId)->pluck('id');

        $shares = PropertyShare::whereIn('residence_id', $residenceIds)->get();

        return [
            'total_shares' => $shares->count(),
            'total_clicks' => $shares->sum('click_count'),
            'total_bookings' => $shares->sum('booking_count'),
            'conversion_rate' => $shares->sum('click_count') > 0
                ? round(($shares->sum('booking_count') / $shares->sum('click_count')) * 100, 2)
                : 0,
            'by_platform' => $shares->groupBy('platform')->map(function ($group) {
                return [
                    'shares' => $group->count(),
                    'clicks' => $group->sum('click_count'),
                    'bookings' => $group->sum('booking_count'),
                ];
            }),
            'top_residences' => $shares->groupBy('residence_id')
                ->map(fn ($group) => $group->sum('click_count'))
                ->sortDesc()
                ->take(5),
        ];
    }

    /**
     * Get trending shares (most clicked in last 7 days)
     */
    public function getTrendingShares(int $limit = 10): Collection
    {
        return PropertyShare::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('residence_id, SUM(click_count) as total_clicks')
            ->groupBy('residence_id')
            ->orderByDesc('total_clicks')
            ->limit($limit)
            ->get();
    }

    /**
     * Copy share link to clipboard text
     */
    public function getCopyText(int $residenceId): string
    {
        $residence = Residence::findOrFail($residenceId);
        $url = route('residences.show', $residence);
        $quartierName = $residence->quartier ?? $residence->commune ?? $residence->city ?? 'Rezi App';

        return "🏠 {$residence->title}\n📍 {$quartierName}\n💰 ".number_format($residence->price, 0, ',', ' ')." FCFA/{$residence->price_label}\n\n{$url}";
    }
}
