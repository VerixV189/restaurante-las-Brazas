<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { useTheme } from '../composables/useTheme';
import NetworkStatusBadge from '../components/NetworkStatusBadge.vue';
import Toast from 'primevue/toast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import {
  Building2,
  Target,
  Eye,
  Award,
  Cpu,
  Smartphone,
  CookingPot,
  Layers,
  Bot,
  ShieldCheck,
  CheckCircle2,
  Sparkles,
  BookOpen,
  ArrowRight,
  Sun,
  Moon,
  Menu,
  X
} from 'lucide-vue-next';

const { isDark, initTheme, toggleTheme } = useTheme();
onMounted(() => {
  initTheme();
});

const toast = useToast();
const billingCycle = ref('monthly');
const saasModalOpen = ref(false);
const mobileMenuOpen = ref(false);
const quoteSentSuccess = ref(false);

const saasPlans = [
  {
    id: 'starter',
    name: 'Plan Parrilla Starter',
    badge: 'Arranque Cloud',
    monthlyPrice: 49,
    annualPrice: 39,
    description: 'Ideal para restaurantes y asadores de 1 sucursal que inician su digitalización operativa en la nube.',
    infra: 'Nube Administrada + Backups Diarios',
    popular: false,
    features: [
      'Terminal Punto de Venta (POS) Web táctil y arqueo de caja',
      'Catálogo digital, categorías y gestión de hasta 15 mesas',
      'Control de inventario de insumos y alertas de stock mínimo',
      'Emisión de tickets PDF y facturación computarizada (SIAT)',
      'Hospedaje en la nube con certificado SSL y backups diarios',
    ],
  },
  {
    id: 'pro',
    name: 'Plan Steakhouse Pro',
    badge: 'Más Popular • Activo en Las Brazas',
    monthlyPrice: 99,
    annualPrice: 79,
    description: 'Ecosistema omnicanal completo con App Móvil para comensales, Monitor KDS de Cocina e Inteligencia Artificial.',
    infra: 'Servidor Cloud Dedicado + Alta Disponibilidad',
    popular: true,
    features: [
      'Todo lo del Plan Starter + Mesas y Zonas ilimitadas',
      'Monitor de Cocina en Tiempo Real (KDS) para parrilleros',
      'App Móvil (Android & iOS) con Delivery, PIN de 4 dígitos y Cupones',
      'Descuento automático por Receta Técnica y control anti-duplicado',
      'Reservas digitales por aforo horario (±2h) y tolerancia 15 min',
      'Asistente Virtual con IA (Chef Virtual) para comensales y gerencia',
    ],
  },
  {
    id: 'enterprise',
    name: 'Plan Enterprise Multi-Sede',
    badge: 'Cadenas & Franquicias',
    monthlyPrice: 189,
    annualPrice: 149,
    description: 'Infraestructura dedicada de alta disponibilidad para cadenas gastronómicas con múltiples sucursales.',
    infra: 'Nube Dedicada + Balanceador de Carga',
    popular: false,
    features: [
      'Todo lo del Plan Pro + Múltiples sucursales y almacenes',
      'Gestor Inteligente de Planos y Distribución de Salón con IA',
      'Predicción inteligente de demanda y lotes de compra (PDF/Excel)',
      'Auditoría completa de actividades y permisos para 6 perfiles operativos',
      'Acuerdo de Nivel de Servicio (SLA 99.9%) y soporte técnico 24/7',
    ],
  },
];

const quoteForm = useForm({
  restaurant_name: '',
  contact_name: '',
  contact_email: '',
  contact_phone: '',
  selected_plan: 'Plan Steakhouse Pro ($99 USD/mes - Mensual)',
  cloud_provider: 'Servidor Cloud Dedicado + Alta Disponibilidad',
  branches_count: 1,
  message: '',
  website: '',
});

function openSaasModal(plan = null) {
  if (plan) {
    const price = billingCycle.value === 'monthly' ? plan.monthlyPrice : plan.annualPrice;
    quoteForm.selected_plan = `${plan.name} ($${price} USD/mes - ${billingCycle.value === 'monthly' ? 'Mensual' : 'Anual'})`;
    quoteForm.cloud_provider = plan.infra;
  }
  saasModalOpen.value = true;
}

