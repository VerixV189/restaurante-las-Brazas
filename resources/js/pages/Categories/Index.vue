<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { Plus, Edit3, Trash2 } from 'lucide-vue-next';

const props = defineProps({
  categories: Array,
});

const toast = useToast();
const confirm = useConfirm();
const modalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
  id: null,
  name: '',
  description: '',
  icon: 'Utensils',
  is_active: true,
});

function openCreateModal() {
  isEditing.value = false;
  form.reset();
  modalOpen.value = true;
}

function openEditModal(category) {
  isEditing.value = true;
  form.id = category.id;
  form.name = category.name;
  form.description = category.description;
  form.icon = category.icon || 'Utensils';
  form.is_active = category.is_active;
  modalOpen.value = true;
}

function submitForm() {
  if (isEditing.value) {
    form.put(`/categories/${form.id}`, {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Categoría actualizada', life: 3000 });
      },
    });
  } else {
    form.post('/categories', {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Categoría creada', life: 3000 });
      },
    });
  }
}

function confirmDelete(category) {
  confirm.require({
    message: `¿Estás seguro de eliminar la categoría "${category.name}"?`,
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/categories/${category.id}`, {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminada', detail: 'Categoría eliminada', life: 3000 });
        },
      });
    },
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Gestión de Categorías</template>

    <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-6 transition-colors duration-200">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Categorías de Menú</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400">Organiza los platillos por secciones (Entradas, Postres, Bebidas)</p>
        </div>
        <Button label="Nueva Categoría" icon="pi pi-plus" class="bg-orange-600 border-none hover:bg-orange-500 text-white" @click="openCreateModal" />
      </div>

      <DataTable :value="categories" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
        <Column field="name" header="Nombre" sortable>
          <template #body="{ data }">
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ data.name }}</span>
          </template>
        </Column>
        <Column field="slug" header="Slug">
          <template #body="{ data }">
            <span class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ data.slug }}</span>
          </template>
        </Column>
        <Column field="description" header="Descripción">
          <template #body="{ data }">
            <span class="text-slate-600 dark:text-slate-300 text-sm">{{ data.description || '—' }}</span>
          </template>
        </Column>
        <Column field="dishes_count" header="Platillos" sortable>
          <template #body="{ data }">
            <Tag :value="`${data.dishes_count || 0} platillos`" severity="info" />
          </template>
        </Column>
        <Column field="is_active" header="Estado">
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Activa' : 'Inactiva'" :severity="data.is_active ? 'success' : 'danger'" />
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
                title="Editar Categoría"
              />
              <Button
                icon="pi pi-trash"
                text
                rounded
                size="small"
                severity="danger"
                class="!w-8 !h-8 !p-0 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10"
                @click="confirmDelete(data)"
                title="Eliminar Categoría"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Modal Form -->
    <Dialog v-model:visible="modalOpen" :header="isEditing ? 'Editar Categoría' : 'Nueva Categoría'" modal class="w-full max-w-md">
      <form @submit.prevent="submitForm" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre</label>
          <InputText v-model="form.name" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Descripción</label>
          <Textarea v-model="form.description" rows="3" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" />
        </div>
        <div class="flex items-center justify-between">
          <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Activa</label>
          <ToggleSwitch v-model="form.is_active" />
        </div>
        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" label="Cancelar" severity="secondary" text @click="modalOpen = false" />
          <Button type="submit" :label="isEditing ? 'Actualizar' : 'Guardar'" severity="warn" class="bg-orange-600 border-none text-white" :loading="form.processing" />
        </div>
      </form>
    </Dialog>
  </AdminLayout>
</template>
