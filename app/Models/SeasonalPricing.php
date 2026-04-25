<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SeasonalPricing extends Model
{
    protected $table = 'seasonal_pricing';

    protected $fillable = [
        'residence_id',
        'name',
        'start_date',
        'end_date',
        'price_per_day',
        'price_per_week',
        'price_per_month',
        'price_multiplier',
        'min_nights',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price_per_day' => 'decimal:2',
        'price_per_week' => 'decimal:2',
        'price_per_month' => 'decimal:2',
        'price_multiplier' => 'decimal:2',
        'min_nights' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'price_multiplier' => 1.00,
        'min_nights' => 1,
        'is_active' => true,
    ];

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Récupère les tarifs saisonniers actifs pour une période
     */
    public static function getActiveForPeriod(int $residenceId, Carbon $startDate, Carbon $endDate): Collection
    {
        return self::where('residence_id', $residenceId)
            ->where('is_active', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Récupère le tarif applicable pour une date
     */
    public static function getPriceForDate(int $residenceId, Carbon $date): ?self
    {
        return self::where('residence_id', $residenceId)
            ->where('is_active', true)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * Templates de saisons prédéfinies pour la Côte d'Ivoire
     */
    public static function getSeasonTemplates(): array
    {
        return [
            'haute_saison' => [
                'name' => 'Haute Saison (Décembre-Janvier)',
                'start_month' => 12,
                'start_day' => 15,
                'end_month' => 1,
                'end_day' => 15,
                'price_multiplier' => 1.30,
                'min_nights' => 3,
            ],
            'fetes_paques' => [
                'name' => 'Fêtes de Pâques',
                'start_month' => 4,
                'start_day' => 1,
                'end_month' => 4,
                'end_day' => 15,
                'price_multiplier' => 1.20,
                'min_nights' => 2,
            ],
            'vacances_ete' => [
                'name' => 'Vacances d\'été',
                'start_month' => 7,
                'start_day' => 1,
                'end_month' => 8,
                'end_day' => 31,
                'price_multiplier' => 1.15,
                'min_nights' => 2,
            ],
            'basse_saison' => [
                'name' => 'Basse Saison',
                'start_month' => 5,
                'start_day' => 1,
                'end_month' => 6,
                'end_day' => 30,
                'price_multiplier' => 0.90,
                'min_nights' => 1,
            ],
        ];
    }

    /**
     * Crée une saison à partir d'un template
     */
    public static function createFromTemplate(int $residenceId, string $templateKey, int $year): ?self
    {
        $templates = self::getSeasonTemplates();

        if (!isset($templates[$templateKey])) {
            return null;
        }

        $template = $templates[$templateKey];

        $startDate = Carbon::create($year, $template['start_month'], $template['start_day']);
        $endDate = Carbon::create(
            $template['end_month'] < $template['start_month'] ? $year + 1 : $year,
            $template['end_month'],
            $template['end_day'],
        );

        return self::create([
            'residence_id' => $residenceId,
            'name' => $template['name'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price_multiplier' => $template['price_multiplier'],
            'min_nights' => $template['min_nights'],
        ]);
    }

    /**
     * Calcule le prix journalier effectif
     */
    public function getEffectiveDailyPrice(Residence $residence): float
    {
        if ($this->price_per_day) {
            return $this->price_per_day;
        }

        return $residence->price_per_day * $this->price_multiplier;
    }

    /**
     * Vérifie si le séjour respecte le minimum de nuits
     */
    public function meetsMinNights(int $nights): bool
    {
        return $nights >= $this->min_nights;
    }

    /**
     * Label pour l'affichage
     */
    public function getLabel(): string
    {
        $priceInfo = $this->price_multiplier != 1.0
            ? ($this->price_multiplier > 1 ? '+' : '').round(($this->price_multiplier - 1) * 100).'%'
            : number_format((float) $this->price_per_day).' FCFA';

        return "{$this->name} ({$this->start_date->format('d/m')} - {$this->end_date->format('d/m')}) - {$priceInfo}";
    }
}
