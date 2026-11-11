import { router } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ComercioCard } from '@/components/ComercioCard';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { NewSubscriptionModal } from '@/components/NewSubscriptionModal';
import { ShopFilterModal } from '@/components/ShopFilterModal';
import type { ShopFilterMode } from '@/components/ShopFilterModal';
import { getCurrentCoords } from '@/lib/media';
import { colors, radius, spacing } from '@/theme';
import type { MarketShop } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type ShopFilter = {
  mode: ShopFilterMode;
  radius: number;
  location: string;
};

type SubscribeTarget = {
  id: number;
  name: string;
};

const DEFAULT_FILTER: ShopFilter = { mode: 'all', radius: 10, location: '' };

/** Misma distancia simulada que la web mientras los comercios no tengan lat/lng. */
function mockKm(shop: MarketShop): number {
  return ((shop.id * 13) % 50) + 1;
}

function normalize(text: string): string {
  return text
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

/** Palabras significativas de una dirección, igual que `fdFilterTokens`. */
function filterTokens(text: string): string[] {
  const stop = new Set([
    'av', 'avenida', 'calle', 'depto', 'piso', 'torre', 'argentina', 'buenos',
    'aires', 'ciudad', 'autonoma', 'caba', 'zona', 'norte', 'sur', 'este',
    'oeste', 'y', 'de', 'del', 'la', 'el', 'los', 'las', 'para', 'por', 'con',
  ]);
  const out: string[] = [];

  normalize(text)
    .split(/[^a-z0-9]+/)
    .forEach((token) => {
      if (token.length >= 4 && !stop.has(token) && !out.includes(token)) {
        out.push(token);
      }
    });

  return out;
}

/** Aplica el filtro de distancia sobre la lista ya cargada (paridad web). */
function applyDistanceFilter(list: MarketShop[], filter: ShopFilter): MarketShop[] {
  if (filter.mode !== 'distance') {
    return list;
  }

  const tokens = filterTokens(filter.location);

  return list.filter((shop) => {
    if (tokens.length > 0) {
      const zoneText = normalize(
        `${shop.zone ?? ''} ${shop.rubro ?? ''} ${shop.business_name}`,
      );

      if (tokens.some((token) => zoneText.includes(token))) {
        return true;
      }
    }

    return mockKm(shop) <= filter.radius;
  });
}

export default function ComerciosScreen() {
  const [shops, setShops] = useState<MarketShop[]>([]);
  const [tab, setTab] = useState<'favorites' | 'all'>('all');
  const [counts, setCounts] = useState({ favorites: 0, all: 0 });
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [favoriteBusyId, setFavoriteBusyId] = useState<number | null>(null);

  const [appliedFilter, setAppliedFilter] = useState<ShopFilter>(DEFAULT_FILTER);
  const [filterVisible, setFilterVisible] = useState(false);
  const [draftMode, setDraftMode] = useState<ShopFilterMode>('all');
  const [draftRadius, setDraftRadius] = useState(10);
  const [draftLocation, setDraftLocation] = useState('');
  const [locating, setLocating] = useState(false);
  const [geoStatus, setGeoStatus] = useState('');

  const [subscribeTarget, setSubscribeTarget] = useState<SubscribeTarget | null>(null);

  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const initialized = useRef(false);

  // Primera carga y recarga al cambiar de tab o de búsqueda. La primera vez
  // se piden todos para conocer los favoritos y elegir el tab inicial.
  useEffect(() => {
    let active = true;
    const favorites = tab === 'favorites';

    api
      .marketShops({
        search: search.trim() || undefined,
        favorite: favorites || undefined,
      })
      .then((response) => {
        if (!active) {
          return;
        }

        setCounts({
          favorites: response.favorites_count,
          all: response.total_count,
        });

        if (!initialized.current) {
          initialized.current = true;

          if (response.favorites_count > 0) {
            setTab('favorites');
            return;
          }
        }

        setShops(response.providers);
        setError(null);
      })
      .catch((caught: unknown) => {
        if (!active) {
          return;
        }

        setError(
          caught instanceof ApiError
            ? caught.message
            : 'No pudimos cargar los comercios.',
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
  }, [tab, search]);

  useEffect(() => {
    return () => {
      if (searchTimer.current) {
        clearTimeout(searchTimer.current);
      }
    };
  }, []);

  function handleSearchChange(text: string) {
    setSearchInput(text);

    if (searchTimer.current) {
      clearTimeout(searchTimer.current);
    }

    searchTimer.current = setTimeout(() => setSearch(text), 300);
  }

  function handleTab(next: 'favorites' | 'all') {
    if (next === tab) {
      return;
    }

    setLoading(true);
    setTab(next);
  }

  function openFilterModal() {
    setDraftMode(appliedFilter.mode);
    setDraftRadius(appliedFilter.radius);
    setDraftLocation(appliedFilter.location);
    setGeoStatus('');
    setFilterVisible(true);
  }

  function applyFilterModal() {
    setAppliedFilter({
      mode: draftMode,
      radius: draftRadius,
      location: draftLocation.trim(),
    });
    setFilterVisible(false);
  }

  async function handleLocate() {
    setLocating(true);
    setGeoStatus('');

    const found = await getCurrentCoords();
    setLocating(false);

    if (found) {
      setDraftLocation((current) =>
        current.trim()
          ? current
          : `${found.latitude.toFixed(5)}, ${found.longitude.toFixed(5)}`,
      );
      setGeoStatus('Ubicación detectada. Se usará como referencia.');
    } else {
      setGeoStatus('No pudimos obtener tu ubicación. Escribila manualmente.');
    }
  }

  async function handleRefresh() {
    setRefreshing(true);

    try {
      const response = await api.marketShops({
        search: search.trim() || undefined,
        favorite: tab === 'favorites' || undefined,
      });

      setShops(response.providers);
      setCounts({
        favorites: response.favorites_count,
        all: response.total_count,
      });
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar los comercios.',
      );
    } finally {
      setRefreshing(false);
    }
  }

  async function handleToggleFavorite(shop: MarketShop) {
    if (favoriteBusyId !== null) {
      return;
    }

    setFavoriteBusyId(shop.id);

    try {
      const response = await api.toggleMarketFavorite(shop.id);

      setShops((current) =>
        tab === 'favorites' && !response.is_favorite
          ? current.filter((item) => item.id !== shop.id)
          : current.map((item) =>
              item.id === shop.id
                ? { ...item, is_favorite: response.is_favorite }
                : item,
            ),
      );

      setCounts((current) => ({
        ...current,
        favorites: Math.max(0, current.favorites + (response.is_favorite ? 1 : -1)),
      }));
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

  const visibleShops = applyDistanceFilter(shops, appliedFilter);
  const filterActive =
    appliedFilter.mode === 'distance' || appliedFilter.location.length > 0;
  const favoritesTab = tab === 'favorites';

  const summary = favoritesTab
    ? counts.favorites > 0
      ? `${counts.favorites} ${counts.favorites === 1 ? 'comercio favorito' : 'comercios favoritos'}`
      : 'Tus comercios concurrentes'
    : shops.length > 0
      ? `${shops.length} comercios disponibles`
      : 'Comercios con envío a tu domicilio';

  if (loading && shops.length === 0) {
    return <LoadingScreen message="Cargando comercios…" />;
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Volver"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>
        <View style={styles.headerTitleWrap}>
          <Text style={styles.title}>Comercios</Text>
          <Text style={styles.subtitle}>{summary}</Text>
        </View>
        <View style={styles.headerSpacer} />
      </View>

      <View style={styles.toolbar}>
        <View style={styles.searchRow}>
          <TextInput
            value={searchInput}
            onChangeText={handleSearchChange}
            placeholder="Buscar comercio..."
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
            {filterActive ? (
              <View style={styles.filterBadge}>
                <Text style={styles.filterBadgeText}>1</Text>
              </View>
            ) : null}
          </Pressable>
        </View>

        <View style={styles.tabs}>
          <Pressable
            accessibilityRole="tab"
            accessibilityState={{ selected: favoritesTab }}
            onPress={() => handleTab('favorites')}
            style={[styles.tab, favoritesTab && styles.tabActive]}
          >
            <Text style={[styles.tabIcon, favoritesTab && styles.tabActiveText]}>★</Text>
            {counts.favorites > 0 ? (
              <View style={styles.tabBadge}>
                <Text style={styles.tabBadgeText}>{counts.favorites}</Text>
              </View>
            ) : null}
          </Pressable>

          <Pressable
            accessibilityRole="tab"
            accessibilityState={{ selected: !favoritesTab }}
            onPress={() => handleTab('all')}
            style={[styles.tab, !favoritesTab && styles.tabActive]}
          >
            <Text style={[styles.tabIcon, !favoritesTab && styles.tabActiveText]}>⊞</Text>
            {counts.all > 0 ? (
              <View style={styles.tabBadge}>
                <Text style={styles.tabBadgeText}>{counts.all}</Text>
              </View>
            ) : null}
          </Pressable>
        </View>
      </View>

      <View style={styles.listContainer}>
        <ErrorBanner message={error} />

        <FlatList
          data={visibleShops}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => void handleRefresh()}
              tintColor={ACCENT}
              colors={[ACCENT]}
            />
          }
          ListEmptyComponent={
            <View style={styles.empty}>
              {favoritesTab ? (
                <>
                  <Text style={styles.emptyText}>
                    Todavía no tenés comercios favoritos.
                  </Text>
                  <Pressable
                    accessibilityRole="button"
                    onPress={() => handleTab('all')}
                    style={styles.emptyAction}
                  >
                    <Text style={styles.emptyActionText}>⊞ Ver todos los comercios</Text>
                  </Pressable>
                </>
              ) : appliedFilter.mode === 'distance' && !search.trim() ? (
                <Text style={styles.emptyText}>
                  No hay comercios dentro del radio seleccionado.
                </Text>
              ) : (
                <Text style={styles.emptyText}>
                  {search.trim()
                    ? 'No encontramos comercios con ese nombre.'
                    : 'Todavía no hay comercios publicados.\nPronto vas a poder pedir desde acá.'}
                </Text>
              )}
            </View>
          }
          ListFooterComponent={
            loading && shops.length > 0 ? (
              <ActivityIndicator color={ACCENT} style={styles.inlineLoading} />
            ) : (
              <View style={styles.listFooter} />
            )
          }
          renderItem={({ item }) => (
            <ComercioCard
              shop={item}
              favoriteBusy={favoriteBusyId === item.id}
              onToggleFavorite={() => void handleToggleFavorite(item)}
              onSubscribe={() =>
                setSubscribeTarget({ id: item.id, name: item.business_name })
              }
              onOpenCatalog={() =>
                router.push({
                  pathname: '/comercio/[id]',
                  params: { id: String(item.id) },
                })
              }
            />
          )}
        />
      </View>

      <ShopFilterModal
        visible={filterVisible}
        mode={draftMode}
        radius={draftRadius}
        location={draftLocation}
        locating={locating}
        geoStatus={geoStatus}
        onChangeMode={setDraftMode}
        onChangeRadius={setDraftRadius}
        onChangeLocation={setDraftLocation}
        onLocate={() => void handleLocate()}
        onApply={applyFilterModal}
        onClose={() => setFilterVisible(false)}
      />

      {subscribeTarget ? (
        <NewSubscriptionModal
          providerId={subscribeTarget.id}
          providerName={subscribeTarget.name}
          onClose={() => setSubscribeTarget(null)}
          onCreated={() => {
            setSubscribeTarget(null);
            router.push('/subscriptions');
          }}
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
  headerTitleWrap: {
    alignItems: 'center',
    flex: 1,
  },
  title: {
    color: NAVY,
    fontSize: 18,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
    textAlign: 'center',
  },
  headerSpacer: {
    minWidth: 70,
  },
  toolbar: {
    gap: spacing.sm,
    paddingBottom: spacing.md,
    paddingHorizontal: spacing.lg,
  },
  searchRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  search: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    color: colors.text,
    flex: 1,
    fontSize: 13,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  filterButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    justifyContent: 'center',
    paddingHorizontal: 10,
  },
  filterButtonIcon: {
    color: NAVY,
    fontSize: 16,
  },
  filterBadge: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderColor: colors.surface,
    borderRadius: 999,
    borderWidth: 2,
    height: 16,
    justifyContent: 'center',
    position: 'absolute',
    right: -5,
    top: -5,
    width: 16,
  },
  filterBadgeText: {
    color: colors.surface,
    fontSize: 9,
    fontWeight: '800',
    lineHeight: 10,
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
  },
  tabActiveText: {
    color: ACCENT,
  },
  tabBadge: {
    backgroundColor: ACCENT,
    borderRadius: 999,
    paddingHorizontal: 6,
  },
  tabBadgeText: {
    color: colors.surface,
    fontSize: 10,
    fontWeight: '800',
  },
  listContainer: {
    flex: 1,
    paddingHorizontal: spacing.lg,
  },
  list: {
    paddingBottom: spacing.xl,
  },
  inlineLoading: {
    marginVertical: spacing.lg,
  },
  listFooter: {
    height: spacing.lg,
  },
  empty: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginTop: spacing.lg,
    paddingHorizontal: spacing.xl,
    paddingVertical: 48,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 14,
    textAlign: 'center',
  },
  emptyAction: {
    backgroundColor: ACCENT,
    borderRadius: 4,
    marginTop: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: 8,
  },
  emptyActionText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
});
