/**
 * Ayuda en linea del panel (Fase 1): boton "?" por ventana + Centro de Ayuda.
 *
 * Sin base de datos: todo el contenido vive en este archivo. Sin tocar
 * dashboard-app.js: la seccion visible se detecta por DOM y el overlay es
 * propio (no reutiliza funciones del panel).
 */
(function () {
  'use strict';

  var SECTION_HELP = {
    'perfil': { title: 'Mi Perfil', steps: ['Completá tu dirección (país, provincia, calle y número) para presupuestos y envíos.', 'Subí tu foto y verificá que tu correo y teléfono estén bien.', 'Si gestionás una cuenta (Admin), elegila arriba para cambiar de contexto.'], tip: 'Sin dirección cargada, varias funciones piden completarla primero.' },
    'usuarios': { title: 'Gestión de Usuarios', steps: ['La tabla muestra todos los usuarios: filtrá por nombre, correo o rol.', 'Crear usuario: botón Nuevo, completá datos y asignale tipo y páginas.', 'El botón de páginas define a qué ventanas entra cada usuario.'], tip: 'Un usuario sin páginas asignadas ve el panel vacío.' },
    'tipo-usuarios': { title: 'Tipos de Usuario', steps: ['Cada tipo (Admin, Cliente, Comercio…) define un rol dentro del sistema.', 'Creá o editá tipos con su descripción.', 'Asigná el tipo al crear o editar cada usuario.'], tip: 'No borres un tipo que tenga usuarios asignados.' },
    'estados-usuarios': { title: 'Estados de Usuario', steps: ['Los estados (Activo, Inactivo…) controlan quién puede entrar.', 'Un usuario Inactivo no puede iniciar sesión.', 'Cambiá el estado desde la fila del usuario.'], tip: 'Para suspender a alguien, pasalo a Inactivo en vez de borrarlo.' },
    'modulos': { title: 'Módulos', steps: ['Los módulos agrupan las ventanas del menú (Hogar, Mercado, Administrar…).', 'Podés crear, renombrar y ordenar módulos.', 'Cada módulo muestra solo sus páginas con contenido habilitado.'], tip: 'El módulo Administrar siempre va al final del menú.' },
    'paginas': { title: 'Páginas', steps: ['Cada página es una ventana del panel con su URL interna.', 'Asigná páginas a módulos y a usuarios (permisos).', 'Una página sin URL muestra "pronto" en el menú.'], tip: 'Los permisos se asignan por usuario en Gestión de Usuarios.' },
    'grupos': { title: 'Grupos', steps: ['Los grupos organizan a los usuarios (familias, equipos, sucursales).', 'Creá grupos con nombre y estado.', 'Asigná usuarios al grupo desde sus fichas.'], tip: 'Los reportes Hogar se filtran por grupo.' },
    'estados-grupos': { title: 'Estados de Grupos', steps: ['Definen si un grupo está activo o no.', 'Los grupos inactivos no aparecen en selectores.', 'Cambiá el estado desde la fila del grupo.'], tip: 'Revisá esto si un grupo "desapareció" de un selector.' },
    'sub-grupos': { title: 'Subgrupos', steps: ['Subdividen un grupo en partes más chicas.', 'Cada subgrupo cuelga de un grupo padre.', 'Se usan para clasificar servicios y reportes.'], tip: 'Primero tiene que existir el grupo padre.' },
    'consultas-sugerencias': { title: 'Consultas y Sugerencias', steps: ['Acá llegan los mensajes del formulario de contacto del sitio.', 'Filtrá por estado: Nuevas, En proceso, Resueltas.', 'Abrí cada mensaje para responderlo por correo y cambiarle el estado.', 'El botón "Ver Visitas por País" muestra de dónde viene el tráfico.'], tip: 'Respondé desde el botón "Responder por correo": ya trae el asunto armado.' },
    'comercios': { title: 'Comercios', steps: ['Lista de comercios con envío a domicilio.', 'Cada comercio tiene tienda, catálogo y zona de cobertura.', 'Desde acá armás tu pedido como cliente.'], tip: 'Usá el buscador y los favoritos para encontrar rápido tus tiendas.' },
    'mi-catalogo': { title: 'Mi Catálogo', steps: ['Cargá tus productos con foto, precio y stock.', 'Organizalos por rubro para que se vean bien en la tienda.', 'Lo que publiques acá aparece en tu vidriera.'], tip: 'Sin foto ni precio, el producto no se publica.' },
    'inventario': { title: 'Inventario', steps: ['Controlá stock por producto e insumo.', 'Registrá entradas y salidas para no vender sin stock.', 'Activá el control de inventario si tu comercio lo usa.'], tip: 'El stock en cero pausa la venta de ese producto.' },
    'unidades-medida': { title: 'Unidades de Medida', steps: ['Catálogo de unidades (kg, litro, unidad…).', 'Se usan al cargar productos e insumos.', 'No borres una unidad que ya esté en uso.'], tip: 'Pedí al Admin que agregue la que te falte.' },
    'tipo-monedas': { title: 'Tipos de Moneda', steps: ['Monedas habilitadas del sistema ($, USD…).', 'Cada precio y billetera usa una de estas monedas.', 'El Admin define cuáles están activas.'], tip: 'Si tu moneda no aparece, pedila al Admin.' },
    'pedidos': { title: 'Pedidos (Despacho)', steps: ['Tablero de pedidos del mercado para preparar y despachar.', 'Cada pedido muestra cliente, items y estado.', 'Avanzá el estado a medida que lo preparás y entregás.'], tip: 'Los pedidos nuevos suenan/llegan primero: atendelos en orden.' },
    'mis-pedidos': { title: 'Mis Pedidos', steps: ['Acá ves todo lo que compraste y su estado.', 'Tocá un pedido para ver el detalle y el repartidor.', 'Calificá cuando lo recibas.'], tip: 'Si un pedido tarda, revisá su estado acá antes de reclamar.' },
    'repartidor': { title: 'Repartidor', steps: ['Ponete EN LÍNEA para recibir ofertas de reparto.', 'Configurá vehículo y zona de cobertura primero.', 'Aceptá, retirá, entregá y cobrá tu ganancia.', 'Pedí el retiro de tu saldo disponible cuando quieras.'], tip: 'Sin vehículo configurado no te llegan pedidos.' },
    'reportes-repartidor': { title: 'Reportes Repartidor', steps: ['Rendimiento de repartos: viajes, ganancias y aceptación.', 'Filtrá por período para ver tu evolución.', 'La calificación sale de los clientes.'], tip: 'Baja aceptación = menos ofertas. Mantenete en línea.' },
    'ordenes-servicio': { title: 'Órdenes de Servicio', steps: ['Trabajos de prestadores (plomero, electricista…).', 'Cada orden tiene estado: pendiente, en curso, terminada.', 'El cliente la sigue desde Mis Servicios.'], tip: 'Actualizá el estado para que el cliente vea el avance.' },
    'ordenes-gestion': { title: 'Órdenes y Gestión', steps: ['Vista de gestión de todas las órdenes de servicio.', 'Asigná, priorizá y cerrá órdenes.', 'Filtrá por estado para ver qué está pendiente.'], tip: 'Las pendientes hace días conviene reasignarlas.' },
    'gestionar-solicitudes': { title: 'Gestionar Solicitudes', steps: ['Solicitudes que te llegan como prestador.', 'Aceptá o rechazá cada una con su motivo.', 'Al aceptar se genera la orden de servicio.'], tip: 'Respondé rápido: las solicitudes vencen.' },
    'solicitar-servicio': { title: 'Solicitar Servicio', steps: ['Buscá prestadores por rubro o cercanía.', 'Pedí presupuesto describiendo lo que necesitás.', 'Coordiná día y hora desde la solicitud.'], tip: 'Cuantos más detalles pongas, mejor el presupuesto.' },
    'mis-servicios': { title: 'Mis Servicios', steps: ['Tus contrataciones y su estado.', 'Seguí cada trabajo y chateá con el prestador.', 'Calificá al terminar.'], tip: 'Todo tu historial queda guardado acá.' },
    'reportes': { title: 'Reportes', steps: ['Tablero general de reportes del sistema.', 'Elegí el reporte y el período.', 'Exportá o imprimí lo que necesites.'], tip: 'Los reportes salen con los datos al día.' },
    'reportes-hogar': { title: 'Reportes Hogar', steps: ['Consumos y gastos del hogar por grupo.', 'Compará períodos para detectar subas.', 'Filtrá por grupo o subgrupo.'], tip: 'Útil para dividir gastos del grupo.' },
    'dinero': { title: 'Dinero (Admin)', steps: ['Backoffice de pagos: comisiones, liquidaciones y cobros.', 'Verificá los cobros pendientes de acreditación.', 'Emití liquidaciones a comercios y repartidores.', 'La matriz PideAca concentra las cuentas del sistema.'], tip: 'Solo Admin: cada movimiento queda auditado.' },
    'billeteras-pais': { title: 'Billeteras por País', steps: ['Cuentas donde cobrás, una por moneda.', 'La primera queda como predeterminada.', 'Las liquidaciones llegan a tu billetera.'], tip: 'Verificá que tu CBU/alias esté bien antes de pedir un retiro.' },
    'finanzas': { title: 'Finanzas', steps: ['Salud financiera de tu negocio: costos y márgenes.', 'Revisá qué productos dejan más ganancia.', 'Ajustá precios según el costo real.'], tip: 'Cargá bien los costos para que el margen sea real.' },
    'retencion': { title: 'Retención', steps: ['Clientes en riesgo y acciones de recupero.', 'Enviá el mensaje de recuperación sugerido.', 'Medí cuántos vuelven.'], tip: 'Reaccioná a tiempo: un cliente perdido cuesta más.' },
    'suscripciones': { title: 'Suscripciones', steps: ['Planes Hogar con facturación periódica.', 'Emití facturas y controlá mora y suspensiones.', 'Los pedidos se generan solos al vencer.'], tip: 'A los 15 días de mora el plan se suspende solo.' },
    'mis-suscripciones': { title: 'Mis Suscripciones', steps: ['Tus planes activos y próximos vencimientos.', 'Pagá desde acá para evitar la suspensión.', 'Darte de baja es desde esta misma pantalla.'], tip: 'Pagá antes del vencimiento y no se corta el servicio.' }
  };

  var FIRST_STEPS = [
    { role: 'Cliente', steps: ['Completá tu dirección en Mi Perfil.', 'Buscá comercios y armá tu primer pedido.', 'Seguilo en Mis Pedidos y calificá al recibir.'] },
    { role: 'Comercio', steps: ['Cargá tu catálogo con fotos y precios.', 'Verificá tu zona de cobertura.', 'Atendé los pedidos del tablero en orden.'] },
    { role: 'Prestador', steps: ['Completá Mis Servicios con tus rubros.', 'Respondé las solicitudes que te lleguen.', 'Actualizá cada orden hasta cerrarla.'] },
    { role: 'Repartidor', steps: ['Configurá vehículo y zona de cobertura.', 'Ponete EN LÍNEA en la Terminal.', 'Aceptá, entregá y retirá tu saldo.'] },
    { role: 'Admin', steps: ['Creá usuarios y asignales páginas.', 'Cargá catálogos base (monedas, unidades, tipos).', 'Controlá Dinero y las visitas del sitio.'] }
  ];

  var FAQ = [
    { q: 'No veo una ventana en mi menú', a: 'Pedile al Admin que te asigne esa página en Gestión de Usuarios. Sin página asignada, la ventana no aparece.' },
    { q: 'Olvidé mi contraseña', a: 'En la pantalla de ingreso tocá "¿Olvidaste tu contraseña?" y seguí el correo de recuperación.' },
    { q: 'Un pedido quedó trabado', a: 'Revisá su estado en Mis Pedidos (cliente) o en Pedidos (comercio). Si no avanza, escribí por Consultas.' },
    { q: '¿Cómo cobro lo que gané?', a: 'En Billetera o Mi Dinero pedí el retiro a tu cuenta cargada. Verificá CBU/alias antes.' },
    { q: 'No me llegan pedidos (repartidor)', a: 'Verificá: vehículo configurado, zona con comercios, estado EN LÍNEA y buena aceptación.' },
    { q: '¿Cómo publico un producto?', a: 'En Mi Catálogo: foto, precio, stock y rubro. Sin esos datos no se publica.' },
    { q: '¿Qué significan los estados del pedido?', a: 'Pendiente → en preparación → en camino → entregado. Se avanzan solos o desde el tablero.' },
    { q: '¿A quién pregunto si no está acá?', a: 'Usá la ventana Consultas y Sugerencias: llega directo al equipo Admin.' }
  ];

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  function currentKey() {
    var sections = document.querySelectorAll('.dash-section');
    for (var i = 0; i < sections.length; i++) {
      if (sections[i].style.display !== 'none' && sections[i].id.indexOf('dash-') === 0) {
        var key = sections[i].id.slice(5);
        if (key === '/repartidor/reportes' || key === 'repartidor/reportes') key = 'reportes-repartidor';
        return key;
      }
    }
    return 'perfil';
  }

  function sectionGuide(key) {
    return SECTION_HELP[key] || {
      title: 'Esta ventana',
      steps: ['Explorá las opciones y filtros de la parte superior.', 'Tocá cada fila o tarjeta para ver su detalle.', 'Usá Actualizar si los datos parecen viejos.'],
      tip: 'Si algo no aparece, puede ser un permiso: consultá al Admin.'
    };
  }

  function guideHtml(key) {
    var g = sectionGuide(key);
    var html = '<p style="font-size:13px;color:#6b7280;margin:0 0 10px;">Guía rápida: <strong style="color:#0c2a4d;">' + esc(g.title) + '</strong></p>';
    html += '<ol style="margin:0 0 10px;padding-left:20px;list-style-type:decimal;font-size:13px;color:#374151;line-height:1.7;">';
    for (var i = 0; i < g.steps.length; i++) {
      html += '<li>' + esc(g.steps[i]) + '</li>';
    }
    html += '</ol>';
    html += '<p style="font-size:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:8px 10px;color:#92400e;margin:0;">💡 ' + esc(g.tip) + '</p>';
    return html;
  }

  function firstStepsHtml() {
    var html = '';
    for (var i = 0; i < FIRST_STEPS.length; i++) {
      var r = FIRST_STEPS[i];
      html += '<p style="font-size:13px;font-weight:800;color:#0c2a4d;margin:12px 0 4px;">' + esc(r.role) + '</p>';
      html += '<ol style="margin:0;padding-left:20px;list-style-type:decimal;font-size:13px;color:#374151;line-height:1.7;">';
      for (var j = 0; j < r.steps.length; j++) {
        html += '<li>' + esc(r.steps[j]) + '</li>';
      }
      html += '</ol>';
    }
    return html;
  }

  function faqHtml() {
    var html = '';
    for (var i = 0; i < FAQ.length; i++) {
      html += '<p style="font-size:13px;font-weight:700;color:#0c2a4d;margin:12px 0 2px;">' + esc(FAQ[i].q) + '</p>';
      html += '<p style="font-size:13px;color:#374151;margin:0;line-height:1.6;">' + esc(FAQ[i].a) + '</p>';
    }
    return html;
  }

  function setTab(tab) {
    var tabs = ['win', 'start', 'faq'];
    for (var i = 0; i < tabs.length; i++) {
      var btn = document.getElementById('dh-tab-' + tabs[i]);
      var pane = document.getElementById('dh-pane-' + tabs[i]);
      if (btn) btn.style.cssText += ';' + (tabs[i] === tab
        ? 'background:#0c2a4d;border-color:#0c2a4d;color:#fff;'
        : 'background:#fff;border-color:#d1d5db;color:#374151;');
      if (pane) pane.style.display = tabs[i] === tab ? 'block' : 'none';
    }
  }

  function openDashHelp(key) {
    var overlay = document.getElementById('dh-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'dh-overlay';
      overlay.style.cssText = 'display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.55);z-index:300;align-items:center;justify-content:center;padding:16px;';
      overlay.onclick = function (e) { if (e.target === overlay) closeDashHelp(); };
      overlay.innerHTML =
        '<div style="background:#fff;width:100%;max-width:640px;border-radius:8px;box-shadow:0 12px 40px rgba(0,0,0,.25);max-height:92vh;overflow-y:auto;">' +
        '<div style="padding:14px 18px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;">' +
        '<h3 style="font-size:16px;font-weight:700;color:#0c2a4d;margin:0;">Ayuda en línea</h3>' +
        '<button type="button" onclick="closeDashHelp()" aria-label="Cerrar ayuda" style="background:none;border:none;cursor:pointer;padding:4px;color:#6b7280;font-size:20px;line-height:1;">&times;</button>' +
        '</div>' +
        '<div style="padding:16px 18px;">' +
        '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">' +
        '<button type="button" id="dh-tab-win" onclick="dhSetTab(\'win\')" style="padding:6px 12px;border:1px solid #d1d5db;border-radius:9999px;font-size:12px;font-weight:600;cursor:pointer;">Esta ventana</button>' +
        '<button type="button" id="dh-tab-start" onclick="dhSetTab(\'start\')" style="padding:6px 12px;border:1px solid #d1d5db;border-radius:9999px;font-size:12px;font-weight:600;cursor:pointer;">Primeros pasos</button>' +
        '<button type="button" id="dh-tab-faq" onclick="dhSetTab(\'faq\')" style="padding:6px 12px;border:1px solid #d1d5db;border-radius:9999px;font-size:12px;font-weight:600;cursor:pointer;">Preguntas</button>' +
        '</div>' +
        '<div id="dh-pane-win"></div>' +
        '<div id="dh-pane-start" style="display:none;">' + firstStepsHtml() + '</div>' +
        '<div id="dh-pane-faq" style="display:none;">' + faqHtml() + '</div>' +
        '</div>' +
        '<div style="padding:12px 18px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;">' +
        '<button type="button" onclick="closeDashHelp()" style="padding:8px 18px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer;color:#374151;">Cerrar</button>' +
        '</div></div>';
      document.body.appendChild(overlay);
    }
    var resolved = key || currentKey();
    var pane = document.getElementById('dh-pane-win');
    if (pane) pane.innerHTML = guideHtml(resolved);
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTab('win');
  }

  function closeDashHelp() {
    var overlay = document.getElementById('dh-overlay');
    if (overlay) overlay.style.display = 'none';
    document.body.style.overflow = '';
  }

  window.openDashHelp = openDashHelp;
  window.closeDashHelp = closeDashHelp;
  window.dhSetTab = setTab;

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeDashHelp();
  });

  // Botón flotante "?" abre la guía de la ventana visible.
  document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('dh-fab')) return;
    var fab = document.createElement('button');
    fab.id = 'dh-fab';
    fab.type = 'button';
    fab.title = 'Ayuda de esta ventana';
    fab.setAttribute('aria-label', 'Ayuda de esta ventana');
    fab.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:250;width:44px;height:44px;border-radius:50%;background:#D24C19;color:#fff;font-size:20px;font-weight:800;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.3);';
    fab.textContent = '?';
    fab.onclick = function () { openDashHelp(); };
    document.body.appendChild(fab);
  });
})();
