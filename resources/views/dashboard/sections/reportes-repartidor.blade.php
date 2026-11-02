            {{-- Reporte del repartidor: la vista ya existe como pagina completa
                 (/repartidor/reportes), con metricas, historial y boton PDF.
                 Se embebe con carga diferida para no duplicar consultas mientras
                 la seccion esta oculta; el enlace directo del menu va a esa ruta. --}}
            <section id="dash-reportes-repartidor" class="dash-section" style="display:none;width:96%;max-width:96%;margin:24px auto;padding:0 1rem;box-sizing:border-box;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                    <h2 style="font-size:18px;font-weight:800;color:#0c2a4d;margin:0;">{{ $page->description }}</h2>
                    <a href="{{ route('repartidor.reportes') }}" target="_blank" rel="noopener" style="font-size:12.5px;font-weight:800;color:#D24C19;text-decoration:none;background:#FFF7ED;border:1px solid #FED7AA;border-radius:9999px;padding:9px 16px;">
                        Abrir reporte completo
                    </a>
                </div>
                <iframe
                    src="{{ route('repartidor.reportes') }}"
                    title="Reporte del repartidor"
                    loading="lazy"
                    style="display:block;width:100%;height:85vh;border:1px solid #e2e8f0;border-radius:14px;background:#fff;box-shadow:0 10px 30px rgba(15,23,42,.06);"
                ></iframe>
            </section>
