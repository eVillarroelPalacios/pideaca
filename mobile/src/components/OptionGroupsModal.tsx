import { useRef, useState } from 'react';
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
import { colors, radius, spacing } from '@/theme';
import type { ProviderCatalogProduct } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type LocalOption = {
  key: string;
  id: number | null;
  name: string;
  extraPrice: string;
  isAvailable: boolean;
};

type LocalGroup = {
  key: string;
  id: number | null;
  name: string;
  min: string;
  max: string;
  isRequired: boolean;
  options: LocalOption[];
};

type Props = {
  visible: boolean;
  product: ProviderCatalogProduct | null;
  onClose: () => void;
  onSaved: () => void;
};

let keySeq = 0;

function nextKey(prefix: string): string {
  keySeq += 1;

  return `${prefix}-${keySeq}`;
}

function newOption(): LocalOption {
  return {
    key: nextKey('opt'),
    id: null,
    name: '',
    extraPrice: '',
    isAvailable: true,
  };
}

function newGroup(): LocalGroup {
  return {
    key: nextKey('grp'),
    id: null,
    name: '',
    min: '0',
    max: '3',
    isRequired: false,
    options: [],
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
 * Edición de agregados (grupos de opciones y sus opciones), igual que el
 * modal web: al guardar se borran las bajas y se guardan primero los grupos
 * para que las opciones nuevas tengan su id.
 */
export function OptionGroupsModal({ visible, product, onClose, onSaved }: Props) {
  const [groups, setGroups] = useState<LocalGroup[]>(() =>
    (product?.option_groups ?? []).map((group) => ({
      key: nextKey('grp'),
      id: group.id,
      name: group.name,
      min: String(group.min_choices),
      max: String(group.max_choices),
      isRequired: group.is_required,
      options: group.options.map((option) => ({
        key: nextKey('opt'),
        id: option.id,
        name: option.name,
        extraPrice: String(option.extra_price),
        isAvailable: option.is_available !== false,
      })),
    })),
  );
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const originalGroups = useRef<number[]>(
    (product?.option_groups ?? []).map((group) => group.id),
  );
  const originalOptions = useRef<number[]>(
    (product?.option_groups ?? []).flatMap((group) =>
      group.options.map((option) => option.id),
    ),
  );

  function updateGroup(key: string, patch: Partial<LocalGroup>) {
    setGroups((current) =>
      current.map((group) => (group.key === key ? { ...group, ...patch } : group)),
    );
  }

  function removeGroup(key: string) {
    setGroups((current) => current.filter((group) => group.key !== key));
  }

  function addOption(groupKey: string) {
    setGroups((current) =>
      current.map((group) =>
        group.key === groupKey
          ? { ...group, options: [...group.options, newOption()] }
          : group,
      ),
    );
  }

  function updateOption(groupKey: string, optionKey: string, patch: Partial<LocalOption>) {
    setGroups((current) =>
      current.map((group) =>
        group.key === groupKey
          ? {
              ...group,
              options: group.options.map((option) =>
                option.key === optionKey ? { ...option, ...patch } : option,
              ),
            }
          : group,
      ),
    );
  }

  function removeOption(groupKey: string, optionKey: string) {
    setGroups((current) =>
      current.map((group) =>
        group.key === groupKey
          ? {
              ...group,
              options: group.options.filter((option) => option.key !== optionKey),
            }
          : group,
      ),
    );
  }

  async function save() {
    if (!product) {
      return;
    }

    for (const group of groups) {
      const nombre = group.name.trim();
      const min = Number(group.min || 0);
      const max = Number(group.max || 0);

      if (!nombre) {
        setError('Cada grupo necesita un nombre.');
        return;
      }

      if (max < 1) {
        setError(`En "${nombre}" el máximo de elecciones debe ser al menos 1.`);
        return;
      }

      if (min > max) {
        setError(`En "${nombre}" el mínimo no puede superar al máximo.`);
        return;
      }

      for (const option of group.options) {
        if (!option.name.trim() || option.extraPrice.trim() === '') {
          setError('Completá nombre y precio extra en cada opción.');
          return;
        }
      }
    }

    setSaving(true);
    setError(null);

    try {
      const liveGroupIds = groups
        .filter((group) => group.id !== null)
        .map((group) => group.id as number);
      const liveOptionIds = groups.flatMap((group) =>
        group.options
          .filter((option) => option.id !== null)
          .map((option) => option.id as number),
      );

      // Bajas: primero las opciones sueltas, después los grupos (cascada).
      for (const id of originalOptions.current) {
        if (!liveOptionIds.includes(id)) {
          await api.providerCatalogDeleteOption(id);
        }
      }

      for (const id of originalGroups.current) {
        if (!liveGroupIds.includes(id)) {
          await api.providerCatalogDeleteOptionGroup(id);
        }
      }

      // Altas y ediciones: cada grupo se guarda antes que sus opciones.
      for (const group of groups) {
        const payload = {
          name: group.name.trim(),
          min_choices: Number(group.min || 0),
          max_choices: Number(group.max || 0),
          is_required: group.isRequired,
        };

        let groupId = group.id;

        if (groupId !== null) {
          await api.providerCatalogUpdateOptionGroup(groupId, payload);
        } else {
          const created = await api.providerCatalogCreateOptionGroup(product.id, payload);
          groupId = created.group.id;
        }

        for (const option of group.options) {
          const optionPayload = {
            name: option.name.trim(),
            extra_price: Number(option.extraPrice),
            is_available: option.isAvailable,
          };

          if (option.id !== null) {
            await api.providerCatalogUpdateOption(option.id, optionPayload);
          } else {
            await api.providerCatalogCreateOption(groupId, optionPayload);
          }
        }
      }

      onSaved();
      onClose();
    } catch (caught) {
      setError(errorMessage(caught, 'No se pudieron guardar los agregados.'));
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
              <Text style={styles.title}>Agregados</Text>
              {product ? <Text style={styles.subtitle}>{product.name}</Text> : null}
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
            {groups.length === 0 ? (
              <Text style={styles.empty}>Todavía no hay grupos de agregados.</Text>
            ) : null}

            {groups.map((group, groupIndex) => (
              <View key={group.key} style={styles.groupCard}>
                <View style={styles.groupHeader}>
                  <Text style={styles.groupTitle}>Grupo {groupIndex + 1}</Text>
                  <Pressable
                    accessibilityRole="button"
                    onPress={() => removeGroup(group.key)}
                    style={styles.removeGroup}
                  >
                    <Text style={styles.removeGroupText}>Quitar grupo</Text>
                  </Pressable>
                </View>

                <Text style={styles.label}>Grupo</Text>
                <TextInput
                  value={group.name}
                  onChangeText={(value) => updateGroup(group.key, { name: value })}
                  placeholder="Ej: Agregados"
                  placeholderTextColor={colors.muted}
                  maxLength={60}
                  style={styles.input}
                />

                <View style={styles.ruleRow}>
                  <View style={styles.ruleField}>
                    <Text style={styles.label}>Mín.</Text>
                    <TextInput
                      value={group.min}
                      onChangeText={(value) => updateGroup(group.key, { min: value })}
                      keyboardType="number-pad"
                      placeholder="0"
                      placeholderTextColor={colors.muted}
                      style={styles.input}
                    />
                  </View>
                  <View style={styles.ruleField}>
                    <Text style={styles.label}>Máx.</Text>
                    <TextInput
                      value={group.max}
                      onChangeText={(value) => updateGroup(group.key, { max: value })}
                      keyboardType="number-pad"
                      placeholder="3"
                      placeholderTextColor={colors.muted}
                      style={styles.input}
                    />
                  </View>
                </View>

                <View style={styles.switchRow}>
                  <Text style={styles.switchLabel}>Obligatorio</Text>
                  <Switch
                    value={group.isRequired}
                    onValueChange={(value) => updateGroup(group.key, { isRequired: value })}
                  />
                </View>

                {group.options.map((option) => (
                  <View key={option.key} style={styles.optionRow}>
                    <View style={styles.optionHeader}>
                      <Text style={styles.optionTitle}>Opción</Text>
                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Quitar opción"
                        onPress={() => removeOption(group.key, option.key)}
                        style={styles.removeOption}
                      >
                        <Text style={styles.removeOptionText}>✕</Text>
                      </Pressable>
                    </View>

                    <View style={styles.optionFields}>
                      <View style={styles.optionName}>
                        <Text style={styles.label}>Nombre</Text>
                        <TextInput
                          value={option.name}
                          onChangeText={(value) =>
                            updateOption(group.key, option.key, { name: value })
                          }
                          placeholder="Ej: Muzzarella extra"
                          placeholderTextColor={colors.muted}
                          maxLength={60}
                          style={styles.input}
                        />
                      </View>
                      <View style={styles.optionPrice}>
                        <Text style={styles.label}>Precio extra</Text>
                        <TextInput
                          value={option.extraPrice}
                          onChangeText={(value) =>
                            updateOption(group.key, option.key, { extraPrice: value })
                          }
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
                        value={option.isAvailable}
                        onValueChange={(value) =>
                          updateOption(group.key, option.key, { isAvailable: value })
                        }
                      />
                    </View>
                  </View>
                ))}

                <Pressable
                  accessibilityRole="button"
                  onPress={() => addOption(group.key)}
                  style={styles.addOption}
                >
                  <Text style={styles.addOptionText}>+ Agregar opción</Text>
                </Pressable>
              </View>
            ))}

            <Pressable
              accessibilityRole="button"
              onPress={() => setGroups((current) => [...current, newGroup()])}
              style={styles.addGroup}
            >
              <Text style={styles.addGroupText}>+ Agregar grupo</Text>
            </Pressable>

            {error ? <Text style={styles.error}>{error}</Text> : null}

            <View style={styles.buttons}>
              <PrimaryButton
                title="Guardar agregados"
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
    maxHeight: '92%',
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
  groupCard: {
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  groupHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  groupTitle: {
    color: '#475569',
    fontSize: 12,
    fontWeight: '700',
  },
  removeGroup: {
    paddingVertical: 2,
  },
  removeGroupText: {
    color: '#b91c1c',
    fontSize: 11,
    fontWeight: '700',
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
    marginBottom: spacing.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
  },
  ruleRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  ruleField: {
    flex: 1,
  },
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  switchLabel: {
    color: '#475569',
    fontSize: 12,
  },
  optionRow: {
    backgroundColor: colors.background,
    borderRadius: radius.sm,
    marginBottom: spacing.sm,
    padding: spacing.sm,
  },
  optionHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  optionTitle: {
    color: '#475569',
    fontSize: 11,
    fontWeight: '700',
  },
  removeOption: {
    alignItems: 'center',
    height: 24,
    justifyContent: 'center',
    width: 24,
  },
  removeOptionText: {
    color: '#b91c1c',
    fontSize: 13,
  },
  optionFields: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  optionName: {
    flex: 2,
  },
  optionPrice: {
    flex: 1,
  },
  addOption: {
    marginTop: spacing.xs,
  },
  addOptionText: {
    color: ACCENT,
    fontSize: 12,
    fontWeight: '700',
  },
  addGroup: {
    marginBottom: spacing.md,
  },
  addGroupText: {
    color: ACCENT,
    fontSize: 13,
    fontWeight: '700',
  },
  error: {
    color: colors.danger,
    fontSize: 13,
    marginBottom: spacing.md,
  },
  buttons: {
    marginTop: spacing.xs,
  },
  buttonGap: {
    height: spacing.sm,
  },
});
