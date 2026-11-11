import { router } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { MonthPickerModal } from '@/components/MonthPickerModal';
import { money } from '@/lib/money';
import {
  exportReportComercioPdf,
  type ReportComercioPdfData,
} from '@/lib/reportComercioPdf';
import { colors, radius, spacing } from '@/theme';
import type {
  ReportComercioHistoryResponse,
  ReportComercioNutritionResponse,
  ReportComercioSavingsResponse,
  ReportComercioSubscription,
  ReportComercioSubscriptionsResponse,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';
const GREEN = '#047857';
const AMBER = '#b45309';

type TabKey = 'savings' | 'mysubs' | 'history' | 'nutrition';

const TABS: { key: TabKey; label: string; icon: string }[] = [
  { key: 'savings', label: 'Mi ahorro', icon: '🏷️' },
  { key: 'mysubs', label: 'Suscripciones activas', icon: '🔄' },
  { key: 'history', label: 'Histórico y hábitos', icon: '📋' },
  { key: 'nutrition', label: 'Resumen nutricional', icon: '🤍' },
];

const MONTHS = [
  'Enero',
  'Febrero',
  'Marzo',
  'Abril',
  'Mayo',
  'Junio',
  'Julio',
  'Agosto',
  'Septiembre',
  'Octubre',
  'Noviembre',
  'Diciembre',
];

const FREQUENCY_LABELS: Record<string, string> = {
  DAILY: 'cada día',
  WEEKLY: 'cada semana',
  BIWEEKLY: 'cada dos semanas',
  MONTHLY: 'cada mes',
};

const DAY_LABELS: Record<number, string> = {
  1: 'Lunes',
  2: 'Martes',
  3: 'Miércoles',
  4: 'Jueves',
  5: 'Viernes',
  6: 'Sábado',
  7: 'Domingo',
};

type TabData = {
  savings: ReportComercioSavingsResponse | null;
  mysubs: ReportComercioSubscriptionsResponse | null;
  history: ReportComercioHistoryResponse | null;
  nutrition: ReportComercioNutritionResponse | null;
};

function errorMessage(caught: unknown): string {
  return caught instanceof ApiError
    ? caught.message
    : 'No se pudo cargar el reporte. Revisá tu conexión e intentá de nuevo.';
}

function shortDate(iso: string | null | undefined): string {
  if (!iso) {
    return '—';
  }

  const [y, m, d] = String(iso).slice(0, 10).split('-');

  return y && m && d ? `${d}/${m}/${y}` : '—';
}

function monthLabel(value: string): string {
  const match = /^(\d{4})-(\d{2})$/.exec(value);

  if (!match) {
    return 'Mes actual';
  }

  const index = Number(match[2]) - 1;
  const name = MONTHS[index] ?? match[2];

  return `${name} ${match[1]}`;
}

function openShop(
  providerId: number | null,
  product?: { product_id: number; quantity: number },
) {
  if (!providerId) {
    return;
  }

  router.push({
    pathname: '/comercio/[id]',
    params: {
      id: String(providerId),
      ...(product
        ? {
            repeat: JSON.stringify([
              { product_id: product.product_id, variant_id: null, quantity: product.quantity },
            ]),
          }
        : {}),
    },
  });
}

function KpiCard({
  label,
  value,
  hint,
  accent,
  valueColor,
}: {
  label: string;
  value: string;
  hint?: string;
  accent: string;
  valueColor?: string;
}) {
  return (
    <View style={[styles.kpiCard, { borderLeftColor: accent }]}>
      <Text style={styles.kpiLabel}>{label}</Text>
      <Text style={[styles.kpiValue, valueColor ? { color: valueColor } : null]}>{value}</Text>
      {hint ? <Text style={styles.kpiHint}>{hint}</Text> : null}
    </View>
  );
}

function SectionTitle({ title, hint }: { title: string; hint?: string }) {
  return (
    <View style={styles.sectionTitleRow}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {hint ? <Text style={styles.sectionHint}>{hint}</Text> : null}
    </View>
  );
}

function EmptyState({ text }: { text: string }) {
  return (
    <View style={styles.emptyDashed}>
      <Text style={styles.emptyText}>{text}</Text>
    </View>
  );
}

function SubscriptionCard({ subscription }: { subscription: ReportComercioSubscription }) {
  const active = subscription.status === 'ACTIVE';
  const nextCharge = subscription.next_charge
    ? `Cobro ${money(subscription.next_charge.total)} el ${shortDate(subscription.next_charge.period_start)}`
    : 'Sin cobro pendiente';
  const payment =
    subscription.payment_method === 'cash'
      ? 'Efectivo'
      : subscription.payment_method ?? '—';

  return (
    <View
      style={[
        styles.subCard,
        { borderLeftColor: active ? ACCENT : '#f59e0b' },
      ]}
    >
      <View style={styles.subHeader}>
        <View style={styles.subTitleWrap}>
          <Text style={styles.subTitle}>{subscription.plan_title ?? 'Plan'}</Text>
          {subscription.provider ? (
            <Text style={styles.subProvider}>{subscription.provider}</Text>
          ) : null}
        </View>
        <View
          style={[
            styles.pill,
            { backgroundColor: active ? '#d1fae5' : '#fef3c7' },
          ]}
        >
          <Text style={[styles.pillText, { color: active ? GREEN : AMBER }]}>
            {subscription.status_label || subscription.status}
          </Text>
        </View>
      </View>

      <View style={styles.subAmountRow}>
        <Text style={styles.subAmount}>{money(subscription.charge_amount)}</Text>
        <Text style={styles.subFrequency}>
          {FREQUENCY_LABELS[subscription.frequency ?? ''] ?? ''}
        </Text>
      </View>

      <View style={styles.subGrid}>
        <View style={styles.subField}>
          <Text style={styles.fieldLabel}>Próxima entrega</Text>
          <Text style={styles.fieldValue}>{shortDate(subscription.next_delivery_date)}</Text>
        </View>
        <View style={styles.subField}>
          <Text style={styles.fieldLabel}>Día preferido</Text>
          <Text style={styles.fieldValue}>
            {DAY_LABELS[subscription.preferred_delivery_day ?? 0] ?? 'Sin preferencia'}
          </Text>
        </View>
        <View style={styles.subField}>
          <Text style={styles.fieldLabel}>Horario</Text>
          <Text style={styles.fieldValue}>
            {subscription.preferred_delivery_time ?? 'Sin preferencia'}
          </Text>
        </View>
        <View style={styles.subField}>
          <Text style={styles.fieldLabel}>Pago</Text>
          <Text style={styles.fieldValue}>{payment}</Text>
        </View>
        <View style={styles.subField}>
          <Text style={styles.fieldLabel}>Cobro</Text>
          <Text style={styles.fieldValue}>{nextCharge}</Text>
        </View>
      </View>

      {subscription.products.length ? (
        <Text style={styles.subProducts}>
          <Text style={styles.fieldLabel}>Productos: </Text>
          {subscription.products
            .map((product) => `${product.name ?? ''} x${Number(product.quantity || 0)}`)
            .join(' · ')}
        </Text>
      ) : null}
    </View>
  );
}

export default function ReportsComercioScreen() {
  const { user } = useAuth();

  const [activeTab, setActiveTab] = useState<TabKey>('savings');
  const [data, setData] = useState<TabData>({
    savings: null,
    mysubs: null,
    history: null,
    nutrition: null,
  });
  const [loading, setLoading] = useState<Record<TabKey, boolean>>({
    savings: true,
    mysubs: false,
    history: false,
    nutrition: false,
  });
  const [errors, setErrors] = useState<Record<TabKey, string | null>>({
    savings: null,
    mysubs: null,
    history: null,
    nutrition: null,
  });
  const [refreshing, setRefreshing] = useState(false);
  const [pdfBusy, setPdfBusy] = useState(false);
  const [month, setMonth] = useState('');
  const [monthPicker, setMonthPicker] = useState(false);

  const fetchTab = useCallback(async (tab: TabKey, requestedMonth?: string) => {
    try {
      if (tab === 'savings') {
        const response = await api.reportComercioSavings();
        setData((current) => ({ ...current, savings: response }));
      } else if (tab === 'mysubs') {
        const response = await api.reportComercioSubscriptions();
        setData((current) => ({ ...current, mysubs: response }));
      } else if (tab === 'history') {
        const response = await api.reportComercioHistory();
        setData((current) => ({ ...current, history: response }));
      } else {
        const response = await api.reportComercioNutrition(
          requestedMonth ? { month: requestedMonth } : {},
        );
        setData((current) => ({ ...current, nutrition: response }));
        setMonth(response.period.month);
      }

      setErrors((current) => ({ ...current, [tab]: null }));
    } catch (caught) {
      setErrors((current) => ({ ...current, [tab]: errorMessage(caught) }));
    } finally {
      setLoading((current) => ({ ...current, [tab]: false }));
    }
  }, []);

  useEffect(() => {
    void fetchTab('savings');
  }, [fetchTab]);

  function selectTab(tab: TabKey) {
    setActiveTab(tab);

    if (data[tab] || loading[tab]) {
      return;
    }

    setErrors((current) => ({ ...current, [tab]: null }));
    setLoading((current) => ({ ...current, [tab]: true }));
    void fetchTab(tab, tab === 'nutrition' && month ? month : undefined);
  }

  async function handleRefresh() {
    setRefreshing(true);
    await fetchTab(
      activeTab,
      activeTab === 'nutrition' && month ? month : undefined,
    );
    setRefreshing(false);
  }

  function retry() {
    setErrors((current) => ({ ...current, [activeTab]: null }));
    setLoading((current) => ({ ...current, [activeTab]: true }));
    void fetchTab(
      activeTab,
      activeTab === 'nutrition' && month ? month : undefined,
    );
  }

  function pickMonth(value: string) {
    setMonthPicker(false);
    setMonth(value);
    setErrors((current) => ({ ...current, nutrition: null }));
    setLoading((current) => ({ ...current, nutrition: true }));
    void fetchTab('nutrition', value);
  }

  function pickCurrentMonth() {
    setMonthPicker(false);
    setMonth('');
    setErrors((current) => ({ ...current, nutrition: null }));
    setLoading((current) => ({ ...current, nutrition: true }));
    void fetchTab('nutrition');
  }

  function currentPdfData(): ReportComercioPdfData | null {
    if (activeTab === 'savings' && data.savings) {
      return { tab: 'savings', data: data.savings };
    }

    if (activeTab === 'mysubs' && data.mysubs) {
      return { tab: 'mysubs', data: data.mysubs };
    }

    if (activeTab === 'history' && data.history) {
      return { tab: 'history', data: data.history };
    }

    if (activeTab === 'nutrition' && data.nutrition) {
      return {
        tab: 'nutrition',
        data: data.nutrition,
        month: data.nutrition.period.month,
      };
    }

    return null;
  }

  async function handlePdf() {
    const payload = currentPdfData();

    if (!payload || pdfBusy) {
      return;
    }

    setPdfBusy(true);

    try {
      await exportReportComercioPdf(payload, user?.name);
    } catch (caught) {
      setErrors((current) => ({
        ...current,
        [activeTab]:
          caught instanceof Error
            ? caught.message
            : 'No se pudo generar el PDF del reporte.',
      }));
    } finally {
      setPdfBusy(false);
    }
  }

  function renderSavings(report: ReportComercioSavingsResponse) {
    const summary = report.summary;

    return (
      <>
        <View style={styles.kpiGrid}>
          <KpiCard
            label="Total ahorrado"
            value={money(summary.total_saved)}
            hint="Vs. comprar por separado"
            accent={GREEN}
            valueColor={GREEN}
          />
          <KpiCard
            label="Por suscripciones"
            value={money(summary.saved_with_subscriptions)}
            accent={ACCENT}
          />
          <KpiCard
            label="Por promociones"
            value={money(summary.saved_with_promotions)}
            accent="#f59e0b"
          />
          <KpiCard
            label="Ciclos suscriptos pagados"
            value={String(summary.paid_subscription_cycles)}
            accent="#3b82f6"
          />
          <KpiCard
            label="Pedidos con descuento"
            value={String(summary.orders_with_discount)}
            accent="#6366f1"
          />
        </View>

        <View style={styles.banner}>
          <Text style={styles.bannerText}>
            Comprar con suscripción y promociones te dejó{' '}
            <Text style={styles.bannerStrong}>{money(summary.total_saved)}</Text> frente a los
            precios Express del mismo carrito.
          </Text>
        </View>

        <SectionTitle title="Suscripciones que generan ahorro" />

        {report.by_subscription.length === 0 ? (
          <EmptyState text="Todavía no tenés suscripciones generando ahorro." />
        ) : (
          report.by_subscription.map((subscription) => (
            <View key={subscription.subscription_id} style={styles.rowCard}>
              <Text style={styles.rowStrong}>{subscription.plan_title ?? 'Plan'}</Text>
              <Text style={styles.rowMuted}>{subscription.provider ?? '—'}</Text>
            </View>
          ))
        )}
      </>
    );
  }

  function renderSubscriptions(report: ReportComercioSubscriptionsResponse) {
    const summary = report.summary;

    return (
      <>
        <View style={styles.kpiGrid}>
          <KpiCard
            label="Activas"
            value={String(summary.active_count)}
            accent={GREEN}
            valueColor={GREEN}
          />
          <KpiCard
            label="Pausadas"
            value={String(summary.paused_count)}
            accent="#f59e0b"
            valueColor={AMBER}
          />
          <KpiCard
            label="Compromiso mensual"
            value={money(summary.monthly_commitment)}
            accent={ACCENT}
            valueColor={ACCENT}
          />
        </View>

        {report.subscriptions.length === 0 ? (
          <EmptyState text="No tenés suscripciones activas o pausadas. Armá un plan desde Comercios." />
        ) : (
          report.subscriptions.map((subscription) => (
            <SubscriptionCard
              key={subscription.subscription_id}
              subscription={subscription}
            />
          ))
        )}
      </>
    );
  }

  function renderHistory(report: ReportComercioHistoryResponse) {
    const summary = report.summary;

    return (
      <>
        <View style={styles.kpiGrid}>
          <KpiCard
            label="Pedidos realizados"
            value={String(summary.total_orders)}
            accent={ACCENT}
          />
          <KpiCard
            label="Total gastado"
            value={money(summary.total_spent)}
            accent="#f59e0b"
          />
          <KpiCard
            label="Tu plato favorito"
            value={summary.favorite_dish ?? '—'}
            hint="El que más pediste"
            accent="#10b981"
          />
          <KpiCard
            label="Tu comercio favorito"
            value={summary.favorite_provider ?? '—'}
            accent="#6366f1"
          />
        </View>

        <SectionTitle title="Platos más pedidos" hint="(reordenables en 1 clic)" />

        {report.top_products.length === 0 ? (
          <EmptyState text="Todavía no tenés pedidos en tu historial." />
        ) : (
          report.top_products.map((product) => (
            <View key={product.product_id} style={styles.productCard}>
              <View style={styles.productInfo}>
                <Text style={styles.rowStrong}>{product.name ?? '—'}</Text>
                <Text style={styles.rowMuted}>
                  {product.times_ordered} {product.times_ordered === 1 ? 'vez' : 'veces'} ·{' '}
                  {product.total_quantity} unidades · {money(product.total_spent)}
                </Text>
                <Text style={styles.rowSubtle}>
                  Última vez: {shortDate(product.last_ordered_at)}
                </Text>
              </View>
              {product.can_reorder_1_click && product.provider_id ? (
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Reordenar ${product.name ?? 'producto'}`}
                  onPress={() =>
                    openShop(product.provider_id, {
                      product_id: product.product_id,
                      quantity: 1,
                    })
                  }
                  style={styles.reorderButton}
                >
                  <Text style={styles.reorderText}>Reordenar</Text>
                </Pressable>
              ) : null}
            </View>
          ))
        )}

        <SectionTitle title="Comercios más frecuentes" />

        {report.top_providers.length === 0 ? (
          <EmptyState text="Todavía no tenés comercios frecuentes." />
        ) : (
          report.top_providers.map((provider) => (
            <View key={provider.provider_id} style={styles.productCard}>
              <View style={styles.productInfo}>
                <Text style={styles.rowStrong}>{provider.business_name ?? '—'}</Text>
                <Text style={styles.rowMuted}>
                  {provider.orders_count}{' '}
                  {provider.orders_count === 1 ? 'pedido' : 'pedidos'} ·{' '}
                  {money(provider.total_spent)}
                </Text>
                <Text style={styles.rowSubtle}>
                  Último pedido: {shortDate(provider.last_order_at)}
                </Text>
              </View>
              {provider.can_reorder_1_click ? (
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Reordenar en ${provider.business_name ?? 'comercio'}`}
                  onPress={() => openShop(provider.provider_id)}
                  style={styles.reorderButton}
                >
                  <Text style={styles.reorderText}>Pedir de nuevo</Text>
                </Pressable>
              ) : null}
            </View>
          ))
        )}
      </>
    );
  }

  function renderNutrition(report: ReportComercioNutritionResponse) {
    const summary = report.summary;

    return (
      <>
        <View style={styles.monthCard}>
          <Text style={styles.filterLabel}>Mes</Text>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Elegir mes del resumen nutricional"
            onPress={() => setMonthPicker(true)}
            style={styles.monthInput}
          >
            <Text style={styles.monthText}>{monthLabel(month || report.period.month)}</Text>
            <Text style={styles.monthChevron}>▾</Text>
          </Pressable>
        </View>

        <View style={styles.kpiGrid}>
          <KpiCard
            label="Pedidos entregados"
            value={String(summary.total_delivered_orders)}
            accent={ACCENT}
          />
          <KpiCard
            label="Comidas recibidas"
            value={String(summary.total_meals)}
            accent="#f59e0b"
            valueColor={ACCENT}
          />
          <KpiCard
            label="Gastado en el mes"
            value={money(summary.total_spent)}
            accent="#6366f1"
          />
          <KpiCard
            label="Categoría principal"
            value={summary.main_category ?? '—'}
            accent={GREEN}
            valueColor={GREEN}
          />
        </View>

        <SectionTitle title="Qué comiste este mes" />

        {report.categories.length === 0 ? (
          <EmptyState text="Todavía no recibiste comidas en el mes seleccionado." />
        ) : (
          report.categories.map((category) => (
            <View
              key={`${category.category_id ?? 'none'}`}
              style={styles.rowCard}
            >
              <View style={styles.categoryHeader}>
                <Text style={styles.rowStrong}>{category.category_name ?? '—'}</Text>
                <Text style={styles.rowMuted}>{money(category.total_spent)}</Text>
              </View>
              <Text style={styles.rowMuted}>
                {category.meals_count} comidas · {category.total_quantity} unidades
              </Text>
              <Text style={styles.rowSubtle}>
                {category.top_products.length
                  ? category.top_products
                      .map((product) => `${product.name} x${product.quantity}`)
                      .join(' · ')
                  : '—'}
              </Text>
            </View>
          ))
        )}
      </>
    );
  }

  function renderActiveTab() {
    const tab = activeTab;
    const tabData = data[tab];
    const isLoading = loading[tab];

    if (isLoading && !tabData) {
      return (
        <View style={styles.stateBox}>
          <ActivityIndicator color={colors.brand} />
          <Text style={styles.stateText}>Cargando reporte…</Text>
        </View>
      );
    }

    if (!tabData) {
      return (
        <View style={styles.stateBox}>
          <Text style={styles.stateText}>
            {errors[tab] ?? 'No se pudo cargar el reporte.'}
          </Text>
          <Pressable
            accessibilityRole="button"
            onPress={retry}
            style={styles.retryButton}
          >
            <Text style={styles.retryText}>Reintentar</Text>
          </Pressable>
        </View>
      );
    }

    if (tab === 'savings') {
      return renderSavings(tabData as ReportComercioSavingsResponse);
    }

    if (tab === 'mysubs') {
      return renderSubscriptions(tabData as ReportComercioSubscriptionsResponse);
    }

    if (tab === 'history') {
      return renderHistory(tabData as ReportComercioHistoryResponse);
    }

    return renderNutrition(tabData as ReportComercioNutritionResponse);
  }

  if (loading.savings && !data.savings) {
    return <LoadingScreen message="Cargando reportes del comercio…" />;
  }

  const pdfData = currentPdfData();

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
        <Text style={styles.title}>Reportes Comercio</Text>
        <Text style={styles.subtitle}>
          Tu ahorro, tus suscripciones y tus hábitos de compra
        </Text>
      </View>

      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.tabsTrack}
        style={styles.tabsScroll}
      >
        {TABS.map((tab) => {
          const active = tab.key === activeTab;

          return (
            <Pressable
              key={tab.key}
              accessibilityRole="button"
              accessibilityState={{ selected: active }}
              onPress={() => selectTab(tab.key)}
              style={[styles.tab, active && styles.tabActive]}
            >
              <Text style={[styles.tabText, active && styles.tabTextActive]}>
                {tab.icon} {tab.label}
              </Text>
            </Pressable>
          );
        })}
      </ScrollView>

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
        style={styles.scroll}
      >
        <ErrorBanner message={errors[activeTab]} />
        {renderActiveTab()}
      </ScrollView>

      <View style={styles.pdfBar}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Exportar reporte a PDF"
          disabled={!pdfData || pdfBusy}
          onPress={handlePdf}
          style={[styles.pdfButton, (!pdfData || pdfBusy) && styles.buttonDisabled]}
        >
          {pdfBusy ? (
            <ActivityIndicator color={colors.surface} size="small" />
          ) : (
            <Text style={styles.pdfIcon}>🖨️</Text>
          )}
          <Text style={styles.pdfText}>
            {pdfBusy ? 'Generando…' : 'PDF / Imprimir'}
          </Text>
        </Pressable>
      </View>

      <MonthPickerModal
        key={monthPicker ? 'open' : 'closed'}
        visible={monthPicker}
        title="Mes del resumen"
        initial={month || data.nutrition?.period.month || ''}
        onPick={pickMonth}
        onClear={pickCurrentMonth}
        onClose={() => setMonthPicker(false)}
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
  titleWrap: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md,
    paddingTop: spacing.xs,
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
  tabsScroll: {
    flexGrow: 0,
    marginBottom: spacing.md,
  },
  tabsTrack: {
    gap: spacing.xs,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.xs,
  },
  tab: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  tabActive: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  tabText: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '600',
  },
  tabTextActive: {
    color: colors.surface,
    fontWeight: '800',
  },
  scroll: {
    flex: 1,
  },
  content: {
    paddingBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.xs,
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
    fontSize: 18,
    fontWeight: '800',
    marginTop: 6,
  },
  kpiHint: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 3,
  },
  banner: {
    backgroundColor: '#ecfdf5',
    borderColor: '#a7f3d0',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  bannerText: {
    color: '#065f46',
    fontSize: 12,
    lineHeight: 18,
  },
  bannerStrong: {
    fontWeight: '800',
  },
  sectionTitleRow: {
    alignItems: 'baseline',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  sectionTitle: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '700',
  },
  sectionHint: {
    color: '#94a3b8',
    fontSize: 11,
  },
  rowCard: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  rowStrong: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '700',
  },
  rowMuted: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  rowSubtle: {
    color: '#94a3b8',
    fontSize: 11,
    marginTop: 4,
  },
  emptyDashed: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.md,
    borderStyle: 'dashed',
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.xxl,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
  },
  subCard: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderLeftWidth: 3,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  subHeader: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
  },
  subTitleWrap: {
    flex: 1,
    minWidth: 0,
  },
  subTitle: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '700',
  },
  subProvider: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  pill: {
    borderRadius: radius.pill,
    paddingHorizontal: 10,
    paddingVertical: 3,
  },
  pillText: {
    fontSize: 10,
    fontWeight: '800',
  },
  subAmountRow: {
    alignItems: 'baseline',
    flexDirection: 'row',
    gap: 6,
    marginTop: spacing.sm,
  },
  subAmount: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '800',
  },
  subFrequency: {
    color: colors.muted,
    fontSize: 11,
  },
  subGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: spacing.sm,
    rowGap: spacing.sm,
  },
  subField: {
    width: '50%',
  },
  fieldLabel: {
    color: '#94a3b8',
    fontSize: 11,
  },
  fieldValue: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '600',
    marginTop: 1,
  },
  subProducts: {
    color: '#475569',
    fontSize: 12,
    lineHeight: 18,
    marginTop: spacing.sm,
  },
  productCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  productInfo: {
    flex: 1,
    minWidth: 0,
  },
  reorderButton: {
    backgroundColor: ACCENT,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  reorderText: {
    color: colors.surface,
    fontSize: 11,
    fontWeight: '700',
  },
  categoryHeader: {
    alignItems: 'baseline',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 2,
  },
  monthCard: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.md,
  },
  filterLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
    marginBottom: spacing.xs,
    textTransform: 'uppercase',
  },
  monthInput: {
    alignItems: 'center',
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: 10,
  },
  monthText: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
  },
  monthChevron: {
    color: colors.muted,
    fontSize: 12,
  },
  stateBox: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    padding: spacing.xl,
  },
  stateText: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 19,
    marginTop: spacing.sm,
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
  pdfBar: {
    backgroundColor: colors.surface,
    borderTopColor: colors.border,
    borderTopWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  pdfButton: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: radius.sm,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'center',
    minHeight: 46,
  },
  pdfIcon: {
    color: colors.surface,
    fontSize: 15,
  },
  pdfText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '700',
  },
  buttonDisabled: {
    opacity: 0.55,
  },
});
