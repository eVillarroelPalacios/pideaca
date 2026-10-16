<section id="dash-gestionar-solicitudes" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p id="fd-gs-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">{{ optional($user->provider)->business_name ?: 'Tu comercio' }}</p>
        </div>
        <button type="button" onclick="window.fdGsLoad()" style="background:#D24C19;color:#fff;border:none;padding:8px 14px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
            Actualizar
        </button>
    </div>

    <div id="fd-gs-tabs" style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:14px;flex-wrap:wrap;">
        <button type="button" data-filter="pending" onclick="window.fdGsFilter('pending')" class="fd-gs-tab" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#D24C19;background:none;border:none;border-bottom:2px solid #D24C19;margin-bottom:-2px;cursor:pointer;">
            Pendientes
            <span id="fd-gs-count-pending" style="background:#D24C19;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
        </button>
        <button type="button" data-filter="confirmed" onclick="window.fdGsFilter('confirmed')" class="fd-gs-tab" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;">
            Confirmados
        </button>
        <button type="button" data-filter="cancelled" onclick="window.fdGsFilter('cancelled')" class="fd-gs-tab" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;">
            Cancelados / rechazados
        </button>
        <button type="button" data-filter="all" onclick="window.fdGsFilter('all')" class="fd-gs-tab" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;">
            Todos
        </button>
    </div>

    <div id="fd-gs-message" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:12px;border-radius:6px;margin-bottom:12px;">
        <p id="fd-gs-message-text" style="font-size:13px;color:#374151;margin:0;white-space:pre-line;"></p>
    </div>

    <div id="fd-gs-loading" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:40px;text-align:center;border-radius:6px;">
        <p style="font-size:13px;color:#6b7280;margin:0;">Cargando solicitudes...</p>
    </div>

    <div id="fd-gs-empty" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;border-radius:6px;">
        <p style="font-size:14px;color:#6b7280;margin:0;">No hay solicitudes en esta vista.</p>
    </div>

    <div id="fd-gs-list" style="display:none;"></div>
</section>

