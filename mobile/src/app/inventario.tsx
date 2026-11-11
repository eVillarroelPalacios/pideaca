import { router } from 'expo-router';
import { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ComboField } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { InventoryItemCard } from '@/components/InventoryItemCard';
import { InventoryStockModal } from '@/components/InventoryStockModal';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderInventoryItem,
  ProviderInventoryProductPayload,
  ProviderInventoryResponse,
} from '@/types/api';

const NAVY = '#0c2a4d';

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

export default function InventarioScreen() {
  const [data, setData] = useState<ProviderInventoryResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [controlEnabled, setControlEnabled] = useState(false);
  const [savingControl, setSavingControl] = useState(false);
  const [categoryFilter, setCategoryFilter] = useState<number | null>(null);
  const [togglingId, setTogglingId] = useState<number | null>(null);
  const [modalItem, setModalItem] = useState<ProviderInventoryItem | null>(null);

  /**
   * Trae el inventario. No toca `loading` para poder llamarse desde el efecto
   * sin renders en cascada.
   */
  async function fetchInventory() {
    try {
      const response = await api.providerInventory();
      setData(response);
      setControlEnabled(response.provider.has_inventory_control);
      setCategoryFilter((current) =>
        current !== null && !response.categories.some((category) => category.id === current)
          ? null
          : current,
      );
      setError(null);
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo cargar el inventario.'));
    }
  }

  async function load(mode: 'initial' | 'refresh' = 'initial') {
    if (mode === 'refresh') {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    try {
      await fetchInventory();
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }

  useEffect(() => {
    let active = true;

    void (async () => {
      try {
        await fetchInventory();
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    })();

    return () => {
      active = false;
    };
  }, []);

  async function toggleControl(next: boolean) {
    if (savingControl) {
      return;
    }

    setSavingControl(true);
    setControlEnabled(next);
    setError(null);

    try {
      const response = await api.providerInventoryUpdateSettings(next);
      setControlEnabled(response.provider.has_inventory_control);
      await fetchInventory();
    } catch (caught) {
      setControlEnabled(!next);
      setError(
        errorMessage(caught, 'No se pudo actualizar la configuración de inventario.'),
      );
    } finally {
      setSavingControl(false);
    }
  }

  /**
   * Interruptor rapido de la tarjeta: cambia el seguimiento sin abrir el modal,
   * conservando cantidad, minimo y unidad ya cargados (igual que la web).
   */
  async function toggleTrack(item: ProviderInventoryItem, next: boolean) {
    if (togglingId !== null) {
      return;
    }

    setTogglingId(item.product_id);
    setError(null);

    const payload: ProviderInventoryProductPayload = { track_stock: next };

    if (next) {
      payload.current_stock = item.current_stock;

      if (item.min_stock_alert !== null) {
        payload.min_stock_alert = item.min_stock_alert;
      }

      if (item.unit_of_measure) {
        payload.unit_of_measure_id = item.unit_of_measure.id;
      }
    }

    try {
      await api.providerInventoryUpdateProduct(item.product_id, payload);
      await fetchInventory();
    } catch (caught) {
      setError(
        errorMessage(caught, 'No se pudo cambiar el seguimiento del stock.'),
      );
    } finally {
      setTogglingId(null);
    }
  }

  const businessName = data?.provider.business_name ?? 'Tu comercio';

  const categories = useMemo(() => data?.categories ?? [], [data]);
  const allProducts = useMemo(() => data?.products ?? [], [data]);

  const products = useMemo(() => {
    if (categoryFilter === null) {
      return allProducts;
    }

    return allProducts.filter((product) => product.category_id === categoryFilter);
  }, [allProducts, categoryFilter]);

  const lowStockCount = useMemo(
    () => products.filter((product) => product.low_stock).length,
    [products],
  );

  const categoryOptions = useMemo(
    () =>
      categories.map((category) => ({
        value: category.id,
        label: category.name,
        hint: `${category.products_count} producto(s)`,
      })),
    [categories],
  );

  const refreshingControl = (
    <RefreshControl
      refreshing={refreshing}
      onRefresh={() => void load('refresh')}
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
      >
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>

        <View style={styles.headerRow}>
          <View style={styles.headerInfo}>
            <Text style={styles.title}>Inventario</Text>
            <Text style={styles.subtitle}>
              {businessName} · inventario {controlEnabled ? 'activado' : 'desactivado'}
            </Text>
          </View>

          <View style={styles.controlBox}>
            <Text style={styles.controlLabel}>Control de inventario</Text>
            <Switch
              value={controlEnabled}
              onValueChange={(value) => void toggleControl(value)}
              disabled={loading || savingControl}
              trackColor={{ false: '#cbd5e1', true: '#fdba74' }}
              thumbColor={controlEnabled ? colors.brand : '#f8fafc'}
            />
          </View>
        </View>

        <ErrorBanner message={error} />

        {loading ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator color={colors.brand} />
            <Text style={styles.loadingText}>Cargando inventario…</Text>
          </View>
        ) : data ? (
          <>
            <View style={styles.badgesRow}>
              <View style={styles.countBadge}>
                <Text style={styles.countBadgeText}>{products.length} productos</Text>
              </View>
              <View style={styles.countBadge}>
                <Text style={styles.countBadgeText}>{categories.length} categorías</Text>
              </View>
              <View
                style={[
                  styles.badge,
                  lowStockCount ? styles.badgeDanger : styles.badgeSuccess,
                ]}
              >
                <Text
                  style={[
                    styles.badgeText,
                    lowStockCount ? styles.badgeDangerText : styles.badgeSuccessText,
                  ]}
                >
                  {lowStockCount} con stock bajo
                </Text>
              </View>
              <View
                style={[
                  styles.badge,
                  controlEnabled ? styles.badgeSuccess : styles.badgeWarning,
                ]}
              >
                <Text
                  style={[
                    styles.badgeText,
                    controlEnabled ? styles.badgeSuccessText : styles.badgeWarningText,
                  ]}
                >
                  {controlEnabled ? 'Control activo' : 'Control desactivado'}
                </Text>
              </View>
            </View>

            <View style={styles.filterRow}>
              <View style={styles.filterCombo}>
                <ComboField<number>
                  label="Categoría"
                  title="Filtrar por categoría"
                  value={categoryFilter}
                  options={categoryOptions}
                  onChange={setCategoryFilter}
                  allowEmpty
                  emptyLabel="Todas las categorías"
                  onClear={() => setCategoryFilter(null)}
                  placeholder="Todas las categorías"
                />
              </View>
            </View>

            {!controlEnabled ? (
              <View style={styles.warning}>
                <Text style={styles.warningText}>
                  El control de inventario está desactivado: las ventas no descuentan stock
                  y los ajustes no se aplican. Activalo con el interruptor de arriba.
                </Text>
              </View>
            ) : null}

            {allProducts.length === 0 ? (
              <View style={styles.emptyCard}>
                <Text style={styles.emptyTitle}>Todavía no hay productos en tu catálogo.</Text>
                <Text style={styles.emptyText}>
                  Cargá productos desde Mi Catálogo para empezar a controlar el stock.
                </Text>
              </View>
            ) : products.length === 0 ? (
              <View style={styles.emptyCard}>
                <Text style={styles.emptyText}>No hay productos en esta categoría.</Text>
              </View>
            ) : (
              products.map((product) => (
                <InventoryItemCard
                  key={product.product_id}
                  item={product}
                  toggling={togglingId === product.product_id}
                  onToggleTrack={(next) => void toggleTrack(product, next)}
                  onEdit={() => setModalItem(product)}
                />
              ))
            )}
          </>
        ) : null}
      </ScrollView>

      {modalItem ? (
        <InventoryStockModal
          key={`stock-${modalItem.product_id}`}
          visible
          item={modalItem}
          units={data?.unit_of_measures ?? []}
          onClose={() => setModalItem(null)}
          onSaved={() => void load('refresh')}
        />
      ) : null}
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
  headerRow: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    justifyContent: 'space-between',
    paddingBottom: spacing.md,
    paddingTop: spacing.xs,
  },
  headerInfo: {
    flexShrink: 1,
  },
  title: {
    color: NAVY,
    fontSize: 22,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 13,
    marginTop: spacing.xs,
  },
  controlBox: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
  },
  controlLabel: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '600',
  },
  loadingBox: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    gap: spacing.sm,
    paddingVertical: spacing.xxl,
  },
  loadingText: {
    color: colors.muted,
    fontSize: 13,
  },
  badgesRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  countBadge: {
    backgroundColor: '#eef2f7',
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  countBadgeText: {
    color: NAVY,
    fontSize: 12,
    fontWeight: '600',
  },
  badge: {
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '600',
  },
  badgeSuccess: {
    backgroundColor: '#d1fae5',
  },
  badgeSuccessText: {
    color: '#047857',
  },
  badgeDanger: {
    backgroundColor: '#fee2e2',
  },
  badgeDangerText: {
    color: '#b91c1c',
  },
  badgeWarning: {
    backgroundColor: '#fff7ed',
  },
  badgeWarningText: {
    color: '#9a3412',
  },
  filterRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  filterCombo: {
    flex: 1,
  },
  warning: {
    backgroundColor: '#fffbeb',
    borderColor: '#fde68a',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  warningText: {
    color: '#92400e',
    fontSize: 13,
    lineHeight: 19,
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
    color: NAVY,
    fontSize: 14,
    fontWeight: '700',
    marginBottom: spacing.xs,
    textAlign: 'center',
  },
  emptyText: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
  },
});
