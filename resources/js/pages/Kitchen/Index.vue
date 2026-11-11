<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import {
  ChefHat,
  Clock,
  CheckCircle2,
  Flame,
  RefreshCw,
  Utensils,
  Check,
  Truck,
  ShoppingBag,
  MapPin,
} from 'lucide-vue-next';

const props = defineProps({
  orders: Array,
  stats: Object,
});

const toast = useToast();
const filterStatus = ref('all');
const autoRefreshEnabled = ref(true);
let refreshInterval = null;

// Elapsed Time tracker that updates every second
const currentTime = ref(Date.now());
let timerInterval = null;

onMounted(() => {
  timerInterval = setInterval(() => {
    currentTime.value = Date.now();
  }, 1000);

  if (autoRefreshEnabled.value) {
    startAutoRefresh();
  }
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
  if (refreshInterval) clearInterval(refreshInterval);
});

function startAutoRefresh() {
  refreshInterval = setInterval(() => {
    router.reload({ preserveScroll: true });
  }, 10000); // Cada 10 segundos
}

function toggleAutoRefresh() {
  autoRefreshEnabled.value = !autoRefreshEnabled.value;
  if (autoRefreshEnabled.value) {
    startAutoRefresh();
    toast.add({ severity: 'info', summary: 'Auto-refresco', detail: 'Actualización en vivo activada (10s)', life: 2000 });
  } else {
    if (refreshInterval) clearInterval(refreshInterval);
    toast.add({ severity: 'secondary', summary: 'Auto-refresco', detail: 'Actualización manual', life: 2000 });
  }
}

function manualRefresh() {
  router.reload({
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'info', summary: 'Actualizado', detail: 'Comandas de cocina sincronizadas', life: 1500 });
    }
  });
}

// Format Elapsed Minutes cleanly without overflowing if an order is old
function getElapsedMinutes(createdAt) {
  const created = new Date(createdAt).getTime();
  const diffMs = Math.max(0, currentTime.value - created);
  const minutes = Math.floor(diffMs / 60000);
  const seconds = Math.floor((diffMs % 60000) / 1000);

  let formatted = '';
  if (minutes < 60) {
    formatted = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
  } else if (minutes < 1440) {
    const hours = Math.floor(minutes / 60);
    const remMin = minutes % 60;
    formatted = `${hours}h ${remMin}m`;
  } else {
    const days = Math.floor(minutes / 1440);
    formatted = `+${days}d`;
  }

  return {
    minutes,
    formatted,
    urgency: minutes >= 20 ? 'danger' : minutes >= 12 ? 'warning' : 'normal',
  };
}

// Filtered Orders
const filteredOrders = computed(() => {
  if (filterStatus.value === 'all') return props.orders;
  if (filterStatus.value === 'delivery') return props.orders.filter(o => o.order_type === 'delivery' || o.order_type === 'takeaway');
  if (filterStatus.value === 'pending') return props.orders.filter(o => o.status === 'pending');
  if (filterStatus.value === 'in_kitchen') return props.orders.filter(o => o.status === 'in_kitchen');
  if (filterStatus.value === 'ready') return props.orders.filter(o => o.status === 'ready');
  return props.orders;
});

// Update single item status
function changeItemStatus(item, nextStatus) {
  router.post(`/kitchen/items/${item.id}/status`, {
    status: nextStatus
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({
        severity: nextStatus === 'ready' ? 'success' : 'info',
        summary: nextStatus === 'ready' ? 'Platillo Listo' : 'En Cocción',
        detail: `${item.dish?.name} marcado como ${nextStatus}`,
        life: 2000
      });
    }
  });
}

