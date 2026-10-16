<section id="dash-ordenes-servicio" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">{{ optional($user->provider)->business_name ?: 'Tu comercio' }}</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <select id="fd-os-filter" onchange="window.fdOsLoad()" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;">
                <option value="">Todas</option>
            </select>
            <button type="button" onclick="window.fdOsLoad()" style="background:#D24C19;color:#fff;border:none;padding:8px 14px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                Actualizar
            </button>
        </div>
    </div>

    <div id="fd-os-message" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:12px;border-radius:6px;margin-bottom:12px;">
        <p id="fd-os-message-text" style="font-size:13px;color:#374151;margin:0;white-space:pre-line;"></p>
    </div>

    <div id="fd-os-loading" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:40px;text-align:center;border-radius:6px;">
        <p style="font-size:13px;color:#6b7280;margin:0;">Cargando órdenes de servicio...</p>
    </div>

    <div id="fd-os-empty" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;border-radius:6px;">
        <p style="font-size:14px;color:#6b7280;margin:0;">No hay órdenes de servicio en esta vista.</p>
    </div>

    <div id="fd-os-pool-wrap" style="display:none;margin-bottom:20px;">
        <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 8px;text-transform:uppercase;letter-spacing:.5px;">Disponibles para vos (sin asignar)</p>
        <div id="fd-os-pool"></div>
    </div>

    <div id="fd-os-mine-wrap" style="display:none;">
        <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 8px;text-transform:uppercase;letter-spacing:.5px;">Mis órdenes</p>
        <div id="fd-os-list"></div>
    </div>

    <div id="fd-os-pagination" style="display:none;justify-content:center;align-items:center;gap:10px;margin-top:14px;"></div>
</section>

