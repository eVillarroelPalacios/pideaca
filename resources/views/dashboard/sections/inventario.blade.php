            <section id="dash-inventario" class="dash-section" data-provider-id="{{ $user->provider?->id ?? '' }}" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                        <p id="fd-inventory-summary" style="font-size:13px;color:#6b7280;margin:4px 0 0;">{{ optional($user->provider)->business_name ?: 'Tu comercio' }}</p>
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                        <label for="fd-inventory-enabled" style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#374151;cursor:pointer;">
                            <input type="checkbox" id="fd-inventory-enabled" onchange="toggleInventoryControl()" style="cursor:pointer;" />
                            Control de inventario
                        </label>
                    </div>
                </div>

                <div id="fd-inventory-loading" style="display:none;background:white;border:1px solid #e5e7eb;padding:40px;text-align:center;">
                    <p style="font-size:13px;color:#6b7280;margin:0;">Cargando inventario...</p>
                </div>

                <div id="fd-inventory-message" style="display:none;background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
                    <p id="fd-inventory-message-text" style="font-size:14px;color:#6b7280;margin:0;white-space:pre-line;">No se pudo cargar el inventario.</p>
                </div>

                <div id="fd-inventory-content" style="display:none;"></div>
            </section>

            <div id="fd-inventory-stock-overlay" style="display:none;position:fixed;inset:0;z-index:320;background:rgba(15,23,42,0.6);align-items:center;justify-content:center;">
                <div style="background:#fff;padding:24px;width:100%;max-width:460px;box-shadow:0 24px 60px rgba(15,23,42,0.2);box-sizing:border-box;">
                    <h3 style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 16px;">Stock del producto</h3>
                    <input type="hidden" id="fd-inventory-stock-id" />

                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#374151;margin-bottom:16px;cursor:pointer;">
                        <input type="checkbox" id="fd-inventory-stock-track" onchange="fdInvStockTrackChanged()" style="cursor:pointer;" />
                        Controlar stock de este producto
                    </label>

                    <div id="fd-inventory-stock-fields" style="display:none;">
                        <div style="margin-bottom:12px;">
                            <label class="field-label" for="fd-inventory-stock-current">Stock actual</label>
                            <input type="number" step="0.001" id="fd-inventory-stock-current" class="field-input" placeholder="0" />
                        </div>
                        <div style="margin-bottom:12px;">
                            <label class="field-label" for="fd-inventory-stock-min">Stock mínimo</label>
                            <input type="number" step="0.001" min="0" id="fd-inventory-stock-min" class="field-input" placeholder="0" />
                        </div>
                        <div style="margin-bottom:12px;">
                            <label class="field-label" for="fd-inventory-stock-unit">Unidad de medida</label>
                            <select id="fd-inventory-stock-unit" class="field-input"></select>
                        </div>
                        <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#475569;margin-bottom:12px;cursor:pointer;">
                            <input type="checkbox" id="fd-inventory-stock-negative" style="cursor:pointer;" />
                            Permitir stock negativo
                        </label>
                        <div>
                            <label class="field-label" for="fd-inventory-stock-notes">Motivo (opcional)</label>
                            <input type="text" id="fd-inventory-stock-notes" class="field-input" placeholder="Ej: conteo de depósito" />
                        </div>
                    </div>

                    <div style="margin-top:20px;text-align:right;">
                        <span class="save-msg err" id="fd-inventory-stock-error" style="display:none;margin-right:8px;"></span>
                        <button type="button" onclick="closeInventoryStockModal()" title="Cancelar" aria-label="Cancelar" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;margin-right:8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                        <button type="button" onclick="saveInventoryStock()" title="Guardar" aria-label="Guardar" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </button>
                    </div>
                </div>
            </div>
