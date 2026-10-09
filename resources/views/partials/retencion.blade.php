{{-- Reglas de retencion del comercio: el motor las corre todos los dias. --}}
<style>
    /* --- Retencion: sidebar de campanas (una seccion por retencion) --- */
    .ret-layout { display: flex; align-items: stretch; background: #ffffff; border: 1px solid #e5e7eb; }
    #ret-sidebar { width: 210px; min-width: 210px; background: #f8fafc; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; padding-bottom: 8px; }
    .ret-menu-title { font-size: 10px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: #94a3b8; padding: 16px 20px 8px; }
    .ret-body { flex: 1; min-width: 0; padding: 20px 22px; }
    .ret-panel { display: none; }
    .ret-panel.active { display: block; animation: tabFade .18s ease; }
    .ret-panel-head { margin-bottom: 14px; }
    .ret-panel-head h3 { font-size: 15px; font-weight: 700; color: #0c2a4d; margin: 0; }
    .ret-panel-head p { font-size: 12px; color: #6b7280; margin: 3px 0 0; }
    .ret-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #d97706; flex-shrink: 0; }
    #ret-sidebar .sidebar-link { font-size: 12px; gap: 8px; padding: 9px 16px; align-items: center; }
    .ret-topbar { display: none; }
    #ret-menu-toggle { display: none; }
    #ret-overlay { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 240; }
    #ret-overlay.open { display: block; }
    #ret-nav { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
    #ret-legend { margin-top: auto; padding: 12px 16px 6px; display: flex; flex-direction: column; gap: 6px; font-size: 10px; color: #94a3b8; border-top: 1px solid #e5e7eb; }
    #ret-legend span { display: flex; align-items: center; gap: 6px; }
    @media (max-width: 860px) {
        .ret-layout { flex-direction: column; }
        /* Barra superior con la campana actual, como Mi Panel */
        .ret-topbar {
            display: flex;
            align-items: center;
            gap: 8px;
            height: 44px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 16px;
            position: sticky;
            top: 60px;
            z-index: 40;
        }
        #ret-menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #0f172a;
        }
        #ret-menu-toggle:hover { color: #D24C19; }
        /* Sidebar fijo que entra desde la izquierda, como Mi Panel */
        #ret-sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 250px;
            min-width: 250px;
            height: calc(100vh - 60px);
            overflow-y: auto;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            border-bottom: none;
            transform: translateX(-100%);
            transition: transform .25s ease;
            z-index: 260;
            padding-bottom: 8px;
        }
        #ret-sidebar.open { transform: translateX(0); }
        .ret-body { padding: 16px 12px; }
    }
    @media (min-width: 861px) {
        #ret-overlay { display: none !important; }
    }
</style>
<section id="dash-{{ $page->url }}" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1000px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
        </div>
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

        <div class="ret-layout">
            <div class="ret-topbar">
                <button type="button" id="ret-menu-toggle" onclick="retToggleMenu()" aria-controls="ret-sidebar" aria-expanded="false" aria-label="Abrir campañas" title="Campañas">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>
                <span style="font-size:12px;color:#64748b;">Campañas</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                <span id="ret-menu-current" style="font-size:12px;color:#0f172a;font-weight:600;">Reactivación</span>
            </div>

            <div id="ret-overlay" onclick="retToggleMenu()" title="Cerrar" aria-hidden="true"></div>

            <aside id="ret-sidebar" aria-label="Campañas de retención">
                <div id="ret-nav">
                <div class="ret-menu-title">Campañas</div>

                <a href="#" class="sidebar-link active" id="ret-link-INACTIVE_CUSTOMER" data-ret-panel="INACTIVE_CUSTOMER" title="Reactivación" onclick="event.preventDefault();retTab('INACTIVE_CUSTOMER')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                    <span style="flex:1;min-width:0;">Reactivación</span>
                    <span class="ret-dot" id="ret-dot-INACTIVE_CUSTOMER" title="Sin configurar"></span>
                </a>

                <a href="#" class="sidebar-link" id="ret-link-RECURRING_DAY_REMINDER" data-ret-panel="RECURRING_DAY_REMINDER" title="Aviso de Envío" onclick="event.preventDefault();retTab('RECURRING_DAY_REMINDER')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><rect width="18" height="18" x="3" y="4" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18"/></svg>
                    <span style="flex:1;min-width:0;">Aviso de Envío</span>
                    <span class="ret-dot" id="ret-dot-RECURRING_DAY_REMINDER" title="Sin configurar"></span>
                </a>

                <a href="#" class="sidebar-link" id="ret-link-WELCOME_BACK" data-ret-panel="WELCOME_BACK" title="Reencuentro" onclick="event.preventDefault();retTab('WELCOME_BACK')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                    <span style="flex:1;min-width:0;">Reencuentro</span>
                    <span class="ret-dot" id="ret-dot-WELCOME_BACK" title="Sin configurar"></span>
                </a>

                <div id="ret-legend">
                    <span><i class="ret-dot" style="background:#047857;"></i> Activa</span>
                    <span><i class="ret-dot" style="background:#6b7280;"></i> Inactiva</span>
                    <span><i class="ret-dot" style="background:#d97706;"></i> Sin configurar</span>
                </div>
                </div>
            </aside>

            <div class="ret-body">
                <div class="ret-panel active" id="ret-panel-INACTIVE_CUSTOMER">
                    <div class="ret-panel-head">
                        <h3>Reactivación</h3>
                        <p>Avisa cuando el cliente lleva varios días sin pedirte nada.</p>
                    </div>
                    <div id="ret-slot-INACTIVE_CUSTOMER"></div>
                </div>

                <div class="ret-panel" id="ret-panel-RECURRING_DAY_REMINDER">
                    <div class="ret-panel-head">
                        <h3>Aviso de Envío</h3>
                        <p>Recuerda el pedido recurrente antes de que llegue el día.</p>
                    </div>
                    <div id="ret-slot-RECURRING_DAY_REMINDER"></div>
                </div>

                <div class="ret-panel" id="ret-panel-WELCOME_BACK">
                    <div class="ret-panel-head">
                        <h3>Reencuentro</h3>
                        <p>Recupera clientes que dejaron de comprar hace tiempo.</p>
                    </div>
                    <div id="ret-slot-WELCOME_BACK"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var RET_URL = {
        index: '{{ url("/api/v1/provider/marketing/rules") }}',
        update: '{{ url("/api/v1/provider/marketing/rules") }}'
    };

    var RET_DAYS = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
    var RET_REQUIRED_TOKENS = ['{nombre}', '{comercio}', '{cupon}'];
    var retTokens = {};
    var retLastValid = {};

    var RET_META = {
        INACTIVE_CUSTOMER: {
            title: 'Reactivación',
            hint: 'Avisa cuando el cliente lleva varios dias sin pedirte nada.'
        },
        RECURRING_DAY_REMINDER: {
            title: 'Aviso de Envío',
            hint: 'Recuerda el pedido recurrente antes de que llegue el dia.'
        },
        WELCOME_BACK: {
            title: 'Reencuentro',
            hint: 'Recupera clientes que dejaron de comprar hace tiempo.'
        }
    };

    var RET_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>';

    var retState = { rules: {}, active: 'INACTIVE_CUSTOMER' };

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

    // Cada campana vive en su propia seccion del cuerpo; el sidebar solo cambia
    // la visible. En responsive el sidebar es un menu hamburguesa.
    function retToggleMenu() {
        var sb = document.getElementById('ret-sidebar');
        if (!sb) return;

        var abierto = sb.classList.toggle('open');
        var ov = document.getElementById('ret-overlay');
        if (ov) ov.classList.toggle('open', abierto);
        var btn = document.getElementById('ret-menu-toggle');
        if (btn) btn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    }

    function retCloseMenu() {
        var sb = document.getElementById('ret-sidebar');
        var ov = document.getElementById('ret-overlay');
        var btn = document.getElementById('ret-menu-toggle');

        if (ov) ov.classList.remove('open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        if (sb) sb.classList.remove('open');
    }

    function retTab(type) {
        retState.active = type;

        document.querySelectorAll('#ret-sidebar .sidebar-link[data-ret-panel]').forEach(function (link) {
            link.classList.toggle('active', link.getAttribute('data-ret-panel') === type);
        });

        document.querySelectorAll('.ret-panel').forEach(function (panel) {
            panel.classList.toggle('active', panel.id === 'ret-panel-' + type);
        });

        var actual = document.getElementById('ret-menu-current');
        if (actual) actual.textContent = (RET_META[type] ? RET_META[type].title : type);

        retCloseMenu();
    }

    // Si la API agrega un tipo de campana que no esta en el HTML, se crean su
    // entrada en el sidebar y su seccion.
    function retEnsurePanel(type) {
        if (document.getElementById('ret-panel-' + type)) return;

        var meta = RET_META[type] || { title: type, hint: '' };

        var link = document.createElement('a');
        link.href = '#';
        link.className = 'sidebar-link';
        link.id = 'ret-link-' + type;
        link.setAttribute('data-ret-panel', type);
        link.setAttribute('title', meta.title);
        link.innerHTML = RET_ICON
            + '<span style="flex:1;min-width:0;">' + escapeHtml(meta.title) + '</span>'
            + '<span class="ret-dot" id="ret-dot-' + type + '" title="Sin configurar"></span>';
        link.onclick = function (e) { e.preventDefault(); retTab(type); };

        var legend = document.getElementById('ret-legend');
        var nav = document.getElementById('ret-nav') || document.getElementById('ret-sidebar');

        if (legend && legend.parentNode === nav) {
            nav.insertBefore(link, legend);
        } else if (typeof nav.appendChild === 'function') {
            nav.appendChild(link);
        } else {
            nav.insertBefore(link, legend);
        }

        var panel = document.createElement('div');
        panel.className = 'ret-panel';
        panel.id = 'ret-panel-' + type;
        panel.innerHTML = '<div class="ret-panel-head">'
            + '<h3>' + escapeHtml(meta.title) + '</h3>'
            + '<p>' + escapeHtml(meta.hint) + '</p>'
            + '</div><div id="ret-slot-' + type + '"></div>';
        document.querySelector('.ret-body').appendChild(panel);
    }

    function retPanelTypes() {
        return Array.prototype.map.call(document.querySelectorAll('.ret-panel'), function (p) {
            return p.id.replace('ret-panel-', '');
        });
    }

    // Punto de estado en el sidebar: verde activa, gris inactiva, amarillo sin configurar.
    function retPaintDot(type, rule) {
        var dot = document.getElementById('ret-dot-' + type);
        var link = document.getElementById('ret-link-' + type);
        if (!dot) return;

        var configured = !!rule.id;
        var enabled = configured && rule.is_enabled === true;
        var state = enabled ? 'Activa' : (configured ? 'Inactiva' : 'Sin configurar');

        dot.style.background = enabled ? '#047857' : (configured ? '#6b7280' : '#d97706');
        dot.title = state;
        if (link) {
            link.title = (RET_META[type] ? RET_META[type].title : type) + ' — ' + state;
        }
    }

    // Tokens que el mensaje de cada campana no puede perder: los de la
    // plantilla por defecto (p.ej. {dias} en Reactivación) mas los bases.
    function retTokenList(type, defaults) {
        var lista = [];
        var encontrados = ((((defaults || {})[type]) || '').match(/\{[a-z_]+\}/g) || []).concat(RET_REQUIRED_TOKENS);

        encontrados.forEach(function (token) {
            if (lista.indexOf(token) === -1) lista.push(token);
        });

        return lista;
    }

    function retTokensFor(type) {
        return retTokens[type] || RET_REQUIRED_TOKENS;
    }

    function retHintTokens(tokens) {
        if (tokens.length < 2) return tokens.join('');
        return tokens.slice(0, -1).join(', ') + ' y ' + tokens[tokens.length - 1];
    }

    // El mensaje no se puede editar de manera que pierda un token obligatorio:
    // se revierte el texto y se avisa. Las ediciones alrededor de los tokens
    // siguen permitidas.
    function retGuardMessage(el) {
        if (!el || typeof el.value !== 'string') return;

        var faltantes = retTokensFor(String(el.id).replace('ret-message-', ''))
            .filter(function (token) { return el.value.indexOf(token) === -1; });

        if (!faltantes.length) {
            retLastValid[el.id] = el.value;
            return;
        }

        var previo = retLastValid[el.id];

        if (typeof previo !== 'string' && typeof el.defaultValue === 'string') {
            previo = el.defaultValue;
        }

        if (typeof previo !== 'string') return;

        var todaviaFalta = faltantes.some(function (token) { return previo.indexOf(token) === -1; });
        if (todaviaFalta) return;

        el.value = previo;
        fdToast('No se pueden borrar ' + faltantes.join(', ') + '; esos tokens se completan solos al enviar.', true);
    }

    function retCardHtml(type, rule, defaults) {
        var enabled = rule.is_enabled === true;
        retTokens[type] = retTokenList(type, defaults);

        return '<div style="background:#fff;border:1px solid #e5e7eb;border-left:3px solid '
            + (enabled ? '#D24C19' : '#d1d5db') + ';padding:18px;">'
            + '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">'
            + (rule.id
                ? (enabled ? fdBadge('Activa', '#047857', '#d1fae5') : fdBadge('Inactiva', '#6b7280', '#f3f4f6'))
                : fdBadge('Sin configurar', '#b45309', '#fef3c7'))
            + '<label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#374151;cursor:pointer;">'
            + '<input type="checkbox" id="ret-enabled-' + type + '"' + (enabled ? ' checked' : '') + ' '
            + 'style="width:16px;height:16px;cursor:pointer;" /> Habilitar</label>'
            + '</div>'
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
            + '<textarea id="ret-message-' + type + '" rows="3" oninput="retGuardMessage(this)" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;">'
            + escapeHtml(rule.message_template || defaults[type] || '') + '</textarea>'
            + '<p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">Usá {nombre}, {comercio}, {dias} y {cupon}; se completan solos al enviar. '
            + retHintTokens(retTokens[type]) + ' son obligatorios y no se pueden borrar.</p>'
            + '</div>'
            + '<button type="button" onclick="saveRetentionRule(\'' + type + '\')" title="Guardar" aria-label="Guardar" '
            + 'style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></button>'
            + '</div>';
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

                // Asegura la seccion de cada campana que venga del servidor.
                types.forEach(retEnsurePanel);

                // Renderiza la tarjeta de cada campana dentro de su seccion.
                retPanelTypes().forEach(function (type) {
                    var visible = types.indexOf(type) !== -1;
                    var link = document.getElementById('ret-link-' + type);
                    var panel = document.getElementById('ret-panel-' + type);
                    if (link) link.style.display = visible ? '' : 'none';
                    if (panel) panel.style.display = visible ? '' : 'none';
                    if (!visible) return;

                    var rule = retState.rules[type] || {};
                    document.getElementById('ret-slot-' + type).innerHTML = retCardHtml(type, rule, defaults);
                    retPaintDot(type, rule);

                    var mensaje = document.getElementById('ret-message-' + type);
                    if (mensaje && typeof mensaje.value === 'string') retLastValid[mensaje.id] = mensaje.value;
                });

                if (types.indexOf(retState.active) === -1) retState.active = types[0];
                retTab(retState.active);
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

        var faltantes = retTokensFor(type).filter(function (token) {
            return body.message_template.indexOf(token) === -1;
        });

        if (faltantes.length) {
            fdToast('El mensaje no puede perder ' + faltantes.join(', ') + '; esos tokens se completan solos al enviar.', true);
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
    window.retTab = retTab;
    window.retGuardMessage = retGuardMessage;
    window.retToggleMenu = retToggleMenu;
})();
</script>
