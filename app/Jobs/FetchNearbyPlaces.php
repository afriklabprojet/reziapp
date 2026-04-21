<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Residence;
use App\Services\NearbyPlacesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Récupère automatiquement les points d'intérêt autour d'une résidence
 * via Google Places Nearby Search API.
 *
 * Dispatché après création/approbation d'une résidence.
 */
class FetchNearbyPlaces implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;

    public function __construct(
        protected Residence $residence,
        protected bool $force = false,
    ) {
    }

    public function handle(NearbyPlacesService $service): void
    {
        $service->fetchAndSave($this->residence, $this->force);
    }
}
