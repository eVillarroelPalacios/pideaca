import { Link, Redirect, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import {
  Alert,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { API_BASE_URL } from '@/config';
import { colors, radius, spacing } from '@/theme';

function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

function roleColors(role: string | null): { background: string; foreground: string } {
  const value = (role ?? '').toLowerCase();

  if (value === 'repartidor') {
    return { background: '#eff6ff', foreground: '#1d4ed8' };
  }

  if (value === 'prestador') {
    return { background: colors.brandSoft, foreground: colors.brandDark };
  }

  return { background: colors.successSoft, foreground: colors.success };
}

export default function HomeScreen() {
  const { user, isLoading, signOut, deleteAccount, refresh } = useAuth();
  const [refreshing, setRefreshing] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);

  // Contador de avisos sin leer: se actualiza cada vez que volvés al inicio.
  useFocusEffect(
    useCallback(() => {
      let active = true;

      api
        .notifications({ per_page: 1 })
        .then((response) => {
          if (active) {
            setUnreadCount(response.meta.unread_count);
          }
        })
        .catch(() => {
          // Sin conexión no hay contador; la pantalla igual funciona.
        });

      return () => {
        active = false;
      };
    }, []),
  );

  if (isLoading) {
    return <LoadingScreen message="Cargando tu cuenta…" />;
  }

  if (!user) {
    return <Redirect href="/login" />;
  }

  const showProviderCard = user.has_provider;

  const badge = roleColors(user.role);
  const firstName = user.name.split(' ')[0] ?? user.name;

  async function handleRefresh() {
    setRefreshing(true);

    try {
      await refresh();
    } catch {
      // Si el token venció, el guard de arriba redirige al login.
    } finally {
      setRefreshing(false);
    }
  }

  async function handleDelete() {
    setDeleting(true);

    try {
      await deleteAccount();
    } catch (caught) {
      Alert.alert(
        'No pudimos desactivar la cuenta',
        caught instanceof ApiError ? caught.message : 'Intentá de nuevo en unos minutos.',
      );
    } finally {
      setDeleting(false);
    }
  }

  function confirmDelete() {
    Alert.alert(
      'Eliminar cuenta',
      'Tu cuenta será desactivada y se cerrará la sesión. No vas a poder volver a ingresar desde la app. ¿Querés continuar?',
      [
        { text: 'Cancelar', style: 'cancel' },
        { text: 'Desactivar', style: 'destructive', onPress: () => void handleDelete() },
      ],
    );
  }

  return (
    <SafeAreaView style={styles.safe}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={colors.brand}
            colors={[colors.brand]}
          />
        }
      >
        <View style={styles.header}>
          <Text style={styles.brand}>PideAca</Text>
          <Pressable accessibilityRole="button" onPress={signOut} style={styles.logout}>
            <Text style={styles.logoutText}>Salir</Text>
          </Pressable>
        </View>

        <Text style={styles.greeting}>Hola, {firstName} 👋</Text>
        <Text style={styles.subtitle}>Esta es tu cuenta en la app móvil.</Text>

        <Link href="/providers" asChild>
          <Pressable accessibilityRole="button" style={styles.exploreCard}>
            <View>
              <Text style={styles.exploreTitle}>🔧 Explorar prestadores</Text>
              <Text style={styles.exploreText}>
                Urgencias, citas y presupuestos cerca tuyo.
              </Text>
            </View>
            <Text style={styles.exploreArrow}>›</Text>
          </Pressable>
        </Link>

        {showProviderCard ? (
          <Link href="/provider-orders" asChild>
            <Pressable accessibilityRole="button" style={styles.providerCard}>
              <View>
                <Text style={styles.providerTitle}>🏪 Mi comercio</Text>
                <Text style={styles.providerText}>
                  Pool de trabajos, propuestas y cierres.
                </Text>
              </View>
              <Text style={styles.exploreArrow}>›</Text>
            </Pressable>
          </Link>
        ) : null}

        <Link href="/requests" asChild>
          <Pressable accessibilityRole="button" style={styles.requestsCard}>
            <View>
              <Text style={styles.requestsTitle}>📋 Mis solicitudes</Text>
              <Text style={styles.requestsText}>
                Seguí tus urgencias, citas y presupuestos.
              </Text>
            </View>
            <Text style={styles.exploreArrow}>›</Text>
          </Pressable>
        </Link>

        <Link href="/notifications" asChild>
          <Pressable accessibilityRole="button" style={styles.requestsCard}>
            <View style={styles.notificationRow}>
              <Text style={styles.requestsTitle}>🔔 Avisos</Text>
              {unreadCount > 0 ? (
                <View style={styles.unreadBadge}>
                  <Text style={styles.unreadText}>{unreadCount}</Text>
                </View>
              ) : null}
            </View>
            <Text style={styles.exploreArrow}>›</Text>
          </Pressable>
        </Link>

        <View style={styles.profileCard}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{initials(user.name)}</Text>
          </View>

          <View style={styles.profileInfo}>
            <Text style={styles.name}>{user.name}</Text>
            <Text style={styles.email}>{user.email}</Text>

            <View style={styles.badges}>
              <View style={[styles.badge, { backgroundColor: badge.background }]}>
                <Text style={[styles.badgeText, { color: badge.foreground }]}>
                  {user.role ?? 'Sin rol'}
                </Text>
              </View>

              {user.status ? (
                <View style={[styles.badge, styles.statusBadge]}>
                  <Text style={[styles.badgeText, styles.statusText]}>{user.status}</Text>
                </View>
              ) : null}
            </View>
          </View>
        </View>

        {user.is_driver ? (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Datos de repartidor</Text>
            <Row label="Vehículo" value={user.vehicle_type ?? 'Sin definir'} />
            <Row label="Patente" value={user.vehicle_plate ?? 'Sin definir'} last />
          </View>
        ) : null}

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Resumen</Text>
          <Row label="ID de cuenta" value={String(user.id)} />
          <Row label="Tipo" value={user.role ?? 'Sin rol'} />
          <Row label="Estado" value={user.status ?? 'Sin estado'} last />
        </View>

        <View style={styles.tipCard}>
          <Text style={styles.tipTitle}>Próximo paso</Text>
          <Text style={styles.tipText}>
            Si tenés un comercio asociado, entrás al panel del prestador desde
            Mi comercio.
          </Text>
        </View>

        <PrimaryButton title="Actualizar mis datos" onPress={handleRefresh} variant="ghost" />

        <Pressable
          accessibilityRole="button"
          disabled={deleting}
          onPress={confirmDelete}
          style={styles.deleteButton}
        >
          <Text style={styles.deleteText}>
            {deleting ? 'Desactivando cuenta…' : 'Eliminar mi cuenta'}
          </Text>
        </Pressable>

        {__DEV__ ? (
          <Text style={styles.devText}>API: {API_BASE_URL}</Text>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

function Row({ label, value, last = false }: { label: string; value: string; last?: boolean }) {
  return (
    <View style={[styles.row, last && styles.rowLast]}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.background,
    flex: 1,
  },
  content: {
    padding: spacing.xl,
    paddingBottom: spacing.xxl,
  },
  header: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.xl,
  },
  brand: {
    color: colors.brand,
    fontSize: 20,
    fontWeight: '800',
  },
  logout: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  logoutText: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  greeting: {
    color: colors.text,
    fontSize: 30,
    fontWeight: '800',
    marginBottom: spacing.xs,
  },
  subtitle: {
    color: colors.muted,
    fontSize: 15,
    marginBottom: spacing.xl,
  },
  exploreCard: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.lg,
    elevation: 2,
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.lg,
    padding: spacing.xl,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.12,
    shadowRadius: 16,
  },
  exploreTitle: {
    color: colors.surface,
    fontSize: 18,
    fontWeight: '800',
    marginBottom: 4,
  },
  exploreText: {
    color: '#ffedd5',
    fontSize: 14,
  },
  exploreArrow: {
    color: colors.surface,
    fontSize: 32,
    fontWeight: '300',
  },
  requestsCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    elevation: 2,
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.lg,
    padding: spacing.xl,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.08,
    shadowRadius: 16,
  },
  requestsTitle: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
    marginBottom: 4,
  },
  requestsText: {
    color: colors.muted,
    fontSize: 14,
  },
  notificationRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
  },
  unreadBadge: {
    alignItems: 'center',
    backgroundColor: colors.danger,
    borderRadius: radius.pill,
    justifyContent: 'center',
    minWidth: 24,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  unreadText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '800',
  },
  providerCard: {
    alignItems: 'center',
    backgroundColor: colors.text,
    borderRadius: radius.lg,
    elevation: 2,
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.lg,
    padding: spacing.xl,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.12,
    shadowRadius: 16,
  },
  providerTitle: {
    color: colors.surface,
    fontSize: 18,
    fontWeight: '800',
    marginBottom: 4,
  },
  providerText: {
    color: '#cbd5e1',
    fontSize: 14,
  },
  profileCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    elevation: 2,
    flexDirection: 'row',
    gap: spacing.lg,
    marginBottom: spacing.lg,
    padding: spacing.xl,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.08,
    shadowRadius: 16,
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.pill,
    height: 64,
    justifyContent: 'center',
    width: 64,
  },
  avatarText: {
    color: colors.surface,
    fontSize: 22,
    fontWeight: '800',
  },
  profileInfo: {
    flex: 1,
  },
  name: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '700',
  },
  email: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.sm,
  },
  badge: {
    borderRadius: radius.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '700',
  },
  statusBadge: {
    backgroundColor: colors.background,
  },
  statusText: {
    color: colors.muted,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.xl,
    paddingVertical: spacing.sm,
  },
  cardTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
    paddingVertical: spacing.md,
  },
  row: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: spacing.md,
  },
  rowLast: {
    borderBottomWidth: 0,
  },
  rowLabel: {
    color: colors.muted,
    fontSize: 14,
  },
  rowValue: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  tipCard: {
    backgroundColor: colors.brandSoft,
    borderColor: '#fed7aa',
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.lg,
  },
  tipTitle: {
    color: colors.brandDark,
    fontSize: 14,
    fontWeight: '700',
    marginBottom: spacing.xs,
  },
  tipText: {
    color: '#9a3412',
    fontSize: 14,
    lineHeight: 20,
  },
  deleteButton: {
    alignItems: 'center',
    marginTop: spacing.lg,
    paddingVertical: spacing.md,
  },
  deleteText: {
    color: colors.danger,
    fontSize: 14,
    fontWeight: '600',
  },
  devText: {
    color: colors.muted,
    fontSize: 11,
    marginTop: spacing.lg,
    textAlign: 'center',
  },
});
