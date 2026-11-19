<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import {
  Sparkles,
  Bot,
  X,
  Send,
  Mic,
  RotateCcw,
  ArrowUpRight,
  Flame,
  Building2,
  Trash2,
  PenLine,
  Loader2,
  AlertCircle
} from 'lucide-vue-next';
import { useVoiceRecorder } from '../composables/useVoiceRecorder';
import VoiceAudioBadge from './VoiceAudioBadge.vue';

const props = defineProps({
  portal: {
    type: String,
    default: 'restaurant', // 'restaurant' | 'corporate'
  },
});

const isOpen = ref(false);
const isLoading = ref(false);
const inputMessage = ref('');
const messages = ref([]);
const scrollAreaRef = ref(null);
const inputRef = ref(null);

const isCorporate = computed(() => props.portal === 'corporate');

const {
  isRecording,
  isTranscribing,
  formattedDuration,
  audioLevels,
  voiceError,
  startRecording,
  stopAndTranscribe,
  cancelRecording,
  clearError: clearVoiceError,
} = useVoiceRecorder({
  endpoint: '/ai/public-transcribe',
  context: props.portal,
  maxDurationSec: 60,
  onTranscribed: ({ transcript, mode, audioUrl, durationLabel }) => {
    if (mode === 'dictate') {
      inputMessage.value = inputMessage.value.trim()
        ? `${inputMessage.value.trim()} ${transcript}`
        : transcript;
      nextTick(() => inputRef.value?.focus());
    } else {
      sendMessage(transcript, { audioUrl, audioDuration: durationLabel });
    }
  },
});

const quickPrompts = computed(() => {
  if (isCorporate.value) {
    return [
      { label: '🎯 Misión y Visión', prompt: '¿Cuál es la misión, visión y slogan de BrazasTech?' },
      { label: '💼 Planes SaaS', prompt: 'Compárame los planes SaaS (Starter, Pro y Enterprise) y precios anuales' },
      { label: '⚙️ Módulos POS/KDS/IA', prompt: '¿Qué incluye el ecosistema digital POS, KDS, App Móvil e IA?' },
      { label: '📜 Términos y Privacidad', prompt: 'Explícame los términos y condiciones y la política de privacidad' },
      { label: '📍 Ubicación y Contacto', prompt: '¿Dónde están ubicados y cómo solicito una cotización SaaS?' },
      { label: '🔐 Acceso Personal', prompt: '¿Cómo ingresa el personal del restaurante y qué roles existen?' },
    ];
  }

  return [
    { label: '🥩 Recomiéndame cortes', prompt: '¿Qué cortes a la parrilla y precios me recomiendas hoy?' },
    { label: '📍 Ubicación y Horarios', prompt: '¿Dónde están ubicados, cuál es su teléfono y horarios de atención?' },
    { label: '📅 Reservas y Tolerancia', prompt: '¿Cómo funcionan las reservas, las zonas y los minutos de tolerancia?' },
    { label: '📜 Términos y Privacidad', prompt: '¿Cuáles son los términos y condiciones y la política de privacidad?' },
    { label: '🛵 Delivery y Cupones', prompt: '¿Cómo funciona el delivery con PIN y qué cupones activos tienen?' },
    { label: '🚀 Misión, Visión y SaaS', prompt: '¿Cuál es la misión, visión y qué planes SaaS ofrece BrazasTech?' },
  ];
});

function buildWelcomeMessage() {
  if (isCorporate.value) {
    return {
      id: 'welcome-corp',
      role: 'assistant',
      text: '¡Hola! 👋 Soy el **Agente Inteligente de BrazasTech Cloud SaaS**.\n\nPuedes preguntarme cualquier duda sobre nuestra **Misión, Visión, Planes SaaS, Facturación SIAT, Términos, Política de Privacidad, Seguridad, Ubicación o Acceso de Personal**.',
      actions: [
        { label: 'Ver Planes SaaS', href: '/empresa#modelo-saas', kind: 'blue' },
        { label: 'Solicitar Cotización', href: '/empresa#cotizacion', kind: 'orange' },
      ],
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    };
  }

  return {
    id: 'welcome-rest',
    role: 'assistant',
    text: '¡Hola! 👋 Soy el **Asistente Inteligente de Restaurante Las Brazas**. 🔥🥩\n\nPregúntame lo que necesites: **cortes y precios del menú en vivo, ubicación, horarios, reservas, delivery con PIN, términos y condiciones, política de privacidad o incluso sobre nuestra plataforma SaaS**.',
    actions: [
      { label: 'Ver Menú Digital', href: '/#menu', kind: 'orange' },
      { label: 'Términos', href: '/terms', kind: 'slate' },
      { label: 'Privacidad', href: '/privacy', kind: 'emerald' },
    ],
    timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
  };
}

