<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta conectada - PideAca</title>
</head>
<body style="margin:0;padding:24px;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;text-align:center;">
    <div style="max-width:480px;margin:40px auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:32px 24px;">
        @if ($ok ?? false)
            <h1 style="font-size:20px;color:#047857;">¡Cuenta conectada!</h1>
            <p style="font-size:14px;color:#334155;">Ya podés cobrar directo con Mercado Pago: lo que te paguen entra a tu cuenta y la comisión va a PideAca sola. Podés volver a la app.</p>
        @else
            <h1 style="font-size:20px;color:#b91c1d;">No se pudo conectar</h1>
            <p style="font-size:14px;color:#334155;">{{ $message ?? 'Intentá de nuevo desde tu billetera.' }}</p>
        @endif
        <p><a href="{{ url('/') }}" style="color:#D24C19;font-size:14px;">Volver a PideAca</a></p>
    </div>
</body>
</html>
