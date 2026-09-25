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
import { UserCircle, Plus, Edit3, Trash2, Phone, Mail, CalendarCheck, HeartHandshake, FileText } from 'lucide-vue-next';

const props = defineProps({
  customers: Array,
});

const toast = useToast();
const confirm = useConfirm();
const modalOpen = ref(false);
const isEditing = ref(false);
const viewModalOpen = ref(false);
const selectedCustomer = ref(null);

const form = useForm({
  id: null,
  name: '',
  phone: '',
  email: '',
  preferences_notes: '',
});

function openCreateModal() {
  isEditing.value = false;
  form.reset();
  modalOpen.value = true;
}

function openEditModal(customer) {
  isEditing.value = true;
  form.id = customer.id;
  form.name = customer.name;
  form.phone = customer.phone || '';
  form.email = customer.email || '';
  form.preferences_notes = customer.preferences_notes || '';
  modalOpen.value = true;
}

function openViewModal(customer) {
  selectedCustomer.value = customer;
  viewModalOpen.value = true;
}

function submitForm() {
  if (isEditing.value) {
    form.put(`/customers/${form.id}`, {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cliente actualizado', life: 3000 });
      },
    });
  } else {
    form.post('/customers', {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cliente registrado', life: 3000 });
      },
    });
  }
}

function confirmDelete(customer) {
  confirm.require({
    message: `¿Estás seguro de eliminar el registro del cliente "${customer.name}"?`,
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/customers/${customer.id}`, {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Cliente eliminado', life: 3000 });
        },
      });
    },
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Directorio de Clientes</template>

    <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-6 transition-colors duration-200">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <UserCircle class="w-6 h-6 text-orange-500" />
            Directorio de Clientes & Preferencias
          </h2>
          <p class="text-sm text-slate-500 dark:text-slate-400">
            Consulta el historial de reservas, preferencias gastronómicas y alergias de los clientes habituales.
          </p>
        </div>
        <Button
          label="Nuevo Cliente"
          icon="pi pi-plus"
          class="bg-orange-600 border-none hover:bg-orange-500 text-white shrink-0"
          @click="openCreateModal"
        />
      </div>

      <DataTable :value="customers" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
        <Column field="name" header="Nombre del Cliente" sortable>
          <template #body="{ data }">
            <div class="font-bold text-slate-800 dark:text-slate-100">{{ data.name }}</div>
          </template>
        </Column>
        <Column header="Contacto">
          <template #body="{ data }">
            <div class="space-y-1 text-xs">
              <div v-if="data.phone" class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                <Phone class="w-3.5 h-3.5 text-orange-500 dark:text-orange-400" />
                <span>{{ data.phone }}</span>
              </div>
              <div v-if="data.email" class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                <Mail class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400" />
                <span>{{ data.email }}</span>
              </div>
              <span v-if="!data.phone && !data.email" class="text-slate-400 dark:text-slate-500 italic">Sin contacto registrado</span>
            </div>
          </template>
        </Column>
        <Column header="Preferencias / Alergias">
          <template #body="{ data }">
            <span v-if="data.preferences_notes" class="text-xs text-amber-700 dark:text-amber-300 bg-amber-500/10 border border-amber-500/20 px-2 py-1 rounded-lg line-clamp-1">
              {{ data.preferences_notes }}
            </span>
            <span v-else class="text-xs text-slate-400 dark:text-slate-500 italic">Ninguna</span>
          </template>
        </Column>
        <Column header="Historial" sortable>
          <template #body="{ data }">
            <div class="flex gap-1.5">
              <Tag :value="`${data.reservations_count || 0} reservas`" severity="info" />
              <Tag :value="`${data.orders_count || 0} pedidos`" severity="warn" />
            </div>
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
                title="Ver Historial"
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
      :header="isEditing ? 'Editar Perfil de Cliente' : 'Nuevo Cliente'"
      modal
      class="w-full max-w-md"
    >
      <form @submit.prevent="submitForm" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Nombre Completo *</label>
          <InputText
            v-model="form.name"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            placeholder="Ej: Lic. Carlos Mendoza"
            required
          />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Teléfono</label>
            <InputText
              v-model="form.phone"
              class="w-full bg-slate-800 border-slate-700 text-slate-100"
              placeholder="555-9876"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Correo Electrónico</label>
            <InputText
              v-model="form.email"
              type="email"
              class="w-full bg-slate-800 border-slate-700 text-slate-100"
              placeholder="cliente@ejemplo.com"
            />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Preferencias / Alergias / Notas</label>
          <Textarea
            v-model="form.preferences_notes"
            rows="3"
            class="w-full bg-slate-800 border-slate-700 text-slate-100 text-sm"
            placeholder="Ej: Alérgico a los mariscos, prefiere mesa en terraza junto a la ventana..."
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

    <!-- Modal Historial y Detalle del Cliente -->
    <Dialog
      v-model:visible="viewModalOpen"
      header="Ficha y Visitas del Cliente"
      modal
      class="w-full max-w-lg"
    >
      <div v-if="selectedCustomer" class="space-y-4 pt-2">
        <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700/50">
          <h3 class="text-lg font-bold text-slate-100">{{ selectedCustomer.name }}</h3>
          <div class="flex gap-4 text-xs text-slate-400 mt-1">
            <span>📞 {{ selectedCustomer.phone || 'Sin teléfono' }}</span>
            <span>✉️ {{ selectedCustomer.email || 'Sin correo' }}</span>
          </div>
          <div v-if="selectedCustomer.preferences_notes" class="mt-3 p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs">
            <strong>Preferencias / Alergias:</strong> {{ selectedCustomer.preferences_notes }}
          </div>
        </div>

        <div>
          <h4 class="text-xs font-bold uppercase text-slate-400 mb-2">Últimas Reservas</h4>
          <div v-if="selectedCustomer.reservations && selectedCustomer.reservations.length > 0" class="space-y-2 max-h-56 overflow-y-auto pr-1">
            <div
              v-for="res in selectedCustomer.reservations"
              :key="res.id"
              class="p-2.5 rounded-lg bg-slate-800/40 border border-slate-700/30 flex items-center justify-between text-xs"
            >
              <div>
                <div class="font-semibold text-slate-200">
                  {{ res.reservation_date }} a las {{ res.reservation_time }}
                </div>
                <div class="text-[11px] text-slate-400">
                  {{ res.table ? `${res.table.table_number} (${res.table.area?.name})` : 'Sin mesa asignada' }} · {{ res.guests_count }} personas
                </div>
              </div>
              <Tag
                :value="res.status"
                :severity="res.status === 'confirmed' ? 'success' : res.status === 'completed' ? 'info' : res.status === 'cancelled' ? 'danger' : 'warn'"
              />
            </div>
          </div>
          <div v-else class="text-xs text-slate-500 italic py-2">
            No tiene reservas previas registradas.
          </div>
        </div>

        <div class="flex justify-end pt-3">
          <Button label="Cerrar" severity="secondary" text @click="viewModalOpen = false" />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
