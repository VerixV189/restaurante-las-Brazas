<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '../../layouts/AdminLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import { Plus, Edit3, Trash2, BookOpen, Layers, DollarSign, ChefHat } from 'lucide-vue-next';

const props = defineProps({
  recipes: Array,
  dishes: Array,
  ingredients: Array,
});

const toast = useToast();
const confirm = useConfirm();
const modalOpen = ref(false);
const viewModalOpen = ref(false);
const selectedRecipe = ref(null);
const isEditing = ref(false);

const form = useForm({
  id: null,
  dish_id: null,
  instructions: '',
  ingredients: [
    { ingredient_id: null, quantity_required: 0.1, unit_of_measure: 'kg' }
  ],
});

// Platillos que no tienen receta creada aún
const availableDishesForCreation = computed(() => {
  const existingDishIds = props.recipes.map(r => r.dish_id);
  return props.dishes.filter(d => !existingDishIds.includes(d.id));
});

function openCreateModal() {
  isEditing.value = false;
  form.reset();
  form.ingredients = [
    { ingredient_id: null, quantity_required: 0.1, unit_of_measure: 'kg' }
  ];
  modalOpen.value = true;
}

function openEditModal(recipe) {
  isEditing.value = true;
  form.id = recipe.id;
  form.dish_id = recipe.dish_id;
  form.instructions = recipe.instructions || '';
  form.ingredients = recipe.ingredients.map(item => ({
    ingredient_id: item.ingredient_id,
    quantity_required: parseFloat(item.quantity_required),
    unit_of_measure: item.unit_of_measure || item.ingredient?.unit_of_measure || 'kg',
  }));
  modalOpen.value = true;
}

function openViewModal(recipe) {
  selectedRecipe.value = recipe;
  viewModalOpen.value = true;
}

function addIngredientRow() {
  form.ingredients.push({
    ingredient_id: null,
    quantity_required: 0.1,
    unit_of_measure: 'kg',
  });
}

function removeIngredientRow(index) {
  if (form.ingredients.length > 1) {
    form.ingredients.splice(index, 1);
  }
}

function onIngredientChange(row) {
  const ing = props.ingredients.find(i => i.id === row.ingredient_id);
  if (ing) {
    row.unit_of_measure = ing.unit_of_measure;
  }
}

function calculateEstimatedCost(recipe) {
  if (!recipe || !recipe.ingredients) return 0;
  return recipe.ingredients.reduce((acc, item) => {
    const cost = parseFloat(item.ingredient?.cost_per_unit || 0);
    const qty = parseFloat(item.quantity_required || 0);
    return acc + (cost * qty);
  }, 0);
}

function submitForm() {
  if (isEditing.value) {
    form.put(`/recipes/${form.id}`, {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Receta técnica actualizada', life: 3000 });
      },
    });
  } else {
    form.post('/recipes', {
      onSuccess: () => {
        modalOpen.value = false;
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Receta técnica creada', life: 3000 });
      },
    });
  }
}

function confirmDelete(recipe) {
  confirm.require({
    message: `¿Estás seguro de eliminar la receta de "${recipe.dish?.name}"?`,
    header: 'Confirmar Eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(`/recipes/${recipe.id}`, {
        onSuccess: () => {
          toast.add({ severity: 'success', summary: 'Eliminada', detail: 'Receta técnica eliminada', life: 3000 });
        },
      });
    },
  });
}
</script>

