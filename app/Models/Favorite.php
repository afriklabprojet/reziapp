<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'residence_id',
        'collection_id',
        'notes',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    /**
     * L'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La résidence
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * La collection
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Ajouter/retirer un favori (toggle)
     */
    public static function toggle(User $user, Residence $residence): bool
    {
        $favorite = self::where('user_id', $user->id)
            ->where('residence_id', $residence->id)
            ->first();

        if ($favorite) {
            $collection = $favorite->collection;
            $favorite->delete();

            // Update collection count
            if ($collection) {
                $collection->updateFavoritesCount();
            }

            return false; // Removed
        }

        self::create([
            'user_id' => $user->id,
            'residence_id' => $residence->id,
        ]);

        return true; // Added
    }

    /**
     * Scopes
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInCollection($query, ?int $collectionId)
    {
        if ($collectionId) {
            return $query->where('collection_id', $collectionId);
        }

        return $query;
    }

    public function scopeUncategorized($query)
    {
        return $query->whereNull('collection_id');
    }
}
