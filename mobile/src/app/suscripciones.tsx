import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { MerchantSubscriptionKpis } from '@/components/MerchantSubscriptionKpis';
import { NewSubscriptionPlanModal } from '@/components/NewSubscriptionPlanModal';
import {
  SubscriptionTabs,
  type SubscriptionTab,
  type SubscriptionTabKey,
} from '@/components/SubscriptionTabs';
import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderCatalogProduct,
  ProviderSubscriptionPayment,
  ProviderSubscriptionPlan,
  ProviderSubscriptionRevenueResponse,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

const TABS: SubscriptionTab[] = [
  { key: 'planes', label: 'Ingresos por plan', icon: '🏷️' },
  { key: 'cobros', label: 'Cobros por confirmar', icon: '💳' },
  { key: 'historial', label: 'Historial por período', icon: '🕒' },
];

const FREQUENCY_LABEL: Record<string, string> = {
  DAILY: 'Diaria',
  WEEKLY: 'Semanal',
  BIWEEKLY: 'Quincenal',
  MONTHLY: 'Mensual',
};

const PAYMENT_STATUS: Record<string, { label: string; color: string; background: string }> = {
  PENDING: { label: 'Pendiente', color: '#b45309', background: '#fef3c7' },
  PAID: { label: 'Pagado', color: '#047857', background: '#d1fae5' },
  FAILED: { label: 'Fallido', color: '#b91c1c', background: '#fee2e2' },
  REFUNDED: { label: 'Reembolsado', color: '#6b7280', background: '#f3f4f6' },
};

type Notice = { text: string; error: boolean } | null;

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

/**
 * Suscripciones del comercio.
 *
 * Espeja `partials/suscripciones-comercio.blade.php`: KPIs de ingreso
 * recurrente, detalle por plan, cobros por confirmar e historial por periodo,
 * mas el alta y edicion de planes con sus productos.
 */
