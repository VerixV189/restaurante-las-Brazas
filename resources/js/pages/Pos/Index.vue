<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import {
  Armchair,
  Flame,
  Plus,
  Minus,
  Trash2,
  Clock,
  Receipt,
  CreditCard,
  Banknote,
  Send,
  Search,
  Printer,
  Truck,
  ShoppingBag,
  MapPin,
  Phone,
  KeyRound,
  CheckCircle2,
  RotateCcw,
} from 'lucide-vue-next';

const props = defineProps({
  areas: Array,
  categories: Array,
  dishes: Array,
  customers: Array,
  activeOrders: Array,
  deliveryOrders: {
    type: Array,
    default: () => [],
  },
});

const toast = useToast();
const confirm = useConfirm();

// View Mode: 'tables' (Salón) or 'delivery' (Pedidos App / Delivery & Takeaway)
const activeTab = ref('tables');
const selectedCategoryId = ref('all');
const searchQuery = ref('');

const selectedTable = ref(null);
const currentActiveOrder = ref(null);
const paymentModalOpen = ref(false);

// Local Order Cart for taking a new order
const cartItems = ref([]);
const selectedCustomerId = ref(null);
const orderType = ref('dine_in');
const deliveryAddress = ref('');

// Payment Form
const paymentForm = useForm({
  payment_method: 'cash',
  amount_paid: 0,
  transaction_ref: '',
});

// Active Delivery / Takeaway Orders count
const activeDeliveryCount = computed(() => {
  return (props.deliveryOrders || []).filter(o =>
    !['paid', 'cancelled'].includes(o.status) && !o.delivered_at
  ).length;
});

// Filtered dishes for POS picker
const filteredDishes = computed(() => {
  return props.dishes.filter(d => {
    const matchesCategory = selectedCategoryId.value === 'all' || d.category_id === selectedCategoryId.value;
    const matchesSearch = d.name.toLowerCase().includes(searchQuery.value.toLowerCase());
    return matchesCategory && matchesSearch;
  });
});

// Cart Total Calculation
const cartSubtotal = computed(() => {
  return cartItems.value.reduce((acc, item) => acc + (item.price * item.quantity), 0);
});

// Handlers
function onTableClick(table) {
  selectedTable.value = table;
  orderType.value = 'dine_in';
  const activeOrder = table.orders && table.orders.length > 0 ? table.orders[0] : null;

  if (activeOrder) {
    currentActiveOrder.value = activeOrder;
    cartItems.value = [];
  } else {
    currentActiveOrder.value = null;
    cartItems.value = [];
    selectedCustomerId.value = null;
  }
}

function onSelectDeliveryOrder(order) {
  selectedTable.value = null;
  currentActiveOrder.value = order;
  cartItems.value = [];
}

function startNewOrder(defaultType = 'dine_in') {
  selectedTable.value = null;
  currentActiveOrder.value = null;
  cartItems.value = [];
  selectedCustomerId.value = null;
  orderType.value = defaultType;
  deliveryAddress.value = '';
}

function addToCart(dish) {
  const existing = cartItems.value.find(i => i.dish_id === dish.id);
  if (existing) {
    existing.quantity++;
  } else {
    cartItems.value.push({
      dish_id: dish.id,
      name: dish.name,
      price: parseFloat(dish.price),
      quantity: 1,
      notes: '',
    });
  }
}

function updateCartItemQty(index, delta) {
  cartItems.value[index].quantity += delta;
  if (cartItems.value[index].quantity <= 0) {
    cartItems.value.splice(index, 1);
  }
}

function removeFromCart(index) {
  cartItems.value.splice(index, 1);
}

// Submit New Order
function submitNewOrder() {
  if (cartItems.value.length === 0) {
    toast.add({ severity: 'warn', summary: 'Comanda Vacía', detail: 'Agrega al menos un platillo', life: 3000 });
    return;
  }
  if (orderType.value === 'delivery' && !deliveryAddress.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Dirección Requerida', detail: 'Ingresa la dirección para el envío a domicilio', life: 3000 });
    return;
  }

  router.post('/pos/orders', {
    table_id: orderType.value === 'dine_in' ? (selectedTable.value?.id || null) : null,
    customer_id: selectedCustomerId.value,
    order_type: orderType.value,
    delivery_address: orderType.value === 'delivery' ? deliveryAddress.value : null,
    items: cartItems.value.map(i => ({
      dish_id: i.dish_id,
      quantity: i.quantity,
      notes: i.notes,
    })),
  }, {
    onSuccess: () => {
      cartItems.value = [];
      deliveryAddress.value = '';
      toast.add({ severity: 'success', summary: 'Comanda Enviada', detail: 'Comanda enviada a cocina exitosamente', life: 3000 });
    }
  });
}

