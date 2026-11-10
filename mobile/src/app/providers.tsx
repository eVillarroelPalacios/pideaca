import { router } from 'expo-router';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
  FlatList,
  Modal,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { ProviderAdCard } from '@/components/ProviderAdCard';
import { getCurrentCoords } from '@/lib/media';
import { colors, radius, spacing } from '@/theme';
import type { ProviderCard, ProvidersParams } from '@/types/api';

type TypeFilter = 'ALL' | 'EMERGENCY' | 'SCHEDULED' | 'QUOTE';

const TYPE_FILTERS: { code: TypeFilter; label: string }[] = [
  { code: 'ALL', label: 'Todos' },
  { code: 'EMERGENCY', label: 'Urgencias' },
  { code: 'SCHEDULED', label: 'Programadas' },
  { code: 'QUOTE', label: 'Presupuesto' },
];

const PAGE_SIZE = 15;

/** Azul oscuro corporativo de la cabecera del modal (igual que la web). */
const NAVY = '#0c2a4d';
/** Naranja corporativo de acentos (igual que la web). */
const ACCENT = '#D24C19';

/** Radio máximo: 51 representa "50+ km". */
const MAX_RADIUS = 51;

function radiusLabel(km: number): string {
  return km >= MAX_RADIUS ? '50+ km' : `${km} km`;
}

/** Distancia en km entre dos coordenadas (Haversine). */
function kmBetween(
  aLat: number,
  aLng: number,
  bLat: number,
  bLng: number,
): number {
  const toRad = (deg: number) => (deg * Math.PI) / 180;
  const earth = 6371;
  const dLat = toRad(bLat - aLat);
  const dLng = toRad(bLng - aLng);
  const h =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRad(aLat)) *
      Math.cos(toRad(bLat)) *
      Math.sin(dLng / 2) *
      Math.sin(dLng / 2);

  return 2 * earth * Math.asin(Math.sqrt(h));
}

