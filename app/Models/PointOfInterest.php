<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointOfInterest extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'name',
        'type',
        'distance_meters',
        'walking_time_minutes',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'distance_meters' => 'decimal:2',
        'walking_time_minutes' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public const TYPES = [
        'restaurant' => ['icon' => '🍽️', 'label' => 'Restaurant'],
        'supermarket' => ['icon' => '🛒', 'label' => 'Supermarché'],
        'pharmacy' => ['icon' => '💊', 'label' => 'Pharmacie'],
        'hospital' => ['icon' => '🏥', 'label' => 'Hôpital'],
        'bank' => ['icon' => '🏦', 'label' => 'Banque'],
        'transport' => ['icon' => '🚌', 'label' => 'Transport'],
        'beach' => ['icon' => '🏖️', 'label' => 'Plage'],
        'mall' => ['icon' => '🏬', 'label' => 'Centre commercial'],
        'school' => ['icon' => '🏫', 'label' => 'École'],
        'mosque' => ['icon' => '🕌', 'label' => 'Mosquée'],
        'church' => ['icon' => '⛪', 'label' => 'Église'],
        'park' => ['icon' => '🌳', 'label' => 'Parc'],
        'gym' => ['icon' => '💪', 'label' => 'Salle de sport'],
        'other' => ['icon' => '📍', 'label' => 'Autre'],
    ];

    // Relationships
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    // Accessors
    public function getIconAttribute(): string
    {
        return self::TYPES[$this->type]['icon'] ?? '📍';
    }

    public function getLabelAttribute(): string
    {
        return self::TYPES[$this->type]['label'] ?? ucfirst($this->type);
    }

    public function getFormattedDistanceAttribute(): string
    {
        if ($this->distance_meters < 1000) {
            return round($this->distance_meters).' m';
        }

        return number_format($this->distance_meters / 1000, 1, ',', ' ').' km';
    }

    public function getFormattedWalkingTimeAttribute(): string
    {
        if (!$this->walking_time_minutes) {
            // Estimate: 5 km/h walking speed = 83m/min
            $estimated = ceil($this->distance_meters / 83);

            return "~{$estimated} min à pied";
        }

        return "{$this->walking_time_minutes} min à pied";
    }

    // Scopes
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithinDistance($query, float $maxMeters)
    {
        return $query->where('distance_meters', '<=', $maxMeters);
    }

    public function scopeOrderByDistance($query)
    {
        return $query->orderBy('distance_meters');
    }
}
