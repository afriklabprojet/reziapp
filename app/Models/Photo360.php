<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo360 extends Model
{
    use HasFactory;

    protected $table = 'photos_360';

    protected $fillable = [
        'residence_id',
        'path',
        'title',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    // Relationships
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    // Accessors
    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }
}
