<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\IcalService;
use Illuminate\Console\Command;

class SyncIcalFeeds extends Command
{
    protected $signature = 'rezi:sync-ical';
    protected $description = 'Synchroniser les flux iCal depuis Airbnb, Booking.com, etc.';

    public function handle(IcalService $service): int
    {
        $synced = $service->syncAll();

        $this->info("$synced flux iCal synchronisé(s).");

        return self::SUCCESS;
    }
}
