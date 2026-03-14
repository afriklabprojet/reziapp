<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'cover_image',
        'is_public',
        'share_token',
        'favorites_count',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'favorites_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($collection) {
            if (empty($collection->share_token)) {
                $collection->share_token = Str::random(32);
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function updateFavoritesCount(): void
    {
        $this->update(['favorites_count' => $this->favorites()->count()]);
    }

    public function getShareUrl(): string
    {
        return route('collections.shared', $this->share_token);
    }

    public function regenerateShareToken(): void
    {
        $this->update(['share_token' => Str::random(32)]);
    }

    public function getResidences()
    {
        return Residence::whereIn('id', $this->favorites()->pluck('residence_id'));
    }

    public function getCoverImageUrl(): ?string
    {
        if ($this->cover_image) {
            return asset('storage/'.$this->cover_image);
        }

        // Get first residence image as fallback
        $firstFavorite = $this->favorites()->with('residence.photos')->first();
        if ($firstFavorite && $firstFavorite->residence && $firstFavorite->residence->photos->first()) {
            return $firstFavorite->residence->photos->first()?->url;
        }

        return null;
    }
}
