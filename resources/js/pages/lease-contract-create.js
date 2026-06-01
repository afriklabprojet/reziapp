/**
 * Lease Contract Create - Alpine.js components for the lease contract creation page.
 * Extracted from resources/views/owner/lease-contracts/create.blade.php
 * for @alpinejs/csp compatibility.
 */

/**
 * Tenant search autocomplete widget.
 *
 * Usage in Blade:
 *   x-data="leaseTenantSearch(@js(['selectedId' => old('tenant_id'), 'tenants' => $tenants->...]))"
 */
export function leaseTenantSearch(config = {}) {
    return {
        search: '',
        selectedId: config.selectedId || '',
        selectedName: '',
        open: false,
        tenants: config.tenants || [],

        get filtered() {
            if (!this.search) return this.tenants;
            const s = this.search.toLowerCase();
            return this.tenants.filter(
                t => t.name.toLowerCase().includes(s) || t.email.toLowerCase().includes(s)
            );
        },

        select(t) {
            this.selectedId = t.id;
            this.selectedName = t.name + ' (' + t.email + ')';
            this.search = this.selectedName;
            this.open = false;
        },

        init() {
            if (this.selectedId) {
                const t = this.tenants.find(t => t.id == this.selectedId);
                if (t) this.select(t);
            }
        },
    };
}

/**
 * Lease type / financial conditions widget.
 *
 * Usage in Blade:
 *   x-data="leaseTypeSection(@js(['leaseType' => old('lease_type', 'short_term')]))"
 */
export function leaseTypeSection(config = {}) {
    return {
        leaseType: config.leaseType || 'short_term',

        init() {
            const select = document.querySelector('[name=lease_type]');
            if (select) {
                select.addEventListener('change', e => { this.leaseType = e.target.value; });
            }
        },
    };
}

/**
 * AI clauses and included services widget.
 *
 * Usage in Blade:
 *   x-data="leaseClausesSection(@js([
 *     'services'       => old('included_services', []),
 *     'clauses'        => old('special_clauses', ''),
 *     'generateUrl'    => route('owner.ai.generate-clauses'),
 *     'suggestUrl'     => route('owner.ai.suggest-services'),
 *     'csrfToken'      => csrf_token(),
 *   ]))"
 */
export function leaseClausesSection(config = {}) {
    return {
        services: config.services || [],
        newService: '',
        clauses: config.clauses || '',
        aiLoading: false,
        aiServicesLoading: false,
        aiError: '',
        generateUrl: config.generateUrl || '',
        suggestUrl: config.suggestUrl || '',
        csrfToken: config.csrfToken || '',

        add() {
            const trimmed = this.newService.trim();
            if (trimmed && !this.services.includes(trimmed)) {
                this.services = [...this.services, trimmed];
                this.newService = '';
            }
        },

        remove(index) {
            this.services = this.services.filter((_, i) => i !== index);
        },

        async generateClauses() {
            this.aiLoading = true;
            this.aiError = '';
            try {
                const form = document.querySelector('form');
                const fd = new FormData(form);
                const res = await fetch(this.generateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        lease_type: fd.get('lease_type'),
                        monthly_rent: fd.get('monthly_rent'),
                        deposit_amount: fd.get('deposit_amount'),
                        residence_name: form.querySelector('[name=residence_id] option:checked')?.textContent?.trim() || '',
                        commune: '',
                        included_services: this.services,
                    }),
                });
                const data = await res.json();
                if (data.clauses) {
                    this.clauses = data.clauses;
                } else {
                    this.aiError = data.error || 'Erreur lors de la génération.';
                }
            } catch {
                this.aiError = 'Erreur de connexion.';
            }
            this.aiLoading = false;
        },

        async suggestServices() {
            this.aiServicesLoading = true;
            this.aiError = '';
            try {
                const form = document.querySelector('form');
                const fd = new FormData(form);
                const res = await fetch(this.suggestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        lease_type: fd.get('lease_type'),
                        monthly_rent: fd.get('monthly_rent'),
                        commune: '',
                    }),
                });
                const data = await res.json();
                if (data.services) {
                    data.services.forEach(s => {
                        if (!this.services.includes(s)) {
                            this.services = [...this.services, s];
                        }
                    });
                } else {
                    this.aiError = data.error || 'Erreur lors de la suggestion.';
                }
            } catch {
                this.aiError = 'Erreur de connexion.';
            }
            this.aiServicesLoading = false;
        },
    };
}
