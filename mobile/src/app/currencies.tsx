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
import { ComboField } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';
import type { CurrencyCountryOption, CurrencyItem } from '@/types/api';

type FieldErrors = {
  country_id?: string;
  code?: string;
  name?: string;
  symbol?: string;
  symbol_position?: string;
  decimals?: string;
  decimal_separator?: string;
  thousand_separator?: string;
  locale?: string;
};

/**
 * Replica el formateo de la web (preview de 1234.56 con los separadores
 * y la posicion del simbolo configurados).
 */
export function formatCurrencyPreview(
  c: Pick<
    CurrencyItem,
    'symbol' | 'symbol_position' | 'symbol_space' | 'decimals' | 'decimal_separator' | 'thousand_separator'
  >,
  value = 1234.56,
): string {
  const n = Number.isFinite(value) ? Math.abs(value) : 0;
  const negative = value < 0;
  const decimals = Math.max(0, Math.min(4, c.decimals ?? 2));
  const parts = n.toFixed(decimals).split('.');
  const thousand = c.thousand_separator ?? '.';
  const decimal = c.decimal_separator ?? ',';
  const intPart = (parts[0] ?? '0').replace(/\B(?=(\d{3})+(?!\d))/g, thousand);
  const decPart = decimals > 0 ? decimal + (parts[1] ?? '') : '';
  const number = intPart + decPart;
  const space = c.symbol_space === false ? '' : ' ';
  const out =
    c.symbol_position === 'after'
      ? `${number}${space}${c.symbol || ''}`
      : `${c.symbol || ''}${space}${number}`;
  return negative ? `-${out}` : out;
}

