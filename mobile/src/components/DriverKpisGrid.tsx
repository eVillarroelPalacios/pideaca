import { Pressable, StyleSheet, Text, View } from 'react-native';

import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';

const ACCENT = '#D24C19';
const GREEN = '#16a34a';
const LABEL = '#94a3b8';

type Props = {
  earningsToday: number;
  baseToday: number;
  tipsToday: number;
  deliveredToday: number;
  acceptance: number | null;
  rating: number | null;
  goal: number;
  onPressGoal?: () => void;
};

function ratingLabel(rating: number | null): string {
  if (rating === null) {
    return '— ★';
  }

  return `${rating.toFixed(1).replace('.', ',')} ★`;
}

/**
 * KPIs del día en grid 2x2: ganancias, meta, aceptación y calificación.
 * Espeja `div.drv-kpis` de la Terminal Central de Repartos.
 */
export function DriverKpisGrid({
  earningsToday,
  baseToday,
  tipsToday,
  deliveredToday,
  acceptance,
  rating,
  goal,
  onPressGoal,
}: Props) {
  const goalTotal = Math.max(1, goal);
  const goalPct = Math.min(100, Math.round((deliveredToday / goalTotal) * 100));

  return (
    <View style={styles.grid}>
      <View style={styles.row}>
        <View style={[styles.card, styles.accentCard]}>
          <View style={styles.labelRow}>
            <Text style={styles.label}>Ganancias hoy</Text>
            <View style={[styles.icon, styles.iconEarn]}>
              <Text style={[styles.iconText, styles.iconTextOrange]}>$</Text>
            </View>
          </View>
          <Text style={[styles.value, styles.valueOrange]}>{money(earningsToday)}</Text>
          <Text style={styles.delta}>
            +{deliveredToday} {deliveredToday === 1 ? 'viaje' : 'viajes'}
          </Text>
          <Text style={styles.sub} numberOfLines={2}>
            Base: {money(baseToday)} | Propinas: {money(tipsToday)}
          </Text>
        </View>

        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Cambiar la meta diaria de pedidos"
          onPress={onPressGoal}
          style={({ pressed }) => [styles.card, pressed && styles.pressed]}
        >
          <View style={styles.labelRow}>
            <Text style={styles.label}>Meta del día</Text>
            <View style={[styles.icon, styles.iconDone]}>
              <Text style={[styles.iconText, styles.iconTextMuted]}>✓</Text>
            </View>
          </View>
          <View style={styles.valueRow}>
            <Text style={styles.value}>{deliveredToday}</Text>
            <Text style={styles.goalOf}>de {goal} pedidos</Text>
          </View>
          <View style={styles.track}>
            <View style={[styles.trackFill, { width: `${goalPct}%` }]} />
          </View>
        </Pressable>
      </View>

      <View style={styles.row}>
        <View style={styles.card}>
          <View style={styles.labelRow}>
            <Text style={styles.label}>Aceptación</Text>
            <View style={[styles.icon, styles.iconAcceptance]}>
              <Text style={[styles.iconText, styles.iconTextGreen]}>↗</Text>
            </View>
          </View>
          <Text style={[styles.value, styles.valueGreen]}>
            {acceptance === null ? '—' : `${acceptance}%`}
          </Text>
        </View>

        <View style={styles.card}>
          <View style={styles.labelRow}>
            <Text style={styles.label}>Calificación repartidor</Text>
            <View style={[styles.icon, styles.iconStar]}>
              <Text style={[styles.iconText, styles.iconTextOrange]}>★</Text>
            </View>
          </View>
          <Text style={[styles.value, styles.valueOrange]}>{ratingLabel(rating)}</Text>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  grid: {
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: 14,
    borderWidth: 1,
    flex: 1,
    minHeight: 118,
    paddingHorizontal: 13,
    paddingVertical: spacing.md,
  },
  accentCard: {
    backgroundColor: '#FFF7ED',
    borderColor: '#FED7AA',
  },
  pressed: {
    opacity: 0.75,
  },
  labelRow: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.xs,
    justifyContent: 'space-between',
  },
  label: {
    color: LABEL,
    flex: 1,
    fontSize: 9.5,
    fontWeight: '800',
    letterSpacing: 0.6,
    lineHeight: 13,
    textTransform: 'uppercase',
  },
  valueRow: {
    alignItems: 'baseline',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 4,
    marginTop: 6,
  },
  value: {
    color: colors.text,
    fontSize: 23,
    fontWeight: '800',
    letterSpacing: -0.6,
    lineHeight: 27,
    marginTop: 2,
  },
  valueOrange: {
    color: ACCENT,
  },
  valueGreen: {
    color: GREEN,
  },
  delta: {
    color: GREEN,
    fontSize: 11.5,
    fontWeight: '800',
    marginTop: 2,
  },
  sub: {
    color: LABEL,
    fontSize: 9.5,
    fontWeight: '600',
    marginTop: 4,
  },
  goalOf: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
  },
  track: {
    backgroundColor: '#e8edf3',
    borderRadius: radius.pill,
    height: 6,
    marginTop: spacing.sm,
    overflow: 'hidden',
  },
  trackFill: {
    backgroundColor: ACCENT,
    borderRadius: radius.pill,
    height: '100%',
  },
  icon: {
    alignItems: 'center',
    borderRadius: radius.pill,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  iconText: {
    fontSize: 15,
    fontWeight: '800',
  },
  iconEarn: {
    backgroundColor: '#FFE3D0',
  },
  iconDone: {
    backgroundColor: '#EEF2F7',
  },
  iconAcceptance: {
    backgroundColor: '#DCFCE7',
  },
  iconStar: {
    backgroundColor: '#FFF1E4',
  },
  iconTextOrange: {
    color: ACCENT,
  },
  iconTextMuted: {
    color: '#64748b',
  },
  iconTextGreen: {
    color: GREEN,
  },
});
