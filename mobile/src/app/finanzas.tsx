import { router } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ErrorBanner } from '@/components/ErrorBanner';
import { FinancialProductCard } from '@/components/FinancialProductCard';
import { FinancialRecipeModal } from '@/components/FinancialRecipeModal';
import { FinancialSupplyCard } from '@/components/FinancialSupplyCard';
import { FinancialSupplyModal } from '@/components/FinancialSupplyModal';
import {
  FinancialTabSelector,
  type FinancialTab,
} from '@/components/FinancialTabSelector';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderFinancialHealthResponse,
  ProviderFinancialProduct,
  ProviderSuppliesResponse,
  ProviderSupply,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

const TABS: FinancialTab[] = [
  { key: 'productos', label: 'Productos', icon: '📦' },
  { key: 'insumos', label: 'Insumos', icon: '🍱' },
];

type Notice = { text: string; error: boolean } | null;

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

/** Porcentaje con hasta dos decimales, igual que `finNum` de la web. */
function percent(value: number): string {
  return String(Math.round(value * 100) / 100);
}

/**
 * Salud financiera del comercio.
 *
 * Espeja `partials/salud-financiera.blade.php`: costo de produccion, margen
 * bruto y alertas por producto, mas la gestion de insumos y las fichas
 * tecnicas que alimentan esos calculos.
 */
