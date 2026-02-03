const CACHE_NAME = 'e-clean-v3';
const urlsToCache = [
  '/pwa/icon-192x192.png',
  '/pwa/icon-512x512.png',
  '/manifest.json',
];

// Install Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Fetch assets
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Skip Service Worker entirely for:
  // 1. Non-GET requests (POST, PUT, DELETE, etc.)
  // 2. Login/logout/auth routes (including OAuth callbacks)
  // 3. Livewire requests
  // 4. API requests
  // Using return (without event.respondWith) lets the browser handle these
  // requests normally, avoiding Private Network Access (PNA) blocking.
  if (
    event.request.method !== 'GET' ||
    url.pathname.includes('/login') ||
    url.pathname.includes('/logout') ||
    url.pathname.includes('/auth/') ||
    url.pathname.includes('/livewire') ||
    url.pathname.includes('/api/')
  ) {
    return;
  }

  // For other GET requests, use cache-first strategy
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Cache hit - return response
        if (response) {
          return response;
        }
        return fetch(event.request).then(
          response => {
            // Check if we received a valid response
            if(!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Only cache successful responses for static assets
            if (
              url.pathname.includes('/css/') ||
              url.pathname.includes('/js/') ||
              url.pathname.includes('/fonts/') ||
              url.pathname.includes('/pwa/') ||
              url.pathname.includes('/manifest.json')
            ) {
              const responseToCache = response.clone();
              caches.open(CACHE_NAME).then(cache => {
                cache.put(event.request, responseToCache);
              });
            }

            return response;
          }
        );
      })
    );
});

// Activate and clean old caches
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
