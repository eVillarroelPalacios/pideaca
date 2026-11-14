            {{-- Reporte del repartidor: la vista ya existe como pagina completa
                 (/repartidor/reportes), con metricas, historial y boton PDF.
                 Se embebe con carga diferida para no duplicar consultas mientras
                 la seccion esta oculta; el enlace directo del menu va a esa ruta. --}}
            <section id="dash-reportes-repartidor" class="dash-section" style="display:none;width:100%;margin:0;padding:0;">
                <iframe
                    id="repartidor-report-frame"
                    src="{{ route('repartidor.reportes') }}"
                    title="Reporte del repartidor"
                    loading="lazy"
                    scrolling="no"
                    onload="fdFitRepartidorFrame(this)"
                    style="display:block;width:100%;height:70vh;min-height:480px;border:0;border-radius:0;background:#f1f5f9;box-shadow:none;overflow:hidden;"
                ></iframe>
            </section>
            <script>
            (function () {
                // El reporte vive en la misma app (mismo origen), asi que el
                // padre puede medir el documento interno y darle al iframe su
                // altura real: no hay barra de scroll interna, el contenido
                // fluye con el scroll general de la pagina.
                function innerHeight(frame) {
                    try {
                        var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
                        if (!doc) return 0;
                        var de = doc.documentElement;
                        var body = doc.body;
                        return Math.max(de ? de.scrollHeight : 0, body ? body.scrollHeight : 0);
                    } catch (e) {
                        return 0;
                    }
                }
                function fit(frame) {
                    var h = innerHeight(frame);
                    if (h > 0) frame.style.height = (h + 4) + 'px';
                }
                window.fdFitRepartidorFrame = function (frame) {
                    fit(frame);
                    try {
                        var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
                        if (!doc || typeof ResizeObserver === 'undefined') return;
                        var target = doc.documentElement || doc.body;
                        if (!target) return;
                        if (frame.__fdResizeObserver) frame.__fdResizeObserver.disconnect();
                        var raf = 0;
                        var observer = new ResizeObserver(function () {
                            cancelAnimationFrame(raf);
                            raf = requestAnimationFrame(function () { fit(frame); });
                        });
                        observer.observe(target);
                        frame.__fdResizeObserver = observer;
                    } catch (e) { /* sin acceso al documento interno */ }
                };
                // Un cambio de ancho reacomoda el contenido y altera su altura.
                var resizeTimer = 0;
                window.addEventListener('resize', function () {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(function () {
                        var frame = document.getElementById('repartidor-report-frame');
                        if (frame && frame.offsetParent !== null) fit(frame);
                    }, 150);
                });
            })();
            </script>
