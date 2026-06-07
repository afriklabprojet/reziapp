const DEFAULT_RADIUS_KM = 5;

function buildDefaultFilters(filters = {}) {
    return {
        city: filters.city || '',
        commune: filters.commune || '',
        quartier: filters.quartier || '',
        min_price: filters.min_price || '',
        max_price: filters.max_price || '',
        type: filters.type || '',
        bedrooms: filters.bedrooms || '',
        bathrooms: filters.bathrooms || '',
        max_guests: filters.max_guests || '',
        min_rating: filters.min_rating || '',
        cancellation_policy: filters.cancellation_policy || '',
        amenities: Array.isArray(filters.amenities) ? filters.amenities : [],
        instant_book: Boolean(filters.instant_book),
        has_promotion: Boolean(filters.has_promotion),
        available_now: Boolean(filters.available_now),
        is_accessible: Boolean(filters.is_accessible),
        check_in: filters.check_in || '',
        check_out: filters.check_out || '',
        flex_window: filters.flex_window || 0,
        flex_dates: Boolean(filters.flex_dates),
        flex_type: filters.flex_type || '',
        category: filters.category || '',
    };
}

function appendSearchParams(state, params) {
    const scalarParams = [
        ['latitude', state.latitude],
        ['longitude', state.longitude],
        ['city', state.filters.city],
        ['commune', state.filters.commune],
        ['quartier', state.filters.quartier],
        ['min_price', state.filters.min_price],
        ['max_price', state.filters.max_price],
        ['type', state.filters.type],
        ['bedrooms', state.filters.bedrooms],
        ['bathrooms', state.filters.bathrooms],
        ['max_guests', state.filters.max_guests],
        ['min_rating', state.filters.min_rating],
        ['cancellation_policy', state.filters.cancellation_policy],
        ['category', state.filters.category],
    ];

    scalarParams.forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            params.set(key, value);
        }
    });

    if (state.radius) {
        params.set('radius', Math.round(Number(state.radius) * 1000));
    }

    state.filters.amenities.forEach((id) => params.append('amenities[]', id));

    const booleanParams = [
        ['instant_book', state.filters.instant_book],
        ['has_promotion', state.filters.has_promotion],
        ['available_now', state.filters.available_now],
        ['is_accessible', state.filters.is_accessible],
    ];

    booleanParams.forEach(([key, enabled]) => {
        if (enabled) {
            params.set(key, '1');
        }
    });
}

function appendDateParams(state, params) {
    if (state.dateMode === 'exact' && state.filters.check_in && state.filters.check_out) {
        params.set('check_in', state.filters.check_in);
        params.set('check_out', state.filters.check_out);

        if (state.filters.flex_window > 0) {
            params.set('flex_window', state.filters.flex_window);
        }

        return;
    }

    if (state.dateMode === 'flex' && state.filters.flex_type) {
        params.set('flex_dates', '1');
        params.set('flex_type', state.filters.flex_type);
        return;
    }

    if (state.dateMode === 'weekend' || state.dateMode === 'month') {
        params.set('flex_dates', '1');
        params.set('flex_type', state.dateMode);
    }
}

function buildSearchParams(state) {
    const params = new URLSearchParams();

    appendSearchParams(state, params);
    appendDateParams(state, params);

    if (state.sortBy && state.sortBy !== 'distance') {
        params.set('sort', state.sortBy);
    }

    return params;
}

/**
 * Search Page - Alpine.js component for residence search with filters.
 */
