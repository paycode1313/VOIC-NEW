const CACHE_NAME = 'voic-cache-v4';
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/icon.svg',
    '/favicon.ico',
    '/images/avatars/dosen.jpg',
    '/images/avatars/hrd.jpg',
    '/images/avatars/hrd_animated.svg',
    '/images/avatars/investor.jpg',
];

// Install Event - Pre-cache essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// Activate Event - Clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event - Network first for web routes, Cache first for static assets
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET or cross-origin requests
    if (event.request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Static assets & images: cache first with network fallback
    if (url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.jpeg') ||
        url.pathname.endsWith('.webp') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.woff2')) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                if (cached) return cached;
                return fetch(event.request).then((response) => {
                    if (response && response.status === 200) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Dynamic pages & APIs: Network first with offline fallback
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
