export function themeToggle() {
    return {
        dark: false,

        init() {
            this.syncWithDocument();
        },

        syncWithDocument() {
            this.dark = document.documentElement.classList.contains('dark');
        },

        toggle() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        },
    };
}

export function newsletterForm(subscribeUrl = '', csrfToken = '') {
    return {
        email: '',
        loading: false,
        success: false,
        error: '',
        message: '',

        async subscribe() {
            if (!this.email || this.loading) {
                return;
            }

            this.loading = true;
            this.error = '';
            this.success = false;

            try {
                const response = await fetch(subscribeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ email: this.email, source: 'footer' }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.success = true;
                    this.message = data.message;
                    this.email = '';
                    return;
                }

                if (response.status === 422 && data.errors?.email) {
                    this.error = data.errors.email[0];
                    return;
                }

                this.error = data.message || 'Une erreur est survenue.';
            } catch {
                this.error = 'Erreur de connexion. Veuillez réessayer.';
            } finally {
                this.loading = false;
            }
        },
    };
}

export function navigationState(threshold = 8) {
    return {
        open: false,
        scrolled: false,

        init() {
            const updateScrollState = () => {
                this.scrolled = window.scrollY > threshold;
            };

            updateScrollState();
            window.addEventListener('scroll', updateScrollState, { passive: true });
        },
    };
}

export function scrollReveal(threshold = 400) {
    return {
        visible: false,

        init() {
            const updateVisibility = () => {
                this.visible = window.scrollY > threshold;
            };

            updateVisibility();
            window.addEventListener('scroll', updateVisibility, { passive: true });
        },

        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    };
}