import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { colors, radius, spacing } from '@/theme';

export type SectionDrawerItem = {
  key: string;
  label: string;
  icon: string;
};

type Props = {
  visible: boolean;
  onClose: () => void;
  title: string;
  subtitle?: string;
  items: SectionDrawerItem[];
  active: string;
  onSelect: (key: string) => void;
};

/**
 * Drawer lateral de secciones con la misma estetica que el sidebar de
 * Mi Perfil (AccountSidebar): tarjeta superior + lista con la seccion
 * activa resaltada. Generico: sirve para reportes y futuras pantallas.
 */
export function SectionDrawer({
  visible,
  onClose,
  title,
  subtitle,
  items,
  active,
  onSelect,
}: Props) {
  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      onRequestClose={onClose}
    >
      <View style={styles.overlay}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Cerrar secciones"
          onPress={onClose}
          style={styles.backdrop}
        />
        <SafeAreaView style={styles.panel} edges={['top', 'bottom', 'left']}>
          <View style={styles.header}>
            <View style={styles.badge}>
              <Text style={styles.badgeText}>
                {title
                  .split(' ')
                  .filter(Boolean)
                  .slice(0, 2)
                  .map((part) => part[0]?.toUpperCase() ?? '')
                  .join('')}
              </Text>
            </View>
            <View style={styles.headerInfo}>
              <Text style={styles.name} numberOfLines={1}>
                {title}
              </Text>
              {subtitle ? (
                <Text style={styles.sub} numberOfLines={2}>
                  {subtitle}
                </Text>
              ) : null}
            </View>
          </View>

          <Text style={styles.sectionLabel}>Secciones</Text>

          <ScrollView style={styles.list} contentContainerStyle={styles.listContent}>
            {items.map((item) => {
              const isActive = item.key === active;

              return (
                <Pressable
                  key={item.key}
                  accessibilityRole="button"
                  accessibilityState={{ selected: isActive }}
                  onPress={() => {
                    onSelect(item.key);
                    onClose();
                  }}
                  style={[styles.item, isActive && styles.itemActive]}
                >
                  <Text style={styles.itemIcon}>{item.icon}</Text>
                  <Text style={[styles.itemText, isActive && styles.itemTextActive]} numberOfLines={2}>
                    {item.label}
                  </Text>
                  {isActive ? <Text style={styles.itemChevron}>›</Text> : null}
                </Pressable>
              );
            })}
          </ScrollView>

          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Cerrar secciones"
            onPress={onClose}
            style={styles.closeButton}
          >
            <Text style={styles.closeText}>Cerrar</Text>
          </Pressable>
        </SafeAreaView>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: { flex: 1, flexDirection: 'row', backgroundColor: 'rgba(15,23,42,0.5)' },
  backdrop: { flex: 1 },
  panel: { backgroundColor: colors.surface, width: '82%', maxWidth: 340 },
  header: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: spacing.md,
    padding: spacing.lg,
  },
  badge: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: 999,
    height: 44,
    justifyContent: 'center',
    width: 44,
  },
  badgeText: { color: colors.surface, fontSize: 15, fontWeight: '800' },
  headerInfo: { flex: 1, minWidth: 0 },
  name: { color: colors.text, fontSize: 15, fontWeight: '700' },
  sub: { color: colors.muted, fontSize: 12, marginTop: 2 },
  sectionLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.5,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
    textTransform: 'uppercase',
  },
  list: { flex: 1 },
  listContent: { padding: spacing.md },
  item: {
    alignItems: 'center',
    borderLeftColor: 'transparent',
    borderLeftWidth: 3,
    borderRadius: radius.sm,
    flexDirection: 'row',
    gap: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },
  itemActive: { backgroundColor: colors.brandSoft, borderLeftColor: colors.brand },
  itemIcon: { fontSize: 18 },
  itemText: { color: colors.muted, flex: 1, fontSize: 13, fontWeight: '600' },
  itemTextActive: { color: colors.text, fontWeight: '800' },
  itemChevron: { color: colors.brand, fontSize: 18, fontWeight: '800' },
  closeButton: {
    alignItems: 'center',
    borderTopColor: colors.border,
    borderTopWidth: 1,
    padding: spacing.lg,
  },
  closeText: { color: colors.brandDark, fontSize: 14, fontWeight: '700' },
});
