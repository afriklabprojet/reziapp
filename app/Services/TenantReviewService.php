<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TenantReview;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class TenantReviewService
{
    public function getReviews(User $owner, array $filters = []): LengthAwarePaginator
    {
        $query = TenantReview::forOwner($owner->id)
            ->with(['tenant', 'residence', 'booking']);

        if (!empty($filters['residence_id'])) {
            $query->where('residence_id', $filters['residence_id']);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function create(User $owner, array $data): TenantReview
    {
        $data['owner_id'] = $owner->id;

        return TenantReview::create($data);
    }

    public function update(TenantReview $review, array $data): TenantReview
    {
        $review->update($data);

        return $review->fresh();
    }

    public function delete(TenantReview $review): void
    {
        $review->delete();
    }

    public function getTenantScore(int $tenantId): array
    {
        $reviews = TenantReview::forTenant($tenantId)->get();

        if ($reviews->isEmpty()) {
            return ['score' => null, 'count' => 0, 'dimensions' => []];
        }

        $dimensions = [];
        foreach (TenantReview::RATING_DIMENSIONS as $key => $label) {
            $dimensions[$key] = [
                'label'   => $label,
                'average' => round($reviews->avg($key), 1),
            ];
        }

        return [
            'score'      => round($reviews->avg('overall_rating'), 1),
            'count'      => $reviews->count(),
            'dimensions' => $dimensions,
            'would_rent_again_pct' => $reviews->count() > 0
                ? round($reviews->where('would_rent_again', true)->count() / $reviews->count() * 100)
                : 0,
        ];
    }
}
