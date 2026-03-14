<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VacationMode;
use App\Models\Residence;
use App\Models\User;

class VacationModeService
{
    public function getForOwner(User $owner): ?VacationMode
    {
        return VacationMode::forOwner($owner->id)
            ->orderByDesc('created_at')
            ->first();
    }

    public function getActiveMode(User $owner): ?VacationMode
    {
        return VacationMode::forOwner($owner->id)
            ->active()
            ->first();
    }

    public function activate(User $owner, array $data): VacationMode
    {
        // Deactivate any existing active mode
        VacationMode::forOwner($owner->id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'deactivated_at' => now()]);

        $vacation = VacationMode::create([
            'owner_id'              => $owner->id,
            'start_date'            => $data['start_date'],
            'end_date'              => $data['end_date'],
            'auto_message'          => $data['auto_message'] ?? null,
            'affected_residences'   => $data['affected_residences'] ?? [],
            'is_active'             => true,
            'activated_at'          => now(),
        ]);

        // Mark affected residences as unavailable
        $this->updateResidenceAvailability($owner, $vacation, false);

        return $vacation;
    }

    public function deactivate(VacationMode $vacation): void
    {
        $vacation->deactivate();

        // Restore residence availability
        $this->updateResidenceAvailability($vacation->owner, $vacation, true);
    }

    private function updateResidenceAvailability(User $owner, VacationMode $vacation, bool $available): void
    {
        $query = $owner->residences();

        $affected = $vacation->affected_residences ?? [];
        if (!empty($affected)) {
            $query->whereIn('id', $affected);
        }

        $query->update(['is_available' => $available]);
    }
}
