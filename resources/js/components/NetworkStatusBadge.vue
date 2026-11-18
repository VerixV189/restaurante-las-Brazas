<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useOfflinePwa } from '../composables/useOfflinePwa';
import {
  Wifi,
  WifiOff,
  ShieldCheck,
  Download,
  Trash2,
  RefreshCw,
  CheckCircle2,
} from 'lucide-vue-next';

const {
  isBrowserOnline,
  simulatedOffline,
  isOfflineMode,
  activeUserKey,
  activeUserLabel,
  cachedPagesCount,
  lastSecurityEvent,
  canInstallPwa,
  toggleSimulatedOffline,
  installPwa,
  purgeOfflineSessionData,
  refreshCachedPagesCount,
} = useOfflinePwa();

const panelOpen = ref(false);
const containerRef = ref(null);
const clearedNotice = ref(false);

function togglePanel() {
  panelOpen.value = !panelOpen.value;
  if (panelOpen.value) {
    refreshCachedPagesCount();
  }
}

async function handleManualClearCache() {
  await purgeOfflineSessionData('Caché de sesión limpiada manualmente');
  clearedNotice.value = true;
  setTimeout(() => {
    clearedNotice.value = false;
  }, 3000);
}

function handleClickOutside(e) {
  if (panelOpen.value && containerRef.value && !containerRef.value.contains(e.target)) {
    panelOpen.value = false;
  }
}

onMounted(() => {
  refreshCachedPagesCount();
  document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside);
});
</script>

<template>
  <div ref="containerRef" class="relative inline-block">
    <!-- Botón / Letrero Principal de Estado Online / Offline -->
    <button
      type="button"
      @click="togglePanel"
      :class="[
        'inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all duration-200 cursor-pointer shadow-2xs',
        isOfflineMode
          ? 'bg-amber-500/15 text-amber-700 border-amber-500/40 hover:bg-amber-500/25 dark:bg-amber-500/20 dark:text-amber-300 dark:border-amber-500/40'
          : 'bg-emerald-500/10 text-emerald-700 border-emerald-500/30 hover:bg-emerald-500/20 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30'
      ]"
      :title="isOfflineMode ? 'Modo Offline activo (clic para opciones PWA)' : 'Conectado en línea · PWA activa (clic para opciones)'"
    >
      <span
        :class="[
          'w-2 h-2 rounded-full shrink-0',
          isOfflineMode ? 'bg-amber-500 animate-ping' : 'bg-emerald-500'
        ]"
      ></span>
      <WifiOff v-if="isOfflineMode" class="w-3.5 h-3.5 shrink-0" />
      <Wifi v-else class="w-3.5 h-3.5 shrink-0" />
      <span class="whitespace-nowrap">
        {{ isOfflineMode ? 'Modo Offline' : 'En Línea' }}
      </span>
    </button>

    <!-- Panel Desplegable de Control PWA y Seguridad de Sesión -->
    <div
      v-if="panelOpen"
      class="absolute right-0 mt-2 w-80 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-4 z-50 space-y-3 text-left"
    >
      <div class="flex items-start justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-2.5">
        <div>
          <div class="flex items-center gap-1.5 text-xs font-extrabold text-slate-900 dark:text-white">
            <WifiOff v-if="isOfflineMode" class="w-4 h-4 text-amber-500" />
            <Wifi v-else class="w-4 h-4 text-emerald-500" />
            <span>{{ isOfflineMode ? 'Modo Offline Activo' : 'Sistema En Línea (PWA)' }}</span>
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
            {{
              !isBrowserOnline
                ? 'Sin red de internet — usando memoria local.'
                : simulatedOffline
                  ? 'Modo sin conexión activado manualmente.'
                  : 'Sincronizando datos en tiempo real.'
            }}
          </p>
        </div>
        <span
          :class="[
            'px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase',
            isOfflineMode
              ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300'
              : 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'
          ]"
        >
          {{ isOfflineMode ? 'Offline' : 'Online' }}
        </span>
      </div>

      <!-- Información de Aislamiento por Sesión / Rol -->
      <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200/80 dark:border-slate-800 space-y-1.5 text-[11px]">
        <div class="flex items-center justify-between">
          <span class="text-slate-500 dark:text-slate-400 font-semibold">Sesión actual:</span>
          <strong class="text-slate-800 dark:text-slate-200 truncate max-w-[165px]">{{ activeUserLabel }}</strong>
        </div>
        <div v-if="activeUserKey !== 'guest'" class="flex items-center justify-between">
          <span class="text-slate-500 dark:text-slate-400 font-semibold">Vistas en caché de sesión:</span>
          <strong class="text-orange-600 dark:text-orange-400">{{ cachedPagesCount }} guardadas</strong>
        </div>
        <div class="pt-1 border-t border-slate-200/70 dark:border-slate-800/80 flex items-start gap-1.5 text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
          <ShieldCheck class="w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5" />
          <span>
            Al cerrar sesión o cambiar de usuario, toda la caché privada se borra automáticamente por seguridad.
          </span>
        </div>
      </div>

      <div
        v-if="clearedNotice || lastSecurityEvent"
        class="px-3 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-[11px] text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5"
      >
        <CheckCircle2 class="w-3.5 h-3.5 shrink-0" />
        <span>{{ clearedNotice ? 'Caché de sesión eliminada.' : lastSecurityEvent }}</span>
      </div>

      <!-- Botones de Acción -->
      <div class="space-y-2 pt-1">
        <button
          type="button"
          @click="toggleSimulatedOffline"
          :class="[
            'w-full py-2 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-colors cursor-pointer',
            simulatedOffline
              ? 'bg-emerald-600 hover:bg-emerald-500 text-white'
              : 'bg-amber-500/15 hover:bg-amber-500/25 text-amber-800 dark:text-amber-300 border border-amber-500/30'
          ]"
        >
          <RefreshCw class="w-3.5 h-3.5" />
          <span>{{ simulatedOffline ? 'Volver a Modo Online' : 'Cambiar a Modo Offline' }}</span>
        </button>

        <button
          v-if="activeUserKey !== 'guest'"
          type="button"
          @click="handleManualClearCache"
          class="w-full py-2 px-3 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-rose-500/15 text-slate-700 hover:text-rose-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-700 flex items-center justify-center gap-2 transition-colors cursor-pointer"
        >
          <Trash2 class="w-3.5 h-3.5" />
          <span>Borrar Datos Offline de esta Sesión</span>
        </button>

        <button
          v-if="canInstallPwa"
          type="button"
          @click="installPwa"
          class="w-full py-2 px-3 rounded-xl text-xs font-bold bg-gradient-to-r from-orange-600 to-amber-500 text-white flex items-center justify-center gap-2 shadow-sm hover:opacity-95 cursor-pointer"
        >
          <Download class="w-3.5 h-3.5" />
          <span>Instalar App en este Dispositivo</span>
        </button>
      </div>
    </div>
  </div>
</template>
