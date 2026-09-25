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
import Tag from 'primevue/tag';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { MapPin, Plus, Edit3, Trash2, Users, Layers, Armchair } from 'lucide-vue-next';

const props = defineProps({
  areas: Array,
  tables: Array,
});

const toast = useToast();
const confirm = useConfirm();

// Modal States
const areaModalOpen = ref(false);
const isEditingArea = ref(false);

const tableModalOpen = ref(false);
const isEditingTable = ref(false);

// Forms
const areaForm = useForm({
  id: null,
  name: '',
  description: '',
});

const tableForm = useForm({
  id: null,
  area_id: null,
  table_number: '',
  capacity: 4,
  status: 'available',
});

const statusOptions = [
  { label: 'Disponible', value: 'available', severity: 'success' },
  { label: 'Ocupada', value: 'occupied', severity: 'danger' },
  { label: 'Reservada', value: 'reserved', severity: 'warn' },
  { label: 'Mantenimiento', value: 'maintenance', severity: 'secondary' },
];

function getStatusBadge(status) {
  switch (status) {
    case 'available':
      return { label: 'Disponible', severity: 'success' };
    case 'occupied':
      return { label: 'Ocupada', severity: 'danger' };
    case 'reserved':
      return { label: 'Reservada', severity: 'warn' };
    case 'maintenance':
      return { label: 'Mantenimiento', severity: 'secondary' };
    default:
      return { label: status, severity: 'info' };
  }
}

// Area CRUD Methods
function openCreateAreaModal() {
  isEditingArea.value = false;
  areaForm.reset();
  areaModalOpen.value = true;
}

function openEditAreaModal(area) {
  isEditingArea.value = true;
  areaForm.id = area.id;
  areaForm.name = area.name;
  areaForm.description = area.description;
  areaModalOpen.value = true;
}

function submitAreaForm() {
  if (isEditingArea.value) {
    areaForm.put(`/areas/${areaForm.id}`, {
      onSuccess: () => {
        areaModalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Zona actualizada', life: 3000 });
      },
    });
  } else {
    areaForm.post('/areas', {
      onSuccess: () => {
        areaModalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Zona creada', life: 3000 });
      },
    });
  }
}

function confirmDeleteArea(area) {
  confirm.require({
    message: `¿Estás seguro de eliminar la zona "${area.name}"?`,
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/areas/${area.id}`, {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminada', detail: 'Zona eliminada', life: 3000 });
        },
      });
    },
  });
}

// Table CRUD Methods
function openCreateTableModal() {
  isEditingTable.value = false;
  tableForm.reset();
  if (props.areas.length > 0) {
    tableForm.area_id = props.areas[0].id;
  }
  tableModalOpen.value = true;
}

function openEditTableModal(table) {
  isEditingTable.value = true;
  tableForm.id = table.id;
  tableForm.area_id = table.area_id;
  tableForm.table_number = table.table_number;
  tableForm.capacity = table.capacity;
  tableForm.status = table.status;
  tableModalOpen.value = true;
}

function submitTableForm() {
  if (isEditingTable.value) {
    tableForm.put(`/tables/${tableForm.id}`, {
      onSuccess: () => {
        tableModalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Mesa actualizada', life: 3000 });
      },
    });
  } else {
    tableForm.post('/tables', {
      onSuccess: () => {
        tableModalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Mesa creada', life: 3000 });
      },
    });
  }
}

function confirmDeleteTable(table) {
  confirm.require({
    message: `¿Estás seguro de eliminar la mesa "${table.table_number}"?`,
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/tables/${table.id}`, {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminada', detail: 'Mesa eliminada', life: 3000 });
        },
      });
    },
  });
}

