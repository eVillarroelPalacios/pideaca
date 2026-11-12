<!DOCTYPE html>
<html lang="es" data-theme="light">
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
        <div id="top-social-bar" style="background:#f3f4f6;padding:10px 0;display:block;">
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
                    <a href="#" onclick="showSection('quienes-somos');return false;" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
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
                    <a href="#" onclick="openRegister();return false;" class="header-btn" style="padding:2px 10px;background:#D24C19;color:white;border:1px solid #D24C19;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;cursor:pointer;">Registrate</a>
                    <a href="#" onclick="openLogin();return false;" class="header-btn" style="padding:2px 10px;background:#D24C19;color:white;border:1px solid #D24C19;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;cursor:pointer;">Entrar</a>
                    <button id="menu-toggle" class="block md:hidden" onclick="toggleMenu()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;padding:4px 8px;">&#9776;</button>
                </div>
            </div>
            <div id="mobile-menu" style="display:none;background:#ffffff;padding:10px 8px;position:absolute;top:100%;left:0;right:0;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <div style="display:flex;justify-content:center;align-items:center;gap:32px;">
                    <a href="#" onclick="showSection('quienes-somos');return false;" style="color:#1f2937;text-decoration:none;display:flex;align-items:center;justify-content:center;padding:6px;" title="Quienes Somos">
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
            <section id="hero-carousel" style="padding:0;">
                <div style="width:100%;">
                    <div style="position:relative;overflow:hidden;border-radius:0;">
                        <div id="carousel-track" style="display:flex;transition:transform 0.4s ease;">
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:200px;overflow:hidden;background:white;">
                                <video autoplay muted loop playsinline preload="auto" style="width:100%;height:100%;object-fit:cover;">
                                    <source src="{{ asset('images/videos/video1.mp4') }}" type="video/mp4">
                                </video>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:200px;background:url('{{ asset('images/Imacarousel/slide2.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Servicios del Hogar 24/7</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Plomeros, electricistas, técnicos y estética a domicilio. Profesionales certificados.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Solicitar Servicio</button>
                                </div>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:200px;background:url('{{ asset('images/Imacarousel/slide3.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Auxilio Vial en Tiempo Real</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Grúas, mecánica ligera y gomería móvil con ubicación GPS.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Solicitar Auxilio</button>
                                </div>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:200px;background:url('{{ asset('images/Imacarousel/slide4.jpg') }}') center/cover no-repeat;">
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
            </section>

                <section style="background:#f5f7fa;padding:0;">
                    <div id="section-inicio" class="page-section" style="display:block;max-width:100%;margin:0;background:white;border-radius:0;padding:0;box-shadow:0 10px 30px rgba(0,0,0,0.08);">

                        <div id="adFilterBar" style="display:flex;align-items:center;gap:8px;border-bottom:2px solid #e5e7eb;margin:0;padding:0 28px;overflow:visible;background:#f3f4f6;">
                            <span class="ad-filter-tab active" onclick="adSetCategory('Todos')" data-cat="Todos" title="Todos">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                            </span>
                            @php
                                $groupIcons = [
                                    'Comercio' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
                                    'Hogar' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>',
                                    'Comercio & Gastronomía' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
                                    'Servicios del Hogar & Bienestar' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>',
                                    'Auxilio Vial & Mecánica' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.5 2.8C1.4 11.3 1 12.2 1 13v3c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>',
                                    'Abastos Recurrentes' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
                                ];
                            @endphp
                            @foreach($allGroups as $grp)
                            <div style="width:1px;height:20px;background:#d1d5db;flex-shrink:0;"></div>
                            <span class="ad-filter-tab" onclick="adSetCategory('{{ mb_strtoupper($grp->description) }}')" data-cat="{{ mb_strtoupper($grp->description) }}" title="{{ $grp->description }}">
                                {!! ($grp->icon ?: ($groupIcons[$grp->description] ?? '')) !!}
                            </span>
                            @endforeach
                            <div style="width:1px;height:20px;background:#d1d5db;flex-shrink:0;"></div>
                            <div class="ad-search-wrap" style="flex:0 0 auto;position:relative;display:flex;align-items:center;gap:8px;overflow:visible;z-index:30;">
                                <div class="ad-search-inner">
                                    <input id="adSearchInput" type="text" placeholder="Buscar..." autocomplete="off" spellcheck="false"
                                        oninput="adOnInput()" onfocus="adOnFocus()" onkeydown="adOnKey(event)" />
                                    <button id="adClearBtn" type="button" title="Limpiar" aria-label="Limpiar búsqueda" onclick="adClear()">&#10005;</button>
                                    <button id="adSubmitBtn" type="button" title="Buscar" aria-label="Buscar" onclick="adSubmit()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    </button>
                                </div>
                                <button type="button" id="btn-filtro-modal" onclick="document.getElementById('modal-filtro').style.display='flex'; distanceFilterMode()" style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;min-width:24px;background:#e8590c;border:none;border-radius:3px;margin-left:5px;cursor:pointer;flex-shrink:0;">
                                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/>
                                    <line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/>
                                    <line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/>
                                    <line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                                  </svg>
                                </button>
                                <div id="adSuggestBox" class="ad-suggest-box"></div>
                            </div>
                            <div id="adResultCount-wrap" style="margin-left:auto;padding:8px 0;font-size:11px;color:#374151;font-weight:600;white-space:nowrap;flex-shrink:0;">Mostrando <span id="adResultCount">{{ count($providers) }}</span> de {{ count($providers) }}</div>
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

                                // Sin imagen de publicidad: iniciales del negocio en un
                                // circulo de color, para que no quede el alt roto.
                                $sigWords = array_values(array_filter(
                                    preg_split('/\s+/', trim((string) $provider['name'])),
                                    fn ($w) => $w !== '' && !in_array(strtolower($w), ['de', 'del', 'la', 'el', 'los', 'las', 'y', 'con'], true)
                                ));
                                if (empty($sigWords)) {
                                    $sigWords = ['?'];
                                }
                                $initials = mb_strtoupper(isset($sigWords[1])
                                    ? mb_substr($sigWords[0], 0, 1) . mb_substr($sigWords[1], 0, 1)
                                    : mb_substr($sigWords[0], 0, 2));
                                $initialsPalette = ['#D24C19', '#0c2a4d', '#047857', '#7c3aed', '#b45309', '#0e7490'];
                                $initialsColor = $initialsPalette[((int) $provider['id']) % count($initialsPalette)];
                            @endphp
                            <div class="flip-card" data-idx="{{ $idx }}" data-category="{{ mb_strtoupper($provider['category']) }}" data-groups="{{ mb_strtoupper(implode(',', $provider['groups'])) }}" data-search="{{ $searchTerms }}"><div class="flip-inner"><div class="flip-face flip-front"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                @if(isset($provider['status']) && strtolower($provider['status']) === 'prueba')
                                <span class="ad-trial-band" style="position:absolute;top:0;right:18px;z-index:3;display:block;width:82px;text-align:center;font-size:11px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;color:#2b313a;filter:drop-shadow(0 4px 6px rgba(0,0,0,.45));">
                                    <span style="display:block;height:9px;background:linear-gradient(180deg,#666d78 0%,#8b929d 55%,#b4bac4 100%);border-radius:3px 3px 0 0;box-shadow:inset 0 1px 2px rgba(0,0,0,.4);"></span>
                                    <span style="display:block;padding:9px 0 20px;background:linear-gradient(180deg,#cfd3d9 0%,#c0c4cb 50%,#b3b8bf 100%);clip-path:polygon(0 0,100% 0,100% 100%,50% 76%,0 100%);text-shadow:0 1px 0 rgba(255,255,255,.75);">Prueba</span>
                                </span>
                                @endif
                                <div class="ad-banner">
                                    @if($provider['banner_url'])
                                    <img class="ad-avatar-img" src="{{ asset('images/publicidad/' . $provider['banner_url'] . '?v=' . filemtime(public_path('images/publicidad/' . $provider['banner_url']))) }}" alt="{{ $provider['name'] }}" />
                                    @else
                                    <div class="ad-avatar" style="background:{{ $initialsColor }};">{{ $initials }}</div>
                                    @endif
                                </div>
                                <div class="ad-body" style="position:relative;z-index:1;background:white;">
                                    <span class="ad-badge">{{ mb_strtoupper($provider['category']) }}</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">{{ $provider['name'] }}</h4>
                                             @php
                                                 $rating = $provider['rating'] ?? null;
                                                 $ratingCount = $provider['rating_count'] ?? 0;
                                             @endphp
                                             @if($rating !== null && $rating > 0)
                                                 @php
                                                     $fullStars = (int) floor($rating);
                                                     $halfStar = ($rating - $fullStars) >= 0.5;
                                                     $emptyStar = 5 - $fullStars - ($halfStar ? 1 : 0);
                                                     $starsHtml = str_repeat('&#9733;', $fullStars) . ($halfStar ? '&#9734;' : '') . str_repeat('&#9734;', $emptyStar);
                                                 @endphp
                                                 <span class="ad-rating" style="font-size:11px;color:#f59e0b;">{!! $starsHtml !!}</span> <span style="font-size:11px;color:#6b7280;">{{ number_format($rating, 1) }} ({{ $ratingCount }})</span>
                                             @else
                                                 <span style="font-size:11px;color:#94a3b8;font-style:italic;">★ Sin calificaciones</span>
                                             @endif
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
                    </div>
                    <div id="section-quienes-somos" class="page-section" style="display:none;">
                        <section class="quienes-somos-section">
                            <div class="qs-container">

                                <!-- COLUMNA IZQUIERDA: Ilustraci&#243;n Visual Vectorial -->
                                <div class="qs-image-col">
                                    <div class="illustration-wrapper">
                                        <video autoplay muted loop playsinline preload="auto" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:0;">
                                            <source src="{{ asset('images/videos/quienes-somos.mp4') }}" type="video/mp4">
                                        </video>
                                    </div>
                                </div>

                                <!-- COLUMNA DERECHA: Redacci&#243;n Institucional -->
                                <div class="qs-text-col">
                                    <span class="qs-badge">Ecosistema multiservicio</span>
                                    <h2>Qui&#233;nes Somos</h2>
                                    <p class="qs-lead">
                                        <strong>Pideaca.com</strong> nace para transformar la manera en que las comunidades locales interact&#250;an con el comercio y los servicios cotidianos. Dise&#241;amos un ecosistema multiservicio &#8212;web y app m&#243;vil&#8212; que integra en un &#250;nico lugar todo lo que un hogar, un comercio o un conductor necesita para resolver compras, urgencias, presupuestos o agendas diarias de forma &#225;gil, segura y transparente.
                                    </p>

                                    <div class="qs-section-block">
                                        <h3>Nuestra Filosof&#237;a: El Modelo &#8220;Ganar - Ganar&#8221;</h3>
                                        <p>
                                            Creemos firmemente en el valor bidireccional y arm&#243;nico. Mientras resolvemos las necesidades inmediatas de los usuarios mediante soluciones centralizadas y perfiles verificados, impulsamos el crecimiento comercial y laboral de los prestadores locales a trav&#233;s de esquemas de cobro justos y transparentes.
                                        </p>

                                    </div>
                                </div>
                            </div>

                            <div class="qs-full">

                                <div class="qs-section-block">
                                    <h3>Un ecosistema, muchas soluciones</h3>
                                    <div class="qs-pillars-grid">
                                        <div class="qs-pillar-card">
                                            <span class="qs-pillar-icon">&#128722;</span>
                                            <h4>Mercado y Comercio</h4>
                                            <p>Pizzer&#237;as, rotiser&#237;as, hamburgueser&#237;as, kioscos y tiendas con cat&#225;logo digital, pedidos y env&#237;o express.</p>
                                        </div>
                                        <div class="qs-pillar-card">
                                            <span class="qs-pillar-icon">&#127968;</span>
                                            <h4>Hogar y Servicios</h4>
                                            <p>Plomeros, electricistas, gasistas, t&#233;cnicos y profesionales verificados para urgencias, citas programadas y presupuestos.</p>
                                        </div>
                                        <div class="qs-pillar-card">
                                            <span class="qs-pillar-icon">&#128666;</span>
                                            <h4>Log&#237;stica y Repartidores</h4>
                                            <p>Repartidores de confianza, seguimiento de entregas, auxilio vial y env&#237;os express para llegar a cada rinc&#243;n.</p>
                                        </div>
                                        <div class="qs-pillar-card">
                                            <span class="qs-pillar-icon">&#129520;</span>
                                            <h4>Gesti&#243;n del Prestador</h4>
                                            <p>Cat&#225;logo, inventario, agenda, despacho, finanzas y reportes reunidos en un mismo panel, web y m&#243;vil.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="qs-section-block">
                                    <h3>Bondades para quien pide</h3>
                                    <div class="qs-benefits-grid">
                                        <div class="qs-benefit-card">
                                            <h4>Soluci&#243;n integral</h4>
                                            <p>Desde una pizza hasta un plomero o una gr&#250;a, sin cambiar de aplicaci&#243;n.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Seguridad y confianza</h4>
                                            <p>Perfiles comprobados, matr&#237;culas validadas, horarios y calificaciones reales.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Tres modalidades de servicio</h4>
                                            <p>Urgencias 24/7, citas programadas con calendario y se&#241;a, o presupuestos a cotizar.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Geolocalizaci&#243;n y b&#250;squeda</h4>
                                            <p>Buscador por nombre o rubro, favoritos y filtro por distancia para encontrar lo m&#225;s cercano.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Seguimiento y calificaci&#243;n</h4>
                                            <p>Aprob&#225;s presupuestos, segu&#237;s el estado del pedido y calific&#225;s el servicio recibido.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Cuentas familiares</h4>
                                            <p>Titular y secundarios, direcciones compartidas y notificaciones push al instante.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="qs-section-block">
                                    <h3>Bondades para quien ofrece</h3>
                                    <div class="qs-benefits-grid">
                                        <div class="qs-benefit-card">
                                            <h4>Alta visibilidad</h4>
                                            <p>Un canal digital directo para captar nuevos clientes en tu zona.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Cat&#225;logo e inventario</h4>
                                            <p>Productos, categor&#237;as, variantes, grupos de opciones y control de stock con alertas.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Agenda y presupuestos</h4>
                                            <p>Gestion&#225; solicitudes, estimaciones en sitio, cotizaciones y disponibilidad.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Despacho propio</h4>
                                            <p>Asign&#225; pedidos a tu red de repartidores de confianza o envialos al pool general.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Finanzas y marketing</h4>
                                            <p>Costos de recetas, m&#225;rgenes, campa&#241;as, demanda y reportes de tus clientes.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Suscripciones justas</h4>
                                            <p>Planes fijos, pagos y comisiones reducidas para proteger tus ganancias.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="qs-section-block">
                                    <h3>Tecnolog&#237;a y confianza</h3>
                                    <div class="qs-tech-grid">
                                        <div class="qs-benefit-card">
                                            <h4>Web + app m&#243;vil</h4>
                                            <p>La misma experiencia en el navegador y en la app para iOS y Android.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Perfiles verificados</h4>
                                            <p>Validaci&#243;n de identidad, matr&#237;culas y datos del prestador antes de publicar.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Permisos por rol</h4>
                                            <p>Cada usuario accede solo a las herramientas y secciones que le corresponden.</p>
                                        </div>
                                        <div class="qs-benefit-card">
                                            <h4>Notificaciones y WhatsApp</h4>
                                            <p>Avisos push, recordatorios y contacto directo con el prestador por WhatsApp.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="qs-section-block">
                                    <h3>Estructura de Servicios</h3>
                                        <div class="qs-modules-grid">
                                            @foreach($modules as $mod)
                                            <div class="module-item">
                                                <span class="mod-icon">{!! ($mod->icon ?: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>') !!}</span>
                                                <div>
                                                    <strong>{{ $mod->description }}</strong>
                                                    @if($mod->groups->isNotEmpty())
                                                        <p>{{ $mod->groups->pluck('description')->implode(' · ') }}</p>
                                                    @endif
                                                    @php $svcNames = $mod->groups->flatMap->subgroups->pluck('description')->unique()->values(); @endphp
                                                    @if($svcNames->isNotEmpty())
                                                        <p style="margin-top:4px;font-size:0.75rem;color:#6b7280;">{{ $svcNames->implode(' · ') }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                            @if($orphanGroups->isNotEmpty())
                                            <div class="module-item">
                                                <span class="mod-icon"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
                                                <div>
                                                    <strong>Otros servicios</strong>
                                                    <p>{{ $orphanGroups->pluck('description')->implode(' · ') }}</p>
                                                    @php $orphanSvc = $orphanGroups->flatMap->subgroups->pluck('description')->unique()->values(); @endphp
                                                    @if($orphanSvc->isNotEmpty())
                                                        <p style="margin-top:4px;font-size:0.75rem;color:#6b7280;">{{ $orphanSvc->implode(' · ') }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="qs-section-block">
                                        <h3>Monetizaci&#243;n Sostenible y Equitativa</h3>
                                        <p class="qs-small-text">
                                            Para garantizar la sostenibilidad sin encarecer los precios finales, implementamos tarifas planas para profesionales, costos m&#237;nimos por contacto y comisiones reducidas para el sector gastron&#243;mico.
                                        </p>
                                        <div class="qs-tags">
                                            <span class="qs-tag">Tarifas planas para profesionales</span>
                                            <span class="qs-tag">Costos m&#237;nimos por contacto</span>
                                            <span class="qs-tag">Comisiones reducidas para gastronom&#237;a</span>
                                            <span class="qs-tag">Planes flexibles</span>
                                        </div>
                                    </div>

                                <div class="qs-cta">
                                    <div>
                                        <h3>&#191;Quer&#233;s sumarte a PideAca?</h3>
                                        <p>Sum&#225; tu comercio o servicio y empez&#225; a recibir clientes, o escribinos para resolver tus dudas.</p>
                                    </div>
                                    <div class="qs-cta-actions">
                                        <a href="#" onclick="openRegister();return false;" class="qs-cta-btn">Quiero registrarme</a>
                                        <a href="#" onclick="showSection('contactos');return false;" class="qs-cta-btn qs-cta-btn-outline">Contactar al equipo</a>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div id="section-servicios" class="page-section rounded-none" style="display:none;max-width:none;margin:0;background:white;border-radius:0;padding:0;box-shadow:none;">
<div style="width: 100%; padding: 32px 24px; box-sizing: border-box;">
  <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: stretch; width: 100%;">

    <!-- COLUMNA IZQUIERDA: VIDEO CON PADDING/BORDE -->
    <div style="flex: 1 1 450px; min-width: 320px; position: sticky; top: 24px; align-self: flex-start;">
      <video id="video-servicios" autoplay muted playsinline style="width: 100%; height: 560px; object-fit: cover; display: block; border: 1px solid #e5e7eb;">
        <source src="{{ asset('images/videos/servicios.mp4') }}" type="video/mp4">
      </video>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('video-servicios');
    if (video) {
      video.addEventListener('timeupdate', () => {
        if (video.currentTime >= 8.0) {
          video.currentTime = 0;
          video.play();
        }
      });
    }
  });
</script>
    </div>

    <!-- COLUMNA DERECHA: ACORDEÓN -->
    <div style="flex: 1 1 450px; min-width: 320px; display: flex; flex-direction: column; justify-content: center;">
      <div style="width: 100%; border: 1px solid #e5e7eb; background: #ffffff;">

        <div style="padding: 0 24px; margin-bottom: 4px;">
          <p style="margin: 18px 0 6px; font-size: 15px; color: #334155; line-height: 1.6;">PideAca reúne tres grandes soluciones para resolver lo cotidiano: pedir de tus comercios y restaurantes, contratar profesionales del hogar y recibir todo con logística local. Cada grupo funciona de forma independiente, pero comparte la misma cuenta, la misma búsqueda y la misma seguridad.</p>
        </div>

        <!-- 1. Comercio -->
        <details open style="border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">
          <summary style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; font-weight: 700; color: #1e293b; cursor: pointer; list-style: none; user-select: none;">
            <span>Comercio - Tiendas y Gastronomía</span>
            <span style="font-size: 13px; color: #64748b;">▼</span>
          </summary>
          <div style="padding: 0 24px 24px 24px; color: #4b5563; font-size: 14px; line-height: 1.6;">
            <p style="margin: 0 0 14px 0;">Encontrá tus comercios y restaurantes favoritos: pizzerías, rotiserías, hamburgueserías, sushi y poke, heladerías, kioscos y tiendas de conveniencia. Cada negocio publica su catálogo digital con fotos, precios, variantes y adicionales, y lo pedís desde el celular o la web.</p>

            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #0c2a4d; text-transform: uppercase; letter-spacing: 0.5px;">Qué incluye</p>
            <ul style="margin: 0 0 14px 0; padding-left: 18px;">
              <li>Catálogo digital con categorías, productos, variantes y grupos de opciones.</li>
              <li>Pedís para llevar, para enviar a tu dirección o para retirar en el local.</li>
              <li>Favoritos y recompra rápida de tus comercios habituales.</li>
              <li>Estado del pedido y seguimiento del envío hasta tu puerta.</li>
              <li>Calificación y reseñas del comercio y del repartidor.</li>
            </ul>

            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #0c2a4d; text-transform: uppercase; letter-spacing: 0.5px;">Cómo funciona</p>
            <ol style="margin: 0 0 14px 0; padding-left: 18px;">
              <li>Buscá el comercio por nombre o rubro, o filtrá por zona y distancia.</li>
              <li>Elegí tus productos, cantidades y adicionales.</li>
              <li>Confirmá el pedido con la dirección de entrega.</li>
              <li>Seguí el pedido hasta la puerta y calificá la experiencia.</li>
            </ol>

            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #0c2a4d; text-transform: uppercase; letter-spacing: 0.5px;">Para el comercio</p>
            <ul style="margin: 0 0 14px 0; padding-left: 18px;">
              <li>Catálogo, inventario y alertas de stock.</li>
              <li>Despacho con repartidores de confianza o envío al pool general.</li>
              <li>Salud financiera: costos, recetas, márgenes y reportes de ventas.</li>
              <li>Suscripciones flexibles y comisiones reducidas.</li>
            </ul>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Gastronomía</span>
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Tiendas</span>
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Envío Veloz</span>
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Catálogo Digital</span>
            </div>
          </div>
        </details>

        <!-- 2. Hogar -->
        <details style="border-bottom: 1px solid #e5e7eb;">
          <summary style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; font-weight: 700; color: #1e293b; cursor: pointer; list-style: none; user-select: none;">
            <span>Hogar - Soluciones Técnicas y Mantenimiento</span>
            <span style="font-size: 13px; color: #64748b;">▼</span>
          </summary>
          <div style="padding: 0 24px 24px 24px; color: #4b5563; font-size: 14px; line-height: 1.6;">
            <p style="margin: 0 0 14px 0;">Soluciones para el hogar, el negocio y el bienestar, con profesionales verificados y matrículas validadas. Desde una urgencia a las 3 de la mañana hasta una obra planificada, encontrás el profesional indicado y el formato de contratación que te convenga.</p>

            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #0c2a4d; text-transform: uppercase; letter-spacing: 0.5px;">Qué incluye</p>
            <ul style="margin: 0 0 14px 0; padding-left: 18px;">
              <li>Plomería, electricidad, cerrajería y gasistas matriculados.</li>
              <li>Aire acondicionado, heladeras, lavarropas y servicio técnico de computadoras y celulares.</li>
              <li>Limpieza profunda, fumigación, jardinería y mantenimiento de piletas.</li>
              <li>Peluquería canina y felina a domicilio, veterinarios y urgencias veterinarias 24/7.</li>
              <li>Cuidado de mascotas, adultos mayores y niños, y enfermería a domicilio.</li>
              <li>Fletes, minimudanzas, peones de carga, arreglos menores, pintura y albañilería ligera.</li>
            </ul>

            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #0c2a4d; text-transform: uppercase; letter-spacing: 0.5px;">Tres modalidades para contratar</p>
            <ol style="margin: 0 0 14px 0; padding-left: 18px;">
              <li><strong>Urgencias 24/7:</strong> publicás el problema y un profesional responde a cualquier hora.</li>
              <li><strong>Citas programadas:</strong> elegís día y horario, reservás con seña y dejás datos de acceso.</li>
              <li><strong>Presupuestos:</strong> describís el trabajo con fotos y recibís propuestas de varios profesionales.</li>
            </ol>

            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #0c2a4d; text-transform: uppercase; letter-spacing: 0.5px;">Cómo funciona</p>
            <ol style="margin: 0 0 14px 0; padding-left: 18px;">
              <li>Contá qué necesitás, adjuntá fotos y indicá la ubicación.</li>
              <li>Elegís urgencia, cita programada o presupuesto.</li>
              <li>El profesional llega y presenta una estimación o presupuesto para tu aprobación.</li>
              <li>Aprobás, se realiza el trabajo y liberás el pago cuando esté todo bien.</li>
              <li>Calificás al profesional para que el resto de la comunidad sepa cómo trabajó.</li>
            </ol>

            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #0c2a4d; text-transform: uppercase; letter-spacing: 0.5px;">Para el profesional</p>
            <ul style="margin: 0 0 14px 0; padding-left: 18px;">
              <li>Agenda, servicios, disponibilidad y reordenamiento de tu oferta.</li>
              <li>Solicitudes, estimaciones en sitio y cotizaciones desde el panel.</li>
              <li>Contacto directo con el cliente por WhatsApp.</li>
              <li>Reportes de demanda, clientes y rendimiento.</li>
            </ul>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Urgencias 24/7</span>
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Citas Programadas</span>
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Presupuestos</span>
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Profesionales Verificados</span>
            </div>
          </div>
        </details>

        <!-- 3. Mercado -->
        <details>
          <summary style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; font-weight: 700; color: #1e293b; cursor: pointer; list-style: none; user-select: none;">
            <span>Mercado y Envíos Express</span>
            <span style="font-size: 13px; color: #64748b;">▼</span>
          </summary>
          <div style="padding: 0 24px 24px 24px; color: #4b5563; font-size: 14px; line-height: 1.6;">
            <p style="margin: 0 0 14px 0;">La capa logística que conecta comercios, hogares y repartidores para que recibas todo sin salir de casa. Acá conviven los pedidos de tus tiendas del barrio, los envíos express, la compra recurrente y hasta el auxilio vial, con repartidores de confianza y seguimiento en tiempo real.</p>

            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #0c2a4d; text-transform: uppercase; letter-spacing: 0.5px;">Qué incluye</p>
            <ul style="margin: 0 0 14px 0; padding-left: 18px;">
              <li>Envíos express de comercios del barrio y de abastos recurrentes, como agua purificada.</li>
              <li>Auxilio vial y mecánica: gomerías, grúas y asistencia 24/7.</li>
              <li>Pool de repartidores disponibles para tomar pedidos al instante.</li>
              <li>Despacho propio del comercio con red de repartidores de confianza.</li>
              <li>Seguimiento del pedido y calificación de cada entrega.</li>
            </ul>

            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; color: #0c2a4d; text-transform: uppercase; letter-spacing: 0.5px;">Cómo funciona</p>
            <ol style="margin: 0 0 14px 0; padding-left: 18px;">
              <li>El comercio o el usuario arma el pedido con lo que necesita.</li>
              <li>El pedido entra al pool general o se asigna a un repartidor de confianza del comercio.</li>
              <li>El repartidor retira, entrega y confirma la recepción.</li>
              <li>Recibís notificaciones de cada paso y calificás la entrega.</li>
            </ol>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Envíos Express</span>
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Repartidores de Confianza</span>
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Auxilio Vial</span>
              <span style="background: #f3f4f6; color: #4b5563; padding: 3px 10px; font-size: 12px; font-weight: 500;">Abastos Recurrentes</span>
            </div>
          </div>
        </details>

      </div>
    </div>

  </div>
</div>
                    </div>
                    <div id="section-contactos" class="page-section rounded-none" style="display:none;max-width:none;margin:0;background:white;border-radius:0;padding:0;box-shadow:none;">
                        <div style="width:100%;padding:32px 24px;box-sizing:border-box;">
                        <h2 style="font-size:24px;font-weight:bold;color:#0c2a4d;margin:0 0 20px;">Contactos</h2>
                        <div class="contacto-grid">
                            <div>
                                <p style="font-size:15px;color:#374151;line-height:1.8;margin:0 0 20px;">Podés escribirnos cuando necesites ayuda o quieras sumar tu comercio o servicio a PideAca:</p>
                                <div style="display:flex;flex-direction:column;gap:16px;">
                                    <div style="display:flex;align-items:flex-start;gap:12px;">
                                        <span style="flex:0 0 38px;width:38px;height:38px;display:flex;align-items:center;justify-content:center;background:#fff7ed;color:#D24C19;border-radius:8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                                        </span>
                                        <div>
                                            <p style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 2px;">Email</p>
                                            <a href="mailto:hola@pideaca.com" style="font-size:14px;color:#0c2a4d;font-weight:600;text-decoration:none;">hola@pideaca.com</a>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:flex-start;gap:12px;">
                                        <span style="flex:0 0 38px;width:38px;height:38px;display:flex;align-items:center;justify-content:center;background:#fff7ed;color:#D24C19;border-radius:8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                                        </span>
                                        <div>
                                            <p style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 2px;">Teléfono</p>
                                            <a href="tel:+541155551234" style="font-size:14px;color:#0c2a4d;font-weight:600;text-decoration:none;">+54 11 5555-1234</a>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:flex-start;gap:12px;">
                                        <span style="flex:0 0 38px;width:38px;height:38px;display:flex;align-items:center;justify-content:center;background:#fff7ed;color:#D24C19;border-radius:8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        </span>
                                        <div>
                                            <p style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 2px;">Horario de atención</p>
                                            <p style="font-size:14px;color:#0c2a4d;font-weight:600;margin:0;">Lunes a domingo, 24 horas.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="contacto-form">
                                <form id="contacto-form" novalidate onsubmit="submitContactInquiry(event)">
                                    <div style="margin-bottom:12px;">
                                        <label for="contact-full-name">Nombre completo</label>
                                        <input type="text" id="contact-full-name" name="full_name" maxlength="255" placeholder="Tu nombre y apellido">
                                    </div>
                                    <div style="margin-bottom:12px;">
                                        <label for="contact-email">Correo electrónico</label>
                                        <input type="email" id="contact-email" name="email" maxlength="255" placeholder="tu@correo.com">
                                    </div>
                                    <div style="margin-bottom:12px;">
                                        <label for="contact-inquiry-type">Tipo de consulta</label>
                                        <select id="contact-inquiry-type" name="inquiry_type">
                                            <option value="">Seleccioná una opción</option>
                                            <option value="consulta">Consulta</option>
                                            <option value="sugerencia">Sugerencia</option>
                                            <option value="reclamo">Reclamo</option>
                                        </select>
                                    </div>
                                    <div style="margin-bottom:12px;">
                                        <label for="contact-message">Mensaje</label>
                                        <textarea id="contact-message" name="message" rows="5" maxlength="5000" placeholder="Contanos en qué podemos ayudarte"></textarea>
                                    </div>
                                    <div id="contacto-msg" role="status" aria-live="polite" style="display:none;font-size:12px;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;padding:8px 12px;border-radius:0;"></div>
                                    <button type="submit" id="contacto-submit" style="width:100%;margin-top:12px;padding:11px;background:#D24C19;color:#ffffff;border:1px solid #D24C19;border-radius:4px;font-size:14px;font-weight:700;cursor:pointer;">Enviar Mensaje</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                </section>

        </main>
        <div style="background:white;height:8px;"></div>
        <footer style="background:#0a0f1a;color:white;padding:14px 0;">
            <div style="text-align:center;">
                @include('partials.footer-group-icons')
                <p style="font-size:10px;color:#9ca3af;margin:0;">&copy; {{ date('Y') }} pideaca.com - Todos los derechos reservados.</p>
            </div>
        </footer>

        {{-- LOGIN MODAL --}}
        <div id="login-overlay" class="login-overlay" style="display:none;">
            <div class="login-box" style="background:#ffffff;width:360px;max-width:92vw;border-radius:0;box-shadow:0 20px 50px rgba(0,0,0,0.3);position:relative;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:#ffffff;border-bottom:1px solid #e5e7eb;">
                    <img src="{{ asset('images/logo.png') }}" alt="PideAca" style="height:44px;width:auto;max-width:150px;object-fit:contain;display:block;" />
                    <button onclick="closeLogin()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;line-height:1;font-weight:600;">&times;</button>
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
                        <button type="button" onclick="recoverPassword()" style="padding:10px;background:#ffffff;color:#D24C19;border:1px solid #d1d5db;border-radius:0;font-size:13px;font-weight:600;cursor:pointer;">Recuperar contraseña</button>
                    </div>
                </form>

                <form id="recovery-form" style="padding:20px;display:none;flex-direction:column;gap:14px;">
                    <div>
                        <label for="recovery-email" style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Correo</label>
                        <input type="email" id="recovery-email" name="email" placeholder="Ingrese el correo"
                            style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #d1d5db;border-radius:0;font-size:13px;outline:none;">
                    </div>
                    <div id="recovery-msg" style="display:none;font-size:12px;padding:9px 12px;"></div>
                    <button type="submit" id="recovery-submit" class="header-btn" style="padding:10px;background:#D24C19;color:white;border:1px solid #D24C19;border-radius:4px;font-size:14px;font-weight:700;cursor:pointer;">Enviar enlace</button>
                    <button type="button" onclick="backToLogin()" style="padding:10px;background:#ffffff;color:#D24C19;border:1px solid #d1d5db;border-radius:0;font-size:13px;font-weight:600;cursor:pointer;">Volver a iniciar sesión</button>
                    <p style="font-size:11.5px;line-height:1.5;color:#64748b;margin:0;">Te enviaremos un correo con un botón para restablecer tu contraseña. El enlace dura 60 minutos y solo puede usarse una vez.</p>
                </form>
            </div>
        </div>

        {{-- REGISTRO MODAL --}}
        <div id="register-overlay" class="login-overlay" style="display:none;">
            <div class="login-box" style="background:#ffffff;width:360px;max-width:92vw;border-radius:0;box-shadow:0 20px 50px rgba(0,0,0,0.3);position:relative;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:#ffffff;border-bottom:1px solid #e5e7eb;">
                    <img src="{{ asset('images/logo.png') }}" alt="PideAca" style="height:44px;width:auto;max-width:150px;object-fit:contain;display:block;" />
                    <button onclick="closeRegister()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#6b7280;line-height:1;font-weight:600;">&times;</button>
                </div>
                <form id="register-form" style="padding:20px;display:flex;flex-direction:column;gap:14px;">
                    <div>
                        <label for="register-name" style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Nombre completo</label>
                        <input type="text" id="register-name" name="name" placeholder="Ingrese su nombre completo"
                            style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #d1d5db;border-radius:0;font-size:13px;outline:none;">
                    </div>
                    <div>
                        <label for="register-email" style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Correo</label>
                        <input type="email" id="register-email" name="email" placeholder="Ingrese el correo"
                            style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #d1d5db;border-radius:0;font-size:13px;outline:none;">
                    </div>
                    <div>
                        <label for="register-type" style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Tipo de cliente</label>
                        <select id="register-type" name="type_user_id"
                            style="width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #d1d5db;border-radius:0;font-size:13px;outline:none;background:#ffffff;">
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                    <div>
                        <label for="register-password" style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Contraseña</label>
                        <div style="position:relative;">
                            <input type="password" id="register-password" name="password" placeholder="Ingrese su contraseña"
                                style="width:100%;box-sizing:border-box;padding:9px 36px 9px 12px;border:1px solid #d1d5db;border-radius:0;font-size:13px;outline:none;">
                            <button type="button" onclick="toggleRegisterPassword()" id="reg-eye-toggle"
                                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:2px;color:#6b7280;line-height:1;"
                                title="Ver contraseña">
                                <svg id="reg-eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                <svg id="reg-eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="register-password-confirm" style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">Confirmar contraseña</label>
                        <div style="position:relative;">
                            <input type="password" id="register-password-confirm" name="password_confirmation" placeholder="Repita la contraseña"
                                style="width:100%;box-sizing:border-box;padding:9px 36px 9px 12px;border:1px solid #d1d5db;border-radius:0;font-size:13px;outline:none;">
                            <button type="button" onclick="toggleRegisterPasswordConfirm()" id="reg-eye2-toggle"
                                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:2px;color:#6b7280;line-height:1;"
                                title="Ver contraseña">
                                <svg id="reg-eye2-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                <svg id="reg-eye2-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                    </div>
                    <div id="register-error" style="display:none;font-size:12px;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;padding:8px 12px;border-radius:0;"></div>
                    <div id="register-ok" style="display:none;font-size:12px;color:#047857;background:#ecfdf5;border:1px solid #a7f3d0;padding:8px 12px;border-radius:0;"></div>
                    <div id="register-spinner-overlay" style="display:none;position:absolute;top:0;left:0;right:0;bottom:0;background:transparent;z-index:10;align-items:center;justify-content:center;border-radius:0;">
                        <div class="spinner-ring"></div>
                    </div>
                    <div>
                        <button type="submit" id="register-submit" class="header-btn" style="width:100%;box-sizing:border-box;display:block;padding:10px;background:#D24C19;color:white;border:1px solid #D24C19;border-radius:4px;font-size:14px;font-weight:700;cursor:pointer;">Registrarse</button>
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

    :root {
        --qs-primary: #0b2545;
        --qs-accent: #e65100;
        --qs-text: #334155;
        --qs-muted: #64748b;
        --qs-bg-light: #f8fafc;
    }
    #section-quienes-somos,
    #section-inicio,
    #section-servicios,
    #section-contactos {
        scroll-margin-top: 70px;
    }
    .contacto-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        align-items: start;
    }
    .contacto-form {
        border-left: 1px solid #e5e7eb;
        padding-left: 32px;
    }
    .contacto-form label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 5px;
    }
    .contacto-form input,
    .contacto-form select,
    .contacto-form textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 9px 12px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        background: #ffffff;
        color: #1f2937;
        font-size: 13px;
        font-family: inherit;
        outline: none;
    }
    .contacto-form input:focus,
    .contacto-form select:focus,
    .contacto-form textarea:focus {
        border-color: #D24C19;
    }
    .contacto-form textarea {
        min-height: 110px;
        resize: vertical;
    }
    @media (max-width: 768px) {
        .contacto-grid {
            grid-template-columns: 1fr;
            gap: 24px;
        }
        .contacto-form {
            border-left: none;
            border-top: 1px solid #e5e7eb;
            padding-left: 0;
            padding-top: 24px;
        }
    }
    #top-social-bar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    .quienes-somos-section {
        padding: 2rem 20px;
        background-color: #ffffff;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: var(--qs-text);
    }
    .qs-container {
        max-width: 1400px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 50px;
        align-items: start;
    }
    .qs-image-col {
        position: sticky;
        top: 30px;
        width: 100%;
    }
    .illustration-wrapper {
        width: 100%;
        height: 560px;
        border-radius: 0;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
    }
    .floating-item { animation: floatAnim 4s ease-in-out infinite; }
    .floating-item-delay { animation: floatAnim 4s ease-in-out 2s infinite; }
    @keyframes floatAnim {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-7px); }
    }
    .qs-badge {
        color: var(--qs-accent);
        background-color: #fff3e0;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: inline-block;
        margin-bottom: 10px;
    }
    .qs-text-col h2 {
        font-size: 2.2rem;
        color: var(--qs-primary);
        margin: 0 0 15px 0;
    }
    .qs-brand { color: var(--qs-accent); }
    .qs-lead {
        font-size: 1.05rem;
        line-height: 1.6;
        margin-bottom: 30px;
        text-align: justify;
    }
    .qs-section-block { margin-bottom: 30px; }
    .qs-section-block p { text-align: justify; }
    .qs-section-block h3 {
        font-size: 1.3rem;
        color: var(--qs-primary);
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 8px;
        margin-bottom: 15px;
    }
    .qs-modules-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .module-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: #ffffff;
        padding: 12px;
        border-radius: 0;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .mod-icon { color: var(--qs-accent); display: flex; align-items: center; flex-shrink: 0; }
    .module-item strong {
        display: block;
        font-size: 0.9rem;
        color: var(--qs-primary);
    }
    .module-item p {
        margin: 2px 0 0 0;
        font-size: 0.8rem;
        color: var(--qs-muted);
    }
    .qs-small-text {
        font-size: 0.9rem;
        color: var(--qs-muted);
        line-height: 1.5;
        text-align: justify;
    }
    .qs-full {
        display: flex;
        flex-direction: column;
        gap: 40px;
        margin: 45px auto 0;
        max-width: 1400px;
    }
    .qs-full .qs-section-block { margin-bottom: 0; }
    .qs-pillars-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-top: 15px;
    }
    .qs-pillar-card {
        background: var(--qs-bg-light);
        border: 1px solid #e2e8f0;
        border-top: 3px solid var(--qs-accent);
        padding: 18px;
    }
    .qs-pillar-icon {
        display: block;
        font-size: 22px;
        line-height: 1;
        margin-bottom: 10px;
    }
    .qs-pillar-card h4 {
        color: var(--qs-primary);
        font-size: 0.95rem;
        margin: 0 0 8px 0;
    }
    .qs-pillar-card p {
        color: var(--qs-muted);
        font-size: 0.85rem;
        line-height: 1.5;
        margin: 0;
    }
    .qs-benefits-grid,
    .qs-tech-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-top: 15px;
    }
    .qs-tech-grid { grid-template-columns: repeat(4, 1fr); }
    .qs-benefit-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        padding: 16px;
    }
    .qs-benefit-card h4 {
        color: var(--qs-primary);
        font-size: 0.92rem;
        margin: 0 0 6px 0;
    }
    .qs-benefit-card p {
        color: var(--qs-muted);
        font-size: 0.84rem;
        line-height: 1.5;
        margin: 0;
    }
    .qs-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }
    .qs-tag {
        background: #fff3e0;
        border: 1px solid #fed7aa;
        border-radius: 20px;
        color: var(--qs-accent);
        font-size: 0.78rem;
        font-weight: 600;
        padding: 5px 11px;
    }
    .qs-cta {
        align-items: center;
        background: var(--qs-primary);
        border-left: 4px solid var(--qs-accent);
        color: #ffffff;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: space-between;
        padding: 26px 28px;
    }
    .qs-cta h3 {
        border: none;
        color: #ffffff;
        font-size: 1.25rem;
        margin: 0 0 6px 0;
        padding: 0;
    }
    .qs-cta p {
        color: #cbd5e1;
        font-size: 0.9rem;
        margin: 0;
    }
    .qs-cta-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .qs-cta-btn {
        background: var(--qs-accent);
        border: 1px solid var(--qs-accent);
        color: #ffffff;
        font-size: 0.86rem;
        font-weight: 700;
        padding: 10px 16px;
        text-decoration: none;
    }
    .qs-cta-btn:hover {
        background: #ffffff;
        color: var(--qs-accent);
    }
    .qs-cta-btn-outline {
        background: transparent;
        border-color: #ffffff;
        color: #ffffff;
    }
    .qs-cta-btn-outline:hover {
        background: #ffffff;
        border-color: #ffffff;
        color: var(--qs-primary);
    }
    @media (max-width: 1100px) {
        .qs-pillars-grid,
        .qs-benefits-grid,
        .qs-tech-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 900px) {
        .qs-container { grid-template-columns: 1fr; }
        .qs-modules-grid,
        .qs-pillars-grid, .qs-benefits-grid, .qs-tech-grid { grid-template-columns: 1fr; }
        .qs-image-col { position: relative; top: 0; }
        .illustration-wrapper { height: 240px; }
        .qs-cta { padding: 20px; }
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
    .ad-card:hover { border-color: #f97316; }

    .ad-banner {
        width: 100%;
        height: 180px;
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: linear-gradient(180deg, #f8fafc 0%, #eef2f6 100%);
        border-bottom: 1px solid #eef2f6;
    }
    .ad-avatar,
    .ad-avatar-img {
        width: 152px;
        height: 152px;
        flex: none;
        box-sizing: border-box;
        border-radius: 50%;
        border: 3px solid #ffffff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);
        transition: transform 0.2s ease;
    }
    .ad-avatar-img {
        object-fit: cover;
        display: block;
        background: #f1f5f9;
    }
    .ad-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 44px;
        font-weight: 800;
        letter-spacing: 1px;
    }
    @media (hover: hover) {
        .flip-card:hover .ad-avatar,
        .flip-card:hover .ad-avatar-img { transform: scale(1.05); }
    }
    
    .ad-body { 
        padding: 16px; 
        display: flex; 
        flex-direction: column; 
        flex-grow: 1; 
        position: relative;
        padding-bottom: 48px !important;
    }

    .ad-badge {
        align-self: flex-start;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 20px;
        color: #c2410c;
        font-size: 9px !important;
        font-weight: 700;
        letter-spacing: 0.6px;
        padding: 3px 9px !important;
        margin-bottom: 10px !important;
        text-transform: uppercase;
    }
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
        padding-right: 40px; 
        height: 32px !important; 
        display: -webkit-box; 
        -webkit-line-clamp: 2; 
        -webkit-box-orient: vertical; 
        overflow: hidden; 
    }

    .card-action-icon { 
        position: absolute; 
        bottom: 12px; 
        right: 12px; 
        width: 32px; 
        height: 32px; 
        border-radius: 50%; 
        background-color: #D24C19; 
        border: 1px solid #D24C19; 
        color: #ffffff; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        box-shadow: 0 4px 10px rgba(210, 76, 25, 0.28);
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
        transform: translateY(0);
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    @media (hover: hover) {
        .flip-card:hover .flip-front {
            transform: translateY(-4px);
            box-shadow: 0 16px 30px -8px rgba(0,0,0,0.18), 0 6px 12px rgba(0,0,0,0.08);
        }
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
    .ad-filter-tab { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; font-size: 11px; font-weight: 600; color: #6b7280; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s ease; white-space: nowrap; user-select: none; margin-bottom: -2px; letter-spacing: 0.02em; position: relative; }
    .ad-filter-tab svg { flex-shrink: 0; }
    .ad-filter-tab:hover { color: #374151; background: #9ca3af; }
    .ad-filter-tab.active { color: #D24C19; border-bottom-color: #D24C19; }
    .ad-filter-tab:hover { z-index: 10; }

    .ad-search-inner { display: flex; align-items: center; background: #fff; border-radius: 0; box-shadow: 0 1px 4px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #d1d5db; transition: border-color 0.15s ease; height: 24px; }
    .ad-search-inner:focus-within { border-color: #D24C19; }
    #adSearchInput { flex: 1; min-width: 0; height: 100%; border: none; outline: none; background: transparent; padding: 0 8px; font-size: 11px; color: #111827; font-family: inherit; border-radius: 0; }
    #adSearchInput::placeholder { color: #9ca3af; }
    #adClearBtn { display: none; align-items: center; justify-content: center; background: none; border: none; padding: 0 4px; color: #6b7280; cursor: pointer; line-height: 1; font-size: 14px; }
    #adClearBtn:hover { color: #111827; }
    #adSubmitBtn { width: 32px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #D24C19; color: white; border: 1px solid #d1d5db; border-radius: 0; font-size: 11px; font-weight: 600; cursor: pointer; box-sizing: border-box; transition: all 0.2s ease; padding: 0; align-self: stretch; }
    #adSubmitBtn:hover { background: #fff; color: #D24C19; border-color: #D24C19; }
    .ad-search-wrap { flex: 0 0 auto; min-width: 260px; position: relative; z-index: 30; align-self: center; }

    .ad-suggest-box { position: absolute; top: 100%; left: 0; right: 0; z-index: 40; background: #fff; border-radius: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.18); border: 1px solid #e5e7eb; border-top: none; max-height: 420px; overflow-y: auto; overflow-x: hidden; display: none; padding: 8px; box-sizing: border-box; }
    .sug-sec { font-size: 10px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #6b7280; padding: 8px 10px 4px; }
    .sug-row { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 0; cursor: pointer; font-size: 13px; color: #1f2937; }
    .sug-row svg { flex-shrink: 0; color: #6b7280; }
    .sug-row:hover { background: #f3f4f6; }
    .sug-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sug-count { font-size: 11px; color: #9ca3af; flex-shrink: 0; }
    .sug-badge { flex-shrink: 1; min-width: 0; font-size: 9px; font-weight: 700; color: #fff; padding: 2px 8px; border-radius: 0; letter-spacing: 0.03em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sug-empty { padding: 18px; text-align: center; font-size: 12px; color: #6b7280; }

    @media (max-width: 768px) {
        .nav-container { flex-wrap: nowrap !important; }
        .logo-img { width: 100px !important; height: 40px !important; }
        .ad-search-wrap { flex: 0 0 auto; min-width: 80px; max-width: none; }
        .ad-search-inner { height: 24px; }
        #adSearchInput { padding: 2px 6px; font-size: 10px; }
        #adSubmitBtn { width: 28px; }
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

    #menu-toggle { display: none !important; }

    @media (max-width: 768px) {
        .nav-link, .nav-separator { display: none !important; }
        #menu-toggle { display: block !important; }
        .logo-img { width: 140px !important; height: 52px !important; }
        .nav-container { padding: 1px 8px !important; justify-content: space-between !important; }
        .carousel-slide { height: 140px !important; }
        
        .provider-grid { 
            grid-template-columns: repeat(2, 1fr) !important; 
            gap: 10px !important; 
            padding: 4px 12px !important; 
        }
        #adFilterBar { padding: 0 8px !important; gap: 2px !important; flex-wrap: nowrap !important; overflow: hidden !important; }
        .ad-filter-tab { font-size: 0 !important; padding: 3px 6px !important; gap: 0 !important; }
        .ad-filter-tab svg { width: 16px !important; height: 16px !important; }
        .ad-search-wrap { min-width: 0 !important; flex: 0 1 160px !important; }
        .ad-search-inner { height: 24px; }
        #adSearchInput { padding: 2px 6px; font-size: 10px; }
        #adSubmitBtn { width: 26px; }
        #adResultCount-wrap { font-size: 9px !important; white-space: nowrap !important; padding: 8px 0 !important; }
        .ad-banner { height: 168px !important; }
        .ad-avatar,
        .ad-avatar-img { width: 128px; height: 128px; }
        .ad-avatar { font-size: 38px; }
        .ad-title { height: 26px !important; font-size: 11px !important; }
        .ad-desc { height: 24px !important; font-size: 10px !important; }
        .ad-card-info { margin-bottom: 4px !important; }
        .ad-card .ad-body { padding: 16px !important; padding-bottom: 44px !important; }
    }

    @media (max-width: 480px) {
        .provider-grid { 
            grid-template-columns: 1fr !important; 
        }
        .ad-banner { height: 148px !important; }
        .ad-avatar,
        .ad-avatar-img { width: 112px; height: 112px; }
        .ad-avatar { font-size: 34px; }
    }
    @media (min-width: 769px) {
        #mobile-menu { display: none !important; }
    }

    .login-overlay {
        position: fixed; inset: 0; z-index: 200;
        background: rgba(7, 26, 48, 0.6);
        display: flex; align-items: flex-start; justify-content: center;
        padding: 48px 20px 40px;
        overflow-y: auto;
    }
    .login-box { margin: auto; animation: loginPop 0.25s ease; }
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
        const carousel = document.getElementById('hero-carousel');
        if (carousel) carousel.style.display = (key === 'inicio') ? 'block' : 'none';
        const menu = document.getElementById('mobile-menu');
        if (menu) menu.style.display = 'none';
        const suggest = document.getElementById('adSuggestBox');
        if (suggest) suggest.style.display = 'none';
        if (history.replaceState) {
            history.replaceState(null, '', window.location.pathname + window.location.search);
        }
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;
        window.scrollTo(0, 0);
        requestAnimationFrame(function () {
            window.scrollTo(0, 0);
        });
    }

    function showContactError(field, text) {
        const message = document.getElementById('contacto-msg');
        if (message) {
            message.style.display = 'block';
            message.style.color = '#dc2626';
            message.style.background = '#fef2f2';
            message.style.border = '1px solid #fecaca';
            message.textContent = text;
        }
        if (field && typeof field.focus === 'function') {
            field.focus();
        }
    }

    function clearContactError() {
        const message = document.getElementById('contacto-msg');
        if (!message) {
            return;
        }
        message.style.display = 'none';
        message.textContent = '';
    }

    ['contact-full-name', 'contact-email', 'contact-inquiry-type', 'contact-message'].forEach(function (id) {
        const field = document.getElementById(id);
        if (!field) {
            return;
        }
        field.addEventListener('input', clearContactError);
        field.addEventListener('change', clearContactError);
    });

    async function submitContactInquiry(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const button = document.getElementById('contacto-submit');
        const message = document.getElementById('contacto-msg');
        const fullNameInput = document.getElementById('contact-full-name');
        const emailInput = document.getElementById('contact-email');
        const inquiryTypeInput = document.getElementById('contact-inquiry-type');
        const messageInput = document.getElementById('contact-message');
        const fullName = fullNameInput ? fullNameInput.value.trim() : '';
        const email = emailInput ? emailInput.value.trim() : '';
        const inquiryType = inquiryTypeInput ? inquiryTypeInput.value : '';
        const body = messageInput ? messageInput.value.trim() : '';

        clearContactError();

        // Validación secuencial: se detiene en el primer campo inválido.
        if (!fullName) {
            showContactError(fullNameInput, 'Ingrese su nombre completo');
            return;
        }

        if (!email) {
            showContactError(emailInput, 'Ingrese el correo');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showContactError(emailInput, 'Ingrese un correo válido');
            return;
        }

        if (!inquiryType) {
            showContactError(inquiryTypeInput, 'Seleccione un tipo de consulta');
            return;
        }

        if (!body) {
            showContactError(messageInput, 'Ingrese su mensaje');
            return;
        }

        const payload = {
            full_name: fullName,
            email: email,
            inquiry_type: inquiryType,
            message: body
        };

        button.disabled = true;
        button.textContent = 'Enviando...';

        try {
            const response = await fetch(@json(url('/contact-inquiries')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });
            const data = await response.json().catch(function () { return {}; });

            if (!response.ok) {
                const firstKey = data.errors ? Object.keys(data.errors)[0] : null;
                const firstError = firstKey ? data.errors[firstKey][0] : null;
                throw new Error(firstError || data.message || 'No pudimos enviar tu mensaje. Intentá nuevamente.');
            }

            form.reset();
            message.style.display = 'block';
            message.style.color = '#047857';
            message.style.background = '#ecfdf5';
            message.style.border = '1px solid #a7f3d0';
            message.textContent = data.message || '¡Gracias! Recibimos tu mensaje.';
        } catch (error) {
            showContactError(null, error.message || 'No pudimos enviar tu mensaje. Intentá nuevamente.');
        } finally {
            button.disabled = false;
            button.textContent = 'Enviar Mensaje';
        }
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

    function distanceFilterMode() {
        var todas = document.getElementById('radio-todas');
        var range = document.getElementById('modal-filtro-range');
        var wrap = document.getElementById('modal-filtro-slider-wrap');
        var unlimited = todas && todas.checked;

        if (range) range.disabled = unlimited;
        if (wrap) wrap.style.opacity = unlimited ? '.45' : '1';
    }

    // Por defecto se busca en todas partes (sin límite de distancia).
    // activo:false equivale a la opción "Buscar en todas partes".
    window.filtroDistancia = { activo: false, radioKm: 10, ubicacion: null };

    function aplicarFiltroDistancia() {
        var modo = document.querySelector('input[name="modo_distancia"]:checked');
        window.filtroDistancia = {
            activo: modo ? modo.value === 'distancia' : false,
            radioKm: Number((document.getElementById('modal-filtro-range') || {}).value || 0),
            ubicacion: (document.getElementById('modal-filtro-ubicacion') || {}).value || null
        };

        var modal = document.getElementById('modal-filtro');
        if (modal) modal.style.display = 'none';

        if (typeof adFilterProviders === 'function') adFilterProviders();
    }

    function adFilterProviders() {
        var query = (adSearchInput.value || '').toLowerCase().trim();
        var filtro = window.filtroDistancia;
        var conDistancia = !!(filtro && filtro.activo && filtro.radioKm > 0);
        var refLat = -34.6037;
        var refLng = -58.3816;

        if (conDistancia && filtro.ubicacion) {
            var semilla = 0;
            var texto = String(filtro.ubicacion).toLowerCase();
            for (var s = 0; s < texto.length; s++) semilla = (semilla * 31 + texto.charCodeAt(s)) % 100000;
            refLat = -34.6037 + ((semilla % 200) - 100) / 1000;
            refLng = -58.3816 + ((Math.floor(semilla / 200) % 200) - 100) / 1000;
        }

        function haversine(lat1, lng1, lat2, lng2) {
            var R = 6371;
            var dLat = (lat2 - lat1) * Math.PI / 180;
            var dLng = (lng2 - lng1) * Math.PI / 180;
            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
                + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
                * Math.sin(dLng / 2) * Math.sin(dLng / 2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        adCurrentPage = 1;
        var visibleCount = 0;

        adAllCards.forEach(function (card) {
            var groups = (card.getAttribute('data-groups') || '').split(',');
            var search = card.getAttribute('data-search') || '';
            var matchCat = (adActiveCategory === 'Todos') || groups.indexOf(adActiveCategory) !== -1;
            var matchSearch = !query || search.indexOf(query) !== -1;
            var matchDist = true;

            if (conDistancia) {
                var idx = Number(card.getAttribute('data-idx') || 0);
                var lat = -34.6037 + ((idx * 37) % 100) / 100 * 0.2;
                var lng = -58.3816 + ((idx * 53) % 100) / 100 * 0.2;
                matchDist = haversine(lat, lng, refLat, refLng) <= filtro.radioKm;
            }

            var show = matchCat && matchSearch && matchDist;
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

        var filtro = window.filtroDistancia;
        if (filtro && filtro.activo && filtro.radioKm > 0) {
            window.filtroDistancia = {
                activo: true,
                radioKm: Number(filtro.radioKm),
                ubicacion: filtro.ubicacion || null
            };
        }

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

    adFilterProviders();
    window.addEventListener('resize', adRenderPage);
    adToggleClear();

    const loginOverlay = document.getElementById('login-overlay');
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');
    const loginSpinnerOverlay = document.getElementById('login-spinner-overlay');
    const recoveryForm = document.getElementById('recovery-form');
    const recoveryEmail = document.getElementById('recovery-email');
    const recoveryMsg = document.getElementById('recovery-msg');

    function setRecoveryMsg(text, kind) {
        if (!text) {
            recoveryMsg.style.display = 'none';
            recoveryMsg.textContent = '';
            return;
        }
        recoveryMsg.textContent = text;
        recoveryMsg.style.display = 'block';
        if (kind === 'ok') {
            recoveryMsg.style.color = '#047857';
            recoveryMsg.style.background = '#ecfdf5';
            recoveryMsg.style.border = '1px solid #a7f3d0';
        } else {
            recoveryMsg.style.color = '#dc2626';
            recoveryMsg.style.background = '#fef2f2';
            recoveryMsg.style.border = '1px solid #fecaca';
        }
    }

    function backToLogin() {
        recoveryForm.style.display = 'none';
        loginForm.style.display = 'flex';
        setRecoveryMsg('');
        loginError.style.display = 'none';
        document.getElementById('login-password').value = '';
        document.getElementById('login-email').focus();
    }

    function openLogin(prefillEmail) {
        loginError.style.display = 'none';
        setRecoveryMsg('');
        recoveryForm.style.display = 'none';
        loginForm.style.display = 'flex';
        document.getElementById('login-password').value = '';
        document.getElementById('login-password').type = 'password';
        document.getElementById('eye-open').style.display = 'block';
        document.getElementById('eye-closed').style.display = 'none';
        if (typeof prefillEmail === 'string' && prefillEmail) {
            document.getElementById('login-email').value = prefillEmail;
        }
        loginOverlay.style.display = 'flex';
        if (typeof prefillEmail === 'string' && prefillEmail) {
            document.getElementById('login-password').focus();
        } else {
            document.getElementById('login-email').focus();
        }
    }

    function closeLogin() {
        loginOverlay.style.display = 'none';
    }

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
                    window.location.href = '{{ url("/") }}';
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
        loginForm.style.display = 'none';
        recoveryForm.style.display = 'flex';
        loginError.style.display = 'none';
        setRecoveryMsg('');
        var loginEmail = document.getElementById('login-email').value.trim();
        if (loginEmail) recoveryEmail.value = loginEmail;
        recoveryEmail.focus();
    }

    recoveryForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var email = recoveryEmail.value.trim();
        if (!email) { setRecoveryMsg('Ingrese el correo', 'err'); return; }

        setRecoveryMsg('');
        var submitBtn = document.getElementById('recovery-submit');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';

        fetch('{{ url('/password/forgot') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ email: email })
        })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (r) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar enlace';
            if (r.ok && r.data.success) {
                setRecoveryMsg(r.data.message, 'ok');
                return;
            }
            var errors = r.data && r.data.errors;
            var msg = (errors && errors.email && errors.email[0]) || (r.data && r.data.message) || 'No pudimos procesar la solicitud.';
            setRecoveryMsg(msg, 'err');
        })
        .catch(function () {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar enlace';
            setRecoveryMsg('Error de conexión. Intentá de nuevo.', 'err');
        });
    });

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

    const registerOverlay = document.getElementById('register-overlay');
    const registerForm = document.getElementById('register-form');
    const registerError = document.getElementById('register-error');
    const registerOk = document.getElementById('register-ok');
    const registerSpinnerOverlay = document.getElementById('register-spinner-overlay');
    const registerType = document.getElementById('register-type');
    let registerTypesLoaded = false;

    function loadRegisterTypes() {
        if (registerTypesLoaded) return;
        fetch(@json(url('/registro/tipos')), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (res) { return res.json(); })
        .then(function (types) {
            registerType.innerHTML = '';
            var fallback = document.createElement('option');
            fallback.value = '';
            fallback.textContent = 'Seleccione una opción';
            registerType.appendChild(fallback);
            (types || []).forEach(function (t) {
                var opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.description;
                registerType.appendChild(opt);
            });
            registerTypesLoaded = true;
        })
        .catch(function () {
            registerType.innerHTML = '<option value="">No se pudieron cargar los tipos</option>';
        });
    }

    function showRegisterMessage(el, text, isError) {
        el.style.color = isError ? '#dc2626' : '#047857';
        el.style.background = isError ? '#fef2f2' : '#ecfdf5';
        el.style.border = isError ? '1px solid #fecaca' : '1px solid #a7f3d0';
        el.style.display = 'block';
        el.textContent = text;
    }

    function openRegister() {
        registerError.style.display = 'none';
        registerOk.style.display = 'none';
        document.getElementById('register-submit').disabled = false;
        document.getElementById('register-name').value = '';
        document.getElementById('register-email').value = '';
        document.getElementById('register-password').value = '';
        document.getElementById('register-password-confirm').value = '';
        resetRegisterPassword(document.getElementById('register-password'), 'reg-eye-open', 'reg-eye-closed');
        resetRegisterPassword(document.getElementById('register-password-confirm'), 'reg-eye2-open', 'reg-eye2-closed');
        loadRegisterTypes();
        registerOverlay.style.display = 'flex';
        document.getElementById('register-name').focus();
    }

    function resetRegisterPassword(input, openId, closedId) {
        input.type = 'password';
        document.getElementById(openId).style.display = 'block';
        document.getElementById(closedId).style.display = 'none';
    }

    function toggleRegisterPassword() {
        toggleRegisterField('register-password', 'reg-eye-open', 'reg-eye-closed');
    }

    function toggleRegisterPasswordConfirm() {
        toggleRegisterField('register-password-confirm', 'reg-eye2-open', 'reg-eye2-closed');
    }

    function toggleRegisterField(inputId, openId, closedId) {
        var input = document.getElementById(inputId);
        var open = document.getElementById(openId);
        var closed = document.getElementById(closedId);
        if (input.type === 'password') {
            input.type = 'text';
            open.style.display = 'none';
            closed.style.display = 'block';
        } else {
            input.type = 'password';
            open.style.display = 'block';
            closed.style.display = 'none';
        }
    }

    function closeRegister() {
        registerOverlay.style.display = 'none';
    }

    registerForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var fullName = document.getElementById('register-name').value.trim();
        var email = document.getElementById('register-email').value.trim();
        var typeId = registerType.value;
        var password = document.getElementById('register-password').value;
        var passwordConfirm = document.getElementById('register-password-confirm').value;
        registerError.style.display = 'none';
        registerOk.style.display = 'none';

        if (!fullName) { showRegisterMessage(registerError, 'Ingrese el nombre completo', true); return; }
        if (!email) { showRegisterMessage(registerError, 'Ingrese el correo', true); return; }
        if (!typeId) { showRegisterMessage(registerError, 'Seleccione el tipo de cliente', true); return; }
        if (!password) { showRegisterMessage(registerError, 'Ingrese la contraseña', true); return; }
        if (password.length < 8) { showRegisterMessage(registerError, 'La contraseña debe tener al menos 8 caracteres', true); return; }
        if (!passwordConfirm) { showRegisterMessage(registerError, 'Confirme la contraseña', true); return; }
        if (password !== passwordConfirm) { showRegisterMessage(registerError, 'Las contraseñas no coinciden', true); return; }

        var submitBtn = document.getElementById('register-submit');
        submitBtn.disabled = true;
        registerSpinnerOverlay.style.display = 'flex';

        fetch(@json(url('/registro')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                name: fullName,
                email: email,
                type_user_id: typeId,
                password: password,
                password_confirmation: passwordConfirm
            })
        })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (r) {
            submitBtn.disabled = false;
            registerSpinnerOverlay.style.display = 'none';
            if (r.ok && r.data.success) {
                showRegisterMessage(registerOk, r.data.message, false);
                document.getElementById('register-submit').disabled = true;
                setTimeout(function () {
                    closeRegister();
                    openLogin(document.getElementById('register-email').value.trim());
                }, 2000);
            } else {
                showRegisterMessage(registerError, r.data.message || 'No se pudo completar el registro', true);
            }
        })
        .catch(function () {
            submitBtn.disabled = false;
            registerSpinnerOverlay.style.display = 'none';
            showRegisterMessage(registerError, 'Error de conexión. Intentá de nuevo.', true);
        });
    });

    // Vuelta desde el formulario de nueva contraseña (?password_reset=1)
    (function () {
        var params = new URLSearchParams(window.location.search);
        if (params.get('password_reset') !== '1') return;
        openLogin();
        loginError.style.color = '#047857';
        loginError.style.background = '#ecfdf5';
        loginError.style.border = '1px solid #a7f3d0';
        loginError.style.display = 'block';
        loginError.textContent = 'Contraseña actualizada. Ya podés iniciar sesión.';
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, '', window.location.pathname);
        }
    })();
</script>
<div id="modal-filtro" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;padding:20px;border-radius:8px;max-width:420px;width:90%;position:relative;">
    <button type="button" aria-label="Cerrar" onclick="document.getElementById('modal-filtro').style.display='none'" style="position:absolute;top:8px;right:8px;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;background:none;border:none;border-radius:4px;color:#6b7280;font-size:20px;line-height:1;cursor:pointer;">&times;</button>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <h3 style="margin:0;font-size:15px;font-weight:700;color:#0c2a4d;">Filtro de distancia</h3>
      <label for="radio-distancia" style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;">
        <input type="radio" id="radio-distancia" name="modo_distancia" value="distancia" style="accent-color:#D24C19;cursor:pointer;" onchange="distanceFilterMode()">
        Por distancia
      </label>
      <div id="modal-filtro-slider-wrap" style="display:flex;flex-direction:column;gap:6px;padding-left:24px;opacity:.45;">
        <div style="display:flex;justify-content:space-between;font-size:12px;color:#374151;">
          <span>Radio</span>
          <strong id="modal-filtro-distance-label" style="color:#D24C19;">10 km</strong>
        </div>
        <input type="range" id="modal-filtro-range" min="1" max="50" value="10" step="1" disabled style="width:100%;accent-color:#D24C19;cursor:pointer;" oninput="document.getElementById('modal-filtro-distance-label').textContent=(this.value>=50?'50+':this.value)+' km'">
      </div>
      <label for="radio-todas" style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;">
        <input type="radio" id="radio-todas" name="modo_distancia" value="todas" checked style="accent-color:#D24C19;cursor:pointer;" onchange="distanceFilterMode()">
        Buscar en todas partes (sin límite de distancia)
      </label>
      <label for="modal-filtro-ubicacion" style="font-size:11.5px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.4px;">Ubicación de referencia (opcional)</label>
      <input type="text" id="modal-filtro-ubicacion" placeholder="Ej. Av. Siempre Viva 123" autocomplete="off" style="padding:9px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:12.5px;outline:none;">
    </div>
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
      <button type="button" onclick="aplicarFiltroDistancia()" style="background:#D24C19;color:#fff;border:none;border-radius:6px;padding:8px 16px;font-size:12.5px;font-weight:700;cursor:pointer;">Aplicar</button>
    </div>
  </div>
</div>
</html>
