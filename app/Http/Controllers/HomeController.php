<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\RecordSponsoredImpressions;
use App\Models\Category;
use App\Models\Residence;
use App\Services\GeolocationService;
use App\Services\SponsoredListingService;
use App\Services\UserLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct(private readonly SponsoredListingService $sponsoredListingService)
    {
    }

    /**
     * Display the homepage with search functionality
     */
    public function index(Request $request)
    {
        [$residences, $searchPerformed, $userLocation] = $this->resolveHomepageSearch($request);

        // Featured residences — filtrées par localisation utilisateur (pays + ville)
        $location = UserLocationService::current();
        $locationKey = strtolower($location['country_code'].'_'.($location['city'] ?? 'all'));
        $cacheTtl = config('rezi.cache_ttl');

        $featuredResidences = $this->getFeaturedResidences($location, $locationKey, $cacheTtl);

        // Enregistrer les impressions sponsorisées HORS du cache (à chaque page view)
        // Dispatch en asynchrone pour éviter le N+1 bloquant (3–5 requêtes / annonce)
        $this->dispatchFeaturedResidenceImpressions($featuredResidences, $request);

        // Popular zones — filtrées par localisation utilisateur (résidences meublées uniquement)
        $popularZones = Cache::remember("popular_zones_{$locationKey}", $cacheTtl, function () use ($location) {
            $zones = Residence::approved()
                ->where('type_location', 'residence_meublee')
                ->when($location['country_code'] ?? null, fn ($q, $cc) => $q->where('country_code', $cc))
                ->when($location['city'] ?? null, fn ($q, $city) => $q->where('city', $city))
                ->select('commune', 'city', DB::raw('COUNT(*) as count'), DB::raw('MIN(price_per_day) as min_price'))
                ->groupBy('commune', 'city')
                ->orderBy('count', 'desc')
                ->limit(config('rezi.pagination.home_featured'))
                ->get();

            // Récupérer une photo par commune en 2 requêtes au lieu de 12 (fix N+1)
            $communeNames = $zones->pluck('commune');
            $communePhotos = Residence::approved()
                ->where('type_location', 'residence_meublee')
                ->whereIn('commune', $communeNames)
                ->whereHas('photos')
                ->with('photos:id,residence_id,path')
                ->select('id', 'commune')
                ->get()
                ->unique('commune')
                ->mapWithKeys(fn ($r) => [$r->commune => $r->photos->first()?->path]);

            return $zones->map(function ($zone) use ($communePhotos) {
                $photoPath = $communePhotos->get($zone->commune);

                return [
                    'name' => $zone->commune,
                    'city' => $zone->city,
                    'count' => $zone->count,
                    'min_price' => $zone->min_price,
                    'image' => $photoPath ? storage_url($photoPath) : asset('images/placeholder-residence.jpg'),
                ];
            });
        });

        // Statistics — filtrées par localisation
        $stats = Cache::remember("home_stats_{$locationKey}", $cacheTtl, function () use ($location) {
            $base = Residence::listable()
                ->when($location['country_code'] ?? null, fn ($q, $cc) => $q->where('country_code', $cc))
                ->when($location['city'] ?? null, fn ($q, $city) => $q->where('city', $city));

            return [
                'residences' => (clone $base)->count(),
                'owners' => (clone $base)->distinct('owner_id')->count('owner_id'),
                'communes' => (clone $base)->distinct('commune')->count('commune'),
                'contacts' => \App\Models\Contact::count(),
            ];
        });

        // Témoignages (avis vedettes depuis la BDD)
        $testimonials = Cache::remember('home_testimonials', $cacheTtl, function () {
            $reviews = \App\Models\Review::where('status', 'approved')
                ->where('rating', '>=', 4)
                ->whereNotNull('comment')
                ->with('user')
                ->orderByDesc('is_featured')
                ->orderByDesc('helpful_count')
                ->orderByDesc('rating')
                ->limit(config('rezi.pagination.home_testimonials'))
                ->get();

            if ($reviews->isEmpty()) {
                return collect();
            }

            return $reviews->map(function ($review) {
                return [
                    'name' => $review->user->name ?? 'Utilisateur',
                    'role' => 'Locataire',
                    'content' => $review->comment,
                    'avatar' => $review->user->getAvatarUrl(),
                    'rating' => $review->rating,
                ];
            });
        });

        // Statistiques avis — moyenne et nombre réels
        $reviewStats = Cache::remember('home_review_stats', $cacheTtl, function () {
            $row = \App\Models\Review::where('status', 'approved')
                ->selectRaw('ROUND(AVG(rating), 1) as avg_rating, COUNT(*) as total')
                ->first();

            return [
                'avg'   => $row->avg_rating > 0 ? number_format((float) $row->avg_rating, 1) : null,
                'total' => (int) $row->total,
            ];
        });

        // Catégories actives avec compteur (approved + available)
        $categories = Cache::remember('home_categories', $cacheTtl, function () {
            return Category::active()
                ->ordered()
                ->withCount('availableResidences as residences_count')
                ->get();
        });

        return view('home', compact(
            'residences',
            'searchPerformed',
            'userLocation',
            'featuredResidences',
            'popularZones',
            'stats',
            'testimonials',
            'reviewStats',
            'categories',
        ));
    }

    private function resolveHomepageSearch(Request $request): array
    {
        $residences = collect();
        $searchPerformed = false;
        $userLocation = null;

        if ($request->has('latitude') && $request->has('longitude')) {
            $searchPerformed = true;
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $radius = $request->input('radius', GeolocationService::getDefaultRadius());

            $userLocation = [
                'lat' => $latitude,
                'lng' => $longitude,
            ];

            $residences = Residence::approved()
                ->available()
                ->with(['photos', 'amenities', 'owner'])
                ->withinRadius($latitude, $longitude, $radius)
                ->limit(50)
                ->get();

            return [$residences, $searchPerformed, $userLocation];
        }

        if ($request->has('commune') || $request->has('quartier')) {
            $searchPerformed = true;
            $query = Residence::approved()
                ->available()
                ->with(['photos', 'amenities', 'owner']);

            if ($request->filled('commune')) {
                $commune = str_replace(['%', '_'], ['\%', '\_'], $request->commune);
                $query->where('commune', 'like', '%'.$commune.'%');
            }

            if ($request->filled('quartier')) {
                $quartier = str_replace(['%', '_'], ['\%', '\_'], $request->quartier);
                $query->where('quartier', 'like', '%'.$quartier.'%');
            }

            $residences = $query->limit(50)->get();
        }

        return [$residences, $searchPerformed, $userLocation];
    }

    private function getFeaturedResidences(array $location, string $locationKey, int $cacheTtl): Collection
    {
        return Cache::remember("featured_residences_{$locationKey}", $cacheTtl, function () use ($location) {
            $limit = config('rezi.pagination.home_featured');
            $sponsoredIds = $this->sponsoredListingService->getFeaturedHomeResidenceIds();

            $sponsored = collect();
            if (!empty($sponsoredIds)) {
                $sponsored = Residence::listable()
                    ->with(['photos', 'amenities', 'owner.badges'])
                    ->whereHas('photos')
                    ->whereIn('id', $sponsoredIds)
                    ->when($location['country_code'] ?? null, fn ($q, $cc) => $q->where('country_code', $cc))
                    ->when($location['city'] ?? null, fn ($q, $city) => $q->where('city', $city))
                    ->limit($limit)
                    ->get();
            }

            $remaining = $limit - $sponsored->count();
            $organic = collect();
            if ($remaining > 0) {
                $organic = Residence::listable()
                    ->with(['photos', 'amenities', 'owner.badges'])
                    ->whereHas('photos')
                    ->whereNotIn('id', $sponsoredIds)
                    ->when($location['country_code'] ?? null, fn ($q, $cc) => $q->where('country_code', $cc))
                    ->when($location['city'] ?? null, fn ($q, $city) => $q->where('city', $city))
                    ->orderBy('created_at', 'desc')
                    ->limit($remaining)
                    ->get();
            }

            return $sponsored->concat($organic);
        });
    }

    private function dispatchFeaturedResidenceImpressions(Collection $featuredResidences, Request $request): void
    {
        $sponsoredResidenceIds = $featuredResidences->pluck('id')->toArray();

        if (!empty($sponsoredResidenceIds)) {
            RecordSponsoredImpressions::dispatch(
                $sponsoredResidenceIds,
                $request->ip(),
                $request->user()?->id,
            )->onQueue('default');
        }
    }
}
