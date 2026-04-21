/**
 * Composant Alpine.js — Bande de recommandations IA
 *
 * Usage :
 *   x-data="recommendationStrip(userId, residencesSSR, limit)"
 *   x-init="init()"
 *
 * Si `userId` est fourni et `residencesSSR` est vide → chargement AJAX.
 * Sinon, utilise les données SSR directement.
 */
export default function recommendationStrip(userId, residencesSSR = null, limit = 6) {
    return {
        items: [],
        loading: false,
        error: false,

        init() {
            if (residencesSSR && residencesSSR.length > 0) {
                // Données pré-chargées côté serveur (dashboard)
                this.items = residencesSSR.slice(0, limit);
            } else if (userId) {
                this.fetchRecommendations();
            }
        },

        async fetchRecommendations() {
            this.loading = true;
            this.error = false;

            try {
                const resp = await fetch(`/api/v1/recommendations?limit=${limit}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

                const data = await resp.json();
                this.items = (data.data ?? []).slice(0, limit);
            } catch (err) {
                console.warn('[Recommendations] Chargement échoué:', err);
                this.error = true;
            } finally {
                this.loading = false;
            }
        },

        formatPrice(price) {
            if (!price) return '—';
            return new Intl.NumberFormat('fr-FR', {
                style: 'decimal',
                maximumFractionDigits: 0,
            }).format(price) + ' FCFA';
        },
    };
}
