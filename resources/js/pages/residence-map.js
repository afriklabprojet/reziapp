const DEFAULT_CENTER = { lat: 5.36, lng: -4.0083 };
const DEFAULT_RADIUS_OPTIONS = [2, 5, 10, 20];

function toFiniteNumber(value, fallback = null) {
    const number = Number(value);
    return Number.isFinite(number) ? number : fallback;
}

function normalizeResidence(residence) {
    const latitude = toFiniteNumber(residence.location?.latitude ?? residence.latitude);
    const longitude = toFiniteNumber(residence.location?.longitude ?? residence.longitude);
    const price = toFiniteNumber(
        residence.price ?? residence.price_per_day ?? residence.price_per_month,
        0
    );
    const averageRating = toFiniteNumber(residence.average_rating, 0);
    const reviewsCount = toFiniteNumber(residence.reviews_count, 0);

    return {
        ...residence,
        title: residence.title ?? residence.name ?? 'Résidence',
        price,
        average_rating: averageRating,
        reviews_count: reviewsCount,
        bedrooms: toFiniteNumber(residence.bedrooms, 0),
        bathrooms: toFiniteNumber(residence.bathrooms, 0),
        max_guests: toFiniteNumber(residence.max_guests, 0),
        is_available: residence.is_available !== false,
        instant_book: Boolean(residence.instant_book),
        is_verified: Boolean(residence.is_verified),
        location: {
            ...residence.location,
            latitude,
            longitude,
        },
        distanceKm: null,
    };
}

