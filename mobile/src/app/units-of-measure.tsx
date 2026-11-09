import { router, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import {
  Alert,
  FlatList,
  Modal,
  Pressable,
  RefreshControl,
  StyleSheet,
  Switch,
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
import type { UnitOfMeasureItem } from '@/types/api';

type FieldErrors = {
  name?: string;
  symbol?: string;
  base_conversion_factor?: string;
};

export default function UnitsOfMeasureScreen() {
  const [items, setItems] = useState<UnitOfMeasureItem[]>([]);
  const [inUse, setInUse] = useState(0);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  // Modal de alta/edicion.
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<UnitOfMeasureItem | null>(null);
  const [name, setName] = useState('');
  const [symbol, setSymbol] = useState('');
  const [factor, setFactor] = useState('1');
  const [integerOnly, setIntegerOnly] = useState(true);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [saving, setSaving] = useState(false);

  const fetchUnits = useCallback(async () => {
    try {
      const response = await api.unitsOfMeasure();

      setItems(response.data);
      setInUse(response.summary.in_use);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar las unidades.',
      );
    }
  }, []);

  // Se recarga al entrar y al volver de otra pantalla.
  useFocusEffect(
    useCallback(() => {
      let active = true;

      setLoading(true);
      void fetchUnits().finally(() => {
        if (active) {
          setLoading(false);
        }
      });

      return () => {
        active = false;
      };
    }, [fetchUnits]),
  );

  async function handleRefresh() {
    setRefreshing(true);
    await fetchUnits();
    setRefreshing(false);
  }

  function openCreate() {
    setEditing(null);
    setName('');
    setSymbol('');
    setFactor('1');
    setIntegerOnly(true);
    setFieldErrors({});
    setNotice(null);
    setModalOpen(true);
  }

  function openEdit(item: UnitOfMeasureItem) {
    setEditing(item);
    setName(item.name);
    setSymbol(item.symbol);
    setFactor(String(item.base_conversion_factor));
    setIntegerOnly(item.is_integer_only);
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
    setFieldErrors({});
  }

  async function handleSave() {
    const nameValue = name.trim();
    const symbolValue = symbol.trim();
    const factorValue = Number(String(factor).replace(',', '.'));

    if (!nameValue) {
      setFieldErrors({ name: 'Ingresá el nombre de la unidad.' });
      return;
    }

    if (!symbolValue) {
      setFieldErrors({ symbol: 'Ingresá el símbolo.' });
      return;
    }

    if (!Number.isFinite(factorValue) || factorValue <= 0) {
      setFieldErrors({ base_conversion_factor: 'La equivalencia debe ser un número mayor a cero.' });
      return;
    }

    setSaving(true);
    setFieldErrors({});

    const payload = {
      name: nameValue,
      symbol: symbolValue,
      base_conversion_factor: factorValue,
      is_integer_only: integerOnly,
    };

    try {
      const response = editing
        ? await api.updateUnitOfMeasure(editing.id, payload)
        : await api.createUnitOfMeasure(payload);

      setModalOpen(false);
      setEditing(null);
      setNotice(response.message);
      await fetchUnits();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setFieldErrors({
          name: caught.errors?.name?.[0],
          symbol: caught.errors?.symbol?.[0],
          base_conversion_factor: caught.errors?.base_conversion_factor?.[0],
        });
        setFieldErrors((current) =>
          current.name || current.symbol || current.base_conversion_factor
            ? current
            : { name: caught.message },
        );
      } else {
        setFieldErrors({ name: 'No pudimos guardar la unidad.' });
      }
    } finally {
      setSaving(false);
    }
  }

  function confirmDelete(item: UnitOfMeasureItem) {
    if (!item.can_delete) {
      Alert.alert(
        'No se puede eliminar',
        'No se puede eliminar la unidad porque la usan productos, insumos o movimientos de stock.',
      );
      return;
    }

    Alert.alert(
      'Eliminar unidad',
      `¿Estás seguro de eliminar "${item.name}"?`,
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

  async function remove(item: UnitOfMeasureItem) {
    setNotice(null);

    try {
      const response = await api.deleteUnitOfMeasure(item.id);
      setNotice(response.message);
      await fetchUnits();
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
    ? items.filter(
        (item) =>
          item.name.toLowerCase().includes(term) ||
          item.symbol.toLowerCase().includes(term),
      )
    : items;

  if (loading && items.length === 0) {
    return <LoadingScreen message="Cargando unidades…" />;
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
        <Text style={styles.title}>Unidades</Text>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Nueva unidad"
          onPress={openCreate}
          style={styles.addButton}
        >
          <Text style={styles.addText}>＋ Nueva</Text>
        </Pressable>
      </View>

      <View style={styles.listContainer}>
        <Text style={styles.summary}>
          {items.length} unidades · {inUse} en uso
        </Text>
        <Text style={styles.cardSub}>Unidades globales para productos, insumos y stock.</Text>

        <ErrorBanner message={error} />

        {notice ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>{notice}</Text>
          </View>
        ) : null}

        <TextField
          label="Buscar unidad"
          placeholder="Nombre o símbolo…"
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
                {term ? 'No se encontraron unidades' : 'Todavía no hay unidades'}
              </Text>
              <Text style={styles.emptyText}>
                {term
                  ? 'Probá con otro nombre.'
                  : 'Creá la primera unidad con el botón Nueva.'}
              </Text>
            </View>
          }
          renderItem={({ item }) => (
            <View style={styles.card}>
              <View style={styles.cardInfo}>
                <Text style={styles.cardTitle}>
                  {item.name} · {item.symbol}
                </Text>
                <Text style={styles.cardMeta}>
                  1 {item.name} = {item.base_conversion_factor} und
                </Text>
                <View style={styles.metaRow}>
                  <View style={[styles.badge, item.is_integer_only ? styles.integerBadge : styles.decimalBadge]}>
                    <Text style={[styles.badgeText, item.is_integer_only ? styles.integerText : styles.decimalText]}>
                      {item.is_integer_only ? 'Solo enteras' : 'Admite decimales'}
                    </Text>
                  </View>
                  <Text style={styles.cardMeta}>
                    {item.products_count === 1 ? '1 producto' : `${item.products_count} productos`}
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
                {editing ? 'Editar unidad' : 'Nueva unidad'}
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
                label="Unidad"
                placeholder="Ej: Kilogramo"
                value={name}
                onChangeText={setName}
                maxLength={255}
                autoFocus
                error={fieldErrors.name}
              />

              <TextField
                label="Símbolo"
                placeholder="Ej: kg"
                value={symbol}
                onChangeText={setSymbol}
                maxLength={20}
                autoCapitalize="none"
                autoCorrect={false}
                error={fieldErrors.symbol}
              />

              <TextField
                label="Equivalencia en und."
                placeholder="1"
                value={factor}
                onChangeText={setFactor}
                keyboardType="decimal-pad"
                error={fieldErrors.base_conversion_factor}
              />

              <View style={styles.switchRow}>
                <Text style={styles.switchLabel}>Solo admite cantidades enteras</Text>
                <Switch value={integerOnly} onValueChange={setIntegerOnly} />
              </View>

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
    marginBottom: spacing.xs,
  },
  cardSub: {
    color: colors.muted,
    fontSize: 12,
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
    marginTop: spacing.xs,
  },
  metaRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.xs,
  },
  badge: {
    borderRadius: radius.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  integerBadge: {
    backgroundColor: colors.brandSoft,
  },
  integerText: {
    color: '#9a3412',
  },
  decimalBadge: {
    backgroundColor: colors.successSoft,
  },
  decimalText: {
    color: colors.success,
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
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  switchLabel: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  buttonGap: {
    height: spacing.sm,
  },
});
