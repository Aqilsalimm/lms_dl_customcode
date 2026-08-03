const CACHE_NAME = 'drastha-lms-v6';
const PRECACHE_ASSETS = [
    '/favicon.ico',
    '/images/logo/logo_dl.png',
    '/offline.html',
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

    // Bypass Service Worker caching/interception completely for dynamic, auth, dashboard, courses, and Inertia routes
    if (
        url.pathname.startsWith('/courses') ||
        url.pathname.startsWith('/dashboard') ||
        url.pathname.startsWith('/live-classes') ||
        url.pathname.startsWith('/live-class') ||
        url.pathname.startsWith('/cart') ||
        url.pathname.startsWith('/checkout') ||
        url.pathname.startsWith('/blogs') ||
        url.pathname.startsWith('/login') ||
        url.pathname.startsWith('/register') ||
        url.pathname.startsWith('/forgot-password') ||
        url.pathname.startsWith('/reset-password') ||
        url.pathname.startsWith('/api') ||
        e.request.headers.has('x-inertia') ||
        e.request.headers.get('x-inertia') ||
        e.request.headers.get('X-Inertia') ||
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
                    // Cek response yang valid dan berasal dari origin yang sama
                    if (!networkResponse || !networkResponse.ok || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                        return networkResponse;
                    }
                    
                    // Clone response SEBELUM dimasukkan ke cache (karena cache.put mengonsumsi body stream)
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(e.request, responseToCache);
                    });
                    
                    return networkResponse;
                }).catch(() => {});

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // Dynamic HTML/Navigation requests: always fetch from network, DO NOT cache HTML in Service Worker
    e.respondWith(
        fetch(e.request).catch(() => {
            return caches.match(e.request).then(response => {
                return response || caches.match('/offline.html');
            });
        })
    );
});

// Web Push Notification Event Listener
self.addEventListener('push', e => {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    let data = {};
    if (e.data) {
        try {
            data = e.data.json();
        } catch (err) {
            data = { title: 'Drastha Learning', body: e.data.text() };
        }
    }

    const title = data.title || '⏰ Pengingat Sesi Live Class';
    const options = {
        body: data.body || 'Sesi Live Class Anda akan segera dimulai.',
        icon: data.icon || '/images/logo/logo_dl.png',
        badge: '/images/logo/logo_dl.png',
        data: data.data || { url: '/' },
        vibrate: [100, 50, 100],
        actions: data.actions || [
            { action: 'open', title: 'Masuk Kelas' }
        ]
    };

    e.waitUntil(self.registration.showNotification(title, options));
});

// Notification Click Event Handler with Smart Window Management
self.addEventListener('notificationclick', function(event) {
    // 1. Segera tutup pop-up notifikasi agar tidak menggantung di layar OS
    event.notification.close();

    // 2. Ambil URL target yang disisipkan di payload (event 'push'), fallback ke /dashboard
    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : '/dashboard';

    // 3. Logika "Window Management" (Mencegah membuka tab ganda)
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(windowClients) {
            // Cek apakah user sudah membuka tab Drastha Learning
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                
                // Jika tab sudah terbuka di scope LMS kita, fokuskan tab tersebut dan navigasikan ke URL Live Class
                if (client.url.includes(self.registration.scope) && 'focus' in client) {
                    client.navigate(targetUrl); // Arahkan tab lama ke halaman baru
                    return client.focus();      // Bawa tab tersebut ke depan layar
                }
            }

            // Jika tidak ada tab yang terbuka, buka tab/jendela baru (sangat berguna untuk PWA iOS/Android)
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
