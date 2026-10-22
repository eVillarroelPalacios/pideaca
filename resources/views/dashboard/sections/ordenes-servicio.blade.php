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
        PENDIENTE: 'Pendiente', PRESUPUESTADO: 'Presupuestado', EN_PROCESO: 'En proceso',
        COMPLETADO: 'Completado', CANCELADO: 'Cancelado'
    };

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

    function isPool(o) { return !o.merchant_id; }
    function isMine(o) { return !!o.merchant_id && o.merchant_id === providerId; }

    // La tarjeta operativa (ubicacion, contacto, adjuntos, presupuesto,
    // linea de tiempo y acciones) es la compartida con "Gestionar
    // Solicitudes": vive en dashboard/partials/servicio-operativo.
    function card(o) {
        if (!window.FdServicioUI) return '';
        return window.FdServicioUI.serviceCard(o, { pool: isPool(o), mine: isMine(o) });
    }

    window.fdOsAccept = function (orderId) {
        if (!window.confirm('¿Aceptar este pedido para tu comercio?')) return;
        postJson('{{ url('/api/v1/provider/service-orders') }}/' + orderId + '/accept', {}).then(function (json) {
            if (json && json.success) { message(json.message || 'Pedido aceptado.', true); }
            else { message((json && json.message) || 'No se pudo aceptar el pedido.', false); }
            window.fdOsLoad();
        }).catch(function () { message('No se pudo aceptar el pedido. Intenta nuevamente.', false); });
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

        fetch('{{ url('/api/v1/provider/service-orders') }}?' + qs, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
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
                    document.getElementById('fd-os-pool').innerHTML = pool.map(card).join('');
                    show('fd-os-pool-wrap', true);
                }

                if (mine.length) {
                    document.getElementById('fd-os-list').innerHTML = mine.map(card).join('');
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

    // La vista compartida avisa cuando algo cambia (por ejemplo, tras
    // completar el trabajo) y usa el mensaje de aceptacion de esta pagina.
    if (window.FdServicioUI) {
        window.FdServicioUI.onChanged = function () { window.fdOsLoad(); };
        window.FdServicioUI.onAccept = function (orderId) { window.fdOsAccept(orderId); };
    }

    window.addEventListener('load', function () { window.fdOsLoad(); });
})();
</script>
