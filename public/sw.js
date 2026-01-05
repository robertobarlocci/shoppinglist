const CACHE_NAME = 'chnubber-shop-v9';

// Static assets that are safe to cache
// Note: We explicitly DO NOT cache '/' or any HTML pages to avoid CSRF token issues
const STATIC_ASSETS = [
  '/offline.html',
  '/manifest.json',
];

// Patterns for assets that can be cached (CSS, JS, images, fonts)
const CACHEABLE_ASSET_PATTERNS = [
  /\/build\/assets\/.+\.(css|js)$/,
  /\/icons\/.+\.(svg|png|ico)$/,
  /\.(woff2?|ttf|eot)$/,
];

// Routes that should NEVER be cached (auth-related, API, etc.)
const NEVER_CACHE_PATTERNS = [
  /^\/login/,
  /^\/logout/,
  /^\/api\//,
  /^\/sanctum\//,
  /^\/_debugbar/,
];

/**
 * Check if a URL matches any pattern in a list
 */
function matchesPattern(url, patterns) {
  const pathname = new URL(url).pathname;
  return patterns.some(pattern => pattern.test(pathname));
}

/**
 * Check if a request is for a static asset that can be cached
 */
function isCacheableAsset(url) {
  return matchesPattern(url, CACHEABLE_ASSET_PATTERNS);
}

/**
 * Check if a request should never be cached
 */
function shouldNeverCache(url) {
  return matchesPattern(url, NEVER_CACHE_PATTERNS);
}

// Install event - Pre-cache only offline page and manifest
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW] Pre-caching offline page');
        return cache.addAll(STATIC_ASSETS);
      })
      .catch((err) => {
        console.error('[SW] Pre-cache failed:', err);
      })
  );
  // Activate immediately without waiting for existing clients to close
  self.skipWaiting();
});

// Fetch event - Network first with intelligent caching
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Only handle GET requests with http/https schemes
  if (request.method !== 'GET' || !url.protocol.startsWith('http')) {
    return;
  }
  
  // Never cache auth-related or API routes
  if (shouldNeverCache(request.url)) {
    return;
  }
  
  // For navigation requests (HTML pages), always go to network
  // This ensures fresh CSRF tokens and proper auth state
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .catch(() => {
          // Only show offline page when network completely fails
          return caches.match('/offline.html');
        })
    );
    return;
  }
  
  // For cacheable static assets, use cache-first strategy
  if (isCacheableAsset(request.url)) {
    event.respondWith(
      caches.match(request)
        .then((cachedResponse) => {
          if (cachedResponse) {
            // Return cached response and update cache in background
            fetch(request)
              .then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                  caches.open(CACHE_NAME)
                    .then((cache) => cache.put(request, networkResponse));
                }
              })
              .catch(() => {});
            return cachedResponse;
          }
          
          // Not in cache, fetch from network and cache
          return fetch(request)
            .then((networkResponse) => {
              if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                const responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME)
                  .then((cache) => cache.put(request, responseToCache));
              }
              return networkResponse;
            });
        })
    );
    return;
  }
  
  // For all other requests, network only (no caching)
  // This includes API responses, dynamic content, etc.
});

// Activate event - Clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((cacheName) => cacheName !== CACHE_NAME)
            .map((cacheName) => {
              console.log('[SW] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            })
        );
      })
      .then(() => {
        console.log('[SW] Activated, claiming clients');
        return self.clients.claim();
      })
  );
});

// Background sync for offline actions
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-items') {
    event.waitUntil(syncItems());
  }
});

async function syncItems() {
  console.log('[SW] Syncing items...');
  // This would sync with the backend when online
}

// Handle messages from the main app
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  // Allow the app to clear caches when needed (e.g., after logout)
  if (event.data && event.data.type === 'CLEAR_CACHES') {
    event.waitUntil(
      caches.keys()
        .then((cacheNames) => {
          return Promise.all(
            cacheNames.map((cacheName) => caches.delete(cacheName))
          );
        })
        .then(() => {
          console.log('[SW] All caches cleared');
        })
    );
  }
});
