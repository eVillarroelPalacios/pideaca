{{--
    Vista operativa compartida de las ordenes de servicio del prestador.

    La usan las paginas "Ordenes de Servicio" y "Gestionar Solicitudes":
    tarjeta enrichida (ubicacion, contacto, adjuntos, presupuesto y linea de
    tiempo) y modal de detalle con las acciones operativas del prestador.
--}}
<div id="fd-so-modal" style="display:none;position:fixed;top:0;right:0;bottom:0;left:0;background:rgba(0,0,0,.6);z-index:600;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;width:100%;max-width:720px;max-height:90vh;display:flex;flex-direction:column;border-radius:10px;box-shadow:0 12px 40px rgba(0,0,0,.35);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
            <div id="fd-so-modal-head" style="min-width:0;">
                <div id="fd-so-modal-badges" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:6px;"></div>
                <h3 id="fd-so-modal-title" style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;"></h3>
            </div>
            <button type="button" onclick="window.FdServicioUI.close()" aria-label="Cerrar" style="background:#f1f5f9;color:#475569;border:none;width:30px;height:30px;border-radius:50%;font-size:16px;line-height:1;cursor:pointer;flex:0 0 auto;">×</button>
        </div>
        <div id="fd-so-modal-body" style="padding:16px 20px;overflow:auto;flex:1 1 auto;"></div>
        <div id="fd-so-modal-actions" style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;flex-wrap:wrap;align-items:center;"></div>
    </div>
</div>