// Add items to existing active order
function addItemsToExistingOrder() {
  if (cartItems.value.length === 0 || !currentActiveOrder.value) return;

  cartItems.value.forEach(item => {
    router.post(`/pos/orders/${currentActiveOrder.value.id}/items`, {
      dish_id: item.dish_id,
      quantity: item.quantity,
      notes: item.notes,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        toast.add({ severity: 'success', summary: 'Agregado', detail: `${item.name} añadido a la comanda`, life: 2000 });
      }
    });
  });
  cartItems.value = [];
}

function dispatchDeliveryOrder(order) {
  router.post(`/pos/orders/${order.id}/dispatch`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'info', summary: 'En Camino', detail: `Pedido #${order.id} despachado a domicilio`, life: 3000 });
    }
  });
}

function deliverOrder(order) {
  router.post(`/pos/orders/${order.id}/deliver`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Entregado', detail: `Pedido #${order.id} marcado como entregado`, life: 3000 });
    }
  });
}

// Open Payment Modal
function openPaymentModal(order) {
  currentActiveOrder.value = order;
  paymentForm.amount_paid = parseFloat(order.total_amount);
  paymentForm.payment_method = 'cash';
  paymentForm.transaction_ref = '';
  paymentModalOpen.value = true;
}

// Submit Payment and Close Table
function submitPayment() {
  paymentForm.post(`/pos/orders/${currentActiveOrder.value.id}/close`, {
    onSuccess: () => {
      paymentModalOpen.value = false;
      currentActiveOrder.value = null;
      toast.add({
        severity: 'success',
        summary: 'Cuenta Pagada',
        detail: 'Comanda cobrada con éxito.',
        life: 4000,
      });
    }
  });
}

// Cancel Order
function cancelOrder(order) {
  confirm.require({
    message: `¿Estás seguro de cancelar la comanda #${order.id}?`,
    header: 'Confirmar Cancelación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, cancelar',
    rejectLabel: 'Volver',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.post(`/pos/orders/${order.id}/cancel`, {}, {
        onSuccess: () => {
          currentActiveOrder.value = null;
          toast.add({ severity: 'info', summary: 'Comanda Cancelada', detail: 'La comanda ha sido cancelada', life: 3000 });
        }
      });
    }
  });
}

function getOrderStatusInfo(order) {
  if (order.status === 'cancelled') return { label: 'Cancelado', severity: 'danger' };
  if (order.delivered_at && order.status === 'paid') return { label: 'Entregado y Pagado', severity: 'success' };
  if (order.delivered_at) return { label: 'Entregado', severity: 'success' };
  if (order.dispatched_at) return { label: '🛵 En Camino', severity: 'info' };
  if (order.status === 'ready') return { label: 'Listo p/ Entrega', severity: 'success' };
  if (order.status === 'in_kitchen') return { label: 'En Cocina', severity: 'warn' };
  if (order.status === 'paid') return { label: 'Pagado', severity: 'success' };
  return { label: 'Recibido / Pendiente', severity: 'warn' };
}
</script>

