/**
 * Ayuda en línea de la app (Fase 1): guía por pantalla + primeros pasos + FAQ.
 *
 * Sin base de datos: todo el contenido vive en este archivo. Espeja las
 * guías del panel web con textos adaptados a la app.
 */

export type HelpGuide = {
  title: string;
  steps: string[];
  tip: string;
};

export const HELP_BY_SCREEN: Record<string, HelpGuide> = {
  home: {
    title: 'Mi Perfil',
    steps: [
      'Completá tu dirección (país, provincia, calle y número).',
      'Verificá que tu correo y teléfono estén bien.',
      'Desde AcountSidebar cambiás de sección o de cuenta.',
    ],
    tip: 'Sin dirección cargada, varias funciones la piden primero.',
  },
  menu: {
    title: 'Mi cuenta (menú)',
    steps: [
      'Acá están tus módulos: Hogar, Mercado, Reparto y Administrar.',
      'Tocá una fila para ir a esa pantalla.',
      'El botón Cerrar vuelve a donde estabas.',
    ],
    tip: 'Si te falta una sección, pedile al Admin que te asigne la página.',
  },
  comercios: {
    title: 'Comercios',
    steps: [
      'Vidriera de comercios con envío a domicilio.',
      'Buscá por nombre o mirá tus favoritos.',
      'Tocá una tarjeta para entrar a la tienda y pedir.',
    ],
    tip: 'La estrella guarda tus comercios favoritos.',
  },
  catalogo: {
    title: 'Catálogo',
    steps: [
      'Productos del comercio con foto y precio.',
      'Agregá unidades al carrito desde cada tarjeta.',
      'Confirmá el pedido para enviarlo a cocina.',
    ],
    tip: 'Revisá el carrito antes de confirmar.',
  },
  providers: {
    title: 'Prestadores (Solicitudes)',
    steps: [
      'Buscá prestadores por nombre o rubro.',
      'Filtrá por cercanía (usa el GPS del teléfono).',
      'Tocá una tarjeta para ver el perfil y pedir presupuesto.',
    ],
    tip: 'La estrella marca tus prestadores favoritos.',
  },
  repartidor: {
    title: 'Terminal del repartidor',
    steps: [
      'Configurá vehículo y zona antes de ponerte en línea.',
      'Aceptá las ofertas del radar sonoro.',
      'Completá la entrega y pedí el retiro de tu saldo.',
    ],
    tip: 'Sin vehículo configurado no te llegan pedidos.',
  },
  dinero: {
    title: 'Dinero (solo Admin)',
    steps: [
      'Backoffice de pagos: comisiones, liquidaciones y cobros.',
      'Verificá los cobros pendientes de acreditación.',
      'Emití liquidaciones a comercios y repartidores.',
    ],
    tip: 'Solo administradores: todo queda auditado.',
  },
  'mi-dinero': {
    title: 'Mi Dinero',
    steps: [
      'Tus liquidaciones, cobros y estado de cuenta.',
      'Si ves el aviso de deuda, regularizá en Billetera.',
      'Filtrá por estado y período.',
    ],
    tip: 'Con deuda no podés operar: regularizá primero.',
  },
  billetera: {
    title: 'Billetera',
    steps: [
      'Tus cuentas para cobrar, una por moneda.',
      'La primera queda como predeterminada.',
      'Desde acá pedís los retiros de tu saldo.',
    ],
    tip: 'Verificá CBU/alias antes de pedir un retiro.',
  },
  'my-orders': {
    title: 'Mis Pedidos',
    steps: [
      'Todo lo que compraste y su estado.',
      'Tocá un pedido para ver el detalle.',
      'Calificá cuando lo recibas.',
    ],
    tip: 'Si un pedido tarda, mirá su estado acá primero.',
  },
  'provider-orders': {
    title: 'Mi comercio',
    steps: [
      'Tu vidriera y balance del comercio.',
      'Entrá a cada sección para gestionar catálogo y pedidos.',
      'Si no tenés comercio asociado, registrate como prestador.',
    ],
    tip: 'Todo lo de tu comercio se maneja desde acá.',
  },
  'contact-inquiries': {
    title: 'Consultas',
    steps: [
      'Bandeja de mensajes del formulario de contacto.',
      'Filtrá por estado y respondé por correo.',
      'El botón de visitas muestra de dónde viene el tráfico.',
    ],
    tip: 'Respondé desde el botón de correo: ya trae el asunto.',
  },
  pedidos: {
    title: 'Pedidos (Despacho)',
    steps: [
      'Tablero para preparar y despachar pedidos.',
      'Cada pedido muestra cliente, items y estado.',
      'Avanzá el estado a medida que avanzás.',
    ],
    tip: 'Atendé los nuevos primero y en orden.',
  },
  inventario: {
    title: 'Inventario',
    steps: [
      'Stock por producto e insumo.',
      'Registrá entradas y salidas.',
      'El stock en cero pausa la venta.',
    ],
    tip: 'Revisá el stock antes de abrir la tienda.',
  },
  suscripciones: {
    title: 'Suscripciones',
    steps: [
      'Planes Hogar con facturación periódica.',
      'Emití facturas y controlá mora.',
      'A los 15 días de mora el plan se suspende solo.',
    ],
    tip: 'Los pedidos se generan solos al vencer.',
  },
  subscriptions: {
    title: 'Mis Suscripciones',
    steps: [
      'Tus planes activos y vencimientos.',
      'Pagá desde acá para evitar la suspensión.',
      'La baja es desde esta misma pantalla.',
    ],
    tip: 'Pagá antes del vencimiento y no se corta.',
  },
  requests: {
    title: 'Solicitudes',
    steps: [
      'Buscá prestadores y pedí presupuesto.',
      'Describí bien lo que necesitás.',
      'Coordiná día y hora desde la solicitud.',
    ],
    tip: 'Más detalles = mejor presupuesto.',
  },
  notifications: {
    title: 'Notificaciones',
    steps: [
      'Avisos de pedidos, pagos y mensajes.',
      'Tocá una notificación para ir al detalle.',
      'Marcá como leídas las que ya viste.',
    ],
    tip: 'Activá los permisos del teléfono para no perderte nada.',
  },
};

