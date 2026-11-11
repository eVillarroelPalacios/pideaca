import { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ComboField, type ComboOption } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderFinancialProduct,
  ProviderRecipePayload,
  ProviderSupply,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type Row = {
  key: string;
  supplyId: number | null;
  quantity: string;
};

type Props = {
  visible: boolean;
  product: ProviderFinancialProduct;
  supplies: ProviderSupply[];
  onClose: () => void;
  onSaved: () => void;
};

function errorMessage(caught: unknown, fallback: string): string {
  if (caught instanceof ApiError) {
    const first = caught.errors ? Object.values(caught.errors)[0] : null;

    if (first && first.length > 0) {
      return first[0];
    }

    return caught.message;
  }

  return fallback;
}

function parseQuantity(raw: string): number | null {
  const normalized = raw.trim().replace(',', '.');

  if (normalized === '') {
    return null;
  }

  const value = Number(normalized);

  return Number.isFinite(value) ? value : Number.NaN;
}

let rowCounter = 0;

function newRow(supplyId: number | null = null, quantity = ''): Row {
  rowCounter += 1;

  return { key: `row-${rowCounter}`, supplyId, quantity };
}

/**
 * Modal "Ficha tecnica" de un producto.
 *
 * Espeja el modal de la web: carga la ficha al abrir, permite agregar filas de
 * insumo con su cantidad, calcular el costo estimado en cliente y guardar el
 * reemplazo completo de la ficha.
 */
