import { router, useLocalSearchParams } from 'expo-router';
import {
  AudioModule,
  RecordingPresets,
  setAudioModeAsync,
  useAudioRecorder,
  useAudioRecorderState,
} from 'expo-audio';
import { useEffect, useRef, useState } from 'react';
import {
  Alert,
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
import { ProviderAdCard } from '@/components/ProviderAdCard';
import { TextField } from '@/components/TextField';
import {
  appendAudio,
  appendPhoto,
  getCurrentCoords,
  pickMultipleImages,
  pickSingleImage,
  takePhoto,
  type LocalAudio,
  type LocalPhoto,
} from '@/lib/media';
import { colors, radius, spacing } from '@/theme';
import type { ProviderCard, ProviderDetail, ServiceSubgroup } from '@/types/api';

type OrderType = 'EMERGENCY' | 'SCHEDULED' | 'QUOTE';

const ORDER_TYPES: { code: OrderType; title: string; hint: string }[] = [
  { code: 'EMERGENCY', title: '🚨 Urgencia', hint: 'Lo necesito ya, 24/7. Con foto del problema.' },
  { code: 'SCHEDULED', title: '📅 Cita', hint: 'Reservo día y hora, a domicilio o en el local.' },
  { code: 'QUOTE', title: '🧾 Presupuesto', hint: 'Describo el trabajo con fotos y espero propuestas.' },
];

/** Valida el tipo que puede llegar desde una tarjeta de prestador. */
function isOrderType(value: unknown): value is OrderType {
  return (
    value === 'EMERGENCY' || value === 'SCHEDULED' || value === 'QUOTE'
  );
}

const PAYMENT_METHODS = [
  { code: 'efectivo', label: 'Efectivo' },
  { code: 'transferencia', label: 'Transferencia' },
  { code: 'tarjeta', label: 'Tarjeta' },
  { code: 'mercado_pago', label: 'Mercado Pago' },
];

/** Medios de pago rápidos para la tarifa base de la urgencia (estilo web). */
const EMERGENCY_PAYMENT_METHODS = [
  { code: 'mercado_pago', label: 'Mercado Pago', icon: 'MP', mp: true },
  { code: 'tarjeta', label: 'Tarjeta', icon: '💳', mp: false },
  { code: 'efectivo', label: 'Efectivo', icon: '💵', mp: false },
];

/** Opciones rápidas de urgencia: un toque completa la descripción. */
const PROBLEM_CHIPS = [
  { key: 'agua', label: '💧 Pérdida de agua', problem: 'Pérdida de agua' },
  { key: 'inundacion', label: '🌊 Inundación', problem: 'Inundación' },
  { key: 'luz', label: '💡 Sin luz', problem: 'Sin luz' },
  { key: 'gas', label: '🔥 Pérdida de gas', problem: 'Pérdida de gas' },
  { key: 'otro', label: '📦 Otro', problem: '' },
];

const MAX_QUOTE_PHOTOS = 5;
const MAX_PHOTO_BYTES = 5 * 1024 * 1024;
const MAX_AUDIO_BYTES = 8 * 1024 * 1024;
const MAX_AUDIO_SECONDS = 120;

/** El azul oscuro de las acciones secundarias de la web. */
const NAVY = '#0c2a4d';

type SelectedProvider = ProviderCard &
  Partial<Pick<ProviderDetail, 'emergency_count'>>;

/** Extensión/MIME del archivo temporal que devuelve expo-audio. */
function toLocalAudio(uri: string): LocalAudio {
  const raw = uri.split('?')[0].split('.').pop()?.toLowerCase() ?? '';
  const ext = ['mp3', 'm4a', 'ogg', 'oga', 'webm', 'wav', 'aac'].includes(raw)
    ? raw
    : 'm4a';

  return {
    uri,
    name: `audio_${Date.now()}.${ext}`,
    mimeType: ext === 'oga' ? 'audio/ogg' : `audio/${ext}`,
  };
}

function formatDuration(millis: number): string {
  const total = Math.max(0, Math.floor(millis / 1000));
  const minutes = Math.floor(total / 60);
  const seconds = total % 60;

  return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

function formatSize(bytes: number): string {
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function NewRequestScreen() {
  const { providerId, service } = useLocalSearchParams<{
    providerId?: string;
    service?: string;
  }>();

  const initialService = Array.isArray(service) ? service[0] : service;

  // Cuando se llega desde una tarjeta, el tipo y el prestador quedan fijos:
  // no se puede cambiar el servicio ni buscar otro prestador, y el rubro
  // deja de mostrarse porque el pedido va directo a ese prestador.
  const requestedProviderId = Number(providerId);
  const hasRequestedProvider =
    Boolean(providerId) && Number.isInteger(requestedProviderId) && requestedProviderId > 0;
  const serviceLocked = isOrderType(initialService);
  const [providerLoadFailed, setProviderLoadFailed] = useState(false);
  const providerLocked = hasRequestedProvider && !providerLoadFailed;

  const [orderType, setOrderType] = useState<OrderType | null>(
    isOrderType(initialService) ? initialService : null,
  );
  const [subgroups, setSubgroups] = useState<ServiceSubgroup[]>([]);
  const [loadingSubgroups, setLoadingSubgroups] = useState(true);
  const [subgroupId, setSubgroupId] = useState<number | null>(null);
  const [provider, setProvider] = useState<SelectedProvider | null>(null);

  // Buscador y tarjetas de prestador (opcional).
  const [providerSearch, setProviderSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [providerTab, setProviderTab] = useState<'favorites' | 'all'>('all');
  const [providerResults, setProviderResults] = useState<ProviderCard[]>([]);
  const [providersPage, setProvidersPage] = useState(1);
  const [providersLastPage, setProvidersLastPage] = useState(1);
  const [providersCounts, setProvidersCounts] = useState({ all: 0, favorites: 0 });
  const [loadingProviders, setLoadingProviders] = useState(false);
  const [loadingMoreProviders, setLoadingMoreProviders] = useState(false);
  const [favoriteBusyId, setFavoriteBusyId] = useState<number | null>(null);
  const defaultTabApplied = useRef(false);

  // Urgencia.
  const [photo, setPhoto] = useState<LocalPhoto | null>(null);
  const [photoSource, setPhotoSource] = useState<'gallery' | 'camera' | null>(null);
  const [problemChip, setProblemChip] = useState<string | null>(null);
  const [description, setDescription] = useState('');
  const [address, setAddress] = useState('');
  const [paymentMethod, setPaymentMethod] = useState('mercado_pago');
  const [coords, setCoords] = useState<{ latitude: number; longitude: number } | null>(null);
  const [locating, setLocating] = useState(false);
  const [audio, setAudio] = useState<LocalAudio | null>(null);
  const [audioSize, setAudioSize] = useState<number | null>(null);
  const [audioBusy, setAudioBusy] = useState(false);
  const addressPrefilled = useRef(false);
  const audioTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const audioRecorder = useAudioRecorder(RecordingPresets.HIGH_QUALITY);
  const recorderState = useAudioRecorderState(audioRecorder, 500);

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

    if (hasRequestedProvider) {
      api
        .provider(requestedProviderId)
        .then((response) => {
          if (active) {
            setProvider({
              ...response.data,
              ratings_count: response.data.ratings_count,
            });
          }
        })
        .catch(() => {
          // Si el prestador ya no existe, se libera el flujo normal.
          if (active) {
            setProviderLoadFailed(true);
          }
        });
    }

    return () => {
      active = false;
    };
  }, [hasRequestedProvider, requestedProviderId]);

  // Igual que la web: al entrar a una urgencia se precarga la dirección
  // principal del perfil (solo si el usuario todavía no escribió nada).
  useEffect(() => {
    if (orderType !== 'EMERGENCY' || addressPrefilled.current) {
      return;
    }

    addressPrefilled.current = true;
    let active = true;

    api
      .profileAddress()
      .then((response) => {
        if (!active || !response.address) {
          return;
        }

        const primary = response.address;
        const formatted = [
          primary.street,
          primary.number,
          primary.floor_apartment,
        ]
          .filter((part) => part && String(part).trim().length > 0)
          .join(' ');

        if (formatted) {
          setAddress((current) => (current.trim() ? current : formatted));
        }
      })
      .catch(() => {
        // Sin dirección guardada se puede escribir a mano igual.
      });

    return () => {
      active = false;
    };
  }, [orderType]);

  // Al detenerse la grabación se guarda la nota de voz para adjuntarla al
  // enviar la urgencia.
  useEffect(() => {
    return () => {
      if (audioTimer.current) {
        clearTimeout(audioTimer.current);
      }
    };
  }, []);

  // Buscador de prestadores con debounce.
  useEffect(() => {
    if (!orderType || providerLocked) {
      return;
    }

    const next = providerSearch.trim();

    if (next === debouncedSearch) {
      return;
    }

    const timer = setTimeout(() => {
      setLoadingProviders(true);
      setDebouncedSearch(next);
    }, 400);

    return () => clearTimeout(timer);
  }, [providerSearch, debouncedSearch, orderType, providerLocked]);

  // Tarjetas de prestadores filtradas por tipo de pedido y pestaña.
  useEffect(() => {
    if (!orderType || providerLocked) {
      return;
    }

    let active = true;

    api
      .providers({
        ...(debouncedSearch ? { search: debouncedSearch } : {}),
        type: orderType,
        favorite: providerTab === 'favorites',
        per_page: 8,
        page: 1,
      })
      .then((response) => {
        if (!active) {
          return;
        }

        const favorites = response.meta.favorites_count ?? 0;
        setProvidersCounts({
          all: response.meta.total_count ?? response.meta.total,
          favorites,
        });

        // Igual que la web: si tenés favoritos, arranca en esa pestaña.
        if (
          !defaultTabApplied.current &&
          providerTab === 'all' &&
          debouncedSearch === '' &&
          favorites > 0
        ) {
          defaultTabApplied.current = true;
          setLoadingProviders(true);
          setProviderTab('favorites');
          return;
        }

        defaultTabApplied.current = true;
        setProviderResults(response.data);
        setProvidersPage(response.meta.current_page);
        setProvidersLastPage(response.meta.last_page);
        setError(null);
      })
      .catch(() => {
        if (active) {
          setProviderResults([]);
        }
      })
      .finally(() => {
        if (active) {
          setLoadingProviders(false);
        }
      });

    return () => {
      active = false;
    };
  }, [orderType, debouncedSearch, providerTab, providerLocked]);

  function handleOrderType(code: OrderType) {
    setOrderType(code);
    setLoadingProviders(true);

    // Si el prestador elegido no ofrece este servicio, se vuelve al pool.
    if (provider && !provider.service_types.some((type) => type.code === code)) {
      setProvider(null);
    }
  }

  function handleProviderTab(tab: 'favorites' | 'all') {
    if (providerTab === tab) {
      return;
    }

    setLoadingProviders(true);
    setProviderTab(tab);
  }

  function pickProvider(item: ProviderCard, serviceCode?: string) {
    setProvider(item);
    // El rubro tiene prioridad al enviar: se limpia al elegir prestador.
    setSubgroupId(null);

    // La tarjeta no trae las urgencias atendidas: se completan con el detalle
    // para replicar la cabecera de la web (★ 5.0 (1 urgencia)).
    api
      .provider(item.id)
      .then((response) => {
        setProvider((current) =>
          current && current.id === item.id ? response.data : current,
        );
      })
      .catch(() => {
        // Sin detalle la cabecera usa los datos de la tarjeta.
      });

    if (serviceCode) {
      if (serviceCode !== orderType) {
        setLoadingProviders(true);
      }

      setOrderType(serviceCode as OrderType);
    }

    setProviderSearch('');
  }

  async function toggleProviderFavorite(item: ProviderCard) {
    if (favoriteBusyId !== null) {
      return;
    }

    setFavoriteBusyId(item.id);

    try {
      const response = await api.toggleProviderFavorite(item.id);

      setProviderResults((current) =>
        providerTab === 'favorites' && !response.is_favorite
          ? current.filter((provider) => provider.id !== item.id)
          : current.map((provider) =>
              provider.id === item.id
                ? { ...provider, is_favorite: response.is_favorite }
                : provider,
            ),
      );
      setProvider((current) =>
        current && current.id === item.id
          ? { ...current, is_favorite: response.is_favorite }
          : current,
      );
      setProvidersCounts((current) => ({
        ...current,
        // El badge cuenta los favoritos del filtro actual, no el total
        // global que devuelve el endpoint.
        favorites: Math.max(0, current.favorites + (response.is_favorite ? 1 : -1)),
      }));
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos actualizar tus favoritos.',
      );
    } finally {
      setFavoriteBusyId(null);
    }
  }

  async function loadMoreProviders() {
    if (!orderType || loadingMoreProviders || providersPage >= providersLastPage) {
      return;
    }

    setLoadingMoreProviders(true);

    try {
      const response = await api.providers({
        ...(debouncedSearch ? { search: debouncedSearch } : {}),
        type: orderType,
        favorite: providerTab === 'favorites',
        per_page: 8,
        page: providersPage + 1,
      });

      setProviderResults((current) => [...current, ...response.data]);
      setProvidersPage(response.meta.current_page);
      setProvidersLastPage(response.meta.last_page);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar más prestadores.',
      );
    } finally {
      setLoadingMoreProviders(false);
    }
  }

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

    if (!picked) {
      return;
    }

    if (picked.size && picked.size > MAX_PHOTO_BYTES) {
      setError(
        `La foto pesa ${formatSize(picked.size)} y el máximo es 5 MB. Elegí una más liviana.`,
      );
      return;
    }

    setError(null);
    setPhoto(picked);
  }

  function chooseEmergencyPhoto() {
    Alert.alert(
      'Foto del problema',
      'Adjuntá una foto de la urgencia (máximo 5 MB).',
      [
        { text: 'Cámara', onPress: () => void pickEmergencyPhoto('camera') },
        { text: 'Galería', onPress: () => void pickEmergencyPhoto('gallery') },
        { text: 'Cancelar', style: 'cancel' },
      ],
    );
  }

  async function finishRecording() {
    if (audioTimer.current) {
      clearTimeout(audioTimer.current);
      audioTimer.current = null;
    }

    setAudioBusy(true);

    try {
      await audioRecorder.stop();
      const status = audioRecorder.getStatus();
      const uri = audioRecorder.uri ?? status.url;

      if (!uri) {
        setError('No pudimos guardar el audio. Intentá de nuevo.');
        return;
      }

      if (status.fileSize > MAX_AUDIO_BYTES) {
        setAudio(null);
        setAudioSize(null);
        setError('El audio supera los 8 MB. Grabá una nota más corta.');
        return;
      }

      setAudio(toLocalAudio(uri));
      setAudioSize(status.fileSize || null);
    } catch {
      setError('No pudimos guardar el audio. Intentá de nuevo.');
    } finally {
      setAudioBusy(false);
    }
  }

  async function toggleRecording() {
    if (audioBusy) {
      return;
    }

    if (recorderState.isRecording || audioRecorder.isRecording) {
      await finishRecording();
      return;
    }

    setAudioBusy(true);

    try {
      setError(null);

      const permission = await AudioModule.requestRecordingPermissionsAsync();

      if (!permission.granted) {
        Alert.alert(
          'Micrófono',
          'Necesitamos permiso para grabar una nota de voz. Podés habilitarlo en los ajustes del teléfono.',
        );
        return;
      }

      await setAudioModeAsync({
        allowsRecording: true,
        playsInSilentMode: true,
      });
      await audioRecorder.prepareToRecordAsync();
      audioRecorder.record();

      // Tope de duración: 2 minutos mantienen el archivo por debajo de 8 MB.
      audioTimer.current = setTimeout(() => {
        void finishRecording();
      }, MAX_AUDIO_SECONDS * 1000);
    } catch {
      setError('No pudimos grabar el audio. Intentá de nuevo.');
    } finally {
      setAudioBusy(false);
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

  function handleProblemChip(chip: (typeof PROBLEM_CHIPS)[number]) {
    // Tocar el chip activo lo apaga y limpia la descripción, igual que la web.
    if (problemChip === chip.key) {
      setProblemChip(null);
      setDescription('');
      return;
    }

    setProblemChip(chip.key);
    setDescription(chip.problem);
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

        if (!description.trim()) {
          setError(
            'Contanos qué pasa: tocá una opción rápida o escribí una descripción del problema.',
          );
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
        if (audio) {
          appendAudio(form, 'audio', audio);
        }
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

  const emergencyProvider = orderType === 'EMERGENCY' ? provider : null;
  const emergencyCount = provider?.emergency_count ?? 0;
  const providerScore = Number(provider?.rating ?? 0);
  const providerRubro = provider?.subgroups?.[0]?.description ?? 'Servicios del hogar';
  const providerMeta = provider
    ? `${providerRubro}${provider.zone ? ` · ${provider.zone}` : ''}`
    : '';
  const emergencyServiceLabel =
    provider?.service_types.find((type) => type.code === 'EMERGENCY')?.name ??
    'Urgencias 24/7';
  const baseFee = provider?.base_visit_fee ?? null;
  const hasBaseFee = baseFee !== null && Number(baseFee) > 0;
  const baseFeeLabel = hasBaseFee
    ? `$${Number(baseFee).toLocaleString('es-AR', { maximumFractionDigits: 2 })}`
    : 'A convenir';
  const emergencyHasContent = Boolean(description.trim());

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

        {emergencyProvider ? null : (
          <>
            <Text style={styles.label}>¿Qué necesitás?</Text>
            {serviceLocked && orderType ? (
              <View style={[styles.typeCard, styles.typeCardSelected]}>
                <Text style={styles.typeTitle}>
                  {ORDER_TYPES.find((type) => type.code === orderType)?.title ?? orderType}
                </Text>
                <Text style={styles.typeHint}>
                  Elegido desde la tarjeta del prestador.
                </Text>
              </View>
            ) : (
              <View style={styles.typeList}>
                {ORDER_TYPES.map((type) => {
                  const selected = orderType === type.code;

                  return (
                    <Pressable
                      key={type.code}
                      accessibilityRole="radio"
                      accessibilityState={{ selected }}
                      onPress={() => handleOrderType(type.code)}
                      style={[styles.typeCard, selected && styles.typeCardSelected]}
                    >
                      <Text style={styles.typeTitle}>{type.title}</Text>
                      <Text style={styles.typeHint}>{type.hint}</Text>
                    </Pressable>
                  );
                })}
              </View>
            )}
          </>
        )}

        {orderType ? (
          <>
            {!providerLocked && !provider ? (
              loadingSubgroups ? (
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
              )
            ) : null}

            {emergencyProvider ? null : (
              <>
                <Text style={styles.label}>
                  {providerLocked ? 'Prestador' : 'Prestador (opcional)'}
                </Text>
            {provider ? (
              <View style={styles.providerPicked}>
                <Text style={styles.providerPickedText} numberOfLines={1}>
                  ✓ {provider.business_name}
                </Text>
                {!providerLocked ? (
                  <Pressable
                    accessibilityRole="button"
                    onPress={() => setProvider(null)}
                    style={styles.providerRemove}
                  >
                    <Text style={styles.providerRemoveText}>Quitar</Text>
                  </Pressable>
                ) : null}
              </View>
            ) : providerLocked ? (
              <Text style={styles.hint}>Cargando prestador…</Text>
            ) : (
              <>
                <Text style={styles.hint}>
                  Si no elegís, tu pedido va al pool del rubro.
                </Text>
                <TextField
                  label=""
                  value={providerSearch}
                  onChangeText={setProviderSearch}
                  placeholder="Buscar prestador por nombre…"
                  autoCapitalize="none"
                  autoCorrect={false}
                />

                <View style={styles.providerTabs}>
                  <Pressable
                    accessibilityRole="tab"
                    accessibilityState={{ selected: providerTab === 'favorites' }}
                    onPress={() => handleProviderTab('favorites')}
                    style={[styles.providerTab, providerTab === 'favorites' && styles.providerTabActive]}
                  >
                    <Text
                      style={[
                        styles.providerTabText,
                        providerTab === 'favorites' && styles.providerTabTextActive,
                      ]}
                    >
                      ★ Favoritos
                    </Text>
                    <View style={styles.providerTabBadge}>
                      <Text style={styles.providerTabBadgeText}>
                        {providersCounts.favorites}
                      </Text>
                    </View>
                  </Pressable>

                  <Pressable
                    accessibilityRole="tab"
                    accessibilityState={{ selected: providerTab === 'all' }}
                    onPress={() => handleProviderTab('all')}
                    style={[styles.providerTab, providerTab === 'all' && styles.providerTabActive]}
                  >
                    <Text
                      style={[
                        styles.providerTabText,
                        providerTab === 'all' && styles.providerTabTextActive,
                      ]}
                    >
                      Todos
                    </Text>
                    <View style={styles.providerTabBadge}>
                      <Text style={styles.providerTabBadgeText}>{providersCounts.all}</Text>
                    </View>
                  </Pressable>
                </View>

                {loadingProviders ? (
                  <Text style={styles.hint}>Cargando prestadores…</Text>
                ) : providerResults.length === 0 ? (
                  <Text style={styles.hint}>
                    {providerTab === 'favorites'
                      ? 'Todavía no tenés prestadores favoritos para este servicio.'
                      : 'No encontramos prestadores para este servicio.'}
                  </Text>
                ) : (
                  providerResults.map((item) => (
                    <ProviderAdCard
                      key={item.id}
                      provider={item}
                      onPress={() => pickProvider(item)}
                      onSelectService={(code) => pickProvider(item, code)}
                      onToggleFavorite={() => void toggleProviderFavorite(item)}
                      favoriteBusy={favoriteBusyId === item.id}
                    />
                  ))
                )}

                {!loadingProviders && providersPage < providersLastPage ? (
                  <Pressable
                    accessibilityRole="button"
                    disabled={loadingMoreProviders}
                    onPress={() => void loadMoreProviders()}
                    style={styles.providerMore}
                  >
                    <Text style={styles.providerMoreText}>
                      {loadingMoreProviders ? 'Cargando…' : 'Ver más prestadores'}
                    </Text>
                  </Pressable>
                ) : null}
              </>
                )}
              </>
            )}

            {orderType === 'EMERGENCY' ? (
              <>
                {emergencyProvider ? (
                  <View style={styles.providerHeader}>
                    <View style={styles.providerHeaderTop}>
                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Cambiar de prestador"
                        onPress={() =>
                          providerLocked ? router.back() : setProvider(null)
                        }
                        style={styles.providerBack}
                      >
                        <Text style={styles.providerBackText}>←</Text>
                      </Pressable>

                      <View style={styles.providerAvatar}>
                        <Text style={styles.providerAvatarText}>
                          {(emergencyProvider.business_name || '?')
                            .charAt(0)
                            .toUpperCase()}
                        </Text>
                      </View>

                      <View style={styles.providerHeaderInfo}>
                        <View style={styles.providerNameRow}>
                          <Text style={styles.providerName} numberOfLines={1}>
                            {emergencyProvider.business_name}
                          </Text>
                          {emergencyProvider.is_verified ? (
                            <View style={styles.verifiedBadge}>
                              <Text style={styles.verifiedText}>✓ Verificado</Text>
                            </View>
                          ) : null}
                        </View>

                        <Text style={styles.providerMeta} numberOfLines={1}>
                          {providerMeta}
                        </Text>

                        <View style={styles.providerBadges}>
                          <Text style={styles.providerRating}>
                            {providerScore > 0
                              ? `★ ${providerScore.toFixed(1)}`
                              : '★ Nuevo'}
                            {emergencyCount > 0 ? (
                              <Text style={styles.providerRatingCount}>
                                {` (${emergencyCount} urgencia${
                                  emergencyCount === 1 ? '' : 's'
                                })`}
                              </Text>
                            ) : null}
                          </Text>

                          <View
                            style={[
                              styles.statusBadge,
                              emergencyProvider.is_available_now
                                ? styles.statusBadgeOpen
                                : styles.statusBadgeClosed,
                            ]}
                          >
                            <Text
                              style={[
                                styles.statusText,
                                emergencyProvider.is_available_now
                                  ? styles.statusTextOpen
                                  : styles.statusTextClosed,
                              ]}
                            >
                              {emergencyProvider.is_available_now
                                ? '🟢 Disponible Ahora'
                                : `🟡 ${emergencyProvider.availability_label}`}
                            </Text>
                          </View>
                        </View>
                      </View>
                    </View>

                    <View style={styles.providerServiceRow}>
                      <Text style={styles.providerService}>
                        {emergencyServiceLabel}
                      </Text>
                      <Text style={styles.providerServiceNote}>
                        El precio lo define el profesional
                      </Text>
                    </View>
                  </View>
                ) : null}

                {emergencyProvider ? (
                  <View style={styles.feeCard}>
                    <View style={styles.feeIcon}>
                      <Text style={styles.feeIconText}>🔔</Text>
                    </View>
                    <View style={styles.feeBody}>
                      <Text style={styles.feeTitle}>
                        TARIFA BASE POR URGENCIA / VISITA
                      </Text>
                      <Text
                        style={[
                          styles.feeAmount,
                          !hasBaseFee && styles.feeAmountPending,
                        ]}
                      >
                        {baseFeeLabel}
                      </Text>
                      <Text style={styles.feeNote}>
                        {hasBaseFee
                          ? 'Se abona al confirmar el trabajo. El profesional puede ajustar el monto final en el lugar.'
                          : 'El profesional confirma el monto de la visita antes de empezar.'}
                      </Text>
                    </View>
                  </View>
                ) : null}

                <Text style={styles.sectionTitle}>CONTANOS EL PROBLEMA</Text>

                <View style={styles.chips}>
                  {PROBLEM_CHIPS.map((chip) => {
                    const selected = problemChip === chip.key;

                    return (
                      <Pressable
                        key={chip.key}
                        accessibilityRole="button"
                        accessibilityState={{ selected }}
                        onPress={() => handleProblemChip(chip)}
                        style={[styles.chip, selected && styles.chipSelected]}
                      >
                        <Text
                          style={[
                            styles.chipText,
                            selected && styles.chipTextSelected,
                          ]}
                        >
                          {chip.label}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>

                <TextField
                  label="Descripción (opcional)"
                  value={description}
                  onChangeText={(value) => {
                    setDescription(value);

                    // Escribir a mano suelta el chip activo, como en la web.
                    if (problemChip) {
                      setProblemChip(null);
                    }
                  }}
                  placeholder="Ej: Pérdida de gas bajo la bacha de la cocina"
                  multiline
                  numberOfLines={3}
                  maxLength={1000}
                  error={fieldErrors.description?.[0] ?? null}
                />

                <TextField
                  label="Dirección donde te urge (opcional)"
                  value={address}
                  onChangeText={setAddress}
                  placeholder="Calle, número, piso/depto"
                  maxLength={500}
                  error={fieldErrors.address?.[0] ?? null}
                />

                <Pressable
                  accessibilityRole="button"
                  disabled={locating}
                  onPress={() => void handleLocate()}
                  style={[
                    styles.locateButton,
                    locating && styles.locateButtonDisabled,
                  ]}
                >
                  <Text style={styles.locateButtonText}>
                    {locating
                      ? 'Obteniendo ubicación…'
                      : '📍 Usar mi ubicación actual'}
                  </Text>
                </Pressable>
                {coords ? (
                  <Text style={styles.locateStatus}>
                    ✅ Ubicación lista ({coords.latitude.toFixed(4)},{' '}
                    {coords.longitude.toFixed(4)})
                  </Text>
                ) : null}

                <View style={styles.mediaRow}>
                  <View style={styles.mediaCol}>
                    <Text style={styles.mediaLabel}>
                      Foto del problema (obligatoria, máx 5 MB)
                    </Text>
                    <Pressable
                      accessibilityRole="button"
                      disabled={photoSource !== null}
                      onPress={chooseEmergencyPhoto}
                      style={[
                        styles.mediaButton,
                        photo ? styles.mediaButtonReady : null,
                      ]}
                    >
                      <View style={styles.mediaIcon}>
                        <Text style={styles.mediaIconText}>📷</Text>
                      </View>
                      <Text style={styles.mediaButtonText}>
                        {photoSource
                          ? 'Abriendo…'
                          : photo
                            ? 'Cambiar Foto'
                            : 'Tomar / Subir Foto'}
                      </Text>
                    </Pressable>

                    {photo ? (
                      <View style={styles.mediaInfo}>
                        <Image
                          source={{ uri: photo.uri }}
                          style={styles.mediaThumb}
                        />
                        <View style={styles.mediaInfoBody}>
                          <Text style={styles.mediaInfoText} numberOfLines={1}>
                            {photo.name}
                          </Text>
                          {photo.size ? (
                            <Text style={styles.mediaInfoSize}>
                              {formatSize(photo.size)}
                            </Text>
                          ) : null}
                          <Pressable
                            accessibilityRole="button"
                            onPress={() => setPhoto(null)}
                          >
                            <Text style={styles.mediaRemove}>Quitar</Text>
                          </Pressable>
                        </View>
                      </View>
                    ) : null}

                    {fieldErrors.photo?.[0] ? (
                      <Text style={styles.fieldError}>
                        {fieldErrors.photo[0]}
                      </Text>
                    ) : null}
                  </View>

                  <View style={styles.mediaCol}>
                    <Text style={styles.mediaLabel}>
                      Audio (opcional, máx 8 MB)
                    </Text>
                    <Pressable
                      accessibilityRole="button"
                      disabled={audioBusy || Boolean(audio)}
                      onPress={() => void toggleRecording()}
                      style={[
                        styles.mediaButton,
                        recorderState.isRecording
                          ? styles.mediaButtonRecording
                          : null,
                        audio ? styles.mediaButtonReady : null,
                      ]}
                    >
                      <View style={styles.mediaIcon}>
                        <Text style={styles.mediaIconText}>
                          {recorderState.isRecording ? '⏹️' : '🎙️'}
                        </Text>
                      </View>
                      <Text
                        style={[
                          styles.mediaButtonText,
                          recorderState.isRecording
                            ? styles.mediaButtonTextRecording
                            : null,
                        ]}
                      >
                        {audioBusy
                          ? 'Procesando…'
                          : recorderState.isRecording
                            ? `Detener ${formatDuration(
                                recorderState.durationMillis,
                              )}`
                            : audio
                              ? 'Audio listo'
                              : 'Grabar Audio'}
                      </Text>
                    </Pressable>

                    {recorderState.isRecording ? (
                      <Text style={styles.recordingHint}>
                        Grabando… máximo 2 minutos.
                      </Text>
                    ) : null}

                    {audio ? (
                      <View style={styles.mediaInfo}>
                        <View style={styles.mediaInfoBody}>
                          <Text style={styles.mediaInfoText} numberOfLines={1}>
                            {audio.name}
                          </Text>
                          {audioSize ? (
                            <Text style={styles.mediaInfoSize}>
                              {formatSize(audioSize)}
                            </Text>
                          ) : null}
                          <Pressable
                            accessibilityRole="button"
                            onPress={() => {
                              setAudio(null);
                              setAudioSize(null);
                            }}
                          >
                            <Text style={styles.mediaRemove}>Quitar</Text>
                          </Pressable>
                        </View>
                      </View>
                    ) : null}
                  </View>
                </View>

                <View style={styles.paymentBlock}>
                  <Text style={styles.mediaLabel}>
                    Medio de pago de la tarifa base
                  </Text>
                  <View style={styles.paymentRow}>
                    {EMERGENCY_PAYMENT_METHODS.map((method) => {
                      const selected = paymentMethod === method.code;

                      return (
                        <Pressable
                          key={method.code}
                          accessibilityRole="radio"
                          accessibilityState={{ selected }}
                          onPress={() => setPaymentMethod(method.code)}
                          style={[
                            styles.paymentChip,
                            selected && styles.paymentChipSelected,
                          ]}
                        >
                          {method.mp ? (
                            <View style={styles.mpBadge}>
                              <Text style={styles.mpBadgeText}>MP</Text>
                            </View>
                          ) : (
                            <Text style={styles.paymentIcon}>
                              {method.icon}
                            </Text>
                          )}
                          <Text
                            style={[
                              styles.paymentText,
                              selected && styles.paymentTextSelected,
                            ]}
                          >
                            {method.label}
                          </Text>
                        </Pressable>
                      );
                    })}
                  </View>
                  <Text style={styles.paymentHelp}>
                    Se aplica a la tarifa base de la visita. Podés cambiarlo al
                    confirmar el trabajo.
                  </Text>
                </View>

                {emergencyProvider && !emergencyHasContent ? (
                  <Text style={styles.hint}>
                    Elegí una opción rápida o escribí qué está pasando.
                  </Text>
                ) : null}
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
                title={
                  orderType === 'EMERGENCY'
                    ? '🚨 Solicitar urgencia ahora'
                    : 'Enviar solicitud'
                }
                onPress={handleSubmit}
                loading={submitting}
                disabled={
                  submitting ||
                  (providerLocked && !provider) ||
                  (orderType === 'EMERGENCY' && !photo)
                }
              />
              {orderType === 'EMERGENCY' ? (
                <Text style={styles.submitHint}>
                  Respuesta estimada: menos de 3 minutos.
                </Text>
              ) : null}
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
  providerTabs: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  providerTab: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.xs,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  providerTabActive: {
    backgroundColor: colors.brandSoft,
    borderColor: colors.brand,
  },
  providerTabText: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '700',
  },
  providerTabTextActive: {
    color: colors.brandDark,
  },
  providerTabBadge: {
    backgroundColor: colors.background,
    borderRadius: radius.pill,
    minWidth: 20,
    paddingHorizontal: 5,
    paddingVertical: 1,
  },
  providerTabBadgeText: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
    textAlign: 'center',
  },
  providerMore: {
    alignItems: 'center',
    paddingVertical: spacing.md,
  },
  providerMoreText: {
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
  submitHint: {
    color: colors.muted,
    fontSize: 11,
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  providerHeader: {
    backgroundColor: '#f8fafc',
    borderColor: '#e2e8f0',
    borderRadius: radius.md,
    borderWidth: 1,
    marginTop: spacing.sm,
    padding: spacing.md,
  },
  providerHeaderTop: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
  },
  providerBack: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 36,
    justifyContent: 'center',
    width: 36,
  },
  providerBackText: {
    color: NAVY,
    fontSize: 18,
    fontWeight: '700',
    lineHeight: 20,
  },
  providerAvatar: {
    alignItems: 'center',
    backgroundColor: '#fff1eb',
    borderRadius: radius.pill,
    height: 40,
    justifyContent: 'center',
    width: 40,
  },
  providerAvatarText: {
    color: '#D24C19',
    fontSize: 16,
    fontWeight: '700',
  },
  providerHeaderInfo: {
    flex: 1,
    minWidth: 0,
  },
  providerNameRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  providerName: {
    color: '#0f172a',
    flexShrink: 1,
    fontSize: 14,
    fontWeight: '700',
  },
  verifiedBadge: {
    backgroundColor: '#eff6ff',
    borderColor: '#bfdbfe',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: 7,
    paddingVertical: 2,
  },
  verifiedText: {
    color: '#1d4ed8',
    fontSize: 10,
    fontWeight: '700',
  },
  providerMeta: {
    color: '#64748b',
    fontSize: 12,
    marginTop: 3,
  },
  providerBadges: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: 6,
  },
  providerRating: {
    color: '#0f172a',
    fontSize: 12,
    fontWeight: '700',
  },
  providerRatingCount: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '600',
  },
  statusBadge: {
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: 8,
    paddingVertical: 2,
  },
  statusBadgeOpen: {
    backgroundColor: '#ecfdf5',
    borderColor: '#a7f3d0',
  },
  statusBadgeClosed: {
    backgroundColor: '#fffbeb',
    borderColor: '#fde68a',
  },
  statusText: {
    fontSize: 11,
    fontWeight: '700',
  },
  statusTextOpen: {
    color: '#047857',
  },
  statusTextClosed: {
    color: '#b45309',
  },
  providerServiceRow: {
    alignItems: 'flex-end',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    justifyContent: 'space-between',
    marginTop: spacing.md,
  },
  providerService: {
    color: '#D24C19',
    fontSize: 12,
    fontWeight: '700',
  },
  providerServiceNote: {
    color: '#6b7280',
    flexShrink: 1,
    fontSize: 11,
    textAlign: 'right',
  },
  feeCard: {
    alignItems: 'flex-start',
    backgroundColor: '#fff7ed',
    borderColor: '#fed7aa',
    borderLeftColor: '#D24C19',
    borderLeftWidth: 4,
    borderRadius: radius.sm,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.md,
    marginTop: spacing.md,
    padding: spacing.md,
  },
  feeIcon: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#fed7aa',
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 30,
    justifyContent: 'center',
    width: 30,
  },
  feeIconText: {
    fontSize: 14,
  },
  feeBody: {
    flex: 1,
  },
  feeTitle: {
    color: '#9a3412',
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 0.5,
  },
  feeAmount: {
    color: NAVY,
    fontSize: 18,
    fontWeight: '700',
    marginTop: 2,
  },
  feeAmountPending: {
    color: '#b45309',
  },
  feeNote: {
    color: '#9a3412',
    fontSize: 11,
    marginTop: 4,
  },
  sectionTitle: {
    color: '#94a3b8',
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.5,
    marginBottom: spacing.sm,
    marginTop: spacing.lg,
  },
  locateButton: {
    alignItems: 'center',
    backgroundColor: NAVY,
    borderRadius: radius.sm,
    marginBottom: spacing.sm,
    paddingVertical: spacing.md,
  },
  locateButtonDisabled: {
    opacity: 0.6,
  },
  locateButtonText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '700',
  },
  locateStatus: {
    color: '#047857',
    fontSize: 12,
    fontWeight: '600',
    marginBottom: spacing.sm,
  },
  mediaRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    marginTop: spacing.md,
  },
  mediaCol: {
    flexBasis: 140,
    flexGrow: 1,
    minWidth: 0,
  },
  mediaLabel: {
    color: '#6b7280',
    fontSize: 11,
    fontWeight: '600',
    marginBottom: 4,
  },
  mediaButton: {
    alignItems: 'center',
    backgroundColor: '#f9fafb',
    borderColor: '#cbd5e1',
    borderRadius: radius.sm,
    borderStyle: 'dashed',
    borderWidth: 1.5,
    flexDirection: 'row',
    gap: spacing.sm,
    minHeight: 52,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },
  mediaButtonReady: {
    backgroundColor: '#fff1eb',
    borderColor: '#D24C19',
    borderStyle: 'solid',
  },
  mediaButtonRecording: {
    backgroundColor: '#fef2f2',
    borderColor: colors.danger,
    borderStyle: 'solid',
  },
  mediaIcon: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#fed7aa',
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  mediaIconText: {
    fontSize: 15,
  },
  mediaButtonText: {
    color: NAVY,
    flex: 1,
    fontSize: 12,
    fontWeight: '600',
  },
  mediaButtonTextRecording: {
    color: colors.danger,
  },
  mediaInfo: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  mediaThumb: {
    borderRadius: radius.sm,
    height: 44,
    width: 44,
  },
  mediaInfoBody: {
    flex: 1,
    minWidth: 0,
  },
  mediaInfoText: {
    color: '#047857',
    fontSize: 11,
    fontWeight: '600',
  },
  mediaInfoSize: {
    color: '#64748b',
    fontSize: 11,
  },
  mediaRemove: {
    color: colors.danger,
    fontSize: 11,
    fontWeight: '700',
    marginTop: 2,
  },
  recordingHint: {
    color: colors.danger,
    fontSize: 11,
    fontWeight: '600',
    marginTop: 4,
  },
  paymentBlock: {
    borderTopColor: '#eef2f7',
    borderTopWidth: 1,
    marginTop: spacing.lg,
    paddingTop: spacing.md,
  },
  paymentRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  paymentChip: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1.5,
    flexDirection: 'row',
    gap: 6,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  paymentChipSelected: {
    backgroundColor: '#fff1eb',
    borderColor: '#D24C19',
  },
  mpBadge: {
    alignItems: 'center',
    backgroundColor: '#00b6ea',
    borderRadius: 4,
    height: 19,
    justifyContent: 'center',
    width: 19,
  },
  mpBadgeText: {
    color: colors.surface,
    fontSize: 10,
    fontWeight: '800',
  },
  paymentIcon: {
    fontSize: 14,
  },
  paymentText: {
    color: '#374151',
    fontSize: 13,
    fontWeight: '600',
  },
  paymentTextSelected: {
    color: '#D24C19',
  },
  paymentHelp: {
    color: '#6b7280',
    fontSize: 11,
    marginTop: spacing.sm,
  },
});
