<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Residence;
use App\Services\ListingScoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job pour recalculer le score qualité d'une annonce en arrière-plan.
 * Dispatché après chaque modification significative d'une résidence.
 */
class ComputeListingScore implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Nombre de tentatives en cas d'échec */
    public int $tries = 3;

    /** Délai avant réessai (secondes) */
    public int $backoff = 60;

    public function __construct(
        public readonly Residence $residence,
    ) {}

    public function handle(ListingScoreService $scoreService): void
    {
        // S'assurer que la résidence existe toujours
        if (! $this->residence->exists) {
            return;
        }

        $scoreService->compute($this->residence);
    }

    public function tags(): array
    {
        return ['listing-score', "residence:{$this->residence->id}"];
    }
}