<script>
(function () {
    var sec = document.getElementById('dash-gestionar-solicitudes');
    if (!sec) return;

    var providerId = sec.getAttribute('data-provider-id');
    var currentFilter = 'pending';
    var STATUS_LABEL = {
        pending: 'Pendiente',
        confirmed: 'Confirmado',
        in_preparation: 'En preparación',
        on_the_way: 'En camino',
        delivered: 'Entregado',
        cancelled: 'Cancelado'
    };

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

    function show(id, on) {
        var el = document.getElementById(id);
        if (el) el.style.display = on ? '' : 'none';
    }

    function message(text, ok) {
        var box = document.getElementById('fd-gs-message');
        var txt = document.getElementById('fd-gs-message-text');
        if (!box || !txt) return;
        if (!text) { box.style.display = 'none'; return; }
        txt.textContent = text;
        box.style.background = ok ? '#ecfdf5' : '#fff1f2';
        box.style.borderColor = ok ? '#a7f3d0' : '#fecdd3';
        box.style.display = '';
    }

    function statusBadge(status) {
        var color = { pending: '#b45309', confirmed: '#047857', in_preparation: '#1d4ed8', on_the_way: '#6d28d9', delivered: '#047857', cancelled: '#b91c1c' }[status] || '#6b7280';
        return '<span style="display:inline-block;font-size:11px;font-weight:700;color:' + color + ';background:' + color + '1a;border-radius:9999px;padding:2px 9px;">' + esc(STATUS_LABEL[status] || status) + '</span>';
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

    function card(order) {
        var pending = order.status === 'pending';
        var actions = '';
        if (pending) {
            var acceptBtn = '<button type="button" onclick="window.fdGsDecide(' + order.id + ', \'confirmed\')" style="background:#047857;color:#fff;border:none;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Aceptar</button>';
            var rejectBtn = '<button type="button" onclick="window.fdGsDecide(' + order.id + ', \'cancelled\')" style="background:#fff;color:#b91c1c;border:1px solid #fca5a5;padding:9px 16px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Rechazar</button>';
            actions = '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;">' + acceptBtn + rejectBtn + '</div>';
        }

        return '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:10px;">' +
            '<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">' +
                '<div style="min-width:0;">' +
                    '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">' +
                        '<strong style="font-size:14px;color:#0f172a;">' + esc(order.order_number) + '</strong>' +
                        statusBadge(order.status) +
                    '</div>' +
                    '<p style="font-size:12px;color:#6b7280;margin:4px 0 0;">' + esc((order.user && order.user.name) || 'Cliente') + ' · ' + esc(addressText(order.address)) + '</p>' +
                '</div>' +
                '<div style="text-align:right;">' +
                    '<p style="font-size:15px;font-weight:700;color:#0f172a;margin:0;">' + money(order.total) + '</p>' +
                    '<p style="font-size:11px;color:#6b7280;margin:2px 0 0;">' + esc(new Date(order.created_at).toLocaleString('es-AR')) + '</p>' +
                '</div>' +
            '</div>' +
            '<p style="font-size:12px;color:#374151;margin:10px 0 0;">' + esc(itemsSummary(order.items)) + '</p>' +
            (order.notes ? '<p style="font-size:12px;color:#6b7280;margin:6px 0 0;font-style:italic;">Nota: ' + esc(order.notes) + '</p>' : '') +
            actions +
        '</div>';
    }

    window.fdGsFilter = function (filter) {
        currentFilter = filter;
        Array.prototype.forEach.call(sec.querySelectorAll('.fd-gs-tab'), function (tab) {
            var active = tab.getAttribute('data-filter') === filter;
            tab.style.color = active ? '#D24C19' : '#64748b';
            tab.style.borderBottomColor = active ? '#D24C19' : 'transparent';
        });
        window.fdGsLoad();
    };

    window.fdGsDecide = function (orderId, status) {
        var label = status === 'confirmed' ? 'aceptar' : 'rechazar';
        if (!window.confirm('¿Seguro que querés ' + label + ' esta solicitud?')) return;

        var url = '/api/orders/' + orderId + '/status';
        var meta = document.querySelector('meta[name="csrf-token"]');
        var opts = { method: 'PATCH', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': meta ? meta.content : '' }, body: JSON.stringify({ status: status }) };

        fetch(url, opts).then(function (res) { return res.json().catch(function () { return {}; }); }).then(function (json) {
            if (json && json.success) {
                message(status === 'confirmed' ? 'Solicitud aceptada. Ya podés verla en la página "Pedidos".' : 'Solicitud rechazada y cancelada.', true);
            } else {
                message((json && json.message) || 'No se pudo actualizar la solicitud.', false);
            }
            window.fdGsLoad();
        }).catch(function () {
            message('No se pudo actualizar la solicitud. Intenta nuevamente.', false);
        });
    };

    window.fdGsLoad = function () {
        if (!providerId) {
            show('fd-gs-loading', false);
            show('fd-gs-list', false);
            show('fd-gs-empty', false);
            message('Tu usuario aún no tiene un comercio asociado. Completá tu perfil de prestador para gestionar solicitudes.', false);
            return;
        }

        show('fd-gs-message', false);
        show('fd-gs-empty', false);
        show('fd-gs-loading', true);

        var params = 'per_page=50';
        if (currentFilter !== 'all') params += '&status=' + encodeURIComponent(currentFilter);
        fetch('/api/providers/' + providerId + '/orders?' + params, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json().catch(function () { return {}; }); })
            .then(function (json) {
                show('fd-gs-loading', false);

                if (!json || !json.success) {
                    show('fd-gs-list', false);
                    show('fd-gs-empty', true);
                    message((json && json.message) || 'No se pudieron cargar las solicitudes.', false);
                    return;
                }

                var orders = json.orders || [];
                var list = document.getElementById('fd-gs-list');
                var badge = document.getElementById('fd-gs-count-pending');

                var pendingCount = orders.filter(function (o) { return o.status === 'pending'; }).length;
                if (currentFilter === 'pending' && badge) {
                    badge.textContent = String(pendingCount);
                    badge.style.display = pendingCount > 0 ? '' : 'none';
                }

                if (!orders.length) {
                    list.style.display = 'none';
                    show('fd-gs-empty', true);
                    return;
                }

                list.innerHTML = orders.map(card).join('');
                list.style.display = '';
            })
            .catch(function () {
                show('fd-gs-loading', false);
                show('fd-gs-list', false);
                show('fd-gs-empty', true);
                message('No se pudieron cargar las solicitudes. Intenta nuevamente.', false);
            });
    };

    window.addEventListener('load', function () {
        window.fdGsFilter('pending');
    });
})();
</script>
