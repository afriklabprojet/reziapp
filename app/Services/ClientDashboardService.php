<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\Residence;
use App\Models\ResidenceView;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClientDashboardService
{
    /**
     * Durée du cache pour les requêtes géolocalisées (en secondes).
     */
    private const GEO_CACHE_TTL = 600; // 10 minutes

    public function __construct(
        private readonly ResidenceMatchingService $matching,
    ) {
    }

    /**
     * Assembler toutes les données nécessaires au dashboard client.
     */
    public function getDashboardData(User $user): array
    {
        $upcomingBookings = $this->getUpcomingBookings($user);
        $ongoingBooking   = $this->getOngoingBooking($user);
        $heroBooking      = $this->getHeroBooking($user);

        return [
            'stats'                   => $this->getStats($user, $upcomingBookings),
            'upcomingBookings'        => $upcomingBookings,
            'ongoingBooking'          => $ongoingBooking,
            'heroBooking'             => $heroBooking,
            'recentCompletedBookings' => $this->getRecentCompletedBookings($user),
            'recentViews'             => $this->getRecentViews($user),
            'recentConversations'     => $this->getRecentConversations($user),
            'recentSearches'          => $this->getRecentSearches($user),
            'recommendations'         => $this->getRecommendations($user, 6),
            'pendingContacts'         => $this->getPendingContacts($user),
            'newInFavoriteAreas'      => $this->getNewInFavoriteAreas($user, 4),
            'profileCompletion'       => $this->getProfileCompletion($user),
            'activityStats'           => $this->getActivityStats($user),
            'reviewsAwaitingFeedback' => $this->getReviewsAwaitingFeedback($user),
            'isTrustedTenant'         => $this->isTrustedTenant($user),
            'lastSearch'              => $this->getRecentSearches($user, 1)->first(),
        ];
    }

    // ─── Bookings ─────────────────────────────────────────────

    public function getUpcomingBookings(User $user, int $limit = 3): Collection
    {
        return $user->bookings()
            ->upcoming()
            ->with(['residence.photos'])
            ->orderBy('check_in')
            ->take($limit)
            ->get();
    }

    public function getOngoingBooking(User $user)
    {
        return $user->bookings()
            ->ongoing()
            ->with(['residence.photos'])
            ->first();
    }

    public function getRecentCompletedBookings(User $user, int $limit = 3): Collection
    {
        return $user->bookings()
            ->completed()
            ->with(['residence.photos', 'review'])
            ->orderBy('check_out', 'desc')
            ->take($limit)
            ->get();
    }

    // ─── Stats ────────────────────────────────────────────────

    public function getStats(User $user, ?Collection $upcomingBookings = null): array
    {
        return [
            'bookings_upcoming' => ($upcomingBookings ?? $this->getUpcomingBookings($user))->count(),
            'favorites_count'      => $user->favorites()->count(),
            'messages_unread'      => $user->unreadMessagesCount(),
            'notifications_unread' => $user->unreadNotifications()->count(),
            'views_count'          => $user->residenceViews()->count(),
            'reviews_count'        => $user->reviews()->count(),
            'alerts_count'         => $user->savedSearches()->count(),
        ];
    }

    // ─── Recent activity ──────────────────────────────────────

    public function getRecentViews(User $user, int $limit = 6): Collection
    {
        return ResidenceView::where('user_id', $user->id)
            ->with(['residence.photos'])
            ->select('residence_id', DB::raw('MAX(created_at) as last_viewed'))
            ->groupBy('residence_id')
            ->orderBy('last_viewed', 'desc')
            ->take($limit)
            ->get();
    }

    public function getRecentConversations(User $user, int $limit = 3): Collection
    {
        return Conversation::where('user_id', $user->id)
            ->with(['residence', 'owner', 'messages' => fn ($q) => $q->latest()->take(1)])
            ->orderBy('last_message_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function getRecentSearches(User $user, int $limit = 5): Collection
    {
        return $user->searchHistories()
            ->latest()
            ->take($limit)
            ->get();
    }

    public function getPendingContacts(User $user, int $limit = 3): Collection
    {
        return $user->sentContacts()
            ->where('status', 'pending')
            ->with(['residence', 'owner'])
            ->latest()
            ->take($limit)
            ->get();
    }

    // ─── Recommendations (moteur IA — délégué à ResidenceMatchingService) ─────

    public function getRecommendations(User $user, int $limit = 6): Collection
    {
        return $this->matching->recommend($user, $limit);
    }

    // ─── Price alerts (real, based on PriceAlert model) ──────

    public function getPriceAlerts(User $user): Collection
    {
        return $user->priceAlerts()
            ->active()
            ->with(['residence.photos'])
            ->latest()
            ->get();
    }

    // ─── New listings in favorite areas (cached) ─────────────

    public function getNewInFavoriteAreas(User $user, int $limit = 4): Collection
    {
        $cacheKey = "client_new_areas_{$user->id}";

        return Cache::remember($cacheKey, self::GEO_CACHE_TTL, function () use ($user, $limit) {
            $favoriteCommunes = $user->favorites()
                ->join('residences', 'favorites.residence_id', '=', 'residences.id')
                ->pluck('residences.commune')
                ->unique()
                ->toArray();

            if (empty($favoriteCommunes)) {
                return Residence::where('status', 'active')
                    ->where('is_available', true)
                    ->where('owner_id', '!=', $user->id)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->with(['photos', 'amenities'])
                    ->latest()
                    ->take($limit)
                    ->get();
            }

            return Residence::whereIn('commune', $favoriteCommunes)
                ->where('status', 'active')
                ->where('is_available', true)
                ->where('owner_id', '!=', $user->id)
                ->where('created_at', '>=', now()->subDays(14))
                ->with(['photos', 'amenities'])
                ->latest()
                ->take($limit)
                ->get();
        });
    }

    // ─── Profile completion ──────────────────────────────────

    public function getProfileCompletion(User $user): array
    {
        $steps = [
            'avatar'         => [
                'done'  => !empty($user->profile_photo) || !empty($user->avatar),
                'label' => 'Photo de profil',
                'icon'  => 'camera',
            ],
            'phone'          => [
                'done'  => !empty($user->phone),
                'label' => 'Numéro de téléphone',
                'icon'  => 'phone',
            ],
            'email_verified' => [
                'done'  => $user->hasVerifiedEmail(),
                'label' => 'E-mail vérifié',
                'icon'  => 'envelope',
            ],
            'identity'       => [
                'done'  => $user->identityVerifications()->where('status', 'approved')->exists(),
                'label' => 'Pièce d\'identité',
                'icon'  => 'identification',
            ],
            'first_search'   => [
                'done'  => $user->searchHistories()->exists(),
                'label' => 'Première recherche',
                'icon'  => 'magnifying-glass',
            ],
        ];

        $completed = collect($steps)->where('done', true)->count();
        $total     = count($steps);

        return [
            'steps'      => $steps,
            'completed'  => $completed,
            'total'      => $total,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];
    }

    // ─── Hero Booking ─────────────────────────────────────────

    /**
     * Renvoie la prochaine réservation confirmée dont le check-in est dans ≤ 14 jours.
     */
    public function getHeroBooking(User $user)
    {
        return $user->bookings()
            ->upcoming()
            ->where('check_in', '<=', now()->addDays(14))
            ->with(['residence.photos', 'residence'])
            ->orderBy('check_in')
            ->first();
    }

    // ─── Activity Stats ───────────────────────────────────────

    /**
     * Synthèse parcours : séjours terminés, nuits totales, montant investi.
     */
    public function getActivityStats(User $user): array
    {
        $completed = $user->bookings()->completed()->get(['nights', 'total_amount']);

        return [
            'stays'       => $completed->count(),
            'nights'      => (int) $completed->sum('nights'),
            'total_spent' => (int) $completed->sum('total_amount'),
        ];
    }

    // ─── Reviews Awaiting Feedback ────────────────────────────

    /**
     * Nombre de réservations terminées sans avis, encore éligibles (≤ 14 jours après check-out).
     */
    public function getReviewsAwaitingFeedback(User $user): int
    {
        return $user->bookings()
            ->completed()
            ->whereDoesntHave('review')
            ->where('check_out', '>=', now()->subDays(14))
            ->count();
    }

    // ─── Trusted Tenant ──────────────────────────────────────

    /**
     * Le locataire est "Vérifié" s'il a vérifié son email + phone + identité + ≥ 1 séjour terminé.
     */
    public function isTrustedTenant(User $user): bool
    {
        return $user->hasVerifiedEmail()
            && !empty($user->phone)
            && $user->identityVerifications()->where('status', 'approved')->exists()
            && $user->bookings()->completed()->exists();
    }
}
