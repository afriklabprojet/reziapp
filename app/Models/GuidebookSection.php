<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuidebookSection extends Model
{
    protected $fillable = [
        'guidebook_id', 'title', 'icon', 'content',
        'photos', 'sort_order', 'is_visible',
    ];

    protected $casts = [
        'photos'     => 'array',
        'is_visible' => 'boolean',
    ];

    public function guidebook(): BelongsTo
    {
        return $this->belongsTo(Guidebook::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
