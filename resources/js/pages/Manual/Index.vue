<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useTheme } from '../../composables/useTheme';
import NetworkStatusBadge from '../../components/NetworkStatusBadge.vue';
import {
  Flame,
  Search,
  Download,
  Printer,
  Sun,
  Moon,
  ArrowLeft,
  Globe,
  ShieldCheck,
  Users,
  ChefHat,
  UtensilsCrossed,
  CalendarCheck,
  Truck,
  Smartphone,
  Bot,
  BarChart3,
  CheckCircle2,
  AlertTriangle,
  HelpCircle,
  ChevronDown,
  ChevronRight,
  Menu,
  X,
  Layers,
  Clock,
  Sparkles,
  FileText,
  Monitor,
  Compass,
  ScanLine,
  ExternalLink
} from 'lucide-vue-next';

const props = defineProps({
  isSubdomain: { type: Boolean, default: false },
  mainSystemUrl: { type: String, default: '/' },
  manualSubdomainUrl: { type: String, default: '' },
  pdfUrl: { type: String, default: '/manual/pdf' },
  pdfDownloadUrl: { type: String, default: '/manual/pdf?download=1' },
  pdfAvailable: { type: Boolean, default: true },
  pdfSizeKb: { type: Number, default: 0 },
  pdfFilename: { type: String, default: 'Manual_Usuario_Completo_Las_Brazas.pdf' },
  appVersion: { type: String, default: '2.4.0' },
});

const { isDark, initTheme, toggleTheme } = useTheme();

const searchQuery = ref('');
const selectedRole = ref('todos');
const mobileNavOpen = ref(false);
const activeSection = ref('introduccion');
const activeVisualClientTab = ref('desktop');
const activeVisualOpsTab = ref('kds');
const openFaqIndex = ref(0);
const searchInputRef = ref(null);

const roles = [
  { id: 'todos', label: 'Todos los Perfiles', short: 'Todos' },
  { id: 'administrador', label: 'Administrador', short: 'Admin' },
  { id: 'chef', label: 'Chef / Cocina', short: 'Chef' },
  { id: 'mesero', label: 'Mesero / Salón', short: 'Mesero' },
  { id: 'recepcionista', label: 'Recepcionista', short: 'Recepción' },
  { id: 'repartidor', label: 'Repartidor', short: 'Delivery' },
  { id: 'cliente', label: 'Cliente / Comensal', short: 'Cliente' },
];

const tocGroups = [
  {
    title: 'PARTE 1 · Manual del Usuario',
    items: [
      { id: 'introduccion', label: '1. Bienvenida y Módulos', roles: ['todos', 'administrador', 'chef', 'mesero', 'recepcionista', 'repartidor', 'cliente'] },
      { id: 'roles-permisos', label: '2. Perfiles y Matriz de Acceso', roles: ['todos', 'administrador', 'chef', 'mesero', 'recepcionista', 'repartidor', 'cliente'] },
      { id: 'flujos-rol', label: '3. Guías Paso a Paso por Perfil', roles: ['todos', 'administrador', 'chef', 'mesero', 'recepcionista', 'repartidor', 'cliente'] },
      { id: 'estados', label: '4. Estados y Tiempos de Cocina', roles: ['todos', 'administrador', 'chef', 'mesero', 'recepcionista', 'repartidor', 'cliente'] },
    ],
  },
  {
    title: 'PARTE 2 · Guía Visual de Pantallas',
    items: [
      { id: 'visual-cliente', label: '5. Portal Web & App Móvil', roles: ['todos', 'cliente', 'administrador'] },
      { id: 'visual-pos', label: '6. POS Salón y Planos de Salón', roles: ['todos', 'mesero', 'recepcionista', 'administrador'] },
      { id: 'visual-kds-delivery', label: '7. Monitor Cocina & Entregas PIN', roles: ['todos', 'chef', 'repartidor', 'administrador'] },
      { id: 'visual-ia', label: '8. Chef Virtual IA & Reportes', roles: ['todos', 'administrador', 'cliente', 'chef'] },
    ],
  },
  {
    title: 'PARTE 3 · Acceso y Soporte',
    items: [
      { id: 'acceso-soporte', label: '9. Acceso Web y Compatibilidad', roles: ['todos', 'administrador'] },
      { id: 'soporte', label: '10. Preguntas Frecuentes (FAQ)', roles: ['todos', 'administrador', 'chef', 'mesero', 'recepcionista', 'repartidor', 'cliente'] },
    ],
  },
];

const roleCards = [
  {
    id: 'administrador',
    name: 'Administrador',
    icon: ShieldCheck,
    scope: 'Dirección general, personal, proveedores, reportes gerenciales y auditoría',
    routes: 'Usuarios, Proveedores, Reportes Gerenciales, Auditoría + todos los módulos',
    summary: 'Supervisa la operación integral del restaurante, administra accesos de personal, analiza métricas de ventas y descarga reportes ejecutivos.',
  },
  {
    id: 'chef',
    name: 'Chef',
    icon: ChefHat,
    scope: 'Monitor de Cocina (KDS), carta, recetas, insumos y pronósticos',
    routes: 'Monitor Cocina, Platos, Categorías, Recetas, Inventario y Pronósticos',
    summary: 'Controla comandas en tiempo real con semáforos de preparación, configura recetas e ingredientes y anticipa compras de insumos.',
  },
  {
    id: 'mesero',
    name: 'Mesero',
    icon: UtensilsCrossed,
    scope: 'Punto de Venta (POS Salón), comandas en mesa y consulta de reservas',
    routes: 'Punto de Venta (POS) y Reservas de Salón',
    summary: 'Registra pedidos en mesa, para llevar o domicilio, especifica términos de cocción y hace seguimiento hasta servir cada orden.',
  },
  {
    id: 'recepcionista',
    name: 'Recepcionista',
    icon: CalendarCheck,
    scope: 'Zonas, mesas, planos inteligentes de salón, directorio de clientes y reservas',
    routes: 'Zonas y Mesas, Planos Inteligentes, Clientes y Reservas',
    summary: 'Organiza el aforo del restaurante, detecta mesas automáticamente desde el plano del local y confirma llegadas de comensales.',
  },
  {
    id: 'repartidor',
    name: 'Repartidor',
    icon: Truck,
    scope: 'Entregas a domicilio en ruta y validación con PIN + Firma',
    routes: 'Entregas Delivery (Disponibles, En Camino, Entregadas)',
    summary: 'Toma pedidos listos desde cocina, abre navegación GPS y confirma la entrega al cliente solicitando PIN de 4 dígitos y firma táctil.',
  },
  {
    id: 'cliente',
    name: 'Cliente',
    icon: Smartphone,
    scope: 'Portal de Comensal Web y Aplicación Móvil Oficial (Android / iOS)',
    routes: 'Carta, Carrito, Mis Pedidos, Promociones, Reservas, Chef Virtual y Perfil',
    summary: 'Explora cortes a la parrilla, canjea cupones, sigue su pedido en tiempo real, agenda mesas y conversa con el Chef Virtual.',
  },
];

const accessMatrix = [
  { module: 'Carta Pública, Reserva Online y Registro', admin: true, chef: true, mesero: true, recep: true, rep: true, cli: true },
  { module: 'Portal del Comensal (7 secciones) y App Móvil', admin: true, chef: false, mesero: false, recep: false, rep: false, cli: true },
  { module: 'Punto de Venta (POS Salón y Comandas)', admin: true, chef: false, mesero: true, recep: false, rep: false, cli: false },
  { module: 'Monitor de Cocina en Tiempo Real (KDS)', admin: true, chef: true, mesero: false, recep: false, rep: false, cli: false },
  { module: 'Platos, Categorías, Recetas, Insumos y Pronósticos', admin: true, chef: true, mesero: false, recep: false, rep: false, cli: false },
  { module: 'Zonas, Mesas, Clientes y Planos Inteligentes', admin: true, chef: false, mesero: false, recep: true, rep: false, cli: false },
  { module: 'Gestión Centralizada de Reservas', admin: true, chef: false, mesero: true, recep: true, rep: false, cli: false },
  { module: 'Entregas a Domicilio con PIN + Firma Digital', admin: true, chef: false, mesero: false, recep: false, rep: true, cli: false },
  { module: 'Usuarios, Proveedores, Reportes BI & Auditoría', admin: true, chef: false, mesero: false, recep: false, rep: false, cli: false },
  { module: 'Asistente Virtual Chef IA (Voz y Texto)', admin: true, chef: true, mesero: true, recep: true, rep: true, cli: true },
];

