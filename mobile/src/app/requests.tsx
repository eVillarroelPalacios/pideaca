import { router } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ComboField } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { RatingModal } from '@/components/RatingModal';
import { colors, radius, spacing } from '@/theme';
import type { ServiceOrderItem, ServiceOrderType } from '@/types/api';

const PAGE_SIZE = 15;

const TYPE_LABELS: Record<string, string> = {
  EMERGENCY: 'Urgencia',
  SCHEDULED: 'Cita',
  QUOTE: 'Presupuesto',
};

const STATUS_COLORS: Record<string, { background: string; foreground: string }> = {
  PENDIENTE: { background: '#fef9c3', foreground: '#854d0e' },
  PRESUPUESTADO: { background: '#e0f2fe', foreground: '#075985' },
  EN_PROCESO: { background: colors.brandSoft, foreground: colors.brandDark },
  COMPLETADO: { background: colors.successSoft, foreground: colors.success },
  CANCELADO: { background: '#f1f5f9', foreground: colors.muted },
};

function statusColors(displayStatus: string): {
  background: string;
  foreground: string;
} {
  return STATUS_COLORS[displayStatus] ?? { background: '#f1f5f9', foreground: colors.muted };
}

function formatDate(iso: string | null): string {
  if (!iso) {
    return '';
  }

  const date = new Date(iso);

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return date.toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export default function RequestsScreen() {
  const [orders, setOrders] = useState<ServiceOrderItem[]>([]);
  const [statuses, setStatuses] = useState<string[]>([]);
  const [statusFilter, setStatusFilter] = useState<string | null>(null);
  const [typeFilter, setTypeFilter] = useState<ServiceOrderType | null>(null);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ratingOrder, setRatingOrder] = useState<ServiceOrderItem | null>(null);
  const [ratingSubmitting, setRatingSubmitting] = useState(false);
  const [ratingError, setRatingError] = useState<string | null>(null);

  const fetchPage = useCallback(
    async (nextPage: number, replace: boolean) => {
      try {
        const response = await api.serviceOrders({
          per_page: PAGE_SIZE,
          page: nextPage,
          ...(statusFilter ? { status: statusFilter } : {}),
          ...(typeFilter ? { order_type: typeFilter } : {}),
        });

        setOrders((current) =>
          replace ? response.data : [...current, ...response.data],
        );
        setPage(response.meta.current_page);
        setLastPage(response.meta.last_page);
        setTotal(response.meta.total);
        setStatuses(response.filters.statuses);
        setError(null);
      } catch (caught) {
        setError(
          caught instanceof ApiError
            ? caught.message
            : 'No pudimos cargar tus solicitudes.',
        );
      }
    },
    [statusFilter, typeFilter],
  );

  useEffect(() => {
    let active = true;

    api
      .serviceOrders({
        per_page: PAGE_SIZE,
        page: 1,
        ...(statusFilter ? { status: statusFilter } : {}),
        ...(typeFilter ? { order_type: typeFilter } : {}),
      })
      .then((response) => {
        if (!active) {
          return;
        }

        setOrders(response.data);
        setPage(response.meta.current_page);
        setLastPage(response.meta.last_page);
        setTotal(response.meta.total);
        setStatuses(response.filters.statuses);
        setError(null);
      })
      .catch((caught: unknown) => {
        if (!active) {
          return;
        }

        setError(
          caught instanceof ApiError
            ? caught.message
            : 'No pudimos cargar tus solicitudes.',
        );
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, [statusFilter, typeFilter]);

  function handleStatusFilter(status: string | null) {
    setLoading(true);
    setStatusFilter(status);
  }

  function handleTypeFilter(type: ServiceOrderType | null) {
    setLoading(true);
    setTypeFilter(type);
  }

  async function handleRefresh() {
    setRefreshing(true);
    await fetchPage(1, true);
    setRefreshing(false);
  }

  async function handleLoadMore() {
    if (loadingMore || page >= lastPage) {
      return;
    }

    setLoadingMore(true);
    await fetchPage(page + 1, false);
    setLoadingMore(false);
  }

  function openRating(order: ServiceOrderItem) {
    setRatingError(null);
    setRatingOrder(order);
  }

  async function handleRate(stars: number, comment: string) {
    if (!ratingOrder) {
      return;
    }

    setRatingSubmitting(true);
    setRatingError(null);

    try {
      const response = await api.rateServiceOrder(ratingOrder.id, stars, comment);

      setOrders((current) =>
        current.map((order) =>
          order.id === ratingOrder.id ? response.data.order : order,
        ),
      );
      setRatingOrder(null);
    } catch (caught) {
      setRatingError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos guardar la calificación.',
      );
    } finally {
      setRatingSubmitting(false);
    }
  }

  if (loading) {
    return <LoadingScreen message="Cargando tus solicitudes…" />;
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>
        <Text style={styles.title}>Mis solicitudes</Text>
        <View style={styles.headerSpacer} />
      </View>

      <View style={styles.actions}>
        <PrimaryButton
          title="+ Nueva solicitud"
          onPress={() => router.push('/request/new')}
        />
      </View>

      <View style={styles.filterBlock}>
        <ComboField
          label="Estado"
          value={statusFilter}
          options={statuses.map((status) => ({
            value: status,
            label: status.charAt(0) + status.slice(1).toLowerCase(),
          }))}
          onChange={handleStatusFilter}
          allowEmpty
          emptyLabel="Todos los estados"
          onClear={() => handleStatusFilter(null)}
          searchPlaceholder="Buscar estado…"
        />
      </View>

      <View style={styles.filters}>
        {(Object.keys(TYPE_LABELS) as ServiceOrderType[]).map((type) => {
          const selected = typeFilter === type;

          return (
            <Pressable
              key={type}
              accessibilityRole="radio"
              accessibilityState={{ selected }}
              onPress={() => handleTypeFilter(selected ? null : type)}
              style={[styles.chip, selected && styles.chipSelected]}
            >
              <Text style={[styles.chipText, selected && styles.chipTextSelected]}>
                {TYPE_LABELS[type]}
              </Text>
            </Pressable>
          );
        })}
      </View>

      <View style={styles.listContainer}>
        <ErrorBanner message={error} />

        <FlatList
          data={orders}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={handleRefresh}
              tintColor={colors.brand}
              colors={[colors.brand]}
            />
          }
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.4}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyTitle}>Sin solicitudes</Text>
              <Text style={styles.emptyText}>
                Todavía no pediste ningún servicio. Tocá “Nueva solicitud” para empezar.
              </Text>
            </View>
          }
          ListFooterComponent={
            <Text style={styles.count}>
              {total === 0
                ? ' '
                : `${orders.length} de ${total}${page < lastPage ? ' · bajá para ver más' : ''}`}
            </Text>
          }
          renderItem={({ item }) => {
            const badge = statusColors(item.display_status);

            return (
              <Pressable
                accessibilityRole="button"
                onPress={() =>
                  router.push({
                    pathname: '/request/[id]',
                    params: { id: String(item.id) },
                  })
                }
                style={({ pressed }) => [styles.card, pressed && styles.cardPressed]}
              >
                <View style={styles.cardHeader}>
                  <Text style={styles.cardType}>
                    {TYPE_LABELS[item.order_type] ?? item.order_type} #{item.id}
                  </Text>
                  <View style={[styles.badge, { backgroundColor: badge.background }]}>
                    <Text style={[styles.badgeText, { color: badge.foreground }]}>
                      {item.status_label}
                    </Text>
                  </View>
                </View>

                <Text style={styles.cardSubgroup} numberOfLines={1}>
                  {item.sub_group.description}
                </Text>

                <Text style={styles.cardMeta} numberOfLines={1}>
                  {item.merchant ? item.merchant.business_name : 'Buscando profesional…'}
                  {formatDate(item.created_at) ? ` · ${formatDate(item.created_at)}` : ''}
                </Text>

                {item.description ? (
                  <Text style={styles.cardDescription} numberOfLines={2}>
                    {item.description}
                  </Text>
                ) : null}

                {item.status === 'COMPLETED' ? (
                  item.customer_rated ? (
                    <Text style={styles.ratedLine}>
                      ★ Ya calificaste
                      {item.customer_rating
                        ? ` (${item.customer_rating.stars}/5)`
                        : ''}
                    </Text>
                  ) : (
                    <Pressable
                      accessibilityRole="button"
                      onPress={(event) => {
                        event.stopPropagation();
                        openRating(item);
                      }}
                      style={styles.rateButton}
                    >
                      <Text style={styles.rateButtonText}>
                        ★ Calificar al prestador
                      </Text>
                    </Pressable>
                  )
                ) : null}
              </Pressable>
            );
          }}
        />
      </View>

      <RatingModal
        key={ratingOrder?.id ?? 'closed'}
        visible={ratingOrder !== null}
        title="Calificar al prestador"
        subtitle={ratingOrder?.merchant?.business_name ?? null}
        submitting={ratingSubmitting}
        error={ratingError}
        onClose={() => {
          if (!ratingSubmitting) {
            setRatingOrder(null);
          }
        }}
        onSubmit={handleRate}
      />
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
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  backButton: {
    minWidth: 70,
  },
  backText: {
    color: colors.brandDark,
    fontSize: 16,
    fontWeight: '600',
  },
  title: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
  },
  headerSpacer: {
    minWidth: 70,
  },
  actions: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md,
  },
  filters: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.sm,
  },
  filterBlock: {
    paddingHorizontal: spacing.lg,
  },
  chip: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  chipSelected: {
    backgroundColor: colors.brandSoft,
    borderColor: colors.brand,
  },
  chipText: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  chipTextSelected: {
    color: colors.brandDark,
  },
  listContainer: {
    flex: 1,
    paddingHorizontal: spacing.lg,
  },
  list: {
    paddingBottom: spacing.xxl,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  cardPressed: {
    opacity: 0.7,
  },
  cardHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.xs,
  },
  cardType: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
  },
  badge: {
    borderRadius: radius.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '700',
  },
  cardSubgroup: {
    color: colors.brandDark,
    fontSize: 14,
    fontWeight: '600',
    marginBottom: spacing.xs,
  },
  cardMeta: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.xs,
  },
  cardDescription: {
    color: colors.text,
    fontSize: 14,
    lineHeight: 20,
  },
  rateButton: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.md,
    marginTop: spacing.md,
    paddingVertical: spacing.md,
  },
  rateButtonText: {
    color: colors.surface,
    fontSize: 14,
    fontWeight: '700',
  },
  ratedLine: {
    color: colors.success,
    fontSize: 13,
    fontWeight: '700',
    marginTop: spacing.md,
  },
  empty: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
  },
  emptyTitle: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '700',
    marginBottom: spacing.xs,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 14,
    textAlign: 'center',
  },
  count: {
    color: colors.muted,
    fontSize: 12,
    paddingVertical: spacing.md,
    textAlign: 'center',
  },
});
