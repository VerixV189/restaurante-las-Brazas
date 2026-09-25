<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import AdminLayout from '../../layouts/AdminLayout.vue';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import {
  Layers,
  UploadCloud,
  Sparkles,
  Zap,
  Save,
  CheckCircle2,
  AlertTriangle,
  Armchair,
  Maximize2,
  Users,
  RotateCcw,
  Eye,
  Info,
  MapPin,
} from 'lucide-vue-next';

const props = defineProps({
  areas: Array,
});

const toast = useToast();

// Form & Config State
const fileInputRef = ref(null);
const selectedFile = ref(null);
const imagePreviewUrl = ref(null);
const localAnchoM = ref(12.0);
const localAltoM = ref(9.0);
const mesaMinM = ref(0.6);
const mesaMaxM = ref(2.5);
const sillaMinM = ref(0.3);
const sillaMaxM = ref(0.6);
const selectedAreaId = ref(props.areas?.[0]?.id || null);

// Detection & Canvas State
const isDetecting = ref(false);
const isOptimizing = ref(false);
const isSyncing = ref(false);
const layoutResult = ref(null);
const selectedTableId = ref(null);

function handleFileChange(event) {
  const file = event.target.files[0];
  if (file) {
    selectedFile.value = file;
    imagePreviewUrl.value = URL.createObjectURL(file);
    layoutResult.value = null;
  }
}

function triggerFileInput() {
  fileInputRef.value?.click();
}

// Cargar plano de demostración precargado
function loadSampleBlueprint() {
  // Crear un canvas con un plano simple sintético
  const canvas = document.createElement('canvas');
  canvas.width = 900;
  canvas.height = 650;
  const ctx = canvas.getContext('2d');

  // Fondo
  ctx.fillStyle = '#0f172a';
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  // Paredes
  ctx.strokeStyle = '#334155';
  ctx.lineWidth = 8;
  ctx.strokeRect(30, 30, canvas.width - 60, canvas.height - 60);

  // Puerta de entrada
  ctx.strokeStyle = '#ea580c';
  ctx.lineWidth = 6;
  ctx.beginPath();
  ctx.moveTo(canvas.width / 2 - 40, canvas.height - 30);
  ctx.lineTo(canvas.width / 2 + 40, canvas.height - 30);
  ctx.stroke();

  // Grilla de mesas dibujadas en el plano de fondo
  ctx.fillStyle = '#1e293b';
  ctx.strokeStyle = '#475569';
  ctx.lineWidth = 2;
  const positions = [
    [150, 150], [450, 150], [750, 150],
    [150, 400], [450, 400], [750, 400],
  ];

  positions.forEach(([x, y]) => {
    ctx.fillRect(x - 50, y - 35, 100, 70);
    ctx.strokeRect(x - 50, y - 35, 100, 70);
  });

  canvas.toBlob((blob) => {
    const file = new File([blob], 'plano_muestra_las_brazas.png', { type: 'image/png' });
    selectedFile.value = file;
    imagePreviewUrl.value = canvas.toDataURL();
    layoutResult.value = null;
    toast.add({ severity: 'info', summary: 'Plano de Muestra', detail: 'Plano arquitectónico cargado', life: 2000 });
  });
}

// 1. Detectar Layout con YOLO
async function runYoloDetection() {
  if (!selectedFile.value) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Por favor sube una imagen del plano primero.', life: 3000 });
    return;
  }

  isDetecting.value = true;
  const formData = new FormData();
  formData.append('plano_imagen', selectedFile.value);
  formData.append('ancho_m', localAnchoM.value);
  formData.append('alto_m', localAltoM.value);
  formData.append('mesa_min_m', mesaMinM.value);
  formData.append('mesa_max_m', mesaMaxM.value);
  formData.append('silla_min_m', sillaMinM.value);
  formData.append('silla_max_m', sillaMaxM.value);

  try {
    const response = await axios.post('/floor-plans/detect', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (response.data.success) {
      layoutResult.value = response.data.layout;
      toast.add({
        severity: 'success',
        summary: 'Detección Completada',
        detail: `Se detectaron ${response.data.layout.objects?.length || 0} mesas en el plano.`,
        life: 3000,
      });
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al procesar el plano con YOLO.',
      life: 3500,
    });
  } finally {
    isDetecting.value = false;
  }
}

