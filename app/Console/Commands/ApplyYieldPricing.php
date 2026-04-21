<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\YieldManagementService;
use Illuminate\Console\Command;

class ApplyYieldPricing extends Command
{
    protected $signature = 'rezi:apply-yield {--days=60 : Jours à planifier}';
    protected $description = 'Appliquer le yield management et gap-night pricing automatiquement';

    public function handle(YieldManagementService $service): int
    {
        $days    = (int) $this->option('days');
        $results = $service->applyAutoPricing($days);

        $this->info(count($results).' résidence(s) traité(s) pour le yield management.');

        $gapResults = $service->applyGapNightPricing();
        $this->info(count($gapResults).' résidence(s) avec gap-night pricing appliqué.');

        return self::SUCCESS;
    }
}
