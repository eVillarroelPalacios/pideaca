{{-- Solicitar Servicio (Modulo B): urgencias 24/7, citas programadas y
     presupuestos a medida. El cliente busca y elige un prestador con el
     mismo esquema que los comercios (buscador + tarjetas); la orden se le
     asigna directo al elegido y el precio lo define el prestador. --}}
<section id="dash-solicitar-servicio" class="dash-section" data-module-id="{{ $page->module_id }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p id="fd-solic-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">Buscá al prestador que necesitás y pedile el servicio.</p>
        </div>
    </div>

    <div id="fd-solic-tabs" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
        <button type="button" id="fd-solic-tab-emergency" onclick="fdSolicTab('emergency')" style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;font-size:13px;font-weight:600;color:#64748b;background:#fff;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v6"/><path d="m8 8 4 4 4-4"/><path d="M4 14h16"/><path d="M6 18h12a2 2 0 0 0 2-2v-2H4v2a2 2 0 0 0 2 2z"/></svg>
            <span>Urgencia 24/7</span>
        </button>
        <button type="button" id="fd-solic-tab-scheduled" onclick="fdSolicTab('scheduled')" style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;font-size:13px;font-weight:600;color:#64748b;background:#fff;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
            <span>Cita programada</span>
        </button>
        <button type="button" id="fd-solic-tab-quote" onclick="fdSolicTab('quote')" style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;font-size:13px;font-weight:600;color:#64748b;background:#fff;border:1px solid #e5e7eb;border-radius:4px;cursor:pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 11h2"/></svg>
            <span>Presupuesto</span>
        </button>
    </div>

    <div id="fd-solic-message" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:12px 16px;margin-bottom:14px;">
        <p id="fd-solic-message-text" style="font-size:13px;color:#374151;margin:0;white-space:pre-line;"></p>
    </div>

    {{-- Buscador + grilla de prestadores (esquema Comercios) --}}
    <div id="fd-solic-pick" style="background:#fff;border:1px solid #e5e7eb;padding:16px;margin-bottom:14px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;flex-wrap:wrap;">
            <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0;text-transform:uppercase;letter-spacing:.5px;">1. Elegí el prestador</p>
            <input id="fd-solic-search" type="text" placeholder="Buscar prestador..." oninput="fdSolicSearchDebounce()" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;min-width:200px;background:#fff;outline:none;">
        </div>

        <div id="fd-solic-loading" style="display:none;text-align:center;padding:24px;">
            <p style="font-size:13px;color:#6b7280;margin:0;">Cargando prestadores...</p>
        </div>

        <div id="fd-solic-empty" style="display:none;border:1px dashed #d1d5db;padding:28px 20px;text-align:center;">
            <p id="fd-solic-empty-text" style="font-size:13px;color:#6b7280;margin:0;">No hay prestadores publicados todavia.</p>
        </div>

        <div id="fd-solic-grid" style="display:none;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px;"></div>
    </div>

    {{-- Prestador elegido + formulario del tipo de servicio --}}
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
                <p style="font-size:11px;color:#6b7280;margin:0;">El precio lo define el profesional</p>
            </div>
        </div>
    </div>

    <form id="fd-solic-form-emergency" onsubmit="return fdSolicSubmitEmergency(event)" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:16px;">
        <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">2. Contanos el problema</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;">
            <div style="grid-column:1/-1;">
                <label for="fd-solic-emergency-desc" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Descripción (opcional)</label>
                <textarea id="fd-solic-emergency-desc" rows="2" maxlength="1000" placeholder="Ej: Pérdida de gas bajo la bacha de la cocina" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;outline:none;"></textarea>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Ubicación</label>
                <div style="display:flex;gap:6px;align-items:center;">
                    <input type="text" id="fd-solic-lat" placeholder="Latitud" inputmode="decimal" style="width:50%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;">
                    <input type="text" id="fd-solic-lng" placeholder="Longitud" inputmode="decimal" style="width:50%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;">
                </div>
                <button type="button" onclick="fdSolicLocate()" style="margin-top:6px;background:#fff;color:#0c2a4d;border:1px solid #d1d5db;padding:7px 12px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Usar mi ubicación actual
                </button>
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
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px;flex-wrap:wrap;">
            <p id="fd-solic-emergency-fee" style="font-size:12px;color:#6b7280;margin:0;">El prestador elegido define el precio al atender la urgencia.</p>
            <button type="submit" id="fd-solic-emergency-submit" style="background:#D24C19;color:#fff;border:none;padding:10px 20px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">Pedir urgencia</button>
        </div>
    </form>

    <form id="fd-solic-form-scheduled" onsubmit="return fdSolicSubmitScheduled(event)" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:16px;">
        <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">2. Elegí fecha y horario</p>
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

        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px;flex-wrap:wrap;">
            <p id="fd-solic-deposit" style="font-size:12px;color:#6b7280;margin:0;"></p>
            <button type="submit" id="fd-solic-scheduled-submit" style="background:#D24C19;color:#fff;border:none;padding:10px 20px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">Reservar cita</button>
        </div>
    </form>

    <form id="fd-solic-form-quote" onsubmit="return fdSolicSubmitQuote(event)" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:16px;">
        <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">2. Detallá el trabajo</p>
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
            <button type="submit" id="fd-solic-quote-submit" style="background:#D24C19;color:#fff;border:none;padding:10px 20px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;">Pedir presupuesto</button>
        </div>
    </form>
