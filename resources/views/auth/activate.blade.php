<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Activar cuenta - PideAca</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f1f5f9;
            font-family: Arial, Helvetica, sans-serif;
        }
        .activate-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
        }
        .activate-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .activate-body { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
        .activate-title { font-size: 18px; font-weight: 700; color: #0c2a4d; }
        .activate-text { font-size: 13px; line-height: 1.6; color: #475569; }
        .alert {
            display: block;
            font-size: 12.5px;
            padding: 9px 12px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }
        .btn-main {
            display: block;
            text-align: center;
            text-decoration: none;
            padding: 11px;
            background: #D24C19;
            color: #ffffff;
            border: 1px solid #D24C19;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
        }
        .btn-main:hover { background: #ffffff; color: #D24C19; }
        .badge-ok {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="activate-card">
        <div class="activate-head">
            <img src="{{ asset('images/logo.png') }}" alt="PideAca" style="height:44px;width:auto;max-width:160px;object-fit:contain;display:block;" />
        </div>

        <div class="activate-body">
            @if ($activated)
                <div class="activate-title">¡Cuenta activada!</div>
                <p class="activate-text">
                    @if ($userName)
                        Hola <strong>{{ $userName }}</strong>,
                    @endif
                    tu cuenta ya está activa. Ya podés iniciar sesión en la web o en la app.
                </p>
                <span class="badge-ok">Tu cuenta está lista para usar.</span>
                <a href="{{ url('/') }}" class="btn-main">Ir a iniciar sesión</a>
            @else
                <div class="activate-title">Enlace no válido</div>
                <p class="activate-text">
                    Este enlace de activación ya venció, ya fue usado o no existe.
                    Iniciá sesión para recibir uno nuevo en tu correo.
                </p>
                <span class="alert">El enlace no es válido o expiró.</span>
                <a href="{{ url('/') }}" class="btn-main">Volver al inicio</a>
            @endif
        </div>
    </div>
</body>
</html>
