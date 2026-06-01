<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Commande RGPD : anonymisation et purge des données personnelles
 * dont la durée de conservation légale est dépassée.
 *
 * TTL appliqués :
 *  - coordonnées GPS dans `contacts`   : 90 jours
 *  - search_histories                  : 365 jours
 *  - view_histories anonymes           : 365 jours
 */
class PrivacyCleanupCommand extends Command
{
    protected $signature = 'privacy:cleanup {--dry-run : Prévisualise les changements sans les appliquer}';

    protected $description = 'Anonymise les données personnelles RGPD dépassant leur TTL de conservation';

    public function handle(): int
    {
        $dryRun   = (bool) $this->option('dry-run');
        $cutoff90  = now()->subDays(90);
        $cutoff365 = now()->subDays(365);

        $contactsAnonymized    = $this->anonymizeContactGps($cutoff90, $dryRun);
        $searchHistoriesDeleted = $this->purgeSearchHistories($cutoff365, $dryRun);
        $viewHistoriesDeleted   = $this->purgeAnonymousViewHistories($cutoff365, $dryRun);

        if ($dryRun) {
            $this->warn('Mode dry-run : aucune modification appliquée.');
        }

        Log::info('privacy:cleanup exécuté', [
            'dry_run'                  => $dryRun,
            'contacts_anonymized'      => $dryRun ? 0 : $contactsAnonymized,
            'search_histories_deleted' => $dryRun ? 0 : $searchHistoriesDeleted,
            'view_histories_deleted'   => $dryRun ? 0 : $viewHistoriesDeleted,
        ]);

        return self::SUCCESS;
    }

    /**
     * Supprime les coordonnées GPS des contacts créés il y a plus de 90 jours.
     */
    private function anonymizeContactGps(\DateTimeInterface $cutoff, bool $dryRun): int
    {
        $query = DB::table('contacts')
            ->whereNotNull('user_latitude')
            ->where('created_at', '<', $cutoff);

        $count = $query->count();

        $this->info("Contacts avec coords GPS > 90 j : {$count}");

        if (! $dryRun && $count > 0) {
            $query->update([
                'user_latitude'  => null,
                'user_longitude' => null,
            ]);
            $this->info("  => {$count} contacts anonymisés (GPS supprimé).");
        }

        return $count;
    }

    /**
     * Supprime les search_histories créés il y a plus de 365 jours.
     */
    private function purgeSearchHistories(\DateTimeInterface $cutoff, bool $dryRun): int
    {
        $query = DB::table('search_histories')
            ->where('created_at', '<', $cutoff);

        $count = $query->count();

        $this->info("Search histories > 365 j : {$count}");

        if (! $dryRun && $count > 0) {
            $query->delete();
            $this->info("  => {$count} search histories supprimées.");
        }

        return $count;
    }

    /**
     * Supprime les view_histories anonymes créés il y a plus de 365 jours.
     */
    private function purgeAnonymousViewHistories(\DateTimeInterface $cutoff, bool $dryRun): int
    {
        $query = DB::table('view_histories')
            ->whereNull('user_id')
            ->where('created_at', '<', $cutoff);

        $count = $query->count();

        $this->info("View histories anonymes > 365 j : {$count}");

        if (! $dryRun && $count > 0) {
            $query->delete();
            $this->info("  => {$count} view histories anonymes supprimées.");
        }

        return $count;
    }
}
