<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Table;
use App\Models\Dish;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(): Response
    {
        // Áreas con sus mesas y órdenes activas
        $areas = Area::with(['tables' => function ($query) {
            $query->with(['orders' => function ($q) {
                $q->whereIn('status', ['pending', 'in_progress'])
                  ->with(['items.dish', 'customer', 'user'])
                  ->latest();
            }]);
        }])->get();

        // Platillos agrupados por categoría para la selección rápida
        $categories = Category::where('is_active', true)
            ->with(['dishes' => function ($query) {
                $query->where('is_available', true);
            }])
            ->get();

        $dishes = Dish::where('is_available', true)->with('category')->get();
        $customers = Customer::orderBy('name')->get();

        // Órdenes activas en curso (en salón y para llevar)
        $activeOrders = Order::whereIn('status', ['pending', 'in_kitchen', 'ready', 'served'])
            ->with(['table.area', 'items.dish', 'customer', 'user'])
            ->latest()
            ->get();

        return Inertia::render('Pos/Index', [
            'areas' => $areas,
            'categories' => $categories,
            'dishes' => $dishes,
            'customers' => $customers,
            'activeOrders' => $activeOrders,
        ]);
    }

    /**
     * Crear una nueva orden / abrir mesa con comanda inicial.
     */
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'nullable|exists:tables,id',
            'customer_id' => 'nullable|exists:customers,id',
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'items' => 'required|array|min:1',
            'items.*.dish_id' => 'required|exists:dishes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $dish = Dish::findOrFail($item['dish_id']);
                $itemSubtotal = (float) $dish->price * (int) $item['quantity'];
                $subtotal += $itemSubtotal;

                $itemsData[] = [
                    'dish_id' => $dish->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $dish->price,
                    'subtotal' => $itemSubtotal,
                    'notes' => $item['notes'] ?? null,
                    'status' => 'pending',
                ];
            }

            // Crear Orden
            $order = Order::create([
                'table_id' => $validated['table_id'] ?? null,
                'user_id' => Auth::id(),
                'customer_id' => $validated['customer_id'] ?? null,
                'order_type' => $validated['order_type'],
                'status' => 'in_kitchen',
                'subtotal' => $subtotal,
                'tax' => 0,
                'total_amount' => $subtotal,
            ]);

            // Guardar items de comanda
            foreach ($itemsData as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }

            // Si es mesa en salón, marcar mesa como ocupada
            if (!empty($validated['table_id'])) {
                Table::where('id', $validated['table_id'])->update(['status' => 'occupied']);
            }

            return $order;
        });

        return redirect()->back()->with('success', "Comanda #{$order->id} enviada a cocina exitosamente.");
    }

    /**
     * Agregar más platillos a una orden existente abierta.
     */
    public function addItem(Request $request, Order $order)
    {
        $validated = $request->validate([
            'dish_id' => 'required|exists:dishes,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($order, $validated) {
            $dish = Dish::findOrFail($validated['dish_id']);
            $itemSubtotal = (float) $dish->price * (int) $validated['quantity'];

            OrderItem::create([
                'order_id' => $order->id,
                'dish_id' => $dish->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $dish->price,
                'subtotal' => $itemSubtotal,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            // Recalcular total de la orden
            $newSubtotal = $order->items()->sum('subtotal');
            $order->update([
                'subtotal' => $newSubtotal,
                'total_amount' => $newSubtotal + (float) $order->tax,
            ]);
        });

        return redirect()->back()->with('success', 'Platillo agregado a la comanda.');
    }

    /**
     * Cobrar orden, registrar pago, liberar mesa y DESCONTAR INVENTARIO automáticamente.
     */
    public function closeOrder(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,transfer',
            'amount_paid' => 'required|numeric|min:0.01',
            'transaction_ref' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($order, $validated) {
            // 1. Registrar pago
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['amount_paid'],
                'transaction_ref' => $validated['transaction_ref'] ?? null,
                'paid_at' => now(),
            ]);

            // 2. Marcar orden como pagada
            $order->update(['status' => 'paid']);

            // 3. Liberar mesa asociada
            if ($order->table_id) {
                Table::where('id', $order->table_id)->update(['status' => 'available']);
            }

            // 4. DESCUENTO AUTOMÁTICO DE INVENTARIO POR RECETA
            $order->loadMissing('items.dish.recipe.ingredients');
            foreach ($order->items as $item) {
                if ($item->dish) {
                    $this->inventoryService->deductForDish(
                        dish: $item->dish,
                        quantity: (int) $item->quantity,
                        userId: Auth::id(),
                        reason: "Venta Comanda #{$order->id} (Mesa: " . ($order->table?->table_number ?? 'Para llevar') . ")"
                    );
                }
            }
        });

        return redirect()->back()->with('success', "Cuenta de Comanda #{$order->id} cerrada y cobrada con éxito. Inventario descontado.");
    }

    /**
     * Cancelar una comanda y liberar mesa.
     */
    public function cancelOrder(Order $order)
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);
            if ($order->table_id) {
                Table::where('id', $order->table_id)->update(['status' => 'available']);
            }
        });

        return redirect()->back()->with('success', "Comanda #{$order->id} cancelada.");
    }

    /**
     * Generar e imprimir Ticket Térmico en PDF para una comanda.
     */
    public function ticketPdf(Order $order)
    {
        $order->load(['table.area', 'items.dish', 'customer', 'user', 'payments']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.comanda_ticket', compact('order'))
            ->setPaper([0, 0, 226.77, 600], 'portrait') // 80mm ancho térmico
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'Courier',
            ]);

        return $pdf->stream("ticket_comanda_{$order->id}.pdf");
    }
}
