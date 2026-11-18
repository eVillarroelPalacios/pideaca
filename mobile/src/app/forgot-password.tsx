import { Link, Redirect, router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { ApiError, api } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { AuthScreen } from '@/components/AuthScreen';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Recuperacion de contrasena: replica el mismo flujo de la web.
 *
 * El API responde siempre igual (exista o no la cuenta) para no revelar
 * que correos estan registrados, y envia un enlace valido por 60 minutos.
 */
export default function ForgotPasswordScreen() {
  const { user, isLoading } = useAuth();
  const params = useLocalSearchParams<{ email?: string }>();
  const initialEmail = typeof params.email === 'string' ? params.email : '';

  const [email, setEmail] = useState(initialEmail);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
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
    setSuccess(null);
    setFieldErrors({});

    const trimmed = email.trim();

    if (!trimmed) {
      setError('Ingresá tu correo electrónico.');
      return;
    }

    if (!EMAIL_PATTERN.test(trimmed)) {
      setError('Ingresá un correo válido.');
      return;
    }

    setSubmitting(true);

    try {
      const response = await api.forgotPassword(trimmed);
      setSuccess(response.message);
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.message);
        setFieldErrors(caught.errors ?? {});
      } else {
        setError('No pudimos procesar la solicitud. Intentá de nuevo.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <AuthScreen
      title="Recuperá tu contraseña"
      subtitle="Te enviamos un enlace al correo de tu cuenta para que elijas una contraseña nueva."
      footer={
        <View style={styles.footerRow}>
          <Text style={styles.footerText}>¿Ya la recordaste?</Text>
          <Link href="/login" style={styles.link}>
            Ingresá
          </Link>
        </View>
      }
    >
      {success ? (
        <View style={styles.success} accessibilityRole="alert">
          <Text style={styles.successText}>{success}</Text>
          <Text style={styles.successHint}>
            El enlace vence en 60 minutos y sirve una sola vez. Si no lo ves, revisá
            la carpeta de spam.
          </Text>
        </View>
      ) : null}

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
        editable={!submitting}
      />

      <PrimaryButton
        title="Enviar enlace"
        onPress={handleSubmit}
        loading={submitting}
        disabled={submitting}
      />

      {success ? (
        <View style={styles.backWrapper}>
          <PrimaryButton
            title="Volver a ingresar"
            variant="ghost"
            onPress={() => router.replace('/login')}
          />
        </View>
      ) : null}
    </AuthScreen>
  );
}

const styles = StyleSheet.create({
  success: {
    backgroundColor: colors.successSoft,
    borderColor: '#bbf7d0',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  successText: {
    color: colors.success,
    fontSize: 14,
    fontWeight: '600',
    lineHeight: 20,
  },
  successHint: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 18,
    marginTop: spacing.xs,
  },
  backWrapper: {
    marginTop: spacing.md,
  },
  footerRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.xs,
  },
  footerText: {
    color: colors.muted,
    fontSize: 14,
  },
  link: {
    color: colors.brand,
    fontSize: 14,
    fontWeight: '700',
  },
});
