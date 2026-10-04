<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>500 - Server Error | PideAca</title>
        <style>
            * { box-sizing: border-box; }
            html, body { margin: 0; padding: 0; }
            body {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 22px;
                padding: 28px 16px;
                background: radial-gradient(1200px 600px at 50% -10%, #123a66 0%, #0c2a4d 45%, #071a30 100%);
                font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                color: #e5edf7;
            }
            .err-topbar {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 15px;
                font-weight: 700;
                letter-spacing: 0.4px;
                color: #ff7a52;
                text-transform: none;
            }
            .err-topbar .err-x {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 26px;
                height: 26px;
                border: 2px solid #ff7a52;
                border-radius: 50%;
                font-size: 13px;
                line-height: 1;
            }
            .err-card {
                width: 100%;
                max-width: 720px;
                background: #ffffff;
                color: #0c2a4d;
                border-radius: 14px;
                box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
                padding: 30px 34px;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 26px;
            }
            .err-art { flex: 0 0 auto; width: 210px; max-width: 100%; }
            .err-art svg { width: 100%; height: auto; display: block; }
            .err-body { flex: 1 1 300px; min-width: 260px; }
            .err-code {
                margin: 0;
                font-size: 64px;
                font-weight: 800;
                line-height: 1;
                color: #0c2a4d;
                letter-spacing: 2px;
            }
            .err-title {
                margin: 8px 0 10px;
                font-size: 20px;
                font-weight: 700;
                color: #0c2a4d;
            }
            .err-text {
                margin: 0 0 20px;
                font-size: 14px;
                line-height: 1.6;
                color: #4b5a70;
            }
            .err-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            .err-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 10px 18px;
                border-radius: 4px;
                font-size: 13px;
                font-weight: 700;
                text-decoration: none;
                cursor: pointer;
                border: 1px solid transparent;
                transition: filter 0.15s ease, background 0.15s ease;
            }
            .err-btn:hover { filter: brightness(1.08); }
            .err-btn-primary { background: #D24C19; border-color: #D24C19; color: #ffffff; }
            .err-btn-secondary { background: #ffffff; border-color: #cdd6e2; color: #0c2a4d; }
            .err-btn svg { flex: 0 0 auto; }
            .err-footer {
                font-size: 11px;
                color: #9fb1c7;
                text-align: center;
            }
            @media (max-width: 560px) {
                .err-card { padding: 24px 20px; gap: 18px; }
                .err-art { width: 160px; margin: 0 auto; }
                .err-code { font-size: 52px; }
            }
        </style>
    </head>
    <body>
        <div class="err-topbar"><span class="err-x">&#10005;</span> Server Error</div>

        <main class="err-card" role="main">
            <div class="err-art" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 200" fill="none">
                    <rect x="26" y="44" width="132" height="46" rx="8" fill="#eef2f7" stroke="#0c2a4d" stroke-width="4"/>
                    <rect x="26" y="98" width="132" height="46" rx="8" fill="#eef2f7" stroke="#0c2a4d" stroke-width="4"/>
                    <circle cx="46" cy="67" r="7" fill="#D24C19"/>
                    <circle cx="46" cy="121" r="7" fill="#9fb1c7"/>
                    <rect x="64" y="60" width="76" height="14" rx="7" fill="#c7d2de"/>
                    <rect x="64" y="114" width="76" height="14" rx="7" fill="#c7d2de"/>
                    <path d="M186 46 L226 116 H146 Z" fill="#fff6ef" stroke="#D24C19" stroke-width="5" stroke-linejoin="round"/>
                    <path d="M186 72 V96" stroke="#D24C19" stroke-width="6" stroke-linecap="round"/>
                    <circle cx="186" cy="106" r="4.5" fill="#D24C19"/>
                    <path d="M52 160 H174" stroke="#0c2a4d" stroke-width="4" stroke-linecap="round" stroke-dasharray="10 12"/>
                </svg>
            </div>

            <div class="err-body">
                <p class="err-code">500</p>
                <h1 class="err-title">Ups, algo sali&oacute; mal de nuestro lado.</h1>
                <p class="err-text">
                    Estamos teniendo un problema temporal para procesar tu solicitud. Nuestro equipo ya fue notificado
                    y est&aacute; trabajando para solucionarlo. Por favor, intenta de nuevo en unos minutos: tu sesi&oacute;n
                    y tus datos siguen a salvo.
                </p>
                <div class="err-actions">
                    <a class="err-btn err-btn-primary" href="{{ url('/') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                        Regresar a la P&aacute;gina Principal
                    </a>
                    <a class="err-btn err-btn-secondary" href="{{ url('/up') }}" title="Verificar Estado del Sistema">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                        Verificar Estado del Sistema
                    </a>
                </div>
            </div>
        </main>

        <footer class="err-footer">PideAca &copy; Copyright {{ date('Y') }} - All rights reserved.</footer>
    </body>
</html>
