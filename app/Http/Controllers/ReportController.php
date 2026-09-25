<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Dish;
use App\Models\Category;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    private function getReportData(): array
    {
        // 1. KPIs Financieros y Operativos Generales
        $totalRevenue = (float) Payment::sum('amount');
        $todayRevenue = (float) Payment::whereDate('paid_at', today())->sum('amount');
        $monthRevenue = (float) Payment::whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $totalOrdersCount = Order::count();
        $completedOrdersCount = Order::where('status', 'paid')->count();
        $averageTicket = $completedOrdersCount > 0 ? ($totalRevenue / $completedOrdersCount) : 0;

        // Pérdidas por merma / desperdicio
        $wasteMovements = InventoryMovement::where('movement_type', 'waste')
            ->with('ingredient')
            ->get();

        $totalWasteLoss = $wasteMovements->reduce(function ($acc, $item) {
            $cost = (float) ($item->ingredient?->cost_per_unit ?? 0);
            return $acc + ($cost * (float) $item->quantity);
        }, 0);

        // 2. Gráfico de Ventas de los últimos 7 días
        $dailySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayName = $date->translatedFormat('D d M');
            $amount = (float) Payment::whereDate('paid_at', $date->toDateString())->sum('amount');
            $dailySales[] = [
                'date' => $dayName,
                'amount' => $amount,
            ];
        }

        // 3. Ventas por Categoría (Doughnut Chart)
        $categoriesSales = Category::with(['dishes.orderItems' => function ($query) {
            $query->whereHas('order', fn ($q) => $q->where('status', 'paid'));
        }])->get()->map(function ($category) {
            $total = $category->dishes->reduce(function ($acc, $dish) {
                return $acc + (float) $dish->orderItems->sum('subtotal');
            }, 0);

            return [
                'category_name' => $category->name,
                'total_sales' => $total,
            ];
        })->filter(fn ($c) => $c['total_sales'] > 0)->values();

        // 4. Top Platillos Más Vendidos
        $topDishes = Dish::with('category')
            ->withCount(['orderItems as total_units_sold' => function ($query) {
                $query->whereHas('order', fn ($q) => $q->where('status', 'paid'));
            }])
            ->withSum(['orderItems as total_revenue_generated' => function ($query) {
                $query->whereHas('order', fn ($q) => $q->where('status', 'paid'));
            }], 'subtotal')
            ->orderByDesc('total_units_sold')
            ->take(10)
            ->get();

        // 5. Matriz de Rentabilidad (Precio vs Costo de Receta por Platillo)
        $dishesProfitability = Dish::with(['category', 'recipe.ingredients.ingredient'])
            ->get()
            ->map(function ($dish) {
                $sellingPrice = (float) $dish->price;
                $recipeCost = 0;

                if ($dish->recipe && $dish->recipe->ingredients) {
                    foreach ($dish->recipe->ingredients as $item) {
                        $unitCost = (float) ($item->ingredient?->cost_per_unit ?? 0);
                        $qty = (float) $item->quantity_required;
                        $recipeCost += ($unitCost * $qty);
                    }
                }

                $grossProfit = $sellingPrice - $recipeCost;
                $marginPercent = $sellingPrice > 0 ? (($grossProfit / $sellingPrice) * 100) : 0;

                return [
                    'id' => $dish->id,
                    'name' => $dish->name,
                    'category' => $dish->category?->name ?? 'General',
                    'selling_price' => $sellingPrice,
                    'recipe_cost' => $recipeCost,
                    'gross_profit' => $grossProfit,
                    'margin_percent' => round($marginPercent, 1),
                    'has_recipe' => (bool) $dish->recipe,
                ];
            });

        return [
            'summary' => [
                'total_revenue' => $totalRevenue,
                'today_revenue' => $todayRevenue,
                'month_revenue' => $monthRevenue,
                'completed_orders_count' => $completedOrdersCount,
                'average_ticket' => round($averageTicket, 2),
                'total_waste_loss' => round($totalWasteLoss, 2),
            ],
            'dailySales' => $dailySales,
            'categoriesSales' => $categoriesSales,
            'topDishes' => $topDishes,
            'profitability' => $dishesProfitability,
        ];
    }

    public function index(): Response
    {
        $data = $this->getReportData();

        return Inertia::render('Reports/Index', $data);
    }

    public function exportPdf(): HttpResponse
    {
        $data = $this->getReportData();

        $pdf = Pdf::loadView('pdf.reporte_ventas', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Helvetica',
            ]);

        return $pdf->download('reporte_ventas_las_brazas_' . now()->format('Y-m-d_H-i') . '.pdf');
    }
}
