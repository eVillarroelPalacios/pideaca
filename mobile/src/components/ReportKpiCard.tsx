import { StyleSheet, Text, View } from 'react-native';

import { colors } from '@/theme';

const NAVY = '#0c2a4d';

type Props = {
  label: string;
  value: string;
  hint?: string;
  /** Color del borde izquierdo. */
  accent?: string;
  /** Color del valor (por defecto navy). */
  valueColor?: string;
};

/**
 * Tarjeta de KPI para los reportes (ahorro, hábitos, nutrición).
 * Espeja `repKpis()` de `dashboard/sections/reportes.blade.php`.
 */
export function ReportKpiCard({ label, value, hint, accent, valueColor }: Props) {
  return (
    <View style={[styles.card, accent ? { borderLeftColor: accent } : null]}>
      <Text style={styles.label}>{label}</Text>
      <Text style={[styles.value, valueColor ? { color: valueColor } : null]}>{value}</Text>
      {hint ? <Text style={styles.hint}>{hint}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderLeftWidth: 4,
    borderRadius: 8,
    borderWidth: 1,
    padding: 16,
    width: '48%',
  },
  label: {
    color: colors.muted,
    fontSize: 10,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  value: {
    color: NAVY,
    fontSize: 18,
    fontWeight: '800',
    marginTop: 6,
  },
  hint: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 3,
  },
});
