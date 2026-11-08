import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

import { colors, spacing } from '@/theme';

export function LoadingScreen({ message = 'Cargando…' }: { message?: string }) {
  return (
    <View style={styles.container}>
      <ActivityIndicator size="large" color={colors.brand} />
      <Text style={styles.text}>{message}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    backgroundColor: colors.background,
    flex: 1,
    justifyContent: 'center',
    padding: spacing.xl,
  },
  text: {
    color: colors.muted,
    fontSize: 15,
    marginTop: spacing.md,
  },
});
