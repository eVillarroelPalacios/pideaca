import { Link, Redirect } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { ApiError, api } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { AuthScreen } from '@/components/AuthScreen';
import { ComboField } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';
import type { TypeUser } from '@/types/api';

export default function RegisterScreen() {
  const { user, isLoading, signUp } = useAuth();

  const [types, setTypes] = useState<TypeUser[]>([]);
  const [loadingTypes, setLoadingTypes] = useState(true);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [selectedTypeId, setSelectedTypeId] = useState<number | null>(null);
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  // Los tipos de cuenta (Cliente, Prestador, etc.) salen del API para que el
  // panel web y la app siempre muestren lo mismo.
  useEffect(() => {
    let active = true;

    api
      .types()
      .then((response) => {
        if (active) {
          setTypes(response.data);
        }
      })
      .catch(() => {
        if (active) {
          setError('No pudimos cargar los tipos de cuenta. Revisá la conexión.');
        }
      })
      .finally(() => {
        if (active) {
          setLoadingTypes(false);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  if (isLoading) {
    return <LoadingScreen message="Revisando tu sesión…" />;
  }

  if (user) {
    return <Redirect href="/home" />;
  }

  async function handleSubmit() {
    setError(null);
    setFieldErrors({});

    if (!name.trim()) {
      setError('Ingresá tu nombre completo.');
      return;
    }

    if (!email.trim()) {
      setError('Ingresá tu correo electrónico.');
      return;
    }

    if (!selectedTypeId) {
      setError('Elegí el tipo de cuenta.');
      return;
    }

    if (password.length < 8) {
      setError('La contraseña debe tener al menos 8 caracteres.');
      return;
    }

    if (password !== passwordConfirmation) {
      setError('Las contraseñas no coinciden.');
      return;
    }

    setSubmitting(true);

    try {
      await signUp({
        name: name.trim(),
        email: email.trim(),
        type_user_id: selectedTypeId,
        password,
        password_confirmation: passwordConfirmation,
      });
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.message);
        setFieldErrors(caught.errors ?? {});
      } else {
        setError('No pudimos crear la cuenta. Intentá de nuevo.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <AuthScreen
      title="Creá tu cuenta"
      subtitle="Elegí cómo querés usar PideAca. Podés tener más de una cuenta con el mismo correo."
      footer={
        <View style={styles.footerRow}>
          <Text style={styles.footerText}>¿Ya tenés cuenta?</Text>
          <Link href="/login" style={styles.link}>
            Ingresá
          </Link>
        </View>
      }
    >
      <ErrorBanner message={error} />

      <TextField
        label="Nombre completo"
        value={name}
        onChangeText={setName}
        error={fieldErrors.name?.[0] ?? null}
        autoCapitalize="words"
        autoComplete="name"
        placeholder="Tu nombre y apellido"
        textContentType="name"
      />

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

      {loadingTypes ? (
        <View style={styles.typesWrapper}>
          <Text style={styles.typesLabel}>Tipo de cuenta</Text>
          <View style={styles.typesLoading}>
            <ActivityIndicator color={colors.brand} />
            <Text style={styles.typesLoadingText}>Cargando opciones…</Text>
          </View>
        </View>
      ) : (
        <ComboField
          label="Tipo de cuenta"
          value={selectedTypeId}
          options={types.map((type) => ({
            value: type.id,
            label: type.description,
          }))}
          onChange={setSelectedTypeId}
          placeholder="Seleccioná un tipo de cuenta"
          error={fieldErrors.type_user_id?.[0] ?? null}
        />
      )}

      <TextField
        label="Contraseña"
        value={password}
        onChangeText={setPassword}
        error={fieldErrors.password?.[0] ?? null}
        placeholder="Mínimo 8 caracteres"
        secureTextEntry
        autoCapitalize="none"
        autoComplete="new-password"
        textContentType="newPassword"
      />

      <TextField
        label="Repetí la contraseña"
        value={passwordConfirmation}
        onChangeText={setPasswordConfirmation}
        error={fieldErrors.password_confirmation?.[0] ?? null}
        placeholder="Repetí tu contraseña"
        secureTextEntry
        autoCapitalize="none"
        autoComplete="new-password"
        textContentType="newPassword"
      />

      <PrimaryButton
        title="Crear cuenta"
        onPress={handleSubmit}
        loading={submitting}
        disabled={submitting || loadingTypes}
      />
    </AuthScreen>
  );
}

const styles = StyleSheet.create({
  typesWrapper: {
    marginBottom: spacing.lg,
  },
  typesLabel: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
    marginBottom: spacing.sm,
  },
  typesLoading: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    paddingVertical: spacing.sm,
  },
  typesLoadingText: {
    color: colors.muted,
    fontSize: 14,
  },
  typesList: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  chip: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  chipSelected: {
    backgroundColor: colors.brandSoft,
    borderColor: colors.brand,
  },
  chipText: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '600',
  },
  chipTextSelected: {
    color: colors.brandDark,
  },
  fieldError: {
    color: colors.danger,
    fontSize: 13,
    marginTop: spacing.xs,
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
