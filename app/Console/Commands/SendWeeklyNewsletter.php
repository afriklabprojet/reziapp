<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendNewsletterCampaign;
use App\Models\Residence;
use Illuminate\Console\Command;

/**
 * Option C — Newsletter hebdomadaire automatique.
 *
 * Sélectionne les meilleures résidences approuvées de la semaine (max 6)
 * par score qualité, puis dispatche SendNewsletterCampaign sur la queue.
 *
 * Planification : tous les lundis à 09:00 (déclaré dans bootstrap/app.php)
 *
 * Usage manuel :
 *   php artisan newsletter:weekly
 *   php artisan newsletter:weekly --dry-run
 */
class SendWeeklyNewsletter extends Command
{
    protected $signature = 'newsletter:weekly
        {--dry-run : Affiche les résidences sélectionnées sans envoyer}
        {--days=7  : Fenêtre de sélection en jours (résidences approuvées depuis N jours)}
        {--limit=6 : Nombre maximum de résidences dans l\'email}';

    protected $description = 'Envoie la newsletter hebdomadaire avec les meilleures résidences récentes';

    public function handle(): int
    {
        $days  = (int) $this->option('days');
        $limit = (int) $this->option('limit');

        // 1. Résidences approuvées durant la période
        $residences = Residence::where('status', 'approved')
            ->whereNotNull('approved_at')
            ->where('approved_at', '>=', now()->subDays($days))
            ->with('primaryPhoto')
            ->orderByDesc('listing_quality_score')
            ->limit($limit)
            ->get();

        // 2. Fallback : si aucune résidence récente, prendre les meilleures globales
        if ($residences->isEmpty()) {
            $this->warn("Aucune résidence approuvée dans les {$days} derniers jours. Fallback sur les meilleures globales.");

            $residences = Residence::where('status', 'approved')
                ->with('primaryPhoto')
                ->orderByDesc('listing_quality_score')
                ->limit($limit)
                ->get();
        }

        if ($residences->isEmpty()) {
            $this->error('Aucune résidence disponible pour la newsletter.');

            return self::FAILURE;
        }

        $this->info("✅ {$residences->count()} résidence(s) sélectionnée(s) :");
        foreach ($residences as $r) {
            $this->line("   • [{$r->id}] {$r->title} — {$r->commune}");
        }

        if ($this->option('dry-run')) {
            $this->warn('Mode dry-run : aucun email envoyé.');

            return self::SUCCESS;
        }

        SendNewsletterCampaign::dispatch($residences->pluck('id')->toArray(), 'weekly')
            ->onQueue('default');

        $this->info('📨 Campagne newsletter hebdomadaire dispatchée sur la queue.');

        return self::SUCCESS;
    }
}
