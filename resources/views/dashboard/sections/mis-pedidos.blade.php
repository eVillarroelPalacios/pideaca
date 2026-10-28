            <section id="dash-mis-pedidos" class="dash-section" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
                <style>
                    #dash-mis-pedidos{--fd-orange:#D24C19;--fd-navy:#0c2a4d;}
                    #dash-mis-pedidos *{box-sizing:border-box;}
                    #dash-mis-pedidos .fd-card{background:#fff;border:1px solid #e5e7eb;}
                    #dash-mis-pedidos .fd-eyebrow{font-size:10px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#94a3b8;}
                    #dash-mis-pedidos .fd-chip{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:600;color:#475569;background:#f8fafc;border:1px solid #e5e7eb;padding:5px 11px;border-radius:9999px;}
                    #dash-mis-pedidos .fd-chip-live{color:#047857;background:#ecfdf5;border-color:#bbf7d0;}
                    #dash-mis-pedidos .fd-dot{width:8px;height:8px;border-radius:50%;flex:none;display:inline-block;}
                    #dash-mis-pedidos .fd-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:600;padding:8px 14px;border-radius:4px;cursor:pointer;transition:all .15s;background:#fff;border:1px solid #d1d5db;color:#334155;line-height:1;text-decoration:none;}
                    #dash-mis-pedidos .fd-btn:hover{background:#f8fafc;border-color:#cbd5e1;}
                    #dash-mis-pedidos .fd-btn-outline{background:#fff7ed;border-color:#fdba74;color:var(--fd-orange);}
                    #dash-mis-pedidos .fd-btn-outline:hover{background:#ffedd5;border-color:#fb923c;}
                    #dash-mis-pedidos .fd-btn-ghost{background:transparent;border-color:transparent;color:#64748b;}
                    #dash-mis-pedidos .fd-btn-ghost:hover{background:#f1f5f9;color:#0f172a;border-color:transparent;}
                    #dash-mis-pedidos .fd-btn-danger{color:#b91c1c;border-color:#fecaca;background:#fff;}
                    #dash-mis-pedidos .fd-btn-danger:hover{background:#fef2f2;}
                    #dash-mis-pedidos .fd-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;padding:0;border-radius:4px;border:1px solid #e5e7eb;background:#fff;color:#64748b;cursor:pointer;transition:all .15s;line-height:0;}
                    #dash-mis-pedidos .fd-icon-btn:hover{color:#0f172a;border-color:#cbd5e1;background:#f8fafc;}
                    #dash-mis-pedidos .fd-tab{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;font-size:13px;font-weight:600;color:#64748b;background:transparent;border:1px solid transparent;border-radius:9999px;cursor:pointer;transition:all .15s;}
                    #dash-mis-pedidos .fd-tab:hover{color:#0f172a;background:#f1f5f9;}
                    #dash-mis-pedidos .fd-tab.active{background:#fff7ed;color:var(--fd-orange);border-color:#fdba74;box-shadow:0 1px 2px rgba(210,76,25,.16);}
                    #dash-mis-pedidos .fd-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;border-radius:9999px;background:#e5e7eb;color:#475569;font-size:10px;font-weight:800;}
                    #dash-mis-pedidos .fd-tab.active .fd-tab-count{background:var(--fd-orange);color:#fff;}
                    #dash-mis-pedidos .fd-tab .tab-label{display:inline !important;}
                    #dash-mis-pedidos .fd-input{height:36px;border:1px solid #d1d5db;background:#fff;font-size:12px;color:#334155;outline:none;padding:0 10px;border-radius:4px;transition:border-color .15s,box-shadow .15s;}
                    #dash-mis-pedidos .fd-input:focus{border-color:var(--fd-orange);box-shadow:0 0 0 3px rgba(210,76,25,.12);}
                    #dash-mis-pedidos .fd-input::placeholder{color:#94a3b8;}
                    #dash-mis-pedidos .fd-range-label{font-size:11px;color:#6b7280;font-weight:600;}
                    #dash-mis-pedidos .fd-search-wrap{position:relative;display:flex;align-items:center;}
                    #dash-mis-pedidos .fd-search-wrap svg{position:absolute;left:12px;color:#94a3b8;pointer-events:none;}
                    #dash-mis-pedidos .fd-search-wrap input{padding-left:34px;width:100%;height:40px;font-size:13px;}
                    #dash-mis-pedidos .fd-select-wrap{position:relative;display:inline-flex;align-items:center;gap:8px;height:36px;border:1px solid #d1d5db;background:#fff;padding:0 10px;border-radius:4px;transition:border-color .15s,box-shadow .15s;}
                    #dash-mis-pedidos .fd-select-wrap:focus-within{border-color:var(--fd-orange);box-shadow:0 0 0 3px rgba(210,76,25,.12);}
                    #dash-mis-pedidos .fd-select-wrap select{appearance:none;-webkit-appearance:none;border:none;background:transparent;font-size:12px;font-weight:600;color:#334155;outline:none;padding-right:16px;cursor:pointer;}
                    #dash-mis-pedidos .fd-select-wrap .fd-select-arrow{position:absolute;right:9px;pointer-events:none;color:#94a3b8;}
                    #dash-mis-pedidos .fd-order{background:#fff;border:1px solid #e5e7eb;margin-bottom:14px;overflow:hidden;transition:box-shadow .18s,border-color .18s;}
                    #dash-mis-pedidos .fd-order:hover{border-color:#cbd5e1;box-shadow:0 8px 22px rgba(15,23,42,.07);}
                    #dash-mis-pedidos .fd-shop-ico{width:44px;height:44px;border-radius:10px;background:#fff7ed;color:var(--fd-orange);display:flex;align-items:center;justify-content:center;flex:none;}
                    #dash-mis-pedidos .fd-shop-name{font-size:15px;font-weight:800;color:#0f172a;}
                    #dash-mis-pedidos .fd-onum{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11.5px;font-weight:600;color:#94a3b8;letter-spacing:-.2px;}
                    #dash-mis-pedidos .fd-live{border:1px solid #fed7aa;background:linear-gradient(180deg,#fff7ed,#fff);padding:16px;}
                    #dash-mis-pedidos .fd-live-title{font-size:17px;font-weight:800;color:#0f172a;margin:5px 0 0;line-height:1.3;}
                    #dash-mis-pedidos .fd-eta{text-align:right;}
                    #dash-mis-pedidos .fd-eta b{display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:800;color:var(--fd-orange);background:#fff;border:1px solid #fdba74;border-radius:9999px;padding:6px 13px;margin-top:5px;}
                    #dash-mis-pedidos .fd-steps{display:flex;align-items:flex-start;padding:18px 4px 4px;overflow-x:auto;}
                    #dash-mis-pedidos .fd-step{display:flex;flex-direction:column;align-items:center;width:78px;flex:none;}
                    #dash-mis-pedidos .fd-step-ico{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:1px solid #e2e8f0;background:#e2e8f0;color:#64748b;}
                    #dash-mis-pedidos .fd-step.done .fd-step-ico{background:var(--fd-orange);border-color:var(--fd-orange);color:#fff;}
                    #dash-mis-pedidos .fd-step.now .fd-step-ico{background:var(--fd-orange);border-color:var(--fd-orange);color:#fff;box-shadow:0 0 0 5px #ffe9df;}
                    #dash-mis-pedidos .fd-step-label{margin-top:7px;font-size:11px;line-height:1.25;text-align:center;color:#94a3b8;font-weight:600;}
                    #dash-mis-pedidos .fd-step.done .fd-step-label{color:#334155;}
                    #dash-mis-pedidos .fd-step.now .fd-step-label{color:var(--fd-orange);font-weight:800;}
                    #dash-mis-pedidos .fd-step-sub{font-size:10px;color:#94a3b8;margin-top:2px;text-align:center;}
                    #dash-mis-pedidos .fd-step-now{height:13px;font-size:9px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--fd-orange);line-height:13px;}
                    #dash-mis-pedidos .fd-step-line{flex:1;min-width:16px;height:3px;border-radius:2px;background:#e2e8f0;margin-top:29px;}
                    #dash-mis-pedidos .fd-step-line.done{background:var(--fd-orange);}
                    #dash-mis-pedidos .fd-block{border:1px solid #e5e7eb;background:#fff;border-radius:6px;padding:14px;}
                    #dash-mis-pedidos .fd-block + .fd-block{margin-top:12px;}
                    #dash-mis-pedidos .fd-block-ico{width:34px;height:34px;border-radius:8px;background:#fff7ed;color:var(--fd-orange);display:flex;align-items:center;justify-content:center;flex:none;}
                    #dash-mis-pedidos .fd-block-ico-green{background:#ecfdf5;color:#059669;}
                    #dash-mis-pedidos .fd-driver-name{font-size:13.5px;font-weight:700;color:#0f172a;}
                    #dash-mis-pedidos .fd-note-chip{display:inline-flex;align-items:center;gap:6px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:11.5px;font-weight:600;padding:5px 10px;border-radius:9999px;}
                    #dash-mis-pedidos .fd-item{display:flex;gap:10px;align-items:flex-start;padding:11px 0;border-bottom:1px solid #f1f5f9;}
                    #dash-mis-pedidos .fd-qty{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:24px;padding:0 6px;border:1px solid #fdba74;background:#fff7ed;color:var(--fd-orange);font-size:11px;font-weight:800;border-radius:4px;flex:none;}
                    #dash-mis-pedidos .fd-item-total{font-size:13.5px;font-weight:700;color:#0f172a;white-space:nowrap;margin-left:auto;}
                    #dash-mis-pedidos .fd-total-final{display:flex;justify-content:space-between;align-items:baseline;font-size:15px;font-weight:800;color:#0f172a;margin-top:8px;padding-top:10px;border-top:1px dashed #e2e8f0;}
                    #dash-mis-pedidos .fd-total-final span:last-child{color:var(--fd-orange);font-size:18px;}
                    #dash-mis-pedidos .fd-hist-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 16px;}
                    #dash-mis-pedidos .fd-hist-resume{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:10px 16px;border-top:1px solid #f1f5f9;font-size:12.5px;color:#475569;}
                    #dash-mis-pedidos .fd-hist-foot{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding:10px 16px;border-top:1px solid #f1f5f9;background:#f8fafc;}
                    #dash-mis-pedidos .fd-stars{color:#f59e0b;letter-spacing:1px;}
                    #dash-mis-pedidos .fd-empty-ico{width:58px;height:58px;border-radius:50%;background:#f1f5f9;color:#94a3b8;display:inline-flex;align-items:center;justify-content:center;}
                    #dash-mis-pedidos .fd-spinner{width:26px;height:26px;border:3px solid #e2e8f0;border-top-color:var(--fd-orange);border-radius:50%;margin:0 auto;animation:fdMySpin .8s linear infinite;}
                    @keyframes fdMySpin{to{transform:rotate(360deg);}}
                    @media (max-width:768px){
                        #dash-mis-pedidos .fd-eta{text-align:left;}
                        #dash-mis-pedidos .fd-step{width:66px;}
                    }
                </style>

                {{-- Cabecera: seguimiento de pedidos --}}
                <div style="margin-bottom:16px;">
                    <div style="display:inline-flex;align-items:center;gap:7px;color:var(--fd-orange);font-size:11px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        Seguimiento de pedidos
                    </div>
                    <h2 style="font-size:24px;font-weight:800;color:#0c2a4d;margin:6px 0 0;letter-spacing:-.4px;">Mis Pedidos</h2>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:8px;">
                        <span id="fd-myorders-live" class="fd-chip fd-chip-live" style="display:none;"><span class="fd-dot" style="background:#10b981;"></span><span id="fd-myorders-live-text">0 activos ahora</span></span>
                        <span id="fd-myorders-summary" style="font-size:13px;color:#6b7280;">Tus pedidos y su estado</span>
                    </div>
                </div>

                {{-- Buscador --}}
                <div class="fd-card" style="padding:10px 14px;margin-bottom:12px;">
                    <div class="fd-search-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="search" id="fd-myorders-search" class="fd-input" placeholder="Buscar por plato, local o código..." aria-label="Buscar por plato, local o código" oninput="fdMyOrdersSearchDebounce()">
                    </div>
                </div>

                {{-- Pestañas y filtros --}}
                <div class="fd-card" style="padding:12px 14px;margin-bottom:14px;">
                    <div id="fd-myorders-tabs" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                        <button type="button" id="fd-myorders-tab-today" onclick="fdMyOrdersTab('today')" class="fd-tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                            <span class="tab-label">En curso</span>
                            <span id="fd-myorders-count-today" class="fd-tab-count" style="display:none;">0</span>
                        </button>
                        <button type="button" id="fd-myorders-tab-history" onclick="fdMyOrdersTab('history')" class="fd-tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>
                            <span class="tab-label">Historial</span>
                            <span id="fd-myorders-count-history" class="fd-tab-count" style="display:none;">0</span>
                        </button>

                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-left:auto;">
                            <div id="fd-myorders-range" style="display:none;gap:6px;align-items:center;flex-wrap:wrap;">
                                <label for="fd-myorders-date-from" class="fd-range-label">Desde</label>
                                <input type="date" id="fd-myorders-date-from" onchange="fdMyOrdersRangeChange()" class="fd-input" style="width:150px;">
                                <label for="fd-myorders-date-to" class="fd-range-label">Hasta</label>
                                <input type="date" id="fd-myorders-date-to" onchange="fdMyOrdersRangeChange()" class="fd-input" style="width:150px;">
                            </div>
                            <div class="fd-select-wrap">
                                <span class="fd-dot" id="fd-myorders-status-dot" style="background:#94a3b8;"></span>
                                <select id="fd-myorders-filter" onchange="loadMyOrders()" aria-label="Filtrar por estado">
                                    <option value="">Todos los estados</option>
                                </select>
                                <svg class="fd-select-arrow" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            <button type="button" class="fd-icon-btn" onclick="fdMyOrdersResetFilters()" title="Limpiar filtros" aria-label="Limpiar filtros">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="fd-myorders-loading" class="fd-card" style="display:none;padding:44px 24px;text-align:center;">
                    <div class="fd-spinner"></div>
                    <p style="font-size:13px;color:#6b7280;margin:14px 0 0;">Cargando pedidos...</p>
                </div>

                <div id="fd-myorders-message" class="fd-card" style="display:none;padding:52px 24px;text-align:center;">
                    <span class="fd-empty-ico">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </span>
                    <p id="fd-myorders-message-text" style="font-size:16px;font-weight:700;color:#0f172a;margin:16px 0 0;white-space:pre-line;">Todavía no hiciste pedidos.</p>
                    <div id="fd-myorders-message-action" style="margin-top:18px;"></div>
                </div>

                <div id="fd-myorders-list" style="display:none;"></div>
                <div id="fd-myorders-pagination" style="display:none;justify-content:center;align-items:center;gap:10px;margin-top:14px;"></div>
            </section>
