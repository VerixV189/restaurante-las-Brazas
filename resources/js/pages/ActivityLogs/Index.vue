<script setup>
import AdminLayout from '../../layouts/AdminLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

defineProps({
  logs: Object,
});
</script>

<template>
  <AdminLayout>
    <template #header>Historial de Logs & Auditoría</template>

    <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-6 transition-colors duration-200">
      <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Registro de Movimientos del Sistema</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400">Auditoría automática en tiempo real de creaciones, ediciones y eliminaciones de datos</p>
      </div>

      <DataTable :value="logs.data" paginator :rows="logs.per_page" :totalRecords="logs.total" lazy class="p-datatable-sm text-sm" responsiveLayout="scroll">
        <Column field="created_at" header="Fecha y Hora">
          <template #body="{ data }">
            <span class="text-slate-600 dark:text-slate-300 font-mono text-xs">{{ new Date(data.created_at).toLocaleString() }}</span>
          </template>
        </Column>
        <Column field="user.name" header="Usuario / Sistema">
          <template #body="{ data }">
            <span class="text-slate-800 dark:text-slate-200 font-semibold">{{ data.user?.name || 'Sistema / Seeder' }}</span>
          </template>
        </Column>
        <Column field="action" header="Acción">
          <template #body="{ data }">
            <Tag
              :value="data.action.toUpperCase()"
              :severity="data.action === 'created' ? 'success' : data.action === 'updated' ? 'info' : 'danger'"
            />
          </template>
        </Column>
        <Column field="model_type" header="Entidad">
          <template #body="{ data }">
            <span class="font-mono text-xs text-orange-600 dark:text-orange-400 font-semibold">{{ data.model_type.split('\\').pop() }} #{{ data.model_id }}</span>
          </template>
        </Column>
        <Column field="new_values" header="Cambios Realizados">
          <template #body="{ data }">
            <pre class="bg-slate-100 border border-slate-200 text-slate-800 dark:bg-slate-950 dark:border-slate-800 dark:text-slate-300 p-2.5 rounded-lg text-[11px] font-mono max-h-28 overflow-y-auto max-w-sm">{{ JSON.stringify(data.new_values || data.old_values, null, 2) }}</pre>
          </template>
        </Column>
        <Column field="ip_address" header="IP">
          <template #body="{ data }">
            <span class="text-xs font-mono text-slate-500 dark:text-slate-400">{{ data.ip_address || '127.0.0.1' }}</span>
          </template>
        </Column>
      </DataTable>
    </div>
  </AdminLayout>
</template>