// 2. Optimizar Layout
async function optimizeLayout() {
  if (!layoutResult.value) return;

  isOptimizing.value = true;
  try {
    const response = await axios.post('/floor-plans/optimize', {
      layout: layoutResult.value,
    });

    if (response.data.success) {
      layoutResult.value = response.data.optimized_layout;
      toast.add({
        severity: 'success',
        summary: 'Layout Optimizado',
        detail: 'Distribución espacial recalculada exitosamente.',
        life: 2500,
      });
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo optimizar el layout.', life: 3000 });
  } finally {
    isOptimizing.value = false;
  }
}

// 3. Sincronizar a Base de Datos
async function syncTablesToDatabase() {
  if (!layoutResult.value?.objects || !selectedAreaId.value) {
    toast.add({ severity: 'warn', summary: 'Faltan Datos', detail: 'Selecciona una zona antes de sincronizar.', life: 3000 });
    return;
  }

  isSyncing.value = true;
  const tablesPayload = layoutResult.value.objects.map((obj, idx) => ({
    table_number: obj.label || `Mesa ${idx + 1}`,
    capacity: obj.capacity || 4,
  }));

  try {
    const response = await axios.post('/floor-plans/sync-tables', {
      area_id: selectedAreaId.value,
      tables: tablesPayload,
    });

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Mesas Sincronizadas',
        detail: response.data.message,
        life: 4000,
      });
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error al Guardar',
      detail: err.response?.data?.message || 'Error al guardar mesas en la base de datos.',
      life: 3500,
    });
  } finally {
    isSyncing.value = false;
  }
}

// Perimeter Polygon SVG Path string
const perimeterSvgPoints = computed(() => {
  if (!layoutResult.value?.perimeter?.points) return '';
  return layoutResult.value.perimeter.points.map(p => `${p[0]},${p[1]}`).join(' ');
});

const totalDetectedCapacity = computed(() => {
  if (!layoutResult.value?.objects) return 0;
  return layoutResult.value.objects.reduce((acc, obj) => acc + (obj.capacity || 0), 0);
});
</script>

