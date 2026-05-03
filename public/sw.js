// Service Worker — caches build assets and static files for instant reloads
const CACHE = 'medinova-v1';

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE).then((cache) =>
      cache.addAll([
        '/',
        '/build/assets/vendor-CoKr06XY.js',
        '/build/assets/app-CKzE661t.js',
        '/build/assets/app-CYAU6aVj.css',
        '/build/assets/vendor-DvB2Xm2x.css',
      ])
    )
  );
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Cache-first for static assets, network-first for HTML
self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);

  // Cache-first for build assets and static files
  if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/assets/') || url.pathname.startsWith('/fonts/') || url.pathname.startsWith('/images/')) {
    e.respondWith(
      caches.match(e.request).then((cached) => cached || fetch(e.request).then((res) => {
        if (res.ok) {
          const clone = res.clone();
          caches.open(CACHE).then((cache) => cache.put(e.request, clone));
        }
        return res;
      }))
    );
    return;
  }

  // Network-first for everything else
  e.respondWith(
    fetch(e.request)
      .then((res) => {
        if (res.ok && e.request.method === 'GET') {
          const clone = res.clone();
          caches.open(CACHE).then((cache) => cache.put(e.request, clone));
        }
        return res;
      })
      .catch(() => caches.match(e.request))
  );
});
