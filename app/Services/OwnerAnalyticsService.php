<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\MarketPriceData;
use App\Models\Message;
use App\Models\OwnerAnalytics;
use App\Models\Residence;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OwnerAnalyticsService
{
    /**
     * Calculer et stocker les analytics quotidiennes pour un propriétaire
     */
    public function calculateDailyAnalytics(User $owner, ?Carbon $date = null): OwnerAnalytics
    {
        $date = $date ?? Carbon::today();

        $residenceIds = $owner->residences()->pluck('id');

        // Vues du jour
        $totalViews = DB::table('residence_views')
            ->whereIn('residence_id', $residenceIds)
            ->whereDate('created_at', $date)
            ->count();

        // Demandes/messages du jour
        $totalInquiries = Message::whereIn('conversation_id', function ($query) use ($residenceIds) {
            $query->select('id')
                ->from('conversations')
                ->whereIn('residence_id', $residenceIds);
        })
            ->whereDate('created_at', $date)
            ->where('sender_id', '!=', $owner->id)
            ->count();

        // Réservations du jour
        $bookings = Booking::whereIn('residence_id', $residenceIds)
            ->whereDate('created_at', $date)
            ->get();

        $totalBookings = $bookings->count();
        $totalRevenue = $bookings->sum('total_amount');
        $avgBookingValue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;

        // Taux d'occupation
        $occupancyRate = $this->calculateOccupancyRate($residenceIds, $date);

        // Messages reçus et répondus
        $messagesReceived = Message::whereIn('conversation_id', function ($query) use ($residenceIds) {
            $query->select('id')
                ->from('conversations')
                ->whereIn('residence_id', $residenceIds);
        })
            ->whereDate('created_at', $date)
            ->where('sender_id', '!=', $owner->id)
            ->count();

        $messagesAnswered = Message::whereIn('conversation_id', function ($query) use ($residenceIds) {
            $query->select('id')
                ->from('conversations')
                ->whereIn('residence_id', $residenceIds);
        })
            ->whereDate('created_at', $date)
            ->where('sender_id', $owner->id)
            ->count();

        // Temps de réponse moyen
        $avgResponseTime = $this->calculateAverageResponseTime($owner, $date);

        // Score reviews
        $reviewStats = Review::whereIn('residence_id', $residenceIds)
            ->whereDate('created_at', '<=', $date)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')
            ->first();

        // Annulations
        $cancellations = Booking::whereIn('residence_id', $residenceIds)
            ->whereDate('cancelled_at', $date)
            ->count();

        return OwnerAnalytics::updateOrCreate(
            ['user_id' => $owner->id, 'date' => $date],
            [
                'total_views' => $totalViews,
                'total_inquiries' => $totalInquiries,
                'total_bookings' => $totalBookings,
                'total_revenue' => $totalRevenue,
                'occupancy_rate' => $occupancyRate,
                'avg_booking_value' => $avgBookingValue,
                'messages_received' => $messagesReceived,
                'messages_answered' => $messagesAnswered,
                'avg_response_time_minutes' => $avgResponseTime,
                'review_score_avg' => $reviewStats->avg_rating,
                'reviews_count' => $reviewStats->count ?? 0,
                'cancellations_count' => $cancellations,
            ],
        );
    }

    /**
     * Obtenir les analytics sur une période
     */
    public function getAnalytics(User $owner, Carbon $startDate, Carbon $endDate): array
    {
        $analytics = OwnerAnalytics::where('user_id', $owner->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $totals = [
            'total_views' => $analytics->sum('total_views'),
            'total_inquiries' => $analytics->sum('total_inquiries'),
            'total_bookings' => $analytics->sum('total_bookings'),
            'total_revenue' => $analytics->sum('total_revenue'),
            'avg_occupancy_rate' => $analytics->avg('occupancy_rate'),
            'avg_response_time' => $analytics->avg('avg_response_time_minutes'),
            'avg_review_score' => $analytics->avg('review_score_avg'),
            'total_cancellations' => $analytics->sum('cancellations_count'),
        ];

        // Conversion rate
        $totals['conversion_rate'] = $totals['total_inquiries'] > 0
            ? ($totals['total_bookings'] / $totals['total_inquiries']) * 100
            : 0;

        // Response rate
        $totalReceived = $analytics->sum('messages_received');
        $totalAnswered = $analytics->sum('messages_answered');
        $totals['response_rate'] = $totalReceived > 0
            ? ($totalAnswered / $totalReceived) * 100
            : 100;

        return [
            'daily' => $analytics,
            'totals' => $totals,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
        ];
    }

    /**
     * Comparer les revenus avec le mois précédent
     */
    public function getRevenueComparison(User $owner): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $currentRevenue = OwnerAnalytics::where('user_id', $owner->id)
            ->whereBetween('date', [$currentMonth, Carbon::now()])
            ->sum('total_revenue');

        $lastMonthRevenue = OwnerAnalytics::where('user_id', $owner->id)
            ->whereBetween('date', [$lastMonth, $lastMonth->copy()->endOfMonth()])
            ->sum('total_revenue');

        $change = $lastMonthRevenue > 0
            ? (($currentRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        return [
            'current' => $currentRevenue,
            'previous' => $lastMonthRevenue,
            'change_percent' => round($change, 1),
            'trend' => $change >= 0 ? 'up' : 'down',
        ];
    }

    /**
     * Obtenir les suggestions d'amélioration
     */
    public function getImprovementSuggestions(User $owner): array
    {
        $suggestions = [];
        $analytics = $this->getAnalytics($owner, Carbon::now()->subDays(30), Carbon::now());
        $totals = $analytics['totals'];

        // Taux de réponse bas
        if ($totals['response_rate'] < 80) {
            $suggestions[] = [
                'type' => 'response',
                'priority' => 'high',
                'title' => 'Améliorez votre taux de réponse',
                'description' => "Votre taux de réponse est de {$totals['response_rate']}%. Répondez plus rapidement aux messages pour augmenter vos réservations.",
                'action' => 'Activer les notifications et auto-réponses',
            ];
        }

        // Temps de réponse élevé
        if ($totals['avg_response_time'] && $totals['avg_response_time'] > 120) {
            $suggestions[] = [
                'type' => 'speed',
                'priority' => 'medium',
                'title' => 'Répondez plus vite',
                'description' => "Votre temps de réponse moyen est de {$totals['avg_response_time']} minutes. Les propriétaires qui répondent en moins d'une heure ont 40% plus de réservations.",
                'action' => 'Configurer les réponses automatiques',
            ];
        }

        // Score avis bas
        if ($totals['avg_review_score'] && $totals['avg_review_score'] < 4.0) {
            $suggestions[] = [
                'type' => 'quality',
                'priority' => 'high',
                'title' => 'Améliorez la qualité de vos logements',
                'description' => "Votre note moyenne est de {$totals['avg_review_score']}/5. Lisez les avis pour identifier les points à améliorer.",
                'action' => 'Consulter les avis détaillés',
            ];
        }

        // Taux de conversion bas
        if ($totals['conversion_rate'] < 10) {
            $suggestions[] = [
                'type' => 'conversion',
                'priority' => 'medium',
                'title' => 'Optimisez vos annonces',
                'description' => "Seulement {$totals['conversion_rate']}% de vos demandes se convertissent en réservations. Améliorez vos photos et descriptions.",
                'action' => 'Améliorer mes annonces',
            ];
        }

        // Pas assez de photos
        $residencesWithFewPhotos = $owner->residences()
            ->withCount('photos')
            ->having('photos_count', '<', 5)
            ->count();

        if ($residencesWithFewPhotos > 0) {
            $suggestions[] = [
                'type' => 'photos',
                'priority' => 'medium',
                'title' => 'Ajoutez plus de photos',
                'description' => "{$residencesWithFewPhotos} de vos logements ont moins de 5 photos. Les annonces avec 10+ photos ont 2x plus de vues.",
                'action' => 'Ajouter des photos',
            ];
        }

        return $suggestions;
    }

    /**
     * Comparer le prix avec le marché
     */
    public function comparePriceToMarket(Residence $residence): array
    {
        $marketData = MarketPriceData::where('country_code', $residence->country_code ?? 'CI')
            ->where('city', $residence->city)
            ->where('commune', $residence->commune)
            ->where('residence_type', $residence->type)
            ->where('bedrooms', $residence->bedrooms)
            ->orderBy('period_end', 'desc')
            ->first();

        if (!$marketData) {
            // Fallback: données par type uniquement
            $marketData = MarketPriceData::where('country_code', $residence->country_code ?? 'CI')
                ->where('residence_type', $residence->type)
                ->whereNull('commune')
                ->orderBy('period_end', 'desc')
                ->first();
        }

        if (!$marketData) {
            return [
                'has_data' => false,
                'message' => 'Pas assez de données pour comparer',
            ];
        }

        $currentPrice = $residence->price_per_night;
        $marketAvg = $marketData->avg_price_per_night;
        $diff = (($currentPrice - $marketAvg) / $marketAvg) * 100;

        $recommendation = 'optimal';
        $message = 'Votre prix est dans la moyenne du marché';

        if ($diff > 20) {
            $recommendation = 'high';
            $message = 'Votre prix est supérieur à la moyenne. Envisagez de le réduire pour augmenter vos réservations.';
        } elseif ($diff < -20) {
            $recommendation = 'low';
            $message = 'Votre prix est inférieur à la moyenne. Vous pourriez augmenter vos revenus.';
        }

        return [
            'has_data' => true,
            'your_price' => $currentPrice,
            'market_avg' => $marketAvg,
            'market_min' => $marketData->min_price_per_night,
            'market_max' => $marketData->max_price_per_night,
            'difference_percent' => round($diff, 1),
            'recommendation' => $recommendation,
            'message' => $message,
            'sample_size' => $marketData->sample_size,
        ];
    }

    /**
     * Calculer le taux d'occupation
     */
    protected function calculateOccupancyRate($residenceIds, Carbon $date): float
    {
        if ($residenceIds->isEmpty()) {
            return 0;
        }

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;

        $totalDays = $residenceIds->count() * $daysInMonth;

        $bookedDays = Booking::whereIn('residence_id', $residenceIds)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('check_in', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('check_out', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($q) use ($startOfMonth, $endOfMonth) {
                        $q->where('check_in', '<=', $startOfMonth)
                            ->where('check_out', '>=', $endOfMonth);
                    });
            })
            ->get()
            ->sum(function ($booking) use ($startOfMonth, $endOfMonth) {
                $start = $booking->check_in->copy()->max($startOfMonth);
                $end = $booking->check_out->copy()->min($endOfMonth);

                return $start->diffInDays($end);
            });

        return $totalDays > 0 ? round(($bookedDays / $totalDays) * 100, 2) : 0;
    }

    /**
     * Calculer le temps de réponse moyen
     */
    protected function calculateAverageResponseTime(User $owner, Carbon $date): ?int
    {
        // Trouver les conversations où le propriétaire a répondu
        $responseTimes = DB::table('messages as m1')
            ->join('messages as m2', function ($join) use ($owner) {
                $join->on('m1.conversation_id', '=', 'm2.conversation_id')
                    ->where('m2.sender_id', '=', $owner->id)
                    ->whereRaw('m2.created_at > m1.created_at');
            })
            ->join('conversations', 'conversations.id', '=', 'm1.conversation_id')
            ->whereIn('conversations.residence_id', $owner->residences()->pluck('id'))
            ->where('m1.sender_id', '!=', $owner->id)
            ->whereDate('m1.created_at', $date)
            ->selectRaw('MIN(TIMESTAMPDIFF(MINUTE, m1.created_at, m2.created_at)) as response_time')
            ->groupBy('m1.id')
            ->pluck('response_time');

        if ($responseTimes->isEmpty()) {
            return null;
        }

        return (int) $responseTimes->avg();
    }
}
