<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class DamageReportService
{
    public function getReports(User $owner, array $filters = []): LengthAwarePaginator
    {
        $query = DamageReport::forOwner($owner->id)
            ->with(['residence', 'booking', 'reporter']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['residence_id'])) {
            $query->where('residence_id', $filters['residence_id']);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function create(User $reporter, array $data): DamageReport
    {
        $data['reported_by'] = $reporter->id;
        $data['reference']   = 'DMG-'.date('Y').'-'.strtoupper(Str::random(8));

        return DamageReport::create($data);
    }

    public function updateStatus(DamageReport $report, string $status, array $extra = []): DamageReport
    {
        $data = ['status' => $status];

        if ($status === 'assessed') {
            $data['assessed_at'] = now();
        }
        if ($status === 'repaired') {
            $data['repaired_at'] = now();
        }

        $report->update(array_merge($data, $extra));

        return $report->fresh();
    }
}
