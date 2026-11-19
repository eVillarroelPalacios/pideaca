import { Redirect, router } from 'expo-router';
import { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { ApiError, api } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { AuthScreen } from '@/components/AuthScreen';
import { ComboField } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import type { BillingStatusResponse, PayoutAccount, WalletType } from '@/types/api';
import { colors, radius, spacing } from '@/theme';

/**
 * Billetera de cobro (Fase 1). Mismas reglas que el panel web:
 * CBU/alias/IBAN validados, una predeterminada por moneda y al menos
 * una activa para operar.
 */
export default function BilleteraScreen() {
  const { user, isLoading } = useAuth();

  const [accounts, setAccounts] = useState<PayoutAccount[]>([]);
  const [canOperate, setCanOperate] = useState(true);
  const [billing, setBilling] = useState<BillingStatusResponse['data'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  const [label, setLabel] = useState('');
  const [holder, setHolder] = useState('');
  const [cbu, setCbu] = useState('');
  const [alias, setAlias] = useState('');
  const [accountRef, setAccountRef] = useState('');
  const [iban, setIban] = useState('');
  const [bank, setBank] = useState('');
  const [country, setCountry] = useState('AR');
  const [types, setTypes] = useState<WalletType[]>([]);
  const [selectedTypeId, setSelectedTypeId] = useState<number | null>(null);
  const [reloadKey, setReloadKey] = useState(0);

  // Igual que register.tsx: el setState vive en los callbacks, no en el
  // cuerpo del efecto.
  useEffect(() => {
    let active = true;

    api.payoutAccounts()
      .then((response) => {
        if (!active) return;
        setAccounts(response.data);
        setCanOperate(response.can_operate);
      })
      .catch((caught: unknown) => {
        if (!active) return;
        setError(
          caught instanceof ApiError
            ? caught.message
            : 'No pudimos cargar tus billeteras.',
        );
      });

    api.walletTypes()
      .then((response) => {
        if (!active) return;
        setTypes(response.data);
      })
      .catch(() => {});

    api.billingStatus()
      .then((response) => {
        if (!active) return;
        setBilling(response.data);
        if (typeof response.data.can_operate === 'boolean') {
          setCanOperate(response.data.can_operate);
        }
      })
      .catch(() => {})
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, [reloadKey]);

  function reload() {
    setLoading(true);
    setReloadKey((key) => key + 1);
  }

  if (isLoading || loading) {
    return <LoadingScreen message="Cargando tu billetera…" />;
  }

  if (!user) {
    return <Redirect href="/login" />;
  }

  const countryTypes = types.filter((t) => t.country === country);
  const selectedType = countryTypes.find((t) => t.id === selectedTypeId) ?? null;
  const typeFields: string[] = selectedType?.fields ?? ['holder', 'cbu', 'alias', 'iban', 'bank'];
  const needsRefLabel = typeFields.includes('email')
    ? 'Email de la cuenta'
    : typeFields.includes('phone')
      ? 'Celular asociado'
      : 'Referencia';

  async function handleSubmit() {
    setError(null);
    setNotice(null);
    setFieldErrors({});

    if (!holder.trim()) {
      setError('Ingresá el titular de la cuenta.');
      return;
    }

    if (!selectedType) {
      setError('Elegí el tipo de billetera.');
      return;
    }

    setSubmitting(true);

    try {
      const response = await api.createPayoutAccount({
        wallet_type_id: selectedType.id,
        country,
        label: label.trim() || undefined,
        holder_name: holder.trim(),
        cbu: typeFields.includes('cbu') && cbu.trim() ? cbu.trim() : undefined,
        alias_cbu: typeFields.includes('alias') && alias.trim() ? alias.trim() : undefined,
        account_ref: (typeFields.includes('email') || typeFields.includes('phone')) && accountRef.trim() ? accountRef.trim() : undefined,
        iban: typeFields.includes('iban') && iban.trim() ? iban.trim() : undefined,
        bank: bank.trim() || undefined,
      });
      setNotice(response.message);
      setLabel('');
      setHolder('');
      setCbu('');
      setAlias('');
      setAccountRef('');
      setIban('');
      setBank('');
      reload();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.message);
        setFieldErrors(caught.errors ?? {});
      } else {
        setError('No pudimos guardar la billetera. Intentá de nuevo.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  async function handleAction(
    action: (id: number) => Promise<{ message: string }>,
    id: number,
  ) {
    setError(null);
    setNotice(null);

    try {
      const response = await action(id);
      setNotice(response.message);
      reload();
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos procesar la acción. Intentá de nuevo.',
      );
    }
  }

  return (
    <AuthScreen
      title="Billetera de cobro"
      subtitle="Cargá las cuentas donde querés cobrar. La primera queda como predeterminada para tus liquidaciones."
      footer={
        <View style={styles.footerRow}>
          <Text style={styles.footerText}>¿Terminaste?</Text>
          <Text
            style={styles.link}
            onPress={() => router.replace('/home')}
            accessibilityRole="link"
          >
            Volver al inicio
          </Text>
        </View>
      }
    >
      <View
        style={[styles.banner, canOperate ? styles.bannerOk : styles.bannerAlert]}
        accessibilityRole="alert"
      >
        <Text
          style={[styles.bannerText, canOperate ? styles.bannerTextOk : styles.bannerTextAlert]}
        >
          {canOperate
            ? 'Billetera activa: podés operar y recibir liquidaciones.'
            : 'Agregá al menos una billetera para operar (recibir pedidos, trabajos o repartos).'}
        </Text>
      </View>

      {billing && billing.cash_debt < 0 ? (
        <View style={[styles.banner, styles.bannerAlert]} accessibilityRole="alert">
          <Text style={[styles.bannerText, styles.bannerTextAlert]}>
            Tenés deuda de efectivo: ${Math.abs(billing.cash_debt).toFixed(2)} {billing.currency}.
            Regularizala para seguir operando con efectivo.
          </Text>
        </View>
      ) : null}

      {billing?.membership?.subscribed ? (
        <View style={styles.card}>
          <Text style={styles.cardTitle}>🧾 Suscripción Hogar</Text>
          <Text style={styles.cardSub}>
            {billing.membership.suspended
              ? 'Suspendida por mora. Pagá lo adeudado para reactivarte.'
              : billing.membership.open_invoice
                ? `Cuota pendiente: $${billing.membership.open_invoice.amount.toFixed(2)} ${billing.membership.open_invoice.currency} · vence ${billing.membership.open_invoice.due_date ?? '—'}.`
                : 'Al día. Sin facturas pendientes.'}
          </Text>
        </View>
      ) : null}

      {notice ? (
        <View style={styles.notice} accessibilityRole="alert">
          <Text style={styles.noticeText}>{notice}</Text>
        </View>
      ) : null}

      <ErrorBanner message={error} />

      {accounts.map((account) => (
        <View key={account.id} style={styles.card}>
          <Text style={styles.cardTitle}>
            {(account.wallet_type ?? account.label ?? account.bank ?? account.currency) +
              (account.is_default ? ' · Predeterminada' : '') +
              (account.is_active ? '' : ' · Inactiva')}
          </Text>
          <Text style={styles.cardSub}>
            {[account.holder_name, account.cbu ? `CBU ${account.cbu}` : null, account.alias_cbu, account.account_ref, account.iban ? `IBAN ${account.iban}` : null]
              .filter(Boolean)
              .join(' · ')}
          </Text>
          <View style={styles.actionsRow}>
            {!account.is_default && account.is_active ? (
              <PrimaryButton
                title="Predeterminada"
                variant="ghost"
                onPress={() => void handleAction(api.setDefaultPayoutAccount.bind(api), account.id)}
              />
            ) : null}
            {account.is_active ? (
              <PrimaryButton
                title="Desactivar"
                variant="ghost"
                onPress={() => void handleAction(api.deactivatePayoutAccount.bind(api), account.id)}
              />
            ) : (
              <PrimaryButton
                title="Activar"
                onPress={() => void handleAction(api.reactivatePayoutAccount.bind(api), account.id)}
              />
            )}
          </View>
        </View>
      ))}

      <View style={styles.card}>
        <Text style={styles.cardTitle}>📊 Mi Dinero</Text>
        <Text style={styles.cardSub}>
          Tus liquidaciones, cobros y estado de cuenta, igual que el panel web.
        </Text>
        <PrimaryButton
          title="Ver mis liquidaciones y cobros"
          variant="ghost"
          onPress={() => router.push('/mi-dinero' as never)}
        />
      </View>

      <Text style={styles.sectionTitle}>Agregar billetera</Text>

      <ComboField
        label="País"
        value={country}
        options={[
          { value: 'AR', label: 'Argentina' },
          { value: 'ES', label: 'España' },
        ]}
        onChange={(value) => {
          setCountry(String(value));
          setSelectedTypeId(null);
        }}
        placeholder="Seleccioná el país"
      />

      <ComboField
        label="Tipo de billetera"
        value={selectedTypeId}
        options={countryTypes.map((type) => ({
          value: type.id,
          label: type.name,
        }))}
        onChange={(value) => setSelectedTypeId(typeof value === 'number' ? value : null)}
        placeholder={countryTypes.length ? 'Seleccioná el tipo' : 'Sin tipos para este país'}
        error={fieldErrors.wallet_type_id?.[0] ?? null}
      />

      <TextField
        label="Etiqueta (opcional)"
        value={label}
        onChangeText={setLabel}
        placeholder="Banco X"
        editable={!submitting}
      />
      <TextField
        label="Titular"
        value={holder}
        onChangeText={setHolder}
        error={fieldErrors.holder_name?.[0] ?? null}
        autoCapitalize="words"
        placeholder="Nombre del titular"
        editable={!submitting}
      />
      {typeFields.includes('cbu') ? (
        <TextField
          label="CBU (22 dígitos)"
          value={cbu}
          onChangeText={setCbu}
          error={fieldErrors.cbu?.[0] ?? null}
          keyboardType="number-pad"
          placeholder="Solo si cobrás por banco"
          editable={!submitting}
        />
      ) : null}
      {typeFields.includes('alias') ? (
        <TextField
          label="Alias"
          value={alias}
          onChangeText={setAlias}
          error={fieldErrors.alias_cbu?.[0] ?? null}
          autoCapitalize="none"
          autoCorrect={false}
          placeholder="Opcional"
          editable={!submitting}
        />
      ) : null}
      {(typeFields.includes('email') || typeFields.includes('phone')) ? (
        <TextField
          label={needsRefLabel}
          value={accountRef}
          onChangeText={setAccountRef}
          error={fieldErrors.account_ref?.[0] ?? null}
          autoCapitalize="none"
          autoCorrect={false}
          keyboardType={typeFields.includes('phone') ? 'phone-pad' : 'email-address'}
          placeholder={typeFields.includes('email') ? 'tu@correo.com' : 'Con código de área'}
          editable={!submitting}
        />
      ) : null}
      {typeFields.includes('iban') ? (
        <TextField
          label="IBAN"
          value={iban}
          onChangeText={setIban}
          error={fieldErrors.iban?.[0] ?? null}
          autoCapitalize="characters"
          autoCorrect={false}
          placeholder="Cuenta del exterior"
          editable={!submitting}
        />
      ) : null}
      <TextField
        label="Banco"
        value={bank}
        onChangeText={setBank}
        placeholder="Opcional"
        editable={!submitting}
      />

      <PrimaryButton
        title="Agregar billetera"
        onPress={handleSubmit}
        loading={submitting}
        disabled={submitting}
      />
    </AuthScreen>
  );
}

const styles = StyleSheet.create({
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
  banner: {
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  bannerOk: {
    backgroundColor: colors.successSoft,
    borderColor: '#bbf7d0',
  },
  bannerAlert: {
    backgroundColor: '#fef2f2',
    borderColor: '#fecaca',
  },
  bannerText: {
    fontSize: 14,
    fontWeight: '600',
    lineHeight: 20,
  },
  bannerTextOk: {
    color: colors.success,
  },
  bannerTextAlert: {
    color: colors.danger,
  },
  notice: {
    backgroundColor: colors.successSoft,
    borderColor: '#bbf7d0',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  noticeText: {
    color: colors.success,
    fontSize: 14,
    fontWeight: '600',
    lineHeight: 20,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  cardTitle: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
    marginBottom: spacing.xs,
  },
  cardSub: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 18,
    marginBottom: spacing.sm,
  },
  actionsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  sectionTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
    marginBottom: spacing.md,
    marginTop: spacing.md,
  },
});
