<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Condiciones y Políticas del Sitio - Restaurante Las Brazas</title>
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
            background: linear-gradient(135deg, #b45309, #d97706, #ea580c);
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
        .policy-card {
            background-color: #0f172a;
            border-left: 4px solid #f59e0b;
            border-radius: 4px 10px 10px 4px;
            padding: 16px 20px;
            margin-bottom: 16px;
            border-top: 1px solid #334155;
            border-right: 1px solid #334155;
            border-bottom: 1px solid #334155;
        }
        .policy-title {
            font-size: 14px;
            font-weight: 700;
            color: #fbbf24;
            margin: 0 0 6px 0;
        }
        .policy-text {
            font-size: 13px;
            color: #cbd5e1;
            margin: 0;
            line-height: 1.5;
        }
        .highlight-box {
            background: rgba(245, 158, 11, 0.1);
            border: 1px dashed #f59e0b;
            border-radius: 10px;
            padding: 14px 18px;
            margin: 20px 0;
            font-size: 13px;
            color: #fde68a;
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
            <h1>📋 Condiciones y Políticas del Sitio</h1>
            <p>Restaurante Las Brazas • Normas del Comensal</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="salutation">Estimado/a {{ $user->name }},</div>
            <div class="intro">
                Para garantizar la máxima calidad gastronómica y una experiencia memorable para todos nuestros visitantes en <strong>Las Brazas</strong>, compartimos nuestras condiciones operativas y políticas vigentes:
            </div>

            <div class="highlight-box">
                ⏱ <strong>Tiempo de Tolerancia en Reservaciones:</strong> Mantenemos su mesa reservada con una tolerancia de <strong>15 minutos</strong> a partir de la hora pautada. Tras ese lapso, la mesa podrá ser reasignada a otros comensales en espera.
            </div>

            <!-- Policy 1 -->
            <div class="policy-card">
                <h3 class="policy-title">1. Reservaciones y Distribución de Salón</h3>
                <p class="policy-text">
                    La preferencia de mesas (Terraza, Salón VIP o Familiar) está sujeta a disponibilidad y capacidad operativa. Nuestro equipo de anfitriones optimizará la mejor ubicación para el confort de su grupo.
                </p>
            </div>

            <!-- Policy 2 -->
            <div class="policy-card">
                <h3 class="policy-title">2. Políticas de Cancelación o Reprogramación</h3>
                <p class="policy-text">
                    Agradecemos notificar cancelaciones o cambios en el número de comensales con al menos 2 horas de anticipación a través del sistema o telefónicamente, permitiendo atender a otras familias comensales.
                </p>
            </div>

            <!-- Policy 3 -->
            <div class="policy-card">
                <h3 class="policy-title">3. Alérgenos y Requerimientos Especiales</h3>
                <p class="policy-text">
                    Le solicitamos informar al personal o en el campo de peticiones especiales sobre cualquier intolerancia alimentaria, alergia o término de cocción preferido para nuestros cortes a la brasa.
                </p>
            </div>

            <!-- Policy 4 -->
            <div class="policy-card">
                <h3 class="policy-title">4. Métodos de Pago y Facturación Electrónica</h3>
                <p class="policy-text">
                    Aceptamos efectivo, tarjetas de débito/crédito, transferencias y pagos QR. Todas las comandas emiten comprobante con validez fiscal según las normativas tributarias.
                </p>
            </div>

            <!-- Policy 5 -->
            <div class="policy-card">
                <h3 class="policy-title">5. Convivencia y Ambiente Familiar</h3>
                <p class="policy-text">
                    Las Brazas promueve un espacio respetuoso, familiar y seguro para todos. Nos reservamos el derecho de admisión ante conductas inapropiadas o que perturben la tranquilidad del salón.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 6px 0;"><strong>Restaurante Las Brazas • Steakhouse & Grill</strong></p>
            <p style="margin: 0 0 6px 0;">Atención al Cliente: contacto@lasbrazas.com • Cochabamba / Santa Cruz</p>
            <p style="margin: 0;">Gracias por elegirnos. ¡Esperamos deleitar su paladar muy pronto!</p>
        </div>
    </div>
</body>
</html>
