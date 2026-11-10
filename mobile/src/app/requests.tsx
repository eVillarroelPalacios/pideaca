import { router } from 'expo-router';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  Alert,
  FlatList,
  Image,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
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

type OrderFull = ServiceOrderItem & {
  quote?: {
    id: number;
    status: string | null;
    proposed_price: number | string | null;
    estimated_time: string | null;
    details: string | null;
  } | null;
  visit_fee?: number | string | null;
  completed_at?: string | null;
};

const MIS_PER_PAGE = 5;
const FETCH_SIZE = 50;
const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

const STATUS_LABEL: Record<string, string> = {
  PENDIENTE: 'Pendiente',
  PRESUPUESTADO: 'Presupuestado',
  EN_PROCESO: 'En proceso',
  COMPLETADO: 'Completado',
  CANCELADO: 'Cancelado',
};

const STATUS_COLOR: Record<string, string> = {
  PENDIENTE: '#b45309',
  PRESUPUESTADO: '#6d28d9',
  EN_PROCESO: '#0369a1',
  COMPLETADO: '#047857',
  CANCELADO: '#b91c1c',
};

const TYPE_LABEL: Record<string, string> = {
  EMERGENCY: 'Urgencia 24/7',
  SCHEDULED: 'Cita programada',
  QUOTE: 'Presupuesto',
};

const TYPE_COLOR: Record<string, string> = {
  EMERGENCY: '#dc2626',
  SCHEDULED: '#1d4ed8',
  QUOTE: '#6d28d9',
};

const ESCROW_LABEL: Record<string, string> = {
  HOLDING: 'Retenido en garantía',
  RELEASED: 'Pago liberado',
  REFUNDED: 'Pago devuelto',
};

const ESCROW_COLOR: Record<string, string> = {
  HOLDING: '#b45309',
  RELEASED: '#047857',
  REFUNDED: '#64748b',
};

const PAYMENT_LABELS: Record<string, string> = {
  efectivo: 'Efectivo',
  transfer: 'Transferencia',
  transferencia: 'Transferencia',
  transferencia_bancaria: 'Transferencia bancaria',
  tarjeta: 'Tarjeta',
  mercado_pago: 'Mercado Pago',
  mercadopago: 'Mercado Pago',
};

const STATUS_OPTIONS = [
  'PENDIENTE',
  'PRESUPUESTADO',
  'EN_PROCESO',
  'COMPLETADO',
  'CANCELADO',
];

function money(value: number | string | null | undefined): string {
  const n = Number(value ?? 0);
  const negative = n < 0;
  const abs = Math.abs(Number.isNaN(n) ? 0 : n);
  const fixed = abs.toFixed(2);
  const [intPart, decPart] = fixed.split('.');
  const grouped = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  const out = `$ ${grouped},${decPart ?? '00'}`;
  return negative ? `-${out}` : out;
}

function dateText(iso: string | null | undefined): string {
  if (!iso) {
    return '';
  }
  const d = new Date(String(iso).replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) {
    return '';
  }
  return d.toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
}

