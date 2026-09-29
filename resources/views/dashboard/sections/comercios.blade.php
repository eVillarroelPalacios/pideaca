            <section id="dash-comercios" class="dash-section" data-module-id="{{ $page->module_id }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                        <p id="fd-shops-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">Comercios con envío a tu domicilio</p>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <input type="text" id="fd-shops-search" placeholder="Buscar comercio..." oninput="fdShopsSearchDebounce()" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;min-width:200px;" />
                        <button type="button" onclick="loadComercios()" title="Actualizar comercios" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:600;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            Actualizar
                        </button>
                    </div>
                </div>

                <div id="fd-shops-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
                    <p style="font-size:13px;color:#6b7280;margin:0;">Cargando comercios...</p>
                </div>

                <div id="fd-shops-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
                    <p id="fd-shops-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudieron cargar los comercios.</p>
                </div>

                <div id="fd-shops-grid" style="display:none;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px;"></div>
            </section>

            <div id="fd-shop-overlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:300;align-items:center;justify-content:center;">
                <div style="background:#fff;width:100%;max-width:880px;max-height:90vh;margin:16px;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.35);">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;padding:14px 18px;border-bottom:1px solid #e5e7eb;">
                        <div style="min-width:0;">
                            <h3 id="fd-shop-title" style="margin:0;font-size:16px;font-weight:700;color:#0c2a4d;"></h3>
                            <p id="fd-shop-subtitle" style="margin:4px 0 0;font-size:12px;color:#6b7280;"></p>
                        </div>
                        <button type="button" onclick="closeFdShop()" title="Cerrar" style="background:none;border:none;font-size:26px;line-height:1;color:#64748b;cursor:pointer;padding:0 4px;">&times;</button>
                    </div>
                    <div id="fd-shop-body" style="padding:16px 18px;overflow:auto;flex:1 1 auto;"></div>
                    <div id="fd-shop-cart" style="border-top:1px solid #e5e7eb;padding:14px 18px;background:#f8fafc;"></div>
                </div>
            </div>
