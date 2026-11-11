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
import { ErrorBanner } from '@/components/ErrorBanner';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { stockNumber } from '@/lib/stock';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderInventoryItem,
  ProviderInventoryProductPayload,
  ProviderInventoryUnit,
} from '@/types/api';

const NAVY = '#0c2a4d';

type Props = {
  visible: boolean;
  item: ProviderInventoryItem;
  units: ProviderInventoryUnit[];
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

/** Devuelve null cuando el campo quedó vacío y NaN cuando no es un número. */
function parseNumber(raw: string): number | null {
  const normalized = raw.trim().replace(',', '.');

  if (normalized === '') {
    return null;
  }

  const value = Number(normalized);

  return Number.isFinite(value) ? value : Number.NaN;
}

/**
 * Modal "Stock del producto" del panel web de inventario.
 *
 * Se monta condicionalmente con `key` por producto, asi el estado inicial se
 * toma directo del item sin efectos de sincronizacion.
 */
export function InventoryStockModal({ visible, item, units, onClose, onSaved }: Props) {
  const [trackStock, setTrackStock] = useState(item.track_stock);
  const [currentStock, setCurrentStock] = useState(stockNumber(item.current_stock));
  const [minStock, setMinStock] = useState(
    item.min_stock_alert === null ? '' : stockNumber(item.min_stock_alert),
  );
  const [unitId, setUnitId] = useState<number | null>(item.unit_of_measure?.id ?? null);
  const [allowNegative, setAllowNegative] = useState(item.allow_negative_stock);
  const [notes, setNotes] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const unitOptions = useMemo<ComboOption<number>[]>(
    () =>
      units.map((unit) => ({
        value: unit.id,
        label: `${unit.name} (${unit.symbol})`,
        hint: unit.is_integer_only ? 'Solo cantidades enteras' : undefined,
      })),
    [units],
  );

  async function save() {
    const payload: ProviderInventoryProductPayload = { track_stock: trackStock };

    if (trackStock) {
      const currentValue = parseNumber(currentStock);

      if (currentValue === null) {
        setError('Indicá el stock actual.');
        return;
      }

      if (Number.isNaN(currentValue)) {
        setError('Indicá un stock actual válido.');
        return;
      }

      const minValue = parseNumber(minStock);

      if (Number.isNaN(minValue)) {
        setError('Indicá un stock mínimo válido.');
        return;
      }

      payload.current_stock = currentValue;
      payload.min_stock_alert = minValue;
      payload.unit_of_measure_id = unitId;
      payload.allow_negative_stock = allowNegative;

      if (notes.trim() !== '') {
        payload.notes = notes.trim();
      }
    }

    setSaving(true);
    setError(null);

    try {
      await api.providerInventoryUpdateProduct(item.product_id, payload);
      onSaved();
      onClose();
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo guardar el stock.'));
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
              <Text style={styles.title}>Stock del producto</Text>
              <Text style={styles.subtitle} numberOfLines={1}>
                {item.name}
              </Text>
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
            <View style={styles.switchRow}>
              <Text style={styles.switchLabel}>Controlar stock de este producto</Text>
              <Switch value={trackStock} onValueChange={setTrackStock} />
            </View>

            {trackStock ? (
              <>
                <TextField
                  label="Stock actual"
                  placeholder="0"
                  value={currentStock}
                  onChangeText={setCurrentStock}
                  keyboardType="decimal-pad"
                />

                <TextField
                  label="Stock mínimo"
                  placeholder="0"
                  value={minStock}
                  onChangeText={setMinStock}
                  keyboardType="decimal-pad"
                />

                <ComboField<number>
                  label="Unidad de medida"
                  value={unitId}
                  options={unitOptions}
                  onChange={setUnitId}
                  allowEmpty
                  emptyLabel="Sin unidad"
                  onClear={() => setUnitId(null)}
                  placeholder="Sin unidad"
                  title="Unidad de medida"
                />

                <View style={styles.switchRow}>
                  <Text style={styles.switchHint}>Permitir stock negativo</Text>
                  <Switch value={allowNegative} onValueChange={setAllowNegative} />
                </View>

                <TextField
                  label="Motivo (opcional)"
                  placeholder="Ej: conteo de depósito"
                  value={notes}
                  onChangeText={setNotes}
                  maxLength={500}
                />
              </>
            ) : null}

            <ErrorBanner message={error} />

            <View style={styles.buttons}>
              <PrimaryButton title="Guardar stock" onPress={() => void save()} loading={saving} />
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
  headerInfo: {
    flex: 1,
  },
  title: {
    color: NAVY,
    fontSize: 17,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
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
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'space-between',
    marginBottom: spacing.lg,
  },
  switchLabel: {
    color: colors.text,
    flex: 1,
    fontSize: 14,
    fontWeight: '600',
  },
  switchHint: {
    color: colors.muted,
    flex: 1,
    fontSize: 13,
  },
  buttons: {
    marginTop: spacing.xs,
  },
  buttonGap: {
    height: spacing.sm,
  },
});
