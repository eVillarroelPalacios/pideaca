import { useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Animated,
  Easing,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  useWindowDimensions,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ErrorBanner } from '@/components/ErrorBanner';
import { PrimaryButton } from '@/components/PrimaryButton';
import { visiblePanels, type PanelKey } from '@/lib/profilePanels';
import { colors, radius, spacing } from '@/theme';
import type { ProfileFlags } from '@/types/api';

export type AccountSidebarUser = {
  name: string;
  email: string;
  role: string | null;
  status: string | null;
  /** null mientras el perfil carga (no muestra Titular/Secundario). */
  is_principal: boolean | null;
};

export type ManagedAccount = {
  id: number;
  name: string;
  email: string;
  role?: string | null;
};

type Props = {
  visible: boolean;
  onClose: () => void;
  /** Usuario efectivo (el gestionado si el Admin está en modo managed). */
  user: AccountSidebarUser | null;
  flags: ProfileFlags | null;
  managedId: number | null;
  managed?: ManagedAccount | null;
  activePanel?: PanelKey | null;
  onSelectPanel: (panel: PanelKey) => void;
  onExitManaged?: () => void;
  /** En la pantalla Usuarios, "Usuarios" sigue visible en modo managed. */
  keepUsersInManaged?: boolean;
  loading?: boolean;
  error?: string | null;
};

function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

/**
 * Colores del estado del usuario, iguales a los del perfil web
 * (activo verde, inactivo rojo, prueba ámbar).
 */
function statusTone(status: string | null): { backgroundColor: string; color: string } {
  const key = (status ?? '').trim().toLowerCase();

  if (['active', 'activo', 'activa'].includes(key)) {
    return { backgroundColor: '#ecfdf5', color: '#047857' };
  }
  if (['inactive', 'inactivo', 'inactiva'].includes(key)) {
    return { backgroundColor: '#fef2f2', color: '#b91c1c' };
  }
  if (key === 'prueba') {
    return { backgroundColor: '#fffbeb', color: '#b45309' };
  }

  return { backgroundColor: colors.background, color: colors.muted };
}

/**
 * Sidebar de cuenta (espejo del `#profile-sidebar` de la web).
 *
 * En el teléfono se muestra como drawer deslizante desde la izquierda:
 * cabecera con la info del usuario efectivo, badge de gestión y la lista
 * de secciones habilitadas según rol/permisos.
 */
