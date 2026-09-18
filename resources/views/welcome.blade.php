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
            <div class="nav-container" style="max-width:1200px;margin:0 auto;padding:1px 2px;display:flex;align-items:center;justify-content:flex-end;gap:6px;position:relative;">
                <a href="/" onclick="window.location.reload(true);return false;" style="margin-right:auto;z-index:60;">
                    <img class="logo-img" src="{{ asset('images/logo.png') }}" alt="PideAca" style="width:170px;height:58px;border-radius:0;object-fit:contain;" />
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="#" onclick="window.location.reload()" class="nav-link" style="padding:4px 12px;color:white;font-size:13px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    Inicio
                </a>
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
            <div id="mobile-menu" style="display:none;background:#ffffff;padding:10px 8px;position:absolute;top:100%;left:0;right:0;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <div style="display:flex;justify-content:center;align-items:center;gap:32px;">
                    <a href="#" onclick="window.location.reload()" style="color:#1f2937;text-decoration:none;display:flex;align-items:center;justify-content:center;padding:6px;" title="Inicio">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    </a>
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
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:260px;overflow:hidden;background:white;">
                                <video autoplay muted loop playsinline preload="auto" style="width:100%;height:100%;object-fit:cover;">
                                    <source src="{{ asset('images/videos/video1.mp4') }}" type="video/mp4">
                                </video>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:260px;background:url('{{ asset('images/Imacarousel/slide2.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Servicios del Hogar 24/7</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Plomeros, electricistas, técnicos y estética a domicilio. Profesionales certificados.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Solicitar Servicio</button>
                                </div>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:260px;background:url('{{ asset('images/Imacarousel/slide3.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Auxilio Vial en Tiempo Real</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Grúas, mecánica ligera y gomería móvil con ubicación GPS.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Solicitar Auxilio</button>
                                </div>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:260px;background:url('{{ asset('images/Imacarousel/slide4.jpg') }}') center/cover no-repeat;">
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

                        <div style="max-width:620px;margin:0 auto 16px;position:relative;">
                            <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:15px;">&#128269;</span>
                            <input type="text" placeholder="Buscá por nombre, servicio o categoría..." readonly
                                style="width:100%;box-sizing:border-box;padding:12px 14px 12px 40px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;color:#6b7280;background:#f9fafb;outline:none;cursor:not-allowed;">
                        </div>

                        <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-bottom:20px;">
                            <span style="padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;background:#D24C19;color:white;cursor:pointer;">Todos</span>
                            @foreach($providers->pluck('category')->unique()->sort() as $cat)
                            <span style="padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;background:#f3f4f6;color:#374151;cursor:pointer;">{{ strtoupper($cat) }}</span>
                            @endforeach
                        </div>

                        <div class="ad-grid-zone" style="width:100%;box-sizing:border-box;">
                        <div class="provider-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:4px 28px;box-sizing:border-box;">

                            @foreach($providers as $idx => $provider)
                            <div class="flip-card" data-idx="{{ $idx }}"><div class="flip-inner"><div class="flip-face flip-front"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ $provider['banner_url'] ? asset('storage/' . $provider['banner_url']) : asset('images/publicidad/default.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">{{ strtoupper($provider['category']) }}</span>
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
                    <div id="section-quienes-somos" class="page-section" style="display:none;max-width:900px;margin:0 auto;background:white;border-radius:16px;padding:28px 24px;box-shadow:0 10px 30px rgba(0,0,0,0.08);">
                        <h2 style="font-size:24px;font-weight:bold;color:#0c2a4d;margin:0 0 12px;">Quienes Somos</h2>
                        <p style="font-size:15px;color:#374151;line-height:1.7;margin:0;">PideAca es una plataforma digital que conecta a tu comunidad con todo lo que necesita: gastronomía y comercios de proximidad, servicios del hogar, auxilio vial y abastos recurrentes. Nuestra misión es simplificar tu día a día reuniendo en un solo lugar a comercios y profesionales confiables, para que pidas o programes servicios con solo unos clics, con calidad, seguridad y en el horario que vos necesites.</p>
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
                <p style="font-size:11px;color:#d1d5db;margin:0;">Gastronomía, Hogar, Auxilio Vial y Abastos.</p>
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
    .ad-grid-zone { width: 100%; box-sizing: border-box; }
    
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

    .ad-banner { height: 110px !important; width: 100%; overflow: hidden; background-size: cover; background-position: center; }
    
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
        height: 34px !important; 
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
        .carousel-slide { height: 160px !important; }
        
        .provider-grid { 
            grid-template-columns: repeat(2, 1fr) !important; 
            gap: 10px !important; 
            padding: 4px 12px !important; 
        }
        .ad-banner { height: 90px !important; }
        .ad-title { height: 30px !important; font-size: 11px !important; }
        .ad-desc { height: 28px !important; font-size: 10px !important; }
    }

    @media (max-width: 480px) {
        .provider-grid { 
            grid-template-columns: 1fr !important; 
        }
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
    const adCards = document.querySelectorAll('.flip-card');
    const paginationWrap = document.getElementById('ad-pagination');

    function adGetPerPage() {
        const grid = document.querySelector('.provider-grid');
        if (!grid) return 8;
        const cols = window.getComputedStyle(grid).gridTemplateColumns.split(' ').length;
        return cols * 2;
    }

    function adRenderPage() {
        const perPage = adGetPerPage();
        const totalPages = Math.ceil(adCards.length / perPage);
        if (adCurrentPage > totalPages) adCurrentPage = totalPages;
        if (adCurrentPage < 1) adCurrentPage = 1;

        adCards.forEach(function (c, i) {
            const start = (adCurrentPage - 1) * perPage;
            const end = start + perPage;
            c.style.display = (i >= start && i < end) ? '' : 'none';
        });

        if (totalPages <= 1) { paginationWrap.style.display = 'none'; return; }
        paginationWrap.style.display = 'flex';

        const dots = document.getElementById('page-dots');
        dots.innerHTML = '';
        for (let p = 1; p <= totalPages; p++) {
            const d = document.createElement('span');
            d.textContent = p;
            d.style.cssText = 'padding:2px 6px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;' + (p === adCurrentPage ? 'color:#D24C19;' : 'color:#9ca3af;');
            d.onmouseenter = function () { if (p !== adCurrentPage) this.style.color = '#374151'; };
            d.onmouseleave = function () { if (p !== adCurrentPage) this.style.color = '#9ca3af'; };
            d.onclick = function () { adCurrentPage = p; adRenderPage(); };
            dots.appendChild(d);
        }
        document.getElementById('prev-page').style.opacity = adCurrentPage === 1 ? '0.4' : '1';
        document.getElementById('prev-page').style.pointerEvents = adCurrentPage === 1 ? 'none' : 'auto';
        document.getElementById('next-page').style.opacity = adCurrentPage === totalPages ? '0.4' : '1';
        document.getElementById('next-page').style.pointerEvents = adCurrentPage === totalPages ? 'none' : 'auto';
    }

    function adPage(dir) {
        const perPage = adGetPerPage();
        const totalPages = Math.ceil(adCards.length / perPage);
        if (dir === 1 && adCurrentPage > 1) { adCurrentPage--; adRenderPage(); }
        if (dir === 2 && adCurrentPage < totalPages) { adCurrentPage++; adRenderPage(); }
    }

    var adFlipData = {!! json_encode($providers->map(fn($p) => [
        'cat' => strtoupper($p['category']),
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