<template>
  <AdminLayout>
    <template #header>Gestor Inteligente de Planos & Layouts (YOLO v8)</template>

    <div class="space-y-6">
      <!-- Grid Principal: Configuración (4 Cols) + Canvas/SVG Viewer (8 Cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ── COLUMNA IZQUIERDA: CONTROLES Y PARÁMETROS (4 COLS) ──────── -->
        <div class="lg:col-span-4 space-y-5">

          <!-- 1. Cargar Plano -->
          <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex items-center gap-2 text-slate-100 font-bold text-sm">
              <UploadCloud class="w-4 h-4 text-orange-500" />
              <span>1. Imagen del Plano Arquitectónico</span>
            </div>

            <input
              ref="fileInputRef"
              type="file"
              accept="image/*"
              class="hidden"
              @change="handleFileChange"
            />

            <div
              @click="triggerFileInput"
              class="border-2 border-dashed border-slate-700 hover:border-orange-500/60 rounded-2xl p-4 text-center cursor-pointer transition-all bg-slate-950/60 hover:bg-orange-500/5 group"
            >
              <UploadCloud class="w-8 h-8 mx-auto text-slate-500 group-hover:text-orange-400 transition-colors mb-2" />
              <p class="text-xs font-semibold text-slate-300">
                {{ selectedFile ? selectedFile.name : 'Haz clic para subir imagen o plano' }}
              </p>
              <p class="text-[10px] text-slate-500 mt-1">PNG, JPG, WEBP (Hasta 5MB)</p>
            </div>

            <button
              type="button"
              @click="loadSampleBlueprint"
              class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl flex items-center justify-center gap-1.5 transition-colors border border-slate-700"
            >
              <Sparkles class="w-3.5 h-3.5 text-amber-400" />
              <span>Cargar Plano de Demostración</span>
            </button>
          </div>

          <!-- 2. Dimensiones y Filtros -->
          <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex items-center gap-2 text-slate-100 font-bold text-sm">
              <Maximize2 class="w-4 h-4 text-purple-400" />
              <span>2. Dimensiones Reales (Metros)</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Ancho Local (m)</label>
                <input
                  v-model.number="localAnchoM"
                  type="number"
                  step="0.5"
                  min="1"
                  class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-orange-500"
                />
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Alto Local (m)</label>
                <input
                  v-model.number="localAltoM"
                  type="number"
                  step="0.5"
                  min="1"
                  class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-orange-500"
                />
              </div>
            </div>

            <div class="pt-2 border-t border-slate-800">
              <span class="block text-[11px] font-semibold text-slate-400 uppercase mb-2">Filtros de Mesas & Sillas (m)</span>
              <div class="grid grid-cols-2 gap-2 text-[11px]">
                <div>
                  <label class="text-slate-500">Mesa Min:</label>
                  <input v-model.number="mesaMinM" type="number" step="0.1" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-300 text-xs" />
                </div>
                <div>
                  <label class="text-slate-500">Mesa Max:</label>
                  <input v-model.number="mesaMaxM" type="number" step="0.1" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-1.5 text-slate-300 text-xs" />
                </div>
              </div>
            </div>

            <!-- Botón Detectar con YOLO -->
            <Button
              :loading="isDetecting"
              label="Detectar Layout con YOLO"
              icon="pi pi-search"
              class="w-full bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-500 hover:to-amber-500 border-none text-white font-bold text-xs py-3 rounded-2xl shadow-lg shadow-orange-600/30 transition-all"
              @click="runYoloDetection"
              :disabled="!selectedFile"
            />
          </div>

          <!-- 3. Sincronización a Base de Datos -->
          <div v-if="layoutResult" class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4 animate-fade-in">
            <div class="flex items-center gap-2 text-slate-100 font-bold text-sm">
              <Save class="w-4 h-4 text-emerald-400" />
              <span>3. Guardar en Base de Datos</span>
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">Zona / Salón de Destino *</label>
              <select
                v-model="selectedAreaId"
                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
              >
                <option v-for="area in areas" :key="area.id" :value="area.id">
                  {{ area.name }} ({{ area.tables?.length || 0 }} mesas existentes)
                </option>
              </select>
            </div>

            <div class="flex gap-2">
              <Button
                :loading="isOptimizing"
                label="Optimizar"
                icon="pi pi-bolt"
                class="flex-1 bg-slate-800 hover:bg-slate-700 text-purple-300 border border-purple-500/30 text-xs font-bold py-2.5 rounded-xl"
                @click="optimizeLayout"
              />

              <Button
                :loading="isSyncing"
                label="Sincronizar Mesas"
                icon="pi pi-check"
                class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-2.5 rounded-xl border-none shadow-md shadow-emerald-600/30"
                @click="syncTablesToDatabase"
              />
            </div>
          </div>

        </div>

        <!-- ── COLUMNA DERECHA: VISOR INTERACTIVO SVG / CANVAS (8 COLS) ── -->
        <div class="lg:col-span-8 space-y-4">

          <!-- Barra de Métricas Detectadas -->
          <div v-if="layoutResult" class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-4 text-xs">
              <div class="flex items-center gap-1.5 text-slate-300">
                <Armchair class="w-4 h-4 text-emerald-400" />
                <span>Mesas: <strong class="text-emerald-400 font-bold text-sm">{{ layoutResult.objects?.length || 0 }}</strong></span>
              </div>
              <div class="flex items-center gap-1.5 text-slate-300">
                <Users class="w-4 h-4 text-blue-400" />
                <span>Aforo Total: <strong class="text-blue-400 font-bold text-sm">{{ totalDetectedCapacity }} personas</strong></span>
              </div>
              <div class="flex items-center gap-1.5 text-slate-300">
                <Maximize2 class="w-4 h-4 text-purple-400" />
                <span>Superficie: <strong class="text-slate-200">{{ (localAnchoM * localAltoM).toFixed(1) }} m²</strong></span>
              </div>
            </div>

            <Tag
              :value="layoutResult.engine === 'yolo_v8' ? 'YOLO v8 (best.pt)' : 'Simulación Geométrica'"
              :severity="layoutResult.engine === 'yolo_v8' ? 'success' : 'warn'"
              class="text-[10px]"
            />
          </div>

          <!-- Contenedor del Visor SVG del Plano -->
          <div class="relative bg-slate-950 border-2 border-slate-800 rounded-3xl overflow-hidden min-h-[520px] flex items-center justify-center shadow-2xl">
            <!-- Grid de Fondo tipo Blueprint -->
            <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:20px_20px] opacity-30"></div>

            <!-- Si hay imagen cargada / resultado -->
            <div v-if="imagePreviewUrl" class="relative max-w-full max-h-[520px] overflow-hidden p-2">
              <img
                :src="imagePreviewUrl"
                alt="Plano"
                class="block w-full max-h-[500px] object-contain rounded-xl opacity-80"
              />

              <!-- Overlay SVG para dibujar contornos, mesas y sillas -->
              <svg
                v-if="layoutResult"
                class="absolute inset-0 w-full h-full pointer-events-auto"
                :viewBox="`0 0 ${layoutResult.dimensions?.width_px || 900} ${layoutResult.dimensions?.height_px || 650}`"
                preserveAspectRatio="xMidYMid meet"
              >
                <!-- Polígono del Perímetro -->
                <polygon
                  v-if="perimeterSvgPoints"
                  :points="perimeterSvgPoints"
                  fill="rgba(234, 88, 12, 0.05)"
                  stroke="#ea580c"
                  stroke-width="3"
                  stroke-dasharray="6,4"
                />

                <!-- Mesas y Sillas Detectadas -->
                <g v-for="obj in layoutResult.objects" :key="obj.id">
                  <!-- Sillas alrededor de la mesa -->
                  <circle
                    v-for="(silla, sIdx) in obj.sillas"
                    :key="sIdx"
                    :cx="silla.x"
                    :cy="silla.y"
                    :r="silla.radius || 8"
                    fill="#3b82f6"
                    stroke="#1d4ed8"
                    stroke-width="2"
                    opacity="0.85"
                  />

                  <!-- Mesa (Rectángulo con esquinas redondeadas) -->
                  <rect
                    :x="obj.x"
                    :y="obj.y"
                    :width="obj.width"
                    :height="obj.height"
                    rx="10"
                    fill="rgba(16, 185, 129, 0.35)"
                    stroke="#10b981"
                    stroke-width="2.5"
                    class="cursor-pointer transition-all hover:fill-emerald-500/50"
                    @click="selectedTableId = obj.id"
                  />

                  <!-- Etiqueta de la Mesa -->
                  <text
                    :x="obj.x + (obj.width / 2)"
                    :y="obj.y + (obj.height / 2) + 4"
                    fill="#ffffff"
                    font-size="12"
                    font-weight="bold"
                    text-anchor="middle"
                    pointer-events="none"
                  >
                    {{ obj.label }} ({{ obj.capacity }}p)
                  </text>
                </g>
              </svg>
            </div>

            <!-- Estado Vacío -->
            <div v-else class="text-center p-8 space-y-3 z-10">
              <div class="w-16 h-16 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto text-slate-500">
                <Layers class="w-8 h-8" />
              </div>
              <h4 class="text-base font-bold text-slate-200">Visor de Planos Desactivado</h4>
              <p class="text-xs text-slate-400 max-w-sm mx-auto">
                Carga una imagen o haz clic en "Cargar Plano de Demostración" para comenzar a detectar el layout arquitectónico con YOLO.
              </p>
            </div>
          </div>

          <!-- Leyenda de Colores -->
          <div class="flex items-center justify-center gap-6 text-xs text-slate-400 bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
            <span class="flex items-center gap-1.5">
              <span class="w-3.5 h-3.5 rounded bg-emerald-500/40 border border-emerald-500 inline-block"></span>
              Mesas Detectadas (YOLO)
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-blue-500 border border-blue-400 inline-block"></span>
              Sillas / Aforo Asignado
            </span>
            <span class="flex items-center gap-1.5">
              <span class="w-3.5 h-1 border-t-2 border-dashed border-orange-500 inline-block"></span>
              Perímetro del Salón
            </span>
          </div>

        </div>

      </div>
    </div>
  </AdminLayout>
</template>
