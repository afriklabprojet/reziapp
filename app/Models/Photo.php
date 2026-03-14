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
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_optimized' => 'boolean',
    ];

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
