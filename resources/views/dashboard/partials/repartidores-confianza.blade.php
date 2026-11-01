{{-- Repartidores de confianza: gestión del despacho cerrado del comercio.
     Consume los endpoints /api/v1/provider/trusted-drivers y no libera pedidos
     por tiempo: la única salida automática no existe, es siempre manual. --}}
<div id="fd-td-panel">
    <style>
        #fd-td-panel{--fd-orange:#D24C19;--fd-navy:#0c2a4d;color:#0f172a;}
        #fd-td-panel *{box-sizing:border-box;}
        #fd-td-panel .fd-td-hero{display:flex;gap:18px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;}
        #fd-td-panel .fd-td-eyebrow{font-size:10px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#94a3b8;}
        #fd-td-panel .fd-td-title{font-size:20px;font-weight:800;color:var(--fd-navy);margin:4px 0 0;letter-spacing:-.3px;}
        #fd-td-panel .fd-td-sub{font-size:12.5px;color:#64748b;margin:6px 0 0;max-width:560px;line-height:1.55;}
        #fd-td-panel .fd-td-chip{display:inline-flex;align-items:center;gap:7px;font-size:11.5px;font-weight:700;color:#475569;background:#f8fafc;border:1px solid #e5e7eb;padding:4px 10px;border-radius:9999px;}
        #fd-td-panel .fd-td-dot{width:8px;height:8px;border-radius:50%;flex:none;display:inline-block;}
        #fd-td-panel .fd-td-switch{display:inline-flex;align-items:center;gap:10px;cursor:pointer;user-select:none;position:relative;padding:6px 0;}
        #fd-td-panel .fd-td-switch input{position:absolute;opacity:0;width:0;height:0;}
        #fd-td-panel .fd-td-track{width:44px;height:25px;border-radius:9999px;background:#cbd5e1;position:relative;transition:background .18s ease;flex:none;}
        #fd-td-panel .fd-td-thumb{position:absolute;top:3px;left:3px;width:19px;height:19px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.25);transition:transform .18s ease;}
        #fd-td-panel .fd-td-switch input:checked + .fd-td-track{background:var(--fd-orange);}
        #fd-td-panel .fd-td-switch input:checked + .fd-td-track .fd-td-thumb{transform:translateX(19px);}
        #fd-td-panel .fd-td-switch input:focus-visible + .fd-td-track{box-shadow:0 0 0 3px rgba(210,76,25,.22);}
        #fd-td-panel .fd-td-switch input:disabled + .fd-td-track{opacity:.55;}
        #fd-td-panel .fd-td-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:0 0 16px;}
        #fd-td-panel .fd-td-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;}
        #fd-td-panel .fd-td-kpi-value{font-size:18px;font-weight:800;color:var(--fd-navy);line-height:1.1;}
        #fd-td-panel .fd-td-kpi-label{font-size:10.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#94a3b8;margin-top:4px;}
        #fd-td-panel .fd-td-add-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;}
        #fd-td-panel .fd-td-add-tools{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto;}
        #fd-td-panel .fd-td-search-input{width:240px;min-width:200px;max-width:260px;padding:7px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;color:#0f172a;background:#fff;outline:none;transition:border-color .15s;appearance:none;-webkit-appearance:none;}
        #fd-td-panel .fd-td-search-input::placeholder{color:#9ca3af;}
        #fd-td-panel .fd-td-search-input:focus{border-color:#9ca3af;box-shadow:none;}
        #fd-td-panel .fd-td-results{display:flex;flex-direction:column;gap:8px;}
        #fd-td-panel .fd-td-result{display:flex;align-items:center;gap:10px;padding:9px 11px;border:1px solid #eef2f7;border-radius:10px;background:#fff;}
        #fd-td-panel .fd-td-result:hover{border-color:#e2e8f0;background:#f8fafc;}
        #fd-td-panel .fd-td-avatar{width:34px;height:34px;border-radius:50%;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex:none;}
        #fd-td-panel .fd-td-result-main{min-width:0;flex:1;}
        #fd-td-panel .fd-td-name{font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        #fd-td-panel .fd-td-meta{font-size:11.5px;color:#64748b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        #fd-td-panel .fd-td-empty{padding:18px 8px;text-align:center;font-size:12.5px;color:#94a3b8;line-height:1.5;}
        #fd-td-panel .fd-td-table-wrap{overflow-x:auto;margin-top:4px;}
        #fd-td-panel table{width:100%;border-collapse:collapse;min-width:620px;}
        #fd-td-panel thead th{font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#94a3b8;text-align:left;padding:0 10px 10px;border-bottom:1px solid #eef2f7;white-space:nowrap;}
        #fd-td-panel tbody td{padding:11px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:12.5px;color:#334155;}
        #fd-td-panel tbody tr:last-child td{border-bottom:none;}
        #fd-td-panel .fd-td-user{display:flex;align-items:center;gap:10px;min-width:190px;}
        #fd-td-panel .fd-td-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:3px 9px;border-radius:9999px;white-space:nowrap;}
        #fd-td-panel .fd-td-badge.on{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;}
        #fd-td-panel .fd-td-badge.off{background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;}
        #fd-td-panel .fd-td-plate{display:inline-block;font-size:11.5px;font-weight:800;letter-spacing:.06em;color:#334155;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:2px 7px;}
        #fd-td-panel .fd-td-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid transparent;border-radius:6px;font-size:12px;font-weight:700;padding:7px 12px;cursor:pointer;transition:all .15s;white-space:nowrap;}
        #fd-td-panel .fd-td-btn-primary{background:var(--fd-orange);border-color:var(--fd-orange);color:#fff;}
        #fd-td-panel .fd-td-btn-primary:hover{background:#b84114;border-color:#b84114;}
        #fd-td-panel .fd-td-btn-primary:disabled{opacity:.55;cursor:not-allowed;}
        #fd-td-panel .fd-td-btn-ghost{background:#fff;border-color:#e2e8f0;color:#475569;padding:7px 9px;}
        #fd-td-panel .fd-td-btn-ghost:hover{border-color:#fecaca;background:#fef2f2;color:#b91c1c;}
        #fd-td-panel .fd-td-msg{display:none;margin:0 0 14px;padding:10px 12px;border-radius:8px;font-size:12px;font-weight:600;line-height:1.45;}
        @media (max-width:640px){
            #fd-td-panel .fd-td-hero{flex-direction:column;}
            #fd-td-panel .fd-td-add-head{align-items:stretch;}
            #fd-td-panel .fd-td-add-tools{width:100%;margin-left:0;}
            #fd-td-panel .fd-td-search-input{width:100%;min-width:0;max-width:none;flex:1;}
        }
    </style>

    <div class="pcard" style="padding:20px 22px;margin-bottom:14px;">
        <div class="fd-td-hero">
            <div style="min-width:0;flex:1;">
                <div class="fd-td-eyebrow">Despacho de pedidos</div>
                <h2 class="fd-td-title">Repartidores de confianza</h2>
                <p class="fd-td-sub">
                    Elegí quiénes pueden tomar los pedidos de tu comercio. Con la modalidad activa, los pedidos
                    quedan reservados a tu lista y <strong>no se liberan solos por tiempo</strong>: esperan hasta que
                    desactives la modalidad o envíes el pedido manualmente al pool general.
                </p>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:12px;">
                    <span class="fd-td-chip"><span class="fd-td-dot" id="fd-td-dot" style="background:#94a3b8;"></span><span id="fd-td-mode">Pool general</span></span>
                    <span class="fd-td-chip" id="fd-td-count">0 integrantes</span>
                </div>
            </div>
            <label class="fd-td-switch" title="Despachar solo a los repartidores de confianza">
                <input type="checkbox" id="fd-td-toggle" onchange="fdTrustedToggle(this)">
                <span class="fd-td-track"><span class="fd-td-thumb"></span></span>
                <span style="font-size:12px;font-weight:700;color:#334155;">Solo repartidores de confianza</span>
            </label>
        </div>
    </div>

    <div id="fd-td-msg" class="fd-td-msg" role="status" aria-live="polite"></div>

    <div class="fd-td-kpis">
        <div class="fd-td-kpi">
            <div class="fd-td-kpi-value" id="fd-td-kpi-count">0</div>
            <div class="fd-td-kpi-label">En la lista</div>
        </div>
        <div class="fd-td-kpi">
            <div class="fd-td-kpi-value" id="fd-td-kpi-active">0</div>
            <div class="fd-td-kpi-label">Activos</div>
        </div>
        <div class="fd-td-kpi">
            <div class="fd-td-kpi-value" id="fd-td-kpi-mode" style="font-size:14px;">Pool general</div>
            <div class="fd-td-kpi-label">Modo actual</div>
        </div>
    </div>

    <div class="pcard" style="padding:20px 22px;margin-bottom:14px;">
        <div class="fd-td-add-head">
            <div style="min-width:0;flex:1;">
                <h3 class="pcard-title" style="margin-bottom:2px;">Agregar repartidor</h3>
                <p class="pcard-sub" style="margin-bottom:0;">Buscá por nombre, correo o ID de usuario para sumarlo a la lista.</p>
            </div>
            <div class="fd-td-add-tools">
                <input type="search" id="fd-td-search" class="fd-td-search-input" placeholder="Buscar repartidor..." autocomplete="off" oninput="fdTrustedSearchInput(this.value)" aria-label="Buscar repartidor para agregar">
                <span class="fd-td-chip" id="fd-td-results-count">0 disponibles</span>
            </div>
        </div>

        <div id="fd-td-results" class="fd-td-results">
            <div class="fd-td-empty">Cargando repartidores disponibles...</div>
        </div>
    </div>

    <div class="pcard" style="padding:20px 22px;margin-bottom:0;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
            <div>
                <h3 class="pcard-title" style="margin-bottom:2px;">Lista de confianza</h3>
                <p class="pcard-sub" style="margin-bottom:0;">Solo estos repartidores verán y podrán tomar tus pedidos cerrados.</p>
            </div>
            <span class="fd-td-chip" id="fd-td-list-count">Sin integrantes</span>
        </div>

        <div class="fd-td-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Repartidor</th>
                        <th>Vehículo</th>
                        <th>Patente</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="fd-td-list">
                    <tr><td colspan="5" class="fd-td-empty">Cargando lista de confianza...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
