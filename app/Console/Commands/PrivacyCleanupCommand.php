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
 *  - coordonnées GPS dans `contacts`         : 90 jours
 *  - search_histories                        : 365 jours
 *  - view_histories anonymes (user_id null)  : 365 jours
 *  - view_histories authentifiés (user_id)   : 365 jours
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

        $contactsAnonymized              = $this->anonymizeContactGps($cutoff90, $dryRun);
        $searchHistoriesDeleted          = $this->purgeSearchHistories($cutoff365, $dryRun);
        $anonymousViewHistoriesDeleted   = $this->purgeAnonymousViewHistories($cutoff365, $dryRun);
        $authenticatedViewHistoriesDeleted = $this->purgeAuthenticatedViewHistories($cutoff365, $dryRun);

        if ($dryRun) {
            $this->warn('Mode dry-run : aucune modification appliquée.');
        }

        Log::info('privacy:cleanup exécuté', [
            'dry_run'                  => $dryRun,
            'contacts_anonymized'      => $contactsAnonymized,
            'search_histories_deleted' => $searchHistoriesDeleted,
            'view_histories_deleted'   => $anonymousViewHistoriesDeleted + $authenticatedViewHistoriesDeleted,
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
     * Supprime les view_histories anonymes créés il y a plus de 365 jours.
     * Traitement par chunks pour éviter les locks de table prolongés.
     */
    private function purgeAnonymousViewHistories(\DateTimeInterface $cutoff, bool $dryRun): int
    {
        $count = DB::table('view_histories')
            ->whereNull('user_id')
            ->where('created_at', '<', $cutoff)
            ->count();

        $this->info("View histories anonymes > 365 j : {$count}");

        if (! $dryRun && $count > 0) {
            $processed = 0;

            DB::table('view_histories')
                ->whereNull('user_id')
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use (&$processed) {
                    $ids = $rows->pluck('id')->all();
                    DB::table('view_histories')
                        ->whereIn('id', $ids)
                        ->delete();
                    $processed += count($ids);
                });

            $this->info("  => {$processed} view histories anonymes supprimées.");
        }

        return $count;
    }

    /**
     * Supprime les view_histories d'utilisateurs authentifiés créés il y a plus de 365 jours.
     * Requis par le RGPD : les données des utilisateurs identifiés doivent également être purgées.
     * Traitement par chunks pour éviter les locks de table prolongés.
     */
    private function purgeAuthenticatedViewHistories(\DateTimeInterface $cutoff, bool $dryRun): int
    {
        $count = DB::table('view_histories')
            ->whereNotNull('user_id')
            ->where('created_at', '<', $cutoff)
            ->count();

        $this->info("View histories authentifiés > 365 j : {$count}");

        if (! $dryRun && $count > 0) {
            $processed = 0;

            DB::table('view_histories')
                ->whereNotNull('user_id')
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->chunkById(self::CHUNK_SIZE, function ($rows) use (&$processed) {
                    $ids = $rows->pluck('id')->all();
                    DB::table('view_histories')
                        ->whereIn('id', $ids)
                        ->delete();
                    $processed += count($ids);
                });

            $this->info("  => {$processed} view histories authentifiés supprimées.");
        }

        return $count;
    }
}
