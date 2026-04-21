<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchResidenceRequest;
use App\Models\Category;
use App\Models\FraudReport;
use App\Models\Residence;
use App\Models\SponsoredListing;
use App\Services\GeolocationService;
use App\Services\UserLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller pour les résidences (public)
 */
class ResidenceController extends Controller
{
    public function __construct(
        private GeolocationService $geolocationService,
    ) {
    }

    /**
     * Liste des résidences
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Residence::approved()
            ->available()
            ->with(['photos', 'amenities']);

        // Recherche texte (FULLTEXT)
        if ($request->filled('q')) {
            $search = $request->q;
            $query->whereRaw(
                'MATCH(name, commune, quartier, description) AGAINST(? IN BOOLEAN MODE)',
                [$search.'*'],
            );
        }

        // Filtres de base — auto-filtre par localisation utilisateur (style Airbnb)
        // Si pas de country_code/city explicite dans la requête, on utilise la session
        $location = UserLocationService::current();
        $effectiveCountry = $request->country_code ?: ($location['country_code'] ?? null);
        $effectiveCity = $request->city ?: ($location['city'] ?? null);

        $query->when($effectiveCountry, fn ($q, $cc) => $q->where('country_code', $cc))
              ->when($effectiveCity, fn ($q, $city) => $q->where('city', $city))
              ->when($request->commune, fn ($q, $commune) => $q->where('commune', $commune))
              ->when($request->min_price, fn ($q, $min) => $q->where('price_per_month', '>=', $min))
              ->when($request->max_price, fn ($q, $max) => $q->where('price_per_month', '<=', $max));

        // Filtre par catégorie
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filtre par type de location (residence_meublee, appartement, hotel)
        // Supporte query string OU route defaults (URLs propres)
        $typeLocation = $request->type_location ?? $request->route('type_location');
        if ($typeLocation) {
            $query->where('type_location', $typeLocation);
        }

        // Filtres avancés
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', '>=', $request->bedrooms);
        }

        if ($request->filled('bathrooms')) {
            $query->where('bathrooms', '>=', $request->bathrooms);
        }

        if ($request->filled('min_surface')) {
            $query->where('surface_area', '>=', $request->min_surface);
        }

        // Filtre équipements
        if ($request->filled('amenities') && is_array($request->amenities)) {
            foreach ($request->amenities as $amenity) {
                $safe = str_replace(['%', '_'], ['\%', '\_'], $amenity);
                $query->whereHas('amenities', function ($q) use ($amenity, $safe) {
                    $q->where('slug', $amenity)->orWhere('name', 'like', "%{$safe}%");
                });
            }
        }

        // Récupérer les IDs sponsorisés AVANT la requête pour le tri
        $sponsoredIds = [];
        $sponsoredQuery = SponsoredListing::topSearch()->pluck('residence_id')->unique();
        if ($sponsoredQuery->isNotEmpty()) {
            $sponsoredIds = $sponsoredQuery->toArray();
        }

        // Tri — sponsorisés toujours en tête sur la 1ère page
        if (!empty($sponsoredIds)) {
            $ids = implode(',', array_map('intval', $sponsoredIds));
            $query->orderByRaw("FIELD(residences.id, {$ids}) DESC");
        }

        switch ($request->get('sort', 'recent')) {
            case 'price_asc':
                $query->orderBy('price_per_month', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_per_month', 'desc');
                break;
            case 'surface':
                $query->orderBy('surface', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $residences = $query->paginate(config('rezi.pagination.residences'));

        // Enregistrer les impressions sponsorisées (1ère page uniquement)
        if ($residences->currentPage() === 1 && !empty($sponsoredIds)) {
            $ip = $request->ip();
            $userId = request()->user()?->id ?? 0;
            SponsoredListing::topSearch()
                ->whereIn('residence_id', $sponsoredIds)
                ->each(fn ($sl) => $sl->recordImpression($ip, $userId));
        }

        // AJAX "Load more" → return only the card partials
        if ($request->ajax()) {
            $html = '';
            foreach ($residences as $residence) {
                $html .= view('components.residence-card-item', ['residence' => $residence])->render();
            }
            $htmlList = '';
            foreach ($residences as $residence) {
                $htmlList .= view('components.residence-card-horizontal-item', ['residence' => $residence])->render();
            }

            return response()->json([
                'html'      => $html,
                'htmlList'  => $htmlList,
                'hasMore'   => $residences->hasMorePages(),
                'nextPage'  => $residences->currentPage() + 1,
                'total'     => $residences->total(),
            ]);
        }

        $indexFilterKey = 'filter_communes_'.strtolower(($effectiveCountry ?? 'all').'_'.($effectiveCity ?? 'all'));
        $communes = \Illuminate\Support\Facades\Cache::remember(
            $indexFilterKey,
            config('rezi.cache_ttl'),
            fn () =>
            Residence::approved()
                ->when($effectiveCountry, fn ($q, $cc) => $q->where('country_code', $cc))
                ->when($effectiveCity, fn ($q, $city) => $q->where('city', $city))
                ->distinct()
                ->pluck('commune')
                ->sort()
                ->values(),
        );

        $cities = \Illuminate\Support\Facades\Cache::remember(
            'filter_cities',
            config('rezi.cache_ttl'),
            fn () =>
            Residence::approved()
                ->whereNotNull('city')
                ->distinct()
                ->pluck('city')
                ->sort()
                ->values(),
        );

        // Catégories pour le filtre (approved + available)
        $categories = Category::active()->ordered()
            ->withCount('availableResidences as residences_count')
            ->get();

        // Catégorie active (si filtrée)
        $currentCategory = $request->filled('category')
            ? Category::where('slug', $request->category)->first()
            : null;

        return view('residences.index', compact('residences', 'communes', 'cities', 'categories', 'currentCategory', 'sponsoredIds'));
    }

    /**
     * Recherche de résidences avec filtres avancés
     */
    public function search(SearchResidenceRequest $request): View
    {
        $validated = $request->validated();

        $query = Residence::approved()->available()
            ->with(['photos', 'amenities', 'activePromotions']);

        // Recherche géolocalisée
        if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
            $radius = $validated['radius'] ?? 500;
            $query->withinRadius(
                $validated['latitude'],
                $validated['longitude'],
                $radius,
            );
        }

