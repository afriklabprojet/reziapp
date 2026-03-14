export default function priceSuggestions(config = {}) {
    const applyUrl = config.applyUrl || '';
    const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

    return {
        selected: [],
        loading: false,
        toast: { show: false, message: '', type: 'success' },

        get selectedCount() {
            return this.selected.length;
        },

        selectAll(section) {
            const checkboxes = document.querySelectorAll(`input[type="checkbox"][data-section="${section}"]`);
            const allSelected = Array.from(checkboxes).every(cb => this.selected.includes(cb.value));

            if (allSelected) {
                // Deselect all in this section
                const sectionValues = Array.from(checkboxes).map(cb => cb.value);
                this.selected = this.selected.filter(v => !sectionValues.includes(v));
            } else {
                // Select all in this section
                checkboxes.forEach(cb => {
                    if (!this.selected.includes(cb.value)) {
                        this.selected.push(cb.value);
                    }
                });
            }
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        async applySelected() {
            if (this.selected.length === 0 || this.loading) return;

            this.loading = true;
            const suggestions = this.selected.map(s => JSON.parse(s));

            try {
                const response = await fetch(applyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ suggestions })
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.selected = [];
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.showToast('Erreur lors de l\'application des suggestions', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showToast('Erreur de connexion. Veuillez réessayer.', 'error');
            } finally {
                this.loading = false;
            }
        }
    };
}
