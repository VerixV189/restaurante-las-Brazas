<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AdminLayout from '../../layouts/AdminLayout.vue';
import { useToast } from 'primevue/usetoast';
import {
  Flame,
  ShoppingCart,
  Truck,
  Sparkles,
  CalendarCheck,
  UserCircle,
  Search,
  Plus,
  Minus,
  Trash2,
  MapPin,
  Clock,
  CheckCircle2,
  XCircle,
  FileText,
  RotateCcw,
  Tag as TagIcon,
  CreditCard,
  QrCode,
  Banknote,
  Star,
  Send,
  Mic,
  MicOff,
  Bot,
  UtensilsCrossed,
  ShieldCheck,
  Store,
  Armchair,
  ChevronRight,
  SlidersHorizontal,
} from 'lucide-vue-next';

const props = defineProps({
  categories: { type: Array, default: () => [] },
  dishes: { type: Array, default: () => [] },
  orders: { type: Array, default: () => [] },
  addresses: { type: Array, default: () => [] },
  coupons: { type: Array, default: () => [] },
  reservations: { type: Array, default: () => [] },
  areas: { type: Array, default: () => [] },
  tables: { type: Array, default: () => [] },
  userProfile: { type: Object, default: () => ({}) },
  initialTab: { type: String, default: 'menu' },
});

const toast = useToast();
const page = usePage();

// ─────────────────────────────────────────────────────────────────────────────
// NAVEGACIÓN POR PESTAÑAS (Sincronizada con Sidebar ?tab=...)
// ─────────────────────────────────────────────────────────────────────────────
const activeTab = ref(props.initialTab || 'menu');

watch(
  () => page.url,
  (newUrl) => {
    const match = newUrl.match(/[?&]tab=([^&]+)/);
    if (match && match[1]) {
      activeTab.value = match[1];
    } else if (newUrl === '/dashboard') {
      activeTab.value = 'menu';
    }
  },
  { immediate: true }
);

function switchTab(tab) {
  activeTab.value = tab;
  window.history.replaceState({}, '', `/dashboard?tab=${tab}`);
}

const tabs = [
  { key: 'menu', label: 'Marketplace / Menú', icon: Flame },
  { key: 'cart', label: 'Mi Carrito & Envío', icon: ShoppingCart },
  { key: 'orders', label: 'Mis Pedidos', icon: Truck },
  { key: 'promos', label: 'Promos & Puntos', icon: TagIcon },
  { key: 'reservations', label: 'Reservas', icon: CalendarCheck },
  { key: 'ai', label: 'Agente IA', icon: Sparkles },
  { key: 'profile', label: 'Mi Perfil & Direcciones', icon: UserCircle },
];

// ─────────────────────────────────────────────────────────────────────────────
// ESTADO REACTIVO LOCAL (Pedidos, Direcciones, Reservas)
// ─────────────────────────────────────────────────────────────────────────────
const localOrders = ref([...props.orders]);
const localAddresses = ref([...props.addresses]);
const localReservations = ref([...props.reservations]);
const loyaltyPoints = ref(props.userProfile?.loyalty_points || 100);

// Preferencias dietéticas (RF-25)
const dietOptions = [
  'Amante de Cortes a la Brasa',
  'Bajo en Carbohidratos / Keto',
  'Sin Picante',
  'Sin Lactosa',
  'Sin Cebolla',
];
const selectedPrefs = ref(['Amante de Cortes a la Brasa']);

function toggleDietPref(pref) {
  const idx = selectedPrefs.value.indexOf(pref);
  if (idx >= 0) {
    selectedPrefs.value.splice(idx, 1);
  } else {
    selectedPrefs.value.push(pref);
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// MARKETPLACE & MENÚ DIGITAL (RF-01, RF-02, RF-25)
// ─────────────────────────────────────────────────────────────────────────────
const selectedCategoryId = ref(null);
const searchQuery = ref('');
const customizingDish = ref(null);
const customCookingTerm = ref('Término Medio (A punto)');
const customNotes = ref('');
const customQuantity = ref(1);

const filteredDishes = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  return props.dishes.filter((d) => {
    const matchCat = selectedCategoryId.value === null || d.category_id === selectedCategoryId.value;
    const matchSearch =
      !q ||
      d.name.toLowerCase().includes(q) ||
      (d.description && d.description.toLowerCase().includes(q)) ||
      (d.category_name && d.category_name.toLowerCase().includes(q));
    return matchCat && matchSearch;
  });
});

const recommendedDish = computed(() => {
  if (!props.dishes.length) return null;
  const prefersMeat = selectedPrefs.value.some((p) => p.toLowerCase().includes('corte'));
  if (prefersMeat) {
    const meat = props.dishes.find((d) => d.is_meat);
    if (meat) return meat;
  }
  return props.dishes[0];
});

function openCustomizeModal(dish) {
  customizingDish.value = dish;
  customCookingTerm.value = dish.is_meat ? 'Término Medio (A punto)' : '';
  customNotes.value = '';
  customQuantity.value = 1;
}

function confirmCustomAdd() {
  if (!customizingDish.value) return;
  const notesArr = [];
  if (customizingDish.value.is_meat && customCookingTerm.value) {
    notesArr.push(customCookingTerm.value);
  }
  if (customNotes.value.trim()) {
    notesArr.push(customNotes.value.trim());
  }
  addToCart(customizingDish.value, customQuantity.value, notesArr.join(' • '));
  customizingDish.value = null;
}

// ─────────────────────────────────────────────────────────────────────────────
// CARRITO, CUPONES & CHECKOUT (RF-03, RF-04, RF-07, RF-10, RF-24)
// ─────────────────────────────────────────────────────────────────────────────
const cartItems = ref([]);
const orderType = ref('delivery'); // 'delivery' | 'takeaway' | 'dine_in'
const selectedAddressId = ref(localAddresses.value[0]?.id || null);
const manualAddress = ref('');
const selectedTableId = ref(props.tables[0]?.id || 1);
const couponInput = ref('');
const appliedCoupon = ref(null); // { code, type, discount, free_shipping }
const paymentMethod = ref('card'); // 'card' | 'transfer' | 'cash'
const requestInvoice = ref(false);
const invoiceName = ref('');
const invoiceTaxId = ref('');
const orderNotes = ref('');
const submittingOrder = ref(false);

// Datos simulados de pasarela
const cardNumber = ref('4532 •••• •••• 8891');
const cardHolder = ref((props.userProfile?.name || 'CLIENTE LAS BRAZAS').toUpperCase());
const cardExpiry = ref('09/28');
const cardCvv = ref('842');
const transferRef = ref('QR-BRAZAS-' + Math.floor(10000 + Math.random() * 90000));

function addToCart(dish, qty = 1, notes = null) {
  const defaultNotes = notes !== null ? notes : dish.is_meat ? 'Término Medio (A punto)' : '';
  const existing = cartItems.value.find(
    (item) => item.dish.id === dish.id && (item.notes || '') === (defaultNotes || '')
  );
  if (existing) {
    existing.quantity += qty;
  } else {
    cartItems.value.push({
      dish,
      quantity: qty,
      notes: defaultNotes,
    });
  }
  saveCartToStorage();
  toast.add({
    severity: 'success',
    summary: 'Agregado al carrito',
    detail: `${qty}x ${dish.name}`,
    life: 2200,
  });
}

function updateQty(index, delta) {
  const item = cartItems.value[index];
  if (!item) return;
  item.quantity += delta;
  if (item.quantity <= 0) {
    cartItems.value.splice(index, 1);
  }
  saveCartToStorage();
}

function removeCartItem(index) {
  cartItems.value.splice(index, 1);
  saveCartToStorage();
}

function clearCart() {
  cartItems.value = [];
  appliedCoupon.value = null;
  couponInput.value = '';
  saveCartToStorage();
}

function saveCartToStorage() {
  try {
    localStorage.setItem('lasbrazas_client_cart', JSON.stringify(cartItems.value));
  } catch (_) {}
}

