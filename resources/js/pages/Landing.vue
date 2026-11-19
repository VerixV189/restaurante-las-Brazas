<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useTheme } from '../composables/useTheme';
import NetworkStatusBadge from '../components/NetworkStatusBadge.vue';
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
  CalendarCheck,
  Clock,
  MapPin,
  Phone,
  ShieldCheck,
  Star,
  Sparkles,
  MessageSquare,
  CheckCircle2,
  Sun,
  Moon,
  Mail,
  Menu,
  X
} from 'lucide-vue-next';

const props = defineProps({
  categories: Array,
  dishes: Array,
  tables: Array,
});

const { isDark, initTheme, toggleTheme } = useTheme();
onMounted(() => {
  initTheme();
});

const toast = useToast();
const selectedCategory = ref('all');
const reserveModalOpen = ref(false);
const testimonialModalOpen = ref(false);
const mobileMenuOpen = ref(false);

const filteredDishes = computed(() => {
  if (selectedCategory.value === 'all') return props.dishes;
  return props.dishes.filter(d => d.category_id === selectedCategory.value);
});

// Formulario de Reserva de Mesa
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
        life: 4000,
      });
    },
  });
}

// Formulario Contáctenos (envío directo a Gmail vía SMTP)
const contactSentSuccess = ref(false);
const contactSubjectOptions = [
  'Consulta General',
  'Reserva Grupal o Evento Corporativo',
  'Pedidos & Delivery',
  'Sugerencia o Felicitación',
  'Proveedores',
];

const contactForm = useForm({
  name: '',
  email: '',
  phone: '',
  subject: 'Consulta General',
  message: '',
  website: '',
});

function submitContactForm() {
  contactSentSuccess.value = false;
  contactForm.post('/contact', {
    preserveScroll: true,
    onSuccess: () => {
      contactSentSuccess.value = true;
      contactForm.reset('name', 'email', 'phone', 'message', 'website');
      contactForm.subject = 'Consulta General';
      toast.add({
        severity: 'success',
        summary: '¡Mensaje Enviado!',
        detail: 'Tu consulta fue enviada directamente a nuestro correo oficial. Te responderemos muy pronto.',
        life: 5000,
      });
    },
    onError: () => {
      toast.add({
        severity: 'warn',
        summary: 'Verifica los datos',
        detail: 'Por favor revisa los campos marcados en el formulario de contacto.',
        life: 4000,
      });
    },
  });
}

function getDishImage(dish) {
  if (dish.image_url) return dish.image_url;
  if (dish.name.toLowerCase().includes('arrachera')) return '/images/arrachera.png';
  return 'https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=600&auto=format&fit=crop';
}

// Muro de Comentarios y Testimonios de Comensales
const newTestimonial = ref({
  name: '',
  role: 'Comensal Frecuente',
  rating: 5,
  comment: '',
});

const testimonials = ref([
  {
    id: 1,
    name: 'Carlos Mendoza',
    role: 'Comensal Frecuente',
    avatar: 'CM',
    avatarBg: 'from-orange-500 to-amber-500',
    rating: 5,
    comment: 'La facilidad para reservar la mesa y la precisión con la que llegó el Bife de Chorizo en término tres cuartos fue impecable. La calidez del servicio se nota desde que entras.',
    tag: 'Parrilla & Cortes',
    date: 'Hace 2 días',
  },
  {
    id: 2,
    name: 'Mariana Salvatierra',
    role: 'Cena de Aniversario',
    avatar: 'MS',
    avatarBg: 'from-red-600 to-orange-500',
    rating: 5,
    comment: 'Celebramos nuestro aniversario en la Terraza Jardín. La picaña a la leña estaba jugosa, el vino recomendado fue un acierto total y los postres superaron toda expectativa.',
    tag: 'Terraza & Maridaje',
    date: 'Hace 4 días',
  },
  {
    id: 3,
    name: 'Diego Arancibia',
    role: 'Pedido a Domicilio',
    avatar: 'DA',
    avatarBg: 'from-amber-500 to-yellow-400',
    rating: 5,
    comment: 'Pedí una parrillada familiar para el domingo y llegó súper caliente y en su punto exacto. Me encantó el código PIN de seguridad al recibir el pedido en casa.',
    tag: 'Delivery Express',
    date: 'Hace 1 semana',
  },
  {
    id: 4,
    name: 'Ing. Roberto Céspedes',
    role: 'Cliente Corporativo VIP',
    avatar: 'RC',
    avatarBg: 'from-emerald-500 to-teal-500',
    rating: 5,
    comment: 'Organizamos una cena empresarial para 18 personas en el Área VIP. Todo estuvo preparado a nuestra llegada, los cortes salieron a tiempo y la atención fue de primera.',
    tag: 'Área VIP & Eventos',
    date: 'Hace 1 semana',
  },
  {
    id: 5,
    name: 'Valeria Hurtado',
    role: 'Almuerzo Familiar',
    avatar: 'VH',
    avatarBg: 'from-purple-500 to-pink-500',
    rating: 5,
    comment: 'Ambiente acogedor, el sabor ahumado a la leña es auténtico y todo llega a la mesa recién salido de la parrilla. Nuestro lugar favorito de fines de semana en Santa Cruz.',
    tag: 'Ambiente Familiar',
    date: 'Hace 2 semanas',
  },
  {
    id: 6,
    name: 'Andrea Toledo',
    role: 'Comensal Gourmet',
    avatar: 'AT',
    avatarBg: 'from-blue-500 to-indigo-500',
    rating: 5,
    comment: 'Poder consultar el menú con fotos reales de los cortes y precios actualizados antes de reservar o pedir desde casa es una comodidad excelente.',
    tag: 'Carta Digital',
    date: 'Hace 3 semanas',
  },
]);

