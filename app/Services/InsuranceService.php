<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InsuranceSubscription;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class InsuranceService
{
    public function getSubscriptions(User $owner, array $filters = []): LengthAwarePaginator
    {
        $query = InsuranceSubscription::forOwner($owner->id)->with('residence');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function create(User $owner, array $data): InsuranceSubscription
    {
        $data['owner_id'] = $owner->id;
        $data['status']   = InsuranceSubscription::STATUS_ACTIVE;

        return InsuranceSubscription::create($data);
    }

    public function update(InsuranceSubscription $subscription, array $data): InsuranceSubscription
    {
        $subscription->update($data);
        return $subscription->fresh();
    }

    public function cancel(InsuranceSubscription $subscription): void
    {
        $subscription->update(['status' => InsuranceSubscription::STATUS_CANCELLED]);
    }

    public function getExpiringSoon(User $owner, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return InsuranceSubscription::forOwner($owner->id)
            ->expiringSoon($days)
            ->with('residence')
            ->get();
    }

    public function getTotalMonthlyCost(User $owner): float
    {
        return (float) InsuranceSubscription::forOwner($owner->id)
            ->active()
            ->sum('monthly_premium');
    }
}
