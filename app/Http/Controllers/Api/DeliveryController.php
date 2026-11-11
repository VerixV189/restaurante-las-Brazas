<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    private function ensureDriver(Request $request): void
    {
        $role = $request->user()->load('role')->role?->name;
        abort_unless(in_array($role, ['Repartidor', 'Administrador']), 403, 'Solo repartidores.');
    }

    private function row(Order $o): array
    {
        return [
            'id' => $o->id, 'status' => $o->status, 'tracking' => OrderController::timeline($o),
            'customer' => $o->customer?->name, 'phone' => $o->customer?->phone,
            'address' => $o->delivery_address, 'lat' => $o->delivery_lat, 'lng' => $o->delivery_lng,
            'total' => $o->total_amount,
            'navigation_url' => $o->delivery_lat
                ? "https://www.google.com/maps/dir/?api=1&destination={$o->delivery_lat},{$o->delivery_lng}" : null,
        ];
    }

    /** Pedidos de delivery listos y sin repartidor. */
    public function pending(Request $request): JsonResponse
    {
        $this->ensureDriver($request);
        $orders = Order::with('customer')->where('order_type', 'delivery')
            ->whereNull('driver_id')->whereIn('status', ['pending', 'in_kitchen', 'ready'])->get();
        return response()->json(['success' => true, 'data' => $orders->map(fn ($o) => $this->row($o))]);
    }

    /** Pedidos asignados al repartidor. */
    public function mine(Request $request): JsonResponse
    {
        $this->ensureDriver($request);
        $orders = Order::with('customer')->where('driver_id', $request->user()->id)
            ->whereNull('delivered_at')->where('status', '!=', 'cancelled')->get();
        return response()->json(['success' => true, 'data' => $orders->map(fn ($o) => $this->row($o))]);
    }

    public function take(Request $request, int $id): JsonResponse
    {
        $this->ensureDriver($request);
        $order = Order::where('order_type', 'delivery')->whereNull('driver_id')->findOrFail($id);
        $order->update(['driver_id' => $request->user()->id]);
        return response()->json(['success' => true, 'message' => 'Pedido asignado.']);
    }

    /** Marca que el repartidor recogió la comida y salió. */
    public function pickup(Request $request, int $id): JsonResponse
    {
        $this->ensureDriver($request);
        $order = Order::where('driver_id', $request->user()->id)->findOrFail($id);
        abort_unless($order->status === 'ready', 422, 'El pedido aún no está listo en cocina.');
        $order->update(['dispatched_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Pedido en camino.']);
    }

    /** Confirmar entrega con PIN de 4 dígitos. */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $this->ensureDriver($request);
        $data = $request->validate(['pin' => 'required|string|size:4']);
        $order = Order::where('driver_id', $request->user()->id)->findOrFail($id);
        if ($order->delivery_pin !== $data['pin']) {
            return response()->json(['success' => false, 'message' => 'PIN incorrecto.'], 422);
        }
        $order->update(['delivered_at' => now(), 'status' => $order->status === 'paid' ? 'paid' : 'served']);
        return response()->json(['success' => true, 'message' => 'Entrega confirmada.']);
    }
}

