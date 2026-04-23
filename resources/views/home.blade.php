<x-app-layout>
    @section('title', 'REZI — Résidences meublées à ' . ($userLocation['city'] ??
        (\App\Services\UserLocationService::current()['city'] ?? 'Abidjan')) . ' | Recherche géolocalisée')

        @push('styles')
            {{-- Preload Mapbox pour la carte héro + mini-map + page carte --}}
            <link rel="preload" href="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <script src="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js" defer></script>
            <noscript><link href="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css" rel="stylesheet"></noscript>
            <style>
                .mapboxgl-ctrl-logo, .mapboxgl-ctrl-attrib { display: none !important; }
            </style>
        @endpush

        {{-- APP STATE MANAGEMENT --}}
        <div x-data="{
            radius: 500,
            gpsState: 'prompt', // prompt, locating, success, error, search
            showResidences: false,
            activeSlide: 0,
            resultsCount: 0,
            userLocation: null,
            searchQuery: '',
            selectedCommune: '',
            geoError: '',
            radiusCounts: { 500: 0, 2000: 0, 5000: 0 },
            gpsAccuracy: null,
            heroMap: null,
            heroExpanded: true,

            /**
             * Attend que Mapbox JS soit chargé, puis initialise la carte héro
             */
            waitAndInitMap() {
                if (window.mapboxgl) { this.initHeroMap(); }
                else { setTimeout(() => this.waitAndInitMap(), 100); }
            },

            communes: @js($popularZones->pluck('name')->values()),

            /**
             * Géolocalisation haute précision avec fallback
             * 1. Tente d'abord avec GPS haute précision (enableHighAccuracy: true)
             * 2. Si timeout, retente avec précision réseau (fallback)
             * 3. Affiche la précision obtenue à l'utilisateur
             */
            startGeoloc() {
                this.gpsState = 'locating';
                this.geoError = '';
                this.gpsAccuracy = null;

                if (!navigator.geolocation) {
                    this.gpsState = 'search';
                    this.geoError = 'Géolocalisation non supportée — choisissez un quartier';
                    return;
                }

                // Étape 1 : Haute précision (GPS matériel)
                navigator.geolocation.getCurrentPosition(
                    (position) => this.handleGeoSuccess(position),
                    (error) => {
                        if (error.code === 1) {
                            // Permission refusée → pas de fallback possible
                            this.gpsState = 'search';
                            this.geoError = 'Position refusée — choisissez un quartier ci-dessous';
                            return;
                        }
                        // Étape 2 : Fallback réseau (WiFi/Cell) si GPS timeout ou indisponible
                        console.log('GPS haute précision échoué, tentative réseau...');
                        navigator.geolocation.getCurrentPosition(
                            (position) => this.handleGeoSuccess(position),
                            (fallbackError) => {
                                this.gpsState = 'search';
                                if (fallbackError.code === 2) {
                                    this.geoError = 'Position introuvable — choisissez un quartier';
                                } else {
                                    this.geoError = 'Délai dépassé — choisissez un quartier';
                                }
                            }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 30000 }
                        );
                    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                );
            },

            /**
             * Traite le succès de la géolocalisation
             * Stocke la position + précision en mètres
             */
            handleGeoSuccess(position) {
                this.userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                this.gpsAccuracy = Math.round(position.coords.accuracy);
                console.log(`Position obtenue: ${this.userLocation.lat}, ${this.userLocation.lng} (±${this.gpsAccuracy}m)`);
                this.fetchRadiusCounts();
            },

            mapUrl() {
                const base = '{{ route('residences.map') }}';
                if (this.userLocation) {
                    return base + '?lat=' + this.userLocation.lat + '&lng=' + this.userLocation.lng;
                }
                return base;
            },

            setRadius(r) {
                this.radius = r;
                this.resultsCount = this.radiusCounts[r] ?? 0;
            },

            async fetchRadiusCounts() {
                if (!this.userLocation) return;
                try {
                    const res = await fetch(`/api/v1/geo/radius-counts?latitude=${this.userLocation.lat}&longitude=${this.userLocation.lng}`);
                    const json = await res.json();
                    if (json.success && json.data) {
                        json.data.forEach(item => {
                            this.radiusCounts[item.radius] = item.count;
                        });
                        this.resultsCount = this.radiusCounts[this.radius] ?? 0;
                        this.gpsState = 'success';
                        this.showResidences = true;
                        // Auto-collapse la carte après 2s pour révéler la carte Mapbox
                        setTimeout(() => { this.heroExpanded = false; }, 2000);
                    } else {
                        // Position hors zone couverte → basculer vers recherche par quartier
                        this.gpsState = 'search';
                        this.geoError = 'Vous semblez être hors de la zone couverte — choisissez un quartier';
                    }
                } catch (e) {
                    console.warn('Impossible de charger les compteurs:', e);
                    this.gpsState = 'search';
                    this.geoError = 'Erreur réseau — choisissez un quartier';
                }
            },

            searchByCommune() {
                if (this.selectedCommune) {
                    window.location.href = '{{ route('residences.index') }}?commune=' + encodeURIComponent(this.selectedCommune);
                }
            },

            /**
             * Calcul distance Haversine côté client (mètres)
             */
            haversineDistance(lat1, lng1, lat2, lng2) {
                const R = 6371000;
                const toRad = x => x * Math.PI / 180;
                const dLat = toRad(lat2 - lat1);
                const dLng = toRad(lng2 - lng1);
                const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng/2)**2;
                return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            },

            formatDistance(meters) {
                if (meters < 1000) return Math.round(meters) + 'm';
                return (meters / 1000).toFixed(1) + 'km';
            },

            /**
             * Initialise une vraie carte Mapbox en arrière-plan du héro
             * Se lance dès le chargement, centrée sur Abidjan
             */
            async initHeroMap() {
                if (this.heroMap) return;
                const token = '{{ config('services.mapbox.access_token') }}';
                if (!token || !window.mapboxgl) return;

                await this.$nextTick();

                const container = this.$refs.heroMapContainer;
                if (!container || container.clientWidth === 0) {
                    setTimeout(() => this.initHeroMap(), 200);
                    return;
                }

                mapboxgl.accessToken = token;
                // Centre par défaut : Abidjan, Côte d'Ivoire
                const defaultCenter = [-3.9962, 5.3600];
                this.heroMap = new mapboxgl.Map({
                    container: container,
                    style: 'mapbox://styles/mapbox/streets-v12',
                    center: this.userLocation ? [this.userLocation.lng, this.userLocation.lat] : defaultCenter,
                    zoom: this.userLocation ? 13 : 11,
                    interactive: false,
                    attributionControl: false,
                });

                this.heroMap.on('load', () => {
                    this.heroMap.resize();
                    if (this.userLocation) {
                        this.addHeroMarkers();
                    }
                    setTimeout(() => this.heroMap && this.heroMap.resize(), 500);
                });
            },

            /**
             * Recentre la carte sur la position utilisateur + ajoute les marqueurs
             */
            flyToUser() {
                if (!this.heroMap || !this.userLocation) return;
                this.heroMap.flyTo({
                    center: [this.userLocation.lng, this.userLocation.lat],
                    zoom: 13,
                    duration: 2000,
                    essential: true,
                });
                this.addHeroMarkers();
            },

            /**
             * Ajoute les marqueurs résidences + utilisateur sur la carte héro
             */
            addHeroMarkers() {
                if (!this.heroMap || !this.userLocation) return;

                const residences = @js($featuredResidences->take(8)->map(fn($r) => [
                    'lat' => $r->latitude,
                    'lng' => $r->longitude,
                    'price' => ($r->price_per_day ?? 0) > 0 ? number_format($r->price_per_day / 1000) . 'k' : '—',
                    'name' => Str::limit($r->name, 20),
                ]));

                residences.forEach(r => {
                    if (!r.lat || !r.lng) return;
                    const el = document.createElement('div');
                    el.innerHTML = `<div class='bg-orange-500 text-white px-2 py-1 rounded-lg text-xs font-bold shadow-lg whitespace-nowrap'>${r.price}<span class='font-normal text-white/80'>/j</span></div><div class='w-2 h-2 bg-orange-400 rounded-full mx-auto mt-0.5 shadow'></div>`;
                    el.className = 'pointer-events-none';
                    new mapboxgl.Marker({ element: el, anchor: 'bottom' })
                        .setLngLat([r.lng, r.lat])
                        .addTo(this.heroMap);
                });

                const userEl = document.createElement('div');
                userEl.innerHTML = `<div class='w-5 h-5 bg-blue-600 rounded-full border-3 border-white shadow-xl'></div><div class='absolute inset-0 w-5 h-5 bg-blue-500 rounded-full animate-ping opacity-50'></div>`;
                userEl.className = 'relative pointer-events-none';
                new mapboxgl.Marker({ element: userEl })
                    .setLngLat([this.userLocation.lng, this.userLocation.lat])
                    .addTo(this.heroMap);
            }
        }" class="relative bg-sand-50 flex flex-col">

            {{-- 1. HERO IMMERSIF - MAP INTERFACE --}}
            <div class="relative min-h-[85vh] lg:min-h-[90vh] w-full overflow-hidden bg-gray-900 flex flex-col"
                style="isolation: isolate;">

                {{-- Background Map avec animation subtile --}}
                <div class="absolute inset-0 z-0">
                    {{-- Vraie carte Mapbox - toujours visible, centrée sur Abidjan par défaut --}}
                    <div x-ref="heroMapContainer"
                        x-init="waitAndInitMap(); $watch('gpsState', (state) => { if (state === 'success') flyToUser(); })"
                        class="absolute inset-0 w-full h-full transition-all duration-700"
                        :class="heroExpanded && gpsState === 'success' ? 'opacity-60' : (!heroExpanded && gpsState === 'success' ? 'opacity-95' : 'opacity-50')"
                        :style="heroExpanded && gpsState === 'success' ? 'filter: saturate(0.8) brightness(0.85)' : (!heroExpanded && gpsState === 'success' ? 'filter: saturate(1) brightness(1)' : 'filter: saturate(0.5) brightness(0.7)')">
                    </div>

                    {{-- Overlay gradient dynamique - s'allège quand la carte est visible --}}
                    <div class="absolute inset-0 transition-all duration-700"
                        :class="!heroExpanded && gpsState === 'success' ? 'bg-linear-to-b from-gray-900/30 via-transparent to-gray-900/40' : 'bg-linear-to-b from-gray-900/50 via-gray-900/20 to-gray-900/60'"></div>

                    {{-- Grille décorative (style tech) - masquée quand carte Mapbox active --}}
                    <div class="absolute inset-0 opacity-10" x-show="gpsState !== 'success'"
                        style="background-image: linear-gradient(rgba(247, 147, 30, 0.25) 1px, transparent 1px), linear-gradient(90deg, rgba(247, 147, 30, 0.25) 1px, transparent 1px); background-size: 50px 50px;">
                    </div>

                    {{-- Cercles de radar animés --}}
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none"
                        x-show="gpsState === 'locating'">
                        <div class="w-64 h-64 sm:w-96 sm:h-96 rounded-full border border-orange-500/30 animate-ping"
                            style="animation-duration: 3s;"></div>
                        <div class="absolute inset-0 w-64 h-64 sm:w-96 sm:h-96 rounded-full border border-orange-500/20 animate-ping"
                            style="animation-duration: 4s; animation-delay: 1s;"></div>
                        <div class="absolute inset-0 w-64 h-64 sm:w-96 sm:h-96 rounded-full border border-orange-500/10 animate-ping"
                            style="animation-duration: 5s; animation-delay: 2s;"></div>
                    </div>
                </div>

                {{-- Main Content --}}
                <div class="relative z-20 flex-1 flex flex-col items-center justify-center px-4 sm:px-6 py-8 sm:py-12">

                    {{-- Hero Title --}}
                    <div class="text-center mb-8 max-w-2xl" x-show="gpsState === 'prompt'" x-transition>
                        {{-- Current location badge --}}
                        @if (isset($userLocation))
                            <div
                                class="inline-flex items-center gap-1.5 px-3 py-1 mb-4 bg-white/15 backdrop-blur-sm rounded-full text-sm text-white/90 border border-white/20">
                                <span>{{ \App\Services\UserLocationService::countryFlag($userLocation['country_code'] ?? 'CI') }}</span>
                                <span>{{ $userLocation['city'] ?? 'Abidjan' }},
                                    {{ $userLocation['country_name'] ?? "Côte d'Ivoire" }}</span>
                            </div>
                        @endif

                        <h1 class="font-display text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-4">
                            Trouvez un logement
                            <span class="block text-transparent bg-clip-text bg-linear-to-r from-orange-300 to-amber-400">à
                                côté de vous</span>
                        </h1>
                        <p class="text-base sm:text-lg text-gray-300 max-w-lg mx-auto">
                            Résidences meublées vérifiées dans un rayon de <span
                                class="text-orange-400 font-semibold">500m</span>.
                            Réservation directe, sans intermédiaire.
                        </p>
                    </div>

                    {{-- Search Interface Card --}}
                    <div class="w-full max-w-lg">

                        {{-- Main Action Card (hidden when collapsed to reveal map) --}}
                        <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl shadow-black/20 overflow-hidden border border-white/50 transition-all duration-500"
                            :class="gpsState === 'locating' ? 'scale-[0.98]' : 'scale-100'"
                            x-show="heroExpanded || gpsState !== 'success'"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-8">

                            {{-- Tab Switcher --}}
                            <div class="flex border-b border-gray-100" x-show="gpsState === 'prompt'">
                                <button
                                    class="flex-1 py-4 text-sm font-semibold text-orange-500 border-b-2 border-orange-500 bg-orange-50/50 flex items-center justify-center gap-2">
                                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                    Autour de moi
                                </button>
                                <button @click="gpsState = 'search'"
                                    class="flex-1 py-4 text-sm font-semibold text-gray-500 hover:text-gray-700 flex items-center justify-center gap-2 transition">
                                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Par quartier
                                </button>
                            </div>

                            {{-- State: PROMPT - Location Button --}}
                            <div x-show="gpsState === 'prompt'" class="p-6 sm:p-8">
                                <div class="text-center space-y-6">
                                    {{-- Animated Location Icon --}}
                                    <div class="relative inline-flex items-center justify-center">
                                        <div class="absolute w-24 h-24 bg-orange-100 rounded-full animate-ping opacity-50">
                                        </div>
                                        <div class="absolute w-20 h-20 bg-orange-50 rounded-full"></div>
                                        <div
                                            class="relative w-16 h-16 bg-linear-to-br from-orange-500 to-orange-500 rounded-full flex items-center justify-center shadow-lg shadow-orange-500/30">
                                            <svg aria-hidden="true" class="w-8 h-8 text-white" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- CTA Button --}}
                                    <button @click="startGeoloc()"
                                        class="w-full bg-linear-to-r from-orange-500 to-orange-500 hover:from-orange-600 hover:to-teal-700 text-white font-bold py-4 px-8 rounded-2xl shadow-xl shadow-orange-500/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-3 text-lg">
                                        <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        Activer ma position
                                    </button>

                                    {{-- Trust Badges --}}
                                    <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg aria-hidden="true" class="w-4 h-4 text-orange-500" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            Données sécurisées
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg aria-hidden="true" class="w-4 h-4 text-blue-500" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            Instantané
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg aria-hidden="true" class="w-4 h-4 text-amber-500" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            100% Gratuit
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- State: SEARCH - By Commune --}}
                            <div x-show="gpsState === 'search'" x-cloak class="p-6 sm:p-8">
                                <div class="space-y-4">
                                    {{-- Bandeau info si on arrive d'une erreur géoloc --}}
                                    <div x-show="geoError" x-transition
                                        class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm">
                                        <svg aria-hidden="true" class="w-5 h-5 text-amber-500 shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-amber-700" x-text="geoError"></span>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Choisir une
                                            zone</label>
                                        <select x-model="selectedCommune"
                                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-gray-900">
                                            <option value="">Sélectionner une zone...</option>
                                            <template x-for="commune in communes" :key="commune">
                                                <option :value="commune" x-text="commune"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <button @click="searchByCommune()" :disabled="!selectedCommune"
                                        :class="selectedCommune ? 'bg-orange-500 hover:bg-orange-600' :
                                            'bg-gray-300 cursor-not-allowed'"
                                        class="w-full text-white font-bold py-4 px-8 rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2">
                                        <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        Rechercher
                                    </button>

                                    <div class="flex items-center justify-between">
                                        <button @click="gpsState = 'prompt'; geoError = ''"
                                            class="text-gray-500 hover:text-gray-700 text-sm py-2 transition flex items-center gap-1">
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 19l-7-7 7-7" />
                                            </svg>
                                            Retour
                                        </button>
                                        <a href="{{ route('residences.map') }}"
                                            class="text-orange-500 hover:text-orange-600 text-sm py-2 font-medium transition flex items-center gap-1">
                                            Voir la carte
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- State: LOCATING - Loading Animation --}}
                            <div x-show="gpsState === 'locating'" x-cloak class="p-6 sm:p-8 md:p-12">
                                <div class="flex flex-col items-center justify-center text-center space-y-4">
                                    {{-- Radar Animation --}}
                                    <div class="relative w-24 h-24">
                                        <div class="absolute inset-0 border-4 border-orange-200 rounded-full"></div>
                                        <div class="absolute inset-2 border-4 border-emerald-300 rounded-full animate-ping"
                                            style="animation-duration: 1.5s;"></div>
                                        <div class="absolute inset-4 border-4 border-orange-400 rounded-full animate-ping"
                                            style="animation-duration: 2s;"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="w-4 h-4 bg-orange-500 rounded-full shadow-lg shadow-orange-500/50">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-xl font-bold text-gray-900">Scan en cours...</p>
                                        <p class="text-sm text-gray-500 mt-1">Analyse des résidences dans votre zone</p>
                                    </div>

                                    {{-- Progress Steps --}}
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <span class="flex items-center gap-1 text-orange-500">
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Position
                                        </span>
                                        <span>→</span>
                                        <span class="animate-pulse text-orange-500">Recherche...</span>
                                        <span>→</span>
                                        <span>Résultats</span>
                                    </div>
                                </div>
                            </div>

                            {{-- State: SUCCESS - Full Card (expanded) --}}
                            <div x-show="gpsState === 'success' && heroExpanded" x-cloak class="p-4 sm:p-6"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95">

                                {{-- Note: seules les disponibles --}}
                                <div class="text-xs text-gray-500 mb-3 flex items-center gap-1">
                                    <svg aria-hidden="true" class="w-3.5 h-3.5 text-emerald-500" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Seules les résidences disponibles sont affichées
                                </div>
                                <div class="flex items-center gap-3 bg-orange-50 rounded-xl p-3 mb-4">
                                    <div
                                        class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center shrink-0">
                                        <svg aria-hidden="true" class="w-5 h-5 text-orange-500" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-emerald-900">Position confirmée</p>
                                        <p class="text-xs text-orange-500 truncate"
                                            x-text="selectedCommune ? selectedCommune : 'Votre position actuelle'">
                                        </p>
                                        {{-- Indicateur de précision GPS --}}
                                        <p x-show="gpsAccuracy !== null" x-cloak class="text-[10px] mt-0.5"
                                            :class="gpsAccuracy <= 20 ? 'text-emerald-600' : (gpsAccuracy <= 100 ?
                                                'text-orange-500' : 'text-red-500')"
                                            x-text="'Précision : ±' + gpsAccuracy + 'm' + (gpsAccuracy <= 20 ? ' — Excellente' : (gpsAccuracy <= 100 ? ' — Bonne' : ' — Faible, activez le GPS'))">
                                        </p>
                                    </div>
                                    <button @click="gpsState = 'prompt'; showResidences = false; heroExpanded = true"
                                        class="text-orange-500 hover:text-orange-600 text-xs font-medium underline">
                                        Modifier
                                    </button>
                                </div>

                                {{-- Radius Selector --}}
                                <div class="space-y-3">
                                    <label class="block text-sm font-medium text-gray-700">Rayon de recherche</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button @click="setRadius(500)"
                                            :class="radius === 500 ?
                                                'bg-orange-500 text-white border-orange-500 shadow-lg shadow-orange-500/30' :
                                                'bg-white text-gray-700 border-gray-200 hover:border-emerald-300'"
                                            class="py-3 px-4 rounded-xl border-2 font-semibold transition-all">
                                            <span class="block text-lg">500m</span>
                                            <span class="block text-xs opacity-70">🚶 5 min à pied</span>
                                        </button>
                                        <button @click="setRadius(2000)"
                                            :class="radius === 2000 ?
                                                'bg-orange-500 text-white border-orange-500 shadow-lg shadow-orange-500/30' :
                                                'bg-white text-gray-700 border-gray-200 hover:border-emerald-300'"
                                            class="py-3 px-4 rounded-xl border-2 font-semibold transition-all">
                                            <span class="block text-lg">2 km</span>
                                            <span class="block text-xs opacity-70">🚲 En vélo</span>
                                        </button>
                                        <button @click="setRadius(5000)"
                                            :class="radius === 5000 ?
                                                'bg-orange-500 text-white border-orange-500 shadow-lg shadow-orange-500/30' :
                                                'bg-white text-gray-700 border-gray-200 hover:border-emerald-300'"
                                            class="py-3 px-4 rounded-xl border-2 font-semibold transition-all">
                                            <span class="block text-lg">5 km</span>
                                            <span class="block text-xs opacity-70">🚗 En voiture</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Results Summary --}}
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <template x-if="resultsCount > 0">
                                                <span class="flex h-3 w-3 relative">
                                                    <span
                                                        class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-emerald-400 opacity-75"></span>
                                                    <span
                                                        class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                                </span>
                                            </template>
                                            <template x-if="resultsCount === 0">
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-gray-300"></span>
                                            </template>
                                            <span class="text-sm text-gray-600">
                                                <span class="font-bold"
                                                    :class="resultsCount > 0 ? 'text-emerald-600' : 'text-gray-400'"
                                                    x-text="resultsCount"></span>
                                                <span
                                                    x-text="resultsCount > 0 ? 'résidence' + (resultsCount > 1 ? 's' : '') + ' dans ' + (radius >= 1000 ? (radius/1000) + ' km' : radius + 'm') : 'Aucune résidence dans ce rayon'"></span>
                                            </span>
                                        </div>
                                    </div>
                                    {{-- Bouton Carte amélioré --}}
                                    <a :href="mapUrl()"
                                        class="flex items-center gap-3 w-full p-3 bg-linear-to-r from-gray-900 to-gray-800 hover:from-gray-800 hover:to-gray-700 rounded-xl text-white transition-all group shadow-lg">
                                        <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center shrink-0">
                                            <svg aria-hidden="true" class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <span class="text-sm font-semibold">Explorer sur la carte</span>
                                            <span class="block text-xs text-gray-400">Carte interactive avec filtres</span>
                                        </div>
                                        <svg aria-hidden="true" class="w-5 h-5 text-gray-400 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- State: SUCCESS - Compact Floating Bar (collapsed) --}}
                        <div x-show="gpsState === 'success' && !heroExpanded" x-cloak
                            x-transition:enter="transition ease-out duration-500 delay-300"
                            x-transition:enter-start="opacity-0 translate-y-4"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="fixed bottom-4 left-4 right-4 z-50 max-w-lg mx-auto lg:absolute lg:bottom-8 lg:left-1/2 lg:-translate-x-1/2">

                            <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl shadow-black/30 border border-white/50 p-3">
                                {{-- Compact radius pills + count --}}
                                <div class="flex items-center gap-2 mb-2">
                                    <button @click="setRadius(500)"
                                        :class="radius === 500 ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600'"
                                        class="px-3 py-1.5 rounded-full text-xs font-bold transition-all">
                                        500m
                                    </button>
                                    <button @click="setRadius(2000)"
                                        :class="radius === 2000 ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600'"
                                        class="px-3 py-1.5 rounded-full text-xs font-bold transition-all">
                                        2 km
                                    </button>
                                    <button @click="setRadius(5000)"
                                        :class="radius === 5000 ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600'"
                                        class="px-3 py-1.5 rounded-full text-xs font-bold transition-all">
                                        5 km
                                    </button>

                                    <div class="flex-1"></div>

                                    {{-- Result count badge --}}
                                    <span class="text-xs font-bold px-2 py-1 rounded-full"
                                        :class="resultsCount > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                        x-text="resultsCount + ' résidence' + (resultsCount > 1 ? 's' : '')">
                                    </span>

                                    {{-- Expand button --}}
                                    <button @click="heroExpanded = true"
                                        class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-all"
                                        title="Afficher les détails">
                                        <svg aria-hidden="true" class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Action buttons row --}}
                                <div class="flex gap-2">
                                    <a :href="mapUrl()"
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 bg-linear-to-r from-gray-900 to-gray-800 hover:from-gray-800 hover:to-gray-700 rounded-xl text-white text-sm font-semibold transition-all shadow-lg">
                                        <svg aria-hidden="true" class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                        </svg>
                                        Explorer la carte
                                    </a>
                                    <button @click="document.getElementById('nearby-residences')?.scrollIntoView({ behavior: 'smooth' })"
                                        class="flex items-center justify-center gap-1.5 px-4 py-2.5 bg-orange-500 hover:bg-orange-600 rounded-xl text-white text-sm font-semibold transition-all shadow-lg">
                                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                        Voir
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Quick Stats (visible on prompt) --}}
                        <div x-show="gpsState === 'prompt'" class="mt-6 grid grid-cols-3 gap-4 text-center">
                            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                                <div class="text-2xl font-bold text-white">{{ $stats['residences'] ?? 0 }}</div>
                                <div class="text-xs text-gray-400">Résidences</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                                <div class="text-2xl font-bold text-orange-400">{{ $stats['communes'] ?? 0 }}</div>
                                <div class="text-xs text-gray-400">Zones</div>
                            </div>
                            <div class="bg-white/10 backdrop-blur rounded-xl p-3 border border-white/10">
                                <div class="text-2xl font-bold text-white">{{ $stats['owners'] ?? 0 }}</div>
                                <div class="text-xs text-gray-400">Propriétaires</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Scroll Indicator --}}
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20" x-show="showResidences">
                    <div class="animate-bounce text-white/60">
                        <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- 2. BOTTOM SHEET - RESULTS --}}
            <div x-show="showResidences" x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                class="relative z-30 -mt-8 bg-sand-100 rounded-t-3xl pt-4 pb-20 lg:pb-8" x-cloak id="nearby-residences">

                {{-- Drag Handle --}}
                <div class="flex justify-center mb-4">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6">
                    <div class="flex items-center justify-between mb-4 sm:mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Résidences à proximité</h3>
                            <p class="text-sm text-gray-500"><span x-text="resultsCount"></span> logements dans un rayon
                                de <span x-text="radius >= 1000 ? (radius/1000) + ' km' : radius + ' m'"></span></p>
                        </div>
                        <a :href="mapUrl()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl shadow-lg hover:bg-gray-800 transition-all">
                            <svg aria-hidden="true" class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Carte
                        </a>
                    </div>

                    {{-- Horizontal Scroll Container - Dynamic --}}
                    <div
                        class="flex overflow-x-auto snap-x snap-proximity gap-3 sm:gap-4 pb-4 scrollbar-hide -mx-4 px-4 sm:-mx-6 sm:px-6">

                        @forelse($featuredResidences->take(4) as $residence)
                            <a href="{{ route('residences.show', $residence) }}"
                                class="snap-start shrink-0 w-70 sm:w-85 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden group cursor-pointer hover:-translate-y-1 hover:shadow-xl transition-all duration-300 touch-pan-x">
                                <div class="relative h-44 bg-gray-200">
                                    @if ($residence->photos->isNotEmpty())
                                        <img loading="lazy" src="{{ storage_url($residence->photos->first()?->path) }}"
                                            alt="{{ $residence->name }}"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    @else
                                        <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                            <svg aria-hidden="true" class="w-12 h-12 text-gray-300" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="absolute inset-0 bg-linear-to-t from-black/50 to-transparent"></div>
                                    <div
                                        class="absolute top-3 left-3 bg-orange-500 text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow flex items-center gap-1">
                                        <svg aria-hidden="true" class="w-3 h-3" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Dispo
                                    </div>
                                    {{-- Distance badge (si géoloc active) --}}
                                    @if($residence->latitude && $residence->longitude)
                                    <div x-show="userLocation" x-cloak
                                        class="absolute bottom-12 left-3 bg-blue-600 text-white px-2 py-0.5 rounded-md text-[10px] font-bold shadow flex items-center gap-1">
                                        <svg aria-hidden="true" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        <span x-text="userLocation ? formatDistance(haversineDistance(userLocation.lat, userLocation.lng, {{ $residence->latitude }}, {{ $residence->longitude }})) : ''"></span>
                                    </div>
                                    @endif
                                    @if($residence->owner?->isSuperhost())
                                        <div class="absolute top-3 left-18 bg-purple-600 text-white px-2 py-1 rounded-lg text-[10px] font-bold shadow flex items-center gap-1">
                                            <svg aria-hidden="true" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            Superhost
                                        </div>
                                    @endif
                                    @if (($residence->price_per_day ?? 0) > 0)
                                        <div
                                            class="absolute top-3 right-3 bg-white/95 backdrop-blur px-2.5 py-1 rounded-lg text-sm font-bold shadow">
                                            {{ number_format($residence->price_per_day / 1000) }}k<span
                                                class="text-gray-400 font-normal text-xs">/jour</span>
                                        </div>
                                    @endif
                                    <div class="absolute bottom-3 left-3 right-3">
                                        <h4 class="font-bold text-white text-lg drop-shadow">
                                            {{ Str::limit($residence->name, 25) }}</h4>
                                        <p class="text-white/80 text-sm flex items-center gap-1">
                                            <svg aria-hidden="true" class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            {{ $residence->commune }}{{ $residence->quartier ? ', ' . $residence->quartier : '' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-1">
                                            <svg aria-hidden="true" class="w-4 h-4 text-amber-500 fill-current"
                                                viewBox="0 0 24 24">
                                                <path
                                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                            </svg>
                                            <span
                                                class="text-sm font-semibold">{{ $residence->reviews_count > 0 ? number_format($residence->average_rating, 1) : '—' }}</span>
                                            <span
                                                class="text-xs text-gray-400">({{ $residence->reviews_count ?? 0 }})</span>
                                        </div>
                                        @if ($residence->bedrooms)
                                            <div
                                                class="text-xs font-medium text-gray-500 flex items-center gap-1 bg-gray-50 px-2 py-1 rounded-full">
                                                <svg aria-hidden="true" class="w-3 h-3" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                                </svg>
                                                {{ $residence->bedrooms }} ch.
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex gap-2">
                                        <span
                                            class="flex-1 bg-orange-500 hover:bg-orange-600 text-white py-2.5 rounded-xl text-sm font-semibold shadow text-center transition">Voir
                                            détails</span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="flex-1 text-center py-12">
                                <svg aria-hidden="true" class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                                </svg>
                                <p class="text-gray-500 text-sm">Aucune résidence disponible pour le moment</p>
                            </div>
                        @endforelse

                        @if ($featuredResidences->count() > 4)
                            {{-- See More Card --}}
                            <a href="{{ route('residences.index') }}"
                                class="snap-center shrink-0 w-45 bg-linear-to-br from-orange-50 to-orange-50 rounded-2xl border-2 border-dashed border-orange-200 flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-orange-400 hover:from-orange-100 hover:to-orange-100 transition-all">
                                <div
                                    class="w-14 h-14 rounded-full bg-white shadow-md flex items-center justify-center text-orange-500">
                                    <span class="text-xl font-bold">+{{ $featuredResidences->count() - 4 }}</span>
                                </div>
                                <div class="text-center">
                                    <span class="text-sm font-semibold text-orange-600">Voir plus</span>
                                    <p class="text-xs text-orange-500/70">de résidences</p>
                                </div>
                            </a>
                        @endif
                    </div>

                    {{-- Mini-carte interactive (après géoloc) --}}
                    <div x-show="userLocation" x-cloak class="mt-6">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                                <svg aria-hidden="true" class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                Votre zone
                            </h4>
                            <a :href="mapUrl()"
                                class="text-xs font-semibold text-orange-500 hover:text-orange-600 flex items-center gap-1">
                                Agrandir
                                <svg aria-hidden="true" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                </svg>
                            </a>
                        </div>
                        <div x-ref="miniMapContainer" class="w-full h-48 sm:h-56 rounded-2xl overflow-hidden bg-gray-200 shadow-inner border border-gray-200"
                            x-init="$watch('userLocation', async (loc) => {
                                if (!loc || $refs.miniMapContainer._mapInit) return;
                                $refs.miniMapContainer._mapInit = true;
                                const token = '{{ config('services.mapbox.access_token') }}';
                                if (!window.mapboxgl) {
                                    let tries = 0;
                                    while (!window.mapboxgl && tries < 40) { await new Promise(r => setTimeout(r, 150)); tries++; }
                                }
                                if (!window.mapboxgl) return;
                                mapboxgl.accessToken = token;
                                const miniMap = new mapboxgl.Map({
                                    container: $refs.miniMapContainer,
                                    style: '{{ config('services.mapbox.style', 'mapbox://styles/mapbox/streets-v12') }}',
                                    center: [loc.lng, loc.lat],
                                    zoom: 14,
                                    attributionControl: false,
                                    interactive: true,
                                });
                                miniMap.scrollZoom.disable();
                                miniMap.on('load', () => {
                                    // Marqueur utilisateur
                                    const uel = document.createElement('div');
                                    uel.innerHTML = '<div class=&quot;relative&quot;><div class=&quot;w-4 h-4 bg-blue-600 rounded-full border-2 border-white shadow-lg&quot;></div><div class=&quot;absolute inset-0 w-4 h-4 bg-blue-500 rounded-full animate-ping opacity-50&quot;></div></div>';
                                    new mapboxgl.Marker({ element: uel }).setLngLat([loc.lng, loc.lat]).addTo(miniMap);
                                    // Marqueurs résidences
                                    const residences = @js($featuredResidences->take(8)->map(fn($r) => ['lat' => $r->latitude, 'lng' => $r->longitude, 'price' => ($r->price_per_day ?? 0) > 0 ? number_format($r->price_per_day / 1000) . 'k' : '—', 'id' => $r->id]));
                                    residences.forEach(r => {
                                        if (!r.lat || !r.lng) return;
                                        const mel = document.createElement('a');
                                        mel.href = '/residences/' + r.id;
                                        mel.innerHTML = '<div class=&quot;bg-orange-500 text-white px-1.5 py-0.5 rounded text-[10px] font-bold shadow whitespace-nowrap&quot;>' + r.price + '</div>';
                                        new mapboxgl.Marker({ element: mel, anchor: 'bottom' }).setLngLat([r.lng, r.lat]).addTo(miniMap);
                                    });
                                });
                            })">
                            {{-- Placeholder avant chargement de la carte --}}
                            <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                <svg class="animate-spin h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. SECTION MICRO-RASSURANCE —— Icônes premium avec gradient --}}
            <div class="bg-white py-10 sm:py-16 border-t border-gray-100">
                <div class="max-w-7xl mx-auto px-4 sm:px-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-10">
                        <div class="flex flex-col items-center text-center gap-4 reveal-hidden"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <div class="w-16 h-16 rounded-2xl bg-linear-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-lg shadow-orange-500/25 transition-transform duration-300 hover:scale-110">
                                <svg aria-hidden="true" class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">Logements vérifiés</div>
                                <div class="text-sm text-gray-500 mt-1">Chaque annonce est contrôlée par notre équipe REZI.</div>
                            </div>
                        </div>
                        <div class="flex flex-col items-center text-center gap-4 reveal-hidden reveal-delay-1"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <div class="w-16 h-16 rounded-2xl bg-linear-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/25 transition-transform duration-300 hover:scale-110">
                                <svg aria-hidden="true" class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">Disponibilité en temps réel</div>
                                <div class="text-sm text-gray-500 mt-1">Consultez les disponibilités à jour, sans surprise.</div>
                            </div>
                        </div>
                        <div class="flex flex-col items-center text-center gap-4 reveal-hidden reveal-delay-2"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <div class="w-16 h-16 rounded-2xl bg-linear-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/25 transition-transform duration-300 hover:scale-110">
                                <svg aria-hidden="true" class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">Contact direct</div>
                                <div class="text-sm text-gray-500 mt-1">Discutez directement avec le propriétaire, sans commission.</div>
                            </div>
                        </div>
                        <div class="flex flex-col items-center text-center gap-4 reveal-hidden reveal-delay-3"
                            x-intersect.once="$el.classList.add('reveal-visible')">
                            <div class="w-16 h-16 rounded-2xl bg-linear-to-br from-violet-400 to-violet-600 flex items-center justify-center shadow-lg shadow-violet-500/25 transition-transform duration-300 hover:scale-110">
                                <svg aria-hidden="true" class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">Carte interactive</div>
                                <div class="text-sm text-gray-500 mt-1">Explorez Abidjan par quartier avec notre carte géolocalisée.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Alpine scope pour les sections avec x-intersect (reveal animations) --}}
        <div x-data>

            {{-- 3.5 BARRE DE CATÉGORIES — Airbnb-style sticky horizontal scroll --}}
            @if($categories->isNotEmpty())
            <nav class="sticky top-14 z-30 bg-white border-b border-gray-200 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6">
                    <div class="flex flex-wrap justify-center gap-6 sm:gap-8 py-3">
                        @foreach($categories as $category)
                            <a href="{{ route('residences.index', ['category' => $category->slug]) }}"
                               class="shrink-0 flex flex-col items-center gap-1 group cursor-pointer min-w-14">
                                {{-- Icône --}}
                                <div class="w-6 h-6 text-gray-500 group-hover:text-orange-500 transition-colors duration-200">
                                    @switch($category->icon)
                                        @case('building')
                                            <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        @break
                                        @case('home')
                                            <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                        @break
                                        @case('door-open')
                                            <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                            </svg>
                                        @break
                                        @case('layer-group')
                                            <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        @break
                                        @case('bed')
                                            <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v11a1 1 0 001 1h16a1 1 0 001-1V7M3 7l9-4 9 4M3 7l9 4 9-4" />
                                            </svg>
                                        @break
                                        @case('house-user')
                                            <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                        @break
                                        @case('crown')
                                            <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3l4 9H3L5 3zm7 0l4 9h-8l4-9zm7 0l-2 9h-4l4-9zM3 15h18v6H3v-6z" />
                                            </svg>
                                        @break
                                        @case('concierge-bell')
                                            <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z" />
                                            </svg>
                                        @break
                                        @default
                                            <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                                            </svg>
                                    @endswitch
                                </div>
                                {{-- Label + compteur --}}
                                <span class="text-[10px] sm:text-xs font-medium text-gray-500 group-hover:text-orange-500 transition-colors duration-200 whitespace-nowrap">
                                    {{ $category->name }}
                                </span>
                                @if($category->residences_count > 0)
                                    <span class="text-[9px] sm:text-[10px] font-semibold text-orange-500 leading-none">
                                        {{ $category->residences_count }}
                                    </span>
                                @endif
                                {{-- Active indicator --}}
                                <div class="h-0.5 w-full bg-transparent group-hover:bg-orange-500 transition-colors duration-200 rounded-full"></div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </nav>
            @endif

                @include('home.featured')

                @include('home.popular')

                @include('home.how-it-works')

                @include('home.testimonials')

                @include('home.owners')

                @include('home.cta-mobile')

        </div> {{-- Fin du x-data pour reveal animations --}}

        </x-app-layout>