export default function SuscripcionesScreen() {
  const [revenue, setRevenue] = useState<ProviderSubscriptionRevenueResponse | null>(
    null,
  );
  const [plans, setPlans] = useState<ProviderSubscriptionPlan[]>([]);
  const [products, setProducts] = useState<ProviderCatalogProduct[]>([]);
  const [activeTab, setActiveTab] = useState<SubscriptionTabKey>('planes');
  const [months, setMonths] = useState(3);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [historyLoading, setHistoryLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<Notice>(null);

  const [planModalOpen, setPlanModalOpen] = useState(false);
  const [editingPlan, setEditingPlan] = useState<ProviderSubscriptionPlan | null>(null);

  async function fetchData(monthCount: number) {
    const [revenueResponse, plansResponse, catalogResponse] = await Promise.all([
      api.providerSubscriptionRevenue(monthCount),
      api.providerSubscriptionPlans(),
      api.providerCatalogPreview(),
    ]);

    setRevenue(revenueResponse);
    setPlans(plansResponse.plans);
    setProducts(
      catalogResponse.categories.flatMap((category) => category.products),
    );
    setError(null);
  }

  async function load(
    mode: 'initial' | 'refresh' | 'silent' = 'initial',
    monthCount = months,
  ) {
    if (mode === 'refresh') {
      setRefreshing(true);
    } else if (mode === 'initial') {
      setLoading(true);
    }

    try {
      await fetchData(monthCount);
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo cargar el panel de suscripciones.'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }

  useEffect(() => {
    let mounted = true;

    void (async () => {
      try {
        await fetchData(3);
      } catch (caught) {
        if (mounted) {
          setError(
            errorMessage(caught, 'No se pudo cargar el panel de suscripciones.'),
          );
        }
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    })();

    return () => {
      mounted = false;
    };
  }, []);

  async function changeMonths(next: number) {
    if (next === months) {
      return;
    }

    setMonths(next);
    setHistoryLoading(true);

    try {
      await fetchData(next);
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo cargar el historial.'));
    } finally {
      setHistoryLoading(false);
    }
  }

  function openNewPlan() {
    setEditingPlan(null);
    setPlanModalOpen(true);
  }

  function openEditPlan(plan: ProviderSubscriptionPlan) {
    setEditingPlan(plan);
    setPlanModalOpen(true);
  }

  function closePlanModal() {
    setPlanModalOpen(false);
    setEditingPlan(null);
  }

  function handlePlanSaved(message: string) {
    setNotice({ text: message, error: false });
    void load('silent');
  }

  async function updateCharge(payment: ProviderSubscriptionPayment, status: 'PAID' | 'FAILED') {
    try {
      const response = await api.providerSubscriptionUpdatePayment(
        payment.id,
        status === 'PAID'
          ? { status }
          : { status, failure_reason: 'El cliente no pudo pagar.' },
      );

      setNotice({ text: response.message || 'Cobro actualizado.', error: false });
      await load('silent');
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo actualizar el cobro.'));
      await load('silent');
    }
  }

  function confirmCharge(payment: ProviderSubscriptionPayment, status: 'PAID' | 'FAILED') {
    const customer = payment.customer?.name ?? 'este cliente';

    Alert.alert(
      status === 'PAID' ? 'Confirmar pago' : 'Marcar cobro fallido',
      status === 'PAID'
        ? `¿Confirmar el cobro de ${money(payment.total)} de ${customer}?`
        : `¿Marcar como fallido el cobro de ${money(payment.total)} de ${customer}?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: status === 'PAID' ? 'Confirmar' : 'Marcar fallido',
          style: status === 'FAILED' ? 'destructive' : 'default',
          onPress: () => void updateCharge(payment, status),
        },
      ],
    );
  }

  if (loading && !revenue) {
    return (
      <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>
        <LoadingScreen message="Cargando suscripciones…" />
      </SafeAreaView>
    );
  }

  const recurring = revenue?.recurring;
  const cash = revenue?.cash;
  const byPlan = revenue?.recurring.by_plan ?? [];
  const pendingCharges = revenue?.pending_charges ?? [];
  const history = revenue?.history ?? [];

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => void load('refresh')}
            colors={[colors.brand]}
            tintColor={colors.brand}
          />
        }
        keyboardShouldPersistTaps="handled"
      >
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>

        <View style={styles.header}>
          <Text style={styles.title}>Suscripciones</Text>

          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Agregar plan"
            onPress={openNewPlan}
            style={({ pressed }) => [
              styles.addButton,
              pressed && styles.pressed,
            ]}
          >
            <Text style={styles.addButtonText}>+</Text>
          </Pressable>
        </View>

        <ErrorBanner message={error} />

        {notice ? (
          <View
            style={[
              styles.notice,
              notice.error ? styles.noticeError : styles.noticeSuccess,
            ]}
          >
            <Text
              style={[
                styles.noticeText,
                notice.error ? styles.noticeErrorText : styles.noticeSuccessText,
              ]}
            >
              {notice.text}
            </Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Cerrar aviso"
              onPress={() => setNotice(null)}
              style={styles.noticeClose}
            >
              <Text style={styles.noticeCloseText}>✕</Text>
            </Pressable>
          </View>
        ) : null}

        {!revenue ? (
          <View style={styles.emptyCard}>
            <Text style={styles.emptyTitle}>No se pudo cargar el panel</Text>
            <Text style={styles.emptyText}>
              Revisá tu conexión e intentá de nuevo.
            </Text>
            <Pressable
              accessibilityRole="button"
              onPress={() => void load('initial')}
              style={styles.retryButton}
            >
              <Text style={styles.retryText}>↺ Reintentar</Text>
            </Pressable>
          </View>
        ) : (
          <>
            {recurring && cash ? (
              <MerchantSubscriptionKpis recurring={recurring} cash={cash} />
            ) : null}

            <SubscriptionTabs tabs={TABS} active={activeTab} onChange={setActiveTab} />

            {activeTab === 'planes' ? (
              byPlan.length === 0 ? (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>
                    Todavía no tenés suscriptores activos. Cuando se sumen, el
                    detalle aparece acá.
                  </Text>
                </View>
              ) : (
                byPlan.map((plan) => (
                  <View key={plan.plan_id} style={styles.card}>
                    <View style={styles.cardHead}>
                      <Text style={styles.cardTitle} numberOfLines={2}>
                        {plan.title ?? 'Plan'}
                      </Text>
                      <View style={styles.frequencyBadge}>
                        <Text style={styles.frequencyText}>
                          {FREQUENCY_LABEL[plan.frequency ?? ''] ?? plan.frequency}
                        </Text>
                      </View>
                    </View>

                    <View style={styles.metaRow}>
                      <Text style={styles.metaLabel}>Suscriptores</Text>
                      <Text style={styles.metaValue}>{plan.subscribers ?? 0}</Text>
                    </View>

                    <View style={styles.metaRow}>
                      <Text style={styles.metaLabel}>Precio del plan</Text>
                      <Text style={styles.metaValue}>{money(plan.price)}</Text>
                    </View>

                    <View style={styles.metaRow}>
                      <Text style={styles.metaLabel}>Cobra por ciclo</Text>
                      <Text style={styles.metaValue}>{money(plan.charge_amount)}</Text>
                    </View>

                    <View style={styles.metaRow}>
                      <Text style={styles.metaLabel}>Cupos</Text>
                      <Text style={styles.metaValue}>
                        {plan.capacity
                          ? `${plan.available_slots ?? 0} / ${plan.capacity}`
                          : 'Libre'}
                      </Text>
                    </View>

                    <View style={[styles.metaRow, styles.metaRowLast]}>
                      <Text style={styles.metaLabel}>MRR</Text>
                      <Text style={styles.metaStrong}>{money(plan.mrr)}</Text>
                    </View>
                  </View>
                ))
              )
            ) : activeTab === 'cobros' ? (
              pendingCharges.length === 0 ? (
                <View style={styles.emptyCard}>
                  <Text style={styles.emptyText}>
                    No hay cobros esperando confirmación. Los ciclos se generan
                    solos cuando llega la fecha de entrega.
                  </Text>
                </View>
              ) : (
                pendingCharges.map((payment) => {
                  const status = PAYMENT_STATUS[payment.status] ?? {
                    label: payment.status,
                    color: '#334155',
                    background: '#f1f5f9',
                  };

                  return (
                    <View key={payment.id} style={styles.card}>
                      <View style={styles.cardHead}>
                        <View style={styles.customerInfo}>
                          <Text style={styles.cardTitle} numberOfLines={1}>
                            {payment.customer?.name ?? '—'}
                          </Text>
                          {payment.customer?.email ? (
                            <Text style={styles.cardSub} numberOfLines={1}>
                              {payment.customer.email}
                            </Text>
                          ) : null}
                        </View>

                        <View
                          style={[
                            styles.statusBadge,
                            { backgroundColor: status.background },
                          ]}
                        >
                          <Text style={[styles.statusText, { color: status.color }]}>
                            {status.label}
                          </Text>
                        </View>
                      </View>

                      <Text style={styles.cardLine}>
                        Plan: {payment.subscription?.plan ?? '—'}
                      </Text>
                      <Text style={styles.cardLine}>
                        Período: {payment.period_start ?? '—'} → {payment.period_end ?? '—'}
                      </Text>

                      <Text style={styles.chargeTotal}>{money(payment.total)}</Text>

                      <View style={styles.chargeActions}>
                        <Pressable
                          accessibilityRole="button"
                          accessibilityLabel="Confirmar pago"
                          onPress={() => confirmCharge(payment, 'PAID')}
                          style={({ pressed }) => [
                            styles.confirmButton,
                            pressed && styles.pressed,
                          ]}
                        >
                          <Text style={styles.confirmText}>✓ Confirmar pago</Text>
                        </Pressable>

                        <Pressable
                          accessibilityRole="button"
                          accessibilityLabel="Marcar fallido"
                          onPress={() => confirmCharge(payment, 'FAILED')}
                          style={({ pressed }) => [
                            styles.failedButton,
                            pressed && styles.pressed,
                          ]}
                        >
                          <Text style={styles.failedText}>✕ Marcar fallido</Text>
                        </Pressable>
                      </View>
                    </View>
                  );
                })
              )
            ) : (
              <>
                <View style={styles.periodFilter}>
                  <Text style={styles.periodFilterLabel}>Período</Text>

                  <View style={styles.periodOptions}>
                    {[3, 6, 12].map((option) => {
                      const isActive = option === months;

                      return (
                        <Pressable
                          key={option}
                          accessibilityRole="button"
                          accessibilityState={{ selected: isActive }}
                          onPress={() => void changeMonths(option)}
                          style={({ pressed }) => [
                            styles.periodChip,
                            isActive && styles.periodChipActive,
                            pressed && styles.pressed,
                          ]}
                        >
                          <Text
                            style={[
                              styles.periodChipText,
                              isActive && styles.periodChipTextActive,
                            ]}
                          >
                            {option} meses
                          </Text>
                        </Pressable>
                      );
                    })}
                  </View>
                </View>

                {historyLoading ? (
                  <View style={styles.historyLoading}>
                    <ActivityIndicator color={colors.brand} />
                  </View>
                ) : history.length === 0 ? (
                  <View style={styles.emptyCard}>
                    <Text style={styles.emptyText}>
                      Sin cobros registrados en los últimos meses.
                    </Text>
                  </View>
                ) : (
                  history.map((row) => (
                    <View key={row.period} style={styles.card}>
                      <Text style={styles.historyPeriod}>{row.period}</Text>

                      <View style={styles.historyGrid}>
                        <View style={styles.historyItem}>
                          <Text style={styles.metaLabel}>Cobrado</Text>
                          <Text style={[styles.historyValue, styles.historyCollected]}>
                            {money(row.collected)}
                          </Text>
                        </View>

                        <View style={styles.historyItem}>
                          <Text style={styles.metaLabel}>Pendiente</Text>
                          <Text style={[styles.historyValue, styles.historyPending]}>
                            {money(row.pending)}
                          </Text>
                        </View>

                        <View style={styles.historyItem}>
                          <Text style={styles.metaLabel}>Fallidos</Text>
                          <Text style={[styles.historyValue, styles.historyFailed]}>
                            {money(row.failed)}
                          </Text>
                        </View>

                        <View style={styles.historyItem}>
                          <Text style={styles.metaLabel}>Reembolsos</Text>
                          <Text style={styles.historyValue}>{money(row.refunded)}</Text>
                        </View>
                      </View>

                      <Text style={styles.historyCharges}>
                        {row.charges ?? 0} cobro(s)
                      </Text>
                    </View>
                  ))
                )}
              </>
            )}
          </>
        )}
      </ScrollView>

      {planModalOpen ? (
        <NewSubscriptionPlanModal
          key={`plan-${editingPlan?.id ?? 'new'}`}
          visible
          plan={editingPlan}
          plans={plans}
          products={products}
          onClose={closePlanModal}
          onSaved={handlePlanSaved}
          onEditPlan={openEditPlan}
        />
      ) : null}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.background,
    flex: 1,
  },
  scroll: {
    flex: 1,
  },
  content: {
    paddingBottom: spacing.xxl,
    paddingHorizontal: spacing.lg,
  },
  backButton: {
    alignSelf: 'flex-start',
    paddingBottom: spacing.xs,
    paddingTop: spacing.sm,
  },
  backText: {
    color: colors.brandDark,
    fontSize: 16,
    fontWeight: '600',
  },
  header: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
    paddingTop: spacing.xs,
  },
  title: {
    color: NAVY,
    fontSize: 22,
    fontWeight: '800',
  },
  addButton: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.sm,
    height: 38,
    justifyContent: 'center',
    width: 38,
  },
  addButtonText: {
    color: colors.surface,
    fontSize: 24,
    fontWeight: '700',
    lineHeight: 26,
  },
  pressed: {
    opacity: 0.8,
  },
  notice: {
    alignItems: 'flex-start',
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  noticeSuccess: {
    backgroundColor: '#ecfdf5',
    borderColor: '#a7f3d0',
  },
  noticeError: {
    backgroundColor: '#fef2f2',
    borderColor: '#fecaca',
  },
  noticeText: {
    flex: 1,
    fontSize: 12,
    lineHeight: 18,
  },
  noticeSuccessText: {
    color: '#047857',
  },
  noticeErrorText: {
    color: '#b91c1c',
  },
  noticeClose: {
    padding: 2,
  },
  noticeCloseText: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '700',
  },
  emptyCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.xl,
  },
  emptyTitle: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '700',
    marginBottom: spacing.xs,
    textAlign: 'center',
  },
  emptyText: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
  },
  retryButton: {
    backgroundColor: ACCENT,
    borderRadius: radius.sm,
    marginTop: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  retryText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  cardHead: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  cardTitle: {
    color: NAVY,
    flex: 1,
    fontSize: 15,
    fontWeight: '800',
  },
  customerInfo: {
    flex: 1,
  },
  cardSub: {
    color: '#9ca3af',
    fontSize: 11,
    marginTop: 2,
  },
  cardLine: {
    color: colors.muted,
    fontSize: 12,
    lineHeight: 18,
  },
  frequencyBadge: {
    backgroundColor: colors.brandSoft,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  frequencyText: {
    color: colors.brandDark,
    fontSize: 11,
    fontWeight: '700',
  },
  metaRow: {
    alignItems: 'center',
    borderBottomColor: '#f3f4f6',
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 6,
  },
  metaRowLast: {
    borderBottomWidth: 0,
    paddingBottom: 0,
  },
  metaLabel: {
    color: colors.muted,
    fontSize: 12,
  },
  metaValue: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
  },
  metaStrong: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '800',
  },
  statusBadge: {
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '700',
  },
  chargeTotal: {
    color: NAVY,
    fontSize: 18,
    fontWeight: '800',
    marginTop: spacing.sm,
  },
  chargeActions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  confirmButton: {
    alignItems: 'center',
    backgroundColor: '#047857',
    borderRadius: radius.sm,
    flex: 1,
    paddingVertical: spacing.sm,
  },
  confirmText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
  failedButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#fecaca',
    borderRadius: radius.sm,
    borderWidth: 1,
    flex: 1,
    paddingVertical: spacing.sm,
  },
  failedText: {
    color: '#b91c1c',
    fontSize: 12,
    fontWeight: '700',
  },
  periodFilter: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  periodFilterLabel: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '700',
  },
  periodOptions: {
    flexDirection: 'row',
    gap: 6,
  },
  periodChip: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 5,
  },
  periodChipActive: {
    backgroundColor: colors.brandSoft,
    borderColor: colors.brand,
  },
  periodChipText: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '600',
  },
  periodChipTextActive: {
    color: colors.brandDark,
    fontWeight: '800',
  },
  historyLoading: {
    alignItems: 'center',
    paddingVertical: spacing.xl,
  },
  historyPeriod: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '800',
    marginBottom: spacing.sm,
  },
  historyGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    rowGap: spacing.sm,
  },
  historyItem: {
    flexBasis: '50%',
  },
  historyValue: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
    marginTop: 2,
  },
  historyCollected: {
    color: '#047857',
  },
  historyPending: {
    color: '#b45309',
  },
  historyFailed: {
    color: '#b91c1c',
  },
  historyCharges: {
    color: colors.muted,
    fontSize: 11,
    marginTop: spacing.sm,
  },
});
