/* ============================================================================
 * Service Worker Oficial — Restaurante Las Brazas (PWA & Modo Offline Seguro)
 * Separa estrictamente la caché pública (estáticos) de la caché de sesión
 * autenticada, purgando los datos privados al cerrar sesión o cambiar de rol.
 * ============================================================================ */

const STATIC_CACHE = 'lasbrazas-static-v1';
const SESSION_CACHE = 'lasbrazas-session-v1';

const PRECACHE_URLS = [
  '/',
  '/offline.html',
  '/manifest.webmanifest',
  '/images/logo-dark.jpg',
  '/images/logo-light.jpg',
];

const PRIVATE_PATH_PREFIXES = [
  '/dashboard',
  '/pos',
  '/kitchen',
  '/delivery',
  '/reservations',
  '/reports',
  '/users',
  '/categories',
  '/dishes',
  '/recipes',
  '/ingredients',
  '/inventory-movements',
  '/predictions',
  '/areas',
  '/tables',
  '/floor-plans',
  '/customers',
  '/suppliers',
  '/activity-logs',
  '/client',
];

let currentUserKey = 'guest';
let simulateOffline = false;

function isPrivatePath(pathname) {
  return PRIVATE_PATH_PREFIXES.some(
    (prefix) => pathname === prefix || pathname.startsWith(prefix + '/')
  );
}

function isStaticAsset(pathname) {
  return (
    pathname.startsWith('/build/') ||
    pathname.startsWith('/images/') ||
    pathname.startsWith('/manual') ||
    pathname === '/pdf' ||
    pathname === '/descargar-pdf' ||
    pathname === '/manifest.webmanifest' ||
    pathname === '/offline.html' ||
    /\.(?:js|css|woff2?|ttf|eot|svg|png|jpe?g|webp|ico|pdf)$/i.test(pathname)
  );
}

async function purgeSessionCache() {
  await caches.delete(SESSION_CACHE);
}

async function broadcastClients(payload) {
  const clientsList = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
  for (const client of clientsList) {
    client.postMessage(payload);
  }
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches
      .open(STATIC_CACHE)
      .then((cache) => cache.addAll(PRECACHE_URLS).catch(() => {}))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys.map((key) => {
            if (key !== STATIC_CACHE && key !== SESSION_CACHE) {
              return caches.delete(key);
            }
            return Promise.resolve();
          })
        )
      )
      .then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  const data = event.data || {};

  if (data.type === 'SET_USER_CONTEXT') {
    const nextUserKey = data.userKey || 'guest';
    if (nextUserKey !== currentUserKey) {
      const previousKey = currentUserKey;
      currentUserKey = nextUserKey;
      // Si cerró sesión o cambió de usuario/rol, purgar toda caché privada inmediatamente
      if (previousKey !== 'guest' || nextUserKey === 'guest') {
        event.waitUntil(
          purgeSessionCache().then(() =>
            broadcastClients({
              type: 'SESSION_CACHE_PURGED',
              userKey: currentUserKey,
            })
          )
        );
      }
    }
  } else if (data.type === 'CLEAR_AUTH_CACHE') {
    currentUserKey = 'guest';
    simulateOffline = false;
    event.waitUntil(
      purgeSessionCache().then(() =>
        broadcastClients({
          type: 'SESSION_CACHE_PURGED',
          userKey: 'guest',
        })
      )
    );
  } else if (data.type === 'SET_SIMULATE_OFFLINE') {
    simulateOffline = Boolean(data.value);
    event.waitUntil(
      broadcastClients({
        type: 'SIMULATE_OFFLINE_CHANGED',
        simulateOffline,
      })
    );
  }
});

