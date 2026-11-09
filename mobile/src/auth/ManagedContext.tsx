import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';

import {
  clearManaged,
  loadManaged,
  saveManaged,
  type ManagedAccount,
} from '@/lib/managedStore';

type ManagedContextValue = {
  /** Cuenta gestionada por el Admin (null = cuenta propia). */
  managed: ManagedAccount | null;
  /** true mientras se lee la cuenta guardada al abrir la app. */
  isLoading: boolean;
  enter: (account: ManagedAccount) => Promise<void>;
  exit: () => Promise<void>;
};

const ManagedContext = createContext<ManagedContextValue | null>(null);

export function ManagedProvider({ children }: { children: ReactNode }) {
  const [managed, setManaged] = useState<ManagedAccount | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let active = true;

    void loadManaged().then((stored) => {
      if (active) {
        setManaged(stored);
        setIsLoading(false);
      }
    });

    return () => {
      active = false;
    };
  }, []);

  const enter = useCallback(async (account: ManagedAccount) => {
    await saveManaged(account);
    setManaged(account);
  }, []);

  const exit = useCallback(async () => {
    await clearManaged();
    setManaged(null);
  }, []);

  const value = useMemo(
    () => ({ managed, isLoading, enter, exit }),
    [managed, isLoading, enter, exit],
  );

  return <ManagedContext.Provider value={value}>{children}</ManagedContext.Provider>;
}

export function useManaged(): ManagedContextValue {
  const context = useContext(ManagedContext);

  if (!context) {
    throw new Error('useManaged debe usarse dentro de ManagedProvider.');
  }

  return context;
}
