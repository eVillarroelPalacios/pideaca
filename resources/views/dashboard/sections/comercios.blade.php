            <style>
                /* Comercios ocupa el ancho completo: sin barra lateral y contenido centrado */
                body:has(#dash-comercios[style*="display: block"]) #profile-sidebar,
                body:has(#fd-shop-overlay[style*="display: block"]) #profile-sidebar,
                body:has(#dash-comercios[style*="display: block"]) #sidebar-toggle-btn,
                body:has(#fd-shop-overlay[style*="display: block"]) #sidebar-toggle-btn { display: none !important; }

                body:has(#dash-comercios[style*="display: block"]) #profile-content,
                body:has(#fd-shop-overlay[style*="display: block"]) #profile-content { margin-left: 0 !important; }

                #dash-comercios { width: 95%; max-width: none; margin: 24px auto; padding: 0 1rem; box-sizing: border-box; }

                /* Detalle del comercio: 90% de ancho (5% de margen por lado) */
                #fd-shop-overlay { width: 90%; max-width: none; margin: 0 auto; padding: 0 0 24px; box-sizing: border-box; }
                @media (max-width: 768px) { #fd-shop-overlay { width: 96%; } }

                /* Sin scroll interno ni flechas: el scroll lo maneja solo el viewport */
                #fd-shop-overlay > div { max-height: none !important; overflow: visible !important; }
                #fd-shop-body { overflow: visible !important; }
                #fd-shop-cart { max-height: none !important; overflow: visible !important; }
                #fd-shop-body::-webkit-scrollbar-button:vertical,
                #fd-shop-cart::-webkit-scrollbar-button:vertical { display: none !important; height: 0 !important; }

                #fd-shops-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1.5rem; }
                @media (max-width: 1023px) { #fd-shops-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
                @media (max-width: 639px) { #fd-shops-grid { grid-template-columns: 1fr; } }

                /* Cabecera de tarjeta de comercio: imagen publicitaria centrada
                   dentro de un circulo, con fallback de iniciales del mismo
                   tamano para que todas las tarjetas queden alineadas. */
                #fd-shops-grid .fd-shop-head { height: 160px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; margin: -16px -16px 0; }
                #fd-shops-grid .fd-shop-circle { width: 104px; height: 104px; flex: none; border-radius: 50%; border: 3px solid #ffffff; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1); }
                #fd-shops-grid .fd-shop-photo { object-fit: cover; display: block; background: #ffffff; }
                #fd-shops-grid .fd-shop-initials { display: flex; align-items: center; justify-content: center; background: #fff1eb; color: #D24C19; font-size: 38px; font-weight: 700; }
            </style>

            <section id="dash-comercios" class="dash-section" data-module-id="{{ $page->module_id }}" style="display:none;width:1550;max-width:none;margin:10px auto;padding:0 1rem;box-sizing:border-box;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                        <p id="fd-shops-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">Comercios con envío a tu domicilio</p>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <input type="text" id="fd-shops-search" placeholder="Buscar comercio..." oninput="fdShopsSearchDebounce()" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;background:#fff;min-width:200px;" />
                        <button type="button" id="fd-shops-filter-btn" onclick="fdShopsFilterOpen()" title="Filtrar por ubicación y distancia" aria-label="Filtrar por ubicación y distancia" style="position:relative;background:#fff;color:#0c2a4d;border:1px solid #d1d5db;border-radius:4px;padding:7px 9px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            <span id="fd-shops-filter-badge" style="display:none;position:absolute;top:-5px;right:-5px;width:16px;height:16px;border-radius:50%;background:#D24C19;color:#fff;font-size:9px;font-weight:700;align-items:center;justify-content:center;line-height:1;border:2px solid #fff;">1</span>
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

                <div id="fd-shops-grid" style="display:none;"></div>
            </section>

            <!-- Modal: filtro de ubicación / distancia -->
            <div id="fd-shops-filter-modal" role="dialog" aria-modal="true" aria-labelledby="fd-shops-filter-title"
                 style="display:none;position:fixed;inset:0;z-index:120;background:rgba(7,26,48,.55);backdrop-filter:blur(2px);align-items:center;justify-content:center;padding:16px;">
                <div style="background:#fff;width:100%;max-width:430px;border-radius:12px;box-shadow:0 24px 60px rgba(7,26,48,.35);overflow:hidden;">
                    <div style="background:#0c2a4d;color:#fff;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                        <div>
                            <h3 id="fd-shops-filter-title" style="margin:0;font-size:15px;font-weight:700;">Filtrar comercios</h3>
                            <p style="margin:3px 0 0;font-size:11.5px;color:#b9c7db;">Ubicación y distancia</p>
                        </div>
                        <button type="button" onclick="fdShopsFilterClose()" aria-label="Cerrar" style="background:rgba(255,255,255,.12);color:#fff;border:none;width:32px;height:32px;border-radius:8px;font-size:18px;line-height:1;cursor:pointer;">×</button>
                    </div>

                    <div style="padding:18px;">
                        <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid #e5e7eb;border-radius:9px;cursor:pointer;margin-bottom:10px;">
                            <input type="radio" name="fd-shops-filter-mode" id="fd-shops-filter-mode-distance" value="distance" onchange="fdShopsFilterMode()" style="margin-top:2px;accent-color:#D24C19;">
                            <span>
                                <strong style="display:block;font-size:13.5px;color:#0c2a4d;">Por distancia</strong>
                                <span style="display:block;font-size:12px;color:#6b7280;">Mostrar comercios dentro del radio elegido.</span>
                            </span>
                        </label>

                        <div id="fd-shops-filter-slider-wrap" style="padding:0 4px 14px 34px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#374151;margin-bottom:6px;">
                                <span>Radio</span>
                                <strong id="fd-shops-filter-distance-label" style="color:#D24C19;">10 km</strong>
                            </div>
                            <input type="range" id="fd-shops-filter-range" min="1" max="50" value="10" step="1" disabled oninput="fdShopsFilterDistanceLabel()" style="width:100%;accent-color:#D24C19;cursor:pointer;">
                            <div style="display:flex;justify-content:space-between;font-size:10.5px;color:#9ca3af;margin-top:2px;">
                                <span>1 km</span><span>50+ km</span>
                            </div>
                        </div>

                        <label style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid #e5e7eb;border-radius:9px;cursor:pointer;margin-bottom:14px;">
                            <input type="radio" name="fd-shops-filter-mode" id="fd-shops-filter-mode-all" value="all" checked onchange="fdShopsFilterMode()" style="margin-top:2px;accent-color:#D24C19;">
                            <span>
                                <strong style="display:block;font-size:13.5px;color:#0c2a4d;">Buscar en todas partes</strong>
                                <span style="display:block;font-size:12px;color:#6b7280;">Sin límite de distancia.</span>
                            </span>
                        </label>

                        <label for="fd-shops-filter-location" style="display:block;font-size:11.5px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Ubicación de referencia (opcional)</label>
                        <div style="display:flex;gap:8px;">
                            <input type="text" id="fd-shops-filter-location" placeholder="Ej. Av. Siempre Viva 123" autocomplete="off" style="flex:1;min-width:0;padding:9px 10px;border:1px solid #d1d5db;border-radius:7px;font-size:12.5px;outline:none;background:#fff;">
                            <button type="button" id="fd-shops-filter-geo" onclick="fdShopsFilterLocate()" title="Usar mi ubicación actual" aria-label="Usar mi ubicación actual" style="flex:none;background:#fff1eb;color:#D24C19;border:1px solid #f5c6ad;border-radius:7px;padding:0 11px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
                            </button>
                        </div>
                        <p id="fd-shops-filter-geo-status" style="margin:6px 0 0;font-size:11px;color:#6b7280;"></p>
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:8px;padding:14px 18px;border-top:1px solid #e5e7eb;background:#f8fafc;">
                        <button type="button" onclick="fdShopsFilterClose()" style="background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:7px;padding:9px 16px;font-size:12.5px;font-weight:600;cursor:pointer;">Cancelar</button>
                        <button type="button" onclick="fdShopsFilterApply()" style="background:#D24C19;color:#fff;border:none;border-radius:7px;padding:9px 18px;font-size:12.5px;font-weight:700;cursor:pointer;box-shadow:0 6px 16px rgba(210,76,25,.3);">Aplicar</button>
                    </div>
                </div>
            </div>

            <section id="fd-shop-overlay" class="dash-section" style="display:none;">
                <div style="background:#fff;width:100%;max-width:none;margin:16px 0 0;display:flex;flex-direction:column;border:1px solid #e5e7eb;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;padding:14px 18px;border-bottom:1px solid #e5e7eb;">
                        <div style="min-width:0;">
                            <h3 id="fd-shop-title" style="margin:0;font-size:16px;font-weight:700;color:#0c2a4d;"></h3>
                            <p id="fd-shop-subtitle" style="margin:4px 0 0;font-size:12px;color:#6b7280;"></p>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;flex:none;">
                            <span id="fd-shop-cart-badge" style="display:none;align-items:center;gap:6px;background:#fff1eb;color:#D24C19;border:1px solid #f5c6ad;border-radius:9999px;padding:5px 10px;font-size:12px;font-weight:700;white-space:nowrap;"></span>
                            <button type="button" onclick="fdShopBack()" title="Volver a comercios" aria-label="Volver a comercios" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:8px 10px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                            </button>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;padding:10px 18px;border-bottom:1px solid #e5e7eb;background:#f8fafc;">
                        <label for="fd-shop-category" style="font-size:11px;font-weight:700;color:#0c2a4d;text-transform:uppercase;letter-spacing:.4px;flex:none;">Categoría</label>
                        <select id="fd-shop-category" onchange="fdShopCategoryFilter()" style="flex:1;max-width:340px;padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;background:#fff;outline:none;">
                            <option value="">Todas las categorías</option>
                        </select>
                    </div>
                    <div id="fd-shop-body" style="padding:16px 18px;flex:1 1 auto;"></div>
                    <div id="fd-shop-cart" style="border-top:1px solid #e5e7eb;padding:14px 18px;background:#f8fafc;"></div>
                    <div id="fd-shop-msg" style="display:none;font-size:12px;font-weight:600;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:4px;padding:9px 12px;margin:10px 18px 0;"></div>
                    <div id="fd-shop-delivery" style="display:none;padding:12px 18px 0;"></div>
                    <div style="padding:12px 18px 16px;">
                        <button type="button" id="fd-shop-submit" onclick="fdShopSubmit()" title="Confirmar compra" aria-label="Confirmar compra" style="display:none;width:100%;background:#D24C19;color:#fff;border:none;padding:12px 14px;border-radius:4px;cursor:pointer;align-items:center;justify-content:center;gap:8px;font-size:13px;font-weight:700;box-shadow:0 6px 16px rgba(210,76,25,.3);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <span>Confirmar compra</span>
                        </button>
                    </div>
                </div>
            </section>
