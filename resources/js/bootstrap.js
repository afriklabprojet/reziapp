import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Vérifier si les variables d'environnement sont définies
const reverbAppKey = import.meta.env.VITE_REVERB_APP_KEY;
const pusherAppKey = import.meta.env.VITE_PUSHER_APP_KEY;

const broadcastEnabled = import.meta.env.VITE_BROADCASTING_ENABLED === 'true';

if (broadcastEnabled && reverbAppKey) {
    // Configuration pour Laravel Reverb
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbAppKey,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? `${window.location.hostname}`,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} else if (broadcastEnabled && pusherAppKey) {
    // Configuration pour Pusher
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherAppKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'eu',
        forceTLS: true,
        encrypted: true,
    });
}