const totalItemsCount = computed(() =>
  cartItems.value.reduce((acc, item) => acc + item.quantity, 0)
);

const cartSubtotal = computed(() =>
  cartItems.value.reduce((acc, item) => acc + Number(item.dish.price) * item.quantity, 0)
);

const cartDiscount = computed(() => {
  if (!appliedCoupon.value) return 0;
  return Number(appliedCoupon.value.discount || 0);
});

const deliveryFee = computed(() => {
  if (orderType.value !== 'delivery' || cartItems.value.length === 0) return 0;
  if (appliedCoupon.value?.free_shipping || cartSubtotal.value >= 50) return 0;
  return 3.0;
});

const cartTotal = computed(() =>
  Math.max(0, cartSubtotal.value - cartDiscount.value + deliveryFee.value)
);

const estimatedEta = computed(() => {
  const maxPrep = cartItems.value.reduce(
    (max, i) => Math.max(max, Number(i.dish.prep_time_minutes || 20)),
    20
  );
  const base = maxPrep + (orderType.value === 'delivery' ? 25 : 0);
  return `${base} - ${base + 15} min`;
});

const upsellDishes = computed(() => {
  const inCartIds = new Set(cartItems.value.map((i) => i.dish.id));
  return props.dishes.filter((d) => !inCartIds.has(d.id) && !d.is_meat).slice(0, 4);
});

async function applyCouponCode(codeOverride = null) {
  const code = (codeOverride || couponInput.value || '').trim().toUpperCase();
  if (!code) return;
  couponInput.value = code;

  if (cartSubtotal.value <= 0) {
    toast.add({
      severity: 'warn',
      summary: 'Carrito vacío',
      detail: 'Agrega platillos a tu carrito antes de validar el cupón.',
      life: 3000,
    });
    return;
  }

  try {
    const res = await axios.post('/client/coupons/validate', {
      code,
      subtotal: cartSubtotal.value,
    });
    appliedCoupon.value = res.data.data;
    toast.add({
      severity: 'success',
      summary: 'Cupón aplicado',
      detail: res.data.message,
      life: 3000,
    });
  } catch (err) {
    appliedCoupon.value = null;
    toast.add({
      severity: 'error',
      summary: 'Cupón no válido',
      detail: err.response?.data?.message || 'No se pudo aplicar el cupón.',
      life: 3500,
    });
  }
}

async function submitCheckout() {
  if (cartItems.value.length === 0) return;

  if (orderType.value === 'delivery' && !selectedAddressId.value && !manualAddress.value.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Dirección requerida',
      detail: 'Selecciona o escribe una dirección de entrega para tu envío a domicilio.',
      life: 3500,
    });
    return;
  }

  if (requestInvoice.value && (!invoiceName.value.trim() || !invoiceTaxId.value.trim())) {
    toast.add({
      severity: 'warn',
      summary: 'Datos de facturación',
      detail: 'Ingresa Razón Social y NIT/CI para emitir tu factura electrónica.',
      life: 3500,
    });
    return;
  }

  submittingOrder.value = true;
  try {
    const payload = {
      items: cartItems.value.map((i) => ({
        dish_id: i.dish.id,
        quantity: i.quantity,
        notes: i.notes || null,
      })),
      order_type: orderType.value,
      table_id: orderType.value === 'dine_in' ? selectedTableId.value : null,
      address_id: orderType.value === 'delivery' ? selectedAddressId.value : null,
      delivery_address: orderType.value === 'delivery' ? manualAddress.value.trim() : null,
      coupon_code: appliedCoupon.value?.code || null,
      invoice_name: requestInvoice.value ? invoiceName.value.trim() : null,
      invoice_tax_id: requestInvoice.value ? invoiceTaxId.value.trim() : null,
      notes: orderNotes.value.trim() || null,
      payment_method: paymentMethod.value,
      transaction_ref:
        paymentMethod.value === 'transfer'
          ? transferRef.value
          : paymentMethod.value === 'card'
          ? 'STRIPE-' + Date.now()
          : 'CASH-DELIVERY',
    };

    const res = await axios.post('/client/orders', payload);
    const created = res.data.data;
    localOrders.value.unshift(created);
    loyaltyPoints.value += Math.round(created.total);
    clearCart();

    toast.add({
      severity: 'success',
      summary: '¡Pedido Enviado a Cocina!',
      detail: res.data.message,
      life: 4500,
    });

    switchTab('orders');
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error al procesar pedido',
      detail: err.response?.data?.message || 'Verifica los datos e intenta nuevamente.',
      life: 4000,
    });
  } finally {
    submittingOrder.value = false;
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// MIS PEDIDOS & TRACKING (RF-13, RF-14)
// ─────────────────────────────────────────────────────────────────────────────
const trackingStepsLabels = {
  received: 'Recibido',
  in_kitchen: 'En Cocina (KDS)',
  ready: 'Listo para Entrega',
  on_the_way: 'En Camino',
  delivered: 'Entregado',
  cancelled: 'Cancelado',
};

function getStepIndex(order, step) {
  return order.tracking?.steps?.indexOf(step) ?? 0;
}

function getCurrentStepIndex(order) {
  if (order.status === 'cancelled') return -1;
  const idx = order.tracking?.steps?.indexOf(order.tracking?.current);
  return idx >= 0 ? idx : 0;
}

function reorderPastOrder(order) {
  let added = 0;
  for (const item of order.items || []) {
    const foundDish = props.dishes.find((d) => d.id === item.dish_id);
    if (foundDish) {
      addToCart(foundDish, item.quantity, item.notes);
      added++;
    }
  }
  if (added > 0) {
    switchTab('cart');
  }
}

async function cancelPendingOrder(order) {
  try {
    const res = await axios.post(`/client/orders/${order.id}/cancel`);
    order.status = 'cancelled';
    if (order.tracking) order.tracking.current = 'cancelled';
    toast.add({
      severity: 'info',
      summary: 'Pedido cancelado',
      detail: res.data.message,
      life: 3000,
    });
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'No se pudo cancelar',
      detail: err.response?.data?.message || 'El pedido ya está en cocina.',
      life: 3500,
    });
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// DIRECCIONES DEL CLIENTE (RF-05)
// ─────────────────────────────────────────────────────────────────────────────
const newAddr = ref({
  label: 'Casa',
  street: '',
  number: '',
  floor: '',
  reference: '',
  is_default: true,
});
const savingAddress = ref(false);

async function saveNewAddress() {
  if (!newAddr.value.street.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Calle obligatoria',
      detail: 'Ingresa la avenida o calle de tu dirección.',
      life: 3000,
    });
    return;
  }
  savingAddress.value = true;
  try {
    const res = await axios.post('/client/addresses', newAddr.value);
    const created = res.data.data;
    if (created.is_default) {
      localAddresses.value.forEach((a) => (a.is_default = false));
    }
    localAddresses.value.unshift(created);
    selectedAddressId.value = created.id;
    newAddr.value = { label: 'Oficina', street: '', number: '', floor: '', reference: '', is_default: false };
    toast.add({
      severity: 'success',
      summary: 'Dirección guardada',
      detail: res.data.message,
      life: 3000,
    });
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'No se pudo guardar la dirección.',
      life: 3500,
    });
  } finally {
    savingAddress.value = false;
  }
}

async function deleteAddress(id) {
  try {
    await axios.delete(`/client/addresses/${id}`);
    localAddresses.value = localAddresses.value.filter((a) => a.id !== id);
    if (selectedAddressId.value === id) {
      selectedAddressId.value = localAddresses.value[0]?.id || null;
    }
    toast.add({ severity: 'info', summary: 'Dirección eliminada', life: 2500 });
  } catch (_) {}
}

// ─────────────────────────────────────────────────────────────────────────────
// RESERVAS DE MESAS (RF-12)
// ─────────────────────────────────────────────────────────────────────────────
const tomorrowStr = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
const resForm = ref({
  date: tomorrowStr,
  time: '20:00',
  adults: 2,
  children: 0,
  area_id: props.areas[0]?.id || null,
  special_requests: '',
});
const submittingRes = ref(false);

