<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import axios from 'axios';
import AdminLayout from '../../layouts/AdminLayout.vue';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import {
  BarChart3,
  DollarSign,
  TrendingUp,
  Receipt,
  AlertTriangle,
  Award,
  PieChart,
  Percent,
  Calendar,
  FileDown,
  FileSpreadsheet,
  FileText,
  Sparkles,
  Bot,
  RotateCcw,
  Zap,
  Mic,
  MicOff,
} from 'lucide-vue-next';

const props = defineProps({
  summary: Object,
  dailySales: Array,
  categoriesSales: Array,
  topDishes: Array,
  profitability: Array,
});

const toast = useToast();

// ── 1. ASISTENTE IA (GEMINI / NLP) + DICTADO POR VOZ STATE ──────────
const aiPrompt = ref('');
const aiLoading = ref(false);
const aiResult = ref(null);
const aiError = ref(null);
const aiActiveChartType = ref('bar');

// Reconocimiento de Voz (Web Speech API)
const isListening = ref(false);
let recognitionInstance = null;

function toggleVoiceDictation() {
  if (isListening.value && recognitionInstance) {
    recognitionInstance.stop();
    isListening.value = false;
    return;
  }

  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognition) {
    toast.add({
      severity: 'warn',
      summary: 'Dictado no compatible',
      detail: 'Tu navegador no soporta reconocimiento de voz nativo (usa Chrome, Edge o Brave).',
      life: 3500,
    });
    return;
  }

  recognitionInstance = new SpeechRecognition();
  recognitionInstance.lang = 'es-ES';
  recognitionInstance.continuous = false;
  recognitionInstance.interimResults = true;

  recognitionInstance.onstart = () => {
    isListening.value = true;
    aiError.value = null;
    toast.add({
      severity: 'info',
      summary: '🎙️ Micrófono Activo',
      detail: 'Habla ahora para dictar tu consulta de reporte...',
      life: 2500,
    });
  };

  recognitionInstance.onresult = (event) => {
    let transcript = '';
    for (let i = 0; i < event.results.length; i++) {
      transcript += event.results[i][0].transcript;
    }
    aiPrompt.value = transcript;
  };

  recognitionInstance.onerror = (event) => {
    isListening.value = false;
    if (event.error !== 'no-speech' && event.error !== 'aborted') {
      toast.add({
        severity: 'error',
        summary: 'Error de Micrófono',
        detail: 'Verifica los permisos del micrófono en tu navegador.',
        life: 3000,
      });
    }
  };

  recognitionInstance.onend = () => {
    isListening.value = false;
    if (aiPrompt.value.trim().length > 2) {
      sendAiQuery();
    }
  };

  recognitionInstance.start();
}

onBeforeUnmount(() => {
  if (recognitionInstance && isListening.value) {
    recognitionInstance.stop();
  }
});

const suggestedQueries = [
  '🥩 Ventas de Arrachera esta semana en barras',
  '📅 Ventas de hoy por hora',
  '📈 Comparativa de ventas de los últimos 30 días en líneas',
  '🥧 Distribución general en gráfica de pastel',
];

function applySuggestion(query) {
  aiPrompt.value = query.replace(/^[^\w\s¿¡áéíóúÁÉÍÓÚñÑ]+/, '').trim();
  sendAiQuery();
}

async function sendAiQuery() {
  if (!aiPrompt.value.trim() || aiLoading.value) return;

  aiLoading.value = true;
  aiError.value = null;

  try {
    const response = await axios.post('/reports/ai-query', {
      prompt: aiPrompt.value.trim(),
    });

    if (response.data.success) {
      aiResult.value = response.data;
      const suggested = response.data.result?.recommended_charts || [];
      if (suggested.includes('linea') || suggested.includes('line')) {
        aiActiveChartType.value = 'line';
      } else if (suggested.includes('pastel') || suggested.includes('pie') || suggested.includes('dona')) {
        aiActiveChartType.value = 'doughnut';
      } else {
        aiActiveChartType.value = 'bar';
      }

      toast.add({
        severity: 'success',
        summary: 'Consulta IA Procesada',
        detail: `Datos generados para: ${response.data.result.dish_name}`,
        life: 2500,
      });
    } else {
      aiError.value = response.data.message || 'No se pudieron obtener datos.';
    }
  } catch (err) {
    aiError.value = err.response?.data?.message || 'Error al comunicarse con el asistente de IA.';
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo procesar la consulta con IA',
      life: 3000,
    });
  } finally {
    aiLoading.value = false;
  }
}

