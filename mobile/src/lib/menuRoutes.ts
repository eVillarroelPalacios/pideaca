/**
 * Traducción de páginas del menú web (`url` de la tabla pages) a rutas de
 * Expo Router. Devuelve null cuando la página aún no tiene pantalla móvil:
 * la app la muestra como "Próximamente" en vez de un link muerto.
 *
 * La clave incluye el módulo porque hay urls repetidas (por ejemplo
 * `reportes` existe en Mercado y en Reparto).
 */
export function menuRouteFor(module: string, url: string | null): string | null {
  if (!url) {
    return null;
  }

  const key = `${module.trim().toLowerCase()}::${url.trim().toLowerCase()}`;

  const routes: Record<string, string> = {
    // Mi Perfil vive en el home de la app.
    'administrar::perfil': '/home',
    // Administrar: consultas del formulario de contacto.
    'administrar::consultas-sugerencias': '/contact-inquiries',
    // Administrar: catalogo de estados para los grupos.
    'administrar::estados-grupos': '/group-statuses',
    // Administrar: catalogo de estados para los usuarios.
    'administrar::estados-usuarios': '/user-statuses',
    // Administrar: grupos del catalogo.
    'administrar::grupos': '/groups',
    // Administrar: modulos del sistema.
    'administrar::modulos': '/modules',
    // Administrar: paginas del sistema.
    'administrar::paginas': '/pages',
    // Administrar: sub grupos del catalogo.
    'administrar::sub-grupos': '/subgroups',
    // Administrar: tipos de monedas (una por pais).
    'administrar::tipo-monedas': '/currencies',
    // Hogar: lo ya construido.
    'hogar::solicitar-servicio': '/providers',
    'hogar::mis-servicios': '/requests',
    'hogar::gestionar-solicitudes': '/provider-orders',
    'hogar::ordenes-servicio': '/provider-orders',
    'hogar::ordenes-gestion': '/provider-orders',
  };

  return routes[key] ?? null;
}

/** Emoji por módulo, para que cada sección se reconozca de un vistazo. */
export function menuModuleIcon(module: string): string {
  const value = module.trim().toLowerCase();

  if (value === 'hogar') {
    return '🏠';
  }

  if (value === 'mercado') {
    return '🛒';
  }

  if (value === 'reparto') {
    return '🛵';
  }

  if (value === 'administrar') {
    return '⚙️';
  }

  return '📁';
}
