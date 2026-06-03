// Loewix Service Worker v1.0
const CACHE_NAME = 'loewix-cache-v1';
const OFFLINE_URL = '/staff/offline.html';

// Assets to cache on install (app shell)
const PRECACHE_ASSETS = [
  '/staff/',
  '/staff/index.php',
  '/staff/assets/img/logo/lwx.png',
  '/staff/assets/img/logo/lwx-logo.png',
  '/staff/assets/css/material-dashboard.css?v=3.1.0',
  '/staff/assets/css/nucleo-icons.css',
  '/staff/assets/css/nucleo-svg.css',
  '/staff/assets/js/core/popper.min.js',
  '/staff/assets/js/core/bootstrap.min.js',
  '/staff/assets/js/material-dashboard.min.js?v=3.1.0',
  OFFLINE_URL,
];

// Install — precache app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Pre-caching app shell');
      return cache.addAll(PRECACHE_ASSETS);
    })
  );
  self.skipWaiting();
});

// Activate — clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    })
  );
  self.clients.claim();
});

// Fetch — Network First for navigations, Cache First for assets
self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Skip non-GET requests
  if (request.method !== 'GET') return;

  // Skip Chrome extensions and external URLs
  if (!request.url.startsWith(self.location.origin)) return;

  // Navigation requests — Network First
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Cache a copy of the page
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          return response;
        })
        .catch(() => {
          // Try cache, then offline page
          return caches.match(request).then((cached) => {
            return cached || caches.match(OFFLINE_URL);
          });
        })
    );
    return;
  }

  // Static assets — Cache First, then Network
  if (request.url.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/)) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) return cached;

        return fetch(request).then((response) => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          return response;
        });
      })
    );
    return;
  }

  // Everything else — Network First
  event.respondWith(
    fetch(request)
      .then((response) => {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
        return response;
      })
      .catch(() => caches.match(request))
  );
});
