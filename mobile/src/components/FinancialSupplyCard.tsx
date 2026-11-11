import { Pressable, StyleSheet, Text, View } from 'react-native';

import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type { ProviderSupply } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type Props = {
  supply: ProviderSupply;
  onEdit: () => void;
  onDelete: () => void;
};

/**
 * Tarjeta de insumo de Salud financiera.
 *
 * Adapta la fila de la tabla web: nombre, unidad, costo por unidad, aviso de
 * "en uso" cuando alguna ficha tecnica lo referencia y acciones editar/borrar.
 */
export function FinancialSupplyCard({ supply, onEdit, onDelete }: Props) {
  const inUse = supply.recipes_count > 0;

  return (
    <View style={styles.card}>
      <View style={styles.info}>
        <View style={styles.nameRow}>
          <Text style={styles.name} numberOfLines={2}>
            {supply.name}
          </Text>

          {inUse ? (
            <View style={styles.inUseTag}>
              <Text style={styles.inUseText}>
                en uso · {supply.recipes_count} ficha(s)
              </Text>
            </View>
          ) : null}
        </View>

        <Text style={styles.detail}>
          {supply.unit_of_measure ? supply.unit_of_measure.name : 'Sin unidad'}
          {' · '}
          {money(supply.cost_per_unit)} por unidad
        </Text>
      </View>

      <View style={styles.actions}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`Editar ${supply.name}`}
          onPress={onEdit}
          style={({ pressed }) => [styles.actionButton, pressed && styles.pressed]}
        >
          <Text style={styles.editIcon}>✎</Text>
        </Pressable>

        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`Eliminar ${supply.name}`}
          onPress={onDelete}
          style={({ pressed }) => [
            styles.actionButton,
            styles.deleteButton,
            pressed && styles.pressed,
          ]}
        >
          <Text style={styles.deleteIcon}>🗑</Text>
        </Pressable>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.sm,
    padding: spacing.lg,
  },
  info: {
    flex: 1,
  },
  nameRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  name: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '700',
  },
  inUseTag: {
    backgroundColor: '#eef2f7',
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  inUseText: {
    color: colors.muted,
    fontSize: 10,
    fontWeight: '700',
  },
  detail: {
    color: colors.muted,
    fontSize: 12,
    marginTop: spacing.xs,
  },
  actions: {
    flexDirection: 'row',
    gap: 6,
  },
  actionButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 36,
    justifyContent: 'center',
    width: 36,
  },
  editIcon: {
    color: ACCENT,
    fontSize: 16,
    fontWeight: '700',
  },
  deleteButton: {
    borderColor: '#fecaca',
  },
  deleteIcon: {
    fontSize: 14,
  },
  pressed: {
    opacity: 0.8,
  },
});
