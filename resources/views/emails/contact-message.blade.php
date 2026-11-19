<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Mensaje de Contacto - Restaurante Las Brazas</title>
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
            max-width: 620px;
            margin: 30px auto;
            background-color: #1e293b;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #334155;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        .header {
            background: linear-gradient(135deg, #c2410c, #ea580c, #f59e0b);
            padding: 28px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
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
        .badge {
            display: inline-block;
            background-color: rgba(234, 88, 12, 0.15);
            color: #fb923c;
            border: 1px solid rgba(234, 88, 12, 0.35);
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 18px;
        }
        .card {
            background-color: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 18px;
        }
        .field-row {
            margin-bottom: 10px;
            font-size: 14px;
        }
        .field-row:last-child {
            margin-bottom: 0;
        }
        .field-label {
            color: #94a3b8;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }
        .field-value {
            color: #f8fafc;
            font-weight: 600;
            font-size: 15px;
        }
        .field-value a {
            color: #fb923c;
            text-decoration: none;
        }
        .message-box {
            background-color: #0b1120;
            border-left: 4px solid #ea580c;
            border-radius: 8px;
            padding: 16px 18px;
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
            margin: 4px;
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
            <h1>Restaurante Las Brazas</h1>
            <p>Nuevo Mensaje desde el Formulario Contáctenos</p>
        </div>

        <div class="content">
            <span class="badge">Motivo: {{ $contactData['subject'] }}</span>

            <div class="card">
                <div class="field-row">
                    <span class="field-label">Nombre del Remitente</span>
                    <span class="field-value">{{ $contactData['name'] }}</span>
                </div>
                <div class="field-row">
                    <span class="field-label">Correo Electrónico (Responder a)</span>
                    <span class="field-value">
                        <a href="mailto:{{ $contactData['email'] }}">{{ $contactData['email'] }}</a>
                    </span>
                </div>
                <div class="field-row">
                    <span class="field-label">Teléfono / WhatsApp</span>
                    <span class="field-value">{{ $contactData['phone'] }}</span>
                </div>
                <div class="field-row">
                    <span class="field-label">Fecha y Hora de Recepción</span>
                    <span class="field-value">{{ $contactData['sent_at'] }}</span>
                </div>
            </div>

            <div>
                <span class="field-label">Mensaje del Cliente</span>
                <div class="message-box">{{ $contactData['message'] }}</div>
            </div>

            <div class="actions">
                <a class="btn" href="mailto:{{ $contactData['email'] }}?subject={{ rawurlencode('Re: ' . $contactData['subject'] . ' - Restaurante Las Brazas') }}">
                    Responder por Correo
                </a>
            </div>
        </div>

        <div class="footer">
            Puedes responder directamente a este correo desde Gmail y tu respuesta llegará a <strong>{{ $contactData['email'] }}</strong>.<br>
            © {{ date('Y') }} Restaurante Las Brazas Steakhouse • Portal Web Oficial
        </div>
    </div>
</body>
</html>
