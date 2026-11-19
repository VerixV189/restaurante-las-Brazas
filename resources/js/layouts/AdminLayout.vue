<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import { useTheme } from '../composables/useTheme';
import { useOfflinePwa } from '../composables/useOfflinePwa';
import AiChatSidebar from '../components/AiChatSidebar.vue';
import NetworkStatusBadge from '../components/NetworkStatusBadge.vue';
import {
  LayoutDashboard,
  UtensilsCrossed,
  Boxes,
  CalendarCheck,
  Users,
  History,
  Menu,
  X,
  Flame,
  LogOut,
  ChevronRight,
  BookOpen,
  MapPin,
  Truck,
  UserCircle,
  ShoppingCart,
  ChefHat,
  BarChart3,
  Sparkles,
  Calculator,
  Sun,
  Moon,
} from 'lucide-vue-next';

const page = usePage();
const isDesktopViewport = () => (typeof window !== 'undefined' ? window.innerWidth >= 1024 : true);
const sidebarOpen = ref(isDesktopViewport());
const aiChatOpen = ref(false);
const { isDark, initTheme, toggleTheme } = useTheme();
const { isOfflineMode, simulatedOffline, toggleSimulatedOffline, purgeOfflineSessionData } = useOfflinePwa();

function closeSidebarOnMobile() {
  if (!isDesktopViewport()) {
    sidebarOpen.value = false;
  }
}

async function handleSecureLogout() {
  await purgeOfflineSessionData('Sesión cerrada: datos offline eliminados por seguridad');
  router.post('/logout');
}

onMounted(() => {
  initTheme();
  sidebarOpen.value = isDesktopViewport();
});

// All navigation items with their allowed roles (empty = all roles)
const allNavItems = [
  {
    name: 'Dashboard',
    href: '/dashboard',
    icon: LayoutDashboard,
    roles: [], // All roles
  },
  {
    name: 'Categorías',
    href: '/categories',
    icon: UtensilsCrossed,
    roles: ['Administrador', 'Chef'],
  },
  {
    name: 'Platillos (Menú)',
    href: '/dishes',
    icon: Flame,
    roles: ['Administrador', 'Chef'],
  },
  {
    name: 'Recetas',
    href: '/recipes',
    icon: BookOpen,
    roles: ['Administrador', 'Chef'],
  },
  {
    name: 'Inventario / Insumos',
    href: '/ingredients',
    icon: Boxes,
    roles: ['Administrador', 'Chef'],
  },
  {
    name: 'Movimientos & Mermas',
    href: '/inventory-movements',
    icon: History,
    roles: ['Administrador', 'Chef'],
  },
  {
    name: 'Predicciones & Lotes',
    href: '/predictions',
    icon: Calculator,
    roles: ['Administrador', 'Chef'],
  },
  {
    name: 'Zonas y Mesas',
    href: '/areas',
    icon: MapPin,
    roles: ['Administrador', 'Recepcionista'],
  },
  {
    name: 'Planos Inteligentes',
    href: '/floor-plans',
    icon: Sparkles,
    roles: ['Administrador', 'Recepcionista'],
  },
  {
    name: 'Reservas & Mesas',
    href: '/reservations',
    icon: CalendarCheck,
    roles: ['Administrador', 'Recepcionista', 'Mesero'],
  },
  {
    name: 'Proveedores',
    href: '/suppliers',
    icon: Truck,
    roles: ['Administrador'],
  },
  {
    name: 'Clientes',
    href: '/customers',
    icon: UserCircle,
    roles: ['Administrador', 'Recepcionista'],
  },
  {
    name: 'Punto de Venta',
    href: '/pos',
    icon: ShoppingCart,
    roles: ['Administrador', 'Mesero'],
  },
  {
    name: 'Pantalla Cocina',
    href: '/kitchen',
    icon: ChefHat,
    roles: ['Administrador', 'Chef'],
  },
  {
    name: 'Central Delivery GPS',
    href: '/delivery',
    icon: Truck,
    roles: ['Administrador', 'Repartidor'],
  },
  {
    name: 'Reportes',
    href: '/reports',
    icon: BarChart3,
    roles: ['Administrador'],
  },
  {
    name: 'Usuarios & Roles',
    href: '/users',
    icon: Users,
    roles: ['Administrador'],
  },
  {
    name: 'Logs de Auditoría',
    href: '/activity-logs',
    icon: History,
    roles: ['Administrador'],
  },
];

