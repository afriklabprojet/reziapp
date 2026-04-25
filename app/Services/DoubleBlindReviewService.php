<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\Review;
use App\Models\TenantReview;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reviews bilatérales aveugles (Airbnb pattern).
 * Cache la review tant que l'autre partie n'a pas posté OU 14j non écoulés.
 */
class DoubleBlindReviewService
{
    public const REVEAL_AFTER_DAYS = 14;

    /**
     * À appeler après création d'une Review (guest→owner).
     * Si l'owner a déjà posté son TenantReview pour ce booking, on publie les 2.
     */
    public function onGuestReviewCreated(Review $review): void
    {
        if (!$review->booking_id) {
            return;
        }

        $tenantReview = TenantReview::where('booking_id', $review->booking_id)->first();

        if ($tenantReview) {
            // L'autre côté a déjà posté → on révèle simultanément
            DB::transaction(function () use ($review, $tenantReview) {
                $now = now();
                if (!$review->published_at) {
                    $review->forceFill(['published_at' => $now, 'status' => Review::STATUS_APPROVED])->save();
                }
                if (!$tenantReview->published_at) {
                    $tenantReview->forceFill(['published_at' => $now])->save();
                }
            });
        }
    }

    /**
     * À appeler après création d'une TenantReview (owner→guest).
     */
    public function onOwnerReviewCreated(TenantReview $tenantReview): void
    {
        if (!$tenantReview->booking_id) {
            return;
        }

        $review = Review::where('booking_id', $tenantReview->booking_id)->first();

        if ($review) {
            DB::transaction(function () use ($review, $tenantReview) {
                $now = now();
                if (!$review->published_at) {
                    $review->forceFill(['published_at' => $now, 'status' => Review::STATUS_APPROVED])->save();
                }
                if (!$tenantReview->published_at) {
                    $tenantReview->forceFill(['published_at' => $now])->save();
                }
            });
        }
    }

    /**
     * Job périodique : publie les reviews qui ont dépassé la fenêtre de 14j
     * depuis la fin du séjour (check_out), même si l'autre partie n'a rien posté.
     *
     * @return array{published_guest_reviews:int, published_owner_reviews:int}
     */
    public function publishExpiredReviews(): array
    {
        $cutoff = now()->subDays(self::REVEAL_AFTER_DAYS);

        // Guest reviews (Review) — publier celles dont le séjour est terminé depuis 14j+
        $guestPublished = Review::query()
            ->whereNull('published_at')
            ->whereIn('status', [Review::STATUS_PENDING, Review::STATUS_APPROVED])
            ->whereHas('booking', function ($q) use ($cutoff) {
                $q->where('check_out', '<=', $cutoff);
            })
            ->update([
                'published_at' => now(),
                'status'       => Review::STATUS_APPROVED,
            ]);

        // Owner reviews (TenantReview) — même logique
        $ownerPublished = TenantReview::query()
            ->whereNull('published_at')
            ->whereHas('booking', function ($q) use ($cutoff) {
                $q->where('check_out', '<=', $cutoff);
            })
            ->update(['published_at' => now()]);

        return [
            'published_guest_reviews' => (int) $guestPublished,
            'published_owner_reviews' => (int) $ownerPublished,
        ];
    }

    /**
     * Une review est-elle visible publiquement ?
     */
    public function isPublished(Review|TenantReview $review): bool
    {
        return !is_null($review->published_at);
    }
}
