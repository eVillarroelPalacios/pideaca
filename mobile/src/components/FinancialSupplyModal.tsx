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
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderSupply,
  ProviderSupplyPayload,
  ProviderSupplyUnit,
} from '@/types/api';

const NAVY = '#0c2a4d';

type Props = {
  visible: boolean;
  supply: ProviderSupply | null;
  units: ProviderSupplyUnit[];
  onClose: () => void;
  onSaved: (message: string) => void;
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

function parseCost(raw: string): number | null {
  const normalized = raw.trim().replace(',', '.');

  if (normalized === '') {
    return null;
  }

  const value = Number(normalized);

  return Number.isFinite(value) ? value : Number.NaN;
}

/**
 * Modal "Nuevo insumo" / "Editar insumo".
 *
 * Se monta con `key` por insumo para tomar los valores iniciales directo de la
 * fila, sin efectos de sincronizacion.
 */
export function FinancialSupplyModal({
  visible,
  supply,
  units,
  onClose,
  onSaved,
}: Props) {
  const [name, setName] = useState(supply?.name ?? '');
  const [unitId, setUnitId] = useState<number | null>(
    supply?.unit_of_measure_id ?? units[0]?.id ?? null,
  );
  const [cost, setCost] = useState(
    supply === null ? '' : String(supply.cost_per_unit),
  );
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const unitOptions = useMemo<ComboOption<number>[]>(
    () =>
      units.map((unit) => ({
        value: unit.id,
        label: `${unit.name}`,
        hint: unit.symbol,
      })),
    [units],
  );

  async function save() {
    const trimmedName = name.trim();

    if (trimmedName === '') {
      setError('Escribí el nombre del insumo.');
      return;
    }

    if (unitId === null) {
      setError('Elegí la unidad de medida.');
      return;
    }

    const costValue = parseCost(cost);

    if (costValue === null) {
      setError('Indicá el costo por unidad.');
      return;
    }

    if (Number.isNaN(costValue)) {
      setError('Indicá un costo por unidad válido.');
      return;
    }

    if (costValue < 0) {
      setError('El costo por unidad no puede ser negativo.');
      return;
    }

    const payload: ProviderSupplyPayload = {
      name: trimmedName,
      unit_of_measure_id: unitId,
      cost_per_unit: costValue,
    };

    if (supply) {
      payload.id = supply.id;
    }

    setSaving(true);
    setError(null);

    try {
      const response = await api.providerFinancialSaveSupply(payload);
      onSaved(response.message || 'Insumo guardado.');
      onClose();
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudo guardar el insumo.'));
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
            <Text style={styles.title}>
              {supply ? 'Editar insumo' : 'Nuevo insumo'}
            </Text>

            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Cerrar"
              onPress={onClose}
              style={({ pressed }) => [styles.close, pressed && styles.pressed]}
            >
              <Text style={styles.closeText}>✕</Text>
            </Pressable>
          </View>

          <ScrollView
            style={styles.body}
            contentContainerStyle={styles.bodyContent}
            keyboardShouldPersistTaps="handled"
          >
            <TextField
              label="Nombre"
              placeholder="Ej: Harina 000"
              value={name}
              onChangeText={setName}
              maxLength={150}
            />

            <ComboField<number>
              label="Unidad"
              title="Unidad de medida"
              value={unitId}
              options={unitOptions}
              onChange={setUnitId}
              placeholder="Elegí una unidad"
            />

            <TextField
              label="Costo por unidad"
              placeholder="0,00"
              value={cost}
              onChangeText={setCost}
              keyboardType="decimal-pad"
            />

            <ErrorBanner message={error} />

            <PrimaryButton
              title={supply ? 'Guardar cambios' : 'Crear insumo'}
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
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  closeText: {
    color: '#374151',
    fontSize: 15,
    fontWeight: '700',
  },
  pressed: {
    opacity: 0.8,
  },
  body: {
    flexGrow: 0,
  },
  bodyContent: {
    padding: spacing.lg,
  },
  buttonGap: {
    height: spacing.sm,
  },
});
