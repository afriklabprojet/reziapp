<?php

declare(strict_types=1);

namespace App\Support\Residences;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\BlockedDate;
use App\Models\MarketPriceData;
use App\Models\OwnerBadge;
use App\Models\Residence;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ResidenceShowSupport
{
    public function ensureResidenceIsVisible(Residence $residence, mixed $user): void
    {
        if ($residence->status !== 'active' && ($user === null || $user->id !== $residence->owner_id)) {
            abort(404);
        }
    }

    public function loadResidenceDisplayRelations(Residence $residence): void
    {
        $residence->load([
            'photos',
            'amenities',
            'owner:id,name,phone,avatar,identity_verified,created_at',
            'photos360',
            'pointsOfInterest',
            'activeBadges',
            'reviews' => function ($query) {
                $query->with('user:id,name')
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc');
            },
        ]);
    }

    public function buildShowViewData(Residence $residence, mixed $user, bool $isSponsored): array
    {
        [$activeViewers, $bookingsThisMonth, $lastBookedDaysAgo, $isSuperhost] = $this->resolveResidenceUrgencyMetrics($residence);
        [$responseRate, $avgResponseTime] = $this->resolveHostResponseMetrics($residence);

        return [
            'residence' => $residence,
            'similarResidences' => $this->getSimilarResidences($residence),
            'ownerPhone' => $residence->owner->phone ?? null,
            'ownerResidencesCount' => $this->getOwnerResidencesCount($residence),
            'canReview' => $this->canReviewResidence($residence, $user),
            'canContact' => $this->canContactResidence($residence, $user),
            'unavailableDates' => $this->getResidenceUnavailableDates($residence),
            'isSponsored' => $isSponsored,
            'activeViewers' => $activeViewers,
            'bookingsThisMonth' => $bookingsThisMonth,
            'lastBookedDaysAgo' => $lastBookedDaysAgo,
            'responseRate' => $responseRate,
            'avgResponseTime' => $avgResponseTime,
            'isSuperhost' => $isSuperhost,
            'priceSuggestion' => $this->getResidencePriceSuggestion($residence),
        ];
    }

    private function getOwnerResidencesCount(Residence $residence): int
    {
        if ($residence->owner === null) {
            return 0;
        }

        return (int) Cache::remember(
            "owner:{$residence->owner_id}:residences_count",
            config('rezi.cache_ttl'),
            fn () => $residence->owner->residences()->approved()->count(),
        );
    }

    private function getSimilarResidences(Residence $residence)
    {
        return Cache::remember(
            "residence:{$residence->id}:similar",
            config('rezi.cache_ttl'),
            fn () => Residence::approved()
                ->available()
                ->where('id', '!=', $residence->id)
                ->where(function ($query) use ($residence) {
                    $query->where('commune', $residence->commune)
                        ->orWhere('type', $residence->type);
                })
                ->with(['photos'])
                ->orderByRaw('CASE WHEN commune = ? THEN 0 ELSE 1 END', [$residence->commune])
                ->take(4)
                ->get(),
        );
    }

    private function resolveResidenceUrgencyMetrics(Residence $residence): array
    {
        $lastConfirmedAt = Booking::where('residence_id', $residence->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->orderByDesc('confirmed_at')
            ->value('confirmed_at');

        $lastBookedDaysAgo = $lastConfirmedAt !== null
            ? (int) Carbon::parse($lastConfirmedAt)->diffInDays(now())
            : null;

        $isSuperhost = OwnerBadge::where('user_id', $residence->owner_id)
            ->where('badge_type', OwnerBadge::TYPE_SUPERHOST)
            ->where('status', OwnerBadge::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        return [
            $residence->active_viewers_24h ?? 0,
            $residence->bookings_this_month ?? 0,
            $lastBookedDaysAgo,
            $isSuperhost,
        ];
    }

    private function resolveHostResponseMetrics(Residence $residence): array
    {
        $responseRate = $residence->response_rate ?? 0;
        $avgResponseTime = $residence->avg_response_time_hours ?? null;

        if ($responseRate != 0 || $avgResponseTime !== null) {
            return [$responseRate, $avgResponseTime];
        }

        $stats = BookingRequest::where('residence_id', $residence->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->selectRaw('COUNT(*) as total, SUM(responded_at IS NOT NULL) as responded')
            ->first();

        $total = (int) ($stats->total ?? 0);

        return [
            $total > 0 ? round((int) $stats->responded / $total * 100) : null,
            null,
        ];
    }

    private function getResidencePriceSuggestion(Residence $residence): ?array
    {
        $suggestion = null;

        if ($residence->commune) {
            $marketPrice = MarketPriceData::query()
                ->selectRaw('AVG(avg_price_per_night) as avg_price, AVG(median_price_per_night) as median_price')
                ->where('commune', $residence->commune)
                ->where('bedrooms', $residence->bedrooms)
                ->where('period_end', '>=', now()->subDays(30))
                ->first();

            if ($marketPrice && $residence->price_per_day > 0 && (float) ($marketPrice->avg_price ?? 0) > 0) {
                $diff = (($residence->price_per_day - $marketPrice->avg_price) / $marketPrice->avg_price) * 100;

                if ($diff > 20) {
                    $suggestion = ['type' => 'above', 'percent' => round(abs($diff)), 'market' => $marketPrice->avg_price];
                } elseif ($diff < -20) {
                    $suggestion = ['type' => 'below', 'percent' => round(abs($diff)), 'market' => $marketPrice->avg_price];
                }
            }
        }

        return $suggestion;
    }

    private function canReviewResidence(Residence $residence, mixed $user): bool
    {
        if ($user === null) {
            return false;
        }

        $hasCompletedBooking = Booking::where('user_id', $user->id)
            ->where('residence_id', $residence->id)
            ->where('status', 'completed')
            ->exists();
        $hasAlreadyReviewed = Review::where('user_id', $user->id)
            ->where('residence_id', $residence->id)
            ->exists();

        return $hasCompletedBooking && ! $hasAlreadyReviewed;
    }

    private function canContactResidence(Residence $residence, mixed $user): bool
    {
        if ($user === null || $user->id === $residence->owner_id) {
            return false;
        }

        return Booking::where('user_id', $user->id)
            ->where('residence_id', $residence->id)
            ->whereNotIn('status', ['cancelled', 'expired'])
            ->exists();
    }

    private function getResidenceUnavailableDates(Residence $residence): array
    {
        return Cache::remember(
            "residence:{$residence->id}:unavailable_dates",
            900,
            function () use ($residence) {
                $blockedDates = BlockedDate::getBlockedDatesArray(
                    $residence->id,
                    now()->toDateString(),
                    now()->addMonths(6)->toDateString(),
                );
                $bookedDates = Booking::where('residence_id', $residence->id)
                    ->whereIn('status', ['confirmed', 'pending', 'paid'])
                    ->where('check_out', '>=', now())
                    ->get()
                    ->flatMap(function ($booking) {
                        $dates = [];
                        $current = Carbon::parse($booking->check_in);
                        $end = Carbon::parse($booking->check_out);

                        while ($current < $end) {
                            $dates[] = $current->format('Y-m-d');
                            $current->addDay();
                        }

                        return $dates;
                    })
                    ->toArray();

                return array_values(array_unique(array_merge($blockedDates, $bookedDates)));
            },
        );
    }
}
