import { Link } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { colors, spacing } from '@/theme';

export default function NotFoundScreen() {
  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.title}>Pantalla no encontrada</Text>
        <Text style={styles.text}>La ruta que intentaste abrir no existe.</Text>
        <Link href="/" style={styles.link}>
          Volver al inicio
        </Link>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    backgroundColor: colors.background,
    flex: 1,
  },
  content: {
    alignItems: 'center',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.xl,
  },
  title: {
    color: colors.text,
    fontSize: 22,
    fontWeight: '800',
    marginBottom: spacing.sm,
    textAlign: 'center',
  },
  text: {
    color: colors.muted,
    fontSize: 15,
    marginBottom: spacing.xl,
    textAlign: 'center',
  },
  link: {
    color: colors.brand,
    fontSize: 15,
    fontWeight: '700',
  },
});
