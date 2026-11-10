import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  Image,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ComboField } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import {
  appendPhoto,
  getCurrentCoords,
  pickMultipleImages,
  pickSingleImage,
  takePhoto,
  type LocalPhoto,
} from '@/lib/media';
import { colors, radius, spacing } from '@/theme';
import type { ProviderCard, ServiceSubgroup } from '@/types/api';

type OrderType = 'EMERGENCY' | 'SCHEDULED' | 'QUOTE';

const ORDER_TYPES: { code: OrderType; title: string; hint: string }[] = [
  { code: 'EMERGENCY', title: '🚨 Urgencia', hint: 'Lo necesito ya, 24/7. Con foto del problema.' },
  { code: 'SCHEDULED', title: '📅 Cita', hint: 'Reservo día y hora, a domicilio o en el local.' },
  { code: 'QUOTE', title: '🧾 Presupuesto', hint: 'Describo el trabajo con fotos y espero propuestas.' },
];

const PAYMENT_METHODS = [
  { code: 'efectivo', label: 'Efectivo' },
  { code: 'transferencia', label: 'Transferencia' },
  { code: 'tarjeta', label: 'Tarjeta' },
  { code: 'mercado_pago', label: 'Mercado Pago' },
];

const MAX_QUOTE_PHOTOS = 5;

