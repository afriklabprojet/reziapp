<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SmartLockService;
use Illuminate\Console\Command;

class ExpireSmartLockCodes extends Command
{
    protected $signature = 'rezi:expire-lock-codes';
    protected $description = 'Expirer les codes de serrures connectées dont la validité a expiré';

    public function handle(SmartLockService $service): int
    {
        $expired = $service->expireOldCodes();

        $this->info("$expired code(s) expiré(s).");

        return self::SUCCESS;
    }
}
