<script setup>
import { ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '../../layouts/AdminLayout.vue';
import { useToast } from 'primevue/usetoast';
import {
  Truck,
  MapPin,
  Phone,
  CheckCircle2,
  Clock,
  Navigation,
  Package,
  ShieldCheck,
  RefreshCw,
  PenTool,
  X,
} from 'lucide-vue-next';

const props = defineProps({
  pendingOrders: { type: Array, default: () => [] },
  myOrders: { type: Array, default: () => [] },
  completedOrders: { type: Array, default: () => [] },
  driverProfile: { type: Object, default: () => ({}) },
});

const toast = useToast();
const activeTab = ref(props.myOrders.length > 0 ? 'mine' : 'pending');
const isRefreshing = ref(false);

// Modal de Confirmación con PIN de 4 dígitos y Firma Digital (RF-19)
const confirmingOrder = ref(null);
const pinInput = ref('');
const submittingConfirm = ref(false);
const signatureCanvas = ref(null);
let isDrawing = false;

function refreshDeliveries() {
  isRefreshing.value = true;
  router.reload({
    onFinish: () => {
      isRefreshing.value = false;
    },
  });
}

async function takeDelivery(order) {
  try {
    const res = await axios.post(`/delivery/${order.id}/take`);
    toast.add({
      severity: 'success',
      summary: 'Envío Asignado',
      detail: res.data.message,
      life: 3000,
    });
    activeTab.value = 'mine';
    refreshDeliveries();
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'No se pudo asignar el pedido.',
      life: 3500,
    });
  }
}

async function markPickedUp(order) {
  try {
    const res = await axios.post(`/delivery/${order.id}/pickup`);
    toast.add({
      severity: 'info',
      summary: 'En Camino al Domicilio',
      detail: res.data.message,
      life: 3000,
    });
    refreshDeliveries();
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'No se pudo actualizar el estado.',
      life: 3500,
    });
  }
}

function openConfirmModal(order) {
  confirmingOrder.value = order;
  pinInput.value = '';
  nextTick(() => {
    clearSignature();
  });
}

function startDrawing(e) {
  const canvas = signatureCanvas.value;
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const rect = canvas.getBoundingClientRect();
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  isDrawing = true;
  ctx.beginPath();
  ctx.moveTo(clientX - rect.left, clientY - rect.top);
}

function draw(e) {
  if (!isDrawing) return;
  const canvas = signatureCanvas.value;
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const rect = canvas.getBoundingClientRect();
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  ctx.lineWidth = 2.5;
  ctx.lineCap = 'round';
  ctx.strokeStyle = '#ea580c';
  ctx.lineTo(clientX - rect.left, clientY - rect.top);
  ctx.stroke();
}

function stopDrawing() {
  isDrawing = false;
}

function clearSignature() {
  const canvas = signatureCanvas.value;
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
}