export default function ProvidersScreen() {
  const [providers, setProviders] = useState<ProviderCard[]>([]);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState<TypeFilter>('ALL');
  const [favoritesOnly, setFavoritesOnly] = useState(false);
  const [favoritesCount, setFavoritesCount] = useState(0);
  const [favoriteBusyId, setFavoriteBusyId] = useState<number | null>(null);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [allTotal, setAllTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Filtro de ubicación/distancia (igual que el modal web de comercios).
  const [filterVisible, setFilterVisible] = useState(false);
  const [appliedMode, setAppliedMode] = useState<'distance' | 'all'>('all');
  const [appliedRadius, setAppliedRadius] = useState(10);
  const [draftMode, setDraftMode] = useState<'distance' | 'all'>('all');
  const [draftRadius, setDraftRadius] = useState(10);
  const [refLocation, setRefLocation] = useState('');
  const [userCoords, setUserCoords] = useState<{
    latitude: number;
    longitude: number;
  } | null>(null);
  const [locating, setLocating] = useState(false);
  const [geoStatus, setGeoStatus] = useState('');
  const addressPrefilled = useRef(false);

  function buildParams(nextPage: number): ProvidersParams {
    const params: ProvidersParams = {
      per_page: PAGE_SIZE,
      page: nextPage,
    };

    if (search.trim()) {
      params.search = search.trim();
    }

    if (typeFilter !== 'ALL') {
      params.type = typeFilter;
    }

    if (favoritesOnly) {
      params.favorite = true;
    }

    return params;
  }

  const load = useCallback(
    async (nextPage: number, replace: boolean) => {
      try {
        const response = await api.providers(buildParams(nextPage));

        setProviders((current) =>
          replace ? response.data : [...current, ...response.data],
        );
        setPage(response.meta.current_page);
        setLastPage(response.meta.last_page);
        setTotal(response.meta.total);
        setFavoritesCount(response.meta.favorites_count ?? 0);
        if (!favoritesOnly) {
          setAllTotal(response.meta.total);
        }
        setError(null);
      } catch (caught) {
        setError(
          caught instanceof ApiError
            ? caught.message
            : 'No pudimos cargar los prestadores.',
        );
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [search, typeFilter, favoritesOnly],
  );

  // Primera carga y recarga cuando cambian los filtros. La cadena de
  // promesas va en línea (no vía `load`) para cumplir la regla
  // react-hooks/set-state-in-effect; los setState viven en callbacks.
  useEffect(() => {
    let active = true;

    api
      .providers(buildParams(1))
      .then((response) => {
        if (!active) {
          return;
        }

        setProviders(response.data);
        setPage(response.meta.current_page);
        setLastPage(response.meta.last_page);
        setTotal(response.meta.total);
        setFavoritesCount(response.meta.favorites_count ?? 0);
        if (!favoritesOnly) {
          setAllTotal(response.meta.total);
        }
        setError(null);
      })
      .catch((caught: unknown) => {
        if (!active) {
          return;
        }

        setError(
          caught instanceof ApiError
            ? caught.message
            : 'No pudimos cargar los prestadores.',
        );
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [search, typeFilter, favoritesOnly]);

  // La búsqueda espera 500 ms desde que dejás de escribir.
  function handleSearchChange(text: string) {
    setSearchInput(text);
    setLoading(true);

    if (searchTimer.current) {
      clearTimeout(searchTimer.current);
    }

    searchTimer.current = setTimeout(() => setSearch(text), 500);
  }

  function handleTypeFilter(code: TypeFilter) {
    setLoading(true);
    setTypeFilter(code);
  }

  function handleFavoritesToggle() {
    setLoading(true);
    setFavoritesOnly((current) => !current);
  }

  function handleTab(tab: 'favorites' | 'all') {
    const next = tab === 'favorites';

    if (next !== favoritesOnly) {
      handleFavoritesToggle();
    }
  }

  // Abre el modal y precarga la dirección principal una sola vez.
  function openFilterModal() {
    setDraftMode(appliedMode);
    setDraftRadius(appliedRadius);
    setGeoStatus('');
    setFilterVisible(true);

    if (addressPrefilled.current) {
      return;
    }

    addressPrefilled.current = true;

    api
      .profileAddress()
      .then((response) => {
        if (!response.address) {
          return;
        }

        const primary = response.address;
        const formatted = [
          primary.street,
          primary.number,
          primary.floor_apartment,
        ]
          .filter((part) => part && String(part).trim().length > 0)
          .join(' ');

        if (formatted) {
          setRefLocation((current) => (current.trim() ? current : formatted));
        }
      })
      .catch(() => {
        // Sin dirección guardada se puede escribir a mano igual.
      });
  }

  function closeFilterModal() {
    setFilterVisible(false);
  }

  function applyFilterModal() {
    setAppliedMode(draftMode);
    setAppliedRadius(draftRadius);
    setFilterVisible(false);
    setLoading(true);
    void load(1, true);
  }

  async function handleFilterLocate() {
    setLocating(true);
    setGeoStatus('');
    const found = await getCurrentCoords();
    setLocating(false);

    if (found) {
      setUserCoords(found);
      setGeoStatus('✅ Ubicación lista para filtrar por distancia.');
    } else {
      setGeoStatus('No pudimos obtener tu ubicación. Revisá los permisos.');
    }
  }

  // Subtítulo dinámico con el conteo (igual que la web).
  const summary = favoritesOnly
    ? `${favoritesCount} ${favoritesCount === 1 ? 'prestador favorito' : 'prestadores favoritos'}`
    : `${total} ${total === 1 ? 'prestador' : 'prestadores'}`;

  // Filtro en tiempo real por nombre o rubro sobre lo ya cargado.
  const term = search.trim().toLowerCase();
  const searchedProviders = term
    ? providers.filter((item) => {
        const rubro = (item.subgroups ?? [])
          .map((sub) => sub.description)
          .join(' ');

        return `${item.business_name} ${rubro} ${item.zone ?? ''}`
          .toLowerCase()
          .includes(term);
      })
    : providers;

  // Filtro por distancia: solo puede aplicarse si el prestador trae
  // coordenadas; si no, se conserva la lista (el backend aún no expone
  // lat/lng en la tarjeta).
  const visibleProviders =
    appliedMode === 'distance' && userCoords
      ? searchedProviders.filter((item) => {
          const lat = (item as unknown as { latitude?: number | null })
            .latitude;
          const lng = (item as unknown as { longitude?: number | null })
            .longitude;

          if (typeof lat !== 'number' || typeof lng !== 'number') {
            return true;
          }

          return (
            kmBetween(userCoords.latitude, userCoords.longitude, lat, lng) <=
            appliedRadius
          );
        })
      : searchedProviders;

  const distanceActive = appliedMode === 'distance';

  // Limpia el temporizador si salís de la pantalla escribiendo.
  useEffect(() => {
    return () => {
      if (searchTimer.current) {
        clearTimeout(searchTimer.current);
      }
    };
  }, []);

  async function handleRefresh() {
    setRefreshing(true);
    await load(1, true);
    setRefreshing(false);
  }

  async function handleLoadMore() {
    if (loadingMore || page >= lastPage) {
      return;
    }

    setLoadingMore(true);
    await load(page + 1, false);
    setLoadingMore(false);
  }

  async function handleToggleFavorite(provider: ProviderCard) {
    if (favoriteBusyId !== null) {
      return;
    }

    setFavoriteBusyId(provider.id);

    try {
      const response = await api.toggleProviderFavorite(provider.id);

      setProviders((current) =>
        favoritesOnly && !response.is_favorite
          ? current.filter((item) => item.id !== provider.id)
          : current.map((item) =>
              item.id === provider.id
                ? { ...item, is_favorite: response.is_favorite }
                : item,
            ),
      );

      setFavoritesCount((current) =>
        // El chip cuenta los favoritos del filtro actual, no el total global
        // que devuelve el endpoint.
        Math.max(0, current + (response.is_favorite ? 1 : -1)),
      );

      if (favoritesOnly && !response.is_favorite) {
        setTotal((current) => Math.max(0, current - 1));
      }
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos actualizar tus favoritos.',
      );
    } finally {
      setFavoriteBusyId(null);
    }
  }

  if (loading) {
    return <LoadingScreen message="Buscando prestadores…" />;
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>
        <View style={styles.headerTitleWrap}>
          <Text style={styles.title}>Solicitudes</Text>
          <Text style={styles.subtitle}>{summary}</Text>
        </View>
        <View style={styles.headerSpacer} />
      </View>

      <View style={styles.toolbar}>
        <View style={styles.tabs}>
          <Pressable
            accessibilityRole="tab"
            accessibilityState={{ selected: favoritesOnly }}
            onPress={() => handleTab('favorites')}
            style={[styles.tab, favoritesOnly && styles.tabActive]}
          >
            <Text style={[styles.tabIcon, favoritesOnly && styles.tabActiveText]}>
              ⭐
            </Text>
            <View style={styles.tabBadge}>
              <Text style={styles.tabBadgeText}>{favoritesCount}</Text>
            </View>
          </Pressable>

          <Pressable
            accessibilityRole="tab"
            accessibilityState={{ selected: !favoritesOnly }}
            onPress={() => handleTab('all')}
            style={[styles.tab, !favoritesOnly && styles.tabActive]}
          >
            <Text style={[styles.tabIcon, !favoritesOnly && styles.tabActiveText]}>
              ⊞
            </Text>
            <View style={styles.tabBadge}>
              <Text style={styles.tabBadgeText}>
                {favoritesOnly ? allTotal : total}
              </Text>
            </View>
          </Pressable>
        </View>

        <View style={styles.searchRow}>
          <TextInput
            value={searchInput}
            onChangeText={handleSearchChange}
            placeholder="Buscar prestador..."
            placeholderTextColor={colors.muted}
            autoCapitalize="none"
            autoCorrect={false}
            returnKeyType="search"
            style={styles.search}
          />
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Filtrar por ubicación y distancia"
            onPress={openFilterModal}
            style={styles.filterButton}
          >
            <Text style={styles.filterButtonIcon}>⧩</Text>
            {distanceActive ? <View style={styles.filterDot} /> : null}
          </Pressable>
        </View>
      </View>

      <View style={styles.filters}>
        {TYPE_FILTERS.map((filter) => {
          const selected = typeFilter === filter.code;

          return (
            <Pressable
              key={filter.code}
              accessibilityRole="radio"
              accessibilityState={{ selected }}
              onPress={() => handleTypeFilter(filter.code)}
              style={[styles.chip, selected && styles.chipSelected]}
            >
              <Text style={[styles.chipText, selected && styles.chipTextSelected]}>
                {filter.label}
              </Text>
            </Pressable>
          );
        })}
      </View>

      <View style={styles.listContainer}>
        <ErrorBanner message={error} />

        <FlatList
          data={visibleProviders}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={handleRefresh}
              tintColor={colors.brand}
              colors={[colors.brand]}
            />
          }
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.4}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyTitle}>Sin resultados</Text>
              <Text style={styles.emptyText}>
                Probá con otra búsqueda o sacá los filtros.
              </Text>
            </View>
          }
          ListFooterComponent={
            <Text style={styles.count}>
              {total === 0
                ? ' '
                : `${visibleProviders.length} de ${total} prestadores${page < lastPage ? ' · bajá para ver más' : ''}`}
            </Text>
          }
          renderItem={({ item }) => (
            <ProviderAdCard
              provider={item}
              onPress={() =>
                router.push({
                  pathname: '/provider/[id]',
                  params: { id: String(item.id) },
                })
              }
              onToggleFavorite={() => void handleToggleFavorite(item)}
              onSelectService={(code) =>
                router.push({
                  pathname: '/request/new',
                  params: { providerId: String(item.id), service: code },
                })
              }
              favoriteBusy={favoriteBusyId === item.id}
            />
          )}
        />
      </View>

      <Modal
        visible={filterVisible}
        transparent
        animationType="fade"
        onRequestClose={closeFilterModal}
        statusBarTranslucent
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <View style={styles.modalHeaderText}>
                <Text style={styles.modalTitle}>Filtrar comercios</Text>
                <Text style={styles.modalSubtitle}>Ubicación y distancia</Text>
              </View>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Cerrar filtros"
                onPress={closeFilterModal}
                style={styles.modalClose}
              >
                <Text style={styles.modalCloseText}>✕</Text>
              </Pressable>
            </View>

            <View style={styles.modalBody}>
              <Pressable
                accessibilityRole="radio"
                accessibilityState={{ selected: draftMode === 'distance' }}
                onPress={() => setDraftMode('distance')}
                style={[
                  styles.radioCard,
                  draftMode === 'distance' && styles.radioCardSelected,
                ]}
              >
                <View
                  style={[
                    styles.radioDot,
                    draftMode === 'distance' && styles.radioDotSelected,
                  ]}
                />
                <View style={styles.radioTextWrap}>
                  <Text style={styles.radioTitle}>Por distancia</Text>
                  <Text style={styles.radioHint}>
                    Mostrar comercios dentro del radio elegido.
                  </Text>
                </View>
              </Pressable>

              <View style={styles.sliderWrap}>
                <View style={styles.sliderLabelRow}>
                  <Text style={styles.sliderLabel}>Radio</Text>
                  <Text style={styles.sliderValue}>
                    {radiusLabel(draftRadius)}
                  </Text>
                </View>
                <View style={styles.sliderRow}>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Achicar radio"
                    disabled={draftMode !== 'distance' || draftRadius <= 1}
                    onPress={() =>
                      setDraftRadius((current) => Math.max(1, current - 1))
                    }
                    style={[
                      styles.stepper,
                      (draftMode !== 'distance' || draftRadius <= 1) &&
                        styles.stepperDisabled,
                    ]}
                  >
                    <Text style={styles.stepperText}>−</Text>
                  </Pressable>
                  <View style={styles.track}>
                    <View
                      style={[
                        styles.trackFill,
                        {
                          width: `${Math.min(
                            100,
                            ((draftRadius - 1) / (MAX_RADIUS - 1)) * 100,
                          )}%`,
                        },
                      ]}
                    />
                    <View
                      style={[
                        styles.thumb,
                        {
                          left: `${Math.min(
                            100,
                            ((draftRadius - 1) / (MAX_RADIUS - 1)) * 100,
                          )}%`,
                        },
                      ]}
                    />
                  </View>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Agrandar radio"
                    disabled={
                      draftMode !== 'distance' || draftRadius >= MAX_RADIUS
                    }
                    onPress={() =>
                      setDraftRadius((current) =>
                        Math.min(MAX_RADIUS, current + 1),
                      )
                    }
                    style={[
                      styles.stepper,
                      (draftMode !== 'distance' ||
                        draftRadius >= MAX_RADIUS) &&
                        styles.stepperDisabled,
                    ]}
                  >
                    <Text style={styles.stepperText}>+</Text>
                  </Pressable>
                </View>
                <View style={styles.sliderEnds}>
                  <Text style={styles.sliderEnd}>1 km</Text>
                  <Text style={styles.sliderEnd}>50+ km</Text>
                </View>
              </View>

              <Pressable
                accessibilityRole="radio"
                accessibilityState={{ selected: draftMode === 'all' }}
                onPress={() => setDraftMode('all')}
                style={[
                  styles.radioCard,
                  draftMode === 'all' && styles.radioCardSelected,
                ]}
              >
                <View
                  style={[
                    styles.radioDot,
                    draftMode === 'all' && styles.radioDotSelected,
                  ]}
                />
                <View style={styles.radioTextWrap}>
                  <Text style={styles.radioTitle}>
                    Buscar en todas partes
                  </Text>
                  <Text style={styles.radioHint}>
                    Sin límite de distancia.
                  </Text>
                </View>
              </Pressable>

              <Text style={styles.locationLabel}>
                UBICACIÓN DE REFERENCIA (OPCIONAL)
              </Text>
              <View style={styles.locationRow}>
                <TextInput
                  value={refLocation}
                  onChangeText={setRefLocation}
                  placeholder="Ej. Av. Santa Fe 1234, Depto 2B"
                  placeholderTextColor={colors.muted}
                  autoCapitalize="none"
                  autoCorrect={false}
                  style={styles.locationInput}
                />
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Usar mi ubicación actual"
                  disabled={locating}
                  onPress={() => void handleFilterLocate()}
                  style={styles.geoButton}
                >
                  <Text style={styles.geoButtonText}>◎</Text>
                </Pressable>
              </View>
              {geoStatus ? (
                <Text style={styles.geoStatus}>{geoStatus}</Text>
              ) : null}
            </View>

            <View style={styles.modalFooter}>
              <Pressable
                accessibilityRole="button"
                onPress={closeFilterModal}
                style={styles.cancelButton}
              >
                <Text style={styles.cancelButtonText}>Cancelar</Text>
              </Pressable>
              <Pressable
                accessibilityRole="button"
                onPress={applyFilterModal}
                style={styles.applyButton}
              >
                <Text style={styles.applyButtonText}>Aplicar</Text>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.background,
    flex: 1,
  },
  header: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  backButton: {
    minWidth: 70,
  },
  backText: {
    color: colors.brandDark,
    fontSize: 16,
    fontWeight: '600',
  },
  title: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
  },
  headerSpacer: {
    minWidth: 70,
  },
  headerTitleWrap: {
    alignItems: 'center',
    flex: 1,
  },
  subtitle: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
  },
  toolbar: {
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md,
  },
  tabs: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  tab: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: 4,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 6,
    justifyContent: 'center',
    paddingHorizontal: spacing.md,
    paddingVertical: 7,
  },
  tabActive: {
    backgroundColor: '#fff1eb',
    borderColor: ACCENT,
  },
  tabIcon: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  tabActiveText: {
    color: ACCENT,
  },
  tabBadge: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: radius.pill,
    justifyContent: 'center',
    minWidth: 20,
    paddingHorizontal: 6,
  },
  tabBadgeText: {
    color: colors.surface,
    fontSize: 10,
    fontWeight: '700',
  },
  searchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
  },
  search: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: 4,
    borderWidth: 1,
    color: colors.text,
    flex: 1,
    fontSize: 12,
    minWidth: 0,
    paddingHorizontal: spacing.md,
    paddingVertical: 7,
  },
  filterButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    height: 36,
    justifyContent: 'center',
    width: 36,
  },
  filterButtonIcon: {
    color: NAVY,
    fontSize: 16,
    fontWeight: '700',
  },
  filterDot: {
    backgroundColor: ACCENT,
    borderColor: colors.surface,
    borderRadius: 8,
    borderWidth: 2,
    height: 12,
    position: 'absolute',
    right: -4,
    top: -4,
    width: 12,
  },
  filters: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md,
  },
  chip: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  chipSelected: {
    backgroundColor: colors.brandSoft,
    borderColor: colors.brand,
  },
  chipText: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  chipTextSelected: {
    color: colors.brandDark,
  },
  listContainer: {
    flex: 1,
    paddingHorizontal: spacing.lg,
  },
  list: {
    paddingBottom: spacing.xxl,
  },
  empty: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
  },
  emptyTitle: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '700',
    marginBottom: spacing.xs,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 14,
    textAlign: 'center',
  },
  count: {
    color: colors.muted,
    fontSize: 12,
    paddingVertical: spacing.md,
    textAlign: 'center',
  },
  modalBackdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(7,26,48,0.55)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  modalCard: {
    backgroundColor: colors.surface,
    borderRadius: 12,
    maxWidth: 430,
    overflow: 'hidden',
    width: '100%',
  },
  modalHeader: {
    alignItems: 'center',
    backgroundColor: NAVY,
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'space-between',
    paddingHorizontal: 18,
    paddingVertical: 14,
  },
  modalHeaderText: {
    flex: 1,
  },
  modalTitle: {
    color: colors.surface,
    fontSize: 15,
    fontWeight: '700',
  },
  modalSubtitle: {
    color: '#b9c7db',
    fontSize: 11,
    marginTop: 3,
  },
  modalClose: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.12)',
    borderRadius: 8,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  modalCloseText: {
    color: colors.surface,
    fontSize: 18,
    fontWeight: '700',
    lineHeight: 20,
  },
  modalBody: {
    padding: 18,
  },
  radioCard: {
    alignItems: 'flex-start',
    borderColor: '#e5e7eb',
    borderRadius: 9,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  radioCardSelected: {
    borderColor: ACCENT,
  },
  radioDot: {
    borderColor: '#d1d5db',
    borderRadius: 999,
    borderWidth: 2,
    height: 18,
    marginTop: 2,
    width: 18,
  },
  radioDotSelected: {
    borderColor: ACCENT,
  },
  radioTextWrap: {
    flex: 1,
  },
  radioTitle: {
    color: NAVY,
    fontSize: 13,
    fontWeight: '700',
  },
  radioHint: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  sliderWrap: {
    marginBottom: 14,
    paddingLeft: 34,
    paddingRight: 4,
  },
  sliderLabelRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  sliderLabel: {
    color: colors.text,
    fontSize: 12,
  },
  sliderValue: {
    color: ACCENT,
    fontSize: 12,
    fontWeight: '800',
  },
  sliderRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
  },
  stepper: {
    alignItems: 'center',
    borderColor: '#d1d5db',
    borderRadius: 6,
    borderWidth: 1,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  stepperDisabled: {
    opacity: 0.4,
  },
  stepperText: {
    color: NAVY,
    fontSize: 18,
    fontWeight: '700',
    lineHeight: 20,
  },
  track: {
    backgroundColor: '#e5e7eb',
    borderRadius: 999,
    flex: 1,
    height: 6,
    justifyContent: 'center',
  },
  trackFill: {
    backgroundColor: ACCENT,
    borderRadius: 999,
    height: 6,
  },
  thumb: {
    backgroundColor: ACCENT,
    borderColor: colors.surface,
    borderRadius: 999,
    borderWidth: 2,
    height: 18,
    marginLeft: -9,
    position: 'absolute',
    width: 18,
  },
  sliderEnds: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 2,
  },
  sliderEnd: {
    color: '#9ca3af',
    fontSize: 10,
  },
  locationLabel: {
    color: colors.text,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.4,
    marginBottom: 6,
  },
  locationRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  locationInput: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 7,
    borderWidth: 1,
    color: colors.text,
    flex: 1,
    fontSize: 12,
    minWidth: 0,
    paddingHorizontal: spacing.md,
    paddingVertical: 9,
  },
  geoButton: {
    alignItems: 'center',
    backgroundColor: '#fff1eb',
    borderColor: '#f5c6ad',
    borderRadius: 7,
    borderWidth: 1,
    justifyContent: 'center',
    paddingHorizontal: 11,
  },
  geoButtonText: {
    color: ACCENT,
    fontSize: 18,
    fontWeight: '800',
  },
  geoStatus: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 6,
  },
  modalFooter: {
    backgroundColor: '#f8fafc',
    borderTopColor: '#e5e7eb',
    borderTopWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'flex-end',
    paddingHorizontal: 18,
    paddingVertical: 14,
  },
  cancelButton: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 7,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  cancelButtonText: {
    color: colors.text,
    fontSize: 12,
    fontWeight: '600',
  },
  applyButton: {
    backgroundColor: ACCENT,
    borderRadius: 7,
    paddingHorizontal: 18,
    paddingVertical: 9,
  },
  applyButtonText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
});
