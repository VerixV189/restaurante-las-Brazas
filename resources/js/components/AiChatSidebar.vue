<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Button from 'primevue/button';
import {
  Flame,
  Sparkles,
  X,
  Send,
  Trash2,
  Bot,
  User as UserIcon,
  Clock,
  Utensils,
  Calendar,
  Package,
  RotateCcw,
  Info,
  Mic,
  MicOff
} from 'lucide-vue-next';

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:visible']);

const page = usePage();
const userName = computed(() => {
  const full = page.props.auth?.user?.name || 'Comensal';
  return full.split(' ')[0];
});
const userRole = computed(() => page.props.auth?.user?.role || 'Cliente');
const isStaff = computed(() => ['Administrador', 'Chef', 'Mesero', 'Recepcionista'].includes(userRole.value));

const messages = ref([]);
const inputMessage = ref('');
const isLoading = ref(false);
const isListening = ref(false);
const chatContainer = ref(null);
const inputRef = ref(null);
let recognition = null;

function toggleVoiceChat() {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognition) {
    alert('Tu navegador no soporta dictado por voz. Usa Chrome o Edge.');
    return;
  }

  if (isListening.value && recognition) {
    recognition.stop();
    isListening.value = false;
    return;
  }

  recognition = new SpeechRecognition();
  recognition.lang = 'es-ES';
  recognition.interimResults = false;
  recognition.maxAlternatives = 1;

  recognition.onstart = () => {
    isListening.value = true;
  };
  recognition.onresult = (event) => {
    const transcript = event.results[0][0].transcript;
    inputMessage.value = transcript;
    isListening.value = false;
    sendMessage();
  };
  recognition.onerror = () => {
    isListening.value = false;
  };
  recognition.onend = () => {
    isListening.value = false;
  };

  recognition.start();
}

// Mensaje inicial de bienvenida
function initWelcomeMessage() {
  const welcomeText = isStaff.value
    ? `¡Hola, **${userName.value}** (${userRole.value})! 👋 Soy tu **Agente IA Ejecutivo y Chef Virtual** en Las Brazas. 🔥🥩\n\nEstoy conectado en vivo a PostgreSQL. Puedes hablarme por micrófono o escribirme:\n\n• 📊 **Finanzas y KPIs:** Ventas de hoy, ticket promedio y top vendidos.\n• 💰 **Rentabilidad y Recetas:** Costos de insumos, márgenes y fichas técnicas.\n• ⚠️ **Stock Crítico y Mermas:** Alertas de insumos y teléfonos de proveedores.\n• 🛵 **Cocina y Delivery App:** Comandas en fuego y pedidos móviles.\n• 🪑 **Acciones por Chat:** Escribe *"Reserva una mesa para 4 personas mañana a las 20:00"* y la agendo al instante.`
    : `¡Hola, **${userName.value}**! 👋 Soy tu **Chef Virtual y Sommelier IA** en Restaurante Las Brazas. 🔥🥩\n\nPuedes hablarme o escribirme sobre:\n\n• 🥩 **Menú, Cortes y Maridaje:** Precios, tiempos y sugerencias por presupuesto.\n• 📅 **Reservar por Chat:** Dime *"Reserva una mesa para 4 personas mañana a las 20:00"*.\n• 📦 **Tus Pedidos y Cupones:** Estado de tus comandas y descuentos activos.`;

  messages.value = [
    {
      id: 'welcome',
      role: 'assistant',
      text: welcomeText,
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    },
  ];
}

onMounted(() => {
  initWelcomeMessage();
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});

function handleKeydown(e) {
  if (e.key === 'Escape' && props.visible) {
    closeChat();
  }
}

function closeChat() {
  emit('update:visible', false);
}

