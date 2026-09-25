<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiReportController extends Controller
{
    /**
     * Procesa consultas en lenguaje natural mediante el microservicio FastAPI (Gemini)
     * o fallback inteligente, y genera la data de gráficos para el frontend.
     */
    public function query(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|min:2|max:500',
        ]);

        $prompt = trim($validated['prompt']);
        $aiServiceUrl = config('services.fastapi_ai.url', 'http://127.0.0.1:8001');

        $parsedData = null;
        $engineUsed = 'gemini_ai';

        // 1. Intentar llamar al microservicio FastAPI (Gemini 2.5 Flash)
        try {
            $response = Http::timeout(8)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$aiServiceUrl}/api/AI/generar-json/", [
                    'texto' => $prompt,
                ]);

            if ($response->successful()) {
                $parsedData = $response->json();
            }
        } catch (\Throwable $e) {
            Log::info("FastAPI AI microservice unreachable, using smart PHP fallback parser: " . $e->getMessage());
        }

        // 2. Si el microservicio no respondió o vino vacío, usar fallback inteligente en PHP
        if (!$parsedData || !isset($parsedData['platillos']) || empty($parsedData['platillos'])) {
            $parsedData = $this->smartFallbackParser($prompt);
            $engineUsed = 'offline_rules';
        }

        // 3. Ejecutar las consultas SQL dinámicas basadas en los parámetros extraídos
        $results = [];
        foreach ($parsedData['platillos'] as $pConfig) {
            $results[] = $this->executeAiSqlQuery($pConfig);
        }

        // Tomar el primer resultado o consolidar
        $primaryResult = $results[0] ?? null;

        if (!$primaryResult) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron datos para la consulta proporcionada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'engine' => $engineUsed,
            'prompt_original' => $prompt,
            'parsed_config' => $parsedData,
            'result' => $primaryResult,
        ]);
    }

    /**
     * Ejecuta la consulta SQL con generate_series y date_trunc en PostgreSQL.
     */
    private function executeAiSqlQuery(array $config): array
    {
        $dishName = trim($config['nombre'] ?? 'ventas');
        $charts = $config['graficas'] ?? ['barra'];
        $startDate = $config['fecha_inicio'] ?? now()->subDays(6)->startOfDay()->toDateTimeString();
        $endDate = $config['fecha_fin'] ?? now()->endOfDay()->toDateTimeString();
        $granularity = $config['granularidad'] ?? 'day';
        $interval = $config['intervalo_sql'] ?? '1 day';

        // Normalizar nombres y filtros
        $isAllDishes = in_array(strtolower($dishName), ['ventas', 'venta', 'todos', 'todo', 'general', 'platos']);
        
        $joinDish = '';
        $whereDish = '';
        $params = [
            'fecha_inicio' => $startDate,
            'fecha_fin' => $endDate,
            'intervalo' => $interval,
        ];

        if (!$isAllDishes) {
            $joinDish = "JOIN dishes d ON d.id = oi.dish_id";
            $whereDish = "AND d.name ILIKE :dish_name";
            $params['dish_name'] = '%' . $dishName . '%';
        }

        try {
            if ($granularity === 'custom') {
                $sql = "
                    WITH series AS (
                        SELECT generate_series(
                            :fecha_inicio::timestamp,
                            :fecha_fin::timestamp,
                            :intervalo::interval
                        ) AS periodo
                    ),
                    datos AS (
                        SELECT
                            (
                                SELECT s.periodo 
                                FROM series s
                                WHERE s.periodo <= o.created_at
                                ORDER BY s.periodo DESC
                                LIMIT 1
                            ) AS periodo_agrupado,
                            SUM(oi.subtotal) AS total_ventas,
                            SUM(oi.quantity) AS platos_vendidos
                        FROM order_items oi
                        JOIN orders o ON o.id = oi.order_id
                        $joinDish
                        WHERE o.status = 'paid'
                          AND o.created_at BETWEEN :fecha_inicio AND :fecha_fin
                          $whereDish
                        GROUP BY periodo_agrupado
                    )
                    SELECT 
                        TO_CHAR(series.periodo, 'YYYY-MM-DD HH24:MI') AS etiqueta,
                        COALESCE(datos.total_ventas, 0) AS total_ventas,
                        COALESCE(datos.platos_vendidos, 0) AS platos_vendidos
                    FROM series
                    LEFT JOIN datos ON series.periodo = datos.periodo_agrupado
                    ORDER BY series.periodo;
                ";
            } else {
                $params['granularidad'] = $granularity;
                $sql = "
                    WITH series AS (
                        SELECT generate_series(
                            :fecha_inicio::timestamp,
                            :fecha_fin::timestamp,
                            :intervalo::interval
                        ) AS periodo
                    ),
                    datos AS (
                        SELECT 
                            date_trunc(:granularidad, o.created_at) AS periodo,
                            SUM(oi.subtotal) AS total_ventas,
                            SUM(oi.quantity) AS platos_vendidos
                        FROM order_items oi
                        JOIN orders o ON o.id = oi.order_id
                        $joinDish
                        WHERE o.status = 'paid'
                          AND o.created_at BETWEEN :fecha_inicio AND :fecha_fin
                          $whereDish
                        GROUP BY periodo
                    )
                    SELECT 
                        TO_CHAR(series.periodo, 'YYYY-MM-DD HH24:MI') AS etiqueta,
                        COALESCE(datos.total_ventas, 0) AS total_ventas,
                        COALESCE(datos.platos_vendidos, 0) AS platos_vendidos
                    FROM series
                    LEFT JOIN datos ON series.periodo = datos.periodo
                    ORDER BY series.periodo;
                ";
            }

            $rows = DB::select($sql, $params);
        } catch (\Throwable $e) {
            Log::warning("PostgreSQL advanced series query failed, using standard Eloquent fallback: " . $e->getMessage());
            $rows = $this->eloquentFallbackQuery($startDate, $endDate, $isAllDishes ? null : $dishName);
        }

        $labels = [];
        $salesData = [];
        $qtyData = [];
        $totalSales = 0;
        $totalUnits = 0;

        foreach ($rows as $row) {
            $label = Carbon::parse($row->etiqueta ?? now())->format($granularity === 'hour' ? 'H:i' : ($granularity === 'month' ? 'M Y' : 'd M'));
            $labels[] = $label;
            $sales = (float) ($row->total_ventas ?? 0);
            $units = (int) ($row->platos_vendidos ?? 0);
            $salesData[] = $sales;
            $qtyData[] = $units;
            $totalSales += $sales;
            $totalUnits += $units;
        }

        return [
            'dish_name' => $isAllDishes ? 'Ventas Generales' : ucfirst($dishName),
            'recommended_charts' => $charts,
            'time_range' => [
                'start' => Carbon::parse($startDate)->format('d/m/Y H:i'),
                'end' => Carbon::parse($endDate)->format('d/m/Y H:i'),
                'granularity' => $granularity,
            ],
            'chart_data' => [
                'labels' => $labels,
                'sales' => $salesData,
                'quantities' => $qtyData,
            ],
            'statistics' => [
                'total_sales' => $totalSales,
                'total_units' => $totalUnits,
                'average_per_period' => count($salesData) > 0 ? round($totalSales / count($salesData), 2) : 0,
            ],
        ];
    }

    /**
     * Parser local inteligente si el microservicio FastAPI no está encendido.
     */
    private function smartFallbackParser(string $prompt): array
    {
        $lower = strtolower($prompt);

        // Platillo detectado
        $dishName = 'ventas';
        $dishes = Dish::pluck('name')->all();
        foreach ($dishes as $name) {
            $words = explode(' ', strtolower($name));
            foreach ($words as $word) {
                if (strlen($word) > 3 && str_contains($lower, $word)) {
                    $dishName = $name;
                    break 2;
                }
            }
        }

        // Gráficos detectados
        $graficas = ['barra'];
        if (str_contains($lower, 'linea') || str_contains($lower, 'línea') || str_contains($lower, 'tendencia')) {
            $graficas[] = 'linea';
        }
        if (str_contains($lower, 'pastel') || str_contains($lower, 'torta') || str_contains($lower, 'pie') || str_contains($lower, 'circular') || str_contains($lower, 'dona')) {
            $graficas[] = 'pastel';
        }

        // Rango de fechas
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();
        $granularity = 'day';
        $intervalSql = '1 day';
        $tipo = 'diario';

        if (str_contains($lower, 'hoy')) {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();
            $granularity = 'hour';
            $intervalSql = '1 hour';
            $tipo = 'por_hora';
        } elseif (str_contains($lower, 'ayer')) {
            $startDate = now()->subDay()->startOfDay();
            $endDate = now()->subDay()->endOfDay();
            $granularity = 'hour';
            $intervalSql = '1 hour';
            $tipo = 'por_hora';
        } elseif (str_contains($lower, 'mes') || str_contains($lower, '30 dias') || str_contains($lower, '30 días')) {
            $startDate = now()->subDays(29)->startOfDay();
            $endDate = now()->endOfDay();
            $granularity = 'day';
            $intervalSql = '1 day';
            $tipo = 'diario';
        } elseif (str_contains($lower, 'año') || str_contains($lower, 'anual')) {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();
            $granularity = 'month';
            $intervalSql = '1 month';
            $tipo = 'mensual';
        }

        return [
            'platillos' => [
                [
                    'nombre' => $dishName,
                    'graficas' => array_unique($graficas),
                    'fecha_inicio' => $startDate->toDateTimeString(),
                    'fecha_fin' => $endDate->toDateTimeString(),
                    'tipo' => $tipo,
                    'granularidad' => $granularity,
                    'intervalo_sql' => $intervalSql,
                ],
            ],
        ];
    }

    /**
     * Fallback estándar con Eloquent
     */
    private function eloquentFallbackQuery(string $startDate, string $endDate, ?string $dishName = null): array
    {
        $query = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate) {
            $q->where('status', 'paid')
              ->whereBetween('created_at', [$startDate, $endDate]);
        });

        if ($dishName) {
            $query->whereHas('dish', function ($q) use ($dishName) {
                $q->where('name', 'ILIKE', "%{$dishName}%");
            });
        }

        $items = $query->with('order')->get();

        $byDate = $items->groupBy(function ($item) {
            return $item->order->created_at->format('Y-m-d');
        });

        $rows = [];
        foreach ($byDate as $date => $group) {
            $rows[] = (object) [
                'etiqueta' => $date,
                'total_ventas' => $group->sum('subtotal'),
                'platos_vendidos' => $group->sum('quantity'),
            ];
        }

        return $rows;
    }
}
