<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Solicitud de Cotización SaaS - BrazasTech S.R.L.</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f172a;
            color: #cbd5e1;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 640px;
            margin: 30px auto;
            background-color: #1e293b;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #334155;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.45);
        }
        .header {
            background: linear-gradient(135deg, #1d4ed8, #4f46e5, #ea580c);
            padding: 28px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 24px;
            font-weight: 800;
        }
        .header p {
            margin: 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.92;
            font-weight: 600;
        }
        .content {
            padding: 30px 26px;
        }
        .plan-banner {
            background-color: rgba(234, 88, 12, 0.15);
            border: 1px solid rgba(234, 88, 12, 0.35);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }
        .plan-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #fb923c;
            font-weight: 700;
            display: block;
        }
        .plan-name {
            font-size: 17px;
            font-weight: 800;
            color: #ffffff;
            margin-top: 2px;
        }
        .plan-meta {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .card {
            background-color: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 18px;
        }
        .field-row {
            margin-bottom: 11px;
        }
        .field-row:last-child {
            margin-bottom: 0;
        }
        .field-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }
        .field-value {
            color: #f8fafc;
            font-weight: 600;
            font-size: 14px;
        }
        .field-value a {
            color: #fb923c;
            text-decoration: none;
        }
        .message-box {
            background-color: #0b1120;
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 14px 18px;
            color: #f1f5f9;
            font-size: 14px;
            white-space: pre-line;
            margin-top: 8px;
        }
        .actions {
            text-align: center;
            margin-top: 24px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #ea580c, #f59e0b);
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            padding: 11px 22px;
            border-radius: 10px;
        }
        .footer {
            background-color: #0b1120;
            padding: 20px 24px;
            text-align: center;
            border-top: 1px solid #1e293b;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BrazasTech Cloud SaaS</h1>
            <p>Nueva Solicitud de Cotización & Demo Comercial</p>
        </div>

        <div class="content">
            <div class="plan-banner">
                <span class="plan-label">Plan de Licencia Solicitado</span>
                <div class="plan-name">{{ $quoteData['selected_plan'] }}</div>
                <div class="plan-meta">
                    Infraestructura: {{ $quoteData['cloud_provider'] }} • Sucursales estimadas: {{ $quoteData['branches_count'] }}
                </div>
            </div>

            <div class="card">
                <div class="field-row">
                    <span class="field-label">Restaurante / Empresa</span>
                    <span class="field-value">{{ $quoteData['restaurant_name'] }}</span>
                </div>
                <div class="field-row">
                    <span class="field-label">Persona de Contacto</span>
                    <span class="field-value">{{ $quoteData['contact_name'] }}</span>
                </div>
                <div class="field-row">
                    <span class="field-label">Teléfono / WhatsApp</span>
                    <span class="field-value">{{ $quoteData['contact_phone'] }}</span>
                </div>
                <div class="field-row">
                    <span class="field-label">Correo Corporativo</span>
                    <span class="field-value">
                        @if(!empty($quoteData['contact_email']))
                            <a href="mailto:{{ $quoteData['contact_email'] }}">{{ $quoteData['contact_email'] }}</a>
                        @else
                            No proporcionado
                        @endif
                    </span>
                </div>
                <div class="field-row">
                    <span class="field-label">Fecha y Hora de Solicitud</span>
                    <span class="field-value">{{ $quoteData['sent_at'] }}</span>
                </div>
            </div>

            <div>
                <span class="field-label">Requerimientos de Despliegue / Comentarios</span>
                <div class="message-box">{{ !empty($quoteData['message']) ? $quoteData['message'] : 'Sin comentarios adicionales.' }}</div>
            </div>

            @if(!empty($quoteData['contact_email']))
                <div class="actions">
                    <a class="btn" href="mailto:{{ $quoteData['contact_email'] }}?subject={{ rawurlencode('Cotización BrazasTech SaaS - ' . $quoteData['restaurant_name']) }}">
                        Responder Cotización por Correo
                    </a>
                </div>
            @endif
        </div>

        <div class="footer">
            Solicitud recibida desde el Sitio Web Empresarial (/empresa) de BrazasTech S.R.L.<br>
            © {{ date('Y') }} BrazasTech Soluciones de Software S.R.L.
        </div>
    </div>
</body>
</html>
