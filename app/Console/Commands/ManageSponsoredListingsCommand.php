<?php

namespace App\Console\Commands;

use App\Models\SponsoredListing;
use App\Notifications\SponsoredListingCompleted;
use App\Services\SponsoredListingService;
use Illuminate\Console\Command;

class ManageSponsoredListingsCommand extends Command
{
    protected $signature = 'rezi:manage-sponsored-listings';

    protected $description = 'Auto-complete expired sponsored listings and pause budget-exhausted ones';

    public function handle(SponsoredListingService $sponsoredListingService): int
    {
        $this->info('🔄 Gestion des mises en avant...');

        // 1. Auto-complete expired active campaigns
        $expired = $sponsoredListingService->completeExpiredActiveListings();

        $expiredCount = $expired->count();

        foreach ($expired as $sponsored) {
            $sponsored->complete();
            $this->sendCompletionReport($sponsored);
            $residenceName = $sponsored->residence->name ?? 'N/A';
            $this->line("  ✅ Complétée: #{$sponsored->id} - {$residenceName}");
        }

        // 2. Pause campaigns that exhausted their budget
        $budgetExhausted = $sponsoredListingService->pauseBudgetExhaustedListings();

        $budgetCount = $budgetExhausted->count();

        foreach ($budgetExhausted as $sponsored) {
            $sponsored->pause();
            $residenceName = $sponsored->residence->name ?? 'N/A';
            $this->line("  ⏸️  Budget épuisé: #{$sponsored->id} - {$residenceName}");
        }

        // 3. Auto-complete paused expired campaigns (ended while paused)
        $pausedExpired = $sponsoredListingService->completeExpiredPausedListings();

        $pausedExpiredCount = $pausedExpired->count();

        foreach ($pausedExpired as $sponsored) {
            $sponsored->complete();
            $this->sendCompletionReport($sponsored);
            $this->line("  ✅ En pause → Complétée: #{$sponsored->id}");
        }

        $this->info('📊 Résumé:');
        $this->info("   - Expirées complétées: {$expiredCount}");
        $this->info("   - Budget épuisé (pause): {$budgetCount}");
        $this->info("   - En pause expirées: {$pausedExpiredCount}");

        return self::SUCCESS;
    }

    /**
     * Envoyer le rapport de fin de campagne au propriétaire
     */
    private function sendCompletionReport(SponsoredListing $sponsored): void
    {
        $sponsored->load(['residence', 'user']);

        if ($sponsored->user) {
            $sponsored->user->notify(new SponsoredListingCompleted($sponsored));
        }
    }
}
