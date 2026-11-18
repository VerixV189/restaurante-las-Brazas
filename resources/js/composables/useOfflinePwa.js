import { ref, computed } from 'vue';

const STATIC_CACHE = 'lasbrazas-static-v1';
const SESSION_CACHE = 'lasbrazas-session-v1';
const OWNER_STORAGE_KEY = 'lasbrazas_cache_owner';
const SIM_OFFLINE_KEY = 'lasbrazas_sim_offline';

const isBrowserOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const simulatedOffline = ref(false);
const swRegistered = ref(false);
const activeUserKey = ref('guest');
const activeUserLabel = ref('Público (Sin sesión)');
const cachedPagesCount = ref(0);
const lastSecurityEvent = ref('');
const deferredInstallPrompt = ref(null);
const canInstallPwa = ref(false);

let listenersInitialized = false;

function postToServiceWorker(payload) {
  if (typeof navigator === 'undefined' || !('serviceWorker' in navigator)) return;
  if (navigator.serviceWorker.controller) {
    navigator.serviceWorker.controller.postMessage(payload);
  }
  navigator.serviceWorker.ready
    .then((reg) => {
      if (reg && reg.active) {
        reg.active.postMessage(payload);
      }
    })
    .catch(() => {});
}

export async function refreshCachedPagesCount() {
  if (typeof window === 'undefined' || !('caches' in window)) {
    cachedPagesCount.value = 0;
    return 0;
  }
  try {
    const hasSession = activeUserKey.value !== 'guest';
    if (!hasSession) {
      cachedPagesCount.value = 0;
      return 0;
    }
    const cache = await window.caches.open(SESSION_CACHE);
    const keys = await cache.keys();
    cachedPagesCount.value = keys.length;
    return keys.length;
  } catch (_) {
    cachedPagesCount.value = 0;
    return 0;
  }
}

/**
 * Elimina inmediatamente toda la caché de sesión autenticada y datos locales
 * para impedir que otro usuario (ej. Cliente) vea datos previos de un Administrador.
 */
export async function purgeOfflineSessionData(reason = 'Cierre de sesión') {
  activeUserKey.value = 'guest';
  activeUserLabel.value = 'Público (Sin sesión)';
  simulatedOffline.value = false;
  lastSecurityEvent.value = reason;

  try {
    if (typeof localStorage !== 'undefined') {
      const savedTheme = localStorage.getItem('theme');
      localStorage.removeItem(OWNER_STORAGE_KEY);
      localStorage.removeItem(SIM_OFFLINE_KEY);
      // Limpiar cualquier rastro en sessionStorage
      if (typeof sessionStorage !== 'undefined') {
        sessionStorage.clear();
      }
      if (savedTheme) {
        localStorage.setItem('theme', savedTheme);
      }
    }
  } catch (_) {}

  if (typeof window !== 'undefined' && 'caches' in window) {
    try {
      await window.caches.delete(SESSION_CACHE);
    } catch (_) {}
  }

  postToServiceWorker({ type: 'CLEAR_AUTH_CACHE' });
  cachedPagesCount.value = 0;
}

/**
 * Guarda la vista actual de Inertia en la caché de sesión (o pública) mientras está online
 * para permitir navegar hacia ella si se pierde la conexión.
 */
export async function cacheCurrentInertiaPage(pageObj) {
  if (
    typeof window === 'undefined' ||
    !('caches' in window) ||
    !pageObj ||
    !pageObj.url
  ) {
    return;
  }

  const isOfflineNow = !isBrowserOnline.value || simulatedOffline.value;
  if (isOfflineNow) return;

  try {
    const user = pageObj.props?.auth?.user;
    const isPrivate = Boolean(user);
    const targetCacheName = isPrivate ? SESSION_CACHE : STATIC_CACHE;
    const cache = await window.caches.open(targetCacheName);

    // 1. Guardar respuesta JSON de Inertia para navegación SPA (<Link>)
    const inertiaUrl = new URL(pageObj.url, window.location.origin);
    inertiaUrl.searchParams.set('__inertia_json', '1');

    const payload = JSON.stringify({
      component: pageObj.component,
      props: pageObj.props,
      url: pageObj.url,
      version: pageObj.version || null,
    });

    const jsonResponse = new Response(payload, {
      status: 200,
      headers: {
        'Content-Type': 'application/json',
        'X-Inertia': 'true',
        Vary: 'X-Inertia',
      },
    });

    await cache.put(new Request(inertiaUrl.toString(), { method: 'GET' }), jsonResponse);

    // 2. Guardar también el documento HTML actual para recargas completas (F5)
    const htmlUrl = new URL(pageObj.url, window.location.origin).toString();
    const htmlDoc = '<!DOCTYPE html>\n' + document.documentElement.outerHTML;
    const htmlResponse = new Response(htmlDoc, {
      status: 200,
      headers: {
        'Content-Type': 'text/html; charset=UTF-8',
      },
    });
    await cache.put(new Request(htmlUrl, { method: 'GET' }), htmlResponse);

    await refreshCachedPagesCount();
  } catch (_) {}
}

