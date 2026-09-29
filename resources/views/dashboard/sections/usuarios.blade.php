            <section id="dash-usuarios" class="dash-section" style="display:none;max-width:1100px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">Usuarios</h2>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <button type="button" onclick="openTypeUserPagesModal()" title="Páginas por tipos usuarios" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:8px 10px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m-6-6h.008v.008H8.25v-6zm9 0h.008v.008H16.5v-6zm-9 3.75h.008v.008H8.25v-.008zm9 0h.008v.008H16.5v-.008z"/></svg>
                        </button>
                        <button type="button" onclick="openUserSelectModal('all')" title="Seleccionar usuario" style="background:#D24C19;color:white;border:1px solid #D24C19;padding:8px 10px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        </button>
                    </div>
                </div>

                <div id="user-demo-empty" style="background:white;border:1px solid #e5e7eb;padding:48px 24px;text-align:center;">
                    <div style="width:56px;height:56px;border-radius:50%;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                    </div>
                    <p style="font-size:14px;font-weight:600;color:#0f172a;margin:0 0 6px;">Ningún usuario seleccionado</p>
                    <p style="font-size:13px;color:#64748b;margin:0;">Elegí un usuario para ver su demografía y trabajar con él.</p>
                </div>

                <div id="user-sidebar-overlay" onclick="toggleUserSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:240;"></div>

                <div id="user-demo-panel" style="display:none;background:white;border:1px solid #e5e7eb;">
                    <div style="display:flex;align-items:stretch;background:#fff;border-bottom:1px solid #e5e7eb;">
                        <aside id="user-sidebar">
                            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div id="user-demo-avatar" style="width:38px;height:38px;border-radius:50%;background:#D24C19;color:white;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0;">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                    <div style="min-width:0;">
                                        <div id="user-demo-hdr-name" style="font-size:13px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $user->name }}</div>
                                        <div id="user-demo-hdr-email" style="font-size:11px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $user->email }}</div>
                                    </div>
                                </div>
                                <div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap;">
                                    <span id="user-demo-status" class="status-badge" style="font-size:11px;padding:4px 10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;">{{ optional($user->status)->status ?: 'Activo' }}</span>
                                    <span id="user-demo-type" class="status-badge" style="background:#eff6ff;color:#1d4ed8;font-size:11px;padding:4px 10px;">{{ optional($user->typeUser)->description ?: 'Prestador' }}</span>
                                </div>
                            </div>

                            <nav style="flex:1;padding:12px 0;">
                                <a href="#" onclick="event.preventDefault();switchUserTab('demografia')" class="sidebar-link active" data-user-panel="demografia">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                    <span>Demografía</span>
                                </a>
                                <a href="#" onclick="event.preventDefault();switchUserTab('direcciones')" class="sidebar-link" data-user-panel="direcciones">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                    <span>Direccion</span>
                                </a>
                                <a href="#" onclick="event.preventDefault();switchUserTab('negocio')" class="sidebar-link" data-user-panel="negocio">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                                    <span>Negocio</span>
                                </a>
                                <a href="#" onclick="event.preventDefault();switchUserTab('categorias')" class="sidebar-link" data-user-panel="categorias">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path d="M6 6h.008v.008H6V6Z"/></svg>
                                    <span>Categorias</span>
                                </a>
                                <a href="#" onclick="event.preventDefault();switchUserTab('cuenta')" class="sidebar-link" data-user-panel="cuenta">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                    <span>Cuentas</span>
                                </a>
                                <a href="#" onclick="event.preventDefault();switchUserTab('imagenes')" class="sidebar-link" data-user-panel="imagenes">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    <span>Publicidad</span>
                                </a>
                                <a href="#" onclick="event.preventDefault();switchUserTab('config')" class="sidebar-link" data-user-panel="config">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    <span>Contraseñas</span>
                                </a>
                            </nav>
                        </aside>

                        <div id="user-content">
                            <div style="height:44px;background:#fff;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;padding:0 20px;gap:8px;">
                                <button onclick="toggleUserSidebar()" id="user-sidebar-toggle" style="display:none;background:none;border:none;cursor:pointer;padding:4px;color:#0f172a;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                                </button>
                                <span style="font-size:12px;color:#64748b;">Usuarios</span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                <span style="font-size:12px;color:#0f172a;font-weight:600;" id="user-breadcrumb-current">Demografía</span>
                            </div>

                            <div id="user-panel-demografia" class="user-panel active">
                                <div class="pcard">
                                    <h3 class="pcard-title">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                        Datos del usuario
                                    </h3>
                                    <p class="pcard-sub">Editá la información de la cuenta de este usuario.</p>
                                    <div class="profile-field-grid">
                                        <div>
                                            <label class="field-label">Nombre completo</label>
                                            <input type="text" id="user-demo-name" class="field-input" maxlength="255" placeholder="Nombre completo" />
                                            <span class="err-msg" id="user-demo-name-error"></span>
                                        </div>
                                        <div>
                                            <label class="field-label">Email</label>
                                            <input type="email" id="user-demo-email" class="field-input" maxlength="255" placeholder="correo@ejemplo.com" />
                                            <span class="err-msg" id="user-demo-email-error"></span>
                                        </div>
                                        <div>
                                            <label class="field-label">Estado</label>
                                            <select id="user-demo-status-id" class="field-input">
                                                <option value="">-- Sin estado --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="field-label">Tipo de usuario</label>
                                            <select id="user-demo-type-id" class="field-input">
                                                <option value="">-- Sin tipo --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="field-label">Registro</label>
                                            <input type="text" id="user-demo-created" class="field-input" readonly style="background:#f8fafc;" />
                                        </div>
                                        <div>
                                            <label class="field-label">Email verificado</label>
                                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#334155;cursor:pointer;min-height:38px;">
                                                <input type="checkbox" id="user-demo-verified" style="accent-color:#D24C19;width:14px;height:14px;cursor:pointer;" />
                                                Marcar como verificado
                                            </label>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                        <span class="save-msg" id="user-demo-msg"></span>
                                        <button type="button" class="btn-primary" id="user-demo-save-btn" onclick="submitUserDemographic()">Guardar datos</button>
                                    </div>
                                </div>
                            </div>

                            <div id="user-panel-direcciones" class="user-panel">
                                <div class="pcard">
                                    <h3 class="pcard-title">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                        <span id="user-addr-form-title">Mi Dirección</span>
                                    </h3>
                                    <p class="pcard-sub">Configurá la ubicación de este usuario para que los clientes lo encuentren.</p>
                                    <input type="hidden" id="user-addr-id" value="" />
                                    <div class="profile-field-grid">
                                        <div>
                                            <label class="field-label">País</label>
                                            <select id="user-addr-country" class="field-input" onchange="loadUserAddressRegions(this.value)">
                                                <option value="">-- Seleccionar --</option>
                                            </select>
                                            <span class="err-msg" id="user-addr-country-error"></span>
                                        </div>
                                        <div>
                                            <label class="field-label">Provincia / Región</label>
                                            <select id="user-addr-province" class="field-input" disabled>
                                                <option value="">-- Seleccionar país primero --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="field-label">Calle</label>
                                            <input type="text" id="user-addr-street" class="field-input" maxlength="255" placeholder="Av. Corrientes" />
                                        </div>
                                        <div>
                                            <label class="field-label">Número</label>
                                            <input type="text" id="user-addr-number" class="field-input" maxlength="20" placeholder="4567" />
                                        </div>
                                        <div>
                                            <label class="field-label">Piso / Depto</label>
                                            <input type="text" id="user-addr-floor" class="field-input" maxlength="50" placeholder="Local 1" />
                                        </div>
                                        <div>
                                            <label class="field-label">Código Postal</label>
                                            <input type="text" id="user-addr-postal" class="field-input" maxlength="20" placeholder="C1043" />
                                        </div>
                                        <div class="full">
                                            <label class="field-label">Notas de ubicación</label>
                                            <textarea id="user-addr-notes" rows="2" class="field-input" maxlength="500" placeholder="Esquina con Callao, local amarillo..."></textarea>
                                        </div>
                                    </div>
                                    <span class="err-msg" id="user-addr-error" style="margin-bottom:12px;"></span>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                        <span class="save-msg" id="user-addr-msg"></span>
                                        <button type="button" class="btn-primary" id="user-addr-save-btn" onclick="submitUserAddress(event)">Guardar dirección</button>
                                    </div>
                                </div>
                            </div>

                            <div id="user-panel-negocio" class="user-panel">
                                <div class="pcard">
                                    <h3 class="pcard-title">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 21v-7.5h-3V21m-6 0v-7.5H3.75V21M4.5 3h15a.75.75 0 0 1 .75.75V6a.75.75 0 0 1-.75.75h-15A.75.75 0 0 1 3.75 6V3.75A.75.75 0 0 1 4.5 3Z"/></svg>
                                        Negocio / Proveedor
                                    </h3>
                                    <p class="pcard-sub">Datos del negocio que se muestran en las tarjetas publicitarias.</p>
                                    <div class="profile-field-grid">
                                        <div>
                                            <label class="field-label">Nombre comercial</label>
                                            <input type="text" id="user-prov-business" class="field-input" maxlength="255" placeholder="Ej: Agua Purificada Don Agua" />
                                            <span class="err-msg" id="user-prov-business-error"></span>
                                        </div>
                                        <div>
                                            <label class="field-label">Teléfono</label>
                                            <input type="text" id="user-prov-phone" class="field-input" maxlength="30" placeholder="+54 11 5555-1515" />
                                        </div>
                                        <div>
                                            <label class="field-label">WhatsApp</label>
                                            <input type="text" id="user-prov-whatsapp" class="field-input" maxlength="30" placeholder="541155551515" />
                                        </div>
                                        <div>
                                            <label class="field-label">Zona de cobertura</label>
                                            <input type="text" id="user-prov-zone" class="field-input" maxlength="255" placeholder="Tigre, San Fernando" />
                                        </div>
                                        <div class="full">
                                            <label class="field-label">Descripción</label>
                                            <textarea id="user-prov-description" rows="2" class="field-input" maxlength="1000" placeholder="Venta y distribución de agua purificada y bidones."></textarea>
                                        </div>
                                        <div class="full">
                                            <label class="field-label">Promoción</label>
                                            <input type="text" id="user-prov-promo" class="field-input" maxlength="255" placeholder="3 bidones por el precio de 2" />
                                        </div>
                                        <div class="full">
                                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#334155;cursor:pointer;">
                                                <input type="checkbox" id="user-prov-active" style="accent-color:#D24C19;width:14px;height:14px;cursor:pointer;" />
                                                Negocio activo
                                            </label>
                                        </div>
                                    </div>
                                    <span class="err-msg" id="user-prov-error" style="margin-bottom:12px;"></span>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                        <span class="save-msg" id="user-prov-msg"></span>
                                        <button type="button" class="btn-primary" id="user-prov-save-btn" onclick="submitUserProvider(event)">Guardar negocio</button>
                                    </div>
                                </div>
                            </div>

                            <div id="user-panel-categorias" class="user-panel">
                                <div class="pcard">
                                    <h3 class="pcard-title">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path d="M6 6h.008v.008H6V6Z"/></svg>
                                        Categorias
                                    </h3>
                                    <p class="pcard-sub">Seleccioná los grupos y subgrupos de servicios que ofrece este negocio.</p>
                                    <div id="user-categorias-loading" style="text-align:center;padding:20px;color:#94a3b8;font-size:12px;">Cargando categorías...</div>
                                    <div id="user-categorias-list" style="display:none;"></div>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:16px;">
                                        <span class="save-msg" id="user-categorias-msg"></span>
                                    </div>
                                </div>
                            </div>

                            <div id="user-panel-cuenta" class="user-panel">
                                <div class="pcard">
                                    <div class="user-demo-section-header" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:0;">
                                        <div>
                                            <h3 class="pcard-title" style="margin-bottom:4px;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                                Cuentas
                                            </h3>
                                            <p class="pcard-sub" style="margin-bottom:0;">Usuarios</p>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:18px;">
                                    <div class="user-account-tabs" role="tablist" style="margin-top:0;flex:1;min-width:0;">
                                        <button type="button" class="user-account-tab active" data-account-tab="gestion" onclick="switchUserAccountTab('gestion')" role="tab">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                            Usuarios
                                            <span class="tab-count" id="user-tab-count-gestion">1</span>
                                        </button>
                                        <button type="button" class="user-account-tab" data-account-tab="paginas" onclick="switchUserAccountTab('paginas')" role="tab">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                            Páginas
                                            <span class="tab-count" id="user-tab-count-paginas">0</span>
                                        </button>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;padding-bottom:4px;">
                                        <button type="button" onclick="openUserSelectModal('account')" title="Seleccionar usuario" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:8px 10px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                        </button>
                                        <button type="button" id="user-create-btn" onclick="openUserModal(null)" title="Crear usuario" style="background:#D24C19;color:white;border:1px solid #D24C19;padding:8px 10px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                        </button>
                                    </div>
                                    </div>
                                    <div id="user-account-pane-gestion" class="user-account-pane" role="tabpanel"></div>
                                    <div id="user-account-pane-paginas" class="user-account-pane" role="tabpanel" style="display:none;"></div>
                                </div>
                            </div>

                            <div id="user-panel-imagenes" class="user-panel">
                                <div class="pcard">
                                    <h3 class="pcard-title">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                        Publicidad
                                    </h3>
                                    <p class="pcard-sub">Publicidad del negocio del usuario. Solo se permite una a la vez.</p>
                                    <div id="user-demo-images-dropzone" class="dropzone" style="display:none;max-width:400px;">
                                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="1.6"><path d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0z"/></svg>
                                        <span class="dropzone-title">Subir imagen publicitaria</span>
                                        <span class="dropzone-hint">Arrastrá o hacé clic (JPG o PNG, máx. 5MB)</span>
                                        <input type="file" id="user-demo-images-input" accept="image/jpeg,image/png" style="display:none;" />
                                    </div>
                                    <div id="user-demo-images-no-provider" class="user-demo-empty-tab" style="display:none;">Este usuario no tiene negocio. Creá el negocio para poder subir imágenes.</div>
                                    <span id="user-demo-images-msg" class="save-msg" style="display:none;margin-top:12px;"></span>
                                    <div id="user-demo-images-grid" style="display:none;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-top:16px;"></div>
                                    <div id="user-demo-images-empty" class="user-demo-empty-tab" style="display:none;margin-top:4px;">No hay imagen publicitaria cargada.</div>
                                </div>
                            </div>

                            <div id="user-panel-config" class="user-panel">
                                <div class="pcard" id="user-cfg-password-card">
                                    <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg> Cambiar contraseña</h3>
                                    <p class="pcard-sub" id="user-cfg-pw-sub">Usá una contraseña segura de al menos 8 caracteres.</p>
                                    <div style="display:flex;flex-direction:column;gap:16px;">
                                        <div id="user-cfg-pw-current-field"><label class="field-label">Contraseña actual</label><div style="position:relative;width:420px;max-width:100%;"><input type="password" id="user-cfg-pw-current" class="field-input" autocomplete="current-password" style="padding-right:36px;" /><button type="button" onclick="togglePwVis('user-cfg-pw-current', this)" title="Mostrar contraseña" aria-label="Mostrar contraseña" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#64748b;display:flex;align-items:center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg></button></div><span class="err-msg" id="user-cfg-pw-current-error"></span></div>
                                        <div><label class="field-label">Nueva contraseña</label><div style="position:relative;width:420px;max-width:100%;"><input type="password" id="user-cfg-pw-new" class="field-input" autocomplete="new-password" style="padding-right:36px;" /><button type="button" onclick="togglePwVis('user-cfg-pw-new', this)" title="Mostrar contraseña" aria-label="Mostrar contraseña" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#64748b;display:flex;align-items:center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg></button></div><span class="err-msg" id="user-cfg-pw-new-error"></span></div>
                                        <div><label class="field-label">Confirmar nueva contraseña</label><div style="position:relative;width:420px;max-width:100%;"><input type="password" id="user-cfg-pw-confirm" class="field-input" autocomplete="new-password" style="padding-right:36px;" /><button type="button" onclick="togglePwVis('user-cfg-pw-confirm', this)" title="Mostrar contraseña" aria-label="Mostrar contraseña" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#64748b;display:flex;align-items:center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg></button></div><span class="err-msg" id="user-cfg-pw-confirm-error"></span></div>
                                    </div>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                        <span class="save-msg" id="user-cfg-pw-msg"></span>
                                        <button type="button" class="btn-primary" onclick="submitUserAccountPassword()">Guardar contraseña</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>