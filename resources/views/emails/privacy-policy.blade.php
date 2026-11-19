<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad - Restaurante Las Brazas</title>
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
            background: linear-gradient(135deg, #047857, #059669, #10b981);
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
            opacity: 0.92;
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
            background-color: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.35);
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
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
            color: #34d399;
            margin: 0 0 6px 0;
        }
        .section-text {
            font-size: 13px;
            color: #cbd5e1;
            margin: 0;
            line-height: 1.55;
        }
        .cta {
            text-align: center;
            margin-top: 24px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #059669, #10b981);
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            padding: 12px 24px;
            border-radius: 10px;
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
            color: #34d399;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Restaurante Las Brazas</h1>
            <p>Política de Privacidad y Protección de Datos</p>
        </div>

        <div class="content">
            <span class="badge">Protección de Datos Personales</span>
            <p class="salutation">Hola, {{ $user->name }}:</p>
            <p class="intro">
                En <strong>Restaurante Las Brazas Steakhouse</strong> respetamos tu privacidad y protegemos tu información personal con estrictos estándares de seguridad. Te enviamos una copia resumida de nuestra Política de Privacidad vigente.
            </p>

            <div class="section">
                <h2 class="section-title">1. Datos que Recopilamos</h2>
                <p class="section-text">
                    Únicamente almacenamos los datos indispensables para atenderte: tu nombre completo, correo electrónico, número de teléfono (opcional), direcciones de entrega que registres y el historial de tus reservas y pedidos.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">2. Inicio de Sesión con Google</h2>
                <p class="section-text">
                    Cuando eliges iniciar sesión con Google, solicitamos exclusivamente tu perfil básico y dirección de correo verificado (<code>openid</code>, <code>email</code>, <code>profile</code>). Nunca accedemos a tu contraseña de Google, correos, archivos ni contactos.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">3. Seguridad, Sesiones y Modo Offline (PWA)</h2>
                <p class="section-text">
                    Toda comunicación viaja cifrada mediante HTTPS/SSL y tus credenciales están protegidas mediante cifrado criptográfico. Al cerrar sesión, la plataforma purga automáticamente los datos privados de la caché local del navegador para impedir el acceso a terceros.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">4. Tus Derechos como Titular</h2>
                <p class="section-text">
                    No vendemos ni compartimos tus datos personales con terceros con fines publicitarios. Puedes solicitar en cualquier momento la actualización, rectificación o eliminación de tu cuenta y datos escribiéndonos desde la sección Contáctenos de nuestro portal.
                </p>
            </div>

            <div class="cta">
                <a class="btn" href="{{ url('/privacy') }}">Leer Política de Privacidad Completa</a>
            </div>
        </div>

        <div class="footer">
            Puedes consultar nuestra política completa en cualquier momento en
            <a href="{{ url('/privacy') }}">{{ url('/privacy') }}</a>.<br>
            © {{ date('Y') }} Restaurante Las Brazas Steakhouse • Santa Cruz de la Sierra, Bolivia.
        </div>
    </div>
</body>
</html>
