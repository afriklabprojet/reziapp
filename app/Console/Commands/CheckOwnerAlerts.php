<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OwnerAlertService;
use Illuminate\Console\Command;

class CheckOwnerAlerts extends Command
{
    protected $signature = 'rezi:check-owner-alerts';
    protected $description = 'Vérifier les SLA, avis en attente, et nuits orphelines pour alerter les propriétaires';

    public function handle(OwnerAlertService $service): int
    {
        $slaAlerts    = $service->checkResponseTimeSLA();
        $gapAlerts    = $service->checkBookingGaps();
        $reviewAlerts = $service->checkPendingReviews();

        $total = $slaAlerts + $gapAlerts + $reviewAlerts;
        $this->info("$total alerte(s) créée(s) (SLA: $slaAlerts, gaps: $gapAlerts, avis: $reviewAlerts).");

        return self::SUCCESS;
    }
}
