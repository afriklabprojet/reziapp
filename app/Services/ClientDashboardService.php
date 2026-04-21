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
        $ongoingBooking = $this->getOngoingBooking($user);

        return [
            'stats'               => $this->getStats($user, $upcomingBookings),
            'upcomingBookings'    => $upcomingBookings,
            'ongoingBooking'      => $ongoingBooking,
            'recentViews'         => $this->getRecentViews($user),
            'recentConversations' => $this->getRecentConversations($user),
            'recentSearches'      => $this->getRecentSearches($user),
            'recommendations'     => $this->getRecommendations($user, 6),
            'pendingContacts'     => $this->getPendingContacts($user),
            'newInFavoriteAreas'  => $this->getNewInFavoriteAreas($user, 4),
            'profileCompletion'   => $this->getProfileCompletion($user),
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

    // ─── Stats ────────────────────────────────────────────────

    public function getStats(User $user, ?Collection $upcomingBookings = null): array
    {
        return [
            'bookings_upcoming'  => ($upcomingBookings ?? $this->getUpcomingBookings($user))->count(),
            'favorites_count'    => $user->favorites()->count(),
            'messages_unread'    => $user->unreadMessagesCount(),
            'views_count'        => $user->residenceViews()->count(),
            'reviews_count'      => $user->reviews()->count(),
            'notifications_unread' => $user->unreadNotifications()->count(),
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
}
