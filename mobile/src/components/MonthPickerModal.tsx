import { useState } from 'react';
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';

import { colors, radius, spacing } from '@/theme';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

const MONTHS = [
  'Enero',
  'Febrero',
  'Marzo',
  'Abril',
  'Mayo',
  'Junio',
  'Julio',
  'Agosto',
  'Septiembre',
  'Octubre',
  'Noviembre',
  'Diciembre',
];

type Props = {
  visible: boolean;
  title: string;
  /** Mes en formato YYYY-MM o cadena vacía. */
  initial: string;
  onPick: (ym: string) => void;
  onClear: () => void;
  onClose: () => void;
};

/**
 * Selector de mes propio (sin dependencias nativas). Espeja el
 * `<input type="month">` que usa la vista web para el resumen nutricional.
 */
export function MonthPickerModal({ visible, title, initial, onPick, onClear, onClose }: Props) {
  const today = new Date();
  const [year, setYear] = useState(() => {
    if (/^\d{4}-\d{2}$/.test(initial)) {
      return Number(initial.slice(0, 4));
    }

    return today.getFullYear();
  });

  const selectedMonth = /^\d{4}-\d{2}$/.test(initial) ? Number(initial.slice(5, 7)) : null;
  const selectedHere = selectedMonth !== null && Number(initial.slice(0, 4)) === year;

  function pick(monthIndex: number) {
    onPick(`${year}-${String(monthIndex + 1).padStart(2, '0')}`);
  }

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <Text style={styles.title}>{title}</Text>

          <View style={styles.nav}>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Año anterior"
              onPress={() => setYear((current) => current - 1)}
              style={styles.navButton}
            >
              <Text style={styles.navText}>❮</Text>
            </Pressable>
            <Text style={styles.year}>{year}</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Año siguiente"
              onPress={() => setYear((current) => current + 1)}
              style={styles.navButton}
            >
              <Text style={styles.navText}>❯</Text>
            </Pressable>
          </View>

          <View style={styles.grid}>
            {MONTHS.map((name, index) => {
              const active = selectedHere && selectedMonth === index + 1;

              return (
                <Pressable
                  key={name}
                  accessibilityRole="button"
                  accessibilityLabel={`${name} ${year}`}
                  onPress={() => pick(index)}
                  style={[styles.monthCell, active && styles.monthCellActive]}
                >
                  <Text style={[styles.monthText, active && styles.monthTextActive]}>
                    {name.slice(0, 3)}
                  </Text>
                </Pressable>
              );
            })}
          </View>

          <View style={styles.footer}>
            <Pressable accessibilityRole="button" onPress={onClear} style={styles.ghost}>
              <Text style={styles.ghostText}>Mes actual</Text>
            </Pressable>
            <Pressable accessibilityRole="button" onPress={onClose} style={styles.ghost}>
              <Text style={styles.ghostText}>Cancelar</Text>
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(7,26,48,0.55)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    padding: spacing.lg,
    width: '100%',
  },
  title: {
    color: NAVY,
    fontSize: 15,
    fontWeight: '800',
  },
  nav: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.md,
  },
  navButton: {
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  navText: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '700',
  },
  year: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: spacing.md,
    rowGap: spacing.sm,
  },
  monthCell: {
    alignItems: 'center',
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    justifyContent: 'center',
    marginHorizontal: '0.66%',
    paddingVertical: spacing.md,
    width: '31.3%',
  },
  monthCellActive: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  monthText: {
    color: colors.text,
    fontSize: 12,
    fontWeight: '600',
  },
  monthTextActive: {
    color: colors.surface,
    fontWeight: '800',
  },
  footer: {
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'flex-end',
    marginTop: spacing.lg,
  },
  ghost: {
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  ghostText: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
  },
});
