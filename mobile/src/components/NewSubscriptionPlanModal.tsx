import { useMemo, useState } from 'react';
import {
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ComboField, type ComboOption } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { TextField } from '@/components/TextField';
import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderCatalogProduct,
  ProviderSubscriptionFrequency,
  ProviderSubscriptionPlan,
  ProviderSubscriptionPlanPayload,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

const FREQUENCY_OPTIONS: ComboOption<ProviderSubscriptionFrequency>[] = [
  { value: 'DAILY', label: 'Diaria' },
  { value: 'WEEKLY', label: 'Semanal' },
  { value: 'BIWEEKLY', label: 'Quincenal' },
  { value: 'MONTHLY', label: 'Mensual' },
];

const FREQUENCY_LABEL: Record<string, string> = {
  DAILY: 'Diaria',
  WEEKLY: 'Semanal',
  BIWEEKLY: 'Quincenal',
  MONTHLY: 'Mensual',
};

type Row = {
  key: string;
  productId: number | null;
  quantity: string;
};

type Props = {
  visible: boolean;
  plan: ProviderSubscriptionPlan | null;
  plans: ProviderSubscriptionPlan[];
  products: ProviderCatalogProduct[];
  onClose: () => void;
  onSaved: (message: string) => void;
  onEditPlan: (plan: ProviderSubscriptionPlan) => void;
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

/** Devuelve null cuando el campo quedó vacío y NaN cuando no es un número. */
function parseNumber(raw: string): number | null {
  const normalized = raw.trim().replace(',', '.');

  if (normalized === '') {
    return null;
  }

  const value = Number(normalized);

  return Number.isFinite(value) ? value : Number.NaN;
}

let rowCounter = 0;

function newRow(productId: number | null = null, quantity = '1'): Row {
  rowCounter += 1;

  return { key: `plan-row-${rowCounter}`, productId, quantity };
}

/**
 * Modal "Nuevo plan" / "Editar plan" de Suscripciones.
 *
 * Espeja el formulario del panel web: datos del plan, filas dinamicas de
 * productos con cantidad, resumen de la suma y listado de planes existentes
 * con su badge de visibilidad y acceso rapido a la edicion.
 */
export function NewSubscriptionPlanModal({
  visible,
  plan,
  plans,
  products,
  onClose,
  onSaved,
  onEditPlan,
}: Props) {
  const [title, setTitle] = useState(plan?.title ?? '');
  const [frequency, setFrequency] = useState<ProviderSubscriptionFrequency>(
    (plan?.frequency as ProviderSubscriptionFrequency) ?? 'WEEKLY',
  );
  const [price, setPrice] = useState(plan ? String(plan.price) : '');
  const [discount, setDiscount] = useState(
    plan && Number(plan.discount_percentage) > 0
      ? String(plan.discount_percentage)
      : '',
  );
  const [capacity, setCapacity] = useState(
    plan && plan.capacity ? String(plan.capacity) : '',
  );
  const [description, setDescription] = useState(plan?.description ?? '');
  const [active, setActive] = useState(plan?.is_active ?? true);
  const [rows, setRows] = useState<Row[]>(
    plan && plan.items.length > 0
      ? plan.items.map((item) => newRow(item.product_id, String(item.quantity)))
      : [newRow()],
  );
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const productOptions = useMemo<ComboOption<number>[]>(
    () =>
      products.map((product) => ({
        value: product.id,
        label: product.name,
        hint: money(product.price),
      })),
    [products],
  );

  const productMap = useMemo(
    () => new Map(products.map((product) => [product.id, product])),
    [products],
  );

  const estimate = useMemo(() => {
    let total = 0;
    let count = 0;

    rows.forEach((row) => {
      const product = row.productId === null ? null : productMap.get(row.productId);
      const quantity = parseNumber(row.quantity);

      if (!product || quantity === null || Number.isNaN(quantity) || quantity <= 0) {
        return;
      }

      total += quantity * product.price;
      count += 1;
    });

    return { total: Math.round(total * 100) / 100, count };
  }, [rows, productMap]);

  function updateRow(key: string, patch: Partial<Omit<Row, 'key'>>) {
    setRows((current) =>
      current.map((row) => (row.key === key ? { ...row, ...patch } : row)),
    );
    setError(null);
  }

  function addRow() {
    setRows((current) => [...current, newRow()]);
    setError(null);
  }

  function removeRow(key: string) {
    setRows((current) =>
      current.length === 1
        ? [newRow()]
        : current.filter((row) => row.key !== key),
    );
    setError(null);
  }

  function toggleActive() {
    setActive((current) => !current);
  }

  async function save() {
    const cleanTitle = title.trim();

    if (!cleanTitle) {
      setError('Escribí el nombre del plan.');
      return;
    }

    const priceValue = parseNumber(price);

    if (priceValue === null || Number.isNaN(priceValue) || priceValue < 0) {
      setError('Indicá un precio válido para el plan.');
      return;
    }

    const discountValue = parseNumber(discount);

    if (discountValue !== null && (Number.isNaN(discountValue) || discountValue < 0 || discountValue > 100)) {
      setError('El descuento debe estar entre 0 y 100.');
      return;
    }

    const capacityValue = parseNumber(capacity);

    if (
      capacityValue !== null &&
      (Number.isNaN(capacityValue) || capacityValue < 1 || !Number.isInteger(capacityValue))
    ) {
      setError('Los cupos deben ser un número entero mayor a cero.');
      return;
    }

    const items: ProviderSubscriptionPlanPayload['items'] = [];
    const used = new Set<number>();

    for (const row of rows) {
      if (row.productId === null) {
        continue;
      }

      const quantity = parseNumber(row.quantity);

      if (
        quantity === null ||
        Number.isNaN(quantity) ||
        quantity < 1 ||
        !Number.isInteger(quantity)
      ) {
        setError('Poné una cantidad entera mayor a cero en cada producto.');
        return;
      }

      if (used.has(row.productId)) {
        setError('No repitas el mismo producto dentro del plan.');
        return;
      }

      used.add(row.productId);
      items.push({ product_id: row.productId, quantity });
    }

    if (items.length === 0) {
      setError('Agregá al menos un producto al plan.');
      return;
    }

    setSaving(true);
    setError(null);

    try {
      const payload: ProviderSubscriptionPlanPayload = {
        title: cleanTitle,
        description: description.trim() === '' ? null : description.trim(),
        frequency,
        price: priceValue,
        discount_percentage: discountValue,
        capacity: capacityValue,
        is_active: active,
        items,
      };

      const response = plan
        ? await api.providerSubscriptionUpdatePlan(plan.id, payload)
        : await api.providerSubscriptionSavePlan(payload);

      onSaved(response.message || (plan ? 'Plan actualizado.' : 'Plan creado correctamente.'));
      onClose();
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo guardar el plan.'));
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
            <Text style={styles.title}>{plan ? 'Editar plan' : 'Nuevo plan'}</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Cerrar"
              onPress={onClose}
              style={({ pressed }) => [styles.headerClose, pressed && styles.pressed]}
            >
              <Text style={styles.headerCloseText}>✕</Text>
            </Pressable>
          </View>

          <ScrollView
            style={styles.body}
            contentContainerStyle={styles.bodyContent}
            keyboardShouldPersistTaps="handled"
          >
            <ErrorBanner message={error} />

            <TextField
              label="Nombre del plan"
              placeholder="Ej: Mega Ganga"
              value={title}
              onChangeText={(value) => {
                setTitle(value);
                setError(null);
              }}
              maxLength={150}
            />

            <ComboField<ProviderSubscriptionFrequency>
              label="Frecuencia"
              title="Frecuencia de entrega"
              value={frequency}
              options={FREQUENCY_OPTIONS}
              onChange={setFrequency}
            />

            <View style={styles.grid}>
              <View style={styles.gridItem}>
                <TextField
                  label="Precio"
                  placeholder="0,00"
                  value={price}
                  onChangeText={(value) => {
                    setPrice(value);
                    setError(null);
                  }}
                  keyboardType="decimal-pad"
                />
              </View>

              <View style={styles.gridItem}>
                <TextField
                  label="Descuento %"
                  placeholder="0"
                  value={discount}
                  onChangeText={(value) => {
                    setDiscount(value);
                    setError(null);
                  }}
                  keyboardType="decimal-pad"
                />
              </View>
            </View>

            <TextField
              label="Cupos (opcional)"
              placeholder="Ilimitado"
              value={capacity}
              onChangeText={(value) => {
                setCapacity(value);
                setError(null);
              }}
              keyboardType="number-pad"
            />

            <Pressable
              accessibilityRole="checkbox"
              accessibilityState={{ checked: active }}
              accessibilityLabel="Plan visible"
              onPress={toggleActive}
              style={({ pressed }) => [styles.checkRow, pressed && styles.pressed]}
            >
              <View style={[styles.checkbox, active && styles.checkboxActive]}>
                {active ? <Text style={styles.checkmark}>✓</Text> : null}
              </View>
              <Text style={styles.checkLabel}>Plan visible</Text>
            </Pressable>

            <TextField
              label="Descripción"
              placeholder="Qué incluye el plan"
              value={description}
              onChangeText={setDescription}
              multiline
              numberOfLines={3}
              maxLength={2000}
              style={styles.descriptionInput}
            />

            <View style={styles.sectionHead}>
              <Text style={styles.sectionTitle}>Productos del plan</Text>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Agregar producto"
                onPress={addRow}
                style={({ pressed }) => [
                  styles.addButton,
                  pressed && styles.pressed,
                ]}
              >
                <Text style={styles.addButtonText}>+</Text>
              </Pressable>
            </View>

            {products.length === 0 ? (
              <View style={styles.notice}>
                <Text style={styles.noticeText}>
                  No hay productos en el catálogo. Cargalos desde Mi Catálogo para
                  poder sumarlos al plan.
                </Text>
              </View>
            ) : null}

            {rows.map((row, index) => (
              <View key={row.key} style={styles.rowCard}>
                <View style={styles.rowHeader}>
                  <Text style={styles.rowTitle}>Producto {index + 1}</Text>

                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Quitar producto"
                    onPress={() => removeRow(row.key)}
                    style={({ pressed }) => [
                      styles.deleteButton,
                      pressed && styles.pressed,
                    ]}
                  >
                    <Text style={styles.deleteText}>✕</Text>
                  </Pressable>
                </View>

                <ComboField<number>
                  label="Producto"
                  title="Productos del catálogo"
                  value={row.productId}
                  options={productOptions}
                  onChange={(value) => updateRow(row.key, { productId: value })}
                  placeholder={
                    products.length === 0
                      ? 'Cargá productos primero'
                      : 'Elegí un producto'
                  }
                  disabled={products.length === 0}
                />

                <TextField
                  label="Cantidad"
                  placeholder="1"
                  value={row.quantity}
                  onChangeText={(value) => updateRow(row.key, { quantity: value })}
                  keyboardType="number-pad"
                />
              </View>
            ))}

            {plans.length > 0 ? (
              <View style={styles.existingBlock}>
                <Text style={styles.sectionTitle}>Planes existentes</Text>

                {plans.map((existing) => (
                  <View key={existing.id} style={styles.existingRow}>
                    <Text style={styles.existingText} numberOfLines={2}>
                      {existing.title} ·{' '}
                      {FREQUENCY_LABEL[existing.frequency] ?? existing.frequency} ·{' '}
                      {money(existing.price)} · {existing.charge_amount}/ciclo
                    </Text>

                    <View style={styles.existingActions}>
                      <View
                        style={[
                          styles.visibilityBadge,
                          existing.is_active
                            ? styles.visibilityVisible
                            : styles.visibilityHidden,
                        ]}
                      >
                        <Text
                          style={[
                            styles.visibilityText,
                            existing.is_active
                              ? styles.visibilityVisibleText
                              : styles.visibilityHiddenText,
                          ]}
                        >
                          {existing.is_active ? 'Visible' : 'Oculto'}
                        </Text>
                      </View>

                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel={`Editar ${existing.title}`}
                        onPress={() => onEditPlan(existing)}
                        style={({ pressed }) => [
                          styles.editButton,
                          pressed && styles.pressed,
                        ]}
                      >
                        <Text style={styles.editText}>✎</Text>
                      </Pressable>
                    </View>
                  </View>
                ))}
              </View>
            ) : null}
          </ScrollView>

          <View style={styles.footer}>
            <Text style={styles.estimate}>
              {estimate.count > 0
                ? `${estimate.count} producto(s) · suma ${money(estimate.total)}`
                : 'Agregá al menos un producto'}
            </Text>

            <View style={styles.footerActions}>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Guardar plan"
                accessibilityState={{ busy: saving }}
                disabled={saving}
                onPress={() => void save()}
                style={({ pressed }) => [
                  styles.iconButton,
                  styles.saveButton,
                  pressed && styles.pressed,
                  saving && styles.disabled,
                ]}
              >
                <Text style={styles.saveText}>✓</Text>
              </Pressable>

              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Cancelar"
                disabled={saving}
                onPress={onClose}
                style={({ pressed }) => [
                  styles.iconButton,
                  styles.cancelButton,
                  pressed && styles.pressed,
                  saving && styles.disabled,
                ]}
              >
                <Text style={styles.cancelText}>✕</Text>
              </Pressable>
            </View>
          </View>
        </SafeAreaView>
      </KeyboardAvoidingView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    backgroundColor: 'rgba(15, 23, 42, 0.6)',
    flex: 1,
    justifyContent: 'flex-end',
  },
  sheet: {
    backgroundColor: colors.surface,
    borderTopLeftRadius: radius.lg,
    borderTopRightRadius: radius.lg,
    maxHeight: '94%',
    overflow: 'hidden',
  },
  header: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  title: {
    color: NAVY,
    flex: 1,
    fontSize: 17,
    fontWeight: '800',
  },
  headerClose: {
    alignItems: 'center',
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  headerCloseText: {
    color: colors.muted,
    fontSize: 16,
    fontWeight: '700',
  },
  body: {
    flexGrow: 0,
    flexShrink: 1,
  },
  bodyContent: {
    padding: spacing.lg,
  },
  grid: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  gridItem: {
    flex: 1,
  },
  descriptionInput: {
    minHeight: 78,
    textAlignVertical: 'top',
  },
  checkRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.lg,
  },
  checkbox: {
    alignItems: 'center',
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    height: 22,
    justifyContent: 'center',
    width: 22,
  },
  checkboxActive: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  checkmark: {
    color: colors.surface,
    fontSize: 14,
    fontWeight: '800',
    lineHeight: 16,
  },
  checkLabel: {
    color: '#374151',
    fontSize: 13,
    fontWeight: '600',
  },
  sectionHead: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  sectionTitle: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
  },
  addButton: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: radius.sm,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  addButtonText: {
    color: colors.surface,
    fontSize: 20,
    fontWeight: '700',
    lineHeight: 22,
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
  deleteButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#fecaca',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  deleteText: {
    color: '#b91c1c',
    fontSize: 13,
    fontWeight: '700',
  },
  existingBlock: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    marginTop: spacing.sm,
    paddingTop: spacing.md,
  },
  existingRow: {
    alignItems: 'center',
    borderBottomColor: '#f3f4f6',
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    paddingVertical: spacing.sm,
  },
  existingText: {
    color: colors.text,
    flex: 1,
    fontSize: 12,
    lineHeight: 17,
  },
  existingActions: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 6,
  },
  visibilityBadge: {
    borderRadius: radius.sm,
    paddingHorizontal: 6,
    paddingVertical: 3,
  },
  visibilityVisible: {
    backgroundColor: '#d1fae5',
  },
  visibilityHidden: {
    backgroundColor: '#f3f4f6',
  },
  visibilityText: {
    fontSize: 10,
    fontWeight: '700',
  },
  visibilityVisibleText: {
    color: '#047857',
  },
  visibilityHiddenText: {
    color: colors.muted,
  },
  editButton: {
    alignItems: 'center',
    height: 28,
    justifyContent: 'center',
    width: 28,
  },
  editText: {
    color: ACCENT,
    fontSize: 16,
    fontWeight: '700',
  },
  footer: {
    alignItems: 'center',
    borderTopColor: colors.border,
    borderTopWidth: 1,
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  estimate: {
    color: colors.muted,
    flex: 1,
    fontSize: 12,
  },
  footerActions: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  iconButton: {
    alignItems: 'center',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 40,
    justifyContent: 'center',
    width: 40,
  },
  saveButton: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  saveText: {
    color: colors.surface,
    fontSize: 18,
    fontWeight: '800',
    lineHeight: 20,
  },
  cancelButton: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
  },
  cancelText: {
    color: '#374151',
    fontSize: 15,
    fontWeight: '700',
    lineHeight: 17,
  },
  pressed: {
    opacity: 0.8,
  },
  disabled: {
    opacity: 0.55,
  },
});
