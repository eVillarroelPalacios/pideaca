import { ActivityIndicator, Image, Pressable, StyleSheet, Text, View } from 'react-native';

import { resolveAssetUrl } from '@/lib/assets';
import { colors, radius, spacing } from '@/theme';
import type { MarketShop } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type Props = {
  shop: MarketShop;
  favoriteBusy?: boolean;
  onToggleFavorite: () => void;
  onSubscribe: () => void;
  onOpenCatalog: () => void;
};

/**
 * Tarjeta de comercio con el mismo esquema que `fdRenderProviderCard` de la
 * web: banner de publicidad, avatar con inicial, rubro/zona, rating, cinta
 * promocional y los tres accesos (favorito, suscripción, catálogo).
 */
export function ComercioCard({
  shop,
  favoriteBusy = false,
  onToggleFavorite,
  onSubscribe,
  onOpenCatalog,
}: Props) {
  const banner = resolveAssetUrl(shop.publicidad_image);
  const initial = shop.business_name.trim().charAt(0).toUpperCase() || '?';
  const meta = shop.rubro || shop.zone || 'Comercio';
  const hasPlans = shop.active_subscription_plans_count > 0;

  return (
    <View style={styles.card}>
      {banner ? (
        <Image source={{ uri: banner }} style={styles.banner} resizeMode="cover" />
      ) : null}

      <Pressable
        accessibilityRole="button"
        accessibilityLabel={`Ver catálogo de ${shop.business_name}`}
        onPress={onOpenCatalog}
        style={({ pressed }) => [styles.pressable, pressed && styles.pressed]}
      >
        <View style={styles.headRow}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{initial}</Text>
          </View>
          <View style={styles.headInfo}>
            <Text style={styles.name} numberOfLines={1}>
              {shop.business_name}
            </Text>
            <Text style={styles.meta} numberOfLines={1}>
              {meta}
              {shop.zone && shop.rubro ? ` · ${shop.zone}` : ''}
            </Text>
          </View>
          {shop.rating ? (
            <Text style={styles.rating}>★ {shop.rating.toFixed(1)}</Text>
          ) : null}
        </View>

        {shop.promo ? (
          <View style={styles.promo}>
            <Text style={styles.promoText} numberOfLines={2}>
              {shop.promo}
            </Text>
          </View>
        ) : null}
      </Pressable>

      <View style={styles.footer}>
        <Text style={styles.counts}>
          {shop.products_count} productos · {shop.categories_count} categorías
        </Text>

        <View style={styles.actions}>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={
              shop.is_favorite ? 'Quitar de favoritos' : 'Agregar a favoritos'
            }
            accessibilityState={{ selected: shop.is_favorite, busy: favoriteBusy }}
            disabled={favoriteBusy}
            onPress={onToggleFavorite}
            style={[styles.iconButton, shop.is_favorite && styles.iconButtonActive]}
          >
            {favoriteBusy ? (
              <ActivityIndicator size="small" color={ACCENT} />
            ) : (
              <Text
                style={[styles.iconStar, shop.is_favorite && styles.iconStarActive]}
              >
                {shop.is_favorite ? '★' : '☆'}
              </Text>
            )}
          </Pressable>

          {hasPlans ? (
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Suscribirse a un plan de este comercio"
              onPress={onSubscribe}
              style={[styles.iconButton, styles.iconButtonAccent]}
            >
              <Text style={styles.iconAccent}>＋</Text>
            </Pressable>
          ) : null}

          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Ver catálogo"
            onPress={onOpenCatalog}
            style={[styles.iconButton, styles.iconButtonSolid]}
          >
            <Text style={styles.iconSolid}>▤</Text>
          </Pressable>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    overflow: 'hidden',
  },
  banner: {
    backgroundColor: colors.background,
    height: 70,
    width: '100%',
  },
  pressable: {
    flexDirection: 'column',
    gap: 10,
    padding: spacing.lg,
  },
  pressed: {
    opacity: 0.85,
  },
  headRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: '#fff1eb',
    borderRadius: radius.pill,
    height: 44,
    justifyContent: 'center',
    width: 44,
  },
  avatarText: {
    color: ACCENT,
    fontSize: 18,
    fontWeight: '800',
  },
  headInfo: {
    flex: 1,
    minWidth: 0,
  },
  name: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '800',
  },
  meta: {
    color: '#94a3b8',
    fontSize: 12,
    marginTop: 2,
  },
  rating: {
    color: '#b45309',
    fontSize: 12,
    fontWeight: '700',
  },
  promo: {
    backgroundColor: '#ecfdf5',
    paddingHorizontal: spacing.sm,
    paddingVertical: 6,
  },
  promoText: {
    color: '#047857',
    fontSize: 12,
  },
  footer: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    justifyContent: 'space-between',
    paddingBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
  },
  counts: {
    color: colors.muted,
    fontSize: 12,
  },
  actions: {
    flexDirection: 'row',
    gap: 6,
  },
  iconButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 6,
    borderWidth: 1,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  iconButtonActive: {
    borderColor: ACCENT,
  },
  iconButtonAccent: {
    borderColor: ACCENT,
  },
  iconButtonSolid: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  iconStar: {
    color: '#94a3b8',
    fontSize: 19,
    lineHeight: 22,
  },
  iconStarActive: {
    color: ACCENT,
  },
  iconAccent: {
    color: ACCENT,
    fontSize: 16,
    fontWeight: '800',
    lineHeight: 18,
  },
  iconSolid: {
    color: colors.surface,
    fontSize: 15,
    lineHeight: 18,
  },
});
