import { router, useFocusEffect } from 'expo-router';
import { useCallback, useRef, useState } from 'react';
import {
  FlatList,
  Modal,
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
import {
  COUNTRY_VISITS_MOCK,
  COUNTRY_VISITS_TOTAL,
  formatInquiryDate,
  INQUIRY_FILTERS,
  type InquiryFilter,
  inquiryStatusLabel,
  inquiryStatusStyle,
  inquiryTypeLabel,
  inquiryTypeStyle,
} from '@/lib/contactInquiries';
import { colors, radius, spacing } from '@/theme';
import type { ContactInquiry, ContactInquiryCounts } from '@/types/api';

const PAGE_SIZE = 20;
const POLL_MS = 8000;

const EMPTY_COUNTS: ContactInquiryCounts = {
  all: 0,
  new: 0,
  in_progress: 0,
  resolved: 0,
};

export default function ContactInquiriesScreen() {
  const [items, setItems] = useState<ContactInquiry[]>([]);
  const [counts, setCounts] = useState<ContactInquiryCounts>(EMPTY_COUNTS);
  const [filter, setFilter] = useState<InquiryFilter>('all');
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [visitsVisible, setVisitsVisible] = useState(false);

  // Descarta respuestas viejas si el usuario cambia de filtro rápido.
  const requestId = useRef(0);
  // Firma de la primera página: solo repinta el sondeo si algo cambió.
  const listSig = useRef<string | null>(null);

  const fetchPage = useCallback(
    async (nextPage: number, replace: boolean, silent = false) => {
      const id = ++requestId.current;

      try {
        const response = await api.contactInquiries({
          status: filter,
          per_page: PAGE_SIZE,
          page: nextPage,
        });

        if (id !== requestId.current) {
          return;
        }

        if (silent && replace && nextPage === 1) {
          const sig = response.data
            .map((x) => `${x.id}:${x.status}`)
            .join('|');
          setCounts(response.counts);
          setPage(response.meta.current_page);
          setLastPage(response.meta.last_page);
          setTotal(response.meta.total);
          if (sig === listSig.current) {
            return;
          }
          listSig.current = sig;
          setItems(response.data);
          setError(null);
          return;
        }

        if (replace && nextPage === 1) {
          listSig.current = response.data
            .map((x) => `${x.id}:${x.status}`)
            .join('|');
        }
        setItems((current) => (replace ? response.data : [...current, ...response.data]));
        setCounts(response.counts);
        setPage(response.meta.current_page);
        setLastPage(response.meta.last_page);
        setTotal(response.meta.total);
        setError(null);
      } catch (caught) {
        if (id !== requestId.current) {
          return;
        }

        if (silent) {
          return;
        }
        setError(
          caught instanceof ApiError
            ? caught.message
            : 'No pudimos cargar las consultas.',
        );
      }
    },
    [filter],
  );

  // Se recarga al entrar y cada vez que cambia el filtro.
  useFocusEffect(
    useCallback(() => {
      let active = true;

      setLoading(true);
      void fetchPage(1, true).finally(() => {
        if (active) {
          setLoading(false);
        }
      });

      return () => {
        active = false;
      };
    }, [fetchPage]),
  );

  // Sondeo en vivo: solo la pantalla enfocada, solo primera página,
  // y solo repinta si la firma cambió. Pull-to-refresh sigue intacto.
  useFocusEffect(
    useCallback(() => {
      const timer = setInterval(() => {
        if (page !== 1 || loadingMore || refreshing || visitsVisible) {
          return;
        }
        void fetchPage(1, true, true);
      }, POLL_MS);

      return () => clearInterval(timer);
    }, [fetchPage, page, loadingMore, refreshing, visitsVisible]),
  );

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

  function selectFilter(next: InquiryFilter) {
    if (next !== filter) {
      setFilter(next);
    }
  }

  if (loading && items.length === 0) {
    return <LoadingScreen message="Cargando consultas…" />;
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
        <Text style={styles.title}>Consultas</Text>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Actualizar consultas"
          onPress={() => void handleRefresh()}
          style={styles.refreshButton}
        >
          <Text style={styles.refreshText}>Actualizar</Text>
        </Pressable>
      </View>

      <View style={styles.visitsRow}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Ver visitas por país"
          onPress={() => setVisitsVisible(true)}
          style={({ pressed }) => [styles.visitsButton, pressed && styles.cardPressed]}
        >
          <Text style={styles.visitsButtonText}>🌐 Ver Visitas por País</Text>
        </Pressable>
      </View>

      <View style={styles.filtersWrap}>
        {INQUIRY_FILTERS.map((option) => {
          const active = filter === option.value;

          return (
            <Pressable
              key={option.value}
              accessibilityRole="button"
              accessibilityState={{ selected: active }}
              onPress={() => selectFilter(option.value)}
              style={[styles.filterChip, active && styles.filterChipActive]}
            >
              <Text style={[styles.filterText, active && styles.filterTextActive]}>
                {option.label} {counts[option.value]}
              </Text>
            </Pressable>
          );
        })}
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
              <Text style={styles.emptyTitle}>No se encontraron consultas</Text>
              <Text style={styles.emptyText}>
                Los mensajes enviados desde el formulario de contacto del sitio van a aparecer acá.
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
          renderItem={({ item }) => {
            const statusStyle = inquiryStatusStyle(item.status);
            const typeStyle = inquiryTypeStyle(item.inquiry_type);

            return (
              <Pressable
                accessibilityRole="button"
                onPress={() =>
                  router.push({
                    pathname: '/contact-inquiry/[id]',
                    params: { id: String(item.id) },
                  })
                }
                style={({ pressed }) => [styles.card, pressed && styles.cardPressed]}
              >
                <View style={styles.cardTop}>
                  <Text style={styles.cardName} numberOfLines={1}>
                    {item.full_name}
                  </Text>
                  <View
                    style={[
                      styles.badge,
                      { backgroundColor: statusStyle.background, borderColor: statusStyle.border },
                    ]}
                  >
                    <Text style={[styles.badgeText, { color: statusStyle.foreground }]}>
                      {inquiryStatusLabel(item.status)}
                    </Text>
                  </View>
                </View>

                <View style={styles.cardMeta}>
                  <View
                    style={[
                      styles.badge,
                      { backgroundColor: typeStyle.background, borderColor: typeStyle.border },
                    ]}
                  >
                    <Text style={[styles.badgeText, { color: typeStyle.foreground }]}>
                      {inquiryTypeLabel(item.inquiry_type)}
                    </Text>
                  </View>
                  <Text style={styles.cardDate}>{formatInquiryDate(item.created_at)}</Text>
                </View>

                <Text style={styles.cardMessage} numberOfLines={2}>
                  {item.message}
                </Text>
                <Text style={styles.cardEmail} numberOfLines={1}>
                  {item.email}
                </Text>
              </Pressable>
            );
          }}
        />
      </View>

      <Modal
        visible={visitsVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setVisitsVisible(false)}
      >
        <View style={styles.visitsOverlay}>
          <View style={styles.visitsCard}>
            <View style={styles.visitsHeader}>
              <Text style={styles.visitsTitle}>Estadísticas de Visitas por País</Text>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Cerrar estadísticas"
                onPress={() => setVisitsVisible(false)}
                style={styles.visitsClose}
              >
                <Text style={styles.visitsCloseText}>✕</Text>
              </Pressable>
            </View>

            <FlatList
              data={COUNTRY_VISITS_MOCK}
              keyExtractor={(item) => item.code}
              contentContainerStyle={styles.visitsBody}
              ListHeaderComponent={
                <View>
                  <Text style={styles.visitsSubtitle}>Total de visitas registradas en el sitio</Text>
                  <Text style={styles.visitsTotal}>{COUNTRY_VISITS_TOTAL.toLocaleString('es-AR')}</Text>
                  <View style={styles.visitsSegments}>
                    {COUNTRY_VISITS_MOCK.map((item) => (
                      <View
                        key={item.code}
                        style={{ backgroundColor: item.color, flex: item.pct }}
                      />
                    ))}
                  </View>
                  <View style={styles.visitsMap}>
                    <Text style={styles.visitsMapLabel}>MAPA MUNDIAL</Text>
                    <View style={styles.visitsMapBox}>
                      <View style={[styles.visitsDot, { backgroundColor: '#ea580c', left: '18%', top: '38%' }]} />
                      <View style={[styles.visitsDot, { backgroundColor: '#0c2a4d', left: '48%', top: '28%' }]} />
                      <View style={[styles.visitsDot, { backgroundColor: '#1e40af', left: '24%', top: '60%' }]} />
                      <View style={[styles.visitsDot, { backgroundColor: '#f97316', left: '28%', top: '78%' }]} />
                      <Text style={[styles.visitsMapTag, { left: '18%', top: '52%' }]}>MX 38%</Text>
                      <Text style={[styles.visitsMapTag, { left: '48%', top: '42%' }]}>ES 26%</Text>
                    </View>
                    <View style={styles.visitsLegend}>
                      <Text style={styles.visitsLegendText}>● Mayor tráfico</Text>
                      <Text style={styles.visitsLegendText}>● Tráfico alto</Text>
                    </View>
                  </View>
                </View>
              }
              renderItem={({ item }) => (
                <View>
                  <View style={styles.visitsBarHead}>
                    <Text style={styles.visitsCountry}>
                      {item.flag} {item.name}
                    </Text>
                    <Text style={styles.visitsPct}>{item.pct}%</Text>
                  </View>
                  <View style={styles.visitsTrack}>
                    <View
                      style={[styles.visitsFill, { backgroundColor: item.color, width: `${item.pct}%` }]}
                    />
                  </View>
                  <View style={styles.visitsRowLine}>
                    <Text style={styles.visitsVisits}>{item.visits.toLocaleString('es-AR')} visitas</Text>
                  </View>
                </View>
              )}
              ListFooterComponent={
                <View style={styles.visitsFooter}>
                  <Pressable
                    accessibilityRole="button"
                    onPress={() => setVisitsVisible(false)}
                    style={styles.visitsCloseButton}
                  >
                    <Text style={styles.visitsCloseButtonText}>Cerrar</Text>
                  </Pressable>
                </View>
              }
            />
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
  refreshButton: {
    alignItems: 'flex-end',
    minWidth: 70,
  },
  refreshText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '700',
  },
  filtersWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    paddingBottom: spacing.md,
    paddingHorizontal: spacing.lg,
  },
  filterChip: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
  },
  filterChipActive: {
    backgroundColor: colors.brand,
    borderColor: colors.brand,
  },
  filterText: {
    color: colors.text,
    fontSize: 12,
    fontWeight: '600',
  },
  filterTextActive: {
    color: colors.surface,
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
  cardPressed: {
    opacity: 0.85,
  },
  cardTop: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: spacing.sm,
  },
  cardName: {
    color: colors.text,
    flex: 1,
    fontSize: 15,
    fontWeight: '800',
  },
  cardMeta: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  badge: {
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  cardDate: {
    color: colors.muted,
    fontSize: 12,
  },
  cardMessage: {
    color: colors.text,
    fontSize: 14,
    lineHeight: 20,
    marginTop: spacing.sm,
  },
  cardEmail: {
    color: colors.muted,
    fontSize: 12,
    marginTop: spacing.sm,
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
    paddingTop: spacing.sm,
    textAlign: 'center',
  },
  visitsRow: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.sm,
  },
  visitsButton: {
    alignItems: 'center',
    backgroundColor: '#ea580c',
    borderRadius: radius.md,
    flexDirection: 'row',
    justifyContent: 'center',
    paddingVertical: 10,
  },
  visitsButtonText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '700',
  },
  visitsOverlay: {
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.55)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  visitsCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    maxHeight: '90%',
    width: '100%',
  },
  visitsHeader: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    padding: spacing.lg,
  },
  visitsTitle: {
    color: colors.text,
    flex: 1,
    fontSize: 16,
    fontWeight: '800',
  },
  visitsClose: {
    padding: spacing.xs,
  },
  visitsCloseText: {
    color: colors.muted,
    fontSize: 18,
  },
  visitsBody: {
    padding: spacing.lg,
  },
  visitsSubtitle: {
    color: colors.muted,
    fontSize: 13,
  },
  visitsTotal: {
    color: colors.text,
    fontSize: 26,
    fontWeight: '800',
    marginVertical: spacing.xs,
  },
  visitsSegments: {
    borderRadius: radius.pill,
    flexDirection: 'row',
    height: 10,
    marginVertical: spacing.sm,
    overflow: 'hidden',
  },
  visitsMap: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginTop: spacing.md,
    padding: spacing.md,
  },
  visitsMapLabel: {
    color: colors.text,
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.5,
    marginBottom: spacing.sm,
  },
  visitsMapBox: {
    backgroundColor: '#eef2f7',
    borderRadius: radius.sm,
    height: 150,
    position: 'relative',
  },
  visitsDot: {
    borderRadius: 7,
    height: 14,
    position: 'absolute',
    width: 14,
  },
  visitsMapTag: {
    color: '#0c2a4d',
    fontSize: 10,
    fontWeight: '800',
    position: 'absolute',
  },
  visitsLegend: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.sm,
  },
  visitsLegendText: {
    color: colors.muted,
    fontSize: 12,
  },
  visitsBarHead: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.md,
  },
  visitsCountry: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
  },
  visitsPct: {
    color: colors.text,
    fontSize: 12,
    fontWeight: '800',
  },
  visitsTrack: {
    backgroundColor: colors.border,
    borderRadius: radius.pill,
    height: 8,
    marginTop: 6,
    overflow: 'hidden',
  },
  visitsFill: {
    height: 8,
  },
  visitsRowLine: {
    marginTop: 4,
  },
  visitsVisits: {
    color: colors.muted,
    fontSize: 12,
  },
  visitsFooter: {
    alignItems: 'flex-end',
    marginTop: spacing.lg,
  },
  visitsCloseButton: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    paddingHorizontal: spacing.xl,
    paddingVertical: 10,
  },
  visitsCloseButtonText: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
  },
});
