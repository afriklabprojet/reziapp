<?php

declare(strict_types=1);

namespace App\Services\Owner;

use App\Models\Booking;
use App\Models\Contact;
use App\Models\OwnerBalance;
use App\Models\Payout;
use App\Models\Review;
use App\Models\Statistic;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Service de calcul des statistiques propriétaire.
 *
 * Extrait du OwnerController pour respecter le principe de responsabilité
 * unique (SRP) : le controller ne gère plus que la logique HTTP.
 */
class OwnerStatsService
{
    /**
     * Statistiques globales de la liste des résidences du propriétaire.
     */
    public function calculateStats(User $user): array
    {
        $residences = $user->residences()
            ->select(['id', 'status', 'is_available', 'views_count', 'contacts_count'])
            ->get();

        return [
            'total_residences'     => $residences->count(),
            'approved_residences'  => $residences->whereIn('status', ['active', 'approved'])->count(),
            'pending_residences'   => $residences->where('status', 'pending')->count(),
            'rejected_residences'  => $residences->where('status', 'rejected')->count(),
            'total_views'          => $residences->sum('views_count'),
            'total_contacts'       => $residences->sum('contacts_count'),
            'pending_contacts'     => Contact::where('owner_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'available_residences' => $residences->whereIn('status', ['active', 'approved'])
                ->where('is_available', true)
                ->count(),
        ];
    }

    /**
     * Revenus du propriétaire (ce mois, mois précédent, total, tendance %).
     *
     * @param  Collection|array  $residenceIds
     */
    public function calculateRevenue(Collection|array $residenceIds): array
    {
        $completedStatuses = ['confirmed', 'completed', 'checked_in', 'checked_out'];

        $thisMonth = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', $completedStatuses)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        $lastMonth = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', $completedStatuses)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total_amount');

        $total = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', $completedStatuses)
            ->sum('total_amount');

        $trend = $lastMonth > 0
            ? (int) round((($thisMonth - $lastMonth) / $lastMonth) * 100)
            : ($thisMonth > 0 ? 100 : 0);

        return [
            'this_month' => (float) $thisMonth,
            'last_month' => (float) $lastMonth,
            'total'      => (float) $total,
            'trend'      => $trend,
        ];
    }

    /**
     * Tendance des vues (% de variation mois courant vs mois précédent).
     *
     * @param  Collection|array|null  $residenceIds
     */
    public function calculateViewsTrend(User $user, Collection|array|null $residenceIds = null): int
    {
        $residenceIds = $residenceIds ?? $user->residences()->pluck('id');

        $currentMonth = Statistic::whereIn('residence_id', $residenceIds)
            ->whereMonth('stat_date', now()->month)
            ->whereYear('stat_date', now()->year)
            ->sum('views');

        $previousMonth = Statistic::whereIn('residence_id', $residenceIds)
            ->whereMonth('stat_date', now()->subMonth()->month)
            ->whereYear('stat_date', now()->subMonth()->year)
            ->sum('views');

        if ($previousMonth == 0) {
            return $currentMonth > 0 ? 100 : 0;
        }

        return (int) round((($currentMonth - $previousMonth) / $previousMonth) * 100);
    }

    /**
     * Données de réservation (check-ins, en cours, à venir, taux d'occupation).
     *
     * @param  Collection|array  $residenceIds
     */
    public function getBookingsData(Collection|array $residenceIds): array
    {
        $today = now()->toDateString();

        $checkingOut = Booking::whereIn('residence_id', $residenceIds)
            ->whereDate('check_out', $today)
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        $currentlyHosting = Booking::whereIn('residence_id', $residenceIds)
            ->where('check_in', '<=', $today)
            ->where('check_out', '>=', $today)
            ->whereIn('status', ['confirmed'])
            ->count();

        $arrivingSoon = Booking::whereIn('residence_id', $residenceIds)
            ->where('check_in', '>', $today)
            ->where('check_in', '<=', now()->addDays(7)->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->count();

        $pendingBookings = Booking::whereIn('residence_id', $residenceIds)
            ->where('status', 'pending')
            ->count();

        $upcomingBookings = Booking::whereIn('residence_id', $residenceIds)
            ->where('check_in', '>=', $today)
            ->whereIn('status', ['confirmed', 'pending'])
            ->with(['user:id,name,phone', 'residence:id,name'])
            ->orderBy('check_in')
            ->take(3)
            ->get();

        $pendingReviews = Booking::whereIn('residence_id', $residenceIds)
            ->where('status', 'completed')
            ->where('check_out', '<', $today)
            ->whereDoesntHave('review')
            ->count();

        // Taux d'occupation ce mois
        $daysInMonth     = (int) now()->daysInMonth;
        $residenceCount  = count($residenceIds);
        $totalNights     = $daysInMonth * $residenceCount;
        $bookedNights    = 0;

        if ($totalNights > 0) {
            $bookedNights = (int) Booking::whereIn('residence_id', $residenceIds)
                ->whereIn('status', ['confirmed', 'completed', 'checked_in', 'checked_out'])
                ->where('check_out', '>=', now()->startOfMonth()->toDateString())
                ->where('check_in', '<=', now()->endOfMonth()->toDateString())
                ->sum(\Illuminate\Support\Facades\DB::raw(
                    'LEAST(DATEDIFF(LEAST(check_out, "'.now()->endOfMonth()->toDateString().'"),
                           GREATEST(check_in, "'.now()->startOfMonth()->toDateString().'"))
                    , '.$daysInMonth.')',
                ));
        }

