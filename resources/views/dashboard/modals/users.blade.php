            <div id="typeuser-pages-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:415;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:720px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);max-height:90vh;display:flex;flex-direction:column;box-sizing:border-box;">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Páginas por tipos usuarios</h3>
                        <button onclick="closeTypeUserPagesModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <div style="padding:16px 20px 0;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Tipo de usuario</label>
                            <select id="typeuser-pages-select" onchange="loadTypeUserPagesForType()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;box-sizing:border-box;">
                                <option value="">-- Seleccioná un tipo --</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Módulo</label>
                            <select id="typeuser-pages-module" onchange="renderTypeUserPagesList()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;box-sizing:border-box;">
                                <option value="">-- Todos los módulos --</option>
                            </select>
                        </div>
                    </div>
                    <div style="padding:8px 20px 0;">
                        <p id="typeuser-pages-hint" style="font-size:12px;color:#64748b;margin:0;display:none;"></p>
                    </div>
                    <div style="padding:14px 20px;overflow:auto;flex:1;min-height:180px;">
                        <div id="typeuser-pages-loading" style="padding:32px;text-align:center;display:none;">
                            <p style="font-size:13px;color:#6b7280;">Cargando páginas...</p>
                        </div>
                        <div id="typeuser-pages-empty" style="padding:32px;text-align:center;display:none;">
                            <p style="font-size:13px;color:#6b7280;">Seleccioná un tipo de usuario para gestionar sus páginas.</p>
                        </div>
                        <div id="typeuser-pages-list" style="display:none;overflow-x:auto;">
                            <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;">
                                <thead>
                                    <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                        <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;width:70%;">Página</th>
                                        <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;width:30%;">
                                            <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;text-transform:uppercase;">
                                                <input type="checkbox" id="typeuser-pages-check-all" onchange="toggleTypeUserPagesAll(this.checked)" style="width:15px;height:15px;accent-color:#D24C19;cursor:pointer;" title="Agregar o quitar todo" />
                                                <span>Agregar/Quitar</span>
                                            </label>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="typeuser-pages-tbody"></tbody>
                            </table>
                            <div id="typeuser-pages-grid-empty" style="display:none;padding:24px;text-align:center;font-size:13px;color:#6b7280;background:#fff;border:1px solid #e5e7eb;border-top:none;"></div>
                        </div>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;">
                        <span id="typeuser-pages-msg" class="save-msg" style="display:none;"></span>
                    </div>
                </div>
            </div>

            <div id="user-select-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:400;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:860px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);max-height:90vh;display:flex;flex-direction:column;box-sizing:border-box;">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Seleccionar usuario</h3>
                        <button onclick="closeUserSelectModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <div style="padding:16px 20px 0;">
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <input type="text" id="user-search" placeholder="Buscar por nombre o email..." oninput="filterUsers()" style="flex:1;min-width:180px;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;box-sizing:border-box;" />
                            <select id="user-filter-status" onchange="filterUsers()" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:#fff;min-width:140px;">
                                <option value="">Todos los estados</option>
                            </select>
                            <select id="user-filter-type" onchange="filterUsers()" style="padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:#fff;min-width:140px;">
                                <option value="">Todos los tipos</option>
                            </select>
                        </div>
                    </div>
                    <div style="padding:12px 20px 20px;overflow:auto;flex:1;">
                        <div style="background:white;border:1px solid #e5e7eb;overflow:hidden;">
                            <div id="users-loading" style="padding:40px;text-align:center;">
                                <p style="font-size:13px;color:#6b7280;">Cargando usuarios...</p>
                            </div>
                            <div id="users-empty" style="display:none;padding:40px;text-align:center;">
                                <p style="font-size:13px;color:#6b7280;">No se encontraron usuarios.</p>
                            </div>
                            <div id="users-table-wrap" style="display:none;overflow-x:auto;">
                            <table id="users-table" style="width:100%;border-collapse:collapse;">
                                <thead>
                                    <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                        <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Nombre</th>
                                        <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Email</th>
                                        <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="users-tbody"></tbody>
                            </table>
                            </div>
                            <div id="users-pagination" style="display:none;justify-content:center;align-items:center;width:100%;min-height:48px;padding:8px;border-top:1px solid #e5e7eb;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <button id="users-prev-page" class="pagination-nav-btn" onclick="userPage(1)" style="background:white;border:1px solid #d1d5db;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;color:#374151;">&#10094;</button>
                                    <div id="users-page-dots" style="display:flex;gap:6px;"></div>
                                    <button id="users-next-page" class="pagination-nav-btn" onclick="userPage(2)" style="background:white;border:1px solid #d1d5db;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;color:#374151;">&#10095;</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="user-modal-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:410;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:460px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 id="user-modal-title" style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Nuevo Usuario</h3>
                        <button onclick="closeUserModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <form id="user-form" onsubmit="submitUser(event)" style="padding:20px;">
                        @csrf
                        <input type="hidden" id="user-id" value="" />
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Nombre <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="user-name" maxlength="255" placeholder="Nombre completo" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            <p id="user-name-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <input type="hidden" id="user-email" value="" />
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Contraseña <span id="user-password-required" style="color:#dc2626;">*</span></label>
                            <div style="position:relative;">
                                <input type="password" id="user-password" minlength="8" placeholder="Mínimo 8 caracteres" style="width:100%;padding:8px 36px 8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;box-sizing:border-box;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                                <button type="button" class="user-pw-toggle" data-target="user-password" onclick="toggleUserPasswordVisibility('user-password', this)" title="Mostrar u ocultar contraseña" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;padding:0;cursor:pointer;color:#6b7280;line-height:1;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1 1 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1 1 0 0 1 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                                </button>
                            </div>
                            <p id="user-password-hint" style="font-size:11px;color:#6b7280;margin:4px 0 0;display:none;">Dejar en blanco para no cambiarla.</p>
                            <p id="user-password-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div id="user-password-confirm-field" style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Confirmar contraseña <span id="user-password-confirm-required" style="color:#dc2626;">*</span></label>
                            <div style="position:relative;">
                                <input type="password" id="user-password-confirm" minlength="8" placeholder="Repetí la contraseña" style="width:100%;padding:8px 36px 8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;box-sizing:border-box;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                                <button type="button" class="user-pw-toggle" data-target="user-password-confirm" onclick="toggleUserPasswordVisibility('user-password-confirm', this)" title="Mostrar u ocultar contraseña" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;padding:0;cursor:pointer;color:#6b7280;line-height:1;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1 1 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1 1 0 0 1 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                                </button>
                            </div>
                            <p id="user-password-confirm-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                            <button type="button" id="user-modal-delete-btn" onclick="userModalDelete()" style="padding:8px 16px;background:#dc2626;border:1px solid #dc2626;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;display:none;">Eliminar</button>
                            <button type="submit" id="user-submit-btn" class="btn-orange" style="padding:8px 16px;background:#D24C19;border:1px solid #D24C19;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="user-delete-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:420;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:380px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Confirmar inactivaci&oacute;n</h3>
                    </div>
                    <div style="padding:20px;">
                        <p style="font-size:13px;color:#374151;margin:0;">&iquest;Est&aacute;s seguro de inactivar al usuario <strong id="user-delete-name"></strong>? Se quitar&aacute;n sus p&aacute;ginas asignadas.</p>
                        <p id="user-delete-warning" style="font-size:11px;color:#dc2626;margin:8px 0 0;display:none;"></p>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
                        <button onclick="closeUserDelete()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                        <button id="user-delete-btn" onclick="confirmDeleteUser()" style="padding:8px 16px;background:#dc2626;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Eliminar</button>
                    </div>
                </div>
            </div>