<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'start_date',
        'end_date',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    public function scopeActive($query)
    {
        return $query->where('end_date', '>=', today());
    }

    // Methods
    public static function isDateBlocked(int $residenceId, $date): bool
    {
        return self::forResidence($residenceId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    public static function hasBlockedDatesInRange(int $residenceId, $startDate, $endDate): bool
    {
        return self::forResidence($residenceId)
            ->forDateRange($startDate, $endDate)
            ->exists();
    }

    public static function getBlockedDatesArray(int $residenceId, $startDate, $endDate): array
    {
        $blockedRanges = self::forResidence($residenceId)
            ->forDateRange($startDate, $endDate)
            ->get();

        $dates = [];
        foreach ($blockedRanges as $range) {
            $current = $range->start_date->copy();
            while ($current <= $range->end_date) {
                $dates[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        return array_unique($dates);
    }

    // Helpers
    public function getDurationDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getReasonLabel(): string
    {
        return match ($this->reason) {
            'maintenance' => 'Maintenance',
            'personal' => 'Usage personnel',
            'renovation' => 'Rénovation',
            'booking' => 'Réservation externe',
            'other' => 'Autre',
            default => $this->reason ?? 'Non spécifié',
        };
    }
}
