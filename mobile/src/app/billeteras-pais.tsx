import { Redirect } from 'expo-router';
import { useEffect, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { ComboField, type ComboOption } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';
import type { WalletType } from '@/types/api';

/**
 * Billeteras por país (Fase 1, solo Admin). Espeja la página web:
 * catálogo de tipos de billetera que ven los usuarios al cargar la suya.
 * Lo desactivado no aparece en los combos.
 */
const COUNTRY_OPTIONS: ComboOption<string>[] = [
  { value: 'AR', label: 'Argentina' },
  { value: 'ES', label: 'España' },
];

const YES_NO_OPTIONS: ComboOption<string>[] = [
  { value: '1', label: 'Sí' },
  { value: '0', label: 'No' },
];

function firstError(caught: unknown, fallback: string): string {
  if (caught instanceof ApiError) {
    if (caught.errors) {
      const first = Object.values(caught.errors)[0];
      if (first && first.length > 0) return first[0];
    }
    return caught.message;
  }
  return fallback;
}

export default function BilleterasPaisScreen() {
  const { user, isLoading } = useAuth();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [types, setTypes] = useState<WalletType[]>([]);
  const [busy, setBusy] = useState(false);

  const [country, setCountry] = useState('AR');
  const [code, setCode] = useState('');
  const [name, setName] = useState('');
  const [fields, setFields] = useState('holder,cbu,alias');
  const [active, setActive] = useState('1');

  useEffect(() => {
    let live = true;
    setLoading(true);
    setError(null);

    api
      .dineroWalletTypes()
      .then((response) => {
        if (!live) return;
        setTypes(response.data);
      })
      .catch((caught: unknown) => {
        if (!live) return;
        setError(firstError(caught, 'No pudimos cargar los tipos.'));
      })
      .finally(() => {
        if (live) setLoading(false);
      });

    return () => {
      live = false;
    };
  }, []);

  if (isLoading || loading) {
    return <LoadingScreen message="Cargando billeteras por país…" />;
  }

  if (!user) {
    return <Redirect href="/login" />;
  }

  if (!user.is_admin) {
    return (
      <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
        <View style={styles.center}>
          <Text style={styles.title}>Billeteras por país</Text>
          <ErrorBanner message="Solo administradores." />
        </View>
      </SafeAreaView>
    );
  }

  async function reload() {
    const res = await api.dineroWalletTypes();
    setTypes(res.data);
  }

  async function handleCreate() {
    if (!code.trim() || !name.trim()) {
      setError('Ingresá código y nombre visible.');
      return;
    }
    setError(null);
    setBusy(true);
    try {
      const res = await api.dineroCreateWalletType({
        country,
        code: code.trim().toLowerCase(),
        name: name.trim(),
        fields: fields
          .split(',')
          .map((f) => f.trim())
          .filter((f) => f.length > 0),
        active: active === '1',
      });
      setNotice(res.message);
      setCode('');
      setName('');
      await reload();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos crear el tipo.'));
    } finally {
      setBusy(false);
    }
  }

  async function handleToggle(type: WalletType) {
    setError(null);
    try {
      const res = await api.dineroUpdateWalletType(type.id, {
        active: !type.active,
      });
      setNotice(res.message);
      await reload();
    } catch (caught) {
      setError(firstError(caught, 'No pudimos actualizar el tipo.'));
    }
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <ScrollView contentContainerStyle={styles.container}>
        <Text style={styles.title}>Billeteras por país</Text>
        <Text style={styles.subtitle}>
          Catálogo de tipos que ven los usuarios al cargar la suya. Lo desactivado
          no aparece en los combos.
        </Text>

        {error ? <ErrorBanner message={error} /> : null}
        {notice ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>{notice}</Text>
          </View>
        ) : null}

        {types.length === 0 ? (
          <Text style={styles.empty}>Sin tipos cargados.</Text>
        ) : (
          types.map((t) => (
            <View key={t.id} style={styles.card}>
              <Text style={styles.cardTitle}>
                {t.country} · {t.name} ({t.code})
              </Text>
              <Text style={[styles.cardSub, t.active ? styles.ok : styles.bad]}>
                {(t.fields ?? []).join(', ') || 'Sin campos'} ·{' '}
                {t.active ? 'Activa' : 'Inactiva'}
              </Text>
              <PrimaryButton
                title={t.active ? 'Desactivar' : 'Activar'}
                variant="ghost"
                onPress={() => void handleToggle(t)}
              />
            </View>
          ))
        )}

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Agregar tipo</Text>
          <ComboField label="País" value={country} options={COUNTRY_OPTIONS} onChange={setCountry} />
          <TextField
            label="Código (único por país)"
            value={code}
            onChangeText={setCode}
            placeholder="ej: galicia"
            autoCapitalize="none"
          />
          <TextField
            label="Nombre visible"
            value={name}
            onChangeText={setName}
            placeholder="ej: Banco Galicia"
          />
          <TextField
            label="Campos (coma: holder,cbu,alias,iban,email,phone,bank)"
            value={fields}
            onChangeText={setFields}
            autoCapitalize="none"
          />
          <ComboField label="Activa" value={active} options={YES_NO_OPTIONS} onChange={setActive} />
          <PrimaryButton title="Agregar tipo" loading={busy} onPress={() => void handleCreate()} />
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.background,
    flex: 1,
  },
  container: {
    padding: spacing.lg,
  },
  center: {
    padding: spacing.xl,
  },
  title: {
    color: colors.text,
    fontSize: 20,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.lg,
    marginTop: spacing.xs,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.lg,
  },
  cardTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
    marginBottom: spacing.sm,
  },
  cardSub: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.sm,
  },
  empty: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.lg,
  },
  notice: {
    backgroundColor: colors.successSoft,
    borderRadius: radius.md,
    marginBottom: spacing.lg,
    padding: spacing.md,
  },
  noticeText: {
    color: colors.success,
    fontSize: 13,
    fontWeight: '600',
  },
  ok: {
    color: colors.success,
  },
  bad: {
    color: colors.danger,
  },
});
