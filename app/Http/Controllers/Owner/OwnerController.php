<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\IdentityVerification;
use App\Models\OwnerBalance;
use App\Models\Payout;
use App\Models\Review;
use App\Models\Statistic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Controller pour le dashboard propriétaire
 */
class OwnerController extends Controller
{
    /**
     * Dashboard propriétaire avec statistiques complètes
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $cacheKey = "owner_dashboard:{$user->id}";
        $cacheTtl = 300; // 5 minutes

        // Mes résidences (les 5 dernières) — toujours frais
        $residences = $user->residences()
            ->with(['photos', 'amenities'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // ── Données lourdes cachées 5 min ──
        $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($user) {
            $residenceIds = $user->residences()->pluck('id');

            $stats = $this->calculateStats($user);
            $revenueData = $this->calculateRevenue($residenceIds);
            $viewsTrend = $this->calculateViewsTrend($user, $residenceIds);
            $bookingsData = $this->getBookingsData($residenceIds);
            $earningsData = $this->getEarningsData($user, $residenceIds);
            $reviewsData = $this->getReviewsData($residenceIds);
            $responseMetrics = $this->getResponseMetrics($user);
            $hostScore = $this->calculateHostScore($user, $stats, $reviewsData, $responseMetrics);

            // Stats journalières sur 30 jours
            $dailyStats = Statistic::whereIn('residence_id', $residenceIds)
                ->where('stat_date', '>=', now()->subDays(30))
                ->selectRaw('stat_date, SUM(views) as views, SUM(contacts) as contacts')
                ->groupBy('stat_date')
                ->orderBy('stat_date')
                ->get()
                ->keyBy(fn ($item) => $item->stat_date->format('Y-m-d'));

            $chartData = collect();
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $dayData = $dailyStats->get($date);
                $chartData->push([
                    'date' => $date,
                    'label' => now()->subDays($i)->format('d/m'),
                    'views' => $dayData ? (int) $dayData->views : 0,
                    'contacts' => $dayData ? (int) $dayData->contacts : 0,
                ]);
            }

            // Distribution des étoiles
            $starDistribution = $reviewsData['total'] > 0
                ? Review::whereIn('residence_id', $residenceIds)
                    ->where('status', 'approved')
                    ->selectRaw('rating, COUNT(*) as count')
                    ->groupBy('rating')
                    ->orderBy('rating', 'desc')
                    ->pluck('count', 'rating')
                    ->toArray()
                : [];

            return compact(
                'stats', 'revenueData', 'viewsTrend', 'bookingsData',
                'earningsData', 'reviewsData', 'responseMetrics', 'hostScore',
                'chartData', 'starDistribution', 'residenceIds',
            );
        });

        // Extraire les données cachées
        extract($cached);

        // ── Données temps-réel (non cachées) ──
        $recentContacts = Contact::where('owner_id', $user->id)
            ->with(['user:id,name,email,phone', 'residence:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $conversionRate = $stats['total_views'] > 0
            ? round(($stats['total_contacts'] / $stats['total_views']) * 100, 1)
            : 0;

        $todayTasks = $this->getTodayTasks($user, $stats, $residences);

        $hour = (int) now()->format('H');
        $greeting = match (true) {
            $hour < 12 => 'Bonjour',
            $hour < 18 => 'Bon après-midi',
            default => 'Bonsoir',
        };

        $unreadMessages = Conversation::where(function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->sum('unread_owner_count');

        $identityVerification = IdentityVerification::where('user_id', $user->id)
            ->latest()
            ->first();
        $verificationStatus = $identityVerification?->status;

        $recentMessages = Conversation::where('owner_id', $user->id)
            ->whereHas('messages')
            ->with(['user:id,name,profile_photo,avatar', 'residence:id,name', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderBy('last_message_at', 'desc')
            ->take(3)
            ->get();

        $calendarEvents = Booking::whereIn('residence_id', $residenceIds)
            ->where(function ($q) {
                $q->whereBetween('check_in', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->orWhereBetween('check_out', [now()->toDateString(), now()->addDays(7)->toDateString()]);
            })
            ->whereIn('status', ['confirmed', 'pending'])
            ->with(['user:id,name', 'residence:id,name'])
            ->orderBy('check_in')
            ->take(5)
            ->get();

        return view('owner.dashboard', compact(
            'residences',
            'stats',
            'recentContacts',
            'conversionRate',
            'viewsTrend',
            'revenueData',
            'chartData',
            'todayTasks',
            'greeting',
            'bookingsData',
            'earningsData',
            'reviewsData',
            'responseMetrics',
            'hostScore',
            'unreadMessages',
            'verificationStatus',
            'recentMessages',
            'calendarEvents',
            'starDistribution',
        ));
    }

    /**
     * Calculer les revenus du propriétaire
     */
    private function calculateRevenue($residenceIds): array
    {
        $completedStatuses = ['confirmed', 'completed', 'checked_in', 'checked_out'];

        // Revenus ce mois-ci
        $thisMonth = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', $completedStatuses)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        // Revenus mois précédent
        $lastMonth = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', $completedStatuses)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total_amount');

