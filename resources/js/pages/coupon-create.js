/**
 * Coupon creation form — live preview, code generator, presets, duration helpers
 */
export default function couponCreate() {
    return {
        // Form fields
        code: '',
        residenceId: '',
        discountType: 'percentage',
        discountValue: '',
        maxDiscount: '',
        minAmount: '',
        maxUses: '',
        maxUsesPerUser: '',
        startsAt: '',
        expiresAt: '',
        description: '',

        // UI state
        generating: false,
        activePreset: null,
        activeDuration: null,

        // Preset definitions
        presets: {
            welcome: { code: 'BIENVENUE', type: 'percentage', value: 10, description: 'Code de bienvenue pour nouveaux clients', duration: '1m' },
            summer: { code: 'ETE2025', type: 'fixed', value: 5000, description: 'Promotion été 2025', duration: '3m' },
            vip: { code: 'VIP20', type: 'percentage', value: 20, maxDiscount: 25000, description: 'Code VIP clients fidèles', duration: '3m' },
            flash: { code: 'FLASH15', type: 'percentage', value: 15, description: 'Vente flash — durée limitée', duration: '7d' },
            fidelity: { code: 'FIDELE', type: 'fixed', value: 10000, minAmount: 50000, description: 'Récompense fidélité client', duration: '1m' },
        },

        init() {
            // Restore old values if validation failed
            const oldCode = document.querySelector('[name="code"]')?.getAttribute('value');
            if (oldCode) this.code = oldCode;
        },

        // Code generator with animation
        generateCode() {
            this.generating = true;
            this.activePreset = null;
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let iterations = 0;
            const interval = setInterval(() => {
                let result = 'Rezi App';
                for (let i = 0; i < 6; i++) {
                    result += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                this.code = result;
                iterations++;
                if (iterations >= 8) {
                    clearInterval(interval);
                    this.generating = false;
                }
            }, 60);
        },

        // Apply a preset template
        applyPreset(name) {
            if (this.activePreset === name) {
                this.activePreset = null;
                return;
            }
            const p = this.presets[name];
            if (!p) return;
            this.activePreset = name;
            this.code = p.code || '';
            this.discountType = p.type || 'percentage';
            this.discountValue = p.value || '';
            this.maxDiscount = p.maxDiscount || '';
            this.minAmount = p.minAmount || '';
            this.description = p.description || '';
            if (p.duration) {
                this.setDuration(p.duration);
            }
        },

        // Duration quick-set
        setDuration(duration) {
            this.activeDuration = duration;
            const today = new Date();
            const fmt = (d) => d.toISOString().split('T')[0];
            this.startsAt = fmt(today);

            if (duration === 'unlimited') {
                this.startsAt = '';
                this.expiresAt = '';
            } else if (duration === 'custom') {
                // Show date inputs, user fills manually
                this.startsAt = fmt(today);
                this.expiresAt = '';
            } else if (duration === '7d') {
                const end = new Date(today); end.setDate(end.getDate() + 7);
                this.expiresAt = fmt(end);
            } else if (duration === '14d') {
                const end = new Date(today); end.setDate(end.getDate() + 14);
                this.expiresAt = fmt(end);
            } else if (duration === '1m') {
                const end = new Date(today); end.setMonth(end.getMonth() + 1);
                this.expiresAt = fmt(end);
            } else if (duration === '3m') {
                const end = new Date(today); end.setMonth(end.getMonth() + 3);
                this.expiresAt = fmt(end);
            }
        },

        // Computed: preview discount label
        get previewDiscountLabel() {
            if (!this.discountValue) return '-0%';
            if (this.discountType === 'percentage') {
                return `-${this.discountValue}%`;
            }
            return `-${Number(this.discountValue).toLocaleString('fr-FR')} F`;
        },

        // Computed: validity label for recap
        get validityLabel() {
            if (!this.startsAt && !this.expiresAt) return 'Illimitée';
            if (this.startsAt && this.expiresAt) {
                return `${this.formatDate(this.startsAt)} → ${this.formatDate(this.expiresAt)}`;
            }
            if (this.startsAt) return `Dès le ${this.formatDate(this.startsAt)}`;
            return `Jusqu'au ${this.formatDate(this.expiresAt)}`;
        },

        // Format a date string to DD/MM/YYYY
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
        },
    };
}
