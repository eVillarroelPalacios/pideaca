<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
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
                <a href="#" class="header-btn" style="padding:2px 10px;background:#D24C19;color:white;border:1px solid #D24C19;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;cursor:pointer;">Entrar</a>
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
            <div style="background:#D24C19;height:2px;"></div>
            {{-- CAROUSEL --}}
            <section style="padding:0;">
                <div style="width:100%;">
                    <div style="position:relative;overflow:hidden;border-radius:0;">
                        <div id="carousel-track" style="display:flex;transition:transform 0.4s ease;">
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:260px;background:linear-gradient(135deg,#0c2a4d 0%,#123b73 55%,#0c2a4d 100%);">
                                <div class="carousel-text" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:20px;">
                                    <div style="text-align:center;max-width:680px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;">
                                        <div id="ex-icon" style="font-size:42px;line-height:1;">&#128269;</div>
                                        <h2 id="ex-title" style="font-size:26px;font-weight:bold;margin:0;">Buscá tu prestador</h2>
                                        <p id="ex-desc" style="font-size:14px;margin:0;max-width:540px;">Elegí qué necesitás: comercios, hogar, auxilio vial o abastos.</p>
                                    </div>
                                    <div id="ex-dots" style="display:flex;gap:6px;margin-top:14px;"></div>
                                    <span style="margin-top:10px;font-size:9px;letter-spacing:1px;color:rgba(255,255,255,0.6);">&#9654; ASÍ FUNCIONA PIDEACA</span>
                                </div>
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
                            <span style="padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;background:#f3f4f6;color:#374151;cursor:pointer;">Comercio</span>
                            <span style="padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;background:#f3f4f6;color:#374151;cursor:pointer;">Hogar</span>
                            <span style="padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;background:#f3f4f6;color:#374151;cursor:pointer;">Auxilio Vial</span>
                            <span style="padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;background:#f3f4f6;color:#374151;cursor:pointer;">Abastos</span>
                        </div>

                        <div class="provider-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;padding:4px 28px;box-sizing:border-box;">

                            {{-- COMERCIO 1 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/pizzeria.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">COMERCIO</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Pizzeria Los Hermanos</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <span style="font-size:11px;color:#6b7280;">4.8</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Pizza a la piedra, empanadas y delivery por zona. Promo 2x1 los miercoles.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- COMERCIO 2 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/ferreteria.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">COMERCIO</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Ferreteria El Tornillo</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9734;</span> <span style="font-size:11px;color:#6b7280;">4.5</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Pinturas, herramientas y articulos de bazar. Entrega a domicilio.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- HOGAR 1 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/plomeria.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">HOGAR</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Plomeria Rapida Perez</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <span style="font-size:11px;color:#6b7280;">4.7</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Destapaciones, cambios de canillas y termotanques. Certificado, 24 hs.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- HOGAR 2 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/electricidad.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">HOGAR</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Electricidad Total</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <span style="font-size:11px;color:#6b7280;">4.6</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Instalaciones, reparaciones y certificacion de artefactos electricos.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- AUXILIO VIAL 1 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/grua.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">AUXILIO VIAL</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Gruas Aurora 24h</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <span style="font-size:11px;color:#6b7280;">4.9</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Remolque, arranque con pinas y auxilio en ruta con GPS en tiempo real.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- AUXILIO VIAL 2 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/gomeria.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">AUXILIO VIAL</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Gomeria RuedasBien</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9734;</span> <span style="font-size:11px;color:#6b7280;">4.4</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Reparacion de pinchaduras y recambios a domicilio, con traslado.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- ABASTOS 1 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/agua.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">ABASTOS</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Agua Pura Distribuidora</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <span style="font-size:11px;color:#6b7280;">4.8</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Bidones de agua y hielo en granel. Pedidos programados semanales.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- ABASTOS 2 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/gas.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">ABASTOS</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Gas Paz Gas</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9734;</span> <span style="font-size:11px;color:#6b7280;">4.5</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Garrafas de 10 y 45 kg, soda y entrega en el dia solicitada por la app.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- COMERCIO 3 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/rotiseria.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">COMERCIO</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Rotiseria Don Carlos</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <span style="font-size:11px;color:#6b7280;">4.9</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Comida casera, guisos y porciones. Envio gratis en pedidos mayores a $5000.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- COMERCIO 4 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/kiosco.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">COMERCIO</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Kiosco Express 24hs</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9734;</span> <span style="font-size:11px;color:#6b7280;">4.3</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Snacks, bebidas, cigarrillos y articulos de higiene. Abierto las 24 horas.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- HOGAR 3 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/pintor.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">HOGAR</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Pinturas La Brocha</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <span style="font-size:11px;color:#6b7280;">4.8</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Pintura interior y exterior, presupuesto sin cargo y colorimetria digital.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- HOGAR 4 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/tecnico-ac.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">HOGAR</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Clima Total SAC</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9734;</span> <span style="font-size:11px;color:#6b7280;">4.5</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Instalacion, limpieza y reparacion de aires acondicionados. Certificado.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- AUXILIO VIAL 3 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/mecanica.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">AUXILIO VIAL</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Mecanica Rapida SRL</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <span style="font-size:11px;color:#6b7280;">4.7</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Service mecanico express, cambios de aceite y frenos. Taller movil.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- AUXILIO VIAL 4 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/service.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">AUXILIO VIAL</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Auxilio 24 Horas</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9734;</span> <span style="font-size:11px;color:#6b7280;">4.6</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Apertura de vehiculos, cambio de bateria y asistencia en ruta.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- ABASTOS 3 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/verduleria.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">ABASTOS</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Verduleria Don Pepe</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <span style="font-size:11px;color:#6b7280;">4.9</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Frutas y verduras frescas del dia. Pedidos programados y combo semanal.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                            </a>{{-- ABASTOS 4 --}}
                            <a href="#" class="ad-card-link"><div class="ad-card" style="position:relative;border:1px solid #e5e7eb;border-radius:0;overflow:hidden;background:white;box-shadow:0 4px 14px rgba(0,0,0,0.05);">
                                <div class="ad-banner" style="position:relative;height:180px;overflow:hidden;background:url('{{ asset('images/publicidad/panaderia.jpg') }}') center/cover no-repeat;">
                                    
                                </div>
                                <div class="ad-body" style="padding:14px;position:relative;z-index:1;background:white;">
                                    <span class="ad-badge" style="display:inline-block;font-size:10px;font-weight:700;padding:3px 10px;border-radius:10px;margin-bottom:10px;">ABASTOS</span>
                                    <div class="ad-card-info" style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                                        
                                        <div>
                                            <h4 class="ad-title" style="font-size:14px;font-weight:bold;color:#1f2937;margin:0;">Panaderia La Especial</h4>
                                            <span class="ad-rating" style="font-size:11px;color:#f59e0b;">&#9733;&#9733;&#9733;&#9733;&#9734;</span> <span style="font-size:11px;color:#6b7280;">4.6</span>
                                        </div>
                                    </div>
                                    <p class="ad-desc" style="font-size:12px;color:#6b7280;line-height:1.5;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">Pan fresco, tortas y facturas. Reservas con 24hs de anticipacion.</p>
                                    <div class="card-action-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                    </div>
                                    
                                </div>
                            </div>

                        </a></div>

                        <div id="ad-pagination" style="display:flex;justify-content:center;align-items:center;gap:8px;padding:8px 28px;box-sizing:border-box;">
                            <button id="prev-page" class="pagination-nav-btn" onclick="adPage(1)" style="background:white;border:1px solid #d1d5db;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;color:#374151;">&#10094;</button>
                            <div id="page-dots" style="display:flex;gap:6px;"></div>
                            <button id="next-page" class="pagination-nav-btn" onclick="adPage(2)" style="background:white;border:1px solid #d1d5db;border-radius:50%;width:32px;height:32px;font-size:14px;cursor:pointer;color:#374151;">&#10095;</button>
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

    </body>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        .header-btn:hover, .cta-btn:hover {
            background: #ffffff !important;
            color: #D24C19 !important;
            border: 1px solid #D24C19 !important;
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
        .ad-banner { height: 85px; }
        .ad-body { padding: 6px; }
        .ad-avatar { width: 22px; height: 22px; min-width: 22px; font-size: 10px; }
        .ad-title { font-size: 10px; }
        .ad-desc { font-size: 9px; line-height: 1.2; margin: 0 0 4px; padding-right: 28px; }
        .ad-btn { padding: 4px; font-size: 9px; }
        .ad-badge { font-size: 8px !important; padding: 2px 6px !important; margin-bottom: 4px !important; }
        .ad-rating { font-size: 9px !important; }
        .ad-card-info { gap: 6px !important; margin-bottom: 4px !important; }
        .ad-card-link { text-decoration: none; color: inherit; display: block; }
        .ad-card { position: relative; background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; cursor: pointer; }
        .ad-card:hover { transform: translateY(-4px); box-shadow: 0 12px 20px -5px rgba(0,0,0,0.1); border-color: #f97316; }
        .ad-body { padding-bottom: 36px; }
        .card-action-icon { position: absolute; bottom: 10px; right: 10px; width: 28px; height: 28px; border-radius: 50%; background-color: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center; transition: background-color 0.2s ease, transform 0.2s ease, color 0.2s ease; }
        .ad-card:hover .card-action-icon { background-color: #ea580c; color: #ffffff; transform: translate(2px, -2px); }
        @media (max-width: 768px) {
            .nav-link { display: none !important; }
            .nav-separator { display: none !important; }
            #menu-toggle { display: block !important; }
            .logo-img { width: 140px !important; height: 52px !important; }
            .nav-container { padding: 1px 8px !important; justify-content: space-between !important; }
            .nav-container > div:first-child { display: none !important; }
            .nav-container > div:last-child { justify-content: flex-end !important; }
            .carousel-slide { height: 200px !important; }
            .carousel-text { padding: 20px 16px !important; }
            .carousel-text h2 { font-size: 20px !important; line-height: 1.2 !important; }
            .carousel-text p { font-size: 13px !important; max-width: 90% !important; margin-bottom: 12px !important; }
            .carousel-text button { padding: 10px 24px !important; font-size: 13px !important; }
            .provider-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 8px !important; padding: 4px 12px !important; }
            .ad-banner { height: 80px !important; }
            .ad-body { padding: 6px !important; }
            .ad-avatar { width: 22px !important; height: 22px !important; min-width: 22px !important; font-size: 10px !important; }
            .ad-title { font-size: 10px !important; }
            .ad-desc { font-size: 9px !important; line-height: 1.2 !important; margin: 0 0 4px !important; padding-right: 24px !important; }
            .ad-btn { padding: 4px !important; font-size: 9px !important; }
            .card-action-icon { width: 24px !important; height: 24px !important; bottom: 8px !important; right: 8px !important; }
            .card-action-icon svg { width: 14px !important; height: 14px !important; }
            .ad-body { padding-bottom: 28px !important; }
            #ad-pagination { padding: 2px 12px !important; gap: 4px !important; margin-top: -2px !important; }
            #ad-pagination button { width: 28px !important; height: 28px !important; font-size: 14px !important; }
        }
        @media (max-width: 480px) {
            .logo-img { width: 120px !important; height: 48px !important; }
            .header-btn { padding: 6px 12px !important; font-size: 11px !important; }
            .carousel-slide { height: 160px !important; }
            .carousel-text { padding: 16px 12px !important; }
            .carousel-text h2 { font-size: 17px !important; line-height: 1.2 !important; }
            .carousel-text p { font-size: 11px !important; max-width: 95% !important; margin-bottom: 10px !important; }
            .carousel-text button { padding: 8px 20px !important; font-size: 12px !important; }
            .provider-grid { grid-template-columns: 1fr !important; gap: 6px !important; padding: 4px 10px !important; }
            .ad-banner { height: 70px !important; }
            .ad-body { padding: 5px !important; }
            .ad-avatar { width: 20px !important; height: 20px !important; min-width: 20px !important; font-size: 9px !important; }
            .ad-title { font-size: 9px !important; }
            .ad-desc { font-size: 8px !important; line-height: 1.2 !important; margin: 0 0 3px !important; padding-right: 20px !important; }
            .ad-btn { padding: 3px !important; font-size: 8px !important; }
            .card-action-icon { width: 22px !important; height: 22px !important; bottom: 6px !important; right: 6px !important; }
            .card-action-icon svg { width: 12px !important; height: 12px !important; }
            .ad-body { padding-bottom: 24px !important; }
            #ad-pagination { padding: 2px 8px !important; gap: 3px !important; margin-top: -2px !important; }
            #ad-pagination button { width: 26px !important; height: 26px !important; font-size: 13px !important; }
        }
        @media (min-width: 769px) {
            #mobile-menu { display: none !important; }
        }
    </style>
    <script>
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

        const exSteps = [
            { icon: '🔍', title: 'Buscá tu prestador', desc: 'Elegí qué necesitás: comercios, hogar, auxilio vial o abastos.' },
            { icon: '🤝', title: 'Elegí el mejor', desc: 'Compará prestadores, precios y valoraciones de tu comunidad.' },
            { icon: '📲', title: 'Confirmá el servicio', desc: 'Programá o solicitá con un toque y pago seguro desde la app.' },
            { icon: '🚚', title: 'Recibilo en tu casa', desc: 'Seguimiento en tiempo real hasta tu puerta, sin perder tiempo.' }
        ];
        let exIndex = 0;
        function exUpdate() {
            const s = exSteps[exIndex];
            document.getElementById('ex-icon').textContent = s.icon;
            document.getElementById('ex-title').textContent = s.title;
            document.getElementById('ex-desc').textContent = s.desc;
            const dots = document.getElementById('ex-dots');
            dots.innerHTML = '';
            exSteps.forEach(function (step, i) {
                const d = document.createElement('span');
                d.style.cssText = 'width:8px;height:8px;border-radius:50%;' + (i === exIndex ? 'background:#D24C19;transform:scale(1.2);' : 'background:rgba(255,255,255,0.4);') + 'transition:background 0.3s ease,transform 0.3s ease;';
                dots.appendChild(d);
            });
        }
        setInterval(function () {
            exIndex = (exIndex + 1) % exSteps.length;
            exUpdate();
        }, 3000);
        exUpdate();

        let adCurrentPage = 1;
        const adCards = document.querySelectorAll('.ad-card');
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

        (function fixGridHeight() {
            const grid = document.querySelector('.provider-grid');
            if (!grid) return;
            grid.style.overflow = 'hidden';
            function setHeight() {
                const cards = grid.querySelectorAll('.ad-card');
                if (!cards.length) return;
                const cols = window.getComputedStyle(grid).gridTemplateColumns.split(' ').length;
                const rows = 2;
                const perPage = cols * rows;
                let maxH = 0;
                for (let i = 0; i < perPage && i < cards.length; i++) {
                    cards[i].style.display = '';
                    const h = cards[i].offsetHeight;
                    if (h > maxH) maxH = h;
                }
                const gap = parseInt(window.getComputedStyle(grid).gap) || 0;
                grid.style.height = (maxH * rows + gap * (rows - 1)) + 'px';
            }
            setHeight();
            window.addEventListener('resize', setHeight);
        })();

        function adPage(dir) {
            const perPage = adGetPerPage();
            const totalPages = Math.ceil(adCards.length / perPage);
            if (dir === 1 && adCurrentPage > 1) { adCurrentPage--; adRenderPage(); }
            if (dir === 2 && adCurrentPage < totalPages) { adCurrentPage++; adRenderPage(); }
        }

        adRenderPage();
        window.addEventListener('resize', adRenderPage);

    </script>
</html>
