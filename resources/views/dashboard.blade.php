<!DOCTYPE html>
<html lang="es" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>PideAca - Mi Panel</title>
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-pideaca-32.png') }}?v=2">
        <link rel="icon" type="image/png" sizes="180x180" href="{{ asset('images/favicon-pideaca.png') }}?v=2">
        <link rel="shortcut icon" type="image/png" href="{{ asset('images/favicon-pideaca.png') }}?v=2">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon-pideaca.png') }}?v=2">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|inter:400,500,600,700,800" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://cdn.jsdelivr.net/npm/daisyui@5/dist/full.min.css" rel="stylesheet" />
        @endif
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    </head>
    <body style="margin:0;padding:0;background:#ffffff;min-height:100vh;display:flex;flex-direction:column;">

        <div style="background:#D24C19;height:2px;"></div>

        {{-- NAV DINAMICO --}}
        <nav style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);position:sticky;top:0;z-index:50;overflow:visible;">
            <div class="nav-container" style="width:98%;margin:0 auto;padding:1px 2px;display:flex;align-items:center;justify-content:flex-end;gap:6px;position:relative;">
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
                <script>console.log("NAVBAR_MODULOS_FILTRADOS:", @json($modules->map(function ($m) { return ['modulo' => $m->description, 'paginas' => $m->pages->pluck('description')->values()]; })->values()));</script>
                @foreach($modules->sortBy(function ($m) { return $m->description === 'Administrar' ? 1 : 0; }) as $module)
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <div class="nav-dropdown" data-module-id="{{ $module->id }}" style="position:relative;">
                    <button class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;text-decoration:none;">
                        @if($module->icon)
                        <span style="display:flex;align-items:center;">{!! $module->icon !!}</span>
                        @elseif($module->description === 'Comercio & Gastronomía')
                        <span style="display:flex;align-items:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
                        @elseif($module->description === 'Servicios del Hogar & Bienestar')
                        <span style="display:flex;align-items:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
                        @elseif($module->description === 'Reparto')
                        <span style="display:flex;align-items:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>
                        @elseif($module->description === 'Mercado')
                        <span style="display:flex;align-items:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39A2 2 0 0 0 9.66 17h8.66a2 2 0 0 0 2-1.61L23 6H6"></path></svg></span>
                        @elseif($module->description === 'Hogar')
                        <span style="display:flex;align-items:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></span>
                        @endif
                        {{ $module->description }}
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </button>
                    <div class="nav-dropdown-menu" style="display:none;position:absolute;top:100%;right:0;background:white;border-radius:0;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;padding:6px 0;z-index:100;">
                        @foreach($module->pages as $page)
                            @if($page->url)
                                @if($module->description === 'Reparto' && !str_starts_with($page->url, '/') && ($page->url === 'reportes' || $page->description === 'Reportes Repartidor'))
                        <a href="#" onclick="event.preventDefault();closeDropdowns();showDashSection('reportes-repartidor')" data-page-id="{{ $page->id }}" class="dropdown-item" style="display:block;padding:8px 16px;color:#1f2937;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;">
                            {{ $page->description }}
                        </a>
                                @elseif(str_starts_with($page->url, '/'))
                        <a href="{{ url($page->url) }}" data-page-id="{{ $page->id }}" class="dropdown-item" style="display:block;padding:8px 16px;color:#1f2937;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;">
                            {{ $page->description }}
                        </a>
                                @else
                        <a href="#" onclick="event.preventDefault();closeDropdowns();showDashSection('{{ $page->url }}')" data-page-id="{{ $page->id }}" class="dropdown-item" style="display:block;padding:8px 16px;color:#1f2937;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;">
                            {{ $page->description }}
                        </a>
                                @endif
                            @else
                        <div class="dropdown-item" data-page-id="{{ $page->id }}" title="Todavia no tiene seccion" style="display:block;padding:8px 16px;color:#9ca3af;font-size:13px;font-weight:500;white-space:nowrap;cursor:default;">
                            {{ $page->description }} <span style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">pronto</span>
                        </div>
                            @endif
                        @endforeach
                        @if($module->description === 'Administrar' || $loop->last)
<div style="width:100%;height:1px;background:#e5e7eb;margin:4px 0;"></div>
<a href="#" onclick="event.preventDefault();closeDropdowns();doLogout()" class="dropdown-item" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#ffffff'" style="display:block;padding:8px 16px;color:#000000;background:#ffffff;font-size:13px;font-weight:400;text-decoration:none;white-space:nowrap;transition:background 0.15s ease;">
    Cerrar Sesión
