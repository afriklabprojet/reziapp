/**
 * Coupon Show - Alpine.js component for the coupon detail page.
 * Extracted from resources/views/owner/marketing/coupons/show.blade.php
 * for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="couponShow(@js(['code' => $coupon->code]))"
 */
export default function couponShow(config = {}) {
    return {
        copied: false,
        confirmDelete: false,
        code: config.code || '',

        copyCode() {
            navigator.clipboard.writeText(this.code);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        },
    };
}
