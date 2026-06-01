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
 *  - coordonnées GPS dans `contacts`  : 90 jours
 *  - search_histories                 : 365 jours
 *  - view_history (last_viewed_at)    : 365 jours
 */
class PrivacyCleanupCommand extends Command
{
    protected $signature = 'privacy:cleanup {--dry-run : Prévisualise les changements sans les appliquer}';

    protected $description = 'Anonymise les données personnelles RGPD dépassant leur TTL de conservation';

    private const CHUNK_SIZE = 500;

    public function handle(): int
    {
        $dryRun   = (bool) $this->option('dry-run');
        $cutoff90  = now()->subDays(90);
        $cutoff365 = now()->subDays(365);

        $contactsAnonymized     = $this->anonymizeContactGps($cutoff90, $dryRun);
        $searchHistoriesDeleted = $this->purgeSearchHistories($cutoff365, $dryRun);
        $viewHistoriesDeleted   = $this->purgeViewHistory($cutoff365, $dryRun);

        if ($dryRun) {
            $this->warn('Mode dry-run : aucune modification appliquée.');
        }

        Log::info('privacy:cleanup exécuté', [
            'dry_run'                  => $dryRun,
            'contacts_anonymized'      => $contactsAnonymized,
            'search_histories_deleted' => $searchHistoriesDeleted,
            'view_histories_deleted'   => $viewHistoriesDeleted,
        ]);

        return self::SUCCESS;
    }

    /**
     * Supprime les coordonnées GPS des contacts créés il y a plus de 90 jours.
     * Traitement par chunks pour éviter les locks de table prolongés.
     */
    private function anonymizeContactGps(\DateTimeInterface $cutoff, bool $dryRun): int
    {
        $count = DB::table('contacts')
            ->whereNotNull('user_latitude')
            ->where('created_at', '<', $cutoff)
            ->count();

        $this->info("Contacts avec coords GPS > 90 j : {$count}");

        if (! $dryRun && $count > 0) {
            $processed = 0;

            DB::table('contacts')
                ->whereNotNull('user_latitude')
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use (&$processed) {
                    $ids = $rows->pluck('id')->all();
                    DB::table('contacts')
                        ->whereIn('id', $ids)
                        ->update([
                            'user_latitude'  => null,
                            'user_longitude' => null,
                        ]);
                    $processed += count($ids);
                });

            $this->info("  => {$processed} contacts anonymisés (GPS supprimé).");
        }

        return $count;
    }

    /**
     * Supprime les search_histories créés il y a plus de 365 jours.
     * Traitement par chunks pour éviter les locks de table prolongés.
     */
    private function purgeSearchHistories(\DateTimeInterface $cutoff, bool $dryRun): int
    {
        $count = DB::table('search_histories')
            ->where('created_at', '<', $cutoff)
            ->count();

        $this->info("Search histories > 365 j : {$count}");

        if (! $dryRun && $count > 0) {
            $processed = 0;

            DB::table('search_histories')
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use (&$processed) {
                    $ids = $rows->pluck('id')->all();
                    DB::table('search_histories')
                        ->whereIn('id', $ids)
                        ->delete();
                    $processed += count($ids);
                });

            $this->info("  => {$processed} search histories supprimées.");
        }

        return $count;
    }

    /**
     * Purge les enregistrements view_history dont last_viewed_at dépasse 365 jours (RGPD).
     * Traitement par chunks pour éviter les locks de table prolongés.
     */
    private function purgeViewHistory(\DateTimeInterface $cutoff, bool $dryRun): int
    {
        $count = DB::table('view_history')
            ->where('last_viewed_at', '<', $cutoff)
            ->count();

        $this->info("View history > 365 j : {$count}");

        if (! $dryRun && $count > 0) {
            $processed = 0;

            DB::table('view_history')
                ->where('last_viewed_at', '<', $cutoff)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use (&$processed) {
                    $ids = $rows->pluck('id')->all();
                    DB::table('view_history')
                        ->whereIn('id', $ids)
                        ->delete();
                    $processed += count($ids);
                });

            $this->info("  => {$processed} view history supprimées.");
        }

        return $count;
    }
}
