import { useMemo, useState } from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  View,
} from 'react-native';

import { ApiError, api } from '@/api/client';
import { ComboField, type ComboOption } from '@/components/ComboField';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderCatalogCategory,
  ProviderCatalogProduct,
  ProviderCatalogProductsResponse,
} from '@/types/api';

const NAVY = '#0c2a4d';

type UnitOption = ProviderCatalogProductsResponse['unit_of_measures'][number];

type Props = {
  visible: boolean;
  /** null = alta; producto = edición. */
  product: ProviderCatalogProduct | null;
  categories: ProviderCatalogCategory[];
  units: UnitOption[];
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

/**
 * Alta y edición de producto, con los mismos campos que el modal web:
 * nombre, categoría (con alta rápida), precio, disponibilidad y control de
 * stock (carga inicial, mínimo, unidad y stock negativo).
 */
export function ProductFormModal({
  visible,
  product,
  categories,
  units,
  onClose,
  onSaved,
}: Props) {
  const [name, setName] = useState(product?.name ?? '');
  const [categoryId, setCategoryId] = useState<number | null>(product?.category_id ?? null);
  const [price, setPrice] = useState(product ? String(product.price) : '');
  const [isAvailable, setIsAvailable] = useState(product ? product.is_available !== false : true);
  const [trackStock, setTrackStock] = useState(Boolean(product?.track_stock));
  const [initialStock, setInitialStock] = useState('');
  const [minStock, setMinStock] = useState(
    product && product.min_stock_alert !== null ? String(product.min_stock_alert) : '',
  );
  const [unitId, setUnitId] = useState<number | null>(product?.unit_of_measure_id ?? null);
  const [allowNegative, setAllowNegative] = useState(Boolean(product?.allow_negative_stock));
  const [localCategories, setLocalCategories] =
    useState<ProviderCatalogCategory[]>(categories);
  const [newCategoryOpen, setNewCategoryOpen] = useState(false);
  const [newCategoryName, setNewCategoryName] = useState('');
  const [creatingCategory, setCreatingCategory] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const categoryOptions = useMemo<ComboOption<number>[]>(
    () =>
      localCategories.map((category) => ({
        value: category.id,
        label: category.name,
      })),
    [localCategories],
  );

  const unitOptions = useMemo<ComboOption<number>[]>(
    () =>
      units.map((unit) => ({
        value: unit.id,
        label: `${unit.name} (${unit.symbol})`,
      })),
    [units],
  );

  async function createCategory() {
    const nombre = newCategoryName.trim();

    if (nombre.length < 2) {
      setError('Escribí el nombre de la categoría.');
      return;
    }

    setCreatingCategory(true);
    setError(null);

    try {
      const response = await api.providerCatalogCreateCategory(nombre);
      setLocalCategories((current) => [...current, response.category]);
      setCategoryId(response.category.id);
      setNewCategoryOpen(false);
      setNewCategoryName('');
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo crear la categoría.'));
    } finally {
      setCreatingCategory(false);
    }
  }

  async function save() {
    const nombre = name.trim();

    if (!nombre || categoryId === null || price.trim() === '') {
      setError('Completá nombre, categoría y precio.');
      return;
    }

    const payload = {
      name: nombre,
      category_id: categoryId,
      price: Number(price),
      is_available: isAvailable,
      track_stock: trackStock,
      ...(trackStock
        ? {
            // En la edición la cantidad se maneja desde Inventario.
            initial_stock: product ? 0 : initialStock.trim() === '' ? 0 : Number(initialStock),
            min_stock_alert: minStock.trim() === '' ? null : Number(minStock),
            unit_of_measure_id: unitId,
            allow_negative_stock: allowNegative,
          }
        : {}),
    };

    setSaving(true);
    setError(null);

    try {
      if (product) {
        await api.providerCatalogUpdateProduct(product.id, payload);
      } else {
        await api.providerCatalogCreateProduct(payload);
      }

      onSaved();
      onClose();
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo guardar el producto.'));
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <View style={styles.header}>
            <Text style={styles.title}>{product ? 'Editar producto' : 'Nuevo producto'}</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Cerrar"
              onPress={onClose}
              style={styles.close}
            >
              <Text style={styles.closeText}>×</Text>
            </Pressable>
          </View>

          <ScrollView
            style={styles.body}
            contentContainerStyle={styles.bodyContent}
            keyboardShouldPersistTaps="handled"
          >
            <TextField
              label="Nombre *"
              placeholder="Ej: Empanada de carne"
              value={name}
              onChangeText={setName}
              maxLength={255}
            />

            <View style={styles.row}>
              <View style={styles.flex}>
                <ComboField<number>
                  label="Categoría *"
                  value={categoryId}
                  options={categoryOptions}
                  onChange={setCategoryId}
                  placeholder="Elegir categoría"
                />
              </View>

              <View style={styles.flex}>
                <TextField
                  label="Precio *"
                  placeholder="0.00"
                  value={price}
                  onChangeText={setPrice}
                  keyboardType="decimal-pad"
                />
              </View>
            </View>

            {newCategoryOpen ? (
              <View style={styles.newCategoryBox}>
                <TextField
                  label="Nueva categoría"
                  placeholder="Ej: Bebidas frías"
                  value={newCategoryName}
                  onChangeText={setNewCategoryName}
                  maxLength={80}
                />
                <View style={styles.newCategoryActions}>
                  <View style={styles.flex}>
                    <PrimaryButton
                      title={creatingCategory ? 'Agregando…' : 'Agregar'}
                      onPress={() => void createCategory()}
                      loading={creatingCategory}
                    />
                  </View>
                  <View style={styles.flex}>
                    <PrimaryButton
                      title="Cancelar"
                      variant="ghost"
                      onPress={() => {
                        setNewCategoryOpen(false);
                        setNewCategoryName('');
                        setError(null);
                      }}
                      disabled={creatingCategory}
                    />
                  </View>
                </View>
              </View>
            ) : (
              <Pressable
                accessibilityRole="button"
                onPress={() => setNewCategoryOpen(true)}
                style={styles.newCategoryLink}
              >
                <Text style={styles.newCategoryLinkText}>+ Nueva categoría</Text>
              </Pressable>
            )}

            <View style={styles.switchRow}>
              <Text style={styles.switchLabel}>Producto disponible para pedidos</Text>
              <Switch value={isAvailable} onValueChange={setIsAvailable} />
            </View>

            <View style={styles.switchRow}>
              <Text style={styles.switchLabel}>Controlar stock (habilitar seguimiento)</Text>
              <Switch value={trackStock} onValueChange={setTrackStock} />
            </View>

            {trackStock ? (
              <View style={styles.stockBox}>
                <View style={styles.row}>
                  <View style={styles.flex}>
                    <TextField
                      label="Stock inicial"
                      placeholder="0"
                      value={initialStock}
                      onChangeText={setInitialStock}
                      keyboardType="decimal-pad"
                      editable={!product}
                    />
                  </View>
                  <View style={styles.flex}>
                    <TextField
                      label="Stock mínimo"
                      placeholder="0"
                      value={minStock}
                      onChangeText={setMinStock}
                      keyboardType="decimal-pad"
                    />
                  </View>
                </View>

                <ComboField<number>
                  label="Unidad de medida"
                  value={unitId}
                  options={unitOptions}
                  onChange={setUnitId}
                  placeholder="Elegir unidad"
                />

                <View style={styles.switchRow}>
                  <Text style={styles.switchHint}>Permitir stock negativo</Text>
                  <Switch value={allowNegative} onValueChange={setAllowNegative} />
                </View>

                <Text style={styles.hint}>
                  El stock inicial solo aplica para la creación; después se edita desde
                  Inventario.
                </Text>
              </View>
            ) : null}

            {error ? <Text style={styles.error}>{error}</Text> : null}

            <View style={styles.buttons}>
              <PrimaryButton
                title={product ? 'Guardar cambios' : 'Crear producto'}
                onPress={() => void save()}
                loading={saving}
              />
              <View style={styles.buttonGap} />
              <PrimaryButton
                title="Cancelar"
                variant="ghost"
                onPress={onClose}
                disabled={saving}
              />
            </View>
          </ScrollView>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(15, 23, 42, 0.6)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    maxHeight: '90%',
    width: '100%',
  },
  header: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    padding: spacing.lg,
  },
  title: {
    color: NAVY,
    fontSize: 17,
    fontWeight: '800',
  },
  close: {
    alignItems: 'center',
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  closeText: {
    color: colors.muted,
    fontSize: 24,
    lineHeight: 26,
  },
  body: {
    paddingHorizontal: spacing.lg,
  },
  bodyContent: {
    paddingBottom: spacing.lg,
    paddingTop: spacing.lg,
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  flex: {
    flex: 1,
  },
  newCategoryLink: {
    alignSelf: 'flex-start',
    marginBottom: spacing.md,
  },
  newCategoryLinkText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '700',
  },
  newCategoryBox: {
    backgroundColor: colors.background,
    borderRadius: radius.md,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  newCategoryActions: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  switchLabel: {
    color: '#374151',
    flex: 1,
    fontSize: 13,
    fontWeight: '600',
    marginRight: spacing.md,
  },
  switchHint: {
    color: '#475569',
    flex: 1,
    fontSize: 12,
    marginRight: spacing.md,
  },
  stockBox: {
    backgroundColor: colors.background,
    borderRadius: radius.md,
    marginBottom: spacing.sm,
    padding: spacing.md,
  },
  hint: {
    color: '#94a3b8',
    fontSize: 11,
  },
  error: {
    color: colors.danger,
    fontSize: 13,
    marginBottom: spacing.md,
  },
  buttons: {
    marginTop: spacing.sm,
  },
  buttonGap: {
    height: spacing.sm,
  },
});
