/**
 * Promotion Form - Alpine.js component for promotion create/edit forms.
 * Extracted from resources/views/owner/marketing/promotions/{create,edit}.blade.php
 * for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="promotionForm(@js([...]))"
 */
export default function promotionForm(config = {}) {
    return {
        discountType: config.discountType || 'percentage',
        discountValue: config.discountValue || '',
        title: config.title || '',
        startsAt: config.startsAt || '',
        endsAt: config.endsAt || '',
        description: config.description || '',
        residenceId: config.residenceId || '',
        minNights: config.minNights || '',
        maxUses: config.maxUses || '',
        isActive: config.isActive ?? true,
        showDeleteConfirm: false,

        get daysCount() {
            if (!this.startsAt || !this.endsAt) return 0;
            const start = new Date(this.startsAt);
            const end = new Date(this.endsAt);
            const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            return diff > 0 ? diff : 0;
        },

        get discountLabel() {
            if (!this.discountValue) return '';
            if (this.discountType === 'percentage') return '-' + this.discountValue + '%';
            if (this.discountType === 'fixed') return '-' + Number(this.discountValue).toLocaleString('fr-FR') + ' F';
            return (
                this.discountValue +
                ' nuit' + (this.discountValue > 1 ? 's' : '') +
                ' offerte' + (this.discountValue > 1 ? 's' : '')
            );
        },
    };
}
