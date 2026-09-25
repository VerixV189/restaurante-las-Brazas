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
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import {
  ShoppingCart,
  Armchair,
  Flame,
  Plus,
  Minus,
  Trash2,
  CheckCircle2,
  DollarSign,
  Clock,
  User,
  Utensils,
  Receipt,
  CreditCard,
  Banknote,
  Send,
  XCircle,
  Search,
  Printer,
  FileDown,
} from 'lucide-vue-next';

const props = defineProps({
  areas: Array,
  categories: Array,
  dishes: Array,
  customers: Array,
  activeOrders: Array,
});

const toast = useToast();
const confirm = useConfirm();

// State
const selectedAreaId = ref('all');
const selectedCategoryId = ref('all');
const searchQuery = ref('');

const selectedTable = ref(null);
const currentActiveOrder = ref(null);
const orderDrawerOpen = ref(false);
const paymentModalOpen = ref(false);

// Local Order Cart for taking a new order
const cartItems = ref([]);
const selectedCustomerId = ref(null);
const orderType = ref('dine_in');

// Payment Form
const paymentForm = useForm({
  payment_method: 'cash',
  amount_paid: 0,
  transaction_ref: '',
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
  // Check if table has an active order
  const activeOrder = table.orders && table.orders.length > 0 ? table.orders[0] : null;

  if (activeOrder) {
    currentActiveOrder.value = activeOrder;
    cartItems.value = [];
    orderDrawerOpen.value = true;
  } else {
    // Open table for new order
    currentActiveOrder.value = null;
    cartItems.value = [];
    selectedCustomerId.value = null;
    orderType.value = 'dine_in';
    orderDrawerOpen.value = true;
  }
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

  router.post('/pos/orders', {
    table_id: selectedTable.value?.id || null,
    customer_id: selectedCustomerId.value,
    order_type: orderType.value,
    items: cartItems.value.map(i => ({
      dish_id: i.dish_id,
      quantity: i.quantity,
      notes: i.notes,
    })),
  }, {
    onSuccess: () => {
      orderDrawerOpen.value = false;
      cartItems.value = [];
      toast.add({ severity: 'success', summary: 'Comanda Enviada', detail: 'Comanda enviada a cocina', life: 3000 });
    }
  });
}

// Add items to existing active order
function addItemsToExistingOrder() {
  if (cartItems.value.length === 0) return;

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
      orderDrawerOpen.value = false;
      toast.add({
        severity: 'success',
        summary: 'Cuenta Pagada',
        detail: `Comanda #${currentActiveOrder.value.id} cobrada. Mesa liberada e inventario descontado.`,
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
          orderDrawerOpen.value = false;
          toast.add({ severity: 'info', summary: 'Comanda Cancelada', detail: 'La comanda ha sido cancelada', life: 3000 });
        }
      });
    }
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Punto de Venta (POS) & Comandas en Vivo</template>

    <div class="space-y-6">
      <!-- Top Floor Map & Active Orders View -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left: Table Floor & Dish Catalog (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">

          <!-- 1. Plano de Mesas en Vivo -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <Armchair class="w-5 h-5 text-orange-500" />
                <h3 class="text-base font-bold text-slate-100">Mesas en Salón</h3>
              </div>
              <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5 text-slate-300">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Libre
                </span>
                <span class="flex items-center gap-1.5 text-slate-300">
                  <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span> Ocupada
                </span>
                <span class="flex items-center gap-1.5 text-slate-300">
                  <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Reservada
                </span>
              </div>
            </div>

            <!-- Mesas organizadas por áreas -->
            <div class="space-y-4">
              <div v-for="area in areas" :key="area.id" class="border-t border-slate-800/80 pt-3">
                <p class="text-xs font-semibold text-slate-400 mb-2 uppercase tracking-wide">{{ area.name }}</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                  <button
                    v-for="table in area.tables"
                    :key="table.id"
                    @click="onTableClick(table)"
                    :class="[
                      'p-3.5 rounded-xl border text-left transition-all relative overflow-hidden flex flex-col justify-between h-24 group',
                      table.status === 'occupied'
                        ? 'bg-red-500/10 border-red-500/40 hover:border-red-400 hover:bg-red-500/15'
                        : table.status === 'reserved'
                        ? 'bg-amber-500/10 border-amber-500/40 hover:border-amber-400'
                        : 'bg-slate-800/60 border-slate-700/60 hover:border-orange-500/60 hover:bg-slate-800'
                    ]"
                  >
                    <div class="flex items-center justify-between">
                      <span class="font-bold text-sm text-slate-100 group-hover:text-orange-300">{{ table.table_number }}</span>
                      <span
                        class="w-2 h-2 rounded-full"
                        :class="table.status === 'occupied' ? 'bg-red-500 animate-pulse' : table.status === 'reserved' ? 'bg-amber-500' : 'bg-emerald-500'"
                      ></span>
                    </div>

                    <div class="text-[11px] text-slate-400">
                      <div v-if="table.status === 'occupied' && table.orders && table.orders.length > 0">
                        <strong class="text-red-400">Orden #{{ table.orders[0].id }}</strong> · ${{ parseFloat(table.orders[0].total_amount).toFixed(2) }}
                      </div>
                      <div v-else>
                        {{ table.capacity }} personas · <span class="text-emerald-400 font-medium">Libre</span>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Catálogo Rápido de Platillos -->
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
              <div class="flex items-center gap-2">
                <Flame class="w-5 h-5 text-orange-500" />
                <h3 class="text-base font-bold text-slate-100">Menú & Platillos</h3>
              </div>

              <!-- Search Bar -->
              <div class="relative w-full sm:w-64">
                <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Buscar platillo..."
                  class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-9 pr-3 py-1.5 text-xs text-slate-100 focus:outline-none focus:border-orange-500"
                />
              </div>
            </div>

            <!-- Category Pills -->
            <div class="flex gap-2 overflow-x-auto pb-2 mb-4">
              <button
                @click="selectedCategoryId = 'all'"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors',
                  selectedCategoryId === 'all'
                    ? 'bg-orange-600 text-white'
                    : 'bg-slate-800 text-slate-400 hover:text-slate-200'
                ]"
              >
                Todos
              </button>
              <button
                v-for="cat in categories"
                :key="cat.id"
                @click="selectedCategoryId = cat.id"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors',
                  selectedCategoryId === cat.id
                    ? 'bg-orange-600 text-white'
                    : 'bg-slate-800 text-slate-400 hover:text-slate-200'
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
                class="bg-slate-950/70 border border-slate-800 rounded-xl p-3 flex flex-col justify-between hover:border-slate-700 transition-all group"
              >
                <div>
                  <div class="flex items-start justify-between gap-2 mb-1">
                    <h4 class="font-bold text-xs text-slate-200 group-hover:text-orange-400 leading-tight">{{ dish.name }}</h4>
                    <span class="font-bold text-xs text-emerald-400 shrink-0">${{ parseFloat(dish.price).toFixed(2) }}</span>
                  </div>
                  <p class="text-[10px] text-slate-400 line-clamp-2 mb-2">{{ dish.description }}</p>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-800/60">
                  <span class="text-[10px] text-slate-500 flex items-center gap-1">
                    <Clock class="w-3 h-3" /> {{ dish.prep_time_minutes }}m
                  </span>
                  <button
                    @click="addToCart(dish)"
                    class="px-2.5 py-1 bg-orange-600 hover:bg-orange-500 text-white text-[11px] font-bold rounded-lg flex items-center gap-1 transition-colors"
                  >
                    <Plus class="w-3 h-3" /> Agregar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Active Ticket / Cart Sidebar (4 Cols) -->
        <div class="lg:col-span-4 bg-slate-900 border border-slate-800 rounded-2xl p-5 flex flex-col justify-between">
          <div>
            <!-- Ticket Header -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
              <div class="flex items-center gap-2">
                <Receipt class="w-5 h-5 text-orange-500" />
                <h3 class="text-base font-bold text-slate-100">
                  {{ selectedTable ? selectedTable.table_number : 'Comanda en Curso' }}
                </h3>
              </div>
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
            </div>

            <!-- Customer Picker & Order Type (if new order) -->
            <div v-if="!currentActiveOrder" class="space-y-2.5 mb-4">
              <div>
                <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-1">Cliente (Opcional)</label>
                <Select
                  v-model="selectedCustomerId"
                  :options="customers"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Cliente Ocasional / General"
                  class="w-full bg-slate-800 border-slate-700 text-xs"
                />
              </div>
            </div>

            <!-- EXISTING ITEMS (if table already has an active order) -->
            <div v-if="currentActiveOrder && currentActiveOrder.items" class="mb-4">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Platillos en Preparación / Servidos</p>
              <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                <div
                  v-for="item in currentActiveOrder.items"
                  :key="item.id"
                  class="p-2 rounded-lg bg-slate-950/80 border border-slate-800 flex items-center justify-between text-xs"
                >
                  <div>
                    <span class="font-semibold text-slate-200">{{ item.quantity }}x {{ item.dish?.name }}</span>
                    <div v-if="item.notes" class="text-[10px] text-amber-400 italic">"{{ item.notes }}"</div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-slate-300">${{ parseFloat(item.subtotal).toFixed(2) }}</div>
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
              <p class="text-[10px] font-bold text-orange-400 uppercase tracking-wider mb-2">
                {{ currentActiveOrder ? '+ Platillos Nuevos por Agregar' : 'Detalle de la Comanda' }}
              </p>

              <div v-if="cartItems.length > 0" class="space-y-2 max-h-56 overflow-y-auto pr-1">
                <div
                  v-for="(item, idx) in cartItems"
                  :key="idx"
                  class="p-2.5 rounded-xl bg-slate-800/70 border border-slate-700/60 flex flex-col gap-1.5 text-xs"
                >
                  <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-100">{{ item.name }}</span>
                    <span class="font-bold text-emerald-400">${{ (item.price * item.quantity).toFixed(2) }}</span>
                  </div>

                  <div class="flex items-center justify-between gap-2">
                    <!-- Note input -->
                    <input
                      v-model="item.notes"
                      type="text"
                      placeholder="Nota (ej. sin cebolla, 3/4)..."
                      class="flex-1 bg-slate-900 border border-slate-700 rounded px-2 py-0.5 text-[10px] text-slate-300 focus:outline-none"
                    />

                    <!-- Stepper -->
                    <div class="flex items-center gap-1 bg-slate-900 rounded-lg p-0.5 border border-slate-700">
                      <button @click="updateCartItemQty(idx, -1)" class="p-1 hover:text-orange-400">
                        <Minus class="w-3 h-3" />
                      </button>
                      <span class="font-bold px-1.5 text-slate-200">{{ item.quantity }}</span>
                      <button @click="updateCartItemQty(idx, 1)" class="p-1 hover:text-orange-400">
                        <Plus class="w-3 h-3" />
                      </button>
                    </div>

                    <button @click="removeFromCart(idx)" class="text-red-400 hover:text-red-300 p-1">
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              </div>

              <div v-else-if="!currentActiveOrder" class="py-8 text-center text-xs text-slate-500 italic">
                Selecciona platillos del menú para armar la comanda.
              </div>
            </div>
          </div>

          <!-- Bottom: Totals & Action Buttons -->
          <div class="pt-4 border-t border-slate-800 space-y-3 mt-4">
            <div class="space-y-1 text-xs">
              <div class="flex justify-between text-slate-400">
                <span>Subtotal:</span>
                <span>${{ ((currentActiveOrder ? parseFloat(currentActiveOrder.subtotal) : 0) + cartSubtotal).toFixed(2) }}</span>
              </div>
              <div class="flex justify-between font-bold text-base text-slate-100 pt-1 border-t border-slate-800">
                <span>Total a Pagar:</span>
                <span class="text-emerald-400 text-lg">
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
                class="w-full bg-orange-600 hover:bg-orange-500 border-none font-bold text-sm py-2.5"
                @click="submitNewOrder"
                :disabled="cartItems.length === 0"
              />

              <!-- If Existing Order: Add new items or Pay Ticket -->
              <div v-else class="space-y-2">
                <Button
                  v-if="cartItems.length > 0"
                  label="Agregar Platillos a Comanda"
                  icon="pi pi-plus"
                  class="w-full bg-slate-800 hover:bg-slate-700 text-slate-200 border-slate-700 text-xs py-2"
                  @click="addItemsToExistingOrder"
                />

                <Button
                  label="Cobrar Cuenta & Liberar Mesa"
                  icon="pi pi-dollar"
                  class="w-full bg-emerald-600 hover:bg-emerald-500 border-none font-bold text-sm py-2.5 text-white"
                  @click="openPaymentModal(currentActiveOrder)"
                />

                <div class="grid grid-cols-2 gap-2">
                  <a
                    :href="`/pos/orders/${currentActiveOrder.id}/ticket`"
                    target="_blank"
                    class="py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-colors"
                  >
                    <Printer class="w-3.5 h-3.5" />
                    <span>Ticket PDF</span>
                  </a>

                  <Button
                    label="Cancelar"
                    severity="danger"
                    text
                    class="text-red-400 hover:text-red-300 text-xs border border-red-500/20"
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
        <div class="p-4 bg-slate-800/60 rounded-xl border border-slate-700 text-center">
          <p class="text-xs text-slate-400 uppercase font-semibold">Total a Cobrar</p>
          <h2 class="text-3xl font-black text-emerald-400">${{ parseFloat(currentActiveOrder.total_amount).toFixed(2) }}</h2>
          <p class="text-xs text-slate-300 mt-1">Mesa: {{ currentActiveOrder.table?.table_number || 'Para Llevar' }}</p>
        </div>

        <form @submit.prevent="submitPayment" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Método de Pago *</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                type="button"
                @click="paymentForm.payment_method = 'cash'"
                :class="[
                  'p-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 transition-all',
                  paymentForm.payment_method === 'cash'
                    ? 'bg-emerald-600/20 border-emerald-500 text-emerald-300'
                    : 'bg-slate-800 border-slate-700 text-slate-400'
                ]"
              >
                <Banknote class="w-5 h-5" /> Efectivo
              </button>
              <button
                type="button"
                @click="paymentForm.payment_method = 'card'"
                :class="[
                  'p-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 transition-all',
                  paymentForm.payment_method === 'card'
                    ? 'bg-blue-600/20 border-blue-500 text-blue-300'
                    : 'bg-slate-800 border-slate-700 text-slate-400'
                ]"
              >
                <CreditCard class="w-5 h-5" /> Tarjeta
              </button>
              <button
                type="button"
                @click="paymentForm.payment_method = 'transfer'"
                :class="[
                  'p-3 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 transition-all',
                  paymentForm.payment_method === 'transfer'
                    ? 'bg-purple-600/20 border-purple-500 text-purple-300'
                    : 'bg-slate-800 border-slate-700 text-slate-400'
                ]"
              >
                <Send class="w-5 h-5" /> Transferencia
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Monto Recibido ($)</label>
            <InputNumber
              v-model="paymentForm.amount_paid"
              :min="0.01"
              :minFractionDigits="2"
              class="w-full"
              required
            />
            <div v-if="paymentForm.payment_method === 'cash' && paymentForm.amount_paid > parseFloat(currentActiveOrder.total_amount)" class="mt-2 text-xs p-2 rounded bg-slate-800 text-slate-200">
              Cambio a Entregar: <strong class="text-emerald-400 font-bold">${{ (paymentForm.amount_paid - parseFloat(currentActiveOrder.total_amount)).toFixed(2) }}</strong>
            </div>
          </div>

          <div v-if="paymentForm.payment_method !== 'cash'">
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Referencia / No. de Autorización</label>
            <InputText
              v-model="paymentForm.transaction_ref"
              class="w-full bg-slate-800 border-slate-700 text-slate-100 text-xs"
              placeholder="Ej: AUT-984321"
            />
          </div>

          <div class="p-2.5 rounded-lg bg-orange-500/10 border border-orange-500/20 text-[11px] text-orange-300">
            ℹ️ Al confirmar el pago, la mesa se liberará y <strong>los insumos de la receta se descontarán automáticamente del inventario</strong>.
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
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
