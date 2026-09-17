<!DOCTYPE html>
<html lang="es" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>PideAca - Mi Panel</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|inter:400,500,600,700,800" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://cdn.jsdelivr.net/npm/daisyui@5/dist/full.min.css" rel="stylesheet" />
        @endif
    </head>
    <body style="margin:0;padding:0;background:#ffffff;min-height:100vh;display:flex;flex-direction:column;">

        <div style="background:#D24C19;height:2px;"></div>

        {{-- NAV DINAMICO --}}
        <nav style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);position:sticky;top:0;z-index:50;overflow:visible;">
            <div class="nav-container" style="max-width:1200px;margin:0 auto;padding:1px 2px;display:flex;align-items:center;justify-content:flex-end;gap:6px;position:relative;">
                <div style="z-index:60;cursor:default;">
                    <img class="logo-img" src="{{ asset('images/logo.png') }}" alt="PideAca" style="width:170px;height:58px;border-radius:0;object-fit:contain;" />
                </div>
                <div class="user-badge" style="display:flex;align-items:center;gap:8px;padding:4px 12px;color:white;font-size:12px;border-radius:4px;margin-right:auto;">
                    <div style="width:28px;height:28px;border-radius:50%;background:#D24C19;color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div style="display:flex;flex-direction:column;line-height:1.2;">
                        <span style="font-weight:600;font-size:12px;">{{ $user->name }}</span>
                        <span style="font-size:10px;color:rgba(255,255,255,0.7);">{{ $user->email }}</span>
                    </div>
                </div>
                @foreach($modules as $module)
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                @if($module->pages->count() === 1)
                    @php $page = $module->pages->first(); @endphp
                    <a href="#" onclick="event.preventDefault();showDashSection('{{ $page->url }}')" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                        {{ $page->description }}
                    </a>
                @else
                    <div class="nav-dropdown" style="position:relative;">
                        <button class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;text-decoration:none;">
                            @if($module->icon)
                            <span style="display:flex;align-items:center;">{!! $module->icon !!}</span>
                            @endif
                            {{ $module->description }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div class="nav-dropdown-menu" style="display:none;position:absolute;top:100%;left:0;background:white;border-radius:0;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;padding:6px 0;z-index:100;">
                            @foreach($module->pages as $page)
                            <a href="#" onclick="event.preventDefault();closeDropdowns();showDashSection('{{ $page->url }}')" class="dropdown-item" style="display:block;padding:8px 16px;color:#1f2937;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;">
                                {{ $page->description }}
                            </a>
                            @endforeach
                            <div style="width:100%;height:1px;background:#e5e7eb;margin:4px 0;"></div>
                            <a href="#" onclick="event.preventDefault();closeDropdowns();showDashSection('perfil')" class="dropdown-item" style="display:block;padding:8px 16px;color:#1f2937;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;">
                                Mi Perfil
                            </a>
                            <div style="width:100%;height:1px;background:#e5e7eb;margin:4px 0;"></div>
                            <a href="#" onclick="event.preventDefault();closeDropdowns();doLogout()" class="dropdown-item" style="display:block;padding:8px 16px;color:#dc2626;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;">
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                @endif
                @endforeach
                <button id="menu-toggle" onclick="toggleMenu()" style="display:none;background:none;border:none;color:white;font-size:24px;cursor:pointer;padding:4px 8px;">&#9776;</button>
            </div>
            <div id="mobile-menu" style="display:none;background:#ffffff;padding:10px 8px;position:absolute;top:100%;left:0;right:0;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <div style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:8px 0;">
                    <div style="width:100%;height:1px;background:#e5e7eb;"></div>
                    @foreach($modules as $module)
                        <button onclick="toggleMobileSub(this)" style="width:100%;background:none;border:none;font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;padding:8px 0;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                            @if($module->icon)
                            <span style="display:flex;align-items:center;">{!! $module->icon !!}</span>
                            @endif
                            {{ $module->description }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div class="mobile-sub" style="display:none;width:100%;text-align:center;">
                            @foreach($module->pages as $page)
                            <a href="#" onclick="showDashSection('{{ $page->url }}')" style="display:block;color:#1f2937;text-decoration:none;font-size:13px;font-weight:500;padding:6px 0;">{{ $page->description }}</a>
                            @endforeach
                            <a href="#" onclick="showDashSection('perfil')" style="display:block;color:#1f2937;text-decoration:none;font-size:13px;font-weight:500;padding:6px 0;">Mi Perfil</a>
                        </div>
                    @endforeach
                    <div style="width:100%;height:1px;background:#e5e7eb;margin:4px 0;"></div>
                    <button type="button" onclick="doLogout()" style="background:none;border:none;color:#dc2626;font-size:13px;font-weight:600;cursor:pointer;padding:6px 0;">Cerrar Sesión</button>
                </div>
            </div>
        </nav>

        <main style="flex:1;">
            <div style="background:#D24C19;height:2px;"></div>

            {{-- DASHBOARD SECTIONS --}}
            <section id="dash-perfil" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin-bottom:12px;">Mi Perfil</h2>
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0;padding:20px;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div>
                            <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Nombre</label>
                            <p style="font-size:14px;color:#1f2937;margin:4px 0 0;">{{ $user->name }}</p>
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Correo</label>
                            <p style="font-size:14px;color:#1f2937;margin:4px 0 0;">{{ $user->email }}</p>
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Dirección</label>
                            <p style="font-size:14px;color:#1f2937;margin:4px 0 0;">{{ $user->address ?? 'No registrada' }}</p>
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Estado</label>
                            <p style="font-size:14px;color:#1f2937;margin:4px 0 0;">{{ $user->status->status ?? 'Sin estado' }}</p>
                        </div>
                    </div>
                </div>
            </section>

            @foreach($modules as $module)
                @foreach($module->pages as $page)
                    @if($page->url === 'modulos')
            <section id="dash-modulos" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                    <button onclick="openModuleModal()" class="btn-orange" style="background:#D24C19;color:white;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Nuevo Módulo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:12px;">
                    <input type="text" id="module-search" placeholder="Buscar módulo..." oninput="filterModules()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;" />
                </div>

                <div style="background:white;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;">
                    <div id="modules-loading" style="padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">Cargando módulos...</p>
                    </div>
                    <div id="modules-empty" style="display:none;padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">No se encontraron módulos.</p>
                    </div>
                    <div id="modules-table-wrap" style="display:none;overflow-x:auto;">
                    <table id="modules-table" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Descripción</th>
                                <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="modules-tbody"></tbody>
                    </table>
                    </div>
                </div>
            </section>

            <div id="module-modal-overlay" onclick="if(event.target===this)closeModuleModal()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:420px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 id="module-modal-title" style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Nuevo Módulo</h3>
                        <button onclick="closeModuleModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <form id="module-form" onsubmit="submitModule(event)" style="padding:20px;">
                        @csrf
                        <input type="hidden" id="module-id" value="" />
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Descripción <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="module-description" maxlength="255" placeholder="Nombre del módulo" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            <p id="module-description-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Icono (SVG)</label>
                            <textarea id="module-icon" rows="3" placeholder='<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">...</svg>' style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;font-family:monospace;outline:none;resize:vertical;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'"></textarea>
                            <p style="font-size:10px;color:#9ca3af;margin:2px 0 0;">Pegá el código SVG del icono. Si está vacío, no se muestra icono.</p>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                            <button type="button" onclick="closeModuleModal()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                            <button type="submit" id="module-submit-btn" class="btn-orange" style="padding:8px 16px;background:#D24C19;border:1px solid #D24C19;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="module-delete-overlay" onclick="if(event.target===this)closeModuleDelete()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:380px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Confirmar Eliminación</h3>
                    </div>
                    <div style="padding:20px;">
                        <p style="font-size:13px;color:#374151;margin:0;">¿Estás seguro de eliminar el módulo <strong id="module-delete-name"></strong>?</p>
                        <p id="module-delete-warning" style="font-size:11px;color:#dc2626;margin:8px 0 0;display:none;"></p>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
                        <button onclick="closeModuleDelete()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                        <button id="module-delete-btn" onclick="confirmDeleteModule()" style="padding:8px 16px;background:#dc2626;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Eliminar</button>
                    </div>
                </div>
            </div>
                    @elseif($page->url === 'grupos')
            <section id="dash-grupos" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                    <button onclick="openGroupModal()" class="btn-orange" style="background:#D24C19;color:white;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Nuevo Grupo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:12px;">
                    <input type="text" id="group-search" placeholder="Buscar grupo..." oninput="filterGroups()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;" />
                </div>

                <div style="background:white;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;">
                    <div id="groups-loading" style="padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">Cargando grupos...</p>
                    </div>
                    <div id="groups-empty" style="display:none;padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">No se encontraron grupos.</p>
                    </div>
                    <div id="groups-table-wrap" style="display:none;overflow-x:auto;">
                    <table id="groups-table" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Descripción</th>
                                <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="groups-tbody"></tbody>
                    </table>
                    </div>
                </div>
            </section>

            <div id="group-modal-overlay" onclick="if(event.target===this)closeGroupModal()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:420px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 id="group-modal-title" style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Nuevo Grupo</h3>
                        <button onclick="closeGroupModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <form id="group-form" onsubmit="submitGroup(event)" style="padding:20px;">
                        @csrf
                        <input type="hidden" id="group-id" value="" />
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Descripción <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="group-description" maxlength="255" placeholder="Nombre del grupo" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            <p id="group-description-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Icono (SVG)</label>
                            <textarea id="group-icon" rows="3" placeholder='<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">...</svg>' style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;font-family:monospace;outline:none;resize:vertical;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'"></textarea>
                            <p style="font-size:10px;color:#9ca3af;margin:2px 0 0;">Pegá el código SVG del icono. Si está vacío, no se muestra icono.</p>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                            <button type="button" onclick="closeGroupModal()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                            <button type="submit" id="group-submit-btn" class="btn-orange" style="padding:8px 16px;background:#D24C19;border:1px solid #D24C19;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="group-delete-overlay" onclick="if(event.target===this)closeGroupDelete()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:380px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Confirmar Eliminación</h3>
                    </div>
                    <div style="padding:20px;">
                        <p style="font-size:13px;color:#374151;margin:0;">¿Estás seguro de eliminar el grupo <strong id="group-delete-name"></strong>?</p>
                        <p id="group-delete-warning" style="font-size:11px;color:#dc2626;margin:8px 0 0;display:none;"></p>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
                        <button onclick="closeGroupDelete()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                        <button id="group-delete-btn" onclick="confirmDeleteGroup()" style="padding:8px 16px;background:#dc2626;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Eliminar</button>
                    </div>
                </div>
            </div>
                    @elseif($page->url === 'sub-grupos')
            <section id="dash-sub-grupos" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                    <button onclick="openSubGroupModal()" class="btn-orange" style="background:#D24C19;color:white;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Nuevo Sub Grupo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:12px;">
                    <input type="text" id="subgroup-search" placeholder="Buscar sub grupo..." oninput="filterSubGroups()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;" />
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
                                <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Grupo</th>
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

            <div id="subgroup-modal-overlay" onclick="if(event.target===this)closeSubGroupModal()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
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

            <div id="subgroup-delete-overlay" onclick="if(event.target===this)closeSubGroupDelete()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
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
                    @elseif($page->url === 'paginas')
            <section id="dash-paginas" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                    <button onclick="openPageModal()" class="btn-orange" style="background:#D24C19;color:white;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Nueva Página">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:12px;">
                    <input type="text" id="page-search" placeholder="Buscar página..." oninput="filterPages()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;" />
                </div>

                <div style="background:white;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;">
                    <div id="pages-loading" style="padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">Cargando páginas...</p>
                    </div>
                    <div id="pages-empty" style="display:none;padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">No se encontraron páginas.</p>
                    </div>
                    <div id="pages-table-wrap" style="display:none;overflow-x:auto;">
                    <table id="pages-table" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Descripción</th>
                                <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Módulo</th>
                                <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="pages-tbody"></tbody>
                    </table>
                    </div>
                </div>
            </section>

            <div id="page-modal-overlay" onclick="if(event.target===this)closePageModal()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:420px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 id="page-modal-title" style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Nueva Página</h3>
                        <button onclick="closePageModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <form id="page-form" onsubmit="submitPage(event)" style="padding:20px;">
                        @csrf
                        <input type="hidden" id="page-id" value="" />
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Descripción <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="page-description" maxlength="255" placeholder="Descripción de la página" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            <p id="page-description-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Módulo <span style="color:#dc2626;">*</span></label>
                            <select id="page-module-id" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:#fff;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'">
                                <option value="">-- Seleccionar módulo --</option>
                            </select>
                            <p id="page-module-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">URL</label>
                            <input type="text" id="page-url" maxlength="255" placeholder="url-de-la-pagina" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            <p id="page-url-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                            <button type="button" onclick="closePageModal()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                            <button type="submit" id="page-submit-btn" class="btn-orange" style="padding:8px 16px;background:#D24C19;border:1px solid #D24C19;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="page-delete-overlay" onclick="if(event.target===this)closePageDelete()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:380px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Confirmar Eliminación</h3>
                    </div>
                    <div style="padding:20px;">
                        <p style="font-size:13px;color:#374151;margin:0;">¿Estás seguro de eliminar la página <strong id="page-delete-name"></strong>?</p>
                        <p id="page-delete-warning" style="font-size:11px;color:#dc2626;margin:8px 0 0;display:none;"></p>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
                        <button onclick="closePageDelete()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                        <button id="page-delete-btn" onclick="confirmDeletePage()" style="padding:8px 16px;background:#dc2626;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Eliminar</button>
                    </div>
                </div>
            </div>
                    @elseif($page->url === 'tipo-usuarios')
            <section id="dash-tipo-usuarios" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                    <button onclick="openTypeUserModal()" class="btn-orange" style="background:#D24C19;color:white;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Nuevo Tipo de Usuario">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:12px;">
                    <input type="text" id="typeuser-search" placeholder="Buscar tipo de usuario..." oninput="filterTypeUsers()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;" />
                </div>

                <div style="background:white;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;">
                    <div id="typeusers-loading" style="padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">Cargando tipos de usuario...</p>
                    </div>
                    <div id="typeusers-empty" style="display:none;padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">No se encontraron tipos de usuario.</p>
                    </div>
                    <div id="typeusers-table-wrap" style="display:none;overflow-x:auto;">
                    <table id="typeusers-table" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Descripción</th>
                                <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="typeusers-tbody"></tbody>
                    </table>
                    </div>
                </div>
            </section>

            <div id="typeuser-modal-overlay" onclick="if(event.target===this)closeTypeUserModal()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:420px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 id="typeuser-modal-title" style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Nuevo Tipo de Usuario</h3>
                        <button onclick="closeTypeUserModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <form id="typeuser-form" onsubmit="submitTypeUser(event)" style="padding:20px;">
                        @csrf
                        <input type="hidden" id="typeuser-id" value="" />
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Descripción <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="typeuser-description" maxlength="255" placeholder="Tipo de usuario" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            <p id="typeuser-description-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                            <button type="button" onclick="closeTypeUserModal()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                            <button type="submit" id="typeuser-submit-btn" class="btn-orange" style="padding:8px 16px;background:#D24C19;border:1px solid #D24C19;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="typeuser-delete-overlay" onclick="if(event.target===this)closeTypeUserDelete()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:380px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Confirmar Eliminación</h3>
                    </div>
                    <div style="padding:20px;">
                        <p style="font-size:13px;color:#374151;margin:0;">¿Estás seguro de eliminar el tipo de usuario <strong id="typeuser-delete-name"></strong>?</p>
                        <p id="typeuser-delete-warning" style="font-size:11px;color:#dc2626;margin:8px 0 0;display:none;"></p>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
                        <button onclick="closeTypeUserDelete()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                        <button id="typeuser-delete-btn" onclick="confirmDeleteTypeUser()" style="padding:8px 16px;background:#dc2626;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Eliminar</button>
                    </div>
                </div>
            </div>
                    @elseif($page->url === 'estados-usuarios')
            <section id="dash-estados-usuarios" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
                    <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                    <button onclick="openUserStatusModal()" class="btn-orange" style="background:#D24C19;color:white;border:1px solid #D24C19;padding:8px 12px;border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;" title="Nuevo Estado">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:12px;">
                    <input type="text" id="userstatus-search" placeholder="Buscar estado..." oninput="filterUserStatuses()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;" />
                </div>

                <div style="background:white;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;">
                    <div id="userstatuses-loading" style="padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">Cargando estados...</p>
                    </div>
                    <div id="userstatuses-empty" style="display:none;padding:40px;text-align:center;">
                        <p style="font-size:13px;color:#6b7280;">No se encontraron estados.</p>
                    </div>
                    <div id="userstatuses-table-wrap" style="display:none;overflow-x:auto;">
                    <table id="userstatuses-table" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                                <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Estado</th>
                                <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="userstatuses-tbody"></tbody>
                    </table>
                    </div>
                </div>
            </section>

            <div id="userstatus-modal-overlay" onclick="if(event.target===this)closeUserStatusModal()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:420px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
                        <h3 id="userstatus-modal-title" style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Nuevo Estado</h3>
                        <button onclick="closeUserStatusModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:18px;line-height:1;">&times;</button>
                    </div>
                    <form id="userstatus-form" onsubmit="submitUserStatus(event)" style="padding:20px;">
                        @csrf
                        <input type="hidden" id="userstatus-id" value="" />
                        <div style="margin-bottom:16px;">
                            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Estado <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="userstatus-status" maxlength="255" placeholder="Nombre del estado" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            <p id="userstatus-status-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
                        </div>
                        <div style="display:flex;gap:8px;justify-content:flex-end;">
                            <button type="button" onclick="closeUserStatusModal()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                            <button type="submit" id="userstatus-submit-btn" class="btn-orange" style="padding:8px 16px;background:#D24C19;border:1px solid #D24C19;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="userstatus-delete-overlay" onclick="if(event.target===this)closeUserStatusDelete()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:200;align-items:center;justify-content:center;">
                <div style="background:white;border-radius:0;width:100%;max-width:380px;margin:20px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                        <h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Confirmar Eliminación</h3>
                    </div>
                    <div style="padding:20px;">
                        <p style="font-size:13px;color:#374151;margin:0;">¿Estás seguro de eliminar el estado <strong id="userstatus-delete-name"></strong>?</p>
                        <p id="userstatus-delete-warning" style="font-size:11px;color:#dc2626;margin:8px 0 0;display:none;"></p>
                    </div>
                    <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
                        <button onclick="closeUserStatusDelete()" style="padding:8px 16px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;color:#374151;">Cancelar</button>
                        <button id="userstatus-delete-btn" onclick="confirmDeleteUserStatus()" style="padding:8px 16px;background:#dc2626;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:white;">Eliminar</button>
                    </div>
                </div>
            </div>
                    @else
            <section id="dash-{{ $page->url }}" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin-bottom:12px;">{{ $page->description }}</h2>
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0;padding:40px;text-align:center;">
                    <p style="font-size:14px;color:#6b7280;margin:0;">Próximamente podrás gestionar {{ strtolower($page->description) }} aquí.</p>
                </div>
            </section>
                    @endif
                @endforeach
            @endforeach

        </main>

        <div style="background:white;height:8px;"></div>
        <footer style="background:#0a0f1a;color:white;padding:14px 0;">
            <div style="text-align:center;">
                <p style="font-size:11px;color:#d1d5db;margin:0;">Gastronomía, Hogar, Auxilio Vial y Abastos.</p>
                <p style="font-size:10px;color:#9ca3af;margin:0;">&copy; {{ date('Y') }} pideaca.com - Todos los derechos reservados.</p>
            </div>
        </footer>

    </body>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; }

    .header-btn:hover { background: rgba(255,255,255,0.1) !important; color: #ffffff !important; border-color: #ffffff !important; }
    .nav-link {
        position: relative;
        text-decoration: none;
        transition: transform 0.2s ease, text-shadow 0.2s ease;
    }
    .nav-link:hover {
        background: rgba(255,255,255,0.1);
        transform: scale(1.1) translateZ(10px);
        text-shadow: 0 2px 8px rgba(255,255,255,0.4);
    }
    .nav-dropdown:hover .nav-dropdown-menu { /* handled by JS */ }
    .dropdown-item {
        text-decoration: none;
    }
    .dropdown-item:hover {
        background: #f3f4f6;
    }

    #groups-table tbody tr:hover,
    #subgroups-table tbody tr:hover,
    #modules-table tbody tr:hover,
    #userstatuses-table tbody tr:hover,
    #typeusers-table tbody tr:hover,
    #pages-table tbody tr:hover {
        background: #f9fafb;
    }

    .btn-orange { transition: background 0.2s, color 0.2s, border-color 0.2s; }
    .btn-orange:hover {
        background: #ffffff !important;
        color: #D24C19 !important;
        border: 1px solid #D24C19 !important;
    }
    .pagination-nav-btn:hover {
        background: #f3f4f6 !important;
        border-color: #9ca3af !important;
    }

    #module-modal-overlay[style*="display: flex"],
    #module-delete-overlay[style*="display: flex"] {
        backdrop-filter: blur(2px);
    }

    @media (max-width: 768px) {
        .nav-link, .nav-separator, .nav-dropdown { display: none !important; }
        #menu-toggle { display: block !important; }
        .logo-img { width: 140px !important; height: 52px !important; }
        .nav-container { padding: 1px 8px !important; justify-content: space-between !important; }
    }
    @media (min-width: 769px) {
        #mobile-menu { display: none !important; }
    }
