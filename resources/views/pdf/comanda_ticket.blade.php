<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comanda #{{ $order->id }} - Las Brazas</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace;
        }
        body {
            width: 280px;
            margin: 0 auto;
            padding: 10px 5px;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .divider-double {
            border-top: 2px solid #000;
            margin: 8px 0;
        }
        .header {
            text-align: center;
            margin-bottom: 8px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            border-bottom: 1px solid #000;
            padding: 3px 0;
            font-size: 10px;
        }
        .items-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .item-notes {
            font-size: 9px;
            font-style: italic;
            padding-left: 10px;
        }
        .totals-table td {
            padding: 2px 0;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">🔥 LAS BRAZAS 🔥</div>
        <div class="subtitle">Cortes Finos & Asados Tradicionales</div>
        <div class="subtitle">NIT: 1029384756 · Sucursal Central</div>
        <div class="subtitle">Tel: (555) 123-4567</div>
    </div>

    <div class="divider"></div>

    <!-- Metadatos de la Comanda -->
    <table>
        <tr>
            <td><strong>Comanda:</strong> #{{ $order->id }}</td>
            <td class="text-right"><strong>Mesa:</strong> {{ $order->table->table_number ?? 'Para Llevar' }}</td>
        </tr>
        <tr>
            <td><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y') }}</td>
            <td class="text-right"><strong>Hora:</strong> {{ $order->created_at->format('H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Atendido por:</strong></td>
            <td class="text-right">{{ $order->user->name ?? 'Mesero' }}</td>
        </tr>
        @if($order->customer)
        <tr>
            <td><strong>Cliente:</strong></td>
            <td class="text-right">{{ $order->customer->name }}</td>
        </tr>
        @endif
        <tr>
            <td><strong>Tipo:</strong></td>
            <td class="text-right">{{ strtoupper($order->order_type) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Detalle de Productos -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 25px;">CANT</th>
                <th class="text-left">DESCRIPCIÓN</th>
                <th class="text-right" style="width: 45px;">P.UNIT</th>
                <th class="text-right" style="width: 50px;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td class="bold">{{ $item->quantity }}x</td>
                    <td>
                        <div class="bold">{{ $item->dish->name ?? 'Platillo' }}</div>
                        @if($item->notes)
                            <div class="item-notes">* {{ $item->notes }}</div>
                        @endif
                    </td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right bold">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider-double"></div>

    <!-- Totales -->
    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @if($order->tax > 0)
        <tr>
            <td>Impuestos (IVA):</td>
            <td class="text-right">${{ number_format($order->tax, 2) }}</td>
        </tr>
        @endif
        <tr style="font-size: 13px;">
            <td class="bold">TOTAL A PAGAR:</td>
            <td class="text-right bold">${{ number_format($order->total_amount, 2) }}</td>
        </tr>
    </table>

    @if($order->payments && $order->payments->count() > 0)
        <div class="divider"></div>
        <table>
            @foreach($order->payments as $payment)
                <tr>
                    <td><strong>Método:</strong> {{ strtoupper($payment->payment_method) }}</td>
                    <td class="text-right"><strong>Pagado:</strong> ${{ number_format($payment->amount, 2) }}</td>
                </tr>
                @if($payment->transaction_ref)
                <tr>
                    <td colspan="2" style="font-size: 9px; color: #555;">Ref: {{ $payment->transaction_ref }}</td>
                </tr>
                @endif
            @endforeach
        </table>
    @endif

    <div class="divider"></div>

    <div class="footer">
        <p class="bold">¡GRACIAS POR SU PREFERENCIA!</p>
        <p>Conserve este ticket para cualquier aclaración o facturación.</p>
        <p>www.lasbrazas.com</p>
    </div>

</body>
</html>
