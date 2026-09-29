            <section id="dash-mi-catalogo" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
                <div class="fd-cat-topbar">
                    <button type="button" onclick="fdCatToggleSidebar()" aria-label="Abrir menú" style="background:none;border:none;cursor:pointer;padding:4px;color:#0f172a;display:flex;align-items:center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                    <span style="font-size:12px;color:#64748b;">Mi Catálogo</span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    <span style="font-size:12px;color:#0f172a;font-weight:600;" id="fd-cat-breadcrumb">Mi catálogo</span>
                    <div style="flex:1;"></div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:12px;color:#0f172a;font-weight:600;">{{ $user->name }}</span>
                        <div style="width:28px;height:28px;border-radius:50%;background:#D24C19;color:white;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    </div>
                </div>

                <div class="fd-cat-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                        <p id="fd-catalog-subtitle" style="font-size:13px;color:#6b7280;margin:4px 0 0;">{{ optional($user->provider)->business_name ?: 'Tu comercio' }}</p>
                    </div>
                </div>

                <div class="fd-cat-layout">
                    <div id="fd-cat-overlay" onclick="fdCatToggleSidebar()"></div>
                    <aside id="fd-cat-sidebar">
                        <div class="fd-cat-menu-title">Organización</div>
                        <a href="#" class="sidebar-link active" data-cat-panel="catalogo" onclick="event.preventDefault();fdCatTab('catalogo')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            <span>Mi catálogo</span>
                        </a>
                        <a href="#" class="sidebar-link" data-cat-panel="productos" onclick="event.preventDefault();fdCatTab('productos')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                            <span>Productos</span>
                        </a>
                        <a href="#" class="sidebar-link" data-cat-panel="categorias" onclick="event.preventDefault();fdCatTab('categorias')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            <span>Categorías</span>
                        </a>
                        <a href="#" class="sidebar-link" data-cat-panel="unidades" onclick="event.preventDefault();fdCatTab('unidades')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14.5 12.5 12 15m2.5-6.5L12 9m-4.5 3.5L7 14"/></svg>
                            <span>Uds. de medida</span>
                        </a>
                    </aside>

                    <div class="fd-cat-body">
                        <div id="fd-cat-panel-catalogo" class="fd-cat-panel active">
                            <div class="fd-cat-panel-head">
                                <h3>Mi catálogo</h3>
                                <p>Vista previa de lo que ven tus clientes en la tienda.</p>
                            </div>

                <div id="fd-catalog-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
                    <p style="font-size:13px;color:#6b7280;margin:0;">Cargando catálogo...</p>
                </div>

                <div id="fd-catalog-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
                    <p id="fd-catalog-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudo cargar el catálogo.</p>
                </div>

                <div id="fd-catalog-content" style="display:none;"></div>
            </div>

            <div id="fd-cat-panel-productos" class="fd-cat-panel">
                <div class="fd-cat-panel-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                    <div>
                        <h3>Productos</h3>
                        <p>Alta y edición de productos, con sus variantes (Individual, Grande...) y agregados.</p>
                    </div>
                    <button type="button" onclick="openProductModal(null)" title="Agregar producto" aria-label="Agregar producto" style="background:#D24C19;color:#fff;border:none;padding:8px 10px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>
                <div id="fd-prod-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
                    <p style="font-size:13px;color:#6b7280;margin:0;">Cargando productos...</p>
                </div>
                <div id="fd-prod-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
                    <p id="fd-prod-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudieron cargar los productos.</p>
                </div>
                <div id="fd-prod-content" style="display:none;"></div>
            </div>

            <div id="fd-cat-panel-categorias" class="fd-cat-panel">
                <div class="fd-cat-panel-head">
                    <h3>Categorías</h3>
                    <p>Creá, renombrá y reordená las categorías del catálogo.</p>
                </div>
                <div id="fd-cats-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
                    <p style="font-size:13px;color:#6b7280;margin:0;">Cargando categorías...</p>
                </div>
                <div id="fd-cats-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
                    <p id="fd-cats-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudieron cargar las categorías.</p>
                </div>
                <div id="fd-cats-content" style="display:none;"></div>
            </div>

            <div id="fd-cat-panel-unidades" class="fd-cat-panel">
                <div class="fd-cat-panel-head">
                    <h3>Unidades de medida</h3>
                    <p>Referencia de las unidades disponibles y cuántos productos las usan.</p>
                </div>
                <div id="fd-units-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
                    <p style="font-size:13px;color:#6b7280;margin:0;">Cargando unidades de medida...</p>
                </div>
                <div id="fd-units-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
                    <p id="fd-units-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudieron cargar las unidades.</p>
                </div>
                <div id="fd-units-content" style="display:none;"></div>
            </div>
        </div>
    </div>
</section>
