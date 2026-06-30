const CACHE_NAME = 'drastha-lms-v3';
const PRECACHE_ASSETS = [
    '/',
    '/favicon.ico',
    '/images/logo/logo_dl.png',
    '/images/pages/welcome/welcome_beranda.gif',
];

self.addEventListener('install', e => {
    self.skipWaiting();
    e.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_ASSETS))
    );
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.map(key => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', e => {
    const url = new URL(e.request.url);

    // Skip non-GET requests and chrome extension requests
    if (e.request.method !== 'GET' || !e.request.url.startsWith(self.location.origin)) {
        return;
    }

    // Bypass Service Worker caching/interception completely for dynamic, auth, dashboard, and Inertia routes
    if (
        url.pathname.startsWith('/dashboard') ||
        url.pathname.startsWith('/login') ||
        url.pathname.startsWith('/register') ||
        url.pathname.startsWith('/forgot-password') ||
        url.pathname.startsWith('/reset-password') ||
        url.pathname.startsWith('/api') ||
        e.request.headers.has('x-inertia') ||
        (e.request.headers.get('accept') && e.request.headers.get('accept').includes('json'))
    ) {
        return; // Let the browser handle it directly via normal network requests
    }

    // Static assets: Stale-While-Revalidate (Cache First, fetch & update in background)
    if (
        url.pathname.includes('/build/assets/') ||
        url.pathname.includes('/images/') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.jpeg') ||
        url.pathname.endsWith('.gif') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.woff2')
    ) {
        e.respondWith(
            caches.match(e.request).then(cachedResponse => {
                const fetchPromise = fetch(e.request).then(networkResponse => {
                    if (networkResponse.status === 200) {
                        caches.open(CACHE_NAME).then(cache => cache.put(e.request, networkResponse));
                    }
                    return networkResponse.clone();
                }).catch(() => {});

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // Dynamic HTML/Navigation requests: always fetch from network, DO NOT cache in Service Worker
    e.respondWith(
        fetch(e.request).catch(() => {
            return caches.match(e.request).then(cachedResponse => {
                return cachedResponse || caches.match('/');
            });
        })
    );
});
