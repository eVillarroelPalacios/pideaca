import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { colors, spacing } from '@/theme';

export type SubscriptionTabKey = 'planes' | 'cobros' | 'historial';

export type SubscriptionTab = {
  key: SubscriptionTabKey;
  label: string;
  icon: string;
};

type Props = {
  tabs: SubscriptionTab[];
  active: SubscriptionTabKey;
  onChange: (key: SubscriptionTabKey) => void;
};

/**
 * Pestanas horizontales de Suscripciones.
 *
 * Espeja la barra de la web: borde inferior naranja en la pestana activa y
 * scroll horizontal para que las tres etiquetas entren en pantallas chicas.
 */
export function SubscriptionTabs({ tabs, active, onChange }: Props) {
  return (
    <View style={styles.bar}>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.content}
      >
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
              <Text
                numberOfLines={1}
                style={[styles.label, isActive ? styles.labelActive : styles.labelIdle]}
              >
                {tab.label}
              </Text>
            </Pressable>
          );
        })}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  bar: {
    borderBottomColor: '#e2e8f0',
    borderBottomWidth: 2,
    marginBottom: spacing.md,
  },
  content: {
    flexDirection: 'row',
  },
  tab: {
    alignItems: 'center',
    borderBottomWidth: 2,
    flexDirection: 'row',
    gap: 6,
    marginBottom: -2,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm + 2,
  },
  tabActive: {
    backgroundColor: 'rgba(249, 115, 22, 0.06)',
    borderBottomColor: colors.brand,
  },
  tabIdle: {
    borderBottomColor: 'transparent',
  },
  pressed: {
    opacity: 0.8,
  },
  icon: {
    fontSize: 14,
  },
  label: {
    fontSize: 12,
    fontWeight: '700',
  },
  labelActive: {
    color: colors.brandDark,
  },
  labelIdle: {
    color: colors.muted,
  },
});
