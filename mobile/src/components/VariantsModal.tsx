import { useState } from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  View,
} from 'react-native';

import { ApiError, api } from '@/api/client';
import { PrimaryButton } from '@/components/PrimaryButton';
import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type { ProviderCatalogProduct } from '@/types/api';

const NAVY = '#0c2a4d';

type Row = {
  key: string;
  id: number | null;
  name: string;
  price: string;
  isAvailable: boolean;
};

type Props = {
  visible: boolean;
  product: ProviderCatalogProduct | null;
  onClose: () => void;
  onSaved: () => void;
};

let rowSeq = 0;

function newRow(): Row {
  rowSeq += 1;

  return {
    key: `new-${rowSeq}`,
    id: null,
    name: '',
    price: '',
    isAvailable: true,
  };
}

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
 * Edición de variantes en bloque, igual que el modal web: al guardar se
 * comparan las filas con las originales para borrar, actualizar y crear.
 */
export function VariantsModal({ visible, product, onClose, onSaved }: Props) {
  const [rows, setRows] = useState<Row[]>(() =>
    (product?.variants ?? []).map((variant) => ({
      key: `v-${variant.id}`,
      id: variant.id,
      name: variant.name,
      price: String(variant.price),
      isAvailable: variant.is_available !== false,
    })),
  );
  const [originalIds] = useState<number[]>(() =>
    (product?.variants ?? []).map((variant) => variant.id),
  );
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  function updateRow(key: string, patch: Partial<Row>) {
    setRows((current) =>
      current.map((row) => (row.key === key ? { ...row, ...patch } : row)),
    );
  }

  function removeRow(key: string) {
    setRows((current) => current.filter((row) => row.key !== key));
  }

  async function save() {
    if (!product) {
      return;
    }

    for (const row of rows) {
      if (!row.name.trim() || row.price.trim() === '') {
        setError('Completá nombre y precio en cada variante.');
        return;
      }
    }

    setSaving(true);
    setError(null);

    try {
      const liveIds = rows
        .filter((row) => row.id !== null)
        .map((row) => row.id as number);

      for (const id of originalIds) {
        if (!liveIds.includes(id)) {
          await api.providerCatalogDeleteVariant(id);
        }
      }

      for (const row of rows) {
        const payload = {
          name: row.name.trim(),
          price: Number(row.price),
          is_available: row.isAvailable,
        };

        if (row.id !== null) {
          await api.providerCatalogUpdateVariant(row.id, payload);
        } else {
          await api.providerCatalogCreateVariant(product.id, payload);
        }
      }

      onSaved();
      onClose();
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudieron guardar las variantes.'));
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <View style={styles.header}>
            <View style={styles.headerInfo}>
              <Text style={styles.title}>Variantes</Text>
              {product ? (
                <Text style={styles.subtitle}>
                  {product.name} · precio base {money(product.price)}
                </Text>
              ) : null}
            </View>
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
            {rows.length === 0 ? (
              <Text style={styles.empty}>Todavía no hay variantes.</Text>
            ) : null}

            {rows.map((row, index) => (
              <View key={row.key} style={styles.row}>
                <View style={styles.rowHeader}>
                  <Text style={styles.rowTitle}>Variante {index + 1}</Text>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Quitar variante"
                    onPress={() => removeRow(row.key)}
                    style={styles.remove}
                  >
                    <Text style={styles.removeText}>✕</Text>
                  </Pressable>
                </View>

                <View style={styles.rowFields}>
                  <View style={styles.nameField}>
                    <Text style={styles.label}>Nombre</Text>
                    <TextInput
                      value={row.name}
                      onChangeText={(value) => updateRow(row.key, { name: value })}
                      placeholder="Ej: Individual"
                      placeholderTextColor={colors.muted}
                      maxLength={60}
                      style={styles.input}
                    />
                  </View>

                  <View style={styles.priceField}>
                    <Text style={styles.label}>Precio</Text>
                    <TextInput
                      value={row.price}
                      onChangeText={(value) => updateRow(row.key, { price: value })}
                      placeholder="0.00"
                      placeholderTextColor={colors.muted}
                      keyboardType="decimal-pad"
                      style={styles.input}
                    />
                  </View>
                </View>

                <View style={styles.switchRow}>
                  <Text style={styles.switchLabel}>Disponible</Text>
                  <Switch
                    value={row.isAvailable}
                    onValueChange={(value) => updateRow(row.key, { isAvailable: value })}
                  />
                </View>
              </View>
            ))}

            <Pressable
              accessibilityRole="button"
              onPress={() => setRows((current) => [...current, newRow()])}
              style={styles.addRow}
            >
              <Text style={styles.addRowText}>+ Agregar variante</Text>
            </Pressable>

            {error ? <Text style={styles.error}>{error}</Text> : null}

            <View style={styles.buttons}>
              <PrimaryButton
                title="Guardar variantes"
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
    alignItems: 'flex-start',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    padding: spacing.lg,
  },
  headerInfo: {
    flexShrink: 1,
  },
  title: {
    color: NAVY,
    fontSize: 17,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 12,
    marginTop: spacing.xs,
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
    paddingTop: spacing.md,
  },
  empty: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.md,
  },
  row: {
    borderBottomColor: '#f1f5f9',
    borderBottomWidth: 1,
    paddingVertical: spacing.md,
  },
  rowHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.xs,
  },
  rowTitle: {
    color: '#475569',
    fontSize: 12,
    fontWeight: '700',
  },
  remove: {
    alignItems: 'center',
    height: 26,
    justifyContent: 'center',
    width: 26,
  },
  removeText: {
    color: '#b91c1c',
    fontSize: 14,
  },
  rowFields: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  nameField: {
    flex: 2,
  },
  priceField: {
    flex: 1,
  },
  label: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 4,
  },
  input: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    color: colors.text,
    fontSize: 13,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
  },
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.sm,
  },
  switchLabel: {
    color: '#475569',
    fontSize: 12,
  },
  addRow: {
    marginTop: spacing.md,
  },
  addRowText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '700',
  },
  error: {
    color: colors.danger,
    fontSize: 13,
    marginTop: spacing.md,
  },
  buttons: {
    marginTop: spacing.md,
  },
  buttonGap: {
    height: spacing.sm,
  },
});
