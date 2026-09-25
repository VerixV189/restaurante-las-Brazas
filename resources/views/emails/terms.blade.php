<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos del Servicio - Restaurante Las Brazas</title>
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
            padding: 30px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.9;
            font-weight: 600;
        }
        .content {
            padding: 32px 28px;
        }
        .salutation {
            font-size: 17px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 14px;
        }
        .intro {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 24px;
        }
        .badge {
            display: inline-block;
            background-color: rgba(234, 88, 12, 0.15);
            color: #fb923c;
            border: 1px solid rgba(234, 88, 12, 0.3);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .section {
            background-color: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 16px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #f97316;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-text {
            font-size: 13px;
            color: #cbd5e1;
            margin: 0;
            line-height: 1.5;
        }
        .footer {
            background-color: #0b1120;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #1e293b;
            font-size: 12px;
            color: #64748b;
        }
        .footer a {
            color: #f97316;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔥 Restaurante Las Brazas</h1>
            <p>Términos del Servicio Aceptados</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="salutation">Estimado/a {{ $user->name }},</div>
            <div class="intro">
                Le damos una cordial bienvenida a nuestro portal gastronómico. Al registrar su cuenta, usted ha aceptado formalmente nuestros <strong>Términos del Servicio</strong>. A continuación le enviamos una copia detallada de las disposiciones acordadas para su respaldo personal:
            </div>

            <div class="badge">
                📅 Fecha de Aceptación: {{ now()->translatedFormat('d \d\e F \d\e Y, H:i') }}
            </div>

            <!-- Section 1 -->
            <div class="section">
                <h3 class="section-title">1. Aceptación y Naturaleza del Servicio</h3>
                <p class="section-text">
                    El registro como comensal en Restaurante Las Brazas otorga acceso a nuestro sistema de reservas en línea, visualización de menú en tiempo real, gestión de perfiles de comensal y notificaciones de eventos especiales a las brasas.
                </p>
            </div>

            <!-- Section 2 -->
            <div class="section">
                <h3 class="section-title">2. Responsabilidad de la Cuenta y Seguridad</h3>
                <p class="section-text">
                    Usted es el único responsable de la confidencialidad de su contraseña y de cualquier actividad originada bajo su cuenta. Las Brazas no solicitará su contraseña por correo ni teléfono.
                </p>
            </div>

            <!-- Section 3 -->
            <div class="section">
                <h3 class="section-title">3. Uso Correcto de la Plataforma</h3>
                <p class="section-text">
                    El usuario se compromete a proporcionar información fidedigna (nombre, teléfono y correo) al gestionar reservas o pedidos, evitando registros fraudulentos o reservas falsas que perjudiquen la disponibilidad de mesas.
                </p>
            </div>

            <!-- Section 4 -->
            <div class="section">
                <h3 class="section-title">4. Protección de Datos y Privacidad</h3>
                <p class="section-text">
                    Sus datos personales son tratados con estricta confidencialidad para la coordinación de sus visitas, facturación y confirmación de servicios, bajo altos estándares de seguridad informática.
                </p>
            </div>

            <!-- Section 5 -->
            <div class="section">
                <h3 class="section-title">5. Modificaciones y Contacto</h3>
                <p class="section-text">
                    Las Brazas se reserva el derecho de actualizar estos términos para optimizar la experiencia de los clientes. Puede consultar la versión vigente en cualquier momento en nuestro sitio web o contactar a nuestra administración.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 6px 0;"><strong>Restaurante Las Brazas • Steakhouse & Grill</strong></p>
            <p style="margin: 0 0 6px 0;">Av. Principal de las Brasas #742 • Reservas: (591) 4-4589211</p>
            <p style="margin: 0;">Este es un mensaje legal informativo automático generado al registrar su cuenta.</p>
        </div>
    </div>
</body>
</html>
