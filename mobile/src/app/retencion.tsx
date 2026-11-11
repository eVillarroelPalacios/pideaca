import { router } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
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
import {
  CampaignFormCard,
  type CampaignDraft,
} from '@/components/CampaignFormCard';
import {
  CampaignTabSelector,
  type CampaignState,
  type CampaignTab,
} from '@/components/CampaignTabSelector';
import { ErrorBanner } from '@/components/ErrorBanner';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderMarketingRule,
  ProviderMarketingRulePayload,
  ProviderMarketingRuleType,
  ProviderMarketingRulesResponse,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

const DEFAULT_TYPES: ProviderMarketingRuleType[] = [
  'INACTIVE_CUSTOMER',
  'RECURRING_DAY_REMINDER',
  'WELCOME_BACK',
];

const REQUIRED_TOKENS = ['{nombre}', '{comercio}', '{cupon}'];

const DAYS = [
  'Lunes',
  'Martes',
  'Miercoles',
  'Jueves',
  'Viernes',
  'Sabado',
  'Domingo',
];

const DAY_OPTIONS = DAYS.map((label, index) => ({ value: index + 1, label }));

type CampaignMeta = {
  title: string;
  description: string;
  icon: string;
  requiresDays: boolean;
  requiresDay: boolean;
};

const CAMPAIGNS: Record<string, CampaignMeta> = {
  INACTIVE_CUSTOMER: {
    title: 'Reactivación',
    description: 'Avisa cuando el cliente lleva varios días sin pedirte nada.',
    icon: '👤',
    requiresDays: true,
    requiresDay: false,
  },
  RECURRING_DAY_REMINDER: {
    title: 'Aviso de Envío',
    description: 'Recuerda el pedido recurrente antes de que llegue el día.',
    icon: '📅',
    requiresDays: false,
    requiresDay: true,
  },
  WELCOME_BACK: {
    title: 'Reencuentro',
    description: 'Recupera clientes que dejaron de comprar hace tiempo.',
    icon: '🔄',
    requiresDays: false,
    requiresDay: false,
  },
};

type Notice = { text: string; error: boolean } | null;

function metaFor(type: string): CampaignMeta {
  return (
    CAMPAIGNS[type] ?? {
      title: type,
      description: '',
      icon: '📩',
      requiresDays: false,
      requiresDay: false,
    }
  );
}

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

/** Tokens que el mensaje no puede perder: los de la plantilla por defecto
 * (p. ej. {dias} en Reactivación) más los tres obligatorios. */
function tokensFor(
  type: ProviderMarketingRuleType,
  data: ProviderMarketingRulesResponse | null,
): string[] {
  const template = data?.default_templates[type] ?? '';
  const found = template.match(/\{[a-z_]+\}/g) ?? [];

  return Array.from(new Set([...found, ...REQUIRED_TOKENS]));
}

function draftFromRule(
  type: ProviderMarketingRuleType,
  rule: ProviderMarketingRule | null,
  data: ProviderMarketingRulesResponse | null,
): CampaignDraft {
  return {
    is_enabled: rule?.is_enabled ?? false,
    days_inactive: String(rule?.days_inactive ?? 30),
    day_of_week: rule?.day_of_week ?? 1,
    discount_code: rule?.discount_code ?? '',
    message_template: rule?.message_template ?? data?.default_templates[type] ?? '',
  };
}

/** Arma el borrador editable de cada campaña a partir de la respuesta API. */
function buildDrafts(
  response: ProviderMarketingRulesResponse,
): Partial<Record<string, CampaignDraft>> {
  const types = response.types.length ? response.types : DEFAULT_TYPES;
  const drafts: Partial<Record<string, CampaignDraft>> = {};

  types.forEach((type) => {
    const rule = response.rules.find((item) => item.rule_type === type) ?? null;
    drafts[type] = draftFromRule(type, rule, response);
  });

  return drafts;
}

function stateFor(rule: ProviderMarketingRule | undefined): CampaignState {
  if (!rule) {
    return 'unconfigured';
  }

  return rule.is_enabled ? 'active' : 'inactive';
}

