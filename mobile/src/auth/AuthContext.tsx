import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';

import { api } from '@/api/client';
import { clearToken, loadToken, saveToken } from '@/lib/tokenStore';
import { registerPushToken, unregisterPushToken } from '@/lib/notifications';
import type { RegisterPayload, User } from '@/types/api';

type AuthContextValue = {
  /** Usuario autenticado o null si no hay sesión. */
  user: User | null;
  /** true mientras revisamos si había un token guardado al abrir la app. */
  isLoading: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  signUp: (payload: RegisterPayload) => Promise<void>;
  signOut: () => Promise<void>;
  deleteAccount: () => Promise<void>;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Al abrir la app: si hay token guardado, lo validamos contra /auth/me.
  useEffect(() => {
    let active = true;

    (async () => {
      try {
        const token = await loadToken();

        if (!token) {
          return;
        }

        const response = await api.me();

        if (active) {
          setUser(response.user);
        }
      } catch {
        // Token vencido o revocado: lo borramos y mostramos el login.
        await clearToken();
      } finally {
        if (active) {
          setIsLoading(false);
        }
      }
    })();

    return () => {
      active = false;
    };
  }, []);

  const signIn = useCallback(async (email: string, password: string) => {
    const response = await api.login(email.trim(), password);
    await saveToken(response.token);
    setUser(response.user);
    // No bloquea el login: si falla el permiso, la app sigue igual.
    void registerPushToken();
  }, []);

  const signUp = useCallback(async (payload: RegisterPayload) => {
    const response = await api.register(payload);
    await saveToken(response.token);
    setUser(response.user);
    void registerPushToken();
  }, []);

  const signOut = useCallback(async () => {
    try {
      await unregisterPushToken();
      await api.logout();
    } catch {
      // Si el token ya no vale, igual cerramos la sesión local.
    }

    await clearToken();
    setUser(null);
  }, []);

  const deleteAccount = useCallback(async () => {
    await api.deleteAccount();
    await clearToken();
    setUser(null);
  }, []);

  const refresh = useCallback(async () => {
    const response = await api.me();
    setUser(response.user);
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      isLoading,
      signIn,
      signUp,
      signOut,
      deleteAccount,
      refresh,
    }),
    [user, isLoading, signIn, signUp, signOut, deleteAccount, refresh],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error('useAuth debe usarse dentro de <AuthProvider>.');
  }

  return context;
}
