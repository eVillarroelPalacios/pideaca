import { router } from 'expo-router';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
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
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { ProviderAdCard } from '@/components/ProviderAdCard';
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
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const searchTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

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
        <Text style={styles.title}>Prestadores</Text>
        <View style={styles.headerSpacer} />
      </View>

      <TextInput
        value={searchInput}
        onChangeText={handleSearchChange}
        placeholder="Buscar por nombre, servicio o zona…"
        placeholderTextColor={colors.muted}
        autoCapitalize="none"
        autoCorrect={false}
        returnKeyType="search"
        style={styles.search}
      />

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

        <Pressable
          accessibilityRole="switch"
          accessibilityState={{ checked: favoritesOnly }}
          onPress={handleFavoritesToggle}
          style={[styles.chip, favoritesOnly && styles.chipSelected]}
        >
          <Text style={[styles.chipText, favoritesOnly && styles.chipTextSelected]}>
            ★ Favoritos{favoritesCount > 0 ? ` (${favoritesCount})` : ''}
          </Text>
        </Pressable>
      </View>

      <View style={styles.listContainer}>
        <ErrorBanner message={error} />

        <FlatList
          data={providers}
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
                : `${providers.length} de ${total} prestadores${page < lastPage ? ' · bajá para ver más' : ''}`}
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
              favoriteBusy={favoriteBusyId === item.id}
            />
          )}
        />
      </View>
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
  search: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    color: colors.text,
    fontSize: 15,
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
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
});
