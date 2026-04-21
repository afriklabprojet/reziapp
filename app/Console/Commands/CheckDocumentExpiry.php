<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\OwnerDocument;
use App\Models\User;
use App\Notifications\DocumentExpiryNotification;
use Illuminate\Console\Command;

class CheckDocumentExpiry extends Command
{
    protected $signature = 'rezi:check-document-expiry';

    protected $description = 'Vérifie les documents qui expirent bientôt et notifie les propriétaires';

    public function handle(): int
    {
        $this->info('Vérification des documents expirant bientôt...');

        $expiringDocuments = OwnerDocument::query()
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->whereNull('deleted_at')
            ->with('owner')
            ->get();

        $notified = 0;

        foreach ($expiringDocuments->groupBy('owner_id') as $ownerId => $documents) {
            $user = User::find($ownerId);
            if (!$user) {
                continue;
            }

            $user->notify(new DocumentExpiryNotification($documents));

            // Send a simple database notification
            $user->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\Notifications\DocumentExpiryNotification',
                'data' => [
                    'title' => 'Documents expirant bientôt',
                    'message' => $documents->count().' document(s) expirent dans les 30 prochains jours.',
                    'documents' => $documents->map(fn ($d) => [
                        'name' => $d->name,
                        'expiry_date' => $d->expiry_date->format('d/m/Y'),
                    ])->toArray(),
                ],
                'read_at' => null,
            ]);

            $notified++;
        }

        $this->info("Propriétaires notifiés : {$notified}");
        $this->info("Documents concernés : {$expiringDocuments->count()}");

        return self::SUCCESS;
    }
}