        // Filtres localisation — auto-filtre par localisation utilisateur si non spécifié
        $location = UserLocationService::current();

        $searchCountry = !empty($validated['country_code']) ? $validated['country_code'] : ($location['country_code'] ?? null);
        $searchCity = !empty($validated['city']) ? $validated['city'] : ($location['city'] ?? null);

        if ($searchCountry) {
            $query->where('country_code', $searchCountry);
        }

        if ($searchCity) {
            $query->where('city', $searchCity);
        }

        if (!empty($validated['commune'])) {
            $safeCommune = str_replace(['%', '_'], ['\%', '\_'], $validated['commune']);
            $query->where('commune', 'like', "%{$safeCommune}%");
        }

        if (!empty($validated['quartier'])) {
            $safeQuartier = str_replace(['%', '_'], ['\%', '\_'], $validated['quartier']);
            $query->where('quartier', 'like', "%{$safeQuartier}%");
        }

        // Filtres de prix
        if (!empty($validated['min_price']) || !empty($validated['max_price'])) {
            $query->priceBetween(
                $validated['min_price'] ?? null,
                $validated['max_price'] ?? null,
            );
        }

        // Filtre par type
        if (!empty($validated['type'])) {
            $query->ofType($validated['type']);
        }

        // Filtre par caractéristiques
        if (!empty($validated['bedrooms'])) {
            $query->where('bedrooms', '>=', $validated['bedrooms']);
        }

        if (!empty($validated['bathrooms'])) {
            $query->where('bathrooms', '>=', $validated['bathrooms']);
        }

