<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '../layouts/AdminLayout.vue';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import {
  Flame,
  Boxes,
  CalendarCheck,
  AlertTriangle,
  History,
  DollarSign,
  TrendingUp,
  Receipt,
  Armchair,
  ChefHat,
  Truck,
  ShoppingBag,
  BarChart3,
  PieChart,
  RefreshCw,
  Award,
  ArrowUpRight,
  Users,
} from 'lucide-vue-next';

const props = defineProps({
  stats: Object,
  periods: Object,
  dailySales: { type: Array, default: () => [] },
  channelStats: { type: Object, default: () => ({}) },
  paymentMethods: { type: Object, default: () => ({}) },
  topDishes: { type: Array, default: () => [] },
  liveOrders: { type: Array, default: () => [] },
  lowStockList: { type: Array, default: () => [] },
  recentActivities: { type: Array, default: () => [] },
});

const toast = useToast();

// Dynamic Period Filter ('today', 'week', 'month', 'all')
const selectedPeriod = ref('all');
const activeChartTab = ref('daily'); // 'daily' | 'channels' | 'payments'
const isRefreshing = ref(false);

const periodOptions = [
  { key: 'today', label: 'Hoy' },
  { key: 'week', label: 'Últimos 7 Días' },
  { key: 'month', label: 'Este Mes' },
  { key: 'all', label: 'Histórico General' },
];

const currentMetrics = computed(() => {
  return props.periods?.[selectedPeriod.value] || {
    revenue: 0,
    orders_count: 0,
    paid_orders_count: 0,
    avg_ticket: 0,
    delivery_count: 0,
  };
});

function refreshDashboard() {
  isRefreshing.value = true;
  router.reload({
    preserveScroll: true,
    onFinish: () => {
      isRefreshing.value = false;
      toast.add({ severity: 'info', summary: 'Dashboard Actualizado', detail: 'KPIs sincronizados en tiempo real', life: 2000 });
    },
  });
}

// Charts Data
const dailyChartData = computed(() => ({
  labels: props.dailySales.map(d => d.date),
  datasets: [
    {
      label: 'Ingresos ($)',
      backgroundColor: 'rgba(234, 88, 12, 0.75)',
      borderColor: '#ea580c',
      borderWidth: 2,
      borderRadius: 8,
      data: props.dailySales.map(d => d.amount),
    },
  ],
}));

const channelChartData = computed(() => ({
  labels: [
    props.channelStats.dine_in?.label || 'En Salón',
    props.channelStats.delivery?.label || 'Delivery App',
    props.channelStats.takeaway?.label || 'Para Llevar',
  ],
  datasets: [
    {
      data: [
        props.channelStats.dine_in?.orders || 0,
        props.channelStats.delivery?.orders || 0,
        props.channelStats.takeaway?.orders || 0,
      ],
      backgroundColor: ['#ea580c', '#8b5cf6', '#3b82f6'],
      borderWidth: 2,
    },
  ],
}));

const paymentChartData = computed(() => ({
  labels: ['Efectivo ($)', 'Tarjeta ($)', 'Transferencia / QR ($)'],
  datasets: [
    {
      data: [
        props.paymentMethods.cash || 0,
        props.paymentMethods.card || 0,
        props.paymentMethods.transfer || 0,
      ],
      backgroundColor: ['#10b981', '#3b82f6', '#a855f7'],
      borderWidth: 2,
    },
  ],
}));

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { labels: { color: '#64748b', font: { size: 12 } } },
  },
  scales: {
    x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(148, 163, 184, 0.15)' } },
    y: {
      ticks: { color: '#64748b', callback: (v) => `$${v}` },
      grid: { color: 'rgba(148, 163, 184, 0.15)' },
    },
  },
};

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
      labels: { color: '#64748b', font: { size: 12 } },
    },
  },
};

const maxDishUnits = computed(() => {
  const max = Math.max(...props.topDishes.map(d => Number(d.units_sold || 0)), 1);
  return max;
});