function submitSaasQuote() {
  quoteSentSuccess.value = false;
  quoteForm.post('/empresa/cotizacion', {
    preserveScroll: true,
    onSuccess: () => {
      saasModalOpen.value = false;
      quoteSentSuccess.value = true;
      quoteForm.reset('restaurant_name', 'contact_name', 'contact_email', 'contact_phone', 'message', 'website');
      quoteForm.branches_count = 1;
      toast.add({
        severity: 'success',
        summary: '¡Cotización SaaS Enviada!',
        detail: 'BrazasTech S.R.L. recibió tu solicitud en nuestro correo oficial y te contactará a la brevedad.',
        life: 5500,
      });
    },
    onError: () => {
      toast.add({
        severity: 'warn',
        summary: 'Revisa los campos',
        detail: 'Por favor completa los datos obligatorios para enviar tu cotización SaaS.',
        life: 4000,
      });
    },
  });
}
</script>

<template>
  <div class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-100 font-sans selection:bg-orange-500 selection:text-white transition-colors duration-200">
    <Toast />

    <!-- Navbar Empresarial -->
    <header class="sticky top-0 z-40 bg-white/95 dark:bg-slate-950/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800/80 transition-colors duration-200">
      <div class="max-w-7xl mx-auto px-3 sm:px-6 h-16 sm:h-20 flex items-center justify-between gap-2 sm:gap-3">
        <!-- Brand -->
        <Link href="/empresa" class="flex items-center gap-2 sm:gap-2.5 group shrink-0">
          <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-slate-900 via-blue-800 to-orange-500 flex items-center justify-center text-white shadow-md group-hover:scale-105 transition-transform">
            <Building2 class="w-5 h-5" />
          </div>
          <div class="leading-tight">
            <span class="font-extrabold text-base sm:text-xl tracking-wide bg-gradient-to-r from-slate-900 via-blue-700 to-orange-600 dark:from-white dark:via-blue-300 dark:to-orange-400 bg-clip-text text-transparent block">
              BrazasTech
            </span>
            <p class="hidden sm:block text-[9px] tracking-widest text-slate-500 dark:text-slate-400 uppercase font-semibold">Soluciones Cloud SaaS</p>
          </div>
        </Link>

        <!-- Nav Links (Desktop XL+) -->
        <nav class="hidden xl:flex items-center gap-5 text-xs font-medium text-slate-600 dark:text-slate-300">
          <a href="#identidad" class="whitespace-nowrap hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Misión & Visión</a>
          <a href="#tecnologia" class="whitespace-nowrap hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Ecosistema</a>
          <a href="#modelo-saas" class="whitespace-nowrap hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Planes SaaS</a>
          <a href="#cotizacion" class="whitespace-nowrap hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Cotización B2B</a>
          <Link href="/manual" class="whitespace-nowrap hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Manual</Link>
        </nav>

        <!-- Right Actions -->
        <div class="flex items-center gap-2 shrink-0">
          <div class="hidden sm:flex">
            <NetworkStatusBadge />
          </div>

          <button
            type="button"
            @click="toggleTheme"
            class="p-2 rounded-xl border transition-all duration-200 flex items-center justify-center text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border-slate-300 dark:text-slate-300 dark:hover:text-amber-400 dark:bg-slate-800/90 dark:hover:bg-slate-800 dark:border-slate-700 shadow-sm cursor-pointer shrink-0"
            :title="isDark ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
          >
            <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
            <Moon v-else class="w-4 h-4 text-slate-700" />
          </button>

          <Link href="/" class="hidden md:inline-block shrink-0">
            <Button
              label="Sitio Restaurante"
              icon="pi pi-home"
              severity="secondary"
              outlined
              class="!px-3 !py-2 !text-slate-700 dark:!text-slate-300 !border-slate-300 dark:!border-slate-700 hover:!bg-slate-100 dark:hover:!bg-slate-800 !text-xs whitespace-nowrap"
            />
          </Link>

          <Link href="/staff/login" class="shrink-0">
            <Button
              label="Acceso Personal"
              icon="pi pi-shield"
              class="!px-3.5 !py-2 !bg-gradient-to-r !from-slate-900 !via-blue-900 !to-slate-900 hover:!from-blue-800 hover:!to-slate-800 !border-none !text-white font-semibold !text-xs whitespace-nowrap shadow-md"
            />
          </Link>

          <button
            type="button"
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="xl:hidden p-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 cursor-pointer shrink-0"
            aria-label="Abrir menú empresarial"
          >
            <X v-if="mobileMenuOpen" class="w-4 h-4" />
            <Menu v-else class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Mobile/Tablet Menu -->
      <div
        v-if="mobileMenuOpen"
        class="xl:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 px-6 py-4 space-y-3 shadow-lg"
      >
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
          <a href="#identidad" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-900">Misión & Visión</a>
          <a href="#tecnologia" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-900">Ecosistema</a>
          <a href="#modelo-saas" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-900">Planes SaaS</a>
          <a href="#cotizacion" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-900">Cotización B2B</a>
          <Link href="/manual" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-900">Manual</Link>
          <Link href="/" @click="mobileMenuOpen = false" class="px-3 py-2 rounded-xl text-orange-600 dark:text-orange-400">Sitio Restaurante</Link>
        </div>
      </div>
    </header>

    <!-- Hero Corporativo B2B -->
    <section class="relative overflow-hidden bg-slate-950 text-white py-24 px-6">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(234,88,12,0.22),transparent_45%),radial-gradient(circle_at_bottom_left,rgba(37,99,235,0.25),transparent_50%)]"></div>
      <div class="relative z-10 max-w-5xl mx-auto text-center space-y-6">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/15 border border-blue-400/30 text-blue-300 text-xs font-bold uppercase tracking-widest">
          <Cpu class="w-4 h-4" /> BrazasTech Soluciones de Software S.R.L. • Sitio Web Empresarial
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight">
          Plataforma Cloud <span class="bg-gradient-to-r from-orange-400 via-amber-400 to-yellow-300 bg-clip-text text-transparent">SaaS Omnicanal</span> para Restaurantes y Parrillas
        </h1>

        <p class="text-base sm:text-lg text-slate-300 max-w-3xl mx-auto font-light leading-relaxed">
          Centraliza tu Punto de Venta POS, Monitor de Cocina KDS, Control Cárnico por Recetas, Delivery con PIN de seguridad, Planos de Salón con IA y App Móvil bajo un modelo empresarial escalable en la nube.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
          <a href="#modelo-saas">
            <Button
              label="Explorar Planes SaaS"
              icon="pi pi-bolt"
              class="!px-7 !py-3.5 !bg-gradient-to-r !from-orange-600 !to-amber-500 hover:!from-orange-500 hover:!to-amber-400 !text-white font-bold !rounded-2xl shadow-xl shadow-orange-600/30 !border-none"
            />
          </a>
          <Link href="/staff/login">
            <Button
              label="Acceso Personal / Panel Operativo"
              icon="pi pi-shield"
              class="!px-7 !py-3.5 !bg-slate-900 hover:!bg-slate-800 !text-slate-100 font-bold !rounded-2xl !border !border-slate-700"
            />
          </Link>
        </div>
      </div>
    </section>

    <!-- Identidad Corporativa: Misión y Visión -->
    <section id="identidad" class="max-w-7xl mx-auto px-6 py-24">
      <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-bold uppercase tracking-widest">
          <Sparkles class="w-3.5 h-3.5" /> BrazasTech S.R.L. • Identidad Corporativa
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white">
          Misión, Visión & Filosofía Empresarial
        </h2>
        <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base leading-relaxed">
          Transformamos la operación gastronómica de alto volumen mediante ingeniería de software segura, trazable y orientada a resultados.
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Misión -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-orange-500/50 p-8 sm:p-10 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-xl group space-y-6">
          <div class="flex items-center justify-between">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-orange-600 to-amber-500 p-0.5 shadow-lg shadow-orange-600/20">
              <div class="w-full h-full bg-white dark:bg-slate-950 rounded-[14px] flex items-center justify-center text-orange-600 dark:text-orange-400">
                <Target class="w-7 h-7" />
              </div>
            </div>
            <span class="px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-semibold uppercase tracking-wider">
              Propósito & Acción
            </span>
          </div>

          <div>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white">Nuestra Misión</h3>
            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 font-semibold mt-1">Hospitalidad & Gestión Gastronómica</p>
          </div>

          <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base leading-relaxed">
            Brindar soluciones digitales integrales (Web, App Móvil e Inteligencia Artificial) que optimizan cada etapa del restaurante con herramientas seguras, ágiles y confiables para el servicio al comensal y el control operativo.
          </p>

          <div class="space-y-2.5 pt-2 border-t border-slate-200 dark:border-slate-800/80 text-xs text-slate-600 dark:text-slate-300">
            <div class="flex items-center gap-2.5"><CheckCircle2 class="w-4 h-4 text-orange-500 shrink-0" /><span>Control de comandas, mesas y pedidos en tiempo real</span></div>
            <div class="flex items-center gap-2.5"><CheckCircle2 class="w-4 h-4 text-orange-500 shrink-0" /><span>Aseguramiento continuo de calidad en servicio y atención</span></div>
            <div class="flex items-center gap-2.5"><CheckCircle2 class="w-4 h-4 text-orange-500 shrink-0" /><span>Trazabilidad absoluta de cobros, facturación e inventario cárnico</span></div>
          </div>
        </div>

        <!-- Visión -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-amber-500/50 p-8 sm:p-10 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-xl group space-y-6">
          <div class="flex items-center justify-between">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-500 to-yellow-400 p-0.5 shadow-lg shadow-amber-500/20">
              <div class="w-full h-full bg-white dark:bg-slate-950 rounded-[14px] flex items-center justify-center text-amber-600 dark:text-amber-400">
                <Eye class="w-7 h-7" />
              </div>
            </div>
            <span class="px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-semibold uppercase tracking-wider">
              Futuro & Liderazgo
            </span>
          </div>

          <div>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white">Nuestra Visión</h3>
            <p class="text-xs uppercase tracking-widest text-slate-500 dark:text-slate-400 font-semibold mt-1">Liderazgo en Tecnología Gastronómica</p>
          </div>

          <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base leading-relaxed">
            Consolidarnos como referentes en soluciones tecnológicas omnicanal (Web y Móvil) para la industria gastronómica en Latinoamérica, reconocidos por nuestra innovación, facilidad de uso y máxima estabilidad operativa en horas pico.
          </p>

          <div class="space-y-2.5 pt-2 border-t border-slate-200 dark:border-slate-800/80 text-xs text-slate-600 dark:text-slate-300">
            <div class="flex items-center gap-2.5"><CheckCircle2 class="w-4 h-4 text-amber-500 shrink-0" /><span>Plataforma omnicanal de alta disponibilidad sin caídas</span></div>
            <div class="flex items-center gap-2.5"><CheckCircle2 class="w-4 h-4 text-amber-500 shrink-0" /><span>Toma de decisiones respaldada por analítica e inteligencia predictiva</span></div>
            <div class="flex items-center gap-2.5"><CheckCircle2 class="w-4 h-4 text-amber-500 shrink-0" /><span>Arquitectura preparada para escalar a múltiples salones y sucursales</span></div>
          </div>
        </div>
      </div>

      <!-- Compromiso de Calidad y Slogan -->
      <div class="bg-gradient-to-r from-orange-50 via-white to-amber-50 dark:from-orange-950/40 dark:via-slate-900 dark:to-amber-950/40 border border-orange-200 dark:border-orange-500/30 p-8 sm:p-10 rounded-3xl shadow-md flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-5">
          <div class="w-16 h-16 rounded-2xl bg-orange-500/10 dark:bg-orange-600/20 border border-orange-500/30 flex items-center justify-center text-orange-600 dark:text-orange-400 shrink-0">
            <Award class="w-8 h-8" />
          </div>
          <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-xs font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">Compromiso de Calidad & Seguridad</span>
              <span class="text-[10px] px-2 py-0.5 rounded bg-orange-500/15 text-orange-700 dark:text-orange-300 font-mono">Disponibilidad 99.9% • SSL/TLS</span>
            </div>
            <h4 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Aseguramiento de Calidad en Cada Proceso</h4>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">
              Operamos con ciclos de mejora continua, controles preventivos de seguridad y monitoreo permanente para ofrecer una atención ágil sin interrupciones.
            </p>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-950/80 border border-orange-200 dark:border-orange-500/30 px-6 py-4 rounded-2xl text-center md:text-right shrink-0 w-full md:w-auto shadow-sm">
          <p class="text-[10px] tracking-widest uppercase text-amber-600 dark:text-amber-400 font-bold">Nuestro Slogan</p>
          <p class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white italic mt-0.5">
            "Excelencia en cada corte, precisión en cada servicio."
          </p>
        </div>
      </div>
    </section>

    <!-- Fortalezas del Ecosistema Digital -->
    <section id="tecnologia" class="bg-slate-100/80 dark:bg-slate-900/40 border-y border-slate-200 dark:border-slate-800/80 py-24 px-6">
      <div class="max-w-7xl mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-3">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-widest">
            <Cpu class="w-3.5 h-3.5" /> Plataforma de Alto Desempeño
          </div>
          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white">Fortalezas del Ecosistema Digital</h2>
          <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base leading-relaxed">
            Diseñado específicamente para eliminar cuellos de botella en horas de máxima demanda y conectar instantáneamente salón, cocina, delivery y gerencia.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-orange-500/50 p-7 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-lg">
            <div class="flex items-center justify-between mb-5">
              <div class="w-12 h-12 bg-orange-500/10 text-orange-600 dark:text-orange-400 rounded-2xl flex items-center justify-center border border-orange-500/20"><Layers class="w-6 h-6" /></div>
              <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold border border-slate-200 dark:border-slate-700">Interfaz Táctil</span>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Punto de Venta (POS) Táctil</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Cobros ultra veloces, arqueos y cortes de caja en vivo, división de cuentas por comensal y emisión de facturas y tickets PDF en segundos.</p>
          </div>

          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-orange-500/50 p-7 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-lg">
            <div class="flex items-center justify-between mb-5">
              <div class="w-12 h-12 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center border border-amber-500/20"><Smartphone class="w-6 h-6" /></div>
              <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold border border-slate-200 dark:border-slate-700">iOS & Android</span>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">App Móvil & Comandero</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Toma de pedidos digital al pie de mesa y pedidos a domicilio desde el teléfono, con términos de cocción, cupones y seguimiento en vivo.</p>
          </div>

          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-orange-500/50 p-7 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-lg">
            <div class="flex items-center justify-between mb-5">
              <div class="w-12 h-12 bg-red-500/10 text-red-600 dark:text-red-400 rounded-2xl flex items-center justify-center border border-red-500/20"><CookingPot class="w-6 h-6" /></div>
              <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold border border-slate-200 dark:border-slate-700">Tiempo Real</span>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Monitor de Cocina (KDS)</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Pantalla interactiva en vivo para jefes de cocina y parrilleros, con cronómetros por mesa, prioridades y términos de cocción sin papeles perdidos.</p>
          </div>

          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-orange-500/50 p-7 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-lg">
            <div class="flex items-center justify-between mb-5">
              <div class="w-12 h-12 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center border border-emerald-500/20"><ShieldCheck class="w-6 h-6" /></div>
              <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold border border-slate-200 dark:border-slate-700">Control Exacto</span>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Control Cárnico y Mermas</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Descuento automático de inventario por porciones y gramajes según recetas técnicas, con trazabilidad de mermas y alertas de stock mínimo.</p>
          </div>

          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-orange-500/50 p-7 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-lg">
            <div class="flex items-center justify-between mb-5">
              <div class="w-12 h-12 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center border border-blue-500/20"><Bot class="w-6 h-6" /></div>
              <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold border border-slate-200 dark:border-slate-700">Chef Virtual IA</span>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Asistente Inteligente (IA)</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Soporte conversacional para comensales y gerencia, detección inteligente de planos de salón y predicción de demanda de insumos.</p>
          </div>

          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-orange-500/50 p-7 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-lg">
            <div class="flex items-center justify-between mb-5">
              <div class="w-12 h-12 bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-2xl flex items-center justify-center border border-purple-500/20"><Cpu class="w-6 h-6" /></div>
              <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold border border-slate-200 dark:border-slate-700">Nube 24/7</span>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Estabilidad & Resiliencia</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Despliegue en la nube con respaldos diarios, sesiones separadas por rol, soporte PWA offline seguro y monitoreo 24/7.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Modelo SaaS & Cotización Comercial B2B -->
    <section id="modelo-saas" class="max-w-7xl mx-auto px-6 py-24 space-y-16">
      <div class="text-center max-w-3xl mx-auto space-y-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-orange-500/10 border border-orange-500/30 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-widest">
          <Cpu class="w-4 h-4" /> BrazasTech Cloud SaaS • Licenciamiento B2B
        </div>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white">
          Modelo <span class="bg-gradient-to-r from-orange-600 to-amber-500 dark:from-orange-400 dark:to-amber-300 bg-clip-text text-transparent">SaaS</span> para Restaurantes & Parrillas
        </h2>
        <p class="text-slate-600 dark:text-slate-400 text-sm sm:text-base leading-relaxed">
          Impulsado por <strong>BrazasTech Soluciones de Software S.R.L.</strong> (registrado ante SEPREC y SENAPI). Digitaliza tu restaurante mediante suscripción mensual o anual todo incluido.
        </p>

        <!-- Toggle Mensual / Anual -->
        <div class="inline-flex items-center p-1.5 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 shadow-sm mt-2">
          <button
            type="button"
            @click="billingCycle = 'monthly'"
            :class="[
              'px-5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer',
              billingCycle === 'monthly'
                ? 'bg-orange-600 text-white shadow-sm'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
            ]"
          >
            Suscripción Mensual
          </button>
          <button
            type="button"
            @click="billingCycle = 'annual'"
            :class="[
              'px-5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2',
              billingCycle === 'annual'
                ? 'bg-orange-600 text-white shadow-sm'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
            ]"
          >
            <span>Suscripción Anual</span>
            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-[10px] font-extrabold">
              -20% Ahorro
            </span>
          </button>
        </div>
      </div>

      <!-- Tarjetas de Planes SaaS -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
        <div
          v-for="plan in saasPlans"
          :key="plan.id"
          :class="[
            'relative rounded-3xl p-8 flex flex-col justify-between transition-all duration-300',
            plan.popular
              ? 'bg-white dark:bg-slate-900 border-2 border-orange-500 shadow-2xl shadow-orange-500/10 lg:-translate-y-2'
              : 'bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-orange-500/40 shadow-sm hover:shadow-xl'
          ]"
        >
          <div class="space-y-6">
            <div class="flex items-center justify-between gap-2">
              <span
                :class="[
                  'px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider border',
                  plan.popular
                    ? 'bg-orange-500 text-white border-orange-500'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'
                ]"
              >
                {{ plan.badge }}
              </span>
              <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">Licencia SaaS</span>
            </div>

            <div>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ plan.name }}</h3>
              <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">{{ plan.description }}</p>
            </div>

            <div class="py-4 border-y border-slate-200 dark:border-slate-800">
              <div class="flex items-baseline gap-1.5">
                <span class="text-4xl font-black text-slate-900 dark:text-white">
                  ${{ billingCycle === 'monthly' ? plan.monthlyPrice : plan.annualPrice }}
                </span>
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">USD / mes</span>
              </div>
              <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1">
                Infraestructura incluida: {{ plan.infra }}
              </p>
            </div>

            <ul class="space-y-3">
              <li
                v-for="(feat, idx) in plan.features"
                :key="idx"
                class="flex items-start gap-2.5 text-xs text-slate-700 dark:text-slate-300"
              >
                <CheckCircle2 class="w-4 h-4 text-orange-500 dark:text-orange-400 shrink-0 mt-0.5" />
                <span>{{ feat }}</span>
              </li>
            </ul>
          </div>

          <div class="pt-8 mt-6">
            <Button
              :label="plan.popular ? 'Contratar / Solicitar Demo Pro' : 'Solicitar este Plan SaaS'"
              icon="pi pi-send"
              :class="[
                'w-full font-bold rounded-xl py-3 text-xs border-none transition-all',
                plan.popular
                  ? '!bg-gradient-to-r !from-orange-600 !to-amber-500 !text-white shadow-lg shadow-orange-600/25 hover:scale-[1.01]'
                  : '!bg-slate-900 dark:!bg-slate-800 !text-white hover:!bg-orange-600'
              ]"
              @click="openSaasModal(plan)"
            />
          </div>
        </div>
      </div>

      <!-- Bloque Directo de Contacto Comercial & Cotización SaaS (conectado a Gmail) -->
      <div id="cotizacion" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 sm:p-10 shadow-lg grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
        <div class="lg:col-span-5 space-y-6">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider">
            <ShieldCheck class="w-4 h-4" /> Respaldo Legal & Tecnológico en Bolivia
          </div>
          <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">
            Contacto Comercial & Cotización de Licencia SaaS
          </h3>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
            ¿Deseas implementar <strong>BrazasTech POS, KDS & Mobile</strong> en tu restaurante o cadena gastronómica? Envía tus datos y recibirás una cotización directa desde nuestro correo oficial.
          </p>

          <div class="space-y-3 pt-2 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-300">
            <div class="flex items-start gap-3">
              <Award class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" />
              <div>
                <strong class="text-slate-900 dark:text-white">Propiedad Intelectual y Facturación SIAT:</strong>
                <p class="text-slate-500 dark:text-slate-400">Software registrado ante el SENAPI por BrazasTech S.R.L. Emisión de factura electrónica en línea (SIN).</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <Cpu class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" />
              <div>
                <strong class="text-slate-900 dark:text-white">Infraestructura Cloud Empresarial:</strong>
                <p class="text-slate-500 dark:text-slate-400">Despliegue en la nube de alta disponibilidad con copias de respaldo diarias, cifrado SSL y monitoreo 24/7.</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <BookOpen class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" />
              <div>
                <strong class="text-slate-900 dark:text-white">Capacitación & Manual Oficial:</strong>
                <p class="text-slate-500 dark:text-slate-400">
                  Accede en línea al
                  <Link href="/manual" class="text-orange-600 dark:text-orange-400 font-semibold underline">Manual Interactivo de Usuario</Link>
                  y descarga el PDF completo.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Formulario Real conectado a POST /empresa/cotizacion -->
        <div class="lg:col-span-7 bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8">
          <div
            v-if="quoteSentSuccess"
            class="mb-5 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-start gap-3 text-xs text-emerald-800 dark:text-emerald-200"
          >
            <CheckCircle2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
            <div>
              <p class="font-bold text-sm">¡Solicitud de Cotización SaaS Enviada!</p>
              <p class="mt-0.5 text-emerald-700 dark:text-emerald-300">
                Tu requerimiento llegó directamente a nuestro correo oficial de ventas. Un especialista comercial se pondrá en contacto contigo.
              </p>
            </div>
          </div>

          <form @submit.prevent="submitSaasQuote" class="space-y-4">
            <!-- Honeypot oculto anti-spam -->
            <input v-model="quoteForm.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Restaurante / Empresa *</label>
                <InputText v-model="quoteForm.restaurant_name" class="w-full" placeholder="Ej: Parrilla El Gaucho S.R.L." :invalid="!!quoteForm.errors.restaurant_name" required />
                <p v-if="quoteForm.errors.restaurant_name" class="text-red-500 text-[11px] mt-1">{{ quoteForm.errors.restaurant_name }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Persona de Contacto / Cargo *</label>
                <InputText v-model="quoteForm.contact_name" class="w-full" placeholder="Ej: Ing. Roberto Céspedes (Gerente)" :invalid="!!quoteForm.errors.contact_name" required />
                <p v-if="quoteForm.errors.contact_name" class="text-red-500 text-[11px] mt-1">{{ quoteForm.errors.contact_name }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Teléfono / WhatsApp *</label>
                <InputText v-model="quoteForm.contact_phone" class="w-full" placeholder="+591 700-12345" :invalid="!!quoteForm.errors.contact_phone" required />
                <p v-if="quoteForm.errors.contact_phone" class="text-red-500 text-[11px] mt-1">{{ quoteForm.errors.contact_phone }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Correo Corporativo *</label>
                <InputText type="email" v-model="quoteForm.contact_email" class="w-full" placeholder="gerencia@restaurante.bo" :invalid="!!quoteForm.errors.contact_email" required />
                <p v-if="quoteForm.errors.contact_email" class="text-red-500 text-[11px] mt-1">{{ quoteForm.errors.contact_email }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Plan de Licencia SaaS *</label>
                <InputText v-model="quoteForm.selected_plan" class="w-full" required />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">N° de Sucursales</label>
                <InputNumber v-model="quoteForm.branches_count" :min="1" :max="100" class="w-full" />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Requerimientos de Despliegue / Comentarios</label>
              <Textarea
                v-model="quoteForm.message"
                rows="3"
                class="w-full"
                placeholder="Indica cantidad de mesas, terminales POS requeridas o integración especial de tu restaurante..."
              />
              <p v-if="quoteForm.errors.message" class="text-red-500 text-[11px] mt-1">{{ quoteForm.errors.message }}</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
              <span class="text-[11px] text-slate-500 dark:text-slate-400">
                Al enviar aceptas nuestros <Link href="/terms" class="text-orange-600 dark:text-orange-400 underline">Términos</Link> y <Link href="/privacy" class="text-orange-600 dark:text-orange-400 underline">Política de Privacidad</Link>.
              </span>
              <Button
                type="submit"
                label="Solicitar Demo & Cotización SaaS"
                icon="pi pi-send"
                :loading="quoteForm.processing"
                class="!bg-gradient-to-r !from-orange-600 !to-amber-500 !border-none !text-white font-bold rounded-xl px-6 py-3 text-xs shadow-lg shadow-orange-600/25 w-full sm:w-auto"
              />
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- Footer Corporativo -->
    <footer class="bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800/80 py-14 px-6">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6 text-xs text-slate-500 dark:text-slate-400">
        <div>
          <p class="font-bold text-slate-900 dark:text-white text-sm">BrazasTech Soluciones de Software S.R.L.</p>
          <p class="mt-1">© 2026 Ecosistema Cloud SaaS para Gastronomía • Santa Cruz de la Sierra, Bolivia.</p>
        </div>
        <div class="flex flex-wrap items-center gap-5 font-medium">
          <Link href="/" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Sitio del Restaurante</Link>
          <Link href="/staff/login" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors font-bold text-slate-800 dark:text-slate-200">Acceso Personal</Link>
          <Link href="/manual" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Manual de Usuario</Link>
          <Link href="/terms" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Términos</Link>
          <Link href="/privacy" class="hover:text-orange-600 dark:hover:text-orange-400 transition-colors">Privacidad</Link>
        </div>
      </div>
    </footer>

    <!-- Modal de Cotización Rápida SaaS -->
    <Dialog v-model:visible="saasModalOpen" header="Solicitar Cotización SaaS — BrazasTech S.R.L." modal class="w-full max-w-lg">
      <form @submit.prevent="submitSaasQuote" class="space-y-4 pt-2">
        <div class="p-3.5 rounded-xl bg-orange-500/10 border border-orange-500/30 text-xs text-slate-700 dark:text-slate-200 flex items-center justify-between gap-2">
          <div>
            <span class="block text-[10px] uppercase font-bold text-orange-600 dark:text-orange-400">Plan Seleccionado</span>
            <strong class="text-sm">{{ quoteForm.selected_plan }}</strong>
          </div>
          <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold">
            {{ quoteForm.cloud_provider }}
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Restaurante / Empresa *</label>
            <InputText v-model="quoteForm.restaurant_name" class="w-full" placeholder="Ej: Asador Santa Cruz S.R.L." required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Persona de Contacto *</label>
            <InputText v-model="quoteForm.contact_name" class="w-full" placeholder="Ej: Carlos Justiniano" required />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Teléfono / WhatsApp *</label>
            <InputText v-model="quoteForm.contact_phone" class="w-full" placeholder="+591 700-12345" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Correo Corporativo *</label>
            <InputText type="email" v-model="quoteForm.contact_email" class="w-full" placeholder="gerencia@restaurante.bo" required />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Detalles de tu Restaurante</label>
          <Textarea
            v-model="quoteForm.message"
            rows="2"
            class="w-full"
            placeholder="Indica número de mesas, sucursales o requerimientos..."
          />
        </div>

        <div class="flex justify-end gap-2 pt-3">
          <Button type="button" label="Cancelar" severity="secondary" text @click="saasModalOpen = false" />
          <Button
            type="submit"
            label="Enviar Solicitud por Correo"
            icon="pi pi-send"
            :loading="quoteForm.processing"
            class="!bg-orange-600 !border-none font-semibold !text-white"
          />
        </div>
      </form>
    </Dialog>
  </div>
</template>
