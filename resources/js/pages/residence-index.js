export default function residenceSearch() {
    return {
        viewMode: 'grid',
        showAdvancedFilters: false,
        isSticky: false,
        loading: false,
        currentPage: 1,
        hasMore: true,

        get hasActiveFilters() {
            const params = new URLSearchParams(window.location.search);
            return params.has('bathrooms') || params.has('min_price') || params.has('min_surface') ||
                params.has('amenities[]') || (params.has('sort') && params.get('sort') !== 'recent');
        },

        get activeFiltersCount() {
            const params = new URLSearchParams(window.location.search);
            let count = 0;
            ['bathrooms', 'min_price', 'min_surface'].forEach(key => {
                if (params.has(key) && params.get(key)) count++;
            });
            const amenities = params.getAll('amenities[]');
            count += amenities.length;
            if (params.has('sort') && params.get('sort') !== 'recent') count++;
            return count;
        },

        init() {
            const savedMode = localStorage.getItem('residences_view_mode');
            if (savedMode) {
                this.viewMode = savedMode;
            }

            if (this.hasActiveFilters) {
                this.showAdvancedFilters = true;
            }

            // Check initial pagination state from the DOM
            const paginationMeta = document.querySelector('[data-has-more]');
            if (paginationMeta) {
                this.hasMore = paginationMeta.dataset.hasMore === 'true';
                this.currentPage = parseInt(paginationMeta.dataset.currentPage || '1', 10);
            }

            this.handleScroll();
            window.addEventListener('scroll', () => this.handleScroll());
        },

        handleScroll() {
            this.isSticky = window.scrollY > 20;
        },

        setViewMode(mode) {
            this.viewMode = mode;
            localStorage.setItem('residences_view_mode', mode);
        },

        async loadMore() {
            if (this.loading || !this.hasMore) return;

            this.loading = true;
            const nextPage = this.currentPage + 1;

            try {
                const params = new URLSearchParams(window.location.search);
                params.set('page', nextPage);

                const response = await fetch(`${window.location.pathname}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) throw new Error('Network error');

                const data = await response.json();

                // Append grid cards
                const gridContainer = document.getElementById('residences-grid');
                if (gridContainer && data.html) {
                    gridContainer.insertAdjacentHTML('beforeend', data.html);
                }

                // Append list cards
                const listContainer = document.getElementById('residences-list');
                if (listContainer && data.htmlList) {
                    listContainer.insertAdjacentHTML('beforeend', data.htmlList);
                }

                this.currentPage = nextPage;
                this.hasMore = data.hasMore;
            } catch (error) {
                console.error('Erreur chargement résidences:', error);
            } finally {
                this.loading = false;
            }
        }
    };
}
