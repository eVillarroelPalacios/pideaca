import { Pressable, StyleSheet, Text, View } from 'react-native';

import { colors, radius, spacing } from '@/theme';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';
const GREEN = '#22c55e';
const AMBER = '#f59e0b';

type Props = {
  name: string;
  initials: string;
  driverCode: string;
  online: boolean;
  onToggleConnection: () => void;
  onTogglePause: () => void;
};

/**
 * Cabecera navy de la Terminal Central de Repartos: marca, switch de
 * conexión, botón de pausa y perfil del repartidor con su ID.
 * Espeja `header.drv-header` de `repartidor.blade.php`.
 */
export function DriverHeader({
  name,
  initials,
  driverCode,
  online,
  onToggleConnection,
  onTogglePause,
}: Props) {
  return (
    <View style={styles.header}>
      <View style={styles.topRow}>
        <View style={styles.brand}>
          <View style={styles.logo}>
            <Text style={styles.logoIcon}>🚚</Text>
          </View>
          <View style={styles.brandText}>
            <View style={styles.titleRow}>
              <Text style={styles.title}>PideAca</Text>
              <View style={styles.badge}>
                <Text style={styles.badgeText}>DRIVER PRO</Text>
              </View>
            </View>
            <Text style={styles.subtitle}>Terminal Central de Repartos</Text>
          </View>
        </View>

        <View style={styles.driver}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{initials || 'DR'}</Text>
          </View>
          <View style={styles.driverMeta}>
            <Text numberOfLines={1} style={styles.driverName}>
              {name}
            </Text>
            <View style={styles.driverIdBadge}>
              <Text style={styles.driverIdText}>ID: {driverCode}</Text>
            </View>
          </View>
        </View>
      </View>

      <View style={styles.actionsRow}>
        <Pressable
          accessibilityRole="switch"
          accessibilityState={{ checked: online }}
          accessibilityLabel={online ? 'Desconectarse' : 'Conectarse'}
          onPress={onToggleConnection}
          style={({ pressed }) => [styles.pill, pressed && styles.pillPressed]}
        >
          <View
            style={[
              styles.dot,
              { backgroundColor: online ? GREEN : AMBER },
              online ? styles.dotOnline : styles.dotPaused,
            ]}
          />
          <Text style={styles.pillText}>{online ? 'EN LÍNEA' : 'DESCONECTADO'}</Text>
        </Pressable>

        <Pressable
          accessibilityRole="button"
          onPress={onTogglePause}
          style={({ pressed }) => [styles.pill, pressed && styles.pillPressed]}
        >
          <Text style={styles.pillText}>{online ? 'Pausar' : 'Reanudar'}</Text>
        </Pressable>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  header: {
    backgroundColor: NAVY,
    borderBottomColor: ACCENT,
    borderBottomWidth: 3,
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.lg,
  },
  topRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    justifyContent: 'space-between',
  },
  brand: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
    minWidth: 0,
  },
  logo: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    height: 44,
    justifyContent: 'center',
    width: 44,
  },
  logoIcon: {
    fontSize: 22,
  },
  brandText: {
    flexShrink: 1,
    minWidth: 0,
  },
  titleRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  title: {
    color: colors.surface,
    fontSize: 20,
    fontWeight: '800',
    letterSpacing: -0.3,
  },
  badge: {
    backgroundColor: ACCENT,
    borderRadius: radius.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  badgeText: {
    color: colors.surface,
    fontSize: 9,
    fontWeight: '800',
    letterSpacing: 0.8,
  },
  subtitle: {
    color: 'rgba(255,255,255,0.68)',
    fontSize: 12.5,
    fontWeight: '500',
    marginTop: 3,
  },
  driver: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderColor: 'rgba(255,255,255,0.18)',
    borderRadius: radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    paddingBottom: 5,
    paddingLeft: 5,
    paddingRight: spacing.lg,
    paddingTop: 5,
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderColor: 'rgba(255,255,255,0.38)',
    borderRadius: radius.pill,
    borderWidth: 2,
    height: 38,
    justifyContent: 'center',
    width: 38,
  },
  avatarText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '800',
  },
  driverMeta: {
    flexShrink: 1,
    minWidth: 0,
  },
  driverName: {
    color: colors.surface,
    fontSize: 12.5,
    fontWeight: '800',
    maxWidth: 150,
  },
  driverIdBadge: {
    alignSelf: 'flex-start',
    backgroundColor: 'rgba(255,255,255,0.10)',
    borderColor: 'rgba(255,255,255,0.16)',
    borderRadius: radius.pill,
    borderWidth: 1,
    marginTop: 2,
    paddingHorizontal: 8,
    paddingVertical: 1,
  },
  driverIdText: {
    color: 'rgba(255,255,255,0.72)',
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 0.7,
  },
  actionsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  pill: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.10)',
    borderColor: 'rgba(255,255,255,0.22)',
    borderRadius: radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    minHeight: 38,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  pillPressed: {
    opacity: 0.75,
  },
  pillText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
  dot: {
    borderRadius: radius.pill,
    height: 8,
    width: 8,
  },
  dotOnline: {
    shadowColor: GREEN,
    shadowOpacity: 0.45,
    shadowRadius: 3,
  },
  dotPaused: {
    shadowColor: AMBER,
    shadowOpacity: 0.45,
    shadowRadius: 3,
  },
});
