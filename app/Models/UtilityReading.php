<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityReading extends Model
{
    public const TYPE_ELECTRICITY = 'electricity';
    public const TYPE_WATER       = 'water';
    public const TYPE_GAS         = 'gas';
    public const TYPE_INTERNET    = 'internet';

    public const TYPES = [
        self::TYPE_ELECTRICITY => 'Électricité (CIE)',
        self::TYPE_WATER       => 'Eau (SODECI)',
        self::TYPE_GAS         => 'Gaz',
        self::TYPE_INTERNET    => 'Internet',
    ];

    public const UNITS = [
        self::TYPE_ELECTRICITY => 'kWh',
        self::TYPE_WATER       => 'm³',
        self::TYPE_GAS         => 'm³',
        self::TYPE_INTERNET    => 'Go',
    ];

    protected $fillable = [
        'residence_id', 'user_id', 'booking_id', 'utility_type',
        'reading_value', 'unit', 'reading_date', 'reading_type',
        'photo', 'notes',
    ];

    protected $casts = [
        'reading_value' => 'decimal:2',
        'reading_date'  => 'date',
    ];

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->utility_type] ?? $this->utility_type;
    }

    public function scopeForResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('utility_type', $type);
    }

    /**
     * Calculer la consommation entre deux relevés
     */
    public static function calculateConsumption(int $residenceId, string $type, $from, $to): float
    {
        $readings = self::where('residence_id', $residenceId)
            ->where('utility_type', $type)
            ->whereBetween('reading_date', [$from, $to])
            ->orderBy('reading_date')
            ->pluck('reading_value')
            ->toArray();

        if (count($readings) < 2) {
            return 0;
        }

        return (float) (end($readings) - reset($readings));
    }
}
