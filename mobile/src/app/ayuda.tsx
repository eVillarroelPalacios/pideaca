import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { HELP_BY_SCREEN, HELP_FAQ, HELP_FIRST_STEPS, helpFor } from '@/lib/help';
import { colors, radius, spacing } from '@/theme';

const SCREEN_KEYS = Object.keys(HELP_BY_SCREEN);

export default function AyudaScreen() {
  const params = useLocalSearchParams<{ screen?: string }>();
  const initial = typeof params.screen === 'string' ? params.screen : SCREEN_KEYS[0];
  const [selected, setSelected] = useState(
    initial && HELP_BY_SCREEN[initial] ? initial : SCREEN_KEYS[0],
  );
  const guide = helpFor(selected);

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Atrás"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>
        <Text style={styles.title}>Ayuda en línea</Text>
        <View style={styles.headerSpacer} />
      </View>

      <ScrollView contentContainerStyle={styles.body}>
        <Text style={styles.sectionLabel}>GUÍA POR PANTALLA</Text>
        <View style={styles.chips}>
          {SCREEN_KEYS.map((key) => {
            const active = key === selected;

            return (
              <Pressable
                key={key}
                accessibilityRole="button"
                accessibilityState={{ selected: active }}
                onPress={() => setSelected(key)}
                style={[styles.chip, active && styles.chipActive]}
              >
                <Text style={[styles.chipText, active && styles.chipTextActive]}>
                  {HELP_BY_SCREEN[key].title}
                </Text>
              </Pressable>
            );
          })}
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>{guide.title}</Text>
          {guide.steps.map((step, index) => (
            <Text key={`${index}`} style={styles.step}>
              {index + 1}. {step}
            </Text>
          ))}
          <Text style={styles.tip}>💡 {guide.tip}</Text>
        </View>

        <Text style={styles.sectionLabel}>PRIMEROS PASOS</Text>
        {HELP_FIRST_STEPS.map((item) => (
          <View key={item.role} style={styles.card}>
            <Text style={styles.cardTitle}>{item.role}</Text>
            {item.steps.map((step, index) => (
              <Text key={`${index}`} style={styles.step}>
                {index + 1}. {step}
              </Text>
            ))}
          </View>
        ))}

        <Text style={styles.sectionLabel}>PREGUNTAS FRECUENTES</Text>
        {HELP_FAQ.map((item) => (
          <View key={item.q} style={styles.card}>
            <Text style={styles.faqQ}>{item.q}</Text>
            <Text style={styles.faqA}>{item.a}</Text>
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.background,
    flex: 1,
  },
  header: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  backButton: {
    minWidth: 70,
  },
  backText: {
    color: colors.brandDark,
    fontSize: 16,
    fontWeight: '600',
  },
  title: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
  },
  headerSpacer: {
    minWidth: 70,
  },
  body: {
    padding: spacing.lg,
    paddingBottom: spacing.xxl,
  },
  sectionLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.5,
    marginBottom: spacing.sm,
    marginTop: spacing.md,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  chip: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
  },
  chipActive: {
    backgroundColor: colors.brand,
    borderColor: colors.brand,
  },
  chipText: {
    color: colors.text,
    fontSize: 12,
    fontWeight: '600',
  },
  chipTextActive: {
    color: colors.surface,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginTop: spacing.md,
    padding: spacing.lg,
  },
  cardTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
    marginBottom: spacing.sm,
  },
  step: {
    color: colors.text,
    fontSize: 13,
    lineHeight: 20,
    marginBottom: 4,
  },
  tip: {
    backgroundColor: '#fffbeb',
    borderColor: '#fde68a',
    borderRadius: radius.md,
    borderWidth: 1,
    color: '#92400e',
    fontSize: 12,
    marginTop: spacing.sm,
    padding: spacing.sm,
  },
  faqQ: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
    marginBottom: 2,
  },
  faqA: {
    color: colors.text,
    fontSize: 13,
    lineHeight: 20,
  },
});
