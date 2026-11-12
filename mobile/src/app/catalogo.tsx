import { router } from 'expo-router';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Animated,
  Easing,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  useWindowDimensions,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { CatalogProductCard } from '@/components/CatalogProductCard';
import { ComboField } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { OptionGroupsModal } from '@/components/OptionGroupsModal';
import { PrimaryButton } from '@/components/PrimaryButton';
import { ProductFormModal } from '@/components/ProductFormModal';
import { VariantsModal } from '@/components/VariantsModal';
import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderCatalogCategory,
  ProviderCatalogPreviewResponse,
  ProviderCatalogProduct,
  ProviderCatalogProductsResponse,
  ProviderCatalogUnitsResponse,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';
const PER_PAGE = 10;

type TabKey = 'catalogo' | 'productos' | 'categorias' | 'unidades';

type CatalogSection = {
  key: TabKey;
  label: string;
  icon: string;
};

const TABS: CatalogSection[] = [
  { key: 'catalogo', label: 'Mi catálogo', icon: '📖' },
  { key: 'productos', label: 'Productos', icon: '📦' },
  { key: 'categorias', label: 'Categorías', icon: '🏷️' },
  { key: 'unidades', label: 'Uds. de medida', icon: '⚖️' },
];

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

function invNumber(value: number): string {
  return Number.isInteger(value) ? String(value) : String(Number(value.toFixed(3)));
}

type PreviewEntry = {
  cat: ProviderCatalogPreviewResponse['categories'][number];
  products: ProviderCatalogProduct[];
};

function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

type CatalogSidebarProps = {
  visible: boolean;
  onClose: () => void;
  activeKey: TabKey;
  onSelect: (key: TabKey) => void;
  providerName: string;
  providerZone: string | null;
  isActive: boolean | null;
  productCount: number | null;
  categoryCount: number | null;
};

/**
 * Drawer de secciones del catálogo. Reutiliza el diseño y la animación de
 * `AccountSidebar`, pero con las cuatro vistas internas de esta pantalla.
 */
function CatalogSidebar({
  visible,
  onClose,
  activeKey,
  onSelect,
  providerName,
  providerZone,
  isActive,
  productCount,
  categoryCount,
}: CatalogSidebarProps) {
  const { width } = useWindowDimensions();
  const panelWidth = Math.min(360, Math.round(width * 0.85));
  const [translateX] = useState(() => new Animated.Value(-panelWidth));
  const closingRef = useRef(false);

  useEffect(() => {
    if (!visible) {
      return;
    }

    closingRef.current = false;
    translateX.setValue(-panelWidth);
    Animated.timing(translateX, {
      toValue: 0,
      duration: 220,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: true,
    }).start();
  }, [visible, panelWidth, translateX]);

  function handleClose() {
    if (closingRef.current) {
      return;
    }

    closingRef.current = true;
    Animated.timing(translateX, {
      toValue: -panelWidth,
      duration: 180,
      easing: Easing.in(Easing.cubic),
      useNativeDriver: true,
    }).start(() => {
      closingRef.current = false;
      onClose();
    });
  }

  function handleSelect(key: TabKey) {
    onClose();
    onSelect(key);
  }

  return (
    <Modal
      visible={visible}
      transparent
      animationType="none"
      onRequestClose={handleClose}
      statusBarTranslucent
    >
      <View style={styles.sidebarBackdrop}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Cerrar secciones del catálogo"
          onPress={handleClose}
          style={styles.sidebarBackdropTap}
        />

        <Animated.View
          style={[
            styles.sidebarPanel,
            { width: panelWidth, transform: [{ translateX }] },
          ]}
        >
          <SafeAreaView style={styles.sidebarSafe} edges={['top', 'bottom', 'left']}>
            <View style={styles.sidebarHeader}>
              <Text style={styles.sidebarTitle}>Catálogo</Text>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Cerrar secciones"
                onPress={handleClose}
                style={styles.sidebarCloseButton}
              >
                <Text style={styles.sidebarCloseText}>✕</Text>
              </Pressable>
            </View>

            <ScrollView
              contentContainerStyle={styles.sidebarScroll}
              keyboardShouldPersistTaps="handled"
            >
              <View style={styles.sidebarProvider}>
                <View style={styles.sidebarAvatar}>
                  <Text style={styles.sidebarAvatarText}>{initials(providerName)}</Text>
                </View>
                <View style={styles.sidebarProviderInfo}>
                  <Text style={styles.sidebarProviderName} numberOfLines={1}>
                    {providerName}
                  </Text>
                  {providerZone ? (
                    <Text style={styles.sidebarProviderZone} numberOfLines={1}>
                      {providerZone}
                    </Text>
                  ) : null}
                </View>
              </View>

              <View style={styles.sidebarBadges}>
                {isActive !== null ? (
                  <View
                    style={[
                      styles.sidebarBadge,
                      isActive
                        ? styles.sidebarBadgeActive
                        : styles.sidebarBadgePaused,
                    ]}
                  >
                    <Text
                      style={[
                        styles.sidebarBadgeText,
                        isActive
                          ? styles.sidebarBadgeTextActive
                          : styles.sidebarBadgeTextPaused,
                      ]}
                    >
                      {isActive ? 'Activo' : 'Pausado'}
                    </Text>
                  </View>
                ) : null}

                {productCount !== null ? (
                  <View style={styles.sidebarBadge}>
                    <Text style={styles.sidebarBadgeText}>
                      {productCount} {productCount === 1 ? 'producto' : 'productos'}
                    </Text>
                  </View>
                ) : null}

                {categoryCount !== null ? (
                  <View style={styles.sidebarBadge}>
                    <Text style={styles.sidebarBadgeText}>
                      {categoryCount} {categoryCount === 1 ? 'categoría' : 'categorías'}
                    </Text>
                  </View>
                ) : null}
              </View>

              <Text style={styles.sidebarNavTitle}>Secciones</Text>

              {TABS.map((section) => {
                const active = activeKey === section.key;

                return (
                  <Pressable
                    key={section.key}
                    accessibilityRole="button"
                    accessibilityState={{ selected: active }}
                    accessibilityLabel={section.label}
                    onPress={() => handleSelect(section.key)}
                    style={[
                      styles.sidebarNavItem,
                      active && styles.sidebarNavItemActive,
                    ]}
                  >
                    <Text style={styles.sidebarNavIcon}>{section.icon}</Text>
                    <Text
                      style={[
                        styles.sidebarNavLabel,
                        active && styles.sidebarNavLabelActive,
                      ]}
                      numberOfLines={1}
                    >
                      {section.label}
                    </Text>
                    {active ? <View style={styles.sidebarActiveDot} /> : null}
                  </Pressable>
                );
              })}
            </ScrollView>

            <View style={styles.sidebarFooter}>
              <PrimaryButton title="Cerrar" variant="ghost" onPress={handleClose} />
            </View>
          </SafeAreaView>
        </Animated.View>
      </View>
    </Modal>
  );
}

