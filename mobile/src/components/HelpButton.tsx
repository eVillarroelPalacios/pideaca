import { router } from 'expo-router';
import { Pressable, StyleSheet, Text } from 'react-native';

import { colors } from '@/theme';

/**
 * Botón "?" que abre la Ayuda en línea en la guía de la pantalla indicada.
 */
export function HelpButton({ screen }: { screen: string }) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel="Ayuda de esta pantalla"
      onPress={() => router.push({ pathname: '/ayuda', params: { screen } })}
      style={styles.button}
    >
      <Text style={styles.text}>?</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  button: {
    alignItems: 'center',
    borderColor: colors.muted,
    borderRadius: 14,
    borderWidth: 1.5,
    height: 28,
    justifyContent: 'center',
    width: 28,
  },
  text: {
    color: colors.muted,
    fontSize: 15,
    fontWeight: '800',
  },
});
