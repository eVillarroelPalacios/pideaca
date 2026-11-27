import { Redirect } from 'expo-router';
import { useEffect, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { ComboField, type ComboOption } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { money } from '@/lib/money';
import { HelpButton } from '@/components/HelpButton';
import { colors, radius, spacing } from '@/theme';
import type { DineroCharge, DineroSettlement } from '@/types/api';

/**
 * Mi Dinero del prestador: solo datos propios. Espeja las pestañas web de
 * liquidaciones, cobros y estado, pero filtradas siempre al usuario
 * autenticado (el servidor jamás expone datos de otros usuarios).
 */
type Tab = 'liquidaciones' | 'cobros' | 'estado';

const TABS: Array<{ key: Tab; label: string }> = [
  { key: 'liquidaciones', label: 'Liquidaciones' },
  { key: 'cobros', label: 'Cobros' },
  { key: 'estado', label: 'Estado' },
];

const STATUS_OPTIONS: ComboOption<string>[] = [
  { value: '', label: 'Todas' },
  { value: 'pending', label: 'Pendientes' },
  { value: 'paid', label: 'Pagadas' },
  { value: 'verified', label: 'Verificados' },
  { value: 'pending_verification', label: 'Por verificar' },
  { value: 'cancelled', label: 'Cancelados' },
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

export default function MiDineroScreen() {
  const { user, isLoading } = useAuth();
  const [tab, setTab] = useState<Tab>('liquidaciones');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [settlements, setSettlements] = useState<DineroSettlement[]>([]);
  const [settleStatus, setSettleStatus] = useState('pending');
  const [charges, setCharges] = useState<DineroCharge[]>([]);
  const [chargeStatus, setChargeStatus] = useState('');
  const [canOperate, setCanOperate] = useState(true);
  const [cashDebt, setCashDebt] = useState(0);
  const [currency, setCurrency] = useState('ARS');

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError(null);

    Promise.all([
      api.mySettlements({ status: 'pending' }).catch(() => null),
      api.myCharges().catch(() => null),
      api.billingStatus().catch(() => null),
    ])
      .then(([s, c, b]) => {
        if (!active) return;
        if (s) setSettlements(s.data);
        if (c) setCharges(c.data);
        if (b) {
          setCanOperate(b.data.can_operate);
          setCashDebt(b.data.cash_debt);
          setCurrency(b.data.currency);
        }
      })
      .catch((caught: unknown) => {
        if (!active) return;
        setError(firstError(caught, 'No pudimos cargar tu dinero.'));
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => {
      active = false;
    };
  }, []);

  if (isLoading || loading) {
    return <LoadingScreen message="Cargando tu dinero…" />;
  }

  if (!user) {
    return <Redirect href="/login" />;
  }

  async function filterSettlements(status: string) {
    setSettleStatus(status);
    setError(null);
    try {
      const res = await api.mySettlements(status ? { status } : {});
      setSettlements(res.data);
    } catch (caught) {
      setError(firstError(caught, 'No pudimos filtrar.'));
    }
  }

  async function filterCharges(status: string) {
    setChargeStatus(status);
    setError(null);
    try {
      const res = await api.myCharges(status ? { status } : {});
      setCharges(res.data);
    } catch (caught) {
      setError(firstError(caught, 'No pudimos filtrar.'));
    }
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <ScrollView contentContainerStyle={styles.container}>
        <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
          <Text style={styles.title}>Mi Dinero</Text>
          <HelpButton screen="mi-dinero" />
        </View>
        <Text style={styles.subtitle}>
          Tus liquidaciones, cobros y estado de cuenta.
        </Text>

        {error ? <ErrorBanner message={error} /> : null}

        {!canOperate ? (
          <View style={styles.alert}>
            <Text style={styles.alertText}>
              Tu cuenta no puede operar por ahora. Revisá tu billetera y deuda.
            </Text>
          </View>
        ) : null}

        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.tabs}>
          {TABS.map((t) => (
            <Pressable
              key={t.key}
              accessibilityRole="tab"
              accessibilityState={{ selected: tab === t.key }}
              onPress={() => setTab(t.key)}
              style={[styles.tab, tab === t.key && styles.tabActive]}
            >
              <Text style={[styles.tabText, tab === t.key && styles.tabTextActive]}>
                {t.label}
              </Text>
            </Pressable>
          ))}
        </ScrollView>

        {tab === 'liquidaciones' ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Mis liquidaciones</Text>
            <ComboField
              label="Estado"
              value={settleStatus}
              options={STATUS_OPTIONS.filter((o) =>
                ['', 'pending', 'paid', 'cancelled'].includes(o.value),
              )}
              onChange={(v) => void filterSettlements(v)}
            />
            {settlements.length === 0 ? (
              <Text style={styles.empty}>Sin liquidaciones.</Text>
            ) : (
              settlements.map((s) => (
                <View key={s.id} style={styles.item}>
                  <Text style={styles.itemTitle}>
                    #{s.id} · Bruto {money(s.gross)} · Neto {money(s.net)} {s.currency}
                  </Text>
                  <Text style={styles.itemSub}>
                    Comisión {money(s.fee)} · {s.status}
                    {s.payout_reference ? ` · Comp. ${s.payout_reference}` : ''}
                  </Text>
                </View>
              ))
            )}
          </View>
        ) : null}

        {tab === 'cobros' ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Mis cobros</Text>
            <ComboField
              label="Estado"
              value={chargeStatus}
              options={STATUS_OPTIONS.filter((o) =>
                ['', 'verified', 'pending_verification', 'cancelled'].includes(o.value),
              )}
              onChange={(v) => void filterCharges(v)}
            />
            {charges.length === 0 ? (
              <Text style={styles.empty}>Sin cobros.</Text>
            ) : (
              charges.map((c) => (
                <View key={c.id} style={styles.item}>
                  <Text style={styles.itemTitle}>
                    #{c.id} · {c.order_type} #{c.order_id} · {money(c.amount)} {c.currency}
                  </Text>
                  <Text style={styles.itemSub}>
                    Comisión {money(c.fee)} · {c.method} · {c.status}
                  </Text>
                </View>
              ))
            )}
          </View>
        ) : null}

        {tab === 'estado' ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Estado de cuenta</Text>
            <Text style={styles.itemSub}>
              Podés operar: {canOperate ? 'Sí' : 'No'}
            </Text>
            <Text style={styles.itemSub}>
              Deuda de efectivo: {money(Math.abs(cashDebt))} {currency}
              {cashDebt >= 0 ? ' (sin deuda)' : ''}
            </Text>
          </View>
        ) : null}
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
  tabs: {
    marginBottom: spacing.lg,
  },
  tab: {
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    marginRight: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  tabActive: {
    backgroundColor: colors.brand,
    borderColor: colors.brand,
  },
  tabText: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  tabTextActive: {
    color: colors.surface,
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
    marginBottom: spacing.md,
  },
  item: {
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    marginBottom: spacing.md,
    paddingBottom: spacing.md,
  },
  itemTitle: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
  itemSub: {
    color: colors.muted,
    fontSize: 13,
    marginTop: spacing.xs,
  },
  empty: {
    color: colors.muted,
    fontSize: 13,
  },
  alert: {
    backgroundColor: colors.dangerSoft,
    borderRadius: radius.md,
    marginBottom: spacing.lg,
    padding: spacing.md,
  },
  alertText: {
    color: colors.danger,
    fontSize: 13,
    fontWeight: '600',
  },
});
