<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerAnalytics extends Model
{
    protected $table = 'owner_analytics';

    protected $fillable = [
        'user_id',
        'date',
        'total_views',
        'total_inquiries',
        'total_bookings',
        'total_revenue',
        'occupancy_rate',
        'avg_booking_value',
        'messages_received',
        'messages_answered',
        'avg_response_time_minutes',
        'review_score_avg',
        'reviews_count',
        'cancellations_count',
    ];

    protected $casts = [
        'date' => 'date',
        'total_views' => 'integer',
        'total_inquiries' => 'integer',
        'total_bookings' => 'integer',
        'total_revenue' => 'decimal:2',
        'occupancy_rate' => 'decimal:2',
        'avg_booking_value' => 'decimal:2',
        'messages_received' => 'integer',
        'messages_answered' => 'integer',
        'avg_response_time_minutes' => 'integer',
        'review_score_avg' => 'decimal:2',
        'reviews_count' => 'integer',
        'cancellations_count' => 'integer',
    ];

    // ===== RELATIONSHIPS =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ===== SCOPES =====

    public function scopeForOwner($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->where('date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeThisYear($query)
    {
        return $query->whereBetween('date', [now()->startOfYear(), now()->endOfYear()]);
    }

    // ===== AGGREGATIONS =====

    /**
     * Obtenir les totaux pour une période
     */
    public static function getTotals(int $userId, $startDate = null, $endDate = null): array
    {
        $query = self::forOwner($userId);
        
        if ($startDate && $endDate) {
            $query->forPeriod($startDate, $endDate);
        }

        return [
            'profile_views' => $query->sum('profile_views'),
            'residence_views' => $query->sum('residence_views'),
            'search_appearances' => $query->sum('search_appearances'),
            'messages_received' => $query->sum('messages_received'),
            'bookings_count' => $query->sum('bookings_count'),
            'revenue' => $query->sum('revenue'),
            'avg_response_time' => round($query->avg('response_time_avg') ?? 0),
            'avg_response_rate' => round($query->avg('response_rate') ?? 0, 2),
            'avg_conversion_rate' => round($query->avg('conversion_rate') ?? 0, 2),
        ];
    }

    /**
     * Obtenir les données pour un graphique (derniers N jours)
     */
    public static function getChartData(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $data = self::forOwner($userId)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => $d->format('d/m'))->toArray(),
            'views' => $data->pluck('residence_views')->toArray(),
            'bookings' => $data->pluck('bookings_count')->toArray(),
            'revenue' => $data->pluck('revenue')->toArray(),
        ];
    }

    /**
     * Comparer deux périodes
     */
    public static function comparePeriods(int $userId, $currentStart, $currentEnd, $previousStart, $previousEnd): array
    {
        $current = self::getTotals($userId, $currentStart, $currentEnd);
        $previous = self::getTotals($userId, $previousStart, $previousEnd);

        $comparison = [];
        foreach ($current as $key => $value) {
            $prevValue = $previous[$key] ?? 0;
            $change = $prevValue > 0 ? (($value - $prevValue) / $prevValue) * 100 : 0;
            
            $comparison[$key] = [
                'current' => $value,
                'previous' => $prevValue,
                'change' => round($change, 1),
                'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            ];
        }

        return $comparison;
    }

    /**
     * Calculer le score de performance global
     */
    public static function calculatePerformanceScore(int $userId): int
    {
        $last30Days = self::getTotals($userId, now()->subDays(30), now());

        $score = 0;

        // Taux de réponse (max 30 points)
        $score += min(30, ($last30Days['avg_response_rate'] ?? 0) * 0.3);

        // Temps de réponse (max 20 points - moins c'est mieux)
        $responseTime = $last30Days['avg_response_time'] ?? 1440; // 24h par défaut
        $score += max(0, 20 - ($responseTime / 60)); // -1 point par heure

        // Taux de conversion (max 25 points)
        $score += min(25, ($last30Days['avg_conversion_rate'] ?? 0) * 2.5);

        // Activité (max 25 points)
        $bookings = $last30Days['bookings_count'] ?? 0;
        $score += min(25, $bookings * 5);

        return min(100, max(0, round($score)));
    }
}
