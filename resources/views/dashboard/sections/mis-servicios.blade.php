<section id="dash-mis-servicios" class="dash-section" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p id="fd-mis-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">Tus solicitudes, citas y presupuestos</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <select id="fd-mis-filter" onchange="window.fdMisLoad()" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;">
                <option value="">Todos los estados</option>
            </select>
            <select id="fd-mis-type" onchange="window.fdMisLoad()" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;">
                <option value="">Todos los tipos</option>
                <option value="EMERGENCY">Urgencia 24/7</option>
                <option value="SCHEDULED">Cita programada</option>
                <option value="QUOTE">Presupuesto</option>
            </select>
        </div>
    </div>

    <div id="fd-mis-tabs" style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:14px;flex-wrap:wrap;">
        <button type="button" id="fd-mis-tab-today" onclick="window.fdMisTab('today')" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:color .15s ease,border-color .15s ease,background .15s ease;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
            <span class="tab-label">Servicios de hoy</span>
            <span id="fd-mis-count-today" style="background:#D24C19;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
        </button>
        <button type="button" id="fd-mis-tab-history" onclick="window.fdMisTab('history')" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:color .15s ease,border-color .15s ease,background .15s ease;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span class="tab-label">Historial</span>
            <span id="fd-mis-count-history" style="background:#6b7280;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
        </button>

        <div id="fd-mis-range" style="display:none;gap:6px;align-items:center;flex-wrap:wrap;margin-left:auto;padding-bottom:6px;">
            <label for="fd-mis-date-from" style="font-size:11px;color:#6b7280;">Desde</label>
            <input type="date" id="fd-mis-date-from" onchange="window.fdMisRangeChange()" style="padding:6px 8px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;color:#374151;">
            <label for="fd-mis-date-to" style="font-size:11px;color:#6b7280;">Hasta</label>
            <input type="date" id="fd-mis-date-to" onchange="window.fdMisRangeChange()" style="padding:6px 8px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;color:#374151;">
            <button type="button" onclick="window.fdMisClearRange()" title="Limpiar rango de fechas" aria-label="Limpiar rango de fechas" style="background:#fff;color:#6b7280;border:1px solid #d1d5db;padding:6px 8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <div id="fd-mis-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
        <p style="font-size:13px;color:#6b7280;margin:0;">Cargando tus servicios...</p>
    </div>

    <div id="fd-mis-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
        <p id="fd-mis-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudieron cargar tus servicios.</p>
    </div>

    <div id="fd-mis-list" style="display:none;"></div>

    <div id="fd-mis-pagination" style="display:none;justify-content:center;align-items:center;gap:10px;margin-top:14px;"></div>
</section>

