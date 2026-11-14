import { router } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type { DriverStats, DriverWithdrawal } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

const WITHDRAW_METHODS = [
  { value: 'transferencia', label: 'Transferencia' },
  { value: 'efectivo', label: 'Efectivo' },
  { value: 'otro', label: 'Otro' },
];

function errorMessage(caught: unknown, fallback: string): string {
  if (caught instanceof ApiError) {
    const first = caught.errors ? Object.values(caught.errors)[0] : null;

    if (first && first.length > 0) {
      return first[0];
    }

    return caught.message;
  }

  return fallback;
}

function withdrawalStatusLabel(status: DriverWithdrawal['status']): string {
  if (status === 'approved') {
    return 'aprobado';
  }

  if (status === 'rejected') {
    return 'rechazado';
  }

  return 'pendiente de aprobación';
}

function formatDate(value: string | null): string {
  if (!value) {
    return '';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return date.toLocaleDateString();
}

/**
 * Reportes Repartidor: billetera, ganancias, retiros y estadísticas reales
 * de entregas del repartidor (`driverStats` + `driverWithdrawals`).
 * No reutiliza los endpoints de reportes del cliente (`/api/reportes`).
 */
export default function ReportsRepartidorScreen() {
  const [stats, setStats] = useState<DriverStats | null>(null);
  const [balance, setBalance] = useState(0);
  const [minWithdrawal, setMinWithdrawal] = useState(0);
  const [withdrawals, setWithdrawals] = useState<DriverWithdrawal[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [amount, setAmount] = useState('');
  const [method, setMethod] = useState('transferencia');
  const [notes, setNotes] = useState('');
  const [formError, setFormError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const reload = useCallback(async () => {
    try {
      const [statsResponse, walletResponse] = await Promise.all([
        api.driverStats(),
        api.driverWithdrawals(),
      ]);

      setStats(statsResponse.stats);
      setBalance(walletResponse.balance);
      setMinWithdrawal(walletResponse.min_withdrawal);
      setWithdrawals(walletResponse.withdrawals);
      setError(null);
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo cargar tu reporte.'));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    let active = true;

    Promise.all([api.driverStats(), api.driverWithdrawals()])
      .then(([statsResponse, walletResponse]) => {
        if (!active) {
          return;
        }

        setStats(statsResponse.stats);
        setBalance(walletResponse.balance);
        setMinWithdrawal(walletResponse.min_withdrawal);
        setWithdrawals(walletResponse.withdrawals);
        setError(null);
      })
      .catch((caught: unknown) => {
        if (!active) {
          return;
        }

        setError(errorMessage(caught, 'No se pudo cargar tu reporte.'));
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  async function handleRefresh() {
    setRefreshing(true);

    try {
      await reload();
    } finally {
      setRefreshing(false);
    }
  }

  async function submitWithdraw() {
    const parsed = Number.parseFloat(amount.replace(',', '.')) || 0;

    if (parsed <= 0) {
      setFormError('Ingresá un monto válido para retirar.');
      return;
    }

    if (parsed < minWithdrawal) {
      setFormError(`El monto mínimo de retiro es ${money(minWithdrawal)}.`);
      return;
    }

    if (parsed > balance) {
      setFormError('El monto supera tu saldo disponible.');
      return;
    }

    setBusy(true);
    setFormError(null);

    try {
      const response = await api.driverRequestWithdrawal({
        amount: parsed,
        method,
        notes: notes.trim() === '' ? null : notes.trim(),
      });

      setBalance(response.balance);
      setAmount('');
      setNotes('');
      await reload();
      Alert.alert(
        'Solicitud enviada',
        response.message ||
          'El admin va a revisar tu retiro. El monto ya quedó reservado.',
      );
    } catch (caught) {
      setFormError(
        errorMessage(caught, 'No se pudo solicitar el retiro. Intentá de nuevo.'),
      );
    } finally {
      setBusy(false);
    }
  }

  if (loading) {
    return (
      <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
        <View style={styles.loadingWrap}>
          <ActivityIndicator color={ACCENT} size="large" />
          <Text style={styles.loadingText}>Cargando tu reporte…</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.topBar}>
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>
      </View>

      <View style={styles.titleWrap}>
        <Text style={styles.title}>Reportes Repartidor</Text>
        <Text style={styles.subtitle}>
          Tus ganancias, billetera y retiros como repartidor
        </Text>
      </View>

      <ScrollView
        contentContainerStyle={styles.content}
        keyboardShouldPersistTaps="handled"
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => void handleRefresh()}
            tintColor={colors.brand}
            colors={[colors.brand]}
          />
        }
        style={styles.scroll}
      >
        {error ? <Text style={styles.error}>{error}</Text> : null}

        <View style={styles.walletCard}>
          <Text style={styles.walletLabel}>Saldo disponible</Text>
          <Text style={styles.walletValue}>{money(balance)}</Text>
          <Text style={styles.walletHint}>
            Retiro mínimo {money(minWithdrawal)} · El 100% del envío es para
            vos.
          </Text>
        </View>

        <View style={styles.statsGrid}>
          <View style={styles.statCard}>
            <Text style={styles.statLabel}>Ganado hoy</Text>
            <Text style={styles.statValue}>{money(stats?.earnings_today ?? 0)}</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statLabel}>Entregas hoy</Text>
            <Text style={styles.statValue}>{stats?.delivered_today ?? 0}</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statLabel}>Completadas</Text>
            <Text style={styles.statValue}>{stats?.completed ?? 0}</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statLabel}>Aceptación</Text>
            <Text style={styles.statValue}>
              {stats?.acceptance !== null && stats?.acceptance !== undefined
                ? `${Math.round(stats.acceptance)}%`
                : '—'}
            </Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statLabel}>Calificación</Text>
            <Text style={styles.statValue}>
              {stats?.rating !== null && stats?.rating !== undefined
                ? `${stats.rating.toFixed(1)} (${stats.rating_count})`
                : '—'}
            </Text>
          </View>
        </View>

        <View style={styles.withdrawCard}>
          <Text style={styles.cardTitle}>Solicitar retiro</Text>
          <Text style={styles.cardSubtitle}>
            El monto queda reservado hasta que el admin confirme el pago.
          </Text>
          <TextInput
            keyboardType="decimal-pad"
            onChangeText={setAmount}
            placeholder={`Mínimo ${money(minWithdrawal)}`}
            placeholderTextColor="#94a3b8"
            style={styles.input}
            value={amount}
          />
          <View style={styles.methods}>
            {WITHDRAW_METHODS.map((option) => {
              const selected = option.value === method;

              return (
                <Pressable
                  key={option.value}
                  accessibilityRole="radio"
                  accessibilityState={{ selected }}
                  disabled={busy}
                  onPress={() => setMethod(option.value)}
                  style={[styles.method, selected && styles.methodActive]}
                >
                  <Text
                    style={[
                      styles.methodText,
                      selected && styles.methodTextActive,
                    ]}
                  >
                    {option.label}
                  </Text>
                </Pressable>
              );
            })}
          </View>
          <TextInput
            maxLength={500}
            multiline
            numberOfLines={2}
            onChangeText={setNotes}
            placeholder="Notas para el pago (CBU, alias, referencia) — opcional"
            placeholderTextColor="#94a3b8"
            style={styles.notes}
            value={notes}
          />
          {formError ? <Text style={styles.formError}>{formError}</Text> : null}
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Solicitar retiro de saldo"
            disabled={busy || balance <= 0 || balance < minWithdrawal}
            onPress={() => void submitWithdraw()}
            style={({ pressed }) => [
              styles.submit,
              (busy || balance <= 0 || balance < minWithdrawal) &&
                styles.submitDisabled,
              pressed && styles.pressed,
            ]}
          >
            <Text style={styles.submitText}>
              {busy ? 'Enviando…' : 'Solicitar retiro'}
            </Text>
          </Pressable>
        </View>

        <View style={styles.historyCard}>
          <Text style={styles.cardTitle}>Historial de retiros</Text>
          {withdrawals.length === 0 ? (
            <Text style={styles.emptyText}>
              Todavía no solicitaste ningún retiro.
            </Text>
          ) : (
            withdrawals.map((item) => (
              <View key={item.id} style={styles.historyRow}>
                <View style={styles.historyInfo}>
                  <Text style={styles.historyAmount}>{money(item.amount)}</Text>
                  <Text style={styles.historyDetail}>
                    {withdrawalStatusLabel(item.status)}
                    {item.method ? ` · ${item.method}` : ''}
                    {formatDate(item.created_at)
                      ? ` · ${formatDate(item.created_at)}`
                      : ''}
                  </Text>
                </View>
              </View>
            ))
          )}
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
  loadingWrap: {
    alignItems: 'center',
    flex: 1,
    gap: spacing.sm,
    justifyContent: 'center',
  },
  loadingText: {
    color: '#64748b',
    fontSize: 13,
    fontWeight: '600',
  },
  topBar: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
  },
  backButton: {
    alignSelf: 'flex-start',
  },
  backText: {
    color: colors.brandDark,
    fontSize: 16,
    fontWeight: '600',
  },
  titleWrap: {
    paddingBottom: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.xs,
  },
  title: {
    color: NAVY,
    fontSize: 20,
    fontWeight: '800',
  },
  subtitle: {
    color: '#64748b',
    fontSize: 13,
    fontWeight: '500',
    marginTop: 2,
  },
  scroll: {
    flex: 1,
  },
  content: {
    gap: spacing.md,
    paddingBottom: spacing.xl,
    paddingHorizontal: spacing.lg,
  },
  error: {
    color: '#dc2626',
    fontSize: 12.5,
    fontWeight: '700',
  },
  walletCard: {
    backgroundColor: '#FFF7ED',
    borderColor: '#FED7AA',
    borderRadius: radius.lg,
    borderWidth: 1,
    gap: 2,
    padding: spacing.lg,
  },
  walletLabel: {
    color: '#b45309',
    fontSize: 10.5,
    fontWeight: '800',
    letterSpacing: 0.8,
    textTransform: 'uppercase',
  },
  walletValue: {
    color: ACCENT,
    fontSize: 28,
    fontWeight: '800',
    letterSpacing: -0.6,
  },
  walletHint: {
    color: '#64748b',
    fontSize: 11.5,
    fontWeight: '600',
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  statCard: {
    backgroundColor: colors.surface,
    borderColor: '#e2e8f0',
    borderRadius: radius.lg,
    borderWidth: 1,
    flexBasis: '48%',
    flexGrow: 1,
    gap: 2,
    padding: spacing.md,
  },
  statLabel: {
    color: '#94a3b8',
    fontSize: 10.5,
    fontWeight: '800',
    letterSpacing: 0.6,
    textTransform: 'uppercase',
  },
  statValue: {
    color: NAVY,
    fontSize: 17,
    fontWeight: '800',
  },
  withdrawCard: {
    backgroundColor: colors.surface,
    borderColor: '#e2e8f0',
    borderRadius: radius.lg,
    borderWidth: 1,
    gap: spacing.sm,
    padding: spacing.md,
  },
  cardTitle: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '800',
  },
  cardSubtitle: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '500',
  },
  input: {
    backgroundColor: '#f8fafc',
    borderColor: '#cbd5e1',
    borderRadius: radius.md,
    borderWidth: 1,
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  methods: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  method: {
    alignItems: 'center',
    backgroundColor: '#f8fafc',
    borderColor: '#e2e8f0',
    borderRadius: radius.pill,
    borderWidth: 1,
    flex: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 9,
  },
  methodActive: {
    backgroundColor: '#FFF7ED',
    borderColor: '#FED7AA',
  },
  methodText: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '700',
  },
  methodTextActive: {
    color: ACCENT,
  },
  notes: {
    backgroundColor: '#f8fafc',
    borderColor: '#cbd5e1',
    borderRadius: radius.md,
    borderWidth: 1,
    color: colors.text,
    fontSize: 13,
    minHeight: 64,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    textAlignVertical: 'top',
  },
  formError: {
    color: '#dc2626',
    fontSize: 12,
    fontWeight: '700',
  },
  submit: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: radius.pill,
    paddingVertical: 12,
  },
  submitDisabled: {
    backgroundColor: '#e2e8f0',
  },
  submitText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '800',
  },
  pressed: {
    opacity: 0.85,
  },
  historyCard: {
    backgroundColor: colors.surface,
    borderColor: '#e2e8f0',
    borderRadius: radius.lg,
    borderWidth: 1,
    gap: spacing.xs,
    padding: spacing.md,
  },
  historyRow: {
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    flexDirection: 'row',
    paddingVertical: spacing.sm,
  },
  historyInfo: {
    flex: 1,
    gap: 2,
  },
  historyAmount: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '800',
  },
  historyDetail: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
  },
  emptyText: {
    color: '#94a3b8',
    fontSize: 12.5,
    fontWeight: '600',
  },
});
