<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\ViewedResidence;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Met à jour les compteurs de rareté (scarcity) sur chaque résidence :
 *  - active_viewers_24h  : visiteurs uniques dans les dernières 24h
 *  - bookings_this_month : réservations confirmées sur le mois courant
 *
 * À planifier toutes les heures dans le scheduler.
 */
class UpdateResidenceScarcityStats extends Command
{
    protected $signature = 'rezi:update-scarcity-stats';

    protected $description = 'Met à jour active_viewers_24h et bookings_this_month pour toutes les résidences';

    public function handle(): int
    {
        $this->info('Calcul des statistiques de rareté...');

        // --- Viewers actifs (24h) ---
        $viewerCounts = ViewedResidence::select('residence_id', DB::raw('COUNT(DISTINCT user_id) as cnt'))
            ->where('last_viewed', '>=', Carbon::now()->subDay())
            ->groupBy('residence_id')
            ->pluck('cnt', 'residence_id');

        // --- Réservations du mois ---
        $bookingCounts = Booking::select('residence_id', DB::raw('COUNT(*) as cnt'))
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->groupBy('residence_id')
            ->pluck('cnt', 'residence_id');

        // Batch update — évite N requêtes individuelles
        $residenceIds = $viewerCounts->keys()->merge($bookingCounts->keys())->unique();

        $updated = 0;
        foreach ($residenceIds->chunk(200) as $chunk) {
            foreach ($chunk as $id) {
                DB::table('residences')->where('id', $id)->update([
                    'active_viewers_24h'  => $viewerCounts->get($id, 0),
                    'bookings_this_month' => $bookingCounts->get($id, 0),
                    'updated_at'          => now(),
                ]);
                $updated++;
            }
        }

        // Remettre à 0 les résidences sans activité récente
        DB::table('residences')
            ->whereNotIn('id', $residenceIds->isEmpty() ? [0] : $residenceIds->all())
            ->where(function ($q) {
                $q->where('active_viewers_24h', '>', 0)
                  ->orWhere('bookings_this_month', '>', 0);
            })
            ->update(['active_viewers_24h' => 0, 'bookings_this_month' => 0]);

        $this->info("✓ {$updated} résidences mises à jour.");

        return self::SUCCESS;
    }
}
