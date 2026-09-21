<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>PideAca</title>
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
        <div style="background:#f3f4f6;padding:10px 0;">
            <div style="max-width:1200px;margin:0 auto;padding:0 20px;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:14px;">
                    <a href="#" title="Facebook" style="color:#6b7280;transition:color 0.2s;" onmouseover="this.style.color='#1877F2'" onmouseout="this.style.color='#6b7280'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" title="Instagram" style="color:#6b7280;transition:color 0.2s;" onmouseover="this.style.color='#E4405F'" onmouseout="this.style.color='#6b7280'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    <a href="#" title="X / Twitter" style="color:#6b7280;transition:color 0.2s;" onmouseover="this.style.color='#000'" onmouseout="this.style.color='#6b7280'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" title="YouTube" style="color:#6b7280;transition:color 0.2s;" onmouseover="this.style.color='#FF0000'" onmouseout="this.style.color='#6b7280'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                    <a href="#" title="WhatsApp" style="color:#6b7280;transition:color 0.2s;" onmouseover="this.style.color='#25D366'" onmouseout="this.style.color='#6b7280'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
                </div>
                <div style="display:flex;align-items:center;gap:16px;">
                    <a href="tel:+595981234567" style="color:#6b7280;font-size:12px;text-decoration:none;display:flex;align-items:center;gap:5px;transition:color 0.2s;" onmouseover="this.style.color='#1f2937'" onmouseout="this.style.color='#6b7280'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                        +595 981 234567
                    </a>
                </div>
            </div>
        </div>
        {{-- NAV --}}
        <nav style="background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);position:sticky;top:0;z-index:50;overflow:visible;">
            <div class="nav-container" style="max-width:1200px;margin:0 auto;padding:1px 2px;display:flex;align-items:center;justify-content:space-between;gap:6px;position:relative;">
                <a href="/" onclick="window.location.reload(true);return false;" style="flex-shrink:0;z-index:60;">
                    <img class="logo-img" src="{{ asset('images/logo.png') }}" alt="PideAca" style="width:170px;height:58px;border-radius:0;object-fit:contain;" />
                </a>

                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                    <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                    <a href="#" onclick="showSection('quienes-somos')" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                        Quienes Somos
                    </a>
                    <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                    <a href="#" onclick="showSection('servicios')" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
                        Servicios
                    </a>
                    <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                    <a href="#" onclick="showSection('contactos')" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                        Contactos
                    </a>
                    <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                    <a href="#" class="header-btn" style="padding:2px 10px;background:#D24C19;color:white;border:1px solid #D24C19;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;cursor:pointer;">Registrate</a>
                    <a href="#" onclick="openLogin();return false;" class="header-btn" style="padding:2px 10px;background:#D24C19;color:white;border:1px solid #D24C19;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;cursor:pointer;">Entrar</a>
                    <button id="menu-toggle" onclick="toggleMenu()" style="display:none;background:none;border:none;color:white;font-size:24px;cursor:pointer;padding:4px 8px;">&#9776;</button>
                </div>
            </div>
            <div id="mobile-menu" style="display:none;background:#ffffff;padding:10px 8px;position:absolute;top:100%;left:0;right:0;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <div style="display:flex;justify-content:center;align-items:center;gap:32px;">
                    <a href="#" onclick="showSection('quienes-somos')" style="color:#1f2937;text-decoration:none;display:flex;align-items:center;justify-content:center;padding:6px;" title="Quienes Somos">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    </a>
                    <a href="#" onclick="showSection('servicios')" style="color:#1f2937;text-decoration:none;display:flex;align-items:center;justify-content:center;padding:6px;" title="Servicios">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
                    </a>
                    <a href="#" onclick="showSection('contactos')" style="color:#1f2937;text-decoration:none;display:flex;align-items:center;justify-content:center;padding:6px;" title="Contactos">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                    </a>
                </div>
            </div>
        </nav>

        {{-- SECTION --}}
        <main style="flex:1;">
            {{-- CAROUSEL --}}
            <section style="padding:0;">
                <div style="width:CAROUSEL100%;">
                    <div style="position:relative;overflow:hidden;border-radius:0;">
                        <div id="carousel-track" style="display:flex;transition:transform 0.4s ease;">
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:150px;overflow:hidden;background:white;">
                                <video autoplay muted loop playsinline preload="auto" style="width:100%;height:100%;object-fit:cover;">
                                    <source src="{{ asset('images/videos/video1.mp4') }}" type="video/mp4">
                                </video>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:150px;background:url('{{ asset('images/Imacarousel/slide2.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Servicios del Hogar 24/7</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Plomeros, electricistas, técnicos y estética a domicilio. Profesionales certificados.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Solicitar Servicio</button>
                                </div>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:150px;background:url('{{ asset('images/Imacarousel/slide3.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Auxilio Vial en Tiempo Real</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Grúas, mecánica ligera y gomería móvil con ubicación GPS.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Solicitar Auxilio</button>
                                </div>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:150px;background:url('{{ asset('images/Imacarousel/slide4.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Abastos Recurrentes</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Agua, gas, hielo y soda. Pedidos programados semanales o quincenales.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Programar Pedido</button>
                                </div>
                            </div>
                        </div>
                        <button class="carousel-nav-btn" onclick="moveCarousel(-1)" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);background:white;border:1px solid #d1d5db;border-radius:50%;width:36px;height:36px;font-size:16px;cursor:pointer;color:#374151;">&#10094;</button>
                        <button class="carousel-nav-btn" onclick="moveCarousel(1)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:white;border:1px solid #d1d5db;border-radius:50%;width:36px;height:36px;font-size:16px;cursor:pointer;color:#374151;">&#10095;</button>
                    </div>
                </div>

                <section style="background:#f5f7fa;padding:0;">
                    <div id="section-inicio" class="page-section" style="display:block;max-width:100%;margin:0;background:white;border-radius:0;padding:0;box-shadow:0 10px 30px rgba(0,0,0,0.08);">

                        <div id="adFilterBar" style="display:flex;align-items:center;gap:8px;border-bottom:2px solid #e5e7eb;margin:0;padding:0 28px;overflow-x:auto;background:#f3f4f6;">
                            <span class="ad-filter-tab active" onclick="adSetCategory('Todos')" data-cat="Todos" title="Todos">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            </span>
                            @php
                                $groupIcons = [
                                    'Comercio & Gastronomía' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
                                    'Servicios del Hogar & Cuidado Personal' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
                                    'Auxilio Vial & Mecánica' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C1.4 11.3 1 12.2 1 13v3c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>',
                                    'Abastos Recurrentes' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
                                ];
                            @endphp
                            @foreach($allGroups as $grp)
                            <span class="ad-filter-tab" onclick="adSetCategory('{{ mb_strtoupper($grp->description) }}')" data-cat="{{ mb_strtoupper($grp->description) }}" title="{{ $grp->description }}">
                                {!! $groupIcons[$grp->description] ?? '' !!}
                            </span>
                            @endforeach
                            <div style="width:1px;height:20px;background:#d1d5db;flex-shrink:0;"></div>
                            <div class="ad-search-wrap" style="flex:0 0 auto;position:relative;">
                                <div class="ad-search-inner">
                                    <input id="adSearchInput" type="text" placeholder="Buscar..." autocomplete="off" spellcheck="false"
                                        oninput="adOnInput()" onfocus="adOnFocus()" onkeydown="adOnKey(event)" />
                                    <button id="adClearBtn" type="button" title="Limpiar" aria-label="Limpiar búsqueda" onclick="adClear()">&#10005;</button>
                                    <button id="adSubmitBtn" type="button" title="Buscar" aria-label="Buscar" onclick="adSubmit()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    </button>
                                </div>
                                <div id="adSuggestBox" class="ad-suggest-box"></div>
                            </div>
                            <div id="adResultCount-wrap" style="margin-left:auto;padding:8px 0;font-size:11px;color:#9ca3af;white-space:nowrap;flex-shrink:0;">Mostrando <span id="adResultCount">{{ count($providers) }}</span> de {{ count($providers) }}</div>
                        </div>

                        <div class="ad-grid-zone" style="width:100%;box-sizing:border-box;">
                        <div class="provider-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:4px 28px;box-sizing:border-box;">

                            @foreach($providers as $idx => $provider)
                            @php
                                $searchTerms = strtolower(implode(' ', array_filter([
                                    $provider['name'],
                                    $provider['category'],
                                    $provider['description'],
                                    $provider['zone'] ?? '',
                                ])));
                                $svcList = is_array($provider['services']) ? implode(' ', $provider['services']) : ($provider['services'] ?? '');
                                $searchTerms .= ' ' . strtolower($svcList);
                            @endphp
                            <div class="flip-card" data-idx="{{ $idx }}" data-category="{{ mb_strtoupper($provider['category']) }}" data-groups="{{ mb_strtoupper(implode(',', $provider['groups'])) }}" data-search="{{ $searchTerms }}"><div class="flip-inner"><div class="flip-face flip-front"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;overflow:hidden;background:#f9fafb;height:150px;">
                                    <img src="{{ $provider['banner_url'] ? asset('images/publicidad/' . $provider['banner_url'] . '?v=' . filemtime(public_path('images/publicidad/' . $provider['banner_url']))) : asset('images/publicidad/default.jpg') }}" alt="{{ $provider['name'] }}" style="width:100%;height:100%;object-fit:contain;display:block;background:#f9fafb;" />
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">{{ mb_strtoupper($provider['category']) }}</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">{{ $provider['name'] }}</h4>
                                            @php
                                                $rating = $provider['rating'] ?? 4.5;
                                                $fullStars = (int) floor($rating);
                                                $halfStar = ($rating - $fullStars) >= 0.5;
                                                $emptyStar = 5 - $fullStars - ($halfStar ? 1 : 0);
                                                $starsHtml = str_repeat('&#9733;', $fullStars) . ($halfStar ? '&#9734;' : '') . str_repeat('&#9734;', $emptyStar);
                                            @endphp
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">{!! $starsHtml !!}</span> <span style="font-size:11px;color:#6b7280;">{{ number_format($rating, 1) }}</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $provider['description'] }}</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div></div><div class="flip-face flip-back"></div></div></div>
                            @endforeach
                        </div>

                        <div id="ad-pagination" style="display:flex;justify-content:center;align-items:center;width:100%;min-height:56px;padding:8px 28px;box-sizing:border-box;">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <button id="prev-page" class="pagination-nav-btn" onclick="adPage(1)" style="background:white;border:1px solid #d1d5db;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;color:#374151;">&#10094;</button>
                                <div id="page-dots" style="display:flex;gap:6px;"></div>
                                <button id="next-page" class="pagination-nav-btn" onclick="adPage(2)" style="background:white;border:1px solid #d1d5db;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;color:#374151;">&#10095;</button>
                            </div>
                        </div>
                    </div>
                    <div id="section-quienes-somos" class="page-section" style="display:none;">
                        <section class="quienes-somos-container">
                            <div class="quienes-somos-content">
                                <div class="text-column">
                                    <span class="badge-tag">Conectando tu comunidad</span>
                                    <h2>Qui&#233;nes Somos en <span class="brand-name">pideaca.com</span></h2>
                                    <p class="paragraph">
                                        <strong>pideaca.com</strong> nace con la misi&#243;n de transformar y modernizar la manera en que las comunidades locales interact&#250;an con los negocios y servicios de su entorno. Dise&#241;amos una soluci&#243;n integral que re&#250;ne en una sola aplicaci&#243;n todo lo que un hogar necesita: desde realizar un pedido gastron&#243;mico o abastecerse de insumos cotidianos, hasta encontrar un t&#233;cnico de confianza para urgencias del hogar o solicitar auxilio vial inmediato mediante geolocalizaci&#243;n en tiempo real.
                                    </p>
                                    <p class="paragraph">
                                        Construimos nuestra plataforma sobre una <strong>filosof&#237;a de valor bidireccional (&#8220;Ganar-Ganar&#8221;)</strong>: brindamos a los usuarios una experiencia centralizada, r&#225;pida y segura con perfiles y profesionales verificados; al mismo tiempo, impulsamos la digitalizaci&#243;n del comercio local y de los trabajadores independientes mediante esquemas de cobro accesibles, justos y transparentes. En <strong>pideaca.com</strong>, conectar a los vecinos con el trabajo y el talento local es el motor que fortalece la econom&#237;a de nuestras ciudades.
                                    </p>
                                    <div class="stats-row">
                                        <div class="stat-item">
                                            <span class="stat-number">100%</span>
                                            <span class="stat-label">Comercio Local</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number">Win-Win</span>
                                            <span class="stat-label">Modelo Justo</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="image-column">
                                    <div class="illustration-card">
                                        <svg viewBox="0 0 500 400" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                    <stop offset="0%" stop-color="#f8fafc"/>
                                                    <stop offset="100%" stop-color="#edf2f7"/>
                                                </linearGradient>
                                                <linearGradient id="accentGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                    <stop offset="0%" stop-color="#e65100"/>
                                                    <stop offset="100%" stop-color="#ff9800"/>
                                                </linearGradient>
                                                <linearGradient id="primaryGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                    <stop offset="0%" stop-color="#0b2545"/>
                                                    <stop offset="100%" stop-color="#1e3a8a"/>
                                                </linearGradient>
                                                <filter id="shadow" x="-10%" y="-10%" width="120%" height="120%">
                                                    <feDropShadow dx="0" dy="8" stdDeviation="6" flood-opacity="0.08"/>
                                                </filter>
                                            </defs>
                                            <rect width="500" height="400" rx="20" fill="url(#bgGrad)"/>
                                            <g filter="url(#shadow)">
                                                <circle cx="250" cy="180" r="110" fill="#ffffff"/>
                                                <path d="M250,90 C205,90 170,125 170,170 C170,225 250,300 250,300 C250,300 330,225 330,170 C330,125 295,90 250,90 Z" fill="url(#accentGrad)"/>
                                                <circle cx="250" cy="165" r="35" fill="#ffffff"/>
                                            </g>
                                            <g filter="url(#shadow)" class="floating-card">
                                                <rect x="50" y="70" width="120" height="70" rx="12" fill="#ffffff"/>
                                                <circle cx="85" cy="105" r="18" fill="#fff3e0"/>
                                                <path d="M85 93 L97 113 H73 Z" fill="#e65100"/>
                                                <circle cx="85" cy="104" r="2" fill="#ffffff"/>
                                                <rect x="112" y="95" width="45" height="6" rx="3" fill="#0b2545"/>
                                                <rect x="112" y="107" width="30" height="5" rx="2.5" fill="#94a3b8"/>
                                            </g>
                                            <g filter="url(#shadow)" class="floating-card-delay">
                                                <rect x="330" y="80" width="130" height="70" rx="12" fill="#ffffff"/>
                                                <circle cx="365" cy="115" r="18" fill="#e0f2fe"/>
                                                <path d="M360 108 L370 118 M368 108 L360 118" stroke="#0284c7" stroke-width="3" stroke-linecap="round"/>
                                                <rect x="392" y="105" width="50" height="6" rx="3" fill="#0b2545"/>
                                                <rect x="392" y="117" width="35" height="5" rx="2.5" fill="#94a3b8"/>
                                            </g>
                                            <g filter="url(#shadow)">
                                                <rect x="140" y="310" width="220" height="55" rx="28" fill="url(#primaryGrad)"/>
                                                <circle cx="170" cy="337" r="16" fill="#ffffff"/>
                                                <path d="M164 337 L168 341 L176 333" stroke="#e65100" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                                <text x="195" y="342" fill="#ffffff" font-family="sans-serif" font-weight="bold" font-size="13">Ecosistema Win-Win</text>
                                            </g>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div id="section-servicios" class="page-section" style="display:none;max-width:900px;margin:0 auto;background:white;border-radius:16px;padding:28px 24px;box-shadow:0 10px 30px rgba(0,0,0,0.08);">
                        <h2 style="font-size:24px;font-weight:bold;color:#0c2a4d;margin:0 0 12px;">Nuestros Servicios</h2>
                        <p style="font-size:15px;color:#374151;line-height:1.7;margin:0 0 12px;">Descubrí las cuatro grandes familias de servicios que ofrecemos:</p>
                        <p style="font-size:14px;color:#374151;line-height:1.7;margin:4px 0;"><span style="font-weight:700;color:#D24C19;">Comercio:</span> Pizzerías, rotiserías, kioscos y tiendas de conveniencia.</p>
                        <p style="font-size:14px;color:#374151;line-height:1.7;margin:4px 0;"><span style="font-weight:700;color:#2563eb;">Hogar:</span> Plomeros, electricistas, técnicos y estética a domicilio.</p>
                        <p style="font-size:14px;color:#374151;line-height:1.7;margin:4px 0;"><span style="font-weight:700;color:#0d9488;">Auxilio Vial:</span> Grúas, mecánica ligera y gomería móvil con ubicación GPS.</p>
                        <p style="font-size:14px;color:#374151;line-height:1.7;margin:4px 0;"><span style="font-weight:700;color:#d97706;">Abastos:</span> Agua, gas, hielo y soda en pedidos programados.</p>
                    </div>
                    <div id="section-contactos" class="page-section" style="display:none;max-width:900px;margin:0 auto;background:white;border-radius:16px;padding:28px 24px;box-shadow:0 10px 30px rgba(0,0,0,0.08);">
                        <h2 style="font-size:24px;font-weight:bold;color:#0c2a4d;margin:0 0 12px;">Contactos</h2>
                        <p style="font-size:15px;color:#374151;line-height:1.8;margin:0;">Podés escribirnos cuando necesites ayuda o quieras sumar tu comercio o servicio a PideAca:</p>
                        <p style="font-size:14px;color:#374151;margin:10px 0 0;"><strong>Email:</strong> hola@pideaca.com</p>
                        <p style="font-size:14px;color:#374151;margin:4px 0;"><strong>Teléfono:</strong> +54 11 5555-1234</p>
                        <p style="font-size:14px;color:#374151;margin:4px 0;"><strong>Horario de atención:</strong> Lunes a domingo, 24 horas.</p>
                    </div>
                </section>
            </section>

        </main>
        <div style="background:white;height:8px;"></div>
        <footer style="background:#0a0f1a;color:white;padding:14px 0;">
            <div style="text-align:center;">
                <div style="display:flex;align-items:center;justify-content:center;gap:16px;margin:0 0 6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C1.4 11.3 1 12.2 1 13v3c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                <p style="font-size:10px;color:#9ca3af;margin:0;">&copy; {{ date('Y') }} pideaca.com - Todos los derechos reservados.</p>
            </div>
        </footer>

        {{-- LOGIN MODAL --}}
        <div id="login-overlay" class="login-overlay" style="display:none;">
            <div class="login-box" style="background:#ffffff;width:360px;max-width:92vw;border-radius:0;box-shadow:0 20px 50px rgba(0,0,0,0.3);position:relative;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:linear-gradient(180deg,#0c2a4d 0%,#071a30 100%);">
                    <img src="{{ asset('images/logo.png') }}" alt="PideAca" style="height:36px;object-fit:contain;" />
                    <button onclick="closeLogin()" style="background:none;border:none;font-size:20px;cursor:pointer;color:rgba(255,255,255,0.7);line-height:1;">&times;</button>
                </div>
                <form id="login-form" style="padding:20px;display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label for="login-email" style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Correo</label>
                        <input type="email" id="login-email" name="email" placeholder="Ingrese el correo"
                            style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #d1d5db;border-radius:0;font-size:13px;outline:none;">
                    </div>
                    <div>
                        <label for="login-password" style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Clave</label>
                        <div style="position:relative;">
                            <input type="password" id="login-password" name="password" placeholder="Ingrese su contraseña"
                                style="width:100%;box-sizing:border-box;padding:9px 36px 9px 12px;border:1px solid #d1d5db;border-radius:0;font-size:13px;outline:none;">
                            <button type="button" onclick="togglePassword()" id="eye-toggle"
                                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:2px;color:#6b7280;line-height:1;">
                                <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                    </div>
                    <div id="login-error" style="display:none;font-size:12px;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;padding:8px 12px;border-radius:0;"></div>
                    <div id="login-spinner-overlay" style="display:none;position:absolute;top:0;left:0;right:0;bottom:0;background:transparent;z-index:10;align-items:center;justify-content:center;border-radius:0;">
                        <div class="spinner-ring"></div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <button type="submit" id="login-submit" class="header-btn" style="padding:10px;background:#D24C19;color:white;border:1px solid #D24C19;border-radius:4px;font-size:14px;font-weight:700;cursor:pointer;">Iniciar</button>
                        <button type="button" onclick="recoverPassword()" style="padding:10px;background:#ffffff;color:#1d4ed8;border:1px solid #d1d5db;border-radius:0;font-size:13px;font-weight:600;cursor:pointer;">Recuperar contraseña</button>
                    </div>
                </form>
            </div>
        </div>

    </body>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; }

    :root {
        --primary-dark: #0b2545;
        --accent-orange: #e65100;
        --text-main: #334155;
        --text-muted: #64748b;
    }

    .quienes-somos-container {
        padding: 60px 20px;
        background-color: #ffffff;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }
    .quienes-somos-content {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 50px;
        align-items: center;
    }
    .badge-tag {
        color: var(--accent-orange);
        background-color: #fff3e0;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: inline-block;
        margin-bottom: 12px;
    }
    .text-column h2 {
        color: var(--primary-dark);
        font-size: 2.2rem;
        margin: 0 0 20px 0;
        line-height: 1.2;
    }
    .brand-name { color: var(--accent-orange); }
    .paragraph {
        color: var(--text-main);
        font-size: 1.05rem;
        line-height: 1.65;
        margin-bottom: 18px;
    }
    .paragraph strong { color: var(--primary-dark); }
    .stats-row {
        display: flex;
        gap: 30px;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }
    .stat-item { display: flex; flex-direction: column; }
    .stat-number { font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); }
    .stat-label { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; }
    .image-column { width: 100%; display: flex; justify-content: center; }
    .illustration-card {
        width: 100%;
        max-width: 480px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    .floating-card { animation: float 4s ease-in-out infinite; }
    .floating-card-delay { animation: float 4s ease-in-out 2s infinite; }
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-8px); }
    }
    @media (max-width: 900px) {
        .quienes-somos-content { grid-template-columns: 1fr; gap: 40px; }
        .text-column h2 { font-size: 1.8rem; }
    }
    
    .header-btn:hover, .cta-btn:hover {
        background: #ffffff !important;
        color: #D24C19 !important;
        border: 1px solid #D24C19 !important;
    }

    .spinner-ring {
        width: 28px;
        height: 28px;
        border: 3px solid #e5e7eb;
        border-top: 3px solid #D24C19;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .carousel-nav-btn:hover, .pagination-nav-btn:hover {
        border: 1px solid #D24C19 !important;
        color: #D24C19 !important;
    }
    .nav-link {
        position: relative;
        text-decoration: none;
        transition: transform 0.2s ease, text-shadow 0.2s ease;
    }
    .nav-link:hover {
        transform: scale(1.1) translateZ(10px);
        text-shadow: 0 2px 8px rgba(255,255,255,0.4);
    }

    /* GRILLA LIMPIA SIN FRANJAS NI ALTURAS FORZADAS POR JS */
    .ad-grid-zone { width: 100%; box-sizing: border-box; padding-top: 6px; }
    
    .provider-grid { 
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 12px !important;
        padding: 4px 28px !important;
        box-sizing: border-box !important;
        align-items: stretch !important;
        height: auto !important; /* IMPORTANTE: Cancela la altura forzada por JS */
    }

    .ad-card-link { text-decoration: none; color: inherit; display: block; height: 100%; }
    
    .ad-card { 
        position: relative; 
        background: #ffffff; 
        border-radius: 12px; 
        border: 1px solid #e5e7eb; 
        overflow: hidden; 
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; 
        cursor: pointer; 
        display: flex; 
        flex-direction: column; 
        height: 100%; 
    }
    .ad-card:hover { transform: translateY(-4px); box-shadow: 0 12px 20px -5px rgba(0,0,0,0.1); border-color: #f97316; }

    .ad-banner { width: 100%; height: 200px; overflow: hidden; background: #f9fafb; }
    
    .ad-body { 
        padding: 12px; 
        display: flex; 
        flex-direction: column; 
        flex-grow: 1; 
        position: relative;
        padding-bottom: 36px !important;
    }

    .ad-badge { font-size: 9px !important; padding: 2px 6px !important; margin-bottom: 6px !important; }
    .ad-rating { font-size: 10px !important; }
    .ad-card-info { gap: 6px !important; margin-bottom: 6px !important; }

    .ad-title { 
        font-size: 13px !important; 
        line-height: 1.3 !important; 
        font-weight: bold;
        color: #1f2937;
        margin: 0;
        height: auto !important; 
        max-height: 36px !important;
        display: -webkit-box; 
        -webkit-line-clamp: 2; 
        -webkit-box-orient: vertical; 
        overflow: hidden; 
    }

    .ad-desc { 
        font-size: 11px !important; 
        line-height: 1.4 !important; 
        color: #6b7280;
        margin: 0; 
        padding-right: 20px; 
        height: 32px !important; 
        display: -webkit-box; 
        -webkit-line-clamp: 2; 
        -webkit-box-orient: vertical; 
        overflow: hidden; 
    }

    .card-action-icon { 
        position: absolute; 
        bottom: 10px; 
        right: 10px; 
        width: 28px; 
        height: 28px; 
        border-radius: 50%; 
        background-color: #D24C19; 
        border: 1px solid #D24C19; 
        color: #ffffff; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        transition: background-color 0.2s ease, transform 0.2s ease, color 0.2s ease, border-color 0.2s ease; 
    }
    .ad-card:hover .card-action-icon { background-color: #ffffff; color: #D24C19; border-color: #D24C19; transform: translate(2px, -2px); }

    .flip-card {
        position: relative;
        height: 100%;
        cursor: pointer;
        perspective: 1000px;
    }
    .flip-inner {
        position: relative;
        width: 100%;
        height: 100%;
        transform-style: preserve-3d;
        transition: transform 0.65s cubic-bezier(0.4, 0.2, 0.2, 1);
    }
    .flip-card.flipped .flip-inner { transform: rotateY(180deg); }
    .flip-face {
        -webkit-backface-visibility: hidden;
        backface-visibility: hidden;
    }
    .flip-front {
        position: relative;
        z-index: 2;
        height: 100%;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0,0,0,0.18), 0 4px 8px rgba(0,0,0,0.10);
        transform: translateY(-2px);
        transition: box-shadow 0.3s ease, transform 0.3s ease;
    }
    .flip-front .ad-card:hover { transform: none; box-shadow: none; }
    .flip-card:hover .ad-card { border-color: #f97316 !important; }
    .flip-back {
        position: absolute; inset: 0;
        transform: rotateY(180deg);
        background: linear-gradient(180deg, #fff7ed 0%, #ffffff 45%);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        padding: 14px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.20), 0 4px 8px rgba(0,0,0,0.12);
    }
    .flip-back::-webkit-scrollbar { width: 4px; }
    .flip-back::-webkit-scrollbar-thumb { background: #fdba74; border-radius: 4px; }

    .fb-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
    .fb-heading { min-width: 0; }
    .fb-name { font-size: 14px; font-weight: bold; color: #0f172a; margin: 0; line-height: 1.25; }
    .fb-close {
        flex-shrink: 0; width: 26px; height: 26px; border-radius: 50%;
        border: 1px solid #e5e7eb; background: #ffffff; color: #0f172a;
        font-size: 12px; line-height: 1; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s ease;
    }
    .fb-close:hover { background: #e85d04; border-color: #e85d04; color: #ffffff; }
    .fb-block { margin: 0 0 8px; }
    .fb-block-title {
        font-size: 9px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: 800;
        color: #e85d04; margin: 0 0 5px;
    }
    .fb-services { list-style: none; margin: 0; padding: 0; }
    .fb-services li {
        display: flex; align-items: center; gap: 6px;
        font-size: 11px; color: #374151; line-height: 1.35; padding: 2px 0;
    }
    .fb-services svg { flex-shrink: 0; color: #e85d04; }
    .fb-promo {
        display: flex; align-items: center; gap: 6px;
        background: #ffffff; border: 1px solid #fed7aa; border-left: 3px solid #e85d04;
        border-radius: 8px; padding: 6px 8px; font-size: 11px; color: #9a3412;
        margin-bottom: 8px; font-weight: 600;
    }
    .fb-promo-label {
        flex-shrink: 0; background: #e85d04; color: #ffffff;
        font-size: 8px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase;
        padding: 2px 6px; border-radius: 5px;
    }
    .fb-meta { display: flex; flex-direction: column; gap: 4px; margin-bottom: 10px; }
    .fb-meta-row { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #374151; }
    .fb-meta-row svg { flex-shrink: 0; color: #0f172a; }
    .ad-hidden { display: none !important; }
    .ad-filter-tab { display: inline-flex; align-items: center; gap: 6px; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s ease; white-space: nowrap; user-select: none; margin-bottom: -2px; letter-spacing: 0.02em; position: relative; }
    .ad-filter-tab svg { flex-shrink: 0; }
    .ad-filter-tab:hover { color: #374151; background: #d1d5db; }
    .ad-filter-tab.active { color: #D24C19; border-bottom-color: #D24C19; }
    .ad-filter-tab:hover { z-index: 10; }

    .ad-search-inner { display: flex; align-items: center; background: #fff; border-radius: 0; box-shadow: 0 2px 12px rgba(0,0,0,0.28); overflow: hidden; border: 2px solid transparent; transition: border-color 0.15s ease; height: 28px; }
    .ad-search-inner:focus-within { border-color: #D24C19; }
    #adSearchInput { flex: 1; min-width: 0; border: none; outline: none; background: transparent; padding: 4px 10px; font-size: 12px; color: #111827; font-family: inherit; border-radius: 0; }
    #adSearchInput::placeholder { color: #9ca3af; }
    #adClearBtn { display: none; align-items: center; justify-content: center; background: none; border: none; padding: 0 4px; color: #6b7280; cursor: pointer; line-height: 1; font-size: 14px; }
    #adClearBtn:hover { color: #111827; }
    #adSubmitBtn { width: 32px; height: 24px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #D24C19; color: white; border: none; border-radius: 0; font-size: 11px; font-weight: 600; cursor: pointer; box-sizing: border-box; transition: all 0.2s ease; }
    #adSubmitBtn:hover { background: #fff; color: #D24C19; border: 1px solid #D24C19; }
    .ad-search-wrap { flex: 0 0 auto; min-width: 260px; position: relative; z-index: 1; }

    .ad-suggest-box { position: absolute; top: calc(100% + 8px); left: 0; right: 0; z-index: 40; background: #fff; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.18); border: 1px solid #e5e7eb; max-height: 420px; overflow: auto; display: none; padding: 8px; box-sizing: border-box; }
    .sug-sec { font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #6b7280; padding: 8px 10px 4px; }
    .sug-row { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 6px; cursor: pointer; font-size: 13px; color: #1f2937; }
    .sug-row svg { flex-shrink: 0; color: #6b7280; }
    .sug-row:hover { background: #f3f4f6; }
    .sug-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sug-count { font-size: 11px; color: #9ca3af; flex-shrink: 0; }
    .sug-badge { flex-shrink: 0; font-size: 9px; font-weight: 700; color: #fff; padding: 2px 8px; border-radius: 10px; letter-spacing: 0.03em; }
    .sug-empty { padding: 18px; text-align: center; font-size: 12px; color: #6b7280; }

    @media (max-width: 768px) {
        .nav-container { flex-wrap: nowrap !important; }
        .logo-img { width: 100px !important; height: 40px !important; }
        .ad-search-wrap { flex: 0 0 auto; min-width: 80px; max-width: none; }
        .ad-search-inner { height: 26px; }
        #adSearchInput { padding: 3px 6px; font-size: 11px; }
        #adSubmitBtn { width: 28px; height: 22px; }
        .nav-link { font-size: 0 !important; padding: 4px 6px !important; gap: 0 !important; }
        .nav-link svg { margin: 0 !important; }
        .nav-separator { display: none !important; }
        .header-btn { padding: 3px 8px !important; font-size: 10px !important; }
        .ad-suggest-box { max-height: 320px; }
    }

    @media (max-width: 768px) {
        .flip-card, .flip-inner, .flip-face { border-radius: 0; }
        .fb-name { font-size: 12px; }
        .fb-services li, .fb-meta-row, .fb-promo { font-size: 10px; }
        .fb-promo { padding: 5px 7px; }
    }

    @media (max-width: 768px) {
        .nav-link, .nav-separator { display: none !important; }
        #menu-toggle { display: block !important; }
        .logo-img { width: 140px !important; height: 52px !important; }
        .nav-container { padding: 1px 8px !important; justify-content: space-between !important; }
        .carousel-slide { height: 100px !important; }
        
        .provider-grid { 
            grid-template-columns: repeat(2, 1fr) !important; 
            gap: 10px !important; 
            padding: 4px 12px !important; 
        }
        #adFilterBar { padding: 0 8px !important; gap: 2px !important; flex-wrap: nowrap !important; overflow: hidden !important; }
        .ad-filter-tab { font-size: 0 !important; padding: 8px 6px !important; gap: 0 !important; }
        .ad-filter-tab svg { width: 16px !important; height: 16px !important; }
        .ad-search-wrap { min-width: 0 !important; flex: 0 1 160px !important; }
        .ad-search-inner { height: 26px; }
        #adSearchInput { padding: 3px 6px; font-size: 11px; }
        #adSubmitBtn { width: 26px; height: 22px; }
        #adResultCount-wrap { font-size: 9px !important; white-space: nowrap !important; padding: 8px 0 !important; }
        .ad-banner { height: 120px !important; }
        .ad-title { height: 26px !important; font-size: 11px !important; }
        .ad-desc { height: 24px !important; font-size: 10px !important; }
        .ad-card-info { margin-bottom: 4px !important; }
        .ad-card .ad-body { padding: 10px !important; }
    }

    @media (max-width: 480px) {
        .provider-grid { 
            grid-template-columns: 1fr !important; 
        }
        .ad-banner { height: 100px !important; }
    }
    @media (min-width: 769px) {
        #mobile-menu { display: none !important; }
    }

    .login-overlay {
        position: fixed; inset: 0; z-index: 200;
        background: rgba(7, 26, 48, 0.6);
        display: flex; align-items: flex-start; justify-content: center;
        padding-top: 180px;
    }
    .login-box { animation: loginPop 0.25s ease; }
    @keyframes loginPop {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>
   <script>
    if (window.location.search || window.location.hash) history.replaceState(null, '', window.location.pathname);
    let carouselIndex = 0;
    const track = document.getElementById('carousel-track');
    const totalSlides = track.children.length;

    function moveCarousel(direction) {
        carouselIndex += direction;
        if (carouselIndex < 0) carouselIndex = totalSlides - 1;
        if (carouselIndex >= totalSlides) carouselIndex = 0;
        track.style.transform = 'translateX(-' + (carouselIndex * 100) + '%)';
    }

    function toggleMenu() {
        const menu = document.getElementById('mobile-menu');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }

    function showSection(key) {
        const sections = ['inicio', 'quienes-somos', 'servicios', 'contactos'];
        sections.forEach(function (k) {
            const el = document.getElementById('section-' + k);
            if (el) el.style.display = (k === key) ? 'block' : 'none';
        });
        const target = document.getElementById('section-' + key);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        const menu = document.getElementById('mobile-menu');
        if (menu) menu.style.display = 'none';
    }

    let adCurrentPage = 1;
    let adActiveCategory = 'Todos';
    const adAllCards = document.querySelectorAll('.flip-card');
    const paginationWrap = document.getElementById('ad-pagination');
    const adSearchInput = document.getElementById('adSearchInput');

    function adGetPerPage() {
        const grid = document.querySelector('.provider-grid');
        if (!grid) return 8;
        const cols = window.getComputedStyle(grid).gridTemplateColumns.split(' ').length;
        return cols * 2;
    }

    function adFilterProviders() {
        var query = (adSearchInput.value || '').toLowerCase().trim();
        adCurrentPage = 1;
        var visibleCount = 0;

        adAllCards.forEach(function (card) {
            var groups = (card.getAttribute('data-groups') || '').split(',');
            var search = card.getAttribute('data-search') || '';
            var matchCat = (adActiveCategory === 'Todos') || groups.indexOf(adActiveCategory) !== -1;
            var matchSearch = !query || search.indexOf(query) !== -1;
            var show = matchCat && matchSearch;
            card.classList.toggle('ad-hidden', !show);
            if (show) visibleCount++;
        });

        var countEl = document.getElementById('adResultCount');
        if (countEl) countEl.textContent = visibleCount;

        adRenderPage();
    }

    function adSetCategory(cat) {
        adActiveCategory = cat;
        document.querySelectorAll('.ad-filter-tab').forEach(function (btn) {
            var isActive = btn.getAttribute('data-cat') === cat;
            btn.classList.toggle('active', isActive);
        });
        adFilterProviders();
    }

    function adRenderPage() {
        var perPage = adGetPerPage();
        var visibleCards = [];
        adAllCards.forEach(function (c) {
            if (!c.classList.contains('ad-hidden')) visibleCards.push(c);
        });
        var totalPages = Math.ceil(visibleCards.length / perPage);
        if (adCurrentPage > totalPages) adCurrentPage = totalPages;
        if (adCurrentPage < 1) adCurrentPage = 1;

        adAllCards.forEach(function (c) { c.style.display = 'none'; });

        var start = (adCurrentPage - 1) * perPage;
        var end = start + perPage;
        for (var i = start; i < end && i < visibleCards.length; i++) {
            visibleCards[i].style.display = '';
        }

        if (totalPages <= 1) { paginationWrap.style.display = 'none'; return; }
        paginationWrap.style.display = 'flex';

        var dots = document.getElementById('page-dots');
        dots.innerHTML = '';
        for (var p = 1; p <= totalPages; p++) {
            (function (pg) {
                var d = document.createElement('span');
                d.textContent = pg;
                d.style.cssText = 'padding:2px 6px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;' + (pg === adCurrentPage ? 'color:#D24C19;' : 'color:#9ca3af;');
                d.onmouseenter = function () { if (pg !== adCurrentPage) this.style.color = '#374151'; };
                d.onmouseleave = function () { if (pg !== adCurrentPage) this.style.color = '#9ca3af'; };
                d.onclick = function () { adCurrentPage = pg; adRenderPage(); };
                dots.appendChild(d);
            })(p);
        }
        document.getElementById('prev-page').style.opacity = adCurrentPage === 1 ? '0.4' : '1';
        document.getElementById('prev-page').style.pointerEvents = adCurrentPage === 1 ? 'none' : 'auto';
        document.getElementById('next-page').style.opacity = adCurrentPage === totalPages ? '0.4' : '1';
        document.getElementById('next-page').style.pointerEvents = adCurrentPage === totalPages ? 'none' : 'auto';
    }

    function adPage(dir) {
        var perPage = adGetPerPage();
        var visibleCards = [];
        adAllCards.forEach(function (c) {
            if (!c.classList.contains('ad-hidden')) visibleCards.push(c);
        });
        var totalPages = Math.ceil(visibleCards.length / perPage);
        if (dir === 1 && adCurrentPage > 1) { adCurrentPage--; adRenderPage(); }
        if (dir === 2 && adCurrentPage < totalPages) { adCurrentPage++; adRenderPage(); }
    }

    var adSuggestBox = document.getElementById('adSuggestBox');
    var adClearBtn = document.getElementById('adClearBtn');

    var adCatColors = { 'GASTRONOMÍA': '#ea580c', 'COMERCIO': '#ea580c', 'PIZZERÍAS': '#ea580c', 'ROTISERÍAS': '#ea580c', 'KIOSCOS': '#ea580c', 'PANADERÍAS': '#ea580c', 'HOGAR': '#2563eb', 'SERVICIOS DEL HOGAR & CUIDADO PERSONAL': '#2563eb', 'PLOMEROS': '#2563eb', 'ELECTRICISTAS': '#2563eb', 'CERRAJEROS': '#2563eb', 'GASISTAS MATRICULADOS': '#2563eb', 'ARREGLO DE AIRES ACONDICIONADOS': '#2563eb', 'HELADERAS': '#2563eb', 'LAVARROPAS': '#2563eb', 'COMPUTADORAS Y CELULARES': '#2563eb', 'LIMPIEZA PROFUNDA': '#2563eb', 'FUMIGACIÓN': '#2563eb', 'JARDINERÍA Y MANTENIMIENTO DE PILETAS': '#2563eb', 'AUXILIO VIAL': '#0d9488', 'AUXILIO VIAL & MECÁNICA': '#0d9488', 'SERVICIO DE GRÚAS': '#0d9488', 'MECÁNICA LIGERA Y GOMERÍA MÓVIL': '#0d9488', 'ABASTOS': '#d97706', 'ABASTOS RECURRENTES': '#d97706', 'GARRAFAS DE GAS': '#d97706', 'REPARTO DE AGUA EMBOTELLADA/DISPENSERS & SODA': '#d97706', 'GENERAL': '#6b7280' };
    var ICON_CLOCK = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
    var ICON_TAG = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.83z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>';
    var ICON_STORE = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1.5-5h15L21 9M5 9v11h14V9"/><path d="M3 13h18M8 20v-6h8v6"/></svg>';

    function adEsc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    function adCatColor(c) { return adCatColors[c] || '#6b7280'; }
    function adHistory() { try { return JSON.parse(localStorage.getItem('pideaca_search_history') || '[]'); } catch (e) { return []; } }
    function adSaveHistory(q) {
        q = (q || '').trim();
        if (!q) return;
        var h = adHistory().filter(function (x) { return x !== q; });
        h.unshift(q);
        try { localStorage.setItem('pideaca_search_history', JSON.stringify(h.slice(0, 5))); } catch (e) {}
    }
    function adToggleClear() { adClearBtn.style.display = adSearchInput.value ? 'flex' : 'none'; }
    function adCloseSuggest() { if (adSuggestBox) adSuggestBox.style.display = 'none'; }

    function adOnInput() { adToggleClear(); adFilterProviders(); adOpenSuggest(); }
    function adOnFocus() { adOpenSuggest(); }
    function adOnKey(e) {
        if (e.key === 'Enter') { e.preventDefault(); adSubmit(); }
        if (e.key === 'Escape') { adCloseSuggest(); adSearchInput.blur(); }
    }
    function adClear() {
        adSearchInput.value = '';
        adToggleClear();
        adFilterProviders();
        adOpenSuggest();
        adSearchInput.focus();
    }
    function adSubmit() {
        adSaveHistory(adSearchInput.value);
        adCloseSuggest();
        adFilterProviders();
        showSection('inicio');
    }
    function adPick(el) {
        var v = el.getAttribute('data-v') || adSearchInput.value;
        adSearchInput.value = v;
        adToggleClear();
        adSaveHistory(v);
        adSetCategory('Todos');
        adFilterProviders();
        adCloseSuggest();
        showSection('inicio');
    }

    function adOpenSuggest() {
        var q = (adSearchInput.value || '').toLowerCase().trim();
        var hist = adHistory();
        var cats = {}, providers = [];
        adFlipData.forEach(function (d, i) { var k = d.cat; cats[k] = (cats[k] || 0) + 1; providers.push({ name: d.name, cat: d.cat, i: i }); });

        var html = '', hasData = false;

        if (hist.length) {
            var hh = q ? hist.filter(function (x) { return x.toLowerCase().indexOf(q) !== -1; }).slice(0, 5) : hist.slice(0, 5);
            if (hh.length) {
                html += '<div class="sug-sec">Recientes</div>';
                hh.forEach(function (x) { html += '<div class="sug-row" data-v="' + adEsc(x) + '" onclick="adPick(this)">' + ICON_CLOCK + '<span class="sug-label">' + adEsc(x) + '</span></div>'; });
                hasData = true;
            }
        }

        var catKeys = Object.keys(cats).filter(function (c) {
            if (!q) return true;
            if (c.toLowerCase().indexOf(q) !== -1) return true;
            return [].concat.apply([], adFlipData.filter(function (d) { return d.cat === c; }).map(function (d) { return d.services; })).join(' ').toLowerCase().indexOf(q) !== -1;
        }).slice(0, 4);
        if (catKeys.length) {
            html += '<div class="sug-sec">Categorías de servicios</div>';
            catKeys.forEach(function (c) { html += '<div class="sug-row" data-v="' + adEsc(c) + '" onclick="adPick(this)">' + ICON_TAG + '<span class="sug-label">' + adEsc(c) + '</span><span class="sug-count">' + cats[c] + '</span></div>'; });
            hasData = true;
        }

        var bp = providers.filter(function (p) {
            if (!q) return true;
            if (p.name.toLowerCase().indexOf(q) !== -1) return true;
            return adFlipData[p.i].services.join(' ').toLowerCase().indexOf(q) !== -1;
        }).slice(0, 6);
        if (bp.length) {
            html += '<div class="sug-sec">Comercios</div>';
            bp.forEach(function (p) { html += '<div class="sug-row" data-v="' + adEsc(p.name) + '" onclick="adPick(this)">' + ICON_STORE + '<span class="sug-label">' + adEsc(p.name) + '</span><span class="sug-badge" style="background:' + adCatColor(p.cat) + '">' + adEsc(p.cat) + '</span></div>'; });
            hasData = true;
        }

        if (!hasData) html = '<div class="sug-empty">Sin resultados para &ldquo;' + adEsc(q) + '&rdquo;</div>';
        adSuggestBox.innerHTML = html;
        adSuggestBox.style.display = 'block';
    }

    document.addEventListener('click', function (e) {
        var wrap = document.querySelector('.ad-search-wrap');
        if (wrap && !wrap.contains(e.target)) adCloseSuggest();
    });

    var adFlipData = {!! json_encode($providers->map(fn($p) => [
        'cat' => mb_strtoupper($p['category']),
        'group' => mb_strtoupper($p['group'] ?? 'General'),
        'name' => $p['name'],
        'rating' => $p['rating'] ?? 4.5,
        'desc' => $p['description'],
        'services' => $p['services'],
        'promo' => $p['promo'],
        'hours' => $p['hours'],
        'phone' => $p['phone'],
        'zone' => $p['zone'],
        'wa' => $p['whatsapp'],
    ])) !!};

    function flipCheck() {
        return '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    }
    function buildFlipBack(d) {
        if (!d) return '';
        var li = [];
        d.services.forEach(function (s) { li.push('<li>' + flipCheck() + '<span>' + s + '</span></li>'); });
        return ''
            + '<div class="fb-top">'
            +   '<div class="fb-heading">'
            +       '<h4 class="fb-name">' + d.name + '</h4>'
            +   '</div>'
            +   '<button type="button" class="fb-close" aria-label="Volver" title="Volver" onclick="event.stopPropagation(); flipCardBack(this);">&#10005;</button>'
            + '</div>'
            + '<div class="fb-block">'
            +   '<p class="fb-block-title">Servicios</p>'
            +   '<ul class="fb-services">' + li.join('') + '</ul>'
            + '</div>'
            + (d.promo ? '<div class="fb-promo"><span class="fb-promo-label">Promo</span><span>' + d.promo + '</span></div>' : '')
            + '<div class="fb-meta">'
            +   '<div class="fb-meta-row"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg><span>' + d.hours + '</span></div>'
            +   '<div class="fb-meta-row"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg><span>' + d.phone + '</span></div>'
            +   '<div class="fb-meta-row"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg><span>' + d.zone + '</span></div>'
            + '</div>';
    }

    function initFlipBacks() {
        document.querySelectorAll('.flip-card').forEach(function (card) {
            var d = adFlipData[parseInt(card.getAttribute('data-idx'), 10)];
            var back = card.querySelector('.flip-back');
            if (d && back) back.innerHTML = buildFlipBack(d);
        });
    }
    initFlipBacks();

    function flipCard(card) {
        if (card) card.classList.toggle('flipped');
    }
    function flipCardBack(el) {
        var card = el.closest ? el.closest('.flip-card') : null;
        if (card) card.classList.remove('flipped');
    }

    document.addEventListener('click', function (e) {
        var card = e.target && e.target.closest ? e.target.closest('.flip-card') : null;
        if (!card) return;
        var back = card.querySelector('.flip-back');
        if (back && back.contains(e.target)) return;
        card.classList.toggle('flipped');
    });

    adRenderPage();
    window.addEventListener('resize', adRenderPage);
    adToggleClear();

    const loginOverlay = document.getElementById('login-overlay');
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');
    const loginSpinnerOverlay = document.getElementById('login-spinner-overlay');

    function openLogin() {
        loginError.style.display = 'none';
        document.getElementById('login-password').value = '';
        document.getElementById('login-password').type = 'password';
        document.getElementById('eye-open').style.display = 'block';
        document.getElementById('eye-closed').style.display = 'none';
        loginOverlay.style.display = 'flex';
        document.getElementById('login-email').focus();
    }

    function closeLogin() {
        loginOverlay.style.display = 'none';
    }

    loginOverlay.addEventListener('click', function (e) {
        if (e.target === loginOverlay) closeLogin();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && loginOverlay.style.display === 'flex') closeLogin();
    });

    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var email = document.getElementById('login-email').value.trim();
        var pass = document.getElementById('login-password').value.trim();
        if (!email) { loginError.style.display='block'; loginError.style.color='#dc2626'; loginError.style.background='#fef2f2'; loginError.style.border='1px solid #fecaca'; loginError.textContent='Ingrese el correo'; return; }
        if (!pass) { loginError.style.display='block'; loginError.style.color='#dc2626'; loginError.style.background='#fef2f2'; loginError.style.border='1px solid #fecaca'; loginError.textContent='Ingrese su contraseña'; return; }
        loginError.style.display = 'none';
        loginSpinnerOverlay.style.display = 'none';
        const submitBtn = document.getElementById('login-submit');
        submitBtn.disabled = true;

        fetch('{{ url('/login') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                email: document.getElementById('login-email').value,
                password: document.getElementById('login-password').value
            })
        })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (r) {
            submitBtn.disabled = false;
            if (r.ok && r.data.success) {
                loginError.style.display = 'none';
                loginSpinnerOverlay.style.display = 'flex';
                if (r.data.user) sessionStorage.setItem('pideaca_user', JSON.stringify(r.data.user));
                setTimeout(function () {
                    window.location.href = '{{ url("/dashboard") }}';
                }, 1000);
            } else {
                loginError.style.color = '#dc2626';
                loginError.style.background = '#fef2f2';
                loginError.style.border = '1px solid #fecaca';
                loginError.style.display = 'block';
                loginError.textContent = 'Contraseña incorrecta';
                setTimeout(function () { loginError.style.display = 'none'; }, 2000);
            }
        })
        .catch(function () {
            submitBtn.disabled = false;
            loginError.style.color = '#dc2626';
            loginError.style.background = '#fef2f2';
            loginError.style.border = '1px solid #fecaca';
            loginError.style.display = 'block';
            loginError.textContent = 'Error de conexión. Intentá de nuevo.';
            setTimeout(function () { loginError.style.display = 'none'; }, 2000);
        });
    });

    function recoverPassword() {
        loginError.style.color = '#1d4ed8';
        loginError.style.background = '#eff6ff';
        loginError.style.border = '1px solid #bfdbfe';
        loginError.style.display = 'block';
        loginError.textContent = 'Próximamente enviaremos un enlace de recuperación a tu correo.';
    }

    function togglePassword() {
        var input = document.getElementById('login-password');
        var eyeOpen = document.getElementById('eye-open');
        var eyeClosed = document.getElementById('eye-closed');
        if (input.type === 'password') {
            input.type = 'text';
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'block';
        } else {
            input.type = 'password';
            eyeOpen.style.display = 'block';
            eyeClosed.style.display = 'none';
        }
    }
</script>
</html>