</style>
<script>
    history.replaceState(null, '', '{{ url("/") }}');

    function toggleMenu() {
        var menu = document.getElementById('mobile-menu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    function closeDropdowns() {
        document.querySelectorAll('.nav-dropdown-menu').forEach(function (m) { m.style.display = 'none'; });
    }

    document.querySelectorAll('.nav-dropdown').forEach(function (dd) {
        var menu = dd.querySelector('.nav-dropdown-menu');
        if (!menu) return;
        dd.addEventListener('mouseenter', function () { menu.style.display = 'block'; });
        dd.addEventListener('mouseleave', function () { menu.style.display = 'none'; });
    });

    function toggleMobileSub(btn) {
        var sub = btn.nextElementSibling;
        var svg = btn.querySelector('svg');
        if (sub.style.display === 'none') {
            sub.style.display = 'block';
            if (svg) { svg.style.transition = 'transform 0.2s ease'; svg.style.transform = 'rotate(180deg)'; }
        } else {
            sub.style.display = 'none';
            if (svg) { svg.style.transition = 'transform 0.2s ease'; svg.style.transform = 'rotate(0deg)'; }
        }
    }

    function showDashSection(key) {
        var sections = document.querySelectorAll('.dash-section');
        sections.forEach(function (s) { s.style.display = 'none'; });
        var target = document.getElementById('dash-' + key);
        if (target) target.style.display = 'block';
        history.replaceState(null, '', '{{ url('/') }}');
        closeDropdowns();
        var menu = document.getElementById('mobile-menu');
        if (menu) menu.style.display = 'none';
    }

    function doLogout() {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ url("/logout") }}';
        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }

    var allModules = [];
    var deleteModuleId = null;

    function loadModules() {
        document.getElementById('modules-loading').style.display = 'block';
        document.getElementById('modules-empty').style.display = 'none';
        document.getElementById('modules-table-wrap').style.display = 'none';

        fetch('{{ url("/modules") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allModules = data;
            renderModules(data);
        })
        .catch(function() {
            document.getElementById('modules-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar módulos.</p>';
        });
    }

    function renderModules(modules) {
        var tbody = document.getElementById('modules-tbody');
        var loading = document.getElementById('modules-loading');
        var empty = document.getElementById('modules-empty');
        var tableWrap = document.getElementById('modules-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (modules.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        modules.forEach(function(m, i) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', m.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(m.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editModule(' + m.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openModuleDelete(' + m.id + ', \'' + escapeHtml(m.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterModules() {
        var q = document.getElementById('module-search').value.toLowerCase();
        var filtered = allModules.filter(function(m) {
            return m.description.toLowerCase().indexOf(q) !== -1;
        });
        renderModules(filtered);
    }

    function openModuleModal(id, description, icon) {
        document.getElementById('module-id').value = id || '';
        document.getElementById('module-description').value = description || '';
        document.getElementById('module-icon').value = icon || '';
        document.getElementById('module-description-error').style.display = 'none';
        document.getElementById('module-modal-title').textContent = id ? 'Editar Módulo' : 'Nuevo Módulo';
        document.getElementById('module-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('module-modal-overlay').style.display = 'flex';
        document.getElementById('module-description').focus();
    }

    function closeModuleModal() {
        document.getElementById('module-modal-overlay').style.display = 'none';
    }

    function editModule(id) {
        var m = allModules.find(function(mod) { return mod.id === id; });
        if (m) openModuleModal(m.id, m.description, m.icon);
    }

    function submitModule(e) {
        e.preventDefault();
        var id = document.getElementById('module-id').value;
        var desc = document.getElementById('module-description').value.trim();
        var iconVal = document.getElementById('module-icon').value.trim();
        var errorEl = document.getElementById('module-description-error');
        var submitBtn = document.getElementById('module-submit-btn');

        errorEl.style.display = 'none';

        if (!desc) {
            errorEl.textContent = 'La descripción es obligatoria.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? '{{ url("/modules") }}/' + id : '{{ url("/modules") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: desc, icon: iconVal })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var msg = data.errors.description ? data.errors.description[0] : 'Error de validación.';
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeModuleModal();
                loadModules();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openModuleDelete(id, name) {
        deleteModuleId = id;
        document.getElementById('module-delete-name').textContent = name;
        document.getElementById('module-delete-warning').style.display = 'none';
        document.getElementById('module-delete-overlay').style.display = 'flex';
    }

    function closeModuleDelete() {
        document.getElementById('module-delete-overlay').style.display = 'none';
        deleteModuleId = null;
    }

    function confirmDeleteModule() {
        if (!deleteModuleId) return;
        var btn = document.getElementById('module-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch('{{ url("/modules") }}/' + deleteModuleId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeModuleDelete();
                loadModules();
            } else {
                var warn = document.getElementById('module-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('module-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    var allGroups = [];
    var deleteGroupId = null;

    function loadGroups() {
        document.getElementById('groups-loading').style.display = 'block';
        document.getElementById('groups-empty').style.display = 'none';
        document.getElementById('groups-table-wrap').style.display = 'none';

        fetch('{{ url("/groups") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allGroups = data;
            renderGroups(data);
        })
        .catch(function() {
            document.getElementById('groups-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar grupos.</p>';
        });
    }

    function renderGroups(groups) {
        var tbody = document.getElementById('groups-tbody');
        var loading = document.getElementById('groups-loading');
        var empty = document.getElementById('groups-empty');
        var tableWrap = document.getElementById('groups-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (groups.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        groups.forEach(function(g, i) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', g.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(g.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editGroup(' + g.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openGroupDelete(' + g.id + ', \'' + escapeHtml(g.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterGroups() {
        var q = document.getElementById('group-search').value.toLowerCase();
        var filtered = allGroups.filter(function(g) {
            return g.description.toLowerCase().indexOf(q) !== -1;
        });
        renderGroups(filtered);
    }

    function openGroupModal(id, description, icon) {
        document.getElementById('group-id').value = id || '';
        document.getElementById('group-description').value = description || '';
        document.getElementById('group-icon').value = icon || '';
        document.getElementById('group-description-error').style.display = 'none';
        document.getElementById('group-modal-title').textContent = id ? 'Editar Grupo' : 'Nuevo Grupo';
        document.getElementById('group-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('group-modal-overlay').style.display = 'flex';
        document.getElementById('group-description').focus();
    }

    function closeGroupModal() {
        document.getElementById('group-modal-overlay').style.display = 'none';
    }

    function editGroup(id) {
        var g = allGroups.find(function(grp) { return grp.id === id; });
        if (g) openGroupModal(g.id, g.description, g.icon);
    }

    function submitGroup(e) {
        e.preventDefault();
        var id = document.getElementById('group-id').value;
        var desc = document.getElementById('group-description').value.trim();
        var iconVal = document.getElementById('group-icon').value.trim();
        var errorEl = document.getElementById('group-description-error');
        var submitBtn = document.getElementById('group-submit-btn');

        errorEl.style.display = 'none';

        if (!desc) {
            errorEl.textContent = 'La descripción es obligatoria.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? '{{ url("/groups") }}/' + id : '{{ url("/groups") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: desc, icon: iconVal })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var msg = data.errors.description ? data.errors.description[0] : 'Error de validación.';
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeGroupModal();
                loadGroups();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openGroupDelete(id, name) {
        deleteGroupId = id;
        document.getElementById('group-delete-name').textContent = name;
        document.getElementById('group-delete-warning').style.display = 'none';
        document.getElementById('group-delete-overlay').style.display = 'flex';
    }

    function closeGroupDelete() {
        document.getElementById('group-delete-overlay').style.display = 'none';
        deleteGroupId = null;
    }

    function confirmDeleteGroup() {
        if (!deleteGroupId) return;
        var btn = document.getElementById('group-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch('{{ url("/groups") }}/' + deleteGroupId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeGroupDelete();
                loadGroups();
            } else {
                var warn = document.getElementById('group-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('group-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    var allSubGroups = [];
    var allSubGroupGroups = [];
    var deleteSubGroupId = null;
    var subgroupCurrentPage = 1;
    var subgroupPerPage = 10;

    function loadSubGroupGroups(callback) {
        fetch('{{ url("/groups") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allSubGroupGroups = data;
            var select = document.getElementById('subgroup-group-id');
            select.innerHTML = '<option value="">-- Seleccionar grupo --</option>';
            data.forEach(function(g) {
                var opt = document.createElement('option');
                opt.value = g.id;
                opt.textContent = g.description;
                select.appendChild(opt);
            });
            if (callback) callback();
        })
        .catch(function() {
            if (callback) callback();
        });
    }

    function loadSubGroups() {
        subgroupCurrentPage = 1;
        document.getElementById('subgroups-loading').style.display = 'block';
        document.getElementById('subgroups-empty').style.display = 'none';
        document.getElementById('subgroups-table-wrap').style.display = 'none';

        fetch('{{ url("/subgroups") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allSubGroups = data;
            allSubGroups._filtered = data;
            renderSubGroups(data);
        })
        .catch(function() {
            document.getElementById('subgroups-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar sub grupos.</p>';
        });
    }

    function renderSubGroups(subgroups) {
        var tbody = document.getElementById('subgroups-tbody');
        var loading = document.getElementById('subgroups-loading');
        var empty = document.getElementById('subgroups-empty');
        var tableWrap = document.getElementById('subgroups-table-wrap');
        var pagination = document.getElementById('subgroup-pagination');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (subgroups.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            pagination.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        var totalPages = Math.ceil(subgroups.length / subgroupPerPage);
        if (subgroupCurrentPage > totalPages) subgroupCurrentPage = totalPages;
        if (subgroupCurrentPage < 1) subgroupCurrentPage = 1;

        var start = (subgroupCurrentPage - 1) * subgroupPerPage;
        var end = start + subgroupPerPage;
        var pageItems = subgroups.slice(start, end);

        pageItems.forEach(function(s) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', s.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            var groupName = s.group ? escapeHtml(s.group.description) : '<span style="color:#9ca3af;">—</span>';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(s.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;font-size:13px;color:#6b7280;">' + groupName + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editSubGroup(' + s.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openSubGroupDelete(' + s.id + ', \'' + escapeHtml(s.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });

        if (totalPages <= 1) { pagination.style.display = 'none'; return; }
        pagination.style.display = 'flex';

        var dots = document.getElementById('subgroup-page-dots');
        dots.innerHTML = '';
        for (var p = 1; p <= totalPages; p++) {
            var d = document.createElement('span');
            d.textContent = p;
            d.style.cssText = 'padding:2px 6px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;' + (p === subgroupCurrentPage ? 'color:#D24C19;' : 'color:#9ca3af;');
            d.onmouseenter = function () { if (parseInt(this.textContent) !== subgroupCurrentPage) this.style.color = '#374151'; };
            d.onmouseleave = function () { if (parseInt(this.textContent) !== subgroupCurrentPage) this.style.color = '#9ca3af'; };
            d.onclick = function () { subgroupCurrentPage = parseInt(this.textContent); renderSubGroups(allSubGroups._filtered || allSubGroups); };
            dots.appendChild(d);
        }
        document.getElementById('subgroup-prev-page').style.opacity = subgroupCurrentPage === 1 ? '0.4' : '1';
        document.getElementById('subgroup-prev-page').style.pointerEvents = subgroupCurrentPage === 1 ? 'none' : 'auto';
        document.getElementById('subgroup-next-page').style.opacity = subgroupCurrentPage === totalPages ? '0.4' : '1';
        document.getElementById('subgroup-next-page').style.pointerEvents = subgroupCurrentPage === totalPages ? 'none' : 'auto';
    }

    function subGroupPage(dir) {
        var data = allSubGroups._filtered || allSubGroups;
        var totalPages = Math.ceil(data.length / subgroupPerPage);
        if (dir === 1 && subgroupCurrentPage > 1) { subgroupCurrentPage--; renderSubGroups(data); }
        if (dir === 2 && subgroupCurrentPage < totalPages) { subgroupCurrentPage++; renderSubGroups(data); }
    }

    function filterSubGroups() {
        subgroupCurrentPage = 1;
        var q = document.getElementById('subgroup-search').value.toLowerCase();
        var filtered = allSubGroups.filter(function(s) {
            return s.description.toLowerCase().indexOf(q) !== -1;
        });
        allSubGroups._filtered = filtered;
        renderSubGroups(filtered);
    }

    function openSubGroupModal(id, description, groupId) {
        document.getElementById('subgroup-id').value = id || '';
        document.getElementById('subgroup-description').value = description || '';
        document.getElementById('subgroup-description-error').style.display = 'none';
        document.getElementById('subgroup-group-error').style.display = 'none';
        document.getElementById('subgroup-modal-title').textContent = id ? 'Editar Sub Grupo' : 'Nuevo Sub Grupo';
        document.getElementById('subgroup-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';

        var open = function() {
            if (groupId) {
                document.getElementById('subgroup-group-id').value = groupId;
            } else {
                document.getElementById('subgroup-group-id').value = '';
            }
            document.getElementById('subgroup-modal-overlay').style.display = 'flex';
            document.getElementById('subgroup-description').focus();
        };

        if (allSubGroupGroups.length === 0) {
            loadSubGroupGroups(open);
        } else {
            open();
        }
    }

    function closeSubGroupModal() {
        document.getElementById('subgroup-modal-overlay').style.display = 'none';
    }

    function editSubGroup(id) {
        var s = allSubGroups.find(function(sub) { return sub.id === id; });
        if (s) openSubGroupModal(s.id, s.description, s.group_id);
    }

    function submitSubGroup(e) {
        e.preventDefault();
        var id = document.getElementById('subgroup-id').value;
        var desc = document.getElementById('subgroup-description').value.trim();
        var groupId = document.getElementById('subgroup-group-id').value;
        var descError = document.getElementById('subgroup-description-error');
        var groupError = document.getElementById('subgroup-group-error');
        var submitBtn = document.getElementById('subgroup-submit-btn');

        descError.style.display = 'none';
        groupError.style.display = 'none';

        if (!desc) {
            descError.textContent = 'La descripción es obligatoria.';
            descError.style.display = 'block';
            return;
        }

        if (!groupId) {
            groupError.textContent = 'Debe seleccionar un grupo.';
            groupError.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? '{{ url("/subgroups") }}/' + id : '{{ url("/subgroups") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: desc, group_id: groupId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                if (data.errors.description) {
                    descError.textContent = data.errors.description[0];
                    descError.style.display = 'block';
                }
                if (data.errors.group_id) {
                    groupError.textContent = data.errors.group_id[0];
                    groupError.style.display = 'block';
                }
                return;
            }

            if (data.success) {
                closeSubGroupModal();
                loadSubGroups();
            } else {
                descError.textContent = data.message || 'Error al guardar.';
                descError.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            descError.textContent = 'Error de conexión.';
            descError.style.display = 'block';
        });
    }

    function openSubGroupDelete(id, name) {
        deleteSubGroupId = id;
        document.getElementById('subgroup-delete-name').textContent = name;
        document.getElementById('subgroup-delete-warning').style.display = 'none';
        document.getElementById('subgroup-delete-overlay').style.display = 'flex';
    }

    function closeSubGroupDelete() {
        document.getElementById('subgroup-delete-overlay').style.display = 'none';
        deleteSubGroupId = null;
    }

    function confirmDeleteSubGroup() {
        if (!deleteSubGroupId) return;
        var btn = document.getElementById('subgroup-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch('{{ url("/subgroups") }}/' + deleteSubGroupId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeSubGroupDelete();
                loadSubGroups();
            } else {
                var warn = document.getElementById('subgroup-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('subgroup-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function() {
        var origShow = showDashSection;
        showDashSection = function(key) {
            origShow(key);
            if (key === 'modulos') loadModules();
            if (key === 'grupos') loadGroups();
            if (key === 'sub-grupos') loadSubGroups();
            if (key === 'estados-usuarios') loadUserStatuses();
            if (key === 'tipo-usuarios') loadTypeUsers();
            if (key === 'paginas') loadPages();
        };
    });

    var allPages = [];
    var allPageModules = [];
    var deletePageId = null;

    function loadPageModules(callback) {
        fetch('{{ url("/modules") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allPageModules = data;
            var select = document.getElementById('page-module-id');
            select.innerHTML = '<option value="">-- Seleccionar módulo --</option>';
            data.forEach(function(m) {
                var opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = m.description;
                select.appendChild(opt);
            });
            if (callback) callback();
        })
        .catch(function() {
            if (callback) callback();
        });
    }

    function loadPages() {
        document.getElementById('pages-loading').style.display = 'block';
        document.getElementById('pages-empty').style.display = 'none';
        document.getElementById('pages-table-wrap').style.display = 'none';

        fetch('{{ url("/pages") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allPages = data;
            renderPages(data);
        })
        .catch(function() {
            document.getElementById('pages-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar páginas.</p>';
        });
    }

    function renderPages(pages) {
        var tbody = document.getElementById('pages-tbody');
        var loading = document.getElementById('pages-loading');
        var empty = document.getElementById('pages-empty');
        var tableWrap = document.getElementById('pages-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (pages.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        pages.forEach(function(p) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', p.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            var moduleName = p.module ? escapeHtml(p.module.description) : '<span style="color:#9ca3af;">—</span>';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(p.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;font-size:13px;color:#6b7280;">' + moduleName + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editPage(' + p.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openPageDelete(' + p.id + ', \'' + escapeHtml(p.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterPages() {
        var q = document.getElementById('page-search').value.toLowerCase();
        var filtered = allPages.filter(function(p) {
            return (p.description && p.description.toLowerCase().indexOf(q) !== -1) ||
                   (p.module && p.module.description.toLowerCase().indexOf(q) !== -1);
        });
        renderPages(filtered);
    }

    function openPageModal(id) {
        var p = id ? allPages.find(function(pg) { return pg.id === id; }) : null;
        document.getElementById('page-id').value = id || '';
        document.getElementById('page-description').value = p ? p.description : '';
        document.getElementById('page-url').value = p && p.url ? p.url : '';
        document.getElementById('page-description-error').style.display = 'none';
        document.getElementById('page-module-error').style.display = 'none';
        document.getElementById('page-url-error').style.display = 'none';
        document.getElementById('page-modal-title').textContent = id ? 'Editar Página' : 'Nueva Página';
        document.getElementById('page-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';

        var open = function() {
            if (p && p.module_id) {
                document.getElementById('page-module-id').value = p.module_id;
            }
            document.getElementById('page-modal-overlay').style.display = 'flex';
            document.getElementById('page-description').focus();
        };

        if (allPageModules.length === 0) {
            loadPageModules(open);
        } else {
            open();
        }
    }

    function closePageModal() {
        document.getElementById('page-modal-overlay').style.display = 'none';
    }

    function editPage(id) {
        openPageModal(id);
    }

    function submitPage(e) {
        e.preventDefault();
        var id = document.getElementById('page-id').value;
        var desc = document.getElementById('page-description').value.trim();
        var urlVal = document.getElementById('page-url').value.trim();
        var moduleId = document.getElementById('page-module-id').value;
        var descError = document.getElementById('page-description-error');
        var moduleError = document.getElementById('page-module-error');
        var submitBtn = document.getElementById('page-submit-btn');

        descError.style.display = 'none';
        moduleError.style.display = 'none';

        if (!desc) {
            descError.textContent = 'La descripción es obligatoria.';
            descError.style.display = 'block';
            return;
        }

        if (!moduleId) {
            moduleError.textContent = 'Seleccione un módulo.';
            moduleError.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var apiUrl = id ? '{{ url("/pages") }}/' + id : '{{ url("/pages") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(apiUrl, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: desc, url: urlVal, module_id: moduleId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var msg = data.errors.description ? data.errors.description[0] :
                          data.errors.module_id ? data.errors.module_id[0] :
                          data.errors.url ? data.errors.url[0] : 'Error de validación.';
                if (data.errors.module_id) {
                    moduleError.textContent = msg;
                    moduleError.style.display = 'block';
                } else {
                    descError.textContent = msg;
                    descError.style.display = 'block';
                }
                return;
            }

            if (data.success) {
                closePageModal();
                loadPages();
            } else {
                descError.textContent = data.message || 'Error al guardar.';
                descError.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            descError.textContent = 'Error de conexión.';
            descError.style.display = 'block';
        });
    }

    function openPageDelete(id, name) {
        deletePageId = id;
        document.getElementById('page-delete-name').textContent = name;
        document.getElementById('page-delete-warning').style.display = 'none';
        document.getElementById('page-delete-overlay').style.display = 'flex';
    }

    function closePageDelete() {
        document.getElementById('page-delete-overlay').style.display = 'none';
        deletePageId = null;
    }

    function confirmDeletePage() {
        if (!deletePageId) return;
        var btn = document.getElementById('page-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch('{{ url("/pages") }}/' + deletePageId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closePageDelete();
                loadPages();
            } else {
                var warn = document.getElementById('page-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('page-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    var allTypeUsers = [];
    var deleteTypeUserId = null;

    function loadTypeUsers() {
        document.getElementById('typeusers-loading').style.display = 'block';
        document.getElementById('typeusers-empty').style.display = 'none';
        document.getElementById('typeusers-table-wrap').style.display = 'none';

        fetch('{{ url("/type-users") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allTypeUsers = data;
            renderTypeUsers(data);
        })
        .catch(function() {
            document.getElementById('typeusers-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar tipos de usuario.</p>';
        });
    }

    function renderTypeUsers(typeUsers) {
        var tbody = document.getElementById('typeusers-tbody');
        var loading = document.getElementById('typeusers-loading');
        var empty = document.getElementById('typeusers-empty');
        var tableWrap = document.getElementById('typeusers-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (typeUsers.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        typeUsers.forEach(function(t) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', t.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(t.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editTypeUser(' + t.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openTypeUserDelete(' + t.id + ', \'' + escapeHtml(t.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterTypeUsers() {
        var q = document.getElementById('typeuser-search').value.toLowerCase();
        var filtered = allTypeUsers.filter(function(t) {
            return t.description.toLowerCase().indexOf(q) !== -1;
        });
        renderTypeUsers(filtered);
    }

    function openTypeUserModal(id, description) {
        document.getElementById('typeuser-id').value = id || '';
        document.getElementById('typeuser-description').value = description || '';
        document.getElementById('typeuser-description-error').style.display = 'none';
        document.getElementById('typeuser-modal-title').textContent = id ? 'Editar Tipo de Usuario' : 'Nuevo Tipo de Usuario';
        document.getElementById('typeuser-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('typeuser-modal-overlay').style.display = 'flex';
        document.getElementById('typeuser-description').focus();
    }

    function closeTypeUserModal() {
        document.getElementById('typeuser-modal-overlay').style.display = 'none';
    }

    function editTypeUser(id) {
        var t = allTypeUsers.find(function(tu) { return tu.id === id; });
        if (t) openTypeUserModal(t.id, t.description);
    }

    function submitTypeUser(e) {
        e.preventDefault();
        var id = document.getElementById('typeuser-id').value;
        var desc = document.getElementById('typeuser-description').value.trim();
        var errorEl = document.getElementById('typeuser-description-error');
        var submitBtn = document.getElementById('typeuser-submit-btn');

        errorEl.style.display = 'none';

        if (!desc) {
            errorEl.textContent = 'La descripción es obligatoria.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? '{{ url("/type-users") }}/' + id : '{{ url("/type-users") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: desc })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var msg = data.errors.description ? data.errors.description[0] : 'Error de validación.';
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeTypeUserModal();
                loadTypeUsers();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openTypeUserDelete(id, name) {
        deleteTypeUserId = id;
        document.getElementById('typeuser-delete-name').textContent = name;
        document.getElementById('typeuser-delete-warning').style.display = 'none';
        document.getElementById('typeuser-delete-overlay').style.display = 'flex';
    }

    function closeTypeUserDelete() {
        document.getElementById('typeuser-delete-overlay').style.display = 'none';
        deleteTypeUserId = null;
    }

    function confirmDeleteTypeUser() {
        if (!deleteTypeUserId) return;
        var btn = document.getElementById('typeuser-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch('{{ url("/type-users") }}/' + deleteTypeUserId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeTypeUserDelete();
                loadTypeUsers();
            } else {
                var warn = document.getElementById('typeuser-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('typeuser-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    var allUserStatuses = [];
    var deleteUserStatusId = null;

    function loadUserStatuses() {
        document.getElementById('userstatuses-loading').style.display = 'block';
        document.getElementById('userstatuses-empty').style.display = 'none';
        document.getElementById('userstatuses-table-wrap').style.display = 'none';

        fetch('{{ url("/user-statuses") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allUserStatuses = data;
            renderUserStatuses(data);
        })
        .catch(function() {
            document.getElementById('userstatuses-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar estados.</p>';
        });
    }

    function renderUserStatuses(statuses) {
        var tbody = document.getElementById('userstatuses-tbody');
        var loading = document.getElementById('userstatuses-loading');
        var empty = document.getElementById('userstatuses-empty');
        var tableWrap = document.getElementById('userstatuses-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (statuses.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        statuses.forEach(function(s) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', s.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(s.status) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editUserStatus(' + s.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openUserStatusDelete(' + s.id + ', \'' + escapeHtml(s.status).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterUserStatuses() {
        var q = document.getElementById('userstatus-search').value.toLowerCase();
        var filtered = allUserStatuses.filter(function(s) {
            return s.status.toLowerCase().indexOf(q) !== -1;
        });
        renderUserStatuses(filtered);
    }

    function openUserStatusModal(id, status) {
        document.getElementById('userstatus-id').value = id || '';
        document.getElementById('userstatus-status').value = status || '';
        document.getElementById('userstatus-status-error').style.display = 'none';
        document.getElementById('userstatus-modal-title').textContent = id ? 'Editar Estado' : 'Nuevo Estado';
        document.getElementById('userstatus-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('userstatus-modal-overlay').style.display = 'flex';
        document.getElementById('userstatus-status').focus();
    }

    function closeUserStatusModal() {
        document.getElementById('userstatus-modal-overlay').style.display = 'none';
    }

    function editUserStatus(id) {
        var s = allUserStatuses.find(function(st) { return st.id === id; });
        if (s) openUserStatusModal(s.id, s.status);
    }

    function submitUserStatus(e) {
        e.preventDefault();
        var id = document.getElementById('userstatus-id').value;
        var statusVal = document.getElementById('userstatus-status').value.trim();
        var errorEl = document.getElementById('userstatus-status-error');
        var submitBtn = document.getElementById('userstatus-submit-btn');

        errorEl.style.display = 'none';

        if (!statusVal) {
            errorEl.textContent = 'El estado es obligatorio.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? '{{ url("/user-statuses") }}/' + id : '{{ url("/user-statuses") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: statusVal })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var msg = data.errors.status ? data.errors.status[0] : 'Error de validación.';
                errorEl.textContent = msg;
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeUserStatusModal();
                loadUserStatuses();
            } else {
                errorEl.textContent = data.message || 'Error al guardar.';
                errorEl.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            errorEl.textContent = 'Error de conexión.';
            errorEl.style.display = 'block';
        });
    }

    function openUserStatusDelete(id, name) {
        deleteUserStatusId = id;
        document.getElementById('userstatus-delete-name').textContent = name;
        document.getElementById('userstatus-delete-warning').style.display = 'none';
        document.getElementById('userstatus-delete-overlay').style.display = 'flex';
    }

    function closeUserStatusDelete() {
        document.getElementById('userstatus-delete-overlay').style.display = 'none';
        deleteUserStatusId = null;
    }

    function confirmDeleteUserStatus() {
        if (!deleteUserStatusId) return;
        var btn = document.getElementById('userstatus-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch('{{ url("/user-statuses") }}/' + deleteUserStatusId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            if (data.success) {
                closeUserStatusDelete();
                loadUserStatuses();
            } else {
                var warn = document.getElementById('userstatus-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('userstatus-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }
</script>
</html>