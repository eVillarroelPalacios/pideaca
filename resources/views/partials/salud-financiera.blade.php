{{-- Salud financiera del comercio: productos e insumos; la ficha tecnica y el alta/edicion de insumos abren en modal. --}}
<style>
    /* --- Salud financiera: sidebar de secciones (productos, insumos) --- */
    .fin-layout { display: flex; align-items: stretch; background: #ffffff; border: 1px solid #e5e7eb; }
    #fin-sidebar { width: 168px; min-width: 168px; background: #f8fafc; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; padding-bottom: 8px; }
    .fin-menu-title { font-size: 10px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: #94a3b8; padding: 16px 14px 8px; }
    .fin-body { flex: 1; min-width: 0; padding: 20px 22px; }
    .fin-panel { display: none; }
    .fin-panel.active { display: block; animation: tabFade .18s ease; }
    .fin-panel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
    .fin-panel-head h3 { font-size: 15px; font-weight: 700; color: #0c2a4d; margin: 0; }
    .fin-panel-head p { font-size: 12px; color: #6b7280; margin: 3px 0 0; }
    #fin-sidebar .sidebar-link { font-size: 12px; gap: 8px; padding: 9px 10px; align-items: center; }
    .fin-topbar { display: none; }
    #fin-menu-toggle { display: none; }
    #fin-overlay { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 240; }
    #fin-overlay.open { display: block; }
    #fin-nav { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }

    /* --- Modal de ficha tecnica --- */
    #fin-recipe-modal { display: none; position: fixed; inset: 0; z-index: 300; }
    #fin-recipe-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .55); }
    #fin-recipe-dialog {
        position: relative;
        margin: 6vh auto;
        width: min(760px, 94vw);
        max-height: 88vh;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .35);
    }
    .fin-recipe-row { display: grid; grid-template-columns: 2fr 1fr auto; gap: 8px; margin-bottom: 8px; }

    /* --- Modal de alta/edicion de insumo --- */
    #fin-supply-modal { display: none; position: fixed; inset: 0; z-index: 300; }
    #fin-supply-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .55); }
    #fin-supply-dialog {
        position: relative;
        margin: 12vh auto;
        width: min(640px, 94vw);
        max-height: 80vh;
        overflow-y: auto;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .35);
    }
    @media (max-width: 860px) {
        .fin-layout { flex-direction: column; }
        /* Barra superior con la seccion actual, como Mi Panel */
        .fin-topbar {
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
        #fin-menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #0f172a;
        }
        #fin-menu-toggle:hover { color: #D24C19; }
        /* Sidebar fijo que entra desde la izquierda, como Mi Panel */
        #fin-sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 210px;
            min-width: 210px;
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
        #fin-sidebar.open { transform: translateX(0); }
        .fin-body { padding: 16px 12px; }
        #fin-recipe-modal { z-index: 320; }
        #fin-recipe-dialog { margin: 0; width: 100vw; max-height: 100vh; min-height: 100vh; border: none; border-radius: 0; }
        .fin-recipe-row { grid-template-columns: 1fr; }
        #fin-supply-modal { z-index: 320; }
        #fin-supply-dialog { margin: 0; width: 100vw; max-height: 100vh; min-height: 100vh; border: none; border-radius: 0; }
    }
    @media (min-width: 861px) {
        #fin-overlay { display: none !important; }
    }
