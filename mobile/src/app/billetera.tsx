import { Redirect, router } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { useAuth } from '@/auth/AuthContext';
import { AuthScreen } from '@/components/AuthScreen';
import { BilleteraManager } from '@/components/BilleteraManager';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { colors, radius, spacing } from '@/theme';

/**
 * Billetera de cobro (Fase 1). El contenido vive en BilleteraManager para
 * compartirlo con la sección Billetera de Mi Perfil, igual que la ventana
 * web (mismo panel en el sidebar y misma pantalla).
 */
export default function BilleteraScreen() {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return <LoadingScreen message="Cargando tu billetera…" />;
  }

  if (!user) {
    return <Redirect href="/login" />;
  }

  return (
    <AuthScreen
      title="Billetera de cobro"
      subtitle="Cargá las cuentas donde querés cobrar. La primera queda como predeterminada para tus liquidaciones."
      footer={
        <View style={styles.footerRow}>
          <Text style={styles.footerText}>¿Terminaste?</Text>
          <Text
            style={styles.link}
            onPress={() => router.replace('/home')}
            accessibilityRole="link"
          >
            Volver al inicio
          </Text>
        </View>
      }
    >
      <BilleteraManager />

      <View style={styles.card}>
        <Text style={styles.cardTitle}>📊 Mi Dinero</Text>
        <Text style={styles.cardSub}>
          Tus liquidaciones, cobros y estado de cuenta, igual que el panel web.
        </Text>
        <PrimaryButton
          title="Ver mis liquidaciones y cobros"
          variant="ghost"
          onPress={() => router.push('/mi-dinero' as never)}
        />
      </View>
    </AuthScreen>
  );
}

const styles = StyleSheet.create({
  footerRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.xs,
  },
  footerText: {
    color: colors.muted,
    fontSize: 14,
  },
  link: {
    color: colors.brand,
    fontSize: 14,
    fontWeight: '700',
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    marginTop: spacing.lg,
    padding: spacing.lg,
  },
  cardTitle: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
    marginBottom: spacing.xs,
  },
  cardSub: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 18,
    marginBottom: spacing.sm,
  },
});
