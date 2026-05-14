<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ $nextUrl }}">
    <title>Santa Cruz — procesando XML</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; padding: 2rem; text-align: center; color: #334155; background: #f8fafc; }
        a { color: #0d9488; }
        code { background: #e2e8f0; padding: 0.15rem 0.4rem; border-radius: 0.25rem; }
    </style>
</head>
<body>
    <p><strong>Analizando archivos XML desde el FTP…</strong></p>
    <p>{{ $done }} de {{ $total }} ya procesados en el servidor.</p>
    <p class="text-sm" style="color:#64748b">Esta página se recarga sola hasta terminar. Si se detiene, tocá <a href="{{ $nextUrl }}">continuar</a>.</p>
</body>
</html>