export default function NewRequestScreen() {
  const { providerId } = useLocalSearchParams<{ providerId?: string }>();

  const [orderType, setOrderType] = useState<OrderType | null>(null);
  const [subgroups, setSubgroups] = useState<ServiceSubgroup[]>([]);
  const [loadingSubgroups, setLoadingSubgroups] = useState(true);
  const [subgroupId, setSubgroupId] = useState<number | null>(null);
  const [provider, setProvider] = useState<ProviderCard | null>(null);

  // Buscador de prestador (opcional).
  const [providerSearch, setProviderSearch] = useState('');
  const [providerResults, setProviderResults] = useState<ProviderCard[]>([]);
  const [searchingProviders, setSearchingProviders] = useState(false);

  // Urgencia.
  const [photo, setPhoto] = useState<LocalPhoto | null>(null);
  const [photoSource, setPhotoSource] = useState<'gallery' | 'camera' | null>(null);
  const [description, setDescription] = useState('');
  const [address, setAddress] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('efectivo');
  const [coords, setCoords] = useState<{ latitude: number; longitude: number } | null>(null);
  const [locating, setLocating] = useState(false);

  // Cita.
  const [date, setDate] = useState('');
  const [time, setTime] = useState('');
  const [fulfillment, setFulfillment] = useState<'AT_HOME' | 'AT_MERCHANT_LOCATION'>('AT_HOME');
  const [paymentMode, setPaymentMode] = useState<'DEPOSIT' | 'FULL'>('FULL');
  const [scheduledPaymentMethod, setScheduledPaymentMethod] = useState('Efectivo');
  const [notes, setNotes] = useState('');
  const [addressStreet, setAddressStreet] = useState('');
  const [addressUnit, setAddressUnit] = useState('');
  const [addressNeighborhood, setAddressNeighborhood] = useState('');
  const [reminder, setReminder] = useState<'none' | 'whatsapp' | 'email'>('none');

  // Presupuesto.
  const [details, setDetails] = useState('');
  const [quotePhotos, setQuotePhotos] = useState<LocalPhoto[]>([]);

  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  // Rubros + prestador preseleccionado (si viene de la ficha).
  useEffect(() => {
    let active = true;

    api
      .serviceSubgroups()
      .then((response) => {
        if (active) {
          setSubgroups(response.data);
        }
      })
      .catch(() => {
        if (active) {
          setError('No pudimos cargar los rubros. Revisá tu conexión.');
        }
      })
      .finally(() => {
        if (active) {
          setLoadingSubgroups(false);
        }
      });

    const preselected = Number(providerId);

    if (providerId && Number.isInteger(preselected) && preselected > 0) {
      api
        .provider(preselected)
        .then((response) => {
          if (active) {
            setProvider({
              ...response.data,
              ratings_count: response.data.ratings_count,
            });
          }
        })
        .catch(() => {
          // Si el prestador ya no existe, se sigue sin prestador.
        });
    }

    return () => {
      active = false;
    };
  }, [providerId]);

  // Buscador de prestadores con debounce. Si el texto es muy corto no se
  // busca; los resultados viejos se limpian en el onChangeText (evento).
  useEffect(() => {
    if (providerSearch.trim().length < 2) {
      return;
    }

    const timer = setTimeout(() => {
      setSearchingProviders(true);

      api
        .providers({ search: providerSearch.trim(), per_page: 8 })
        .then((response) => setProviderResults(response.data))
        .catch(() => setProviderResults([]))
        .finally(() => setSearchingProviders(false));
    }, 500);

    return () => clearTimeout(timer);
  }, [providerSearch]);

  function requireTarget(): boolean {
    if (!subgroupId && !provider) {
      setError('Elegí un rubro o un prestador para tu solicitud.');
      return false;
    }

    return true;
  }

  async function pickEmergencyPhoto(from: 'gallery' | 'camera') {
    setPhotoSource(from);
    const picked = from === 'camera' ? await takePhoto() : await pickSingleImage();
    setPhotoSource(null);

    if (picked) {
      setPhoto(picked);
    }
  }

  async function handleLocate() {
    setLocating(true);
    const found = await getCurrentCoords();
    setLocating(false);

    if (found) {
      setCoords(found);
    } else {
      setError('No pudimos obtener tu ubicación. Podés escribir la dirección igual.');
    }
  }

  async function addQuotePhotos() {
    const remaining = MAX_QUOTE_PHOTOS - quotePhotos.length;

    if (remaining <= 0) {
      return;
    }

    const picked = await pickMultipleImages(remaining);
    setQuotePhotos((current) => [...current, ...picked].slice(0, MAX_QUOTE_PHOTOS));
  }

  function scheduledAtValid(): string | null {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(date) || !/^\d{2}:\d{2}$/.test(time)) {
      return null;
    }

    const when = new Date(`${date}T${time}:00`);

    if (Number.isNaN(when.getTime()) || when <= new Date()) {
      return null;
    }

    return `${date} ${time}`;
  }

  async function handleSubmit() {
    setError(null);
    setFieldErrors({});

    if (!orderType) {
      setError('Elegí qué tipo de servicio necesitás.');
      return;
    }

    if (!requireTarget()) {
      return;
    }

    setSubmitting(true);

    try {
      const target = subgroupId
        ? { sub_group_id: subgroupId }
        : { provider_id: provider?.id as number };

      let orderId: number;

      if (orderType === 'EMERGENCY') {
        if (!photo) {
          setError('La urgencia necesita una foto del problema.');
          setSubmitting(false);
          return;
        }

        const form = new FormData();
        if ('sub_group_id' in target) {
          form.append('sub_group_id', String(target.sub_group_id));
        } else {
          form.append('provider_id', String(target.provider_id));
        }
        appendPhoto(form, 'photo', photo);
        if (description.trim()) {
          form.append('description', description.trim());
        }
        if (address.trim()) {
          form.append('address', address.trim());
        }
        form.append('payment_method', paymentMethod);
        if (coords) {
          form.append('latitude', String(coords.latitude));
          form.append('longitude', String(coords.longitude));
        }

        const response = await api.createEmergency(form);
        orderId = response.data.order.id;
      } else if (orderType === 'SCHEDULED') {
        const scheduledAt = scheduledAtValid();

        if (!scheduledAt) {
          setError('Ingresá una fecha (AAAA-MM-DD) y hora (HH:MM) futuras.');
          setSubmitting(false);
          return;
        }

        if (!scheduledPaymentMethod.trim()) {
          setError('Elegí el medio de pago.');
          setSubmitting(false);
          return;
        }

        const payload: Record<string, unknown> = {
          ...target,
          scheduled_at: scheduledAt,
          fulfillment,
          payment_method: scheduledPaymentMethod.trim(),
          payment_mode: paymentMode,
        };

        if (notes.trim()) {
          payload.notes = notes.trim();
        }
        if (addressStreet.trim()) {
          payload.address_street = addressStreet.trim();
        }
        if (addressUnit.trim()) {
          payload.address_unit = addressUnit.trim();
        }
        if (addressNeighborhood.trim()) {
          payload.address_neighborhood = addressNeighborhood.trim();
        }
        if (reminder !== 'none') {
          payload.reminder_channel = reminder;
        }

        const response = await api.createScheduled(payload);
        orderId = response.data.order.id;
      } else {
        if (!details.trim()) {
          setError('Contanos qué trabajo necesitás.');
          setSubmitting(false);
          return;
        }

        if (quotePhotos.length === 0) {
          setError('El presupuesto necesita al menos una foto.');
          setSubmitting(false);
          return;
        }

        const form = new FormData();
        if ('sub_group_id' in target) {
          form.append('sub_group_id', String(target.sub_group_id));
        } else {
          form.append('provider_id', String(target.provider_id));
        }
        form.append('details', details.trim());
        quotePhotos.forEach((item) => appendPhoto(form, 'photos[]', item));

        const response = await api.createQuote(form);
        orderId = response.data.order.id;
      }

      router.replace({
        pathname: '/request/[id]',
        params: { id: String(orderId), created: '1' },
      });
    } catch (caught) {
      if (caught instanceof ApiError) {
        setError(caught.message);
        setFieldErrors(caught.errors ?? {});
      } else {
        setError('No pudimos enviar tu solicitud. Intentá de nuevo.');
      }
    } finally {
      setSubmitting(false);
    }
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
        <Text style={styles.title}>Nueva solicitud</Text>
        <View style={styles.headerSpacer} />
      </View>

      <ScrollView
        contentContainerStyle={styles.content}
        keyboardShouldPersistTaps="handled"
      >
        <ErrorBanner message={error} />

        <Text style={styles.label}>¿Qué necesitás?</Text>
        <View style={styles.typeList}>
          {ORDER_TYPES.map((type) => {
            const selected = orderType === type.code;

            return (
              <Pressable
                key={type.code}
                accessibilityRole="radio"
                accessibilityState={{ selected }}
                onPress={() => setOrderType(type.code)}
                style={[styles.typeCard, selected && styles.typeCardSelected]}
              >
                <Text style={styles.typeTitle}>{type.title}</Text>
                <Text style={styles.typeHint}>{type.hint}</Text>
              </Pressable>
            );
          })}
        </View>

        {orderType ? (
          <>
            {loadingSubgroups ? (
              <Text style={styles.hint}>Cargando rubros…</Text>
            ) : (
              <ComboField
                label="Rubro"
                value={subgroupId}
                options={subgroups.map((subgroup) => ({
                  value: subgroup.id,
                  label: subgroup.description,
                  hint:
                    subgroup.providers_count > 0
                      ? `${subgroup.providers_count} prestador(es)`
                      : undefined,
                }))}
                onChange={setSubgroupId}
                placeholder={
                  subgroups.length === 0
                    ? 'No hay rubros disponibles'
                    : 'Seleccioná un rubro'
                }
                disabled={subgroups.length === 0}
                error={fieldErrors.sub_group_id?.[0] ?? null}
                searchPlaceholder="Buscar rubro…"
              />
            )}

            <Text style={styles.label}>Prestador (opcional)</Text>
            {provider ? (
              <View style={styles.providerPicked}>
                <Text style={styles.providerPickedText} numberOfLines={1}>
                  ✓ {provider.business_name}
                </Text>
                <Pressable
                  accessibilityRole="button"
                  onPress={() => setProvider(null)}
                  style={styles.providerRemove}
                >
                  <Text style={styles.providerRemoveText}>Quitar</Text>
                </Pressable>
              </View>
            ) : (
              <>
                <Text style={styles.hint}>
                  Si no elegís, tu pedido va al pool del rubro.
                </Text>
                <TextField
                  label=""
                  value={providerSearch}
                  onChangeText={(text) => {
                    setProviderSearch(text);

                    if (text.trim().length < 2) {
                      setProviderResults([]);
                    }
                  }}
                  placeholder="Buscar prestador por nombre…"
                  autoCapitalize="none"
                  autoCorrect={false}
                />
                {searchingProviders ? (
                  <Text style={styles.hint}>Buscando…</Text>
                ) : (
                  providerResults.map((item) => (
                    <Pressable
                      key={item.id}
                      accessibilityRole="button"
                      onPress={() => {
                        setProvider(item);
                        setProviderSearch('');
                        setProviderResults([]);
                      }}
                      style={styles.providerRow}
                    >
                      <Text style={styles.providerRowText} numberOfLines={1}>
                        {item.business_name}
                        {item.zone ? ` · ${item.zone}` : ''}
                      </Text>
                    </Pressable>
                  ))
                )}
              </>
            )}

            {orderType === 'EMERGENCY' ? (
              <>
                <Text style={styles.label}>Foto del problema *</Text>
                {photo ? (
                  <View style={styles.photoPreview}>
                    <Image source={{ uri: photo.uri }} style={styles.photo} />
                    <Pressable
                      accessibilityRole="button"
                      onPress={() => setPhoto(null)}
                      style={styles.photoRemove}
                    >
                      <Text style={styles.providerRemoveText}>Cambiar</Text>
                    </Pressable>
                  </View>
                ) : (
                  <View style={styles.photoButtons}>
                    <Pressable
                      accessibilityRole="button"
                      disabled={photoSource !== null}
                      onPress={() => void pickEmergencyPhoto('camera')}
                      style={styles.photoButton}
                    >
                      <Text style={styles.photoButtonText}>
                        {photoSource === 'camera' ? 'Abriendo…' : '📷 Cámara'}
                      </Text>
                    </Pressable>
                    <Pressable
                      accessibilityRole="button"
                      disabled={photoSource !== null}
                      onPress={() => void pickEmergencyPhoto('gallery')}
                      style={styles.photoButton}
                    >
                      <Text style={styles.photoButtonText}>
                        {photoSource === 'gallery' ? 'Abriendo…' : '🖼️ Galería'}
                      </Text>
                    </Pressable>
                  </View>
                )}
                {fieldErrors.photo?.[0] ? (
                  <Text style={styles.fieldError}>{fieldErrors.photo[0]}</Text>
                ) : null}

                <TextField
                  label="Descripción (opcional)"
                  value={description}
                  onChangeText={setDescription}
                  placeholder="¿Qué pasó?"
                  multiline
                  numberOfLines={3}
                />

                <TextField
                  label="Dirección (opcional)"
                  value={address}
                  onChangeText={setAddress}
                  placeholder="Calle y número"
                />

                <Pressable
                  accessibilityRole="button"
                  disabled={locating}
                  onPress={() => void handleLocate()}
                  style={styles.ghostButton}
                >
                  <Text style={styles.ghostButtonText}>
                    {locating
                      ? 'Obteniendo ubicación…'
                      : coords
                        ? `📍 Ubicación lista (${coords.latitude.toFixed(4)}, ${coords.longitude.toFixed(4)})`
                        : '📍 Usar mi ubicación actual'}
                  </Text>
                </Pressable>

                <ComboField
                  label="Medio de pago"
                  value={paymentMethod}
                  options={PAYMENT_METHODS.map((method) => ({
                    value: method.code,
                    label: method.label,
                  }))}
                  onChange={setPaymentMethod}
                />
              </>
            ) : null}

            {orderType === 'SCHEDULED' ? (
              <>
                <View style={styles.row}>
                  <View style={styles.half}>
                    <TextField
                      label="Fecha (AAAA-MM-DD) *"
                      value={date}
                      onChangeText={setDate}
                      placeholder="2026-11-20"
                      keyboardType="numbers-and-punctuation"
                      autoCapitalize="none"
                      autoCorrect={false}
                      maxLength={10}
                    />
                  </View>
                  <View style={styles.half}>
                    <TextField
                      label="Hora (HH:MM) *"
                      value={time}
                      onChangeText={setTime}
                      placeholder="10:00"
                      keyboardType="numbers-and-punctuation"
                      autoCapitalize="none"
                      autoCorrect={false}
                      maxLength={5}
                    />
                  </View>
                </View>
                {fieldErrors.scheduled_at?.[0] ? (
                  <Text style={styles.fieldError}>{fieldErrors.scheduled_at[0]}</Text>
                ) : null}

                <ComboField
                  label="¿Dónde?"
                  value={fulfillment}
                  options={[
                    { value: 'AT_HOME', label: '🏠 A domicilio' },
                    { value: 'AT_MERCHANT_LOCATION', label: '🏪 En el local' },
                  ]}
                  onChange={setFulfillment}
                />

                <ComboField
                  label="Pago"
                  value={paymentMode}
                  options={[
                    { value: 'DEPOSIT', label: 'Seña' },
                    { value: 'FULL', label: 'Total' },
                  ]}
                  onChange={setPaymentMode}
                />

                <ComboField
                  label="Medio de pago"
                  value={scheduledPaymentMethod}
                  options={PAYMENT_METHODS.map((method) => ({
                    value: method.label,
                    label: method.label,
                  }))}
                  onChange={setScheduledPaymentMethod}
                />

                <TextField
                  label="Notas (opcional)"
                  value={notes}
                  onChangeText={setNotes}
                  placeholder="Detalles para el prestador"
                  multiline
                  numberOfLines={3}
                />

                <TextField
                  label="Calle y número (opcional)"
                  value={addressStreet}
                  onChangeText={setAddressStreet}
                  placeholder="Calle Falsa 123"
                />
                <View style={styles.row}>
                  <View style={styles.half}>
                    <TextField
                      label="Piso / Depto"
                      value={addressUnit}
                      onChangeText={setAddressUnit}
                      placeholder="2º B"
                    />
                  </View>
                  <View style={styles.half}>
                    <TextField
                      label="Barrio"
                      value={addressNeighborhood}
                      onChangeText={setAddressNeighborhood}
                      placeholder="Centro"
                    />
                  </View>
                </View>

                <ComboField
                  label="Recordatorio"
                  value={reminder}
                  options={[
                    { value: 'none', label: 'Sin recordatorio' },
                    { value: 'whatsapp', label: 'WhatsApp' },
                    { value: 'email', label: 'Email' },
                  ]}
                  onChange={setReminder}
                />
              </>
            ) : null}

            {orderType === 'QUOTE' ? (
              <>
                <TextField
                  label="¿Qué trabajo necesitás? *"
                  value={details}
                  onChangeText={setDetails}
                  placeholder="Describí el trabajo lo más completo posible"
                  multiline
                  numberOfLines={4}
                />
                {fieldErrors.details?.[0] ? (
                  <Text style={styles.fieldError}>{fieldErrors.details[0]}</Text>
                ) : null}

                <Text style={styles.label}>
                  Fotos ({quotePhotos.length}/{MAX_QUOTE_PHOTOS}) *
                </Text>
                <View style={styles.quotePhotos}>
                  {quotePhotos.map((item, index) => (
                    <Pressable
                      key={`${item.uri}-${index}`}
                      accessibilityRole="button"
                      onPress={() =>
                        setQuotePhotos((current) =>
                          current.filter((_, i) => i !== index),
                        )
                      }
                    >
                      <Image source={{ uri: item.uri }} style={styles.thumb} />
                      <Text style={styles.thumbRemove}>Quitar</Text>
                    </Pressable>
                  ))}

                  {quotePhotos.length < MAX_QUOTE_PHOTOS ? (
                    <Pressable
                      accessibilityRole="button"
                      onPress={() => void addQuotePhotos()}
                      style={styles.thumbAdd}
                    >
                      <Text style={styles.thumbAddText}>+ Agregar</Text>
                    </Pressable>
                  ) : null}
                </View>
              </>
            ) : null}

            <View style={styles.submit}>
              <PrimaryButton
                title="Enviar solicitud"
                onPress={handleSubmit}
                loading={submitting}
                disabled={submitting}
              />
            </View>
          </>
        ) : null}
      </ScrollView>
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
    fontSize: 18,
    fontWeight: '800',
  },
  headerSpacer: {
    minWidth: 70,
  },
  content: {
    padding: spacing.lg,
    paddingBottom: spacing.xxl,
  },
  label: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
    marginBottom: spacing.sm,
    marginTop: spacing.lg,
  },
  hint: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: spacing.sm,
  },
  typeList: {
    gap: spacing.sm,
  },
  typeCard: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    padding: spacing.lg,
  },
  typeCardSelected: {
    backgroundColor: colors.brandSoft,
    borderColor: colors.brand,
  },
  typeTitle: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '800',
  },
  typeHint: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  chip: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
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
    fontSize: 13,
    marginTop: spacing.xs,
  },
  providerPicked: {
    alignItems: 'center',
    backgroundColor: colors.successSoft,
    borderRadius: radius.md,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },
  providerPickedText: {
    color: colors.success,
    flex: 1,
    fontSize: 15,
    fontWeight: '700',
    marginRight: spacing.sm,
  },
  providerRemove: {
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
  },
  providerRemoveText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '700',
  },
  providerRow: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.xs,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },
  providerRowText: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  photoButtons: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  photoButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    flex: 1,
    paddingVertical: spacing.lg,
  },
  photoButtonText: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
  },
  photoPreview: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
  },
  photo: {
    borderRadius: radius.md,
    height: 140,
    width: 140,
  },
  photoRemove: {
    padding: spacing.md,
  },
  ghostButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.sm,
    paddingVertical: spacing.md,
  },
  ghostButtonText: {
    color: colors.brandDark,
    fontSize: 14,
    fontWeight: '700',
  },
  row: {
    flexDirection: 'row',
    gap: spacing.md,
  },
  half: {
    flex: 1,
  },
  quotePhotos: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
  },
  thumb: {
    borderRadius: radius.md,
    height: 100,
    width: 100,
  },
  thumbRemove: {
    color: colors.danger,
    fontSize: 12,
    fontWeight: '600',
    marginTop: 2,
    textAlign: 'center',
  },
  thumbAdd: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderStyle: 'dashed',
    borderWidth: 1,
    height: 100,
    justifyContent: 'center',
    width: 100,
  },
  thumbAddText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '700',
  },
  submit: {
    marginTop: spacing.xl,
  },
});
