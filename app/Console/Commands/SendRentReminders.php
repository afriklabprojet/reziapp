<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\RentReminderService;
use Illuminate\Console\Command;

class SendRentReminders extends Command
{
    protected $signature = 'rezi:send-rent-reminders';

    protected $description = 'Envoie automatiquement les relances de paiement aux locataires selon le calendrier configuré';

    public function handle(RentReminderService $service): int
    {
        $this->info('Traitement des relances de paiement automatiques...');

        $result = $service->processAutoReminders();

        $this->info("Relances envoyées : {$result['sent']}");
        $this->info("Relances en erreur : {$result['failed']}");

        return self::SUCCESS;
    }
}
