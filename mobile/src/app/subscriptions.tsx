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
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { NewSubscriptionModal } from '@/components/NewSubscriptionModal';
import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type { MarketSubscription } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

const STATUS_META: Record<
  string,
  { label: string; color: string; background: string }
> = {
  ACTIVE: { label: 'Activa', color: '#047857', background: '#d1fae5' },
  PAUSED: { label: 'Pausada', color: '#b45309', background: '#fef3c7' },
  CANCELLED: { label: 'Cancelada', color: '#6b7280', background: '#f3f4f6' },
  PAYMENT_FAILED: { label: 'Pago pendiente', color: '#b91c1c', background: '#fee2e2' },
};

const FREQUENCY_LABEL: Record<string, string> = {
  DAILY: 'cada día',
  WEEKLY: 'cada semana',
  BIWEEKLY: 'cada dos semanas',
  MONTHLY: 'cada mes',
};

const DAY_LABEL: Record<number, string> = {
  1: 'Lunes',
  2: 'Martes',
  3: 'Miércoles',
  4: 'Jueves',
  5: 'Viernes',
  6: 'Sábado',
  7: 'Domingo',
};

type ClientStatus = 'ACTIVE' | 'PAUSED' | 'CANCELLED';

const ACTION_VERB: Record<ClientStatus, string> = {
  ACTIVE: 'reanudar',
  PAUSED: 'pausar',
  CANCELLED: 'cancelar',
};

function firstError(caught: unknown, fallback: string): string {
  if (caught instanceof ApiError) {
    if (caught.errors) {
      const first = Object.values(caught.errors)[0];

      if (first && first.length > 0) {
        return first[0];
      }
    }

    return caught.message;
  }

  return fallback;
}

function displayDate(ymd: string | null): string {
  if (!ymd) {
    return '—';
  }

  const [y, m, d] = ymd.split('-');

  return y && m && d ? `${d}/${m}/${y}` : ymd;
}

function paymentLabel(method: string | null): string {
  if (!method) {
    return '—';
  }

  if (method === 'cash' || method === 'efectivo') {
    return 'Efectivo';
  }

  if (method === 'transfer' || method === 'transferencia') {
    return 'Transferencia';
  }

  return method;
}

function StatusBadge({ status }: { status: string }) {
  const meta = STATUS_META[status] ?? {
    label: status,
    color: '#334155',
    background: '#f1f5f9',
  };

  return (
    <View style={[styles.badge, { backgroundColor: meta.background }]}>
      <Text style={[styles.badgeText, { color: meta.color }]}>{meta.label}</Text>
    </View>
  );
}

