import { Pressable, StyleSheet, Text, View } from 'react-native';

import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type { ProviderFinancialAlert, ProviderFinancialProduct } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type Props = {
  product: ProviderFinancialProduct;
  threshold: number;
  onOpenRecipe: () => void;
};

/** Porcentaje con hasta dos decimales, igual que `finNum` de la web. */
function percent(value: number): string {
  return String(Math.round(value * 100) / 100);
}

function alertStyle(alert: ProviderFinancialAlert) {
  return alert.severity === 'warning'
    ? { box: styles.alertWarning, text: styles.alertWarningText }
    : { box: styles.alertInfo, text: styles.alertInfoText };
}

/**
 * Tarjeta de producto de Salud financiera.
 *
 * Adapta la fila de la tabla web: nombre, precio de venta, costo de
 * produccion, badge de margen, estado/alerta y accion para abrir la ficha.
 */
export function FinancialProductCard({ product, threshold, onOpenRecipe }: Props) {
  const margin = product.profit_margin;

  return (
    <View style={styles.card}>
      <View style={styles.topRow}>
        <Text style={styles.name} numberOfLines={2}>
          {product.name}
        </Text>

        <Pressable
          accessibilityRole="button"
          accessibilityLabel={product.has_recipe ? 'Editar ficha' : 'Cargar ficha'}
          onPress={onOpenRecipe}
          style={({ pressed }) => [styles.recipeButton, pressed && styles.pressed]}
        >
          <Text style={styles.recipeIcon}>📄</Text>
        </Pressable>
      </View>

      <View style={styles.metricsRow}>
        <View style={styles.metric}>
          <Text style={styles.metricLabel}>Precio</Text>
          <Text style={styles.price}>{money(product.sale_price)}</Text>
        </View>

        <View style={[styles.metric, styles.metricRight]}>
          <Text style={styles.metricLabel}>Costo</Text>
          <Text style={styles.cost}>
            {product.has_recipe ? money(product.production_cost) : '—'}
          </Text>
        </View>
      </View>

      <View style={styles.tagsRow}>
        {margin === null ? (
          <View style={[styles.tag, styles.tagNoPrice]}>
            <Text style={[styles.tagText, styles.tagNoPriceText]}>Sin precio</Text>
          </View>
        ) : margin < threshold ? (
          <View style={[styles.tag, styles.tagDanger]}>
            <Text style={[styles.tagText, styles.tagDangerText]}>
              Margen bajo {percent(margin)}%
            </Text>
          </View>
        ) : (
          <View style={[styles.tag, styles.tagSuccess]}>
            <Text style={[styles.tagText, styles.tagSuccessText]}>
              Margen {percent(margin)}%
            </Text>
          </View>
        )}
      </View>

      <View style={styles.alerts}>
        {product.alerts.length === 0 ? (
          <View style={[styles.tag, styles.tagSuccess]}>
            <Text style={[styles.tagText, styles.tagSuccessText]}>OK</Text>
          </View>
        ) : (
          product.alerts.map((alert, index) => {
            const style = alertStyle(alert);

            return (
              <View key={`${alert.code}-${index}`} style={[styles.tag, style.box]}>
                <Text style={[styles.tagText, style.text]}>{alert.message}</Text>
              </View>
            );
          })
        )}
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
    marginBottom: spacing.sm,
    padding: spacing.lg,
  },
  topRow: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
  },
  name: {
    color: NAVY,
    flex: 1,
    fontSize: 15,
    fontWeight: '700',
  },
  recipeButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: ACCENT,
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  pressed: {
    opacity: 0.8,
  },
  recipeIcon: {
    fontSize: 15,
  },
  metricsRow: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.md,
  },
  metric: {
    flex: 1,
  },
  metricRight: {
    alignItems: 'flex-end',
  },
  metricLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '600',
    marginBottom: 2,
    textTransform: 'uppercase',
  },
  price: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
  },
  cost: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '600',
  },
  tagsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.md,
  },
  alerts: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  tag: {
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  tagText: {
    fontSize: 11,
    fontWeight: '700',
  },
  tagSuccess: {
    backgroundColor: '#d1fae5',
  },
  tagSuccessText: {
    color: '#047857',
  },
  tagDanger: {
    backgroundColor: '#fee2e2',
  },
  tagDangerText: {
    color: '#b91c1c',
  },
  tagNoPrice: {
    backgroundColor: '#fff7ed',
  },
  tagNoPriceText: {
    color: '#9a3412',
  },
  alertWarning: {
    backgroundColor: '#fef3c7',
  },
  alertWarningText: {
    color: '#b45309',
  },
  alertInfo: {
    backgroundColor: '#dbeafe',
  },
  alertInfoText: {
    color: '#1d4ed8',
  },
});
