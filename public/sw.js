/* SLSU Smart Campus PWA service worker.
 *
 * Authenticated HTML and external map tiles are deliberately never cached.
 * Only public campus datasets and same-origin application assets are saved.
 */
const PWA_CACHE_VERSION = '2026-08-04.1';
const CACHE_PREFIX = 'wayfinding-pwa-';
const STATIC_CACHE = `${CACHE_PREFIX}static-${PWA_CACHE_VERSION}`;
const DATA_CACHE = `${CACHE_PREFIX}data-${PWA_CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/background/slsu-logo.jpg',
    '/icons/pwa-icon-180.png',
    '/icons/pwa-icon-192.png',
    '/icons/pwa-icon-512.png',
    '/css/wayfinding/17-cr-navigation.css',
    '/js/wayfinding/15-cr-navigation.js',
    '/js/wayfinding-route-worker.js',
    '/js/wayfinding-search-worker.js',
];

const CACHEABLE_DATA_PATHS = new Set([
    '/data/campus-snapshot.json',
    '/data/destination-keywords.json',
    '/api/buildings',
    '/api/paths',
    '/api/entry-points',
    '/api/building-entrances',
    '/api/hazard-points',
    '/api/landuses',
    '/api/indoor-maps',
    '/api/indoor-rooms',
    '/api/indoor-paths',
    '/api/indoor-entrances',
    '/api/building-entrance-links',
    '/api/indoor-stairs-links',
    '/api/campus-events',
]);

function isCacheableStaticPath(pathname) {
    return pathname.startsWith('/build/assets/')
        || pathname.startsWith('/background/')
        || pathname.startsWith('/icons/')
        || pathname.startsWith('/css/wayfinding/')
        || pathname.startsWith('/js/wayfinding/');
}

function isCacheableIndoorDataPath(pathname) {
    return /^\/data\/indoor\/\d+\.json$/.test(pathname);
}

function isCacheableResponse(response) {
    return Boolean(response && response.ok && response.type === 'basic');
}

function collectViteAssetUrls(manifest) {
    const urls = new Set();

    Object.values(manifest || {}).forEach(entry => {
        [entry?.file, ...(entry?.css || []), ...(entry?.assets || [])]
            .filter(path => typeof path === 'string' && path.startsWith('assets/'))
            .forEach(path => urls.add(`/build/${path}`));
    });

    return Array.from(urls);
}

async function precacheAppShell() {
    const cache = await caches.open(STATIC_CACHE);
    await cache.addAll(PRECACHE_URLS);

    /*
     * Production Vite filenames are content-hashed. Discover them from the
     * generated manifest so the first service-worker install saves the exact
     * CSS, JavaScript, Leaflet images, and shared chunks for this release.
     * Development can continue safely when the production manifest is absent.
     */
    try {
        const manifestResponse = await fetch('/build/manifest.json', {
            cache: 'no-store',
        });
        if (!manifestResponse.ok) return;

        const manifest = await manifestResponse.clone().json();
        const assetUrls = collectViteAssetUrls(manifest);

        await Promise.all(
            assetUrls.map(url => cache.add(url).catch(() => null))
        );
        await cache.put('/build/manifest.json', manifestResponse);
        await trimCache(STATIC_CACHE, 90);
    } catch (error) {
        /* The Vite development server does not require production precaching. */
    }
}

async function trimCache(cacheName, maximumEntries) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();

    while (keys.length > maximumEntries) {
        await cache.delete(keys.shift());
    }
}

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    const response = await fetch(request);
    if (isCacheableResponse(response)) {
        const cache = await caches.open(STATIC_CACHE);
        await cache.put(request, response.clone());
        await trimCache(STATIC_CACHE, 90);
    }

    return response;
}

async function staleWhileRevalidate(request, event) {
    const cached = await caches.match(request);
    const network = fetch(request)
        .then(async response => {
            if (isCacheableResponse(response)) {
                const cache = await caches.open(STATIC_CACHE);
                await cache.put(request, response.clone());
                await trimCache(STATIC_CACHE, 90);
            }
            return response;
        })
        .catch(() => null);

    if (cached) {
        event.waitUntil(network);
        return cached;
    }

    return (await network) || Response.error();
}

async function networkFirstData(request) {
    try {
        const response = await fetch(request);

        if (isCacheableResponse(response)) {
            const cache = await caches.open(DATA_CACHE);
            await cache.put(request, response.clone());
            await trimCache(DATA_CACHE, 30);
        }

        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) return cached;

        return new Response(
            JSON.stringify({
                message: 'Campus data is unavailable offline and has not been saved on this device yet.',
            }),
            {
                status: 503,
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-store',
                },
            },
        );
    }
}

async function staleWhileRevalidateData(request, event) {
    const cache = await caches.open(DATA_CACHE);
    const cached = await cache.match(request);
    const network = fetch(request)
        .then(async response => {
            if (isCacheableResponse(response)) {
                await cache.put(request, response.clone());
                await trimCache(DATA_CACHE, 60);
            }
            return response;
        })
        .catch(() => null);

    if (cached) {
        event.waitUntil(network);
        return cached;
    }

    return (await network) || Response.error();
}

self.addEventListener('install', event => {
    event.waitUntil(precacheAppShell());
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys
                    .filter(key => key.startsWith(CACHE_PREFIX))
                    .filter(key => ![STATIC_CACHE, DATA_CACHE].includes(key))
                    .map(key => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    if (
        url.pathname === '/data/campus-snapshot.json'
        || url.pathname === '/data/destination-keywords.json'
        || isCacheableIndoorDataPath(url.pathname)
    ) {
        event.respondWith(staleWhileRevalidateData(request, event));
        return;
    }

    if (CACHEABLE_DATA_PATHS.has(url.pathname)) {
        event.respondWith(networkFirstData(request));
        return;
    }

    if (PRECACHE_URLS.includes(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (isCacheableStaticPath(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request, event));
    }
});

self.addEventListener('message', event => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
