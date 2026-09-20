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
                        <a href="#" onclick="event.preventDefault();closeDropdowns();doLogout()" class="dropdown-item" style="display:block;padding:8px 16px;color:#dc2626;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;">
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
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
                            <div style="width:60%;height:1px;background:#e5e7eb;margin:6px auto;"></div>
                            <button type="button" onclick="doLogout()" style="background:none;border:none;color:#dc2626;font-size:13px;font-weight:600;cursor:pointer;padding:6px 0;">Cerrar Sesión</button>
                        </div>
                    @endforeach
                </div>
            </div>
        </nav>

        <main style="flex:1;">
            <div style="background:#D24C19;height:2px;"></div>

            {{-- DASHBOARD SECTIONS --}}
            <section id="dash-perfil" class="dash-section" style="display:none;width:100%;margin:0;padding:0;">
                <style>
                    .sidebar-link{display:flex;align-items:center;gap:10px;padding:10px 20px;color:#64748b;font-size:13px;font-weight:500;text-decoration:none;transition:all .15s;border-left:3px solid transparent;cursor:pointer;}
                    .sidebar-link:hover{background:#f1f5f9;color:#0f172a;}
                    .sidebar-link.active{background:#fff7ed;color:#D24C19;border-left-color:#D24C19;font-weight:600;}
                    .sidebar-link svg{flex-shrink:0;}
                    #profile-content{margin-left:250px;}
                    .profile-panel{display:none;}
                    .profile-panel.active{display:block;}
                    .pcard{background:#ffffff;border:1px solid #e2e8f0;border-radius:0;box-shadow:0 1px 2px rgba(15,23,42,0.04);padding:24px;margin-bottom:16px;}
                    .pcard-title{font-size:13px;font-weight:700;color:#0f172a;margin:0 0 4px;display:flex;align-items:center;gap:8px;}
                    .pcard-sub{font-size:11px;color:#94a3b8;margin:0 0 16px;}
                    .field-label{display:block;font-size:11px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;}
                    .field-input{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #cbd5e1;border-radius:0;font-size:13px;color:#0f172a;background:#fff;outline:none;transition:border-color .2s, box-shadow .2s;}
                    .field-input:focus{border-color:#e85d04;box-shadow:0 0 0 3px rgba(232,93,4,0.12);}
                    .field-input::placeholder{color:#94a3b8;}
                    textarea.field-input{resize:vertical;}
                    .field-input.err{border-color:#dc2626 !important;}
                    .err-msg{font-size:11px;color:#dc2626;margin-top:5px;display:none;}
                    .btn-primary{background:#D24C19;border:1px solid #D24C19;color:#fff;border-radius:4px;padding:7px 18px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;}
                    .btn-primary:hover{background:#ffffff;color:#D24C19;border-color:#D24C19;}
                    .btn-secondary{background:#fff;border:1px solid #cbd5e1;color:#334155;border-radius:4px;padding:7px 16px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;}
                    .btn-secondary:hover{background:#f8fafc;}
                    .btn-danger{background:#fff;border:1px solid #fecaca;color:#dc2626;border-radius:4px;padding:7px 16px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;}
                    .btn-danger:hover{background:#fef2f2;}
                    .save-msg{font-size:12px;font-weight:600;display:none;}
                    .save-msg.ok{color:#059669;}
                    .save-msg.err{color:#dc2626;}
                    .profile-field-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
                    .profile-field-grid .full{grid-column:1 / -1;}
                    .hour-chip input[type=checkbox]{display:none;}
                    .hour-chip span{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:26px;padding:0 6px;font-size:11px;font-weight:600;color:#64748b;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;user-select:none;transition:all .15s;}
                    .hour-chip span:hover{border-color:#94a3b8;background:#e2e8f0;}
                    .hour-chip input:checked + span{background:#e85d04;border-color:#e85d04;color:#fff;}
                    .dropzone{border:2px dashed #cbd5e1;border-radius:0;background:#f8fafc;cursor:pointer;transition:all .2s;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;min-height:140px;text-align:center;padding:16px;}
                    .dropzone:hover,.dropzone.dragover{border-color:#e85d04;background:#fff7ed;}
                    .dropzone-title{font-size:12px;font-weight:700;color:#0f172a;}
                    .dropzone-hint{font-size:11px;color:#94a3b8;}
                    .service-card{display:flex;align-items:center;justify-content:space-between;gap:12px;background:#fff;border:1px solid #e2e8f0;border-radius:0;padding:12px 16px;transition:box-shadow .2s, border-color .2s;}
                    .service-card:hover{box-shadow:0 4px 14px rgba(15,23,42,0.08);border-color:#cbd5e1;}
                    .service-index{display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:28px;border-radius:9999px;background:#0f172a;color:#fff;font-size:11px;font-weight:700;margin-right:10px;}
                    .icon-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:4px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;color:#64748b;transition:all .15s;}
                    .icon-btn.edit:hover{color:#0f172a;border-color:#0f172a;background:#f8fafc;}
                    .icon-btn.del:hover{color:#dc2626;border-color:#fecaca;background:#fef2f2;}
                    .img-type-badge{position:absolute;bottom:6px;left:6px;background:rgba(15,23,42,0.85);color:#fff;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;border-radius:0;padding:3px 7px;}
                    .img-star{position:absolute;top:6px;left:6px;color:#fbbf24;font-size:14px;}
                    .status-badge{display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#eef2f7;color:#334155;font-size:12px;font-weight:600;}
                    @media (max-width:768px){
                        .profile-field-grid{grid-template-columns:1fr;}
                        .img-zones{grid-template-columns:1fr !important;}
                        #profile-sidebar{position:fixed;top:60px;left:0;height:calc(100vh - 60px);transform:translateX(-100%);z-index:250;transition:transform .25s ease;}
                        #profile-sidebar.open{transform:translateX(0);}
                        #profile-content{margin-left:0 !important;}
                        #sidebar-toggle-btn{display:flex !important;}
                    }
                </style>

                <div style="display:flex;min-height:calc(100vh - 60px);">

                    <div id="sidebar-overlay" onclick="toggleProfileSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:240;"></div>

                    <aside id="profile-sidebar" style="width:250px;min-width:250px;background:#ffffff;color:#1e293b;display:flex;flex-direction:column;position:fixed;top:70px;left:0;z-index:260;overflow-y:auto;">
                        <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:38px;height:38px;border-radius:50%;background:#D24C19;color:white;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0;">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                <div style="min-width:0;">
                                    <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $user->name }}</div>
                                    <div style="font-size:11px;color:#94a3b8;">{{ $user->typeUser ? $user->typeUser->description : '' }}</div>
                                </div>
                            </div>
                        </div>

                        <nav style="flex:1;padding:12px 0;">
                            <a href="#" onclick="event.preventDefault();showProfilePanel('negocio')" class="sidebar-link active" data-panel="negocio" id="sidebar-link-negocio">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                                <span>Datos del Negocio</span>
                            </a>
                            <a href="#" onclick="event.preventDefault();showProfilePanel('horarios')" class="sidebar-link" data-panel="horarios" id="sidebar-link-horarios">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                <span>Horarios de Atención</span>
                            </a>
                            <a href="#" onclick="event.preventDefault();showProfilePanel('servicios')" class="sidebar-link" data-panel="servicios" id="sidebar-link-servicios">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085"/></svg>
                                <span>Mis Servicios</span>
                            </a>
                            <a href="#" onclick="event.preventDefault();showProfilePanel('imagenes')" class="sidebar-link" data-panel="imagenes" id="sidebar-link-imagenes">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                <span>Imagen Publicitaria</span>
                            </a>
                            <a href="#" onclick="event.preventDefault();showProfilePanel('categorias')" class="sidebar-link" data-panel="categorias" id="sidebar-link-categorias">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path d="M6 6h.008v.008H6V6Z"/></svg>
                                <span>Mis Categorías</span>
                            </a>
                            <a href="#" onclick="event.preventDefault();showProfilePanel('direccion')" class="sidebar-link" data-panel="direccion" id="sidebar-link-direccion">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                <span>Mi Dirección</span>
                            </a>
                            <a href="#" onclick="event.preventDefault();showProfilePanel('cuenta')" class="sidebar-link" data-panel="cuenta" id="sidebar-link-cuenta">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                <span>Configuración de Cuenta</span>
                            </a>
                        </nav>
                    </aside>

                    <div id="profile-content" style="flex:1;display:flex;flex-direction:column;min-width:0;">
                        <div style="height:44px;background:#fff;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;padding:0 20px;gap:8px;position:sticky;top:60px;z-index:40;">
                            <button onclick="toggleProfileSidebar()" id="sidebar-toggle-btn" style="display:none;background:none;border:none;cursor:pointer;padding:4px;color:#0f172a;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                            </button>
                            <span style="font-size:12px;color:#64748b;">Mi Panel</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                            <span style="font-size:12px;color:#0f172a;font-weight:600;" id="breadcrumb-current">Datos del Negocio</span>
                            <div style="flex:1;"></div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-size:12px;color:#0f172a;font-weight:600;">{{ $user->name }}</span>
                                <div style="width:28px;height:28px;border-radius:50%;background:#D24C19;color:white;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            </div>
                        </div>

                        <div style="flex:1;overflow-y:auto;padding:24px;background:#f1f5f9;">
                            <div id="profile-loading" style="text-align:center;padding:40px;color:#64748b;font-size:13px;">Cargando tu panel...</div>
                            <div id="profile-panels" style="display:none;max-width:960px;margin:0 auto;">

                                <div class="profile-panel active" data-panel="negocio">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg> Información del negocio</h3>
                                        <p class="pcard-sub">Estos datos se muestran en las tarjetas publicitarias de la landing.</p>
                                        <div class="profile-field-grid">
                                            <div><label class="field-label">Nombre del negocio</label><input type="text" id="provider-business-name" class="field-input" placeholder="Ej: Pizzería Los Hermanos" /><span class="err-msg" id="provider-business-name-error"></span></div>
                                            <div><label class="field-label">Descripción</label><input type="text" id="provider-description" class="field-input" placeholder="Breve descripción de tu negocio..." /></div>
                                        </div>
                                    </div>
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg> Contacto y promoción</h3>
                                        <p class="pcard-sub">Formas de contacto, zonas de cobertura y tu promoción destacada.</p>
                                        <div class="profile-field-grid">
                                            <div><label class="field-label">Teléfono</label><input type="text" id="provider-phone" class="field-input" placeholder="+54 11 ..." /></div>
                                            <div><label class="field-label">WhatsApp</label><input type="text" id="provider-whatsapp" class="field-input" placeholder="5411..." /></div>
                                            <div><label class="field-label">Zona de cobertura</label><input type="text" id="provider-zone" class="field-input" placeholder="Zona Norte, Centro..." /></div>
                                            <div><label class="field-label">Promoción</label><input type="text" id="provider-promo" class="field-input" placeholder="2x1 en empanadas..." /></div>
                                        </div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="tab1-msg"></span>
                                            <button type="button" class="btn-primary" onclick="submitProvider('tab1-msg')">Guardar negocio</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="profile-panel" data-panel="horarios">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg> Horarios de atención</h3>
                                        <p class="pcard-sub">Configurá los horarios de apertura de tu negocio por día.</p>
                                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
                                            <button type="button" class="btn-secondary" onclick="copyMonFri()" style="display:flex;align-items:center;gap:6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Aplicar Lunes a Viernes</button>
                                        </div>
                                        <div id="hours-list" style="display:flex;flex-direction:column;gap:8px;"></div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="tab2-msg"></span>
                                            <button type="button" class="btn-primary" onclick="submitProvider('tab2-msg')">Guardar horarios</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="profile-panel" data-panel="servicios">
                                    <div class="pcard">
                                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:2px;">
                                            <div><h3 class="pcard-title" style="margin-bottom:4px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path d="M6 6h.008v.008H6V6Z"/></svg> Servicios</h3><p class="pcard-sub" style="margin-bottom:0;">Los servicios que brindás en tu negocio.</p></div>
                                            <button type="button" class="btn-primary" onclick="openServiceModal()">+ Agregar servicio</button>
                                        </div>
                                        <div id="services-loading" style="text-align:center;padding:20px;color:#94a3b8;font-size:12px;">Cargando servicios...</div>
                                        <div id="services-empty" style="display:none;text-align:center;padding:24px;color:#94a3b8;font-size:12px;border:1px dashed #e2e8f0;border-radius:1rem;">Todavía no agregaste servicios.</div>
                                        <div id="services-list" style="display:none;flex-direction:column;gap:10px;margin-top:16px;"></div>
                                    </div>
                                </div>

                                <div class="profile-panel" data-panel="imagenes">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg> Imagen Publicitaria</h3>
                                        <p class="pcard-sub">Subí la imagen que se mostrará en la página de publicidad. Se permite una sola imagen por proveedor.</p>
                                        <div style="max-width:400px;">
                                            <div class="dropzone" id="dropzone-publicidad" style="width:100%;">
                                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="1.6"><path d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"/></svg>
                                                <span class="dropzone-title">Mi imagen publicitaria</span>
                                                <span class="dropzone-hint">Arrastrá y soltá acá o hacé clic para elegir</span>
                                                <input type="file" id="image-upload-publicidad" accept="image/jpeg,image/png" style="display:none;" />
                                            </div>
                                        </div>
                                        <span class="save-msg err" id="images-msg" style="display:none;margin-top:12px;"></span>
                                        <div id="images-loading" style="text-align:center;padding:16px;color:#94a3b8;font-size:12px;">Cargando imagen...</div>
                                        <div id="images-empty" style="display:none;text-align:center;padding:20px;color:#94a3b8;font-size:12px;border:1px dashed #e2e8f0;border-radius:1rem;margin-top:16px;">No tenés imagen publicitaria cargada.</div>
                                        <div id="images-grid" style="display:none;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-top:16px;"></div>
                                    </div>
                                </div>

                                <div class="profile-panel" data-panel="categorias">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path d="M6 6h.008v.008H6V6Z"/></svg> Mis Categorías y Servicios</h3>
                                        <p class="pcard-sub">Seleccioná los grupos y subgrupos de servicios que ofrecés en tu negocio.</p>
                                        <div id="categorias-loading" style="text-align:center;padding:20px;color:#94a3b8;font-size:12px;">Cargando categorías...</div>
                                        <div id="categorias-list" style="display:none;"></div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:16px;">
                                            <span class="save-msg" id="categorias-msg"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="profile-panel" data-panel="direccion">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg> Mi Dirección</h3>
                                        <p class="pcard-sub">Configurá la ubicación de tu negocio para que los clientes te encuentren.</p>
                                        <div class="profile-field-grid">
                                            <div>
                                                <label class="field-label">País</label>
                                                <select id="addr-country" class="field-input" onchange="loadRegionsForCountry(this.value)">
                                                    <option value="">-- Seleccionar --</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="field-label">Provincia / Región</label>
                                                <select id="addr-province" class="field-input" disabled>
                                                    <option value="">-- Seleccionar país primero --</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="field-label">Calle</label>
                                                <input type="text" id="addr-street" class="field-input" placeholder="Av. San Martín" />
                                            </div>
                                            <div>
                                                <label class="field-label">Número</label>
                                                <input type="text" id="addr-number" class="field-input" placeholder="1234" />
                                            </div>
                                            <div>
                                                <label class="field-label">Piso / Depto</label>
                                                <input type="text" id="addr-floor" class="field-input" placeholder="3B" />
                                            </div>
                                            <div>
                                                <label class="field-label">Código Postal</label>
                                                <input type="text" id="addr-postal" class="field-input" placeholder="B1602" />
                                            </div>
                                            <div class="full">
                                                <label class="field-label">Notas de ubicación</label>
                                                <textarea id="addr-notes" rows="2" class="field-input" placeholder="Ej: Frente a la plaza, local amarillo..."></textarea>
                                            </div>
                                        </div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="direccion-msg"></span>
                                            <button type="button" class="btn-primary" onclick="submitAddress()">Guardar dirección</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="profile-panel" data-panel="cuenta">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg> Datos personales</h3>
                                        <p class="pcard-sub">Información de tu cuenta de usuario.</p>
                                        <div class="profile-field-grid">
                                            <div><label class="field-label">Nombre</label><input type="text" id="profile-name" class="field-input" /><span class="err-msg" id="profile-name-error"></span></div>
                                            <div><label class="field-label">Correo</label><input type="email" id="profile-email" class="field-input" /><span class="err-msg" id="profile-email-error"></span></div>
                                            <div><label class="field-label">Estado</label><span id="profile-status" class="status-badge">-</span></div>
                                            <div><label class="field-label">Tipo de usuario</label><span id="profile-type" class="status-badge">-</span></div>
                                        </div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="profile-msg"></span>
                                            <button type="button" class="btn-primary" onclick="submitProfile()">Guardar</button>
                                        </div>
                                    </div>
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg> Cambiar contraseña</h3>
                                        <p class="pcard-sub">Usá una contraseña segura de al menos 8 caracteres.</p>
                                        <div class="profile-field-grid">
                                            <div><label class="field-label">Contraseña actual</label><input type="password" id="pw-current" class="field-input" autocomplete="current-password" /><span class="err-msg" id="pw-current-error"></span></div>
                                            <div><label class="field-label">Nueva contraseña</label><input type="password" id="pw-new" class="field-input" autocomplete="new-password" /><span class="err-msg" id="pw-new-error"></span></div>
                                            <div class="full"><label class="field-label">Confirmar nueva contraseña</label><input type="password" id="pw-confirm" class="field-input" autocomplete="new-password" /><span class="err-msg" id="pw-confirm-error"></span></div>
                                        </div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="pw-msg"></span>
                                            <button type="button" class="btn-primary" onclick="submitPassword()">Guardar contraseña</button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div id="service-modal-overlay" style="display:none;position:fixed;inset:0;z-index:300;background:rgba(15,23,42,0.6);align-items:center;justify-content:center;">
                    <div style="background:#fff;border-radius:0;padding:24px;width:100%;max-width:420px;box-shadow:0 24px 60px rgba(15,23,42,0.2);box-sizing:border-box;">
                        <h3 id="service-modal-title" style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 16px;">Agregar servicio</h3>
                        <input type="hidden" id="service-edit-id" />
                        <div style="margin-bottom:12px;"><label class="field-label">Nombre del servicio</label><input type="text" id="service-name" class="field-input" placeholder="Ej: Pizza a la piedra" /><span class="err-msg" id="service-name-error"></span></div>
                        <div><label class="field-label">Descripción (opcional)</label><textarea id="service-description" rows="3" class="field-input" placeholder="Breve descripción del servicio..."></textarea></div>
                        <div style="margin-top:20px;text-align:right;">
                            <span class="save-msg err" id="service-msg" style="display:none;margin-right:8px;"></span>
                            <button type="button" class="btn-secondary" onclick="closeServiceModal()" style="margin-right:8px;">Cancelar</button>
                            <button type="button" class="btn-primary" onclick="submitService()">Guardar</button>
                        </div>
                    </div>
                </div>

                <div id="service-delete-overlay" style="display:none;position:fixed;inset:0;z-index:300;background:rgba(15,23,42,0.6);align-items:center;justify-content:center;">
                    <div style="background:#fff;border-radius:0;padding:24px;width:100%;max-width:380px;box-shadow:0 24px 60px rgba(15,23,42,0.2);text-align:center;box-sizing:border-box;">
                        <div style="width:44px;height:44px;border-radius:9999px;background:#fef2f2;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg></div>
                        <p style="font-size:14px;font-weight:700;color:#0f172a;margin:0 0 4px;">¿Eliminar este servicio?</p>
                        <p style="font-size:12px;color:#64748b;margin:0 0 18px;">Esta acción no se puede deshacer.</p>
                        <button type="button" class="btn-secondary" onclick="closeServiceDeleteModal()" style="margin-right:8px;">Cancelar</button>
                        <button type="button" class="btn-danger" style="background:#dc2626;border-color:#dc2626;color:#fff;" onclick="confirmDeleteService()">Eliminar</button>
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
            if (key === 'perfil') loadProfile();
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

    // ==================== MI PANEL / MI PERFIL ====================

    var profileData = null;
    var profileProvider = null;
    var profileIsPrestador = false;

    var PROFILE_DAYS = [
        { key: 'mon', label: 'Lunes' },
        { key: 'tue', label: 'Martes' },
        { key: 'wed', label: 'Miércoles' },
        { key: 'thu', label: 'Jueves' },
        { key: 'fri', label: 'Viernes' },
        { key: 'sat', label: 'Sábado' },
        { key: 'sun', label: 'Domingo' }
    ];

    function showMsg(id, text, ok) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = text;
        el.className = 'save-msg ' + (ok ? 'ok' : 'err');
        el.style.display = 'inline';
        clearTimeout(el._t);
        el._t = setTimeout(function() { el.style.display = 'none'; }, 3500);
    }

    var profileBreadcrumbLabels = {
        negocio: 'Datos del Negocio',
        horarios: 'Horarios de Atención',
        servicios: 'Mis Servicios',
        imagenes: 'Imagen Publicitaria',
        categorias: 'Mis Categorías',
        direccion: 'Mi Dirección',
        cuenta: 'Configuración de Cuenta'
    };

    function showProfilePanel(key) {
        document.querySelectorAll('.sidebar-link[data-panel]').forEach(function(link) {
            link.classList.toggle('active', link.dataset.panel === key);
        });
        document.querySelectorAll('.profile-panel').forEach(function(p) {
            p.classList.toggle('active', p.dataset.panel === key);
        });
        var bc = document.getElementById('breadcrumb-current');
        if (bc) bc.textContent = profileBreadcrumbLabels[key] || key;
        var sb = document.getElementById('profile-sidebar');
        if (sb) sb.classList.remove('open');
        var ov = document.getElementById('sidebar-overlay');
        if (ov) ov.style.display = 'none';
    }

    function toggleProfileSidebar() {
        var sb = document.getElementById('profile-sidebar');
        var ov = document.getElementById('sidebar-overlay');
        if (!sb) return;
        var isOpen = sb.classList.toggle('open');
        if (ov) ov.style.display = isOpen ? 'block' : 'none';
        setTimeout(adjustSidebarHeight, 50);
    }

    function loadProfile() {
        var loading = document.getElementById('profile-loading');
        var panels = document.getElementById('profile-panels');
        panels.style.display = 'none';
        loading.style.display = 'block';

        fetch('{{ url("/profile") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(user) {
            profileData = user;
            profileProvider = user.provider || null;
            profileIsPrestador = user.type_user && user.type_user.description === 'Prestador';

            document.getElementById('profile-name').value = user.name || '';
            document.getElementById('profile-email').value = user.email || '';
            setProfileStatusBadge(user);
            document.getElementById('profile-type').textContent = user.type_user ? user.type_user.description : '-';

            document.getElementById('sidebar-link-negocio').style.display = profileIsPrestador ? '' : 'none';
            document.getElementById('sidebar-link-horarios').style.display = profileIsPrestador ? '' : 'none';
            document.getElementById('sidebar-link-servicios').style.display = profileIsPrestador ? '' : 'none';
            document.getElementById('sidebar-link-imagenes').style.display = profileIsPrestador ? '' : 'none';
            document.getElementById('sidebar-link-categorias').style.display = profileIsPrestador ? '' : 'none';

            loading.style.display = 'none';
            panels.style.display = 'block';

            if (profileIsPrestador) {
                showProfilePanel('negocio');
                fillProviderForm();
                buildHoursGrid();
                loadProviderServices();
                loadProviderImages();
                loadProviderSubgroups();
                setupImageDropzones();
                loadAddress();
            } else {
                showProfilePanel('cuenta');
            }
        })
        .catch(function() {
            loading.textContent = 'Error al cargar el perfil.';
        });
    }

    function setProfileStatusBadge(user) {
        var el = document.getElementById('profile-status');
        var st = user.user_status ? user.user_status.status : '-';
        el.textContent = st;
        if (st === 'Active') {
            el.style.cssText = 'display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;font-size:12px;font-weight:600;';
        } else if (st === 'Inactive') {
            el.style.cssText = 'display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:12px;font-weight:600;';
        } else {
            el.style.cssText = 'display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#eef2f7;border:1px solid #e2e8f0;color:#334155;font-size:12px;font-weight:600;';
        }
    }

    // ---------- Tab 1: Datos del negocio ----------

    function fillProviderForm() {
        if (!profileProvider) return;
        document.getElementById('provider-business-name').value = profileProvider.business_name || '';
        document.getElementById('provider-description').value = profileProvider.description || '';
        document.getElementById('provider-phone').value = profileProvider.phone || '';
        document.getElementById('provider-whatsapp').value = profileProvider.whatsapp || '';
        document.getElementById('provider-zone').value = profileProvider.zone || '';
        document.getElementById('provider-promo').value = profileProvider.promo || '';
    }

    function submitProvider(msgId) {
        var errBn = document.getElementById('provider-business-name-error');
        var nameEl = document.getElementById('provider-business-name');
        errBn.style.display = 'none';
        nameEl.classList.remove('err');

        var businessName = nameEl.value.trim();
        if (!businessName) {
            nameEl.classList.add('err');
            errBn.textContent = 'El nombre del negocio es obligatorio.';
            errBn.style.display = 'block';
            showProfilePanel('negocio');
            return;
        }

        var payload = {
            business_name: businessName,
            description: document.getElementById('provider-description').value.trim(),
            phone: document.getElementById('provider-phone').value.trim(),
            whatsapp: document.getElementById('provider-whatsapp').value.trim(),
            zone: document.getElementById('provider-zone').value.trim(),
            promo: document.getElementById('provider-promo').value.trim(),
            hours: collectHours()
        };

        fetch('{{ url("/profile/provider") }}', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                nameEl.classList.add('err');
                if (data.errors.business_name) { errBn.textContent = data.errors.business_name[0]; errBn.style.display = 'block'; }
                return;
            }
            if (data.success) {
                profileProvider = data.provider;
                showMsg(msgId, data.message, true);
            } else {
                showMsg(msgId, data.message || 'Error al guardar.', false);
            }
        })
        .catch(function() {
            showMsg(msgId, 'Error de conexión.', false);
        });
    }

    // ---------- Tab 2: Horarios ----------

    function buildHoursGrid() {
        var container = document.getElementById('hours-list');
        if (!container) return;
        container.innerHTML = '';
        var hoursStyle = document.getElementById('hours-toggle-style');
        if (!hoursStyle) {
            hoursStyle = document.createElement('style');
            hoursStyle.id = 'hours-toggle-style';
            hoursStyle.textContent = '.hours-toggle:checked + span { background:#22c55e !important; } .hours-toggle:checked + span > span { transform:translateX(20px); }';
            document.head.appendChild(hoursStyle);
        }
        var hours = (profileProvider && profileProvider.hours) ? profileProvider.hours : {};

        PROFILE_DAYS.forEach(function(day) {
            var dayData = hours[day.key] || {};
            var isOpen = dayData.open !== false && (dayData.shifts && dayData.shifts.length > 0);

            var row = document.createElement('div');
            row.setAttribute('data-day', day.key);
            row.style.cssText = 'display:flex;align-items:center;gap:12px;padding:12px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:0;flex-wrap:wrap;';

            var label = document.createElement('span');
            label.style.cssText = 'min-width:110px;font-size:13px;font-weight:600;color:#0f172a;';
            label.textContent = day.label;
            row.appendChild(label);

            var toggleWrap = document.createElement('label');
            toggleWrap.style.cssText = 'position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0;';
            var toggle = document.createElement('input');
            toggle.type = 'checkbox';
            toggle.checked = isOpen;
            toggle.className = 'hours-toggle';
            toggle.setAttribute('data-day', day.key);
            toggle.style.cssText = 'opacity:0;width:0;height:0;';
            toggle.addEventListener('change', function() {
                toggleDay(day.key, this.checked);
            });
            var slider = document.createElement('span');
            slider.style.cssText = 'position:absolute;cursor:pointer;inset:0;background:#cbd5e1;border-radius:24px;transition:.2s;';
            var sliderBefore = document.createElement('span');
            sliderBefore.style.cssText = 'position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:white;border-radius:50%;transition:.2s;';
            slider.appendChild(sliderBefore);
            toggleWrap.appendChild(toggle);
            toggleWrap.appendChild(slider);
            row.appendChild(toggleWrap);

            var statusLabel = document.createElement('span');
            statusLabel.className = 'hours-status';
            statusLabel.setAttribute('data-day', day.key);
            statusLabel.style.cssText = 'font-size:12px;font-weight:600;min-width:60px;';
            statusLabel.textContent = isOpen ? 'Abierto' : 'Cerrado';
            statusLabel.style.color = isOpen ? '#16a34a' : '#94a3b8';
            row.appendChild(statusLabel);

            var shiftsWrap = document.createElement('div');
            shiftsWrap.className = 'hours-shifts';
            shiftsWrap.setAttribute('data-day', day.key);
            shiftsWrap.style.cssText = 'display:flex;align-items:center;gap:8px;flex-wrap:wrap;width:100%;' + (isOpen ? '' : 'opacity:0.4;pointer-events:none;');

            var shifts = (dayData.shifts && dayData.shifts.length > 0) ? dayData.shifts : [{ from: '09:00', to: '18:00' }];

            shifts.forEach(function(shift, si) {
                var shiftGroup = document.createElement('div');
                shiftGroup.className = 'shift-group';
                shiftGroup.setAttribute('data-day', day.key);
                shiftGroup.setAttribute('data-shift', si);
                shiftGroup.style.cssText = 'display:flex;align-items:center;gap:6px;flex-wrap:wrap;';

                if (si > 0) {
                    var dash1 = document.createElement('span');
                    dash1.style.cssText = 'font-size:11px;color:#94a3b8;font-weight:600;';
                    dash1.textContent = '—';
                    shiftGroup.appendChild(dash1);
                }

                var fromInput = document.createElement('input');
                fromInput.type = 'time';
                fromInput.value = shift.from || '09:00';
                fromInput.className = 'hours-from';
                fromInput.setAttribute('data-day', day.key);
                fromInput.setAttribute('data-shift', si);
                fromInput.style.cssText = 'padding:6px 10px;border:1px solid #d1d5db;border-radius:0;font-size:12px;font-weight:500;color:#0f172a;background:#fff;outline:none;width:110px;';
                fromInput.addEventListener('focus', function() { this.style.borderColor = '#D24C19'; });
                fromInput.addEventListener('blur', function() { this.style.borderColor = '#d1d5db'; });
                shiftGroup.appendChild(fromInput);

                var dash = document.createElement('span');
                dash.style.cssText = 'font-size:11px;color:#94a3b8;font-weight:600;';
                dash.textContent = 'a';
                shiftGroup.appendChild(dash);

                var toInput = document.createElement('input');
                toInput.type = 'time';
                toInput.value = shift.to || '18:00';
                toInput.className = 'hours-to';
                toInput.setAttribute('data-day', day.key);
                toInput.setAttribute('data-shift', si);
                toInput.style.cssText = 'padding:6px 10px;border:1px solid #d1d5db;border-radius:0;font-size:12px;font-weight:500;color:#0f172a;background:#fff;outline:none;width:110px;';
                toInput.addEventListener('focus', function() { this.style.borderColor = '#D24C19'; });
                toInput.addEventListener('blur', function() { this.style.borderColor = '#d1d5db'; });
                shiftGroup.appendChild(toInput);

                if (si > 0) {
                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.style.cssText = 'background:none;border:none;cursor:pointer;color:#dc2626;padding:2px;';
                    removeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>';
                    removeBtn.addEventListener('click', function() {
                        removeShift(day.key, si);
                    });
                    shiftGroup.appendChild(removeBtn);
                }

                shiftsWrap.appendChild(shiftGroup);
            });

            if (isOpen) {
                var addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'add-shift-btn';
                addBtn.setAttribute('data-day', day.key);
                addBtn.style.cssText = 'background:none;border:1px dashed #d1d5db;border-radius:0;padding:5px 12px;font-size:11px;font-weight:600;color:#64748b;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:4px;';
                addBtn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m-7-7h14"/></svg> Agregar turno';
                addBtn.addEventListener('mouseenter', function() { this.style.borderColor = '#D24C19'; this.style.color = '#D24C19'; });
                addBtn.addEventListener('mouseleave', function() { this.style.borderColor = '#d1d5db'; this.style.color = '#64748b'; });
                addBtn.addEventListener('click', function() {
                    addShift(day.key);
                });
                shiftsWrap.appendChild(addBtn);
            }

            row.appendChild(shiftsWrap);

            var copyBtn = document.createElement('button');
            copyBtn.type = 'button';
            copyBtn.title = 'Copiar al día siguiente';
            copyBtn.style.cssText = 'background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;flex-shrink:0;';
            copyBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>';
            copyBtn.addEventListener('click', function() {
                copyDayToNext(day.key);
            });
            row.appendChild(copyBtn);

            container.appendChild(row);
        });
    }

    function toggleDay(dayKey, isOpen) {
        var row = document.querySelector('#hours-list [data-day="' + dayKey + '"]');
        if (!row) return;
        var shiftsWrap = row.querySelector('.hours-shifts');
        var status = row.querySelector('.hours-status');
        if (isOpen) {
            shiftsWrap.style.opacity = '1';
            shiftsWrap.style.pointerEvents = 'auto';
            status.textContent = 'Abierto';
            status.style.color = '#16a34a';
            if (!shiftsWrap.querySelector('.shift-group')) {
                addShift(dayKey);
            }
        } else {
            shiftsWrap.style.opacity = '0.4';
            shiftsWrap.style.pointerEvents = 'none';
            status.textContent = 'Cerrado';
            status.style.color = '#94a3b8';
        }
    }

    function addShift(dayKey) {
        var shiftsWrap = document.querySelector('.hours-shifts[data-day="' + dayKey + '"]');
        if (!shiftsWrap) return;
        var existingShifts = shiftsWrap.querySelectorAll('.shift-group').length;
        if (existingShifts >= 3) return;

        var shiftGroup = document.createElement('div');
        shiftGroup.className = 'shift-group';
        shiftGroup.setAttribute('data-day', dayKey);
        shiftGroup.setAttribute('data-shift', existingShifts);
        shiftGroup.style.cssText = 'display:flex;align-items:center;gap:6px;flex-wrap:wrap;';

        var dash1 = document.createElement('span');
        dash1.style.cssText = 'font-size:11px;color:#94a3b8;font-weight:600;';
        dash1.textContent = '—';
        shiftGroup.appendChild(dash1);

        var fromInput = document.createElement('input');
        fromInput.type = 'time';
        fromInput.value = '13:00';
        fromInput.className = 'hours-from';
        fromInput.setAttribute('data-day', dayKey);
        fromInput.setAttribute('data-shift', existingShifts);
        fromInput.style.cssText = 'padding:6px 10px;border:1px solid #d1d5db;border-radius:0;font-size:12px;font-weight:500;color:#0f172a;background:#fff;outline:none;width:110px;';
        fromInput.addEventListener('focus', function() { this.style.borderColor = '#D24C19'; });
        fromInput.addEventListener('blur', function() { this.style.borderColor = '#d1d5db'; });
        shiftGroup.appendChild(fromInput);

        var dash = document.createElement('span');
        dash.style.cssText = 'font-size:11px;color:#94a3b8;font-weight:600;';
        dash.textContent = 'a';
        shiftGroup.appendChild(dash);

        var toInput = document.createElement('input');
        toInput.type = 'time';
        toInput.value = '17:00';
        toInput.className = 'hours-to';
        toInput.setAttribute('data-day', dayKey);
        toInput.setAttribute('data-shift', existingShifts);
        toInput.style.cssText = 'padding:6px 10px;border:1px solid #d1d5db;border-radius:0;font-size:12px;font-weight:500;color:#0f172a;background:#fff;outline:none;width:110px;';
        toInput.addEventListener('focus', function() { this.style.borderColor = '#D24C19'; });
        toInput.addEventListener('blur', function() { this.style.borderColor = '#d1d5db'; });
        shiftGroup.appendChild(toInput);

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.style.cssText = 'background:none;border:none;cursor:pointer;color:#dc2626;padding:2px;';
        removeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>';
        removeBtn.addEventListener('click', function() {
            removeShift(dayKey, existingShifts);
        });
        shiftGroup.appendChild(removeBtn);

        var addBtn = shiftsWrap.querySelector('.add-shift-btn');
        shiftsWrap.insertBefore(shiftGroup, addBtn);
    }

    function removeShift(dayKey, shiftIndex) {
        var shiftsWrap = document.querySelector('.hours-shifts[data-day="' + dayKey + '"]');
        if (!shiftsWrap) return;
        var groups = shiftsWrap.querySelectorAll('.shift-group');
        if (groups.length <= 1) return;
        groups[shiftIndex].remove();
        var remaining = shiftsWrap.querySelectorAll('.shift-group');
        remaining.forEach(function(g, i) {
            g.setAttribute('data-shift', i);
            g.querySelectorAll('input').forEach(function(inp) {
                inp.setAttribute('data-shift', i);
            });
        });
    }

    function collectHours() {
        var out = {};
        PROFILE_DAYS.forEach(function(day) {
            var toggle = document.querySelector('.hours-toggle[data-day="' + day.key + '"]');
            if (!toggle || !toggle.checked) {
                out[day.key] = { open: false, shifts: [] };
                return;
            }
            var shifts = [];
            var groups = document.querySelectorAll('.hours-shifts[data-day="' + day.key + '"] .shift-group');
            groups.forEach(function(g) {
                var from = g.querySelector('.hours-from');
                var to = g.querySelector('.hours-to');
                if (from && to) {
                    shifts.push({ from: from.value, to: to.value });
                }
            });
            out[day.key] = { open: true, shifts: shifts };
        });
        return out;
    }

    function copyMonFri() {
        var monData = null;
        var monToggle = document.querySelector('.hours-toggle[data-day="mon"]');
        if (monToggle && monToggle.checked) {
            var monShifts = [];
            document.querySelectorAll('.hours-shifts[data-day="mon"] .shift-group').forEach(function(g) {
                var from = g.querySelector('.hours-from');
                var to = g.querySelector('.hours-to');
                if (from && to) monShifts.push({ from: from.value, to: to.value });
            });
            monData = { open: true, shifts: monShifts };
        } else {
            monData = { open: false, shifts: [] };
        }

        ['tue','wed','thu','fri'].forEach(function(d) {
            var toggle = document.querySelector('.hours-toggle[data-day="' + d + '"]');
            if (toggle) {
                toggle.checked = monData.open;
                toggle.dispatchEvent(new Event('change'));
            }
            var groups = document.querySelectorAll('.hours-shifts[data-day="' + d + '"] .shift-group');
            groups.forEach(function(g, i) { if (i > 0) g.remove(); });
            if (monData.shifts.length > 0) {
                var firstFrom = document.querySelector('.hours-shifts[data-day="' + d + '"] .hours-from');
                var firstTo = document.querySelector('.hours-shifts[data-day="' + d + '"] .hours-to');
                if (firstFrom) firstFrom.value = monData.shifts[0].from;
                if (firstTo) firstTo.value = monData.shifts[0].to;
                for (var si = 1; si < monData.shifts.length; si++) {
                    addShift(d);
                    var newGroup = document.querySelector('.hours-shifts[data-day="' + d + '"] .shift-group[data-shift="' + si + '"]');
                    if (newGroup) {
                        var nf = newGroup.querySelector('.hours-from');
                        var nt = newGroup.querySelector('.hours-to');
                        if (nf) nf.value = monData.shifts[si].from;
                        if (nt) nt.value = monData.shifts[si].to;
                    }
                }
            }
        });
    }

    function copyDayToNext(dayKey) {
        var days = ['mon','tue','wed','thu','fri','sat','sun'];
        var idx = days.indexOf(dayKey);
        if (idx < 0 || idx >= days.length - 1) return;
        var nextDay = days[idx + 1];

        var sourceData = collectHours()[dayKey];

        var nextToggle = document.querySelector('.hours-toggle[data-day="' + nextDay + '"]');
        if (nextToggle) {
            nextToggle.checked = sourceData.open;
            nextToggle.dispatchEvent(new Event('change'));
        }
        var groups = document.querySelectorAll('.hours-shifts[data-day="' + nextDay + '"] .shift-group');
        groups.forEach(function(g, i) { if (i > 0) g.remove(); });
        if (sourceData.shifts.length > 0) {
            var firstFrom = document.querySelector('.hours-shifts[data-day="' + nextDay + '"] .hours-from');
            var firstTo = document.querySelector('.hours-shifts[data-day="' + nextDay + '"] .hours-to');
            if (firstFrom) firstFrom.value = sourceData.shifts[0].from;
            if (firstTo) firstTo.value = sourceData.shifts[0].to;
            for (var si = 1; si < sourceData.shifts.length; si++) {
                addShift(nextDay);
                var newGroup = document.querySelector('.hours-shifts[data-day="' + nextDay + '"] .shift-group[data-shift="' + si + '"]');
                if (newGroup) {
                    var nf = newGroup.querySelector('.hours-from');
                    var nt = newGroup.querySelector('.hours-to');
                    if (nf) nf.value = sourceData.shifts[si].from;
                    if (nt) nt.value = sourceData.shifts[si].to;
                }
            }
        }
    }

    // ---------- Tab 3: Servicios e imágenes ----------

    function loadProviderServices() {
        var list = document.getElementById('services-list');
        var loading = document.getElementById('services-loading');
        var empty = document.getElementById('services-empty');

        fetch('{{ url("/profile/services") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            if (!data.services || data.services.length === 0) {
                empty.style.display = 'block';
                list.style.display = 'none';
                return;
            }
            empty.style.display = 'none';
            list.style.display = 'flex';
            list.innerHTML = '';

            data.services.forEach(function(s, i) {
                var card = document.createElement('div');
                card.className = 'service-card';

                var top = document.createElement('div');
                top.style.cssText = 'display:flex;align-items:center;justify-content:space-between;';
                var left = document.createElement('div');
                left.style.cssText = 'display:flex;align-items:center;gap:8px;';
                var idx = document.createElement('span');
                idx.className = 'service-index';
                idx.textContent = (i + 1 < 10 ? '0' : '') + (i + 1);
                var name = document.createElement('span');
                name.style.cssText = 'font-size:13px;font-weight:600;color:#0f172a;';
                name.textContent = s.name;
                left.appendChild(idx);
                left.appendChild(name);
                var actions = document.createElement('div');
                actions.style.cssText = 'display:flex;gap:6px;';
                actions.appendChild(makeIconBtn('edit', "openEditServiceModal(" + s.id + ", '" + escapeHtml(s.name).replace(/'/g, "\\'") + "', '" + escapeHtml(s.description || '').replace(/'/g, "\\'") + "')"));
                actions.appendChild(makeIconBtn('del', "openServiceDeleteModal(" + s.id + ")"));
                top.appendChild(left);
                top.appendChild(actions);
                card.appendChild(top);

                if (s.description) {
                    var desc = document.createElement('p');
                    desc.style.cssText = 'font-size:12px;color:#64748b;margin:8px 0 0;line-height:1.4;';
                    desc.textContent = s.description;
                    card.appendChild(desc);
                }

                list.appendChild(card);
            });
        })
        .catch(function() {
            loading.textContent = 'Error al cargar servicios.';
        });
    }

    function makeIconBtn(kind, onclick) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'icon-btn ' + (kind === 'edit' ? 'edit' : 'del');
        b.setAttribute('onclick', onclick);
        if (kind === 'edit') {
            b.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>';
        } else {
            b.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>';
        }
        return b;
    }

    function openServiceModal() {
        document.getElementById('service-edit-id').value = '';
        document.getElementById('service-name').value = '';
        document.getElementById('service-description').value = '';
        document.getElementById('service-name').classList.remove('err');
        document.getElementById('service-name-error').style.display = 'none';
        document.getElementById('service-msg').style.display = 'none';
        document.getElementById('service-modal-title').textContent = 'Agregar servicio';
        document.getElementById('service-modal-overlay').style.display = 'flex';
    }

    function openEditServiceModal(id, name, description) {
        document.getElementById('service-edit-id').value = id;
        document.getElementById('service-name').value = name;
        document.getElementById('service-description').value = description || '';
        document.getElementById('service-name').classList.remove('err');
        document.getElementById('service-name-error').style.display = 'none';
        document.getElementById('service-msg').style.display = 'none';
        document.getElementById('service-modal-title').textContent = 'Editar servicio';
        document.getElementById('service-modal-overlay').style.display = 'flex';
    }

    function closeServiceModal() {
        document.getElementById('service-modal-overlay').style.display = 'none';
    }

    function submitService() {
        var editId = document.getElementById('service-edit-id').value;
        var nameEl = document.getElementById('service-name');
        var name = nameEl.value.trim();
        var descEl = document.getElementById('service-description');
        var description = descEl.value.trim();
        var errEl = document.getElementById('service-name-error');
        var msgEl = document.getElementById('service-msg');
        errEl.style.display = 'none';
        msgEl.style.display = 'none';
        nameEl.classList.remove('err');

        if (!name) {
            nameEl.classList.add('err');
            errEl.textContent = 'El nombre es obligatorio.';
            errEl.style.display = 'block';
            return;
        }

        var url = editId ? '{{ url("/profile/services/") }}/' + editId : '{{ url("/profile/services") }}';
        var method = editId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ name: name, description: description })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                nameEl.classList.add('err');
                if (data.errors.name) { errEl.textContent = data.errors.name[0]; errEl.style.display = 'block'; }
                return;
            }
            if (data.success) {
                closeServiceModal();
                loadProviderServices();
            } else {
                msgEl.textContent = data.message;
                msgEl.className = 'save-msg err';
                msgEl.style.display = 'inline';
            }
        })
        .catch(function() {
            msgEl.textContent = 'Error de conexión.';
            msgEl.className = 'save-msg err';
            msgEl.style.display = 'inline';
        });
    }

    var deleteServiceId = null;

    function openServiceDeleteModal(id) {
        deleteServiceId = id;
        document.getElementById('service-delete-overlay').style.display = 'flex';
    }

    function closeServiceDeleteModal() {
        deleteServiceId = null;
        document.getElementById('service-delete-overlay').style.display = 'none';
    }

    function confirmDeleteService() {
        if (!deleteServiceId) return;
        fetch('{{ url("/profile/services/") }}/' + deleteServiceId, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            closeServiceDeleteModal();
            loadProviderServices();
        });
    }

    function loadProviderImages() {
        var grid = document.getElementById('images-grid');
        var loading = document.getElementById('images-loading');
        var empty = document.getElementById('images-empty');
        var dropzone = document.getElementById('dropzone-publicidad');
        var publicidadSection = document.getElementById('publicidad-limit-msg');

        if (!publicidadSection) {
            var dz = document.getElementById('dropzone-publicidad');
            if (dz) {
                var msg = document.createElement('div');
                msg.id = 'publicidad-limit-msg';
                msg.style.cssText = 'display:none;margin-top:12px;padding:10px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;font-size:12px;color:#9a3412;';
                msg.innerHTML = '&#9432; Solo se permite una imagen publicitaria. Eliminá la actual para subir una nueva.';
                dz.parentNode.insertBefore(msg, dz.nextSibling);
                publicidadSection = msg;
            }
        }

        if (!profileProvider || !profileProvider.images || profileProvider.images.length === 0) {
            loading.style.display = 'none';
            empty.style.display = 'block';
            grid.style.display = 'none';
            if (dropzone) dropzone.style.display = '';
            if (publicidadSection) publicidadSection.style.display = 'none';
            return;
        }

        loading.style.display = 'none';
        empty.style.display = 'none';
        grid.style.display = 'grid';
        grid.innerHTML = '';

        if (dropzone) dropzone.style.display = 'none';
        if (publicidadSection) publicidadSection.style.display = 'block';

        profileProvider.images.forEach(function(img) {
            var card = document.createElement('div');
            card.style.cssText = 'position:relative;border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;background:#fff;';
            var src = '{{ asset("images/publicidad/") }}/' + img.image_path + '?v=' + Date.now();
            var typeLabel = 'Publicidad';
            card.innerHTML = '<img src="' + src + '" style="width:100%;height:180px;object-fit:cover;display:block;" />' +
                '<span class="img-type-badge">' + typeLabel + '</span>' +
                '<button type="button" onclick="deleteImage(' + img.id + ')" style="position:absolute;top:6px;right:6px;background:rgba(220,38,38,0.95);color:#fff;border:none;border-radius:8px;width:24px;height:24px;font-size:11px;cursor:pointer;line-height:1;">&#10005;</button>';
            grid.appendChild(card);
        });
    }

    function setupImageDropzones() {
        setupDropzone('dropzone-publicidad', 'publicidad', 'image-upload-publicidad');
    }

    function setupDropzone(zoneId, type, inputId) {
        var zone = document.getElementById(zoneId);
        var input = document.getElementById(inputId);
        if (!zone || !input) return;

        zone.addEventListener('click', function(e) {
            if (e.target !== input) input.click();
        });
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });
        zone.addEventListener('dragleave', function() {
            zone.classList.remove('dragover');
        });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                uploadImage(type, e.dataTransfer.files[0]);
            }
        });
        input.addEventListener('change', function() {
            if (input.files && input.files[0]) {
                uploadImage(type, input.files[0]);
                input.value = '';
            }
        });
    }

    function uploadImage(type, file) {
        if (!file) return;
        var formData = new FormData();
        formData.append('image', file);
        formData.append('image_type', type);

        fetch('{{ url("/profile/images") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                profileProvider.images.push(data.image);
                loadProviderImages();
                showMsg('images-msg', data.message, true);
            } else {
                showMsg('images-msg', data.message || 'No se pudo subir la imagen.', false);
            }
        })
        .catch(function() {
            showMsg('images-msg', 'Error de conexión.', false);
        });
    }

    function deleteImage(id) {
        if (!confirm('¿Eliminar esta imagen?')) return;
        fetch('{{ url("/profile/images/") }}/' + id, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                profileProvider.images = profileProvider.images.filter(function(img) { return img.id != id; });
                loadProviderImages();
            }
        });
    }

    // ---------- Categorías y subgrupos ----------

    function loadProviderSubgroups() {
        var list = document.getElementById('categorias-list');
        var loading = document.getElementById('categorias-loading');

        fetch('{{ url("/profile/subgroups") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '';

            if (!data.groups || data.groups.length === 0) {
                list.innerHTML = '<p style="text-align:center;color:#94a3b8;font-size:12px;padding:20px;">No hay categorías disponibles.</p>';
                return;
            }

            data.groups.forEach(function(group) {
                if (!group.subgroups || group.subgroups.length === 0) return;

                var section = document.createElement('div');
                section.style.cssText = 'margin-bottom:20px;border:1px solid #e2e8f0;background:#fff;padding:16px;';

                var header = document.createElement('div');
                header.style.cssText = 'display:flex;align-items:center;gap:8px;margin-bottom:12px;';
                var icon = document.createElement('span');
                if (group.icon) icon.innerHTML = group.icon;
                icon.style.cssText = 'display:flex;align-items:center;color:#D24C19;';
                var title = document.createElement('h4');
                title.style.cssText = 'font-size:13px;font-weight:700;color:#0f172a;margin:0;';
                title.textContent = group.description;
                header.appendChild(icon);
                header.appendChild(title);
                section.appendChild(header);

                var grid = document.createElement('div');
                grid.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;';

                group.subgroups.forEach(function(sub) {
                    var isSelected = data.selected.indexOf(sub.id) >= 0;
                    var label = document.createElement('label');
                    label.style.cssText = 'display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid ' + (isSelected ? '#D24C19' : '#e2e8f0') + ';background:' + (isSelected ? '#fff7ed' : '#fff') + ';cursor:pointer;transition:all .15s;font-size:12px;font-weight:500;color:' + (isSelected ? '#D24C19' : '#475569') + ';';
                    var cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.checked = isSelected;
                    cb.style.cssText = 'accent-color:#D24C19;width:14px;height:14px;cursor:pointer;';
                    cb.addEventListener('change', function() {
                        toggleSubgroup(sub.id, label, cb);
                    });
                    var span = document.createElement('span');
                    span.textContent = sub.description;
                    label.appendChild(cb);
                    label.appendChild(span);
                    grid.appendChild(label);
                });

                section.appendChild(grid);
                list.appendChild(section);
            });
        })
        .catch(function() {
            loading.textContent = 'Error al cargar categorías.';
        });
    }

    function toggleSubgroup(subgroupId, labelEl, cb) {
        fetch('{{ url("/profile/subgroups/toggle") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ subgroup_id: subgroupId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var isSelected = data.selected.indexOf(subgroupId) >= 0;
                labelEl.style.borderColor = isSelected ? '#D24C19' : '#e2e8f0';
                labelEl.style.background = isSelected ? '#fff7ed' : '#fff';
                labelEl.style.color = isSelected ? '#D24C19' : '#475569';
                cb.checked = isSelected;
                showMsg('categorias-msg', data.message, true);
            } else {
                cb.checked = !cb.checked;
                showMsg('categorias-msg', data.message || 'Error al guardar.', false);
            }
        })
        .catch(function() {
            cb.checked = !cb.checked;
            showMsg('categorias-msg', 'Error de conexión.', false);
        });
    }

    // ---------- Tab 4: Cuenta y perfil ----------

    function submitProfile() {
        var nameEl = document.getElementById('profile-name');
        var emailEl = document.getElementById('profile-email');
        var errName = document.getElementById('profile-name-error');
        var errEmail = document.getElementById('profile-email-error');
        var name = nameEl.value.trim();
        var email = emailEl.value.trim();

        errName.style.display = 'none';
        errEmail.style.display = 'none';
        nameEl.classList.remove('err');
        emailEl.classList.remove('err');

        if (!name) { nameEl.classList.add('err'); errName.textContent = 'El nombre es obligatorio.'; errName.style.display = 'block'; return; }
        if (!email) { emailEl.classList.add('err'); errEmail.textContent = 'El correo es obligatorio.'; errEmail.style.display = 'block'; return; }

        fetch('{{ url("/profile") }}', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ name: name, email: email })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                if (data.errors.name) { nameEl.classList.add('err'); errName.textContent = data.errors.name[0]; errName.style.display = 'block'; }
                if (data.errors.email) { emailEl.classList.add('err'); errEmail.textContent = data.errors.email[0]; errEmail.style.display = 'block'; }
                return;
            }
            if (data.success) {
                showMsg('profile-msg', data.message, true);
            }
        })
        .catch(function() {
            showMsg('profile-msg', 'Error de conexión.', false);
        });
    }

    function submitPassword() {
        var curEl = document.getElementById('pw-current');
        var nwEl = document.getElementById('pw-new');
        var conEl = document.getElementById('pw-confirm');
        var errCur = document.getElementById('pw-current-error');
        var errNew = document.getElementById('pw-new-error');
        var errCon = document.getElementById('pw-confirm-error');
        var cur = curEl.value;
        var nw = nwEl.value;
        var con = conEl.value;

        [errCur, errNew, errCon].forEach(function(e) { e.style.display = 'none'; });
        [curEl, nwEl, conEl].forEach(function(e) { e.classList.remove('err'); });

        if (!cur) { curEl.classList.add('err'); errCur.textContent = 'Ingresá tu contraseña actual.'; errCur.style.display = 'block'; return; }
        if (nw.length < 8) { nwEl.classList.add('err'); errNew.textContent = 'Mínimo 8 caracteres.'; errNew.style.display = 'block'; return; }
        if (nw !== con) { conEl.classList.add('err'); errCon.textContent = 'Las contraseñas no coinciden.'; errCon.style.display = 'block'; return; }

        fetch('{{ url("/profile/password") }}', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ current_password: cur, password: nw, password_confirmation: con })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                if (data.errors.current_password) { curEl.classList.add('err'); errCur.textContent = data.errors.current_password[0]; errCur.style.display = 'block'; }
                if (data.errors.password) { nwEl.classList.add('err'); errNew.textContent = data.errors.password[0]; errNew.style.display = 'block'; }
                return;
            }
            if (data.success) {
                curEl.value = ''; nwEl.value = ''; conEl.value = '';
                showMsg('pw-msg', data.message, true);
            }
        })
        .catch(function() {
            showMsg('pw-msg', 'Error de conexión.', false);
        });
    }

    // ---------- Sidebar height ----------

    function adjustSidebarHeight() {
        var sidebar = document.getElementById('profile-sidebar');
        var footer = document.querySelector('footer');
        if (!sidebar || !footer) return;
        var navBottom = 70;
        var footerTop = footer.getBoundingClientRect().top;
        var viewportBottom = window.innerHeight;
        var h;
        if (window.innerWidth <= 768) {
            if (sidebar.classList.contains('open')) {
                h = footerTop - navBottom;
                if (h < 100) h = viewportBottom - navBottom;
                sidebar.style.height = h + 'px';
            } else {
                sidebar.style.height = '';
            }
            return;
        }
        h = footerTop - navBottom;
        if (h < 100) h = 100;
        sidebar.style.height = h + 'px';
    }

    window.addEventListener('resize', adjustSidebarHeight);
    window.addEventListener('scroll', adjustSidebarHeight);
    adjustSidebarHeight();

    // ---------- Mi Dirección ----------

    function loadAddress() {
        fetch('{{ url("/profile/address") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            fetch('{{ url("/countries") }}', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(function(r) { return r.json(); })
            .then(function(countries) {
                var sel = document.getElementById('addr-country');
                sel.innerHTML = '<option value="">-- Seleccionar --</option>';
                countries.forEach(function(c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    sel.appendChild(opt);
                });

                if (data.address) {
                    var a = data.address;
                    if (a.country_id) {
                        sel.value = a.country_id;
                        loadRegionsForCountry(a.country_id, a.province_id);
                    }
                    if (a.street) document.getElementById('addr-street').value = a.street;
                    if (a.number) document.getElementById('addr-number').value = a.number;
                    if (a.floor_apartment) document.getElementById('addr-floor').value = a.floor_apartment;
                    if (a.postal_code) document.getElementById('addr-postal').value = a.postal_code;
                    if (a.notes) document.getElementById('addr-notes').value = a.notes;
                }
            });
        })
        .catch(function() {});
    }

    function loadRegionsForCountry(countryId, selectedId) {
        var sel = document.getElementById('addr-province');
        sel.innerHTML = '<option value="">Cargando...</option>';
        sel.disabled = true;
        if (!countryId) {
            sel.innerHTML = '<option value="">-- Seleccionar país primero --</option>';
            return;
        }
        fetch('{{ url("/regions") }}?country_id=' + countryId, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(regions) {
            sel.innerHTML = '<option value="">-- Seleccionar --</option>';
            regions.forEach(function(r) {
                var opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name;
                sel.appendChild(opt);
            });
            if (selectedId) sel.value = selectedId;
            sel.disabled = false;
        })
        .catch(function() {
            sel.innerHTML = '<option value="">Error al cargar regiones</option>';
        });
    }

    function submitAddress() {
        var countryId = document.getElementById('addr-country').value;
        var provinceId = document.getElementById('addr-province').value;
        var street = document.getElementById('addr-street').value.trim();
        var number = document.getElementById('addr-number').value.trim();
        var floor = document.getElementById('addr-floor').value.trim();
        var postal = document.getElementById('addr-postal').value.trim();
        var notes = document.getElementById('addr-notes').value.trim();

        if (!countryId) {
            showMsg('direccion-msg', 'Seleccioná un país.', false);
            return;
        }

        fetch('{{ url("/profile/address") }}', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({
                country_id: parseInt(countryId),
                province_id: provinceId ? parseInt(provinceId) : null,
                street: street,
                number: number,
                floor_apartment: floor,
                postal_code: postal,
                notes: notes
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                var firstKey = Object.keys(data.errors)[0];
                showMsg('direccion-msg', data.errors[firstKey][0], false);
                return;
            }
            if (data.success) {
                showMsg('direccion-msg', data.message, true);
            }
        })
        .catch(function() {
            showMsg('direccion-msg', 'Error de conexión.', false);
        });
    }
</script>
</html>