import { router, Stack, useRootNavigationState } from 'expo-router';
import * as Notifications from 'expo-notifications';
import { useEffect } from 'react';
import { Platform } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { AuthProvider } from '@/auth/AuthContext';
import { ManagedProvider } from '@/auth/ManagedContext';
import { colors } from '@/theme';

/**
 * Tocar una notificación abre la pantalla que indica su `url`
 * (por ejemplo /request/123). Espera a que la navegación esté lista.
 */
function useNotificationObserver() {
  const navigationState = useRootNavigationState();

  useEffect(() => {
    // Las respuestas a notificaciones no existen en web (solo app nativa).
    if (Platform.OS === 'web') {
      return;
    }

    function redirect(notification: Notifications.Notification) {
      const url = notification.request.content.data?.url;

      if (typeof url === 'string' && url.startsWith('/')) {
        router.push(url as never);
        void Notifications.clearLastNotificationResponseAsync();
      }
    }

    if (!navigationState?.key) {
      return;
    }

    void Notifications.getLastNotificationResponseAsync().then((last) => {
      if (last?.notification) {
        redirect(last.notification);
      }
    });

    const subscription = Notifications.addNotificationResponseReceivedListener(
      (response) => redirect(response.notification),
    );

    return () => {
      subscription.remove();
    };
  }, [navigationState?.key]);
}

export default function RootLayout() {
  useNotificationObserver();

  return (
    <SafeAreaProvider>
      <AuthProvider>
        <ManagedProvider>
          <StatusBar style="dark" />
          <Stack
            screenOptions={{
              headerShown: false,
              contentStyle: { backgroundColor: colors.background },
            }}
          >
            {/* Menú lateral: modal transparente que deja ver la pantalla anterior. */}
            <Stack.Screen
              name="menu"
              options={{
                animation: 'slide_from_left',
                contentStyle: { backgroundColor: 'transparent' },
                presentation: 'transparentModal',
              }}
            />
          </Stack>
        </ManagedProvider>
      </AuthProvider>
    </SafeAreaProvider>
  );
}
