            <section id="dash-sub-grupos" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                    <button onclick="openSubGroupModal()" class="btn-orange" style="background:#D24C19;color:white;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Nuevo Sub Grupo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
                    <input type="text" id="subgroup-search" placeholder="Buscar sub grupo..." oninput="filterSubGroups()" style="flex:1;min-width:180px;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;" />
                    <select id="subgroup-group-filter" onchange="filterSubGroups()" title="Filtrar por grupo" style="flex:0 1 260px;min-width:200px;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;">
                        <option value="">Todos los grupos</option>
                    </select>
                </div>

                <div style="background:white;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;">
                    <div id="subgroups-loading" style="padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">Cargando sub grupos...</p>
                    </div>
                    <div id="subgroups-empty" style="display:none;padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">No se encontraron sub grupos.</p>
                    </div>
                    <div id="subgroups-table-wrap" style="display:none;overflow-x:auto;">
                    <table id="subgroups-table" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Descripción</th>
                                <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="subgroups-tbody"></tbody>
                    </table>
                    </div>
                        <div id="subgroup-pagination" style="display:none;justify-content:center;align-items:center;width:100%;min-height:48px;padding:8px;border-top:1px solid #e5e7eb;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <button id="subgroup-prev-page" class="pagination-nav-btn" onclick="subGroupPage(1)" style="background:white;border:1px solid #d1d5db;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;color:#374151;">&#10094;</button>
                                <div id="subgroup-page-dots" style="display:flex;gap:6px;"></div>
                                <button id="subgroup-next-page" class="pagination-nav-btn" onclick="subGroupPage(2)" style="background:white;border:1px solid #d1d5db;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;color:#374151;">&#10095;</button>
                            </div>
                        </div>
                </div>
            </section>

            <div id="subgroup-modal-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:420px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 id="subgroup-modal-title" style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Nuevo Sub Grupo</h3>
                        <button onclick="closeSubGroupModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <form id="subgroup-form" onsubmit="submitSubGroup(event)" style="padding:20px;">
                        @csrf
                        <input type="hidden" id="subgroup-id" value="" />
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Descripción <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="subgroup-description" maxlength="255" placeholder="Nombre del sub grupo" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            <p id="subgroup-description-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Grupo <span style="color:#dc2626;">*</span></label>
                            <select id="subgroup-group-id" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'">
                                <option value="">-- Seleccionar grupo --</option>
                            </select>
                            <p id="subgroup-group-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                            <button type="button" onclick="closeSubGroupModal()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                            <button type="submit" id="subgroup-submit-btn" class="btn-orange" style="padding:8px 16px;background:#D24C19;border:1px solid #D24C19;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="subgroup-delete-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:380px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Confirmar Eliminación</h3>
                    </div>
                    <div style="padding:20px;">
                        <p style="font-size:13px;color:#374151;margin:0;">¿Estás seguro de eliminar el sub grupo <strong id="subgroup-delete-name"></strong>?</p>
                        <p id="subgroup-delete-warning" style="font-size:11px;color:#dc2626;margin:8px 0 0;display:none;"></p>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
                        <button onclick="closeSubGroupDelete()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                        <button id="subgroup-delete-btn" onclick="confirmDeleteSubGroup()" style="padding:8px 16px;background:#dc2626;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Eliminar</button>
                    </div>
                </div>
            </div>