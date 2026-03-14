/**
 * Verification dashboard — emergency confirmation
 * Extracted from verification/dashboard.blade.php
 */
export function confirmEmergency() {
    const modal = document.querySelector('[x-data]');
    if (modal && modal.__x) {
        modal.__x.$data.open = true;
    }
}
