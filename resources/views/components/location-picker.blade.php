{{-- Location Picker (style Airbnb) --}}
{{-- Affiche la ville actuelle et permet de changer de pays/ville --}}
@props(['class' => ''])

@php
    $location = $userLocation ?? \App\Services\UserLocationService::current();
    $locations = $availableLocations ?? \App\Services\UserLocationService::availableLocations();
    $flag = \App\Services\UserLocationService::countryFlag($location['country_code'] ?? 'CI');
@endphp

<div x-data="locationPicker(@js($location), @js($locations))" {{ $attributes->merge(['class' => $class]) }}>
    {{-- Bouton trigger --}}
    <button @click="open = !open" @click.away="open = false"
        type="button"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-full border border-gray-200 transition-colors duration-200"
        :class="{ 'bg-gray-50 border-gray-300': open }"
        aria-label="Changer de localisation">
        <span class="text-base" x-text="currentFlag">{{ $flag }}</span>
        <span class="hidden sm:inline max-w-28 truncate" x-text="currentCity">{{ $location['city'] ?? 'Abidjan' }}</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1" x-cloak
        class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden">

        {{-- Header --}}
        <div class="px-4 py-3 bg-gray-50 border-b">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Choisir une localisation</p>
        </div>

        {{-- Détection GPS --}}
        <button @click="detectLocation()"
            class="w-full px-4 py-3 text-left hover:bg-blue-50 flex items-center gap-3 border-b transition-colors"
            :class="{ 'bg-blue-50': detecting }">
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                <svg x-show="!detecting" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <svg x-show="detecting" class="w-4 h-4 text-blue-600 animate-spin" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4" />
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">Ma position actuelle</p>
                <p class="text-xs text-gray-500">Utiliser le GPS</p>
            </div>
        </button>

        {{-- Liste des pays/villes --}}
        <div class="max-h-64 overflow-y-auto">
            <template x-for="country in locations" :key="country.code">
                <div>
                    {{-- En-tête pays --}}
                    <div class="px-4 py-2 bg-gray-50/80 border-b border-t">
                        <p class="text-xs font-semibold text-gray-500 flex items-center gap-1.5">
                            <span x-text="country.flag"></span>
                            <span x-text="country.name"></span>
                        </p>
                    </div>

                    {{-- Villes --}}
                    <template x-for="city in country.cities" :key="city.name">
                        <button @click="selectCity(country.code, country.name, city.name, country.flag)"
                            class="w-full px-4 py-2.5 text-left hover:bg-orange-50 flex items-center justify-between group transition-colors"
                            :class="{
                                'bg-orange-50 font-semibold': currentCity === city.name && currentCountry === country
                                    .code
                            }">
                            <span class="text-sm text-gray-700 group-hover:text-orange-700" x-text="city.name"></span>
                            <svg x-show="currentCity === city.name && currentCountry === country.code"
                                class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('locationPicker', (initialLocation, locations) => ({
                    open: false,
                    detecting: false,
                    locations: locations,
                    currentCountry: initialLocation.country_code || 'CI',
                    currentCity: initialLocation.city || 'Abidjan',
                    currentFlag: @js(\App\Services\UserLocationService::countryFlag('CI')),

                    init() {
                        this.currentFlag = this.getFlagForCountry(this.currentCountry);

                        // Auto-detect location on first visit if not yet set
                        if (!initialLocation.detected && !localStorage.getItem('rezi_location_set')) {
                            this.detectLocation();
                        }
                    },

                    getFlagForCountry(code) {
                        const country = this.locations.find(c => c.code === code);
                        return country ? country.flag : '🌍';
                    },

                    async selectCity(countryCode, countryName, cityName, flag) {
                        this.currentCountry = countryCode;
                        this.currentCity = cityName;
                        this.currentFlag = flag;
                        this.open = false;

                        localStorage.setItem('rezi_location_set', '1');

                        try {
                            const res = await fetch('/api/v1/locations/set', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    country_code: countryCode,
                                    city: cityName
                                }),
                            });

                            if (res.ok) {
                                // Recharger la page pour afficher les résidences de la nouvelle ville
                                window.location.reload();
                            }
                        } catch (e) {
                            console.error('Erreur changement localisation:', e);
                        }
                    },

                    async detectLocation() {
                        if (!navigator.geolocation) {
                            return;
                        }

                        this.detecting = true;

                        navigator.geolocation.getCurrentPosition(
                            async (position) => {
                                try {
                                    const res = await fetch('/api/v1/locations/detect', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]').content,
                                            'Accept': 'application/json',
                                        },
                                        body: JSON.stringify({
                                            latitude: position.coords.latitude,
                                            longitude: position.coords.longitude,
                                        }),
                                    });

                                    const json = await res.json();
                                    if (json.success && json.data) {
                                        this.currentCountry = json.data.country_code;
                                        this.currentCity = json.data.city;
                                        this.currentFlag = this.getFlagForCountry(json.data
                                            .country_code);
                                        this.open = false;

                                        localStorage.setItem('rezi_location_set', '1');

                                        // Reload only if location actually changed
                                        if (json.data.country_code !== initialLocation
                                            .country_code || json.data.city !== initialLocation
                                            .city) {
                                            window.location.reload();
                                        }
                                    }
                                } catch (e) {
                                    console.error('Erreur détection:', e);
                                } finally {
                                    this.detecting = false;
                                }
                            },
                            (error) => {
                                console.log('Géolocalisation refusée ou indisponible');
                                this.detecting = false;
                            }, {
                                enableHighAccuracy: false,
                                timeout: 10000,
                                maximumAge: 300000
                            }
                        );
                    }
                }));
            });
        </script>
    @endpush
@endonce
