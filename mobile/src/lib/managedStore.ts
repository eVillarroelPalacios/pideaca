import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

// Cuenta gestionada por el Admin (modo managed de la web: umManagedUserId).
// Se persiste igual que el token para sobrevivir reinicios.
const MANAGED_KEY = 'pideaca.managed.user';

export type ManagedAccount = {
  id: number;
  name: string;
  email: string;
  role: string | null;
};

type WebStorage = {
  getItem: (key: string) => string | null;
  setItem: (key: string, value: string) => void;
  removeItem: (key: string) => void;
};

const webStorage =
  typeof globalThis !== 'undefined'
    ? (globalThis as { localStorage?: WebStorage }).localStorage
    : undefined;

export async function loadManaged(): Promise<ManagedAccount | null> {
  try {
    const raw =
      Platform.OS === 'web'
        ? webStorage?.getItem(MANAGED_KEY) ?? null
        : await SecureStore.getItemAsync(MANAGED_KEY);

    if (!raw) {
      return null;
    }

    const parsed = JSON.parse(raw) as Partial<ManagedAccount>;

    if (typeof parsed.id !== 'number') {
      return null;
    }

    return {
      id: parsed.id,
      name: typeof parsed.name === 'string' ? parsed.name : '',
      email: typeof parsed.email === 'string' ? parsed.email : '',
      role: typeof parsed.role === 'string' ? parsed.role : null,
    };
  } catch {
    return null;
  }
}

export async function saveManaged(account: ManagedAccount): Promise<void> {
  const raw = JSON.stringify(account);

  if (Platform.OS === 'web') {
    webStorage?.setItem(MANAGED_KEY, raw);
    return;
  }

  await SecureStore.setItemAsync(MANAGED_KEY, raw);
}

export async function clearManaged(): Promise<void> {
  if (Platform.OS === 'web') {
    webStorage?.removeItem(MANAGED_KEY);
    return;
  }

  await SecureStore.deleteItemAsync(MANAGED_KEY);
}
