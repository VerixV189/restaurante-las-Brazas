<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import {
  Flame,
  UtensilsCrossed,
  CalendarCheck,
  Clock,
  MapPin,
  Phone,
  ShieldCheck,
  Star,
  LogIn,
  ChevronRight,
  Sparkles,
  HeartHandshake
} from 'lucide-vue-next';

const props = defineProps({
  categories: Array,
  dishes: Array,
  tables: Array,
});

const toast = useToast();
const selectedCategory = ref('all');
const reserveModalOpen = ref(false);

const filteredDishes = computed(() => {
  if (selectedCategory.value === 'all') return props.dishes;
  return props.dishes.filter(d => d.category_id === selectedCategory.value);
});

const form = useForm({
  customer_name: '',
  customer_phone: '',
  customer_email: '',
  reservation_date: new Date(),
  guest_count: 2,
  table_id: null,
  special_requests: '',
});

function openReservationModal(dishName = null) {
  if (dishName) {
    form.special_requests = `Deseo probar: ${dishName}`;
  }
  reserveModalOpen.value = true;
}

function submitReservation() {
  form.post('/reserve', {
    onSuccess: () => {
      reserveModalOpen.value = false;
      form.reset();
      toast.add({
        severity: 'success',
        summary: '¡Reserva Solicitada!',
        detail: 'Hemos recibido tu solicitud de reserva en Las Brazas.',
        life: 4000
      });
    },
  });
}

