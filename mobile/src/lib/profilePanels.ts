import type { ProfileFlags } from '@/types/api';

/**
 * Secciones del "Mi Perfil" (sidebar de la web).
 *
 * Es la misma lista y las mismas condiciones de `dashboard.blade.php`:
 * cada item se muestra según el rol/permisos del usuario efectivo
 * (el propio o el gestionado por un Admin). La usan Mi Perfil y la
 * pantalla Usuarios para que el sidebar sea idéntico en ambas.
 */
export type PanelKey =
  | 'negocio'
  | 'horarios'
  | 'servicios'
  | 'repartidores'
  | 'imagenes'
  | 'categorias'
  | 'direccion'
  | 'usuarios'
  | 'vehiculo'
  | 'cuenta';

export type ProfilePanel = {
  key: PanelKey;
  label: string;
  icon: string;
};

export const PANEL_META: ProfilePanel[] = [
  { key: 'negocio', label: 'Datos del Negocio', icon: '🏪' },
  { key: 'horarios', label: 'Horarios de Atención', icon: '🕒' },
  { key: 'servicios', label: 'Mis Servicios', icon: '🧰' },
  { key: 'repartidores', label: 'Repartidores de confianza', icon: '🛵' },
  { key: 'imagenes', label: 'Publicidad', icon: '🖼️' },
  { key: 'categorias', label: 'Categorías', icon: '🏷️' },
  { key: 'vehiculo', label: 'Mi Vehículo', icon: '🚗' },
  { key: 'direccion', label: 'Mi Dirección', icon: '📍' },
  { key: 'usuarios', label: 'Usuarios', icon: '👥' },
  { key: 'cuenta', label: 'Contraseñas', icon: '🔐' },
];

type Options = {
  /**
   * En modo gestionado Mi Perfil oculta el panel Usuarios (se administra
   * desde la pantalla Usuarios). La pantalla Usuarios sí lo muestra como
   * sección activa.
   */
  keepUsersInManaged?: boolean;
};

/**
 * Filtra las secciones visibles. Sin perfil cargado solo queda Contraseñas,
 * igual que el fallback de la web.
 */
export function visiblePanels(
  flags: ProfileFlags | null | undefined,
  managedId: number | null,
  options: Options = {},
): ProfilePanel[] {
  if (!flags) {
    return PANEL_META.filter((panel) => panel.key === 'cuenta');
  }

  const hiddenInManaged: PanelKey[] = managedId
    ? [
        'repartidores',
        'vehiculo',
        ...(options.keepUsersInManaged ? [] : (['usuarios'] as PanelKey[])),
      ]
    : [];

  return PANEL_META.filter((panel) => {
    if (hiddenInManaged.includes(panel.key)) {
      return false;
    }
    if (panel.key === 'cuenta') {
      return true;
    }
    if (panel.key === 'vehiculo') {
      return flags.is_repartidor;
    }
    if (panel.key === 'direccion') {
      return flags.can_see_address;
    }
    if (panel.key === 'usuarios') {
      return flags.can_manage_users;
    }
    if (panel.key === 'repartidores') {
      return flags.can_see_trusted_drivers;
    }

    // Negocio, horarios, servicios, publicidad y categorías.
    return flags.can_configure_business;
  });
}