function resetChat() {
  messages.value = [buildWelcomeMessage()];
}

function toggleOpen() {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    nextTick(() => {
      scrollToBottom();
      inputRef.value?.focus();
    });
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (scrollAreaRef.value) {
      scrollAreaRef.value.scrollTop = scrollAreaRef.value.scrollHeight;
    }
  });
}

async function sendMessage(presetPrompt = null, voiceMeta = null) {
  const text = (presetPrompt ?? inputMessage.value).trim();
  if (!text || isLoading.value) return;

  inputMessage.value = '';
  clearVoiceError();
  const historyPayload = messages.value
    .filter((m) => m.id !== 'welcome-rest' && m.id !== 'welcome-corp')
    .slice(-8)
    .map((m) => ({ role: m.role, content: m.text }));

  messages.value.push({
    id: 'u-' + Date.now(),
    role: 'user',
    text,
    audioUrl: voiceMeta?.audioUrl || null,
    audioDuration: voiceMeta?.audioDuration || null,
    timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
  });
  scrollToBottom();

  isLoading.value = true;
  try {
    const res = await axios.post('/ai/public-chat', {
      message: text,
      portal: props.portal,
      history: historyPayload,
    });

    messages.value.push({
      id: 'a-' + Date.now(),
      role: 'assistant',
      text: res.data?.reply || 'No pude generar una respuesta en este momento.',
      actions: res.data?.suggested_actions || [],
      timestamp: res.data?.timestamp || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    });
  } catch (err) {
    const status = err.response?.status;
    messages.value.push({
      id: 'err-' + Date.now(),
      role: 'assistant',
      text: status === 429
        ? 'Has enviado varias consultas muy rápido. Espera unos segundos e intenta de nuevo.'
        : 'Ocurrió un inconveniente temporal de conexión. Por favor intenta nuevamente o escríbenos en Contáctenos.',
      actions: [{ label: 'Ir a Contáctenos', href: '/#contacto', kind: 'orange' }],
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    });
  } finally {
    isLoading.value = false;
    scrollToBottom();
  }
}

watch(isOpen, (open) => {
  if (!open && isRecording.value) {
    cancelRecording();
  }
});

function renderMarkdown(raw) {
  if (!raw) return '';
  return raw
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/^###\s+(.*)$/gm, '<h4 class="font-bold text-xs uppercase tracking-wider text-orange-600 dark:text-orange-400 mt-2 mb-1">$1</h4>')
    .replace(/^\s*[\*\-]\s+/gm, '• ')
    .replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-[11px] font-mono">$1</code>')
    .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900 dark:text-white">$1</strong>')
    .replace(/\*([^*\n]+)\*/g, '<em>$1</em>')
    .replace(/\n/g, '<br />');
}

function actionClass(kind) {
  if (kind === 'emerald') {
    return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/20';
  }
  if (kind === 'blue') {
    return 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-500/30 hover:bg-blue-500/20';
  }
  if (kind === 'slate') {
    return 'bg-slate-200/70 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700';
  }
  return 'bg-orange-500/10 text-orange-700 dark:text-orange-300 border-orange-500/30 hover:bg-orange-500/20';
}

function handleEscape(e) {
  if (e.key === 'Escape' && isOpen.value) {
    isOpen.value = false;
  }
}

onMounted(() => {
  resetChat();
  window.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleEscape);
});
</script>

