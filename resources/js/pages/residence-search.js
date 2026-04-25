/**
 * Search Page - Alpine.js component for residence search with filters
 * Extracted from resources/views/residences/search.blade.php
 *
 * Usage in Blade:
 *   Alpine.data('searchPage', () => searchPage(config))
 *   where config is passed via @js()
 */
export default function searchPage(config) {
    return {
        // État
        searchQuery: config.commune || '',
        latitude: config.latitude || null,
        longitude: config.longitude || null,
        radius: config.radius,
        suggestions: [],
        showFilters: config.showFilters,
        mobileView: 'map',
        sortBy: config.sortBy || 'distance',
        loading: false,
        residences: config.residences,

        // Sprint 2 — Search-as-I-move
        searchAsIMove: localStorage.getItem('rezi:search-as-i-move') === '1',
        showSearchHereButton: false,
        boundsLoading: false,
        currentBounds: null,
        boundsTotal: null,

        // Filtres
        filters: {
            commune: config.filters.commune || '',
            quartier: config.filters.quartier || '',
            min_price: config.filters.min_price || '',
            max_price: config.filters.max_price || '',
            type: config.filters.type || '',
            bedrooms: config.filters.bedrooms || '',
            bathrooms: config.filters.bathrooms || '',
            max_guests: config.filters.max_guests || '',
            min_rating: config.filters.min_rating || '',
            cancellation_policy: config.filters.cancellation_policy || '',
            amenities: config.filters.amenities || [],
            instant_book: config.filters.instant_book || false,
            has_promotion: config.filters.has_promotion || false,
            available_now: config.filters.available_now || false,
            is_accessible: config.filters.is_accessible || false,
            // Sprint 2 — dates
            check_in: config.filters.check_in || '',
            check_out: config.filters.check_out || '',
            flex_window: config.filters.flex_window || 0,
            flex_dates: config.filters.flex_dates || false,
            flex_type: config.filters.flex_type || '',
            // Sprint 2 — catégorie visuelle
            category: config.filters.category || '',
        },

        // Sprint 2 — Date mode pour UI tabs
        dateMode: (() => {
            const f = config.filters || {};
            if (f.flex_type === 'weekend') return 'weekend';
            if (f.flex_type === 'month') return 'month';
            if (f.flex_type) return 'flex';
            return 'exact';
        })(),
        get todayIso() {
            return new Date().toISOString().split('T')[0];
        },

        init() {
            // Écouter les événements de la carte
            window.addEventListener('map:user-location', (e) => {
                this.latitude = e.detail.lat;
                this.longitude = e.detail.lng;
                this.updateSearch();
            });

            window.addEventListener('map:residence-hover', (e) => {
                this.highlightListItem(e.detail.id);
            });

            window.addEventListener('map:residence-unhover', (e) => {
                this.unhighlightListItem(e.detail.id);
            });

            // Sprint 2 — Search-as-I-move
            window.addEventListener('map:bounds-changed', (e) => {
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
            localStorage.setItem('rezi:search-as-i-move', this.searchAsIMove ? '1' : '0');
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
                window.dispatchEvent(new CustomEvent('map:update-residences', {
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
                // 1. Tenter l'API REZI (zones, quartiers, résidences) — POI-aware
                const reziUrl = `/api/v1/geo/autocomplete?q=${encodeURIComponent(this.searchQuery)}`;
                const reziResp = await fetch(reziUrl, { headers: { 'Accept': 'application/json' } });
                const reziJson = await reziResp.json();
                let suggestions = (reziJson.data || []).map(s => ({
                    place_name: s.label,
                    text: s.label,
                    subtitle: s.subtitle,
                    icon: s.icon,
                    type: s.type,
                    residence_id: s.residence_id || null,
                    center: [s.longitude, s.latitude],
                }));

                // 2. Compléter avec Mapbox si peu de résultats locaux (≥ 3 chars)
                if (suggestions.length < 3 && this.searchQuery.length >= 3 && config.mapboxToken) {
                    const bbox = '-8.6,4.3,2.4,15.1';
                    const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(this.searchQuery)}.json?access_token=${config.mapboxToken}&bbox=${bbox}&limit=4&language=fr`;
                    const resp = await fetch(url);
                    const data = await resp.json();
                    const mapboxSuggestions = (data.features || []).map(f => ({
                        place_name: f.place_name,
                        text: f.text,
                        subtitle: f.place_type?.[0] === 'poi' ? '📌 Lieu' : '🌍 Mapbox',
                        icon: '🌍',
                        type: 'mapbox',
                        center: f.center,
                    }));
                    suggestions = suggestions.concat(mapboxSuggestions);
                }

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
                window.location.href = `/residences/${suggestion.residence_id}`;
                return;
            }

            this.longitude = suggestion.center[0];
            this.latitude = suggestion.center[1];

            // Centrer la carte
            window.dispatchEvent(new CustomEvent('map:center-on', {
                detail: { lat: this.latitude, lng: this.longitude, zoom: 14 }
            }));

            this.updateSearch();
        },

        updateSearch() {
            // Mettre à jour le cercle de rayon
            window.dispatchEvent(new CustomEvent('map:update-radius', {
                detail: { radius: this.radius }
            }));

            // Construire les paramètres de recherche
            const params = new URLSearchParams();

            // Géolocalisation
            if (this.latitude) params.set('latitude', this.latitude);
            if (this.longitude) params.set('longitude', this.longitude);
            if (this.radius) params.set('radius', this.radius * 1000);

            // Localisation textuelle
            if (this.filters.commune) params.set('commune', this.filters.commune);
            if (this.filters.quartier) params.set('quartier', this.filters.quartier);

            // Prix
            if (this.filters.min_price) params.set('min_price', this.filters.min_price);
            if (this.filters.max_price) params.set('max_price', this.filters.max_price);

            // Type et caractéristiques
            if (this.filters.type) params.set('type', this.filters.type);
            if (this.filters.bedrooms) params.set('bedrooms', this.filters.bedrooms);
            if (this.filters.bathrooms) params.set('bathrooms', this.filters.bathrooms);
            if (this.filters.max_guests) params.set('max_guests', this.filters.max_guests);

            // Note et politique
            if (this.filters.min_rating) params.set('min_rating', this.filters.min_rating);
            if (this.filters.cancellation_policy) params.set('cancellation_policy', this.filters.cancellation_policy);

            // Équipements
            this.filters.amenities.forEach(id => params.append('amenities[]', id));

            // Options spéciales
            if (this.filters.instant_book) params.set('instant_book', '1');
            if (this.filters.has_promotion) params.set('has_promotion', '1');
            if (this.filters.available_now) params.set('available_now', '1');
            if (this.filters.is_accessible) params.set('is_accessible', '1');

            // Sprint 2 — Dates
            if (this.dateMode === 'exact' && this.filters.check_in && this.filters.check_out) {
                params.set('check_in', this.filters.check_in);
                params.set('check_out', this.filters.check_out);
                if (this.filters.flex_window > 0) params.set('flex_window', this.filters.flex_window);
            } else if (this.dateMode === 'flex' && this.filters.flex_type) {
                params.set('flex_dates', '1');
                params.set('flex_type', this.filters.flex_type);
            } else if (this.dateMode === 'weekend') {
                params.set('flex_dates', '1');
                params.set('flex_type', 'weekend');
            } else if (this.dateMode === 'month') {
                params.set('flex_dates', '1');
                params.set('flex_type', 'month');
            }

            // Sprint 2 — catégorie
            if (this.filters.category) params.set('category', this.filters.category);

            // Tri
            if (this.sortBy && this.sortBy !== 'distance') params.set('sort', this.sortBy);

            // Rediriger avec les nouveaux paramètres
            window.location.href = config.searchUrl + '?' + params.toString();
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
            this.filters = {
                commune: '',
                quartier: '',
                min_price: '',
                max_price: '',
                type: '',
                bedrooms: '',
                bathrooms: '',
                max_guests: '',
                min_rating: '',
                cancellation_policy: '',
                amenities: [],
                instant_book: false,
                has_promotion: false,
                available_now: false,
                is_accessible: false,
                check_in: '',
                check_out: '',
                flex_window: 0,
                flex_dates: false,
                flex_type: '',
                category: '',
            };
            this.dateMode = 'exact';
            this.radius = config.defaultRadius;
            this.searchQuery = '';
            this.latitude = null;
            this.longitude = null;
            this.sortBy = 'distance';

            window.location.href = config.searchUrl;
        },

        sortResidences() {
            // Envoyer le tri au serveur
            this.updateSearch();
        },

        highlightMarker(id) {
            window.dispatchEvent(new CustomEvent('map:highlight-residence', {
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
