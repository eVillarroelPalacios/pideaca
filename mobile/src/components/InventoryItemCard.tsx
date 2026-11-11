import { ActivityIndicator, Pressable, StyleSheet, Switch, Text, View } from 'react-native';

import { stockNumber } from '@/lib/stock';
import { colors, radius, spacing } from '@/theme';
import type { ProviderInventoryItem } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type Props = {
  item: ProviderInventoryItem;
  /** Deshabilita el switch mientras se guarda el seguimiento. */
  toggling?: boolean;
  onToggleTrack: (next: boolean) => void;
  onEdit: () => void;
};

type Status = {
  label: string;
  background: string;
  color: string;
};

/**
 * Estado de la fila, con la misma prioridad que la tabla del panel web:
 * sin seguimiento (S/S), bajo el mínimo, sin disponible o todo ok.
 */
function statusFor(item: ProviderInventoryItem): Status {
  if (!item.track_stock) {
    return { label: 'S/S', background: '#f1f5f9', color: '#64748b' };
  }

  if (item.low_stock) {
    return { label: 'Stock bajo', background: '#fee2e2', color: '#b91c1c' };
  }

  if (item.available_stock <= 0) {
    return { label: 'Sin stock', background: '#fee2e2', color: '#b91c1c' };
  }

  return { label: 'OK', background: '#d1fae5', color: '#047857' };
}

/**
 * Tarjeta de un producto del inventario. Resume la fila ancha del panel web
 * (stock actual, reservado, disponible, minimo, estado y acciones) en un
 * formato compacto pensado para pantallas de celular.
 */
export function InventoryItemCard({ item, toggling = false, onToggleTrack, onEdit }: Props) {
  const status = statusFor(item);
  const unit = item.unit_of_measure
    ? `${item.unit_of_measure.name} (${item.unit_of_measure.symbol})`
    : '—';

  return (
    <View style={styles.card}>
      <View style={styles.headerRow}>
        <View style={styles.headerInfo}>
          <Text style={styles.name}>{item.name}</Text>
          <Text style={styles.category}>{item.category_name ?? 'Sin categoría'}</Text>
        </View>

        <View style={[styles.statusBadge, { backgroundColor: status.background }]}>
          <Text style={[styles.statusText, { color: status.color }]}>{status.label}</Text>
        </View>

        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`Editar stock de ${item.name}`}
          onPress={onEdit}
          style={styles.editButton}
        >
          <Text style={styles.editIcon}>✎</Text>
        </Pressable>
      </View>

      <Text style={styles.unit}>Unidad: {unit}</Text>

      <View style={styles.metrics}>
        <View style={styles.metric}>
          <Text style={styles.metricLabel}>Stock actual</Text>
          <Text style={styles.metricValue}>{stockNumber(item.current_stock)}</Text>
        </View>
        <View style={styles.metric}>
          <Text style={styles.metricLabel}>Reservado</Text>
          <Text style={styles.metricValueMuted}>{stockNumber(item.reserved_stock)}</Text>
        </View>
        <View style={styles.metric}>
          <Text style={styles.metricLabel}>Disponible</Text>
          <Text style={styles.metricValue}>{stockNumber(item.available_stock)}</Text>
        </View>
        <View style={styles.metric}>
          <Text style={styles.metricLabel}>Mínimo</Text>
          <Text style={styles.metricValueMuted}>{stockNumber(item.min_stock_alert)}</Text>
        </View>
      </View>

      <View style={styles.trackRow}>
        <Switch
          value={item.track_stock}
          onValueChange={onToggleTrack}
          disabled={toggling}
          trackColor={{ false: '#cbd5e1', true: '#fdba74' }}
          thumbColor={item.track_stock ? ACCENT : '#f8fafc'}
        />
        <Text style={styles.trackLabel}>
          {item.track_stock ? 'C/S · controla stock' : 'S/S · sin seguimiento'}
        </Text>
        {toggling ? <ActivityIndicator color={ACCENT} size="small" /> : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  headerRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
  },
  headerInfo: {
    flex: 1,
  },
  name: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
  },
  category: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  statusBadge: {
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '700',
  },
  editButton: {
    alignItems: 'center',
    borderColor: ACCENT,
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 30,
    justifyContent: 'center',
    width: 30,
  },
  editIcon: {
    color: ACCENT,
    fontSize: 15,
    lineHeight: 17,
  },
  unit: {
    color: colors.muted,
    fontSize: 12,
    marginTop: spacing.sm,
  },
  metrics: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  metric: {
    backgroundColor: '#f8fafc',
    borderRadius: radius.sm,
    flexBasis: '47%',
    flexGrow: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
  },
  metricLabel: {
    color: colors.muted,
    fontSize: 10,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  metricValue: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '700',
    marginTop: 2,
  },
  metricValueMuted: {
    color: '#475569',
    fontSize: 15,
    fontWeight: '600',
    marginTop: 2,
  },
  trackRow: {
    alignItems: 'center',
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingTop: spacing.sm,
  },
  trackLabel: {
    color: '#475569',
    flex: 1,
    fontSize: 12,
    fontWeight: '600',
  },
});
