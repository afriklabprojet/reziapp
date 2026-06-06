<?php

namespace App\Services;

use App\Models\Residence;
use App\Models\SponsoredListing;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SponsoredListingService
{
    private const DEFAULT_TYPE = 'premium_listing';

    private const DEFAULT_WEEKLY_PRICE = 7500;

    /**
     * Créer un listing sponsorisé
     */
    public function createSponsoredListing(array $data): SponsoredListing
    {
        $type = $data['type'] ?? self::DEFAULT_TYPE;
        $priceKey = "rezi.sponsored.{$type}_price_weekly";
        $weeklyPrice = config($priceKey, self::DEFAULT_WEEKLY_PRICE);

        return SponsoredListing::create([
            'residence_id' => $data['residence_id'],
            'user_id' => $data['user_id'],
            'type' => $type,
            'duration_days' => $data['duration_days'] ?? null,
            'daily_budget' => $data['daily_budget'] ?? null,
            'total_budget' => $data['total_budget'] ?? $weeklyPrice,
            'amount_spent' => 0,
            'billing_type' => $data['billing_type'] ?? 'flat_rate',
            'cost_per_unit' => $data['cost_per_unit'] ?? 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'impressions' => $data['impressions'] ?? 0,
            'clicks' => $data['clicks'] ?? 0,
            'contacts_generated' => $data['contacts_generated'] ?? 0,
            'target_communes' => $data['target_communes'] ?? null,
            'target_user_types' => $data['target_user_types'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'is_paid' => $data['is_paid'] ?? false,
        ]);
    }

    public function getOwnerStats(int $userId): array
    {
        $baseQuery = SponsoredListing::query()->where('user_id', $userId);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->active()->count(),
            'total_spent' => (float) ((clone $baseQuery)->sum('amount_spent') ?? 0),
            'total_impressions' => (int) ((clone $baseQuery)->sum('impressions') ?? 0),
            'total_clicks' => (int) ((clone $baseQuery)->sum('clicks') ?? 0),
            'total_contacts' => (int) ((clone $baseQuery)->sum('contacts_generated') ?? 0),
        ];

        $stats['ctr'] = $stats['total_impressions'] > 0
            ? round(($stats['total_clicks'] / $stats['total_impressions']) * 100, 2)
            : 0;

        return $stats;
    }

    /**
     * Activer un listing sponsorisé après paiement
     */
    public function activateSponsoredListing(SponsoredListing $sponsored): bool
    {
        if ($sponsored->status !== 'pending') {
            return false;
        }

        $sponsored->update([
            'status' => 'active',
            'starts_at' => now(),
        ]);

        return true;
    }

    public function markPaymentAsSuccessful(
        SponsoredListing $sponsored,
        ?string $paymentReference = null,
        ?string $paymentMethod = null,
        ?DateTimeInterface $paidAt = null,
    ): SponsoredListing {
        $duration = $sponsored->duration_days ?? 7;

        $sponsored->update([
            'is_paid' => true,
            'status' => 'active',
            'payment_status' => 'success',
            'payment_reference' => $paymentReference ?? $sponsored->payment_reference,
            'payment_method' => $paymentMethod ?? $sponsored->payment_method,
            'paid_at' => $paidAt ?? now(),
            'starts_at' => $sponsored->starts_at ?? now(),
            'ends_at' => $sponsored->ends_at ?? now()->addDays($duration),
        ]);

        return $sponsored;
    }

    public function markPaymentAsFailed(SponsoredListing $sponsored): SponsoredListing
    {
        $sponsored->update([
            'payment_status' => 'error',
        ]);

        return $sponsored;
    }

    /**
     * Enregistrer une impression
     */
    public function recordSponsoredImpression(SponsoredListing $sponsored, ?string $ip = null, ?int $userId = null): void
    {
        if ($sponsored->canRun()) {
            $sponsored->recordImpression($ip ?? request()->ip(), $userId ?? Auth::id());
        }
    }

    /**
     * Enregistrer un clic
     */
    public function recordSponsoredClick(SponsoredListing $sponsored, ?string $ip = null, ?int $userId = null): void
    {
        if ($sponsored->canRun()) {
            $sponsored->recordClick($ip ?? request()->ip(), $userId ?? Auth::id());
        }
    }

    /**
     * Obtenir les résidences sponsorisées pour l'affichage
     */
    public function getSponsoredResidences(string $type = self::DEFAULT_TYPE, int $limit = 5)
    {
        return Residence::whereHas('sponsoredListings', function ($query) use ($type) {
            $query->active()->where('type', $type);
        })
            ->with(['photos', 'sponsoredListings' => function ($query) use ($type) {
                $query->active()->where('type', $type);
            }])
            ->approved()
            ->available()
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getTopSearchResidenceIds(): array
    {
        return SponsoredListing::topSearch()
            ->pluck('residence_id')
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    public function getFeaturedHomeResidenceIds(): array
    {
        return SponsoredListing::featuredHome()
            ->pluck('residence_id')
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    public function recordTopSearchImpressions(array $residenceIds, ?string $ip = null, ?int $userId = null): void
    {
        if ($residenceIds === []) {
            return;
        }

        SponsoredListing::topSearch()
            ->whereIn('residence_id', $residenceIds)
            ->each(fn (SponsoredListing $listing) => $this->recordSponsoredImpression($listing, $ip, $userId));
    }

    public function recordFeaturedHomeImpressions(array $residenceIds, ?string $ip = null, ?int $userId = null): void
    {
        if ($residenceIds === []) {
            return;
        }

        SponsoredListing::featuredHome()
            ->whereIn('residence_id', $residenceIds)
            ->each(fn (SponsoredListing $listing) => $this->recordSponsoredImpression($listing, $ip, $userId));
    }

    public function recordResidenceClick(Residence $residence, ?string $ip = null, ?int $userId = null): void
    {
        $activeSponsoredListing = $residence->activeSponsoredListing();

        if ($activeSponsoredListing instanceof SponsoredListing) {
            $this->recordSponsoredClick($activeSponsoredListing, $ip, $userId);
        }
    }

    public function recordResidenceContact(Residence $residence, ?string $ip = null, ?int $userId = null): void
    {
        $activeSponsoredListing = $residence->activeSponsoredListing();

        if ($activeSponsoredListing instanceof SponsoredListing) {
            $activeSponsoredListing->recordContact($ip ?? request()->ip(), $userId ?? Auth::id());
        }
    }

    public function getExpiringActiveListings(DateTimeInterface $threshold): Collection
    {
        return SponsoredListing::where('status', 'active')
            ->where('ends_at', '<=', $threshold)
            ->where('ends_at', '>', now())
            ->with(['residence.owner', 'user'])
            ->get();
    }

    public function completeExpiredActiveListings(): Collection
    {
        $expiredListings = SponsoredListing::where('status', 'active')
            ->where('ends_at', '<', now())
            ->get();

        $expiredListings->each(fn (SponsoredListing $listing) => $listing->complete());

        return $expiredListings->loadMissing(['residence', 'user']);
    }

    public function pauseBudgetExhaustedListings(): Collection
    {
        $budgetExhaustedListings = SponsoredListing::where('status', 'active')
            ->whereNotNull('total_budget')
            ->whereColumn('amount_spent', '>=', 'total_budget')
            ->get();

        $budgetExhaustedListings->each(fn (SponsoredListing $listing) => $listing->pause());

        return $budgetExhaustedListings->loadMissing(['residence', 'user']);
    }

    public function completeExpiredPausedListings(): Collection
    {
        $expiredPausedListings = SponsoredListing::where('status', 'paused')
            ->where('ends_at', '<', now())
            ->get();

        $expiredPausedListings->each(fn (SponsoredListing $listing) => $listing->complete());

        return $expiredPausedListings->loadMissing(['residence', 'user']);
    }

    /**
     * Calculer le CTR d'un listing sponsorisé
     */
    public function calculateCTR(SponsoredListing $sponsored): float
    {
        if ($sponsored->impressions === 0) {
            return 0;
        }

        return round(($sponsored->clicks / $sponsored->impressions) * 100, 2);
    }
}
