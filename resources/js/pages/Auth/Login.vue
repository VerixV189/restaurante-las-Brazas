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
  email: '',
  password: '',
  remember: true,
});

function submitLogin() {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
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
          <div class="inline-flex mb-1">
            <img
              :src="isDark ? '/images/logo-dark.jpg' : '/images/logo-light.jpg'"
              alt="Las Brazas Steakhouse"
              class="w-28 h-28 rounded-2xl object-cover shadow-lg border border-slate-200 dark:border-slate-800 transition-all duration-300"
            />
          </div>
          <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Portal de Comensales</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400">Inicia sesión para ordenar, reservar tu mesa y canjear cupones en Las Brazas</p>
        </div>

        <!-- Flash Messages (Registration or Verification Success) -->
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

        <!-- Botón de acceso rápido con Google OAuth 2.0 -->
        <div class="mb-5">
          <a
            href="/auth/google/redirect"
            class="w-full flex items-center justify-center gap-3 py-3 px-4 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/80 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 font-semibold text-sm shadow-sm hover:shadow transition-all duration-200"
          >
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
              <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z"/>
              <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z"/>
              <path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.13-1.57.38-2.29V6.62H1.29C.47 8.24 0 10.06 0 12s.47 3.76 1.29 5.38l3.98-3.09z"/>
              <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09c.95-2.85 3.6-4.96 6.73-4.96z"/>
            </svg>
            <span>Continuar con Google</span>
          </a>

          <div class="relative flex items-center justify-center my-4">
            <div class="border-t border-slate-200 dark:border-slate-800 w-full"></div>
            <span class="bg-white dark:bg-slate-900 px-3 text-[11px] uppercase tracking-wider font-semibold text-slate-400 dark:text-slate-500 shrink-0">
              o continúa con tu correo
            </span>
            <div class="border-t border-slate-200 dark:border-slate-800 w-full"></div>
          </div>
        </div>

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
                placeholder="tucorreo@gmail.com"
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

        <!-- Link to Register & Staff Portal -->
        <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800 text-center space-y-2.5 text-xs text-slate-600 dark:text-slate-400">
          <div>
            ¿No tienes una cuenta de comensal?
            <Link href="/register" class="text-orange-600 dark:text-orange-400 font-bold hover:underline ml-1">
              Regístrate aquí
            </Link>
          </div>
          <div class="text-[11px] text-slate-400 dark:text-slate-500">
            ¿Eres parte del equipo de Las Brazas?
            <Link href="/staff/login" class="text-slate-600 dark:text-slate-300 font-semibold hover:text-orange-500 underline ml-1">
              Acceso Personal →
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
