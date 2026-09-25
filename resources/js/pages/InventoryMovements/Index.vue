<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import {
  ArrowDownLeft,
  ArrowUpRight,
  RefreshCw,
  AlertTriangle,
  Boxes,
  Plus,
  TrendingDown,
  TrendingUp,
  History
} from 'lucide-vue-next';

const props = defineProps({
  movements: Array,
  ingredients: Array,
  summary: Object,
});

const toast = useToast();
const modalOpen = ref(false);

const form = useForm({
  ingredient_id: null,
  movement_type: 'waste',
  quantity: 1,
  reason: '',
});

const movementTypes = [
  { label: '🔴 Merma / Desperdicio / Caducidad', value: 'waste', description: 'Resta del stock por daño o merma' },
  { label: '🟢 Entrada / Compra de Insumo', value: 'in', description: 'Suma al stock actual' },
  { label: '🔵 Salida Manual', value: 'out', description: 'Resta del stock para otros usos' },
  { label: '🟡 Ajuste Físico (Auditoría)', value: 'adjustment', description: 'Reemplaza el stock con el valor contado' },
];

function openCreateModal(defaultType = 'waste') {
  form.reset();
  form.movement_type = defaultType;
  if (props.ingredients.length > 0) {
    form.ingredient_id = props.ingredients[0].id;
  }
  modalOpen.value = true;
}

function submitForm() {
  form.post('/inventory-movements', {
    onSuccess: () => {
      modalOpen.value = false;
      toast.add({
        severity: 'success',
        summary: 'Registrado',
        detail: 'Movimiento de inventario guardado correctamente',
        life: 3000
      });
    },
  });
}

function getSelectedIngredient() {
  return props.ingredients.find(i => i.id === form.ingredient_id);
}

