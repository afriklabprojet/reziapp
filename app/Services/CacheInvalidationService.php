<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Centralized cache management for ReziApp.
 * Single source of truth for cache keys and invalidation logic.
 *
 * Prevents stale data after mutations (booking, payment, approval, etc.)
 */
class CacheInvalidationService
{
    /**
     * Invalidate all residence-related caches after a mutation.
     * Called after: create, update, delete, approve, reject.
     */
    public static function invalidateResidence(int $residenceId): void
    {
        // API listing cache (paginated)
        self::forgetByPattern('api:residences:list:');

        // Individual residence cache
        Cache::forget("api:residence:{$residenceId}");

        // Reference data that may include residence counts
        Cache::forget('api:admin:stats');

        // Geo search caches
        self::forgetByPattern('geo:search:');
    }

    /**
     * Invalidate booking-related caches.
     */
    public static function invalidateBooking(int $residenceId, ?int $userId = null): void
    {
        // Residence availability
        Cache::forget("residence:{$residenceId}:availability");

        // Owner stats
        Cache::forget("api:owner:stats:{$residenceId}");

        // Admin stats
        Cache::forget('api:admin:stats');

        // User booking list
        if ($userId) {
            Cache::forget("user:{$userId}:bookings");
        }
    }

    /**
     * Invalidate payment-related caches.
     */
    public static function invalidatePayment(?int $userId = null): void
    {
        Cache::forget('api:admin:stats');

        if ($userId) {
            Cache::forget("user:{$userId}:payments");
        }
    }

    /**
     * Invalidate all reference data (communes, amenities, policies).
     * Called rarely — after admin changes.
     */
    public static function invalidateReferenceData(): void
    {
        Cache::forget('api:communes');
        Cache::forget('api:amenities');
        Cache::forget('api:cancellation_policies');
        self::forgetByPattern('api:quartiers:');
    }

    /**
     * Invalidate user-related caches after profile/role change.
     */
    public static function invalidateUser(int $userId): void
    {
        Cache::forget("user:{$userId}:profile");
        Cache::forget("user:{$userId}:bookings");
        Cache::forget("user:{$userId}:payments");
    }

    /**
     * Forget cache keys matching a prefix.
     *
     * Supports database and Redis drivers. For other drivers (array, file) this
     * is a no-op — those are typically used in tests where explicit Cache::forget
     * calls or RefreshDatabase are sufficient for isolation.
     */
    private static function forgetByPattern(string $prefix): void
    {
        $cachePrefix = config('cache.prefix', '');
        $fullPrefix = $cachePrefix ? "{$cachePrefix}:{$prefix}" : $prefix;

        try {
            $driver = config('cache.default');

            if ($driver === 'database') {
                $table = config('cache.stores.database.table', 'cache');
                \Illuminate\Support\Facades\DB::table($table)
                    ->where('key', 'like', "{$fullPrefix}%")
                    ->delete();

                return;
            }

            if ($driver === 'redis') {
                $redis = Cache::getRedis();
                $pattern = "{$fullPrefix}*";
                $keys = $redis->keys($pattern);

                foreach ($keys as $key) {
                    $redis->del($key);
                }
            }
        } catch (\Throwable $e) {
            // Cache cleanup failure is non-critical
            \Illuminate\Support\Facades\Log::channel('critical')->warning('Cache pattern invalidation failed', [
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Full cache flush (only for admin/deployment use).
     */
    public static function flushAll(): void
    {
        Cache::flush();
    }
}
