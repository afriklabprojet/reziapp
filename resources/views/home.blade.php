<x-app-layout>
    @section('title', 'Rezi App – Location de résidences meublés à ' . ($userLocation['city'] ??
        (\App\Services\UserLocationService::current()['city'] ?? 'Abidjan')))

        @push('styles')
            {{-- Preload Mapbox pour la carte héro + mini-map + page carte --}}
            <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css" integrity="sha384-SDYx9Nwa5fE1fRuBplOPejrcbPOK/ql0Uym6hsGsTvnlC784P5LZhBJIbo8O/O+0" crossorigin="anonymous">
            <script src="https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js" defer nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" integrity="sha384-GCe89tb5amHPhp10tMEUmIOUpgyTbhqwThspGxJoQMvr5I6Zfq7lYU6ydn7dVKA6" crossorigin="anonymous"></script>
            <style>
                .mapboxgl-ctrl-logo, .mapboxgl-ctrl-attrib { display: none !important; }
            </style>
        @endpush

        @push('scripts')
            <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
                window.reziHomePage = function () {
                    return {
                        radius: @js(config('rezi.search.default_radius', 2000)),
                        preferredMaxRadius: 5000,
                        radiusOptions: @js(config('rezi.search.allowed_radii', [2000, 5000, 10000, 25000, 50000])),
                        autoExpandedRadius: null,
                        gpsState: 'prompt',
                        showResidences: false,
                        activeSlide: 0,
                        resultsCount: 0,
                        nearbyResidences: [],
                        nearbyLoading: false,
                        nearbyError: '',
                        userLocation: null,
                        searchQuery: '',
                        selectedCommune: '',
                        geoError: '',
                        radiusCounts: @js(array_fill_keys(config('rezi.search.allowed_radii', [2000, 5000, 10000, 25000, 50000]), 0)),
                        gpsAccuracy: null,
                        heroMap: null,
                        mobileMap: null,
                        mobileMapMarkers: [],
                        mobileUserMarker: null,
                        heroExpanded: true,
                        mobileMapResidences: @js($featuredResidences->take(6)->values()->map(fn($r) => [
                            'id' => $r->id,
                            'url' => route('residences.show', $r),
                            'lat' => $r->latitude,
                            'lng' => $r->longitude,
                            'price' => ($r->price_per_day ?? 0) > 0 ? number_format($r->price_per_day, 0, ',', ' ') : null,
                            'name' => Str::limit($r->name, 24),
                            'commune' => $r->commune,
                            'quartier' => $r->quartier,
                        ])),
                        communes: @js($popularZones->pluck('name')->values()),

                        waitAndInitMap() {
                            if (window.mapboxgl) { this.initHeroMap(); }
                            else { setTimeout(() => this.waitAndInitMap(), 100); }
                        },

                        waitAndInitMobileMap() {
                            if (window.mapboxgl) { this.initMobileMap(); }
                            else { setTimeout(() => this.waitAndInitMobileMap(), 100); }
                        },

                        startGeoloc() {
                            this.gpsState = 'locating';
                            this.geoError = '';
                            this.gpsAccuracy = null;

                            if (!navigator.geolocation) {
                                this.gpsState = 'search';
                                this.geoError = 'Géolocalisation non supportée — choisissez un quartier';
                                return;
                            }

                            navigator.geolocation.getCurrentPosition(
                                (position) => this.handleGeoSuccess(position),
                                (error) => {
                                    if (error.code === 1) {
                                        this.gpsState = 'search';
                                        this.geoError = 'Position refusée — choisissez un quartier ci-dessous';
                                        return;
                                    }

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

                        handleGeoSuccess(position) {
                            this.userLocation = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                            };
                            this.gpsAccuracy = Math.round(position.coords.accuracy);
                            console.log(`Position obtenue: ${this.userLocation.lat}, ${this.userLocation.lng} (±${this.gpsAccuracy}m)`);
                            this.focusMobileUserLocation();
                            this.fetchRadiusCounts();
                        },

                        mapUrl() {
                            const base = '{{ route('residences.map') }}';
                            if (this.userLocation) {
                                return base + '?lat=' + this.userLocation.lat + '&lng=' + this.userLocation.lng + '&radius=' + this.radius;
                            }
                            return base;
                        },

                        setRadius(radius) {
                            this.radius = radius;
                            this.autoExpandedRadius = null;
                            this.resultsCount = this.radiusCounts[radius] ?? 0;
                            if (this.userLocation && this.resultsCount === 0) {
                                this.smartRadiusSelection();
                            }
                            if (this.userLocation) {
                                this.fetchNearbyResidences();
                            }
                        },

                        smartRadiusSelection() {
                            const currentCount = this.radiusCounts[this.radius] ?? 0;
                            if (currentCount > 0) {
                                this.resultsCount = currentCount;
                                return;
                            }

                            const preferredFallback = this.radiusOptions.find(radius => radius > this.radius && radius <= this.preferredMaxRadius && (this.radiusCounts[radius] ?? 0) > 0);
                            const expandedFallback = this.radiusOptions.find(radius => radius > this.preferredMaxRadius && (this.radiusCounts[radius] ?? 0) > 0);
                            const widestRadius = this.radiusOptions[this.radiusOptions.length - 1] ?? this.radius;
                            const selectedRadius = preferredFallback ?? expandedFallback ?? widestRadius;

                            this.autoExpandedRadius = selectedRadius > this.preferredMaxRadius ? selectedRadius : null;
                            this.radius = selectedRadius;
                            this.resultsCount = this.radiusCounts[selectedRadius] ?? 0;
                        },

                        radiusLabel(radius = this.radius) {
                            return radius >= 1000 ? (radius / 1000) + ' km' : radius + ' m';
                        },

                        radiusNotice() {
                            if (!this.autoExpandedRadius) return '';
                            if (this.resultsCount > 0) {
                                return `Aucun logement trouvé dans 5 km. REZI a élargi automatiquement la recherche à ${this.radiusLabel(this.autoExpandedRadius)} avant d'afficher les résidences à proximité.`;
                            }
                            return `Aucun logement trouvé dans 5 km, même après élargissement automatique à ${this.radiusLabel(this.autoExpandedRadius)}.`;
                        },

                        nearbyUrl() {
                            if (!this.userLocation) return '';
                            const params = new URLSearchParams({
                                latitude: this.userLocation.lat,
                                longitude: this.userLocation.lng,
                                radius: this.radius,
                                limit: Math.min(Math.max(this.resultsCount || 12, 12), 50),
                            });
                            return `/api/v1/geo/nearby?${params.toString()}`;
                        },

                        async fetchNearbyResidences() {
                            if (!this.userLocation || this.resultsCount === 0) {
                                this.nearbyResidences = [];
                                this.showResidences = false;
                                return;
                            }

                            this.nearbyLoading = true;
                            this.nearbyError = '';
                            this.showResidences = true;

                            try {
                                const response = await fetch(this.nearbyUrl());
                                const json = await response.json();

                                if (!response.ok || !json.success) {
                                    throw new Error(json.message || 'Recherche indisponible');
                                }

                                this.nearbyResidences = json.data ?? [];
                                this.resultsCount = this.radiusCounts[this.radius] ?? json.meta?.count ?? this.nearbyResidences.length;
                                this.showResidences = this.nearbyResidences.length > 0;
                            } catch (error) {
                                console.warn('Impossible de charger les résidences proches:', error);
                                this.nearbyResidences = [];
                                this.nearbyError = 'Impossible de charger les résidences trouvées. Ouvrez la carte pour voir les résultats.';
                                this.showResidences = true;
                            } finally {
                                this.nearbyLoading = false;
                            }
                        },

                        nearbyResidenceUrl(residence) {
                            return residence?.urls?.show ?? `/residences/${residence.id}`;
                        },

                        nearbyResidencePrice(residence) {
                            return residence?.price_formatted ?? 'Prix sur demande';
                        },

                        nearbyResidenceLocation(residence) {
                            const location = residence?.location ?? {};
                            return [location.commune, location.quartier].filter(Boolean).join(', ') || 'Autour de votre position';
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
                                    this.smartRadiusSelection();
                                    this.gpsState = 'success';
                                    await this.fetchNearbyResidences();
                                    setTimeout(() => { this.heroExpanded = false; }, 2000);
                                } else {
                                    this.gpsState = 'search';
                                    this.geoError = 'Vous semblez être hors de la zone couverte — choisissez un quartier';
                                }
                            } catch (error) {
                                console.warn('Impossible de charger les compteurs:', error);
                                this.gpsState = 'search';
                                this.geoError = 'Erreur réseau — choisissez un quartier';
                            }
                        },

                        searchByCommune() {
                            if (this.selectedCommune) {
                                window.location.href = '{{ route('residences.index') }}?commune=' + encodeURIComponent(this.selectedCommune);
                            }
                        },

                        searchMobile() {
                            const query = (this.searchQuery || '').trim();

                            if (!query) {
                                window.location.href = '{{ route('residences.map') }}';
                                return;
                            }

                            const exactCommune = this.communes.find(
                                commune => commune.toLowerCase() === query.toLowerCase()
                            );

                            if (exactCommune) {
                                window.location.href = '{{ route('residences.index') }}?commune=' + encodeURIComponent(exactCommune);
                                return;
                            }

                            window.location.href = '{{ route('residences.index') }}?q=' + encodeURIComponent(query);
                        },

                        haversineDistance(lat1, lng1, lat2, lng2) {
                            const R = 6371000;
                            const toRad = x => x * Math.PI / 180;
                            const dLat = toRad(lat2 - lat1);
                            const dLng = toRad(lng2 - lng1);
                            const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
                            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                        },

                        formatDistance(meters) {
                            if (meters < 1000) return Math.round(meters) + 'm';
                            return (meters / 1000).toFixed(1) + 'km';
                        },

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
                            const defaultCenter = [-3.9962, 5.3600];
                            this.heroMap = new mapboxgl.Map({
                                container,
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

                        async initMobileMap() {
                            if (this.mobileMap) return;
                            const token = '{{ config('services.mapbox.access_token') }}';
                            if (!token || !window.mapboxgl) return;

                            await this.$nextTick();

                            const container = this.$refs.mobileMapContainer;
                            if (!container || container.clientWidth === 0) {
                                setTimeout(() => this.initMobileMap(), 200);
                                return;
                            }

                            const firstResidence = this.mobileMapResidences.find(residence => residence.lat && residence.lng);
                            const center = firstResidence ? [firstResidence.lng, firstResidence.lat] : [-3.9962, 5.3600];

                            mapboxgl.accessToken = token;
                            this.mobileMap = new mapboxgl.Map({
                                container,
                                style: 'mapbox://styles/mapbox/streets-v12',
                                center,
                                zoom: firstResidence ? 11.25 : 10.7,
                                interactive: false,
                                attributionControl: false,
                            });

                            this.mobileMap.on('load', () => {
                                this.renderMobileMapMarkers();
                                this.focusMobileUserLocation();
                                this.mobileMap.resize();
                            });
                        },

                        focusMobileUserLocation() {
                            if (!this.mobileMap || !this.userLocation || !window.mapboxgl) return;

                            this.mobileMap.flyTo({
                                center: [this.userLocation.lng, this.userLocation.lat],
                                zoom: 13.4,
                                duration: 1400,
                                essential: true,
                            });

                            if (this.mobileUserMarker) {
                                this.mobileUserMarker.remove();
                            }

                            const markerElement = document.createElement('div');
                            markerElement.innerHTML = `
                                <div style='position:relative;width:34px;height:34px;display:grid;place-items:center;'>
                                    <div style='position:absolute;inset:0;border-radius:9999px;background:rgba(45,143,131,0.22);animation:reziPulse 1.8s ease-out infinite;'></div>
                                    <div style='width:18px;height:18px;border-radius:9999px;background:#2d8f83;border:4px solid white;box-shadow:0 10px 24px rgba(20,70,65,0.35);'></div>
                                </div>
                            `;

                            if (!document.getElementById('rezi-mobile-user-marker-style')) {
                                const style = document.createElement('style');
                                style.id = 'rezi-mobile-user-marker-style';
                                style.textContent = '@keyframes reziPulse{0%{transform:scale(.65);opacity:.85}100%{transform:scale(1.55);opacity:0}}';
                                document.head.appendChild(style);
                            }

                            this.mobileUserMarker = new mapboxgl.Marker({ element: markerElement, anchor: 'center' })
                                .setLngLat([this.userLocation.lng, this.userLocation.lat])
                                .addTo(this.mobileMap);
                        },

                        renderMobileMapMarkers() {
                            if (!this.mobileMap) return;

                            this.mobileMapMarkers.forEach(marker => marker.remove());
                            this.mobileMapMarkers = [];

                            this.mobileMapResidences.forEach(residence => {
                                if (!residence.lat || !residence.lng) return;

                                const markerElement = document.createElement('button');
                                const priceLabel = residence.price ? `${residence.price} FCFA` : 'Logement';
                                const ariaPriceLabel = residence.price ? `${priceLabel} par jour` : 'logement';
                                const locationLabel = [residence.commune, residence.quartier].filter(Boolean).join(', ');
                                markerElement.type = 'button';
                                markerElement.className = 'rezi-home-mobile-marker';
                                markerElement.setAttribute('aria-label', `Voir ${residence.name}${locationLabel ? ' à ' + locationLabel : ''}, ${ariaPriceLabel}`);
                                markerElement.style.cssText = 'position:relative;min-width:56px;min-height:44px;border:0;background:transparent;padding:0;display:grid;place-items:center;cursor:pointer;touch-action:manipulation;';
                                markerElement.innerHTML = `
                                    <span style="position:relative;display:inline-flex;min-height:36px;align-items:center;justify-content:center;border-radius:9999px;background:#ffffff;padding:0 12px;color:#222222;font-size:13px;font-weight:800;line-height:1;white-space:nowrap;box-shadow:0 0 0 1px rgba(0,0,0,0.08),0 4px 14px rgba(0,0,0,0.16);transition:transform .16s ease,background .16s ease,color .16s ease;">
                                        <span data-price-label></span>
                                    </span>
                                    <span style="position:absolute;left:50%;bottom:2px;transform:translateX(-50%) rotate(45deg);width:10px;height:10px;background:#ffffff;box-shadow:1px 1px 0 rgba(0,0,0,0.08);"></span>
                                `;
                                markerElement.querySelector('[data-price-label]').textContent = priceLabel;
                                markerElement.addEventListener('click', () => {
                                    globalThis.location.href = residence.url || `/residences/${residence.id}`;
                                });
                                markerElement.addEventListener('keydown', (event) => {
                                    if (event.key !== 'Enter' && event.key !== ' ') return;
                                    event.preventDefault();
                                    globalThis.location.href = residence.url || `/residences/${residence.id}`;
                                });

                                const marker = new mapboxgl.Marker({ element: markerElement, anchor: 'bottom' })
                                    .setLngLat([residence.lng, residence.lat])
                                    .addTo(this.mobileMap);

                                this.mobileMapMarkers.push(marker);
                            });
                        },

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

                        addHeroMarkers() {
                            if (!this.heroMap || !this.userLocation) return;

                            const residences = @js($featuredResidences->take(8)->map(fn($r) => [
                                'lat' => $r->latitude,
                                'lng' => $r->longitude,
                                'price' => ($r->price_per_day ?? 0) > 0 ? number_format($r->price_per_day / 1000) . 'k' : '—',
                                'name' => Str::limit($r->name, 20),
                            ]));

                            residences.forEach(residence => {
                                if (!residence.lat || !residence.lng) return;
                                const markerElement = document.createElement('div');
                                markerElement.innerHTML = `<div class='bg-[#F16A00] text-white px-2 py-1 rounded-lg text-xs font-bold shadow-lg whitespace-nowrap'>${residence.price}<span class='font-normal text-white/80'>/j</span></div><div class='w-2 h-2 bg-[#FF8A1F] rounded-full mx-auto mt-0.5 shadow'></div>`;
                                markerElement.className = 'pointer-events-none';
                                new mapboxgl.Marker({ element: markerElement, anchor: 'bottom' })
                                    .setLngLat([residence.lng, residence.lat])
                                    .addTo(this.heroMap);
                            });

                            const userEl = document.createElement('div');
                            userEl.innerHTML = `<div class='w-5 h-5 bg-blue-600 rounded-full border-3 border-white shadow-xl'></div><div class='absolute inset-0 w-5 h-5 bg-blue-500 rounded-full animate-ping opacity-50'></div>`;
                            userEl.className = 'relative pointer-events-none';
                            new mapboxgl.Marker({ element: userEl })
                                .setLngLat([this.userLocation.lng, this.userLocation.lat])
                                .addTo(this.heroMap);
                        },
                    };
                };
            </script>
        @endpush

        {{-- APP STATE MANAGEMENT --}}
        <div x-data="reziHomePage()" class="relative bg-white flex flex-col">

            @include('home.mobile-showcase')

            <div class="hidden md:block">

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

                    {{-- Cercle de radar animé --}}
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none"
                        x-show="gpsState === 'locating'">
                        <div class="w-64 h-64 sm:w-80 sm:h-80 rounded-full border-2 border-[#F16A00]/40 animate-ping"
                            style="animation-duration: 2s;"></div>
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

                        <h1 class="font-sans text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-4">
                            Résidences meublées vérifiées
                            <span class="block text-transparent bg-clip-text bg-linear-to-r from-[#F16A00] to-[#FF8A1F]">à {{ $userLocation['city'] ?? 'Abidjan' }}</span>
                        </h1>
                        <p class="text-base sm:text-lg text-gray-300 max-w-lg mx-auto">
                            Résidences meublées vérifiées dans un rayon de <span
                                class="text-[#FF8A1F] font-semibold">2km</span>.
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
                                    class="flex-1 py-4 text-sm font-semibold text-[#F16A00] border-b-2 border-[#F16A00] bg-[#FFF4EB]/50 flex items-center justify-center gap-2">
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
                                        <div class="absolute w-24 h-24 bg-[#FFE7D1] rounded-full animate-ping opacity-50">
                                        </div>
                                        <div class="absolute w-20 h-20 bg-[#FFF4EB] rounded-full"></div>
                                        <div
                                            class="relative w-16 h-16 bg-[#F16A00] rounded-full flex items-center justify-center shadow-lg">
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
                                        class="w-full bg-[#F16A00] hover:bg-[#CC5A00] text-white font-bold py-4 px-8 rounded-2xl shadow-xl transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-3 text-lg">
                                        <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        Activer ma position
                                    </button>

                                    {{-- Lien fallback sans géoloc --}}
                                    <a href="{{ route('residences.index') }}"
                                        class="block text-center text-sm text-gray-400 hover:text-gray-700 transition-colors">
                                        Parcourir sans géolocalisation →
                                    </a>
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
                                        <label for="commune-select" class="block text-sm font-medium text-gray-700 mb-2">Choisir une
                                            zone</label>
                                        <select id="commune-select" x-model="selectedCommune"
                                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#F16A00] focus:border-[#F16A00] text-gray-900">
                                            <option value="">Sélectionner une zone...</option>
                                            <template x-for="commune in communes" :key="commune">
                                                <option :value="commune" x-text="commune"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <button @click="searchByCommune()" :disabled="!selectedCommune"
                                        :class="selectedCommune ? 'bg-[#F16A00] hover:bg-[#CC5A00]' :
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
                                            class="text-[#F16A00] hover:text-[#CC5A00] text-sm py-2 font-medium transition flex items-center gap-1">
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
                                        <div class="absolute inset-0 border-4 border-[#FFD0A3] rounded-full"></div>
                                        <div class="absolute inset-2 border-4 border-emerald-300 rounded-full animate-ping"
                                            style="animation-duration: 1.5s;"></div>
                                        <div class="absolute inset-4 border-4 border-[#FF8A1F] rounded-full animate-ping"
                                            style="animation-duration: 2s;"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="w-4 h-4 bg-[#F16A00] rounded-full shadow-lg shadow-none/50">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-xl font-bold text-gray-900">Scan en cours...</p>
                                        <p class="text-sm text-gray-500 mt-1">Analyse des résidences dans votre zone</p>
                                    </div>

                                    {{-- Progress Steps --}}
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <span class="flex items-center gap-1 text-[#F16A00]">
                                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Position
                                        </span>
                                        <span>→</span>
                                        <span class="animate-pulse text-[#F16A00]">Recherche...</span>
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
                                <div class="flex items-center gap-3 bg-[#FFF4EB] rounded-xl p-3 mb-4">
                                    <div
                                        class="w-10 h-10 bg-[#FFE7D1] rounded-full flex items-center justify-center shrink-0">
                                        <svg aria-hidden="true" class="w-5 h-5 text-[#F16A00]" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-emerald-900">Position confirmée</p>
                                        <p class="text-xs text-[#F16A00] truncate"
                                            x-text="selectedCommune ? selectedCommune : 'Votre position actuelle'">
                                        </p>
                                        {{-- Indicateur de précision GPS --}}
                                        <p x-show="gpsAccuracy !== null" x-cloak class="text-[10px] mt-0.5"
                                            :class="gpsAccuracy <= 20 ? 'text-emerald-600' : (gpsAccuracy <= 100 ?
                                                'text-[#F16A00]' : 'text-red-500')"
                                            x-text="'Précision : ±' + gpsAccuracy + 'm' + (gpsAccuracy <= 20 ? ' — Excellente' : (gpsAccuracy <= 100 ? ' — Bonne' : ' — Faible, activez le GPS'))">
                                        </p>
                                    </div>
                                    <button @click="gpsState = 'prompt'; showResidences = false; heroExpanded = true"
                                        class="text-[#F16A00] hover:text-[#CC5A00] text-xs font-medium underline">
                                        Modifier
                                    </button>
                                </div>

                                {{-- Radius Selector --}}
                                <div class="space-y-3">
                                        <span class="block text-sm font-medium text-gray-700">Rayon de recherche</span>
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                            <button @click="setRadius(2000)"
                                                :class="radius === 2000 ?
                                                    'bg-[#F16A00] text-white border-[#F16A00] shadow-lg' :
                                                'bg-white text-gray-700 border-gray-200 hover:border-emerald-300'"
                                                class="py-3 px-4 rounded-xl border-2 font-semibold transition-all">
                                                <span class="block text-lg">2 km</span>
                                                <span class="block text-xs opacity-70">Très proche</span>
                                            </button>
                                            <button @click="setRadius(5000)"
                                                :class="radius === 5000 ?
                                                    'bg-[#F16A00] text-white border-[#F16A00] shadow-lg' :
                                                'bg-white text-gray-700 border-gray-200 hover:border-emerald-300'"
                                                class="py-3 px-4 rounded-xl border-2 font-semibold transition-all">
                                                <span class="block text-lg">5 km</span>
                                                <span class="block text-xs opacity-70">Zone proche</span>
                                            </button>
                                            <button x-show="autoExpandedRadius" x-cloak @click="setRadius(autoExpandedRadius)"
                                                class="py-3 px-4 rounded-xl border-2 border-[#222222] bg-[#222222] font-semibold text-white shadow-lg transition-all sm:col-span-1">
                                                <span class="block text-lg" x-text="radiusLabel(autoExpandedRadius)"></span>
                                                <span class="block text-xs opacity-70">Élargi auto</span>
                                            </button>
                                    </div>
                                </div>

                                <div x-show="autoExpandedRadius" x-cloak class="mt-3 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-800">
                                    <span x-text="radiusNotice()"></span>
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
                                                    x-text="resultsCount > 0 ? 'résidence' + (resultsCount > 1 ? 's' : '') + ' dans ' + radiusLabel() : 'Aucune résidence dans ce rayon'"></span>
                                            </span>
                                        </div>
                                    </div>
                                    {{-- Bouton Carte amélioré --}}
                                    <a :href="mapUrl()"
                                        class="flex items-center gap-3 w-full p-3 bg-linear-to-r from-gray-900 to-gray-800 hover:from-gray-800 hover:to-gray-700 rounded-xl text-white transition-all group shadow-lg">
                                        <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center shrink-0">
                                            <svg aria-hidden="true" class="w-5 h-5 text-[#FF8A1F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <button @click="setRadius(2000)"
                                        :class="radius === 2000 ? 'bg-[#F16A00] text-white' : 'bg-gray-100 text-gray-600'"
                                        class="px-3 py-1.5 rounded-full text-xs font-bold transition-all">
                                        2 km
                                    </button>
                                    <button @click="setRadius(5000)"
                                        :class="radius === 5000 ? 'bg-[#F16A00] text-white' : 'bg-gray-100 text-gray-600'"
                                        class="px-3 py-1.5 rounded-full text-xs font-bold transition-all">
                                        5 km
                                    </button>
                                    <button x-show="autoExpandedRadius" x-cloak @click="setRadius(autoExpandedRadius)"
                                        class="rounded-full bg-gray-900 px-3 py-1.5 text-xs font-bold text-white transition-all"
                                        x-text="radiusLabel(autoExpandedRadius)">
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
                                        <svg aria-hidden="true" class="w-4 h-4 text-[#FF8A1F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                        </svg>
                                        Explorer la carte
                                    </a>
                                    <button @click="document.getElementById('nearby-residences')?.scrollIntoView({ behavior: 'smooth' })"
                                        class="flex items-center justify-center gap-1.5 px-4 py-2.5 bg-[#F16A00] hover:bg-[#CC5A00] rounded-xl text-white text-sm font-semibold transition-all shadow-lg">
                                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                        Voir
                                    </button>
                                </div>
                            </div>
                        </div>

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
                class="relative z-30 -mt-8 bg-[#F2F2F2] rounded-t-3xl pt-4 pb-8" x-cloak id="nearby-residences">

                {{-- Drag Handle --}}
                <div class="flex justify-center mb-4">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6">
                    <div class="flex items-center justify-between mb-4 sm:mb-6">
                        <div>
                            <h3 class="text-base sm:text-xl font-bold text-gray-900">Résidences à proximité</h3>
                            <p class="text-sm text-gray-500"><span x-text="resultsCount"></span> logements dans un rayon
                                de <span x-text="radiusLabel()"></span></p>
                            <p x-show="autoExpandedRadius" x-cloak class="mt-1 text-xs font-medium text-blue-700">
                                Recherche élargie automatiquement car aucun logement n'était disponible dans 5 km.
                            </p>
                        </div>
                        <a :href="mapUrl()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl shadow-lg hover:bg-gray-800 transition-all">
                            <svg aria-hidden="true" class="w-4 h-4 text-[#FF8A1F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Carte
                        </a>
                    </div>

                    {{-- Horizontal Scroll Container - Nearby API results --}}
                    <div class="flex overflow-x-auto snap-x snap-proximity gap-3 sm:gap-4 pb-4 scrollbar-hide -mx-4 px-4 sm:-mx-6 sm:px-6">
                        <template x-if="nearbyLoading">
                            <div class="flex gap-3 sm:gap-4">
                                <template x-for="index in 3" :key="index">
                                    <div class="snap-start shrink-0 w-64 sm:w-85 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg">
                                        <div class="h-44 animate-pulse bg-gray-200"></div>
                                        <div class="space-y-3 p-4">
                                            <div class="h-4 w-3/4 animate-pulse rounded bg-gray-200"></div>
                                            <div class="h-3 w-1/2 animate-pulse rounded bg-gray-100"></div>
                                            <div class="h-10 animate-pulse rounded-xl bg-gray-100"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="nearbyError && !nearbyLoading">
                            <div class="w-full rounded-2xl border border-orange-100 bg-white p-6 text-center shadow-sm">
                                <svg aria-hidden="true" class="mx-auto mb-3 h-10 w-10 text-[#F16A00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m0 3.75h.008v.008H12V16.5zm9-4.5a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm font-semibold text-gray-900" x-text="nearbyError"></p>
                                <a :href="mapUrl()" class="mt-4 inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-lg">
                                    Ouvrir la carte
                                </a>
                            </div>
                        </template>

                        <template x-for="residence in nearbyResidences" :key="residence.id">
                            <a :href="nearbyResidenceUrl(residence)"
                                class="snap-start shrink-0 w-64 sm:w-85 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden group cursor-pointer hover:-translate-y-1 hover:shadow-xl transition-all duration-300 touch-pan-x">
                                <div class="relative h-44 bg-gray-200">
                                    <img loading="lazy" :src="residence.thumbnail || '{{ asset('images/residence-placeholder.jpg') }}'"
                                        :alt="residence.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                    <div class="absolute inset-0 bg-linear-to-t from-black/55 to-transparent"></div>
                                    <div class="absolute top-3 left-3 bg-[#F16A00] text-white px-2.5 py-1 rounded-lg text-xs font-bold shadow flex items-center gap-1">
                                        <svg aria-hidden="true" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Dispo
                                    </div>
                                    <div x-show="residence.location?.distance_label" x-cloak
                                        class="absolute bottom-12 left-3 bg-blue-600 text-white px-2 py-0.5 rounded-md text-[10px] font-bold shadow flex items-center gap-1">
                                        <svg aria-hidden="true" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        <span x-text="residence.location?.distance_label"></span>
                                    </div>
                                    <div class="absolute top-3 right-3 bg-white/95 backdrop-blur px-2.5 py-1 rounded-lg text-xs font-bold shadow text-gray-900"
                                        x-text="nearbyResidencePrice(residence)"></div>
                                    <div class="absolute bottom-3 left-3 right-3">
                                        <p class="font-bold text-white text-lg drop-shadow" x-text="residence.title"></p>
                                        <p class="text-white/85 text-sm flex items-center gap-1">
                                            <svg aria-hidden="true" class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            <span class="truncate" x-text="nearbyResidenceLocation(residence)"></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-900" x-text="residence.title"></p>
                                            <p class="truncate text-xs text-gray-500" x-text="nearbyResidenceLocation(residence)"></p>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Dans le rayon</span>
                                    </div>
                                    <span class="block bg-[#F16A00] hover:bg-[#CC5A00] text-white py-2.5 rounded-xl text-sm font-semibold shadow text-center transition">
                                        Voir détails
                                    </span>
                                </div>
                            </a>
                        </template>

                        <a x-show="!nearbyLoading && !nearbyError && resultsCount > nearbyResidences.length" x-cloak :href="mapUrl()"
                            class="snap-center shrink-0 w-45 bg-linear-to-br from-[#FFF4EB] to-[#FFF4EB] rounded-2xl border-2 border-dashed border-[#F2F2F2] flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-[#FF8A1F] hover:from-[#FFE7D1] hover:to-[#FFE7D1] transition-all">
                            <div class="w-14 h-14 rounded-full bg-white shadow-md flex items-center justify-center text-[#F16A00]">
                                <span class="text-xl font-bold" x-text="'+' + (resultsCount - nearbyResidences.length)"></span>
                            </div>
                            <div class="text-center">
                                <span class="text-sm font-semibold text-[#CC5A00]">Voir sur carte</span>
                                <p class="text-xs text-[#F16A00]/70">même rayon</p>
                            </div>
                        </a>
                    </div>


                </div>
            </div>

        </div>

        {{-- Alpine scope pour les sections avec x-intersect (reveal animations) --}}
        <div x-data>

            {{-- 3.5 BARRE DE CATÉGORIES — Airbnb-style sticky horizontal scroll --}}
            @if($categories->isNotEmpty())
            <nav class="mt-0 bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto">
                    <div class="flex overflow-x-auto scrollbar-hide px-4 sm:px-6 gap-1 sm:gap-2">
                        @foreach($categories as $category)
                            <a href="{{ route('residences.index', ['category' => $category->slug]) }}"
                               class="shrink-0 flex flex-col items-center gap-1.5 py-3 px-3 sm:px-4 group cursor-pointer min-w-14 relative">
                                {{-- Icône --}}
                                <div class="w-6 h-6 text-gray-400 group-hover:text-gray-800 transition-colors duration-200">
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
                                {{-- Label --}}
                                <span class="text-xs font-medium text-gray-500 group-hover:text-gray-800 transition-colors duration-200 whitespace-nowrap">
                                    {{ $category->name }}
                                </span>
                                {{-- Active underline bar --}}
                                <div class="absolute bottom-0 left-3 right-3 h-0.5 bg-transparent group-hover:bg-gray-800 transition-colors duration-200 rounded-full"></div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </nav>
            @endif

                @include('home.featured')

                @include('home.popular')

                @include('home.testimonials')

                {{-- Section propriétaires supprimée --}}

                @include('home.cta-mobile')

            </div>

        </div> {{-- Fin du x-data pour reveal animations --}}

        </x-app-layout>
