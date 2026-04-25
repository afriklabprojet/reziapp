<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'filters',
        'location',
        'latitude',
        'longitude',
        'radius_km',
        'min_price',
        'max_price',
        'check_in',
        'check_out',
        'guests',
        'has_alerts',
        'alert_frequency',
        'new_results_count',
        'last_searched_at',
        'last_alert_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius_km' => 'integer',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'check_in' => 'date',
        'check_out' => 'date',
        'guests' => 'integer',
        'has_alerts' => 'boolean',
        'new_results_count' => 'integer',
        'last_searched_at' => 'datetime',
        'last_alert_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeWithAlerts($query)
    {
        return $query->where('has_alerts', true);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeNeedsAlert($query)
    {
        return $query->where('has_alerts', true)
            ->where('new_results_count', '>', 0)
            ->where(function ($q) {
                $q->whereNull('last_alert_at')
                    ->orWhere(function ($inner) {
                        // Instant alerts
                        $inner->where('alert_frequency', 'instant')
                            ->where('last_alert_at', '<', now()->subMinutes(5));
                    })
                    ->orWhere(function ($inner) {
                        // Daily alerts
                        $inner->where('alert_frequency', 'daily')
                            ->where('last_alert_at', '<', now()->subDay());
                    })
                    ->orWhere(function ($inner) {
                        // Weekly alerts
                        $inner->where('alert_frequency', 'weekly')
                            ->where('last_alert_at', '<', now()->subWeek());
                    });
            });
    }

    // Methods
    public function buildSearchUrl(): string
    {
        $params = [];

        if ($this->location) {
            $params['location'] = $this->location;
        }
        if ($this->latitude && $this->longitude) {
            $params['lat'] = $this->latitude;
            $params['lng'] = $this->longitude;
        }
        if ($this->radius_km) {
            $params['radius'] = $this->radius_km;
        }
        if ($this->min_price) {
            $params['min_price'] = $this->min_price;
        }
        if ($this->max_price) {
            $params['max_price'] = $this->max_price;
        }
        if ($this->check_in) {
            $params['check_in'] = $this->check_in->format('Y-m-d');
        }
        if ($this->check_out) {
            $params['check_out'] = $this->check_out->format('Y-m-d');
        }
        if ($this->guests) {
            $params['guests'] = $this->guests;
        }

        // Add additional filters
        if ($this->filters) {
            $params = array_merge($params, $this->filters);
        }

        return route('search.index', $params);
    }

    public function getFiltersDescription(): string
    {
        $parts = [];

        if ($this->location) {
            $parts[] = $this->location;
        }
        if ($this->min_price || $this->max_price) {
            $price = '';
            if ($this->min_price && $this->max_price) {
                $price = number_format((float) $this->min_price, 0, ',', ' ').' - '.number_format((float) $this->max_price, 0, ',', ' ').' FCFA';
            } elseif ($this->min_price) {
                $price = 'Min '.number_format((float) $this->min_price, 0, ',', ' ').' FCFA';
            } else {
                $price = 'Max '.number_format((float) $this->max_price, 0, ',', ' ').' FCFA';
            }
            $parts[] = $price;
        }
        if ($this->guests) {
            $parts[] = $this->guests.' voyageur'.($this->guests > 1 ? 's' : '');
        }

        return implode(' • ', $parts) ?: 'Tous les logements';
    }

    public function markSearched(): void
    {
        $this->update(['last_searched_at' => now()]);
    }

    public function markAlertSent(): void
    {
        $this->update([
            'last_alert_at' => now(),
            'new_results_count' => 0,
        ]);
    }

    public function incrementNewResults(int $count = 1): void
    {
        $this->increment('new_results_count', $count);
    }

    public function resetNewResults(): void
    {
        $this->update(['new_results_count' => 0]);
    }

    public static function createFromFilters(int $userId, string $name, array $searchParams): self
    {
        return self::create([
            'user_id' => $userId,
            'name' => $name,
            'filters' => $searchParams['filters'] ?? [],
            'location' => $searchParams['location'] ?? null,
            'latitude' => $searchParams['latitude'] ?? null,
            'longitude' => $searchParams['longitude'] ?? null,
            'radius_km' => $searchParams['radius_km'] ?? null,
            'min_price' => $searchParams['min_price'] ?? null,
            'max_price' => $searchParams['max_price'] ?? null,
            'check_in' => $searchParams['check_in'] ?? null,
            'check_out' => $searchParams['check_out'] ?? null,
            'guests' => $searchParams['guests'] ?? null,
            'has_alerts' => $searchParams['has_alerts'] ?? false,
            'alert_frequency' => $searchParams['alert_frequency'] ?? 'daily',
        ]);
    }
}
