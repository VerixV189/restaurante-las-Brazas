<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useTheme } from '../composables/useTheme';
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
const sidebarOpen = ref(true);
const { isDark, initTheme, toggleTheme } = useTheme();

onMounted(() => {
  initTheme();
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
    name: 'Planos IA (YOLO)',
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

// Filter nav items based on authenticated user role
const userRole = computed(() => page.props.auth?.user?.role || '');

const navItems = computed(() =>
  allNavItems.filter(item =>
    item.roles.length === 0 || item.roles.includes(userRole.value)
  )
);

function isUrlActive(url) {
  return page.url.startsWith(url);
}
</script>


<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-100 flex font-sans transition-colors duration-200">
    <Toast />
    <ConfirmDialog />

    <!-- Sidebar -->
    <aside
      :class="[
        'bg-white border-r border-slate-200 dark:bg-slate-900 dark:border-slate-800 flex flex-col transition-all duration-300 z-30 fixed lg:static inset-y-0 left-0 shadow-sm lg:shadow-none',
        sidebarOpen ? 'w-64 translate-x-0' : 'w-20 -translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Logo Header -->
      <div class="h-16 flex items-center justify-between px-4 border-b border-slate-200 dark:border-slate-800">
        <Link href="/" class="flex items-center gap-3 overflow-hidden">
          <div class="bg-gradient-to-tr from-orange-600 to-amber-500 p-2 rounded-xl text-white shrink-0 shadow-sm shadow-orange-500/20">
            <Flame class="w-6 h-6" />
          </div>
          <span v-if="sidebarOpen" class="font-bold text-lg tracking-wide bg-gradient-to-r from-orange-600 to-amber-500 dark:from-orange-400 dark:to-amber-200 bg-clip-text text-transparent truncate">
            Las Brazas
          </span>
        </Link>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 py-4 px-3 space-y-1.5 overflow-y-auto">
        <Link
          v-for="item in navItems"
          :key="item.name"
          :href="item.href"
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
                'bg-slate-500/20 text-slate-600 dark:text-slate-300': !['Administrador','Chef','Mesero','Recepcionista'].includes(userRole),
              }"
            >{{ userRole }}</span>
          </div>
        </div>

        <Link
          href="/logout"
          method="post"
          as="button"
          :title="!sidebarOpen ? 'Cerrar Sesión' : ''"
          :class="[
            'w-full flex items-center gap-3 rounded-xl text-red-500 hover:bg-red-500/10 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300 font-medium text-xs transition-colors',
            sidebarOpen ? 'px-3 py-2' : 'p-2.5 justify-center'
          ]"
        >
          <LogOut class="w-4 h-4 shrink-0" />
          <span v-if="sidebarOpen">Cerrar Sesión</span>
        </Link>
      </div>
    </aside>

    <!-- Overlay Mobile -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-slate-900/50 dark:bg-slate-950/80 backdrop-blur-sm z-20 lg:hidden"
    ></div>

    <!-- Main Content Container -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Navbar -->
      <header class="h-16 bg-white/80 dark:bg-slate-900/60 backdrop-blur border-b border-slate-200 dark:border-slate-800 px-6 flex items-center justify-between sticky top-0 z-10 transition-colors duration-200">
        <div class="flex items-center gap-4">
          <button
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition-colors"
            title="Alternar Menú"
          >
            <Menu class="w-6 h-6" />
          </button>
          <h1 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
            <slot name="header">Panel de Administración</slot>
          </h1>
        </div>

        <div class="flex items-center gap-3">
          <!-- Theme Switcher Button -->
          <button
            @click="toggleTheme"
            type="button"
            class="p-2 rounded-xl border transition-all duration-200 flex items-center justify-center text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border-slate-300 dark:text-slate-300 dark:hover:text-amber-400 dark:bg-slate-800/90 dark:hover:bg-slate-800 dark:border-slate-700 shadow-sm"
            :title="isDark ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
            aria-label="Alternar tema"
          >
            <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
            <Moon v-else class="w-4 h-4 text-slate-700" />
          </button>

          <Link href="/" class="text-xs text-slate-500 dark:text-slate-400 hover:text-orange-500 dark:hover:text-orange-400 transition-colors flex items-center gap-1">
            <span>Ver Landing / Menú Público</span>
          </Link>
          <Tag value="Sistema Restaurante v1.0" severity="warn" class="hidden sm:inline-flex bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-500/20" />
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-6 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