function clearAiResult() {
  aiResult.value = null;
  aiPrompt.value = '';
  aiError.value = null;
}

// Exportar el resultado dinámico de la consulta IA a CSV
function exportAiResultCsv() {
  if (!aiResult.value?.result) return;
  const res = aiResult.value.result;
  const labels = res.chart_data?.labels || [];
  const sales = res.chart_data?.sales || [];
  const quantities = res.chart_data?.quantities || [];

  let csv = '\uFEFF';
  csv += `Consulta IA,"${(aiResult.value.prompt_original || '').replace(/"/g, '""')}"\n`;
  csv += `Platillo / Serie,"${res.dish_name}"\n`;
  csv += `Rango,"${res.time_range.start} - ${res.time_range.end}"\n\n`;
  csv += 'Periodo,Unidades Vendidas,Ventas ($)\n';

  labels.forEach((lbl, idx) => {
    csv += `"${lbl}",${quantities[idx] || 0},${(sales[idx] || 0).toFixed(2)}\n`;
  });

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `consulta_ia_${Date.now()}.csv`;
  link.click();
}

// Dynamic Chart Data for AI Result
const aiChartData = computed(() => {
  if (!aiResult.value?.result?.chart_data) return null;
  const res = aiResult.value.result;
  const labels = res.chart_data.labels;
  const sales = res.chart_data.sales;

  if (aiActiveChartType.value === 'doughnut') {
    const colors = ['#8b5cf6', '#ec4899', '#3b82f6', '#10b981', '#f59e0b', '#ea580c', '#06b6d4', '#6366f1'];
    return {
      labels: labels,
      datasets: [
        {
          data: sales,
          backgroundColor: colors.slice(0, labels.length),
          borderWidth: 2,
        },
      ],
    };
  }

  return {
    labels: labels,
    datasets: [
      {
        label: `Ventas ($) - ${res.dish_name}`,
        backgroundColor: aiActiveChartType.value === 'line' ? 'rgba(139, 92, 246, 0.2)' : 'rgba(139, 92, 246, 0.75)',
        borderColor: '#8b5cf6',
        borderWidth: 2,
        fill: aiActiveChartType.value === 'line',
        tension: 0.35,
        borderRadius: 8,
        data: sales,
      },
    ],
  };
});

const aiChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: {
        color: '#8b5cf6',
        font: { family: 'sans-serif', size: 12, weight: 'bold' },
      },
    },
    tooltip: {
      callbacks: {
        label: (context) => ` Ventas: $${Number(context.raw || 0).toFixed(2)}`,
      },
    },
  },
  scales: {
    x: {
      ticks: { color: '#64748b' },
      grid: { color: 'rgba(148, 163, 184, 0.2)' },
    },
    y: {
      ticks: {
        color: '#64748b',
        callback: (value) => `$${value}`,
      },
      grid: { color: 'rgba(148, 163, 184, 0.2)' },
    },
  },
};

// ── 2. CHARTS ESTÁNDAR ──────────────────────────────────────────
const dailySalesChartData = computed(() => {
  return {
    labels: props.dailySales.map(d => d.date),
    datasets: [
      {
        label: 'Ventas ($)',
        backgroundColor: 'rgba(234, 88, 12, 0.75)',
        borderColor: '#ea580c',
        borderWidth: 2,
        borderRadius: 8,
        data: props.dailySales.map(d => d.amount),
      },
    ],
  };
});

const dailySalesChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: {
        color: '#64748b',
        font: { family: 'sans-serif', size: 12 },
      },
    },
    tooltip: {
      callbacks: {
        label: (context) => ` Ventas: $${Number(context.raw || 0).toFixed(2)}`,
      },
    },
  },
  scales: {
    x: {
      ticks: { color: '#64748b' },
      grid: { color: 'rgba(148, 163, 184, 0.2)' },
    },
    y: {
      ticks: {
        color: '#64748b',
        callback: (value) => `$${value}`,
      },
      grid: { color: 'rgba(148, 163, 184, 0.2)' },
    },
  },
};

