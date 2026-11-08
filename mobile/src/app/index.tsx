import { Redirect } from 'expo-router';

import { useAuth } from '@/auth/AuthContext';
import { LoadingScreen } from '@/components/LoadingScreen';

/**
 * Pantalla inicial: decide si mandar al login o al home según la sesión.
 */
export default function IndexScreen() {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return <LoadingScreen message="Iniciando PideAca…" />;
  }

  return <Redirect href={user ? '/home' : '/login'} />;
}
