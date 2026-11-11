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
  User,
  Mail,
  Phone,
  Lock,
  KeyRound,
  ArrowLeft,
  ShieldCheck,
  AlertTriangle,
  Info,
  Sun,
  Moon
} from 'lucide-vue-next';

const { isDark, initTheme, toggleTheme } = useTheme();

onMounted(() => {
  initTheme();
});

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  terms: false,
});

function submitRegister() {
  if (!form.terms) {
    return;
  }

  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
}
</script>

<template>
  <div class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex items-center justify-center p-6 relative overflow-hidden font-sans transition-colors duration-200">
    <!-- Background Ambient Glow -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-orange-600/15 dark:bg-orange-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-amber-600/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-lg space-y-5 relative z-10">
      <!-- Top Navigation & Theme Switch -->
      <div class="flex items-center justify-between">
        <Link href="/" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-orange-500 transition-colors">
          <ArrowLeft class="w-4 h-4" /> Volver al menú principal
        </Link>
        
        <div class="flex items-center gap-3">
          <Link href="/terms" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-orange-500 transition-colors">
            <Info class="w-3.5 h-3.5" /> Términos y condiciones
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

      <!-- Main Registration Card -->
      <div class="auth-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 sm:p-10 shadow-xl dark:shadow-2xl dark:shadow-slate-950 transition-colors">
        <!-- Logo & Title -->
        <div class="text-center space-y-2 mb-6">
          <div class="inline-flex mb-1">
            <img
              :src="isDark ? '/images/logo-dark.jpg' : '/images/logo-light.jpg'"
              alt="Las Brazas Steakhouse"
              class="w-28 h-28 rounded-2xl object-cover shadow-lg border border-slate-200 dark:border-slate-800 transition-all duration-300"
            />
          </div>
          <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Crear Cuenta en Las Brazas</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400">Regístrate para reservar mesas, gestionar tus pedidos y acceder a promociones</p>
        </div>

        <!-- Information Notice regarding 3 emails -->
        <div class="mb-6 p-3.5 rounded-2xl bg-orange-50 dark:bg-orange-950/20 border border-orange-200 dark:border-orange-800/40 flex items-start gap-3">
          <ShieldCheck class="w-5 h-5 text-orange-600 dark:text-orange-400 shrink-0 mt-0.5" />
          <div class="text-xs text-slate-700 dark:text-slate-300">
            <p class="font-bold text-orange-700 dark:text-orange-300">Activación en 3 Pasos:</p>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5 leading-relaxed">
              Al completar tu registro, recibirás <strong>3 correos</strong>: Términos del Servicio, Condiciones de Reserva y el Enlace de Verificación de tu Cuenta.
            </p>
          </div>
        </div>

        <!-- General / Server Error Messages -->
        <Message v-if="form.errors.terms" severity="error" class="mb-4 text-xs">
          {{ form.errors.terms }}
        </Message>

        <!-- Registration Form -->
        <form @submit.prevent="submitRegister" class="space-y-4">
          <!-- Full Name -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Nombre Completo *</label>
            <div class="relative flex items-center">
              <User class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 pointer-events-none z-10" />
              <InputText
                type="text"
                v-model="form.name"
                class="input-with-icon w-full h-11 text-sm rounded-xl focus:border-orange-500"
                style="padding-left: 2.75rem !important;"
                placeholder="Ej: Marcelo Quiroga"
                required
              />
            </div>
            <p v-if="form.errors.name" class="text-red-500 text-[11px] mt-1">{{ form.errors.name }}</p>
          </div>

          <!-- Email & Phone Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Email -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Correo Electrónico *</label>
              <div class="relative flex items-center">
                <Mail class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 pointer-events-none z-10" />
                <InputText
                  type="email"
                  v-model="form.email"
                  class="input-with-icon w-full h-11 text-sm rounded-xl focus:border-orange-500"
                  style="padding-left: 2.75rem !important;"
                  placeholder="comensal@ejemplo.com"
                  required
                />
              </div>
              <p v-if="form.errors.email" class="text-red-500 text-[11px] mt-1">{{ form.errors.email }}</p>
            </div>

            <!-- Phone -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Teléfono Móvil</label>
              <div class="relative flex items-center">
                <Phone class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 pointer-events-none z-10" />
                <InputText
                  type="tel"
                  v-model="form.phone"
                  class="input-with-icon w-full h-11 text-sm rounded-xl focus:border-orange-500"
                  style="padding-left: 2.75rem !important;"
                  placeholder="700-12345"
                />
              </div>
              <p v-if="form.errors.phone" class="text-red-500 text-[11px] mt-1">{{ form.errors.phone }}</p>
            </div>
          </div>

          <!-- Passwords Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Password -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Contraseña *</label>
              <div class="relative flex items-center">
                <Lock class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 pointer-events-none z-10" />
                <InputText
                  type="password"
                  v-model="form.password"
                  class="input-with-icon w-full h-11 text-sm rounded-xl focus:border-orange-500"
                  style="padding-left: 2.75rem !important;"
                  placeholder="Mínimo 8 carácteres"
                  required
                />
              </div>
              <p v-if="form.errors.password" class="text-red-500 text-[11px] mt-1">{{ form.errors.password }}</p>
            </div>

            <!-- Confirm Password -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Confirmar Contraseña *</label>
              <div class="relative flex items-center">
                <KeyRound class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3.5 pointer-events-none z-10" />
                <InputText
                  type="password"
                  v-model="form.password_confirmation"
                  class="input-with-icon w-full h-11 text-sm rounded-xl focus:border-orange-500"
                  style="padding-left: 2.75rem !important;"
                  placeholder="Repite tu contraseña"
                  required
                />
              </div>
            </div>
          </div>

          <!-- Mandatory Terms & Conditions Checkbox -->
          <div class="pt-2">
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 flex items-start gap-3 transition-colors" :class="{ 'border-orange-400 dark:border-orange-500/50 bg-orange-50/50 dark:bg-orange-950/15': form.terms }">
              <Checkbox
                v-model="form.terms"
                :binary="true"
                inputId="terms_checkbox"
                class="mt-0.5"
              />
              <div class="text-xs text-slate-700 dark:text-slate-300 select-none">
                <label for="terms_checkbox" class="cursor-pointer">
                  He leído y acepto los
                </label>
                <!-- Clickable text redirecting directly to Terms and Conditions page -->
                <a
                  href="/terms"
                  target="_blank"
                  class="text-orange-600 dark:text-orange-400 font-bold underline hover:text-orange-500 transition-colors ml-1"
                  title="Abrir y leer los términos y condiciones"
                >
                  términos y condiciones
                </a>
                <span> del sistema de Restaurante Las Brazas.</span>
                <p class="text-[11px] text-slate-500 mt-1">
                  (Haz clic en <span class="text-orange-600 dark:text-orange-400 font-medium">"términos y condiciones"</span> para revisarlos antes de aceptar).
                </p>
              </div>
            </div>
          </div>

          <!-- Submit Button - Disabled until terms is checked -->
          <div class="pt-2">
            <Button
              type="submit"
              label="Registrarse y Crear Cuenta"
              icon="pi pi-user-plus"
              :disabled="!form.terms || form.processing"
              :loading="form.processing"
              :class="[
                'w-full h-12 font-bold rounded-xl border-none transition-all duration-200',
                form.terms
                  ? 'bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white shadow-lg shadow-orange-600/30 cursor-pointer hover:scale-[1.01]'
                  : 'bg-slate-200 dark:bg-slate-800 text-slate-400 dark:text-slate-500 opacity-60 cursor-not-allowed border border-slate-300 dark:border-slate-700'
              ]"
            />

            <!-- Warning note if button is disabled -->
            <p v-if="!form.terms" class="text-center text-[11px] text-amber-600 dark:text-amber-500 mt-2 flex items-center justify-center gap-1.5 font-medium">
              <AlertTriangle class="w-3.5 h-3.5" />
              Debes marcar la casilla de aceptación para poder registrarte.
            </p>
          </div>
        </form>

        <!-- Divider & Login Link -->
        <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800 text-center text-xs text-slate-600 dark:text-slate-400">
          ¿Ya tienes una cuenta registrada?
          <Link href="/login" class="text-orange-600 dark:text-orange-400 font-bold hover:underline ml-1">
            Inicia sesión aquí
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
