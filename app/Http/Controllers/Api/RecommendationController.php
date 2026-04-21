<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Residence;
use App\Services\ResidenceMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RecommendationController extends Controller
{
    public function __construct(
        private readonly ResidenceMatchingService $matching,
    ) {
    }

    /**
     * GET /api/v1/recommendations
     * Recommandations personnalisées pour l'utilisateur authentifié.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $limit = min((int) $request->query('limit', 12), 24);
        $fresh = (bool) $request->query('fresh', false);

        $recommendations = $this->matching->recommend($user, $limit, $fresh);

        return response()->json([
            'data'    => $recommendations->map(fn (Residence $r) => $this->formatResidence($r)),
            'profile' => $fresh ? $this->matching->getUserProfile($user) : null,
            'meta'    => [
                'total'          => $recommendations->count(),
                'has_signal'     => $this->matching->getUserProfile($user)['has_signal'],
                'generated_at'   => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/v1/recommendations/invalidate
     * Invalider le cache de recommandations (après favori/réservation).
     */
    public function invalidate(Request $request): JsonResponse
    {
        $this->matching->invalidate($request->user());

        return response()->json(['message' => 'Cache invalidé']);
    }

    /**
     * GET /api/v1/recommendations/profile
     * Profil inféré de l'utilisateur (debug / UI).
     */
    public function profile(Request $request): JsonResponse
    {
        $profile = $this->matching->getUserProfile($request->user());

        return response()->json(['data' => $profile]);
    }

    /**
     * GET /api/v1/residences/{residence}/similar
     * Résidences similaires (sans auth, basé sur les caractéristiques).
     */
    public function similar(Residence $residence, Request $request): JsonResponse
    {
        $limit    = min((int) $request->query('limit', 6), 12);
        $cacheKey = "similar_residences_{$residence->id}_{$limit}";

        $similar = Cache::remember($cacheKey, 1800, function () use ($residence, $limit) {
            return Residence::query()
                ->where('status', 'active')
                ->where('is_available', true)
                ->where('id', '!=', $residence->id)
                ->where(function ($q) use ($residence) {
                    $q->where('commune', $residence->commune)
                      ->orWhere('type', $residence->type);
                })
                ->whereBetween('price_per_day', [
                    ($residence->price_per_day ?? 0) * 0.6,
                    ($residence->price_per_day ?? 99999) * 1.6,
                ])
                ->where('bedrooms', '>=', max(1, ($residence->bedrooms ?? 1) - 1))
                ->where('bedrooms', '<=', ($residence->bedrooms ?? 2) + 1)
                ->with(['photos'])
                ->orderByDesc('average_rating')
                ->take($limit)
                ->get();
        });

        return response()->json([
            'data' => $similar->map(fn (Residence $r) => $this->formatResidence($r)),
        ]);
    }

    // ─── Formatage ─────────────────────────────────────────────────────────

    private function formatResidence(Residence $residence): array
    {
        $photo = $residence->photos->first();

        return [
            'id'             => $residence->id,
            'title'          => $residence->title,
            'commune'        => $residence->commune,
            'price_per_day'  => $residence->price_per_day,
            'price_per_month' => $residence->price_per_month,
            'bedrooms'       => $residence->bedrooms,
            'bathrooms'      => $residence->bathrooms,
            'type'           => $residence->type,
            'average_rating' => $residence->average_rating,
            'reviews_count'  => $residence->reviews_count,
            'is_verified'    => (bool) $residence->is_verified,
            'is_top'         => (bool) $residence->is_top_residence,
            'photo'          => $photo ? [
                'url'       => $photo->url ?? asset('images/placeholder.jpg'),
                'thumbnail' => $photo->thumbnail_url ?? $photo->url ?? asset('images/placeholder.jpg'),
            ] : null,
            'url'            => route('residences.show', $residence->id),
            'match_score'    => $residence->match_score ?? null,
            'match_reasons'  => $residence->match_reasons ?? [],
        ];
    }
}
