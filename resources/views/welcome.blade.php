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

        <div style="background:#b0b0b0;height:2px;"></div>
        {{-- NAV --}}
        <nav style="background:linear-gradient(180deg,#123b73 0%,#0a2440 100%);position:sticky;top:0;z-index:50;overflow:visible;">
            <div class="nav-container" style="max-width:1200px;margin:0 auto;padding:2px 2px;display:flex;align-items:center;justify-content:flex-end;gap:8px;position:relative;">
                <a href="/" onclick="window.location.reload(true);return false;" style="margin-right:auto;z-index:60;">
                    <img class="logo-img" src="{{ asset('images/logo.png') }}" alt="PideAca" style="width:190px;height:75px;border-radius:0;object-fit:contain;" />
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="#" class="nav-link" style="padding:8px 16px;color:white;font-size:14px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    Inicio
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="#" class="nav-link" style="padding:8px 16px;color:white;font-size:14px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    Quienes Somos
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="#" class="nav-link" style="padding:8px 16px;color:white;font-size:14px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
                    Servicios
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="#" class="nav-link" style="padding:8px 16px;color:white;font-size:14px;font-weight:600;border-radius:4px;text-decoration:none;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                    Contactos
                </a>
                <div class="nav-separator" style="width:1px;height:20px;background:rgba(255,255,255,0.4);"></div>
                <a href="#" class="header-btn" style="padding:2px 10px;background:#D24C19;color:white;border:1px solid #D24C19;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;cursor:pointer;">Registrate</a>
                <a href="#" class="header-btn" style="padding:2px 10px;background:#D24C19;color:white;border:1px solid #D24C19;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;cursor:pointer;">Entrar</a>
                <button id="menu-toggle" onclick="toggleMenu()" style="display:none;background:none;border:none;color:white;font-size:24px;cursor:pointer;padding:4px 8px;">&#9776;</button>
            </div>
            <div id="mobile-menu" style="display:none;background:#ffffff;padding:8px 16px;position:absolute;top:100%;left:0;right:0;z-index:100;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <a href="#" style="display:flex;align-items:center;gap:8px;padding:10px 16px;color:#1f2937;font-size:14px;font-weight:600;text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    Inicio
                </a>
                <a href="#" style="display:flex;align-items:center;gap:8px;padding:10px 16px;color:#1f2937;font-size:14px;font-weight:600;text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    Quienes Somos
                </a>
                <a href="#" style="display:flex;align-items:center;gap:8px;padding:10px 16px;color:#1f2937;font-size:14px;font-weight:600;text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 0 1-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 1 1-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 0 1 6.336-4.486l-3.276 3.276a3.004 3.004 0 0 0 2.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
                    Servicios
                </a>
                <a href="#" style="display:flex;align-items:center;gap:8px;padding:10px 16px;color:#1f2937;font-size:14px;font-weight:600;text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
                    Contactos
                </a>
            </div>
        </nav>

        {{-- SECTION --}}
        <main style="flex:1;">
            <div style="background:#b0b0b0;height:2px;"></div>
            {{-- CAROUSEL + SERVICIOS SOBREMONTADOS --}}
            <section style="padding:0;">
                <div style="width:100%;">
                    <div style="position:relative;overflow:hidden;border-radius:0;">
                        <div id="carousel-track" style="display:flex;transition:transform 0.4s ease;">
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:380px;background:url('{{ asset('images/Imacarousel/slide1.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Gastronomía y Comercio a tu alcance</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Pizzerías, rotiserías, kioscos y tiendas de conveniencia. Todo en un solo lugar.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Explorar Comercios</button>
                                </div>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:380px;background:url('{{ asset('images/Imacarousel/slide2.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Servicios del Hogar 24/7</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Plomeros, electricistas, técnicos y estética a domicilio. Profesionales certificados.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Solicitar Servicio</button>
                                </div>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:380px;background:url('{{ asset('images/Imacarousel/slide3.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Auxilio Vial en Tiempo Real</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Grúas, mecánica ligera y gomería móvil con ubicación GPS.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Solicitar Auxilio</button>
                                </div>
                            </div>
                            <div class="carousel-slide" style="min-width:100%;box-sizing:border-box;position:relative;height:380px;background:url('{{ asset('images/Imacarousel/slide4.jpg') }}') center/cover no-repeat;">
                                <div class="carousel-text" style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;padding:40px;">
                                    <h2 style="font-size:36px;font-weight:bold;margin-bottom:8px;">Abastos Recurrentes</h2>
                                    <p style="font-size:18px;max-width:500px;text-align:center;margin-bottom:20px;">Agua, gas, hielo y soda. Pedidos programados semanales o quincenales.</p>
                                    <button class="cta-btn" style="padding:5px 16px;background:#D24C19;color:white;border:1px solid rgba(255,255,255,0.6);border-radius:4px;font-size:12px;font-weight:bold;cursor:pointer;">Programar Pedido</button>
                                </div>
                            </div>
                        </div>
                        <button onclick="moveCarousel(-1)" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.9);border:none;border-radius:50%;width:40px;height:40px;font-size:18px;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,0.3);">&#10094;</button>
                        <button onclick="moveCarousel(1)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.9);border:none;border-radius:50%;width:40px;height:40px;font-size:18px;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,0.3);">&#10095;</button>
                    </div>
                </div>

                <div class="service-cards" style="max-width:900px;margin:-30px auto 0;position:relative;z-index:10;display:grid;grid-template-columns:repeat(4, 1fr);gap:32px;padding:0 16px;">
                    <div class="service-card" style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.9);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.6);border-radius:16px;padding:16px;box-shadow:0 10px 30px rgba(0,0,0,0.12);transition:transform 0.3s ease,box-shadow 0.3s ease;cursor:pointer;">
                        <div class="card-icon" style="width:44px;height:44px;min-width:44px;border-radius:12px;background:rgba(234,88,12,0.12);display:flex;align-items:center;justify-content:center;font-size:20px;">&#127829;</div>
                        <div>
                            <h4 style="font-size:12px;font-weight:bold;color:#1f2937;margin:0 0 2px;">COMERCIO</h4>
                            <p style="font-size:10px;color:#6b7280;margin:0;">Gastronomía y conveniencia</p>
                        </div>
                    </div>
                    <div class="service-card" style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.9);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.6);border-radius:16px;padding:16px;box-shadow:0 10px 30px rgba(0,0,0,0.12);transition:transform 0.3s ease,box-shadow 0.3s ease;cursor:pointer;">
                        <div class="card-icon" style="width:44px;height:44px;min-width:44px;border-radius:12px;background:rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:center;font-size:20px;">&#128295;</div>
                        <div>
                            <h4 style="font-size:12px;font-weight:bold;color:#1f2937;margin:0 0 2px;">HOGAR</h4>
                            <p style="font-size:10px;color:#6b7280;margin:0;">Mantenimiento y cuidado</p>
                        </div>
                    </div>
                    <div class="service-card" style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.9);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.6);border-radius:16px;padding:16px;box-shadow:0 10px 30px rgba(0,0,0,0.12);transition:transform 0.3s ease,box-shadow 0.3s ease;cursor:pointer;">
                        <div class="card-icon" style="width:44px;height:44px;min-width:44px;border-radius:12px;background:rgba(20,184,166,0.12);display:flex;align-items:center;justify-content:center;font-size:20px;">&#128663;</div>
                        <div>
                            <h4 style="font-size:12px;font-weight:bold;color:#1f2937;margin:0 0 2px;">AUXILIO VIAL</h4>
                            <p style="font-size:10px;color:#6b7280;margin:0;">Grúas y mecánica móvil</p>
                        </div>
                    </div>
                    <div class="service-card" style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.9);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.6);border-radius:16px;padding:16px;box-shadow:0 10px 30px rgba(0,0,0,0.12);transition:transform 0.3s ease,box-shadow 0.3s ease;cursor:pointer;">
                        <div class="card-icon" style="width:44px;height:44px;min-width:44px;border-radius:12px;background:rgba(234,179,8,0.12);display:flex;align-items:center;justify-content:center;font-size:20px;">&#128230;</div>
                        <div>
                            <h4 style="font-size:12px;font-weight:bold;color:#1f2937;margin:0 0 2px;">ABASTOS</h4>
                            <p style="font-size:10px;color:#6b7280;margin:0;">Pedidos recurrentes</p>
                        </div>
                    </div>
                </div>
            </section>

        </main>
        <div style="background:white;height:15px;"></div>
        <footer style="background:#0a2440;color:white;padding:10px 0;">
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
        .nav-link {
            position: relative;
            text-decoration: none;
            transition: transform 0.2s ease, text-shadow 0.2s ease;
        }
        .nav-link:hover {
            transform: scale(1.1) translateZ(10px);
            text-shadow: 0 2px 8px rgba(255,255,255,0.4);
        }
        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.18) !important;
            background: rgba(255,255,255,0.95) !important;
        }
        @media (max-width: 768px) {
            .nav-link { display: none !important; }
            .nav-separator { display: none !important; }
            #menu-toggle { display: block !important; }
            .logo-img { width: 160px !important; height: 70px !important; }
            .nav-container { padding: 6px 8px !important; justify-content: space-between !important; }
            .nav-container > div:first-child { display: none !important; }
            .nav-container > div:last-child { justify-content: flex-end !important; }
            .service-cards { max-width: 100% !important; gap: 12px !important; margin-top: -20px !important; padding: 0 8px !important; grid-template-columns: repeat(4, 1fr) !important; }
            .service-card { flex-direction: column !important; text-align: center !important; padding: 10px 4px !important; border-radius: 12px !important; transition: border-color 0.3s ease !important; }
            .service-card:hover { border-color: #D24C19 !important; }
            .card-icon { width: 32px !important; height: 32px !important; min-width: 32px !important; font-size: 16px !important; margin: 0 !important; }
            .service-card h4 { font-size: 9px !important; }
            .service-card p { font-size: 7px !important; display: none !important; }
            .carousel-slide { height: 280px !important; }
            .carousel-text { padding: 20px 16px !important; }
            .carousel-text h2 { font-size: 20px !important; line-height: 1.2 !important; }
            .carousel-text p { font-size: 13px !important; max-width: 90% !important; margin-bottom: 12px !important; }
            .carousel-text button { padding: 10px 24px !important; font-size: 13px !important; }
        }
        @media (max-width: 480px) {
            .logo-img { width: 140px !important; height: 62px !important; }
            .header-btn { padding: 6px 12px !important; font-size: 11px !important; }
            .service-cards { gap: 10px !important; margin-top: -18px !important; padding: 0 6px !important; grid-template-columns: repeat(4, 1fr) !important; }
            .service-card { padding: 8px 2px !important; transition: border-color 0.3s ease !important; }
            .service-card:hover { border-color: #D24C19 !important; }
            .card-icon { width: 28px !important; height: 28px !important; min-width: 28px !important; font-size: 14px !important; }
            .service-card h4 { font-size: 8px !important; margin-bottom: 0 !important; }
            .service-card p { font-size: 7px !important; }
            .carousel-slide { height: 220px !important; }
            .carousel-text { padding: 16px 12px !important; }
            .carousel-text h2 { font-size: 17px !important; line-height: 1.2 !important; }
            .carousel-text p { font-size: 11px !important; max-width: 95% !important; margin-bottom: 10px !important; }
            .carousel-text button { padding: 8px 20px !important; font-size: 12px !important; }
        }
        @media (min-width: 769px) {
            #mobile-menu { display: none !important; }
        }
        .service-card {
            border: 1px solid rgba(255,255,255,0.6);
        }
        .service-card:hover {
            border-color: #D24C19 !important;
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

    </script>
</html>