            <section id="dash-usuarios" class="dash-section" style="display:none;max-width:1320px;margin:24px auto;padding:0 20px 40px;">
                <style>
                    .um-page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px;}
                    .um-page-title{font-size:20px;font-weight:700;color:#0c2a4d;margin:0;}
                    .um-page-sub{font-size:13px;color:#64748b;margin:4px 0 0;max-width:640px;}
                    .um-head-actions{display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;}
                    .um-with-sidebar{display:block;}
                    .um-with-sidebar.docked .um-main{margin-left:250px;}
                    #um-sidebar-slot{display:none;}
                    .um-with-sidebar.docked #um-sidebar-slot{display:block;}
                    @media (max-width:768px){
                        .um-with-sidebar.docked .um-main{margin-left:0;}
                    }
                    .um-outline-btn{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#D24C19;border:1px solid #D24C19;padding:9px 12px;border-radius:4px;cursor:pointer;font-size:12px;font-weight:600;transition:background .15s ease;}
                    .um-outline-btn:hover{background:#fff7ed;}
                    .um-limit-card{display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;background:#fff;border:1px solid #e5e7eb;padding:16px 20px;margin-bottom:18px;box-shadow:0 1px 2px rgba(15,23,42,.04);}
                    .um-limit-info{flex:1;min-width:240px;}
                    .um-limit-title{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#0c2a4d;}
                    .um-limit-meta{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:8px;font-size:11px;color:#64748b;}
                    .um-limit-count{font-weight:700;color:#D24C19;}
                    .um-progress{height:8px;background:#f1f5f9;border-radius:9999px;overflow:hidden;margin-top:8px;}
                    .um-progress-bar{display:block;height:100%;width:0;background:linear-gradient(90deg,#f59e0b,#D24C19);border-radius:9999px;transition:width .3s ease,background .3s ease;}
                    .um-add-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#D24C19;border:1px solid #D24C19;color:#fff;border-radius:4px;padding:10px 16px;font-size:12px;font-weight:700;cursor:pointer;transition:all .2s ease;white-space:nowrap;}
                    .um-add-btn:hover:not(:disabled){background:#b93f12;border-color:#b93f12;}
                    .um-add-btn:disabled{background:#e5e7eb;border-color:#e5e7eb;color:#94a3b8;cursor:not-allowed;}
                    .um-layout{display:grid;grid-template-columns:minmax(0,.95fr) minmax(0,1.05fr);gap:18px;align-items:start;}
                    .um-card{background:#fff;border:1px solid #e5e7eb;box-shadow:0 1px 2px rgba(15,23,42,.04);min-width:0;}
                    .um-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:16px 18px;border-bottom:1px solid #e5e7eb;}
                    .um-card-title{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#0c2a4d;margin:0;}
                    .um-card-sub{font-size:11px;color:#94a3b8;margin:4px 0 0;}
                    .um-count-badge{display:inline-flex;align-items:center;justify-content:center;min-width:26px;height:24px;padding:0 8px;border-radius:9999px;background:#f1f5f9;color:#475569;font-size:11px;font-weight:700;}
                    .um-table{width:100%;border-collapse:collapse;}
                    .um-table thead tr{background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);}
                    .um-table th{padding:10px 14px;text-align:left;font-size:10px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.5px;}
                    .um-table td{padding:12px 14px;font-size:13px;color:#0f172a;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
                    .um-table tr:last-child td{border-bottom:none;}
                    .um-user-row{cursor:pointer;transition:background .15s ease;}
                    .um-user-row:hover td{background:#fffaf6;}
                    .um-user-row.active td{background:#fff7ed;}
                    .um-user-row.active td:first-child{box-shadow:inset 3px 0 0 #D24C19;}
                    .um-user-name{font-weight:600;color:#0f172a;word-break:break-word;}
                    .um-user-email{font-size:11px;color:#64748b;margin-top:2px;word-break:break-word;}
                    .um-role{display:inline-flex;align-items:center;justify-content:center;padding:4px 10px;border-radius:9999px;background:#eff6ff;color:#1d4ed8;font-size:10px;font-weight:700;white-space:nowrap;}
                    .um-role.parent{background:#fff7ed;color:#D24C19;}
                    .um-actions{display:flex;flex-direction:row;align-items:center;justify-content:center;gap:6px;flex-wrap:nowrap;}
                    .um-action-btn{width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:#fff;border:1px solid #e2e8f0;cursor:pointer;transition:all .15s ease;flex-shrink:0;}
                    .um-inactive-badge{display:inline-block;margin-left:6px;padding:1px 6px;border-radius:999px;background:#fee2e2;color:#b91c1c;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.02em;vertical-align:middle;}
                    .um-action-btn.perm{color:#D24C19;border:1px solid #fdba74;}
                    .um-action-btn.perm:hover{background:#fff7ed;border-color:#D24C19;}
                    .um-action-btn.edit{color:#1d4ed8;border:1px solid #bfdbfe;}
                    .um-action-btn.edit:hover{background:#eff6ff;border-color:#1d4ed8;}
                    .um-action-btn.del{color:#dc2626;border:1px solid #fecaca;}
                    .um-action-btn.del:hover{background:#fef2f2;border-color:#dc2626;}
                    .um-empty{padding:34px 18px;text-align:center;color:#64748b;font-size:13px;background:#f8fafc;border:1px dashed #e2e8f0;}
                    .um-perm-body{padding:16px 18px;}
                    .um-perm-grid{display:grid;grid-template-columns:minmax(0,.82fr) minmax(0,1.18fr);gap:14px;}
                    .um-subcol{border:1px solid #e2e8f0;background:#f8fafc;min-width:0;}
                    .um-subcol-head{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border-bottom:1px solid #e2e8f0;background:#fff;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;}
                    .um-subcol-count{display:inline-flex;align-items:center;padding:2px 7px;border-radius:9999px;background:#f1f5f9;color:#64748b;font-size:10px;font-weight:700;text-transform:none;letter-spacing:0;}
                    .um-module-list{display:flex;flex-direction:column;gap:6px;padding:8px;max-height:360px;overflow:auto;}
                    .um-module-btn{display:flex;align-items:center;gap:9px;width:100%;text-align:left;background:#fff;border:1px solid #e2e8f0;padding:10px 11px;cursor:pointer;font-size:12px;font-weight:600;color:#334155;transition:all .15s ease;}
                    .um-module-btn:hover{border-color:#fdba74;background:#fffaf6;}
                    .um-module-btn.active{border-color:#D24C19;background:#fff7ed;color:#D24C19;box-shadow:inset 3px 0 0 #D24C19;}
                    .um-module-dot{width:8px;height:8px;border-radius:50%;background:#cbd5e1;flex-shrink:0;}
                    .um-module-btn.active .um-module-dot{background:#D24C19;}
                    .um-module-name{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
                    .um-module-count{margin-left:auto;padding:2px 7px;border-radius:9999px;background:#f1f5f9;color:#64748b;font-size:10px;font-weight:700;flex-shrink:0;}
                    .um-module-btn.active .um-module-count{background:#D24C19;color:#fff;}
                    .um-pages-list{display:flex;flex-direction:column;gap:6px;padding:10px;max-height:360px;overflow:auto;background:#fff;}
                    .um-page-item{display:flex;align-items:flex-start;gap:9px;padding:9px 10px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;font-size:12px;color:#334155;transition:all .15s ease;}
                    .um-page-item:hover{background:#fffaf6;border-color:#fdba74;}
                    .um-page-item.checked{background:#fff7ed;border-color:#D24C19;color:#0c2a4d;}
                    .um-page-item input{width:15px;height:15px;margin-top:1px;accent-color:#D24C19;cursor:pointer;flex-shrink:0;}
                    .um-page-item input:disabled{cursor:not-allowed;}
                    .um-page-text{min-width:0;}
                    .um-page-name{display:block;font-weight:600;}
                    .um-page-url{display:block;font-size:10px;color:#94a3b8;margin-top:2px;word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;}
                    .um-parent-note{margin:12px 0 0;padding:8px 10px;border:1px solid #fde68a;background:#fffbeb;color:#b45309;font-size:11px;line-height:1.5;}
                    .um-footer{display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap;padding:14px 18px;border-top:1px solid #e5e7eb;background:#f8fafc;}
                    .um-msg{margin-right:auto;font-size:12px;font-weight:600;}
                    .um-msg.ok{color:#047857;}
                    .um-msg.err{color:#dc2626;}
                    .um-footer .um-cancel-btn{background:#fff;border:1px solid #cbd5e1;color:#334155;border-radius:4px;padding:8px 16px;font-size:12px;font-weight:600;cursor:pointer;}
                    .um-footer .um-cancel-btn:hover{background:#f1f5f9;}
                    .um-footer .um-save-btn{background:#D24C19;border:1px solid #D24C19;color:#fff;border-radius:4px;padding:8px 18px;font-size:12px;font-weight:700;cursor:pointer;transition:all .2s ease;}
                    .um-footer .um-save-btn:hover:not(:disabled){background:#b93f12;border-color:#b93f12;}
                    .um-footer .um-save-btn:disabled{background:#e5e7eb;border-color:#e5e7eb;color:#94a3b8;cursor:not-allowed;}
                    .um-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
                    .um-form-grid .full{grid-column:1 / -1;}
                    .um-form-grid input:disabled{background-color:#f3f4f6;color:#6b7280;cursor:not-allowed;}
                    @media (max-width:960px){
                        .um-layout{grid-template-columns:1fr;}
                        .um-perm-grid{grid-template-columns:1fr;}
                        .um-limit-card{align-items:flex-start;}
                    }
                </style>

                <div class="um-with-sidebar" id="um-with-sidebar">
                    <div id="um-sidebar-slot"></div>
                    <div class="um-main">
                <div class="um-page-head">
                    <div>
                        <h2 class="um-page-title">Gestión de Usuarios</h2>
                        <p class="um-page-sub">Administrá los usuarios de tu cuenta y asigná permisos de páginas de los módulos que aparecen en la barra superior.</p>
                    </div>
                    <div class="um-head-actions">
                        <button type="button" onclick="openUserSelectModal('all')" title="Buscar y seleccionar usuario" class="um-outline-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                            Buscar Usuario
                        </button>
                        <button type="button" onclick="openTypeUserPagesModal()" title="Configurar páginas por tipo de usuario" class="um-outline-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m-6-6h.008v.008H8.25v-6zm9 0h.008v.008H16.5v-6zm-9 3.75h.008v.008H8.25v-.008zm9 0h.008v.008H16.5v-.008z"/></svg>
                            Páginas por tipo
                        </button>
                    </div>
                </div>

                <div id="user-panel-cuenta" class="user-panel active" style="display:block;padding:0;">
                    <div class="um-limit-card">
                        <div class="um-limit-info">
                            <div class="um-limit-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D24C19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                Usuarios de la cuenta
                            </div>
                            <div class="um-limit-meta">
                                <span id="um-limit-text" class="um-limit-count">0 de 3 creados</span>
                                <span id="um-limit-hint">Podés crear hasta 3 usuarios adicionales.</span>
                            </div>
                            <div class="um-progress" role="progressbar" aria-valuemin="0" aria-valuemax="3" aria-valuenow="0" id="um-progress">
                                <span id="um-limit-bar" class="um-progress-bar"></span>
                            </div>
                        </div>
                        <button type="button" id="user-create-btn" onclick="openUserModal(null)" class="um-add-btn" title="Agregar nuevo usuario">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Agregar nuevo usuario
                        </button>
                    </div>

                    <div class="um-layout">
                        <div class="um-card">
                            <div class="um-card-head">
                                <div>
                                    <h3 class="um-card-title">Usuarios</h3>
                                    <p class="um-card-sub">Seleccioná un usuario para editar sus permisos.</p>
                                </div>
                                <span id="um-users-count" class="um-count-badge">0</span>
                            </div>
                            <div style="overflow-x:auto;">
                                <table class="um-table">
                                    <thead>
                                        <tr>
                                            <th style="width:44%;">Nombre / Email</th>
                                            <th style="width:18%;text-align:center;">Rol</th>
                                            <th style="width:38%;text-align:right;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="um-users-tbody"></tbody>
                                </table>
                                <div id="um-users-empty" class="um-empty" style="display:none;">No hay usuarios creados en esta cuenta.</div>
                            </div>
                        </div>

                        <div class="um-card" id="um-permissions-panel">
                            <div class="um-card-head">
                                <div>
                                    <h3 class="um-card-title">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D24C19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        Asignar Permisos de Páginas
                                    </h3>
                                    <p class="um-card-sub" id="um-selected-user-label">Seleccioná un usuario de la lista.</p>
                                </div>
                                <span id="um-selected-user-role" class="um-role" style="display:none;"></span>
                            </div>

                            <div id="um-permissions-empty" class="um-empty" style="margin:16px;">Seleccioná un usuario para asignar permisos.</div>

                            <div id="um-permissions-body" style="display:none;">
                                <div class="um-perm-body">
                                    <div class="um-perm-grid">
                                        <div class="um-subcol">
                                            <div class="um-subcol-head">
                                                <span>Módulos de la Barra Superior</span>
                                            </div>
                                            <div id="um-modules-list" class="um-module-list"></div>
                                        </div>
                                        <div class="um-subcol">
                                            <div class="um-subcol-head">
                                                <span>Páginas Permitidas</span>
                                                <span id="um-selection-summary" class="um-subcol-count">0 seleccionadas</span>
                                            </div>
                                            <div id="um-pages-list" class="um-pages-list"></div>
                                        </div>
                                    </div>
                                    <p id="um-parent-note" class="um-parent-note" style="display:none;"></p>
                                </div>
                                <div class="um-footer">
                                    <span id="um-permissions-msg" class="um-msg"></span>
                                    <button type="button" class="um-cancel-btn" onclick="cancelUserPermissions()">Cancelar</button>
                                    <button type="button" class="um-save-btn" id="um-save-btn" onclick="saveUserPermissions()">Guardar Cambios</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="um-edit-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:415;align-items:center;justify-content:center;">
                        <div style="background:white;width:100%;max-width:640px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);max-height:90vh;overflow:auto;position:relative;">
                            <button type="button" onclick="closeUserEditModal()" title="Cerrar" aria-label="Cerrar" style="position:absolute;top:10px;right:12px;background:none;border:none;cursor:pointer;font-size:20px;line-height:1;color:#6b7280;z-index:2;">&times;</button>
                        <div class="um-card" id="um-edit-panel" style="border:none;box-shadow:none;">
                            <div class="um-card-head">
                                <div>
                                    <h3 class="um-card-title">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D24C19" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                        Editar Datos del Usuario
                                    </h3>
                                    <p class="um-card-sub" id="um-edit-user-label">Seleccioná un usuario de la lista.</p>
                                </div>
                            </div>

                            <div id="um-edit-empty" class="um-empty" style="margin:16px;">Seleccioná un usuario para editar sus datos.</div>

                            <div id="um-edit-body" style="display:none;">
                                <div class="um-perm-body">
                                    <div class="um-form-grid">
                                        <div>
                                            <label class="field-label" style="font-size:10px;">Nombre</label>
                                            <input type="text" id="um-edit-name" class="field-input" style="border-radius:4px;" placeholder="Nombre completo" />
                                        </div>
                                        <div>
                                            <label class="field-label" style="font-size:10px;">Email</label>
                                            <input type="email" id="um-edit-email" class="field-input" style="border-radius:4px;" disabled />
                                        </div>
                                        <div>
                                            <label class="field-label" style="font-size:10px;">País</label>
                                            <select id="um-edit-country" class="field-input" style="border-radius:4px;" onchange="umLoadEditProvinces(this.value)">
                                                <option value="">-- Seleccionar --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="field-label" style="font-size:10px;">Provincia / Región</label>
                                            <select id="um-edit-province" class="field-input" style="border-radius:4px;">
                                                <option value="">-- Seleccionar país primero --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="field-label" style="font-size:10px;">Calle</label>
                                            <input type="text" id="um-edit-street" class="field-input" style="border-radius:4px;" placeholder="Av. San Martín" />
                                        </div>
                                        <div>
                                            <label class="field-label" style="font-size:10px;">Número</label>
                                            <input type="text" id="um-edit-number" class="field-input" style="border-radius:4px;" placeholder="1234" />
                                        </div>
                                        <div>
                                            <label class="field-label" style="font-size:10px;">Estado</label>
                                            <select id="um-edit-status" class="field-input" style="border-radius:4px;">
                                                <option value="">-- Seleccionar --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="field-label" style="font-size:10px;">Rol de la cuenta principal</label>
                                            <div id="um-edit-role-label" class="field-input" style="border-radius:4px;background:#f3f4f6;color:#374151;display:flex;align-items:center;cursor:not-allowed;" aria-readonly="true" title="El rol lo define el usuario principal de la cuenta">—</div>
                                            <input type="hidden" id="um-edit-type" value="" />
                                            <p style="font-size:10px;color:#6b7280;margin:4px 0 0;">El rol lo define el usuario principal de la cuenta.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="um-footer">
                                    <span id="um-edit-msg" class="um-msg"></span>
                                    <button type="button" class="um-cancel-btn" onclick="umCancelUserData()">Cancelar</button>
                                    <button type="button" class="um-save-btn" id="um-edit-save-btn" onclick="umSaveUserData()">Guardar Cambios</button>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                    </div>
                </div>
            </section>