export function helpFor(screen: string | string[] | undefined): HelpGuide {
  const key = Array.isArray(screen) ? screen[0] : (screen ?? '');
  return (
    HELP_BY_SCREEN[key] ?? {
      title: 'Ayuda',
      steps: [
        'Explorá las opciones y filtros de la pantalla.',
        'Tocá cada tarjeta para ver su detalle.',
        'Bajá para cargar más elementos.',
      ],
      tip: 'Si algo no aparece, puede ser un permiso: consultá al Admin.',
    }
  );
}

export const HELP_FIRST_STEPS: { role: string; steps: string[] }[] = [
  { role: 'Cliente', steps: ['Completá tu dirección en Mi Perfil.', 'Buscá comercios y armá tu primer pedido.', 'Seguilo en Mis Pedidos y calificá al recibir.'] },
  { role: 'Comercio', steps: ['Cargá tu catálogo con fotos y precios.', 'Verificá tu zona de cobertura.', 'Atendé los pedidos del tablero en orden.'] },
  { role: 'Prestador', steps: ['Completá tus rubros y zonas.', 'Respondé las solicitudes que te lleguen.', 'Actualizá cada trabajo hasta cerrarlo.'] },
  { role: 'Repartidor', steps: ['Configurá vehículo y zona de cobertura.', 'Ponete EN LÍNEA en la Terminal.', 'Aceptá, entregá y retirá tu saldo.'] },
];

export const HELP_FAQ: { q: string; a: string }[] = [
  { q: 'No veo una sección en mi menú', a: 'Pedile al Admin que te asigne esa página. Sin página asignada, la sección no aparece.' },
  { q: 'Olvidé mi contraseña', a: 'En el ingreso tocá "¿Olvidaste tu contraseña?" y seguí el correo de recuperación.' },
  { q: 'Un pedido quedó trabado', a: 'Revisá su estado en Mis Pedidos. Si no avanza, escribí por Consultas.' },
  { q: '¿Cómo cobro lo que gané?', a: 'En Billetera pedí el retiro a tu cuenta cargada. Verificá CBU/alias antes.' },
  { q: 'No me llegan pedidos (repartidor)', a: 'Verificá: vehículo configurado, zona con comercios y estado EN LÍNEA.' },
  { q: '¿A quién pregunto si no está acá?', a: 'Usá la pantalla Consultas: llega directo al equipo Admin.' },
];
