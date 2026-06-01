/**
 * Residence Map - Alpine.js component for the residences map page.
 * Extracted from resources/views/residences/map.blade.php
 * for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="residenceMap(@js([
 *     'residences' => $residences->map(...)->toArray(),
 *     'priceMin'   => $priceMin,
 *     'priceMax'   => $priceMax,
 *   ]))"
 */
export default function residenceMap(config = {}) {
    const priceMin = config.priceMin ?? 0;
    const priceMax = config.priceMax ?? 1000000;

    return {
        residences: config.residences || [],
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
        sortBy: 'price_asc',
        showFilters: false,
        searchQuery: '',

        init() {
            this.applyFilters();
            const watchers = [
                'filterCommune', 'filterPriceMin', 'filterPriceMax',
                'filterType', 'filterAvailability', 'filterBedrooms',
                'filterRating', 'filterInstantBook', 'sortBy', 'searchQuery',
            ];
            watchers.forEach(w => this.$watch(w, () => this.applyFilters()));

            window.addEventListener('map:residence-hover', (e) => { this.hoveredId = e.detail.id; });
            window.addEventListener('map:residence-unhover', () => { this.hoveredId = null; });
        },

        applyFilters() {
            let result = [...this.residences];

            if (this.searchQuery.trim()) {
                const q = this.searchQuery.trim().toLowerCase();
                result = result.filter(r =>
                    (r.title?.toLowerCase().includes(q)) ||
                    (r.commune?.toLowerCase().includes(q)) ||
                    (r.quartier?.toLowerCase().includes(q))
                );
            }
            if (this.filterCommune) {
                result = result.filter(r => r.commune === this.filterCommune);
            }
            if (this.filterType) {
                result = result.filter(r => r.type === this.filterType);
            }
            if (this.filterAvailability === 'available') {
                result = result.filter(r => r.is_available);
            } else if (this.filterAvailability === 'unavailable') {
                result = result.filter(r => !r.is_available);
            }
            if (this.filterBedrooms) {
                const min = parseInt(this.filterBedrooms);
                result = result.filter(r => (r.bedrooms || 0) >= min);
            }
            if (this.filterRating) {
                const min = parseFloat(this.filterRating);
                result = result.filter(r => (r.average_rating || 0) >= min);
            }
            if (this.filterInstantBook) {
                result = result.filter(r => r.instant_book);
            }
            result = result.filter(r => r.price >= this.filterPriceMin && r.price <= this.filterPriceMax);

            switch (this.sortBy) {
                case 'price_asc':  result.sort((a, b) => a.price - b.price); break;
                case 'price_desc': result.sort((a, b) => b.price - a.price); break;
                case 'rating':     result.sort((a, b) => (b.average_rating || 0) - (a.average_rating || 0)); break;
                case 'newest':     result.sort((a, b) => b.id - a.id); break;
            }

            this.filteredResidences = result;
            window.dispatchEvent(new CustomEvent('map:update-residences', { detail: { residences: result } }));
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
            return count;
        },

        get stats() {
            const total = this.filteredResidences.length;
            const available = this.filteredResidences.filter(r => r.is_available).length;
            const avgPrice = total > 0
                ? Math.round(this.filteredResidences.reduce((sum, r) => sum + r.price, 0) / total)
                : 0;
            const avgRating = total > 0
                ? (this.filteredResidences.reduce((sum, r) => sum + (r.average_rating || 0), 0) / total).toFixed(1)
                : '—';
            return { total, available, avgPrice, avgRating };
        },

        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price);
        },

        highlightOnMap(id) {
            window.dispatchEvent(new CustomEvent('map:highlight-residence', { detail: { id } }));
        },
    };
}
