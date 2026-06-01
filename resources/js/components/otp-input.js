/**
 * OTP Input - Alpine.js component for 6-digit OTP entry.
 * Extracted from resources/views/verification/phone/verify.blade.php
 * for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="otpInputForm()"
 */
export default function otpInputForm() {
    return {
        code: ['', '', '', '', '', ''],
        submitting: false,

        get fullCode() {
            return this.code.join('');
        },

        focusNext(index) {
            if (this.code[index] && index < 5) {
                this.$refs['input' + (index + 1)].focus();
            }
        },

        focusPrev(index, event) {
            if (event.key === 'Backspace' && !this.code[index] && index > 0) {
                this.$refs['input' + (index - 1)].focus();
            }
        },

        handlePaste(event) {
            const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
            for (let i = 0; i < 6; i++) {
                this.code[i] = pasted[i] || '';
            }
            if (pasted.length >= 6) {
                this.$refs.input5.focus();
            }
        },
    };
}
