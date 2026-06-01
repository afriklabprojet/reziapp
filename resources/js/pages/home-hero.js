/**
 * Home Hero - Alpine.js component for the hero section on the home page.
 * Extracted from resources/views/home.blade.php for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="homeHero(@js(['communes' => ..., 'mapboxToken' => ..., ...]))"
 */
export default function homeHero(config = {}) {
    return {
        radius: 500,
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
        heroExpanded: true,

        communes: config.communes || [],
        featuredResidences: config.featuredResidences || [],
        mapboxToken: config.mapboxToken || '',
        residencesIndexUrl: config.residencesIndexUrl || '/residences',
        residencesMapUrl: config.residencesMapUrl || '/residences/map',
        radiusCountsUrl: config.radiusCountsUrl || '/api/v1/geo/radius-counts',

        waitAndInitMap() {
            if (window.mapboxgl) {
                this.initHeroMap();
            } else {
                setTimeout(() => this.waitAndInitMap(), 100);
            }
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

        setRadius(r) {
            this.radius = r;
            this.resultsCount = this.radiusCounts[r] ?? 0;
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
                    this.resultsCount = this.radiusCounts[this.radius] ?? 0;
                    this.gpsState = 'success';
                    this.showResidences = true;
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
                window.location.href = this.residencesIndexUrl + '?commune=' + encodeURIComponent(this.selectedCommune);
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
            const token = this.mapboxToken;
            if (!token || !window.mapboxgl) return;

            await this.$nextTick();

            const container = this.$refs.heroMapContainer;
            if (!container || container.clientWidth === 0) {
                setTimeout(() => this.initHeroMap(), 200);
                return;
            }

            window.mapboxgl.accessToken = token;
            const defaultCenter = [-3.9962, 5.3600];
            this.heroMap = new window.mapboxgl.Map({
                container,
                style: 'mapbox://styles/mapbox/streets-v12',
                center: this.userLocation
                    ? [this.userLocation.lng, this.userLocation.lat]
                    : defaultCenter,
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

        _buildPriceMarkerEl(price) {
            const wrapper = document.createElement('div');
            wrapper.className = 'pointer-events-none';

            const badge = document.createElement('div');
            badge.className = 'bg-[#ff385c] text-white px-2 py-1 rounded-lg text-xs font-bold shadow-lg whitespace-nowrap';

            const priceText = document.createTextNode(price);
            const unitSpan = document.createElement('span');
            unitSpan.className = 'font-normal text-white/80';
            unitSpan.textContent = '/j';

            badge.appendChild(priceText);
            badge.appendChild(unitSpan);

            const dot = document.createElement('div');
            dot.className = 'w-2 h-2 bg-[#ff4d6d] rounded-full mx-auto mt-0.5 shadow';

            wrapper.appendChild(badge);
            wrapper.appendChild(dot);
            return wrapper;
        },

        _buildUserMarkerEl() {
            const wrapper = document.createElement('div');
            wrapper.className = 'relative pointer-events-none';

            const circle = document.createElement('div');
            circle.className = 'w-5 h-5 bg-blue-600 rounded-full border-3 border-white shadow-xl';

            const ping = document.createElement('div');
            ping.className = 'absolute inset-0 w-5 h-5 bg-blue-500 rounded-full animate-ping opacity-50';

            wrapper.appendChild(circle);
            wrapper.appendChild(ping);
            return wrapper;
        },

        addHeroMarkers() {
            if (!this.heroMap || !this.userLocation) return;

            this.featuredResidences.forEach(r => {
                if (!r.lat || !r.lng) return;
                const el = this._buildPriceMarkerEl(r.price);
                new window.mapboxgl.Marker({ element: el, anchor: 'bottom' })
                    .setLngLat([r.lng, r.lat])
                    .addTo(this.heroMap);
            });

            const userEl = this._buildUserMarkerEl();
            new window.mapboxgl.Marker({ element: userEl })
                .setLngLat([this.userLocation.lng, this.userLocation.lat])
                .addTo(this.heroMap);
        },
    };
}
