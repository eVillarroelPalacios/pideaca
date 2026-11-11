import { Pressable, StyleSheet, Text, View } from 'react-native';

import { colors, radius, spacing } from '@/theme';

export type FinancialTab = {
  key: 'productos' | 'insumos';
  label: string;
  icon: string;
};

type Props = {
  tabs: FinancialTab[];
  active: FinancialTab['key'];
  onChange: (key: FinancialTab['key']) => void;
};

/**
 * Selector horizontal de secciones de Salud financiera.
 *
 * Espeja el sidebar "SECCIONES" de la web adaptado a mobile: dos pestañas
 * ("Productos" e "Insumos") con la activa pintada con el color corporativo.
 */
export function FinancialTabSelector({ tabs, active, onChange }: Props) {
  return (
    <View style={styles.row}>
      {tabs.map((tab) => {
        const isActive = tab.key === active;

        return (
          <Pressable
            key={tab.key}
            accessibilityRole="tab"
            accessibilityState={{ selected: isActive }}
            accessibilityLabel={tab.label}
            onPress={() => onChange(tab.key)}
            style={({ pressed }) => [
              styles.tab,
              isActive ? styles.tabActive : styles.tabIdle,
              pressed && styles.pressed,
            ]}
          >
            <Text style={styles.icon}>{tab.icon}</Text>
            <Text style={[styles.label, isActive ? styles.labelActive : styles.labelIdle]}>
              {tab.label}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  tab: {
    alignItems: 'center',
    borderRadius: radius.md,
    borderWidth: 1,
    flex: 1,
    flexDirection: 'row',
    gap: 6,
    justifyContent: 'center',
    minHeight: 44,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  tabActive: {
    backgroundColor: colors.brand,
    borderColor: colors.brand,
  },
  tabIdle: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
  },
  pressed: {
    opacity: 0.85,
  },
  icon: {
    fontSize: 15,
  },
  label: {
    fontSize: 13,
    fontWeight: '700',
  },
  labelActive: {
    color: colors.surface,
  },
  labelIdle: {
    color: colors.muted,
  },
});
