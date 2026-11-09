import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  Image,
  Linking,
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
import { LoadingScreen } from '@/components/LoadingScreen';
import { colors, radius, spacing } from '@/theme';
import type { ProviderDetail } from '@/types/api';

export default function ProviderDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const providerId = Number(id);
  const invalidId = !Number.isInteger(providerId) || providerId <= 0;

  const [provider, setProvider] = useState<ProviderDetail | null>(null);
  const [loading, setLoading] = useState(!invalidId);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(
    invalidId ? 'Prestador inválido.' : null,
  );

  useEffect(() => {
    if (invalidId) {
      return;
    }

    let active = true;

    api
      .provider(providerId)
      .then((response) => {
        if (active) {
          setProvider(response.data);
          setError(null);
        }
      })
      .catch((caught: unknown) => {
        if (active) {
          setError(
            caught instanceof ApiError
              ? caught.message
              : 'No pudimos cargar el prestador.',
          );
        }
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, [providerId, invalidId]);

  async function handleRefresh() {
    if (invalidId) {
      return;
    }

    setRefreshing(true);

    try {
      const response = await api.provider(providerId);
      setProvider(response.data);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar el prestador.',
      );
    } finally {
      setRefreshing(false);
    }
  }

  function openUrl(url: string, fallback: string) {
    Linking.openURL(url).catch(() => setError(fallback));
  }

  if (loading) {
    return <LoadingScreen message="Cargando prestador…" />;
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
        <Text style={styles.title}>Prestador</Text>
        <View style={styles.headerSpacer} />
      </View>

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={colors.brand}
            colors={[colors.brand]}
          />
        }
      >
        <ErrorBanner message={error} />

        {!provider ? (
          <View style={styles.empty}>
            <Text style={styles.emptyText}>
              No encontramos este prestador. Probablemente ya no está disponible.
            </Text>
          </View>
        ) : (
          <>
            <Text style={styles.name}>
              {provider.is_verified ? '✓ ' : ''}
              {provider.business_name}
            </Text>

            <View style={styles.badges}>
              <View
                style={[
                  styles.badge,
                  provider.is_available_now
                    ? styles.badgeOpen
                    : styles.badgeClosed,
                ]}
              >
                <Text
                  style={[
                    styles.badgeText,
                    provider.is_available_now
                      ? styles.badgeTextOpen
                      : styles.badgeTextClosed,
                  ]}
                >
                  {provider.availability_label}
                </Text>
              </View>

              {provider.service_types.map((type) => (
                <View key={type.code} style={styles.badgeNeutral}>
                  <Text style={styles.badgeNeutralText}>{type.name}</Text>
                </View>
              ))}
            </View>

            <Text style={styles.meta}>
              {provider.rating !== null
                ? `★ ${provider.rating.toFixed(1)} · ${provider.ratings_count} calificaciones`
                : 'Sin calificaciones todavía'}
              {provider.zone ? ` · ${provider.zone}` : ''}
            </Text>

            {provider.description ? (
              <Text style={styles.description}>{provider.description}</Text>
            ) : null}

            {provider.promo ? (
              <View style={styles.promo}>
                <Text style={styles.promoText}>🎁 {provider.promo}</Text>
              </View>
            ) : null}

            <View style={styles.stats}>
              <View style={styles.stat}>
                <Text style={styles.statValue}>~{provider.eta_minutes} min</Text>
                <Text style={styles.statLabel}>Llegada estimada</Text>
              </View>
              <View style={styles.stat}>
                <Text style={styles.statValue}>{provider.emergency_count}</Text>
                <Text style={styles.statLabel}>Urgencias atendidas</Text>
              </View>
              {provider.base_visit_fee !== null ? (
                <View style={styles.stat}>
                  <Text style={styles.statValue}>${provider.base_visit_fee}</Text>
                  <Text style={styles.statLabel}>Visita base</Text>
                </View>
              ) : null}
            </View>

            <Pressable
              accessibilityRole="button"
              onPress={() =>
                router.push({
                  pathname: '/request/new',
                  params: { providerId: String(provider.id) },
                })
              }
              style={styles.requestButton}
            >
              <Text style={styles.requestButtonText}>Solicitar servicio</Text>
            </Pressable>

            {provider.phone || provider.whatsapp ? (
              <View style={styles.contactRow}>
                {provider.phone ? (
                  <Pressable
                    accessibilityRole="button"
                    onPress={() => openUrl(`tel:${provider.phone}`, 'No pudimos abrir el marcador.')}
                    style={styles.contactButton}
                  >
                    <Text style={styles.contactButtonText}>📞 {provider.phone}</Text>
                  </Pressable>
                ) : null}

                {provider.whatsapp ? (
                  <Pressable
                    accessibilityRole="button"
                    onPress={() =>
                      openUrl(
                        `https://wa.me/${provider.whatsapp}`,
                        'No pudimos abrir WhatsApp.',
                      )
                    }
                    style={[styles.contactButton, styles.whatsappButton]}
                  >
                    <Text style={styles.contactButtonText}>WhatsApp</Text>
                  </Pressable>
                ) : null}
              </View>
            ) : null}

            {provider.services.length > 0 ? (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>Servicios</Text>
                {provider.services.map((service) => (
                  <View key={service.id} style={styles.serviceRow}>
                    <Text style={styles.serviceName}>{service.name}</Text>
                    {service.description ? (
                      <Text style={styles.serviceDescription}>
                        {service.description}
                      </Text>
                    ) : null}
                  </View>
                ))}
              </View>
            ) : null}

            {provider.images.length > 0 ? (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>Fotos</Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                  {provider.images
                    .filter((image) => image.url)
                    .map((image) => (
                      <Image
                        key={image.id}
                        source={{ uri: image.url as string }}
                        style={styles.photo}
                        resizeMode="cover"
                      />
                    ))}
                </ScrollView>
              </View>
            ) : null}
          </>
        )}
      </ScrollView>
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
  content: {
    padding: spacing.lg,
    paddingBottom: spacing.xxl,
  },
  empty: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 15,
    textAlign: 'center',
  },
  name: {
    color: colors.text,
    fontSize: 24,
    fontWeight: '800',
    marginBottom: spacing.sm,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginBottom: spacing.md,
  },
  badge: {
    borderRadius: radius.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
  },
  badgeOpen: {
    backgroundColor: colors.successSoft,
  },
  badgeClosed: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderWidth: 1,
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '700',
  },
  badgeTextOpen: {
    color: colors.success,
  },
  badgeTextClosed: {
    color: colors.muted,
  },
  badgeNeutral: {
    backgroundColor: colors.brandSoft,
    borderRadius: radius.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
  },
  badgeNeutralText: {
    color: colors.brandDark,
    fontSize: 12,
    fontWeight: '700',
  },
  meta: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
    marginBottom: spacing.md,
  },
  description: {
    color: colors.muted,
    fontSize: 15,
    lineHeight: 22,
    marginBottom: spacing.md,
  },
  promo: {
    backgroundColor: colors.brandSoft,
    borderColor: '#fed7aa',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.md,
  },
  promoText: {
    color: '#9a3412',
    fontSize: 14,
    fontWeight: '600',
  },
  stats: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    flexDirection: 'row',
    marginBottom: spacing.lg,
    padding: spacing.lg,
  },
  stat: {
    alignItems: 'center',
    flex: 1,
  },
  statValue: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
  },
  statLabel: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
    textAlign: 'center',
  },
  contactRow: {
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.lg,
  },
  contactButton: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.md,
    flex: 1,
    paddingVertical: spacing.md,
  },
  whatsappButton: {
    backgroundColor: colors.success,
  },
  contactButtonText: {
    color: colors.surface,
    fontSize: 15,
    fontWeight: '700',
  },
  requestButton: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.md,
    marginBottom: spacing.lg,
    paddingVertical: spacing.md,
  },
  requestButtonText: {
    color: colors.surface,
    fontSize: 16,
    fontWeight: '800',
  },
  section: {
    marginBottom: spacing.lg,
  },
  sectionTitle: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '800',
    marginBottom: spacing.md,
  },
  serviceRow: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.sm,
    padding: spacing.md,
  },
  serviceName: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
  },
  serviceDescription: {
    color: colors.muted,
    fontSize: 14,
    marginTop: 2,
  },
  photo: {
    backgroundColor: colors.border,
    borderRadius: radius.md,
    height: 160,
    marginRight: spacing.md,
    width: 220,
  },
});