const categoryChartData = computed(() => {
  const colors = ['#ea580c', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'];
  return {
    labels: props.categoriesSales.map(c => c.category_name),
    datasets: [
      {
        data: props.categoriesSales.map(c => c.total_sales),
        backgroundColor: colors.slice(0, props.categoriesSales.length),
        borderWidth: 2,
      },
    ],
  };
});

const categoryChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
      labels: {
        color: '#64748b',
        font: { family: 'sans-serif', size: 11 },
      },
    },
    tooltip: {
      callbacks: {
        label: (context) => ` ${context.label}: $${Number(context.raw || 0).toFixed(2)}`,
      },
    },
  },
};

function getMarginBadge(margin) {
  if (margin >= 65) return { severity: 'success', label: `${margin}% (Alto)` };
  if (margin >= 45) return { severity: 'warn', label: `${margin}% (Medio)` };
  return { severity: 'danger', label: `${margin}% (Bajo)` };
}
</script>

<template>
  <AdminLayout>
    <template #header>
      <div class="flex flex-wrap items-center justify-between gap-3 w-full">
        <span>Reportes y Analíticas Comerciales</span>

        <!-- Botones de Exportación: PDF, Excel, CSV -->
        <div class="flex flex-wrap items-center gap-2">
          <a
            href="/reports/export/pdf"
            target="_blank"
            class="px-3 py-1.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm hover:shadow-orange-600/20"
            title="Descargar Reporte en PDF"
          >
            <FileDown class="w-3.5 h-3.5" />
            <span>PDF</span>
          </a>

          <a
            href="/reports/export/excel"
            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm hover:shadow-emerald-600/20"
            title="Descargar Reporte en Excel (.xls)"
          >
            <FileSpreadsheet class="w-3.5 h-3.5" />
            <span>Excel</span>
          </a>

          <a
            href="/reports/export/csv"
            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm hover:shadow-blue-600/20"
            title="Descargar Reporte en CSV"
          >
            <FileText class="w-3.5 h-3.5" />
            <span>CSV</span>
          </a>
        </div>
      </div>
    </template>

    <div class="space-y-6">

      <!-- ── ASISTENTE IA CON GEMINI Y DICTADO POR VOZ ─────────────── -->
      <div class="relative overflow-hidden bg-gradient-to-r from-purple-50 via-indigo-50/70 to-purple-50 dark:from-purple-950/50 dark:via-slate-900 dark:to-indigo-950/50 border-2 border-purple-200 dark:border-purple-500/30 rounded-3xl p-6 shadow-md transition-colors duration-200">
        <!-- Glow effect background -->
        <div class="absolute -right-20 -top-20 w-60 h-60 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col space-y-4">
          <!-- Header del Asistente -->
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-purple-500/30">
                <Sparkles class="w-5 h-5" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Asistente IA de Reportes & Dictado por Voz</h3>
                  <Tag value="Gemini 2.5 Flash" severity="contrast" class="bg-purple-600/15 text-purple-700 dark:bg-purple-900/60 dark:text-purple-200 border border-purple-500/30 text-[10px]" />
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-400">
                  Escribe o usa el <strong>micrófono</strong> para pedir gráficas sobre ventas, platillos y periodos en lenguaje natural.
                </p>
              </div>
            </div>

            <button
              v-if="aiResult"
              @click="clearAiResult"
              class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700 text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-colors cursor-pointer"
            >
              <RotateCcw class="w-3.5 h-3.5" /> Limpiar
            </button>
          </div>

          <!-- Sugerencias Rápidas -->
          <div class="flex flex-wrap items-center gap-2 pt-1">
            <span class="text-[11px] font-bold text-purple-700 dark:text-purple-300 flex items-center gap-1">
              <Zap class="w-3.5 h-3.5" /> Sugerencias:
            </span>
            <button
              v-for="(sug, idx) in suggestedQueries"
              :key="idx"
              @click="applySuggestion(sug)"
              class="text-[11px] bg-white hover:bg-purple-600 hover:text-white text-slate-700 border border-purple-200 dark:bg-slate-800/90 dark:hover:bg-purple-600/40 dark:border-slate-700 dark:text-slate-300 dark:hover:text-purple-100 px-3 py-1 rounded-full transition-all cursor-pointer shadow-2xs"
            >
              {{ sug }}
            </button>
          </div>

          <!-- Input Box, Microphone Button & Send Button -->
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative flex-1">
              <input
                v-model="aiPrompt"
                @keyup.enter="sendAiQuery"
                type="text"
                :placeholder="isListening ? '🎙️ Escuchando tu voz... habla ahora...' : 'Ejemplo: ¿Cuánto vendimos de arrachera en los últimos 7 días en gráfico de barras?'"
                class="w-full bg-white dark:bg-slate-950/90 border-2 border-purple-300 dark:border-purple-500/30 focus:border-purple-600 dark:focus:border-purple-500 rounded-2xl py-3 pl-4 pr-24 text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none transition-colors shadow-xs"
                :disabled="aiLoading"
              />

              <!-- Microphone Voice Dictation Button inside Input -->
              <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                <button
                  type="button"
                  @click="toggleVoiceDictation"
                  :class="[
                    'p-2 rounded-xl text-xs font-bold flex items-center gap-1 transition-all cursor-pointer',
                    isListening
                      ? 'bg-red-600 text-white animate-pulse shadow-md shadow-red-500/40'
                      : 'bg-purple-100 hover:bg-purple-200 text-purple-700 dark:bg-purple-900/50 dark:hover:bg-purple-800 dark:text-purple-300'
                  ]"
                  :title="isListening ? 'Detener dictado por voz' : 'Dictar consulta por voz'"
                >
                  <MicOff v-if="isListening" class="w-4 h-4" />
                  <Mic v-else class="w-4 h-4" />
                  <span class="hidden md:inline text-[11px]">{{ isListening ? 'Escuchando...' : 'Dictar' }}</span>
                </button>
              </div>
            </div>

            <Button
              :loading="aiLoading"
              icon="pi pi-send"
              label="Consultar IA"
              class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 border-none text-white font-bold text-xs py-3.5 px-6 rounded-2xl shadow-lg shadow-purple-600/30 transition-all shrink-0"
              @click="sendAiQuery"
            />
          </div>

          <!-- Alerta de Error si ocurre -->
          <div v-if="aiError" class="p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-xs text-red-700 dark:text-red-300 flex items-center gap-2">
            <AlertTriangle class="w-4 h-4 shrink-0 text-red-500" />
            <span>{{ aiError }}</span>
          </div>

          <!-- RESULTADOS DINÁMICOS DE LA IA -->
          <div v-if="aiResult?.result" class="mt-4 pt-4 border-t border-purple-200 dark:border-purple-500/20 space-y-4">
            <!-- Header de Interpretación & Filtros -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 p-3 bg-white/80 dark:bg-purple-950/30 rounded-2xl border border-purple-200 dark:border-purple-500/20">
              <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-xs font-semibold text-purple-700 dark:text-purple-300">Consulta Interpretada:</span>
                  <Tag :value="aiResult.result.dish_name" severity="info" class="font-bold" />
                  <Tag :value="`Rango: ${aiResult.result.time_range.start} → ${aiResult.result.time_range.end}`" severity="secondary" class="text-[10px]" />
                </div>
                <p class="text-[11px] text-slate-600 dark:text-slate-400">
                  Motor: <strong class="text-purple-700 dark:text-purple-300">{{ aiResult.engine === 'gemini_ai' ? 'Gemini 2.5 Flash (NLP)' : 'Motor Analítico SQL' }}</strong>
                </p>
              </div>

              <!-- Selector de Tipo de Gráfico Dinámico + Exportar Consulta -->
              <div class="flex flex-wrap items-center gap-2">
                <div class="flex rounded-xl bg-slate-100 dark:bg-slate-900 p-1 border border-slate-200 dark:border-slate-700">
                  <button
                    @click="aiActiveChartType = 'bar'"
                    :class="['px-3 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer', aiActiveChartType === 'bar' ? 'bg-purple-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200']"
                  >
                    Barras
                  </button>
                  <button
                    @click="aiActiveChartType = 'line'"
                    :class="['px-3 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer', aiActiveChartType === 'line' ? 'bg-purple-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200']"
                  >
                    Líneas
                  </button>
                  <button
                    @click="aiActiveChartType = 'doughnut'"
                    :class="['px-3 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer', aiActiveChartType === 'doughnut' ? 'bg-purple-600 text-white' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200']"
                  >
                    Pastel
                  </button>
                </div>

                <button
                  @click="exportAiResultCsv"
                  class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer"
                  title="Exportar este resultado a CSV"
                >
                  <FileDown class="w-3.5 h-3.5" /> CSV IA
                </button>
              </div>
            </div>

            <!-- Mini KPIs de la Consulta IA -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div class="p-3 rounded-xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 text-center">
                <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">Total Recaudado</span>
                <h4 class="text-lg font-bold text-emerald-600 dark:text-emerald-400">${{ aiResult.result.statistics.total_sales.toFixed(2) }}</h4>
              </div>
              <div class="p-3 rounded-xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 text-center">
                <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">Porciones Vendidas</span>
                <h4 class="text-lg font-bold text-purple-600 dark:text-purple-300">{{ aiResult.result.statistics.total_units }} uds</h4>
              </div>
              <div class="p-3 rounded-xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 text-center">
                <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400">Promedio / Periodo</span>
                <h4 class="text-lg font-bold text-cyan-600 dark:text-cyan-400">${{ aiResult.result.statistics.average_per_period.toFixed(2) }}</h4>
              </div>
            </div>

            <!-- Canvas del Gráfico Dinámico de la IA -->
            <div class="bg-white dark:bg-slate-950/60 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 h-64">
              <Chart
                v-if="aiChartData"
                :type="aiActiveChartType"
                :data="aiChartData"
                :options="aiActiveChartType === 'doughnut' ? categoryChartOptions : aiChartOptions"
                class="h-full w-full"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- ── 1. TOP KPI SUMMARY CARDS ────────────────────────────────── -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Total Ingresos -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Histórico</span>
            <DollarSign class="w-4 h-4 text-emerald-500 dark:text-emerald-400" />
          </div>
          <h3 class="text-xl font-bold text-emerald-600 dark:text-emerald-400">${{ summary.total_revenue.toFixed(2) }}</h3>
        </div>

        <!-- Ventas Hoy -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Ventas Hoy</span>
            <TrendingUp class="w-4 h-4 text-orange-500 dark:text-orange-400" />
          </div>
          <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">${{ summary.today_revenue.toFixed(2) }}</h3>
        </div>

        <!-- Ventas Mes -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Este Mes</span>
            <Calendar class="w-4 h-4 text-blue-500 dark:text-blue-400" />
          </div>
          <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">${{ summary.month_revenue.toFixed(2) }}</h3>
        </div>

        <!-- Comandas Cobradas -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Comandas</span>
            <Receipt class="w-4 h-4 text-purple-500 dark:text-purple-400" />
          </div>
          <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ summary.completed_orders_count }}</h3>
        </div>

        <!-- Ticket Promedio -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Ticket Promedio</span>
            <Percent class="w-4 h-4 text-cyan-500 dark:text-cyan-400" />
          </div>
          <h3 class="text-xl font-bold text-cyan-600 dark:text-cyan-400">${{ summary.average_ticket.toFixed(2) }}</h3>
        </div>

        <!-- Pérdida por Mermas -->
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Costo Mermas</span>
            <AlertTriangle class="w-4 h-4 text-red-500 dark:text-red-400" />
          </div>
          <h3 class="text-xl font-bold text-red-600 dark:text-red-400">${{ summary.total_waste_loss.toFixed(2) }}</h3>
        </div>
      </div>

      <!-- ── 2. GRÁFICOS ESTÁNDAR ────────────────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Daily Sales Chart (8 Cols) -->
        <div class="lg:col-span-8 bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 transition-colors duration-200">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <BarChart3 class="w-5 h-5 text-orange-500" />
              <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Evolución de Ventas (Últimos 7 Días)</h3>
            </div>
          </div>
          <div class="h-64">
            <Chart type="bar" :data="dailySalesChartData" :options="dailySalesChartOptions" class="h-full w-full" />
          </div>
        </div>

        <!-- Category Distribution Chart (4 Cols) -->
        <div class="lg:col-span-4 bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 flex flex-col justify-between transition-colors duration-200">
          <div class="flex items-center gap-2 mb-4">
            <PieChart class="w-5 h-5 text-blue-500" />
            <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Ventas por Categoría</h3>
          </div>

          <div class="h-56 flex items-center justify-center">
            <Chart
              v-if="categoriesSales.length > 0"
              type="doughnut"
              :data="categoryChartData"
              :options="categoryChartOptions"
              class="h-full w-full"
            />
            <div v-else class="text-xs text-slate-500 italic">
              Sin datos de ventas por categoría aún.
            </div>
          </div>

          <p class="text-[11px] text-slate-500 text-center mt-2">
            Distribución proporcional del menú
          </p>
        </div>
      </div>

      <!-- ── 3. TABLAS ANALÍTICAS ────────────────────────────────────── -->
      <Tabs value="0" class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 transition-colors duration-200">
        <TabList class="border-b border-slate-200 dark:border-slate-800">
          <Tab value="0" class="text-slate-600 dark:text-slate-300 font-semibold">
            <div class="flex items-center gap-2">
              <Award class="w-4 h-4 text-amber-500 dark:text-amber-400" />
              <span>Top Platillos Más Vendidos</span>
            </div>
          </Tab>
          <Tab value="1" class="text-slate-600 dark:text-slate-300 font-semibold">
            <div class="flex items-center gap-2">
              <Percent class="w-4 h-4 text-emerald-500 dark:text-emerald-400" />
              <span>Matriz de Costo vs. Precio (Rentabilidad)</span>
            </div>
          </Tab>
        </TabList>

        <TabPanels>
          <!-- TAB 1: TOP DISHES -->
          <TabPanel value="0" class="pt-4">
            <DataTable :value="topDishes" class="p-datatable-sm" responsiveLayout="scroll">
              <Column header="Ranking" style="width: 80px">
                <template #body="{ index }">
                  <span
                    class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs"
                    :class="[
                      index === 0 ? 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/40' :
                      index === 1 ? 'bg-slate-400/20 text-slate-700 dark:text-slate-300 border border-slate-400/40' :
                      index === 2 ? 'bg-orange-700/20 text-orange-600 dark:text-orange-400 border border-orange-700/40' :
                      'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                    ]"
                  >
                    #{{ index + 1 }}
                  </span>
                </template>
              </Column>
              <Column field="name" header="Platillo" sortable>
                <template #body="{ data }">
                  <span class="font-bold text-slate-800 dark:text-slate-100">{{ data.name }}</span>
                </template>
              </Column>
              <Column field="category.name" header="Categoría"></Column>
              <Column header="Precio Unitario">
                <template #body="{ data }">
                  <span class="font-semibold text-slate-700 dark:text-slate-200">${{ parseFloat(data.price).toFixed(2) }}</span>
                </template>
              </Column>
              <Column header="Unidades Vendidas" sortable>
                <template #body="{ data }">
                  <Tag :value="`${data.total_units_sold || 0} vendidos`" severity="info" />
                </template>
              </Column>
              <Column header="Ingresos Generados" sortable>
                <template #body="{ data }">
                  <span class="font-bold text-emerald-600 dark:text-emerald-400">
                    ${{ parseFloat(data.total_revenue_generated || 0).toFixed(2) }}
                  </span>
                </template>
              </Column>
            </DataTable>
          </TabPanel>

          <!-- TAB 2: PROFITABILITY MATRIX -->
          <TabPanel value="1" class="pt-4">
            <DataTable :value="profitability" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
              <Column field="name" header="Platillo" sortable>
                <template #body="{ data }">
                  <div>
                    <span class="font-bold text-slate-800 dark:text-slate-100">{{ data.name }}</span>
                    <span v-if="!data.has_recipe" class="text-[10px] text-amber-600 dark:text-amber-400 ml-2 italic">(Sin receta)</span>
                  </div>
                </template>
              </Column>
              <Column field="category" header="Categoría"></Column>
              <Column header="Precio de Venta" sortable>
                <template #body="{ data }">
                  <span class="font-semibold text-slate-700 dark:text-slate-200">${{ data.selling_price.toFixed(2) }}</span>
                </template>
              </Column>
              <Column header="Costo de Insumos (Receta)" sortable>
                <template #body="{ data }">
                  <span class="font-semibold text-amber-600 dark:text-amber-400">${{ data.recipe_cost.toFixed(2) }}</span>
                </template>
              </Column>
              <Column header="Ganancia Bruta ($)" sortable>
                <template #body="{ data }">
                  <span class="font-bold text-emerald-600 dark:text-emerald-400">${{ data.gross_profit.toFixed(2) }}</span>
                </template>
              </Column>
              <Column header="Margen %" sortable>
                <template #body="{ data }">
                  <Tag
                    :value="getMarginBadge(data.margin_percent).label"
                    :severity="getMarginBadge(data.margin_percent).severity"
                  />
                </template>
              </Column>
            </DataTable>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>
  </AdminLayout>
</template>
