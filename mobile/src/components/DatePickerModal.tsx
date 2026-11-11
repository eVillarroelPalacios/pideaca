import { useState } from 'react';
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';

import { colors, radius, spacing } from '@/theme';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type Props = {
  visible: boolean;
  title: string;
  /** Fecha en formato YYYY-MM-DD o cadena vacía. */
  initial: string;
  onPick: (ymd: string) => void;
  onClear: () => void;
  onClose: () => void;
};

/**
 * Calendario propio (sin dependencias nativas) para los filtros de fecha.
 * Muestra el mes en curso y permite elegir un día, limpiar o cancelar.
 */
export function DatePickerModal({
  visible,
  title,
  initial,
  onPick,
  onClear,
  onClose,
}: Props) {
  const today = new Date();
  const [cursor, setCursor] = useState(() => {
    if (/^\d{4}-\d{2}-\d{2}$/.test(initial)) {
      const [y, m] = initial.split('-').map(Number);
      return new Date(y, m - 1, 1);
    }

    return new Date(today.getFullYear(), today.getMonth(), 1);
  });

  const year = cursor.getFullYear();
  const month = cursor.getMonth();
  const firstOffset = (new Date(year, month, 1).getDay() + 6) % 7;
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const monthName = cursor.toLocaleString('es-AR', { month: 'long', year: 'numeric' });
  const parts = initial.split('-');
  const validInitial = /^\d{4}-\d{2}-\d{2}$/.test(initial);
  const selectedDay = validInitial ? Number(parts[2]) : null;
  const selectedHere =
    selectedDay !== null && Number(parts[0]) === year && Number(parts[1]) === month + 1;

  function pick(day: number) {
    const ymd = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    onPick(ymd);
  }

  const cells: (number | null)[] = [
    ...Array.from({ length: firstOffset }, () => null),
    ...Array.from({ length: daysInMonth }, (_, i) => i + 1),
  ];

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <Text style={styles.title}>{title}</Text>

          <View style={styles.nav}>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Mes anterior"
              onPress={() => setCursor(new Date(year, month - 1, 1))}
              style={styles.navButton}
            >
              <Text style={styles.navText}>❮</Text>
            </Pressable>
            <Text style={styles.month}>{monthName}</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Mes siguiente"
              onPress={() => setCursor(new Date(year, month + 1, 1))}
              style={styles.navButton}
            >
              <Text style={styles.navText}>❯</Text>
            </Pressable>
          </View>

          <View style={styles.weekRow}>
            {['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'].map((day) => (
              <Text key={day} style={styles.weekDay}>
                {day}
              </Text>
            ))}
          </View>

          <View style={styles.daysGrid}>
            {cells.map((day, index) =>
              day === null ? (
                <View key={`empty-${index}`} style={styles.dayCell} />
              ) : (
                <Pressable
                  key={day}
                  accessibilityRole="button"
                  accessibilityLabel={`Día ${day}`}
                  onPress={() => pick(day)}
                  style={[styles.dayCell, selectedHere && day === selectedDay && styles.daySelected]}
                >
                  <Text
                    style={[
                      styles.dayText,
                      selectedHere && day === selectedDay && styles.dayTextSelected,
                    ]}
                  >
                    {day}
                  </Text>
                </Pressable>
              ),
            )}
          </View>

          <View style={styles.footer}>
            <Pressable accessibilityRole="button" onPress={onClear} style={styles.ghost}>
              <Text style={styles.ghostText}>Limpiar</Text>
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
  month: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
    textTransform: 'capitalize',
  },
  weekRow: {
    flexDirection: 'row',
    marginTop: spacing.md,
  },
  weekDay: {
    color: colors.muted,
    flex: 1,
    fontSize: 11,
    fontWeight: '700',
    textAlign: 'center',
  },
  daysGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: spacing.xs,
  },
  dayCell: {
    alignItems: 'center',
    height: 36,
    justifyContent: 'center',
    width: '14.28%',
  },
  dayText: {
    color: colors.text,
    fontSize: 13,
  },
  daySelected: {
    backgroundColor: ACCENT,
    borderRadius: 999,
  },
  dayTextSelected: {
    color: colors.surface,
    fontWeight: '800',
  },
  footer: {
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'flex-end',
    marginTop: spacing.md,
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
