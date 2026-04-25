<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Residence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * Sprint 2 — Search-as-I-move : recherche dans la bbox visible de la carte.
 *
 * GET /api/v1/maps/search-bounds
 *   sw_lat, sw_lng, ne_lat, ne_lng (requis)
 *   + filtres standards (min_price, max_price, bedrooms, type, amenities[])
 *
 * Réponse : JSON minimal pour markers + cards (id, title, lat, lng, price, thumbnail).
 * Cap dur : 200 résultats.
 */
class MapBoundsSearchController extends Controller
{
    private const MAX_RESULTS = 200;
    private const CACHE_TTL = 60; // 1 min — la carte bouge, on rafraîchit vite

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sw_lat' => ['required', 'numeric', 'between:-90,90'],
            'sw_lng' => ['required', 'numeric', 'between:-180,180'],
            'ne_lat' => ['required', 'numeric', 'between:-90,90'],
            'ne_lng' => ['required', 'numeric', 'between:-180,180'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:10'],
            'type' => ['nullable', 'string', 'max:50'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['integer'],
            'instant_book' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Sanity: bbox cohérente
        if ($data['sw_lat'] >= $data['ne_lat'] || $data['sw_lng'] >= $data['ne_lng']) {
            return response()->json([
                'success' => false,
                'message' => 'Bounding box invalide.',
            ], 422);
        }

        // Cap surface : refuser bbox > 10°x10° (anti-DoS)
        if (($data['ne_lat'] - $data['sw_lat']) > 10 || ($data['ne_lng'] - $data['sw_lng']) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'Zone de recherche trop large. Zoomez davantage.',
            ], 422);
        }

        $cacheKey = 'map-bounds:'.md5(json_encode($data));

        $payload = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($data) {
            $query = Residence::query()
                ->approved()
                ->available()
                ->whereBetween('latitude', [$data['sw_lat'], $data['ne_lat']])
                ->whereBetween('longitude', [$data['sw_lng'], $data['ne_lng']])
                ->with(['photos:id,residence_id,path,is_primary']);

            if (!empty($data['min_price'])) {
                $query->where('price_per_day', '>=', $data['min_price']);
            }
            if (!empty($data['max_price'])) {
                $query->where('price_per_day', '<=', $data['max_price']);
            }
            if (!empty($data['bedrooms'])) {
                $query->where('bedrooms', '>=', $data['bedrooms']);
            }
            if (!empty($data['type'])) {
                $query->where('type', $data['type']);
            }
            if (!empty($data['instant_book'])) {
                $query->where('instant_book', true);
            }
            if (!empty($data['amenities'])) {
                foreach ($data['amenities'] as $amenityId) {
                    $query->whereHas('amenities', fn ($q) => $q->where('amenities.id', $amenityId));
                }
            }

            $total = (clone $query)->count();

            $residences = $query
                ->select(['id', 'name', 'latitude', 'longitude', 'price_per_day', 'bedrooms', 'commune', 'quartier', 'type', 'instant_book', 'average_rating', 'reviews_count'])
                ->limit(self::MAX_RESULTS)
                ->get()
                ->map(function (Residence $r) {
                    $primary = $r->photos->firstWhere('is_primary', true) ?? $r->photos->first();
                    return [
                        'id' => $r->id,
                        'title' => $r->name,
                        'url' => route('residences.show', $r->id),
                        'latitude' => (float) $r->latitude,
                        'longitude' => (float) $r->longitude,
                        'price' => (int) $r->price_per_day,
                        'price_label' => '/jour',
                        'bedrooms' => (int) $r->bedrooms,
                        'commune' => $r->commune,
                        'quartier' => $r->quartier,
                        'thumbnail' => $primary ? asset('storage/'.$primary->path) : null,
                        'instant_book' => (bool) $r->instant_book,
                        'rating_avg' => $r->average_rating ? (float) $r->average_rating : null,
                        'rating_count' => (int) ($r->reviews_count ?? 0),
                        'is_available' => true,
                        'location' => [
                            'latitude' => (float) $r->latitude,
                            'longitude' => (float) $r->longitude,
                            'commune' => $r->commune,
                            'quartier' => $r->quartier,
                        ],
                    ];
                });

            return [
                'total' => $total,
                'returned' => $residences->count(),
                'capped' => $total > self::MAX_RESULTS,
                'residences' => $residences,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }
}