/**
 * Verifica que el usuario autenticado actual coincida con el dueño de la caché offline.
 * Si cerró sesión o entró con otro rol/usuario, purga toda la caché privada antes de continuar.
 */
export async function syncUserSessionSecurity(pageObj) {
  const user = pageObj?.props?.auth?.user || null;
  const nextKey = user ? `user:${user.id}:${user.role || 'Usuario'}` : 'guest';
  const nextLabel = user ? `${user.name} (${user.role || 'Usuario'})` : 'Público (Sin sesión)';

  let previousStoredOwner = null;
  try {
    previousStoredOwner = localStorage.getItem(OWNER_STORAGE_KEY);
  } catch (_) {}

  // Si antes había un usuario autenticado y ahora es invitado o cambió de cuenta/rol -> purgar
  if (
    (previousStoredOwner && previousStoredOwner !== 'guest' && previousStoredOwner !== nextKey) ||
    (activeUserKey.value !== 'guest' && activeUserKey.value !== nextKey)
  ) {
    await purgeOfflineSessionData('Datos offline de la sesión anterior eliminados por seguridad');
  }

  activeUserKey.value = nextKey;
  activeUserLabel.value = nextLabel;

  try {
    localStorage.setItem(OWNER_STORAGE_KEY, nextKey);
  } catch (_) {}

  postToServiceWorker({
    type: 'SET_USER_CONTEXT',
    userKey: nextKey,
  });

  if (pageObj) {
    await cacheCurrentInertiaPage(pageObj);
  }
}

export function initOfflinePwaSystem(initialPageObj = null) {
  if (typeof window === 'undefined') return;

  if (!listenersInitialized) {
    listenersInitialized = true;

    try {
      simulatedOffline.value = localStorage.getItem(SIM_OFFLINE_KEY) === '1';
    } catch (_) {}

    window.addEventListener('online', () => {
      isBrowserOnline.value = true;
    });

    window.addEventListener('offline', () => {
      isBrowserOnline.value = false;
    });

    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredInstallPrompt.value = e;
      canInstallPwa.value = true;
    });

    window.addEventListener('appinstalled', () => {
      deferredInstallPrompt.value = null;
      canInstallPwa.value = false;
    });

    if ('serviceWorker' in navigator) {
      navigator.serviceWorker
        .register('/sw.js', { scope: '/' })
        .then(() => {
          swRegistered.value = true;
          postToServiceWorker({
            type: 'SET_SIMULATE_OFFLINE',
            value: simulatedOffline.value,
          });
          if (initialPageObj) {
            syncUserSessionSecurity(initialPageObj);
          }
        })
        .catch(() => {});

      navigator.serviceWorker.addEventListener('message', (event) => {
        const data = event.data || {};
        if (data.type === 'SESSION_CACHE_PURGED') {
          cachedPagesCount.value = 0;
        } else if (data.type === 'SIMULATE_OFFLINE_CHANGED') {
          simulatedOffline.value = Boolean(data.simulateOffline);
        }
      });
    }
  }

  if (initialPageObj) {
    syncUserSessionSecurity(initialPageObj);
  }
}

export function useOfflinePwa() {
  const isOfflineMode = computed(() => !isBrowserOnline.value || simulatedOffline.value);

  function toggleSimulatedOffline() {
    simulatedOffline.value = !simulatedOffline.value;
    try {
      if (simulatedOffline.value) {
        localStorage.setItem(SIM_OFFLINE_KEY, '1');
      } else {
        localStorage.removeItem(SIM_OFFLINE_KEY);
      }
    } catch (_) {}

    postToServiceWorker({
      type: 'SET_SIMULATE_OFFLINE',
      value: simulatedOffline.value,
    });
  }

  async function installPwa() {
    if (!deferredInstallPrompt.value) return false;
    deferredInstallPrompt.value.prompt();
    const { outcome } = await deferredInstallPrompt.value.userChoice;
    if (outcome === 'accepted') {
      deferredInstallPrompt.value = null;
      canInstallPwa.value = false;
      return true;
    }
    return false;
  }

  return {
    isBrowserOnline,
    simulatedOffline,
    isOfflineMode,
    swRegistered,
    activeUserKey,
    activeUserLabel,
    cachedPagesCount,
    lastSecurityEvent,
    canInstallPwa,
    toggleSimulatedOffline,
    installPwa,
    purgeOfflineSessionData,
    syncUserSessionSecurity,
    refreshCachedPagesCount,
  };
}
