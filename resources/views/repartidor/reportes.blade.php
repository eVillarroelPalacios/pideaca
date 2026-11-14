<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
            padding: 0 0 48px;
            background: var(--bg);
            color: var(--ink);
            font-family: 'Inter', 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Vista abierta: sin tarjeta externa que encierre el reporte. El
           contenido fluye sobre el fondo gris unificado, como el resto de
           las secciones principales. */
        .report {
            width: 100%;
            max-width: none;
            margin: 0;
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            overflow: visible;
        }

        .report-body {
            width: 100%;
            max-width: 1320px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ── Encabezado ── */
        .report-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 20px 26px;
            margin: 0;
            border: 0;
            border-radius: 0;
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
            padding: 14px 0;
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
            padding: 22px 0 8px;
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

        /* ── Billetera ── */
        .wallet {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            margin: 16px 0 0;
            padding: 18px 22px;
            border: 1px solid var(--orange-border);
            border-radius: 14px;
            background: linear-gradient(135deg, #fff7ed 0%, #fff 65%);
        }

        .wallet-label { font-size: 10.5px; font-weight: 800; letter-spacing: .7px; text-transform: uppercase; color: #b45309; }

        .wallet-value { margin-top: 6px; font-size: 30px; font-weight: 800; letter-spacing: -.6px; color: var(--orange); line-height: 1.05; }

        .wallet-sub { margin-top: 6px; font-size: 11.5px; font-weight: 600; color: var(--muted); }

        .wallet-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--orange);
            border-radius: 999px;
            background: var(--orange);
            color: #fff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
            padding: 11px 22px;
            cursor: pointer;
            transition: .15s;
        }

        .wallet-btn:hover { background: #b84114; }
        .wallet-btn:disabled { background: #e5e7eb; border-color: #e5e7eb; color: #94a3b8; cursor: not-allowed; }

        .badge-status { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; }
        .badge-status.pending { background: #fffbeb; color: #b45309; }
        .badge-status.approved { background: #ecfdf5; color: #047857; }
        .badge-status.rejected { background: #fef2f2; color: #b91c1c; }

        .wd-overlay {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(7, 26, 48, .55);
            z-index: 900;
        }

        .wd-overlay.open { display: flex; }

        .wd-modal {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(7, 26, 48, .35);
            overflow: hidden;
        }

        .wd-head { padding: 18px 22px; background: linear-gradient(180deg, var(--navy) 0%, var(--navy-dark) 100%); border-bottom: 3px solid var(--orange); color: #fff; }
        .wd-title { margin: 0; font-size: 17px; font-weight: 800; }
        .wd-sub { margin: 5px 0 0; font-size: 12px; color: rgba(255,255,255,.72); }
        .wd-body { padding: 18px 22px; display: flex; flex-direction: column; gap: 12px; }
        .wd-field label { display: block; font-size: 10.5px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
        .wd-field input, .wd-field select, .wd-field textarea {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            font-family: inherit;
            font-size: 13px;
            color: var(--ink);
            background: #fff;
        }
        .wd-field textarea { resize: vertical; min-height: 60px; }
        .wd-msg { min-height: 16px; font-size: 12px; font-weight: 700; color: #dc2626; }
        .wd-msg.ok { color: #047857; }
        .wd-foot { display: flex; justify-content: flex-end; gap: 10px; padding: 0 22px 20px; }
        .wd-cancel { background: #fff; border: 1px solid #cbd5e1; color: #334155; border-radius: 8px; padding: 9px 16px; font-family: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }
        .wd-send { background: var(--orange); border: 1px solid var(--orange); color: #fff; border-radius: 8px; padding: 9px 18px; font-family: inherit; font-size: 12px; font-weight: 800; cursor: pointer; }
        .wd-send:hover { background: #b84114; }
        .wd-send:disabled { background: #e5e7eb; border-color: #e5e7eb; color: #94a3b8; cursor: not-allowed; }

        /* ── Historial ── */
        .section-head {
            display: flex;
            align-items: center;
            gap: 11px;
            flex-wrap: wrap;
            padding: 26px 0 12px;
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

        .table-wrap { padding: 0 0 28px; overflow-x: auto; }

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

            .report-body { max-width: none; padding: 0; }

            .no-print { display: none !important; }

            .cards { break-inside: avoid; }

            thead { display: table-header-group; }
            tfoot { display: table-row-group; }
            tr { break-inside: avoid; }
        }

        @media (max-width: 720px) {
            body { padding: 0 0 32px; }
            .report-body { padding: 0 12px; }
            .report-head { padding-left: 16px; padding-right: 16px; }
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

        <div class="report-body">
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

        <section class="wallet no-print" aria-label="Billetera del repartidor">
            <div>
                <div class="wallet-label">Saldo disponible</div>
                <div class="wallet-value" id="wd-balance">{{ $money($walletBalance) }}</div>
                <div class="wallet-sub">
                    Retiro mínimo {{ $money($minWithdrawal) }} · El saldo se descuenta al solicitar y se paga cuando el admin aprueba.
                </div>
            </div>
            <button type="button" class="wallet-btn" id="wd-open" onclick="fdOpenWithdraw()" @if ($walletBalance < $minWithdrawal) disabled @endif>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3v13m0 0-4-4m4 4 4-4"></path>
                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path>
                </svg>
                Retirar saldo
            </button>
        </section>

        <div class="section-head no-print">
            <span class="section-dot"></span>
            <h2 class="section-title">Retiros de saldo</h2>
            <span class="section-hint">{{ $withdrawals->count() }} solicitudes recientes</span>
        </div>

        <div class="table-wrap no-print">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="num">Monto</th>
                        <th>Estado</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($withdrawals as $withdrawal)
                        @php
                            $status = $withdrawal->status;
                            $statusLabel = $status === 'approved' ? 'Aprobado' : ($status === 'rejected' ? 'Rechazado' : 'Pendiente');
                        @endphp
                        <tr>
                            <td class="date">{{ $withdrawal->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="num money">{{ $money($withdrawal->amount) }}</td>
                            <td><span class="badge-status {{ $status }}">{{ $statusLabel }}</span></td>
                            <td class="muted">{{ $withdrawal->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty">Todavía no solicitaste retiros. Cuando lo hagas, el estado aparece acá.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="wd-overlay no-print" id="wd-overlay" onclick="if (event.target === this) fdCloseWithdraw()">
            <div class="wd-modal" role="dialog" aria-modal="true" aria-labelledby="wd-title">
                <div class="wd-head">
                    <h2 class="wd-title" id="wd-title">Retirar saldo</h2>
                    <p class="wd-sub">El monto queda reservado hasta que el admin confirme el pago.</p>
                </div>
                <div class="wd-body">
                    <div class="wd-field">
                        <label for="wd-amount">Monto a retirar</label>
                        <input type="number" id="wd-amount" min="{{ $minWithdrawal }}" step="0.01" placeholder="{{ $minWithdrawal }}" inputmode="decimal" />
                    </div>
                    <div class="wd-field">
                        <label for="wd-method">Método de pago</label>
                        <select id="wd-method">
                            <option value="transferencia">Transferencia</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="wd-field">
                        <label for="wd-notes">Notas (opcional)</label>
                        <textarea id="wd-notes" maxlength="500" placeholder="CBU, alias o referencia para el pago"></textarea>
                    </div>
                    <span class="wd-msg" id="wd-msg"></span>
                </div>
                <div class="wd-foot">
                    <button type="button" class="wd-cancel" onclick="fdCloseWithdraw()">Cancelar</button>
                    <button type="button" class="wd-send" id="wd-send" onclick="fdSubmitWithdraw()">Solicitar retiro</button>
                </div>
            </div>
        </div>

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
        </div>
    </main>

    <script>
        (function () {
            var MIN = Number({{ (float) $minWithdrawal }}) || 0;
            var ENDPOINT = '{{ url("/api/v1/delivery/wallet/withdraw") }}';

            function el(id) { return document.getElementById(id); }

            function setMsg(text, ok) {
                var box = el('wd-msg');
                if (!box) return;
                box.textContent = text || '';
                box.className = 'wd-msg' + (ok ? ' ok' : '');
            }

            window.fdOpenWithdraw = function () {
                var overlay = el('wd-overlay');
                if (!overlay) return;
                setMsg('', false);
                var amount = el('wd-amount');
                if (amount) amount.value = '';
                overlay.classList.add('open');
                if (amount) amount.focus();
            };

            window.fdCloseWithdraw = function () {
                var overlay = el('wd-overlay');
                if (overlay) overlay.classList.remove('open');
                setMsg('', false);
            };

            window.fdSubmitWithdraw = function () {
                var amountEl = el('wd-amount');
                var amount = parseFloat((amountEl && amountEl.value) || '0') || 0;

                if (amount <= 0) {
                    setMsg('Ingresá un monto válido para retirar.', false);
                    return;
                }

                if (amount < MIN) {
                    setMsg('El monto mínimo de retiro es ' + {{ \Illuminate\Support\Js::from(\App\Support\Money::format($minWithdrawal)) }} + '.', false);
                    return;
                }

                var button = el('wd-send');
                if (button) button.disabled = true;
                setMsg('', false);

                var meta = document.querySelector('meta[name="csrf-token"]');

                fetch(ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': meta ? meta.content : ''
                    },
                    body: JSON.stringify({
                        amount: amount,
                        method: (el('wd-method') || {}).value || null,
                        notes: (el('wd-notes') || {}).value || null
                    })
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            setMsg(data.message || 'Solicitud enviada.', true);
                            window.setTimeout(function () { window.location.reload(); }, 900);
                            return;
                        }

                        if (button) button.disabled = false;
                        setMsg((data && data.message) || 'No se pudo procesar el retiro.', false);
                    })
                    .catch(function () {
                        if (button) button.disabled = false;
                        setMsg('Error de conexión al solicitar el retiro.', false);
                    });
            };
        })();
    </script>
</body>
</html>
