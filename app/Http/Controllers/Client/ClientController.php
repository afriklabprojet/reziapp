<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Residence;
use App\Models\ResidenceView;
use App\Models\SearchHistory;
use App\Services\ClientDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientDashboardService $dashboardService,
    ) {}

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

        $data = $this->dashboardService->getDashboardData($user);

        return view('client.dashboard', $data);
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

        // Alertes de prix réelles (via PriceAlert model)
        $priceAlerts = $this->dashboardService->getPriceAlerts($user);

        // Nouvelles résidences dans les zones favorites
        $newListings = $this->dashboardService->getNewInFavoriteAreas($user, 10);

        // Recherches sauvegardées avec alertes
        $savedSearches = $user->savedSearches()->withAlerts()->latest()->get();

        // Alertes de disponibilité
        $availabilityAlerts = $user->favorites()
            ->whereHas('residence', fn ($q) => $q->where('is_available', true))
            ->with('residence.photos')
            ->get();

        return view('client.alerts', compact('priceAlerts', 'newListings', 'savedSearches', 'availabilityAlerts'));
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
     * Page contrats / baux du locataire
     */
    public function contracts()
    {
        $user = Auth::user();

        $contracts = $user->leaseContracts()
            ->with(['residence.photos', 'owner'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $contractStats = [
            'total'      => $user->leaseContracts()->count(),
            'active'     => $user->leaseContracts()->where('status', 'active')->count(),
            'pending'    => $user->leaseContracts()->whereIn('status', ['pending_tenant', 'pending_owner', 'draft'])->count(),
            'terminated' => $user->leaseContracts()->whereIn('status', ['terminated', 'expired'])->count(),
        ];

        return view('client.contracts', compact('contracts', 'contractStats'));
    }

    /**
     * Détail d'un contrat (côté locataire)
     */
    public function showContract(\App\Models\LeaseContract $leaseContract)
    {
        $user = Auth::user();

        if ((int) $leaseContract->tenant_id !== (int) $user->id) {
            abort(403);
        }

        $leaseContract->load([
            'owner:id,name,email,phone',
            'tenant:id,name,email,phone',
            'residence:id,name,commune,address,surface_area,bedrooms',
            'residence.photos',
            'booking',
        ]);

        return view('client.contract-show', [
            'contract' => $leaseContract,
        ]);
    }

    /**
     * Signer un contrat (côté locataire)
     */
    public function signContract(\App\Models\LeaseContract $leaseContract)
    {
        $user = Auth::user();

        if ((int) $leaseContract->tenant_id !== (int) $user->id) {
            abort(403);
        }

        if ($leaseContract->status !== 'pending_tenant') {
            return back()->with('error', 'Ce contrat ne peut pas être signé actuellement.');
        }

        try {
            $service = app(\App\Services\LeaseContractService::class);
            $service->sign($leaseContract, $user, request()->ip());

            return back()->with('success', 'Contrat signé avec succès !');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Télécharger le PDF d'un contrat (côté locataire)
     */
    public function downloadContract(\App\Models\LeaseContract $leaseContract)
    {
        $user = Auth::user();

        if ((int) $leaseContract->tenant_id !== (int) $user->id) {
            abort(403);
        }

        $service = app(\App\Services\LeaseContractService::class);
        $pdfContent = $service->downloadPdf($leaseContract);
        $filename = "contrat-{$leaseContract->reference}.pdf";

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Sauvegarder une recherche comme alerte
     */
    public function saveSearchAsAlert(SearchHistory $search)
    {
        $user = Auth::user();

        if ($search->user_id !== $user->id) {
            abort(403);
        }

        // Vérifier si une alerte similaire existe déjà
        $existing = $user->savedSearches()
            ->where('location', $search->commune)
            ->where('min_price', $search->min_price)
            ->where('max_price', $search->max_price)
            ->first();

        if ($existing) {
            return back()->with('info', 'Une alerte similaire existe déjà.');
        }

        $user->savedSearches()->create([
            'name'            => $search->commune
                ? "Alerte {$search->commune}"
                : 'Alerte recherche',
            'filters'         => array_filter([
                'type'      => $search->type,
                'bedrooms'  => $search->bedrooms,
                'amenities' => $search->amenities,
            ]),
            'location'        => $search->commune,
            'latitude'        => $search->latitude,
            'longitude'       => $search->longitude,
            'min_price'       => $search->min_price,
            'max_price'       => $search->max_price,
            'has_alerts'      => true,
            'alert_frequency' => 'daily',
            'last_searched_at' => now(),
        ]);

        return back()->with('success', 'Alerte créée ! Vous serez notifié des nouveaux résultats.');
    }

    /**
     * Supprimer une alerte sauvegardée
     */
    public function deleteAlert(\App\Models\SavedSearch $savedSearch)
    {
        if ($savedSearch->user_id !== Auth::id()) {
            abort(403);
        }

        $savedSearch->delete();

        return back()->with('success', 'Alerte supprimée.');
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
