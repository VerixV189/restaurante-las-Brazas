<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Table;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private const DELIVERY_FEE = 3.00;
    private const FREE_DELIVERY_OVER = 50.00;

    public function __construct(private InventoryService $inventory) {}

    /** Calcula desglose del carrito (subtotal, impuestos, envío, descuento, tiempo). */
    private function calculate(array $items, string $type, ?string $couponCode): array
    {
        $lines = [];
        $subtotal = 0.0;
        $maxPrep = 0;
        foreach ($items as $item) {
            $dish = Dish::findOrFail($item['dish_id']);
            abort_unless($dish->is_available, 422, "El platillo '{$dish->name}' no está disponible.");
            $lineTotal = (float) $dish->price * (int) $item['quantity'];
            $subtotal += $lineTotal;
            $maxPrep = max($maxPrep, (int) $dish->prep_time_minutes);
            $lines[] = [
                'dish' => $dish, 'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $dish->price, 'subtotal' => $lineTotal,
                'notes' => $item['notes'] ?? null,
            ];
        }

        $coupon = null;
        $discount = 0.0;
        $freeShipping = false;
        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper($couponCode))->first();
            abort_unless($coupon, 422, 'Cupón inválido.');
            if ($err = $coupon->validationError($subtotal)) abort(422, $err);
            [$discount, $freeShipping] = $coupon->apply($subtotal);
        }

        $fee = 0.0;
        if ($type === 'delivery') {
            $fee = ($freeShipping || $subtotal >= self::FREE_DELIVERY_OVER) ? 0.0 : self::DELIVERY_FEE;
        }
        $tax = round(($subtotal - $discount) * (float) env('ORDER_TAX_RATE', 0), 2);
        $total = round($subtotal - $discount + $tax + $fee, 2);
        $eta = $maxPrep + ($type === 'delivery' ? 30 : 0);

        return compact('lines', 'subtotal', 'discount', 'tax', 'fee', 'total', 'eta', 'coupon') + [
            'estimated_minutes' => [$eta, $eta + 15],
        ];
    }

    private function summary(array $c): array
    {
        return [
            'subtotal' => round($c['subtotal'], 2), 'discount' => $c['discount'], 'tax' => $c['tax'],
            'delivery_fee' => $c['fee'], 'total' => $c['total'],
            'estimated_minutes' => $c['estimated_minutes'],
        ];
    }

    private const ITEM_RULES = [
        'items' => 'required|array|min:1',
        'items.*.dish_id' => 'required|exists:dishes,id',
        'items.*.quantity' => 'required|integer|min:1|max:50',
        'items.*.notes' => 'nullable|string|max:255',
    ];

    /** Cotización en vivo del carrito: subtotal, impuestos, envío y tiempo. */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate(self::ITEM_RULES + [
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'coupon_code' => 'nullable|string',
        ]);
        $c = $this->calculate($data['items'], $data['order_type'], $data['coupon_code'] ?? null);
        return response()->json(['success' => true, 'data' => $this->summary($c)]);
    }

    /** Validar cupón de descuento. */
    public function validateCoupon(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string', 'subtotal' => 'required|numeric|min:0']);
        $coupon = Coupon::where('code', strtoupper($data['code']))->first();
        if (! $coupon) {
            return response()->json(['success' => false, 'message' => 'Cupón inválido.'], 422);
        }
        if ($err = $coupon->validationError((float) $data['subtotal'])) {
            return response()->json(['success' => false, 'message' => $err], 422);
        }
        [$discount, $free] = $coupon->apply((float) $data['subtotal']);
        return response()->json(['success' => true, 'data' => [
            'code' => $coupon->code, 'type' => $coupon->type, 'discount' => $discount, 'free_shipping' => $free,
        ]]);
    }

    /** Crear pedido: entra a cocina (KDS) y descuenta inventario. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(self::ITEM_RULES + [
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'table_id' => 'required_if:order_type,dine_in|nullable|exists:tables,id',
            'address_id' => 'nullable|integer',
            'delivery_address' => 'nullable|string|max:500',
            'delivery_lat' => 'nullable|numeric|between:-90,90',
            'delivery_lng' => 'nullable|numeric|between:-180,180',
            'coupon_code' => 'nullable|string',
            'invoice_name' => 'nullable|string|max:255',
            'invoice_tax_id' => 'required_with:invoice_name|nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'payment_method' => 'nullable|in:cash,card,transfer',
        ]);

        $customer = CustomerController::customerFor($request);
        $address = $lat = $lng = null;
        if ($data['order_type'] === 'delivery') {
            if (! empty($data['address_id'])) {
                $a = $customer->addresses()->findOrFail($data['address_id']);
                $address = $a->fullText(); $lat = $a->latitude; $lng = $a->longitude;
            } else {
                $address = $data['delivery_address'] ?? null;
                $lat = $data['delivery_lat'] ?? null; $lng = $data['delivery_lng'] ?? null;
            }
            abort_unless($address, 422, 'Se requiere una dirección de entrega.');
        }

        $c = $this->calculate($data['items'], $data['order_type'], $data['coupon_code'] ?? null);

        $order = DB::transaction(function () use ($data, $c, $customer, $request, $address, $lat, $lng) {
            $order = Order::create([
                'table_id' => $data['order_type'] === 'dine_in' ? $data['table_id'] : null,
                'user_id' => null,
                'customer_id' => $customer->id,
                'order_type' => $data['order_type'],
                'status' => 'pending',
                'subtotal' => $c['subtotal'], 'tax' => $c['tax'], 'total_amount' => $c['total'],
                'delivery_fee' => $c['fee'], 'discount' => $c['discount'],
                'coupon_code' => $c['coupon']?->code,
                'delivery_address' => $address, 'delivery_lat' => $lat, 'delivery_lng' => $lng,
                'invoice_name' => $data['invoice_name'] ?? null,
                'invoice_tax_id' => $data['invoice_tax_id'] ?? null,
                'delivery_pin' => $data['order_type'] === 'delivery' ? str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT) : null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($c['lines'] as $l) {
                OrderItem::create([
                    'order_id' => $order->id, 'dish_id' => $l['dish']->id, 'quantity' => $l['quantity'],
                    'unit_price' => $l['unit_price'], 'subtotal' => $l['subtotal'],
                    'notes' => $l['notes'], 'status' => 'pending',
                ]);
                $this->inventory->deductForDish($l['dish'], $l['quantity'], null, "Pedido móvil #{$order->id}");
            }

            if ($c['coupon']) $c['coupon']->increment('used_count');
            if ($order->table_id) Table::where('id', $order->table_id)->update(['status' => 'occupied']);

            return $order;
        });

        return response()->json(['success' => true, 'message' => 'Pedido recibido.', 'data' => $this->format($order->fresh('items.dish'), true)], 201);
    }

    /** Historial de pedidos del cliente. */
    public function history(Request $request): JsonResponse
    {
        $orders = CustomerController::customerFor($request)->orders()
            ->with('items.dish')->latest()->paginate(15);
        return response()->json(['success' => true, 'data' => $orders->through(fn ($o) => $this->format($o))]);
    }

    /** Estado y línea de tiempo del pedido. */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->ownOrder($request, $id);
        return response()->json(['success' => true, 'data' => $this->format($order, true)]);
    }

    /** Pago: online (card), transferencia/QR o contra entrega (cash). */
    public function pay(Request $request, int $id): JsonResponse
    {
        $order = $this->ownOrder($request, $id);
        $data = $request->validate([
            'payment_method' => 'required|in:cash,card,transfer',
            'transaction_ref' => 'required_if:payment_method,card,transfer|nullable|string|max:255',
        ]);
        abort_if(in_array($order->status, ['paid', 'cancelled']), 422, 'El pedido no admite pagos.');

        // cash = contra entrega: se registra el método y se cobra al entregar.
        $isOnline = $data['payment_method'] !== 'cash';
        DB::transaction(function () use ($order, $data, $isOnline) {
            Payment::create([
                'order_id' => $order->id, 'payment_method' => $data['payment_method'],
                'amount' => $order->total_amount, 'transaction_ref' => $data['transaction_ref'] ?? null,
                'paid_at' => now(),
            ]);
            if ($isOnline) $order->update(['status' => 'paid']);
        });
        return response()->json([
            'success' => true,
            'message' => $isOnline ? 'Pago registrado.' : 'Pagarás en efectivo/POS contra entrega.',
        ]);
    }

    /** Descargar comprobante PDF. */
    public function invoice(Request $request, int $id)
    {
        $order = $this->ownOrder($request, $id);
        $order->load(['table.area', 'items.dish', 'customer', 'user', 'payments']);
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.comanda_ticket', compact('order'))
            ->setPaper([0, 0, 226.77, 600], 'portrait')
            ->stream("comprobante_{$order->id}.pdf");
    }

    /** Volver a pedir: devuelve los ítems del pedido para recargar el carrito. */
    public function reorder(Request $request, int $id): JsonResponse
    {
        $order = $this->ownOrder($request, $id);
        $items = $order->items->filter(fn ($i) => $i->dish?->is_available)->map(fn ($i) => [
            'dish_id' => $i->dish_id, 'name' => $i->dish->name,
            'quantity' => $i->quantity, 'notes' => $i->notes,
        ])->values();
        return response()->json(['success' => true, 'data' => $items]);
    }

    /** Cancelar mientras siga pendiente. */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = $this->ownOrder($request, $id);
        abort_unless($order->status === 'pending', 422, 'Solo se puede cancelar un pedido que aún no entró a cocina.');
        $order->update(['status' => 'cancelled']);
        if ($order->table_id) Table::where('id', $order->table_id)->update(['status' => 'available']);
        return response()->json(['success' => true, 'message' => 'Pedido cancelado.']);
    }

    private function ownOrder(Request $request, int $id): Order
    {
        return CustomerController::customerFor($request)->orders()
            ->with('items.dish', 'payments')->findOrFail($id);
    }

    public static function timeline(Order $o): array
    {
        $current = match (true) {
            $o->status === 'cancelled' => 'cancelled',
            (bool) $o->delivered_at => 'delivered',
            (bool) $o->dispatched_at => 'on_the_way',
            in_array($o->status, ['served', 'paid']) && $o->order_type !== 'delivery' => 'delivered',
            $o->status === 'ready' => 'ready',
            $o->status === 'in_kitchen' => 'in_kitchen',
            default => 'received',
        };
        return [
            'current' => $current,
            'steps' => $o->order_type === 'delivery'
                ? ['received', 'in_kitchen', 'ready', 'on_the_way', 'delivered']
                : ['received', 'in_kitchen', 'ready', 'delivered'],
        ];
    }

    public static function format(Order $o, bool $detail = false): array
    {
        $base = [
            'id' => $o->id, 'order_type' => $o->order_type, 'status' => $o->status,
            'tracking' => self::timeline($o),
            'subtotal' => $o->subtotal, 'discount' => $o->discount, 'tax' => $o->tax,
            'delivery_fee' => $o->delivery_fee, 'total' => $o->total_amount,
            'created_at' => $o->created_at?->toIso8601String(),
            'items_count' => $o->items->sum('quantity'),
        ];
        if (! $detail) return $base;
        return $base + [
            'delivery_address' => $o->delivery_address, 'delivery_pin' => $o->delivery_pin,
            'coupon_code' => $o->coupon_code, 'notes' => $o->notes,
            'invoice_name' => $o->invoice_name, 'invoice_tax_id' => $o->invoice_tax_id,
            'items' => $o->items->map(fn ($i) => [
                'dish_id' => $i->dish_id, 'name' => $i->dish?->name, 'quantity' => $i->quantity,
                'unit_price' => $i->unit_price, 'subtotal' => $i->subtotal, 'notes' => $i->notes,
            ]),
        ];
    }
}