function clockNow(): string {
  return new Date().toLocaleTimeString('es-AR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

function dayOf(iso: string | null | undefined): string {
  return String(iso ?? '').slice(0, 10);
}

function displayDate(ymd: string): string {
  if (!ymd) {
    return 'Elegir fecha';
  }
  const [y, m, d] = ymd.split('-');
  if (!y || !m || !d) {
    return ymd;
  }
  return `${d}/${m}/${y}`;
}

type StepDef = { code: string; label: string; icon: string };

const ALL_STEPS: StepDef[] = [
  { code: 'PENDIENTE', label: 'Pendiente', icon: '👤' },
  { code: 'PRESUPUESTADO', label: 'Presupuestado', icon: '📄' },
  { code: 'EN_PROCESO', label: 'En proceso', icon: '🚚' },
  { code: 'COMPLETADO', label: 'Completado', icon: '🏠' },
];

function stepsFor(order: OrderFull): StepDef[] {
  const quoted =
    order.status === 'QUOTED' ||
    order.status === 'ACCEPTED' ||
    Boolean(order.quote) ||
    (order.proposals ?? []).length > 0 ||
    Number(order.additional_cost ?? 0) > 0;
  if (quoted) {
    return ALL_STEPS;
  }
  return ALL_STEPS.filter((s) => s.code !== 'PRESUPUESTADO');
}

function statusKey(label: string): string {
  return label.charAt(0) + label.slice(1).toLowerCase();
}

export default function RequestsScreen() {
  const [orders, setOrders] = useState<OrderFull[]>([]);
  const [statuses, setStatuses] = useState<string[]>([]);
  const [statusFilter, setStatusFilter] = useState<string | null>(null);
  const [typeFilter, setTypeFilter] = useState<ServiceOrderType | null>(null);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [misPage, setMisPage] = useState(1);
  const [updatedAt, setUpdatedAt] = useState(clockNow());
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [actingKey, setActingKey] = useState<string | null>(null);
  const [photoUrl, setPhotoUrl] = useState<string | null>(null);
  const [picker, setPicker] = useState<'from' | 'to' | null>(null);
  const [ratingOrder, setRatingOrder] = useState<OrderFull | null>(null);
  const [ratingSubmitting, setRatingSubmitting] = useState(false);
  const [ratingError, setRatingError] = useState<string | null>(null);
  const listRef = useRef<FlatList<OrderFull> | null>(null);

  const fetchAll = useCallback(
    async (silent: boolean) => {
      try {
        const first = await api.serviceOrders({
          per_page: FETCH_SIZE,
          page: 1,
          ...(statusFilter ? { status: statusFilter } : {}),
          ...(typeFilter ? { order_type: typeFilter } : {}),
        });
        let all = first.data as OrderFull[];
        const total = first.meta.total;
        if (total > all.length) {
          const second = await api.serviceOrders({
            per_page: FETCH_SIZE,
            page: 2,
            ...(statusFilter ? { status: statusFilter } : {}),
            ...(typeFilter ? { order_type: typeFilter } : {}),
          });
          all = [...all, ...(second.data as OrderFull[])];
        }
        setOrders(all);
        setStatuses(
          first.filters.statuses?.length ? first.filters.statuses : STATUS_OPTIONS,
        );
        setUpdatedAt(clockNow());
        setError(null);
        if (!silent) {
          setMisPage(1);
        }
      } catch (caught) {
        if (!silent) {
          setError(
            caught instanceof ApiError
              ? caught.message
              : 'No se pudieron cargar tus servicios.',
          );
        }
      }
    },
    [statusFilter, typeFilter],
  );

  useEffect(() => {
    let active = true;
    setLoading(true);
    fetchAll(false).finally(() => {
      if (active) {
        setLoading(false);
      }
    });
    return () => {
      active = false;
    };
  }, [fetchAll]);

  const filtered = useMemo(() => {
    if (!dateFrom && !dateTo) {
      return orders;
    }
    return orders.filter((o) => {
      const day = dayOf(o.created_at);
      if (!day) {
        return false;
      }
      if (dateFrom && day < dateFrom) {
        return false;
      }
      if (dateTo && day > dateTo) {
        return false;
      }
      return true;
    });
  }, [orders, dateFrom, dateTo]);

  const rangeActive = Boolean(dateFrom || dateTo);

  const totalPages = Math.max(1, Math.ceil(filtered.length / MIS_PER_PAGE));
  const safePage = Math.min(Math.max(1, misPage), totalPages);
  const pageList = useMemo(
    () => filtered.slice((safePage - 1) * MIS_PER_PAGE, safePage * MIS_PER_PAGE),
    [filtered, safePage],
  );

  const pendingCount = useMemo(
    () => orders.filter((o) => o.status !== 'COMPLETED' && o.status !== 'CANCELLED').length,
    [orders],
  );

  const summary =
    orders.length === 0
      ? `Tus solicitudes, citas y presupuestos · actualizado ${updatedAt}`
      : `${orders.length} ${orders.length === 1 ? 'servicio' : 'servicios'}${
          pendingCount > 0
            ? ` · ${pendingCount}${pendingCount === 1 ? 'pendiente' : 'pendientes'}`
            : ''
        } · actualizado ${updatedAt}`;

  const emptyText = rangeActive
    ? 'No hay servicios en ese rango de fechas.'
    : statusFilter || typeFilter
      ? 'No hay servicios con ese filtro.'
      : 'Todavía no tenés servicios solicitados.\nPedí uno desde la sección "Solicitar Servicio" y acá vas a poder seguirlo.';

  function scrollTop() {
    try {
      listRef.current?.scrollToOffset({ offset: 0, animated: false });
    } catch {
      // Sin scroll disponible (lista corta): no hace falta moverse.
    }
  }

  function goPage(page: number) {
    setMisPage(Math.min(Math.max(1, page), totalPages));
    scrollTop();
  }

  function handleStatusFilter(status: string | null) {
    setStatusFilter(status);
  }

  function handleTypeFilter(type: ServiceOrderType | null) {
    setTypeFilter(type);
  }

  function handleResetAll() {
    setStatusFilter(null);
    setTypeFilter(null);
    setDateFrom('');
    setDateTo('');
    setMisPage(1);
    if (!statusFilter && !typeFilter) {
      void fetchAll(false);
    }
  }

  async function handleRefresh() {
    setRefreshing(true);
    await fetchAll(true);
    setRefreshing(false);
  }

  function replaceOrder(next: OrderFull) {
    setOrders((current) =>
      current.map((o) => (o.id === next.id ? next : o)),
    );
    setUpdatedAt(clockNow());
  }

  function runAct(order: OrderFull, act: string, fn: () => Promise<unknown>) {
    const key = `${order.id}:${act}`;
    setActingKey(key);
    setError(null);
    fn()
      .then((res) => {
        const next = (res as { data?: { order?: OrderFull } })?.data?.order;
        if (next) {
          replaceOrder(next);
        }
      })
      .catch((caught: unknown) => {
        setError(
          caught instanceof ApiError
            ? caught.message
            : 'No se pudo completar la acción.',
        );
      })
      .finally(() => {
        setActingKey((current) => (current === key ? null : current));
      });
  }

  function confirmAndRun(order: OrderFull, act: string, title: string, message: string) {
    Alert.alert(title, message, [
      { text: 'Volver', style: 'cancel' },
      {
        text: 'Confirmar',
        style: act === 'cancel' || act === 'reject-quote' ? 'destructive' : 'default',
        onPress: () => {
          if (act === 'cancel') {
            runAct(order, act, () => api.cancelServiceOrder(order.id));
          } else if (act === 'accept-quote') {
            runAct(order, act, () => api.acceptQuote(order.id, order.quote?.id));
          } else if (act === 'reject-quote') {
            runAct(order, act, () => api.rejectQuote(order.id, order.quote?.id));
          } else if (act === 'approve-estimate') {
            runAct(order, act, () => api.approveEstimate(order.id));
          } else if (act === 'release') {
            runAct(order, act, () => api.confirmRelease(order.id));
          }
        },
      },
    ]);
  }

  function handleAction(order: OrderFull, act: string) {
    if (actingKey) {
      return;
    }
    if (act === 'rate') {
      setRatingError(null);
      setRatingOrder(order);
      return;
    }
    if (act === 'cancel') {
      confirmAndRun(
        order,
        act,
        'Cancelar solicitud',
        '¿Seguro que querés cancelar esta solicitud? Se devuelven los fondos retenidos.',
      );
      return;
    }
    if (act === 'reject-quote') {
      confirmAndRun(
        order,
        act,
        'Rechazar propuesta',
        '¿Rechazar esta propuesta? El profesional podrá enviar otra.',
      );
      return;
    }
    if (act === 'approve-estimate') {
      confirmAndRun(
        order,
        act,
        'Aprobar costo adicional',
        '¿Aprobar este costo adicional? El trabajo continúa.',
      );
      return;
    }
    if (act === 'release') {
      confirmAndRun(
        order,
        act,
        'Confirmar y liberar pago',
        '¿Confirmar que el trabajo está conforme? Se libera el pago al prestador.',
      );
      return;
    }
    if (act === 'accept-quote') {
      const price = money(order.quote?.proposed_price ?? null);
      Alert.alert(
        'Aceptar propuesta',
        `¿Confirmás el trabajo por ${price}? Los demás profesionales quedan descartados.`,
        [
          { text: 'Volver', style: 'cancel' },
          {
            text: 'Sí, aceptar',
            onPress: () => runAct(order, act, () => api.acceptQuote(order.id, order.quote?.id)),
          },
        ],
      );
      return;
    }
  }

  function openRating(order: OrderFull) {
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
      replaceOrder(response.data.order as OrderFull);
      setRatingOrder(null);
    } catch (caught) {
      setRatingError(
        caught instanceof ApiError
          ? caught.message
          : 'No se pudo registrar la calificación.',
      );
    } finally {
      setRatingSubmitting(false);
    }
  }

  if (loading) {
    return <LoadingScreen message="Cargando tus servicios…" />;
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
        <View style={styles.headerTitleWrap}>
          <Text style={styles.title}>Mis servicios</Text>
          <Text style={styles.subtitle}>{summary}</Text>
        </View>
        <View style={styles.headerSpacer} />
      </View>

      <View style={styles.filterSection}>
        <View style={styles.filterRowTop}>
          <View style={styles.filterItem}>
            <ComboField
              label="Estado"
              value={statusFilter}
              options={(statuses.length ? statuses : STATUS_OPTIONS).map((s) => ({
                value: s,
                label: STATUS_LABEL[s] ?? statusKey(s),
              }))}
              onChange={handleStatusFilter}
              allowEmpty
              emptyLabel="Todos los estados"
              onClear={() => handleStatusFilter(null)}
              searchPlaceholder="Buscar estado…"
            />
          </View>
          <View style={styles.filterItem}>
            <ComboField
              label="Tipo"
              value={typeFilter}
              options={[
                { value: 'EMERGENCY' as ServiceOrderType, label: 'Urgencia 24/7' },
                { value: 'SCHEDULED' as ServiceOrderType, label: 'Cita programada' },
                { value: 'QUOTE' as ServiceOrderType, label: 'Presupuesto' },
              ]}
              onChange={handleTypeFilter}
              allowEmpty
              emptyLabel="Todos los tipos"
              onClear={() => handleTypeFilter(null)}
            />
          </View>
        </View>
        <View style={styles.filterRowBottom}>
          <View style={styles.filterDate}>
            <Text style={styles.dateLabel}>Desde</Text>
            <Pressable
              accessibilityRole="button"
              onPress={() => setPicker('from')}
              style={styles.dateInput}
            >
              <Text style={[styles.dateText, !dateFrom && styles.datePlaceholder]}>
                {displayDate(dateFrom)}
              </Text>
            </Pressable>
          </View>
          <View style={styles.filterDate}>
            <Text style={styles.dateLabel}>Hasta</Text>
            <Pressable
              accessibilityRole="button"
              onPress={() => setPicker('to')}
              style={styles.dateInput}
            >
              <Text style={[styles.dateText, !dateTo && styles.datePlaceholder]}>
                {displayDate(dateTo)}
              </Text>
            </Pressable>
          </View>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Limpiar filtros"
            onPress={handleResetAll}
            style={styles.resetButton}
          >
            <Text style={styles.resetText}>✕</Text>
          </Pressable>
        </View>
      </View>

      <View style={styles.listContainer}>
        <ErrorBanner message={error} />

        <FlatList
          ref={listRef}
          data={pageList}
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
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyTitle}>Sin servicios</Text>
              <Text style={styles.emptyText}>{emptyText}</Text>
            </View>
          }
          ListFooterComponent={
            totalPages > 1 ? (
              <View style={styles.pagination}>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Página anterior"
                  disabled={safePage <= 1}
                  onPress={() => goPage(safePage - 1)}
                  style={[styles.pageNav, safePage <= 1 && styles.pageNavDisabled]}
                >
                  <Text style={styles.pageNavText}>❮</Text>
                </Pressable>
                <View style={styles.pageDots}>
                  {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
                    <Pressable
                      key={p}
                      accessibilityRole="button"
                      onPress={() => goPage(p)}
                      style={styles.pageDot}
                    >
                      <Text
                        style={[styles.pageDotText, p === safePage && styles.pageDotActive]}
                      >
                        {p}
                      </Text>
                    </Pressable>
                  ))}
                </View>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Página siguiente"
                  disabled={safePage >= totalPages}
                  onPress={() => goPage(safePage + 1)}
                  style={[styles.pageNav, safePage >= totalPages && styles.pageNavDisabled]}
                >
                  <Text style={styles.pageNavText}>❯</Text>
                </Pressable>
              </View>
            ) : null
          }
          renderItem={({ item }) => (
            <ServiceCard
              order={item}
              actingKey={actingKey}
              onAction={handleAction}
              onOpenRating={openRating}
              onPhoto={setPhotoUrl}
            />
          )}
        />
      </View>

      <DatePickerModal
        visible={picker !== null}
        title={picker === 'from' ? 'Fecha desde' : 'Fecha hasta'}
        initial={picker === 'from' ? dateFrom : dateTo}
        onPick={(value) => {
          if (picker === 'from') {
            setDateFrom(value);
          } else if (picker === 'to') {
            setDateTo(value);
          }
          setMisPage(1);
          setPicker(null);
        }}
        onClear={() => {
          if (picker === 'from') {
            setDateFrom('');
          } else {
            setDateTo('');
          }
          setMisPage(1);
          setPicker(null);
        }}
        onClose={() => setPicker(null)}
      />

      <Modal
        visible={photoUrl !== null}
        transparent
        animationType="fade"
        onRequestClose={() => setPhotoUrl(null)}
      >
        <View style={styles.photoBackdrop}>
          <View style={styles.photoCard}>
            <Text style={styles.photoTitle}>Foto comprobante</Text>
            {photoUrl ? (
              <Image source={{ uri: photoUrl }} style={styles.photoPreview} resizeMode="contain" />
            ) : null}
            <PrimaryButton title="Cerrar" onPress={() => setPhotoUrl(null)} />
          </View>
        </View>
      </Modal>

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