const roleWorkflows = [
  {
    id: 'cliente',
    roleName: 'Cliente / Comensal',
    badge: 'Portal Web + App Móvil',
    moduleUrl: 'Portal de Comensal (/dashboard) y App iOS/Android',
    steps: [
      { title: 'Registro y Activación de Cuenta', detail: 'En la pantalla de registro ingresa nombre, correo, teléfono y contraseña. Recibirás los Términos del Servicio y el enlace de confirmación por correo para activar tu acceso.' },
      { title: 'Exploración de Carta y Personalización', detail: 'Desde la pestaña Carta en la Web o en la App Móvil, filtra categorías, elige tus cortes favoritos e indica término de cocción o notas especiales.' },
      { title: 'Carrito, Cupones y Modalidad de Entrega', detail: 'Elige entre Entrega a Domicilio, Para Llevar o En Local; selecciona tu dirección guardada, aplica cupones promocionales y registra tus datos de facturación.' },
      { title: 'Seguimiento en Vivo y Reservas', detail: 'Observa el avance de tu orden paso a paso y visualiza tu PIN de 4 dígitos. En Reservas agenda tu mesa respetando ventanas de 2 horas y 15 min de tolerancia.' },
    ],
    result: 'Tu orden llega al instante a parrilla y recepción, con seguimiento en tiempo real y entrega protegida mediante PIN personal.',
  },
  {
    id: 'recepcionista',
    roleName: 'Recepcionista',
    badge: 'Planos & Reservas',
    moduleUrl: 'Menú lateral: Zonas y Mesas · Planos Inteligentes · Reservas',
    steps: [
      { title: 'Configuración de Áreas y Mesas', detail: 'En Zonas y Mesas organiza sectores (Salón Principal, Terraza, VIP) con la capacidad de comensales por cada mesa.' },
      { title: 'Distribución Automática desde Planos', detail: 'En Planos Inteligentes sube una imagen del plano del restaurante (o usa el plano modelo), pulsa Detectar Mesas Automáticamente, optimiza y guarda en tu zona.' },
      { title: 'Confirmación de Reservas Entrantes', detail: 'Revisa las solicitudes con estado Pendiente en Reservas, verifica disponibilidad en la franja horaria de ±2 horas y cámbialas a Confirmada.' },
      { title: 'Recepción en Puerta (Check-In)', detail: 'Cuando llegan los invitados dentro de los 15 minutos de tolerancia, marca la reserva como En Mesa para habilitar la atención de salón.' },
    ],
    result: 'El mapa de mesas refleja en vivo cada espacio libre, reservado u ocupado, evitando empalmes de reservas en horarios pico.',
  },
  {
    id: 'mesero',
    roleName: 'Mesero',
    badge: 'POS Salón',
    moduleUrl: 'Menú lateral: Punto de Venta (POS)',
    steps: [
      { title: 'Selección de Mesa o Modalidad', detail: 'Ingresa a Punto de Venta y elige una mesa libre para atención en salón, o selecciona Para Llevar / Delivery si el pedido es telefónico.' },
      { title: 'Armado de Comanda e Indicaciones', detail: 'Toca cada plato o bebida para sumarlo al ticket y escribe observaciones claras de cocción (ejemplo: "Término 3/4, guarnición aparte").' },
      { title: 'Asignación de Cliente y Envío a Cocina', detail: 'Selecciona cliente registrado o consumidor final y presiona Confirmar Comanda para enviarlo de inmediato a parrilla.' },
      { title: 'Entrega en Mesa', detail: 'Cuando cocina marque la orden como Listo, lleva los platos a la mesa y actualiza el estado del pedido a Servido.' },
    ],
    result: 'Cada pedido queda registrado sin papeles extraviados, vinculado a su mesa correspondiente y listo para cobro en caja.',
  },
  {
    id: 'chef',
    roleName: 'Chef / Cocina',
    badge: 'Monitor KDS & Recetas',
    moduleUrl: 'Menú lateral: Monitor Cocina · Recetas · Pronósticos',
    steps: [
      { title: 'Supervisión del Tablero de Cocina', detail: 'El Monitor KDS se actualiza automáticamente cada 10 segundos y permite filtrar comandas en Todos, Delivery, Pendiente, En Cocina y Listo.' },
      { title: 'Control de Tiempos por Semáforo', detail: 'Cada tarjeta muestra un cronómetro activo: Verde (< 12 min) dentro de tiempo, Ámbar (12–19 min) prioridad media y Rojo (≥ 20 min) despacho urgente.' },
      { title: 'Avance de Preparación y Despacho', detail: 'Marca las órdenes o platos al iniciar en parrilla (En Cocina) y pásalos a Listo al completar el emplatado en barra.' },
      { title: 'Recetas, Stock y Pronóstico de Demanda', detail: 'Configura la fórmula de ingredientes de cada plato en Recetas; monitorea existencias en Inventario y estima compras mensuales en Pronósticos.' },
    ],
    result: 'Cocina coordinada cronológicamente con descuento automático de insumos cada vez que se vende un plato.',
  },
  {
    id: 'repartidor',
    roleName: 'Repartidor',
    badge: 'Delivery Seguro',
    moduleUrl: 'Menú lateral: Entregas Delivery',
    steps: [
      { title: 'Tomar Pedido Disponible', detail: 'En la pestaña Disponibles revisa dirección, teléfono del cliente y total; presiona Tomar Pedido para asignártelo.' },
      { title: 'Salida desde Cocina (En Camino)', detail: 'Al retirar el pedido empaquetado de barra, marca Recogido de Cocina para moverlo a tu lista activa y abrir la ruta en Google Maps o Waze.' },
      { title: 'Validación Presencial con PIN + Firma', detail: 'Al llegar al domicilio, abre Confirmar Entrega, solicita al cliente su PIN de 4 dígitos visible en su app/portal e invítalo a firmar en pantalla.' },
      { title: 'Cierre de Entrega', detail: 'Una vez validados el PIN y la firma manuscrita, el sistema finaliza la orden y la mueve al historial Entregadas.' },
    ],
    result: 'Respaldo digital completo de cada entrega con confirmación presencial del cliente mediante PIN y firma.',
  },
  {
    id: 'administrador',
    roleName: 'Administrador',
    badge: 'Gerencia & Reportes',
    moduleUrl: 'Menú lateral: Reportes · Usuarios · Auditoría',
    steps: [
      { title: 'Gestión de Personal y Proveedores', detail: 'En Usuarios crea cuentas de colaboradores asignando su perfil exacto; en Proveedores mantén actualizado el directorio de abastecimiento.' },
      { title: 'Reportes Financieros y Exportación', detail: 'En Reportes filtra cualquier rango de fechas para analizar ingresos, ticket promedio y platos líderes, y exporta a PDF, Excel o CSV.' },
      { title: 'Consultas Inteligentes por Voz y Texto', detail: 'Utiliza la barra superior en Reportes (o el botón de micrófono) para pedir gráficas de ventas en lenguaje natural al instante.' },
      { title: 'Bitácora de Auditoría', detail: 'Supervisa movimientos, modificaciones y anulaciones críticas del restaurante desde la sección Auditoría.' },
    ],
    result: 'Visibilidad financiera, operativa y de seguridad centralizada para tomar decisiones rápidas con datos reales.',
  },
];

