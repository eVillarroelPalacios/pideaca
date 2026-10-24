<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Servicios Hogar - PideAca</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; font-size: 11px; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #0c2a4d; padding-bottom: 12px; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; color: #0c2a4d; margin: 0; }
        .subtitle { font-size: 11px; color: #64748b; margin: 4px 0 0; }
        .kpi-grid { display: table; width: 100%; margin-bottom: 20px; table-layout: fixed; }
        .kpi-box { display: table-cell; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; text-align: center; }
        .kpi-title { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: bold; }
        .kpi-value { font-size: 14px; font-weight: bold; color: #0c2a4d; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #0c2a4d; color: #fff; padding: 8px 6px; font-size: 10px; text-align: left; }
        td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; color: #334151; }
        tr:nth-child(even) { background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; background: #e2e8f0; color: #475569; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div>
            <h1 class="title">PideAca — Reporte de Servicios Hogar</h1>
            <p class="subtitle">{{ $isPrestador ? 'Prestador: ' . ($user->provider->business_name ?? $user->name) : 'Cliente: ' . $user->name }} | Emitido el: {{ $generatedAt }}</p>
        </div>
        <div style="text-align: right;">
            <strong style="color: #D24C19; font-size: 12px;">PideAca.com</strong>
            <div style="font-size: 9px; color: #64748b;">Comprobante Interno de Plataforma</div>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-box" style="border-left: 4px solid #D24C19;">
            <div class="kpi-title">{{ $isPrestador ? 'Total Facturado' : 'Total Gastado' }}</div>
            <div class="kpi-value">$ {{ number_format($totalMonto, 2, ',', '.') }}</div>
        </div>
        <div class="kpi-box" style="border-left: 4px solid #f59e0b;">
            <div class="kpi-title">Señas en Custodia</div>
            <div class="kpi-value">$ {{ number_format($totalSenas, 2, ',', '.') }}</div>
        </div>
        <div class="kpi-box" style="border-left: 4px solid #10b981;">
            <div class="kpi-title">Servicios Finalizados</div>
            <div class="kpi-value">{{ $orders->where('status', 'COMPLETED')->count() }}</div>
        </div>
        <div class="kpi-box" style="border-left: 4px solid #ec4899;">
            <div class="kpi-title">Índice Satisfacción</div>
            <div class="kpi-value">★ {{ $satisfaccion }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Orden</th>
                <th>Fecha</th>
                <th>{{ $isPrestador ? 'Cliente' : 'Prestador' }}</th>
                <th>Tipo / Rubro</th>
                <th>Monto Total</th>
                <th>Seña</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td><strong>#{{ $order->id }}</strong></td>
                <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $isPrestador ? ($order->customer->name ?? 'Cliente') : ($order->merchant->business_name ?? 'Prestador') }}</td>
                <td>
                    {{ $order->order_type === 'EMERGENCY' ? 'Urgencia' : ($order->order_type === 'SCHEDULED' ? 'Cita' : 'Presupuesto') }}<br>
                    <span style="color: #64748b;">{{ $order->subGroup->description ?? 'Hogar' }}</span>
                </td>
                <td><strong>$ {{ number_format((float)($order->total_amount ?? 0), 2, ',', '.') }}</strong></td>
                <td>$ {{ number_format((float)($order->deposit_amount ?? 0), 2, ',', '.') }}</td>
                <td><span class="badge">{{ $order->status }}</span></td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #94a3b8; padding: 20px;">Sin registros para el período consultado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Comprobante generado automáticamente por PideAca.com — Documento válido para control operativo interno de servicios. Página 1 de 1
    </div>
</body>
</html>
