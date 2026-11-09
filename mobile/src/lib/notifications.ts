import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { AndroidImportance } from 'expo-notifications';
import { Platform } from 'react-native';

import { api } from '@/api/client';

// Cómo se muestra una notificación que llega con la app abierta.
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowBanner: true,
    shouldShowList: true,
    shouldPlaySound: false,
    shouldSetBadge: false,
  }),
});

let cachedToken: string | null = null;

/** Canal de Android (obligatorio desde Android 8 para que suene/muestre). */
async function ensureAndroidChannel(): Promise<void> {
  if (Platform.OS !== 'android') {
    return;
  }

  await Notifications.setNotificationChannelAsync('pideaca', {
    name: 'PideAca',
    importance: AndroidImportance.DEFAULT,
  });
}

/**
 * Pide permiso, obtiene el ExponentPushToken y lo registra en el API.
 * Devuelve null si no hay permiso, no es un dispositivo físico o falla.
 * Nunca lanza: las notificaciones son opcionales.
 */
export async function registerPushToken(): Promise<string | null> {
  try {
    if (!Device.isDevice) {
      return null;
    }

    await ensureAndroidChannel();

    const { status: existingStatus } = await Notifications.getPermissionsAsync();
    let finalStatus = existingStatus;

    if (existingStatus !== 'granted') {
      const { status } = await Notifications.requestPermissionsAsync();
      finalStatus = status;
    }

    if (finalStatus !== 'granted') {
      return null;
    }

    const token = (await Notifications.getExpoPushTokenAsync()).data;
    cachedToken = token;

    await api.registerPushToken(token, Platform.OS === 'ios' ? 'ios' : 'android');

    return token;
  } catch {
    return null;
  }
}

/** Borra el token del servidor (al cerrar sesión). Nunca lanza. */
export async function unregisterPushToken(): Promise<void> {
  try {
    const token = cachedToken ?? (await Notifications.getExpoPushTokenAsync()).data;
    cachedToken = null;

    await api.unregisterPushToken(token);
  } catch {
    // Si no hay token o falla la red, no pasa nada.
  }
}
