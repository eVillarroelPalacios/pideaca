import { Pressable, StyleSheet, Text, View } from 'react-native';

import { colors, radius, spacing } from '@/theme';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type Props = {
  title: string;
  lines: string[];
  footer?: string;
  actionLabel?: string;
  actionAccessibilityLabel?: string;
  onAction?: () => void;
};

/**
 * Fila de hábito (plato más pedido o comercio frecuente) con botón
 * opcional de reorden en 1 clic. Espeja las tablas de `repHistory()` de
 * `dashboard/sections/reportes.blade.php`.
 */
export function PlatoHabitoCard({
  title,
  lines,
  footer,
  actionLabel,
  actionAccessibilityLabel,
  onAction,
}: Props) {
  return (
    <View style={styles.card}>
      <View style={styles.info}>
        <Text style={styles.title}>{title}</Text>
        {lines.map((line) => (
          <Text key={line} style={styles.line}>
            {line}
          </Text>
        ))}
        {footer ? <Text style={styles.footer}>{footer}</Text> : null}
      </View>
      {actionLabel && onAction ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={actionAccessibilityLabel ?? actionLabel}
          onPress={onAction}
          style={styles.action}
        >
          <Text style={styles.actionText}>{actionLabel}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  info: {
    flex: 1,
    minWidth: 0,
  },
  title: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '700',
  },
  line: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  footer: {
    color: '#94a3b8',
    fontSize: 11,
    marginTop: 4,
  },
  action: {
    backgroundColor: ACCENT,
    borderRadius: radius.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  actionText: {
    color: colors.surface,
    fontSize: 11,
    fontWeight: '700',
  },
});