function SubscriptionCard({
  subscription,
  busy,
  onChangeStatus,
}: {
  subscription: MarketSubscription;
  busy: boolean;
  onChangeStatus: (status: ClientStatus) => void;
}) {
  const plan = subscription.plan;
  const canManage =
    subscription.status === 'ACTIVE' ||
    subscription.status === 'PAUSED' ||
    subscription.status === 'PAYMENT_FAILED';

  return (
    <View
      style={[
        styles.card,
        subscription.status === 'ACTIVE' && styles.cardActive,
      ]}
    >
      <View style={styles.cardHead}>
        <View style={styles.cardHeadInfo}>
          <Text style={styles.cardTitle} numberOfLines={2}>
            {plan?.title ?? 'Plan'}
          </Text>
          <Text style={styles.cardProvider} numberOfLines={1}>
            {subscription.provider?.business_name ?? 'Comercio'}
          </Text>
        </View>

        <View style={styles.cardHeadRight}>
          <StatusBadge status={subscription.status} />
          <View style={styles.priceRow}>
            <Text style={styles.price}>{money(plan?.charge_amount ?? 0)}</Text>
            <Text style={styles.frequency} numberOfLines={1}>
              {FREQUENCY_LABEL[plan?.frequency ?? ''] ?? plan?.frequency ?? ''}
            </Text>
          </View>
        </View>
      </View>

      <View style={styles.facts}>
        <View style={styles.fact}>
          <Text style={styles.factLabel}>Próxima entrega</Text>
          <Text style={styles.factValue}>
            {displayDate(subscription.next_delivery_date)}
          </Text>
        </View>
        <View style={styles.fact}>
          <Text style={styles.factLabel}>Día preferido</Text>
          <Text style={styles.factValue}>
            {subscription.preferred_delivery_day
              ? DAY_LABEL[subscription.preferred_delivery_day] ?? '—'
              : 'Sin preferencia'}
          </Text>
        </View>
        <View style={styles.fact}>
          <Text style={styles.factLabel}>Horario</Text>
          <Text style={styles.factValue}>
            {subscription.preferred_delivery_time ?? 'Sin preferencia'}
          </Text>
        </View>
        <View style={styles.fact}>
          <Text style={styles.factLabel}>Pago</Text>
          <Text style={styles.factValue}>
            {paymentLabel(subscription.payment_method)}
          </Text>
        </View>
      </View>

      {subscription.items.length > 0 ? (
        <View style={styles.itemsBox}>
          <Text style={styles.itemsLabel}>Tu distribución</Text>
          <Text style={styles.itemsText}>
            {subscription.items
              .map((item) => `${item.name ?? 'Producto'} x${item.quantity}`)
              .join(' · ')}
          </Text>
        </View>
      ) : null}

      {canManage ? (
        <View style={styles.actions}>
          {busy ? (
            <ActivityIndicator color={ACCENT} style={styles.actionBusy} />
          ) : (
            <>
              {subscription.status === 'ACTIVE' ? (
                <Pressable
                  accessibilityRole="button"
                  onPress={() => onChangeStatus('PAUSED')}
                  style={[styles.actionButton, styles.pauseButton]}
                >
                  <Text style={styles.pauseText}>⏸ Pausar</Text>
                </Pressable>
              ) : (
                <Pressable
                  accessibilityRole="button"
                  onPress={() => onChangeStatus('ACTIVE')}
                  style={[styles.actionButton, styles.resumeButton]}
                >
                  <Text style={styles.resumeText}>▶ Reanudar</Text>
                </Pressable>
              )}

              <Pressable
                accessibilityRole="button"
                onPress={() => onChangeStatus('CANCELLED')}
                style={[styles.actionButton, styles.cancelButton]}
              >
                <Text style={styles.cancelText}>✕ Cancelar</Text>
              </Pressable>
            </>
          )}
        </View>
      ) : null}
    </View>
  );
}

