<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class MarketPriceData extends Model
{
    protected $table = 'market_price_data';

    protected $fillable = [
        'country_code',
        'city',
        'commune',
        'residence_type',
        'bedrooms',
        'avg_price_per_night',
        'min_price_per_night',
        'max_price_per_night',
        'median_price_per_night',
        'sample_size',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'bedrooms' => 'integer',
        'avg_price_per_night' => 'decimal:2',
        'min_price_per_night' => 'decimal:2',
        'max_price_per_night' => 'decimal:2',
        'median_price_per_night' => 'decimal:2',
        'sample_size' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    // Residence types
    public const TYPE_APARTMENT = 'apartment';
    public const TYPE_STUDIO = 'studio';
    public const TYPE_HOUSE = 'house';
    public const TYPE_VILLA = 'villa';
    public const TYPE_ROOM = 'room';

    // ===== RELATIONSHIPS =====

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    // ===== SCOPES =====

    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeForCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeForCommune($query, ?string $commune)
    {
        return $query->where('commune', $commune);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('residence_type', $type);
    }

    public function scopeForBedrooms($query, ?int $bedrooms)
    {
        return $query->where('bedrooms', $bedrooms);
    }

    public function scopeCurrentPeriod($query)
    {
        return $query->where('period_start', '>=', now()->startOfMonth());
    }

    // ===== HELPERS =====

    /**
     * Obtenir le prix moyen du marché pour des critères donnés
     */
    public static function getMarketPrice(
        string $city,
        string $residenceType,
        ?int $bedrooms = null,
        string $countryCode = 'CI',
    ): ?array {
        $query = self::forCountry($countryCode)
            ->forCity($city)
            ->forType($residenceType)
            ->currentPeriod();

        if ($bedrooms) {
            $query->forBedrooms($bedrooms);
        }

        $data = $query->first();

        if (!$data) {
            return null;
        }

        return [
            'avg' => $data->avg_price_per_night,
            'min' => $data->min_price_per_night,
            'max' => $data->max_price_per_night,
            'median' => $data->median_price_per_night,
            'sample_size' => $data->sample_size,
            'period' => $data->period_start->format('M Y'),
        ];
    }

    /**
     * Comparer un prix au marché
     */
    public static function comparePriceToMarket(
        string $city,
        string $residenceType,
        float $price,
        ?int $bedrooms = null,
        string $countryCode = 'CI',
    ): array {
        $market = self::getMarketPrice($city, $residenceType, $bedrooms, $countryCode);

        if (!$market || $market['avg'] == 0) {
            return [
                'status' => 'unknown',
                'message' => 'Pas assez de données pour cette zone',
                'difference_percent' => null,
            ];
        }

        $diffPercent = (($price - $market['avg']) / $market['avg']) * 100;

        if ($diffPercent < -20) {
            $status = 'very_low';
            $message = 'Prix très attractif - '.abs(round($diffPercent)).'% en dessous du marché';
        } elseif ($diffPercent < -10) {
            $status = 'low';
            $message = 'Prix attractif - '.abs(round($diffPercent)).'% en dessous du marché';
        } elseif ($diffPercent < 10) {
            $status = 'fair';
            $message = 'Prix conforme au marché';
        } elseif ($diffPercent < 20) {
            $status = 'high';
            $message = 'Prix légèrement élevé - '.round($diffPercent).'% au-dessus du marché';
        } else {
            $status = 'very_high';
            $message = 'Prix élevé - '.round($diffPercent).'% au-dessus du marché';
        }

        return [
            'status' => $status,
            'message' => $message,
            'difference_percent' => round($diffPercent, 1),
            'market_avg' => $market['avg'],
            'market_min' => $market['min'],
            'market_max' => $market['max'],
            'market_median' => $market['median'],
            'sample_size' => $market['sample_size'],
        ];
    }

    /**
     * Recalculer tous les prix du marché via commande artisan
     */
    public static function recalculateAll(string $countryCode = 'CI'): void
    {
        Artisan::call('rezi:calculate-market-prices', ['--country' => $countryCode]);
    }

    /**
     * Obtenir le label du type de résidence
     */
    public function getTypeLabel(): string
    {
        return match($this->residence_type) {
            self::TYPE_APARTMENT => 'Appartement',
            self::TYPE_STUDIO => 'Studio',
            self::TYPE_HOUSE => 'Maison',
            self::TYPE_VILLA => 'Villa',
            self::TYPE_ROOM => 'Chambre',
            default => $this->residence_type,
        };
    }

    /**
     * Formater le prix pour affichage
     */
    public function getFormattedAvgPrice(): string
    {
        return number_format((float) $this->avg_price_per_night, 0, ',', ' ').' FCFA/nuit';
    }
}
