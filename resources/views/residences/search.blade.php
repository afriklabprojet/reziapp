<x-app-layout>
    <div x-data="searchPage()" x-init="init()" class="h-[calc(100vh-64px)] flex flex-col">
        <!-- Header avec filtres -->
        <div class="bg-white border-b px-4 py-3">
            <div class="max-w-full mx-auto">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Barre de recherche -->
                    <div class="flex-1 min-w-62.5 relative">
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="searchLocation()"
                            placeholder="Rechercher un lieu..."
                            class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>

                        <!-- Suggestions -->
                        <div x-show="suggestions.length > 0" x-transition @click.away="suggestions = []"
                            class="absolute z-50 w-full mt-1 bg-white rounded-lg shadow-lg border max-h-60 overflow-auto">
                            <template x-for="suggestion in suggestions" :key="suggestion.id">
                                <button @click="selectLocation(suggestion)"
                                    class="w-full px-4 py-2 text-left hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    <span x-text="suggestion.place_name || suggestion.text"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Rayon -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Rayon:</label>
                        <div class="flex items-center gap-2">
                            <input type="range" x-model="radius" @change="updateSearch()" min="1"
                                max="20" step="1"
                                class="w-24 h-2 bg-gray-200 rounded-lg cursor-pointer accent-blue-600">
                            <span class="text-sm font-medium text-blue-600 w-12" x-text="radius + ' km'"></span>
                        </div>
                    </div>

                    <!-- Filtres avancés toggle -->
                    <button @click="showFilters = !showFilters"
                        :class="showFilters ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 hover:opacity-80 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filtres
                        <span x-show="activeFiltersCount > 0"
                            class="bg-blue-600 text-white text-xs px-1.5 py-0.5 rounded-full"
                            x-text="activeFiltersCount"></span>
                    </button>

                    <!-- Toggle mobile carte/liste -->
                    <div class="lg:hidden flex rounded-lg overflow-hidden border">
                        <button @click="mobileView = 'map'"
                            :class="mobileView === 'map' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                            class="px-4 py-2 text-sm font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Carte
                        </button>
                        <button @click="mobileView = 'list'"
                            :class="mobileView === 'list' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                            class="px-4 py-2 text-sm font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            Liste
                        </button>
                    </div>
                </div>

                <!-- Filtres avancés -->
                <div x-show="showFilters" x-collapse class="mt-4 pt-4 border-t">
                    {{-- Filtres de base --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-4">
                        <!-- Ville -->
                        @if (isset($cities) && $cities->count() > 1)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                                <select x-model="filters.city" @change="updateSearch()"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Toutes</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Commune -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Commune</label>
                            <select x-model="filters.commune" @change="loadQuartiers(); updateSearch()"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Toutes</option>
                                @foreach ($communes as $commune)
                                    <option value="{{ $commune }}">{{ $commune }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Quartier -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quartier</label>
                            <select x-model="filters.quartier" @change="updateSearch()"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                :disabled="!filters.commune">
                                <option value="">Tous</option>
                                @if (isset($quartiers))
                                    @foreach ($quartiers as $quartier)
                                        <option value="{{ $quartier }}">{{ $quartier }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select x-model="filters.type" @change="updateSearch()"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Tous</option>
                                <option value="studio">Studio</option>
                                <option value="apartment">Appartement</option>
                                <option value="house">Maison</option>
                                <option value="villa">Villa</option>
                                <option value="duplex">Duplex</option>
                            </select>
                        </div>

                        <!-- Chambres -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chambres</label>
                            <select x-model="filters.bedrooms" @change="updateSearch()"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Toutes</option>
                                <option value="1">1+</option>
                                <option value="2">2+</option>
                                <option value="3">3+</option>
                                <option value="4">4+</option>
                                <option value="5">5+</option>
                            </select>
                        </div>

                        <!-- Salles de bain -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Salles de bain</label>
                            <select x-model="filters.bathrooms" @change="updateSearch()"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Toutes</option>
                                <option value="1">1+</option>
                                <option value="2">2+</option>
                                <option value="3">3+</option>
                            </select>
                        </div>

                        <!-- Capacité -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Voyageurs</label>
                            <select x-model="filters.max_guests" @change="updateSearch()"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Tous</option>
                                <option value="1">1+</option>
                                <option value="2">2+</option>
                                <option value="4">4+</option>
                                <option value="6">6+</option>
                                <option value="8">8+</option>
                            </select>
                        </div>
                    </div>

                    {{-- Prix avec slider --}}
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Prix mensuel:
                            <span class="text-blue-600 font-semibold"
                                x-text="formatPrice(filters.min_price || {{ $priceRange->min_price ?? 0 }})"></span>
                            -
                            <span class="text-blue-600 font-semibold"
                                x-text="filters.max_price ? formatPrice(filters.max_price) : 'Illimité'"></span>
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="number" x-model="filters.min_price" @change="updateSearch()"
                                placeholder="Min"
                                class="w-32 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <div class="flex-1 h-2 bg-gray-200 rounded-full relative">
                                <div class="absolute inset-y-0 bg-blue-500 rounded-full"
                                    :style="`left: ${((filters.min_price || 0) / {{ $priceRange->max_price ?? 1000000 }}) * 100}%; right: ${100 - ((filters.max_price || {{ $priceRange->max_price ?? 1000000 }}) / {{ $priceRange->max_price ?? 1000000 }}) * 100}%`">
                                </div>
                            </div>
                            <input type="number" x-model="filters.max_price" @change="updateSearch()"
                                placeholder="Max"
                                class="w-32 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    {{-- Note minimale --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Note minimale</label>
                        <div class="flex items-center gap-2">
                            <template x-for="star in [1, 2, 3, 4, 5]">
                                <button type="button"
                                    @click="filters.min_rating = filters.min_rating === star ? '' : star; updateSearch()"
                                    :class="star <= (filters.min_rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                                    class="text-2xl hover:scale-110 transition-transform focus:outline-none">
                                    ★
                                </button>
                            </template>
                            <span class="text-sm text-gray-500 ml-2" x-show="filters.min_rating">
                                <span x-text="filters.min_rating"></span>+ étoiles
                            </span>
                        </div>
                    </div>

                    {{-- Équipements --}}
                    @if (isset($amenities) && $amenities->isNotEmpty())
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Équipements</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($amenities as $amenity)
                                    <button type="button" @click="toggleAmenity({{ $amenity->id }})"
                                        :class="filters.amenities.includes({{ $amenity->id }}) ?
                                            'bg-blue-100 border-blue-500 text-blue-700' :
                                            'bg-white border-gray-300 text-gray-700'"
                                        class="px-3 py-1.5 rounded-full border text-sm font-medium hover:border-blue-400 transition-colors flex items-center gap-1.5">
                                        <span>{{ $amenity->icon ?? '✓' }}</span>
                                        <span>{{ $amenity->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Politique d'annulation --}}
                    @if (isset($cancellationPolicies) && $cancellationPolicies->isNotEmpty())
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Politique d'annulation</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($cancellationPolicies as $policy)
                                    <button type="button"
                                        @click="filters.cancellation_policy = filters.cancellation_policy === {{ $policy->id }} ? '' : {{ $policy->id }}; updateSearch()"
                                        :class="filters.cancellation_policy === {{ $policy->id }} ?
                                            'bg-green-100 border-green-500 text-green-700' :
                                            'bg-white border-gray-300 text-gray-700'"
                                        class="px-3 py-1.5 rounded-full border text-sm font-medium hover:border-green-400 transition-colors">
                                        {{ $policy->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Options spéciales --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Options</label>
                        <div class="flex flex-wrap gap-3">
                            <!-- Réservation instantanée -->
                            <label
                                class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border transition-colors"
                                :class="filters.instant_book ? 'bg-orange-50 border-orange-400' :
                                    'border-gray-200 hover:border-gray-300'">
                                <input type="checkbox" x-model="filters.instant_book" @change="updateSearch()"
                                    class="sr-only">
                                <span class="text-lg">⚡</span>
                                <span class="text-sm font-medium"
                                    :class="filters.instant_book ? 'text-orange-700' : 'text-gray-700'">Réservation
                                    instantanée</span>
                            </label>

                            <!-- Promotions actives -->
                            <label
                                class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border transition-colors"
                                :class="filters.has_promotion ? 'bg-red-50 border-red-400' :
                                    'border-gray-200 hover:border-gray-300'">
                                <input type="checkbox" x-model="filters.has_promotion" @change="updateSearch()"
                                    class="sr-only">
                                <span class="text-lg">🏷️</span>
                                <span class="text-sm font-medium"
                                    :class="filters.has_promotion ? 'text-red-700' : 'text-gray-700'">Promos</span>
                            </label>

                            <!-- Disponible maintenant -->
                            <label
                                class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border transition-colors"
                                :class="filters.available_now ? 'bg-green-50 border-green-400' :
                                    'border-gray-200 hover:border-gray-300'">
                                <input type="checkbox" x-model="filters.available_now" @change="updateSearch()"
                                    class="sr-only">
                                <span class="text-lg">🟢</span>
                                <span class="text-sm font-medium"
                                    :class="filters.available_now ? 'text-green-700' : 'text-gray-700'">Dispo.
                                    immédiate</span>
                            </label>

                            <!-- Accessibilité PMR -->
                            <label
                                class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border transition-colors"
                                :class="filters.is_accessible ? 'bg-blue-50 border-blue-400' :
                                    'border-gray-200 hover:border-gray-300'">
                                <input type="checkbox" x-model="filters.is_accessible" @change="updateSearch()"
                                    class="sr-only">
                                <span class="text-lg">♿</span>
                                <span class="text-sm font-medium"
                                    :class="filters.is_accessible ? 'text-blue-700' : 'text-gray-700'">Accessible
                                    PMR</span>
                            </label>
                        </div>
                    </div>

                    {{-- Boutons d'action --}}
                    <div class="flex items-center justify-between pt-4 border-t">
                        <button @click="resetFilters()"
                            class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                            Réinitialiser les filtres
                        </button>
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <span x-show="activeFiltersCount > 0">
                                <span x-text="activeFiltersCount"></span> filtre(s) actif(s)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Carte (gauche sur desktop) -->
            <div :class="mobileView === 'map' ? 'block' : 'hidden lg:block'"
                class="w-full lg:w-1/2 xl:w-3/5 h-full relative">
                <x-map-search :residences="$residences
                    ->map(
                        fn($r) => [
                            'id' => $r->id,
                            'title' => $r->name,
                            'price' => $r->price_per_month,
                            'thumbnail' => $r->photos->isNotEmpty()
                                ? storage_url(
                                    $r->photos->where('is_primary', true)->first()?->path ?? $r->photos->first()?->path,
                                )
                                : null,
                            'location' => [
                                'latitude' => $r->latitude,
                                'longitude' => $r->longitude,
                                'commune' => $r->commune,
                                'quartier' => $r->quartier,
                                'address' => $r->address,
                                'distance_meters' => $r->distance ? $r->distance * 1000 : null,
                            ],
                        ],
                    )
                    ->toArray()" :center="[
                    'lat' => $validated['latitude'] ?? config('rezi.default_latitude'),
                    'lng' => $validated['longitude'] ?? config('rezi.default_longitude'),
                ]" :radius="$validated['radius'] ?? config('rezi.default_search_radius_km')" height="h-full" class="h-full" />
            </div>

            <!-- Liste (droite sur desktop) -->
            <div :class="mobileView === 'list' ? 'block' : 'hidden lg:block'"
                class="w-full lg:w-1/2 xl:w-2/5 h-full overflow-y-auto bg-gray-50 border-l">
                <!-- Header liste -->
                <div class="sticky top-0 bg-white border-b px-4 py-3 z-10">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            <span class="font-semibold text-blue-600"
                                x-text="residences.length">{{ $residences->count() }}</span> résidence(s)
                            @if (isset($userLocation))
                                <span
                                    class="inline-flex items-center gap-1 ml-1 px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded-full text-xs">
                                    {{ \App\Services\UserLocationService::countryFlag($userLocation['country_code'] ?? 'CI') }}
                                    {{ $userLocation['city'] ?? 'Abidjan' }}
                                </span>
                            @endif
                        </p>
                        <select x-model="sortBy" @change="sortResidences()"
                            class="text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500">
                            <option value="distance">Plus proche</option>
                            <option value="price_asc">Prix croissant</option>
                            <option value="price_desc">Prix décroissant</option>
                            <option value="recent">Plus récent</option>
                        </select>
                    </div>
                </div>

                <!-- Liste des résidences -->
                <div class="p-4 space-y-4">
                    <!-- Résidences depuis le serveur (initial) -->
                    @forelse($residences as $residence)
                        <div class="residence-list-item bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
                            data-id="{{ $residence->id }}" @mouseenter="highlightMarker({{ $residence->id }})"
                            @mouseleave="unhighlightMarker()">
                            <a href="{{ route('residences.show', $residence) }}" class="flex relative">
                                {{-- Badge Sponsorisé --}}
                                @if (in_array($residence->id, $sponsoredIds ?? []) || $residence->isSponsored())
                                    <span
                                        class="absolute top-2 left-2 z-10 inline-flex items-center gap-1 px-2 py-0.5 bg-amber-500 text-white text-[10px] font-semibold rounded-full shadow-sm">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        Sponsorisé
                                    </span>
                                @endif
                                <!-- Image -->
                                <div class="w-32 h-28 shrink-0 bg-gray-200">
                                    @if ($residence->photos->isNotEmpty())
                                        <img loading="lazy"
                                            src="{{ storage_url($residence->photos->where('is_primary', true)->first()?->path ?? $residence->photos->first()?->path) }}"
                                            alt="{{ $residence->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <!-- Contenu -->
                                <div class="flex-1 p-3 flex flex-col justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 text-sm line-clamp-1">
                                            {{ $residence->name }}</h3>
                                        <p class="text-xs text-gray-500 flex items-center mt-1">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            {{ $residence->commune }}
                                            @if (isset($residence->distance))
                                                <span class="ml-1 text-blue-600">•
                                                    {{ number_format($residence->distance, 1) }} km</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-blue-600 font-bold text-sm">
                                            {{ number_format($residence->price_per_month, 0, ',', ' ') }} FCFA
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ ucfirst($residence->type) }}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune résidence trouvée</h3>
                            <p class="text-gray-500 text-sm">Essayez de modifier vos critères ou d'agrandir le rayon de
                                recherche.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if ($residences->hasPages())
                    <div class="p-4 border-t bg-white">
                        {{ $residences->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('searchPage', () => window.searchPage(@js([
    'commune' => $validated['commune'] ?? '',
    'latitude' => $validated['latitude'] ?? null,
    'longitude' => $validated['longitude'] ?? null,
    'radius' => $validated['radius'] ?? config('rezi.default_search_radius_km', 5),
    'showFilters' => !empty(array_filter($validated ?? [])),
    'sortBy' => $validated['sort'] ?? 'distance',
    'residences' => $residences->items(),
    'mapboxToken' => config('services.mapbox.access_token'),
    'searchUrl' => route('residences.search'),
    'defaultRadius' => config('rezi.default_search_radius_km', 5),
    'filters' => [
        'city' => $validated['city'] ?? '',
        'commune' => $validated['commune'] ?? '',
        'quartier' => $validated['quartier'] ?? '',
        'min_price' => $validated['min_price'] ?? '',
        'max_price' => $validated['max_price'] ?? '',
        'type' => $validated['type'] ?? '',
        'bedrooms' => $validated['bedrooms'] ?? '',
        'bathrooms' => $validated['bathrooms'] ?? '',
        'max_guests' => $validated['max_guests'] ?? '',
        'min_rating' => $validated['min_rating'] ?? '',
        'cancellation_policy' => $validated['cancellation_policy'] ?? '',
        'amenities' => $validated['amenities'] ?? [],
        'instant_book' => !empty($validated['instant_book']),
        'has_promotion' => !empty($validated['has_promotion']),
        'available_now' => !empty($validated['available_now']),
        'is_accessible' => !empty($validated['is_accessible']),
    ],
])));
            });
        </script>
    @endpush
</x-app-layout>
