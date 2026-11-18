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
                            <h1 style="margin:0;font-size:20px;color:#0c2a4d;">Tu suscripción está pendiente de pago</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px;">
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.6;color:#334155;">
                                Hola{{ $name ? ' ' . $name : '' }}, tu cuota <strong>{{ $invoice->plan_name }}</strong>
                                de <strong>${{ number_format($invoice->amount, 2, ',', '.') }}</strong>
                                (período {{ $invoice->period_start }} al {{ $invoice->period_end }}) sigue impaga.
                            </p>
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.6;color:#334155;">
                                Si no se acredita el pago en <strong>{{ max(0, $graceDays - $overdueDays) }} días</strong>,
                                tu cuenta se suspenderá y dejarás de recibir trabajos hasta regularizar.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px 6px;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
                                Hacé tu transferencia y avisanos por los canales habituales para imputarla.
                                Si ya pagaste, ignorá este correo.
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
