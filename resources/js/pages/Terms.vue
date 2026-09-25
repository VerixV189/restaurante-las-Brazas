<script setup>
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { useTheme } from '@/composables/useTheme';
import {
  Flame,
  FileText,
  Clock,
  ShieldCheck,
  CreditCard,
  UtensilsCrossed,
  AlertCircle,
  ArrowLeft,
  UserCheck,
  CheckCircle2,
  Sun,
  Moon
} from 'lucide-vue-next';

const { isDark, initTheme, toggleTheme } = useTheme();

onMounted(() => {
  initTheme();
});
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans transition-colors duration-200">
    <!-- Navbar -->
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
      <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">
        <!-- Logo -->
        <Link href="/" class="flex items-center gap-3 group">
          <div class="bg-gradient-to-tr from-orange-600 to-amber-500 p-2.5 rounded-2xl text-white shadow-lg shadow-orange-600/20 group-hover:scale-105 transition-transform">
            <Flame class="w-6 h-6" />
          </div>
          <div>
            <span class="font-extrabold text-xl tracking-wide bg-gradient-to-r from-orange-500 via-amber-500 to-yellow-500 bg-clip-text text-transparent">
              Las Brazas
            </span>
            <p class="text-[10px] tracking-widest text-slate-500 dark:text-slate-400 uppercase font-semibold">Steakhouse & Grill</p>
          </div>
        </Link>

        <!-- Right Actions -->
        <div class="flex items-center gap-3">
          <!-- Theme Toggle -->
          <button
            type="button"
            @click="toggleTheme"
            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:text-orange-500 transition-colors"
            :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
          >
            <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
            <Moon v-else class="w-4 h-4 text-slate-600" />
          </button>

          <Link href="/register">
            <Button
              label="Crear Cuenta"
              icon="pi pi-user-plus"
              class="bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white font-semibold rounded-xl border-none shadow-md shadow-orange-600/20 text-xs px-4 h-10"
            />
          </Link>

          <Link href="/login">
            <Button
              label="Iniciar Sesión"
              icon="pi pi-sign-in"
              text
              class="text-slate-600 dark:text-slate-300 hover:text-orange-500 text-xs h-10"
            />
          </Link>
        </div>
      </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto px-6 py-12 space-y-10">
      <!-- Back Link -->
      <div>
        <Link href="/register" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-orange-500 transition-colors">
          <ArrowLeft class="w-4 h-4" /> Volver al formulario de registro
        </Link>
      </div>

      <!-- Hero Header -->
      <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-orange-500/10 border border-orange-500/30 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-wider">
          <FileText class="w-4 h-4" /> Documento Legal y Normativo
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
          Términos del Servicio y Condiciones del Sitio
        </h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
          En <strong>Restaurante Las Brazas</strong> valoramos la transparencia y la confianza de nuestros comensales. A continuación detallamos los términos y políticas que rigen el uso de nuestro portal y la experiencia en el salón.
        </p>
        <p class="text-xs text-slate-400 dark:text-slate-500 font-mono">
          Vigente desde: Septiembre de 2026 • Versión 2.0
        </p>
      </div>

      <!-- Quick Summary Alert -->
      <div class="p-5 rounded-2xl bg-orange-50 dark:bg-orange-950/20 border border-orange-200 dark:border-orange-800/50 flex items-start gap-4">
        <div class="p-2 rounded-xl bg-orange-500 text-white shrink-0 mt-0.5">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div class="text-xs text-slate-700 dark:text-slate-300 space-y-1">
          <p class="font-bold text-orange-900 dark:text-orange-200 text-sm">Resumen Esencial para Comensales:</p>
          <ul class="list-disc pl-4 space-y-1 text-slate-600 dark:text-slate-400">
            <li>Al registrarte aceptas los Términos del Servicio y recibirás copia detallada en tu correo electrónico.</li>
            <li>Las reservaciones cuentan con un margen de tolerancia estricto de <strong>15 minutos</strong> antes de liberar la mesa.</li>
            <li>Tus datos personales están protegidos y solo se usan para gestionar tus reservas y brindarte un servicio de excelencia.</li>
          </ul>
        </div>
      </div>

      <!-- Sections Grid -->
      <div class="space-y-8">
        <!-- Section 1 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-500">
              <UserCheck class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">1. Términos del Servicio y Registro de Cuenta</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Condiciones de acceso como comensal registrado</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-3 leading-relaxed">
            <p>
              El acceso a determinadas funcionalidades del portal de <strong>Restaurante Las Brazas</strong> (como la confirmación automática de reservas, historial de visitas y personalización de preferencias) requiere la creación previa de una cuenta de usuario.
            </p>
            <p>
              Al registrarse, usted garantiza que los datos suministrados (nombre completo, correo electrónico y teléfono de contacto) son veraces y corresponden a su persona. Cada usuario es responsable de salvaguardar su contraseña de acceso.
            </p>
          </div>
        </section>

        <!-- Section 2 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500">
              <Clock class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">2. Políticas de Reserva, Tolerancia y Asistencia</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Normas para garantizar mesas a todos nuestros visitantes</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-3 leading-relaxed">
            <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 text-amber-900 dark:text-amber-200 font-semibold text-xs">
              ⏱ Tolerancia de 15 Minutos: Toda reserva confirmada se mantendrá bloqueada por un lapso máximo de 15 minutos contados a partir de la hora solicitada. Pasado este tiempo sin aviso previo, el sistema liberará automáticamente la mesa para comensales en lista de espera.
            </div>
            <p>
              Para grupos mayores a 8 comensales, solicitamos confirmar asistencia telefónicamente o mediante el enlace recibido en su correo con al menos 2 horas de anticipación.
            </p>
            <p>
              En caso de no poder asistir, el comensal puede cancelar su reserva en cualquier momento sin costo alguno a través de la plataforma o informando a nuestro personal.
            </p>
          </div>
        </section>

        <!-- Section 3 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-500">
              <UtensilsCrossed class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">3. Menú, Cortes a la Brasa y Alérgenos</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Calidad culinaria y seguridad alimentaria</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-3 leading-relaxed">
            <p>
              Todos nuestros cortes (Arrachera, Bife de Chorizo, Picaña, Costillares y Guarniciones) se preparan con carne fresca e insumos seleccionados bajo rigurosos estándares de higiene y cocción a las brasas de carbón y leña.
            </p>
            <p>
              Si usted o algún acompañante presenta alergias alimentarias (gluten, lactosa, frutos secos, etc.) o prefiere un término específico de cocción, solicitamos indicarlo expresamente al momento de hacer la comanda con nuestro personal de meseros.
            </p>
          </div>
        </section>

        <!-- Section 4 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-green-500/10 text-green-500">
              <CreditCard class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">4. Facturación, Métodos de Pago y Precios</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Transparencia económica en el punto de venta</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-3 leading-relaxed">
            <p>
              Los precios exhibidos en la carta física y digital están expresados en moneda nacional e incluyen todos los impuestos de ley. Emitimos factura electrónica o recibo fiscal por cada consumo realizado en salón o modalidad para llevar.
            </p>
            <p>
              Aceptamos los siguientes métodos de pago: Efectivo, Tarjetas de Débito y Crédito (Visa, Mastercard), Transferencias Bancarias y Códigos QR de cobro inmediato. La propina para nuestro equipo es totalmente voluntaria.
            </p>
          </div>
        </section>

        <!-- Section 5 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-500">
              <ShieldCheck class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">5. Privacidad y Protección de Datos Personales</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Seguridad de la información del cliente</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-3 leading-relaxed">
            <p>
              En <strong>Restaurante Las Brazas</strong> no comercializamos ni compartimos datos con terceros con fines ajenos a nuestro servicio. Sus datos se utilizan exclusivamente para:
            </p>
            <ul class="list-disc pl-5 space-y-1">
              <li>Coordinar y confirmar el estado de sus reservas en salón.</li>
              <li>Emitir facturas y comprobantes fiscales correspondientes.</li>
              <li>Enviar notificaciones sobre su cuenta (términos, condiciones y enlaces de verificación).</li>
              <li>Informar de manera no intrusiva sobre promociones especiales si el cliente lo autoriza.</li>
            </ul>
          </div>
        </section>

        <!-- Section 6 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-red-500/10 text-red-500">
              <AlertCircle class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">6. Código de Convivencia y Admisión</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Ambiente armónico y familiar</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-3 leading-relaxed">
            <p>
              Nos esforzamos por brindar una atmósfera cálida, acogedora y familiar. Nos reservamos el derecho de admisión y permanencia ante comportamientos violentos, estados de ebriedad manifiesta o faltas de respeto hacia nuestro personal u otros comensales.
            </p>
          </div>
        </section>
      </div>

      <!-- Bottom Call to Action Card -->
      <div class="bg-gradient-to-r from-orange-600 to-amber-600 rounded-3xl p-8 text-white text-center space-y-4 shadow-xl shadow-orange-600/20">
        <h3 class="text-2xl font-extrabold tracking-tight">¿Listo para unirte a Las Brazas?</h3>
        <p class="text-sm text-orange-100 max-w-lg mx-auto">
          Crea tu cuenta de comensal hoy mismo y disfruta de atención prioritaria, confirmación ágil de reservas y cortes de primera.
        </p>
        <div class="flex items-center justify-center gap-4 pt-2">
          <Link href="/register">
            <Button
              label="Continuar con el Registro"
              icon="pi pi-check"
              class="bg-white hover:bg-orange-50 text-orange-700 font-bold rounded-xl border-none shadow-md px-6 h-11"
            />
          </Link>
          <Link href="/">
            <Button
              label="Volver al Inicio"
              text
              class="text-white hover:text-orange-200 font-semibold"
            />
          </Link>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 dark:border-slate-800 py-8 text-center text-xs text-slate-500 dark:text-slate-400">
      © 2026 Restaurante Las Brazas • Steakhouse & Grill. Todos los derechos reservados.
    </footer>
  </div>
</template>
