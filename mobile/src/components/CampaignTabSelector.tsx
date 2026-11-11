import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { colors, radius, spacing } from '@/theme';

const ACCENT = '#D24C19';

export type CampaignState = 'active' | 'inactive' | 'unconfigured';

export type CampaignTab = {
  type: string;
  label: string;
  icon: string;
  state: CampaignState;
};

type Props = {
  tabs: CampaignTab[];
  active: string;
  onChange: (type: string) => void;
};

/** Color del punto de estado, igual que la leyenda de la web. */
const STATE_COLORS: Record<CampaignState, string> = {
  active: '#047857',
  inactive: '#6b7280',
  unconfigured: '#d97706',
};

/**
 * Selector horizontal de campañas de retención.
 *
 * Espeja el sidebar "CAMPAÑAS" de la web: cada pestaña muestra su icono, el
 * nombre y un punto con el estado actual. Abajo queda la leyenda de estados.
 */
export function CampaignTabSelector({ tabs, active, onChange }: Props) {
  return (
    <View style={styles.card}>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.scrollContent}
      >
        {tabs.map((tab) => {
          const isActive = tab.type === active;

          return (
            <Pressable
              key={tab.type}
              accessibilityRole="tab"
              accessibilityState={{ selected: isActive }}
              accessibilityLabel={`${tab.label}, ${
                tab.state === 'active'
                  ? 'activa'
                  : tab.state === 'inactive'
                    ? 'inactiva'
                    : 'sin configurar'
              }`}
              onPress={() => onChange(tab.type)}
              style={[styles.tab, isActive && styles.tabActive]}
            >
              <Text style={styles.icon}>{tab.icon}</Text>
              <Text style={[styles.label, isActive && styles.labelActive]}>
                {tab.label}
              </Text>
              <View
                style={[styles.dot, { backgroundColor: STATE_COLORS[tab.state] }]}
              />
            </Pressable>
          );
        })}
      </ScrollView>

      <View style={styles.legend}>
        <View style={styles.legendItem}>
          <View style={[styles.legendDot, { backgroundColor: STATE_COLORS.active }]} />
          <Text style={styles.legendText}>Activa</Text>
        </View>
        <View style={styles.legendItem}>
          <View style={[styles.legendDot, { backgroundColor: STATE_COLORS.inactive }]} />
          <Text style={styles.legendText}>Inactiva</Text>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    paddingVertical: spacing.sm,
  },
  scrollContent: {
    gap: spacing.sm,
    paddingHorizontal: spacing.sm,
  },
  tab: {
    alignItems: 'center',
    borderColor: 'transparent',
    borderRadius: radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 6,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  tabActive: {
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
  },
  icon: {
    fontSize: 14,
  },
  label: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
  },
  labelActive: {
    color: ACCENT,
    fontWeight: '800',
  },
  dot: {
    borderRadius: radius.pill,
    height: 8,
    width: 8,
  },
  legend: {
    borderTopColor: '#e5e7eb',
    borderTopWidth: 1,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    marginTop: spacing.sm,
    paddingHorizontal: spacing.md,
    paddingTop: spacing.sm,
  },
  legendItem: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 5,
  },
  legendDot: {
    borderRadius: radius.pill,
    height: 6,
    width: 6,
  },
  legendText: {
    color: '#94a3b8',
    fontSize: 10,
    fontWeight: '600',
  },
});
