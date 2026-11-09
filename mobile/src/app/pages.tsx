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
import type { PageItem, PageModuleOption } from '@/types/api';

type FieldErrors = {
  description?: string;
  url?: string;
  module_id?: string;
};

export default function PagesScreen() {
  const [items, setItems] = useState<PageItem[]>([]);
  const [moduleOptions, setModuleOptions] = useState<PageModuleOption[]>([]);
  const [inUse, setInUse] = useState(0);
  const [search, setSearch] = useState('');
  const [moduleFilter, setModuleFilter] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  // Modal de alta/edicion.
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<PageItem | null>(null);
  const [description, setDescription] = useState('');
  const [url, setUrl] = useState('');
  const [moduleId, setModuleId] = useState<number | null>(null);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [saving, setSaving] = useState(false);

  const fetchPages = useCallback(async () => {
    try {
      const response = await api.pages();

      setItems(response.data);
      setModuleOptions(response.filters.modules);
      setInUse(response.summary.in_use);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar las páginas.',
      );
    }
  }, []);

  // Se recarga al entrar y al volver de otra pantalla.
  useFocusEffect(
    useCallback(() => {
      let active = true;

      setLoading(true);
      void fetchPages().finally(() => {
        if (active) {
          setLoading(false);
        }
      });

      return () => {
        active = false;
      };
    }, [fetchPages]),
  );

  async function handleRefresh() {
    setRefreshing(true);
    await fetchPages();
    setRefreshing(false);
  }

  function openCreate() {
    setEditing(null);
    setDescription('');
    setUrl('');
    setModuleId(moduleFilter);
    setFieldErrors({});
    setNotice(null);
    setModalOpen(true);
  }

  function openEdit(item: PageItem) {
    setEditing(item);
    setDescription(item.description);
    setUrl(item.url ?? '');
    setModuleId(item.module_id);
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
    setUrl('');
    setModuleId(null);
    setFieldErrors({});
  }

  async function handleSave() {
    const value = description.trim();

    if (!value) {
      setFieldErrors({ description: 'Ingresá la descripción de la página.' });
      return;
    }

    if (!moduleId) {
      setFieldErrors({ module_id: 'Seleccioná un módulo.' });
      return;
    }

    setSaving(true);
    setFieldErrors({});

    const payload = {
      description: value,
      url: url.trim() ? url.trim() : null,
      module_id: moduleId,
    };

    try {
      const response = editing
        ? await api.updatePage(editing.id, payload)
        : await api.createPage(payload);

      setModalOpen(false);
      setEditing(null);
      setDescription('');
      setUrl('');
      setModuleId(null);
      setNotice(response.message);
      await fetchPages();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setFieldErrors({
          description: caught.errors?.description?.[0],
          url: caught.errors?.url?.[0],
          module_id: caught.errors?.module_id?.[0],
        });
        setFieldErrors((current) =>
          current.description || current.url || current.module_id
            ? current
            : { description: caught.message },
        );
      } else {
        setFieldErrors({ description: 'No pudimos guardar la página.' });
      }
    } finally {
      setSaving(false);
    }
  }

  function confirmDelete(item: PageItem) {
    if (!item.can_delete) {
      Alert.alert(
        'No se puede eliminar',
        'No se puede eliminar la página porque tiene usuarios asignados.',
      );
      return;
    }

    Alert.alert(
      'Eliminar página',
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

  async function remove(item: PageItem) {
    setNotice(null);

    try {
      const response = await api.deletePage(item.id);
      setNotice(response.message);
      await fetchPages();
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
    const matchesModule = !moduleFilter || item.module_id === moduleFilter;
    return matchesText && matchesModule;
  });

  if (loading && items.length === 0) {
    return <LoadingScreen message="Cargando páginas…" />;
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
        <Text style={styles.title}>Páginas</Text>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Nueva página"
          onPress={openCreate}
          style={styles.addButton}
        >
          <Text style={styles.addText}>＋ Nueva</Text>
        </Pressable>
      </View>

      <View style={styles.listContainer}>
        <Text style={styles.summary}>
          {items.length} páginas · {inUse} en uso
        </Text>

        <ErrorBanner message={error} />

        {notice ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>{notice}</Text>
          </View>
        ) : null}

        <TextField
          label="Buscar página"
          placeholder="Descripción de la página…"
          value={search}
          onChangeText={setSearch}
          autoCapitalize="none"
          autoCorrect={false}
        />

        <Text style={styles.selectorLabel}>Filtrar por módulo</Text>
        <View style={styles.chips}>
          <Pressable
            accessibilityRole="button"
            accessibilityState={{ selected: moduleFilter === null }}
            onPress={() => setModuleFilter(null)}
            style={[styles.chip, moduleFilter === null && styles.chipSelected]}
          >
            <Text style={[styles.chipText, moduleFilter === null && styles.chipTextSelected]}>
              Todos
            </Text>
          </Pressable>
          {moduleOptions.map((option) => {
            const selected = moduleFilter === option.id;
            return (
              <Pressable
                key={option.id}
                accessibilityRole="button"
                accessibilityState={{ selected }}
                onPress={() => setModuleFilter(selected ? null : option.id)}
                style={[styles.chip, selected && styles.chipSelected]}
              >
                <Text style={[styles.chipText, selected && styles.chipTextSelected]}>
                  {option.description}
                </Text>
              </Pressable>
            );
          })}
        </View>

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
                {term || moduleFilter ? 'No se encontraron páginas' : 'Todavía no hay páginas'}
              </Text>
              <Text style={styles.emptyText}>
                {term || moduleFilter
                  ? 'Probá con otro filtro.'
                  : 'Creá la primera página con el botón Nueva.'}
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
                      {item.module?.description ?? 'Sin módulo'}
                    </Text>
                  </View>
                  <Text style={styles.cardMeta}>
                    {item.users_count === 1 ? '1 usuario' : `${item.users_count} usuarios`}
                    {item.url ? ` · ${item.url}` : ''}
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
                {editing ? 'Editar página' : 'Nueva página'}
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
                placeholder="Descripción de la página"
                value={description}
                onChangeText={setDescription}
                maxLength={255}
                autoFocus
                error={fieldErrors.description}
              />

              <Text style={styles.selectorLabel}>Módulo</Text>
              <View style={styles.chips}>
                {moduleOptions.map((option) => {
                  const selected = moduleId === option.id;
                  return (
                    <Pressable
                      key={option.id}
                      accessibilityRole="button"
                      accessibilityState={{ selected }}
                      onPress={() => setModuleId(option.id)}
                      style={[styles.chip, selected && styles.chipSelected]}
                    >
                      <Text style={[styles.chipText, selected && styles.chipTextSelected]}>
                        {option.description}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>
              {moduleOptions.length === 0 ? (
                <Text style={styles.hint}>No hay módulos disponibles.</Text>
              ) : null}
              {fieldErrors.module_id ? (
                <Text style={styles.fieldError}>{fieldErrors.module_id}</Text>
              ) : null}

              <TextField
                label="URL"
                placeholder="url-de-la-pagina"
                value={url}
                onChangeText={setUrl}
                maxLength={255}
                autoCapitalize="none"
                autoCorrect={false}
                error={fieldErrors.url}
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
