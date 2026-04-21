/**
 * Mobile Filters - Alpine.js component for mobile filter modal
 * Extracted from resources/views/components/mobile-filters.blade.php
 */
export default function filterModal() {
    return {
        open: false,
        filters: {
            price_min: null,
            price_max: null,
            types: [],
            bedrooms: null,
            guests: 1,
            commune: '',
            amenities: [],
            instant_book: false,
            verified: false,
        },
        types: [
            { value: 'apartment', label: 'Appartement' },
            { value: 'house', label: 'Maison' },
            { value: 'villa', label: 'Villa' },
            { value: 'studio', label: 'Studio' },
            { value: 'room', label: 'Chambre' },
        ],
        resultsCount: '...',

        get activeFiltersCount() {
            let count = 0;
            if (this.filters.price_min || this.filters.price_max) count++;
            if (this.filters.types.length) count++;
            if (this.filters.bedrooms) count++;
            if (this.filters.guests > 1) count++;
            if (this.filters.commune) count++;
            if (this.filters.amenities.length) count++;
            if (this.filters.instant_book) count++;
            if (this.filters.verified) count++;
            return count;
        },

        toggleType(type) {
            const index = this.filters.types.indexOf(type);
            if (index === -1) {
                this.filters.types.push(type);
            } else {
                this.filters.types.splice(index, 1);
            }
            this.fetchResultsCount();
        },

        resetFilters() {
            this.filters = {
                price_min: null,
                price_max: null,
                types: [],
                bedrooms: null,
                guests: 1,
                commune: '',
                amenities: [],
                instant_book: false,
                verified: false,
            };
            this.fetchResultsCount();
        },

        async fetchResultsCount() {
            this.resultsCount = '...';
            try {
                const params = new URLSearchParams(this.buildQueryParams());
                const response = await fetch(`/api/v1/residences/count?${params}`);
                const data = await response.json();
                this.resultsCount = data.count ?? '0';
            } catch (_e) {
                this.resultsCount = '0';
            }
        },

        buildQueryParams() {
            const params = {};
            if (this.filters.price_min) params.price_min = this.filters.price_min;
            if (this.filters.price_max) params.price_max = this.filters.price_max;
            if (this.filters.types.length) params.types = this.filters.types.join(',');
            if (this.filters.bedrooms) params.bedrooms = this.filters.bedrooms === '5+' ? 5 : this.filters.bedrooms;
            if (this.filters.guests > 1) params.guests = this.filters.guests;
            if (this.filters.commune) params.commune = this.filters.commune;
            if (this.filters.amenities.length) params.amenities = this.filters.amenities.join(',');
            if (this.filters.instant_book) params.instant_book = 1;
            if (this.filters.verified) params.verified = 1;
            return params;
        },

        applyFilters() {
            const params = new URLSearchParams(this.buildQueryParams());
            window.location.href = `/residences?${params}`;
        },

        init() {
            // Récupérer les filtres de l'URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('price_min')) this.filters.price_min = urlParams.get('price_min');
            if (urlParams.get('price_max')) this.filters.price_max = urlParams.get('price_max');
            if (urlParams.get('types')) this.filters.types = urlParams.get('types').split(',');
            if (urlParams.get('bedrooms')) this.filters.bedrooms = urlParams.get('bedrooms');
            if (urlParams.get('guests')) this.filters.guests = parseInt(urlParams.get('guests'));
            if (urlParams.get('commune')) this.filters.commune = urlParams.get('commune');
            if (urlParams.get('amenities')) this.filters.amenities = urlParams.get('amenities').split(',');
            if (urlParams.get('instant_book')) this.filters.instant_book = true;
            if (urlParams.get('verified')) this.filters.verified = true;

            this.fetchResultsCount();
        }
    };
}
