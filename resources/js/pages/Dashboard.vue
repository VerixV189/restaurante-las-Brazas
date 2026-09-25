<script setup>
import AdminLayout from '../layouts/AdminLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import {
  Flame,
  Boxes,
  CalendarCheck,
  Users,
  AlertTriangle,
  History
} from 'lucide-vue-next';

defineProps({
  stats: Object,
  lowStockList: Array,
  recentActivities: Array,
});
</script>

<template>
  <AdminLayout>
    <template #header>Dashboard General</template>

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <!-- Platillos -->
      <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-5 rounded-2xl flex items-center justify-between transition-colors duration-200">
        <div>
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Platillos Activos</p>
          <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ stats.available_dishes }} / {{ stats.total_dishes }}</h3>
        </div>
        <div class="p-3 bg-orange-500/10 text-orange-600 dark:text-orange-400 rounded-xl border border-orange-500/20">
          <Flame class="w-6 h-6" />
        </div>
      </div>

      <!-- Insumos -->
      <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-5 rounded-2xl flex items-center justify-between transition-colors duration-200">
        <div>
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Insumos</p>
          <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ stats.total_ingredients }}</h3>
        </div>
        <div class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl border border-blue-500/20">
          <Boxes class="w-6 h-6" />
        </div>
      </div>

      <!-- Alertas de Stock Bajo -->
      <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-5 rounded-2xl flex items-center justify-between transition-colors duration-200">
        <div>
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Bajo Stock</p>
          <h3 class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ stats.low_stock_ingredients }}</h3>
        </div>
        <div class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-500/20">
          <AlertTriangle class="w-6 h-6" />
        </div>
      </div>

      <!-- Reservas Pendientes -->
      <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 p-5 rounded-2xl flex items-center justify-between transition-colors duration-200">
        <div>
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Reservas Hoy</p>
          <h3 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.today_reservations }}</h3>
        </div>
        <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-500/20">
          <CalendarCheck class="w-6 h-6" />
        </div>
      </div>
    </div>

    <!-- Section: Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Low Stock Warning Table -->
      <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 transition-colors duration-200">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <AlertTriangle class="w-5 h-5 text-amber-500 dark:text-amber-400" />
            <h2 class="font-semibold text-slate-800 dark:text-slate-200">Alertas de Bajo Stock</h2>
          </div>
          <Tag :value="`${lowStockList.length} Insumos`" severity="warn" />
        </div>

        <DataTable :value="lowStockList" size="small" class="p-datatable-sm text-sm" responsiveLayout="scroll">
          <Column field="name" header="Ingrediente">
            <template #body="{ data }">
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ data.name }}</span>
            </template>
          </Column>
          <Column field="current_stock" header="Stock Actual">
            <template #body="{ data }">
              <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ data.current_stock }} {{ data.unit_of_measure }}</span>
            </template>
          </Column>
          <Column field="minimum_stock" header="Stock Mínimo">
            <template #body="{ data }">
              <span class="text-slate-500 dark:text-slate-400">{{ data.minimum_stock }} {{ data.unit_of_measure }}</span>
            </template>
          </Column>
        </DataTable>
      </div>

      <!-- Recent Activities Table -->
      <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-5 transition-colors duration-200">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <History class="w-5 h-5 text-orange-500 dark:text-orange-400" />
            <h2 class="font-semibold text-slate-800 dark:text-slate-200">Actividad Reciente (Auditoría)</h2>
          </div>
        </div>

        <DataTable :value="recentActivities" size="small" class="p-datatable-sm text-sm" responsiveLayout="scroll">
          <Column field="action" header="Acción">
            <template #body="{ data }">
              <Tag
                :value="data.action"
                :severity="data.action === 'created' ? 'success' : data.action === 'updated' ? 'info' : 'danger'"
              />
            </template>
          </Column>
          <Column field="model_type" header="Módulo">
            <template #body="{ data }">
              <span class="font-mono text-xs text-slate-600 dark:text-slate-300 font-semibold">{{ data.model_type.split('\\').pop() }}</span>
            </template>
          </Column>
          <Column field="created_at" header="Fecha">
            <template #body="{ data }">
              <span class="text-xs text-slate-500 dark:text-slate-400">{{ new Date(data.created_at).toLocaleString() }}</span>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>
  </AdminLayout>
</template>