        if (!empty($validated['max_guests'])) {
            $query->where('max_guests', '>=', $validated['max_guests']);
        }

        // Filtre par équipements
        if (!empty($validated['amenities'])) {
            $query->withAmenities($validated['amenities']);
        }

        // Filtre par note minimale
        if (!empty($validated['min_rating'])) {
            $query->minRating($validated['min_rating']);
        }

        // Filtre par politique d'annulation
        if (!empty($validated['cancellation_policy'])) {
            $query->withCancellationPolicy($validated['cancellation_policy']);
        }

        // Réservation instantanée
        if (!empty($validated['instant_book'])) {
            $query->instantBook();
        }

        // Promotions actives
        if (!empty($validated['has_promotion'])) {
            $query->withActivePromotions();
        }

        // Accessibilité PMR
        if (!empty($validated['is_accessible'])) {
            $query->accessible();
        }

        // Disponibilité immédiate
        if (!empty($validated['available_now'])) {
            $query->availableNow();
        }

        // Filtre Voyage professionnel
        if (!empty($validated['is_work_travel_ready'])) {
            $query->where('is_work_travel_ready', true);
        }

        // Filtre Éco-responsable
        if (!empty($validated['is_eco'])) {
            $query->where('sustainability_score', '>=', 70);
        }

