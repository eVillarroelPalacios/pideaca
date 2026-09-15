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
                <a href="/" style="margin-right:auto;z-index:60;">
                    <img class="logo-img" src="{{ asset('images/logo.png') }}" alt="PideAca" style="width:170px;height:58px;border-radius:0;object-fit:contain;" />
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="/" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    Inicio
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="#mi-perfil" onclick="showDashSection('perfil')" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    Mi Perfil
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="#mis-servicios" onclick="showDashSection('servicios')" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
                    Mis Servicios
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="#mis-pedidos" onclick="showDashSection('pedidos')" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    Mis Pedidos
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="#notificaciones" onclick="showDashSection('notificaciones')" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    Notificaciones
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <div class="user-badge" style="display:flex;align-items:center;gap:8px;padding:4px 12px;color:white;font-size:12px;border-radius:4px;background:rgba(255,255,255,0.1);">
                    <div style="width:28px;height:28px;border-radius:50%;background:#D24C19;color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div style="display:flex;flex-direction:column;line-height:1.2;">
                        <span style="font-weight:600;font-size:12px;">{{ $user->name }}</span>
                        <span style="font-size:10px;color:rgba(255,255,255,0.7);">{{ $user->email }}</span>
                    </div>
                </div>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <button type="button" onclick="doLogout()" class="header-btn" style="padding:2px 10px;background:transparent;color:white;border:1px solid rgba(255,255,255,0.5);border-radius:4px;font-size:11px;font-weight:600;cursor:pointer;">Cerrar Sesión</button>
                <button id="menu-toggle" onclick="toggleMenu()" style="display:none;background:none;border:none;color:white;font-size:24px;cursor:pointer;padding:4px 8px;">&#9776;</button>
            </div>
            <div id="mobile-menu" style="display:none;background:#ffffff;padding:10px 8px;position:absolute;top:100%;left:0;right:0;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <div style="display:flex;flex-direction:column;align-items:center;gap:12px;padding:8px 0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:#D24C19;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div style="text-align:left;">
                            <div style="font-size:13px;font-weight:600;color:#1f2937;">{{ $user->name }}</div>
                            <div style="font-size:10px;color:#6b7280;">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div style="width:100%;height:1px;background:#e5e7eb;"></div>
                    <a href="/" style="color:#1f2937;text-decoration:none;font-size:13px;font-weight:600;padding:6px 0;">Inicio</a>
                    <a href="#" onclick="showDashSection('perfil')" style="color:#1f2937;text-decoration:none;font-size:13px;font-weight:600;padding:6px 0;">Mi Perfil</a>
                    <a href="#" onclick="showDashSection('servicios')" style="color:#1f2937;text-decoration:none;font-size:13px;font-weight:600;padding:6px 0;">Mis Servicios</a>
                    <a href="#" onclick="showDashSection('pedidos')" style="color:#1f2937;text-decoration:none;font-size:13px;font-weight:600;padding:6px 0;">Mis Pedidos</a>
                    <a href="#" onclick="showDashSection('notificaciones')" style="color:#1f2937;text-decoration:none;font-size:13px;font-weight:600;padding:6px 0;">Notificaciones</a>
                    <div style="width:100%;height:1px;background:#e5e7eb;"></div>
                    <button type="button" onclick="doLogout()" style="background:none;border:none;color:#D24C19;font-size:13px;font-weight:600;cursor:pointer;padding:6px 0;">Cerrar Sesión</button>
                </div>
            </div>
        </nav>

        <main style="flex:1;">
            <div style="background:#D24C19;height:2px;"></div>

            {{-- WELCOME BANNER --}}
            <div style="background:linear-gradient(135deg,#0c2a4d 0%,#123b73 60%,#0c2a4d 100%);padding:32px 20px;text-align:center;">
                <div style="max-width:800px;margin:0 auto;display:flex;flex-direction:column;align-items:center;gap:10px;">
                    <div style="width:56px;height:56px;border-radius:50%;background:#D24C19;color:white;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <h1 style="color:white;font-size:24px;font-weight:700;margin:0;">Bienvenido, {{ $user->name }}</h1>
                    <p style="color:rgba(255,255,255,0.8);font-size:14px;margin:0;">Gestioná tus servicios, pedidos y perfil desde tu panel personal.</p>
                </div>
            </div>

            {{-- DASHBOARD SECTIONS --}}
            <section id="dash-perfil" class="dash-section" style="display:block;max-width:900px;margin:24px auto;padding:0 20px;">
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

            <section id="dash-servicios" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin-bottom:12px;">Mis Servicios</h2>
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0;padding:40px;text-align:center;">
                    <p style="font-size:14px;color:#6b7280;margin:0;">Próximamente podrás gestionar tus servicios aquí.</p>
                </div>
            </section>

            <section id="dash-pedidos" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin-bottom:12px;">Mis Pedidos</h2>
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0;padding:40px;text-align:center;">
                    <p style="font-size:14px;color:#6b7280;margin:0;">Próximamente podrás ver tus pedidos aquí.</p>
                </div>
            </section>

            <section id="dash-notificaciones" class="dash-section" style="display:none;max-width:900px;margin:24px auto;padding:0 20px;">
                <h2 style="font-size:18px;font-weight:700;color:#0c2a4d;margin-bottom:12px;">Notificaciones</h2>
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0;padding:40px;text-align:center;">
                    <p style="font-size:14px;color:#6b7280;margin:0;">No tienes notificaciones nuevas.</p>
                </div>
            </section>

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
    .nav-link:hover { background: rgba(255,255,255,0.1); }

    @media (max-width: 768px) {
        .nav-link, .nav-separator, .user-badge { display: none !important; }
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

    function showDashSection(key) {
        var sections = ['perfil', 'servicios', 'pedidos', 'notificaciones'];
        sections.forEach(function (k) {
            var el = document.getElementById('dash-' + k);
            if (el) el.style.display = (k === key) ? 'block' : 'none';
        });
        var menu = document.getElementById('mobile-menu');
        if (menu) menu.style.display = 'none';
    }

    function doLogout() {
        fetch('{{ route('logout') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(function () {
            window.close();
            setTimeout(function () { window.location.href = '{{ url('/') }}'; }, 500);
        });
    }
</script>
</html>