        $occupancyRate = $totalNights > 0
            ? min(100, (int) round(($bookedNights / $totalNights) * 100))
            : 0;

        return [
            'checking_out'     => $checkingOut,
            'currently_hosting' => $currentlyHosting,
            'arriving_soon'    => $arrivingSoon,
            'pending'          => $pendingBookings,
            'pending_reviews'  => $pendingReviews,
            'upcoming'         => $upcomingBookings,
            'total_active'     => $checkingOut + $currentlyHosting + $arrivingSoon,
            'occupancy_rate'   => $occupancyRate,
        ];
    }

    /**
     * Données financières complètes (solde, versements, revenus mensuels).
     *
     * @param  Collection|array  $residenceIds
     */
    public function getEarningsData(User $user, Collection|array $residenceIds): array
    {
        $balance = OwnerBalance::where('user_id', $user->id)->first();

        $nextPayout = Payout::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('requested_at', 'desc')
            ->first();

        $lastPayout = Payout::where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->first();

        $monthlyRevenue = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', ['confirmed', 'completed', 'checked_in', 'checked_out'])
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        return [
            'available_balance' => $balance?->available_balance ?? 0,
            'pending_balance'   => $balance?->pending_balance ?? 0,
            'total_earned'      => $balance?->total_earned ?? 0,
            'total_withdrawn'   => $balance?->total_withdrawn ?? 0,
            'currency'          => $balance?->currency ?? 'XOF',
            'next_payout'       => $nextPayout,
            'last_payout'       => $lastPayout,
            'monthly_revenue'   => $monthlyRevenue,
        ];
    }

    /**
     * Avis et réputation (note moyenne, notes détaillées, avis sans réponse).
     *
     * @param  Collection|array  $residenceIds
     */
    public function getReviewsData(Collection|array $residenceIds): array
    {
        $reviews      = Review::whereIn('residence_id', $residenceIds)->where('status', 'approved');
        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 2) : 0;

        $detailedRatings = [];
        if ($totalReviews > 0) {
            $avg = Review::whereIn('residence_id', $residenceIds)
                ->where('status', 'approved')
                ->selectRaw('
                    AVG(cleanliness_rating)    as avg_cleanliness,
                    AVG(location_rating)       as avg_location,
                    AVG(value_rating)          as avg_value,
                    AVG(communication_rating)  as avg_communication,
                    AVG(accuracy_rating)       as avg_accuracy,
                    AVG(checkin_rating)        as avg_checkin
                ')
                ->first();

            $detailedRatings = [
                'cleanliness'   => round((float) ($avg->avg_cleanliness ?? 0), 1),
                'location'      => round((float) ($avg->avg_location ?? 0), 1),
                'value'         => round((float) ($avg->avg_value ?? 0), 1),
                'communication' => round((float) ($avg->avg_communication ?? 0), 1),
                'accuracy'      => round((float) ($avg->avg_accuracy ?? 0), 1),
                'checkin'       => round((float) ($avg->avg_checkin ?? 0), 1),
            ];
        }

        $recentReviews = Review::whereIn('residence_id', $residenceIds)
            ->where('status', 'approved')
            ->with(['user:id,name', 'residence:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        $unansweredReviews = Review::whereIn('residence_id', $residenceIds)
            ->where('status', 'approved')
            ->whereNull('owner_response')
            ->count();

        return [
            'total'            => $totalReviews,
            'average_rating'   => $averageRating,
            'detailed_ratings' => $detailedRatings,
            'recent'           => $recentReviews,
            'unanswered'       => $unansweredReviews,
        ];
    }

    /**
     * Métriques de réponse aux contacts (taux et délai moyen).
     */
    public function getResponseMetrics(User $user): array
    {
        $totalContacts     = Contact::where('owner_id', $user->id)->count();
        $respondedContacts = Contact::where('owner_id', $user->id)
            ->where('status', 'responded')
            ->count();

        $responseRate = $totalContacts > 0
            ? round(($respondedContacts / $totalContacts) * 100)
            : 0;

        $avgResponseTime = Contact::where('owner_id', $user->id)
            ->where('status', 'responded')
            ->whereNotNull('responded_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, responded_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'response_rate'     => (int) $responseRate,
            'avg_response_time' => $avgResponseTime ? round((float) $avgResponseTime, 1) : null,
            'total_contacts'    => $totalContacts,
            'responded'         => $respondedContacts,
        ];
    }

    /**
     * Score hôte global (0–100) avec critères détaillés (style Superhost Airbnb).
     */
    public function calculateHostScore(
        User $user,
        array $stats,
        array $reviewsData,
        array $responseMetrics,
    ): array {
        $score    = 0;
        $criteria = [];

        // 1. Taux de réponse (max 25 pts, objectif ≥ 90 %)
        $responseScore = min(25, (int) round(($responseMetrics['response_rate'] / 100) * 25));
        $score        += $responseScore;
        $criteria['response_rate'] = [
            'score' => $responseScore,
            'max'   => 25,
            'label' => 'Taux de réponse',
            'value' => $responseMetrics['response_rate'].'%',
            'target' => '≥ 90%',
            'met'   => $responseMetrics['response_rate'] >= 90,
        ];

        // 2. Note moyenne (max 25 pts, objectif ≥ 4.5)
        $ratingScore = $reviewsData['average_rating'] > 0
            ? min(25, (int) round(($reviewsData['average_rating'] / 5) * 25))
            : 0;
        $score      += $ratingScore;
        $criteria['rating'] = [
            'score' => $ratingScore,
            'max'   => 25,
            'label' => 'Note moyenne',
            'value' => $reviewsData['average_rating'] > 0
                ? $reviewsData['average_rating'].'/5'
                : 'Aucun avis',
            'target' => '≥ 4.5/5',
            'met'   => $reviewsData['average_rating'] >= 4.5,
        ];

        // 3. Qualité des annonces (max 25 pts)
        $listingScore = 0;
        if ($stats['approved_residences'] > 0) {
            $listingScore += 10;
        }
        if ($stats['rejected_residences'] === 0) {
            $listingScore += 10;
        }
        if ($stats['pending_contacts'] === 0) {
            $listingScore += 5;
        }
        $listingScore = min(25, $listingScore);
        $score       += $listingScore;
        $criteria['listings'] = [
            'score' => $listingScore,
            'max'   => 25,
            'label' => 'Qualité annonces',
            'value' => $stats['approved_residences'].' active(s)',
            'target' => 'Annonces approuvées, 0 rejetées',
            'met'   => $listingScore >= 20,
        ];

        // 4. Engagement & activité (max 25 pts)
        $activityScore = 0;
        if ($reviewsData['unanswered'] === 0 && $reviewsData['total'] > 0) {
            $activityScore += 10;
        }
        if ($responseMetrics['avg_response_time'] !== null) {
            if ($responseMetrics['avg_response_time'] <= 2) {
                $activityScore += 10;
            } elseif ($responseMetrics['avg_response_time'] <= 6) {
                $activityScore += 5;
            }
        }
        if ($stats['total_views'] > 50) {
            $activityScore += 5;
        }
        $activityScore = min(25, $activityScore);
        $score        += $activityScore;
        $criteria['activity'] = [
            'score' => $activityScore,
            'max'   => 25,
            'label' => 'Engagement',
            'value' => $responseMetrics['avg_response_time'] !== null
                ? $responseMetrics['avg_response_time'].'h de réponse'
                : 'N/A',
            'target' => 'Réponses < 2h, avis traités',
            'met'   => $activityScore >= 20,
        ];

        $level = match (true) {
            $score >= 85 => ['name' => 'Hôte Premium',  'color' => 'amber', 'icon' => '⭐'],
            $score >= 70 => ['name' => 'Hôte Confirmé', 'color' => 'blue',  'icon' => '🏆'],
            $score >= 50 => ['name' => 'Hôte Actif',    'color' => 'green', 'icon' => '✓'],
            default      => ['name' => 'Nouvel Hôte',   'color' => 'gray',  'icon' => '🏠'],
        };

        return [
            'score'    => $score,
            'level'    => $level,
            'criteria' => $criteria,
        ];
    }

    /**
     * Tâches du jour (style "Aujourd'hui" Airbnb).
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $residences
     */
    public function getTodayTasks(User $user, array $stats, $residences): array
    {
        $tasks        = [];
        $residenceIds = $user->residences()->pluck('id');

        $pendingBookings = Booking::whereIn('residence_id', $residenceIds)
            ->where('status', 'pending')
            ->count();
        if ($pendingBookings > 0) {
            $tasks[] = [
                'icon'        => 'booking',
                'color'       => 'red',
                'title'       => $pendingBookings.' réservation'.($pendingBookings > 1 ? 's' : '').' à confirmer',
                'description' => 'Confirmez avant l\'expiration du délai',
                'action_url'  => route('owner.bookings.index', ['status' => 'pending']),
                'action_text' => 'Confirmer',
                'urgent'      => true,
            ];
        }

        $todayCheckins = Booking::whereIn('residence_id', $residenceIds)
            ->whereDate('check_in', now()->toDateString())
            ->where('status', 'confirmed')
            ->count();
        if ($todayCheckins > 0) {
            $tasks[] = [
                'icon'        => 'checkin',
                'color'       => 'green',
                'title'       => $todayCheckins.' arrivée'.($todayCheckins > 1 ? 's' : '').' aujourd\'hui',
                'description' => 'Préparez l\'accueil de vos locataires',
                'action_url'  => route('owner.bookings.index'),
                'action_text' => 'Voir',
                'urgent'      => true,
            ];
        }

        if ($stats['pending_contacts'] > 0) {
            $n = $stats['pending_contacts'];
            $tasks[] = [
                'icon'        => 'mail',
                'color'       => 'red',
                'title'       => $n.' contact'.($n > 1 ? 's' : '').' en attente',
                'description' => 'Répondez pour augmenter vos conversions',
                'action_url'  => route('owner.contacts.index', ['status' => 'pending']),
                'action_text' => 'Répondre',
                'urgent'      => true,
            ];
        }

        if ($stats['pending_residences'] > 0) {
            $n = $stats['pending_residences'];
            $tasks[] = [
                'icon'        => 'clock',
                'color'       => 'yellow',
                'title'       => $n.' annonce'.($n > 1 ? 's' : '').' en attente de validation',
                'description' => 'L\'équipe Rezi Studio Meublé Faya examine votre annonce',
                'action_url'  => route('owner.residences.index'),
                'action_text' => 'Voir',
                'urgent'      => false,
            ];
        }

        $noPhotos = $residences->filter(fn ($r) => $r->photos->isEmpty())->count();
        if ($noPhotos > 0) {
            $tasks[] = [
                'icon'        => 'camera',
                'color'       => 'orange',
                'title'       => $noPhotos.' annonce'.($noPhotos > 1 ? 's' : '').' sans photo',
                'description' => 'Les annonces avec photos reçoivent 5x plus de contacts',
                'action_url'  => route('owner.residences.index'),
                'action_text' => 'Ajouter',
                'urgent'      => false,
            ];
        }

        if ($stats['rejected_residences'] > 0) {
            $n = $stats['rejected_residences'];
            $tasks[] = [
                'icon'        => 'alert',
                'color'       => 'red',
                'title'       => $n.' annonce'.($n > 1 ? 's' : '').' rejetée'.($n > 1 ? 's' : ''),
                'description' => 'Modifiez-les pour les soumettre à nouveau',
                'action_url'  => route('owner.residences.index'),
                'action_text' => 'Corriger',
                'urgent'      => true,
            ];
        }

        $unansweredReviews = Review::whereIn('residence_id', $residenceIds)
            ->whereNull('owner_response')
            ->where('status', 'approved')
            ->count();
        if ($unansweredReviews > 0) {
            $tasks[] = [
                'icon'        => 'star',
                'color'       => 'yellow',
                'title'       => $unansweredReviews.' avis sans réponse',
                'description' => 'Répondre aux avis renforce votre crédibilité',
                'action_url'  => route('owner.received-reviews.index'),
                'action_text' => 'Répondre',
                'urgent'      => false,
            ];
        }

        if (empty($tasks)) {
            $tasks[] = [
                'icon'        => 'check',
                'color'       => 'green',
                'title'       => 'Tout est en ordre !',
                'description' => 'Vous n\'avez aucune action en attente',
                'action_url'  => null,
                'action_text' => null,
                'urgent'      => false,
            ];
        }

        return $tasks;
    }
}
