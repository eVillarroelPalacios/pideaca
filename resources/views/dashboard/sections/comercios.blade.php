            <section id="dash-comercios" class="dash-section" data-module-id="{{ $page->module_id }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                        <p id="fd-shops-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">Comercios con envío a tu domicilio</p>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <input type="text" id="fd-shops-search" placeholder="Buscar comercio..." oninput="fdShopsSearchDebounce()" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;min-width:200px;" />
                        <button type="button" onclick="loadComercios()" title="Actualizar comercios" aria-label="Actualizar comercios" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:600;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        </button>
                    </div>
                </div>

                <div id="fd-shops-tabs" style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
                    <button type="button" id="fd-shops-tab-favorites" onclick="fdShopsTab('favorites')" title="Mis favoritos" aria-label="Mis favoritos" style="background:#fff1eb;color:#D24C19;border:1px solid #D24C19;padding:7px 10px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"/></svg>
                        <span id="fd-shops-count-favorites" style="background:#D24C19;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
                    </button>
                    <button type="button" id="fd-shops-tab-all" onclick="fdShopsTab('all')" title="Todos los comercios" aria-label="Todos los comercios" style="background:#fff;color:#6b7280;border:1px solid #e5e7eb;padding:7px 10px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        <span id="fd-shops-count-all" style="background:#6b7280;color:#fff;border-radius:9999px;padding:0 6px;font-size:10px;font-weight:700;display:none;">0</span>
                    </button>
                </div>

                <div id="fd-shops-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
                    <p style="font-size:13px;color:#6b7280;margin:0;">Cargando comercios...</p>
                </div>

                <div id="fd-shops-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
                    <p id="fd-shops-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudieron cargar los comercios.</p>
                    <div id="fd-shops-message-action" style="margin-top:14px;"></div>
                </div>

                <div id="fd-shops-grid" style="display:none;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px;"></div>
            </section>

            <section id="fd-shop-overlay" class="dash-section" style="display:none;max-width:1100px;margin:0 auto;padding:0 20px 24px;">
                <div style="background:#fff;width:100%;max-width:880px;margin:16px auto 0;display:flex;flex-direction:column;border:1px solid #e5e7eb;max-height:calc(100vh - 200px);overflow:hidden;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;padding:14px 18px;border-bottom:1px solid #e5e7eb;">
                        <div style="min-width:0;">
                            <h3 id="fd-shop-title" style="margin:0;font-size:16px;font-weight:700;color:#0c2a4d;"></h3>
                            <p id="fd-shop-subtitle" style="margin:4px 0 0;font-size:12px;color:#6b7280;"></p>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;flex:none;">
                            <span id="fd-shop-cart-badge" style="display:none;align-items:center;gap:6px;background:#fff1eb;color:#D24C19;border:1px solid #f5c6ad;border-radius:9999px;padding:5px 10px;font-size:12px;font-weight:700;white-space:nowrap;"></span>
                            <button type="button" onclick="fdShopBack()" title="Volver a comercios" aria-label="Volver a comercios" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:7px 10px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                                Volver
                            </button>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;padding:10px 18px;border-bottom:1px solid #e5e7eb;background:#f8fafc;">
                        <label for="fd-shop-category" style="font-size:11px;font-weight:700;color:#0c2a4d;text-transform:uppercase;letter-spacing:.4px;flex:none;">Categoría</label>
                        <select id="fd-shop-category" onchange="fdShopCategoryFilter()" style="flex:1;max-width:340px;padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;background:#fff;outline:none;">
                            <option value="">Todas las categorías</option>
                        </select>
                    </div>
                    <div id="fd-shop-body" style="padding:16px 18px;overflow:auto;flex:1 1 auto;"></div>
                    <div id="fd-shop-cart" style="border-top:1px solid #e5e7eb;padding:14px 18px;background:#f8fafc;max-height:42vh;overflow:auto;"></div>
                    <div id="fd-shop-msg" style="display:none;font-size:12px;font-weight:600;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:4px;padding:9px 12px;margin:10px 18px 0;"></div>
                    <div style="padding:12px 18px 16px;">
                        <button type="button" id="fd-shop-submit" onclick="fdShopSubmit()" title="Confirmar compra" aria-label="Confirmar compra" style="display:none;width:100%;background:#D24C19;color:#fff;border:none;padding:12px 14px;border-radius:4px;cursor:pointer;align-items:center;justify-content:center;gap:8px;font-size:13px;font-weight:700;box-shadow:0 6px 16px rgba(210,76,25,.3);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span>Confirmar compra</span>
                        </button>
                    </div>
                </div>
            </section>
