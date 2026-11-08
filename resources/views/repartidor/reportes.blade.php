<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte del repartidor · PideAca</title>
    <style>
        :root {
            --navy: #0c2a4d;
            --navy-dark: #071a30;
            --orange: #D24C19;
            --orange-soft: #FFF7ED;
            --orange-border: #FED7AA;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --bg: #f1f5f9;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px 16px 48px;
            background: var(--bg);
            color: var(--ink);
            font-family: 'Inter', 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report {
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 34px rgba(15, 23, 42, .08);
        }

        /* ── Encabezado ── */
        .report-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 20px 26px;
            background: linear-gradient(180deg, var(--navy) 0%, var(--navy-dark) 100%);
            border-bottom: 3px solid var(--orange);
            color: #fff;
        }

        .brand { display: flex; align-items: center; gap: 14px; }

        .brand-logo {
            width: 48px;
            height: 48px;
            border-radius: 13px;
            background: #fff;
            color: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .brand-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -.4px;
            line-height: 1.1;
        }

        .brand-badge {
            background: var(--orange);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .8px;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .brand-sub { margin-top: 5px; font-size: 12.5px; font-weight: 500; color: rgba(255, 255, 255, .68); }

        .head-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 10px 18px;
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 800;
            line-height: 1;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, .22);
            background: rgba(255, 255, 255, .10);
            color: #fff;
            transition: .15s;
        }

        .btn:hover { background: rgba(255, 255, 255, .20); }

        .btn svg { width: 15px; height: 15px; }

        .btn-print { background: var(--orange); border-color: var(--orange); }
        .btn-print:hover { background: #b84114; }

        /* ── Meta del reporte ── */
        .report-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 14px 26px;
            border-bottom: 1px solid #e8edf3;
            font-size: 12.5px;
            color: var(--muted);
        }

        .report-meta strong { color: var(--ink); font-weight: 800; }

        /* ── Tarjetas de métricas ── */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
            padding: 22px 26px 8px;
        }

        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .05);
        }

        .card.accent { background: var(--orange-soft); border-color: var(--orange-border); }

        .card-label {
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .card-value {
            margin-top: 8px;
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -.6px;
            line-height: 1.1;
        }

        .card-value.orange { color: var(--orange); }
        .card-value.green { color: #16a34a; }

        .card-value small { font-size: 13px; font-weight: 700; color: var(--muted); letter-spacing: 0; }

        .card-sub { margin-top: 8px; font-size: 11.5px; font-weight: 600; color: #94a3b8; }

        /* ── Historial ── */
        .section-head {
            display: flex;
            align-items: center;
            gap: 11px;
            flex-wrap: wrap;
            padding: 26px 26px 12px;
        }

        .section-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--orange);
            box-shadow: 0 0 0 4px rgba(210, 76, 25, .14);
            flex-shrink: 0;
        }

        .section-title { font-size: 18px; font-weight: 800; letter-spacing: -.3px; }

        .section-hint { margin-left: auto; font-size: 12px; font-weight: 500; color: #94a3b8; }

        .table-wrap { padding: 0 26px 28px; overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }

        thead th {
            text-align: left;
            padding: 11px 12px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        thead th.num { text-align: right; }

        tbody td {
            padding: 12px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }

        tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fffaf5; }

        .order-no {
            display: inline-block;
            padding: 4px 11px;
            border-radius: 999px;
            background: var(--orange-soft);
            border: 1px solid var(--orange-border);
            color: var(--orange);
            font-size: 11.5px;
            font-weight: 800;
            white-space: nowrap;
        }

        .shop { font-weight: 700; color: var(--ink); }
        .date { color: var(--muted); white-space: nowrap; }
        .money { font-weight: 800; color: var(--ink); white-space: nowrap; }
        .money.gain { color: var(--orange); }
        .muted { color: #94a3b8; }
        .empty { padding: 34px 12px; text-align: center; color: #94a3b8; font-size: 13px; }

        tfoot td {
            padding: 13px 12px;
            border-top: 2px solid var(--line);
            font-weight: 800;
            font-size: 13px;
        }

        tfoot td.num { text-align: right; }
        tfoot .total-gain { color: var(--orange); }

        /* ── Impresión / PDF ── */
        @media print {
            @page { size: A4 portrait; margin: 12mm; }

            body { padding: 0; background: #fff; }

            .report {
                width: 100%;
                max-width: none;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .no-print { display: none !important; }

            .report-head { border-radius: 0; }

            .cards { break-inside: avoid; }

            thead { display: table-header-group; }
            tfoot { display: table-row-group; }
            tr { break-inside: avoid; }
        }

        @media (max-width: 720px) {
            body { padding: 12px 8px 32px; }
            .report { width: 100%; }
            .report-head, .report-meta, .cards, .section-head, .table-wrap { padding-left: 16px; padding-right: 16px; }
            .table-wrap { padding-bottom: 20px; }
            .brand-title { font-size: 18px; }
            .section-hint { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>
    @php
        $money = fn ($value) => \App\Support\Money::format($value);
        $rating = $stats['rating'] !== null ? number_format($stats['rating'], 1, ',', '.') : '—';
        $acceptance = $stats['acceptance'] !== null ? $stats['acceptance'] . '%' : '—';
        $listedEarnings = (float) $orders->sum('delivery_fee');
        $period = now()->translatedFormat('F Y');
    @endphp

    <main class="report">
        <header class="report-head">
            <div class="brand">
                <div class="brand-logo">
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 13h1l2-5h8l2 5h4"></path>
                        <circle cx="7" cy="18" r="1.6"></circle>
                        <circle cx="18" cy="18" r="1.6"></circle>
                        <path d="M9 18h6"></path>
                    </svg>
                </div>
                <div>
                    <div class="brand-title">
                        Reporte del repartidor
                        <span class="brand-badge">PIDEACA</span>
                    </div>
                    <div class="brand-sub">{{ $user->name }} · Historial de viajes y ganancias</div>
                </div>
            </div>

            <div class="head-actions no-print">
                <a class="btn" href="{{ route('dashboard', ['section' => 'repartidor']) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6"></path>
                    </svg>
                    Volver al panel
                </a>
                <button type="button" class="btn btn-print" onclick="window.print()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 9V2h12v7"></path>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8" rx="1"></rect>
                    </svg>
                    Descargar PDF
                </button>
            </div>
        </header>

        <div class="report-meta">
            <span>Período del reporte: <strong>{{ ucfirst($period) }}</strong></span>
            <span>Generado el <strong>{{ now()->format('d/m/Y H:i') }}</strong></span>
        </div>

        <section class="cards" aria-label="Métricas del repartidor">
            <article class="card accent">
                <div class="card-label">Ganancias de hoy</div>
                <div class="card-value orange">{{ $money($stats['earningsToday']) }}</div>
                <div class="card-sub">Base {{ $money($stats['baseToday']) }} · Propinas {{ $money($stats['tipsToday']) }}</div>
            </article>

            <article class="card">
                <div class="card-label">Ganancias del mes</div>
                <div class="card-value">{{ $money($earningsMonth) }}</div>
                <div class="card-sub">Con propinas incluidas</div>
            </article>

            <article class="card">
                <div class="card-label">Propinas del mes</div>
                <div class="card-value green">{{ $money($tipsMonth) }}</div>
                <div class="card-sub">Acreditadas en la billetera</div>
            </article>

            <article class="card">
                <div class="card-label">Entregas del mes</div>
                <div class="card-value">{{ $tripsMonth }} <small>de {{ $stats['completed'] }} totales</small></div>
                <div class="card-sub">{{ $stats['deliveredToday'] }} entregadas hoy</div>
            </article>

            <article class="card">
                <div class="card-label">Calificación</div>
                <div class="card-value orange">{{ $rating }} <small>/ 5</small></div>
                <div class="card-sub">{{ $stats['ratingCount'] }} valoraciones recibidas</div>
            </article>

            <article class="card">
                <div class="card-label">Tasa de aceptación</div>
                <div class="card-value">{{ $acceptance }}</div>
                <div class="card-sub">Saldo en billetera: {{ $money($stats['walletBalance']) }}</div>
            </article>
        </section>

        <div class="section-head">
            <span class="section-dot"></span>
            <h2 class="section-title">Historial de viajes</h2>
            <span class="section-hint">{{ $orders->count() }} viajes entregados · Total histórico {{ $money($totalEarnings) }}</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Comercio</th>
                        <th>Fecha de entrega</th>
                        <th class="num">Total del pedido</th>
                        <th class="num">Ganancia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><span class="order-no">#{{ $order->order_number ?: $order->id }}</span></td>
                            <td class="shop">{{ $order->provider->business_name ?? '—' }}</td>
                            <td class="date">{{ $order->updated_at ? $order->updated_at->format('d/m/Y H:i') : '—' }}</td>
                            <td class="num money">{{ $money($order->total) }}</td>
                            <td class="num money gain">{{ $money($order->delivery_fee) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">Todavía no tenés viajes entregados. Cuando completes tu primera entrega aparecerá acá.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($orders->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="3">Total de los viajes listados</td>
                            <td class="num">{{ $money($orders->sum('total')) }}</td>
                            <td class="num total-gain">{{ $money($listedEarnings) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </main>
</body>
</html>
