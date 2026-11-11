import { router } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ComboField, type ComboOption } from '@/components/ComboField';
import { DatePickerModal } from '@/components/DatePickerModal';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { exportReportHogarPdf } from '@/lib/reportHogarPdf';
import { colors, radius, spacing } from '@/theme';
import type {
  ReportHogarOrder,
  ReportHogarParams,
  ReportHogarResponse,
  ServiceOrderType,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

/** Mismos estados y modalidades del `<select>` de la vista web. */
const ESTADO_OPTIONS: ComboOption<string>[] = [
  { value: 'REQUESTED', label: 'Pendiente' },
  { value: 'QUOTED', label: 'Presupuestado' },
  { value: 'ACCEPTED', label: 'Confirmado / Cita' },
  { value: 'IN_PROGRESS', label: 'En camino / Proceso' },
  { value: 'COMPLETED', label: 'Completado' },
  { value: 'CANCELLED', label: 'Cancelado' },
];

const TIPO_OPTIONS: ComboOption<ServiceOrderType>[] = [
  { value: 'EMERGENCY' as ServiceOrderType, label: 'Urgencia 24/7' },
  { value: 'SCHEDULED' as ServiceOrderType, label: 'Cita programada' },
  { value: 'QUOTE' as ServiceOrderType, label: 'Presupuesto a cotizar' },
];

const TYPE_LABELS: Record<string, string> = {
  EMERGENCY: 'Urgencia 24/7',
  SCHEDULED: 'Cita Programada',
  QUOTE: 'Presupuesto',
};

type Filters = {
  fechaDesde: string;
  fechaHasta: string;
  estado: string | null;
  tipo: ServiceOrderType | null;
};

function defaultFilters(): Filters {
  return { fechaDesde: '', fechaHasta: '', estado: null, tipo: null };
}

function toParams(filters: Filters): ReportHogarParams {
  const params: ReportHogarParams = {};

  if (filters.fechaDesde) {
    params.fecha_desde = filters.fechaDesde;
  }

  if (filters.fechaHasta) {
    params.fecha_hasta = filters.fechaHasta;
  }

  if (filters.estado) {
    params.estado = filters.estado;
  }

  if (filters.tipo) {
    params.tipo = filters.tipo;
  }

  return params;
}

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

function dateTime(iso: string | null | undefined): string {
  if (!iso) {
    return '';
  }

  const date = new Date(String(iso).replace(' ', 'T'));

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return date.toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
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

function errorMessage(caught: unknown): string {
  return caught instanceof ApiError
    ? caught.message
    : 'No se pudieron cargar los reportes del hogar.';
}

function statusStyle(status: string): { backgroundColor: string; color: string } {
  if (status === 'COMPLETED') {
    return { backgroundColor: '#d1fae5', color: '#065f46' };
  }

  if (['ACCEPTED', 'IN_PROGRESS', 'QUOTED'].includes(status)) {
    return { backgroundColor: '#e0f2fe', color: '#0369a1' };
  }

  if (status === 'CANCELLED') {
    return { backgroundColor: '#fee2e2', color: '#991b1b' };
  }

  return { backgroundColor: '#f1f5f9', color: '#475569' };
}

function KpiCard({
  label,
  value,
  hint,
  accent,
}: {
  label: string;
  value: string;
  hint?: string;
  accent: string;
}) {
  return (
    <View style={[styles.kpiCard, { borderLeftColor: accent }]}>
      <Text style={styles.kpiLabel}>{label}</Text>
      <Text style={styles.kpiValue}>
        {value}
        {hint ? <Text style={styles.kpiHint}> {hint}</Text> : null}
      </Text>
    </View>
  );
}

function OrderCard({
  order,
  isPrestador,
}: {
  order: ReportHogarOrder;
  isPrestador: boolean;
}) {
  const pill = statusStyle(order.status);
  const thirdName = isPrestador
    ? order.customer?.name ?? 'Cliente'
    : order.merchant?.business_name ?? 'Prestador';
  const thirdContact = isPrestador
    ? order.customer?.phone ?? order.customer?.email ?? ''
    : order.merchant?.phone ?? order.merchant?.contact_name ?? '';

  function open() {
    router.push((isPrestador ? `/provider-order/${order.id}` : `/request/${order.id}`) as never);
  }

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Ver orden ${order.id}`}
      onPress={open}
      style={({ pressed }) => [styles.card, pressed && styles.cardPressed]}
    >
      <View style={styles.cardHeader}>
        <View style={styles.cardTitleWrap}>
          <Text style={styles.cardTitle}>#{order.id}</Text>
          <Text style={styles.cardDate}>{dateTime(order.created_at)}</Text>
        </View>
        <View style={[styles.statusPill, { backgroundColor: pill.backgroundColor }]}>
          <Text style={[styles.statusText, { color: pill.color }]}>{order.status}</Text>
        </View>
      </View>

      <View style={styles.cardBody}>
        <View style={styles.cardRow}>
          <Text style={styles.rowLabel}>{isPrestador ? 'Cliente' : 'Prestador'}</Text>
          <View style={styles.rowValueWrap}>
            <Text style={styles.rowValueStrong}>{thirdName}</Text>
            {thirdContact ? <Text style={styles.rowSubtle}>{thirdContact}</Text> : null}
          </View>
        </View>

        <View style={styles.cardRow}>
          <Text style={styles.rowLabel}>Tipo / Rubro</Text>
          <View style={styles.rowValueWrap}>
            <View style={styles.typePill}>
              <Text style={styles.typePillText}>
                {TYPE_LABELS[order.order_type] ?? order.order_type}
              </Text>
            </View>
            <Text style={styles.subgroup}>
              {order.sub_group?.description ?? 'Servicio Hogar'}
            </Text>
          </View>
        </View>

        <View style={styles.cardRow}>
          <Text style={styles.rowLabel}>Problema / Detalle</Text>
          <Text style={styles.rowDetail}>
            {order.description?.trim() ? order.description : 'Sin detalle especificado'}
          </Text>
        </View>

        <View style={styles.moneyRow}>
          <View style={styles.moneyBox}>
            <Text style={styles.moneyLabel}>Monto Total</Text>
            <Text style={styles.moneyValue}>{money(order.total_amount)}</Text>
          </View>
          <View style={styles.moneyBox}>
            <Text style={styles.moneyLabel}>Seña Custodia</Text>
            <Text style={styles.depositValue}>{money(order.deposit_amount)}</Text>
            <Text style={styles.escrowText}>{order.escrow_status ?? 'NONE'}</Text>
          </View>
        </View>
      </View>
    </Pressable>
  );
}

export default function ReportsHogarScreen() {
  const [report, setReport] = useState<ReportHogarResponse | null>(null);
  const [draft, setDraft] = useState<Filters>(defaultFilters);
  const [applied, setApplied] = useState<Filters>(defaultFilters);
  const [picker, setPicker] = useState<'from' | 'to' | null>(null);
  const [loading, setLoading] = useState(true);
  const [fetching, setFetching] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [pdfBusy, setPdfBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (filters: Filters, mode: 'initial' | 'filter' | 'refresh') => {
    if (mode === 'initial') {
      setLoading(true);
    } else if (mode === 'filter') {
      setFetching(true);
    } else {
      setRefreshing(true);
    }

    try {
      const response = await api.reportHogar(toParams(filters));
      setReport(response);
      setError(null);
    } catch (caught) {
      setError(errorMessage(caught));
    } finally {
      if (mode === 'initial') {
        setLoading(false);
      } else if (mode === 'filter') {
        setFetching(false);
      } else {
        setRefreshing(false);
      }
    }
  }, []);

  useEffect(() => {
    let active = true;

    async function loadInitial() {
      try {
        const response = await api.reportHogar({});

        if (active) {
          setReport(response);
          setError(null);
        }
      } catch (caught) {
        if (active) {
          setError(errorMessage(caught));
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    void loadInitial();

    return () => {
      active = false;
    };
  }, []);

  function applyFilters() {
    setApplied(draft);
    void load(draft, 'filter');
  }

  function clearFilters() {
    const next = defaultFilters();
    setDraft(next);
    setApplied(next);
    void load(next, 'filter');
  }

  async function handleRefresh() {
    await load(applied, 'refresh');
  }

  async function handlePdf() {
    if (!report || pdfBusy) {
      return;
    }

    setPdfBusy(true);
    setError(null);

    try {
      await exportReportHogarPdf({
        isPrestador: report.is_prestador,
        kpis: report.kpis,
        orders: report.data,
        filters: {
          fecha_desde: applied.fechaDesde || null,
          fecha_hasta: applied.fechaHasta || null,
          estado: applied.estado,
          tipo: applied.tipo,
        },
      });
    } catch (caught) {
      setError(
        caught instanceof Error
          ? caught.message
          : 'No se pudo generar el PDF del reporte.',
      );
    } finally {
      setPdfBusy(false);
    }
  }

  if (loading) {
    return <LoadingScreen message="Cargando reportes del hogar…" />;
  }

  const isPrestador = Boolean(report?.is_prestador);
  const kpis = report?.kpis;
  const orders = report?.data ?? [];

  const listHeader = (
    <View>
      <ErrorBanner message={error} />

      <View style={styles.filtersCard}>
        <View style={styles.filterGrid}>
          <View style={styles.filterCol}>
            <Text style={styles.filterLabel}>Fecha Desde</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Elegir fecha desde"
              onPress={() => setPicker('from')}
              style={styles.dateInput}
            >
              <Text
                style={[styles.dateText, !draft.fechaDesde && styles.datePlaceholder]}
                numberOfLines={1}
              >
                {displayDate(draft.fechaDesde)}
              </Text>
            </Pressable>
          </View>
          <View style={styles.filterCol}>
            <Text style={styles.filterLabel}>Fecha Hasta</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Elegir fecha hasta"
              onPress={() => setPicker('to')}
              style={styles.dateInput}
            >
              <Text
                style={[styles.dateText, !draft.fechaHasta && styles.datePlaceholder]}
                numberOfLines={1}
              >
                {displayDate(draft.fechaHasta)}
              </Text>
            </Pressable>
          </View>
        </View>

        <View style={styles.filterGrid}>
          <View style={styles.filterCol}>
            <ComboField
              label="Estado"
              value={draft.estado}
              options={ESTADO_OPTIONS}
              onChange={(value) => setDraft((current) => ({ ...current, estado: value }))}
              allowEmpty
              emptyLabel="Todos los estados"
              onClear={() => setDraft((current) => ({ ...current, estado: null }))}
              title="Estado"
            />
          </View>
          <View style={styles.filterCol}>
            <ComboField
              label="Modalidad"
              value={draft.tipo}
              options={TIPO_OPTIONS}
              onChange={(value) => setDraft((current) => ({ ...current, tipo: value }))}
              allowEmpty
              emptyLabel="Todas las modalidades"
              onClear={() => setDraft((current) => ({ ...current, tipo: null }))}
              title="Modalidad"
            />
          </View>
        </View>

        <View style={styles.filterActions}>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Filtrar reportes"
            disabled={fetching}
            onPress={applyFilters}
            style={[styles.applyButton, fetching && styles.buttonDisabled]}
          >
            {fetching ? (
              <ActivityIndicator color={colors.surface} size="small" />
            ) : (
              <Text style={styles.applyText}>Filtrar</Text>
            )}
          </Pressable>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Limpiar filtros"
            disabled={fetching}
            onPress={clearFilters}
            style={[styles.clearButton, fetching && styles.buttonDisabled]}
          >
            <Text style={styles.clearText}>Limpiar</Text>
          </Pressable>
        </View>
      </View>

      {kpis ? (
        <View style={styles.kpiGrid}>
          <KpiCard
            label={isPrestador ? 'Total Facturado' : 'Total Gastado'}
            value={money(kpis.total_economico)}
            accent={ACCENT}
          />
          <KpiCard
            label="Seña en Custodia"
            value={money(kpis.senas_custodia)}
            accent="#f59e0b"
          />
          <KpiCard
            label="Servicios Finalizados"
            value={String(kpis.completados)}
            accent="#10b981"
          />
          {isPrestador ? (
            <>
              <KpiCard
                label="Efectividad Cotizaciones"
                value={`${kpis.efectividad_cotizaciones ?? 0}%`}
                accent="#3b82f6"
              />
              <KpiCard
                label="Trabajos Pendientes"
                value={String(kpis.pendientes ?? 0)}
                hint={`(${kpis.en_proceso ?? 0} en proceso)`}
                accent="#6366f1"
              />
            </>
          ) : null}
          <KpiCard
            label="Reputación / Satisfacción"
            value={`★ ${kpis.promedio_reputacion ?? 0}`}
            hint={`(${kpis.total_resenas ?? 0} reseñas)`}
            accent="#ec4899"
          />
          <KpiCard label="Cancelaciones" value={String(kpis.cancelados)} accent="#ef4444" />
        </View>
      ) : null}

      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>Detalle de Órdenes y Solicitudes Hogar</Text>
        {report ? (
          <Text style={styles.sectionCount}>
            {report.meta.total} {report.meta.total === 1 ? 'registro' : 'registros'}
          </Text>
        ) : null}
      </View>
    </View>
  );

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

      <View style={styles.titleRow}>
        <View style={styles.titleWrap}>
          <Text style={styles.title}>Reportes Hogar</Text>
          <Text style={styles.subtitle}>
            Consolidado de servicios, métricas financieras y estados del Módulo Hogar
          </Text>
        </View>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Exportar a PDF"
          disabled={!report || pdfBusy}
          onPress={handlePdf}
          style={[styles.pdfButton, (!report || pdfBusy) && styles.buttonDisabled]}
        >
          {pdfBusy ? (
            <ActivityIndicator color={colors.surface} size="small" />
          ) : (
            <Text style={styles.pdfIcon}>⤓</Text>
          )}
          <Text style={styles.pdfText}>{pdfBusy ? 'Generando…' : 'Exportar a PDF'}</Text>
        </Pressable>
      </View>

      <FlatList
        data={orders}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.list}
        keyboardShouldPersistTaps="handled"
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={colors.brand}
            colors={[colors.brand]}
          />
        }
        ListHeaderComponent={listHeader}
        ListEmptyComponent={
          report ? (
            <View style={styles.empty}>
              <Text style={styles.emptyText}>
                No se encontraron registros de servicios para el hogar con los filtros
                seleccionados.
              </Text>
            </View>
          ) : (
            <View style={styles.empty}>
              <Text style={styles.emptyText}>
                No se pudo cargar el reporte. Revisá tu conexión e intentá de nuevo.
              </Text>
              <Pressable
                accessibilityRole="button"
                onPress={() => void load(applied, 'initial')}
                style={styles.retryButton}
              >
                <Text style={styles.retryText}>Reintentar</Text>
              </Pressable>
            </View>
          )
        }
        renderItem={({ item }) => <OrderCard order={item} isPrestador={isPrestador} />}
      />

      <DatePickerModal
        key={picker ?? 'closed'}
        visible={picker !== null}
        title={picker === 'from' ? 'Fecha desde' : 'Fecha hasta'}
        initial={picker === 'from' ? draft.fechaDesde : draft.fechaHasta}
        onPick={(value) => {
          setDraft((current) =>
            picker === 'from'
              ? { ...current, fechaDesde: value }
              : { ...current, fechaHasta: value },
          );
          setPicker(null);
        }}
        onClear={() => {
          setDraft((current) =>
            picker === 'from'
              ? { ...current, fechaDesde: '' }
              : { ...current, fechaHasta: '' },
          );
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
  titleRow: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md,
    paddingTop: spacing.xs,
  },
  titleWrap: {
    flex: 1,
    minWidth: 0,
  },
  title: {
    color: NAVY,
    fontSize: 20,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 12,
    lineHeight: 17,
    marginTop: 3,
  },
  pdfButton: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: 6,
    flexDirection: 'row',
    gap: 6,
    paddingHorizontal: spacing.md,
    paddingVertical: 9,
  },
  pdfIcon: {
    color: colors.surface,
    fontSize: 14,
    fontWeight: '800',
  },
  pdfText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
  buttonDisabled: {
    opacity: 0.55,
  },
  list: {
    paddingBottom: spacing.xxl,
    paddingHorizontal: spacing.lg,
  },
  filtersCard: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.lg,
  },
  filterGrid: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  filterCol: {
    flex: 1,
    minWidth: 0,
  },
  filterLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
    marginBottom: spacing.xs,
    textTransform: 'uppercase',
  },
  dateInput: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
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
  filterActions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  applyButton: {
    alignItems: 'center',
    backgroundColor: NAVY,
    borderRadius: 6,
    flex: 1,
    justifyContent: 'center',
    minHeight: 42,
    paddingHorizontal: spacing.lg,
  },
  applyText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '700',
  },
  clearButton: {
    alignItems: 'center',
    backgroundColor: '#f1f5f9',
    borderColor: '#cbd5e1',
    borderRadius: 6,
    borderWidth: 1,
    flex: 1,
    justifyContent: 'center',
    minHeight: 42,
    paddingHorizontal: spacing.lg,
  },
  clearText: {
    color: '#475569',
    fontSize: 13,
    fontWeight: '700',
  },
  kpiGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: spacing.lg,
    rowGap: spacing.md,
  },
  kpiCard: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderLeftWidth: 4,
    borderRadius: 8,
    borderWidth: 1,
    padding: spacing.lg,
    width: '48%',
  },
  kpiLabel: {
    color: colors.muted,
    fontSize: 10,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  kpiValue: {
    color: NAVY,
    fontSize: 20,
    fontWeight: '800',
    marginTop: 6,
  },
  kpiHint: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '400',
  },
  sectionHeader: {
    alignItems: 'baseline',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  sectionTitle: {
    color: NAVY,
    flex: 1,
    fontSize: 14,
    fontWeight: '700',
  },
  sectionCount: {
    color: colors.muted,
    fontSize: 11,
    marginLeft: spacing.sm,
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
    opacity: 0.75,
  },
  cardHeader: {
    alignItems: 'center',
    backgroundColor: '#f8fafc',
    borderBottomColor: '#e5e7eb',
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: 11,
  },
  cardTitleWrap: {
    flex: 1,
    minWidth: 0,
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
    marginTop: 2,
  },
  statusPill: {
    borderRadius: radius.pill,
    paddingHorizontal: 10,
    paddingVertical: 3,
  },
  statusText: {
    fontSize: 10,
    fontWeight: '800',
  },
  cardBody: {
    padding: spacing.md,
  },
  cardRow: {
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.sm,
  },
  rowLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
    width: 104,
  },
  rowValueWrap: {
    flex: 1,
    minWidth: 0,
  },
  rowValueStrong: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
  },
  rowSubtle: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 1,
  },
  rowDetail: {
    color: '#475569',
    flex: 1,
    fontSize: 12,
    lineHeight: 18,
  },
  typePill: {
    alignSelf: 'flex-start',
    backgroundColor: '#e0f2fe',
    borderRadius: 4,
    paddingHorizontal: 8,
    paddingVertical: 2,
  },
  typePillText: {
    color: '#0369a1',
    fontSize: 10,
    fontWeight: '700',
  },
  subgroup: {
    color: '#1e293b',
    fontSize: 12,
    fontWeight: '600',
    marginTop: 3,
  },
  moneyRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  moneyBox: {
    backgroundColor: '#f8fafc',
    borderRadius: radius.sm,
    flex: 1,
    padding: spacing.sm,
  },
  moneyLabel: {
    color: colors.muted,
    fontSize: 10,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  moneyValue: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '800',
    marginTop: 3,
  },
  depositValue: {
    color: '#d97706',
    fontSize: 15,
    fontWeight: '800',
    marginTop: 3,
  },
  escrowText: {
    color: colors.muted,
    fontSize: 9,
    fontWeight: '600',
    marginTop: 1,
  },
  empty: {
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.xxl,
  },
  emptyText: {
    color: '#94a3b8',
    fontSize: 13,
    fontStyle: 'italic',
    lineHeight: 19,
    textAlign: 'center',
  },
  retryButton: {
    backgroundColor: NAVY,
    borderRadius: 6,
    marginTop: spacing.lg,
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.sm,
  },
  retryText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '700',
  },
});
