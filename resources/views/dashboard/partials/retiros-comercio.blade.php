{{-- Retiros de los repartidores del comercio: el titular confirma que ya
     pagó (aprobar) o devuelve el monto a la billetera (rechazar). Consume
     los endpoints /profile/provider/withdrawals del propio comercio. --}}
<div class="pcard" style="padding:20px 22px;">
    <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg> Retiros de repartidores</h3>
    <p class="pcard-sub">Solicitudes pendientes de tus repartidores (con entregas para tu comercio o en tu lista de confianza). El saldo ya quedó reservado al solicitar: aprobá para confirmar el pago o rechazá para devolverlo a la billetera.</p>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:14px 0;">
        <span class="pcard-sub" style="margin:0;"><strong id="pv-wd-count">0</strong> pendientes</span>
        <button type="button" class="um-outline-btn" onclick="pvWdLoad()">Actualizar</button>
    </div>
    <div style="overflow-x:auto;">
        <table class="um-table">
            <thead>
                <tr>
                    <th style="width:36%;">Repartidor</th>
                    <th style="width:18%;">Monto</th>
                    <th style="width:22%;">Solicitado</th>
                    <th style="width:24%;text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody id="pv-wd-tbody"></tbody>
        </table>
        <div id="pv-wd-empty" class="um-empty" style="display:none;">No hay retiros pendientes de tus repartidores.</div>
    </div>
    <p id="pv-wd-msg" class="um-msg" style="margin:10px 0 0;"></p>
</div>

<script>
(function () {
    // Base con prefijo del proyecto (http://localhost/pideaca): url() genera
    // la ruta absoluta correcta; una ruta hardcodeada '/profile/...' ignora
    // el prefijo y devuelve 404 HTML que rompe el .json().
    function endpoint() { return '{{ url("/profile/provider/withdrawals") }}'; }

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function date(value) {
        if (!value) return '—';
        var d = new Date(value);
        if (isNaN(d.getTime())) return '—';
        return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function msg(text, ok) {
        var el = document.getElementById('pv-wd-msg');
        if (!el) return;
        el.textContent = text || '';
        el.className = 'um-msg ' + (ok ? 'ok' : 'err');
        if (!text) el.className = 'um-msg';
    }

    window.pvWdLoad = function () {
        var tbody = document.getElementById('pv-wd-tbody');
        if (!tbody) return;

        fetch(endpoint(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                return r.json().catch(function () { return null; }).then(function (data) {
                    return { ok: r.ok, data: data };
                });
            })
            .then(function (res) {
                var data = res.data;

                if (!res.ok || !data || !data.success) {
                    msg((data && data.message) || 'No se pudieron cargar los retiros.', false);
                    return;
                }

                var rows = data.withdrawals || [];
                var count = document.getElementById('pv-wd-count');
                if (count) count.textContent = rows.length;

                var empty = document.getElementById('pv-wd-empty');
                if (empty) empty.style.display = rows.length ? 'none' : 'block';

                var money = (typeof fdMoney === 'function') ? fdMoney : function (v) { return '$ ' + v; };

                tbody.innerHTML = rows.map(function (w) {
                    var user = w.user || {};
                    var name = esc(user.name || 'Repartidor');
                    var email = esc(user.email || '');
                    var method = esc(w.method || '');
                    var notes = esc(w.notes || '');
                    var payDetail = method || notes
                        ? '<div class="um-user-email">' + (method ? 'Método: ' + method : '') + (notes ? '<br>' + notes : '') + '</div>'
                        : '';

                    return '<tr>'
                        + '<td><div class="um-user-name">' + name + '</div>'
                        + (email ? '<div class="um-user-email">' + email + '</div>' : '')
                        + payDetail
                        + '</td>'
                        + '<td><strong>' + money(w.amount) + '</strong></td>'
                        + '<td>' + date(w.created_at) + '</td>'
                        + '<td><div class="um-actions" style="justify-content:flex-end;">'
                        + '<button type="button" class="um-action-btn edit" title="Aprobar retiro" aria-label="Aprobar retiro" onclick="pvWdApprove(' + Number(w.id) + ')">'
                        + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>'
                        + '</button>'
                        + '<button type="button" class="um-action-btn del" title="Rechazar retiro" aria-label="Rechazar retiro" onclick="pvWdReject(' + Number(w.id) + ')">'
                        + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18 18 6M6 6l12 12"/></svg>'
                        + '</button>'
                        + '</div></td>'
                        + '</tr>';
                }).join('');
            })
            .catch(function () {
                msg('Error de conexión al cargar los retiros.', false);
            });
    };

    function action(id, what, body) {
        var token = document.querySelector('meta[name="csrf-token"]');

        return fetch(endpoint() + '/' + id + '/' + what, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.content : ''
            },
            body: JSON.stringify(body || {})
        })
            .then(function (r) {
                return r.json().catch(function () { return null; }).then(function (data) {
                    return { ok: r.ok, data: data };
                });
            })
            .then(function (res) {
                var data = res.data;

                if (res.ok && data && data.success) {
                    msg(data.message || 'Retiro actualizado.', true);
                    pvWdLoad();
                    return;
                }
                msg((data && data.message) || 'No se pudo procesar el retiro.', false);
            })
            .catch(function () {
                msg('Error de conexión.', false);
            });
    }

    window.pvWdApprove = function (id) {
        if (!confirm('¿Confirmás que el pago del retiro ya fue realizado?')) return;
        action(id, 'approve', {});
    };

    window.pvWdReject = function (id) {
        var reason = prompt('Motivo del rechazo (opcional):', '');
        if (reason === null) return;
        action(id, 'reject', { reason: reason });
    };
})();
</script>
