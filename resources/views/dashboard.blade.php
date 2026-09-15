<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
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
                    <a href="#{{ $page->url }}" onclick="showDashSection('{{ $page->url }}')" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                        {{ $page->description }}
                    </a>
                @else
                    <div class="nav-dropdown" style="position:relative;">
                        <button class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;text-decoration:none;">
                            {{ $module->description }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div class="nav-dropdown-menu" style="display:none;position:absolute;top:100%;left:0;background:white;border-radius:0;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:180px;padding:6px 0;z-index:100;">
                            @foreach($module->pages as $page)
                            <a href="#{{ $page->url }}" onclick="closeDropdowns();showDashSection('{{ $page->url }}')" class="dropdown-item" style="display:block;padding:8px 16px;color:#1f2937;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;">
                                {{ $page->description }}
                            </a>
                            @endforeach
                            <div style="width:100%;height:1px;background:#e5e7eb;margin:4px 0;"></div>
                            <a href="#" onclick="closeDropdowns();showDashSection('perfil')" class="dropdown-item" style="display:block;padding:8px 16px;color:#1f2937;font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;">
                                Mi Perfil
                            </a>
                            <div style="width:100%;height:1px;background:#e5e7eb;margin:4px 0;"></div>
                            <a href="#" onclick="closeDropdowns();doLogout()" class="dropdown-item" style="display:block;padding:8px 16px;color:#dc2626;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;">
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
                            <input type="text" id="module-description" required maxlength="255" placeholder="Nombre del módulo" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='#D24C19'" onblur="this.style.borderColor='#d1d5db'" />
                            <p id="module-description-error" style="font-size:11px;color:#dc2626;margin:4px 0 0;display:none;"></p>
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
                    @elseif($page->url === 'paginas')
            <section id="dash-paginas" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin-bottom:12px;">{{ $page->description }}</h2>
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0;padding:40px;text-align:center;">
                    <p style="font-size:14px;color:#6b7280;margin:0;">Gestión de páginas - Próximamente.</p>
                </div>
            </section>
                    @elseif($page->url === 'tipo-usuarios')
            <section id="dash-tipo-usuarios" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin-bottom:12px;">{{ $page->description }}</h2>
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0;padding:40px;text-align:center;">
                    <p style="font-size:14px;color:#6b7280;margin:0;">Gestión de tipos de usuario - Próximamente.</p>
                </div>
            </section>
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
    .nav-dropdown:hover .nav-dropdown-menu { display: block !important; }
    .dropdown-item {
        text-decoration: none;
    }
    .dropdown-item:hover {
        background: #f3f4f6;
    }

    #modules-table tbody tr:hover {
        background: #f9fafb;
    }

    .btn-orange { transition: background 0.2s, color 0.2s, border-color 0.2s; }
    .btn-orange:hover {
        background: #ffffff !important;
        color: #D24C19 !important;
        border: 1px solid #D24C19 !important;
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

    function openModuleModal(id, description) {
        document.getElementById('module-id').value = id || '';
        document.getElementById('module-description').value = description || '';
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
        if (m) openModuleModal(m.id, m.description);
    }

    function submitModule(e) {
        e.preventDefault();
        var id = document.getElementById('module-id').value;
        var desc = document.getElementById('module-description').value.trim();
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
        };
    });
</script>
</html>