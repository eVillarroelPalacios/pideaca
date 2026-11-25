Hola{{ $name ? ' ' . $name : '' }},

Tu cuota {{ $invoice->plan_name }} de ${{ number_format($invoice->amount, 2, ',', '.') }} (período {{ $invoice->period_start }} al {{ $invoice->period_end }}) sigue impaga.

Si no se acredita el pago en {{ max(0, $graceDays - $overdueDays) }} días, tu cuenta se suspenderá y dejarás de recibir trabajos hasta regularizar.

Hacé tu transferencia y avisanos por los canales habituales para imputarla. Si ya pagaste, ignorá este correo.

--
PideAca - pideaca.com - Todos los derechos reservados.
