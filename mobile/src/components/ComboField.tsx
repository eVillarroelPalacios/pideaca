import { useMemo, useState } from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { colors, radius, spacing } from '@/theme';

export type ComboOption<T extends string | number> = {
  value: T;
  label: string;
  /** Texto secundario (por ejemplo, cantidad de usuarios). */
  hint?: string;
  disabled?: boolean;
};

type Props<T extends string | number> = {
  label: string;
  value: T | null;
  options: ComboOption<T>[];
  onChange: (value: T) => void;
  placeholder?: string;
  error?: string | null;
  disabled?: boolean;
  /** Fuerza el buscador; por defecto aparece con 10 o más opciones. */
  searchable?: boolean;
  searchPlaceholder?: string;
  /** Agrega la opción "Todos" para filtros. */
  allowEmpty?: boolean;
  emptyLabel?: string;
  onClear?: () => void;
  /** Título de la hoja; por defecto usa la etiqueta del campo. */
  title?: string;
};

/** Compara ignorando mayúsculas y acentos (Perú = Peru). */
function normalize(value: string): string {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

/**
 * Combo con el mismo modelo que el `<select>` de la web: un campo que
 * muestra el valor elegido y abre una lista de opciones en una hoja
 * inferior. Reemplaza a los chips de selección única de la app móvil.
 */
export function ComboField<T extends string | number>({
  label,
  value,
  options,
  onChange,
  placeholder = 'Seleccioná una opción',
  error = null,
  disabled = false,
  searchable,
  searchPlaceholder = 'Buscar…',
  allowEmpty = false,
  emptyLabel = 'Todos',
  onClear,
  title,
}: Props<T>) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');

  const selected = options.find((option) => option.value === value) ?? null;
  const showSearch = searchable ?? options.length >= 10;
  const filtered = useMemo(() => {
    const term = normalize(query);

    if (!term) {
      return options;
    }

    return options.filter((option) => {
      const text = `${option.label} ${option.hint ?? ''}`;
      return normalize(text).includes(term);
    });
  }, [options, query]);

  function openSheet() {
    if (disabled) {
      return;
    }

    setQuery('');
    setOpen(true);
  }

  function closeSheet() {
    setOpen(false);
  }

  function pick(option: ComboOption<T>) {
    if (option.disabled) {
      return;
    }

    onChange(option.value);
    closeSheet();
  }

  function clear() {
    onClear?.();
    closeSheet();
  }

  const display = selected?.label ?? (allowEmpty && value === null ? emptyLabel : '');
  const shownTitle = title ?? label;

  return (
    <View style={styles.wrapper}>
      <Text style={styles.label}>{label}</Text>

      <Pressable
        accessibilityRole="button"
        accessibilityLabel={label}
        accessibilityState={{ disabled, expanded: open }}
        disabled={disabled}
        onPress={openSheet}
        style={[
          styles.input,
          open && styles.inputFocused,
          Boolean(error) && styles.inputError,
          disabled && styles.inputDisabled,
        ]}
      >
        <Text
          numberOfLines={1}
          style={[styles.value, !display && styles.placeholder]}
        >
          {display || placeholder}
        </Text>
        <Text style={styles.chevron}>⌄</Text>
      </Pressable>

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <Modal
        visible={open}
        transparent
        animationType="fade"
        onRequestClose={closeSheet}
        statusBarTranslucent
      >
        <View style={styles.backdrop}>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Cerrar opciones"
            onPress={closeSheet}
            style={styles.backdropTap}
          />

          <SafeAreaView style={styles.sheet} edges={['bottom']}>
            <View style={styles.sheetHeader}>
              <Text style={styles.sheetTitle} numberOfLines={2}>
                {shownTitle}
              </Text>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Cerrar opciones"
                onPress={closeSheet}
                style={styles.closeButton}
              >
                <Text style={styles.closeText}>✕</Text>
              </Pressable>
            </View>

            {showSearch ? (
              <TextInput
                value={query}
                onChangeText={setQuery}
                placeholder={searchPlaceholder}
                placeholderTextColor={colors.muted}
                autoCapitalize="none"
                autoCorrect={false}
                style={styles.search}
              />
            ) : null}

            <ScrollView
              style={styles.list}
              contentContainerStyle={styles.listContent}
              keyboardShouldPersistTaps="handled"
            >
              {allowEmpty ? (
                <Pressable
                  accessibilityRole="radio"
                  accessibilityState={{ selected: value === null }}
                  onPress={clear}
                  style={[
                    styles.option,
                    value === null && styles.optionSelected,
                  ]}
                >
                  <Text
                    style={[
                      styles.optionLabel,
                      value === null && styles.optionLabelSelected,
                    ]}
                  >
                    {emptyLabel}
                  </Text>
                  {value === null ? <Text style={styles.check}>✓</Text> : null}
                </Pressable>
              ) : null}

              {filtered.map((option) => {
                const isSelected = option.value === value;

                return (
                  <Pressable
                    key={String(option.value)}
                    accessibilityRole="radio"
                    accessibilityState={{
                      selected: isSelected,
                      disabled: option.disabled,
                    }}
                    disabled={option.disabled}
                    onPress={() => pick(option)}
                    style={[
                      styles.option,
                      isSelected && styles.optionSelected,
                      option.disabled && styles.optionDisabled,
                    ]}
                  >
                    <View style={styles.optionText}>
                      <Text
                        style={[
                          styles.optionLabel,
                          isSelected && styles.optionLabelSelected,
                          option.disabled && styles.optionLabelDisabled,
                        ]}
                        numberOfLines={2}
                      >
                        {option.label}
                      </Text>
                      {option.hint ? (
                        <Text style={styles.optionHint}>{option.hint}</Text>
                      ) : null}
                    </View>
                    {isSelected ? <Text style={styles.check}>✓</Text> : null}
                  </Pressable>
                );
              })}

              {filtered.length === 0 ? (
                <Text style={styles.empty}>Sin resultados.</Text>
              ) : null}
            </ScrollView>
          </SafeAreaView>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    marginBottom: spacing.lg,
  },
  label: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
    marginBottom: spacing.sm,
  },
  input: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  inputFocused: {
    borderColor: colors.brand,
  },
  inputError: {
    borderColor: colors.danger,
  },
  inputDisabled: {
    backgroundColor: colors.background,
    opacity: 0.7,
  },
  value: {
    color: colors.text,
    flex: 1,
    fontSize: 16,
  },
  placeholder: {
    color: colors.muted,
  },
  chevron: {
    color: colors.muted,
    fontSize: 16,
    fontWeight: '700',
    lineHeight: 18,
  },
  error: {
    color: colors.danger,
    fontSize: 13,
    marginTop: spacing.xs,
  },
  backdrop: {
    backgroundColor: 'rgba(15,23,42,0.5)',
    flex: 1,
    justifyContent: 'flex-end',
  },
  backdropTap: {
    bottom: 0,
    left: 0,
    position: 'absolute',
    right: 0,
    top: 0,
  },
  sheet: {
    backgroundColor: colors.surface,
    borderTopLeftRadius: radius.lg,
    borderTopRightRadius: radius.lg,
    maxHeight: '80%',
    overflow: 'hidden',
    paddingTop: spacing.sm,
  },
  sheetHeader: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  sheetTitle: {
    color: colors.text,
    flex: 1,
    fontSize: 16,
    fontWeight: '800',
    paddingRight: spacing.sm,
  },
  closeButton: {
    alignItems: 'center',
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  closeText: {
    color: colors.muted,
    fontSize: 15,
    fontWeight: '700',
  },
  search: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    color: colors.text,
    fontSize: 15,
    marginHorizontal: spacing.lg,
    marginTop: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  list: {
    flexGrow: 0,
    flexShrink: 1,
  },
  listContent: {
    padding: spacing.md,
  },
  option: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderRadius: radius.sm,
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'space-between',
    minHeight: 48,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
  },
  optionSelected: {
    backgroundColor: colors.brandSoft,
  },
  optionDisabled: {
    opacity: 0.5,
  },
  optionText: {
    flex: 1,
  },
  optionLabel: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '600',
  },
  optionLabelSelected: {
    color: colors.brandDark,
    fontWeight: '800',
  },
  optionLabelDisabled: {
    color: colors.muted,
  },
  optionHint: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  check: {
    color: colors.brand,
    fontSize: 16,
    fontWeight: '800',
  },
  empty: {
    color: colors.muted,
    fontSize: 13,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.lg,
    textAlign: 'center',
  },
});
