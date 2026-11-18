import { Redirect, router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { ApiError, api } from '@/api/client';
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
  const [inactive, setInactive] = useState(false);
  const [resending, setResending] = useState(false);
  const [resent, setResent] = useState<string | null>(null);

  if (isLoading) {
    return <LoadingScreen message="Revisando tu sesión…" />;
  }

  if (user) {
    return <Redirect href="/home" />;
  }

  async function handleSubmit() {
    setError(null);
    setFieldErrors({});
    setInactive(false);
    setResent(null);

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
        setInactive(caught.code === 'ACCOUNT_INACTIVE');
      } else {
        setError('No pudimos iniciar sesión. Intentá de nuevo.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  async function handleResend() {
    if (!email.trim() || resending) {
      return;
    }

    setResending(true);
    setResent(null);

    try {
      const response = await api.resendActivation(email.trim());
      setResent(response.message);
    } catch (caught) {
      setResent(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos reenviar el correo. Intentá de nuevo.',
      );
    } finally {
      setResending(false);
    }
  }

  return (
    <AuthScreen subtitle="Ingresá con la misma cuenta que usás en la web.">
      <ErrorBanner message={error} />

      {inactive ? (
        <View style={styles.resendWrapper}>
          <Pressable
            accessibilityRole="button"
            onPress={handleResend}
            disabled={resending}
            style={styles.resend}
          >
            <Text style={styles.resendText}>
              {resending ? 'Enviando…' : 'Reenviar correo de activación'}
            </Text>
          </Pressable>
          {resent ? <Text style={styles.resentText}>{resent}</Text> : null}
        </View>
      ) : null}

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

      <View style={styles.helperRow}>
        <Pressable
          accessibilityRole="button"
          onPress={() => setShowPassword((current) => !current)}
          style={styles.showPassword}
        >
          <Text style={styles.showPasswordText}>
            {showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
          </Text>
        </Pressable>

        <Pressable
          accessibilityRole="link"
          onPress={() =>
            // Los tipos de Expo Router viven en .expo/ y se regeneran al
            // correr `expo start`; hasta entonces la ruta nueva va casteada
            // (mismo criterio que en _layout.tsx).
            router.push({
              pathname: '/forgot-password',
              params: { email: email.trim() },
            } as never)
          }
          style={styles.forgotPassword}
        >
          <Text style={styles.forgotPasswordText}>¿Olvidaste tu contraseña?</Text>
        </Pressable>
      </View>

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
  helperRow: {
    alignItems: 'flex-end',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    justifyContent: 'space-between',
    marginBottom: spacing.lg,
    marginTop: -spacing.sm,
  },
  showPassword: {
    paddingVertical: spacing.xs,
  },
  showPasswordText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '600',
  },
  forgotPassword: {
    paddingVertical: spacing.xs,
  },
  forgotPasswordText: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  resendWrapper: {
    marginBottom: spacing.md,
  },
  resend: {
    paddingVertical: spacing.xs,
  },
  resendText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '600',
  },
  resentText: {
    color: colors.success,
    fontSize: 13,
    lineHeight: 18,
    marginTop: spacing.xs,
  },
});