export default function CurrenciesScreen() {
  const [items, setItems] = useState<CurrencyItem[]>([]);
  const [countryOptions, setCountryOptions] = useState<CurrencyCountryOption[]>([]);
  const [summary, setSummary] = useState({ total: 0, active: 0, default_code: '' });
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  // Modal de alta/edicion.
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<CurrencyItem | null>(null);
  const [countryId, setCountryId] = useState<number | null>(null);
  const [code, setCode] = useState('');
  const [name, setName] = useState('');
  const [symbol, setSymbol] = useState('');
  const [position, setPosition] = useState<'before' | 'after'>('before');
  const [space, setSpace] = useState(true);
  const [decimals, setDecimals] = useState(2);
  const [decimalSep, setDecimalSep] = useState(',');
  const [thousandSep, setThousandSep] = useState('.');
  const [locale, setLocale] = useState('');
  const [active, setActive] = useState(true);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [saving, setSaving] = useState(false);

  const fetchCurrencies = useCallback(async () => {
    try {
      const response = await api.currencies();

      setItems(response.data);
      setCountryOptions(response.filters.countries);
      setSummary(response.summary);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar las monedas.',
      );
    }
  }, []);

  // Se recarga al entrar y al volver de otra pantalla.
  useFocusEffect(
    useCallback(() => {
      let active = true;

      setLoading(true);
      void fetchCurrencies().finally(() => {
        if (active) {
          setLoading(false);
        }
      });

      return () => {
        active = false;
      };
    }, [fetchCurrencies]),
  );

  async function handleRefresh() {
    setRefreshing(true);
    await fetchCurrencies();
    setRefreshing(false);
  }

  function openCreate() {
    setEditing(null);
    setCountryId(null);
    setCode('');
    setName('');
    setSymbol('');
    setPosition('before');
    setSpace(true);
    setDecimals(2);
    setDecimalSep(',');
    setThousandSep('.');
    setLocale('');
    setActive(true);
    setFieldErrors({});
    setNotice(null);
    setModalOpen(true);
  }

  function openEdit(item: CurrencyItem) {
    setEditing(item);
    setCountryId(item.country_id);
    setCode(item.code);
    setName(item.name);
    setSymbol(item.symbol);
    setPosition(item.symbol_position);
    setSpace(item.symbol_space);
    setDecimals(item.decimals);
    setDecimalSep(item.decimal_separator);
    setThousandSep(item.thousand_separator);
    setLocale(item.locale);
    setActive(item.is_active);
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
    const codeValue = code.trim().toUpperCase();
    const nameValue = name.trim();
    const symbolValue = symbol.trim();
    const localeValue = locale.trim();

    if (!countryId) {
      setFieldErrors({ country_id: 'Seleccioná un país.' });
      return;
    }

    if (!/^[A-Z]{3}$/.test(codeValue)) {
      setFieldErrors({ code: 'El código debe tener 3 letras (ej: ARS).' });
      return;
    }

    if (!nameValue) {
      setFieldErrors({ name: 'Ingresá el nombre de la moneda.' });
      return;
    }

    if (!symbolValue) {
      setFieldErrors({ symbol: 'Ingresá el símbolo.' });
      return;
    }

    if (!localeValue) {
      setFieldErrors({ locale: 'El locale es obligatorio (ej: es-AR).' });
      return;
    }

    setSaving(true);
    setFieldErrors({});

    const payload = {
      country_id: countryId,
      code: codeValue,
      name: nameValue,
      symbol: symbolValue,
      symbol_position: position,
      symbol_space: space,
      decimals,
      decimal_separator: decimalSep,
      thousand_separator: thousandSep,
      locale: localeValue,
      is_active: active,
    };

    try {
      const response = editing
        ? await api.updateCurrency(editing.id, payload)
        : await api.createCurrency(payload);

      setModalOpen(false);
      setEditing(null);
      setNotice(response.message);
      await fetchCurrencies();
    } catch (caught) {
      if (caught instanceof ApiError) {
        setFieldErrors({
          country_id: caught.errors?.country_id?.[0],
          code: caught.errors?.code?.[0],
          name: caught.errors?.name?.[0],
          symbol: caught.errors?.symbol?.[0],
          symbol_position: caught.errors?.symbol_position?.[0],
          decimals: caught.errors?.decimals?.[0],
          decimal_separator: caught.errors?.decimal_separator?.[0],
          thousand_separator: caught.errors?.thousand_separator?.[0],
          locale: caught.errors?.locale?.[0],
        });
        setFieldErrors((current) =>
          Object.values(current).some(Boolean) ? current : { code: caught.message },
        );
      } else {
        setFieldErrors({ code: 'No pudimos guardar la moneda.' });
      }
    } finally {
      setSaving(false);
    }
  }

  function confirmDelete(item: CurrencyItem) {
    if (!item.can_delete) {
      Alert.alert(
        'No se puede eliminar',
        'No se puede eliminar la moneda por defecto de la plataforma.',
      );
      return;
    }

    Alert.alert(
      'Eliminar moneda',
      `¿Estás seguro de eliminar "${item.code}"?`,
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

  async function remove(item: CurrencyItem) {
    setNotice(null);

    try {
      const response = await api.deleteCurrency(item.id);
      setNotice(response.message);
      await fetchCurrencies();
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
          item.code.toLowerCase().includes(term) ||
          item.name.toLowerCase().includes(term) ||
          item.symbol.toLowerCase().includes(term) ||
          (item.country?.name ?? '').toLowerCase().includes(term),
      )
    : items;

  const preview = formatCurrencyPreview({
    symbol: symbol || '$',
    symbol_position: position,
    symbol_space: space,
    decimals,
    decimal_separator: decimalSep,
    thousand_separator: thousandSep,
  });

  if (loading && items.length === 0) {
    return <LoadingScreen message="Cargando monedas…" />;
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
        <Text style={styles.title}>Monedas</Text>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Nueva moneda"
          onPress={openCreate}
          style={styles.addButton}
        >
          <Text style={styles.addText}>＋ Nueva</Text>
        </Pressable>
      </View>

      <View style={styles.listContainer}>
        <Text style={styles.summary}>
          {summary.total} monedas · {summary.active} activas
          {summary.default_code ? ` · defecto ${summary.default_code}` : ''}
        </Text>
        <Text style={styles.cardSub}>Una moneda por país: símbolo, posición, decimales y separadores.</Text>

        <ErrorBanner message={error} />

        {notice ? (
          <View style={styles.notice}>
            <Text style={styles.noticeText}>{notice}</Text>
          </View>
        ) : null}

        <TextField
          label="Buscar moneda"
          placeholder="Código, nombre, símbolo o país…"
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
                {term ? 'No se encontraron monedas' : 'Todavía no hay monedas'}
              </Text>
              <Text style={styles.emptyText}>
                {term
                  ? 'Probá con otro nombre.'
                  : 'Creá la primera moneda con el botón Nueva.'}
              </Text>
            </View>
          }
          renderItem={({ item }) => (
            <View style={styles.card}>
              <View style={styles.cardInfo}>
                <Text style={styles.cardTitle}>
                  {item.country?.name ?? 'Sin país'} · {item.code}
                </Text>
                <Text style={styles.cardMeta}>
                  {item.name} · {item.symbol} · {formatCurrencyPreview(item)}
                </Text>
                <View style={styles.metaRow}>
                  {item.is_default ? (
                    <View style={[styles.badge, styles.defaultBadge]}>
                      <Text style={[styles.badgeText, styles.defaultText]}>Por defecto</Text>
                    </View>
                  ) : null}
                  <View style={[styles.badge, item.is_active ? styles.activeBadge : styles.mutedBadge]}>
                    <Text style={[styles.badgeText, item.is_active ? styles.activeText : styles.mutedText]}>
                      {item.is_active ? 'Activa' : 'Inactiva'}
                    </Text>
                  </View>
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
                {editing ? 'Editar moneda' : 'Nueva moneda'}
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

            <FlatList
              data={[{ key: 'form' }]}
              keyExtractor={(row) => row.key}
              renderItem={() => (
                <View style={styles.modalBody}>
                  <ComboField
                    label="País"
                    value={countryId}
                    options={countryOptions.map((option) => ({
                      value: option.id,
                      label: option.name,
                    }))}
                    onChange={setCountryId}
                    placeholder={
                      countryOptions.length === 0
                        ? 'No hay países disponibles'
                        : 'Seleccioná un país'
                    }
                    disabled={countryOptions.length === 0}
                    error={fieldErrors.country_id}
                    searchPlaceholder="Buscar país…"
                  />

                  <TextField
                    label="Código ISO"
                    placeholder="Ej: ARS"
                    value={code}
                    onChangeText={(value) => setCode(value.toUpperCase())}
                    maxLength={3}
                    autoCapitalize="characters"
                    autoCorrect={false}
                    error={fieldErrors.code}
                  />

                  <TextField
                    label="Nombre"
                    placeholder="Ej: Peso argentino"
                    value={name}
                    onChangeText={setName}
                    maxLength={255}
                    error={fieldErrors.name}
                  />

                  <TextField
                    label="Símbolo"
                    placeholder="Ej: $"
                    value={symbol}
                    onChangeText={setSymbol}
                    maxLength={8}
                    error={fieldErrors.symbol}
                  />

                  <ComboField
                    label="Posición del símbolo"
                    value={position}
                    options={[
                      { value: 'before', label: 'Antes del monto' },
                      { value: 'after', label: 'Después del monto' },
                    ]}
                    onChange={setPosition}
                    error={fieldErrors.symbol_position}
                  />

                  <View style={styles.switchRow}>
                    <Text style={styles.switchLabel}>Espacio entre símbolo y monto</Text>
                    <Switch value={space} onValueChange={setSpace} />
                  </View>

                  <ComboField
                    label="Decimales"
                    value={decimals}
                    options={[0, 1, 2, 3, 4].map((value) => ({
                      value,
                      label: String(value),
                    }))}
                    onChange={setDecimals}
                    error={fieldErrors.decimals}
                  />

                  <ComboField
                    label="Separador decimal"
                    value={decimalSep}
                    options={[
                      { value: ',', label: 'Coma ( , )' },
                      { value: '.', label: 'Punto ( . )' },
                    ]}
                    onChange={setDecimalSep}
                    error={fieldErrors.decimal_separator}
                  />

                  <ComboField
                    label="Separador de miles"
                    value={thousandSep}
                    options={[
                      { value: '.', label: 'Punto ( . )' },
                      { value: ',', label: 'Coma ( , )' },
                      { value: ' ', label: 'Espacio' },
                    ]}
                    onChange={setThousandSep}
                    error={fieldErrors.thousand_separator}
                  />

                  <TextField
                    label="Locale"
                    placeholder="Ej: es-AR"
                    value={locale}
                    onChangeText={setLocale}
                    maxLength={12}
                    autoCapitalize="none"
                    autoCorrect={false}
                    error={fieldErrors.locale}
                  />

                  <View style={styles.switchRow}>
                    <Text style={styles.switchLabel}>Activa</Text>
                    <Switch value={active} onValueChange={setActive} />
                  </View>

                  <View style={styles.previewBox}>
                    <Text style={styles.previewLabel}>Vista previa</Text>
                    <Text style={styles.previewValue}>{preview}</Text>
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
              )}
            />
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
  defaultBadge: {
    backgroundColor: '#eff6ff',
  },
  defaultText: {
    color: '#1d4ed8',
  },
  activeBadge: {
    backgroundColor: colors.successSoft,
  },
  activeText: {
    color: colors.success,
  },
  mutedBadge: {
    backgroundColor: colors.border,
  },
  mutedText: {
    color: colors.muted,
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
    maxHeight: '90%',
    maxWidth: 520,
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
  previewBox: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  previewLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  previewValue: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
    marginTop: 2,
  },
  buttonGap: {
    height: spacing.sm,
  },
});
