<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchResidenceRequest;
use App\Models\FraudReport;
use App\Models\Residence;
use App\Services\SponsoredListingService;
use App\Services\UserLocationService;
use App\Support\Residences\ResidenceBrowseSupport;
use App\Support\Residences\ResidenceShowSupport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Controller pour les résidences (public)
 */
class ResidenceController extends Controller
{
    public function __construct(
        private readonly SponsoredListingService $sponsoredListingService,
        private readonly ResidenceBrowseSupport $browseSupport,
        private readonly ResidenceShowSupport $showSupport,
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

        [$effectiveCountry, $effectiveCity] = $this->browseSupport->resolveEffectiveLocation($request);

        $this->browseSupport->applyIndexTextSearch($query, $request);
        $this->browseSupport->applyIndexFilters($query, $request, $effectiveCountry, $effectiveCity);

        $sponsoredIds = $this->sponsoredListingService->getTopSearchResidenceIds();

        $this->applySponsoredOrdering($query, $sponsoredIds);
        $this->browseSupport->applyIndexSort($query, (string) $request->get('sort', 'recent'));

        $residences = $query->paginate(config('rezi.pagination.residences'));

        $this->recordTopSearchImpressionsForFirstPage($residences, $sponsoredIds, $request);

        if ($request->ajax()) {
            return $this->browseSupport->buildIndexAjaxResponse($residences);
        }

        return view('residences.index', array_merge(
            [
                'residences' => $residences,
                'sponsoredIds' => $sponsoredIds,
            ],
            $this->browseSupport->buildIndexViewData($request, $effectiveCountry, $effectiveCity),
        ));
    }

    /**
     * Recherche de résidences avec filtres avancés
     */
    public function search(SearchResidenceRequest $request): View
    {
        $validated = $request->validated();

        $query = Residence::approved()->available()
            ->with(['photos', 'amenities', 'activePromotions']);

        $this->browseSupport->applySearchGeoFilter($query, $validated);

        [$searchCountry, $searchCity] = $this->browseSupport->resolveSearchLocation($validated);

        $this->browseSupport->applySearchLocationFilters($query, $validated, $searchCountry, $searchCity);
        $this->browseSupport->applySearchPrimaryFilters($query, $validated);
        $this->browseSupport->applySearchAvailabilityFilters($query, $validated);

        $sponsoredIds = $this->sponsoredListingService->getTopSearchResidenceIds();

        $this->applySponsoredOrdering($query, $sponsoredIds);
        $this->browseSupport->applySearchSort($query, (string) ($validated['sort'] ?? 'newest'));

        $residences = $query->paginate(config('rezi.pagination.residences'))->withQueryString();

        $this->recordTopSearchImpressionsForFirstPage($residences, $sponsoredIds, $request);

        return view('residences.search', array_merge(
            [
                'residences' => $residences,
                'validated' => $validated,
                'sponsoredIds' => $sponsoredIds,
            ],
            $this->browseSupport->buildSearchViewData($validated, $searchCountry, $searchCity),
        ));
    }

    /**
     * Vue carte
     */
    public function map(Request $request): View
    {
        $location = UserLocationService::current();

        $baseQuery = Residence::approved()
            ->with('primaryPhoto')
            ->select([
                'id', 'name', 'latitude', 'longitude',
                'price_per_day', 'price_per_month',
                'commune', 'quartier', 'type', 'type_location',
                'is_available', 'country_code', 'city',
                'bedrooms', 'bathrooms', 'max_guests',
                'average_rating', 'reviews_count',
                'instant_book', 'is_verified',
            ]);

        $hasLocationScope = ($location['country_code'] ?? null) || ($location['city'] ?? null);
        $scopedQuery = (clone $baseQuery)
            ->when($location['country_code'] ?? null, fn ($query, $countryCode) => $query->where('country_code', $countryCode))
            ->when($location['city'] ?? null, fn ($query, $city) => $query->where('city', $city));

        $residences = (clone $scopedQuery)
            ->limit(500)
            ->get();

        if ($hasLocationScope && $residences->isEmpty()) {
            $residences = (clone $baseQuery)
                ->limit(500)
                ->get();

            $hasLocationScope = false;
        }

        $mapFilterKey = 'filter_communes_'.strtolower(
            ($hasLocationScope ? ($location['country_code'] ?? 'all') : 'all').'_'
            .($hasLocationScope ? ($location['city'] ?? 'all') : 'all'),
        );
        $communes = \Illuminate\Support\Facades\Cache::remember(
            $mapFilterKey,
            config('rezi.cache_ttl'),
            function () use ($baseQuery, $scopedQuery, $hasLocationScope) {
                $query = $hasLocationScope ? clone $scopedQuery : clone $baseQuery;

                return $query
                    ->distinct()
                    ->pluck('commune')
                    ->sort()
                    ->values();
            },
        );

        $priceMin = (int) ($residences->min('price') ?: 0);
        $priceMax = (int) ($residences->max('price') ?: 500000);
        $types = $residences->pluck('type')->unique()->filter()->sort()->values();

        return view('residences.map', compact('residences', 'communes', 'priceMin', 'priceMax', 'types'));
    }

    /**
     * Détails d'une résidence
     */
    public function show(Residence $residence): View
    {
        $user = request()->user();

        $this->showSupport->ensureResidenceIsVisible($residence, $user);

        $isSponsored = $this->trackResidenceVisit($residence, $user?->id);

        $this->showSupport->loadResidenceDisplayRelations($residence);

        if ($residence->reviews->isNotEmpty()) {
            $residence->average_rating = $residence->reviews->avg('rating');
        }

        return view('residences.show', $this->showSupport->buildShowViewData($residence, $user, $isSponsored));
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

    private function applySponsoredOrdering(Builder $query, array $sponsoredIds): void
    {
        if ($sponsoredIds === []) {
            return;
        }

        $bindings = array_map('intval', $sponsoredIds);
        $placeholders = implode(',', array_fill(0, count($bindings), '?'));

        $query->orderByRaw("FIELD(residences.id, {$placeholders}) DESC", $bindings);
    }

    private function recordTopSearchImpressionsForFirstPage(LengthAwarePaginator $residences, array $sponsoredIds, Request $request): void
    {
        if ($residences->currentPage() !== 1 || $sponsoredIds === []) {
            return;
        }

        $this->sponsoredListingService->recordTopSearchImpressions(
            $sponsoredIds,
            $request->ip(),
            $request->user()?->id,
        );
    }

    private function trackResidenceVisit(Residence $residence, ?int $userId): bool
    {
        $residence->incrementViews();

        if (! $residence->isSponsored()) {
            return false;
        }

        $this->sponsoredListingService->recordResidenceClick($residence, request()->ip(), $userId);

        return true;
    }
}
