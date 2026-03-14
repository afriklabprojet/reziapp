<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'stat_date',
        'views',
        'contacts',
        'shares',
        'favorites',
        'geo_searches',
        'map_views',
        'mobile_views',
        'desktop_views',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'views' => 'integer',
        'contacts' => 'integer',
        'shares' => 'integer',
        'favorites' => 'integer',
        'geo_searches' => 'integer',
        'map_views' => 'integer',
        'mobile_views' => 'integer',
        'desktop_views' => 'integer',
    ];

    /**
     * La résidence concernée
     */
    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Scope pour une période donnée
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('stat_date', [$start, $end]);
    }

    /**
     * Scope pour aujourd'hui
     */
    public function scopeToday($query)
    {
        return $query->where('stat_date', now()->toDateString());
    }

    /**
     * Obtenir ou créer les stats du jour pour une résidence
     */
    public static function getOrCreateForToday(int $residenceId): self
    {
        return self::firstOrCreate(
            [
                'residence_id' => $residenceId,
                'stat_date' => now()->toDateString(),
            ],
            [
                'views' => 0,
                'contacts' => 0,
                'shares' => 0,
                'favorites' => 0,
                'geo_searches' => 0,
                'map_views' => 0,
                'mobile_views' => 0,
                'desktop_views' => 0,
            ],
        );
    }

    /**
     * Incrémenter un compteur
     */
    public function incrementCounter(string $counter, int $amount = 1): void
    {
        if (in_array($counter, $this->fillable)) {
            $this->increment($counter, $amount);
        }
    }

    /**
     * Calculer les totaux pour une résidence
     */
    public static function getTotalsForResidence(int $residenceId): array
    {
        return self::where('residence_id', $residenceId)
            ->selectRaw('
                SUM(views) as total_views,
                SUM(contacts) as total_contacts,
                SUM(shares) as total_shares,
                SUM(favorites) as total_favorites,
                SUM(geo_searches) as total_geo_searches,
                SUM(map_views) as total_map_views
            ')
            ->first()
            ->toArray();
    }
}