export function FinancialRecipeModal({
  visible,
  product,
  supplies,
  onClose,
  onSaved,
}: Props) {
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [dirty, setDirty] = useState(false);

  useEffect(() => {
    let mounted = true;

    void (async () => {
      try {
        const response = await api.providerFinancialRecipe(product.product_id);

        if (!mounted) {
          return;
        }

        setRows(
          response.recipe.items.map((item) =>
            newRow(item.supply_id, String(item.quantity_required)),
          ),
        );
      } catch (caught) {
        if (mounted) {
          setError(errorMessage(caught, 'No se pudo cargar la ficha técnica.'));
        }
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    })();

    return () => {
      mounted = false;
    };
  }, [product.product_id]);

  const supplyMap = useMemo(
    () => new Map(supplies.map((supply) => [supply.id, supply])),
    [supplies],
  );

  const supplyOptions = useMemo<ComboOption<number>[]>(
    () =>
      supplies.map((supply) => ({
        value: supply.id,
        label: supply.name,
        hint: `${money(supply.cost_per_unit)} / ${
          supply.unit_of_measure?.symbol ?? 'unidad'
        }`,
      })),
    [supplies],
  );

  const estimate = useMemo(() => {
    let total = 0;
    let missing = false;

    rows.forEach((row) => {
      const supply = row.supplyId === null ? null : supplyMap.get(row.supplyId);
      const quantity = parseQuantity(row.quantity);

      if (!supply || quantity === null || Number.isNaN(quantity)) {
        missing = true;
        return;
      }

      total += quantity * supply.cost_per_unit;
    });

    return { total: Math.round(total * 100) / 100, missing };
  }, [rows, supplyMap]);

  function updateRow(key: string, patch: Partial<Omit<Row, 'key'>>) {
    setRows((current) =>
      current.map((row) => (row.key === key ? { ...row, ...patch } : row)),
    );
    setDirty(true);
    setNotice(null);
    setError(null);
  }

  function addRow() {
    if (supplies.length === 0) {
      setNotice('Cargá al menos un insumo en la sección Insumos para elegirlo acá.');
      return;
    }

    setRows((current) => [...current, newRow()]);
    setDirty(true);
    setError(null);
  }

  function removeRow(key: string) {
    setRows((current) => current.filter((row) => row.key !== key));
    setDirty(true);
    setError(null);
  }

  async function save() {
    const items: ProviderRecipePayload['items'] = [];
    const used = new Set<number>();

    for (const row of rows) {
      if (row.supplyId === null) {
        continue;
      }

      const quantity = parseQuantity(row.quantity);

      if (quantity === null || Number.isNaN(quantity) || quantity <= 0) {
        setError('Poné la cantidad de cada insumo antes de guardar.');
        return;
      }

      if (used.has(row.supplyId)) {
        setError('Cada insumo puede aparecer una sola vez en la ficha.');
        return;
      }

      used.add(row.supplyId);
      items.push({ supply_id: row.supplyId, quantity_required: quantity });
    }

    if (items.length === 0 && rows.length > 0) {
      setError('Elegí un insumo en cada fila antes de guardar.');
      return;
    }

    setSaving(true);
    setError(null);
    setNotice(null);

    try {
      await api.providerFinancialSaveRecipe(product.product_id, { items });

      setDirty(false);
      onSaved();
      onClose();
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo guardar la ficha técnica.'));
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.backdrop}
      >
        <SafeAreaView style={styles.sheet} edges={['bottom']}>
          <View style={styles.header}>
            <Text style={styles.title} numberOfLines={2}>
              Ficha técnica · {product.name}
            </Text>

            <View style={styles.headerActions}>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Agregar insumo"
                onPress={addRow}
                style={({ pressed }) => [
                  styles.iconButton,
                  styles.outlineAccent,
                  pressed && styles.pressed,
                ]}
              >
                <Text style={styles.outlineAccentText}>+</Text>
              </Pressable>

              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Guardar ficha"
                accessibilityState={{ busy: saving }}
                disabled={saving || loading}
                onPress={() => void save()}
                style={({ pressed }) => [
                  styles.iconButton,
                  styles.filledAccent,
                  pressed && styles.pressed,
                  (saving || loading) && styles.disabled,
                ]}
              >
                <Text style={styles.filledAccentText}>✓</Text>
              </Pressable>

              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Cerrar"
                onPress={onClose}
                style={({ pressed }) => [
                  styles.iconButton,
                  styles.outlineGray,
                  pressed && styles.pressed,
                ]}
              >
                <Text style={styles.outlineGrayText}>✕</Text>
              </Pressable>
            </View>
          </View>

          <ScrollView
            style={styles.body}
            contentContainerStyle={styles.bodyContent}
            keyboardShouldPersistTaps="handled"
          >
            <ErrorBanner message={error} />

            {notice ? (
              <View style={styles.notice}>
                <Text style={styles.noticeText}>{notice}</Text>
              </View>
            ) : null}

            {loading ? (
              <View style={styles.loading}>
                <ActivityIndicator color={colors.brand} />
                <Text style={styles.loadingText}>Cargando ficha…</Text>
              </View>
            ) : rows.length === 0 ? (
              <Text style={styles.emptyText}>
                Este producto todavía no tiene ficha técnica. Agregá los insumos que
                necesita para calcular su costo.
              </Text>
            ) : (
              rows.map((row, index) => {
                const supply =
                  row.supplyId === null ? null : supplyMap.get(row.supplyId);
                const quantity = parseQuantity(row.quantity);
                const lineCost =
                  supply && quantity !== null && !Number.isNaN(quantity)
                    ? Math.round(quantity * supply.cost_per_unit * 100) / 100
                    : null;

                return (
                  <View key={row.key} style={styles.rowCard}>
                    <View style={styles.rowHeader}>
                      <Text style={styles.rowTitle}>Insumo {index + 1}</Text>

                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Quitar insumo"
                        onPress={() => removeRow(row.key)}
                        style={({ pressed }) => [
                          styles.trashButton,
                          pressed && styles.pressed,
                        ]}
                      >
                        <Text style={styles.trashIcon}>🗑</Text>
                      </Pressable>
                    </View>

                    <ComboField<number>
                      label="Insumo"
                      title="Elegir insumo"
                      value={row.supplyId}
                      options={supplyOptions}
                      onChange={(value) => updateRow(row.key, { supplyId: value })}
                      placeholder={
                        supplies.length === 0 ? 'Cargá insumos primero' : 'Elegí un insumo'
                      }
                      disabled={supplies.length === 0}
                    />

                    <Text style={styles.fieldLabel}>Cantidad</Text>
                    <View style={styles.quantityRow}>
                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Restar cantidad"
                        onPress={() => {
                          const current = parseQuantity(row.quantity);
                          const base = current === null || Number.isNaN(current) ? 0 : current;
                          const next = Math.max(0, Math.round((base - 1) * 1000) / 1000);
                          updateRow(row.key, { quantity: String(next) });
                        }}
                        style={({ pressed }) => [
                          styles.stepButton,
                          pressed && styles.pressed,
                        ]}
                      >
                        <Text style={styles.stepText}>−</Text>
                      </Pressable>

                      <View style={styles.quantityInput}>
                        <TextInput
                          value={row.quantity}
                          onChangeText={(value) =>
                            updateRow(row.key, { quantity: value })
                          }
                          placeholder="Cantidad"
                          placeholderTextColor={colors.muted}
                          keyboardType="decimal-pad"
                          style={styles.quantityTextInput}
                        />
                      </View>

                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Sumar cantidad"
                        onPress={() => {
                          const current = parseQuantity(row.quantity);
                          const base = current === null || Number.isNaN(current) ? 0 : current;
                          updateRow(row.key, {
                            quantity: String(Math.round((base + 1) * 1000) / 1000),
                          });
                        }}
                        style={({ pressed }) => [
                          styles.stepButton,
                          pressed && styles.pressed,
                        ]}
                      >
                        <Text style={styles.stepText}>+</Text>
                      </Pressable>
                    </View>

                    <Text style={styles.lineCost}>
                      {lineCost === null
                        ? 'Completá insumo y cantidad'
                        : `Costo de la línea: ${money(lineCost)}`}
                      {supply?.unit_of_measure
                        ? ` · ${supply.unit_of_measure.symbol}`
                        : ''}
                    </Text>
                  </View>
                );
              })
            )}

            <View style={styles.totalBox}>
              <Text style={styles.totalText}>
                Costo estimado: {money(estimate.total)}
                {estimate.missing ? ' · completá las cantidades' : ''}
              </Text>
              {dirty ? (
                <Text style={styles.dirtyText}>· cambios sin guardar</Text>
              ) : null}
            </View>
          </ScrollView>
        </SafeAreaView>
      </KeyboardAvoidingView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    backgroundColor: 'rgba(15, 23, 42, 0.55)',
    flex: 1,
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: colors.surface,
    borderTopLeftRadius: radius.lg,
    borderTopRightRadius: radius.lg,
    maxHeight: '92%',
    overflow: 'hidden',
  },
  header: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    padding: spacing.lg,
  },
  title: {
    color: NAVY,
    flex: 1,
    fontSize: 16,
    fontWeight: '800',
  },
  headerActions: {
    flexDirection: 'row',
    gap: 6,
  },
  iconButton: {
    alignItems: 'center',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 36,
    justifyContent: 'center',
    width: 36,
  },
  outlineAccent: {
    backgroundColor: colors.surface,
    borderColor: ACCENT,
  },
  outlineAccentText: {
    color: ACCENT,
    fontSize: 19,
    fontWeight: '700',
    lineHeight: 21,
  },
  filledAccent: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  filledAccentText: {
    color: colors.surface,
    fontSize: 18,
    fontWeight: '800',
    lineHeight: 20,
  },
  outlineGray: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
  },
  outlineGrayText: {
    color: '#374151',
    fontSize: 16,
    fontWeight: '700',
    lineHeight: 18,
  },
  pressed: {
    opacity: 0.8,
  },
  disabled: {
    opacity: 0.55,
  },
  body: {
    flexGrow: 0,
  },
  bodyContent: {
    padding: spacing.lg,
  },
  notice: {
    backgroundColor: '#fffbeb',
    borderColor: '#fde68a',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  noticeText: {
    color: '#92400e',
    fontSize: 12,
    lineHeight: 18,
  },
  loading: {
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.xl,
  },
  loadingText: {
    color: colors.muted,
    fontSize: 13,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 19,
    marginBottom: spacing.md,
  },
  rowCard: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  rowHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  rowTitle: {
    color: NAVY,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  trashButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#fecaca',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  trashIcon: {
    fontSize: 14,
  },
  fieldLabel: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
    marginBottom: spacing.sm,
  },
  quantityRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
  },
  stepButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 44,
    justifyContent: 'center',
    width: 44,
  },
  stepText: {
    color: colors.text,
    fontSize: 20,
    fontWeight: '700',
    lineHeight: 22,
  },
  quantityInput: {
    flex: 1,
  },
  quantityTextInput: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    color: colors.text,
    fontSize: 16,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  lineCost: {
    color: colors.muted,
    fontSize: 12,
    marginTop: spacing.sm,
  },
  totalBox: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    marginTop: spacing.xs,
    paddingTop: spacing.md,
  },
  totalText: {
    color: NAVY,
    fontSize: 13,
    fontWeight: '700',
  },
  dirtyText: {
    color: '#b45309',
    fontSize: 12,
    fontWeight: '600',
    marginTop: 2,
  },
});
