<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
  users: Array,
  roles: Array,
});

const toast = useToast();
const confirm = useConfirm();
const modalOpen = ref(false);
const isEditing = ref(false);

const form = useForm({
  id: null,
  role_id: null,
  name: '',
  email: '',
  phone: '',
  password: '',
  is_active: true,
});

function openCreateModal() {
  isEditing.value = false;
  form.reset();
  if (props.roles.length > 0) {
    form.role_id = props.roles[0].id;
  }
  modalOpen.value = true;
}

function openEditModal(usr) {
  isEditing.value = true;
  form.id = usr.id;
  form.role_id = usr.role_id;
  form.name = usr.name;
  form.email = usr.email;
  form.phone = usr.phone;
  form.password = '';
  form.is_active = usr.is_active;
  modalOpen.value = true;
}

function submitForm() {
  if (isEditing.value) {
    form.put(`/users/${form.id}`, {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Usuario actualizado', life: 3000 });
      },
    });
  } else {
    form.post('/users', {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Usuario registrado', life: 3000 });
      },
    });
  }
}

function confirmDelete(usr) {
  confirm.require({
    message: `¿Eliminar al usuario "${usr.name}" del sistema?`,
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/users/${usr.id}`, {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Usuario eliminado', life: 3000 });
        },
      });
    },
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Usuarios & Permisos</template>

    <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-6 transition-colors duration-200">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Usuarios del Personal</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400">Gestión de accesos para Administradores, Chefs, Meseros y Recepción</p>
        </div>
        <Button label="Nuevo Usuario" icon="pi pi-plus" class="bg-orange-600 border-none hover:bg-orange-500 text-white" @click="openCreateModal" />
      </div>

      <DataTable :value="users" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
        <Column field="name" header="Nombre" sortable>
          <template #body="{ data }">
            <span class="font-medium text-slate-800 dark:text-slate-100">{{ data.name }}</span>
          </template>
        </Column>
        <Column field="email" header="Email">
          <template #body="{ data }">
            <span class="text-slate-600 dark:text-slate-300 text-xs font-mono">{{ data.email }}</span>
          </template>
        </Column>
        <Column field="role.name" header="Rol / Puesto" sortable>
          <template #body="{ data }">
            <Tag :value="data.role?.name || 'Sin Rol'" severity="warn" />
          </template>
        </Column>
        <Column field="phone" header="Teléfono">
          <template #body="{ data }">
            <span class="text-slate-600 dark:text-slate-400 text-xs">{{ data.phone || '—' }}</span>
          </template>
        </Column>
        <Column field="is_active" header="Estado">
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Activo' : 'Inactivo'" :severity="data.is_active ? 'success' : 'danger'" />
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
                title="Editar Usuario"
              />
              <Button
                icon="pi pi-trash"
                text
                rounded
                size="small"
                severity="danger"
                class="!w-8 !h-8 !p-0 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10"
                @click="confirmDelete(data)"
                title="Eliminar Usuario"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Modal Form -->
    <Dialog v-model:visible="modalOpen" :header="isEditing ? 'Editar Usuario' : 'Nuevo Usuario'" modal class="w-full max-w-md">
      <form @submit.prevent="submitForm" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre Completo</label>
          <InputText v-model="form.name" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Correo Electrónico</label>
          <InputText type="email" v-model="form.email" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Rol / Puesto</label>
            <Select v-model="form.role_id" :options="roles" optionLabel="name" optionValue="id" class="w-full bg-slate-50 border-slate-300 dark:bg-slate-800 dark:border-slate-700" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Teléfono</label>
            <InputText v-model="form.phone" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">
            Contraseña <span v-if="isEditing" class="text-[10px] text-slate-500 dark:text-slate-400 font-normal">(dejar en blanco para conservar)</span>
          </label>
          <InputText type="password" v-model="form.password" class="w-full bg-slate-50 border-slate-300 text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-100" :required="!isEditing" />
        </div>
        <div class="flex items-center justify-between">
          <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Acceso Activo</label>
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
