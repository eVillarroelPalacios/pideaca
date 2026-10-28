            <section id="dash-pedidos" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
                <style>
                    #dash-pedidos{--fd-orange:#D24C19;--fd-navy:#0c2a4d;}
                    #dash-pedidos *{box-sizing:border-box;}
                    #dash-pedidos .fd-card{background:#fff;border:1px solid #e5e7eb;}
                    #dash-pedidos .fd-eyebrow{font-size:10px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#94a3b8;}
                    #dash-pedidos .fd-chip{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:600;color:#475569;background:#f8fafc;border:1px solid #e5e7eb;padding:5px 11px;border-radius:9999px;}
                    #dash-pedidos .fd-dot{width:8px;height:8px;border-radius:50%;flex:none;display:inline-block;}
                    #dash-pedidos .fd-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:600;padding:8px 14px;border-radius:4px;cursor:pointer;transition:all .15s;background:#fff;border:1px solid #d1d5db;color:#334155;line-height:1;text-decoration:none;}
                    #dash-pedidos .fd-btn:hover{background:#f8fafc;border-color:#cbd5e1;}
                    #dash-pedidos .fd-btn:disabled{opacity:.5;cursor:default;}
                    #dash-pedidos .fd-btn-primary{background:var(--fd-orange);border-color:var(--fd-orange);color:#fff;}
                    #dash-pedidos .fd-btn-primary:hover{background:#b93f14;border-color:#b93f14;}
                    #dash-pedidos .fd-btn-whatsapp{background:#eff6ff;border-color:#bbf7d0;color:#15803d;}
                    #dash-pedidos .fd-btn-whatsapp:hover{background:#f0fdf4;border-color:#86efac;}
                    #dash-pedidos .fd-btn-ghost{background:transparent;border-color:transparent;color:#64748b;}
                    #dash-pedidos .fd-btn-ghost:hover{background:#f1f5f9;color:#0f172a;border-color:transparent;}
                    #dash-pedidos .fd-btn-danger{color:#b91c1c;border-color:#fecaca;background:#fff;}
                    #dash-pedidos .fd-btn-danger:hover{background:#fef2f2;}
                    #dash-pedidos .fd-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;padding:0;border-radius:4px;border:1px solid #e5e7eb;background:#fff;color:#64748b;cursor:pointer;transition:all .15s;line-height:0;}
                    #dash-pedidos .fd-icon-btn:hover{color:#0f172a;border-color:#cbd5e1;background:#f8fafc;}
                    #dash-pedidos .fd-kpi{flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;padding:14px 16px;}
                    #dash-pedidos .fd-kpi b{display:block;font-size:26px;font-weight:800;color:var(--fd-navy);line-height:1.1;margin-top:6px;}
                    #dash-pedidos .fd-kpi.fd-kpi-alert{border-color:#fdba74;background:#fffaf6;}
                    #dash-pedidos .fd-kpi.fd-kpi-alert b{color:var(--fd-orange);}
                    #dash-pedidos .fd-kpi.fd-kpi-alert .fd-eyebrow{color:var(--fd-orange);}
                    #dash-pedidos .fd-tab{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;font-size:13px;font-weight:600;color:#64748b;background:transparent;border:1px solid transparent;border-radius:9999px;cursor:pointer;transition:all .15s;}
                    #dash-pedidos .fd-tab:hover{color:#0f172a;background:#f1f5f9;}
                    #dash-pedidos .fd-tab.active{background:#fff7ed;color:var(--fd-orange);border-color:#fdba74;box-shadow:0 1px 2px rgba(210,76,25,.16);}
                    #dash-pedidos .fd-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;border-radius:9999px;background:#e5e7eb;color:#475569;font-size:10px;font-weight:800;}
                    #dash-pedidos .fd-tab.active .fd-tab-count{background:var(--fd-orange);color:#fff;}
                    /* En esta vista las pestañas son píldoras con texto: no se ocultan en mobile. */
                    #dash-pedidos .fd-tab .tab-label{display:inline !important;}
                    #dash-pedidos .fd-input{height:36px;border:1px solid #d1d5db;background:#fff;font-size:12px;color:#334155;outline:none;padding:0 10px;border-radius:4px;transition:border-color .15s,box-shadow .15s;}
                    #dash-pedidos .fd-input:focus{border-color:var(--fd-orange);box-shadow:0 0 0 3px rgba(210,76,25,.12);}
                    #dash-pedidos .fd-input::placeholder{color:#94a3b8;}
                    #dash-pedidos .fd-range-label{font-size:11px;color:#6b7280;font-weight:600;}
                    #dash-pedidos .fd-search-wrap{position:relative;display:flex;align-items:center;}
                    #dash-pedidos .fd-search-wrap svg{position:absolute;left:10px;color:#94a3b8;pointer-events:none;}
                    #dash-pedidos .fd-search-wrap input{padding-left:32px;width:230px;}
                    #dash-pedidos .fd-select-wrap{position:relative;display:inline-flex;align-items:center;gap:8px;height:36px;border:1px solid #d1d5db;background:#fff;padding:0 10px;border-radius:4px;transition:border-color .15s,box-shadow .15s;}
                    #dash-pedidos .fd-select-wrap:focus-within{border-color:var(--fd-orange);box-shadow:0 0 0 3px rgba(210,76,25,.12);}
                    #dash-pedidos .fd-select-wrap select{appearance:none;-webkit-appearance:none;border:none;background:transparent;font-size:12px;font-weight:600;color:#334155;outline:none;padding-right:16px;cursor:pointer;}
                    #dash-pedidos .fd-select-wrap .fd-select-arrow{position:absolute;right:9px;pointer-events:none;color:#94a3b8;}
                    #dash-pedidos .fd-order{background:#fff;border:1px solid #e5e7eb;margin-bottom:14px;overflow:hidden;transition:box-shadow .18s,border-color .18s;}
                    #dash-pedidos .fd-order:hover{border-color:#cbd5e1;box-shadow:0 8px 22px rgba(15,23,42,.07);}
                    #dash-pedidos .fd-order-head{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb;}
                    #dash-pedidos .fd-idx{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:26px;padding:0 6px;border:1px solid #fdba74;background:#fff7ed;color:var(--fd-orange);font-size:11px;font-weight:800;border-radius:4px;}
                    #dash-pedidos .fd-onum{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12.5px;font-weight:700;color:var(--fd-navy);letter-spacing:-.2px;}
                    #dash-pedidos .fd-body{padding:16px;}
                    #dash-pedidos .fd-block{border:1px solid #e5e7eb;background:#fff;border-radius:6px;padding:14px;}
                    #dash-pedidos .fd-block + .fd-block{margin-top:12px;}
                    #dash-pedidos .fd-block-title{font-size:10px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#64748b;margin:0 0 12px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;}
                    #dash-pedidos .fd-avatar{width:40px;height:40px;border-radius:50%;background:#eef2f7;color:#334155;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex:none;}
                    #dash-pedidos .fd-avatar-blue{background:#e0f2fe;color:#0369a1;}
                    #dash-pedidos .fd-info-name{font-size:14px;font-weight:700;color:#0f172a;}
                    #dash-pedidos .fd-info-meta{font-size:12px;color:#64748b;margin-top:2px;word-break:break-word;}
                    #dash-pedidos .fd-info-link{font-size:12.5px;color:var(--fd-orange);font-weight:600;text-decoration:none;}
                    #dash-pedidos .fd-info-link:hover{text-decoration:underline;}
                    #dash-pedidos .fd-note-chip{display:inline-flex;align-items:center;gap:6px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:11.5px;font-weight:600;padding:5px 10px;border-radius:9999px;}
                    #dash-pedidos .fd-stepbar{display:flex;gap:6px;margin-top:12px;}
                    #dash-pedidos .fd-stepbar div{flex:1;}
                    #dash-pedidos .fd-step-label{font-size:10.5px;font-weight:700;color:#94a3b8;margin-bottom:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
                    #dash-pedidos .fd-step-label.done{color:#047857;}
                    #dash-pedidos .fd-step-label.now{color:#D24C19;}
                    #dash-pedidos .fd-step-track{height:5px;border-radius:9999px;background:#e5e7eb;overflow:hidden;}
                    #dash-pedidos .fd-step-fill{height:100%;border-radius:9999px;background:#047857;transition:width .4s ease;}
                    #dash-pedidos .fd-items-head{display:flex;justify-content:space-between;font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#64748b;padding-bottom:10px;border-bottom:1px solid #e5e7eb;}
                    #dash-pedidos .fd-item{display:flex;gap:10px;align-items:flex-start;padding:11px 0;border-bottom:1px solid #f1f5f9;}
                    #dash-pedidos .fd-qty{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:24px;padding:0 6px;border:1px solid #fdba74;background:#fff7ed;color:var(--fd-orange);font-size:11px;font-weight:800;border-radius:4px;flex:none;}
                    #dash-pedidos .fd-item-total{font-size:13.5px;font-weight:700;color:#0f172a;white-space:nowrap;margin-left:auto;}
                    #dash-pedidos .fd-total-line{display:flex;justify-content:space-between;font-size:13px;color:#64748b;padding:3px 0;}
                    #dash-pedidos .fd-total-final{display:flex;justify-content:space-between;align-items:baseline;font-size:15px;font-weight:800;color:#0f172a;margin-top:8px;padding-top:10px;border-top:1px dashed #e2e8f0;}
                    #dash-pedidos .fd-total-final span:last-child{color:var(--fd-orange);font-size:18px;}
                    #dash-pedidos .fd-card-foot{display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap;padding:12px 16px;background:#f8fafc;border-top:1px solid #e5e7eb;}
                    #dash-pedidos .fd-pay{display:inline-flex;align-items:center;gap:7px;font-size:12px;color:#475569;font-weight:600;}
                    #dash-pedidos .fd-empty-ico{width:58px;height:58px;border-radius:50%;background:#f1f5f9;color:#94a3b8;display:inline-flex;align-items:center;justify-content:center;}
                    #dash-pedidos .fd-spinner{width:26px;height:26px;border:3px solid #e2e8f0;border-top-color:var(--fd-orange);border-radius:50%;margin:0 auto;animation:fdSpin .8s linear infinite;}
                    @keyframes fdSpin{to{transform:rotate(360deg);}}
                    @media (max-width:768px){
                        #dash-pedidos .fd-search-wrap{flex:1;}
                        #dash-pedidos .fd-search-wrap input{width:100%;}
                        #dash-pedidos .fd-kpi{min-width:calc(50% - 8px);}
                        #dash-pedidos .fd-order-head{gap:8px;}
                    }
                </style>

                {{-- Barra superior: centro de despacho, comercio y sincronización en vivo --}}
                <div class="fd-card" style="padding:14px 18px;margin-bottom:14px;">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <span style="width:32px;height:32px;border-radius:6px;background:#0c2a4d;color:#fff;display:inline-flex;align-items:center;justify-content:center;flex:none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        </span>
                        <span style="font-size:13px;font-weight:700;color:#0c2a4d;">Centro de Despacho</span>
                        <span style="width:1px;height:16px;background:#e2e8f0;"></span>
                        <span id="fd-orders-bizname" style="font-size:13px;color:#475569;">{{ optional($user->provider)->business_name ?: 'Tu comercio' }}</span>
                        <span style="width:1px;height:16px;background:#e2e8f0;"></span>
                        <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#94a3b8;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span id="fd-orders-summary">Sincronizado —</span>
                        </span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:12px;">
                        <span class="fd-chip"><span class="fd-dot" id="fd-orders-dot-pending" style="background:#f59e0b;"></span><span id="fd-orders-chip-pending">0 pendientes</span></span>
                        <span class="fd-chip"><span class="fd-dot" id="fd-orders-dot-kitchen" style="background:#2563eb;"></span><span id="fd-orders-chip-kitchen">0 en cocina</span></span>
                        <div style="flex:1;"></div>
                        <button type="button" class="fd-btn" onclick="loadProviderOrders()" title="Actualizar pedidos" aria-label="Actualizar pedidos">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                            Actualizar
                        </button>
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <h2 style="font-size:22px;font-weight:800;color:#0c2a4d;margin:0;letter-spacing:-.3px;">Control de Pedidos</h2>
                    <p style="font-size:13px;color:#64748b;margin:5px 0 0;">Supervisá el flujo de preparación, la asignación de repartidores y las entregas en tiempo real.</p>
                </div>

                {{-- Indicadores rápidos --}}
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                    <div class="fd-kpi">
                        <div class="fd-eyebrow">Hoy</div>
                        <b id="fd-orders-kpi-today">0</b>
                    </div>
                    <div class="fd-kpi">
                        <div class="fd-eyebrow">Historial</div>
                        <b id="fd-orders-kpi-history">0</b>
                    </div>
                    <div class="fd-kpi fd-kpi-alert">
                        <div class="fd-eyebrow">Pendientes</div>
                        <b id="fd-orders-kpi-pending">0</b>
                    </div>
                </div>

                {{-- Pestañas y filtros --}}
                <div class="fd-card" style="padding:12px 14px;margin-bottom:14px;">
                    <div id="fd-orders-tabs" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                        <button type="button" id="fd-orders-tab-today" onclick="fdOrdersTab('today')" class="fd-tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                            <span class="tab-label">Pedidos de hoy</span>
                            <span id="fd-orders-count-today" class="fd-tab-count" style="display:none;">0</span>
                        </button>
                        <button type="button" id="fd-orders-tab-history" onclick="fdOrdersTab('history')" class="fd-tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>
                            <span class="tab-label">Historial</span>
                            <span id="fd-orders-count-history" class="fd-tab-count" style="display:none;">0</span>
                        </button>

                        <div id="fd-orders-range" style="display:none;gap:6px;align-items:center;flex-wrap:wrap;margin-left:auto;">
                            <label for="fd-orders-date-from" class="fd-range-label">Desde</label>
                            <input type="date" id="fd-orders-date-from" onchange="fdOrdersRangeChange()" class="fd-input" style="width:150px;">
                            <label for="fd-orders-date-to" class="fd-range-label">Hasta</label>
                            <input type="date" id="fd-orders-date-to" onchange="fdOrdersRangeChange()" class="fd-input" style="width:150px;">
                            <button type="button" class="fd-icon-btn" onclick="fdOrdersClearRange()" title="Limpiar rango de fechas" aria-label="Limpiar rango de fechas">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;">
                        <div class="fd-select-wrap">
                            <span class="fd-dot" id="fd-orders-status-dot" style="background:#94a3b8;"></span>
                            <select id="fd-orders-filter" onchange="loadProviderOrders()" aria-label="Filtrar por estado">
                                <option value="">Todos los estados</option>
                            </select>
                            <svg class="fd-select-arrow" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </div>

                        <div class="fd-search-wrap">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="search" id="fd-orders-search" class="fd-input" placeholder="Buscar pedido o cliente..." aria-label="Buscar pedido o cliente" oninput="fdOrdersSearchDebounce()">
                        </div>

                        <button type="button" class="fd-btn" id="fd-orders-reset" onclick="fdOrdersResetFilters()" title="Restablecer filtros" aria-label="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                            Restablecer
                        </button>
                    </div>
                </div>

                <div id="fd-orders-loading" class="fd-card" style="display:none;padding:44px 24px;text-align:center;">
                    <div class="fd-spinner"></div>
                    <p style="font-size:13px;color:#6b7280;margin:14px 0 0;">Cargando pedidos...</p>
                </div>

                <div id="fd-orders-message" class="fd-card" style="display:none;padding:52px 24px;text-align:center;">
                    <span class="fd-empty-ico">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                    </span>
                    <p id="fd-orders-message-title" style="font-size:16px;font-weight:700;color:#0f172a;margin:16px 0 0;">No se encontraron pedidos</p>
                    <p id="fd-orders-message-text" style="font-size:13px;color:#6b7280;margin:7px auto 0;max-width:430px;white-space:pre-line;">No hay pedidos que coincidan con los filtros seleccionados o el criterio de búsqueda actual.</p>
                    <div id="fd-orders-message-action" style="margin-top:18px;"></div>
                </div>

                <div id="fd-orders-list" style="display:none;"></div>
                <div id="fd-orders-pagination" style="display:none;justify-content:center;align-items:center;gap:10px;margin-top:14px;"></div>
            </section>
