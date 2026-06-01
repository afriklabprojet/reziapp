/**
 * Residence Create Form - Alpine.js component for the residence creation page.
 * Extracted from resources/views/owner/residences/create.blade.php
 * for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="residenceCreateForm(@js([
 *     'description'           => old('description', ''),
 *     'houseRules'            => old('house_rules', ''),
 *     'typeLocation'          => old('type_location', 'residence_meublee'),
 *     'generateDescriptionUrl'=> route('owner.ai.generate-description'),
 *     'generateTitleUrl'      => route('owner.ai.generate-title'),
 *     'improveDescriptionUrl' => route('owner.ai.improve-description'),
 *     'csrfToken'             => csrf_token(),
 *   ]))"
 */
export default function residenceCreateForm(config = {}) {
    return {
        description: config.description || '',
        houseRules: config.houseRules || '',
        typeLocation: config.typeLocation || 'residence_meublee',
        aiLoading: false,
        aiTitleLoading: false,
        aiImproveLoading: false,
        aiError: '',
        pricePeriod: 'day',
        priceLabel: 'Prix par jour (FCFA)',
        pricePlaceholder: '15000',
        priceMin: '1000',
        priceFieldName: 'price_per_day',

        generateDescriptionUrl: config.generateDescriptionUrl || '',
        generateTitleUrl: config.generateTitleUrl || '',
        improveDescriptionUrl: config.improveDescriptionUrl || '',
        csrfToken: config.csrfToken || '',

        getFormContext() {
            const form = this.$root;
            const fd = new FormData(form);
            return {
                type: fd.get('type') || '',
                type_location: fd.get('type_location') || '',
                commune: fd.get('commune_id')
                    ? (form.querySelector('[name=commune_id] option:checked')?.textContent?.trim() || '')
                    : '',
                bedrooms: fd.get('bedrooms') || '',
                bathrooms: fd.get('bathrooms') || '',
                surface_area: fd.get('surface_area') || '',
                max_guests: fd.get('max_guests') || '',
                price: fd.get('price_per_day') || fd.get('price_per_month') || '',
            };
        },

        async generateDescription() {
            this.aiError = '';
            const ctx = this.getFormContext();
            if (!ctx.type) {
                this.aiError = "Veuillez d'abord sélectionner le type de résidence.";
                return;
            }
            this.aiLoading = true;
            try {
                const res = await fetch(this.generateDescriptionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(ctx),
                });
                const data = await res.json();
                if (data.description) {
                    this.description = data.description;
                } else {
                    this.aiError = data.error || 'Erreur lors de la génération.';
                }
            } catch {
                this.aiError = 'Erreur de connexion.';
            }
            this.aiLoading = false;
        },

        async generateTitle() {
            this.aiError = '';
            const ctx = this.getFormContext();
            if (!ctx.type) {
                this.aiError = "Veuillez d'abord sélectionner le type de résidence.";
                return;
            }
            this.aiTitleLoading = true;
            try {
                const res = await fetch(this.generateTitleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(ctx),
                });
                const data = await res.json();
                if (data.title) {
                    const nameInput = document.getElementById('name');
                    if (nameInput) nameInput.value = data.title;
                } else {
                    this.aiError = data.error || 'Erreur lors de la génération.';
                }
            } catch {
                this.aiError = 'Erreur de connexion.';
            }
            this.aiTitleLoading = false;
        },

        async improveDescription() {
            if (this.description.length < 10) {
                this.aiError = "Écrivez au moins quelques mots avant d'améliorer.";
                return;
            }
            this.aiImproveLoading = true;
            this.aiError = '';
            try {
                const ctx = this.getFormContext();
                const body = { ...ctx, description: this.description };
                const res = await fetch(this.improveDescriptionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (data.description) {
                    this.description = data.description;
                } else {
                    this.aiError = data.error || "Erreur lors de l'amélioration.";
                }
            } catch {
                this.aiError = 'Erreur de connexion.';
            }
            this.aiImproveLoading = false;
        },
    };
}
