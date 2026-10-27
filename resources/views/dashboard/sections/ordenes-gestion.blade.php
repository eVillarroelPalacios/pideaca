<section id="dash-ordenes-gestion" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Gestiona y consulta el historial de tus servicios en un solo lugar.</p>
        </div>
        <button type="button" onclick="window.fdOgLoad()" style="background:#D24C19;color:#fff;border:none;padding:8px 14px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
            Actualizar
        </button>
    </div>

    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
        <div id="fd-og-tabs" style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;flex-wrap:wrap;">
            <button type="button" data-group="all" onclick="window.fdOgTab('all')" class="fd-og-tab" style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;font-size:13px;font-weight:600;color:#D24C19;background:none;border:none;border-bottom:2px solid #D24C19;margin-bottom:-2px;cursor:pointer;">
                Todas las órdenes
                <span id="fd-og-count-all" style="background:#D24C19;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
            </button>
            <button type="button" data-group="pending" onclick="window.fdOgTab('pending')" class="fd-og-tab" style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;">
                Pendientes
                <span id="fd-og-count-pending" style="background:#b45309;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
            </button>
            <button type="button" data-group="confirmed" onclick="window.fdOgTab('confirmed')" class="fd-og-tab" style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;">
                Confirmados
                <span id="fd-og-count-confirmed" style="background:#047857;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
            </button>
            <button type="button" data-group="completed" onclick="window.fdOgTab('completed')" class="fd-og-tab" style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;">
                Completados
                <span id="fd-og-count-completed" style="background:#047857;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
            </button>
            <button type="button" data-group="cancelled" onclick="window.fdOgTab('cancelled')" class="fd-og-tab" style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;">
                Cancelados
                <span id="fd-og-count-cancelled" style="background:#b91c1c;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
            </button>
        </div>

        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <input type="search" id="fd-og-search" oninput="window.fdOgSearch()" placeholder="Buscar por cliente o ID..." style="padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;min-width:200px;" />
            <div id="fd-og-quick-wrap" style="position:relative;">
                <button type="button" onclick="window.fdOgQuickToggle()" id="fd-og-quick-btn" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;background:#fff;color:#374151;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    Filtros rápidos
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div id="fd-og-quick-menu" style="display:none;position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #e5e7eb;border-radius:6px;box-shadow:0 8px 24px rgba(15,23,42,.12);z-index:30;min-width:180px;padding:6px;">
                    <button type="button" data-quick="all" onclick="window.fdOgQuick('all')" class="fd-og-quick-item" style="display:block;width:100%;text-align:left;padding:8px 10px;font-size:12px;border:none;background:none;border-radius:4px;cursor:pointer;color:#374151;">Todo</button>
                    <button type="button" data-quick="today" onclick="window.fdOgQuick('today')" class="fd-og-quick-item" style="display:block;width:100%;text-align:left;padding:8px 10px;font-size:12px;border:none;background:none;border-radius:4px;cursor:pointer;color:#374151;">Hoy</button>
                    <button type="button" data-quick="week" onclick="window.fdOgQuick('week')" class="fd-og-quick-item" style="display:block;width:100%;text-align:left;padding:8px 10px;font-size:12px;border:none;background:none;border-radius:4px;cursor:pointer;color:#374151;">Últimos 7 días</button>
                    <button type="button" data-quick="month" onclick="window.fdOgQuick('month')" class="fd-og-quick-item" style="display:block;width:100%;text-align:left;padding:8px 10px;font-size:12px;border:none;background:none;border-radius:4px;cursor:pointer;color:#374151;">Últimos 30 días</button>
                    <div style="height:1px;background:#e5e7eb;margin:4px 6px;"></div>
                    <button type="button" data-quick="emergency" onclick="window.fdOgQuick('emergency')" class="fd-og-quick-item" style="display:block;width:100%;text-align:left;padding:8px 10px;font-size:12px;border:none;background:none;border-radius:4px;cursor:pointer;color:#374151;">Emergencias</button>
                    <button type="button" data-quick="quote" onclick="window.fdOgQuick('quote')" class="fd-og-quick-item" style="display:block;width:100%;text-align:left;padding:8px 10px;font-size:12px;border:none;background:none;border-radius:4px;cursor:pointer;color:#374151;">Presupuestos</button>
                </div>
            </div>
        </div>
    </div>

    <div id="fd-og-message" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:12px;border-radius:6px;margin-bottom:12px;">
        <p id="fd-og-message-text" style="font-size:13px;color:#374151;margin:0;white-space:pre-line;"></p>
    </div>

    <div id="fd-og-loading" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:40px;text-align:center;border-radius:6px;">
        <p style="font-size:13px;color:#6b7280;margin:0;">Cargando órdenes...</p>
    </div>

    <div id="fd-og-empty" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;border-radius:6px;">
        <p id="fd-og-empty-text" style="font-size:14px;color:#6b7280;margin:0;">Todavía no hay órdenes.</p>
    </div>

    <div id="fd-og-list" style="display:none;"></div>

    <p id="fd-og-foot" style="display:none;font-size:12px;color:#6b7280;text-align:center;margin:12px 0 0;"></p>
