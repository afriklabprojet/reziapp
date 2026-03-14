<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcalBlockedDate extends Model
{
    protected $fillable = [
        'ical_feed_id', 'residence_id', 'start_date', 'end_date',
        'summary', 'uid',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(IcalFeed::class, 'ical_feed_id');
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Vérifier si une date tombe dans cet intervalle bloqué
     */
    public function containsDate(\Carbon\Carbon $date): bool
    {
        return $date->between($this->start_date, $this->end_date);
    }

    public function scopeForResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    public function scopeOverlapping($query, $startDate, $endDate)
    {
        return $query->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);
    }
}
