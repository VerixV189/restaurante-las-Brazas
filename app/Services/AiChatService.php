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

        // Detectar intención de ARMAR O HACER UN PEDIDO por voz/texto ("pídeme...", "quiero pedir...", "ordenar...", "agrega al carrito...")
        if (
            str_contains($q, 'pídeme') || str_contains($q, 'pideme') ||
            str_contains($q, 'quiero pedir') || str_contains($q, 'hazme un pedido') ||
            str_contains($q, 'hacer un pedido') || str_contains($q, 'ordenar ') ||
            str_contains($q, 'ordéname') || str_contains($q, 'ordename') ||
            str_contains($q, 'agrega al carrito') || str_contains($q, 'agregar al carrito')
        ) {
            $matchedItems = $this->parseDishesFromMessage($message, $context['categories']);
            if (! empty($matchedItems)) {
                $total = 0;
                $lines = [];
                foreach ($matchedItems as $item) {
                    $sub = $item['price'] * $item['quantity'];
                    $total += $sub;
                    $noteStr = ! empty($item['notes']) ? " *({$item['notes']})*" : '';
                    $lines[] = "• **{$item['quantity']}x {$item['name']}**{$noteStr} — $" . number_format($sub, 2);
                }

                return "🛒 **¡He preparado tu orden inteligente, {$user->name}!** 🔥\n\n"
                     . implode("\n", $lines) . "\n\n"
                     . "💰 **Subtotal Estimado:** $" . number_format($total, 2) . "\n\n"
                     . "👇 *Usa los botones interactivos aquí abajo para **agregar todo a tu carrito** o **enviar el pedido directo a cocina**.*";
            }
        }

        return null;
    }

    /**
     * Extrae platillos sugeridos y cupones accionables para mostrar tarjetas interactivas en el Chat IA.
     */
    public function extractInteractivePayload(User $user, string $message, string $reply): array
    {
        $q = mb_strtolower($message, 'UTF-8');
        $categories = Category::with(['dishes' => fn ($dq) => $dq->where('is_available', true)])->get();

        // 1. Si el usuario pidió ordenar platillos específicos
        $parsedOrderItems = $this->parseDishesFromMessage($message, $categories);

        // 2. Si no es orden directa, buscar platillos mencionados en la respuesta del asistente (máx 4 tarjetas)
        if (empty($parsedOrderItems)) {
            $allDishes = $categories->flatMap(fn ($c) => $c->dishes->map(fn ($d) => ['dish' => $d, 'cat' => $c->name]));
            $replyLower = mb_strtolower($reply, 'UTF-8');
            foreach ($allDishes as $entry) {
                $d = $entry['dish'];
                if (str_contains($replyLower, mb_strtolower($d->name, 'UTF-8'))) {
                    $parsedOrderItems[] = $this->formatSuggestedDish($d, $entry['cat'], 1, $this->detectCookingNotes($q, $d->name));
                    if (count($parsedOrderItems) >= 4) {
                        break;
                    }
                }
            }
        }

        // 3. Cupones activos si se habló de cupones/descuentos
        $suggestedCoupons = [];
        if (str_contains($q, 'cupon') || str_contains($q, 'cupón') || str_contains($q, 'descuento') || str_contains($q, 'promo')) {
            $suggestedCoupons = Coupon::where('is_active', true)->get()->map(fn ($c) => [
                'code' => $c->code,
                'type' => $c->type,
                'value' => (float) $c->value,
                'min_order' => (float) $c->min_order,
            ])->values()->all();
        }

        return [
            'suggested_items' => $parsedOrderItems,
            'suggested_coupons' => $suggestedCoupons,
            'created_reservation' => str_contains($reply, '¡Reserva Creada Exitosamente por el Agente IA!'),
        ];
    }

    private function parseDishesFromMessage(string $message, $categories): array
    {
        $q = mb_strtolower($message, 'UTF-8');
        $allDishes = $categories->flatMap(fn ($c) => $c->dishes->map(fn ($d) => ['dish' => $d, 'cat' => $c->name]));
        $matched = [];

        foreach ($allDishes as $entry) {
            $dish = $entry['dish'];
            $nameLower = mb_strtolower($dish->name, 'UTF-8');
            $keywords = array_values(array_filter(
                explode(' ', preg_replace('/[^a-záéíóúñ0-9\s]/u', '', $nameLower)),
                fn ($w) => mb_strlen($w) >= 4 && ! in_array($w, ['para', 'brazas', 'casa', 'corte', 'gramos'])
            ));

            $found = str_contains($q, $nameLower);
            if (! $found) {
                foreach ($keywords as $kw) {
                    if (str_contains($q, $kw)) {
                        $found = true;
                        break;
                    }
                }
            }

            if ($found) {
                $qty = 1;
                foreach ($keywords as $kw) {
                    if (preg_match('/(\d+|un|una|dos|tres|cuatro)\s+(?:[a-záéíóúñ]+\s+)?' . preg_quote($kw, '/') . '/u', $q, $m)) {
                        $qty = match ($m[1]) {
                            'dos' => 2,
                            'tres' => 3,
                            'cuatro' => 4,
                            'un', 'una' => 1,
                            default => max(1, (int) $m[1]),
                        };
                        break;
                    }
                }

                $matched[$dish->id] = $this->formatSuggestedDish(
                    $dish,
                    $entry['cat'],
                    $qty,
                    $this->detectCookingNotes($q, $dish->name)
                );
            }
        }

        return array_values($matched);
    }

    private function detectCookingNotes(string $q, string $dishName): ?string
    {
        $notes = [];
        if (str_contains($q, 'término medio') || str_contains($q, 'termino medio') || str_contains($q, 'a punto')) {
            $notes[] = 'Término Medio (A punto)';
        } elseif (str_contains($q, 'tres cuartos') || str_contains($q, '3/4')) {
            $notes[] = 'Tres Cuartos (Jugoso)';
        } elseif (str_contains($q, 'bien cocido')) {
            $notes[] = 'Bien Cocido';
        } elseif (str_contains($q, 'sellado') || str_contains($q, 'vuelta y vuelta') || str_contains($q, 'inglés')) {
            $notes[] = 'Vuelta y vuelta (Sellado)';
        } elseif (stripos($dishName, 'arrachera') !== false || stripos($dishName, 'bife') !== false || stripos($dishName, 'picaña') !== false || stripos($dishName, 'tomahawk') !== false) {
            $notes[] = 'Término Medio (A punto)';
        }

        if (str_contains($q, 'sin cebolla')) {
            $notes[] = 'Sin cebolla';
        }
        if (str_contains($q, 'sin sal')) {
            $notes[] = 'Bajo en sal';
        }

        return ! empty($notes) ? implode(' • ', $notes) : null;
    }

    private function formatSuggestedDish($dish, string $catName, int $qty = 1, ?string $notes = null): array
    {
        $imageUrl = $dish->image_url;
        if (empty($imageUrl)) {
            $imageUrl = stripos($dish->name, 'arrachera') !== false
                ? url('/images/arrachera.png')
                : 'https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=600&auto=format&fit=crop';
        } elseif (! str_starts_with($imageUrl, 'http')) {
            $imageUrl = url($imageUrl);
        }

        return [
            'dish_id' => $dish->id,
            'name' => $dish->name,
            'category_name' => $catName,
            'price' => (float) $dish->price,
            'image_url' => $imageUrl,
            'prep_time_minutes' => (int) $dish->prep_time_minutes,
            'quantity' => $qty,
            'notes' => $notes,
        ];
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
            return "👋 ¡Hola, **{$userName}** ({$role})! Soy tu **Asistente Ejecutivo y Operativo** de Las Brazas. 🔥\n\n"
                 . "Estoy conectado en tiempo real con la operación del restaurante. Puedes consultarme o pedirme acciones:\n"
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

    /**
     * =========================================================================
     * AGENTE IA PÚBLICO PARA LANDINGS (RESTAURANTE `/` Y EMPRESARIAL `/empresa`)
     * =========================================================================
     */
    public function generatePublicReply(string $message, array $history = [], string $portal = 'restaurant'): string
    {
        $message = trim($message);
        $publicContext = $this->gatherPublicContext();

        // 1. Intentar Google Gemini API con base de conocimiento empresarial + carta en vivo
        $geminiReply = $this->tryDirectPublicGeminiApi($message, $publicContext, $history, $portal);
        if ($geminiReply !== null) {
            return $geminiReply;
        }

        // 2. Motor inteligente de respaldo en PHP (cubre 100% de dudas sin fallar jamás)
        return $this->smartProcessPublicQuery($message, $publicContext, $portal);
    }

    /**
     * Extrae accesos directos interactivos recomendados según el tema de la conversación.
     */
    public function extractPublicActions(string $message, string $reply, string $portal = 'restaurant'): array
    {
        $combined = mb_strtolower($message . ' ' . $reply, 'UTF-8');
        $actions = [];

        if (str_contains($combined, 'privacidad') || str_contains($combined, 'datos personales') || str_contains($combined, 'arco')) {
            $actions[] = ['label' => 'Ver Política de Privacidad', 'href' => '/privacy', 'kind' => 'emerald'];
        }
        if (str_contains($combined, 'término') || str_contains($combined, 'termino') || str_contains($combined, 'condicion') || str_contains($combined, 'tolerancia')) {
            $actions[] = ['label' => 'Leer Términos y Condiciones', 'href' => '/terms', 'kind' => 'orange'];
        }
        if (str_contains($combined, 'saas') || str_contains($combined, 'plan') || str_contains($combined, 'cotiz') || str_contains($combined, 'licencia') || str_contains($combined, 'brazastech') || str_contains($combined, 'misión') || str_contains($combined, 'visión')) {
            $actions[] = [
                'label' => $portal === 'corporate' ? 'Ir a Cotización SaaS' : 'Explorar Sitio Empresarial',
                'href'  => $portal === 'corporate' ? '/empresa#cotizacion' : '/empresa',
                'kind'  => 'blue',
            ];
        }
        if (str_contains($combined, 'menú') || str_contains($combined, 'menu') || str_contains($combined, 'corte') || str_contains($combined, 'platillo') || str_contains($combined, 'precio')) {
            $actions[] = ['label' => 'Ver Menú Digital', 'href' => '/#menu', 'kind' => 'orange'];
        }
        if (str_contains($combined, 'registrar') || str_contains($combined, 'cuenta') || str_contains($combined, 'google')) {
            $actions[] = ['label' => 'Crear Cuenta Cliente', 'href' => '/register', 'kind' => 'orange'];
        }
        if (str_contains($combined, 'personal') || str_contains($combined, 'empleado') || str_contains($combined, 'staff') || str_contains($combined, 'administrador')) {
            $actions[] = ['label' => 'Acceso Personal', 'href' => '/staff/login', 'kind' => 'slate'];
        }
        if (str_contains($combined, 'manual') || str_contains($combined, 'pdf') || str_contains($combined, 'guía')) {
            $actions[] = ['label' => 'Abrir Manual Interactivo', 'href' => '/manual', 'kind' => 'orange'];
        }
        if (str_contains($combined, 'contact') || str_contains($combined, 'ubicación') || str_contains($combined, 'ubicacion') || str_contains($combined, 'horario') || str_contains($combined, 'teléfono')) {
            $actions[] = ['label' => 'Ir a Contáctenos', 'href' => '/#contacto', 'kind' => 'orange'];
        }

        // Eliminar duplicados por href y limitar a 3 acciones limpias
        $unique = [];
        foreach ($actions as $act) {
            $unique[$act['href']] = $act;
        }

        return array_slice(array_values($unique), 0, 3);
    }

    private function gatherPublicContext(): array
    {
        try {
            $categories = Category::where('is_active', true)
                ->with(['dishes' => fn ($q) => $q->where('is_available', true)->orderBy('name')])
                ->get();
        } catch (\Throwable $e) {
            $categories = collect();
        }

        try {
            $areas = Area::with('tables')->get();
        } catch (\Throwable $e) {
            $areas = collect();
        }

        try {
            $coupons = Coupon::where('is_active', true)->get();
        } catch (\Throwable $e) {
            $coupons = collect();
        }

        return [
            'categories' => $categories,
            'areas'      => $areas,
            'coupons'    => $coupons,
        ];
    }

    private function tryDirectPublicGeminiApi(string $message, array $context, array $history, string $portal): ?string
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            return null;
        }

        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $systemPrompt = $this->buildPublicRagSystemPrompt($context, $portal);

        try {
            $contents = [];
            foreach (array_slice($history, -8) as $h) {
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

            $response = Http::timeout(12)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature'     => 0.35,
                        'maxOutputTokens' => 1000,
                    ],
                ]);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');
                if (!empty($text)) {
                    return trim($text);
                }
            }
        } catch (\Throwable $e) {
            Log::info('Public Gemini API fallback: ' . $e->getMessage());
        }

        return null;
    }

    private function buildPublicRagSystemPrompt(array $context, string $portal): string
    {
        $menuLines = [];
        foreach ($context['categories'] as $cat) {
            foreach ($cat->dishes as $d) {
                $menuLines[] = "- {$d->name} ({$cat->name}): $" . number_format((float) $d->price, 2) . " | Prep: {$d->prep_time_minutes} min | {$d->description}";
            }
        }

        $areaLines = [];
        foreach ($context['areas'] as $area) {
            $cap = $area->tables->sum('capacity');
            $areaLines[] = "- {$area->name}: {$area->tables->count()} mesas (capacidad total {$cap} personas)";
        }

        $couponLines = [];
        foreach ($context['coupons'] as $c) {
            $desc = $c->type === 'percent' ? "{$c->value}% de descuento" : ($c->type === 'free_shipping' ? 'Envío gratis' : '$' . number_format((float) $c->value, 2) . ' de descuento');
            $couponLines[] = "- Código {$c->code}: {$desc} (pedido mínimo $" . number_format((float) $c->min_order, 2) . ")";
        }

        $rolePersona = $portal === 'corporate'
            ? "Actúas como Consultor Especialista Cloud SaaS de BrazasTech Soluciones de Software S.R.L. y conoces también todos los detalles de su restaurante insignia 'Restaurante Las Brazas Steakhouse'."
            : "Actúas como Anfitrión Virtual y Sommelier IA de 'Restaurante Las Brazas Steakhouse', y además conoces por completo la información empresarial y SaaS de BrazasTech S.R.L. por si un visitante o cliente B2B pregunta por el software.";

        return <<<PROMPT
Eres el Agente Inteligente Oficial de Restaurante Las Brazas Steakhouse & BrazasTech Soluciones de Software S.R.L.
{$rolePersona}
Responde siempre en español con tono amable, profesional, claro y usando formato Markdown (negritas y viñetas breves). Nunca inventes datos ni reveles contraseñas internas.

=== 1. IDENTIDAD CORPORATIVA (MISIÓN, VISIÓN, VALORES Y CALIDAD) ===
- Empresa desarrolladora del software: BrazasTech Soluciones de Software S.R.L. (registrada ante SEPREC y SENAPI en Bolivia, facturación electrónica SIAT).
- Restaurante insignia: Restaurante Las Brazas Steakhouse & Grill (+12 años de tradición parrillera, +45,000 comensales atendidos, calificación 4.9/5, cocción 100% con carbón vegetal y leña de mezquite seleccionada).
- Misión: Brindar una experiencia gastronómica excepcional acompañada de soluciones digitales integrales (Web, App Móvil e Inteligencia Artificial) que optimizan cada etapa del restaurante con herramientas seguras, ágiles y confiables para el servicio al comensal y el control operativo.
- Visión: Consolidarnos como referentes en hospitalidad y soluciones tecnológicas omnicanal (Web y Móvil) para la industria gastronómica en Latinoamérica, reconocidos por nuestra innovación, facilidad de uso y máxima estabilidad operativa en horas pico.
- Slogan: "Excelencia en cada corte, precisión en cada servicio."
- Compromiso de Calidad: Disponibilidad 99.9%, cifrado SSL/TLS, copias de respaldo diarias, auditoría de procesos y trazabilidad total de inventario cárnico.

=== 2. UBICACIÓN, HORARIOS Y CONTACTO ===
- Dirección: Av. Las Brazas #120, Zona Equipetrol Norte, Santa Cruz de la Sierra, Bolivia.
- Teléfono fijo: +591 (3) 345-6789 | WhatsApp de atención: +591 700-45678.
- Horarios de atención:
  • Lunes a Jueves: 1:00 PM – 10:00 PM
  • Viernes y Sábado: 1:00 PM – 11:30 PM
  • Domingo Familiar: 1:00 PM – 9:00 PM
- Formulario Contáctenos (/#contacto) y Cotización SaaS (/empresa#cotizacion): envían directamente al correo oficial Gmail del equipo con respuesta directa.

=== 3. MODELO SAAS Y PLANES COMERCIALES (/empresa) ===
- Plan Parrilla Starter: $49 USD/mes (Mensual) o $39 USD/mes (Anual, -20%). Incluye 1 sucursal, hasta 15 mesas, POS Web táctil y arqueo de caja, catálogo digital, control de inventario y stock mínimo, tickets PDF y facturación SIAT, Nube Administrada + backups diarios.
- Plan Steakhouse Pro (Más Popular • Activo en Las Brazas): $99 USD/mes (Mensual) o $79 USD/mes (Anual, -20%). Todo lo del Starter + mesas y zonas ilimitadas, Monitor de Cocina en Tiempo Real (KDS), App Móvil Android & iOS con Delivery, PIN de 4 dígitos y Cupones, descuento automático por Receta Técnica, reservas digitales por aforo horario (±2h) y tolerancia de 15 min, Chef Virtual IA, Servidor Cloud Dedicado.
- Plan Enterprise Multi-Sede: $189 USD/mes (Mensual) o $149 USD/mes (Anual, -20%). Todo lo del Pro + múltiples sucursales y almacenes, Gestor Inteligente de Planos de Salón con IA, predicción de demanda y lotes de compra (PDF/Excel), auditoría completa y permisos para 6 perfiles operativos, SLA 99.9% y soporte 24/7.

=== 4. TÉRMINOS Y CONDICIONES (/terms) ===
- Registro manual: requiere aceptar 2 casillas independientes obligatorias (Términos y Condiciones + Política de Privacidad) y envía 4 correos (Términos, Condiciones, Política de Privacidad y Verificación de cuenta).
- Registro en 1 clic con Google OAuth 2.0: activa y verifica la cuenta automáticamente al instante y envía los 3 correos informativos (Términos, Condiciones y Política de Privacidad), sin correo de validación.
- Reservas de mesas: tolerancia estricta de 15 minutos antes de liberar la mesa; control de aforo en ventanas de ±2 horas; cancelaciones permitidas con al menos 2 horas de anticipación.
- Pedidos y Delivery: entregas a domicilio validadas con PIN secreto de 4 dígitos y firma manuscrita; envío estándar $3.00 o envío GRATIS en pedidos desde $50.00 o con cupón de envío gratis.
- Código de convivencia: ambiente familiar, derecho de admisión ante conductas irrespetuosas.

=== 5. POLÍTICA DE PRIVACIDAD Y SEGURIDAD (/privacy) ===
- Nunca vendemos ni compartimos datos personales con terceros publicitarios.
- Acceso con Google OAuth 2.0: solo solicitamos alcances básicos (openid, email, profile). Jamás accedemos a contraseñas, correos de Gmail, Drive ni contactos.
- Seguridad técnica: HTTPS/SSL, hash seguro de contraseñas, separación estricta de accesos (Clientes solo entran en /login; Personal autorizado solo entra en /staff/login), y Modo Offline PWA con purga automática de caché privada al cerrar sesión (Clear-Site-Data).
- Derechos del titular (ARCO): acceso, rectificación, actualización y eliminación de cuenta/datos vía /#contacto.

=== 6. PORTALES Y ACCESOS ===
- Web del Restaurante (Clientes): / | Login Clientes: /login | Registro Clientes: /register
- Sitio Web Empresarial SaaS: /empresa | Cotización SaaS: /empresa#cotizacion
- Acceso Exclusivo Personal (Administrador, Chef, Mesero, Recepcionista, Repartidor): /staff/login
- Manual de Usuario Interactivo y PDF: /manual (también disponible en manual.lasbrazas.ramio.tech)

=== 7. DATOS EN VIVO DE LA BASE DE DATOS ===
CARTA DISPONIBLE HOY:
{$this->joinLinesOrFallback($menuLines, 'Consulta nuestra carta actualizada en la sección Menú Digital.')}

AMBIENTES / ZONAS:
{$this->joinLinesOrFallback($areaLines, 'Salón Principal, Terraza Jardín y Área VIP.')}

CUPONES ACTIVOS:
{$this->joinLinesOrFallback($couponLines, 'Consulta las promociones vigentes al ingresar a tu Portal del Comensal.')}
PROMPT;
    }

    private function joinLinesOrFallback(array $lines, string $fallback): string
    {
        return empty($lines) ? $fallback : implode("\n", array_slice($lines, 0, 40));
    }

    /**
     * Motor inteligente local de respaldo que responde cualquier duda sobre el restaurante o el SaaS.
     */
    private function smartProcessPublicQuery(string $message, array $context, string $portal): string
    {
        $q = mb_strtolower($message, 'UTF-8');

        // 1. Misión y Visión / Identidad Corporativa
        if (str_contains($q, 'misión') || str_contains($q, 'mision') || str_contains($q, 'visión') || str_contains($q, 'vision') || str_contains($q, 'slogan') || str_contains($q, 'filosof')) {
            return "🎯 **Identidad Corporativa — BrazasTech & Restaurante Las Brazas**\n\n"
                 . "• **Nuestra Misión:** Brindar una experiencia gastronómica excepcional acompañada de soluciones digitales integrales (Web, App Móvil e Inteligencia Artificial) que optimizan cada etapa del restaurante con herramientas seguras, ágiles y confiables.\n"
                 . "• **Nuestra Visión:** Consolidarnos como referentes en hospitalidad y soluciones tecnológicas omnicanal para la industria gastronómica en Latinoamérica, reconocidos por nuestra innovación, facilidad de uso y estabilidad operativa 99.9%.\n"
                 . "• **Nuestro Slogan:** *\"Excelencia en cada corte, precisión en cada servicio.\"*\n"
                 . "• **Compromiso de Calidad:** Respaldo diario en nube, cifrado SSL/TLS y trazabilidad total de inventario cárnico.";
        }

        // 2. Sobre Nosotros / Historia
        if (str_contains($q, 'sobre nosotros') || str_contains($q, 'historia') || str_contains($q, 'quienes son') || str_contains($q, 'quiénes son') || str_contains($q, 'tradición') || str_contains($q, 'tradicion')) {
            return "🔥 **Sobre Nosotros — Restaurante Las Brazas Steakhouse**\n\n"
                 . "Somos una casa de parrilla y cortes madurados con **más de 12 años de tradición** en Santa Cruz de la Sierra, cocinando 100% a la leña de mezquite y carbón vegetal seleccionado.\n\n"
                 . "• ⭐ **4.9 / 5** de calificación con **+45,000 comensales atendidos**.\n"
                 . "• 🪑 **3 Ambientes Exclusivos:** Salón Principal, Terraza Jardín y Área VIP Corporativa.\n"
                 . "• 💻 Tecnología operada con el ecosistema **BrazasTech Cloud SaaS** para ofrecer reservas sin filas y entregas seguras.";
        }

        // 3. Política de Privacidad y Protección de Datos
        if (str_contains($q, 'privacidad') || str_contains($q, 'datos personales') || str_contains($q, 'protección de datos') || str_contains($q, 'cookies') || str_contains($q, 'arco')) {
            return "🛡️ **Política de Privacidad — Restaurante Las Brazas**\n\n"
                 . "• **Uso responsable:** Nunca vendemos ni compartimos tus datos personales con terceros con fines publicitarios.\n"
                 . "• **Google OAuth 2.0:** Al iniciar sesión con Google solo solicitamos tu nombre y correo verificado (`openid`, `email`, `profile`). Jamás accedemos a tu contraseña, correos ni archivos.\n"
                 . "• **Seguridad técnica y PWA:** Conexiones cifradas HTTPS/SSL, contraseñas con hash irreversible y **borrado automático de caché offline** al cerrar sesión (`Clear-Site-Data`).\n"
                 . "• **Derechos ARCO:** Puedes solicitar acceso, actualización o eliminación de tu cuenta desde **Contáctenos**.\n\n"
                 . "📄 Puedes leer el documento completo en **`/privacy`** (o **`/politica-de-privacidad`**).";
        }

        // 4. Términos y Condiciones
        if (str_contains($q, 'término') || str_contains($q, 'termino') || str_contains($q, 'condicion') || str_contains($q, 'legal') || str_contains($q, 'regla')) {
            return "📜 **Términos y Condiciones del Servicio — Las Brazas**\n\n"
                 . "• **Registro de cuenta:** Con correo manual debes aceptar **2 casillas independientes** (Términos y Política de Privacidad) y recibirás 4 correos (Términos, Condiciones, Privacidad y Verificación). Con **Google** quedas activo al instante y recibes los 3 correos informativos (sin validación).\n"
                 . "• **Reservas de mesa:** Margen de tolerancia estricto de **15 minutos** antes de liberar la mesa y cancelación permitida hasta **2 horas antes**.\n"
                 . "• **Delivery seguro:** Cada pedido a domicilio incluye un **PIN de 4 dígitos** y firma manuscrita al momento de la entrega.\n"
                 . "• **Convivencia:** Ambiente cálido y familiar con respeto hacia clientes y personal.\n\n"
                 . "📄 Consulta el documento completo en **`/terms`**.";
        }

        // 5. Planes SaaS, Precios, Módulos B2B, Cotización
        if (str_contains($q, 'saas') || str_contains($q, 'plan') || str_contains($q, 'licencia') || str_contains($q, 'cotiz') || str_contains($q, 'brazastech') || str_contains($q, 'pos') || str_contains($q, 'kds') || str_contains($q, 'ecosistema') || str_contains($q, 'software')) {
            return "🚀 **BrazasTech Cloud SaaS — Planes para Restaurantes & Parrillas**\n\n"
                 . "Desarrollado por **BrazasTech Soluciones de Software S.R.L.** (registrado ante SEPREC y SENAPI, con facturación SIAT):\n\n"
                 . "• **Plan Parrilla Starter — $49 USD/mes** *(o $39/mes anual, -20%)*: 1 sucursal, hasta 15 mesas, POS Web táctil, inventario con stock mínimo, tickets PDF/SIAT y backups diarios.\n"
                 . "• **Plan Steakhouse Pro — $99 USD/mes** *(o $79/mes anual, -20% • Más Popular)*: Mesas y zonas ilimitadas, Monitor de Cocina **KDS** en vivo, **App Móvil (Android/iOS)** con Delivery + PIN 4 dígitos + Cupones, descuento automático por Receta Técnica y **Chef Virtual IA**.\n"
                 . "• **Plan Enterprise Multi-Sede — $189 USD/mes** *(o $149/mes anual, -20%)*: Múltiples sucursales, Gestor Inteligente de Planos con IA, predicción de demanda y compras (PDF/Excel), auditoría de 6 roles y SLA 99.9%.\n\n"
                 . "📩 Puedes solicitar una demo y cotización directa a nuestro correo en **`/empresa#cotizacion`**.";
        }

        // 6. Ubicación, Dirección, Horarios y Contacto
        if (str_contains($q, 'ubicaci') || str_contains($q, 'direcci') || str_contains($q, 'donde') || str_contains($q, 'dónde') || str_contains($q, 'horario') || str_contains($q, 'hora') || str_contains($q, 'teléfono') || str_contains($q, 'telefono') || str_contains($q, 'whatsapp') || str_contains($q, 'contact')) {
            return "📍 **Ubicación, Horarios y Contacto — Restaurante Las Brazas**\n\n"
                 . "• **Dirección:** Av. Las Brazas #120, Zona Equipetrol Norte, Santa Cruz de la Sierra, Bolivia.\n"
                 . "• **Teléfono Fijo:** +591 (3) 345-6789\n"
                 . "• **WhatsApp Atención:** +591 700-45678\n"
                 . "• **Horarios de Atención:**\n"
                 . "  - Lunes a Jueves: **1:00 PM – 10:00 PM**\n"
                 . "  - Viernes y Sábado: **1:00 PM – 11:30 PM**\n"
                 . "  - Domingo Familiar: **1:00 PM – 9:00 PM**\n\n"
                 . "✉️ También puedes escribirnos desde el formulario **Contáctenos (`/#contacto`)** y tu mensaje llega directamente a nuestra bandeja oficial de Gmail.";
        }

        // 7. Reservas y Ambientes
        if (str_contains($q, 'reserv') || str_contains($q, 'mesa') || str_contains($q, 'terraza') || str_contains($q, 'vip') || str_contains($q, 'tolerancia')) {
            $zones = $context['areas']->map(fn ($a) => "**{$a->name}** ({$a->tables->count()} mesas)")->implode(', ');
            $zonesText = $zones ?: '**Salón Principal**, **Terraza Jardín** y **Área VIP**';

            return "📅 **Reservas de Mesas en Las Brazas**\n\n"
                 . "• **Zonas disponibles:** {$zonesText}.\n"
                 . "• **Cómo reservar:** Pulsa el botón **Reservar** en la página principal o ingresa a tu **Portal del Comensal (`/login`)** para elegir fecha, horario, número de adultos/niños y zona favorita.\n"
                 . "• **Tolerancia:** Contamos con **15 minutos de tolerancia** desde la hora reservada y gestión de disponibilidad en franjas de ±2 horas.\n"
                 . "• **Cancelación:** Puedes cancelar anticipadamente hasta **2 horas antes** de tu reserva.";
        }

        // 8. Delivery, Cupones y Pagos
        if (str_contains($q, 'delivery') || str_contains($q, 'domicilio') || str_contains($q, 'envío') || str_contains($q, 'envio') || str_contains($q, 'cupon') || str_contains($q, 'cupón') || str_contains($q, 'descuento') || str_contains($q, 'promo') || str_contains($q, 'pin')) {
            $couponBlock = '';
            if ($context['coupons']->isNotEmpty()) {
                $couponBlock = "\n\n🎟️ **Cupones Activos Hoy:**\n";
                foreach ($context['coupons']->take(4) as $c) {
                    $benefit = $c->type === 'percent' ? "{$c->value}% OFF" : ($c->type === 'free_shipping' ? 'Envío Gratis' : '$' . number_format((float) $c->value, 2) . ' OFF');
                    $couponBlock .= "• **`{$c->code}`** — {$benefit} (mínimo $" . number_format((float) $c->min_order, 2) . ")\n";
                }
            }

            return "🛵 **Pedidos Online, Delivery Seguro & Promociones**\n\n"
                 . "• **Modalidades:** Delivery a domicilio, Para Recoger (Takeaway) y Pedidos en Mesa.\n"
                 . "• **Tarifa de envío:** $3.00 estándar y **Envío GRATIS** en pedidos desde $50.00 (o usando cupón).\n"
                 . "• **Doble Seguridad:** Al pedir delivery recibes un **PIN secreto de 4 dígitos** que entregas al repartidor junto a tu firma en pantalla.\n"
                 . "• **Puntos & Cashback:** Acumulas puntos automáticamente con cada pedido desde tu cuenta de comensal." . $couponBlock;
        }

        // 9. Acceso Personal, Roles, Login Google, Manual o PWA Offline
        if (str_contains($q, 'google') || str_contains($q, 'iniciar') || str_contains($q, 'login') || str_contains($q, 'registr') || str_contains($q, 'personal') || str_contains($q, 'staff') || str_contains($q, 'empleado') || str_contains($q, 'manual') || str_contains($q, 'offline') || str_contains($q, 'pwa')) {
            return "🔐 **Accesos, Seguridad y Centro de Ayuda**\n\n"
                 . "• **Clientes / Comensales:** Ingresan por **`/login`** o se registran en **`/register`** (incluye acceso rápido en 1 clic con **Google OAuth 2.0**).\n"
                 . "• **Personal y Administración:** Acceso estricto y exclusivo en **`/staff/login`** para los 5 roles internos (`Administrador`, `Chef`, `Mesero`, `Recepcionista`, `Repartidor`). Los accesos de clientes y empleados están estrictamente separados.\n"
                 . "• **Modo Offline (PWA):** El sistema funciona como app instalable con indicador **En Línea / Sin Conexión** y elimina automáticamente la caché privada al cerrar sesión.\n"
                 . "• **Manual Oficial:** Disponible en línea y en PDF descargable en **`/manual`**.";
        }

        // 10. Menú, Platillos, Cortes, Precios, Presupuesto
        if (str_contains($q, 'menú') || str_contains($q, 'menu') || str_contains($q, 'carta') || str_contains($q, 'corte') || str_contains($q, 'carne') || str_contains($q, 'platillo') || str_contains($q, 'plato') || str_contains($q, 'precio') || str_contains($q, 'comer') || str_contains($q, 'recomiend') || str_contains($q, 'presupuesto')) {
            $allDishes = $context['categories']->flatMap(fn ($c) => $c->dishes->map(fn ($d) => ['dish' => $d, 'cat' => $c->name]));
            if ($allDishes->isNotEmpty()) {
                $lines = [];
                foreach ($allDishes->take(8) as $entry) {
                    $d = $entry['dish'];
                    $lines[] = "• **{$d->name}** *({$entry['cat']})* — **$" . number_format((float) $d->price, 2) . "** *(~{$d->prep_time_minutes} min)*";
                }
                return "🥩 **Recomendaciones de Nuestra Carta en Vivo:**\n\n"
                     . implode("\n", $lines) . "\n\n"
                     . "🔥 *Todos nuestros cortes se preparan a la leña en el término que prefieras (Sellado, Término Medio, Tres Cuartos o Bien Cocido). Puedes explorar la carta completa en **`/#menu`**.*";
            }
        }

        // 11. Bienvenida / Respuesta general según portal
        if ($portal === 'corporate') {
            return "👋 ¡Hola! Soy el **Agente Inteligente de BrazasTech Cloud SaaS**. 🚀\n\n"
                 . "Puedes ponerme a prueba preguntándome cualquier detalle:\n"
                 . "• 🎯 **Identidad Corporativa:** Misión, Visión, Slogan y Políticas de Calidad.\n"
                 . "• 💼 **Planes y Precios SaaS:** Comparativa de Starter ($49), Pro ($99) y Enterprise ($189).\n"
                 . "• ⚙️ **Ecosistema Tecnológico:** POS táctil, Monitor KDS, App Móvil, Planos con IA e Inventarios.\n"
                 . "• 🛡️ **Legal y Seguridad:** Términos y Condiciones, Política de Privacidad, Google OAuth y PWA Offline.\n"
                 . "• 🔐 **Accesos:** Portal de Personal (`/staff/login`) y Manual Interactivo (`/manual`).";
        }

        return "👋 ¡Hola! Soy el **Asistente Inteligente Oficial de Restaurante Las Brazas**. 🔥🥩\n\n"
             . "Estoy aquí para responder cualquier consulta al instante:\n"
             . "• 🥩 **Menú y Recomendaciones:** Cortes a la leña, precios y tiempos de preparación.\n"
             . "• 📍 **Ubicación y Horarios:** Cómo llegar, teléfonos y horarios de atención.\n"
             . "• 📅 **Reservas y Delivery:** Zonas disponibles, tolerancia de 15 min, cupones y PIN de entrega.\n"
             . "• 📜 **Legal y Privacidad:** Términos y Condiciones, Política de Privacidad y registro con Google.\n"
             . "• 🚀 **Sitio Empresarial SaaS:** Misión, Visión y Planes BrazasTech para restaurantes.";
    }
}
