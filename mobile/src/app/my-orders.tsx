import { router, useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
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
import { ComboField, type ComboOption } from '@/components/ComboField';
import { DatePickerModal } from '@/components/DatePickerModal';
import { ErrorBanner } from '@/components/ErrorBanner';
import { OrderHistoryCard, OrderTrackingCard } from '@/components/OrderTrackingCard';
import {
  ACTIVE_STATUSES,
  ORDER_STATUS,
  ORDER_STATUS_ORDER,
  displayDate,
  matchesOrderSearch,
  orderDay,
} from '@/lib/orderTracking';
import { colors, radius, spacing } from '@/theme';
import type { MarketMyOrder } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';
const PER_PAGE = 3;
const POLL_MS = 8000;

type Tab = 'active' | 'history';
type Notice = { text: string; error: boolean };

function errorMessage(caught: unknown): string {
  return caught instanceof ApiError
    ? caught.message
    : 'No pudimos cargar tus pedidos. Revisá tu conexión e intentá de nuevo.';
}

/**
 * "Mis Pedidos" de Mercado: seguimiento en vivo de los pedidos en curso,
 * historial con calificación y repetir pedido. Espeja la vista web
 * `dashboard.sections.mis-pedidos`.
 */
export default function MyOrdersScreen() {
  const [orders, setOrders] = useState<MarketMyOrder[]>([]);
  const [statuses, setStatuses] = useState<string[]>([]);
  const [tab, setTab] = useState<Tab>('active');
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string | null>(null);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [picker, setPicker] = useState<'from' | 'to' | null>(null);
  const [historyPage, setHistoryPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<Notice | null>(null);
  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Carga inicial y cada cambio del filtro de estado (el servidor filtra).
  useEffect(() => {
    let active = true;

    api
      .marketOrders({ status: statusFilter ?? undefined })
      .then((response) => {
        if (!active) {
          return;
        }

        setOrders(response.orders);
        setStatuses(response.statuses);
        setError(null);
      })
      .catch((caught: unknown) => {
        if (active) {
          setError(errorMessage(caught));
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
  }, [statusFilter]);

  // Seguimiento en vivo: refresca solo mientras la pantalla está enfocada.
  useFocusEffect(
    useCallback(() => {
      const timer = setInterval(() => {
        api
          .marketOrders({ status: statusFilter ?? undefined })
          .then((response) => {
            setOrders(response.orders);
            setStatuses(response.statuses);
            setError(null);
          })
          .catch(() => {
            // El siguiente ciclo reintenta sin interrumpir la lectura.
          });
      }, POLL_MS);

      return () => clearInterval(timer);
    }, [statusFilter]),
  );

  useEffect(
    () => () => {
      if (searchTimer.current) {
        clearTimeout(searchTimer.current);
      }
    },
    [],
  );

  async function handleRefresh() {
    setRefreshing(true);

    try {
      const response = await api.marketOrders({ status: statusFilter ?? undefined });
      setOrders(response.orders);
      setStatuses(response.statuses);
      setError(null);
    } catch (caught) {
      setError(errorMessage(caught));
    } finally {
      setRefreshing(false);
    }
  }

  function handleSearchChange(text: string) {
    setSearchInput(text);

    if (searchTimer.current) {
      clearTimeout(searchTimer.current);
    }

    searchTimer.current = setTimeout(() => {
      setSearch(text);
      setHistoryPage(1);
    }, 300);
  }

  function changeTab(next: Tab) {
    setTab(next);
    setHistoryPage(1);
  }

  function changeStatus(next: string | null) {
    if (next === statusFilter) {
      return;
    }

    setLoading(true);
    setError(null);
    setStatusFilter(next);
    setHistoryPage(1);
  }

  function clearFilters() {
    if (searchTimer.current) {
      clearTimeout(searchTimer.current);
    }

    if (statusFilter !== null) {
      setLoading(true);
      setError(null);
    }

    setSearchInput('');
    setSearch('');
    setStatusFilter(null);
    setDateFrom('');
    setDateTo('');
    setHistoryPage(1);
    setNotice(null);
  }

  async function handleRate(orderId: number, stars: number, comment: string) {
    try {
      const response = await api.rateMarketOrder(orderId, {
        stars,
        comment: comment || null,
      });

      setOrders((current) =>
        current.map((order) =>
          order.id === orderId
            ? {
                ...order,
                customer_rated: response.customer_rated,
                customer_rating: response.customer_rating,
                provider_rated: response.provider_rated,
                provider_rating: response.provider_rating,
              }
            : order,
        ),
      );
      setNotice({ text: '¡Gracias por calificar al comercio!', error: false });

      return null;
    } catch (caught) {
      return caught instanceof ApiError
        ? caught.message
        : 'No se pudo registrar la calificación.';
    }
  }

  function handleRepeat(order: MarketMyOrder) {
    if (!order.provider) {
      setNotice({ text: 'Este pedido no tiene comercio para repetir.', error: true });
      return;
    }

    const items = (order.items ?? []).map((item) => ({
      product_id: item.product_id,
      variant_id: item.variant_id,
      quantity: item.quantity,
    }));

    if (items.length === 0) {
      setNotice({ text: 'Este pedido no tiene productos para repetir.', error: true });
      return;
    }

    router.push({
      pathname: '/comercio/[id]',
      params: { id: String(order.provider.id), repeat: JSON.stringify(items) },
    });
  }

  const groups = useMemo(() => {
    const active: MarketMyOrder[] = [];
    const history: MarketMyOrder[] = [];

    for (const order of orders) {
      if (ACTIVE_STATUSES.includes(order.status)) {
        active.push(order);
      } else {
        history.push(order);
      }
    }

    return { active, history };
  }, [orders]);

  const filtered = useMemo(() => {
    let list = tab === 'active' ? groups.active : groups.history;
    const term = search.trim();

    if (term) {
      list = list.filter((order) => matchesOrderSearch(order, term));
    }

    if (tab === 'history' && (dateFrom || dateTo)) {
      list = list.filter((order) => {
        const day = orderDay(order.created_at);

        if (!day) {
          return false;
        }

        return (!dateFrom || day >= dateFrom) && (!dateTo || day <= dateTo);
      });
    }

    return list;
  }, [tab, groups, search, dateFrom, dateTo]);

  const totalPages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
  const page = Math.min(historyPage, totalPages);
  const visible =
    tab === 'history'
      ? filtered.slice((page - 1) * PER_PAGE, page * PER_PAGE)
      : filtered;

  const statusOptions = useMemo<ComboOption<string>[]>(() => {
    const list = statuses.length > 0 ? statuses : ORDER_STATUS_ORDER;

    return list.map((status) => ({
      value: status,
      label: ORDER_STATUS[status]?.label ?? status,
    }));
  }, [statuses]);

  function emptyMessage(): string {
    if (search.trim()) {
      return `No hay pedidos que coincidan con "${search.trim()}".\nRevisá el nombre del local o de un plato.`;
    }

    if (tab === 'history' && (dateFrom || dateTo)) {
      return 'No hay pedidos en ese rango de fechas.';
    }

    if (statusFilter) {
      return 'No hay pedidos con ese estado en esta sección.';
    }

    if (orders.length === 0) {
      return 'Todavía no hiciste pedidos.\nEntrá a Comercios y armá tu primer pedido.';
    }

    if (tab === 'active') {
      return 'No tenés pedidos en curso.\nCuando hagas uno lo vas a ver acá hasta que se entregue.';
    }

    return 'Todavía no hay pedidos en el historial.';
  }

  const activeCount = groups.active.length;
  const historyCount = groups.history.length;

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.topBar}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Volver"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>
      </View>

      <ScrollView
        contentContainerStyle={styles.content}
        keyboardShouldPersistTaps="handled"
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={colors.brand}
            colors={[colors.brand]}
          />
        }
      >
        <View style={styles.header}>
          <Text style={styles.eyebrow}>SEGUIMIENTO DE PEDIDOS</Text>
          <Text style={styles.title}>Mis Pedidos</Text>

          <View style={styles.summaryRow}>
            {activeCount > 0 ? (
              <View style={styles.liveChip}>
                <View style={styles.liveDot} />
                <Text style={styles.liveChipText}>
                  {activeCount} {activeCount === 1 ? 'activo ahora' : 'activos ahora'}
                </Text>
              </View>
            ) : null}

            <Text style={styles.summaryText}>
              {orders.length > 0
                ? `${activeCount} en curso · ${historyCount} en historial · Actualizado recién`
                : 'Tus pedidos y su estado'}
            </Text>
          </View>
        </View>

        <View style={styles.card}>
          <View style={styles.searchRow}>
            <Text style={styles.searchIcon}>🔍</Text>
            <TextInput
              value={searchInput}
              onChangeText={handleSearchChange}
              placeholder="Buscar por plato, local o código..."
              placeholderTextColor={colors.muted}
              autoCorrect={false}
              autoCapitalize="none"
              style={styles.searchInput}
            />
          </View>
        </View>

        <View style={styles.card}>
          <View style={styles.tabsRow}>
            <Pressable
              accessibilityRole="tab"
              accessibilityState={{ selected: tab === 'active' }}
              onPress={() => changeTab('active')}
              style={[styles.tab, tab === 'active' && styles.tabActive]}
            >
              <Text style={[styles.tabText, tab === 'active' && styles.tabTextActive]}>
                En curso
              </Text>
              {activeCount > 0 ? (
                <View style={[styles.tabCount, tab === 'active' && styles.tabCountActive]}>
                  <Text
                    style={[
                      styles.tabCountText,
                      tab === 'active' && styles.tabCountTextActive,
                    ]}
                  >
                    {activeCount}
                  </Text>
                </View>
              ) : null}
            </Pressable>

            <Pressable
              accessibilityRole="tab"
              accessibilityState={{ selected: tab === 'history' }}
              onPress={() => changeTab('history')}
              style={[styles.tab, tab === 'history' && styles.tabActive]}
            >
              <Text style={[styles.tabText, tab === 'history' && styles.tabTextActive]}>
                Historial
              </Text>
              {historyCount > 0 ? (
                <View style={[styles.tabCount, tab === 'history' && styles.tabCountActive]}>
                  <Text
                    style={[
                      styles.tabCountText,
                      tab === 'history' && styles.tabCountTextActive,
                    ]}
                  >
                    {historyCount}
                  </Text>
                </View>
              ) : null}
            </Pressable>
          </View>

          {tab === 'history' ? (
            <View style={styles.rangeRow}>
              <View style={styles.rangeCol}>
                <Text style={styles.rangeLabel}>Desde</Text>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Elegir fecha desde"
                  onPress={() => setPicker('from')}
                  style={styles.dateInput}
                >
                  <Text
                    style={[styles.dateText, !dateFrom && styles.datePlaceholder]}
                    numberOfLines={1}
                  >
                    {displayDate(dateFrom)}
                  </Text>
                </Pressable>
              </View>

              <View style={styles.rangeCol}>
                <Text style={styles.rangeLabel}>Hasta</Text>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Elegir fecha hasta"
                  onPress={() => setPicker('to')}
                  style={styles.dateInput}
                >
                  <Text
                    style={[styles.dateText, !dateTo && styles.datePlaceholder]}
                    numberOfLines={1}
                  >
                    {displayDate(dateTo)}
                  </Text>
                </Pressable>
              </View>
            </View>
          ) : null}

          <View style={styles.filterRow}>
            <View style={styles.statusField}>
              <ComboField
                label="Estado"
                value={statusFilter}
                options={statusOptions}
                onChange={changeStatus}
                allowEmpty
                emptyLabel="Todos los estados"
                onClear={() => changeStatus(null)}
                title="Filtrar por estado"
              />
            </View>

            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Limpiar filtros"
              onPress={clearFilters}
              style={({ pressed }) => [styles.clearButton, pressed && styles.pressed]}
            >
              <Text style={styles.clearButtonText}>✕</Text>
            </Pressable>
          </View>
        </View>

        <ErrorBanner message={error} />

        {notice ? (
          <View
            style={[styles.notice, notice.error ? styles.noticeError : styles.noticeSuccess]}
          >
            <Text
              style={[
                styles.noticeText,
                notice.error ? styles.noticeTextError : styles.noticeTextSuccess,
              ]}
            >
              {notice.text}
            </Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Cerrar aviso"
              onPress={() => setNotice(null)}
            >
              <Text
                style={[
                  styles.noticeClose,
                  notice.error ? styles.noticeTextError : styles.noticeTextSuccess,
                ]}
              >
                ✕
              </Text>
            </Pressable>
          </View>
        ) : null}

        {loading ? (
          <View style={[styles.card, styles.loadingCard]}>
            <ActivityIndicator color={ACCENT} size="large" />
            <Text style={styles.loadingText}>Cargando pedidos…</Text>
          </View>
        ) : visible.length === 0 ? (
          <View style={[styles.card, styles.emptyCard]}>
            <View style={styles.emptyIcon}>
              <Text style={styles.emptyIconText}>🧾</Text>
            </View>
            <Text style={styles.emptyText}>{emptyMessage()}</Text>
            {orders.length === 0 && !search.trim() && !statusFilter ? (
              <Pressable
                accessibilityRole="button"
                onPress={() => router.push('/comercios')}
                style={({ pressed }) => [styles.emptyButton, pressed && styles.pressed]}
              >
                <Text style={styles.emptyButtonText}>🛒 Ir a Comercios</Text>
              </Pressable>
            ) : null}
          </View>
        ) : (
          <View>
            {visible.map((order) =>
              tab === 'history' ? (
                <OrderHistoryCard
                  key={order.id}
                  order={order}
                  onRate={(stars, comment) => handleRate(order.id, stars, comment)}
                  onRepeat={() => handleRepeat(order)}
                />
              ) : (
                <OrderTrackingCard key={order.id} order={order} />
              ),
            )}

            {tab === 'history' && totalPages > 1 ? (
              <View style={styles.pagination}>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Página anterior"
                  disabled={page <= 1}
                  onPress={() => setHistoryPage(Math.max(1, page - 1))}
                  style={[
                    styles.pageButton,
                    page <= 1 && styles.pageButtonDisabled,
                  ]}
                >
                  <Text style={styles.pageArrow}>❮</Text>
                </Pressable>

                <Text style={styles.pageLabel}>
                  Página {page} de {totalPages}
                </Text>

                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Página siguiente"
                  disabled={page >= totalPages}
                  onPress={() => setHistoryPage(Math.min(totalPages, page + 1))}
                  style={[
                    styles.pageButton,
                    page >= totalPages && styles.pageButtonDisabled,
                  ]}
                >
                  <Text style={styles.pageArrow}>❯</Text>
                </Pressable>
              </View>
            ) : null}
          </View>
        )}
      </ScrollView>

      <DatePickerModal
        visible={picker !== null}
        title={picker === 'to' ? 'Fecha hasta' : 'Fecha desde'}
        initial={picker === 'to' ? dateTo : dateFrom}
        onPick={(ymd) => {
          if (picker === 'to') {
            setDateTo(ymd);
          } else {
            setDateFrom(ymd);
          }

          setHistoryPage(1);
          setPicker(null);
        }}
        onClear={() => {
          if (picker === 'to') {
            setDateTo('');
          } else {
            setDateFrom('');
          }

          setHistoryPage(1);
          setPicker(null);
        }}
        onClose={() => setPicker(null)}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.background,
    flex: 1,
  },
  topBar: {
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  backButton: {
    alignSelf: 'flex-start',
  },
  backText: {
    color: colors.brandDark,
    fontSize: 16,
    fontWeight: '600',
  },
  content: {
    paddingBottom: spacing.xxl,
    paddingHorizontal: spacing.lg,
  },
  header: {
    marginBottom: spacing.lg,
  },
  eyebrow: {
    color: ACCENT,
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 1,
  },
  title: {
    color: NAVY,
    fontSize: 24,
    fontWeight: '800',
    letterSpacing: -0.4,
    marginTop: 6,
  },
  summaryRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  liveChip: {
    alignItems: 'center',
    backgroundColor: '#ecfdf5',
    borderColor: '#bbf7d0',
    borderRadius: radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 7,
    paddingHorizontal: 11,
    paddingVertical: 5,
  },
  liveDot: {
    backgroundColor: '#10b981',
    borderRadius: 4,
    height: 8,
    width: 8,
  },
  liveChipText: {
    color: '#047857',
    fontSize: 12,
    fontWeight: '600',
  },
  summaryText: {
    color: '#6b7280',
    fontSize: 13,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  searchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
  },
  searchIcon: {
    fontSize: 14,
  },
  searchInput: {
    color: colors.text,
    flex: 1,
    fontSize: 13,
    paddingVertical: spacing.sm,
  },
  tabsRow: {
    flexDirection: 'row',
    gap: 6,
    paddingVertical: spacing.xs,
  },
  tab: {
    alignItems: 'center',
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: 'transparent',
    flexDirection: 'row',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  tabActive: {
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
  },
  tabText: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  tabTextActive: {
    color: ACCENT,
  },
  tabCount: {
    alignItems: 'center',
    backgroundColor: '#e5e7eb',
    borderRadius: radius.pill,
    justifyContent: 'center',
    minWidth: 20,
    paddingHorizontal: 6,
    paddingVertical: 1,
  },
  tabCountActive: {
    backgroundColor: ACCENT,
  },
  tabCountText: {
    color: '#475569',
    fontSize: 10,
    fontWeight: '800',
  },
  tabCountTextActive: {
    color: colors.surface,
  },
  rangeRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    paddingTop: spacing.md,
  },
  rangeCol: {
    flex: 1,
  },
  rangeLabel: {
    color: '#6b7280',
    fontSize: 11,
    fontWeight: '600',
    marginBottom: 4,
  },
  dateInput: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 9,
  },
  dateText: {
    color: colors.text,
    fontSize: 13,
  },
  datePlaceholder: {
    color: colors.muted,
  },
  filterRow: {
    alignItems: 'flex-end',
    flexDirection: 'row',
    gap: spacing.sm,
    paddingTop: spacing.sm,
  },
  statusField: {
    flex: 1,
  },
  clearButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: 4,
    borderWidth: 1,
    height: 44,
    justifyContent: 'center',
    marginBottom: spacing.lg,
    width: 44,
  },
  clearButtonText: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '700',
  },
  pressed: {
    opacity: 0.75,
  },
  notice: {
    alignItems: 'center',
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    marginBottom: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  noticeError: {
    backgroundColor: colors.dangerSoft,
    borderColor: '#fecaca',
  },
  noticeSuccess: {
    backgroundColor: colors.successSoft,
    borderColor: '#bbf7d0',
  },
  noticeText: {
    flex: 1,
    fontSize: 13,
    lineHeight: 19,
  },
  noticeTextError: {
    color: colors.danger,
  },
  noticeTextSuccess: {
    color: '#047857',
  },
  noticeClose: {
    fontSize: 14,
    fontWeight: '700',
  },
  loadingCard: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
  },
  loadingText: {
    color: colors.muted,
    fontSize: 13,
    marginTop: spacing.md,
  },
  emptyCard: {
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: 44,
  },
  emptyIcon: {
    alignItems: 'center',
    backgroundColor: '#f1f5f9',
    borderRadius: 29,
    height: 58,
    justifyContent: 'center',
    width: 58,
  },
  emptyIconText: {
    fontSize: 26,
  },
  emptyText: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '600',
    lineHeight: 22,
    marginTop: spacing.lg,
    textAlign: 'center',
  },
  emptyButton: {
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
    borderRadius: 4,
    borderWidth: 1,
    marginTop: spacing.lg,
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  emptyButtonText: {
    color: ACCENT,
    fontSize: 13,
    fontWeight: '600',
  },
  pagination: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'center',
    marginTop: spacing.sm,
  },
  pageButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: 4,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  pageButtonDisabled: {
    opacity: 0.4,
  },
  pageArrow: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '700',
  },
  pageLabel: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '600',
  },
});
