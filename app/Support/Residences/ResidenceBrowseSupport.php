<?php

declare(strict_types=1);

namespace App\Support\Residences;

use App\Models\Category;
use App\Models\Residence;
use App\Services\GeolocationService;
use App\Services\UserLocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ResidenceBrowseSupport
{
    public function resolveEffectiveLocation(Request $request): array
    {
        $location = UserLocationService::current();

        return [
            $request->input('country_code') ?: ($location['country_code'] ?? null),
            $request->input('city') ?: ($location['city'] ?? null),
        ];
    }

    public function applyIndexTextSearch(Builder $query, Request $request): void
    {
        if (! $request->filled('q')) {
            return;
        }

        $search = (string) $request->q;

        $query->whereRaw(
            'MATCH(name, commune, quartier, description) AGAINST(? IN BOOLEAN MODE)',
            [$search.'*'],
        );
    }

    public function applyIndexFilters(Builder $query, Request $request, ?string $country, ?string $city): void
    {
        $query->when($country, fn ($builder, $value) => $builder->where('country_code', $value))
            ->when($city, fn ($builder, $value) => $builder->where('city', $value))
            ->when($request->commune, fn ($builder, $value) => $builder->where('commune', $value))
            ->when($request->min_price, fn ($builder, $value) => $builder->where('price_per_month', '>=', $value))
            ->when($request->max_price, fn ($builder, $value) => $builder->where('price_per_month', '<=', $value));

        if ($request->filled('category')) {
            $query->whereHas('category', function ($builder) use ($request) {
                $builder->where('slug', $request->category);
            });
        }

        $typeLocation = $request->type_location ?? $request->route('type_location');
        if ($typeLocation) {
            $query->where('type_location', $typeLocation);
        }

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

        if ($request->filled('amenities') && is_array($request->amenities)) {
            foreach ($request->amenities as $amenity) {
                $safe = str_replace(['%', '_'], ['\\%', '\\_'], $amenity);
                $query->whereHas('amenities', function ($builder) use ($amenity, $safe) {
                    $builder->where('slug', $amenity)->orWhere('name', 'like', "%{$safe}%");
                });
            }
        }
    }

    public function applyIndexSort(Builder $query, string $sort): void
    {
        switch ($sort) {
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
                break;
        }
    }

    public function buildIndexAjaxResponse(LengthAwarePaginator $residences): JsonResponse
    {
        return response()->json([
            'html' => $this->renderResidenceCards($residences, 'components.residence-card-item'),
            'htmlList' => $this->renderResidenceCards($residences, 'components.residence-card-horizontal-item'),
            'hasMore' => $residences->hasMorePages(),
            'nextPage' => $residences->currentPage() + 1,
            'total' => $residences->total(),
        ]);
    }

    public function buildIndexViewData(Request $request, ?string $country, ?string $city): array
    {
        $indexFilterKey = 'filter_communes_'.strtolower(($country ?? 'all').'_'.($city ?? 'all'));
        $communes = \Illuminate\Support\Facades\Cache::remember(
            $indexFilterKey,
            config('rezi.cache_ttl'),
            fn () => Residence::approved()
                ->when($country, fn ($builder, $value) => $builder->where('country_code', $value))
                ->when($city, fn ($builder, $value) => $builder->where('city', $value))
                ->distinct()
                ->pluck('commune')
                ->sort()
                ->values(),
        );

        $cities = \Illuminate\Support\Facades\Cache::remember(
            'filter_cities',
            config('rezi.cache_ttl'),
            fn () => Residence::approved()
                ->whereNotNull('city')
                ->distinct()
                ->pluck('city')
                ->sort()
                ->values(),
        );

        return [
            'communes' => $communes,
            'cities' => $cities,
            'categories' => Category::active()->ordered()
                ->withCount('availableResidences as residences_count')
                ->get(),
            'currentCategory' => $request->filled('category')
                ? Category::where('slug', $request->category)->first()
                : null,
        ];
    }

    public function applySearchGeoFilter(Builder $query, array $validated): void
    {
        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            return;
        }

        $query->withinRadius(
            $validated['latitude'],
            $validated['longitude'],
            $validated['radius'] ?? GeolocationService::getDefaultRadius(),
        );
    }

    public function resolveSearchLocation(array $validated): array
    {
        $location = UserLocationService::current();

        return [
            ! empty($validated['country_code']) ? $validated['country_code'] : ($location['country_code'] ?? null),
            ! empty($validated['city']) ? $validated['city'] : ($location['city'] ?? null),
        ];
    }

    public function applySearchLocationFilters(Builder $query, array $validated, ?string $country, ?string $city): void
    {
        if ($country) {
            $query->where('country_code', $country);
        }

        if ($city) {
            $query->where('city', $city);
        }

        if (! empty($validated['commune'])) {
            $safeCommune = str_replace(['%', '_'], ['\\%', '\\_'], $validated['commune']);
            $query->where('commune', 'like', "%{$safeCommune}%");
        }

        if (! empty($validated['quartier'])) {
            $safeQuartier = str_replace(['%', '_'], ['\\%', '\\_'], $validated['quartier']);
            $query->where('quartier', 'like', "%{$safeQuartier}%");
        }
    }

    public function applySearchPrimaryFilters(Builder $query, array $validated): void
    {
        $this->applySearchPropertyFilters($query, $validated);
        $this->applySearchExperienceFilters($query, $validated);
    }

    public function applySearchAvailabilityFilters(Builder $query, array $validated): void
    {
        if (! empty($validated['check_in']) && ! empty($validated['check_out'])) {
            $checkIn = \Carbon\Carbon::parse($validated['check_in']);
            $checkOut = \Carbon\Carbon::parse($validated['check_out']);
            $flexWindow = (int) ($validated['flex_window'] ?? 0);

            if ($flexWindow > 0) {
                $this->applyFlexibleWindowAvailabilityFilter($query, $checkIn, $checkOut, $flexWindow);

                return;
            }

            $this->applyExactAvailabilityFilter($query, $checkIn, $checkOut);

            return;
        }

        if (! empty($validated['flex_dates']) && ! empty($validated['flex_type'])) {
            [$flexStart, $flexEnd] = $this->resolveFlexDateRange($validated['flex_type']);

            if ($flexStart && $flexEnd) {
                $query->whereDoesntHave('bookings', function ($builder) use ($flexStart, $flexEnd) {
                    $builder->whereIn('status', ['confirmed', 'pending', 'pending_payment'])
                        ->where('check_in', '<', $flexEnd)
                        ->where('check_out', '>', $flexStart);
                });
            }
        }
    }

    public function applySearchSort(Builder $query, string $sort): void
    {
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
            case 'distance':
            default:
                break;
        }
    }

    public function buildSearchViewData(array $validated, ?string $searchCountry, ?string $searchCity): array
    {
        $filterCacheKey = 'filter_communes_'.strtolower(($searchCountry ?? 'all').'_'.($searchCity ?? 'all'));
        $communes = \Illuminate\Support\Facades\Cache::remember(
            $filterCacheKey,
            config('rezi.cache_ttl'),
            fn () => Residence::approved()
                ->when($searchCountry, fn ($builder, $value) => $builder->where('country_code', $value))
                ->when($searchCity, fn ($builder, $value) => $builder->where('city', $value))
                ->distinct()
                ->pluck('commune')
                ->sort()
                ->values(),
        );

        $cities = \Illuminate\Support\Facades\Cache::remember(
            'filter_cities',
            config('rezi.cache_ttl'),
            fn () => Residence::approved()
                ->distinct()
                ->pluck('city')
                ->filter()
                ->sort()
                ->values(),
        );

        $quartiers = Residence::approved()
            ->when(! empty($validated['commune']), function ($builder) use ($validated) {
                $builder->where('commune', $validated['commune']);
            })
            ->distinct()
            ->pluck('quartier')
            ->filter()
            ->sort();

        return [
            'communes' => $communes,
            'cities' => $cities,
            'quartiers' => $quartiers,
            'amenities' => \App\Models\Amenity::orderBy('name')->get(),
            'cancellationPolicies' => \App\Models\CancellationPolicy::orderBy('name')->get(),
            'categories' => \Illuminate\Support\Facades\Cache::remember('search_categories_pills', 600, function () {
                return Category::active()->ordered()->get(['id', 'name', 'slug', 'icon']);
            }),
            'priceRange' => Residence::approved()
                ->selectRaw('MIN(price_per_month) as min_price, MAX(price_per_month) as max_price')
                ->first(),
        ];
    }

    private function renderResidenceCards(LengthAwarePaginator $residences, string $viewName): string
    {
        $html = '';

        foreach ($residences as $residence) {
            $html .= view($viewName, ['residence' => $residence])->render();
        }

        return $html;
    }

    private function applySearchPropertyFilters(Builder $query, array $validated): void
    {
        if (! empty($validated['min_price']) || ! empty($validated['max_price'])) {
            $query->priceBetween($validated['min_price'] ?? null, $validated['max_price'] ?? null);
        }

        if (! empty($validated['type'])) {
            $query->ofType($validated['type']);
        }

        if (! empty($validated['category'])) {
            $query->whereHas('category', function ($builder) use ($validated) {
                $builder->where('slug', $validated['category']);
            });
        }

        if (! empty($validated['bedrooms'])) {
            $query->where('bedrooms', '>=', $validated['bedrooms']);
        }

        if (! empty($validated['bathrooms'])) {
            $query->where('bathrooms', '>=', $validated['bathrooms']);
        }

        if (! empty($validated['max_guests'])) {
            $query->where('max_guests', '>=', $validated['max_guests']);
        }

        if (! empty($validated['amenities'])) {
            $query->withAmenities($validated['amenities']);
        }
    }

    private function applySearchExperienceFilters(Builder $query, array $validated): void
    {
        if (! empty($validated['min_rating'])) {
            $query->minRating($validated['min_rating']);
        }

        if (! empty($validated['cancellation_policy'])) {
            $query->withCancellationPolicy($validated['cancellation_policy']);
        }

        if (! empty($validated['instant_book'])) {
            $query->instantBook();
        }

        if (! empty($validated['has_promotion'])) {
            $query->withActivePromotions();
        }

        if (! empty($validated['is_accessible'])) {
            $query->accessible();
        }

        if (! empty($validated['available_now'])) {
            $query->availableNow();
        }

        if (! empty($validated['is_work_travel_ready'])) {
            $query->where('is_work_travel_ready', true);
        }

        if (! empty($validated['is_eco'])) {
            $query->where('sustainability_score', '>=', 70);
        }
    }

    private function applyFlexibleWindowAvailabilityFilter(Builder $query, \Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut, int $flexWindow): void
    {
        $duration = $checkIn->diffInDays($checkOut);
        $windows = [
            [$checkIn->copy()->subDays($flexWindow), $checkIn->copy()->subDays($flexWindow)->addDays($duration)],
            [$checkIn->copy(), $checkOut->copy()],
            [$checkIn->copy()->addDays($flexWindow), $checkIn->copy()->addDays($flexWindow)->addDays($duration)],
        ];

        $query->where(function ($outer) use ($windows) {
            foreach ($windows as $window) {
                [$windowStart, $windowEnd] = $window;

                $outer->orWhere(function ($builder) use ($windowStart, $windowEnd) {
                    $builder->whereDoesntHave('bookings', function ($bookingQuery) use ($windowStart, $windowEnd) {
                        $bookingQuery->whereIn('status', ['confirmed', 'pending', 'pending_payment'])
                            ->where('check_in', '<', $windowEnd)
                            ->where('check_out', '>', $windowStart);
                    })->whereDoesntHave('blockedDates', function ($blockedQuery) use ($windowStart, $windowEnd) {
                        $blockedQuery->where('start_date', '<', $windowEnd)
                            ->where('end_date', '>', $windowStart);
                    });
                });
            }
        });
    }

    private function applyExactAvailabilityFilter(Builder $query, \Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut): void
    {
        $query->whereDoesntHave('bookings', function ($builder) use ($checkIn, $checkOut) {
            $builder->whereIn('status', ['confirmed', 'pending', 'pending_payment'])
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn);
        })->whereDoesntHave('blockedDates', function ($builder) use ($checkIn, $checkOut) {
            $builder->where('start_date', '<', $checkOut)
                ->where('end_date', '>', $checkIn);
        });
    }

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
}
