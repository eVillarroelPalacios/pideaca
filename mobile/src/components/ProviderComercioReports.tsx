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
import { SectionDrawer } from '@/components/SectionDrawer';
import { money } from '@/lib/money';
import {
  exportProviderComercioPdf,
  type ProviderComercioPdfData,
} from '@/lib/reportProviderPdf';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderCampaignRow,
  ProviderCampaignsResponse,
  ProviderCommissionResponse,
  ProviderForecastResponse,
  ProviderHeatmapResponse,
  ProviderLtvCustomer,
  ProviderLtvResponse,
  ProviderMarginsResponse,
  ProviderMrrResponse,
} from '@/types/api';

// Pantalla de Reportes Comercio para el Prestador: espeja los 7 tabs de la
// web (Rentabilidad, MRR, Comisiones, Clientes, Campanas, Demanda,
// Abastecimiento). Los clientes siguen viendo la pantalla de ahorro en
// reports-comercio.tsx.

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';
const GREEN = '#047857';
const AMBER = '#b45309';
const RED = '#b91c1d';

type ProviderTabKey =
  | 'margins'
  | 'mrr'
  | 'commission'
  | 'ltv'
  | 'campaigns'
  | 'heatmap'
  | 'forecast';

const TABS: { key: ProviderTabKey; label: string; icon: string }[] = [
  { key: 'margins', label: 'Rentabilidad', icon: '📊' },
  { key: 'mrr', label: 'Suscripciones y MRR', icon: '🔄' },
  { key: 'commission', label: 'Comisiones y ahorro', icon: '💸' },
  { key: 'ltv', label: 'Clientes', icon: '👥' },
  { key: 'campaigns', label: 'Campañas WhatsApp', icon: '💬' },
  { key: 'heatmap', label: 'Demanda', icon: '🕒' },
  { key: 'forecast', label: 'Abastecimiento', icon: '📦' },
];