// Advance all items in order
function advanceAllOrder(order, targetStatus) {
  router.post(`/kitchen/orders/${order.id}/advance`, {
    target_status: targetStatus
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({
        severity: targetStatus === 'ready' ? 'success' : 'info',
        summary: 'Comanda Actualizada',
        detail: `Comanda #${order.id} marcada como ${targetStatus}`,
        life: 3000
      });
    }
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Monitor de Cocina en Vivo (KDS)</template>

    <div class="space-y-6">
      <!-- Top Station Bar -->
      <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors duration-200">
        <!-- Live Counters -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 dark:bg-slate-800/80 dark:border-slate-700">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">En Espera:</span>
            <strong class="text-amber-600 dark:text-amber-400 font-bold text-sm">{{ stats.pending_orders }}</strong>
          </div>

          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 dark:bg-slate-800/80 dark:border-slate-700">
            <span class="w-2.5 h-2.5 rounded-full bg-orange-500 animate-pulse"></span>
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">En Fuego / Cocción:</span>
            <strong class="text-orange-600 dark:text-orange-400 font-bold text-sm">{{ stats.cooking_orders }}</strong>
          </div>

          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 dark:bg-slate-800/80 dark:border-slate-700">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Listas para Servir:</span>
            <strong class="text-emerald-600 dark:text-emerald-400 font-bold text-sm">{{ stats.ready_orders }}</strong>
          </div>

          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 dark:bg-slate-800/80 dark:border-slate-700">
            <Utensils class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400" />
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Platillos en Fila:</span>
            <strong class="text-blue-600 dark:text-blue-400 font-bold text-sm">{{ stats.total_active_dishes }}</strong>
          </div>
        </div>

        <!-- Controls: Filter & Auto-refresh -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- Filter Buttons -->
          <div class="flex rounded-xl bg-slate-100 dark:bg-slate-800/80 p-1 border border-slate-200 dark:border-slate-700">
            <button
              @click="filterStatus = 'all'"
              :class="[
                'px-2.5 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer',
                filterStatus === 'all' ? 'bg-orange-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
              ]"
            >
              Todas ({{ orders.length }})
            </button>
            <button
              @click="filterStatus = 'in_kitchen'"
              :class="[
                'px-2.5 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer',
                filterStatus === 'in_kitchen' ? 'bg-orange-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
              ]"
            >
              En Cocción
            </button>
            <button
              @click="filterStatus = 'ready'"
              :class="[
                'px-2.5 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer',
                filterStatus === 'ready' ? 'bg-emerald-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
              ]"
            >
              Listas
            </button>
            <button
              @click="filterStatus = 'delivery'"
              :class="[
                'px-2.5 py-1 rounded-lg text-xs font-bold transition-colors flex items-center gap-1 cursor-pointer',
                filterStatus === 'delivery' ? 'bg-orange-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
              ]"
            >
              <Truck class="w-3.5 h-3.5" /> App / Delivery
            </button>
          </div>

          <!-- Refresh Buttons -->
          <button
            @click="toggleAutoRefresh"
            :class="[
              'p-2 rounded-xl border text-xs font-semibold flex items-center gap-1 transition-colors cursor-pointer',
              autoRefreshEnabled
                ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400'
                : 'bg-slate-100 border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400'
            ]"
            title="Alternar Auto-refresco"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': autoRefreshEnabled }" />
          </button>

          <Button
            icon="pi pi-refresh"
            class="bg-slate-100 hover:bg-slate-200 border-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-slate-700 dark:text-slate-200 p-2"
            @click="manualRefresh"
            title="Refrescar Ahora"
          />
        </div>
      </div>

      <!-- KDS Kanban Tickets Grid -->
      <div v-if="filteredOrders.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div
          v-for="order in filteredOrders"
          :key="order.id"
          :class="[
            'bg-white dark:bg-slate-900 border-2 rounded-2xl flex flex-col justify-between overflow-hidden transition-all shadow-md',
            getElapsedMinutes(order.created_at).urgency === 'danger'
              ? 'border-red-500/80 shadow-red-500/10'
              : getElapsedMinutes(order.created_at).urgency === 'warning'
              ? 'border-amber-500/80 shadow-amber-500/10'
              : 'border-slate-200 dark:border-slate-800'
          ]"
        >
          <!-- Ticket Header -->
          <div
            :class="[
              'p-3 border-b flex items-center justify-between',
              getElapsedMinutes(order.created_at).urgency === 'danger'
                ? 'bg-red-500/10 border-red-500/30 text-red-700 dark:text-red-300'
                : getElapsedMinutes(order.created_at).urgency === 'warning'
                ? 'bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300'
                : 'bg-slate-50 border-slate-200 text-slate-800 dark:bg-slate-800/60 dark:border-slate-800 dark:text-slate-200'
            ]"
          >
            <div>
              <div class="flex items-center gap-2">
                <span class="font-black text-base tracking-wide flex items-center gap-1.5">
                  <Truck v-if="order.order_type === 'delivery'" class="w-4 h-4 text-orange-500" />
                  <ShoppingBag v-else-if="order.order_type === 'takeaway'" class="w-4 h-4 text-blue-500" />
                  {{ order.table ? order.table.table_number : (order.order_type === 'delivery' ? 'Delivery App' : 'Para Llevar') }}
                </span>
                <Tag
                  :value="`#${order.id}`"
                  severity="secondary"
                  class="text-[10px]"
                />
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                <template v-if="order.order_type === 'delivery'">
                  {{ order.customer?.name || 'Cliente App' }} · Envío a Domicilio
                </template>
                <template v-else-if="order.order_type === 'takeaway'">
                  {{ order.customer?.name || 'Mostrador' }} · Para Recoger
                </template>
                <template v-else>
                  {{ order.table?.area?.name || 'Salón' }} · {{ order.user?.name || 'Mesero' }}
                </template>
              </p>
            </div>

            <!-- Elapsed Live Timer -->
            <div
              :class="[
                'px-2.5 py-1 rounded-xl font-mono font-black text-xs flex items-center gap-1.5 shadow-inner shrink-0',
                getElapsedMinutes(order.created_at).urgency === 'danger'
                  ? 'bg-red-600 text-white'
                  : getElapsedMinutes(order.created_at).urgency === 'warning'
                  ? 'bg-amber-600 text-white'
                  : 'bg-emerald-500/15 text-emerald-700 dark:bg-slate-800 dark:text-emerald-400'
              ]"
            >
              <Clock class="w-3.5 h-3.5" />
              <span>{{ getElapsedMinutes(order.created_at).formatted }}</span>
            </div>
          </div>

          <!-- Delivery Address Banner if Delivery -->
          <div v-if="order.order_type === 'delivery' && order.delivery_address" class="px-3.5 py-2 bg-orange-500/10 border-b border-orange-500/20 text-[11px] text-orange-700 dark:text-orange-300 flex items-center gap-1.5">
            <MapPin class="w-3.5 h-3.5 shrink-0 text-orange-500" />
            <span class="truncate">{{ order.delivery_address }}</span>
          </div>

          <!-- Ticket Items List -->
          <div class="p-3.5 space-y-3 flex-1">
            <div
              v-for="item in order.items"
              :key="item.id"
              :class="[
                'p-2.5 rounded-xl border transition-all flex flex-col justify-between gap-1.5',
                item.status === 'ready'
                  ? 'bg-emerald-500/10 border-emerald-500/40 text-emerald-800 dark:text-emerald-300'
                  : item.status === 'cooking'
                  ? 'bg-orange-500/10 border-orange-500/40 text-orange-800 dark:text-orange-300'
                  : item.status === 'served'
                  ? 'bg-slate-100 border-slate-200 text-slate-400 dark:bg-slate-950/40 dark:border-slate-800 dark:text-slate-500 opacity-60'
                  : 'bg-slate-50 border-slate-200 text-slate-800 dark:bg-slate-950/80 dark:border-slate-800 dark:text-slate-200'
              ]"
            >
              <div class="flex items-start justify-between gap-2">
                <div class="flex items-start gap-2">
                  <span class="font-black text-sm text-orange-600 dark:text-orange-400 shrink-0">{{ item.quantity }}x</span>
                  <div>
                    <h4 class="font-bold text-xs text-slate-800 dark:text-slate-100 leading-snug">{{ item.dish?.name }}</h4>
                    <div v-if="item.notes" class="mt-1 text-[11px] font-medium bg-amber-500/15 border border-amber-500/30 text-amber-800 dark:text-amber-300 px-2 py-0.5 rounded-md inline-block">
                      ⚠️ "{{ item.notes }}"
                    </div>
                  </div>
                </div>
              </div>

              <!-- Item Interactive Controls -->
              <div class="flex items-center justify-between pt-1 border-t border-slate-200/80 dark:border-slate-800/60 mt-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                  Estado: <strong :class="item.status === 'ready' ? 'text-emerald-600 dark:text-emerald-400' : item.status === 'cooking' ? 'text-orange-600 dark:text-orange-400' : 'text-slate-700 dark:text-slate-300'">{{ item.status }}</strong>
                </span>

                <div class="flex gap-1">
                  <!-- Button to Cocinar -->
                  <button
                    v-if="item.status === 'pending'"
                    @click="changeItemStatus(item, 'cooking')"
                    class="px-2 py-1 bg-orange-600/20 hover:bg-orange-600 border border-orange-500/40 text-orange-700 dark:text-orange-300 hover:text-white rounded-lg text-[10px] font-bold flex items-center gap-1 transition-colors cursor-pointer"
                  >
                    <Flame class="w-3 h-3" /> Cocinar
                  </button>

                  <!-- Button to Listo -->
                  <button
                    v-if="item.status === 'cooking' || item.status === 'pending'"
                    @click="changeItemStatus(item, 'ready')"
                    class="px-2 py-1 bg-emerald-600/20 hover:bg-emerald-600 border border-emerald-500/40 text-emerald-700 dark:text-emerald-300 hover:text-white rounded-lg text-[10px] font-bold flex items-center gap-1 transition-colors cursor-pointer"
                  >
                    <Check class="w-3 h-3" /> Listo
                  </button>

                  <!-- Button to Servido -->
                  <button
                    v-if="item.status === 'ready'"
                    @click="changeItemStatus(item, 'served')"
                    class="px-2 py-1 bg-blue-600/20 hover:bg-blue-600 border border-blue-500/40 text-blue-700 dark:text-blue-300 hover:text-white rounded-lg text-[10px] font-bold flex items-center gap-1 transition-colors cursor-pointer"
                  >
                    <CheckCircle2 class="w-3 h-3" /> Servido
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Ticket Batch Actions Footer -->
          <div class="p-3 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-200 dark:border-slate-800/80 flex gap-2">
            <button
              v-if="order.status !== 'ready'"
              @click="advanceAllOrder(order, 'cooking')"
              class="flex-1 py-1.5 bg-white hover:bg-orange-500/15 text-slate-700 hover:text-orange-600 border border-slate-300 dark:bg-slate-800 dark:hover:bg-orange-600/30 dark:text-slate-300 dark:hover:text-orange-300 dark:border-slate-700 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
            >
              <Flame class="w-3.5 h-3.5" /> Todo a Fuego
            </button>

            <button
              @click="advanceAllOrder(order, 'ready')"
              class="flex-1 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
            >
              <CheckCircle2 class="w-3.5 h-3.5" /> Todo Listo
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="py-16 bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl text-center space-y-3">
        <div class="w-16 h-16 bg-emerald-500/10 text-emerald-500 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto">
          <ChefHat class="w-8 h-8" />
        </div>
        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">¡Cocina al Día!</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
          No hay comandas pendientes de preparación en este momento. Las nuevas órdenes enviadas desde el POS o la App Móvil aparecerán aquí automáticamente.
        </p>
      </div>
    </div>
  </AdminLayout>
</template>
