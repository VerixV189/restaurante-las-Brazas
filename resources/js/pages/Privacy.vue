<script setup>
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { useTheme } from '@/composables/useTheme';
import {
  Flame,
  ShieldCheck,
  Lock,
  Database,
  Mail,
  UserCheck,
  Eye,
  Trash2,
  ArrowLeft,
  CheckCircle2,
  FileText,
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
    <header class="sticky top-0 z-40 bg-white/85 dark:bg-slate-950/85 backdrop-blur-md border-b border-slate-200 dark:border-slate-800">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between gap-3">
        <!-- Logo -->
        <Link href="/" class="flex items-center gap-3 group shrink-0">
          <div class="bg-gradient-to-tr from-emerald-600 to-teal-500 p-2.5 rounded-2xl text-white shadow-lg shadow-emerald-600/20 group-hover:scale-105 transition-transform">
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
        <div class="flex items-center gap-2 sm:gap-3">
          <button
            type="button"
            @click="toggleTheme"
            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:text-orange-500 transition-colors cursor-pointer"
            :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
          >
            <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
            <Moon v-else class="w-4 h-4 text-slate-600" />
          </button>

          <Link href="/terms" class="hidden sm:inline-block">
            <Button
              label="Términos"
              icon="pi pi-file"
              text
              class="!text-slate-600 dark:!text-slate-300 hover:!text-orange-500 text-xs h-10"
            />
          </Link>

          <Link href="/register">
            <Button
              label="Crear Cuenta"
              icon="pi pi-user-plus"
              class="!bg-gradient-to-r !from-orange-600 !to-amber-500 hover:!from-orange-500 hover:!to-amber-400 !text-white font-semibold rounded-xl !border-none shadow-md shadow-orange-600/20 text-xs px-4 h-10"
            />
          </Link>

          <Link href="/login">
            <Button
              label="Iniciar Sesión"
              icon="pi pi-sign-in"
              text
              class="!text-slate-600 dark:!text-slate-300 hover:!text-orange-500 text-xs h-10"
            />
          </Link>
        </div>
      </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto px-6 py-12 space-y-10">
      <!-- Navigation Back -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        <Link href="/" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
          <ArrowLeft class="w-4 h-4" /> Volver al inicio
        </Link>
        <Link href="/terms" class="inline-flex items-center gap-1.5 text-xs font-semibold text-orange-600 dark:text-orange-400 hover:underline">
          <FileText class="w-3.5 h-3.5" /> Ver Términos y Condiciones del Servicio
        </Link>
      </div>

      <!-- Hero Header -->
      <div class="text-center space-y-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider">
          <ShieldCheck class="w-4 h-4" /> Protección de Datos Personales
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
          Política de Privacidad
        </h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 max-w-2xl mx-auto leading-relaxed">
          En <strong>Restaurante Las Brazas Steakhouse</strong> nos comprometemos a proteger la privacidad, confidencialidad y seguridad de la información personal que compartes al utilizar nuestro sitio web, portal de reservas y aplicación móvil.
        </p>
        <p class="text-xs text-slate-400 dark:text-slate-500 font-mono">
          Última actualización: Noviembre de 2026 • Versión 2.1
        </p>
      </div>

      <!-- Quick Summary Box -->
      <div class="p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/25 border border-emerald-200 dark:border-emerald-800/50 flex items-start gap-4">
        <div class="p-2 rounded-xl bg-emerald-600 text-white shrink-0 mt-0.5">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div class="text-xs text-slate-700 dark:text-slate-300 space-y-1">
          <p class="font-bold text-emerald-900 dark:text-emerald-200 text-sm">Compromiso Claro de Privacidad:</p>
          <ul class="list-disc pl-4 space-y-1 text-slate-600 dark:text-slate-400">
            <li>Nunca vendemos, alquilamos ni compartimos tu información personal con terceros publicitarios.</li>
            <li>Si inicias sesión con <strong>Google</strong>, solo recibimos tu nombre y correo verificado; jamás accedemos a tu contraseña ni a tu cuenta privada.</li>
            <li>Al cerrar sesión, toda caché local de tu sesión se elimina automáticamente de tu dispositivo.</li>
          </ul>
        </div>
      </div>

      <!-- Detailed Policy Sections -->
      <div class="space-y-6">
        <!-- Section 1 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
              <Database class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">1. Información que Recopilamos</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Datos mínimos necesarios para brindarte el servicio</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-3 leading-relaxed">
            <p>Recopilamos únicamente la información que proporcionas de forma voluntaria al registrarte, reservar una mesa, realizar un pedido o escribirnos desde el formulario de contacto:</p>
            <ul class="list-disc pl-5 space-y-1.5 text-xs sm:text-sm">
              <li><strong>Datos de identificación y contacto:</strong> Nombre completo, dirección de correo electrónico y número de teléfono/WhatsApp.</li>
              <li><strong>Datos de entrega y reservas:</strong> Direcciones registradas para delivery, preferencias de mesa, número de comensales y observaciones culinarias o alergias.</li>
              <li><strong>Historial de pedidos y comprobantes:</strong> Registro de órdenes, cupones canjeados y notas de venta para seguimiento y atención al cliente.</li>
            </ul>
          </div>
        </section>

        <!-- Section 2 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
              <UserCheck class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">2. Inicio de Sesión con Google (OAuth 2.0)</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Autenticación segura en 1 clic sin contraseñas externas</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-3 leading-relaxed">
            <p>
              Ofrecemos la opción <strong>"Continuar con Google"</strong> para facilitar el acceso rápido y seguro a tu cuenta de comensal. Al utilizar este método:
            </p>
            <ul class="list-disc pl-5 space-y-1.5 text-xs sm:text-sm">
              <li>Solicitamos exclusivamente los alcances básicos de identidad: <code>openid</code>, <code>email</code> y <code>profile</code> (nombre y dirección de correo verificada).</li>
              <li><strong>No solicitamos ni tenemos acceso</strong> a tu contraseña de Google, bandeja de Gmail, archivos de Google Drive, calendario ni lista de contactos.</li>
              <li>El correo proporcionado por Google se utiliza para crear o vincular tu cuenta, enviarte los documentos legales informativos (Términos, Condiciones y Política de Privacidad) y gestionar tus pedidos o reservas.</li>
            </ul>
          </div>
        </section>

        <!-- Section 3 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400">
              <Eye class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">3. Finalidad del Uso de tus Datos</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Para qué utilizamos tu información</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-2.5 leading-relaxed">
            <p>Tus datos personales se utilizan exclusivamente para:</p>
            <ul class="list-disc pl-5 space-y-1.5 text-xs sm:text-sm">
              <li>Gestionar, confirmar y notificar el estado de tus reservas de mesa y pedidos en línea o delivery.</li>
              <li>Generar códigos PIN de confirmación de entrega para garantizar que tu pedido llegue a tus manos de forma segura.</li>
              <li>Emitir comprobantes de consumo y responder consultas enviadas a través del formulario <strong>Contáctenos</strong>.</li>
              <li>Enviar comunicaciones transaccionales e informativas relacionadas directamente con tu cuenta.</li>
            </ul>
          </div>
        </section>

        <!-- Section 4 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
              <Lock class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">4. Seguridad, Cookies y Caché Offline (PWA)</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Resguardo técnico de sesiones y dispositivos compartidos</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-3 leading-relaxed">
            <p>
              Implementamos medidas técnicas y organizativas para proteger tu información contra accesos no autorizados, pérdida o alteración:
            </p>
            <ul class="list-disc pl-5 space-y-1.5 text-xs sm:text-sm">
              <li><strong>Cifrado en tránsito y contraseñas:</strong> Todas las conexiones operan bajo protocolo HTTPS (TLS/SSL) y las contraseñas se almacenan con hash irreversible.</li>
              <li><strong>Cookies esenciales:</strong> Utilizamos cookies estrictamente necesarias para mantener tu sesión activa y proteger los formularios contra ataques CSRF.</li>
              <li><strong>Aislamiento de Modo Offline (PWA):</strong> Las vistas de sesión autenticada se almacenan en una caché separada que se <strong>elimina automáticamente al cerrar sesión</strong> o cambiar de usuario, evitando la exposición de información entre cuentas.</li>
            </ul>
          </div>
        </section>

        <!-- Section 5 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
              <Trash2 class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">5. Conservación y Derechos del Titular (ARCO)</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Control total sobre tu cuenta y tus datos</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-3 leading-relaxed">
            <p>
              Conservamos tus datos mientras mantengas tu cuenta activa o durante el plazo legal necesario para registros administrativos y fiscales. Como titular de tus datos, tienes derecho a:
            </p>
            <ul class="list-disc pl-5 space-y-1.5 text-xs sm:text-sm">
              <li><strong>Acceso y Rectificación:</strong> Consultar y actualizar tus datos de contacto o direcciones desde tu Portal del Comensal.</li>
              <li><strong>Cancelación y Eliminación:</strong> Solicitar la baja definitiva de tu cuenta y la eliminación de tus datos personales escribiéndonos a través de nuestro canal oficial.</li>
              <li><strong>Revocación de Google:</strong> Puedes desvincular el acceso de Restaurante Las Brazas en cualquier momento desde la configuración de seguridad de tu cuenta de Google.</li>
            </ul>
          </div>
        </section>

        <!-- Section 6 -->
        <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400">
              <Mail class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">6. Contacto sobre Privacidad</h2>
              <span class="text-xs text-slate-500 dark:text-slate-400">Canal directo con administración</span>
            </div>
          </div>
          <div class="text-sm text-slate-600 dark:text-slate-300 space-y-2 leading-relaxed">
            <p>
              Si tienes preguntas sobre esta Política de Privacidad o deseas ejercer tus derechos de acceso, rectificación o eliminación de datos, contáctanos directamente en:
            </p>
            <p class="text-xs sm:text-sm">
              <strong>Restaurante Las Brazas Steakhouse</strong> — Av. Las Brazas #120, Zona Equipetrol Norte, Santa Cruz de la Sierra, Bolivia • Tel: +591 (3) 345-6789 • Formulario directo en
              <Link href="/#contacto" class="text-emerald-600 dark:text-emerald-400 font-semibold underline">Contáctenos</Link>.
            </p>
          </div>
        </section>
      </div>

      <!-- Bottom Call to Action -->
      <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-3xl p-8 text-white text-center space-y-4 shadow-xl shadow-emerald-600/20">
        <h3 class="text-2xl font-extrabold tracking-tight">Tu Privacidad Está Resguardada</h3>
        <p class="text-sm text-emerald-100 max-w-lg mx-auto">
          Consulta también nuestros Términos y Condiciones del Servicio o regresa al portal principal de Restaurante Las Brazas.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
          <Link href="/terms">
            <Button
              label="Ver Términos y Condiciones"
              icon="pi pi-file"
              class="!bg-white hover:!bg-emerald-50 !text-emerald-800 font-bold rounded-xl !border-none shadow-md px-5 h-11 text-xs"
            />
          </Link>
          <Link href="/register">
            <Button
              label="Ir al Registro"
              icon="pi pi-user-plus"
              class="!bg-emerald-900/40 hover:!bg-emerald-900/60 !text-white font-bold rounded-xl !border !border-white/30 px-5 h-11 text-xs"
            />
          </Link>
          <Link href="/">
            <Button
              label="Volver al Inicio"
              text
              class="!text-white hover:!text-emerald-100 font-semibold text-xs"
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
