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
import type { GroupItem, GroupStatusOption } from '@/types/api';

type FieldErrors = {
  description?: string;
  group_status_id?: string;
};

export default function GroupsScreen() {
  const [items, setItems] = useState<GroupItem[]>([]);
  const [statusOptions, setStatusOptions] = useState<GroupStatusOption[]>([]);
  const [defaultStatusId, setDefaultStatusId] = useState<number | null>(null);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  // Modal de alta/edicion.
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<GroupItem | null>(null);
  const [description, setDescription] = useState('');
  const [icon, setIcon] = useState('');
  const [statusId, setStatusId] = useState<number | null>(null);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [saving, setSaving] = useState(false);

  const fetchGroups = useCallback(async () => {
    try {
      const response = await api.groups();

      setItems(response.data);
      setStatusOptions(response.filters.group_statuses);
      setDefaultStatusId(response.filters.default_group_status_id);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar los grupos.',
      );
    }
  }, []);

  // Se recarga al entrar y al volver de otra pantalla.
  useFocusEffect(
    useCallback(() => {
      let active = true;

      setLoading(true);
      void fetchGroups().finally(() => {
        if (active) {
          setLoading(false);
        }
      });

      return () => {
        active = false;
      };
    }, [fetchGroups]),
  );

  async function handleRefresh() {
    setRefreshing(true);
    await fetchGroups();
    setRefreshing(false);
  }

  function initialStatusId(): number | null {
    return defaultStatusId ?? statusOptions[0]?.id ?? null;
  }

  function openCreate() {
    setEditing(null);
    setDescription('');
    setIcon('');
    setStatusId(initialStatusId());
    setFieldErrors({});
    setNotice(null);
    setModalOpen(true);
  }

  function openEdit(item: GroupItem) {
    setEditing(item);
    setDescription(item.description);
    setIcon(item.icon ?? '');
    setStatusId(item.group_status_id ?? initialStatusId());
    setFieldErrors({});
    setNotice(null);
    setModalOpen(true);
  }

  function closeModal() {
    if (saving) {
      return;
    }

    setModalOpen(false);
    setEditing(null);
    setDescription('');
    setIcon('');
    setStatusId(null);
    setFieldErrors({});
  }

  async function handleSave() {
    const value = description.trim();

    if (!value) {
      setFieldErrors({ description: 'Ingresá el nombre del grupo.' });
      return;
    }

    setSaving(true);
    setFieldErrors({});

    const payload = {
      description: value,
      icon: icon.trim(),
      group_status_id: statusId,
    };

    try {
      const response = editing
        ? await api.updateGroup(editing.id, payload)
        : await api.createGroup(payload);

      setModalOpen(false);
      setEditing(null);
      setDescription('');
      setIcon('');
      setStatusId(null);
      setNotice(response.message);
      await fetchGroups();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setFieldErrors({
          description: caught.errors?.description?.[0],
          group_status_id: caught.errors?.group_status_id?.[0],
        });
        setFieldErrors((current) =>
          current.description || current.group_status_id
            ? current
            : { description: caught.message },
        );
      } else {
        setFieldErrors({ description: 'No pudimos guardar el grupo.' });
      }
    } finally {
      setSaving(false);
    }
  }

  function confirmDelete(item: GroupItem) {
    if (!item.can_delete) {
      Alert.alert(
        'No se puede eliminar',
        'No se puede eliminar el grupo porque tiene sub grupos asociados.',
      );
      return;
    }

    Alert.alert(
      'Eliminar grupo',
      `¿Estás seguro de eliminar "${item.description}"?`,
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

  async function remove(item: GroupItem) {
    setNotice(null);

    try {
      const response = await api.deleteGroup(item.id);
      setNotice(response.message);
      await fetchGroups();
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
    ? items.filter((item) => item.description.toLowerCase().includes(term))
    : items;
  const withSubgroups = items.filter((item) => item.subgroups_count > 0).length;

  if (loading && items.length === 0) {
    return <LoadingScreen message="Cargando grupos…" />;
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
        <Text style={styles.title}>Grupos</Text>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Nuevo grupo"
          onPress={openCreate}
          style={styles.addButton}
        >
          <Text style={styles.addText}>＋ Nuevo</Text>
        </Pressable>
      </View>

      <View style={styles.listContainer}>
        <Text style={styles.summary}>
          {items.length} grupos · {withSubgroups} con sub grupos
        </Text>

        <ErrorBanner message={error} />

        {notice ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>{notice}</Text>
          </View>
        ) : null}

        <TextField
          label="Buscar grupo"
          placeholder="Nombre del grupo…"
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
                {term ? 'No se encontraron grupos' : 'Todavía no hay grupos'}
              </Text>
              <Text style={styles.emptyText}>
                {term
                  ? 'Probá con otro nombre.'
                  : 'Creá el primer grupo con el botón Nuevo.'}
              </Text>
            </View>
          }
          renderItem={({ item }) => (
            <View style={styles.card}>
              <View style={styles.cardInfo}>
                <Text style={styles.cardTitle}>{item.description}</Text>
                <View style={styles.metaRow}>
                  <View style={[styles.badge, item.group_status ? styles.badgeActive : styles.badgeMuted]}>
                    <Text style={[styles.badgeText, item.group_status ? styles.badgeTextActive : styles.badgeTextMuted]}>
                      {item.group_status?.description ?? 'Sin estado'}
                    </Text>
                  </View>
                  <Text style={styles.cardMeta}>
                    {item.subgroups_count === 1
                      ? '1 sub grupo'
                      : `${item.subgroups_count} sub grupos`}
                    {item.icon ? ' · con icono' : ''}
                  </Text>
                </View>
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
                {editing ? 'Editar grupo' : 'Nuevo grupo'}
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
                label="Descripción"
                placeholder="Nombre del grupo"
                value={description}
                onChangeText={setDescription}
                maxLength={255}
                autoFocus
                error={fieldErrors.description}
              />

              <Text style={styles.selectorLabel}>Estado</Text>
              <View style={styles.chips}>
                {statusOptions.map((option) => {
                  const selected = statusId === option.id;

                  return (
                    <Pressable
                      key={option.id}
                      accessibilityRole="button"
                      accessibilityState={{ selected }}
                      onPress={() => setStatusId(option.id)}
                      style={[styles.chip, selected && styles.chipSelected]}
                    >
                      <Text style={[styles.chipText, selected && styles.chipTextSelected]}>
                        {option.description}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>
              {statusOptions.length === 0 ? (
                <Text style={styles.hint}>No hay estados disponibles.</Text>
              ) : null}
              {fieldErrors.group_status_id ? (
                <Text style={styles.fieldError}>{fieldErrors.group_status_id}</Text>
              ) : null}

              <TextField
                label="Icono (SVG)"
                placeholder='<svg xmlns="http://www.w3.org/2000/svg" …></svg>'
                value={icon}
                onChangeText={setIcon}
                multiline
                numberOfLines={4}
                style={styles.iconInput}
              />
              <Text style={styles.hint}>
                Pegá el código SVG del icono. Si está vacío, no se muestra icono.
              </Text>

              <View style={styles.buttonGap} />
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
  metaRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  cardMeta: {
    color: colors.muted,
    fontSize: 12,
  },
  badge: {
    borderRadius: radius.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  badgeActive: {
    backgroundColor: colors.successSoft,
  },
  badgeMuted: {
    backgroundColor: colors.border,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  badgeTextActive: {
    color: colors.success,
  },
  badgeTextMuted: {
    color: colors.muted,
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
  selectorLabel: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: spacing.xs,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
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
  fieldError: {
    color: colors.danger,
    fontSize: 12,
    marginTop: spacing.xs,
  },
  iconInput: {
    fontFamily: 'monospace',
    height: 96,
    textAlignVertical: 'top',
  },
  hint: {
    color: colors.muted,
    fontSize: 11,
    marginTop: spacing.xs,
  },
  buttonGap: {
    height: spacing.sm,
  },
});