<template>
  <div class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 flex flex-col items-end">
    <!-- Panel de Chat Desplegable -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-4 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-4 scale-95"
    >
      <div
        v-if="isOpen"
        class="mb-3 w-[calc(100vw-2rem)] sm:w-[410px] h-[76vh] sm:h-[590px] max-h-[650px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl flex flex-col overflow-hidden"
      >
        <!-- Header del Agente -->
        <div
          :class="[
            'px-4 py-3.5 text-white flex items-center justify-between gap-3 shrink-0',
            isCorporate
              ? 'bg-gradient-to-r from-slate-900 via-blue-900 to-orange-600'
              : 'bg-gradient-to-r from-orange-600 via-amber-600 to-orange-500'
          ]"
        >
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur border border-white/25 flex items-center justify-center shrink-0 relative">
              <Building2 v-if="isCorporate" class="w-5 h-5 text-white" />
              <Flame v-else class="w-5 h-5 text-white" />
              <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 border-2 border-slate-900"></span>
            </div>
            <div class="min-w-0">
              <h3 class="font-extrabold text-sm leading-tight truncate">
                {{ isCorporate ? 'Agente IA • BrazasTech SaaS' : 'Asistente IA • Las Brazas' }}
              </h3>
              <p class="text-[11px] text-white/85 truncate">
                En línea • Respuestas instantáneas 24/7
              </p>
            </div>
          </div>

          <div class="flex items-center gap-1 shrink-0">
            <button
              type="button"
              @click="resetChat"
              class="p-1.5 rounded-lg hover:bg-white/15 transition-colors cursor-pointer"
              title="Reiniciar conversación"
            >
              <RotateCcw class="w-4 h-4" />
            </button>
            <button
              type="button"
              @click="isOpen = false"
              class="p-1.5 rounded-lg hover:bg-white/15 transition-colors cursor-pointer"
              title="Cerrar chat"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Sugerencias Rápidas -->
        <div class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950/70 border-b border-slate-200 dark:border-slate-800 flex items-center gap-1.5 overflow-x-auto shrink-0">
          <button
            v-for="chip in quickPrompts"
            :key="chip.label"
            type="button"
            @click="sendMessage(chip.prompt)"
            :disabled="isLoading"
            class="px-2.5 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-orange-500 text-[11px] font-semibold text-slate-700 dark:text-slate-200 whitespace-nowrap transition-colors cursor-pointer shrink-0 disabled:opacity-50"
          >
            {{ chip.label }}
          </button>
        </div>

        <!-- Área de Mensajes -->
        <div ref="scrollAreaRef" class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-slate-50/50 dark:bg-slate-950/40">
          <div
            v-for="msg in messages"
            :key="msg.id"
            :class="['flex flex-col', msg.role === 'user' ? 'items-end' : 'items-start']"
          >
            <div
              :class="[
                'max-w-[88%] rounded-2xl px-3.5 py-2.5 text-xs leading-relaxed shadow-xs',
                msg.role === 'user'
                  ? 'bg-gradient-to-r from-orange-600 to-amber-500 text-white rounded-br-sm font-medium'
                  : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-bl-sm'
              ]"
            >
              <div v-if="msg.role === 'assistant'" v-html="renderMarkdown(msg.text)"></div>
              <div v-else>
                <VoiceAudioBadge
                  v-if="msg.audioUrl"
                  :audio-url="msg.audioUrl"
                  :duration="msg.audioDuration || '0:02'"
                />
                <div>{{ msg.text }}</div>
              </div>

              <!-- Botones de Acción Rápida sugeridos por la IA -->
              <div v-if="msg.actions && msg.actions.length" class="flex flex-wrap gap-1.5 mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800">
                <template v-for="act in msg.actions" :key="act.href">
                  <a
                    v-if="act.href.includes('#')"
                    :href="act.href"
                    @click="isOpen = false"
                    :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border text-[11px] font-bold transition-colors', actionClass(act.kind)]"
                  >
                    <span>{{ act.label }}</span>
                    <ArrowUpRight class="w-3 h-3" />
                  </a>
                  <Link
                    v-else
                    :href="act.href"
                    @click="isOpen = false"
                    :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border text-[11px] font-bold transition-colors', actionClass(act.kind)]"
                  >
                    <span>{{ act.label }}</span>
                    <ArrowUpRight class="w-3 h-3" />
                  </Link>
                </template>
              </div>
            </div>
            <span class="text-[10px] text-slate-400 mt-1 px-1">{{ msg.timestamp }}</span>
          </div>

          <!-- Indicador de escritura -->
          <div v-if="isLoading" class="flex items-start">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl rounded-bl-sm px-4 py-3 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
              <Bot class="w-4 h-4 text-orange-500 animate-bounce" />
              <span>Consultando información en tiempo real...</span>
            </div>
          </div>
        </div>

        <!-- Aviso de error de micrófono -->
        <div
          v-if="voiceError"
          class="px-3 py-2 bg-red-50 dark:bg-red-950/60 border-t border-red-200 dark:border-red-900 text-[11px] text-red-700 dark:text-red-300 flex items-center justify-between gap-2 shrink-0"
        >
          <div class="flex items-center gap-1.5 min-w-0">
            <AlertCircle class="w-3.5 h-3.5 shrink-0 text-red-500" />
            <span class="leading-snug">{{ voiceError }}</span>
          </div>
          <button type="button" @click="clearVoiceError" class="p-0.5 hover:text-red-900 dark:hover:text-white cursor-pointer shrink-0">
            <X class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- Formulario de Envío / Grabadora de Audio y Dictado -->
        <div class="p-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 shrink-0">
          <!-- Estado: Transcribiendo Audio -->
          <div
            v-if="isTranscribing"
            class="flex items-center justify-center gap-2.5 h-10 px-3 rounded-xl bg-orange-50 dark:bg-orange-950/40 border border-orange-200 dark:border-orange-500/30 text-xs font-semibold text-orange-700 dark:text-orange-300"
          >
            <Loader2 class="w-4 h-4 animate-spin shrink-0" />
            <span>Procesando y transcribiendo tu audio...</span>
          </div>

          <!-- Estado: Grabando Audio en Vivo -->
          <div
            v-else-if="isRecording"
            class="flex items-center gap-2 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-500/40 rounded-xl px-2.5 py-1.5"
          >
            <button
              type="button"
              @click="cancelRecording"
              class="p-2 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors cursor-pointer shrink-0"
              title="Cancelar grabación"
            >
              <Trash2 class="w-4 h-4" />
            </button>

            <div class="flex-1 flex items-center gap-2 min-w-0">
              <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse shrink-0"></span>
              <span class="text-xs font-mono font-bold text-red-600 dark:text-red-400 shrink-0">{{ formattedDuration }}</span>
              <div class="flex items-center gap-0.5 h-5 flex-1 max-w-[90px]">
                <span
                  v-for="(lvl, i) in audioLevels"
                  :key="i"
                  class="w-1 rounded-full bg-red-500/80 dark:bg-red-400 transition-all duration-75"
                  :style="{ height: `${Math.max(20, lvl)}%` }"
                ></span>
              </div>
            </div>

            <button
              type="button"
              @click="stopAndTranscribe('dictate')"
              class="p-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-orange-500 hover:text-orange-600 transition-colors cursor-pointer shrink-0"
              title="Transcribir al cuadro de texto (para editar antes de enviar)"
            >
              <PenLine class="w-4 h-4" />
            </button>

            <button
              type="button"
              @click="stopAndTranscribe('send')"
              class="px-3 py-2 rounded-lg bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white text-xs font-bold flex items-center gap-1 shadow-md cursor-pointer shrink-0"
              title="Enviar nota de voz al asistente"
            >
              <Send class="w-3.5 h-3.5" />
              <span>Enviar</span>
            </button>
          </div>

          <!-- Estado Normal: Texto + Botón de Micrófono -->
          <form
            v-else
            @submit.prevent="sendMessage()"
            class="flex items-center gap-2"
          >
            <button
              type="button"
              @click="startRecording"
              :disabled="isLoading"
              class="p-2.5 rounded-xl border bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:text-orange-500 hover:border-orange-400 transition-colors cursor-pointer shrink-0 disabled:opacity-45"
              title="Dictar o enviar audio por micrófono"
            >
              <Mic class="w-4 h-4" />
            </button>

            <input
              ref="inputRef"
              v-model="inputMessage"
              type="text"
              maxlength="1000"
              placeholder="Escribe o usa el micrófono para dictar..."
              class="flex-1 min-w-0 bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:border-orange-500"
              :disabled="isLoading"
            />

            <button
              type="submit"
              :disabled="isLoading || !inputMessage.trim()"
              class="p-2.5 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white shadow-md disabled:opacity-45 cursor-pointer shrink-0 transition-all"
              title="Enviar mensaje"
            >
              <Send class="w-4 h-4" />
            </button>
          </form>
        </div>
      </div>
    </transition>

    <!-- Botón Flotante (FAB) para desplegar el Agente Inteligente -->
    <button
      type="button"
      @click="toggleOpen"
      :class="[
        'flex items-center gap-2.5 px-4 py-3 rounded-full text-white font-bold text-xs sm:text-sm shadow-2xl transition-all duration-200 hover:scale-105 active:scale-95 cursor-pointer relative border border-white/20',
        isCorporate
          ? 'bg-gradient-to-r from-slate-900 via-blue-800 to-orange-600 shadow-blue-900/40'
          : 'bg-gradient-to-r from-orange-600 to-amber-500 shadow-orange-600/40'
      ]"
      :aria-label="isOpen ? 'Cerrar asistente inteligente' : 'Abrir asistente inteligente'"
    >
      <span class="relative flex h-2.5 w-2.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
      </span>
      <X v-if="isOpen" class="w-4 h-4" />
      <Sparkles v-else class="w-4 h-4 text-amber-200" />
      <span>{{ isOpen ? 'Cerrar Chat' : (isCorporate ? 'Asistente IA SaaS' : 'Asistente IA') }}</span>
    </button>
  </div>
</template>
