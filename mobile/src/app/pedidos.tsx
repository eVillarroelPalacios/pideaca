import { router, useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
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
import { ComboField } from '@/components/ComboField';
import { DatePickerModal } from '@/components/DatePickerModal';
import { DispatchOrderCard } from '@/components/DispatchOrderCard';
import { DispatchTrustedCard } from '@/components/DispatchTrustedCard';
import { ErrorBanner } from '@/components/ErrorBanner';
import { RatingModal } from '@/components/RatingModal';
import { ORDER_STATUS_ORDER, displayDate, statusInfo } from '@/lib/orderTracking';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderDispatchOrder,
  ProviderDispatchOrdersResponse,
  TrustedDriverRow,
  TrustedDriversResponse,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';
const PER_PAGE = 10;
const POLL_MS = 8000;

type Tab = 'today' | 'history';
type Notice = { text: string; error: boolean } | null;
type PickerTarget = 'from' | 'to';

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

function clockLabel(date: Date): string {
  return date.toLocaleTimeString('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
  });
}

/** Mantiene el switch del despacho alineado con lo que informa el sondeo. */
function applyTrustedFlag(
  current: TrustedDriversResponse | null,
  enabled: boolean,
): TrustedDriversResponse | null {
  if (!current || current.provider.trusted_drivers_enabled === enabled) {
    return current;
  }

  return {
    ...current,
    provider: { ...current.provider, trusted_drivers_enabled: enabled },
  };
}

/**
 * Control de Pedidos / Centro de Despacho del comercio.
 *
 * Espeja `dashboard.sections.pedidos`: modalidad de despacho, lista de
 * confianza, KPIs, filtros, tarjetas de pedido con acciones y calificacion.
 * Los datos se refrescan cada 8 s mientras la pantalla está enfocada.
 */
