{{-- Solicitar Servicio (Modulo B): urgencias 24/7, citas programadas y
     presupuestos a medida. El cliente busca prestadores con el mismo esquema
     que los comercios (buscador + tabs Favoritos/Todos + tarjetas); cada
     tarjeta trae los botones de los servicios que ofrece y el favorito, y al
     elegir un servicio la orden se le asigna directo a ese prestador. --}}
<section id="dash-solicitar-servicio" class="dash-section" data-module-id="{{ $page->module_id }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p id="fd-solic-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">Buscá al prestador que necesitás y pedile el servicio.</p>
        </div>
        <input id="fd-solic-search" type="text" placeholder="Buscar prestador..." oninput="fdSolicSearchDebounce()" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;min-width:200px;background:#fff;outline:none;">
    </div>

    <div id="fd-solic-tabs" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
        <button type="button" id="fd-solic-tab-favorites" onclick="fdSolicTab('favorites')" aria-label="Favoritos" title="Favoritos" style="display:inline-flex;align-items:center;gap:6px;padding:7px 12px;font-size:13px;font-weight:600;background:#fff;color:#6b7280;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <span id="fd-solic-count-favorites" style="display:none;background:#D24C19;color:#fff;border-radius:9999px;font-size:11px;font-weight:700;padding:1px 7px;"></span>
        </button>
        <button type="button" id="fd-solic-tab-all" onclick="fdSolicTab('all')" aria-label="Todos" title="Todos" style="display:inline-flex;align-items:center;gap:6px;padding:7px 12px;font-size:13px;font-weight:600;background:#fff;color:#6b7280;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
            <span id="fd-solic-count-all" style="display:none;background:#6b7280;color:#fff;border-radius:9999px;font-size:11px;font-weight:700;padding:1px 7px;"></span>
        </button>
    </div>

    <div id="fd-solic-message" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:12px 16px;margin-bottom:14px;">
        <p id="fd-solic-message-text" style="font-size:13px;color:#374151;margin:0;white-space:pre-line;"></p>
    </div>

    {{-- Grilla de prestadores (esquema Comercios) --}}
    <div id="fd-solic-pick" style="background:#fff;border:1px solid #e5e7eb;padding:16px;margin-bottom:14px;">
        <div id="fd-solic-loading" style="display:none;text-align:center;padding:24px;">
            <p style="font-size:13px;color:#6b7280;margin:0;">Cargando prestadores...</p>
        </div>

        <div id="fd-solic-empty" style="display:none;border:1px dashed #d1d5db;padding:28px 20px;text-align:center;">
            <p id="fd-solic-empty-text" style="font-size:13px;color:#6b7280;margin:0 0 12px;">No hay prestadores publicados todavia.</p>
            <div id="fd-solic-empty-action"></div>
        </div>

        <div id="fd-solic-grid" style="display:none;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;"></div>
    </div>

    {{-- Prestador elegido + formulario del servicio --}}
    <div id="fd-solic-chosen" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;padding:12px 14px;margin-bottom:14px;">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <button type="button" onclick="fdSolicBack()" title="Cambiar de prestador" aria-label="Cambiar de prestador" style="background:#fff;color:#0c2a4d;border:1px solid #d1d5db;border-radius:4px;cursor:pointer;padding:8px;display:inline-flex;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            </button>
            <div id="fd-solic-chosen-avatar" style="width:40px;height:40px;flex:none;border-radius:50%;background:#fff1eb;color:#D24C19;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:16px;"></div>
            <div style="min-width:0;">
                <p id="fd-solic-chosen-name" style="font-size:14px;font-weight:700;color:#0f172a;margin:0;"></p>
                <p id="fd-solic-chosen-meta" style="font-size:12px;color:#64748b;margin:3px 0 0;"></p>
            </div>
            <div style="text-align:right;margin-left:auto;">
                <p id="fd-solic-chosen-service" style="font-size:12px;font-weight:700;color:#D24C19;margin:0;"></p>
                <p style="font-size:11px;color:#6b7280;margin:3px 0 0;">El precio lo define el profesional</p>
            </div>
        </div>
    </div>

    <form id="fd-solic-form-emergency" onsubmit="return fdSolicSubmitEmergency(event)" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:16px;">
        <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">Contanos el problema</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;">
            <div style="grid-column:1/-1;">
                <label for="fd-solic-emergency-desc" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Descripción (opcional)</label>
                <textarea id="fd-solic-emergency-desc" rows="2" maxlength="1000" placeholder="Ej: Pérdida de gas bajo la bacha de la cocina" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;outline:none;"></textarea>
            </div>
            <div>
                <label for="fd-solic-emergency-photo" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Foto del problema (obligatoria, máx 5 MB)</label>
                <input type="file" id="fd-solic-emergency-photo" accept="image/jpeg,image/png,image/webp" style="width:100%;font-size:12px;">
            </div>
            <div>
                <label for="fd-solic-emergency-audio" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Audio (opcional, máx 8 MB)</label>
                <input type="file" id="fd-solic-emergency-audio" accept="audio/*" style="width:100%;font-size:12px;">
            </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:14px;flex-wrap:wrap;">
            <button type="submit" id="fd-solic-emergency-submit" title="Pedir urgencia" aria-label="Pedir urgencia" style="background:#D24C19;color:#fff;border:none;padding:10px 20px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 18v-6a5 5 0 1 1 10 0v6"/><path d="M5 21a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-1a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2z"/><path d="M21 12h1"/><path d="M12 2v1"/></svg></button>
        </div>
    </form>

    <form id="fd-solic-form-scheduled" onsubmit="return fdSolicSubmitScheduled(event)" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:16px;">
        <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">Elegí fecha y horario</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
            <div>
                <label for="fd-solic-date" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Fecha</label>
                <input type="date" id="fd-solic-date" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;">
            </div>
            <div>
                <label for="fd-solic-time" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Hora</label>
                <input type="time" id="fd-solic-time" value="09:00" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;">
            </div>
            <div>
                <label for="fd-solic-fulfillment" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Modalidad</label>
                <select id="fd-solic-fulfillment" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;outline:none;">
                    <option value="AT_HOME">A domicilio</option>
                    <option value="AT_MERCHANT_LOCATION">En el comercio</option>
                </select>
            </div>
            <div>
                <label for="fd-solic-payment-method" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Medio de pago</label>
                <select id="fd-solic-payment-method" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;outline:none;">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                </select>
            </div>
            <div>
                <label for="fd-solic-payment-mode" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Pago</label>
                <select id="fd-solic-payment-mode" onchange="fdSolicRenderDeposit()" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;outline:none;">
                    <option value="DEPOSIT">Seña (50%)</option>
                    <option value="FULL">Pago total</option>
                </select>
            </div>
            <div style="grid-column:1/-1;">
                <label for="fd-solic-notes" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Notas (opcional)</label>
                <textarea id="fd-solic-notes" rows="2" maxlength="1000" placeholder="Ej: Timbre roto,toqué al 4B" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;outline:none;"></textarea>
            </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:14px;flex-wrap:wrap;">
            <button type="submit" id="fd-solic-scheduled-submit" title="Reservar cita" aria-label="Reservar cita" style="background:#D24C19;color:#fff;border:none;padding:10px 20px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg></button>
        </div>
    </form>

    <form id="fd-solic-form-quote" onsubmit="return fdSolicSubmitQuote(event)" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:16px;">
        <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">Detallá el trabajo</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;">
            <div style="grid-column:1/-1;">
                <label for="fd-solic-quote-details" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Detalles del trabajo (obligatorio, máx 2000)</label>
                <textarea id="fd-solic-quote-details" rows="3" maxlength="2000" placeholder="Ej: Quiero renovar la instalación eléctrica de un departamento de 3 ambientes" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;outline:none;"></textarea>
            </div>
            <div>
                <label for="fd-solic-quote-photos" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Fotos (1 a 5, máx 5 MB c/u)</label>
                <input type="file" id="fd-solic-quote-photos" accept="image/jpeg,image/png,image/webp" multiple style="width:100%;font-size:12px;">
            </div>
            <div>
                <label for="fd-solic-quote-video" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Video (opcional, máx 20 MB)</label>
                <input type="file" id="fd-solic-quote-video" accept="video/mp4,video/webm,video/quicktime" style="width:100%;font-size:12px;">
            </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:14px;">
            <button type="submit" id="fd-solic-quote-submit" title="Pedir presupuesto" aria-label="Pedir presupuesto" style="background:#D24C19;color:#fff;border:none;padding:10px 20px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 11h2"/></svg></button>
        </div>
    </form>