function getMovementBadge(type) {
  switch (type) {
    case 'in':
      return { label: 'Entrada / Compra', severity: 'success', icon: 'pi pi-arrow-down-left' };
    case 'out':
      return { label: 'Salida / Consumo', severity: 'info', icon: 'pi pi-arrow-up-right' };
    case 'waste':
      return { label: 'Merma / Desperdicio', severity: 'danger', icon: 'pi pi-exclamation-circle' };
    case 'adjustment':
      return { label: 'Ajuste de Stock', severity: 'warn', icon: 'pi pi-refresh' };
    default:
      return { label: type, severity: 'secondary', icon: 'pi pi-info-circle' };
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Control de Movimientos y Mermas de Inventario</template>

    <div class="space-y-6">
      <!-- Metric Summary Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex items-center gap-4 transition-colors duration-200">
          <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl">
            <TrendingUp class="w-6 h-6" />
          </div>
          <div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Entradas (Compras)</p>
            <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ summary.total_in }}</h3>
          </div>
        </div>

        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex items-center gap-4 transition-colors duration-200">
          <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl">
            <TrendingDown class="w-6 h-6" />
          </div>
          <div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Salidas / Ventas</p>
            <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ summary.total_out }}</h3>
          </div>
        </div>

        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex items-center gap-4 transition-colors duration-200">
          <div class="p-3 bg-red-500/10 text-red-600 dark:text-red-400 rounded-xl">
            <AlertTriangle class="w-6 h-6" />
          </div>
          <div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Mermas / Desperdicio</p>
            <h3 class="text-2xl font-bold text-red-600 dark:text-red-400">{{ summary.total_waste }}</h3>
          </div>
        </div>

        <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 flex items-center gap-4 transition-colors duration-200">
          <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl">
            <RefreshCw class="w-6 h-6" />
          </div>
          <div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Ajustes de Auditoría</p>
            <h3 class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ summary.total_adjustment }}</h3>
          </div>
        </div>
      </div>

      <!-- Main Content Card -->
      <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-6 transition-colors duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
              <History class="w-6 h-6 text-orange-500" />
              Historial de Movimientos de Inventario
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
              Registro auditado de deducciones automáticas por venta, compras y control de desperdicios.
            </p>
          </div>
          <div class="flex gap-2 shrink-0">
            <Button
              label="Registrar Merma"
              icon="pi pi-exclamation-triangle"
              class="bg-red-600/80 hover:bg-red-600 border-none text-white text-xs"
              @click="openCreateModal('waste')"
            />
            <Button
              label="Nuevo Movimiento"
              icon="pi pi-plus"
              class="bg-orange-600 hover:bg-orange-500 border-none text-white text-xs"
              @click="openCreateModal('in')"
            />
          </div>
        </div>

        <DataTable :value="movements" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
          <Column header="Fecha / Hora" sortable>
            <template #body="{ data }">
              <span class="text-xs text-slate-600 dark:text-slate-300 font-mono">{{ formatDate(data.created_at) }}</span>
            </template>
          </Column>
          <Column header="Insumo" sortable>
            <template #body="{ data }">
              <div>
                <span class="font-bold text-slate-800 dark:text-slate-100">{{ data.ingredient?.name }}</span>
                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                  Stock actual: {{ parseFloat(data.ingredient?.current_stock || 0) }} {{ data.ingredient?.unit_of_measure }}
                </div>
              </div>
            </template>
          </Column>
          <Column header="Tipo">
            <template #body="{ data }">
              <Tag
                :value="getMovementBadge(data.movement_type).label"
                :severity="getMovementBadge(data.movement_type).severity"
              />
            </template>
          </Column>
          <Column header="Cantidad">
            <template #body="{ data }">
              <span
                class="font-bold text-sm font-mono"
                :class="{
                  'text-emerald-400': data.movement_type === 'in',
                  'text-blue-400': data.movement_type === 'out',
                  'text-red-400': data.movement_type === 'waste',
                  'text-amber-400': data.movement_type === 'adjustment',
                }"
              >
                {{ data.movement_type === 'in' ? '+' : data.movement_type === 'out' || data.movement_type === 'waste' ? '-' : '' }}{{ parseFloat(data.quantity) }} {{ data.ingredient?.unit_of_measure }}
              </span>
            </template>
          </Column>
          <Column header="Motivo / Detalle">
            <template #body="{ data }">
              <span class="text-xs text-slate-300">{{ data.reason || 'Sin motivo especificado' }}</span>
            </template>
          </Column>
          <Column header="Responsable">
            <template #body="{ data }">
              <span class="text-xs text-slate-400">{{ data.user?.name || 'Sistema Automático' }}</span>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- Modal Formulario Movimiento -->
    <Dialog
      v-model:visible="modalOpen"
      header="Registrar Movimiento / Ajuste de Stock"
      modal
      class="w-full max-w-md"
    >
      <form @submit.prevent="submitForm" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Insumo / Ingrediente *</label>
          <Select
            v-model="form.ingredient_id"
            :options="ingredients"
            optionLabel="name"
            optionValue="id"
            placeholder="Selecciona el insumo"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            required
          />
          <div v-if="getSelectedIngredient()" class="text-xs text-slate-400 mt-1">
            Stock actual: <strong class="text-slate-200">{{ parseFloat(getSelectedIngredient().current_stock) }} {{ getSelectedIngredient().unit_of_measure }}</strong>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Tipo de Movimiento *</label>
          <Select
            v-model="form.movement_type"
            :options="movementTypes"
            optionLabel="label"
            optionValue="value"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            required
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">
            {{ form.movement_type === 'adjustment' ? 'Nuevo Stock Real Físico *' : 'Cantidad a Registrar *' }}
          </label>
          <InputNumber
            v-model="form.quantity"
            :min="0.001"
            :minFractionDigits="1"
            :maxFractionDigits="3"
            class="w-full"
            required
          />
          <div v-if="getSelectedIngredient()" class="text-[11px] text-slate-400 mt-1">
            Unidad de medida: <strong>{{ getSelectedIngredient().unit_of_measure }}</strong>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Motivo / Causa</label>
          <Textarea
            v-model="form.reason"
            rows="2"
            class="w-full bg-slate-800 border-slate-700 text-slate-100 text-sm"
            placeholder="Ej: Caducado en cámara frigorífica, Compra factura #1042..."
          />
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-800">
          <Button type="button" label="Cancelar" severity="secondary" text @click="modalOpen = false" />
          <Button
            type="submit"
            label="Guardar Movimiento"
            class="bg-orange-600 border-none hover:bg-orange-500"
            :loading="form.processing"
          />
        </div>
      </form>
    </Dialog>
  </AdminLayout>
</template>