export default function searchPage(config = {}) {
    const filters = buildDefaultFilters(config.filters);
    const defaultRadius = Number.isFinite(Number(config.radius))
        ? Number(config.radius)
        : Number(config.defaultRadius ?? DEFAULT_RADIUS_KM);

    return {
        // État
        searchQuery: config.searchQuery || config.commune || '',
        latitude: config.latitude || null,
        longitude: config.longitude || null,
        radius: defaultRadius,
        defaultRadius,
        suggestions: [],
        showFilters: Boolean(config.showFilters),
        mobileView: 'map',
        sortBy: config.sortBy || 'newest',
        loading: false,
        residences: Array.isArray(config.residences) ? config.residences : [],
        searchUrl: config.searchUrl || '/residences/search',

        // Sprint 2 — Search-as-I-move
        searchAsIMove: globalThis.localStorage?.getItem('rezi:search-as-i-move') === '1',
        showSearchHereButton: false,
        boundsLoading: false,
        currentBounds: null,
        boundsTotal: null,

        // Filtres
        filters,

        // Sprint 2 — Date mode pour UI tabs
        dateMode: (() => {
            const f = filters;
            if (f.flex_type === 'weekend') return 'weekend';
            if (f.flex_type === 'month') return 'month';
            if (f.flex_type) return 'flex';
            return 'exact';
        })(),
        get todayIso() {
            return new Date().toISOString().split('T')[0];
        },

        get residencesCount() {
            return Number.isFinite(Number(this.boundsTotal))
                ? Number(this.boundsTotal)
                : this.residences.length;
        },

        init() {
            // Écouter les événements de la carte
            globalThis.addEventListener('map:user-location', (e) => {
                this.latitude = e.detail.lat;
                this.longitude = e.detail.lng;
                this.updateSearch();
            });

            globalThis.addEventListener('map:residence-hover', (e) => {
                this.highlightListItem(e.detail.id);
            });

            globalThis.addEventListener('map:residence-unhover', (e) => {
                this.unhighlightListItem(e.detail.id);
            });

            // Sprint 2 — Search-as-I-move
            globalThis.addEventListener('map:bounds-changed', (e) => {
                this.currentBounds = e.detail;
                if (this.searchAsIMove) {
                    this.searchInBounds(e.detail);
                } else {
                    // Bouton "Rechercher cette zone" sinon
                    this.showSearchHereButton = true;
                }
            });
        },

        toggleSearchAsIMove() {
            this.searchAsIMove = !this.searchAsIMove;
            globalThis.localStorage?.setItem('rezi:search-as-i-move', this.searchAsIMove ? '1' : '0');
            if (this.searchAsIMove && this.currentBounds) {
                this.showSearchHereButton = false;
                this.searchInBounds(this.currentBounds);
            }
        },

        searchHere() {
            if (this.currentBounds) {
                this.searchInBounds(this.currentBounds);
                this.showSearchHereButton = false;
            }
        },        async searchInBounds(bounds) {
            this.boundsLoading = true;
            try {
                const params = new URLSearchParams({
                    sw_lat: bounds.sw_lat,
                    sw_lng: bounds.sw_lng,
                    ne_lat: bounds.ne_lat,
                    ne_lng: bounds.ne_lng,
                });

                if (this.filters.min_price) params.set('min_price', this.filters.min_price);
                if (this.filters.max_price) params.set('max_price', this.filters.max_price);
                if (this.filters.bedrooms) params.set('bedrooms', this.filters.bedrooms);
                if (this.filters.type) params.set('type', this.filters.type);
                if (this.filters.instant_book) params.set('instant_book', '1');
                this.filters.amenities.forEach(id => params.append('amenities[]', id));

                const response = await fetch(`/api/v1/maps/search-bounds?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) {
                    if (response.status === 422) {
                        // Bbox invalide ou trop large — on garde silencieux
                        return;
                    }
                    throw new Error(`HTTP ${response.status}`);
                }

                const json = await response.json();
                if (!json.success) return;

                this.residences = json.data.residences;
                this.boundsTotal = json.data.total;

                // Mettre à jour les markers
                globalThis.dispatchEvent(new CustomEvent('map:update-residences', {
                    detail: { residences: this.residences },
                }));
            } catch (error) {
                console.error('Erreur search-bounds:', error);
            } finally {
                this.boundsLoading = false;
            }
        },

        get activeFiltersCount() {
            let count = 0;
            if (this.filters.commune) count++;
            if (this.filters.quartier) count++;
            if (this.filters.min_price) count++;
            if (this.filters.max_price) count++;
            if (this.filters.type) count++;
            if (this.filters.bedrooms) count++;
            if (this.filters.bathrooms) count++;
            if (this.filters.max_guests) count++;
            if (this.filters.min_rating) count++;
            if (this.filters.cancellation_policy) count++;
            if (this.filters.amenities.length > 0) count += this.filters.amenities.length;
            if (this.filters.instant_book) count++;
            if (this.filters.has_promotion) count++;
            if (this.filters.available_now) count++;
            if (this.filters.is_accessible) count++;
            return count;
        },

        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price) + ' FCFA';
        },

        toggleAmenity(amenityId) {
            const index = this.filters.amenities.indexOf(amenityId);
            if (index === -1) {
                this.filters.amenities.push(amenityId);
            } else {
                this.filters.amenities.splice(index, 1);
            }
            this.updateSearch();
        },

        async loadQuartiers() {
            if (!this.filters.commune) {
                return;
            }
            // Les quartiers sont chargés côté serveur pour l'instant
        },

        async searchLocation() {
            if (this.searchQuery.length < 2) {
                this.suggestions = [];
                return;
            }

            try {
                // 1. Tenter l'API Rezi Studio Meublé Faya (zones, quartiers, résidences) — POI-aware
                const reziUrl = `/api/v1/geo/autocomplete?q=${encodeURIComponent(this.searchQuery)}`;
                const reziResp = await fetch(reziUrl, { headers: { 'Accept': 'application/json' } });
                const reziJson = await reziResp.json();
                const suggestions = (reziJson.data || []).map(s => ({
                    place_name: s.label,
                    text: s.label,
                    subtitle: s.subtitle,
                    icon: s.icon,
                    type: s.type,
                    residence_id: s.residence_id || null,
                    center: [s.longitude, s.latitude],
                }));

                this.suggestions = suggestions.slice(0, 10);
            } catch (error) {
                console.error('Erreur recherche:', error);
                this.suggestions = [];
            }
        },

        selectLocation(suggestion) {
            this.searchQuery = suggestion.place_name || suggestion.text;
            this.suggestions = [];

            // Si c'est une résidence directe, rediriger
            if (suggestion.type === 'residence' && suggestion.residence_id) {
                globalThis.location.href = `/residences/${suggestion.residence_id}`;
                return;
            }

            this.longitude = suggestion.center[0];
            this.latitude = suggestion.center[1];

            // Centrer la carte
            globalThis.dispatchEvent(new CustomEvent('map:center-on', {
                detail: { lat: this.latitude, lng: this.longitude, zoom: 14 }
            }));

            this.updateSearch();
        },

        updateSearch() {
            globalThis.dispatchEvent(new CustomEvent('map:update-radius', {
                detail: { radius: this.radius }
            }));

            const params = buildSearchParams(this);
            const queryString = params.toString();

            globalThis.location.href = queryString
                ? `${this.searchUrl}?${queryString}`
                : this.searchUrl;
        },

        // Sprint 2 — UI dates helpers
        setDateMode(mode) {
            this.dateMode = mode;
            if (mode === 'exact') {
                this.filters.flex_type = '';
                this.filters.flex_dates = false;
            } else if (mode === 'flex') {
                this.filters.check_in = '';
                this.filters.check_out = '';
                this.filters.flex_window = 0;
                if (!['flexible_3', 'flexible_7', 'week'].includes(this.filters.flex_type)) {
                    this.filters.flex_type = 'flexible_3';
                }
                this.filters.flex_dates = true;
                this.updateSearch();
            } else if (mode === 'weekend' || mode === 'month') {
                this.filters.check_in = '';
                this.filters.check_out = '';
                this.filters.flex_window = 0;
                this.filters.flex_type = mode;
                this.filters.flex_dates = true;
                this.updateSearch();
            }
        },

        setFlexType(type) {
            this.filters.flex_type = type;
            this.filters.flex_dates = true;
            this.updateSearch();
        },

        resetDates() {
            this.filters.check_in = '';
            this.filters.check_out = '';
            this.filters.flex_window = 0;
            this.filters.flex_type = '';
            this.filters.flex_dates = false;
            this.dateMode = 'exact';
            this.updateSearch();
        },

        resetFilters() {
            this.filters = buildDefaultFilters();
            this.dateMode = 'exact';
            this.radius = this.defaultRadius;
            this.searchQuery = '';
            this.latitude = null;
            this.longitude = null;
            this.sortBy = 'newest';
            this.boundsTotal = null;

            globalThis.location.href = this.searchUrl;
        },

        sortResidences() {
            // Envoyer le tri au serveur
            this.updateSearch();
        },

        formatResidenceType(type) {
            if (!type) {
                return '';
            }

            return `${type.charAt(0).toUpperCase()}${type.slice(1)}`;
        },

        highlightMarker(id) {
            globalThis.dispatchEvent(new CustomEvent('map:highlight-residence', {
                detail: { id }
            }));
        },

        unhighlightMarker() {
            // Géré par la carte
        },

        highlightListItem(id) {
            const item = document.querySelector(`.residence-list-item[data-id="${id}"]`);
            if (item) {
                item.classList.add('ring-2', 'ring-blue-500');
                item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        },

        unhighlightListItem(id) {
            const item = document.querySelector(`.residence-list-item[data-id="${id}"]`);
            if (item) {
                item.classList.remove('ring-2', 'ring-blue-500');
            }
        }
    };
}
