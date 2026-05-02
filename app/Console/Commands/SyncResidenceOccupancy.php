<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Residence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncResidenceOccupancy extends Command
{
    protected $signature = 'rezi:sync-residence-occupancy';

    protected $description = 'Marque automatiquement les résidences comme occupées/disponibles selon les réservations confirmées du jour';

    public function handle(): int
    {
        $today = now()->toDateString();

        // ── 1. Résidences qui commencent leur occupation aujourd'hui ou sont en cours ──
        // Confirmed booking : check_in <= today < check_out
        $occupiedResidenceIds = Booking::query()
            ->where('status', 'confirmed')
            ->where('check_in', '<=', $today)
            ->where('check_out', '>', $today)
            ->pluck('residence_id')
            ->unique()
            ->values();

        $markedOccupied = 0;
        if ($occupiedResidenceIds->isNotEmpty()) {
            $markedOccupied = Residence::whereIn('id', $occupiedResidenceIds)
                ->where('is_available', true)
                ->update(['is_available' => false]);
        }

        // ── 2. Résidences dont le dernier booking vient de se terminer ──
        // Résidences actuellement indisponibles dont AUCUNE réservation confirmée ne couvre aujourd'hui
        // → remettre is_available = true (sauf si le propriétaire a explicitement mis offline)
        $stillOccupiedIds = $occupiedResidenceIds->all();

        // Résidences marquées unavailable par une réservation précédente (elles ont au moins
        // un booking confirmé historique) qui n'ont PLUS de booking actif aujourd'hui
        $toRestoreIds = Residence::query()
            ->where('is_available', false)
            ->whereIn('status', ['approved', 'active'])
            ->whereNotIn('id', $stillOccupiedIds)
            // A eu au moins une réservation confirmée (logique : c'est nous qui avons mis false)
            ->whereHas('bookings', fn ($q) => $q->where('status', 'confirmed'))
            // Mais aucune ne couvre aujourd'hui
            ->whereDoesntHave('bookings', fn ($q) => $q
                ->where('status', 'confirmed')
                ->where('check_in', '<=', $today)
                ->where('check_out', '>', $today)
            )
            // Ne pas toucher aux résidences dont le propriétaire a un mode vacances actif
            ->whereNotIn('owner_id', function ($q) {
                $q->select('owner_id')
                    ->from('vacation_modes')
                    ->where('is_active', true);
            })
            ->pluck('id');

        $markedAvailable = 0;
        if ($toRestoreIds->isNotEmpty()) {
            $markedAvailable = Residence::whereIn('id', $toRestoreIds)
                ->update(['is_available' => true]);
        }

        $this->info("Résidences marquées occupées : {$markedOccupied}");
        $this->info("Résidences remises disponibles : {$markedAvailable}");

        Log::info('rezi:sync-residence-occupancy', [
            'date'              => $today,
            'marked_occupied'   => $markedOccupied,
            'marked_available'  => $markedAvailable,
        ]);

        return self::SUCCESS;
    }
}
