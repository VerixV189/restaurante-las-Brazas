<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
  ingredients: Array,
  suppliers: Array,
});

const toast = useToast();
const confirm = useConfirm();
const modalOpen = ref(false);
const isEditing = ref(false);

const unitOptions = ['kg', 'g', 'lt', 'ml', 'unidad', 'paquete', 'caja'];

const form = useForm({
  id: null,
  supplier_id: null,
  name: '',
  unit_of_measure: 'kg',
  current_stock: 0,
  minimum_stock: 5,
  cost_per_unit: 0,
});

function openCreateModal() {
  isEditing.value = false;
  form.reset();
  modalOpen.value = true;
}

function openEditModal(ingredient) {
  isEditing.value = true;
  form.id = ingredient.id;
  form.supplier_id = ingredient.supplier_id;
  form.name = ingredient.name;
  form.unit_of_measure = ingredient.unit_of_measure;
  form.current_stock = Number(ingredient.current_stock);
  form.minimum_stock = Number(ingredient.minimum_stock);
  form.cost_per_unit = Number(ingredient.cost_per_unit);
  modalOpen.value = true;
}

function submitForm() {
  if (isEditing.value) {
    form.put(`/ingredients/${form.id}`, {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Insumo actualizado', life: 3000 });
      },
    });
  } else {
    form.post('/ingredients', {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Insumo registrado', life: 3000 });
      },
    });
  }
}

function confirmDelete(ingredient) {
  confirm.require({
    message: `¿Eliminar el insumo "${ingredient.name}" del inventario?`,
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/ingredients/${ingredient.id}`, {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Insumo eliminado', life: 3000 });
        },
      });
    },
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Gestión de Inventario e Insumos</template>

    <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-6 transition-colors duration-200">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Control de Stock e Insumos</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400">Supervisa las existencias de materias primas y alertas de bajo stock</p>
        </div>
        <Button label="Registrar Insumo" icon="pi pi-plus" class="bg-orange-600 border-none hover:bg-orange-500 text-white" @click="openCreateModal" />
      </div>

      <DataTable :value="ingredients" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
        <Column field="name" header="Ingrediente / Insumo" sortable>
          <template #body="{ data }">
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ data.name }}</span>
          </template>
        </Column>
        <Column field="supplier.company_name" header="Proveedor">
          <template #body="{ data }">
            <span class="text-slate-600 dark:text-slate-300 text-sm">{{ data.supplier?.company_name || 'Sin Proveedor' }}</span>
          </template>
        </Column>
        <Column field="current_stock" header="Stock Actual" sortable>
          <template #body="{ data }">
            <span :class="['font-bold text-sm', Number(data.current_stock) <= Number(data.minimum_stock) ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-slate-100']">
              {{ data.current_stock }} {{ data.unit_of_measure }}
            </span>
          </template>
        </Column>
        <Column field="minimum_stock" header="Stock Mínimo">
          <template #body="{ data }">
            <span class="text-slate-500 dark:text-slate-400 text-sm">{{ data.minimum_stock }} {{ data.unit_of_measure }}</span>
          </template>
        </Column>
        <Column field="cost_per_unit" header="Costo Unitario">
          <template #body="{ data }">
            <span class="text-slate-600 dark:text-slate-300">${{ Number(data.cost_per_unit).toFixed(2) }} / {{ data.unit_of_measure }}</span>
          </template>
        </Column>
        <Column header="Estado Stock">
          <template #body="{ data }">
            <Tag
              :value="Number(data.current_stock) <= Number(data.minimum_stock) ? 'Stock Bajo' : 'Normal'"
              :severity="Number(data.current_stock) <= Number(data.minimum_stock) ? 'warn' : 'success'"
            />
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <Button
                icon="pi pi-pencil"
                text
                rounded
                size="small"
                severity="info"
                class="!w-8 !h-8 !p-0 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10"
                @click="openEditModal(data)"
                title="Editar Insumo"
              />
              <Button
                icon="pi pi-trash"
                text
                rounded
                size="small"
                severity="danger"
                class="!w-8 !h-8 !p-0 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10"
                @click="confirmDelete(data)"
                title="Eliminar Insumo"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Modal Form -->
    <Dialog v-model:visible="modalOpen" :header="isEditing ? 'Editar Insumo' : 'Registrar Insumo'" modal class="w-full max-w-md">
      <form @submit.prevent="submitForm" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Insumo</label>
          <InputText v-model="form.name" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Proveedor</label>
          <Select v-model="form.supplier_id" :options="suppliers" optionLabel="company_name" optionValue="id" placeholder="Seleccionar proveedor" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" showClear />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Stock Actual</label>
            <InputNumber v-model="form.current_stock" :minFractionDigits="1" :maxFractionDigits="3" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Stock Mínimo</label>
            <InputNumber v-model="form.minimum_stock" :minFractionDigits="1" :maxFractionDigits="3" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Unidad Medida</label>
            <Select v-model="form.unit_of_measure" :options="unitOptions" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Costo Unitario ($)</label>
            <InputNumber v-model="form.cost_per_unit" mode="currency" currency="USD" locale="en-US" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" label="Cancelar" severity="secondary" text @click="modalOpen = false" />
          <Button type="submit" :label="isEditing ? 'Actualizar' : 'Guardar'" severity="warn" class="bg-orange-600 border-none text-white" :loading="form.processing" />
        </div>
      </form>
    </Dialog>
  </AdminLayout>
</template>