function Badge({ label, color }: { label: string; color: string }) {
  if (!label) {
    return null;
  }
  return (
    <View style={[styles.badge, { borderColor: color }]}>
      <Text style={[styles.badgeText, { color }]}>{label}</Text>
    </View>
  );
}

function Stepper({ order }: { order: OrderFull }) {
  const display = order.display_status || order.status;
  if (order.status === 'CANCELLED' || display === 'CANCELADO') {
    return <Text style={styles.cancelledBar}>Servicio cancelado</Text>;
  }
  const steps = stepsFor(order);
  const current = steps.findIndex((s) => s.code === display);
  if (current < 0) {
    return null;
  }
  return (
    <View style={styles.track}>
      {steps.map((step, i) => {
        const done = i < current;
        const active = i === current;
        const passed = done || active;
        return (
          <View key={step.code} style={styles.stepWrap}>
            {i > 0 ? (
              <View
                style={[
                  styles.stepLine,
                  i <= current ? styles.stepLinePassed : styles.stepLineTodo,
                ]}
              />
            ) : null}
            <View style={styles.step}>
              <Text style={styles.stepNow}>{active ? 'AHORA' : ' '}</Text>
              <View
                style={[
                  styles.stepCircle,
                  passed ? styles.stepCirclePassed : styles.stepCircleTodo,
                  active && styles.stepCircleActive,
                ]}
              >
                <Text style={styles.stepIcon}>{step.icon}</Text>
              </View>
              <Text
                style={[
                  styles.stepLabel,
                  active
                    ? styles.stepLabelActive
                    : done
                      ? styles.stepLabelDone
                      : styles.stepLabelTodo,
                ]}
              >
                {step.label}
              </Text>
            </View>
            {i < steps.length - 1 ? <View style={styles.stepGap} /> : null}
          </View>
        );
      })}
    </View>
  );
}

