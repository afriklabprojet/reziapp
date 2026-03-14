<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Residence;
use App\Models\ResidenceView;
use App\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    /**
     * Dashboard principal du client
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Rediriger les propriétaires vers leur dashboard dédié
        if ($user->isOwner()) {
            return redirect()->route('owner.dashboard');
        }

        // Réservations à venir (priorité #1 pour un locataire)
        $upcomingBookings = $user->bookings()
            ->upcoming()
            ->with(['residence.photos'])
            ->orderBy('check_in')
            ->take(3)
            ->get();

        // Réservation en cours
        $ongoingBooking = $user->bookings()
            ->ongoing()
            ->with(['residence.photos'])
            ->first();

        // Statistiques générales
        $stats = [
            'bookings_upcoming' => $upcomingBookings->count(),
            'favorites_count' => $user->favorites()->count(),
            'messages_unread' => $user->unreadMessagesCount(),
            'views_count' => $user->residenceViews()->count(),
            'reviews_count' => $user->reviews()->count(),
            'notifications_unread' => $user->unreadNotifications()->count(),
        ];

        // Résidences récemment visitées
        $recentViews = ResidenceView::where('user_id', $user->id)
            ->with(['residence.photos'])
            ->select('residence_id', DB::raw('MAX(created_at) as last_viewed'))
            ->groupBy('residence_id')
            ->orderBy('last_viewed', 'desc')
            ->take(6)
            ->get();

        // Conversations récentes
        $recentConversations = Conversation::where('user_id', $user->id)
            ->with(['residence', 'owner', 'messages' => fn ($q) => $q->latest()->take(1)])
            ->orderBy('last_message_at', 'desc')
            ->take(3)
            ->get();

        // Recherches récentes
        $recentSearches = $user->searchHistories()
            ->latest()
            ->take(5)
            ->get();

        // Recommandations personnalisées
        $recommendations = $this->getRecommendations($user, 6);

        // Contacts en attente de réponse
        $pendingContacts = $user->sentContacts()
            ->where('status', 'pending')
            ->with(['residence', 'owner'])
            ->latest()
            ->take(3)
            ->get();

        // Nouvelles résidences dans les communes favorites
        $newInFavoriteAreas = $this->getNewInFavoriteAreas($user, 4);

        return view('client.dashboard', compact(
            'stats',
            'upcomingBookings',
            'ongoingBooking',
            'recentViews',
            'recentConversations',
            'recentSearches',
            'recommendations',
            'pendingContacts',
            'newInFavoriteAreas',
        ));
    }

    /**
     * Page historique des recherches
     */
    public function searchHistory(Request $request)
    {
        $user = Auth::user();

        $searches = $user->searchHistories()
            ->when($request->commune, fn ($q, $commune) => $q->where('commune', $commune))
            ->latest()
            ->paginate(20);

        // Communes les plus recherchées
        $topCommunes = $user->searchHistories()
            ->select('commune', DB::raw('COUNT(*) as count'))
            ->whereNotNull('commune')
            ->groupBy('commune')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();

        // Statistiques de recherche
        $searchStats = [
            'total' => $user->searchHistories()->count(),
            'this_month' => $user->searchHistories()->whereMonth('created_at', now()->month)->count(),
            'avg_results' => round($user->searchHistories()->avg('results_count') ?? 0),
        ];

        return view('client.search-history', compact('searches', 'topCommunes', 'searchStats'));
    }

    /**
     * Supprimer une recherche de l'historique
     */
    public function deleteSearch(SearchHistory $search)
    {
        if ($search->user_id !== Auth::id()) {
            abort(403);
        }

        $search->delete();

        return back()->with('success', 'Recherche supprimée de l\'historique.');
    }

    /**
     * Effacer tout l'historique de recherche
     */
    public function clearSearchHistory()
    {
        Auth::user()->searchHistories()->delete();

        return back()->with('success', 'Historique de recherche effacé.');
    }

    /**
     * Page historique des visites
     */
    public function viewHistory(Request $request)
    {
        $user = Auth::user();

        $views = ResidenceView::where('user_id', $user->id)
            ->with(['residence.photos', 'residence.amenities'])
            ->when($request->source, fn ($q, $source) => $q->where('source', $source))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistiques de visites
        $viewStats = [
            'total' => $user->residenceViews()->count(),
            'this_week' => $user->residenceViews()->where('created_at', '>=', now()->subWeek())->count(),
            'contacted' => $user->residenceViews()->where('contacted', true)->count(),
            'favorited' => $user->residenceViews()->where('favorited', true)->count(),
        ];

        // Communes les plus visitées
        $topViewedCommunes = ResidenceView::where('user_id', $user->id)
            ->join('residences', 'residence_views.residence_id', '=', 'residences.id')
            ->select('residences.commune', DB::raw('COUNT(*) as count'))
            ->groupBy('residences.commune')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();

        return view('client.view-history', compact('views', 'viewStats', 'topViewedCommunes'));
    }

    /**
     * Effacer l'historique des visites
     */
    public function clearViewHistory()
    {
        Auth::user()->residenceViews()->delete();

        return back()->with('success', 'Historique des visites effacé.');
    }

    /**
     * Page comparateur de résidences
     */
    public function compare(Request $request)
    {
        $user = Auth::user();
        $residenceIds = $request->input('residences', []);

        // Si aucune résidence sélectionnée, prendre les favoris récents
        if (empty($residenceIds)) {
            $residenceIds = $user->favorites()
                ->latest()
                ->take(4)
                ->pluck('residence_id')
                ->toArray();
        }

        $residences = Residence::whereIn('id', $residenceIds)
            ->with(['photos', 'amenities', 'owner'])
            ->get();

        // Tous les favoris pour permettre d'en ajouter
        $allFavorites = $user->favorites()
            ->with('residence')
            ->get();

        return view('client.compare', compact('residences', 'allFavorites'));
    }

    /**
     * Page des alertes
     */
    public function alerts()
    {
        $user = Auth::user();

        // Alertes de prix (simulées pour l'instant)
        $priceAlerts = $this->getPriceAlerts($user);

        // Nouvelles résidences dans les zones favorites
        $newListings = $this->getNewInFavoriteAreas($user, 10);

        // Alertes de disponibilité
        $availabilityAlerts = $user->favorites()
            ->whereHas('residence', fn ($q) => $q->where('is_available', true))
            ->with('residence.photos')
            ->get();

        return view('client.alerts', compact('priceAlerts', 'newListings', 'availabilityAlerts'));
    }

    /**
     * Mes contacts envoyés
     */
    public function contacts(Request $request)
    {
        $user = Auth::user();

        $contacts = $user->sentContacts()
            ->with(['residence.photos', 'owner'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Statistiques
        $contactStats = [
            'total' => $user->sentContacts()->count(),
            'pending' => $user->sentContacts()->where('status', 'pending')->count(),
            'replied' => $user->sentContacts()->where('status', 'replied')->count(),
            'closed' => $user->sentContacts()->where('status', 'closed')->count(),
        ];

        return view('client.contacts', compact('contacts', 'contactStats'));
    }

    /**
     * Mes avis laissés
     */
    public function reviews()
    {
        $user = Auth::user();

        $reviews = $user->reviews()
            ->with(['residence.photos', 'residence.owner'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Statistiques
        $reviewStats = [
            'total' => $user->reviews()->count(),
            'avg_rating' => round($user->reviews()->avg('rating'), 1),
            'with_response' => $user->reviews()->whereNotNull('owner_response')->count(),
        ];

        return view('client.reviews', compact('reviews', 'reviewStats'));
    }

    /**
     * Statistiques personnelles
     */
    public function statistics()
    {
        $user = Auth::user();

        // Activité par mois (derniers 6 mois)
        $monthlyActivity = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyActivity[] = [
                'month' => $date->translatedFormat('M Y'),
                'views' => $user->residenceViews()->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                'searches' => $user->searchHistories()->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                'contacts' => $user->sentContacts()->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
            ];
        }

        // Communes les plus explorées
        $topCommunes = ResidenceView::where('user_id', $user->id)
            ->join('residences', 'residence_views.residence_id', '=', 'residences.id')
            ->select('residences.commune', DB::raw('COUNT(*) as views_count'))
            ->groupBy('residences.commune')
            ->orderBy('views_count', 'desc')
            ->take(10)
            ->get();

        // Types de logement préférés
        $preferredTypes = ResidenceView::where('user_id', $user->id)
            ->join('residences', 'residence_views.residence_id', '=', 'residences.id')
            ->select('residences.type', DB::raw('COUNT(*) as count'))
            ->groupBy('residences.type')
            ->orderBy('count', 'desc')
            ->get();

        // Budget moyen recherché
        $budgetStats = $user->searchHistories()
            ->where(function ($q) {
                $q->whereNotNull('min_price')->orWhereNotNull('max_price');
            })
            ->selectRaw('AVG(min_price) as avg_min, AVG(max_price) as avg_max')
            ->first();

        // Statistiques globales
        $globalStats = [
            'member_since' => $user->created_at,
            'total_views' => $user->residenceViews()->count(),
            'total_searches' => $user->searchHistories()->count(),
            'total_contacts' => $user->sentContacts()->count(),
            'total_favorites' => $user->favorites()->count(),
            'total_reviews' => $user->reviews()->count(),
        ];

        return view('client.statistics', compact(
            'monthlyActivity',
            'topCommunes',
            'preferredTypes',
            'budgetStats',
            'globalStats',
        ));
    }

    /**
     * Obtenir des recommandations personnalisées
     */
    private function getRecommendations($user, int $limit = 6)
    {
        // Récupérer les communes et types favoris
        $favoriteCommunes = $user->favorites()
            ->join('residences', 'favorites.residence_id', '=', 'residences.id')
            ->pluck('residences.commune')
            ->unique()
            ->toArray();

        $favoriteTypes = $user->favorites()
            ->join('residences', 'favorites.residence_id', '=', 'residences.id')
            ->pluck('residences.type')
            ->unique()
            ->toArray();

        // Récupérer les communes recherchées récemment
        $searchedCommunes = $user->searchHistories()
            ->whereNotNull('commune')
            ->latest()
            ->take(5)
            ->pluck('commune')
            ->toArray();

        $allCommunes = array_unique(array_merge($favoriteCommunes, $searchedCommunes));

        // IDs des résidences déjà vues, en favoris, ou appartenant à l'utilisateur
        $excludeIds = $user->favorites()->pluck('residence_id')
            ->merge($user->residenceViews()->pluck('residence_id'))
            ->unique()
            ->toArray();

        // Chercher des résidences similaires (exclure les propres résidences du propriétaire)
        return Residence::query()
            ->where('status', 'active')
            ->where('is_available', true)
            ->where('owner_id', '!=', $user->id)
            ->whereNotIn('id', $excludeIds)
            ->where(function ($query) use ($allCommunes, $favoriteTypes) {
                $query->when(!empty($allCommunes), fn ($q) => $q->whereIn('commune', $allCommunes))
                      ->when(!empty($favoriteTypes), fn ($q) => $q->orWhereIn('type', $favoriteTypes));
            })
            ->with(['photos'])
            ->inRandomOrder()
            ->take($limit)
            ->get();
    }

    /**
     * Obtenir les alertes de prix pour les résidences favorites
     */
    private function getPriceAlerts($user)
    {
        // Récupérer les favoris avec les résidences qui ont eu des changements de prix
        // Pour l'instant, on simule en vérifiant les résidences mises à jour récemment
        return $user->favorites()
            ->with(['residence' => function ($query) {
                $query->where('updated_at', '>=', now()->subDays(7))
                    ->with('photos');
            }])
            ->whereHas('residence', function ($query) {
                $query->where('updated_at', '>=', now()->subDays(7));
            })
            ->get()
            ->map(function ($favorite) {
                return (object) [
                    'id' => $favorite->id,
                    'residence' => $favorite->residence,
                    'old_price' => $favorite->residence->price * 1.1, // Simulé: ancien prix 10% plus élevé
                    'new_price' => $favorite->residence->price,
                    'change_percentage' => -10, // Simulé: baisse de 10%
                    'changed_at' => $favorite->residence->updated_at,
                ];
            });
    }

    /**
     * Obtenir les nouvelles résidences dans les zones favorites
     */
    private function getNewInFavoriteAreas($user, int $limit = 4)
    {
        $favoriteCommunes = $user->favorites()
            ->join('residences', 'favorites.residence_id', '=', 'residences.id')
            ->pluck('residences.commune')
            ->unique()
            ->toArray();

        if (empty($favoriteCommunes)) {
            // Fallback: nouvelles résidences en général
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
    }

    /**
     * Enregistrer une recherche dans l'historique
     */
    public static function recordSearch(Request $request, int $resultsCount): void
    {
        if (!Auth::check()) {
            return;
        }

        SearchHistory::create([
            'user_id' => Auth::id(),
            'commune' => $request->input('commune'),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'bedrooms' => $request->input('bedrooms'),
            'type' => $request->input('type'),
            'amenities' => $request->input('amenities'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'radius' => $request->input('radius'),
            'search_query' => $request->input('q'),
            'results_count' => $resultsCount,
        ]);
    }
}
