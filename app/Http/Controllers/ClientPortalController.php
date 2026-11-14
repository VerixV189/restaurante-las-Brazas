<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Models\Area;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Table;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ClientPortalController extends Controller
{
    private const DELIVERY_FEE = 3.00;
    private const FREE_DELIVERY_OVER = 50.00;

    public function __construct(private InventoryService $inventory) {}

    public static function customerFor(Request $request): Customer
    {
        $user = $request->user();
        return Customer::firstOrCreate(
            ['email' => $user->email],
            ['name' => $user->name, 'phone' => $user->phone ?? 'Sin teléfono']
        );
    }

    /**
     * Renderiza el Portal / Marketplace exclusivo del Cliente.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $customer = self::customerFor($request);

        $categories = Category::where('is_active', true)
            ->withCount(['dishes' => fn ($q) => $q->where('is_available', true)])
            ->get(['id', 'name', 'slug', 'description', 'icon']);

        $dishes = Dish::with('category:id,name,slug')
            ->where('is_available', true)
            ->orderBy('name')
            ->get()
            ->map(function ($dish) {
                $imageUrl = $dish->image_url;
                if (empty($imageUrl)) {
                    $imageUrl = stripos($dish->name, 'arrachera') !== false
                        ? url('/images/arrachera.png')
                        : 'https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=600&auto=format&fit=crop';
                } elseif (! str_starts_with($imageUrl, 'http')) {
                    $imageUrl = url($imageUrl);
                }

                $isMeat = stripos($dish->name, 'corte') !== false
                       || stripos($dish->name, 'arrachera') !== false
                       || stripos($dish->name, 'bife') !== false
                       || stripos($dish->name, 'picaña') !== false
                       || stripos($dish->name, 'tomahawk') !== false
                       || stripos($dish->name, 'parrillada') !== false
                       || (optional($dish->category)->slug === 'cortes-y-parrilladas');

                return [
                    'id' => $dish->id,
                    'category_id' => $dish->category_id,
                    'category_name' => optional($dish->category)->name,
                    'name' => $dish->name,
                    'description' => $dish->description,
                    'price' => (float) $dish->price,
                    'image_url' => $imageUrl,
                    'prep_time_minutes' => (int) $dish->prep_time_minutes,
                    'is_available' => (bool) $dish->is_available,
                    'is_meat' => $isMeat,
                    'meat_cooking_terms' => $isMeat ? [
                        'Vuelta y vuelta (Sellado)',
                        'Término Medio (A punto)',
                        'Tres Cuartos (Jugoso)',
                        'Bien Cocido',
                    ] : [],
                ];
            });

        $ordersRaw = Order::where(function ($q) use ($user, $customer) {
            $q->where('customer_id', $customer->id)
              ->orWhere('user_id', $user->id);
        })
        ->with(['items.dish', 'payments', 'table.area'])
        ->latest()
        ->take(30)
        ->get();

        $orders = $ordersRaw->map(fn ($o) => $this->formatOrder($o))->values();

        $addresses = $customer->addresses()->orderByDesc('is_default')->get()->map(fn ($a) => [
            'id' => $a->id,
            'label' => $a->label ?: 'Mi Dirección',
            'street' => $a->street,
            'number' => $a->number,
            'floor' => $a->floor,
            'reference' => $a->reference,
            'is_default' => (bool) $a->is_default,
            'full_text' => $a->fullText(),
        ]);

        $coupons = Coupon::where('is_active', true)->get()->map(fn ($c) => [
            'id' => $c->id,
            'code' => $c->code,
            'type' => $c->type,
            'value' => (float) $c->value,
            'min_order' => (float) $c->min_order,
            'expires_at' => $c->expires_at?->format('d/m/Y'),
        ]);

        $reservations = Reservation::where(function ($q) use ($user, $customer) {
            $q->where('customer_id', $customer->id)
              ->orWhere('user_id', $user->id);
        })
        ->with(['table.area', 'area'])
        ->latest('reservation_date')
        ->take(20)
        ->get()
        ->map(fn ($r) => [
            'id' => $r->id,
            'reservation_date' => Carbon::parse($r->reservation_date)->format('d/m/Y H:i'),
            'raw_date' => Carbon::parse($r->reservation_date)->toIso8601String(),
            'guest_count' => (int) $r->guest_count,
            'children_count' => (int) ($r->children_count ?? 0),
            'status' => $r->status,
            'area_name' => $r->area?->name ?? $r->table?->area?->name ?? 'Salón Principal',
            'table_number' => $r->table?->table_number ?? 'Por asignar',
            'special_requests' => $r->special_requests,
        ]);

        $areas = Area::with('tables')->withSum('tables as capacity', 'capacity')->get()->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'description' => $a->description,
            'capacity' => (int) ($a->capacity ?? 0),
            'available_tables' => $a->tables->where('status', 'available')->count(),
        ]);

        $tables = Table::with('area:id,name')->orderBy('id')->get()->map(fn ($t) => [
            'id' => $t->id,
            'table_number' => $t->table_number,
            'capacity' => $t->capacity,
            'status' => $t->status,
            'area_name' => $t->area?->name ?? 'Salón',
        ]);

        $totalSpent = (float) $ordersRaw->where('status', '!=', 'cancelled')->sum('total_amount');
        $loyaltyPoints = 100 + (int) round($totalSpent);

        return Inertia::render('Client/Portal', [
            'categories' => $categories,
            'dishes' => $dishes,
            'orders' => $orders,
            'addresses' => $addresses,
            'coupons' => $coupons,
            'reservations' => $reservations,
            'areas' => $areas,
            'tables' => $tables,
            'userProfile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? $customer->phone,
                'email_verified_at' => $user->email_verified_at?->format('d/m/Y H:i'),
                'loyalty_points' => $loyaltyPoints,
                'cashback_balance' => round($loyaltyPoints * 0.05, 2),
            ],
            'initialTab' => $request->query('tab', 'menu'),
        ]);
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', strtoupper(trim($data['code'])))->first();
        if (! $coupon) {
            return response()->json(['success' => false, 'message' => 'El código de cupón no existe.'], 422);
        }

        if ($err = $coupon->validationError((float) $data['subtotal'])) {
            return response()->json(['success' => false, 'message' => $err], 422);
        }

        [$discount, $freeShipping] = $coupon->apply((float) $data['subtotal']);

        return response()->json([
            'success' => true,
            'message' => "¡Cupón {$coupon->code} aplicado con éxito!",
            'data' => [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'discount' => $discount,
                'free_shipping' => $freeShipping,
            ],
        ]);
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.dish_id' => 'required|exists:dishes,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'items.*.notes' => 'nullable|string|max:255',
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'table_id' => 'required_if:order_type,dine_in|nullable|exists:tables,id',
            'address_id' => 'nullable|integer',
            'delivery_address' => 'nullable|string|max:500',
            'coupon_code' => 'nullable|string',
            'invoice_name' => 'nullable|string|max:255',
            'invoice_tax_id' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'payment_method' => 'required|in:cash,card,transfer',
            'transaction_ref' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $customer = self::customerFor($request);

        $address = null;
        if ($data['order_type'] === 'delivery') {
            if (! empty($data['address_id'])) {
                $addrModel = $customer->addresses()->find($data['address_id']);
                $address = $addrModel?->fullText();
            }
            if (! $address) {
                $address = $data['delivery_address'] ?? null;
            }
            if (! $address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor selecciona o escribe una dirección de envío para Delivery.',
                ], 422);
            }
        }

        $lines = [];
        $subtotal = 0.0;
        $maxPrep = 0;

        foreach ($data['items'] as $item) {
            $dish = Dish::findOrFail($item['dish_id']);
            if (! $dish->is_available) {
                return response()->json([
                    'success' => false,
                    'message' => "El platillo '{$dish->name}' no está disponible en este momento.",
                ], 422);
            }
            $lineTotal = (float) $dish->price * (int) $item['quantity'];
            $subtotal += $lineTotal;
            $maxPrep = max($maxPrep, (int) $dish->prep_time_minutes);
            $lines[] = [
                'dish' => $dish,
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $dish->price,
                'subtotal' => $lineTotal,
                'notes' => $item['notes'] ?? null,
            ];
        }

        $coupon = null;
        $discount = 0.0;
        $freeShipping = false;

        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', strtoupper(trim($data['coupon_code'])))->first();
            if ($coupon && ! $coupon->validationError($subtotal)) {
                [$discount, $freeShipping] = $coupon->apply($subtotal);
            }
        }

        $fee = 0.0;
        if ($data['order_type'] === 'delivery') {
            $fee = ($freeShipping || $subtotal >= self::FREE_DELIVERY_OVER) ? 0.0 : self::DELIVERY_FEE;
        }

        $tax = round(($subtotal - $discount) * (float) env('ORDER_TAX_RATE', 0), 2);
        $total = round(max(0, $subtotal - $discount + $tax + $fee), 2);

        $order = DB::transaction(function () use ($data, $lines, $subtotal, $discount, $tax, $fee, $total, $coupon, $customer, $user, $address) {
            $isOnlinePaid = $data['payment_method'] !== 'cash';

            $order = Order::create([
                'table_id' => $data['order_type'] === 'dine_in' ? ($data['table_id'] ?? null) : null,
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'order_type' => $data['order_type'],
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total_amount' => $total,
                'delivery_fee' => $fee,
                'discount' => $discount,
                'coupon_code' => $coupon?->code,
                'delivery_address' => $address,
                'invoice_name' => $data['invoice_name'] ?? null,
                'invoice_tax_id' => $data['invoice_tax_id'] ?? null,
                'delivery_pin' => $data['order_type'] === 'delivery'
                    ? str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT)
                    : null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $l) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'dish_id' => $l['dish']->id,
                    'quantity' => $l['quantity'],
                    'unit_price' => $l['unit_price'],
                    'subtotal' => $l['subtotal'],
                    'notes' => $l['notes'],
                    'status' => 'pending',
                ]);
                $this->inventory->deductForDish($l['dish'], $l['quantity'], $user->id, "Pedido Web Cliente #{$order->id}");
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $data['payment_method'],
                'amount' => $total,
                'transaction_ref' => $data['transaction_ref'] ?? ($isOnlinePaid ? 'WEB-' . time() : 'CONTRA-ENTREGA'),
                'paid_at' => now(),
            ]);

            if ($coupon) {
                $coupon->increment('used_count');
            }
            if ($order->table_id) {
                Table::where('id', $order->table_id)->update(['status' => 'occupied']);
            }

            return $order;
        });

        $order->load(['items.dish', 'payments', 'table.area']);

        return response()->json([
            'success' => true,
            'message' => "¡Pedido #{$order->id} confirmado y enviado a Cocina (KDS)!",
            'data' => $this->formatOrder($order),
        ], 201);
    }

    public function cancelOrder(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = self::customerFor($request);

        $order = Order::where('id', $id)
            ->where(function ($q) use ($user, $customer) {
                $q->where('customer_id', $customer->id)->orWhere('user_id', $user->id);
            })
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede cancelar un pedido que aún no ha entrado a preparación en cocina.',
            ], 422);
        }

        $order->update(['status' => 'cancelled']);
        if ($order->table_id) {
            Table::where('id', $order->table_id)->update(['status' => 'available']);
        }

        return response()->json([
            'success' => true,
            'message' => "El Pedido #{$order->id} fue cancelado.",
        ]);
    }

    public function invoice(Request $request, int $id)
    {
        $user = $request->user();
        $customer = self::customerFor($request);

        $order = Order::where('id', $id)
            ->where(function ($q) use ($user, $customer) {
                $q->where('customer_id', $customer->id)->orWhere('user_id', $user->id);
            })
            ->with(['table.area', 'items.dish', 'customer', 'user', 'payments'])
            ->firstOrFail();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.comanda_ticket', compact('order'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->stream("comprobante_pedido_{$order->id}.pdf");
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => 'nullable|string|max:50',
            'street' => 'required|string|max:255',
            'number' => 'nullable|string|max:30',
            'floor' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);

        $customer = self::customerFor($request);
        if (! empty($data['is_default'])) {
            $customer->addresses()->update(['is_default' => false]);
        }
        $address = $customer->addresses()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Dirección guardada correctamente.',
            'data' => [
                'id' => $address->id,
                'label' => $address->label ?: 'Mi Dirección',
                'street' => $address->street,
                'number' => $address->number,
                'floor' => $address->floor,
                'reference' => $address->reference,
                'is_default' => (bool) $address->is_default,
                'full_text' => $address->fullText(),
            ],
        ], 201);
    }

    public function destroyAddress(Request $request, int $id): JsonResponse
    {
        $customer = self::customerFor($request);
        $customer->addresses()->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dirección eliminada.',
        ]);
    }

    public function storeReservation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reservation_date' => 'required|date|after:now',
            'adults' => 'required|integer|min:1|max:30',
            'children' => 'nullable|integer|min:0|max:20',
            'area_id' => 'required|exists:areas,id',
            'special_requests' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $customer = self::customerFor($request);
        $when = Carbon::parse($data['reservation_date']);
        $guests = (int) $data['adults'] + (int) ($data['children'] ?? 0);

        $table = Table::where('area_id', $data['area_id'])
            ->where('capacity', '>=', $guests)
            ->orderByRaw("CASE WHEN status = 'available' THEN 0 ELSE 1 END")
            ->orderBy('capacity', 'asc')
            ->first();

        $reservation = Reservation::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'area_id' => $data['area_id'],
            'table_id' => $table?->id,
            'reservation_date' => $when,
            'guest_count' => $guests,
            'children_count' => (int) ($data['children'] ?? 0),
            'status' => 'confirmed',
            'special_requests' => $data['special_requests'] ?? null,
        ]);

        $reservation->load(['table.area', 'area']);

        return response()->json([
            'success' => true,
            'message' => '¡Reserva confirmada! Recuerda que tienes 15 minutos de tolerancia.',
            'data' => [
                'id' => $reservation->id,
                'reservation_date' => Carbon::parse($reservation->reservation_date)->format('d/m/Y H:i'),
                'raw_date' => Carbon::parse($reservation->reservation_date)->toIso8601String(),
                'guest_count' => (int) $reservation->guest_count,
                'children_count' => (int) ($reservation->children_count ?? 0),
                'status' => $reservation->status,
                'area_name' => $reservation->area?->name ?? $reservation->table?->area?->name ?? 'Salón Principal',
                'table_number' => $reservation->table?->table_number ?? 'Por asignar',
                'special_requests' => $reservation->special_requests,
            ],
        ], 201);
    }

    public function cancelReservation(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = self::customerFor($request);

        $reservation = Reservation::where('id', $id)
            ->where(function ($q) use ($user, $customer) {
                $q->where('customer_id', $customer->id)->orWhere('user_id', $user->id);
            })
            ->firstOrFail();

        $reservation->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => "Reservación #{$reservation->id} cancelada.",
        ]);
    }

    private function formatOrder(Order $o): array
    {
        $tracking = ApiOrderController::timeline($o);

        return [
            'id' => $o->id,
            'order_type' => $o->order_type,
            'status' => $o->status,
            'tracking' => $tracking,
            'subtotal' => (float) $o->subtotal,
            'discount' => (float) $o->discount,
            'tax' => (float) $o->tax,
            'delivery_fee' => (float) $o->delivery_fee,
            'total' => (float) $o->total_amount,
            'created_at' => $o->created_at?->format('d/m/Y H:i'),
            'items_count' => (int) $o->items->sum('quantity'),
            'delivery_address' => $o->delivery_address,
            'delivery_pin' => $o->delivery_pin,
            'coupon_code' => $o->coupon_code,
            'notes' => $o->notes,
            'invoice_name' => $o->invoice_name,
            'invoice_tax_id' => $o->invoice_tax_id,
            'table_number' => $o->table?->table_number,
            'payment_method' => $o->payments->first()?->payment_method ?? 'card',
            'items' => $o->items->map(fn ($i) => [
                'dish_id' => $i->dish_id,
                'name' => $i->dish?->name ?? 'Platillo',
                'quantity' => (int) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'subtotal' => (float) $i->subtotal,
                'notes' => $i->notes,
            ])->values(),
        ];
    }
}

