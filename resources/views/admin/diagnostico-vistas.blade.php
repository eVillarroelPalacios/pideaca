<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Centro de Diagnóstico y Salud de Vistas · PideAca</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #f3f5f9;
            color: #0f172a;
        }
        .barra { background: #D24C19; height: 3px; }
        header {
            background: linear-gradient(180deg, #0c2a4d 0%, #071a30 100%);
            color: #fff;
            padding: 22px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        header h1 { margin: 0; font-size: 21px; letter-spacing: .2px; }
        header p { margin: 6px 0 0; font-size: 13px; color: #b9c7db; }
        header a {
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,.35);
            padding: 8px 14px;
            border-radius: 8px;
            transition: background .15s;
        }
        header a:hover { background: rgba(255,255,255,.12); }
        .contenedor { max-width: 1280px; margin: 24px auto 60px; padding: 0 20px; }
        .panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
            padding: 20px;
        }
        .controles {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .campo { display: flex; flex-direction: column; gap: 6px; }
        .campo label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
        select {
            appearance: none;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 10px 38px 10px 12px;
            font-size: 14px;
            color: #0f172a;
            min-width: 210px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%2364748b' stroke-width='1.8' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 13px center;
        }
        button {
            font-family: inherit;
            cursor: pointer;
            border: none;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 700;
            transition: transform .12s, box-shadow .12s, background .12s;
        }
        button:active { transform: translateY(1px); }
        .btn-auditar {
            background: #D24C19;
            color: #fff;
            padding: 11px 20px;
            box-shadow: 0 6px 16px rgba(210, 76, 25, .28);
        }
        .btn-auditar:hover { background: #bc4315; }
        .btn-auditar[disabled] { opacity: .55; cursor: progress; box-shadow: none; }
        .btn-preview {
            background: #eef2f7;
            color: #0c2a4d;
            padding: 7px 12px;
            font-size: 12.5px;
            border: 1px solid #d7e0ea;
        }
        .btn-preview:hover { background: #e0e8f1; }
        .leyenda { display: flex; gap: 16px; flex-wrap: wrap; font-size: 12.5px; color: #475569; margin-left: auto; }
        .leyenda span { display: inline-flex; align-items: center; gap: 6px; }
        .punto { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .punto.ok { background: #16a34a; }
        .punto.warn { background: #d97706; }
        .punto.error { background: #dc2626; }
        .tabla-scroll { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 860px; }
        thead th {
            background: #f8fafc;
            text-align: left;
            padding: 12px 14px;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        tbody td { padding: 12px 14px; border-bottom: 1px solid #eef2f7; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr.fila { transition: background .2s; }
        tbody tr.fila:hover { background: #f8fafc; }
        tbody tr.ok { background: #f0fdf4; }
        tbody tr.ok:hover { background: #dcfce7; }
        tbody tr.warn { background: #fffbeb; }
        tbody tr.warn:hover { background: #fef3c7; }
        tbody tr.error { background: #fef2f2; }
        tbody tr.error:hover { background: #fee2e2; }
        tbody tr.auditando { background: #eff6ff; }
        .pagina-nombre { font-weight: 700; color: #0f172a; display: block; }
        .modulo-nombre { font-size: 11.5px; color: #64748b; display: block; margin-top: 2px; }
        code {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 3px 7px;
            font-size: 12px;
            color: #0c2a4d;
            word-break: break-all;
            display: inline-block;
        }
        .estado { font-weight: 800; font-variant-numeric: tabular-nums; }
        .estado.s-ok { color: #15803d; }
        .estado.s-warn { color: #b45309; }
        .estado.s-error { color: #b91c1c; }
        .diagnostico { color: #475569; max-width: 430px; }
        .diagnostico.d-ok { color: #15803d; font-weight: 600; }
        .diagnostico.d-warn { color: #b45309; font-weight: 600; }
        .diagnostico.d-error { color: #b91c1c; font-weight: 600; }
        .resumen {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-top: 18px;
        }
        .tarjeta {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .tarjeta .num { font-size: 22px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .tarjeta .etq { font-size: 11.5px; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin-top: 2px; }
        .tarjeta.total .num { color: #0c2a4d; }
        .tarjeta.ok .num { color: #15803d; }
        .tarjeta.warn .num { color: #b45309; }
        .tarjeta.error .num { color: #b91c1c; }
        .nota { margin-top: 14px; font-size: 12.5px; color: #64748b; line-height: 1.5; }

        /* Modal de previsualización */
        #modal {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: none;
            background: rgba(7, 26, 48, .55);
            backdrop-filter: blur(2px);
        }
        #modal.abierto { display: flex; }
        .modal-caja {
            background: #fff;
            width: min(1200px, 94vw);
            height: 90vh;
            margin: auto;
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 24px 60px rgba(7, 26, 48, .35);
        }
        .modal-head {
            background: #0c2a4d;
            color: #fff;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .modal-head strong { font-size: 14.5px; }
        .modal-head small { display: block; font-size: 11.5px; color: #b9c7db; margin-top: 3px; word-break: break-all; }
        .modal-head button {
            background: rgba(255,255,255,.12);
            color: #fff;
            width: 34px;
            height: 34px;
            font-size: 18px;
            line-height: 1;
            flex: 0 0 auto;
        }
        .modal-head button:hover { background: rgba(255,255,255,.24); }
        .modal-caja iframe { flex: 1; width: 100%; border: 0; background: #fff; }
        .vacio { text-align: center; color: #64748b; padding: 40px 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="barra"></div>
    <header>
        <div>
            <h1>Centro de Diagnóstico y Salud de Vistas</h1>
            <p>Auditoría en vivo de páginas y módulos de PideAca · solo peticiones GET</p>
        </div>
        <a href="{{ url('/') }}">← Volver al panel</a>
    </header>

    <main class="contenedor">
        <div class="panel">
            <div class="controles">
                <div class="campo">
                    <label for="rol">Rol para la simulación</label>
                    <select id="rol">
                        <option value="todos">Todos los roles</option>
                        <option value="cliente">Cliente</option>
                        <option value="comercio">Comercio</option>
                        <option value="repartidor">Repartidor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <button type="button" id="btn-auditar" class="btn-auditar">Auditar Todas las Vistas</button>

                <div class="leyenda">
                    <span><i class="punto ok"></i> OK</span>
                    <span><i class="punto warn"></i> Advertencia</span>
                    <span><i class="punto error"></i> Error</span>
                </div>
            </div>

            @if (count($paginas) === 0)
                <div class="vacio">No hay páginas registradas para auditar.</div>
            @else
                <div class="tabla-scroll">
                    <table id="tabla">
                        <thead>
                            <tr>
                                <th>Módulo / Página</th>
                                <th>URL</th>
                                <th>Estado HTTP</th>
                                <th>Diagnóstico</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paginas as $fila)
                                <tr class="fila"
                                    data-url="{{ $fila['url'] }}"
                                    data-rol="{{ $fila['rol'] }}"
                                    data-nombre="{{ $fila['modulo'] }} · {{ $fila['pagina'] }}">
                                    <td>
                                        <span class="pagina-nombre">{{ $fila['pagina'] }}</span>
                                        <span class="modulo-nombre">{{ $fila['modulo'] }}</span>
                                    </td>
                                    <td><code>{{ $fila['url'] }}</code></td>
                                    <td class="estado">—</td>
                                    <td class="diagnostico">Sin auditar</td>
                                    <td>
                                        <button type="button" class="btn-preview"
                                            data-url="{{ $fila['url'] }}"
                                            data-rol="{{ $fila['rol'] }}"
                                            data-nombre="{{ $fila['modulo'] }} · {{ $fila['pagina'] }}">
                                            Previsualizar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="resumen">
                    <div class="tarjeta total"><div class="num" id="res-total">0</div><div class="etq">Total evaluadas</div></div>
                    <div class="tarjeta ok"><div class="num" id="res-ok">0</div><div class="etq">Exitosas [OK]</div></div>
                    <div class="tarjeta warn"><div class="num" id="res-warn">0</div><div class="etq">Con advertencias</div></div>
                    <div class="tarjeta error"><div class="num" id="res-error">0</div><div class="etq">Fallidas</div></div>
                </div>

                <p class="nota">
                    Cada fila se consulta por GET con el rol seleccionado simulado en memoria: no se modifican
                    contraseñas, sesiones ni datos. Una fila verde respondió 200 con contenido completo; amarilla
                    respondió 200 pero contiene textos como “Próximamente” o “PRONTO”; roja devolvió 403, 404, 500
                    o una excepción.
                </p>
            @endif
            </div>

            <div class="panel" style="margin-top: 18px;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
                    <div>
                        <h2 style="margin:0;font-size:17px;">Simular Ciclo Completo de Pedido (POST/PUT)</h2>
                        <p style="margin:6px 0 0;font-size:13px;color:#475569;">
                            Crea un pedido, asigna un repartidor, confirma la entrega y acredita la tarifa.
                            Todo se ejecuta con <strong>DB::beginTransaction()</strong> y <strong>rollBack()</strong> en un bloque
                            <code style="border:0;padding:0;background:transparent;">finally</code>; ninguna operación persiste.
                        </p>
                    </div>
                    <button type="button" id="btn-test-flujo" class="btn-auditar">Simular Ciclo Completo de Pedido (POST/PUT)</button>
                </div>

                <div id="flujo-estado" style="display:none;border-radius:10px;padding:12px 16px;font-size:13.5px;font-weight:600;"></div>
                <div id="flujo-pasos" style="display:none;flex-direction:column;gap:10px;margin-top:14px;"></div>
                <div id="flujo-error" style="display:none;margin-top:14px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:10px;padding:14px 18px;"></div>
                <p class="nota">Las pruebas son estrictamente de lectura/escritura simulado: se usan modelos existentes y solo datos en memoria dentro de la transacción revertida.</p>
            </div>
    </main>

    <div id="modal" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
        <div class="modal-caja">
            <div class="modal-head">
                <div>
                    <strong id="modal-titulo">Previsualización</strong>
                    <small id="modal-url"></small>
                </div>
                <button type="button" id="modal-cerrar" aria-label="Cerrar">×</button>
            </div>
            <iframe id="frame" title="Previsualización de la vista" sandbox="allow-same-origin allow-scripts allow-forms allow-popups"></iframe>
        </div>
    </div>

    <script>
        (function () {
            const AUDITOR = @json(route('admin.diagnostico.auditar'));
            const tabla = document.getElementById('tabla');
            const selectorRol = document.getElementById('rol');
            const btnAuditar = document.getElementById('btn-auditar');
            const modal = document.getElementById('modal');
            const frame = document.getElementById('frame');

            const FRASES = [
                'Próximamente',
                'Proximamente',
                'Todavia no tiene seccion',
                'Todavía no tiene sección',
                'PRONTO'
            ];

            function filas() {
                return tabla ? Array.from(tabla.querySelectorAll('tr.fila')) : [];
            }

            function visibles() {
                return filas().filter(function (tr) { return tr.style.display !== 'none'; });
            }

            function aplicarFiltro() {
                const rol = selectorRol.value;

                filas().forEach(function (tr) {
                    tr.style.display = (rol === 'todos' || tr.dataset.rol === rol) ? '' : 'none';
                });
            }

            function limpiarFila(tr) {
                tr.className = 'fila';
                tr.querySelector('.estado').textContent = '—';
                tr.querySelector('.estado').className = 'estado';
                tr.querySelector('.diagnostico').textContent = 'Sin auditar';
                tr.querySelector('.diagnostico').className = 'diagnostico';
            }

            function marcar(tr, nivel, estado, texto) {
                const celdaEstado = tr.querySelector('.estado');
                const celdaDiag = tr.querySelector('.diagnostico');

                tr.className = 'fila ' + nivel;
                celdaEstado.textContent = estado;
                celdaEstado.className = 'estado s-' + nivel;
                celdaDiag.textContent = texto;
                celdaDiag.className = 'diagnostico d-' + nivel;
            }

            function detectarMarcador(texto) {
                const minusculas = texto.toLowerCase();

                for (let i = 0; i < FRASES.length; i++) {
                    if (minusculas.indexOf(FRASES[i].toLowerCase()) !== -1) {
                        return FRASES[i];
                    }
                }

                return null;
            }

            function recortar(texto, max) {
                const limpio = texto.replace(/\s+/g, ' ').trim();

                return limpio.length > max ? limpio.slice(0, max) + '…' : limpio;
            }

            function urlAuditoria(url, rol) {
                return AUDITOR + '?url=' + encodeURIComponent(url) + '&rol=' + encodeURIComponent(rol);
            }

            async function auditarFila(tr) {
                const url = tr.dataset.url;
                const rol = tr.dataset.rol;

                tr.className = 'fila auditando';
                tr.querySelector('.estado').textContent = '…';
                tr.querySelector('.estado').className = 'estado';
                tr.querySelector('.diagnostico').textContent = 'Consultando…';
                tr.querySelector('.diagnostico').className = 'diagnostico';

                try {
                    const respuesta = await fetch(urlAuditoria(url, rol), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    const html = await respuesta.text();
                    const sinRuido = html
                        .replace(/<script[\s\S]*?<\/script>/gi, '')
                        .replace(/<style[\s\S]*?<\/style>/gi, '');
                    const estado = respuesta.status;

                    if (estado >= 400) {
                        const detalle = recortar(sinRuido.replace(/<[^>]+>/g, ' '), 170);
                        marcar(tr, 'error', estado, 'Error HTTP ' + estado + (detalle ? ': ' + detalle : ''));
                        return 'error';
                    }

                    if (estado === 200) {
                        if (sinRuido.trim().length < 300) {
                            marcar(tr, 'warn', estado, 'Vista vacía o incompleta (' + sinRuido.trim().length + ' caracteres).');
                            return 'warn';
                        }

                        const marcador = detectarMarcador(sinRuido);

                        if (marcador) {
                            marcar(tr, 'warn', estado, 'Contiene texto de marcador de posición: “' + marcador + '”.');
                            return 'warn';
                        }

                        marcar(tr, 'ok', estado, 'Vista cargada correctamente.');
                        return 'ok';
                    }

                    marcar(tr, 'warn', estado, 'HTTP ' + estado + ' (redirección o respuesta no esperada).');
                    return 'warn';
                } catch (error) {
                    marcar(tr, 'error', '—', 'Error de red: ' + error.message);
                    return 'error';
                }
            }

            async function auditarTodas() {
                const pendientes = visibles().slice();
                const contadores = { ok: 0, warn: 0, error: 0 };

                if (pendientes.length === 0) {
                    return;
                }

                btnAuditar.disabled = true;
                btnAuditar.textContent = 'Auditando…';
                pendientes.forEach(limpiarFila);

                async function trabajador() {
                    while (pendientes.length > 0) {
                        const tr = pendientes.shift();
                        const resultado = await auditarFila(tr);

                        if (resultado) {
                            contadores[resultado]++;
                        }
                    }
                }

                // 4 peticiones en paralelo: rápido sin saturar el servidor.
                const trabajadores = [];

                for (let i = 0; i < 4; i++) {
                    trabajadores.push(trabajador());
                }

                await Promise.all(trabajadores);

                document.getElementById('res-total').textContent = Object.values(contadores).reduce(function (a, b) { return a + b; }, 0);
                document.getElementById('res-ok').textContent = contadores.ok;
                document.getElementById('res-warn').textContent = contadores.warn;
                document.getElementById('res-error').textContent = contadores.error;

                btnAuditar.disabled = false;
                btnAuditar.textContent = 'Auditar Todas las Vistas';
            }

            function abrirPreview(boton) {
                document.getElementById('modal-titulo').textContent = boton.dataset.nombre || 'Previsualización';
                document.getElementById('modal-url').textContent = boton.dataset.url;
                frame.src = urlAuditoria(boton.dataset.url, boton.dataset.rol);
                modal.classList.add('abierto');
            }

            function cerrarPreview() {
                modal.classList.remove('abierto');
                frame.src = 'about:blank';
            }

            if (tabla) {
                selectorRol.addEventListener('change', aplicarFiltro);
                btnAuditar.addEventListener('click', auditarTodas);

                tabla.addEventListener('click', function (evento) {
                    const boton = evento.target.closest('.btn-preview');

                    if (boton) {
                        abrirPreview(boton);
                    }
                });

                document.getElementById('modal-cerrar').addEventListener('click', cerrarPreview);

                modal.addEventListener('click', function (evento) {
                    if (evento.target === modal) {
                        cerrarPreview();
                    }
                });

                document.addEventListener('keydown', function (evento) {
                    if (evento.key === 'Escape' && modal.classList.contains('abierto')) {
                        cerrarPreview();
                    }
                });
            }

            // ---- Simulación del ciclo completo de pedido (POST con rollback) ----
            const btnFlujo = document.getElementById('btn-test-flujo');
            const estadoFlujo = document.getElementById('flujo-estado');
            const pasosFlujo = document.getElementById('flujo-pasos');
            const errorFlujo = document.getElementById('flujo-error');
            const tokenCsrf = document.querySelector('meta[name="csrf-token"]');

            function mostrarEstadoFlujo(tipo, texto) {
                if (!estadoFlujo) {
                    return;
                }

                const estilos = {
                    info: ['#eff6ff', '#1d4ed8', '#bfdbfe'],
                    ok: ['#f0fdf4', '#15803d', '#bbf7d0'],
                    error: ['#fef2f2', '#b91c1c', '#fecaca']
                };
                const color = estilos[tipo] || estilos.info;

                estadoFlujo.style.display = 'block';
                estadoFlujo.style.background = color[0];
                estadoFlujo.style.color = color[1];
                estadoFlujo.style.border = '1px solid ' + color[2];
                estadoFlujo.textContent = texto;
            }

            function agregarPaso(paso) {
                if (!pasosFlujo) {
                    return;
                }

                const esOk = paso.status === 'success';
                const fila = document.createElement('div');
                fila.style.cssText = 'display:flex;gap:10px;align-items:flex-start;padding:10px 12px;'
                    + 'border-radius:9px;border:1px solid ' + (esOk ? '#bbf7d0' : '#fecaca') + ';'
                    + 'background:' + (esOk ? '#f0fdf4' : '#fef2f2') + ';';

                const check = document.createElement('span');
                check.textContent = esOk ? '✓' : '✕';
                check.style.cssText = 'flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;'
                    + 'width:24px;height:24px;border-radius:50%;color:#fff;font-size:13px;font-weight:800;'
                    + 'background:' + (esOk ? '#16a34a' : '#dc2626') + ';';

                const cuerpo = document.createElement('div');
                const titulo = document.createElement('strong');
                titulo.style.cssText = 'display:block;font-size:13.5px;color:#0f172a;';
                titulo.textContent = (paso.paso ? paso.paso + '. ' : '') + (paso.accion || 'Paso');

                const detalle = document.createElement('small');
                detalle.style.cssText = 'display:block;font-size:12.5px;color:#475569;margin-top:2px;';
                detalle.textContent = paso.detalle || '';

                cuerpo.appendChild(titulo);
                cuerpo.appendChild(detalle);
                fila.appendChild(check);
                fila.appendChild(cuerpo);
                pasosFlujo.appendChild(fila);
            }

            async function ejecutarTestFlujo() {
                if (!btnFlujo) {
                    return;
                }

                const textoOriginal = btnFlujo.textContent;
                btnFlujo.disabled = true;
                btnFlujo.textContent = 'Ejecutando transacción…';

                if (pasosFlujo) {
                    pasosFlujo.innerHTML = '';
                    pasosFlujo.style.display = 'flex';
                }

                if (errorFlujo) {
                    errorFlujo.style.display = 'none';
                    errorFlujo.innerHTML = '';
                }

                mostrarEstadoFlujo('info', 'Ejecutando el ciclo completo dentro de una transacción…');

                try {
                    const respuesta = await fetch(@json(route('admin.diagnostico.test-flujo')), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': tokenCsrf ? tokenCsrf.content : '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: '{}'
                    });

                    const datos = await respuesta.json().catch(function () { return null; });

                    if (!datos) {
                        mostrarEstadoFlujo('error', 'La respuesta del servidor no es JSON válido (HTTP ' + respuesta.status + ').');
                        return;
                    }

                    (datos.pasos || []).forEach(agregarPaso);

                    if (datos.status === 'success') {
                        mostrarEstadoFlujo('ok', '✓ ' + (datos.mensaje || 'Ciclo completo verificado.'));
                        return;
                    }

                    mostrarEstadoFlujo('error', '✕ ' + (datos.mensaje || 'Falló la simulación del flujo.'));

                    const error = datos.error || {};

                    if (errorFlujo && (error.mensaje || error.archivo)) {
                        errorFlujo.style.display = 'block';

                        const titulo = document.createElement('strong');
                        titulo.textContent = 'Detalle exacto del error';

                        const pre = document.createElement('pre');
                        pre.style.cssText = 'margin:8px 0 0;white-space:pre-wrap;font-size:12.5px;';
                        pre.textContent = (error.mensaje || '')
                            + (error.archivo ? '\n' + error.archivo + ':' + error.linea : '');

                        errorFlujo.appendChild(titulo);
                        errorFlujo.appendChild(pre);
                    }
                } catch (error) {
                    mostrarEstadoFlujo('error', 'Error de red: ' + error.message);
                } finally {
                    btnFlujo.disabled = false;
                    btnFlujo.textContent = textoOriginal;
                }
            }

            if (btnFlujo) {
                btnFlujo.addEventListener('click', ejecutarTestFlujo);
            }
        })();
    </script>
</body>
</html>
