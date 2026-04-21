<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'date',
        'price',
        'min_nights',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'min_nights' => 'integer',
    ];

    // Relations
    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    // Scopes
    public function scopeForResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeFuture($query)
    {
        return $query->where('date', '>=', today());
    }

    // Methods
    public static function getPriceForDate(int $residenceId, $date): ?float
    {
        $specialPrice = self::forResidence($residenceId)
            ->where('date', $date)
            ->first();

        return $specialPrice?->price;
    }

    public static function getPricesForDateRange(int $residenceId, $startDate, $endDate): array
    {
        return self::forResidence($residenceId)
            ->forDateRange($startDate, $endDate)
            ->pluck('price', 'date')
            ->mapWithKeys(function ($price, $date) {
                return [$date instanceof \DateTime ? $date->format('Y-m-d') : $date => $price];
            })
            ->toArray();
    }

    // Helpers
    public function getReasonLabel(): string
    {
        return match ($this->reason) {
            'weekend' => 'Week-end',
            'holiday' => 'Jour férié',
            'high_season' => 'Haute saison',
            'low_season' => 'Basse saison',
            'event' => 'Événement',
            'custom' => 'Personnalisé',
            default => $this->reason ?? 'Prix spécial',
        };
    }

    public function getChangePercent(float $basePrice): float
    {
        if ($basePrice <= 0) {
            return 0;
        }

        return round((($this->price - $basePrice) / $basePrice) * 100, 1);
    }

    public function isHigherThanBase(float $basePrice): bool
    {
        return $this->price > $basePrice;
    }
}