const statusLegends = [
  { group: 'Estados de Pedidos', items: [
    { code: 'Pendiente', label: 'Pendiente', style: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30', desc: 'Orden recién registrada; espera ingreso a parrilla.' },
    { code: 'En Cocina', label: 'En Cocina', style: 'bg-orange-500/15 text-orange-700 dark:text-orange-300 border-orange-500/30', desc: 'En preparación activa en el Monitor de Cocina.' },
    { code: 'Listo', label: 'Listo', style: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30', desc: 'Emplatado en barra para entrega de mesero o repartidor.' },
    { code: 'Servido', label: 'Servido', style: 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30', desc: 'Entregado satisfactoriamente al comensal en mesa.' },
    { code: 'Entregado', label: 'Entregado', style: 'bg-teal-500/15 text-teal-700 dark:text-teal-300 border-teal-500/30', desc: 'Finalizado en domicilio tras validar PIN de 4 dígitos y firma.' },
    { code: 'Cancelado', label: 'Cancelado', style: 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30', desc: 'Orden anulada y registrada en historial.' },
  ]},
  { group: 'Semáforo de Cocina', items: [
    { code: '< 12 min', label: 'Normal', style: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30', desc: 'Tiempo óptimo de cocción a las brasas dentro del estándar.' },
    { code: '12–19 min', label: 'Atención', style: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30', desc: 'Prioridad media; revisar punto de cocción y emplatado.' },
    { code: '≥ 20 min', label: 'Urgente', style: 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30', desc: 'Tiempo excedido; requiere despacho inmediato.' },
  ]},
  { group: 'Ciclo de Reservas', items: [
    { code: 'Solicitud', label: 'Pendiente', style: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30', desc: 'Solicitud ingresada desde la web o App Móvil.' },
    { code: 'Bloqueada', label: 'Confirmada', style: 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30', desc: 'Mesa reservada en franja ±2h con tolerancia de 15 minutos.' },
    { code: 'En Salón', label: 'En Mesa', style: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30', desc: 'Comensales presentes y ubicados por recepción.' },
    { code: 'Cierre', label: 'Completada', style: 'bg-slate-500/15 text-slate-700 dark:text-slate-300 border-slate-500/30', desc: 'Visita finalizada correctamente.' },
  ]},
];

const faqs = [
  {
    q: 'Me registré como cliente pero no puedo iniciar sesión ("cuenta sin verificar"). ¿Qué hago?',
    a: 'Al crear tu cuenta enviamos un correo electrónico con el enlace de activación. Abre tu bandeja de entrada o carpeta de spam, haz clic en verificar correo y podrás ingresar de inmediato. Si necesitas ayuda en sucursal, solicita apoyo al Administrador.',
  },
  {
    q: 'Al intentar entrar a una sección aparece "Acceso Denegado".',
    a: 'Cada miembro del equipo visualiza únicamente las herramientas de su perfil operativo (por ejemplo, Cocina para Chef, POS para Mesero, Entregas para Repartidor). Si necesitas permisos adicionales, el Administrador puede actualizar tu perfil desde la sección Usuarios.',
  },
  {
    q: 'El Repartidor no logra finalizar la entrega de un pedido.',
    a: 'Como garantía antifraude, la plataforma requiere validar presencialmente tanto el código PIN de 4 dígitos que visualiza el cliente en su teléfono como su firma manuscrita en la pantalla antes de habilitar el cierre.',
  },
  {
    q: 'El Monitor de Cocina no muestra todavía un pedido recién creado.',
    a: 'La pantalla se actualiza automáticamente cada 10 segundos (y cuenta con botón manual de sincronización). Verifica también que no esté activado un filtro específico de estado (por ejemplo "Listo") que oculte pedidos nuevos.',
  },
  {
    q: 'No aparecen mesas disponibles al intentar reservar en un horario concreto.',
    a: 'Para garantizar tu espacio sin esperas, cada reserva confirmada reserva la mesa por una ventana de 2 horas. Prueba seleccionar otro sector (Salón, Terraza, VIP) o ajustar la hora unos minutos, o pídele ayuda al Chef Virtual IA.',
  },
  {
    q: '¿Cómo consulto este manual desde el celular o sin conexión a internet?',
    a: 'Puedes abrir el subdominio oficial del manual desde cualquier navegador móvil o descargar el archivo Manual_Usuario_Completo_Las_Brazas.pdf con el botón naranja superior para tenerlo guardado siempre disponible offline.',
  },
];

const normalizedQuery = computed(() => searchQuery.value.trim().toLowerCase());

const matchesQuery = (text) => {
  if (!normalizedQuery.value) return true;
  return text.toLowerCase().includes(normalizedQuery.value);
};

const filteredRoleCards = computed(() => {
  return roleCards.filter((card) => {
    const roleMatch = selectedRole.value === 'todos' || card.id === selectedRole.value;
    const textMatch = matchesQuery(`${card.name} ${card.scope} ${card.routes} ${card.summary}`);
    return roleMatch && textMatch;
  });
});

const filteredWorkflows = computed(() => {
  return roleWorkflows.filter((flow) => {
    const roleMatch = selectedRole.value === 'todos' || flow.id === selectedRole.value;
    const fullText = `${flow.roleName} ${flow.badge} ${flow.moduleUrl} ${flow.result} ${flow.steps.map(s => s.title + ' ' + s.detail).join(' ')}`;
    return roleMatch && matchesQuery(fullText);
  });
});

const filteredFaqs = computed(() => {
  return faqs.filter((item) => matchesQuery(`${item.q} ${item.a}`));
});

const isSectionRoleHighlighted = (itemRoles) => {
  return selectedRole.value === 'todos' || itemRoles.includes(selectedRole.value);
};

const scrollToSection = (id) => {
  activeSection.value = id;
  mobileNavOpen.value = false;
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

const triggerPrint = () => {
  window.print();
};

const handleKeydown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    searchInputRef.value?.focus();
  } else if (e.key === '/' && document.activeElement?.tagName !== 'INPUT' && document.activeElement?.tagName !== 'TEXTAREA') {
    e.preventDefault();
    searchInputRef.value?.focus();
  }
};

onMounted(() => {
  initTheme();
  window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <Head title="Manual de Usuario — Restaurante Las Brazas" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200 selection:bg-orange-500/20 selection:text-orange-600">
    <!-- Top Fixed Bar (no-print) -->
    <header class="no-print sticky top-0 z-50 border-b border-slate-200/80 dark:border-slate-800/80 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
        <!-- Brand + Mobile Toggle -->
        <div class="flex items-center gap-3 min-w-0">
          <button
            type="button"
            @click="mobileNavOpen = !mobileNavOpen"
            class="lg:hidden p-2 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900"
            aria-label="Abrir índice del manual"
          >
            <Menu v-if="!mobileNavOpen" class="w-5 h-5" />
            <X v-else class="w-5 h-5" />
          </button>

          <a href="#introduccion" @click.prevent="scrollToSection('introduccion')" class="flex items-center gap-2.5 min-w-0">
            <img
              :src="isDark ? '/images/logo-dark.jpg' : '/images/logo-light.jpg'"
              alt="Las Brazas Logo"
              class="w-9 h-9 rounded-xl object-cover border border-orange-500/30 shadow-sm shrink-0"
            />
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-sm sm:text-base tracking-tight truncate text-slate-900 dark:text-white">
                  Las Brazas <span class="text-orange-600 dark:text-orange-400">Ayuda</span>
                </span>
                <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-500/25">
                  v{{ appVersion }}
                </span>
                <span v-if="isSubdomain" class="hidden md:inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25">
                  <Globe class="w-3 h-3" /> Centro de Ayuda Oficial
                </span>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate hidden sm:block">
                Manual Oficial de Usuario · Plataforma POS, Cocina, Delivery y App Móvil
              </p>
            </div>
          </a>
        </div>

        <!-- Search Input -->
        <div class="flex-1 max-w-md hidden md:block">
          <div class="relative">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              ref="searchInputRef"
              v-model="searchQuery"
              type="text"
              placeholder="Buscar perfil, flujo, pantalla, PIN, reservas... (Ctrl+K)"
              class="w-full pl-9 pr-14 py-2 text-xs rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/40 focus:border-orange-500"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click="searchQuery = ''"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300"
            >
              Limpiar
            </button>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 shrink-0">
          <NetworkStatusBadge />

          <button
            type="button"
            @click="triggerPrint"
            class="hidden xl:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          >
            <Printer class="w-3.5 h-3.5" />
            Imprimir
          </button>

          <a
            v-if="pdfAvailable"
            :href="pdfDownloadUrl"
            download="Manual_Usuario_Completo_Las_Brazas.pdf"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-orange-600 to-amber-500 text-white shadow-md shadow-orange-600/20 hover:brightness-105 transition-all"
          >
            <Download class="w-3.5 h-3.5" />
            <span>Descargar PDF</span>
            <span v-if="pdfSizeKb > 0" class="hidden sm:inline text-[10px] opacity-85 font-normal">({{ pdfSizeKb }} KB)</span>
          </a>

          <button
            type="button"
            @click="toggleTheme"
            class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:text-orange-600 dark:hover:text-orange-400 transition-colors"
            :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
          >
            <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
            <Moon v-else class="w-4 h-4" />
          </button>

          <a
            :href="mainSystemUrl"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-orange-500/40 hover:text-orange-600 dark:hover:text-orange-400 transition-colors"
          >
            <ArrowLeft class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Sistema Principal</span>
          </a>
        </div>
      </div>

      <!-- Mobile search bar -->
      <div class="md:hidden px-4 pb-3">
        <div class="relative">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar en el manual (perfiles, POS, KDS, PIN, FAQ)..."
            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
          />
        </div>
      </div>
    </header>

    <!-- Layout Container -->
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 flex flex-col lg:flex-row gap-8 py-6 sm:py-8">
      <!-- Left Sticky Navigation Sidebar -->
      <aside
        :class="[
          'no-print lg:w-72 shrink-0',
          mobileNavOpen ? 'block fixed inset-0 top-16 z-40 bg-white dark:bg-slate-950 p-5 overflow-y-auto' : 'hidden lg:block'
        ]"
      >
        <div class="lg:sticky lg:top-24 space-y-5 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto pr-1">
          <!-- Role Quick Filter -->
          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/70 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2.5">
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <Users class="w-3.5 h-3.5 text-orange-500" /> Filtrar por Perfil
              </span>
              <button
                v-if="selectedRole !== 'todos'"
                type="button"
                @click="selectedRole = 'todos'"
                class="text-[10px] font-semibold text-orange-600 dark:text-orange-400 hover:underline"
              >
                Ver todos
              </button>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="r in roles"
                :key="r.id"
                type="button"
                @click="selectedRole = r.id"
                :class="[
                  'px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all border',
                  selectedRole === r.id
                    ? 'bg-orange-600 text-white border-orange-600 shadow-sm'
                    : 'bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:border-orange-500/40'
                ]"
              >
                {{ r.short }}
              </button>
            </div>
          </div>

          <!-- Table of Contents -->
          <nav class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/70 p-4 shadow-sm space-y-4">
            <div v-for="group in tocGroups" :key="group.title" class="space-y-1.5">
              <div class="text-[10px] font-extrabold uppercase tracking-wider text-orange-600 dark:text-orange-400 px-2">
                {{ group.title }}
              </div>
              <button
                v-for="item in group.items"
                :key="item.id"
                type="button"
                @click="scrollToSection(item.id)"
                :class="[
                  'w-full text-left px-2.5 py-2 rounded-xl text-xs font-medium flex items-center justify-between transition-all',
                  activeSection === item.id
                    ? 'bg-orange-500/10 text-orange-700 dark:text-orange-300 font-bold border border-orange-500/25'
                    : isSectionRoleHighlighted(item.roles)
                      ? 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/70'
                      : 'text-slate-400 dark:text-slate-600 opacity-50 hover:opacity-100'
                ]"
              >
                <span class="truncate">{{ item.label }}</span>
                <ChevronRight class="w-3.5 h-3.5 shrink-0 opacity-60" />
              </button>
            </div>
          </nav>

          <!-- PDF Direct Card -->
          <div class="rounded-2xl border border-orange-500/25 bg-gradient-to-br from-orange-500/10 via-amber-500/5 to-transparent p-4 space-y-2.5">
            <div class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-white">
              <FileText class="w-4 h-4 text-orange-600 dark:text-orange-400" />
              Manual Completo PDF
            </div>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
              Ideal para capacitación del equipo o consulta sin conexión. Incluye guía por perfiles y referencias visuales paso a paso.
            </p>
            <div class="flex flex-col gap-1.5 pt-1">
              <a
                :href="pdfUrl"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl text-xs font-bold bg-orange-600 hover:bg-orange-500 text-white shadow-sm transition-colors"
              >
                <ExternalLink class="w-3.5 h-3.5" /> Abrir PDF en Navegador
              </a>
              <a
                :href="pdfDownloadUrl"
                download="Manual_Usuario_Completo_Las_Brazas.pdf"
                class="inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl text-xs font-semibold bg-white dark:bg-slate-900 border border-orange-500/30 text-orange-700 dark:text-orange-300 hover:bg-orange-500/10 transition-colors"
              >
                <Download class="w-3.5 h-3.5" /> Descargar Archivo PDF
              </a>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Documentation Canvas -->
      <main class="flex-1 min-w-0 space-y-12 pb-20">
        <!-- Hero Banner -->
        <section
          id="introduccion"
          class="scroll-mt-24 rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm"
        >
          <div class="relative bg-gradient-to-br from-orange-50 via-amber-50/60 to-white dark:from-slate-950 dark:via-slate-900 dark:to-orange-950 text-slate-900 dark:text-white p-7 sm:p-10 border-b border-orange-200/70 dark:border-transparent">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.14),transparent_55%)] dark:bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.22),transparent_55%)] pointer-events-none"></div>
            <div class="relative z-10 max-w-3xl space-y-4">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/10 dark:bg-orange-500/20 border border-orange-500/30 dark:border-orange-400/30 text-orange-700 dark:text-orange-300 text-xs font-bold">
                <Flame class="w-3.5 h-3.5" />
                RESTAURANTE LAS BRAZAS · GUÍA OFICIAL DEL SISTEMA v{{ appVersion }}
              </div>
              <h1 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight">
                Manual de Usuario Web e Interactivo
              </h1>
              <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Centro de ayuda oficial del ecosistema gastronómico <strong>Las Brazas Steakhouse</strong>: cubre los 6 perfiles operativos, Punto de Venta POS, Monitor de Cocina en tiempo real, Entregas con confirmación PIN + Firma, Planos Inteligentes de Salón, Chef Virtual IA y Aplicación Móvil.
              </p>

              <div class="flex flex-wrap gap-3 pt-2 no-print">
                <a
                  v-if="pdfAvailable"
                  :href="pdfDownloadUrl"
                  download="Manual_Usuario_Completo_Las_Brazas.pdf"
                  class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-orange-600 hover:bg-orange-500 text-white shadow-lg shadow-orange-600/25 transition-all"
                >
                  <Download class="w-4 h-4" />
                  Descargar Manual Completo (.PDF)
                </a>
                <button
                  type="button"
                  @click="scrollToSection('roles-permisos')"
                  class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 dark:bg-white/10 dark:hover:bg-white/20 dark:text-white dark:border-white/15 transition-all"
                >
                  <Compass class="w-4 h-4" />
                  Explorar por Perfil (6)
                </button>
              </div>
            </div>
          </div>

          <!-- Architecture Quick Pillars -->
          <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 p-6 sm:p-8 bg-white dark:bg-slate-900">
            <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-1.5">
              <div class="flex items-center gap-2 text-xs font-bold text-orange-600 dark:text-orange-400">
                <UtensilsCrossed class="w-4 h-4" /> POS + Salón + Cocina
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Flujo continuo desde la toma del pedido en mesa o app hasta el monitor de parrilla con actualización cada 10 segundos.
              </p>
            </div>
            <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-1.5">
              <div class="flex items-center gap-2 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                <Truck class="w-4 h-4" /> Delivery con Doble Seguridad
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Confirmación en última milla exigiendo el PIN de 4 dígitos del cliente y su firma manuscrita en pantalla.
              </p>
            </div>
            <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-1.5">
              <div class="flex items-center gap-2 text-xs font-bold text-sky-600 dark:text-sky-400">
                <ScanLine class="w-4 h-4" /> Planos Inteligentes & Stock
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Detección automática de mesas sobre planos del restaurante y proyección inteligente de consumo de insumos.
              </p>
            </div>
            <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-1.5">
              <div class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                <Bot class="w-4 h-4" /> Chef Virtual & Reportes IA
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Asistente por voz y texto para comensales (carta, cupones, reservas) y consultas instantáneas de ventas para gerencia.
              </p>
            </div>
          </div>
        </section>

        <!-- PARTE 1 DIVIDER BANNER -->
        <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-200 dark:border-slate-700">
          <span class="px-2.5 py-1 rounded-lg bg-orange-600 text-white text-[11px] font-black uppercase tracking-wider">Parte 1</span>
          <span class="text-sm font-bold">Manual Operativo — Perfiles, Permisos, Guías de Uso y Estados</span>
        </div>

        <!-- SECTION 2: ROLES & ACCESS MATRIX -->
        <section id="roles-permisos" class="scroll-mt-24 space-y-6">
          <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <ShieldCheck class="w-6 h-6 text-orange-500" />
              2. Perfiles de Usuario y Matriz de Acceso Rápido
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              Cada cuenta en Las Brazas dispone de permisos específicos de seguridad según su función dentro del restaurante.
            </p>
          </div>

          <!-- Role Cards Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <article
              v-for="card in filteredRoleCards"
              :key="card.id"
              class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm hover:border-orange-500/40 transition-colors flex flex-col justify-between gap-3"
            >
              <div class="space-y-2.5">
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center">
                      <component :is="card.icon" class="w-5 h-5" />
                    </div>
                    <h3 class="font-bold text-base text-slate-900 dark:text-white">{{ card.name }}</h3>
                  </div>
                  <button
                    type="button"
                    @click="selectedRole = card.id; scrollToSection('flujos-rol')"
                    class="no-print text-[11px] font-bold text-orange-600 dark:text-orange-400 hover:underline"
                  >
                    Ver guía →
                  </button>
                </div>
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ card.scope }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ card.summary }}</p>
              </div>
              <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400 block mb-1">Secciones habilitadas</span>
                <span class="text-[11px] font-medium text-orange-700 dark:text-orange-300">{{ card.routes }}</span>
              </div>
            </article>
          </div>

          <!-- Access Matrix Table -->
          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/60">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                Matriz de Acceso por Módulo y Perfil
              </span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 text-slate-600 dark:text-slate-300">
                    <th class="py-3 px-4 font-bold">Módulo o Herramienta</th>
                    <th class="py-3 px-3 text-center font-bold">Admin</th>
                    <th class="py-3 px-3 text-center font-bold">Chef</th>
                    <th class="py-3 px-3 text-center font-bold">Mesero</th>
                    <th class="py-3 px-3 text-center font-bold">Recepción</th>
                    <th class="py-3 px-3 text-center font-bold">Repartidor</th>
                    <th class="py-3 px-3 text-center font-bold">Cliente</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                  <tr v-for="row in accessMatrix" :key="row.module" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                    <td class="py-2.5 px-4 font-medium text-slate-800 dark:text-slate-200">{{ row.module }}</td>
                    <td class="py-2.5 px-3 text-center font-bold" :class="row.admin ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-300 dark:text-slate-700'">{{ row.admin ? '✓' : '—' }}</td>
                    <td class="py-2.5 px-3 text-center font-bold" :class="row.chef ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-300 dark:text-slate-700'">{{ row.chef ? '✓' : '—' }}</td>
                    <td class="py-2.5 px-3 text-center font-bold" :class="row.mesero ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-300 dark:text-slate-700'">{{ row.mesero ? '✓' : '—' }}</td>
                    <td class="py-2.5 px-3 text-center font-bold" :class="row.recep ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-300 dark:text-slate-700'">{{ row.recep ? '✓' : '—' }}</td>
                    <td class="py-2.5 px-3 text-center font-bold" :class="row.rep ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-300 dark:text-slate-700'">{{ row.rep ? '✓' : '—' }}</td>
                    <td class="py-2.5 px-3 text-center font-bold" :class="row.cli ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-300 dark:text-slate-700'">{{ row.cli ? '✓' : '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION 3: STEP-BY-STEP WORKFLOWS BY ROLE -->
        <section id="flujos-rol" class="scroll-mt-24 space-y-6">
          <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <Layers class="w-6 h-6 text-orange-500" />
              3. Guías de Uso Paso a Paso por Perfil
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              Secuencia recomendada para operar cada área del restaurante de forma ágil y sin errores.
            </p>
          </div>

          <div v-if="filteredWorkflows.length === 0" class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500">
            No hay guías que coincidan con el filtro o búsqueda actual.
            <button type="button" @click="searchQuery = ''; selectedRole = 'todos'" class="ml-2 text-orange-600 font-bold underline">Restablecer filtros</button>
          </div>

          <div class="space-y-6">
            <div
              v-for="flow in filteredWorkflows"
              :key="flow.id"
              class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-5"
            >
              <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="space-y-1">
                  <div class="flex items-center gap-2.5">
                    <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ flow.roleName }}</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-orange-500/10 text-orange-700 dark:text-orange-300 border border-orange-500/25">
                      {{ flow.badge }}
                    </span>
                  </div>
                  <span class="text-xs text-slate-500 dark:text-slate-400">{{ flow.moduleUrl }}</span>
                </div>
              </div>

              <!-- Numbered Steps -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                  v-for="(step, idx) in flow.steps"
                  :key="step.title"
                  class="flex gap-3.5 p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50"
                >
                  <div class="w-7 h-7 rounded-lg bg-slate-900 dark:bg-orange-600 text-white font-black text-xs flex items-center justify-center shrink-0 mt-0.5">
                    {{ idx + 1 }}
                  </div>
                  <div class="space-y-1">
                    <h4 class="font-bold text-sm text-slate-900 dark:text-white">{{ step.title }}</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ step.detail }}</p>
                  </div>
                </div>
              </div>

              <!-- Green Outcome Box -->
              <div class="flex items-start gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-900 dark:text-emerald-200">
                <CheckCircle2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                <div class="text-xs leading-relaxed">
                  <strong class="font-extrabold uppercase tracking-wide block mb-0.5 text-emerald-700 dark:text-emerald-300">■ Resultado Esperado</strong>
                  {{ flow.result }}
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 4: SYSTEM STATES & TIMERS -->
        <section id="estados" class="scroll-mt-24 space-y-6">
          <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <Clock class="w-6 h-6 text-orange-500" />
              4. Estados del Sistema y Semáforo de Cocina
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              Significado de los colores e indicadores visibles en las pantallas web y la aplicación móvil.
            </p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div
              v-for="group in statusLegends"
              :key="group.group"
              class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm space-y-4"
            >
              <h3 class="font-bold text-sm text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-2.5">
                {{ group.group }}
              </h3>
              <div class="space-y-3">
                <div v-for="st in group.items" :key="st.code" class="space-y-1">
                  <div class="flex items-center justify-between gap-2">
                    <span :class="['px-2.5 py-0.5 rounded-full text-[11px] font-bold border', st.style]">
                      {{ st.label }}
                    </span>
                    <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ st.code }}</span>
                  </div>
                  <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ st.desc }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- PARTE 2 DIVIDER BANNER -->
        <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-gradient-to-r from-orange-600 to-amber-500 text-white shadow-sm">
          <span class="px-2.5 py-1 rounded-lg bg-slate-950/30 text-[11px] font-black uppercase tracking-wider">Parte 2</span>
          <span class="text-sm font-bold">Guía Visual de Uso — Pantallas Comentadas y Referencia de Controles</span>
        </div>

        <!-- SECTION 5: VISUAL CLIENT PORTAL & MOBILE -->
        <section id="visual-cliente" class="scroll-mt-24 space-y-5">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                <Monitor class="w-6 h-6 text-orange-500" />
                5. Guía Visual: Portal del Comensal Web y App Móvil
              </h2>
              <p class="text-sm text-slate-600 dark:text-slate-400">Alterna entre la vista Web de escritorio y la App Móvil oficial.</p>
            </div>
            <div class="no-print inline-flex rounded-xl p-1 bg-slate-200/80 dark:bg-slate-800">
              <button
                type="button"
                @click="activeVisualClientTab = 'desktop'"
                :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', activeVisualClientTab === 'desktop' ? 'bg-white dark:bg-slate-950 text-orange-600 dark:text-orange-400 shadow-sm' : 'text-slate-600 dark:text-slate-300']"
              >
                Portal Web Comensal
              </button>
              <button
                type="button"
                @click="activeVisualClientTab = 'mobile'"
                :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', activeVisualClientTab === 'mobile' ? 'bg-white dark:bg-slate-950 text-orange-600 dark:text-orange-400 shadow-sm' : 'text-slate-600 dark:text-slate-300']"
              >
                App Móvil (Android / iOS)
              </button>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
            <!-- Mock Browser Frame -->
            <div class="px-4 py-2.5 bg-slate-100 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
              <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
              </div>
              <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                {{ activeVisualClientTab === 'desktop' ? 'Portal del Comensal · /dashboard' : 'Aplicación Móvil Las Brazas (iOS & Android)' }}
              </span>
              <span class="text-[10px] font-bold text-orange-600 dark:text-orange-400">Guía Visual</span>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
              <!-- Visual Diagram -->
              <div class="lg:col-span-7">
                <div v-if="activeVisualClientTab === 'desktop'" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-100/80 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-4 space-y-4">
                  <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2 text-xs font-bold">
                      <span class="w-5 h-5 rounded-full bg-orange-600 text-white text-[10px] inline-flex items-center justify-center">①</span>
                      <span>Pestañas del Cliente:</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5 text-[10px] font-semibold">
                      <span class="px-2 py-0.5 rounded bg-orange-600 text-white">Carta</span>
                      <span class="px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent">Carrito</span>
                      <span class="px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent">Pedidos</span>
                      <span class="px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent">Promos</span>
                      <span class="px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent">Reservas</span>
                      <span class="px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent">Chef IA</span>
                    </div>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2 shadow-2xs">
                      <div class="flex justify-between items-center text-xs font-bold">
                        <span>Bife de Chorizo 400g</span>
                        <span class="w-5 h-5 rounded-full bg-orange-600 text-white text-[10px] inline-flex items-center justify-center">②</span>
                      </div>
                      <p class="text-[10px] text-slate-500 dark:text-slate-400">Corte premium a las brazas + guarnición</p>
                      <div class="flex items-center justify-between pt-1">
                        <span class="text-xs font-black text-orange-600 dark:text-amber-400">Bs. 115</span>
                        <span class="px-2 py-0.5 rounded bg-orange-600 text-white text-[10px] font-bold">+ Agregar</span>
                      </div>
                    </div>
                    <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2 shadow-2xs">
                      <div class="flex justify-between items-center text-xs font-bold">
                        <span>Parrillada Las Brazas</span>
                        <span class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] inline-flex items-center justify-center">③</span>
                      </div>
                      <p class="text-[10px] text-slate-500 dark:text-slate-400">Aplica cupón BRAZAS15 (-15%)</p>
                      <div class="flex items-center justify-between pt-1">
                        <span class="text-xs font-black text-orange-600 dark:text-amber-400">Bs. 240</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-600 text-white text-[10px] font-bold">Confirmar</span>
                      </div>
                    </div>
                  </div>
                  <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-orange-500/30 flex items-center justify-between text-xs shadow-2xs">
                    <span class="flex items-center gap-2">
                      <span class="w-5 h-5 rounded-full bg-sky-600 text-white text-[10px] inline-flex items-center justify-center">④</span>
                      Seguimiento Pedido #1042: <strong>En Cocina</strong> · PIN Entrega: <strong class="text-orange-600 dark:text-amber-400">4829</strong>
                    </span>
                    <span class="px-2 py-0.5 rounded bg-sky-500/15 text-sky-700 dark:text-sky-300 text-[10px] font-bold">En vivo</span>
                  </div>
                </div>

                <!-- Mobile Mockup -->
                <div v-else class="max-w-xs mx-auto rounded-[2rem] border-4 border-slate-300 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-white p-4 space-y-3 shadow-lg">
                  <div class="w-16 h-1.5 rounded-full bg-slate-300 dark:bg-slate-800 mx-auto"></div>
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-black text-orange-600 dark:text-orange-400">Las Brazas App</span>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold">En línea</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-gradient-to-r from-orange-600 to-amber-500 text-white text-xs space-y-1">
                    <div class="font-bold flex items-center justify-between">
                      <span>Estado de tu Pedido #1042</span>
                      <span class="w-5 h-5 rounded-full bg-white text-orange-600 text-[10px] inline-flex items-center justify-center font-black">①</span>
                    </div>
                    <p class="text-[11px] opacity-95">En camino con Repartidor · Tu PIN: <strong>4829</strong></p>
                  </div>
                  <div class="p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-[11px] space-y-1.5">
                    <div class="font-bold flex justify-between">
                      <span>② Dirección & Facturación NIT/CI</span>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400">Av. Las Brazas #120 · NIT: 4589120</p>
                  </div>
                  <div class="p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-[11px] space-y-1.5">
                    <div class="font-bold">③ Asistente Chef Virtual IA</div>
                    <p class="text-slate-500 dark:text-slate-400">"¿Qué corte recomiendan para 4 personas?"</p>
                  </div>
                </div>
              </div>

              <!-- Annotated Controls Table -->
              <div class="lg:col-span-5">
                <table class="w-full text-left border-collapse text-xs">
                  <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500">
                      <th class="py-2 pr-2 font-bold">Ref.</th>
                      <th class="py-2 pr-2 font-bold">Control / Elemento</th>
                      <th class="py-2 font-bold">Acción en Pantalla</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr>
                      <td class="py-2.5 pr-2 font-black text-orange-600">①</td>
                      <td class="py-2.5 pr-2 font-semibold">Pestañas del Comensal</td>
                      <td class="py-2.5 text-slate-600 dark:text-slate-400">Navega entre Carta, Carrito, Pedidos, Promociones, Reservas, Asistente IA y Perfil.</td>
                    </tr>
                    <tr>
                      <td class="py-2.5 pr-2 font-black text-orange-600">②</td>
                      <td class="py-2.5 pr-2 font-semibold">Tarjeta de Plato</td>
                      <td class="py-2.5 text-slate-600 dark:text-slate-400">Permite elegir término de cocción o notas culinarias y sumar al carrito en un clic.</td>
                    </tr>
                    <tr>
                      <td class="py-2.5 pr-2 font-black text-emerald-600">③</td>
                      <td class="py-2.5 pr-2 font-semibold">Canje de Cupones</td>
                      <td class="py-2.5 text-slate-600 dark:text-slate-400">Valida códigos promocionales al instante y descuenta el monto antes de confirmar tu orden.</td>
                    </tr>
                    <tr>
                      <td class="py-2.5 pr-2 font-black text-sky-600">④</td>
                      <td class="py-2.5 pr-2 font-semibold">Seguimiento + PIN Delivery</td>
                      <td class="py-2.5 text-slate-600 dark:text-slate-400">Muestra el progreso en vivo y el PIN secreto de 4 dígitos para entregar al repartidor.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 6: VISUAL POS & FLOOR PLANS -->
        <section id="visual-pos" class="scroll-mt-24 space-y-5">
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <UtensilsCrossed class="w-6 h-6 text-orange-500" />
              6. Guía Visual: POS de Salón y Planos Inteligentes
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Herramientas de Mesero (Punto de Venta) y Recepción (Distribución Inteligente de Mesas).</p>
          </div>

          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start shadow-sm">
            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-100/80 dark:bg-slate-950 text-slate-900 dark:text-white p-4 space-y-3">
                <div class="text-xs font-bold text-orange-600 dark:text-orange-400 flex items-center justify-between">
                  <span>Punto de Venta POS</span>
                  <span class="px-2 py-0.5 rounded bg-orange-500/15 text-[10px]">① Selector Mesa</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-[11px] font-bold">
                  <div class="p-2 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300">M1 Libre</div>
                  <div class="p-2 rounded-lg bg-rose-500/15 border border-rose-500/30 text-rose-700 dark:text-rose-300">M2 Ocup.</div>
                  <div class="p-2 rounded-lg bg-amber-500/15 border border-amber-500/30 text-amber-700 dark:text-amber-300">M3 Res.</div>
                </div>
                <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-[11px] space-y-1">
                  <div class="font-bold text-slate-700 dark:text-slate-200">② Modalidad Comanda</div>
                  <div class="flex flex-wrap gap-1.5 text-[10px]">
                    <span class="px-2 py-0.5 rounded bg-orange-600 text-white">En Local</span>
                    <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Para Llevar</span>
                    <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">Delivery</span>
                  </div>
                </div>
              </div>

              <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-100/80 dark:bg-slate-950 text-slate-900 dark:text-white p-4 space-y-3">
                <div class="text-xs font-bold text-sky-600 dark:text-sky-400 flex items-center justify-between">
                  <span>Planos Inteligentes</span>
                  <span class="px-2 py-0.5 rounded bg-sky-500/15 text-[10px]">③ Detección IA</span>
                </div>
                <div class="h-24 rounded-xl border border-dashed border-sky-500/40 bg-white dark:bg-slate-900 flex items-center justify-around p-2">
                  <div class="w-10 h-10 rounded border-2 border-emerald-500 dark:border-emerald-400 text-[9px] flex items-center justify-center font-bold text-emerald-700 dark:text-emerald-300">Mesa 1</div>
                  <div class="w-12 h-10 rounded border-2 border-sky-500 dark:border-sky-400 text-[9px] flex items-center justify-center font-bold text-sky-700 dark:text-sky-300">Mesa 2</div>
                  <div class="w-10 h-10 rounded border-2 border-amber-500 dark:border-amber-400 text-[9px] flex items-center justify-center font-bold text-amber-700 dark:text-amber-300">Mesa 3</div>
                </div>
                <div class="text-[10px] text-slate-600 dark:text-slate-400">④ Botón: <strong class="text-slate-900 dark:text-white">Sincronizar Mesas</strong> guarda ubicaciones y sillas.</div>
              </div>
            </div>

            <div class="lg:col-span-5">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500">
                    <th class="py-2 pr-2 font-bold">Ref.</th>
                    <th class="py-2 pr-2 font-bold">Elemento</th>
                    <th class="py-2 font-bold">Función Clave</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr>
                    <td class="py-2.5 pr-2 font-black text-orange-600">①</td>
                    <td class="py-2.5 pr-2 font-semibold">Mapa de Mesas POS</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">Colores instantáneos: verde libre, rojo ocupada, ámbar reservada.</td>
                  </tr>
                  <tr>
                    <td class="py-2.5 pr-2 font-black text-orange-600">②</td>
                    <td class="py-2.5 pr-2 font-semibold">Tipo de Servicio</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">Soporta pedidos En Local, Para Llevar o Delivery desde un mismo tablero.</td>
                  </tr>
                  <tr>
                    <td class="py-2.5 pr-2 font-black text-sky-600">③</td>
                    <td class="py-2.5 pr-2 font-semibold">Detección Automática</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">Reconoce mesas y aforo automáticamente sobre el plano subido o plano modelo.</td>
                  </tr>
                  <tr>
                    <td class="py-2.5 pr-2 font-black text-sky-600">④</td>
                    <td class="py-2.5 pr-2 font-semibold">Optimizar & Sincronizar</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">Alinea la distribución del salón y guarda todas las mesas en la zona elegida.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION 7: VISUAL KDS & DELIVERY PIN + SIGNATURE -->
        <section id="visual-kds-delivery" class="scroll-mt-24 space-y-5">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                <ChefHat class="w-6 h-6 text-orange-500" />
                7. Guía Visual: Monitor de Cocina (KDS) y Verificación Delivery (PIN + Firma)
              </h2>
              <p class="text-sm text-slate-600 dark:text-slate-400">Pantallas operativas de Parrilla/Cocina y Reparto en Ruta.</p>
            </div>
            <div class="no-print inline-flex rounded-xl p-1 bg-slate-200/80 dark:bg-slate-800">
              <button
                type="button"
                @click="activeVisualOpsTab = 'kds'"
                :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', activeVisualOpsTab === 'kds' ? 'bg-white dark:bg-slate-950 text-orange-600 dark:text-orange-400 shadow-sm' : 'text-slate-600 dark:text-slate-300']"
              >
                Monitor KDS (Cocina)
              </button>
              <button
                type="button"
                @click="activeVisualOpsTab = 'delivery'"
                :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', activeVisualOpsTab === 'delivery' ? 'bg-white dark:bg-slate-950 text-orange-600 dark:text-orange-400 shadow-sm' : 'text-slate-600 dark:text-slate-300']"
              >
                Delivery PIN + Firma
              </button>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start shadow-sm">
            <div class="lg:col-span-7">
              <div v-if="activeVisualOpsTab === 'kds'" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-100/80 dark:bg-slate-950 text-slate-900 dark:text-white p-4 space-y-3">
                <div class="flex items-center justify-between text-xs border-b border-slate-200 dark:border-slate-800 pb-2">
                  <span class="font-bold text-amber-700 dark:text-amber-400">① Actualización cada 10s · Filtros: Todos / Delivery / Pendiente / En Cocina / Listo</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                  <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 border-l-4 border-l-emerald-500 space-y-1.5 shadow-2xs">
                    <div class="flex justify-between font-bold">
                      <span>Comanda #1045 · Mesa 4</span>
                      <span class="text-emerald-600 dark:text-emerald-400">② 06:40</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">2x Bife Ancho (Término Medio)</p>
                    <span class="inline-block px-2 py-0.5 rounded bg-orange-600 text-white text-[10px] font-bold">Marcar Listo</span>
                  </div>
                  <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 border-l-4 border-l-rose-500 space-y-1.5 shadow-2xs">
                    <div class="flex justify-between font-bold">
                      <span>Comanda #1039 · Delivery</span>
                      <span class="text-rose-600 dark:text-rose-400">③ 21:15 ⚠️</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">1x Parrillada Familiar + Chorizo</p>
                    <span class="inline-block px-2 py-0.5 rounded bg-rose-600 text-white text-[10px] font-bold">Despacho Urgente</span>
                  </div>
                </div>
              </div>

              <div v-else class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-100/80 dark:bg-slate-950 text-slate-900 dark:text-white p-4 space-y-3">
                <div class="flex items-center justify-between text-xs border-b border-slate-200 dark:border-slate-800 pb-2">
                  <span class="font-bold text-violet-700 dark:text-violet-400">Confirmar Entrega Segura — Pedido #1042</span>
                  <span class="text-[10px] px-2 py-0.5 rounded bg-violet-500/15 text-violet-700 dark:text-violet-300 font-bold">En Camino</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                  <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2 shadow-2xs">
                    <div class="font-bold text-slate-800 dark:text-slate-200">① PIN de Seguridad (4 dígitos)</div>
                    <div class="flex gap-2 justify-center py-1">
                      <span class="w-8 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 border border-violet-500/40 flex items-center justify-center font-mono font-bold text-base">4</span>
                      <span class="w-8 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 border border-violet-500/40 flex items-center justify-center font-mono font-bold text-base">8</span>
                      <span class="w-8 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 border border-violet-500/40 flex items-center justify-center font-mono font-bold text-base">2</span>
                      <span class="w-8 h-9 rounded-lg bg-slate-100 dark:bg-slate-800 border border-violet-500/40 flex items-center justify-center font-mono font-bold text-base">9</span>
                    </div>
                  </div>
                  <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-2 shadow-2xs">
                    <div class="font-bold text-slate-800 dark:text-slate-200">② Firma Digital en Pantalla</div>
                    <div class="h-12 rounded-lg bg-slate-50 dark:bg-white flex items-center justify-center text-slate-800 italic font-serif text-sm border border-dashed border-slate-400">
                      C. Mendoza ✓
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="lg:col-span-5">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500">
                    <th class="py-2 pr-2 font-bold">Área</th>
                    <th class="py-2 pr-2 font-bold">Regla Crítica</th>
                    <th class="py-2 font-bold">Funcionamiento</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr>
                    <td class="py-2.5 pr-2 font-bold text-amber-600">Monitor KDS</td>
                    <td class="py-2.5 pr-2">Refresco 10s & Cronómetro</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">&lt;12m verde, 12–19m ámbar, ≥20m rojo urgente.</td>
                  </tr>
                  <tr>
                    <td class="py-2.5 pr-2 font-bold text-violet-600">Delivery</td>
                    <td class="py-2.5 pr-2">3 Etapas de Ruta</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">Disponibles → En Camino (GPS Maps/Waze) → Entregadas.</td>
                  </tr>
                  <tr>
                    <td class="py-2.5 pr-2 font-bold text-emerald-600">Seguridad</td>
                    <td class="py-2.5 pr-2">PIN + Firma Táctil</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">Exige ambos datos presencialmente antes de cerrar la entrega.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION 8: VISUAL CHATBOT IA & BI REPORTS -->
        <section id="visual-ia" class="scroll-mt-24 space-y-5">
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <Bot class="w-6 h-6 text-orange-500" />
              8. Guía Visual: Chef Virtual IA y Reportes Inteligentes
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Asistencia conversacional por voz o texto y análisis gerencial de ventas e inventario.</p>
          </div>

          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 grid grid-cols-1 lg:grid-cols-2 gap-6 shadow-sm">
            <!-- Chatbot Box -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white p-5 space-y-3">
              <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl bg-orange-600 text-white flex items-center justify-center">
                    <Sparkles class="w-4 h-4" />
                  </div>
                  <div>
                    <div class="text-xs font-extrabold">Chef Virtual Las Brazas</div>
                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">En línea · Atención a Comensales y Equipo</div>
                  </div>
                </div>
                <span class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-semibold">Asistente IA</span>
              </div>
              <div class="space-y-2 text-xs">
                <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 shadow-2xs">
                  "¡Hola! Soy el Chef Virtual IA. Puedo sugerirte cortes a la parrilla, aplicar cupones al carrito o verificar disponibilidad de mesas en tiempo real."
                </div>
                <div class="p-2.5 rounded-xl bg-orange-500/10 dark:bg-orange-600/20 border border-orange-500/30 text-orange-800 dark:text-orange-200 text-[11px]">
                  <strong>Funciones integradas:</strong> Dictado por micrófono, tarjetas rápidas para añadir platos al carrito y reserva instantánea desde el chat.
                </div>
              </div>
            </div>

            <!-- Reports BI + Predictions Box -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 p-5 space-y-3">
              <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2 text-sm font-extrabold text-slate-900 dark:text-white">
                  <BarChart3 class="w-4 h-4 text-orange-500" /> Reportes Gerenciales & Pronósticos
                </div>
              </div>
              <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                <li class="flex items-start gap-2">
                  <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span><strong>Exportación multiformato:</strong> Descarga informes gerenciales en <strong>PDF, Excel y CSV</strong> filtrando por rango de fechas.</span>
                </li>
                <li class="flex items-start gap-2">
                  <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span><strong>Consultas por Voz o Texto:</strong> El Administrador pregunta en español natural (ej. "¿Qué plato se vendió más el viernes?") y obtiene gráficas al instante.</span>
                </li>
                <li class="flex items-start gap-2">
                  <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span><strong>Pronóstico de Demanda:</strong> Estima el consumo futuro de ingredientes para planificar compras de carne y guarniciones sin desperdicio.</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- PARTE 3: ACCESS & COMPATIBILITY -->
        <section id="acceso-soporte" class="scroll-mt-24 space-y-5">
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <Globe class="w-6 h-6 text-orange-500" />
              9. Acceso Web, Dispositivos Compatibles y Descarga
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              Canales de acceso disponibles para clientes, meseros, parrilleros, repartidores y administración.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 space-y-3 shadow-sm">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">Dispositivos Recomendados</h3>
              <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2 leading-relaxed">
                <li><strong>• Navegador Web / POS / Monitor Cocina:</strong> Google Chrome, Microsoft Edge o Mozilla Firefox en PC, tablet o pantalla táctil.</li>
                <li><strong>• App Móvil Comensal:</strong> Teléfonos Android 9+ e iOS 14+ conectados en tiempo real a tu cuenta.</li>
                <li><strong>• Disponibilidad en la Nube:</strong> Operación continua 24/7 con respaldos diarios y conexión cifrada HTTPS.</li>
              </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 space-y-3 shadow-sm">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">Accesos Directos al Manual</h3>
              <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2 leading-relaxed">
                <li><strong>• Portal Web Dedicado:</strong> <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-medium text-orange-600 dark:text-orange-400">{{ manualSubdomainUrl || 'Subdominio Oficial de Ayuda' }}</span></li>
                <li><strong>• Acceso desde el Sistema:</strong> Enlace directo <em>Manual de Usuario Interactivo</em> en el pie de página del portal principal.</li>
                <li><strong>• Versión PDF Descargable:</strong> Botón superior <strong>Descargar PDF</strong> para consultar o imprimir sin conexión a internet.</li>
              </ul>
            </div>
          </div>
        </section>

        <!-- SECTION 10: TROUBLESHOOTING FAQ -->
        <section id="soporte" class="scroll-mt-24 space-y-5">
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <HelpCircle class="w-6 h-6 text-orange-500" />
              10. Preguntas Frecuentes y Soporte (FAQ)
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Respuestas rápidas ante dudas operativas comunes.</p>
          </div>

          <div class="space-y-3">
            <div
              v-for="(item, index) in filteredFaqs"
              :key="item.q"
              class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm"
            >
              <button
                type="button"
                @click="openFaqIndex = openFaqIndex === index ? -1 : index"
                class="w-full px-5 py-4 text-left flex items-center justify-between gap-4 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
              >
                <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-2.5">
                  <AlertTriangle class="w-4 h-4 text-amber-500 shrink-0" />
                  {{ item.q }}
                </span>
                <ChevronDown :class="['w-4 h-4 text-slate-400 shrink-0 transition-transform', openFaqIndex === index ? 'rotate-180' : '']" />
              </button>
              <div v-show="openFaqIndex === index" class="px-5 pb-4 pt-1 text-xs text-slate-600 dark:text-slate-400 leading-relaxed border-t border-slate-100 dark:border-slate-800/70">
                {{ item.a }}
              </div>
            </div>
          </div>
        </section>

        <!-- Footer -->
        <footer class="pt-6 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
          <span>© 2026 Restaurante Las Brazas Steakhouse · BrazasTech Soluciones de Software S.R.L.</span>
          <div class="flex items-center gap-4 no-print">
            <a :href="pdfUrl" target="_blank" rel="noopener" class="text-orange-600 dark:text-orange-400 font-semibold hover:underline">Ver Manual PDF</a>
            <a :href="mainSystemUrl" class="hover:text-slate-900 dark:hover:text-white underline">Volver al Sistema</a>
          </div>
        </footer>
      </main>
    </div>
  </div>
</template>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
  main {
    width: 100% !important;
  }
  section {
    break-inside: avoid;
  }
}
</style>
