<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes Hogar - PideAca</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
</head>
<body style="margin:0;padding:24px;background:#f8fafc;font-family:'Inter',sans-serif;color:#1e293b;">
    <div style="max-width:1200px;margin:0 auto;">
        
        <!-- ENCABEZADO -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:16px;">
            <div>
                <h1 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">Reportes Hogar ({{ ($isPrestador ?? false) ? 'Prestador: ' . (($user->provider->business_name ?? null) ?: $user->name) : 'Cliente: ' . $user->name }})</h1>
                <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Consolidado de servicios, métricas financieras y estados del Módulo Hogar</p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <a href="{{ route('reportes.hogar.pdf', request()->all()) }}" target="_blank" style="background:#D24C19;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Exportar a PDF
                </a>
            </div>
        </div>

        <!-- FILTROS -->
        <div style="background:#fff;border:1px solid #e2e8f0;padding:16px;margin-bottom:24px;border-radius:8px;">
            <form method="GET" action="{{ route('reportes.hogar') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#64748b;margin-bottom:4px;text-transform:uppercase;">Fecha Desde</label>
                    <input type="date" name="fecha_desde" value="{{ $filters['fecha_desde'] ?? '' }}" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#64748b;margin-bottom:4px;text-transform:uppercase;">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ $filters['fecha_hasta'] ?? '' }}" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#64748b;margin-bottom:4px;text-transform:uppercase;">Estado</label>
                    <select name="estado" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;background:#fff;">
                        <option value="">Todos los estados</option>
                        <option value="REQUESTED" {{ (($filters['estado'] ?? '') === 'REQUESTED') ? 'selected' : '' }}>Pendiente</option>
                        <option value="QUOTED" {{ (($filters['estado'] ?? '') === 'QUOTED') ? 'selected' : '' }}>Presupuestado</option>
                        <option value="ACCEPTED" {{ (($filters['estado'] ?? '') === 'ACCEPTED') ? 'selected' : '' }}>Confirmado / Cita</option>
                        <option value="IN_PROGRESS" {{ (($filters['estado'] ?? '') === 'IN_PROGRESS') ? 'selected' : '' }}>En camino / Proceso</option>
                        <option value="COMPLETED" {{ (($filters['estado'] ?? '') === 'COMPLETED') ? 'selected' : '' }}>Completado</option>
                        <option value="CANCELLED" {{ (($filters['estado'] ?? '') === 'CANCELLED') ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#64748b;margin-bottom:4px;text-transform:uppercase;">Modalidad</label>
                    <select name="tipo" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;background:#fff;">
                        <option value="">Todas las modalidades</option>
                        <option value="EMERGENCY" {{ (($filters['tipo'] ?? '') === 'EMERGENCY') ? 'selected' : '' }}>Urgencia 24/7</option>
                        <option value="SCHEDULED" {{ (($filters['tipo'] ?? '') === 'SCHEDULED') ? 'selected' : '' }}>Cita programada</option>
                        <option value="QUOTE" {{ (($filters['tipo'] ?? '') === 'QUOTE') ? 'selected' : '' }}>Presupuesto a cotizar</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" style="background:#0c2a4d;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">Filtrar</button>
                    <a href="{{ route('reportes.hogar') }}" style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;padding:8px 16px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;">Limpiar</a>
                </div>
            </form>
        </div>

        <!-- KPIS / RESUMEN -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;">
            <div style="background:#fff;border:1px solid #e2e8f0;padding:18px;border-radius:8px;border-left:4px solid #D24C19;">
                <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">{{ ($isPrestador ?? false) ? 'Total Facturado' : 'Total Gastado' }}</span>
                <div style="font-size:22px;font-weight:700;color:#0c2a4d;margin-top:6px;">$ {{ number_format($kpis['total_economico'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;padding:18px;border-radius:8px;border-left:4px solid #f59e0b;">
                <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Seña en Custodia</span>
                <div style="font-size:22px;font-weight:700;color:#0c2a4d;margin-top:6px;">$ {{ number_format($kpis['senas_custodia'] ?? 0, 2, ',', '.') }}</div>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;padding:18px;border-radius:8px;border-left:4px solid #10b981;">
                <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Servicios Finalizados</span>
                <div style="font-size:22px;font-weight:700;color:#0c2a4d;margin-top:6px;">{{ $kpis['completados'] ?? 0 }}</div>
            </div>
            @if($isPrestador ?? false)
            <div style="background:#fff;border:1px solid #e2e8f0;padding:18px;border-radius:8px;border-left:4px solid #3b82f6;">
                <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Efectividad Cotizaciones</span>
                <div style="font-size:22px;font-weight:700;color:#0c2a4d;margin-top:6px;">{{ $kpis['efectividad_cotizaciones'] ?? 0 }}%</div>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;padding:18px;border-radius:8px;border-left:4px solid #6366f1;">
                <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Trabajos Pendientes</span>
                <div style="font-size:22px;font-weight:700;color:#0c2a4d;margin-top:6px;">{{ $kpis['pendientes'] ?? 0 }} ({{ $kpis['en_proceso'] ?? 0 }} en proceso)</div>
            </div>
            @endif
            <div style="background:#fff;border:1px solid #e2e8f0;padding:18px;border-radius:8px;border-left:4px solid #ec4899;">
                <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Reputación / Satisfacción</span>
                <div style="font-size:22px;font-weight:700;color:#0c2a4d;margin-top:6px;">★ {{ $kpis['promedio_reputacion'] ?? 0 }} <span style="font-size:12px;color:#64748b;font-weight:normal;">({{ $kpis['total_resenas'] ?? 0 }} reseñas)</span></div>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;padding:18px;border-radius:8px;border-left:4px solid #ef4444;">
                <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Cancelaciones</span>
                <div style="font-size:22px;font-weight:700;color:#0c2a4d;margin-top:6px;">{{ $kpis['cancelados'] ?? 0 }}</div>
            </div>
        </div>

        <!-- TABLA DE DETALLES -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;background:#f8fafc;font-weight:700;font-size:14px;color:#0c2a4d;">
                Detalle de Órdenes y Solicitudes Hogar
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;text-align:left;font-size:12px;">
                    <thead>
                        <tr style="background:#f1f5f9;color:#475569;border-bottom:1px solid #e2e8f0;">
                            <th style="padding:12px 16px;">N° Orden</th>
                            <th style="padding:12px 16px;">Fecha / Hora</th>
                            <th style="padding:12px 16px;">{{ ($isPrestador ?? false) ? 'Cliente' : 'Prestador' }}</th>
                            <th style="padding:12px 16px;">Tipo / Rubro</th>
                            <th style="padding:12px 16px;">Problema / Detalle</th>
                            <th style="padding:12px 16px;">Monto Total</th>
                            <th style="padding:12px 16px;">Seña Custodia</th>
                            <th style="padding:12px 16px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders ?? [] as $order)
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:12px 16px;font-weight:700;color:#0c2a4d;font-family:monospace;">#{{ $order->id }}</td>
                            <td style="padding:12px 16px;color:#64748b;white-space:nowrap;">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                            <td style="padding:12px 16px;">
                                @if($isPrestador ?? false)
                                    <strong>{{ $order->customer->name ?? 'Cliente' }}</strong>
                                    <div style="font-size:11px;color:#64748b;">{{ $order->customer->phone ?? $order->customer->email ?? '' }}</div>
                                @else
                                    <strong>{{ $order->merchant->business_name ?? 'Prestador' }}</strong>
                                    <div style="font-size:11px;color:#64748b;">{{ $order->merchant->user->phone ?? $order->merchant->user->email ?? '' }}</div>
                                @endif
                            </td>
                            <td style="padding:12px 16px;">
                                <span style="display:inline-block;padding:3px 8px;border-radius:4px;font-size:10px;font-weight:700;background:#e0f2fe;color:#0369a1;">
                                    {{ $order->order_type === 'EMERGENCY' ? 'Urgencia 24/7' : ($order->order_type === 'SCHEDULED' ? 'Cita Programada' : 'Presupuesto') }}
                                </span>
                                <div style="font-weight:600;color:#1e293b;margin-top:3px;">{{ $order->subGroup->description ?? 'Servicio Hogar' }}</div>
                            </td>
                            <td style="padding:12px 16px;color:#475569;max-width:220px;">
                                {{ $order->description ?? 'Sin detalle especificado' }}
                            </td>
                            <td style="padding:12px 16px;font-weight:700;color:#0c2a4d;white-space:nowrap;">
                                $ {{ number_format((float)($order->total_amount ?? 0), 2, ',', '.') }}
                            </td>
                            <td style="padding:12px 16px;color:#d97706;font-weight:600;white-space:nowrap;">
                                $ {{ number_format((float)($order->deposit_amount ?? 0), 2, ',', '.') }}
                                <div style="font-size:10px;color:#64748b;">{{ $order->escrow_status ?? 'NONE' }}</div>
                            </td>
                            <td style="padding:12px 16px;white-space:nowrap;">
                                @php
                                    $bg = '#f1f5f9';
                                    $fg = '#475569';
                                    if($order->status === 'COMPLETED') { $bg = '#d1fae5'; $fg = '#065f46'; }
                                    elseif(in_array($order->status, ['ACCEPTED', 'IN_PROGRESS', 'QUOTED'])) { $bg = '#e0f2fe'; $fg = '#0369a1'; }
                                    elseif($order->status === 'CANCELLED') { $bg = '#fee2e2'; $fg = '#991b1b'; }
                                @endphp
                                <span style="display:inline-block;padding:4px 10px;border-radius:9999px;font-size:11px;font-weight:700;background:{{ $bg }};color:{{ $fg }};">
                                    {{ $order->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" style="padding:32px;text-align:center;color:#94a3b8;font-style:italic;">
                                No se encontraron registros de servicios para el hogar con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>
