import { StyleSheet, Text, View } from 'react-native';

import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderSubscriptionCash,
  ProviderSubscriptionRecurring,
} from '@/types/api';

const NAVY = '#0c2a4d';

type Props = {
  recurring: ProviderSubscriptionRecurring;
  cash: ProviderSubscriptionCash;
};

type Kpi = {
  label: string;
  value: string;
  hint: string;
  accent: string;
};

/**
 * Tarjetas de metricas de Suscripciones del comercio.
 *
 * Espeja los cuatro KPIs del panel web (MRR, activas, cobrado 30 dias y por
 * cobrar), en un grid 2x2 con el acento de color arriba de cada tarjeta.
 */
export function MerchantSubscriptionKpis({ recurring, cash }: Props) {
  const kpis: Kpi[] = [
    {
      label: 'Ingreso recurrente (MRR)',
      value: money(recurring.mrr),
      hint: recurring.mrr_basis,
      accent: '#60a5fa',
    },
    {
      label: 'Activas',
      value: String(recurring.active_subscriptions ?? 0),
      hint: `${recurring.paused_subscriptions ?? 0} pausadas`,
      accent: NAVY,
    },
    {
      label: 'Cobrado 30 días',
      value: money(cash.collected_last_30_days),
      hint: `Envíos: ${money(cash.delivery_fees_last_30_days)}`,
      accent: '#047857',
    },
    {
      label: 'Por cobrar',
      value: money(cash.pending_amount),
      hint: `${cash.failed_last_30_days ?? 0} cobros fallidos`,
      accent: '#b45309',
    },
  ];

  return (
    <View style={styles.grid}>
      {kpis.map((kpi) => (
        <View key={kpi.label} style={[styles.card, { borderTopColor: kpi.accent }]}>
          <Text style={styles.label}>{kpi.label}</Text>
          <Text style={styles.value} numberOfLines={1} adjustsFontSizeToFit>
            {kpi.value}
          </Text>
          <Text style={styles.hint} numberOfLines={2}>
            {kpi.hint}
          </Text>
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
    rowGap: spacing.md,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderTopWidth: 3,
    borderWidth: 1,
    flexBasis: '48%',
    minHeight: 108,
    padding: spacing.md,
  },
  label: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '600',
    marginBottom: 4,
  },
  value: {
    color: NAVY,
    fontSize: 19,
    fontWeight: '800',
  },
  hint: {
    color: '#9ca3af',
    fontSize: 11,
    lineHeight: 15,
    marginTop: 4,
  },
});