// Sugerencias rápidas inteligentes según el rol del usuario
const quickSuggestions = computed(() => {
  if (isStaff.value) {
    return [
      { label: '📊 Ventas y KPIs Hoy', prompt: '¿Cómo van las ventas hoy y cuáles son los platillos más vendidos?' },
      { label: '⚠️ Stock Crítico', prompt: '¿Qué insumos están en stock crítico y a qué proveedor debo llamar?' },
      { label: '💰 Más Rentables', prompt: '¿Cuáles son los platillos con mayor margen de rentabilidad?' },
      { label: '🛵 Cocina & Delivery', prompt: '¿Qué comandas hay activas en cocina y en pedidos de la App Móvil?' },
      { label: '🪑 Mesas Libres', prompt: '¿Qué mesas están libres en este momento?' },
      { label: '➕ Reservar por Chat', prompt: 'Reserva una mesa para 4 personas mañana a las 20:00' },
      { label: '🎟️ Cupones Activos', prompt: '¿Qué cupones promocionales están activos?' },
    ];
  }
  return [
    { label: '🛒 Pedir Arrachera + Cerveza', prompt: 'Pídeme una Arrachera término medio y una Cerveza Artesanal' },
    { label: '🥩 Mejores cortes', prompt: '¿Cuáles son los cortes de carne más recomendados?' },
    { label: '🎯 Presupuesto $350', prompt: '¿Qué me recomiendas pedir con un presupuesto de $350?' },
    { label: '➕ Reservar para 4p', prompt: 'Reserva una mesa para 4 personas mañana a las 20:00' },
    { label: '📦 Mis pedidos', prompt: '¿Cuál es el estado de mis pedidos recientes?' },
    { label: '🎟️ Cupones', prompt: '¿Qué cupones de descuento tienen activos?' },
    { label: '🪑 Mesas Libres', prompt: '¿Qué mesas están libres ahora?' },
  ];
});

function addSuggestedToCart(items) {
  window.dispatchEvent(new CustomEvent('lasbrazas:add-to-cart', { detail: items }));
  closeChat();
}

function applySuggestedCoupon(code) {
  window.dispatchEvent(new CustomEvent('lasbrazas:apply-coupon', { detail: code }));
  closeChat();
}

function sendSuggestion(prompt) {
  inputMessage.value = prompt;
  sendMessage();
}

async function sendMessage() {
  const text = inputMessage.value.trim();
  if (!text || isLoading.value) return;

  // Agregar mensaje del usuario
  const userMsgId = 'u-' + Date.now();
  messages.value.push({
    id: userMsgId,
    role: 'user',
    text: text,
    timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
  });

  inputMessage.value = '';
  isLoading.value = true;
  await scrollToBottom();

  try {
    const historyPayload = messages.value.slice(-6).map(m => ({
      role: m.role,
      content: m.text,
    }));

    const response = await axios.post('/ai/chat', {
      message: text,
      history: historyPayload,
    });

    if (response.data && response.data.reply) {
      messages.value.push({
        id: 'ai-' + Date.now(),
        role: 'assistant',
        text: response.data.reply,
        suggestedItems: response.data.suggested_items || [],
        suggestedCoupons: response.data.suggested_coupons || [],
        timestamp: response.data.timestamp || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      });
      if (response.data.created_reservation) {
        window.dispatchEvent(new CustomEvent('lasbrazas:refresh-portal'));
      }
    } else {
      messages.value.push({
        id: 'err-' + Date.now(),
        role: 'assistant',
        text: 'Disculpa, no pude procesar la respuesta en este momento. Inténtalo nuevamente.',
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      });
    }
  } catch (error) {
    console.error('Error en AI Chat:', error);
    messages.value.push({
      id: 'err-' + Date.now(),
      role: 'assistant',
      text: 'Ocurrió un error al contactar al asistente. Por favor verifica tu conexión y vuelve a intentar.',
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    });
  } finally {
    isLoading.value = false;
    await scrollToBottom();
  }
}

function clearChat() {
  initWelcomeMessage();
}

async function scrollToBottom() {
  await nextTick();
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
  }
}

// Enfocar input al abrir
watch(() => props.visible, (val) => {
  if (val) {
    nextTick(() => {
      scrollToBottom();
      if (inputRef.value) {
        inputRef.value.focus();
      }
    });
  }
});

