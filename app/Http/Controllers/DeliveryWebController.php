<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryWebController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. Pedidos de delivery pendientes de asignar repartidor
        $pendingOrders = Order::with(['customer', 'items.dish'])
            ->where('order_type', 'delivery')
            ->whereNull('driver_id')
            ->whereIn('status', ['pending', 'in_kitchen', 'ready'])
            ->latest()
            ->get()
            ->map(fn ($o) => $this->formatOrder($o))
            ->values();

        // 2. Pedidos asignados al repartidor actual (o todos los asignados en curso si es Administrador)
        $myOrdersQuery = Order::with(['customer', 'items.dish', 'driver'])
            ->where('order_type', 'delivery')
            ->whereNull('delivered_at')
            ->where('status', '!=', 'cancelled');

        if ($user->role?->name === 'Repartidor') {
            $myOrdersQuery->where('driver_id', $user->id);
        } else {
            $myOrdersQuery->whereNotNull('driver_id');
        }

        $myOrders = $myOrdersQuery
            ->latest()
            ->get()
            ->map(fn ($o) => $this->formatOrder($o, $user->role?->name === 'Administrador'))
            ->values();

        // 3. Historial de entregas completadas
        $completedQuery = Order::with(['customer', 'items.dish', 'driver'])
            ->where('order_type', 'delivery')
            ->whereNotNull('delivered_at');

        if ($user->role?->name === 'Repartidor') {
            $completedQuery->where('driver_id', $user->id);
        }

        $completedOrders = $completedQuery
            ->latest('delivered_at')
            ->take(20)
            ->get()
            ->map(fn ($o) => $this->formatOrder($o, true))
            ->values();

        return Inertia::render('Delivery/Index', [
            'pendingOrders' => $pendingOrders,
            'myOrders' => $myOrders,
            'completedOrders' => $completedOrders,
            'driverProfile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role?->name,
            ],
        ]);
    }

    public function take(Request $request, int $id): JsonResponse
    {
        $order = Order::where('order_type', 'delivery')
            ->whereNull('driver_id')
            ->findOrFail($id);

        $order->update(['driver_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => "Te has asignado el Pedido #{$order->id}.",
        ]);
    }

    public function pickup(Request $request, int $id): JsonResponse
    {
        $order = Order::where('order_type', 'delivery')->findOrFail($id);

        if ($order->status !== 'ready') {
            $order->update([
                'status' => 'ready',
                'dispatched_at' => now(),
            ]);
        } else {
            $order->update(['dispatched_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => "Pedido #{$order->id} marcado como recogido y En Camino al domicilio.",
        ]);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $order = Order::where('order_type', 'delivery')->findOrFail($id);

        if ($order->delivery_pin && $order->delivery_pin !== $data['pin']) {
            return response()->json([
                'success' => false,
                'message' => 'El código PIN de 4 dígitos no coincide con el del cliente.',
            ], 422);
        }

        $order->update([
            'delivered_at' => now(),
            'status' => 'paid',
        ]);

        return response()->json([
            'success' => true,
            'message' => "¡Entrega del Pedido #{$order->id} confirmada exitosamente!",
        ]);
    }

    private function formatOrder(Order $o, bool $includePinHint = false): array
    {
        $address = $o->delivery_address ?: 'Dirección registrada en pedido';
        $mapsQuery = urlencode($address . ', Santa Cruz, Bolivia');

        return [
            'id' => $o->id,
            'status' => $o->status,
            'tracking' => ApiOrderController::timeline($o),
            'customer_name' => $o->customer?->name ?? 'Cliente App',
            'customer_phone' => $o->customer?->phone ?? 'Sin teléfono',
            'address' => $address,
            'total' => (float) $o->total_amount,
            'delivery_fee' => (float) $o->delivery_fee,
            'dispatched_at' => $o->dispatched_at?->format('H:i'),
            'delivered_at' => $o->delivered_at?->format('d/m/Y H:i'),
            'created_at' => $o->created_at?->format('d/m/Y H:i'),
            'driver_name' => $o->driver?->name,
            'delivery_pin' => $includePinHint ? $o->delivery_pin : null,
            'google_maps_url' => $o->delivery_lat
                ? "https://www.google.com/maps/dir/?api=1&destination={$o->delivery_lat},{$o->delivery_lng}"
                : "https://www.google.com/maps/search/?api=1&query={$mapsQuery}",
            'waze_url' => $o->delivery_lat
                ? "https://waze.com/ul?ll={$o->delivery_lat},{$o->delivery_lng}&navigate=yes"
                : "https://waze.com/ul?q={$mapsQuery}&navigate=yes",
            'items' => $o->items->map(fn ($i) => [
                'name' => $i->dish?->name ?? 'Platillo',
                'quantity' => (int) $i->quantity,
                'notes' => $i->notes,
            ])->values(),
        ];
    }
}

