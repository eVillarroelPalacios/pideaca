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
                @foreach($modules->sortBy(function ($m) { return $m->description === 'Administrar' ? 1 : 0; }) as $module)
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <div class="nav-dropdown" style="position:relative;">
                    <button class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;text-decoration:none;">
                        @if($module->icon)
                        <span style="display:flex;align-items:center;">{!! $module->icon !!}</span>
                        @endif
                        {{ $module->description }}
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="nav-dropdown-menu" style="display:none;position:absolute;top:100%;right:0;background:white;border-radius:0;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;padding:6px 0;z-index:100;">
                        @foreach($module->pages as $page)
                            @if($page->url)
                        <a href="#" onclick="event.preventDefault();closeDropdowns();showDashSection('{{ $page->url }}')" class="dropdown-item" style="display:block;padding:8px 16px;color:#1f2937;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;">
                            {{ $page->description }}
                        </a>
                            @else
                        <div class="dropdown-item" title="Todavia no tiene seccion" style="display:block;padding:8px 16px;color:#9ca3af;font-size:13px;font-weight:500;white-space:nowrap;cursor:default;">
                            {{ $page->description }} <span style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">pronto</span>
                        </div>
                            @endif
                        @endforeach
                        @if($module->description === 'Administrar' || $loop->last)
                        <div style="width:100%;height:1px;background:#e5e7eb;margin:4px 0;"></div>
                        <a href="#" onclick="event.preventDefault();closeDropdowns();doLogout()" class="dropdown-item" style="display:block;padding:8px 16px;color:#dc2626;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;">
                            Cerrar Sesión
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
                <button id="menu-toggle" onclick="toggleMenu()" style="display:none;background:none;border:none;color:white;font-size:24px;cursor:pointer;padding:4px 8px;">&#9776;</button>
            </div>
            <div id="mobile-menu" style="display:none;background:#ffffff;padding:10px 8px;position:absolute;top:100%;left:0;right:0;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <div style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:8px 0;">
                    <div style="width:100%;height:1px;background:#e5e7eb;"></div>
                    @foreach($modules->sortBy(function ($m) { return $m->description === 'Administrar' ? 1 : 0; }) as $module)
                        <button onclick="toggleMobileSub(this)" style="width:100%;background:none;border:none;font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;padding:8px 0;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                            @if($module->icon)
                            <span style="display:flex;align-items:center;">{!! $module->icon !!}</span>
                            @endif
                            {{ $module->description }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div class="mobile-sub" style="display:none;width:100%;text-align:center;">
                            @foreach($module->pages as $page)
                                @if($page->url)
                            <a href="#" onclick="showDashSection('{{ $page->url }}')" style="display:block;color:#1f2937;text-decoration:none;font-size:13px;font-weight:500;padding:6px 0;">{{ $page->description }}</a>
                                @else
                            <div title="Todavia no tiene seccion" style="display:block;color:#9ca3af;font-size:13px;font-weight:500;padding:6px 0;cursor:default;">{{ $page->description }} <span style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">pronto</span></div>
                                @endif
                            @endforeach
                            @if($module->description === 'Administrar' || $loop->last)
                            <div style="width:60%;height:1px;background:#e5e7eb;margin:6px auto;"></div>
                            <button type="button" onclick="doLogout()" style="background:none;border:none;color:#dc2626;font-size:13px;font-weight:600;cursor:pointer;padding:6px 0;">Cerrar Sesión</button>
                            @endif
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
                    .btn-icon{display:inline-flex;align-items:center;justify-content:center;padding:7px 8px;line-height:0;}
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
                    #user-sidebar{width:230px;min-width:230px;background:#ffffff;color:#1e293b;display:flex;flex-direction:column;border-right:1px solid #e5e7eb;align-self:stretch;}
                    #user-content{flex:1;display:flex;flex-direction:column;min-width:0;background:#fff;}
                .user-panel{display:none;padding:20px 24px;}
                .user-panel.active{display:block;animation:tabFade .18s ease;}
                #profile-usuarios-slot .user-panel{padding:0;}
                    #user-sidebar-toggle{display:none;}
                    @keyframes tabFade{from{opacity:0;transform:translateY(4px);}to{opacity:1;transform:translateY(0);}}
                    .user-demo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;}
                    .user-demo-label{font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
                    .user-demo-value{font-size:13px;color:#0f172a;font-weight:500;}
                    .user-demo-section-title{font-size:13px;font-weight:700;color:#0f172a;margin:0 0 14px;text-transform:uppercase;letter-spacing:.4px;}
                    .user-demo-empty-tab{padding:36px 16px;text-align:center;color:#64748b;font-size:13px;background:#f8fafc;border:1px dashed #e2e8f0;}
                    .user-account-tabs{display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-top:22px;}
                    .user-account-tab{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:13px;font-weight:600;color:#64748b;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:color .15s ease,border-color .15s ease,background .15s ease;}
                    .user-account-tab:hover{color:#0f172a;background:#f8fafc;}
                    .user-account-tab.active{color:#D24C19;border-bottom-color:#D24C19;background:linear-gradient(180deg,rgba(210,76,25,.06),transparent);}
                    .user-account-tab .tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;border-radius:9999px;background:#e2e8f0;color:#475569;font-size:11px;font-weight:700;}
                    .user-account-tab.active .tab-count{background:#D24C19;color:#fff;}
                    .user-account-pane{padding-top:18px;animation:tabFade .2s ease;}
                    .user-pages-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;}
                    .user-page-card{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;transition:border-color .15s ease,box-shadow .15s ease;}
                    .user-page-card:hover{border-color:#fdba74;box-shadow:0 2px 8px rgba(210,76,25,.08);}
                    .user-page-card .page-icon{width:32px;height:32px;border-radius:8px;background:#fff7ed;color:#D24C19;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
                    .user-page-card .page-name{font-size:13px;font-weight:600;color:#0f172a;}
                    .user-page-card .page-meta{font-size:11px;color:#64748b;margin-top:2px;}
                    .user-page-card .page-url{font-size:11px;color:#D24C19;margin-top:4px;word-break:break-all;font-family:ui-monospace,monospace;}
                    .user-pages-grid{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;}
                    .user-pages-grid th{background:#f8fafc;color:#475569;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:10px 14px;text-align:left;border-bottom:1px solid #e5e7eb;}
                    .user-pages-grid td{padding:10px 14px;font-size:13px;color:#0f172a;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
                    .user-pages-grid tr:last-child td{border-bottom:none;}
                    .user-pages-grid tr:hover td{background:#fffaf6;}
                    .user-pages-grid .page-module{display:block;font-size:11px;color:#94a3b8;margin-top:2px;}
                    .user-pages-grid thead tr{background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%) !important;}
                    .user-pages-grid thead th{background:transparent !important;color:#ffffff !important;border-bottom:none;}
                    .user-pages-msg{margin-top:10px;font-size:12px;}
                    .user-pages-msg.ok{color:#047857;}
                    .user-pages-msg.err{color:#dc2626;}
                    @media (max-width:768px){
                        .profile-field-grid{grid-template-columns:1fr;}
                        .img-zones{grid-template-columns:1fr !important;}
                        #profile-sidebar{position:fixed;top:60px;left:0;height:calc(100vh - 60px);transform:translateX(-100%);z-index:250;transition:transform .25s ease;}
                        #profile-sidebar.open{transform:translateX(0);}
                        #profile-content{margin-left:0 !important;}
                        #sidebar-toggle-btn{display:flex !important;}
                        #user-sidebar{position:fixed;top:60px;left:0;height:calc(100vh - 60px);transform:translateX(-100%);z-index:260;transition:transform .25s ease;overflow-y:auto;}
                        #user-sidebar.open{transform:translateX(0);}
                        #user-sidebar-toggle{display:flex !important;}
                    }
                </style>

                <div style="display:flex;min-height:calc(100vh - 60px);">

                    <div id="sidebar-overlay" onclick="toggleProfileSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:240;"></div>

                    @php
                        $esPerfilPrestador = strcasecmp((string) $user->typeUser?->description, 'Prestador') === 0;
                        $esPerfilCliente = strcasecmp((string) $user->typeUser?->description, 'Cliente') === 0;
                        $perfilPanelInicial = $esPerfilPrestador ? 'negocio' : ($esPerfilCliente ? 'direccion' : 'cuenta');
                        $perfilEtiquetas = [
                            'negocio' => 'Datos del Negocio',
                            'direccion' => 'Mi Dirección',
                            'cuenta' => 'Contraseñas',
                        ];
                        $estadoPerfil = $user->status?->status ?? '-';
                        $estadoPerfilKey = strtolower(trim($estadoPerfil));
                        $estadoPerfilEstilo = match (true) {
                            in_array($estadoPerfilKey, ['active', 'activo', 'activa'], true) => 'background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;',
                            in_array($estadoPerfilKey, ['inactive', 'inactivo', 'inactiva'], true) => 'background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;',
                            $estadoPerfilKey === 'prueba' => 'background:#fffbeb;border:1px solid #fde68a;color:#b45309;',
                            default => '',
                        };
                    @endphp

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
                            @if($esPerfilPrestador)
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
                                <span>Publicidad</span>
                            </a>
                            <a href="#" onclick="event.preventDefault();showProfilePanel('categorias')" class="sidebar-link" data-panel="categorias" id="sidebar-link-categorias">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path d="M6 6h.008v.008H6V6Z"/></svg>
                                <span>Categorias</span>
                            </a>
                            @endif
                            <a href="#" onclick="event.preventDefault();showProfilePanel('direccion')" class="sidebar-link{{ $perfilPanelInicial === 'direccion' ? ' active' : '' }}" data-panel="direccion" id="sidebar-link-direccion">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                <span>Mi Dirección</span>
                            </a>
                            @if(strcasecmp((string) $user->typeUser?->description, 'Cliente') !== 0)
                            <a href="#" onclick="event.preventDefault();showProfilePanel('usuarios')" class="sidebar-link" data-panel="usuarios" id="sidebar-link-usuarios">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                <span>Usuarios</span>
                            </a>
                            @endif
                            <a href="#" onclick="event.preventDefault();showProfilePanel('cuenta')" class="sidebar-link{{ $perfilPanelInicial === 'cuenta' ? ' active' : '' }}" data-panel="cuenta" id="sidebar-link-cuenta">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                <span>Contraseñas</span>
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
                            <span style="font-size:12px;color:#0f172a;font-weight:600;" id="breadcrumb-current">{{ $perfilEtiquetas[$perfilPanelInicial] }}</span>
                            <div style="flex:1;"></div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-size:12px;color:#0f172a;font-weight:600;">{{ $user->name }}</span>
                                <div style="width:28px;height:28px;border-radius:50%;background:#D24C19;color:white;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            </div>
                        </div>

                        <div style="flex:1;overflow-y:auto;padding:24px;background:#f1f5f9;">
                            <div id="profile-loading" style="text-align:center;padding:40px;color:#64748b;font-size:13px;{{ $esPerfilPrestador ? '' : 'display:none;' }}">Cargando tu panel...</div>
                            <div id="profile-panels" style="{{ $esPerfilPrestador ? 'display:none;' : '' }}max-width:960px;margin:0 auto;">

                                <div class="profile-panel{{ $esPerfilPrestador ? ' active' : '' }}" data-panel="negocio">
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
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg> Publicidad</h3>
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
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path d="M6 6h.008v.008H6V6Z"/></svg> Categorias y Servicios</h3>
                                        <p class="pcard-sub">Seleccioná los grupos y subgrupos de servicios que ofrecés en tu negocio.</p>
                                        <div id="categorias-loading" style="text-align:center;padding:20px;color:#94a3b8;font-size:12px;">Cargando categorías...</div>
                                        <div id="categorias-list" style="display:none;"></div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:16px;">
                                            <span class="save-msg" id="categorias-msg"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="profile-panel{{ $perfilPanelInicial === 'direccion' ? ' active' : '' }}" data-panel="direccion">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg> Mi Dirección</h3>
                                        <p class="pcard-sub">{{ $esPerfilPrestador ? 'Configurá la ubicación de tu negocio para que los clientes te encuentren.' : 'Configurá la dirección a la que querés recibir tus pedidos.' }}</p>
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

                                <div class="profile-panel" data-panel="usuarios">
                                    <div id="profile-usuarios-slot"></div>
                                </div>

                                <div class="profile-panel{{ $perfilPanelInicial === 'cuenta' ? ' active' : '' }}" data-panel="cuenta">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg> Datos personales</h3>
                                        <p class="pcard-sub">Información de tu cuenta de usuario.</p>
                                        <div class="profile-field-grid">
                                            <div><label class="field-label">Nombre</label><input type="text" id="profile-name" class="field-input" value="{{ $user->name }}" /><span class="err-msg" id="profile-name-error"></span></div>
                                            <div><label class="field-label">Correo</label><input type="email" id="profile-email" class="field-input" value="{{ $user->email }}" /><span class="err-msg" id="profile-email-error"></span></div>
                                            <div><label class="field-label">Estado</label><span id="profile-status" class="status-badge" style="{{ $estadoPerfilEstilo }}">{{ $estadoPerfil }}</span></div>
                                            <div><label class="field-label">Tipo de usuario</label><span id="profile-type" class="status-badge">{{ $user->typeUser?->description ?? '-' }}</span></div>
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
                                            <div><label class="field-label">Nueva contraseña</label>
                                                <div style="position:relative;">
                                                    <input type="password" id="pw-new" class="field-input" autocomplete="new-password" style="padding-right:36px;" />
                                                    <button type="button" onclick="togglePwVis('pw-new', this)" title="Mostrar contraseña" aria-label="Mostrar contraseña" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#64748b;display:flex;align-items:center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg></button>
                                                </div>
                                                <span class="err-msg" id="pw-new-error"></span></div>
                                            <div style="grid-column:1;"><label class="field-label">Confirmar nueva contraseña</label>
                                                <div style="position:relative;">
                                                    <input type="password" id="pw-confirm" class="field-input" autocomplete="new-password" style="padding-right:36px;" />
                                                    <button type="button" onclick="togglePwVis('pw-confirm', this)" title="Mostrar contraseña" aria-label="Mostrar contraseña" style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#64748b;display:flex;align-items:center;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg></button>
                                                </div>
                                                <span class="err-msg" id="pw-confirm-error"></span></div>
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
            @include('dashboard.sections.modulos')
                    @elseif($page->url === 'grupos')
            @include('dashboard.sections.grupos')
                    @elseif($page->url === 'sub-grupos')
            @include('dashboard.sections.sub-grupos')
                    @elseif($page->url === 'paginas')
            @include('dashboard.sections.paginas')
                    @elseif($page->url === 'tipo-usuarios')
            @include('dashboard.sections.tipo-usuarios')
                    @elseif($page->url === 'estados-usuarios')
            @include('dashboard.sections.estados-usuarios')
                    @elseif($page->url === 'estados-grupos')
            @include('dashboard.sections.estados-grupos')
                    @elseif($page->url === 'unidades-medida')
            @include('dashboard.sections.unidades-medida')
                    @elseif($page->url === 'usuarios')
                    @elseif($page->url === 'mi-catalogo')
            @include('dashboard.sections.mi-catalogo')
                    @elseif($page->url === 'pedidos')
            @include('dashboard.sections.pedidos')
                    @elseif($page->url === 'inventario')
            @include('dashboard.sections.inventario')
                    @elseif($page->url === 'comercios')
            @include('dashboard.sections.comercios')
                    @elseif($page->url === 'mis-pedidos')
            @include('dashboard.sections.mis-pedidos')
                    @elseif($page->url === 'finanzas')
            @include('partials.salud-financiera')
                    @elseif($page->url === 'retencion')
            @include('partials.retencion')
                    @elseif($page->url === 'suscripciones')
            @include('partials.suscripciones-comercio')
                    @elseif($page->url === 'mis-suscripciones')
            @include('partials.mis-suscripciones')
                    @elseif($page->url)
            @include('dashboard.sections.default')
                    @endif
                @endforeach
            @endforeach
            @include('dashboard.modals.catalog')


            @include('dashboard.sections.usuarios')

            @include('dashboard.modals.users')


        </main>

        <div style="background:white;height:8px;"></div>
        <footer style="background:#0a0f1a;color:white;padding:14px 0;">
            <div style="text-align:center;">
                @include('partials.footer-group-icons')
                <p style="font-size:10px;color:#9ca3af;margin:0;">&copy; {{ date('Y') }} pideaca.com - Todos los derechos reservados.</p>
            </div>
        </footer>

        <div id="onboard-cats-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.65);z-index:400;align-items:center;justify-content:center;padding:20px;">
            <div style="background:#ffffff;width:100%;max-width:780px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 12px 40px rgba(0,0,0,0.35);">
                <div style="padding:18px 24px;border-bottom:1px solid #e5e7eb;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
                    <div>
                        <h3 style="font-size:17px;font-weight:700;color:#0c2a4d;margin:0;">Configurá tus categorías y servicios</h3>
                        <p style="font-size:12.5px;color:#64748b;margin:6px 0 0;">Seleccioná los rubros y servicios que vas a ofrecer en tu negocio. Debes elegir al menos uno para poder cerrar esta ventana.</p>
                    </div>
                    <button type="button" onclick="closeOnboarding()" title="Cerrar" aria-label="Cerrar" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:24px;line-height:1;">&times;</button>
                </div>
                <div style="padding:20px 24px;overflow-y:auto;flex:1 1 auto;">
                    <div id="onboard-cats-loading" style="text-align:center;padding:30px;color:#94a3b8;font-size:12px;">Cargando categorías...</div>
                    <div id="onboard-cats-list" style="display:none;"></div>
                </div>
                <div style="padding:14px 24px;border-top:1px solid #e5e7eb;display:flex;align-items:center;gap:10px;background:#f9fafb;flex-wrap:wrap;">
                    <span id="onboard-cats-hint" style="font-size:12px;font-weight:600;color:#dc2626;">Debés seleccionar al menos una categoría para continuar.</span>
                    <span class="save-msg" id="onboard-cats-msg"></span>
                </div>
            </div>
        </div>

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
    #groupstatuses-table tbody tr:hover,
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

    /* --- Mi catálogo: sidebar de secciones (catálogo, productos, categorías, unidades) --- */
    .fd-cat-layout { display: flex; align-items: stretch; background: #ffffff; border: 1px solid #e5e7eb; }
    #fd-cat-sidebar { width: 170px; min-width: 170px; background: #f8fafc; border-right: 1px solid #e5e7eb; display: flex; flex-direction: column; padding-bottom: 8px; }
    .fd-cat-menu-title { font-size: 10px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: #94a3b8; padding: 16px 20px 8px; }
    .fd-cat-body { flex: 1; min-width: 0; padding: 20px 22px; }
    .fd-cat-panel { display: none; }
    .fd-cat-panel.active { display: block; animation: tabFade .18s ease; }
    .fd-cat-panel-head { margin-bottom: 14px; }
    .fd-cat-panel-head h3 { font-size: 15px; font-weight: 700; color: #0c2a4d; margin: 0; }
    .fd-cat-panel-head p { font-size: 12px; color: #6b7280; margin: 3px 0 0; }
    .fd-cat-row { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-top: 1px solid #f1f5f9; background: #fff; }
    .fd-cat-row:hover { background: #f8fafc; }
    .fd-cat-actions { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
    .fd-cat-topbar { display: none; }
    #fd-cat-overlay { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 240; }
    #fd-cat-overlay.open { display: block; }
    @media (max-width: 860px) {
        #dash-mi-catalogo { margin-top: 0 !important; }
        .fd-cat-header { display: none !important; }
        .fd-cat-topbar {
            display: flex;
            align-items: center;
            gap: 8px;
            height: 44px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 20px;
            margin: 0 -20px 14px;
            position: sticky;
            top: 60px;
            z-index: 40;
        }
        .fd-cat-layout { flex-direction: column; }
        #fd-cat-sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 250px;
            min-width: 250px;
            height: calc(100vh - 60px);
            overflow-y: auto;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            border-bottom: none;
            transform: translateX(-100%);
            transition: transform .25s ease;
            z-index: 250;
        }
        #fd-cat-sidebar.open { transform: translateX(0); }
        .fd-cat-body { padding: 16px 12px; }
    }
    @media (min-width: 861px) {
        #fd-cat-overlay { display: none !important; }
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

    var fdSectionHistoryReady = false;
    var fdSectionPopNav = false;
    // Listado de comercios: seccion de salida de la tienda (volver y fallback
    // del historial si el navegador avanza a la tienda ya cerrada).
    var FD_SECTION_SHOP_FALLBACK = 'comercios';

    function showDashSection(key) {
        unmountProfileUsuariosPanel();
        var sections = document.querySelectorAll('.dash-section');
        sections.forEach(function (s) { s.style.display = 'none'; });
        var target = document.getElementById('dash-' + key);
        if (target) target.style.display = 'block';

        // Cada seccion deja una entrada en el historial: el boton Atras del
        // navegador recorre el panel del usuario en vez de salir al sitio.
        if (!fdSectionPopNav && (!history.state || history.state.fdSection !== key)) {
            var state = { fdSection: key };
            if (fdSectionHistoryReady) {
                history.pushState(state, '', '{{ url('/dashboard') }}');
            } else {
                history.replaceState(state, '', '{{ url('/dashboard') }}');
                fdSectionHistoryReady = true;
            }
        }

        closeDropdowns();
        var menu = document.getElementById('mobile-menu');
        if (menu) menu.style.display = 'none';
    }

    window.addEventListener('popstate', function (e) {
        var key = (e.state && e.state.fdSection) || 'perfil';
        fdSectionPopNav = true;
        try {
            if (key === 'tienda') {
                var shop = document.getElementById('fd-shop-overlay');
                if (typeof FD_SHOP !== 'undefined' && FD_SHOP.providerId && shop) {
                    document.querySelectorAll('.dash-section').forEach(function (s) { s.style.display = 'none'; });
                    shop.style.display = 'block';
                } else {
                    showDashSection(FD_SECTION_SHOP_FALLBACK);
                }
            } else {
                showDashSection(key);
            }
        } finally {
            fdSectionPopNav = false;
        }
    });

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
    var allGroupStatusOptions = [];
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

    function openGroupModal(id, description, icon, statusId) {
        document.getElementById('group-id').value = id || '';
        document.getElementById('group-description').value = description || '';
        document.getElementById('group-icon').value = icon || '';
        document.getElementById('group-description-error').style.display = 'none';
        document.getElementById('group-status-error').style.display = 'none';
        document.getElementById('group-modal-title').textContent = id ? 'Editar Grupo' : 'Nuevo Grupo';
        document.getElementById('group-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('group-modal-overlay').style.display = 'flex';
        loadGroupStatusOptions(statusId);
        document.getElementById('group-description').focus();
    }

    function loadGroupStatusOptions(selectedId) {
        var sel = document.getElementById('group-status-select');
        sel.innerHTML = '<option value="">Cargando estados...</option>';
        sel.disabled = true;

        fetch('{{ url("/group-statuses") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(list) {
            fillGroupStatusSelect(Array.isArray(list) ? list : [], selectedId);
        })
        .catch(function() {
            sel.innerHTML = '<option value="">No se pudieron cargar los estados</option>';
            sel.disabled = false;
        });
    }

    function fillGroupStatusSelect(statuses, selectedId) {
        var sel = document.getElementById('group-status-select');
        allGroupStatusOptions = statuses;
        sel.innerHTML = '';

        if (statuses.length === 0) {
            sel.innerHTML = '<option value="">Sin estados disponibles</option>';
            sel.disabled = false;
            return;
        }

        statuses.forEach(function(s) {
            var opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.description;
            sel.appendChild(opt);
        });
        sel.disabled = false;

        var wanted = selectedId ? String(selectedId) : '';
        var match = wanted && statuses.some(function(s) { return String(s.id) === wanted; });
        if (match) {
            sel.value = wanted;
            return;
        }
        // Grupo nuevo (o estado eliminado): se preselecciona "Activo".
        var activo = statuses.find(function(s) { return s.description === 'Activo'; });
        sel.value = activo ? activo.id : statuses[0].id;
    }

    function closeGroupModal() {
        document.getElementById('group-modal-overlay').style.display = 'none';
    }

    function editGroup(id) {
        var g = allGroups.find(function(grp) { return grp.id === id; });
        if (g) openGroupModal(g.id, g.description, g.icon, g.group_status_id);
    }

    function submitGroup(e) {
        e.preventDefault();
        var id = document.getElementById('group-id').value;
        var desc = document.getElementById('group-description').value.trim();
        var iconVal = document.getElementById('group-icon').value.trim();
        var statusSel = document.getElementById('group-status-select');
        var errorEl = document.getElementById('group-description-error');
        var statusErrorEl = document.getElementById('group-status-error');
        var submitBtn = document.getElementById('group-submit-btn');

        errorEl.style.display = 'none';
        statusErrorEl.style.display = 'none';

        if (!desc) {
            errorEl.textContent = 'La descripción es obligatoria.';
            errorEl.style.display = 'block';
            return;
        }

        var payload = { description: desc, icon: iconVal };
        if (statusSel.value) {
            payload.group_status_id = parseInt(statusSel.value, 10);
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
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                if (data.errors.group_status_id) {
                    statusErrorEl.textContent = data.errors.group_status_id[0];
                    statusErrorEl.style.display = 'block';
                }
                if (data.errors.description) {
                    errorEl.textContent = data.errors.description[0];
                    errorEl.style.display = 'block';
                } else if (!data.errors.group_status_id) {
                    errorEl.textContent = 'Error de validación.';
                    errorEl.style.display = 'block';
                }
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

    function loadSubGroupFilter() {
        var select = document.getElementById('subgroup-group-filter');
        if (!select) return Promise.resolve();

        var current = select.value;
        select.innerHTML = '<option value="">Todos los grupos</option>';

        return fetch('{{ url("/groups") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            (Array.isArray(data) ? data : []).forEach(function(g) {
                var opt = document.createElement('option');
                opt.value = g.id;
                opt.textContent = g.description;
                select.appendChild(opt);
            });
            select.value = current;
        })
        .catch(function() {});
    }

    function loadSubGroups() {
        subgroupCurrentPage = 1;
        document.getElementById('subgroups-loading').style.display = 'block';
        document.getElementById('subgroups-empty').style.display = 'none';
        document.getElementById('subgroups-table-wrap').style.display = 'none';

        // Se esperan el combo de grupos y los sub grupos: si no, la grilla se
        // pintaba sin filtrar mientras el combo ya tenia un grupo elegido.
        Promise.all([
            loadSubGroupFilter(),
            fetch('{{ url("/subgroups") }}', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(function(r) { return r.json(); })
        ])
        .then(function(results) {
            allSubGroups = Array.isArray(results[1]) ? results[1] : [];
            allSubGroups._filtered = allSubGroups;
            applySubGroupFilters(false);
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
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(s.description) + '</td>' +
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

    // Aplica el texto del buscador y el grupo elegido en el combo. Es la unica
    // via de pintado: asi la grilla respeta los filtros al cargar y al refrescar.
    function applySubGroupFilters(resetPage) {
        var search = document.getElementById('subgroup-search');
        var combo = document.getElementById('subgroup-group-filter');
        if (resetPage) subgroupCurrentPage = 1;

        var q = ((search && search.value) || '').toLowerCase();
        var groupId = combo ? String(combo.value) : '';
        var list = Array.isArray(allSubGroups) ? allSubGroups : [];

        var filtered = list.filter(function(s) {
            var text = (s.description || '').toLowerCase();
            var matchText = !q || text.indexOf(q) !== -1;
            var matchGroup = !groupId || String(s.group_id) === groupId;
            return matchText && matchGroup;
        });

        allSubGroups._filtered = filtered;
        renderSubGroups(filtered);
    }

    function filterSubGroups() {
        applySubGroupFilters(true);
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

    // ============ FAST DELIVERY: CATÁLOGO Y PEDIDOS DEL PRESTADOR ============

    var FD_ORDER_STATUS = {
        'pending': ['Pendiente', '#b45309', '#fef3c7'],
        'confirmed': ['Confirmado', '#1d4ed8', '#dbeafe'],
        'in_preparation': ['En preparación', '#6d28d9', '#ede9fe'],
        'on_the_way': ['En camino', '#0369a1', '#e0f2fe'],
        'delivered': ['Entregado', '#047857', '#d1fae5'],
        'cancelled': ['Cancelado', '#b91c1c', '#fee2e2']
    };

    var FD_PAYMENT_STATUS = {
        'pending': ['Pago pendiente', '#b45309', '#fef3c7'],
        'processing': ['Pago en proceso', '#1d4ed8', '#dbeafe'],
        'paid': ['Pagado', '#047857', '#d1fae5'],
        'failed': ['Pago fallido', '#b91c1c', '#fee2e2'],
        'refunded': ['Reembolsado', '#6b7280', '#f1f5f9']
    };

    function fdProviderId(sectionId) {
        var el = document.getElementById(sectionId);
        var id = el ? parseInt(el.getAttribute('data-provider-id'), 10) : NaN;
        return isNaN(id) ? null : id;
    }

    function fdMoney(value) {
        var n = Number(value || 0);
        return '$ ' + n.toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function fdDate(value) {
        if (!value) return '';
        var d = new Date(String(value).replace(' ', 'T'));
        return isNaN(d.getTime()) ? '' : d.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
    }

    function fdBadge(text, color, bg) {
        return '<span style="display:inline-block;font-size:11px;font-weight:600;padding:3px 9px;border-radius:9999px;color:' +
            color + ';background:' + bg + ';white-space:nowrap;">' + escapeHtml(text) + '</span>';
    }

    function fdStatusBadge(status) {
        var s = FD_ORDER_STATUS[status] || [status || '-', '#334155', '#f1f5f9'];
        return fdBadge(s[0], s[1], s[2]);
    }

    function fdPaymentBadge(payments) {
        var p = (payments && payments[0]) || null;
        if (!p) return '';
        var s = FD_PAYMENT_STATUS[p.status] || [p.status || '-', '#334155', '#f1f5f9'];
        return fdBadge(s[0], s[1], s[2]);
    }

    // Siguiente paso del flujo que el comercio puede marcar en cada pedido.
    var FD_ORDER_FLOW = {
        'pending': ['confirmed', 'Confirmar pedido'],
        'confirmed': ['in_preparation', 'Marcar en preparación'],
        'in_preparation': ['on_the_way', 'Marcar en camino'],
        'on_the_way': ['delivered', 'Marcar entregado']
    };

    var FD_ORDER_CANCELLABLE = ['pending', 'confirmed', 'in_preparation'];

    function fdOrderActions(o) {
        var html = '<div style="border-top:1px solid #e5e7eb;margin-top:10px;padding-top:10px;display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">';
        var flow = FD_ORDER_FLOW[o.status];

        if (flow) {
            html += '<button type="button" data-order-action onclick="fdUpdateOrderStatus(' + Number(o.id) + ', \'' + flow[0] + '\', this)" '
                + 'style="background:#D24C19;color:#fff;border:none;padding:8px 14px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">'
                + escapeHtml(flow[1]) + '</button>';
        }

        if (FD_ORDER_CANCELLABLE.indexOf(o.status) !== -1) {
            html += '<button type="button" data-order-action onclick="fdUpdateOrderStatus(' + Number(o.id) + ', \'cancelled\', this)" '
                + 'style="background:#fff;color:#b91c1c;border:1px solid #fecaca;padding:8px 14px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;">'
                + 'Cancelar</button>';
        }

        return html + '</div>';
    }

    function fdUpdateOrderStatus(orderId, status, btn) {
        if (btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }

        fdFetchJson('{{ url("/api/orders") }}/' + orderId + '/status', {
            method: 'PATCH',
            body: JSON.stringify({ status: status })
        })
        .then(function (res) {
            if (!res.ok) {
                fdToast((res.data && res.data.message) || 'No se pudo actualizar el pedido.', true);
                loadProviderOrders();
                return;
            }

            var card = document.querySelector('[data-order-id="' + orderId + '"]');
            if (card && res.data.order) {
                card.outerHTML = fdRenderOrder(res.data.order);
                fdOrdersLastHtml = '';
            } else {
                loadProviderOrders(true);
            }

            fdToast('Pedido actualizado: ' + (FD_ORDER_STATUS[res.data.status] || [res.data.status])[0] + '.', false);
        })
        .catch(function () {
            fdToast('No se pudo conectar con el servidor.', true);
            loadProviderOrders();
        });
    }

    function fdSetCatalogState(state, text) {
        var map = { loading: 'fd-catalog-loading', message: 'fd-catalog-message', content: 'fd-catalog-content' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-catalog-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function fdSetOrdersState(state, text) {
        var map = { loading: 'fd-orders-loading', message: 'fd-orders-message', content: 'fd-orders-list' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-orders-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function fdFetchJson(url, options) {
        options = options || {};
        var headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        };
        Object.keys(options.headers || {}).forEach(function (k) { headers[k] = options.headers[k]; });
        if (options.body && !headers['Content-Type']) headers['Content-Type'] = 'application/json';

        return fetch(url, Object.assign({}, options, { headers: headers })).then(function (r) {
            return r.json().then(function (data) { return { ok: r.ok, data: data }; });
        });
    }

    // --- Catálogo ---

    function loadProviderCatalog() {
        var providerId = fdProviderId('dash-mi-catalogo');
        if (!providerId) {
            fdSetCatalogState('message', 'Todavía no tenés un comercio configurado.\nCreá tu comercio desde Tu Perfil para cargar el catálogo.');
            return;
        }

        fdSetCatalogState('loading');

        fdFetchJson('{{ url("/api/providers") }}/' + providerId + '/catalog')
            .then(function (res) {
                if (!res.ok) {
                    fdSetCatalogState('message', (res.data && res.data.message) || 'No se pudo cargar el catálogo.');
                    return;
                }

                var subtitle = document.getElementById('fd-catalog-subtitle');
                if (subtitle && res.data.provider) {
                    subtitle.textContent = res.data.provider.business_name +
                        (res.data.provider.is_active === false ? ' · comercio pausado' : '');
                }

                var categories = res.data.categories || [];

                if (!categories.length) {
                    fdSetCatalogState('message', 'Tu catálogo está vacío.\nCreá categorías y productos para que los clientes puedan pedir.');
                    return;
                }

                fdPrevData = { categories: categories, provider: res.data.provider };

                document.getElementById('fd-catalog-content').innerHTML =
                    fdRenderCatalog(categories, res.data.provider);

                fdSetCatalogState('content');
            })
            .catch(function () {
                fdSetCatalogState('message', 'No se pudo conectar con el servidor.');
            });
    }

    // --- Sidebar de Mi catálogo: catálogo, productos, categorías y unidades ---

    var fdCatActive = 'catalogo';

    var FD_CAT_PANELS = {
        catalogo:  { loading: 'fd-catalog-loading', message: 'fd-catalog-message', content: 'fd-catalog-content', text: 'fd-catalog-message-text' },
        productos: { loading: 'fd-prod-loading',    message: 'fd-prod-message',    content: 'fd-prod-content',    text: 'fd-prod-message-text' },
        categorias:{ loading: 'fd-cats-loading',    message: 'fd-cats-message',    content: 'fd-cats-content',    text: 'fd-cats-message-text' },
        unidades:  { loading: 'fd-units-loading',   message: 'fd-units-message',   content: 'fd-units-content',   text: 'fd-units-message-text' }
    };

    var FD_CAT_LABELS = {
        catalogo: 'Mi catálogo',
        productos: 'Productos',
        categorias: 'Categorías',
        unidades: 'Uds. de medida'
    };

    function fdPanelState(ids, state, text) {
        ['loading', 'message', 'content'].forEach(function (k) {
            var el = document.getElementById(ids[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById(ids.text);
            if (msg) msg.textContent = text;
        }
    }

    function fdCatTab(key) {
        if (!FD_CAT_PANELS[key]) return;
        fdCatActive = key;

        document.querySelectorAll('[data-cat-panel]').forEach(function (a) {
            a.classList.toggle('active', a.getAttribute('data-cat-panel') === key);
        });

        document.querySelectorAll('.fd-cat-panel').forEach(function (p) {
            p.classList.toggle('active', p.id === 'fd-cat-panel-' + key);
        });

        var bc = document.getElementById('fd-cat-breadcrumb');
        if (bc) bc.textContent = FD_CAT_LABELS[key] || key;
        fdCatCloseSidebar();

        fdCatLoad(key);
    }

    function fdCatToggleSidebar() {
        var sb = document.getElementById('fd-cat-sidebar');
        var ov = document.getElementById('fd-cat-overlay');
        if (!sb) return;
        var open = sb.classList.toggle('open');
        if (ov) ov.classList.toggle('open', open);
    }

    function fdCatCloseSidebar() {
        var sb = document.getElementById('fd-cat-sidebar');
        var ov = document.getElementById('fd-cat-overlay');
        if (sb) sb.classList.remove('open');
        if (ov) ov.classList.remove('open');
    }

    function fdCatLoad(key) {
        if (key === 'catalogo') loadProviderCatalog();
        else if (key === 'productos') loadProductsPanel();
        else if (key === 'categorias') loadCategoriesPanel();
        else if (key === 'unidades') loadUnitsPanel();
    }

    // Se ejecuta cada vez que se entra a la sección desde el menú.
    function fdCatShow() {
        fdCatTab(fdCatActive);
    }

    // Recarga el panel a la vista y deja la caché de productos fresca.
    function fdCatReload() {
        fdProdData = null;
        fdCatLoad(fdCatActive);
    }

    function fdSeq(tareas) {
        return tareas.reduce(function (p, t) {
            return p.then(function () { return t(); });
        }, Promise.resolve());
    }

    function fdCheck(res, mensaje) {
        if (!res.ok) {
            throw new Error((res.data && (res.data.message || Object.values(res.data.errors || {})[0])) || mensaje);
        }
        return res;
    }

    // --- Panel: productos (con variantes y agregados) ---

    function loadProductsPanel() {
        fdPanelState(FD_CAT_PANELS.productos, 'loading');

        fdFetchJson('{{ url("/api/v1/provider/products") }}')
            .then(function (res) {
                fdCheck(res, 'No se pudieron cargar los productos.');
                fdProdData = res.data;
                document.getElementById('fd-prod-content').innerHTML = fdRenderProducts(res.data);
                fdPanelState(FD_CAT_PANELS.productos, 'content');
            })
            .catch(function (e) {
                fdPanelState(FD_CAT_PANELS.productos, 'message', e.message || 'No se pudo conectar con el servidor.');
            });
    }

    var fdProdCatFilter = '';

    var FD_ICON_EDIT = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>';
    var FD_ICON_VARIANTS = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>';
    var FD_ICON_ADDONS = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>';
    var FD_ICON_PLUS = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4.5v15m7.5-7.5h-15"/></svg>';
    var FD_ICON_SAVE = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.75l6 6 9-13.5"/></svg>';
    var FD_ICON_UP = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>';
    var FD_ICON_DOWN = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>';
    var FD_ICON_TRASH = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';
    var FD_ICON_BOOK = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>';
    var FD_ICON_USER_PLUS = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>';
    var FD_ICON_STAR = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"/></svg>';
    var FD_ICON_STAR_FILLED = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"/></svg>';

    function fdProdFilterChange(value) {
        fdProdCatFilter = value || '';
        if (!fdProdData) return;
        var content = document.getElementById('fd-prod-content');
        if (content) content.innerHTML = fdRenderProducts(fdProdData);
    }

    function fdRenderProducts(data) {
        var all = data.products || [];
        var cats = data.categories || [];

        var selected = String(fdProdCatFilter || '');
        if (selected && !cats.some(function (c) { return String(c.id) === selected; })) {
            selected = '';
            fdProdCatFilter = '';
        }

        var products = selected
            ? all.filter(function (p) { return String(p.category_id) === selected; })
            : all;

        if (!all.length) {
            return '<div style="background:#fff;border:1px solid #e5e7eb;padding:36px 20px;text-align:center;">'
                + '<p style="font-size:14px;color:#6b7280;margin:0 0 14px;">Todavía no cargaste productos.</p>'
                + '<button type="button" class="btn-primary" onclick="openProductModal(null)">+ Agregar producto</button>'
                + '</div>';
        }

        var combo = '<div style="margin-left:auto;display:flex;align-items:center;gap:6px;">'
            + '<label for="fd-prod-cat-filter" style="font-size:12px;color:#6b7280;font-weight:600;">Categoría</label>'
            + '<select id="fd-prod-cat-filter" onchange="fdProdFilterChange(this.value)" style="font-size:13px;padding:7px 10px;border:1px solid #cbd5e1;border-radius:0;background:#fff;color:#0f172a;cursor:pointer;">'
            + '<option value="">Todas las categorías</option>'
            + cats.map(function (c) {
                return '<option value="' + escapeHtml(String(c.id)) + '"' + (String(c.id) === selected ? ' selected' : '') + '>' + escapeHtml(c.name) + '</option>';
            }).join('')
            + '</select>'
            + '</div>';

        var html = '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px;">'
            + fdBadge(products.length + ' productos', '#0c2a4d', '#eef2f7')
            + fdBadge(cats.length + ' categorías', '#0c2a4d', '#eef2f7')
            + combo
            + '</div>';

        if (!products.length) {
            return html + '<div style="background:#fff;border:1px solid #e5e7eb;padding:32px 20px;text-align:center;">'
                + '<p style="font-size:13px;color:#6b7280;margin:0;">No hay productos en esta categoría.</p>'
                + '</div>';
        }

        html += '<div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">'
            + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            + '<thead><tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Producto</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Precio</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Variantes</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Agregados</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Stock</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Acciones</th>'
            + '</tr></thead><tbody>';

        products.forEach(function (p, i) {
            var variantes = p.variants || [];
            var grupos = p.option_groups || [];
            var opciones = 0;
            grupos.forEach(function (g) { opciones += (g.options || []).length; });

            var vtxt = variantes.length
                ? variantes.map(function (v) { return escapeHtml(v.name); }).join(', ')
                : '<span style="color:#94a3b8;">ninguna</span>';

            var gtxt = grupos.length
                ? fdBadge(grupos.length + ' grupo(s) · ' + opciones + ' opción(es)', '#9a3412', '#fff7ed')
                : '<span style="color:#94a3b8;">ninguno</span>';

            var stock = p.track_stock
                ? fdBadge(fdInvNum(p.current_stock) + (p.unit_of_measure ? ' ' + p.unit_of_measure.symbol : ''), '#047857', '#d1fae5')
                : fdBadge('S/S', '#64748b', '#f1f5f9');

            var id = escapeHtml(String(p.id));

            html += '<tr style="background:' + (i % 2 ? '#f8fafc' : '#fff') + ';border-top:1px solid #f1f5f9;">'
                + '<td style="padding:10px 12px;color:#0f172a;font-weight:600;min-width:170px;">'
                + escapeHtml(p.name)
                + (p.is_available === false ? ' ' + fdBadge('No disponible', '#b91c1c', '#fee2e2') : '')
                + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:#D24C19;font-weight:700;">' + fdMoney(p.price) + '</td>'
                + '<td style="padding:10px 12px;color:#475569;min-width:150px;">'
                + (variantes.length ? fdBadge(String(variantes.length), '#0c2a4d', '#eef2f7') + ' ' : '') + vtxt
                + '</td>'
                + '<td style="padding:10px 12px;">' + gtxt + '</td>'
                + '<td style="padding:10px 12px;">' + stock + '</td>'
                + '<td style="padding:10px 12px;white-space:nowrap;">'
                + '<div class="fd-cat-actions">'
                + '<button type="button" class="btn-secondary btn-icon" title="Editar" aria-label="Editar" onclick="openProductModal(' + id + ')">' + FD_ICON_EDIT + '</button>'
                + '<button type="button" class="btn-secondary btn-icon" title="Variantes" aria-label="Variantes" onclick="openVariantModal(' + id + ')">' + FD_ICON_VARIANTS + '</button>'
                + '<button type="button" class="btn-secondary btn-icon" title="Agregados" aria-label="Agregados" onclick="openOptionModal(' + id + ')">' + FD_ICON_ADDONS + '</button>'
                + '</div></td>'
                + '</tr>';
        });

        html += '</tbody></table></div>';

        return html;
    }

    // --- Panel: categorías ---

    function loadCategoriesPanel() {
        fdPanelState(FD_CAT_PANELS.categorias, 'loading');

        fdFetchJson('{{ url("/api/v1/provider/products") }}')
            .then(function (res) {
                fdCheck(res, 'No se pudieron cargar las categorías.');
                fdProdData = res.data;
                document.getElementById('fd-cats-content').innerHTML = fdRenderCategories(res.data);
                fdPanelState(FD_CAT_PANELS.categorias, 'content');
            })
            .catch(function (e) {
                fdPanelState(FD_CAT_PANELS.categorias, 'message', e.message || 'No se pudo conectar con el servidor.');
            });
    }

    function fdRenderCategories(data) {
        var cats = data.categories || [];

        var html = '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">'
            + fdBadge(cats.length + ' categorías', '#0c2a4d', '#eef2f7')
            + '</div>'
            + '<div style="background:#fff;border:1px solid #e5e7eb;padding:14px;margin-bottom:14px;">'
            + '<label class="field-label" for="fd-cat-new-name">Nueva categoría</label>'
            + '<div style="display:flex;gap:8px;align-items:flex-start;">'
            + '<div style="flex:1;min-width:0;"><input type="text" id="fd-cat-new-name" class="field-input" maxlength="80" placeholder="Ej: Bebidas frías"></div>'
            + '<button type="button" class="btn-primary btn-icon" title="Agregar" aria-label="Agregar" onclick="fdCatCreate()">' + FD_ICON_PLUS + '</button>'
            + '</div>'
            + '<span class="save-msg err" id="fd-cat-new-error" style="display:none;margin-top:6px;"></span>'
            + '</div>';

        html += '<div style="background:#fff;border:1px solid #e5e7eb;">';

        if (!cats.length) {
            html += '<div style="padding:24px 16px;font-size:13px;color:#94a3b8;text-align:center;">Todavía no tenés categorías: creá la primera arriba.</div>';
        }

        cats.forEach(function (c) {
            html += '<div class="fd-cat-row" data-cid="' + escapeHtml(String(c.id)) + '">'
                + '<div style="flex:1;min-width:0;"><input type="text" class="field-input fd-cat-name" maxlength="80" value="' + escapeHtml(c.name) + '" style="padding:7px 10px;font-size:13px;"></div>'
                + fdBadge(c.products_count + ' producto(s)', c.products_count ? '#0c2a4d' : '#64748b', c.products_count ? '#eef2f7' : '#f1f5f9')
                + (c.is_active ? '' : fdBadge('Oculta', '#9a3412', '#fff7ed'))
                + '<div class="fd-cat-actions">'
                + '<button type="button" class="btn-secondary btn-icon" title="Guardar" aria-label="Guardar" onclick="fdCatRename(this)">' + FD_ICON_SAVE + '</button>'
                + '<button type="button" class="btn-secondary btn-icon" title="Subir" aria-label="Subir" onclick="fdCatMove(this, -1)">' + FD_ICON_UP + '</button>'
                + '<button type="button" class="btn-secondary btn-icon" title="Bajar" aria-label="Bajar" onclick="fdCatMove(this, 1)">' + FD_ICON_DOWN + '</button>'
                + '<button type="button" class="btn-danger btn-icon" title="Eliminar" aria-label="Eliminar" onclick="fdCatRemove(this)">' + FD_ICON_TRASH + '</button>'
                + '</div>'
                + '</div>';
        });

        html += '</div>';

        return html;
    }

    function fdCatCreate() {
        var input = document.getElementById('fd-cat-new-name');
        var error = document.getElementById('fd-cat-new-error');
        var nombre = input.value.trim();

        if (nombre.length < 2) {
            error.textContent = 'Escribí el nombre de la categoría.';
            error.style.display = 'inline-block';
            return;
        }

        error.style.display = 'none';

        fdFetchJson('{{ url("/api/v1/provider/categories") }}', {
            method: 'POST',
            body: JSON.stringify({ name: nombre })
        })
            .then(function (res) {
                fdCheck(res, 'No se pudo crear la categoría.');
                fdToast('Categoría creada.', false);
                loadCategoriesPanel();
            })
            .catch(function (e) {
                error.textContent = e.message;
                error.style.display = 'inline-block';
            });
    }

    function fdCatRename(boton) {
        var fila = boton.closest('.fd-cat-row');
        var nombre = fila.querySelector('.fd-cat-name').value.trim();

        if (nombre.length < 2) {
            fdToast('El nombre de la categoría debe tener al menos 2 caracteres.', true);
            return;
        }

        fdFetchJson('{{ url("/api/v1/provider/categories") }}/' + encodeURIComponent(fila.getAttribute('data-cid')), {
            method: 'PUT',
            body: JSON.stringify({ name: nombre })
        })
            .then(function (res) {
                fdCheck(res, 'No se pudo renombrar la categoría.');
                fdToast('Categoría actualizada.', false);
                loadCategoriesPanel();
            })
            .catch(function (e) { fdToast(e.message, true); });
    }

    function fdCatRemove(boton) {
        var fila = boton.closest('.fd-cat-row');
        var nombre = fila.querySelector('.fd-cat-name').value;

        if (!window.confirm('¿Eliminar la categoría "' + nombre + '"?')) return;

        fdFetchJson('{{ url("/api/v1/provider/categories") }}/' + encodeURIComponent(fila.getAttribute('data-cid')), {
            method: 'DELETE'
        })
            .then(function (res) {
                fdCheck(res, 'No se pudo eliminar la categoría.');
                fdToast('Categoría eliminada.', false);
                loadCategoriesPanel();
            })
            .catch(function (e) { fdToast(e.message, true); });
    }

    function fdCatMove(boton, dir) {
        var filas = Array.prototype.slice.call(document.querySelectorAll('#fd-cats-content .fd-cat-row'));
        var fila = boton.closest('.fd-cat-row');
        var i = filas.indexOf(fila);
        var j = i + dir;

        if (i < 0 || j < 0 || j >= filas.length) return;

        var tmp = filas[i];
        filas[i] = filas[j];
        filas[j] = tmp;

        var tareas = filas.map(function (f, indice) {
            var catId = f.getAttribute('data-cid');
            return function () {
                return fdFetchJson('{{ url("/api/v1/provider/categories") }}/' + encodeURIComponent(catId), {
                    method: 'PUT',
                    body: JSON.stringify({ sort_order: (indice + 1) * 10 })
                }).then(function (res) { fdCheck(res, 'No se pudo reordenar.'); });
            };
        });

        fdSeq(tareas)
            .then(function () {
                fdToast('Orden actualizado.', false);
                loadCategoriesPanel();
            })
            .catch(function (e) { fdToast(e.message, true); });
    }

    // --- Panel: unidades de medida (consulta) ---

    function loadUnitsPanel() {
        fdPanelState(FD_CAT_PANELS.unidades, 'loading');

        fdFetchJson('{{ url("/api/v1/provider/units") }}')
            .then(function (res) {
                fdCheck(res, 'No se pudieron cargar las unidades.');
                document.getElementById('fd-units-content').innerHTML = fdRenderUnits(res.data);
                fdPanelState(FD_CAT_PANELS.unidades, 'content');
            })
            .catch(function (e) {
                fdPanelState(FD_CAT_PANELS.unidades, 'message', e.message || 'No se pudo conectar con el servidor.');
            });
    }

    function fdRenderUnits(data) {
        var units = data.units || [];

        var html = '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">'
            + fdBadge(units.length + ' unidades', '#0c2a4d', '#eef2f7')
            + fdBadge((data.products_with_unit || 0) + ' producto(s) con unidad', (data.products_with_unit || 0) ? '#047857' : '#64748b', (data.products_with_unit || 0) ? '#d1fae5' : '#f1f5f9')
            + '</div>'
            + '<div style="background:#fffbeb;border:1px solid #fde68a;padding:12px 16px;margin-bottom:14px;font-size:13px;color:#92400e;">'
            + 'Las unidades de medida son de toda la plataforma: se usan para controlar el stock de los productos. '
            + 'Si necesitás una que no aparece, escribinos y la agregamos.'
            + '</div>'
            + '<div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">'
            + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            + '<thead><tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Unidad</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Símbolo</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Equivalencia</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Cantidades</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Tus productos</th>'
            + '</tr></thead><tbody>';

        if (!units.length) {
            html += '<tr><td colspan="5" style="padding:24px 16px;color:#94a3b8;text-align:center;">No hay unidades de medida cargadas.</td></tr>';
        }

        units.forEach(function (u, i) {
            html += '<tr style="background:' + (i % 2 ? '#f8fafc' : '#fff') + ';border-top:1px solid #f1f5f9;">'
                + '<td style="padding:10px 12px;color:#0f172a;font-weight:600;">' + escapeHtml(u.name) + '</td>'
                + '<td style="padding:10px 12px;color:#6b7280;">' + escapeHtml(u.symbol) + '</td>'
                + '<td style="padding:10px 12px;color:#475569;">1 ' + escapeHtml(u.name) + ' = ' + fdInvNum(u.base_conversion_factor) + ' und</td>'
                + '<td style="padding:10px 12px;">' + (u.is_integer_only ? fdBadge('Solo enteras', '#9a3412', '#fff7ed') : fdBadge('Admite decimales', '#047857', '#d1fae5')) + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:' + (u.products_count ? '#0f172a' : '#94a3b8') + ';font-weight:600;">' + u.products_count + '</td>'
                + '</tr>';
        });

        html += '</tbody></table></div>';

        return html;
    }

    // --- Productos (alta y edición) ---

    var fdProdData = null;

    function fdProdLoad(callback) {
        if (fdProdData) {
            callback();
            return;
        }

        fdFetchJson('{{ url("/api/v1/provider/products") }}')
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudieron cargar los datos del catálogo.', true);
                    return;
                }
                fdProdData = res.data;
                callback();
            })
            .catch(function () {
                fdToast('No se pudo conectar con el servidor.', true);
            });
    }

    function fdProdOptions() {
        var out = { products: [], categories: [], units: [] };

        (fdProdData.products || []).forEach(function (p) { out.products[p.id] = p; });
        (fdProdData.categories || []).forEach(function (c) { out.categories.push(c); });
        (fdProdData.unit_of_measures || []).forEach(function (u) { out.units.push(u); });

        return out;
    }

    function fdProdCategories(selected) {
        var opts = fdProdOptions().categories;
        var html = '<option value="">Sin categoría</option>';

        opts.forEach(function (c) {
            html += '<option value="' + escapeHtml(String(c.id)) + '"'
                + (String(c.id) === String(selected) ? ' selected' : '') + '>'
                + escapeHtml(c.name) + '</option>';
        });

        return html;
    }

    function fdProdUnits(selected) {
        var opts = fdProdOptions().units;
        var html = '<option value="">Sin unidad</option>';

        opts.forEach(function (u) {
            html += '<option value="' + escapeHtml(String(u.id)) + '"'
                + (String(u.id) === String(selected) ? ' selected' : '') + '>'
                + escapeHtml(u.name) + ' (' + escapeHtml(u.symbol) + ')</option>';
        });

        return html;
    }

    function fdProdTrackChanged() {
        var fields = document.getElementById('fd-product-stock-fields');
        if (fields) fields.style.display = document.getElementById('fd-product-track').checked ? 'block' : 'none';
    }

    function fdProdNewCategoryError(mensaje) {
        var error = document.getElementById('fd-product-newcategory-error');
        error.textContent = mensaje;
        error.style.display = mensaje ? 'inline-block' : 'none';
    }

    function fdProdShowNewCategory() {
        document.getElementById('fd-product-newcategory-box').style.display = 'block';
        document.getElementById('fd-product-newcategory-btn').style.visibility = 'hidden';
        document.getElementById('fd-product-newcategory-input').value = '';
        fdProdNewCategoryError('');
        document.getElementById('fd-product-newcategory-input').focus();
    }

    function fdProdCancelNewCategory() {
        document.getElementById('fd-product-newcategory-box').style.display = 'none';
        document.getElementById('fd-product-newcategory-btn').style.visibility = 'visible';
        document.getElementById('fd-product-newcategory-input').value = '';
        fdProdNewCategoryError('');
    }

    // Crea la categoria y, si viene un callback, deja la recién creada seleccionada
    // en el select para que "Guardar" la use directamente.
    function fdProdCreateCategory(alTerminar) {
        var input = document.getElementById('fd-product-newcategory-input');
        var boton = document.getElementById('fd-product-newcategory-ok');
        var nombre = input.value.trim();

        if (nombre.length < 2) {
            fdProdNewCategoryError('Escribí el nombre de la categoría.');
            return;
        }

        if (boton.disabled) return;

        fdProdNewCategoryError('');
        boton.disabled = true;
        boton.textContent = 'Agregando...';

        fdFetchJson('{{ url("/api/v1/provider/categories") }}', {
            method: 'POST',
            body: JSON.stringify({ name: nombre })
        })
            .then(function (res) {
                boton.disabled = false;
                boton.textContent = 'Agregar';

                if (!res.ok) {
                    fdProdNewCategoryError((res.data && (res.data.message || Object.values(res.data.errors || {})[0])) || 'No se pudo crear la categoría.');
                    return;
                }

                var cat = res.data.category;
                if (fdProdData) {
                    fdProdData.categories = fdProdData.categories || [];
                    fdProdData.categories.push(cat);
                }

                document.getElementById('fd-product-category').innerHTML = fdProdCategories(cat.id);
                fdProdCancelNewCategory();

                if (typeof alTerminar === 'function') {
                    alTerminar(cat);
                } else {
                    fdToast('Categoría "' + cat.name + '" creada.', false);
                }
            })
            .catch(function () {
                boton.disabled = false;
                boton.textContent = 'Agregar';
                fdProdNewCategoryError('No se pudo conectar con el servidor.');
            });
    }

    function openProductModal(productId) {
        fdProdLoad(function () {
            var editing = productId !== null && typeof productId !== 'undefined';
            var p = editing ? fdProdOptions().products[productId] : null;

            document.getElementById('fd-product-id').value = editing ? productId : '';
            document.getElementById('fd-product-title').textContent = editing ? 'Editar producto' : 'Nuevo producto';
            document.getElementById('fd-product-name').value = p ? p.name : '';
            document.getElementById('fd-product-category').innerHTML = fdProdCategories(p ? p.category_id : '');
            document.getElementById('fd-product-price').value = p ? p.price : '';
            document.getElementById('fd-product-description').value = p && p.description ? p.description : '';
            document.getElementById('fd-product-available').checked = !p || p.is_available !== false;
            document.getElementById('fd-product-track').checked = !!p && !!p.track_stock;
            document.getElementById('fd-product-unit').innerHTML = fdProdUnits(p ? p.unit_of_measure_id : '');
            document.getElementById('fd-product-initial').value = '';
            document.getElementById('fd-product-min').value = p && p.min_stock_alert !== null ? p.min_stock_alert : '';
            document.getElementById('fd-product-negative').checked = !!p && p.inventory ? !!p.inventory.allow_negative_stock : false;

            var error = document.getElementById('fd-product-error');
            error.style.display = 'none';
            error.textContent = '';

            fdProdCancelNewCategory();
            fdProdTrackChanged();
            document.getElementById('fd-product-overlay').style.display = 'flex';
        });
    }

    function closeProductModal() {
        document.getElementById('fd-product-overlay').style.display = 'none';
    }

    function saveProduct() {
        var id = document.getElementById('fd-product-id').value;
        var error = document.getElementById('fd-product-error');
        var track = document.getElementById('fd-product-track').checked;

        var nombre = document.getElementById('fd-product-name').value.trim();
        var categoria = document.getElementById('fd-product-category').value;
        var precio = document.getElementById('fd-product-price').value;

        // Si quedó una categoría tipeada y sin dar de alta, primero se crea (queda
        // seleccionada y se limpia el input) y recién después se guarda el producto.
        if (document.getElementById('fd-product-newcategory-input').value.trim()) {
            fdProdCreateCategory(function () { saveProduct(); });
            return;
        }

        if (!nombre || !categoria || precio === '') {
            error.textContent = 'Completá nombre, categoría y precio.';
            error.style.display = 'inline';
            return;
        }

        var cuerpo = {
            name: nombre,
            category_id: Number(categoria),
            price: Number(precio),
            description: document.getElementById('fd-product-description').value.trim() || null,
            is_available: document.getElementById('fd-product-available').checked,
            track_stock: track
        };

        if (track) {
            var inicial = document.getElementById('fd-product-initial').value;
            var minimo = document.getElementById('fd-product-min').value;
            var unidad = document.getElementById('fd-product-unit').value;

            if (id === '') {
                cuerpo.initial_stock = inicial === '' ? 0 : Number(inicial);
            } else {
                cuerpo.initial_stock = 0;
            }

            cuerpo.min_stock_alert = minimo === '' ? null : Number(minimo);
            cuerpo.unit_of_measure_id = unidad === '' ? null : Number(unidad);
            cuerpo.allow_negative_stock = document.getElementById('fd-product-negative').checked;
        }

        error.style.display = 'none';

        var method = id ? 'PUT' : 'POST';
        var url = '{{ url("/api/v1/provider/products") }}' + (id ? '/' + encodeURIComponent(id) : '');

        fdFetchJson(url, { method: method, body: JSON.stringify(cuerpo) })
            .then(function (res) {
                if (!res.ok) {
                    var mensaje = (res.data && (res.data.message || Object.values(res.data.errors || {})[0])) || 'No se pudo guardar el producto.';
                    error.textContent = mensaje;
                    error.style.display = 'inline';
                    return;
                }
                closeProductModal();
                fdToast(id ? 'Producto actualizado.' : 'Producto creado.', false);
                fdCatReload();
            })
            .catch(function () {
                error.textContent = 'No se pudo conectar con el servidor.';
                error.style.display = 'inline';
            });
    }

    // --- Variantes (Individual / Grande / Familiar) ---

    var fdVariantProducto = null;
    var fdVariantOriginales = [];

    function fdUrlVariants(productId) {
        return '{{ url("/api/v1/provider/products") }}/' + encodeURIComponent(productId) + '/variants';
    }

    function fdUrlVariant(id) {
        return '{{ url("/api/v1/provider/variants") }}/' + encodeURIComponent(id);
    }

    function fdVariantRow(v) {
        v = v || {};
        var vid = (v.id !== undefined && v.id !== null) ? String(v.id) : '';
        var precio = (v.price !== undefined && v.price !== null && v.price !== '') ? Number(v.price) : '';

        return '<div class="fd-variant-row" data-vid="' + vid + '" style="display:flex;gap:8px;align-items:flex-end;padding:8px 0;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;">'
            + '<div style="flex:2;min-width:150px;"><label class="field-label">Nombre</label>'
            + '<input type="text" class="field-input fd-v-name" maxlength="60" value="' + escapeHtml(v.name || '') + '" placeholder="Ej: Individual" /></div>'
            + '<div style="flex:1;min-width:110px;"><label class="field-label">Precio</label>'
            + '<input type="number" step="0.01" min="0" class="field-input fd-v-price" value="' + precio + '" placeholder="0.00" /></div>'
            + '<label style="display:flex;gap:6px;align-items:center;font-size:12px;color:#475569;padding-bottom:10px;cursor:pointer;white-space:nowrap;">'
            + '<input type="checkbox" class="fd-v-avail"' + (v.is_available === false ? '' : ' checked') + ' /> Disponible</label>'
            + '<button type="button" class="icon-btn del" title="Quitar variante" onclick="fdVariantRemove(this)" style="margin-bottom:2px;">&#10005;</button>'
            + '</div>';
    }

    function fdVariantRemove(boton) {
        var fila = boton.closest('.fd-variant-row');
        if (fila && fila.parentNode) fila.parentNode.removeChild(fila);
    }

    function fdVariantAddRow() {
        document.getElementById('fd-variant-rows').insertAdjacentHTML('beforeend', fdVariantRow(null));
    }

    function openVariantModal(productId) {
        fdProdLoad(function () {
            var producto = fdProdOptions().products[productId];
            if (!producto) return;

            fdVariantProducto = producto;
            fdVariantOriginales = (producto.variants || []).map(function (v) { return String(v.id); });

            document.getElementById('fd-variant-product').textContent =
                producto.name + ' · precio base ' + fdMoney(producto.price);
            document.getElementById('fd-variant-rows').innerHTML =
                (producto.variants || []).map(function (v) { return fdVariantRow(v); }).join('');

            var error = document.getElementById('fd-variant-error');
            error.textContent = '';
            error.style.display = 'none';

            document.getElementById('fd-variant-overlay').style.display = 'flex';
        });
    }

    function closeVariantModal() {
        document.getElementById('fd-variant-overlay').style.display = 'none';
    }

    function saveVariants() {
        if (!fdVariantProducto) return;

        var error = document.getElementById('fd-variant-error');
        error.style.display = 'none';

        var filas = Array.prototype.slice.call(document.querySelectorAll('#fd-variant-rows .fd-variant-row'));
        var datos = [];

        for (var i = 0; i < filas.length; i++) {
            var f = filas[i];
            var nombre = f.querySelector('.fd-v-name').value.trim();
            var precio = f.querySelector('.fd-v-price').value;

            if (!nombre || precio === '') {
                error.textContent = 'Completá nombre y precio en cada variante.';
                error.style.display = 'inline';
                return;
            }

            datos.push({
                id: f.getAttribute('data-vid'),
                body: JSON.stringify({
                    name: nombre,
                    price: Number(precio),
                    is_available: f.querySelector('.fd-v-avail').checked
                })
            });
        }

        var vivos = datos.filter(function (d) { return d.id; }).map(function (d) { return d.id; });
        var tareas = [];

        fdVariantOriginales.forEach(function (id) {
            if (vivos.indexOf(id) === -1) {
                tareas.push(function () {
                    return fdFetchJson(fdUrlVariant(id), { method: 'DELETE' })
                        .then(function (res) { fdCheck(res, 'No se pudo eliminar la variante.'); });
                });
            }
        });

        datos.forEach(function (d) {
            if (d.id) {
                tareas.push(function () {
                    return fdFetchJson(fdUrlVariant(d.id), { method: 'PUT', body: d.body })
                        .then(function (res) { fdCheck(res, 'No se pudo guardar la variante.'); });
                });
            } else {
                tareas.push(function () {
                    return fdFetchJson(fdUrlVariants(fdVariantProducto.id), { method: 'POST', body: d.body })
                        .then(function (res) { fdCheck(res, 'No se pudo crear la variante.'); });
                });
            }
        });

        fdSeq(tareas)
            .then(function () {
                closeVariantModal();
                fdToast('Variantes guardadas.', false);
                fdCatReload();
            })
            .catch(function (e) {
                error.textContent = e.message;
                error.style.display = 'inline';
            });
    }

    // --- Agregados (grupos de opciones y opciones) ---

    var fdOptProducto = null;
    var fdOptGruposOriginales = [];
    var fdOptOpcionesOriginales = [];

    function fdUrlGroups(productId) {
        return '{{ url("/api/v1/provider/products") }}/' + encodeURIComponent(productId) + '/option-groups';
    }

    function fdUrlGroup(id) {
        return '{{ url("/api/v1/provider/option-groups") }}/' + encodeURIComponent(id);
    }

    function fdUrlOptions(groupId) {
        return '{{ url("/api/v1/provider/option-groups") }}/' + encodeURIComponent(groupId) + '/options';
    }

    function fdUrlOption(id) {
        return '{{ url("/api/v1/provider/options") }}/' + encodeURIComponent(id);
    }

    function fdOptOptionRow(o) {
        o = o || {};
        var oid = (o.id !== undefined && o.id !== null) ? String(o.id) : '';
        var extra = (o.extra_price !== undefined && o.extra_price !== null && o.extra_price !== '') ? Number(o.extra_price) : 0;

        return '<div class="fd-opt-option" data-oid="' + oid + '" style="display:flex;gap:8px;align-items:flex-end;padding:8px 0;border-top:1px solid #f1f5f9;flex-wrap:wrap;">'
            + '<div style="flex:2;min-width:150px;"><label class="field-label">Opción</label>'
            + '<input type="text" class="field-input fd-o-name" maxlength="60" value="' + escapeHtml(o.name || '') + '" placeholder="Ej: Muzzarella extra" /></div>'
            + '<div style="flex:1;min-width:110px;"><label class="field-label">Precio extra</label>'
            + '<input type="number" step="0.01" min="0" class="field-input fd-o-price" value="' + extra + '" placeholder="0.00" /></div>'
            + '<label style="display:flex;gap:6px;align-items:center;font-size:12px;color:#475569;padding-bottom:10px;cursor:pointer;white-space:nowrap;">'
            + '<input type="checkbox" class="fd-o-avail"' + (o.is_available === false ? '' : ' checked') + ' /> Disponible</label>'
            + '<button type="button" class="icon-btn del" title="Quitar opción" onclick="fdOptRemoveOption(this)" style="margin-bottom:2px;">&#10005;</button>'
            + '</div>';
    }

    function fdOptGroupCard(g) {
        g = g || {};
        var gid = (g.id !== undefined && g.id !== null) ? String(g.id) : '';
        var min = (g.min_choices !== undefined && g.min_choices !== null) ? g.min_choices : 0;
        var max = (g.max_choices !== undefined && g.max_choices !== null) ? g.max_choices : 3;

        return '<div class="fd-opt-group" data-gid="' + gid + '" style="border:1px solid #e5e7eb;padding:12px 14px;margin-bottom:12px;background:#fff;">'
            + '<div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">'
            + '<div style="flex:2;min-width:160px;"><label class="field-label">Grupo</label>'
            + '<input type="text" class="field-input fd-g-name" maxlength="60" value="' + escapeHtml(g.name || '') + '" placeholder="Ej: Agregados" /></div>'
            + '<div style="width:86px;"><label class="field-label">Mín.</label>'
            + '<input type="number" min="0" max="20" class="field-input fd-g-min" value="' + min + '" /></div>'
            + '<div style="width:86px;"><label class="field-label">Máx.</label>'
            + '<input type="number" min="1" max="20" class="field-input fd-g-max" value="' + max + '" /></div>'
            + '<label style="display:flex;gap:6px;align-items:center;font-size:12px;color:#475569;padding-bottom:10px;cursor:pointer;white-space:nowrap;">'
            + '<input type="checkbox" class="fd-g-req"' + (g.is_required ? ' checked' : '') + ' /> Obligatorio</label>'
            + '<button type="button" class="btn-danger" onclick="fdOptRemoveGroup(this)">Quitar grupo</button>'
            + '</div>'
            + '<div class="fd-opt-options" style="margin-top:6px;">'
            + (g.options || []).map(function (o) { return fdOptOptionRow(o); }).join('')
            + '</div>'
            + '<button type="button" class="btn-secondary" style="margin-top:8px;" onclick="fdOptAddOption(this)">+ Agregar opción</button>'
            + '</div>';
    }

    function fdOptAddGroup() {
        document.getElementById('fd-opt-groups').insertAdjacentHTML('beforeend', fdOptGroupCard(null));
    }

    function fdOptAddOption(boton) {
        boton.closest('.fd-opt-group').querySelector('.fd-opt-options')
            .insertAdjacentHTML('beforeend', fdOptOptionRow(null));
    }

    function fdOptRemoveOption(boton) {
        var fila = boton.closest('.fd-opt-option');
        if (fila && fila.parentNode) fila.parentNode.removeChild(fila);
    }

    function fdOptRemoveGroup(boton) {
        var card = boton.closest('.fd-opt-group');
        if (card && card.parentNode) card.parentNode.removeChild(card);
    }

    function openOptionModal(productId) {
        fdProdLoad(function () {
            var producto = fdProdOptions().products[productId];
            if (!producto) return;

            fdOptProducto = producto;
            fdOptGruposOriginales = [];
            fdOptOpcionesOriginales = [];

            (producto.option_groups || []).forEach(function (g) {
                fdOptGruposOriginales.push(String(g.id));
                (g.options || []).forEach(function (o) { fdOptOpcionesOriginales.push(String(o.id)); });
            });

            document.getElementById('fd-opt-product').textContent = producto.name;
            document.getElementById('fd-opt-groups').innerHTML =
                (producto.option_groups || []).map(function (g) { return fdOptGroupCard(g); }).join('');

            var error = document.getElementById('fd-opt-error');
            error.textContent = '';
            error.style.display = 'none';

            document.getElementById('fd-opt-overlay').style.display = 'flex';
        });
    }

    function closeOptionModal() {
        document.getElementById('fd-opt-overlay').style.display = 'none';
    }

    function saveOptions() {
        if (!fdOptProducto) return;

        var error = document.getElementById('fd-opt-error');
        error.style.display = 'none';

        var cards = Array.prototype.slice.call(document.querySelectorAll('#fd-opt-groups .fd-opt-group'));
        var grupos = [];

        for (var i = 0; i < cards.length; i++) {
            var card = cards[i];
            var nombreGrupo = card.querySelector('.fd-g-name').value.trim();
            var min = Number(card.querySelector('.fd-g-min').value || 0);
            var max = Number(card.querySelector('.fd-g-max').value || 0);
            var opciones = Array.prototype.slice.call(card.querySelectorAll('.fd-opt-option'));
            var datosOpciones = [];

            if (!nombreGrupo) {
                error.textContent = 'Cada grupo necesita un nombre.';
                error.style.display = 'inline';
                return;
            }

            if (max < 1) {
                error.textContent = 'En "' + nombreGrupo + '" el máximo de elecciones debe ser al menos 1.';
                error.style.display = 'inline';
                return;
            }

            if (min > max) {
                error.textContent = 'En "' + nombreGrupo + '" el mínimo no puede superar al máximo.';
                error.style.display = 'inline';
                return;
            }

            for (var j = 0; j < opciones.length; j++) {
                var fila = opciones[j];
                var nombreOp = fila.querySelector('.fd-o-name').value.trim();
                var extra = fila.querySelector('.fd-o-price').value;

                if (!nombreOp || extra === '') {
                    error.textContent = 'Completá nombre y precio extra en cada opción.';
                    error.style.display = 'inline';
                    return;
                }

                datosOpciones.push({
                    id: fila.getAttribute('data-oid'),
                    body: JSON.stringify({
                        name: nombreOp,
                        extra_price: Number(extra),
                        is_available: fila.querySelector('.fd-o-avail').checked
                    })
                });
            }

            grupos.push({
                el: card,
                gid: card.getAttribute('data-gid'),
                body: JSON.stringify({
                    name: nombreGrupo,
                    min_choices: min,
                    max_choices: max,
                    is_required: card.querySelector('.fd-g-req').checked
                }),
                opciones: datosOpciones
            });
        }

        var gruposVivos = grupos.filter(function (g) { return g.gid; }).map(function (g) { return g.gid; });
        var opcionesVivas = [];

        grupos.forEach(function (g) {
            g.opciones.forEach(function (o) { if (o.id) opcionesVivas.push(o.id); });
        });

        var tareas = [];

        // Bajas: primero las opciones sueltas, después los grupos (cascada).
        fdOptOpcionesOriginales.forEach(function (id) {
            if (opcionesVivas.indexOf(id) === -1) {
                tareas.push(function () {
                    return fdFetchJson(fdUrlOption(id), { method: 'DELETE' })
                        .then(function (res) { fdCheck(res, 'No se pudo eliminar la opción.'); });
                });
            }
        });

        fdOptGruposOriginales.forEach(function (id) {
            if (gruposVivos.indexOf(id) === -1) {
                tareas.push(function () {
                    return fdFetchJson(fdUrlGroup(id), { method: 'DELETE' })
                        .then(function (res) { fdCheck(res, 'No se pudo eliminar el grupo.'); });
                });
            }
        });

        // Altas y ediciones: cada grupo se guarda antes que sus opciones, así
        // una variante nueva ya tiene su id al cargar las opciones.
        grupos.forEach(function (g) {
            tareas.push(function () {
                if (g.gid) {
                    return fdFetchJson(fdUrlGroup(g.gid), { method: 'PUT', body: g.body })
                        .then(function (res) { fdCheck(res, 'No se pudo guardar el grupo.'); });
                }

                return fdFetchJson(fdUrlGroups(fdOptProducto.id), { method: 'POST', body: g.body })
                    .then(function (res) {
                        fdCheck(res, 'No se pudo crear el grupo.');
                        g.gid = String(res.data.group.id);
                        g.el.setAttribute('data-gid', g.gid);
                    });
            });

            g.opciones.forEach(function (o) {
                tareas.push(function () {
                    if (!g.gid) {
                        throw new Error('No se pudo guardar la opción porque falta el grupo.');
                    }

                    if (o.id) {
                        return fdFetchJson(fdUrlOption(o.id), { method: 'PUT', body: o.body })
                            .then(function (res) { fdCheck(res, 'No se pudo guardar la opción.'); });
                    }

                    return fdFetchJson(fdUrlOptions(g.gid), { method: 'POST', body: o.body })
                        .then(function (res) { fdCheck(res, 'No se pudo crear la opción.'); });
                });
            });
        });

        fdSeq(tareas)
            .then(function () {
                closeOptionModal();
                fdToast('Agregados guardados.', false);
                fdCatReload();
            })
            .catch(function (e) {
                error.textContent = e.message;
                error.style.display = 'inline';
            });
    }

    // --- Inventario ---

    function fdSetInventoryState(state, text) {
        var map = { loading: 'fd-inventory-loading', message: 'fd-inventory-message', content: 'fd-inventory-content' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-inventory-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function loadInventory() {
        fdSetInventoryState('loading');

        fdFetchJson('{{ url("/api/v1/provider/inventory") }}')
            .then(function (res) {
                if (!res.ok) {
                    fdSetInventoryState('message', (res.data && res.data.message) || 'No se pudo cargar el inventario.');
                    return;
                }

                var provider = res.data.provider || {};
                var enabled = !!provider.has_inventory_control;
                var toggle = document.getElementById('fd-inventory-enabled');
                if (toggle) toggle.checked = enabled;

                var summary = document.getElementById('fd-inventory-summary');
                if (summary) {
                    summary.textContent = (provider.business_name || 'Tu comercio')
                        + (enabled ? ' · inventario activo' : ' · inventario desactivado')
                        + (res.data.low_stock_count ? ' · ' + res.data.low_stock_count + ' con stock bajo' : '');
                }

                var products = res.data.products || [];
                fdInventoryData = res.data;
                if (!products.length) {
                    fdSetInventoryState('message', 'Todavía no hay productos en tu catálogo.\nCargá productos desde Mi Catálogo para empezar a controlar el stock.');
                    return;
                }

                document.getElementById('fd-inventory-content').innerHTML = fdRenderInventory(res.data);
                fdSetInventoryState('content');
            })
            .catch(function () {
                fdSetInventoryState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function toggleInventoryControl() {
        var toggle = document.getElementById('fd-inventory-enabled');
        if (!toggle) return;

        fdFetchJson('{{ url("/api/v1/provider/inventory/settings") }}', {
            method: 'PUT',
            body: JSON.stringify({ has_inventory_control: toggle.checked })
        })
        .then(function (res) {
            if (!res.ok) {
                toggle.checked = !toggle.checked;
                fdToast('No se pudo actualizar la configuración de inventario.', true);
                return;
            }
            fdToast('Configuración de inventario actualizada.', false);
            loadInventory();
        })
        .catch(function () {
            toggle.checked = !toggle.checked;
            fdToast('No se pudo conectar con el servidor.', true);
        });
    }

    function fdInvNum(value) {
        if (value === null || typeof value === 'undefined') return '—';
        var n = Number(value);
        if (isNaN(n)) return escapeHtml(String(value));
        return String(Math.round(n * 1000) / 1000);
    }

    var fdInventoryData = null;
    var fdInvCatFilter = '';

    function fdInvUnitOptions(selectedId) {
        var units = (fdInventoryData && fdInventoryData.unit_of_measures) || [];
        var html = '<option value="">Sin unidad</option>';

        units.forEach(function (u) {
            html += '<option value="' + escapeHtml(String(u.id)) + '"'
                + (String(u.id) === String(selectedId) ? ' selected' : '')
                + (u.is_integer_only ? ' data-integer="1"' : '') + '>'
                + escapeHtml(u.name) + ' (' + escapeHtml(u.symbol) + ')</option>';
        });

        return html;
    }

    function fdInvProduct(productId) {
        var products = (fdInventoryData && fdInventoryData.products) || [];

        for (var i = 0; i < products.length; i++) {
            if (String(products[i].product_id) === String(productId)) return products[i];
        }

        return null;
    }

    function fdInvStockTrackChanged() {
        var fields = document.getElementById('fd-inventory-stock-fields');
        if (fields) fields.style.display = document.getElementById('fd-inventory-stock-track').checked ? 'block' : 'none';
    }

    // Interruptor rapido de la fila: activa o desactiva el seguimiento sin
    // entrar al modal, conservando la cantidad y el minimo ya cargados.
    function fdInvToggleTrack(productId, checkbox) {
        var producto = fdInvProduct(productId);
        if (!producto) return;

        var cuerpo = { track_stock: checkbox.checked };

        if (checkbox.checked) {
            cuerpo.current_stock = Number(producto.current_stock) || 0;
            if (producto.min_stock_alert !== null && typeof producto.min_stock_alert !== 'undefined') {
                cuerpo.min_stock_alert = producto.min_stock_alert;
            }
            if (producto.unit_of_measure) cuerpo.unit_of_measure_id = producto.unit_of_measure.id;
        }

        checkbox.disabled = true;

        fdFetchJson('{{ url("/api/v1/provider/inventory/products") }}/' + encodeURIComponent(productId), {
            method: 'PUT',
            body: JSON.stringify(cuerpo)
        })
            .then(function (res) {
                checkbox.disabled = false;
                if (!res.ok) {
                    checkbox.checked = !checkbox.checked;
                    fdToast((res.data && res.data.message) || 'No se pudo cambiar el seguimiento del stock.', true);
                    return;
                }
                fdToast(checkbox.checked ? 'Seguimiento de stock activado.' : 'Seguimiento de stock desactivado.', false);
                loadInventory();
            })
            .catch(function () {
                checkbox.disabled = false;
                checkbox.checked = !checkbox.checked;
                fdToast('No se pudo conectar con el servidor.', true);
            });
    }

    function openInventoryStockModal(productId) {
        var producto = fdInvProduct(productId);
        if (!producto) return;

        document.getElementById('fd-inventory-stock-id').value = producto.product_id;
        document.getElementById('fd-inventory-stock-name').textContent = producto.name;
        document.getElementById('fd-inventory-stock-track').checked = !!producto.track_stock;
        document.getElementById('fd-inventory-stock-current').value = fdInvNum(producto.current_stock) === '—' ? '' : fdInvNum(producto.current_stock);
        document.getElementById('fd-inventory-stock-min').value = (producto.min_stock_alert === null || typeof producto.min_stock_alert === 'undefined') ? '' : fdInvNum(producto.min_stock_alert);
        document.getElementById('fd-inventory-stock-unit').innerHTML = fdInvUnitOptions(producto.unit_of_measure ? producto.unit_of_measure.id : '');
        document.getElementById('fd-inventory-stock-negative').checked = !!producto.allow_negative_stock;
        document.getElementById('fd-inventory-stock-notes').value = '';

        var error = document.getElementById('fd-inventory-stock-error');
        error.style.display = 'none';
        error.textContent = '';

        fdInvStockTrackChanged();
        document.getElementById('fd-inventory-stock-overlay').style.display = 'flex';
    }

    function closeInventoryStockModal() {
        document.getElementById('fd-inventory-stock-overlay').style.display = 'none';
    }

    function saveInventoryStock() {
        var productId = document.getElementById('fd-inventory-stock-id').value;
        var track = document.getElementById('fd-inventory-stock-track').checked;
        var error = document.getElementById('fd-inventory-stock-error');

        var cuerpo = { track_stock: track };

        if (track) {
            var actual = document.getElementById('fd-inventory-stock-current').value;
            var minimo = document.getElementById('fd-inventory-stock-min').value;
            var unidad = document.getElementById('fd-inventory-stock-unit').value;

            if (actual === '') {
                error.textContent = 'Indicá el stock actual.';
                error.style.display = 'inline';
                return;
            }

            cuerpo.current_stock = Number(actual);
            cuerpo.allow_negative_stock = document.getElementById('fd-inventory-stock-negative').checked;
            cuerpo.min_stock_alert = minimo === '' ? null : Number(minimo);
            cuerpo.unit_of_measure_id = unidad === '' ? null : Number(unidad);

            var notas = document.getElementById('fd-inventory-stock-notes').value;
            if (notas) cuerpo.notes = notas;
        }

        error.style.display = 'none';

        fdFetchJson('{{ url("/api/v1/provider/inventory/products") }}/' + encodeURIComponent(productId), {
            method: 'PUT',
            body: JSON.stringify(cuerpo)
        })
            .then(function (res) {
                if (!res.ok) {
                    var mensaje = (res.data && (res.data.message || Object.values(res.data.errors || {})[0])) || 'No se pudo guardar el stock.';
                    error.textContent = mensaje;
                    error.style.display = 'inline';
                    return;
                }
                closeInventoryStockModal();
                fdToast('Stock actualizado.', false);
                loadInventory();
            })
            .catch(function () {
                error.textContent = 'No se pudo conectar con el servidor.';
                error.style.display = 'inline';
            });
    }

    function fdInvFilterChange(value) {
        fdInvCatFilter = value || '';
        if (!fdInventoryData) return;
        var content = document.getElementById('fd-inventory-content');
        if (content) content.innerHTML = fdRenderInventory(fdInventoryData);
    }

    function fdRenderInventory(data) {
        var all = data.products || [];
        var cats = data.categories || [];
        var enabled = !!(data.provider && data.provider.has_inventory_control);

        var selected = String(fdInvCatFilter || '');
        if (selected && !cats.some(function (c) { return String(c.id) === selected; })) {
            selected = '';
            fdInvCatFilter = '';
        }

        var products = selected
            ? all.filter(function (p) { return String(p.category_id) === selected; })
            : all;

        var lowCount = products.filter(function (p) { return p.low_stock; }).length;

        var combo = '<div style="margin-left:auto;display:flex;align-items:center;gap:6px;">'
            + '<label for="fd-inv-cat-filter" style="font-size:12px;color:#6b7280;font-weight:600;">Categoría</label>'
            + '<select id="fd-inv-cat-filter" onchange="fdInvFilterChange(this.value)" style="font-size:13px;padding:7px 10px;border:1px solid #cbd5e1;border-radius:0;background:#fff;color:#0f172a;cursor:pointer;">'
            + '<option value="">Todas las categorías</option>'
            + cats.map(function (c) {
                return '<option value="' + escapeHtml(String(c.id)) + '"' + (String(c.id) === selected ? ' selected' : '') + '>' + escapeHtml(c.name) + '</option>';
            }).join('')
            + '</select>'
            + '</div>';

        var html = '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">'
            + fdBadge(products.length + ' productos', '#0c2a4d', '#eef2f7')
            + fdBadge(cats.length + ' categorías', '#0c2a4d', '#eef2f7')
            + fdBadge(lowCount + ' con stock bajo', lowCount ? '#b91c1c' : '#047857', lowCount ? '#fee2e2' : '#d1fae5')
            + fdBadge(enabled ? 'Control activo' : 'Control desactivado', enabled ? '#047857' : '#9a3412', enabled ? '#d1fae5' : '#fff7ed')
            + combo
            + '</div>';

        if (!enabled) {
            html += '<div style="background:#fffbeb;border:1px solid #fde68a;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#92400e;">'
                + 'El control de inventario está desactivado: las ventas no descuentan stock y los ajustes no se aplican. '
                + 'Activalo con el interruptor de arriba.'
                + '</div>';
        }

        if (!products.length) {
            return html + '<div style="background:#fff;border:1px solid #e5e7eb;padding:32px 20px;text-align:center;">'
                + '<p style="font-size:13px;color:#6b7280;margin:0;">No hay productos en esta categoría.</p>'
                + '</div>';
        }

        html += '<div style="background:#fff;border:1px solid #e5e7eb;overflow-x:auto;">'
            + '<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            + '<thead><tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Producto</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Unidad</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Stock actual</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Reservado</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Disponible</th>'
            + '<th style="text-align:right;padding:10px 12px;color:#fff;font-weight:600;">Mínimo</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Estado</th>'
            + '<th style="text-align:left;padding:10px 12px;color:#fff;font-weight:600;">Acciones</th>'
            + '</tr></thead><tbody>';

        products.forEach(function (p, i) {
            var unit = p.unit_of_measure
                ? escapeHtml(p.unit_of_measure.name) + ' (' + escapeHtml(p.unit_of_measure.symbol) + ')'
                : '—';

            var status;
            if (!p.track_stock) status = fdBadge('S/S', '#64748b', '#f1f5f9');
            else if (p.low_stock) status = fdBadge('Stock bajo', '#b91c1c', '#fee2e2');
            else if (Number(p.available_stock) <= 0) status = fdBadge('Sin stock', '#b91c1c', '#fee2e2');
            else status = fdBadge('OK', '#047857', '#d1fae5');

            html += '<tr style="background:' + (i % 2 ? '#f8fafc' : '#fff') + ';border-top:1px solid #f1f5f9;">'
                + '<td style="padding:10px 12px;color:#0f172a;font-weight:600;">' + escapeHtml(p.name) + '</td>'
                + '<td style="padding:10px 12px;color:#6b7280;">' + unit + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:#0f172a;font-weight:600;">' + fdInvNum(p.current_stock) + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:#6b7280;">' + fdInvNum(p.reserved_stock) + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:#0f172a;">' + fdInvNum(p.available_stock) + '</td>'
                + '<td style="padding:10px 12px;text-align:right;color:#6b7280;">'
                + (typeof p.min_stock_alert !== 'undefined' && p.min_stock_alert !== null ? fdInvNum(p.min_stock_alert) : '—')
                + '</td>'
                + '<td style="padding:10px 12px;">' + status + '</td>'
                + '<td style="padding:10px 12px;white-space:nowrap;text-align:left;">' + fdInvRowActions(p) + '</td>'
                + '</tr>';
        });

        html += '</tbody></table></div>';

        return html;
    }

    function fdInvRowActions(p) {
        var id = escapeHtml(String(p.product_id));
        var toggle = '<label style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#475569;cursor:pointer;white-space:nowrap;" title="Cambiar seguimiento de stock">'
            + '<input type="checkbox" ' + (p.track_stock ? 'checked' : '')
            + ' onchange="fdInvToggleTrack(\'' + id + '\', this)" style="cursor:pointer;">'
            + '<span>' + (p.track_stock ? 'C/S' : 'S/S') + '</span>'
            + '</label>';
        var edit = '<button type="button" title="Editar" aria-label="Editar" onclick="openInventoryStockModal(\'' + id + '\')" style="margin-left:10px;background:#fff;color:#D24C19;border:1px solid #D24C19;padding:5px 7px;border-radius:3px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">' + FD_ICON_EDIT + '</button>';

        return toggle + edit;
    }

    var fdPrevCatFilter = '';
    var fdPrevData = null;

    function fdPrevFilterChange(value) {
        fdPrevCatFilter = value || '';
        if (!fdPrevData) return;
        var content = document.getElementById('fd-catalog-content');
        if (content) content.innerHTML = fdRenderCatalog(fdPrevData.categories, fdPrevData.provider);
    }

    function fdRenderCatalog(categories, provider) {
        var selected = String(fdPrevCatFilter || '');
        if (selected && !categories.some(function (c) { return String(c.id) === selected; })) {
            selected = '';
            fdPrevCatFilter = '';
        }

        var shown = selected
            ? categories.filter(function (c) { return String(c.id) === selected; })
            : categories;

        var shownProducts = 0;
        shown.forEach(function (c) { shownProducts += (c.products || []).length; });

        var combo = '<div style="margin-left:auto;display:flex;align-items:center;gap:6px;">'
            + '<label for="fd-prev-cat-filter" style="font-size:12px;color:#6b7280;font-weight:600;">Categoría</label>'
            + '<select id="fd-prev-cat-filter" onchange="fdPrevFilterChange(this.value)" style="font-size:13px;padding:7px 10px;border:1px solid #cbd5e1;border-radius:0;background:#fff;color:#0f172a;cursor:pointer;">'
            + '<option value="">Todas las categorías</option>'
            + categories.map(function (c) {
                return '<option value="' + escapeHtml(String(c.id)) + '"' + (String(c.id) === selected ? ' selected' : '') + '>' + escapeHtml(c.name) + '</option>';
            }).join('')
            + '</select>'
            + '</div>';

        var html = '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:16px;">'
            + fdBadge(shown.length + (shown.length === 1 ? ' categoría' : ' categorías'), '#0c2a4d', '#eef2f7')
            + fdBadge(shownProducts + (shownProducts === 1 ? ' producto' : ' productos'), '#0c2a4d', '#eef2f7')
            + (provider && provider.zone ? fdBadge(provider.zone, '#9a3412', '#fff7ed') : '')
            + (provider && provider.is_active === false ? fdBadge('Comercio pausado', '#b91c1c', '#fee2e2') : '')
            + combo
            + '</div>';

        shown.forEach(function (cat) {
            var products = cat.products || [];

            var stripTitle = selected ? 'Filtrando: ' + escapeHtml(cat.name) : escapeHtml(cat.name);
            var stripCount = products.length + (products.length === 1 ? ' producto' : ' productos');

            html += '<div style="background:#fff;border:1px solid #e5e7eb;margin-bottom:16px;overflow:hidden;">'
                + '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 16px;background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">'
                + '<span style="font-size:13px;font-weight:700;color:#fff;">' + stripTitle + '</span>'
                + '<span style="font-size:11px;color:rgba(255,255,255,.75);">' + stripCount + '</span>'
                + '</div>';

            if (!products.length) {
                html += '<div style="padding:20px 16px;font-size:13px;color:#94a3b8;text-align:center;">Esta categoría todavía no tiene productos.</div>';
            } else {
                products.forEach(function (p) { html += fdRenderProduct(p); });
            }

            html += '</div>';
        });

        return html;
    }

    function fdRenderProduct(p) {
        var available = p.is_available !== false;
        var html = '<div style="padding:14px 16px;border-top:1px solid #f1f5f9;">'
            + '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">'
            + '<div style="min-width:0;">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
            + '<span style="font-size:14px;font-weight:600;color:#0f172a;">' + escapeHtml(p.name) + '</span>'
            + (available ? fdBadge('Disponible', '#047857', '#d1fae5') : fdBadge('No disponible', '#b91c1c', '#fee2e2'))
            + '</div>';

        if (p.description) {
            html += '<p style="font-size:12px;color:#64748b;margin:4px 0 0;">' + escapeHtml(p.description) + '</p>';
        }

        if (p.track_stock) {
            var stock = p.inventory ? Number(p.inventory.current_stock || 0) : 0;
            var minimo = (typeof p.min_stock_alert !== 'undefined' && p.min_stock_alert !== null) ? Number(p.min_stock_alert) : null;
            var bajo = minimo !== null && stock < minimo;

            html += '<p style="font-size:11px;color:' + (bajo ? '#b91c1c' : '#475569') + ';margin:4px 0 0;">'
                + (bajo ? fdBadge('Stock bajo', '#b91c1c', '#fee2e2') : fdBadge('Con stock', '#047857', '#d1fae5'))
                + ' <span style="margin-left:4px;">' + fdInvNum(stock) + ' disponible'
                + (minimo !== null ? ' · mínimo ' + fdInvNum(minimo) : '')
                + '</span></p>';
        }

        html += '</div>'
            + '<div style="display:flex;align-items:center;gap:10px;white-space:nowrap;">'
            + '<button type="button" title="Editar" aria-label="Editar" onclick="openProductModal(' + escapeHtml(String(p.id)) + ')" style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:6px 8px;border-radius:3px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">' + FD_ICON_EDIT + '</button>'
            + '<div style="font-size:14px;font-weight:700;color:#D24C19;">' + fdMoney(p.price) + '</div>'
            + '</div>'
            + '</div>';

        var variants = p.variants || [];
        if (variants.length) {
            html += '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">';
            variants.forEach(function (v) {
                var ok = v.is_available !== false;
                html += '<span style="font-size:11px;padding:4px 9px;border:1px solid ' + (ok ? '#fdba74' : '#e2e8f0')
                    + ';border-radius:4px;background:' + (ok ? '#fff7ed' : '#f8fafc')
                    + ';color:' + (ok ? '#9a3412' : '#94a3b8') + ';">'
                    + escapeHtml(v.name) + ' · ' + fdMoney(v.price) + (ok ? '' : ' · sin stock') + '</span>';
            });
            html += '</div>';
        }

        var groups = p.option_groups || [];
        groups.forEach(function (g) {
            var opts = g.options || [];
            var rules = [];
            if (g.min_choices) rules.push('mín. ' + g.min_choices);
            if (g.max_choices) rules.push('máx. ' + g.max_choices);
            if (g.is_required) rules.push('obligatorio');

            html += '<div style="margin-top:10px;padding-top:10px;border-top:1px dashed #e5e7eb;">'
                + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;">'
                + '<span style="font-weight:600;color:#0f172a;">' + escapeHtml(g.name) + '</span>'
                + (rules.length ? '<span style="color:#94a3b8;">' + escapeHtml(rules.join(' · ')) + '</span>' : '')
                + '</div>';

            if (opts.length) {
                html += '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;">';
                opts.forEach(function (o) {
                    var ok = o.is_available !== false;
                    html += '<span style="font-size:11px;padding:4px 9px;border:1px solid #e2e8f0;border-radius:4px;background:'
                        + (ok ? '#fff' : '#f8fafc') + ';color:' + (ok ? '#334155' : '#94a3b8') + ';">'
                        + escapeHtml(o.name)
                        + (Number(o.extra_price) > 0 ? ' <b style="color:#D24C19;">+' + fdMoney(o.extra_price) + '</b>' : '')
                        + (ok ? '' : ' · sin stock')
                        + '</span>';
                });
                html += '</div>';
            } else {
                html += '<div style="font-size:11px;color:#94a3b8;margin-top:4px;">Sin opciones cargadas.</div>';
            }

            html += '</div>';
        });

        return html + '</div>';
    }

    // --- Pedidos ---

    function fdClock() {
        return new Date().toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function fdToast(text, isError) {
        var el = document.createElement('div');
        el.textContent = text;
        el.style.cssText = 'position:fixed;right:18px;bottom:18px;z-index:9999;max-width:340px;padding:11px 16px;'
            + 'border-radius:4px;font-size:13px;font-weight:600;color:#fff;box-shadow:0 6px 20px rgba(0,0,0,.18);'
            + 'background:' + (isError ? '#b91c1c' : '#047857') + ';';
        document.body.appendChild(el);
        setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 4000);
    }

    // Ultimo HTML pintado por lista: evita repintar (y parpadear) cuando la
    // actualizacion automatica trae los mismos datos.
    var fdOrdersLastHtml = '';
    var fdMyOrdersLastHtml = '';

    // Mis Pedidos: pestaña activa (pedidos de hoy / historial) y pedido completos.
    var fdMyOrdersActive = 'today';
    var fdMyOrdersAll = [];
    // Historial paginado: 3 pedidos por página (hoy se muestra completo).
    var fdMyOrdersHistoryPage = 1;
    var FD_MYORDERS_PER_PAGE = 3;

    function loadProviderOrders(silent) {
        var providerId = fdProviderId('dash-pedidos');
        if (!providerId) {
            if (!silent) fdSetOrdersState('message', 'Todavía no tenés un comercio configurado.\nCreá tu comercio desde Tu Perfil para recibir pedidos.');
            return;
        }

        var filterEl = document.getElementById('fd-orders-filter');
        var savedFilter = filterEl ? filterEl.value : '';

        if (!silent) fdSetOrdersState('loading');

        var url = '{{ url("/api/providers") }}/' + providerId + '/orders';
        if (savedFilter) url += '?status=' + encodeURIComponent(savedFilter);

        fdFetchJson(url)
            .then(function (res) {
                if (!res.ok) {
                    if (silent) return;
                    fdSetOrdersState('message', (res.data && res.data.message) || 'No se pudieron cargar los pedidos.');
                    return;
                }

                var filter = document.getElementById('fd-orders-filter');
                if (filter && filter.options.length <= 1) {
                    (res.data.statuses || []).forEach(function (status) {
                        var opt = document.createElement('option');
                        opt.value = status;
                        opt.textContent = FD_ORDER_STATUS[status] ? FD_ORDER_STATUS[status][0] : status;
                        filter.appendChild(opt);
                    });
                    filter.value = savedFilter;
                }

                var orders = res.data.orders || [];
                var provider = res.data.provider || {};
                var pending = orders.filter(function (o) { return o.status === 'pending'; }).length;

                var summary = document.getElementById('fd-orders-summary');
                if (summary) {
                    summary.textContent = (provider.business_name || 'Tu comercio') +
                        ' · ' + orders.length + ' pedidos' +
                        (pending ? ' · ' + pending + ' pendientes' : '') +
                        ' · actualizado ' + fdClock();
                }

                if (!orders.length) {
                    fdOrdersLastHtml = '';
                    fdSetOrdersState('message', savedFilter
                        ? 'No hay pedidos con ese estado.'
                        : 'Todavía no recibiste pedidos.\nCuando un cliente coloque su pedido va a aparecer acá.');
                    return;
                }

                var html = orders.map(fdRenderOrder).join('');
                var list = document.getElementById('fd-orders-list');

                if (silent && html === fdOrdersLastHtml && list.style.display === 'block') return;

                fdOrdersLastHtml = html;
                list.innerHTML = html;
                fdSetOrdersState('content');
            })
            .catch(function () {
                if (silent) return;
                fdSetOrdersState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function fdRenderOrder(o) {
        var client = o.user || {};
        var address = o.address;
        var html = '<div data-order-id="' + Number(o.id) + '" style="background:#fff;border:1px solid #e5e7eb;margin-bottom:14px;overflow:hidden;">'
            + '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:11px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb;">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
            + '<span style="font-size:13px;font-weight:700;color:#0c2a4d;font-family:ui-monospace,monospace;">' + escapeHtml(o.order_number) + '</span>'
            + fdStatusBadge(o.status)
            + fdPaymentBadge(o.payments)
            + '</div>'
            + '<span style="font-size:11px;color:#94a3b8;">' + fdDate(o.created_at) + '</span>'
            + '</div>';

        html += '<div style="padding:14px 16px;">'
            + '<div style="font-size:13px;color:#334155;">'
            + '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">'
            + '<b>' + escapeHtml(client.name || 'Cliente') + '</b>'
            + '<span style="color:#94a3b8;font-size:12px;">' + escapeHtml(client.email || '') + '</span>'
            + '</div>';

        if (address) {
            html += '<div style="color:#64748b;margin-top:4px;font-size:12px;">'
                + escapeHtml(address.street) + ' ' + escapeHtml(address.number || '')
                + (address.floor_apartment ? ', ' + escapeHtml(address.floor_apartment) : '')
                + (address.postal_code ? ' · CP ' + escapeHtml(address.postal_code) : '')
                + '</div>';
            if (address.notes) {
                html += '<div style="color:#94a3b8;font-size:11px;margin-top:2px;">' + escapeHtml(address.notes) + '</div>';
            }
        } else {
            html += '<div style="color:#94a3b8;margin-top:4px;font-size:12px;">Sin dirección registrada</div>';
        }

        if (o.notes) {
            html += '<div style="margin-top:4px;font-size:12px;color:#9a3412;">Nota: ' + escapeHtml(o.notes) + '</div>';
        }

        html += '</div>'
            + '<div style="border-top:1px solid #f1f5f9;padding-top:10px;">'
            + (o.items || []).map(fdRenderOrderItem).join('')
            + '</div>'
            + '<div style="border-top:1px solid #e5e7eb;padding-top:10px;font-size:13px;">'
            + fdTotalRow('Subtotal', fdMoney(o.subtotal), false)
            + fdTotalRow('Envío', fdMoney(o.delivery_fee), false)
            + fdTotalRow('Descuento', '-' + fdMoney(o.discount), false)
            + '<div style="display:flex;justify-content:space-between;font-size:15px;font-weight:700;color:#0c2a4d;margin-top:6px;">'
            + '<span>Total</span><span>' + fdMoney(o.total) + '</span></div>'
            + '</div>'
            + '</div>'
            + fdOrderActions(o)
            + '</div>';

        return html;
    }

    function fdRenderOrderItem(it) {
        var options = it.options || [];
        var optionsHtml = '';

        if (options.length) {
            optionsHtml = '<div style="font-size:11px;color:#94a3b8;margin-top:2px;">'
                + options.map(function (op) {
                    return escapeHtml(op.option_name) +
                        (Number(op.extra_price) > 0 ? ' +' + fdMoney(op.extra_price) : '');
                }).join(' · ')
                + '</div>';
        }

        return '<div style="display:flex;justify-content:space-between;gap:10px;padding:6px 0;font-size:13px;flex-wrap:wrap;">'
            + '<div style="min-width:0;">'
            + '<b>' + Number(it.quantity) + '× </b>' + escapeHtml(it.product_name)
            + (it.variant_name ? ' <span style="color:#94a3b8;">(' + escapeHtml(it.variant_name) + ')</span>' : '')
            + '<div style="font-size:11px;color:#94a3b8;">' + fdMoney(it.unit_price) + ' c/u</div>'
            + optionsHtml
            + '</div>'
            + '<div style="font-weight:600;white-space:nowrap;">' + fdMoney(it.total_price) + '</div>'
            + '</div>';
    }

    function fdTotalRow(label, value, strong) {
        return '<div style="display:flex;justify-content:space-between;color:#64748b;' +
            (strong ? 'font-weight:700;color:#0c2a4d;' : '') + '">'
            + '<span>' + escapeHtml(label) + '</span><span>' + value + '</span></div>';
    }

    // ============ FAST DELIVERY: COMERCIOS Y MIS PEDIDOS DEL CLIENTE ============

    var FD_SHOP = { providerId: null, providerName: '', catalog: null, cart: [], addresses: null, settings: {}, busy: false, categoryFilter: '' };
    var FD_PAYMENT_METHOD_LABELS = { 'efectivo': 'Efectivo', 'transfer': 'Transferencia', 'transferencia': 'Transferencia', 'mercado_pago': 'Mercado Pago', 'mercadopago': 'Mercado Pago' };
    var fdShopsTimer = null;
    var fdShopsTabActive = 'favorites';
    var fdShopsCounts = { favorites: 0, all: 0 };

    function fdSetShopsState(state, text, actionHtml) {
        var map = { loading: 'fd-shops-loading', message: 'fd-shops-message', content: 'fd-shops-grid' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? (k === 'content' ? 'grid' : 'block') : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-shops-message-text');
            if (msg) msg.textContent = text;
        }
        var action = document.getElementById('fd-shops-message-action');
        if (action) action.innerHTML = actionHtml || '';
    }

    function fdPaintShopsTabs() {
        var favBtn = document.getElementById('fd-shops-tab-favorites');
        var allBtn = document.getElementById('fd-shops-tab-all');
        var isFav = fdShopsTabActive === 'favorites';

        var tabBase = 'padding:7px 10px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;';
        if (favBtn) favBtn.style.cssText = isFav
            ? 'background:#fff1eb;color:#D24C19;border:1px solid #D24C19;' + tabBase
            : 'background:#fff;color:#6b7280;border:1px solid #e5e7eb;' + tabBase;
        if (allBtn) allBtn.style.cssText = !isFav
            ? 'background:#fff1eb;color:#D24C19;border:1px solid #D24C19;' + tabBase
            : 'background:#fff;color:#6b7280;border:1px solid #e5e7eb;' + tabBase;

        [['favorites', 'fd-shops-count-favorites'], ['all', 'fd-shops-count-all']].forEach(function (pair) {
            var el = document.getElementById(pair[1]);
            if (!el) return;
            var count = fdShopsCounts[pair[0]];
            el.textContent = count;
            el.style.display = count > 0 ? 'inline-block' : 'none';
        });
    }

    function fdShopsTab(tab) {
        if (tab !== 'favorites' && tab !== 'all') return;
        if (fdShopsTabActive === tab) return;
        fdShopsTabActive = tab;
        fdPaintShopsTabs();
        loadComercios();
    }

    // Estado vacio de Favoritos: solo el boton con icono, sin descripcion.
    function fdShopsFavoritesEmptyHtml() {
        return '<button type="button" onclick="fdShopsTab(\'all\')" title="Ver todos los comercios" aria-label="Ver todos los comercios" '
            + 'style="background:#D24C19;color:#fff;border:none;padding:8px 10px;border-radius:4px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>'
            + '</button>';
    }

    function fdSetMyOrdersState(state, text) {
        var map = { loading: 'fd-myorders-loading', message: 'fd-myorders-message', content: 'fd-myorders-list' };
        Object.keys(map).forEach(function (k) {
            var el = document.getElementById(map[k]);
            if (el) el.style.display = (k === state) ? 'block' : 'none';
        });
        if (state === 'message' && typeof text !== 'undefined') {
            var msg = document.getElementById('fd-myorders-message-text');
            if (msg) msg.textContent = text;
        }
    }

    function fdShopsSearchDebounce() {
        if (fdShopsTimer) clearTimeout(fdShopsTimer);
        fdShopsTimer = setTimeout(loadComercios, 300);
    }

    function loadComercios() {
        var input = document.getElementById('fd-shops-search');
        var search = input ? input.value.trim() : '';
        var section = document.getElementById('dash-comercios');
        var moduleId = section ? parseInt(section.getAttribute('data-module-id'), 10) : NaN;
        var params = [];
        var isFavorites = fdShopsTabActive === 'favorites';

        // Solo comercios de la categoria que corresponde al modulo abierto
        if (moduleId > 0) params.push('module_id=' + moduleId);
        if (search) params.push('search=' + encodeURIComponent(search));
        if (isFavorites) params.push('favorite=1');

        fdSetShopsState('loading');

        fdFetchJson('{{ url("/api/providers") }}' + (params.length ? '?' + params.join('&') : ''))
            .then(function (res) {
                if (!res.ok) {
                    fdSetShopsState('message', (res.data && res.data.message) || 'No se pudieron cargar los comercios.');
                    return;
                }

                var providers = res.data.providers || [];
                FD_SHOP.settings = res.data.settings || {};
                fdShopsCounts = {
                    favorites: Number(res.data.favorites_count || 0),
                    all: Number(res.data.total_count || 0),
                };
                fdPaintShopsTabs();

                var summary = document.getElementById('fd-shops-summary');
                if (summary) {
                    if (isFavorites) {
                        summary.textContent = fdShopsCounts.favorites
                            ? fdShopsCounts.favorites + ' comercios favoritos'
                            : 'Tus comercios concurrentes';
                    } else {
                        summary.textContent = providers.length
                            ? providers.length + ' comercios disponibles'
                            : 'Comercios con envío a tu domicilio';
                    }
                }

                if (!providers.length) {
                    if (isFavorites) {
                        fdSetShopsState('message', '', fdShopsFavoritesEmptyHtml());
                    } else {
                        fdSetShopsState('message', search
                            ? 'No encontramos comercios con ese nombre.'
                            : 'Todavía no hay comercios publicados.\nPronto vas a poder pedir desde acá.');
                    }
                    return;
                }

                document.getElementById('fd-shops-grid').innerHTML = providers.map(fdRenderProviderCard).join('');
                fdSetShopsState('content');
            })
            .catch(function () {
                fdSetShopsState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function fdRenderProviderCard(p) {
        var initial = escapeHtml((p.business_name || '?').replace(/^\s+/, '').charAt(0).toUpperCase());
        var meta = p.rubro || p.zone || 'Comercio';

        var html = '<div data-fd-provider="' + Number(p.id) + '" style="background:#fff;border:1px solid #e5e7eb;padding:16px;display:flex;flex-direction:column;gap:10px;">';

        if (p.publicidad_image) {
            html += '<img src="' + escapeHtml(p.publicidad_image) + '" alt="Publicidad de ' + escapeHtml(p.business_name || '') + '" style="width:calc(100% + 32px);margin:-16px -16px 0;display:block;height:70px;object-fit:cover;background:#f1f5f9;" />';
        }

        html += '<div style="display:flex;gap:12px;align-items:center;">'
            + '<div style="width:44px;height:44px;flex:none;border-radius:50%;background:#fff1eb;color:#D24C19;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:18px;">' + initial + '</div>'
            + '<div style="min-width:0;">'
            + '<div style="font-size:14px;font-weight:700;color:#0c2a4d;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(p.business_name) + '</div>'
            + '<div style="font-size:11px;color:#94a3b8;">' + escapeHtml(meta) + (p.zone && p.rubro ? ' · ' + escapeHtml(p.zone) : '') + '</div>'
            + '</div>'
            + (p.rating ? '<div style="margin-left:auto;font-size:12px;font-weight:700;color:#b45309;white-space:nowrap;">★ ' + escapeHtml(Number(p.rating).toFixed(1)) + '</div>' : '')
            + '</div>';

        if (p.promo) {
            html += '<div style="font-size:11px;color:#047857;background:#ecfdf5;padding:6px 8px;">' + escapeHtml(p.promo) + '</div>';
        }

        html += '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:auto;flex-wrap:wrap;">'
            + '<span style="font-size:11px;color:#6b7280;">' + Number(p.products_count || 0) + ' productos · ' + Number(p.categories_count || 0) + ' categorías</span>'
            + '<span style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">'
            + '<button type="button" class="btn-icon" onclick="fdToggleFavorite(' + Number(p.id) + ', this)" '
            + 'title="' + (p.is_favorite ? 'Quitar de favoritos' : 'Agregar a favoritos') + '" '
            + 'aria-label="' + (p.is_favorite ? 'Quitar de favoritos' : 'Agregar a favoritos') + '" '
            + 'style="background:#fff;color:' + (p.is_favorite ? '#D24C19' : '#94a3b8') + ';border:1px solid ' + (p.is_favorite ? '#D24C19' : '#d1d5db') + ';border-radius:4px;cursor:pointer;">'
            + (p.is_favorite ? FD_ICON_STAR_FILLED : FD_ICON_STAR) + '</button>'
            + (Number(p.active_subscription_plans_count || 0) > 0
                ? '<button type="button" class="btn-icon" onclick="fdSubscribeToProvider(' + Number(p.id) + ',&quot;' + escapeHtml(p.business_name || '').replace(/"/g, '&quot;') + '&quot;)" title="Suscribirse a un plan de este comercio" aria-label="Suscribirse" style="background:#fff;color:#D24C19;border:1px solid #D24C19;border-radius:4px;cursor:pointer;">' + FD_ICON_USER_PLUS + '</button>'
                : '')
            + '<button type="button" class="btn-icon" onclick="openFdShop(' + Number(p.id) + ')" title="Ver catálogo" aria-label="Ver catálogo" style="background:#D24C19;color:#fff;border:none;border-radius:4px;cursor:pointer;">' + FD_ICON_BOOK + '</button>'
            + '</span>'
            + '</div>'
            + '</div>';

        return html;
    }

    function fdSubscribeToProvider(providerId, providerName) {
        var section = document.getElementById('dash-mis-suscripciones');
        if (!section) {
            fdToast('No tenés habilitada la sección Mis Suscripciones.', true);
            return;
        }

        showDashSection('mis-suscripciones');

        if (typeof openSubNewForm === 'function') {
            openSubNewForm(Number(providerId), providerName || '');
        }
    }

    function fdToggleFavorite(providerId, btn) {
        if (btn) btn.disabled = true;

        fdFetchJson('{{ url("/api/providers") }}/' + Number(providerId) + '/favorite', { method: 'POST' })
            .then(function (res) {
                if (!res.ok) {
                    fdToast((res.data && res.data.message) || 'No se pudo actualizar el favorito.', true);
                    return;
                }

                var isFav = !!(res.data && res.data.is_favorite);
                fdShopsCounts.favorites = Number(res.data.favorites_count || 0);
                fdPaintShopsTabs();
                fdToast(isFav ? 'Comercio agregado a tus favoritos ★' : 'Comercio quitado de tus favoritos');

                var card = btn && btn.closest ? btn.closest('[data-fd-provider]') : null;
                if (btn) {
                    var label = isFav ? 'Quitar de favoritos' : 'Agregar a favoritos';
                    btn.title = label;
                    if (btn.setAttribute) btn.setAttribute('aria-label', label);
                    btn.style = btn.style || {};
                    btn.style.color = isFav ? '#D24C19' : '#94a3b8';
                    btn.style.borderColor = isFav ? '#D24C19' : '#d1d5db';
                    btn.innerHTML = isFav ? FD_ICON_STAR_FILLED : FD_ICON_STAR;
                }

                if (card && !isFav && fdShopsTabActive === 'favorites' && card.parentNode) {
                    card.parentNode.removeChild(card);
                    var grid = document.getElementById('fd-shops-grid');
                    var stillThere = grid && grid.querySelector ? grid.querySelector('[data-fd-provider]') : null;
                    if (!stillThere) {
                        fdSetShopsState('message', '', fdShopsFavoritesEmptyHtml());
                    }
                }

                if (fdShopsTabActive === 'favorites') {
                    var summary = document.getElementById('fd-shops-summary');
                    if (summary) {
                        summary.textContent = fdShopsCounts.favorites
                            ? fdShopsCounts.favorites + ' comercios favoritos'
                            : 'Tus comercios concurrentes';
                    }
                }
            })
            .catch(function () {
                fdToast('No se pudo conectar con el servidor.', true);
            })
            .then(function () {
                if (btn) btn.disabled = false;
            });
    }

    function openFdShop(providerId) {
        FD_SHOP.providerId = Number(providerId);
        FD_SHOP.providerName = '';
        FD_SHOP.catalog = null;
        FD_SHOP.cart = [];
        FD_SHOP.categoryFilter = '';
        fdShopFillCategoryFilter([]);

        var overlay = document.getElementById('fd-shop-overlay');
        if (overlay) {
            // La tienda ya no es un modal: reemplaza la seccion actual, como
            // cualquier otra seccion del dashboard.
            document.querySelectorAll('.dash-section').forEach(function (s) { s.style.display = 'none'; });
            overlay.style.display = 'block';
            window.scrollTo(0, 0);

            if (fdSectionHistoryReady) {
                history.pushState({ fdSection: 'tienda' }, '', '{{ url('/dashboard') }}');
            }
        }

        document.getElementById('fd-shop-title').textContent = 'Cargando...';
        document.getElementById('fd-shop-subtitle').textContent = '';
        document.getElementById('fd-shop-body').innerHTML =
            '<p style="font-size:13px;color:#6b7280;text-align:center;padding:32px 0;margin:0;">Cargando catálogo...</p>';
        document.getElementById('fd-shop-cart').innerHTML = '';
        fdShopPaintBadge();

        fdLoadShopAddresses();

        fdFetchJson('{{ url("/api/providers") }}/' + FD_SHOP.providerId + '/catalog?only_available=1')
            .then(function (res) {
                if (!res.ok) {
                    document.getElementById('fd-shop-body').innerHTML =
                        '<p style="font-size:13px;color:#b91c1c;text-align:center;padding:32px 0;margin:0;">'
                        + escapeHtml((res.data && res.data.message) || 'No se pudo cargar el catálogo.') + '</p>';
                    return;
                }

                FD_SHOP.catalog = res.data;
                fdShopFillCategoryFilter(res.data.categories || []);

                var provider = res.data.provider || {};
                FD_SHOP.providerName = provider.business_name || '';

                document.getElementById('fd-shop-title').textContent = provider.business_name || 'Comercio';

                var hours = provider.hours;
                var hoursText = Array.isArray(hours) ? hours.join(' · ') : (typeof hours === 'string' ? hours : '');
                document.getElementById('fd-shop-subtitle').textContent = [provider.zone, hoursText].filter(Boolean).join(' · ');

                document.getElementById('fd-shop-body').innerHTML =
                    fdRenderShopCatalog(res.data.categories || []);
                fdRenderShopCart();
            })
            .catch(function () {
                document.getElementById('fd-shop-body').innerHTML =
                    '<p style="font-size:13px;color:#b91c1c;text-align:center;padding:32px 0;margin:0;">No se pudo conectar con el servidor.</p>';
            });
    }

    function closeFdShop() {
        var overlay = document.getElementById('fd-shop-overlay');
        if (overlay) overlay.style.display = 'none';
        FD_SHOP.providerId = null;
        FD_SHOP.catalog = null;
        FD_SHOP.cart = [];
        fdShopPaintBadge();
    }

    // Volver desde la tienda: si la abrimos empujando el estado 'tienda',
    // volvemos con el historial para no dejar entradas repetidas.
    function fdShopBack() {
        closeFdShop();
        if (fdSectionHistoryReady && history.state && history.state.fdSection === 'tienda') {
            history.back();
        } else {
            showDashSection(FD_SECTION_SHOP_FALLBACK);
        }
    }

    // Navegacion desde la tienda a otra seccion del panel (ej. Mis Pedidos):
    // reemplaza la entrada 'tienda' para que Atras siga funcionando bien.
    function fdShopGo(key) {
        closeFdShop();
        if (fdSectionHistoryReady && history.state && history.state.fdSection === 'tienda') {
            history.replaceState({ fdSection: key }, '', '{{ url('/dashboard') }}');
            fdSectionPopNav = true;
            try {
                showDashSection(key);
            } finally {
                fdSectionPopNav = false;
            }
        } else {
            showDashSection(key);
        }
    }

    // Combo de categorias del comercio: filtra el catalogo de la tienda.
    function fdShopFillCategoryFilter(categories) {
        var sel = document.getElementById('fd-shop-category');
        if (!sel) return;

        sel.innerHTML = '<option value="">Todas las categorías</option>'
            + categories.map(function (c, i) {
                return '<option value="' + i + '">' + escapeHtml(c.name || '') + '</option>';
            }).join('');
        sel.value = '';
    }

    function fdShopCategoryFilter() {
        var sel = document.getElementById('fd-shop-category');
        var body = document.getElementById('fd-shop-body');
        if (!body || !FD_SHOP.catalog) return;

        var value = sel ? String(sel.value) : '';
        FD_SHOP.categoryFilter = value;

        var categories = FD_SHOP.catalog.categories || [];
        var visible = categories;

        if (value !== '') {
            var index = Number(value);
            visible = !isNaN(index) && categories[index] ? [categories[index]] : [];
        }

        body.innerHTML = fdRenderShopCatalog(visible);
        body.scrollTop = 0;
    }

    function fdRenderShopCatalog(categories) {
        if (!categories.length) {
            return '<p style="font-size:13px;color:#6b7280;text-align:center;padding:32px 0;margin:0;">'
                + 'Este comercio todavía no tiene productos disponibles.</p>';
        }

        return categories.map(function (c) {
            return '<div style="margin-bottom:18px;">'
                + '<div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#9a3412;margin-bottom:8px;">'
                + escapeHtml(c.name) + '</div>'
                + (c.products || []).map(fdRenderShopProduct).join('')
                + '</div>';
        }).join('');
    }

    function fdRenderShopProduct(p) {
        var variants = p.variants || [];
        var groups = p.option_groups || [];
        var maxQty = Number(FD_SHOP.settings.max_quantity_per_item || 20);

        var initial = escapeHtml(String(p.name || '?').charAt(0).toUpperCase());
        var photo = '<div style="width:88px;height:88px;flex:none;border-radius:6px;overflow:hidden;background:#eef2f6;display:flex;align-items:center;justify-content:center;position:relative;">'
            + '<span style="font-size:26px;font-weight:800;color:#94a3b8;">' + initial + '</span>'
            + (p.image_path
                ? '<img src="{{ asset("images/") }}/' + escapeHtml(p.image_path) + '" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;" onerror="this.remove();" />'
                : '')
            + '</div>';

        var html = '<div id="fd-p-' + p.id + '" style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:12px 14px;margin-bottom:10px;">'
            + '<div style="display:flex;gap:12px;align-items:flex-start;">'
            + photo
            + '<div style="flex:1;min-width:0;">'
            + '<div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">'
            + '<div style="min-width:0;">'
            + '<div style="font-size:14px;font-weight:700;color:#0f172a;">' + escapeHtml(p.name) + '</div>'
            + (p.description ? '<div style="font-size:12px;color:#64748b;margin-top:2px;">' + escapeHtml(p.description) + '</div>' : '')
            + '</div>'
            + '<div style="font-size:14px;font-weight:700;color:#0f172a;white-space:nowrap;">'
            + fdMoney(variants.length ? variants[0].price : p.price) + '</div>'
            + '</div>';

        if (variants.length > 1) {
            html += '<div style="margin-top:8px;">'
                + '<select id="fd-variant-' + p.id + '" style="width:100%;padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;background:#fff;outline:none;">'
                + variants.map(function (v) {
                    return '<option value="' + v.id + '">' + escapeHtml(v.name) + ' — ' + fdMoney(v.price) + '</option>';
                }).join('')
                + '</select></div>';
        } else if (variants.length === 1) {
            html += '<div style="margin-top:6px;font-size:11px;color:#94a3b8;">' + escapeHtml(variants[0].name) + '</div>'
                + '<input type="hidden" id="fd-variant-' + p.id + '" value="' + variants[0].id + '" />';
        }

        groups.forEach(function (g) {
            var opts = g.options || [];
            if (!opts.length) return;

            var min = Number(g.min_choices || 0);
            var max = Number(g.max_choices || 0);
            var hint = (min > 0 ? 'obligatorio' : 'opcional') + (max > 0 ? ', hasta ' + max : '');

            html += '<div style="margin-top:10px;font-size:12px;color:#334155;">'
                + '<div style="font-weight:700;color:#0f172a;">' + escapeHtml(g.name) + ' <span style="color:#94a3b8;font-weight:500;">(' + hint + ')</span></div>'
                + '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">'
                + opts.map(function (o) {
                    return '<label style="display:flex;align-items:center;gap:6px;font-size:12px;background:#f8fafc;border:1px solid #e5e7eb;padding:5px 8px;cursor:pointer;">'
                        + '<input type="checkbox" class="fd-opt" data-product="' + p.id + '" data-group="' + g.id + '" data-extra="' + Number(o.extra_price || 0) + '" value="' + o.id + '" style="accent-color:#D24C19;cursor:pointer;" />'
                        + escapeHtml(o.name)
                        + (Number(o.extra_price) > 0 ? ' <span style="margin-left:4px;color:#D24C19;font-weight:700;">+' + fdMoney(o.extra_price) + '</span>' : '')
                        + '</label>';
                }).join('')
                + '</div></div>';
        });

        var outOfStock = !!p.is_out_of_stock;
        var qtyControls = '<div style="display:flex;align-items:center;gap:6px;'
            + (outOfStock ? 'opacity:.5;' : '') + '">'
            + '<button type="button" onclick="fdShopQty(' + p.id + ', -1)" title="Quitar una unidad" aria-label="Quitar una unidad" style="width:30px;height:30px;border:1px solid #e5e7eb;background:#f3f4f6;border-radius:4px;cursor:pointer;font-size:15px;line-height:1;color:#374151;">−</button>'
            + '<input type="number" id="fd-qty-' + p.id + '" value="1" min="1" readonly style="width:48px;text-align:center;padding:5px 0;border:1px solid #e5e7eb;border-radius:4px;font-size:13px;background:#fff;" />'
            + '<button type="button" onclick="fdShopQty(' + p.id + ', 1)" title="Agregar una unidad" aria-label="Agregar una unidad" style="width:30px;height:30px;border:1px solid #e5e7eb;background:#f3f4f6;border-radius:4px;cursor:pointer;font-size:15px;line-height:1;color:#374151;">+</button>'
            + '</div>';

        var addButton = outOfStock
            ? '<span style="font-size:12px;font-weight:700;color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;padding:6px 10px;border-radius:4px;">Sin stock</span>'
            : '<button type="button" class="btn-icon" onclick="fdShopAdd(' + p.id + ')" title="Agregar al carrito" aria-label="Agregar al carrito" style="background:#D24C19;color:#fff;border:none;width:32px;height:32px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/><line x1="12" x2="12" y1="10" y2="16"/><line x1="9" x2="15" y1="13" y2="13"/></svg>'
            + '</button>';

        html += '</div></div>'
            + '<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap;">'
            + qtyControls
            + addButton
            + '</div>'
            + '<div id="fd-p-msg-' + p.id + '" style="display:none;font-size:11px;color:#b91c1c;margin-top:6px;"></div>'
            + '</div>';

        return html;
    }

    function fdShopQty(productId, delta) {
        var el = document.getElementById('fd-qty-' + productId);
        if (!el) return;
        var max = Number(FD_SHOP.settings.max_quantity_per_item || 20);
        var value = (parseInt(el.value, 10) || 1) + delta;
        el.value = Math.min(Math.max(value, 1), Math.max(max, 1));
    }

    function fdShopFindProduct(productId) {
        var categories = (FD_SHOP.catalog && FD_SHOP.catalog.categories) || [];
        for (var i = 0; i < categories.length; i++) {
            var products = categories[i].products || [];
            for (var j = 0; j < products.length; j++) {
                if (Number(products[j].id) === Number(productId)) return products[j];
            }
        }
        return null;
    }

    function fdShopAdd(productId) {
        var p = fdShopFindProduct(productId);
        if (!p) return;

        var msgEl = document.getElementById('fd-p-msg-' + productId);
        var showMsg = function (text) {
            if (msgEl) { msgEl.textContent = text; msgEl.style.display = 'block'; }
        };
        var hideMsg = function () { if (msgEl) msgEl.style.display = 'none'; };

        if (p.is_out_of_stock) {
            showMsg('Sin stock en este momento.');
            return;
        }

        var qtyEl = document.getElementById('fd-qty-' + productId);
        var quantity = Math.max(1, parseInt(qtyEl ? qtyEl.value : '1', 10) || 1);
        var maxQty = Number(FD_SHOP.settings.max_quantity_per_item || 20);

        if (quantity > maxQty) {
            showMsg('Máximo ' + maxQty + ' unidades por producto.');
            return;
        }

        var variants = p.variants || [];
        var variantId = null;
        var variantName = '';
        var unitPrice = Number(p.price || 0);

        var variantEl = document.getElementById('fd-variant-' + productId);
        if (variantEl) {
            var selectedVariantId = parseInt(variantEl.value, 10);
            variants.forEach(function (v) {
                if (Number(v.id) === selectedVariantId) {
                    variantId = Number(v.id);
                    variantName = v.name;
                    unitPrice = Number(v.price);
                }
            });
        }

        var boxes = document.querySelectorAll('#fd-p-' + productId + ' input.fd-opt:checked');
        var byGroup = {};
        var options = [];
        var optionLabels = [];
        var optionsExtra = 0;

        Array.prototype.forEach.call(boxes, function (box) {
            var groupId = box.getAttribute('data-group');
            if (!byGroup[groupId]) byGroup[groupId] = [];
            byGroup[groupId].push(box);
            options.push(Number(box.value));
        });

        var invalid = (p.option_groups || []).some(function (g) {
            var selected = byGroup[g.id] || [];
            var min = Number(g.min_choices || 0);
            var max = Number(g.max_choices || 0);

            if (min > 0 && selected.length < min) {
                showMsg('Elegí al menos ' + min + ' opción de "' + g.name + '".');
                return true;
            }
            if (max > 0 && selected.length > max) {
                showMsg('Podés elegir hasta ' + max + ' opciones de "' + g.name + '".');
                return true;
            }
            return false;
        });

        if (invalid) return;

        Array.prototype.forEach.call(boxes, function (box) {
            var extra = Number(box.getAttribute('data-extra') || 0);
            optionsExtra += extra;
            optionLabels.push(box.parentNode.textContent.trim());
        });

        var maxItems = Number(FD_SHOP.settings.max_items_per_order || 30);
        var sortedOptions = options.slice().sort();
        var sameLine = -1;

        FD_SHOP.cart.forEach(function (line, i) {
            if (sameLine !== -1) return;
            if (Number(line.product_id) === Number(p.id)
                && line.variant_id === variantId
                && (line.options || []).slice().sort().join(',') === sortedOptions.join(',')) {
                sameLine = i;
            }
        });

        if (sameLine !== -1) {
            var merged = FD_SHOP.cart[sameLine];
            if (Number(merged.quantity) + quantity > maxQty) {
                showMsg('Máximo ' + maxQty + ' unidades por producto.');
                return;
            }
            merged.quantity = Number(merged.quantity) + quantity;
        } else {
            if (FD_SHOP.cart.length >= maxItems) {
                showMsg('Podés llevar hasta ' + maxItems + ' productos distintos por pedido.');
                return;
            }

            FD_SHOP.cart.push({
                product_id: Number(p.id),
                variant_id: variantId,
                quantity: quantity,
                product_name: p.name,
                variant_name: variantName,
                unit_price: unitPrice,
                options: options,
                options_extra: optionsExtra,
                option_labels: optionLabels
            });
        }

        if (qtyEl) qtyEl.value = 1;
        hideMsg();
        fdRenderShopCart();
        fdToast('Agregado al carrito');
    }

    function fdShopRemove(index) {
        FD_SHOP.cart.splice(Number(index), 1);
        fdRenderShopCart();
    }

    function fdShopLineQty(index, delta) {
        var line = FD_SHOP.cart[Number(index)];
        if (!line) return;

        var max = Number(FD_SHOP.settings.max_quantity_per_item || 20);
        var next = Number(line.quantity) + Number(delta);

        if (next < 1) {
            fdShopRemove(index);
            return;
        }

        line.quantity = Math.min(next, Math.max(max, 1));
        fdRenderShopCart();
    }

    function fdShopPaintBadge() {
        var badge = document.getElementById('fd-shop-cart-badge');
        var confirmBtn = document.getElementById('fd-shop-submit');
        if (!badge) return;

        var units = 0;
        var subtotal = 0;
        FD_SHOP.cart.forEach(function (line) {
            units += Number(line.quantity);
            subtotal += (Number(line.unit_price) + Number(line.options_extra)) * Number(line.quantity);
        });

        if (!units) {
            badge.style.display = 'none';
            badge.innerHTML = '';
            if (confirmBtn) {
                confirmBtn.style.display = 'none';
                confirmBtn.disabled = false;
                confirmBtn.title = 'Confirmar compra';
            }
            return;
        }

        badge.style.display = 'inline-flex';
        badge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>'
            + '<span style="background:#D24C19;color:#fff;border-radius:9999px;min-width:17px;height:17px;padding:0 5px;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;">' + units + '</span>'
            + '<b>' + fdMoney(subtotal) + '</b>';

        if (confirmBtn) confirmBtn.style.display = 'inline-flex';
    }

    function fdLoadShopAddresses() {
        FD_SHOP.addresses = null;
        fdFetchJson('{{ url("/api/me/addresses") }}')
            .then(function (res) {
                FD_SHOP.addresses = (res.ok && res.data.addresses) || [];
                fdRenderShopCart();
            })
            .catch(function () {
                FD_SHOP.addresses = [];
                fdRenderShopCart();
            });
    }

    function fdRenderShopCart() {
        var el = document.getElementById('fd-shop-cart');
        if (!el) return;

        var prevAddress = document.getElementById('fd-shop-address');
        var prevNotes = document.getElementById('fd-shop-notes');
        var prevPayment = document.getElementById('fd-shop-payment');
        var keepAddress = prevAddress ? prevAddress.value : '';
        var keepNotes = prevNotes ? prevNotes.value : '';
        var keepPayment = prevPayment ? prevPayment.value : '';

        if (!FD_SHOP.cart.length) {
            el.innerHTML = '<div style="font-size:12px;color:#6b7280;text-align:center;display:flex;align-items:center;justify-content:center;gap:6px;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#94a3b8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>'
                + 'Tu carrito está vacío. Agregá productos para continuar.</div>';
            fdShopPaintBadge();
            return;
        }

        var subtotal = 0;
        var lines = FD_SHOP.cart.map(function (line, index) {
            var lineTotal = (Number(line.unit_price) + Number(line.options_extra)) * Number(line.quantity);
            subtotal += lineTotal;

            return '<div style="display:flex;justify-content:space-between;gap:10px;font-size:13px;padding:7px 0;border-bottom:1px dashed #e5e7eb;">'
                + '<div style="min-width:0;">'
                + '<b>' + Number(line.quantity) + '×</b> ' + escapeHtml(line.product_name)
                + (line.variant_name ? ' <span style="color:#94a3b8;">(' + escapeHtml(line.variant_name) + ')</span>' : '')
                + (line.option_labels && line.option_labels.length
                    ? '<div style="font-size:11px;color:#6b7280;">' + line.option_labels.map(escapeHtml).join(', ') + '</div>'
                    : '')
                + '</div>'
                + '<div style="display:flex;align-items:center;gap:8px;white-space:nowrap;flex:none;">'
                + '<span style="display:flex;align-items:center;gap:4px;">'
                + '<button type="button" onclick="fdShopLineQty(' + index + ', -1)" title="Quitar una unidad" aria-label="Quitar una unidad" style="width:24px;height:24px;border:1px solid #e5e7eb;background:#f3f4f6;border-radius:4px;cursor:pointer;font-size:13px;line-height:1;color:#374151;">−</button>'
                + '<span style="min-width:18px;text-align:center;font-weight:600;">' + Number(line.quantity) + '</span>'
                + '<button type="button" onclick="fdShopLineQty(' + index + ', 1)" title="Agregar una unidad" aria-label="Agregar una unidad" style="width:24px;height:24px;border:1px solid #e5e7eb;background:#f3f4f6;border-radius:4px;cursor:pointer;font-size:13px;line-height:1;color:#374151;">+</button>'
                + '</span>'
                + '<span style="font-weight:600;">' + fdMoney(lineTotal) + '</span>'
                + '<button type="button" class="btn-icon" onclick="fdShopRemove(' + index + ')" title="Quitar del carrito" aria-label="Quitar del carrito" style="background:none;color:#dc2626;border:none;border-radius:4px;cursor:pointer;padding:2px;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>'
                + '</button>'
                + '</div></div>';
        }).join('');

        var fee = Number(FD_SHOP.settings.delivery_fee || 0);
        var total = subtotal + fee;
        var addresses = FD_SHOP.addresses;

        var addressHtml;
        if (addresses === null) {
            addressHtml = '<div style="font-size:12px;color:#6b7280;margin-top:10px;">Cargando direcciones...</div>';
        } else if (!addresses.length) {
            addressHtml = '<div style="font-size:12px;color:#b45309;margin-top:10px;">'
                + 'No tenés direcciones cargadas. Cargá una desde <b>Tu Perfil</b> para poder pedir.</div>';
        } else {
            addressHtml = '<div style="margin-top:10px;">'
                + '<label style="font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Dirección de entrega *</label>'
                + '<select id="fd-shop-address" style="width:100%;padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;background:#fff;outline:none;">'
                + addresses.map(function (a) {
                    return '<option value="' + a.id + '"' + (String(a.id) === keepAddress ? ' selected' : '') + '>'
                        + escapeHtml(a.formatted || (a.street + ' ' + (a.number || '')))
                        + (a.is_primary ? ' · principal' : '')
                        + '</option>';
                }).join('')
                + '</select></div>';
        }

        var paymentOptions = Object.keys(FD_PAYMENT_METHOD_LABELS).map(function (key) {
            if (key === 'transferencia' || key === 'mercadopago') return '';
            return '<option value="' + key + '"' + (key === keepPayment ? ' selected' : '') + '>'
                + escapeHtml(FD_PAYMENT_METHOD_LABELS[key]) + '</option>';
        }).join('');

        var units = 0;
        FD_SHOP.cart.forEach(function (line) { units += Number(line.quantity); });

        el.innerHTML = '<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">'
            + '<span style="font-size:12px;font-weight:800;color:#0c2a4d;text-transform:uppercase;letter-spacing:.4px;">Tu carrito</span>'
            + '<span style="font-size:11px;color:#6b7280;">' + units + (units === 1 ? ' unidad' : ' unidades') + ' · ' + FD_SHOP.cart.length + (FD_SHOP.cart.length === 1 ? ' producto' : ' productos') + '</span>'
            + '</div>'
            + lines
            + '<div style="border-top:1px solid #e5e7eb;margin-top:8px;padding-top:8px;font-size:13px;">'
            + fdTotalRow('Subtotal', fdMoney(subtotal), false)
            + fdTotalRow('Envío', fdMoney(fee), false)
            + '<div style="display:flex;justify-content:space-between;font-size:15px;font-weight:700;color:#0c2a4d;margin-top:6px;">'
            + '<span>Total estimado</span><span>' + fdMoney(total) + '</span></div>'
            + '</div>'
            + addressHtml
            + '<div style="margin-top:10px;">'
            + '<label style="font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Forma de pago</label>'
            + '<select id="fd-shop-payment" style="width:100%;padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;background:#fff;outline:none;">'
            + paymentOptions
            + '</select></div>'
            + '<div style="margin-top:10px;">'
            + '<label style="font-size:11px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Nota para el comercio</label>'
            + '<textarea id="fd-shop-notes" rows="2" maxlength="1000" placeholder="Ej: sin cebolla, timbre roto, etc." style="width:100%;padding:7px 9px;border:1px solid #d1d5db;border-radius:4px;font-size:12px;outline:none;resize:vertical;">'
            + escapeHtml(keepNotes) + '</textarea></div>';

        var submitBtn = document.getElementById('fd-shop-submit');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.title = 'Confirmar compra · ' + fdMoney(total);
            submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>'
                + '<span>Confirmar compra · ' + fdMoney(total) + '</span>';
        }

        if (keepNotes) {
            var notes = document.getElementById('fd-shop-notes');
            if (notes) notes.value = keepNotes;
        }

        fdShopPaintBadge();
    }

    function fdShopShowMsg(text, isError) {
        var msg = document.getElementById('fd-shop-msg');
        if (!msg) return;
        msg.textContent = text;
        msg.style.color = isError ? '#b91c1c' : '#047857';
        msg.style.background = isError ? '#fef2f2' : '#ecfdf5';
        msg.style.borderColor = isError ? '#fecaca' : '#a7f3d0';
        msg.style.display = 'block';
        if (typeof msg.scrollIntoView === 'function') {
            try { msg.scrollIntoView({ block: 'nearest' }); } catch (e) {}
        }
    }

    function fdShopSubmit() {
        if (FD_SHOP.busy) return;

        if (!FD_SHOP.cart.length) {
            fdShopShowMsg('Agregá al menos un producto antes de confirmar.', true);
            return;
        }

        var addressSel = document.getElementById('fd-shop-address');
        var addressId = addressSel ? parseInt(addressSel.value, 10) : NaN;

        if (isNaN(addressId)) {
            fdShopShowMsg('Seleccioná la dirección de entrega.', true);
            return;
        }

        var notesEl = document.getElementById('fd-shop-notes');
        var paymentEl = document.getElementById('fd-shop-payment');
        var submitBtn = document.getElementById('fd-shop-submit');

        var payload = {
            provider_id: FD_SHOP.providerId,
            address_id: addressId,
            payment_method: paymentEl ? paymentEl.value : 'efectivo',
            notes: notesEl ? notesEl.value.trim() : '',
            items: FD_SHOP.cart.map(function (line) {
                return {
                    product_id: line.product_id,
                    variant_id: line.variant_id,
                    quantity: line.quantity,
                    options: line.options
                };
            })
        };

        FD_SHOP.busy = true;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.title = 'Enviando...';
            submitBtn.style.opacity = '0.65';
        }

        fetch('{{ url("/api/orders") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (res) {
            FD_SHOP.busy = false;

            if (!res.ok) {
                var message = (res.data && res.data.message) || 'No se pudo registrar el pedido.';
                if (res.data && res.data.errors) {
                    var firstKey = Object.keys(res.data.errors)[0];
                    message = res.data.errors[firstKey][0];
                }
                // Repinta el carrito: conserva dirección/nota/pago y devuelve
                // el botón con el total por si el usuario reintenta.
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                }
                fdRenderShopCart();
                fdShopShowMsg(message, true);
                return;
            }

            var orderNumber = (res.data.order && res.data.order.order_number) || '';

            FD_SHOP.cart = [];
            fdShopPaintBadge();
            document.getElementById('fd-shop-cart').innerHTML =
                '<div style="text-align:center;padding:6px 0;">'
                + '<div style="font-size:14px;font-weight:700;color:#047857;margin-bottom:6px;">Pedido registrado</div>'
                + '<div style="font-size:12px;color:#64748b;">'
                + (orderNumber ? 'Número <b>' + escapeHtml(orderNumber) + '</b>. ' : '')
                + 'El comercio va a confirmar tu pedido y lo vas a ver en Mis Pedidos.</div>'
                + '<button type="button" onclick="fdShopGo(\'mis-pedidos\');" title="Ver mis pedidos" aria-label="Ver mis pedidos" style="margin-top:10px;background:#0c2a4d;color:#fff;border:none;width:32px;height:32px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>'
                + '</button>'
                + '</div>';

            var cartEl = document.getElementById('fd-shop-cart');
            if (cartEl) cartEl.scrollTop = 0;

            loadMyOrders();
        })
        .catch(function () {
            FD_SHOP.busy = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
            fdRenderShopCart();
            fdShopShowMsg('No se pudo conectar con el servidor.', true);
        });
    }

    function loadMyOrders(silent) {
        var filterEl = document.getElementById('fd-myorders-filter');
        var savedFilter = filterEl ? filterEl.value : '';

        if (!silent) {
            // Carga manual o cambio de filtro: vuelve a la primera página.
            fdMyOrdersHistoryPage = 1;
            fdSetMyOrdersState('loading');
        }

        var url = '{{ url("/api/orders/mine") }}';
        if (savedFilter) url += '?status=' + encodeURIComponent(savedFilter);

        fdFetchJson(url)
            .then(function (res) {
                if (!res.ok) {
                    if (silent) return;
                    fdSetMyOrdersState('message', (res.data && res.data.message) || 'No se pudieron cargar tus pedidos.');
                    return;
                }

                var filter = document.getElementById('fd-myorders-filter');
                if (filter && filter.options.length <= 1) {
                    (res.data.statuses || []).forEach(function (status) {
                        var opt = document.createElement('option');
                        opt.value = status;
                        opt.textContent = FD_ORDER_STATUS[status] ? FD_ORDER_STATUS[status][0] : status;
                        filter.appendChild(opt);
                    });
                    filter.value = savedFilter;
                }

                var orders = res.data.orders || [];
                var pending = orders.filter(function (o) { return o.status === 'pending'; }).length;

                fdMyOrdersAll = orders;
                var groups = fdMyOrdersSplit();

                var summary = document.getElementById('fd-myorders-summary');
                if (summary) {
                    summary.textContent = (orders.length
                        ? groups.today.length + ' de hoy · ' + groups.history.length + ' en historial'
                            + (pending ? ' · ' + pending + ' pendientes' : '')
                        : 'Tus pedidos y su estado')
                        + ' · actualizado ' + fdClock();
                }

                if (!orders.length) {
                    fdMyOrdersLastHtml = '';
                    fdMyOrdersPaintTabs(groups);
                    fdSetMyOrdersState('message', savedFilter
                        ? 'No hay pedidos con ese estado.'
                        : 'Todavía no hiciste pedidos.\nEntrá a Comercios y armá tu primer pedido.');
                    return;
                }

                fdMyOrdersRender(silent);
            })
            .catch(function () {
                if (silent) return;
                fdSetMyOrdersState('message', 'No se pudo conectar con el servidor.');
            });
    }

    function fdMyOrdersIsToday(o) {
        var d = new Date(String((o && o.created_at) || '').replace(' ', 'T'));
        if (isNaN(d.getTime())) return false;

        var now = new Date();
        return d.getFullYear() === now.getFullYear()
            && d.getMonth() === now.getMonth()
            && d.getDate() === now.getDate();
    }

    function fdMyOrdersSplit() {
        var groups = { today: [], history: [] };
        fdMyOrdersAll.forEach(function (o) {
            groups[fdMyOrdersIsToday(o) ? 'today' : 'history'].push(o);
        });
        return groups;
    }

    function fdMyOrdersPaintTabs(groups) {
        var keys = ['today', 'history'];
        keys.forEach(function (key) {
            var count = groups[key].length;

            var badge = document.getElementById('fd-myorders-count-' + key);
            if (badge) {
                badge.textContent = count;
                badge.style.display = count ? 'inline-flex' : 'none';
            }

            var btn = document.getElementById('fd-myorders-tab-' + key);
            if (btn) {
                var on = fdMyOrdersActive === key;
                btn.style.background = on ? '#fff1eb' : '#fff';
                btn.style.color = on ? '#D24C19' : '#6b7280';
                btn.style.border = on ? '1px solid #D24C19' : '1px solid #e5e7eb';
            }
        });

        var range = document.getElementById('fd-myorders-range');
        if (range) range.style.display = fdMyOrdersActive === 'history' ? 'flex' : 'none';
    }

    function fdMyOrdersClearRange() {
        var from = document.getElementById('fd-myorders-date-from');
        var to = document.getElementById('fd-myorders-date-to');
        if (from) from.value = '';
        if (to) to.value = '';
        fdMyOrdersHistoryPage = 1;
        fdMyOrdersLastHtml = '';
        fdMyOrdersRender(false);
    }

    function fdMyOrdersRangeChange() {
        fdMyOrdersHistoryPage = 1;
        fdMyOrdersLastHtml = '';
        fdMyOrdersRender(false);
    }

    function fdMyOrdersTab(tab) {
        fdMyOrdersActive = tab === 'history' ? 'history' : 'today';
        fdMyOrdersHistoryPage = 1;
        fdMyOrdersLastHtml = '';
        fdMyOrdersRender(false);
    }

    function fdMyOrdersGoPage(page) {
        fdMyOrdersHistoryPage = Number(page) || 1;
        fdMyOrdersLastHtml = '';
        fdMyOrdersRender(false);

        var listEl = document.getElementById('fd-myorders-list');
        if (listEl && typeof listEl.scrollIntoView === 'function') {
            try { listEl.scrollIntoView({ block: 'start' }); } catch (e) {}
        }
    }

    function fdMyOrdersPaintPagination(totalPages) {
        var pag = document.getElementById('fd-myorders-pagination');
        if (!pag) return;

        if (fdMyOrdersActive !== 'history' || totalPages <= 1) {
            pag.style.display = 'none';
            pag.innerHTML = '';
            return;
        }

        var page = fdMyOrdersHistoryPage;
        var iconLeft = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>';
        var iconRight = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>';

        pag.style.display = 'flex';
        pag.innerHTML = '<button type="button" onclick="fdMyOrdersGoPage(' + (page - 1) + ')" title="Página anterior" aria-label="Página anterior" ' + (page <= 1 ? 'disabled ' : '')
            + 'style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:6px 8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;'
            + (page <= 1 ? 'opacity:.45;cursor:not-allowed;' : '') + '">' + iconLeft + '</button>'
            + '<span style="font-size:12px;color:#374151;font-weight:600;">Página ' + page + ' de ' + totalPages + '</span>'
            + '<button type="button" onclick="fdMyOrdersGoPage(' + (page + 1) + ')" title="Página siguiente" aria-label="Página siguiente" ' + (page >= totalPages ? 'disabled ' : '')
            + 'style="background:#fff;color:#D24C19;border:1px solid #D24C19;padding:6px 8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;'
            + (page >= totalPages ? 'opacity:.45;cursor:not-allowed;' : '') + '">' + iconRight + '</button>';
    }

    function fdMyOrdersRender(silent) {
        var groups = fdMyOrdersSplit();
        fdMyOrdersPaintTabs(groups);

        var list = fdMyOrdersActive === 'today' ? groups.today : groups.history;
        var rangeActive = false;

        if (fdMyOrdersActive === 'history') {
            var fromEl = document.getElementById('fd-myorders-date-from');
            var toEl = document.getElementById('fd-myorders-date-to');
            var from = fromEl ? fromEl.value : '';
            var to = toEl ? toEl.value : '';

            if (from || to) {
                rangeActive = true;
                list = list.filter(function (o) {
                    var day = String((o && o.created_at) || '').slice(0, 10);
                    if (!day) return false;
                    return (!from || day >= from) && (!to || day <= to);
                });
            }
        }

        if (!list.length) {
            fdMyOrdersLastHtml = '';
            fdMyOrdersPaintPagination(0);

            var filterEl = document.getElementById('fd-myorders-filter');
            var text;
            if (rangeActive) {
                text = 'No hay pedidos en ese rango de fechas.';
            } else if (filterEl && filterEl.value) {
                text = 'No hay pedidos con ese estado en esta sección.';
            } else if (!fdMyOrdersAll.length) {
                text = 'Todavía no hiciste pedidos.\nEntrá a Comercios y armá tu primer pedido.';
            } else if (fdMyOrdersActive === 'today') {
                text = 'No tenés pedidos hoy.\nPasá por la pestaña de historial para ver los anteriores.';
            } else {
                text = 'Todavía no hay pedidos en el historial.';
            }

            fdSetMyOrdersState('message', text);
            return;
        }

        // Historial: se muestran 3 pedidos por página.
        var totalPages = 1;
        var pageList = list;

        if (fdMyOrdersActive === 'history') {
            totalPages = Math.max(1, Math.ceil(list.length / FD_MYORDERS_PER_PAGE));
            if (fdMyOrdersHistoryPage > totalPages) fdMyOrdersHistoryPage = totalPages;
            if (fdMyOrdersHistoryPage < 1) fdMyOrdersHistoryPage = 1;
            pageList = list.slice(
                (fdMyOrdersHistoryPage - 1) * FD_MYORDERS_PER_PAGE,
                fdMyOrdersHistoryPage * FD_MYORDERS_PER_PAGE
            );
        }

        var html = pageList.map(fdRenderMyOrder).join('');
        var listEl = document.getElementById('fd-myorders-list');

        if (silent && html === fdMyOrdersLastHtml && listEl.style.display === 'block') return;

        fdMyOrdersLastHtml = html;
        listEl.innerHTML = html;
        fdSetMyOrdersState('content');
        fdMyOrdersPaintPagination(totalPages);
    }

    var FD_TRACK_STEPS = [
        ['pending', 'Recibido'],
        ['confirmed', 'Confirmado'],
        ['in_preparation', 'En preparación'],
        ['on_the_way', 'En camino'],
        ['delivered', 'Entregado']
    ];

    var FD_TRACK_ICONS = [
        // Recibido: cliente
        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/><path d="M4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>',
        // Confirmado: reloj
        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        // En preparación: fuego de cocina
        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.6a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z"/><path d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z"/></svg>',
        // En camino: camión
        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        // Entregado: casa
        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>'
    ];

    function fdTrackHtml(status) {
        if (status === 'cancelled') {
            return '<div style="background:#fef2f2;border-bottom:1px solid #fecaca;padding:12px 16px;font-size:13px;color:#b91c1c;font-weight:700;">Pedido cancelado</div>';
        }

        var current = -1;
        FD_TRACK_STEPS.forEach(function (step, i) { if (step[0] === status) current = i; });
        if (current < 0) return '';

        var html = '<div data-track="' + status + '" style="display:flex;align-items:flex-start;padding:16px 16px 8px;overflow-x:auto;">';

        FD_TRACK_STEPS.forEach(function (step, i) {
            var done = i < current;
            var active = i === current;
            var passed = done || active;
            var circleBg = passed ? '#D24C19' : '#e2e8f0';
            var circleBorder = passed ? '#D24C19' : '#e2e8f0';
            var iconColor = passed ? '#fff' : '#64748b';
            var labelColor = active ? '#D24C19' : (done ? '#334155' : '#94a3b8');
            var ring = active ? 'box-shadow:0 0 0 4px #ffe9df;' : '';

            html += '<div style="display:flex;flex-direction:column;align-items:center;width:72px;flex:none;">'
                + '<div style="height:14px;line-height:14px;font-size:9px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#D24C19;">'
                + (active ? 'Ahora' : '') + '</div>'
                + '<div style="width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:' + circleBg + ';border:1px solid ' + circleBorder + ';' + ring + '">'
                + '<span style="display:flex;line-height:0;color:' + iconColor + ';">' + FD_TRACK_ICONS[i] + '</span>'
                + '</div>'
                + '<div style="margin-top:7px;font-size:10px;line-height:1.25;text-align:center;color:' + labelColor + ';font-weight:' + (active ? '700' : (done ? '600' : '500')) + ';">'
                + escapeHtml(step[1]) + '</div>'
                + '</div>';

            if (i < FD_TRACK_STEPS.length - 1) {
                var lineColor = i <= current ? '#D24C19' : '#e2e8f0';
                html += '<div style="flex:1;min-width:14px;height:3px;border-radius:2px;background:' + lineColor + ';margin-top:28px;"></div>';
            }
        });

        return html + '</div>';
    }

    function fdRenderMyOrder(o) {
        var shop = o.provider || {};
        var address = o.address;

        var html = '<div data-order-id="' + Number(o.id) + '" style="background:#fff;border:1px solid #e5e7eb;margin-bottom:14px;overflow:hidden;">'
            + '<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:11px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb;">'
            + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
            + '<span style="font-size:13px;font-weight:700;color:#0c2a4d;font-family:ui-monospace,monospace;">' + escapeHtml(o.order_number) + '</span>'
            + fdStatusBadge(o.status)
            + fdPaymentBadge(o.payments)
            + '</div>'
            + '<span style="font-size:11px;color:#94a3b8;">' + fdDate(o.created_at) + '</span>'
            + '</div>';

        html += fdTrackHtml(o.status);

        html += '<div style="padding:14px 16px;">'
            + '<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:13px;">'
            + '<div style="min-width:0;">'
            + '<b>' + escapeHtml(shop.business_name || 'Comercio') + '</b>'
            + (shop.is_active === false ? ' <span style="color:#94a3b8;font-size:11px;">(comercio pausado)</span>' : '')
            + (address
                ? '<div style="color:#64748b;margin-top:4px;font-size:12px;">'
                    + escapeHtml(address.street) + ' ' + escapeHtml(address.number || '')
                    + (address.floor_apartment ? ', ' + escapeHtml(address.floor_apartment) : '')
                    + (address.postal_code ? ' · CP ' + escapeHtml(address.postal_code) : '')
                    + '</div>'
                : '<div style="color:#94a3b8;margin-top:4px;font-size:12px;">Sin dirección registrada</div>')
            + '</div>'
            + '<div style="text-align:right;font-size:12px;color:#64748b;white-space:nowrap;">'
            + 'Pago: ' + escapeHtml(FD_PAYMENT_METHOD_LABELS[o.payment_method] || o.payment_method || '-')
            + '</div>'
            + '</div>';

        if (o.notes) {
            html += '<div style="margin-top:4px;font-size:12px;color:#9a3412;">Nota: ' + escapeHtml(o.notes) + '</div>';
        }

        html += '<div style="border-top:1px solid #f1f5f9;margin-top:10px;padding-top:10px;">'
            + (o.items || []).map(fdRenderOrderItem).join('')
            + '</div>'
            + '<div style="border-top:1px solid #e5e7eb;margin-top:10px;padding-top:10px;font-size:13px;">'
            + fdTotalRow('Subtotal', fdMoney(o.subtotal), false)
            + fdTotalRow('Envío', fdMoney(o.delivery_fee), false)
            + fdTotalRow('Descuento', '-' + fdMoney(o.discount), false)
            + '<div style="display:flex;justify-content:space-between;font-size:15px;font-weight:700;color:#0c2a4d;margin-top:6px;">'
            + '<span>Total</span><span>' + fdMoney(o.total) + '</span></div>'
            + '</div>'
            + '</div>'
            + '</div>';

        return html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ============ SEGUIMIENTO EN VIVO ============
    // Cada seccion de pedidos se refresca sola mientras este a la vista, para
    // que el estado del pedido cambie sin recargar la pagina.

    var FD_POLL_SECTIONS = {
        'mis-pedidos': function () { loadMyOrders(true); },
        'pedidos': function () { loadProviderOrders(true); }
    };
    var FD_POLL_MS = 8000;
    var fdPollTimer = null;
    var fdPollKey = null;

    function fdStopPolling() {
        if (fdPollTimer) clearInterval(fdPollTimer);
        fdPollTimer = null;
        fdPollKey = null;
    }

    function fdStartPolling(key) {
        if (fdPollKey === key) return;
        fdStopPolling();
        if (!FD_POLL_SECTIONS[key]) return;

        fdPollKey = key;
        fdPollTimer = setInterval(function () {
            if (document.hidden) return;
            var section = document.getElementById('dash-' + fdPollKey);
            if (!section || section.style.display === 'none') { fdStopPolling(); return; }
            FD_POLL_SECTIONS[fdPollKey]();
        }, FD_POLL_MS);
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && fdPollKey && FD_POLL_SECTIONS[fdPollKey]) FD_POLL_SECTIONS[fdPollKey]();
    });

    document.addEventListener('DOMContentLoaded', function() {
        var origShow = showDashSection;
        showDashSection = function(key) {
            origShow(key);
            if (key === 'modulos') loadModules();
            if (key === 'grupos') loadGroups();
            if (key === 'sub-grupos') loadSubGroups();
            if (key === 'estados-usuarios') loadUserStatuses();
            if (key === 'estados-grupos') loadGroupStatuses();
            if (key === 'unidades-medida') loadUnitsOfMeasure();
            if (key === 'tipo-usuarios') loadTypeUsers();
            if (key === 'usuarios') { loadUsers(); }
            if (key === 'paginas') loadPages();
            if (key === 'perfil') loadProfile();
            if (key === 'mi-catalogo') fdCatShow();
            if (key === 'pedidos') loadProviderOrders();
            if (key === 'inventario') loadInventory();
            if (key === 'comercios') loadComercios();
            if (key === 'mis-pedidos') loadMyOrders();
            if (key === 'finanzas') loadFinances();
            if (key === 'retencion') loadRetention();
            if (key === 'suscripciones') loadSubRevenue();
            if (key === 'mis-suscripciones') {
                // Al entrar por el nav el combo de comercios vuelve a quedar
                // habilitado; si el origen es la tarjeta del comercio,
                // openSubNewForm() lo fija recien despues de esta llamada.
                var subProvider = document.getElementById('mysub-provider');
                if (subProvider) {
                    subProvider.disabled = false;
                    subProvider.title = '';
                }
                loadMySubscriptions();
            }
            fdStartPolling(key);
        };

        document.querySelectorAll('.dash-section').forEach(function (section) {
            var key = section.id.indexOf('dash-') === 0 ? section.id.slice(5) : '';
            if (section.style.display === 'none' || !FD_POLL_SECTIONS[key]) return;
            fdStartPolling(key);
            FD_POLL_SECTIONS[key]();
        });

        // Al entrar al dashboard se muestra una seccion por defecto: sin esto
        // todas las secciones quedan ocultas y la pantalla aparecia en blanco.
        showDashSection('perfil');

        @if (strcasecmp((string) $user->typeUser?->description, 'Prestador') === 0)
            // Prestador que todavía no eligió categorías: no deja avanzar hasta configurarlas.
            checkCategoryOnboarding();
        @endif
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
            fillPageModuleFilter();
            if (callback) callback();
        })
        .catch(function() {
            if (callback) callback();
        });
    }

    function fillPageModuleFilter() {
        var select = document.getElementById('page-module-filter');
        if (!select) return;

        var current = select.value;
        select.innerHTML = '<option value="">Todos los módulos</option>';
        allPageModules.forEach(function(m) {
            var opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.description;
            select.appendChild(opt);
        });

        select.value = current || '';
        if (select.selectedIndex === -1) select.value = '';
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
            if (allPageModules.length === 0) {
                loadPageModules();
            } else {
                fillPageModuleFilter();
            }
            filterPages();
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
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(p.description) + '</td>' +
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
        var moduleId = document.getElementById('page-module-filter').value;
        var filtered = allPages.filter(function(p) {
            var matchesText = !q || (p.description && p.description.toLowerCase().indexOf(q) !== -1);
            var matchesModule = !moduleId || String(p.module_id || '') === moduleId;
            return matchesText && matchesModule;
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

    var allUsers = [];
    var deleteUserId = null;
    var selectedUser = null;
    var authEmail = @json($user->email);
    var authUserId = @json($user->id);
    var userSelectScope = 'all';
    var userCurrentPage = 1;
    var userPerPage = 10;
    var allUserStatuses = [];
    var deleteUserStatusId = null;

    function loadUsers() {
        userCurrentPage = 1;
        document.getElementById('users-loading').style.display = 'block';
        document.getElementById('users-empty').style.display = 'none';
        document.getElementById('users-table-wrap').style.display = 'none';
        document.getElementById('users-pagination').style.display = 'none';

        return fetch('{{ url("/admin/users") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allUsers = data;
            loadUserStatusOptions();
            loadTypeUserOptions();
            filterUsers();
            updateCreateUserBtnVisibility();
            if (selectedUser) {
                var fresh = allUsers.find(function(x) { return String(x.id) === String(selectedUser.id); });
                if (fresh) {
                    if ((!fresh.addresses || !fresh.addresses.length) && selectedUser.addresses && selectedUser.addresses.length) {
                        fresh.addresses = selectedUser.addresses;
                    }
                    if ((!fresh.primary_address) && selectedUser.primary_address) {
                        fresh.primary_address = selectedUser.primary_address;
                    }
                    if (!fresh.provider && selectedUser.provider) {
                        fresh.provider = selectedUser.provider;
                    } else if (fresh.provider && selectedUser.provider && selectedUser.provider.images && (!fresh.provider.images || !fresh.provider.images.length)) {
                        fresh.provider.images = selectedUser.provider.images;
                    }
                    selectedUser = fresh;
                    var sIdx = allUsers.findIndex(function(x) { return String(x.id) === String(fresh.id); });
                    if (sIdx !== -1) allUsers[sIdx] = fresh;
                    renderUserDemographic(fresh);
                    renderUserAccount(fresh);
                    loadUserCategorias();
                } else {
                    clearUserDemographic();
                }
            }
        })
        .catch(function() {
            document.getElementById('users-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar usuarios.</p>';
        });
    }

    function getActiveAccountEmail() {
        if (selectedUser && selectedUser.email) return String(selectedUser.email).toLowerCase();
        if (authEmail) return String(authEmail).toLowerCase();
        return '';
    }

    function getAccountUsers() {
        var email = getActiveAccountEmail();
        if (!email) return allUsers;
        return allUsers.filter(function(u) {
            return u.email && String(u.email).toLowerCase() === email;
        });
    }

    function getSelectableUsers() {
        return userSelectScope === 'account' ? getAccountUsers() : allUsers;
    }

    function openUserSelectModal(scope) {
        userSelectScope = (scope === 'account') ? 'account' : 'all';
        document.getElementById('user-select-overlay').style.display = 'flex';
        var search = document.getElementById('user-search');
        search.value = '';
        var statusFilter = document.getElementById('user-filter-status');
        var typeFilter = document.getElementById('user-filter-type');
        if (statusFilter) statusFilter.value = '';
        if (typeFilter) typeFilter.value = '';
        loadUserStatusOptions();
        loadTypeUserOptions();
        if (!allUsers.length) {
            loadUsers();
        } else {
            userCurrentPage = 1;
            var scoped = getSelectableUsers();
            allUsers._filtered = scoped;
            renderUsers(scoped);
        }
        setTimeout(function() { search.focus(); }, 50);
    }

    function closeUserSelectModal() {
        document.getElementById('user-select-overlay').style.display = 'none';
    }

    var typeUserPagesTypes = [];
    var typeUserPagesAll = [];
    var typeUserPagesAssigned = [];
    var typeUserPagesModules = [];

    function openTypeUserPagesModal() {
        document.getElementById('typeuser-pages-overlay').style.display = 'flex';
        var msg = document.getElementById('typeuser-pages-msg');
        if (msg) { msg.style.display = 'none'; msg.textContent = ''; msg.classList.remove('ok', 'err'); }
        var select = document.getElementById('typeuser-pages-select');
        var moduleSelect = document.getElementById('typeuser-pages-module');
        var empty = document.getElementById('typeuser-pages-empty');
        var list = document.getElementById('typeuser-pages-list');
        var tbody = document.getElementById('typeuser-pages-tbody');
        var gridEmpty = document.getElementById('typeuser-pages-grid-empty');
        var loading = document.getElementById('typeuser-pages-loading');
        var hint = document.getElementById('typeuser-pages-hint');
        if (empty) empty.style.display = 'block';
        if (list) list.style.display = 'none';
        if (tbody) tbody.innerHTML = '';
        if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }
        if (loading) loading.style.display = 'none';
        if (hint) hint.style.display = 'none';
        if (select) select.value = '';
        if (moduleSelect) moduleSelect.value = '';
        loadTypeUserPagesData();
    }

    function closeTypeUserPagesModal() {
        document.getElementById('typeuser-pages-overlay').style.display = 'none';
    }

    function loadTypeUserPagesData() {
        var loading = document.getElementById('typeuser-pages-loading');
        var empty = document.getElementById('typeuser-pages-empty');
        var select = document.getElementById('typeuser-pages-select');
        var moduleSelect = document.getElementById('typeuser-pages-module');
        if (loading) loading.style.display = 'block';
        if (empty) empty.style.display = 'none';

        Promise.all([
            fetch('{{ url("/type-users/pages-data") }}', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(function(r) { return r.json(); }),
            fetch('{{ url("/modules") }}', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(function(r) { return r.json(); })
        ])
        .then(function(results) {
            if (loading) loading.style.display = 'none';
            var data = results[0] || {};
            var modules = results[1] || [];
            typeUserPagesTypes = data.types || [];
            typeUserPagesAll = data.pages || [];
            typeUserPagesModules = Array.isArray(modules) ? modules : [];
            if (select) {
                select.innerHTML = '<option value="">-- Seleccioná un tipo --</option>' +
                    typeUserPagesTypes.map(function(t) {
                        return '<option value="' + t.id + '">' + escapeHtml(t.description) + ' (' + (t.users_count || 0) + ' usuarios)</option>';
                    }).join('');
            }
            if (moduleSelect) {
                moduleSelect.innerHTML = '<option value="">-- Todos los módulos --</option>' +
                    typeUserPagesModules.map(function(m) {
                        return '<option value="' + m.id + '">' + escapeHtml(m.description || m.name || ('Módulo ' + m.id)) + '</option>';
                    }).join('');
            }
            if (!typeUserPagesTypes.length) {
                if (empty) {
                    empty.textContent = 'No hay tipos de usuario disponibles.';
                    empty.style.display = 'block';
                }
            } else {
                if (empty) empty.style.display = 'block';
            }
        })
        .catch(function() {
            if (loading) loading.style.display = 'none';
            if (empty) {
                empty.textContent = 'Error al cargar los tipos de usuario.';
                empty.style.display = 'block';
            }
        });
    }

    function loadTypeUserPagesForType() {
        var select = document.getElementById('typeuser-pages-select');
        var typeId = select ? parseInt(select.value, 10) : 0;
        var empty = document.getElementById('typeuser-pages-empty');
        var list = document.getElementById('typeuser-pages-list');
        var tbody = document.getElementById('typeuser-pages-tbody');
        var gridEmpty = document.getElementById('typeuser-pages-grid-empty');
        var hint = document.getElementById('typeuser-pages-hint');
        var msg = document.getElementById('typeuser-pages-msg');
        if (msg) { msg.style.display = 'none'; msg.textContent = ''; msg.classList.remove('ok', 'err'); }

        if (!typeId) {
            if (list) list.style.display = 'none';
            if (tbody) tbody.innerHTML = '';
            if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }
            if (empty) {
                empty.textContent = 'Seleccioná un tipo de usuario para gestionar sus páginas.';
                empty.style.display = 'block';
            }
            if (hint) hint.style.display = 'none';
            typeUserPagesAssigned = [];
            return;
        }

        var type = typeUserPagesTypes.find(function(t) { return t.id === typeId; });
        typeUserPagesAssigned = ((type && type.assigned_page_ids) || []).slice();
        if (hint && type) {
            hint.textContent = type.users_count + ' usuario(s) con este tipo. Al guardar, las páginas seleccionadas se aplican a todos ellos.';
            hint.style.display = 'block';
        }
        if (empty) empty.style.display = 'none';
        renderTypeUserPagesList();
    }

    function renderTypeUserPagesList() {
        var list = document.getElementById('typeuser-pages-list');
        var tbody = document.getElementById('typeuser-pages-tbody');
        var gridEmpty = document.getElementById('typeuser-pages-grid-empty');
        var empty = document.getElementById('typeuser-pages-empty');
        var typeSelect = document.getElementById('typeuser-pages-select');
        var moduleSelect = document.getElementById('typeuser-pages-module');
        if (!list || !tbody) return;

        var typeId = typeSelect ? parseInt(typeSelect.value, 10) : 0;
        var moduleFilter = moduleSelect ? moduleSelect.value : '';
        if (!typeId) {
            list.style.display = 'none';
            tbody.innerHTML = '';
            if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }
            if (empty) {
                empty.textContent = 'Seleccioná un tipo de usuario para gestionar sus páginas.';
                empty.style.display = 'block';
            }
            return;
        }

        if (!moduleFilter) {
            list.style.display = 'none';
            tbody.innerHTML = '';
            if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }
            if (empty) {
                empty.textContent = 'Seleccioná un módulo para ver las páginas.';
                empty.style.display = 'block';
            }
            return;
        }

        if (empty) empty.style.display = 'none';

        var mid = parseInt(moduleFilter, 10);
        var pages = typeUserPagesAll.filter(function(pg) {
            return pg.module_id === mid;
        });

        if (!pages.length) {
            tbody.innerHTML = '';
            list.style.display = 'block';
            if (gridEmpty) {
                gridEmpty.textContent = 'No hay páginas disponibles para este módulo.';
                gridEmpty.style.display = 'block';
            }
            return;
        }

        if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }

        var assigned = {};
        typeUserPagesAssigned.forEach(function(id) { assigned[id] = true; });

        tbody.innerHTML = pages.map(function(pg, idx) {
            var checked = !!assigned[pg.id];
            var bg = idx % 2 === 0 ? '#fff' : '#f8fafc';
            var meta = [];
            if (pg.module) meta.push(escapeHtml(pg.module));
            if (pg.url) meta.push(escapeHtml(pg.url));
            return '<tr style="background:' + bg + ';border-bottom:1px solid #e5e7eb;">' +
                '<td style="padding:10px 16px;font-size:13px;color:#0f172a;vertical-align:middle;">' +
                    '<div style="font-weight:600;">' + escapeHtml(pg.description || '-') + '</div>' +
                    (meta.length ? '<div style="font-size:11px;color:#64748b;margin-top:2px;">' + meta.join(' · ') + '</div>' : '') +
                '</td>' +
                '<td style="padding:10px 16px;text-align:center;vertical-align:middle;">' +
                    '<input type="checkbox" class="typeuser-pages-check" value="' + pg.id + '"' + (checked ? ' checked' : '') + ' style="width:16px;height:16px;accent-color:#D24C19;cursor:pointer;" title="Asociar o quitar página" onchange="toggleTypeUserPage(' + pg.id + ', this.checked)" />' +
                    '<div style="font-size:10px;color:#64748b;margin-top:2px;">' + (checked ? 'Agregada' : 'Agregar') + '</div>' +
                '</td>' +
            '</tr>';
        }).join('');
        list.style.display = 'block';
        updateTypeUserPagesCheckAll();
    }

    function getVisibleTypeUserPageIds() {
        var moduleSelect = document.getElementById('typeuser-pages-module');
        var moduleFilter = moduleSelect ? moduleSelect.value : '';
        if (!moduleFilter) return [];
        var mid = parseInt(moduleFilter, 10);
        return typeUserPagesAll
            .filter(function(pg) { return pg.module_id === mid; })
            .map(function(pg) { return pg.id; });
    }

    function updateTypeUserPagesCheckAll() {
        var header = document.getElementById('typeuser-pages-check-all');
        if (!header) return;
        var list = document.getElementById('typeuser-pages-list');
        if (!list || list.style.display === 'none') {
            header.checked = false;
            header.indeterminate = false;
            header.disabled = false;
            return;
        }
        var visibleIds = getVisibleTypeUserPageIds();
        if (!visibleIds.length) {
            header.checked = false;
            header.indeterminate = false;
            return;
        }
        var onCount = 0;
        visibleIds.forEach(function(id) {
            if (typeUserPagesAssigned.indexOf(id) !== -1) onCount++;
        });
        header.disabled = false;
        header.checked = onCount === visibleIds.length;
        header.indeterminate = onCount > 0 && onCount < visibleIds.length;
    }

    function toggleTypeUserPagesAll(checked) {
        var typeSelect = document.getElementById('typeuser-pages-select');
        var typeId = typeSelect ? parseInt(typeSelect.value, 10) : 0;
        var msg = document.getElementById('typeuser-pages-msg');
        var header = document.getElementById('typeuser-pages-check-all');
        var visibleIds = getVisibleTypeUserPageIds();

        function showMsg(text, ok) {
            if (!msg) return;
            msg.textContent = text;
            msg.style.display = 'block';
            msg.className = 'save-msg ' + (ok ? 'ok' : 'err');
        }

        if (!typeId) {
            if (header) header.checked = false;
            showMsg('Seleccioná un tipo de usuario.', false);
            return;
        }

        if (!visibleIds.length) {
            if (header) header.checked = false;
            showMsg('No hay páginas visibles para actualizar.', false);
            return;
        }

        var prevAssigned = typeUserPagesAssigned.slice();
        var prevHeader = header ? { checked: header.checked, indeterminate: header.indeterminate } : null;

        if (checked) {
            visibleIds.forEach(function(id) {
                if (typeUserPagesAssigned.indexOf(id) === -1) typeUserPagesAssigned.push(id);
            });
        } else {
            typeUserPagesAssigned = typeUserPagesAssigned.filter(function(id) {
                return visibleIds.indexOf(id) === -1;
            });
        }

        if (header) {
            header.checked = checked;
            header.indeterminate = false;
        }
        renderTypeUserPagesList();

        if (header) {
            header.checked = checked;
            header.indeterminate = false;
        }

        fetch('{{ url("/type-users") }}/' + typeId + '/pages/bulk', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                action: checked ? 'attach' : 'detach',
                page_ids: visibleIds
            })
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(res) {
            if (!res.ok || !res.data.success) {
                typeUserPagesAssigned = prevAssigned;
                if (header && prevHeader) {
                    header.checked = prevHeader.checked;
                    header.indeterminate = prevHeader.indeterminate;
                }
                renderTypeUserPagesList();
                showMsg((res.data && res.data.message) || 'Error al actualizar las páginas.', false);
                return;
            }
            var type = typeUserPagesTypes.find(function(t) { return t.id === typeId; });
            if (type) type.assigned_page_ids = typeUserPagesAssigned.slice();
            showMsg(res.data.message || (checked ? 'Páginas agregadas.' : 'Páginas quitadas.'), true);
            updateTypeUserPagesCheckAll();
            if (selectedUser && selectedUser.type_user_id === typeId) {
                refreshSelectedUser();
            }
        })
        .catch(function() {
            typeUserPagesAssigned = prevAssigned;
            if (header && prevHeader) {
                header.checked = prevHeader.checked;
                header.indeterminate = prevHeader.indeterminate;
            }
            renderTypeUserPagesList();
            showMsg('Error de conexión.', false);
        });
    }

    function toggleTypeUserPage(pageId, checked) {
        var typeSelect = document.getElementById('typeuser-pages-select');
        var typeId = typeSelect ? parseInt(typeSelect.value, 10) : 0;
        var msg = document.getElementById('typeuser-pages-msg');
        var checkbox = document.querySelector('.typeuser-pages-check[value="' + pageId + '"]');

        function showMsg(text, ok) {
            if (!msg) return;
            msg.textContent = text;
            msg.style.display = 'block';
            msg.className = 'save-msg ' + (ok ? 'ok' : 'err');
        }

        function setLabel(isOn) {
            if (!checkbox) return;
            var label = checkbox.nextElementSibling;
            if (label) label.textContent = isOn ? 'Agregada' : 'Agregar';
        }

        function revert() {
            if (checkbox) checkbox.checked = !checked;
            var idx = typeUserPagesAssigned.indexOf(pageId);
            if (checked) {
                if (idx !== -1) typeUserPagesAssigned.splice(idx, 1);
            } else if (idx === -1) {
                typeUserPagesAssigned.push(pageId);
            }
            setLabel(typeUserPagesAssigned.indexOf(pageId) !== -1);
        }

        if (!typeId) {
            if (checkbox) checkbox.checked = !checked;
            showMsg('Seleccioná un tipo de usuario.', false);
            return;
        }

        if (checked) {
            if (typeUserPagesAssigned.indexOf(pageId) === -1) typeUserPagesAssigned.push(pageId);
        } else {
            var i = typeUserPagesAssigned.indexOf(pageId);
            if (i !== -1) typeUserPagesAssigned.splice(i, 1);
        }
        setLabel(checked);

        var method = checked ? 'POST' : 'DELETE';
        fetch('{{ url("/type-users") }}/' + typeId + '/pages/' + pageId, {
            method: method,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
        .then(function(res) {
            if (!res.ok || !res.data.success) {
                revert();
                showMsg(res.data.message || 'Error al actualizar la página.', false);
                updateTypeUserPagesCheckAll();
                return;
            }
            var type = typeUserPagesTypes.find(function(t) { return t.id === typeId; });
            if (type) type.assigned_page_ids = typeUserPagesAssigned.slice();
            showMsg(res.data.message || (checked ? 'Página agregada.' : 'Página quitada.'), true);
            updateTypeUserPagesCheckAll();
            if (selectedUser && selectedUser.type_user_id === typeId) {
                refreshSelectedUser();
            }
        })
        .catch(function() {
            revert();
            showMsg('Error de conexión.', false);
            updateTypeUserPagesCheckAll();
        });
    }

    function selectUser(id) {
        var u = allUsers.find(function(x) { return String(x.id) === String(id); });
        if (!u) return;
        if (isInactiveUserStatus(u)) {
            if (!confirm('\u00bfEl usuario ' + (u.name || '') + ' est\u00e1 inactivo. \u00bfQuer\u00e9s activarlo?')) {
                return;
            }
            activateInactiveUser(u);
        }
        if (userSelectScope !== 'account') {
            var selEmail = u.email ? String(u.email).toLowerCase() : '';
            if (selEmail) {
                var principal = allUsers.filter(function(x) {
                    return x.email && String(x.email).toLowerCase() === selEmail;
                }).sort(function(a, b) { return Number(a.id) - Number(b.id); })[0];
                if (principal) u = principal;
            }
            selectedUser = u;
            switchUserTab('demografia');
        } else {
            selectedUser = u;
            switchUserTab('cuenta');
            switchUserAccountTab('gestion');
        }
        renderUserDemographic(u);
        renderUserAccount(u);
        loadUserCategorias();
        closeUserSelectModal();
        refreshSelectedUser();
    }

    function refreshSelectedUser() {
        if (!selectedUser) return Promise.resolve();
        var id = selectedUser.id;
        return fetch('{{ url("/admin/users") }}/' + id, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(fresh) {
            if (!fresh || fresh.error) return;
            if (!selectedUser || String(selectedUser.id) !== String(fresh.id)) return;
            selectedUser = fresh;
            var idx = allUsers.findIndex(function(x) { return String(x.id) === String(fresh.id); });
            if (idx !== -1) allUsers[idx] = fresh;
            allUsers._filtered = getAccountUsers();
            renderUserDemographic(fresh);
            renderUserAccount(fresh);
            loadUserCategorias();
        })
        .catch(function() {});
    }

    function applyUserUpdate(user) {
        if (!user || !selectedUser) return;
        var idx = allUsers.findIndex(function(x) { return String(x.id) === String(user.id); });
        if (idx !== -1) allUsers[idx] = user;
        var sameAccount = String(selectedUser.id) === String(user.id) ||
            (selectedUser.email && user.email && String(selectedUser.email).toLowerCase() === String(user.email).toLowerCase());
        if (String(selectedUser.id) === String(user.id)) {
            selectedUser = user;
        }
        allUsers._filtered = getAccountUsers();
        if (!sameAccount) return;
        renderUserDemographic(selectedUser);
        renderUserAccount(selectedUser);
        loadUserCategorias();
        if (currentUserPanelTab() === 'config') loadUserAccountConfig();
    }

    function currentUserPanelTab() {
        var active = document.querySelector('#user-sidebar .sidebar-link[data-user-panel].active');
        return active ? active.getAttribute('data-user-panel') : 'demografia';
    }

    var userPanelLabels = {
        demografia: 'Demografía',
        direcciones: 'Direccion',
        negocio: 'Negocio',
        categorias: 'Categorias',
        cuenta: 'Cuentas',
        imagenes: 'Publicidad',
        config: 'Contraseñas'
    };

    function switchUserTab(tab) {
        if (tab === 'imagenes' && !userCanAdvertise()) tab = 'demografia';
        document.querySelectorAll('#user-sidebar .sidebar-link[data-user-panel]').forEach(function(link) {
            link.classList.toggle('active', link.getAttribute('data-user-panel') === tab);
        });
        document.querySelectorAll('.user-panel').forEach(function(panel) {
            panel.classList.toggle('active', panel.id === 'user-panel-' + tab);
        });
        var bc = document.getElementById('user-breadcrumb-current');
        if (bc) bc.textContent = userPanelLabels[tab] || tab;
        var sb = document.getElementById('user-sidebar');
        if (sb) sb.classList.remove('open');
        var ov = document.getElementById('user-sidebar-overlay');
        if (ov) ov.style.display = 'none';
        if (tab === 'categorias') loadUserCategorias();
        if (tab === 'imagenes' && selectedUser) renderUserImages(selectedUser);
        if (tab === 'config') loadUserAccountConfig();
    }

    function toggleUserSidebar() {
        var sb = document.getElementById('user-sidebar');
        var ov = document.getElementById('user-sidebar-overlay');
        if (!sb) return;
        var isOpen = sb.classList.toggle('open');
        if (ov) ov.style.display = isOpen ? 'block' : 'none';
    }

    function renderUserAddresses(u) {
        var list = getEffectiveAddresses(u);
        if (!list.length) {
            resetUserAddressForm();
            return;
        }
        var currentId = document.getElementById('user-addr-id') ? document.getElementById('user-addr-id').value : '';
        var stillThere = currentId && list.some(function(x) { return String(x.id) === String(currentId); });
        if (!stillThere) {
            var primaryAddr = list.find(function(x) { return x.is_primary; }) || list[0];
            fillUserAddressForm(primaryAddr.id);
        }
    }

    function renderUserProvider(u) {
        var p = getEffectiveProvider(u);
        var set = function(id, val) {
            var el = document.getElementById(id);
            if (el) el.value = val || '';
        };
        set('user-prov-business', p && p.business_name);
        set('user-prov-description', p && p.description);
        set('user-prov-phone', p && p.phone);
        set('user-prov-whatsapp', p && p.whatsapp);
        set('user-prov-zone', p && p.zone);
        set('user-prov-promo', p && p.promo);
        var activeCb = document.getElementById('user-prov-active');
        if (activeCb) activeCb.checked = p ? !!p.is_active : false;
        var errBn = document.getElementById('user-prov-business-error');
        if (errBn) errBn.style.display = 'none';
        var errEl = document.getElementById('user-prov-error');
        if (errEl) errEl.style.display = 'none';
        var msg = document.getElementById('user-prov-msg');
        if (msg) { msg.style.display = 'none'; msg.textContent = ''; }
    }

    var userCategoriasReqId = 0;

    function loadUserCategorias() {
        var list = document.getElementById('user-categorias-list');
        var loading = document.getElementById('user-categorias-loading');
        if (!list || !loading) return;
        var reqId = ++userCategoriasReqId;
        if (!selectedUser) {
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '<div class="user-demo-empty-tab">Seleccioná un usuario para ver sus categorías.</div>';
            return;
        }
        var userId = selectedUser.id;
        loading.style.display = 'block';
        loading.textContent = 'Cargando categorías...';
        list.style.display = 'none';
        fetch('{{ url("/admin/users") }}/' + userId + '/subgroups', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (reqId !== userCategoriasReqId) return;
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '';
            if (data.has_provider === false) {
                list.innerHTML = '<div class="user-demo-empty-tab">Este usuario no tiene negocio. Creá el negocio para asignar categorías.</div>';
                return;
            }
            if (!data.groups || !data.groups.length) {
                list.innerHTML = '<p style="text-align:center;color:#94a3b8;font-size:12px;padding:20px;">No hay categorías disponibles.</p>';
                return;
            }
            data.groups.forEach(function(group) {
                if (!group.subgroups || !group.subgroups.length) return;
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
                    var isSelected = (data.selected || []).indexOf(sub.id) >= 0;
                    var label = document.createElement('label');
                    label.style.cssText = 'display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid ' + (isSelected ? '#D24C19' : '#e2e8f0') + ';background:' + (isSelected ? '#fff7ed' : '#fff') + ';cursor:pointer;transition:all .15s;font-size:12px;font-weight:500;color:' + (isSelected ? '#D24C19' : '#475569') + ';';
                    var cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.checked = isSelected;
                    cb.style.cssText = 'accent-color:#D24C19;width:14px;height:14px;cursor:pointer;';
                    cb.addEventListener('change', function() {
                        toggleUserSubgroup(sub.id, label, cb);
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
            if (reqId !== userCategoriasReqId) return;
            loading.style.display = 'none';
            list.style.display = 'block';
            list.innerHTML = '<div class="user-demo-empty-tab">Error al cargar categorías.</div>';
        });
    }

    function toggleUserSubgroup(subgroupId, labelEl, cb) {
        if (!selectedUser) {
            cb.checked = !cb.checked;
            return;
        }
        fetch('{{ url("/admin/users") }}/' + selectedUser.id + '/subgroups/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ subgroup_id: subgroupId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var isSelected = (data.selected || []).indexOf(subgroupId) >= 0;
                labelEl.style.borderColor = isSelected ? '#D24C19' : '#e2e8f0';
                labelEl.style.background = isSelected ? '#fff7ed' : '#fff';
                labelEl.style.color = isSelected ? '#D24C19' : '#475569';
                cb.checked = isSelected;
                showMsg('user-categorias-msg', data.message, true);
            } else {
                cb.checked = !cb.checked;
                showMsg('user-categorias-msg', data.message || 'Error al guardar.', false);
            }
        })
        .catch(function() {
            cb.checked = !cb.checked;
            showMsg('user-categorias-msg', 'Error de conexión.', false);
        });
    }

    var userAccountActiveTab = 'gestion';

    function switchUserAccountTab(tab) {
        userAccountActiveTab = (tab === 'paginas') ? 'paginas' : 'gestion';
        document.querySelectorAll('.user-account-tab').forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-account-tab') === userAccountActiveTab);
        });
        var g = document.getElementById('user-account-pane-gestion');
        var p = document.getElementById('user-account-pane-paginas');
        if (g) g.style.display = userAccountActiveTab === 'gestion' ? 'block' : 'none';
        if (p) p.style.display = userAccountActiveTab === 'paginas' ? 'block' : 'none';
    }

    function renderUserAccount(u) {
        renderUserAccountGestion(u);
        renderUserAccountPaginas(u);
        switchUserAccountTab(userAccountActiveTab);
        updateCreateUserBtnVisibility();
    }

    var USER_MAX_SECONDARIES = 3;

    function countAccountSecondaries() {
        var email = getActiveAccountEmail();
        if (!email) return 0;
        var total = allUsers.filter(function(u) {
            return u.email && String(u.email).toLowerCase() === email;
        }).length;
        return Math.max(0, total - 1);
    }

    function updateCreateUserBtnVisibility() {
        var btn = document.getElementById('user-create-btn');
        if (btn) btn.style.display = countAccountSecondaries() >= USER_MAX_SECONDARIES ? 'none' : 'flex';
    }

    function renderUserAccountGestion(u) {
        var pane = document.getElementById('user-account-pane-gestion');
        if (!pane) return;
        pane.setAttribute('data-user-id', (u && u.id != null) ? String(u.id) : '');

        var selectedName = u.name || '-';
        var isSecondary = false;
        var accEmail = (u.email || '').toLowerCase();
        if (accEmail) {
            var principal = allUsers.filter(function(x) {
                return x.email && String(x.email).toLowerCase() === accEmail;
            }).sort(function(a, b) { return Number(a.id) - Number(b.id); })[0];
            isSecondary = !!principal && String(principal.id) !== String(u.id);
        }
        var editBtnHtml = isSecondary
            ? '<button type="button" id="user-demo-edit-btn-account" title="Editar usuario" aria-label="Editar usuario" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;padding:0;border-radius:4px;background:#fff;color:#D24C19;border:1px solid #D24C19;cursor:pointer;transition:all 0.2s;" onclick="editUserAccount(\'' + u.id + '\')"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931z"/><path d="m15 5 4 4"/></svg></button>'
            : '';

        pane.innerHTML =
            '<div class="user-demo-grid" style="grid-template-columns:repeat(2,minmax(0,1fr));align-items:end;">' +
                '<div style="grid-column:span 1;"><div class="user-demo-label">Usuario seleccionado</div><div class="user-demo-value" style="font-weight:700;color:#0c2a4d;">' + escapeHtml(selectedName) + '</div></div>' +
                '<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;flex-wrap:wrap;">' +
                    '<div><div class="user-demo-label">Páginas asignadas</div><div class="user-demo-value">' + (u.pages || []).length + '</div></div>' +
                    editBtnHtml +
                '</div>' +
            '</div>';

        var countEl = document.getElementById('user-tab-count-gestion');
        if (countEl) {
            var accCount = accEmail ? allUsers.filter(function(x) {
                return x.email && String(x.email).toLowerCase() === accEmail;
            }).length : 1;
            countEl.textContent = String(accCount || 1);
        }
    }

    var userPagesModuleFilter = '';
    var userPagesModulesCache = [];
    var userPagesAllCache = [];

    function getPrincipalAccountUser() {
        var acc = getAccountUsers();
        if (!acc.length) return selectedUser;
        return acc.slice().sort(function(a, b) { return Number(a.id) - Number(b.id); })[0];
    }

    function getEffectiveProvider(u) {
        var target = u || selectedUser;
        if (!target) return null;
        var email = target.email ? String(target.email).toLowerCase() : '';
        var peer = null;
        if (email) {
            peer = allUsers.filter(function(x) {
                return x.email && String(x.email).toLowerCase() === email && String(x.id) !== String(target.id) && x.provider;
            }).sort(function(a, b) { return Number(a.id) - Number(b.id); })[0] || null;
        }
        if (target.provider) {
            var p = target.provider;
            if ((!p.images || !p.images.length) && peer && peer.provider && peer.provider.images && peer.provider.images.length) {
                p = Object.assign({}, p, { images: peer.provider.images });
            }
            return p;
        }
        return peer ? peer.provider : null;
    }

    function getAccountPeer(u, predicate) {
        var target = u || selectedUser;
        if (!target) return null;
        var email = target.email ? String(target.email).toLowerCase() : '';
        if (!email) return null;
        return allUsers.filter(function(x) {
            return x.email && String(x.email).toLowerCase() === email && String(x.id) !== String(target.id) && predicate(x);
        }).sort(function(a, b) { return Number(a.id) - Number(b.id); })[0] || null;
    }

    function getEffectiveAddresses(u) {
        var target = u || selectedUser;
        if (!target) return [];
        if (target.addresses && target.addresses.length) return target.addresses;
        var peer = getAccountPeer(target, function(x) { return x.addresses && x.addresses.length; });
        return peer ? peer.addresses : (target.addresses || []);
    }

    function getEffectivePrimaryAddress(u) {
        var target = u || selectedUser;
        if (!target) return null;
        if (target.primary_address) return target.primary_address;
        if (target.primaryAddress) return target.primaryAddress;
        var list = getEffectiveAddresses(target);
        var primary = list.filter(function(a) { return a.is_primary; })[0];
        if (primary) return primary;
        if (list.length) return list[0];
        var peer = getAccountPeer(target, function(x) { return !!(x.primary_address || x.primaryAddress); });
        return peer ? (peer.primary_address || peer.primaryAddress) : null;
    }

    function getEffectiveStatus(u) {
        var target = u || selectedUser;
        if (!target) return null;
        if (target.status) return target.status;
        var peer = getAccountPeer(target, function(x) { return !!x.status; });
        return peer ? peer.status : null;
    }

    function getEffectiveTypeUser(u) {
        var target = u || selectedUser;
        if (!target) return null;
        if (target.type_user || target.typeUser) return target.type_user || target.typeUser;
        var peer = getAccountPeer(target, function(x) { return !!(x.type_user || x.typeUser); });
        return peer ? (peer.type_user || peer.typeUser) : null;
    }

    // Publicidad: solo usuarios tipo Prestador con estado Activo o Prueba.
    var ADVERTISING_STATUSES = ['activo', 'prueba'];

    function userCanAdvertise(u) {
        var target = u || selectedUser;
        if (!target) return false;
        var typeObj = getEffectiveTypeUser(target);
        var statusObj = getEffectiveStatus(target);
        var type = String(typeObj && (typeObj.description || typeObj.status) || '').trim().toLowerCase();
        var status = String(statusObj && (statusObj.status || statusObj.description) || '').trim().toLowerCase();
        return type === 'prestador' && ADVERTISING_STATUSES.indexOf(status) !== -1;
    }

    function updateUserAdTabVisibility(u) {
        var link = document.querySelector('#user-sidebar .sidebar-link[data-user-panel="imagenes"]');
        if (!link) return;
        var allowed = userCanAdvertise(u);
        link.style.display = allowed ? '' : 'none';
        if (!allowed && currentUserPanelTab() === 'imagenes') {
            switchUserTab('demografia');
        }
    }

    function ensureUserPagesModules() {
        if (userPagesModulesCache.length) return Promise.resolve(userPagesModulesCache);
        if (typeUserPagesModules && typeUserPagesModules.length) {
            userPagesModulesCache = typeUserPagesModules;
            return Promise.resolve(userPagesModulesCache);
        }
        return fetch('{{ url("/modules") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(modules) {
            userPagesModulesCache = Array.isArray(modules) ? modules : [];
            if (typeUserPagesModules && !typeUserPagesModules.length) {
                typeUserPagesModules = userPagesModulesCache;
            }
            return userPagesModulesCache;
        })
        .catch(function() { return []; });
    }

    function ensureUserPagesCatalog() {
        if (userPagesAllCache.length) return Promise.resolve(userPagesAllCache);
        if (typeUserPagesAll && typeUserPagesAll.length) {
            userPagesAllCache = typeUserPagesAll;
            return Promise.resolve(userPagesAllCache);
        }
        return fetch('{{ url("/pages") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(pages) {
            userPagesAllCache = Array.isArray(pages) ? pages : [];
            if (typeUserPagesAll && !typeUserPagesAll.length) {
                typeUserPagesAll = userPagesAllCache;
            }
            return userPagesAllCache;
        })
        .catch(function() { return []; });
    }

    function getUserPagesModuleId(pg) {
        if (pg.module_id) return Number(pg.module_id);
        if (pg.module && typeof pg.module === 'object' && pg.module.id) return Number(pg.module.id);
        return 0;
    }

    function getUserAssignedPageIds(user) {
        var ids = {};
        ((user && user.pages) || []).forEach(function(pg) {
            ids[String(pg.id)] = true;
        });
        return ids;
    }

    function isSecondaryAccountUser() {
        if (!selectedUser) return false;
        var principal = getPrincipalAccountUser();
        if (!principal) return false;
        return String(principal.id) !== String(selectedUser.id);
    }

    function getPrincipalAllowedPages() {
        var principal = getPrincipalAccountUser();
        if (!principal) return [];
        if (!isSecondaryAccountUser()) return userPagesAllCache;
        var allowed = getUserAssignedPageIds(principal);
        return userPagesAllCache.filter(function(pg) {
            return !!allowed[String(pg.id)];
        });
    }

    function getUserPagesModulesForSelection(allModules) {
        if (!isSecondaryAccountUser()) return allModules;
        var allowedPages = getPrincipalAllowedPages();
        if (!allowedPages.length) return [];
        var moduleIds = {};
        allowedPages.forEach(function(pg) {
            moduleIds[String(getUserPagesModuleId(pg))] = true;
        });
        return allModules.filter(function(m) {
            return !!moduleIds[String(m.id)];
        });
    }

    function getVisibleUserAccountPages() {
        if (!userPagesModuleFilter) return [];
        var mid = parseInt(userPagesModuleFilter, 10);
        return getPrincipalAllowedPages().filter(function(pg) {
            return getUserPagesModuleId(pg) === mid;
        });
    }

    function renderUserAccountPaginas(u) {
        var pane = document.getElementById('user-account-pane-paginas');
        if (!pane) return;

        var target = u || selectedUser;
        var countEl = document.getElementById('user-tab-count-paginas');
        if (countEl) countEl.textContent = String((target && target.pages ? target.pages.length : 0));

        pane.innerHTML =
            '<div style="margin-bottom:12px;max-width:320px;">' +
                '<label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Módulo</label>' +
                '<select id="user-pages-module" onchange="onUserPagesModuleChange()" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;background:white;box-sizing:border-box;">' +
                    '<option value="">-- Seleccionar módulo --</option>' +
                '</select>' +
            '</div>' +
            '<div id="user-pages-loading" style="padding:24px;text-align:center;display:none;font-size:13px;color:#6b7280;">Cargando...</div>' +
            '<div id="user-pages-empty" class="user-demo-empty-tab" style="display:none;"></div>' +
            '<div id="user-pages-list" style="display:none;overflow-x:auto;">' +
                '<table class="user-pages-grid">' +
                    '<thead>' +
                        '<tr style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">' +
                            '<th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;width:70%;">Página</th>' +
                            '<th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:#ffffff;text-transform:uppercase;letter-spacing:0.5px;width:30%;">' +
                                '<label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;text-transform:uppercase;">' +
                                    '<input type="checkbox" id="user-pages-check-all" onchange="toggleUserAccountPagesAll(this.checked)" style="width:15px;height:15px;accent-color:#D24C19;cursor:pointer;" title="Agregar o quitar todo" />' +
                                    '<span>Agregar/Quitar</span>' +
                                '</label>' +
                            '</th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody id="user-pages-tbody"></tbody>' +
                '</table>' +
                '<div id="user-pages-grid-empty" style="display:none;padding:24px;text-align:center;font-size:13px;color:#6b7280;background:#fff;border:1px solid #e5e7eb;border-top:none;"></div>' +
            '</div>' +
            '<div id="user-pages-msg" class="user-pages-msg" style="display:none;"></div>' +
            '<p id="user-pages-hint" style="margin:12px 0 0;font-size:12px;color:#94a3b8;display:none;"></p>';

        var loading = document.getElementById('user-pages-loading');
        var moduleSel = document.getElementById('user-pages-module');
        if (loading) loading.style.display = 'block';

        Promise.all([ensureUserPagesModules(), ensureUserPagesCatalog()]).then(function(results) {
            var modules = results[0] || [];
            modules = getUserPagesModulesForSelection(modules);
            if (loading) loading.style.display = 'none';
            if (moduleSel) {
                moduleSel.innerHTML = '<option value="">-- Seleccionar módulo --</option>' +
                    modules.map(function(m) {
                        return '<option value="' + m.id + '">' + escapeHtml(m.description || m.name || ('Módulo ' + m.id)) + '</option>';
                    }).join('');
                if (userPagesModuleFilter) {
                    moduleSel.value = userPagesModuleFilter;
                    if (moduleSel.value !== userPagesModuleFilter) {
                        userPagesModuleFilter = '';
                        moduleSel.value = '';
                    }
                }
            }
            renderUserAccountPaginasRows();
        });
    }

    function onUserPagesModuleChange() {
        var sel = document.getElementById('user-pages-module');
        userPagesModuleFilter = sel ? sel.value : '';
        renderUserAccountPaginasRows();
    }

    function renderUserAccountPaginasRows() {
        var tbody = document.getElementById('user-pages-tbody');
        var list = document.getElementById('user-pages-list');
        var empty = document.getElementById('user-pages-empty');
        var gridEmpty = document.getElementById('user-pages-grid-empty');
        var hint = document.getElementById('user-pages-hint');
        if (!tbody || !list) return;

        if (!selectedUser) {
            if (list) list.style.display = 'none';
            if (empty) {
                empty.textContent = 'Seleccioná un usuario para gestionar sus páginas.';
                empty.style.display = 'block';
            }
            if (hint) hint.style.display = 'none';
            return;
        }

        if (isSecondaryAccountUser() && !getPrincipalAllowedPages().length) {
            if (list) list.style.display = 'none';
            if (empty) {
                empty.textContent = 'El usuario principal no tiene módulos ni páginas asignadas.';
                empty.style.display = 'block';
            }
            if (hint) hint.style.display = 'none';
            return;
        }

        if (!userPagesModuleFilter) {
            if (list) list.style.display = 'none';
            if (empty) empty.style.display = 'none';
            if (hint) hint.style.display = 'none';
            return;
        }

        if (empty) empty.style.display = 'none';

        var pages = getVisibleUserAccountPages();
        var selectedIds = getUserAssignedPageIds(selectedUser);

        if (!pages.length) {
            tbody.innerHTML = '';
            list.style.display = 'block';
            if (gridEmpty) {
                gridEmpty.textContent = 'No hay páginas disponibles para este módulo.';
                gridEmpty.style.display = 'block';
            }
            if (hint) hint.style.display = 'none';
            updateUserPagesCheckAll();
            return;
        }

        if (gridEmpty) { gridEmpty.style.display = 'none'; gridEmpty.textContent = ''; }

        tbody.innerHTML = pages.map(function(pg, idx) {
            var moduleName = (pg.module && (typeof pg.module === 'object' ? (pg.module.description || pg.module.name) : pg.module)) || 'Sin módulo';
            var assigned = !!selectedIds[String(pg.id)];
            var bg = idx % 2 === 0 ? '#fff' : '#f8fafc';
            var meta = [];
            if (moduleName) meta.push(escapeHtml(moduleName));
            if (pg.url) meta.push(escapeHtml(pg.url));
            return '<tr style="background:' + bg + ';border-bottom:1px solid #e5e7eb;">' +
                '<td style="padding:10px 16px;font-size:13px;color:#0f172a;vertical-align:middle;">' +
                    '<div style="font-weight:600;">' + escapeHtml(pg.description || '-') + '</div>' +
                    (meta.length ? '<div style="font-size:11px;color:#64748b;margin-top:2px;">' + meta.join(' · ') + '</div>' : '') +
                '</td>' +
                '<td style="padding:10px 16px;text-align:center;vertical-align:middle;">' +
                    '<input type="checkbox" class="user-pages-check" value="' + pg.id + '"' + (assigned ? ' checked' : '') + ' style="width:16px;height:16px;accent-color:#D24C19;cursor:pointer;" title="Agregar o quitar página" onchange="toggleUserAccountPage(' + pg.id + ', this.checked)" />' +
                    '<div style="font-size:10px;color:#64748b;margin-top:2px;">' + (assigned ? 'Agregada' : 'Agregar') + '</div>' +
                '</td>' +
            '</tr>';
        }).join('');

        list.style.display = 'block';
        if (hint) {
            if (isSecondaryAccountUser()) {
                hint.textContent = 'Solo módulos y páginas visibles para el usuario principal. El check guarda en la tabla de páginas de usuarios de ' + (selectedUser.name || 'este usuario') + '.';
            } else {
                hint.textContent = 'Todas las páginas del módulo. El check guarda en la tabla de páginas de usuarios del usuario seleccionado (' + (selectedUser.name || '') + ').';
            }
            hint.style.display = 'block';
        }
        updateUserPagesCheckAll();
    }

    function getVisibleUserAccountPageIds() {
        return getVisibleUserAccountPages().map(function(pg) { return pg.id; });
    }

    function updateUserPagesCheckAll() {
        var header = document.getElementById('user-pages-check-all');
        if (!header) return;
        var list = document.getElementById('user-pages-list');
        if (!list || list.style.display === 'none') {
            header.checked = false;
            header.indeterminate = false;
            return;
        }
        var visibleIds = getVisibleUserAccountPageIds();
        if (!visibleIds.length) {
            header.checked = false;
            header.indeterminate = false;
            return;
        }
        var assigned = getUserAssignedPageIds(selectedUser);
        var onCount = 0;
        visibleIds.forEach(function(id) {
            if (assigned[String(id)]) onCount++;
        });
        header.checked = onCount === visibleIds.length;
        header.indeterminate = onCount > 0 && onCount < visibleIds.length;
    }

    function toggleUserAccountPagesAll(checked) {
        var header = document.getElementById('user-pages-check-all');
        var visibleIds = getVisibleUserAccountPageIds();
        if (!selectedUser || !visibleIds.length) {
            if (header) header.checked = false;
            showUserPagesMsg('No hay páginas visibles para actualizar.', false);
            return;
        }

        visibleIds.forEach(function(id) {
            var sel = document.querySelector('.user-pages-check[value="' + id + '"]');
            if (sel) sel.checked = checked;
        });
        if (header) {
            header.checked = checked;
            header.indeterminate = false;
        }

        Promise.all(visibleIds.map(function(id) {
            var url = '{{ url("/admin/users") }}/' + selectedUser.id + '/pages/' + id;
            return fetch(url, {
                method: checked ? 'POST' : 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(function(r) { return r.json(); }).catch(function() { return { success: false }; });
        }))
        .then(function(results) {
            var failed = results.filter(function(r) { return !r.success; });
            var lastOk = results.find(function(r) { return r.success && r.user; });
            if (lastOk && lastOk.user) applyUserUpdate(lastOk.user);
            refreshSelectedUser().then(function() {
                return loadUsers();
            }).then(function() {
                renderUserAccountPaginasRows();
            });
            if (failed.length) {
                showUserPagesMsg('Algunas páginas no se pudieron actualizar (' + failed.length + ').', false);
            } else {
                showUserPagesMsg(checked ? 'Páginas agregadas.' : 'Páginas quitadas.', true);
            }
        });
    }

    function showUserPagesMsg(text, ok) {
        var el = document.getElementById('user-pages-msg');
        if (!el) return;
        el.textContent = text;
        el.style.display = 'block';
        el.classList.toggle('ok', !!ok);
        el.classList.toggle('err', !ok);
    }

    function toggleUserAccountPage(pageId, checked) {
        if (!selectedUser || !pageId) return;
        var checkbox = document.querySelector('.user-pages-check[value="' + pageId + '"]');
        if (checkbox) checkbox.disabled = true;

        var url = '{{ url("/admin/users") }}/' + selectedUser.id + '/pages/' + pageId;
        fetch(url, {
            method: checked ? 'POST' : 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (checkbox) checkbox.disabled = false;
            if (data.success) {
                showUserPagesMsg(data.message || (checked ? 'Página agregada.' : 'Página quitada.'), true);
                if (data.user) applyUserUpdate(data.user);
                refreshSelectedUser().then(function() {
                    return loadUsers();
                }).then(function() {
                    renderUserAccountPaginasRows();
                });
            } else {
                showUserPagesMsg(data.message || 'No se pudo actualizar la página.', false);
                if (checkbox) checkbox.checked = !checked;
                updateUserPagesCheckAll();
            }
        })
        .catch(function() {
            if (checkbox) {
                checkbox.disabled = false;
                checkbox.checked = !checked;
            }
            showUserPagesMsg('Error de conexión.', false);
            updateUserPagesCheckAll();
        });
    }

    function renderUserImages(u) {
        var dropzone = document.getElementById('user-demo-images-dropzone');
        var noProvider = document.getElementById('user-demo-images-no-provider');
        var grid = document.getElementById('user-demo-images-grid');
        var empty = document.getElementById('user-demo-images-empty');
        var msg = document.getElementById('user-demo-images-msg');
        if (!dropzone || !noProvider || !grid || !empty) return;

        if (msg) {
            msg.style.display = 'none';
            msg.textContent = '';
            msg.classList.remove('ok', 'err');
        }

        var p = getEffectiveProvider(u);
        if (!p) {
            dropzone.style.display = 'none';
            noProvider.style.display = 'block';
            grid.style.display = 'none';
            grid.innerHTML = '';
            empty.style.display = 'none';
            return;
        }

        noProvider.style.display = 'none';
        var limitNote = document.getElementById('user-demo-images-limit');
        if (limitNote) limitNote.style.display = 'none';

        var images = p.images || [];
        if (!images.length) {
            dropzone.style.display = '';
            grid.style.display = 'none';
            grid.innerHTML = '';
            empty.style.display = 'block';
            setupUserDemoImagesDropzone();
            return;
        }

        dropzone.style.display = 'none';
        empty.style.display = 'none';
        grid.style.display = 'grid';
        grid.innerHTML = images.map(function(img) {
            var src = '{{ asset("images/publicidad/") }}/' + img.image_path + '?v=' + Date.now();
            var fileName = img.image_path || ('publicidad_' + img.id);
            return '<div style="position:relative;border:1px solid #e2e8f0;background:#fff;overflow:hidden;">' +
                '<img src="' + src + '" style="width:100%;height:180px;object-fit:cover;display:block;" />' +
                '<span class="img-type-badge">Publicidad</span>' +
                '<button type="button" onclick="downloadUserDemoImage(\'' + encodeURIComponent(src) + '\', \'' + String(fileName).replace(/'/g, '') + '\')" title="Descargar imagen" style="position:absolute;top:6px;right:36px;background:rgba(12,42,77,0.92);color:#fff;border:none;width:24px;height:24px;font-size:11px;cursor:pointer;line-height:1;">&#8595;</button>' +
                '<button type="button" onclick="deleteUserDemoImage(' + img.id + ')" title="Eliminar imagen" style="position:absolute;top:6px;right:6px;background:rgba(220,38,38,0.95);color:#fff;border:none;width:24px;height:24px;font-size:11px;cursor:pointer;line-height:1;">&#10005;</button>' +
                '</div>';
        }).join('');

        if (!limitNote) {
            limitNote = document.createElement('div');
            limitNote.id = 'user-demo-images-limit';
            limitNote.style.cssText = 'margin-top:12px;padding:10px 14px;background:#fff7ed;border:1px solid #fed7aa;font-size:12px;color:#9a3412;';
            limitNote.innerHTML = '&#9432; Solo se permite una imagen publicitaria. Eliminá la actual para subir una nueva.';
            grid.parentNode.insertBefore(limitNote, grid.nextSibling);
        }
        limitNote.style.display = 'block';
    }

    function setupUserDemoImagesDropzone() {
        var zone = document.getElementById('user-demo-images-dropzone');
        var input = document.getElementById('user-demo-images-input');
        if (!zone || !input || zone._userDemoBound) return;
        zone._userDemoBound = true;

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
                uploadUserDemoImage(e.dataTransfer.files[0]);
            }
        });
        input.addEventListener('change', function() {
            if (input.files && input.files[0]) {
                uploadUserDemoImage(input.files[0]);
                input.value = '';
            }
        });
    }

    function showUserDemoImagesMsg(text, ok) {
        var msg = document.getElementById('user-demo-images-msg');
        if (!msg) return;
        msg.textContent = text;
        msg.style.display = 'block';
        msg.classList.toggle('ok', !!ok);
        msg.classList.toggle('err', !ok);
    }

    function uploadUserDemoImage(file) {
        if (!selectedUser || !file) return;
        if (file.size > 5 * 1024 * 1024) {
            showUserDemoImagesMsg('La imagen no puede superar 5MB.', false);
            return;
        }

        var formData = new FormData();
        formData.append('image', file);

        showUserDemoImagesMsg('Subiendo imagen...', true);

        fetch('{{ url("/admin/users") }}/' + selectedUser.id + '/images', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showUserDemoImagesMsg(data.message || 'Imagen subida correctamente.', true);
                applyUserUpdate(data.user);
                refreshSelectedUser();
            } else {
                showUserDemoImagesMsg(data.message || 'No se pudo subir la imagen.', false);
            }
        })
        .catch(function() {
            showUserDemoImagesMsg('Error de conexión.', false);
        });
    }

    function downloadUserDemoImage(encodedSrc, fileName) {
        var src = decodeURIComponent(encodedSrc);
        fetch(src, { cache: 'no-cache' })
            .then(function(r) {
                if (!r.ok) throw new Error('fail');
                return r.blob();
            })
            .then(function(blob) {
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch(function() {
                var a = document.createElement('a');
                a.href = src;
                a.download = fileName;
                a.target = '_blank';
                document.body.appendChild(a);
                a.click();
                a.remove();
            });
    }

    function deleteUserDemoImage(imageId) {
        if (!selectedUser) return;
        if (!confirm('¿Eliminar esta imagen?')) return;

        fetch('{{ url("/admin/users") }}/' + selectedUser.id + '/images/' + imageId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showUserDemoImagesMsg(data.message || 'Imagen eliminada correctamente.', true);
                applyUserUpdate(data.user);
                refreshSelectedUser();
            } else {
                showUserDemoImagesMsg(data.message || 'No se pudo eliminar.', false);
            }
        })
        .catch(function() {
            showUserDemoImagesMsg('Error de conexión.', false);
        });
    }

    function clearUserDemographic() {
        selectedUser = null;
        document.getElementById('user-demo-empty').style.display = 'block';
        document.getElementById('user-demo-panel').style.display = 'none';
        ['user-demo-save-btn', 'user-prov-save-btn', 'user-addr-save-btn'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        switchUserTab('demografia');
    }

    function renderUserDemographic(u) {
        document.getElementById('user-demo-empty').style.display = 'none';
        document.getElementById('user-demo-panel').style.display = 'block';

        ['user-demo-save-btn', 'user-prov-save-btn', 'user-addr-save-btn'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = '';
        });

        switchUserTab(currentUserPanelTab() || 'demografia');

        var nameInput = document.getElementById('user-demo-name');
        var emailInput = document.getElementById('user-demo-email');
        var createdInput = document.getElementById('user-demo-created');
        var verifiedCb = document.getElementById('user-demo-verified');
        var statusSel = document.getElementById('user-demo-status-id');
        var typeSel = document.getElementById('user-demo-type-id');
        var msg = document.getElementById('user-demo-msg');
        ['user-demo-name-error', 'user-demo-email-error'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        if (msg) { msg.style.display = 'none'; msg.textContent = ''; }

        var formU = getPrincipalAccountUser() || u;
        if (nameInput) nameInput.value = formU.name || '';
        if (emailInput) emailInput.value = formU.email || '';
        if (createdInput) createdInput.value = formU.created_at ? new Date(formU.created_at).toLocaleString('es-AR') : '';
        if (verifiedCb) verifiedCb.checked = !!formU.email_verified_at;

        var hdrU = formU;
        var hdrName = document.getElementById('user-demo-hdr-name');
        var hdrEmail = document.getElementById('user-demo-hdr-email');
        var hdrAvatar = document.getElementById('user-demo-avatar');
        var hdrStatus = document.getElementById('user-demo-status');
        var hdrType = document.getElementById('user-demo-type');
        if (hdrName) hdrName.textContent = hdrU.name || '';
        if (hdrEmail) hdrEmail.textContent = hdrU.email || '';
        if (hdrAvatar) hdrAvatar.textContent = ((hdrU.name || '?').trim().charAt(0) || '?').toUpperCase();

        var statusObj = getEffectiveStatus(formU) || formU.status || null;
        var typeObj = getEffectiveTypeUser(formU) || formU.type_user || formU.typeUser || null;
        var statusId = formU.user_status_id || (statusObj && statusObj.id) || '';
        var typeId = formU.type_user_id || (typeObj && typeObj.id) || '';
        var hdrStatusObj = hdrU.status || getEffectiveStatus(hdrU);
        var hdrTypeObj = hdrU.type_user || hdrU.typeUser || getEffectiveTypeUser(hdrU);
        if (hdrStatus) {
            hdrStatus.textContent = (hdrStatusObj && (hdrStatusObj.status || hdrStatusObj.description)) || 'Activo';
            var stKey = String(hdrStatus.textContent).trim().toLowerCase();
            if (stKey === 'inactive' || stKey === 'inactivo' || stKey === 'inactiva') {
                hdrStatus.style.cssText = 'font-size:11px;padding:4px 10px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;';
            } else if (stKey === 'prueba') {
                hdrStatus.style.cssText = 'font-size:11px;padding:4px 10px;background:#fffbeb;border:1px solid #fde68a;color:#b45309;';
            } else {
                hdrStatus.style.cssText = 'font-size:11px;padding:4px 10px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;';
            }
        }
        if (hdrType) hdrType.textContent = (hdrTypeObj && (hdrTypeObj.description || hdrTypeObj.status)) || 'Prestador';
        if (statusSel) {
            statusSel._pendingValue = statusId ? String(statusId) : '';
            if (userStatusOptionsCache) fillUserStatusSelect(statusSel, statusSel._pendingValue);
            else loadUserStatusOptions();
        }
        if (typeSel) {
            typeSel._pendingValue = typeId ? String(typeId) : '';
            if (userTypeOptionsCache) fillUserTypeSelect(typeSel, typeSel._pendingValue);
            else loadTypeUserOptions();
        }

        renderUserAddresses(u);
        renderUserProvider(u);
        renderUserAccount(u);
        renderUserImages(u);
        updateUserAdTabVisibility(u);
    }

    function submitUserDemographic() {
        if (!selectedUser) return;
        var target = getPrincipalAccountUser() || selectedUser;
        var name = document.getElementById('user-demo-name').value.trim();
        var email = document.getElementById('user-demo-email').value.trim();
        var statusSel = document.getElementById('user-demo-status-id');
        var typeSel = document.getElementById('user-demo-type-id');
        var verifiedCb = document.getElementById('user-demo-verified');
        var nameErr = document.getElementById('user-demo-name-error');
        var emailErr = document.getElementById('user-demo-email-error');
        var msg = document.getElementById('user-demo-msg');
        var btn = document.getElementById('user-demo-save-btn');

        nameErr.style.display = 'none';
        emailErr.style.display = 'none';
        msg.style.display = 'none';

        if (!name) {
            nameErr.textContent = 'El nombre es obligatorio.';
            nameErr.style.display = 'block';
            return;
        }
        if (!email) {
            emailErr.textContent = 'El email es obligatorio.';
            emailErr.style.display = 'block';
            return;
        }

        var body = {
            name: name,
            email: email,
            user_status_id: statusSel && statusSel.value ? parseInt(statusSel.value, 10) : null,
            type_user_id: typeSel && typeSel.value ? parseInt(typeSel.value, 10) : null,
            email_verified: !!(verifiedCb && verifiedCb.checked)
        };

        btn.disabled = true;
        btn.textContent = 'Guardando...';

        fetch('{{ url("/admin/users") }}/' + target.id, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Guardar datos';
            if (data.errors) {
                if (data.errors.name) { nameErr.textContent = data.errors.name[0]; nameErr.style.display = 'block'; }
                if (data.errors.email) { emailErr.textContent = data.errors.email[0]; emailErr.style.display = 'block'; }
                return;
            }
            if (data.success) {
                msg.textContent = 'Guardado correctamente.';
                msg.className = 'save-msg ok';
                msg.style.display = 'inline';
                if (data.user) applyUserUpdate(data.user);
                refreshSelectedUser();
                setTimeout(function() { msg.style.display = 'none'; }, 2500);
            } else {
                msg.textContent = data.message || 'Error al guardar.';
                msg.className = 'save-msg err';
                msg.style.display = 'inline';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Guardar datos';
            msg.textContent = 'Error de conexión.';
            msg.className = 'save-msg err';
            msg.style.display = 'inline';
        });
    }

    var userStatusOptionsCache = null;
    var userTypeOptionsCache = null;
    var userStatusOptReqId = 0;
    var userTypeOptReqId = 0;

    function eachStatusSelect(fn) {
        ['user-demo-status-id', 'user-filter-status'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) fn(el);
        });
    }

    function eachTypeSelect(fn) {
        ['user-demo-type-id', 'user-filter-type'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) fn(el);
        });
    }

    function fillUserStatusSelect(select, selectedValue) {
        var isFilter = select.id.indexOf('filter') !== -1;
        select.innerHTML = isFilter ? '<option value="">Todos los estados</option>' : '<option value="">-- Sin estado --</option>';
        (userStatusOptionsCache || []).forEach(function(s) {
            var opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.status;
            select.appendChild(opt);
        });
        if (selectedValue) select.value = String(selectedValue);
        select._pendingValue = '';
    }

    function fillUserTypeSelect(select, selectedValue) {
        var isFilter = select.id.indexOf('filter') !== -1;
        select.innerHTML = isFilter ? '<option value="">Todos los tipos</option>' : '<option value="">-- Sin tipo --</option>';
        (userTypeOptionsCache || []).forEach(function(t) {
            var opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.description;
            select.appendChild(opt);
        });
        if (selectedValue) select.value = String(selectedValue);
        select._pendingValue = '';
    }

    function loadUserStatusOptions() {
        if (userStatusOptionsCache) {
            eachStatusSelect(function(sel) {
                fillUserStatusSelect(sel, sel._pendingValue || sel.value || '');
            });
            return;
        }
        var reqId = ++userStatusOptReqId;
        fetch('{{ url("/user-statuses") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (reqId !== userStatusOptReqId) return;
            userStatusOptionsCache = Array.isArray(data) ? data : [];
            eachStatusSelect(function(sel) {
                fillUserStatusSelect(sel, sel._pendingValue || sel.value || '');
            });
        })
        .catch(function() {});
    }

    function loadTypeUserOptions() {
        if (userTypeOptionsCache) {
            eachTypeSelect(function(sel) {
                fillUserTypeSelect(sel, sel._pendingValue || sel.value || '');
            });
            return;
        }
        var reqId = ++userTypeOptReqId;
        fetch('{{ url("/type-users") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (reqId !== userTypeOptReqId) return;
            userTypeOptionsCache = Array.isArray(data) ? data : [];
            eachTypeSelect(function(sel) {
                fillUserTypeSelect(sel, sel._pendingValue || sel.value || '');
            });
        })
        .catch(function() {});
    }

    function renderUsers(users) {
        var tbody = document.getElementById('users-tbody');
        var loading = document.getElementById('users-loading');
        var empty = document.getElementById('users-empty');
        var tableWrap = document.getElementById('users-table-wrap');
        var pagination = document.getElementById('users-pagination');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (users.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            pagination.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        var totalPages = Math.ceil(users.length / userPerPage);
        if (userCurrentPage > totalPages) userCurrentPage = totalPages;
        if (userCurrentPage < 1) userCurrentPage = 1;

        var start = (userCurrentPage - 1) * userPerPage;
        var end = start + userPerPage;
        var pageItems = users.slice(start, end);

        pageItems.forEach(function(u) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', u.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(u.name) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;">' + escapeHtml(u.email) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="selectUser(' + u.id + ')" title="Seleccionar" aria-label="Seleccionar" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;padding:0;border-radius:4px;background:#D24C19;border:1px solid #D24C19;cursor:pointer;color:#fff;transition:all 0.2s;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg></button>' +
                '</td>';
            tbody.appendChild(tr);
        });

        if (totalPages <= 1) { pagination.style.display = 'none'; return; }
        pagination.style.display = 'flex';

        var dots = document.getElementById('users-page-dots');
        dots.innerHTML = '';
        for (var p = 1; p <= totalPages; p++) {
            var d = document.createElement('span');
            d.textContent = p;
            d.style.cssText = 'padding:2px 6px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;' + (p === userCurrentPage ? 'color:#D24C19;' : 'color:#9ca3af;');
            d.onmouseenter = function () { if (parseInt(this.textContent) !== userCurrentPage) this.style.color = '#374151'; };
            d.onmouseleave = function () { if (parseInt(this.textContent) !== userCurrentPage) this.style.color = '#9ca3af'; };
            d.onclick = function () { userCurrentPage = parseInt(this.textContent); renderUsers(allUsers._filtered || allUsers); };
            dots.appendChild(d);
        }
        document.getElementById('users-prev-page').style.opacity = userCurrentPage === 1 ? '0.4' : '1';
        document.getElementById('users-prev-page').style.pointerEvents = userCurrentPage === 1 ? 'none' : 'auto';
        document.getElementById('users-next-page').style.opacity = userCurrentPage === totalPages ? '0.4' : '1';
        document.getElementById('users-next-page').style.pointerEvents = userCurrentPage === totalPages ? 'none' : 'auto';
    }

    function userPage(dir) {
        var data = allUsers._filtered || allUsers;
        var totalPages = Math.ceil(data.length / userPerPage);
        if (dir === 1 && userCurrentPage > 1) { userCurrentPage--; renderUsers(data); }
        if (dir === 2 && userCurrentPage < totalPages) { userCurrentPage++; renderUsers(data); }
    }

    function filterUsers() {
        userCurrentPage = 1;
        var q = document.getElementById('user-search').value.toLowerCase();
        var statusId = document.getElementById('user-filter-status') ? document.getElementById('user-filter-status').value : '';
        var typeId = document.getElementById('user-filter-type') ? document.getElementById('user-filter-type').value : '';
        var filtered = getSelectableUsers().filter(function(u) {
            var matchQ = u.name.toLowerCase().indexOf(q) !== -1 || u.email.toLowerCase().indexOf(q) !== -1;
            var uStatusId = u.user_status_id || (u.status && u.status.id) || '';
            var uTypeId = u.type_user_id || (u.type_user && u.type_user.id) || (u.typeUser && u.typeUser.id) || '';
            var matchStatus = !statusId || String(uStatusId) === String(statusId);
            var matchType = !typeId || String(uTypeId) === String(typeId);
            return matchQ && matchStatus && matchType;
        });
        allUsers._filtered = filtered;
        renderUsers(filtered);
    }

    function toggleUserPasswordVisibility(inputId, btn) {
        var input = document.getElementById(inputId);
        if (!input || !btn) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.innerHTML = show
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><path d="M2 2l20 20"/></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1 1 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1 1 0 0 1 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>';
    }

    function openUserModal(id) {
        var u = null;
        if (id !== null && id !== undefined && id !== '') {
            u = allUsers.find(function(x) { return String(x.id) === String(id); });
            if (!u && selectedUser && String(selectedUser.id) === String(id)) {
                u = selectedUser;
            }
        }
        if (!u && selectedUser && (id === selectedUser.id || String(id) === String(selectedUser.id))) {
            u = selectedUser;
        }
        var emailInput = document.getElementById('user-email');
        var confirmField = document.getElementById('user-password-confirm-field');
        var confirmReq = document.getElementById('user-password-confirm-required');
        var deleteBtn = document.getElementById('user-modal-delete-btn');
        document.getElementById('user-id').value = u ? u.id : '';
        document.getElementById('user-name').value = u ? u.name : '';
        var principal = getPrincipalAccountUser() || u || selectedUser;
        if (emailInput) emailInput.value = (principal && principal.email) || (u && u.email) || (selectedUser && selectedUser.email) || authEmail || '';
        if (deleteBtn) deleteBtn.style.display = u ? 'inline-block' : 'none';
        document.getElementById('user-password').value = '';
        var confirmInput = document.getElementById('user-password-confirm');
        if (confirmInput) {
            confirmInput.value = '';
            confirmInput.type = 'password';
        }
        var pwInput = document.getElementById('user-password');
        if (pwInput) pwInput.type = 'password';
        document.querySelectorAll('.user-pw-toggle').forEach(function(btn) {
            if (!btn.dataset.target) return;
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1 1 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178a1 1 0 0 1 0 .644C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>';
        });
        document.getElementById('user-name-error').style.display = 'none';
        document.getElementById('user-password-error').style.display = 'none';
        var confirmErr = document.getElementById('user-password-confirm-error');
        if (confirmErr) confirmErr.style.display = 'none';
        document.getElementById('user-password-required').style.display = u ? 'none' : 'inline';
        document.getElementById('user-password-hint').style.display = u ? 'block' : 'none';
        if (confirmField) confirmField.style.display = u ? 'none' : 'block';
        if (confirmReq) confirmReq.style.display = u ? 'none' : 'inline';
        document.getElementById('user-modal-title').textContent = u ? 'Editar Usuario' : 'Nuevo Usuario';
        document.getElementById('user-submit-btn').textContent = u ? 'Actualizar' : 'Guardar';
        document.getElementById('user-modal-overlay').style.display = 'flex';
        document.getElementById('user-name').focus();
    }

    function closeUserModal() {
        document.getElementById('user-modal-overlay').style.display = 'none';
    }

    function userModalDelete() {
        var id = document.getElementById('user-id').value;
        if (!id) {
            closeUserModal();
            return;
        }
        var u = allUsers.find(function(x) { return String(x.id) === String(id); });
        if (!u && selectedUser && String(selectedUser.id) === String(id)) u = selectedUser;
        var name = (u && u.name) || document.getElementById('user-name').value || '';
        closeUserModal();
        openUserDelete(id, name);
    }

    function editUser(id) {
        editUserAccount(id);
    }

    function isInactiveUserStatus(u) {
        var st = (u && u.status) ? String(u.status.status || u.status.description || '') : '';
        st = st.trim().toLowerCase();
        return st === 'inactive' || st === 'inactivo';
    }

    function editUserAccount(id) {
        var u = null;
        if (id !== null && id !== undefined && id !== '') {
            u = allUsers.find(function(x) { return String(x.id) === String(id); });
            if (!u && selectedUser && String(selectedUser.id) === String(id)) u = selectedUser;
        }
        if (u && isInactiveUserStatus(u)) {
            if (!confirm('\u00bfEl usuario ' + (u.name || '') + ' est\u00e1 inactivo. \u00bfQuer\u00e9s activarlo?')) {
                return;
            }
            activateInactiveUser(u);
            return;
        }
        openUserModal(id);
    }

    function activateInactiveUser(u) {
        var csrf = document.querySelector('meta[name="csrf-token"]').content;
        var jsonHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf
        };
        var statusesPromise = userStatusOptionsCache
            ? Promise.resolve(userStatusOptionsCache)
            : fetch('{{ url("/user-statuses") }}', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                userStatusOptionsCache = Array.isArray(data) ? data : [];
                return userStatusOptionsCache;
            });

        statusesPromise.then(function(list) {
            var active = null;
            (list || []).forEach(function(s) {
                var n = String(s.status || '').trim().toLowerCase();
                if (!active && (n === 'active' || n === 'activo')) active = s;
            });
            if (!active) return;
            var principal = getPrincipalAccountUser() || u;
            var body = {
                name: u.name,
                email: (principal && principal.email) || u.email || authEmail || '',
                user_status_id: active.id,
                type_user_id: u.type_user_id || (u.type_user && u.type_user.id) || null
            };
            return fetch('{{ url("/admin/users") }}/' + u.id, {
                method: 'PUT',
                headers: jsonHeaders,
                body: JSON.stringify(body)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (data.user) applyUserUpdate(data.user);
                    loadUsers().then(function() { refreshSelectedUser(); });
                }
            });
        }).catch(function() {});
    }

    function submitUser(e) {
        e.preventDefault();
        var id = document.getElementById('user-id').value;
        var name = document.getElementById('user-name').value.trim();
        var emailEl = document.getElementById('user-email');
        var emailInputVal = emailEl ? emailEl.value.trim() : '';
        var principal = getPrincipalAccountUser() || selectedUser;
        var email = emailInputVal || (principal && principal.email) || (selectedUser && selectedUser.email) || authEmail || '';
        var password = document.getElementById('user-password').value;
        var passwordConfirm = document.getElementById('user-password-confirm') ? document.getElementById('user-password-confirm').value : '';
        var target = selectedUser;
        if (id) {
            target = allUsers.find(function(x) { return String(x.id) === String(id); }) || selectedUser;
            if (selectedUser && String(selectedUser.id) === String(id)) target = selectedUser;
        }
        var statusId = null;
        var typeId = null;
        if (target) {
            statusId = target.user_status_id || (target.status && target.status.id) || null;
            typeId = target.type_user_id || (target.type_user && target.type_user.id) || null;
        }
        var nameErr = document.getElementById('user-name-error');
        var passErr = document.getElementById('user-password-error');
        var confirmErr = document.getElementById('user-password-confirm-error');
        var submitBtn = document.getElementById('user-submit-btn');

        nameErr.style.display = 'none';
        passErr.style.display = 'none';
        if (confirmErr) confirmErr.style.display = 'none';

        if (!name) {
            nameErr.textContent = 'El nombre es obligatorio.';
            nameErr.style.display = 'block';
            return;
        }
        if (!email) {
            nameErr.textContent = 'No se pudo obtener el email del usuario principal.';
            nameErr.style.display = 'block';
            return;
        }
        if (!id && password.length < 8) {
            passErr.textContent = 'La contraseña debe tener al menos 8 caracteres.';
            passErr.style.display = 'block';
            return;
        }
        if (!id && !passwordConfirm) {
            if (confirmErr) {
                confirmErr.textContent = 'Confirmá la contraseña.';
                confirmErr.style.display = 'block';
            }
            return;
        }
        if (!id && password !== passwordConfirm) {
            if (confirmErr) {
                confirmErr.textContent = 'Las contraseñas no coinciden.';
                confirmErr.style.display = 'block';
            }
            return;
        }
        if (id && password) {
            if (password.length < 8) {
                passErr.textContent = 'La contraseña debe tener al menos 8 caracteres.';
                passErr.style.display = 'block';
                return;
            }
            if (passwordConfirm && password !== passwordConfirm) {
                if (confirmErr) {
                    confirmErr.textContent = 'Las contraseñas no coinciden.';
                    confirmErr.style.display = 'block';
                }
                return;
            }
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var body = {
            name: name,
            email: email,
            user_status_id: statusId,
            type_user_id: typeId
        };
        if (password) body.password = password;

        var url = id ? '{{ url("/admin/users") }}/' + id : '{{ url("/admin/users") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                if (data.errors.name) { nameErr.textContent = data.errors.name[0]; nameErr.style.display = 'block'; }
                if (data.errors.email) { nameErr.textContent = data.errors.email[0]; nameErr.style.display = 'block'; }
                if (data.errors.password) { passErr.textContent = data.errors.password[0]; passErr.style.display = 'block'; }
                return;
            }

            if (data.success) {
                closeUserModal();
                if (data.user) applyUserUpdate(data.user);
                loadUsers().then(function() {
                    refreshSelectedUser();
                });
            } else {
                nameErr.textContent = data.message || 'Error al guardar.';
                nameErr.style.display = 'block';
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';
            nameErr.textContent = 'Error de conexión.';
            nameErr.style.display = 'block';
        });
    }

    function setErr(id, text) {
        var el = document.getElementById(id);
        if (!el) return;
        if (text) {
            el.textContent = text;
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    }

    function showUserAddrMsg(text, ok) {
        var msg = document.getElementById('user-addr-msg');
        if (!msg) return;
        msg.textContent = text || '';
        msg.className = 'save-msg ' + (ok ? 'ok' : 'err');
        msg.style.display = text ? 'inline' : 'none';
    }

    function resetUserAddressForm() {
        document.getElementById('user-addr-id').value = '';
        document.getElementById('user-addr-form-title').textContent = 'Mi Dirección';
        document.getElementById('user-addr-street').value = '';
        document.getElementById('user-addr-number').value = '';
        document.getElementById('user-addr-floor').value = '';
        document.getElementById('user-addr-postal').value = '';
        document.getElementById('user-addr-notes').value = '';
        setErr('user-addr-error', '');
        setErr('user-addr-country-error', '');
        showUserAddrMsg('', true);
        var countrySel = document.getElementById('user-addr-country');
        var provinceSel = document.getElementById('user-addr-province');
        if (countrySel && countrySel.options.length <= 1) {
            countrySel.innerHTML = '<option value="">Cargando...</option>';
            fetch('{{ url("/countries") }}', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(function(r) { return r.json(); })
            .then(function(countries) {
                if (!countrySel.isConnected) return;
                countrySel.innerHTML = '<option value="">-- Seleccionar --</option>';
                countries.forEach(function(c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    countrySel.appendChild(opt);
                });
            })
            .catch(function() {
                countrySel.innerHTML = '<option value="">Error al cargar países</option>';
            });
        }
        if (provinceSel) {
            provinceSel.innerHTML = '<option value="">-- Seleccionar país primero --</option>';
            provinceSel.disabled = true;
        }
    }

    function fillUserAddressForm(addressId, switchTab) {
        if (!selectedUser) return;
        var addresses = getEffectiveAddresses(selectedUser);
        var a = addressId ? addresses.find(function(x) { return String(x.id) === String(addressId); }) : null;
        document.getElementById('user-addr-id').value = a ? a.id : '';
        document.getElementById('user-addr-form-title').textContent = a ? 'Editar dirección' : 'Mi Dirección';
        document.getElementById('user-addr-street').value = a ? (a.street || '') : '';
        document.getElementById('user-addr-number').value = a ? (a.number || '') : '';
        document.getElementById('user-addr-floor').value = a ? (a.floor_apartment || '') : '';
        document.getElementById('user-addr-postal').value = a ? (a.postal_code || '') : '';
        document.getElementById('user-addr-notes').value = a ? (a.notes || '') : '';
        setErr('user-addr-error', '');
        setErr('user-addr-country-error', '');
        showUserAddrMsg('', true);

        var countrySel = document.getElementById('user-addr-country');
        var provinceSel = document.getElementById('user-addr-province');
        countrySel.innerHTML = '<option value="">Cargando...</option>';
        provinceSel.innerHTML = '<option value="">-- Seleccionar país primero --</option>';
        provinceSel.disabled = true;

        fetch('{{ url("/countries") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(countries) {
            countrySel.innerHTML = '<option value="">-- Seleccionar --</option>';
            countries.forEach(function(c) {
                var opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                countrySel.appendChild(opt);
            });
            if (a && a.country_id) {
                countrySel.value = String(a.country_id);
                loadUserAddressRegions(a.country_id, a.province_id);
            }
        })
        .catch(function() {
            countrySel.innerHTML = '<option value="">Error al cargar países</option>';
        });

        if (a && switchTab) {
            switchUserTab('direcciones');
            var form = document.getElementById('user-addr-street');
            if (form) form.focus();
        }
    }

    function loadUserAddressRegions(countryId, selectedId) {
        var sel = document.getElementById('user-addr-province');
        if (!sel) return;
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
            if (selectedId) sel.value = String(selectedId);
            sel.disabled = false;
        })
        .catch(function() {
            sel.innerHTML = '<option value="">Error al cargar regiones</option>';
        });
    }

    function submitUserAddress(e) {
        if (e && e.preventDefault) e.preventDefault();
        if (!selectedUser) return;
        var addressId = document.getElementById('user-addr-id').value;
        var countryId = document.getElementById('user-addr-country').value;
        var errEl = document.getElementById('user-addr-error');
        var countryErr = document.getElementById('user-addr-country-error');
        var btn = document.getElementById('user-addr-save-btn');
        errEl.style.display = 'none';
        countryErr.style.display = 'none';
        showUserAddrMsg('', true);

        if (!countryId) {
            countryErr.textContent = 'El país es obligatorio.';
            countryErr.style.display = 'block';
            return;
        }

        var body = {
            country_id: parseInt(countryId, 10),
            province_id: document.getElementById('user-addr-province').value ? parseInt(document.getElementById('user-addr-province').value, 10) : null,
            street: document.getElementById('user-addr-street').value.trim(),
            number: document.getElementById('user-addr-number').value.trim(),
            floor_apartment: document.getElementById('user-addr-floor').value.trim(),
            postal_code: document.getElementById('user-addr-postal').value.trim(),
            notes: document.getElementById('user-addr-notes').value.trim(),
            is_primary: true
        };

        btn.disabled = true;
        btn.textContent = 'Guardando...';

        var base = '{{ url("/admin/users") }}/' + selectedUser.id + '/addresses';
        var url = addressId ? base + '/' + addressId : base;
        var method = addressId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Guardar dirección';
            if (data.errors) {
                var msgs = [];
                Object.keys(data.errors).forEach(function(k) { msgs.push(data.errors[k][0]); });
                errEl.textContent = msgs.join(' ');
                errEl.style.display = 'block';
                return;
            }
            if (data.success) {
                showUserAddrMsg('Guardada correctamente.', true);
                applyUserUpdate(data.user);
                refreshSelectedUser();
                setTimeout(function() { showUserAddrMsg('', true); }, 2500);
            } else {
                errEl.textContent = data.message || 'Error al guardar.';
                errEl.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Guardar dirección';
            errEl.textContent = 'Error de conexión.';
            errEl.style.display = 'block';
        });
    }

    function openUserProviderModal() {
        if (!selectedUser) return;
        renderUserProvider(selectedUser);
        switchUserTab('negocio');
        var business = document.getElementById('user-prov-business');
        if (business) business.focus();
    }

    function closeUserProviderModal() {}

    function submitUserProvider(e) {
        if (e && e.preventDefault) e.preventDefault();
        if (!selectedUser) return;
        var business = document.getElementById('user-prov-business').value.trim();
        var errBn = document.getElementById('user-prov-business-error');
        var errEl = document.getElementById('user-prov-error');
        var msg = document.getElementById('user-prov-msg');
        var btn = document.getElementById('user-prov-save-btn');
        errBn.style.display = 'none';
        errEl.style.display = 'none';
        if (msg) { msg.style.display = 'none'; }

        if (!business) {
            errBn.textContent = 'El nombre del negocio es obligatorio.';
            errBn.style.display = 'block';
            return;
        }

        var body = {
            business_name: business,
            description: document.getElementById('user-prov-description').value.trim(),
            phone: document.getElementById('user-prov-phone').value.trim(),
            whatsapp: document.getElementById('user-prov-whatsapp').value.trim(),
            zone: document.getElementById('user-prov-zone').value.trim(),
            promo: document.getElementById('user-prov-promo').value.trim(),
            is_active: document.getElementById('user-prov-active').checked
        };

        btn.disabled = true;
        btn.textContent = 'Guardando...';

        fetch('{{ url("/admin/users") }}/' + selectedUser.id + '/provider', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(body)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Guardar negocio';
            if (data.errors) {
                var msgs = [];
                Object.keys(data.errors).forEach(function(k) { msgs.push(data.errors[k][0]); });
                errEl.textContent = msgs.join(' ');
                errEl.style.display = 'block';
                return;
            }
            if (data.success) {
                if (msg) {
                    msg.textContent = 'Guardado correctamente.';
                    msg.className = 'save-msg ok';
                    msg.style.display = 'inline';
                }
                applyUserUpdate(data.user);
                refreshSelectedUser();
                if (msg) setTimeout(function() { msg.style.display = 'none'; }, 2500);
            } else {
                errEl.textContent = data.message || 'Error al guardar.';
                errEl.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Guardar negocio';
            errEl.textContent = 'Error de conexión.';
            errEl.style.display = 'block';
        });
    }

    function openUserDelete(id, name) {
        deleteUserId = id;
        document.getElementById('user-delete-name').textContent = name;
        document.getElementById('user-delete-warning').style.display = 'none';
        document.getElementById('user-delete-overlay').style.display = 'flex';
    }

    function closeUserDelete() {
        document.getElementById('user-delete-overlay').style.display = 'none';
        deleteUserId = null;
    }

    function confirmDeleteUser() {
        if (!deleteUserId) return;
        var btn = document.getElementById('user-delete-btn');
        var deletingId = deleteUserId;
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch('{{ url("/admin/users") }}/' + deleteUserId, {
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
                closeUserDelete();
                var deletedEmail = '';
                var deletedUser = allUsers.find(function(x) { return String(x.id) === String(deletingId); });
                if (deletedUser && deletedUser.email) deletedEmail = String(deletedUser.email).toLowerCase();
                if (selectedUser && String(selectedUser.id) === String(deletingId)) selectedUser = null;
                loadUsers().then(function() {
                    var email = deletedEmail || (authEmail ? String(authEmail).toLowerCase() : '');
                    var acc = email ? allUsers.filter(function(x) {
                        return x.email && String(x.email).toLowerCase() === email;
                    }) : [];
                    acc.sort(function(a, b) { return Number(a.id) - Number(b.id); });
                    var principal = acc.length ? acc[0] : null;
                    if (principal && String(principal.id) !== String(deletingId)) {
                        selectedUser = principal;
                        renderUserDemographic(principal);
                        renderUserAccount(principal);
                        loadUserCategorias();
                        updateCreateUserBtnVisibility();
                        return;
                    }
                    var demoEmpty = document.getElementById('user-demo-empty');
                    var demoPanel = document.getElementById('user-demo-panel');
                    if (demoEmpty) demoEmpty.style.display = 'block';
                    if (demoPanel) demoPanel.style.display = 'none';
                    ['user-demo-save-btn', 'user-prov-save-btn', 'user-addr-save-btn'].forEach(function(id) {
                        var el = document.getElementById(id);
                        if (el) el.style.display = 'none';
                    });
                    var g = document.getElementById('user-account-pane-gestion');
                    if (g) {
                        g.removeAttribute('data-user-id');
                        g.innerHTML = '<div class="user-demo-empty-tab">Seleccion\u00e1 un usuario para ver su informaci\u00f3n.</div>';
                    }
                    var p = document.getElementById('user-account-pane-paginas');
                    if (p) {
                        p.removeAttribute('data-user-id');
                        p.innerHTML = '<div class="user-demo-empty-tab">Seleccion\u00e1 un usuario para gestionar sus p\u00e1ginas.</div>';
                    }
                    var pCount = document.getElementById('user-tab-count-paginas');
                    if (pCount) pCount.textContent = '0';
                    updateCreateUserBtnVisibility();
                });
            } else {
                var warn = document.getElementById('user-delete-warning');
                warn.textContent = data.message || 'No se pudo inactivar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('user-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

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
                '<td style="padding:10px 16px;text-align:center;font-size:13px;color:#6b7280;">' + Number(s.users_count || 0) + '</td>' +
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

    // ==================== UNIDADES DE MEDIDA ====================

    var allUnitsOfMeasure = [];
    var deleteUnitId = null;

    function loadUnitsOfMeasure() {
        document.getElementById('uom-loading').style.display = 'block';
        document.getElementById('uom-empty').style.display = 'none';
        document.getElementById('uom-table-wrap').style.display = 'none';

        fetch('{{ url("/units-of-measure") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allUnitsOfMeasure = data;
            renderUnitsOfMeasure(data);
        })
        .catch(function() {
            document.getElementById('uom-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar unidades.</p>';
        });
    }

    function renderUnitsOfMeasure(units) {
        var tbody = document.getElementById('uom-tbody');
        var loading = document.getElementById('uom-loading');
        var empty = document.getElementById('uom-empty');
        var tableWrap = document.getElementById('uom-table-wrap');

        loading.style.display = 'none';
        tbody.innerHTML = '';

        if (units.length === 0) {
            empty.style.display = 'block';
            tableWrap.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        tableWrap.style.display = 'block';

        units.forEach(function(u) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', u.id);
            tr.style.borderBottom = '1px solid #f3f4f6';
            tr.innerHTML =
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(u.name) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#6b7280;">' + escapeHtml(u.symbol) + '</td>' +
                '<td style="padding:10px 16px;font-size:13px;color:#6b7280;">1 ' + escapeHtml(u.name) + ' = ' + Number(u.base_conversion_factor) + ' und</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    (u.is_integer_only
                        ? '<span style="background:#fff7ed;color:#9a3412;border:1px solid #fde68a;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:600;">Solo enteras</span>'
                        : '<span style="background:#d1fae5;color:#047857;border:1px solid #a7f3d0;border-radius:9999px;padding:2px 8px;font-size:11px;font-weight:600;">Admite decimales</span>') +
                '</td>' +
                '<td style="padding:10px 16px;text-align:center;font-size:13px;color:' + (Number(u.products_count || 0) ? '#1f2937' : '#94a3b8') + ';">' + Number(u.products_count || 0) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editUnit(' + u.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openUnitDelete(' + u.id + ', \'' + escapeHtml(u.name).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterUnitsOfMeasure() {
        var q = document.getElementById('uom-search').value.toLowerCase();
        var filtered = allUnitsOfMeasure.filter(function(u) {
            return u.name.toLowerCase().indexOf(q) !== -1 || u.symbol.toLowerCase().indexOf(q) !== -1;
        });
        renderUnitsOfMeasure(filtered);
    }

    function openUnitModal(id, name, symbol, factor, isInteger) {
        document.getElementById('uom-id').value = id || '';
        document.getElementById('uom-name').value = name || '';
        document.getElementById('uom-symbol').value = symbol || '';
        document.getElementById('uom-factor').value = factor != null ? factor : '1';
        document.getElementById('uom-integer').checked = isInteger === undefined ? true : !!isInteger;
        document.getElementById('uom-error').style.display = 'none';
        document.getElementById('uom-modal-title').textContent = id ? 'Editar Unidad' : 'Nueva Unidad';
        document.getElementById('uom-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('uom-modal-overlay').style.display = 'flex';
        document.getElementById('uom-name').focus();
    }

    function closeUnitModal() {
        document.getElementById('uom-modal-overlay').style.display = 'none';
    }

    function editUnit(id) {
        var u = allUnitsOfMeasure.find(function(unit) { return unit.id === id; });
        if (u) openUnitModal(u.id, u.name, u.symbol, u.base_conversion_factor, u.is_integer_only);
    }

    function submitUnit(e) {
        e.preventDefault();
        var id = document.getElementById('uom-id').value;
        var nameVal = document.getElementById('uom-name').value.trim();
        var symbolVal = document.getElementById('uom-symbol').value.trim();
        var factorVal = document.getElementById('uom-factor').value.trim();
        var integerVal = document.getElementById('uom-integer').checked;
        var errorEl = document.getElementById('uom-error');
        var submitBtn = document.getElementById('uom-submit-btn');

        errorEl.style.display = 'none';

        if (!nameVal) {
            errorEl.textContent = 'La unidad es obligatoria.';
            errorEl.style.display = 'block';
            return;
        }
        if (!symbolVal) {
            errorEl.textContent = 'El símbolo es obligatorio.';
            errorEl.style.display = 'block';
            return;
        }
        if (!factorVal || Number(factorVal) <= 0) {
            errorEl.textContent = 'La equivalencia debe ser un número mayor a cero.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? '{{ url("/units-of-measure") }}/' + id : '{{ url("/units-of-measure") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                name: nameVal,
                symbol: symbolVal,
                base_conversion_factor: factorVal,
                is_integer_only: integerVal
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                var firstKey = Object.keys(data.errors)[0];
                errorEl.textContent = data.errors[firstKey][0];
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeUnitModal();
                loadUnitsOfMeasure();
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

    function openUnitDelete(id, name) {
        deleteUnitId = id;
        document.getElementById('uom-delete-name').textContent = name;
        document.getElementById('uom-delete-warning').style.display = 'none';
        document.getElementById('uom-delete-overlay').style.display = 'flex';
    }

    function closeUnitDelete() {
        document.getElementById('uom-delete-overlay').style.display = 'none';
        deleteUnitId = null;
    }

    function confirmDeleteUnit() {
        if (!deleteUnitId) return;
        var btn = document.getElementById('uom-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch('{{ url("/units-of-measure") }}/' + deleteUnitId, {
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
                closeUnitDelete();
                loadUnitsOfMeasure();
            } else {
                var warn = document.getElementById('uom-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('uom-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    // ==================== ESTADO DE LOS GRUPOS ====================

    var allGroupStatuses = [];
    var deleteGroupStatusId = null;

    function loadGroupStatuses() {
        document.getElementById('groupstatuses-loading').style.display = 'block';
        document.getElementById('groupstatuses-empty').style.display = 'none';
        document.getElementById('groupstatuses-table-wrap').style.display = 'none';

        fetch('{{ url("/group-statuses") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            allGroupStatuses = data;
            renderGroupStatuses(data);

            var summary = document.getElementById('groupstatuses-summary');
            if (summary) {
                var enUso = data.filter(function(s) { return (s.groups_count || 0) > 0; }).length;
                summary.textContent = data.length + ' estados · ' + enUso + ' en uso por grupos';
            }
        })
        .catch(function() {
            document.getElementById('groupstatuses-loading').innerHTML = '<p style="font-size:13px;color:#dc2626;">Error al cargar estados.</p>';
        });
    }

    function renderGroupStatuses(statuses) {
        var tbody = document.getElementById('groupstatuses-tbody');
        var loading = document.getElementById('groupstatuses-loading');
        var empty = document.getElementById('groupstatuses-empty');
        var tableWrap = document.getElementById('groupstatuses-table-wrap');

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
                '<td style="padding:10px 16px;font-size:13px;color:#1f2937;font-weight:500;">' + escapeHtml(s.description) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;font-size:13px;color:#6b7280;">' + Number(s.groups_count || 0) + '</td>' +
                '<td style="padding:10px 16px;text-align:center;">' +
                    '<button onclick="editGroupStatus(' + s.id + ')" title="Editar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;margin-right:4px;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#D24C19\';this.style.color=\'#D24C19\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>' +
                    '</button>' +
                    '<button onclick="openGroupStatusDelete(' + s.id + ', \'' + escapeHtml(s.description).replace(/'/g, "\\'") + '\')" title="Eliminar" style="background:none;border:1px solid #d1d5db;border-radius:4px;padding:4px 8px;cursor:pointer;color:#6b7280;font-size:12px;transition:all 0.2s;" onmouseover="this.style.borderColor=\'#dc2626\';this.style.color=\'#dc2626\';" onmouseout="this.style.borderColor=\'#d1d5db\';this.style.color=\'#6b7280\';">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>' +
                    '</button>' +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function filterGroupStatuses() {
        var q = document.getElementById('groupstatus-search').value.toLowerCase();
        var filtered = allGroupStatuses.filter(function(s) {
            return s.description.toLowerCase().indexOf(q) !== -1;
        });
        renderGroupStatuses(filtered);
    }

    function openGroupStatusModal(id, description) {
        document.getElementById('grupostatus-id').value = id || '';
        document.getElementById('grupostatus-description').value = description || '';
        document.getElementById('grupostatus-description-error').style.display = 'none';
        document.getElementById('grupostatus-modal-title').textContent = id ? 'Editar Estado' : 'Nuevo Estado';
        document.getElementById('grupostatus-submit-btn').textContent = id ? 'Actualizar' : 'Guardar';
        document.getElementById('grupostatus-modal-overlay').style.display = 'flex';
        document.getElementById('grupostatus-description').focus();
    }

    function closeGroupStatusModal() {
        document.getElementById('grupostatus-modal-overlay').style.display = 'none';
    }

    function editGroupStatus(id) {
        var s = allGroupStatuses.find(function(st) { return st.id === id; });
        if (s) openGroupStatusModal(s.id, s.description);
    }

    function submitGroupStatus(e) {
        e.preventDefault();
        var id = document.getElementById('grupostatus-id').value;
        var value = document.getElementById('grupostatus-description').value.trim();
        var errorEl = document.getElementById('grupostatus-description-error');
        var submitBtn = document.getElementById('grupostatus-submit-btn');

        errorEl.style.display = 'none';

        if (!value) {
            errorEl.textContent = 'El estado es obligatorio.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = id ? 'Actualizando...' : 'Guardando...';

        var url = id ? '{{ url("/group-statuses") }}/' + id : '{{ url("/group-statuses") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ description: value })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.textContent = id ? 'Actualizar' : 'Guardar';

            if (data.errors) {
                errorEl.textContent = data.errors.description ? data.errors.description[0] : 'Error de validación.';
                errorEl.style.display = 'block';
                return;
            }

            if (data.success) {
                closeGroupStatusModal();
                loadGroupStatuses();
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

    function openGroupStatusDelete(id, name) {
        deleteGroupStatusId = id;
        document.getElementById('grupostatus-delete-name').textContent = name;
        document.getElementById('grupostatus-delete-warning').style.display = 'none';
        document.getElementById('grupostatus-delete-overlay').style.display = 'flex';
    }

    function closeGroupStatusDelete() {
        document.getElementById('grupostatus-delete-overlay').style.display = 'none';
        deleteGroupStatusId = null;
    }

    function confirmDeleteGroupStatus() {
        if (!deleteGroupStatusId) return;
        var btn = document.getElementById('grupostatus-delete-btn');
        btn.disabled = true;
        btn.textContent = 'Eliminando...';

        fetch('{{ url("/group-statuses") }}/' + deleteGroupStatusId, {
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
                closeGroupStatusDelete();
                loadGroupStatuses();
            } else {
                var warn = document.getElementById('grupostatus-delete-warning');
                warn.textContent = data.message || 'No se pudo eliminar.';
                warn.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Eliminar';
            var warn = document.getElementById('grupostatus-delete-warning');
            warn.textContent = 'Error de conexión.';
            warn.style.display = 'block';
        });
    }

    // ==================== MI PANEL / MI PERFIL ====================

    var profileData = null;
    var profileProvider = null;
    var profileIsPrestador = false;
    var profileEsPrestador = {{ $esPerfilPrestador ? 'true' : 'false' }};
    var profilePanelInicial = '{{ $perfilPanelInicial }}';
    var profileDireccionCargada = false;
    var profileCanAdvertise = false;

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
        imagenes: 'Publicidad',
        categorias: 'Categorias',
        direccion: 'Mi Dirección',
        usuarios: 'Usuarios',
        cuenta: 'Contraseñas'
    };

    var profileUsuariosActive = false;
    var profilePrevUser = null;
    var profilePrevScope = 'all';
    var profilePrevPanelId = null;
    var profilePrevPaneG = null;
    var profilePrevPaneP = null;
    var profilePrevCountG = null;
    var profilePrevCountP = null;

    function focusProfileAccountSelection() {
        userSelectScope = 'account';
        var apply = function() {
            var me = null;
            if (typeof authUserId !== 'undefined' && authUserId !== null && authUserId !== '') {
                me = allUsers.find(function(x) { return String(x.id) === String(authUserId); });
            }
            if (!me && authEmail) {
                var email = String(authEmail).toLowerCase();
                me = allUsers.filter(function(x) { return x.email && String(x.email).toLowerCase() === email; })
                    .sort(function(a, b) { return Number(a.id) - Number(b.id); })[0] || null;
            }
            if (!me) return;
            selectedUser = me;
            renderUserAccount(me);
            switchUserAccountTab('gestion');
        };
        if (!allUsers.length) {
            loadUsers().then(apply);
        } else {
            apply();
        }
    }

    function mountProfileUsuariosPanel() {
        var host = document.getElementById('user-panel-cuenta');
        var slot = document.getElementById('profile-usuarios-slot');
        if (host && slot) {
            while (host.firstChild) slot.appendChild(host.firstChild);
        }
        if (!profileUsuariosActive) {
            profileUsuariosActive = true;
            profilePrevUser = selectedUser;
            profilePrevScope = userSelectScope;
            var prevPanel = document.querySelector('.user-panel.active');
            profilePrevPanelId = prevPanel ? prevPanel.id : null;
            if (host) host.classList.add('active');
            var g = document.getElementById('user-account-pane-gestion');
            var p = document.getElementById('user-account-pane-paginas');
            var cg = document.getElementById('user-tab-count-gestion');
            var cp = document.getElementById('user-tab-count-paginas');
            profilePrevPaneG = g ? g.innerHTML : null;
            profilePrevPaneP = p ? p.innerHTML : null;
            profilePrevCountG = cg ? cg.textContent : null;
            profilePrevCountP = cp ? cp.textContent : null;
        }
        focusProfileAccountSelection();
    }

    function unmountProfileUsuariosPanel() {
        var host = document.getElementById('user-panel-cuenta');
        var slot = document.getElementById('profile-usuarios-slot');
        if (host && slot) {
            while (slot.firstChild) host.appendChild(slot.firstChild);
        }
        if (!profileUsuariosActive) return;
        profileUsuariosActive = false;
        if (host) host.classList.remove('active');
        if (profilePrevPanelId) {
            var prevPanel = document.getElementById(profilePrevPanelId);
            if (prevPanel) prevPanel.classList.add('active');
        }
        profilePrevPanelId = null;
        selectedUser = profilePrevUser;
        userSelectScope = profilePrevScope;
        if (selectedUser) {
            renderUserAccount(selectedUser);
        } else {
            var g = document.getElementById('user-account-pane-gestion');
            var p = document.getElementById('user-account-pane-paginas');
            var cg = document.getElementById('user-tab-count-gestion');
            var cp = document.getElementById('user-tab-count-paginas');
            if (g && profilePrevPaneG !== null) g.innerHTML = profilePrevPaneG;
            if (p && profilePrevPaneP !== null) p.innerHTML = profilePrevPaneP;
            if (cg && profilePrevCountG !== null) cg.textContent = profilePrevCountG;
            if (cp && profilePrevCountP !== null) cp.textContent = profilePrevCountP;
        }
        updateCreateUserBtnVisibility();
    }

    function showProfilePanel(key) {
        if (key === 'imagenes' && !profileCanAdvertise) key = profileIsPrestador ? 'negocio' : 'cuenta';
        if (key === 'usuarios' && !document.querySelector('.sidebar-link[data-panel="usuarios"]')) key = 'cuenta';
        if (key === 'usuarios') mountProfileUsuariosPanel();
        else unmountProfileUsuariosPanel();
        if (key === 'direccion') loadAddress();
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
        if (!profileEsPrestador) {
            if (profilePanelInicial === 'direccion') showProfilePanel('direccion');
            return;
        }

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
            var typeObj = user.type_user || user.typeUser || null;
            profileIsPrestador = typeObj && typeObj.description === 'Prestador';
            var statusObj = user.status || user.user_status || null;
            var statusName = String((statusObj && (statusObj.status || statusObj.description)) || '').trim().toLowerCase();
            profileCanAdvertise = !!profileIsPrestador && ADVERTISING_STATUSES.indexOf(statusName) !== -1;

            document.getElementById('profile-name').value = user.name || '';
            document.getElementById('profile-email').value = user.email || '';
            setProfileStatusBadge(user);
            document.getElementById('profile-type').textContent = typeObj ? typeObj.description : '-';

            var toggleSidebarLink = function (id, visible) {
                var link = document.getElementById(id);
                if (link) link.style.display = visible ? '' : 'none';
            };

            toggleSidebarLink('sidebar-link-negocio', profileIsPrestador);
            toggleSidebarLink('sidebar-link-horarios', profileIsPrestador);
            toggleSidebarLink('sidebar-link-servicios', profileIsPrestador);
            toggleSidebarLink('sidebar-link-imagenes', profileCanAdvertise);
            toggleSidebarLink('sidebar-link-categorias', profileIsPrestador);

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
        var stObj = user.status || user.user_status || null;
        var st = stObj ? (stObj.status || stObj.description || '-') : '-';
        el.textContent = st;
        var stLower = String(st).toLowerCase();
        if (stLower === 'active' || stLower === 'activo' || stLower === 'activa') {
            el.style.cssText = 'display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;font-size:12px;font-weight:600;';
        } else if (stLower === 'inactive' || stLower === 'inactivo' || stLower === 'inactiva') {
            el.style.cssText = 'display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:12px;font-weight:600;';
        } else if (stLower === 'prueba') {
            el.style.cssText = 'display:inline-flex;align-items:center;padding:6px 12px;border-radius:9999px;background:#fffbeb;border:1px solid #fde68a;color:#b45309;font-size:12px;font-weight:600;';
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
            renderProviderSubgroups(list, data, { msgId: 'categorias-msg' });
        })
        .catch(function() {
            loading.textContent = 'Error al cargar categorías.';
        });
    }

    function renderProviderSubgroups(list, data, ctx) {
        ctx = ctx || {};
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
                    toggleSubgroup(sub.id, label, cb, ctx);
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
    }

    function toggleSubgroup(subgroupId, labelEl, cb, ctx) {
        ctx = ctx || {};
        var msgId = ctx.msgId || 'categorias-msg';
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
                if (ctx.onSelectionChange) ctx.onSelectionChange(data.selected);
                showMsg(msgId, data.message, true);
            } else {
                cb.checked = !cb.checked;
                showMsg(msgId, data.message || 'Error al guardar.', false);
            }
        })
        .catch(function() {
            cb.checked = !cb.checked;
            showMsg(msgId, 'Error de conexión.', false);
        });
    }

    // ---------- Primer ingreso: categorías obligatorias ----------

    var onboardingSelected = [];
    var onboardingAvailable = true;

    function checkCategoryOnboarding() {
        fetch('{{ url("/profile/subgroups") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            if (data.selected && data.selected.length > 0) return;
            openCategoryOnboarding();
        })
        .catch(function() {});
    }

    function openCategoryOnboarding() {
        var overlay = document.getElementById('onboard-cats-overlay');
        if (!overlay) return;

        onboardingSelected = [];
        updateOnboardingHint();
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        var list = document.getElementById('onboard-cats-list');
        var loading = document.getElementById('onboard-cats-loading');
        list.style.display = 'none';
        list.innerHTML = '';
        loading.style.display = 'block';
        loading.textContent = 'Cargando categorías...';

        fetch('{{ url("/profile/subgroups") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            list.style.display = 'block';
            onboardingSelected = (data.selected || []).slice();
            onboardingAvailable = false;
            (data.groups || []).forEach(function(g) {
                if (g.subgroups && g.subgroups.length > 0) onboardingAvailable = true;
            });
            updateOnboardingHint();
            renderProviderSubgroups(list, data, {
                msgId: 'onboard-cats-msg',
                onSelectionChange: function(selected) {
                    onboardingSelected = (selected || []).slice();
                    updateOnboardingHint();
                }
            });
        })
        .catch(function() {
            loading.textContent = 'No pudimos cargar las categorías. Recargá la página para intentar de nuevo.';
            onboardingAvailable = false;
            updateOnboardingHint();
        });
    }

    function updateOnboardingHint() {
        var hint = document.getElementById('onboard-cats-hint');
        if (!hint) return;
        if (!onboardingAvailable) {
            hint.textContent = 'No hay categorías disponibles ahora. Cerrá esta ventana y volvé a intentar más tarde.';
            hint.style.color = '#64748b';
            return;
        }
        var count = onboardingSelected.length;
        if (count > 0) {
            hint.textContent = 'Listo: seleccionaste ' + count + (count === 1 ? ' categoría.' : ' categorías.') + ' Ya podés cerrar esta ventana.';
            hint.style.color = '#059669';
        } else {
            hint.textContent = 'Debés seleccionar al menos una categoría para cerrar esta ventana.';
            hint.style.color = '#dc2626';
        }
    }

    function closeOnboarding() {
        if (onboardingAvailable && onboardingSelected.length === 0) {
            showMsg('onboard-cats-msg', 'Elegí al menos una categoría para poder continuar.', false);
            return;
        }
        var overlay = document.getElementById('onboard-cats-overlay');
        if (overlay) overlay.style.display = 'none';
        document.body.style.overflow = '';
        loadProviderSubgroups();
    }

    // ---------- Tab 4: Cuenta y perfil ----------

    var userAccountConfigLoaded = false;
    var userAccountConfigUserId = null;
    var userAccountConfigIsAuth = false;

    function fillUserAccountConfig(user) {
        if (!user) return;
        var isAuth = !authEmail || (user.email && String(user.email).toLowerCase() === String(authEmail).toLowerCase());
        userAccountConfigIsAuth = isAuth;
        userAccountConfigUserId = user.id || null;

        var pwCard = document.getElementById('user-cfg-password-card');
        if (pwCard) pwCard.style.display = '';
        var curField = document.getElementById('user-cfg-pw-current-field');
        if (curField) curField.style.display = isAuth ? '' : 'none';
        var pwSub = document.getElementById('user-cfg-pw-sub');
        if (pwSub) {
            pwSub.textContent = isAuth
                ? 'Usá una contraseña segura de al menos 8 caracteres.'
                : 'Establecé una nueva contraseña para este usuario (mínimo 8 caracteres).';
        }

        ['user-cfg-pw-current', 'user-cfg-pw-new', 'user-cfg-pw-confirm'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });
        ['user-cfg-pw-current-error', 'user-cfg-pw-new-error', 'user-cfg-pw-confirm-error'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.style.display = 'none';
        });
        ['user-cfg-pw-current', 'user-cfg-pw-new', 'user-cfg-pw-confirm'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.classList.remove('err');
        });
        var pwMsg = document.getElementById('user-cfg-pw-msg');
        if (pwMsg) { pwMsg.style.display = 'none'; pwMsg.textContent = ''; }
    }

    function loadUserAccountConfig() {
        var pwCard = document.getElementById('user-cfg-password-card');
        if (!pwCard) return;

        var principal = getPrincipalAccountUser() || selectedUser;
        if (principal && principal.id) {
            userAccountConfigUserId = principal.id;
            fetch('{{ url("/admin/users") }}/' + principal.id, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(function(r) { return r.json(); })
            .then(function(fresh) {
                if (!fresh || fresh.error) {
                    fillUserAccountConfig(principal);
                    return;
                }
                var idx = allUsers.findIndex(function(x) { return String(x.id) === String(fresh.id); });
                if (idx !== -1) allUsers[idx] = fresh;
                if (selectedUser && String(selectedUser.id) === String(fresh.id)) selectedUser = fresh;
                userAccountConfigLoaded = true;
                fillUserAccountConfig(fresh);
            })
            .catch(function() {
                fillUserAccountConfig(principal);
            });
            return;
        }

        userAccountConfigUserId = null;
        fetch('{{ url("/profile") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(function(r) { return r.json(); })
        .then(function(user) {
            profileData = user;
            userAccountConfigLoaded = true;
            fillUserAccountConfig(user);
        })
        .catch(function() {
            fillUserAccountConfig({ id: null, email: authEmail });
        });
    }

    function submitUserAccountPassword() {
        var curEl = document.getElementById('user-cfg-pw-current');
        var nwEl = document.getElementById('user-cfg-pw-new');
        var conEl = document.getElementById('user-cfg-pw-confirm');
        var errCur = document.getElementById('user-cfg-pw-current-error');
        var errNew = document.getElementById('user-cfg-pw-new-error');
        var errCon = document.getElementById('user-cfg-pw-confirm-error');
        var curField = document.getElementById('user-cfg-pw-current-field');
        var curVisible = !curField || curField.style.display !== 'none';
        var cur = curEl ? curEl.value : '';
        var nw = nwEl.value;
        var con = conEl.value;
        var principal = getPrincipalAccountUser() || selectedUser;
        var targetId = (principal && principal.id) || userAccountConfigUserId;
        var isAuthMode = userAccountConfigIsAuth || !targetId ||
            (authEmail && principal && principal.email && String(principal.email).toLowerCase() === String(authEmail).toLowerCase());

        [errCur, errNew, errCon].forEach(function(e) { e.style.display = 'none'; });
        [curEl, nwEl, conEl].forEach(function(e) { if (e) e.classList.remove('err'); });

        if (isAuthMode && curVisible && !cur) {
            curEl.classList.add('err');
            errCur.textContent = 'Ingresá tu contraseña actual.';
            errCur.style.display = 'block';
            return;
        }
        if (nw.length < 8) {
            nwEl.classList.add('err');
            errNew.textContent = 'Mínimo 8 caracteres.';
            errNew.style.display = 'block';
            return;
        }
        if (nw !== con) {
            conEl.classList.add('err');
            errCon.textContent = 'Las contraseñas no coinciden.';
            errCon.style.display = 'block';
            return;
        }

        var doneOk = function(message) {
            if (curEl) curEl.value = '';
            nwEl.value = '';
            conEl.value = '';
            showMsg('user-cfg-pw-msg', message || 'Contraseña actualizada correctamente.', true);
        };

        if (isAuthMode) {
            fetch('{{ url("/profile/password") }}', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ current_password: cur, password: nw, password_confirmation: con })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.errors) {
                    if (data.errors.current_password && curEl) { curEl.classList.add('err'); errCur.textContent = data.errors.current_password[0]; errCur.style.display = 'block'; }
                    if (data.errors.password) { nwEl.classList.add('err'); errNew.textContent = data.errors.password[0]; errNew.style.display = 'block'; }
                    return;
                }
                if (data.success) doneOk(data.message);
            })
            .catch(function() {
                showMsg('user-cfg-pw-msg', 'Error de conexión.', false);
            });
            return;
        }

        if (!targetId) {
            showMsg('user-cfg-pw-msg', 'No se pudo identificar el usuario.', false);
            return;
        }

        var statusId = principal.user_status_id || (principal.status && principal.status.id) || null;
        var typeId = principal.type_user_id || (principal.type_user && principal.type_user.id) || null;
        fetch('{{ url("/admin/users") }}/' + targetId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({
                name: principal.name,
                email: principal.email,
                password: nw,
                user_status_id: statusId,
                type_user_id: typeId
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                if (data.errors.password) { nwEl.classList.add('err'); errNew.textContent = data.errors.password[0]; errNew.style.display = 'block'; }
                if (data.errors.name || data.errors.email) {
                    showMsg('user-cfg-pw-msg', (data.errors.name || data.errors.email)[0], false);
                }
                return;
            }
            if (data.success) {
                if (data.user) applyUserUpdate(data.user);
                doneOk('Contraseña del usuario actualizada correctamente.');
            }
        })
        .catch(function() {
            showMsg('user-cfg-pw-msg', 'Error de conexión.', false);
        });
    }

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

    var PW_EYE_ON = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>';
    var PW_EYE_OFF = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.774 3.162 10.066 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.63.6M17.25 12a7.5 7.5 0 0 1-12.75 3.75"/></svg>';

    function togglePwVis(id, btn) {
        var el = document.getElementById(id);
        if (!el) return;
        var show = el.type === 'password';
        el.type = show ? 'text' : 'password';
        btn.innerHTML = show ? PW_EYE_OFF : PW_EYE_ON;
    }

    function submitPassword() {
        var nwEl = document.getElementById('pw-new');
        var conEl = document.getElementById('pw-confirm');
        var errNew = document.getElementById('pw-new-error');
        var errCon = document.getElementById('pw-confirm-error');
        var nw = nwEl.value;
        var con = conEl.value;

        [errNew, errCon].forEach(function(e) { e.style.display = 'none'; });
        [nwEl, conEl].forEach(function(e) { e.classList.remove('err'); });

        if (nw.length < 8) { nwEl.classList.add('err'); errNew.textContent = 'Mínimo 8 caracteres.'; errNew.style.display = 'block'; return; }
        if (nw !== con) { conEl.classList.add('err'); errCon.textContent = 'Las contraseñas no coinciden.'; errCon.style.display = 'block'; return; }

        fetch('{{ url("/profile/password") }}', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ password: nw, password_confirmation: con })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.errors) {
                if (data.errors.password) { nwEl.classList.add('err'); errNew.textContent = data.errors.password[0]; errNew.style.display = 'block'; }
                if (data.errors.password_confirmation) { conEl.classList.add('err'); errCon.textContent = data.errors.password_confirmation[0]; errCon.style.display = 'block'; }
                return;
            }
            if (data.success) {
                nwEl.value = ''; conEl.value = '';
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
        if (profileDireccionCargada) return;
        profileDireccionCargada = true;

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
        .catch(function() { profileDireccionCargada = false; });
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