function quickChangeTableStatus(table, newStatus) {
  router.put(`/tables/${table.id}`, {
    area_id: table.area_id,
    table_number: table.table_number,
    capacity: table.capacity,
    status: newStatus,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'info', summary: 'Estado', detail: `Mesa cambiada a ${newStatus}`, life: 2000 });
    }
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Zonas y Distribución de Mesas</template>

    <div class="space-y-6">
      <!-- Header Banner -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-xl font-bold text-slate-100 flex items-center gap-2">
            <MapPin class="w-6 h-6 text-orange-500" />
            Zonas y Mesas del Restaurante
          </h2>
          <p class="text-sm text-slate-400">
            Organiza los ambientes (Salón, Terraza, VIP) y gestiona la capacidad y disponibilidad de cada mesa.
          </p>
        </div>
        <div class="flex gap-2 shrink-0">
          <Button
            label="Nueva Zona"
            icon="pi pi-plus"
            class="bg-slate-800 hover:bg-slate-700 text-slate-200 border-slate-700"
            @click="openCreateAreaModal"
          />
          <Button
            label="Nueva Mesa"
            icon="pi pi-plus"
            class="bg-orange-600 border-none hover:bg-orange-500"
            @click="openCreateTableModal"
            :disabled="areas.length === 0"
          />
        </div>
      </div>

      <!-- Tabs de Gestión -->
      <Tabs value="0" class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-4 transition-colors duration-200">
        <TabList class="border-b border-slate-200 dark:border-slate-800">
          <Tab value="0" class="text-slate-600 dark:text-slate-300 font-semibold">
            <div class="flex items-center gap-2">
              <Armchair class="w-4 h-4 text-orange-500 dark:text-orange-400" />
              <span>Mapa y Listado de Mesas ({{ tables.length }})</span>
            </div>
          </Tab>
          <Tab value="1" class="text-slate-600 dark:text-slate-300 font-semibold">
            <div class="flex items-center gap-2">
              <Layers class="w-4 h-4 text-orange-500 dark:text-orange-400" />
              <span>Zonas / Áreas ({{ areas.length }})</span>
            </div>
          </Tab>
        </TabList>

        <TabPanels>
          <!-- PANEL 1: MESAS -->
          <TabPanel value="0" class="pt-4">
            <!-- Vista de Mesas Agrupadas por Zona -->
            <div v-for="area in areas" :key="area.id" class="mb-8 last:mb-0">
              <div class="flex items-center justify-between border-b border-slate-800/80 pb-2 mb-4">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                  <h3 class="text-base font-bold text-slate-200">{{ area.name }}</h3>
                  <span class="text-xs text-slate-400">({{ area.tables?.length || 0 }} mesas)</span>
                </div>
              </div>

              <!-- Grid de Mesas -->
              <div v-if="area.tables && area.tables.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div
                  v-for="table in area.tables"
                  :key="table.id"
                  class="bg-slate-950/70 border border-slate-800 rounded-xl p-4 flex flex-col justify-between hover:border-slate-700 transition-all group"
                >
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="font-bold text-slate-100 text-base">{{ table.table_number }}</h4>
                      <Tag
                        :value="getStatusBadge(table.status).label"
                        :severity="getStatusBadge(table.status).severity"
                      />
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-400 mb-3">
                      <Users class="w-3.5 h-3.5 text-slate-500" />
                      <span>Capacidad: <strong class="text-slate-200">{{ table.capacity }} personas</strong></span>
                    </div>
                  </div>

                  <!-- Quick Actions & Status -->
                  <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between">
                    <!-- Selector Rápido de Estado -->
                    <select
                      :value="table.status"
                      @change="quickChangeTableStatus(table, $event.target.value)"
                      class="bg-slate-900 border border-slate-700 text-[11px] rounded-lg px-2 py-1 text-slate-300 focus:outline-none"
                    >
                      <option value="available">🟢 Libre</option>
                      <option value="occupied">🔴 Ocupada</option>
                      <option value="reserved">🟡 Reservada</option>
                      <option value="maintenance">⚪ Mantenimiento</option>
                    </select>

                    <div class="flex gap-1">
                      <Button icon="pi pi-pencil" class="p-button-text p-button-sm text-blue-400 p-1" @click="openEditTableModal(table)" />
                      <Button icon="pi pi-trash" class="p-button-text p-button-sm text-red-400 p-1" @click="confirmDeleteTable(table)" />
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-xs text-slate-500 italic py-2">
                No hay mesas registradas en esta zona.
              </div>
            </div>
          </TabPanel>

          <!-- PANEL 2: ZONAS / ÁREAS -->
          <TabPanel value="1" class="pt-4">
            <DataTable :value="areas" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
              <Column field="name" header="Nombre de Zona" sortable>
                <template #body="{ data }">
                  <span class="font-semibold text-slate-800 dark:text-slate-100">{{ data.name }}</span>
                </template>
              </Column>
              <Column field="description" header="Descripción">
                <template #body="{ data }">
                  <span class="text-slate-600 dark:text-slate-300 text-sm">{{ data.description || '—' }}</span>
                </template>
              </Column>
              <Column header="Mesas Asignadas" sortable>
                <template #body="{ data }">
                  <Tag :value="`${data.tables_count || 0} mesas`" severity="info" />
                </template>
              </Column>
              <Column header="Acciones" style="width: 120px">
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <Button icon="pi pi-pencil" text rounded size="small" severity="info" class="!w-8 !h-8 !p-0 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10" @click="openEditAreaModal(data)" title="Editar Zona" />
                    <Button icon="pi pi-trash" text rounded size="small" severity="danger" class="!w-8 !h-8 !p-0 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10" @click="confirmDeleteArea(data)" title="Eliminar Zona" />
                  </div>
                </template>
              </Column>
            </DataTable>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <!-- Modal Formulario Área -->
    <Dialog
      v-model:visible="areaModalOpen"
      :header="isEditingArea ? 'Editar Zona' : 'Nueva Zona'"
      modal
      class="w-full max-w-md"
    >
      <form @submit.prevent="submitAreaForm" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Nombre de Zona *</label>
          <InputText
            v-model="areaForm.name"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            placeholder="Ej: Terraza Principal, Salón VIP"
            required
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Descripción</label>
          <Textarea
            v-model="areaForm.description"
            rows="3"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            placeholder="Detalles sobre este espacio..."
          />
        </div>
        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" label="Cancelar" severity="secondary" text @click="areaModalOpen = false" />
          <Button
            type="submit"
            :label="isEditingArea ? 'Actualizar' : 'Guardar'"
            class="bg-orange-600 border-none hover:bg-orange-500"
            :loading="areaForm.processing"
          />
        </div>
      </form>
    </Dialog>

    <!-- Modal Formulario Mesa -->
    <Dialog
      v-model:visible="tableModalOpen"
      :header="isEditingTable ? 'Editar Mesa' : 'Nueva Mesa'"
      modal
      class="w-full max-w-md"
    >
      <form @submit.prevent="submitTableForm" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Zona / Ambiente *</label>
          <Select
            v-model="tableForm.area_id"
            :options="areas"
            optionLabel="name"
            optionValue="id"
            placeholder="Selecciona el área"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            required
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Identificador de Mesa *</label>
          <InputText
            v-model="tableForm.table_number"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            placeholder="Ej: Mesa 1, Mesa VIP 2, Barra 4"
            required
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Capacidad (Personas / Sillas) *</label>
          <InputNumber
            v-model="tableForm.capacity"
            :min="1"
            :max="50"
            class="w-full"
            required
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Estado Inicial</label>
          <Select
            v-model="tableForm.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            required
          />
        </div>
        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" label="Cancelar" severity="secondary" text @click="tableModalOpen = false" />
          <Button
            type="submit"
            :label="isEditingTable ? 'Actualizar' : 'Guardar'"
            class="bg-orange-600 border-none hover:bg-orange-500"
            :loading="tableForm.processing"
          />
        </div>
      </form>
    </Dialog>
  </AdminLayout>
</template>