<template>
  <AdminLayout>
    <template #header>Fichas Técnicas y Recetas</template>

    <div class="bg-white border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 rounded-2xl p-6 transition-colors duration-200">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <ChefHat class="w-6 h-6 text-orange-500" />
            Recetario y Fichas Técnicas
          </h2>
          <p class="text-sm text-slate-500 dark:text-slate-400">
            Define los ingredientes requeridos por platillo para calcular costos y automatizar el descuento de inventario.
          </p>
        </div>
        <Button
          label="Nueva Receta"
          icon="pi pi-plus"
          class="bg-orange-600 border-none hover:bg-orange-500 text-white shrink-0"
          @click="openCreateModal"
          :disabled="availableDishesForCreation.length === 0"
        />
      </div>

      <DataTable :value="recipes" paginator :rows="10" class="p-datatable-sm" responsiveLayout="scroll">
        <Column header="Platillo" sortable>
          <template #body="{ data }">
            <div>
              <div class="font-semibold text-slate-800 dark:text-slate-100">{{ data.dish?.name }}</div>
              <div class="text-xs text-orange-600 dark:text-orange-400 font-medium">{{ data.dish?.category?.name }}</div>
            </div>
          </template>
        </Column>
        <Column header="Insumos Requeridos">
          <template #body="{ data }">
            <Tag :value="`${data.ingredients?.length || 0} insumos`" severity="info" />
          </template>
        </Column>
        <Column header="Costo Estimado">
          <template #body="{ data }">
            <span class="font-semibold text-amber-600 dark:text-amber-400">
              ${{ calculateEstimatedCost(data).toFixed(2) }}
            </span>
          </template>
        </Column>
        <Column header="Precio de Venta">
          <template #body="{ data }">
            <span class="font-semibold text-emerald-600 dark:text-emerald-400">
              ${{ parseFloat(data.dish?.price || 0).toFixed(2) }}
            </span>
          </template>
        </Column>
        <Column header="Margen">
          <template #body="{ data }">
            <span class="text-xs font-semibold px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
              {{ (parseFloat(data.dish?.price || 0) > 0)
                ? (((parseFloat(data.dish?.price) - calculateEstimatedCost(data)) / parseFloat(data.dish?.price)) * 100).toFixed(1) + '%'
                : '0%' }}
            </span>
          </template>
        </Column>
        <Column header="Instrucciones">
          <template #body="{ data }">
            <span class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">
              {{ data.instructions || 'Sin notas especiales' }}
            </span>
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
                title="Ver Detalle"
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
      :header="isEditing ? 'Editar Ficha Técnica de Receta' : 'Nueva Ficha Técnica de Receta'"
      modal
      class="w-full max-w-2xl"
    >
      <form @submit.prevent="submitForm" class="space-y-4 pt-2">
        <!-- Selector de Platillo -->
        <div v-if="!isEditing">
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Platillo *</label>
          <Select
            v-model="form.dish_id"
            :options="availableDishesForCreation"
            optionLabel="name"
            optionValue="id"
            placeholder="Selecciona el platillo para la receta"
            class="w-full bg-slate-800 border-slate-700 text-slate-100"
            required
          />
        </div>

        <!-- Instrucciones de Cocina -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Instrucciones / Procedimiento de Preparación</label>
          <Textarea
            v-model="form.instructions"
            rows="3"
            class="w-full bg-slate-800 border-slate-700 text-slate-100 text-sm"
            placeholder="Paso 1: Sellar corte a 250°C. Paso 2: Agregar especias..."
          />
        </div>

        <!-- Lista Dinámica de Ingredientes -->
        <div class="border-t border-slate-800 pt-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-200 uppercase tracking-wide">
              Insumos e Ingredientes por Porción
            </h3>
            <Button
              type="button"
              label="Agregar Insumo"
              icon="pi pi-plus"
              size="small"
              class="bg-slate-800 border border-slate-700 text-slate-200 hover:bg-slate-700"
              @click="addIngredientRow"
            />
          </div>

          <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
            <div
              v-for="(row, idx) in form.ingredients"
              :key="idx"
              class="flex items-center gap-2 p-2 bg-slate-800/60 border border-slate-700/50 rounded-xl"
            >
              <div class="flex-1">
                <Select
                  v-model="row.ingredient_id"
                  :options="ingredients"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Selecciona insumo"
                  class="w-full bg-slate-900 border-slate-700 text-xs"
                  @change="onIngredientChange(row)"
                  required
                />
              </div>
              <div class="w-28">
                <InputNumber
                  v-model="row.quantity_required"
                  :minFractionDigits="1"
                  :maxFractionDigits="3"
                  :min="0.001"
                  placeholder="Cant."
                  class="w-full text-xs"
                  required
                />
              </div>
              <div class="w-24">
                <InputText
                  v-model="row.unit_of_measure"
                  placeholder="Unidad (kg, g, l)"
                  class="w-full bg-slate-900 border-slate-700 text-xs"
                  required
                />
              </div>
              <Button
                type="button"
                icon="pi pi-trash"
                class="p-button-text p-button-sm text-red-400 shrink-0"
                @click="removeIngredientRow(idx)"
                :disabled="form.ingredients.length <= 1"
              />
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-800">
          <Button type="button" label="Cancelar" severity="secondary" text @click="modalOpen = false" />
          <Button
            type="submit"
            :label="isEditing ? 'Actualizar Receta' : 'Guardar Receta'"
            class="bg-orange-600 border-none hover:bg-orange-500"
            :loading="form.processing"
          />
        </div>
      </form>
    </Dialog>

    <!-- Modal Detalle / Ficha Técnica -->
    <Dialog
      v-model:visible="viewModalOpen"
      header="Ficha Técnica de Receta"
      modal
      class="w-full max-w-lg"
    >
      <div v-if="selectedRecipe" class="space-y-4 pt-2">
        <div class="p-4 bg-slate-800/50 rounded-xl border border-slate-700/50">
          <h3 class="text-lg font-bold text-orange-400">{{ selectedRecipe.dish?.name }}</h3>
          <p class="text-xs text-slate-400 mb-2">{{ selectedRecipe.dish?.description }}</p>
          <div class="flex gap-4 text-xs font-semibold">
            <span class="text-slate-300">Precio Venta: <strong class="text-emerald-400">${{ parseFloat(selectedRecipe.dish?.price || 0).toFixed(2) }}</strong></span>
            <span class="text-slate-300">Costo Insumos: <strong class="text-amber-400">${{ calculateEstimatedCost(selectedRecipe).toFixed(2) }}</strong></span>
          </div>
        </div>

        <div>
          <h4 class="text-xs font-bold uppercase text-slate-400 mb-2">Insumos Requeridos</h4>
          <div class="space-y-1.5">
            <div
              v-for="item in selectedRecipe.ingredients"
              :key="item.id"
              class="flex items-center justify-between p-2 rounded-lg bg-slate-800/40 text-xs"
            >
              <span class="text-slate-200 font-medium">{{ item.ingredient?.name }}</span>
              <span class="text-slate-400 font-mono">
                {{ parseFloat(item.quantity_required) }} {{ item.unit_of_measure }}
              </span>
            </div>
          </div>
        </div>

        <div v-if="selectedRecipe.instructions">
          <h4 class="text-xs font-bold uppercase text-slate-400 mb-1">Procedimiento</h4>
          <p class="text-xs text-slate-300 bg-slate-800/30 p-3 rounded-lg border border-slate-700/40 leading-relaxed whitespace-pre-line">
            {{ selectedRecipe.instructions }}
          </p>
        </div>

        <div class="flex justify-end pt-3">
          <Button label="Cerrar" severity="secondary" text @click="viewModalOpen = false" />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
