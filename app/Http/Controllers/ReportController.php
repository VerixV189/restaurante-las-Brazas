<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Dish;
use App\Models\Category;
use App\Models\InventoryMovement;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // 4. Top Platillos Más Vendidos (NULLS LAST para PostgreSQL)
        $topDishes = Dish::with('category')
            ->withSum(['orderItems as total_units_sold' => function ($query) {
                $query->whereHas('order', fn ($q) => $q->where('status', 'paid'));
            }], 'quantity')
            ->withSum(['orderItems as total_revenue_generated' => function ($query) {
                $query->whereHas('order', fn ($q) => $q->where('status', 'paid'));
            }], 'subtotal')
            ->orderByRaw('total_units_sold DESC NULLS LAST, total_revenue_generated DESC NULLS LAST')
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

    /**
     * Exportar Reporte Completo en formato Excel (.xls compatible con MS Excel / LibreOffice / Google Sheets).
     */
    public function exportExcel(): HttpResponse
    {
        $data = $this->getReportData();
        $summary = $data['summary'];
        $filename = 'reporte_ventas_las_brazas_' . now()->format('Y-m-d_H-i') . '.xls';

        $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h2 { color: #ea580c; margin-top: 20px; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th { background-color: #ea580c; color: #ffffff; font-weight: bold; padding: 8px; border: 1px solid #cbd5e1; text-align: left; }
            td { padding: 6px 8px; border: 1px solid #cbd5e1; }
            .num { text-align: right; }
        </style></head><body>';

        $html .= '<h2>RESTAURANTE LAS BRAZAS - INFORME COMERCIAL Y FINANCIERO</h2>';
        $html .= '<p>Fecha de generación: ' . now()->format('d/m/Y H:i') . '</p>';

        // 1. Resumen KPI
        $html .= '<h2>1. Resumen de Indicadores Clave (KPIs)</h2><table><thead><tr>
            <th>Indicador</th><th>Valor</th>
        </tr></thead><tbody>
            <tr><td>Ventas Totales Históricas</td><td class="num">$' . number_format($summary['total_revenue'], 2) . '</td></tr>
            <tr><td>Ventas de Hoy</td><td class="num">$' . number_format($summary['today_revenue'], 2) . '</td></tr>
            <tr><td>Ventas del Mes Actual</td><td class="num">$' . number_format($summary['month_revenue'], 2) . '</td></tr>
            <tr><td>Comandas Pagadas</td><td class="num">' . $summary['completed_orders_count'] . '</td></tr>
            <tr><td>Ticket Promedio</td><td class="num">$' . number_format($summary['average_ticket'], 2) . '</td></tr>
            <tr><td>Costo por Mermas</td><td class="num">$' . number_format($summary['total_waste_loss'], 2) . '</td></tr>
        </tbody></table>';

        // 2. Ventas Últimos 7 Días
        $html .= '<h2>2. Evolución de Ventas (Últimos 7 Días)</h2><table><thead><tr>
            <th>Fecha</th><th>Ventas ($)</th>
        </tr></thead><tbody>';
        foreach ($data['dailySales'] as $day) {
            $html .= '<tr><td>' . e($day['date']) . '</td><td class="num">$' . number_format($day['amount'], 2) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        // 3. Ventas por Categoría
        $html .= '<h2>3. Ventas por Categoría</h2><table><thead><tr>
            <th>Categoría</th><th>Total Ventas ($)</th>
        </tr></thead><tbody>';
        foreach ($data['categoriesSales'] as $cat) {
            $html .= '<tr><td>' . e($cat['category_name']) . '</td><td class="num">$' . number_format($cat['total_sales'], 2) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        // 4. Top Platillos Más Vendidos
        $html .= '<h2>4. Top Platillos Más Vendidos</h2><table><thead><tr>
            <th>#</th><th>Platillo</th><th>Categoría</th><th>Precio Unitario</th><th>Unidades Vendidas</th><th>Ingresos Generados</th>
        </tr></thead><tbody>';
        foreach ($data['topDishes'] as $idx => $dish) {
            $html .= '<tr>
                <td>' . ($idx + 1) . '</td>
                <td>' . e($dish->name) . '</td>
                <td>' . e($dish->category->name ?? 'General') . '</td>
                <td class="num">$' . number_format((float) $dish->price, 2) . '</td>
                <td class="num">' . (int) ($dish->total_units_sold ?? 0) . '</td>
                <td class="num">$' . number_format((float) ($dish->total_revenue_generated ?? 0), 2) . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        // 5. Matriz de Rentabilidad
        $html .= '<h2>5. Matriz de Costo vs. Precio (Rentabilidad por Receta)</h2><table><thead><tr>
            <th>Platillo</th><th>Categoría</th><th>Precio Venta</th><th>Costo Receta</th><th>Ganancia Bruta</th><th>Margen (%)</th>
        </tr></thead><tbody>';
        foreach ($data['profitability'] as $item) {
            $html .= '<tr>
                <td>' . e($item['name']) . '</td>
                <td>' . e($item['category']) . '</td>
                <td class="num">$' . number_format($item['selling_price'], 2) . '</td>
                <td class="num">$' . number_format($item['recipe_cost'], 2) . '</td>
                <td class="num">$' . number_format($item['gross_profit'], 2) . '</td>
                <td class="num">' . $item['margin_percent'] . '%</td>
            </tr>';
        }
        $html .= '</tbody></table></body></html>';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Exportar Reporte Completo en formato CSV (con BOM UTF-8 para compatibilidad directa en Excel).
     */
    public function exportCsv(): StreamedResponse
    {
        $data = $this->getReportData();
        $filename = 'reporte_ventas_las_brazas_' . now()->format('Y-m-d_H-i') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            // Añadir BOM UTF-8 para que Excel reconozca tildes y eñes automáticamente
            fwrite($out, "\xEF\xBB\xBF");

            $summary = $data['summary'];

            fputcsv($out, ['RESTAURANTE LAS BRAZAS - REPORTE COMERCIAL Y FINANCIERO']);
            fputcsv($out, ['Generado el', now()->format('d/m/Y H:i')]);
            fputcsv($out, []);

            // Sección 1: KPIs
            fputcsv($out, ['=== 1. RESUMEN DE INDICADORES CLAVE (KPIS) ===']);
            fputcsv($out, ['Indicador', 'Valor']);
            fputcsv($out, ['Ventas Totales Históricas ($)', number_format($summary['total_revenue'], 2, '.', '')]);
            fputcsv($out, ['Ventas de Hoy ($)', number_format($summary['today_revenue'], 2, '.', '')]);
            fputcsv($out, ['Ventas Este Mes ($)', number_format($summary['month_revenue'], 2, '.', '')]);
            fputcsv($out, ['Comandas Cobradas', $summary['completed_orders_count']]);
            fputcsv($out, ['Ticket Promedio ($)', number_format($summary['average_ticket'], 2, '.', '')]);
            fputcsv($out, ['Costo por Mermas ($)', number_format($summary['total_waste_loss'], 2, '.', '')]);
            fputcsv($out, []);

            // Sección 2: Ventas Diarias
            fputcsv($out, ['=== 2. EVOLUCIÓN DE VENTAS (ÚLTIMOS 7 DÍAS) ===']);
            fputcsv($out, ['Fecha', 'Monto ($)']);
            foreach ($data['dailySales'] as $day) {
                fputcsv($out, [$day['date'], number_format($day['amount'], 2, '.', '')]);
            }
            fputcsv($out, []);

            // Sección 3: Ventas por Categoría
            fputcsv($out, ['=== 3. VENTAS POR CATEGORÍA ===']);
            fputcsv($out, ['Categoría', 'Total Ventas ($)']);
            foreach ($data['categoriesSales'] as $cat) {
                fputcsv($out, [$cat['category_name'], number_format($cat['total_sales'], 2, '.', '')]);
            }
            fputcsv($out, []);

            // Sección 4: Top Platillos
            fputcsv($out, ['=== 4. TOP PLATILLOS MÁS VENDIDOS ===']);
            fputcsv($out, ['Ranking', 'Platillo', 'Categoría', 'Precio Unitario ($)', 'Unidades Vendidas', 'Ingresos Generados ($)']);
            foreach ($data['topDishes'] as $idx => $dish) {
                fputcsv($out, [
                    $idx + 1,
                    $dish->name,
                    $dish->category->name ?? 'General',
                    number_format((float) $dish->price, 2, '.', ''),
                    (int) ($dish->total_units_sold ?? 0),
                    number_format((float) ($dish->total_revenue_generated ?? 0), 2, '.', ''),
                ]);
            }
            fputcsv($out, []);

            // Sección 5: Rentabilidad
            fputcsv($out, ['=== 5. MATRIZ DE COSTO VS PRECIO (RENTABILIDAD) ===']);
            fputcsv($out, ['Platillo', 'Categoría', 'Precio Venta ($)', 'Costo Insumos ($)', 'Ganancia Bruta ($)', 'Margen (%)']);
            foreach ($data['profitability'] as $item) {
                fputcsv($out, [
                    $item['name'],
                    $item['category'],
                    number_format($item['selling_price'], 2, '.', ''),
                    number_format($item['recipe_cost'], 2, '.', ''),
                    number_format($item['gross_profit'], 2, '.', ''),
                    $item['margin_percent'] . '%',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