export default function PedidosScreen() {
  const [data, setData] = useState<ProviderDispatchOrdersResponse | null>(null);
  const [trusted, setTrusted] = useState<TrustedDriversResponse | null>(null);
  const [tab, setTab] = useState<Tab>('today');
  const [statusFilter, setStatusFilter] = useState<string | null>(null);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [picker, setPicker] = useState<PickerTarget | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [syncedAt, setSyncedAt] = useState<Date | null>(null);

  const [collapsed, setCollapsed] = useState<Record<number, boolean>>({});
  const [busyOrderId, setBusyOrderId] = useState<number | null>(null);
  const [rateOrder, setRateOrder] = useState<ProviderDispatchOrder | null>(null);
  const [ratingSubmitting, setRatingSubmitting] = useState(false);
  const [ratingError, setRatingError] = useState<string | null>(null);

  const [trustedLoading, setTrustedLoading] = useState(true);
  const [togglingTrusted, setTogglingTrusted] = useState(false);
  const [addingTrusted, setAddingTrusted] = useState(false);
  const [removingId, setRemovingId] = useState<number | null>(null);
  const [candidateId, setCandidateId] = useState<number | null>(null);
  const [trustedNotice, setTrustedNotice] = useState<Notice>(null);

  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const loadOrders = useCallback(
    async (options: { silent?: boolean } = {}) => {
      try {
        const response = await api.providerDispatchOrders({
          tab,
          status: statusFilter ?? undefined,
          search: search || undefined,
          date_from: tab === 'history' ? dateFrom || undefined : undefined,
          date_to: tab === 'history' ? dateTo || undefined : undefined,
          page,
          per_page: PER_PAGE,
        });

        setData(response);
        setSyncedAt(new Date());
        setError(null);
        setTrusted((current) =>
          applyTrustedFlag(current, response.provider.trusted_drivers_enabled),
        );

        // Si el sondeo deja la página fuera de rango (se borraron pedidos),
        // volvemos a la última disponible para no mostrar una lista vacía.
        if (response.pagination.current_page > response.pagination.last_page) {
          setPage(response.pagination.last_page);
        }
      } catch (caught) {
        if (!options.silent) {
          setError(errorMessage(caught, 'No se pudieron cargar los pedidos.'));
        }
      }
    },
    [tab, statusFilter, search, dateFrom, dateTo, page],
  );

  const loadTrusted = useCallback(async (options: { silent?: boolean } = {}) => {
    try {
      const response = await api.trustedDrivers();
      setTrusted(response);
    } catch (caught) {
      if (!options.silent) {
        setTrustedNotice({
          text: errorMessage(caught, 'No se pudo cargar la lista de confianza.'),
          error: true,
        });
      }
    } finally {
      setTrustedLoading(false);
    }
  }, []);

  // Carga inicial y cada cambio de filtros/página.
  useEffect(() => {
    let active = true;

    api
      .providerDispatchOrders({
        tab,
        status: statusFilter ?? undefined,
        search: search || undefined,
        date_from: tab === 'history' ? dateFrom || undefined : undefined,
        date_to: tab === 'history' ? dateTo || undefined : undefined,
        page,
        per_page: PER_PAGE,
      })
      .then((response) => {
        if (!active) {
          return;
        }

        setData(response);
        setSyncedAt(new Date());
        setError(null);
        setTrusted((current) =>
          applyTrustedFlag(current, response.provider.trusted_drivers_enabled),
        );

        if (response.pagination.current_page > response.pagination.last_page) {
          setPage(response.pagination.last_page);
        }
      })
      .catch((caught: unknown) => {
        if (active) {
          setError(errorMessage(caught, 'No se pudieron cargar los pedidos.'));
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
  }, [tab, statusFilter, search, dateFrom, dateTo, page]);

  // La lista de confianza se consulta al abrir la pantalla y tras cada acción.
  useEffect(() => {
    let active = true;

    api
      .trustedDrivers()
      .then((response) => {
        if (active) {
          setTrusted(response);
        }
      })
      .catch((caught: unknown) => {
        if (active) {
          setTrustedNotice({
            text: errorMessage(caught, 'No se pudo cargar la lista de confianza.'),
            error: true,
          });
        }
      })
      .finally(() => {
        if (active) {
          setTrustedLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  // Refresco automático mientras la pantalla está enfocada.
  useFocusEffect(
    useCallback(() => {
      const timer = setInterval(() => {
        void loadOrders({ silent: true });
      }, POLL_MS);

      return () => clearInterval(timer);
    }, [loadOrders]),
  );

  useEffect(
    () => () => {
      if (searchTimer.current) {
        clearTimeout(searchTimer.current);
      }
    },
    [],
  );

  const statuses = useMemo(() => data?.statuses ?? ORDER_STATUS_ORDER, [data]);

  const statusOptions = useMemo(
    () =>
      statuses.map((status) => ({
        value: status,
        label: statusInfo(status).label,
      })),
    [statuses],
  );

  const counts = data?.counts ?? { today: 0, history: 0, pending: 0, in_preparation: 0 };
  const orders = data?.orders ?? [];
  const pagination = data?.pagination ?? null;
  const businessName = data?.provider.business_name ?? 'Tu comercio';
  const trustedEnabled = data?.provider.trusted_drivers_enabled ?? false;

  const filtersActive = Boolean(statusFilter || search || dateFrom || dateTo);

  function emptyState(): { title: string; text: string } {
    if (tab === 'history' && (dateFrom || dateTo)) {
      return {
        title: 'No se encontraron pedidos',
        text: 'No hay pedidos en ese rango de fechas.\nProbá con otro rango o restablecé los filtros.',
      };
    }

    if (search) {
      return {
        title: 'No se encontraron pedidos',
        text: `No hay pedidos que coincidan con "${search}".\nRevisá el número de pedido o el nombre del cliente.`,
      };
    }

    if (statusFilter) {
      return {
        title: 'No se encontraron pedidos',
        text: 'No hay pedidos con ese estado en esta sección.',
      };
    }

    if (!counts.today && !counts.history) {
      return {
        title: 'Todavía no recibiste pedidos',
        text: 'Todavía no recibiste pedidos.\nCuando un cliente coloque su pedido va a aparecer acá.',
      };
    }

    if (tab === 'today') {
      return {
        title: 'Sin pedidos para hoy',
        text: 'No tenés pedidos hoy.\nPasá por la pestaña de historial para ver los anteriores.',
      };
    }

    return {
      title: 'El historial está vacío',
      text: 'Todavía no hay pedidos en el historial.',
    };
  }

  async function handleRefresh() {
    setRefreshing(true);

    try {
      await Promise.all([loadOrders({ silent: true }), loadTrusted({ silent: true })]);
    } finally {
      setRefreshing(false);
    }
  }

  function changeTab(next: Tab) {
    if (next === tab) {
      return;
    }

    setLoading(true);
    setError(null);
    setTab(next);
    setPage(1);
  }

  function changeStatus(next: string | null) {
    if (next === statusFilter) {
      return;
    }

    setLoading(true);
    setError(null);
    setStatusFilter(next);
    setPage(1);
  }

  function changeSearch(text: string) {
    setSearchInput(text);

    if (searchTimer.current) {
      clearTimeout(searchTimer.current);
    }

    searchTimer.current = setTimeout(() => {
      setLoading(true);
      setSearch(text.trim());
      setPage(1);
    }, 350);
  }

  function clearFilters() {
    if (searchTimer.current) {
      clearTimeout(searchTimer.current);
    }

    setLoading(true);
    setError(null);
    setSearchInput('');
    setSearch('');
    setStatusFilter(null);
    setDateFrom('');
    setDateTo('');
    setPage(1);
  }

  function pickDate(ymd: string) {
    setLoading(true);
    setError(null);

    if (picker === 'from') {
      setDateFrom(ymd);
    } else {
      setDateTo(ymd);
    }

    setPicker(null);
    setPage(1);
  }

  function clearDate(target: PickerTarget) {
    setLoading(true);
    setError(null);

    if (target === 'from') {
      setDateFrom('');
    } else {
      setDateTo('');
    }

    setPicker(null);
    setPage(1);
  }

  function goPage(delta: number) {
    if (!pagination) {
      return;
    }

    const next = Math.min(Math.max(1, pagination.current_page + delta), pagination.last_page);

    if (next === pagination.current_page) {
      return;
    }

    setLoading(true);
    setError(null);
    setPage(next);
  }

  async function changeOrderStatus(
    order: ProviderDispatchOrder,
    status: string,
  ) {
    setBusyOrderId(order.id);
    setError(null);

    try {
      await api.providerDispatchUpdateStatus(order.id, { status });
      await loadOrders({ silent: true });
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo actualizar el pedido.'));
    } finally {
      setBusyOrderId(null);
    }
  }

  function handleCancel(order: ProviderDispatchOrder) {
    Alert.alert('Cancelar pedido', `¿Cancelar el pedido ${order.order_number}?`, [
      { text: 'Volver', style: 'cancel' },
      {
        text: 'Cancelar pedido',
        style: 'destructive',
        onPress: () => void changeOrderStatus(order, 'cancelled'),
      },
    ]);
  }

  function handleSendToPool(order: ProviderDispatchOrder) {
    Alert.alert(
      'Enviar al pool general',
      '¿Enviar este pedido al pool general? Cualquier repartidor podrá verlo y tomarlo.',
      [
        { text: 'Volver', style: 'cancel' },
        {
          text: 'Enviar',
          onPress: () => {
            setBusyOrderId(order.id);
            setError(null);

            api
              .providerDispatchSendToPool(order.id)
              .then(() => loadOrders({ silent: true }))
              .catch((caught: unknown) => {
                setError(
                  errorMessage(caught, 'No se pudo enviar el pedido al pool general.'),
                );
              })
              .finally(() => setBusyOrderId(null));
          },
        },
      ],
    );
  }

  async function submitRating(stars: number, comment: string) {
    if (!rateOrder) {
      return;
    }

    setRatingSubmitting(true);
    setRatingError(null);

    try {
      await api.providerDispatchRateOrder(rateOrder.id, {
        stars,
        comment: comment || null,
      });
      setRateOrder(null);
      await loadOrders({ silent: true });
    } catch (caught) {
      setRatingError(errorMessage(caught, 'No se pudo registrar la calificación.'));
    } finally {
      setRatingSubmitting(false);
    }
  }

  async function toggleTrusted(enabled: boolean) {
    setTogglingTrusted(true);
    setTrustedNotice(null);

    try {
      const response = await api.updateTrustedDriversSettings(enabled);

      setTrusted((current) =>
        current
          ? {
              ...current,
              provider: {
                ...current.provider,
                trusted_drivers_enabled: response.provider.trusted_drivers_enabled,
              },
            }
          : current,
      );
      setTrustedNotice({ text: response.message, error: false });
      await loadOrders({ silent: true });
    } catch (caught) {
      setTrustedNotice({
        text: errorMessage(caught, 'No se pudo actualizar la modalidad.'),
        error: true,
      });
    } finally {
      setTogglingTrusted(false);
    }
  }

  async function addTrusted() {
    if (candidateId === null) {
      setTrustedNotice({ text: 'Seleccioná un repartidor para agregar.', error: true });
      return;
    }

    setAddingTrusted(true);
    setTrustedNotice(null);

    try {
      const response = await api.addTrustedDriver(candidateId);

      setTrusted((current) =>
        current
          ? {
              ...current,
              drivers: response.drivers,
              candidates: response.candidates,
            }
          : current,
      );
      setCandidateId(null);
      setTrustedNotice({ text: 'Repartidor agregado.', error: false });
      await loadOrders({ silent: true });
    } catch (caught) {
      setTrustedNotice({
        text: errorMessage(caught, 'No se pudo agregar el repartidor.'),
        error: true,
      });
    } finally {
      setAddingTrusted(false);
    }
  }

  async function removeTrusted(driver: TrustedDriverRow, disableMode: boolean) {
    setRemovingId(driver.id);
    setTrustedNotice(null);

    try {
      let response: TrustedDriversResponse;

      try {
        response = await api.removeTrustedDriver(driver.id, disableMode);
      } catch (caught) {
        if (caught instanceof ApiError && caught.code === 'last_active_driver') {
          setRemovingId(null);
          Alert.alert(
            'Último repartidor activo',
            `${caught.message}\n\n¿Desactivar la modalidad y quitar a ${
              driver.name ?? 'este repartidor'
            }? Los pedidos volverán al pool general.`,
            [
              { text: 'Volver', style: 'cancel' },
              {
                text: 'Desactivar y quitar',
                style: 'destructive',
                onPress: () => void removeTrusted(driver, true),
              },
            ],
          );
          return;
        }

        throw caught;
      }

      setTrusted(response);

      if (disableMode) {
        setTrustedNotice({
          text: 'Modalidad desactivada y repartidor quitado. Los pedidos vuelven al pool general.',
          error: false,
        });
      } else {
        setTrustedNotice({ text: 'Repartidor quitado correctamente.', error: false });
      }

      await loadOrders({ silent: true });
    } catch (caught) {
      setTrustedNotice({
        text: errorMessage(caught, 'No se pudo quitar el repartidor.'),
        error: true,
      });
    } finally {
      setRemovingId(null);
    }
  }

  function confirmRemove(driver: TrustedDriverRow) {
    const drivers = trusted?.drivers ?? [];
    const activeCount = drivers.filter((item) => item.status === 'active').length;
    const mustDisable =
      trustedEnabled && driver.status === 'active' && activeCount <= 1;

    const question = `¿Quitar a ${driver.name ?? 'este repartidor'} de tus repartidores de confianza?`;
    const extra = mustDisable
      ? '\n\nEs el último repartidor y la modalidad está activa. Al quitarlo también se desactivará la modalidad y los pedidos volverán al pool general.'
      : '';

    Alert.alert('Quitar repartidor', question + extra, [
      { text: 'Volver', style: 'cancel' },
      {
        text: mustDisable ? 'Quitar y desactivar' : 'Quitar',
        style: 'destructive',
        onPress: () => void removeTrusted(driver, mustDisable),
      },
    ]);
  }

  const refreshingControl = (
    <RefreshControl
      refreshing={refreshing}
      onRefresh={() => void handleRefresh()}
      colors={[colors.brand]}
      tintColor={colors.brand}
    />
  );

  const empty = emptyState();

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.content}
        refreshControl={refreshingControl}
        keyboardShouldPersistTaps="handled"
      >
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>

        <View style={styles.headerCard}>
          <View style={styles.headerRow}>
            <View style={styles.truckIcon}>
              <Text style={styles.truckIconText}>🚚</Text>
            </View>
            <Text style={styles.headerTitle}>Centro de Despacho</Text>
            <Text style={styles.headerDivider}>|</Text>
            <Text style={styles.headerBusiness}>{businessName}</Text>
          </View>

          <View style={styles.headerRow}>
            <Text style={styles.syncText}>
              🕒 Sincronizado {syncedAt ? `a las ${clockLabel(syncedAt)}` : '—'}
            </Text>
          </View>

          <View style={styles.chipsRow}>
            <View style={styles.chip}>
              <View style={[styles.chipDot, { backgroundColor: '#f59e0b' }]} />
              <Text style={styles.chipText}>{counts.pending} pendientes</Text>
            </View>
            <View style={styles.chip}>
              <View style={[styles.chipDot, { backgroundColor: '#2563eb' }]} />
              <Text style={styles.chipText}>{counts.in_preparation} en cocina</Text>
            </View>
          </View>
        </View>

        <View style={styles.titleBlock}>
          <Text style={styles.title}>Control de Pedidos</Text>
          <Text style={styles.subtitle}>
            Supervisá el flujo de preparación, la asignación de repartidores y las entregas en
            tiempo real.
          </Text>
        </View>

        <View style={styles.sectionGap}>
          <DispatchTrustedCard
            data={trusted}
            loading={trustedLoading}
            toggling={togglingTrusted}
            adding={addingTrusted}
            removingId={removingId}
            candidateId={candidateId}
            notice={trustedNotice}
            onCandidateChange={setCandidateId}
            onCandidateClear={() => setCandidateId(null)}
            onToggle={(value) => void toggleTrusted(value)}
            onAdd={() => void addTrusted()}
            onRemove={confirmRemove}
          />
        </View>

        <View style={styles.kpiRow}>
          <View style={styles.kpi}>
            <Text style={styles.kpiLabel}>Hoy</Text>
            <Text style={styles.kpiValue}>{counts.today}</Text>
          </View>
          <View style={styles.kpi}>
            <Text style={styles.kpiLabel}>Historial</Text>
            <Text style={styles.kpiValue}>{counts.history}</Text>
          </View>
          <View style={[styles.kpi, styles.kpiAlert]}>
            <Text style={[styles.kpiLabel, styles.kpiLabelAlert]}>Pendientes</Text>
            <Text style={[styles.kpiValue, styles.kpiValueAlert]}>{counts.pending}</Text>
          </View>
        </View>

        <View style={styles.filtersCard}>
          <View style={styles.tabsRow}>
            <Pressable
              accessibilityRole="button"
              onPress={() => changeTab('today')}
              style={[styles.tab, tab === 'today' && styles.tabActive]}
            >
              <Text style={[styles.tabText, tab === 'today' && styles.tabTextActive]}>
                📅 Pedidos de hoy
              </Text>
              {counts.today > 0 ? (
                <View style={[styles.tabCount, tab === 'today' && styles.tabCountActive]}>
                  <Text
                    style={[
                      styles.tabCountText,
                      tab === 'today' && styles.tabCountTextActive,
                    ]}
                  >
                    {counts.today}
                  </Text>
                </View>
              ) : null}
            </Pressable>

            <Pressable
              accessibilityRole="button"
              onPress={() => changeTab('history')}
              style={[styles.tab, tab === 'history' && styles.tabActive]}
            >
              <Text style={[styles.tabText, tab === 'history' && styles.tabTextActive]}>
                Historial
              </Text>
              {counts.history > 0 ? (
                <View style={[styles.tabCount, tab === 'history' && styles.tabCountActive]}>
                  <Text
                    style={[
                      styles.tabCountText,
                      tab === 'history' && styles.tabCountTextActive,
                    ]}
                  >
                    {counts.history}
                  </Text>
                </View>
              ) : null}
            </Pressable>
          </View>

          {tab === 'history' ? (
            <View style={styles.rangeRow}>
              <Pressable
                accessibilityRole="button"
                onPress={() => setPicker('from')}
                style={styles.dateButton}
              >
                <Text style={styles.dateLabel}>Desde</Text>
                <Text style={styles.dateValue}>
                  {dateFrom ? displayDate(dateFrom) : 'Elegir fecha'}
                </Text>
              </Pressable>

              <Pressable
                accessibilityRole="button"
                onPress={() => setPicker('to')}
                style={styles.dateButton}
              >
                <Text style={styles.dateLabel}>Hasta</Text>
                <Text style={styles.dateValue}>
                  {dateTo ? displayDate(dateTo) : 'Elegir fecha'}
                </Text>
              </Pressable>

              {dateFrom || dateTo ? (
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Limpiar rango de fechas"
                  onPress={() => {
                    setLoading(true);
                    setError(null);
                    setDateFrom('');
                    setDateTo('');
                    setPage(1);
                  }}
                  style={styles.clearDateButton}
                >
                  <Text style={styles.clearDateText}>✕</Text>
                </Pressable>
              ) : null}
            </View>
          ) : null}

          <View style={styles.filterDivider} />

          <View style={styles.filterRow}>
            <View style={styles.statusCombo}>
              <ComboField<string>
                label="Estado"
                title="Filtrar por estado"
                value={statusFilter}
                options={statusOptions}
                onChange={changeStatus}
                allowEmpty
                emptyLabel="Todos los estados"
                onClear={() => changeStatus(null)}
                placeholder="Todos los estados"
              />
            </View>

            <TextInput
              value={searchInput}
              onChangeText={changeSearch}
              placeholder="Buscar pedido o cliente..."
              placeholderTextColor="#94a3b8"
              autoCapitalize="none"
              autoCorrect={false}
              style={styles.searchInput}
            />

            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Restablecer filtros"
              onPress={clearFilters}
              style={styles.resetButton}
            >
              <Text style={styles.resetText}>↺ Restablecer</Text>
            </Pressable>
          </View>
        </View>

        <ErrorBanner message={error} />

        {loading ? (
          <View style={styles.loadingCard}>
            <ActivityIndicator color={colors.brand} />
            <Text style={styles.loadingText}>Cargando pedidos…</Text>
          </View>
        ) : orders.length === 0 ? (
          <View style={styles.emptyCard}>
            <Text style={styles.emptyIcon}>📦</Text>
            <Text style={styles.emptyTitle}>{empty.title}</Text>
            <Text style={styles.emptyText}>{empty.text}</Text>
            {filtersActive ? (
              <Pressable
                accessibilityRole="button"
                onPress={clearFilters}
                style={styles.emptyReset}
              >
                <Text style={styles.emptyResetText}>↺ Restablecer Filtros</Text>
              </Pressable>
            ) : null}
          </View>
        ) : (
          <>
            {orders.map((order, index) => (
              <DispatchOrderCard
                key={order.id}
                order={order}
                index={(pagination ? (pagination.current_page - 1) * PER_PAGE : 0) + index + 1}
                trustedEnabled={trustedEnabled}
                collapsed={Boolean(collapsed[order.id])}
                busy={busyOrderId === order.id}
                onToggle={() =>
                  setCollapsed((current) => ({
                    ...current,
                    [order.id]: !current[order.id],
                  }))
                }
                onNextStatus={(item, status) => void changeOrderStatus(item, status)}
                onCancel={handleCancel}
                onSendToPool={handleSendToPool}
                onRate={(item) => {
                  setRatingError(null);
                  setRateOrder(item);
                }}
              />
            ))}

            {pagination && pagination.last_page > 1 ? (
              <View style={styles.pagination}>
                <Pressable
                  accessibilityRole="button"
                  disabled={pagination.current_page <= 1}
                  onPress={() => goPage(-1)}
                  style={[
                    styles.pageButton,
                    pagination.current_page <= 1 && styles.disabled,
                  ]}
                >
                  <Text style={styles.pageButtonText}>‹</Text>
                </Pressable>

                <Text style={styles.pageLabel}>
                  Página {pagination.current_page} de {pagination.last_page}
                </Text>

                <Pressable
                  accessibilityRole="button"
                  disabled={pagination.current_page >= pagination.last_page}
                  onPress={() => goPage(1)}
                  style={[
                    styles.pageButton,
                    pagination.current_page >= pagination.last_page && styles.disabled,
                  ]}
                >
                  <Text style={styles.pageButtonText}>›</Text>
                </Pressable>
              </View>
            ) : null}
          </>
        )}
      </ScrollView>

      {picker ? (
        <DatePickerModal
          key={`date-${picker}`}
          visible
          title={picker === 'from' ? 'Desde' : 'Hasta'}
          initial={picker === 'from' ? dateFrom : dateTo}
          onPick={pickDate}
          onClear={() => clearDate(picker)}
          onClose={() => setPicker(null)}
        />
      ) : null}

      {rateOrder ? (
        <RatingModal
          key={`rating-${rateOrder.id}`}
          visible
          title="Calificar cliente"
          subtitle={`Pedido ${rateOrder.order_number}`}
          submitting={ratingSubmitting}
          error={ratingError}
          onClose={() => {
            setRateOrder(null);
            setRatingError(null);
          }}
          onSubmit={(stars, comment) => void submitRating(stars, comment)}
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
  headerCard: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  headerRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  truckIcon: {
    alignItems: 'center',
    backgroundColor: NAVY,
    borderRadius: radius.sm,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  truckIconText: {
    fontSize: 15,
  },
  headerTitle: {
    color: NAVY,
    fontSize: 13,
    fontWeight: '700',
  },
  headerDivider: {
    color: '#e2e8f0',
    fontSize: 13,
  },
  headerBusiness: {
    color: '#475569',
    fontSize: 13,
  },
  syncText: {
    color: '#94a3b8',
    fontSize: 12,
    marginTop: spacing.sm,
  },
  chipsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  chip: {
    alignItems: 'center',
    backgroundColor: '#f8fafc',
    borderColor: '#e5e7eb',
    borderRadius: radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 7,
    paddingHorizontal: spacing.md,
    paddingVertical: 5,
  },
  chipDot: {
    borderRadius: radius.pill,
    height: 8,
    width: 8,
  },
  chipText: {
    color: '#475569',
    fontSize: 12,
    fontWeight: '600',
  },
  titleBlock: {
    marginBottom: spacing.md,
  },
  title: {
    color: NAVY,
    fontSize: 22,
    fontWeight: '800',
  },
  subtitle: {
    color: '#64748b',
    fontSize: 13,
    lineHeight: 19,
    marginTop: spacing.xs,
  },
  sectionGap: {
    marginBottom: spacing.md,
  },
  kpiRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  kpi: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.sm,
    borderWidth: 1,
    flex: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },
  kpiAlert: {
    backgroundColor: '#fffaf6',
    borderColor: '#fdba74',
  },
  kpiLabel: {
    color: '#94a3b8',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.8,
    textTransform: 'uppercase',
  },
  kpiLabelAlert: {
    color: ACCENT,
  },
  kpiValue: {
    color: NAVY,
    fontSize: 26,
    fontWeight: '800',
    marginTop: 6,
  },
  kpiValueAlert: {
    color: ACCENT,
  },
  filtersCard: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  tabsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
  },
  tab: {
    alignItems: 'center',
    borderColor: 'transparent',
    borderRadius: radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  tabActive: {
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
  },
  tabText: {
    color: '#64748b',
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
    paddingVertical: 2,
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
    alignItems: 'flex-end',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  dateButton: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    flex: 1,
    minWidth: 130,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  dateLabel: {
    color: '#6b7280',
    fontSize: 11,
    fontWeight: '600',
  },
  dateValue: {
    color: '#334155',
    fontSize: 13,
    marginTop: 2,
  },
  clearDateButton: {
    alignItems: 'center',
    borderColor: '#e5e7eb',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 40,
    justifyContent: 'center',
    width: 40,
  },
  clearDateText: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '700',
  },
  filterDivider: {
    backgroundColor: '#f1f5f9',
    height: 1,
    marginTop: spacing.md,
  },
  filterRow: {
    marginTop: spacing.md,
  },
  statusCombo: {
    marginBottom: -spacing.sm,
  },
  searchInput: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    color: '#334155',
    fontSize: 14,
    marginTop: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  resetButton: {
    alignItems: 'center',
    alignSelf: 'flex-start',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    marginTop: spacing.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  resetText: {
    color: '#334155',
    fontSize: 12,
    fontWeight: '600',
  },
  loadingCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    gap: spacing.sm,
    paddingVertical: spacing.xxl,
  },
  loadingText: {
    color: '#6b7280',
    fontSize: 13,
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
  emptyIcon: {
    fontSize: 30,
    marginBottom: spacing.sm,
  },
  emptyTitle: {
    color: '#0f172a',
    fontSize: 16,
    fontWeight: '700',
    textAlign: 'center',
  },
  emptyText: {
    color: '#6b7280',
    fontSize: 13,
    lineHeight: 19,
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  emptyReset: {
    backgroundColor: ACCENT,
    borderRadius: radius.sm,
    marginTop: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  emptyResetText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
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
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 40,
  },
  pageButtonText: {
    color: '#374151',
    fontSize: 16,
    fontWeight: '700',
  },
  pageLabel: {
    color: '#6b7280',
    fontSize: 12,
  },
  disabled: {
    opacity: 0.5,
  },
});