async function submitReservation() {
  if (!resForm.value.area_id) return;
  submittingRes.value = true;
  try {
    const res = await axios.post('/client/reservations', {
      reservation_date: `${resForm.value.date} ${resForm.value.time}`,
      adults: resForm.value.adults,
      children: resForm.value.children,
      area_id: resForm.value.area_id,
      special_requests: resForm.value.special_requests,
    });
    localReservations.value.unshift(res.data.data);
    resForm.value.special_requests = '';
    toast.add({
      severity: 'success',
      summary: 'Reserva Confirmada',
      detail: res.data.message,
      life: 4000,
    });
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'No se pudo reservar',
      detail: err.response?.data?.message || 'Revisa la fecha y hora seleccionadas.',
      life: 3500,
    });
  } finally {
    submittingRes.value = false;
  }
}

async function cancelReservation(r) {
  try {
    const res = await axios.post(`/client/reservations/${r.id}/cancel`);
    r.status = 'cancelled';
    toast.add({ severity: 'info', summary: 'Reserva cancelada', detail: res.data.message, life: 3000 });
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'No se pudo cancelar la reserva.',
      life: 3500,
    });
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// AGENTE INTELIGENTE GASTRONÓMICO (RF-27: Pedidos, Reservas, Búsqueda, Voz)
// ─────────────────────────────────────────────────────────────────────────────
const aiMessages = ref([
  {
    id: 'welcome',
    role: 'assistant',
    text:
      `🔥 ¡Hola, **${props.userProfile?.name?.split(' ')[0] || 'Comensal'}**! Soy tu **Agente Inteligente Gastronómico de Las Brazas**.\n\n` +
      `Puedo ejecutar acciones reales por ti en lenguaje natural o por voz:\n` +
      `• 🛒 **Hacer Pedidos:** *"Pídeme una Arrachera término medio y una Cerveza Artesanal"*\n` +
      `• 📅 **Crear Reservas:** *"Reserva una mesa para 4 personas mañana a las 20:00"*\n` +
      `• 🎯 **Buscar por Presupuesto o Corte:** *"¿Qué me recomiendas pedir con $350?"*\n` +
      `• 🎟️ **Cupones y Estado de Envíos:** *"¿Qué cupones tienen activos?"* o *"Estado de mi pedido"*`,
    suggestedItems: [],
    suggestedCoupons: [],
    timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
  },
]);
const aiInput = ref('');
const aiLoading = ref(false);
const aiListening = ref(false);
let speechRec = null;

const aiPrompts = [
  '🛒 Pídeme una Arrachera término medio y una Cerveza Artesanal',
  '🥩 ¿Cuáles son los cortes de carne más recomendados?',
  '🎯 ¿Qué me recomiendas pedir con un presupuesto de $350?',
  '📅 Reserva una mesa para 4 personas mañana a las 20:00',
  '🎟️ ¿Qué cupones de descuento tienen activos?',
  '📦 ¿Cuál es el estado de mis pedidos recientes?',
];

async function sendAiPrompt(preset = null) {
  const text = (preset ?? aiInput.value).trim();
  if (!text || aiLoading.value) return;
  aiInput.value = '';

  aiMessages.value.push({
    id: 'u-' + Date.now(),
    role: 'user',
    text,
    timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
  });

  aiLoading.value = true;
  try {
    const res = await axios.post('/ai/chat', {
      message: text,
      history: aiMessages.value.slice(-6).map((m) => ({ role: m.role, content: m.text })),
    });

    aiMessages.value.push({
      id: 'ai-' + Date.now(),
      role: 'assistant',
      text: res.data.reply,
      suggestedItems: res.data.suggested_items || [],
      suggestedCoupons: res.data.suggested_coupons || [],
      timestamp: res.data.timestamp || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    });

    if (res.data.created_reservation) {
      router.reload({ only: ['reservations'] });
    }
  } catch (_) {
    aiMessages.value.push({
      id: 'err-' + Date.now(),
      role: 'assistant',
      text: 'Ocurrió un problema al consultar el Agente IA. Intenta nuevamente.',
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    });
  } finally {
    aiLoading.value = false;
  }
}

function toggleAiVoice() {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SR) {
    toast.add({
      severity: 'warn',
      summary: 'Voz no soportada',
      detail: 'Usa Chrome o Edge para dictar por voz.',
      life: 3000,
    });
    return;
  }
  if (aiListening.value && speechRec) {
    speechRec.stop();
    aiListening.value = false;
    return;
  }
  speechRec = new SR();
  speechRec.lang = 'es-ES';
  speechRec.onstart = () => (aiListening.value = true);
  speechRec.onresult = (e) => {
    const transcript = e.results[0][0].transcript;
    aiListening.value = false;
    sendAiPrompt(transcript);
  };
  speechRec.onerror = () => (aiListening.value = false);
  speechRec.onend = () => (aiListening.value = false);
  speechRec.start();
}

function addAiSuggestedItems(items, goToCheckout = false) {
  for (const item of items) {
    const dishObj = props.dishes.find((d) => d.id === item.dish_id) || {
      id: item.dish_id,
      name: item.name,
      price: item.price,
      image_url: item.image_url,
      prep_time_minutes: item.prep_time_minutes || 20,
      is_meat: false,
    };
    addToCart(dishObj, item.quantity || 1, item.notes || null);
  }
  if (goToCheckout) {
    switchTab('cart');
  }
}

function formatAiMarkdown(text) {
  if (!text) return '';
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/### (.*?)\n/g, '<h4 class="font-bold text-sm text-orange-600 dark:text-orange-400 mt-2 mb-1">$1</h4>')
    .replace(/\n/g, '<br />');
}

// ─────────────────────────────────────────────────────────────────────────────
// EVENTOS GLOBALES DESDE EL DRAWER LATERAL DE IA
// ─────────────────────────────────────────────────────────────────────────────
function handleExternalAddToCart(e) {
  const items = e.detail || [];
  addAiSuggestedItems(items, true);
}

function handleExternalApplyCoupon(e) {
  const code = e.detail;
  if (code) {
    couponInput.value = code;
    switchTab('cart');
    applyCouponCode(code);
  }
}

function handleExternalRefresh() {
  router.reload();
}

onMounted(() => {
  try {
    const saved = localStorage.getItem('lasbrazas_client_cart');
    if (saved) {
      const parsed = JSON.parse(saved);
      if (Array.isArray(parsed)) cartItems.value = parsed;
    }
  } catch (_) {}

  window.addEventListener('lasbrazas:add-to-cart', handleExternalAddToCart);
  window.addEventListener('lasbrazas:apply-coupon', handleExternalApplyCoupon);
  window.addEventListener('lasbrazas:refresh-portal', handleExternalRefresh);
});

onUnmounted(() => {
  window.removeEventListener('lasbrazas:add-to-cart', handleExternalAddToCart);
  window.removeEventListener('lasbrazas:apply-coupon', handleExternalApplyCoupon);
  window.removeEventListener('lasbrazas:refresh-portal', handleExternalRefresh);
});
</script>

