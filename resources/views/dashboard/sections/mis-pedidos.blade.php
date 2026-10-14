            <section id="dash-mis-pedidos" class="dash-section" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                        <p id="fd-myorders-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">Tus pedidos y su estado</p>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <select id="fd-myorders-filter" onchange="loadMyOrders()" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;">
                            <option value="">Todos los estados</option>
                        </select>
                    </div>
                </div>

                <div id="fd-myorders-tabs" style="display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:14px;flex-wrap:wrap;">
                    <button type="button" id="fd-myorders-tab-today" onclick="fdMyOrdersTab('today')" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:color .15s ease,border-color .15s ease,background .15s ease;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                        <span class="tab-label">Pedidos de hoy</span>
                        <span id="fd-myorders-count-today" style="background:#D24C19;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
                    </button>
                    <button type="button" id="fd-myorders-tab-history" onclick="fdMyOrdersTab('history')" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:color .15s ease,border-color .15s ease,background .15s ease;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span class="tab-label">Historial</span>
                        <span id="fd-myorders-count-history" style="background:#6b7280;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
                    </button>

                    <div id="fd-myorders-range" style="display:none;gap:6px;align-items:center;flex-wrap:wrap;margin-left:auto;padding-bottom:6px;">
                        <label for="fd-myorders-date-from" style="font-size:11px;color:#6b7280;">Desde</label>
                        <input type="date" id="fd-myorders-date-from" onchange="fdMyOrdersRangeChange()" style="padding:6px 8px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;color:#374151;">
                        <label for="fd-myorders-date-to" style="font-size:11px;color:#6b7280;">Hasta</label>
                        <input type="date" id="fd-myorders-date-to" onchange="fdMyOrdersRangeChange()" style="padding:6px 8px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;color:#374151;">
                        <button type="button" onclick="fdMyOrdersClearRange()" title="Limpiar rango de fechas" aria-label="Limpiar rango de fechas" style="background:#fff;color:#6b7280;border:1px solid #d1d5db;padding:6px 8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div id="fd-myorders-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
                    <p style="font-size:13px;color:#6b7280;margin:0;">Cargando pedidos...</p>
                </div>

                <div id="fd-myorders-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
                    <p id="fd-myorders-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudieron cargar tus pedidos.</p>
                </div>

                <div id="fd-myorders-list" style="display:none;"></div>
                <div id="fd-myorders-pagination" style="display:none;justify-content:center;align-items:center;gap:10px;margin-top:14px;"></div>
            </section>
