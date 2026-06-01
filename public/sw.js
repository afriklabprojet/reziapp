// REZI Service Worker v2.0 - West Africa Optimized
// Enhanced for: Offline mode, Lite mode, Low bandwidth optimization
const CACHE_VERSION = 'v8';
const CACHE_NAME = `rezi-cache-${CACHE_VERSION}`;
const IMAGES_CACHE = `rezi-images-${CACHE_VERSION}`;
const API_CACHE = `rezi-api-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

// Configuration
const CONFIG = {
    maxImageCacheSize: 100, // Nombre max d'images en cache
    maxApiCacheAge: 5 * 60 * 1000, // 5 minutes pour les données API
    liteModeBandwidth: 500, // kb/s - seuil pour activer le mode lite
    offlineQueueKey: 'rezi-offline-queue'
};

// Assets critiques à mettre en cache immédiatement
// Note: CSS/JS sont gérés par Vite avec des hashes dynamiques, pas de précache pour eux
const PRECACHE_ASSETS = [
    '/',
    '/offline.html',
    '/manifest.json',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
    '/images/logo-rezi.png'
];

// Pages importantes à pré-cacher
const RUNTIME_CACHE_URLS = [
    '/residences',
    '/residences/search',
    '/residences/map',
    '/faq',
    '/contact'
];

// API endpoints à cacher pour mode offline
const CACHEABLE_API = [
    '/api/communes',
    '/api/cities',
    '/api/amenities',
    '/api/residence-types'
];

// État global
let isLiteMode = false;
let networkSpeed = 'fast';

// Installation du service worker
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installation v2.0');

    event.waitUntil(
        Promise.all([
            // Cache principal
            caches.open(CACHE_NAME).then((cache) => {
                console.log('[ServiceWorker] Mise en cache des assets critiques');
                return cache.addAll(PRECACHE_ASSETS);
            }),
            // Cache images
            caches.open(IMAGES_CACHE),
            // Cache API
            caches.open(API_CACHE)
        ]).then(() => {
            // Warm cache des pages principales (non bloquant)
            warmCache();
            return self.skipWaiting();
        })
    );
});

// Warm cache en arrière-plan
async function warmCache() {
    const cache = await caches.open(CACHE_NAME);
    for (const url of RUNTIME_CACHE_URLS) {
        try {
            const response = await fetch(url);
            if (response.ok) {
                await cache.put(url, response);
            }
        } catch (e) {
            // Ignorer les erreurs de warm cache
        }
    }

    // Pré-cacher les données API statiques
    const apiCache = await caches.open(API_CACHE);
    for (const url of CACHEABLE_API) {
        try {
            const response = await fetch(url);
            if (response.ok) {
                await apiCache.put(url, response);
            }
        } catch (e) {
            // Ignorer
        }
    }
}

// Activation et nettoyage des anciens caches
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activation');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => {
                            // Supprimer les caches qui ne correspondent pas à la version actuelle
                            return !cacheName.includes(CACHE_VERSION);
                        })
                        .map((cacheName) => {
                            console.log('[ServiceWorker] Suppression de l\'ancien cache:', cacheName);
                            return caches.delete(cacheName);
                        })
                );
            })
            .then(() => {
                return self.clients.claim();
            })
    );
});

// Détection de la vitesse réseau
async function detectNetworkSpeed() {
    if ('connection' in navigator) {
        const conn = navigator.connection;
        const effectiveType = conn.effectiveType;
        const downlink = conn.downlink;

        if (effectiveType === 'slow-2g' || effectiveType === '2g' || downlink < 0.5) {
            networkSpeed = 'slow';
            isLiteMode = true;
        } else if (effectiveType === '3g' || downlink < 1.5) {
            networkSpeed = 'medium';
            isLiteMode = true;
        } else {
            networkSpeed = 'fast';
            isLiteMode = false;
        }
    }
    return { networkSpeed, isLiteMode };
}

// Gestion des images en mode lite (thumbnails)
function getLiteImageUrl(url) {
    if (!isLiteMode) return url;

    // Si c'est une URL d'image de résidence, demander la version réduite
    if (url.includes('/storage/residences/')) {
        // Ajouter un paramètre pour demander une version légère
        const separator = url.includes('?') ? '&' : '?';
        return `${url}${separator}w=400&q=60`;
    }
    return url;
}

// Stratégie de cache pour les images optimisée
async function handleImageRequest(request) {
    const cache = await caches.open(IMAGES_CACHE);

    // Vérifier le cache d'abord
    const cachedResponse = await cache.match(request);
    if (cachedResponse) {
        // Rafraîchir en arrière-plan pour les images consultées récemment
        refreshImageInBackground(request, cache);
        return cachedResponse;
    }

    try {
        // En mode lite, modifier l'URL pour demander une image plus petite
        const imageUrl = getLiteImageUrl(request.url);
        const fetchRequest = imageUrl !== request.url
            ? new Request(imageUrl, { mode: 'cors' })
            : request;

        const networkResponse = await fetch(fetchRequest);

        if (networkResponse.ok) {
            // Ne pas bloquer la réponse pour la mise en cache
            cache.put(request, networkResponse.clone()).catch(() => {});

            // Nettoyer le cache si trop grand
            limitImageCache(cache);
        }

        return networkResponse;
    } catch (error) {
        // Retourner une image placeholder en cas d'erreur
        return new Response('', {
            status: 404,
            statusText: 'Image not available offline'
        });
    }
}

// Rafraîchir l'image en arrière-plan
async function refreshImageInBackground(request, cache) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            await cache.put(request, response);
        }
    } catch (e) {
        // Ignorer les erreurs de rafraîchissement
    }
}

// Limiter la taille du cache d'images
async function limitImageCache(cache) {
    const keys = await cache.keys();
    if (keys.length > CONFIG.maxImageCacheSize) {
        // Supprimer les plus anciennes
        const toDelete = keys.slice(0, keys.length - CONFIG.maxImageCacheSize);
        await Promise.all(toDelete.map(key => cache.delete(key)));
    }
}

// Stratégie de cache pour les API avec expiration
async function handleApiRequest(request) {
    const cache = await caches.open(API_CACHE);
    const cachedResponse = await cache.match(request);

    // Vérifier si le cache est encore valide
    if (cachedResponse) {
        const cachedTime = cachedResponse.headers.get('sw-cached-time');
        if (cachedTime) {
            const age = Date.now() - parseInt(cachedTime);
            if (age < CONFIG.maxApiCacheAge) {
                // Cache encore valide, utiliser et rafraîchir en arrière-plan
                refreshApiInBackground(request, cache);
                return cachedResponse;
            }
        }
    }

    try {
        const response = await fetch(request);

        if (response.ok) {
            // Ajouter un timestamp pour l'expiration
            const responseWithTime = new Response(response.body, {
                status: response.status,
                statusText: response.statusText,
                headers: new Headers({
                    ...Object.fromEntries(response.headers),
                    'sw-cached-time': Date.now().toString()
                })
            });
            cache.put(request, responseWithTime).catch(() => {});
        }

        return response;
    } catch (error) {
        // Retourner le cache même expiré en cas d'erreur réseau
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

// Rafraîchir l'API en arrière-plan
async function refreshApiInBackground(request, cache) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const responseWithTime = new Response(response.body, {
                status: response.status,
                statusText: response.statusText,
                headers: new Headers({
                    ...Object.fromEntries(response.headers),
                    'sw-cached-time': Date.now().toString()
                })
            });
            await cache.put(request, responseWithTime);
        }
    } catch (e) {
        // Ignorer
    }
}

// Stratégie de cache principale
self.addEventListener('fetch', (event) => {
    // Ignorer les requêtes non-GET
    if (event.request.method !== 'GET') {
        // Pour les POST/PUT/DELETE, tenter d'enregistrer pour sync offline
        if (event.request.method === 'POST' && !navigator.onLine) {
            event.respondWith(queueOfflineRequest(event.request));
        }
        return;
    }

    const url = new URL(event.request.url);

    // Ignorer les requêtes vers d'autres origines (sauf CDN images)
    if (!event.request.url.startsWith(self.location.origin)) {
        // Permettre le cache des images CDN
        if (event.request.destination === 'image') {
            event.respondWith(handleImageRequest(event.request));
        }
        return;
    }

    // Ignorer le panneau d'administration Filament
    if (url.pathname.startsWith('/admin')) {
        return;
    }

    // Ignorer Livewire
    if (url.pathname.includes('/livewire')) {
        return;
    }

    // Ignorer les websockets
    if (url.pathname.startsWith('/broadcasting')) {
        return;
    }

    // API endpoints - stratégie stale-while-revalidate avec expiration
    if (url.pathname.startsWith('/api/')) {
        // Certains endpoints API peuvent être cachés
        const isCacheable = CACHEABLE_API.some(endpoint => url.pathname.startsWith(endpoint));
        if (isCacheable) {
            event.respondWith(handleApiRequest(event.request));
        }
        return;
    }

    // Images - stratégie Cache First avec lite mode
    if (event.request.destination === 'image') {
        event.respondWith(handleImageRequest(event.request));
        return;
    }

    // Fonts - stratégie Cache First (ne changent jamais)
    if (event.request.destination === 'font') {
        event.respondWith(
            caches.match(event.request)
                .then(cached => cached || fetchAndCache(event.request, CACHE_NAME))
        );
        return;
    }

    // CSS/JS - stratégie Stale While Revalidate
    if (event.request.destination === 'style' || event.request.destination === 'script') {
        event.respondWith(staleWhileRevalidate(event.request));
        return;
    }

    // Pages HTML - stratégie Network First avec fallback offline
    event.respondWith(handlePageRequest(event.request));
});

// Stratégie Stale While Revalidate
async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);

    const fetchPromise = fetch(request).then(response => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(() => null);

    return cachedResponse || fetchPromise;
}

// Fetch et mise en cache
async function fetchAndCache(request, cacheName) {
    const cache = await caches.open(cacheName);
    const response = await fetch(request);
    if (response.ok) {
        cache.put(request, response.clone());
    }
    return response;
}

// Gestion des requêtes de pages
async function handlePageRequest(request) {
    // Timeout adapté aux réseaux mobiles d'Afrique de l'Ouest
    const TIMEOUT_MS = 15000; // 15 secondes
    const MAX_RETRIES = 1;

    for (let attempt = 0; attempt <= MAX_RETRIES; attempt++) {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

            const networkResponse = await fetch(request, { signal: controller.signal });
            clearTimeout(timeoutId);

            if (networkResponse.ok) {
                const cache = await caches.open(CACHE_NAME);
                cache.put(request, networkResponse.clone()).catch(() => {});
            }

            // Retourner la réponse réseau même si ce n'est pas ok (ex: 404, 500)
            // pour que l'utilisateur voie l'erreur serveur plutôt que la page offline
            return networkResponse;
        } catch (error) {
            // Si c'est un abort (timeout) et qu'on a encore des tentatives, réessayer
            if (error.name === 'AbortError' && attempt < MAX_RETRIES) {
                console.log('[ServiceWorker] Timeout, tentative', attempt + 2);
                continue;
            }

            // Toutes les tentatives échouées — essayer le cache
            const cachedResponse = await caches.match(request);
            if (cachedResponse) {
                return cachedResponse;
            }

            // Essayer aussi le cache sans query string
            const url = new URL(request.url);
            if (url.search) {
                const baseUrl = url.origin + url.pathname;
                const baseCached = await caches.match(baseUrl);
                if (baseCached) {
                    return baseCached;
                }
            }

            // Dernier recours — page offline
            if (request.mode === 'navigate') {
                const offlineResponse = await caches.match(OFFLINE_URL);
                if (offlineResponse) {
                    return offlineResponse;
                }
            }

            throw error;
        }
    }
}

// File d'attente pour les requêtes offline
async function queueOfflineRequest(request) {
    try {
        // Stocker dans IndexedDB pour synchronisation ultérieure
        const db = await openOfflineDB();
        const data = {
            url: request.url,
            method: request.method,
            headers: Object.fromEntries(request.headers),
            body: await request.text(),
            timestamp: Date.now()
        };

        await db.add('offline-queue', data);

        return new Response(JSON.stringify({
            queued: true,
            message: 'Requête enregistrée pour synchronisation'
        }), {
            status: 202,
            headers: { 'Content-Type': 'application/json' }
        });
    } catch (e) {
        return new Response(JSON.stringify({
            error: true,
            message: 'Impossible de sauvegarder la requête'
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Ouvrir IndexedDB pour la file offline
function openOfflineDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('rezi-offline', 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('offline-queue')) {
                db.createObjectStore('offline-queue', { keyPath: 'timestamp' });
            }
            if (!db.objectStoreNames.contains('favorites')) {
                db.createObjectStore('favorites', { keyPath: 'id' });
            }
            if (!db.objectStoreNames.contains('viewed-residences')) {
                db.createObjectStore('viewed-residences', { keyPath: 'id' });
            }
        };
    });
}

// Gestion des notifications push
self.addEventListener('push', (event) => {
    console.log('[ServiceWorker] Notification push reçue');

    let notificationData = {
        title: 'REZI',
        body: 'Vous avez une nouvelle notification',
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/icon-72x72.png',
        tag: 'rezi-notification',
        requireInteraction: false,
        vibrate: [200, 100, 200],
        actions: []
    };

    if (event.data) {
        try {
            const data = event.data.json();
            notificationData = { ...notificationData, ...data };

            // Ajouter des actions contextuelles
            if (data.type === 'message') {
                notificationData.actions = [
                    { action: 'reply', title: 'Répondre' },
                    { action: 'view', title: 'Voir' }
                ];
            } else if (data.type === 'booking') {
                notificationData.actions = [
                    { action: 'accept', title: 'Accepter' },
                    { action: 'view', title: 'Voir' }
                ];
            }
        } catch (e) {
            notificationData.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(notificationData.title, {
            body: notificationData.body,
            icon: notificationData.icon,
            badge: notificationData.badge,
            tag: notificationData.tag,
            requireInteraction: notificationData.requireInteraction,
            vibrate: notificationData.vibrate,
            actions: notificationData.actions,
            data: notificationData.data || {}
        })
    );
});

// Gestion du clic sur les notifications
self.addEventListener('notificationclick', (event) => {
    console.log('[ServiceWorker] Clic sur notification', event.action);

    event.notification.close();

    let urlToOpen = event.notification.data?.url || '/notifications';

    // Actions spécifiques
    switch (event.action) {
        case 'reply':
            urlToOpen = event.notification.data?.conversationUrl || '/messages';
            break;
        case 'accept':
            // Accepter la réservation via API
            if (event.notification.data?.bookingId) {
                acceptBookingInBackground(event.notification.data.bookingId);
            }
            urlToOpen = event.notification.data?.bookingUrl || '/owner/reservations';
            break;
        case 'view':
            urlToOpen = event.notification.data?.url || '/notifications';
            break;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                // Chercher une fenêtre déjà ouverte
                for (const client of windowClients) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        client.focus();
                        client.navigate(urlToOpen);
                        return;
                    }
                }
                // Sinon ouvrir une nouvelle fenêtre
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Accepter une réservation en arrière-plan
async function acceptBookingInBackground(bookingId) {
    try {
        await fetch(`/api/bookings/${bookingId}/accept`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
    } catch (e) {
        console.error('[ServiceWorker] Erreur acceptation réservation:', e);
    }
}

// Background Sync - Synchronisation quand la connexion revient
self.addEventListener('sync', (event) => {
    console.log('[ServiceWorker] Sync event:', event.tag);

    switch (event.tag) {
        case 'sync-favorites':
            event.waitUntil(syncFavorites());
            break;
        case 'sync-offline-queue':
            event.waitUntil(syncOfflineQueue());
            break;
        case 'sync-viewed':
            event.waitUntil(syncViewedResidences());
            break;
    }
});

// Synchroniser les favoris
async function syncFavorites() {
    console.log('[ServiceWorker] Synchronisation des favoris');

    try {
        const db = await openOfflineDB();
        const tx = db.transaction('favorites', 'readonly');
        const store = tx.objectStore('favorites');
        const favorites = await new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });

        for (const fav of favorites) {
            if (fav.pendingSync) {
                await fetch('/api/favorites', {
                    method: fav.action === 'add' ? 'POST' : 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ residence_id: fav.id })
                });
            }
        }

        // Nettoyer après sync
        const txWrite = db.transaction('favorites', 'readwrite');
        const storeWrite = txWrite.objectStore('favorites');
        storeWrite.clear();

    } catch (e) {
        console.error('[ServiceWorker] Erreur sync favoris:', e);
    }
}

// Synchroniser la file d'attente offline
async function syncOfflineQueue() {
    console.log('[ServiceWorker] Synchronisation de la file offline');

    try {
        const db = await openOfflineDB();
        const tx = db.transaction('offline-queue', 'readwrite');
        const store = tx.objectStore('offline-queue');

        const requests = await new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });

        for (const req of requests) {
            try {
                await fetch(req.url, {
                    method: req.method,
                    headers: req.headers,
                    body: req.body
                });
                store.delete(req.timestamp);
            } catch (e) {
                // Garder pour réessayer plus tard
                console.error('[ServiceWorker] Échec sync requête:', e);
            }
        }
    } catch (e) {
        console.error('[ServiceWorker] Erreur sync queue:', e);
    }
}

// Synchroniser les résidences consultées (analytics)
async function syncViewedResidences() {
    console.log('[ServiceWorker] Synchronisation des vues');

    try {
        const db = await openOfflineDB();
        const tx = db.transaction('viewed-residences', 'readwrite');
        const store = tx.objectStore('viewed-residences');

        const viewed = await new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });

        if (viewed.length > 0) {
            await fetch('/api/analytics/batch-views', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ views: viewed })
            });
            store.clear();
        }
    } catch (e) {
        console.error('[ServiceWorker] Erreur sync vues:', e);
    }
}

// Message du client
self.addEventListener('message', (event) => {
    console.log('[ServiceWorker] Message reçu:', event.data);

    switch (event.data?.type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;

        case 'ENABLE_LITE_MODE':
            isLiteMode = true;
            break;

        case 'DISABLE_LITE_MODE':
            isLiteMode = false;
            break;

        case 'GET_CACHE_STATUS':
            getCacheStatus().then(status => {
                event.source.postMessage({ type: 'CACHE_STATUS', data: status });
            });
            break;

        case 'CLEAR_CACHE':
            clearAllCaches().then(() => {
                event.source.postMessage({ type: 'CACHE_CLEARED' });
            });
            break;

        case 'PREFETCH_RESIDENCE':
            prefetchResidence(event.data.residenceId);
            break;
    }
});

// Obtenir le statut du cache
async function getCacheStatus() {
    const cacheNames = await caches.keys();
    let totalSize = 0;
    let itemCount = 0;

    for (const name of cacheNames) {
        const cache = await caches.open(name);
        const keys = await cache.keys();
        itemCount += keys.length;
    }

    return {
        caches: cacheNames,
        itemCount,
        isLiteMode,
        networkSpeed
    };
}

// Vider tous les caches
async function clearAllCaches() {
    const cacheNames = await caches.keys();
    await Promise.all(cacheNames.map(name => caches.delete(name)));
}

// Pré-charger une résidence spécifique
async function prefetchResidence(residenceId) {
    try {
        const urls = [
            `/residences/${residenceId}`,
            `/api/residences/${residenceId}`,
            `/api/residences/${residenceId}/photos`
        ];

        const cache = await caches.open(CACHE_NAME);
        const imageCache = await caches.open(IMAGES_CACHE);

        for (const url of urls) {
            const response = await fetch(url);
            if (response.ok) {
                if (url.includes('/photos')) {
                    const photos = await response.clone().json();
                    // Pré-charger les images
                    for (const photo of photos.slice(0, 5)) {
                        const imgResponse = await fetch(photo.url);
                        if (imgResponse.ok) {
                            imageCache.put(photo.url, imgResponse);
                        }
                    }
                }
                cache.put(url, response);
            }
        }
    } catch (e) {
        console.error('[ServiceWorker] Erreur prefetch:', e);
    }
}

// Periodic Background Sync (si supporté)
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'update-favorites') {
        event.waitUntil(syncFavorites());
    }
    if (event.tag === 'update-cache') {
        event.waitUntil(warmCache());
    }
});
