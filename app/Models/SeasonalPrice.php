<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonalPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'name',
        'start_date',
        'end_date',
        'price_per_night',
        'price_per_week',
        'price_per_month',
        'min_nights',
        'priority',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price_per_night' => 'decimal:2',
        'price_per_week' => 'decimal:2',
        'price_per_month' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec la résidence
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Scope pour les prix actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour une date donnée
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('start_date', '<=', $date)
                     ->where('end_date', '>=', $date);
    }

    /**
     * Scope pour une période
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Vérifie si la saison est en cours
     */
    public function isCurrentlyActive(): bool
    {
        $today = now()->toDateString();

        return $this->is_active
            && $this->start_date <= $today
            && $this->end_date >= $today;
    }

    /**
     * Calcule le prix pour un nombre de nuits
     */
    public function calculatePrice(int $nights): float
    {
        if ($nights >= 30 && $this->price_per_month) {
            $months = floor($nights / 30);
            $remainingNights = $nights % 30;

            return ($months * $this->price_per_month) + ($remainingNights * $this->price_per_night);
        }

        if ($nights >= 7 && $this->price_per_week) {
            $weeks = floor($nights / 7);
            $remainingNights = $nights % 7;

            return ($weeks * $this->price_per_week) + ($remainingNights * $this->price_per_night);
        }

        return $nights * $this->price_per_night;
    }
}
