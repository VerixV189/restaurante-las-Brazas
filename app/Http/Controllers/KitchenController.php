<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KitchenController extends Controller
{
    public function index(): Response
    {
        // Obtener órdenes activas en cocina
        $orders = Order::whereIn('status', ['pending', 'in_kitchen', 'ready'])
            ->with([
                'table.area',
                'user',
                'customer',
                'items' => function ($query) {
                    $query->with(['dish.category', 'dish.recipe']);
                }
            ])
            ->oldest() // Las más antiguas primero (FIFO - First In First Out)
            ->get();

        // Contadores en tiempo real
        $stats = [
            'pending_orders' => Order::where('status', 'pending')->count(),
            'cooking_orders' => Order::where('status', 'in_kitchen')->count(),
            'ready_orders' => Order::where('status', 'ready')->count(),
            'total_active_dishes' => OrderItem::whereIn('status', ['pending', 'cooking'])->count(),
        ];

        return Inertia::render('Kitchen/Index', [
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }

    /**
     * Actualiza el estado de un platillo individual dentro de la comanda.
     */
    public function updateItemStatus(Request $request, OrderItem $item)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,cooking,ready,served',
        ]);

        $item->update(['status' => $validated['status']]);

        // Verificar el estado global de la orden
        $order = $item->order;
        if ($order) {
            $items = $order->items;
            $allServed = $items->every(fn ($i) => $i->status === 'served');
            $allReady = $items->every(fn ($i) => in_array($i->status, ['ready', 'served']));

            if ($allServed) {
                $order->update(['status' => 'served']);
            } elseif ($allReady) {
                $order->update(['status' => 'ready']);
            } else {
                $order->update(['status' => 'in_kitchen']);
            }
        }

        return redirect()->back()->with('success', 'Estado del platillo actualizado.');
    }

    /**
     * Avanzar todos los platillos de una comanda al siguiente estado (Cocinar todos / Servir todos).
     */
    public function advanceOrderStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'target_status' => 'required|in:cooking,ready,served',
        ]);

        $targetStatus = $validated['target_status'];

        $order->items()->update(['status' => $targetStatus]);

        if ($targetStatus === 'cooking') {
            $order->update(['status' => 'in_kitchen']);
        } elseif ($targetStatus === 'ready') {
            $order->update(['status' => 'ready']);
        } elseif ($targetStatus === 'served') {
            $order->update(['status' => 'served']);
        }

        return redirect()->back()->with('success', "Comanda #{$order->id} marcada como {$targetStatus}.");
    }
}