function ServiceCard({
  order,
  actingKey,
  onAction,
  onOpenRating,
  onPhoto,
}: {
  order: OrderFull;
  actingKey: string | null;
  onAction: (order: OrderFull, act: string) => void;
  onOpenRating: (order: OrderFull) => void;
  onPhoto: (url: string) => void;
}) {
  const merchant = order.merchant;
  const right: string[] = [];
  if (order.payment_method) {
    right.push(`Pago: ${PAYMENT_LABELS[order.payment_method] ?? order.payment_method}`);
  }
  if (order.scheduled_at) {
    right.push(`Programado: ${dateText(order.scheduled_at)}`);
  }

  const proposalPending =
    order.order_type === 'QUOTE' &&
    order.status === 'QUOTED' &&
    order.quote &&
    order.quote.status === 'PENDING' &&
    Number(order.quote.proposed_price ?? 0) > 0;
  const estimatePending =
    (order.order_type === 'EMERGENCY' || order.order_type === 'SCHEDULED') &&
    order.status === 'QUOTED' &&
    Number(order.additional_cost ?? 0) > 0 &&
    order.escrow_status === 'HOLDING';
  const canRelease = order.status === 'COMPLETED' && order.escrow_status === 'HOLDING';
  const canRate = order.status === 'COMPLETED' && !order.customer_rated;
  const busy = (act: string) => actingKey === `${order.id}:${act}`;

  const rows: { label: string; value: string }[] = [];
  if (Number(order.visit_fee ?? 0) > 0) {
    rows.push({ label: 'Visita', value: money(order.visit_fee) });
  }
  if (Number(order.additional_cost ?? 0) > 0) {
    rows.push({ label: 'Costo adicional', value: money(order.additional_cost) });
  }

  return (
    <Pressable
      accessibilityRole="button"
      onPress={() =>
        router.push({ pathname: '/request/[id]', params: { id: String(order.id) } })
      }
      style={styles.card}
    >
      <View style={styles.cardHeader}>
        <View style={styles.cardTitleRow}>
          <Text style={styles.cardTitle}>Servicio #{order.id}</Text>
          <Badge label={TYPE_LABEL[order.order_type] ?? order.order_type} color={TYPE_COLOR[order.order_type] ?? '#6b7280'} />
          <Badge
            label={order.status_label || STATUS_LABEL[order.display_status] || order.status}
            color={STATUS_COLOR[order.display_status] ?? '#6b7280'}
          />
          <Badge
            label={ESCROW_LABEL[order.escrow_status ?? ''] ?? ''}
            color={ESCROW_COLOR[order.escrow_status ?? ''] ?? '#6b7280'}
          />
        </View>
        <Text style={styles.cardDate}>{dateText(order.created_at)}</Text>
      </View>

      <Stepper order={order} />

      <View style={styles.cardBody}>
        <View style={styles.merchantRow}>
          <View style={styles.merchantLeft}>
            <Text style={styles.merchantName}>
              {merchant?.business_name ?? 'Profesional sin asignar'}
            </Text>
            <Text style={styles.merchantSub}>
              {(order.sub_group?.description ?? 'Servicio') +
                (merchant?.zone ? ` · ${merchant.zone}` : '')}
            </Text>
          </View>
          {right.length ? <Text style={styles.merchantRight}>{right.join(' · ')}</Text> : null}
        </View>

        {order.description ? (
          <Text style={styles.detail}>Detalle: {order.description}</Text>
        ) : null}

        {order.order_type === 'QUOTE' && order.status === 'QUOTED' && order.quote ? (
          <View style={styles.proposalBox}>
            <Text style={styles.proposalTitle}>Propuesta del profesional</Text>
            <Text style={styles.proposalPrice}>
              {money(order.quote.proposed_price)}
              {order.quote.estimated_time ? ` · ${order.quote.estimated_time}` : ''}
            </Text>
            {order.quote.details ? (
              <Text style={styles.proposalDetails}>{order.quote.details}</Text>
            ) : null}
          </View>
        ) : null}

        {order.estimate_details ? (
          <Text style={styles.estimateDetails}>Detalle del estimado: {order.estimate_details}</Text>
        ) : null}

        {rows.length > 0 || Number(order.total_amount ?? 0) > 0 ? (
          <View style={styles.totals}>
            {rows.map((r) => (
              <View key={r.label} style={styles.totalRow}>
                <Text style={styles.totalLabel}>{r.label}</Text>
                <Text style={styles.totalValue}>{r.value}</Text>
              </View>
            ))}
            <View style={styles.totalFinal}>
              <Text style={styles.totalFinalLabel}>Total</Text>
              <Text style={styles.totalFinalValue}>{money(order.total_amount)}</Text>
            </View>
          </View>
        ) : (
          <Text style={styles.noCharges}>Sin cargos registrados todavía.</Text>
        )}

        {order.completion_photo_url ? (
          <Pressable
            accessibilityRole="button"
            onPress={(e) => {
              e.stopPropagation();
              if (order.completion_photo_url) {
                onPhoto(order.completion_photo_url);
              }
            }}
          >
            <Text style={styles.photoLink}>Ver foto comprobante</Text>
          </Pressable>
        ) : null}

        <View style={styles.cardActions}>
          {proposalPending ? (
            <>
              <Pressable
                accessibilityRole="button"
                disabled={busy('accept-quote')}
                onPress={(e) => {
                  e.stopPropagation();
                  onAction(order, 'accept-quote');
                }}
                style={[styles.btnAccept, busy('accept-quote') && styles.btnDisabled]}
              >
                <Text style={styles.btnAcceptText}>
                  {busy('accept-quote') ? 'Enviando…' : 'Aceptar propuesta'}
                </Text>
              </Pressable>
              <Pressable
                accessibilityRole="button"
                disabled={busy('reject-quote')}
                onPress={(e) => {
                  e.stopPropagation();
                  onAction(order, 'reject-quote');
                }}
                style={[styles.btnReject, busy('reject-quote') && styles.btnDisabled]}
              >
                <Text style={styles.btnRejectText}>
                  {busy('reject-quote') ? 'Enviando…' : 'Rechazar propuesta'}
                </Text>
              </Pressable>
            </>
          ) : null}

          {estimatePending ? (
            <Pressable
              accessibilityRole="button"
              disabled={busy('approve-estimate')}
              onPress={(e) => {
                e.stopPropagation();
                onAction(order, 'approve-estimate');
              }}
              style={[styles.btnAccept, busy('approve-estimate') && styles.btnDisabled]}
            >
              <Text style={styles.btnAcceptText}>
                {busy('approve-estimate') ? 'Enviando…' : 'Aprobar costo adicional'}
              </Text>
            </Pressable>
          ) : null}

          {order.cancellable ? (
            <Pressable
              accessibilityRole="button"
              disabled={busy('cancel')}
              onPress={(e) => {
                e.stopPropagation();
                onAction(order, 'cancel');
              }}
              style={[styles.btnCancel, busy('cancel') && styles.btnDisabled]}
            >
              <Text style={styles.btnCancelText}>
                {busy('cancel') ? 'Enviando…' : '✕ Cancelar'}
              </Text>
            </Pressable>
          ) : null}

          {canRelease ? (
            <Pressable
              accessibilityRole="button"
              disabled={busy('release')}
              onPress={(e) => {
                e.stopPropagation();
                onAction(order, 'release');
              }}
              style={[styles.btnRelease, busy('release') && styles.btnDisabled]}
            >
              <Text style={styles.btnReleaseText}>
                {busy('release') ? 'Enviando…' : 'Confirmar y liberar pago'}
              </Text>
            </Pressable>
          ) : null}

          {canRate ? (
            <Pressable
              accessibilityRole="button"
              onPress={(e) => {
                e.stopPropagation();
                onOpenRating(order);
              }}
              style={styles.btnRate}
            >
              <Text style={styles.btnRateText}>Calificar al prestador</Text>
            </Pressable>
          ) : null}
        </View>

        {order.status === 'COMPLETED' && order.customer_rated ? (
          <Text style={styles.ratedLine}>
            ★ Ya calificaste{order.customer_rating ? ` (${order.customer_rating.stars}/5)` : ''}
          </Text>
        ) : null}
      </View>
    </Pressable>
  );
}