export function AccountSidebar({
  visible,
  onClose,
  user,
  flags,
  managedId,
  managed = null,
  activePanel = null,
  onSelectPanel,
  onExitManaged,
  keepUsersInManaged = false,
  loading = false,
  error = null,
}: Props) {
  const { width } = useWindowDimensions();
  const panelWidth = Math.min(340, Math.round(width * 0.86));
  // El valor animado se crea una sola vez y vive en el estado (los refs no
  // deben leerse durante el render).
  const [translateX] = useState(() => new Animated.Value(-panelWidth));
  const closingRef = useRef(false);

  useEffect(() => {
    if (!visible) {
      return;
    }

    closingRef.current = false;
    translateX.setValue(-panelWidth);
    Animated.timing(translateX, {
      toValue: 0,
      duration: 220,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: true,
    }).start();
  }, [visible, panelWidth, translateX]);

  function handleClose() {
    if (closingRef.current) {
      return;
    }

    closingRef.current = true;
    Animated.timing(translateX, {
      toValue: -panelWidth,
      duration: 180,
      easing: Easing.in(Easing.cubic),
      useNativeDriver: true,
    }).start(() => {
      closingRef.current = false;
      onClose();
    });
  }

  function handleSelect(panel: PanelKey) {
    // Cierre inmediato: si la sección navega, no esperamos la animación.
    onClose();
    onSelectPanel(panel);
  }

  const panels = visiblePanels(flags, managedId, { keepUsersInManaged });
  const tone = statusTone(user?.status ?? null);

  return (
    <Modal
      visible={visible}
      transparent
      animationType="none"
      onRequestClose={handleClose}
      statusBarTranslucent
    >
      <View style={styles.backdrop}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Cerrar secciones de la cuenta"
          onPress={handleClose}
          style={styles.backdropTap}
        />

        <Animated.View
          style={[styles.panel, { width: panelWidth, transform: [{ translateX }] }]}
        >
          <SafeAreaView style={styles.panelSafe} edges={['top', 'bottom', 'left']}>
            <View style={styles.panelHeader}>
              <Text style={styles.panelTitle}>Mi cuenta</Text>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Cerrar secciones"
                onPress={handleClose}
                style={styles.closeButton}
              >
                <Text style={styles.closeText}>✕</Text>
              </Pressable>
            </View>

            <ScrollView
              contentContainerStyle={styles.scroll}
              keyboardShouldPersistTaps="handled"
            >
              {managed ? (
                <View style={styles.managedCard}>
                  <Text style={styles.managedLabel}>Administrando</Text>
                  <Text style={styles.managedName}>{managed.name}</Text>
                  {managed.role ? (
                    <Text style={styles.managedRole}>({managed.role})</Text>
                  ) : null}
                  {onExitManaged ? (
                    <Pressable
                      accessibilityRole="button"
                      accessibilityLabel={`Volver a mi cuenta`}
                      onPress={() => {
                        onClose();
                        onExitManaged();
                      }}
                      style={styles.managedExit}
                    >
                      <Text style={styles.managedExitText}>Volver a mi cuenta</Text>
                    </Pressable>
                  ) : null}
                </View>
              ) : null}

              {user ? (
                <>
                  <View style={styles.userCard}>
                    <View style={styles.avatar}>
                      <Text style={styles.avatarText}>{initials(user.name)}</Text>
                    </View>
                    <View style={styles.userInfo}>
                      <Text style={styles.userName} numberOfLines={1}>
                        {user.name}
                      </Text>
                      <Text style={styles.userEmail} numberOfLines={1}>
                        {user.email}
                      </Text>
                      {user.role ? (
                        <Text style={styles.userRole} numberOfLines={1}>
                          {user.role}
                        </Text>
                      ) : null}
                    </View>
                  </View>

                  <View style={styles.badges}>
                    {user.status ? (
                      <View style={[styles.badge, { backgroundColor: tone.backgroundColor }]}>
                        <Text style={[styles.badgeText, { color: tone.color }]}>
                          {user.status}
                        </Text>
                      </View>
                    ) : null}
                    {user.is_principal !== null ? (
                      <View
                        style={[
                          styles.badge,
                          user.is_principal ? styles.badgePrincipal : styles.badgeSecondary,
                        ]}
                      >
                        <Text
                          style={[
                            styles.badgeText,
                            user.is_principal
                              ? styles.badgeTextPrincipal
                              : styles.badgeTextSecondary,
                          ]}
                        >
                          {user.is_principal ? 'Titular' : 'Secundario'}
                        </Text>
                      </View>
                    ) : null}
                  </View>
                </>
              ) : null}

              <ErrorBanner message={error} />

              <Text style={styles.navTitle}>Secciones</Text>

              {loading ? (
                <ActivityIndicator
                  color={colors.brand}
                  size="small"
                  style={styles.loading}
                />
              ) : null}

              {panels.map((panel) => {
                const active = activePanel === panel.key;

                return (
                  <Pressable
                    key={panel.key}
                    accessibilityRole="button"
                    accessibilityState={{ selected: active }}
                    accessibilityLabel={panel.label}
                    onPress={() => handleSelect(panel.key)}
                    style={[styles.navItem, active && styles.navItemActive]}
                  >
                    <Text style={styles.navIcon}>{panel.icon}</Text>
                    <Text
                      style={[styles.navLabel, active && styles.navLabelActive]}
                      numberOfLines={2}
                    >
                      {panel.label}
                    </Text>
                    {active ? <View style={styles.activeDot} /> : null}
                  </Pressable>
                );
              })}
            </ScrollView>

            <View style={styles.footer}>
              <PrimaryButton title="Cerrar" variant="ghost" onPress={handleClose} />
            </View>
          </SafeAreaView>
        </Animated.View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    backgroundColor: 'rgba(15,23,42,0.5)',
    flex: 1,
    flexDirection: 'row',
  },
  backdropTap: {
    bottom: 0,
    left: 0,
    position: 'absolute',
    right: 0,
    top: 0,
  },
  panel: {
    backgroundColor: colors.surface,
    borderBottomRightRadius: radius.lg,
    borderTopRightRadius: radius.lg,
    elevation: 12,
    height: '100%',
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 2, height: 0 },
    shadowOpacity: 0.15,
    shadowRadius: 12,
  },
  panelSafe: {
    flex: 1,
  },
  panelHeader: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  panelTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
  },
  closeButton: {
    alignItems: 'center',
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  closeText: {
    color: colors.muted,
    fontSize: 15,
    fontWeight: '700',
  },
  scroll: {
    padding: spacing.lg,
    paddingBottom: spacing.xl,
  },
  managedCard: {
    backgroundColor: colors.brandSoft,
    borderColor: '#fdba74',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.md,
  },
  managedLabel: {
    color: '#9a3412',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.5,
    textTransform: 'uppercase',
  },
  managedName: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
    marginTop: 2,
  },
  managedRole: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  managedExit: {
    alignItems: 'center',
    alignSelf: 'flex-start',
    backgroundColor: colors.surface,
    borderColor: '#fdba74',
    borderRadius: radius.sm,
    borderWidth: 1,
    marginTop: spacing.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
  },
  managedExitText: {
    color: colors.brandDark,
    fontSize: 11,
    fontWeight: '700',
  },
  userCard: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.pill,
    height: 48,
    justifyContent: 'center',
    width: 48,
  },
  avatarText: {
    color: colors.surface,
    fontSize: 17,
    fontWeight: '800',
  },
  userInfo: {
    flex: 1,
    minWidth: 0,
  },
  userName: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
  },
  userEmail: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 1,
  },
  userRole: {
    color: colors.brandDark,
    fontSize: 12,
    fontWeight: '600',
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
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  badgePrincipal: {
    backgroundColor: colors.successSoft,
  },
  badgeTextPrincipal: {
    color: colors.success,
  },
  badgeSecondary: {
    backgroundColor: colors.background,
  },
  badgeTextSecondary: {
    color: colors.muted,
  },
  navTitle: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.6,
    marginBottom: spacing.sm,
    marginTop: spacing.lg,
    textTransform: 'uppercase',
  },
  loading: {
    marginBottom: spacing.sm,
  },
  navItem: {
    alignItems: 'center',
    borderLeftColor: 'transparent',
    borderLeftWidth: 3,
    borderRadius: radius.sm,
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: 2,
    minHeight: 44,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
  },
  navItemActive: {
    backgroundColor: colors.brandSoft,
    borderLeftColor: colors.brand,
  },
  navIcon: {
    fontSize: 16,
    width: 22,
  },
  navLabel: {
    color: colors.text,
    flex: 1,
    fontSize: 13,
    fontWeight: '600',
  },
  navLabelActive: {
    color: colors.brandDark,
    fontWeight: '800',
  },
  activeDot: {
    backgroundColor: colors.brand,
    borderRadius: radius.pill,
    height: 6,
    width: 6,
  },
  footer: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    padding: spacing.lg,
  },
});
