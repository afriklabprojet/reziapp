<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\UnpaidRentAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande planifiée : vérifie journellement les réservations
 * confirmées non payées et notifie les propriétaires.
 *
 * Usage : php artisan rezi:check-unpaid-bookings
 * Scheduling : quotidien à 08h00
 */
class CheckUnpaidBookings extends Command
{
    protected $signature = 'rezi:check-unpaid-bookings
                            {--dry-run : Afficher sans envoyer les notifications}
                            {--days=3 : Nombre minimum de jours de retard avant alerte}';

    protected $description = 'Détecte les réservations confirmées sans paiement et alerte les propriétaires';

    public function handle(): int
    {
        $isDryRun  = (bool) $this->option('dry-run');
        $minDays   = (int) $this->option('days');
        $threshold = now()->subDays($minDays)->endOfDay();

        $this->info("🔍 Recherche des réservations impayées (retard ≥ {$minDays} jours)...");

        /**
         * Critères :
         * - Statut = 'confirmed' (réservation acceptée)
         * - payment_status ≠ 'paid'
         * - check_in passé depuis au moins $minDays jours
         * - Non annulée
         */
        $unpaidBookings = Booking::with(['user', 'residence.owner'])
            ->where('status', 'confirmed')
            ->where('payment_status', '!=', 'paid')
            ->where('payment_status', '!=', 'refunded')
            ->where('check_in', '<=', $threshold)
            ->whereNotNull('residence_id')
            ->get();

        if ($unpaidBookings->isEmpty()) {
            $this->info('✅ Aucune réservation impayée trouvée.');

            return self::SUCCESS;
        }

        $this->info("⚠️ {$unpaidBookings->count()} réservation(s) impayée(s) détectée(s).");

        $notified = 0;
        $errors   = 0;

        foreach ($unpaidBookings as $booking) {
            $daysOverdue = (int) $booking->check_in->diffInDays(now());
            $owner       = $booking->residence?->owner;

            if (! $owner) {
                $this->warn("  Réservation #{$booking->id} : propriétaire introuvable, ignorée.");
                continue;
            }

            if ($isDryRun) {
                $this->line("  [DRY RUN] → Propriétaire {$owner->email} | Réservation #{$booking->id} | {$daysOverdue}j de retard");
                $notified++;
                continue;
            }

            try {
                $owner->notify(new UnpaidRentAlertNotification($booking, $daysOverdue));
                $notified++;

                Log::info('Unpaid booking alert sent', [
                    'booking_id'   => $booking->id,
                    'owner_id'     => $owner->id,
                    'tenant_id'    => $booking->user_id,
                    'days_overdue' => $daysOverdue,
                ]);

                $this->line("  ✉️ Alerte envoyée : {$owner->email} | Réservation #{$booking->id} | {$daysOverdue}j");
            } catch (\Throwable $e) {
                $errors++;
                Log::error('Failed to send unpaid booking alert', [
                    'booking_id' => $booking->id,
                    'error'      => $e->getMessage(),
                ]);
                $this->error("  Erreur pour réservation #{$booking->id} : {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '📊 Résultat : %d notification(s) envoyée(s), %d erreur(s).',
            $notified,
            $errors,
        ));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
