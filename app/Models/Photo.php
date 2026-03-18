<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'path',
        'order',
        'is_primary',
        'is_optimized',
        'tags',
        'room_type',
        'moderation_status',
        'moderation_reason',
        'quality_score',
        'quality_issues',
        'safe_search_data',
        'labels_data',
        'image_hash',
        'is_property_photo',
    ];

    protected $casts = [
        'is_primary'       => 'boolean',
        'is_optimized'     => 'boolean',
        'is_property_photo' => 'boolean',
        'tags'             => 'array',
        'quality_issues'   => 'array',
        'safe_search_data' => 'array',
        'labels_data'      => 'array',
    ];

    // ── Scopes ──

    public function scopeApproved($query)
    {
        return $query->whereIn('moderation_status', ['approved', 'pending', 'skipped']);
    }

    public function scopeRejected($query)
    {
        return $query->where('moderation_status', 'rejected');
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('moderation_status', 'review');
    }

    public function scopeByRoom($query, string $roomType)
    {
        return $query->where('room_type', $roomType);
    }

    // ── Helpers ──

    public function isModerated(): bool
    {
        return !in_array($this->moderation_status, ['pending', null]);
    }

    public function isSafe(): bool
    {
        return in_array($this->moderation_status, ['approved', 'skipped', 'pending', null]);
    }

    /**
     * Residence that owns this photo
     */
    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Get the full URL of the photo
     * Handles both local storage paths and external URLs (e.g. Unsplash)
     */
    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://')) {
            return $this->path;
        }

        return Storage::url($this->path);
    }

    /**
     * Delete photo file when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($photo) {
            if (Storage::exists($photo->path)) {
                Storage::delete($photo->path);
            }
        });
    }
}
