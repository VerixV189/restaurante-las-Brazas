<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useTheme } from '../../composables/useTheme';
import {
  Flame,
  BookOpen,
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
  KeyRound,
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
  { id: 'todos', label: 'Todos los Roles', short: 'Todos', badge: 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900', accent: 'slate' },
  { id: 'administrador', label: 'Administrador', short: 'Admin', badge: 'bg-orange-500/15 text-orange-700 dark:text-orange-300 border border-orange-500/30', accent: 'orange' },
  { id: 'chef', label: 'Chef / Cocina', short: 'Chef', badge: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30', accent: 'amber' },
  { id: 'mesero', label: 'Mesero / Salón', short: 'Mesero', badge: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30', accent: 'emerald' },
  { id: 'recepcionista', label: 'Recepcionista', short: 'Recepción', badge: 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30', accent: 'sky' },
  { id: 'repartidor', label: 'Repartidor', short: 'Delivery', badge: 'bg-violet-500/15 text-violet-700 dark:text-violet-300 border border-violet-500/30', accent: 'violet' },
  { id: 'cliente', label: 'Cliente / Comensal', short: 'Cliente', badge: 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30', accent: 'rose' },
];

const tocGroups = [
  {
    title: 'PARTE 1 · Manual del Usuario',
    items: [
      { id: 'introduccion', label: '1. Introducción y Ecosistema', roles: ['todos', 'administrador', 'chef', 'mesero', 'recepcionista', 'repartidor', 'cliente'] },
      { id: 'roles-rbac', label: '2. Roles y Matriz RBAC', roles: ['todos', 'administrador', 'chef', 'mesero', 'recepcionista', 'repartidor', 'cliente'] },
      { id: 'flujos-rol', label: '3. Flujos Operativos por Rol', roles: ['todos', 'administrador', 'chef', 'mesero', 'recepcionista', 'repartidor', 'cliente'] },
      { id: 'estados', label: '4. Estados, SLA y Etiquetas', roles: ['todos', 'administrador', 'chef', 'mesero', 'recepcionista', 'repartidor', 'cliente'] },
    ],
  },
  {
    title: 'PARTE 2 · Guía Visual de Uso',
    items: [
      { id: 'visual-cliente', label: '5. Portal Web & App Móvil', roles: ['todos', 'cliente', 'administrador'] },
      { id: 'visual-pos', label: '6. POS Salón y Planos YOLO', roles: ['todos', 'mesero', 'recepcionista', 'administrador'] },
      { id: 'visual-kds-delivery', label: '7. Monitor KDS & Delivery PIN', roles: ['todos', 'chef', 'repartidor', 'administrador'] },
      { id: 'visual-ia', label: '8. Chef Virtual IA & Reportes BI', roles: ['todos', 'administrador', 'cliente', 'chef'] },
    ],
  },
  {
    title: 'PARTE 3 · Infraestructura y Soporte',
    items: [
      { id: 'requisitos', label: '9. Requisitos y Subdominio', roles: ['todos', 'administrador'] },
      { id: 'soporte', label: '10. Preguntas Frecuentes (FAQ)', roles: ['todos', 'administrador', 'chef', 'mesero', 'recepcionista', 'repartidor', 'cliente'] },
    ],
  },
];

const roleCards = [
  {
    id: 'administrador',
    name: 'Administrador',
    icon: ShieldCheck,
    scope: 'Control total del sistema, usuarios, inventario, reportes BI e IA',
    routes: '/users, /suppliers, /reports, /activity-logs, /predictions, + todos los módulos operativos',
    summary: 'Supervisa la operación completa, configura usuarios y roles, evalúa métricas financieras PDF/Excel/CSV con consultas NLP y audita logs.',
  },
  {
    id: 'chef',
    name: 'Chef',
    icon: ChefHat,
    scope: 'Monitor de Cocina (KDS), catálogo gastronómico, recetas e inventario',
    routes: '/kitchen, /categories, /dishes, /recipes, /ingredients, /inventory-movements, /predictions',
    summary: 'Gestiona comandas en tiempo real con SLA cromático, controla fichas de receta, descuento de insumos e inspecciona predicciones de demanda.',
  },
  {
    id: 'mesero',
    name: 'Mesero',
    icon: UtensilsCrossed,
    scope: 'Punto de Venta (POS Salón), comandas de mesa y apoyo en reservas',
    routes: '/pos, /pos/orders, /reservations',
    summary: 'Toma pedidos en salón (dine-in), para llevar (takeaway) o entrega local, asigna mesas disponibles y consulta el estado de comandas.',
  },
  {
    id: 'recepcionista',
    name: 'Recepcionista',
    icon: CalendarCheck,
    scope: 'Salón, mesas, planos arquitectónicos YOLO IA, clientes y reservas',
    routes: '/areas, /tables, /floor-plans, /customers, /reservations',
    summary: 'Administra disponibilidad de mesas, ejecuta detección automática de planos con YOLO IA y valida reservas entrantes.',
  },
  {
    id: 'repartidor',
    name: 'Repartidor',
    icon: Truck,
    scope: 'Despacho Delivery en última milla y verificación PIN + Firma',
    routes: '/delivery (pestañas: Disponibles, En Camino, Entregadas)',
    summary: 'Acepta entregas listas desde cocina, registra el retiro y confirma la entrega presencial mediante PIN de 4 dígitos y firma digital en canvas.',
  },
  {
    id: 'cliente',
    name: 'Cliente',
    icon: Smartphone,
    scope: 'Portal de Comensal (Web /dashboard) y App Móvil Flutter',
    routes: '/dashboard?tab=menu|cart|orders|promos|reservations|ai|profile y API /api/v1/*',
    summary: 'Explora el menú a la parrilla, aplica cupones, realiza pedidos con seguimiento en vivo, reserva mesas e interactúa con el Chef Virtual IA.',
  },
];

const rbacMatrix = [
  { module: 'Landing Pública, Carta y Reserva Online (/ & /reserve)', admin: true, chef: true, mesero: true, recep: true, rep: true, cli: true },
  { module: 'Portal Cliente 7 Pestañas (/dashboard?tab=...) & API Móvil', admin: true, chef: false, mesero: false, recep: false, rep: false, cli: true },
  { module: 'Punto de Venta POS (/pos, /pos/orders)', admin: true, chef: false, mesero: true, recep: false, rep: false, cli: false },
  { module: 'Monitor Cocina KDS (/kitchen, auto-refresco 10s)', admin: true, chef: true, mesero: false, recep: false, rep: false, cli: false },
  { module: 'Catálogo, Recetas, Insumos y Predicciones (/dishes, /recipes, /predictions)', admin: true, chef: true, mesero: false, recep: false, rep: false, cli: false },
  { module: 'Áreas, Mesas, Clientes y Planos YOLO (/floor-plans, /tables)', admin: true, chef: false, mesero: false, recep: true, rep: false, cli: false },
  { module: 'Gestión de Reservas (/reservations)', admin: true, chef: false, mesero: true, recep: true, rep: false, cli: false },
  { module: 'Módulo Delivery con PIN + Firma (/delivery)', admin: true, chef: false, mesero: false, recep: false, rep: true, cli: false },
  { module: 'Proveedores, Usuarios, Reportes BI & Auditoría (/reports, /users, /activity-logs)', admin: true, chef: false, mesero: false, recep: false, rep: false, cli: false },
  { module: 'Asistente Chef Virtual IA (/ai/chat)', admin: true, chef: true, mesero: true, recep: true, rep: true, cli: true },
];

const roleWorkflows = [
  {
    id: 'cliente',
    roleName: 'Cliente / Comensal',
    badge: 'Web Portal + App Flutter',
    moduleUrl: '/dashboard?tab=menu & /api/v1/*',
    steps: [
      { title: 'Registro y Verificación de Correo', detail: 'En /register ingresa nombre, correo, teléfono y contraseña. El sistema asigna el rol Cliente y envía 3 correos automáticos (Términos, Condiciones y enlace de verificación /verify-email/{id}/{hash}).' },
      { title: 'Exploración de Carta y Personalización', detail: 'Desde el Portal (/dashboard?tab=menu) o la App Flutter, filtra categorías activas, ajusta cantidades, términos de cocción y notas culinarias.' },
      { title: 'Carrito, Cupones y Modalidad de Entrega', detail: 'En la pestaña cart elige Consumo en Local, Para Llevar o Delivery, selecciona dirección guardada (/api/v1/customer/addresses), valida cupones (/api/v1/coupons/validate) y registra NIT/CI.' },
      { title: 'Seguimiento Timeline y Reserva de Mesa', detail: 'Monitorea tu pedido en tiempo real (Pendiente → En Cocina → Listo → Entregado). En reservations solicita mesa verificando ventana de ±2 horas y tolerancia de 15 minutos.' },
    ],
    result: 'El pedido se inyecta instantáneamente en el POS y en el Monitor KDS de cocina sin intervención manual, generando comprobante digital y PIN de entrega si es delivery.',
  },
  {
    id: 'recepcionista',
    roleName: 'Recepcionista',
    badge: 'Planos IA & Reservas',
    moduleUrl: '/floor-plans · /tables · /reservations',
    steps: [
      { title: 'Configuración de Áreas y Capacidad', detail: 'En /areas y /tables administra zonas (Salón Principal, Terraza, VIP) con capacidad de comensales y estado operativo.' },
      { title: 'Detección Inteligente de Planos (YOLO IA)', detail: 'En /floor-plans carga la imagen del plano arquitectónico o usa el plano de muestra, ejecuta Detectar con YOLO (/floor-plans/detect), optimiza distribución (/optimize) y sincroniza mesas (/sync-tables).' },
      { title: 'Recepción y Confirmación de Reservas', detail: 'Revisa las solicitudes web con estado pending en /reservations, verifica choque horario de ±2h y cambia a confirmed.' },
      { title: 'Check-In en Mesa', detail: 'Cuando llegan los comensales (dentro de la tolerancia de 15 min), marca la reserva como seated para liberar la toma de comanda al mesero.' },
    ],
    result: 'El mapa de salón refleja en vivo qué mesas están disponibles (available), reservadas (reserved) u ocupadas (occupied), reduciendo tiempos de espera.',
  },
  {
    id: 'mesero',
    roleName: 'Mesero',
    badge: 'POS Salón',
    moduleUrl: '/pos · /pos/orders',
    steps: [
      { title: 'Selección de Tipo de Servicio y Mesa', detail: 'Ingresa a /pos y selecciona el área o mesa activa (dine_in), o bien elige Para Llevar (takeaway) o Delivery para órdenes telefónicas.' },
      { title: 'Armado de Comanda por Categorías', detail: 'Agrega cortes a la parrilla, guarniciones y bebidas; añade observaciones específicas de cocción (ej. "Término 3/4, sin sal") en cada ítem.' },
      { title: 'Asignación de Cliente y Envío a Cocina', detail: 'Vincula un cliente existente o consumidor final y presiona Confirmar Comanda (POST /pos/orders).' },
      { title: 'Servicio en Mesa', detail: 'Al pasar el ticket a estado ready desde cocina, entrega los platos en mesa y actualiza la orden a served.' },
    ],
    result: 'Cada comanda queda trazada con marca de tiempo, asociada a su mesa y visible tanto en el KDS de cocina como en el cierre de caja.',
  },
  {
    id: 'chef',
    roleName: 'Chef / Cocina',
    badge: 'Monitor KDS & Recetas',
    moduleUrl: '/kitchen · /recipes · /predictions',
    steps: [
      { title: 'Supervisión del Tablero KDS en Tiempo Real', detail: 'El monitor /kitchen se actualiza automáticamente cada 10 segundos y clasifica comandas con pestañas all, delivery, pending, in_kitchen y ready.' },
      { title: 'Control de Tiempos por Semáforo SLA', detail: 'Controla el cronómetro de preparación de cada tarjeta: < 12 min (Normal), 12–19 min (Alerta ámbar) y ≥ 20 min (Crítico rojo).' },
      { title: 'Avance de Preparación y Despacho', detail: 'Cambia ítems u órdenes de pending a in_kitchen al iniciar parrilla y a ready cuando el plato pasa a barra de entrega.' },
      { title: 'Recetas, Stock y Predicciones IA', detail: 'En /recipes define la fórmula de ingredientes de cada plato; revisa /inventory-movements y /predictions para anticipar insumos de alta rotación.' },
    ],
    result: 'Cero comandas de papel extraviadas, descuento automático de inventario al vender y visibilidad exacta del cuello de botella por plato.',
  },
  {
    id: 'repartidor',
    roleName: 'Repartidor',
    badge: 'Delivery Última Milla',
    moduleUrl: '/delivery',
    steps: [
      { title: 'Toma de Pedido Disponible', detail: 'En la pestaña Disponibles (pending) revisa dirección de entrega, teléfono del cliente y resumen de platos, y presiona Tomar Pedido (takeDelivery).' },
      { title: 'Retiro de Cocina (En Camino)', detail: 'Al recoger el paquete empaquetado de barra, marca Retirado (markPickedUp) moviendo el pedido a la pestaña En Camino (mine).' },
      { title: 'Verificación Presencial Doble Factor (RF-19)', detail: 'En la puerta del cliente abre el modal de entrega, solicita el PIN de 4 dígitos mostrado en la app del cliente e invita al cliente a firmar en el canvas táctil.' },
      { title: 'Cierre Auditado de Entrega', detail: 'Al confirmar ambos factores, el pedido pasa a Entregadas (completed / delivered) y queda la firma almacenada.' },
    ],
    result: 'Evidencia digital irrefutable de entrega correcta (PIN + Firma Canvas) que elimina reclamos por pedidos no recibidos.',
  },
  {
    id: 'administrador',
    roleName: 'Administrador',
    badge: 'Dirección, BI & Auditoría',
    moduleUrl: '/reports · /users · /activity-logs',
    steps: [
      { title: 'Administración de Personal y Proveedores', detail: 'En /users crea cuentas de personal y asigna exactamente uno de los 6 roles RBAC; en /suppliers registra proveedores de insumos cárnicos y bebidas.' },
      { title: 'Inteligencia de Negocios y Exportación', detail: 'En /reports analiza KPIs de ingresos, ticket promedio y top de platos por rango de fechas, y descarga reportes en PDF, Excel o CSV.' },
      { title: 'Consultas en Lenguaje Natural (Gemini AI)', detail: 'Usa la barra inteligente o dictado por voz en /reports para preguntar datos financieros al instante o abre el panel Chef Virtual IA.' },
      { title: 'Auditoría de Seguridad', detail: 'Revisa /activity-logs para inspeccionar creación, modificación o anulación de registros críticos con fecha, usuario y módulo.' },
    ],
    result: 'Trazabilidad total administrativa y financiera lista para toma de decisiones gerenciales y cumplimiento de auditoría.',
  },
];

const statusLegends = [
  { group: 'Órdenes / Comandas', items: [
    { code: 'pending', label: 'Pendiente', style: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30', desc: 'Recién creada desde POS o App cliente; espera ingreso a parrilla.' },
    { code: 'in_kitchen', label: 'En Cocina', style: 'bg-orange-500/15 text-orange-700 dark:text-orange-300 border-orange-500/30', desc: 'Chef aceptó preparación en Monitor KDS; cronómetro activo.' },
    { code: 'ready', label: 'Listo', style: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30', desc: 'Platos listos en barra para mesero (salón) o repartidor (delivery).' },
    { code: 'served', label: 'Servido', style: 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30', desc: 'Entregado satisfactoriamente en mesa.' },
    { code: 'delivered', label: 'Entregado', style: 'bg-teal-500/15 text-teal-700 dark:text-teal-300 border-teal-500/30', desc: 'Confirmado en delivery mediante PIN 4 dígitos + Firma Digital.' },
    { code: 'cancelled', label: 'Cancelado', style: 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30', desc: 'Orden anulada y auditada en activity_logs.' },
  ]},
  { group: 'Semáforo SLA en KDS', items: [
    { code: '< 12 min', label: 'Normal', style: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30', desc: 'Tiempo óptimo de cocción a las brasas dentro del estándar.' },
    { code: '12–19 min', label: 'Advertencia', style: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30', desc: 'Prioridad media; revisar punto de cocción y emplatado.' },
    { code: '>= 20 min', label: 'Crítico', style: 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30', desc: 'Retraso operativo; requiere despacho inmediato.' },
  ]},
  { group: 'Reservas de Mesa', items: [
    { code: 'pending', label: 'Pendiente', style: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30', desc: 'Solicitud ingresada vía web pública o App móvil.' },
    { code: 'confirmed', label: 'Confirmada', style: 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30', desc: 'Mesa bloqueada en horario ±2h con tolerancia de llegada 15 min.' },
    { code: 'seated', label: 'En Mesa', style: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30', desc: 'Comensales presentes y acomodados por recepción.' },
    { code: 'completed', label: 'Completada', style: 'bg-slate-500/15 text-slate-700 dark:text-slate-300 border-slate-500/30', desc: 'Ciclo de reserva finalizado.' },
  ]},
];

const faqs = [
  {
    q: 'Me registré como cliente pero no puedo iniciar sesión ("cuenta sin verificar"). ¿Qué hago?',
    a: 'Al registrarte en /register el sistema envía 3 correos (Términos, Condiciones y Verificación). Abre el correo "Verificar Cuenta" y haz clic en el enlace (/verify-email/{id}/{hash}). Si usas ambiente de pruebas local sin SMTP real, verifica el buzón configurado (Mailtrap/Log) o solicita al Administrador activar el usuario en /users.',
  },
  {
    q: 'Al intentar entrar a /pos, /kitchen o /reports me aparece error 403 Acceso Denegado.',
    a: 'Es el comportamiento esperado del middleware RBAC CheckRole. Cada módulo permite únicamente los roles autorizados (por ejemplo, KDS solo para Administrador y Chef; POS solo para Administrador y Mesero). Solicita al Administrador revisar tu rol asignado en /users.',
  },
  {
    q: 'El Repartidor no puede finalizar la entrega en /delivery.',
    a: 'Por seguridad (RF-19), la plataforma exige obligatoriamente ambos factores: ingresar el PIN exacto de 4 dígitos que visualiza el cliente en su detalle de orden y trazar una firma válida en el lienzo táctil antes de habilitar la confirmación.',
  },
  {
    q: 'El Monitor KDS de Cocina no muestra la orden recién creada.',
    a: 'El monitor KDS (/kitchen) se auto-actualiza cada 10 segundos y posee filtros de estado (all, delivery, pending, in_kitchen, ready). Verifica que la pestaña "Todos" o "Pendiente" esté activa y que el dispositivo tenga conectividad al servidor.',
  },
  {
    q: 'No se encuentran mesas disponibles al reservar desde la Landing o App.',
    a: 'Las Brazas aplica una regla de bloqueo horario de ±2 horas por reserva confirmada/sentada para evitar sobreventa. Prueba otro horario o zona (Salón, Terraza, VIP), o consulta mediante Chef Virtual IA.',
  },
  {
    q: '¿Cómo accedo al manual desde el subdominio dedicado o sin conexión?',
    a: 'En producción AWS EC2, el manual responde directamente en https://manual.tudominio.com/ (enrutado al mismo controlador ManualController::index sin costo de instancia adicional) y también desde /manual en el dominio principal. Además, puedes descargar el archivo Manual_Usuario_Completo_Las_Brazas.pdf para lectura offline.',
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
  <Head title="Manual de Usuario Interactivo — Restaurante Las Brazas" />

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
                  Las Brazas <span class="text-orange-600 dark:text-orange-400">Docs</span>
                </span>
                <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-500/10 text-orange-600 dark:text-orange-400 border border-orange-500/25">
                  v{{ appVersion }}
                </span>
                <span v-if="isSubdomain" class="hidden md:inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25">
                  <Globe class="w-3 h-3" /> Subdominio Oficial
                </span>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate hidden sm:block">
                Manual Web Interactivo · BrazasTech POS, KDS, IA & App Móvil
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
              placeholder="Buscar rol, flujo, pantalla, PIN, YOLO... (Ctrl+K)"
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
            placeholder="Buscar en el manual (roles, POS, KDS, PIN, FAQ)..."
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
                <Users class="w-3.5 h-3.5 text-orange-500" /> Filtrar por Rol
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
              Versión Completa PDF
            </div>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
              Ideal para capacitación impresa o lectura sin internet. Incluye Parte 1 operativa y Parte 2 guía visual ilustrada.
            </p>
            <div class="flex flex-col gap-1.5 pt-1">
              <a
                :href="pdfUrl"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl text-xs font-bold bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:opacity-90 transition-opacity"
              >
                <ExternalLink class="w-3.5 h-3.5" /> Abrir PDF en Navegador
              </a>
              <a
                :href="pdfDownloadUrl"
                download="Manual_Usuario_Completo_Las_Brazas.pdf"
                class="inline-flex items-center justify-center gap-1.5 w-full py-2 px-3 rounded-xl text-xs font-semibold border border-orange-500/30 text-orange-700 dark:text-orange-300 hover:bg-orange-500/10 transition-colors"
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
          <div class="relative bg-gradient-to-br from-slate-950 via-slate-900 to-orange-950 text-white p-7 sm:p-10">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.22),transparent_55%)] pointer-events-none"></div>
            <div class="relative z-10 max-w-3xl space-y-4">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/20 border border-orange-400/30 text-orange-300 text-xs font-bold">
                <Flame class="w-3.5 h-3.5" />
                RESTAURANTE LAS BRAZAS · BRAZASTECH ECOSYSTEM v{{ appVersion }}
              </div>
              <h1 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight">
                Manual de Usuario Web e Interactivo
              </h1>
              <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
                Documentación oficial integrada del ecosistema gastronómico <strong>Las Brazas Steakhouse</strong>: cubre los 6 roles operativos (RBAC), la plataforma Web Laravel 12 + Vue 3 / Inertia, el Monitor KDS de Cocina en tiempo real, el módulo Delivery con doble verificación PIN + Firma, Planos YOLO con Visión Computacional, Chef Virtual IA (Gemini) y la App Móvil Flutter.
              </p>

              <div class="flex flex-wrap gap-3 pt-2 no-print">
                <a
                  v-if="pdfAvailable"
                  :href="pdfDownloadUrl"
                  download="Manual_Usuario_Completo_Las_Brazas.pdf"
                  class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-orange-600 hover:bg-orange-500 text-white shadow-lg shadow-orange-600/30 transition-all"
                >
                  <Download class="w-4 h-4" />
                  Descargar Manual Completo (.PDF)
                </a>
                <button
                  type="button"
                  @click="scrollToSection('roles-rbac')"
                  class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-white/10 hover:bg-white/20 text-white border border-white/15 transition-all"
                >
                  <Compass class="w-4 h-4" />
                  Explorar por Rol (6)
                </button>
              </div>
            </div>
          </div>

          <!-- Architecture Quick Pillars -->
          <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 p-6 sm:p-8 bg-white dark:bg-slate-900">
            <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-1.5">
              <div class="flex items-center gap-2 text-xs font-bold text-orange-600 dark:text-orange-400">
                <UtensilsCrossed class="w-4 h-4" /> POS + Salón + KDS
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Flujo continuo desde la comanda en mesa o app hasta el monitor de cocina KDS con auto-refresco cada 10s.
              </p>
            </div>
            <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-1.5">
              <div class="flex items-center gap-2 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                <Truck class="w-4 h-4" /> Delivery Seguro (RF-19)
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Confirmación en última milla exigiendo PIN de 4 dígitos del cliente y firma digital trazada en pantalla.
              </p>
            </div>
            <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-1.5">
              <div class="flex items-center gap-2 text-xs font-bold text-sky-600 dark:text-sky-400">
                <ScanLine class="w-4 h-4" /> Visión YOLO & Predicción
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Detección automática de mesas en planos arquitectónicos con YOLO y proyección de demanda con IA.
              </p>
            </div>
            <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 space-y-1.5">
              <div class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                <Bot class="w-4 h-4" /> Chef Virtual & BI Gemini
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                ChatBot con dictado de voz para comensales (carta, cupones, reservas) y consultas gerenciales BI.
              </p>
            </div>
          </div>
        </section>

        <!-- PARTE 1 DIVIDER BANNER -->
        <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-slate-900 dark:bg-slate-800 text-white">
          <span class="px-2.5 py-1 rounded-lg bg-orange-600 text-[11px] font-black uppercase tracking-wider">Parte 1</span>
          <span class="text-sm font-bold">Manual Operativo del Usuario — Roles, Matriz RBAC, Flujos y Estados</span>
        </div>

        <!-- SECTION 2: ROLES & RBAC MATRIX -->
        <section id="roles-rbac" class="scroll-mt-24 space-y-6">
          <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <ShieldCheck class="w-6 h-6 text-orange-500" />
              2. Roles del Sistema y Matriz de Acceso Rápido (RBAC)
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              El backend en Laravel 12 valida cada vista y petición API mediante el middleware <code class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-xs font-mono">CheckRole</code> y tokens <code class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-xs font-mono">Sanctum</code>.
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
                    Ver flujo →
                  </button>
                </div>
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ card.scope }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ card.summary }}</p>
              </div>
              <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400 block mb-1">Rutas autorizadas</span>
                <code class="text-[11px] font-mono text-orange-700 dark:text-orange-300 break-words">{{ card.routes }}</code>
              </div>
            </article>
          </div>

          <!-- RBAC Access Matrix Table -->
          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/60">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                Matriz de Acceso por Módulo y Rol (Verificada en routes/web.php & routes/api.php)
              </span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 text-slate-600 dark:text-slate-300">
                    <th class="py-3 px-4 font-bold">Módulo / Ruta Principal</th>
                    <th class="py-3 px-3 text-center font-bold">Admin</th>
                    <th class="py-3 px-3 text-center font-bold">Chef</th>
                    <th class="py-3 px-3 text-center font-bold">Mesero</th>
                    <th class="py-3 px-3 text-center font-bold">Recepción</th>
                    <th class="py-3 px-3 text-center font-bold">Repartidor</th>
                    <th class="py-3 px-3 text-center font-bold">Cliente</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                  <tr v-for="row in rbacMatrix" :key="row.module" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
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
              3. Guía de Flujos Paso a Paso por Rol
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              Cada flujo detalla la secuencia exacta de operación desde el ingreso hasta la confirmación de resultado en base de datos.
            </p>
          </div>

          <div v-if="filteredWorkflows.length === 0" class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-sm text-slate-500">
            No hay flujos que coincidan con el filtro o búsqueda actual.
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
                  <code class="text-xs font-mono text-slate-500 dark:text-slate-400">{{ flow.moduleUrl }}</code>
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

        <!-- SECTION 4: SYSTEM STATES & SLA BADGES -->
        <section id="estados" class="scroll-mt-24 space-y-6">
          <div class="space-y-1">
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <Clock class="w-6 h-6 text-orange-500" />
              4. Estados del Sistema, Etiquetas y Semáforos SLA
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              Significado operativo de los códigos de estado sincronizados entre Laravel, Inertia Web y Flutter Mobile.
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
                    <code class="text-[11px] font-mono text-slate-500 dark:text-slate-400">{{ st.code }}</code>
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
                5. Guía Visual: Portal Público, Comensal (/dashboard) y App Móvil
              </h2>
              <p class="text-sm text-slate-600 dark:text-slate-400">Alterna entre la vista Web de escritorio y la App Móvil Flutter.</p>
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
                App Móvil Flutter
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
              <code class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
                {{ activeVisualClientTab === 'desktop' ? 'https://lasbrazas.bo/dashboard?tab=menu' : 'Flutter Android/iOS App · API /api/v1/orders' }}
              </code>
              <span class="text-[10px] font-bold text-orange-600 dark:text-orange-400">Pantalla Comentada</span>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
              <!-- Visual Diagram -->
              <div class="lg:col-span-7">
                <div v-if="activeVisualClientTab === 'desktop'" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-950 text-slate-100 p-4 space-y-4">
                  <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2 text-xs font-bold">
                      <span class="w-5 h-5 rounded-full bg-orange-600 text-white text-[10px] inline-flex items-center justify-center">①</span>
                      <span>7 Pestañas del Cliente:</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5 text-[10px] font-semibold">
                      <span class="px-2 py-0.5 rounded bg-orange-600 text-white">Carta (menu)</span>
                      <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300">Carrito</span>
                      <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300">Pedidos</span>
                      <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300">Promos</span>
                      <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300">Reservas</span>
                      <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300">Chef IA</span>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                      <div class="flex justify-between items-center text-xs font-bold">
                        <span>Bife de Chorizo 400g</span>
                        <span class="w-5 h-5 rounded-full bg-orange-600 text-white text-[10px] inline-flex items-center justify-center">②</span>
                      </div>
                      <p class="text-[10px] text-slate-400">Corte premium a las brazas + guarnición</p>
                      <div class="flex items-center justify-between pt-1">
                        <span class="text-xs font-black text-amber-400">Bs. 115</span>
                        <span class="px-2 py-0.5 rounded bg-orange-600 text-[10px] font-bold">+ Agregar</span>
                      </div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                      <div class="flex justify-between items-center text-xs font-bold">
                        <span>Parrillada Las Brazas</span>
                        <span class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] inline-flex items-center justify-center">③</span>
                      </div>
                      <p class="text-[10px] text-slate-400">Aplica cupón BRAZAS15 (-15%)</p>
                      <div class="flex items-center justify-between pt-1">
                        <span class="text-xs font-black text-amber-400">Bs. 240</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-600 text-[10px] font-bold">Checkout</span>
                      </div>
                    </div>
                  </div>
                  <div class="p-3 rounded-xl bg-slate-900 border border-orange-500/30 flex items-center justify-between text-xs">
                    <span class="flex items-center gap-2">
                      <span class="w-5 h-5 rounded-full bg-sky-600 text-white text-[10px] inline-flex items-center justify-center">④</span>
                      Timeline Pedido #1042: <strong>En Cocina</strong> · PIN Entrega: <strong class="text-amber-400">4829</strong>
                    </span>
                    <span class="px-2 py-0.5 rounded bg-sky-500/20 text-sky-300 text-[10px] font-bold">En vivo</span>
                  </div>
                </div>

                <!-- Mobile Mockup -->
                <div v-else class="max-w-xs mx-auto rounded-[2rem] border-4 border-slate-800 bg-slate-950 text-white p-4 space-y-3 shadow-xl">
                  <div class="w-16 h-1.5 rounded-full bg-slate-800 mx-auto"></div>
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-black text-orange-400">Las Brazas App</span>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px]">Sanctum OK</span>
                  </div>
                  <div class="p-3 rounded-2xl bg-gradient-to-r from-orange-600 to-amber-500 text-xs space-y-1">
                    <div class="font-bold flex items-center justify-between">
                      <span>Estado de tu Pedido #1042</span>
                      <span class="w-5 h-5 rounded-full bg-slate-950 text-white text-[10px] inline-flex items-center justify-center">①</span>
                    </div>
                    <p class="text-[11px] opacity-90">En camino con Repartidor · Tu PIN: <strong>4829</strong></p>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 text-[11px] space-y-1.5">
                    <div class="font-bold flex justify-between">
                      <span>② Dirección & Facturación NIT/CI</span>
                    </div>
                    <p class="text-slate-400">Av. Las Brazas #120 · NIT: 4589120</p>
                  </div>
                  <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 text-[11px] space-y-1.5">
                    <div class="font-bold">③ Botón Flotante Chef Virtual IA</div>
                    <p class="text-slate-400">"¿Qué corte recomiendan para 4 personas?"</p>
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
                      <th class="py-2 font-bold">Acción en el Sistema</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr>
                      <td class="py-2.5 pr-2 font-black text-orange-600">①</td>
                      <td class="py-2.5 pr-2 font-semibold">Pestañas Comensal</td>
                      <td class="py-2.5 text-slate-600 dark:text-slate-400">Navega entre Carta, Carrito, Pedidos, Promociones, Reservas, Asistente IA y Perfil.</td>
                    </tr>
                    <tr>
                      <td class="py-2.5 pr-2 font-black text-orange-600">②</td>
                      <td class="py-2.5 pr-2 font-semibold">Tarjeta de Plato</td>
                      <td class="py-2.5 text-slate-600 dark:text-slate-400">Permite personalizar término de cocción/notas y sumar al carrito en un clic.</td>
                    </tr>
                    <tr>
                      <td class="py-2.5 pr-2 font-black text-emerald-600">③</td>
                      <td class="py-2.5 pr-2 font-semibold">Validador de Cupón</td>
                      <td class="py-2.5 text-slate-600 dark:text-slate-400">Consulta <code class="font-mono">/api/v1/coupons/validate</code> y descuenta porcentaje o monto fijo antes de confirmar.</td>
                    </tr>
                    <tr>
                      <td class="py-2.5 pr-2 font-black text-sky-600">④</td>
                      <td class="py-2.5 pr-2 font-semibold">Timeline + PIN Delivery</td>
                      <td class="py-2.5 text-slate-600 dark:text-slate-400">Muestra progreso en vivo y el PIN secreto de 4 dígitos para entregar al repartidor.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 6: VISUAL POS & YOLO FLOOR PLAN -->
        <section id="visual-pos" class="scroll-mt-24 space-y-5">
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <UtensilsCrossed class="w-6 h-6 text-orange-500" />
              6. Guía Visual: POS de Salón y Detección de Planos YOLO
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Operación conjunta de Mesero (<code class="font-mono text-xs">/pos</code>) y Recepcionista (<code class="font-mono text-xs">/floor-plans</code>).</p>
          </div>

          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start shadow-sm">
            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-950 text-white p-4 space-y-3">
                <div class="text-xs font-bold text-orange-400 flex items-center justify-between">
                  <span>POS Salón (/pos)</span>
                  <span class="px-2 py-0.5 rounded bg-orange-500/20 text-[10px]">① Selector Mesa</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-[11px] font-bold">
                  <div class="p-2 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-emerald-300">M1 Libre</div>
                  <div class="p-2 rounded-lg bg-rose-500/20 border border-rose-500/40 text-rose-300">M2 Ocup.</div>
                  <div class="p-2 rounded-lg bg-amber-500/20 border border-amber-500/40 text-amber-300">M3 Res.</div>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-[11px] space-y-1">
                  <div class="font-bold text-slate-200">② Modalidad Comanda</div>
                  <div class="flex gap-1.5 text-[10px]">
                    <span class="px-2 py-0.5 rounded bg-orange-600">En Local</span>
                    <span class="px-2 py-0.5 rounded bg-slate-800">Takeaway</span>
                    <span class="px-2 py-0.5 rounded bg-slate-800">Delivery</span>
                  </div>
                </div>
              </div>

              <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-950 text-white p-4 space-y-3">
                <div class="text-xs font-bold text-sky-400 flex items-center justify-between">
                  <span>Planos IA (/floor-plans)</span>
                  <span class="px-2 py-0.5 rounded bg-sky-500/20 text-[10px]">③ YOLO Vision</span>
                </div>
                <div class="h-24 rounded-xl border border-dashed border-sky-500/40 bg-slate-900 flex items-center justify-around p-2">
                  <div class="w-10 h-10 rounded border-2 border-emerald-400 text-[9px] flex items-center justify-center font-mono text-emerald-300">98% M1</div>
                  <div class="w-12 h-10 rounded border-2 border-sky-400 text-[9px] flex items-center justify-center font-mono text-sky-300">95% M2</div>
                  <div class="w-10 h-10 rounded border-2 border-amber-400 text-[9px] flex items-center justify-center font-mono text-amber-300">94% M3</div>
                </div>
                <div class="text-[10px] text-slate-400">④ Botón: <strong class="text-white">Sincronizar con Mesas</strong> guarda coordenadas y capacidad.</div>
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
                    <td class="py-2.5 pr-2 font-semibold">Tipo de Orden</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">Soporta <code class="font-mono">dine_in</code>, <code class="font-mono">takeaway</code> y <code class="font-mono">delivery</code> en un único carrito.</td>
                  </tr>
                  <tr>
                    <td class="py-2.5 pr-2 font-black text-sky-600">③</td>
                    <td class="py-2.5 pr-2 font-semibold">Detector YOLO /floor-plans</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">Reconoce automáticamente mesas sobre plano cargado (incluye plano de ejemplo).</td>
                  </tr>
                  <tr>
                    <td class="py-2.5 pr-2 font-black text-sky-600">④</td>
                    <td class="py-2.5 pr-2 font-semibold">Optimizar & Sincronizar</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">Llama a <code class="font-mono">/optimize</code> y <code class="font-mono">/sync-tables</code> para actualizar inventario de mesas.</td>
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
              <p class="text-sm text-slate-600 dark:text-slate-400">Pantallas operativas de Chef (<code class="font-mono text-xs">/kitchen</code>) y Repartidor (<code class="font-mono text-xs">/delivery</code>).</p>
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
              <div v-if="activeVisualOpsTab === 'kds'" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-950 text-white p-4 space-y-3">
                <div class="flex items-center justify-between text-xs border-b border-slate-800 pb-2">
                  <span class="font-bold text-amber-400">① Auto-refresco cada 10s · Filtros: all / delivery / pending / in_kitchen / ready</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                  <div class="p-3 rounded-xl bg-slate-900 border-l-4 border-emerald-500 space-y-1.5">
                    <div class="flex justify-between font-bold">
                      <span>Comanda #1045 · Mesa 4</span>
                      <span class="text-emerald-400">② 06:40</span>
                    </div>
                    <p class="text-[11px] text-slate-400">2x Bife Ancho (Término Medio)</p>
                    <span class="inline-block px-2 py-0.5 rounded bg-orange-600 text-[10px] font-bold">Marcar Listo</span>
                  </div>
                  <div class="p-3 rounded-xl bg-slate-900 border-l-4 border-rose-500 space-y-1.5">
                    <div class="flex justify-between font-bold">
                      <span>Comanda #1039 · Delivery</span>
                      <span class="text-rose-400">③ 21:15 ⚠️</span>
                    </div>
                    <p class="text-[11px] text-slate-400">1x Parrillada Familiar + Chorizo</p>
                    <span class="inline-block px-2 py-0.5 rounded bg-rose-600 text-[10px] font-bold">Despacho Urgente</span>
                  </div>
                </div>
              </div>

              <div v-else class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-950 text-white p-4 space-y-3">
                <div class="flex items-center justify-between text-xs border-b border-slate-800 pb-2">
                  <span class="font-bold text-violet-400">Modal Verificación Entrega (RF-19) — Pedido #1042</span>
                  <span class="text-[10px] px-2 py-0.5 rounded bg-violet-500/20 text-violet-300">En Camino</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                  <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                    <div class="font-bold text-slate-200">① PIN de Seguridad (4 dígitos)</div>
                    <div class="flex gap-2 justify-center py-1">
                      <span class="w-8 h-9 rounded-lg bg-slate-800 border border-violet-500/50 flex items-center justify-center font-mono font-bold text-base">4</span>
                      <span class="w-8 h-9 rounded-lg bg-slate-800 border border-violet-500/50 flex items-center justify-center font-mono font-bold text-base">8</span>
                      <span class="w-8 h-9 rounded-lg bg-slate-800 border border-violet-500/50 flex items-center justify-center font-mono font-bold text-base">2</span>
                      <span class="w-8 h-9 rounded-lg bg-slate-800 border border-violet-500/50 flex items-center justify-center font-mono font-bold text-base">9</span>
                    </div>
                  </div>
                  <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                    <div class="font-bold text-slate-200">② Firma Digital en Canvas</div>
                    <div class="h-12 rounded-lg bg-white flex items-center justify-center text-slate-800 italic font-serif text-sm border border-dashed border-slate-400">
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
                    <th class="py-2 pr-2 font-bold">Módulo</th>
                    <th class="py-2 pr-2 font-bold">Regla Crítica</th>
                    <th class="py-2 font-bold">Efecto</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr>
                    <td class="py-2.5 pr-2 font-bold text-amber-600">KDS /kitchen</td>
                    <td class="py-2.5 pr-2">Refresco 10s & Cronómetro</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">&lt;12m verde, 12–19m ámbar, ≥20m rojo crítico.</td>
                  </tr>
                  <tr>
                    <td class="py-2.5 pr-2 font-bold text-violet-600">Delivery</td>
                    <td class="py-2.5 pr-2">3 Pestañas de Flujo</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400"><code class="font-mono">pending</code> (disponible), <code class="font-mono">mine</code> (en ruta), <code class="font-mono">completed</code> (cerrado).</td>
                  </tr>
                  <tr>
                    <td class="py-2.5 pr-2 font-bold text-emerald-600">Seguridad RF-19</td>
                    <td class="py-2.5 pr-2">PIN + Firma Canvas</td>
                    <td class="py-2.5 text-slate-600 dark:text-slate-400">Impide cerrar la entrega sin validar ambos datos presencialmente.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION 8: VISUAL CHATBOT AI & BI REPORTS -->
        <section id="visual-ia" class="scroll-mt-24 space-y-5">
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <Bot class="w-6 h-6 text-orange-500" />
              8. Guía Visual: Chef Virtual IA (ChatBot) y Reportes Inteligentes BI
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Integración conversacional con Gemini AI (<code class="font-mono text-xs">AiChatSidebar.vue</code> y <code class="font-mono text-xs">/reports</code>).</p>
          </div>

          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 grid grid-cols-1 lg:grid-cols-2 gap-6 shadow-sm">
            <!-- Chatbot Box -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-950 text-white p-5 space-y-3">
              <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl bg-orange-600 flex items-center justify-center">
                    <Sparkles class="w-4 h-4" />
                  </div>
                  <div>
                    <div class="text-xs font-extrabold">Chef Virtual Las Brazas</div>
                    <div class="text-[10px] text-emerald-400">En línea · Contexto dinámico Rol Cliente / Staff</div>
                  </div>
                </div>
                <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] font-mono">/ai/chat</span>
              </div>
              <div class="space-y-2 text-xs">
                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800">
                  "¡Hola! Soy el Chef Virtual IA. Puedo sugerirte cortes a la parrilla, aplicar cupones al carrito o verificar disponibilidad de mesas en tiempo real."
                </div>
                <div class="p-2.5 rounded-xl bg-orange-600/20 border border-orange-500/30 text-orange-200 text-[11px]">
                  <strong>Funciones integradas:</strong> Dictado por micrófono (<code class="font-mono">toggleVoiceChat</code>), botones rápidos de acción (añadir plato / crear reserva) y asistencia operativa para el personal.
                </div>
              </div>
            </div>

            <!-- Reports BI + Predictions Box -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 p-5 space-y-3">
              <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2 text-sm font-extrabold text-slate-900 dark:text-white">
                  <BarChart3 class="w-4 h-4 text-orange-500" /> Reportes BI (/reports) & Predicciones (/predictions)
                </div>
              </div>
              <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                <li class="flex items-start gap-2">
                  <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span><strong>Exportación multiformato:</strong> Descarga informes gerenciales en <strong>PDF, Excel y CSV</strong> filtrando por rango de fechas.</span>
                </li>
                <li class="flex items-start gap-2">
                  <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span><strong>Consulta NLP + Voz:</strong> El Administrador pregunta en español natural (ej. "¿Qué plato se vendió más el viernes?") y Gemini interpreta los KPIs reales.</span>
                </li>
                <li class="flex items-start gap-2">
                  <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span><strong>Pronóstico de Demanda:</strong> El módulo <code class="font-mono">/predictions</code> proyecta consumo de platos e insumos para evitar quiebres de stock.</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- PARTE 3: INFRASTRUCTURE & REQUIREMENTS -->
        <section id="requisitos" class="scroll-mt-24 space-y-5">
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <KeyRound class="w-6 h-6 text-orange-500" />
              9. Requisitos del Sistema, Despliegue AWS y Subdominio Dedicado
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              Especificaciones técnicas alineadas al Capítulo 11 (Infraestructura AWS EC2 <code class="font-mono text-xs">c7i-flex.large</code>).
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 space-y-3 shadow-sm">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">Requisitos Mínimos Cliente & Estación POS</h3>
              <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2 leading-relaxed">
                <li><strong>• Navegador Web / POS / KDS:</strong> Chrome, Edge o Firefox actualizado (soporte ES2022, Canvas táctil y Web Speech API para dictado).</li>
                <li><strong>• App Móvil Comensal:</strong> Android 9+ / iOS 14+ compilada en Flutter, conectada vía API REST HTTPS + Laravel Sanctum.</li>
                <li><strong>• Servidor Producción:</strong> Instancia AWS EC2 <code class="font-mono">c7i-flex.large</code> (2 vCPU, 4 GB RAM, 30 GB EBS gp3) + Nginx + PHP 8.2 + MySQL/PostgreSQL.</li>
              </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 space-y-3 shadow-sm">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">Rutas de Acceso al Manual y Subdominio</h3>
              <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2 leading-relaxed">
                <li><strong>• Subdominio Dedicado:</strong> <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-orange-600 dark:text-orange-400">{{ manualSubdomainUrl || 'https://manual.tudominio.com/' }}</code> — Enrutado automáticamente por <code class="font-mono">ManualController::isManualHost()</code> sin servidor adicional.</li>
                <li><strong>• Ruta Integrada:</strong> <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono">/manual</code> accesible desde el pie de página del portal principal.</li>
                <li><strong>• Descarga PDF Directa:</strong> <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono">/manual/pdf</code> (o <code class="font-mono">/pdf</code> en el subdominio) para lectura offline y enlace QR.</li>
              </ul>
            </div>
          </div>
        </section>

        <!-- SECTION 10: TROUBLESHOOTING FAQ -->
        <section id="soporte" class="scroll-mt-24 space-y-5">
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
              <HelpCircle class="w-6 h-6 text-orange-500" />
              10. Solución de Problemas Frecuentes (FAQ)
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">Respuestas rápidas ante incidencias operativas comunes.</p>
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