/**
 * Retención / Campañas de fidelización del comercio.
 *
 * Espeja `dashboard.sections.retencion`: aviso general, contador de campañas
 * configuradas, selector con estado por campaña y el formulario de cada regla
 * automática (Reactivación, Aviso de Envío y Reencuentro).
 */
export default function RetencionScreen() {
  const [data, setData] = useState<ProviderMarketingRulesResponse | null>(null);
  const [drafts, setDrafts] = useState<Partial<Record<string, CampaignDraft>>>({});
  const [active, setActive] = useState<string>(DEFAULT_TYPES[0]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [savingType, setSavingType] = useState<string | null>(null);
  const [notices, setNotices] = useState<Partial<Record<string, Notice>>>({});

  useEffect(() => {
    let mounted = true;

    void (async () => {
      try {
        const response = await api.providerMarketingRules();

        if (!mounted) {
          return;
        }

        setData(response);
        setDrafts(buildDrafts(response));
      } catch (caught) {
        if (mounted) {
          setError(errorMessage(caught, 'No se pudieron cargar las campañas.'));
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

  async function loadRules(options: { resetDrafts?: boolean } = {}) {
    try {
      const response = await api.providerMarketingRules();

      setData(response);

      if (options.resetDrafts) {
        setDrafts(buildDrafts(response));
      }

      setError(null);
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudieron cargar las campañas.'));
    }
  }

  async function handleRefresh() {
    setRefreshing(true);

    try {
      await loadRules({ resetDrafts: true });
    } finally {
      setRefreshing(false);
    }
  }

  async function handleRetry() {
    setLoading(true);

    try {
      await loadRules({ resetDrafts: true });
    } finally {
      setLoading(false);
    }
  }

  function updateDraft(type: string, patch: Partial<CampaignDraft>) {
    setDrafts((current) => {
      const draft = current[type];

      if (!draft) {
        return current;
      }

      return { ...current, [type]: { ...draft, ...patch } };
    });

    setNotices((current) => ({ ...current, [type]: undefined }));
  }

  async function saveCampaign(type: string) {
    const draft = drafts[type];
    const ruleType = type as ProviderMarketingRuleType;
    const meta = metaFor(type);
    const message = draft?.message_template.trim() ?? '';

    if (!draft) {
      return;
    }

    if (!message) {
      setNotices((current) => ({
        ...current,
        [type]: { text: 'Escribí el mensaje de la campaña.', error: true },
      }));
      return;
    }

    const missing = tokensFor(ruleType, data).filter(
      (token) => !message.includes(token),
    );

    if (missing.length > 0) {
      setNotices((current) => ({
        ...current,
        [type]: {
          text: `El mensaje no puede perder ${missing.join(
            ', ',
          )}; esos tokens se completan solos al enviar.`,
          error: true,
        },
      }));
      return;
    }

    const payload: ProviderMarketingRulePayload = {
      rule_type: ruleType,
      message_template: message,
      discount_code: draft.discount_code.trim() || null,
      is_enabled: draft.is_enabled,
    };

    if (meta.requiresDays) {
      const days = Number(draft.days_inactive);

      if (!Number.isFinite(days) || days < 1 || days > 365) {
        setNotices((current) => ({
          ...current,
          [type]: {
            text: 'Ingresá una cantidad de días entre 1 y 365.',
            error: true,
          },
        }));
        return;
      }

      payload.days_inactive = Math.trunc(days);
    }

    if (meta.requiresDay) {
      payload.day_of_week = draft.day_of_week;
    }

    setSavingType(type);
    setNotices((current) => ({ ...current, [type]: undefined }));

    try {
      const response = await api.providerMarketingUpdateRule(payload);

      setData((current) => {
        if (!current) {
          return current;
        }

        const types = current.types.length ? current.types : DEFAULT_TYPES;
        const rules = [
          ...current.rules.filter(
            (item) => item.rule_type !== response.rule.rule_type,
          ),
          response.rule,
        ].sort(
          (a, b) => types.indexOf(a.rule_type) - types.indexOf(b.rule_type),
        );

        return {
          ...current,
          rules,
          summary: {
            total: rules.length,
            enabled: rules.filter((item) => item.is_enabled).length,
          },
        };
      });

      setDrafts((current) => ({
        ...current,
        [type]: draftFromRule(ruleType, response.rule, data),
      }));

      setNotices((current) => ({
        ...current,
        [type]: { text: response.message, error: false },
      }));
    } catch (caught) {
      setNotices((current) => ({
        ...current,
        [type]: {
          text: errorMessage(caught, 'No se pudo guardar la campaña.'),
          error: true,
        },
      }));
    } finally {
      setSavingType(null);
    }
  }

  const types = useMemo(
    () => (data?.types.length ? data.types : DEFAULT_TYPES),
    [data],
  );

  const activeType = types.includes(active as ProviderMarketingRuleType)
    ? (active as ProviderMarketingRuleType)
    : types[0];

  const activeMeta = metaFor(activeType);
  const activeDraft = drafts[activeType];

  const tabs: CampaignTab[] = useMemo(
    () =>
      types.map((type) => {
        const rule = data?.rules.find((item) => item.rule_type === type);
        const meta = metaFor(type);

        return {
          type,
          label: meta.title,
          icon: meta.icon,
          state: stateFor(rule),
        };
      }),
    [types, data],
  );

  const summary = data?.summary ?? { total: 0, enabled: 0 };

  const refreshingControl = (
    <RefreshControl
      refreshing={refreshing}
      onRefresh={() => void handleRefresh()}
      colors={[colors.brand]}
      tintColor={colors.brand}
    />
  );

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

        <View style={styles.titleRow}>
          <Text style={styles.title}>Retención</Text>
          <View style={styles.summaryBadge}>
            <Text style={styles.summaryText}>
              {summary.enabled} de {summary.total} configuradas
            </Text>
          </View>
        </View>

        <View style={styles.banner}>
          <Text style={styles.bannerText}>
            Las campañas se revisan todos los días y solo se envían a los clientes
            que correspondan. Podés desactivar cualquiera en cualquier momento.
          </Text>
        </View>

        <CampaignTabSelector tabs={tabs} active={activeType} onChange={setActive} />

        <ErrorBanner message={error} />

        {loading ? (
          <View style={styles.loadingCard}>
            <ActivityIndicator color={colors.brand} />
            <Text style={styles.loadingText}>Cargando campañas…</Text>
          </View>
        ) : !data || !activeDraft ? (
          <View style={styles.emptyCard}>
            <Text style={styles.emptyTitle}>No se pudieron cargar las campañas</Text>
            <Text style={styles.emptyText}>
              Revisá tu conexión e intentá de nuevo.
            </Text>
            <Pressable
              accessibilityRole="button"
              onPress={() => void handleRetry()}
              style={styles.retryButton}
            >
              <Text style={styles.retryText}>↺ Reintentar</Text>
            </Pressable>
          </View>
        ) : (
          <CampaignFormCard
            title={activeMeta.title}
            description={activeMeta.description}
            state={stateFor(
              data.rules.find((item) => item.rule_type === activeType),
            )}
            draft={activeDraft}
            requiresDays={activeMeta.requiresDays}
            requiresDay={activeMeta.requiresDay}
            dayOptions={DAY_OPTIONS}
            requiredTokens={tokensFor(activeType, data)}
            saving={savingType === activeType}
            notice={notices[activeType] ?? null}
            onChange={(patch) => updateDraft(activeType, patch)}
            onSave={() => void saveCampaign(activeType)}
          />
        )}
      </ScrollView>
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
  titleRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  title: {
    color: NAVY,
    fontSize: 22,
    fontWeight: '800',
  },
  summaryBadge: {
    backgroundColor: '#eef2f7',
    borderRadius: radius.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 5,
  },
  summaryText: {
    color: NAVY,
    fontSize: 11,
    fontWeight: '800',
  },
  banner: {
    backgroundColor: '#eff6ff',
    borderLeftColor: '#2563eb',
    borderLeftWidth: 3,
    marginBottom: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  bannerText: {
    color: '#1e3a8a',
    fontSize: 13,
    lineHeight: 19,
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
});
