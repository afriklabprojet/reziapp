<?php

namespace App\Services;

use App\Models\Residence;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ResidenceCacheService
{
    /**
     * Cache TTL in seconds
     */
    protected int $ttl;

    /**
     * Cache prefix
     */
    protected string $prefix = 'rezi:residences:';

    public function __construct()
    {
        $this->ttl = config('rezi.cache.residence_ttl', 3600); // 1 hour default
    }

    /**
     * Get residence by ID with caching
     */
    public function getById(int $id): ?Residence
    {
        return Cache::remember(
            $this->prefix."single:{$id}",
            $this->ttl,
            fn () => Residence::with(['owner', 'photos', 'amenities'])
                ->listable()
                ->find($id),
        );
    }

    /**
     * Get featured residences (top-rated + available)
     */
    public function getFeatured(int $limit = 8): Collection
    {
        return Cache::remember(
            $this->prefix."featured:{$limit}",
            $this->ttl,
            fn () => Residence::with(['photos'])
                ->listable()
                ->orderByDesc('average_rating')
                ->limit($limit)
                ->get(),
        );
    }

    /**
     * Get residences by commune (text match)
     */
    public function getByCommune(string $commune, int $page = 1, int $perPage = 12): array
    {
        $cacheKey = $this->prefix.'commune:'.md5($commune).":page:{$page}:per:{$perPage}";

        return Cache::remember($cacheKey, $this->ttl, function () use ($commune, $perPage) {
            $paginator = Residence::with(['photos'])
                ->listable()
                ->where('commune', $commune)
                ->paginate($perPage);

            return [
                'data' => $paginator->items(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ];
        });
    }

    /**
     * Get popular residences (by view count)
     */
    public function getPopular(int $limit = 8): Collection
    {
        return Cache::remember(
            $this->prefix."popular:{$limit}",
            $this->ttl,
            fn () => Residence::with(['photos'])
                ->listable()
                ->orderByDesc('views_count')
                ->limit($limit)
                ->get(),
        );
    }

    /**
     * Get latest residences
     */
    public function getLatest(int $limit = 8): Collection
    {
        return Cache::remember(
            $this->prefix."latest:{$limit}",
            $this->ttl / 2, // Shorter TTL for latest
            fn () => Residence::with(['photos'])
                ->listable()
                ->latest()
                ->limit($limit)
                ->get(),
        );
    }

    /**
     * Get residences near a location
     */
    public function getNearby(float $lat, float $lng, float $radiusKm = 5, int $limit = 10): Collection
    {
        // Round coordinates to cache effectively
        $roundedLat = round($lat, 3);
        $roundedLng = round($lng, 3);
        $cacheKey = $this->prefix."nearby:{$roundedLat}:{$roundedLng}:{$radiusKm}:{$limit}";

        return Cache::remember($cacheKey, $this->ttl, function () use ($lat, $lng, $radiusKm, $limit) {
            return Residence::with(['photos'])
                ->listable()
                ->selectRaw(
                    '*, 
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                    [$lat, $lng, $lat],
                )
                ->having('distance', '<', $radiusKm)
                ->orderBy('distance')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get search results with caching (short TTL)
     */
    public function searchWithCache(array $filters, int $page = 1): array
    {
        $filterHash = md5(json_encode($filters)."page:{$page}");
        $cacheKey = $this->prefix."search:{$filterHash}";

        return Cache::remember($cacheKey, 300, function () use ($filters) {
            $query = Residence::with(['photos'])->listable();

            // Apply filters
            if (!empty($filters['commune'])) {
                $query->where('commune', $filters['commune']);
            }
            if (!empty($filters['min_price'])) {
                $query->where('price_per_month', '>=', $filters['min_price']);
            }
            if (!empty($filters['max_price'])) {
                $query->where('price_per_month', '<=', $filters['max_price']);
            }
            if (!empty($filters['bedrooms'])) {
                $query->where('bedrooms', '>=', $filters['bedrooms']);
            }
            if (!empty($filters['guests'])) {
                $query->where('max_guests', '>=', $filters['guests']);
            }
            if (!empty($filters['amenities'])) {
                $query->whereHas('amenities', function ($q) use ($filters) {
                    $q->whereIn('amenities.id', $filters['amenities']);
                }, '>=', count($filters['amenities']));
            }
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            // Sorting
            $sort = $filters['sort'] ?? 'newest';
            switch ($sort) {
                case 'price_asc':
                    $query->orderBy('price_per_month', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price_per_month', 'desc');
                    break;
                case 'rating':
                    $query->orderByDesc('average_rating');
                    break;
                default:
                    $query->latest();
            }

            $paginator = $query->paginate($filters['per_page'] ?? 12);

            return [
                'data' => $paginator->items(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'current_page' => $paginator->currentPage(),
            ];
        });
    }

    /**
     * Invalidate cache for a specific residence
     */
    public function invalidateResidence(int $id): void
    {
        Cache::forget($this->prefix."single:{$id}");

        // Also invalidate list caches
        $this->invalidateListCaches();
    }

    /**
     * Invalidate all list caches
     */
    public function invalidateListCaches(): void
    {
        // Clear pattern-matched keys (requires Redis)
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($this->prefix.'*');
            foreach ($keys as $key) {
                // Remove the prefix that Redis adds
                $cacheKey = str_replace(config('cache.prefix'), '', $key);
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Warm up cache with popular content
     */
    public function warmUpCache(): void
    {
        // Pre-cache common queries
        $this->getFeatured(8);
        $this->getPopular(8);
        $this->getLatest(8);

        // Pre-cache commune pages (using distinct commune names)
        $communes = Residence::listable()->distinct()->pluck('commune');
        foreach ($communes as $commune) {
            $this->getByCommune($commune, 1, 12);
        }
    }
}