<template>
  <AdminLayout>
    <template #header>Punto de Venta (POS), Comandas & Pedidos App Móvil</template>

    <div class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left: Table Floor / App Delivery Orders & Dish Catalog (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">

          <!-- 1. Selector de Vista: Mesas en Salón vs. Pedidos App Móvil / Delivery -->
          <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 transition-colors duration-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-200 dark:border-slate-800">
              <!-- Mode Tabs -->
              <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                <button
                  type="button"
                  @click="activeTab = 'tables'"
                  :class="[
                    'px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 transition-all cursor-pointer',
                    activeTab === 'tables'
                      ? 'bg-orange-600 text-white shadow-xs'
                      : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
                  ]"
                >
                  <Armchair class="w-4 h-4" />
                  <span>Mesas en Salón</span>
                </button>

                <button
                  type="button"
                  @click="activeTab = 'delivery'"
                  :class="[
                    'px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 transition-all cursor-pointer',
                    activeTab === 'delivery'
                      ? 'bg-orange-600 text-white shadow-xs'
                      : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
                  ]"
                >
                  <Truck class="w-4 h-4" />
                  <span>Pedidos App / Delivery & Para Llevar</span>
                  <span
                    class="px-1.5 py-0.5 text-[10px] rounded-full font-extrabold"
                    :class="activeTab === 'delivery' ? 'bg-white text-orange-600' : 'bg-orange-500/20 text-orange-600 dark:text-orange-400'"
                  >
                    {{ deliveryOrders.length }}
                  </span>
                </button>
              </div>

              <!-- Legend for Tables -->
              <div v-if="activeTab === 'tables'" class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300 font-medium">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Libre
                </span>
                <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300 font-medium">
                  <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span> Ocupada
                </span>
                <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300 font-medium">
                  <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Reservada
                </span>
              </div>

              <!-- Quick New Delivery Button -->
              <div v-else class="flex items-center gap-2">
                <button
                  type="button"
                  @click="startNewOrder('delivery')"
                  class="px-3 py-1.5 rounded-xl bg-orange-500/15 hover:bg-orange-600 text-orange-600 dark:text-orange-400 hover:text-white border border-orange-500/30 text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                  <Plus class="w-3.5 h-3.5" /> Nuevo Envío a Domicilio
                </button>
              </div>
            </div>

            <!-- VISTA A: MESAS EN SALÓN -->
            <div v-if="activeTab === 'tables'" class="space-y-4">
              <div v-for="area in areas" :key="area.id" class="first:pt-0 pt-3 border-t first:border-t-0 border-slate-200 dark:border-slate-800/80">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-2.5 uppercase tracking-wide">{{ area.name }}</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                  <button
                    v-for="table in area.tables"
                    :key="table.id"
                    @click="onTableClick(table)"
                    :class="[
                      'p-3.5 rounded-xl border text-left transition-all relative overflow-hidden flex flex-col justify-between h-24 group cursor-pointer',
                      selectedTable?.id === table.id ? 'ring-2 ring-orange-500' : '',
                      table.status === 'occupied'
                        ? 'bg-red-500/10 border-red-500/40 hover:border-red-500 dark:bg-red-500/10 dark:border-red-500/40'
                        : table.status === 'reserved'
                        ? 'bg-amber-500/10 border-amber-500/40 hover:border-amber-500'
                        : 'bg-slate-50 border-slate-200 hover:border-orange-500/60 hover:bg-orange-50/30 dark:bg-slate-800/60 dark:border-slate-700/60 dark:hover:border-orange-500/60 dark:hover:bg-slate-800'
                    ]"
                  >
                    <div class="flex items-center justify-between">
                      <span class="font-bold text-sm text-slate-800 dark:text-slate-100 group-hover:text-orange-600 dark:group-hover:text-orange-300">{{ table.table_number }}</span>
                      <span
                        class="w-2.5 h-2.5 rounded-full"
                        :class="table.status === 'occupied' ? 'bg-red-500 animate-pulse' : table.status === 'reserved' ? 'bg-amber-500' : 'bg-emerald-500'"
                      ></span>
                    </div>

                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                      <div v-if="table.status === 'occupied' && table.orders && table.orders.length > 0">
                        <strong class="text-red-600 dark:text-red-400">Orden #{{ table.orders[0].id }}</strong> ·
                        <span class="font-bold text-slate-800 dark:text-slate-200">${{ parseFloat(table.orders[0].total_amount).toFixed(2) }}</span>
                      </div>
                      <div v-else-if="table.status === 'occupied'">
                        <strong class="text-red-600 dark:text-red-400">Ocupada</strong>
                      </div>
                      <div v-else>
                        {{ table.capacity }} personas · <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Libre</span>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            <!-- VISTA B: PEDIDOS APP MÓVIL / ENVÍOS A DOMICILIO (DELIVERY) & PARA LLEVAR -->
            <div v-else class="space-y-3">
              <div v-if="deliveryOrders && deliveryOrders.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-80 overflow-y-auto pr-1">
                <div
                  v-for="dOrder in deliveryOrders"
                  :key="dOrder.id"
                  @click="onSelectDeliveryOrder(dOrder)"
                  :class="[
                    'p-4 rounded-xl border text-left transition-all cursor-pointer flex flex-col justify-between gap-2',
                    currentActiveOrder?.id === dOrder.id
                      ? 'ring-2 ring-orange-500 bg-orange-50/40 dark:bg-orange-950/20 border-orange-500/50'
                      : 'bg-slate-50 border-slate-200 hover:border-orange-400 dark:bg-slate-950/70 dark:border-slate-800 dark:hover:border-slate-700'
                  ]"
                >
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <span
                        class="p-2 rounded-lg"
                        :class="dOrder.order_type === 'delivery' ? 'bg-orange-500/15 text-orange-600 dark:text-orange-400' : 'bg-blue-500/15 text-blue-600 dark:text-blue-400'"
                      >
                        <Truck v-if="dOrder.order_type === 'delivery'" class="w-4 h-4" />
                        <ShoppingBag v-else class="w-4 h-4" />
                      </span>
                      <div>
                        <div class="flex items-center gap-1.5">
                          <span class="font-bold text-sm text-slate-800 dark:text-slate-100">Pedido #{{ dOrder.id }}</span>
                          <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ dOrder.order_type === 'delivery' ? 'Delivery App' : 'Para Llevar' }}
                          </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-0.5">
                          {{ dOrder.customer?.name || 'Cliente App Móvil' }}
                          <span v-if="dOrder.customer?.phone" class="text-slate-400">· {{ dOrder.customer.phone }}</span>
                        </p>
                      </div>
                    </div>

                    <Tag :value="getOrderStatusInfo(dOrder).label" :severity="getOrderStatusInfo(dOrder).severity" class="text-[10px]" />
                  </div>

                  <div v-if="dOrder.delivery_address" class="text-xs text-slate-600 dark:text-slate-400 flex items-start gap-1.5 bg-white dark:bg-slate-900 p-2 rounded-lg border border-slate-200/80 dark:border-slate-800">
                    <MapPin class="w-3.5 h-3.5 text-orange-500 shrink-0 mt-0.5" />
                    <span class="line-clamp-1">{{ dOrder.delivery_address }}</span>
                  </div>

                  <div class="flex items-center justify-between pt-1 border-t border-slate-200/80 dark:border-slate-800 text-xs">
                    <div class="flex items-center gap-2">
                      <span class="text-slate-500 dark:text-slate-400">{{ dOrder.items?.length || 0 }} platillos</span>
                      <span v-if="dOrder.delivery_pin" class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-700 dark:text-amber-300 font-mono font-bold text-[10px]">
                        PIN: {{ dOrder.delivery_pin }}
                      </span>
                    </div>
                    <span class="font-black text-sm text-emerald-600 dark:text-emerald-400">
                      ${{ parseFloat(dOrder.total_amount).toFixed(2) }}
                    </span>
                  </div>
                </div>
              </div>

              <div v-else class="py-10 text-center space-y-2">
                <Truck class="w-10 h-10 text-slate-400 mx-auto" />
                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">No hay pedidos de envío a domicilio registrados</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  Los pedidos realizados desde la aplicación móvil Flutter aparecerán aquí automáticamente.
                </p>
              </div>
            </div>
          </div>

          <!-- 2. Catálogo Rápido de Platillos -->
          <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 transition-colors duration-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
              <div class="flex items-center gap-2">
                <Flame class="w-5 h-5 text-orange-500" />
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Menú & Platillos</h3>
              </div>

              <!-- Search Bar -->
              <div class="relative w-full sm:w-64">
                <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Buscar platillo..."
                  class="w-full bg-slate-50 border border-slate-300 text-slate-800 placeholder-slate-400 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 rounded-xl pl-9 pr-3 py-1.5 text-xs focus:outline-none focus:border-orange-500"
                />
              </div>
            </div>

            <!-- Category Pills -->
            <div class="flex gap-2 overflow-x-auto pb-2 mb-4">
              <button
                @click="selectedCategoryId = 'all'"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer',
                  selectedCategoryId === 'all'
                    ? 'bg-orange-600 text-white'
                    : 'bg-slate-100 text-slate-600 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                ]"
              >
                Todos
              </button>
              <button
                v-for="cat in categories"
                :key="cat.id"
                @click="selectedCategoryId = cat.id"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer',
                  selectedCategoryId === cat.id
                    ? 'bg-orange-600 text-white'
                    : 'bg-slate-100 text-slate-600 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                ]"
              >
                {{ cat.name }}
              </button>
            </div>

            <!-- Dishes Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-96 overflow-y-auto pr-1">
              <div
                v-for="dish in filteredDishes"
                :key="dish.id"
                class="bg-slate-50 border border-slate-200 hover:border-orange-400 dark:bg-slate-950/70 dark:border-slate-800 dark:hover:border-slate-700 rounded-xl p-3 flex flex-col justify-between transition-all group"
              >
                <div>
                  <div class="flex items-start justify-between gap-2 mb-1">
                    <h4 class="font-bold text-xs text-slate-800 dark:text-slate-200 group-hover:text-orange-600 dark:group-hover:text-orange-400 leading-tight">{{ dish.name }}</h4>
                    <span class="font-bold text-xs text-emerald-600 dark:text-emerald-400 shrink-0">${{ parseFloat(dish.price).toFixed(2) }}</span>
                  </div>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-2 mb-2">{{ dish.description }}</p>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-800/60">
                  <span class="text-[10px] text-slate-500 flex items-center gap-1">
                    <Clock class="w-3 h-3" /> {{ dish.prep_time_minutes }}m
                  </span>
                  <button
                    @click="addToCart(dish)"
                    class="px-2.5 py-1 bg-orange-600 hover:bg-orange-500 text-white text-[11px] font-bold rounded-lg flex items-center gap-1 transition-colors cursor-pointer"
                  >
                    <Plus class="w-3 h-3" /> Agregar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Active Ticket / Cart Sidebar (4 Cols) -->
        <div class="lg:col-span-4 bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 flex flex-col justify-between transition-colors duration-200">
          <div>
            <!-- Ticket Header -->
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 mb-4">
              <div class="flex items-center gap-2">
                <Receipt class="w-5 h-5 text-orange-500" />
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">
                  <template v-if="currentActiveOrder">
                    {{ currentActiveOrder.table ? currentActiveOrder.table.table_number : (currentActiveOrder.order_type === 'delivery' ? 'Envío a Domicilio' : 'Para Llevar') }}
                  </template>
                  <template v-else-if="selectedTable">
                    {{ selectedTable.table_number }}
                  </template>
                  <template v-else>
                    Comanda en Curso
                  </template>
                </h3>
              </div>
              <div class="flex items-center gap-1.5">
                <Tag
                  v-if="currentActiveOrder"
                  :value="`Orden #${currentActiveOrder.id}`"
                  severity="danger"
                />
                <Tag
                  v-else
                  value="Nueva Comanda"
                  severity="success"
                />
                <button
                  v-if="currentActiveOrder"
                  @click="startNewOrder('dine_in')"
                  class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 text-xs cursor-pointer"
                  title="Crear nueva comanda"
                >
                  <RotateCcw class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <!-- Delivery / Customer Metadata Banner (when viewing an existing Delivery/Takeaway order) -->
            <div
              v-if="currentActiveOrder && (currentActiveOrder.order_type === 'delivery' || currentActiveOrder.order_type === 'takeaway')"
              class="mb-4 p-3 rounded-xl bg-orange-50/70 dark:bg-slate-950/90 border border-orange-200 dark:border-slate-800 space-y-2 text-xs"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold text-orange-600 dark:text-orange-400 uppercase text-[10px] tracking-wider">
                  {{ currentActiveOrder.order_type === 'delivery' ? '🛵 Pedido a Domicilio (Delivery)' : '🛍️ Pedido Para Recoger (Takeaway)' }}
                </span>
                <Tag :value="getOrderStatusInfo(currentActiveOrder).label" :severity="getOrderStatusInfo(currentActiveOrder).severity" class="text-[10px]" />
              </div>

              <div v-if="currentActiveOrder.customer" class="text-slate-700 dark:text-slate-200 font-medium flex items-center justify-between">
                <span>👤 {{ currentActiveOrder.customer.name }}</span>
                <span v-if="currentActiveOrder.customer.phone" class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
                  <Phone class="w-3 h-3" /> {{ currentActiveOrder.customer.phone }}
                </span>
              </div>

              <div v-if="currentActiveOrder.delivery_address" class="text-slate-600 dark:text-slate-300 flex items-start gap-1.5">
                <MapPin class="w-3.5 h-3.5 text-orange-500 shrink-0 mt-0.5" />
                <div class="flex-1">
                  <span>{{ currentActiveOrder.delivery_address }}</span>
                  <a
                    v-if="currentActiveOrder.delivery_lat && currentActiveOrder.delivery_lng"
                    :href="`https://www.google.com/maps/dir/?api=1&destination=${currentActiveOrder.delivery_lat},${currentActiveOrder.delivery_lng}`"
                    target="_blank"
                    class="block text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline mt-0.5"
                  >
                    📍 Abrir ubicación GPS en Google Maps
                  </a>
                </div>
              </div>

              <div v-if="currentActiveOrder.delivery_pin" class="flex items-center justify-between pt-1 border-t border-orange-200/60 dark:border-slate-800">
                <span class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1">
                  <KeyRound class="w-3.5 h-3.5 text-amber-500" /> PIN de Confirmación:
                </span>
                <span class="font-mono font-black text-xs px-2 py-0.5 rounded bg-amber-500/20 text-amber-700 dark:text-amber-300">
                  {{ currentActiveOrder.delivery_pin }}
                </span>
              </div>
            </div>

            <!-- Customer Picker & Order Type (if new order) -->
            <div v-if="!currentActiveOrder" class="space-y-3 mb-4">
              <div>
                <label class="block text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase mb-1">Tipo de Orden</label>
                <div class="grid grid-cols-3 gap-1.5">
                  <button
                    type="button"
                    @click="orderType = 'dine_in'"
                    :class="[
                      'py-1.5 px-2 rounded-lg text-[11px] font-bold border transition-all cursor-pointer',
                      orderType === 'dine_in'
                        ? 'bg-orange-600 text-white border-orange-600'
                        : 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
                    ]"
                  >
                    🍽️ En Salón
                  </button>
                  <button
                    type="button"
                    @click="orderType = 'takeaway'"
                    :class="[
                      'py-1.5 px-2 rounded-lg text-[11px] font-bold border transition-all cursor-pointer',
                      orderType === 'takeaway'
                        ? 'bg-orange-600 text-white border-orange-600'
                        : 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
                    ]"
                  >
                    🛍️ Llevar
                  </button>
                  <button
                    type="button"
                    @click="orderType = 'delivery'"
                    :class="[
                      'py-1.5 px-2 rounded-lg text-[11px] font-bold border transition-all cursor-pointer',
                      orderType === 'delivery'
                        ? 'bg-orange-600 text-white border-orange-600'
                        : 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
                    ]"
                  >
                    🛵 Delivery
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase mb-1">Cliente (Opcional)</label>
                <Select
                  v-model="selectedCustomerId"
                  :options="customers"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Cliente Ocasional / General"
                  class="w-full text-xs"
                />
              </div>

              <div v-if="orderType === 'delivery'">
                <label class="block text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase mb-1">Dirección de Entrega *</label>
                <input
                  v-model="deliveryAddress"
                  type="text"
                  placeholder="Calle, número, referencia..."
                  class="w-full bg-slate-50 border border-slate-300 text-slate-800 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-orange-500"
                />
              </div>
            </div>

            <!-- EXISTING ITEMS (if table/order already has an active order) -->
            <div v-if="currentActiveOrder && currentActiveOrder.items" class="mb-4">
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Platillos de la Orden</p>
              <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                <div
                  v-for="item in currentActiveOrder.items"
                  :key="item.id"
                  class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 dark:bg-slate-950/80 dark:border-slate-800 flex items-center justify-between text-xs"
                >
                  <div>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ item.quantity }}x {{ item.dish?.name }}</span>
                    <div v-if="item.notes" class="text-[10px] text-amber-600 dark:text-amber-400 italic">"{{ item.notes }}"</div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-slate-700 dark:text-slate-300">${{ parseFloat(item.subtotal).toFixed(2) }}</div>
                    <Tag
                      :value="item.status"
                      :severity="item.status === 'served' ? 'success' : item.status === 'ready' ? 'info' : 'warn'"
                      class="text-[9px]"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- NEW ITEMS TO ADD / CART ITEMS -->
            <div>
              <p class="text-[10px] font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider mb-2">
                {{ currentActiveOrder ? '+ Platillos Nuevos por Agregar' : 'Detalle de la Comanda' }}
              </p>

              <div v-if="cartItems.length > 0" class="space-y-2 max-h-56 overflow-y-auto pr-1">
                <div
                  v-for="(item, idx) in cartItems"
                  :key="idx"
                  class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 dark:bg-slate-800/70 dark:border-slate-700/60 flex flex-col gap-1.5 text-xs"
                >
                  <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ item.name }}</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">${{ (item.price * item.quantity).toFixed(2) }}</span>
                  </div>

                  <div class="flex items-center justify-between gap-2">
                    <!-- Note input -->
                    <input
                      v-model="item.notes"
                      type="text"
                      placeholder="Nota (ej. sin cebolla, 3/4)..."
                      class="flex-1 bg-white border border-slate-300 text-slate-800 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300 rounded px-2 py-0.5 text-[10px] focus:outline-none"
                    />

                    <!-- Stepper -->
                    <div class="flex items-center gap-1 bg-white border border-slate-300 dark:bg-slate-900 dark:border-slate-700 rounded-lg p-0.5">
                      <button @click="updateCartItemQty(idx, -1)" class="p-1 text-slate-600 dark:text-slate-300 hover:text-orange-500 cursor-pointer">
                        <Minus class="w-3 h-3" />
                      </button>
                      <span class="font-bold px-1.5 text-slate-800 dark:text-slate-200">{{ item.quantity }}</span>
                      <button @click="updateCartItemQty(idx, 1)" class="p-1 text-slate-600 dark:text-slate-300 hover:text-orange-500 cursor-pointer">
                        <Plus class="w-3 h-3" />
                      </button>
                    </div>

                    <button @click="removeFromCart(idx)" class="text-red-500 hover:text-red-600 dark:text-red-400 p-1 cursor-pointer">
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </div>

              <div v-else-if="!currentActiveOrder" class="py-8 text-center text-xs text-slate-400 italic">
                Selecciona platillos del menú para armar la comanda.
              </div>
            </div>
          </div>

          <!-- Bottom: Totals & Action Buttons -->
          <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-3 mt-4">
            <div class="space-y-1 text-xs">
              <div class="flex justify-between text-slate-500 dark:text-slate-400">
                <span>Subtotal:</span>
                <span>${{ ((currentActiveOrder ? parseFloat(currentActiveOrder.subtotal) : 0) + cartSubtotal).toFixed(2) }}</span>
              </div>
              <div v-if="currentActiveOrder && parseFloat(currentActiveOrder.delivery_fee) > 0" class="flex justify-between text-slate-500 dark:text-slate-400">
                <span>Costo de Envío:</span>
                <span>${{ parseFloat(currentActiveOrder.delivery_fee).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between font-bold text-base text-slate-800 dark:text-slate-100 pt-1 border-t border-slate-200 dark:border-slate-800">
                <span>Total a Pagar:</span>
                <span class="text-emerald-600 dark:text-emerald-400 text-lg">
                  ${{ ((currentActiveOrder ? parseFloat(currentActiveOrder.total_amount) : 0) + cartSubtotal).toFixed(2) }}
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-2">
              <!-- If New Order: Send to Kitchen Button -->
              <Button
                v-if="!currentActiveOrder"
                label="Enviar Comanda a Cocina"
                icon="pi pi-send"
                class="w-full bg-orange-600 hover:bg-orange-500 border-none font-bold text-sm py-2.5 text-white"
                @click="submitNewOrder"
                :disabled="cartItems.length === 0"
              />

              <!-- If Existing Order: Add new items, Dispatch/Deliver, or Pay Ticket -->
              <div v-else class="space-y-2">
                <Button
                  v-if="cartItems.length > 0"
                  label="Agregar Platillos a Comanda"
                  icon="pi pi-plus"
                  class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 border-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 dark:border-slate-700 text-xs py-2"
                  @click="addItemsToExistingOrder"
                />

                <!-- Delivery Dispatch / Deliver controls -->
                <div v-if="currentActiveOrder.order_type === 'delivery' && !currentActiveOrder.delivered_at" class="grid grid-cols-2 gap-2">
                  <button
                    v-if="!currentActiveOrder.dispatched_at"
                    type="button"
                    @click="dispatchDeliveryOrder(currentActiveOrder)"
                    class="py-2 px-3 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
                  >
                    <Truck class="w-3.5 h-3.5" /> Despachar Envío
                  </button>
                  <button
                    type="button"
                    @click="deliverOrder(currentActiveOrder)"
                    class="py-2 px-3 bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
                    :class="!currentActiveOrder.dispatched_at ? '' : 'col-span-2'"
                  >
                    <CheckCircle2 class="w-3.5 h-3.5" /> Marcar Entregado
                  </button>
                </div>

                <Button
                  v-if="currentActiveOrder.status !== 'paid'"
                  :label="currentActiveOrder.table ? 'Cobrar Cuenta & Liberar Mesa' : 'Cobrar Pedido'"
                  icon="pi pi-dollar"
                  class="w-full bg-emerald-600 hover:bg-emerald-500 border-none font-bold text-sm py-2.5 text-white"
                  @click="openPaymentModal(currentActiveOrder)"
                />

                <div class="grid grid-cols-2 gap-2">
                  <a
                    :href="`/pos/orders/${currentActiveOrder.id}/ticket`"
                    target="_blank"
                    class="py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 dark:border-slate-700 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-colors"
                  >
                    <Printer class="w-3.5 h-3.5" />
                    <span>Ticket PDF</span>
                  </a>

                  <Button
                    v-if="currentActiveOrder.status !== 'paid' && currentActiveOrder.status !== 'cancelled'"
                    label="Cancelar"
                    severity="danger"
                    text
                    class="text-red-600 dark:text-red-400 hover:text-red-500 text-xs border border-red-500/20"
                    @click="cancelOrder(currentActiveOrder)"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Cobro y Cierre de Mesa -->
    <Dialog
      v-model:visible="paymentModalOpen"
      header="Cobro de Comanda y Cierre de Cuenta"
      modal
      class="w-full max-w-md"
    >
      <div v-if="currentActiveOrder" class="space-y-4 pt-2">
        <div class="p-4 bg-slate-100 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 text-center">
          <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Total a Cobrar</p>
          <h2 class="text-3xl font-black text-emerald-600 dark:text-emerald-400">${{ parseFloat(currentActiveOrder.total_amount).toFixed(2) }}</h2>
          <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
            {{ currentActiveOrder.table?.table_number || (currentActiveOrder.order_type === 'delivery' ? 'Envío a Domicilio' : 'Para Llevar') }}
          </p>
        </div>

        <form @submit.prevent="submitPayment" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Método de Pago *</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                type="button"
                @click="paymentForm.payment_method = 'cash'"
                :class="[
                  'p-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 transition-all cursor-pointer',
                  paymentForm.payment_method === 'cash'
                    ? 'bg-emerald-500/15 border-emerald-500 text-emerald-700 dark:text-emerald-300'
                    : 'bg-slate-50 border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400'
                ]"
              >
                <Banknote class="w-5 h-5" /> Efectivo
              </button>
              <button
                type="button"
                @click="paymentForm.payment_method = 'card'"
                :class="[
                  'p-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 transition-all cursor-pointer',
                  paymentForm.payment_method === 'card'
                    ? 'bg-blue-500/15 border-blue-500 text-blue-700 dark:text-blue-300'
                    : 'bg-slate-50 border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400'
                ]"
              >
                <CreditCard class="w-5 h-5" /> Tarjeta
              </button>
              <button
                type="button"
                @click="paymentForm.payment_method = 'transfer'"
                :class="[
                  'p-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 transition-all cursor-pointer',
                  paymentForm.payment_method === 'transfer'
                    ? 'bg-purple-500/15 border-purple-500 text-purple-700 dark:text-purple-300'
                    : 'bg-slate-50 border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400'
                ]"
              >
                <Send class="w-5 h-5" /> Transferencia
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Monto Recibido ($)</label>
            <InputNumber
              v-model="paymentForm.amount_paid"
              :min="0.01"
              :minFractionDigits="2"
              class="w-full"
              required
            />
            <div v-if="paymentForm.payment_method === 'cash' && paymentForm.amount_paid > parseFloat(currentActiveOrder.total_amount)" class="mt-2 text-xs p-2 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200">
              Cambio a Entregar: <strong class="text-emerald-600 dark:text-emerald-400 font-bold">${{ (paymentForm.amount_paid - parseFloat(currentActiveOrder.total_amount)).toFixed(2) }}</strong>
            </div>
          </div>

          <div v-if="paymentForm.payment_method !== 'cash'">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Referencia / No. de Autorización</label>
            <InputText
              v-model="paymentForm.transaction_ref"
              class="w-full text-xs"
              placeholder="Ej: AUT-984321"
            />
          </div>

          <div class="p-2.5 rounded-lg bg-orange-500/10 border border-orange-500/20 text-[11px] text-orange-700 dark:text-orange-300">
            ℹ️ Al confirmar el pago, la cuenta quedará saldada y <strong>los insumos de la receta se descontarán automáticamente del inventario</strong>.
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
            <Button type="button" label="Cancelar" severity="secondary" text @click="paymentModalOpen = false" />
            <Button
              type="submit"
              label="Confirmar Cobro"
              class="bg-emerald-600 hover:bg-emerald-500 border-none font-bold text-white"
              :loading="paymentForm.processing"
            />
          </div>
        </form>
      </div>
    </Dialog>
  </AdminLayout>
</template>