const TRAFFIC_STYLE: Record<string, { color: string; bg: string }> = {
  green: { color: GREEN, bg: '#d1fae5' },
  yellow: { color: AMBER, bg: '#fef3c7' },
  red: { color: RED, bg: '#fecaca' },
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

  return String(iso).slice(0, 10);
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

function trafficPill(level: string, label: string | null) {
  const style = TRAFFIC_STYLE[level] ?? { color: NAVY, bg: '#e5e7eb' };

  return (
    <View style={[styles.pill, { backgroundColor: style.bg }]}>
      <Text style={[styles.pillText, { color: style.color }]}>{label ?? level}</Text>
    </View>
  );
}

function CustomerCard({ customer }: { customer: ProviderLtvCustomer }) {
  return (
    <View style={styles.rowCard}>
      <Text style={styles.rowStrong}>{customer.name ?? '—'}</Text>
      <Text style={styles.rowMuted}>{customer.email ?? '—'}</Text>
      <Text style={styles.rowSubtle}>
        {customer.orders_count} {customer.orders_count === 1 ? 'pedido' : 'pedidos'} · LTV {money(customer.ltv)}
        {customer.days_since_last_order !== null ? ` · inactivo ${customer.days_since_last_order}d` : ''}
      </Text>
      <Text style={styles.rowSubtle}>Última compra: {shortDate(customer.last_order_at)}</Text>
    </View>
  );
}

function CampaignCard({ campaign }: { campaign: ProviderCampaignRow }) {
  return (
    <View style={styles.rowCard}>
      <Text style={styles.rowStrong}>{campaign.rule_type ?? 'Regla'}</Text>
      {campaign.discount_code ? (
        <Text style={styles.rowMuted}>Código: {campaign.discount_code}</Text>
      ) : null}
      <Text style={styles.rowSubtle}>
        {campaign.sent} enviados · {campaign.failed} fallidos · {campaign.converted} convertidos
      </Text>
      <Text style={styles.rowSubtle}>
        Conversión: {campaign.conversion_rate_percent}% · Ventas atribuidas: {money(campaign.attributed_sales)}
      </Text>
    </View>
  );
}

export function ProviderComercioReports() {
  const { user } = useAuth();

  const [activeTab, setActiveTab] = useState<ProviderTabKey>('margins');
  const [margins, setMargins] = useState<ProviderMarginsResponse | null>(null);
  const [mrr, setMrr] = useState<ProviderMrrResponse | null>(null);
  const [commission, setCommission] = useState<ProviderCommissionResponse | null>(null);
  const [ltv, setLtv] = useState<ProviderLtvResponse | null>(null);
  const [campaigns, setCampaigns] = useState<ProviderCampaignsResponse | null>(null);
  const [heatmap, setHeatmap] = useState<ProviderHeatmapResponse | null>(null);
  const [forecast, setForecast] = useState<ProviderForecastResponse | null>(null);
  const [loading, setLoading] = useState<Record<ProviderTabKey, boolean>>({
    margins: true,
    mrr: false,
    commission: false,
    ltv: false,
    campaigns: false,
    heatmap: false,
    forecast: false,
  });
  const [errors, setErrors] = useState<Record<ProviderTabKey, string | null>>({
    margins: null,
    mrr: null,
    commission: null,
    ltv: null,
    campaigns: null,
    heatmap: null,
    forecast: null,
  });
  const [refreshing, setRefreshing] = useState(false);
  const [pdfBusy, setPdfBusy] = useState(false);
  const [sidebarOpen, setSidebarOpen] = useState(false);

  const loaded: Record<ProviderTabKey, boolean> = {
    margins: margins !== null,
    mrr: mrr !== null,
    commission: commission !== null,
    ltv: ltv !== null,
    campaigns: campaigns !== null,
    heatmap: heatmap !== null,
    forecast: forecast !== null,
  };

  const fetchTab = useCallback(async (tab: ProviderTabKey) => {
    try {
      if (tab === 'margins') {
        setMargins(await api.providerReportMargins());
      } else if (tab === 'mrr') {
        setMrr(await api.providerReportMrr());
      } else if (tab === 'commission') {
        setCommission(await api.providerReportCommission());
      } else if (tab === 'ltv') {
        setLtv(await api.providerReportLtv());
      } else if (tab === 'campaigns') {
        setCampaigns(await api.providerReportCampaigns());
      } else if (tab === 'heatmap') {
        setHeatmap(await api.providerReportHeatmap());
      } else {
        setForecast(await api.providerReportForecast());
      }

      setErrors((current) => ({ ...current, [tab]: null }));
    } catch (caught) {
      setErrors((current) => ({ ...current, [tab]: errorMessage(caught) }));
    } finally {
      setLoading((current) => ({ ...current, [tab]: false }));
    }
  }, []);

  useEffect(() => {
    void fetchTab('margins');
  }, [fetchTab]);

  function selectTab(tab: ProviderTabKey) {
    setActiveTab(tab);

    if (loaded[tab] || loading[tab]) {
      return;
    }

    setErrors((current) => ({ ...current, [tab]: null }));
    setLoading((current) => ({ ...current, [tab]: true }));
    void fetchTab(tab);
  }

  async function handleRefresh() {
    setRefreshing(true);
    await fetchTab(activeTab);
    setRefreshing(false);
  }

  function retry() {
    setErrors((current) => ({ ...current, [activeTab]: null }));
    setLoading((current) => ({ ...current, [activeTab]: true }));
    void fetchTab(activeTab);
  }

  function currentPdfData(): ProviderComercioPdfData | null {
    if (activeTab === 'margins' && margins) return { tab: 'margins', data: margins };
    if (activeTab === 'mrr' && mrr) return { tab: 'mrr', data: mrr };
    if (activeTab === 'commission' && commission) return { tab: 'commission', data: commission };
    if (activeTab === 'ltv' && ltv) return { tab: 'ltv', data: ltv };
    if (activeTab === 'campaigns' && campaigns) return { tab: 'campaigns', data: campaigns };
    if (activeTab === 'heatmap' && heatmap) return { tab: 'heatmap', data: heatmap };
    if (activeTab === 'forecast' && forecast) return { tab: 'forecast', data: forecast };

    return null;
  }

  async function handlePdf() {
    const payload = currentPdfData();

    if (!payload || pdfBusy) {
      return;
    }

    setPdfBusy(true);

    try {
      await exportProviderComercioPdf(payload, user?.name);
    } catch (caught) {
      setErrors((current) => ({
        ...current,
        [activeTab]:
          caught instanceof Error ? caught.message : 'No se pudo generar el PDF del reporte.',
      }));
    } finally {
      setPdfBusy(false);
    }
  }

  function renderMargins() {
    if (!margins) return null;
    const s = margins.summary;

    return (
      <>
        <View style={styles.kpiGrid}>
          <KpiCard label="Productos" value={String(s.total_products)} accent={ACCENT} />
          <KpiCard
            label="Margen promedio"
            value={s.average_margin_percent === null ? '—' : `${s.average_margin_percent}%`}
            accent={NAVY}
            valueColor={NAVY}
          />
          <KpiCard label="En verde" value={String(s.green)} hint="Margen ≥ 35%" accent={GREEN} valueColor={GREEN} />
          <KpiCard label="En amarillo" value={String(s.yellow)} hint="Margen 20% - 34%" accent={AMBER} valueColor={AMBER} />
          <KpiCard label="En rojo" value={String(s.red)} hint="Margen < 20%" accent={RED} valueColor={RED} />
        </View>
        <SectionTitle title="Productos" hint={`${s.with_recipe} con ficha técnica`} />
        {margins.products.length === 0 ? (
          <EmptyState text="Todavía no tenés productos." />
        ) : (
          margins.products.map((p) => (
            <View key={p.product_id} style={styles.rowCard}>
              <View style={styles.productHeader}>
                <Text style={styles.rowStrong}>{p.name ?? '—'}</Text>
                {trafficPill(p.traffic_light, p.traffic_light_label)}
              </View>
              {!p.has_recipe ? <Text style={styles.rowSubtle}>Sin ficha técnica</Text> : null}
              <Text style={styles.rowMuted}>
                Precio {money(p.sale_price)} · Costo {money(p.production_cost)}
              </Text>
              <Text style={styles.rowSubtle}>
                Margen {p.gross_margin_amount === null ? '—' : money(p.gross_margin_amount)}
                {p.gross_margin_percent === null ? '' : ` (${p.gross_margin_percent}%)`}
              </Text>
              {p.recommendation?.note ? (
                <Text style={styles.recommendation}>
                  {p.recommendation.note}
                  {p.recommendation.suggested_price !== null &&
                  p.recommendation.suggested_price !== undefined
                    ? ` Precio sugerido: ${money(p.recommendation.suggested_price)}`
                    : ''}
                </Text>
              ) : null}
            </View>
          ))
        )}
      </>
    );
  }

  function renderMrr() {
    if (!mrr) return null;
    const s = mrr.summary;

    return (
      <>
        <View style={styles.kpiGrid}>
          <KpiCard label="MRR" value={money(s.mrr)} accent={GREEN} valueColor={GREEN} />
          <KpiCard label="Proyección 30 días" value={money(s.projection_30_days)} accent={NAVY} />
          <KpiCard label="Activos" value={String(s.active_subscribers)} accent={ACCENT} />
          <KpiCard label="Pausados" value={String(s.paused_subscribers)} accent={AMBER} valueColor={AMBER} />
          <KpiCard label="Churn" value={`${s.churn_rate_percent}%`} hint={`${s.cancelled_subscribers} cancelados`} accent={RED} valueColor={RED} />
          <KpiCard label="Ticket promedio" value={money(s.average_ticket)} accent={NAVY} />
        </View>
        <SectionTitle title="Por plan" hint={`Cobrado en período: ${money(s.collected_in_period)}`} />
        {mrr.by_plan.length === 0 ? (
          <EmptyState text="Todavía no tenés suscriptores activos." />
        ) : (
          mrr.by_plan.map((p) => (
            <View key={p.plan_id} style={styles.rowCard}>
              <Text style={styles.rowStrong}>{p.title ?? 'Plan'}</Text>
              <Text style={styles.rowMuted}>
                {p.subscribers} {p.subscribers === 1 ? 'suscriptor' : 'suscriptores'} · {p.frequency ?? ''}
              </Text>
              <Text style={styles.rowSubtle}>
                Cobro {money(p.charge_amount)} · MRR {money(p.mrr)}
              </Text>
            </View>
          ))
        )}
      </>
    );
  }

  function renderCommission() {
    if (!commission) return null;
    const s = commission.summary;
    const p = commission.period;

    return (
      <>
        <Text style={styles.periodText}>
          Período: {shortDate(p.start_date)} al {shortDate(p.end_date)}
        </Text>
        <View style={styles.kpiGrid}>
          <KpiCard label="Volumen bruto" value={money(s.gross_volume)} accent={ACCENT} />
          <KpiCard
            label="Comisión PideAca"
            value={money(s.pideaca_commission_amount)}
            hint={`${s.pideaca_commission_percent}%`}
            accent={NAVY}
          />
          <KpiCard
            label="Ahorro vs. tradicional"
            value={`${money(s.net_savings_vs_min)} – ${money(s.net_savings_vs_max)}`}
            accent={GREEN}
            valueColor={GREEN}
          />
        </View>
        <View style={styles.banner}>
          <Text style={styles.bannerText}>{s.savings_note ?? ''}</Text>
        </View>
        <SectionTitle title="Detalle" />
        <View style={styles.rowCard}>
          <Text style={styles.rowMuted}>Pedidos: {money(s.orders_revenue)}</Text>
          <Text style={styles.rowMuted}>Suscripciones: {money(s.subscriptions_revenue)}</Text>
          <Text style={styles.rowSubtle}>
            Tradicional {s.traditional_min_percent}%–{s.traditional_max_percent}%: {money(s.traditional_min_amount)} –{' '}
            {money(s.traditional_max_amount)}
          </Text>
        </View>
      </>
    );
  }

  function renderLtv() {
    if (!ltv) return null;
    const s = ltv.summary;

    return (
      <>
        <View style={styles.kpiGrid}>
          <KpiCard label="Clientes" value={String(s.total_customers)} accent={ACCENT} />
          <KpiCard label="VIP" value={String(s.vip_count)} accent={GREEN} valueColor={GREEN} />
          <KpiCard
            label="En riesgo"
            value={String(s.at_risk_count)}
            hint={`+${s.inactive_days_threshold} días sin pedir`}
            accent={RED}
            valueColor={RED}
          />
        </View>
        <SectionTitle title="VIP por valor de vida" />
        {ltv.vip_customers.length === 0 ? (
          <EmptyState text="Todavía no tenés clientes." />
        ) : (
          ltv.vip_customers.map((c) => <CustomerCard key={c.user_id} customer={c} />)
        )}
        <SectionTitle title="En riesgo" hint="2+ pedidos y sin comprar" />
        {ltv.at_risk_customers.length === 0 ? (
          <EmptyState text="Ningún cliente en riesgo. ¡Bien!" />
        ) : (
          ltv.at_risk_customers.map((c) => <CustomerCard key={c.user_id} customer={c} />)
        )}
      </>
    );
  }

  function renderCampaigns() {
    if (!campaigns) return null;
    const s = campaigns.summary;

    return (
      <>
        <View style={styles.kpiGrid}>
          <KpiCard label="Enviados" value={String(s.total_sent)} accent={ACCENT} />
          <KpiCard label="Convertidos" value={String(s.total_converted)} accent={GREEN} valueColor={GREEN} />
          <KpiCard label="Conversión" value={`${s.overall_conversion_rate_percent}%`} accent={NAVY} />
          <KpiCard label="Ventas atribuidas" value={money(s.attributed_sales_total)} accent={GREEN} valueColor={GREEN} />
        </View>
        <SectionTitle title="Por regla" />
        {campaigns.campaigns.length === 0 ? (
          <EmptyState text="Todavía no hay campañas con envíos." />
        ) : (
          campaigns.campaigns.map((c, index) => (
            <CampaignCard key={c.rule_id ?? `regla-${index}`} campaign={c} />
          ))
        )}
      </>
    );
  }

  function renderHeatmap() {
    if (!heatmap) return null;
    const s = heatmap.summary;

    return (
      <>
        <View style={styles.kpiGrid}>
          <KpiCard label="Pedidos" value={String(s.total_orders)} accent={ACCENT} />
          <KpiCard label="Express" value={String(s.express_orders)} accent={NAVY} />
          <KpiCard label="Suscripciones" value={String(s.subscription_orders)} accent={GREEN} valueColor={GREEN} />
        </View>
        <SectionTitle title="Por día" hint="últimos 30 días" />
        {heatmap.heatmap.map((d) => (
          <View key={d.day_index} style={styles.rowCard}>
            <Text style={styles.rowStrong}>{d.day_name}</Text>
            <Text style={styles.rowMuted}>
              {d.total_orders} {d.total_orders === 1 ? 'pedido' : 'pedidos'} · pico {d.peak_hour}:00 ({d.peak_hour_orders})
            </Text>
          </View>
        ))}
      </>
    );
  }

  function renderForecast() {
    if (!forecast) return null;
    const s = forecast.summary;
    const p = forecast.period;

    return (
      <>
        <Text style={styles.periodText}>
          Período: {shortDate(p.start_date)} al {shortDate(p.end_date)}
        </Text>
        <View style={styles.kpiGrid}>
          <KpiCard label="Suscripciones activas" value={String(s.active_subscriptions)} accent={ACCENT} />
          <KpiCard label="Platos" value={String(s.total_products)} accent={NAVY} />
          <KpiCard label="Insumos" value={String(s.total_supplies)} accent={AMBER} />
          <KpiCard label="Costo insumos" value={money(s.estimated_supplies_cost)} accent={GREEN} valueColor={GREEN} />
        </View>
        <SectionTitle title="Platos necesarios" />
        {forecast.products.length === 0 ? (
          <EmptyState text="Sin entregas programadas en el período." />
        ) : (
          forecast.products.map((x) => (
            <View key={x.product_id} style={styles.rowCard}>
              <Text style={styles.rowStrong}>{x.name ?? '—'}</Text>
              <Text style={styles.rowMuted}>
                {x.quantity} unidades · {x.subscriptions} {x.subscriptions === 1 ? 'suscripción' : 'suscripciones'}
              </Text>
            </View>
          ))
        )}
        <SectionTitle title="Insumos necesarios" />
        {forecast.supplies.length === 0 ? (
          <EmptyState text="Sin insumos requeridos." />
        ) : (
          forecast.supplies.map((x) => (
            <View key={x.supply_id} style={styles.rowCard}>
              <Text style={styles.rowStrong}>{x.name ?? '—'}</Text>
              <Text style={styles.rowMuted}>
                {x.quantity_required} {x.unit_symbol ?? ''} · costo est. {money(x.estimated_cost)}
              </Text>
            </View>
          ))
        )}
      </>
    );
  }

  function renderActiveTab() {
    if (loading[activeTab] && !loaded[activeTab]) {
      return (
        <View style={styles.stateBox}>
          <ActivityIndicator color={colors.brand} />
          <Text style={styles.stateText}>Cargando reporte…</Text>
        </View>
      );
    }

    if (!loaded[activeTab]) {
      return (
        <View style={styles.stateBox}>
          <Text style={styles.stateText}>{errors[activeTab] ?? 'No se pudo cargar el reporte.'}</Text>
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

    if (activeTab === 'mrr') return renderMrr();
    if (activeTab === 'commission') return renderCommission();
    if (activeTab === 'ltv') return renderLtv();
    if (activeTab === 'campaigns') return renderCampaigns();
    if (activeTab === 'heatmap') return renderHeatmap();
    if (activeTab === 'forecast') return renderForecast();

    return renderMargins();
  }

  if (loading.margins && !margins) {
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
        <Text style={styles.subtitle}>Rentabilidad, clientes y operación de tu comercio</Text>
      </View>

      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Abrir secciones de reportes"
        onPress={() => setSidebarOpen(true)}
        style={styles.sectionsCard}
      >
        <View style={styles.sectionsInfo}>
          <Text style={styles.sectionsLabel}>Sección</Text>
          <Text style={styles.sectionsValue} numberOfLines={1}>
            {TABS.find((tab) => tab.key === activeTab)?.icon ?? ''}{' '}
            {TABS.find((tab) => tab.key === activeTab)?.label ?? ''}
          </Text>
        </View>
        <Text style={styles.sectionsChevron}>›</Text>
      </Pressable>

      <SectionDrawer
        visible={sidebarOpen}
        onClose={() => setSidebarOpen(false)}
        title="Reportes Comercio"
        subtitle="Rentabilidad, clientes y operación de tu comercio"
        items={TABS}
        active={activeTab}
        onSelect={(key) => selectTab(key as ProviderTabKey)}
      />

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
          <Text style={styles.pdfText}>{pdfBusy ? 'Generando…' : 'PDF / Imprimir'}</Text>
        </Pressable>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { backgroundColor: colors.background, flex: 1 },
  topBar: { paddingHorizontal: spacing.lg, paddingTop: spacing.sm },
  backButton: { alignSelf: 'flex-start' },
  backText: { color: colors.brandDark, fontSize: 16, fontWeight: '600' },
  titleWrap: { paddingHorizontal: spacing.lg, paddingBottom: spacing.md, paddingTop: spacing.xs },
  title: { color: NAVY, fontSize: 20, fontWeight: '800' },
  subtitle: { color: colors.muted, fontSize: 12, lineHeight: 17, marginTop: 3 },
  tabsScroll: { flexGrow: 0, marginBottom: spacing.md },
  sectionsCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.md,
    marginHorizontal: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  sectionsInfo: { flex: 1, minWidth: 0 },
  sectionsLabel: { color: colors.muted, fontSize: 11, fontWeight: '700', textTransform: 'uppercase' },
  sectionsValue: { color: colors.text, fontSize: 15, fontWeight: '700', marginTop: 2 },
  sectionsChevron: { color: colors.muted, fontSize: 22, fontWeight: '600' },
  scroll: { flex: 1 },
  content: { paddingBottom: spacing.lg, paddingHorizontal: spacing.lg, paddingTop: spacing.xs },
  periodText: { color: colors.muted, fontSize: 12, marginBottom: spacing.md },
  kpiGrid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', marginBottom: spacing.lg, rowGap: spacing.md },
  kpiCard: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderLeftWidth: 4,
    borderRadius: 8,
    borderWidth: 1,
    padding: spacing.lg,
    width: '48%',
  },
  kpiLabel: { color: colors.muted, fontSize: 10, fontWeight: '700', textTransform: 'uppercase' },
  kpiValue: { color: NAVY, fontSize: 18, fontWeight: '800', marginTop: 6 },
  kpiHint: { color: colors.muted, fontSize: 11, marginTop: 3 },
  banner: {
    backgroundColor: '#ecfdf5',
    borderColor: '#a7f3d0',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  bannerText: { color: '#065f46', fontSize: 12, lineHeight: 18 },
  sectionTitleRow: { alignItems: 'baseline', flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginBottom: spacing.md },
  sectionTitle: { color: NAVY, fontSize: 14, fontWeight: '700' },
  sectionHint: { color: '#94a3b8', fontSize: 11 },
  rowCard: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  productHeader: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm },
  rowStrong: { color: NAVY, fontSize: 14, fontWeight: '700', flex: 1 },
  rowMuted: { color: colors.muted, fontSize: 12, marginTop: 2 },
  rowSubtle: { color: '#94a3b8', fontSize: 11, marginTop: 4 },
  recommendation: { color: '#065f46', fontSize: 12, lineHeight: 17, marginTop: 6 },
  pill: { borderRadius: radius.pill, paddingHorizontal: 10, paddingVertical: 3 },
  pillText: { fontSize: 10, fontWeight: '800' },
  emptyDashed: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.md,
    borderStyle: 'dashed',
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.xxl,
  },
  emptyText: { color: colors.muted, fontSize: 13, lineHeight: 19, textAlign: 'center' },
  stateBox: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    padding: spacing.xl,
  },
  stateText: { color: colors.muted, fontSize: 13, lineHeight: 19, marginTop: spacing.sm, textAlign: 'center' },
  retryButton: { backgroundColor: NAVY, borderRadius: 6, marginTop: spacing.lg, paddingHorizontal: spacing.xl, paddingVertical: spacing.sm },
  retryText: { color: colors.surface, fontSize: 13, fontWeight: '700' },
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
  pdfIcon: { color: colors.surface, fontSize: 15 },
  pdfText: { color: colors.surface, fontSize: 13, fontWeight: '700' },
  buttonDisabled: { opacity: 0.55 },
});
