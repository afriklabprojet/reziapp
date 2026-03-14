export default function notificationPreferences(config = {}) {
    return {
        preferences: config.preferences || {},
        saving: false,
        saved: false,
        isPushSupported: 'Notification' in window && 'serviceWorker' in navigator,
        isPushSubscribed: false,
        updateUrl: config.updateUrl || '',
        vapidUrl: config.vapidUrl || '',
        subscribeUrl: config.subscribeUrl || '',
        unsubscribeUrl: config.unsubscribeUrl || '',
        csrfToken: config.csrfToken || '',

        init() {
            if (this.isPushSupported) {
                this.checkPushSubscription();
            }
        },

        async savePreferences() {
            this.saving = true;
            this.saved = false;

            try {
                await fetch(this.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.preferences),
                });

                this.saved = true;
                setTimeout(() => this.saved = false, 2000);
            } catch (error) {
                console.error('Error saving preferences:', error);
            } finally {
                this.saving = false;
            }
        },

        async checkPushSubscription() {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            this.isPushSubscribed = !!subscription;
        },

        async subscribeToPush() {
            if (!this.isPushSupported) return;

            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') return;

                const registration = await navigator.serviceWorker.ready;

                // Get VAPID key
                const response = await fetch(this.vapidUrl);
                const { publicKey } = await response.json();

                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(publicKey),
                });

                // Send to server
                await fetch(this.subscribeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(subscription.toJSON()),
                });

                this.isPushSubscribed = true;
                location.reload();
            } catch (error) {
                console.error('Push subscription error:', error);
            }
        },

        async unsubscribeDevice(endpoint) {
            if (!confirm('Supprimer cet appareil ?')) return;

            await fetch(this.unsubscribeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({ endpoint }),
            });

            location.reload();
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
        },
    };
}
