const CACHE_NAME = 'apotekcare-shell-v2';

const SHELL_ASSETS = [
  '/manifest.json',
  '/offline.html',
  '/assets/css/tokens.css',
  '/assets/css/app.css',
  '/assets/js/app.js',
  '/assets/icons/sprite.svg',
  '/assets/icons/icon-192.svg',
  '/assets/icons/icon-512.svg',
  '/assets/vendor/bootstrap/css/bootstrap.min.css',
  '/assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
];

// Third-party vendor bundles are pinned by filename and never change without
// a filename change, so cache-first is safe. Everything else under /assets/
// (our own app.js/pos.js/*.css) is code we actively iterate on — those use
// network-first so a deploy is never masked by a stale service worker cache.
function isVendorAsset(pathname) {
  return pathname.startsWith('/assets/vendor/') || pathname.startsWith('/assets/icons/');
}

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function (cache) { return cache.addAll(SHELL_ASSETS); })
      .then(function () { return self.skipWaiting(); })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.filter(function (key) { return key !== CACHE_NAME; }).map(function (key) { return caches.delete(key); }));
    }).then(function () { return self.clients.claim(); })
  );
});

self.addEventListener('fetch', function (event) {
  const request = event.request;

  if (request.method !== 'GET') {
    return; // never intercept POST/PUT/DELETE — those need a live server (§32)
  }

  const url = new URL(request.url);

  // API calls and dynamic app pages: network-only, no stale data served silently.
  if (url.pathname.startsWith('/api/')) {
    return;
  }

  // Navigation requests (HTML pages): network-first, offline fallback shell.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(function () {
        return caches.match('/offline.html');
      })
    );
    return;
  }

  if (url.pathname.startsWith('/assets/')) {
    if (isVendorAsset(url.pathname)) {
      // Cache-first — vendor bundles are effectively immutable.
      event.respondWith(
        caches.match(request).then(function (cached) {
          return cached || fetch(request).then(function (response) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(function (cache) { cache.put(request, clone); });
            return response;
          });
        })
      );
    } else {
      // Network-first — our own JS/CSS, so an update is picked up immediately
      // whenever online; only fall back to the cache when offline.
      event.respondWith(
        fetch(request).then(function (response) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(function (cache) { cache.put(request, clone); });
          return response;
        }).catch(function () { return caches.match(request); })
      );
    }
  }
});