<script>
(function () {
    var sec = document.getElementById('dash-ordenes-servicio');
    if (!sec) return;

    var providerId = parseInt(sec.getAttribute('data-provider-id'), 10) || 0;
    var STATUS_LABEL = {
        REQUESTED: 'Solicitado', QUOTED: 'Presupuestado', ACCEPTED: 'Aceptado',
        PROVIDER_DISPATCHED: 'En camino', IN_PROGRESS: 'En curso',
        COMPLETED: 'Completado', CANCELLED: 'Cancelado'
    };
    var STATUS_COLOR = {
        REQUESTED: '#b45309', QUOTED: '#6d28d9', ACCEPTED: '#1d4ed8',
        PROVIDER_DISPATCHED: '#0369a1', IN_PROGRESS: '#047857',
        COMPLETED: '#047857', CANCELLED: '#b91c1c'
    };
    var TYPE_LABEL = { EMERGENCY: 'Urgencia', SCHEDULED: 'Cita', QUOTE: 'Presupuesto' };
    var TYPE_COLOR = { EMERGENCY: '#b91c1c', SCHEDULED: '#1d4ed8', QUOTE: '#6d28d9' };

    function esc(value) {
        if (window.escapeHtml) return window.escapeHtml(value);
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function money(v) {
        var n = parseFloat(v || 0);
        return '$' + n.toLocaleString('es-AR', { maximumFractionDigits: 0 });
    }

    function show(id, on) { var el = document.getElementById(id); if (el) el.style.display = on ? '' : 'none'; }

    function message(text, ok) {
        var box = document.getElementById('fd-os-message');
        var txt = document.getElementById('fd-os-message-text');
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

    function isPool(o) { return !o.merchant_id; }
    function isMine(o) { return o.merchant_id === providerId; }

    function actionsFor(o) {
        var a = [];

        if (isPool(o) && o.status === 'REQUESTED' && (o.order_type === 'EMERGENCY' || o.order_type === 'SCHEDULED')) {
            a.push('accept');
        }
        if (isPool(o) && o.order_type === 'QUOTE' && (o.status === 'REQUESTED' || o.status === 'QUOTED')) {
            a.push('quote');
        }
        if (isMine(o)
            && (o.order_type === 'EMERGENCY' || o.order_type === 'SCHEDULED')
            && ['ACCEPTED', 'PROVIDER_DISPATCHED'].indexOf(o.status) >= 0) {
            a.push('estimate');
        }
        if (isMine(o) && ['ACCEPTED', 'PROVIDER_DISPATCHED', 'IN_PROGRESS'].indexOf(o.status) >= 0) {
            a.push('complete');
        }
        if (isMine(o) && o.status === 'COMPLETED') {
            a.push('rate');
        }
        return a;
    }

    function actionButtons(o) {
        var acts = actionsFor(o);
        if (!acts.length) return '';
        var labels = {
            accept: [o.order_type === 'SCHEDULED' ? 'Aceptar cita' : 'Aceptar y despachar', '#047857'],
            estimate: ['Cargar precio', '#0c2a4d'],
            quote: ['Proponer presupuesto', '#6d28d9'],
            complete: ['Completar trabajo', '#047857'],
            rate: ['Calificar cliente', '#D24C19']
        };
        var html = '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">';
        acts.forEach(function (act) {
            var lbl = labels[act];
            if (!lbl) return;
            var onclick = act === 'accept'
                ? 'window.fdOsAccept(' + o.id + ')'
                : "window.fdOsAct(" + o.id + ",'" + act + "',this)";
            html += '<button type="button" onclick="' + onclick + '" style="background:' + lbl[1] + ';color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">' + lbl[0] + '</button>';
        });
        html += '</div>';
        return html;
    }

    function panelHtml() {
        return '<div class="fd-os-panel" data-act="" data-stars="0" style="display:none;border-top:1px solid #e5e7eb;margin-top:12px;padding-top:12px;">' +
            '<div data-fields="estimate" style="display:none;">' +
                '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Precio del trabajo ($)</label>' +
                '<input type="number" class="fd-os-cost" min="0.01" step="0.01" placeholder="0.00" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">' +
                '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin:10px 0 4px;">Detalles (opcional)</label>' +
                '<textarea class="fd-os-details" rows="2" placeholder="Repuestos, mano de obra..." style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;"></textarea>' +
            '</div>' +
            '<div data-fields="quote" style="display:none;">' +
                '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Precio propuesto ($)</label>' +
                '<input type="number" class="fd-os-price" min="0.01" step="0.01" placeholder="0.00" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">' +
                '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin:10px 0 4px;">Tiempo estimado</label>' +
                '<input type="text" class="fd-os-time" placeholder="Ej: 2 horas" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">' +
                '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin:10px 0 4px;">Detalles (opcional)</label>' +
                '<textarea class="fd-os-qdetails" rows="2" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;"></textarea>' +
            '</div>' +
            '<div data-fields="complete" style="display:none;">' +
                '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Foto comprobante (jpg, png, webp · máx 5 MB)</label>' +
                '<input type="file" class="fd-os-photo" accept="image/*" style="font-size:13px;">' +
            '</div>' +
            '<div data-fields="rate" style="display:none;">' +
                '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Puntuación</label>' +
                '<div class="fd-os-stars" style="display:flex;gap:4px;font-size:22px;line-height:1;">' +
                    '<button type="button" onclick="window.fdOsStar(this,1)" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:0;">★</button>' +
                    '<button type="button" onclick="window.fdOsStar(this,2)" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:0;">★</button>' +
                    '<button type="button" onclick="window.fdOsStar(this,3)" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:0;">★</button>' +
                    '<button type="button" onclick="window.fdOsStar(this,4)" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:0;">★</button>' +
                    '<button type="button" onclick="window.fdOsStar(this,5)" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:0;">★</button>' +
                '</div>' +
                '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin:10px 0 4px;">Comentario (opcional)</label>' +
                '<textarea class="fd-os-comment" rows="2" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;"></textarea>' +
            '</div>' +
            '<div style="display:flex;gap:8px;margin-top:12px;align-items:center;flex-wrap:wrap;">' +
                '<button type="button" onclick="window.fdOsSend(this.closest(\'.fd-os-panel\'))" style="background:#0c2a4d;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Confirmar</button>' +
                '<button type="button" onclick="this.closest(\'.fd-os-panel\').style.display=\'none\'" style="background:#fff;color:#6b7280;border:1px solid #d1d5db;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Cancelar</button>' +
                '<span class="fd-os-panelmsg" style="display:none;font-size:12px;font-weight:600;"></span>' +
            '</div>' +
        '</div>';
    }

    function card(o) {
        var typeColor = TYPE_COLOR[o.order_type] || '#6b7280';
        var statusColor = STATUS_COLOR[o.status] || '#6b7280';
        var when = o.scheduled_at ? new Date(o.scheduled_at).toLocaleString('es-AR') : new Date(o.created_at).toLocaleString('es-AR');
        var whenLabel = o.scheduled_at ? 'Agendado' : 'Creado';
        var rubro = (o.sub_group && o.sub_group.description) || 'Servicio';
        var total = parseFloat(o.total_amount || 0);

        var rows = '';
        rows += '<div style="font-size:13px;color:#374151;margin-top:8px;">Rubro: <strong>' + esc(rubro) + '</strong></div>';
        if (isPool(o)) {
            rows += '<div style="font-size:13px;color:#6b7280;margin-top:4px;">Cliente: ' + esc(o.customer ? o.customer.name : '#'+o.customer_id) + '</div>';
        } else if (o.merchant) {
            rows += '<div style="font-size:13px;color:#374151;margin-top:4px;">Comercio: ' + esc(o.merchant.business_name) + '</div>';
        }
        rows += '<div style="font-size:13px;color:#6b7280;margin-top:4px;">' + whenLabel + ': ' + esc(when) + '</div>';
        if (o.description) rows += '<div style="font-size:13px;color:#6b7280;margin-top:4px;">Descripción: ' + esc(o.description) + '</div>';
        if (o.estimate_details) rows += '<div style="font-size:13px;color:#6b7280;margin-top:4px;">Detalle: ' + esc(o.estimate_details) + '</div>';
        if (o.status === 'QUOTED' && isPool(o) && o.order_type === 'QUOTE') {
            rows += '<div style="font-size:13px;color:#6d28d9;margin-top:6px;font-weight:600;">Esperando la decisión del cliente.</div>';
        }
        if (o.status === 'QUOTED' && isMine(o)) {
            rows += '<div style="font-size:13px;color:#6d28d9;margin-top:6px;font-weight:600;">Esperando aprobación del cliente.</div>';
        }

        return '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:10px;">' +
            '<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">' +
                '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">' +
                    badge(TYPE_LABEL[o.order_type] || o.order_type, typeColor) +
                    badge(STATUS_LABEL[o.status] || o.status, statusColor) +
                    (isPool(o) ? badge('Disponible', '#D24C19') : '') +
                    '<span style="font-size:11px;color:#9ca3af;">#' + o.id + '</span>' +
                '</div>' +
                '<div style="text-align:right;">' +
                    '<p style="font-size:15px;font-weight:700;color:#0f172a;margin:0;">' + (total > 0 ? money(total) : 'A cotizar') + '</p>' +
                    '<p style="font-size:11px;color:#6b7280;margin:2px 0 0;">' + esc(o.payment_method || '') + (o.escrow_status ? ' · escrow ' + esc(o.escrow_status) : '') + '</p>' +
                '</div>' +
            '</div>' +
            rows +
            actionButtons(o) +
            panelHtml() +
        '</div>';
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function postJson(url, data) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify(data || {})
        }).then(function (r) { return r.json().catch(function () { return {}; }); });
    }

    function panelMsg(panel, text, ok) {
        var el = panel.querySelector('.fd-os-panelmsg');
        if (!el) return;
        el.textContent = text || '';
        el.style.display = text ? '' : 'none';
        el.style.color = ok ? '#047857' : '#b91c1c';
    }

    window.fdOsStar = function (btn, n) {
        var panel = btn.closest('.fd-os-panel');
        panel.setAttribute('data-stars', String(n));
        Array.prototype.forEach.call(panel.querySelectorAll('.fd-os-stars button'), function (b, i) {
            b.style.color = i < n ? '#f59e0b' : '#d1d5db';
        });
    };

    window.fdOsAct = function (orderId, act, btn) {
        var card = btn.closest('[data-card]');
        if (!card) return;
        var panel = card.querySelector('.fd-os-panel');
        Array.prototype.forEach.call(panel.querySelectorAll('[data-fields]'), function (g) { g.style.display = 'none'; });
        var group = panel.querySelector('[data-fields="' + act + '"]');
        if (group) group.style.display = '';
        panel.setAttribute('data-act', orderId + '|' + act);
        panel.setAttribute('data-stars', '0');
        Array.prototype.forEach.call(panel.querySelectorAll('.fd-os-stars button'), function (b) { b.style.color = '#d1d5db'; });
        panelMsg(panel, '', true);
        panel.style.display = '';
        var first = panel.querySelector('[data-fields="' + act + '"] input, [data-fields="' + act + '"] textarea');
        if (first) first.focus();
    };

    window.fdOsAccept = function (orderId) {
        if (!window.confirm('¿Aceptar este pedido para tu comercio?')) return;
        postJson('/api/v1/provider/service-orders/' + orderId + '/accept', {}).then(function (json) {
            if (json && json.success) { message(json.message || 'Pedido aceptado.', true); }
            else { message((json && json.message) || 'No se pudo aceptar el pedido.', false); }
            window.fdOsLoad();
        }).catch(function () { message('No se pudo aceptar el pedido. Intenta nuevamente.', false); });
    };

    window.fdOsSend = function (panel) {
        var raw = panel.getAttribute('data-act') || '';
        var parts = raw.split('|');
        var id = parts[0], act = parts[1];
        if (!id || !act) return;

        if (act === 'estimate') {
            var cost = panel.querySelector('.fd-os-cost').value;
            if (!cost || parseFloat(cost) <= 0) { panelMsg(panel, 'Ingresá un precio válido.', false); return; }
            postJson('/api/v1/provider/service-orders/' + id + '/onsite-estimate', {
                additional_cost: parseFloat(cost),
                details: panel.querySelector('.fd-os-details').value || null
            }).then(function (json) {
                if (json && json.success) { panel.style.display = 'none'; message('Precio enviado. El cliente debe aprobarlo.', true); window.fdOsLoad(); }
                else panelMsg(panel, (json && json.message) || 'No se pudo enviar el precio.', false);
            }).catch(function () { panelMsg(panel, 'Error de red. Intenta nuevamente.', false); });

        } else if (act === 'quote') {
            var price = panel.querySelector('.fd-os-price').value;
            var time = panel.querySelector('.fd-os-time').value;
            if (!price || parseFloat(price) <= 0) { panelMsg(panel, 'Ingresá un precio propuesto válido.', false); return; }
            if (!time) { panelMsg(panel, 'Ingresá el tiempo estimado.', false); return; }
            postJson('/api/v1/provider/service-orders/' + id + '/quote-proposal', {
                proposed_price: parseFloat(price),
                estimated_time: time,
                details: panel.querySelector('.fd-os-qdetails').value || null
            }).then(function (json) {
                if (json && json.success) { panel.style.display = 'none'; message('Propuesta enviada. El cliente debe aceptarla.', true); window.fdOsLoad(); }
                else panelMsg(panel, (json && json.message) || 'No se pudo enviar la propuesta.', false);
            }).catch(function () { panelMsg(panel, 'Error de red. Intenta nuevamente.', false); });

        } else if (act === 'complete') {
            var file = panel.querySelector('.fd-os-photo').files[0];
            if (!file) { panelMsg(panel, 'Adjuntá la foto comprobante.', false); return; }
            var fd = new FormData();
            fd.append('photo', file);
            fetch('/api/v1/provider/service-orders/' + id + '/complete', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrfToken() },
                body: fd
            })
                .then(function (r) { return r.json().catch(function () { return {}; }); })
                .then(function (json) {
                    if (json && json.success) { panel.style.display = 'none'; message('Trabajo completado. El cliente debe confirmar para liberar el pago.', true); window.fdOsLoad(); }
                    else panelMsg(panel, (json && json.message) || 'No se pudo completar el trabajo.', false);
                }).catch(function () { panelMsg(panel, 'Error de red. Intenta nuevamente.', false); });

        } else if (act === 'rate') {
            var stars = parseInt(panel.getAttribute('data-stars'), 10) || 0;
            if (stars < 1) { panelMsg(panel, 'Elegí una puntuación de 1 a 5.', false); return; }
            postJson('/api/v1/provider/service-orders/' + id + '/rate', {
                stars: stars,
                comment: panel.querySelector('.fd-os-comment').value || null
            }).then(function (json) {
                if (json && json.success) { panel.style.display = 'none'; message('Gracias por calificar al cliente.', true); window.fdOsLoad(); }
                else panelMsg(panel, (json && json.message) || 'No se pudo registrar la calificación.', false);
            }).catch(function () { panelMsg(panel, 'Error de red. Intenta nuevamente.', false); });
        }
    };

    window.fdOsLoad = function () {
        if (!providerId) {
            show('fd-os-loading', false); show('fd-os-mine-wrap', false); show('fd-os-pool-wrap', false); show('fd-os-empty', false);
            message('Tu usuario aún no tiene un comercio asociado. Completá tu perfil de prestador.', false);
            return;
        }

        show('fd-os-message', false); show('fd-os-empty', false); show('fd-os-pagination', false);
        show('fd-os-mine-wrap', false); show('fd-os-pool-wrap', false);
        show('fd-os-loading', true);

        var sel = document.getElementById('fd-os-filter');
        var status = sel ? sel.value : '';
        var qs = 'per_page=20' + (status ? '&status=' + encodeURIComponent(status) : '');

        fetch('/api/v1/provider/service-orders?' + qs, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (json) {
                show('fd-os-loading', false);
                if (!json || !json.success) {
                    show('fd-os-empty', true);
                    message((json && json.message) || 'No se pudieron cargar las órdenes.', false);
                    return;
                }
                var d = json.data || {};
                var orders = d.orders || [];

                // Rellena el select de estados una sola vez.
                if (sel && sel.options.length <= 1 && d.statuses) {
                    d.statuses.forEach(function (s) {
                        var opt = document.createElement('option');
                        opt.value = s; opt.textContent = STATUS_LABEL[s] || s;
                        sel.appendChild(opt);
                    });
                }

                if (!orders.length) { show('fd-os-empty', true); return; }

                var pool = orders.filter(isPool);
                var mine = orders.filter(function (o) { return !isPool(o); });

                if (pool.length) {
                    document.getElementById('fd-os-pool').innerHTML = pool.map(function (o) {
                        return '<div data-card>' + card(o) + '</div>';
                    }).join('');
                    show('fd-os-pool-wrap', true);
                }

                if (mine.length) {
                    document.getElementById('fd-os-list').innerHTML = mine.map(function (o) {
                        return '<div data-card>' + card(o) + '</div>';
                    }).join('');
                    show('fd-os-mine-wrap', true);
                }

                var pag = d.pagination || {};
                var pg = document.getElementById('fd-os-pagination');
                if (pag.last_page && pag.last_page > 1) {
                    pg.innerHTML = 'Página ' + pag.current_page + ' de ' + pag.last_page + ' · ' + pag.total + ' órdenes';
                    pg.style.display = '';
                }
            })
            .catch(function () {
                show('fd-os-loading', false); show('fd-os-empty', true);
                message('No se pudieron cargar las órdenes. Intenta nuevamente.', false);
            });
    };

    window.addEventListener('load', function () { window.fdOsLoad(); });
})();
</script>
