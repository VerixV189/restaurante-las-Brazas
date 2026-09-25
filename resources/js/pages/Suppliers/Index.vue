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
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { Truck, Plus, Edit3, Trash2, Phone, Mail, MapPin, Boxes } from 'lucide-vue-next';

const props = defineProps({
  suppliers: Array,
});

const toast = useToast();
const confirm = useConfirm();
const modalOpen = ref(false);
const isEditing = ref(false);
const viewModalOpen = ref(false);
const selectedSupplier = ref(null);

const form = useForm({
  id: null,
  company_name: '',
  contact_name: '',
  phone: '',
  email: '',
  address: '',
});

function openCreateModal() {
  isEditing.value = false;
  form.reset();
  modalOpen.value = true;
}

function openEditModal(supplier) {
  isEditing.value = true;
  form.id = supplier.id;
  form.company_name = supplier.company_name;
  form.contact_name = supplier.contact_name || '';
  form.phone = supplier.phone || '';
  form.email = supplier.email || '';
  form.address = supplier.address || '';
  modalOpen.value = true;
}

function openViewModal(supplier) {
  selectedSupplier.value = supplier;
  viewModalOpen.value = true;
}

function submitForm() {
  if (isEditing.value) {
    form.put(`/suppliers/${form.id}`, {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Proveedor actualizado', life: 3000 });
      },
    });
  } else {
    form.post('/suppliers', {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Proveedor registrado', life: 3000 });
      },
    });
  }
}

function confirmDelete(supplier) {
  confirm.require({
    message: `¿Estás seguro de eliminar al proveedor "${supplier.company_name}"?`,
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/suppliers/${supplier.id}`, {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Proveedor eliminado', life: 3000 });
        },
      });
    },
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Gestión de Proveedores</template>

    <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-6 transition-colors duration-200">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <Truck class="w-6 h-6 text-orange-500" />
            Catálogo de Proveedores
          </h2>
          <p class="text-sm text-slate-500 dark:text-slate-400">
            Administra los contactos de distribuidores de carnes, verduras, bebidas e insumos generales.
          </p>
        </div>
        <Button
          label="Nuevo Proveedor"
          icon="pi pi-plus"
          class="bg-orange-600 border-none hover:bg-orange-500 text-white shrink-0"
          @click="openCreateModal"
        />
      </div>

      <DataTable :value="suppliers" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
        <Column field="company_name" header="Empresa / Proveedor" sortable>
          <template #body="{ data }">
            <div>
              <div class="font-bold text-slate-800 dark:text-slate-100">{{ data.company_name }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">Contacto: {{ data.contact_name || 'Sin especificar' }}</div>
            </div>
          </template>
        </Column>
        <Column header="Teléfono">
          <template #body="{ data }">
            <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
              <Phone class="w-3.5 h-3.5 text-orange-500 dark:text-orange-400" />
              <span>{{ data.phone || 'N/A' }}</span>
            </div>
          </template>
        </Column>
        <Column header="Correo Electrónico">
          <template #body="{ data }">
            <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
              <Mail class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400" />
              <span>{{ data.email || 'N/A' }}</span>
            </div>
          </template>
        </Column>
        <Column header="Insumos Vinculados" sortable>
          <template #body="{ data }">
            <Tag :value="`${data.ingredients_count || 0} insumos`" severity="info" />
          </template>
        </Column>
        <Column header="Acciones" style="width: 140px">
          <template #body="{ data }">
            <div class="flex items-center gap-1.5">
              <Button
                icon="pi pi-eye"
                text
                rounded
                size="small"
                severity="secondary"
                class="!w-8 !h-8 !p-0 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                title="Ver Insumos"
                @click="openViewModal(data)"
              />
              <Button
                icon="pi pi-pencil"
                text
                rounded
                size="small"
                severity="info"
                class="!w-8 !h-8 !p-0 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10"
                title="Editar"
                @click="openEditModal(data)"
              />
              <Button
                icon="pi pi-trash"
                text
                rounded
                size="small"
                severity="danger"
                class="!w-8 !h-8 !p-0 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10"
                title="Eliminar"
                @click="confirmDelete(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Modal Formulario Crear/Editar -->
    <Dialog
      v-model:visible="modalOpen"
      :header="isEditing ? 'Editar Proveedor' : 'Nuevo Proveedor'"
      modal
      class="w-full max-w-md"
    >
      <form @submit.prevent="submitForm" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Nombre de la Empresa *</label>
          <InputText
            v-model="form.company_name"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            placeholder="Ej: Carnes & Cortes del Norte S.A."
            required
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Nombre de Contacto / Vendedor</label>
          <InputText
            v-model="form.contact_name"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            placeholder="Ej: Juan Pérez"
          />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Teléfono</label>
            <InputText
              v-model="form.phone"
              class="w-full bg-slate-800 border-slate-700 text-slate-100"
              placeholder="555-1234"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Correo</label>
            <InputText
              v-model="form.email"
              type="email"
              class="w-full bg-slate-800 border-slate-700 text-slate-100"
              placeholder="ventas@proveedor.com"
            />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Dirección / Notas</label>
          <Textarea
            v-model="form.address"
            rows="2"
            class="w-full bg-slate-800 border-slate-700 text-slate-100 text-sm"
            placeholder="Dirección física o condiciones de entrega..."
          />
        </div>
        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" label="Cancelar" severity="secondary" text @click="modalOpen = false" />
          <Button
            type="submit"
            :label="isEditing ? 'Actualizar' : 'Guardar'"
            class="bg-orange-600 border-none hover:bg-orange-500"
            :loading="form.processing"
          />
        </div>
      </form>
    </Dialog>

    <!-- Modal Detalle Insumos del Proveedor -->
    <Dialog
      v-model:visible="viewModalOpen"
      header="Insumos Suministrados por el Proveedor"
      modal
      class="w-full max-w-lg"
    >
      <div v-if="selectedSupplier" class="space-y-4 pt-2">
        <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700/50">
          <h3 class="text-lg font-bold text-slate-100">{{ selectedSupplier.company_name }}</h3>
          <p class="text-xs text-slate-400">Contacto: {{ selectedSupplier.contact_name || 'N/A' }} | Tel: {{ selectedSupplier.phone || 'N/A' }}</p>
          <p v-if="selectedSupplier.address" class="text-xs text-slate-400 mt-1">📍 {{ selectedSupplier.address }}</p>
        </div>

        <div>
          <h4 class="text-xs font-bold uppercase text-slate-400 mb-2">Insumos en Inventario</h4>
          <div v-if="selectedSupplier.ingredients && selectedSupplier.ingredients.length > 0" class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
            <div
              v-for="ing in selectedSupplier.ingredients"
              :key="ing.id"
              class="flex items-center justify-between p-2 rounded-lg bg-slate-800/40 text-xs"
            >
              <div>
                <span class="text-slate-200 font-medium">{{ ing.name }}</span>
                <span class="text-[10px] text-slate-400 ml-2">Stock: {{ parseFloat(ing.current_stock) }} {{ ing.unit_of_measure }}</span>
              </div>
              <span class="font-semibold text-emerald-400">${{ parseFloat(ing.cost_per_unit).toFixed(2) }} / {{ ing.unit_of_measure }}</span>
            </div>
          </div>
          <div v-else class="text-xs text-slate-500 italic py-2">
            Este proveedor no tiene insumos asignados en inventario actualmente.
          </div>
        </div>

        <div class="flex justify-end pt-3">
          <Button label="Cerrar" severity="secondary" text @click="viewModalOpen = false" />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