function haversineDistanceKm(origin, destination) {
    if (!origin || !destination) {
        return null;
    }

    const lat1 = toFiniteNumber(origin.lat);
    const lng1 = toFiniteNumber(origin.lng);
    const lat2 = toFiniteNumber(destination.lat);
    const lng2 = toFiniteNumber(destination.lng);

    if (![lat1, lng1, lat2, lng2].every(Number.isFinite)) {
        return null;
    }

    const toRadians = (degrees) => (degrees * Math.PI) / 180;
    const earthRadiusKm = 6371;
    const deltaLat = toRadians(lat2 - lat1);
    const deltaLng = toRadians(lng2 - lng1);
    const a = Math.sin(deltaLat / 2) ** 2
        + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(deltaLng / 2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return earthRadiusKm * c;
}

function matchesSearchQuery(residence, query) {
    if (!query) {
        return true;
    }

    const haystack = [residence.title, residence.commune, residence.quartier]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

    return haystack.includes(query);
}

function matchesScalarFilters(residence, filters) {
    if (filters.filterCommune && residence.commune !== filters.filterCommune) {
        return false;
    }

    if (filters.filterType && residence.type !== filters.filterType) {
        return false;
    }

    if (filters.filterAvailability === 'available' && !residence.is_available) {
        return false;
    }

    if (filters.filterAvailability === 'unavailable' && residence.is_available) {
        return false;
    }

    if (filters.filterBedrooms && residence.bedrooms < Number(filters.filterBedrooms)) {
        return false;
    }

    if (filters.filterRating && residence.average_rating < Number(filters.filterRating)) {
        return false;
    }

    if (filters.filterInstantBook && !residence.instant_book) {
        return false;
    }

    return residence.price >= filters.filterPriceMin && residence.price <= filters.filterPriceMax;
}

function matchesLocationFilters(residence, state) {
    if (!state.locationMode) {
        return true;
    }

    if (!state.hasUserLocation() || residence.distanceKm === null) {
        return false;
    }

    return residence.distanceKm <= state.radiusKm;
}

export default function residenceMap(config = {}) {
    const priceMin = toFiniteNumber(config.priceMin, 0) ?? 0;
    const priceMax = toFiniteNumber(config.priceMax, 1000000) ?? 1000000;
    const defaultRadiusKm = toFiniteNumber(config.defaultRadiusKm, 5) ?? 5;
    const defaultCenter = {
        lat: toFiniteNumber(config.defaultCenter?.lat, DEFAULT_CENTER.lat) ?? DEFAULT_CENTER.lat,
        lng: toFiniteNumber(config.defaultCenter?.lng, DEFAULT_CENTER.lng) ?? DEFAULT_CENTER.lng,
    };

    return {
        residences: (config.residences || []).map(normalizeResidence),
        filteredResidences: [],
        filterCommune: '',
        filterPriceMin: priceMin,
        filterPriceMax: priceMax,
        priceMin,
        priceMax,
        filterType: '',
        filterAvailability: 'available',
        filterBedrooms: '',
        filterRating: '',
        filterInstantBook: false,
        showSidebar: window.innerWidth >= 1024,
        hoveredId: null,
        sortBy: 'distance',
        showFilters: false,
        searchQuery: '',
        sheetExpanded: false,
        locationMode: false,
        locationState: 'idle',
        userLocation: null,
        radiusKm: defaultRadiusKm,
        radiusOptions: DEFAULT_RADIUS_OPTIONS,
        defaultCenter,

        init() {
            const watchers = [
                'filterCommune', 'filterPriceMin', 'filterPriceMax',
                'filterType', 'filterAvailability', 'filterBedrooms',
                'filterRating', 'filterInstantBook', 'sortBy', 'searchQuery',
                'locationMode', 'radiusKm',
            ];

            watchers.forEach((watchKey) => this.$watch(watchKey, () => this.applyFilters()));

            this.$nextTick(() => {
                this.applyFilters();
                globalThis.dispatchEvent(new CustomEvent('map:update-radius', {
                    detail: { radius: this.radiusKm },
                }));
            });

            globalThis.addEventListener('map:residence-hover', (event) => {
                this.hoveredId = event.detail.id;
            });

            globalThis.addEventListener('map:residence-unhover', () => {
                this.hoveredId = null;
            });

            globalThis.addEventListener('map:user-location', (event) => {
                const lat = toFiniteNumber(event.detail.lat);
                const lng = toFiniteNumber(event.detail.lng);

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return;
                }

                this.userLocation = { lat, lng };
                this.locationState = 'ready';

                if (this.locationMode) {
                    this.applyFilters();
                }
            });

            globalThis.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    this.showSidebar = true;
                    this.sheetExpanded = false;
                }
            });
        },

        hasUserLocation() {
            return Number.isFinite(this.userLocation?.lat) && Number.isFinite(this.userLocation?.lng);
        },

        async locateMe() {
            if (!navigator.geolocation) {
                this.locationState = 'error';
                return;
            }

            this.locationState = 'loading';

            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 10000,
                    });
                });

                this.userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                this.locationState = 'ready';
                this.locationMode = true;

                globalThis.dispatchEvent(new CustomEvent('map:center-on', {
                    detail: {
                        lat: this.userLocation.lat,
                        lng: this.userLocation.lng,
                        zoom: 14,
                    },
                }));
                globalThis.dispatchEvent(new CustomEvent('map:update-radius', {
                    detail: { radius: this.radiusKm },
                }));

                this.applyFilters();
            } catch (error) {
                this.locationState = 'error';
                this.locationErrorMessage = error instanceof Error ? error.message : 'Géolocalisation indisponible';
            }
        },

        setRadius(option) {
            const radius = toFiniteNumber(option, this.radiusKm);
            if (!Number.isFinite(radius)) {
                return;
            }

            this.radiusKm = radius;
            globalThis.dispatchEvent(new CustomEvent('map:update-radius', {
                detail: { radius },
            }));
        },

        applyFilters() {
            const query = this.searchQuery.trim().toLowerCase();

            let result = this.residences
                .map((residence) => {
                    const distanceKm = this.hasUserLocation()
                        ? haversineDistanceKm(this.userLocation, {
                            lat: residence.location.latitude,
                            lng: residence.location.longitude,
                        })
                        : null;

                    return {
                        ...residence,
                        distanceKm,
                    };
                })
                .filter((residence) => matchesSearchQuery(residence, query))
                .filter((residence) => matchesScalarFilters(residence, this))
                .filter((residence) => matchesLocationFilters(residence, this));

            switch (this.sortBy) {
                case 'price_asc':
                    result = [...result].sort((left, right) => left.price - right.price);
                    break;
                case 'price_desc':
                    result = [...result].sort((left, right) => right.price - left.price);
                    break;
                case 'rating':
                    result = [...result].sort((left, right) => right.average_rating - left.average_rating);
                    break;
                case 'newest':
                    result = [...result].sort((left, right) => right.id - left.id);
                    break;
                case 'distance':
                    result = this.hasUserLocation()
                        ? [...result].sort((left, right) => (left.distanceKm ?? Number.POSITIVE_INFINITY) - (right.distanceKm ?? Number.POSITIVE_INFINITY))
                        : [...result].sort((left, right) => left.price - right.price);
                    break;
                default:
                    break;
            }

            this.filteredResidences = result;

            globalThis.dispatchEvent(new CustomEvent('map:update-residences', {
                detail: {
                    residences: result.map((residence) => ({
                        ...residence,
                        location: {
                            ...residence.location,
                            distance_meters: residence.distanceKm === null ? null : Math.round(residence.distanceKm * 1000),
                        },
                    })),
                },
            }));

            globalThis.dispatchEvent(new CustomEvent('map:fit-residences', {
                detail: {
                    residences: result,
                    userLocation: this.locationMode && this.hasUserLocation() ? this.userLocation : null,
                },
            }));
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
            this.locationMode = false;
            this.radiusKm = defaultRadiusKm;
            globalThis.dispatchEvent(new CustomEvent('map:update-radius', {
                detail: { radius: this.radiusKm },
            }));
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
            if (this.locationMode) count++;

            return count;
        },

        get stats() {
            const total = this.filteredResidences.length;
            const available = this.filteredResidences.filter((residence) => residence.is_available).length;
            const avgPrice = total > 0
                ? Math.round(this.filteredResidences.reduce((sum, residence) => sum + residence.price, 0) / total)
                : 0;
            const avgRating = total > 0
                ? (this.filteredResidences.reduce((sum, residence) => sum + residence.average_rating, 0) / total).toFixed(1)
                : '—';
            const nearestDistance = this.filteredResidences
                .map((residence) => residence.distanceKm)
                .filter((distanceKm) => distanceKm !== null)
                .sort((left, right) => left - right)[0] ?? null;

            return {
                total,
                available,
                avgPrice,
                avgRating,
                nearest: this.locationMode ? nearestDistance : null,
            };
        },

        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(price || 0));
        },

        formatDistance(distanceKm) {
            if (distanceKm === null || distanceKm === undefined) {
                return '';
            }

            if (distanceKm < 1) {
                return `${Math.round(distanceKm * 1000)} m`;
            }

            return `${distanceKm < 10 ? distanceKm.toFixed(1) : Math.round(distanceKm)} km`;
        },

        highlightOnMap(id) {
            globalThis.dispatchEvent(new CustomEvent('map:highlight-residence', {
                detail: { id },
            }));
        },
    };
}
