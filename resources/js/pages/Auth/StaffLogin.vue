<script setup>
import { onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import { useTheme } from '@/composables/useTheme';
import {
  ShieldCheck,
  Lock,
  Mail,
  ArrowLeft,
  Building2,
  Sun,
  Moon
} from 'lucide-vue-next';

const { isDark, initTheme, toggleTheme } = useTheme();

onMounted(() => {
  initTheme();
});

const form = useForm({
  email: '',
  password: '',
  remember: true,
});

function submitStaffLogin() {
  form.post('/staff/login', {
    onFinish: () => form.reset('password'),
  });
}
</script>

<template>
  <div class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex items-center justify-center p-6 relative overflow-hidden font-sans transition-colors duration-200">
    <!-- Background Corporate Ambient Glow -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/15 dark:bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-orange-600/10 dark:bg-orange-600/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md space-y-5 relative z-10">
      <!-- Top Navigation & Theme Switch -->
      <div class="flex items-center justify-between gap-2">
        <Link href="/empresa" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
          <ArrowLeft class="w-4 h-4" /> Sitio Empresarial
        </Link>

        <div class="flex items-center gap-2.5">
          <Link href="/" class="text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-orange-500 transition-colors">
            Sitio Restaurante
          </Link>
          <button
            type="button"
            @click="toggleTheme"
            class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:text-blue-500 shadow-sm transition-colors cursor-pointer"
            :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
          >
            <Sun v-if="isDark" class="w-3.5 h-3.5 text-amber-400" />
            <Moon v-else class="w-3.5 h-3.5 text-slate-600" />
          </button>
        </div>
      </div>

      <!-- Main Staff Login Card -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-xl dark:shadow-2xl dark:shadow-slate-950 transition-colors">
        <!-- Badge & Logo -->
        <div class="text-center space-y-2.5 mb-6">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/25 text-blue-700 dark:text-blue-300 text-[11px] font-bold uppercase tracking-wider">
            <ShieldCheck class="w-3.5 h-3.5" /> Acceso Restringido • Personal Autorizado
          </div>

          <div class="flex justify-center pt-1">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-slate-900 via-blue-900 to-orange-600 flex items-center justify-center text-white shadow-lg border border-slate-700">
              <Building2 class="w-8 h-8" />
            </div>
          </div>

          <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Portal de Personal & Administración
          </h1>
          <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
            Uso exclusivo para el equipo operativo de <strong>Las Brazas Steakhouse</strong> y gestión corporativa.
          </p>

          <!-- Authorized Roles Chips -->
          <div class="flex flex-wrap items-center justify-center gap-1.5 pt-1">
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-semibold">Administrador</span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-semibold">Chef / KDS</span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-semibold">Mesero / POS</span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-semibold">Recepcionista</span>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-semibold">Repartidor</span>
          </div>
        </div>

        <!-- Flash Messages -->
        <Message v-if="$page.props.flash?.success" severity="success" class="mb-4 text-xs">
          {{ $page.props.flash.success }}
        </Message>
        <Message v-if="$page.props.flash?.error" severity="error" class="mb-4 text-xs">
          {{ $page.props.flash.error }}
        </Message>

        <!-- Error Message -->
        <Message v-if="form.errors.email" severity="error" class="mb-4 text-xs">
          {{ form.errors.email }}
        </Message>

        <!-- Corporate Credentials Form -->
        <form @submit.prevent="submitStaffLogin" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">
              Correo Corporativo
            </label>
            <div class="relative flex items-center">
              <Mail class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 pointer-events-none z-10" />
              <InputText
                type="email"
                v-model="form.email"
                class="input-with-icon w-full h-11 text-sm rounded-xl focus:border-blue-500"
                style="padding-left: 2.75rem !important;"
                placeholder="usuario@lasbrazas.com"
                autocomplete="username"
                required
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">
              Contraseña Operativa
            </label>
            <div class="relative flex items-center">
              <Lock class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 pointer-events-none z-10" />
              <InputText
                type="password"
                v-model="form.password"
                class="input-with-icon w-full h-11 text-sm rounded-xl focus:border-blue-500"
                style="padding-left: 2.75rem !important;"
                placeholder="••••••••"
                autocomplete="current-password"
                required
              />
            </div>
          </div>

          <div class="flex items-center justify-between pt-1">
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.remember" :binary="true" inputId="staff_remember" />
              <label for="staff_remember" class="text-xs text-slate-600 dark:text-slate-400 cursor-pointer">
                Mantener sesión iniciada
              </label>
            </div>
          </div>

          <Button
            type="submit"
            label="Ingresar al Panel Operativo"
            icon="pi pi-shield"
            class="w-full h-11 !bg-gradient-to-r !from-slate-900 !via-blue-900 !to-slate-900 hover:!from-blue-800 hover:!to-slate-800 !text-white font-bold rounded-xl !border-none shadow-lg mt-2"
            :loading="form.processing"
          />
        </form>

        <!-- Notice for Clients -->
        <div class="mt-7 pt-5 border-t border-slate-200 dark:border-slate-800 text-center space-y-2 text-xs text-slate-500 dark:text-slate-400">
          <p>Las cuentas de empleados son creadas únicamente por la Administración.</p>
          <p>
            ¿Eres cliente o comensal?
            <Link href="/login" class="text-orange-600 dark:text-orange-400 font-bold hover:underline ml-1">
              Ir al Portal de Comensales →
            </Link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
