import { Link, router } from 'expo-router';
import { useEffect, useState } from 'react';
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
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { menuModuleIcon, menuRouteFor } from '@/lib/menuRoutes';
import { colors, radius, spacing } from '@/theme';
import type { MenuModule } from '@/types/api';

function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

export default function MenuScreen() {
  const { user, signOut } = useAuth();
  const [modules, setModules] = useState<MenuModule[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    api
      .menu()
      .then((response) => {
        if (active) {
          setModules(response.data.modules);
          setError(null);
        }
      })
      .catch((caught: unknown) => {
        if (active) {
          setError(
            caught instanceof ApiError
              ? caught.message
              : 'No pudimos cargar el menú.',
          );
        }
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  async function handleRefresh() {
    setRefreshing(true);

    try {
      const response = await api.menu();
      setModules(response.data.modules);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError ? caught.message : 'No pudimos cargar el menú.',
      );
    } finally {
      setRefreshing(false);
    }
  }

  function confirmSignOut() {
    Alert.alert('Cerrar sesión', '¿Querés salir de la app?', [
      { text: 'Volver', style: 'cancel' },
      { text: 'Salir', style: 'destructive', onPress: () => void signOut() },
    ]);
  }

  if (loading) {
    return <LoadingScreen message="Cargando menú…" />;
  }

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>
        <Text style={styles.title}>Menú</Text>
        <View style={styles.headerSpacer} />
      </View>

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
        <ErrorBanner message={error} />

        {user ? (
          <View style={styles.profileCard}>
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>{initials(user.name)}</Text>
            </View>
            <View style={styles.profileInfo}>
              <Text style={styles.name}>{user.name}</Text>
              <Text style={styles.email}>{user.email}</Text>
              {user.role ? <Text style={styles.role}>{user.role}</Text> : null}
            </View>
          </View>
        ) : null}

        {modules.length === 0 ? (
          <View style={styles.empty}>
            <Text style={styles.emptyTitle}>Sin secciones</Text>
            <Text style={styles.emptyText}>
              Tu usuario todavía no tiene páginas asignadas.
            </Text>
          </View>
        ) : (
          modules.map((module) => (
            <View key={module.id} style={styles.section}>
              <Text style={styles.sectionTitle}>
                {menuModuleIcon(module.description)} {module.description}
              </Text>

              {module.pages.map((page) => {
                const route = menuRouteFor(module.description, page.url);

                if (!route) {
                  return (
                    <View key={page.id} style={[styles.row, styles.rowDisabled]}>
                      <Text style={styles.rowTextDisabled}>{page.description}</Text>
                      <View style={styles.soonBadge}>
                        <Text style={styles.soonText}>Próximamente</Text>
                      </View>
                    </View>
                  );
                }

                return (
                  <Link key={page.id} href={route as never} asChild>
                    <Pressable accessibilityRole="button" style={styles.row}>
                      <Text style={styles.rowText}>{page.description}</Text>
                      <Text style={styles.rowArrow}>›</Text>
                    </Pressable>
                  </Link>
                );
              })}
            </View>
          ))
        )}

        <Pressable
          accessibilityRole="button"
          onPress={confirmSignOut}
          style={styles.logoutButton}
        >
          <Text style={styles.logoutText}>Cerrar sesión</Text>
        </Pressable>
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
  content: {
    padding: spacing.lg,
    paddingBottom: spacing.xxl,
  },
  profileCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    flexDirection: 'row',
    marginBottom: spacing.lg,
    padding: spacing.lg,
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: 999,
    height: 44,
    justifyContent: 'center',
    width: 44,
  },
  avatarText: {
    color: colors.surface,
    fontSize: 17,
    fontWeight: '800',
  },
  profileInfo: {
    flex: 1,
    marginLeft: spacing.md,
  },
  name: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
  },
  email: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
  },
  role: {
    color: colors.brandDark,
    fontSize: 12,
    fontWeight: '700',
    marginTop: 2,
    textTransform: 'uppercase',
  },
  section: {
    marginBottom: spacing.lg,
  },
  sectionTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
    marginBottom: spacing.sm,
    paddingHorizontal: spacing.xs,
  },
  row: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  rowText: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '600',
  },
  rowArrow: {
    color: colors.muted,
    fontSize: 20,
    fontWeight: '600',
  },
  rowDisabled: {
    opacity: 0.7,
  },
  rowTextDisabled: {
    color: colors.muted,
    fontSize: 15,
    fontWeight: '600',
  },
  soonBadge: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
  },
  soonText: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  empty: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
  },
  emptyTitle: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '700',
    marginBottom: spacing.xs,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 14,
    textAlign: 'center',
  },
  logoutButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#fca5a5',
    borderRadius: radius.lg,
    borderWidth: 1,
    marginTop: spacing.md,
    paddingVertical: spacing.lg,
  },
  logoutText: {
    color: '#b91c1c',
    fontSize: 15,
    fontWeight: '700',
  },
});
