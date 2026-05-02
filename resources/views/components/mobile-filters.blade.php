{{-- Modal de filtres optimisé mobile --}}
@props(['communes' => [], 'amenities' => []])

<div x-data="filterModal()" x-cloak>
    {{-- Bouton d'ouverture --}}
    <button @click="open = true"
        class="fixed bottom-20 right-4 z-30 md:hidden w-14 h-14 bg-[#e00b41] text-white rounded-full shadow-lg flex items-center justify-center active:scale-95 transition-transform">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
        </svg>
        <span x-show="activeFiltersCount > 0"
            class="absolute -top-1 -right-1 w-5 h-5 bg-white text-[#e00b41] text-xs font-bold rounded-full flex items-center justify-center"
            x-text="activeFiltersCount"></span>
    </button>

    {{-- Modal plein écran --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-white md:hidden overflow-hidden" role="dialog" aria-modal="true"
        aria-label="Filtres de recherche">

        {{-- Header --}}
        <div class="sticky top-0 bg-white border-b z-10">
            <div class="flex items-center justify-between px-4 h-14">
                <button @click="open = false" class="p-2 -ml-2" aria-label="Fermer les filtres">
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <h2 class="font-semibold text-gray-900">Filtres</h2>
                <button @click="resetFilters()" class="text-[#e00b41] font-medium text-sm">
                    Réinitialiser
                </button>
            </div>
        </div>

        {{-- Contenu scrollable --}}
        <div class="overflow-y-auto" style="height: calc(100vh - 140px);">
            <div class="p-4 space-y-6">

                {{-- Prix --}}
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">Fourchette de prix</h3>
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label class="text-xs text-gray-500">Min</label>
                            <div class="relative mt-1">
                                <input type="number" x-model="filters.price_min" placeholder="0"
                                    class="w-full pl-3 pr-12 py-2.5 border border-gray-200 rounded-xl focus:border-[#ff385c] focus:ring-[#ff385c]">
                                <span
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">FCFA</span>
                            </div>
                        </div>
                        <span class="mt-5 text-gray-400">—</span>
                        <div class="flex-1">
                            <label class="text-xs text-gray-500">Max</label>
                            <div class="relative mt-1">
                                <input type="number" x-model="filters.price_max" placeholder="∞"
                                    class="w-full pl-3 pr-12 py-2.5 border border-gray-200 rounded-xl focus:border-[#ff385c] focus:ring-[#ff385c]">
                                <span
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">FCFA</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Type de logement --}}
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">Type de logement</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="type in types" :key="type.value">
                            <button @click="toggleType(type.value)"
                                class="px-4 py-2 rounded-full text-sm font-medium transition-colors"
                                :class="filters.types.includes(type.value) ?
                                    'bg-[#e00b41] text-white' :
                                    'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                x-text="type.label"></button>
                        </template>
                    </div>
                </div>

                {{-- Chambres --}}
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">Chambres</h3>
                    <div class="flex gap-2">
                        <template x-for="n in [1, 2, 3, 4, '5+']" :key="n">
                            <button @click="filters.bedrooms = filters.bedrooms === n ? null : n"
                                class="flex-1 py-2.5 rounded-xl text-sm font-medium transition-colors"
                                :class="filters.bedrooms === n ?
                                    'bg-[#e00b41] text-white' :
                                    'bg-gray-100 text-gray-700'"
                                x-text="n"></button>
                        </template>
                    </div>
                </div>

                {{-- Voyageurs --}}
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">Voyageurs</h3>
                    <div class="flex items-center justify-between bg-gray-100 rounded-xl p-2">
                        <button @click="filters.guests = Math.max(1, filters.guests - 1)"
                            class="w-10 h-10 rounded-lg bg-white shadow-sm flex items-center justify-center active:bg-gray-50">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                            </svg>
                        </button>
                        <span class="text-lg font-semibold" x-text="filters.guests + ' voyageur(s)'"></span>
                        <button @click="filters.guests = Math.min(20, filters.guests + 1)"
                            class="w-10 h-10 rounded-lg bg-white shadow-sm flex items-center justify-center active:bg-gray-50">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Zone / Commune --}}
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">Zone</h3>
                    <select x-model="filters.commune"
                        class="w-full py-3 px-4 border border-gray-200 rounded-xl focus:border-[#ff385c] focus:ring-[#ff385c]">
                        <option value="">Toutes les zones</option>
                        @foreach ($communes as $commune)
                            <option value="{{ $commune }}">{{ $commune }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Équipements --}}
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">Équipements</h3>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($amenities as $amenity)
                            <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer">
                                <input type="checkbox" value="{{ $amenity->id }}" x-model="filters.amenities"
                                    class="w-5 h-5 rounded text-[#e00b41] focus:ring-[#ff385c] border-gray-300">
                                <span class="text-sm text-gray-700">{{ $amenity->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Options --}}
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">Options</h3>
                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-3 bg-gray-50 rounded-xl cursor-pointer">
                            <span class="text-sm text-gray-700">Réservation instantanée</span>
                            <input type="checkbox" x-model="filters.instant_book"
                                class="w-5 h-5 rounded text-[#e00b41] focus:ring-[#ff385c] border-gray-300">
                        </label>
                        <label class="flex items-center justify-between p-3 bg-gray-50 rounded-xl cursor-pointer">
                            <span class="text-sm text-gray-700">Résidences vérifiées</span>
                            <input type="checkbox" x-model="filters.verified"
                                class="w-5 h-5 rounded text-[#e00b41] focus:ring-[#ff385c] border-gray-300">
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer avec bouton appliquer --}}
        <div class="fixed bottom-0 inset-x-0 bg-white border-t p-4 pb-safe">
            <button @click="applyFilters()"
                class="w-full py-3.5 bg-[#e00b41] text-white font-semibold rounded-xl active:bg-[#b5083a] transition-colors">
                Afficher <span x-text="resultsCount"></span> résultats
            </button>
        </div>
    </div>
</div>
