<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'commune',
        'min_price',
        'max_price',
        'bedrooms',
        'type',
        'amenities',
        'latitude',
        'longitude',
        'radius',
        'results_count',
        'search_query',
    ];

    protected $casts = [
        'amenities' => 'array',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Utilisateur qui a effectué la recherche
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Générer un label descriptif pour la recherche
     */
    public function getDescriptionAttribute(): string
    {
        $parts = [];

        if ($this->commune) {
            $parts[] = $this->commune;
        }

        if ($this->type) {
            $parts[] = ucfirst($this->type);
        }

        if ($this->bedrooms) {
            $parts[] = $this->bedrooms.' ch.';
        }

        if ($this->min_price || $this->max_price) {
            if ($this->min_price && $this->max_price) {
                $parts[] = number_format($this->min_price, 0, ',', ' ').' - '.number_format($this->max_price, 0, ',', ' ').' FCFA';
            } elseif ($this->min_price) {
                $parts[] = 'Min '.number_format($this->min_price, 0, ',', ' ').' FCFA';
            } else {
                $parts[] = 'Max '.number_format($this->max_price, 0, ',', ' ').' FCFA';
            }
        }

        if (empty($parts) && $this->search_query) {
            return $this->search_query;
        }

        return empty($parts) ? 'Recherche générale' : implode(' • ', $parts);
    }

    /**
     * Générer l'URL pour relancer cette recherche
     */
    public function getSearchUrlAttribute(): string
    {
        $params = [];

        if ($this->commune) {
            $params['commune'] = $this->commune;
        }
        if ($this->min_price) {
            $params['min_price'] = $this->min_price;
        }
        if ($this->max_price) {
            $params['max_price'] = $this->max_price;
        }
        if ($this->bedrooms) {
            $params['bedrooms'] = $this->bedrooms;
        }
        if ($this->type) {
            $params['type'] = $this->type;
        }
        if ($this->search_query) {
            $params['q'] = $this->search_query;
        }

        return route('residences.index', $params);
    }

    /**
     * Scope pour les recherches récentes
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope pour les recherches uniques (évite les doublons)
     */
    public function scopeUnique($query)
    {
        return $query->select('search_histories.*')
            ->distinct('commune', 'min_price', 'max_price', 'bedrooms', 'type');
    }
}
