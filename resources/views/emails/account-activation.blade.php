<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#ffffff;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="padding:24px 28px 0;">
                            <img src="{{ $message->embed(public_path('images/logo-email.png')) }}" alt="PideAca" style="height:52px;width:auto;display:block;" />
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px 6px;">
                            <h1 style="margin:0;font-size:20px;color:#0c2a4d;">Activá tu cuenta</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px;">
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.6;color:#334155;">
                                Hola{{ $name ? ' ' . $name : '' }}, gracias por registrarte en
                                PideAca. Tocá el botón de abajo para activar tu cuenta y
                                empezar a usarla.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 28px 4px;">
                            <a href="{{ $url }}" style="display:inline-block;background-color:#D24C19;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;padding:12px 26px;border-radius:4px;">
                                Activar mi cuenta
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px 6px;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
                                El enlace es válido durante 24 horas y solo puede usarse una vez.
                                Si no pediste esta cuenta, ignorá este correo.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 24px;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;">
                                &copy; {{ date('Y') }} pideaca.com - Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
