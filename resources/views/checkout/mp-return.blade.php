<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago - PideAca</title>
</head>
<body style="margin:0;padding:24px;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;text-align:center;">
    <div style="max-width:480px;margin:40px auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:32px 24px;">
        @if ($status === 'approved' || $status === 'success')
            <h1 style="font-size:20px;color:#047857;">¡Pago acreditado!</h1>
            <p style="font-size:14px;color:#334155;">Tu pago fue confirmado. El comercio ya puede preparar tu pedido.</p>
        @elseif ($status === 'failure')
            <h1 style="font-size:20px;color:#b91c1d;">El pago no se completó</h1>
            <p style="font-size:14px;color:#334155;">Podés intentarlo de nuevo desde tu pedido.</p>
        @else
            <h1 style="font-size:20px;color:#0c2a4d;">Pago en proceso</h1>
            <p style="font-size:14px;color:#334155;">Estamos esperando la confirmación. Te avisaremos cuando se acredite.</p>
        @endif
        <p><a href="{{ url('/') }}" style="color:#D24C19;font-size:14px;">Volver a PideAca</a></p>
    </div>
</body>
</html>