export default function FinanzasScreen() {
  const [health, setHealth] = useState<ProviderFinancialHealthResponse | null>(null);
  const [suppliesData, setSuppliesData] = useState<ProviderSuppliesResponse | null>(
    null,
  );
  const [active, setActive] = useState<FinancialTab['key']>('productos');
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<Notice>(null);

  const [recipeProduct, setRecipeProduct] = useState<ProviderFinancialProduct | null>(
    null,
  );
  const [supplyModalOpen, setSupplyModalOpen] = useState(false);
  const [editingSupply, setEditingSupply] = useState<ProviderSupply | null>(null);

  /**
   * Trae ambas secciones juntas: la lista de productos y los insumos, que
   * alimentan el selector de la ficha tecnica.
   */
  async function fetchData(pageNumber: number) {
    const [healthResponse, suppliesResponse] = await Promise.all([
      api.providerFinancialHealth(pageNumber),
      api.providerFinancialSupplies(),
    ]);

    setHealth(healthResponse);
    setSuppliesData(suppliesResponse);
    setError(null);
  }

  async function load(mode: 'initial' | 'refresh' | 'silent' = 'initial') {
    if (mode === 'refresh') {
      setRefreshing(true);
    } else if (mode === 'initial') {
      setLoading(true);
    }

    try {
      await fetchData(page);
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo cargar la salud financiera.'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }

  useEffect(() => {
    let mounted = true;

    void (async () => {
      try {
        await fetchData(1);
      } catch (caught) {
        if (mounted) {
          setError(errorMessage(caught, 'No se pudo cargar la salud financiera.'));
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

  async function goPage(next: number) {
    if (!health) {
      return;
    }

    const target = Math.min(Math.max(1, next), health.pagination.last_page);

    if (target === page) {
      return;
    }

    setPage(target);
    setLoading(true);

    try {
      await fetchData(target);
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo cargar la salud financiera.'));
    } finally {
      setLoading(false);
    }
  }

  function openNewSupply() {
    setEditingSupply(null);
    setSupplyModalOpen(true);
  }

  function openEditSupply(supply: ProviderSupply) {
    setEditingSupply(supply);
    setSupplyModalOpen(true);
  }

  async function deleteSupply(supply: ProviderSupply) {
    try {
      const response = await api.providerFinancialDeleteSupply(supply.id);
      setNotice({ text: response.message || 'Insumo eliminado.', error: false });
      await load('silent');
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo eliminar el insumo.'));
      await load('silent');
    }
  }

  function confirmDeleteSupply(supply: ProviderSupply) {
    if (supply.recipes_count > 0) {
      Alert.alert(
        'No se puede eliminar',
        `"${supply.name}" se usa en ${supply.recipes_count} ficha(s) técnica(s). Quitálo de esas fichas antes de borrarlo.`,
      );
      return;
    }

    Alert.alert('Eliminar insumo', `¿Eliminar "${supply.name}"?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: () => void deleteSupply(supply),
      },
    ]);
  }

  function handleSavedSupply(message: string) {
    setNotice({ text: message, error: false });
    void load('silent');
  }

  function handleSavedRecipe() {
    setNotice({ text: 'Ficha técnica guardada.', error: false });
    void load('silent');
  }

  const threshold = health?.summary.min_margin_percent ?? 30;
  const summary = health?.summary;
  const pagination = health?.pagination;
  const products = health?.products ?? [];
  const supplies = suppliesData?.supplies ?? [];
  const units = suppliesData?.units_of_measure ?? [];

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
        keyboardShouldPersistTaps="handled"
      >
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>

        <View style={styles.header}>
          <Text style={styles.title}>Salud financiera</Text>
          <Text style={styles.subtitle}>
            Costo de producción y margen de cada producto · umbral recomendado{' '}
            {percent(threshold)}%
          </Text>
        </View>

        <FinancialTabSelector tabs={TABS} active={active} onChange={setActive} />

        <ErrorBanner message={error} />

        {notice ? (
          <View
            style={[
              styles.notice,
              notice.error ? styles.noticeError : styles.noticeSuccess,
            ]}
          >
            <Text
              style={[
                styles.noticeText,
                notice.error ? styles.noticeErrorText : styles.noticeSuccessText,
              ]}
            >
              {notice.text}
            </Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Cerrar aviso"
              onPress={() => setNotice(null)}
              style={styles.noticeClose}
            >
              <Text style={styles.noticeCloseText}>✕</Text>
            </Pressable>
          </View>
        ) : null}

        {loading ? (
          <View style={styles.loadingCard}>
            <ActivityIndicator color={colors.brand} />
            <Text style={styles.loadingText}>Calculando costos…</Text>
          </View>
        ) : !health ? (
          <View style={styles.emptyCard}>
            <Text style={styles.emptyTitle}>No se pudo cargar la salud financiera</Text>
            <Text style={styles.emptyText}>
              Revisá tu conexión e intentá de nuevo.
            </Text>
            <Pressable
              accessibilityRole="button"
              onPress={() => void load('initial')}
              style={styles.retryButton}
            >
              <Text style={styles.retryText}>↺ Reintentar</Text>
            </Pressable>
          </View>
        ) : active === 'productos' ? (
          <>
            <View style={styles.badgesRow}>
              <View style={styles.countBadge}>
                <Text style={styles.countBadgeText}>
                  {summary?.total_products ?? 0} productos
                </Text>
              </View>

              <View
                style={[
                  styles.badge,
                  (summary?.products_with_alert ?? 0) > 0
                    ? styles.badgeDanger
                    : styles.badgeSuccess,
                ]}
              >
                <Text
                  style={[
                    styles.badgeText,
                    (summary?.products_with_alert ?? 0) > 0
                      ? styles.badgeDangerText
                      : styles.badgeSuccessText,
                  ]}
                >
                  {summary?.products_with_alert ?? 0} con alerta
                </Text>
              </View>
            </View>

            {products.length === 0 ? (
              <View style={styles.emptyCard}>
                <Text style={styles.emptyText}>
                  Todavía no tenés productos. Cargalos desde Mi Catálogo para
                  calcular el costo y el margen.
                </Text>
              </View>
            ) : (
              products.map((product) => (
                <FinancialProductCard
                  key={product.product_id}
                  product={product}
                  threshold={threshold}
                  onOpenRecipe={() => setRecipeProduct(product)}
                />
              ))
            )}

            {pagination && pagination.last_page > 1 ? (
              <View style={styles.pager}>
                <Text style={styles.pagerLabel}>
                  Página {pagination.current_page} de {pagination.last_page}
                </Text>

                <View style={styles.pagerButtons}>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Página anterior"
                    disabled={pagination.current_page <= 1}
                    onPress={() => void goPage(page - 1)}
                    style={({ pressed }) => [
                      styles.pagerButton,
                      pressed && styles.pressed,
                      pagination.current_page <= 1 && styles.disabled,
                    ]}
                  >
                    <Text style={styles.pagerButtonText}>‹</Text>
                  </Pressable>

                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Página siguiente"
                    disabled={pagination.current_page >= pagination.last_page}
                    onPress={() => void goPage(page + 1)}
                    style={({ pressed }) => [
                      styles.pagerButton,
                      pressed && styles.pressed,
                      pagination.current_page >= pagination.last_page && styles.disabled,
                    ]}
                  >
                    <Text style={styles.pagerButtonText}>›</Text>
                  </Pressable>
                </View>
              </View>
            ) : null}
          </>
        ) : (
          <>
            <View style={styles.panelHead}>
              <View style={styles.panelHeadInfo}>
                <Text style={styles.panelTitle}>Insumos</Text>
                <Text style={styles.panelText}>
                  Lo que necesita tu comercio para producir. El costo de cada insumo
                  se usa para calcular el margen.
                </Text>
              </View>

              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Agregar insumo"
                onPress={openNewSupply}
                style={({ pressed }) => [
                  styles.addButton,
                  pressed && styles.pressed,
                ]}
              >
                <Text style={styles.addButtonText}>+</Text>
              </Pressable>
            </View>

            {supplies.length === 0 ? (
              <View style={styles.emptyCard}>
                <Text style={styles.emptyText}>
                  Todavía no cargaste insumos. Usá el botón de agregar insumo (+) para
                  empezar.
                </Text>
              </View>
            ) : (
              supplies.map((supply) => (
                <FinancialSupplyCard
                  key={supply.id}
                  supply={supply}
                  onEdit={() => openEditSupply(supply)}
                  onDelete={() => confirmDeleteSupply(supply)}
                />
              ))
            )}
          </>
        )}
      </ScrollView>

      {recipeProduct ? (
        <FinancialRecipeModal
          key={`recipe-${recipeProduct.product_id}`}
          visible
          product={recipeProduct}
          supplies={supplies}
          onClose={() => setRecipeProduct(null)}
          onSaved={handleSavedRecipe}
        />
      ) : null}

      {supplyModalOpen ? (
        <FinancialSupplyModal
          key={`supply-${editingSupply?.id ?? 'new'}`}
          visible
          supply={editingSupply}
          units={units}
          onClose={() => setSupplyModalOpen(false)}
          onSaved={handleSavedSupply}
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
  header: {
    marginBottom: spacing.md,
    paddingTop: spacing.xs,
  },
  title: {
    color: NAVY,
    fontSize: 22,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 19,
    marginTop: spacing.xs,
  },
  notice: {
    alignItems: 'flex-start',
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  noticeSuccess: {
    backgroundColor: '#ecfdf5',
    borderColor: '#a7f3d0',
  },
  noticeError: {
    backgroundColor: '#fef2f2',
    borderColor: '#fecaca',
  },
  noticeText: {
    flex: 1,
    fontSize: 12,
    lineHeight: 18,
  },
  noticeSuccessText: {
    color: '#047857',
  },
  noticeErrorText: {
    color: '#b91c1c',
  },
  noticeClose: {
    padding: 2,
  },
  noticeCloseText: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '700',
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
    fontSize: 15,
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
  pager: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.sm,
  },
  pagerLabel: {
    color: colors.muted,
    fontSize: 12,
  },
  pagerButtons: {
    flexDirection: 'row',
    gap: 6,
  },
  pagerButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  pagerButtonText: {
    color: colors.text,
    fontSize: 20,
    fontWeight: '700',
    lineHeight: 22,
  },
  panelHead: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  panelHeadInfo: {
    flex: 1,
  },
  panelTitle: {
    color: NAVY,
    fontSize: 16,
    fontWeight: '700',
  },
  panelText: {
    color: colors.muted,
    fontSize: 12,
    lineHeight: 18,
    marginTop: 3,
  },
  addButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: ACCENT,
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 38,
    justifyContent: 'center',
    width: 38,
  },
  addButtonText: {
    color: ACCENT,
    fontSize: 22,
    fontWeight: '700',
    lineHeight: 24,
  },
  pressed: {
    opacity: 0.8,
  },
  disabled: {
    opacity: 0.45,
  },
});
