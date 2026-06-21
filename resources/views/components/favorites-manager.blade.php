{{-- Composant Gestionnaire de Favoris avec Dropdown --}}
@props([
    'position' => 'header', // header, sidebar, footer
])

<div
    x-data="favoritesManager({{ alpine_encode(['isAuthenticated' => auth()->check()]) }})"
    x-init="init()"
    class="relative"
    {{ $attributes }}
>
    {{-- Bouton Trigger --}}
    <button
        @click="toggleDropdown()"
        class="relative flex items-center gap-2 px-3 py-2 rounded-xl transition-all duration-300
               hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500"
        :class="{ 'bg-red-50 dark:bg-red-900/20': isOpen }"
        aria-label="Mes favoris"
        :aria-expanded="isOpen"
    >
        {{-- Icône Cœur --}}
        <div class="relative">
            <svg
                class="w-6 h-6 transition-colors duration-300"
                :class="favorites.length > 0 ? 'text-red-500' : 'text-gray-500'"
                fill="none"
                :fill="favorites.length > 0 ? 'currentColor' : 'none'"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>

            {{-- Badge compteur --}}
            <span
                x-show="favorites.length > 0"
                x-transition
                data-favorites-count
                class="absolute -top-1.5 -right-1.5 min-w-4.5 h-4.5 flex items-center justify-center
                       bg-red-500 text-white text-xs font-bold rounded-full px-1"
                x-text="favorites.length > 99 ? '99+' : favorites.length"
            ></span>
        </div>

        <span class="hidden sm:inline text-sm font-medium text-gray-700 dark:text-gray-300">
            Favoris
        </span>

        {{-- Chevron --}}
        <svg
            class="w-4 h-4 text-gray-400 transition-transform duration-200"
            :class="{ 'rotate-180': isOpen }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="isOpen = false"
        @keydown.escape.window="isOpen = false"
        class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-gray-900 rounded-2xl shadow-2xl
               border border-gray-200 dark:border-gray-700 overflow-hidden z-50"
    >
        {{-- Header --}}
        <div class="px-4 py-3 bg-linear-to-r from-red-500 to-pink-500 text-white">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-lg">Mes Favoris</h3>
                <span class="text-sm opacity-90" x-text="favorites.length + ' résidence(s)'"></span>
            </div>
        </div>

        {{-- Liste des favoris --}}
        <div class="max-h-96 overflow-y-auto">
            {{-- État vide --}}
            <template x-if="favorites.length === 0">
                <div class="py-12 px-4 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 mb-2">Aucun favori</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">
                        Cliquez sur le cœur pour sauvegarder vos résidences préférées
                    </p>
                    <a
                        href="{{ route('residences.index') }}"
                        class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-primary-500 text-white rounded-lg
                               hover:bg-primary-600 transition text-sm font-medium"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Explorer les résidences
                    </a>
                </div>
            </template>

            {{-- Liste --}}
            <template x-if="favorites.length > 0">
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <template x-for="residence in favorites" :key="residence.id">
                        <div class="p-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition group">
                            <a
                                :href="`/residences/${residence.slug || residence.id}`"
                                class="flex gap-3"
                            >
                                {{-- Image --}}
                                <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                                    <img loading="lazy" :src="residence.image || '/images/placeholder-residence.jpg'"
                                        :alt="residence.title"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    >
                                </div>

                                {{-- Infos --}}
                                <div class="flex-1 min-w-0">
                                    <h4
                                        class="font-medium text-gray-900 dark:text-white truncate group-hover:text-primary-600 transition"
                                        x-text="residence.title"
                                    ></h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate" x-text="residence.location"></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-primary-600 font-semibold" x-text="formatPrice(residence.price)"></span>
                                        <span class="text-xs text-gray-400">/jour</span>
                                    </div>
                                </div>

                                {{-- Bouton supprimer --}}
                                <button
                                    @click.prevent.stop="removeFavorite(residence.id)"
                                    class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50
                                           dark:hover:bg-red-900/20 transition opacity-0 group-hover:opacity-100"
                                    title="Retirer des favoris"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </a>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Chargement --}}
            <template x-if="loading">
                <div class="py-8 text-center">
                    <svg class="w-8 h-8 mx-auto animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </template>
        </div>

        {{-- Footer avec actions --}}
        <template x-if="favorites.length > 0">
            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                <div class="flex gap-2">
                    <a
                        href="{{ route('favorites.index') }}"
                        class="flex-1 text-center px-4 py-2 bg-primary-500 text-white rounded-lg
                               hover:bg-primary-600 transition text-sm font-medium"
                    >
                        Voir tous les favoris
                    </a>
                    <button
                        @click="clearAllFavorites()"
                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-red-500
                               hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition text-sm"
                    >
                        Tout supprimer
                    </button>
                </div>

                {{-- Partage favoris --}}
                <button
                    @click="shareFavorites()"
                    class="w-full mt-2 px-4 py-2 text-gray-600 dark:text-gray-400
                           hover:text-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20
                           rounded-lg transition text-sm flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                    </svg>
                    Partager ma liste
                </button>
            </div>
        </template>

        {{-- Info sync --}}
        @guest
        <div class="px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border-t border-amber-200 dark:border-amber-700">
            <p class="text-xs text-amber-700 dark:text-amber-400 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>
                    <a href="{{ route('login') }}" class="underline font-medium">Connectez-vous</a>
                    pour synchroniser vos favoris sur tous vos appareils
                </span>
            </p>
        </div>
        @endguest
    </div>
</div>
