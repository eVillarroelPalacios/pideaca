import { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Modal,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ComboField } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';
import type { AdminUserItem } from '@/types/api';

export type UserSearchStatus = {
  id: number;
  status: string;
};

export type UserSearchType = {
  id: number;
  description: string;
};

type Props = {
  visible: boolean;
  onClose: () => void;
  users: AdminUserItem[];
  statuses: UserSearchStatus[];
  types: UserSearchType[];
  loading?: boolean;
  error?: string | null;
  onSelect: (user: AdminUserItem) => void;
  onRetry?: () => void;
};

/**
 * Buscar y seleccionar usuario (espejo del modal web `user-select-overlay`).
 *
 * Nada de tablas anchas: lista vertical (`FlatList`) de tarjetas, una por
 * usuario, pensada para pantallas chicas y gestos tactiles.
 */
export function UserSearchModal({
  visible,
  onClose,
  users,
  statuses,
  types,
  loading = false,
  error = null,
  onSelect,
  onRetry,
}: Props) {
  const [query, setQuery] = useState('');
  const [statusId, setStatusId] = useState<number | null>(null);
  const [typeId, setTypeId] = useState<number | null>(null);

  const results = useMemo(() => {
    const term = query.trim().toLowerCase();

    return users.filter((user) => {
      const matchesText =
        !term ||
        user.name.toLowerCase().includes(term) ||
        user.email.toLowerCase().includes(term);
      const matchesStatus = statusId === null || user.status_id === statusId;
      const matchesType = typeId === null || user.type_user_id === typeId;

      return matchesText && matchesStatus && matchesType;
    });
  }, [users, query, statusId, typeId]);

  function handleClose() {
    setQuery('');
    setStatusId(null);
    setTypeId(null);
    onClose();
  }

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={handleClose}>
      <View style={styles.backdrop}>
        <SafeAreaView style={styles.card} edges={['top', 'bottom']}>
          <View style={styles.header}>
            <Text style={styles.title}>Buscar Usuario</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Cerrar búsqueda"
              onPress={handleClose}
              style={styles.closeButton}
            >
              <Text style={styles.closeText}>✕</Text>
            </Pressable>
          </View>

          <TextField
            label="Nombre o correo"
            placeholder="Escribí para filtrar…"
            value={query}
            onChangeText={setQuery}
            autoCapitalize="none"
            autoCorrect={false}
            returnKeyType="search"
          />

          <ComboField
            label="Estado"
            value={statusId}
            options={statuses.map((status) => ({
              value: status.id,
              label: status.status,
            }))}
            onChange={setStatusId}
            allowEmpty
            emptyLabel="Todos"
            onClear={() => setStatusId(null)}
          />

          <ComboField
            label="Tipo"
            value={typeId}
            options={types.map((type) => ({
              value: type.id,
              label: type.description,
            }))}
            onChange={setTypeId}
            allowEmpty
            emptyLabel="Todos"
            onClear={() => setTypeId(null)}
          />

          <Text style={styles.selectorLabel}>{results.length} resultados</Text>

          {loading ? (
            <View style={styles.loading}>
              <ActivityIndicator size="large" color={colors.brand} />
              <Text style={styles.loadingText}>Buscando usuarios…</Text>
            </View>
          ) : (
            <FlatList
              data={results}
              keyExtractor={(item) => String(item.id)}
              style={styles.list}
              contentContainerStyle={styles.listContent}
              keyboardShouldPersistTaps="handled"
              ListEmptyComponent={
                <View style={styles.empty}>
                  <Text style={styles.emptyTitle}>Sin resultados</Text>
                  <Text style={styles.emptyText}>
                    {error
                      ? error
                      : 'No hay usuarios para esos filtros. Probá con otro texto.'}
                  </Text>
                  {error && onRetry ? (
                    <View style={styles.retry}>
                      <PrimaryButton title="Reintentar" onPress={onRetry} />
                    </View>
                  ) : null}
                </View>
              }
              renderItem={({ item: user }) => (
                <View style={styles.userCard}>
                  <View style={styles.userInfo}>
                    <Text style={styles.userName} numberOfLines={1}>
                      {user.name}
                      {user.is_principal ? ' (principal)' : ''}
                    </Text>
                    <Text style={styles.userEmail} numberOfLines={1}>
                      {user.email}
                    </Text>
                    <View style={styles.badges}>
                      <View style={[styles.badge, user.is_principal && styles.badgePrincipal]}>
                        <Text
                          style={[styles.badgeText, user.is_principal && styles.badgeTextPrincipal]}
                        >
                          {user.role ?? 'Usuario'}
                        </Text>
                      </View>
                      <View
                        style={[
                          styles.badge,
                          user.is_inactive ? styles.badgeInactive : styles.badgeActive,
                        ]}
                      >
                        <Text
                          style={[
                            styles.badgeText,
                            user.is_inactive ? styles.badgeTextInactive : styles.badgeTextActive,
                          ]}
                        >
                          {user.status ?? (user.is_inactive ? 'Inactivo' : 'Sin estado')}
                        </Text>
                      </View>
                    </View>
                  </View>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel={`Seleccionar a ${user.name}`}
                    onPress={() => onSelect(user)}
                    style={styles.selectButton}
                  >
                    <Text style={styles.selectText}>Seleccionar</Text>
                  </Pressable>
                </View>
              )}
            />
          )}

          <View style={styles.footer}>
            <ErrorBanner message={loading ? null : error} />
            <PrimaryButton title="Cerrar" variant="ghost" onPress={handleClose} />
          </View>
        </SafeAreaView>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.5)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    maxHeight: '90%',
    maxWidth: 520,
    padding: spacing.lg,
    width: '100%',
  },
  header: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  title: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
  },
  closeButton: {
    alignItems: 'center',
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 36,
    justifyContent: 'center',
    width: 36,
  },
  closeText: {
    color: colors.muted,
    fontSize: 16,
    fontWeight: '700',
  },
  selectorLabel: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
    marginBottom: spacing.xs,
    marginTop: spacing.sm,
  },
  chipsWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.xs,
  },
  chip: {
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
  },
  chipSelected: {
    backgroundColor: colors.brandSoft,
    borderColor: colors.brand,
  },
  chipText: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
  },
  chipTextSelected: {
    color: colors.brandDark,
  },
  loading: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
  },
  loadingText: {
    color: colors.muted,
    fontSize: 13,
    marginTop: spacing.sm,
  },
  list: {
    flexGrow: 0,
    maxHeight: 380,
  },
  listContent: {
    paddingBottom: spacing.sm,
  },
  empty: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
  },
  emptyTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
    marginBottom: spacing.xs,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 13,
    textAlign: 'center',
  },
  retry: {
    marginTop: spacing.md,
    minWidth: 160,
  },
  userCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.sm,
    padding: spacing.md,
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
    marginTop: 2,
  },
  badges: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.xs,
    marginTop: spacing.xs,
  },
  badge: {
    backgroundColor: '#eff6ff',
    borderRadius: radius.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  badgeText: {
    color: '#1d4ed8',
    fontSize: 10,
    fontWeight: '700',
  },
  badgePrincipal: {
    backgroundColor: colors.brandSoft,
  },
  badgeTextPrincipal: {
    color: colors.brandDark,
  },
  badgeActive: {
    backgroundColor: colors.successSoft,
  },
  badgeTextActive: {
    color: colors.success,
  },
  badgeInactive: {
    backgroundColor: '#fee2e2',
  },
  badgeTextInactive: {
    color: colors.danger,
  },
  selectButton: {
    alignItems: 'center',
    backgroundColor: colors.brand,
    borderRadius: radius.md,
    justifyContent: 'center',
    minHeight: 44,
    minWidth: 108,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  selectText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '700',
  },
  footer: {
    marginTop: spacing.sm,
  },
});
