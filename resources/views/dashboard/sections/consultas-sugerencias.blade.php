            <section id="dash-consultas-sugerencias" class="dash-section" style="display:none;max-width:1200px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                        <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Mensajes enviados desde el formulario de contacto del sitio</p>
                    </div>
                    <button type="button" onclick="loadContactInquiries()" title="Actualizar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:8px 10px;cursor:pointer;color:#6b7280;display:flex;align-items:center;justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    </button>
                </div>

                <div id="ci-filters" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                    <button type="button" data-status="all" onclick="setContactInquiryFilter('all')" style="padding:6px 12px;border:1px solid #d1d5db;border-radius:9999px;background:#ffffff;color:#374151;font-size:12px;font-weight:600;cursor:pointer;">Todas <span id="ci-count-all">0</span></button>
                    <button type="button" data-status="new" onclick="setContactInquiryFilter('new')" style="padding:6px 12px;border:1px solid #d1d5db;border-radius:9999px;background:#ffffff;color:#374151;font-size:12px;font-weight:600;cursor:pointer;">Nuevas <span id="ci-count-new">0</span></button>
                    <button type="button" data-status="in_progress" onclick="setContactInquiryFilter('in_progress')" style="padding:6px 12px;border:1px solid #d1d5db;border-radius:9999px;background:#ffffff;color:#374151;font-size:12px;font-weight:600;cursor:pointer;">En proceso <span id="ci-count-in_progress">0</span></button>
                    <button type="button" data-status="resolved" onclick="setContactInquiryFilter('resolved')" style="padding:6px 12px;border:1px solid #d1d5db;border-radius:9999px;background:#ffffff;color:#374151;font-size:12px;font-weight:600;cursor:pointer;">Resueltas <span id="ci-count-resolved">0</span></button>
                </div>

                <div style="background:white;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;">
                    <div id="ci-loading" style="padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">Cargando consultas...</p>
                    </div>
                    <div id="ci-empty" style="display:none;padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">No se encontraron consultas.</p>
                    </div>
                    <div id="ci-table-wrap" style="display:none;overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Cliente</th>
                                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Correo</th>
                                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Tipo</th>
                                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Mensaje</th>
                                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Fecha</th>
                                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Estado</th>
                                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="ci-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <div id="ci-detail-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:620px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);max-height:90vh;overflow-y:auto;">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Detalle de la consulta</h3>
                        <button onclick="closeContactInquiryDetail()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <div style="padding:20px;">
                        <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                            <div style="flex:1 1 200px;">
                                <p style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 2px;">Cliente</p>
                                <p id="ci-detail-name" style="font-size:14px;font-weight:600;color:#1f2937;margin:0;"></p>
                            </div>
                            <div style="flex:1 1 200px;">
                                <p style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 2px;">Correo</p>
                                <a id="ci-detail-email" href="#" style="font-size:14px;color:#D24C19;font-weight:600;text-decoration:none;word-break:break-all;"></a>
                            </div>
                            <div style="flex:1 1 120px;">
                                <p style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 2px;">Tipo</p>
                                <p id="ci-detail-type" style="font-size:14px;font-weight:600;color:#1f2937;margin:0;"></p>
                            </div>
                            <div style="flex:1 1 140px;">
                                <p style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 2px;">Fecha</p>
                                <p id="ci-detail-date" style="font-size:14px;color:#1f2937;margin:0;"></p>
                            </div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <p style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 4px;">Mensaje</p>
                            <div id="ci-detail-message" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:4px;padding:12px;font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;word-break:break-word;"></div>
                        </div>

                        <div style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                            <div style="flex:1 1 220px;">
                                <label for="ci-detail-status" style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Estado</label>
                                <select id="ci-detail-status" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;">
                                    <option value="new">Nueva</option>
                                    <option value="in_progress">En proceso</option>
                                    <option value="resolved">Resuelta</option>
                                </select>
                            </div>
                            <a id="ci-detail-reply" href="#" style="padding:9px 14px;background:#ffffff;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:600;color:#0c2a4d;text-decoration:none;white-space:nowrap;">Responder por correo</a>
                        </div>

                        <p id="ci-detail-msg" style="font-size:12px;margin:0 0 12px;display:none;"></p>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
                        <button type="button" onclick="closeContactInquiryDetail()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cerrar</button>
                        <button type="button" id="ci-detail-save" onclick="saveContactInquiryStatus()" style="padding:8px 16px;background:#D24C19;border:1px solid #D24C19;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Guardar estado</button>
                    </div>
                </div>
            </div>
