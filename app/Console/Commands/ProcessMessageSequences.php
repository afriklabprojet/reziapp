<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MessageSequenceService;
use Illuminate\Console\Command;

class ProcessMessageSequences extends Command
{
    protected $signature = 'rezi:process-sequences';
    protected $description = 'Envoyer les messages de séquence planifiés prêts';

    public function handle(MessageSequenceService $service): int
    {
        $sent = $service->sendPendingMessages();

        $this->info("$sent message(s) envoyé(s).");

        return self::SUCCESS;
    }
}
