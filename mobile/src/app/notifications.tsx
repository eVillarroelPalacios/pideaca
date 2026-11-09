import { router } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { colors, radius, spacing } from '@/theme';
import type { AppNotification } from '@/types/api';

const PAGE_SIZE = 15;

function timeAgo(iso: string | null): string {
  if (!iso) {
    return '';
  }

  const date = new Date(iso);

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const minutes = Math.floor((Date.now() - date.getTime()) / 60000);

  if (minutes < 1) {
    return 'ahora';
  }

  if (minutes < 60) {
    return `hace ${minutes} min`;
  }

  const hours = Math.floor(minutes / 60);

  if (hours < 24) {
    return `hace ${hours} h`;
  }

  return date.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit' });
}

export default function NotificationsScreen() {
  const [items, setItems] = useState<AppNotification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchPage = useCallback(async (nextPage: number, replace: boolean) => {
    try {
      const response = await api.notifications({
        per_page: PAGE_SIZE,
        page: nextPage,
      });

      setItems((current) => (replace ? response.data : [...current, ...response.data]));
      setPage(response.meta.current_page);
      setLastPage(response.meta.last_page);
      setTotal(response.meta.total);
      setUnreadCount(response.meta.unread_count);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar tus notificaciones.',
      );
    }
  }, []);

  useEffect(() => {
    let active = true;

    api
      .notifications({ per_page: PAGE_SIZE, page: 1 })
      .then((response) => {
        if (!active) {
          return;
        }

        setItems(response.data);
        setPage(response.meta.current_page);
        setLastPage(response.meta.last_page);
        setTotal(response.meta.total);
        setUnreadCount(response.meta.unread_count);
      })
      .catch((caught: unknown) => {
        if (!active) {
          return;
        }

        setError(
          caught instanceof ApiError
            ? caught.message
            : 'No pudimos cargar tus notificaciones.',
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
  }, []);

  async function handleRefresh() {
    setRefreshing(true);
    await fetchPage(1, true);
    setRefreshing(false);
  }

  async function handleLoadMore() {
    if (loadingMore || page >= lastPage) {
      return;
    }

    setLoadingMore(true);
    await fetchPage(page + 1, false);
    setLoadingMore(false);
  }

  async function handleOpen(item: AppNotification) {
    if (!item.read_at) {
      try {
        await api.markNotificationAsRead(item.id);
        setItems((current) =>
          current.map((entry) =>
            entry.id === item.id ? { ...entry, read_at: new Date().toISOString() } : entry,
          ),
        );
        setUnreadCount((count) => Math.max(0, count - 1));
      } catch {
        // Si falla el marcado, igual abrimos el detalle.
      }
    }

    const orderId = item.data?.order_id;

    if (typeof orderId === 'number') {
      router.push({
        pathname: '/request/[id]',
        params: { id: String(orderId) },
      });
    }
  }

  async function handleReadAll() {
    if (unreadCount === 0) {
      return;
    }

    try {
      await api.markAllNotificationsAsRead();
      await fetchPage(1, true);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos marcar las notificaciones.',
      );
    }
  }

  if (loading) {
    return <LoadingScreen message="Cargando notificaciones…" />;
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
        <Text style={styles.title}>
          Avisos{unreadCount > 0 ? ` (${unreadCount})` : ''}
        </Text>
        <Pressable
          accessibilityRole="button"
          disabled={unreadCount === 0}
          onPress={() => void handleReadAll()}
          style={styles.readAll}
        >
          <Text style={[styles.readAllText, unreadCount === 0 && styles.readAllDisabled]}>
            Leer todas
          </Text>
        </Pressable>
      </View>

      <View style={styles.listContainer}>
        <ErrorBanner message={error} />

        <FlatList
          data={items}
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
              <Text style={styles.emptyTitle}>Sin avisos todavía</Text>
              <Text style={styles.emptyText}>
                Cuando un prestador tome tu solicitud, te proponga un precio o te califiquen, lo vas a ver acá.
              </Text>
            </View>
          }
          ListFooterComponent={
            <Text style={styles.count}>
              {total === 0
                ? ' '
                : `${items.length} de ${total}${page < lastPage ? ' · bajá para ver más' : ''}`}
            </Text>
          }
          renderItem={({ item }) => (
            <Pressable
              accessibilityRole="button"
              onPress={() => void handleOpen(item)}
              style={({ pressed }) => [
                styles.card,
                !item.read_at && styles.cardUnread,
                pressed && styles.cardPressed,
              ]}
            >
              <View style={styles.cardHeader}>
                {!item.read_at ? <View style={styles.dot} /> : null}
                <Text style={styles.cardTitle} numberOfLines={2}>
                  {item.title}
                </Text>
              </View>
              {item.body ? (
                <Text style={styles.cardBody} numberOfLines={3}>
                  {item.body}
                </Text>
              ) : null}
              <Text style={styles.cardTime}>{timeAgo(item.created_at)}</Text>
            </Pressable>
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
  readAll: {
    minWidth: 70,
    alignItems: 'flex-end',
  },
  readAllText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '700',
  },
  readAllDisabled: {
    color: colors.muted,
  },
  listContainer: {
    flex: 1,
    paddingHorizontal: spacing.lg,
  },
  list: {
    paddingBottom: spacing.xxl,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  cardUnread: {
    borderColor: colors.brand,
    borderLeftWidth: 4,
  },
  cardPressed: {
    opacity: 0.7,
  },
  cardHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.xs,
  },
  dot: {
    backgroundColor: colors.brand,
    borderRadius: 5,
    height: 10,
    width: 10,
  },
  cardTitle: {
    color: colors.text,
    flex: 1,
    fontSize: 16,
    fontWeight: '800',
  },
  cardBody: {
    color: colors.muted,
    fontSize: 14,
    lineHeight: 20,
  },
  cardTime: {
    color: colors.muted,
    fontSize: 12,
    marginTop: spacing.xs,
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
