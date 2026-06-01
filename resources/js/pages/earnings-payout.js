/**
 * Earnings Payout - Alpine.js component for the payout request section.
 * Extracted from resources/views/owner/earnings/index.blade.php
 * for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="earningsPayout()"
 */
export default function earningsPayout() {
    return {
        showPinSetup: false,
        showPayoutForm: false,
        payoutMethod: 'wave',
        amount: '',

        get isBankTransfer() {
            return this.payoutMethod === 'bank_transfer';
        },
    };
}