export default function CatalogoScreen() {
  const [tab, setTab] = useState<TabKey>('catalogo');
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [preview, setPreview] = useState<ProviderCatalogPreviewResponse | null>(null);
  const [previewCategory, setPreviewCategory] = useState<number | null>(null);
  const [previewPage, setPreviewPage] = useState(1);

  const [catalog, setCatalog] = useState<ProviderCatalogProductsResponse | null>(null);
  const [productCategory, setProductCategory] = useState<number | null>(null);
  const [categoryNames, setCategoryNames] = useState<Record<number, string>>({});
  const [newCategoryName, setNewCategoryName] = useState('');
  const [categorySaving, setCategorySaving] = useState(false);

  const [unitsData, setUnitsData] = useState<ProviderCatalogUnitsResponse | null>(null);

  const [productModal, setProductModal] = useState<{
    visible: boolean;
    product: ProviderCatalogProduct | null;
  }>({ visible: false, product: null });
  const [variantsProduct, setVariantsProduct] = useState<ProviderCatalogProduct | null>(null);
  const [optionsProduct, setOptionsProduct] = useState<ProviderCatalogProduct | null>(null);

  /**
   * Trae los datos de una sección. No toca `loading` para poder llamarse desde
   * el efecto sin provocar renders en cascada (el spinner se maneja afuera).
   */
  async function fetchTab(key: TabKey) {
    try {
      if (key === 'catalogo') {
        const data = await api.providerCatalogPreview();
        setPreview(data);
        setPreviewPage(1);
        setPreviewCategory((current) =>
          current !== null && !data.categories.some((category) => category.id === current)
            ? null
            : current,
        );
      } else if (key === 'productos' || key === 'categorias') {
        const data = await api.providerCatalogProducts();
        setCatalog(data);
        setCategoryNames(
          Object.fromEntries(data.categories.map((category) => [category.id, category.name])),
        );
        setProductCategory((current) =>
          current !== null && !data.categories.some((category) => category.id === current)
            ? null
            : current,
        );
      } else {
        const data = await api.providerCatalogUnits();
        setUnitsData(data);
      }

      setError(null);
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo cargar la información del catálogo.'));
    }
  }

  async function load(key: TabKey, mode: 'initial' | 'refresh' = 'initial') {
    if (mode === 'refresh') {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    try {
      await fetchTab(key);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }

  useEffect(() => {
    let active = true;

    void (async () => {
      try {
        await fetchTab(tab);
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    })();

    return () => {
      active = false;
    };
  }, [tab]);

  const providerName =
    preview?.provider.business_name ??
    catalog?.provider.business_name ??
    unitsData?.provider.business_name ??
    'Tu comercio';

  const providerZone = preview?.provider.zone ?? catalog?.provider.zone ?? null;
  const providerPaused =
    (preview?.provider.is_active ?? catalog?.provider.is_active) === false;
  const providerLoaded = Boolean(preview || catalog);
  const providerIsActive = providerLoaded ? !providerPaused : null;
  const productCount = catalog
    ? catalog.products.length
    : preview
      ? preview.categories.reduce(
          (total, category) => total + category.products.length,
          0,
        )
      : null;
  const categoryCount = catalog
    ? catalog.categories.length
    : preview
      ? preview.categories.length
      : null;

  const categoryOptions = useMemo(
    () =>
      (catalog?.categories ?? []).map((category) => ({
        value: category.id,
        label: category.name,
      })),
    [catalog],
  );

  // --- Vista previa (Mi catálogo) ---

  const previewData = useMemo(() => {
    const categories = preview?.categories ?? [];

    if (!categories.length) {
      return { shown: [], entries: [] as PreviewEntry[], total: 0, totalPages: 1, page: 1 };
    }

    const selected = previewCategory;
    const shown = selected
      ? categories.filter((category) => category.id === selected)
      : categories;

    const flat = shown.flatMap((category) =>
      category.products.map((product) => ({ cat: category, product })),
    );

    const totalPages = Math.max(1, Math.ceil(flat.length / PER_PAGE));
    const page = Math.min(Math.max(1, previewPage), totalPages);
    const start = (page - 1) * PER_PAGE;
    const end = start + PER_PAGE;

    let cursor = 0;
    const entries: PreviewEntry[] = [];

    shown.forEach((category) => {
      const inPage: ProviderCatalogProduct[] = [];

      category.products.forEach((product) => {
        if (cursor >= start && cursor < end) {
          inPage.push(product);
        }
        cursor += 1;
      });

      if (inPage.length) {
        entries.push({ cat: category, products: inPage });
      } else if (!category.products.length && page === 1) {
        entries.push({ cat: category, products: [] });
      }
    });

    return { shown, entries, total: flat.length, totalPages, page };
  }, [preview, previewCategory, previewPage]);

  // --- Productos ---

  const filteredProducts = useMemo(() => {
    const all = catalog?.products ?? [];

    return productCategory
      ? all.filter((product) => product.category_id === productCategory)
      : all;
  }, [catalog, productCategory]);

  async function ensureCatalog(): Promise<ProviderCatalogProductsResponse | null> {
    if (catalog) {
      return catalog;
    }

    try {
      const data = await api.providerCatalogProducts();
      setCatalog(data);
      setCategoryNames(
        Object.fromEntries(data.categories.map((category) => [category.id, category.name])),
      );

      return data;
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudieron cargar los datos del catálogo.'));

      return null;
    }
  }

  async function editProduct(product: ProviderCatalogProduct | null) {
    const data = await ensureCatalog();

    if (!data) {
      return;
    }

    setProductModal({ visible: true, product });
  }

  // --- Categorías ---

  async function createCategory() {
    const nombre = newCategoryName.trim();

    if (nombre.length < 2) {
      setError('El nombre de la categoría debe tener al menos 2 caracteres.');
      return;
    }

    setCategorySaving(true);
    setError(null);

    try {
      await api.providerCatalogCreateCategory(nombre);
      setNewCategoryName('');
      await load('categorias');
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo crear la categoría.'));
    } finally {
      setCategorySaving(false);
    }
  }

  async function renameCategory(category: ProviderCatalogCategory) {
    const nombre = (categoryNames[category.id] ?? '').trim();

    if (nombre.length < 2) {
      setError('El nombre de la categoría debe tener al menos 2 caracteres.');
      return;
    }

    if (nombre === category.name) {
      return;
    }

    setError(null);

    try {
      await api.providerCatalogUpdateCategory(category.id, { name: nombre });
      await load('categorias');
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo renombrar la categoría.'));
    }
  }

  async function moveCategory(category: ProviderCatalogCategory, direction: -1 | 1) {
    if (!catalog) {
      return;
    }

    const list = catalog.categories.slice();
    const index = list.findIndex((item) => item.id === category.id);
    const target = index + direction;

    if (index < 0 || target < 0 || target >= list.length) {
      return;
    }

    const [moved] = list.splice(index, 1);
    list.splice(target, 0, moved);
    setCatalog({ ...catalog, categories: list });
    setError(null);

    try {
      for (let position = 0; position < list.length; position += 1) {
        await api.providerCatalogUpdateCategory(list[position].id, {
          sort_order: (position + 1) * 10,
        });
      }

      await load('categorias');
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo reordenar las categorías.'));
      await load('categorias');
    }
  }

  function deleteCategory(category: ProviderCatalogCategory) {
    Alert.alert(
      'Eliminar categoría',
      `¿Eliminar la categoría "${category.name}"?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Eliminar',
          style: 'destructive',
          onPress: () => {
            void (async () => {
              setError(null);

              try {
                await api.providerCatalogDeleteCategory(category.id);
                await load('categorias');
              } catch (caught) {
                setError(errorMessage(caught, 'No se pudo eliminar la categoría.'));
              }
            })();
          },
        },
      ],
    );
  }

  function selectSection(key: TabKey) {
    if (key === tab) {
      return;
    }

    setLoading(true);
    setTab(key);
  }

  const refreshingControl = (
    <RefreshControl
      refreshing={refreshing}
      onRefresh={() => void load(tab, 'refresh')}
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

        <View style={styles.header}>
          <View style={styles.headerText}>
            <Text style={styles.title}>Catálogo</Text>
            <Text style={styles.subtitle} numberOfLines={1}>
              {providerName}
            </Text>
          </View>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Abrir secciones del catálogo"
            onPress={() => setSidebarOpen(true)}
            style={styles.sectionHint}
          >
            <Text style={styles.sectionHintText} numberOfLines={1}>
              Secciones
            </Text>
            <Text style={styles.sectionChevron}>›</Text>
          </Pressable>
        </View>

        <ErrorBanner message={error} />

        {loading ? (
          <View style={styles.loadingBox}>
            <ActivityIndicator color={colors.brand} />
            <Text style={styles.loadingText}>Cargando…</Text>
          </View>
        ) : (
          <>
            {tab === 'catalogo' ? (
              <View>
                <View style={styles.panelHead}>
                  <Text style={styles.panelTitle}>Mi catálogo</Text>
                  <Text style={styles.panelSubtitle}>
                    Vista previa de lo que ven tus clientes en la tienda.
                  </Text>
                </View>

                {preview && preview.categories.length === 0 ? (
                  <View style={styles.emptyCard}>
                    <Text style={styles.emptyTitle}>Tu catálogo está vacío.</Text>
                    <Text style={styles.emptyText}>
                      Creá categorías y productos para que los clientes puedan pedir.
                    </Text>
                  </View>
                ) : null}

                {preview && preview.categories.length > 0 ? (
                  <>
                    <View style={styles.badgesRow}>
                      <View style={styles.countBadge}>
                        <Text style={styles.countBadgeText}>
                          {previewData.shown.length}{' '}
                          {previewData.shown.length === 1 ? 'categoría' : 'categorías'}
                        </Text>
                      </View>
                      <View style={styles.countBadge}>
                        <Text style={styles.countBadgeText}>
                          {previewData.total}{' '}
                          {previewData.total === 1 ? 'producto' : 'productos'}
                        </Text>
                      </View>
                      {providerZone ? (
                        <View style={styles.zoneBadge}>
                          <Text style={styles.zoneBadgeText}>{providerZone}</Text>
                        </View>
                      ) : null}
                      {providerPaused ? (
                        <View style={styles.pausedBadge}>
                          <Text style={styles.pausedBadgeText}>Comercio pausado</Text>
                        </View>
                      ) : null}
                    </View>

                    <View style={styles.filterRow}>
                      <View style={styles.filterCombo}>
                        <ComboField<number>
                          label="Categoría"
                          title="Filtrar por categoría"
                          value={previewCategory}
                          options={preview.categories.map((category) => ({
                            value: category.id,
                            label: category.name,
                          }))}
                          onChange={(value) => {
                            setPreviewCategory(value);
                            setPreviewPage(1);
                          }}
                          allowEmpty
                          emptyLabel="Todas las categorías"
                          onClear={() => {
                            setPreviewCategory(null);
                            setPreviewPage(1);
                          }}
                          placeholder="Todas las categorías"
                        />
                      </View>
                    </View>

                    {previewData.entries.map((entry) => (
                      <View key={entry.cat.id} style={styles.categoryBlock}>
                        <View style={styles.categoryHeader}>
                          <Text style={styles.categoryTitle}>
                            {previewCategory
                              ? `Filtrando: ${entry.cat.name}`
                              : entry.cat.name}
                          </Text>
                          <Text style={styles.categoryCount}>
                            {entry.products.length}{' '}
                            {entry.products.length === 1 ? 'producto' : 'productos'}
                          </Text>
                        </View>

                        {entry.products.length === 0 ? (
                          <Text style={styles.categoryEmpty}>
                            Esta categoría todavía no tiene productos.
                          </Text>
                        ) : (
                          entry.products.map((product) => (
                            <CatalogProductCard
                              key={product.id}
                              product={product}
                              onEdit={(item) => void editProduct(item)}
                            />
                          ))
                        )}
                      </View>
                    ))}

                    {previewData.totalPages > 1 ? (
                      <View style={styles.pagination}>
                        <Pressable
                          accessibilityRole="button"
                          accessibilityLabel="Página anterior"
                          disabled={previewData.page <= 1}
                          onPress={() => setPreviewPage((current) => Math.max(1, current - 1))}
                          style={[
                            styles.pageButton,
                            previewData.page <= 1 && styles.pageButtonDisabled,
                          ]}
                        >
                          <Text style={styles.pageButtonText}>‹</Text>
                        </Pressable>
                        <Text style={styles.pageText}>
                          Página {previewData.page} de {previewData.totalPages}
                        </Text>
                        <Pressable
                          accessibilityRole="button"
                          accessibilityLabel="Página siguiente"
                          disabled={previewData.page >= previewData.totalPages}
                          onPress={() =>
                            setPreviewPage((current) =>
                              Math.min(previewData.totalPages, current + 1),
                            )
                          }
                          style={[
                            styles.pageButton,
                            previewData.page >= previewData.totalPages &&
                              styles.pageButtonDisabled,
                          ]}
                        >
                          <Text style={styles.pageButtonText}>›</Text>
                        </Pressable>
                      </View>
                    ) : null}
                  </>
                ) : null}
              </View>
            ) : null}

            {tab === 'productos' ? (
              <View>
                <View style={styles.panelHeadRow}>
                  <View style={styles.panelHeadText}>
                    <Text style={styles.panelTitle}>Productos</Text>
                    <Text style={styles.panelSubtitle}>
                      Alta y edición de productos, con sus variantes y agregados.
                    </Text>
                  </View>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Agregar producto"
                    onPress={() => void editProduct(null)}
                    style={styles.addButton}
                  >
                    <Text style={styles.addButtonText}>+</Text>
                  </Pressable>
                </View>

                {catalog ? (
                  <>
                    <View style={styles.badgesRow}>
                      <View style={styles.countBadge}>
                        <Text style={styles.countBadgeText}>
                          {filteredProducts.length} productos
                        </Text>
                      </View>
                      <View style={styles.countBadge}>
                        <Text style={styles.countBadgeText}>
                          {catalog.categories.length} categorías
                        </Text>
                      </View>
                    </View>

                    <View style={styles.filterRow}>
                      <View style={styles.filterCombo}>
                        <ComboField<number>
                          label="Categoría"
                          title="Filtrar por categoría"
                          value={productCategory}
                          options={categoryOptions}
                          onChange={setProductCategory}
                          allowEmpty
                          emptyLabel="Todas las categorías"
                          onClear={() => setProductCategory(null)}
                          placeholder="Todas las categorías"
                        />
                      </View>
                    </View>

                    {filteredProducts.length === 0 ? (
                      <View style={styles.emptyCard}>
                        <Text style={styles.emptyText}>
                          {catalog.products.length === 0
                            ? 'Todavía no cargaste productos.'
                            : 'No hay productos en esta categoría.'}
                        </Text>
                        {catalog.products.length === 0 ? (
                          <View style={styles.emptyButton}>
                            <PrimaryButton
                              title="Agregar producto"
                              onPress={() => void editProduct(null)}
                            />
                          </View>
                        ) : null}
                      </View>
                    ) : (
                      <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                        <View style={styles.table}>
                          <View style={[styles.tableRow, styles.tableHead]}>
                            <Text style={[styles.th, styles.colProduct]}>Producto</Text>
                            <Text style={[styles.th, styles.colPrice]}>Precio</Text>
                            <Text style={[styles.th, styles.colVariants]}>Variantes</Text>
                            <Text style={[styles.th, styles.colAddons]}>Agregados</Text>
                            <Text style={[styles.th, styles.colStock]}>Stock</Text>
                            <Text style={[styles.th, styles.colActions]}>Acciones</Text>
                          </View>

                          {filteredProducts.map((product, index) => {
                            const optionsCount = product.option_groups.reduce(
                              (total, group) => total + group.options.length,
                              0,
                            );

                            return (
                              <View
                                key={product.id}
                                style={[
                                  styles.tableRow,
                                  index % 2 === 1 && styles.tableRowAlt,
                                ]}
                              >
                                <View style={[styles.td, styles.colProduct]}>
                                  <Text style={styles.productName}>{product.name}</Text>
                                  {!product.is_available ? (
                                    <View style={styles.miniBadgeOff}>
                                      <Text style={styles.miniBadgeOffText}>
                                        No disponible
                                      </Text>
                                    </View>
                                  ) : null}
                                </View>

                                <Text style={[styles.td, styles.colPrice, styles.priceText]}>
                                  {money(product.price)}
                                </Text>

                                <View style={[styles.td, styles.colVariants]}>
                                  {product.variants.length ? (
                                    <View style={styles.variantsCell}>
                                      <View style={styles.miniBadgeNavy}>
                                        <Text style={styles.miniBadgeNavyText}>
                                          {product.variants.length}
                                        </Text>
                                      </View>
                                      <Text style={styles.cellText}>
                                        {product.variants
                                          .map((variant) => variant.name)
                                          .join(', ')}
                                      </Text>
                                    </View>
                                  ) : (
                                    <Text style={styles.cellMuted}>ninguna</Text>
                                  )}
                                </View>

                                <View style={[styles.td, styles.colAddons]}>
                                  {product.option_groups.length ? (
                                    <View style={styles.orangeBadge}>
                                      <Text style={styles.orangeBadgeText}>
                                        {product.option_groups.length} grupo(s) ·{' '}
                                        {optionsCount} opción(es)
                                      </Text>
                                    </View>
                                  ) : (
                                    <Text style={styles.cellMuted}>ninguno</Text>
                                  )}
                                </View>

                                <View style={[styles.td, styles.colStock]}>
                                  {product.track_stock ? (
                                    <View style={styles.greenBadge}>
                                      <Text style={styles.greenBadgeText}>
                                        {invNumber(product.current_stock)}
                                        {product.unit_of_measure
                                          ? ` ${product.unit_of_measure.symbol}`
                                          : ''}
                                      </Text>
                                    </View>
                                  ) : (
                                    <View style={styles.grayBadge}>
                                      <Text style={styles.grayBadgeText}>S/S</Text>
                                    </View>
                                  )}
                                </View>

                                <View style={[styles.td, styles.colActions, styles.actionsCell]}>
                                  <Pressable
                                    accessibilityRole="button"
                                    onPress={() => void editProduct(product)}
                                    style={styles.actionButton}
                                  >
                                    <Text style={styles.actionText}>Editar</Text>
                                  </Pressable>
                                  <Pressable
                                    accessibilityRole="button"
                                    onPress={() => setVariantsProduct(product)}
                                    style={styles.actionButton}
                                  >
                                    <Text style={styles.actionText}>Variantes</Text>
                                  </Pressable>
                                  <Pressable
                                    accessibilityRole="button"
                                    onPress={() => setOptionsProduct(product)}
                                    style={styles.actionButton}
                                  >
                                    <Text style={styles.actionText}>Agregados</Text>
                                  </Pressable>
                                </View>
                              </View>
                            );
                          })}
                        </View>
                      </ScrollView>
                    )}
                  </>
                ) : null}
              </View>
            ) : null}

            {tab === 'categorias' ? (
              <View>
                <View style={styles.panelHead}>
                  <Text style={styles.panelTitle}>Categorías</Text>
                  <Text style={styles.panelSubtitle}>
                    Creá, renombrá y reordená las categorías del catálogo.
                  </Text>
                </View>

                {catalog ? (
                  <>
                    <View style={styles.badgesRow}>
                      <View style={styles.countBadge}>
                        <Text style={styles.countBadgeText}>
                          {catalog.categories.length} categorías
                        </Text>
                      </View>
                    </View>

                    <View style={styles.newCategoryCard}>
                      <Text style={styles.newCategoryLabel}>Nueva categoría</Text>
                      <View style={styles.newCategoryRow}>
                        <TextInput
                          value={newCategoryName}
                          onChangeText={setNewCategoryName}
                          placeholder="Ej: Bebidas frías"
                          placeholderTextColor={colors.muted}
                          maxLength={80}
                          style={styles.categoryInput}
                        />
                        <View style={styles.newCategoryButton}>
                          <PrimaryButton
                            title="Agregar"
                            onPress={() => void createCategory()}
                            loading={categorySaving}
                          />
                        </View>
                      </View>
                    </View>

                    <View style={styles.categoryList}>
                      {catalog.categories.length === 0 ? (
                        <Text style={styles.categoryEmpty}>
                          Todavía no tenés categorías: creá la primera arriba.
                        </Text>
                      ) : null}

                      {catalog.categories.map((category, index) => (
                        <View key={category.id} style={styles.categoryRow}>
                          <TextInput
                            value={categoryNames[category.id] ?? category.name}
                            onChangeText={(value) =>
                              setCategoryNames((current) => ({
                                ...current,
                                [category.id]: value,
                              }))
                            }
                            maxLength={80}
                            style={[styles.categoryInput, styles.categoryRowInput]}
                          />

                          <View style={styles.categoryMeta}>
                            <View
                              style={
                                category.products_count
                                  ? styles.countBadge
                                  : styles.grayBadge
                              }
                            >
                              <Text
                                style={
                                  category.products_count
                                    ? styles.countBadgeText
                                    : styles.grayBadgeText
                                }
                              >
                                {category.products_count} producto(s)
                              </Text>
                            </View>
                            {!category.is_active ? (
                              <View style={styles.zoneBadge}>
                                <Text style={styles.zoneBadgeText}>Oculta</Text>
                              </View>
                            ) : null}
                          </View>

                          <View style={styles.categoryActions}>
                            <Pressable
                              accessibilityRole="button"
                              onPress={() => void renameCategory(category)}
                              style={styles.actionButton}
                            >
                              <Text style={styles.actionText}>Guardar</Text>
                            </Pressable>
                            <Pressable
                              accessibilityRole="button"
                              disabled={index === 0}
                              onPress={() => void moveCategory(category, -1)}
                              style={[
                                styles.iconAction,
                                index === 0 && styles.iconActionDisabled,
                              ]}
                            >
                              <Text style={styles.iconActionText}>↑</Text>
                            </Pressable>
                            <Pressable
                              accessibilityRole="button"
                              disabled={index === catalog.categories.length - 1}
                              onPress={() => void moveCategory(category, 1)}
                              style={[
                                styles.iconAction,
                                index === catalog.categories.length - 1 &&
                                  styles.iconActionDisabled,
                              ]}
                            >
                              <Text style={styles.iconActionText}>↓</Text>
                            </Pressable>
                            <Pressable
                              accessibilityRole="button"
                              onPress={() => deleteCategory(category)}
                              style={[styles.actionButton, styles.deleteButton]}
                            >
                              <Text style={styles.deleteText}>Eliminar</Text>
                            </Pressable>
                          </View>
                        </View>
                      ))}
                    </View>
                  </>
                ) : null}
              </View>
            ) : null}

            {tab === 'unidades' ? (
              <View>
                <View style={styles.panelHead}>
                  <Text style={styles.panelTitle}>Unidades de medida</Text>
                  <Text style={styles.panelSubtitle}>
                    Referencia de las unidades disponibles y cuántos productos las usan.
                  </Text>
                </View>

                {unitsData ? (
                  <>
                    <View style={styles.badgesRow}>
                      <View style={styles.countBadge}>
                        <Text style={styles.countBadgeText}>
                          {unitsData.units.length} unidades
                        </Text>
                      </View>
                      <View
                        style={
                          unitsData.products_with_unit
                            ? styles.greenBadge
                            : styles.grayBadge
                        }
                      >
                        <Text
                          style={
                            unitsData.products_with_unit
                              ? styles.greenBadgeText
                              : styles.grayBadgeText
                          }
                        >
                          {unitsData.products_with_unit} producto(s) con unidad
                        </Text>
                      </View>
                    </View>

                    <View style={styles.notice}>
                      <Text style={styles.noticeText}>
                        Las unidades de medida son de toda la plataforma: se usan para
                        controlar el stock de los productos. Si necesitás una que no
                        aparece, escribinos y la agregamos.
                      </Text>
                    </View>

                    <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                      <View style={styles.table}>
                        <View style={[styles.tableRow, styles.tableHead]}>
                          <Text style={[styles.th, styles.unitName]}>Unidad</Text>
                          <Text style={[styles.th, styles.unitSymbol]}>Símbolo</Text>
                          <Text style={[styles.th, styles.unitFactor]}>Equivalencia</Text>
                          <Text style={[styles.th, styles.unitKinds]}>Cantidades</Text>
                          <Text style={[styles.th, styles.unitCount]}>Tus productos</Text>
                        </View>

                        {unitsData.units.map((unit, index) => (
                          <View
                            key={unit.id}
                            style={[styles.tableRow, index % 2 === 1 && styles.tableRowAlt]}
                          >
                            <Text style={[styles.td, styles.unitName, styles.unitNameText]}>
                              {unit.name}
                            </Text>
                            <Text style={[styles.td, styles.unitSymbol, styles.cellMuted]}>
                              {unit.symbol}
                            </Text>
                            <Text style={[styles.td, styles.unitFactor, styles.cellText]}>
                              1 {unit.name} = {invNumber(unit.base_conversion_factor)} und
                            </Text>
                            <View style={[styles.td, styles.unitKinds]}>
                              {unit.is_integer_only ? (
                                <View style={styles.zoneBadge}>
                                  <Text style={styles.zoneBadgeText}>Solo enteras</Text>
                                </View>
                              ) : (
                                <View style={styles.greenBadge}>
                                  <Text style={styles.greenBadgeText}>Admite decimales</Text>
                                </View>
                              )}
                            </View>
                            <Text
                              style={[
                                styles.td,
                                styles.unitCount,
                                styles.unitCountText,
                                !unit.products_count && styles.cellMuted,
                              ]}
                            >
                              {unit.products_count}
                            </Text>
                          </View>
                        ))}
                      </View>
                    </ScrollView>
                  </>
                ) : null}
              </View>
            ) : null}
          </>
        )}
      </ScrollView>

      {productModal.visible ? (
        <ProductFormModal
          key={productModal.product?.id ?? 'new'}
          visible
          product={productModal.product}
          categories={catalog?.categories ?? []}
          units={catalog?.unit_of_measures ?? []}
          onClose={() => setProductModal({ visible: false, product: null })}
          onSaved={() => void load(tab)}
        />
      ) : null}

      {variantsProduct ? (
        <VariantsModal
          key={`variants-${variantsProduct.id}`}
          visible
          product={variantsProduct}
          onClose={() => setVariantsProduct(null)}
          onSaved={() => void load(tab)}
        />
      ) : null}

      {optionsProduct ? (
        <OptionGroupsModal
          key={`options-${optionsProduct.id}`}
          visible
          product={optionsProduct}
          onClose={() => setOptionsProduct(null)}
          onSaved={() => void load(tab)}
        />
      ) : null}

      <CatalogSidebar
        visible={sidebarOpen}
        onClose={() => setSidebarOpen(false)}
        activeKey={tab}
        onSelect={selectSection}
        providerName={providerName}
        providerZone={providerZone}
        isActive={providerIsActive}
        productCount={productCount}
        categoryCount={categoryCount}
      />
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
  header: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'space-between',
    paddingBottom: spacing.md,
    paddingTop: spacing.xs,
  },
  headerText: {
    flex: 1,
    minWidth: 0,
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
  sectionHint: {
    alignItems: 'center',
    flexDirection: 'row',
    flexShrink: 0,
    gap: 4,
  },
  sectionHintText: {
    color: colors.brandDark,
    fontSize: 11,
    fontWeight: '700',
  },
  sectionChevron: {
    color: colors.brandDark,
    fontSize: 20,
    fontWeight: '700',
    lineHeight: 20,
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
  panelHead: {
    marginBottom: spacing.md,
  },
  panelHeadRow: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  panelHeadText: {
    flexShrink: 1,
  },
  panelTitle: {
    color: NAVY,
    fontSize: 17,
    fontWeight: '800',
  },
  panelSubtitle: {
    color: colors.muted,
    fontSize: 13,
    marginTop: spacing.xs,
  },
  addButton: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: radius.sm,
    height: 38,
    justifyContent: 'center',
    width: 42,
  },
  addButtonText: {
    color: colors.surface,
    fontSize: 22,
    fontWeight: '700',
    lineHeight: 24,
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
  zoneBadge: {
    backgroundColor: '#fff7ed',
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  zoneBadgeText: {
    color: '#9a3412',
    fontSize: 12,
    fontWeight: '600',
  },
  pausedBadge: {
    backgroundColor: '#fee2e2',
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  pausedBadgeText: {
    color: '#b91c1c',
    fontSize: 12,
    fontWeight: '600',
  },
  greenBadge: {
    backgroundColor: '#d1fae5',
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  greenBadgeText: {
    color: '#047857',
    fontSize: 12,
    fontWeight: '600',
  },
  grayBadge: {
    backgroundColor: '#f1f5f9',
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  grayBadgeText: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
  },
  orangeBadge: {
    alignItems: 'flex-start',
    backgroundColor: '#fff7ed',
    borderRadius: radius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  orangeBadgeText: {
    color: '#9a3412',
    fontSize: 12,
    fontWeight: '600',
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
  categoryBlock: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    overflow: 'hidden',
  },
  categoryHeader: {
    alignItems: 'center',
    backgroundColor: NAVY,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  categoryTitle: {
    color: colors.surface,
    flexShrink: 1,
    fontSize: 13,
    fontWeight: '700',
  },
  categoryCount: {
    color: 'rgba(255,255,255,0.75)',
    fontSize: 11,
  },
  categoryEmpty: {
    color: '#94a3b8',
    fontSize: 13,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.lg,
    textAlign: 'center',
  },
  pagination: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'center',
    marginTop: spacing.sm,
  },
  pageButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  pageButtonDisabled: {
    opacity: 0.4,
  },
  pageButtonText: {
    color: '#374151',
    fontSize: 20,
    lineHeight: 22,
  },
  pageText: {
    color: '#6b7280',
    fontSize: 12,
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
    textAlign: 'center',
  },
  emptyButton: {
    marginTop: spacing.md,
    minWidth: 180,
  },
  table: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    overflow: 'hidden',
  },
  tableRow: {
    alignItems: 'center',
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    flexDirection: 'row',
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
  },
  tableRowAlt: {
    backgroundColor: '#f8fafc',
  },
  tableHead: {
    backgroundColor: NAVY,
    borderTopWidth: 0,
  },
  th: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '600',
    paddingHorizontal: spacing.xs,
    paddingVertical: spacing.xs,
  },
  td: {
    paddingHorizontal: spacing.xs,
    paddingVertical: spacing.xs,
  },
  colProduct: {
    width: 170,
  },
  colPrice: {
    textAlign: 'right',
    width: 100,
  },
  colVariants: {
    width: 160,
  },
  colAddons: {
    width: 170,
  },
  colStock: {
    width: 110,
  },
  colActions: {
    width: 230,
  },
  productName: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
  },
  priceText: {
    color: ACCENT,
    fontSize: 13,
    fontWeight: '700',
  },
  cellText: {
    color: '#475569',
    fontSize: 12,
  },
  cellMuted: {
    color: '#94a3b8',
    fontSize: 12,
  },
  variantsCell: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.xs,
  },
  miniBadgeNavy: {
    backgroundColor: '#eef2f7',
    borderRadius: radius.sm,
    paddingHorizontal: 6,
    paddingVertical: 1,
  },
  miniBadgeNavyText: {
    color: NAVY,
    fontSize: 11,
    fontWeight: '700',
  },
  miniBadgeOff: {
    alignSelf: 'flex-start',
    backgroundColor: '#fee2e2',
    borderRadius: radius.sm,
    marginTop: 2,
    paddingHorizontal: 6,
    paddingVertical: 1,
  },
  miniBadgeOffText: {
    color: '#b91c1c',
    fontSize: 10,
    fontWeight: '600',
  },
  actionsCell: {
    flexDirection: 'row',
    gap: spacing.xs,
  },
  actionButton: {
    backgroundColor: colors.surface,
    borderColor: ACCENT,
    borderRadius: radius.sm,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  actionText: {
    color: ACCENT,
    fontSize: 11,
    fontWeight: '700',
  },
  iconAction: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 28,
    justifyContent: 'center',
    width: 30,
  },
  iconActionDisabled: {
    opacity: 0.35,
  },
  iconActionText: {
    color: '#374151',
    fontSize: 14,
    fontWeight: '700',
  },
  deleteButton: {
    borderColor: '#fecaca',
  },
  deleteText: {
    color: '#b91c1c',
    fontSize: 11,
    fontWeight: '700',
  },
  newCategoryCard: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  newCategoryLabel: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '600',
    marginBottom: spacing.xs,
  },
  newCategoryRow: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.sm,
  },
  newCategoryButton: {
    width: 110,
  },
  categoryInput: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    color: colors.text,
    flex: 1,
    fontSize: 13,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
  },
  categoryList: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    overflow: 'hidden',
  },
  categoryRow: {
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    padding: spacing.md,
  },
  categoryRowInput: {
    marginBottom: spacing.sm,
  },
  categoryMeta: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginBottom: spacing.sm,
  },
  categoryActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
  },
  notice: {
    backgroundColor: '#fffbeb',
    borderColor: '#fde68a',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  noticeText: {
    color: '#92400e',
    fontSize: 13,
    lineHeight: 19,
  },
  unitName: {
    width: 150,
  },
  unitNameText: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
  },
  unitSymbol: {
    width: 90,
  },
  unitFactor: {
    width: 190,
  },
  unitKinds: {
    width: 160,
  },
  unitCount: {
    textAlign: 'right',
    width: 110,
  },
  unitCountText: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
  },
  sidebarBackdrop: {
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    flex: 1,
    flexDirection: 'row',
  },
  sidebarBackdropTap: {
    bottom: 0,
    left: 0,
    position: 'absolute',
    right: 0,
    top: 0,
  },
  sidebarPanel: {
    backgroundColor: colors.surface,
    borderBottomRightRadius: radius.lg,
    borderTopRightRadius: radius.lg,
    elevation: 12,
    height: '100%',
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 2, height: 0 },
    shadowOpacity: 0.15,
    shadowRadius: 12,
  },
  sidebarSafe: {
    flex: 1,
  },
  sidebarHeader: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  sidebarTitle: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
  },
  sidebarCloseButton: {
    alignItems: 'center',
    backgroundColor: '#f1f5f9',
    borderRadius: radius.pill,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  sidebarCloseText: {
    color: colors.muted,
    fontSize: 15,
    fontWeight: '700',
  },
  sidebarScroll: {
    padding: spacing.lg,
    paddingBottom: spacing.xl,
  },
  sidebarProvider: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
  },
  sidebarAvatar: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.pill,
    height: 52,
    justifyContent: 'center',
    width: 52,
  },
  sidebarAvatarText: {
    color: colors.surface,
    fontSize: 19,
    fontWeight: '800',
  },
  sidebarProviderInfo: {
    flex: 1,
    minWidth: 0,
  },
  sidebarProviderName: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
  },
  sidebarProviderZone: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  sidebarBadges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.md,
  },
  sidebarBadge: {
    backgroundColor: colors.brandSoft,
    borderRadius: radius.pill,
    paddingHorizontal: 10,
    paddingVertical: 3,
  },
  sidebarBadgeText: {
    color: colors.brandDark,
    fontSize: 10,
    fontWeight: '700',
  },
  sidebarBadgeActive: {
    backgroundColor: colors.successSoft,
  },
  sidebarBadgeTextActive: {
    color: colors.success,
  },
  sidebarBadgePaused: {
    backgroundColor: '#fffbeb',
  },
  sidebarBadgeTextPaused: {
    color: '#b45309',
  },
  sidebarNavTitle: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.6,
    marginBottom: spacing.sm,
    marginTop: spacing.xl,
    textTransform: 'uppercase',
  },
  sidebarNavItem: {
    alignItems: 'center',
    borderLeftColor: 'transparent',
    borderLeftWidth: 3,
    borderRadius: radius.sm,
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: 2,
    minHeight: 44,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
  },
  sidebarNavItemActive: {
    backgroundColor: colors.brandSoft,
    borderLeftColor: colors.brand,
  },
  sidebarNavIcon: {
    fontSize: 16,
    width: 22,
  },
  sidebarNavLabel: {
    color: colors.text,
    flex: 1,
    fontSize: 13,
    fontWeight: '600',
  },
  sidebarNavLabelActive: {
    color: colors.brandDark,
    fontWeight: '800',
  },
  sidebarActiveDot: {
    backgroundColor: colors.brand,
    borderRadius: radius.pill,
    height: 6,
    width: 6,
  },
  sidebarFooter: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    padding: spacing.lg,
  },
});
