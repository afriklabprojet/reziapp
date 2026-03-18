<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Residence;
use App\Services\ListingScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller pour les scores qualité des annonces propriétaires.
 */
class ListingScoreController extends Controller
{
    public function __construct(
        private readonly ListingScoreService $scoreService,
    ) {}

    /**
     * Recalculer manuellement le score d'une résidence (via AJAX ou POST).
     */
    public function compute(Request $request, Residence $residence): JsonResponse
    {
        abort_unless($residence->owner_id === $request->user()->id, 403);

        $result = $this->scoreService->compute($residence);

        return response()->json([
            'success' => true,
            'score'   => $result['score'],
            'label'   => $result['label'],
            'color'   => $result['color'],
            'breakdown' => $result['breakdown'],
            'tips'    => $result['tips'],
        ]);
    }

    /**
     * Vue de détail du score (modale ou page dédiée).
     */
    public function show(Residence $residence): View
    {
        abort_unless($residence->owner_id === auth()->id(), 403);

        $residence->load(['photos', 'amenities', 'reviews']);

        // Si le score n'est pas encore calculé ou trop vieux (> 24h), recalculer
        if (
            $residence->listing_score === null
            || $residence->listing_score_computed_at?->lt(now()->subDay())
        ) {
            $scoreData = $this->scoreService->compute($residence);
        } else {
            $scoreData = $residence->listing_score_breakdown ?? [];
            $scoreData['score'] = $residence->listing_score;
        }

        return view('owner.residences.listing-score', compact('residence', 'scoreData'));
    }
}