function openTestimonialModal() {
  newTestimonial.value = {
    name: '',
    role: 'Comensal Frecuente',
    rating: 5,
    comment: '',
  };
  testimonialModalOpen.value = true;
}

function submitTestimonial() {
  if (!newTestimonial.value.name.trim() || !newTestimonial.value.comment.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Campos requeridos',
      detail: 'Por favor ingresa tu nombre y tu comentario.',
      life: 3000,
    });
    return;
  }

  const initials = newTestimonial.value.name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(w => w[0].toUpperCase())
    .join('') || 'CL';

  testimonials.value.unshift({
    id: Date.now(),
    name: newTestimonial.value.name,
    role: newTestimonial.value.role,
    avatar: initials,
    avatarBg: 'from-orange-600 to-amber-500',
    rating: newTestimonial.value.rating,
    comment: newTestimonial.value.comment,
    tag: 'Opinión Verificada',
    date: 'Reciente',
  });

  testimonialModalOpen.value = false;
  toast.add({
    severity: 'success',
    summary: '¡Testimonio Publicado!',
    detail: 'Muchas gracias por compartir tu experiencia con nosotros en Las Brazas.',
    life: 4000,
  });
}
</script>

<template>
  <div class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-100 font-sans selection:bg-orange-500 selection:text-white transition-colors duration-200">
    <Toast />

    <!-- Navbar Exclusivo del Restaurante -->
    <header class="sticky top-0 z-40 bg-white/95 dark:bg-slate-950/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800/80 transition-colors duration-200">
      <div class="max-w-7xl mx-auto px-3 sm:px-6 h-16 sm:h-20 flex items-center justify-between gap-2 xl:gap-4">
        <!-- Logo -->
        <Link href="/" class="flex items-center gap-2 sm:gap-2.5 group shrink-0">
          <img
            :src="isDark ? '/images/logo-dark.jpg' : '/images/logo-light.jpg'"
            alt="Las Brazas Steakhouse"
            class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl object-cover shadow-sm border border-slate-200 dark:border-slate-800 group-hover:scale-105 transition-transform"
          />
          <div class="leading-tight">
            <span class="font-extrabold text-base sm:text-xl tracking-wide bg-gradient-to-r from-orange-600 to-amber-500 dark:from-orange-400 dark:via-amber-300 dark:to-yellow-200 bg-clip-text text-transparent block">
              Las Brazas
            </span>
            <p class="hidden sm:block text-[9px] tracking-widest text-slate-500 dark:text-slate-400 uppercase font-semibold">Steakhouse & Grill</p>
          </div>
        </Link>

        <!-- Navigation Links (Desktop XL+) -->
        <nav class="hidden xl:flex items-center gap-5 2xl:gap-7 text-xs font-medium text-slate-600 dark:text-slate-300 min-w-0">
          <a href="#sobre-nosotros" class="whitespace-nowrap hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Sobre Nosotros</a>
          <a href="#menu" class="whitespace-nowrap hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Menú</a>
          <a href="#experiencia" class="whitespace-nowrap hover:text-orange-600 dark:hover:text-orange-400 transition-colors">La Experiencia</a>
          <a href="#testimonios" class="whitespace-nowrap hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Testimonios</a>
          <a href="#contacto" class="whitespace-nowrap hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Contáctenos</a>
        </nav>

        <!-- Action Buttons -->
        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
          <!-- Indicador Online / Offline PWA -->
          <div class="hidden sm:flex">
            <NetworkStatusBadge />
          </div>

          <!-- Theme Switcher -->
          <button
            type="button"
            @click="toggleTheme"
            class="p-2 rounded-xl border transition-all duration-200 flex items-center justify-center text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border-slate-300 dark:text-slate-300 dark:hover:text-amber-400 dark:bg-slate-800/90 dark:hover:bg-slate-800 dark:border-slate-700 shadow-sm cursor-pointer shrink-0"
            :title="isDark ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
            aria-label="Alternar tema"
          >
            <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
            <Moon v-else class="w-4 h-4 text-slate-700" />
          </button>

          <Button
            label="Reservar"
            icon="pi pi-calendar"
            severity="warn"
            class="hidden sm:inline-flex !px-3 !py-2 !bg-gradient-to-r !from-orange-600 !to-amber-500 !border-none font-semibold !text-white shadow-md shadow-orange-600/20 hover:shadow-orange-600/40 hover:scale-[1.02] transition-all !text-xs whitespace-nowrap"
            @click="openReservationModal()"
          />
          <template v-if="$page.props.auth?.user">
            <Link href="/dashboard" class="shrink-0">
              <Button
                icon="pi pi-th-large"
                label="Mi Portal"
                severity="secondary"
                outlined
                class="!px-2.5 sm:!px-3.5 !py-1.5 sm:!py-2 !text-slate-700 dark:!text-slate-200 !border-slate-300 dark:!border-slate-700 hover:!bg-slate-100 dark:hover:!bg-slate-800 !text-[11px] sm:!text-xs font-semibold whitespace-nowrap"
              />
            </Link>
          </template>
          <template v-else>
            <Link href="/register" class="hidden md:inline-block shrink-0">
              <Button
                icon="pi pi-user-plus"
                label="Registrarse"
                severity="warn"
                outlined
                class="!px-3 !py-2 !text-orange-600 dark:!text-orange-400 !border-orange-500/40 hover:!bg-orange-500/10 !text-xs whitespace-nowrap"
              />
            </Link>
            <Link href="/login" class="shrink-0">
              <Button
                icon="pi pi-user"
                label="Iniciar Sesión"
                severity="secondary"
                outlined
                class="!px-2.5 sm:!px-3 !py-1.5 sm:!py-2 !text-slate-700 dark:!text-slate-300 !border-slate-300 dark:!border-slate-700 hover:!bg-slate-100 dark:hover:!bg-slate-800 !text-[11px] sm:!text-xs whitespace-nowrap"
              />
            </Link>
          </template>

          <!-- Botón menú hamburguesa (< XL) -->
          <button
            type="button"
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="xl:hidden p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer shrink-0"
            aria-label="Abrir menú de navegación"
          >
            <X v-if="mobileMenuOpen" class="w-4 h-4" />
            <Menu v-else class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Menú desplegable responsivo (< XL) -->
      <div
        v-if="mobileMenuOpen"
        class="xl:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 px-6 py-4 space-y-3 shadow-lg"
      >
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
          <a href="#sobre-nosotros" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-900">Sobre Nosotros</a>
          <a href="#menu" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-900">Menú</a>
          <a href="#experiencia" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-900">La Experiencia</a>
          <a href="#testimonios" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-900">Testimonios</a>
          <a href="#contacto" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-900">Contáctenos</a>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200 dark:border-slate-800 sm:hidden">
          <NetworkStatusBadge />
          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="mobileMenuOpen = false; openReservationModal()"
              class="text-xs font-bold text-orange-600 dark:text-orange-400"
            >
              Reservar Mesa
            </button>
            <Link v-if="!$page.props.auth?.user" href="/register" class="text-xs font-bold text-slate-700 dark:text-slate-200 underline">
              Crear Cuenta →
            </Link>
          </div>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative min-h-[85vh] flex items-center justify-center overflow-hidden py-20 px-6">
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
              class="!px-8 !py-3.5 !bg-gradient-to-r !from-orange-600 !to-amber-500 hover:!from-orange-500 hover:!to-amber-400 !text-white font-bold !rounded-2xl shadow-xl shadow-orange-600/30 !border-none transition-all"
            />
          </a>
          <Button
            label="Apartar tu Mesa Ahora"
            icon="pi pi-calendar-plus"
            class="!px-8 !py-3.5 !bg-slate-900/90 hover:!bg-slate-800 !text-slate-100 font-bold !rounded-2xl !border !border-slate-700 backdrop-blur transition-all"
            @click="openReservationModal()"
          />
        </div>
      </div>
    </section>

    <!-- Sección Sobre Nosotros (100% Gastronómica) -->
    <section id="sobre-nosotros" class="relative max-w-7xl mx-auto px-6 py-24">
      <div class="text-center max-w-3xl mx-auto mb-14 space-y-3">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-bold uppercase tracking-widest">
          <Sparkles class="w-3.5 h-3.5" /> Sobre Nosotros • Tradición & Hospitalidad
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white">
          Pasión por las <span class="bg-gradient-to-r from-orange-600 to-amber-500 dark:from-orange-400 dark:to-amber-300 bg-clip-text text-transparent">Brasas</span>
        </h2>
        <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base leading-relaxed">
          Conoce la historia de <strong>Restaurante Las Brazas Steakhouse</strong>, nuestro respeto por el fuego lento y el cariño en cada plato servido.
        </p>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 sm:p-10 shadow-sm hover:shadow-xl transition-all duration-300">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          <div class="lg:col-span-7 space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-wider">
              <Flame class="w-3.5 h-3.5" /> Nuestra Historia en Santa Cruz de la Sierra
            </div>
            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white leading-tight">
              El Arte del Verdadero Asado a la Leña con Atención de Primer Nivel
            </h3>
            <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base leading-relaxed">
              <strong>Restaurante Las Brazas Steakhouse</strong> nació con un propósito claro: honrar la tradición del asado artesanal utilizando cortes seleccionados de exportación, maduración controlada y cocción lenta a base de carbón vegetal y leña seleccionada.
            </p>
            <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm leading-relaxed">
              Cada visita está pensada para disfrutar sin prisas: ya sea una cena romántica en nuestra Terraza Jardín, un almuerzo familiar en el Salón Principal, una reunión especial en el Área VIP o tu corte favorito entregado caliente en la puerta de tu casa.
            </p>
          </div>

          <div class="lg:col-span-5 grid grid-cols-2 gap-4">
            <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 text-center space-y-1">
              <span class="text-2xl sm:text-3xl font-black text-orange-600 dark:text-orange-400">+12 Años</span>
              <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Tradición Parrillera</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">Cortes madurados y recetas de autor</p>
            </div>
            <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 text-center space-y-1">
              <span class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400">4.9 / 5</span>
              <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Calificación Clientes</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">+45,000 comensales atendidos</p>
            </div>
            <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 text-center space-y-1">
              <span class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">100%</span>
              <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Leña & Carbón Selecto</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">Ahumado natural y término exacto</p>
            </div>
            <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 text-center space-y-1">
              <span class="text-2xl sm:text-3xl font-black text-blue-600 dark:text-blue-400">3 Zonas</span>
              <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Ambientes Exclusivos</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">Salón Principal, Terraza y Área VIP</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Menu Marketplace Section -->
    <section id="menu" class="max-w-7xl mx-auto px-6 py-20">
      <div class="text-center max-w-2xl mx-auto mb-12 space-y-3">
        <span class="text-xs font-bold text-orange-600 dark:text-orange-400 uppercase tracking-widest">Menú de la Casa</span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white">Nuestra Selección de Especialidades</h2>
        <p class="text-slate-600 dark:text-slate-400 text-sm">Platillos preparados al momento por nuestros maestros parrilleros</p>
      </div>

      <!-- Category Filter Tabs -->
      <div class="flex flex-wrap items-center justify-center gap-2 mb-12">
        <button
          @click="selectedCategory = 'all'"
          :class="[
            'px-5 py-2.5 rounded-xl font-medium text-sm transition-all cursor-pointer',
            selectedCategory === 'all'
              ? 'bg-orange-600 text-white shadow-md shadow-orange-600/25'
              : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 shadow-xs'
          ]"
        >
          Todos los Platillos
        </button>
        <button
          v-for="cat in categories"
          :key="cat.id"
          @click="selectedCategory = cat.id"
          :class="[
            'px-5 py-2.5 rounded-xl font-medium text-sm transition-all cursor-pointer',
            selectedCategory === cat.id
              ? 'bg-orange-600 text-white shadow-md shadow-orange-600/25'
              : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 shadow-xs'
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
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden hover:border-orange-500/50 transition-all duration-300 group flex flex-col justify-between shadow-sm hover:shadow-xl"
        >
          <div>
            <div class="relative h-56 overflow-hidden bg-slate-100 dark:bg-slate-950">
              <img
                :src="getDishImage(dish)"
                :alt="dish.name"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>
              <Tag
                :value="dish.category?.name || 'Especial'"
                severity="warn"
                class="absolute top-4 left-4 bg-white/95 dark:bg-slate-950/85 backdrop-blur border border-amber-500/30 text-amber-700 dark:text-amber-300 font-semibold"
              />
            </div>

            <div class="p-6 space-y-3">
              <div class="flex items-start justify-between gap-3">
                <h3 class="font-bold text-lg text-slate-900 dark:text-slate-100 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">
                  {{ dish.name }}
                </h3>
                <span class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 shrink-0">
                  ${{ Number(dish.price).toFixed(2) }}
                </span>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-3">
                {{ dish.description || 'Delicioso platillo preparado con los más altos estándares de calidad.' }}
              </p>
            </div>
          </div>

          <div class="p-6 pt-3 flex items-center justify-between border-t border-slate-100 dark:border-slate-800/60 mt-2">
            <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 text-xs font-medium">
              <Clock class="w-4 h-4 text-orange-500 dark:text-orange-400" />
              <span>{{ dish.prep_time_minutes }} min</span>
            </div>

            <Button
              label="Pedir Mesa / Reservar"
              icon="pi pi-check"
              class="p-button-sm !bg-orange-500/10 !text-orange-600 dark:!text-orange-400 !border !border-orange-500/30 hover:!bg-orange-600 hover:!text-white"
              @click="openReservationModal(dish.name)"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- La Experiencia Las Brazas -->
    <section id="experiencia" class="bg-slate-100/80 dark:bg-slate-900/50 border-y border-slate-200 dark:border-slate-800/80 py-20 px-6 transition-colors duration-200">
      <div class="max-w-7xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
          <span class="text-xs font-bold text-orange-600 dark:text-orange-400 uppercase tracking-widest">¿Por qué Las Brazas?</span>
          <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white">Excelencia en Cada Detalle</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 rounded-3xl space-y-4 shadow-sm">
            <div class="w-12 h-12 bg-orange-500/10 text-orange-600 dark:text-orange-400 rounded-2xl flex items-center justify-center border border-orange-500/20">
              <Flame class="w-6 h-6" />
            </div>
            <h3 class="font-bold text-lg text-slate-900 dark:text-slate-100">Parrilla & Leña Seleccionada</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
              Cocinamos con carbón vegetal y leña de mezquite para impartir ese ahumado inconfundible a cada uno de nuestros cortes.
            </p>
          </div>

          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 rounded-3xl space-y-4 shadow-sm">
            <div class="w-12 h-12 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center border border-emerald-500/20">
              <CalendarCheck class="w-6 h-6" />
            </div>
            <h3 class="font-bold text-lg text-slate-900 dark:text-slate-100">Reservas y Pedidos Sin Espera</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
              Elige tu horario y ambiente favorito (Salón Principal, Terraza Jardín o Salón VIP) o haz tu pedido a domicilio desde tu cuenta de comensal.
            </p>
          </div>

          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-8 rounded-3xl space-y-4 shadow-sm">
            <div class="w-12 h-12 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center border border-blue-500/20">
              <ShieldCheck class="w-6 h-6" />
            </div>
            <h3 class="font-bold text-lg text-slate-900 dark:text-slate-100">Calidad & Frescura Garantizada</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
              Seleccionamos diariamente carnes de primera calidad, vegetales frescos y guarniciones hechas al instante para cuidar cada detalle en tu mesa.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Muro de Comentarios y Testimonios de Comensales -->
    <section id="testimonios" class="max-w-7xl mx-auto px-6 py-24">
      <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
        <div class="space-y-3 max-w-2xl">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-widest">
            <MessageSquare class="w-3.5 h-3.5" /> Voces de Nuestros Comensales
          </div>
          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white">Experiencias en Las Brazas</h2>
          <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base leading-relaxed">
            Lo que opinan las familias, parejas y grupos que disfrutan nuestra parrilla día a día.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 shrink-0">
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-4 py-2.5 rounded-2xl flex items-center gap-2.5 text-xs text-slate-600 dark:text-slate-300 shadow-xs">
            <div class="flex text-amber-400">
              <Star class="w-4 h-4 fill-amber-400" />
              <Star class="w-4 h-4 fill-amber-400" />
              <Star class="w-4 h-4 fill-amber-400" />
              <Star class="w-4 h-4 fill-amber-400" />
              <Star class="w-4 h-4 fill-amber-400" />
            </div>
            <span class="font-bold text-slate-900 dark:text-white">4.9 / 5</span>
            <span class="text-slate-500">| +240 reseñas</span>
          </div>

          <Button
            label="Dejar mi Testimonio"
            icon="pi pi-pencil"
            class="!bg-gradient-to-r !from-orange-600 !to-amber-500 !border-none font-semibold !text-white shadow-lg shadow-orange-600/20 hover:scale-[1.02] transition-all text-xs"
            @click="openTestimonialModal()"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
        <div
          v-for="test in testimonials"
          :key="test.id"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-orange-500/40 p-7 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-xl flex flex-col justify-between group"
        >
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div :class="['w-11 h-11 rounded-2xl bg-gradient-to-tr text-white flex items-center justify-center font-extrabold text-sm shadow-md', test.avatarBg]">
                  {{ test.avatar }}
                </div>
                <div>
                  <div class="flex items-center gap-1.5">
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">{{ test.name }}</h3>
                    <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400" />
                  </div>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ test.role }}</p>
                </div>
              </div>

              <div class="flex text-amber-400 shrink-0">
                <Star
                  v-for="i in 5"
                  :key="i"
                  :class="['w-3.5 h-3.5', i <= test.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300 dark:text-slate-700']"
                />
              </div>
            </div>

            <p class="text-slate-600 dark:text-slate-300 text-xs sm:text-sm leading-relaxed italic">
              "{{ test.comment }}"
            </p>
          </div>

          <div class="flex items-center justify-between pt-4 mt-4 border-t border-slate-100 dark:border-slate-800/60 text-[11px] text-slate-500 dark:text-slate-400">
            <span class="px-2.5 py-0.5 rounded-full bg-orange-500/10 dark:bg-slate-800/80 text-orange-700 dark:text-orange-300 border border-orange-500/20 dark:border-slate-700/60 font-medium">
              {{ test.tag }}
            </span>
            <span class="text-slate-400 dark:text-slate-500">{{ test.date }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Sección Contáctenos (Envío Directo a Gmail) -->
    <section id="contacto" class="bg-slate-100/80 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-800/80 py-24 px-6">
      <div class="max-w-7xl mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-widest">
            <Mail class="w-3.5 h-3.5" /> Atención Directa al Comensal
          </div>
          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white">
            Contáctenos
          </h2>
          <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base leading-relaxed">
            ¿Tienes alguna consulta sobre nuestra carta, deseas cotizar una cena grupal, celebrar un evento especial o dejarnos una sugerencia? Escríbenos y tu mensaje llegará directamente a nuestro correo oficial.
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
          <!-- Información de Ubicación, Teléfonos y Horarios -->
          <div class="lg:col-span-5 space-y-5">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-7 space-y-6 shadow-sm">
              <div>
                <span class="text-xs font-bold uppercase tracking-widest text-orange-600 dark:text-orange-400">Canales Oficiales</span>
                <h3 class="text-xl font-black text-slate-900 dark:text-white mt-1">Estamos para Atenderte</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                  Respondemos todas las solicitudes recibidas por correo electrónico o teléfono durante nuestro horario de atención continua.
                </p>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                  <div class="w-10 h-10 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0 border border-orange-500/20">
                    <MapPin class="w-5 h-5" />
                  </div>
                  <div class="text-xs space-y-0.5">
                    <p class="font-bold text-slate-900 dark:text-white">Dirección del Restaurante</p>
                    <p class="text-slate-600 dark:text-slate-400">Av. Las Brazas #120, Zona Equipetrol Norte</p>
                    <p class="text-slate-500 dark:text-slate-500">Santa Cruz de la Sierra, Bolivia</p>
                  </div>
                </div>

                <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                  <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/20">
                    <Phone class="w-5 h-5" />
                  </div>
                  <div class="text-xs space-y-0.5">
                    <p class="font-bold text-slate-900 dark:text-white">Reservas & Atención Telefónica</p>
                    <p class="text-slate-600 dark:text-slate-400">Teléfono Fijo: +591 (3) 345-6789</p>
                    <p class="text-slate-600 dark:text-slate-400">WhatsApp Atención: +591 700-45678</p>
                  </div>
                </div>

                <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800">
                  <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-500/20">
                    <Clock class="w-5 h-5" />
                  </div>
                  <div class="text-xs space-y-0.5">
                    <p class="font-bold text-slate-900 dark:text-white">Horarios de Atención</p>
                    <p class="text-slate-600 dark:text-slate-400">Lunes a Jueves: 1:00 PM – 10:00 PM</p>
                    <p class="text-slate-600 dark:text-slate-400">Viernes y Sábado: 1:00 PM – 11:30 PM</p>
                    <p class="text-slate-600 dark:text-slate-400">Domingo Familiar: 1:00 PM – 9:00 PM</p>
                  </div>
                </div>
              </div>

              <div class="p-4 rounded-2xl bg-orange-500/10 border border-orange-500/20 flex items-center gap-3 text-xs text-slate-700 dark:text-slate-300">
                <ShieldCheck class="w-5 h-5 text-orange-600 dark:text-orange-400 shrink-0" />
                <span>
                  Recepción directa en nuestra bandeja oficial de <strong>Gmail</strong> con respuesta directa a tu correo electrónico.
                </span>
              </div>
            </div>
          </div>

          <!-- Formulario Contáctenos -->
          <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 sm:p-10 shadow-lg">
            <div class="mb-6">
              <h3 class="text-2xl font-black text-slate-900 dark:text-white">Envíanos un Mensaje</h3>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                Completa el formulario y nuestro equipo te responderá directamente a tu correo electrónico o teléfono.
              </p>
            </div>

            <div
              v-if="contactSentSuccess"
              class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-start gap-3 text-xs text-emerald-800 dark:text-emerald-200"
            >
              <CheckCircle2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
              <div>
                <p class="font-bold text-sm">¡Hemos recibido tu mensaje correctamente!</p>
                <p class="mt-0.5 text-emerald-700 dark:text-emerald-300">
                  Tu consulta ya fue entregada en nuestro correo electrónico. Nuestro equipo de atención al cliente te responderá a la brevedad.
                </p>
              </div>
            </div>

            <form @submit.prevent="submitContactForm" class="space-y-5">
              <input v-model="contactForm.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Nombre Completo *</label>
                  <InputText v-model="contactForm.name" class="w-full" placeholder="Ej: Carlos Mendoza" :invalid="!!contactForm.errors.name" required />
                  <p v-if="contactForm.errors.name" class="text-red-500 text-[11px] mt-1">{{ contactForm.errors.name }}</p>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Correo Electrónico *</label>
                  <InputText v-model="contactForm.email" type="email" class="w-full" placeholder="tucorreo@gmail.com" :invalid="!!contactForm.errors.email" required />
                  <p v-if="contactForm.errors.email" class="text-red-500 text-[11px] mt-1">{{ contactForm.errors.email }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Teléfono / WhatsApp *</label>
                  <InputText v-model="contactForm.phone" class="w-full" placeholder="Ej: +591 700-12345" :invalid="!!contactForm.errors.phone" required />
                  <p v-if="contactForm.errors.phone" class="text-red-500 text-[11px] mt-1">{{ contactForm.errors.phone }}</p>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Motivo de Contacto *</label>
                  <Select v-model="contactForm.subject" :options="contactSubjectOptions" placeholder="Selecciona un motivo" class="w-full" />
                  <p v-if="contactForm.errors.subject" class="text-red-500 text-[11px] mt-1">{{ contactForm.errors.subject }}</p>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">Tu Mensaje o Consulta *</label>
                  <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ contactForm.message.length }} / 2000</span>
                </div>
                <Textarea
                  v-model="contactForm.message"
                  rows="4"
                  maxlength="2000"
                  class="w-full"
                  placeholder="Escribe aquí tu consulta, detalles de tu evento o comentario..."
                  :invalid="!!contactForm.errors.message"
                  required
                />
                <p v-if="contactForm.errors.message" class="text-red-500 text-[11px] mt-1">{{ contactForm.errors.message }}</p>
              </div>

              <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                <span class="text-[11px] text-slate-500 dark:text-slate-400">
                  Tus datos están protegidos conforme a nuestra <Link href="/privacy" class="text-orange-600 dark:text-orange-400 underline">Política de Privacidad</Link> y <Link href="/terms" class="text-orange-600 dark:text-orange-400 underline">Términos</Link>.
                </span>

                <Button
                  type="submit"
                  label="Enviar Mensaje Ahora"
                  icon="pi pi-send"
                  :loading="contactForm.processing"
                  class="!bg-gradient-to-r !from-orange-600 !to-amber-500 !border-none !text-white font-bold rounded-xl px-7 py-3 text-xs shadow-lg shadow-orange-600/25 hover:scale-[1.01] transition-all w-full sm:w-auto"
                />
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer Limpio del Restaurante -->
    <footer class="bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800/80 py-16 px-6 transition-colors duration-200">
      <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-10">
        <div class="space-y-4">
          <div class="flex items-center gap-3">
            <img
              :src="isDark ? '/images/logo-dark.jpg' : '/images/logo-light.jpg'"
              alt="Las Brazas Steakhouse"
              class="w-12 h-12 rounded-xl object-cover border border-slate-200 dark:border-slate-800"
            />
            <span class="font-extrabold text-xl text-slate-900 dark:text-white">Las Brazas</span>
          </div>
          <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
            Cortes madurados, carbón vegetal y leña seleccionada en el corazón de Santa Cruz de la Sierra.
          </p>
          <div class="pt-1 text-xs text-amber-600 dark:text-amber-400 font-semibold italic">
            "El auténtico sabor a la leña en cada corte."
          </div>
        </div>

        <div class="space-y-3">
          <h4 class="font-bold text-sm text-slate-900 dark:text-slate-200">Horario de Atención</h4>
          <div class="text-xs text-slate-600 dark:text-slate-400 space-y-1">
            <p>Lunes a Jueves: 1:00 PM - 10:00 PM</p>
            <p>Viernes y Sábado: 1:00 PM - 11:30 PM</p>
            <p>Domingo: 1:00 PM - 9:00 PM</p>
          </div>
        </div>

        <div class="space-y-3">
          <h4 class="font-bold text-sm text-slate-900 dark:text-slate-200">Contacto & Ubicación</h4>
          <div class="text-xs text-slate-600 dark:text-slate-400 space-y-2">
            <p class="flex items-center gap-2"><MapPin class="w-4 h-4 text-orange-500 dark:text-orange-400" /> Av. Las Brazas #120, Santa Cruz de la Sierra</p>
            <p class="flex items-center gap-2"><Phone class="w-4 h-4 text-orange-500 dark:text-orange-400" /> +591 (3) 345-6789</p>
          </div>
        </div>

        <div class="space-y-3">
          <h4 class="font-bold text-sm text-slate-900 dark:text-slate-200">Portal del Comensal</h4>
          <div class="flex flex-col gap-2 text-xs text-slate-600 dark:text-slate-400">
            <Link href="/register" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
              → Crear Cuenta de Comensal
            </Link>
            <Link href="/login" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
              → Iniciar Sesión (Clientes)
            </Link>
            <Link href="/terms" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
              → Términos y Condiciones
            </Link>
            <Link href="/privacy" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
              → Política de Privacidad
            </Link>
            <Link href="/manual" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
              → Centro de Ayuda / Manual
            </Link>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto pt-10 mt-10 border-t border-slate-200 dark:border-slate-900 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
        <span>© 2026 Restaurante Las Brazas Steakhouse. Todos los derechos reservados.</span>
        <div class="flex flex-wrap items-center gap-5">
          <Link href="/terms" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors underline">
            Términos
          </Link>
          <Link href="/privacy" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors underline">
            Privacidad
          </Link>
          <Link href="/empresa" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
            Sitio Empresarial BrazasTech
          </Link>
          <Link href="/staff/login" class="hover:text-slate-800 dark:hover:text-slate-200 transition-colors font-semibold">
            Acceso Personal
          </Link>
        </div>
      </div>
    </footer>

    <!-- Public Reservation Modal -->
    <Dialog v-model:visible="reserveModalOpen" header="Reservar una Mesa en Las Brazas" modal class="w-full max-w-lg">
      <form @submit.prevent="submitReservation" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tu Nombre Completo</label>
          <InputText v-model="form.customer_name" class="w-full" placeholder="Ej: Carlos Mendoza" required />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Teléfono Móvil</label>
            <InputText v-model="form.customer_phone" class="w-full" placeholder="555-0000" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Email (opcional)</label>
            <InputText type="email" v-model="form.customer_email" class="w-full" placeholder="cliente@mail.com" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha y Hora</label>
            <DatePicker v-model="form.reservation_date" showTime hourFormat="12" class="w-full" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nº Comensales</label>
            <InputNumber v-model="form.guest_count" :min="1" :max="20" class="w-full" required />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Preferencia de Mesa</label>
          <Select v-model="form.table_id" :options="tables" optionLabel="table_number" optionValue="id" placeholder="Cualquier mesa disponible..." class="w-full" showClear />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Petición Especial / Comentarios</label>
          <Textarea v-model="form.special_requests" rows="2" class="w-full" placeholder="Ej: Cumpleaños, alergias, silla de bebé..." />
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" label="Cancelar" severity="secondary" text @click="reserveModalOpen = false" />
          <Button type="submit" label="Confirmar Solicitud de Reserva" severity="warn" class="!bg-orange-600 !border-none !text-white" :loading="form.processing" />
        </div>
      </form>
    </Dialog>

    <!-- Public Testimonial Modal -->
    <Dialog v-model:visible="testimonialModalOpen" header="Dejar mi Opinión / Testimonio" modal class="w-full max-w-md">
      <form @submit.prevent="submitTestimonial" class="space-y-4 pt-2">
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tu Nombre Completo</label>
          <InputText v-model="newTestimonial.name" class="w-full" placeholder="Ej: Marcela Justiniano" required />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tipo de Visita</label>
            <InputText v-model="newTestimonial.role" class="w-full" placeholder="Ej: Cena Familiar" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Calificación</label>
            <div class="flex items-center gap-1.5 pt-2">
              <button
                type="button"
                v-for="star in 5"
                :key="star"
                @click="newTestimonial.rating = star"
                class="focus:outline-none transition-transform hover:scale-125"
              >
                <Star
                  :class="[
                    'w-5 h-5',
                    star <= newTestimonial.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300 dark:text-slate-600'
                  ]"
                />
              </button>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tu Comentario o Reseña</label>
          <Textarea
            v-model="newTestimonial.comment"
            rows="3"
            class="w-full"
            placeholder="Cuéntanos cómo fue tu experiencia con los cortes y la atención..."
            required
          />
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button type="button" label="Cancelar" severity="secondary" text @click="testimonialModalOpen = false" />
          <Button type="submit" label="Publicar Testimonio" severity="warn" class="!bg-orange-600 !border-none font-semibold !text-white" />
        </div>
      </form>
    </Dialog>
  </div>
</template>
