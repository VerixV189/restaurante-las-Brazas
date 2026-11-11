<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatService
{
    /**
     * Procesa el mensaje del usuario y genera una respuesta inteligente, contextualizada y accionable.
     */
    public function generateReply(User $user, string $message, array $history = []): string
    {
        $message = trim($message);

        // 1. Recopilar contexto en tiempo real (personalizado según rol: Staff vs Cliente)
        $context = $this->gatherUserContext($user);

        // 2. Verificar si el usuario solicita una acción transaccional directa (ej. crear reserva por chat)
        $actionReply = $this->tryExecuteConversationalAction($user, $message, $context);
        if ($actionReply !== null) {
            return $actionReply;
        }

        // 3. Intentar usar Google Gemini API directamente si GEMINI_API_KEY está configurada en .env
        $geminiReply = $this->tryDirectGeminiApi($user, $message, $context, $history);
        if ($geminiReply !== null) {
            return $geminiReply;
        }

        // 4. Intentar consultar el microservicio FastAPI si está activo
        $externalReply = $this->tryExternalAiService($user, $message, $context, $history);
        if ($externalReply !== null) {
            return $externalReply;
        }

        // 5. Motor avanzado de inteligencia en PHP con consultas en vivo a PostgreSQL
        return $this->smartProcessQuery($user, $message, $context);
    }

    /**
     * Recopila datos en tiempo real según el rol del usuario autenticado.
     */
    private function gatherUserContext(User $user): array
    {
        $roleName = $user->role?->name ?? 'Cliente';
        $isStaff  = in_array($roleName, ['Administrador', 'Chef', 'Mesero', 'Recepcionista']);

        // Pedidos del usuario
        $orders = Order::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereHas('customer', function ($cq) use ($user) {
                  $cq->where('email', $user->email);
              });
        })
        ->with(['items.dish', 'table', 'payments'])
        ->latest()
        ->take(6)
        ->get();

        // Reservaciones del usuario
        $reservations = Reservation::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereHas('customer', function ($cq) use ($user) {
                  $cq->where('email', $user->email);
              });
        })
        ->with(['table.area'])
        ->latest('reservation_date')
        ->take(6)
        ->get();

        // Menú completo con recetas e insumos
        $categories = Category::with(['dishes' => function ($q) {
            $q->where('is_available', true)->with('recipe.ingredients.ingredient');
        }])->get();

        // Mesas y zonas en tiempo real
        $areas = Area::with('tables')->get();

        // Cupones promocionales activos
        $coupons = Coupon::where('is_active', true)->get();

        $staffMetrics = [];
        if ($isStaff) {
            $todaySales = (float) Order::where('status', 'paid')->whereDate('created_at', today())->sum('total_amount');
            $weekSales  = (float) Order::where('status', 'paid')->where('created_at', '>=', now()->subDays(7))->sum('total_amount');
            $totalSales = (float) Order::where('status', 'paid')->sum('total_amount');
            $paidCount  = Order::where('status', 'paid')->count();
            $avgTicket  = $paidCount > 0 ? round($totalSales / $paidCount, 2) : 0;

            $lowStock = Ingredient::with('supplier')
                ->whereColumn('current_stock', '<=', 'minimum_stock')
                ->get();

            $activeOrders = Order::with(['table', 'customer', 'items.dish'])
                ->whereIn('status', ['pending', 'in_kitchen', 'ready'])
                ->orderBy('created_at', 'asc')
                ->get();

            $topDishes = OrderItem::select('dish_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_rev'))
                ->whereHas('order', fn($q) => $q->where('status', 'paid'))
                ->with('dish')
                ->groupBy('dish_id')
                ->orderByDesc('total_rev')
                ->take(5)
                ->get();

            $staffMetrics = [
                'today_sales'   => $todaySales,
                'week_sales'    => $weekSales,
                'total_sales'   => $totalSales,
                'paid_count'    => $paidCount,
                'avg_ticket'    => $avgTicket,
                'low_stock'     => $lowStock,
                'active_orders' => $activeOrders,
                'top_dishes'    => $topDishes,
            ];
        }

        return [
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'role'     => $roleName,
                'is_staff' => $isStaff,
            ],
            'orders'        => $orders,
            'reservations'  => $reservations,
            'categories'    => $categories,
            'areas'         => $areas,
            'coupons'       => $coupons,
            'staff_metrics' => $staffMetrics,
        ];
    }

    /**
     * Ejecuta acciones transaccionales cuando el usuario pide realizar una acción por chat
     * (Ej: "Quiero reservar una mesa para 4 personas mañana a las 20:00").
     */
    private function tryExecuteConversationalAction(User $user, string $message, array $context): ?string
    {
        $q = mb_strtolower($message, 'UTF-8');

        // Detectar intención de CREAR una reserva ("reservar mesa para X personas", "hazme una reserva para X")
        if (
            (str_contains($q, 'reservar') || str_contains($q, 'hazme una reserva') || str_contains($q, 'agenda una mesa') || str_contains($q, 'quiero una mesa')) &&
            preg_match('/(\d+)\s*(persona|comensal|gente|p\b)/u', $q, $matches)
        ) {
            $guestCount = max(1, min(20, (int) $matches[1]));

            // Determinar fecha (hoy o mañana) y hora
            $targetDate = str_contains($q, 'mañana') ? now()->addDay() : now();
            if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(pm|am|hrs|horas)?/u', $q, $timeMatch)) {
                $hour = (int) $timeMatch[1];
                $min  = isset($timeMatch[2]) && $timeMatch[2] !== '' ? (int) $timeMatch[2] : 0;
                if ($hour >= 1 && $hour <= 11 && (str_contains($q, 'noche') || str_contains($q, 'cena') || ($timeMatch[3] ?? '') === 'pm')) {
                    $hour += 12;
                }
                if ($hour >= 12 && $hour <= 23) {
                    $targetDate->setHour($hour)->setMinute($min)->setSecond(0);
                } else {
                    $targetDate->setHour(20)->setMinute(0)->setSecond(0);
                }
            } else {
                $targetDate->setHour(20)->setMinute(0)->setSecond(0);
            }

            // Buscar mesa disponible con capacidad suficiente
            $table = Table::with('area')
                ->where('capacity', '>=', $guestCount)
                ->orderByRaw("CASE WHEN status = 'available' THEN 0 ELSE 1 END")
                ->orderBy('capacity', 'asc')
                ->first();

            if (!$table) {
                return "⚠️ Busqué en el mapa del restaurante pero no encontré una mesa individual con capacidad para **{$guestCount} personas**. Te sugiero contactar a recepción para unir dos mesas en **Salón VIP & Cava**.";
            }

            $customer = Customer::firstOrCreate(
                ['email' => $user->email],
                ['name' => $user->name, 'phone' => $user->phone ?? 'Sin teléfono']
            );

            $reservation = Reservation::create([
                'customer_id'      => $customer->id,
                'table_id'         => $table->id,
                'area_id'          => $table->area_id,
                'user_id'          => $user->id,
                'reservation_date' => $targetDate,
                'guest_count'      => $guestCount,
                'children_count'   => 0,
                'status'           => 'confirmed',
                'special_requests' => 'Reserva creada automáticamente por el Agente IA',
            ]);

            return "✅ **¡Reserva Creada Exitosamente por el Agente IA!** 🎉\n\n"
                 . "• **Folio de Reserva:** #{$reservation->id}\n"
                 . "• **Comensal:** {$user->name}\n"
                 . "• **Mesa Asignada:** {$table->table_number} ({$table->area?->name})\n"
                 . "• **Capacidad:** {$guestCount} personas (Aforo de mesa: {$table->capacity}p)\n"
                 . "• **Fecha y Hora:** " . $targetDate->format('d/m/Y H:i') . " hrs\n"
                 . "• **Estado:** Confirmada\n\n"
                 . "⏱️ *Recuerda que tienes 15 minutos de tolerancia. Ya puedes ver tu reserva reflejada en el módulo de Reservaciones.*";
        }

        return null;
    }

    /**
     * Conexión directa con Google Gemini API (gemini-2.5-flash) con contexto RAG completo.
     */
    private function tryDirectGeminiApi(User $user, string $message, array $context, array $history): ?string
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            return null;
        }

        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $systemPrompt = $this->buildRagSystemPrompt($context);

        try {
            $contents = [];
            foreach (array_slice($history, -6) as $h) {
                if (!empty($h['content'])) {
                    $contents[] = [
                        'role'  => ($h['role'] ?? 'user') === 'assistant' ? 'model' : 'user',
                        'parts' => [['text' => $h['content']]],
                    ];
                }
            }
            $contents[] = [
                'role'  => 'user',
                'parts' => [['text' => $message]],
            ];

            $response = Http::timeout(10)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature'     => 0.4,
                        'maxOutputTokens' => 900,
                    ],
                ]);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');
                if (!empty($text)) {
                    return trim($text);
                }
            }
        } catch (\Throwable $e) {
            Log::info('Direct Gemini API fallback: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Construye el System Prompt enriquecido (RAG) con datos reales de la base de datos.
     */
    private function buildRagSystemPrompt(array $context): string
    {
        $u = $context['user'];
        $menuLines = [];
        foreach ($context['categories'] as $cat) {
            foreach ($cat->dishes as $d) {
                $menuLines[] = "- {$d->name} ({$cat->name}): $" . number_format($d->price, 2) . " | Prep: {$d->prep_time_minutes}m | {$d->description}";
            }
        }

        $prompt = "Eres el Agente IA Oficial y Chef Ejecutivo Virtual de 'Restaurante Las Brazas' (Steakhouse & Parrilla).\n"
                . "Usuario actual: {$u['name']} (Rol: {$u['role']}). Responde siempre en español con formato Markdown claro y conciso.\n\n"
                . "CARTA ACTUAL EN VIVO:\n" . implode("\n", array_slice($menuLines, 0, 35)) . "\n\n";

        if ($u['is_staff'] && !empty($context['staff_metrics'])) {
            $m = $context['staff_metrics'];
            $lowNames = $m['low_stock']->map(fn($i) => "{$i->name} ({$i->current_stock}/{$i->minimum_stock} {$i->unit_of_measure})")->implode(', ');
            $prompt .= "MÉTRICAS EJECUTIVAS EN TIEMPO REAL:\n"
                    . "- Ventas Hoy: $" . number_format($m['today_sales'], 2) . " | Ventas 7 días: $" . number_format($m['week_sales'], 2) . " | Histórico: $" . number_format($m['total_sales'], 2) . "\n"
                    . "- Ticket Promedio: $" . number_format($m['avg_ticket'], 2) . " ({$m['paid_count']} órdenes pagadas)\n"
                    . "- Comandas activas en cocina/delivery: " . $m['active_orders']->count() . "\n"
                    . "- Alertas de stock crítico: " . ($lowNames ?: 'Ninguna') . "\n";
        }

        return $prompt;
    }

    /**
     * Intenta conectar con el microservicio externo FastAPI / Gemini.
     */
    private function tryExternalAiService(User $user, string $message, array $context, array $history): ?string
    {
        $fastApiUrl = config('services.fastapi_ai.url', 'http://127.0.0.1:8001');

        try {
            $response = Http::timeout(4)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$fastApiUrl}/api/AI/chat/", [
                    'prompt'             => $message,
                    'user'               => $context['user'],
                    'orders_count'       => $context['orders']->count(),
                    'reservations_count' => $context['reservations']->count(),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['reply'])) {
                    return $data['reply'];
                }
            }
        } catch (\Throwable $e) {
            // El microservicio no está disponible, continuar con el motor inteligente local
        }

        return null;
    }

    /**
     * Motor conversacional avanzado en PHP con acceso en tiempo real a ventas, inventario, recetas, KDS, mesas y menú.
     */
    private function smartProcessQuery(User $user, string $query, array $context): string
    {
        $q        = mb_strtolower($query, 'UTF-8');
        $userName = explode(' ', $user->name)[0];
        $isStaff  = $context['user']['is_staff'];

        // ─────────────────────────────────────────────────────────────────────────────
        // 1. INTELIGENCIA EJECUTIVA: VENTAS, KPIS, INGRESOS Y TOP PLATILLOS (STAFF)
        // ─────────────────────────────────────────────────────────────────────────────
        if (
            $isStaff && (
                str_contains($q, 'venta') || str_contains($q, 'kpi') ||
                str_contains($q, 'ingreso') || str_contains($q, 'ganancia') ||
                str_contains($q, 'resumen') || str_contains($q, 'más vendido') ||
                str_contains($q, 'mas vendido') || str_contains($q, 'ticket promedio') ||
                str_contains($q, 'como vamos') || str_contains($q, 'cómo vamos')
            )
        ) {
            return $this->buildExecutiveSalesReply($context['staff_metrics'], $userName);
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 2. RENTABILIDAD, COSTOS Y RECETAS / FICHAS TÉCNICAS
        // ─────────────────────────────────────────────────────────────────────────────
        if (
            str_contains($q, 'rentab') || str_contains($q, 'margen') ||
            str_contains($q, 'receta') || str_contains($q, 'ingrediente') ||
            str_contains($q, 'ficha técnica') || str_contains($q, 'ficha tecnica') ||
            str_contains($q, 'como se prepara') || str_contains($q, 'cómo se prepara') ||
            str_contains($q, 'costo de produccion') || str_contains($q, 'costo de producción')
        ) {
            return $this->buildProfitabilityAndRecipesReply($context['categories'], $q, $isStaff);
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 3. INVENTARIO, STOCK CRÍTICO, INSUMOS, PROVEEDORES Y MERMAS (STAFF)
        // ─────────────────────────────────────────────────────────────────────────────
        if (
            $isStaff && (
                str_contains($q, 'stock') || str_contains($q, 'inventario') ||
                str_contains($q, 'insumo') || str_contains($q, 'merma') ||
                str_contains($q, 'faltante') || str_contains($q, 'agotad') ||
                str_contains($q, 'proveedor') || str_contains($q, 'comprar')
            )
        ) {
            return $this->buildInventoryAndWasteReply($context['staff_metrics']);
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 4. ESTADO DE COCINA (KDS) Y PEDIDOS DE APP MÓVIL / DELIVERY EN VIVO (STAFF)
        // ─────────────────────────────────────────────────────────────────────────────
        if (
            $isStaff && (
                str_contains($q, 'cocina') || str_contains($q, 'kds') ||
                str_contains($q, 'delivery') || str_contains($q, 'domicilio') ||
                str_contains($q, 'comandas activas') || str_contains($q, 'pedidos app') ||
                str_contains($q, 'para llevar') || str_contains($q, 'en fuego')
            )
        ) {
            return $this->buildLiveKitchenAndDeliveryReply($context['staff_metrics']['active_orders']);
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 5. DISPONIBILIDAD DE MESAS Y ZONAS EN TIEMPO REAL
        // ─────────────────────────────────────────────────────────────────────────────
        if (
            str_contains($q, 'mesas libres') || str_contains($q, 'disponibilidad') ||
            str_contains($q, 'qué mesas') || str_contains($q, 'que mesas') ||
            str_contains($q, 'zonas') || str_contains($q, 'terraza') ||
            str_contains($q, 'salón vip') || str_contains($q, 'salon vip') ||
            str_contains($q, 'ocupada')
        ) {
            return $this->buildTablesAvailabilityReply($context['areas']);
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 6. CUPONES, DESCUENTOS Y PROMOCIONES ACTIVAS
        // ─────────────────────────────────────────────────────────────────────────────
        if (
            str_contains($q, 'cupon') || str_contains($q, 'cupón') ||
            str_contains($q, 'descuento') || str_contains($q, 'promo') ||
            str_contains($q, 'oferta') || str_contains($q, 'envio gratis') ||
            str_contains($q, 'envío gratis')
        ) {
            return $this->buildCouponsReply($context['coupons']);
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 7. CONSULTA DE PEDIDOS PERSONALES DEL USUARIO
        // ─────────────────────────────────────────────────────────────────────────────
        if (
            str_contains($q, 'pedido') || str_contains($q, 'orden') ||
            str_contains($q, 'comanda') || str_contains($q, 'cuenta') ||
            str_contains($q, 'he pedido') || str_contains($q, 'mis compras')
        ) {
            if ($isStaff && !str_contains($q, 'mis')) {
                return $this->buildLiveKitchenAndDeliveryReply($context['staff_metrics']['active_orders']);
            }
            return $this->buildOrdersReply($context['orders'], $userName);
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 8. CONSULTA DE RESERVACIONES Y POLÍTICAS DE TOLERANCIA
        // ─────────────────────────────────────────────────────────────────────────────
        if (
            str_contains($q, 'reserva') || str_contains($q, 'mesa') ||
            str_contains($q, 'tolerancia') || str_contains($q, 'hora de llegada') ||
            str_contains($q, 'reservé') || str_contains($q, 'reservacion')
        ) {
            if (str_contains($q, 'tolerancia') || str_contains($q, 'tiempo') || str_contains($q, 'tarde') || str_contains($q, 'esperan')) {
                return "⏱️ **Política de Tolerancia en Las Brazas:**\n\n"
                     . "Mantenemos tu mesa reservada con una tolerancia estricta de **15 minutos** a partir de la hora solicitada.\n"
                     . "Pasado ese tiempo sin previo aviso, la mesa se libera automáticamente para otros comensales en lista de espera.\n\n"
                     . "💡 *También puedes pedirme aquí mismo: **\"Reserva una mesa para 4 personas mañana a las 20:00\"** y la agendaré automáticamente.*";
            }

            return $this->buildReservationsReply($context['reservations'], $userName, $isStaff);
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 9. MENÚ, CORTES, PRECIOS, PRESUPUESTO Y MARIDAJE
        // ─────────────────────────────────────────────────────────────────────────────
        if (
            str_contains($q, 'menu') || str_contains($q, 'menú') ||
            str_contains($q, 'plato') || str_contains($q, 'platillo') ||
            str_contains($q, 'corte') || str_contains($q, 'carne') ||
            str_contains($q, 'precio') || str_contains($q, 'costo') ||
            str_contains($q, 'recomiend') || str_contains($q, 'especialidad') ||
            str_contains($q, 'arrachera') || str_contains($q, 'bife') ||
            str_contains($q, 'picaña') || str_contains($q, 'picanha') ||
            str_contains($q, 'tomahawk') || str_contains($q, 'ojo de bife') ||
            str_contains($q, 'hamburguesa') || str_contains($q, 'wagyu') ||
            str_contains($q, 'chorizo') || str_contains($q, 'costilla') ||
            str_contains($q, 'vino') || str_contains($q, 'maridaje') ||
            str_contains($q, 'bebida') || str_contains($q, 'postre') ||
            str_contains($q, 'carta') || str_contains($q, 'presupuesto') ||
            str_contains($q, 'barato') || str_contains($q, 'económico')
        ) {
            return $this->buildMenuReply($context['categories'], $q, $userName);
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 10. HORARIOS Y MÉTODOS DE PAGO
        // ─────────────────────────────────────────────────────────────────────────────
        if (str_contains($q, 'horario') || str_contains($q, 'hora') || str_contains($q, 'abierto') || str_contains($q, 'atienden')) {
            return "🕒 **Horarios de Atención en Restaurante Las Brazas:**\n\n"
                 . "• **Lunes a Jueves:** 12:00 - 22:30\n"
                 . "• **Viernes y Sábados:** 12:00 - 00:00\n"
                 . "• **Domingos y Feriados:** 12:00 - 21:30\n\n"
                 . "🔥 *Contamos con servicio en Salón, Para Llevar (Takeaway) y Envío a Domicilio (Delivery).*";
        }

        if (str_contains($q, 'pago') || str_contains($q, 'tarjeta') || str_contains($q, 'qr') || str_contains($q, 'transferencia') || str_contains($q, 'factura')) {
            return "💳 **Métodos de Pago Aceptados:**\n\n"
                 . "• **Efectivo** (Moneda Nacional)\n"
                 . "• **Tarjetas de Débito y Crédito** (Visa, Mastercard)\n"
                 . "• **Cobro rápido por Código QR Simple** (Banca Móvil)\n"
                 . "• **Transferencias Bancarias**\n\n"
                 . "🧾 *Emitimos factura electrónica con NIT/CI tanto en salón como en pedidos por la App Móvil.*";
        }

        // ─────────────────────────────────────────────────────────────────────────────
        // 11. SALUDOS Y AYUDA CONTEXTUAL SEGÚN ROL
        // ─────────────────────────────────────────────────────────────────────────────
        return $this->buildWelcomeOrFallbackReply($userName, $context['user']['role'], $isStaff, $query);
    }

    /**
     * Respuesta ejecutiva de ventas y KPIs en tiempo real para Administradores y Chefs.
     */
    private function buildExecutiveSalesReply(array $metrics, string $userName): string
    {
        $reply = "📊 **Reporte Ejecutivo en Tiempo Real para {$userName}:**\n\n"
               . "• **Ventas de Hoy:** $" . number_format($metrics['today_sales'], 2) . "\n"
               . "• **Ventas Últimos 7 Días:** $" . number_format($metrics['week_sales'], 2) . "\n"
               . "• **Ingresos Históricos Totales:** $" . number_format($metrics['total_sales'], 2) . "\n"
               . "• **Órdenes Pagadas:** {$metrics['paid_count']} comandas\n"
               . "• **Ticket Promedio:** $" . number_format($metrics['avg_ticket'], 2) . "\n\n";

        if ($metrics['top_dishes']->isNotEmpty()) {
            $reply .= "### 🏆 Top 5 Platillos Más Vendidos\n";
            foreach ($metrics['top_dishes'] as $idx => $item) {
                $name = $item->dish?->name ?? 'Platillo';
                $reply .= ($idx + 1) . ". **{$name}** — {$item->total_qty} uds ($" . number_format($item->total_rev, 2) . ")\n";
            }
            $reply .= "\n";
        }

        $reply .= "💡 *Puedes exportar este informe en **PDF, Excel o CSV** desde el módulo de Reportes.*";
        return $reply;
    }

    /**
     * Respuesta de rentabilidad, márgenes y fichas técnicas de recetas.
     */
    private function buildProfitabilityAndRecipesReply($categories, string $query, bool $isStaff): string
    {
        $allDishes = $categories->flatMap(fn($c) => $c->dishes);

        // 1. Verificar si pregunta por la receta de un platillo específico
        foreach ($allDishes as $dish) {
            $nameLower = mb_strtolower($dish->name, 'UTF-8');
            $keywords  = array_filter(explode(' ', $nameLower), fn($w) => mb_strlen($w) > 3);
            foreach ($keywords as $kw) {
                if (!in_array($kw, ['para', 'las', 'brazas', 'con']) && str_contains($query, $kw)) {
                    $recipe = $dish->recipe;
                    $cost   = 0;
                    $ingLines = [];
                    if ($recipe && $recipe->ingredients) {
                        foreach ($recipe->ingredients as $ri) {
                            $unitCost = (float) ($ri->ingredient?->cost_per_unit ?? 0);
                            $subCost  = $unitCost * (float) $ri->quantity_required;
                            $cost    += $subCost;
                            $ingName  = $ri->ingredient?->name ?? 'Insumo';
                            $ingLines[] = "• **{$ingName}:** {$ri->quantity_required} {$ri->unit_of_measure}" . ($isStaff ? " ($" . number_format($subCost, 2) . ")" : "");
                        }
                    }
                    $price  = (float) $dish->price;
                    $profit = $price - $cost;
                    $margin = $price > 0 ? round(($profit / $price) * 100, 1) : 0;

                    $reply = "📖 **Ficha Técnica: {$dish->name}**\n\n"
                           . "• **Precio de Venta:** $" . number_format($price, 2) . "\n"
                           . "• **Tiempo en Parrilla:** {$dish->prep_time_minutes} min\n";

                    if ($isStaff) {
                        $reply .= "• **Costo de Insumos:** $" . number_format($cost, 2) . "\n"
                               . "• **Utilidad Neta:** $" . number_format($profit, 2) . " (**{$margin}% margen**)\n";
                    }

                    if (!empty($ingLines)) {
                        $reply .= "\n### 🥩 Insumos por Porción\n" . implode("\n", $ingLines) . "\n";
                    }

                    if ($recipe && !empty($recipe->instructions)) {
                        $reply .= "\n### 🔥 Procedimiento de Cocina\n" . $recipe->instructions;
                    }

                    return $reply;
                }
            }
        }

        // 2. Matriz general de los platillos más rentables
        $matrix = [];
        foreach ($allDishes as $dish) {
            $cost = 0;
            if ($dish->recipe && $dish->recipe->ingredients) {
                foreach ($dish->recipe->ingredients as $ri) {
                    $cost += ((float) $ri->quantity_required) * ((float) ($ri->ingredient?->cost_per_unit ?? 0));
                }
            }
            $price  = (float) $dish->price;
            $profit = $price - $cost;
            $margin = $price > 0 ? round(($profit / $price) * 100, 1) : 0;
            $matrix[] = [
                'name'   => $dish->name,
                'price'  => $price,
                'cost'   => $cost,
                'profit' => $profit,
                'margin' => $margin,
            ];
        }

        usort($matrix, fn($a, $b) => $b['profit'] <=> $a['profit']);

        $reply = "💰 **Top Platillos con Mayor Utilidad y Rentabilidad:**\n\n";
        foreach (array_slice($matrix, 0, 6) as $row) {
            $reply .= "• **{$row['name']}**\n"
                   . "  Venta: $" . number_format($row['price'], 2) . " | Costo: $" . number_format($row['cost'], 2) . " | Ganancia: **$" . number_format($row['profit'], 2) . "** ({$row['margin']}%)\n";
        }
        $reply .= "\n💡 *Pregúntame por la receta de cualquier platillo (ej. \"¿Cuál es la receta del Tomahawk?\") para ver su desglose exacto.*";
        return $reply;
    }

    /**
     * Respuesta de inventario, stock crítico, mermas y proveedores.
     */
    private function buildInventoryAndWasteReply(array $metrics): string
    {
        $lowStock = $metrics['low_stock'];

        $wasteMovements = InventoryMovement::with('ingredient')
            ->where('movement_type', 'waste')
            ->latest()
            ->take(5)
            ->get();

        $totalWasteCost = 0;
        foreach ($wasteMovements as $wm) {
            $totalWasteCost += ((float) $wm->quantity) * ((float) ($wm->ingredient?->cost_per_unit ?? 0));
        }

        $reply = "📦 **Diagnóstico de Inventario & Alertas de Stock:**\n\n";

        if ($lowStock->isEmpty()) {
            $reply .= "✅ Todos los insumos se encuentran por encima del stock mínimo de seguridad.\n\n";
        } else {
            $reply .= "### ⚠️ Insumos en Stock Crítico (" . $lowStock->count() . " alertas)\n";
            foreach ($lowStock as $ing) {
                $deficit  = max(0, (float) $ing->minimum_stock - (float) $ing->current_stock);
                $supplier = $ing->supplier ? "{$ing->supplier->company_name} (Tel: {$ing->supplier->phone})" : "Sin proveedor";
                $reply .= "• **{$ing->name}:** Stock actual **{$ing->current_stock} {$ing->unit_of_measure}** (Mín: {$ing->minimum_stock} {$ing->unit_of_measure})\n"
                       . "  ↳ *Faltante mínimo: {$deficit} {$ing->unit_of_measure} • Proveedor: {$supplier}*\n";
            }
            $reply .= "\n";
        }

        if ($wasteMovements->isNotEmpty()) {
            $reply .= "### 🗑️ Últimas Mermas Registradas (Impacto: $" . number_format($totalWasteCost, 2) . ")\n";
            foreach ($wasteMovements as $wm) {
                $ingName = $wm->ingredient?->name ?? 'Insumo';
                $unit    = $wm->ingredient?->unit_of_measure ?? '';
                $reply  .= "• **{$ingName}** (-{$wm->quantity} {$unit}): *{$wm->reason}*\n";
            }
        }

        return $reply;
    }

    /**
     * Respuesta de comandas activas en cocina (KDS) y pedidos de App Móvil (Delivery/Takeaway).
     */
    private function buildLiveKitchenAndDeliveryReply($activeOrders): string
    {
        if ($activeOrders->isEmpty()) {
            return "🔥 **Monitor de Cocina y Delivery en Vivo:**\n\nActualmente no hay comandas pendientes ni en preparación.";
        }

        $salonOrders = $activeOrders->where('order_type', 'dine_in');
        $appOrders   = $activeOrders->whereIn('order_type', ['delivery', 'takeaway']);

        $reply = "🔥 **Operación en Vivo: Cocina (KDS) & Pedidos App Móvil**\n\n";

        if ($salonOrders->isNotEmpty()) {
            $reply .= "### 🍽️ Comandas en Salón (" . $salonOrders->count() . ")\n";
            foreach ($salonOrders as $o) {
                $tbl = $o->table?->table_number ?? 'Mesa';
                $st  = $o->status === 'ready' ? '✅ Lista para servir' : '🔥 En Fuego / Cocina';
                $reply .= "• **Orden #{$o->id} ({$tbl})** — {$st} — Total: $" . number_format($o->total_amount, 2) . "\n";
            }
            $reply .= "\n";
        }

        if ($appOrders->isNotEmpty()) {
            $reply .= "### 🛵 Pedidos App Móvil: Delivery & Takeaway (" . $appOrders->count() . ")\n";
            foreach ($appOrders as $o) {
                $type = $o->order_type === 'delivery' ? '🛵 Delivery' : '🛍️ Para Llevar';
                $cust = $o->customer?->name ?? 'Cliente App';
                $pin  = $o->delivery_pin ? " | PIN: **{$o->delivery_pin}**" : '';
                $st   = match ($o->status) {
                    'pending'    => '⏳ Pendiente',
                    'in_kitchen' => '🔥 En Cocina',
                    'ready'      => '✅ Listo para Despacho',
                    default      => $o->status,
                };
                $reply .= "• **#{$o->id} {$type}** ({$cust}) — {$st}{$pin} — $" . number_format($o->total_amount, 2) . "\n";
                if ($o->delivery_address) {
                    $reply .= "  📍 *{$o->delivery_address}*\n";
                }
            }
        }

        return $reply;
    }

    /**
     * Disponibilidad de mesas y zonas en tiempo real.
     */
    private function buildTablesAvailabilityReply($areas): string
    {
        $reply = "🪑 **Estado de Zonas y Mesas en Tiempo Real:**\n\n";
        $totalFree = 0;

        foreach ($areas as $area) {
            $freeTables = $area->tables->where('status', 'available');
            $totalFree += $freeTables->count();
            $reply .= "### 📍 {$area->name}\n";
            foreach ($area->tables as $t) {
                $badge = match ($t->status) {
                    'available' => '🟢 Libre',
                    'occupied'  => '🔴 Ocupada',
                    'reserved'  => '🟡 Reservada',
                    default     => '⚪ Mantenimiento',
                };
                $reply .= "• **{$t->table_number}** ({$t->capacity} personas): {$badge}\n";
            }
            $reply .= "\n";
        }

        $reply .= "💡 *Total disponibles ahora mismo: **{$totalFree} mesas libres**. Si deseas agendar una, escribe por ejemplo: **\"Reserva una mesa para 4 personas hoy a las 20:00\"**.*";
        return $reply;
    }

    /**
     * Lista de cupones promocionales activos.
     */
    private function buildCouponsReply($coupons): string
    {
        if ($coupons->isEmpty()) {
            return "🎟️ Actualmente no hay cupones promocionales activos.";
        }

        $reply = "🎟️ **Cupones Promocionales Activos (App Móvil & POS):**\n\n";
        foreach ($coupons as $c) {
            $benefit = match ($c->type) {
                'percent'       => "{$c->value}% de descuento",
                'fixed'         => "$" . number_format($c->value, 2) . " de descuento directo",
                'free_shipping' => "Envío a domicilio GRATIS",
                default         => $c->type,
            };
            $reply .= "• Código **`{$c->code}`** — {$benefit} *(Compra mínima: $" . number_format($c->min_order, 2) . ")*\n";
        }
        return $reply;
    }

    /**
     * Construye la respuesta detallada de los pedidos del usuario.
     */
    private function buildOrdersReply($orders, string $userName): string
    {
        if ($orders->isEmpty()) {
            return "📦 **Pedidos de {$userName}:**\n\n"
                 . "Actualmente no tienes pedidos registrados a tu nombre.\n\n"
                 . "💡 *Puedes ordenar desde la App Móvil o en el Punto de Venta (POS).*";
        }

        $reply = "📦 **Historial de Pedidos Recientes ({$userName}):**\n\n";

        foreach ($orders as $order) {
            $date = Carbon::parse($order->created_at)->format('d/m/Y H:i');
            $statusLabel = match ($order->status) {
                'pending'    => '⏳ Pendiente de Confirmar',
                'in_kitchen' => '🔥 En Cocina (Preparando comanda)',
                'ready'      => '✅ Listo para Servir / Despachar',
                'served'     => '🍽️ Servido en Mesa',
                'paid'       => '💳 Pagado / Completado',
                'cancelled'  => '❌ Cancelado',
                default      => ucfirst(str_replace('_', ' ', $order->status)),
            };

            $typeInfo = match ($order->order_type) {
                'delivery' => ' (🛵 Delivery App)',
                'takeaway' => ' (🛍️ Para Llevar)',
                default    => $order->table ? " ({$order->table->table_number})" : ' (Salón)',
            };

            $reply .= "**Pedido #{$order->id}** — {$date}{$typeInfo}\n";
            $reply .= "• **Estado:** {$statusLabel}\n";
            $reply .= "• **Total:** $" . number_format($order->total_amount, 2) . "\n";

            if ($order->items->isNotEmpty()) {
                foreach ($order->items as $item) {
                    $dishName = $item->dish?->name ?? 'Platillo';
                    $reply .= "  - {$item->quantity}x {$dishName} ($" . number_format($item->subtotal, 2) . ")\n";
                }
            }
            $reply .= "\n---\n";
        }

        return $reply;
    }

    /**
     * Construye la respuesta detallada de las reservaciones.
     */
    private function buildReservationsReply($reservations, string $userName, bool $isStaff = false): string
    {
        $list = $reservations;
        if ($isStaff && $list->isEmpty()) {
            $list = Reservation::with(['customer', 'table.area'])->latest('reservation_date')->take(6)->get();
        }

        if ($list->isEmpty()) {
            return "📅 **Reservaciones:**\n\n"
                 . "No encontramos reservaciones activas.\n\n"
                 . "💡 *Puedes crear una ahora mismo escribiendo: **\"Reserva una mesa para 4 personas mañana a las 20:00\"**.*";
        }

        $reply = "📅 **Reservaciones Registradas en Las Brazas:**\n\n";

        foreach ($list as $res) {
            $date = Carbon::parse($res->reservation_date)->format('d/m/Y H:i');
            $statusLabel = match ($res->status) {
                'pending'   => '⏳ Pendiente',
                'confirmed' => '✅ Confirmada',
                'completed' => '🎉 Completada',
                'cancelled' => '❌ Cancelada',
                default     => ucfirst($res->status),
            };

            $table = $res->table ? "{$res->table->table_number} ({$res->table->area?->name})" : "Por asignar";
            $guestName = $res->customer?->name ?? $userName;

            $reply .= "🗓️ **{$date}** — **{$guestName}**\n";
            $reply .= "• **Estado:** {$statusLabel} | **Ubicación:** {$table} | **Comensales:** {$res->guest_count}p\n";
            if (!empty($res->special_requests)) {
                $reply .= "• **Notas:** *{$res->special_requests}*\n";
            }
            $reply .= "\n";
        }

        $reply .= "⏱️ *Tolerancia de reserva: **15 minutos**.*";
        return $reply;
    }

    /**
     * Construye la respuesta sobre el menú, búsqueda por presupuesto y recomendaciones de sommelier/parrillero.
     */
    private function buildMenuReply($categories, string $query, string $userName): string
    {
        $allDishes = $categories->flatMap(fn($c) => $c->dishes->map(fn($d) => ['dish' => $d, 'cat' => $c->name]));

        // 1. Si el usuario menciona un presupuesto (ej. "tengo 200", "menos de 300")
        if (preg_match('/(?:menos de|con|presupuesto de|hasta)\s*\$?\s*(\d+)/u', $query, $budgetMatch)) {
            $budget = (float) $budgetMatch[1];
            $affordable = $allDishes->filter(fn($item) => (float) $item['dish']->price <= $budget)->sortByDesc(fn($i) => $i['dish']->price);

            if ($affordable->isEmpty()) {
                return "💡 Con un presupuesto de **$" . number_format($budget, 2) . "** puedes ordenar nuestras bebidas refrescantes ($45) o guarniciones como la Yuca Frita Crocante ($75).";
            }

            $reply = "🎯 **Opciones Recomendadas dentro de tu presupuesto ($" . number_format($budget, 2) . "):**\n\n";
            foreach ($affordable->take(7) as $item) {
                $d = $item['dish'];
                $reply .= "• **{$d->name}** ({$item['cat']}) — **$" . number_format($d->price, 2) . "**\n";
            }
            return $reply;
        }

        // 2. Buscar si pregunta por un platillo específico de la base de datos
        foreach ($allDishes as $item) {
            $dish = $item['dish'];
            $dishNameLower = mb_strtolower($dish->name, 'UTF-8');
            $keywords = array_filter(explode(' ', $dishNameLower), fn($w) => mb_strlen($w) > 3 && !in_array($w, ['las', 'brazas', 'para', 'con']));
            foreach ($keywords as $kw) {
                if (str_contains($query, $kw)) {
                    return "🥩 **{$dish->name}**\n\n"
                         . "• **Categoría:** {$item['cat']}\n"
                         . "• **Precio:** $" . number_format($dish->price, 2) . "\n"
                         . "• **Descripción:** {$dish->description}\n"
                         . "• **Tiempo en Parrilla:** {$dish->prep_time_minutes} minutos a las brasas\n\n"
                         . "🍷 *¿Deseas conocer su receta técnica o reservar una mesa para degustarlo?*";
                }
            }
        }

        // 3. Recomendación de cortes y maridaje con precios reales de la BD
        if (str_contains($query, 'recomiend') || str_contains($query, 'especialidad') || str_contains($query, 'mejor') || str_contains($query, 'maridaje')) {
            $cortesCat = $categories->firstWhere('slug', 'cortes-y-parrilladas');
            $topCortes = $cortesCat ? $cortesCat->dishes->take(4) : $allDishes->take(4)->pluck('dish');

            $reply = "🔥 **Recomendaciones del Maestro Parrillero para ti, {$userName}:**\n\n";
            foreach ($topCortes as $idx => $d) {
                $reply .= ($idx + 1) . ". **{$d->name}** ($" . number_format($d->price, 2) . ")\n"
                        . "   *{$d->description}*\n\n";
            }
            $reply .= "🍷 **Maridaje Sugerido:** Acompaña nuestros cortes Dry-Aged con una **Botella de Vino Malbec Reserva Mendoza ($480.00)** o una **Cerveza Artesanal IPA ($95.00)**.";
            return $reply;
        }

        // 4. Resumen completo de las categorías de la carta
        $reply = "📋 **Carta Oficial de Restaurante Las Brazas (" . $allDishes->count() . " Especialidades):**\n\n";
        foreach ($categories as $cat) {
            if ($cat->dishes->isNotEmpty()) {
                $reply .= "### 🔥 {$cat->name}\n";
                foreach ($cat->dishes->take(4) as $d) {
                    $reply .= "• **{$d->name}** — $" . number_format($d->price, 2) . "\n";
                }
                if ($cat->dishes->count() > 4) {
                    $reply .= "  *(+" . ($cat->dishes->count() - 4) . " platillos más en esta categoría)*\n";
                }
                $reply .= "\n";
            }
        }

        $reply .= "💡 *Pregúntame por cualquier platillo, pide opciones según tu presupuesto (ej. \"¿Qué como con $350?\") o solicita su ficha de receta.*";
        return $reply;
    }

    /**
     * Respuesta de bienvenida o guía de capacidades según rol.
     */
    private function buildWelcomeOrFallbackReply(string $userName, string $role, bool $isStaff, string $query): string
    {
        if ($isStaff) {
            return "👋 ¡Hola, **{$userName}** ({$role})! Soy tu **Agente IA Ejecutivo y Operativo** de Las Brazas. 🔥\n\n"
                 . "Estoy conectado en tiempo real a PostgreSQL. Puedes consultarme o pedirme acciones:\n"
                 . "• 📊 **Finanzas y KPIs:** *\"¿Cómo van las ventas hoy y cuáles son los platos más vendidos?\"*\n"
                 . "• 💰 **Rentabilidad y Recetas:** *\"¿Cuáles son los platillos más rentables?\"* o *\"Receta del Tomahawk\"*\n"
                 . "• ⚠️ **Inventario y Mermas:** *\"¿Qué insumos están en stock crítico y a qué proveedor llamar?\"*\n"
                 . "• 🛵 **Cocina y Delivery:** *\"¿Qué pedidos hay activos en cocina y en la App Móvil?\"*\n"
                 . "• 🪑 **Mesas y Reservas por Voz/Texto:** *\"¿Qué mesas están libres?\"* o *\"Reserva una mesa para 4 personas mañana a las 20:00\"*";
        }

        return "¡Hola, **{$userName}**! 👋 Soy tu **Chef Virtual y Sommelier IA** en Restaurante Las Brazas. 🔥🥩\n\n"
             . "Puedo ayudarte en tiempo real con:\n"
             . "• 🥩 **Carta y Recomendaciones:** Precios, cortes, tiempos de cocción y maridajes.\n"
             . "• 🎯 **Búsqueda por Presupuesto:** Dime *\"¿Qué me recomiendas con $350?\"*\n"
             . "• 📅 **Reservar por Chat:** Escribe *\"Reserva una mesa para 4 personas mañana a las 20:00\"* y la agendo al instante.\n"
             . "• 📦 **Tus Pedidos y Cupones:** Estado de tus órdenes y códigos de descuento activos.";
    }
}