<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-2.5">
          <span class="font-extrabold text-slate-900 dark:text-white">
            Marketplace & Portal del Cliente
          </span>
          <span
            v-if="userProfile?.email_verified_at"
            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30"
          >
            <ShieldCheck class="w-3.5 h-3.5" /> Correo Verificado
          </span>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- BARRA SUPERIOR DE EXPERIENCIA DEL CLIENTE (Entrega, Puntos, Carrito) -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <!-- Modalidad de Entrega (RF-04) -->
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mr-1 flex items-center gap-1">
            <Truck class="w-4 h-4 text-orange-500" /> Modalidad:
          </span>
          <button
            type="button"
            @click="orderType = 'delivery'"
            :class="[
              'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 border',
              orderType === 'delivery'
                ? 'bg-orange-600 text-white border-orange-600 shadow-sm'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'
            ]"
          >
            🛵 Delivery a Domicilio
          </button>
          <button
            type="button"
            @click="orderType = 'takeaway'"
            :class="[
              'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 border',
              orderType === 'takeaway'
                ? 'bg-orange-600 text-white border-orange-600 shadow-sm'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'
            ]"
          >
            🛍️ Para Recoger
          </button>
          <button
            type="button"
            @click="orderType = 'dine_in'"
            :class="[
              'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 border',
              orderType === 'dine_in'
                ? 'bg-orange-600 text-white border-orange-600 shadow-sm'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'
            ]"
          >
            🍽️ En Mesa QR
          </button>
        </div>

        <!-- Puntos Las Brazas & Acceso rápido al Carrito -->
        <div class="flex items-center gap-3">
          <button
            type="button"
            @click="switchTab('promos')"
            class="px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-300 text-xs font-extrabold flex items-center gap-1.5 cursor-pointer hover:bg-amber-500/20 transition-colors"
          >
            <Star class="w-4 h-4 text-amber-500 fill-amber-500" />
            {{ loyaltyPoints }} pts (${{ (loyaltyPoints * 0.05).toFixed(2) }} Cashback)
          </button>

          <button
            type="button"
            @click="switchTab('cart')"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white font-bold text-xs shadow-md shadow-orange-600/20 flex items-center gap-2 cursor-pointer transition-transform active:scale-95"
          >
            <ShoppingCart class="w-4 h-4" />
            <span>Carrito ({{ totalItemsCount }})</span>
            <span v-if="cartTotal > 0" class="px-1.5 py-0.5 rounded bg-black/20 text-[11px]">
              ${{ cartTotal.toFixed(2) }}
            </span>
          </button>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- NAVEGACIÓN DE PESTAÑAS DEL CLIENTE                                  -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <button
          v-for="t in tabs"
          :key="t.key"
          type="button"
          @click="switchTab(t.key)"
          :class="[
            'px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm flex items-center gap-2 whitespace-nowrap transition-all cursor-pointer border',
            activeTab === t.key
              ? 'bg-slate-900 text-white border-slate-900 dark:bg-orange-600 dark:border-orange-500 shadow-sm'
              : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-orange-500/40'
          ]"
        >
          <component :is="t.icon" class="w-4 h-4 shrink-0" />
          <span>{{ t.label }}</span>
          <span
            v-if="t.key === 'cart' && totalItemsCount > 0"
            class="px-1.5 py-0.2 rounded-full text-[10px] bg-orange-500 text-white"
          >
            {{ totalItemsCount }}
          </span>
          <span
            v-if="t.key === 'orders' && localOrders.length > 0"
            class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
          >
            {{ localOrders.length }}
          </span>
        </button>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- TAB 1: MARKETPLACE / MENÚ DIGITAL                                   -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div v-if="activeTab === 'menu'" class="space-y-5">
        <!-- Banner de Recomendación Personalizada (RF-25) -->
        <div
          v-if="recommendedDish && !searchQuery"
          class="rounded-2xl p-5 bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 text-white shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
        >
          <div class="space-y-1">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-black/25">
              <Flame class="w-3.5 h-3.5" /> Recomendación del Maestro Parrillero
            </span>
            <h3 class="text-lg sm:text-xl font-extrabold">
              {{ recommendedDish.name }} • ${{ Number(recommendedDish.price).toFixed(2) }}
            </h3>
            <p class="text-xs text-white/90 max-w-2xl">
              {{ recommendedDish.description }}
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <button
              type="button"
              @click="openCustomizeModal(recommendedDish)"
              class="px-4 py-2.5 rounded-xl bg-white text-orange-600 hover:bg-orange-50 font-extrabold text-xs shadow-sm cursor-pointer transition-transform active:scale-95"
            >
              + Ordenar Ahora
            </button>
          </div>
        </div>

        <!-- Buscador y Categorías -->
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
          <div class="relative flex-1 max-w-md">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Buscar cortes, entradas, guarniciones, bebidas..."
              class="w-full h-11 pl-10 pr-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-orange-500"
            />
          </div>

          <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <button
              type="button"
              @click="selectedCategoryId = null"
              :class="[
                'px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap border transition-colors cursor-pointer',
                selectedCategoryId === null
                  ? 'bg-orange-600 text-white border-orange-600'
                  : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800'
              ]"
            >
              Todos ({{ dishes.length }})
            </button>
            <button
              v-for="cat in categories"
              :key="cat.id"
              type="button"
              @click="selectedCategoryId = cat.id"
              :class="[
                'px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap border transition-colors cursor-pointer',
                selectedCategoryId === cat.id
                  ? 'bg-orange-600 text-white border-orange-600'
                  : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800'
              ]"
            >
              {{ cat.name }} ({{ cat.dishes_count }})
            </button>
          </div>
        </div>

        <!-- Grid de Platillos del Marketplace -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
          <div
            v-for="dish in filteredDishes"
            :key="dish.id"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-orange-500/40 transition-all flex flex-col"
          >
            <div class="relative h-44 bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <img
                :src="dish.image_url"
                :alt="dish.name"
                class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
              />
              <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg bg-slate-900/80 backdrop-blur text-white text-[10px] font-bold">
                {{ dish.category_name }}
              </span>
              <span class="absolute bottom-3 right-3 px-2.5 py-1 rounded-lg bg-black/75 text-amber-300 text-[11px] font-bold flex items-center gap-1">
                <Clock class="w-3 h-3" /> {{ dish.prep_time_minutes }} min
              </span>
            </div>

            <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
              <div>
                <h4 class="font-extrabold text-sm text-slate-900 dark:text-white line-clamp-1">
                  {{ dish.name }}
                </h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                  {{ dish.description }}
                </p>
              </div>

              <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                <div>
                  <span class="text-[10px] uppercase font-bold text-slate-400 block">Precio</span>
                  <span class="text-base font-extrabold text-orange-600 dark:text-orange-400">
                    ${{ Number(dish.price).toFixed(2) }}
                  </span>
                </div>

                <div class="flex items-center gap-1.5">
                  <button
                    type="button"
                    @click="openCustomizeModal(dish)"
                    class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-orange-500 hover:text-orange-500 transition-colors cursor-pointer"
                    title="Personalizar término de cocción o notas"
                  >
                    <SlidersHorizontal class="w-4 h-4" />
                  </button>
                  <button
                    type="button"
                    @click="addToCart(dish, 1)"
                    class="px-3.5 py-2 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs flex items-center gap-1 shadow-sm cursor-pointer transition-transform active:scale-95"
                  >
                    <Plus class="w-3.5 h-3.5" /> Agregar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- TAB 2: MI CARRITO, ENVÍO & CHECKOUT                                 -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div v-else-if="activeTab === 'cart'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna Izquierda: Ítems y Venta Cruzada -->
        <div class="lg:col-span-2 space-y-5">
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
              <h3 class="font-extrabold text-base text-slate-900 dark:text-white flex items-center gap-2">
                <ShoppingCart class="w-5 h-5 text-orange-500" />
                Platillos en tu Carrito ({{ totalItemsCount }})
              </h3>
              <button
                v-if="cartItems.length > 0"
                type="button"
                @click="clearCart"
                class="text-xs font-bold text-red-500 hover:underline flex items-center gap-1 cursor-pointer"
              >
                <Trash2 class="w-3.5 h-3.5" /> Vaciar carrito
              </button>
            </div>

            <div v-if="cartItems.length === 0" class="py-12 text-center space-y-3">
              <ShoppingCart class="w-12 h-12 text-slate-300 dark:text-slate-700 mx-auto" />
              <p class="font-bold text-slate-700 dark:text-slate-300">Tu carrito está vacío</p>
              <p class="text-xs text-slate-500 max-w-md mx-auto">
                Explora nuestros cortes a las brasas, guarniciones y bebidas en el Marketplace o pídele al Agente IA que arme tu pedido.
              </p>
              <button
                type="button"
                @click="switchTab('menu')"
                class="px-4 py-2 rounded-xl bg-orange-600 text-white font-bold text-xs cursor-pointer"
              >
                Ir al Menú Digital
              </button>
            </div>

            <div v-else class="divide-y divide-slate-200 dark:divide-slate-800">
              <div
                v-for="(item, idx) in cartItems"
                :key="idx"
                class="py-4 flex items-center justify-between gap-4"
              >
                <div class="flex items-center gap-3 min-w-0 flex-1">
                  <img
                    :src="item.dish.image_url"
                    :alt="item.dish.name"
                    class="w-14 h-14 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shrink-0"
                  />
                  <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white truncate">
                      {{ item.dish.name }}
                    </h4>
                    <p v-if="item.notes" class="text-xs text-orange-600 dark:text-orange-400 font-medium">
                      {{ item.notes }}
                    </p>
                    <p class="text-xs text-slate-500">
                      ${{ Number(item.dish.price).toFixed(2) }} c/u
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-3">
                  <div class="flex items-center border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                    <button
                      type="button"
                      @click="updateQty(idx, -1)"
                      class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-pointer"
                    >
                      <Minus class="w-3.5 h-3.5" />
                    </button>
                    <span class="px-3 text-xs font-extrabold">{{ item.quantity }}</span>
                    <button
                      type="button"
                      @click="updateQty(idx, 1)"
                      class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-pointer"
                    >
                      <Plus class="w-3.5 h-3.5" />
                    </button>
                  </div>

                  <span class="font-extrabold text-sm text-slate-900 dark:text-white w-20 text-right">
                    ${{ (Number(item.dish.price) * item.quantity).toFixed(2) }}
                  </span>

                  <button
                    type="button"
                    @click="removeCartItem(idx)"
                    class="p-1.5 text-slate-400 hover:text-red-500 cursor-pointer"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Sugerencias para acompañar (Upsell RF-24) -->
          <div
            v-if="cartItems.length > 0 && upsellDishes.length > 0"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3"
          >
            <h4 class="font-bold text-sm text-slate-800 dark:text-slate-200 flex items-center gap-2">
              <Sparkles class="w-4 h-4 text-amber-500" /> ¿Algo más para acompañar tus cortes?
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div
                v-for="u in upsellDishes"
                :key="u.id"
                class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3"
              >
                <div class="min-w-0">
                  <p class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ u.name }}</p>
                  <p class="text-xs text-orange-600 dark:text-orange-400 font-bold">${{ Number(u.price).toFixed(2) }}</p>
                </div>
                <button
                  type="button"
                  @click="addToCart(u, 1)"
                  class="px-3 py-1.5 rounded-lg bg-orange-500/10 hover:bg-orange-500/20 text-orange-600 dark:text-orange-400 font-bold text-xs shrink-0 cursor-pointer"
                >
                  + Agregar
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Columna Derecha: Dirección, Cupón, Pago y Confirmación -->
        <div class="space-y-5">
          <!-- Dirección o Mesa según Modalidad -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
            <h4 class="font-extrabold text-sm text-slate-900 dark:text-white flex items-center gap-2">
              <MapPin class="w-4 h-4 text-orange-500" />
              {{ orderType === 'delivery' ? '1. Dirección de Envío a Domicilio' : orderType === 'dine_in' ? '1. Selección de Mesa en Salón' : '1. Retiro en Sucursal Las Brazas' }}
            </h4>

            <div v-if="orderType === 'delivery'" class="space-y-3">
              <div v-if="localAddresses.length > 0" class="space-y-2">
                <label class="text-xs font-semibold text-slate-500 block">Mis direcciones guardadas:</label>
                <select
                  v-model="selectedAddressId"
                  class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold"
                >
                  <option :value="null">-- Escribir otra dirección manualmente --</option>
                  <option v-for="a in localAddresses" :key="a.id" :value="a.id">
                    {{ a.label }}: {{ a.full_text }}
                  </option>
                </select>
              </div>

              <div v-if="!selectedAddressId">
                <input
                  type="text"
                  v-model="manualAddress"
                  placeholder="Ej. Av. San Martín #450, Equipetrol (Ref: portón negro)"
                  class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs"
                />
              </div>

              <p class="text-[11px] text-slate-500 flex items-center gap-1">
                <Clock class="w-3.5 h-3.5 text-orange-500" />
                Tiempo estimado de entrega: <strong>{{ estimatedEta }}</strong>
              </p>
            </div>

            <div v-else-if="orderType === 'dine_in'" class="space-y-2">
              <label class="text-xs font-semibold text-slate-500 block">Selecciona tu mesa:</label>
              <select
                v-model="selectedTableId"
                class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold"
              >
                <option v-for="t in tables" :key="t.id" :value="t.id">
                  {{ t.table_number }} ({{ t.area_name }} • {{ t.capacity }} personas)
                </option>
              </select>
            </div>

            <div v-else class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-700 dark:text-emerald-300">
              Retiro en Sucursal Principal Las Brazas • Estará listo en <strong>{{ estimatedEta }}</strong> (Sin costo de envío).
            </div>
          </div>

          <!-- Cupón Promocional -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
            <h4 class="font-extrabold text-sm text-slate-900 dark:text-white flex items-center gap-2">
              <TagIcon class="w-4 h-4 text-orange-500" /> 2. Cupón de Descuento
            </h4>
            <div class="flex gap-2">
              <input
                type="text"
                v-model="couponInput"
                placeholder="Ej. BRAZAS10 o ENVIOGRATIS"
                class="flex-1 h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs uppercase font-bold"
              />
              <button
                type="button"
                @click="applyCouponCode()"
                class="px-4 h-10 rounded-xl bg-slate-900 dark:bg-orange-600 text-white font-bold text-xs cursor-pointer"
              >
                Aplicar
              </button>
            </div>
            <div v-if="appliedCoupon" class="flex items-center justify-between text-xs text-emerald-600 dark:text-emerald-400 font-bold">
              <span>✓ Cupón {{ appliedCoupon.code }} activo</span>
              <button type="button" @click="appliedCoupon = null" class="text-red-500 underline cursor-pointer">Quitar</button>
            </div>
          </div>

          <!-- Método de Pago y Facturación -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
            <h4 class="font-extrabold text-sm text-slate-900 dark:text-white flex items-center gap-2">
              <CreditCard class="w-4 h-4 text-orange-500" /> 3. Método de Pago & Factura
            </h4>

            <div class="grid grid-cols-3 gap-2">
              <button
                type="button"
                @click="paymentMethod = 'card'"
                :class="[
                  'p-2.5 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 cursor-pointer',
                  paymentMethod === 'card'
                    ? 'border-orange-500 bg-orange-500/10 text-orange-600 dark:text-orange-400'
                    : 'border-slate-200 dark:border-slate-800 text-slate-500'
                ]"
              >
                <CreditCard class="w-4 h-4" /> Tarjeta
              </button>
              <button
                type="button"
                @click="paymentMethod = 'transfer'"
                :class="[
                  'p-2.5 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 cursor-pointer',
                  paymentMethod === 'transfer'
                    ? 'border-orange-500 bg-orange-500/10 text-orange-600 dark:text-orange-400'
                    : 'border-slate-200 dark:border-slate-800 text-slate-500'
                ]"
              >
                <QrCode class="w-4 h-4" /> QR Simple
              </button>
              <button
                type="button"
                @click="paymentMethod = 'cash'"
                :class="[
                  'p-2.5 rounded-xl border text-xs font-bold flex flex-col items-center gap-1 cursor-pointer',
                  paymentMethod === 'cash'
                    ? 'border-orange-500 bg-orange-500/10 text-orange-600 dark:text-orange-400'
                    : 'border-slate-200 dark:border-slate-800 text-slate-500'
                ]"
              >
                <Banknote class="w-4 h-4" /> Efectivo
              </button>
            </div>

            <!-- Campos contextuales de pago -->
            <div v-if="paymentMethod === 'card'" class="space-y-2 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700">
              <input v-model="cardNumber" type="text" placeholder="Número de tarjeta" class="w-full h-9 px-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs font-mono" />
              <div class="grid grid-cols-2 gap-2">
                <input v-model="cardExpiry" type="text" placeholder="MM/AA" class="h-9 px-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs font-mono" />
                <input v-model="cardCvv" type="password" placeholder="CVV" class="h-9 px-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs font-mono" />
              </div>
            </div>

            <div v-else-if="paymentMethod === 'transfer'" class="p-3 rounded-xl bg-teal-500/10 border border-teal-500/30 text-xs space-y-1">
              <p class="font-bold text-teal-700 dark:text-teal-300">Pago por Código QR Simple</p>
              <p class="text-[11px] text-slate-500">Referencia generada: <strong>{{ transferRef }}</strong></p>
            </div>

            <!-- Factura electrónica -->
            <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-2">
              <label class="flex items-center gap-2 text-xs font-bold cursor-pointer">
                <input type="checkbox" v-model="requestInvoice" class="rounded text-orange-600" />
                <span>Solicitar Factura Electrónica (Razón Social y NIT/CI)</span>
              </label>
              <div v-if="requestInvoice" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <input v-model="invoiceName" type="text" placeholder="Razón Social / Nombre" class="h-9 px-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs" />
                <input v-model="invoiceTaxId" type="text" placeholder="NIT / CI" class="h-9 px-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs" />
              </div>
            </div>

            <!-- Resumen de costos -->
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-1.5 text-xs">
              <div class="flex justify-between text-slate-500">
                <span>Subtotal ({{ totalItemsCount }} ítems)</span>
                <span>${{ cartSubtotal.toFixed(2) }}</span>
              </div>
              <div v-if="cartDiscount > 0" class="flex justify-between text-emerald-600 font-bold">
                <span>Descuento Cupón</span>
                <span>-${{ cartDiscount.toFixed(2) }}</span>
              </div>
              <div v-if="orderType === 'delivery'" class="flex justify-between text-slate-500">
                <span>Costo de Envío (Gratis desde $50)</span>
                <span>{{ deliveryFee === 0 ? 'GRATIS' : `$${deliveryFee.toFixed(2)}` }}</span>
              </div>
              <div class="flex justify-between text-base font-extrabold text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-800">
                <span>Total a Pagar</span>
                <span class="text-orange-600 dark:text-orange-400">${{ cartTotal.toFixed(2) }}</span>
              </div>
            </div>

            <button
              type="button"
              :disabled="cartItems.length === 0 || submittingOrder"
              @click="submitCheckout"
              class="w-full py-3 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white font-extrabold text-sm shadow-lg shadow-orange-600/25 disabled:opacity-40 cursor-pointer transition-transform active:scale-98"
            >
              {{ submittingOrder ? 'Procesando pedido...' : `🔥 Confirmar y Pagar $${cartTotal.toFixed(2)}` }}
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- TAB 3: MIS PEDIDOS & TRACKING DE ENVÍOS EN VIVO                     -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div v-else-if="activeTab === 'orders'" class="space-y-4">
        <div v-if="localOrders.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center space-y-3">
          <Truck class="w-12 h-12 text-slate-300 dark:text-slate-700 mx-auto" />
          <h3 class="font-extrabold text-base text-slate-800 dark:text-slate-200">Aún no tienes pedidos registrados</h3>
          <p class="text-xs text-slate-500">Cuando realices un pedido desde el Marketplace o con el Agente IA, verás su seguimiento en tiempo real aquí.</p>
        </div>

        <div
          v-for="order in localOrders"
          :key="order.id"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4"
        >
          <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-base text-slate-900 dark:text-white">
                  Pedido #{{ order.id }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-500/20">
                  {{ order.order_type === 'delivery' ? '🛵 Delivery a Domicilio' : order.order_type === 'takeaway' ? '🛍️ Para Recoger' : `🍽️ En Salón (${order.table_number || 'Mesa'})` }}
                </span>
                <span
                  v-if="order.delivery_pin"
                  class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30"
                >
                  🔐 PIN de Entrega: {{ order.delivery_pin }}
                </span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">
                Realizado el {{ order.created_at }}
                <span v-if="order.delivery_address"> • 📍 {{ order.delivery_address }}</span>
              </p>
            </div>

            <div class="text-right">
              <span class="text-lg font-extrabold text-orange-600 dark:text-orange-400">
                ${{ Number(order.total).toFixed(2) }}
              </span>
              <span class="block text-[10px] uppercase font-bold text-slate-400">
                {{ order.items_count }} ítem(s)
              </span>
            </div>
          </div>

          <!-- Barra de Progreso / Tracking en Vivo (RF-13) -->
          <div v-if="order.status !== 'cancelled'" class="py-2">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
              <div
                v-for="(step, sIdx) in (order.tracking?.steps || ['received', 'in_kitchen', 'ready', 'delivered'])"
                :key="step"
                :class="[
                  'p-2.5 rounded-xl border text-center transition-all',
                  sIdx <= getCurrentStepIndex(order)
                    ? 'bg-emerald-500/10 border-emerald-500/40 text-emerald-700 dark:text-emerald-300 font-bold'
                    : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-800 text-slate-400'
                ]"
              >
                <p class="text-[11px]">{{ sIdx + 1 }}. {{ trackingStepsLabels[step] || step }}</p>
              </div>
            </div>
          </div>
          <div v-else class="p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-xs font-bold text-red-600 dark:text-red-400">
            ❌ Este pedido fue cancelado.
          </div>

          <!-- Lista de platillos del pedido -->
          <div class="bg-slate-50 dark:bg-slate-800/40 rounded-xl p-3 space-y-1 text-xs">
            <div v-for="(it, iIdx) in order.items" :key="iIdx" class="flex justify-between">
              <span class="font-medium text-slate-700 dark:text-slate-300">
                <strong>{{ it.quantity }}x</strong> {{ it.name }}
                <em v-if="it.notes" class="text-slate-400"> ({{ it.notes }})</em>
              </span>
              <span class="font-bold">${{ Number(it.subtotal).toFixed(2) }}</span>
            </div>
          </div>

          <!-- Acciones del pedido -->
          <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
            <a
              :href="`/client/orders/${order.id}/invoice`"
              target="_blank"
              class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-orange-500 text-xs font-bold flex items-center gap-1.5 text-slate-700 dark:text-slate-300"
            >
              <FileText class="w-3.5 h-3.5 text-orange-500" /> Comprobante PDF
            </a>
            <button
              type="button"
              @click="reorderPastOrder(order)"
              class="px-3.5 py-2 rounded-xl bg-orange-500/10 hover:bg-orange-500/20 text-orange-600 dark:text-orange-400 border border-orange-500/30 text-xs font-bold flex items-center gap-1.5 cursor-pointer"
            >
              <RotateCcw class="w-3.5 h-3.5" /> Volver a pedir
            </button>
            <button
              v-if="order.status === 'pending'"
              type="button"
              @click="cancelPendingOrder(order)"
              class="px-3.5 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 border border-red-500/30 text-xs font-bold flex items-center gap-1.5 cursor-pointer"
            >
              <XCircle class="w-3.5 h-3.5" /> Cancelar pedido
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- TAB 4: PROMOS, CUPONES & PROGRAMA DE PUNTOS LAS BRAZAS              -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div v-else-if="activeTab === 'promos'" class="space-y-6">
        <!-- Tarjeta de Lealtad & Cashback (RF-23) -->
        <div class="rounded-2xl p-6 bg-gradient-to-r from-slate-900 via-slate-800 to-orange-950 text-white shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="space-y-1.5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
              <Star class="w-3.5 h-3.5 fill-amber-400" /> Club Las Brazas Rewards
            </span>
            <h3 class="text-2xl font-extrabold">
              Tienes {{ loyaltyPoints }} Puntos Acumulados
            </h3>
            <p class="text-xs text-slate-300">
              Acumulas 1 punto por cada $1 consumido. Equivalente en Cashback: <strong>${{ (loyaltyPoints * 0.05).toFixed(2) }} USD</strong>.
            </p>
          </div>
          <button
            type="button"
            @click="switchTab('cart'); applyCouponCode('BRAZAS10')"
            class="px-5 py-3 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-400 hover:to-amber-400 text-white font-extrabold text-xs shadow-lg cursor-pointer shrink-0"
          >
            🎁 Canjear Cupón BRAZAS10 en mi Carrito
          </button>
        </div>

        <!-- Cupones Activos en la Base de Datos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div
            v-for="c in coupons"
            :key="c.id"
            class="bg-white dark:bg-slate-900 border-2 border-dashed border-orange-500/40 rounded-2xl p-5 flex flex-col justify-between gap-4 shadow-sm"
          >
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="px-3 py-1 rounded-lg bg-orange-500/15 text-orange-600 dark:text-orange-400 font-mono font-extrabold text-sm">
                  {{ c.code }}
                </span>
                <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">● Activo</span>
              </div>
              <h4 class="font-extrabold text-base text-slate-900 dark:text-white">
                {{ c.type === 'percent' ? `${c.value}% de Descuento` : c.type === 'fixed' ? `$${c.value} de Descuento Directo` : 'Envío a Domicilio GRATIS' }}
              </h4>
              <p class="text-xs text-slate-500">
                Compra mínima requerida: <strong>${{ Number(c.min_order).toFixed(2) }}</strong>
              </p>
            </div>

            <button
              type="button"
              @click="switchTab('cart'); applyCouponCode(c.code)"
              class="w-full py-2.5 rounded-xl bg-slate-900 dark:bg-orange-600 hover:opacity-90 text-white font-bold text-xs cursor-pointer"
            >
              Usar cupón {{ c.code }}
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- TAB 5: MIS RESERVAS DE MESA                                         -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div v-else-if="activeTab === 'reservations'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Nueva Reserva -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
          <h3 class="font-extrabold text-base text-slate-900 dark:text-white flex items-center gap-2">
            <CalendarCheck class="w-5 h-5 text-orange-500" /> Agendar Nueva Mesa
          </h3>

          <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-800 dark:text-amber-300 font-medium">
            ⏱️ <strong>Política de Tolerancia:</strong> Tu mesa reservada se guarda por <strong>15 minutos estrictos</strong> a partir de la hora elegida.
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-bold text-slate-500 block mb-1">Fecha</label>
              <input v-model="resForm.date" type="date" class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs" />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-500 block mb-1">Hora</label>
              <input v-model="resForm.time" type="time" class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-bold text-slate-500 block mb-1">Adultos</label>
              <input v-model.number="resForm.adults" type="number" min="1" max="20" class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold" />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-500 block mb-1">Niños</label>
              <input v-model.number="resForm.children" type="number" min="0" max="15" class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold" />
            </div>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-500 block mb-1">Zona del Restaurante</label>
            <select v-model="resForm.area_id" class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold">
              <option v-for="a in areas" :key="a.id" :value="a.id">
                {{ a.name }} ({{ a.available_tables }} mesas libres • Aforo {{ a.capacity }}p)
              </option>
            </select>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-500 block mb-1">Solicitudes Especiales</label>
            <textarea
              v-model="resForm.special_requests"
              rows="2"
              placeholder="Ej. Aniversario, silla para bebé, mesa cerca de ventana..."
              class="w-full p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs"
            ></textarea>
          </div>

          <button
            type="button"
            :disabled="submittingRes"
            @click="submitReservation"
            class="w-full py-3 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 text-white font-extrabold text-xs shadow-md cursor-pointer"
          >
            {{ submittingRes ? 'Confirmando...' : 'Confirmar Reservación' }}
          </button>
        </div>

        <!-- Mis Reservas Registradas -->
        <div class="lg:col-span-2 space-y-3">
          <h3 class="font-extrabold text-base text-slate-900 dark:text-white">
            Mis Reservaciones ({{ localReservations.length }})
          </h3>

          <div v-if="localReservations.length === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-10 text-center text-xs text-slate-500">
            No tienes reservaciones registradas. Puedes crear una en el formulario o pedírsela al Agente IA.
          </div>

          <div
            v-for="r in localReservations"
            :key="r.id"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4 shadow-sm"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-sm text-slate-900 dark:text-white">
                  Folio #{{ r.id }} • {{ r.reservation_date }} hrs
                </span>
                <span
                  :class="[
                    'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                    r.status === 'cancelled'
                      ? 'bg-red-500/10 text-red-500'
                      : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                  ]"
                >
                  {{ r.status === 'confirmed' ? 'Confirmada' : r.status === 'cancelled' ? 'Cancelada' : r.status }}
                </span>
              </div>
              <p class="text-xs text-slate-500">
                📍 <strong>{{ r.area_name }}</strong> ({{ r.table_number }}) • 👥 {{ r.guest_count }} comensales
              </p>
              <p v-if="r.special_requests" class="text-xs text-orange-600 dark:text-orange-400 italic">
                "{{ r.special_requests }}"
              </p>
            </div>

            <button
              v-if="r.status !== 'cancelled' && r.status !== 'completed'"
              type="button"
              @click="cancelReservation(r)"
              class="px-3 py-1.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 font-bold text-xs cursor-pointer"
            >
              Cancelar
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- TAB 6: AGENTE INTELIGENTE DEL CLIENTE (Pedidos, Reservas, Búsqueda) -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div v-else-if="activeTab === 'ai'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden flex flex-col h-[640px]">
        <!-- Cabecera del Agente -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-600 to-amber-500 text-white flex items-center justify-center shadow">
              <Bot class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">
                Agente Inteligente Las Brazas (Voz & Texto)
              </h3>
              <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                ● Hace pedidos, busca platillos por presupuesto, agenda reservas y valida cupones
              </p>
            </div>
          </div>
        </div>

        <!-- Chips de acciones inteligentes -->
        <div class="px-4 py-2.5 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2 overflow-x-auto">
          <button
            v-for="(p, idx) in aiPrompts"
            :key="idx"
            type="button"
            @click="sendAiPrompt(p)"
            class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-orange-500/15 hover:text-orange-600 dark:hover:text-orange-400 text-xs font-semibold whitespace-nowrap cursor-pointer transition-colors"
          >
            {{ p }}
          </button>
        </div>

        <!-- Hilo de Conversación -->
        <div class="flex-1 p-5 overflow-y-auto space-y-4">
          <div
            v-for="m in aiMessages"
            :key="m.id"
            :class="[
              'flex gap-3 max-w-2xl',
              m.role === 'user' ? 'ml-auto flex-row-reverse' : 'mr-auto'
            ]"
          >
            <div
              :class="[
                'p-4 rounded-2xl text-xs sm:text-sm leading-relaxed space-y-3',
                m.role === 'user'
                  ? 'bg-gradient-to-r from-orange-600 to-amber-500 text-white rounded-tr-none shadow-md'
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-tl-none border border-slate-200 dark:border-slate-700'
              ]"
            >
              <div v-html="formatAiMarkdown(m.text)"></div>

              <!-- Tarjetas de Platillos Detectados por el Agente -->
              <div v-if="m.suggestedItems && m.suggestedItems.length > 0" class="pt-3 border-t border-slate-300/60 dark:border-slate-700 space-y-2">
                <div
                  v-for="item in m.suggestedItems"
                  :key="item.dish_id"
                  class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3"
                >
                  <div class="flex items-center gap-2.5 min-w-0">
                    <img :src="item.image_url" class="w-11 h-11 rounded-lg object-cover shrink-0" />
                    <div class="min-w-0">
                      <p class="font-bold text-xs text-slate-900 dark:text-white truncate">
                        {{ item.quantity }}x {{ item.name }}
                      </p>
                      <p class="text-[11px] text-orange-600 dark:text-orange-400 font-bold">
                        ${{ Number(item.price).toFixed(2) }} c/u {{ item.notes ? `• ${item.notes}` : '' }}
                      </p>
                    </div>
                  </div>
                  <button
                    type="button"
                    @click="addAiSuggestedItems([item], false)"
                    class="px-3 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs shrink-0 cursor-pointer"
                  >
                    + Carrito
                  </button>
                </div>

                <div class="flex flex-wrap gap-2 pt-1">
                  <button
                    type="button"
                    @click="addAiSuggestedItems(m.suggestedItems, true)"
                    class="flex-1 py-2.5 px-4 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 text-white font-extrabold text-xs shadow cursor-pointer"
                  >
                    🛒 Agregar Todo al Carrito e Ir a Pagar
                  </button>
                </div>
              </div>

              <!-- Cupones Interactivos -->
              <div v-if="m.suggestedCoupons && m.suggestedCoupons.length > 0" class="pt-2 flex flex-wrap gap-2">
                <button
                  v-for="c in m.suggestedCoupons"
                  :key="c.code"
                  type="button"
                  @click="switchTab('cart'); applyCouponCode(c.code)"
                  class="px-3 py-1.5 rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-bold text-xs cursor-pointer"
                >
                  🎟️ Aplicar {{ c.code }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Barra de Entrada + Voz -->
        <form @submit.prevent="sendAiPrompt()" class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center gap-2">
          <input
            v-model="aiInput"
            type="text"
            placeholder="Ej: 'Pídeme una Arrachera término medio y una cerveza' o 'Reserva para 4 personas mañana a las 20:00'..."
            class="flex-1 h-11 px-4 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs sm:text-sm focus:outline-none focus:border-orange-500"
          />
          <button
            type="button"
            @click="toggleAiVoice"
            :class="[
              'h-11 w-11 rounded-xl flex items-center justify-center border cursor-pointer',
              aiListening ? 'bg-red-500 text-white border-red-400 animate-pulse' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'
            ]"
            title="Dictar por voz"
          >
            <MicOff v-if="aiListening" class="w-4 h-4" />
            <Mic v-else class="w-4 h-4" />
          </button>
          <button
            type="submit"
            :disabled="aiLoading || !aiInput.trim()"
            class="h-11 px-5 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 text-white font-bold text-xs flex items-center gap-1.5 disabled:opacity-40 cursor-pointer"
          >
            <Send class="w-4 h-4" /> Enviar
          </button>
        </form>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <!-- TAB 7: MI PERFIL, PREFERENCIAS & LIBRETA DE DIRECCIONES             -->
      <!-- ═══════════════════════════════════════════════════════════════════ -->
      <div v-else-if="activeTab === 'profile'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Datos de Cuenta y Preferencias Gastronómicas -->
        <div class="space-y-5">
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
            <h3 class="font-extrabold text-base text-slate-900 dark:text-white flex items-center gap-2">
              <UserCircle class="w-5 h-5 text-orange-500" /> Datos de tu Cuenta de Cliente
            </h3>
            <div class="text-xs space-y-1.5 text-slate-600 dark:text-slate-300">
              <p><strong>Nombre:</strong> {{ userProfile?.name }}</p>
              <p><strong>Correo:</strong> {{ userProfile?.email }}</p>
              <p>
                <strong>Estado de Verificación:</strong>
                <span class="ml-1 text-emerald-600 dark:text-emerald-400 font-bold">
                  ✓ Verificado ({{ userProfile?.email_verified_at || 'Activo' }})
                </span>
              </p>
            </div>
          </div>

          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-3">
            <h3 class="font-extrabold text-base text-slate-900 dark:text-white">
              Preferencias Gastronómicas & Alergias
            </h3>
            <p class="text-xs text-slate-500">
              Personalizan las recomendaciones automáticas del Marketplace.
            </p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="pref in dietOptions"
                :key="pref"
                type="button"
                @click="toggleDietPref(pref)"
                :class="[
                  'px-3 py-1.5 rounded-xl text-xs font-bold border transition-colors cursor-pointer',
                  selectedPrefs.includes(pref)
                    ? 'bg-orange-600 text-white border-orange-600'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'
                ]"
              >
                {{ pref }}
              </button>
            </div>
          </div>
        </div>

        <!-- Libreta de Direcciones de Entrega -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
          <h3 class="font-extrabold text-base text-slate-900 dark:text-white flex items-center gap-2">
            <MapPin class="w-5 h-5 text-orange-500" /> Mis Direcciones de Envío
          </h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <input v-model="newAddr.label" type="text" placeholder="Etiqueta (Casa, Oficina)" class="h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs" />
            <input v-model="newAddr.street" type="text" placeholder="Calle / Avenida *" class="h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs" />
            <input v-model="newAddr.number" type="text" placeholder="Número / Casa" class="h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs" />
            <input v-model="newAddr.reference" type="text" placeholder="Referencia de entrega" class="h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs" />
          </div>
          <button
            type="button"
            :disabled="savingAddress"
            @click="saveNewAddress"
            class="px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs cursor-pointer"
          >
            + Guardar Dirección
          </button>

          <div class="divide-y divide-slate-200 dark:divide-slate-800 pt-2">
            <div
              v-for="a in localAddresses"
              :key="a.id"
              class="py-3 flex items-center justify-between gap-3 text-xs"
            >
              <div>
                <p class="font-bold text-slate-900 dark:text-white">{{ a.label }}</p>
                <p class="text-slate-500">{{ a.full_text }}</p>
              </div>
              <button
                type="button"
                @click="deleteAddress(a.id)"
                class="text-red-500 hover:underline font-bold cursor-pointer"
              >
                Eliminar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- MODAL DE PERSONALIZACIÓN DE CORTE / PLATILLO (RF-02)                -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div
      v-if="customizingDish"
      class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="customizingDish = null"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="font-extrabold text-base text-slate-900 dark:text-white">
              {{ customizingDish.name }}
            </h3>
            <p class="text-xs text-orange-600 dark:text-orange-400 font-bold">
              ${{ Number(customizingDish.price).toFixed(2) }} • {{ customizingDish.prep_time_minutes }} min en parrilla
            </p>
          </div>
        </div>

        <div v-if="customizingDish.is_meat" class="space-y-2">
          <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block">
            Selecciona el término de cocción de tu corte:
          </label>
          <div class="grid grid-cols-1 gap-1.5">
            <button
              v-for="term in customizingDish.meat_cooking_terms"
              :key="term"
              type="button"
              @click="customCookingTerm = term"
              :class="[
                'px-3 py-2 rounded-xl border text-xs font-bold text-left cursor-pointer',
                customCookingTerm === term
                  ? 'border-orange-500 bg-orange-500/10 text-orange-600 dark:text-orange-400'
                  : 'border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300'
              ]"
            >
              🔥 {{ term }}
            </button>
          </div>
        </div>

        <div>
          <label class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">
            Notas para cocina (opcional):
          </label>
          <input
            v-model="customNotes"
            type="text"
            placeholder="Ej. Sin cebolla, salsa aparte, bien jugoso..."
            class="w-full h-10 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs"
          />
        </div>

        <div class="flex items-center justify-between pt-2">
          <div class="flex items-center border border-slate-200 dark:border-slate-700 rounded-xl">
            <button type="button" @click="customQuantity = Math.max(1, customQuantity - 1)" class="p-2 cursor-pointer">
              <Minus class="w-4 h-4" />
            </button>
            <span class="px-4 font-extrabold text-sm">{{ customQuantity }}</span>
            <button type="button" @click="customQuantity++" class="p-2 cursor-pointer">
              <Plus class="w-4 h-4" />
            </button>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="customizingDish = null"
              class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold cursor-pointer"
            >
              Cancelar
            </button>
            <button
              type="button"
              @click="confirmCustomAdd"
              class="px-5 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-extrabold text-xs cursor-pointer"
            >
              Agregar (${{ (Number(customizingDish.price) * customQuantity).toFixed(2) }})
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

