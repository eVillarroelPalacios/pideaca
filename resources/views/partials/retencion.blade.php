{{-- Reglas de retencion del comercio: el motor las corre todos los dias. --}}
<section id="dash-{{ $page->url }}" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1000px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Avisos automáticos para que ningún cliente se te escape</p>
        </div>
        <button type="button" onclick="loadRetention()" title="Actualizar retención" aria-label="Actualizar retención" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:8px 10px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg></button>
    </div>

    <div id="ret-banner" style="display:none;background:#eff6ff;border-left:3px solid #2563eb;padding:14px 16px;margin-bottom:18px;font-size:13px;color:#1e3a8a;">
        Las campañas se revisan todos los días y solo se envían a los clientes que correspondan. Podés desactivar cualquiera en cualquier momento.
    </div>

    <div id="ret-loading" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:40px;text-align:center;">
        <p style="font-size:13px;color:#6b7280;margin:0;">Cargando reglas...</p>
    </div>

    <div id="ret-message" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
        <p id="ret-message-text" style="font-size:14px;color:#6b7280;margin:0;">No se pudieron cargar las reglas de retención.</p>
    </div>

    <div id="ret-content" style="display:none;">
        <div id="ret-summary" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;"></div>
        <div id="ret-cards" style="display:flex;flex-direction:column;gap:14px;"></div>
    </div>
</section>

<script>
(function () {
    var RET_URL = {
        index: '{{ url("/api/v1/provider/marketing/rules") }}',
        update: '{{ url("/api/v1/provider/marketing/rules") }}'
    };

    var RET_DAYS = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];

    var RET_META = {
        INACTIVE_CUSTOMER: {
            title: 'Cliente inactivo',
            hint: 'Avisa cuando el cliente lleva varios dias sin pedirte nada.'
        },
        RECURRING_DAY_REMINDER: {
            title: 'Recordatorio del dia de entrega',
            hint: 'Recuerda el pedido recurrente antes de que llegue el dia.'
        },
        WELCOME_BACK: {
            title: 'Bienvenida de regreso',
            hint: 'Recupera clientes que dejaron de comprar hace tiempo.'
        }
    };

    var retState = { rules: {} };

    function retSetState(state, text) {
        var map = { loading: 'ret-loading', message: 'ret-message', content: 'ret-content' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('ret-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function loadRetention() {
        retSetState('loading');

        fdFetchJson(RET_URL.index)
            .then(function (res) {
                if (!res.ok) {
                    retSetState('message', (res.data && res.data.message) || 'No se pudieron cargar las reglas.');
                    return;
                }

                var data = res.data;
                var banner = document.getElementById('ret-banner');
                if (banner) banner.style.display = 'block';

                retState.rules = {};
                (data.rules || []).forEach(function (rule) {
                    retState.rules[rule.rule_type] = rule;
                });

                var types = (data.types && data.types.length) ? data.types : Object.keys(RET_META);
                var defaults = data.default_templates || {};

                var summary = document.getElementById('ret-summary');
                summary.innerHTML = fdBadge(data.summary.enabled + ' de ' + data.summary.total + ' configuradas',
                    '#0c2a4d', '#eef2f7');

                document.getElementById('ret-cards').innerHTML = types.map(function (type) {
                    var rule = retState.rules[type] || {};
                    var meta = RET_META[type] || { title: type, hint: '' };
                    var enabled = rule.is_enabled === true;

                    return '<div style="background:#fff;border:1px solid #e5e7eb;border-left:3px solid '
                        + (enabled ? '#D24C19' : '#d1d5db') + ';padding:18px;">'
                        + '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:4px;">'
                        + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
                        + '<h3 style="font-size:15px;font-weight:700;color:#0c2a4d;margin:0;">' + escapeHtml(meta.title) + '</h3>'
                        + (rule.id
                            ? (enabled ? fdBadge('Activa', '#047857', '#d1fae5') : fdBadge('Inactiva', '#6b7280', '#f3f4f6'))
                            : fdBadge('Sin configurar', '#b45309', '#fef3c7'))
                        + '</div>'
                        + '<label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#374151;cursor:pointer;">'
                        + '<input type="checkbox" id="ret-enabled-' + type + '"' + (enabled ? ' checked' : '') + ' '
                        + 'style="width:16px;height:16px;cursor:pointer;" /> Habilitar</label>'
                        + '</div>'
                        + '<p style="font-size:12px;color:#6b7280;margin:0 0 14px;">' + escapeHtml(meta.hint) + '</p>'
                        + '<div style="display:grid;grid-template-columns:'
                        + (type === 'INACTIVE_CUSTOMER' ? 'repeat(3,1fr)' : (type === 'RECURRING_DAY_REMINDER' ? 'repeat(2,1fr)' : '1fr'))
                        + ';gap:10px;margin-bottom:12px;">'
                        + (type === 'INACTIVE_CUSTOMER'
                            ? '<div><label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Dias sin comprar</label>'
                                + '<input type="number" min="1" max="365" id="ret-days-' + type + '" value="'
                                + escapeHtml(rule.days_inactive || 30) + '" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" /></div>'
                            : '')
                        + (type === 'RECURRING_DAY_REMINDER'
                            ? '<div><label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Dia de la entrega</label>'
                                + '<select id="ret-day-' + type + '" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">'
                                + RET_DAYS.map(function (name, i) {
                                    var value = i + 1;
                                    return '<option value="' + value + '"' + (Number(rule.day_of_week) === value ? ' selected' : '') + '>' + name + '</option>';
                                }).join('')
                                + '</select></div>'
                            : '')
                        + '<div><label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Cupon</label>'
                        + '<input type="text" id="ret-coupon-' + type + '" value="' + escapeHtml(rule.discount_code || '') + '" '
                        + 'style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" placeholder="Opcional" /></div>'
                        + '</div>'
                        + '<div style="margin-bottom:12px;">'
                        + '<label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Mensaje</label>'
                        + '<textarea id="ret-message-' + type + '" rows="3" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;">'
                        + escapeHtml(rule.message_template || defaults[type] || '') + '</textarea>'
                        + '<p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">Usá {nombre}, {comercio}, {dias} y {cupon}; se completan solos al enviar.</p>'
                        + '</div>'
                        + '<button type="button" onclick="saveRetentionRule(\'' + type + '\')" '
                        + 'style="background:#D24C19;color:#fff;border:none;padding:8px 18px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">Guardar</button>'
                        + '</div>';
                }).join('');

                retSetState('content');
            })
            .catch(function () {
                retSetState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function saveRetentionRule(type) {
        var body = {
            rule_type: type,
            is_enabled: document.getElementById('ret-enabled-' + type).checked,
            message_template: document.getElementById('ret-message-' + type).value.trim(),
            discount_code: document.getElementById('ret-coupon-' + type).value.trim() || null
        };

        if (type === 'INACTIVE_CUSTOMER') {
            body.days_inactive = Number(document.getElementById('ret-days-' + type).value);
        }

        if (type === 'RECURRING_DAY_REMINDER') {
            body.day_of_week = Number(document.getElementById('ret-day-' + type).value);
        }

        if (!body.message_template) {
            fdToast('Escribí el mensaje de la campaña.', true);
            return;
        }

        fdFetchJson(RET_URL.update, { method: 'PUT', body: JSON.stringify(body) })
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudo guardar la regla.', true);
                    return;
                }
                fdToast(res.data.message || 'Regla guardada.', false);
                loadRetention();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    window.loadRetention = loadRetention;
    window.saveRetentionRule = saveRetentionRule;
})();
</script>
