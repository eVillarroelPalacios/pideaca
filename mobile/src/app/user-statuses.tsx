import { router, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import {
  Alert,
  FlatList,
  Modal,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';
import type { UserStatusItem } from '@/types/api';

type Summary = {
  total: number;
  in_use: number;
};

const EMPTY_SUMMARY: Summary = { total: 0, in_use: 0 };

export default function UserStatusesScreen() {
  const [items, setItems] = useState<UserStatusItem[]>([]);
  const [summary, setSummary] = useState<Summary>(EMPTY_SUMMARY);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  // Modal de alta/edicion.
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<UserStatusItem | null>(null);
  const [status, setStatus] = useState('');
  const [fieldError, setFieldError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const fetchStatuses = useCallback(async () => {
    try {
      const response = await api.userStatuses();

      setItems(response.data);
      setSummary(response.summary);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar los estados.',
      );
    }
  }, []);

  // Se recarga al entrar y al volver de otra pantalla.
  useFocusEffect(
    useCallback(() => {
      let active = true;

      setLoading(true);
      void fetchStatuses().finally(() => {
        if (active) {
          setLoading(false);
        }
      });

      return () => {
        active = false;
      };
    }, [fetchStatuses]),
  );

  async function handleRefresh() {
    setRefreshing(true);
    await fetchStatuses();
    setRefreshing(false);
  }

  function openCreate() {
    setEditing(null);
    setStatus('');
    setFieldError(null);
    setNotice(null);
    setModalOpen(true);
  }

  function openEdit(item: UserStatusItem) {
    setEditing(item);
    setStatus(item.status);
    setFieldError(null);
    setNotice(null);
    setModalOpen(true);
  }

  function closeModal() {
    if (saving) {
      return;
    }

    setModalOpen(false);
    setEditing(null);
    setStatus('');
    setFieldError(null);
  }

  async function handleSave() {
    const value = status.trim();

    if (!value) {
      setFieldError('Ingresá el nombre del estado.');
      return;
    }

    setSaving(true);
    setFieldError(null);

    try {
      const response = editing
        ? await api.updateUserStatus(editing.id, value)
        : await api.createUserStatus(value);

      setModalOpen(false);
      setEditing(null);
      setStatus('');
      setNotice(response.message);
      await fetchStatuses();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setFieldError(caught.errors?.status?.[0] ?? caught.message);
      } else {
        setFieldError('No pudimos guardar el estado.');
      }
    } finally {
      setSaving(false);
    }
  }

  function confirmDelete(item: UserStatusItem) {
    if (!item.can_delete) {
      Alert.alert(
        'No se puede eliminar',
        'No se puede eliminar el estado porque tiene usuarios asociados.',
      );
      return;
    }

    Alert.alert(
      'Eliminar estado',
      `¿Estás seguro de eliminar "${item.status}"?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Eliminar',
          style: 'destructive',
          onPress: () => void remove(item),
        },
      ],
    );
  }

  async function remove(item: UserStatusItem) {
    setNotice(null);

    try {
      const response = await api.deleteUserStatus(item.id);
      setNotice(response.message);
      await fetchStatuses();
    } catch (caught) {
      Alert.alert(
        'No pudimos eliminar',
        caught instanceof ApiError
          ? caught.message
          : 'Intentá de nuevo en unos segundos.',
      );
    }
  }

  const term = search.trim().toLowerCase();
  const visible = term
    ? items.filter((item) => item.status.toLowerCase().includes(term))
    : items;

  if (loading && items.length === 0) {
    return <LoadingScreen message="Cargando estados…" />;
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
        <Text style={styles.title}>Estados de usuarios</Text>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Nuevo estado"
          onPress={openCreate}
          style={styles.addButton}
        >
          <Text style={styles.addText}>＋ Nuevo</Text>
        </Pressable>
      </View>

      <View style={styles.listContainer}>
        <Text style={styles.summary}>
          {summary.total} estados · {summary.in_use} en uso por usuarios
        </Text>

        <ErrorBanner message={error} />

        {notice ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>{notice}</Text>
          </View>
        ) : null}

        <TextField
          label="Buscar estado"
          placeholder="Nombre del estado…"
          value={search}
          onChangeText={setSearch}
          autoCapitalize="none"
          autoCorrect={false}
        />

        <FlatList
          data={visible}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.list}
          keyboardShouldPersistTaps="handled"
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={handleRefresh}
              tintColor={colors.brand}
              colors={[colors.brand]}
            />
          }
          ListEmptyComponent={
            <View style={styles.empty}>
              <Text style={styles.emptyTitle}>
                {term ? 'No se encontraron estados' : 'Todavía no hay estados'}
              </Text>
              <Text style={styles.emptyText}>
                {term
                  ? 'Probá con otro nombre.'
                  : 'Creá el primer estado con el botón Nuevo.'}
              </Text>
            </View>
          }
          renderItem={({ item }) => (
            <View style={styles.card}>
              <View style={styles.cardInfo}>
                <Text style={styles.cardTitle}>{item.status}</Text>
                <Text style={styles.cardMeta}>
                  {item.users_count === 1
                    ? '1 usuario asociado'
                    : `${item.users_count} usuarios asociados`}
                </Text>
              </View>

              <View style={styles.actions}>
                <Pressable
                  accessibilityRole="button"
                  onPress={() => openEdit(item)}
                  style={styles.actionButton}
                >
                  <Text style={styles.actionText}>Editar</Text>
                </Pressable>
                <Pressable
                  accessibilityRole="button"
                  onPress={() => confirmDelete(item)}
                  style={[styles.actionButton, styles.deleteButton]}
                >
                  <Text style={[styles.actionText, styles.deleteText]}>Eliminar</Text>
                </Pressable>
              </View>
            </View>
          )}
        />
      </View>

      <Modal
        visible={modalOpen}
        transparent
        animationType="fade"
        onRequestClose={closeModal}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>
                {editing ? 'Editar estado' : 'Nuevo estado'}
              </Text>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Cerrar"
                onPress={closeModal}
                style={styles.modalClose}
              >
                <Text style={styles.modalCloseText}>×</Text>
              </Pressable>
            </View>

            <View style={styles.modalBody}>
              <TextField
                label="Estado"
                placeholder="Nombre del estado"
                value={status}
                onChangeText={setStatus}
                maxLength={255}
                autoFocus
                error={fieldError}
              />

              <PrimaryButton
                title={editing ? 'Actualizar' : 'Guardar'}
                onPress={() => void handleSave()}
                loading={saving}
              />
              <View style={styles.buttonGap} />
              <PrimaryButton
                title="Cancelar"
                variant="ghost"
                onPress={closeModal}
                disabled={saving}
              />
            </View>
          </View>
        </View>
      </Modal>
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
    flexShrink: 1,
    fontSize: 18,
    fontWeight: '800',
    textAlign: 'center',
  },
  addButton: {
    alignItems: 'flex-end',
    minWidth: 70,
  },
  addText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '700',
  },
  listContainer: {
    flex: 1,
    paddingHorizontal: spacing.lg,
  },
  summary: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.md,
  },
  notice: {
    backgroundColor: colors.successSoft,
    borderColor: '#bbf7d0',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  noticeText: {
    color: colors.success,
    fontSize: 14,
  },
  list: {
    paddingBottom: spacing.xxl,
  },
  card: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    flexDirection: 'row',
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  cardInfo: {
    flex: 1,
  },
  cardTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
  },
  cardMeta: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  actions: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  actionButton: {
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  actionText: {
    color: colors.text,
    fontSize: 12,
    fontWeight: '600',
  },
  deleteButton: {
    borderColor: '#fecaca',
  },
  deleteText: {
    color: colors.danger,
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
  modalBackdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.5)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  modalCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    maxWidth: 480,
    width: '100%',
  },
  modalHeader: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  modalTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
  },
  modalClose: {
    padding: spacing.xs,
  },
  modalCloseText: {
    color: colors.muted,
    fontSize: 22,
    lineHeight: 22,
  },
  modalBody: {
    padding: spacing.lg,
  },
  buttonGap: {
    height: spacing.sm,
  },
});