        // Revenus total
        $total = Booking::whereIn('residence_id', $residenceIds)
            ->whereIn('status', $completedStatuses)
            ->sum('total_amount');

        // Tendance
        $trend = $lastMonth > 0
            ? (int) round((($thisMonth - $lastMonth) / $lastMonth) * 100)
            : ($thisMonth > 0 ? 100 : 0);

        return [
            'this_month' => (float) $thisMonth,
            'last_month' => (float) $lastMonth,
            'total' => (float) $total,
            'trend' => $trend,
        ];
    }

    /**
     * Tâches du jour (style Airbnb "Aujourd'hui")
     */
    private function getTodayTasks($user, array $stats, $residences): array
    {
        $tasks = [];
        $residenceIds = $user->residences()->pluck('id');

        // Réservations en attente de confirmation
        $pendingBookings = Booking::whereIn('residence_id', $residenceIds)
            ->where('status', 'pending')
            ->count();
        if ($pendingBookings > 0) {
            $tasks[] = [
                'icon' => 'booking',
                'color' => 'red',
                'title' => $pendingBookings . ' réservation' . ($pendingBookings > 1 ? 's' : '') . ' à confirmer',
                'description' => 'Confirmez avant l\'expiration du délai',
                'action_url' => route('owner.bookings.index', ['status' => 'pending']),
                'action_text' => 'Confirmer',
                'urgent' => true,
            ];
        }

        // Check-ins aujourd'hui
        $todayCheckins = Booking::whereIn('residence_id', $residenceIds)
            ->whereDate('check_in', now()->toDateString())
            ->where('status', 'confirmed')
            ->count();
        if ($todayCheckins > 0) {
            $tasks[] = [
                'icon' => 'checkin',
                'color' => 'green',
                'title' => $todayCheckins . ' arrivée' . ($todayCheckins > 1 ? 's' : '') . ' aujourd\'hui',
                'description' => 'Préparez l\'accueil de vos locataires',
                'action_url' => route('owner.bookings.index'),
                'action_text' => 'Voir',
                'urgent' => true,
            ];
        }

        // Contacts en attente
        if ($stats['pending_contacts'] > 0) {
            $tasks[] = [
                'icon' => 'mail',
                'color' => 'red',
                'title' => $stats['pending_contacts'] . ' contact' . ($stats['pending_contacts'] > 1 ? 's' : '') . ' en attente',
                'description' => 'Répondez pour augmenter vos conversions',
                'action_url' => route('owner.contacts.index', ['status' => 'pending']),
                'action_text' => 'Répondre',
                'urgent' => true,
            ];
        }

        // Résidences en attente d'approbation
        if ($stats['pending_residences'] > 0) {
            $tasks[] = [
                'icon' => 'clock',
                'color' => 'yellow',
                'title' => $stats['pending_residences'] . ' annonce' . ($stats['pending_residences'] > 1 ? 's' : '') . ' en attente de validation',
                'description' => 'L\'équipe REZI examine votre annonce',
                'action_url' => route('owner.residences.index'),
                'action_text' => 'Voir',
                'urgent' => false,
            ];
        }

        // Annonces sans photos
        $noPhotos = $residences->filter(fn ($r) => $r->photos->isEmpty())->count();
        if ($noPhotos > 0) {
            $tasks[] = [
                'icon' => 'camera',
                'color' => 'orange',
                'title' => $noPhotos . ' annonce' . ($noPhotos > 1 ? 's' : '') . ' sans photo',
                'description' => 'Les annonces avec photos reçoivent 5x plus de contacts',
                'action_url' => route('owner.residences.index'),
                'action_text' => 'Ajouter',
                'urgent' => false,
            ];
        }

        // Résidences rejetées
        if ($stats['rejected_residences'] > 0) {
            $tasks[] = [
                'icon' => 'alert',
                'color' => 'red',
                'title' => $stats['rejected_residences'] . ' annonce' . ($stats['rejected_residences'] > 1 ? 's' : '') . ' rejetée' . ($stats['rejected_residences'] > 1 ? 's' : ''),
                'description' => 'Modifiez-les pour les soumettre à nouveau',
                'action_url' => route('owner.residences.index'),
                'action_text' => 'Corriger',
                'urgent' => true,
            ];
        }

        // Si aucune tâche = tout va bien
        if (empty($tasks)) {
            $tasks[] = [
                'icon' => 'check',
                'color' => 'green',
                'title' => 'Tout est en ordre !',
                'description' => 'Vous n\'avez aucune action en attente',
                'action_url' => null,
                'action_text' => null,
                'urgent' => false,
            ];
        }

        return $tasks;
    }

    /**
     * Calculer les statistiques globales
     */
    private function calculateStats($user): array
    {
        // Charger toutes les résidences une seule fois (1 requête au lieu de 8)
        $residences = $user->residences()
            ->select(['id', 'status', 'is_available', 'views_count', 'contacts_count'])
            ->get();

        return [
            'total_residences' => $residences->count(),
            'approved_residences' => $residences->whereIn('status', ['active', 'approved'])->count(),
            'pending_residences' => $residences->where('status', 'pending')->count(),
            'rejected_residences' => $residences->where('status', 'rejected')->count(),
            'total_views' => $residences->sum('views_count'),
            'total_contacts' => $residences->sum('contacts_count'),
            'pending_contacts' => Contact::where('owner_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'available_residences' => $residences->whereIn('status', ['active', 'approved'])->where('is_available', true)->count(),
        ];
    }

    /**
     * Calculer la tendance des vues (% de changement)
     */
    private function calculateViewsTrend($user, $residenceIds = null): int
    {
        $residenceIds = $residenceIds ?? $user->residences()->pluck('id');

        // Vues du mois actuel
        $currentMonth = Statistic::whereIn('residence_id', $residenceIds)
            ->whereMonth('stat_date', now()->month)
            ->whereYear('stat_date', now()->year)
            ->sum('views');

        // Vues du mois précédent
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
     * Données de réservation style Airbnb
     * Check-ins aujourd'hui, en cours, à venir, en attente
     */
    private function getBookingsData($residenceIds): array
    {
        $today = now()->toDateString();

        // Check-outs aujourd'hui
        $checkingOut = Booking::whereIn('residence_id', $residenceIds)
            ->whereDate('check_out', $today)
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        // Actuellement hébergés (en cours de séjour)
        $currentlyHosting = Booking::whereIn('residence_id', $residenceIds)
            ->where('check_in', '<=', $today)
            ->where('check_out', '>=', $today)
            ->whereIn('status', ['confirmed'])
            ->count();

        // Arrivées bientôt (7 prochains jours)
        $arrivingSoon = Booking::whereIn('residence_id', $residenceIds)
            ->where('check_in', '>', $today)
            ->where('check_in', '<=', now()->addDays(7)->toDateString())
            ->whereIn('status', ['confirmed', 'pending'])
            ->count();

        // En attente de confirmation
        $pendingBookings = Booking::whereIn('residence_id', $residenceIds)
            ->where('status', 'pending')
            ->count();

        // Prochaines réservations (3 max pour l'affichage)
        $upcomingBookings = Booking::whereIn('residence_id', $residenceIds)
            ->where('check_in', '>=', $today)
            ->whereIn('status', ['confirmed', 'pending'])
            ->with(['user:id,name,phone', 'residence:id,name'])
            ->orderBy('check_in')
            ->take(3)
            ->get();

        // Réservations à évaluer (terminées sans avis)
        $pendingReviews = Booking::whereIn('residence_id', $residenceIds)
            ->where('status', 'completed')
            ->where('check_out', '<', $today)
            ->whereDoesntHave('review')
            ->count();

        return [
            'checking_out' => $checkingOut,
            'currently_hosting' => $currentlyHosting,
            'arriving_soon' => $arrivingSoon,
            'pending' => $pendingBookings,
            'pending_reviews' => $pendingReviews,
            'upcoming' => $upcomingBookings,
            'total_active' => $checkingOut + $currentlyHosting + $arrivingSoon,
        ];
    }

    /**
     * Données financières complètes (style Airbnb Earnings)
     */
    private function getEarningsData($user, $residenceIds): array
    {
        // Solde propriétaire
        $balance = OwnerBalance::where('user_id', $user->id)->first();

        // Prochain versement prévu
        $nextPayout = Payout::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('requested_at', 'desc')
            ->first();

        // Dernier versement complété
        $lastPayout = Payout::where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->first();

        // Revenus par mois (6 derniers mois)
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
            'pending_balance' => $balance?->pending_balance ?? 0,
            'total_earned' => $balance?->total_earned ?? 0,
            'total_withdrawn' => $balance?->total_withdrawn ?? 0,
            'currency' => $balance?->currency ?? 'XOF',
            'next_payout' => $nextPayout,
            'last_payout' => $lastPayout,
            'monthly_revenue' => $monthlyRevenue,
        ];
    }

    /**
     * Données avis & réputation (style Airbnb Reviews)
     */
    private function getReviewsData($residenceIds): array
    {
        // Avis globaux
        $reviews = Review::whereIn('residence_id', $residenceIds)
            ->where('status', 'approved');

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 2) : 0;

        // Notes détaillées (1 seule requête au lieu de 6)
        $detailedRatings = $totalReviews > 0 ? (function () use ($residenceIds) {
            $avg = Review::whereIn('residence_id', $residenceIds)
                ->where('status', 'approved')
                ->selectRaw('
                    AVG(cleanliness_rating) as avg_cleanliness,
                    AVG(location_rating) as avg_location,
                    AVG(value_rating) as avg_value,
                    AVG(communication_rating) as avg_communication,
                    AVG(accuracy_rating) as avg_accuracy,
                    AVG(checkin_rating) as avg_checkin
                ')
                ->first();
            return [
                'cleanliness' => round((float) ($avg->avg_cleanliness ?? 0), 1),
                'location' => round((float) ($avg->avg_location ?? 0), 1),
                'value' => round((float) ($avg->avg_value ?? 0), 1),
                'communication' => round((float) ($avg->avg_communication ?? 0), 1),
                'accuracy' => round((float) ($avg->avg_accuracy ?? 0), 1),
                'checkin' => round((float) ($avg->avg_checkin ?? 0), 1),
            ];
        })() : [];

        // Avis récents (3 derniers)
        $recentReviews = Review::whereIn('residence_id', $residenceIds)
            ->where('status', 'approved')
            ->with(['user:id,name', 'residence:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        // Avis sans réponse du propriétaire
        $unansweredReviews = Review::whereIn('residence_id', $residenceIds)
            ->where('status', 'approved')
            ->whereNull('owner_response')
            ->count();

        return [
            'total' => $totalReviews,
            'average_rating' => $averageRating,
            'detailed_ratings' => $detailedRatings,
            'recent' => $recentReviews,
            'unanswered' => $unansweredReviews,
        ];
    }

    /**
     * Métriques de réponse (taux + temps moyen)
     */
    private function getResponseMetrics($user): array
    {
        $totalContacts = Contact::where('owner_id', $user->id)->count();
        $respondedContacts = Contact::where('owner_id', $user->id)
            ->where('status', 'responded')
            ->count();

        $responseRate = $totalContacts > 0
            ? round(($respondedContacts / $totalContacts) * 100)
            : 0;

        // Temps de réponse moyen (en heures)
        $avgResponseTime = Contact::where('owner_id', $user->id)
            ->where('status', 'responded')
            ->whereNotNull('responded_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, responded_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'response_rate' => (int) $responseRate,
            'avg_response_time' => $avgResponseTime ? round((float) $avgResponseTime, 1) : null,
            'total_contacts' => $totalContacts,
            'responded' => $respondedContacts,
        ];
    }

    /**
     * Calculer le score hôte (style Superhost Airbnb)
     * Score sur 100 basé sur : taux de réponse, avis, complétude, activité
     */
    private function calculateHostScore($user, array $stats, array $reviewsData, array $responseMetrics): array
    {
        $score = 0;
        $criteria = [];

        // 1. Taux de réponse (max 25 points) — objectif > 90%
        $responseScore = min(25, (int) round(($responseMetrics['response_rate'] / 100) * 25));
        $score += $responseScore;
        $criteria['response_rate'] = [
            'score' => $responseScore,
            'max' => 25,
            'label' => 'Taux de réponse',
            'value' => $responseMetrics['response_rate'] . '%',
            'target' => '≥ 90%',
            'met' => $responseMetrics['response_rate'] >= 90,
        ];

        // 2. Note moyenne (max 25 points) — objectif ≥ 4.5
        $ratingScore = $reviewsData['average_rating'] > 0
            ? min(25, (int) round(($reviewsData['average_rating'] / 5) * 25))
            : 0;
        $score += $ratingScore;
        $criteria['rating'] = [
            'score' => $ratingScore,
            'max' => 25,
            'label' => 'Note moyenne',
            'value' => $reviewsData['average_rating'] > 0 ? $reviewsData['average_rating'] . '/5' : 'Aucun avis',
            'target' => '≥ 4.5/5',
            'met' => $reviewsData['average_rating'] >= 4.5,
        ];

        // 3. Annonces actives et qualité (max 25 points)
        $listingScore = 0;
        if ($stats['approved_residences'] > 0) {
            $listingScore += 10; // Au moins 1 annonce active
        }
        if ($stats['total_residences'] > 0 && $stats['rejected_residences'] === 0) {
            $listingScore += 10; // Aucune annonce rejetée
        }
        if ($stats['pending_contacts'] === 0) {
            $listingScore += 5; // Tous les contacts traités
        }
        $listingScore = min(25, $listingScore);
        $score += $listingScore;
        $criteria['listings'] = [
            'score' => $listingScore,
            'max' => 25,
            'label' => 'Qualité annonces',
            'value' => $stats['approved_residences'] . ' active(s)',
            'target' => 'Annonces approuvées, 0 rejetées',
            'met' => $listingScore >= 20,
        ];

        // 4. Activité & Engagement (max 25 points)
        $activityScore = 0;
        if ($reviewsData['unanswered'] === 0 && $reviewsData['total'] > 0) {
            $activityScore += 10; // Tous les avis répondus
        }
        if ($responseMetrics['avg_response_time'] !== null && $responseMetrics['avg_response_time'] <= 2) {
            $activityScore += 10; // Temps de réponse < 2h
        } elseif ($responseMetrics['avg_response_time'] !== null && $responseMetrics['avg_response_time'] <= 6) {
            $activityScore += 5;
        }
        if ($stats['total_views'] > 50) {
            $activityScore += 5; // Visibilité
        }
        $activityScore = min(25, $activityScore);
        $score += $activityScore;
        $criteria['activity'] = [
            'score' => $activityScore,
            'max' => 25,
            'label' => 'Engagement',
            'value' => $responseMetrics['avg_response_time'] !== null
                ? $responseMetrics['avg_response_time'] . 'h de réponse'
                : 'N/A',
            'target' => 'Réponses < 2h, avis traités',
            'met' => $activityScore >= 20,
        ];

        // Niveau basé sur le score
        $level = match (true) {
            $score >= 85 => ['name' => 'Hôte Premium', 'color' => 'amber', 'icon' => '⭐'],
            $score >= 70 => ['name' => 'Hôte Confirmé', 'color' => 'blue', 'icon' => '🏆'],
            $score >= 50 => ['name' => 'Hôte Actif', 'color' => 'green', 'icon' => '✓'],
            default => ['name' => 'Nouvel Hôte', 'color' => 'gray', 'icon' => '🏠'],
        };

        return [
            'score' => $score,
            'level' => $level,
            'criteria' => $criteria,
        ];
    }

    /**
     * Page statistiques détaillées
     */
    public function statistics(Request $request): View
    {
        $user = $request->user();

        // Statistiques par résidence (1 requête)
        $residenceStats = $user->residences()
            ->select(['id', 'name', 'commune', 'views_count', 'contacts_count', 'status', 'price_per_month', 'price_per_day'])
            ->orderBy('views_count', 'desc')
            ->get()
            ->map(function ($residence) {
                $residence->conversion_rate = $residence->views_count > 0
                    ? round(($residence->contacts_count / $residence->views_count) * 100, 1)
                    : 0;

                return $residence;
            });

        // IDs réutilisables (évite de re-requêter)
        $residenceIds = $residenceStats->pluck('id');

        // Évolution sur les 30 derniers jours
        $dailyStats = Statistic::whereIn('residence_id', $residenceIds)
            ->where('stat_date', '>=', now()->subDays(30))
            ->selectRaw('stat_date, SUM(views) as views, SUM(contacts) as contacts')
            ->groupBy('stat_date')
            ->orderBy('stat_date')
            ->get();

        // Statistiques par commune
        $communeStats = $user->residences()
            ->selectRaw('commune, COUNT(*) as count, SUM(views_count) as views, SUM(contacts_count) as contacts')
            ->groupBy('commune')
            ->orderBy('views', 'desc')
            ->get();

        // Statistiques globales (calculées in-memory depuis residenceStats)
        $viewsThisMonth = Statistic::whereIn('residence_id', $residenceIds)
            ->whereMonth('stat_date', now()->month)
            ->whereYear('stat_date', now()->year)
            ->sum('views');

        $contactsThisMonth = Statistic::whereIn('residence_id', $residenceIds)
            ->whereMonth('stat_date', now()->month)
            ->whereYear('stat_date', now()->year)
            ->sum('contacts');

        // Mois précédent pour comparaison
        $viewsLastMonth = Statistic::whereIn('residence_id', $residenceIds)
            ->whereMonth('stat_date', now()->subMonth()->month)
            ->whereYear('stat_date', now()->subMonth()->year)
            ->sum('views');

        $globalStats = [
            'total_views' => $residenceStats->sum('views_count'),
            'total_contacts' => $residenceStats->sum('contacts_count'),
            'avg_conversion' => $residenceStats->avg('conversion_rate') ?? 0,
            'best_residence' => $residenceStats->first(),
            'views_this_month' => $viewsThisMonth,
            'contacts_this_month' => $contactsThisMonth,
            'views_change' => $viewsLastMonth > 0
                ? round((($viewsThisMonth - $viewsLastMonth) / $viewsLastMonth) * 100)
                : ($viewsThisMonth > 0 ? 100 : 0),
            'total_residences' => $residenceStats->count(),
            'active_residences' => $residenceStats->whereIn('status', ['active', 'approved'])->count(),
        ];

        // Top 5 jours avec le plus de vues
        $topDays = Statistic::whereIn('residence_id', $residenceIds)
            ->selectRaw('stat_date, SUM(views) as total_views')
            ->groupBy('stat_date')
            ->orderBy('total_views', 'desc')
            ->limit(5)
            ->get();

        return view('owner.statistics', compact('residenceStats', 'dailyStats', 'communeStats', 'globalStats', 'topDays'));
    }

    /**
     * Liste des contacts
     */
    public function contacts(Request $request): View
    {
        $user = $request->user();

        $status = $request->get('status');

        $query = Contact::where('owner_id', $user->id)
            ->with(['user:id,name,email,phone,created_at', 'residence:id,name,commune,slug'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        // Recherche par nom ou résidence
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('residence', fn ($r) => $r->where('name', 'like', "%{$search}%"))
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate(20)->withQueryString();

        // Stats pour les filtres (1 requête agrégée)
        $rawContactStats = Contact::where('owner_id', $user->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'viewed' THEN 1 ELSE 0 END) as viewed")
            ->selectRaw("SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded")
            ->first();

        $contactStats = [
            'all' => (int) $rawContactStats->total,
            'pending' => (int) $rawContactStats->pending,
            'viewed' => (int) $rawContactStats->viewed,
            'responded' => (int) $rawContactStats->responded,
        ];

        // KPIs supplémentaires
        $responseRate = $contactStats['all'] > 0
            ? round(($contactStats['responded'] / $contactStats['all']) * 100)
            : 0;

        // Temps de réponse moyen (pour les contacts répondus)
        $avgResponseTime = Contact::where('owner_id', $user->id)
            ->where('status', 'responded')
            ->whereNotNull('responded_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, responded_at)) as avg_hours')
            ->value('avg_hours');
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 1) : null;

        // Contacts aujourd'hui
        $todayCount = Contact::where('owner_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        return view('owner.contacts.index', compact(
            'contacts',
            'contactStats',
            'status',
            'responseRate',
            'avgResponseTime',
            'todayCount',
        ));
    }

    /**
     * Marquer un contact comme répondu
     */
    public function markContactAsResponded(Request $request, Contact $contact)
    {
        // Vérifier que le contact appartient au propriétaire
        if ($contact->owner_id !== $request->user()->id) {
            abort(403);
        }

        $contact->markAsResponded();

        return back()->with('success', 'Contact marqué comme répondu');
    }

    /**
     * Notifications du propriétaire (enrichi)
     */
    public function notifications(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('type', 'all');

        $notifications = collect();

        // Contacts en attente
        $pendingContacts = Contact::where('owner_id', $user->id)
            ->where('status', 'pending')
            ->with(['residence:id,name', 'user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($contact) {
                return [
                    'type' => 'contact',
                    'title' => 'Nouvelle demande de contact',
                    'message' => ($contact->user->name ?? 'Un visiteur') . " souhaite vous contacter pour {$contact->residence->name}",
                    'action_url' => route('owner.contacts.index', ['status' => 'pending']),
                    'action_text' => 'Voir le contact',
                    'created_at' => $contact->created_at,
                    'is_new' => true,
                ];
            });
        $notifications = $notifications->merge($pendingContacts);

        // Réservations récentes (pending / confirmed ces 7 derniers jours)
        $recentBookings = \App\Models\Booking::whereHas('residence', fn($q) => $q->where('owner_id', $user->id))
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('created_at', '>=', now()->subDays(7))
            ->with(['user:id,name,first_name', 'residence:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($booking) {
                $isPending = $booking->status === 'pending';
                return [
                    'type' => 'booking',
                    'title' => $isPending ? 'Nouvelle réservation' : 'Réservation confirmée',
                    'message' => ($booking->user->name ?? 'Un voyageur') . ' — ' . ($booking->residence->name ?? 'Résidence')
                        . ' · ' . number_format($booking->total_amount, 0, ',', ' ') . ' FCFA',
                    'action_url' => route('owner.bookings.show', $booking),
                    'action_text' => 'Voir la réservation',
                    'created_at' => $booking->created_at,
                    'is_new' => $isPending,
                ];
            });
        $notifications = $notifications->merge($recentBookings);

        // Résidences approuvées récemment
        $recentlyApproved = $user->residences()
            ->whereIn('status', ['active', 'approved'])
            ->where('updated_at', '>=', now()->subDays(7))
            ->get()
            ->map(function ($residence) {
                return [
                    'type' => 'approval',
                    'title' => 'Annonce approuvée',
                    'message' => "Votre annonce \"{$residence->name}\" est maintenant visible sur REZI",
                    'action_url' => route('residences.show', $residence),
                    'action_text' => "Voir l'annonce",
                    'created_at' => $residence->updated_at,
                    'is_new' => $residence->updated_at >= now()->subDay(),
                ];
            });
        $notifications = $notifications->merge($recentlyApproved);

        // Résidences rejetées
        $rejected = $user->residences()
            ->where('status', 'rejected')
            ->where('updated_at', '>=', now()->subDays(7))
            ->get()
            ->map(function ($residence) {
                return [
                    'type' => 'rejection',
                    'title' => 'Annonce rejetée',
                    'message' => "Votre annonce \"{$residence->name}\" n'a pas été approuvée. Modifiez-la pour la resoumettre.",
                    'action_url' => route('owner.residences.edit', $residence),
                    'action_text' => "Modifier l'annonce",
                    'created_at' => $residence->updated_at,
                    'is_new' => true,
                ];
            });
        $notifications = $notifications->merge($rejected);

        // Trier par date
        $notifications = $notifications->sortByDesc('created_at')->values();

        // Compteurs par type
        $counts = [
            'all' => $notifications->count(),
            'contact' => $notifications->where('type', 'contact')->count(),
            'booking' => $notifications->where('type', 'booking')->count(),
            'approval' => $notifications->where('type', 'approval')->count(),
            'rejection' => $notifications->where('type', 'rejection')->count(),
        ];

        // Filtrer par type
        if ($filter !== 'all') {
            $notifications = $notifications->where('type', $filter)->values();
        }

        $newCount = $notifications->where('is_new', true)->count();

        return view('owner.notifications', compact('notifications', 'filter', 'counts', 'newCount'));
    }
}