</section>

<script>
(function () {
    var sec = document.getElementById('dash-ordenes-gestion');
    if (!sec) return;

    var providerId = sec.getAttribute('data-provider-id') || '';
    var currentTab = 'all';
    var currentQuick = 'all';
    var lastTotal = 0;
    var searchTimer = null;

    var GROUPS = ['all', 'pending', 'confirmed', 'completed', 'cancelled'];
    var QUICK_LABEL = {
        all: 'Filtros rápidos',
        today: 'Filtro: hoy',
        week: 'Filtro: 7 días',
        month: 'Filtro: 30 días',
        emergency: 'Filtro: emergencias',
        quote: 'Filtro: presupuestos'
    };
    var SERVICE_GROUP = {
        REQUESTED: 'pending',
        QUOTED: 'pending',
        ACCEPTED: 'confirmed',
        PROVIDER_DISPATCHED: 'confirmed',
        IN_PROGRESS: 'confirmed',
        COMPLETED: 'completed',
        CANCELLED: 'cancelled'
    };
    var COMMERCE_GROUP = {
        pending: 'pending',
        confirmed: 'confirmed',
        in_preparation: 'confirmed',
        on_the_way: 'confirmed',
        delivered: 'completed',
        cancelled: 'cancelled'
    };
    var COMMERCE_STATUS_LABEL = {
        pending: 'Pendiente',
        confirmed: 'Confirmado',
        in_preparation: 'En preparación',
        on_the_way: 'En camino',
        delivered: 'Entregado',
        cancelled: 'Cancelado'
    };
    var GROUP_COLOR = { pending: '#b45309', confirmed: '#047857', completed: '#047857', cancelled: '#b91c1c' };

    function esc(value) {
        if (window.escapeHtml) return window.escapeHtml(value);
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function money(value) {
        var n = parseFloat(value || 0);
        return '$' + n.toLocaleString('es-AR', { maximumFractionDigits: 0 });
    }

    function fmtDate(iso) {
        var d = new Date(iso);
        return isNaN(d.getTime()) ? '' : d.toLocaleString('es-AR');
    }

    function show(id, on) {
        var el = document.getElementById(id);
        if (el) el.style.display = on ? '' : 'none';
    }

    function message(text, ok) {
        var box = document.getElementById('fd-og-message');
        var txt = document.getElementById('fd-og-message-text');
        if (!box || !txt) return;
        if (!text) { box.style.display = 'none'; return; }
        txt.textContent = text;
        box.style.background = ok ? '#ecfdf5' : '#fff1f2';
        box.style.borderColor = ok ? '#a7f3d0' : '#fecdd3';
        box.style.display = '';
    }

    function groupBadge(group, label) {
        var color = GROUP_COLOR[group] || '#6b7280';
        return '<span style="display:inline-block;font-size:11px;font-weight:700;color:' + color + ';background:' + color + '1a;border-radius:9999px;padding:2px 9px;">' + esc(label) + '</span>';
    }

    function itemsSummary(items) {
        if (!items || !items.length) return '';
        return items.map(function (i) {
            var label = i.quantity + '× ' + (i.product_name || '');
            if (i.variant_name) label += ' (' + i.variant_name + ')';
            return label;
        }).join(' · ');
    }

    function addressText(address) {
        if (!address) return '';
        var text = (address.street || '') + ' ' + (address.number || '');
        if (address.floor_apartment) text += ' — ' + address.floor_apartment;
        return text.trim();
    }

    // Contactos del pedido agrupados bajo el bloque de fecha/monto.
    function commerceContacts(order) {
        var user = order.user || {};
        var html = '<div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;margin-top:6px;">';

        if (user.email) {
            html += '<a href="mailto:' + esc(user.email) + '?subject=' + encodeURIComponent('Pedido ' + (order.order_number || order.id)) + '" style="background:#fff;color:#0c2a4d;border:1px solid #cbd5e1;padding:5px 10px;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;">Email</a>';
        }
        if (user.phone) {
            var digits = String(user.phone).replace(/[^0-9]/g, '');
            html += '<a href="https://wa.me/' + esc(digits) + '" target="_blank" rel="noopener" style="background:#fff;color:#047857;border:1px solid #a7f3d0;padding:5px 10px;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;">WhatsApp</a>';
        }

        return html + '</div>';
    }

    // Repartidor asignado al pedido de comercio.
    function commerceDriver(order) {
        var driver = order.driver || order.deliveryUser || null;
        if (!driver && order.driver_id) {
            driver = order.driver_id ? { name: 'Repartidor asignado', phone: null, email: null } : null;
        }
        if (!driver && !order.driver_id) {
            return '<div style="margin-top:12px;padding:10px 12px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:6px;display:flex;align-items:center;gap:10px;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>'
                + '<span style="font-size:12px;color:#64748b;font-weight:600;">Esperando asignación de repartidor</span>'
                + '</div>';
        }

        var status = order.delivery_status || '';
        var statusLabel = status === 'picked_up' ? 'En camino a entregar' : (status === 'on_the_way' ? 'En viaje' : (status === 'assigned' ? 'En camino a retirar' : 'En proceso'));
        var html = '<div style="margin-top:12px;padding:12px 14px;background:linear-gradient(90deg,#f0fdf4 0%,#ffffff 100%);border:1px solid #86efac;border-radius:6px;">'
            + '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">'
            +   '<div style="display:flex;align-items:center;gap:10px;">'
            +     '<div style="width:34px;height:34px;border-radius:50%;background:#16a34a;display:flex;align-items:center;justify-content:center;color:#fff;">'
            +       '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>'
            +     '</div>'
            +     '<div>'
            +       '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
            +         '<span style="font-size:12px;font-weight:800;color:#166534;">Repartidor asignado</span>'
            +         '<span style="font-size:11px;font-weight:700;color:#166534;background:#dcfce7;padding:3px 8px;border-radius:9999px;">' + esc(statusLabel) + '</span>'
            +       '</div>'
            +       '<div style="font-size:13px;font-weight:700;color:#0f172a;margin-top:2px;">' + esc((driver && driver.name) ? driver.name : 'Carlos Flores') + '</div>'
            +     '</div>'
            +   '</div>';
        if (driver && driver.phone) {
            var d = String(driver.phone).replace(/[^0-9]/g, '');
            html +=   '<a href="https://wa.me/' + esc(d) + '" target="_blank" rel="noopener" style="background:#22c55e;color:#fff;padding:7px 12px;border-radius:4px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">'
                +     '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M20.52 3.48A11.94 11.94 0 0 0 12 0C5.37 0 0 5.37 0 12a11.94 11.94 0 0 0 1.64 6L0 24l6-1.64A11.94 11.94 0 0 0 12 24c6.63 0 12-5.37 12-12 0-3.19-1.24-6.19-3.48-8.52zM12 22a9.94 9.94 0 0 1-5.06-1.38l-.36-.21-3.56.94.95-3.47-.23-.37A9.94 9.94 0 0 1 2 12C2 6.48 6.48 2 12 2s10 4.48 10 10-4.48 10-10 10zm5.07-7.75c-.28-.14-1.65-.81-1.9-.9s-.44-.14-.63.14-.72.9-.88 1.08-.32.21-.6.07a8.18 8.18 0 0 1-2.4-1.48 9.05 9.05 0 0 1-1.67-2.08c-.17-.29 0-.45.13-.59s.28-.32.42-.48.19-.28.28-.46.05-.35-.02-.49-.63-1.52-.86-2.08-.46-.54-.63-.54h-.54a1.04 1.04 0 0 0-.75.35A3.15 3.15 0 0 0 7 9.37c0 1.1.8 2.16.91 2.31a10.29 10.29 0 0 0 3.95 3.69c.55.24.98.38 1.32.49a3.18 3.18 0 0 0 1.46.09c.45-.07 1.65-.67 1.88-1.32s.23-1.21.16-1.32-.25-.18-.53-.32z"/></svg>'
                +     'WhatsApp'
                +   '</a>';
        }
        html += '</div>';
        if (driver && driver.phone) {
            html += '<div style="margin-top:4px;font-size:12px;color:#475569;">Teléfono: ' + esc(driver.phone) + '</div>';
        }
        if (driver && driver.email) {
            html += '<div style="font-size:12px;color:#475569;">Correo: ' + esc(driver.email) + '</div>';
        }
        html += '</div>';
        return html;
    }

    // Stepper de seguimiento del pedido del comercio al pie de la tarjeta.
    function commerceSteps(order) {
        var flow = [
            ['pending', 'Recibido'],
            ['confirmed', 'Confirmado'],
            ['in_preparation', 'En preparación'],
            ['on_the_way', 'En camino'],
            ['delivered', 'Entregado']
        ];

        if (order.status === 'cancelled') {
            return '<div style="display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;color:#b91c1c;">'
                + '<span style="width:8px;height:8px;border-radius:50%;background:#b91c1c;"></span>Pedido cancelado</div>';
        }

        var idx = 0;
        flow.forEach(function (step, i) { if (order.status === step[0]) idx = i; });

        var html = '<div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">';
        flow.forEach(function (step, i) {
            var state = i < idx ? 'done' : (i === idx ? 'active' : 'pending');
            var color = state === 'done' ? '#047857' : (state === 'active' ? '#D24C19' : '#cbd5e1');
            var textColor = state === 'pending' ? '#94a3b8' : '#475569';

            html += '<span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:' + textColor + ';">'
                + '<span style="width:8px;height:8px;border-radius:50%;background:' + color + ';"></span>' + esc(step[1]) + '</span>';
            if (i < flow.length - 1) html += '<span style="color:#e2e8f0;">›</span>';
        });

        return html + '</div>';
    }

    // Desglose financiero del pedido: subtotal, envio, descuento, pagos y
    // medio de pago (el total va destacado junto a la fecha).
    function commerceFinance(order) {
        var rows = '';
        var subtotal = parseFloat(order.subtotal || 0);
        var delivery = parseFloat(order.delivery_fee || 0);
        var discount = parseFloat(order.discount || 0);

        if (subtotal > 0) {
            rows += '<div style="display:flex;justify-content:space-between;gap:10px;font-size:12px;color:#475569;"><span>Subtotal</span><span>' + money(subtotal) + '</span></div>';
        }
        if (delivery > 0) {
            rows += '<div style="display:flex;justify-content:space-between;gap:10px;font-size:12px;color:#475569;"><span>Envío</span><span>' + money(delivery) + '</span></div>';
        }
        if (discount > 0) {
            rows += '<div style="display:flex;justify-content:space-between;gap:10px;font-size:12px;color:#475569;"><span>Descuento</span><span>-' + money(discount) + '</span></div>';
        }
        if (order.payments && order.payments.length) {
            var paid = order.payments.reduce(function (sum, p) { return sum + parseFloat(p.amount || 0); }, 0);
            rows += '<div style="display:flex;justify-content:space-between;gap:10px;font-size:12px;color:#047857;"><span>Pagado</span><span>' + money(paid) + '</span></div>';
        }
        if (!rows) return '';

        return '<div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e2e8f0;display:flex;flex-direction:column;gap:4px;">'
            + rows
            + (order.payment_method ? '<p style="font-size:11px;color:#6b7280;margin:4px 0 0;">Medio de pago: ' + esc(order.payment_method) + '</p>' : '')
            + '</div>';
    }

    function commerceCard(order) {
        var group = COMMERCE_GROUP[order.status] || 'pending';
        var pending = order.status === 'pending';
        var actions = '';
        if (pending) {
            var acceptBtn = '<button type="button" onclick="window.fdOgDecide(' + order.id + ', \'confirmed\')" style="background:#047857;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Aceptar</button>';
            var rejectBtn = '<button type="button" onclick="window.fdOgDecide(' + order.id + ', \'cancelled\')" style="background:#fff;color:#b91c1c;border:1px solid #fca5a5;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Rechazar</button>';
            actions = '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">' + acceptBtn + rejectBtn + '</div>';
        }

        return '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:10px;">' +
            '<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">' +
                '<div style="min-width:0;">' +
                    '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">' +
                        '<strong style="font-size:14px;color:#0f172a;">' + esc(order.order_number || '#' + order.id) + '</strong>' +
                        groupBadge(group, COMMERCE_STATUS_LABEL[order.status] || order.status) +
                    '</div>' +
                    '<p style="font-size:12px;color:#6b7280;margin:4px 0 0;">' + esc((order.user && order.user.name) || 'Cliente') + ' · ' + esc(addressText(order.address)) + '</p>' +
                '</div>' +
                '<div style="text-align:right;">' +
                    '<p style="font-size:17px;font-weight:700;color:#0f172a;margin:0;">' + money(order.total) + '</p>' +
                    '<p style="font-size:11px;color:#6b7280;margin:2px 0 0;">' + esc(fmtDate(order.created_at)) + '</p>' +
                    commerceContacts(order) +
                '</div>' +
            '</div>' +
            '<p style="font-size:12px;color:#374151;margin:10px 0 0;">' + esc(itemsSummary(order.items)) + '</p>' +
            (order.notes ? '<p style="font-size:12px;color:#6b7280;margin:6px 0 0;font-style:italic;">Nota: ' + esc(order.notes) + '</p>' : '') +
            commerceFinance(order) +
            '<div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e2e8f0;">' + commerceSteps(order) + '</div>' +
            commerceDriver(order) +
            actions +
        '</div>';
    }

    // Las ordenes de servicio usan la tarjeta operativa compartida
    // (ubicacion, contacto, adjuntos, presupuesto con seña retenida,
    // linea de tiempo y acciones del prestador).
    function serviceCard(order) {
        if (!window.FdServicioUI) return '';
        return window.FdServicioUI.serviceCard(order, {
            pool: !order.merchant_id,
            mine: !!order.merchant_id
        });
    }

    function renderItem(item) {
        return item.source === 'service' ? serviceCard(item.raw) : commerceCard(item.raw);
    }

    function updateCounts(counts) {
        GROUPS.forEach(function (group) {
            var badge = document.getElementById('fd-og-count-' + group);
            if (!badge) return;
            var total = parseInt((counts || {})[group], 10) || 0;
            badge.textContent = String(total);
            badge.style.display = total > 0 ? '' : 'none';
        });
    }

    window.fdOgTab = function (group) {
        currentTab = group;
        Array.prototype.forEach.call(sec.querySelectorAll('.fd-og-tab'), function (tab) {
            var active = tab.getAttribute('data-group') === group;
            tab.style.color = active ? '#D24C19' : '#64748b';
            tab.style.borderBottomColor = active ? '#D24C19' : 'transparent';
        });
        window.fdOgLoad();
    };

    window.fdOgSearch = function () {
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { window.fdOgLoad(); }, 350);
    };

    function closeQuick() {
        var menu = document.getElementById('fd-og-quick-menu');
        if (menu) menu.style.display = 'none';
    }

    window.fdOgQuickToggle = function () {
        var menu = document.getElementById('fd-og-quick-menu');
        if (menu) menu.style.display = menu.style.display === 'none' ? '' : 'none';
    };

    window.fdOgQuick = function (quick) {
        currentQuick = quick;
        var btn = document.getElementById('fd-og-quick-btn');
        if (btn) btn.childNodes[0].nodeValue = QUICK_LABEL[quick] || QUICK_LABEL.all;
        Array.prototype.forEach.call(sec.querySelectorAll('.fd-og-quick-item'), function (item) {
            var active = item.getAttribute('data-quick') === quick;
            item.style.background = active ? '#fff1eb' : 'none';
            item.style.color = active ? '#D24C19' : '#374151';
            item.style.fontWeight = active ? '700' : '400';
        });
        closeQuick();
        window.fdOgLoad();
    };

    document.addEventListener('click', function (e) {
        var wrap = document.getElementById('fd-og-quick-wrap');
        if (wrap && !wrap.contains(e.target)) closeQuick();
    });

    window.fdOgDecide = function (orderId, status) {
        var label = status === 'confirmed' ? 'aceptar' : 'rechazar';
        if (!window.confirm('¿Seguro que querés ' + label + ' este pedido?')) return;

        var url = '{{ url('/api/orders') }}/' + orderId + '/status';
        var meta = document.querySelector('meta[name="csrf-token"]');
        var opts = { method: 'PATCH', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': meta ? meta.content : '' }, body: JSON.stringify({ status: status }) };

        fetch(url, opts).then(function (res) { return res.json().catch(function () { return {}; }); }).then(function (json) {
            if (json && (json.success || json.ok)) {
                message(status === 'confirmed' ? 'Pedido aceptado.' : 'Pedido rechazado y cancelado.', true);
            } else {
                message((json && json.message) || 'No se pudo actualizar el pedido.', false);
            }
            window.fdOgLoad();
        }).catch(function () {
            message('No se pudo actualizar el pedido. Intenta nuevamente.', false);
        });
    };

    window.fdOgAccept = function (orderId) {
        if (!window.confirm('¿Seguro que querés aceptar esta solicitud para tu comercio?')) return;

        var meta = document.querySelector('meta[name="csrf-token"]');
        var opts = { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': meta ? meta.content : '' }, body: JSON.stringify({}) };
        var url = '{{ url('/api/v1/provider/service-orders') }}/' + orderId + '/accept';

        fetch(url, opts).then(function (res) { return res.json().catch(function () { return {}; }); }).then(function (json) {
            if (json && json.success) {
                message(json.message || 'Solicitud aceptada.', true);
            } else {
                message((json && json.message) || 'No se pudo aceptar la solicitud.', false);
            }
            window.fdOgLoad();
        }).catch(function () {
            message('No se pudo aceptar la solicitud. Intenta nuevamente.', false);
        });
    };

    window.fdOgLoad = function (silent) {
        if (!providerId) {
            show('fd-og-loading', false);
            show('fd-og-list', false);
            show('fd-og-empty', false);
            show('fd-og-foot', false);
            message('Tu usuario aún no tiene un comercio asociado. Completá tu perfil de prestador para gestionar tus órdenes.', false);
            return;
        }

        if (!silent) {
            show('fd-og-message', false);
            show('fd-og-empty', false);
            show('fd-og-loading', true);
        }

        var params = ['per_page=100'];
        if (currentTab !== 'all') params.push('group=' + encodeURIComponent(currentTab));
        if (currentQuick !== 'all') params.push('quick=' + encodeURIComponent(currentQuick));
        var search = (document.getElementById('fd-og-search') || {}).value || '';
        search = search.trim();
        if (search) params.push('q=' + encodeURIComponent(search));

        fetch('{{ url('/api/v1/provider/ordenes-gestion') }}?' + params.join('&'), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json().catch(function () { return {}; }); })
            .then(function (json) {
                show('fd-og-loading', false);
                if (!json || !json.success) {
                    show('fd-og-list', false);
                    show('fd-og-empty', true);
                    if (!silent) message((json && json.message) || 'No se pudieron cargar las órdenes.', false);
                    return;
                }

                var data = json.data || {};
                var items = data.items || [];
                lastTotal = parseInt(data.total, 10) || 0;
                updateCounts(data.counts);

                if (!items.length) {
                    document.getElementById('fd-og-list').style.display = 'none';
                    show('fd-og-foot', false);
                    var empty = document.getElementById('fd-og-empty-text');
                    if (empty) {
                        empty.textContent = lastTotal === 0 && currentTab === 'all' && currentQuick === 'all' && !search
                            ? 'Todavía no hay órdenes. Cuando recibas solicitudes o pedidos van a aparecer acá.'
                            : 'Ninguna orden coincide con los filtros actuales.';
                    }
                    show('fd-og-empty', true);
                    return;
                }

                var list = document.getElementById('fd-og-list');
                list.innerHTML = items.map(renderItem).join('');
                list.style.display = '';

                var foot = document.getElementById('fd-og-foot');
                if (foot) {
                    foot.textContent = items.length < lastTotal
                        ? 'Mostrando las ' + items.length + ' órdenes más recientes de ' + lastTotal + '.'
                        : items.length + (items.length === 1 ? ' orden' : ' órdenes');
                    foot.style.display = '';
                }
            })
            .catch(function () {
                show('fd-og-loading', false);
                show('fd-og-list', false);
                show('fd-og-empty', true);
                if (!silent) message('No se pudieron cargar las órdenes. Intenta nuevamente.', false);
            });
    };

    // La vista compartida avisa cuando algo cambia (por ejemplo, tras
    // completar el trabajo). Si otra pagina enganchó el handler antes, se
    // encadena para no romper su recarga.
    if (window.FdServicioUI) {
        var prevChanged = window.FdServicioUI.onChanged;
        var prevAccept = window.FdServicioUI.onAccept;
        window.FdServicioUI.onChanged = function () {
            window.fdOgLoad(true);
            if (typeof prevChanged === 'function') prevChanged();
        };
        window.FdServicioUI.onAccept = function (orderId) {
            if (typeof prevAccept === 'function') { prevAccept(orderId); return; }
            window.fdOgAccept(orderId);
        };
    }

    window.addEventListener('load', function () {
        window.fdOgLoad(true);
    });
})();
</script>
