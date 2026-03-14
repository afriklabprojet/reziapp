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
            if (this.searchQuery.length < 3) {
                this.suggestions = [];
                return;
            }

            try {
                // Utiliser l'API Mapbox Geocoding
                const token = config.mapboxToken;
                const bbox = '-8.6,4.3,2.4,15.1'; // Bounding box CI + Burkina Faso
                const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(this.searchQuery)}.json?access_token=${token}&bbox=${bbox}&limit=5&language=fr`;

                const response = await fetch(url);
                const data = await response.json();

                this.suggestions = data.features || [];
            } catch (error) {
                console.error('Erreur recherche:', error);
                this.suggestions = [];
            }
        },

        selectLocation(suggestion) {
            this.searchQuery = suggestion.place_name || suggestion.text;
            this.longitude = suggestion.center[0];
            this.latitude = suggestion.center[1];
            this.suggestions = [];

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

            // Tri
            if (this.sortBy && this.sortBy !== 'distance') params.set('sort', this.sortBy);

            // Rediriger avec les nouveaux paramètres
            window.location.href = config.searchUrl + '?' + params.toString();
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
            };
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
