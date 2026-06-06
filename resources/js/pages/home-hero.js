/**
 * Home Hero - Alpine.js component for the hero section on the home page.
 * Extracted from resources/views/home.blade.php for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="homeHero({...})"
 */
export default function homeHero(config = {}) {
    return {
        radius: Number(config.defaultRadius || 2000),
        gpsState: 'prompt',
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
        heroMarkers: [],
        userMarker: null,
        heroExpanded: true,
        googleMapsRetryCount: 0,
        googleMapsLoaderCallback: null,
        googleMapsLoaderCallbackRegistered: false,
        autoExpandedRadius: null,
        nearbyLoading: false,
        nearbyError: '',
        nearbyResidences: [],

        communes: config.communes || [],
        featuredResidences: config.featuredResidences || [],
        residencesIndexUrl: config.residencesIndexUrl || '/residences',
        residencesMapUrl: config.residencesMapUrl || '/residences/map',
        radiusCountsUrl: config.radiusCountsUrl || '/api/v1/geo/radius-counts',
        nearbyUrl: config.nearbyUrl || '/api/v1/geo/nearby',

        init() {
            this.waitAndInitMap();

            this.$watch('gpsState', (state) => {
                if (state === 'success') {
                    this.flyToUser();
                }
            });
        },

        waitAndInitMap() {
            if (globalThis.google?.maps) {
                this.googleMapsRetryCount = 0;
                this.initHeroMap();
                return;
            }

            this.registerGoogleMapsLoaderCallback();

            if (this.googleMapsRetryCount < 100) {
                this.googleMapsRetryCount += 1;
                setTimeout(() => this.waitAndInitMap(), 100);
            }
        },

        registerGoogleMapsLoaderCallback() {
            if (this.googleMapsLoaderCallbackRegistered || !Array.isArray(globalThis.__googleMapsCallbacks)) {
                return;
            }

            if (!this.googleMapsLoaderCallback) {
                this.googleMapsLoaderCallback = () => {
                    this.googleMapsRetryCount = 0;
                    this.waitAndInitMap();
                };
            }

            globalThis.__googleMapsCallbacks.push(this.googleMapsLoaderCallback);
            this.googleMapsLoaderCallbackRegistered = true;
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
                    navigator.geolocation.getCurrentPosition(
                        (position) => this.handleGeoSuccess(position),
                        (fallbackError) => {
                            this.gpsState = 'search';
                            if (fallbackError.code === 2) {
                                this.geoError = 'Position introuvable — choisissez un quartier';
                            } else {
                                this.geoError = 'Délai dépassé — choisissez un quartier';
                            }
                        },
                        { enableHighAccuracy: false, timeout: 10000, maximumAge: 30000 }
                    );
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        },

        handleGeoSuccess(position) {
            this.userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
            };
            this.gpsAccuracy = Math.round(position.coords.accuracy);
            this.fetchRadiusCounts();
        },

        mapUrl() {
            if (this.userLocation) {
                return this.residencesMapUrl + '?lat=' + this.userLocation.lat + '&lng=' + this.userLocation.lng;
            }
            return this.residencesMapUrl;
        },

        effectiveRadius() {
            const activeCount = this.radiusCounts[this.radius] ?? 0;

            if (activeCount > 0 || !this.autoExpandedRadius) {
                return this.radius;
            }

            return this.autoExpandedRadius;
        },

        radiusLabel(radius = this.effectiveRadius()) {
            const normalizedRadius = Number(radius);

            if (normalizedRadius < 1000) {
                return `${normalizedRadius} m`;
            }

            const kilometers = normalizedRadius / 1000;

            if (Number.isInteger(kilometers)) {
                return `${kilometers} km`;
            }

            return `${kilometers.toFixed(1)} km`;
        },

        radiusNotice() {
            if (!this.autoExpandedRadius || this.autoExpandedRadius === this.radius) {
                return '';
            }

            return `Aucun logement disponible dans ${this.radiusLabel(this.radius)}. Recherche élargie automatiquement à ${this.radiusLabel(this.autoExpandedRadius)}.`;
        },

        updateRadiusState() {
            const activeCount = this.radiusCounts[this.radius] ?? 0;
            const orderedRadii = Object.keys(this.radiusCounts)
                .map(Number)
                .sort((left, right) => left - right);

            this.autoExpandedRadius = null;

            if (activeCount === 0) {
                this.autoExpandedRadius = orderedRadii.find((candidate) => {
                    if (candidate <= this.radius) {
                        return false;
                    }

                    return (this.radiusCounts[candidate] ?? 0) > 0;
                }) || null;
            }

            this.resultsCount = this.radiusCounts[this.effectiveRadius()] ?? 0;
        },

        setRadius(r) {
            this.radius = Number(r);
            this.updateRadiusState();

            if (this.userLocation) {
                this.fetchNearbyResidences();
            }
        },

        async fetchNearbyResidences() {
            if (!this.userLocation) {
                return;
            }

            const effectiveRadius = this.effectiveRadius();

            if ((this.radiusCounts[effectiveRadius] ?? 0) === 0) {
                this.nearbyResidences = [];
                this.nearbyLoading = false;
                this.nearbyError = '';
                return;
            }

            this.nearbyLoading = true;
            this.nearbyError = '';

            try {
                const params = new URLSearchParams({
                    latitude: String(this.userLocation.lat),
                    longitude: String(this.userLocation.lng),
                    radius: String(effectiveRadius),
                    limit: '8',
                });
                const response = await fetch(`${this.nearbyUrl}?${params.toString()}`);
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    this.nearbyResidences = [];
                    this.nearbyError = payload.message || 'Impossible de charger les résidences proches.';
                    return;
                }

                this.nearbyResidences = payload.data || [];

                if (this.nearbyResidences.length === 0) {
                    this.nearbyError = `Aucune résidence disponible dans ${this.radiusLabel(effectiveRadius)}.`;
                }
            } catch {
                this.nearbyResidences = [];
                this.nearbyError = 'Impossible de charger les résidences proches pour le moment.';
            } finally {
                this.nearbyLoading = false;
            }
        },

        nearbyResidenceUrl(residence) {
            return residence?.urls?.show || this.residencesIndexUrl;
        },

        nearbyResidencePrice(residence) {
            return residence?.price_formatted || 'Prix sur demande';
        },

        nearbyResidenceLocation(residence) {
            const commune = residence?.location?.commune;
            const quartier = residence?.location?.quartier;
            const city = residence?.location?.city;

            return [quartier, commune, city].filter(Boolean).join(', ');
        },

        async fetchRadiusCounts() {
            if (!this.userLocation) return;
            try {
                const res = await fetch(
                    this.radiusCountsUrl +
                    '?latitude=' + this.userLocation.lat +
                    '&longitude=' + this.userLocation.lng
                );
                const json = await res.json();
                if (json.success && json.data) {
                    json.data.forEach(item => {
                        this.radiusCounts[item.radius] = item.count;
                    });

                    this.updateRadiusState();
                    this.gpsState = 'success';
                    this.showResidences = true;
                    await this.fetchNearbyResidences();
                    setTimeout(() => { this.heroExpanded = false; }, 2000);
                } else {
                    this.gpsState = 'search';
                    this.geoError = 'Vous semblez être hors de la zone couverte — choisissez un quartier';
                }
            } catch {
                this.gpsState = 'search';
                this.geoError = 'Erreur réseau — choisissez un quartier';
            }
        },

        searchByCommune() {
            if (this.selectedCommune) {
                globalThis.location.href = this.residencesIndexUrl + '?commune=' + encodeURIComponent(this.selectedCommune);
            }
        },

        haversineDistance(lat1, lng1, lat2, lng2) {
            const R = 6371000;
            const toRad = x => x * Math.PI / 180;
            const dLat = toRad(lat2 - lat1);
            const dLng = toRad(lng2 - lng1);
            const a =
                Math.sin(dLat / 2) ** 2 +
                Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        },

        formatDistance(meters) {
            if (meters < 1000) return Math.round(meters) + 'm';
            return (meters / 1000).toFixed(1) + 'km';
        },

        async initHeroMap() {
            if (this.heroMap) return;
            if (!globalThis.google?.maps) return;

            await this.$nextTick();

            const container = this.$refs.heroMapContainer;
            if (!container || container.clientWidth === 0) {
                setTimeout(() => this.initHeroMap(), 200);
                return;
            }

            const defaultCenter = { lat: 5.36, lng: -3.9962 };
            this.heroMap = new globalThis.google.maps.Map(container, {
                center: this.userLocation || defaultCenter,
                zoom: this.userLocation ? 13 : 11,
                disableDefaultUI: true,
                gestureHandling: 'none',
                clickableIcons: false,
                streetViewControl: false,
                fullscreenControl: false,
                mapTypeControl: false,
            });

            this.addHeroMarkers();
        },

        flyToUser() {
            if (!this.heroMap || !this.userLocation) return;
            this.heroMap.panTo(this.userLocation);
            this.heroMap.setZoom(13);
            this.addHeroMarkers();
        },

        _buildPriceMarkerIcon(price) {
            const safePrice = String(price || '—');
            const width = Math.max(56, safePrice.length * 9 + 18);
            const height = 34;
            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
                    <rect x="1" y="1" width="${width - 2}" height="24" rx="12" fill="#ff385c" />
                    <path d="M${width / 2 - 6} 24 L${width / 2} 32 L${width / 2 + 6} 24 Z" fill="#ff385c" />
                    <text x="${width / 2}" y="16" text-anchor="middle" font-size="11" font-weight="700" fill="#ffffff">${safePrice}</text>
                </svg>`;

            return {
                url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`,
                scaledSize: new globalThis.google.maps.Size(width, height),
                anchor: new globalThis.google.maps.Point(width / 2, height),
            };
        },

        clearHeroMarkers() {
            this.heroMarkers.forEach((marker) => marker.setMap(null));
            this.heroMarkers = [];

            if (this.userMarker) {
                this.userMarker.setMap(null);
                this.userMarker = null;
            }
        },

        addHeroMarkers() {
            if (!this.heroMap || !this.userLocation) return;

            this.clearHeroMarkers();

            this.featuredResidences.forEach(r => {
                if (!r.lat || !r.lng) return;
                const marker = new globalThis.google.maps.Marker({
                    position: { lat: r.lat, lng: r.lng },
                    map: this.heroMap,
                    icon: this._buildPriceMarkerIcon(r.price),
                    title: r.name || 'Résidence',
                });
                this.heroMarkers.push(marker);
            });

            this.userMarker = new globalThis.google.maps.Marker({
                position: this.userLocation,
                map: this.heroMap,
                title: 'Votre position',
                icon: {
                    path: globalThis.google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: '#2563eb',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 3,
                },
            });
        },
    };
}