<script>
(function () {
    // Se incluye una sola vez por pagina: si ya existe, no se redefine.
    if (window.FdServicioUI) return;

    var API = '{{ url('/api/v1/provider/service-orders') }}';
    var PROVIDER_ID = parseInt('{{ $user->provider?->id ?? 0 }}', 10) || 0;
    var MERCHANT_NAME = @json($user->provider?->business_name ?: 'PideAca');
    var registry = {};
    // Como cada orden se muestra como "propia" o "del pool" segun la pagina
    // que la lista, se guarda el contexto junto con la orden.
    var registryOpts = {};
    // Orden y accion que se estan mostrando en el modal.
    var currentOrderId = null;
    var currentAction = null;

    var STATUS_LABEL = {
        PENDIENTE: 'Pendiente', PRESUPUESTADO: 'Presupuestado', EN_PROCESO: 'En proceso',
        COMPLETADO: 'Completado', CANCELADO: 'Cancelado'
    };
    var STATUS_COLOR = {
        PENDIENTE: '#b45309', PRESUPUESTADO: '#6d28d9', EN_PROCESO: '#0369a1',
        COMPLETADO: '#047857', CANCELADO: '#b91c1c'
    };
    var TYPE_LABEL = { EMERGENCY: 'Urgencia', SCHEDULED: 'Cita', QUOTE: 'Presupuesto' };
    var TYPE_COLOR = { EMERGENCY: '#b91c1c', SCHEDULED: '#1d4ed8', QUOTE: '#6d28d9' };
    var ESCROW_LABEL = { HOLDING: 'En garantía', RELEASED: 'Liberado al prestador', REFUNDED: 'Reembolsado al cliente' };
    // Paso del timeline en el que cae cada estado de la orden.
    var STATUS_STEP = {
        REQUESTED: 'created', QUOTED: 'quoted', ACCEPTED: 'accepted',
        PROVIDER_DISPATCHED: 'started', IN_PROGRESS: 'started',
        COMPLETED: 'completed', CANCELLED: 'cancelled'
    };

    // ------------------------------------------------------------ helpers

    function esc(value) {
        if (window.escapeHtml) return window.escapeHtml(value);
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function money(value) {
        var n = parseFloat(value || 0);
        return '$' + n.toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function fmtDateTime(iso) {
        if (!iso) return '';
        var d = new Date(iso);
        return isNaN(d.getTime()) ? '' : d.toLocaleString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function fmtDate(iso) {
        if (!iso) return '';
        var d = new Date(iso);
        return isNaN(d.getTime()) ? '' : d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function fmtTime(iso) {
        if (!iso) return '';
        var d = new Date(iso);
        return isNaN(d.getTime()) ? '' : d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    }

    function badge(label, color) {
        return '<span style="display:inline-block;font-size:11px;font-weight:700;color:' + color + ';background:' + color + '1a;border-radius:9999px;padding:2px 9px;">' + esc(label) + '</span>';
    }

    function statusBadge(o) {
        return badge(o.status_label || STATUS_LABEL[o.display_status] || o.status, STATUS_COLOR[o.display_status] || '#6b7280');
    }

    function typeBadge(o) {
        return badge(TYPE_LABEL[o.order_type] || o.order_type, TYPE_COLOR[o.order_type] || '#6b7280');
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

    // ------------------------------------------------- ubicacion y contacto

    /**
     * Propuesta del propio prestador sobre la orden (si ya respondio al
     * presupuesto del cliente). El contenedor del cliente viene con el
     * proveedor en null, asi que se filtra por el comercio logueado.
     */
    function myQuote(o) {
        var list = o.quotes || [];

        for (var i = 0; i < list.length; i++) {
            if (list[i].provider_id && list[i].provider_id === PROVIDER_ID) {
                return list[i];
            }
        }

        return null;
    }

    function quoteAt(o) {
        var quote = myQuote(o) || o.quote;
        return quote ? quote.created_at : null;
    }

    function addressLines(o) {
        var lines = [];
        if (o.address_street) lines.push(o.address_street);
        if (o.address_unit) lines.push(o.address_unit);
        if (o.address_neighborhood) lines.push(o.address_neighborhood);
        if (!lines.length && o.address) lines.push(o.address);
        return lines;
    }

    function addressText(o) {
        var lines = addressLines(o);
        return lines.length ? lines.join(' · ') : 'Sin dirección cargada';
    }

    function mapUrl(o) {
        var lat = parseFloat(o.latitude);
        var lng = parseFloat(o.longitude);
        var query = (!isNaN(lat) && !isNaN(lng) && (lat !== 0 || lng !== 0))
            ? (lat + ',' + lng)
            : addressText(o);

        if (!query || query === 'Sin dirección cargada') return '';

        return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(query);
    }

    function phoneDigits(o) {
        var phone = (o.customer && o.customer.phone) || '';
        return String(phone).replace(/[^\d]/g, '');
    }

    function chatText(o) {
        var rubro = (o.sub_group && o.sub_group.description) || 'tu solicitud';
        var comercio = MERCHANT_NAME;
        var nombre = (o.customer && o.customer.name) || '';
        return 'Hola ' + nombre + ', te escribo de ' + comercio + ' por tu solicitud #' + o.id + ' de ' + rubro + '.';
    }

    function waUrl(o, text) {
        var digits = phoneDigits(o);
        if (!digits) return '';
        return 'https://wa.me/' + digits + '?text=' + encodeURIComponent(text || chatText(o));
    }

    function contactButtons(o, small) {
        var phone = (o.customer && o.customer.phone) || '';
        var email = (o.customer && o.customer.email) || '';
        var pad = small ? '6px 10px' : '8px 14px';
        var html = '<div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">';

        if (phone) {
            html += '<a href="tel:+' + esc(phoneDigits(o)) + '" style="background:#0c2a4d;color:#fff;text-decoration:none;padding:' + pad + ';border-radius:4px;font-size:12px;font-weight:600;">Llamar</a>';
            html += '<a href="' + esc(waUrl(o)) + '" target="_blank" rel="noopener" style="background:#25D366;color:#fff;text-decoration:none;padding:' + pad + ';border-radius:4px;font-size:12px;font-weight:600;">WhatsApp</a>';
        }

        html += '<button type="button" onclick="window.FdServicioUI.act(\'chat\',' + o.id + ')" style="background:#fff;color:#0c2a4d;border:1px solid #cbd5e1;padding:' + pad + ';border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Chat</button>';

        if (email) {
            html += '<a href="mailto:' + esc(email) + '" style="background:#fff;color:#475569;text-decoration:none;border:1px solid #cbd5e1;padding:' + pad + ';border-radius:4px;font-size:12px;font-weight:600;">Email</a>';
        }

        html += '</div>';
        return html;
    }

    function locationBlock(o) {
        var html = '<div style="font-size:12px;color:#334155;display:flex;align-items:flex-start;gap:6px;">';
        html += '<span style="flex:0 0 auto;">📍</span>';
        html += '<span style="min-width:0;">' + esc(addressText(o)) + '</span>';
        html += '</div>';

        var url = mapUrl(o);
        if (url) {
            html += '<a href="' + esc(url) + '" target="_blank" rel="noopener" style="display:inline-block;margin-top:6px;font-size:11px;font-weight:700;color:#1d4ed8;text-decoration:none;">Abrir en el mapa →</a>';
        }

        return html;
    }

    // ----------------------------------------------------- adjuntos y dinero

    function attachmentsHtml(o) {
        var items = [];
        var list = o.attachments || [];

        list.forEach(function (att) {
            if (att.type === 'video') {
                items.push('<a href="' + esc(att.url) + '" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#0c2a4d;border:1px solid #e5e7eb;border-radius:6px;padding:6px 10px;text-decoration:none;">▶ Video del cliente</a>');
                return;
            }
            items.push('<a href="' + esc(att.url) + '" target="_blank" rel="noopener" style="display:inline-block;"><img src="' + esc(att.url) + '" alt="Foto del cliente" style="width:72px;height:72px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;"></a>');
        });

        if (o.photo_url) {
            items.push('<a href="' + esc(o.photo_url) + '" target="_blank" rel="noopener" style="display:inline-block;"><img src="' + esc(o.photo_url) + '" alt="Foto de la urgencia" style="width:72px;height:72px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;"></a>');
        }
        if (o.audio_url) {
            items.push('<a href="' + esc(o.audio_url) + '" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#0c2a4d;border:1px solid #e5e7eb;border-radius:6px;padding:6px 10px;text-decoration:none;">🎙 Audio</a>');
        }
        if (o.completion_photo_url) {
            items.push('<a href="' + esc(o.completion_photo_url) + '" target="_blank" rel="noopener" style="display:inline-block;"><img src="' + esc(o.completion_photo_url) + '" alt="Foto del trabajo terminado" style="width:72px;height:72px;object-fit:cover;border-radius:6px;border:1px solid #a7f3d0;"></a>');
        }

        if (!items.length) return '';

        return '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">' + items.join('') + '</div>';
    }

    function budgetRows(o) {
        var rows = [];
        var proposal = myQuote(o) || (o.quote && o.quote.provider_id ? o.quote : null);
        var price = proposal ? parseFloat(proposal.proposed_price || 0) : 0;

        if (price > 0) {
            rows.push({ label: 'Tu propuesta', value: price, strong: false });
            if (proposal.estimated_time) {
                rows.push({ label: 'Tiempo estimado', text: proposal.estimated_time });
            }
            if (proposal.details) {
                rows.push({ label: 'Detalle de la propuesta', text: proposal.details });
            }
        } else if (o.order_type === 'QUOTE' && o.proposals && o.proposals.length) {
            rows.push({ label: 'Propuestas en el rubro', text: o.proposals.length + ' profesional(es) respondieron' });
        }
        if (parseFloat(o.visit_fee) > 0) {
            rows.push({ label: 'Tarifa base', value: parseFloat(o.visit_fee) });
        }
        if (parseFloat(o.additional_cost) > 0) {
            rows.push({ label: o.order_type === 'QUOTE' ? 'Presupuesto asignado' : 'Costo adicional', value: parseFloat(o.additional_cost) });
        }
        if (parseFloat(o.total_amount) > 0) {
            rows.push({ label: 'Total del trabajo', value: parseFloat(o.total_amount), strong: true });
        }
        if (parseFloat(o.deposit_amount) > 0) {
            rows.push({ label: 'Seña retenida', value: parseFloat(o.deposit_amount) });
        }
        if (parseFloat(o.commission_amount) > 0) {
            rows.push({ label: 'Comisión pideaca', value: parseFloat(o.commission_amount) });
        }
        if (o.estimate_details) {
            rows.push({ label: 'Detalle del presupuesto', text: o.estimate_details });
        }

        return rows;
    }

    function budgetHtml(o) {
        var rows = budgetRows(o);

        if (!rows.length) {
            return '<p style="font-size:12px;color:#6b7280;margin:0;">Todavía no hay un presupuesto asignado.</p>';
        }

        var html = '<div style="display:flex;flex-direction:column;gap:4px;">';
        rows.forEach(function (row) {
            if (row.text !== undefined) {
                html += '<div style="font-size:12px;color:#475569;">' + esc(row.label) + ': <strong>' + esc(row.text) + '</strong></div>';
                return;
            }
            html += '<div style="display:flex;justify-content:space-between;gap:10px;font-size:' + (row.strong ? '13px;font-weight:700;color:#0f172a' : '12px;color:#475569') + ';">'
                + '<span>' + esc(row.label) + '</span><span>' + money(row.value) + '</span></div>';
        });
        html += '</div>';

        if (o.payment_method) {
            html += '<p style="font-size:11px;color:#6b7280;margin:8px 0 0;">Medio de pago: ' + esc(o.payment_method)
                + (o.payment_mode ? ' · ' + esc(o.payment_mode === 'FULL' ? 'Pago total' : 'Con seña') : '')
                + (o.escrow_status ? ' · ' + esc(ESCROW_LABEL[o.escrow_status] || o.escrow_status) : '') + '</p>';
        }

        if (o.status === 'QUOTED') {
            var waiting = o.order_type === 'QUOTE'
                ? 'Tu propuesta está enviada: esperando la decisión del cliente.'
                : 'Presupuesto enviado: esperando la aprobación del cliente.';
            html += '<p style="font-size:11px;color:#b45309;margin:6px 0 0;">' + waiting + '</p>';
        }

        return html;
    }

    // ------------------------------------------------------------ timeline

    function timelineSteps(o) {
        var steps = [{ key: 'created', label: 'Solicitud recibida', at: o.created_at }];

        if (o.order_type === 'SCHEDULED' || o.scheduled_at) {
            steps.push({ key: 'scheduled', label: 'Cita programada', at: o.scheduled_at });
        }

        if (o.order_type === 'QUOTE') {
            // En un presupuesto primero se manda la propuesta y despues la
            // confirma el cliente: no es el prestador quien la acepta.
            steps.push({ key: 'quoted', label: 'Presupuesto enviado', at: quoteAt(o) });
            steps.push({ key: 'accepted', label: 'Presupuesto confirmado por el cliente', at: o.accepted_at });
        } else {
            steps.push({ key: 'accepted', label: 'Aceptada por el prestador', at: o.accepted_at });
        }

        steps.push({ key: 'started', label: 'Trabajo iniciado / en camino', at: o.started_at });
        steps.push({ key: 'completed', label: 'Trabajo completado', at: o.completed_at });

        if (o.escrow_status === 'RELEASED') {
            steps.push({ key: 'paid', label: 'Pago liberado al prestador', at: o.completed_at });
        }

        return steps;
    }

    function timelineItems(o) {
        var steps = timelineSteps(o);
        var currentKey = STATUS_STEP[o.status] || 'created';
        var currentIndex = 0;
        var cancelled = o.status === 'CANCELLED';
        var finished = o.status === 'COMPLETED';
        var found = false;

        steps.forEach(function (step, i) {
            if (step.key === currentKey) {
                currentIndex = i;
                found = true;
            }
        });

        if (!found) {
            // El estado no tiene un paso propio (por ejemplo, el presupuesto
            // cargado en el lugar de una cita): se muestra el ultimo paso
            // que ya tiene fecha.
            steps.forEach(function (step, i) {
                if (step.at && i < steps.length - 1) currentIndex = i;
            });
        }

        return steps.map(function (step, i) {
            var state;

            if (cancelled) {
                state = i === 0 ? 'done' : 'off';
            } else if (i === currentIndex) {
                state = finished ? 'done' : 'active';
            } else if (step.at) {
                state = 'done';
            } else if (i < currentIndex) {
                state = 'done';
            } else {
                state = 'pending';
            }

            return { step: step, state: state };
        });
    }

    function timelineHtml(o) {
        var items = timelineItems(o);
        var html = '<ol style="list-style:none;margin:0;padding:0;">';

        items.forEach(function (item, i) {
            var color = item.state === 'done' ? '#047857' : (item.state === 'active' ? '#D24C19' : '#cbd5e1');
            var textColor = item.state === 'pending' || item.state === 'off' ? '#94a3b8' : '#334155';
            var when = item.step.at ? fmtDateTime(item.step.at) : '';
            var last = i === items.length - 1;

            html += '<li style="display:flex;gap:10px;align-items:flex-start;">';
            html += '<div style="display:flex;flex-direction:column;align-items:center;flex:0 0 auto;">';
            html += '<span style="width:11px;height:11px;border-radius:50%;background:' + color + ';' + (item.state === 'active' ? 'box-shadow:0 0 0 3px #D24C19;' : '') + '"></span>';
            if (!last) html += '<span style="width:2px;flex:1 1 auto;min-height:16px;background:' + (item.state === 'done' ? '#047857' : '#e2e8f0') + ';"></span>';
            html += '</div>';
            html += '<div style="padding-bottom:' + (last ? '0' : '10px') + ';min-width:0;">';
            html += '<div style="font-size:12px;font-weight:600;color:' + textColor + ';">' + esc(item.step.label) + '</div>';
            if (when) html += '<div style="font-size:11px;color:#94a3b8;">' + esc(when) + '</div>';
            html += '</div></li>';
        });

        html += '</ol>';
        return html;
    }

    function timelineMini(o) {
        var items = timelineItems(o);
        var html = '<div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">';

        items.forEach(function (item, i) {
            var color = item.state === 'done' ? '#047857' : (item.state === 'active' ? '#D24C19' : '#cbd5e1');
            var textColor = item.state === 'pending' || item.state === 'off' ? '#94a3b8' : '#475569';
            var when = item.step.at ? fmtTime(item.step.at) : '';

            html += '<span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:' + textColor + ';">'
                + '<span style="width:8px;height:8px;border-radius:50%;background:' + color + ';"></span>'
                + esc(item.step.label) + (when ? ' ' + esc(when) : '') + '</span>';

            if (i < items.length - 1) {
                html += '<span style="color:#e2e8f0;">›</span>';
            }
        });

        html += '</div>';
        return html;
    }

    // ------------------------------------------------------------ acciones

    function actionsFor(o, opts) {
        var mine = !!(opts && opts.mine);
        var pool = !!(opts && opts.pool);
        var acts = ['detail'];

        if (pool && o.status === 'REQUESTED' && (o.order_type === 'EMERGENCY' || o.order_type === 'SCHEDULED')) {
            acts.push('accept');
        }
        if (o.order_type === 'QUOTE' && (o.status === 'REQUESTED' || o.status === 'QUOTED') && (mine || pool)) {
            acts.push('proposal');
        }
        if (mine && o.status === 'ACCEPTED' && (o.order_type === 'SCHEDULED' || o.order_type === 'QUOTE')) {
            acts.push('dispatch');
        }
        if (mine && (o.order_type === 'EMERGENCY' || o.order_type === 'SCHEDULED')
            && ['ACCEPTED', 'PROVIDER_DISPATCHED', 'QUOTED'].indexOf(o.status) >= 0) {
            acts.push('estimate');
        }
        if (mine && ['ACCEPTED', 'PROVIDER_DISPATCHED', 'IN_PROGRESS'].indexOf(o.status) >= 0) {
            acts.push('complete');
        }
        if (mine && o.status === 'COMPLETED') {
            acts.push('rate');
        }

        return acts;
    }

    function actionLabel(action, o) {
        var labels = {
            detail: ['Ver Detalle', '#0c2a4d'],
            accept: [o.order_type === 'SCHEDULED' ? 'Aceptar cita' : 'Aceptar solicitud', '#047857'],
            dispatch: ['Iniciar trabajo / En camino', '#0369a1'],
            estimate: [o.status === 'QUOTED' ? 'Modificar presupuesto' : 'Cargar presupuesto', '#6d28d9'],
            proposal: [o.status === 'QUOTED' ? 'Modificar presupuesto' : 'Proponer presupuesto', '#6d28d9'],
            complete: ['Finalizar servicio', '#047857'],
            rate: ['Calificar cliente', '#D24C19']
        };

        return labels[action] || [action, '#0c2a4d'];
    }

    function actionButtons(o, opts) {
        var acts = actionsFor(o, opts);
        var html = '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">';

        acts.forEach(function (action) {
            var lbl = actionLabel(action, o);
            html += '<button type="button" onclick="window.FdServicioUI.act(\'' + action + '\',' + o.id + ')" style="background:' + lbl[1]
                + ';color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">' + esc(lbl[0]) + '</button>';
        });

        html += '</div>';
        return html;
    }

    // -------------------------------------------------------------- tarjeta

    function serviceCard(o, opts) {
        opts = opts || {};
        registry[o.id] = o;
        registryOpts[o.id] = opts;

        var attachments = attachmentsHtml(o);
        var total = parseFloat(o.total_amount || 0);
        var mineQuote = myQuote(o);
        var quotePrice = mineQuote ? parseFloat(mineQuote.proposed_price || 0) : 0;
        var amount = total > 0 ? total : quotePrice;

        var html = '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:10px;">';
        html += '<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">';
        html += '<div style="min-width:0;">';
        html += '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">';
        html += typeBadge(o) + statusBadge(o);
        if (opts.pool) html += badge('Disponible', '#D24C19');
        html += '<span style="font-size:11px;color:#9ca3af;">#' + esc(o.id) + '</span>';
        html += '</div>';
        html += '<p style="font-size:12px;color:#6b7280;margin:4px 0 0;">Cliente: <strong style="color:#334155;">'
            + esc((o.customer && o.customer.name) || 'Cliente') + '</strong> · ' + esc((o.sub_group && o.sub_group.description) || '') + '</p>';
        html += '</div>';
        html += '<div style="text-align:right;">';
        html += '<p style="font-size:15px;font-weight:700;color:#0f172a;margin:0;">' + (amount > 0 ? money(amount) : 'A cotizar') + '</p>';
        html += '<p style="font-size:11px;color:#6b7280;margin:2px 0 0;">' + esc(fmtDateTime(o.created_at)) + '</p>';
        html += '</div>';
        html += '</div>';

        // Ubicacion y contacto directo.
        html += '<div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:10px;">';
        html += '<div style="min-width:0;flex:1 1 240px;">' + locationBlock(o) + '</div>';
        html += '<div style="flex:0 0 auto;">' + contactButtons(o, true) + '</div>';
        html += '</div>';

        if (o.description) {
            html += '<p style="font-size:12px;color:#374151;margin:10px 0 0;">' + esc(o.description) + '</p>';
        }
        if (o.scheduled_at) {
            html += '<p style="font-size:12px;color:#0c2a4d;margin:6px 0 0;font-weight:600;">Cita: ' + esc(fmtDateTime(o.scheduled_at)) + '</p>';
        }
        if (attachments) {
            html += '<div style="margin-top:10px;">' + attachments + '</div>';
        }

        html += '<div style="margin-top:10px;">' + budgetHtml(o) + '</div>';
        html += '<div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e2e8f0;">' + timelineMini(o) + '</div>';
        html += actionButtons(o, opts);
        html += '</div>';

        return html;
    }

    // ---------------------------------------------------------------- modal

    function detailHtml(o) {
        var html = '';

        html += '<div style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 6px;text-transform:uppercase;letter-spacing:.5px;">Ubicación</div>';
        html += '<div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">';
        html += '<div style="min-width:0;flex:1 1 220px;">' + locationBlock(o) + '</div>';
        html += '<div style="flex:0 0 auto;">' + contactButtons(o, false) + '</div>';
        html += '</div>';

        html += '<div style="height:1px;background:#e5e7eb;margin:14px 0;"></div>';

        html += '<div style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 6px;text-transform:uppercase;letter-spacing:.5px;">Cliente</div>';
        html += '<p style="font-size:13px;color:#0f172a;margin:0;font-weight:600;">' + esc((o.customer && o.customer.name) || 'Cliente') + '</p>';
        if ((o.customer && o.customer.phone) || (o.customer && o.customer.email)) {
            html += '<p style="font-size:12px;color:#475569;margin:2px 0 0;">'
                + esc((o.customer && o.customer.phone) || '') + (o.customer && o.customer.email ? ' · ' + esc(o.customer.email) : '') + '</p>';
        }
        if (o.reminder_channel) {
            html += '<p style="font-size:11px;color:#94a3b8;margin:2px 0 0;">Recordatorio: ' + esc(o.reminder_channel) + '</p>';
        }

        html += '<div style="height:1px;background:#e5e7eb;margin:14px 0;"></div>';

        html += '<div style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 6px;text-transform:uppercase;letter-spacing:.5px;">Descripción de la falla</div>';
        html += '<p style="font-size:13px;color:#374151;margin:0;white-space:pre-line;">'
            + esc(o.description || 'El cliente no cargó una descripción.') + '</p>';

        var attachments = attachmentsHtml(o);
        if (attachments) {
            html += '<div style="height:1px;background:#e5e7eb;margin:14px 0;"></div>';
            html += '<div style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 6px;text-transform:uppercase;letter-spacing:.5px;">Adjuntos del cliente</div>';
            html += attachments;
        }

        html += '<div style="height:1px;background:#e5e7eb;margin:14px 0;"></div>';

        html += '<div style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 6px;text-transform:uppercase;letter-spacing:.5px;">Presupuesto</div>';
        html += budgetHtml(o);

        html += '<div style="height:1px;background:#e5e7eb;margin:14px 0;"></div>';

        html += '<div style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 8px;text-transform:uppercase;letter-spacing:.5px;">Avance</div>';
        html += timelineHtml(o);

        return html;
    }

    function formHtml(o, action) {
        if (action === 'estimate') {
            var price = parseFloat(o.additional_cost || 0) || '';
            return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-top:12px;">'
                + '<div style="font-size:12px;font-weight:700;color:#0c2a4d;margin-bottom:8px;">'
                + (o.status === 'QUOTED' ? 'Modificar el presupuesto enviado' : 'Cargar el presupuesto del trabajo') + '</div>'
                + '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Precio del trabajo ($)</label>'
                + '<input id="fd-so-input-price" type="number" min="0.01" step="0.01" value="' + esc(price) + '" placeholder="0.00" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">'
                + '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin:10px 0 4px;">Detalles (opcional)</label>'
                + '<textarea id="fd-so-input-details" rows="2" placeholder="Repuestos, mano de obra..." style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;">' + esc(o.estimate_details || '') + '</textarea>'
                + '<div style="margin-top:10px;"><button type="button" onclick="window.FdServicioUI.sendEstimate(' + o.id + ')" style="background:#6d28d9;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Enviar presupuesto</button>'
                + '<span id="fd-so-form-msg" style="margin-left:8px;font-size:12px;font-weight:600;"></span></div>'
                + '</div>';
        }

        if (action === 'proposal') {
            var quote = myQuote(o) || (o.quote && o.quote.provider_id ? o.quote : null);
            var proposal = quote ? parseFloat(quote.proposed_price || 0) : 0;
            return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-top:12px;">'
                + '<div style="font-size:12px;font-weight:700;color:#0c2a4d;margin-bottom:8px;">'
                + (o.status === 'QUOTED' ? 'Modificar el presupuesto enviado' : 'Proponer un presupuesto') + '</div>'
                + '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Precio propuesto ($)</label>'
                + '<input id="fd-so-input-price" type="number" min="0.01" step="0.01" value="' + esc(proposal || '') + '" placeholder="0.00" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">'
                + '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin:10px 0 4px;">Tiempo estimado</label>'
                + '<input id="fd-so-input-time" type="text" value="' + esc((quote && quote.estimated_time) || '') + '" placeholder="Ej: 2 horas" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">'
                + '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin:10px 0 4px;">Detalles (opcional)</label>'
                + '<textarea id="fd-so-input-details" rows="2" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;">' + esc((quote && quote.details) || '') + '</textarea>'
                + '<div style="margin-top:10px;"><button type="button" onclick="window.FdServicioUI.sendProposal(' + o.id + ')" style="background:#6d28d9;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Enviar propuesta</button>'
                + '<span id="fd-so-form-msg" style="margin-left:8px;font-size:12px;font-weight:600;"></span></div>'
                + '</div>';
        }

        if (action === 'complete') {
            return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-top:12px;">'
                + '<div style="font-size:12px;font-weight:700;color:#0c2a4d;margin-bottom:8px;">Finalizar el servicio</div>'
                + '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Foto comprobante (jpg, png, webp · máx 5 MB)</label>'
                + '<input id="fd-so-input-photo" type="file" accept="image/*" style="font-size:13px;">'
                + '<div style="margin-top:10px;"><button type="button" onclick="window.FdServicioUI.sendComplete(' + o.id + ')" style="background:#047857;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Confirmar finalización</button>'
                + '<span id="fd-so-form-msg" style="margin-left:8px;font-size:12px;font-weight:600;"></span></div>'
                + '</div>';
        }

        if (action === 'rate') {
            return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-top:12px;">'
                + '<div style="font-size:12px;font-weight:700;color:#0c2a4d;margin-bottom:8px;">Calificar al cliente</div>'
                + '<div id="fd-so-input-stars" style="display:flex;gap:4px;font-size:22px;line-height:1;margin-bottom:10px;">'
                + [1, 2, 3, 4, 5].map(function (n) {
                    return '<button type="button" data-star="' + n + '" onclick="window.FdServicioUI.star(' + n + ')" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:0;">★</button>';
                }).join('')
                + '</div>'
                + '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Comentario (opcional)</label>'
                + '<textarea id="fd-so-input-comment" rows="2" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;"></textarea>'
                + '<div style="margin-top:10px;"><button type="button" onclick="window.FdServicioUI.sendRate(' + o.id + ')" style="background:#D24C19;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Enviar calificación</button>'
                + '<span id="fd-so-form-msg" style="margin-left:8px;font-size:12px;font-weight:600;"></span></div>'
                + '</div>';
        }

        if (action === 'chat') {
            return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-top:12px;">'
                + '<div style="font-size:12px;font-weight:700;color:#0c2a4d;margin-bottom:8px;">Mensaje al cliente</div>'
                + '<textarea id="fd-so-input-chat" rows="3" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;">' + esc(chatText(o)) + '</textarea>'
                + '<div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">'
                + '<button type="button" onclick="window.FdServicioUI.sendChat(' + o.id + ')" style="background:#25D366;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Abrir en WhatsApp</button>'
                + '<button type="button" onclick="window.FdServicioUI.copyChat(' + o.id + ')" style="background:#fff;color:#0c2a4d;border:1px solid #cbd5e1;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Copiar mensaje</button>'
                + '<span id="fd-so-form-msg" style="font-size:12px;font-weight:600;"></span>'
                + '</div></div>';
        }

        return '';
    }

    function formMsg(text, ok) {
        var el = document.getElementById('fd-so-form-msg');
        if (!el) return;
        el.textContent = text || '';
        el.style.color = ok ? '#047857' : '#b91c1c';
    }

    function renderModal(o, action, extra) {
        var modal = document.getElementById('fd-so-modal');
        if (!modal) return;

        var head = document.getElementById('fd-so-modal-badges');
        var title = document.getElementById('fd-so-modal-title');
        var body = document.getElementById('fd-so-modal-body');
        var actions = document.getElementById('fd-so-modal-actions');

        if (head) head.innerHTML = typeBadge(o) + statusBadge(o) + '<span style="font-size:11px;color:#9ca3af;">#' + esc(o.id) + '</span>';
        if (title) title.textContent = 'Solicitud #' + o.id + ' · ' + ((o.sub_group && o.sub_group.description) || 'Servicio');
        if (body) body.innerHTML = detailHtml(o) + (extra && extra.html ? extra.html : '') + formHtml(o, action);
        if (actions) {
            var acts = actionsFor(o, optsFor(o));
            acts = acts.filter(function (a) { return a !== 'detail' && a !== 'chat'; });
            var html = '<button type="button" onclick="window.FdServicioUI.close()" style="background:#fff;color:#64748b;border:1px solid #d1d5db;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Cerrar</button>';
            acts.forEach(function (a) {
                var lbl = actionLabel(a, o);
                html += '<button type="button" onclick="window.FdServicioUI.act(\'' + a + '\',' + o.id + ')" style="background:' + lbl[1]
                    + ';color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">' + esc(lbl[0]) + '</button>';
            });
            actions.innerHTML = html;
        }

        currentOrderId = o.id;
        currentAction = action || null;
        modal.style.display = 'flex';
    }

    function optsFor(o) {
        var opts = registryOpts[o.id];

        if (opts) return opts;

        return { mine: !!o.merchant_id, pool: !o.merchant_id };
    }

    function close() {
        var modal = document.getElementById('fd-so-modal');
        if (modal) modal.style.display = 'none';
        currentOrderId = null;
        currentAction = null;
    }

    function refresh(order) {
        if (order) {
            registry[order.id] = order;
            // Al aceptar, la orden deja el pool y pasa a ser propia: el
            // contexto de las acciones se actualiza con el estado nuevo.
            if (order.merchant_id && order.merchant_id === PROVIDER_ID) {
                registryOpts[order.id] = { mine: true, pool: false };
            } else if (!order.merchant_id) {
                registryOpts[order.id] = { mine: false, pool: true };
            }
        }
        if (window.FdServicioUI.onChanged) window.FdServicioUI.onChanged();
    }

    /**
     * Abre el detalle de la orden y lo refresca con el endpoint de detalle:
     * el listado puede venir de una carga anterior y el estado actual manda.
     */
    function openDetail(orderId) {
        var o = registry[orderId];
        if (!o) return;

        renderModal(o, null);

        fetch(API + '/' + orderId, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (json) {
                var order = json && json.success && json.data ? json.data.order : null;
                if (!order) return;
                registry[orderId] = order;
                // Solo se repinta si el modal sigue mostrando el detalle
                // (no un formulario en curso).
                if (currentOrderId === orderId && !currentAction) {
                    renderModal(order, null);
                }
            })
            .catch(function () {});
    }

    function act(action, orderId) {
        var o = registry[orderId];
        if (!o) return;

        if (action === 'detail') {
            openDetail(orderId);
            return;
        }
        if (action === 'chat') {
            renderModal(o, 'chat');
            return;
        }
        if (action === 'accept') {
            // Cada pagina tiene su propio mensaje de acceptacion (y su
            // recarga), asi que se delega cuando esta enganchado.
            if (typeof window.FdServicioUI.onAccept === 'function') {
                window.FdServicioUI.onAccept(orderId);
                return;
            }
            if (!window.confirm('¿Aceptar esta solicitud?')) return;
            postJson(API + '/' + orderId + '/accept', {}).then(function (json) {
                if (json && json.success) {
                    refresh(json.data && json.data.order);
                } else {
                    formMsg((json && json.message) || 'No se pudo aceptar la solicitud.', false);
                }
            });
            return;
        }
        if (action === 'dispatch') {
            if (!window.confirm('¿Marcar el trabajo como iniciado / en camino?')) return;
            postJson(API + '/' + orderId + '/dispatch', {}).then(function (json) {
                if (json && json.success) {
                    refresh(json.data && json.data.order);
                    renderModal(registry[orderId], null);
                } else {
                    formMsg((json && json.message) || 'No se pudo iniciar el trabajo.', false);
                }
            });
            return;
        }

        renderModal(o, action);
    }

    function sendEstimate(orderId) {
        var o = registry[orderId];
        if (!o) return;
        var input = document.getElementById('fd-so-input-price');
        var details = document.getElementById('fd-so-input-details');
        var cost = parseFloat(input ? input.value : 0);

        if (!cost || cost <= 0) { formMsg('Ingresá un precio válido.', false); return; }

        postJson(API + '/' + orderId + '/onsite-estimate', {
            additional_cost: cost,
            details: (details && details.value) || null
        }).then(function (json) {
            if (json && json.success) {
                refresh(json.data && json.data.order);
                renderModal(registry[orderId], null);
            } else {
                formMsg((json && json.message) || 'No se pudo enviar el presupuesto.', false);
            }
        });
    }

    function sendProposal(orderId) {
        var o = registry[orderId];
        if (!o) return;
        var price = parseFloat((document.getElementById('fd-so-input-price') || {}).value || 0);
        var time = (document.getElementById('fd-so-input-time') || {}).value || '';
        var details = (document.getElementById('fd-so-input-details') || {}).value || '';

        if (!price || price <= 0) { formMsg('Ingresá un precio propuesto válido.', false); return; }
        if (!time) { formMsg('Ingresá el tiempo estimado.', false); return; }

        postJson(API + '/' + orderId + '/quote-proposal', {
            proposed_price: price,
            estimated_time: time,
            details: details || null
        }).then(function (json) {
            if (json && json.success) {
                refresh(json.data && json.data.order);
                renderModal(registry[orderId], null);
            } else {
                formMsg((json && json.message) || 'No se pudo enviar la propuesta.', false);
            }
        });
    }

    function sendComplete(orderId) {
        var input = document.getElementById('fd-so-input-photo');
        var file = input && input.files ? input.files[0] : null;

        if (!file) { formMsg('Adjuntá la foto comprobante.', false); return; }

        var fd = new FormData();
        fd.append('photo', file);

        fetch(API + '/' + orderId + '/complete', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: fd
        }).then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (json) {
                if (json && json.success) {
                    refresh(json.data && json.data.order);
                    renderModal(registry[orderId], null);
                } else {
                    formMsg((json && json.message) || 'No se pudo completar el trabajo.', false);
                }
            });
    }

    var currentStars = 0;

    function star(n) {
        currentStars = n;
        var wrap = document.getElementById('fd-so-input-stars');
        if (!wrap) return;
        Array.prototype.forEach.call(wrap.querySelectorAll('[data-star]'), function (btn) {
            btn.style.color = parseInt(btn.getAttribute('data-star'), 10) <= n ? '#f59e0b' : '#d1d5db';
        });
    }

    function sendRate(orderId) {
        if (currentStars < 1) { formMsg('Elegí una puntuación de 1 a 5.', false); return; }
        var comment = (document.getElementById('fd-so-input-comment') || {}).value || '';

        postJson(API + '/' + orderId + '/rate', { stars: currentStars, comment: comment || null }).then(function (json) {
            if (json && json.success) {
                currentStars = 0;
                refresh(json.data && json.data.order);
                close();
            } else {
                formMsg((json && json.message) || 'No se pudo registrar la calificación.', false);
            }
        });
    }

    function sendChat(orderId) {
        var o = registry[orderId];
        if (!o) return;
        var text = (document.getElementById('fd-so-input-chat') || {}).value || chatText(o);
        var url = waUrl(o, text);

        if (!url) { formMsg('El cliente no tiene un teléfono cargado.', false); return; }

        window.open(url, '_blank');
    }

    function copyChat(orderId) {
        var o = registry[orderId];
        if (!o) return;
        var text = (document.getElementById('fd-so-input-chat') || {}).value || chatText(o);

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                formMsg('Mensaje copiado.', true);
            }).catch(function () {
                formMsg('No se pudo copiar el mensaje.', false);
            });
            return;
        }

        formMsg('Copiá el texto manualmente.', false);
    }

    window.FdServicioUI = {
        serviceCard: serviceCard,
        actionsFor: actionsFor,
        actionButtons: actionButtons,
        timelineHtml: timelineHtml,
        budgetHtml: budgetHtml,
        budgetRows: budgetRows,
        chatText: chatText,
        myQuote: myQuote,
        open: function (orderId) { act('detail', orderId); },
        act: act,
        close: close,
        star: star,
        sendEstimate: sendEstimate,
        sendProposal: sendProposal,
        sendComplete: sendComplete,
        sendRate: sendRate,
        sendChat: sendChat,
        copyChat: copyChat,
        onChanged: null,
        onAccept: null
    };
})();
</script>
