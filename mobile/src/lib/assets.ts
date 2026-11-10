import { ASSET_BASE_URL } from '@/config';

/**
 * Adapta las URLs de imágenes del servidor web al host que usa la app.
 *
 * El API devuelve URLs absolutas con APP_URL (por ejemplo
 * http://localhost/pideaca/images/publicidad/provider_1.jpg). En el
 * emulador o en un celular ese "localhost" apunta al propio dispositivo,
 * así que se conserva el path y se reemplaza solo el origen.
 *
 * No copia archivos: las imágenes siguen viviendo en public/images del
 * servidor web y se consumen por HTTP.
 */
export function resolveAssetUrl(url: string | null | undefined): string | null {
  if (!url) {
    return null;
  }

  const trimmed = url.trim();

  if (trimmed.length === 0) {
    return null;
  }

  const absolute = trimmed.match(/^https?:\/\/[^/]+(\/.*)?$/i);
  const path = absolute ? absolute[1] ?? '/' : trimmed;

  return `${ASSET_BASE_URL}${path.startsWith('/') ? path : `/${path}`}`;
}
