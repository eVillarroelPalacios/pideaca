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
            <div style="min-width:0;flex:1 1 220px;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <p id="fd-solic-chosen-name" style="font-size:14px;font-weight:700;color:#0f172a;margin:0;"></p>
                    <span id="fd-solic-chosen-verified" title="Prestador Verificado" style="display:none;align-items:center;gap:4px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:9999px;padding:2px 9px;font-size:10px;font-weight:700;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Prestador Verificado
                    </span>
                </div>
                <p id="fd-solic-chosen-meta" style="font-size:12px;color:#64748b;margin:3px 0 0;"></p>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:6px;">
                    <span id="fd-solic-chosen-rating" style="display:none;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#0f172a;"></span>
                    <span id="fd-solic-chosen-status" style="display:inline-flex;align-items:center;gap:5px;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;border-radius:9999px;padding:2px 9px;font-size:11px;font-weight:700;"></span>
                </div>
            </div>
            <div style="text-align:right;margin-left:auto;">
                <p id="fd-solic-chosen-service" style="font-size:12px;font-weight:700;color:#D24C19;margin:0;"></p>
                <p style="font-size:11px;color:#6b7280;margin:3px 0 0;">El precio lo define el profesional</p>
            </div>
        </div>

        {{-- Tarifa base de la visita/urgencia: informada antes de solicitar --}}
        <div id="fd-solic-chosen-fee" style="display:none;margin-top:12px;background:#fff7ed;border:1px solid #fed7aa;border-left:4px solid #D24C19;border-radius:4px;padding:10px 12px;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;flex:none;border-radius:50%;background:#fff;color:#D24C19;border:1px solid #fed7aa;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 18v-6a5 5 0 1 1 10 0v6"/><path d="M5 21a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-1a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2z"/><path d="M21 12h1"/><path d="M12 2v1"/></svg>
                </span>
                <div style="min-width:0;">
                    <p id="fd-solic-chosen-fee-title" style="font-size:10px;font-weight:700;color:#9a3412;margin:0;text-transform:uppercase;letter-spacing:.5px;"></p>
                    <p id="fd-solic-chosen-fee-amount" style="font-size:18px;font-weight:700;color:#0c2a4d;margin:2px 0 0;line-height:1.1;"></p>
                </div>
                <p id="fd-solic-chosen-fee-note" style="font-size:11px;color:#9a3412;margin:0;flex:1 1 200px;"></p>
            </div>
        </div>
    </div>

    {{-- Seguimiento en vivo de la urgencia recien enviada: reemplaza el --}}
    {{-- formulario y el prestador mientras se espera/despacha la orden --}}
    <div id="fd-solic-track" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:16px;margin-bottom:14px;">
        <style>
            #fd-solic-track .fd-step { display:flex;align-items:center;gap:8px;flex:1;min-width:0; }
            #fd-solic-track .fd-step-dot { display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;flex:none;border-radius:50%;border:2px solid #d1d5db;background:#fff;color:#94a3b8;font-size:11px;font-weight:700; }
            #fd-solic-track .fd-step-label { font-size:11.5px;font-weight:600;color:#94a3b8;white-space:nowrap; }
            #fd-solic-track .fd-step.is-done .fd-step-dot { background:#047857;border-color:#047857;color:#fff; }
            #fd-solic-track .fd-step.is-current .fd-step-dot { background:#D24C19;border-color:#D24C19;color:#fff; }
            #fd-solic-track .fd-step.is-done .fd-step-label, #fd-solic-track .fd-step.is-current .fd-step-label { color:#0c2a4d; }
            #fd-solic-track .fd-step-line { flex:1;min-width:14px;height:2px;background:#e5e7eb; }
            #fd-solic-track .fd-step-line.is-done { background:#047857; }
            #fd-solic-track .fd-shimmer { position:relative;height:10px;background:#f1f5f9;border-radius:9999px;overflow:hidden; }
            #fd-solic-track .fd-shimmer::after { content:'';position:absolute;top:0;bottom:0;width:34%;border-radius:9999px;background:linear-gradient(90deg,rgba(210,76,25,0),#D24C19,rgba(210,76,25,0));animation:fdTrackShimmer 1.5s ease-in-out infinite; }
            #fd-solic-track .fd-contact { display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:6px;padding:12px 18px;font-size:13px;font-weight:700;text-decoration:none; }
            #fd-solic-track .fd-avatar { width:64px;height:64px;flex:none;border-radius:50%;object-fit:cover;border:2px solid #fed7aa;background:#fff1eb;color:#D24C19;font-weight:800;display:flex;align-items:center;justify-content:center;font-size:24px;overflow:hidden; }
            @keyframes fdTrackShimmer { 0% { transform:translateX(-110%); } 100% { transform:translateX(320%); } }
        </style>
        <div id="fd-solic-track-body"></div>
    </div>

    {{-- Confirmacion de la cita programada recien reservada: reemplaza el --}}
    {{-- formulario con el resumen del turno y el estado pendiente. --}}
    <div id="fd-solic-scheduled-confirm" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:18px;margin-bottom:14px;">
        <style>
            #fd-solic-scheduled-confirm .fd-conf-ico { display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;flex:none;border-radius:50%;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0; }
            #fd-solic-scheduled-confirm .fd-conf-row { display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-top:1px solid #f1f5f9; }
            #fd-solic-scheduled-confirm .fd-conf-row:first-child { border-top:0; }
            #fd-solic-scheduled-confirm .fd-conf-lbl { font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin:0 0 2px; }
            #fd-solic-scheduled-confirm .fd-conf-val { font-size:14px;font-weight:700;color:#0f172a;margin:0; }
            #fd-solic-scheduled-confirm .fd-conf-gcal { display:inline-flex;align-items:center;justify-content:center;gap:9px;width:100%;background:#fff;color:#0c2a4d;border:1.5px solid #d1d5db;border-radius:8px;padding:13px 18px;font-size:13.5px;font-weight:700;text-decoration:none;cursor:pointer;transition:all .12s; }
            #fd-solic-scheduled-confirm .fd-conf-gcal:hover { border-color:#D24C19;background:#fff7ed;color:#D24C19; }
            #fd-solic-scheduled-confirm .fd-conf-cta { display:inline-flex;align-items:center;justify-content:center;gap:9px;width:100%;background:#0c2a4d;color:#fff;border:none;border-radius:8px;padding:14px 18px;font-size:14px;font-weight:800;cursor:pointer;transition:background .12s; }
            #fd-solic-scheduled-confirm .fd-conf-cta:hover { background:#0a2140; }
            #fd-solic-scheduled-confirm .fd-conf-link { background:none;border:none;color:#6b7280;font-size:12.5px;font-weight:600;cursor:pointer;text-decoration:underline;padding:6px; }
        </style>
        <div id="fd-solic-scheduled-confirm-body"></div>
    </div>

    <form id="fd-solic-form-emergency" onsubmit="return fdSolicSubmitEmergency(event)" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:16px;">
        <style>
            #fd-solic-form-emergency .fd-chip { display:inline-flex;align-items:center;gap:6px;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:9999px;padding:7px 13px;font-size:12.5px;font-weight:600;cursor:pointer;transition:all .12s; }
            #fd-solic-form-emergency .fd-chip:hover { border-color:#D24C19;color:#D24C19;background:#fff7ed; }
            #fd-solic-form-emergency .fd-chip.is-active { background:#fff1eb;border-color:#D24C19;color:#D24C19;box-shadow:0 0 0 2px rgba(210,76,25,.12); }
            #fd-solic-form-emergency .fd-media-btn { display:flex;align-items:center;gap:10px;width:100%;text-align:left;background:#f9fafb;border:1.5px dashed #cbd5e1;border-radius:6px;padding:12px 14px;font-size:13px;font-weight:600;color:#0c2a4d;cursor:pointer;transition:all .12s; }
            #fd-solic-form-emergency .fd-media-btn:hover { border-color:#D24C19;background:#fff7ed;color:#D24C19; }
            #fd-solic-form-emergency .fd-media-btn.has-file { border-style:solid;border-color:#D24C19;background:#fff1eb;color:#D24C19; }
            #fd-solic-form-emergency .fd-media-btn .fd-media-ico { display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;flex:none;border-radius:50%;background:#fff;color:#D24C19;border:1px solid #fed7aa; }
            #fd-solic-form-emergency .fd-pay { position:relative;display:inline-flex; }
            #fd-solic-form-emergency .fd-pay input { position:absolute;inset:0;width:100%;height:100%;opacity:0;margin:0;cursor:pointer; }
            #fd-solic-form-emergency .fd-pay-face { display:inline-flex;align-items:center;gap:8px;background:#fff;color:#374151;border:1.5px solid #d1d5db;border-radius:6px;padding:11px 15px;font-size:13px;font-weight:600;cursor:pointer;transition:all .12s; }
            #fd-solic-form-emergency .fd-pay-face:hover { border-color:#D24C19;color:#D24C19;background:#fff7ed; }
            #fd-solic-form-emergency .fd-pay input:checked + .fd-pay-face { background:#fff1eb;border-color:#D24C19;color:#D24C19;box-shadow:0 0 0 2px rgba(210,76,25,.14); }
            #fd-solic-form-emergency .fd-pay input:focus-visible + .fd-pay-face { outline:2px solid #0c2a4d;outline-offset:2px; }
            #fd-solic-form-emergency #fd-solic-emergency-submit:hover { background:#bd4115; }
            #fd-solic-form-emergency #fd-solic-emergency-submit:active { transform:translateY(1px); }
        </style>
        <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">Contanos el problema</p>

        {{-- Opciones rapidas: un toque completa la descripcion --}}
        <div id="fd-solic-emergency-chips" role="group" aria-label="Problemas frecuentes" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
            <button type="button" class="fd-chip" data-problem="Pérdida de agua" onclick="fdSolicPickChip(this)">💧 Pérdida de agua</button>
            <button type="button" class="fd-chip" data-problem="Inundación" onclick="fdSolicPickChip(this)">🌊 Inundación</button>
            <button type="button" class="fd-chip" data-problem="Sin luz" onclick="fdSolicPickChip(this)">💡 Sin luz</button>
            <button type="button" class="fd-chip" data-problem="Pérdida de gas" onclick="fdSolicPickChip(this)">🔥 Pérdida de gas</button>
            <button type="button" class="fd-chip" data-problem="" data-free="1" onclick="fdSolicPickChip(this)">✍️ Otro</button>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;">
            <div style="grid-column:1/-1;">
                <label for="fd-solic-emergency-desc" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Descripción (opcional)</label>
                <textarea id="fd-solic-emergency-desc" rows="2" maxlength="1000" oninput="fdSolicClearChips()" placeholder="Ej: Pérdida de gas bajo la bacha de la cocina" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;outline:none;"></textarea>
            </div>

            {{-- Direccion: se completa con la ubicacion actual --}}
            <div style="grid-column:1/-1;">
                <label for="fd-solic-emergency-address" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Dirección donde te urge (opcional)</label>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:stretch;">
                    <input type="text" id="fd-solic-emergency-address" maxlength="500" placeholder="Calle, número, piso/depto" style="flex:1 1 220px;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;">
                    <button type="button" id="fd-solic-emergency-geo" onclick="fdSolicUseLocation()" style="display:inline-flex;align-items:center;gap:7px;background:#0c2a4d;color:#fff;border:none;border-radius:4px;padding:9px 14px;font-size:12.5px;font-weight:700;cursor:pointer;white-space:nowrap;">
                        📍 Usar mi ubicación actual
                    </button>
                </div>
                <p id="fd-solic-emergency-geo-status" style="display:none;font-size:11.5px;margin:6px 0 0;"></p>
            </div>

            {{-- Acciones de foto y audio con botones estilizados --}}
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Foto del problema (obligatoria, máx 5 MB)</label>
                <input type="file" id="fd-solic-emergency-photo" accept="image/jpeg,image/png,image/webp" onchange="fdSolicOnMediaChange('photo')" style="display:none;">
                <button type="button" id="fd-solic-emergency-photo-btn" class="fd-media-btn" onclick="fdSolicPickFile('fd-solic-emergency-photo')">
                    <span class="fd-media-ico">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                    </span>
                    <span id="fd-solic-emergency-photo-label">📷 Tomar / Subir Foto</span>
                </button>
                <div id="fd-solic-emergency-photo-info" style="display:none;align-items:center;gap:8px;margin-top:8px;font-size:11.5px;color:#047857;font-weight:600;"></div>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Audio (opcional, máx 8 MB)</label>
                <input type="file" id="fd-solic-emergency-audio" accept="audio/*" onchange="fdSolicOnMediaChange('audio')" style="display:none;">
                <button type="button" id="fd-solic-emergency-audio-btn" class="fd-media-btn" onclick="fdSolicPickFile('fd-solic-emergency-audio')">
                    <span class="fd-media-ico">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><path d="M12 19v3"/></svg>
                    </span>
                    <span id="fd-solic-emergency-audio-label">🎙️ Grabar Audio</span>
                </button>
                <div id="fd-solic-emergency-audio-info" style="display:none;align-items:center;gap:8px;margin-top:8px;font-size:11.5px;color:#047857;font-weight:600;"></div>
            </div>
        </div>
        {{-- Medio de pago de la tarifa base --}}
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid #eef2f7;">
            <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:8px;">Medio de pago de la tarifa base</label>
            <div id="fd-solic-emergency-payment" role="radiogroup" aria-label="Medio de pago de la tarifa base" style="display:flex;gap:8px;flex-wrap:wrap;">
                <label class="fd-pay">
                    <input type="radio" name="fd-solic-payment" value="mercado_pago" checked>
                    <span class="fd-pay-face">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:19px;height:19px;flex:none;border-radius:4px;background:#00b6ea;color:#fff;font-size:10px;font-weight:800;">MP</span>
                        Mercado Pago
                    </span>
                </label>
                <label class="fd-pay">
                    <input type="radio" name="fd-solic-payment" value="tarjeta">
                    <span class="fd-pay-face">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/></svg>
                        Tarjeta
                    </span>
                </label>
                <label class="fd-pay">
                    <input type="radio" name="fd-solic-payment" value="efectivo">
                    <span class="fd-pay-face">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                        Efectivo
                    </span>
                </label>
            </div>
            <p style="font-size:11px;color:#6b7280;margin:8px 0 0;">Se aplica a la tarifa base de la visita. Podés cambiarlo al confirmar el trabajo.</p>
        </div>

        {{-- CTA principal --}}
        <button type="submit" id="fd-solic-emergency-submit" title="Solicitar urgencia ahora" aria-label="Solicitar urgencia ahora" style="width:100%;margin-top:16px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;background:#D24C19;color:#fff;border:none;padding:17px 24px;border-radius:6px;cursor:pointer;box-shadow:0 6px 16px rgba(210,76,25,.30);">
            <span style="display:inline-flex;align-items:center;gap:8px;font-size:17px;font-weight:800;letter-spacing:.4px;line-height:1.2;">🚨 SOLICITAR URGENCIA AHORA</span>
            <span style="font-size:11.5px;font-weight:600;opacity:.93;">Respuesta estimada: &lt; 3 min</span>
        </button>
    </form>

    <form id="fd-solic-form-scheduled" onsubmit="return fdSolicSubmitScheduled(event)" style="display:none;background:#fff;border:1px solid #e5e7eb;padding:16px;">
        <style>
            #fd-solic-calendar .fd-cal-day { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;min-width:0;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:6px;padding:8px 2px;cursor:pointer;transition:all .12s; }
            #fd-solic-calendar .fd-cal-day:hover { border-color:#D24C19;background:#fff7ed;color:#D24C19; }
            #fd-solic-calendar .fd-cal-day .fd-cal-wd { font-size:10px;font-weight:600;text-transform:uppercase;opacity:.72; }
            #fd-solic-calendar .fd-cal-day .fd-cal-num { font-size:16px;font-weight:700;line-height:1; }
            #fd-solic-calendar .fd-cal-day.is-today .fd-cal-num { color:#D24C19; }
            #fd-solic-calendar .fd-cal-day.is-selected { background:#D24C19;border-color:#D24C19;color:#fff;box-shadow:0 3px 10px rgba(210,76,25,.28); }
            #fd-solic-calendar .fd-cal-day.is-selected .fd-cal-wd,
            #fd-solic-calendar .fd-cal-day.is-selected .fd-cal-num { color:#fff;opacity:1; }
            #fd-solic-calendar .fd-cal-day.is-closed { background:#f8fafc;color:#cbd5e1;border-style:dashed;cursor:not-allowed; }
            #fd-solic-calendar .fd-cal-day.is-closed:hover { border-color:#e5e7eb;background:#f8fafc;color:#cbd5e1; }
            #fd-solic-slots .fd-slot { display:inline-flex;align-items:center;gap:6px;background:#fff;color:#374151;border:1.5px solid #d1d5db;border-radius:9999px;padding:8px 15px;font-size:12.5px;font-weight:600;cursor:pointer;transition:all .12s; }
            #fd-solic-slots .fd-slot:hover { border-color:#D24C19;color:#D24C19;background:#fff7ed; }
            #fd-solic-slots .fd-slot.is-selected { background:#fff1eb;border-color:#D24C19;color:#D24C19;box-shadow:0 0 0 2px rgba(210,76,25,.14); }
            #fd-solic-reminder-chips .fd-reminder-chip { transition:all .12s; }
            #fd-solic-reminder-chips .fd-reminder-chip:hover { border-color:#D24C19;color:#D24C19;background:#fff7ed; }
            #fd-solic-reminder-chips .fd-reminder-chip.is-selected { background:#fff1eb;border-color:#D24C19;color:#D24C19;box-shadow:0 0 0 2px rgba(210,76,25,.14); }
            #fd-solic-form-scheduled #fd-solic-scheduled-submit:hover { background:#bd4115; }
            #fd-solic-form-scheduled #fd-solic-scheduled-submit:active { transform:translateY(1px); }
        </style>
        <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">Elegí fecha y horario</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
        <div style="grid-column:1/-1;">
            <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:5px;">Elegí el día</label>
            <div id="fd-solic-day-nav" style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
                <button type="button" id="fd-solic-day-prev" onclick="fdSolicShiftDays(-1)" title="Días anteriores" aria-label="Días anteriores" style="background:#fff;color:#0c2a4d;border:1px solid #d1d5db;border-radius:4px;cursor:pointer;padding:6px 10px;font-size:13px;font-weight:700;">‹</button>
                <p id="fd-solic-day-range" style="flex:1;text-align:center;font-size:12px;font-weight:600;color:#64748b;margin:0;"></p>
                <button type="button" id="fd-solic-day-next" onclick="fdSolicShiftDays(1)" title="Días siguientes" aria-label="Días siguientes" style="background:#fff;color:#0c2a4d;border:1px solid #d1d5db;border-radius:4px;cursor:pointer;padding:6px 10px;font-size:13px;font-weight:700;">›</button>
            </div>
            {{-- Calendario de disponibilidad: 14 dias, con los abiertos activos. --}}
            <div id="fd-solic-calendar" role="listbox" aria-label="Días disponibles" style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:6px;"></div>
            <p id="fd-solic-calendar-fallback" style="display:none;font-size:11px;color:#b45309;background:#fffbeb;border:1px solid #fde68a;border-radius:4px;padding:7px 10px;margin:8px 0 0;">Este prestador todavía no publicó horarios. Te mostramos los turnos estándar (09:00 a 18:00) y te confirmamos la cita al aceptar.</p>
        </div>

        {{-- Franjas horarias libres del dia elegido: se cargan al tocar un dia. --}}
        <div style="grid-column:1/-1;">
            <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:5px;">Elegí el horario</label>
            <p id="fd-solic-slots-hint" style="font-size:12px;color:#6b7280;margin:0 0 8px;">Primero tocá un día del calendario para ver los horarios libres.</p>
            <div id="fd-solic-slots-empty" style="display:none;font-size:12px;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:4px;padding:9px 12px;">No quedan horarios libres ese día. Probá con otro día.</div>
            <div id="fd-solic-slots" role="radiogroup" aria-label="Horarios disponibles" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
        </div>
        <input type="hidden" id="fd-solic-date" value="">
        <input type="hidden" id="fd-solic-time" value="">

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

            {{-- Politica de la sena y desglose del pago del turno --}}
            <div style="grid-column:1/-1;">
                <div style="background:#fff7ed;border:1px solid #fed7aa;border-left:4px solid #D24C19;border-radius:6px;padding:12px 14px;">
                    <div style="display:flex;align-items:flex-start;gap:10px;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;flex:none;border-radius:50%;background:#fff;color:#D24C19;border:1px solid #fed7aa;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </span>
                        <div style="min-width:0;">
                            <p style="font-size:12.5px;font-weight:700;color:#9a3412;margin:0;">La seña del 50% garantiza el turno y se descuenta del saldo final</p>
                            <p id="fd-solic-deposit" style="font-size:12px;color:#9a3412;margin:4px 0 0;"></p>
                        </div>
                    </div>

                    {{-- Desglose del pago (se recalcula con el prestador y el modo) --}}
                    <div style="margin-top:12px;background:#fff;border:1px solid #fed7aa;border-radius:6px;padding:10px 12px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:4px 0;">
                            <span style="font-size:12.5px;color:#6b7280;">Tarifa base de visita</span>
                            <span id="fd-solic-fee-base" style="font-size:13px;font-weight:700;color:#0f172a;">$0</span>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:4px 0;border-top:1px dashed #fed7aa;">
                            <span id="fd-solic-fee-now-label" style="font-size:12.5px;color:#6b7280;">Monto a abonar ahora (Seña 50%)</span>
                            <span id="fd-solic-fee-now" style="font-size:15px;font-weight:800;color:#D24C19;">$0</span>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:4px 0;border-top:1px dashed #fed7aa;">
                            <span style="font-size:12.5px;color:#6b7280;">Restante a pagar en el domicilio</span>
                            <span id="fd-solic-fee-rest" style="font-size:13px;font-weight:700;color:#0f172a;">$0</span>
                        </div>
                    </div>
                </div>

                {{-- Politica de cancelacion / reprogramacion --}}
                <div style="display:flex;align-items:center;gap:9px;margin-top:10px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:6px;padding:10px 12px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="flex:none;color:#047857;"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="m9 15 2 2 4-4"/></svg>
                    <p style="font-size:12.5px;font-weight:700;color:#047857;margin:0;">Cancelación/Reprogramación gratuita hasta 24 hs antes</p>
                </div>
            </div>
        </div>
            {{-- Datos de acceso: direccion estructurada del domicilio --}}
            <div style="grid-column:1/-1;">
                <p style="font-size:11px;font-weight:700;color:#94a3b8;margin:2px 0 8px;text-transform:uppercase;letter-spacing:.5px;">Datos de acceso</p>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">
                    <div style="grid-column:1/-1;">
                        <label for="fd-solic-address-street" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Calle y número</label>
                        <input type="text" id="fd-solic-address-street" maxlength="150" autocomplete="address-line1" placeholder="Ej: Av. Rivadavia 1234" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;">
                    </div>
                    <div>
                        <label for="fd-solic-address-unit" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Piso / Depto</label>
                        <input type="text" id="fd-solic-address-unit" maxlength="50" autocomplete="address-line2" placeholder="Ej: 4º B" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;">
                    </div>
                    <div>
                        <label for="fd-solic-address-neighborhood" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Barrio / Zona</label>
                        <input type="text" id="fd-solic-address-neighborhood" maxlength="100" autocomplete="address-level3" placeholder="Ej: Caballito" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;">
                    </div>
                </div>
            </div>

            {{-- Canal de recordatorio de la cita --}}
            <div style="grid-column:1/-1;">
                <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:5px;">Canal de recordatorio de la cita</label>
                <input type="hidden" id="fd-solic-reminder" value="whatsapp">
                <div id="fd-solic-reminder-chips" role="radiogroup" aria-label="Canal de recordatorio" style="display:flex;flex-wrap:wrap;gap:8px;">
                    <button type="button" class="fd-reminder-chip is-selected" data-channel="whatsapp" onclick="fdSolicPickReminder('whatsapp')" aria-pressed="true" style="display:inline-flex;align-items:center;gap:7px;background:#fff;color:#374151;border:1.5px solid #d1d5db;border-radius:9999px;padding:8px 15px;font-size:12.5px;font-weight:600;cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="#25D366" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2Zm5.8 14.03c-.24.68-1.42 1.3-1.96 1.35-.54.05-1.05.24-3.55-.74-3.01-1.18-4.92-4.27-5.07-4.47-.15-.2-1.21-1.61-1.21-3.07 0-1.46.77-2.18 1.04-2.48.27-.3.59-.37.79-.37.2 0 .39 0 .56.01.18.01.42-.07.66.5.24.58.83 2.03.9 2.18.07.15.12.32.02.52-.1.2-.15.32-.29.5-.15.17-.31.39-.44.52-.15.15-.3.31-.13.61.17.3.75 1.24 1.61 2.01 1.11.99 2.04 1.29 2.34 1.44.3.15.47.12.65-.07.18-.2.75-.87.95-1.17.2-.3.4-.25.66-.15.27.1 1.71.81 2 .96.3.15.5.22.57.35.07.12.07.72-.17 1.4Z"/></svg>
                        WhatsApp
                    </button>
                    <button type="button" class="fd-reminder-chip" data-channel="email" onclick="fdSolicPickReminder('email')" aria-pressed="false" style="display:inline-flex;align-items:center;gap:7px;background:#fff;color:#374151;border:1.5px solid #d1d5db;border-radius:9999px;padding:8px 15px;font-size:12.5px;font-weight:600;cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" style="color:#0c2a4d;"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        Email
                    </button>
                </div>
                <p id="fd-solic-reminder-hint" style="font-size:11.5px;color:#6b7280;margin:7px 0 0;">Te avisamos por WhatsApp cuando el profesional esté en camino.</p>
            </div>

            {{-- Notas e instrucciones de acceso para el profesional --}}
            <div style="grid-column:1/-1;">
                <label for="fd-solic-notes" style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Notas e instrucciones de acceso (opcional)</label>
                <textarea id="fd-solic-notes" rows="3" maxlength="1000" placeholder="Ej: Instrucciones para el profesional: tocar timbre del 4B, el portón está pintado de verde, hay perro (avisar al llegar), cochera disponible." style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;resize:vertical;outline:none;"></textarea>
                <p style="font-size:11px;color:#94a3b8;margin:5px 0 0;">Contale al profesional cómo llegar y entrar (timbre, portón, mascotas, estacionamiento).</p>
            </div>
        </div>

        <div style="margin-top:16px;">
            <button type="submit" id="fd-solic-scheduled-submit" title="Reservar cita y pagar seña" aria-label="Reservar cita y pagar seña" style="width:100%;background:#D24C19;color:#fff;border:none;padding:16px 20px;border-radius:8px;font-size:15px;font-weight:800;letter-spacing:.4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 4px 14px rgba(210,76,25,.28);transition:background .12s;">
                <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
                Reservar cita y pagar seña
            </button>
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
        quote: '{{ url("/api/v1/customer/service-orders/quote") }}',
        myAddresses: '{{ url("/api/me/addresses") }}',
        tracking: '{{ url("/api/v1/customer/service-orders") }}'
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
    // Coordenadas obtenidas con navigator.geolocation para la urgencia.
    var fdSolicEmergencyCoords = null;
    var fdSolicAddressPrefilled = false;
    var fdSolicGeoTimer = null;
    // Miniatura de la foto elegida: se libera al reemplazarla o quitarla.
    var fdSolicMediaObjectUrl = null;

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

    // ------------------------------------------------- encabezado elegido

    function fdSolicMoney(value) {
        return '$' + Number(value || 0).toLocaleString('es-AR', { maximumFractionDigits: 0 });
    }

    var FD_SOLIC_DAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    function fdSolicMinutes(time) {
        var parts = String(time || '').split(':');
        if (parts.length < 2) return null;
        var h = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10);
        if (isNaN(h) || isNaN(m)) return null;
        return h * 60 + m;
    }

    // Estado en vivo del prestador. Se recalcula en el navegador con los
    // horarios cargados para que el badge no quede viejo si la ventana queda
    // abierta (el ETA si viene calculado por el servidor).
    function fdSolicAvailability(p) {
        var eta = parseInt(p.eta_minutes || 30, 10) || 30;
        var openLabel = 'Disponible Ahora';
        var etaLabel = 'Llega en ~' + eta + ' min';

        if (!p.hours || typeof p.hours !== 'object' || Array.isArray(p.hours)) {
            var defOpen = p.is_available_now !== false;
            return { open: defOpen, label: defOpen ? openLabel : etaLabel };
        }

        var day = p.hours[FD_SOLIC_DAYS[new Date().getDay()]] || null;
        var open = false;

        if (day && day.open) {
            var now = new Date();
            var nowMin = now.getHours() * 60 + now.getMinutes();
            (day.shifts || []).forEach(function (shift) {
                var from = fdSolicMinutes(shift && shift.from);
                var to = fdSolicMinutes(shift && shift.to);
                if (from !== null && to !== null && nowMin >= from && nowMin <= to) open = true;
            });
        }

        return { open: open, label: open ? openLabel : etaLabel };
    }

    function paintAvailability(p) {
        var el = document.getElementById('fd-solic-chosen-status');
        if (!el || !p) return;
        var state = fdSolicAvailability(p);
        el.textContent = (state.open ? '🟢 ' : '🟡 ') + state.label;
        el.style.color = state.open ? '#047857' : '#b45309';
        el.style.background = state.open ? '#ecfdf5' : '#fffbeb';
        el.style.borderColor = state.open ? '#a7f3d0' : '#fde68a';
    }

    function paintRating(p) {
        var el = document.getElementById('fd-solic-chosen-rating');
        if (!el) return;

        var value = parseFloat(p.rating || 0);
        var count = parseInt(p.emergency_count || 0, 10);
        var countText = count > 0 ? ' (' + count + ' urgencia' + (count === 1 ? '' : 's') + ')' : '';
        var verified = document.getElementById('fd-solic-chosen-verified');
        if (verified) verified.style.display = p.is_verified ? 'inline-flex' : 'none';

        if (value > 0) {
            el.innerHTML = '<span style="color:#f59e0b;">★</span> ' + value.toFixed(1)
                + '<span style="color:#64748b;font-weight:600;">' + esc(countText) + '</span>';
        } else {
            el.innerHTML = '<span style="color:#94a3b8;">★ Nuevo</span>'
                + '<span style="color:#94a3b8;font-weight:600;">' + esc(countText) + '</span>';
        }
        el.style.display = 'inline-flex';
    }

    function paintFee(p) {
        var box = document.getElementById('fd-solic-chosen-fee');
        if (!box) return;

        // La tarifa base es de la visita: aplica a urgencias y citas, no al
        // presupuesto (que se arma a medida).
        if (fdSolicService !== 'emergency' && fdSolicService !== 'scheduled') {
            box.style.display = 'none';
            return;
        }

        var isEmergency = fdSolicService === 'emergency';
        var fee = p.base_visit_fee === null || p.base_visit_fee === undefined ? null : parseFloat(p.base_visit_fee);
        var hasFee = fee !== null && !isNaN(fee) && fee > 0;

        document.getElementById('fd-solic-chosen-fee-title').textContent = isEmergency
            ? 'Tarifa base por urgencia / visita'
            : 'Tarifa base por visita';
        document.getElementById('fd-solic-chosen-fee-amount').textContent = hasFee ? fdSolicMoney(fee) : 'A convenir';
        document.getElementById('fd-solic-chosen-fee-amount').style.color = hasFee ? '#0c2a4d' : '#b45309';
        document.getElementById('fd-solic-chosen-fee-note').textContent = hasFee
            ? 'Se abona al confirmar el trabajo. El profesional puede ajustar el monto final en el lugar.'
            : 'El profesional confirma el monto de la visita antes de empezar.';

        box.style.display = 'block';
    }

    function paintChosenHeader(p) {
        paintRating(p);
        paintAvailability(p);
        paintFee(p);
    }

    var fdSolicStatusTimer = null;

    function startStatusTimer() {
        stopStatusTimer();
        fdSolicStatusTimer = setInterval(function () {
            if (fdSolicSelected) paintAvailability(fdSolicSelected);
        }, 60000);
    }

    function stopStatusTimer() {
        if (fdSolicStatusTimer) {
            clearInterval(fdSolicStatusTimer);
            fdSolicStatusTimer = null;
        }
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
        // Con un prestador elegido (urgencia, cita o presupuesto) se ocultan
        // los tabs Favoritos/Todos: el formulario ocupa toda la ventana.
        var fdSolicTabsBar = document.getElementById('fd-solic-tabs');
        if (fdSolicTabsBar) fdSolicTabsBar.style.display = 'none';
        document.getElementById('fd-solic-chosen-avatar').textContent = (found.business_name || '?').charAt(0).toUpperCase();
        document.getElementById('fd-solic-chosen-name').textContent = found.business_name || '';
        document.getElementById('fd-solic-chosen-meta').textContent = rubroOf(found);
        document.getElementById('fd-solic-chosen-service').textContent = serviceName;

        paintChosenHeader(found);
        startStatusTimer();

        if (fdSolicService === 'scheduled') {
            fdSolicScheduledReset(found);
            fdSolicRenderDeposit();
        }

        if (fdSolicService === 'emergency') {
            fdSolicPrefillAddress();
        }

        showForms();
    };

    window.fdSolicBack = function () {
        fdSolicSelected = null;
        fdSolicService = null;
        stopStatusTimer();
        fdSolicTrackStop();
        var track = document.getElementById('fd-solic-track');
        if (track) track.style.display = 'none';
        var confirmEl = document.getElementById('fd-solic-scheduled-confirm');
        if (confirmEl) confirmEl.style.display = 'none';
        fdSolicSchedConfirmOrder = null;
        document.getElementById('fd-solic-chosen').style.display = 'none';
        document.getElementById('fd-solic-pick').style.display = 'block';
        var fdSolicSearchInput = document.getElementById('fd-solic-search');
        if (fdSolicSearchInput) fdSolicSearchInput.style.display = '';
        var fdSolicTabsBar = document.getElementById('fd-solic-tabs');
        if (fdSolicTabsBar) fdSolicTabsBar.style.display = 'flex';
        showForms();
    };

    window.fdSolicRenderDeposit = function () {
        var el = document.getElementById('fd-solic-deposit');
        var payMode = document.getElementById('fd-solic-payment-mode');
        var mode = payMode ? payMode.value : 'DEPOSIT';

        if (el) {
            el.textContent = mode === 'FULL'
                ? 'Con pago total abonás la tarifa base completa ahora y solo se descuenta la visita.'
                : 'La seña del 50% garantiza el turno y se descuenta del saldo final.';
        }

        // Desglose con la tarifa base del prestador elegido (si la publicó).
        var fee = fdSolicSelected && fdSolicSelected.base_visit_fee !== null && fdSolicSelected.base_visit_fee !== undefined
            ? parseFloat(fdSolicSelected.base_visit_fee)
            : null;
        var hasFee = fee !== null && !isNaN(fee) && fee > 0;

        var baseEl = document.getElementById('fd-solic-fee-base');
        var nowEl = document.getElementById('fd-solic-fee-now');
        var restEl = document.getElementById('fd-solic-fee-rest');
        var nowLabel = document.getElementById('fd-solic-fee-now-label');

        if (!hasFee) {
            // Sin tarifa publicada: el precio se confirma al aceptar el trabajo.
            if (baseEl) baseEl.textContent = 'A convenir';
            if (nowEl) nowEl.textContent = 'A convenir';
            if (restEl) restEl.textContent = 'A convenir';
            if (nowLabel) nowLabel.textContent = 'Monto a abonar ahora';
            return;
        }

        var now = mode === 'FULL' ? fee : Math.round(fee * 50) / 100;
        var rest = mode === 'FULL' ? 0 : Math.round(fee * 50) / 100;

        if (baseEl) baseEl.textContent = fdSolicMoney(fee);
        if (nowEl) nowEl.textContent = fdSolicMoney(now);
        if (restEl) restEl.textContent = fdSolicMoney(rest);
        if (nowLabel) nowLabel.textContent = mode === 'FULL' ? 'Monto a abonar ahora (Pago total)' : 'Monto a abonar ahora (Seña 50%)';
    };

    // --------------------------------------- calendario de citas (dias/horas)

    // Horizonte del calendario de citas: 14 dias a partir de hoy.
    var FD_SOLIC_SCHED_DAYS = 14;
    // Sin horarios cargados mostramos franjas estandar (fallback).
    var FD_SOLIC_SCHED_FALLBACK = { from: '09:00', to: '18:00' };
    var FD_SOLIC_SCHED_WEEKDAYS = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
    var FD_SOLIC_SCHED_MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    var fdSolicSchedDayOffset = 0;
    var fdSolicSchedDays = [];
    var fdSolicSchedDate = null;
    var fdSolicSchedTime = null;

    function fdSolicSchedKey(date) {
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return date.getFullYear() + '-' + m + '-' + d;
    }

    // Horario del prestador para un dia de la semana (clave mon..sun).
    function fdSolicSchedDayHours(date) {
        var p = fdSolicSelected;
        var key = FD_SOLIC_DAYS[date.getDay()];

        if (!p || !p.hours || typeof p.hours !== 'object' || Array.isArray(p.hours)) {
            return {
                open: true,
                shifts: [{ from: fdSolicMinutes(FD_SOLIC_SCHED_FALLBACK.from), to: fdSolicMinutes(FD_SOLIC_SCHED_FALLBACK.to) }],
                fallback: true
            };
        }

        var day = p.hours[key] || null;
        var open = Boolean(day && day.open);
        var shifts = [];

        if (open) {
            (day.shifts || []).forEach(function (shift) {
                var from = fdSolicMinutes(shift && shift.from);
                var to = fdSolicMinutes(shift && shift.to);
                if (from !== null && to !== null && to > from) shifts.push({ from: from, to: to });
            });
            if (!shifts.length) open = false;
        }

        return { open: open, shifts: shifts, fallback: false };
    }

    // Arma la lista de dias (14) con su estado de apertura para el prestador.
    function fdSolicSchedBuildDays() {
        var today = new Date();
        today.setHours(0, 0, 0, 0);

        fdSolicSchedDays = [];
        for (var i = 0; i < FD_SOLIC_SCHED_DAYS; i++) {
            var date = new Date(today.getTime());
            date.setDate(today.getDate() + i);
            var info = fdSolicSchedDayHours(date);
            fdSolicSchedDays.push({
                date: date,
                key: fdSolicSchedKey(date),
                open: info.open,
                shifts: info.shifts,
                fallback: info.fallback
            });
        }
    }

    function fdSolicSchedVisibleDays() {
        return fdSolicSchedDays.slice(fdSolicSchedDayOffset, fdSolicSchedDayOffset + 7);
    }

    function fdSolicSchedRangeLabel(days) {
        if (!days.length) return '';
        var first = days[0].date;
        var last = days[days.length - 1].date;
        return first.getDate() + ' ' + FD_SOLIC_SCHED_MONTHS[first.getMonth()]
            + ' – ' + last.getDate() + ' ' + FD_SOLIC_SCHED_MONTHS[last.getMonth()];
    }

    function fdSolicSchedIsToday(date) {
        var today = new Date();
        return date.getFullYear() === today.getFullYear()
            && date.getMonth() === today.getMonth()
            && date.getDate() === today.getDate();
    }

    // Pinta las 7 tarjetas visibles del calendario segun el bloque actual.
    function fdSolicSchedPaintCalendar() {
        var grid = document.getElementById('fd-solic-calendar');
        if (!grid) return;

        var today = new Date();
        today.setHours(0, 0, 0, 0);
        var visible = fdSolicSchedVisibleDays();

        var html = '';
        visible.forEach(function (day) {
            var selected = fdSolicSchedDate === day.key;
            var closed = !day.open;
            var cls = 'fd-cal-day';
            if (closed) cls += ' is-closed';
            if (selected) cls += ' is-selected';
            if (fdSolicSchedIsToday(day.date)) cls += ' is-today';
            var disabled = closed || day.date.getTime() < today.getTime();

            html += '<button type="button" class="' + cls + '" role="option" aria-selected="' + (selected ? 'true' : 'false') + '"'
                + (disabled ? ' disabled' : '')
                + ' onclick="fdSolicPickDay(\'' + day.key + '\')">'
                + '<span class="fd-cal-wd">' + FD_SOLIC_SCHED_WEEKDAYS[day.date.getDay()] + '</span>'
                + '<span class="fd-cal-num">' + day.date.getDate() + '</span>'
                + '</button>';
        });

        grid.innerHTML = html;

        var range = document.getElementById('fd-solic-day-range');
        if (range) range.textContent = fdSolicSchedRangeLabel(visible);

        var prev = document.getElementById('fd-solic-day-prev');
        var next = document.getElementById('fd-solic-day-next');
        if (prev) prev.disabled = fdSolicSchedDayOffset <= 0;
        if (next) next.disabled = fdSolicSchedDayOffset + 7 >= FD_SOLIC_SCHED_DAYS;

        // Aviso de fallback si el dia elegido no tiene horarios publicados.
        var fallbackNote = document.getElementById('fd-solic-calendar-fallback');
        if (fallbackNote) {
            var chosen = fdSolicSchedDays.filter(function (d) { return d.key === fdSolicSchedDate; })[0];
            fallbackNote.style.display = chosen && chosen.fallback ? 'block' : 'none';
        }
    }

    function fdSolicSchedFormatTime(minutes) {
        var h = Math.floor(minutes / 60);
        var m = minutes % 60;
        var suffix = h < 12 ? 'AM' : 'PM';
        var h12 = h % 12;
        if (h12 === 0) h12 = 12;
        return (h12 < 10 ? '0' : '') + h12 + ':' + (m < 10 ? '0' : '') + m + ' ' + suffix;
    }

    function fdSolicSchedTimeValue(minutes) {
        var h = Math.floor(minutes / 60);
        var m = minutes % 60;
        return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
    }

    // Genera las franjas del dia: bloques de 60 min (30 si la franja es corta).
    // Hoy se descartan los turnos que ya pasaron.
    function fdSolicSchedSlotsFor(day) {
        var slots = [];
        if (!day) return slots;

        var nowMin = null;
        if (fdSolicSchedIsToday(day.date)) {
            var now = new Date();
            nowMin = now.getHours() * 60 + now.getMinutes();
        }

        day.shifts.forEach(function (shift) {
            var length = shift.to - shift.from;
            var step = length < 90 ? 30 : 60;
            for (var start = shift.from; start + step <= shift.to; start += step) {
                if (nowMin !== null && start <= nowMin) continue;
                slots.push(start);
            }
        });

        slots.sort(function (a, b) { return a - b; });
        return slots;
    }

    function fdSolicSchedPaintSlots() {
        var box = document.getElementById('fd-solic-slots');
        var hint = document.getElementById('fd-solic-slots-hint');
        var empty = document.getElementById('fd-solic-slots-empty');
        if (!box) return;

        var day = fdSolicSchedDays.filter(function (d) { return d.key === fdSolicSchedDate; })[0];

        if (!day) {
            if (hint) { hint.style.display = 'block'; hint.textContent = 'Primero tocá un día del calendario para ver los horarios libres.'; }
            if (empty) empty.style.display = 'none';
            box.innerHTML = '';
            return;
        }

        var slots = fdSolicSchedSlotsFor(day);

        if (!slots.length) {
            if (hint) hint.style.display = 'none';
            if (empty) empty.style.display = 'block';
            box.innerHTML = '';
            return;
        }

        if (hint) hint.style.display = 'none';
        if (empty) empty.style.display = 'none';

        box.innerHTML = slots.map(function (minutes) {
            var value = fdSolicSchedTimeValue(minutes);
            var selected = fdSolicSchedTime === value;
            return '<button type="button" class="fd-slot ' + (selected ? 'is-selected' : '') + '"'
                + ' role="radio" aria-checked="' + (selected ? 'true' : 'false') + '"'
                + ' onclick="fdSolicPickSlot(\'' + value + '\')">'
                + fdSolicSchedFormatTime(minutes)
                + '</button>';
        }).join('');
    }

    // Reinicia el calendario de citas al elegir un prestador programado.
    function fdSolicScheduledReset(provider) {
        fdSolicSelected = provider || fdSolicSelected;
        fdSolicSchedDayOffset = 0;
        fdSolicSchedDate = null;
        fdSolicSchedTime = null;
        fdSolicSchedBuildDays();

        var date = document.getElementById('fd-solic-date');
        if (date) date.value = '';
        var time = document.getElementById('fd-solic-time');
        if (time) time.value = '';

        fdSolicPickReminder('whatsapp');

        fdSolicSchedPaintCalendar();
        fdSolicSchedPaintSlots();
    }

    // Selector del canal de recordatorio de la cita (WhatsApp / Email).
    window.fdSolicPickReminder = function (channel) {
        var allowed = ['whatsapp', 'email'];
        if (allowed.indexOf(channel) === -1) return false;

        var hidden = document.getElementById('fd-solic-reminder');
        if (hidden) hidden.value = channel;

        var chips = document.querySelectorAll('#fd-solic-reminder-chips .fd-reminder-chip');
        Array.prototype.forEach.call(chips, function (chip) {
            var on = chip.getAttribute('data-channel') === channel;
            chip.classList.toggle('is-selected', on);
            chip.setAttribute('aria-pressed', on ? 'true' : 'false');
        });

        var hint = document.getElementById('fd-solic-reminder-hint');
        if (hint) {
            hint.textContent = channel === 'email'
                ? 'Te enviamos el recordatorio y la confirmación por email.'
                : 'Te avisamos por WhatsApp cuando el profesional esté en camino.';
        }

        return false;
    };

    window.fdSolicShiftDays = function (delta) {
        var next = fdSolicSchedDayOffset + delta * 7;
        if (next < 0 || next + 7 > FD_SOLIC_SCHED_DAYS) return;
        fdSolicSchedDayOffset = next;
        fdSolicSchedPaintCalendar();
        return false;
    };

    window.fdSolicPickDay = function (key) {
        var day = fdSolicSchedDays.filter(function (d) { return d.key === key; })[0];
        if (!day || !day.open) return false;

        fdSolicSchedDate = key;
        // Al cambiar de dia se descarta la hora anterior y se recargan los turnos.
        fdSolicSchedTime = null;

        var date = document.getElementById('fd-solic-date');
        if (date) date.value = key;
        var time = document.getElementById('fd-solic-time');
        if (time) time.value = '';

        msgHide();
        fdSolicSchedPaintCalendar();
        fdSolicSchedPaintSlots();
        return false;
    };

    window.fdSolicPickSlot = function (value) {
        fdSolicSchedTime = value;
        var time = document.getElementById('fd-solic-time');
        if (time) time.value = value;
        msgHide();
        fdSolicSchedPaintSlots();
        return false;
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

    // ------------------------------------------------ seguimiento de urgencias

    var FD_SOLIC_TRACK_STEPS = [
        { key: 'requested', label: 'Solicitada' },
        { key: 'accepted', label: 'Aceptada' },
        { key: 'on_the_way', label: 'En camino' }
    ];

    var FD_SOLIC_TRACK_PAYMENTS = {
        mercado_pago: 'Mercado Pago',
        tarjeta: 'Tarjeta',
        efectivo: 'Efectivo',
        transferencia: 'Transferencia'
    };

    var fdSolicTrackOrderId = null;
    var fdSolicTrackPollTimer = null;
    var fdSolicTrackTickTimer = null;
    var fdSolicTrackStartedAt = null;
    var fdSolicTrackStage = null;
    var fdSolicTrackLastHtml = '';
    var fdSolicTrackCancelling = false;
    // Orden de la cita programada mostrada en la pantalla de confirmacion.
    var fdSolicSchedConfirmOrder = null;
    // Contexto del pedido enviado (tarifa base elegida, medio de pago, etc.):
    // el backend recien lo persiste con su propia data cuando hay cambios.
    var fdSolicTrackContext = {};

    function fdSolicTrackStageOf(status) {
        switch (status) {
            case 'ACCEPTED': return 'accepted';
            case 'PROVIDER_DISPATCHED': return 'on_the_way';
            case 'IN_PROGRESS': return 'in_progress';
            case 'COMPLETED': return 'done';
            case 'CANCELLED': return 'cancelled';
            default: return 'waiting'; // REQUESTED / QUOTED
        }
    }

    function fdSolicTrackStepIndex(stage) {
        if (stage === 'waiting') return 0;
        if (stage === 'accepted') return 1;
        return 2; // on_the_way, in_progress y done cierran el paso En camino
    }

    function fdSolicTrackElapsed() {
        var seconds = fdSolicTrackStartedAt ? Math.max(0, Math.floor((Date.now() - fdSolicTrackStartedAt) / 1000)) : 0;
        var mm = Math.floor(seconds / 60);
        var ss = seconds % 60;
        return (mm < 10 ? '0' : '') + mm + ':' + (ss < 10 ? '0' : '') + ss;
    }

    function fdSolicTrackStepsHtml(stage) {
        var current = fdSolicTrackStepIndex(stage);
        var html = '';
        FD_SOLIC_TRACK_STEPS.forEach(function (step, i) {
            var cls = i < current ? 'is-done' : (i === current ? 'is-current' : '');
            html += '<div class="fd-step ' + cls + '">'
                + '<span class="fd-step-dot">' + (i < current ? '✓' : (i + 1)) + '</span>'
                + '<span class="fd-step-label">' + esc(step.label) + '</span>'
                + '</div>';
            if (i < FD_SOLIC_TRACK_STEPS.length - 1) {
                html += '<span class="fd-step-line ' + (i < current ? 'is-done' : '') + '"></span>';
            }
        });
        return html;
    }

    function fdSolicTrackHeader(order, stage) {
        var titles = {
            waiting: 'Contactando al prestador…',
            accepted: 'Tu urgencia fue aceptada',
            on_the_way: 'El prestador va en camino',
            in_progress: 'El trabajo está en curso',
            done: 'Urgencia resuelta',
            cancelled: 'Solicitud cancelada'
        };
        var pills = {
            waiting: ['En espera', '#fffbeb', '#b45309', '#fde68a'],
            accepted: ['Aceptada', '#ecfdf5', '#047857', '#a7f3d0'],
            on_the_way: ['En camino', '#eff6ff', '#1d4ed8', '#bfdbfe'],
            in_progress: ['En curso', '#eff6ff', '#1d4ed8', '#bfdbfe'],
            done: ['Completada', '#ecfdf5', '#047857', '#a7f3d0'],
            cancelled: ['Cancelada', '#fef2f2', '#b91c1c', '#fecaca']
        };
        var pill = pills[stage] || pills.waiting;

        return '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:16px;">'
            + '<div style="min-width:0;">'
            + '<p style="font-size:10px;font-weight:700;color:#94a3b8;margin:0;text-transform:uppercase;letter-spacing:.5px;">Urgencia #' + esc(order.id) + '</p>'
            + '<p style="font-size:17px;font-weight:700;color:#0c2a4d;margin:3px 0 0;">' + esc(titles[stage] || titles.waiting) + '</p>'
            + (stage === 'waiting'
                ? '<p style="font-size:12.5px;color:#6b7280;margin:4px 0 0;">Estamos avisando a los profesionales disponibles… Tiempo <span id="fd-solic-track-timer">' + fdSolicTrackElapsed() + '</span></p>'
                : '')
            + '</div>'
            + '<span style="display:inline-flex;align-items:center;gap:6px;border:1px solid ' + pill[3] + ';background:' + pill[1] + ';color:' + pill[2] + ';border-radius:9999px;padding:4px 12px;font-size:11.5px;font-weight:700;white-space:nowrap;">'
            + esc(pill[0])
            + '</span>'
            + '</div>';
    }

    function fdSolicTrackWaitingBody() {
        return '<div style="background:#f8fafc;border:1px solid #e2e8f0;padding:16px;">'
            + '<div class="fd-shimmer" style="margin-bottom:14px;"></div>'
            + '<div class="fd-shimmer" style="width:72%;margin-bottom:14px;"></div>'
            + '<div class="fd-shimmer" style="width:48%;"></div>'
            + '<p style="font-size:12px;color:#64748b;margin:14px 0 0;">Si no te acepta en unos minutos, seguimos avisando a otros prestadores cercanos.</p>'
            + '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">'
            + '<button type="button" onclick="return fdSolicTrackCancel()" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;border-radius:6px;padding:10px 16px;font-size:12.5px;font-weight:700;cursor:pointer;">Cancelar solicitud</button>'
            + '<button type="button" onclick="return fdSolicTrackGoToServices()" style="border:1px solid #e5e7eb;background:#fff;color:#6b7280;border-radius:6px;padding:10px 16px;font-size:12.5px;font-weight:700;cursor:pointer;">Ver en Mis Servicios</button>'
            + '</div>'
            + '</div>';
    }

    function fdSolicTrackProviderCard(order) {
        var merchant = order.merchant;
        if (!merchant) return '';
        var stage = fdSolicTrackStage;
        var photo = merchant.photo_url
            ? '<img src="' + esc(merchant.photo_url) + '" alt="' + esc(merchant.business_name) + '" style="width:100%;height:100%;object-fit:cover;">'
            : esc((merchant.business_name || '?').charAt(0).toUpperCase());
        var phoneDigits = String(merchant.phone || '').replace(/\D/g, '');
        var waDigits = String(merchant.whatsapp || merchant.phone || '').replace(/\D/g, '');
        var callBtn = phoneDigits
            ? '<a class="fd-contact" href="tel:' + esc(phoneDigits) + '" style="background:#0c2a4d;color:#fff;">📞 Llamar</a>'
            : '';
        var chatBtn = waDigits
            ? '<a class="fd-contact" href="https://wa.me/' + esc(waDigits) + '" target="_blank" rel="noopener" style="background:#25D366;color:#fff;">💬 WhatsApp</a>'
            : '';
        var headline = {
            accepted: 'Aceptó tu urgencia',
            on_the_way: 'Va en camino hacia tu domicilio',
            in_progress: 'Está trabajando en el lugar',
            done: 'Finalizó el trabajo'
        }[stage] || 'Aceptó tu urgencia';
        var people = [merchant.contact_name, merchant.zone].filter(Boolean).join(' · ');

        return '<div style="margin-top:14px;border:1px solid #e5e7eb;background:#fff;padding:14px;display:flex;gap:14px;flex-wrap:wrap;align-items:center;">'
            + '<span class="fd-avatar">' + photo + '</span>'
            + '<div style="min-width:0;flex:1 1 190px;">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
            + '<p style="font-size:15px;font-weight:700;color:#0f172a;margin:0;">' + esc(merchant.business_name) + '</p>'
            + (merchant.is_verified
                ? '<span style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:9999px;padding:1px 8px;font-size:10px;font-weight:700;">Verificado</span>'
                : '')
            + '</div>'
            + '<p style="font-size:12.5px;color:#047857;font-weight:700;margin:4px 0 0;">' + esc(headline) + '</p>'
            + (people ? '<p style="font-size:12px;color:#6b7280;margin:3px 0 0;">' + esc(people) + '</p>' : '')
            + '</div>'
            + '<div style="display:flex;gap:8px;flex-wrap:wrap;">' + callBtn + chatBtn + '</div>'
            + '</div>';
    }

    function fdSolicTrackSummary(order) {
        var ctx = fdSolicTrackContext || {};
        var visitFee = parseFloat(order.visit_fee || 0);
        var fee = visitFee > 0
            ? fdSolicMoney(visitFee)
            : (ctx.base_fee ? fdSolicMoney(parseFloat(ctx.base_fee)) : 'A convenir');
        var payment = FD_SOLIC_TRACK_PAYMENTS[order.payment_method] || FD_SOLIC_TRACK_PAYMENTS[ctx.payment] || '—';
        var rows = [
            ['Tarifa base', fee],
            ['Medio de pago', payment],
            ['Dirección', order.address || ctx.address || 'No informada'],
            ['Problema', order.description || ctx.description || '—']
        ];
        var html = '<div style="margin-top:14px;border:1px solid #eef2f7;background:#f8fafc;padding:12px 14px;">'
            + '<p style="font-size:10px;font-weight:700;color:#94a3b8;margin:0 0 8px;text-transform:uppercase;letter-spacing:.5px;">Detalle del pedido</p>'
            + '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;">';
        rows.forEach(function (row) {
            html += '<div style="min-width:0;">'
                + '<p style="font-size:10px;color:#6b7280;margin:0;font-weight:600;">' + esc(row[0]) + '</p>'
                + '<p style="font-size:12.5px;color:#0f172a;margin:2px 0 0;font-weight:600;">' + esc(row[1]) + '</p>'
                + '</div>';
        });
        return html + '</div></div>';
    }

    function fdSolicTrackActions(stage) {
        var buttons = '';
        if (stage === 'done' || stage === 'cancelled') {
            buttons += '<button type="button" onclick="fdSolicBack()" style="border:1px solid #D24C19;background:#D24C19;color:#fff;border-radius:6px;padding:10px 16px;font-size:12.5px;font-weight:700;cursor:pointer;">Nueva solicitud</button>';
        }
        buttons += '<button type="button" onclick="return fdSolicTrackGoToServices()" style="border:1px solid #e5e7eb;background:#fff;color:#0c2a4d;border-radius:6px;padding:10px 16px;font-size:12.5px;font-weight:700;cursor:pointer;">Ver en Mis Servicios</button>';
        return '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">' + buttons + '</div>';
    }

    // Sale del seguimiento sin cancelar la urgencia: el pedido sigue activo en
    // el servidor y el cliente lo retoma desde Mis Servicios. Corta el polling.
    window.fdSolicTrackGoToServices = function () {
        fdSolicTrackStop();
        if (typeof showDashSection === 'function') showDashSection('mis-servicios');
        return false;
    };

    window.fdSolicTrackCancel = function () {
        if (!fdSolicTrackOrderId || fdSolicTrackCancelling) return false;
        if (!window.confirm('¿Cancelar la solicitud de urgencia?')) return false;
        fdSolicTrackCancelling = true;
        fdSolicPost(FD_SOLIC_URLS.tracking + '/' + fdSolicTrackOrderId + '/cancel', { method: 'POST' })
            .then(function (res) {
                fdSolicTrackCancelling = false;
                if (!res.ok || !res.data || res.data.success === false) {
                    msg('error', firstError(res.data, 'No se pudo cancelar la solicitud.'));
                    return;
                }
                var order = res.data.data && res.data.data.order;
                if (order) {
                    fdSolicTrackRender(order);
                } else {
                    fdSolicTrackPoll();
                }
            })
            .catch(function () {
                fdSolicTrackCancelling = false;
                msg('error', 'No se pudo cancelar. Revisá tu conexión.');
            });
        return false;
    };

    // ------------------------------------ confirmacion de la cita programada

    // Formatea una fecha ISO de la orden como "martes 21 de octubre, 09:30".
    function fdSolicSchedConfirmWhen(order) {
        if (!order || !order.scheduled_at) return { date: 'A confirmar', time: '' };
        var d = new Date(order.scheduled_at);
        if (isNaN(d.getTime())) return { date: 'A confirmar', time: '' };
        var date = d.toLocaleDateString('es-AR', { weekday: 'long', day: 'numeric', month: 'long' });
        var time = d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
        return { date: date.charAt(0).toUpperCase() + date.slice(1), time: time };
    }

    // Arma el link de Google Calendar (evento de 1 hora) con el turno elegido.
    function fdSolicSchedGcalUrl(order) {
        if (!order || !order.scheduled_at) return '';
        var start = new Date(order.scheduled_at);
        if (isNaN(start.getTime())) return '';
        var end = new Date(start.getTime() + 60 * 60 * 1000);

        function stamp(d) {
            return d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
        }

        var title = 'Cita de servicio: ' + (order.sub_group && order.sub_group.description ? order.sub_group.description : 'Servicio');
        var location = order.address || '';
        var details = 'Turno reservado con ' + (order.merchant && order.merchant.contact_name ? order.merchant.contact_name : 'el profesional')
            + ' a través de PideAca. Estado: pendiente de confirmación por el prestador.';

        return 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            + '&text=' + encodeURIComponent(title)
            + '&dates=' + stamp(start) + '/' + stamp(end)
            + '&location=' + encodeURIComponent(location)
            + '&details=' + encodeURIComponent(details);
    }

    function fdSolicSchedConfirmRender(order) {
        var body = document.getElementById('fd-solic-scheduled-confirm-body');
        if (!order || !body) return;

        var when = fdSolicSchedConfirmWhen(order);
        var address = order.address || 'A convenir con el prestador';
        var provider = order.merchant && order.merchant.contact_name ? order.merchant.contact_name : 'el profesional';
        var gcal = fdSolicSchedGcalUrl(order);

        var html = ''
            + '<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">'
            + '<span class="fd-conf-ico"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>'
            + '<div><p style="font-size:16px;font-weight:800;color:#0f172a;margin:0;">Reserva confirmada</p>'
            + '<p style="font-size:12.5px;color:#6b7280;margin:2px 0 0;">Tu turno quedó registrado. Te avisamos cuando ' + esc(provider) + ' lo confirme.</p></div>'
            + '</div>'

            + '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:6px 14px;margin-bottom:14px;">'
            + '<div class="fd-conf-row"><div style="min-width:0;"><p class="fd-conf-lbl">Fecha</p><p class="fd-conf-val">' + esc(when.date) + '</p></div></div>'
            + '<div class="fd-conf-row"><div style="min-width:0;"><p class="fd-conf-lbl">Hora</p><p class="fd-conf-val">' + esc(when.time || 'A confirmar') + '</p></div></div>'
            + '<div class="fd-conf-row"><div style="min-width:0;"><p class="fd-conf-lbl">Dirección</p><p class="fd-conf-val">' + esc(address) + '</p></div></div>'
            + '</div>';

        if (gcal) {
            html += '<a class="fd-conf-gcal" href="' + esc(gcal) + '" target="_blank" rel="noopener noreferrer">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="M12 14v4"/><path d="M10 16h4"/></svg>'
                + 'Añadir a Google Calendar</a>';
        }

        html += '<div style="display:flex;align-items:flex-start;gap:9px;margin-top:14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;">'
            + '<span style="font-size:15px;flex:none;">⏳</span>'
            + '<div><p style="font-size:12.5px;font-weight:700;color:#9a3412;margin:0;">Pendiente de confirmación por el prestador</p>'
            + '<p style="font-size:12px;color:#9a3412;margin:4px 0 0;">Te notificamos por WhatsApp apenas ' + esc(provider) + ' confirme el turno. Si necesitás reprogramar, podés hacerlo hasta 24 hs antes sin costo.</p></div>'
            + '</div>'

            + '<div style="margin-top:16px;"><button type="button" class="fd-conf-cta" onclick="return fdSolicSchedConfirmServices()">Ver en Mis Servicios</button></div>'
            + '<div style="text-align:center;margin-top:6px;"><button type="button" class="fd-conf-link" onclick="return fdSolicSchedConfirmAgain()">Agendar otra cita</button></div>';

        body.innerHTML = html;
    }

    // Muestra la confirmacion y oculta el catalogo/formulario de la cita.
    window.fdSolicSchedConfirmStart = function (order) {
        if (!order) return;
        fdSolicSchedConfirmOrder = order;
        stopStatusTimer();
        fdSolicSelected = null;
        fdSolicService = null;

        var pick = document.getElementById('fd-solic-pick');
        if (pick) pick.style.display = 'none';
        var chosen = document.getElementById('fd-solic-chosen');
        if (chosen) chosen.style.display = 'none';
        var search = document.getElementById('fd-solic-search');
        if (search) search.style.display = 'none';
        var confirmEl = document.getElementById('fd-solic-scheduled-confirm');
        if (confirmEl) confirmEl.style.display = 'block';
        showForms();

        fdSolicSchedConfirmRender(order);
    };

    window.fdSolicSchedConfirmServices = function () {
        fdSolicSchedConfirmOrder = null;
        var confirmEl = document.getElementById('fd-solic-scheduled-confirm');
        if (confirmEl) confirmEl.style.display = 'none';
        if (typeof showDashSection === 'function') showDashSection('mis-servicios');
        return false;
    };

    window.fdSolicSchedConfirmAgain = function () {
        fdSolicSchedConfirmOrder = null;
        var confirmEl = document.getElementById('fd-solic-scheduled-confirm');
        if (confirmEl) confirmEl.style.display = 'none';
        if (typeof fdSolicBack === 'function') fdSolicBack();
        return false;
    };

    function fdSolicTrackRender(order) {
        var body = document.getElementById('fd-solic-track-body');
        if (!order || !body) return;

        var stage = fdSolicTrackStageOf(order.status);
        fdSolicTrackStage = stage;

        var html = fdSolicTrackHeader(order, stage)
            + '<div style="display:flex;align-items:center;margin-bottom:16px;">' + fdSolicTrackStepsHtml(stage) + '</div>';

        if (stage === 'waiting') {
            html += fdSolicTrackWaitingBody();
        } else {
            html += fdSolicTrackProviderCard(order) + fdSolicTrackSummary(order);
            if (order.cancellable) {
                html += '<div style="margin-top:14px;"><button type="button" onclick="return fdSolicTrackCancel()" style="border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;border-radius:6px;padding:10px 16px;font-size:12.5px;font-weight:700;cursor:pointer;">Cancelar solicitud</button></div>';
            }
            if (stage === 'cancelled') {
                html += '<p style="font-size:12.5px;color:#b91c1c;font-weight:600;margin:12px 0 0;">' + esc(order.status_label || 'Solicitud cancelada') + '</p>';
            }
            html += fdSolicTrackActions(stage);
        }

        if (html !== fdSolicTrackLastHtml) {
            fdSolicTrackLastHtml = html;
            body.innerHTML = html;
        }
        fdSolicTrackSchedule();
    }

    function fdSolicTrackPoll() {
        if (!fdSolicTrackOrderId) return;
        fetch(FD_SOLIC_URLS.tracking + '/' + fdSolicTrackOrderId, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.success && data.data && data.data.order) {
                    fdSolicTrackRender(data.data.order);
                }
            })
            .catch(function () { /* sin red: reintenta en el proximo ciclo */ });
    }

    function fdSolicTrackSchedule() {
        if (fdSolicTrackPollTimer) {
            clearTimeout(fdSolicTrackPollTimer);
            fdSolicTrackPollTimer = null;
        }
        if (!fdSolicTrackOrderId) return;
        if (fdSolicTrackStage === 'done' || fdSolicTrackStage === 'cancelled') return;
        var interval = fdSolicTrackStage === 'waiting' ? 4000 : 10000;
        fdSolicTrackPollTimer = setTimeout(fdSolicTrackPoll, interval);
    }

    function fdSolicTrackStop() {
        if (fdSolicTrackPollTimer) {
            clearTimeout(fdSolicTrackPollTimer);
            fdSolicTrackPollTimer = null;
        }
        if (fdSolicTrackTickTimer) {
            clearInterval(fdSolicTrackTickTimer);
            fdSolicTrackTickTimer = null;
        }
        fdSolicTrackOrderId = null;
        fdSolicTrackStartedAt = null;
        fdSolicTrackStage = null;
        fdSolicTrackLastHtml = '';
    }

    // Arranca el seguimiento apenas la urgencia se crea: oculta el catalogo y
    // el formulario, y pinta la pantalla de estado con la orden recien creada.
    window.fdSolicTrackStart = function (order, context) {
        if (!order) return;

        fdSolicTrackOrderId = order.id;
        fdSolicTrackStartedAt = Date.now();
        fdSolicTrackContext = context || {};
        fdSolicTrackLastHtml = '';
        fdSolicTrackStage = null;

        stopStatusTimer();
        fdSolicSelected = null;
        fdSolicService = null;

        var pick = document.getElementById('fd-solic-pick');
        if (pick) pick.style.display = 'none';
        var chosen = document.getElementById('fd-solic-chosen');
        if (chosen) chosen.style.display = 'none';
        var search = document.getElementById('fd-solic-search');
        if (search) search.style.display = 'none';
        var track = document.getElementById('fd-solic-track');
        if (track) track.style.display = 'block';
        showForms();

        fdSolicTrackRender(order);

        if (fdSolicTrackTickTimer) clearInterval(fdSolicTrackTickTimer);
        fdSolicTrackTickTimer = setInterval(function () {
            var timer = document.getElementById('fd-solic-track-timer');
            if (timer) timer.textContent = fdSolicTrackElapsed();
        }, 1000);
    };

    // El temporizador solo late mientras hay una urgencia en seguimiento y el
    // cliente mira la pestania (si se oculta, se suspende hasta volver).
    document.addEventListener('visibilitychange', function () {
        if (!fdSolicTrackOrderId) return;
        if (document.hidden) {
            if (fdSolicTrackPollTimer) {
                clearTimeout(fdSolicTrackPollTimer);
                fdSolicTrackPollTimer = null;
            }
        } else {
            fdSolicTrackPoll();
            fdSolicTrackSchedule();
        }
    });

    // ------------------------------------- chips de problema / media / geo

    window.fdSolicPickChip = function (btn) {
        var container = document.getElementById('fd-solic-emergency-chips');
        var chips = container ? container.querySelectorAll('.fd-chip') : [];
        var already = btn.classList.contains('is-active');
        for (var i = 0; i < chips.length; i++) chips[i].classList.remove('is-active');

        var textarea = document.getElementById('fd-solic-emergency-desc');

        if (already) {
            if (textarea) textarea.value = '';
            return;
        }

        btn.classList.add('is-active');

        // "Otro" deja el campo libre para escribir a mano.
        if (btn.getAttribute('data-free') === '1') {
            if (textarea) { textarea.value = ''; textarea.focus(); }
            return;
        }

        if (textarea) {
            textarea.value = btn.getAttribute('data-problem') || '';
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = textarea.value.length;
        }
    };

    window.fdSolicClearChips = function () {
        var container = document.getElementById('fd-solic-emergency-chips');
        if (!container) return;
        var chips = container.querySelectorAll('.fd-chip.is-active');
        for (var i = 0; i < chips.length; i++) chips[i].classList.remove('is-active');
    };

    window.fdSolicPickFile = function (inputId) {
        var input = document.getElementById(inputId);
        if (input) input.click();
        return false;
    };

    window.fdSolicOnMediaChange = function (kind) {
        var input = document.getElementById('fd-solic-emergency-' + kind);
        var label = document.getElementById('fd-solic-emergency-' + kind + '-label');
        var info = document.getElementById('fd-solic-emergency-' + kind + '-info');
        var btn = document.getElementById('fd-solic-emergency-' + kind + '-btn');
        var placeholder = kind === 'photo' ? '📷 Tomar / Subir Foto' : '🎙️ Grabar Audio';
        if (!input || !info) return;

        var file = input.files && input.files[0];
        var max = kind === 'photo' ? 5 * 1024 * 1024 : 8 * 1024 * 1024;

        if (file && file.size > max) {
            msg('error', (kind === 'photo' ? 'La foto' : 'El audio') + ' supera el máximo de ' + (kind === 'photo' ? '5' : '8') + ' MB. Elegí otro archivo.');
            input.value = '';
            file = null;
        }

        if (!file) {
            if (fdSolicMediaObjectUrl) {
                URL.revokeObjectURL(fdSolicMediaObjectUrl);
                fdSolicMediaObjectUrl = null;
            }
            if (btn) btn.classList.remove('has-file');
            if (label) label.textContent = placeholder;
            info.style.display = 'none';
            info.innerHTML = '';
            return;
        }

        if (fdSolicMediaObjectUrl) {
            URL.revokeObjectURL(fdSolicMediaObjectUrl);
            fdSolicMediaObjectUrl = null;
        }

        if (btn) btn.classList.add('has-file');
        if (label) label.textContent = kind === 'photo' ? '📷 Cambiar foto' : '🎙️ Cambiar audio';

        var html = '✓ ' + esc(file.name);

        if (kind === 'photo') {
            fdSolicMediaObjectUrl = URL.createObjectURL(file);
            html = '<img src="' + fdSolicMediaObjectUrl + '" alt="Foto del problema" style="width:44px;height:44px;object-fit:cover;border:1px solid #e5e7eb;border-radius:4px;flex:none;">' + html;
        } else {
            html += ' — ' + fdSolicFormatSize(file.size);
        }

        info.innerHTML = html;
        info.style.display = 'flex';
    };

    function fdSolicFormatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function fdSolicGeoStatus(text, kind) {
        var el = document.getElementById('fd-solic-emergency-geo-status');
        if (!el) return;
        if (!text) { el.style.display = 'none'; el.textContent = ''; return; }
        el.style.display = 'block';
        el.textContent = text;
        el.style.color = kind === 'error' ? '#b91c1c' : (kind === 'ok' ? '#047857' : '#6b7280');
        el.style.fontWeight = kind === 'ok' || kind === 'error' ? '700' : '500';
    }

    // Coordenadas -> direccion legible via Nominatim (OpenStreetMap).
    function fdSolicReverseGeocode(lat, lon, onOk, onErr) {
        var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2'
            + '&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lon)
            + '&accept-language=es';

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (data) {
                var addr = (data && data.address) || {};
                var parts = [
                    addr.road || addr.pedestrian || addr.residential || '',
                    addr.house_number || '',
                    addr.suburb || addr.neighbourhood || addr.city_district || '',
                    addr.city || addr.town || addr.village || addr.municipality || ''
                ].filter(Boolean);

                var text = parts.join(' ') || (data && data.display_name) || '';
                if (text) onOk(text);
                else onErr();
            })
            .catch(function () { onErr(); });
    }

    function fdSolicGeoError(err) {
        if (!err) return 'No pudimos obtener tu ubicación. Escribí la dirección a mano.';
        if (err.code === 1) return 'Permiso de ubicación denegado. Activalo en tu navegador o escribí la dirección a mano.';
        if (err.code === 3) return 'La solicitud de ubicación tardó demasiado. Escribí la dirección a mano.';
        return 'No se pudo determinar tu ubicación. Escribí la dirección a mano.';
    }

    window.fdSolicUseLocation = function () {
        var btn = document.getElementById('fd-solic-emergency-geo');

        if (!navigator.geolocation) {
            fdSolicGeoStatus('Tu navegador no soporta geolocalización: escribí la dirección a mano.', 'error');
            return false;
        }

        if (btn) btn.disabled = true;
        fdSolicGeoStatus('Buscando tu ubicación...', '');

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                fdSolicEmergencyCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                fdSolicGeoStatus('Ubicación capturada. Resolviendo la dirección...', '');

                fdSolicReverseGeocode(fdSolicEmergencyCoords.lat, fdSolicEmergencyCoords.lng,
                    function (text) {
                        var input = document.getElementById('fd-solic-emergency-address');
                        if (input) input.value = text;
                        fdSolicGeoStatus('Dirección lista. Revisá que sea correcta.', 'ok');
                        if (btn) btn.disabled = false;
                    },
                    function () {
                        fdSolicGeoStatus('Ubicación capturada, pero no pudimos resolver la dirección. Escribila a mano.', 'error');
                        if (btn) btn.disabled = false;
                    });
            },
            function (err) {
                if (btn) btn.disabled = false;
                fdSolicGeoStatus(fdSolicGeoError(err), 'error');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );

        return false;
    };

    // Precarga la direccion principal del perfil si todavia no escribio nada.
    function fdSolicPrefillAddress() {
        if (fdSolicAddressPrefilled) return;
        fdSolicAddressPrefilled = true;

        var input = document.getElementById('fd-solic-emergency-address');
        if (!input || input.value.trim()) return;

        fetch(FD_SOLIC_URLS.myAddresses, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                var list = (data && data.addresses) || [];
                var primary = list[0] || null;

                for (var i = 0; i < list.length; i++) {
                    if (list[i].is_primary) { primary = list[i]; break; }
                }

                if (primary && primary.formatted && !input.value.trim()) input.value = primary.formatted;
            })
            .catch(function () {});
    }

    // Al menos un chip de categoria o una descripcion escrita: sin eso la
    // urgencia no sale (el chip siempre escribe en la descripcion, asi que el
    // backend recibe texto en ambos casos).
    function fdSolicEmergencyHasContent() {
        var textarea = document.getElementById('fd-solic-emergency-desc');
        var container = document.getElementById('fd-solic-emergency-chips');

        if (textarea && textarea.value.trim()) return true;

        return Boolean(container && container.querySelector('.fd-chip.is-active'));
    }

    function fdSolicPaymentValue() {
        var checked = document.querySelector('input[name="fd-solic-payment"]:checked');
        return checked ? checked.value : '';
    }

    window.fdSolicSubmitEmergency = function (event) {
        event.preventDefault();
        if (!fdSolicSelected) return false;

        if (!fdSolicEmergencyHasContent()) {
            var textarea = document.getElementById('fd-solic-emergency-desc');
            if (textarea) textarea.focus();
            msg('error', 'Contanos qué pasa: tocá una opción rápida o escribí una descripción del problema.');
            return false;
        }

        var photo = document.getElementById('fd-solic-emergency-photo').files[0];
        if (!photo) {
            msg('error', 'Adjuntá una foto del problema.');
            return false;
        }

        // La urgencia no envia ubicacion por defecto: las coordenadas solo se
        // mandan si el cliente toco "Usar mi ubicacion actual".
        var form = new FormData();
        form.append('provider_id', fdSolicSelected.id);
        form.append('photo', photo);
        form.append('description', document.getElementById('fd-solic-emergency-desc').value || '');

        var paymentMethod = fdSolicPaymentValue();
        if (paymentMethod) form.append('payment_method', paymentMethod);

        var address = (document.getElementById('fd-solic-emergency-address').value || '').trim();
        if (address) form.append('address', address);

        if (fdSolicEmergencyCoords) {
            form.append('latitude', fdSolicEmergencyCoords.lat);
            form.append('longitude', fdSolicEmergencyCoords.lng);
        }

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
                var order = res.data.data && res.data.data.order;
                if (order) {
                    // La urgencia ya existe: cambia la ventana al seguimiento en
                    // vivo (contacto -> aceptado -> en camino).
                    fdSolicTrackStart(order, {
                        base_fee: fdSolicSelected ? fdSolicSelected.base_visit_fee : null,
                        payment: paymentMethod,
                        address: address,
                        description: document.getElementById('fd-solic-emergency-desc').value || ''
                    });
                } else {
                    msg('ok', res.data.message + '\nSeguí el estado en "Mis Servicios".');
                }
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

        var date = fdSolicSchedDate || document.getElementById('fd-solic-date').value;
        var time = fdSolicSchedTime || document.getElementById('fd-solic-time').value;

        if (!date) {
            msg('error', 'Elegí un día del calendario para tu cita.');
            return false;
        }

        if (!time) {
            msg('error', 'Elegí uno de los horarios libres del día seleccionado.');
            return false;
        }

        var payload = {
            provider_id: fdSolicSelected.id,
            scheduled_at: date + ' ' + time + ':00',
            fulfillment: document.getElementById('fd-solic-fulfillment').value,
            payment_method: document.getElementById('fd-solic-payment-method').value,
            payment_mode: document.getElementById('fd-solic-payment-mode').value,
            notes: document.getElementById('fd-solic-notes').value || '',
            address_street: document.getElementById('fd-solic-address-street').value.trim(),
            address_unit: document.getElementById('fd-solic-address-unit').value.trim(),
            address_neighborhood: document.getElementById('fd-solic-address-neighborhood').value.trim(),
            reminder_channel: document.getElementById('fd-solic-reminder').value || 'whatsapp'
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
                msgHide();
                var order = res.data.data && res.data.data.order;
                if (order) {
                    // Pantalla de confirmacion con el resumen del turno.
                    fdSolicSchedConfirmStart(order);
                } else {
                    msg('ok', res.data.message + '\nSeguí el estado en "Mis Servicios".');
                }
                // Limpia la eleccion y recarga el calendario para otra cita.
                fdSolicSchedDate = null;
                fdSolicSchedTime = null;
                fdSolicScheduledReset(fdSolicSelected);
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

        // La primera carga decide el tab por defecto (favoritos si hay, si no todos).
        loadProviders();
    });
})();
</script>
