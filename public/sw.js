const CACHE_NAME = 'chnubber-shop-v8';
const urlsToCache = [
  '/',
  '/build/assets/app.css',
  '/build/assets/app.js',
];

// Install event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
      .catch((err) => {
        console.error('Cache failed:', err);
      })
  );
  self.skipWaiting();
});

// Fetch event - Network first, then cache
self.addEventListener('fetch', (event) => {
  // Only cache GET requests with http/https schemes
  const url = new URL(event.request.url);
  if (event.request.method !== 'GET' || !url.protocol.startsWith('http')) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Only cache successful responses
        if (response && response.status === 200 && response.type === 'basic') {
          // Clone the response
          const responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then((cache) => {
              cache.put(event.request, responseToCache);
            });
        }

        return response;
      })
      .catch(() => {
        return caches.match(event.request)
          .then((response) => {
            if (response) {
              return response;
            }

            // Return a custom offline page if needed
            if (event.request.mode === 'navigate') {
              return caches.match('/');
            }
          });
      })
  );
});

// Activate event - Clean up old caches
self.addEventListener('activate', (event) => {
  const cacheWhitelist = [CACHE_NAME];

  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );

  self.clients.claim();
});

// Background sync for offline actions
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-items') {
    event.waitUntil(syncItems());
  }
});

async function syncItems() {
  // This would sync with the backend when online
  console.log('Syncing items...');
}
