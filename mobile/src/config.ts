import Constants from 'expo-constants';
import { Platform } from 'react-native';

// Ruta del API dentro del servidor.
// En XAMPP el proyecto vive en /pideaca, por eso el prefijo.
// Si algún día el API queda en la raíz del dominio, cambiá acá.
const API_PATH = '/pideaca/api/mobile/v1';

const API_TIMEOUT_MS = 20000;

/**
 * Resuelve la URL base del API sin que tengas que tocar código según el
 * dispositivo:
 *
 * 1. Si definiste EXPO_PUBLIC_API_URL en mobile/.env, se usa eso.
 * 2. En web usa el mismo host donde corre Expo.
 * 3. En el celular usa la IP de la PC que Metro ya conoce (hostUri).
 * 4. Como último recurso: 10.0.2.2 (emulador Android) o localhost.
 *
 * Nota: para que el celular físico llegue a tu PC, Apache debe escuchar en
 * la red y el Firewall de Windows debe permitir el puerto 80.
 */
function resolveApiBaseUrl(): string {
  const explicit = process.env.EXPO_PUBLIC_API_URL;

  if (explicit && explicit.trim().length > 0) {
    return explicit.trim().replace(/\/+$/, '');
  }

  if (Platform.OS === 'web' && typeof globalThis.window !== 'undefined') {
    return `${window.location.protocol}//${window.location.hostname}${API_PATH}`;
  }

  const hostUri = Constants.expoConfig?.hostUri ?? '';
  const host = hostUri.split(':')[0];

  if (host) {
    return `http://${host}${API_PATH}`;
  }

  if (Platform.OS === 'android') {
    return `http://10.0.2.2${API_PATH}`;
  }

  return `http://localhost${API_PATH}`;
}

export const API_BASE_URL = resolveApiBaseUrl();

/**
 * Origen para los archivos estáticos (imágenes de publicidad, banners).
 *
 * Las URLs que devuelve el API apuntan a APP_URL, que en XAMPP es
 * http://localhost/pideaca. En el emulador/celular ese host no existe, así
 * que se reemplaza solo el origen por el del API. Se puede sobreescribir con
 * EXPO_PUBLIC_ASSET_URL si las imágenes viven en otro servidor/CDN.
 */
function resolveAssetBaseUrl(): string {
  const explicit = process.env.EXPO_PUBLIC_ASSET_URL;

  if (explicit && explicit.trim().length > 0) {
    return explicit.trim().replace(/\/+$/, '');
  }

  const match = API_BASE_URL.match(/^(https?:\/\/[^/]+)/i);

  return match ? match[1] : API_BASE_URL.replace(/\/+$/, '');
}

export const ASSET_BASE_URL = resolveAssetBaseUrl();
export { API_TIMEOUT_MS };
