<x-app-layout>
    @section('title', 'Carte des résidences - REZI')

    <div class="h-[calc(100vh-64px)] flex flex-col"
        x-data="{
            residences: @js(
                $residences->map(fn($r) => [
                    'id' => $r->id,
                    'title' => $r->name,
                    'price' => $r->price,
                    'price_label' => '/jour',
                    'price_per_day' => $r->price_per_day,
                    'price_per_month' => $r->price_per_month,
                    'thumbnail' => $r->primaryPhoto?->url,
                    'commune' => $r->commune,
                    'quartier' => $r->quartier,
                    'type' => $r->type,
                    'type_location' => $r->type_location,
                    'is_available' => (bool) $r->is_available,
                    'bedrooms' => $r->bedrooms,
                    'bathrooms' => $r->bathrooms,
                    'max_guests' => $r->max_guests,
                    'average_rating' => (float) $r->average_rating,
                    'reviews_count' => (int) $r->reviews_count,
                    'instant_book' => (bool) $r->instant_book,
                    'is_verified' => (bool) $r->is_verified,
                    'location' => [
                        'latitude' => $r->latitude,
                        'longitude' => $r->longitude,
                        'commune' => $r->commune,
                        'quartier' => $r->quartier,
                    ],
                ])->toArray()
            ),
            filteredResidences: [],
            filterCommune: '',
            filterPriceMin: {{ $priceMin }},
            filterPriceMax: {{ $priceMax }},
            priceMin: {{ $priceMin }},
            priceMax: {{ $priceMax }},
            filterType: '',
            filterAvailability: 'available',
            filterBedrooms: '',
            filterRating: '',
            filterInstantBook: false,
            showSidebar: window.innerWidth >= 1024,
            hoveredId: null,
            sortBy: 'price_asc',
            showFilters: false,
            searchQuery: '',

            init() {
                this.applyFilters();
                const watchers = ['filterCommune','filterPriceMin','filterPriceMax','filterType','filterAvailability','filterBedrooms','filterRating','filterInstantBook','sortBy','searchQuery'];
                watchers.forEach(w => this.$watch(w, () => this.applyFilters()));

                window.addEventListener('map:residence-hover', (e) => this.hoveredId = e.detail.id);
                window.addEventListener('map:residence-unhover', () => this.hoveredId = null);
            },

            applyFilters() {
                let result = [...this.residences];

                if (this.searchQuery.trim()) {
                    const q = this.searchQuery.trim().toLowerCase();
                    result = result.filter(r =>
                        (r.title?.toLowerCase().includes(q)) ||
                        (r.commune?.toLowerCase().includes(q)) ||
                        (r.quartier?.toLowerCase().includes(q))
                    );
                }
                if (this.filterCommune) {
                    result = result.filter(r => r.commune === this.filterCommune);
                }
                if (this.filterType) {
                    result = result.filter(r => r.type === this.filterType);
                }
                if (this.filterAvailability === 'available') {
                    result = result.filter(r => r.is_available);
                } else if (this.filterAvailability === 'unavailable') {
                    result = result.filter(r => !r.is_available);
                }
                if (this.filterBedrooms) {
                    const min = parseInt(this.filterBedrooms);
                    result = result.filter(r => (r.bedrooms || 0) >= min);
                }
                if (this.filterRating) {
                    const min = parseFloat(this.filterRating);
                    result = result.filter(r => (r.average_rating || 0) >= min);
                }
                if (this.filterInstantBook) {
                    result = result.filter(r => r.instant_book);
                }
                result = result.filter(r => r.price >= this.filterPriceMin && r.price <= this.filterPriceMax);

                switch (this.sortBy) {
                    case 'price_asc': result.sort((a, b) => a.price - b.price); break;
                    case 'price_desc': result.sort((a, b) => b.price - a.price); break;
                    case 'rating': result.sort((a, b) => (b.average_rating || 0) - (a.average_rating || 0)); break;
                    case 'newest': result.sort((a, b) => b.id - a.id); break;
                }

                this.filteredResidences = result;
                window.dispatchEvent(new CustomEvent('map:update-residences', { detail: { residences: result } }));
            },

            resetFilters() {
                this.filterCommune = '';
                this.filterType = '';
                this.filterAvailability = 'available';
                this.filterPriceMin = this.priceMin;
                this.filterPriceMax = this.priceMax;
                this.filterBedrooms = '';
                this.filterRating = '';
                this.filterInstantBook = false;
                this.searchQuery = '';
            },

            get activeFilterCount() {
                let count = 0;
                if (this.filterCommune) count++;
                if (this.filterType) count++;
                if (this.filterAvailability !== 'available') count++;
                if (this.filterBedrooms) count++;
                if (this.filterRating) count++;
                if (this.filterInstantBook) count++;
                if (this.filterPriceMin > this.priceMin || this.filterPriceMax < this.priceMax) count++;
                if (this.searchQuery.trim()) count++;
                return count;
            },

            get stats() {
                const total = this.filteredResidences.length;
                const available = this.filteredResidences.filter(r => r.is_available).length;
                const avgPrice = total > 0 ? Math.round(this.filteredResidences.reduce((sum, r) => sum + r.price, 0) / total) : 0;
                const avgRating = total > 0 ? (this.filteredResidences.reduce((sum, r) => sum + (r.average_rating || 0), 0) / total).toFixed(1) : '—';
                return { total, available, avgPrice, avgRating };
            },

            formatPrice(price) {
                return new Intl.NumberFormat('fr-FR').format(price);
            },

            highlightOnMap(id) {
                window.dispatchEvent(new CustomEvent('map:highlight-residence', { detail: { id } }));
            }
        }">

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- TOP BAR — Stats + Contrôles --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="bg-white border-b border-gray-200 px-3 sm:px-4 py-2 shrink-0 z-20">
            <div class="flex items-center justify-between gap-2">
                {{-- Stats rapides --}}
                <div class="flex items-center gap-3 sm:gap-5 overflow-x-auto scrollbar-hide">
                    {{-- Total --}}
                    <div class="flex items-center gap-1.5 shrink-0">
                        <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="leading-tight">
                            <span class="text-sm font-bold text-gray-900" x-text="stats.total"></span>
                            <span class="text-[10px] text-gray-400 block">logements</span>
                        </div>
                    </div>
                    {{-- Disponibles --}}
                    <div class="flex items-center gap-1.5 shrink-0">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="leading-tight">
                            <span class="text-sm font-bold text-emerald-600" x-text="stats.available"></span>
                            <span class="text-[10px] text-gray-400 block">disponibles</span>
                        </div>
                    </div>
                    {{-- Prix moyen --}}
                    <div class="hidden sm:flex items-center gap-1.5 shrink-0">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="leading-tight">
                            <span class="text-sm font-bold text-blue-600" x-text="formatPrice(stats.avgPrice)"></span>
                            <span class="text-[10px] text-gray-400 block">FCFA/jour moy.</span>
                        </div>
                    </div>
                    {{-- Note moyenne --}}
                    <div class="hidden md:flex items-center gap-1.5 shrink-0">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <div class="leading-tight">
                            <span class="text-sm font-bold text-amber-600" x-text="stats.avgRating"></span>
                            <span class="text-[10px] text-gray-400 block">note moy.</span>
                        </div>
                    </div>
                </div>

                {{-- Contrôles droite --}}
                <div class="flex items-center gap-2 shrink-0">
                    <button @click="showFilters = !showFilters"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all border"
                        :class="showFilters ? 'bg-orange-50 text-orange-600 border-orange-200' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span class="hidden sm:inline">Filtres</span>
                        <span x-show="activeFilterCount > 0"
                            class="w-5 h-5 rounded-full bg-orange-500 text-white text-[10px] font-bold flex items-center justify-center"
                            x-text="activeFilterCount"></span>
                    </button>
                    <button @click="showSidebar = !showSidebar"
                        class="lg:hidden inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all border"
                        :class="showSidebar ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'">
                        <template x-if="showSidebar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </template>
                        <template x-if="!showSidebar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                        </template>
                        <span x-text="showSidebar ? 'Carte' : 'Liste'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- FILTRES DÉPLIANTS --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div x-show="showFilters" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="bg-white border-b border-gray-200 px-4 py-3 shrink-0 z-10 shadow-sm">

            {{-- Recherche texte --}}
            <div class="mb-3">
                <div class="relative">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" x-model.debounce.300ms="searchQuery"
                        placeholder="Rechercher par nom, commune, quartier..."
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-gray-50">
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Commune</label>
                    <select x-model="filterCommune" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                        <option value="">Toutes</option>
                        @foreach ($communes as $commune)
                            <option value="{{ $commune }}">{{ $commune }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Type</label>
                    <select x-model="filterType" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                        <option value="">Tous</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Chambres</label>
                    <select x-model="filterBedrooms" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                        <option value="">Toutes</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Note min.</label>
                    <select x-model="filterRating" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white">
                        <option value="">Toutes</option>
                        <option value="3">3+ &#9733;</option>
                        <option value="4">4+ &#9733;</option>
                        <option value="4.5">4.5+ &#9733;</option>
                    </select>
                </div>
                <div class="col-span-2 sm:col-span-1 lg:col-span-2">
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">
                        Prix : <span class="text-gray-600" x-text="formatPrice(filterPriceMin) + ' – ' + formatPrice(filterPriceMax) + ' FCFA/j'"></span>
                    </label>
                    <div class="flex gap-2 items-center">
                        <input type="range" x-model.number="filterPriceMin" :min="priceMin" :max="priceMax" step="1000" class="flex-1 accent-orange-500 h-2">
                        <input type="range" x-model.number="filterPriceMax" :min="priceMin" :max="priceMax" step="1000" class="flex-1 accent-orange-500 h-2">
                    </div>
                </div>
            </div>

            {{-- Actions rapides --}}
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="flex gap-1">
                        <button @click="filterAvailability = 'available'"
                            :class="filterAvailability === 'available' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300'"
                            class="text-xs font-medium py-1.5 px-3 rounded-full border transition-all inline-flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Disponibles
                        </button>
                        <button @click="filterAvailability = 'all'"
                            :class="filterAvailability === 'all' ? 'bg-gray-100 text-gray-700 border-gray-400' : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300'"
                            class="text-xs font-medium py-1.5 px-3 rounded-full border transition-all">
                            Toutes
                        </button>
                    </div>
                    <button @click="filterInstantBook = !filterInstantBook"
                        :class="filterInstantBook ? 'bg-amber-50 text-amber-700 border-amber-300' : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300'"
                        class="text-xs font-medium py-1.5 px-3 rounded-full border transition-all inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                        </svg>
                        Instant
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="resetFilters()" class="text-xs text-orange-500 hover:text-orange-600 font-semibold px-2 py-1">Réinitialiser</button>
                    <button @click="showFilters = false" class="text-xs text-gray-400 hover:text-gray-600 font-medium px-2 py-1">Fermer</button>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- CONTENU PRINCIPAL — Sidebar + Carte --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden min-h-0">

            {{-- ─── SIDEBAR ─── --}}
            <div class="lg:w-105 xl:w-115 bg-white border-r border-gray-200 flex flex-col overflow-hidden"
                :class="showSidebar ? 'flex-1 lg:flex-none' : 'hidden lg:flex'">

                <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-gray-50/50 shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">
                            <span x-text="filteredResidences.length" class="font-bold text-orange-500"></span>
                            résultat<span x-show="filteredResidences.length > 1">s</span>
                        </span>
                        <span x-show="activeFilterCount > 0" class="text-[10px] text-gray-400">
                            &bull; <span x-text="activeFilterCount"></span> filtre<span x-show="activeFilterCount > 1">s</span>
                        </span>
                    </div>
                    <select x-model="sortBy"
                        class="text-xs border border-gray-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-orange-500 bg-white font-medium">
                        <option value="price_asc">Prix croissant</option>
                        <option value="price_desc">Prix décroissant</option>
                        <option value="rating">Meilleures notes</option>
                        <option value="newest">Plus récents</option>
                    </select>
                </div>

                <div class="flex-1 overflow-y-auto p-3 space-y-2 relative">
                    <template x-for="residence in filteredResidences" :key="residence.id">
                        <a :href="'/residences/' + residence.id"
                            class="block bg-white rounded-xl border border-gray-100 hover:border-orange-200 hover:shadow-md transition-all duration-200 overflow-hidden group"
                            :class="{
                                'border-orange-400 shadow-md ring-2 ring-orange-100': hoveredId === residence.id,
                                'opacity-50': !residence.is_available,
                            }"
                            @mouseenter="highlightOnMap(residence.id)"
                            @mouseleave="highlightOnMap(null)">
                            <div class="flex gap-3 p-3">
                                <div class="w-28 h-24 rounded-lg overflow-hidden bg-gray-100 shrink-0 relative">
                                    <img :src="residence.thumbnail || '/images/placeholder-residence.jpg'"
                                        :alt="residence.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                        :class="!residence.is_available ? 'grayscale' : ''">
                                    <div class="absolute top-1.5 left-1.5 flex flex-col gap-1">
                                        <span x-show="residence.is_available"
                                            class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-emerald-500 text-white shadow-sm">
                                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                            Dispo
                                        </span>
                                        <span x-show="!residence.is_available"
                                            class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-red-500 text-white shadow-sm">
                                            Indispo
                                        </span>
                                    </div>
                                    <div class="absolute top-1.5 right-1.5 flex flex-col gap-1">
                                        <span x-show="residence.instant_book"
                                            class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-amber-400 text-amber-900 shadow-sm"
                                            title="Réservation immédiate">
                                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" /></svg>
                                        </span>
                                        <span x-show="residence.is_verified"
                                            class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-blue-500 text-white shadow-sm"
                                            title="Vérifié REZI">
                                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0 flex flex-col justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 text-sm line-clamp-1" x-text="residence.title"></h3>
                                        <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                            <svg class="w-3 h-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
                                            <span x-text="(residence.commune || '') + (residence.quartier ? ', ' + residence.quartier : '')" class="truncate"></span>
                                        </p>
                                        <div class="flex items-center gap-2 mt-1.5 text-[10px] text-gray-400">
                                            <span x-show="residence.bedrooms" class="inline-flex items-center gap-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                                <span x-text="residence.bedrooms + ' ch.'"></span>
                                            </span>
                                            <span x-show="residence.bathrooms" class="inline-flex items-center gap-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                                                <span x-text="residence.bathrooms + ' sdb'"></span>
                                            </span>
                                            <span x-show="residence.max_guests" class="inline-flex items-center gap-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                <span x-text="residence.max_guests + ' pers.'"></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between">
                                        <span class="text-orange-500 font-bold text-sm">
                                            <span x-text="formatPrice(residence.price)"></span>
                                            <span class="text-[10px] font-normal text-gray-400">FCFA/jour</span>
                                        </span>
                                        <div x-show="residence.reviews_count > 0" class="flex items-center gap-0.5">
                                            <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                            <span class="text-xs font-semibold text-gray-700" x-text="residence.average_rating.toFixed(1)"></span>
                                            <span class="text-[10px] text-gray-400" x-text="'(' + residence.reviews_count + ')'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </template>

                    <div x-show="filteredResidences.length === 0" class="text-center py-16">
                        <div class="w-20 h-20 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <p class="text-gray-500 text-sm font-medium">Aucune résidence trouvée</p>
                        <p class="text-gray-400 text-xs mt-1">Essayez de modifier vos filtres</p>
                        <button @click="resetFilters()" class="mt-3 text-orange-500 text-sm font-semibold hover:underline inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            Réinitialiser les filtres
                        </button>
                    </div>

                    <div x-show="showSidebar" class="lg:hidden sticky bottom-3 flex justify-center pt-4 z-10">
                        <button @click="showSidebar = false"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white rounded-full shadow-xl text-sm font-semibold hover:bg-gray-800 active:scale-95 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                            Voir la carte
                        </button>
                    </div>
                </div>
            </div>

            {{-- ─── CARTE MAPBOX ─── --}}
            <div class="flex-1 relative min-h-0" :class="showSidebar ? 'hidden lg:block' : 'flex-1'">
                <x-map-search
                    :residences="$residences->map(fn($r) => [
                        'id' => $r->id,
                        'title' => $r->name,
                        'price' => $r->price,
                        'price_label' => '/jour',
                        'thumbnail' => $r->primaryPhoto?->url,
                        'commune' => $r->commune,
                        'type' => $r->type,
                        'is_available' => (bool) $r->is_available,
                        'average_rating' => (float) $r->average_rating,
                        'reviews_count' => (int) $r->reviews_count,
                        'instant_book' => (bool) $r->instant_book,
                        'location' => [
                            'latitude' => $r->latitude,
                            'longitude' => $r->longitude,
                            'commune' => $r->commune,
                            'quartier' => $r->quartier,
                        ],
                    ])->toArray()"
                    :center="['lat' => config('rezi.default_latitude'), 'lng' => config('rezi.default_longitude')]"
                    :radius="config('rezi.default_search_radius_km')"
                    height="h-full"
                    class="h-full rounded-none!"
                    :showRadiusCircle="false"
                />

                <div x-show="!showSidebar" class="lg:hidden absolute bottom-4 left-1/2 -translate-x-1/2 z-20">
                    <button @click="showSidebar = true"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-gray-800 rounded-full shadow-xl border border-gray-200 text-sm font-semibold hover:shadow-2xl active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
                        Liste (<span x-text="filteredResidences.length"></span>)
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
