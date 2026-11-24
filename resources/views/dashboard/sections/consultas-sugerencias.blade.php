@php
    // Mock data: reemplazar luego por datos reales desde el controlador / base de datos.
    // Estructura: code, name, flag (emoji), visits, pct, color (tema: azul oscuro + naranja).
    $countryVisits = $countryVisits ?? [
        ['code' => 'MX', 'name' => 'México', 'flag' => '🇲🇽', 'visits' => 1240, 'pct' => 38, 'color' => '#ea580c'],
        ['code' => 'ES', 'name' => 'España', 'flag' => '🇪🇸', 'visits' => 860, 'pct' => 26, 'color' => '#0c2a4d'],
        ['code' => 'AR', 'name' => 'Argentina', 'flag' => '🇦🇷', 'visits' => 540, 'pct' => 17, 'color' => '#f97316'],
        ['code' => 'CO', 'name' => 'Colombia', 'flag' => '🇨🇴', 'visits' => 320, 'pct' => 10, 'color' => '#1e40af'],
        ['code' => 'OT', 'name' => 'Otros', 'flag' => '🌍', 'visits' => 300, 'pct' => 9, 'color' => '#9ca3af'],
    ];
    $countryVisitsTotal = collect($countryVisits)->sum('visits');
@endphp
            <section id="dash-consultas-sugerencias" class="dash-section" style="display:none;max-width:1200px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                        <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Mensajes enviados desde el formulario de contacto del sitio</p>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                    <button type="button" onclick="openCountryVisitsModal()" style="background:#ea580c;border:1px solid #ea580c;border-radius:4px;padding:8px 14px;cursor:pointer;color:#ffffff;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;white-space:nowrap;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Ver Visitas por País
                    </button>
                    <button type="button" onclick="loadContactInquiries()" title="Actualizar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:8px 10px;cursor:pointer;color:#6b7280;display:flex;align-items:center;justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    </button>
                    </div>
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

            {{-- Modal: Estadísticas de Visitas por País (mock data, sin BD) --}}
            <style>
                #ci-visits-overlay { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.55); z-index:210; align-items:center; justify-content:center; padding:16px; }
                #ci-visits-overlay.ci-open { display:flex; }
                .ci-visits-card { background:#fff; width:100%; max-width:920px; border-radius:8px; box-shadow:0 12px 40px rgba(0,0,0,.25); max-height:92vh; overflow-y:auto; }
                .ci-visits-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; padding:20px; }
                @media (max-width:768px){ .ci-visits-grid { grid-template-columns:1fr; } }
                .ci-donut { width:170px; height:170px; border-radius:50%; margin:0 auto; position:relative;
                    background:conic-gradient(#ea580c 0% 38%, #0c2a4d 38% 64%, #f97316 64% 81%, #1e40af 81% 91%, #9ca3af 91% 100%); }
                .ci-donut::after { content:''; position:absolute; top:26px; left:26px; right:26px; bottom:26px; background:#fff; border-radius:50%; }
                .ci-donut-center { position:absolute; top:0; left:0; right:0; bottom:0; display:flex; flex-direction:column; align-items:center; justify-content:center; z-index:1; }
                .ci-bar { height:8px; background:#e5e7eb; border-radius:9999px; overflow:hidden; }
                .ci-bar > div { height:100%; border-radius:9999px; }
                .ci-country-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f3f4f6; font-size:13px; }
                .ci-country-row:last-child { border-bottom:none; }
            </style>
            <div id="ci-visits-overlay" onclick="if(event.target===this)closeCountryVisitsModal()">
                <div class="ci-visits-card" role="dialog" aria-modal="true" aria-label="Estadísticas de Visitas por País">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Estadísticas de Visitas por País</h3>
                        <button type="button" onclick="closeCountryVisitsModal()" aria-label="Cerrar" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:20px;line-height:1;">&times;</button>
                    </div>
                    <div class="ci-visits-grid">
                        <div>
                            <p style="font-size:13px;color:#6b7280;margin:0 0 4px;">Total de visitas registradas en el sitio</p>
                            <p style="font-size:26px;font-weight:800;color:#0c2a4d;margin:0 0 14px;">{{ number_format($countryVisitsTotal) }}</p>
                            <div class="ci-donut"><div class="ci-donut-center"><span style="font-size:22px;font-weight:800;color:#0c2a4d;">100%</span><span style="font-size:11px;color:#6b7280;">visitas</span></div></div>
                            <div style="margin:16px 0 6px;display:flex;flex-direction:column;gap:10px;">
                                @foreach($countryVisits as $c)
                                <div>
                                    <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;"><span>{{ $c['flag'] }} {{ $c['name'] }}</span><span>{{ $c['pct'] }}%</span></div>
                                    <div class="ci-bar"><div style="width:{{ $c['pct'] }}%;background:{{ $c['color'] }};"></div></div>
                                </div>
                                @endforeach
                            </div>
                            <div style="margin-top:8px;">
                                @foreach($countryVisits as $c)
                                <div class="ci-country-row">
                                    <span style="font-size:18px;">{{ $c['flag'] }}</span>
                                    <span style="flex:1;font-weight:600;color:#1f2937;">{{ $c['name'] }}</span>
                                    <span style="color:#6b7280;">{{ number_format($c['visits']) }} visitas</span>
                                    <span style="min-width:44px;text-align:right;font-weight:700;color:#0c2a4d;">{{ $c['pct'] }}%</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:12px;">
                                <p style="font-size:12px;font-weight:700;color:#0c2a4d;margin:0 0 8px;text-transform:uppercase;letter-spacing:.5px;">Mapa mundial</p>
                                <svg viewBox="0 0 400 210" style="width:100%;height:auto;display:block;background:#eef2f7;border-radius:6px;" aria-hidden="true">
                                    <rect x="0" y="0" width="400" height="210" fill="#eef2f7"/>
                                    <g fill="#cbd5e1">
                                        <ellipse cx="95" cy="70" rx="55" ry="32"/>
                                        <ellipse cx="300" cy="60" rx="62" ry="30"/>
                                        <ellipse cx="310" cy="130" rx="45" ry="28"/>
                                        <ellipse cx="110" cy="150" rx="32" ry="38"/>
                                        <ellipse cx="215" cy="120" rx="28" ry="22"/>
                                    </g>
                                    <g stroke="#94a3b8" stroke-width="1" fill="none" opacity=".6">
                                        <ellipse cx="95" cy="70" rx="55" ry="32"/>
                                        <ellipse cx="300" cy="60" rx="62" ry="30"/>
                                    </g>
                                    <g>
                                        <circle cx="80" cy="85" r="12" fill="#ea580c" opacity=".95"><title>México</title></circle>
                                        <circle cx="80" cy="85" r="16" fill="none" stroke="#ea580c" stroke-width="2" opacity=".4"/>
                                        <circle cx="200" cy="62" r="10" fill="#0c2a4d" opacity=".95"><title>España</title></circle>
                                        <circle cx="200" cy="62" r="14" fill="none" stroke="#0c2a4d" stroke-width="2" opacity=".35"/>
                                        <circle cx="118" cy="168" r="10" fill="#f97316" opacity=".95"><title>Argentina</title></circle>
                                        <circle cx="100" cy="135" r="8" fill="#1e40af" opacity=".95"><title>Colombia</title></circle>
                                    </g>
                                    <g font-family="sans-serif" font-size="10" font-weight="bold" fill="#0c2a4d">
                                        <text x="80" y="112" text-anchor="middle">MX 38%</text>
                                        <text x="200" y="86" text-anchor="middle">ES 26%</text>
                                        <text x="118" y="192" text-anchor="middle">AR 17%</text>
                                        <text x="100" y="152" text-anchor="middle">CO 10%</text>
                                    </g>
                                </svg>
                                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;font-size:12px;color:#374151;">
                                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ea580c;margin-right:4px;"></span>Mayor tráfico</span>
                                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#0c2a4d;margin-right:4px;"></span>Tráfico alto</span>
                                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#cbd5e1;margin-right:4px;"></span>Base</span>
                                </div>
                            </div>
                            <p style="font-size:12px;color:#6b7280;margin:10px 0 0;">Datos de ejemplo (mock). Para conectar a la base real, reemplazar el bloque <code>$countryVisits</code> por la variable enviada desde el controlador.</p>
                        </div>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;">
                        <button type="button" onclick="closeCountryVisitsModal()" style="padding:8px 18px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:#374151;">Cerrar</button>
                    </div>
                </div>
            </div>
            <script>
                function openCountryVisitsModal(){ var m=document.getElementById('ci-visits-overlay'); if(m){ m.classList.add('ci-open'); document.body.style.overflow='hidden'; } }
                function closeCountryVisitsModal(){ var m=document.getElementById('ci-visits-overlay'); if(m){ m.classList.remove('ci-open'); document.body.style.overflow=''; } }
                document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeCountryVisitsModal(); } });
            </script>