</a>
                        @endif
                    </div>
                </div>
                @endforeach
                @if($modules->isEmpty())
                <a href="#" onclick="event.preventDefault();doLogout()" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;">Cerrar Sesión</a>
                @endif
                <button id="menu-toggle" class="block md:hidden" onclick="toggleMenu()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;padding:4px 8px;">&#9776;</button>
            </div>
            <div id="mobile-menu" style="display:none;background:#ffffff;padding:10px 8px;position:absolute;top:100%;left:0;right:0;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <div style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:8px 0;">
                    <div style="width:100%;height:1px;background:#e5e7eb;"></div>
                    @foreach($modules->sortBy(function ($m) { return $m->description === 'Administrar' ? 1 : 0; }) as $module)
                        <button onclick="toggleMobileSub(this)" data-module-id="{{ $module->id }}" style="width:100%;background:none;border:none;font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;padding:8px 0;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;">
                            @if($module->icon)
                            <span style="display:flex;align-items:center;">{!! $module->icon !!}</span>
                            @elseif($module->description === 'Comercio & Gastronomía')
                            <span style="display:flex;align-items:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
                            @elseif($module->description === 'Servicios del Hogar & Bienestar')
                            <span style="display:flex;align-items:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
                            @elseif($module->description === 'Reparto')
                            <span style="display:flex;align-items:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></span>
                            @elseif($module->description === 'Mercado')
                            <span style="display:flex;align-items:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39A2 2 0 0 0 9.66 17h8.66a2 2 0 0 0 2-1.61L23 6H6"></path></svg></span>
                            @elseif($module->description === 'Hogar')
                            <span style="display:flex;align-items:center;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></span>
                            @endif
                            {{ $module->description }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div class="mobile-sub" style="display:none;width:100%;text-align:center;">
                            @foreach($module->pages as $page)
                                @if($page->url)
                                    @if($module->description === 'Reparto' && !str_starts_with($page->url, '/') && ($page->url === 'reportes' || $page->description === 'Reportes Repartidor'))
                            <a href="#" onclick="event.preventDefault();showDashSection('reportes-repartidor')" data-page-id="{{ $page->id }}" style="display:block;color:#1f2937;text-decoration:none;font-size:13px;font-weight:500;padding:6px 0;">{{ $page->description }}</a>
                                    @elseif(str_starts_with($page->url, '/'))
                            <a href="{{ url($page->url) }}" data-page-id="{{ $page->id }}" style="display:block;color:#1f2937;text-decoration:none;font-size:13px;font-weight:500;padding:6px 0;">{{ $page->description }}</a>
                                    @else
                            <a href="#" onclick="event.preventDefault();showDashSection('{{ $page->url }}')" data-page-id="{{ $page->id }}" style="display:block;color:#1f2937;text-decoration:none;font-size:13px;font-weight:500;padding:6px 0;">{{ $page->description }}</a>
                                    @endif
                                @else
                            <div data-page-id="{{ $page->id }}" title="Todavia no tiene seccion" style="display:block;color:#9ca3af;font-size:13px;font-weight:500;padding:6px 0;cursor:default;">{{ $page->description }} <span style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;">pronto</span></div>
                                @endif
                            @endforeach
                            @if($module->description === 'Administrar' || $loop->last)
                            <div style="width:60%;height:1px;background:#e5e7eb;margin:6px auto;"></div>
                            <button type="button" onclick="doLogout()" style="background:none;border:none;color:#000000;font-size:13px;font-weight:400;cursor:pointer;padding:6px 0;">Cerrar Sesión</button>
                            @endif
                        </div>
                    @endforeach
                    @if($modules->isEmpty())
                    <button type="button" onclick="doLogout()" style="background:none;border:none;color:#000000;font-size:13px;font-weight:400;cursor:pointer;padding:6px 0;">Cerrar Sesión</button>
                    @endif
                </div>
            </div>
        </nav>

        <main style="flex:1;">
            <div style="background:#D24C19;height:2px;"></div>

            {{-- DASHBOARD SECTIONS --}}
            <section id="dash-perfil" class="dash-section" style="display:none;width:100%;margin:0;padding:0;">
        <style>
            .nav-link svg, .mobile-menu svg {
                width: 20px !important;
                height: 20px !important;
                font-size: 20px !important;
                vertical-align: middle;
            }
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
                        $esPerfilRepartidor = strcasecmp((string) $user->typeUser?->description, 'Repartidor') === 0;
                        // App movil: la seccion de descarga solo existe si se abre desde un telefono Android.
                        $esAndroid = str_contains(strtolower((string) request()->header('User-Agent', '')), 'android');
                        $perfilDireccion = $user->primaryAddress;
                        $perfilTieneDireccion = $perfilDireccion
                            && trim((string) $perfilDireccion->street) !== ''
                            && trim((string) $perfilDireccion->number) !== '';
                        $perfilPanelInicial = $esPerfilPrestador ? 'negocio' : ($esPerfilCliente ? 'direccion' : ($esPerfilRepartidor ? 'vehiculo' : 'cuenta'));
                        // Modo gestion (Admin viendo ?managed=): estado en combo
                        // y correo editable. Sin gestionar, ambos de solo lectura.
                        $esModoGestionado = $user->isAdmin() && (bool) request()->query('managed');
                        // Solo el titular configura el negocio: el secundario hereda
                        // todo del principal (solo lectura) y nunca lo edita.
                        $esCuentaPrincipal = $user->isAccountPrincipal();
                        // Rol efectivo: la cuenta gestionada manda sobre la sesión.
                        $esRolNegocio = in_array($activeRole ?? '', ['prestador', 'comercio', 'comerciante', 'admin', 'administrador'], true);
                        $puedeConfigurarNegocio = (($esPerfilPrestador || $user->isAdmin()) && ($esCuentaPrincipal || $user->isAdmin())) && $esRolNegocio;
                        // Mi Dirección: solo el titular (o quien tenga el permiso explícito).
                        $puedeVerDireccion = $esCuentaPrincipal || ($user->typeUser && $user->hasPageUrl('direccion'));
                        if (! $perfilTieneDireccion && $esCuentaPrincipal) {
                            $perfilPanelInicial = 'direccion';
                        }
                        if (! $esCuentaPrincipal && $perfilPanelInicial === 'direccion') {
                            $perfilPanelInicial = 'cuenta';
                        }
                        if (! $puedeConfigurarNegocio && in_array($perfilPanelInicial, ['negocio', 'horarios', 'servicios', 'repartidores', 'retiros', 'imagenes', 'categorias'], true)) {
                            $perfilPanelInicial = 'cuenta';
                        }
                        $perfilEtiquetas = [
                            'negocio' => 'Datos del Negocio',
                            'direccion' => 'Mi Dirección',
                            'cuenta' => 'Contraseñas',
                            'billetera' => 'Billetera',
                            'vehiculo' => 'Mi Vehículo',
                            'repartidores' => 'Repartidores de confianza',
                            'retiros' => 'Retiros de repartidores',
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
                            <div id="profile-managed-badge" style="display:none;margin-top:12px;padding:10px 12px;background:#fff7ed;border:1px solid #fdba74;border-radius:4px;">
                                <div style="font-size:10px;font-weight:700;color:#9a3412;text-transform:uppercase;letter-spacing:.5px;">Administrando</div>
                                <div style="font-size:13px;font-weight:700;color:#0f172a;margin-top:2px;"><span id="profile-managed-name"></span> <span style="font-weight:500;color:#64748b;">(<span id="profile-managed-role"></span>)</span></div>
                                <button type="button" onclick="umExitManagedUser()" style="margin-top:8px;background:#fff;border:1px solid #fdba74;color:#D24C19;border-radius:4px;padding:6px 10px;font-size:11px;font-weight:700;cursor:pointer;">Volver a mi cuenta</button>
                            </div>
                        </div>

                        <nav style="flex:1;padding:12px 0;">
                            @if($puedeConfigurarNegocio)
                            <a href="#" onclick="event.preventDefault();showProfilePanel('negocio')" class="sidebar-link{{ $perfilPanelInicial === 'negocio' ? ' active' : '' }}" data-panel="negocio" id="sidebar-link-negocio">
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
                            @if($user->provider)
                            <a href="#" onclick="event.preventDefault();showProfilePanel('repartidores')" class="sidebar-link" data-panel="repartidores" id="sidebar-link-repartidores">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="18" r="3"/><circle cx="18" cy="18" r="3"/><path d="M6 18h6l3-9h3l2 5h2"/><path d="M9 9h5"/><path d="M14 5h3"/></svg>
                                <span>Repartidores de confianza</span>
                            </a>
                            <a href="#" onclick="event.preventDefault();showProfilePanel('retiros')" class="sidebar-link" data-panel="retiros" id="sidebar-link-retiros">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg>
                                <span>Retiros de repartidores</span>
                            </a>
                            @endif
                            <a href="#" onclick="event.preventDefault();showProfilePanel('imagenes')" class="sidebar-link" data-panel="imagenes" id="sidebar-link-imagenes">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                <span>Publicidad</span>
                            </a>
                            <a href="#" onclick="event.preventDefault();showProfilePanel('categorias')" class="sidebar-link" data-panel="categorias" id="sidebar-link-categorias">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path d="M6 6h.008v.008H6V6Z"/></svg>
                                <span>Categorias</span>
                            </a>
                            @endif
                            @if($esPerfilRepartidor)
                            <a href="#" onclick="event.preventDefault();showProfilePanel('vehiculo')" class="sidebar-link{{ $perfilPanelInicial === 'vehiculo' ? ' active' : '' }}" data-panel="vehiculo" id="sidebar-link-vehiculo">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                                <span>Mi Vehículo</span>
                            </a>
                            @endif
                            @if($puedeVerDireccion)
                            <a href="#" onclick="event.preventDefault();showProfilePanel('direccion')" class="sidebar-link{{ $perfilPanelInicial === 'direccion' ? ' active' : '' }}" data-panel="direccion" id="sidebar-link-direccion">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                <span>Mi Dirección</span>
                            </a>
                            @endif
                            @if(strcasecmp((string) $user->typeUser?->description, 'Cliente') !== 0 && (($user->typeUser && $user->hasPageUrl('usuarios')) || $user->isAccountPrincipal()))
                            <a href="#" onclick="event.preventDefault();showProfilePanel('usuarios')" class="sidebar-link" data-panel="usuarios" id="sidebar-link-usuarios">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                <span>Usuarios</span>
                            </a>
                            @endif
                            <a href="#" onclick="event.preventDefault();showProfilePanel('cuenta')" class="sidebar-link{{ $perfilPanelInicial === 'cuenta' ? ' active' : '' }}" data-panel="cuenta" id="sidebar-link-cuenta">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                                <span>Contraseñas</span>
                            </a>
                            <a href="#" onclick="event.preventDefault();showProfilePanel('billetera')" class="sidebar-link{{ $perfilPanelInicial === 'billetera' ? ' active' : '' }}" data-panel="billetera" id="sidebar-link-billetera">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg>
                                <span>Billetera</span>
                            </a>
                            @if($esAndroid && ! $esPerfilCliente)
                            <a href="#" onclick="event.preventDefault();showProfilePanel('app-movil')" class="sidebar-link" data-panel="app-movil" id="sidebar-link-app-movil">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M12 6v6m0 0-2.5-2.5M12 12l2.5-2.5"/></svg>
                                <span>App móvil</span>
                            </a>
                            @endif
                        </nav>
                    </aside>

                    <div id="profile-content" style="flex:1;display:flex;flex-direction:column;min-width:0;">
                        <div style="height:44px;background:#fff;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;padding:0 20px;gap:8px;position:sticky;top:60px;z-index:40;">
                            <button onclick="toggleProfileSidebar()" id="sidebar-toggle-btn" style="display:none;background:none;border:none;cursor:pointer;padding:4px;color:#0f172a;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                            </button>
                            <a href="{{ url('/') }}" style="font-size:12px;color:#64748b;text-decoration:none;" title="Ir al inicio del panel">Mi Panel</a>
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

                                @if($puedeConfigurarNegocio)
                                <div class="profile-panel{{ $perfilPanelInicial === 'negocio' ? ' active' : '' }}" data-panel="negocio">
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
                                        <div class="profile-field-grid">
                                            <div><label class="field-label">Teléfono</label><input type="text" id="provider-phone" class="field-input" placeholder="+54 11 ..." /></div>
                                            <div><label class="field-label">WhatsApp</label><input type="text" id="provider-whatsapp" class="field-input" placeholder="5411..." /></div>
                                            <div><label class="field-label">Zona de cobertura</label><input type="text" id="provider-zone" class="field-input" placeholder="Zona Norte, Centro..." /></div>
                                            <div><label class="field-label">Promoción</label><input type="text" id="provider-promo" class="field-input" placeholder="2x1 en empanadas..." /></div>
                                        </div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="tab1-msg"></span>
                                            <button type="button" onclick="submitProvider('tab1-msg')" title="Guardar negocio" aria-label="Guardar negocio" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg> Tarifa de envío</h3>
                                        <p class="pcard-sub">Vos decidís cuánto cobrar por el envío de tus pedidos. El repartidor se queda con el <strong>100%</strong> del monto.</p>
                                        <div class="profile-field-grid">
                                            <div>
                                                <label class="field-label" for="provider-delivery-mode">Modalidad</label>
                                                <select id="provider-delivery-mode" class="field-input" onchange="fdDeliverySync()">
                                                    <option value="global">Tarifa general de PideAca</option>
                                                    <option value="flat">Monto fijo propio</option>
                                                    <option value="distance">Por distancia (base + $/km)</option>
                                                </select>
                                            </div>
                                            <div id="provider-delivery-flat-wrap">
                                                <label class="field-label" for="provider-delivery-flat">Monto fijo del envío</label>
                                                <input type="number" min="0" step="0.01" id="provider-delivery-flat" class="field-input" placeholder="Ej: 3000" oninput="fdDeliverySync()" />
                                            </div>
                                        </div>
                                        <div id="provider-delivery-distance-wrap" style="display:none;margin-top:12px;">
                                            <div class="profile-field-grid">
                                                <div>
                                                    <label class="field-label" for="provider-delivery-base">Costo base</label>
                                                    <input type="number" min="0" step="0.01" id="provider-delivery-base" class="field-input" placeholder="Ej: 1000" oninput="fdDeliverySync()" />
                                                </div>
                                                <div>
                                                    <label class="field-label" for="provider-delivery-per-km">Precio por km</label>
                                                    <input type="number" min="0" step="0.01" id="provider-delivery-per-km" class="field-input" placeholder="Ej: 800" oninput="fdDeliverySync()" />
                                                </div>
                                                <div>
                                                    <label class="field-label" for="provider-delivery-min">Mínimo por envío</label>
                                                    <input type="number" min="0" step="0.01" id="provider-delivery-min" class="field-input" placeholder="Ej: 3000" oninput="fdDeliverySync()" />
                                                </div>
                                                <div>
                                                    <label class="field-label" for="provider-delivery-max">Techo por envío (0 = sin techo)</label>
                                                    <input type="number" min="0" step="0.01" id="provider-delivery-max" class="field-input" placeholder="Ej: 8000" oninput="fdDeliverySync()" />
                                                </div>
                                            </div>
                                        </div>
                                        <p id="provider-delivery-preview" style="margin:12px 0 0;font-size:12px;line-height:1.5;color:#0c2a4d;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:9px 12px;"></p>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="delivery-msg"></span>
                                            <button type="button" onclick="submitProvider('delivery-msg')" title="Guardar tarifa de envío" aria-label="Guardar tarifa de envío" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="profile-panel" data-panel="horarios">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg> Horarios de atención</h3>
                                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
                                            <button type="button" class="btn-secondary" onclick="copyMonFri()" style="display:flex;align-items:center;gap:6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Aplicar Lunes a Viernes</button>
                                        </div>
                                        <div id="hours-list" style="display:flex;flex-direction:column;gap:8px;"></div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="tab2-msg"></span>
                                            <button type="button" onclick="submitProvider('tab2-msg')" title="Guardar horarios" aria-label="Guardar horarios" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="profile-panel" data-panel="servicios">
                                    <div class="pcard">
                                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:2px;">
                                            <div><h3 class="pcard-title" style="margin-bottom:0;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path d="M6 6h.008v.008H6V6Z"/></svg> Servicios</h3></div>
                                            <button type="button" onclick="openServiceModal()" title="Agregar servicio" aria-label="Agregar servicio" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                            </button>
                                        </div>
                                        <div id="services-loading" style="text-align:center;padding:20px;color:#94a3b8;font-size:12px;">Cargando servicios...</div>
                                        <div id="services-empty" style="display:none;text-align:center;padding:24px;color:#94a3b8;font-size:12px;border:1px dashed #e2e8f0;border-radius:1rem;">Todavía no agregaste servicios.</div>
                                        <div id="services-list" style="display:none;flex-direction:column;gap:10px;margin-top:16px;"></div>
                                    </div>
                                </div>

                                @if($user->provider)
                                <div class="profile-panel" data-panel="repartidores">
                                    @include('dashboard.partials.repartidores-confianza')
                                </div>
                                <div class="profile-panel" data-panel="retiros">
                                    @include('dashboard.partials.retiros-comercio')
                                </div>
                                @endif

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

                                    <div class="pcard" id="service-types-card" style="display:none;margin-top:16px;">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg> Tipos de servicio</h3>
                                        <p class="pcard-sub">Marcá los tipos de servicio que ofrecés: urgencias, citas programadas y/o presupuestos. Podés elegir uno o varios.</p>
                                        <div id="service-types-loading" style="text-align:center;padding:20px;color:#94a3b8;font-size:12px;">Cargando tipos de servicio...</div>
                                        <div id="service-types-list" style="display:none;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;"></div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:16px;">
                                            <span class="save-msg" id="service-types-msg"></span>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($puedeVerDireccion)
                                <div class="profile-panel{{ $perfilPanelInicial === 'direccion' ? ' active' : '' }}" data-panel="direccion">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg> Mi Dirección</h3>
                                        <div class="profile-field-grid">
                                            <div>
                                                <label class="field-label">País</label>
                                                <input type="hidden" id="addr-country-id" value="" />
                                                <select id="addr-country" class="field-input" disabled style="cursor:not-allowed;background-color:#f1f3f5;color:#6b7280;">
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
                                            <button type="button" onclick="submitAddress()" title="Guardar dirección" aria-label="Guardar dirección" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="profile-panel" data-panel="usuarios">
                                    <div id="profile-usuarios-slot"></div>
                                </div>

                                @if($esPerfilRepartidor)
                                @php
                                    $selectedVehicle = $user->vehicle_type ?: \App\Models\User::DEFAULT_VEHICLE_TYPE;
                                @endphp
                                <div class="profile-panel{{ $perfilPanelInicial === 'vehiculo' ? ' active' : '' }}" data-panel="vehiculo">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg> Mi Vehículo</h3>
                                        <p class="pcard-sub">Configurá con qué unidad trabajás. Se usa para asignarte los pedidos y se guarda automáticamente.</p>
                                        <div class="profile-field-grid">
                                            <div>
                                                <label class="field-label" for="profile-vehicle">Tipo de vehículo</label>
                                                <select id="profile-vehicle" class="field-input">
                                                    @foreach(\App\Models\User::VEHICLE_TYPES as $vehicleValue => $vehicleLabel)
                                                        <option value="{{ $vehicleValue }}" @selected($selectedVehicle === $vehicleValue)>{{ $vehicleLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="err-msg" id="profile-vehicle-error"></span>
                                            </div>
                                            <div>
                                                <label class="field-label" for="profile-vehicle-plate">Patente / Placa <span style="font-weight:500;text-transform:none;letter-spacing:0;color:#94a3b8;">(opcional)</span></label>
                                                <input type="text" id="profile-vehicle-plate" class="field-input" value="{{ $user->vehicle_plate }}" placeholder="Ej: AB123CD" maxlength="20" autocomplete="off" />
                                                <span class="err-msg" id="profile-vehicle-plate-error"></span>
                                            </div>
                                        </div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="profile-vehicle-msg" aria-live="polite"></span>
                                            <button type="button" onclick="submitVehicle()" title="Guardar vehículo" aria-label="Guardar vehículo" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <div class="profile-panel{{ $perfilPanelInicial === 'cuenta' ? ' active' : '' }}" data-panel="cuenta">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg> Datos personales</h3>
                                        <div class="profile-field-grid">
                                            <div><label class="field-label">Nombre</label><input type="text" id="profile-name" class="field-input" value="{{ $user->name }}" /><span class="err-msg" id="profile-name-error"></span></div>
                                            <div><label class="field-label">Correo</label><input type="email" id="profile-email" class="field-input" value="{{ $user->email }}" @if(!$esModoGestionado) readonly style="background:#f3f4f6;color:#6b7280;cursor:not-allowed;" @endif /><span class="err-msg" id="profile-email-error"></span></div>
                                            <div><label class="field-label">Estado</label><span id="profile-status" class="status-badge" style="{{ $estadoPerfilEstilo }}">{{ $estadoPerfil }}</span>
                                        @if($esModoGestionado)
                                            <select id="profile-status-select" class="field-input" style="margin-top:8px;"><option value="">Cargando…</option></select><span class="err-msg" id="profile-status-error"></span>
                                        @endif
                                        </div>
                                            <div><label class="field-label">Tipo de usuario</label><span id="profile-type" class="status-badge">{{ $user->typeUser?->description ?? '-' }}</span></div>
                                        </div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="profile-msg"></span>
                                            <button type="button" onclick="submitProfile()" title="Guardar" aria-label="Guardar" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg> Mi suscripción</h3>
                                        <div id="profile-membership-empty" style="font-size:12.5px;color:#64748b;">Solo los prestadores Hogar tienen suscripción mensual.</div>
                                        <div id="profile-membership-box" style="display:none;font-size:12.5px;line-height:1.7;color:#0f172a;"></div>
                                        <div id="profile-membership-matrix" style="display:none;margin-top:10px;font-size:12px;color:#334155;background:#f8fafc;border:1px solid #e2e8f0;padding:10px 12px;border-radius:4px;"></div>
                                    </div>
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg> Cambiar contraseña</h3>
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
                                            <button type="button" onclick="submitPassword()" title="Guardar contraseña" aria-label="Guardar contraseña" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="profile-panel{{ $perfilPanelInicial === 'billetera' ? ' active' : '' }}" data-panel="billetera">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg> Billetera de cobro</h3>
                                        <div id="wallet-operate-banner" style="display:none;font-size:12.5px;padding:9px 12px;border-radius:0;margin-bottom:12px;"></div>
                                        <div id="wallet-list" style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;"></div>
                                        <div class="profile-field-grid">
                                            <div><label class="field-label">País</label><select id="wallet-country" class="field-input"><option value="AR">Argentina</option><option value="ES">España</option></select></div>
                                            <div><label class="field-label">Tipo de billetera</label><select id="wallet-type" class="field-input"><option value="">Cargando…</option></select><span class="err-msg" id="wallet-type-error"></span></div>
                                            <div><label class="field-label">Etiqueta (opcional)</label><input type="text" id="wallet-label" class="field-input" placeholder="Ej: Banco X" /><span class="err-msg" id="wallet-label-error"></span></div>
                                            <div><label class="field-label">Titular</label><input type="text" id="wallet-holder" class="field-input" placeholder="Nombre del titular" /><span class="err-msg" id="wallet-holder-error"></span></div>
                                            <div data-wfield="cbu"><label class="field-label">CBU (22 dígitos)</label><input type="text" id="wallet-cbu" class="field-input" inputmode="numeric" placeholder="Solo si cobrás por banco" /><span class="err-msg" id="wallet-cbu-error"></span></div>
                                            <div data-wfield="alias"><label class="field-label">Alias</label><input type="text" id="wallet-alias" class="field-input" placeholder="Opcional" /><span class="err-msg" id="wallet-alias-error"></span></div>
                                            <div data-wfield="ref" style="display:none;"><label class="field-label" id="wallet-ref-label">Referencia</label><input type="text" id="wallet-ref" class="field-input" placeholder="" /><span class="err-msg" id="wallet-ref-error"></span></div>
                                            <div data-wfield="iban"><label class="field-label">IBAN (solo exterior)</label><input type="text" id="wallet-iban" class="field-input" placeholder="Opcional" /><span class="err-msg" id="wallet-iban-error"></span></div>
                                            <div><label class="field-label">Banco</label><input type="text" id="wallet-bank" class="field-input" placeholder="Opcional" /><span class="err-msg" id="wallet-bank-error"></span></div>
                                        </div>
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:20px;">
                                            <span class="save-msg" id="wallet-msg"></span>
                                            <button type="button" onclick="submitPayoutAccount()" title="Agregar billetera" aria-label="Agregar billetera" style="background:#D24C19;color:#fff;border:none;padding:8px 14px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;">+ Agregar</button>
                                        </div>
                                        <p style="font-size:11.5px;line-height:1.5;color:#64748b;margin:12px 0 0;">Necesitás al menos una billetera activa para operar (recibir pedidos, trabajos o repartos). La primera que agregues queda como predeterminada para tus liquidaciones.</p>
                                    </div>
                                </div>
                                @if($esAndroid && ! $esPerfilCliente)
                                <div class="profile-panel" data-panel="app-movil">
                                    <div class="pcard">
                                        <h3 class="pcard-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e85d04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M12 6v6m0 0-2.5-2.5M12 12l2.5-2.5"/></svg> App móvil</h3>
                                        <p style="font-size:13px;color:#64748b;margin:0 0 12px;">Descargá la app en tu teléfono para operar desde donde estés.</p>
                                        <div><span class="save-msg" id="appmovil-msg"></span></div>
                                        <div id="appmovil-body"><div id="appmovil-checklist"><p style="font-size:13px;color:#64748b;">Cargando…</p></div><div id="appmovil-actions" style="margin-top:12px;" data-apk-url="{{ $androidApkUrl ?? '' }}"></div></div>
                                    </div>
                                </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>

                <div id="service-modal-overlay" style="display:none;position:fixed;inset:0;z-index:300;background:rgba(15,23,42,0.6);align-items:center;justify-content:center;">
                    <div style="background:#fff;border-radius:0;padding:24px;width:100%;max-width:420px;box-shadow:0 24px 60px rgba(15,23,42,0.2);box-sizing:border-box;">
                        <h3 id="service-modal-title" style="font-size:16px;font-weight:800;color:#0f172a;margin:0 0 16px;">Agregar servicio</h3>
                        <input type="hidden" id="service-edit-id" />
                        <div><label class="field-label">Nombre del servicio</label><input type="text" id="service-name" class="field-input" placeholder="Ej: Pizza a la piedra" /><span class="err-msg" id="service-name-error"></span></div>
                        <div style="margin-top:20px;text-align:right;">
                            <span class="save-msg err" id="service-msg" style="display:none;margin-right:8px;"></span>
                            <button type="button" onclick="closeServiceModal()" title="Cancelar" aria-label="Cancelar" style="background:#fff;color:#374151;border:1px solid #d1d5db;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;margin-right:8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                            <button type="button" onclick="submitService()" title="Guardar" aria-label="Guardar" style="background:#D24C19;color:#fff;border:none;padding:8px;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;line-height:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </button>
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

            {{-- Vista operativa compartida de las ordenes de servicio del prestador. --}}
            {{-- Se incluye una sola vez (antes de las secciones) para que las paginas --}}
            {{-- "Ordenes de Servicio", "Gestionar Solicitudes" y "Ordenes y Gestion" --}}
            {{-- usen la misma UI. --}}
            @include('dashboard.partials.servicio-operativo')

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
                    @elseif($page->url === 'tipo-monedas')
            @include('dashboard.sections.tipo-monedas')
                    @elseif($page->url === 'consultas-sugerencias')
            @include('dashboard.sections.consultas-sugerencias')
                    @elseif($page->url === 'usuarios')
                    @elseif($page->url === 'mi-catalogo')
            @include('dashboard.sections.mi-catalogo')
                    @elseif($page->url === 'pedidos')
            @include('dashboard.sections.pedidos')
                    @elseif($page->url === 'gestionar-solicitudes')
            @include('dashboard.sections.gestionar-solicitudes')
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
                    @elseif($page->url === 'reportes')
                        @if($module->description === 'Reparto' || $page->description === 'Reportes Repartidor')
            @include('dashboard.sections.reportes-repartidor')
                        @else
            @include('dashboard.sections.reportes')
                        @endif
                    @elseif($page->url === 'reportes-hogar')
            <div id="dash-reportes-hogar" class="dash-section" style="display:none;width:100%;">
                @include('reports.hogar')
            </div>
                    @elseif($page->url === 'solicitar-servicio')
            @include('dashboard.sections.solicitar-servicio')
                    @elseif($page->url === 'mis-servicios')
            @include('dashboard.sections.mis-servicios')
                    @elseif($page->url === 'ordenes-servicio')
            @include('dashboard.sections.ordenes-servicio')
                    @elseif($page->url === 'ordenes-gestion')
            @include('dashboard.sections.ordenes-gestion')
                    @elseif($page->url === 'repartidor')
            @include('dashboard.sections.repartidor')
                    @elseif(in_array($page->url, ['/repartidor/reportes', 'repartidor/reportes', 'reportes-repartidor'], true))
            @include('dashboard.sections.reportes-repartidor')
                    @elseif($page->url === 'dinero')
            <div id="dash-dinero" class="dash-section" style="display:none;width:100%;">
                @include('dashboard.sections.dinero')
            </div>
                    @elseif($page->url === 'billeteras-pais')
            <div id="dash-billeteras-pais" class="dash-section" style="display:none;width:100%;">
                @include('dashboard.sections.billeteras-pais')
            </div>
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
    html { height: 100%; }
    /* El body debe poder crecer con el contenido: con height:100% la caja del
       body quedaba igual al viewport y el nav sticky se despegaba (header
       cortado) al scrollear mas alla de esa altura. */
    body { min-height: 100%; }

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
    .nav-link > span svg { width: 14px; height: 14px; }
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

    #menu-toggle { display: none !important; }

    @media (max-width: 768px) {
        .nav-link, .nav-separator, .nav-dropdown { display: none !important; }
        #menu-toggle { display: block !important; }
        .logo-img { width: 140px !important; height: 52px !important; }
        .nav-container { padding: 1px 8px !important; justify-content: space-between !important; }
        .tab-label { display: none !important; }
        #fd-myorders-range, #fd-orders-range {
            margin-left: 0 !important;
            width: 100% !important;
            justify-content: flex-start !important;
            margin-top: 8px !important;
            padding-bottom: 0 !important;
        }
    }
    @media (min-width: 769px) {
        #mobile-menu { display: none !important; }
    }
</style>
<script>
/* BOOTSTRAP: valores evaluados en servidor (mantener inline) */
window.APP_BASE = @json(url('/'));
    @include('partials.fd-money-js')
    var authEmail = @json($user->email);
    var authUserId = @json($user->id);
    var userParentModules = @json($permissionModules->values());
    var actorIsAdmin = {{ $user->isAdmin() ? 'true' : 'false' }};
    var UM_BASE_URL = '{{ url('/') }}';
    var profileEsPrestador = {{ $esPerfilPrestador ? 'true' : 'false' }};
    var profilePanelInicial = '{{ $perfilPanelInicial }}';
    var profilePuedeConfigurarNegocio = {{ $puedeConfigurarNegocio ? 'true' : 'false' }};
    var profilePuedeVerDireccion = {{ $puedeVerDireccion ? 'true' : 'false' }};
    var firstAuthorizedSection = '{{ $firstPageUrl }}';
    var VEHICLE_TYPES = @json(\App\Models\User::VEHICLE_TYPES);
    var VEHICLE_DEFAULT_TYPE = @json(\App\Models\User::DEFAULT_VEHICLE_TYPE);
window.__CSRF_TOKEN = '{{ csrf_token() }}';
window.__FD_DELIVERY_FEE = {{ (float) config('fastdelivery.delivery_fee') }};
</script>
<script src="{{ asset('js/dashboard-app-2.js') }}?v={{ filemtime(public_path('js/dashboard-app-2.js')) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof umSnapshotSessionNav === 'function') umSnapshotSessionNav();
        var origShow = showDashSection;
        showDashSection = function(key) {
            if (fdDireccionBloqueada && key !== 'perfil') {
                fdForzarVistaDireccion();
                return;
            }
            origShow(key);
            dockProfileSidebar(key === 'usuarios' && actorIsAdmin);
            if (key === 'modulos') loadModules();
            if (key === 'grupos') loadGroups();
            if (key === 'sub-grupos') loadSubGroups();
            if (key === 'estados-usuarios') loadUserStatuses();
            if (key === 'estados-grupos') loadGroupStatuses();
            if (key === 'unidades-medida') loadUnitsOfMeasure();
            if (key === 'tipo-monedas') loadCurrencies();
            if (key === 'consultas-sugerencias') loadContactInquiries();
            if (key === 'tipo-usuarios') loadTypeUsers();
            if (key === 'usuarios') { loadUsers(); }
            if (key === 'paginas') loadPages();
            if (key === 'perfil') loadProfile();
            if (key === 'mi-catalogo') fdCatShow();
            if (key === 'pedidos') { loadProviderOrders(); loadTrustedDrivers(); }
            if (key === 'inventario') loadInventory();
            if (key === 'comercios') loadComercios();
            if (key === 'mis-pedidos') loadMyOrders();
            if (key === 'finanzas') loadFinances();
            if (key === 'retencion') loadRetention();
            if (key === 'suscripciones') loadSubRevenue();
            if (key === 'reportes' && typeof fdRepOpen === 'function') fdRepOpen();
            if (key === 'mis-suscripciones') {
                // Al entrar por el nav se cierra el modal de nueva suscripcion.
                window.__fdSubReturnTo = null;
                var subOverlay = document.getElementById('mysub-form-overlay');
                if (subOverlay) subOverlay.style.display = 'none';
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
        // El enlace directo de la pagina unificada llega con ?section=... y
        // deja esa seccion abierta (si el usuario realmente la tiene).
        var requestedSection = new URLSearchParams(window.location.search).get('section');
        if (requestedSection === '/repartidor/reportes' || requestedSection === 'repartidor/reportes') {
            requestedSection = 'reportes-repartidor';
        }
        if (requestedSection && document.getElementById('dash-' + requestedSection)) {
            showDashSection(requestedSection);
        } else {
            showDashSection('perfil');
        }

        // El secundario hereda la dirección del principal y no pasa por
        // el bloqueo de dirección obligatoria: accede a sus rutas asignadas.
        @if($user->isAccountPrincipal() && !$user->isAdmin())
            fdVerificarDireccionGuard();
        @endif

        try {
            var managedParam = new URL(window.location.href).searchParams.get('managed');
            if (typeof actorIsAdmin !== 'undefined' && actorIsAdmin && managedParam && !umManagedId()) {
                umSetManagedUser(managedParam);
            }
        } catch (e) {}
        umRestoreManagedUser();

        @if (strcasecmp((string) $user->typeUser?->description, 'Prestador') === 0 && $user->isAccountPrincipal())
            // Prestador que todavía no eligió categorías: no deja avanzar hasta configurarlas.
            // Los usuarios secundarios heredan negocio/categorías del principal y omiten este modal.
            checkCategoryOnboarding();
        @endif
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        ['cur-symbol', 'cur-position', 'cur-space', 'cur-decimals', 'cur-decimal-sep', 'cur-thousand-sep'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', updateCurrencyPreview);
            el.addEventListener('change', updateCurrencyPreview);
        });
    });
</script>
</html>