</section>

<script>
(function () {
    'use strict';

    var FD_SOLIC_URLS = {
        providers: '{{ url("/api/v1/service-providers") }}',
        favorite: '{{ url("/api/providers") }}',
        emergency: '{{ url("/api/v1/customer/service-orders/emergency") }}',
        scheduled: '{{ url("/api/v1/customer/service-orders/scheduled") }}',
        quote: '{{ url("/api/v1/customer/service-orders/quote") }}'
    };

    var FD_SOLIC_STAR = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
    var FD_SOLIC_STAR_FILLED = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

    var FD_SOLIC_TYPE_ICONS = {
        EMERGENCY: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 18v-6a5 5 0 1 1 10 0v6"/><path d="M5 21a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-1a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2z"/><path d="M21 12h1"/><path d="M12 2v1"/></svg>',
        SCHEDULED: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>',
        QUOTE: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 11h2"/></svg>'
    };

    function serviceIcon(code) {
        return FD_SOLIC_TYPE_ICONS[code] || '';
    }


    var fdSolicTab = 'all';
    var fdSolicService = null;
    var fdSolicProviders = [];
    var fdSolicSelected = null;
    var fdSolicSearch = '';
    var fdSolicTimer = null;
    var fdSolicCounts = { favorites: 0, all: 0 };
    var fdSolicInitialized = false;

    function esc(str) {
        return escapeHtml(str == null ? '' : String(str));
    }

    function msg(kind, text) {
        var box = document.getElementById('fd-solic-message');
        var p = document.getElementById('fd-solic-message-text');
        if (!box || !p) return;
        box.style.display = 'block';
        box.style.borderColor = kind === 'error' ? '#fecaca' : '#bbf7d0';
        box.style.background = kind === 'error' ? '#fef2f2' : '#f0fdf4';
        p.style.color = kind === 'error' ? '#b91c1c' : '#166534';
        p.textContent = text;
    }

    function msgHide() {
        var box = document.getElementById('fd-solic-message');
        if (box) box.style.display = 'none';
    }

    function pickState(name) {
        ['loading', 'empty', 'grid'].forEach(function (key) {
            var el = document.getElementById('fd-solic-' + key);
            if (el) el.style.display = 'none';
        });
        if (name) {
            var target = document.getElementById('fd-solic-' + name);
            if (target) target.style.display = name === 'grid' ? 'grid' : 'block';
        }
    }

    function showForms() {
        ['emergency', 'scheduled', 'quote'].forEach(function (key) {
            var el = document.getElementById('fd-solic-form-' + key);
            if (el) el.style.display = 'none';
        });
        var form = fdSolicService ? document.getElementById('fd-solic-form-' + fdSolicService) : null;
        if (form) form.style.display = fdSolicSelected ? 'block' : 'none';
    }

    function paintTabs() {
        [['favorites', '#D24C19'], ['all', '#6b7280']].forEach(function (pair) {
            var key = pair[0];
            var btn = document.getElementById('fd-solic-tab-' + key);
            var active = key === fdSolicTab;
            if (btn) {
                btn.style.background = active ? '#fff1eb' : '#fff';
                btn.style.color = active ? '#D24C19' : '#6b7280';
                btn.style.borderColor = active ? '#D24C19' : '#e5e7eb';
            }
            var badge = document.getElementById('fd-solic-count-' + key);
            if (badge) {
                var count = fdSolicCounts[key];
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline-block' : 'none';
            }
        });
    }

    function paintSummary() {
        var summary = document.getElementById('fd-solic-summary');
        if (!summary) return;
        if (fdSolicTab === 'favorites') {
            summary.textContent = fdSolicCounts.favorites > 0
                ? fdSolicCounts.favorites + ' prestador' + (fdSolicCounts.favorites === 1 ? '' : 'es') + ' favorito' + (fdSolicCounts.favorites === 1 ? '' : 's')
                : 'Todavia no guardaste prestadores favoritos.';
        } else {
            summary.textContent = fdSolicCounts.all > 0
                ? fdSolicCounts.all + ' prestador' + (fdSolicCounts.all === 1 ? '' : 'es') + ' disponible' + (fdSolicCounts.all === 1 ? '' : 's')
                : 'Buscá al prestador que necesitás y pedile el servicio.';
        }
    }

    // ---------------------------------------------------------- tabs

    window.fdSolicTab = function (tab) {
        if (tab !== 'favorites' && tab !== 'all') return;
        fdSolicTab = tab;
        paintTabs();
        loadProviders();
    };

    // ---------------------------------------------------------- buscador

    window.fdSolicSearchDebounce = function () {
        var input = document.getElementById('fd-solic-search');
        fdSolicSearch = input ? input.value.trim() : '';
        if (fdSolicTimer) clearTimeout(fdSolicTimer);
        fdSolicTimer = setTimeout(loadProviders, 300);
    };

    // ---------------------------------------------------------- grilla

    function loadProviders(silent) {
        if (!silent) pickState('loading');

        // En la primera carga pedimos todos (sin filtro) para conocer el
        // conteo de favoritos y decidir el tab por defecto: favoritos si hay,
        // si no, todos.
        var firstLoad = !fdSolicInitialized;
        var isFavorites = !firstLoad && fdSolicTab === 'favorites';

        var url = FD_SOLIC_URLS.providers;
        var params = [];
        if (isFavorites) params.push('favorite=1');
        if (fdSolicSearch) params.push('search=' + encodeURIComponent(fdSolicSearch));
        if (params.length) url += '?' + params.join('&');

        fdFetchJson(url)
            .then(function (res) {
                if (!res.ok || !res.data || res.data.success === false) {
                    if (silent) return;
                    pickState(null);
                    msg('error', (res.data && res.data.message) || 'No se pudieron cargar los prestadores.');
                    return;
                }
                fdSolicProviders = (res.data.data && res.data.data.providers) || [];
                fdSolicCounts.favorites = (res.data.data && res.data.data.favorites_count) || 0;
                fdSolicCounts.all = (res.data.data && res.data.data.total_count) || 0;

                if (firstLoad) {
                    fdSolicInitialized = true;
                    fdSolicTab = fdSolicCounts.favorites > 0 ? 'favorites' : 'all';
                    // Habia favoritos: este request era de "todos", volvemos a
                    // cargar ya con el tab de favoritos activo.
                    if (fdSolicTab === 'favorites') {
                        loadProviders(silent);
                        return;
                    }
                }

                renderProviders();
            })
            .catch(function () {
                if (silent) return;
                pickState(null);
                msg('error', 'No se pudieron cargar los prestadores. Revisá tu conexión.');
            });
    }

    function renderProviders() {
        msgHide();
        paintTabs();
        paintSummary();

        var grid = document.getElementById('fd-solic-grid');
        var emptyText = document.getElementById('fd-solic-empty-text');
        var emptyAction = document.getElementById('fd-solic-empty-action');

        if (!fdSolicProviders.length) {
            if (fdSolicTab === 'favorites') {
                if (emptyText) emptyText.textContent = 'Todavia no guardaste prestadores favoritos.';
                if (emptyAction) {
                    emptyAction.innerHTML = '<button type="button" onclick="fdSolicTab(\'all\')" style="background:#D24C19;color:#fff;border:none;border-radius:4px;cursor:pointer;padding:8px 16px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:6px;">'
                        + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>'
                        + 'Ver todos los prestadores</button>';
                }
            } else {
                if (emptyText) {
                    emptyText.textContent = fdSolicSearch
                        ? 'No encontramos prestadores para "' + fdSolicSearch + '".'
                        : 'No hay prestadores publicados todavia.';
                }
                if (emptyAction) emptyAction.innerHTML = '';
            }
            pickState('empty');
            return;
        }

        if (grid) grid.innerHTML = fdSolicProviders.map(renderCard).join('');
        pickState('grid');
    }

    function renderCard(p) {
        var html = '<div data-fd-solic-provider="' + p.id + '" style="background:#fff;border:1px solid #e5e7eb;padding:16px;display:flex;flex-direction:column;gap:10px;">';

        if (p.publicidad_image) {
            html += '<img src="' + esc(p.publicidad_image) + '" alt="Publicidad de ' + esc(p.business_name) + '" style="width:calc(100% + 32px);margin:-16px -16px 0;display:block;height:70px;object-fit:cover;background:#f1f5f9;">';
        }

        html += '<div style="display:flex;gap:12px;align-items:center;">'
            + '<div style="width:44px;height:44px;flex:none;border-radius:50%;background:#fff1eb;color:#D24C19;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:18px;">' + esc((p.business_name || '?').charAt(0).toUpperCase()) + '</div>'
            + '<div style="min-width:0;">'
            + '<div style="font-size:14px;font-weight:700;color:#0c2a4d;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + esc(p.business_name) + '</div>'
            + '</div>';

        if (p.rating && parseFloat(p.rating) > 0) {
            html += '<div style="margin-left:auto;font-size:12px;font-weight:700;color:#b45309;white-space:nowrap;">★ ' + parseFloat(p.rating).toFixed(1) + '</div>';
        }

        html += '</div>';

        if (p.promo) {
            html += '<div style="font-size:11px;color:#047857;background:#ecfdf5;padding:6px 8px;">' + esc(p.promo) + '</div>';
        }

        html += '<div style="display:flex;gap:6px;flex-wrap:wrap;">';
        (p.service_types || []).forEach(function (t) {
            html += '<button type="button" onclick="fdSolicPick(' + p.id + ',\'' + esc(t.code) + '\')" title="' + esc(t.name) + '" aria-label="' + esc(t.name) + '" style="background:#D24C19;color:#fff;border:none;border-radius:4px;cursor:pointer;padding:8px;display:inline-flex;align-items:center;justify-content:center;">' + serviceIcon(t.code) + '</button>';
        });
        html += '</div>';

        var favColor = p.is_favorite ? '#D24C19' : '#94a3b8';
        var favBorder = p.is_favorite ? '#D24C19' : '#d1d5db';
        var favIcon = p.is_favorite ? FD_SOLIC_STAR_FILLED : FD_SOLIC_STAR;
        var favTitle = p.is_favorite ? 'Quitar de favoritos' : 'Agregar a mis favoritos';

        html += '<div style="display:flex;justify-content:flex-end;margin-top:auto;">'
            + '<button type="button" class="btn-icon" onclick="fdSolicToggleFavorite(' + p.id + ', this)" title="' + favTitle + '" aria-label="' + favTitle + '" style="background:#fff;color:' + favColor + ';border:1px solid ' + favBorder + ';border-radius:4px;cursor:pointer;padding:7px;display:inline-flex;">'
            + favIcon
            + '</button></div>';

        html += '</div>';
        return html;
    }

    function rubroOf(p) {
        var parts = [];
        if (p.subgroups && p.subgroups.length) parts.push(p.subgroups[0].description);
        else parts.push('Servicios del hogar');
        if (p.zone) parts.push(p.zone);
        return parts.join(' · ');
    }

    // ---------------------------------------------------------- favoritos

    window.fdSolicToggleFavorite = function (providerId, btn) {
        btn.disabled = true;

        fdFetchJson(FD_SOLIC_URLS.favorite + '/' + providerId + '/favorite', { method: 'POST' })
            .then(function (res) {
                btn.disabled = false;
                if (!res.ok || !res.data || res.data.success === false) {
                    msg('error', (res.data && res.data.message) || 'No se pudo actualizar el favorito.');
                    return;
                }

                // Feedback inmediato de la estrella.
                var isFav = !!res.data.is_favorite;
                btn.title = isFav ? 'Quitar de favoritos' : 'Agregar a mis favoritos';
                btn.setAttribute('aria-label', btn.title);
                btn.style.color = isFav ? '#D24C19' : '#94a3b8';
                btn.style.borderColor = isFav ? '#D24C19' : '#d1d5db';
                btn.innerHTML = isFav ? FD_SOLIC_STAR_FILLED : FD_SOLIC_STAR;

                // Refrescamos la grilla y los contadores desde el GET (que si
                // filtra por el grupo de servicios) para que queden consistentes
                // con el tab activo y el estado vacio se pinte bien.
                loadProviders(true);
            })
            .catch(function () {
                btn.disabled = false;
                msg('error', 'No se pudo actualizar el favorito. Revisá tu conexión.');
            });
    };

    // ---------------------------------------------------------- eleccion

    window.fdSolicPick = function (id, code) {
        var found = null;
        fdSolicProviders.forEach(function (p) {
            if (p.id === id) found = p;
        });
        if (!found) return;

        var serviceName = '';
        (found.service_types || []).forEach(function (t) {
            if (t.code === code) serviceName = t.name;
        });

        fdSolicSelected = found;
        fdSolicService = (code || '').toLowerCase();

        document.getElementById('fd-solic-pick').style.display = 'none';
        document.getElementById('fd-solic-chosen').style.display = 'block';
        var fdSolicSearchInput = document.getElementById('fd-solic-search');
        if (fdSolicSearchInput) fdSolicSearchInput.style.display = 'none';
        document.getElementById('fd-solic-chosen-avatar').textContent = (found.business_name || '?').charAt(0).toUpperCase();
        document.getElementById('fd-solic-chosen-name').textContent = found.business_name || '';
        document.getElementById('fd-solic-chosen-meta').textContent = rubroOf(found);
        document.getElementById('fd-solic-chosen-service').textContent = serviceName;

        if (fdSolicService === 'scheduled') {
            var date = document.getElementById('fd-solic-date');
            if (date) date.value = '';
            fdSolicRenderDeposit();
        }

        showForms();
    };

    window.fdSolicBack = function () {
        fdSolicSelected = null;
        fdSolicService = null;
        document.getElementById('fd-solic-chosen').style.display = 'none';
        document.getElementById('fd-solic-pick').style.display = 'block';
        var fdSolicSearchInput = document.getElementById('fd-solic-search');
        if (fdSolicSearchInput) fdSolicSearchInput.style.display = '';
        showForms();
    };

    window.fdSolicRenderDeposit = function () {
        var el = document.getElementById('fd-solic-deposit');
        if (!el) return;

        var mode = document.getElementById('fd-solic-payment-mode').value;

        el.textContent = mode === 'FULL'
            ? 'Se cobra el total del trabajo cuando el profesional lo confirme.'
            : 'Se cobra la seña (50%) cuando el profesional confirma el precio.';
    };

    // ---------------------------------------------------------- envios

    function firstError(data, fallback) {
        if (data && data.errors) {
            var keys = Object.keys(data.errors);
            if (keys.length) return data.errors[keys[0]][0];
        }
        return (data && data.message) || fallback;
    }

    function busy(buttonId, isBusy) {
        var btn = document.getElementById(buttonId);
        if (btn) {
            btn.disabled = isBusy;
            btn.style.opacity = isBusy ? '0.6' : '1';
        }
    }

    function fdSolicPost(url, options) {
        return fetch(url, Object.assign({
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }, options)).then(function (r) {
            return r.json().then(function (data) { return { ok: r.ok, data: data }; });
        });
    }

    window.fdSolicSubmitEmergency = function (event) {
        event.preventDefault();
        if (!fdSolicSelected) return false;

        var photo = document.getElementById('fd-solic-emergency-photo').files[0];
        if (!photo) {
            msg('error', 'Adjuntá una foto del problema.');
            return false;
        }

        // La urgencia no envia ubicacion: la ventana no pide coordenadas.
        var form = new FormData();
        form.append('provider_id', fdSolicSelected.id);
        form.append('photo', photo);
        form.append('description', document.getElementById('fd-solic-emergency-desc').value || '');

        var audio = document.getElementById('fd-solic-emergency-audio').files[0];
        if (audio) form.append('audio', audio);

        msgHide();
        busy('fd-solic-emergency-submit', true);

        fdSolicPost(FD_SOLIC_URLS.emergency, { method: 'POST', body: form })
            .then(function (res) {
                busy('fd-solic-emergency-submit', false);
                if (!res.ok || !res.data || res.data.success === false) {
                    msg('error', firstError(res.data, 'No se pudo enviar la urgencia.'));
                    return;
                }
                msg('ok', res.data.message + '\nSeguí el estado en "Mis Servicios".');
            })
            .catch(function () {
                busy('fd-solic-emergency-submit', false);
                msg('error', 'No se pudo enviar la urgencia. Revisá tu conexión.');
            });

        return false;
    };

    window.fdSolicSubmitScheduled = function (event) {
        event.preventDefault();
        if (!fdSolicSelected) return false;

        var date = document.getElementById('fd-solic-date').value;
        var time = document.getElementById('fd-solic-time').value;

        if (!date || !time) {
            msg('error', 'Elegí la fecha y el horario de la cita.');
            return false;
        }

        var payload = {
            provider_id: fdSolicSelected.id,
            scheduled_at: date + ' ' + time + ':00',
            fulfillment: document.getElementById('fd-solic-fulfillment').value,
            payment_method: document.getElementById('fd-solic-payment-method').value,
            payment_mode: document.getElementById('fd-solic-payment-mode').value,
            notes: document.getElementById('fd-solic-notes').value || ''
        };

        msgHide();
        busy('fd-solic-scheduled-submit', true);

        fdSolicPost(FD_SOLIC_URLS.scheduled, {
            method: 'POST',
            body: JSON.stringify(payload)
        })
            .then(function (res) {
                busy('fd-solic-scheduled-submit', false);
                if (!res.ok || !res.data || res.data.success === false) {
                    msg('error', firstError(res.data, 'No se pudo reservar la cita.'));
                    return;
                }
                msg('ok', res.data.message + '\nSeguí el estado en "Mis Servicios".');
                document.getElementById('fd-solic-date').value = '';
            })
            .catch(function () {
                busy('fd-solic-scheduled-submit', false);
                msg('error', 'No se pudo reservar la cita. Revisá tu conexión.');
            });

        return false;
    };

    window.fdSolicSubmitQuote = function (event) {
        event.preventDefault();
        if (!fdSolicSelected) return false;

        var details = document.getElementById('fd-solic-quote-details').value.trim();
        if (!details) {
            msg('error', 'Contanos qué trabajo necesitás cotizar.');
            return false;
        }

        var photos = document.getElementById('fd-solic-quote-photos').files;
        if (!photos.length) {
            msg('error', 'Adjuntá al menos una foto del trabajo.');
            return false;
        }
        if (photos.length > 5) {
            msg('error', 'Podés adjuntar hasta 5 fotos.');
            return false;
        }

        var form = new FormData();
        form.append('provider_id', fdSolicSelected.id);
        form.append('details', details);
        for (var i = 0; i < photos.length; i++) form.append('photos[]', photos[i]);

        var video = document.getElementById('fd-solic-quote-video').files[0];
        if (video) form.append('video', video);

        msgHide();
        busy('fd-solic-quote-submit', true);

        fdSolicPost(FD_SOLIC_URLS.quote, { method: 'POST', body: form })
            .then(function (res) {
                busy('fd-solic-quote-submit', false);
                if (!res.ok || !res.data || res.data.success === false) {
                    msg('error', firstError(res.data, 'No se pudo enviar la solicitud de presupuesto.'));
                    return;
                }
                msg('ok', res.data.message + '\nSeguí la propuesta en "Mis Servicios".');
                document.getElementById('fd-solic-quote-details').value = '';
                document.getElementById('fd-solic-quote-photos').value = '';
                document.getElementById('fd-solic-quote-video').value = '';
            })
            .catch(function () {
                busy('fd-solic-quote-submit', false);
                msg('error', 'No se pudo enviar la solicitud de presupuesto. Revisá tu conexión.');
            });

        return false;
    };

    // ---------------------------------------------------------- init

    window.addEventListener('load', function () {
        var section = document.getElementById('dash-solicitar-servicio');
        if (!section) return;

        var today = new Date();
        var min = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
        var dateInput = document.getElementById('fd-solic-date');
        if (dateInput) dateInput.setAttribute('min', min);

        // La primera carga decide el tab por defecto (favoritos si hay, si no todos).
        loadProviders();
    });
})();
</script>
