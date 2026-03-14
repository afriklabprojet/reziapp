<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AutoCleaningService;
use Illuminate\Console\Command;

class ScheduleAutoCleanings extends Command
{
    protected $signature = 'rezi:schedule-cleanings {--days=7 : Nombre de jours à anticiper}';
    protected $description = 'Créer automatiquement les tâches de ménage pour les réservations à venir';

    public function handle(AutoCleaningService $service): int
    {
        $days    = (int) $this->option('days');
        $created = $service->scheduleUpcomingCleanings($days);

        $this->info("$created tâche(s) de ménage créée(s).");

        return self::SUCCESS;
    }
}
