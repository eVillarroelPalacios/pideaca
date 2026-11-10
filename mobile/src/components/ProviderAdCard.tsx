import { ActivityIndicator, Image, Pressable, StyleSheet, Text, View } from 'react-native';

import { resolveAssetUrl } from '@/lib/assets';
import { colors, radius, spacing } from '@/theme';
import type { ProviderCard } from '@/types/api';

type Props = {
  provider: ProviderCard;
  selected?: boolean;
  favoriteBusy?: boolean;
  /** Tocar la tarjeta: navegar al detalle o elegir al prestador. */
  onPress?: () => void;
  /** Estrella de favorito. Si no se pasa, no se muestra. */
  onToggleFavorite?: () => void;
  /** Botones de servicio. Si no se pasa, se muestran como información. */
  onSelectService?: (code: string, name: string) => void;
};

const SERVICE_ICONS: Record<string, string> = {
  EMERGENCY: '🚨',
  SCHEDULED: '📅',
  QUOTE: '🧾',
};

/**
 * Tarjeta de prestador con el mismo esquema que la web de Solicitar Servicio:
 * banner de publicidad, avatar, rating, promo, servicios que ofrece y
 * estrella de favorito. Reutilizada en Nueva solicitud y en Prestadores.
 */
export function ProviderAdCard({
  provider,
  selected = false,
  favoriteBusy = false,
  onPress,
  onToggleFavorite,
  onSelectService,
}: Props) {
  const banner = resolveAssetUrl(provider.publicidad_image);
  const initial = provider.business_name.trim().charAt(0).toUpperCase() || 'P';
  const rating =
    provider.rating !== null
      ? `★ ${provider.rating.toFixed(1)} (${provider.ratings_count})`
      : '★ Sin calificar';

  return (
    <View style={[styles.card, selected && styles.cardSelected]}>
      <Pressable
        accessibilityRole="button"
        accessibilityState={{ selected }}
        disabled={!onPress}
        onPress={onPress}
        style={({ pressed }) => [styles.pressable, pressed && onPress ? styles.pressed : null]}
      >
        <View style={styles.banner}>
          {banner ? (
            <Image
              source={{ uri: banner }}
              style={styles.bannerImage}
              resizeMode="cover"
              accessibilityIgnoresInvertColors
            />
          ) : (
            <View style={styles.bannerFallback}>
              <Text style={styles.bannerInitial}>{initial}</Text>
            </View>
          )}

          <View
            style={[
              styles.availability,
              provider.is_available_now ? styles.availabilityOpen : styles.availabilityClosed,
            ]}
          >
            <Text
              style={[
                styles.availabilityText,
                provider.is_available_now
                  ? styles.availabilityTextOpen
                  : styles.availabilityTextClosed,
              ]}
            >
              {provider.is_available_now ? 'Disponible' : 'Cerrado'}
            </Text>
          </View>
        </View>

        <View style={styles.body}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{initial}</Text>
          </View>

          <View style={styles.info}>
            <Text style={styles.name} numberOfLines={1}>
              {provider.is_verified ? '✓ ' : ''}
              {provider.business_name}
            </Text>
            <Text style={styles.rating} numberOfLines={1}>
              {rating}
              {provider.zone ? ` · ${provider.zone}` : ''}
            </Text>
          </View>
        </View>

        {provider.promo ? (
          <View style={styles.promo}>
            <Text style={styles.promoText} numberOfLines={2}>
              🎁 {provider.promo}
            </Text>
          </View>
        ) : null}

        {provider.subgroups.length > 0 ? (
          <Text style={styles.tags} numberOfLines={1}>
            {provider.subgroups.map((subgroup) => subgroup.description).join(' · ')}
          </Text>
        ) : null}
      </Pressable>

      {onToggleFavorite ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={
            provider.is_favorite ? 'Quitar de favoritos' : 'Agregar a favoritos'
          }
          accessibilityState={{ selected: provider.is_favorite, busy: favoriteBusy }}
          disabled={favoriteBusy}
          onPress={onToggleFavorite}
          style={({ pressed }) => [styles.favorite, pressed && styles.favoritePressed]}
        >
          {favoriteBusy ? (
            <ActivityIndicator size="small" color={colors.brand} />
          ) : (
            <Text
              style={[styles.favoriteStar, provider.is_favorite && styles.favoriteStarActive]}
            >
              {provider.is_favorite ? '★' : '☆'}
            </Text>
          )}
        </Pressable>
      ) : null}

      {provider.service_types.length > 0 ? (
        <View style={styles.services}>
          {provider.service_types.map((type) => {
            const content = (
              <>
                <Text style={styles.serviceIcon}>{SERVICE_ICONS[type.code] ?? '🛠️'}</Text>
                <Text
                  style={[
                    styles.serviceText,
                    onSelectService ? styles.serviceTextAction : styles.serviceTextInfo,
                  ]}
                  numberOfLines={1}
                >
                  {type.name}
                </Text>
              </>
            );

            return onSelectService ? (
              <Pressable
                key={type.code}
                accessibilityRole="button"
                accessibilityLabel={`Pedir ${type.name}`}
                onPress={() => onSelectService(type.code, type.name)}
                style={({ pressed }) => [
                  styles.service,
                  styles.serviceAction,
                  pressed && styles.servicePressed,
                ]}
              >
                {content}
              </Pressable>
            ) : (
              <View key={type.code} style={[styles.service, styles.serviceInfo]}>
                {content}
              </View>
            );
          })}
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.md,
    overflow: 'hidden',
  },
  cardSelected: {
    backgroundColor: colors.brandSoft,
    borderColor: colors.brand,
    borderWidth: 2,
  },
  pressable: {
    overflow: 'hidden',
  },
  pressed: {
    opacity: 0.85,
  },
  banner: {
    backgroundColor: colors.brandSoft,
    height: 92,
    width: '100%',
  },
  bannerImage: {
    height: '100%',
    width: '100%',
  },
  bannerFallback: {
    alignItems: 'center',
    backgroundColor: colors.brandSoft,
    flex: 1,
    justifyContent: 'center',
  },
  bannerInitial: {
    color: colors.brand,
    fontSize: 38,
    fontWeight: '800',
  },
  availability: {
    borderRadius: radius.pill,
    left: spacing.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
    position: 'absolute',
    top: spacing.sm,
  },
  availabilityOpen: {
    backgroundColor: colors.successSoft,
  },
  availabilityClosed: {
    backgroundColor: colors.background,
  },
  availabilityText: {
    fontSize: 11,
    fontWeight: '700',
  },
  availabilityTextOpen: {
    color: colors.success,
  },
  availabilityTextClosed: {
    color: colors.muted,
  },
  body: {
    alignItems: 'center',
    flexDirection: 'row',
    paddingHorizontal: spacing.md,
    paddingTop: spacing.md,
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: colors.brandSoft,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 44,
    justifyContent: 'center',
    marginRight: spacing.md,
    width: 44,
  },
  avatarText: {
    color: colors.brandDark,
    fontSize: 18,
    fontWeight: '800',
  },
  info: {
    flex: 1,
  },
  name: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
  },
  rating: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
  },
  promo: {
    backgroundColor: colors.brandSoft,
    borderRadius: radius.sm,
    marginHorizontal: spacing.md,
    marginTop: spacing.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
  },
  promoText: {
    color: colors.brandDark,
    fontSize: 12,
    fontWeight: '700',
  },
  tags: {
    color: colors.brandDark,
    fontSize: 12,
    paddingHorizontal: spacing.md,
    paddingTop: spacing.sm,
  },
  favorite: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 36,
    justifyContent: 'center',
    position: 'absolute',
    right: spacing.sm,
    top: spacing.sm,
    width: 36,
  },
  favoritePressed: {
    opacity: 0.7,
  },
  favoriteStar: {
    color: colors.muted,
    fontSize: 20,
    lineHeight: 24,
  },
  favoriteStarActive: {
    color: '#f59e0b',
  },
  services: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    padding: spacing.md,
  },
  service: {
    alignItems: 'center',
    borderRadius: radius.sm,
    flexDirection: 'row',
    gap: 4,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  serviceAction: {
    backgroundColor: colors.brand,
  },
  serviceInfo: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderWidth: 1,
  },
  servicePressed: {
    opacity: 0.8,
  },
  serviceIcon: {
    fontSize: 12,
  },
  serviceText: {
    fontSize: 12,
    fontWeight: '700',
  },
  serviceTextAction: {
    color: colors.surface,
  },
  serviceTextInfo: {
    color: colors.text,
  },
});
