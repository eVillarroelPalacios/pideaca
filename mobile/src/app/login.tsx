import { Redirect } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text } from 'react-native';

import { ApiError } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { AuthScreen } from '@/components/AuthScreen';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, spacing } from '@/theme';

export default function LoginScreen() {
  const { user, isLoading, signIn } = useAuth();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  if (isLoading) {
    return <LoadingScreen message="Revisando tu sesión…" />;
  }

  if (user) {
    return <Redirect href="/home" />;
  }

  async function handleSubmit() {
    setError(null);
    setFieldErrors({});

    if (!email.trim() || !password) {
      setError('Ingresá tu correo y tu contraseña.');
      return;
    }

    setSubmitting(true);

    try {
      await signIn(email, password);
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.message);
        setFieldErrors(caught.errors ?? {});
      } else {
        setError('No pudimos iniciar sesión. Intentá de nuevo.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <AuthScreen subtitle="Ingresá con la misma cuenta que usás en la web.">
      <ErrorBanner message={error} />

      <TextField
        label="Correo electrónico"
        value={email}
        onChangeText={setEmail}
        error={fieldErrors.email?.[0] ?? null}
        autoCapitalize="none"
        autoComplete="email"
        autoCorrect={false}
        keyboardType="email-address"
        placeholder="vos@correo.com"
        textContentType="emailAddress"
      />

      <TextField
        label="Contraseña"
        value={password}
        onChangeText={setPassword}
        error={fieldErrors.password?.[0] ?? null}
        placeholder="Tu contraseña"
        secureTextEntry={!showPassword}
        autoCapitalize="none"
        autoComplete="password"
        textContentType="password"
      />

      <Pressable
        accessibilityRole="button"
        onPress={() => setShowPassword((current) => !current)}
        style={styles.showPassword}
      >
        <Text style={styles.showPasswordText}>
          {showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
        </Text>
      </Pressable>

      <PrimaryButton
        title="Ingresar"
        onPress={handleSubmit}
        loading={submitting}
        disabled={submitting}
      />
    </AuthScreen>
  );
}

const styles = StyleSheet.create({
  showPassword: {
    alignSelf: 'flex-end',
    marginBottom: spacing.lg,
    marginTop: -spacing.sm,
  },
  showPasswordText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '600',
  },
});
