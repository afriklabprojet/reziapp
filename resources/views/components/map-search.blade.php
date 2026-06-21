@props([
    'residences' => [],
    'center' => ['lat' => 5.36, 'lng' => -4.0083],
    'zoom' => 12,
    'radius' => 5,
    'showRadiusCircle' => true,
    'height' => 'h-[300px] sm:h-[400px] lg:h-[500px]',
    'interactive' => true,
])

<div x-data="mapSearch({{ alpine_encode([
    'center' => $center,
    'zoom' => $zoom,
    'radius' => $radius,
    'showRadiusCircle' => $showRadiusCircle,
    'residences' => $residences,
    'interactive' => $interactive,
]) }})" x-init="initMap()"
    {{ $attributes->merge(['class' => 'relative rounded-lg overflow-hidden shadow-lg']) }}>
    <!-- Carte Google Maps -->
    <div x-ref="mapContainer" class="w-full {{ $height }} bg-gray-200">
        <!-- Loader -->
        <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-gray-100 z-10">
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-10 w-10 text-blue-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span class="text-sm text-gray-600">Chargement de la carte...</span>
            </div>
        </div>
    </div>

    <!-- Contrôles de zoom (personnalisés) -->
    <div class="absolute top-4 right-4 flex flex-col gap-2 z-10">
        <button @click="zoomIn()" type="button"
            class="w-10 h-10 bg-white rounded-lg shadow-md flex items-center justify-center hover:bg-gray-50 transition-colors"
            title="Zoomer">
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
        </button>
        <button @click="zoomOut()" type="button"
            class="w-10 h-10 bg-white rounded-lg shadow-md flex items-center justify-center hover:bg-gray-50 transition-colors"
            title="Dézoomer">
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
            </svg>
        </button>
        <button @click="centerOnUser()" type="button"
            class="w-10 h-10 bg-white rounded-lg shadow-md flex items-center justify-center hover:bg-gray-50 transition-colors"
            title="Ma position">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </button>
    </div>

    <!-- Légende -->
    <div
        class="absolute bottom-3 left-4 z-10 hidden rounded-lg bg-white/95 p-3 shadow-md backdrop-blur-sm sm:bottom-4 sm:block">
        <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-[10px] sm:text-xs">
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 bg-emerald-600 rounded-full"></span>
                <span class="text-gray-600">Disponible</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
                <span class="text-gray-600">Indisponible</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                <span class="text-gray-600">Ma position</span>
            </div>
            <div class="flex items-center gap-1" x-show="showRadiusCircle">
                <span class="w-3 h-3 border-2 border-blue-400 rounded-full"></span>
                <span class="text-gray-600">Zone (<span x-text="currentRadius"></span> km)</span>
            </div>
        </div>
    </div>

    <!-- Compteur de résidences -->
    <div class="absolute left-3 top-3 z-10 rounded-full bg-white/95 px-3 py-2 shadow-md backdrop-blur-sm sm:left-4 sm:top-4 sm:rounded-lg sm:px-4">
        <span class="text-sm font-medium text-gray-700">
            <span x-text="visibleCount" class="text-blue-600 font-bold"></span> résidence(s) visible(s)
        </span>
    </div>

    <!-- Popup personnalisé -->
    <template x-teleport="body">
        <div x-show="selectedResidence" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95" @click.away="selectedResidence = null" class="fixed z-9999 w-72"
            :style="popupPosition">
            <template x-if="selectedResidence">
                <div class="bg-white rounded-xl shadow-2xl overflow-hidden border border-gray-100">
                    <!-- Image -->
                    <div class="relative h-32 bg-gray-200">
                        <img loading="lazy" :src="selectedResidence.thumbnail || '/images/placeholder-residence.jpg'"
                            :alt="selectedResidence.title" class="w-full h-full object-cover"
                            :class="!selectedResidence.is_available ? 'grayscale opacity-70' : ''">
                        <div class="absolute top-2 right-2">
                            <button @click="selectedResidence = null"
                                class="w-6 h-6 bg-white/80 rounded-full flex items-center justify-center hover:bg-white">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        {{-- Badge disponibilité --}}
                        <div class="absolute top-2 left-2">
                            <span x-show="selectedResidence.is_available !== false"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500 text-white shadow">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                        clip-rule="evenodd" />
                                </svg>
                                Disponible
                            </span>
                            <span x-show="selectedResidence.is_available === false"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-500 text-white shadow">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                </svg>
                                Indisponible
                            </span>
                        </div>
                        <div class="absolute bottom-2 left-2">
                            <span class="px-2 py-1 bg-[#F16A00] text-white text-xs font-semibold rounded">
                                <span x-text="formatPrice(selectedResidence.price)"></span> FCFA
                            </span>
                        </div>
                    </div>
                    <!-- Contenu -->
                    <div class="p-3">
                        <p class="font-semibold text-gray-900 text-sm line-clamp-1" x-text="selectedResidence.title"></p>
                        <p class="text-xs text-gray-500 mt-1 flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            </svg>
                            <span
                                x-text="selectedResidence.location?.commune || selectedResidence.location?.city || selectedResidence.location?.address || ''"></span>
                            <template x-if="selectedResidence.location?.distance_meters">
                                <span class="ml-1 text-blue-600">
                                    • <span
                                        x-text="(selectedResidence.location.distance_meters / 1000).toFixed(1)"></span>
                                    km
                                </span>
                            </template>
                        </p>
                        <a :href="'/residences/' + selectedResidence.id"
                            class="mt-3 block w-full text-center py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="selectedResidence.is_available !== false ? 'bg-[#F16A00] text-white hover:bg-[#CC5A00]' :
                                'bg-gray-200 text-gray-600 hover:bg-gray-300'">
                            <span
                                x-text="selectedResidence.is_available !== false ? 'Voir les détails' : 'Voir la fiche'"></span>
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>

<x-google-maps-loader />

@once
    @push('styles')
        <style>
            .residence-marker {
                cursor: pointer;
                transition: transform 0.2s ease;
            }

            .residence-marker:hover {
                transform: scale(1.15);
            }

            .residence-marker.active {
                transform: scale(1.2);
                z-index: 10;
            }

            .user-marker {
                animation: pulse 2s infinite;
            }

            @keyframes pulse {

                0%,
                100% {
                    transform: scale(1);
                    opacity: 1;
                }

                50% {
                    transform: scale(1.2);
                    opacity: 0.8;
                }
            }

            .cluster-marker {
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
                color: white;
                font-weight: bold;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
                cursor: pointer;
                transition: transform 0.2s ease;
            }

            .cluster-marker:hover {
                transform: scale(1.1);
            }
        </style>
    @endpush
@endonce
