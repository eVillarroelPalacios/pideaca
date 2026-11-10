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
import { ComboField } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';
import type { SubGroupItem, SubGroupOption } from '@/types/api';

type FieldErrors = {
  description?: string;
  group_id?: string;
};

export default function SubGroupsScreen() {
  const [items, setItems] = useState<SubGroupItem[]>([]);
  const [groupOptions, setGroupOptions] = useState<SubGroupOption[]>([]);
  const [inUse, setInUse] = useState(0);
  const [search, setSearch] = useState('');
  const [groupFilter, setGroupFilter] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  // Modal de alta/edicion.
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<SubGroupItem | null>(null);
  const [description, setDescription] = useState('');
  const [groupId, setGroupId] = useState<number | null>(null);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [saving, setSaving] = useState(false);

  const fetchSubgroups = useCallback(async () => {
    try {
      const response = await api.subgroups();

      setItems(response.data);
      setGroupOptions(response.filters.groups);
      setInUse(response.summary.in_use);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar los sub grupos.',
      );
    }
  }, []);

  // Se recarga al entrar y al volver de otra pantalla.
  useFocusEffect(
    useCallback(() => {
      let active = true;

      setLoading(true);
      void fetchSubgroups().finally(() => {
        if (active) {
          setLoading(false);
        }
      });

      return () => {
        active = false;
      };
    }, [fetchSubgroups]),
  );

  async function handleRefresh() {
    setRefreshing(true);
    await fetchSubgroups();
    setRefreshing(false);
  }

  function openCreate() {
    setEditing(null);
    setDescription('');
    setGroupId(groupFilter);
    setFieldErrors({});
    setNotice(null);
    setModalOpen(true);
  }

  function openEdit(item: SubGroupItem) {
    setEditing(item);
    setDescription(item.description);
    setGroupId(item.group_id);
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
    setGroupId(null);
    setFieldErrors({});
  }

  async function handleSave() {
    const value = description.trim();

    if (!value) {
      setFieldErrors({ description: 'Ingresá la descripción del sub grupo.' });
      return;
    }

    if (!groupId) {
      setFieldErrors({ group_id: 'Seleccioná un grupo.' });
      return;
    }

    setSaving(true);
    setFieldErrors({});

    const payload = {
      description: value,
      group_id: groupId,
    };

    try {
      const response = editing
        ? await api.updateSubgroup(editing.id, payload)
        : await api.createSubgroup(payload);

      setModalOpen(false);
      setEditing(null);
      setDescription('');
      setGroupId(null);
      setNotice(response.message);
      await fetchSubgroups();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setFieldErrors({
          description: caught.errors?.description?.[0],
          group_id: caught.errors?.group_id?.[0],
        });
        setFieldErrors((current) =>
          current.description || current.group_id
            ? current
            : { description: caught.message },
        );
      } else {
        setFieldErrors({ description: 'No pudimos guardar el sub grupo.' });
      }
    } finally {
      setSaving(false);
    }
  }

  function confirmDelete(item: SubGroupItem) {
    if (!item.can_delete) {
      Alert.alert(
        'No se puede eliminar',
        'No se puede eliminar el sub grupo porque tiene prestadores u órdenes asociadas.',
      );
      return;
    }

    Alert.alert(
      'Eliminar sub grupo',
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

  async function remove(item: SubGroupItem) {
    setNotice(null);

    try {
      const response = await api.deleteSubgroup(item.id);
      setNotice(response.message);
      await fetchSubgroups();
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
  const visible = items.filter((item) => {
    const matchesText = !term || item.description.toLowerCase().includes(term);
    const matchesGroup = !groupFilter || item.group_id === groupFilter;
    return matchesText && matchesGroup;
  });

  if (loading && items.length === 0) {
    return <LoadingScreen message="Cargando sub grupos…" />;
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
        <Text style={styles.title}>Sub grupos</Text>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Nuevo sub grupo"
          onPress={openCreate}
          style={styles.addButton}
        >
          <Text style={styles.addText}>＋ Nuevo</Text>
        </Pressable>
      </View>

      <View style={styles.listContainer}>
        <Text style={styles.summary}>
          {items.length} sub grupos · {inUse} en uso
        </Text>

        <ErrorBanner message={error} />

        {notice ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>{notice}</Text>
          </View>
        ) : null}

        <TextField
          label="Buscar sub grupo"
          placeholder="Nombre del sub grupo…"
          value={search}
          onChangeText={setSearch}
          autoCapitalize="none"
          autoCorrect={false}
        />

        <ComboField
          label="Filtrar por grupo"
          value={groupFilter}
          options={groupOptions.map((option) => ({
            value: option.id,
            label: option.description,
          }))}
          onChange={setGroupFilter}
          allowEmpty
          emptyLabel="Todos"
          onClear={() => setGroupFilter(null)}
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
                {term || groupFilter ? 'No se encontraron sub grupos' : 'Todavía no hay sub grupos'}
              </Text>
              <Text style={styles.emptyText}>
                {term || groupFilter
                  ? 'Probá con otro filtro.'
                  : 'Creá el primer sub grupo con el botón Nuevo.'}
              </Text>
            </View>
          }
          renderItem={({ item }) => (
            <View style={styles.card}>
              <View style={styles.cardInfo}>
                <Text style={styles.cardTitle}>{item.description}</Text>
                <View style={styles.metaRow}>
                  <View style={styles.badge}>
                    <Text style={styles.badgeText}>
                      {item.group?.description ?? 'Sin grupo'}
                    </Text>
                  </View>
                  <Text style={styles.cardMeta}>
                    {item.providers_count === 1 ? '1 prestador' : `${item.providers_count} prestadores`}
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
                {editing ? 'Editar sub grupo' : 'Nuevo sub grupo'}
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
                placeholder="Nombre del sub grupo"
                value={description}
                onChangeText={setDescription}
                maxLength={255}
                autoFocus
                error={fieldErrors.description}
              />

              <ComboField
                label="Grupo"
                value={groupId}
                options={groupOptions.map((option) => ({
                  value: option.id,
                  label: option.description,
                }))}
                onChange={setGroupId}
                placeholder={
                  groupOptions.length === 0
                    ? 'No hay grupos disponibles'
                    : 'Seleccioná un grupo'
                }
                disabled={groupOptions.length === 0}
                error={fieldErrors.group_id}
              />

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
    backgroundColor: colors.brandSoft,
    borderRadius: radius.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  badgeText: {
    color: colors.brandDark,
    fontSize: 11,
    fontWeight: '700',
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
    marginBottom: spacing.md,
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
  hint: {
    color: colors.muted,
    fontSize: 11,
    marginTop: spacing.xs,
  },
  buttonGap: {
    height: spacing.sm,
  },
});
