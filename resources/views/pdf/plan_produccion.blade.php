<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan de Producción y Requisición de Insumos - Las Brazas</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        body {
            margin: 0;
            padding: 20px;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #ea580c;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header table {
            width: 100%;
        }
        .brand-title {
            color: #ea580c;
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .brand-subtitle {
            color: #64748b;
            font-size: 10px;
            margin: 3px 0 0 0;
        }
        .report-meta {
            text-align: right;
            color: #475569;
            font-size: 10px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            border-left: 4px solid #ea580c;
            padding-left: 6px;
            margin-top: 15px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-danger { color: #dc2626; }
        .text-success { color: #16a34a; }
        .text-muted { color: #64748b; }
        .font-bold { font-weight: bold; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .kpi-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .signatures {
            margin-top: 30px;
            width: 100%;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            padding-top: 40px;
        }
        .sign-line {
            border-top: 1px solid #64748b;
            width: 70%;
            margin: 0 auto;
            padding-top: 5px;
            font-size: 10px;
            font-weight: bold;
            color: #334155;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <h1 class="brand-title">🔥 Las Brazas Restaurant</h1>
                    <p class="brand-subtitle">Plan de Producción de Cocina & Requisición de Compras</p>
                </td>
                <td class="report-meta">
                    <strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}<br>
                    <strong>Responsable:</strong> {{ auth()->user()->name ?? 'Chef Ejecutivo' }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Platillos a Preparar en el Lote -->
    <div class="section-title">1. Platillos y Porciones Programadas para Producción</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Platillo</th>
                <th>Categoría</th>
                <th class="text-center">Porciones a Producir</th>
                <th class="text-right">Precio Venta Unitario</th>
                <th class="text-right">Proyección Ingreso</th>
            </tr>
        </thead>
        <tbody>
            @foreach($batchDishes as $dish)
                <tr>
                    <td><strong>{{ $dish['name'] }}</strong></td>
                    <td>{{ $dish['category'] }}</td>
                    <td class="text-center"><strong>{{ $dish['portions'] }} porciones</strong></td>
                    <td class="text-right">${{ number_format($dish['price'], 2) }}</td>
                    <td class="text-right" style="color: #16a34a; font-weight: bold;">
                        ${{ number_format($dish['portions'] * $dish['price'], 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Desglose de Insumos Requeridos y Estado de Stock -->
    <div class="section-title">2. Desglose de Insumos Requeridos vs. Stock en Bodega</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Insumo / Materia Prima</th>
                <th class="text-center">Unidad</th>
                <th class="text-right">Cantidad Requerida</th>
                <th class="text-right">Stock Actual</th>
                <th class="text-right">Faltante a Comprar</th>
                <th class="text-right">Costo Unitario</th>
                <th class="text-right">Costo Faltante ($)</th>
                <th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requiredIngredients as $ing)
                <tr>
                    <td><strong>{{ $ing['name'] }}</strong></td>
                    <td class="text-center">{{ $ing['unit'] }}</td>
                    <td class="text-right"><strong>{{ number_format($ing['required_quantity'], 2) }}</strong></td>
                    <td class="text-right">{{ number_format($ing['current_stock'], 2) }}</td>
                    <td class="text-right font-bold {{ $ing['deficit'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($ing['deficit'], 2) }}
                    </td>
                    <td class="text-right">${{ number_format($ing['cost_per_unit'], 2) }}</td>
                    <td class="text-right font-bold {{ $ing['deficit'] > 0 ? 'text-danger' : 'text-muted' }}">
                        ${{ number_format($ing['cost_to_buy'], 2) }}
                    </td>
                    <td class="text-center">
                        @if($ing['deficit'] > 0)
                            <span class="badge badge-danger">FALTANTE</span>
                        @else
                            <span class="badge badge-success">SUFICIENTE</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Resumen Financiero del Lote -->
    <table style="width: 100%; margin-top: 10px;">
        <tr>
            <td style="width: 32%;" class="kpi-box">
                <div style="font-size: 9px; color: #64748b; font-weight: bold;">TOTAL INGREDIENTES REQUERIDOS</div>
                <div style="font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 3px;">
                    {{ count($requiredIngredients) }} insumos
                </div>
            </td>
            <td style="width: 32%;" class="kpi-box">
                <div style="font-size: 9px; color: #64748b; font-weight: bold;">COSTO TOTAL PRODUCCIÓN LOTE</div>
                <div style="font-size: 14px; font-weight: bold; color: #ea580c; margin-top: 3px;">
                    ${{ number_format($totalProductionCost, 2) }}
                </div>
            </td>
            <td style="width: 32%;" class="kpi-box">
                <div style="font-size: 9px; color: #64748b; font-weight: bold;">INVERSIÓN NECESARIA EN COMPRAS</div>
                <div style="font-size: 14px; font-weight: bold; color: #dc2626; margin-top: 3px;">
                    ${{ number_format($totalCostToBuy, 2) }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Firmas -->
    <table class="signatures">
        <tr>
            <td>
                <div class="sign-line">Firma: Chef / Jefe de Cocina</div>
            </td>
            <td>
                <div class="sign-line">Firma: Administrador / Compras</div>
            </td>
        </tr>
    </table>

</body>
</html>