function DatePickerModal({
  visible,
  title,
  initial,
  onPick,
  onClear,
  onClose,
}: {
  visible: boolean;
  title: string;
  initial: string;
  onPick: (ymd: string) => void;
  onClear: () => void;
  onClose: () => void;
}) {
  const today = new Date();
  const [cursor, setCursor] = useState(() => {
    if (/^\d{4}-\d{2}-\d{2}$/.test(initial)) {
      const [y, m] = initial.split('-').map(Number);
      return new Date(y, m - 1, 1);
    }
    return new Date(today.getFullYear(), today.getMonth(), 1);
  });

  useEffect(() => {
    if (visible) {
      if (/^\d{4}-\d{2}-\d{2}$/.test(initial)) {
        const [y, m] = initial.split('-').map(Number);
        setCursor(new Date(y, m - 1, 1));
      } else {
        setCursor(new Date(today.getFullYear(), today.getMonth(), 1));
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [visible, initial]);

  const year = cursor.getFullYear();
  const month = cursor.getMonth();
  const firstOffset = (new Date(year, month, 1).getDay() + 6) % 7;
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const monthName = cursor.toLocaleString('es-AR', { month: 'long', year: 'numeric' });
  const parts = initial.split('-');
  const selectedDay = /^\d{4}-\d{2}-\d{2}$/.test(initial) ? Number(parts[2]) : null;
  const selectedHere =
    selectedDay !== null && Number(parts[0]) === year && Number(parts[1]) === month + 1;

  function pick(day: number) {
    const ymd = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    onPick(ymd);
  }

  const cells: (number | null)[] = [
    ...Array.from({ length: firstOffset }, () => null),
    ...Array.from({ length: daysInMonth }, (_, i) => i + 1),
  ];

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.pickerBackdrop}>
        <View style={styles.pickerCard}>
          <Text style={styles.pickerTitle}>{title}</Text>
          <View style={styles.pickerNav}>
            <Pressable
              accessibilityRole="button"
              onPress={() => setCursor(new Date(year, month - 1, 1))}
              style={styles.pickerNavBtn}
            >
              <Text style={styles.pickerNavText}>❮</Text>
            </Pressable>
            <Text style={styles.pickerMonth}>{monthName}</Text>
            <Pressable
              accessibilityRole="button"
              onPress={() => setCursor(new Date(year, month + 1, 1))}
              style={styles.pickerNavBtn}
            >
              <Text style={styles.pickerNavText}>❯</Text>
            </Pressable>
          </View>
          <View style={styles.weekRow}>
            {['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'].map((w) => (
              <Text key={w} style={styles.weekDay}>
                {w}
              </Text>
            ))}
          </View>
          <View style={styles.daysGrid}>
            {cells.map((day, i) =>
              day === null ? (
                <View key={`e-${i}`} style={styles.dayCell} />
              ) : (
                <Pressable
                  key={day}
                  accessibilityRole="button"
                  onPress={() => pick(day)}
                  style={[styles.dayCell, selectedHere && day === selectedDay && styles.daySelected]}
                >
                  <Text
                    style={[styles.dayText, selectedHere && day === selectedDay && styles.dayTextSelected]}
                  >
                    {day}
                  </Text>
                </Pressable>
              ),
            )}
          </View>
          <View style={styles.pickerFooter}>
            <Pressable accessibilityRole="button" onPress={onClear} style={styles.pickerGhost}>
              <Text style={styles.pickerGhostText}>Limpiar</Text>
            </Pressable>
            <Pressable accessibilityRole="button" onPress={onClose} style={styles.pickerGhost}>
              <Text style={styles.pickerGhostText}>Cancelar</Text>
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
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
    paddingTop: spacing.sm,
    paddingBottom: spacing.xs,
  },
  backButton: {
    minWidth: 70,
  },
  backText: {
    color: colors.brandDark,
    fontSize: 16,
    fontWeight: '600',
  },
  headerTitleWrap: {
    alignItems: 'center',
    flex: 1,
  },
  title: {
    color: NAVY,
    fontSize: 18,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
    textAlign: 'center',
  },
  headerSpacer: {
    minWidth: 70,
  },
  filterSection: {
    paddingHorizontal: spacing.lg,
    paddingTop: 0,
    paddingBottom: spacing.xs,
  },
  filterRowTop: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  filterRowBottom: {
    flexDirection: 'row',
    gap: spacing.sm,
    alignItems: 'flex-end',
    marginTop: -spacing.sm,
  },
  filterItem: {
    flex: 1,
    minWidth: 0,
  },
  filterDate: {
    flex: 1,
    minWidth: 0,
  },
  dateLabel: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: spacing.xs,
  },
  dateInput: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 11,
  },
  dateText: {
    color: colors.text,
    fontSize: 13,
  },
  datePlaceholder: {
    color: colors.muted,
  },
  resetButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    height: 44,
    justifyContent: 'center',
    width: 44,
  },
  resetText: {
    color: colors.muted,
    fontSize: 15,
    fontWeight: '700',
  },
  listContainer: {
    flex: 1,
    paddingHorizontal: spacing.lg,
    paddingTop: 0,
  },
  list: {
    paddingBottom: spacing.xxl,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.md,
    overflow: 'hidden',
  },
  cardPressed: {
    opacity: 0.7,
  },
  cardHeader: {
    backgroundColor: '#f8fafc',
    borderBottomColor: '#e5e7eb',
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: 11,
  },
  cardTitleRow: {
    alignItems: 'center',
    flex: 1,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
  },
  cardTitle: {
    color: NAVY,
    fontFamily: 'monospace',
    fontSize: 13,
    fontWeight: '700',
  },
  cardDate: {
    color: '#94a3b8',
    fontSize: 11,
  },
  badge: {
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: 9,
    paddingVertical: 2,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  cancelledBar: {
    backgroundColor: '#fef2f2',
    borderBottomColor: '#fecaca',
    borderBottomWidth: 1,
    color: '#b91c1c',
    fontSize: 13,
    fontWeight: '700',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  track: {
    flexDirection: 'row',
    paddingHorizontal: spacing.md,
    paddingTop: spacing.md,
    paddingBottom: spacing.sm,
  },
  stepWrap: {
    alignItems: 'flex-start',
    flex: 1,
    flexDirection: 'row',
  },
  stepLine: {
    borderRadius: 2,
    height: 3,
    marginTop: 28,
  },
  stepLinePassed: {
    backgroundColor: ACCENT,
    flex: 1,
  },
  stepLineTodo: {
    backgroundColor: '#e2e8f0',
    flex: 1,
  },
  step: {
    alignItems: 'center',
    flexShrink: 0,
    width: 64,
  },
  stepGap: {
    flex: 1,
  },
  stepNow: {
    color: ACCENT,
    fontSize: 9,
    fontWeight: '800',
    height: 14,
    letterSpacing: 0.5,
    lineHeight: 14,
  },
  stepCircle: {
    alignItems: 'center',
    borderRadius: 999,
    borderWidth: 1,
    height: 30,
    justifyContent: 'center',
    width: 30,
  },
  stepCirclePassed: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  stepCircleTodo: {
    backgroundColor: '#e2e8f0',
    borderColor: '#e2e8f0',
  },
  stepCircleActive: {
    borderColor: ACCENT,
  },
  stepIcon: {
    color: '#fff',
    fontSize: 14,
  },
  stepLabel: {
    fontSize: 9,
    lineHeight: 12,
    marginTop: 7,
    textAlign: 'center',
  },
  stepLabelActive: {
    color: ACCENT,
    fontWeight: '700',
  },
  stepLabelDone: {
    color: '#334155',
    fontWeight: '600',
  },
  stepLabelTodo: {
    color: '#94a3b8',
    fontWeight: '500',
  },
  cardBody: {
    padding: spacing.md,
  },
  merchantRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
  },
  merchantLeft: {
    flex: 1,
    minWidth: 0,
  },
  merchantName: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
  merchantSub: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 4,
  },
  merchantRight: {
    color: colors.muted,
    fontSize: 12,
    textAlign: 'right',
  },
  detail: {
    color: '#9a3412',
    fontSize: 12,
    marginTop: 6,
  },
  proposalBox: {
    backgroundColor: '#f8fafc',
    borderColor: '#e2e8f0',
    borderRadius: 6,
    borderWidth: 1,
    marginTop: spacing.sm,
    padding: spacing.sm,
  },
  proposalTitle: {
    color: '#0f172a',
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 4,
  },
  proposalPrice: {
    color: '#0f172a',
    fontSize: 13,
  },
  proposalDetails: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 4,
  },
  estimateDetails: {
    color: colors.muted,
    fontSize: 12,
    fontStyle: 'italic',
    marginTop: 6,
  },
  totals: {
    borderTopColor: '#e5e7eb',
    borderTopWidth: 1,
    marginTop: spacing.sm,
    paddingTop: spacing.sm,
  },
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  totalLabel: {
    color: colors.muted,
    fontSize: 13,
  },
  totalValue: {
    color: colors.muted,
    fontSize: 13,
  },
  totalFinal: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 6,
  },
  totalFinalLabel: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '700',
  },
  totalFinalValue: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '700',
  },
  noCharges: {
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    color: '#94a3b8',
    fontSize: 12,
    marginTop: spacing.sm,
    paddingTop: spacing.sm,
  },
  photoLink: {
    color: '#1d4ed8',
    fontSize: 12,
    fontWeight: '600',
    marginTop: spacing.sm,
  },
  cardActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  btnAccept: {
    backgroundColor: '#047857',
    borderRadius: 4,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  btnAcceptText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '600',
  },
  btnReject: {
    backgroundColor: '#fff',
    borderColor: '#fca5a5',
    borderRadius: 4,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  btnRejectText: {
    color: '#b91c1c',
    fontSize: 12,
    fontWeight: '600',
  },
  btnCancel: {
    backgroundColor: '#fff',
    borderColor: '#fca5a5',
    borderRadius: 4,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 9,
  },
  btnCancelText: {
    color: '#b91c1c',
    fontSize: 12,
    fontWeight: '600',
  },
  btnRelease: {
    backgroundColor: NAVY,
    borderRadius: 4,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  btnReleaseText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '600',
  },
  btnRate: {
    backgroundColor: ACCENT,
    borderRadius: 4,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  btnRateText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '600',
  },
  btnDisabled: {
    opacity: 0.6,
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
  pagination: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'center',
    marginTop: spacing.md,
  },
  pageNav: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 999,
    borderWidth: 1,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  pageNavDisabled: {
    opacity: 0.4,
  },
  pageNavText: {
    color: '#374151',
    fontSize: 14,
  },
  pageDots: {
    flexDirection: 'row',
    gap: 2,
  },
  pageDot: {
    paddingHorizontal: 6,
    paddingVertical: 2,
  },
  pageDotText: {
    color: '#9ca3b8',
    fontSize: 12,
    fontWeight: '600',
  },
  pageDotActive: {
    color: ACCENT,
  },
  photoBackdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(7,26,48,0.6)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  photoCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    padding: spacing.lg,
    width: '100%',
  },
  photoTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
    marginBottom: spacing.md,
  },
  photoPreview: {
    borderRadius: radius.md,
    height: 320,
    marginBottom: spacing.lg,
    width: '100%',
  },
  pickerBackdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(7,26,48,0.55)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  pickerCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    padding: spacing.lg,
    width: '100%',
  },
  pickerTitle: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '800',
  },
  pickerNav: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.md,
  },
  pickerNavBtn: {
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  pickerNavText: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '700',
  },
  pickerMonth: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
    textTransform: 'capitalize',
  },
  weekRow: {
    flexDirection: 'row',
    marginTop: spacing.md,
  },
  weekDay: {
    color: colors.muted,
    flex: 1,
    fontSize: 11,
    fontWeight: '700',
    textAlign: 'center',
  },
  daysGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: spacing.xs,
  },
  dayCell: {
    alignItems: 'center',
    height: 36,
    justifyContent: 'center',
    width: '14.28%',
  },
  dayText: {
    color: colors.text,
    fontSize: 13,
  },
  daySelected: {
    backgroundColor: ACCENT,
    borderRadius: 999,
  },
  dayTextSelected: {
    color: colors.surface,
    fontWeight: '800',
  },
  pickerFooter: {
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'flex-end',
    marginTop: spacing.md,
  },
  pickerGhost: {
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  pickerGhostText: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
  },
});