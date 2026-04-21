export default function paymentForm(config = {}) {
    return {
        phoneNumber: config.phone || '',
        operator: 'orange_money',
        saveMethod: false,
        loading: false,
        error: null,
        showOtpModal: false,
        otp: '',
        otpError: null,
        paymentId: null,
        paymentUuid: null,
        initiateUrl: config.initiateUrl || '',
        returnUrl: config.returnUrl || '',
        csrfToken: config.csrfToken || '',

        async initiatePayment() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(this.initiateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        phone_number: this.phoneNumber.replace(/\s/g, ''),
                        operator: document.querySelector('input[name="operator"]:checked')?.value || 'orange_money',
                        save_method: this.saveMethod,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.paymentId = data.payment_id;
                    this.paymentUuid = data.payment_uuid;

                    if (data.requires_otp) {
                        this.showOtpModal = true;
                    } else {
                        // Rediriger vers la page de succès
                        window.location.href = data.redirect_url || this.returnUrl.replace(':uuid', this.paymentUuid);
                    }
                } else {
                    this.error = data.message;
                }
            } catch (_e) {
                this.error = 'Une erreur est survenue. Veuillez réessayer.';
            } finally {
                this.loading = false;
            }
        },

        async verifyOtp() {
            this.loading = true;
            this.otpError = null;

            try {
                const response = await fetch(`/payments/${this.paymentId}/verify-otp`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ otp: this.otp }),
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.redirect_url;
                } else if (data.pending) {
                    // Paiement en attente de confirmation
                    this.otpError = data.message;
                    setTimeout(() => {
                        window.location.href = this.returnUrl.replace(':uuid', this.paymentUuid);
                    }, 3000);
                } else {
                    this.otpError = data.message;
                    if (data.attempts_remaining !== null) {
                        this.otpError += ` (${data.attempts_remaining} tentative(s) restante(s))`;
                    }
                }
            } catch (_e) {
                this.otpError = 'Erreur de vérification. Veuillez réessayer.';
            } finally {
                this.loading = false;
            }
        }
    };
}
