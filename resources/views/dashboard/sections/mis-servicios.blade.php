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
            <button type="button" onclick="window.fdMisLoad()" style="background:#D24C19;color:#fff;border:none;padding:8px 14px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                Actualizar
            </button>
        </div>
    </div>

    <div id="fd-mis-message" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:12px;border-radius:6px;margin-bottom:12px;">
        <p id="fd-mis-message-text" style="font-size:13px;color:#374151;margin:0;white-space:pre-line;"></p>
    </div>

    <div id="fd-mis-loading" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:40px;text-align:center;border-radius:6px;">
        <p style="font-size:13px;color:#6b7280;margin:0;">Cargando tus servicios...</p>
    </div>

    <div id="fd-mis-empty" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;border-radius:6px;">
        <p style="font-size:14px;color:#6b7280;margin:0 0 6px;">Todavía no tenés servicios solicitados.</p>
        <p style="font-size:12px;color:#9ca3af;margin:0;">Pedí uno desde la sección "Solicitar Servicio" y acá vas a poder seguirlo.</p>
    </div>

    <div id="fd-mis-list" style="display:none;"></div>

    <div id="fd-mis-pagination" style="display:none;justify-content:center;align-items:center;gap:10px;margin-top:14px;font-size:12px;color:#6b7280;"></div>
</section>

