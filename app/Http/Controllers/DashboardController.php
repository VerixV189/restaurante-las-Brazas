<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Customer;
use App\Models\Table;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // 1. Métricas de Catálogo, Mesas, Cocina, Reservas e Inventario en Tiempo Real
        $totalDishes = Dish::count();
        $availableDishes = Dish::where('is_available', true)->count();
        $totalIngredients = Ingredient::count();
        $lowStockIngredients = Ingredient::whereColumn('current_stock', '<=', 'minimum_stock')->count();

        // Valor total del inventario en almacén ($)
        $inventoryValue = (float) Ingredient::get()->reduce(function ($acc, $ing) {
            return $acc + ((float) $ing->current_stock * (float) $ing->cost_per_unit);
        }, 0);

        // Costo acumulado de mermas ($)
        $wasteLoss = (float) InventoryMovement::where('movement_type', 'waste')
            ->with('ingredient')
            ->get()
            ->reduce(function ($acc, $mov) {
                return $acc + ((float) $mov->quantity * (float) ($mov->ingredient?->cost_per_unit ?? 0));
            }, 0);

        // Ocupación de mesas en salón
        $totalTables = Table::count();
        $occupiedTables = Table::where('status', 'occupied')->count();
        $reservedTables = Table::where('status', 'reserved')->count();
        $availableTables = Table::where('status', 'available')->count();
        $occupancyRate = $totalTables > 0 ? round(($occupiedTables / $totalTables) * 100, 1) : 0;

        // Estado de cocina (KDS) y Delivery en vivo
        $activeKitchenOrders = Order::whereIn('status', ['pending', 'in_kitchen', 'ready'])->count();
        $activeCookingDishes = OrderItem::whereIn('status', ['pending', 'cooking'])->count();

        $totalDeliveryOrders = Order::whereIn('order_type', ['delivery', 'takeaway'])->count();
        $activeDeliveryOrders = Order::whereIn('order_type', ['delivery', 'takeaway'])
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->whereNull('delivered_at')
            ->count();

        // 2. KPIs Financieros Dinámicos por Periodo (Hoy, 7 Días, Mes, Histórico)
        $periods = [
            'today' => $this->buildPeriodMetrics(now()->startOfDay(), now()->endOfDay()),
            'week'  => $this->buildPeriodMetrics(now()->subDays(6)->startOfDay(), now()->endOfDay()),
            'month' => $this->buildPeriodMetrics(now()->startOfMonth(), now()->endOfDay()),
            'all'   => $this->buildPeriodMetrics(null, null),
        ];

        $stats = [
            'total_dishes' => $totalDishes,
            'available_dishes' => $availableDishes,
            'total_ingredients' => $totalIngredients,
            'low_stock_ingredients' => $lowStockIngredients,
            'inventory_value' => round($inventoryValue, 2),
            'waste_loss' => round($wasteLoss, 2),
            'total_tables' => $totalTables,
            'occupied_tables' => $occupiedTables,
            'reserved_tables' => $reservedTables,
            'available_tables' => $availableTables,
            'occupancy_rate' => $occupancyRate,
            'active_kitchen_orders' => $activeKitchenOrders,
            'active_cooking_dishes' => $activeCookingDishes,
            'total_delivery_orders' => $totalDeliveryOrders,
            'active_delivery_orders' => $activeDeliveryOrders,
            'pending_reservations' => Reservation::where('status', 'pending')->count(),
            'today_reservations' => Reservation::whereDate('reservation_date', today())->count(),
            'total_customers' => Customer::count(),
            'total_users' => User::count(),
        ];

        // 3. Serie de Ventas de los últimos 7 días
        $dailySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $amount = (float) Payment::whereDate('paid_at', $date->toDateString())->sum('amount');
            $ordersCount = Order::whereDate('created_at', $date->toDateString())
                ->where('status', '!=', 'cancelled')
                ->count();
            $dailySales[] = [
                'date' => $date->translatedFormat('D d M'),
                'amount' => round($amount, 2),
                'orders' => $ordersCount,
            ];
        }

        // 4. Distribución de Pedidos por Canal (En Salón, Delivery App, Para Llevar)
        $channelStats = [
            'dine_in' => [
                'label' => 'En Salón (Mesas)',
                'orders' => Order::where('order_type', 'dine_in')->where('status', '!=', 'cancelled')->count(),
                'revenue' => round((float) Order::where('order_type', 'dine_in')->where('status', 'paid')->sum('total_amount'), 2),
            ],
            'delivery' => [
                'label' => 'Delivery (App Móvil)',
                'orders' => Order::where('order_type', 'delivery')->where('status', '!=', 'cancelled')->count(),
                'revenue' => round((float) Order::where('order_type', 'delivery')->where('status', '!=', 'cancelled')->sum('total_amount'), 2),
            ],
            'takeaway' => [
                'label' => 'Para Llevar (Takeaway)',
                'orders' => Order::where('order_type', 'takeaway')->where('status', '!=', 'cancelled')->count(),
                'revenue' => round((float) Order::where('order_type', 'takeaway')->where('status', '!=', 'cancelled')->sum('total_amount'), 2),
            ],
        ];

        // 5. Distribución por Método de Pago
        $paymentMethods = [
            'cash' => round((float) Payment::where('payment_method', 'cash')->sum('amount'), 2),
            'card' => round((float) Payment::where('payment_method', 'card')->sum('amount'), 2),
            'transfer' => round((float) Payment::where('payment_method', 'transfer')->sum('amount'), 2),
        ];

        // 6. Top Platillos Estrella (NULLS LAST para PostgreSQL)
        $topDishes = Dish::with('category')
            ->withSum(['orderItems as units_sold' => function ($q) {
                $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'));
            }], 'quantity')
            ->withSum(['orderItems as revenue' => function ($q) {
                $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled'));
            }], 'subtotal')
            ->orderByRaw('units_sold DESC NULLS LAST, revenue DESC NULLS LAST')
            ->take(5)
            ->get();

        // 7. Pedidos Recientes en Vivo (Salón + App Móvil Delivery)
        $liveOrders = Order::with(['table', 'customer', 'items.dish'])
            ->latest()
            ->take(6)
            ->get();

        $lowStockList = Ingredient::whereColumn('current_stock', '<=', 'minimum_stock')
            ->with('supplier')
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(8)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'periods' => $periods,
            'dailySales' => $dailySales,
            'channelStats' => $channelStats,
            'paymentMethods' => $paymentMethods,
            'topDishes' => $topDishes,
            'liveOrders' => $liveOrders,
            'lowStockList' => $lowStockList,
            'recentActivities' => $recentActivities,
        ]);
    }

    private function buildPeriodMetrics($start, $end): array
    {
        $payQuery = Payment::query();
        $ordQuery = Order::where('status', '!=', 'cancelled');

        if ($start && $end) {
            $payQuery->whereBetween('paid_at', [$start, $end]);
            $ordQuery->whereBetween('created_at', [$start, $end]);
        }

        $revenue = (float) $payQuery->sum('amount');
        $ordersCount = (int) $ordQuery->count();
        $paidOrdersCount = (int) (clone $ordQuery)->where('status', 'paid')->count();
        $avgTicket = $paidOrdersCount > 0 ? ($revenue / $paidOrdersCount) : ($ordersCount > 0 ? ((float) $ordQuery->avg('total_amount')) : 0);
        $deliveryCount = (int) (clone $ordQuery)->whereIn('order_type', ['delivery', 'takeaway'])->count();

        return [
            'revenue' => round($revenue, 2),
            'orders_count' => $ordersCount,
            'paid_orders_count' => $paidOrdersCount,
            'avg_ticket' => round($avgTicket, 2),
            'delivery_count' => $deliveryCount,
        ];
    }
}