<script>
(function () {
    var sec = document.getElementById('dash-mis-servicios');
    if (!sec) return;

    var STATUS_LABEL = {
        PENDIENTE: 'Pendiente',
        PRESUPUESTADO: 'Presupuestado',
        EN_PROCESO: 'En proceso',
        COMPLETADO: 'Completado',
        CANCELADO: 'Cancelado'
    };
    var STATUS_COLOR = {
        PENDIENTE: '#b45309',
        PRESUPUESTADO: '#6d28d9',
        EN_PROCESO: '#0369a1',
        COMPLETADO: '#047857',
        CANCELADO: '#b91c1c'
    };
    var TYPE_LABEL = { EMERGENCY: 'Urgencia 24/7', SCHEDULED: 'Cita programada', QUOTE: 'Presupuesto' };
    var TYPE_COLOR = { EMERGENCY: '#dc2626', SCHEDULED: '#1d4ed8', QUOTE: '#6d28d9' };
    var ESCROW_LABEL = { HOLDING: 'Retenido en garantía', RELEASED: 'Pago liberado', REFUNDED: 'Pago devuelto' };
    var ESCROW_COLOR = { HOLDING: '#b45309', RELEASED: '#047857', REFUNDED: '#64748b' };
    var PATH = {
        'accept-quote': 'accept-quote',
        'reject-quote': 'reject-quote',
        'approve-estimate': 'approve-onsite-estimate',
        'cancel': 'cancel',
        'release': 'confirm-release',
        'rate': 'rate'
    };

    var MIS_PER_PAGE = 5;
    var MIS_ACTIVE = 'today';
    var MIS_PAGE = 1;
    var MIS_ALL = [];
    var MIS_LAST_HTML = null;
    var lastPayload = null;

    function esc(value) {
        if (window.escapeHtml) return window.escapeHtml(value);
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function money(value) {
        if (window.fdMoney) return window.fdMoney(value);
        var n = Number(value || 0);
        return '$ ' + n.toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function dateText(iso) {
        if (!iso) return '';
        var d = new Date(String(iso).replace(' ', 'T'));
        return isNaN(d.getTime()) ? '' : d.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
    }

    function clock() {
        if (window.fdClock) return window.fdClock();
        return new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function show(id, on) {
        var el = document.getElementById(id);
        if (el) el.style.display = on ? 'block' : 'none';
    }

    function state(name, text) {
        var map = { loading: 'fd-mis-loading', message: 'fd-mis-message', content: 'fd-mis-list' };
        Object.keys(map).forEach(function (k) {
            show(map[k], k === name);
        });
        if (name === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-mis-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function toast(text, ok) {
        if (window.fdToast) {
            window.fdToast(text, !ok);
            return;
        }
        alert(text);
    }

    function badge(label, color) {
        if (!label) return '';
        if (window.fdBadge) return window.fdBadge(label, color, color + '1a');
        return '<span style="display:inline-block;font-size:11px;font-weight:700;color:' + color + ';background:' + color + '1a;border-radius:9999px;padding:2px 9px;">' + esc(label) + '</span>';
    }

    function totalRow(label, value, strong) {
        if (window.fdTotalRow) return window.fdTotalRow(label, value, !!strong);
        return '<div style="display:flex;justify-content:space-between;color:#64748b;' + (strong ? 'font-weight:700;color:#0c2a4d;' : '') + '">'
            + '<span>' + esc(label) + '</span><span>' + value + '</span></div>';
    }

    function isToday(o) {
        var d = new Date(String((o && o.created_at) || '').replace(' ', 'T'));
        if (isNaN(d.getTime())) return false;

        var now = new Date();
        return d.getFullYear() === now.getFullYear()
            && d.getMonth() === now.getMonth()
            && d.getDate() === now.getDate();
    }

    function splitGroups() {
        var groups = { today: [], history: [] };
        MIS_ALL.forEach(function (o) {
            groups[isToday(o) ? 'today' : 'history'].push(o);
        });
        return groups;
    }

    function paintTabs(groups) {
        ['today', 'history'].forEach(function (key) {
            var count = groups[key].length;

            var badgeEl = document.getElementById('fd-mis-count-' + key);
            if (badgeEl) {
                badgeEl.textContent = count;
                badgeEl.style.display = count ? 'inline-flex' : 'none';
            }

            var btn = document.getElementById('fd-mis-tab-' + key);
            if (btn) {
                var on = MIS_ACTIVE === key;
                btn.style.color = on ? '#D24C19' : '#64748b';
                btn.style.borderBottomColor = on ? '#D24C19' : 'transparent';
                btn.style.background = on ? 'linear-gradient(180deg,rgba(210,76,25,.06),transparent)' : 'none';
            }
        });

        var range = document.getElementById('fd-mis-range');
        if (range) range.style.display = MIS_ACTIVE === 'history' ? 'flex' : 'none';
    }

    function paintSummary(groups) {
        var summary = document.getElementById('fd-mis-summary');
        if (!summary) return;

        var pendientes = MIS_ALL.filter(function (o) {
            return o.status !== 'COMPLETED' && o.status !== 'CANCELLED';
        }).length;

        summary.textContent = (MIS_ALL.length
            ? groups.today.length + ' de hoy · ' + groups.history.length + ' en historial'
                + (pendientes ? ' · ' + pendientes + ' pendientes' : '')
            : 'Tus solicitudes, citas y presupuestos')
            + ' · actualizado ' + clock();
    }

    function paintPagination(totalPages) {
        var pag = document.getElementById('fd-mis-pagination');
        if (!pag) return;

        if (MIS_ACTIVE !== 'history' || totalPages <= 1) {
            pag.style.display = 'none';
            pag.innerHTML = '';
            return;
        }

        var page = MIS_PAGE;
        var dots = '';
        for (var p = 1; p <= totalPages; p++) {
            dots += '<span onclick="window.fdMisGoPage(' + p + ')" style="padding:2px 6px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;color:'
                + (p === page ? '#D24C19;' : '#9ca3af;') + '" onmouseover="if(' + p + '!==' + page + ')this.style.color=\'#D24C19\'" onmouseout="if(' + p + '!==' + page + ')this.style.color=\'#9ca3af\'">'
                + p + '</span>';
        }

        var prevDisabled = page <= 1;
        var nextDisabled = page >= totalPages;
        var navStyle = 'background:white;border:1px solid #d1d5db;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;color:#374151;display:inline-flex;align-items:center;justify-content:center;line-height:0;transition:all 0.2s ease;opacity:';

        pag.style.display = 'flex';
        pag.innerHTML = '<button type="button" onclick="window.fdMisGoPage(' + (page - 1) + ')" title="Página anterior" aria-label="Página anterior" '
            + 'style="' + navStyle + (prevDisabled ? '0.4;' : '1;') + 'pointer-events:' + (prevDisabled ? 'none;' : 'auto;') + '">&#10094;</button>'
            + dots
            + '<button type="button" onclick="window.fdMisGoPage(' + (page + 1) + ')" title="Página siguiente" aria-label="Página siguiente" '
            + 'style="' + navStyle + (nextDisabled ? '0.4;' : '1;') + 'pointer-events:' + (nextDisabled ? 'none;' : 'auto;') + '">&#10095;</button>';
    }

    function actionsFor(o) {
        var list = [];
        var propuestaPendiente = o.order_type === 'QUOTE' && o.status === 'QUOTED'
            && o.quote && o.quote.status === 'PENDING' && parseFloat(o.quote.proposed_price || 0) > 0;
        var estimadoPendiente = o.order_type === 'EMERGENCY' && o.status === 'QUOTED'
            && parseFloat(o.additional_cost || 0) > 0 && o.escrow_status === 'HOLDING';

        if (propuestaPendiente) {
            list.push({ act: 'accept-quote', label: 'Aceptar propuesta', title: 'Aceptar propuesta', bg: '#047857', fg: '#fff', border: 'none' });
            list.push({ act: 'reject-quote', label: 'Rechazar propuesta', title: 'Rechazar propuesta', bg: '#fff', fg: '#b91c1c', border: '1px solid #fca5a5' });
        }
        if (estimadoPendiente) {
            list.push({ act: 'approve-estimate', label: 'Aprobar costo adicional', title: 'Aprobar costo adicional', bg: '#047857', fg: '#fff', border: 'none' });
        }
        if (o.cancellable) {
            list.push({
                act: 'cancel',
                label: '',
                title: 'Cancelar solicitud',
                bg: '#fff',
                fg: '#b91c1c',
                border: '1px solid #fca5a5',
                icon: '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>'
            });
        }
        if (o.status === 'COMPLETED' && o.escrow_status === 'HOLDING') {
            list.push({ act: 'release', label: 'Confirmar y liberar pago', title: 'Confirmar y liberar pago', bg: '#0c2a4d', fg: '#fff', border: 'none' });
        }
        if (o.status === 'COMPLETED' && !o.customer_rated) {
            list.push({ act: 'rate', label: 'Calificar al prestador', title: 'Calificar al prestador', bg: '#D24C19', fg: '#fff', border: 'none' });
        }
        return list;
    }

    var MIS_ICONS = {
        // Pendiente: cliente esperando
        pendiente: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/><path d="M4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>',
        // Presupuestado: documento
        presupuestado: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
        // En proceso: camión en camino / trabajo en curso
        en_proceso: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        // Completado: casa
        completado: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>'
    };

    var MIS_STEPS = [
        ['PENDIENTE', 'Pendiente', MIS_ICONS.pendiente],
        ['PRESUPUESTADO', 'Presupuestado', MIS_ICONS.presupuestado],
        ['EN_PROCESO', 'En proceso', MIS_ICONS.en_proceso],
        ['COMPLETADO', 'Completado', MIS_ICONS.completado]
    ];

    function stepsFor(o) {
        var steps = MIS_STEPS.slice();
        // Aceptado tambien cuenta como presupuesto cerrado (urgencias/citas
        // aceptadas sin propuesta formal).
        var cotizada = o.status === 'QUOTED'
            || o.status === 'ACCEPTED'
            || !!o.quote
            || (o.proposals || []).length > 0
            || parseFloat(o.additional_cost || 0) > 0;

        // Sin cotizacion en la historia del servicio, ese paso no se muestra.
        if (!cotizada) {
            steps = steps.filter(function (s) { return s[0] !== 'PRESUPUESTADO'; });
        }
        return steps;
    }

    function trackHtml(o) {
        var display = o.display_status || o.status;

        if (o.status === 'CANCELLED' || display === 'CANCELADO') {
            return '<div style="background:#fef2f2;border-bottom:1px solid #fecaca;padding:12px 16px;font-size:13px;color:#b91c1c;font-weight:700;">Servicio cancelado</div>';
        }

        var steps = stepsFor(o);
        var current = -1;
        steps.forEach(function (step, i) { if (step[0] === display) current = i; });
        if (current < 0) return '';

        var html = '<div style="display:flex;align-items:flex-start;padding:16px 16px 8px;overflow-x:auto;">';

        steps.forEach(function (step, i) {
            var done = i < current;
            var active = i === current;
            var passed = done || active;
            var circleBg = passed ? '#D24C19' : '#e2e8f0';
            var circleBorder = passed ? '#D24C19' : '#e2e8f0';
            var iconColor = passed ? '#fff' : '#64748b';
            var labelColor = active ? '#D24C19' : (done ? '#334155' : '#94a3b8');
            var ring = active ? 'box-shadow:0 0 0 4px #ffe9df;' : '';

            html += '<div style="display:flex;flex-direction:column;align-items:center;width:72px;flex:none;">'
                + '<div style="height:14px;line-height:14px;font-size:9px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#D24C19;">'
                + (active ? 'Ahora' : '') + '</div>'
                + '<div style="width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:' + circleBg + ';border:1px solid ' + circleBorder + ';' + ring + '">'
                + '<span style="display:flex;line-height:0;color:' + iconColor + ';">' + step[2] + '</span>'
                + '</div>'
                + '<div style="margin-top:7px;font-size:10px;line-height:1.25;text-align:center;color:' + labelColor + ';font-weight:' + (active ? '700' : (done ? '600' : '500')) + ';">'
                + esc(step[1]) + '</div>'
                + '</div>';

            if (i < steps.length - 1) {
                var lineColor = i <= current ? '#D24C19' : '#e2e8f0';
                html += '<div style="flex:1;min-width:14px;height:3px;border-radius:2px;background:' + lineColor + ';margin-top:28px;"></div>';
            }
        });

        return html + '</div>';
    }

    function ratePanel(o) {
        var send = 'window.fdMisSend(this.closest(\'.fd-mis-panel\'))';
        var hide = 'this.closest(\'.fd-mis-panel\').style.display=\'none\'';
        var stars = '';
        for (var i = 1; i <= 5; i++) {
            stars += '<button type="button" onclick="window.fdMisStar(this,' + i + ')" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:0;font-size:22px;line-height:1;">★</button>';
        }
        return '<div class="fd-mis-panel" data-order-id="' + o.id + '" data-stars="0" style="display:none;border-top:1px solid #e5e7eb;margin-top:12px;padding-top:12px;">' +
            '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Puntuación</label>' +
            '<div class="fd-mis-stars" style="display:flex;gap:4px;">' + stars + '</div>' +
            '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin:10px 0 4px;">Comentario (opcional)</label>' +
            '<textarea class="fd-mis-comment" rows="2" placeholder="Todo bien, llegó a tiempo..." style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;box-sizing:border-box;"></textarea>' +
            '<div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">' +
                '<button type="button" class="fd-mis-send" onclick="' + send + '" style="background:#D24C19;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Enviar calificación</button>' +
                '<button type="button" onclick="' + hide + '" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Cerrar</button>' +
            '</div>' +
        '</div>';
    }

    function card(o) {
        var acts = actionsFor(o);
        var merchant = o.merchant || {};

        var html = '<div class="fd-mis-card" data-service-id="' + Number(o.id) + '" style="background:#fff;border:1px solid #e5e7eb;overflow:hidden;margin-bottom:14px;">';

        html += '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:11px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb;">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
            + '<span style="font-size:13px;font-weight:700;color:#0c2a4d;font-family:ui-monospace,monospace;">Servicio #' + esc(o.id) + '</span>'
            + badge(TYPE_LABEL[o.order_type] || o.order_type, TYPE_COLOR[o.order_type] || '#6b7280')
                + badge(o.status_label || STATUS_LABEL[o.display_status] || o.status, STATUS_COLOR[o.display_status] || '#6b7280')
            + badge(ESCROW_LABEL[o.escrow_status] || o.escrow_status || '', ESCROW_COLOR[o.escrow_status] || '#6b7280')
            + '</div>'
            + '<span style="font-size:11px;color:#94a3b8;">' + esc(dateText(o.created_at)) + '</span>'
            + '</div>';

        html += trackHtml(o);

        var paymentLabels = window.FD_PAYMENT_METHOD_LABELS || {};
        var right = [];
        if (o.payment_method) right.push('Pago: ' + (paymentLabels[o.payment_method] || o.payment_method));
        if (o.scheduled_at) right.push('Programado: ' + dateText(o.scheduled_at));

        html += '<div style="padding:14px 16px;">'
            + '<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:13px;">'
            + '<div style="min-width:0;">'
            + '<b>' + esc(merchant.business_name || 'Profesional sin asignar') + '</b>'
            + '<div style="color:#64748b;margin-top:4px;font-size:12px;">'
            + esc((o.sub_group && o.sub_group.description) || 'Servicio')
            + (merchant.zone ? ' · ' + esc(merchant.zone) : '')
            + '</div>'
            + '</div>'
            + (right.length
                ? '<div style="text-align:right;font-size:12px;color:#64748b;white-space:nowrap;">' + esc(right.join(' · ')) + '</div>'
                : '')
            + '</div>';

        if (o.description) {
            html += '<div style="margin-top:6px;font-size:12px;color:#9a3412;">Detalle: ' + esc(o.description) + '</div>';
        }

        if (o.order_type === 'QUOTE' && o.status === 'QUOTED' && o.quote) {
            html += '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px 12px;margin-top:10px;">' +
                '<p style="font-size:12px;font-weight:700;color:#0f172a;margin:0 0 4px;">Propuesta del profesional</p>' +
                '<p style="font-size:13px;color:#0f172a;margin:0;">' + esc(money(o.quote.proposed_price)) +
                    (o.quote.estimated_time ? ' · ' + esc(o.quote.estimated_time) : '') + '</p>' +
                (o.quote.details ? '<p style="font-size:12px;color:#6b7280;margin:4px 0 0;">' + esc(o.quote.details) + '</p>' : '') +
            '</div>';
        }

        if (o.estimate_details) {
            html += '<div style="margin-top:6px;font-size:12px;color:#6b7280;font-style:italic;">Detalle del estimado: ' + esc(o.estimate_details) + '</div>';
        }

        var rows = '';
        if (parseFloat(o.visit_fee || 0) > 0) rows += totalRow('Visita', money(o.visit_fee), false);
        if (parseFloat(o.additional_cost || 0) > 0) rows += totalRow('Costo adicional', money(o.additional_cost), false);

        if (rows || parseFloat(o.total_amount || 0) > 0) {
            html += '<div style="border-top:1px solid #e5e7eb;margin-top:10px;padding-top:10px;font-size:13px;">'
                + rows
                + '<div style="display:flex;justify-content:space-between;font-size:15px;font-weight:700;color:#0c2a4d;margin-top:6px;">'
                + '<span>Total</span><span>' + esc(money(o.total_amount)) + '</span></div>'
                + '</div>';
        } else {
            html += '<div style="border-top:1px solid #f1f5f9;margin-top:10px;padding-top:10px;font-size:12px;color:#94a3b8;">Sin cargos registrados todavía.</div>';
        }

        if (o.completion_photo_url) {
            html += '<div style="margin-top:8px;"><a href="' + esc(o.completion_photo_url) + '" target="_blank" rel="noopener" style="font-size:12px;color:#1d4ed8;font-weight:600;">Ver foto comprobante</a></div>';
        }

        if (acts.length) {
            html += '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">';
            acts.forEach(function (a) {
                var onclick = 'window.fdMisAct(' + o.id + ",'" + a.act + "',this)";
                var title = a.title || a.label;
                var style = 'background:' + a.bg + ';color:' + a.fg + ';border:' + a.border + ';padding:' + (a.label ? '9px 16px' : '9px 12px') + ';border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;';
                html += '<button type="button" onclick="' + onclick + '" title="' + esc(title) + '" aria-label="' + esc(title) + '" style="' + style + '">' + (a.icon || '') + a.label + '</button>';
            });
            html += '</div>';
        }

        var tieneRate = acts.some(function (a) { return a.act === 'rate'; });
        if (tieneRate) html += ratePanel(o);

        return html + '</div>';
    }

    function postJson(url, body, btn, onDone) {
        var meta = document.querySelector('meta[name="csrf-token"]');
        var original = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = 'Enviando...'; }

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': meta ? meta.content : ''
            },
            body: JSON.stringify(body || {})
        })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (json) {
                if (btn) { btn.disabled = false; btn.innerHTML = original; }
                onDone(json || {});
            })
            .catch(function () {
                if (btn) { btn.disabled = false; btn.innerHTML = original; }
                onDone({ success: false, message: 'No se pudo completar la acción. Intenta nuevamente.' });
            });
    }

    window.fdMisStar = function (btn, stars) {
        var panel = btn.closest('.fd-mis-panel');
        if (!panel) return;
        panel.setAttribute('data-stars', String(stars));
        Array.prototype.forEach.call(panel.querySelectorAll('.fd-mis-stars button'), function (b, idx) {
            b.style.color = idx < stars ? '#D24C19' : '#d1d5db';
        });
    };

    window.fdMisSend = function (panel) {
        if (!panel) return;
        var stars = parseInt(panel.getAttribute('data-stars') || '0', 10);
        if (stars < 1) { toast('Elegí una puntuación de 1 a 5 estrellas.', false); return; }

        var comment = (panel.querySelector('.fd-mis-comment') || {}).value || '';
        var orderId = panel.getAttribute('data-order-id');
        var btn = panel.querySelector('.fd-mis-send');

        postJson('{{ url('/api/v1/customer/service-orders') }}/' + orderId + '/' + PATH.rate, { stars: stars, comment: comment }, btn, function (json) {
            if (json.success) {
                panel.style.display = 'none';
                toast('Gracias por calificar al prestador.', true);
                window.fdMisLoad(true);
            } else {
                toast(json.message || 'No se pudo registrar la calificación.', false);
            }
        });
    };

    var CONFIRMA = {
        'cancel': '¿Seguro que querés cancelar esta solicitud? Se devuelven los fondos retenidos.',
        'reject-quote': '¿Rechazar esta propuesta? El profesional podrá enviar otra.',
        'approve-estimate': '¿Aprobar este costo adicional? El trabajo continúa.',
        'release': '¿Confirmar que el trabajo está conforme? Se libera el pago al prestador.'
    };

    window.fdMisAct = function (orderId, act, btn) {
        if (act === 'rate') {
            var cardEl = btn.closest('.fd-mis-card');
            var panel = cardEl ? cardEl.querySelector('.fd-mis-panel') : null;
            if (panel) panel.style.display = panel.style.display === 'none' ? '' : 'none';
            return;
        }

        if (CONFIRMA[act] && !window.confirm(CONFIRMA[act])) return;

        postJson('{{ url('/api/v1/customer/service-orders') }}/' + orderId + '/' + PATH[act], {}, btn, function (json) {
            if (json.success) {
                toast(json.message || 'Acción realizada.', true);
                window.fdMisLoad(true);
            } else {
                toast(json.message || 'No se pudo completar la acción.', false);
            }
        });
    };

    function render() {
        var groups = splitGroups();
        paintTabs(groups);
        paintSummary(groups);

        var list = MIS_ACTIVE === 'today' ? groups.today : groups.history;
        var rangeActive = false;

        if (MIS_ACTIVE === 'history') {
            var fromEl = document.getElementById('fd-mis-date-from');
            var toEl = document.getElementById('fd-mis-date-to');
            var from = fromEl ? fromEl.value : '';
            var to = toEl ? toEl.value : '';

            if (from || to) {
                rangeActive = true;
                list = list.filter(function (o) {
                    var day = String((o && o.created_at) || '').slice(0, 10);
                    if (!day) return false;
                    return (!from || day >= from) && (!to || day <= to);
                });
            }
        }

        if (!list.length) {
            MIS_LAST_HTML = '';
            paintPagination(0);

            var filterEl = document.getElementById('fd-mis-filter');
            var tipoEl = document.getElementById('fd-mis-type');
            var text;
            if (rangeActive) {
                text = 'No hay servicios en ese rango de fechas.';
            } else if ((filterEl && filterEl.value) || (tipoEl && tipoEl.value)) {
                text = 'No hay servicios con ese filtro en esta sección.';
            } else if (!MIS_ALL.length) {
                text = 'Todavía no tenés servicios solicitados.\nPedí uno desde la sección "Solicitar Servicio" y acá vas a poder seguirlo.';
            } else if (MIS_ACTIVE === 'today') {
                text = 'No tenés servicios hoy.\nPasá por la pestaña de historial para ver los anteriores.';
            } else {
                text = 'Todavía no hay servicios en el historial.';
            }

            state('message', text);
            return;
        }

        var totalPages = 1;
        var pageList = list;

        if (MIS_ACTIVE === 'history') {
            totalPages = Math.max(1, Math.ceil(list.length / MIS_PER_PAGE));
            if (MIS_PAGE > totalPages) MIS_PAGE = totalPages;
            if (MIS_PAGE < 1) MIS_PAGE = 1;
            pageList = list.slice((MIS_PAGE - 1) * MIS_PER_PAGE, MIS_PAGE * MIS_PER_PAGE);
        }

        var html = pageList.map(card).join('');
        var listEl = document.getElementById('fd-mis-list');
        if (!listEl) return;

        if (html === MIS_LAST_HTML && listEl.style.display === 'block') {
            paintPagination(totalPages);
            return;
        }

        MIS_LAST_HTML = html;
        listEl.innerHTML = html;
        state('content');
        paintPagination(totalPages);
    }

    window.fdMisLoad = function (silent) {
        silent = !!silent;

        if (!silent) {
            MIS_PAGE = 1;
            state('loading');
        }

        var sel = document.getElementById('fd-mis-filter');
        var status = sel ? sel.value : '';
        var tipo = (document.getElementById('fd-mis-type') || {}).value || '';
        var qs = 'per_page=100'
            + (status ? '&status=' + encodeURIComponent(status) : '')
            + (tipo ? '&order_type=' + encodeURIComponent(tipo) : '');

        fetch('{{ url('/api/v1/customer/service-orders') }}?' + qs, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (json) {
                if (!json || !json.success) {
                    // En background no se tapa lo que ya se esta viendo.
                    if (silent) return;
                    state('message', (json && json.message) || 'No se pudieron cargar tus servicios.');
                    return;
                }

                var d = json.data || {};
                var orders = d.orders || [];

                // Refresco automatico: si nada cambio no se vuelve a pintar.
                var payload = JSON.stringify(d);
                if (silent && payload === lastPayload) return;
                lastPayload = payload;

                MIS_ALL = orders;

                if (sel && sel.options.length <= 1 && d.statuses) {
                    d.statuses.forEach(function (s) {
                        var opt = document.createElement('option');
                        opt.value = s;
                        opt.textContent = STATUS_LABEL[s] || s;
                        sel.appendChild(opt);
                    });
                    sel.value = status;
                }

                render();
            })
            .catch(function () {
                if (silent) return;
                state('message', 'No se pudieron cargar tus servicios. Intenta nuevamente.');
            });
    };

    window.fdMisTab = function (tab) {
        MIS_ACTIVE = tab === 'history' ? 'history' : 'today';
        MIS_PAGE = 1;
        MIS_LAST_HTML = '';
        render();
    };

    window.fdMisRangeChange = function () {
        MIS_PAGE = 1;
        MIS_LAST_HTML = '';
        render();
    };

    window.fdMisClearRange = function () {
        var from = document.getElementById('fd-mis-date-from');
        var to = document.getElementById('fd-mis-date-to');
        if (from) from.value = '';
        if (to) to.value = '';
        MIS_PAGE = 1;
        MIS_LAST_HTML = '';
        render();
    };

    window.fdMisGoPage = function (page) {
        MIS_PAGE = Number(page) || 1;
        MIS_LAST_HTML = '';
        render();

        var listEl = document.getElementById('fd-mis-list');
        if (listEl && typeof listEl.scrollIntoView === 'function') {
            try { listEl.scrollIntoView({ block: 'start' }); } catch (e) {}
        }
    };

    // Sin boton de refresco manual: la lista se refresca sola cada pocos
    // segundos y solo se repinta cuando una solicitud sufrio alguna modificacion.
    function autoRefresh() {
        if (document.hidden) return;
        if (!sec || sec.style.display === 'none') return;
        window.fdMisLoad(true);
    }

    setInterval(autoRefresh, 10000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) autoRefresh();
    });

    window.addEventListener('load', function () { window.fdMisLoad(); });
})();
</script>
