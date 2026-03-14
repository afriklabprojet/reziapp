<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GuestScreeningService;
use Illuminate\Console\Command;

class RecalculateGuestScores extends Command
{
    protected $signature = 'rezi:recalculate-guest-scores';
    protected $description = 'Recalculer les scores de confiance de tous les voyageurs actifs';

    public function handle(GuestScreeningService $service): int
    {
        $users = User::where('role', 'client')
            ->whereHas('bookings')
            ->get();

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $service->calculateScore($user);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info($users->count() . ' score(s) recalculé(s).');

        return self::SUCCESS;
    }
}
