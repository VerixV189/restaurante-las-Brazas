<script setup>
import { onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import { useTheme } from '@/composables/useTheme';
import {
  Flame,
  Lock,
  Mail,
  ArrowLeft,
  KeyRound,
  Info,
  Sun,
  Moon
} from 'lucide-vue-next';

const { isDark, initTheme, toggleTheme } = useTheme();

onMounted(() => {
  initTheme();
});

const form = useForm({
  email: 'admin@lasbrazas.com',
  password: 'password123',
  remember: true,
});

function submitLogin() {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
}

function fillAdminCredentials() {
  form.email = 'admin@lasbrazas.com';
  form.password = 'password123';
}
</script>

<template>
  <div class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex items-center justify-center p-6 relative overflow-hidden font-sans transition-colors duration-200">
    <!-- Background Ambient Glow -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-orange-600/15 dark:bg-orange-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-amber-600/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md space-y-5 relative z-10">
      <!-- Top Navigation & Theme Switch -->
      <div class="flex items-center justify-between">
        <Link href="/" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-orange-500 transition-colors">
          <ArrowLeft class="w-4 h-4" /> Volver al menú principal
        </Link>
        
        <div class="flex items-center gap-3">
          <Link href="/terms" class="text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-orange-500 transition-colors">
            Términos y Condiciones
          </Link>
          <button
            type="button"
            @click="toggleTheme"
            class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:text-orange-500 shadow-sm transition-colors"
            :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
          >
            <Sun v-if="isDark" class="w-3.5 h-3.5 text-amber-400" />
            <Moon v-else class="w-3.5 h-3.5 text-slate-600" />
          </button>
        </div>
      </div>

      <!-- Main Login Card -->
      <div class="auth-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-xl dark:shadow-2xl dark:shadow-slate-950 transition-colors">
        <!-- Logo & Title -->
        <div class="text-center space-y-2 mb-6">
          <div class="inline-flex bg-gradient-to-tr from-orange-600 to-amber-500 p-3 rounded-2xl text-white shadow-lg shadow-orange-600/30 mb-1">
            <Flame class="w-7 h-7" />
          </div>
          <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Acceso a Las Brazas</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400">Ingresa tus credenciales de comensal o personal administrativo</p>
        </div>

        <!-- Flash Messages (Registration or Verification Success) -->
        <Message v-if="$page.props.flash?.success" severity="success" class="mb-4 text-xs">
          {{ $page.props.flash.success }}
        </Message>
        <Message v-if="$page.props.flash?.error" severity="error" class="mb-4 text-xs">
          {{ $page.props.flash.error }}
        </Message>

        <!-- Credentials Helper Box -->
        <div class="mb-6 p-4 rounded-2xl bg-orange-50 dark:bg-orange-950/20 border border-orange-200 dark:border-orange-800/40 flex items-start gap-3">
          <KeyRound class="w-5 h-5 text-orange-600 dark:text-orange-400 shrink-0 mt-0.5" />
          <div class="text-xs text-slate-700 dark:text-slate-300 flex-1">
            <p class="font-bold text-orange-700 dark:text-orange-300">Credenciales Rápidas (Demo Admin):</p>
            <p class="mt-1 font-mono text-[11px] text-slate-600 dark:text-slate-400">Email: <span class="font-semibold text-slate-900 dark:text-slate-200">admin@lasbrazas.com</span></p>
            <p class="font-mono text-[11px] text-slate-600 dark:text-slate-400">Pass: <span class="font-semibold text-slate-900 dark:text-slate-200">password123</span></p>
            <button type="button" @click="fillAdminCredentials" class="mt-2 text-orange-600 dark:text-orange-400 underline font-semibold hover:text-orange-500 text-[11px]">
              Usar estas credenciales
            </button>
          </div>
        </div>

        <!-- Error Message -->
        <Message v-if="form.errors.email" severity="error" class="mb-4 text-xs">
          {{ form.errors.email }}
        </Message>

        <!-- Form -->
        <form @submit.prevent="submitLogin" class="space-y-4">
          <!-- Email Input -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Correo Electrónico</label>
            <div class="relative flex items-center">
              <Mail class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 pointer-events-none z-10" />
              <InputText
                type="email"
                v-model="form.email"
                class="input-with-icon w-full h-11 text-sm rounded-xl focus:border-orange-500"
                style="padding-left: 2.75rem !important;"
                placeholder="admin@lasbrazas.com"
                required
              />
            </div>
          </div>

          <!-- Password Input -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Contraseña</label>
            <div class="relative flex items-center">
              <Lock class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 pointer-events-none z-10" />
              <InputText
                type="password"
                v-model="form.password"
                class="input-with-icon w-full h-11 text-sm rounded-xl focus:border-orange-500"
                style="padding-left: 2.75rem !important;"
                placeholder="••••••••"
                required
              />
            </div>
          </div>

          <div class="flex items-center justify-between pt-1">
            <div class="flex items-center gap-2">
              <Checkbox v-model="form.remember" :binary="true" inputId="remember" />
              <label for="remember" class="text-xs text-slate-600 dark:text-slate-400 cursor-pointer">Recordarme</label>
            </div>
          </div>

          <Button
            type="submit"
            label="Iniciar Sesión"
            icon="pi pi-sign-in"
            class="w-full h-11 bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white font-bold rounded-xl border-none shadow-lg shadow-orange-600/25 mt-2"
            :loading="form.processing"
          />
        </form>

        <!-- Link to Register -->
        <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800 text-center text-xs text-slate-600 dark:text-slate-400">
          ¿No tienes una cuenta de comensal?
          <Link href="/register" class="text-orange-600 dark:text-orange-400 font-bold hover:underline ml-1">
            Regístrate aquí
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
