import { Pressable, StyleSheet, Text, View } from 'react-native';

import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type { ProviderCatalogProduct } from '@/types/api';

const ACCENT = '#D24C19';

type Props = {
  product: ProviderCatalogProduct;
  onEdit: (product: ProviderCatalogProduct) => void;
};

function units(value: number): string {
  return Number.isInteger(value) ? String(value) : String(Number(value.toFixed(3)));
}

/**
 * Tarjeta de producto de la vista previa del catalogo: espeja
 * `fdRenderProduct` del panel web (disponibilidad, stock, precio base,
 * variantes y agregados).
 */
export function CatalogProductCard({ product, onEdit }: Props) {
  const available = product.is_available !== false;
  const lowStock =
    product.track_stock &&
    product.min_stock_alert !== null &&
    product.current_stock < product.min_stock_alert;

  return (
    <View style={styles.card}>
      <View style={styles.topRow}>
        <View style={styles.info}>
          <View style={styles.nameRow}>
            <Text style={styles.name}>{product.name}</Text>
            <View style={[styles.badge, available ? styles.badgeOk : styles.badgeOff]}>
              <Text style={[styles.badgeText, available ? styles.badgeOkText : styles.badgeOffText]}>
                {available ? 'Disponible' : 'No disponible'}
              </Text>
            </View>
          </View>

          {product.description ? (
            <Text style={styles.description}>{product.description}</Text>
          ) : null}

          {product.track_stock ? (
            <View style={styles.stockRow}>
              <View style={[styles.badge, lowStock ? styles.badgeOff : styles.badgeOk]}>
                <Text style={[styles.badgeText, lowStock ? styles.badgeOffText : styles.badgeOkText]}>
                  {lowStock ? 'Stock bajo' : 'Con stock'}
                </Text>
              </View>
              <Text style={[styles.stockText, lowStock && styles.stockTextLow]}>
                {units(product.current_stock)} disponible
                {product.unit_of_measure ? ` ${product.unit_of_measure.symbol}` : ''}
                {product.min_stock_alert !== null
                  ? ` · mínimo ${units(product.min_stock_alert)}`
                  : ''}
              </Text>
            </View>
          ) : null}
        </View>

        <View style={styles.priceColumn}>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={`Editar ${product.name}`}
            onPress={() => onEdit(product)}
            style={styles.editButton}
          >
            <Text style={styles.editIcon}>✎</Text>
          </Pressable>
          <Text style={styles.price}>{money(product.price)}</Text>
        </View>
      </View>

      {product.variants.length > 0 ? (
        <View style={styles.pills}>
          {product.variants.map((variant) => {
            const ok = variant.is_available !== false;

            return (
              <View
                key={variant.id}
                style={[styles.pill, ok ? styles.variantPillOk : styles.pillOff]}
              >
                <Text style={[styles.pillText, ok ? styles.variantPillText : styles.pillOffText]}>
                  {variant.name} · {money(variant.price)}
                  {ok ? '' : ' · sin stock'}
                </Text>
              </View>
            );
          })}
        </View>
      ) : null}

      {product.option_groups.map((group) => {
        const rules = [
          group.min_choices ? `mín. ${group.min_choices}` : null,
          group.max_choices ? `máx. ${group.max_choices}` : null,
          group.is_required ? 'obligatorio' : null,
        ].filter(Boolean);

        return (
          <View key={group.id} style={styles.group}>
            <View style={styles.groupHeader}>
              <Text style={styles.groupName}>{group.name}</Text>
              {rules.length > 0 ? (
                <Text style={styles.groupRules}>{rules.join(' · ')}</Text>
              ) : null}
            </View>

            {group.options.length > 0 ? (
              <View style={styles.pills}>
                {group.options.map((option) => {
                  const ok = option.is_available !== false;

                  return (
                    <View
                      key={option.id}
                      style={[styles.pill, !ok && styles.pillOff]}
                    >
                      <Text style={[styles.pillText, !ok && styles.pillOffText]}>
                        {option.name}
                        {option.extra_price > 0 ? (
                          <Text style={styles.extraPrice}> +{money(option.extra_price)}</Text>
                        ) : null}
                        {ok ? '' : ' · sin stock'}
                      </Text>
                    </View>
                  );
                })}
              </View>
            ) : (
              <Text style={styles.emptyOptions}>Sin opciones cargadas.</Text>
            )}
          </View>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  topRow: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'space-between',
  },
  info: {
    flexShrink: 1,
  },
  nameRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  name: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  badge: {
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  badgeOk: {
    backgroundColor: '#d1fae5',
  },
  badgeOff: {
    backgroundColor: '#fee2e2',
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '600',
  },
  badgeOkText: {
    color: '#047857',
  },
  badgeOffText: {
    color: '#b91c1c',
  },
  description: {
    color: colors.muted,
    fontSize: 12,
    marginTop: spacing.xs,
  },
  stockRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  stockText: {
    color: '#475569',
    fontSize: 11,
  },
  stockTextLow: {
    color: '#b91c1c',
  },
  priceColumn: {
    alignItems: 'flex-end',
    gap: spacing.xs,
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
  price: {
    color: ACCENT,
    fontSize: 14,
    fontWeight: '700',
  },
  pills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginTop: spacing.sm,
  },
  pill: {
    backgroundColor: colors.surface,
    borderColor: '#e2e8f0',
    borderRadius: radius.sm,
    borderWidth: 1,
    paddingHorizontal: 9,
    paddingVertical: 4,
  },
  variantPillOk: {
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
  },
  pillOff: {
    backgroundColor: '#f8fafc',
    borderColor: colors.border,
  },
  pillText: {
    color: '#334155',
    fontSize: 11,
  },
  variantPillText: {
    color: '#9a3412',
  },
  pillOffText: {
    color: '#94a3b8',
  },
  extraPrice: {
    color: ACCENT,
    fontWeight: '700',
  },
  group: {
    borderTopColor: '#e5e7eb',
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.md,
  },
  groupHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  groupName: {
    color: colors.text,
    fontSize: 12,
    fontWeight: '600',
  },
  groupRules: {
    color: '#94a3b8',
    fontSize: 12,
  },
  emptyOptions: {
    color: '#94a3b8',
    fontSize: 11,
    marginTop: spacing.xs,
  },
});
