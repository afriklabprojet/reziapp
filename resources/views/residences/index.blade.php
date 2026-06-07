<x-app-layout>
    @section('title', 'Résidences meublées à louer' . (request('commune') ? ' à ' . request('commune') : '') . ' - Rezi App')
    @section('description', 'Découvrez ' . ($residences->total() ?? '') . ' résidences meublées' . (request('commune') ? ' à ' . request('commune') : ' en Côte d\'Ivoire') . '. Recherche géolocalisée, photos, contact direct avec les propriétaires.')

    {{-- JSON-LD ItemList pour les pages de résultats --}}
    @push('meta')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Résidences meublées' . (request('commune') ? ' à ' . request('commune') : ''),
        'numberOfItems' => $residences->total(),
        'itemListElement' => $residences->map(fn ($r, $i) => [
            '@type' => 'ListItem',
            'position' => ($residences->currentPage() - 1) * $residences->perPage() + $i + 1,
            'url' => route('residences.show', $r),
            'name' => $r->name,
        ])->values()->toArray(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    @endpush

    <div class="min-h-screen bg-white sm:bg-gray-50" x-data="residenceIndex">
        {{-- Sticky Filter Bar --}}
        <div :class="{ 'shadow-lg': isSticky }"
            class="bg-white border-b border-gray-200 transition-shadow duration-300 sticky top-14 md:top-16 z-30">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8 py-2.5 sm:py-3">
                {{-- Quick Filters Row — scroll horizontal sur mobile, wrap sur desktop --}}
                <div class="flex items-center gap-3 overflow-x-auto scrollbar-hide sm:flex-wrap -mx-3 px-3 sm:mx-0 sm:px-0">
                    {{-- Search Input --}}
                    <form method="GET" action="{{ route('residences.index') }}" class="relative grow max-w-xs shrink-0 min-w-40"
                        id="quickSearchForm">
                        @foreach (request()->except(['q', 'page']) as $key => $value)
                            @if (is_array($value))
                                @foreach ($value as $v)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-200 focus:border-[#F16A00] focus:ring-[#F16A00] text-sm">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </form>

                    {{-- City Dropdown --}}
                    @if (isset($cities) && $cities->count() > 1)
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" type="button"
                                class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl hover:border-[#F16A00] transition-all text-sm {{ request('city') ? 'border-[#F16A00] bg-[#FFF4EB]' : '' }}">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="{{ request('city') ? 'text-[#CC5A00] font-medium' : 'text-gray-700' }}">
                                    {{ request('city') ?: 'Ville' }}
                                </span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                class="absolute top-full left-0 mt-2 w-56 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50 max-h-64 overflow-y-auto">
                                <a href="{{ route('residences.index', request()->except(['city', 'commune', 'page'])) }}"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#FFF4EB] hover:text-[#CC5A00]">
                                    Toutes les villes
                                </a>
                                @foreach ($cities as $city)
                                    <a href="{{ route('residences.index', array_merge(request()->except(['page', 'commune']), ['city' => $city])) }}"
                                        class="block px-4 py-2 text-sm hover:bg-[#FFF4EB] hover:text-[#CC5A00] {{ request('city') == $city ? 'bg-[#FFF4EB] text-[#CC5A00] font-medium' : 'text-gray-700' }}">
                                        {{ $city }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Commune Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" type="button"
                            class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl hover:border-[#F16A00] transition-all text-sm {{ request('commune') ? 'border-[#F16A00] bg-[#FFF4EB]' : '' }}">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            <span class="{{ request('commune') ? 'text-[#CC5A00] font-medium' : 'text-gray-700' }}">
                                {{ request('commune') ?: 'Commune' }}
                            </span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                            class="absolute top-full left-0 mt-2 w-56 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50 max-h-64 overflow-y-auto">
                            <a href="{{ route('residences.index', request()->except(['commune', 'page'])) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#FFF4EB] hover:text-[#CC5A00]">
                                Toutes les communes
                            </a>
                            @foreach ($communes as $commune)
                                <a href="{{ route('residences.index', array_merge(request()->except('page'), ['commune' => $commune])) }}"
                                    class="block px-4 py-2 text-sm hover:bg-[#FFF4EB] hover:text-[#CC5A00] {{ request('commune') == $commune ? 'bg-[#FFF4EB] text-[#CC5A00] font-medium' : 'text-gray-700' }}">
                                    {{ $commune }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Price Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" type="button"
                            class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl hover:border-[#F16A00] transition-all text-sm {{ request('max_price') ? 'border-[#F16A00] bg-[#FFF4EB]' : '' }}">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="{{ request('max_price') ? 'text-[#CC5A00] font-medium' : 'text-gray-700' }}">
                                @if (request('max_price'))
                                    ≤ {{ number_format(request('max_price'), 0, ',', ' ') }} F
                                @else
                                    Budget
                                @endif
                            </span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                            class="absolute top-full left-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50">
                            <a href="{{ route('residences.index', request()->except(['max_price', 'page'])) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#FFF4EB] hover:text-[#CC5A00]">
                                Tous les prix
                            </a>
                            @foreach ([50000 => '50 000 F', 100000 => '100 000 F', 150000 => '150 000 F', 200000 => '200 000 F', 300000 => '300 000 F', 500000 => '500 000 F'] as $value => $label)
                                <a href="{{ route('residences.index', array_merge(request()->except('page'), ['max_price' => $value])) }}"
                                    class="block px-4 py-2 text-sm hover:bg-[#FFF4EB] hover:text-[#CC5A00] {{ request('max_price') == $value ? 'bg-[#FFF4EB] text-[#CC5A00] font-medium' : 'text-gray-700' }}">
                                    ≤ {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Type Dropdown --}}
                    <div class="relative hidden sm:block" x-data="{ open: false }">
                        <button @click="open = !open" type="button"
                            class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl hover:border-[#F16A00] transition-all text-sm {{ request('type') ? 'border-[#F16A00] bg-[#FFF4EB]' : '' }}">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span class="{{ request('type') ? 'text-[#CC5A00] font-medium' : 'text-gray-700' }}">
                                {{ request('type') ? ucfirst(request('type')) : 'Type' }}
                            </span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                            class="absolute top-full left-0 mt-2 w-44 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50">
                            <a href="{{ route('residences.index', request()->except(['type', 'page'])) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#FFF4EB] hover:text-[#CC5A00]">
                                Tous les types
                            </a>
                            @foreach (['appartement' => 'Appartement', 'studio' => 'Studio', 'villa' => 'Villa', 'chambre' => 'Chambre', 'duplex' => 'Duplex'] as $key => $label)
                                <a href="{{ route('residences.index', array_merge(request()->except('page'), ['type' => $key])) }}"
                                    class="block px-4 py-2 text-sm hover:bg-[#FFF4EB] hover:text-[#CC5A00] {{ request('type') == $key ? 'bg-[#FFF4EB] text-[#CC5A00] font-medium' : 'text-gray-700' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Bedrooms Dropdown --}}
                    <div class="relative hidden md:block" x-data="{ open: false }">
                        <button @click="open = !open" type="button"
                            class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl hover:border-[#F16A00] transition-all text-sm {{ request('bedrooms') ? 'border-[#F16A00] bg-[#FFF4EB]' : '' }}">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="{{ request('bedrooms') ? 'text-[#CC5A00] font-medium' : 'text-gray-700' }}">
                                {{ request('bedrooms') ? request('bedrooms') . '+ ch.' : 'Chambres' }}
                            </span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                            class="absolute top-full left-0 mt-2 w-40 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50">
                            <a href="{{ route('residences.index', request()->except(['bedrooms', 'page'])) }}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#FFF4EB] hover:text-[#CC5A00]">
                                Toutes
                            </a>
                            @foreach (range(1, 4) as $num)
                                <a href="{{ route('residences.index', array_merge(request()->except('page'), ['bedrooms' => $num])) }}"
                                    class="block px-4 py-2 text-sm hover:bg-[#FFF4EB] hover:text-[#CC5A00] {{ request('bedrooms') == $num ? 'bg-[#FFF4EB] text-[#CC5A00] font-medium' : 'text-gray-700' }}">
                                    {{ $num }}+ chambre{{ $num > 1 ? 's' : '' }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- More Filters Button --}}
                    <button @click="showAdvancedFilters = !showAdvancedFilters" type="button"
                        :class="{ 'bg-[#FFF4EB] border-[#F16A00] text-[#CC5A00]': showAdvancedFilters || hasActiveFilters }"
                        class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 rounded-xl hover:border-[#F16A00] transition-all text-sm">
                        <svg class="w-4 h-4"
                            :class="showAdvancedFilters || hasActiveFilters ? 'text-[#F16A00]' : 'text-gray-400'"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <span>Plus de filtres</span>
                        <span x-show="activeFiltersCount > 0"
                            class="flex items-center justify-center w-5 h-5 bg-[#F16A00] text-white text-xs font-bold rounded-full"
                            x-text="activeFiltersCount"></span>
                    </button>

                    {{-- Spacer --}}
                    <div class="grow"></div>

                    {{-- View Toggle --}}
                    <div class="hidden sm:flex items-center bg-gray-100 rounded-xl p-1">
                        <button @click="setViewMode('grid')" type="button"
                            :class="viewMode === 'grid' ? 'bg-white shadow-sm text-[#CC5A00]' :
                                'text-gray-500 hover:text-gray-700'"
                            class="p-2 rounded-lg transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                        <button @click="setViewMode('list')" type="button"
                            :class="viewMode === 'list' ? 'bg-white shadow-sm text-[#CC5A00]' :
                                'text-gray-500 hover:text-gray-700'"
                            class="p-2 rounded-lg transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </button>
                    </div>

                    {{-- Map View Link --}}
                    <a href="{{ route('residences.map', request()->query()) }}"
                        class="flex items-center gap-2 px-4 py-2.5 bg-[#F16A00] text-white rounded-xl hover:bg-[#CC5A00] transition-colors font-medium text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <span class="hidden md:inline">Carte</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Advanced Filters Panel --}}
        <div x-show="showAdvancedFilters" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2" class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <form method="GET" action="{{ route('residences.index') }}" id="advancedFilterForm">
                    {{-- Preserve quick filters --}}
                    @foreach (['q', 'commune', 'max_price', 'type', 'bedrooms'] as $filter)
                        @if (request($filter))
                            <input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">
                        @endif
                    @endforeach

                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        {{-- Bathrooms --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Salles de bain</label>
                            <select name="bathrooms"
                                class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#F16A00] focus:border-[#F16A00]">
                                <option value="">Peu importe</option>
                                @foreach (range(1, 3) as $num)
                                    <option value="{{ $num }}"
                                        {{ request('bathrooms') == $num ? 'selected' : '' }}>{{ $num }}+
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Min Price --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Prix min</label>
                            <input type="number" name="min_price" value="{{ request('min_price') }}"
                                placeholder="0" step="5000"
                                class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#F16A00] focus:border-[#F16A00]">
                        </div>

                        {{-- Min Surface --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Surface min (m²)</label>
                            <input type="number" name="min_surface" value="{{ request('min_surface') }}"
                                placeholder="0"
                                class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#F16A00] focus:border-[#F16A00]">
                        </div>

                        {{-- Sort --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Trier par</label>
                            <select name="sort"
                                class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#F16A00] focus:border-[#F16A00]">
                                <option value="recent" {{ request('sort', 'recent') == 'recent' ? 'selected' : '' }}>
                                    Plus récent</option>
                                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Prix
                                    croissant</option>
                                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>
                                    Prix décroissant</option>
                                <option value="surface" {{ request('sort') == 'surface' ? 'selected' : '' }}>Surface
                                </option>
                            </select>
                        </div>
                    </div>

                    {{-- Amenities --}}
                    <div class="mt-4">
                        <label class="block text-xs font-medium text-gray-500 mb-2">Équipements</label>
                        <div class="flex flex-wrap gap-2">
                            @php
                                $amenitiesList = [
                                    'wifi' => 'WiFi',
                                    'climatisation' => 'Climatisation',
                                    'parking' => 'Parking',
                                    'piscine' => 'Piscine',
                                    'gardien' => 'Gardien',
                                    'meuble' => 'Meublé',
                                    'cuisine' => 'Cuisine équipée',
                                    'balcon' => 'Balcon',
                                    'groupe' => 'Groupe électrogène',
                                ];
                                $selectedAmenities = request('amenities', []);
                            @endphp
                            @foreach ($amenitiesList as $key => $label)
                                <label
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border cursor-pointer transition-colors
                                              {{ in_array($key, $selectedAmenities) ? 'bg-[#FFE7D1] border-[#F16A00] text-[#CC5A00]' : 'border-gray-200 hover:border-[#FFD0A3] bg-white' }}">
                                    <input type="checkbox" name="amenities[]" value="{{ $key }}"
                                        {{ in_array($key, $selectedAmenities) ? 'checked' : '' }} class="sr-only">
                                    <span class="text-sm">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-4 flex items-center gap-3">
                        <button type="submit"
                            class="px-6 py-2 bg-[#F16A00] text-white text-sm font-medium rounded-xl hover:bg-[#CC5A00] transition-colors">
                            Appliquer
                        </button>
                        <a href="{{ route('residences.index') }}"
                            class="px-6 py-2 text-gray-600 text-sm font-medium hover:text-gray-800 transition-colors">
                            Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Active Filters Tags --}}
        @if (request()->hasAny([
                'q',
                'commune',
                'min_price',
                'max_price',
                'type',
                'bedrooms',
                'bathrooms',
                'amenities',
                'min_surface',
            ]))
            <div class="bg-white border-b border-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm text-gray-500">Filtres:</span>

                        @if (request('q'))
                            <a href="{{ route('residences.index', request()->except(['q', 'page'])) }}"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-[#FFE7D1] text-[#A34700] rounded-full text-sm hover:bg-[#FFD0A3] transition-colors">
                                "{{ request('q') }}"
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif

                        @if (request('commune'))
                            <a href="{{ route('residences.index', request()->except(['commune', 'page'])) }}"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-[#FFE7D1] text-[#A34700] rounded-full text-sm hover:bg-[#FFD0A3] transition-colors">
                                {{ request('commune') }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif

                        @if (request('type'))
                            <a href="{{ route('residences.index', request()->except(['type', 'page'])) }}"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-[#FFE7D1] text-[#A34700] rounded-full text-sm hover:bg-[#FFD0A3] transition-colors">
                                {{ ucfirst(request('type')) }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif

                        @if (request('max_price'))
                            <a href="{{ route('residences.index', request()->except(['max_price', 'page'])) }}"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-[#FFE7D1] text-[#A34700] rounded-full text-sm hover:bg-[#FFD0A3] transition-colors">
                                ≤ {{ number_format(request('max_price'), 0, ',', ' ') }} F
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif

                        @if (request('bedrooms'))
                            <a href="{{ route('residences.index', request()->except(['bedrooms', 'page'])) }}"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-[#FFE7D1] text-[#A34700] rounded-full text-sm hover:bg-[#FFD0A3] transition-colors">
                                {{ request('bedrooms') }}+ ch.
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif

                        <a href="{{ route('residences.index') }}"
                            class="text-sm text-[#CC5A00] hover:text-[#A34700] font-medium ml-2">
                            Tout effacer
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="py-4 sm:py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">

                {{-- Results Header --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4 mb-4 sm:mb-6">
                    <div>
                        <h1 class="text-base sm:text-2xl font-semibold sm:font-bold text-gray-900">
                            @if (request('commune'))
                                Résidences à {{ request('commune') }}
                            @elseif(request('q'))
                                Résultats pour "{{ request('q') }}"
                            @elseif(isset($userLocation) && !empty($userLocation['city']))
                                Résidences à {{ $userLocation['city'] }}
                            @else
                                Toutes les résidences
                            @endif
                        </h1>
                        <p class="text-gray-500 text-sm mt-0.5">
                            <span class="font-semibold text-[#F16A00]">{{ $residences->total() }}</span>
                            résidence{{ $residences->total() > 1 ? 's' : '' }}
                            @if (isset($userLocation))
                                <span
                                    class="inline-flex items-center gap-1 ml-1 px-2 py-0.5 bg-[#FFF4EB] text-[#A34700] rounded-full text-xs font-medium">
                                    {{ \App\Services\UserLocationService::countryFlag($userLocation['country_code'] ?? 'CI') }}
                                    {{ $userLocation['city'] ?? 'Abidjan' }}
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Results --}}
                @if ($residences->isNotEmpty())
                    {{-- Pagination metadata for Alpine --}}
                    <div data-has-more="{{ $residences->hasMorePages() ? 'true' : 'false' }}"
                        data-current-page="{{ $residences->currentPage() }}" class="hidden"></div>

                    {{-- Grid View --}}
                    <div x-show="viewMode === 'grid'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        id="residences-grid"
                        class="grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-4 gap-y-6 sm:gap-6">
                        @foreach ($residences as $residence)
                            {{-- Mobile: carte style Airbnb (flat, no shadow) --}}
                            <div class="sm:hidden">
                                <x-residence-card-mobile :residence="$residence" />
                            </div>
                            {{-- Desktop: carte standard --}}
                            <div class="hidden sm:block">
                                <x-residence-card :residence="$residence" />
                            </div>
                        @endforeach
                    </div>

                    {{-- List View --}}
                    <div x-show="viewMode === 'list'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        id="residences-list" class="space-y-4">
                        @foreach ($residences as $residence)
                            <x-residence-card-horizontal :residence="$residence" />
                        @endforeach
                    </div>

                    {{-- Load More --}}
                    <div class="mt-10 text-center" x-show="hasMore">
                        <button @click="loadMore()" :disabled="loading"
                            class="inline-flex items-center gap-2 px-8 py-3 bg-white border-2 border-[#FFD0A3] text-[#CC5A00] font-semibold rounded-xl hover:bg-[#FFF4EB] hover:border-[#FFD0A3] transition-all duration-200 disabled:opacity-50 disabled:cursor-wait shadow-sm">
                            <template x-if="!loading">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                    Voir plus de résidences
                                </span>
                            </template>
                            <template x-if="loading">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Chargement…
                                </span>
                            </template>
                        </button>
                    </div>

                    {{-- All loaded message --}}
                    <div x-show="!hasMore && currentPage > 1" class="mt-8 text-center" x-cloak>
                        <p class="text-sm text-gray-500 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Toutes les résidences sont affichées
                        </p>
                    </div>

                    {{-- Fallback pagination for no-JS / SEO --}}
                    <noscript>
                        <div class="mt-8">
                            {{ $residences->withQueryString()->links() }}
                        </div>
                    </noscript>
                @else
                    {{-- Empty State --}}
                    <div class="bg-white rounded-2xl shadow-sm p-12 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Aucune résidence trouvée</h3>
                        <p class="text-gray-500 mb-6 max-w-md mx-auto">
                            Nous n'avons pas trouvé de résidences correspondant à vos critères.
                            Essayez de modifier vos filtres ou de rechercher dans une autre zone.
                        </p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                            <a href="{{ route('residences.index') }}"
                                class="px-6 py-3 bg-[#F16A00] text-white font-medium rounded-xl hover:bg-[#CC5A00] transition-colors">
                                Voir toutes les résidences
                            </a>
                            <a href="{{ route('residences.map') }}"
                                class="px-6 py-3 bg-white text-gray-700 font-medium rounded-xl border border-gray-300 hover:bg-gray-50 transition-colors">
                                Explorer sur la carte
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    @endpush
</x-app-layout>
