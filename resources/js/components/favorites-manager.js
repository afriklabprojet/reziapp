export default function favoritesManager(config = {}) {
    const isAuthenticated = config.isAuthenticated || false;
    const syncInterval = config.syncInterval || 60000;

    return {
        isOpen: false,
        loading: false,
        favorites: [],

        init() {
            this.loadFavorites();

            // Écouter les changements de favoris
            window.addEventListener('favorite-changed', (e) => {
                if (e.detail.isFavorite) {
                    this.addToLocalList(e.detail.residenceId);
                } else {
                    this.removeFromLocalList(e.detail.residenceId);
                }
            });

            // Sync périodique pour utilisateurs connectés
            if (isAuthenticated) {
                setInterval(() => this.syncFavorites(), syncInterval);
            }
        },

        toggleDropdown() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.loadFavorites();
            }
        },

        async loadFavorites() {
            this.loading = true;

            try {
                if (isAuthenticated) {
                    // Utilisateur connecté - charger depuis le serveur
                    const response = await fetch('/api/favorites', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.favorites = data.favorites || [];

                        // Sync localStorage
                        const ids = this.favorites.map(f => f.id);
                        localStorage.setItem('rezi_favorites', JSON.stringify(ids));
                    }
                } else {
                    // Visiteur - charger depuis localStorage et récupérer les détails
                    const localIds = JSON.parse(localStorage.getItem('rezi_favorites') || '[]');

                    if (localIds.length > 0) {
                        const response = await fetch('/api/residences/preview', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            },
                            body: JSON.stringify({ ids: localIds })
                        });

                        if (response.ok) {
                            const data = await response.json();
                            this.favorites = data.residences || [];
                        }
                    } else {
                        this.favorites = [];
                    }
                }
            } catch (error) {
                console.error('Erreur chargement favoris:', error);
            } finally {
                this.loading = false;
            }
        },

        async addToLocalList(residenceId) {
            // Ajouter temporairement un placeholder
            if (!this.favorites.find(f => f.id === residenceId)) {
                this.favorites.push({
                    id: residenceId,
                    title: 'Chargement...',
                    image: null,
                    location: '',
                    price: 0
                });

                // Recharger pour avoir les vraies données
                await this.loadFavorites();
            }
        },

        removeFromLocalList(residenceId) {
            this.favorites = this.favorites.filter(f => f.id !== residenceId);
        },

        async removeFavorite(residenceId) {
            // Optimistic update
            this.removeFromLocalList(residenceId);

            try {
                if (isAuthenticated) {
                    await fetch(`/favorites/${residenceId}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    });
                } else {
                    let ids = JSON.parse(localStorage.getItem('rezi_favorites') || '[]');
                    ids = ids.filter(id => id !== residenceId);
                    localStorage.setItem('rezi_favorites', JSON.stringify(ids));
                }

                // Broadcast
                window.dispatchEvent(new CustomEvent('favorite-changed', {
                    detail: { residenceId, isFavorite: false }
                }));
            } catch (error) {
                console.error('Erreur suppression favori:', error);
                this.loadFavorites(); // Rollback
            }
        },

        async clearAllFavorites() {
            if (!confirm('Supprimer tous vos favoris ?')) return;

            const previousFavorites = [...this.favorites];
            this.favorites = [];

            try {
                if (isAuthenticated) {
                    await fetch('/api/favorites/clear', {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    });
                }

                localStorage.setItem('rezi_favorites', '[]');

                // Broadcast
                previousFavorites.forEach(f => {
                    window.dispatchEvent(new CustomEvent('favorite-changed', {
                        detail: { residenceId: f.id, isFavorite: false }
                    }));
                });
            } catch (error) {
                console.error('Erreur suppression favoris:', error);
                this.favorites = previousFavorites;
            }
        },

        async syncFavorites() {
            // Sync silencieuse en arrière-plan
            if (isAuthenticated) {
                try {
                    const response = await fetch('/api/favorites', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.favorites = data.favorites || [];
                    }
                } catch (_error) {
                    // Silencieux
                }
            }
        },

        shareFavorites() {
            const ids = this.favorites.map(f => f.id).join(',');
            const shareUrl = `${window.location.origin}/favorites/shared?ids=${ids}`;

            if (navigator.share) {
                navigator.share({
                    title: 'Mes résidences favorites sur REZI',
                    text: `Découvrez mes ${this.favorites.length} résidences préférées`,
                    url: shareUrl
                });
            } else {
                navigator.clipboard.writeText(shareUrl);
                alert('Lien copié dans le presse-papier !');
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price) + ' FCFA';
        }
    };
}
