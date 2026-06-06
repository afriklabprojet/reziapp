<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Promotion;
use App\Notifications\PromotionExpiring;
use App\Notifications\SponsoredListingExpiring;
use App\Services\SponsoredListingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringPromotionsCommand extends Command
{
    protected $signature = 'rezi:check-expiring-promotions
                            {--days=2 : Nombre de jours avant expiration pour notifier}';

    protected $description = 'Notifier les propriétaires des promotions et mises en avant expirant bientôt';

    public function __construct(private readonly SponsoredListingService $sponsoredListingService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->addDays($days);

        $this->info("Vérification des promotions expirant avant {$threshold->format('d/m/Y H:i')}...");

        // Promotions expirant bientôt (entre maintenant et dans N jours)
        $promotions = Promotion::where('status', 'active')
            ->where('ends_at', '<=', $threshold)
            ->where('ends_at', '>', now())
            ->with(['residence.owner', 'user'])
            ->get();

        $promoCount = 0;
        foreach ($promotions as $promotion) {
            $owner = $promotion->residence?->owner ?? $promotion->user;
            if ($owner) {
                $owner->notify(new PromotionExpiring($promotion));

                // Notification in-app
                \App\Models\Notification::send(
                    $owner,
                    'system',
                    'Promotion bientôt expirée',
                    'Votre promotion "'.$promotion->title.'" expire bientôt.',
                    route('owner.marketing.promotions.index'),
                    ['promotion_id' => $promotion->id],
                );

                $promoCount++;
            }
        }

        // Mises en avant (sponsored listings) expirant bientôt
    $sponsoredListings = $this->sponsoredListingService->getExpiringActiveListings($threshold);

        $sponsoredCount = 0;
        foreach ($sponsoredListings as $listing) {
            $owner = $listing->user ?? $listing->residence?->owner;
            if ($owner) {
                $owner->notify(new SponsoredListingExpiring($listing));

                // Notification in-app
                \App\Models\Notification::send(
                    $owner,
                    'system',
                    'Mise en avant bientôt terminée',
                    'La mise en avant de '.($listing->residence?->name ?? 'votre résidence').' expire bientôt.',
                    route('owner.marketing.sponsored.index'),
                    ['sponsored_listing_id' => $listing->id],
                );

                $sponsoredCount++;
            }
        }

        $this->info("✅ {$promoCount} notification(s) de promotion envoyée(s)");
        $this->info("✅ {$sponsoredCount} notification(s) de mise en avant envoyée(s)");

        Log::info('Check expiring promotions', [
            'promotions_notified' => $promoCount,
            'sponsored_notified' => $sponsoredCount,
        ]);

        return self::SUCCESS;
    }
}
