<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\VacationMode;
use App\Services\VacationModeService;
use Illuminate\Console\Command;

class ManageVacationModes extends Command
{
    protected $signature = 'rezi:manage-vacation-modes';

    protected $description = 'Active et désactive automatiquement les modes vacances selon les dates configurées';

    public function handle(VacationModeService $service): int
    {
        $this->info('Gestion automatique des modes vacances...');

        $activated = 0;
        $deactivated = 0;

        // Auto-activate modes whose start_date has arrived
        $toActivate = VacationMode::query()
            ->where('is_active', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>', now())
            ->get();

        foreach ($toActivate as $mode) {
            $mode->update(['is_active' => true]);
            // Mark associated residences as unavailable
            if ($mode->residence_ids && count($mode->residence_ids)) {
                \App\Models\Residence::whereIn('id', $mode->residence_ids)
                    ->where('owner_id', $mode->user_id)
                    ->update(['is_available' => false]);
            } else {
                \App\Models\Residence::where('owner_id', $mode->user_id)
                    ->update(['is_available' => false]);
            }
            $activated++;
        }

        // Auto-deactivate modes whose end_date has passed
        $toDeactivate = VacationMode::query()
            ->where('is_active', true)
            ->where('end_date', '<=', now())
            ->get();

        foreach ($toDeactivate as $mode) {
            $service->deactivate($mode);
            $deactivated++;
        }

        $this->info("Modes activés : {$activated}");
        $this->info("Modes désactivés : {$deactivated}");

        return self::SUCCESS;
    }
}