function getOrderBadge(order) {
  if (order.status === 'paid') return { label: 'Pagado', severity: 'success' };
  if (order.status === 'ready') return { label: 'Listo', severity: 'info' };
  if (order.status === 'in_kitchen') return { label: 'En Cocina', severity: 'warn' };
  if (order.status === 'cancelled') return { label: 'Cancelado', severity: 'danger' };
  return { label: 'Recibido', severity: 'warn' };
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <span>Dashboard Ejecutivo & Operativo en Vivo</span>
    </template>

    <div class="space-y-6">
      <!-- ── BARRA DE CONTROL Y FILTRO DE PERIODO CON ESPACIADO CÓMODO ── -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm rounded-2xl px-5 py-3.5 flex flex-wrap items-center justify-between gap-4 transition-colors duration-200">
        <div class="flex items-center gap-3">
          <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-500/20">
            <TrendingUp class="w-5 h-5" />
          </div>
          <div>
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Indicadores Clave de Rendimiento (KPIs)</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Filtra los ingresos, comandas y envíos de la App según el periodo seleccionado.</p>
          </div>
        </div>

        <!-- Controles Dinámicos de Periodo y Refresco -->
        <div class="flex flex-wrap items-center gap-2.5">
          <div class="flex flex-wrap rounded-xl bg-slate-100 dark:bg-slate-800 p-1.5 border border-slate-200 dark:border-slate-700 gap-1">
            <button
              v-for="p in periodOptions"
              :key="p.key"
              type="button"
              @click="selectedPeriod = p.key"
              :class="[
                'px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer',
                selectedPeriod === p.key
                  ? 'bg-orange-600 text-white shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
              ]"
            >
              {{ p.label }}
            </button>
          </div>

          <Button
            icon="pi pi-refresh"
            label="Sincronizar"
            size="small"
            :loading="isRefreshing"
            class="bg-emerald-600 hover:bg-emerald-500 text-white border-none !px-3.5 !py-2 rounded-xl text-xs font-bold shadow-sm"
            @click="refreshDashboard"
            title="Sincronizar KPIs ahora"
          />
        </div>
      </div>

      <!-- ── FILA 1: 4 KPIS FINANCIEROS Y DE CANALES (DINÁMICOS POR PERIODO) ── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Ingresos del Periodo -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-5 rounded-2xl flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Ingresos ({{ periodOptions.find(p => p.key === selectedPeriod)?.label }})
              </p>
              <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                ${{ Number(currentMetrics.revenue || 0).toFixed(2) }}
              </h3>
            </div>
            <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-500/20">
              <DollarSign class="w-6 h-6" />
            </div>
          </div>
          <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
            <span class="text-slate-500 dark:text-slate-400">{{ currentMetrics.paid_orders_count }} cuentas cobradas</span>
            <Link href="/reports" class="text-orange-600 dark:text-orange-400 font-semibold flex items-center gap-0.5 hover:underline">
              Ver reportes <ArrowUpRight class="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>

        <!-- 2. Ticket Promedio & Comandas -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-5 rounded-2xl flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ticket Promedio</p>
              <h3 class="text-2xl font-black text-slate-800 dark:text-slate-100 mt-1">
                ${{ Number(currentMetrics.avg_ticket || 0).toFixed(2) }}
              </h3>
            </div>
            <div class="p-3 bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-xl border border-purple-500/20">
              <Receipt class="w-6 h-6" />
            </div>
          </div>
          <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
            <span class="text-slate-500 dark:text-slate-400">Total órdenes: <strong>{{ currentMetrics.orders_count }}</strong></span>
            <Link href="/pos" class="text-purple-600 dark:text-purple-400 font-semibold flex items-center gap-0.5 hover:underline">
              Ir al POS <ArrowUpRight class="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>

        <!-- 3. Pedidos App Móvil / Delivery & Para Llevar -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-5 rounded-2xl flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pedidos App / Delivery</p>
              <h3 class="text-2xl font-black text-orange-600 dark:text-orange-400 mt-1">
                {{ stats.active_delivery_orders }} activos
                <span class="text-sm font-semibold text-slate-400">/ {{ stats.total_delivery_orders }} total</span>
              </h3>
            </div>
            <div class="p-3 bg-orange-500/10 text-orange-600 dark:text-orange-400 rounded-xl border border-orange-500/20">
              <Truck class="w-6 h-6" />
            </div>
          </div>
          <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
            <span class="text-slate-500 dark:text-slate-400">En este periodo: <strong>{{ currentMetrics.delivery_count }}</strong> envíos</span>
            <Link href="/pos" class="text-orange-600 dark:text-orange-400 font-semibold flex items-center gap-0.5 hover:underline">
              Gestionar envíos <ArrowUpRight class="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>

        <!-- 4. Ocupación de Mesas en Tiempo Real -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-5 rounded-2xl flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ocupación de Salón</p>
              <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">
                {{ stats.occupancy_rate }}%
                <span class="text-sm font-semibold text-slate-400">({{ stats.occupied_tables }}/{{ stats.total_tables }} mesas)</span>
              </h3>
            </div>
            <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-500/20">
              <Armchair class="w-6 h-6" />
            </div>
          </div>
          <div class="mt-3 space-y-1.5">
            <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
              <div
                class="h-full bg-gradient-to-r from-blue-500 to-cyan-400 rounded-full transition-all duration-500"
                :style="{ width: `${Math.min(100, stats.occupancy_rate)}%` }"
              ></div>
            </div>
            <div class="flex justify-between text-[11px] text-slate-500 dark:text-slate-400">
              <span>🟢 {{ stats.available_tables }} libres</span>
              <span>🟡 {{ stats.reserved_tables }} reservadas</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── FILA 2: 4 KPIS OPERATIVOS, COCINA, INVENTARIO Y RESERVAS ── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 5. Monitor de Cocina (KDS) -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-4 rounded-2xl flex items-center justify-between transition-colors duration-200">
          <div>
            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Comandas en Cocina</p>
            <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 mt-0.5">
              {{ stats.active_kitchen_orders }} órdenes · <span class="text-orange-600 dark:text-orange-400">{{ stats.active_cooking_dishes }} platos</span>
            </h3>
            <Link href="/kitchen" class="text-[11px] text-orange-600 dark:text-orange-400 hover:underline font-medium">
              Abrir Monitor KDS →
            </Link>
          </div>
          <div class="p-3 bg-orange-500/10 text-orange-600 dark:text-orange-400 rounded-xl border border-orange-500/20">
            <ChefHat class="w-5 h-5" />
          </div>
        </div>

        <!-- 6. Valor de Inventario & Mermas -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-4 rounded-2xl flex items-center justify-between transition-colors duration-200">
          <div>
            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Valor en Almacén</p>
            <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 mt-0.5">
              ${{ Number(stats.inventory_value || 0).toFixed(2) }}
            </h3>
            <p class="text-[11px] text-red-500 dark:text-red-400 font-medium">
              Mermas: ${{ Number(stats.waste_loss || 0).toFixed(2) }}
            </p>
          </div>
          <div class="p-3 bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 rounded-xl border border-cyan-500/20">
            <Boxes class="w-5 h-5" />
          </div>
        </div>

        <!-- 7. Alertas de Stock Crítico -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-4 rounded-2xl flex items-center justify-between transition-colors duration-200">
          <div>
            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Alertas Bajo Stock</p>
            <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-0.5">
              {{ stats.low_stock_ingredients }} <span class="text-xs font-normal text-slate-500">de {{ stats.total_ingredients }} insumos</span>
            </h3>
            <Link href="/ingredients" class="text-[11px] text-amber-600 dark:text-amber-400 hover:underline font-medium">
              Revisar almacén →
            </Link>
          </div>
          <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-500/20">
            <AlertTriangle class="w-5 h-5" />
          </div>
        </div>

        <!-- 8. Reservas & Clientes -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-4 rounded-2xl flex items-center justify-between transition-colors duration-200">
          <div>
            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Reservas Hoy / Clientes</p>
            <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
              {{ stats.today_reservations }} hoy · <span class="text-slate-700 dark:text-slate-200">{{ stats.total_customers }} clientes</span>
            </h3>
            <Link href="/reservations" class="text-[11px] text-emerald-600 dark:text-emerald-400 hover:underline font-medium">
              {{ stats.pending_reservations }} pendientes de confirmar →
            </Link>
          </div>
          <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-500/20">
            <CalendarCheck class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- ── FILA 3: GRÁFICOS INTERACTIVOS + TOP PLATILLOS ESTRELLA ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Gráfico Dinámico Multi-Vista (7 Cols) -->
        <div class="lg:col-span-7 bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 flex flex-col justify-between transition-colors duration-200">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div class="flex items-center gap-2">
              <BarChart3 class="w-5 h-5 text-orange-500" />
              <h2 class="font-bold text-slate-800 dark:text-slate-100">Analítica Dinámica de Operación</h2>
            </div>

            <!-- Selector de Gráfico -->
            <div class="flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700 text-xs">
              <button
                type="button"
                @click="activeChartTab = 'daily'"
                :class="[
                  'px-2.5 py-1 rounded-lg font-bold transition-colors cursor-pointer',
                  activeChartTab === 'daily' ? 'bg-orange-600 text-white' : 'text-slate-600 dark:text-slate-400'
                ]"
              >
                Ventas 7 Días
              </button>
              <button
                type="button"
                @click="activeChartTab = 'channels'"
                :class="[
                  'px-2.5 py-1 rounded-lg font-bold transition-colors cursor-pointer',
                  activeChartTab === 'channels' ? 'bg-orange-600 text-white' : 'text-slate-600 dark:text-slate-400'
                ]"
              >
                Por Canal (App/Salón)
              </button>
              <button
                type="button"
                @click="activeChartTab = 'payments'"
                :class="[
                  'px-2.5 py-1 rounded-lg font-bold transition-colors cursor-pointer',
                  activeChartTab === 'payments' ? 'bg-orange-600 text-white' : 'text-slate-600 dark:text-slate-400'
                ]"
              >
                Métodos de Pago
              </button>
            </div>
          </div>

          <div class="h-64">
            <Chart
              v-if="activeChartTab === 'daily'"
              type="bar"
              :data="dailyChartData"
              :options="barChartOptions"
              class="h-full w-full"
            />
            <Chart
              v-else-if="activeChartTab === 'channels'"
              type="doughnut"
              :data="channelChartData"
              :options="doughnutOptions"
              class="h-full w-full"
            />
            <Chart
              v-else
              type="doughnut"
              :data="paymentChartData"
              :options="doughnutOptions"
              class="h-full w-full"
            />
          </div>
        </div>

        <!-- Ranking de Platillos Más Vendidos (5 Cols) -->
        <div class="lg:col-span-5 bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 flex flex-col justify-between transition-colors duration-200">
          <div>
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <Award class="w-5 h-5 text-amber-500" />
                <h2 class="font-bold text-slate-800 dark:text-slate-100">Platillos Estrella (Más Pedidos)</h2>
              </div>
              <Tag :value="`${stats.available_dishes}/${stats.total_dishes} activos`" severity="success" />
            </div>

            <div class="space-y-3.5">
              <div v-for="(dish, idx) in topDishes" :key="dish.id" class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                  <span class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                    <span class="w-5 h-5 rounded-full bg-orange-500/15 text-orange-600 dark:text-orange-400 flex items-center justify-center text-[10px] font-black">
                      #{{ idx + 1 }}
                    </span>
                    {{ dish.name }}
                  </span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400">
                    {{ dish.units_sold || 0 }} uds · ${{ Number(dish.revenue || 0).toFixed(2) }}
                  </span>
                </div>
                <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div
                    class="h-full bg-gradient-to-r from-orange-500 to-amber-400 rounded-full"
                    :style="{ width: `${Math.max(8, ((dish.units_sold || 0) / maxDishUnits) * 100)}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-right">
            <Link href="/dishes" class="text-xs font-bold text-orange-600 dark:text-orange-400 hover:underline">
              Administrar Menú Completo →
            </Link>
          </div>
        </div>
      </div>

      <!-- ── FILA 4: MONITOR DE PEDIDOS RECIENTES (SALÓN Y APP MÓVIL DELIVERY) ── -->
      <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 transition-colors duration-200">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
          <div class="flex items-center gap-2">
            <Truck class="w-5 h-5 text-orange-500" />
            <div>
              <h2 class="font-bold text-slate-800 dark:text-slate-100">Últimos Pedidos en Tiempo Real (Salón & App Móvil Delivery)</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400">Seguimiento directo de órdenes de mesa, envíos a domicilio y para recoger.</p>
            </div>
          </div>
          <Link
            href="/pos"
            class="px-3.5 py-1.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold transition-colors"
          >
            Ver Todos en Punto de Venta
          </Link>
        </div>

        <DataTable :value="liveOrders" size="small" class="p-datatable-sm text-sm" responsiveLayout="scroll">
          <Column header="# Orden" style="width: 90px">
            <template #body="{ data }">
              <span class="font-mono font-bold text-slate-800 dark:text-slate-100">#{{ data.id }}</span>
            </template>
          </Column>
          <Column header="Canal / Tipo">
            <template #body="{ data }">
              <span
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold"
                :class="{
                  'bg-orange-500/15 text-orange-700 dark:text-orange-300': data.order_type === 'delivery',
                  'bg-blue-500/15 text-blue-700 dark:text-blue-300': data.order_type === 'takeaway',
                  'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300': data.order_type === 'dine_in',
                }"
              >
                <Truck v-if="data.order_type === 'delivery'" class="w-3.5 h-3.5" />
                <ShoppingBag v-else-if="data.order_type === 'takeaway'" class="w-3.5 h-3.5" />
                <Armchair v-else class="w-3.5 h-3.5" />
                {{ data.order_type === 'delivery' ? 'Delivery App' : data.order_type === 'takeaway' ? 'Para Llevar' : (data.table?.table_number || 'En Salón') }}
              </span>
            </template>
          </Column>
          <Column header="Cliente / Destino">
            <template #body="{ data }">
              <div>
                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ data.customer?.name || 'Cliente General' }}</div>
                <div v-if="data.delivery_address" class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-xs">
                  📍 {{ data.delivery_address }}
                </div>
              </div>
            </template>
          </Column>
          <Column header="Platillos">
            <template #body="{ data }">
              <span class="text-xs text-slate-600 dark:text-slate-300">
                {{ data.items?.map(i => `${i.quantity}x ${i.dish?.name || ''}`).join(', ') || '—' }}
              </span>
            </template>
          </Column>
          <Column header="Total">
            <template #body="{ data }">
              <span class="font-bold text-emerald-600 dark:text-emerald-400">${{ parseFloat(data.total_amount).toFixed(2) }}</span>
            </template>
          </Column>
          <Column header="Estado">
            <template #body="{ data }">
              <Tag :value="getOrderBadge(data).label" :severity="getOrderBadge(data).severity" />
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- ── FILA 5: ALERTAS DE BAJO STOCK + AUDITORÍA RECIENTE ── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Low Stock Warning Table -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 transition-colors duration-200">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <AlertTriangle class="w-5 h-5 text-amber-500 dark:text-amber-400" />
              <h2 class="font-semibold text-slate-800 dark:text-slate-200">Alertas de Bajo Stock</h2>
            </div>
            <Tag :value="`${lowStockList.length} Insumos`" severity="warn" />
          </div>

          <DataTable :value="lowStockList" size="small" class="p-datatable-sm text-sm" responsiveLayout="scroll">
            <Column field="name" header="Ingrediente">
              <template #body="{ data }">
                <span class="font-medium text-slate-800 dark:text-slate-200">{{ data.name }}</span>
              </template>
            </Column>
            <Column field="current_stock" header="Stock Actual">
              <template #body="{ data }">
                <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ data.current_stock }} {{ data.unit_of_measure }}</span>
              </template>
            </Column>
            <Column field="minimum_stock" header="Stock Mínimo">
              <template #body="{ data }">
                <span class="text-slate-500 dark:text-slate-400">{{ data.minimum_stock }} {{ data.unit_of_measure }}</span>
              </template>
            </Column>
          </DataTable>
        </div>

        <!-- Recent Activities Table -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 transition-colors duration-200">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <History class="w-5 h-5 text-orange-500 dark:text-orange-400" />
              <h2 class="font-semibold text-slate-800 dark:text-slate-200">Actividad Reciente (Auditoría)</h2>
            </div>
          </div>

          <DataTable :value="recentActivities" size="small" class="p-datatable-sm text-sm" responsiveLayout="scroll">
            <Column field="action" header="Acción">
              <template #body="{ data }">
                <Tag
                  :value="data.action"
                  :severity="data.action === 'created' ? 'success' : data.action === 'updated' ? 'info' : 'danger'"
                />
              </template>
            </Column>
            <Column field="model_type" header="Módulo">
              <template #body="{ data }">
                <span class="font-mono text-xs text-slate-600 dark:text-slate-300 font-semibold">{{ data.model_type.split('\\').pop() }}</span>
              </template>
            </Column>
            <Column field="created_at" header="Fecha">
              <template #body="{ data }">
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ new Date(data.created_at).toLocaleString() }}</span>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