// Navigation items exclusive for Cliente role (Marketplace & Client Portal)
const clientNavItems = [
  {
    name: 'Marketplace / Menú',
    href: '/dashboard?tab=menu',
    icon: Flame,
  },
  {
    name: 'Mi Carrito & Envío',
    href: '/dashboard?tab=cart',
    icon: ShoppingCart,
  },
  {
    name: 'Mis Pedidos & Envíos',
    href: '/dashboard?tab=orders',
    icon: Truck,
  },
  {
    name: 'Promos & Puntos',
    href: '/dashboard?tab=promos',
    icon: Sparkles,
  },
  {
    name: 'Mis Reservas',
    href: '/dashboard?tab=reservations',
    icon: CalendarCheck,
  },
  {
    name: 'Agente IA Gastronómico',
    href: '/dashboard?tab=ai',
    icon: Sparkles,
  },
  {
    name: 'Mi Perfil & Direcciones',
    href: '/dashboard?tab=profile',
    icon: UserCircle,
  },
];

// Filter nav items based on authenticated user role
const userRole = computed(() => page.props.auth?.user?.role || '');
const isStaffRole = computed(() => ['Administrador', 'Chef', 'Mesero', 'Recepcionista', 'Repartidor'].includes(userRole.value));

const navItems = computed(() => {
  if (!isStaffRole.value) {
    return clientNavItems;
  }
  return allNavItems.filter(item =>
    item.roles.length === 0 || item.roles.includes(userRole.value)
  );
});

function isUrlActive(url) {
  if (url.includes('?tab=')) {
    const targetTab = url.split('?tab=')[1];
    if (page.url === '/dashboard' && targetTab === 'menu') return true;
    return page.url.includes(`tab=${targetTab}`);
  }
  return page.url.startsWith(url);
}
</script>


