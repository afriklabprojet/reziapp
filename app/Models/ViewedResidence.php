<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewedResidence extends Model
{
    protected $fillable = [
        'user_id',
        'residence_id',
        'view_count',
        'duration_seconds',
        'last_viewed_at',
    ];

    protected $casts = [
        'last_viewed_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    // ===== SCOPES =====

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('last_viewed_at', '>=', now()->subDays($days));
    }

    // ===== STATIC METHODS =====

    /**
     * Enregistrer une vue
     */
    public static function recordView(int $userId, int $residenceId, ?int $durationSeconds = null): self
    {
        $view = self::updateOrCreate(
            ['user_id' => $userId, 'residence_id' => $residenceId],
            [
                'last_viewed_at' => now(),
            ],
        );

        $view->increment('view_count');

        if ($durationSeconds) {
            $view->update([
                'duration_seconds' => ($view->duration_seconds ?? 0) + $durationSeconds,
            ]);
        }

        return $view;
    }

    /**
     * Obtenir les résidences récemment vues
     */
    public static function getRecentlyViewed(int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::forUser($userId)
            ->recent()
            ->with(['residence' => function ($query) {
                $query->with(['photos', 'commune.city', 'type'])
                    ->where('is_published', true);
            }])
            ->whereHas('residence', function ($query) {
                $query->where('is_published', true);
            })
            ->orderByDesc('last_viewed_at')
            ->limit($limit)
            ->get()
            ->map(fn ($view) => $view->residence)
            ->filter();
    }

    /**
     * Obtenir les résidences les plus consultées par un utilisateur
     */
    public static function getMostViewed(int $userId, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return self::forUser($userId)
            ->with(['residence' => function ($query) {
                $query->with(['photos', 'commune.city', 'type'])
                    ->where('is_published', true);
            }])
            ->whereHas('residence', function ($query) {
                $query->where('is_published', true);
            })
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get()
            ->map(fn ($view) => $view->residence)
            ->filter();
    }

    /**
     * Nettoyer les vues anciennes
     */
    public static function cleanup(int $daysOld = 90): int
    {
        return self::where('last_viewed_at', '<', now()->subDays($daysOld))->delete();
    }
}
