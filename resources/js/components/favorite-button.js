export default function favoriteButton(config = {}) {
    const residenceId = config.residenceId;
    const isAuthenticated = config.isAuthenticated || false;

    return {
        residenceId: residenceId,
        isFavorite: false,
        loading: false,
        showToast: false,
        toastMessage: '',
        justToggled: false,

        init() {
            this.checkFavoriteStatus();

            // Écouter les changements de favoris d'autres composants
            window.addEventListener('favorite-changed', (e) => {
                if (e.detail.residenceId === this.residenceId) {
                    this.isFavorite = e.detail.isFavorite;
                }
            });
        },

        checkFavoriteStatus() {
            // Vérifier d'abord localStorage pour les visiteurs
            const localFavorites = JSON.parse(localStorage.getItem('rezi_favorites') || '[]');
            if (localFavorites.includes(this.residenceId)) {
                this.isFavorite = true;
            }

            // Si connecté, vérifier côté serveur
            if (isAuthenticated) {
                fetch(`/api/favorites/check/${this.residenceId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(res => res.json())
                .then(data => {
                    this.isFavorite = data.isFavorite;
                })
                .catch(() => {});
            }
        },

        async toggle() {
            if (this.loading) return;

            this.loading = true;
            const newState = !this.isFavorite;

            // Animation immédiate pour feedback
            this.isFavorite = newState;
            this.justToggled = true;
            setTimeout(() => this.justToggled = false, 600);

            try {
                if (isAuthenticated) {
                    // Utilisateur connecté - sync serveur
                    const response = await fetch(`/favorites/${this.residenceId}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        // Rollback
                        this.isFavorite = !newState;
                        throw new Error(data.message || 'Erreur');
                    }

                    this.isFavorite = data.isFavorite;
                } else {
                    // Visiteur - localStorage seulement
                    let favorites = JSON.parse(localStorage.getItem('rezi_favorites') || '[]');

                    if (newState) {
                        if (!favorites.includes(this.residenceId)) {
                            favorites.push(this.residenceId);
                        }
                    } else {
                        favorites = favorites.filter(id => id !== this.residenceId);
                    }

                    localStorage.setItem('rezi_favorites', JSON.stringify(favorites));
                }

                // Notification
                this.toastMessage = newState
                    ? 'Ajouté aux favoris'
                    : 'Retiré des favoris';
                this.showToast = true;
                setTimeout(() => this.showToast = false, 2000);

                // Broadcast aux autres composants
                window.dispatchEvent(new CustomEvent('favorite-changed', {
                    detail: { residenceId: this.residenceId, isFavorite: this.isFavorite }
                }));

                // Mettre à jour le compteur global si présent
                this.updateGlobalCounter(newState ? 1 : -1);

            } catch (error) {
                console.error('Erreur favoris:', error);
                this.toastMessage = 'Erreur, veuillez réessayer';
                this.showToast = true;
                setTimeout(() => this.showToast = false, 2000);
            } finally {
                this.loading = false;
            }
        },

        updateGlobalCounter(delta) {
            const counter = document.querySelector('[data-favorites-count]');
            if (counter) {
                const current = parseInt(counter.textContent) || 0;
                counter.textContent = Math.max(0, current + delta);
            }
        }
    };
}