<template>
  <div class="min-h-screen w-full overflow-x-hidden bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-100 flex font-sans transition-colors duration-200">
    <Toast />
    <ConfirmDialog />

    <!-- Sidebar -->
    <aside
      :class="[
        'bg-white border-r border-slate-200 dark:bg-slate-900 dark:border-slate-800 flex flex-col transition-all duration-300 z-30 fixed lg:static inset-y-0 left-0 shadow-lg lg:shadow-none',
        sidebarOpen ? 'w-64 translate-x-0' : 'w-64 lg:w-20 -translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Logo Header -->
      <div class="h-20 flex items-center justify-between px-4 border-b border-slate-200 dark:border-slate-800">
        <Link href="/" @click="closeSidebarOnMobile" class="flex items-center gap-3 overflow-hidden group">
          <img
            :src="isDark ? '/images/logo-dark.jpg' : '/images/logo-light.jpg'"
            alt="Logo Las Brazas Steakhouse"
            class="w-11 h-11 rounded-xl object-cover shrink-0 shadow-sm border border-slate-200 dark:border-slate-700 group-hover:scale-105 transition-transform"
          />
          <div v-if="sidebarOpen" class="overflow-hidden">
            <span class="block font-extrabold text-base tracking-wide bg-gradient-to-r from-orange-600 to-amber-500 dark:from-orange-400 dark:to-amber-200 bg-clip-text text-transparent truncate">
              LAS BRAZAS
            </span>
            <span class="block text-[10px] font-bold tracking-widest uppercase text-slate-500 dark:text-slate-400 truncate">
              Steakhouse
            </span>
          </div>
        </Link>

        <button
          type="button"
          @click="sidebarOpen = false"
          class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
          aria-label="Cerrar menú lateral"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 py-4 px-3 space-y-1.5 overflow-y-auto">
        <Link
          v-for="item in navItems"
          :key="item.name"
          :href="item.href"
          @click="closeSidebarOnMobile"
          :title="!sidebarOpen ? item.name : ''"
          :class="[
            'flex items-center gap-3 rounded-xl font-medium text-sm transition-all duration-200 group',
            sidebarOpen ? 'px-3 py-2.5' : 'p-2.5 justify-center',
            isUrlActive(item.href)
              ? 'bg-orange-500/10 text-orange-600 border border-orange-500/30 dark:bg-orange-600/15 dark:text-orange-400 dark:border-orange-500/30 font-semibold'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/60'
          ]"
        >
          <component :is="item.icon" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" />
          <span v-if="sidebarOpen" class="truncate flex-1">{{ item.name }}</span>
          <ChevronRight v-if="sidebarOpen && isUrlActive(item.href)" class="w-4 h-4 text-orange-500 dark:text-orange-400" />
        </Link>
      </nav>

      <!-- Sidebar Footer (User Info & Logout) -->
      <div class="p-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
        <div :class="['flex items-center gap-3 p-2 rounded-xl bg-slate-100 dark:bg-slate-800/40 border border-slate-200/60 dark:border-transparent', !sidebarOpen ? 'justify-center' : '']">
          <Avatar icon="pi pi-user" class="bg-orange-500/20 text-orange-600 dark:text-orange-400 shrink-0" shape="circle" />
          <div v-if="sidebarOpen" class="overflow-hidden flex-1">
            <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">
              {{ page.props.auth?.user?.name || 'Administrador' }}
            </p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">
              {{ page.props.auth?.user?.email || 'admin@lasbrazas.com' }}
            </p>
            <span
              v-if="userRole"
              class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full inline-block mt-0.5"
              :class="{
                'bg-orange-500/20 text-orange-600 dark:text-orange-300': userRole === 'Administrador',
                'bg-blue-500/20 text-blue-600 dark:text-blue-300': userRole === 'Chef',
                'bg-green-500/20 text-green-600 dark:text-green-300': userRole === 'Mesero',
                'bg-purple-500/20 text-purple-600 dark:text-purple-300': userRole === 'Recepcionista',
                'bg-cyan-500/20 text-cyan-600 dark:text-cyan-300': userRole === 'Repartidor',
                'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300': userRole === 'Cliente',
                'bg-slate-500/20 text-slate-600 dark:text-slate-300': !['Administrador','Chef','Mesero','Recepcionista','Repartidor','Cliente'].includes(userRole),
              }"
            >{{ userRole }}</span>
          </div>
        </div>

        <button
          type="button"
          @click="handleSecureLogout"
          :title="!sidebarOpen ? 'Cerrar Sesión' : ''"
          :class="[
            'w-full flex items-center gap-3 rounded-xl text-red-500 hover:bg-red-500/10 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300 font-medium text-xs transition-colors cursor-pointer',
            sidebarOpen ? 'px-3 py-2' : 'p-2.5 justify-center'
          ]"
        >
          <LogOut class="w-4 h-4 shrink-0" />
          <span v-if="sidebarOpen">Cerrar Sesión</span>
        </button>
      </div>
    </aside>

    <!-- Overlay Mobile -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-slate-900/50 dark:bg-slate-950/80 backdrop-blur-sm z-20 lg:hidden"
    ></div>

    <!-- Main Content Container -->
    <div class="flex-1 flex flex-col min-w-0 w-full">
      <!-- Top Navbar -->
      <header class="min-h-16 py-2 bg-white/95 dark:bg-slate-900/90 backdrop-blur border-b border-slate-200 dark:border-slate-800 px-3 sm:px-6 flex items-center justify-between gap-2 sm:gap-4 sticky top-0 z-10 transition-colors duration-200">
        <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
          <button
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition-colors shrink-0 cursor-pointer"
            title="Alternar Menú"
          >
            <Menu class="w-5 h-5 sm:w-6 sm:h-6" />
          </button>
          <div class="text-sm sm:text-lg font-bold text-slate-800 dark:text-slate-100 flex-1 min-w-0 truncate">
            <slot name="header">Panel de Administración</slot>
          </div>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
          <!-- Indicador / Control de Modo Online y Offline (PWA) -->
          <div class="hidden sm:flex">
            <NetworkStatusBadge />
          </div>

          <!-- AI Assistant Navbar Button -->
          <button
            @click="aiChatOpen = !aiChatOpen"
            type="button"
            class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-xl border font-medium text-xs transition-all duration-200 shadow-sm bg-gradient-to-r from-orange-500/10 via-amber-500/10 to-orange-500/10 border-orange-500/30 text-orange-600 hover:border-orange-500 hover:bg-orange-500/20 dark:from-orange-500/20 dark:via-amber-500/20 dark:to-orange-500/20 dark:border-orange-500/40 dark:text-orange-300 dark:hover:border-orange-400 group cursor-pointer"
            title="Abrir Asistente Virtual IA"
            id="btn-open-ai-chat"
          >
            <Sparkles class="w-3.5 h-3.5 text-amber-500 animate-pulse group-hover:scale-110 transition-transform" />
            <span class="font-semibold hidden md:inline">Asistente IA</span>
          </button>

          <!-- Theme Switcher Button -->
          <button
            @click="toggleTheme"
            type="button"
            class="p-2 rounded-xl border transition-all duration-200 flex items-center justify-center text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border-slate-300 dark:text-slate-300 dark:hover:text-amber-400 dark:bg-slate-800/90 dark:hover:bg-slate-800 dark:border-slate-700 shadow-sm cursor-pointer"
            :title="isDark ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
            aria-label="Alternar tema"
          >
            <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
            <Moon v-else class="w-4 h-4 text-slate-700" />
          </button>

          <Link href="/" class="hidden md:flex text-xs text-slate-500 dark:text-slate-400 hover:text-orange-500 dark:hover:text-orange-400 transition-colors items-center gap-1 whitespace-nowrap">
            <span>Ver Menú Público</span>
          </Link>
        </div>
      </header>

      <!-- Banner de aviso cuando está en Modo Offline -->
      <div
        v-if="isOfflineMode"
        class="bg-amber-500/15 dark:bg-amber-500/20 border-b border-amber-500/30 px-3 sm:px-6 py-2 flex flex-wrap items-center justify-between gap-2 text-xs text-amber-800 dark:text-amber-200"
      >
        <span>
          <strong>Modo Offline Activo:</strong> Estás navegando con los datos guardados en la caché segura de tu sesión actual ({{ userRole || 'Usuario' }}). Al cerrar sesión estos datos se eliminan automáticamente.
        </span>
        <button
          v-if="simulatedOffline"
          type="button"
          @click="toggleSimulatedOffline"
          class="px-2.5 py-1 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-bold text-[11px] cursor-pointer"
        >
          Volver a Modo Online
        </button>
      </div>

      <!-- Page Content -->
      <main class="flex-1 p-3 sm:p-6 overflow-y-auto overflow-x-hidden">
        <slot />
      </main>
    </div>

    <!-- Floating AI Chat Button -->
    <button
      v-if="!aiChatOpen"
      @click="aiChatOpen = true"
      type="button"
      class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-40 flex items-center gap-2 px-3.5 py-3 sm:px-4 rounded-full bg-gradient-to-r from-orange-600 to-amber-500 text-white font-medium text-xs sm:text-sm shadow-xl shadow-orange-500/30 hover:shadow-orange-500/50 hover:scale-105 active:scale-95 transition-all duration-200 group cursor-pointer"
      title="Preguntar al Asistente IA"
      id="fab-open-ai-chat"
    >
      <Sparkles class="w-5 h-5 text-amber-200 animate-pulse group-hover:rotate-12 transition-transform" />
      <span class="hidden sm:inline font-bold tracking-wide">Asistente IA</span>
    </button>

    <!-- AI Chat Sidebar Drawer -->
    <AiChatSidebar v-model:visible="aiChatOpen" />
  </div>
</template>
