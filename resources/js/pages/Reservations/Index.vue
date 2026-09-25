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
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
  reservations: Array,
  customers: Array,
  tables: Array,
});

const toast = useToast();
const confirm = useConfirm();
const modalOpen = ref(false);
const isEditing = ref(false);

const statusOptions = [
  { label: 'Pendiente', value: 'pending', severity: 'warn' },
  { label: 'Confirmada', value: 'confirmed', severity: 'info' },
  { label: 'En Mesa', value: 'seated', severity: 'success' },
  { label: 'Cancelada', value: 'cancelled', severity: 'danger' },
  { label: 'Completada', value: 'completed', severity: 'secondary' },
];

const form = useForm({
  id: null,
  customer_name: '',
  customer_phone: '',
  customer_email: '',
  table_id: null,
  reservation_date: new Date(),
  guest_count: 2,
  status: 'pending',
  special_requests: '',
});

function openCreateModal() {
  isEditing.value = false;
  form.reset();
  form.reservation_date = new Date();
  modalOpen.value = true;
}

function openEditModal(res) {
  isEditing.value = true;
  form.id = res.id;
  form.customer_name = res.customer?.name || '';
  form.customer_phone = res.customer?.phone || '';
  form.customer_email = res.customer?.email || '';
  form.table_id = res.table_id;
  form.reservation_date = new Date(res.reservation_date);
  form.guest_count = res.guest_count;
  form.status = res.status;
  form.special_requests = res.special_requests;
  modalOpen.value = true;
}

function submitForm() {
  if (isEditing.value) {
    form.put(`/reservations/${form.id}`, {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Reserva actualizada', life: 3000 });
      },
    });
  } else {
    form.post('/reservations', {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Reserva registrada', life: 3000 });
      },
    });
  }
}

function confirmDelete(res) {
  confirm.require({
    message: `¿Eliminar la reserva de "${res.customer?.name}"?`,
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/reservations/${res.id}`, {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminada', detail: 'Reserva eliminada', life: 3000 });
        },
      });
    },
  });
}

function getStatusSeverity(status) {
  const item = statusOptions.find(o => o.value === status);
  return item ? item.severity : 'info';
}
</script>

<template>
  <AdminLayout>
    <template #header>Gestión de Reservas & Mesas</template>

    <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-6 transition-colors duration-200">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Reservas de Comensales</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400">Controla la agenda de reservaciones, asignación de mesas y número de personas</p>
        </div>
        <Button label="Nueva Reserva" icon="pi pi-plus" class="bg-orange-600 border-none hover:bg-orange-500 text-white" @click="openCreateModal" />
      </div>

      <DataTable :value="reservations" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
        <Column field="customer.name" header="Cliente" sortable>
          <template #body="{ data }">
            <span class="font-medium text-slate-800 dark:text-slate-100">{{ data.customer?.name }}</span>
          </template>
        </Column>
        <Column field="reservation_date" header="Fecha y Hora" sortable>
          <template #body="{ data }">
            <span class="text-orange-600 dark:text-orange-400 font-medium">{{ new Date(data.reservation_date).toLocaleString() }}</span>
          </template>
        </Column>
        <Column field="table.table_number" header="Mesa Asignada">
          <template #body="{ data }">
            <Tag :value="data.table ? `${data.table.table_number} (${data.table.area?.name})` : 'Por asignar'" severity="secondary" />
          </template>
        </Column>
        <Column field="guest_count" header="Personas">
          <template #body="{ data }">
            <span class="text-slate-800 dark:text-slate-200 font-semibold">{{ data.guest_count }} comensales</span>
          </template>
        </Column>
        <Column field="status" header="Estado">
          <template #body="{ data }">
            <Tag :value="data.status.toUpperCase()" :severity="getStatusSeverity(data.status)" />
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
                title="Editar Reserva"
              />
              <Button
                icon="pi pi-trash"
                text
                rounded
                size="small"
                severity="danger"
                class="!w-8 !h-8 !p-0 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10"
                @click="confirmDelete(data)"
                title="Eliminar Reserva"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Modal Form -->
    <Dialog v-model:visible="modalOpen" :header="isEditing ? 'Editar Reserva' : 'Nueva Reserva'" modal class="w-full max-w-lg">
      <form @submit.prevent="submitForm" class="space-y-4 pt-2">
        <div v-if="!isEditing">
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Cliente</label>
          <InputText v-model="form.customer_name" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" required />
        </div>
        <div v-if="!isEditing" class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Teléfono</label>
            <InputText v-model="form.customer_phone" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Email</label>
            <InputText v-model="form.customer_email" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha y Hora</label>
            <DatePicker v-model="form.reservation_date" showTime hourFormat="12" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nº Comensales</label>
            <InputNumber v-model="form.guest_count" :min="1" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Mesa Asignada</label>
            <Select v-model="form.table_id" :options="tables" optionLabel="table_number" optionValue="id" placeholder="Asignar mesa..." class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" showClear />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Estado Reserva</label>
            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Peticiones Especiales / Notas</label>
          <Textarea v-model="form.special_requests" rows="2" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" placeholder="Ej: Cumpleaños, silla de bebé..." />
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" label="Cancelar" severity="secondary" text @click="modalOpen = false" />
          <Button type="submit" :label="isEditing ? 'Actualizar' : 'Guardar'" severity="warn" class="bg-orange-600 border-none text-white" :loading="form.processing" />
        </div>
      </form>
    </Dialog>
  </AdminLayout>
</template>
