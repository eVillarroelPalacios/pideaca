{{-- Reportes del negocio: cortes de lectura para Prestador y Cliente. --}}
@php
    $esPrestador = strcasecmp((string) ($user->typeUser?->description ?? ''), 'Prestador') === 0;

    // Tabs segun el rol: el Prestador ve los del comercio, el Cliente los suyos.
    $tabs = $esPrestador
        ? [
            ['key' => 'margins', 'label' => 'Rentabilidad', 'icon' => '<path d="M18 20V10M12 20V4M6 20v-6"/>'],
            ['key' => 'mrr', 'label' => 'Suscripciones y MRR', 'icon' => '<line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
            ['key' => 'commission', 'label' => 'Comisiones y ahorro', 'icon' => '<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>'],
            ['key' => 'ltv', 'label' => 'Clientes', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ['key' => 'campaigns', 'label' => 'Campañas WhatsApp', 'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
            ['key' => 'heatmap', 'label' => 'Demanda', 'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
            ['key' => 'forecast', 'label' => 'Abastecimiento', 'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>'],
        ]
        : [
            ['key' => 'savings', 'label' => 'Mi ahorro', 'icon' => '<path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><line x1="7" y1="7" x2="7.01" y2="7"/>'],
            ['key' => 'mysubs', 'label' => 'Suscripciones activas', 'icon' => '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>'],
            ['key' => 'history', 'label' => 'Histórico y hábitos', 'icon' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>'],
            ['key' => 'nutrition', 'label' => 'Resumen nutricional', 'icon' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>'],
        ];
@endphp
<section id="dash-reportes" class="dash-section" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">
                {{ $esPrestador ? 'Rentabilidad, clientes y operación de tu comercio' : 'Tu ahorro, tus suscripciones y tus hábitos de compra' }}
            </p>
        </div>
    </div>

    <div style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:16px;flex-wrap:wrap;">
        @foreach($tabs as $tab)
        <button type="button" class="fd-rep-tab{{ $loop->first ? ' active' : '' }}" data-rep-tab="{{ $tab['key'] }}"
            onclick="fdRepSwitch('{{ $tab['key'] }}')" title="{{ $tab['label'] }}"
            style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;font-size:13px;font-weight:600;color:{{ $loop->first ? '#D24C19' : '#64748b' }};background:{{ $loop->first ? 'linear-gradient(180deg,rgba(210,76,25,.06),transparent)' : 'transparent' }};border:none;border-bottom:2px solid {{ $loop->first ? '#D24C19' : 'transparent' }};margin-bottom:-2px;cursor:pointer;transition:color .15s ease,border-color .15s ease,background .15s ease;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $tab['icon'] !!}</svg>
            <span class="tab-label">{{ $tab['label'] }}</span>
        </button>
        @endforeach
    </div>

    @foreach($tabs as $tab)
    <div id="fd-rep-pane-{{ $tab['key'] }}" class="fd-rep-pane" style="{{ $loop->first ? '' : 'display:none;' }}">
        <div id="fd-rep-{{ $tab['key'] }}-loading" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:40px;text-align:center;">
            <p style="font-size:13px;color:#6b7280;margin:0;">Cargando reporte...</p>
        </div>
        <div id="fd-rep-{{ $tab['key'] }}-message" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
            <p id="fd-rep-{{ $tab['key'] }}-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudo cargar el reporte.</p>
        </div>
        <div id="fd-rep-{{ $tab['key'] }}-body" style="display:none;"></div>
        <div id="fd-rep-{{ $tab['key'] }}-print" style="display:none;text-align:right;margin-top:12px;">
            <button type="button" onclick="fdRepPrint('{{ $tab['key'] }}')" title="Imprimir o guardar el reporte como PDF"
                style="background:#D24C19;color:#fff;border:none;border-radius:4px;padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                PDF / Imprimir
            </button>
        </div>
    </div>
    @endforeach
</section>

<div id="fd-rep-print" style="display:none;"></div>

<style>
    #fd-rep-print { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    @media print {
        @page { margin: 12mm; }
        body * { visibility: hidden; }
        #fd-rep-print, #fd-rep-print * { visibility: visible; }
        #fd-rep-print { display: block !important; position: absolute; left: 0; top: 0; width: 100%; }
        #fd-rep-print button, #fd-rep-print .fd-rep-no-print { display: none !important; }
        #fd-rep-print thead { display: table-header-group; }
        #fd-rep-print tr { page-break-inside: avoid; }
        #fd-rep-print h3 { page-break-after: avoid; }
    }
</style>

<script>
(function () {
    var FD_REP_URLS = {
        margins: '{{ url("/api/v1/provider/reports/margins") }}',
        mrr: '{{ url("/api/v1/provider/reports/mrr") }}',
        commission: '{{ url("/api/v1/provider/reports/commission-savings") }}',
        ltv: '{{ url("/api/v1/provider/reports/ltv-risk") }}',
        campaigns: '{{ url("/api/v1/provider/reports/campaigns") }}',
        heatmap: '{{ url("/api/v1/provider/reports/demand-heatmap") }}',
        forecast: '{{ url("/api/v1/provider/reports/stock-forecast") }}',
        savings: '{{ url("/api/v1/customer/reports/savings") }}',
        mysubs: '{{ url("/api/v1/customer/reports/subscriptions") }}',
        history: '{{ url("/api/v1/customer/reports/order-history") }}',
        nutrition: '{{ url("/api/v1/customer/reports/nutritional") }}'
    };

    var FD_REP_FIRST = @json($tabs[0]['key'] ?? 'margins');
    var FD_REP_PRINT_USER = @json($user->name ?? '');
    var fdRepReady = false;
    var fdRepLoaded = {};

    var FD_REP_FREQ = { DAILY: 'cada día', WEEKLY: 'cada semana', BIWEEKLY: 'cada dos semanas', MONTHLY: 'cada mes' };
    var FD_REP_DAYS = { 1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves', 5: 'Viernes', 6: 'Sábado', 7: 'Domingo' };
    var FD_REP_RULE = {
        INACTIVE_CUSTOMER: 'Cliente inactivo',
        RECURRING_DAY_REMINDER: 'Recordatorio de entrega',
        WELCOME_BACK: 'Recuperación de cliente'
    };

    // ------------------------------------------------------------------
    // Helpers de pintado
    // ------------------------------------------------------------------

    function repKpis(items) {
        var html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px;">';
        items.forEach(function (k) {
            html += '<div style="background:#fff;border:1px solid #e5e7eb;padding:14px 16px;">'
                + '<p style="font-size:11px;color:#9ca3af;margin:0;text-transform:uppercase;letter-spacing:.4px;">' + escapeHtml(k.label) + '</p>'
                + '<p style="font-size:17px;font-weight:700;color:' + (k.color || '#0c2a4d') + ';margin:6px 0 0;">' + k.value + '</p>'
                + (k.hint ? '<p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">' + escapeHtml(k.hint) + '</p>' : '')
                + '</div>';
        });
        return html + '</div>';
    }

    function repTable(headers, rows, empty) {
        if (!rows.length) {
            return '<div style="background:#fff;border:1px dashed #d1d5db;padding:32px 24px;text-align:center;">'
                + '<p style="font-size:13px;color:#6b7280;margin:0;">' + escapeHtml(empty || 'Sin datos en el período.') + '</p></div>';
        }

        var html = '<div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">'
            + '<table style="width:100%;border-collapse:collapse;font-size:12px;">'
            + '<thead><tr>';
        headers.forEach(function (h) {
            html += '<th style="padding:9px 10px;text-align:left;background:#f8fafc;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e5e7eb;white-space:nowrap;">' + escapeHtml(h) + '</th>';
        });
        html += '</tr></thead><tbody>';
        rows.forEach(function (r) {
            html += '<tr>';
            r.forEach(function (c) {
                html += '<td style="padding:9px 10px;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:top;">' + c + '</td>';
            });
            html += '</tr>';
        });
        return html + '</tbody></table></div>';
    }

    function repDateRange(idPrefix, period) {
        return '<div class="fd-rep-no-print" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;background:#fff;border:1px solid #e5e7eb;padding:12px 14px;margin-bottom:14px;">'
            + '<div><label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Desde</label>'
            + '<input type="date" id="' + idPrefix + '-start" value="' + escapeHtml(period.start_date || '') + '" onchange="fdRepReload()" style="padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;" /></div>'
            + '<div><label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Hasta</label>'
            + '<input type="date" id="' + idPrefix + '-end" value="' + escapeHtml(period.end_date || '') + '" onchange="fdRepReload()" style="padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;" /></div>'
            + '<p style="font-size:11px;color:#9ca3af;margin:0;">Los campos vacíos usan el período por defecto.</p>'
            + '</div>';
    }

    // ------------------------------------------------------------------
    // Reportes del Prestador
    // ------------------------------------------------------------------

    function repMargins(d) {
        var s = d.summary || {};
        var lights = { green: ['#047857', '#d1fae5'], yellow: ['#b45309', '#fef3c7'], red: ['#b91c1c', '#fee2e2'], gray: ['#6b7280', '#f3f4f6'] };

        var html = repKpis([
            { label: 'Productos', value: Number(s.total_products || 0) },
            { label: 'Margen promedio', value: s.average_margin_percent === null ? '—' : s.average_margin_percent + ' %' },
            { label: 'En verde', value: Number(s.green || 0), color: '#047857', hint: 'Margen ≥ 35%' },
            { label: 'En amarillo', value: Number(s.yellow || 0), color: '#b45309', hint: 'Margen 20% - 34%' },
            { label: 'En rojo', value: Number(s.red || 0), color: '#b91c1c', hint: 'Margen < 20%' }
        ]);

        html += repTable(
            ['Producto', 'Precio', 'Costo', 'Margen $', 'Margen %', 'Semáforo', 'Recomendación'],
            (d.products || []).map(function (p) {
                var light = lights[p.traffic_light] || lights.gray;
                var rec = p.recommendation || {};
                var recTexto = escapeHtml(rec.note || '');
                if (rec.suggested_price) {
                    recTexto += '<br /><strong style="color:#0c2a4d;">Precio sugerido: ' + fdMoney(rec.suggested_price) + '</strong>';
                }

                return [
                    '<strong>' + escapeHtml(p.name) + '</strong>' + (p.has_recipe ? '' : '<br /><span style="font-size:10px;color:#9ca3af;">Sin ficha técnica</span>'),
                    fdMoney(p.sale_price),
                    fdMoney(p.production_cost),
                    p.gross_margin_amount === null ? '—' : fdMoney(p.gross_margin_amount),
                    p.gross_margin_percent === null ? '—' : p.gross_margin_percent + ' %',
                    fdBadge(p.traffic_light_label, light[0], light[1]),
                    '<span style="font-size:11px;color:#6b7280;">' + recTexto + '</span>'
                ];
            }),
            'Todavía no hay productos cargados en el catálogo.'
        );

        return html;
    }

    function repMrr(d) {
        var s = d.summary || {};

        var html = repKpis([
            { label: 'MRR actual', value: fdMoney(s.mrr), color: '#047857' },
            { label: 'Proyección 30 días', value: fdMoney(s.projection_30_days) },
            { label: 'Suscriptores activos', value: Number(s.active_subscribers || 0) },
            { label: 'Cancelados', value: Number(s.cancelled_subscribers || 0), hint: 'Churn ' + Number(s.churn_rate_percent || 0) + '%' },
            { label: 'Ticket promedio', value: fdMoney(s.average_ticket) },
            { label: 'Cobrado (' + Number(s.period_months || 1) + ' ms)', value: fdMoney(s.collected_in_period) }
        ]);

        html += repTable(
            ['Plan', 'Frecuencia', 'Suscriptores', 'Cargo por ciclo', 'MRR del plan'],
            (d.by_plan || []).map(function (p) {
                return [
                    '<strong>' + escapeHtml(p.title || 'Plan') + '</strong>',
                    escapeHtml(FD_REP_FREQ[p.frequency] || p.frequency || '—'),
                    Number(p.subscribers || 0),
                    fdMoney(p.charge_amount),
                    fdMoney(p.mrr)
                ];
            }),
            'Todavía no hay suscripciones activas. Publicá planes para empezar a facturar fijo.'
        );

        return html;
    }

    function repCommission(d) {
        var s = d.summary || {};

        var html = repDateRange('fd-rep-c', d.period || {});

        html += repKpis([
            { label: 'Volumen del período', value: fdMoney(s.gross_volume) },
            { label: 'Comisión PideAca (' + Number(s.pideaca_commission_percent || 0) + '%)', value: fdMoney(s.pideaca_commission_amount), color: '#D24C19' },
            { label: 'App tradicional (' + Number(s.traditional_min_percent || 0) + '%)', value: fdMoney(s.traditional_min_amount) },
            { label: 'App tradicional (' + Number(s.traditional_max_percent || 0) + '%)', value: fdMoney(s.traditional_max_amount) },
            { label: 'Tu ahorro vs. 25%', value: fdMoney(s.net_savings_vs_min), color: '#047857' },
            { label: 'Tu ahorro vs. 30%', value: fdMoney(s.net_savings_vs_max), color: '#047857' }
        ]);

        html += '<div style="background:#ecfdf5;border:1px solid #a7f3d0;padding:12px 14px;font-size:12px;color:#065f46;">'
            + escapeHtml(s.savings_note || '') + '</div>';

        return html;
    }

    function repLtv(d) {
        var s = d.summary || {};

        var html = repKpis([
            { label: 'Clientes con compras', value: Number(s.total_customers || 0) },
            { label: 'Clientes VIP', value: Number(s.vip_count || 0), color: '#047857' },
            { label: 'En riesgo / inactivos', value: Number(s.at_risk_count || 0), color: '#b91c1c', hint: 'Sin pedir hace ' + Number(s.inactive_days_threshold || 20) + '+ días' }
        ]);

        html += '<h3 style="font-size:14px;font-weight:700;color:#0c2a4d;margin:0 0 8px;">Clientes VIP (por LTV)</h3>';
        html += repTable(
            ['Cliente', 'Pedidos', 'LTV', 'Última compra', 'Días desde la última'],
            (d.vip_customers || []).map(function (c) {
                return [
                    '<strong>' + escapeHtml(c.name || '—') + '</strong><br /><span style="font-size:11px;color:#9ca3af;">' + escapeHtml(c.email || '') + '</span>',
                    Number(c.orders_count || 0),
                    '<strong>' + fdMoney(c.ltv) + '</strong>',
                    escapeHtml(c.last_order_at ? String(c.last_order_at).slice(0, 10) : '—'),
                    c.days_since_last_order === null ? '—' : Number(c.days_since_last_order) + ' días'
                ];
            }),
            'Todavía no hay clientes con pedidos.'
        );

        html += '<h3 style="font-size:14px;font-weight:700;color:#0c2a4d;margin:20px 0 8px;">Clientes en riesgo (≥ 2 compras, sin pedir reciente)</h3>';
        html += repTable(
            ['Cliente', 'Pedidos', 'LTV en riesgo', 'Última compra', 'Sin pedir'],
            (d.at_risk_customers || []).map(function (c) {
                return [
                    '<strong>' + escapeHtml(c.name || '—') + '</strong><br /><span style="font-size:11px;color:#9ca3af;">' + escapeHtml(c.email || '') + '</span>',
                    Number(c.orders_count || 0),
                    fdMoney(c.ltv),
                    escapeHtml(c.last_order_at ? String(c.last_order_at).slice(0, 10) : '—'),
                    '<span style="color:#b91c1c;font-weight:600;">' + Number(c.days_since_last_order || 0) + ' días</span>'
                ];
            }),
            'Ningún cliente está en riesgo. ¡Excelente!'
        );

        return html;
    }

    function repCampaigns(d) {
        var s = d.summary || {};

        var html = repKpis([
            { label: 'Mensajes enviados', value: Number(s.total_sent || 0) },
            { label: 'Fallidos', value: Number(s.total_failed || 0), color: '#b91c1c' },
            { label: 'Conversiones', value: Number(s.total_converted || 0), color: '#047857' },
            { label: 'Conversión global', value: Number(s.overall_conversion_rate_percent || 0) + ' %' },
            { label: 'Ventas atribuidas', value: fdMoney(s.attributed_sales_total), color: '#047857' }
        ]);

        html += repTable(
            ['Campaña', 'Cupón', 'Enviados', 'Fallidos', 'Convertidos', 'Conversión', 'Ventas'],
            (d.campaigns || []).map(function (c) {
                return [
                    '<strong>' + escapeHtml(FD_REP_RULE[c.rule_type] || c.rule_type || 'Automático') + '</strong>',
                    escapeHtml(c.discount_code || '—'),
                    Number(c.sent || 0),
                    Number(c.failed || 0),
                    Number(c.converted || 0),
                    Number(c.conversion_rate_percent || 0) + ' %',
                    fdMoney(c.attributed_sales)
                ];
            }),
            'Todavía no se enviaron mensajes de WhatsApp. Configurá las reglas en Retención.'
        );

        return html;
    }

    function repHeatmap(d) {
        var rows = d.heatmap || [];
        var s = d.summary || {};
        var max = 1;
        rows.forEach(function (r) {
            (r.hours || []).forEach(function (h) { if (h.total > max) max = h.total; });
        });

        var html = repKpis([
            { label: 'Pedidos del período', value: Number(s.total_orders || 0) },
            { label: 'Express', value: Number(s.express_orders || 0), color: '#D24C19' },
            { label: 'Por suscripción', value: Number(s.subscription_orders || 0), color: '#047857' },
            { label: 'Días analizados', value: Number((d.period || {}).days || 0) }
        ]);

        html += '<div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">'
            + '<table style="width:100%;border-collapse:collapse;font-size:10px;">'
            + '<thead><tr><th style="padding:8px;text-align:left;background:#f8fafc;color:#64748b;border-bottom:1px solid #e5e7eb;position:sticky;left:0;background:#f8fafc;">Día</th>';

        for (var h = 0; h < 24; h++) {
            html += '<th style="padding:6px 2px;text-align:center;background:#f8fafc;color:#94a3b8;border-bottom:1px solid #e5e7eb;">' + (h < 10 ? '0' + h : h) + '</th>';
        }
        html += '</tr></thead><tbody>';

        rows.forEach(function (row) {
            html += '<tr><td style="padding:6px 8px;white-space:nowrap;color:#334155;font-weight:600;border-bottom:1px solid #f1f5f9;position:sticky;left:0;background:#fff;">' + escapeHtml(row.day_name) + '</td>';
            (row.hours || []).forEach(function (cell) {
                var alpha = cell.total ? Math.max(0.12, cell.total / max) : 0;
                var bg = alpha ? 'rgba(210,76,25,' + alpha.toFixed(2) + ')' : '#fff';
                var fg = alpha > 0.55 ? '#fff' : '#94a3b8';
                var title = row.day_name + ' ' + (cell.hour < 10 ? '0' + cell.hour : cell.hour) + ':00 — '
                    + cell.total + ' pedidos (Express ' + cell.express + ' · Suscripciones ' + cell.subscriptions + ') · ' + fdMoney(cell.revenue);
                html += '<td title="' + escapeHtml(title) + '" style="padding:6px 2px;text-align:center;background:' + bg + ';color:' + fg
                    + ';border:1px solid #f1f5f9;font-weight:' + (cell.total ? '700' : '400') + ';">' + (cell.total || '') + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>'
            + '<p style="font-size:11px;color:#9ca3af;margin:8px 0 0;">Matriz de 7 días × 24 horas. A más pedidos, más intenso el naranja. Pasá el mouse sobre una celda para el detalle.</p>';

        return html;
    }

    function repForecast(d) {
        var s = d.summary || {};

        var html = repDateRange('fd-rep-f', d.period || {});

        html += repKpis([
            { label: 'Suscripciones activas', value: Number(s.active_subscriptions || 0) },
            { label: 'Entregas programadas', value: Number(s.total_deliveries || 0), color: '#D24C19' },
            { label: 'Platos distintos', value: Number(s.total_products || 0) },
            { label: 'Insumos distintos', value: Number(s.total_supplies || 0) },
            { label: 'Costo estimado de insumos', value: fdMoney(s.estimated_supplies_cost), color: '#b45309' }
        ]);

        html += '<h3 style="font-size:14px;font-weight:700;color:#0c2a4d;margin:0 0 8px;">Platos a producir</h3>';
        html += repTable(
            ['Producto', 'Cantidad total', 'Suscripciones que lo incluyen'],
            (d.products || []).map(function (p) {
                return [
                    '<strong>' + escapeHtml(p.name) + '</strong>',
                    Number(p.quantity || 0),
                    Number(p.subscriptions || 0)
                ];
            }),
            'Ninguna suscripción cae en el período seleccionado.'
        );

        html += '<h3 style="font-size:14px;font-weight:700;color:#0c2a4d;margin:20px 0 8px;">Insumos necesarios</h3>';
        html += repTable(
            ['Insumo', 'Cantidad', 'Costo unitario', 'Costo estimado'],
            (d.supplies || []).map(function (sp) {
                return [
                    '<strong>' + escapeHtml(sp.name) + '</strong>' + (sp.unit_symbol ? ' <span style="font-size:10px;color:#9ca3af;">(' + escapeHtml(sp.unit_symbol) + ')</span>' : ''),
                    Number(sp.quantity_required || 0),
                    fdMoney(sp.cost_per_unit),
                    '<strong>' + fdMoney(sp.estimated_cost) + '</strong>'
                ];
            }),
            'Sin insumos que abastecer en el período.'
        );

        return html;
    }

    // ------------------------------------------------------------------
    // Reportes del Cliente
    // ------------------------------------------------------------------

    function repSavings(d) {
        var s = d.summary || {};

        var html = repKpis([
            { label: 'Total ahorrado', value: fdMoney(s.total_saved), color: '#047857', hint: 'Vs. comprar por separado' },
            { label: 'Por suscripciones', value: fdMoney(s.saved_with_subscriptions) },
            { label: 'Por promociones', value: fdMoney(s.saved_with_promotions) },
            { label: 'Ciclos suscriptos pagados', value: Number(s.paid_subscription_cycles || 0) },
            { label: 'Pedidos con descuento', value: Number(s.orders_with_discount || 0) }
        ]);

        html += '<div style="background:#ecfdf5;border:1px solid #a7f3d0;padding:12px 14px;font-size:12px;color:#065f46;margin-bottom:14px;">'
            + 'Comprar con suscripción y promociones te dejó <strong>' + fdMoney(s.total_saved) + '</strong> frente a los precios Express del mismo carrito.'
            + '</div>';

        html += repTable(
            ['Suscripción', 'Comercio'],
            (d.by_subscription || []).map(function (sub) {
                return ['<strong>' + escapeHtml(sub.plan_title || 'Plan') + '</strong>', escapeHtml(sub.provider || '—')];
            }),
            'Todavía no tenés suscripciones generando ahorro.'
        );

        return html;
    }

    function repMySubs(d) {
        var s = d.summary || {};

        var html = repKpis([
            { label: 'Activas', value: Number(s.active_count || 0), color: '#047857' },
            { label: 'Pausadas', value: Number(s.paused_count || 0), color: '#b45309' },
            { label: 'Compromiso mensual', value: fdMoney(s.monthly_commitment), color: '#D24C19' }
        ]);

        var subs = d.subscriptions || [];

        if (!subs.length) {
            return html + '<div style="background:#fff;border:1px dashed #d1d5db;padding:32px 24px;text-align:center;">'
                + '<p style="font-size:13px;color:#6b7280;margin:0;">No tenés suscripciones activas o pausadas. Armá un plan desde Comercios.</p></div>';
        }

        subs.forEach(function (sub) {
            var esActiva = sub.status === 'ACTIVE';
            var badge = fdBadge(sub.status_label || sub.status, esActiva ? '#047857' : '#b45309', esActiva ? '#d1fae5' : '#fef3c7');
            var proximo = sub.next_charge
                ? 'Cobro ' + fdMoney(sub.next_charge.total) + ' el ' + escapeHtml(sub.next_charge.period_start || '—')
                : 'Sin cobro pendiente';

            html += '<div style="background:#fff;border:1px solid #e5e7eb;border-left:3px solid ' + (esActiva ? '#D24C19' : '#f59e0b') + ';padding:16px 18px;margin-bottom:12px;">'
                + '<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:8px;">'
                + '<div><strong style="font-size:14px;color:#0c2a4d;">' + escapeHtml(sub.plan_title || 'Plan') + '</strong>'
                + '<span style="font-size:12px;color:#6b7280;margin-left:8px;">' + escapeHtml(sub.provider || '') + '</span></div>'
                + '<span style="display:flex;gap:6px;align-items:center;">' + badge
                + '<span style="font-size:13px;font-weight:700;color:#0c2a4d;">' + fdMoney(sub.charge_amount) + '</span>'
                + '<span style="font-size:11px;color:#6b7280;">' + escapeHtml(FD_REP_FREQ[sub.frequency] || '') + '</span></span>'
                + '</div>'
                + '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;font-size:12px;color:#374151;">'
                + '<span><span style="color:#9ca3af;">Próxima entrega:</span> <strong>' + escapeHtml(sub.next_delivery_date || '—') + '</strong></span>'
                + '<span><span style="color:#9ca3af;">Día preferido:</span> ' + escapeHtml(FD_REP_DAYS[sub.preferred_delivery_day] || 'Sin preferencia') + '</span>'
                + '<span><span style="color:#9ca3af;">Horario:</span> ' + escapeHtml(sub.preferred_delivery_time || 'Sin preferencia') + '</span>'
                + '<span><span style="color:#9ca3af;">Pago:</span> ' + escapeHtml(sub.payment_method === 'cash' ? 'Efectivo' : (sub.payment_method || '—')) + '</span>'
                + '<span><span style="color:#9ca3af;">Cobro:</span> ' + proximo + '</span>'
                + '</div>'
                + (sub.products && sub.products.length
                    ? '<p style="font-size:12px;color:#475569;margin:10px 0 0;"><span style="color:#9ca3af;">Productos:</span> '
                        + sub.products.map(function (p) { return escapeHtml(p.name || '') + ' x' + Number(p.quantity || 0); }).join(' · ')
                        + '</p>'
                    : '')
                + '</div>';
        });

        return html;
    }

    function repHistory(d) {
        var s = d.summary || {};

        var html = repKpis([
            { label: 'Pedidos realizados', value: Number(s.total_orders || 0) },
            { label: 'Total gastado', value: fdMoney(s.total_spent) },
            { label: 'Tu plato favorito', value: escapeHtml(s.favorite_dish || '—'), hint: 'El que más pediste' },
            { label: 'Tu comercio favorito', value: escapeHtml(s.favorite_provider || '—') }
        ]);

        html += '<h3 style="font-size:14px;font-weight:700;color:#0c2a4d;margin:0 0 8px;">Platos más pedidos <span style="font-size:11px;font-weight:500;color:#9ca3af;">(reordenables en 1 clic)</span></h3>';
        html += repTable(
            ['Plato', 'Veces pedido', 'Unidades', 'Gastado', 'Última vez', ''],
            (d.top_products || []).map(function (p) {
                return [
                    '<strong>' + escapeHtml(p.name || '—') + '</strong>',
                    Number(p.times_ordered || 0),
                    Number(p.total_quantity || 0),
                    fdMoney(p.total_spent),
                    escapeHtml(p.last_ordered_at ? String(p.last_ordered_at).slice(0, 10) : '—'),
                    p.can_reorder_1_click ? fdBadge('Reordenar 1-click', '#D24C19', '#fff1eb') : ''
                ];
            }),
            'Todavía no tenés pedidos en tu historial.'
        );

        html += '<h3 style="font-size:14px;font-weight:700;color:#0c2a4d;margin:20px 0 8px;">Comercios más frecuentes</h3>';
        html += repTable(
            ['Comercio', 'Pedidos', 'Gastado', 'Último pedido', ''],
            (d.top_providers || []).map(function (p) {
                return [
                    '<strong>' + escapeHtml(p.business_name || '—') + '</strong>',
                    Number(p.orders_count || 0),
                    fdMoney(p.total_spent),
                    escapeHtml(p.last_order_at ? String(p.last_order_at).slice(0, 10) : '—'),
                    p.can_reorder_1_click
                        ? '<button type="button" onclick="openFdShop(' + Number(p.provider_id) + ')" title="Reordenar en 1 clic" aria-label="Reordenar en 1 clic" style="background:#D24C19;color:#fff;border:none;border-radius:4px;width:30px;height:30px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></button>'
                        : ''
                ];
            }),
            'Todavía no tenés comercios frecuentes.'
        );

        return html;
    }

    function repNutrition(d) {
        var s = d.summary || {};
        var period = d.period || {};
        var mesActual = period.month || new Date().toISOString().slice(0, 7);

        var html = '<div class="fd-rep-no-print" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;background:#fff;border:1px solid #e5e7eb;padding:12px 14px;margin-bottom:14px;">'
            + '<div><label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Mes</label>'
            + '<input type="month" id="fd-rep-n-month" value="' + escapeHtml(mesActual) + '" onchange="fdRepReload()" style="padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;" /></div>'
            + '</div>';

        html += repKpis([
            { label: 'Pedidos entregados', value: Number(s.total_delivered_orders || 0) },
            { label: 'Comidas recibidas', value: Number(s.total_meals || 0), color: '#D24C19' },
            { label: 'Gastado en el mes', value: fdMoney(s.total_spent) },
            { label: 'Categoría principal', value: escapeHtml(s.main_category || '—'), color: '#047857' }
        ]);

        html += repTable(
            ['Categoría', 'Comidas', 'Unidades', 'Gastado', 'Más pedidos'],
            (d.categories || []).map(function (c) {
                var top = (c.top_products || []).map(function (p) {
                    return escapeHtml(p.name) + ' x' + Number(p.quantity || 0);
                }).join(' · ');

                return [
                    '<strong>' + escapeHtml(c.category_name || '—') + '</strong>',
                    Number(c.meals_count || 0),
                    Number(c.total_quantity || 0),
                    fdMoney(c.total_spent),
                    '<span style="font-size:11px;color:#6b7280;">' + (top || '—') + '</span>'
                ];
            }),
            'Todavía no recibiste comidas en el mes seleccionado.'
        );

        return html;
    }

    var FD_REP_RENDER = {
        margins: repMargins,
        mrr: repMrr,
        commission: repCommission,
        ltv: repLtv,
        campaigns: repCampaigns,
        heatmap: repHeatmap,
        forecast: repForecast,
        savings: repSavings,
        mysubs: repMySubs,
        history: repHistory,
        nutrition: repNutrition
    };

    // ------------------------------------------------------------------
    // Estados, carga y tabs
    // ------------------------------------------------------------------

    var fdRepActive = FD_REP_FIRST;

    function repState(key, state, text) {
        ['loading', 'message', 'body'].forEach(function (name) {
            var el = document.getElementById('fd-rep-' + key + '-' + name);
            if (el) el.style.display = (name === state) ? 'block' : 'none';
        });

        var printBar = document.getElementById('fd-rep-' + key + '-print');
        if (printBar) printBar.style.display = (state === 'body') ? 'block' : 'none';

        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-rep-' + key + '-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function repQuery(key) {
        var params = [];

        if (key === 'commission') {
            var cStart = document.getElementById('fd-rep-c-start');
            var cEnd = document.getElementById('fd-rep-c-end');
            if (cStart && cStart.value) params.push('start_date=' + cStart.value);
            if (cEnd && cEnd.value) params.push('end_date=' + cEnd.value);
        } else if (key === 'forecast') {
            var fStart = document.getElementById('fd-rep-f-start');
            var fEnd = document.getElementById('fd-rep-f-end');
            if (fStart && fStart.value) params.push('start_date=' + fStart.value);
            if (fEnd && fEnd.value) params.push('end_date=' + fEnd.value);
        } else if (key === 'nutrition') {
            var month = document.getElementById('fd-rep-n-month');
            if (month && month.value) params.push('month=' + month.value);
        }

        return params.length ? '?' + params.join('&') : '';
    }

    function repLoad(key) {
        repState(key, 'loading');

        fdFetchJson(FD_REP_URLS[key] + repQuery(key))
            .then(function (res) {
                if (!res.ok) {
                    repState(key, 'message', (res.data && res.data.message) || 'No se pudo cargar el reporte.');
                    return;
                }

                var render = FD_REP_RENDER[key];
                var body = document.getElementById('fd-rep-' + key + '-body');
                if (body && render) body.innerHTML = render(res.data);

                fdRepLoaded[key] = true;
                repState(key, 'body');
            })
            .catch(function () {
                repState(key, 'message', 'No se pudo conectar con el servidor.');
            });
    }

    function fdRepSwitch(key) {
        fdRepActive = key;

        document.querySelectorAll('.fd-rep-tab').forEach(function (btn) {
            var on = btn.getAttribute('data-rep-tab') === key;
            btn.style.color = on ? '#D24C19' : '#64748b';
            btn.style.borderBottomColor = on ? '#D24C19' : 'transparent';
            btn.style.background = on ? 'linear-gradient(180deg,rgba(210,76,25,.06),transparent)' : 'transparent';
            btn.classList.toggle('active', on);
        });

        document.querySelectorAll('.fd-rep-pane').forEach(function (pane) {
            pane.style.display = pane.id === 'fd-rep-pane-' + key ? 'block' : 'none';
        });

        if (!fdRepLoaded[key]) repLoad(key);
    }

    /**
     * Recarga el reporte activo (al cambiar fechas o mes en los filtros).
     */
    function fdRepReload() {
        fdRepLoaded[fdRepActive] = false;
        repLoad(fdRepActive);
    }

    /**
     * Filtros aplicados al reporte, para incluirlos en la cabecera impresa.
     */
    function repPrintFilters(key) {
        if (key === 'commission' || key === 'forecast') {
            var prefix = key === 'commission' ? 'fd-rep-c' : 'fd-rep-f';
            var startEl = document.getElementById(prefix + '-start');
            var endEl = document.getElementById(prefix + '-end');
            var start = startEl && startEl.value ? startEl.value : '';
            var end = endEl && endEl.value ? endEl.value : '';
            if (start || end) return 'Período: ' + (start || 'inicio') + ' a ' + (end || 'hoy');
        }

        if (key === 'nutrition') {
            var monthEl = document.getElementById('fd-rep-n-month');
            if (monthEl && monthEl.value) return 'Mes: ' + monthEl.value;
        }

        return '';
    }

    /**
     * Prepara el reporte activo en el contenedor de impresión y abre el
     * diálogo del navegador (Guardar como PDF / imprimir en papel).
     */
    function fdRepPrint(key) {
        var body = document.getElementById('fd-rep-' + key + '-body');
        var root = document.getElementById('fd-rep-print');
        if (!body || !root || !fdRepLoaded[key]) return;

        var tabBtn = document.querySelector('.fd-rep-tab[data-rep-tab="' + key + '"]');
        var title = tabBtn ? (tabBtn.getAttribute('title') || tabBtn.textContent.trim() || 'Reporte') : 'Reporte';
        var filters = repPrintFilters(key);
        var fecha = '';
        try {
            fecha = new Date().toLocaleDateString('es-AR', { day: '2-digit', month: 'long', year: 'numeric' });
        } catch (e) {
            fecha = new Date().toISOString().slice(0, 10);
        }

        root.innerHTML =
            '<div style="border-bottom:2px solid #D24C19;padding-bottom:10px;margin-bottom:16px;">'
            + '<p style="font-size:10px;font-weight:700;color:#D24C19;margin:0;letter-spacing:.8px;text-transform:uppercase;">PideAca · Reportes</p>'
            + '<h1 style="font-size:20px;font-weight:700;color:#0c2a4d;margin:6px 0 4px;">' + escapeHtml(title) + '</h1>'
            + '<p style="font-size:11px;color:#6b7280;margin:0;">'
            + escapeHtml(FD_REP_PRINT_USER)
            + (FD_REP_PRINT_USER ? ' · ' : '')
            + 'Generado el ' + escapeHtml(fecha)
            + (filters ? ' · ' + escapeHtml(filters) : '')
            + '</p>'
            + '</div>'
            + '<div>' + body.innerHTML + '</div>';

        setTimeout(function () { window.print(); }, 50);
    }

    window.addEventListener('afterprint', function () {
        var root = document.getElementById('fd-rep-print');
        if (root) root.innerHTML = '';
    });

    /**
     * Al entrar a la sección se pinta el primer tab. Las recargas manuales no
     * existen: cada cambio de filtro ya refresca su propio reporte.
     */
    function fdRepOpen() {
        if (fdRepReady) return;
        fdRepReady = true;
        fdRepSwitch(FD_REP_FIRST);
    }

    window.fdRepSwitch = fdRepSwitch;
    window.fdRepReload = fdRepReload;
    window.fdRepPrint = fdRepPrint;
    window.fdRepOpen = fdRepOpen;
})();
</script>