async function submitConfirmDelivery() {
  if (!confirmingOrder.value) return;
  if (pinInput.value.trim().length !== 4) {
    toast.add({
      severity: 'warn',
      summary: 'PIN incompleto',
      detail: 'Ingresa los 4 dígitos del PIN del cliente.',
      life: 3000,
    });
    return;
  }

  submittingConfirm.value = true;
  try {
    const res = await axios.post(`/delivery/${confirmingOrder.value.id}/confirm`, {
      pin: pinInput.value.trim(),
    });
    toast.add({
      severity: 'success',
      summary: '¡Entrega Completada!',
      detail: res.data.message,
      life: 4000,
    });
    confirmingOrder.value = null;
    refreshDeliveries();
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'PIN Incorrecto',
      detail: err.response?.data?.message || 'Verifica el código PIN de 4 dígitos con el cliente.',
      life: 4000,
    });
  } finally {
    submittingConfirm.value = false;
  }
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between gap-4">
        <span>Central Logística de Reparto & Delivery GPS</span>
      </div>
    </template>

    <div class="space-y-6">
      <!-- KPIs Logísticos del Repartidor -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase text-slate-500">Disponibles para Tomar</p>
            <h3 class="text-2xl font-extrabold text-orange-600 dark:text-orange-400 mt-1">
              {{ pendingOrders.length }} envíos
            </h3>
          </div>
          <div class="p-3 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400">
            <Package class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase text-slate-500">Mis Rutas en Curso</p>
            <h3 class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">
              {{ myOrders.length }} activos
            </h3>
          </div>
          <div class="p-3 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <Truck class="w-6 h-6" />
          </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase text-slate-500">Entregas Completadas</p>
            <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
              {{ completedOrders.length }} entregados
            </h3>
          </div>
          <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <CheckCircle2 class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- Pestañas de Operación -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="activeTab = 'pending'"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold border transition-all cursor-pointer',
              activeTab === 'pending'
                ? 'bg-orange-600 text-white border-orange-600 shadow-sm'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800'
            ]"
          >
            📦 Disponibles ({{ pendingOrders.length }})
          </button>
          <button
            type="button"
            @click="activeTab = 'mine'"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold border transition-all cursor-pointer',
              activeTab === 'mine'
                ? 'bg-orange-600 text-white border-orange-600 shadow-sm'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800'
            ]"
          >
            🛵 Mis Entregas en Curso ({{ myOrders.length }})
          </button>
          <button
            type="button"
            @click="activeTab = 'completed'"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold border transition-all cursor-pointer',
              activeTab === 'completed'
                ? 'bg-orange-600 text-white border-orange-600 shadow-sm'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800'
            ]"
          >
            ✅ Historial Entregados ({{ completedOrders.length }})
          </button>
        </div>

        <button
          type="button"
          @click="refreshDeliveries"
          class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center gap-1.5 cursor-pointer"
        >
          <RefreshCw :class="['w-3.5 h-3.5', isRefreshing ? 'animate-spin' : '']" /> Actualizar
        </button>
      </div>

      <!-- TAB 1: PEDIDOS DISPONIBLES PARA TOMAR -->
      <div v-if="activeTab === 'pending'" class="space-y-4">
        <div
          v-if="pendingOrders.length === 0"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center space-y-2"
        >
          <Package class="w-12 h-12 text-slate-300 dark:text-slate-700 mx-auto" />
          <p class="font-bold text-slate-700 dark:text-slate-300">No hay pedidos pendientes de repartidor</p>
          <p class="text-xs text-slate-500">Los nuevos pedidos a domicilio aparecerán aquí en tiempo real.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="o in pendingOrders"
            :key="o.id"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between gap-4"
          >
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="font-extrabold text-base text-slate-900 dark:text-white">
                  Pedido #{{ o.id }}
                </span>
                <span
                  :class="[
                    'px-2.5 py-0.5 rounded-full text-xs font-bold',
                    o.status === 'ready'
                      ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                      : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'
                  ]"
                >
                  {{ o.status === 'ready' ? '✅ Listo en Cocina para Despacho' : '🔥 Preparándose en Cocina' }}
                </span>
              </div>

              <div class="text-xs space-y-1 text-slate-600 dark:text-slate-300">
                <p class="flex items-center gap-1.5">
                  <strong>Cliente:</strong> {{ o.customer_name }} ({{ o.customer_phone }})
                </p>
                <p class="flex items-start gap-1.5">
                  <MapPin class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" />
                  <span><strong>Destino:</strong> {{ o.address }}</span>
                </p>
              </div>

              <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 text-xs space-y-1">
                <div v-for="(it, idx) in o.items" :key="idx" class="flex justify-between">
                  <span><strong>{{ it.quantity }}x</strong> {{ it.name }}</span>
                </div>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
              <span class="font-extrabold text-base text-orange-600 dark:text-orange-400">
                Total: ${{ Number(o.total).toFixed(2) }}
              </span>
              <button
                type="button"
                @click="takeDelivery(o)"
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 text-white font-extrabold text-xs shadow cursor-pointer"
              >
                🛵 Tomar este Envío
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: MIS ENTREGAS EN CURSO -->
      <div v-else-if="activeTab === 'mine'" class="space-y-4">
        <div
          v-if="myOrders.length === 0"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center space-y-2"
        >
          <Truck class="w-12 h-12 text-slate-300 dark:text-slate-700 mx-auto" />
          <p class="font-bold text-slate-700 dark:text-slate-300">No tienes entregas activas en este momento</p>
          <p class="text-xs text-slate-500">Toma un pedido desde la pestaña "Disponibles" para iniciar tu ruta.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="o in myOrders"
            :key="o.id"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between gap-4"
          >
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="font-extrabold text-base text-slate-900 dark:text-white">
                  Pedido #{{ o.id }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-500/15 text-blue-600 dark:text-blue-400">
                  {{ o.dispatched_at ? `🛵 En Camino (${o.dispatched_at})` : '⏳ Asignado (Recoger en Cocina)' }}
                </span>
              </div>

              <div class="text-xs space-y-1.5 text-slate-600 dark:text-slate-300">
                <p><strong>Cliente:</strong> {{ o.customer_name }} • 📞 {{ o.customer_phone }}</p>
                <p class="flex items-start gap-1.5">
                  <MapPin class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" />
                  <span><strong>Dirección:</strong> {{ o.address }}</span>
                </p>
                <p v-if="o.driver_name && driverProfile.role === 'Administrador'" class="text-orange-500 font-bold">
                  Repartidor asignado: {{ o.driver_name }}
                </p>
                <p v-if="o.delivery_pin" class="text-purple-600 dark:text-purple-400 font-bold">
                  PIN registrado (Vista Admin): {{ o.delivery_pin }}
                </p>
              </div>

              <!-- Botones GPS Google Maps y Waze (RF-18) -->
              <div class="grid grid-cols-2 gap-2 pt-1">
                <a
                  :href="o.google_maps_url"
                  target="_blank"
                  class="py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-blue-500/15 hover:text-blue-600 text-xs font-bold flex items-center justify-center gap-1.5 border border-slate-200 dark:border-slate-700"
                >
                  <MapPin class="w-3.5 h-3.5 text-blue-500" /> Google Maps
                </a>
                <a
                  :href="o.waze_url"
                  target="_blank"
                  class="py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-cyan-500/15 hover:text-cyan-600 text-xs font-bold flex items-center justify-center gap-1.5 border border-slate-200 dark:border-slate-700"
                >
                  <Navigation class="w-3.5 h-3.5 text-cyan-500" /> Abrir en Waze
                </a>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2">
              <button
                v-if="!o.dispatched_at"
                type="button"
                @click="markPickedUp(o)"
                class="flex-1 py-2.5 px-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-white font-extrabold text-xs cursor-pointer"
              >
                🔥 Marcar Recogido de Cocina (Salir a Ruta)
              </button>
              <button
                type="button"
                @click="openConfirmModal(o)"
                class="flex-1 py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs cursor-pointer flex items-center justify-center gap-1.5"
              >
                <ShieldCheck class="w-4 h-4" /> Confirmar Entrega con PIN
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: HISTORIAL DE ENTREGAS COMPLETADAS -->
      <div v-else class="space-y-3">
        <div
          v-for="o in completedOrders"
          :key="o.id"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4"
        >
          <div class="space-y-0.5 text-xs">
            <p class="font-extrabold text-sm text-slate-900 dark:text-white">
              Pedido #{{ o.id }} • {{ o.customer_name }}
            </p>
            <p class="text-slate-500">📍 {{ o.address }}</p>
            <p class="text-emerald-600 dark:text-emerald-400 font-bold">
              ✓ Entregado el {{ o.delivered_at }}
            </p>
          </div>
          <span class="font-extrabold text-base text-slate-900 dark:text-white">
            ${{ Number(o.total).toFixed(2) }}
          </span>
        </div>
      </div>
    </div>

    <!-- MODAL DE CONFIRMACIÓN DE ENTREGA CON PIN 4 DÍGITOS Y FIRMA DIGITAL (RF-19) -->
    <div
      v-if="confirmingOrder"
      class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="confirmingOrder = null"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-extrabold text-base text-slate-900 dark:text-white">
            Confirmar Entrega • Pedido #{{ confirmingOrder.id }}
          </h3>
          <button type="button" @click="confirmingOrder = null" class="text-slate-400 hover:text-slate-600 cursor-pointer">
            <X class="w-5 h-5" />
          </button>
        </div>

        <p class="text-xs text-slate-500">
          Solicita al cliente (<strong>{{ confirmingOrder.customer_name }}</strong>) el código PIN de seguridad de 4 dígitos que aparece en su pedido:
        </p>

        <div>
          <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">
            1. Código PIN de 4 dígitos:
          </label>
          <input
            v-model="pinInput"
            type="text"
            maxlength="4"
            placeholder="0000"
            class="w-full h-12 text-center text-2xl font-mono font-extrabold tracking-widest rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 focus:outline-none focus:border-orange-500"
          />
        </div>

        <div class="space-y-1.5">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1">
              <PenTool class="w-3.5 h-3.5 text-orange-500" /> 2. Firma digital de conformidad:
            </span>
            <button type="button" @click="clearSignature" class="text-orange-600 hover:underline font-bold cursor-pointer">
              Limpiar firma
            </button>
          </div>
          <canvas
            ref="signatureCanvas"
            width="380"
            height="120"
            class="w-full h-28 rounded-xl bg-slate-50 dark:bg-slate-950 border border-dashed border-slate-300 dark:border-slate-700 cursor-crosshair touch-none"
            @mousedown="startDrawing"
            @mousemove="draw"
            @mouseup="stopDrawing"
            @mouseleave="stopDrawing"
            @touchstart.prevent="startDrawing"
            @touchmove.prevent="draw"
            @touchend.prevent="stopDrawing"
          ></canvas>
        </div>

        <button
          type="button"
          :disabled="submittingConfirm"
          @click="submitConfirmDelivery"
          class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs shadow-lg cursor-pointer"
        >
          {{ submittingConfirm ? 'Verificando PIN...' : '✅ Verificar PIN y Finalizar Entrega' }}
        </button>
      </div>
    </div>
  </AdminLayout>
</template>

