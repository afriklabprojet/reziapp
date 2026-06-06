<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Residence;
use App\Services\ResidenceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class ResidenceEngagementController extends Controller
{
    private const VIEW_DEDUPLICATION_TTL_MINUTES = 10;

    public function __construct(private readonly ResidenceService $residenceService) {}

    public function recordView(\Illuminate\Http\Request $request, Residence $residence): JsonResponse
    {
        $user = $request->user();

        if ($residence->status !== 'active' && $user?->id !== $residence->owner_id) {
            abort(404);
        }

        $deduplicationKey = sprintf(
            'api:residence:view:%d:user:%d',
            $residence->id,
            $user?->id ?? 0,
        );

        if (! Cache::add($deduplicationKey, true, now()->addMinutes(self::VIEW_DEDUPLICATION_TTL_MINUTES))) {
            return response()->json([
                'message' => 'Vue déjà enregistrée récemment.',
            ]);
        }

        $this->residenceService->recordView($residence);

        return response()->json([
            'message' => 'Vue enregistrée.',
        ]);
    }
}
