<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SponsoredListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Enregistre les impressions des annonces sponsorisées de façon asynchrone.
 *
 * Résout le N+1 dans HomeController : au lieu d'appeler recordImpression()
 * sur chaque SponsoredListing synchronement (3–5 requêtes par annonce),
 * on délègue l'opération à la queue pour ne pas bloquer la page d'accueil.
 */
class RecordSponsoredImpressions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @param array<int> $residenceIds IDs des résidences dont les impressions sont à enregistrer */
    public function __construct(
        private readonly array $residenceIds,
        private readonly ?string $ip,
        private readonly ?int $userId,
    ) {
    }

    public function handle(): void
    {
        if (empty($this->residenceIds)) {
            return;
        }

        SponsoredListing::featuredHome()
            ->whereIn('residence_id', $this->residenceIds)
            ->each(function (SponsoredListing $sl): void {
                $sl->recordImpression($this->ip, $this->userId);
            });
    }
}
