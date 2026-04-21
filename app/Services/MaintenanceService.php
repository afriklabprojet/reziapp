<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class MaintenanceService
{
    public function getRequests(User $owner, array $filters = []): LengthAwarePaginator
    {
        $query = MaintenanceRequest::forOwner($owner->id)
            ->with(['residence', 'reporter', 'assignee']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['residence_id'])) {
            $query->where('residence_id', $filters['residence_id']);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function create(User $owner, array $data): MaintenanceRequest
    {
        $data['owner_id']    = $owner->id;
        $data['reported_by'] = $data['reported_by'] ?? $owner->id;
        $data['status']      = MaintenanceRequest::STATUS_REPORTED;

        return MaintenanceRequest::create($data);
    }

    public function update(MaintenanceRequest $request, array $data): MaintenanceRequest
    {
        $request->update($data);

        return $request->fresh();
    }

    public function updateStatus(MaintenanceRequest $request, string $status): void
    {
        $updates = ['status' => $status];

        if ($status === MaintenanceRequest::STATUS_RESOLVED) {
            $updates['resolved_at'] = now();
        }

        $request->update($updates);
    }

    public function assign(MaintenanceRequest $request, int $userId): void
    {
        $request->update([
            'assigned_to' => $userId,
            'status'      => MaintenanceRequest::STATUS_ACKNOWLEDGED,
        ]);
    }

    public function getDashboardStats(User $owner): array
    {
        $requests = MaintenanceRequest::forOwner($owner->id);

        return [
            'total'       => $requests->count(),
            'open'        => (clone $requests)->open()->count(),
            'urgent'      => (clone $requests)->urgent()->count(),
            'resolved'    => (clone $requests)->where('status', MaintenanceRequest::STATUS_RESOLVED)->count(),
            'avg_resolution_days' => MaintenanceRequest::forOwner($owner->id)
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG(DATEDIFF(resolved_at, created_at)) as avg_days')
                ->value('avg_days') ?? 0,
        ];
    }
}