        // Filtrage par dates (check_in / check_out)
        if (!empty($validated['check_in']) && !empty($validated['check_out'])) {
            $checkIn = \Carbon\Carbon::parse($validated['check_in']);
            $checkOut = \Carbon\Carbon::parse($validated['check_out']);
            // Exclure les résidences avec une réservation confirmée chevauchant ces dates
            $query->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
                $q->whereIn('status', ['confirmed', 'pending', 'pending_payment'])
                  ->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
            })->whereDoesntHave('blockedDates', function ($q) use ($checkIn, $checkOut) {
                $q->where('start_date', '<', $checkOut)
                  ->where('end_date', '>', $checkIn);
            });
        } elseif (!empty($validated['flex_dates']) && !empty($validated['flex_type'])) {
            // Dates flexibles — calculer la fenêtre correspondante
            [$flexStart, $flexEnd] = $this->resolveFlexDateRange($validated['flex_type']);
            if ($flexStart && $flexEnd) {
                $query->whereDoesntHave('bookings', function ($q) use ($flexStart, $flexEnd) {
                    $q->whereIn('status', ['confirmed', 'pending', 'pending_payment'])
                      ->where('check_in', '<', $flexEnd)
                      ->where('check_out', '>', $flexStart);
                });
            }
        }

        // Récupérer les IDs sponsorisés AVANT la requête pour le tri
        $sponsoredIds = [];
        $sponsoredSearchQuery = SponsoredListing::topSearch()->pluck('residence_id')->unique();
        if ($sponsoredSearchQuery->isNotEmpty()) {
            $sponsoredIds = $sponsoredSearchQuery->toArray();
            // Sponsorisés en tête
            $ids = implode(',', array_map('intval', $sponsoredIds));
            $query->orderByRaw("FIELD(residences.id, {$ids}) DESC");
        }

        // Tri
        $sort = $validated['sort'] ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_per_month', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_per_month', 'desc');
                break;
            case 'rating':
                $query->orderBy('average_rating', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
                // 'distance' est géré par withinRadius
        }

        $residences = $query->paginate(config('rezi.pagination.residences'))->withQueryString();

        // Enregistrer les impressions sponsorisées (1ère page uniquement)
        if ($residences->currentPage() === 1 && !empty($sponsoredIds)) {
            $ip = $request->ip();
            $userId = request()->user()?->id ?? 0;
            SponsoredListing::topSearch()
                ->whereIn('residence_id', $sponsoredIds)
                ->each(fn ($sl) => $sl->recordImpression($ip, $userId));
        }

        // Données pour les filtres — filtrées par localisation active
        $filterCacheKey = 'filter_communes_'.strtolower(($searchCountry ?? 'all').'_'.($searchCity ?? 'all'));
        $communes = \Illuminate\Support\Facades\Cache::remember(
            $filterCacheKey,
            config('rezi.cache_ttl'),
            fn () =>
            Residence::approved()
                ->when($searchCountry, fn ($q, $cc) => $q->where('country_code', $cc))
                ->when($searchCity, fn ($q, $city) => $q->where('city', $city))
                ->distinct()
                ->pluck('commune')
                ->sort()
                ->values(),
        );

        $cities = \Illuminate\Support\Facades\Cache::remember(
            'filter_cities',
            config('rezi.cache_ttl'),
            fn () =>
            Residence::approved()
                ->distinct()
                ->pluck('city')
                ->filter()
                ->sort()
                ->values(),
        );

        $quartiers = Residence::approved()
            ->when(!empty($validated['commune']), function ($q) use ($validated) {
                $q->where('commune', $validated['commune']);
            })
            ->distinct()
            ->pluck('quartier')
            ->filter()
            ->sort();

        $amenities = \App\Models\Amenity::orderBy('name')->get();

        $cancellationPolicies = \App\Models\CancellationPolicy::orderBy('name')->get();

        // Prix min/max pour le slider
        $priceRange = Residence::approved()
            ->selectRaw('MIN(price_per_month) as min_price, MAX(price_per_month) as max_price')
            ->first();

        return view('residences.search', compact(
            'residences',
            'communes',
            'cities',
            'quartiers',
            'amenities',
            'cancellationPolicies',
            'priceRange',
            'validated',
            'sponsoredIds',
        ));
    }

    /**
     * Résoudre la fenêtre de dates flexibles en [start, end] Carbon
     */
    private function resolveFlexDateRange(string $flexType): array
    {
        $now = \Carbon\Carbon::now();

        return match ($flexType) {
            'weekend' => [
                $now->copy()->next(\Carbon\Carbon::FRIDAY),
                $now->copy()->next(\Carbon\Carbon::FRIDAY)->addDays(2),
            ],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'flexible_3' => [$now->copy(), $now->copy()->addDays(3)],
            'flexible_7' => [$now->copy(), $now->copy()->addDays(7)],
            default => [null, null],
        };
    }

    /**
     * Vue carte
     */
    public function map(Request $request): View
    {
        // Filtrer par localisation utilisateur (style Airbnb)
        $location = UserLocationService::current();

        $residences = Residence::approved()
            ->with('primaryPhoto')
            ->select([
                'id', 'name', 'latitude', 'longitude',
                'price_per_day', 'price_per_month',
                'commune', 'quartier', 'type', 'type_location',
                'is_available', 'country_code', 'city',
                'bedrooms', 'bathrooms', 'max_guests',
                'average_rating', 'reviews_count',
                'instant_book', 'is_verified',
            ])
            ->when($location['country_code'] ?? null, fn ($q, $cc) => $q->where('country_code', $cc))
            ->when($location['city'] ?? null, fn ($q, $city) => $q->where('city', $city))
            ->limit(500) // Safety cap pour éviter surcharge mémoire
            ->get();

        $mapFilterKey = 'filter_communes_'.strtolower(($location['country_code'] ?? 'all').'_'.($location['city'] ?? 'all'));
        $communes = \Illuminate\Support\Facades\Cache::remember(
            $mapFilterKey,
            config('rezi.cache_ttl'),
            fn () =>
            Residence::approved()
                ->when($location['country_code'] ?? null, fn ($q, $cc) => $q->where('country_code', $cc))
                ->when($location['city'] ?? null, fn ($q, $city) => $q->where('city', $city))
                ->distinct()
                ->pluck('commune')
                ->sort()
                ->values(),
        );

        // Bornes de prix basées sur le prix d'affichage (accesseur ->price)
        $priceMin = (int) ($residences->min('price') ?: 0);
        $priceMax = (int) ($residences->max('price') ?: 500000);

        // Types de logement disponibles
        $types = $residences->pluck('type')->unique()->filter()->sort()->values();

        return view('residences.map', compact('residences', 'communes', 'priceMin', 'priceMax', 'types'));
    }

    /**
     * Détails d'une résidence
     */
    public function show(Residence $residence): View
    {
        // Vérifier que la résidence est visible
        $user = request()->user();
        if ($residence->status !== 'active' &&
            ($user === null || $user->id !== $residence->owner_id)) {
            abort(404);
        }

        // Incrémenter les vues
        $residence->incrementViews();

        // Enregistrer le clic sponsorisé si la résidence est sponsorisée
        $isSponsored = false;
        if ($residence->isSponsored()) {
            $isSponsored = true;
            $activeSponsoredListing = $residence->activeSponsoredListing();
            if ($activeSponsoredListing) {
                $activeSponsoredListing->recordClick(request()->ip(), $user?->id ?? 0);
            }
        }

        // Charger toutes les relations nécessaires
        $residence->load([
            'photos',
            'amenities',
            'owner:id,name,phone,avatar,identity_verified,created_at',
            'photos360',
            'pointsOfInterest',
            'activeBadges',
            'reviews' => function ($query) {
                $query->with('user:id,name')
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc');
            },
        ]);

        // Calculer la note moyenne
        if ($residence->reviews->isNotEmpty()) {
            $residence->average_rating = $residence->reviews->avg('rating');
        }

        // Résidences similaires (même commune ou type similaire) — cachées 1h
        $similarResidences = \Illuminate\Support\Facades\Cache::remember(
            "residence:{$residence->id}:similar",
            config('rezi.cache_ttl'),
            fn () => Residence::approved()
                ->available()
                ->where('id', '!=', $residence->id)
                ->where(function ($query) use ($residence) {
                    $query->where('commune', $residence->commune)
                          ->orWhere('type', $residence->type);
                })
                ->with(['photos'])
                ->orderByRaw('CASE WHEN commune = ? THEN 0 ELSE 1 END', [$residence->commune])
                ->take(4)
                ->get(),
        );

        // Données pour le contact
        $ownerPhone = $residence->owner->phone ?? '+225 00 00 00 00 00';
        $ownerResidencesCount = $residence->owner->residences()->approved()->count();

        // ── Indicateurs de rareté/urgence (Airbnb/Booking style) ──
        // Vues actives dans les 24 dernières heures (sans requête lourde : valeur mise à jour par job)
        $activeViewers = $residence->active_viewers_24h ?? 0;
        // Nombre de réservations confirmées ce mois
        $bookingsThisMonth = $residence->bookings_this_month ?? 0;
        // Dernière réservation (pour afficher "Réservé récemment")
        $lastBookedDaysAgo = \App\Models\Booking::where('residence_id', $residence->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->where('confirmed_at', '>=', now()->subDays(14))
            ->count();

        // ── Taux de réponse de l'hôte ──
        // Utilise les vraies données depuis la table residences
        $responseRate    = $residence->response_rate ?? 0;
        $avgResponseTime = $residence->avg_response_time_hours ?? null;

        // Calcul en direct si valeur nulle (première visite)
        if ($responseRate == 0 && $avgResponseTime === null) {
            $totalRequests = \App\Models\BookingRequest::where('residence_id', $residence->id)
                ->where('created_at', '>=', now()->subDays(90))
                ->count();
            $respondedRequests = \App\Models\BookingRequest::where('residence_id', $residence->id)
                ->where('created_at', '>=', now()->subDays(90))
                ->whereNotNull('responded_at')
                ->count();

            $responseRate    = $totalRequests > 0 ? round($respondedRequests / $totalRequests * 100) : null;
            $avgResponseTime = null;
        }

        // ── Suggestion de prix intelligent ──
        $marketPrice = null;
        if ($residence->commune) {
            $marketPrice = \App\Models\MarketPriceData::query()
                ->selectRaw('AVG(avg_price_per_night) as avg_price, AVG(median_price_per_night) as median_price')
                ->where('commune', $residence->commune)
                ->where('bedrooms', $residence->bedrooms)
                ->where('period_end', '>=', now()->subDays(30))
                ->first();
        }
        $priceSuggestion = null;
        if ($marketPrice && $residence->price_per_day > 0 && (float)($marketPrice->avg_price ?? 0) > 0) {
            $diff = (($residence->price_per_day - $marketPrice->avg_price) / $marketPrice->avg_price) * 100;
            if ($diff > 20) {
                $priceSuggestion = ['type' => 'above', 'percent' => round(abs($diff)), 'market' => $marketPrice->avg_price];
            } elseif ($diff < -20) {
                $priceSuggestion = ['type' => 'below', 'percent' => round(abs($diff)), 'market' => $marketPrice->avg_price];
            }
        }

        // Vérifier si l'utilisateur connecté peut laisser un avis
        // (a complété une réservation et n'a pas encore donné d'avis)
        $canReview = false;
        if ($user !== null) {
            $hasCompletedBooking = \App\Models\Booking::where('user_id', $user->id)
                ->where('residence_id', $residence->id)
                ->where('status', 'completed')
                ->exists();

            $hasAlreadyReviewed = \App\Models\Review::where('user_id', $user->id)
                ->where('residence_id', $residence->id)
                ->exists();

            $canReview = $hasCompletedBooking && !$hasAlreadyReviewed;
        }

        // Dates bloquées pour les 6 prochains mois — cachées 15 min
        $unavailableDates = \Illuminate\Support\Facades\Cache::remember(
            "residence:{$residence->id}:unavailable_dates",
            900, // 15 minutes
            function () use ($residence) {
                $blockedDates = \App\Models\BlockedDate::getBlockedDatesArray(
                    $residence->id,
                    now()->toDateString(),
                    now()->addMonths(6)->toDateString(),
                );
                $bookedDates = \App\Models\Booking::where('residence_id', $residence->id)
                    ->whereIn('status', ['confirmed', 'pending', 'paid'])
                    ->where('check_out', '>=', now())
                    ->get()
                    ->flatMap(function ($booking) {
                        $dates = [];
                        $current = \Carbon\Carbon::parse($booking->check_in);
                        $end = \Carbon\Carbon::parse($booking->check_out);
                        while ($current < $end) {
                            $dates[] = $current->format('Y-m-d');
                            $current->addDay();
                        }

                        return $dates;
                    })
                    ->toArray();

                return array_values(array_unique(array_merge($blockedDates, $bookedDates)));
            },
        );

        return view('residences.show', compact(
            'residence',
            'similarResidences',
            'ownerPhone',
            'ownerResidencesCount',
            'canReview',
            'unavailableDates',
            'isSponsored',
            'activeViewers',
            'bookingsThisMonth',
            'lastBookedDaysAgo',
            'responseRate',
            'avgResponseTime',
            'priceSuggestion',
        ));
    }

    /**
     * Signaler une annonce (FraudReport)
     */
    public function report(Request $request, Residence $residence): JsonResponse
    {
        $validated = $request->validate([
            'fraud_type' => ['required', 'string', 'in:fake_listing,misleading_photos,wrong_price,scam,inappropriate_content,duplicate,other'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        FraudReport::create([
            'reporter_id' => $request->user()?->id,
            'reporter_ip' => $request->ip(),
            'reporter_user_agent' => $request->userAgent(),
            'target_type' => 'residence',
            'target_id' => $residence->id,
            'target_user_id' => $residence->owner_id,
            'fraud_type' => $validated['fraud_type'],
            'description' => $validated['description'],
            'status' => 'pending',
            'priority' => in_array($validated['fraud_type'], ['scam', 'fake_listing']) ? 'high' : 'medium',
            'is_auto_detected' => false,
        ]);

        return response()->json(['message' => 'Signalement enregistré.']);
    }
}
