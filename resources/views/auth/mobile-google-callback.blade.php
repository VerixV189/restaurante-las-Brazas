<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
  <title>{{ $isError ? 'Error de Autenticación' : 'Regresando a Las Brazas' }} — App Móvil</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: radial-gradient(circle at top, #1e293b 0%, #090d16 70%);
      color: #f8fafc;
    }
    .card {
      width: 100%;
      max-width: 420px;
      background: rgba(15, 23, 42, 0.92);
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 24px;
      padding: 32px 24px;
      text-align: center;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65);
    }
    .badge {
      width: 64px;
      height: 64px;
      margin: 0 auto 18px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 30px;
      background: {{ $isError ? 'rgba(239, 68, 68, 0.16)' : 'linear-gradient(135deg, #ea580c, #f59e0b)' }};
      border: 1px solid {{ $isError ? 'rgba(239, 68, 68, 0.4)' : 'rgba(251, 146, 60, 0.45)' }};
    }
    h1 {
      font-size: 20px;
      font-weight: 800;
      margin-bottom: 10px;
      color: #ffffff;
    }
    p {
      font-size: 14px;
      line-height: 1.55;
      color: #cbd5e1;
      margin-bottom: 24px;
    }
    .btn {
      display: inline-block;
      width: 100%;
      padding: 14px 20px;
      border-radius: 14px;
      background: linear-gradient(90deg, #ea580c, #f59e0b);
      color: #ffffff;
      font-weight: 800;
      font-size: 15px;
      text-decoration: none;
      box-shadow: 0 10px 25px -5px rgba(234, 88, 12, 0.45);
    }
    .btn:active { transform: scale(0.98); }
    .hint {
      margin-top: 16px;
      font-size: 12px;
      color: #94a3b8;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="badge">{{ $isError ? '⚠️' : '🔥' }}</div>
    <h1>{{ $isError ? 'No se pudo completar el acceso' : '¡Sesión lista en Las Brazas!' }}</h1>
    <p>{{ $displayMessage }}</p>

    <a id="open-app-btn" href="{{ $deepLinkUrl }}" class="btn">
      Abrir App Las Brazas
    </a>

    <div class="hint">
      Si la aplicación no se abre automáticamente en unos segundos, pulsa el botón superior.
    </div>
  </div>

  <script>
    (function () {
      var intentUrl = @json($intentUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
      var deepLinkUrl = @json($deepLinkUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
      var isAndroid = /Android/i.test(navigator.userAgent || '');

      try {
        if (isAndroid) {
          window.location.replace(intentUrl);
          setTimeout(function () {
            window.location.href = deepLinkUrl;
          }, 700);
        } else {
          window.location.replace(deepLinkUrl);
        }
      } catch (e) {
        window.location.href = deepLinkUrl;
      }
    })();
  </script>
</body>
</html>
