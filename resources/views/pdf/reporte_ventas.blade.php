<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero y de Ventas - Las Brazas</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        body {
            margin: 0;
            padding: 20px;
            color: #1e293b;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #ea580c;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .brand-title {
            color: #ea580c;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .brand-subtitle {
            color: #64748b;
            font-size: 11px;
            margin: 4px 0 0 0;
        }
        .report-meta {
            text-align: right;
            color: #475569;
            font-size: 11px;
        }
        .kpi-grid {
            width: 100%;
            margin-bottom: 25px;
            border-spacing: 10px;
            margin-left: -10px;
            margin-right: -10px;
        }
        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .kpi-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .kpi-value {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }
        .kpi-value.highlight {
            color: #ea580c;
        }
        .kpi-value.green {
            color: #16a34a;
        }
        .kpi-value.red {
            color: #dc2626;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            border-left: 4px solid #ea580c;
            padding-left: 8px;
            margin-top: 20px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            padding: 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 7px 8px;
            border: 1px solid #e2e8f0;
            font-size: 11px;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #b45309;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
            color: #94a3b8;
            font-size: 10px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1 class="brand-title">🔥 Las Brazas Restaurant</h1>
                    <p class="brand-subtitle">Cortes Finos & Parrilla Tradicional · Sistema de Gestión</p>
                </td>
                <td class="report-meta">
                    <strong>Informe General de Ventas y Finanzas</strong><br>
                    Fecha de Emisión: {{ now()->format('d/m/Y H:i') }}<br>
                    Generado por: {{ auth()->user()->name ?? 'Administrador' }}
                </td>
            </tr>
        </table>
    </div>

    <!-- KPIs Resumen -->
    <table class="kpi-grid">
        <tr>
            <td class="kpi-card" style="width: 33%;">
                <div class="kpi-title">Ventas Totales Históricas</div>
                <div class="kpi-value green">${{ number_format($summary['total_revenue'], 2) }}</div>
            </td>
            <td class="kpi-card" style="width: 33%;">
                <div class="kpi-title">Ventas de Hoy</div>
                <div class="kpi-value highlight">${{ number_format($summary['today_revenue'], 2) }}</div>
            </td>
            <td class="kpi-card" style="width: 33%;">
                <div class="kpi-title">Ventas Este Mes</div>
                <div class="kpi-value">${{ number_format($summary['month_revenue'], 2) }}</div>
            </td>
        </tr>
        <tr>
            <td class="kpi-card">
                <div class="kpi-title">Comandas Cobradas</div>
                <div class="kpi-value">{{ $summary['completed_orders_count'] }}</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-title">Ticket Promedio</div>
                <div class="kpi-value">${{ number_format($summary['average_ticket'], 2) }}</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-title">Costo por Mermas</div>
                <div class="kpi-value red">${{ number_format($summary['total_waste_loss'], 2) }}</div>
            </td>
        </tr>
    </table>

    <!-- Ventas Últimos 7 Días -->
    <div class="section-title">Evolución de Ventas Recientes (Últimos 7 Días)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha / Día</th>
                <th class="text-right">Monto Total Recaudado ($)</th>
                <th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dailySales as $day)
                <tr>
                    <td><strong>{{ $day['date'] }}</strong></td>
                    <td class="text-right font-mono">${{ number_format($day['amount'], 2) }}</td>
                    <td class="text-center">
                        @if($day['amount'] > 0)
                            <span class="badge badge-success">Con Movimiento</span>
                        @else
                            <span class="badge badge-warning">Sin Ventas</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Top 10 Platillos Más Vendidos -->
    <div class="section-title">Top Platillos Más Vendidos</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">#</th>
                <th>Platillo</th>
                <th>Categoría</th>
                <th class="text-right">Precio Unitario</th>
                <th class="text-center">Porciones Vendidas</th>
                <th class="text-right">Ingresos Generados</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topDishes as $index => $dish)
                <tr>
                    <td class="text-center"><strong>{{ $index + 1 }}</strong></td>
                    <td><strong>{{ $dish->name }}</strong></td>
                    <td>{{ $dish->category->name ?? 'General' }}</td>
                    <td class="text-right">${{ number_format($dish->price, 2) }}</td>
                    <td class="text-center">{{ $dish->total_units_sold ?? 0 }}</td>
                    <td class="text-right" style="font-weight: bold; color: #16a34a;">
                        ${{ number_format($dish->total_revenue_generated ?? 0, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No hay registros de ventas de platillos.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Matriz de Rentabilidad y Costos de Receta -->
    <div class="section-title">Matriz de Costo vs. Precio de Venta (Rentabilidad por Receta)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Platillo</th>
                <th>Categoría</th>
                <th class="text-right">Precio Venta</th>
                <th class="text-right">Costo Insumos (Receta)</th>
                <th class="text-right">Ganancia Bruta</th>
                <th class="text-center">Margen %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($profitability as $item)
                <tr>
                    <td>
                        <strong>{{ $item['name'] }}</strong>
                        @if(!$item['has_recipe'])
                            <span style="color: #ea580c; font-size: 9px;">(Sin receta)</span>
                        @endif
                    </td>
                    <td>{{ $item['category'] }}</td>
                    <td class="text-right">${{ number_format($item['selling_price'], 2) }}</td>
                    <td class="text-right" style="color: #b45309;">${{ number_format($item['recipe_cost'], 2) }}</td>
                    <td class="text-right" style="font-weight: bold; color: #16a34a;">${{ number_format($item['gross_profit'], 2) }}</td>
                    <td class="text-center">
                        @if($item['margin_percent'] >= 65)
                            <span class="badge badge-success">{{ $item['margin_percent'] }}% (Alto)</span>
                        @elseif($item['margin_percent'] >= 45)
                            <span class="badge badge-warning">{{ $item['margin_percent'] }}% (Medio)</span>
                        @else
                            <span class="badge badge-danger">{{ $item['margin_percent'] }}% (Bajo)</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        Restaurante Las Brazas · Documento oficial emitido automáticamente por el Sistema de Gestión · Página 1 de 2
    </div>

</body>
</html>