</section>

<script>
(function () {
    'use strict';

    var FD_SOLIC_URLS = {
        providers: '{{ url("/api/v1/service-providers") }}',
        emergency: '{{ url("/api/v1/customer/service-orders/emergency") }}',
        scheduled: '{{ url("/api/v1/customer/service-orders/scheduled") }}',
        quote: '{{ url("/api/v1/customer/service-orders/quote") }}'
    };

    var FD_SOLIC_TAB_CODE = { emergency: 'EMERGENCY', scheduled: 'SCHEDULED', quote: 'QUOTE' };

    var fdSolicTab = 'emergency';
    var fdSolicProviders = [];
    var fdSolicSelected = null;
    var fdSolicSearch = '';
    var fdSolicTimer = null;

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
        var form = document.getElementById('fd-solic-form-' + fdSolicTab);
        if (form) form.style.display = fdSolicSelected ? 'block' : 'none';
    }

    // ---------------------------------------------------------- tabs

    window.fdSolicTab = function (tab) {
        fdSolicTab = tab;
        ['emergency', 'scheduled', 'quote'].forEach(function (key) {
            var btn = document.getElementById('fd-solic-tab-' + key);
            var active = key === tab;
            if (btn) {
                btn.style.background = active ? '#fff1eb' : '#fff';
                btn.style.color = active ? '#D24C19' : '#64748b';
                btn.style.borderColor = active ? '#D24C19' : '#e5e7eb';
            }
        });
        // Cambiar de tipo de servicio invalida la eleccion anterior.
        if (fdSolicSelected) fdSolicBack();
        else showForms();
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

    function loadProviders() {
        var loading = document.getElementById('fd-solic-loading');
        pickState('loading');

        var url = FD_SOLIC_URLS.providers + '?type=' + FD_SOLIC_TAB_CODE[fdSolicTab];
        if (fdSolicSearch) url += '&search=' + encodeURIComponent(fdSolicSearch);

        fdFetchJson(url)
            .then(function (res) {
                if (!res.ok || !res.data || res.data.success === false) {
                    pickState(null);
                    msg('error', (res.data && res.data.message) || 'No se pudieron cargar los prestadores.');
                    return;
                }
                fdSolicProviders = (res.data.data && res.data.data.providers) || [];
                renderProviders();
            })
            .catch(function () {
                pickState(null);
                msg('error', 'No se pudieron cargar los prestadores. Revisá tu conexión.');
            });
    }

    function renderProviders() {
        msgHide();
        var grid = document.getElementById('fd-solic-grid');
        var empty = document.getElementById('fd-solic-empty');
        var emptyText = document.getElementById('fd-solic-empty-text');

        if (!fdSolicProviders.length) {
            if (emptyText) {
                emptyText.textContent = fdSolicSearch
                    ? 'No encontramos prestadores para "' + fdSolicSearch + '".'
                    : 'No hay prestadores publicados todavia para este tipo de servicio.';
            }
            pickState('empty');
            return;
        }

        if (grid) grid.innerHTML = fdSolicProviders.map(renderCard).join('');
        pickState('grid');

        var summary = document.getElementById('fd-solic-summary');
        if (summary) {
            summary.textContent = fdSolicProviders.length + ' prestador' + (fdSolicProviders.length === 1 ? '' : 'es') + ' disponible' + (fdSolicProviders.length === 1 ? '' : 's');
        }
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
            + '<div style="font-size:11px;color:#94a3b8;">' + esc(rubroOf(p)) + '</div>'
            + '</div>';

        if (p.rating && parseFloat(p.rating) > 0) {
            html += '<div style="margin-left:auto;font-size:12px;font-weight:700;color:#b45309;white-space:nowrap;">★ ' + parseFloat(p.rating).toFixed(1) + '</div>';
        }

        html += '</div>';

        if (p.promo) {
            html += '<div style="font-size:11px;color:#047857;background:#ecfdf5;padding:6px 8px;">' + esc(p.promo) + '</div>';
        }

        html += '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:auto;flex-wrap:wrap;">'
            + '<span style="font-size:11px;color:#6b7280;">' + typeBadges(p) + '</span>'
            + '<button type="button" onclick="fdSolicPick(' + p.id + ')" style="background:#D24C19;color:#fff;border:none;border-radius:4px;cursor:pointer;padding:7px 14px;font-size:12px;font-weight:700;">Elegir</button>'
            + '</div>';

        html += '</div>';
        return html;
    }

    function rubroOf(p) {
        if (p.subgroups && p.subgroups.length) return p.subgroups[0].description;
        return p.zone || 'Servicios del hogar';
    }

    function typeBadges(p) {
        if (!p.service_types || !p.service_types.length) return '';
        return p.service_types.map(function (t) {
            return esc(t.name);
        }).join(' · ');
    }

    // ---------------------------------------------------------- eleccion

    window.fdSolicPick = function (id) {
        var found = null;
        fdSolicProviders.forEach(function (p) {
            if (p.id === id) found = p;
        });
        if (!found) return;

        fdSolicSelected = found;

        document.getElementById('fd-solic-pick').style.display = 'none';
        document.getElementById('fd-solic-chosen').style.display = 'block';
        document.getElementById('fd-solic-chosen-avatar').textContent = (found.business_name || '?').charAt(0).toUpperCase();
        document.getElementById('fd-solic-chosen-name').textContent = found.business_name || '';
        document.getElementById('fd-solic-chosen-meta').textContent = rubroOf(found) + (found.zone ? ' · ' + found.zone : '');

        if (fdSolicTab === 'scheduled') {
            var date = document.getElementById('fd-solic-date');
            if (date) date.value = '';
            fdSolicRenderDeposit();
        }

        showForms();
    };

    window.fdSolicBack = function () {
        fdSolicSelected = null;
        document.getElementById('fd-solic-chosen').style.display = 'none';
        document.getElementById('fd-solic-pick').style.display = 'block';
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

    // ---------------------------------------------------------- GPS

    window.fdSolicLocate = function () {
        if (!navigator.geolocation) {
            msg('error', 'Tu navegador no permite obtener la ubicación. Ingresá latitud y longitud a mano.');
            return;
        }

        msg('ok', 'Obteniendo ubicación...');
        navigator.geolocation.getCurrentPosition(function (position) {
            document.getElementById('fd-solic-lat').value = position.coords.latitude.toFixed(7);
            document.getElementById('fd-solic-lng').value = position.coords.longitude.toFixed(7);
            msgHide();
        }, function () {
            msg('error', 'No se pudo obtener tu ubicación. Ingresá latitud y longitud a mano.');
        }, { enableHighAccuracy: true, timeout: 10000 });
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

        var lat = parseFloat(document.getElementById('fd-solic-lat').value);
        var lng = parseFloat(document.getElementById('fd-solic-lng').value);

        if (isNaN(lat) || isNaN(lng)) {
            msg('error', 'Marcá tu ubicación con el botón o ingresá latitud y longitud.');
            return false;
        }

        var photo = document.getElementById('fd-solic-emergency-photo').files[0];
        if (!photo) {
            msg('error', 'Adjuntá una foto del problema.');
            return false;
        }

        var form = new FormData();
        form.append('provider_id', fdSolicSelected.id);
        form.append('latitude', lat);
        form.append('longitude', lng);
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

        window.fdSolicTab('emergency');
    });
})();
</script>