function getDishImage(dish) {
  if (dish.image_url) return dish.image_url;
  if (dish.name.toLowerCase().includes('arrachera')) return '/images/arrachera.png';
  return 'https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=600&auto=format&fit=crop';
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-orange-500 selection:text-white">
    <Toast />

    <!-- Navbar -->
    <header class="sticky top-0 z-40 bg-slate-950/80 backdrop-blur-md border-b border-slate-800/80">
      <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
        <!-- Logo -->
        <Link href="/" class="flex items-center gap-3 group">
          <div class="bg-gradient-to-tr from-orange-600 to-amber-500 p-2.5 rounded-2xl text-white shadow-lg shadow-orange-600/20 group-hover:scale-105 transition-transform">
            <Flame class="w-7 h-7" />
          </div>
          <div>
            <span class="font-extrabold text-2xl tracking-wide bg-gradient-to-r from-orange-400 via-amber-300 to-yellow-200 bg-clip-text text-transparent">
              Las Brazas
            </span>
            <p class="text-[10px] tracking-widest text-slate-400 uppercase font-semibold">Steakhouse & Grill</p>
          </div>
        </Link>

        <!-- Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
          <a href="#menu" class="hover:text-orange-400 transition-colors">Menú Especial</a>
          <a href="#experiencia" class="hover:text-orange-400 transition-colors">La Experiencia</a>
          <a href="#reservas" @click.prevent="openReservationModal()" class="hover:text-orange-400 transition-colors">Reservaciones</a>
          <a href="#contacto" class="hover:text-orange-400 transition-colors">Contacto</a>
        </nav>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5">
          <Button
            label="Reservar Mesa"
            icon="pi pi-calendar"
            class="bg-gradient-to-r from-orange-600 to-amber-500 border-none font-semibold text-white shadow-lg shadow-orange-600/25 hover:shadow-orange-600/40 hover:scale-[1.02] transition-all text-xs"
            @click="openReservationModal()"
          />
          <Link href="/register">
            <Button
              icon="pi pi-user-plus"
              label="Registrarse"
              class="p-button-outlined p-button-warning text-orange-400 border-orange-500/40 hover:bg-orange-500/10 text-xs"
            />
          </Link>
          <Link href="/login">
            <Button
              icon="pi pi-user"
              label="Iniciar Sesión"
              class="p-button-outlined p-button-secondary text-slate-300 border-slate-700 hover:border-slate-500 hover:text-white text-xs"
            />
          </Link>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative min-h-[85vh] flex items-center justify-center overflow-hidden py-20 px-6">
      <!-- Background Image & Gradient -->
      <div class="absolute inset-0 z-0">
        <img
          src="/images/hero.png"
          alt="Restaurante Las Brazas"
          class="w-full h-full object-cover object-center scale-105 filter brightness-50"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950/40"></div>
      </div>

      <div class="relative z-10 max-w-5xl mx-auto text-center space-y-6">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-500/10 border border-orange-500/30 backdrop-blur text-orange-400 text-xs font-bold uppercase tracking-widest">
          <Sparkles class="w-4 h-4" /> La Mejor Experiencia a la Parrilla
        </div>

        <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight text-white leading-tight">
          Cortes Premium & Sabor Auténtico a la <span class="bg-gradient-to-r from-orange-400 via-amber-400 to-yellow-300 bg-clip-text text-transparent">Leña</span>
        </h1>

        <p class="text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto font-light leading-relaxed">
          Disfruta de nuestros cortes madurados, cazuelas al carbón y la tradición gastronómica del verdadero asado en un ambiente exclusivo.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
          <a href="#menu">
            <Button
              label="Explorar Menú Digital"
              icon="pi pi-arrow-down"
              class="px-8 py-3.5 bg-orange-600 hover:bg-orange-500 text-white font-bold rounded-2xl shadow-xl shadow-orange-600/30 border-none transition-all"
            />
          </a>
          <Button
            label="Apartar tu Mesa Ahora"
            icon="pi pi-calendar-plus"
            class="px-8 py-3.5 bg-slate-900/90 hover:bg-slate-800 text-slate-100 font-bold rounded-2xl border border-slate-700 backdrop-blur transition-all"
            @click="openReservationModal()"
          />
        </div>
      </div>
    </section>

    <!-- Menu Marketplace Section -->
    <section id="menu" class="max-w-7xl mx-auto px-6 py-20">
      <div class="text-center max-w-2xl mx-auto mb-12 space-y-3">
        <span class="text-xs font-bold text-orange-400 uppercase tracking-widest">Menú de la Casa</span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Nuestra Selección de Especialidades</h2>
        <p class="text-slate-400 text-sm">Platillos preparados al momento por nuestros maestros parrilleros</p>
      </div>

      <!-- Category Filter Tabs -->
      <div class="flex flex-wrap items-center justify-center gap-2 mb-12">
        <button
          @click="selectedCategory = 'all'"
          :class="[
            'px-5 py-2.5 rounded-xl font-medium text-sm transition-all',
            selectedCategory === 'all'
              ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/30'
              : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-slate-200'
          ]"
        >
          Todos los Platillos
        </button>
        <button
          v-for="cat in categories"
          :key="cat.id"
          @click="selectedCategory = cat.id"
          :class="[
            'px-5 py-2.5 rounded-xl font-medium text-sm transition-all',
            selectedCategory === cat.id
              ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/30'
              : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-slate-200'
          ]"
        >
          {{ cat.name }}
        </button>
      </div>

      <!-- Dishes Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div
          v-for="dish in filteredDishes"
          :key="dish.id"
          class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden hover:border-orange-500/40 transition-all duration-300 group flex flex-col justify-between"
        >
          <div>
            <!-- Image Container -->
            <div class="relative h-56 overflow-hidden bg-slate-950">
              <img
                :src="getDishImage(dish)"
                :alt="dish.name"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent opacity-80"></div>
              <Tag
                :value="dish.category?.name || 'Especial'"
                severity="warn"
                class="absolute top-4 left-4 bg-slate-950/80 backdrop-blur border border-amber-500/30 text-amber-300 font-semibold"
              />
            </div>

            <!-- Details -->
            <div class="p-6 space-y-3">
              <div class="flex items-start justify-between gap-3">
                <h3 class="font-bold text-lg text-slate-100 group-hover:text-orange-400 transition-colors">
                  {{ dish.name }}
                </h3>
                <span class="text-xl font-extrabold text-emerald-400 shrink-0">
                  ${{ Number(dish.price).toFixed(2) }}
                </span>
              </div>
              <p class="text-xs text-slate-400 leading-relaxed line-clamp-3">
                {{ dish.description || 'Delicioso platillo preparado con los más altos estándares de calidad.' }}
              </p>
            </div>
          </div>

          <!-- Footer Card Action -->
          <div class="p-6 pt-0 flex items-center justify-between border-t border-slate-800/60 mt-4">
            <div class="flex items-center gap-1.5 text-slate-400 text-xs font-medium">
              <Clock class="w-4 h-4 text-orange-400" />
              <span>{{ dish.prep_time_minutes }} min</span>
            </div>

            <Button
              label="Pedir Mesa / Reservar"
              icon="pi pi-check"
              class="p-button-sm bg-orange-600/15 text-orange-400 border border-orange-500/30 hover:bg-orange-600 hover:text-white"
              @click="openReservationModal(dish.name)"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- System Features Section -->
    <section id="experiencia" class="bg-slate-900/50 border-y border-slate-800/80 py-20 px-6">
      <div class="max-w-7xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
          <span class="text-xs font-bold text-orange-400 uppercase tracking-widest">¿Por qué Las Brazas?</span>
          <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Excelencia en Cada Detalle</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-slate-900 border border-slate-800 p-8 rounded-3xl space-y-4">
            <div class="w-12 h-12 bg-orange-500/10 text-orange-400 rounded-2xl flex items-center justify-center border border-orange-500/20">
              <Flame class="w-6 h-6" />
            </div>
            <h3 class="font-bold text-lg text-slate-100">Parrilla & Leña Seleccionada</h3>
            <p class="text-xs text-slate-400 leading-relaxed">
              Cocinamos con carbón vegetal y leña de mezquite para impartir ese ahumado inconfundible a nuestros cortes.
            </p>
          </div>

          <div class="bg-slate-900 border border-slate-800 p-8 rounded-3xl space-y-4">
            <div class="w-12 h-12 bg-emerald-500/10 text-emerald-400 rounded-2xl flex items-center justify-center border border-emerald-500/20">
              <CalendarCheck class="w-6 h-6" />
            </div>
            <h3 class="font-bold text-lg text-slate-100">Reservas Digitales en Tiempo Real</h3>
            <p class="text-xs text-slate-400 leading-relaxed">
              Elige tu horario y área favorita (Terraza o Salón VIP). Confirmación directa con el equipo de recepción.
            </p>
          </div>

          <div class="bg-slate-900 border border-slate-800 p-8 rounded-3xl space-y-4">
            <div class="w-12 h-12 bg-blue-500/10 text-blue-400 rounded-2xl flex items-center justify-center border border-blue-500/20">
              <ShieldCheck class="w-6 h-6" />
            </div>
            <h3 class="font-bold text-lg text-slate-100">Calidad & Frescura Garantizada</h3>
            <p class="text-xs text-slate-400 leading-relaxed">
              Monitoreamos nuestros insumos a través de nuestro sistema de inventarios garantizando ingredientes frescos día a día.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer Section -->
    <footer id="contacto" class="bg-slate-950 border-t border-slate-800/80 py-16 px-6">
      <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-10">
        <div class="space-y-4">
          <div class="flex items-center gap-3">
            <div class="bg-orange-600 p-2 rounded-xl text-white">
              <Flame class="w-5 h-5" />
            </div>
            <span class="font-extrabold text-xl text-white">Las Brazas</span>
          </div>
          <p class="text-xs text-slate-400 leading-relaxed">
            El restaurante de parrilla y cortes preferido para momentos inolvidables.
          </p>
        </div>

        <div class="space-y-3">
          <h4 class="font-bold text-sm text-slate-200">Horario de Atención</h4>
          <div class="text-xs text-slate-400 space-y-1">
            <p>Lunes a Jueves: 1:00 PM - 10:00 PM</p>
            <p>Viernes y Sábado: 1:00 PM - 11:30 PM</p>
            <p>Domingo: 1:00 PM - 9:00 PM</p>
          </div>
        </div>

        <div class="space-y-3">
          <h4 class="font-bold text-sm text-slate-200">Contacto & Ubicación</h4>
          <div class="text-xs text-slate-400 space-y-2">
            <p class="flex items-center gap-2"><MapPin class="w-4 h-4 text-orange-400" /> Av. Las Brazas #120, Zona Gourmet</p>
            <p class="flex items-center gap-2"><Phone class="w-4 h-4 text-orange-400" /> +52 (555) 0199</p>
          </div>
        </div>

        <div class="space-y-3">
          <h4 class="font-bold text-sm text-slate-200">Portal & Comensales</h4>
          <div class="flex flex-col gap-2 text-xs text-slate-400">
            <Link href="/register" class="hover:text-orange-400 transition-colors">
              → Crear Cuenta de Comensal
            </Link>
            <Link href="/terms" class="hover:text-orange-400 transition-colors">
              → Términos y Condiciones
            </Link>
            <Link href="/login" class="hover:text-orange-400 transition-colors">
              → Iniciar Sesión / Personal
            </Link>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto pt-10 mt-10 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
        <span>© 2026 Restaurante Las Brazas. Todos los derechos reservados.</span>
        <div class="flex items-center gap-6">
          <Link href="/terms" class="hover:text-orange-400 transition-colors underline">
            Términos del Servicio y Condiciones
          </Link>
          <Link href="/register" class="hover:text-orange-400 transition-colors underline">
            Registro de Usuarios
          </Link>
        </div>
      </div>
    </footer>

    <!-- Public Reservation Modal -->
    <Dialog v-model:visible="reserveModalOpen" header="Reservar una Mesa en Las Brazas" modal class="w-full max-w-lg">
      <form @submit.prevent="submitReservation" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Tu Nombre Completo</label>
          <InputText v-model="form.customer_name" class="w-full bg-slate-800 border-slate-700 text-slate-100" placeholder="Ej: Carlos Mendoza" required />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Teléfono Móvil</label>
            <InputText v-model="form.customer_phone" class="w-full bg-slate-800 border-slate-700 text-slate-100" placeholder="555-0000" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Email (opcional)</label>
            <InputText type="email" v-model="form.customer_email" class="w-full bg-slate-800 border-slate-700 text-slate-100" placeholder="cliente@mail.com" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Fecha y Hora</label>
            <DatePicker v-model="form.reservation_date" showTime hourFormat="12" class="w-full bg-slate-800 border-slate-700" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Nº Comensales</label>
            <InputNumber v-model="form.guest_count" :min="1" :max="20" class="w-full bg-slate-800 border-slate-700" required />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Preferencia de Mesa</label>
          <Select v-model="form.table_id" :options="tables" optionLabel="table_number" optionValue="id" placeholder="Cualquier mesa disponible..." class="w-full bg-slate-800 border-slate-700" showClear />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Petición Especial / Comentarios</label>
          <Textarea v-model="form.special_requests" rows="2" class="w-full bg-slate-800 border-slate-700 text-slate-100" placeholder="Ej: Cumpleaños, alergias, silla de bebé..." />
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" label="Cancelar" severity="secondary" text @click="reserveModalOpen = false" />
          <Button type="submit" label="Confirmar Solicitud de Reserva" severity="warn" class="bg-orange-600 border-none" :loading="form.processing" />
        </div>
      </form>
    </Dialog>
  </div>
</template>