</style>
<section id="dash-{{ $page->url }}" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div>
            <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
            <p id="fin-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">Costo de producción y margen de cada producto</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <button type="button" onclick="loadFinances()" title="Actualizar salud financiera" aria-label="Actualizar salud financiera" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:8px 10px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg></button>
        </div>
    </div>

    <div id="fin-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
        <p style="font-size:13px;color:#6b7280;margin:0;">Calculando costos...</p>
    </div>

    <div id="fin-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
        <p id="fin-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudo cargar la salud financiera.</p>
    </div>

    <div id="fin-content" style="display:none;">
        <div class="fin-layout">
            <div class="fin-topbar">
                <button type="button" id="fin-menu-toggle" onclick="finToggleMenu()" aria-controls="fin-sidebar" aria-expanded="false" aria-label="Abrir secciones" title="Secciones">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>
                <span style="font-size:12px;color:#64748b;">Salud financiera</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                <span id="fin-menu-current" style="font-size:12px;color:#0f172a;font-weight:600;">Productos</span>
            </div>

            <div id="fin-overlay" onclick="finToggleMenu()" title="Cerrar" aria-hidden="true"></div>

            <aside id="fin-sidebar" aria-label="Secciones de salud financiera">
                <div id="fin-nav">
                <div class="fin-menu-title">Secciones</div>

                <a href="#" class="sidebar-link active" id="fin-link-productos" data-fin-panel="productos" title="Productos" onclick="event.preventDefault();finTab('productos')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                    <span style="flex:1;min-width:0;">Productos</span>
                </a>

                <a href="#" class="sidebar-link" id="fin-link-insumos" data-fin-panel="insumos" title="Insumos" onclick="event.preventDefault();finTab('insumos')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M6 20.25h12m-7.5-3v3m3-3v3m-10.5-3h12A2.25 2.25 0 0 0 19.5 18V7.5a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25Zm13.5-11.25V6a2.25 2.25 0 0 0-2.25-2.25H7.5A2.25 2.25 0 0 0 5.25 6v2.625m13.5-2.625h-1.5m-11.25 0H3.75"/></svg>
                    <span style="flex:1;min-width:0;">Insumos</span>
                </a>
                </div>
            </aside>

            <div class="fin-body">
                <div class="fin-panel active" id="fin-panel-productos">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;" id="fin-badges"></div>

                    <div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:13px;">
                            <thead>
                                <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                    <th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Producto</th>
                                    <th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Precio</th>
                                    <th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Costo</th>
                                    <th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Margen</th>
                                    <th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Estado</th>
                                    <th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Ficha</th>
                                </tr>
                            </thead>
                            <tbody id="fin-rows"></tbody>
                        </table>
                    </div>

                    <div id="fin-pager" style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;font-size:12px;color:#6b7280;">
                        <span id="fin-pager-label"></span>
                        <span style="display:flex;gap:6px;">
                            <button type="button" onclick="finGoPage(-1)" title="Página anterior" aria-label="Página anterior" style="background:#fff;border:1px solid #d1d5db;padding:5px 7px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>
                            <button type="button" onclick="finGoPage(1)" title="Página siguiente" aria-label="Página siguiente" style="background:#fff;border:1px solid #d1d5db;padding:5px 7px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>
                        </span>
                    </div>
                </div>

                <div class="fin-panel" id="fin-panel-insumos">
                    <div class="fin-panel-head">
                        <div>
                            <h3>Insumos</h3>
                            <p>Lo que necesita tu comercio para producir. El costo de cada insumo se usa para calcular el margen.</p>
                        </div>
                        <button type="button" onclick="openSupplyForm()" title="Agregar insumo" aria-label="Agregar insumo" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:7px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg></button>
                    </div>
                    <div id="fin-supplies"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="fin-recipe-modal" role="dialog" aria-modal="true" aria-labelledby="fin-recipe-title">
        <div id="fin-recipe-backdrop" onclick="closeRecipe()" title="Cerrar" aria-hidden="true"></div>
        <div id="fin-recipe-dialog" tabindex="-1" style="outline:none;">
            <div id="fin-recipe" style="padding:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
                    <h3 style="font-size:15px;font-weight:700;color:#0c2a4d;margin:0;" id="fin-recipe-title">Ficha técnica</h3>
                    <div style="display:flex;gap:6px;">
                        <button type="button" onclick="addRecipeRow()" title="Agregar insumo" aria-label="Agregar insumo" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:7px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg></button>
                        <button type="button" onclick="saveRecipe()" title="Guardar ficha" aria-label="Guardar ficha" style="background:#D24C19;color:#fff;border:none;padding:7px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></button>
                        <button type="button" onclick="closeRecipe()" title="Cerrar" aria-label="Cerrar" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:7px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
                    </div>
                </div>
                <div id="fin-recipe-items"></div>
                <div id="fin-recipe-total" style="margin-top:12px;font-size:13px;font-weight:600;color:#0c2a4d;"></div>
            </div>
        </div>
    </div>

    <div id="fin-supply-modal" role="dialog" aria-modal="true" aria-labelledby="fin-supply-form-title">
        <div id="fin-supply-backdrop" onclick="closeSupplyForm()" title="Cerrar" aria-hidden="true"></div>
        <div id="fin-supply-dialog" tabindex="-1" style="outline:none;">
            <div id="fin-supply-form" style="padding:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:14px;">
                    <h3 style="font-size:15px;font-weight:700;color:#0c2a4d;margin:0;" id="fin-supply-form-title">Nuevo insumo</h3>
                    <button type="button" onclick="closeSupplyForm()" title="Cerrar" aria-label="Cerrar" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:6px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
                </div>
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:10px;align-items:end;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Nombre</label>
                        <input type="text" id="fin-supply-name" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Unidad</label>
                        <select id="fin-supply-unit" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;"></select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:4px;">Costo por unidad</label>
                        <input type="number" step="0.01" min="0" id="fin-supply-cost" style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" />
                    </div>
                </div>
                <div style="display:flex;gap:6px;justify-content:flex-end;margin-top:16px;">
                    <button type="button" onclick="saveSupply()" title="Guardar insumo" aria-label="Guardar insumo" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></button>
                    <button type="button" onclick="closeSupplyForm()" title="Cancelar" aria-label="Cancelar" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var FIN_URL = {
        health: '{{ url("/api/v1/provider/financial-health") }}',
        supplies: '{{ url("/api/v1/provider/supplies") }}',
        recipe: '{{ url("/api/v1/provider/products") }}'
    };

    var finState = { page: 1, products: [], supplies: [], units: [], recipeProduct: null, editingSupply: null, active: 'productos', dirty: false };

    var FIN_SECTIONS = {
        productos: 'Productos',
        insumos: 'Insumos'
    };

    // Iconos de los botones (los botones no llevan texto, solo title/aria-label).
    var FIN_ICONS = {
        plus: '<path d="M12 5v14M5 12h14"/>',
        pencil: '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        trash: '<path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
        check: '<path d="M20 6 9 17l-5-5"/>',
        x: '<path d="M18 6 6 18M6 6l12 12"/>',
        filePlus: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v5h5"/><path d="M12 11v6M9 14h6"/>'
    };

    function finIcon(name, size) {
        var s = size || 15;
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' + s + '" height="' + s
            + '" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" '
            + 'stroke-linejoin="round" aria-hidden="true">' + (FIN_ICONS[name] || '') + '</svg>';
    }

    function finSetState(state, text) {
        var map = { loading: 'fin-loading', message: 'fin-message', content: 'fin-content' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fin-message-text');
            if (msg) msg.textContent = text;
        }
    }

    // Cada bloque vive en su propia seccion del cuerpo; el sidebar solo cambia
    // la visible. En responsive el sidebar es un menu hamburguesa.
    function finToggleMenu() {
        var sb = document.getElementById('fin-sidebar');
        if (!sb) return;

        var abierto = sb.classList.toggle('open');
        var ov = document.getElementById('fin-overlay');
        if (ov) ov.classList.toggle('open', abierto);
        var btn = document.getElementById('fin-menu-toggle');
        if (btn) btn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    }

    function finCloseMenu() {
        var sb = document.getElementById('fin-sidebar');
        var ov = document.getElementById('fin-overlay');
        var btn = document.getElementById('fin-menu-toggle');

        if (ov) ov.classList.remove('open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        if (sb) sb.classList.remove('open');
    }

    function finTab(key) {
        finState.active = key;

        document.querySelectorAll('#fin-sidebar .sidebar-link[data-fin-panel]').forEach(function (link) {
            link.classList.toggle('active', link.getAttribute('data-fin-panel') === key);
        });

        document.querySelectorAll('.fin-panel').forEach(function (panel) {
            panel.classList.toggle('active', panel.id === 'fin-panel-' + key);
        });

        var actual = document.getElementById('fin-menu-current');
        if (actual) actual.textContent = FIN_SECTIONS[key] || key;

        finCloseMenu();
    }

    function finNum(value) {
        if (value === null || typeof value === 'undefined') return '—';
        var n = Number(value);
        if (isNaN(n)) return escapeHtml(String(value));
        return String(Math.round(n * 100) / 100);
    }

    function finMarginBadge(margin, threshold) {
        if (margin === null || typeof margin === 'undefined') {
            return fdBadge('Sin precio', '#9a3412', '#fff7ed');
        }
        if (margin < threshold) {
            return fdBadge('Margen bajo ' + finNum(margin) + '%', '#b91c1c', '#fee2e2');
        }
        return fdBadge('Margen ' + finNum(margin) + '%', '#047857', '#d1fae5');
    }

    function finAlerts(row) {
        return (row.alerts || []).map(function (a) {
            var color = a.severity === 'warning' ? '#b45309' : '#1d4ed8';
            var bg = a.severity === 'warning' ? '#fef3c7' : '#dbeafe';
            return fdBadge(a.message, color, bg);
        }).join(' ');
    }

    function loadFinances() {
        finSetState('loading');

        fdFetchJson(FIN_URL.health + '?page=' + finState.page)
            .then(function (res) {
                if (!res.ok) {
                    finSetState('message', (res.data && res.data.message) || 'No se pudo cargar la salud financiera.');
                    return;
                }

                var data = res.data;
                finState.products = data.products || [];

                var summary = document.getElementById('fin-summary');
                if (summary) {
                    summary.textContent = 'Costo de producción y margen de cada producto · umbral recomendado '
                        + finNum(data.summary.min_margin_percent) + '%';
                }

                var badges = document.getElementById('fin-badges');
                badges.innerHTML = fdBadge(data.summary.total_products + ' productos', '#0c2a4d', '#eef2f7')
                    + ' ' + fdBadge(data.summary.products_with_alert + ' con alerta',
                        data.summary.products_with_alert ? '#b91c1c' : '#047857',
                        data.summary.products_with_alert ? '#fee2e2' : '#d1fae5');

                var rows = document.getElementById('fin-rows');
                if (!finState.products.length) {
                    rows.innerHTML = '<tr><td colspan="6" style="padding:32px;text-align:center;color:#6b7280;">'
                        + 'Todavía no tenés productos. Cargalos desde Mi Catálogo para calcular el costo y el margen.</td></tr>';
                } else {
                    var threshold = data.summary.min_margin_percent;
                    rows.innerHTML = finState.products.map(function (row) {
                        return '<tr style="border-top:1px solid #f3f4f6;">'
                            + '<td style="padding:10px 12px;">' + escapeHtml(row.name) + '</td>'
                            + '<td style="padding:10px 12px;text-align:right;">' + fdMoney(row.sale_price) + '</td>'
                            + '<td style="padding:10px 12px;text-align:right;">'
                            + (row.has_recipe ? fdMoney(row.production_cost) : '<span style="color:#9ca3af;">—</span>') + '</td>'
                            + '<td style="padding:10px 12px;text-align:right;">' + finMarginBadge(row.profit_margin, threshold) + '</td>'
                            + '<td style="padding:10px 12px;font-size:11px;">' + (finAlerts(row) || fdBadge('OK', '#047857', '#d1fae5')) + '</td>'
                            + '<td style="padding:10px 12px;text-align:right;">'
                            + '<button type="button" onclick="openRecipe(' + row.product_id + ')" title="'
                            + (row.has_recipe ? 'Editar ficha' : 'Cargar ficha') + '" aria-label="'
                            + (row.has_recipe ? 'Editar ficha' : 'Cargar ficha') + '" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:6px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">'
                            + finIcon(row.has_recipe ? 'pencil' : 'filePlus', 14) + '</button></td>'
                            + '</tr>';
                    }).join('');
                }

                var pag = data.pagination || {};
                var label = document.getElementById('fin-pager-label');
                if (label) label.textContent = 'Página ' + (pag.current_page || 1) + ' de ' + (pag.last_page || 1);
                var pager = document.getElementById('fin-pager');
                if (pager) pager.style.display = (pag.last_page > 1) ? 'flex' : 'none';

                finSetState('content');
                loadSupplies();
            })
            .catch(function () {
                finSetState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function finGoPage(step) {
        finState.page = Math.max(1, finState.page + step);
        loadFinances();
    }

    function loadSupplies() {
        return fdFetchJson(FIN_URL.supplies)
            .then(function (res) {
                if (!res.ok) return;
                finState.supplies = res.data.supplies || [];
                finState.units = res.data.units_of_measure || [];

                var box = document.getElementById('fin-supplies');
                if (!box) return;

                if (!finState.supplies.length) {
                    box.innerHTML = '<div style="background:#f9fafb;border:1px dashed #d1d5db;padding:24px;text-align:center;font-size:13px;color:#6b7280;">'
                        + 'Todavía no cargaste insumos. Usá el botón de agregar insumo (+) para empezar.</div>';
                    return;
                }

                box.innerHTML = '<div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">'
                    + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
                    + '<thead><tr style="background:#f9fafb;">'
                    + '<th style="text-align:left;padding:8px 12px;font-weight:600;color:#374151;">Insumo</th>'
                    + '<th style="text-align:left;padding:8px 12px;font-weight:600;color:#374151;">Unidad</th>'
                    + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;">Costo</th>'
                    + '<th style="text-align:right;padding:8px 12px;font-weight:600;color:#374151;"></th>'
                    + '</tr></thead><tbody>'
                    + finState.supplies.map(function (s) {
                        var enUso = Number(s.recipes_count || 0) > 0;
                        return '<tr style="border-top:1px solid #f3f4f6;">'
                            + '<td style="padding:8px 12px;">' + escapeHtml(s.name) + '</td>'
                            + '<td style="padding:8px 12px;color:#6b7280;">'
                            + escapeHtml(s.unit_of_measure ? s.unit_of_measure.name : '—') + '</td>'
                            + '<td style="padding:8px 12px;text-align:right;">' + fdMoney(s.cost_per_unit) + '</td>'
                            + '<td style="padding:8px 12px;text-align:right;white-space:nowrap;">'
                            + '<button type="button" onclick="openSupplyForm(' + s.id + ')" title="Editar" aria-label="Editar" style="background:none;border:none;color:#D24C19;cursor:pointer;padding:4px;display:inline-flex;align-items:center;line-height:0;">' + finIcon('pencil', 15) + '</button>'
                            + (enUso ? '<span title="Se usa en ' + s.recipes_count + ' ficha(s) técnica(s)" style="color:#9ca3af;font-size:11px;font-weight:600;margin:0 8px;">en uso</span>' : '')
                            + '<button type="button" onclick="deleteSupply(' + s.id + ')" title="Eliminar" aria-label="Eliminar" style="background:none;border:none;color:#b91c1c;cursor:pointer;padding:4px;display:inline-flex;align-items:center;line-height:0;">' + finIcon('trash', 15) + '</button>'
                            + '</td></tr>';
                    }).join('')
                    + '</tbody></table></div>';
            })
            .catch(function () {
                // Si la peticion falla la lista queda vacia: addRecipeRow reintenta.
            });
    }

    function openSupplyForm(id) {
        finTab('insumos');

        finState.editingSupply = id ? (finState.supplies.filter(function (s) { return s.id === id; })[0] || null) : null;
        var form = document.getElementById('fin-supply-form');
        var title = document.getElementById('fin-supply-form-title');

        if (title) title.textContent = finState.editingSupply ? 'Editar insumo' : 'Nuevo insumo';

        var unit = document.getElementById('fin-supply-unit');
        if (unit) {
            unit.innerHTML = (finState.units || []).map(function (u) {
                return '<option value="' + u.id + '">' + escapeHtml(u.name) + '</option>';
            }).join('');
        }

        var name = document.getElementById('fin-supply-name');
        var cost = document.getElementById('fin-supply-cost');
        if (name) name.value = finState.editingSupply ? finState.editingSupply.name : '';
        if (cost) cost.value = finState.editingSupply ? finState.editingSupply.cost_per_unit : '';
        if (unit && finState.editingSupply) unit.value = finState.editingSupply.unit_of_measure_id;

        if (form) {
            form.style.display = 'block';
        }

        var modal = document.getElementById('fin-supply-modal');
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        var dialog = document.getElementById('fin-supply-dialog');
        if (dialog) dialog.focus();
        else if (name) name.focus();
    }

    function closeSupplyForm() {
        finState.editingSupply = null;
        var form = document.getElementById('fin-supply-form');
        if (form) form.style.display = 'none';

        var modal = document.getElementById('fin-supply-modal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function saveSupply() {
        var body = {
            name: document.getElementById('fin-supply-name').value.trim(),
            unit_of_measure_id: Number(document.getElementById('fin-supply-unit').value),
            cost_per_unit: Number(document.getElementById('fin-supply-cost').value)
        };

        if (finState.editingSupply) body.id = finState.editingSupply.id;

        fdFetchJson(FIN_URL.supplies, { method: 'POST', body: JSON.stringify(body) })
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudo guardar el insumo.', true);
                    return;
                }
                fdToast(res.data.message || 'Insumo guardado.', false);
                closeSupplyForm();
                loadFinances();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    function deleteSupply(id) {
        var supply = finState.supplies.filter(function (s) { return s.id === id; })[0];
        if (!supply) return;

        var enFichas = Number(supply.recipes_count || 0);

        if (enFichas > 0) {
            fdToast('No se puede eliminar "' + supply.name + '": se usa en ' + enFichas
                + ' ficha(s) técnica(s). Quitálo de esas fichas antes de borrarlo.', true);
            return;
        }

        if (!window.confirm('¿Eliminar el insumo "' + supply.name + '"?')) return;

        fdFetchJson(FIN_URL.supplies + '/' + id, { method: 'DELETE' })
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudo eliminar el insumo.', true);
                    loadFinances();
                    return;
                }

                fdToast(res.data.message || 'Insumo eliminado.', false);

                if (finState.editingSupply && finState.editingSupply.id === id) closeSupplyForm();

                loadFinances();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    function openRecipe(productId) {
        var row = finState.products.filter(function (p) { return p.product_id === productId; })[0];
        if (!row) return;

        finState.recipeProduct = row;

        var title = document.getElementById('fin-recipe-title');
        if (title) title.textContent = 'Ficha técnica · ' + row.name;

        var modal = document.getElementById('fin-recipe-modal');
        if (modal) modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        var dialog = document.getElementById('fin-recipe-dialog');
        if (dialog && dialog.focus) dialog.focus();

        var items = document.getElementById('fin-recipe-items');
        items.innerHTML = '<p style="font-size:12px;color:#6b7280;">Cargando ficha...</p>';

        fdFetchJson(FIN_URL.recipe + '/' + productId + '/recipe')
            .then(function (res) {
                if (!res.ok) {
                    items.innerHTML = '<p style="font-size:12px;color:#b91c1c;">'
                        + escapeHtml((res.data && res.data.message) || 'No se pudo cargar la ficha.') + '</p>';
                    return;
                }
                renderRecipeRows(res.data.recipe.items || []);
                finState.dirty = false;
                updateRecipeTotal();
            })
            .catch(function () {
                items.innerHTML = '<p style="font-size:12px;color:#b91c1c;">No se pudo conectar con el servidor.</p>';
            });
    }

    function renderRecipeRows(items) {
        var box = document.getElementById('fin-recipe-items');
        if (!box) return;

        // Pinta la lista entera: asi desaparece el "Cargando ficha...".
        if (items.length) {
            box.innerHTML = items.map(function (item) {
                return finRecipeRowHtml(item.supply_id, item.quantity_required);
            }).join('');
        } else {
            box.innerHTML = '<p style="font-size:12px;color:#6b7280;margin:0 0 10px;">'
                + 'Este producto todavía no tiene ficha técnica. Agregá los insumos que necesita para calcular su costo.</p>';
        }
    }

    function finRecipeRowHtml(supplyId, quantity) {
        var options = finState.supplies.map(function (s) {
            return '<option value="' + s.id + '"' + (s.id === supplyId ? ' selected' : '') + '>'
                + escapeHtml(s.name) + '</option>';
        }).join('');

        return '<div class="fin-recipe-row">'
            + '<select class="fin-recipe-supply" onchange="finRecipeChanged()" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;background:#fff;">'
            + (options || '<option value="">Cargá insumos primero</option>') + '</select>'
            + '<input type="number" step="0.001" min="0.001" oninput="finRecipeChanged()" class="fin-recipe-qty" value="' + (quantity || '') + '" '
            + 'style="padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;" placeholder="Cantidad" />'
            + '<button type="button" onclick="finRemoveRecipeRow(this)" title="Quitar insumo" aria-label="Quitar insumo" '
            + 'style="background:#fff;color:#b91c1c;border:1px solid #fecaca;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">'
            + finIcon('trash', 15) + '</button>'
            + '</div>';
    }

    function addRecipeRow() {
        var box = document.getElementById('fin-recipe-items');

        if (!box) {
            var recipe = document.getElementById('fin-recipe');
            if (!recipe) {
                fdToast('Abrí la ficha técnica de un producto desde la sección Productos.', true);
                return;
            }
            box = document.createElement('div');
            box.id = 'fin-recipe-items';
            recipe.appendChild(box);
        }

        if (finState.supplies.length) {
            finAppendRecipeRow(box);
            return;
        }

        // Los insumos todavia no llegaron (o fallaron): los pedimos y reintentamos.
        loadSupplies()
            .catch(function () {})
            .then(function () { finAppendRecipeRow(box); });
    }

    function finAppendRecipeRow(box) {
        try {
            box.insertAdjacentHTML('beforeend', finRecipeRowHtml(null, ''));

            var fila = box.lastElementChild;
            if (fila && fila.scrollIntoView) fila.scrollIntoView({ block: 'nearest' });

            finState.dirty = true;
            updateRecipeTotal();

            if (!finState.supplies.length) {
                fdToast('Cargá al menos un insumo en la sección Insumos para elegirlo acá.', true);
            }
        } catch (e) {
            fdToast('No se pudo agregar el insumo: ' + (e && e.message ? e.message : e), true);
        }
    }

    function finRemoveRecipeRow(btn) {
        var fila = btn && btn.parentNode;
        if (fila && fila.parentNode) fila.parentNode.removeChild(fila);
        finState.dirty = true;
        updateRecipeTotal();
    }

    function finRecipeChanged() {
        finState.dirty = true;
        updateRecipeTotal();
    }

    function saveRecipe() {
        if (!finState.recipeProduct) return;

        var items = [];
        var selects = document.querySelectorAll('#fin-recipe-items .fin-recipe-supply');
        var quantities = document.querySelectorAll('#fin-recipe-items .fin-recipe-qty');

        for (var i = 0; i < selects.length; i++) {
            if (!selects[i].value) continue;

            var cantidad = Number(quantities[i].value);
            if (!(cantidad > 0)) {
                fdToast('Poné la cantidad de cada insumo antes de guardar.', true);
                return;
            }

            items.push({ supply_id: Number(selects[i].value), quantity_required: cantidad });
        }

        if (!items.length && selects.length) {
            fdToast('Elegí un insumo en cada fila antes de guardar.', true);
            return;
        }

        fdFetchJson(FIN_URL.recipe + '/' + finState.recipeProduct.product_id + '/recipe', {
            method: 'POST',
            body: JSON.stringify({ items: items })
        })
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudo guardar la ficha técnica.', true);
                    return;
                }
                fdToast('Ficha técnica guardada. Costo: ' + fdMoney(res.data.production_cost)
                    + ' · Margen: ' + (res.data.profit_margin === null ? '—' : finNum(res.data.profit_margin) + '%'), false);
                finState.dirty = false;
                updateRecipeTotal();
                loadFinances();
            })
            .catch(function () { fdToast('No se pudo conectar con el servidor.', true); });
    }

    function updateRecipeTotal() {
        var total = document.getElementById('fin-recipe-total');
        if (!total) return;

        var selects = document.querySelectorAll('#fin-recipe-items .fin-recipe-supply');
        var quantities = document.querySelectorAll('#fin-recipe-items .fin-recipe-qty');
        var sum = 0;
        var missing = false;

        for (var i = 0; i < selects.length; i++) {
            var supply = finState.supplies.filter(function (s) { return s.id === Number(selects[i].value); })[0];
            if (!supply) { missing = true; continue; }
            sum += Number(quantities[i].value || 0) * Number(supply.cost_per_unit || 0);
        }

        if (!finState.recipeProduct) {
            total.innerHTML = '';
            return;
        }

        var texto = 'Costo estimado: ' + fdMoney(Math.round(sum * 100) / 100)
            + (missing ? ' · completá las cantidades' : '');

        total.innerHTML = escapeHtml(texto)
            + (finState.dirty ? ' <span style="color:#b45309;">· cambios sin guardar</span>' : '');
    }

    function closeRecipe() {
        finState.recipeProduct = null;
        finState.dirty = false;

        var modal = document.getElementById('fin-recipe-modal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';

        updateRecipeTotal();
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;

        var supplyModal = document.getElementById('fin-supply-modal');
        if (supplyModal && supplyModal.style.display === 'block') {
            closeSupplyForm();
            return;
        }

        closeRecipe();
    });

    window.loadFinances = loadFinances;
    window.finGoPage = finGoPage;
    window.finTab = finTab;
    window.finToggleMenu = finToggleMenu;
    window.openSupplyForm = openSupplyForm;
    window.closeSupplyForm = closeSupplyForm;
    window.saveSupply = saveSupply;
    window.deleteSupply = deleteSupply;
    window.openRecipe = openRecipe;
    window.closeRecipe = closeRecipe;
    window.saveRecipe = saveRecipe;
    window.addRecipeRow = addRecipeRow;
    window.finRecipeChanged = finRecipeChanged;
    window.finRemoveRecipeRow = finRemoveRecipeRow;
    window.updateRecipeTotal = updateRecipeTotal;
})();
</script>
