import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';
import { Alert, Linking, Platform } from 'react-native';

import { api } from '@/api/client';

// Ultimo chequeo (evita preguntar en cada apertura): 24 h.
const LAST_CHECK_KEY = 'pideaca.app_update.last_check';
const CHECK_INTERVAL_MS = 24 * 60 * 60 * 1000;

type InstalledInfo = { versionName: string; versionCode: number };

/**
 * Versión instalada según lo embebido en el build release
 * (app.json: version + android.versionCode).
 */
export function installedAppVersion(): InstalledInfo {
  const expoConfig = Constants.expoConfig ?? null;
  const versionName =
    (typeof expoConfig?.version === 'string' && expoConfig.version) || '1.0.0';
  const rawCode = (expoConfig?.android as { versionCode?: unknown } | undefined)
    ?.versionCode;
  const versionCode =
    typeof rawCode === 'number' && Number.isFinite(rawCode) ? rawCode : 0;
  return { versionName, versionCode };
}

async function shouldCheck(now: number): Promise<boolean> {
  try {
    if (Platform.OS === 'web') {
      const raw =
        typeof globalThis !== 'undefined'
          ? ((globalThis as { localStorage?: Storage }).localStorage?.getItem(
              LAST_CHECK_KEY,
            ) ?? null)
          : null;
      if (!raw) return true;
      return now - Number(raw) > CHECK_INTERVAL_MS;
    }
    const raw = await SecureStore.getItemAsync(LAST_CHECK_KEY);
    if (!raw) return true;
    return now - Number(raw) > CHECK_INTERVAL_MS;
  } catch {
    return true;
  }
}

async function markChecked(now: number): Promise<void> {
  try {
    if (Platform.OS === 'web') {
      (globalThis as { localStorage?: Storage }).localStorage?.setItem(
        LAST_CHECK_KEY,
        String(now),
      );
      return;
    }
    await SecureStore.setItemAsync(LAST_CHECK_KEY, String(now));
  } catch {
    // No bloquea la app si el almacenamiento falla.
  }
}

function openDownload(url: string): void {
  void Linking.openURL(url).catch(() => {
    Alert.alert(
      'No pudimos abrir la descarga',
      'Copiá este enlace en tu navegador para actualizar:\n' + url,
    );
  });
}

/**
 * Chequeo propio de actualizaciones ($0, sin EAS Update).
 *
 * Compara el version_code instalado con el publicado en
 * GET /app/version. Si hay uno mayor y APK publicado, muestra el aviso:
 * - obligatoria: un solo boton Descargar.
 * - opcional: Descargar / Mas tarde.
 *
 * Se llama una vez al arrancar (RootLayout) y como maximo 1 vez por dia.
 * Nunca bloquea ni rompe el arranque: cualquier error se ignora.
 */
export async function checkForAppUpdate(): Promise<void> {
  try {
    if (Platform.OS !== 'android') {
      return;
    }

    const now = Date.now();
    if (!(await shouldCheck(now))) {
      return;
    }
    await markChecked(now);

    const installed = installedAppVersion();
    if (installed.versionCode <= 0) {
      return;
    }

    const res = await api.appVersion();
    const remote = res.data;
    if (!remote || !remote.apk_published || !remote.apk_url) {
      return;
    }
    if (remote.version_code <= installed.versionCode) {
      return;
    }

    const detail =
      `Tenés la versión ${installed.versionName} y ya salió la ` +
      `${remote.version_name}.${remote.release_notes ? '\n\n' + remote.release_notes : ''}`;

    if (remote.mandatory) {
      Alert.alert('Actualización obligatoria', detail, [
        { text: 'Descargar', onPress: () => openDownload(remote.apk_url!) },
      ]);
      return;
    }

    Alert.alert('Hay una actualización', detail, [
      { text: 'Más tarde', style: 'cancel' },
      { text: 'Descargar', onPress: () => openDownload(remote.apk_url!) },
    ]);
  } catch {
    // Red caida o endpoint aun no desplegado: la app sigue igual.
  }
}
