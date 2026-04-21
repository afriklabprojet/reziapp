/**
 * Push Notifications - Alpine.js component for push notification subscription
 * Extracted from resources/views/components/push-notifications.blade.php
 */
export default function pushNotifications() {
    return {
        showBanner: false,
        isSubscribed: false,
        loading: false,
        permission: 'default',
        showSuccess: false,
        showError: false,
        errorMessage: '',

        async init() {
            // Vérifier le support des notifications
            if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                console.warn('Push notifications not supported');
                return;
            }

            this.permission = Notification.permission;

            // Vérifier si déjà abonné
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            this.isSubscribed = !!subscription;

            // Afficher le banner si pas encore abonné et pas refusé
            if (!this.isSubscribed && this.permission !== 'denied') {
                const dismissed = localStorage.getItem('rezi_push_dismissed');
                const dismissedAt = dismissed ? parseInt(dismissed) : 0;
                const daysSinceDismiss = (Date.now() - dismissedAt) / (1000 * 60 * 60 * 24);

                if (!dismissed || daysSinceDismiss > 7) {
                    setTimeout(() => {
                        this.showBanner = true;
                    }, 5000);
                }
            }
        },

        async subscribe() {
            this.loading = true;

            try {
                const permission = await Notification.requestPermission();
                this.permission = permission;

                if (permission !== 'granted') {
                    this.showErrorMessage('Permission refusée');
                    this.loading = false;
                    return;
                }

                const registration = await navigator.serviceWorker.ready;

                const response = await fetch('/api/v1/push/vapid-key');
                if (!response.ok) throw new Error('VAPID key unavailable');
                const { publicKey } = await response.json();

                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(publicKey)
                });

                await fetch('/notifications/push/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(subscription)
                });

                this.isSubscribed = true;
                this.showBanner = false;
                this.showSuccessMessage();

            } catch (error) {
                console.error('Push subscription error:', error);
                this.showErrorMessage('Erreur lors de l\'activation');
            }

            this.loading = false;
        },

        dismissBanner() {
            this.showBanner = false;
            localStorage.setItem('rezi_push_dismissed', Date.now().toString());
        },

        showSuccessMessage() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false;
            }, 3000);
        },

        showErrorMessage(message) {
            this.errorMessage = message;
            this.showError = true;
            setTimeout(() => {
                this.showError = false;
            }, 5000);
        },

        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
    };
}