export default function SubscriptionsScreen() {
  const [subscriptions, setSubscriptions] = useState<MarketSubscription[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [creating, setCreating] = useState(false);

  const load = useCallback(async () => {
    try {
      const response = await api.marketSubscriptions();

      setSubscriptions(
        response.subscriptions.filter((item) => item.status !== 'CANCELLED'),
      );
      setError(null);
    } catch (caught) {
      setError(
        firstError(caught, 'No pudimos cargar tus suscripciones.'),
      );
    } finally {
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    let active = true;

    api
      .marketSubscriptions()
      .then((response) => {
        if (!active) {
          return;
        }

        setSubscriptions(
          response.subscriptions.filter((item) => item.status !== 'CANCELLED'),
        );
        setError(null);
      })
      .catch((caught: unknown) => {
        if (active) {
          setError(firstError(caught, 'No pudimos cargar tus suscripciones.'));
        }
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

  function handleRefresh() {
    setRefreshing(true);
    void load();
  }

  function applyStatus(subscription: MarketSubscription, status: ClientStatus) {
    if (busyId !== null) {
      return;
    }

    setBusyId(subscription.id);

    api
      .updateMarketSubscriptionStatus(subscription.id, status)
      .then((response) => {
        setSubscriptions((current) =>
          response.subscription.status === 'CANCELLED'
            ? current.filter((item) => item.id !== subscription.id)
            : current.map((item) =>
                item.id === subscription.id ? response.subscription : item,
              ),
        );
        setError(null);
      })
      .catch((caught: unknown) => {
        setError(
          firstError(caught, 'No pudimos actualizar la suscripción.'),
        );
      })
      .finally(() => {
        setBusyId(null);
      });
  }

  function requestStatus(subscription: MarketSubscription, status: ClientStatus) {
    Alert.alert(
      'Suscripción',
      `¿Querés ${ACTION_VERB[status]} esta suscripción?`,
      [
        { text: 'No', style: 'cancel' },
        {
          text: 'Sí',
          style: status === 'CANCELLED' ? 'destructive' : 'default',
          onPress: () => applyStatus(subscription, status),
        },
      ],
    );
  }

  if (loading) {
    return <LoadingScreen message="Cargando tus suscripciones…" />;
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Volver"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>←</Text>
        </Pressable>

        <View style={styles.headerInfo}>
          <Text style={styles.headerTitle}>Mis Suscripciones</Text>
          <Text style={styles.headerSubtitle}>
            Tus pedidos automáticos y el día que querés recibirlos
          </Text>
        </View>

        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Suscribirme"
          onPress={() => setCreating(true)}
          style={styles.addButton}
        >
          <Text style={styles.addButtonText}>＋</Text>
        </Pressable>
      </View>

      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={ACCENT}
            colors={[ACCENT]}
          />
        }
      >
        <ErrorBanner message={error} />

        {subscriptions.length === 0 ? (
          <View style={styles.empty}>
            <Text style={styles.emptyText}>
              Todavía no tenés suscripciones. Buscá un comercio y armá tu plan.
            </Text>
            <Pressable
              accessibilityRole="button"
              onPress={() => setCreating(true)}
              style={styles.emptyButton}
            >
              <Text style={styles.emptyButtonText}>＋ Elegir plan</Text>
            </Pressable>
          </View>
        ) : (
          subscriptions.map((subscription) => (
            <SubscriptionCard
              key={subscription.id}
              subscription={subscription}
              busy={busyId === subscription.id}
              onChangeStatus={(status) => requestStatus(subscription, status)}
            />
          ))
        )}

        <View style={styles.bottomSpace} />
      </ScrollView>

      {creating ? (
        <NewSubscriptionModal
          onClose={() => setCreating(false)}
          onCreated={(subscription) => {
            setCreating(false);
            setSubscriptions((current) => [
              subscription,
              ...current.filter((item) => item.id !== subscription.id),
            ]);
          }}
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
  header: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  backButton: {
    alignItems: 'center',
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  backText: {
    color: '#374151',
    fontSize: 17,
    fontWeight: '700',
    lineHeight: 19,
  },
  headerInfo: {
    flex: 1,
    minWidth: 0,
  },
  headerTitle: {
    color: NAVY,
    fontSize: 18,
    fontWeight: '800',
  },
  headerSubtitle: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  addButton: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: 4,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  addButtonText: {
    color: colors.surface,
    fontSize: 17,
    fontWeight: '800',
    lineHeight: 19,
  },
  body: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
  },
  empty: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.md,
    borderStyle: 'dashed',
    borderWidth: 1,
    paddingHorizontal: spacing.xl,
    paddingVertical: 40,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 14,
    textAlign: 'center',
  },
  emptyButton: {
    backgroundColor: ACCENT,
    borderRadius: 4,
    marginTop: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  emptyButtonText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '800',
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderLeftColor: '#d1d5db',
    borderLeftWidth: 3,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  cardActive: {
    borderLeftColor: ACCENT,
  },
  cardHead: {
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
  },
  cardHeadInfo: {
    flex: 1,
    minWidth: 0,
  },
  cardTitle: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '800',
  },
  cardProvider: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  cardHeadRight: {
    alignItems: 'flex-end',
    gap: 6,
  },
  badge: {
    borderRadius: radius.pill,
    paddingHorizontal: 9,
    paddingVertical: 3,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '800',
  },
  priceRow: {
    alignItems: 'baseline',
    flexDirection: 'row',
    gap: 4,
  },
  price: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '800',
  },
  frequency: {
    color: colors.muted,
    fontSize: 11,
  },
  facts: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  fact: {
    minWidth: 130,
  },
  factLabel: {
    color: '#9ca3af',
    fontSize: 11,
  },
  factValue: {
    color: NAVY,
    fontSize: 13,
    fontWeight: '600',
    marginTop: 2,
  },
  itemsBox: {
    backgroundColor: '#f9fafb',
    borderColor: '#f3f4f6',
    borderRadius: 6,
    borderWidth: 1,
    marginTop: spacing.md,
    padding: 10,
  },
  itemsLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
    marginBottom: 4,
  },
  itemsText: {
    color: '#374151',
    fontSize: 12,
  },
  actions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: spacing.md,
  },
  actionBusy: {
    alignSelf: 'flex-start',
    marginVertical: 4,
  },
  actionButton: {
    alignItems: 'center',
    borderRadius: 4,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  pauseButton: {
    backgroundColor: colors.surface,
    borderColor: '#fde68a',
  },
  pauseText: {
    color: '#b45309',
    fontSize: 12,
    fontWeight: '700',
  },
  resumeButton: {
    backgroundColor: '#047857',
    borderColor: '#047857',
  },
  resumeText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
  cancelButton: {
    backgroundColor: colors.surface,
    borderColor: '#fecaca',
  },
  cancelText: {
    color: '#b91c1c',
    fontSize: 12,
    fontWeight: '700',
  },
  bottomSpace: {
    height: spacing.xl,
  },
});
