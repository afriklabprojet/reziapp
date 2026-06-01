/**
 * Selfie Capture - Alpine.js component for the identity verification selfie page.
 * Extracted from resources/views/verification/identity/selfie.blade.php
 * for @alpinejs/csp compatibility.
 *
 * Usage in Blade:
 *   x-data="selfieCapture()"
 */
export default function selfieCapture() {
    return {
        selfiePreview: null,
        useCamera: false,
        stream: null,
        submitting: false,

        async startCamera() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: 640, height: 480 },
                });
                this.$refs.video.srcObject = this.stream;
                this.useCamera = true;
            } catch (err) {
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    alert(
                        'Accès à la caméra refusé. Vérifiez les permissions de votre navigateur ' +
                        'pour ce site, ou utilisez le bouton Choisir une photo ci-dessous.'
                    );
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    alert('Aucune caméra détectée. Veuillez utiliser le bouton Choisir une photo ci-dessous.');
                } else {
                    alert("Impossible d'accéder à la caméra: " + err.message);
                }
            }
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.useCamera = false;
        },

        capturePhoto() {
            const canvas = document.createElement('canvas');
            canvas.width = this.$refs.video.videoWidth;
            canvas.height = this.$refs.video.videoHeight;
            canvas.getContext('2d').drawImage(this.$refs.video, 0, 0);

            canvas.toBlob((blob) => {
                const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                this.$refs.selfieInput.files = dataTransfer.files;

                this.selfiePreview = canvas.toDataURL('image/jpeg');
                this.stopCamera();
            }, 'image/jpeg', 0.95);
        },

        resetSelfie() {
            this.selfiePreview = null;
            this.$refs.selfieInput.value = '';
        },
    };
}