// Formateador simple de Markdown seguro para el chat
function renderMarkdown(text) {
  if (!text) return '';
  
  let formatted = text
    // Escapar etiquetas HTML
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    // Negritas
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    // Cursiva
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    // Títulos H3
    .replace(/### (.*?)\n/g, '<h4 class="font-bold text-sm text-orange-600 dark:text-orange-400 mt-2 mb-1">$1</h4>')
    // Líneas horizontales
    .replace(/---\n?/g, '<hr class="my-2 border-slate-200 dark:border-slate-700" />')
    // Viñetas
    .replace(/^[•\-\*] (.*$)/gim, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-orange-500 font-bold shrink-0">•</span><span>$1</span></div>')
    // Saltos de línea
    .replace(/\n/g, '<br />');

  return formatted;
}
</script>

<template>
  <div>
    <!-- Backdrop Overlay -->
    <transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="visible"
        @click="closeChat"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 transition-opacity"
        aria-hidden="true"
      ></div>
    </transition>

    <!-- Sliding Sidebar Drawer -->
    <aside
      :class="[
        'fixed inset-y-0 right-0 z-50 w-full sm:w-[440px] md:w-[460px] bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col transform transition-transform duration-300 ease-in-out font-sans',
        visible ? 'translate-x-0' : 'translate-x-full'
      ]"
      role="dialog"
      aria-label="Chat con Asistente de IA"
    >
      <!-- Header -->
      <div class="h-18 px-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/80 dark:bg-slate-950/50 backdrop-blur shrink-0">
        <div class="flex items-center gap-3">
          <div class="relative">
            <img
              src="/images/logo-light.jpg"
              alt="Las Brazas"
              class="w-11 h-11 rounded-2xl object-cover shadow-md border border-slate-200 dark:hidden"
            />
            <img
              src="/images/logo-dark.jpg"
              alt="Las Brazas"
              class="w-11 h-11 rounded-2xl object-cover shadow-md border border-slate-700 hidden dark:block"
            />
            <!-- Online status green dot -->
            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></span>
          </div>
          <div>
            <div class="flex items-center gap-1.5">
              <h2 class="font-bold text-sm text-slate-900 dark:text-white">Chef Virtual Las Brazas</h2>
              <span class="px-1.5 py-0.2 bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-500/20 rounded text-[10px] font-extrabold uppercase">IA</span>
            </div>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
              En línea • Conectado a tu cuenta
            </p>
          </div>
        </div>

        <!-- Header Actions -->
        <div class="flex items-center gap-1">
          <button
            type="button"
            @click="clearChat"
            class="p-2 text-slate-400 hover:text-orange-500 hover:bg-slate-200/50 dark:hover:bg-slate-800 rounded-xl transition-colors text-xs flex items-center gap-1"
            title="Reiniciar conversación"
          >
            <RotateCcw class="w-4 h-4" />
          </button>
          <button
            type="button"
            @click="closeChat"
            class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-200/50 dark:hover:bg-slate-800 rounded-xl transition-colors"
            title="Cerrar chat (Esc)"
          >
            <X class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Messages Thread Area -->
      <div
        ref="chatContainer"
        class="flex-1 p-4 sm:p-5 overflow-y-auto space-y-4 bg-slate-50/40 dark:bg-slate-950/30"
      >
        <div
          v-for="msg in messages"
          :key="msg.id"
          :class="[
            'flex gap-2.5 max-w-[90%]',
            msg.role === 'user' ? 'ml-auto flex-row-reverse' : 'mr-auto'
          ]"
        >
          <!-- Assistant Avatar -->
          <div
            v-if="msg.role === 'assistant'"
            class="w-7 h-7 rounded-xl bg-orange-600/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0 mt-0.5"
          >
            <Bot class="w-4 h-4" />
          </div>

          <!-- User Avatar -->
          <div
            v-else
            class="w-7 h-7 rounded-xl bg-slate-800 text-white flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold"
          >
            {{ userName.charAt(0) }}
          </div>

          <!-- Message Bubble -->
          <div class="space-y-1">
            <div
              :class="[
                'p-3.5 text-xs sm:text-[13px] leading-relaxed',
                msg.role === 'user'
                  ? 'bg-gradient-to-r from-orange-600 to-amber-500 text-white rounded-2xl rounded-tr-none shadow-md shadow-orange-600/20'
                  : 'bg-white dark:bg-slate-800/90 text-slate-800 dark:text-slate-200 rounded-2xl rounded-tl-none border border-slate-200/80 dark:border-slate-700/60 shadow-sm'
              ]"
            >
              <div v-html="renderMarkdown(msg.text)" class="prose prose-sm dark:prose-invert max-w-none"></div>

              <!-- Tarjetas interactivas de platillos sugeridos por el Agente IA -->
              <div v-if="msg.suggestedItems && msg.suggestedItems.length > 0" class="mt-3 pt-2.5 border-t border-slate-200/80 dark:border-slate-700/70 space-y-2">
                <div
                  v-for="item in msg.suggestedItems"
                  :key="item.dish_id"
                  class="flex items-center justify-between gap-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200/70 dark:border-slate-700"
                >
                  <div class="min-w-0 flex-1">
                    <p class="font-bold text-xs text-slate-900 dark:text-white truncate">
                      {{ item.quantity }}x {{ item.name }}
                    </p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">
                      ${{ Number(item.price).toFixed(2) }} c/u {{ item.notes ? `• ${item.notes}` : '' }}
                    </p>
                  </div>
                  <button
                    type="button"
                    @click="addSuggestedToCart([item])"
                    class="px-2.5 py-1 rounded-lg bg-orange-600 hover:bg-orange-500 text-white font-bold text-[11px] shrink-0 transition-colors cursor-pointer"
                  >
                    + Carrito
                  </button>
                </div>

                <button
                  v-if="msg.suggestedItems.length > 1"
                  type="button"
                  @click="addSuggestedToCart(msg.suggestedItems)"
                  class="w-full py-2 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white font-bold text-xs shadow-sm transition-all cursor-pointer"
                >
                  🛒 Agregar los {{ msg.suggestedItems.length }} platillos al carrito
                </button>
              </div>

              <!-- Botones interactivos de cupones sugeridos -->
              <div v-if="msg.suggestedCoupons && msg.suggestedCoupons.length > 0" class="mt-2.5 pt-2 border-t border-slate-200/80 dark:border-slate-700/70 flex flex-wrap gap-1.5">
                <button
                  v-for="c in msg.suggestedCoupons"
                  :key="c.code"
                  type="button"
                  @click="applySuggestedCoupon(c.code)"
                  class="px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 font-bold text-[11px] transition-colors cursor-pointer"
                >
                  🎟️ Aplicar {{ c.code }}
                </button>
              </div>
            </div>

            <!-- Timestamp -->
            <p
              :class="[
                'text-[10px] text-slate-400 px-1',
                msg.role === 'user' ? 'text-right' : 'text-left'
              ]"
            >
              {{ msg.timestamp }}
            </p>
          </div>
        </div>

        <!-- Typing Loading Bubble -->
        <div v-if="isLoading" class="flex gap-2.5 mr-auto max-w-[85%]">
          <div class="w-7 h-7 rounded-xl bg-orange-600/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0 mt-0.5">
            <Sparkles class="w-4 h-4 animate-spin" />
          </div>
          <div class="p-3.5 rounded-2xl rounded-tl-none bg-white dark:bg-slate-800/90 border border-slate-200/80 dark:border-slate-700/60 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2 shadow-sm">
            <span>El Chef está preparando tu respuesta</span>
            <div class="flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-bounce"></span>
              <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-bounce [animation-delay:0.2s]"></span>
              <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-bounce [animation-delay:0.4s]"></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Action Prompt Chips -->
      <div class="px-4 py-2 bg-white dark:bg-slate-900 border-t border-slate-200/70 dark:border-slate-800 shrink-0">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 flex items-center gap-1">
          <Sparkles class="w-3 h-3 text-orange-500" /> Consultas rápidas sugeridas:
        </p>
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
          <button
            v-for="(sug, idx) in quickSuggestions"
            :key="idx"
            type="button"
            @click="sendSuggestion(sug.prompt)"
            :disabled="isLoading"
            class="text-[11px] whitespace-nowrap px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-300 dark:bg-slate-800 dark:hover:bg-slate-800/90 dark:hover:text-orange-400 dark:hover:border-orange-500/40 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition-colors shrink-0 disabled:opacity-50"
          >
            {{ sug.label }}
          </button>
        </div>
      </div>

      <!-- Input Bar -->
      <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
        <form @submit.prevent="sendMessage" class="flex items-center gap-2">
          <div class="relative flex-1">
            <input
              ref="inputRef"
              type="text"
              v-model="inputMessage"
              placeholder="Pregúntame sobre el menú, tus pedidos o reservas..."
              :disabled="isLoading"
              class="w-full h-11 pl-4 pr-10 text-xs sm:text-sm bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition-colors disabled:opacity-60"
            />
            <button
              v-if="inputMessage"
              type="button"
              @click="inputMessage = ''"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <button
            type="button"
            @click="toggleVoiceChat"
            :title="isListening ? 'Detener dictado' : 'Hablar con el Agente IA por voz'"
            :class="[
              'h-11 w-11 rounded-xl flex items-center justify-center transition-all shrink-0 border cursor-pointer',
              isListening
                ? 'bg-red-500 text-white border-red-400 animate-pulse shadow-md shadow-red-500/30'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:text-orange-500 hover:border-orange-500/40'
            ]"
          >
            <MicOff v-if="isListening" class="w-4 h-4" />
            <Mic v-else class="w-4 h-4" />
          </button>

          <Button
            type="submit"
            icon="pi pi-send"
            :loading="isLoading"
            :disabled="!inputMessage.trim() || isLoading"
            class="h-11 w-11 p-0 flex items-center justify-center bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white rounded-xl border-none shadow-md shadow-orange-600/25 shrink-0 transition-transform active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
          />
        </form>
        <p class="text-[10px] text-slate-400 text-center mt-2">
          Presiona <kbd class="px-1 py-0.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-[9px] font-mono">Enter</kbd> para enviar • Las respuestas consideran únicamente tu cuenta y el menú oficial.
        </p>
      </div>
    </aside>
  </div>
</template>

<style scoped>
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
