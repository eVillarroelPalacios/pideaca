            <section id="dash-tipo-monedas" class="dash-section" style="display:none;max-width:1000px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                        <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Monedas de la plataforma (una por país): símbolo, posición, decimales y separadores</p>
                    </div>
                    <button onclick="openCurrencyModal()" class="btn-orange" style="background:#D24C19;color:white;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Nueva Moneda">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:12px;">
                    <input type="text" id="cur-search" placeholder="Buscar moneda..." oninput="filterCurrencies()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;" />
                </div>

                <div style="background:white;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;">
                    <div id="cur-loading" style="padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">Cargando monedas...</p>
                    </div>
                    <div id="cur-empty" style="display:none;padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">No se encontraron monedas.</p>
                    </div>
                    <div id="cur-table-wrap" style="display:none;overflow-x:auto;">
                    <table id="cur-table" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">País</th>
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Código</th>
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Moneda</th>
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Símbolo</th>
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Formato</th>
                                <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Estado</th>
                                <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cur-tbody"></tbody>
                    </table>
                    </div>
                </div>
            </section>

            <div id="cur-modal-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:520px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);max-height:90vh;overflow-y:auto;">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 id="cur-modal-title" style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Nueva Moneda</h3>
                        <button onclick="closeCurrencyModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <form id="cur-form" onsubmit="submitCurrency(event)" style="padding:20px;">
                        @csrf
                        <input type="hidden" id="cur-id" value="" />
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">País <span style="color:#dc2626;">*</span></label>
                            <select id="cur-country" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'">
                                <option value="">Cargando países...</option>
                            </select>
                        </div>
                        <div style="display:flex;gap:12px;margin-bottom:16px;">
                            <div style="flex:1;">
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Código ISO <span style="color:#dc2626;">*</span></label>
                                <input type="text" id="cur-code" maxlength="3" placeholder="Ej: ARS" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;text-transform:uppercase;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            </div>
                            <div style="flex:2;">
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Nombre <span style="color:#dc2626;">*</span></label>
                                <input type="text" id="cur-name" maxlength="255" placeholder="Ej: Peso argentino" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;margin-bottom:16px;">
                            <div style="flex:1;">
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Símbolo <span style="color:#dc2626;">*</span></label>
                                <input type="text" id="cur-symbol" maxlength="8" placeholder="Ej: $" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            </div>
                            <div style="flex:1;">
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Posición <span style="color:#dc2626;">*</span></label>
                                <select id="cur-position" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'">
                                    <option value="before">Antes del monto</option>
                                    <option value="after">Después del monto</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" id="cur-space" checked style="width:15px;height:15px;accent-color:#D24C19;cursor:pointer;" />
                            <label for="cur-space" style="font-size:12px;font-weight:600;color:#374151;cursor:pointer;">Espacio entre símbolo y monto</label>
                        </div>
                        <div style="display:flex;gap:12px;margin-bottom:16px;">
                            <div style="flex:1;">
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Decimales <span style="color:#dc2626;">*</span></label>
                                <input type="number" id="cur-decimals" min="0" max="4" step="1" value="2" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            </div>
                            <div style="flex:1;">
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Separador decimal <span style="color:#dc2626;">*</span></label>
                                <select id="cur-decimal-sep" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'">
                                    <option value=",">Coma ( , )</option>
                                    <option value=".">Punto ( . )</option>
                                </select>
                            </div>
                            <div style="flex:1;">
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Separador de miles <span style="color:#dc2626;">*</span></label>
                                <select id="cur-thousand-sep" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'">
                                    <option value=".">Punto ( . )</option>
                                    <option value=",">Coma ( , )</option>
                                    <option value=" ">Espacio</option>
                                </select>
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;margin-bottom:16px;">
                            <div style="flex:2;">
                                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Locale <span style="color:#dc2626;">*</span></label>
                                <input type="text" id="cur-locale" maxlength="12" placeholder="Ej: es-AR" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            </div>
                            <div style="flex:1;display:flex;align-items:center;gap:8px;padding-top:18px;">
                                <input type="checkbox" id="cur-active" checked style="width:15px;height:15px;accent-color:#D24C19;cursor:pointer;" />
                                <label for="cur-active" style="font-size:12px;font-weight:600;color:#374151;cursor:pointer;">Activa</label>
                            </div>
                        </div>
                        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:4px;padding:10px 12px;margin-bottom:16px;">
                            <span style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Vista previa</span>
                            <div id="cur-preview" style="font-size:16px;font-weight:700;color:#0c2a4d;margin-top:2px;">$ 1.234,56</div>
                        </div>
                        <p id="cur-error" style="font-size:11px;color:#dc2626;margin:0 0 12px;display:none;"></p>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                            <button type="button" onclick="closeCurrencyModal()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                            <button type="submit" id="cur-submit-btn" class="btn-orange" style="padding:8px 16px;background:#D24C19;border:1px solid #D24C19;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="cur-delete-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:380px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Confirmar Eliminación</h3>
                    </div>
                    <div style="padding:20px;">
                        <p style="font-size:13px;color:#374151;margin:0;">¿Estás seguro de eliminar la moneda <strong id="cur-delete-name"></strong>?</p>
                        <p id="cur-delete-warning" style="font-size:11px;color:#dc2626;margin:8px 0 0;display:none;"></p>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
                        <button onclick="closeCurrencyDelete()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                        <button id="cur-delete-btn" onclick="confirmDeleteCurrency()" style="padding:8px 16px;background:#dc2626;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Eliminar</button>
                    </div>
                </div>
            </div>
