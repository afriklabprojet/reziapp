export default function sponsoredForm(config = {}) {
    const packages = config.packages || {};

    return {
        residenceId: config.residenceId || '',
        type: config.type || 'highlighted',
        duration: config.duration || '7',

        getPackageName() {
            return packages[this.type]?.name || '-';
        },

        getBasePrice() {
            const pkg = packages[this.type];
            if (!pkg) return 0;
            return pkg.price * (parseInt(this.duration) / 7);
        },

        getDiscount() {
            if (this.duration === '14') return 10;
            if (this.duration === '30') return 20;
            return 0;
        },

        getTotalPrice() {
            const base = this.getBasePrice();
            const discount = this.getDiscount();
            return base * (1 - discount / 100);
        },

        formatPrice(price) {
            return Math.round(price).toLocaleString('fr-FR');
        }
    };
}
