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
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
  dishes: Array,
  categories: Array,
});

const toast = useToast();
const confirm = useConfirm();
const modalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
  id: null,
  category_id: null,
  name: '',
  description: '',
  price: 0,
  prep_time_minutes: 15,
  is_available: true,
});

function openCreateModal() {
  isEditing.value = false;
  form.reset();
  if (props.categories.length > 0) {
    form.category_id = props.categories[0].id;
  }
  modalOpen.value = true;
}

function openEditModal(dish) {
  isEditing.value = true;
  form.id = dish.id;
  form.category_id = dish.category_id;
  form.name = dish.name;
  form.description = dish.description;
  form.price = Number(dish.price);
  form.prep_time_minutes = dish.prep_time_minutes;
  form.is_available = dish.is_available;
  modalOpen.value = true;
}

function submitForm() {
  if (isEditing.value) {
    form.put(`/dishes/${form.id}`, {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Platillo actualizado', life: 3000 });
      },
    });
  } else {
    form.post('/dishes', {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Platillo guardado', life: 3000 });
      },
    });
  }
}

function confirmDelete(dish) {
  confirm.require({
    message: `¿Deseas eliminar el platillo "${dish.name}"?`,
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/dishes/${dish.id}`, {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Platillo eliminado', life: 3000 });
        },
      });
    },
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Gestión de Platillos / Menú</template>

    <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-6 transition-colors duration-200">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Catálogo de Platillos</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400">Administra los precios, disponibilidad y menú del restaurante</p>
        </div>
        <Button label="Nuevo Platillo" icon="pi pi-plus" class="bg-orange-600 border-none hover:bg-orange-500 text-white" @click="openCreateModal" />
      </div>

      <DataTable :value="dishes" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
        <Column field="name" header="Platillo" sortable>
          <template #body="{ data }">
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ data.name }}</span>
          </template>
        </Column>
        <Column field="category.name" header="Categoría" sortable>
          <template #body="{ data }">
            <Tag :value="data.category?.name || 'Sin Categoría'" severity="warn" />
          </template>
        </Column>
        <Column field="price" header="Precio" sortable>
          <template #body="{ data }">
            <span class="font-bold text-emerald-600 dark:text-emerald-400">${{ Number(data.price).toFixed(2) }}</span>
          </template>
        </Column>
        <Column field="prep_time_minutes" header="Tiempo Prep.">
          <template #body="{ data }">
            <span class="text-slate-600 dark:text-slate-300">{{ data.prep_time_minutes }} min</span>
          </template>
        </Column>
        <Column field="is_available" header="Disponibilidad">
          <template #body="{ data }">
            <Tag :value="data.is_available ? 'Disponible' : 'Agotado'" :severity="data.is_available ? 'success' : 'danger'" />
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
                title="Editar Platillo"
              />
              <Button
                icon="pi pi-trash"
                text
                rounded
                size="small"
                severity="danger"
                class="!w-8 !h-8 !p-0 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10"
                @click="confirmDelete(data)"
                title="Eliminar Platillo"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Modal Form -->
    <Dialog v-model:visible="modalOpen" :header="isEditing ? 'Editar Platillo' : 'Nuevo Platillo'" modal class="w-full max-w-md">
      <form @submit.prevent="submitForm" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre</label>
          <InputText v-model="form.name" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Categoría</label>
          <Select v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Precio ($)</label>
            <InputNumber v-model="form.price" mode="currency" currency="USD" locale="en-US" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tiempo (min)</label>
            <InputNumber v-model="form.prep_time_minutes" suffix=" min" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Descripción</label>
          <Textarea v-model="form.description" rows="3" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" />
        </div>
        <div class="flex items-center justify-between">
          <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Disponible para Venta</label>
          <ToggleSwitch v-model="form.is_available" />
        </div>
        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" label="Cancelar" severity="secondary" text @click="modalOpen = false" />
          <Button type="submit" :label="isEditing ? 'Actualizar' : 'Guardar'" severity="warn" class="bg-orange-600 border-none text-white" :loading="form.processing" />
        </div>
      </form>
    </Dialog>
  </AdminLayout>
</template>
