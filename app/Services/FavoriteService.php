<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\ComparisonList;
use App\Models\Favorite;
use App\Models\PriceAlert;
use App\Models\Residence;
use App\Models\User;
use App\Models\ViewHistory;
use Illuminate\Support\Collection as LaravelCollection;

class FavoriteService
{
    /**
     * Add a residence to favorites
     */
    public function addToFavorites(int $userId, int $residenceId, ?int $collectionId = null, ?string $notes = null, ?array $tags = null): Favorite
    {
        $favorite = Favorite::firstOrCreate(
            ['user_id' => $userId, 'residence_id' => $residenceId],
            ['collection_id' => $collectionId, 'notes' => $notes, 'tags' => $tags],
        );

        if ($collectionId && $favorite->collection) {
            $favorite->collection->updateFavoritesCount();
        }

        return $favorite;
    }

    /**
     * Remove from favorites
     */
    public function removeFromFavorites(int $userId, int $residenceId): bool
    {
        $favorite = Favorite::where('user_id', $userId)
            ->where('residence_id', $residenceId)
            ->first();

        if ($favorite) {
            $collection = $favorite->collection;
            $favorite->delete();

            if ($collection) {
                $collection->updateFavoritesCount();
            }

            return true;
        }

        return false;
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite(int $userId, int $residenceId): array
    {
        $favorite = Favorite::where('user_id', $userId)
            ->where('residence_id', $residenceId)
            ->first();

        if ($favorite) {
            $this->removeFromFavorites($userId, $residenceId);

            return ['action' => 'removed', 'is_favorite' => false];
        }

        $this->addToFavorites($userId, $residenceId);

        return ['action' => 'added', 'is_favorite' => true];
    }

    /**
     * Check if residence is favorite
     */
    public function isFavorite(int $userId, int $residenceId): bool
    {
        return Favorite::where('user_id', $userId)
            ->where('residence_id', $residenceId)
            ->exists();
    }

    /**
     * Get user favorites
     */
    public function getUserFavorites(int $userId, ?int $collectionId = null)
    {
        $query = Favorite::where('user_id', $userId)
            ->with(['residence.photos', 'collection']);

        if ($collectionId) {
            $query->where('collection_id', $collectionId);
        }

        return $query->latest()->paginate(12);
    }

    /**
     * Move favorite to collection
     */
    public function moveToCollection(int $userId, int $residenceId, ?int $collectionId): Favorite
    {
        $favorite = Favorite::where('user_id', $userId)
            ->where('residence_id', $residenceId)
            ->firstOrFail();

        $oldCollection = $favorite->collection;
        $favorite->update(['collection_id' => $collectionId]);

        // Update counts
        if ($oldCollection) {
            $oldCollection->updateFavoritesCount();
        }
        if ($collectionId) {
            Collection::find($collectionId)?->updateFavoritesCount();
        }

        return $favorite->refresh();
    }

    /**
     * Update favorite notes
     */
    public function updateNotes(int $userId, int $residenceId, string $notes): Favorite
    {
        $favorite = Favorite::where('user_id', $userId)
            ->where('residence_id', $residenceId)
            ->firstOrFail();

        $favorite->update(['notes' => $notes]);

        return $favorite;
    }

    /**
     * Update favorite tags
     */
    public function updateTags(int $userId, int $residenceId, array $tags): Favorite
    {
        $favorite = Favorite::where('user_id', $userId)
            ->where('residence_id', $residenceId)
            ->firstOrFail();

        $favorite->update(['tags' => $tags]);

        return $favorite;
    }

    /**
     * Create a collection
     */
    public function createCollection(int $userId, string $name, ?string $description = null, bool $isPublic = false): Collection
    {
        return Collection::create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'is_public' => $isPublic,
        ]);
    }

    /**
     * Get user collections
     */
    public function getUserCollections(int $userId): LaravelCollection
    {
        return Collection::where('user_id', $userId)
            ->withCount('favorites')
            ->latest()
            ->get();
    }

    /**
     * Delete collection (move favorites to uncategorized)
     */
    public function deleteCollection(int $userId, int $collectionId): bool
    {
        $collection = Collection::where('user_id', $userId)
            ->where('id', $collectionId)
            ->first();

        if (!$collection) {
            return false;
        }

        // Move all favorites to uncategorized
        Favorite::where('collection_id', $collectionId)
            ->update(['collection_id' => null]);

        $collection->delete();

        return true;
    }

    /**
     * Record view history
     */
    public function recordView(int $userId, int $residenceId, int $durationSeconds = 0): ViewHistory
    {
        return ViewHistory::recordView($userId, $residenceId, $durationSeconds);
    }

    /**
     * Get recent views
     */
    public function getRecentViews(int $userId, int $limit = 20): LaravelCollection
    {
        return ViewHistory::where('user_id', $userId)
            ->with(['residence.photos'])
            ->orderByDesc('last_viewed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Clear view history
     */
    public function clearViewHistory(int $userId): int
    {
        return ViewHistory::where('user_id', $userId)->delete();
    }

    /**
     * Create price alert
     */
    public function createPriceAlert(int $userId, int $residenceId, string $alertType = 'decrease_only', ?float $targetPrice = null): PriceAlert
    {
        $residence = Residence::findOrFail($residenceId);

        return PriceAlert::updateOrCreate(
            ['user_id' => $userId, 'residence_id' => $residenceId],
            [
                'original_price' => $residence->price,
                'current_price' => $residence->price,
                'target_price' => $targetPrice,
                'alert_type' => $alertType,
                'is_active' => true,
            ],
        );
    }

    /**
     * Get user price alerts
     */
    public function getUserPriceAlerts(int $userId): LaravelCollection
    {
        return PriceAlert::where('user_id', $userId)
            ->with(['residence.photos'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Deactivate price alert
     */
    public function deactivatePriceAlert(int $userId, int $alertId): bool
    {
        return PriceAlert::where('user_id', $userId)
            ->where('id', $alertId)
            ->update(['is_active' => false]) > 0;
    }

    /**
     * Add to comparison list
     */
    public function addToComparison(int $userId, int $residenceId): ComparisonList
    {
        $list = ComparisonList::getOrCreateForUser($userId);
        $list->addResidence($residenceId);

        return $list;
    }

    /**
     * Remove from comparison
     */
    public function removeFromComparison(int $userId, int $residenceId): ComparisonList
    {
        $list = ComparisonList::getOrCreateForUser($userId);
        $list->removeResidence($residenceId);

        return $list;
    }

    /**
     * Get comparison list
     */
    public function getComparisonList(int $userId): ComparisonList
    {
        return ComparisonList::getOrCreateForUser($userId);
    }

    /**
     * Clear comparison list
     */
    public function clearComparison(int $userId): void
    {
        ComparisonList::where('user_id', $userId)
            ->update(['residence_ids' => []]);
    }
}
