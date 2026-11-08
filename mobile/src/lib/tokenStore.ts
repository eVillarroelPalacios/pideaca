import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

// Guardamos el token en el almacenamiento cifrado del sistema:
// - Android: SharedPreferences cifrado con Keystore.
// - iOS: Keychain.
// En web (solo para pruebas rápidas) usamos localStorage.
const TOKEN_KEY = 'pideaca.auth.token';

type WebStorage = {
  getItem: (key: string) => string | null;
  setItem: (key: string, value: string) => void;
  removeItem: (key: string) => void;
};

const webStorage =
  typeof globalThis !== 'undefined'
    ? (globalThis as { localStorage?: WebStorage }).localStorage
    : undefined;

// Caché en memoria para no tocar el almacenamiento en cada request.
let cachedToken: string | null = null;
let loaded = false;

export async function loadToken(): Promise<string | null> {
  if (loaded) {
    return cachedToken;
  }

  if (Platform.OS === 'web') {
    cachedToken = webStorage?.getItem(TOKEN_KEY) ?? null;
  } else {
    cachedToken = await SecureStore.getItemAsync(TOKEN_KEY);
  }

  loaded = true;
  return cachedToken;
}

export async function saveToken(token: string): Promise<void> {
  cachedToken = token;
  loaded = true;

  if (Platform.OS === 'web') {
    webStorage?.setItem(TOKEN_KEY, token);
    return;
  }

  await SecureStore.setItemAsync(TOKEN_KEY, token);
}

export async function clearToken(): Promise<void> {
  cachedToken = null;
  loaded = true;

  if (Platform.OS === 'web') {
    webStorage?.removeItem(TOKEN_KEY);
    return;
  }

  await SecureStore.deleteItemAsync(TOKEN_KEY);
}