<script>
(function () {
    var sec = document.getElementById('dash-mis-servicios');
    if (!sec) return;

    var STATUS_LABEL = {
        REQUESTED: 'Solicitado',
        QUOTED: 'Presupuestado',
        ACCEPTED: 'Aceptado',
        PROVIDER_DISPATCHED: 'En camino',
        IN_PROGRESS: 'En curso',
        COMPLETED: 'Completado',
        CANCELLED: 'Cancelado'
    };
    var STATUS_COLOR = {
        REQUESTED: '#b45309',
        QUOTED: '#6d28d9',
        ACCEPTED: '#1d4ed8',
        PROVIDER_DISPATCHED: '#0369a1',
        IN_PROGRESS: '#c2410c',
        COMPLETED: '#047857',
        CANCELLED: '#b91c1c'
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

    function esc(value) {
        if (window.escapeHtml) return window.escapeHtml(value);
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function money(value) {
        var n = parseFloat(value || 0);
        return '$' + n.toLocaleString('es-AR', { maximumFractionDigits: 2 });
    }

    function dateText(iso) {
        if (!iso) return '';
        var d = new Date(iso);
        return isNaN(d.getTime()) ? '' : d.toLocaleString('es-AR');
    }

    function show(id, on) {
        var el = document.getElementById(id);
        if (el) el.style.display = on ? '' : 'none';
    }

    function message(text, ok) {
        var box = document.getElementById('fd-mis-message');
        var txt = document.getElementById('fd-mis-message-text');
        if (!box || !txt) return;
        if (!text) { box.style.display = 'none'; return; }
        txt.textContent = text;
        box.style.background = ok ? '#ecfdf5' : '#fff1f2';
        box.style.borderColor = ok ? '#a7f3d0' : '#fecdd3';
        box.style.display = '';
    }

    function badge(label, color) {
        return '<span style="display:inline-block;font-size:11px;font-weight:700;color:' + color + ';background:' + color + '1a;border-radius:9999px;padding:2px 9px;">' + esc(label) + '</span>';
    }

    function row(label, value) {
        return '<div style="display:flex;justify-content:space-between;gap:12px;font-size:12px;padding:3px 0;">' +
            '<span style="color:#6b7280;">' + esc(label) + '</span>' +
            '<strong style="color:#0f172a;text-align:right;">' + esc(value) + '</strong>' +
        '</div>';
    }

    function actionsFor(o) {
        var list = [];
        var propuestaPendiente = o.order_type === 'QUOTE' && o.status === 'QUOTED'
            && o.quote && o.quote.status === 'PENDING' && parseFloat(o.quote.proposed_price || 0) > 0;
        var estimadoPendiente = o.order_type === 'EMERGENCY' && o.status === 'QUOTED'
            && parseFloat(o.additional_cost || 0) > 0 && o.escrow_status === 'HOLDING';

        if (propuestaPendiente) {
            list.push({ act: 'accept-quote', label: 'Aceptar propuesta', bg: '#047857', fg: '#fff', border: 'none' });
            list.push({ act: 'reject-quote', label: 'Rechazar propuesta', bg: '#fff', fg: '#b91c1c', border: '1px solid #fca5a5' });
        }
        if (estimadoPendiente) {
            list.push({ act: 'approve-estimate', label: 'Aprobar costo adicional', bg: '#047857', fg: '#fff', border: 'none' });
        }
        if (o.cancellable) {
            list.push({ act: 'cancel', label: 'Cancelar solicitud', bg: '#fff', fg: '#b91c1c', border: '1px solid #fca5a5' });
        }
        if (o.status === 'COMPLETED' && o.escrow_status === 'HOLDING') {
            list.push({ act: 'release', label: 'Confirmar y liberar pago', bg: '#0c2a4d', fg: '#fff', border: 'none' });
        }
        if (o.status === 'COMPLETED' && !o.customer_rated) {
            list.push({ act: 'rate', label: 'Calificar al prestador', bg: '#D24C19', fg: '#fff', border: 'none' });
        }
        return list;
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
        var html = '<div class="fd-mis-card" style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:10px;">';

        html += '<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">' +
            '<div style="min-width:0;">' +
                '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">' +
                    '<strong style="font-size:14px;color:#0f172a;">' + esc((o.sub_group && o.sub_group.description) || ('Servicio #' + o.id)) + '</strong>' +
                    badge(TYPE_LABEL[o.order_type] || o.order_type, TYPE_COLOR[o.order_type] || '#6b7280') +
                    badge(o.status_label || STATUS_LABEL[o.status] || o.status, STATUS_COLOR[o.status] || '#6b7280') +
                '</div>' +
                '<p style="font-size:12px;color:#6b7280;margin:4px 0 0;">' +
                    esc((o.merchant && o.merchant.business_name) || 'Profesional sin asignar') +
                '</p>' +
            '</div>' +
            '<div style="text-align:right;">' +
                badge(ESCROW_LABEL[o.escrow_status] || o.escrow_status || '', ESCROW_COLOR[o.escrow_status] || '#6b7280') +
                '<p style="font-size:11px;color:#6b7280;margin:4px 0 0;">' + esc(dateText(o.created_at)) + '</p>' +
            '</div>' +
        '</div>';

        if (o.description) {
            html += '<p style="font-size:12px;color:#374151;margin:10px 0 0;">' + esc(o.description) + '</p>';
        }
        if (o.scheduled_at) {
            html += '<p style="font-size:12px;color:#374151;margin:6px 0 0;">Programado: ' + esc(dateText(o.scheduled_at)) + '</p>';
        }
        if (o.estimate_details) {
            html += '<p style="font-size:12px;color:#6b7280;margin:6px 0 0;font-style:italic;">Detalle del estimado: ' + esc(o.estimate_details) + '</p>';
        }

        if (o.order_type === 'QUOTE' && o.status === 'QUOTED' && o.quote) {
            html += '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px 12px;margin-top:10px;">' +
                '<p style="font-size:12px;font-weight:700;color:#0f172a;margin:0 0 4px;">Propuesta del profesional</p>' +
                '<p style="font-size:13px;color:#0f172a;margin:0;">' + esc(money(o.quote.proposed_price)) +
                    (o.quote.estimated_time ? ' · ' + esc(o.quote.estimated_time) : '') + '</p>' +
                (o.quote.details ? '<p style="font-size:12px;color:#6b7280;margin:4px 0 0;">' + esc(o.quote.details) + '</p>' : '') +
            '</div>';
        }

        var monto = '';
        if (parseFloat(o.visit_fee || 0) > 0) monto += row('Visita', money(o.visit_fee));
        if (parseFloat(o.additional_cost || 0) > 0) monto += row('Costo adicional', money(o.additional_cost));
        if (parseFloat(o.total_amount || 0) > 0) monto += row('Total', money(o.total_amount));
        html += '<div style="margin-top:10px;">' + monto + '</div>';

        if (o.completion_photo_url) {
            html += '<p style="margin:8px 0 0;"><a href="' + esc(o.completion_photo_url) + '" target="_blank" rel="noopener" style="font-size:12px;color:#1d4ed8;font-weight:600;">Ver foto comprobante</a></p>';
        }

        if (acts.length) {
            html += '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">';
            acts.forEach(function (a) {
                var onclick = 'window.fdMisAct(' + o.id + ",'" + a.act + "',this)";
                html += '<button type="button" onclick="' + onclick + '" style="background:' + a.bg + ';color:' + a.fg + ';border:' + a.border + ';padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">' + a.label + '</button>';
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
        if (stars < 1) { message('Elegí una puntuación de 1 a 5 estrellas.', false); return; }

        var comment = (panel.querySelector('.fd-mis-comment') || {}).value || '';
        var orderId = panel.getAttribute('data-order-id');
        var btn = panel.querySelector('.fd-mis-send');

        postJson('{{ url('/api/v1/customer/service-orders') }}/' + orderId + '/' + PATH.rate, { stars: stars, comment: comment }, btn, function (json) {
            if (json.success) {
                panel.style.display = 'none';
                message('Gracias por calificar al prestador.', true);
                window.fdMisLoad();
            } else {
                message(json.message || 'No se pudo registrar la calificación.', false);
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
            var card = btn.closest('.fd-mis-card');
            var panel = card ? card.querySelector('.fd-mis-panel') : null;
            if (panel) panel.style.display = panel.style.display === 'none' ? '' : 'none';
            return;
        }

        if (CONFIRMA[act] && !window.confirm(CONFIRMA[act])) return;

        postJson('{{ url('/api/v1/customer/service-orders') }}/' + orderId + '/' + PATH[act], {}, btn, function (json) {
            if (json.success) {
                message(json.message || 'Acción realizada.', true);
                window.fdMisLoad();
            } else {
                message(json.message || 'No se pudo completar la acción.', false);
            }
        });
    };

    window.fdMisLoad = function () {
        show('fd-mis-message', false);
        show('fd-mis-empty', false);
        show('fd-mis-pagination', false);
        show('fd-mis-loading', true);

        var sel = document.getElementById('fd-mis-filter');
        var status = sel ? sel.value : '';
        var tipo = (document.getElementById('fd-mis-type') || {}).value || '';
        var qs = 'per_page=20'
            + (status ? '&status=' + encodeURIComponent(status) : '')
            + (tipo ? '&order_type=' + encodeURIComponent(tipo) : '');

        fetch('{{ url('/api/v1/customer/service-orders') }}?' + qs, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (json) {
                show('fd-mis-loading', false);

                if (!json || !json.success) {
                    show('fd-mis-list', false);
                    show('fd-mis-empty', true);
                    message((json && json.message) || 'No se pudieron cargar tus servicios.', false);
                    return;
                }

                var d = json.data || {};
                var orders = d.orders || [];

                if (sel && sel.options.length <= 1 && d.statuses) {
                    d.statuses.forEach(function (s) {
                        var opt = document.createElement('option');
                        opt.value = s;
                        opt.textContent = STATUS_LABEL[s] || s;
                        sel.appendChild(opt);
                    });
                }

                var summary = document.getElementById('fd-mis-summary');
                if (summary) {
                    summary.textContent = d.pagination && d.pagination.total
                        ? d.pagination.total + ' servicio' + (d.pagination.total === 1 ? '' : 's') + ' solicitado' + (d.pagination.total === 1 ? '' : 's')
                        : 'Tus solicitudes, citas y presupuestos';
                }

                if (!orders.length) {
                    show('fd-mis-list', false);
                    show('fd-mis-empty', true);
                    return;
                }

                var list = document.getElementById('fd-mis-list');
                list.innerHTML = orders.map(card).join('');
                list.style.display = '';

                var pag = d.pagination || {};
                var pg = document.getElementById('fd-mis-pagination');
                if (pag.last_page && pag.last_page > 1) {
                    pg.innerHTML = 'Página ' + pag.current_page + ' de ' + pag.last_page + ' · ' + pag.total + ' servicios';
                    pg.style.display = '';
                }
            })
            .catch(function () {
                show('fd-mis-loading', false);
                show('fd-mis-list', false);
                show('fd-mis-empty', true);
                message('No se pudieron cargar tus servicios. Intenta nuevamente.', false);
            });
    };

    window.addEventListener('load', function () { window.fdMisLoad(); });
})();
</script>
