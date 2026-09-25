<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica tu Cuenta - Restaurante Las Brazas</title>
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
            max-width: 600px;
            margin: 30px auto;
            background-color: #1e293b;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #334155;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        .header {
            background: linear-gradient(135deg, #ea580c, #f97316, #fb923c);
            padding: 36px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header-icon {
            font-size: 38px;
            margin-bottom: 8px;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 26px;
            font-weight: 800;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.95;
            font-weight: 600;
        }
        .content {
            padding: 36px 30px;
            text-align: center;
        }
        .salutation {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 12px;
        }
        .message {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .cta-container {
            margin: 32px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #ea580c, #d97706);
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            padding: 14px 34px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(234, 88, 12, 0.35);
            transition: all 0.2s ease;
        }
        .expiry-note {
            background-color: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 12px;
            color: #94a3b8;
            margin-top: 24px;
            text-align: left;
        }
        .raw-link {
            font-size: 11px;
            color: #64748b;
            word-break: break-all;
            margin-top: 8px;
        }
        .raw-link a {
            color: #f97316;
            text-decoration: underline;
        }
        .footer {
            background-color: #0b1120;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #1e293b;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">✉️</div>
            <h1>¡Bienvenido a Las Brazas!</h1>
            <p>Activación de Cuenta de Usuario</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="salutation">¡Hola, {{ $user->name }}!</div>
            <p class="message">
                Gracias por registrarte en el portal de <strong>Restaurante Las Brazas</strong>. Para completar la creación de tu cuenta y verificar que eres el propietario de esta dirección de correo electrónico, por favor pulsa el botón a continuación:
            </p>

            <!-- CTA Button -->
            <div class="cta-container">
                <a href="{{ $verificationUrl }}" class="cta-button" target="_blank">
                    ✓ Verificar Mi Cuenta
                </a>
            </div>

            <!-- Expiry Note -->
            <div class="expiry-note">
                🔒 <strong>Seguridad:</strong> Este enlace de verificación es seguro y expirará en <strong>24 horas</strong>. Si tú no creaste esta cuenta, puedes desestimar este mensaje con tranquilidad.
                <div class="raw-link">
                    ¿El botón no responde? Copia y abre este enlace:<br>
                    <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 6px 0;"><strong>Restaurante Las Brazas • Steakhouse & Grill</strong></p>
            <p style="margin: 0;">Sabor auténtico a las brasas y excelencia culinaria.</p>
        </div>
    </div>
</body>
</html>
