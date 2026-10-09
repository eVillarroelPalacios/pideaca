            <section id="dash-pedidos" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                        <p id="fd-orders-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">{{ optional($user->provider)->business_name ?: 'Tu comercio' }}</p>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <select id="fd-orders-filter" onchange="loadProviderOrders()" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;">
                            <option value="">Todos los estados</option>
                        </select>
                        <button type="button" onclick="loadProviderOrders()" title="Actualizar pedidos" aria-label="Actualizar pedidos" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:8px 10px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        </button>
                    </div>
                </div>

                <div id="fd-orders-tabs" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
                    <button type="button" id="fd-orders-tab-today" onclick="fdOrdersTab('today')" title="Pedidos de hoy" aria-label="Pedidos de hoy" style="background:#fff1eb;color:#D24C19;border:1px solid #D24C19;padding:7px 10px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                        <span id="fd-orders-count-today" style="background:#D24C19;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
                    </button>
                    <button type="button" id="fd-orders-tab-history" onclick="fdOrdersTab('history')" title="Historial" aria-label="Historial" style="background:#fff;color:#6b7280;border:1px solid #e5e7eb;padding:7px 10px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span id="fd-orders-count-history" style="background:#6b7280;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
                    </button>

                    <div id="fd-orders-range" style="display:none;gap:6px;align-items:center;flex-wrap:wrap;margin-left:4px;">
                        <label for="fd-orders-date-from" style="font-size:11px;color:#6b7280;">Desde</label>
                        <input type="date" id="fd-orders-date-from" onchange="fdOrdersRangeChange()" style="padding:6px 8px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;color:#374151;">
                        <label for="fd-orders-date-to" style="font-size:11px;color:#6b7280;">Hasta</label>
                        <input type="date" id="fd-orders-date-to" onchange="fdOrdersRangeChange()" style="padding:6px 8px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;color:#374151;">
                        <button type="button" onclick="fdOrdersClearRange()" title="Limpiar rango de fechas" aria-label="Limpiar rango de fechas" style="background:#fff;color:#6b7280;border:1px solid #d1d5db;padding:6px 8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div id="fd-orders-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
                    <p style="font-size:13px;color:#6b7280;margin:0;">Cargando pedidos...</p>
                </div>

                <div id="fd-orders-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
                    <p id="fd-orders-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudieron cargar los pedidos.</p>
                </div>

                <div id="fd-orders-list" style="display:none;"></div>
                <div id="fd-orders-pagination" style="display:none;justify-content:center;align-items:center;gap:10px;margin-top:14px;"></div>
            </section>
