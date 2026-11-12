import {
  router,
  useNavigation,
  type NavigationState,
} from 'expo-router';
import { useEffect, useState } from 'react';
import {
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
import { useManaged } from '@/auth/ManagedContext';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { menuRouteFor } from '@/lib/menuRoutes';
import { colors, radius, spacing } from '@/theme';
import type { MenuModule, User } from '@/types/api';

function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

function startCase(value: string): string {
  const lower = value.toLowerCase();

  return lower.charAt(0).toUpperCase() + lower.slice(1);
}

function isActiveStatus(status: string | null): boolean {
  return /activ|habilitad|enabled/i.test(status ?? '');
}

function accountBadge(user: User): string {
  if (user.is_admin) {
    return 'Administrador';
  }

  if (user.has_provider) {
    return 'Proveedor';
  }

  if (user.is_driver) {
    return 'Repartidor';
  }

  return 'Titular';
}

/** Icono por página, para que cada fila se reconozca de un vistazo. */
function menuPageIcon(url: string | null, description: string): string {
  const key = `${url ?? ''} ${description}`.toLowerCase();

  if (/repartidor/.test(key)) {
    return '🛵';
  }

  if (/perfil/.test(key)) {
    return '👤';
  }

  if (/consulta/.test(key)) {
    return '💬';
  }

  if (/estado.*grupo/.test(key)) {
    return '🏷️';
  }

  if (/estado.*usuario/.test(key)) {
    return '🚦';
  }

  if (/grupo/.test(key)) {
    return '👥';
  }

  if (/modulo/.test(key)) {
    return '🧩';
  }

  if (/pagina/.test(key)) {
    return '📄';
  }

  if (/moneda/.test(key)) {
    return '💱';
  }

  if (/unidad/.test(key)) {
    return '📏';
  }

  if (/tipo.*usuario/.test(key)) {
    return '🧑‍💼';
  }

  if (/usuario/.test(key)) {
    return '👥';
  }

  if (/catalogo/.test(key)) {
    return '🗂️';
  }

  if (/inventario/.test(key)) {
    return '📦';
  }

  if (/finanza/.test(key)) {
    return '💰';
  }

  if (/retencion/.test(key)) {
    return '🎯';
  }

  if (/suscrip/.test(key)) {
    return '🔄';
  }

  if (/reporte/.test(key)) {
    return '📈';
  }

  if (/comercio/.test(key)) {
    return '🏪';
  }

  if (/pedido/.test(key)) {
    return '🧾';
  }

  if (/solicit/.test(key)) {
    return '🛠️';
  }

  if (/servicio/.test(key)) {
    return '📦';
  }

  return '📄';
}

/**
 * Ruta de la pantalla que había debajo del drawer. El menú se presenta como
 * modal transparente, por eso `usePathname()` siempre devuelve `/menu`; el
 * stack de navegación sí conserva la ruta anterior para resaltar el ítem.
 */
function previousRoutePath(state: NavigationState | undefined): string | null {
  if (!state?.routes.length) {
    return null;
  }

  const index =
    typeof state.index === 'number' ? state.index : state.routes.length - 1;

  for (let i = index - 1; i >= 0; i -= 1) {
    const name = String(state.routes[i]?.name ?? '');

    if (name && name !== 'menu') {
      return name === 'index' ? '/home' : `/${name}`;
    }
  }

  return null;
}

export default function MenuScreen() {
  const { user } = useAuth();
  const { managed, exit: exitManaged } = useManaged();
  const navigation = useNavigation();

  const [modules, setModules] = useState<MenuModule[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const activePath = previousRoutePath(navigation.getState());

  useEffect(() => {
    let active = true;

    api
      .menu(managed?.id ?? null)
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
  }, [managed?.id]);

  async function handleRefresh() {
    setRefreshing(true);

    try {
      const response = await api.menu(managed?.id ?? null);
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

  function closeDrawer() {
    if (router.canGoBack()) {
      router.back();
      return;
    }

    router.replace('/home');
  }

  const status = user?.status?.trim() || 'Activo';
  const statusActive = isActiveStatus(status);

  return (
    <View style={styles.overlay}>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Cerrar menú"
        onPress={closeDrawer}
        style={StyleSheet.absoluteFill}
      />

      <SafeAreaView style={styles.panel} edges={['top', 'bottom', 'left']}>
        <View style={styles.header}>
          <Text style={styles.title}>Mi cuenta</Text>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Cerrar menú"
            hitSlop={8}
            onPress={closeDrawer}
            style={styles.closeButton}
          >
            <Text style={styles.closeIcon}>✕</Text>
          </Pressable>
        </View>

        {loading ? (
          <LoadingScreen message="Cargando menú…" />
        ) : (
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
            showsVerticalScrollIndicator={false}
          >
            <ErrorBanner message={error} />

            {user ? (
              <View style={styles.profile}>
                <View style={styles.profileRow}>
                  <View style={styles.avatar}>
                    <Text style={styles.avatarText}>{initials(user.name)}</Text>
                  </View>
                  <View style={styles.profileInfo}>
                    <Text style={styles.name} numberOfLines={1}>
                      {user.name}
                    </Text>
                    <Text style={styles.email} numberOfLines={1}>
                      {user.email}
                    </Text>
                    {user.role ? <Text style={styles.role}>{user.role}</Text> : null}
                  </View>
                </View>

                <View style={styles.badges}>
                  <View
                    style={[
                      styles.badge,
                      statusActive ? styles.badgeGreen : styles.badgeGray,
                    ]}
                  >
                    <Text
                      style={[
                        styles.badgeText,
                        statusActive ? styles.badgeTextGreen : styles.badgeTextGray,
                      ]}
                    >
                      {startCase(status)}
                    </Text>
                  </View>
                  <View style={[styles.badge, styles.badgeGray]}>
                    <Text style={[styles.badgeText, styles.badgeTextGray]}>
                      {accountBadge(user)}
                    </Text>
                  </View>
                </View>
              </View>
            ) : null}

            {managed ? (
              <View style={styles.managedCard}>
                <View style={styles.managedInfo}>
                  <Text style={styles.managedTitle}>
                    Gestionando a {managed.name}
                  </Text>
                  <Text style={styles.managedSub}>
                    {managed.email}
                    {managed.role ? ` · ${managed.role}` : ''}
                  </Text>
                </View>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Salir de la cuenta gestionada"
                  onPress={() => void exitManaged()}
                  style={styles.managedExit}
                >
                  <Text style={styles.managedExitText}>Salir</Text>
                </Pressable>
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
                  <Text style={styles.sectionTitle}>{module.description}</Text>

                  {module.pages.map((page) => {
                    const route = menuRouteFor(module.description, page.url);
                    const icon = menuPageIcon(page.url, page.description);
                    const active = route !== null && route === activePath;

                    if (!route) {
                      return (
                        <View
                          key={page.id}
                          style={[styles.row, styles.rowDisabled]}
                        >
                          <Text style={styles.rowIcon}>{icon}</Text>
                          <Text style={styles.rowTextDisabled}>
                            {page.description}
                          </Text>
                          <View style={styles.soonBadge}>
                            <Text style={styles.soonText}>Próximamente</Text>
                          </View>
                        </View>
                      );
                    }

                    if (active) {
                      return (
                        <Pressable
                          key={page.id}
                          accessibilityRole="button"
                          accessibilityState={{ selected: true }}
                          onPress={closeDrawer}
                          style={[styles.row, styles.rowActive]}
                        >
                          <Text style={styles.rowIcon}>{icon}</Text>
                          <Text style={[styles.rowText, styles.rowTextActive]}>
                            {page.description}
                          </Text>
                          <View style={styles.activeDot} />
                        </Pressable>
                      );
                    }

                    return (
                      <Pressable
                        key={page.id}
                        accessibilityRole="button"
                        onPress={() => router.dismissTo(route as never)}
                        style={styles.row}
                      >
                        <Text style={styles.rowIcon}>{icon}</Text>
                        <Text style={styles.rowText}>{page.description}</Text>
                      </Pressable>
                    );
                  })}
                </View>
              ))
            )}
          </ScrollView>
        )}

        <View style={styles.footer}>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Cerrar menú"
            onPress={closeDrawer}
            style={styles.footerButton}
          >
            <Text style={styles.footerText}>Cerrar</Text>
          </Pressable>
        </View>
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  overlay: {
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    flex: 1,
  },
  panel: {
    backgroundColor: colors.surface,
    elevation: 16,
    height: '100%',
    shadowColor: '#000',
    shadowOffset: { width: 2, height: 0 },
    shadowOpacity: 0.22,
    shadowRadius: 12,
    width: '85%',
    zIndex: 1,
  },
  header: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingBottom: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
  },
  title: {
    color: colors.text,
    fontSize: 20,
    fontWeight: '800',
  },
  closeButton: {
    alignItems: 'center',
    backgroundColor: '#f1f5f9',
    borderRadius: radius.pill,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  closeIcon: {
    color: '#475569',
    fontSize: 14,
    fontWeight: '700',
  },
  content: {
    paddingBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
  },
  profile: {
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    paddingBottom: spacing.lg,
    paddingTop: spacing.xs,
  },
  profileRow: {
    alignItems: 'center',
    flexDirection: 'row',
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.pill,
    height: 56,
    justifyContent: 'center',
    width: 56,
  },
  avatarText: {
    color: colors.surface,
    fontSize: 20,
    fontWeight: '800',
  },
  profileInfo: {
    flex: 1,
    marginLeft: spacing.md,
  },
  name: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '800',
  },
  email: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
  },
  role: {
    color: colors.brand,
    fontSize: 12,
    fontWeight: '700',
    marginTop: 2,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.md,
  },
  badge: {
    borderRadius: radius.pill,
    paddingHorizontal: 10,
    paddingVertical: 3,
  },
  badgeGreen: {
    backgroundColor: colors.successSoft,
  },
  badgeGray: {
    backgroundColor: '#f1f5f9',
  },
  badgeText: {
    fontSize: 10,
    fontWeight: '800',
  },
  badgeTextGreen: {
    color: colors.success,
  },
  badgeTextGray: {
    color: colors.muted,
  },
  managedCard: {
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    borderColor: '#bfdbfe',
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.lg,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },
  managedInfo: {
    flex: 1,
    paddingRight: spacing.md,
  },
  managedTitle: {
    color: '#1d4ed8',
    fontSize: 13,
    fontWeight: '800',
  },
  managedSub: {
    color: '#1d4ed8',
    fontSize: 11,
    marginTop: 2,
  },
  managedExit: {
    backgroundColor: colors.surface,
    borderColor: '#bfdbfe',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
  },
  managedExitText: {
    color: '#1d4ed8',
    fontSize: 12,
    fontWeight: '700',
  },
  section: {
    marginTop: spacing.xl,
  },
  sectionTitle: {
    color: '#94a3b8',
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 1,
    marginBottom: spacing.sm,
    paddingHorizontal: spacing.xs,
    textTransform: 'uppercase',
  },
  row: {
    alignItems: 'center',
    borderLeftColor: 'transparent',
    borderLeftWidth: 3,
    borderRadius: radius.sm,
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: 2,
    minHeight: 44,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  rowActive: {
    backgroundColor: colors.brandSoft,
    borderLeftColor: colors.brand,
  },
  rowDisabled: {
    opacity: 0.7,
  },
  rowIcon: {
    fontSize: 17,
    width: 22,
  },
  rowText: {
    color: colors.text,
    flex: 1,
    fontSize: 14,
    fontWeight: '600',
  },
  rowTextActive: {
    color: colors.brandDark,
    fontWeight: '800',
  },
  rowTextDisabled: {
    color: colors.muted,
    flex: 1,
    fontSize: 14,
    fontWeight: '600',
  },
  activeDot: {
    backgroundColor: colors.brand,
    borderRadius: radius.pill,
    height: 7,
    width: 7,
  },
  soonBadge: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  soonText: {
    color: colors.muted,
    fontSize: 10,
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
  footer: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    paddingBottom: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
  },
  footerButton: {
    alignItems: 'center',
    borderColor: '#d1d5db',
    borderRadius: radius.md,
    borderWidth: 1,
    justifyContent: 'center',
    minHeight: 44,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  footerText: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
});
