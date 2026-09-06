const CACHE_NAME = 'classroom-pwa-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Network-first strategy for active development
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});