function buildCacheKeyRequest(request) {
  const isInertia = request.headers.get('X-Inertia') === 'true';
  const url = new URL(request.url);
  if (isInertia) {
    url.searchParams.set('__inertia_json', '1');
  }
  return new Request(url.toString(), { method: 'GET' });
}

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar peticiones de otros orígenes
  if (url.origin !== self.location.origin) {
    return;
  }

  // Si se ejecuta cierre de sesión por POST /logout, borrar caché de sesión de inmediato
  if (url.pathname === '/logout') {
    currentUserKey = 'guest';
    simulateOffline = false;
    event.waitUntil(purgeSessionCache());
    return;
  }

  // Solo interceptamos peticiones GET para caché offline
  if (request.method !== 'GET') {
    return;
  }

  const pathname = url.pathname;

  // 1. Activos estáticos (JS, CSS, fuentes, imágenes, manual PDF): Cache-First con actualización en red
  if (isStaticAsset(pathname)) {
    event.respondWith(
      (async () => {
        const staticCache = await caches.open(STATIC_CACHE);
        const cached = await staticCache.match(request);
        if (simulateOffline && cached) {
          return cached;
        }
        try {
          if (simulateOffline) {
            throw new Error('Simulated offline mode');
          }
          const networkResponse = await fetch(request);
          if (networkResponse && networkResponse.ok) {
            staticCache.put(request, networkResponse.clone());
          }
          return networkResponse;
        } catch (_) {
          if (cached) {
            return cached;
          }
          return new Response('Recurso no disponible sin conexión.', { status: 503 });
        }
      })()
    );
    return;
  }

  // 2. Navegación HTML o peticiones de página Inertia (públicas y privadas por sesión)
  const isPrivate = isPrivatePath(pathname);
  const cacheKeyReq = buildCacheKeyRequest(request);

  event.respondWith(
    (async () => {
      // Si es ruta privada y el usuario actual es invitado, prohibir lectura de caché privada
      if (isPrivate && currentUserKey === 'guest' && simulateOffline) {
        await purgeSessionCache();
        const fallback = await caches.match('/offline.html');
        return fallback || Response.redirect('/login', 302);
      }

      const targetCacheName = isPrivate ? SESSION_CACHE : STATIC_CACHE;
      const cache = await caches.open(targetCacheName);

      // Si el usuario activó la simulación Offline manual, servir directamente de la caché
      if (simulateOffline) {
        const cachedResp = await cache.match(cacheKeyReq);
        if (cachedResp) {
          return cachedResp;
        }
        const fallback = await caches.match('/offline.html');
        return fallback || new Response('Modo Offline: página aún no guardada en caché.', { status: 503 });
      }

      // Modo Online normal: Network-First y guardar copia actualizada en la caché correspondiente
      try {
        const networkResponse = await fetch(request);

        // Si el servidor redirigió a /login o devolvió 401/403, no guardar en caché privada
        const redirectedToLogin =
          networkResponse.redirected && new URL(networkResponse.url).pathname === '/login';

        if (networkResponse && networkResponse.ok && !redirectedToLogin) {
          // Solo cachear rutas privadas si existe un usuario autenticado válido
          if (!isPrivate || currentUserKey !== 'guest') {
            cache.put(cacheKeyReq, networkResponse.clone());
          }
        } else if (redirectedToLogin && isPrivate) {
          // Sesión expirada en el servidor -> limpiar caché de sesión
          currentUserKey = 'guest';
          await purgeSessionCache();
        }

        return networkResponse;
      } catch (_) {
        // Sin conexión de red real: intentar servir desde la caché
        if (isPrivate && currentUserKey === 'guest') {
          await purgeSessionCache();
          const fallback = await caches.match('/offline.html');
          return fallback || new Response('Sesión cerrada. Conéctate a internet para iniciar sesión.', { status: 503 });
        }

        const cachedResp = await cache.match(cacheKeyReq);
        if (cachedResp) {
          return cachedResp;
        }

        const fallback = await caches.match('/offline.html');
        if (fallback) {
          return fallback;
        }
        return new Response('Sin conexión a internet.', { status: 503 });
      }
    })()
  );
});
