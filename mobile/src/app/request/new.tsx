import * as ImagePicker from 'expo-image-picker';
import { router, useLocalSearchParams } from 'expo-router';
import {
  AudioModule,
  RecordingPresets,
  setAudioModeAsync,
  useAudioRecorder,
  useAudioRecorderState,
} from 'expo-audio';
import { useEffect, useMemo, useRef, useState } from 'react';
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

/** Medios de pago rápidos para la tarifa base de la urgencia (estilo web). */
const EMERGENCY_PAYMENT_METHODS = [
  { code: 'mercado_pago', label: 'Mercado Pago', icon: 'MP', mp: true },
  { code: 'tarjeta', label: 'Tarjeta', icon: '💳', mp: false },
  { code: 'efectivo', label: 'Efectivo', icon: '💵', mp: false },
];

/** Medios de pago para la cita programada (mismos códigos que la web). */
const SCHEDULED_PAYMENT_METHODS = [
  { code: 'efectivo', label: 'Efectivo' },
  { code: 'mercado_pago', label: 'Mercado Pago' },
  { code: 'tarjeta', label: 'Tarjeta' },
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
const MAX_QUOTE_VIDEO_BYTES = 20 * 1024 * 1024;
const MAX_AUDIO_BYTES = 8 * 1024 * 1024;
const MAX_AUDIO_SECONDS = 120;

/** Mismos formatos de video que valida el backend del presupuesto. */
const QUOTE_VIDEO_TYPES = [
  'video/mp4',
  'video/x-m4v',
  'video/webm',
  'video/quicktime',
];

/** Configuración del formulario de presupuesto según el rubro (web). */
type QuoteQuestionOption = { value: string; label: string };
type QuoteQuestion = {
  id: string;
  label: string;
  default?: string;
  options: QuoteQuestionOption[];
};
type QuoteConfig = {
  slug: 'generic' | 'plomeria' | 'electricidad' | 'pintura';
  label: string;
  icon: string;
  quickTags: string[];
  questions: QuoteQuestion[];
  placeholders: { details: string; photos: string };
};

const QUOTE_QUESTIONS = {
  propertyType: (defaultValue: string): QuoteQuestion => ({
    id: 'property_type',
    label: '¿Dónde hay que hacer el trabajo?',
    default: defaultValue,
    options: [
      { value: 'casa', label: 'Casa' },
      { value: 'departamento', label: 'Departamento' },
      { value: 'local', label: 'Local / Comercio' },
      { value: 'otro', label: 'Otro' },
    ],
  }),
  urgency: (defaultValue = 'esta_semana'): QuoteQuestion => ({
    id: 'urgency',
    label: '¿Qué tan urgente es?',
    default: defaultValue,
    options: [
      { value: 'ahora', label: 'Es una emergencia ahora' },
      { value: 'hoy', label: 'Hoy o mañana' },
      { value: 'esta_semana', label: 'Esta semana' },
      { value: 'sin_apuro', label: 'Sin apuro' },
    ],
  }),
  materials: (
    defaultValue: string,
    label = '¿Quién provee los materiales?',
  ): QuoteQuestion => ({
    id: 'materials',
    label,
    default: defaultValue,
    options: [
      { value: 'profesional', label: 'Los provee el profesional' },
      { value: 'cliente', label: 'Los compro yo' },
      { value: 'combinar', label: 'Lo vemos juntos' },
    ],
  }),
  surface: (): QuoteQuestion => ({
    id: 'surface',
    label: '¿Qué superficie aproximada?',
    default: 'un_ambiente',
    options: [
      { value: 'un_ambiente', label: 'Un ambiente' },
      { value: 'dos_tres', label: '2 a 3 ambientes' },
      { value: 'toda_la_casa', label: 'Toda la casa' },
      { value: 'exterior', label: 'Exterior / fachada' },
    ],
  }),
};

const QUOTE_FALLBACK_CONFIG: QuoteConfig = {
  slug: 'generic',
  label: 'Servicios del hogar',
  icon: '🏠',
  quickTags: [
    'Necesito una visita para evaluar el trabajo',
    'Es un trabajo urgente',
    'Quiero comparar precios antes de decidir',
    'Tengo fotos del problema',
  ],
  questions: [
    QUOTE_QUESTIONS.propertyType('casa'),
    QUOTE_QUESTIONS.materials('profesional'),
  ],
  placeholders: {
    details:
      'Ej: Contame qué necesitás, hace cuánto pasa y cualquier detalle que ayude a cotizar.',
    photos: 'Subí fotos del problema, lugar o material (1 a 5).',
  },
};

const QUOTE_CONFIGS: Record<QuoteConfig['slug'], QuoteConfig> = {
  generic: QUOTE_FALLBACK_CONFIG,
  plomeria: {
    slug: 'plomeria',
    label: 'Plomería y Gas',
    icon: '🚿',
    quickTags: [
      'Pérdida / filtración de agua',
      'Destapación de cañería',
      'Termotanque o calefón',
      'Instalación de grifería',
      'Cambio de caño',
      'Sin agua caliente',
    ],
    questions: [
      QUOTE_QUESTIONS.propertyType('casa'),
      QUOTE_QUESTIONS.urgency(),
      QUOTE_QUESTIONS.materials('profesional'),
    ],
    placeholders: {
      details:
        'Ej: Pérdida debajo de la bacha de la cocina, gotea desde ayer y ya cerré la llave de paso.',
      photos: 'Subí fotos de la pérdida, cañería o equipo (1 a 5).',
    },
  },
  electricidad: {
    slug: 'electricidad',
    label: 'Electricidad',
    icon: '⚡',
    quickTags: [
      'Salta la térmica / disyuntor',
      'Corto circuito',
      'Instalación de enchufes o tomas',
      'Colocación de luminarias',
      'Renovación de tablero',
      'Sin luz en un ambiente',
    ],
    questions: [
      QUOTE_QUESTIONS.propertyType('departamento'),
      QUOTE_QUESTIONS.urgency(),
      QUOTE_QUESTIONS.materials('profesional'),
    ],
    placeholders: {
      details:
        'Ej: Al encender el aire acondicionado salta la térmica del tablero principal.',
      photos: 'Subí fotos del tablero, toma o sector afectado (1 a 5).',
    },
  },
  pintura: {
    slug: 'pintura',
    label: 'Pintura',
    icon: '🎨',
    quickTags: [
      'Pintar un ambiente',
      'Pintar toda la casa',
      'Reparar humedad / goteras',
      'Enduido y lijado',
      'Pintura exterior / fachada',
      'Colocar durlock',
    ],
    questions: [
      QUOTE_QUESTIONS.propertyType('departamento'),
      QUOTE_QUESTIONS.surface(),
      QUOTE_QUESTIONS.materials('cliente', '¿Quién provee la pintura y materiales?'),
    ],
    placeholders: {
      details:
        'Ej: Quiero pintar un living-comedor de 20 m², las paredes están en buen estado.',
      photos: 'Subí fotos de las paredes / ambiente a pintar (1 a 5).',
    },
  },
};

const QUOTE_KEYWORDS: Record<'plomeria' | 'electricidad' | 'pintura', string[]> = {
  plomeria: [
    'plom', 'agua', 'gas', 'destap', 'cano', 'canilla', 'termotanque', 'calefon',
  ],
  electricidad: [
    'elect', 'luz', 'cable', 'tablero', 'disyunt', 'enchufe', 'luminaria',
    'cortocircuito',
  ],
  pintura: ['pint', 'yeso', 'durlock', 'empapel', 'latex', 'revoque'],
};

/** Resuelve el rubro por keywords normalizadas, igual que la web. */
function quoteSlugForText(value: string | null | undefined): QuoteConfig['slug'] {
  const text = (value ?? '')
    .toString()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');

  const slugs: ('plomeria' | 'electricidad' | 'pintura')[] = [
    'plomeria',
    'electricidad',
    'pintura',
  ];

  for (const slug of slugs) {
    if (QUOTE_KEYWORDS[slug].some((word) => text.includes(word))) {
      return slug;
    }
  }

  return 'generic';
}

function quoteConfigForText(value: string | null | undefined): QuoteConfig {
  return QUOTE_CONFIGS[quoteSlugForText(value)];
}

/** Respuestas por defecto del rubro, sembradas como en la web. */
function seedQuoteAnswers(
  config: QuoteConfig,
): Record<string, QuoteQuestionOption> {
  const seeded: Record<string, QuoteQuestionOption> = {};

  config.questions.forEach((question) => {
    if (question.default === undefined) {
      return;
    }

    seeded[question.id] = {
      value: question.default,
      label:
        question.options.find((option) => option.value === question.default)
          ?.label ?? question.default,
    };
  });

  return seeded;
}

type QuoteAvailabilityMode = 'asap' | 'manana' | 'ventana';
type QuoteAvailability = {
  mode: QuoteAvailabilityMode;
  label: string;
  window: { from: string; to: string } | null;
};

const QUOTE_AVAILABILITY_OPTIONS: { mode: QuoteAvailabilityMode; label: string }[] = [
  { mode: 'asap', label: '⚡ Lo antes posible' },
  { mode: 'manana', label: '🗓️ Mañana' },
  { mode: 'ventana', label: '🕒 Franja horaria' },
];

const QUOTE_WINDOW_OPTIONS = [
  { value: 'manana|08:00|12:00', label: '🌅 Mañana (08:00 a 12:00)', from: '08:00', to: '12:00' },
  { value: 'tarde|12:00|18:00', label: '☀️ Tarde (12:00 a 18:00)', from: '12:00', to: '18:00' },
  { value: 'noche|18:00|21:00', label: '🌙 Noche (18:00 a 21:00)', from: '18:00', to: '21:00' },
];

type QuoteQuality = {
  score: number;
  label: 'Bajo' | 'Medio' | 'Alto';
  hint: string;
  bg: string;
  fg: string;
  border: string;
  fill: string;
};

/** Estado completo del formulario de presupuesto (se reinicia por rubro). */
type QuoteFormState = {
  key: string;
  details: string;
  manualText: string;
  tags: string[];
  answers: Record<string, QuoteQuestionOption>;
  availability: QuoteAvailability | null;
  window: string;
  photos: LocalPhoto[];
  video: LocalPhoto | null;
};

function createQuoteForm(config: QuoteConfig, key: string): QuoteFormState {
  return {
    key,
    details: '',
    manualText: '',
    tags: [],
    answers: seedQuoteAnswers(config),
    availability: null,
    window: QUOTE_WINDOW_OPTIONS[0].value,
    photos: [],
    video: null,
  };
}

/** El azul oscuro de las acciones secundarias de la web. */
const NAVY = '#0c2a4d';

type SelectedProvider = ProviderCard &
  Partial<Pick<ProviderDetail, 'emergency_count' | 'hours'>>;

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

/** Horarios publicados por el prestador (payload "hours" del detalle). */
type ProviderHours = Record<
  string,
  { open?: boolean; shifts?: { from?: string; to?: string }[] } | undefined
>;

type ScheduleDay = {
  date: Date;
  key: string;
  open: boolean;
  shifts: { from: number; to: number }[];
  fallback: boolean;
};

const SCHEDULE_DAYS = 14;
const SCHEDULE_FALLBACK_FROM = 9 * 60;
const SCHEDULE_FALLBACK_TO = 18 * 60;
const SCHEDULE_HOURS_KEYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'] as const;
const SCHEDULE_WEEKDAY_LABELS = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
const SCHEDULE_MONTH_LABELS = [
  'ene', 'feb', 'mar', 'abr', 'may', 'jun',
  'jul', 'ago', 'sep', 'oct', 'nov', 'dic',
];

/** "14:30" → 870 minutos. */
function minutesOf(value: string | undefined | null): number | null {
  if (!value) {
    return null;
  }

  const match = /^(\d{1,2}):(\d{2})/.exec(value.trim());

  if (!match) {
    return null;
  }

  const hours = Number(match[1]);
  const minutes = Number(match[2]);

  if (!Number.isFinite(hours) || !Number.isFinite(minutes)) {
    return null;
  }

  return hours * 60 + minutes;
}

function scheduleKey(date: Date): string {
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${date.getFullYear()}-${month}-${day}`;
}

function isSameDate(a: Date, b: Date): boolean {
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  );
}

/** Horario del prestador para un día; sin horarios publicados usa 09–18. */
function scheduleDayHours(
  hours: ProviderHours | null | undefined,
  date: Date,
): { open: boolean; shifts: { from: number; to: number }[]; fallback: boolean } {
  if (!hours || typeof hours !== 'object' || Array.isArray(hours)) {
    return {
      open: true,
      shifts: [{ from: SCHEDULE_FALLBACK_FROM, to: SCHEDULE_FALLBACK_TO }],
      fallback: true,
    };
  }

  const key = SCHEDULE_HOURS_KEYS[date.getDay()];
  const day = hours[key];
  let open = Boolean(day && day.open);
  const shifts: { from: number; to: number }[] = [];

  if (open) {
    (day?.shifts ?? []).forEach((shift) => {
      const from = minutesOf(shift?.from);
      const to = minutesOf(shift?.to);

      if (from !== null && to !== null && to > from) {
        shifts.push({ from, to });
      }
    });

    if (!shifts.length) {
      open = false;
    }
  }

  return { open, shifts, fallback: false };
}

/** Los 14 días del calendario de citas, igual que la web. */
function buildScheduleDays(hours: ProviderHours | null | undefined): ScheduleDay[] {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  return Array.from({ length: SCHEDULE_DAYS }, (_, index) => {
    const date = new Date(today.getTime());
    date.setDate(today.getDate() + index);
    const info = scheduleDayHours(hours, date);

    return { date, key: scheduleKey(date), ...info };
  });
}

/** Franjas de 60 min (30 si el turno es corto); hoy descarta lo ya pasado. */
function scheduleSlotsFor(day: ScheduleDay | undefined): number[] {
  if (!day) {
    return [];
  }

  const now = new Date();
  const nowMinutes = isSameDate(day.date, now)
    ? now.getHours() * 60 + now.getMinutes()
    : null;
  const slots: number[] = [];

  day.shifts.forEach((shift) => {
    const length = shift.to - shift.from;
    const step = length < 90 ? 30 : 60;

    for (let start = shift.from; start + step <= shift.to; start += step) {
      if (nowMinutes !== null && start <= nowMinutes) {
        continue;
      }

      slots.push(start);
    }
  });

  return slots.sort((a, b) => a - b);
}

function slotValue(minutes: number): string {
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;

  return `${String(hours).padStart(2, '0')}:${String(rest).padStart(2, '0')}`;
}

function slotLabel(minutes: number): string {
  const hours24 = Math.floor(minutes / 60);
  const rest = minutes % 60;
  const suffix = hours24 < 12 ? 'AM' : 'PM';
  const hours12 = hours24 % 12 === 0 ? 12 : hours24 % 12;

  return `${String(hours12).padStart(2, '0')}:${String(rest).padStart(2, '0')} ${suffix}`;
}

function scheduleRangeLabel(days: ScheduleDay[]): string {
  if (!days.length) {
    return '';
  }

  const first = days[0].date;
  const last = days[days.length - 1].date;

  return `${first.getDate()} ${SCHEDULE_MONTH_LABELS[first.getMonth()]} – ${
    last.getDate()
  } ${SCHEDULE_MONTH_LABELS[last.getMonth()]}`;
}

function formatMoney(value: number): string {
  return `$${value.toLocaleString('es-AR', { maximumFractionDigits: 2 })}`;
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
  const [weekOffset, setWeekOffset] = useState(0);
  const [fulfillment, setFulfillment] = useState<'AT_HOME' | 'AT_MERCHANT_LOCATION'>('AT_HOME');
  const [paymentMode, setPaymentMode] = useState<'DEPOSIT' | 'FULL'>('DEPOSIT');
  const [scheduledPaymentMethod, setScheduledPaymentMethod] = useState('efectivo');
  const [notes, setNotes] = useState('');
  const [addressStreet, setAddressStreet] = useState('');
  const [addressUnit, setAddressUnit] = useState('');
  const [addressNeighborhood, setAddressNeighborhood] = useState('');
  const [reminder, setReminder] = useState<'whatsapp' | 'email'>('whatsapp');

  // Presupuesto: el estado se inicializa al resolver el rubro (más abajo).

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

            if (initialService === 'SCHEDULED') {
              primeScheduledForm();
            }
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

  // Rubro del presupuesto: primero el subgrupo del prestador elegido.
  const quoteRubroText =
    provider?.subgroups?.[0]?.description ??
    subgroups.find((item) => item.id === subgroupId)?.description ??
    '';
  const quoteConfig = useMemo(
    () => quoteConfigForText(quoteRubroText),
    [quoteRubroText],
  );

  // Identidad del formulario: al cambiar rubro o prestador se vuelve a los
  // valores por defecto sin necesidad de un effect de reseteo.
  const quoteFormKey = `${quoteConfig.slug}:${provider?.id ?? 0}:${
    subgroupId ?? 0
  }`;
  // El formulario completo se guarda en un solo objeto para reiniciarlo
  // cuando cambia el rubro o el prestador.
  const [quoteFormState, setQuoteFormState] = useState<QuoteFormState>(() =>
    createQuoteForm(quoteConfig, quoteFormKey),
  );
  const quoteForm =
    quoteFormState.key === quoteFormKey
      ? quoteFormState
      : createQuoteForm(quoteConfig, quoteFormKey);

  const details = quoteForm.details;
  const quoteTags = quoteForm.tags;
  const quoteAnswers = quoteForm.answers;
  const quoteAvailability = quoteForm.availability;
  const quoteWindow = quoteForm.window;
  const quotePhotos = quoteForm.photos;
  const quoteVideo = quoteForm.video;

  function patchQuoteForm(patch: Partial<Omit<QuoteFormState, 'key'>>) {
    setQuoteFormState({ ...quoteForm, ...patch });
  }

  function primeScheduledForm() {
    setDate('');
    setTime('');
    setWeekOffset(0);
    setPaymentMode('DEPOSIT');
    setScheduledPaymentMethod('efectivo');
    setReminder('whatsapp');
  }

  function handleOrderType(code: OrderType) {
    setOrderType(code);
    setLoadingProviders(true);

    if (code === 'SCHEDULED') {
      primeScheduledForm();
    }

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
    const nextType = (serviceCode as OrderType | undefined) ?? orderType;

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

    if (nextType === 'SCHEDULED') {
      primeScheduledForm();
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

  // Detecta la parte escrita a mano para no pisarla al togglear atajos.
  function handleQuoteDetails(value: string) {
    const chips = quoteTags.join('\n');
    const manualText =
      chips && value.startsWith(chips)
        ? value.slice(chips.length).replace(/^\n/, '')
        : value;

    patchQuoteForm({ details: value, manualText });
  }

  // Atajos multi-selección: vuelcan sus frases al detalle (como la web).
  function toggleQuoteTag(tag: string) {
    const next = quoteTags.includes(tag)
      ? quoteTags.filter((item) => item !== tag)
      : [...quoteTags, tag];

    const manual = quoteForm.manualText;
    const nextDetails = next.length
      ? next.join('\n') + (manual ? `\n${manual}` : '')
      : manual;

    patchQuoteForm({ tags: next, details: nextDetails });
  }

  function pickQuoteAnswer(
    question: QuoteQuestion,
    option: QuoteQuestionOption,
  ) {
    patchQuoteForm({
      answers: { ...quoteAnswers, [question.id]: option },
    });
  }

  function pickQuoteAvailability(mode: QuoteAvailabilityMode) {
    const option = QUOTE_AVAILABILITY_OPTIONS.find((item) => item.mode === mode);
    patchQuoteForm({
      availability: { mode, label: option?.label ?? mode, window: null },
    });
  }

  function handleQuoteWindow(value: string) {
    const option = QUOTE_WINDOW_OPTIONS.find((item) => item.value === value);
    const availability =
      quoteAvailability?.mode === 'ventana'
        ? {
            mode: 'ventana' as const,
            label: `Franja horaria: ${option?.label ?? value}`,
            window: option ? { from: option.from, to: option.to } : null,
          }
        : quoteAvailability;

    patchQuoteForm({ window: value, availability });
  }

  async function addQuotePhotos() {
    const remaining = MAX_QUOTE_PHOTOS - quotePhotos.length;

    if (remaining <= 0) {
      return;
    }

    const picked = await pickMultipleImages(remaining);
    const accepted = picked.filter(
      (item) => !item.size || item.size <= MAX_PHOTO_BYTES,
    );

    patchQuoteForm({
      photos: [...quotePhotos, ...accepted].slice(0, MAX_QUOTE_PHOTOS),
    });

    if (accepted.length < picked.length) {
      setError('Alguna foto supera los 5 MB. Elegí una más liviana.');
    }
  }

  async function pickQuoteVideo() {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();

    if (!permission.granted) {
      Alert.alert(
        'Galería',
        'Necesitamos permiso para elegir un video. Podés habilitarlo en los ajustes del teléfono.',
      );
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['videos'],
      allowsMultipleSelection: false,
      quality: 1,
    });

    if (result.canceled || result.assets.length === 0) {
      return;
    }

    const asset = result.assets[0];
    const name = asset.fileName ?? `video_${Date.now()}.mp4`;
    const extension = name.split('.').pop()?.toLowerCase() ?? '';
    const fallbackMime =
      extension === 'webm'
        ? 'video/webm'
        : extension === 'mov' || extension === 'qt'
          ? 'video/quicktime'
          : extension === 'm4v'
            ? 'video/x-m4v'
            : 'video/mp4';
    const mimeType = asset.mimeType ?? fallbackMime;
    const allowedExtension = ['mp4', 'm4v', 'webm', 'mov', 'qt'].includes(
      extension,
    );

    if (!QUOTE_VIDEO_TYPES.includes(mimeType) && !allowedExtension) {
      setError('El video debe ser MP4, WEBM o MOV.');
      return;
    }

    if (asset.fileSize && asset.fileSize > MAX_QUOTE_VIDEO_BYTES) {
      setError('El video supera los 20 MB. Elegí uno más liviano.');
      return;
    }

    setError(null);
    patchQuoteForm({
      video: {
        uri: asset.uri,
        name,
        mimeType,
        size: asset.fileSize ?? null,
      },
    });
  }

  function openQuotePicker() {
    const canAddPhoto = quotePhotos.length < MAX_QUOTE_PHOTOS;

    Alert.alert('Fotos y video', 'Elegí qué querés adjuntar.', [
      ...(canAddPhoto
        ? [{ text: 'Fotos', onPress: () => void addQuotePhotos() }]
        : []),
      ...(!quoteVideo
        ? [{ text: 'Video', onPress: () => void pickQuoteVideo() }]
        : []),
      { text: 'Cancelar', style: 'cancel' as const },
    ]);
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
        if (!date) {
          setError('Elegí un día del calendario para tu cita.');
          setSubmitting(false);
          return;
        }

        if (!time) {
          setError('Elegí uno de los horarios libres del día seleccionado.');
          setSubmitting(false);
          return;
        }

        const when = new Date(`${date}T${time}:00`);

        if (Number.isNaN(when.getTime()) || when <= new Date()) {
          setError('Elegí un día y un horario futuros para tu cita.');
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
          scheduled_at: `${date} ${time}:00`,
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
        payload.reminder_channel = reminder;

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
        if (quoteVideo) {
          appendPhoto(form, 'video', quoteVideo);
        }

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
  // La cabecera del prestador se comparte entre urgencias, citas y
  // presupuestos: la web esconde el selector cuando el pedido va directo a
  // un profesional.
  const headerProvider =
    provider &&
    (orderType === 'EMERGENCY' ||
      orderType === 'SCHEDULED' ||
      orderType === 'QUOTE')
      ? provider
      : null;
  const emergencyCount = provider?.emergency_count ?? 0;
  const providerScore = Number(provider?.rating ?? 0);
  const providerRubro = provider?.subgroups?.[0]?.description ?? 'Servicios del hogar';
  const providerMeta = provider
    ? `${providerRubro}${provider.zone ? ` · ${provider.zone}` : ''}`
    : '';
  const headerServiceLabel = headerProvider
    ? headerProvider.service_types.find((type) => type.code === orderType)?.name ??
      (orderType === 'EMERGENCY'
        ? 'Urgencias 24/7'
        : orderType === 'SCHEDULED'
          ? 'Cita programada'
          : 'Presupuesto')
    : '';
  const baseFee = provider?.base_visit_fee ?? null;
  const hasBaseFee = baseFee !== null && Number(baseFee) > 0;
  const baseFeeLabel = hasBaseFee
    ? `$${Number(baseFee).toLocaleString('es-AR', { maximumFractionDigits: 2 })}`
    : 'A convenir';
  const emergencyHasContent = Boolean(description.trim());

  // Resumen visible de atajos + respuestas, como en la web.
  const quoteSummaryRows = useMemo(() => {
    const rows: string[] = [];

    if (quoteTags.length) {
      rows.push(quoteTags.join(' · '));
    }

    Object.values(quoteAnswers).forEach((answer) => {
      if (answer?.label) {
        rows.push(answer.label);
      }
    });

    return rows;
  }, [quoteTags, quoteAnswers]);

  // Nivel de detalle (calidad) de la solicitud, con los mismos puntos que la web.
  const quoteQuality = useMemo<QuoteQuality>(() => {
    const length = details.trim().length;
    let score = 0;

    if (length >= 30) score += 20;
    if (length >= 120) score += 15;
    if (quoteTags.length) score += 15;
    if (Object.keys(quoteAnswers).length) score += 15;
    if (quotePhotos.length >= 1) score += 15;
    if (quotePhotos.length >= 3) score += 10;
    if (quoteVideo) score += 5;
    if (quoteAvailability) score += 5;

    score = Math.min(100, score);

    if (score >= 70) {
      return {
        score,
        label: 'Alto',
        hint: 'Excelente. Tu solicitud tiene el detalle que necesitan los prestadores.',
        bg: '#ecfdf5',
        fg: '#047857',
        border: '#a7f3d0',
        fill: '#047857',
      };
    }

    if (score >= 40) {
      return {
        score,
        label: 'Medio',
        hint: 'Buen comienzo. Una foto más o describir mejor el trabajo ayuda al prestador.',
        bg: '#eff6ff',
        fg: '#1d4ed8',
        border: '#bfdbfe',
        fill: '#1d4ed8',
      };
    }

    return {
      score,
      label: 'Bajo',
      hint: 'Sumá detalles, fotos y atajos para recibir presupuestos más precisos.',
      bg: '#fffbeb',
      fg: '#b45309',
      border: '#fed7aa',
      fill: '#b45309',
    };
  }, [details, quoteTags, quoteAnswers, quotePhotos, quoteVideo, quoteAvailability]);

  // Calendario de la cita: 14 días, con navegación semanal (igual que la web).
  const scheduledDays = useMemo(
    () =>
      buildScheduleDays(
        (provider?.hours as ProviderHours | null | undefined) ?? null,
      ),
    [provider?.hours],
  );
  const visibleScheduleDays = scheduledDays.slice(weekOffset, weekOffset + 7);
  const selectedDay = scheduledDays.find((day) => day.key === date);
  const daySlots = scheduleSlotsFor(selectedDay);
  const scheduleRange = scheduleRangeLabel(visibleScheduleDays);
  const canGoPreviousWeek = weekOffset > 0;
  const canGoNextWeek = weekOffset + 7 < SCHEDULE_DAYS;

  // Desglose de la seña: sin tarifa publicada todo queda "A convenir".
  const scheduledFee = hasBaseFee ? Number(baseFee) : null;
  const scheduledNow =
    scheduledFee === null
      ? null
      : paymentMode === 'FULL'
        ? scheduledFee
        : Math.round(scheduledFee * 50) / 100;
  const scheduledRest =
    scheduledFee === null
      ? null
      : paymentMode === 'FULL'
        ? 0
        : Math.round(scheduledFee * 50) / 100;
  const scheduledBaseLabel =
    scheduledFee === null ? 'A convenir' : formatMoney(scheduledFee);
  const scheduledNowLabel =
    scheduledNow === null ? 'A convenir' : formatMoney(scheduledNow);
  const scheduledRestLabel =
    scheduledRest === null ? 'A convenir' : formatMoney(scheduledRest);
  const scheduledNowTitle =
    scheduledFee === null
      ? 'Monto a abonar ahora'
      : paymentMode === 'FULL'
        ? 'Monto a abonar ahora (Pago total)'
        : 'Monto a abonar ahora (Seña 50%)';
  const depositNote =
    paymentMode === 'FULL'
      ? 'Con pago total abonás la tarifa base completa ahora y solo se descuenta la visita.'
      : 'La seña del 50% garantiza el turno y se descuenta del saldo final.';
  const reminderHint =
    reminder === 'email'
      ? 'Te enviamos el recordatorio y la confirmación por email.'
      : 'Te avisamos por WhatsApp cuando el profesional esté en camino.';

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

        {headerProvider ? null : (
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

            {headerProvider ? null : (
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

            {headerProvider ? (
              <>
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
                        {(headerProvider.business_name || '?')
                          .charAt(0)
                          .toUpperCase()}
                      </Text>
                    </View>

                    <View style={styles.providerHeaderInfo}>
                      <View style={styles.providerNameRow}>
                        <Text style={styles.providerName} numberOfLines={1}>
                          {headerProvider.business_name}
                        </Text>
                        {headerProvider.is_verified ? (
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
                            headerProvider.is_available_now
                              ? styles.statusBadgeOpen
                              : styles.statusBadgeClosed,
                          ]}
                        >
                          <Text
                            style={[
                              styles.statusText,
                              headerProvider.is_available_now
                                ? styles.statusTextOpen
                                : styles.statusTextClosed,
                            ]}
                          >
                            {headerProvider.is_available_now
                              ? '🟢 Disponible Ahora'
                              : `🟡 ${headerProvider.availability_label}`}
                          </Text>
                        </View>
                      </View>
                    </View>
                  </View>

                  <View style={styles.providerServiceRow}>
                    <Text style={styles.providerService}>
                      {headerServiceLabel}
                    </Text>
                    <Text style={styles.providerServiceNote}>
                      El precio lo define el profesional
                    </Text>
                  </View>
                </View>

                {orderType !== 'QUOTE' ? (
                  <View style={styles.feeCard}>
                    <View style={styles.feeIcon}>
                      <Text style={styles.feeIconText}>🔔</Text>
                    </View>
                    <View style={styles.feeBody}>
                      <Text style={styles.feeTitle}>
                        {orderType === 'EMERGENCY'
                          ? 'TARIFA BASE POR URGENCIA / VISITA'
                          : 'TARIFA BASE POR VISITA'}
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
              </>
            ) : null}

            {orderType === 'EMERGENCY' ? (
              <>
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
                <Text style={styles.sectionTitle}>ELEGÍ FECHA Y HORARIO</Text>

                <Text style={styles.fieldLabel}>Elegí el día</Text>
                <View style={styles.calendarNav}>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Días anteriores"
                    disabled={!canGoPreviousWeek}
                    onPress={() => setWeekOffset((current) => Math.max(0, current - 7))}
                    style={[
                      styles.calendarNavButton,
                      !canGoPreviousWeek && styles.calendarNavButtonDisabled,
                    ]}
                  >
                    <Text style={styles.calendarNavText}>‹</Text>
                  </Pressable>

                  <Text style={styles.calendarRange}>{scheduleRange}</Text>

                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Días siguientes"
                    disabled={!canGoNextWeek}
                    onPress={() =>
                      setWeekOffset((current) =>
                        Math.min(SCHEDULE_DAYS - 7, current + 7),
                      )
                    }
                    style={[
                      styles.calendarNavButton,
                      !canGoNextWeek && styles.calendarNavButtonDisabled,
                    ]}
                  >
                    <Text style={styles.calendarNavText}>›</Text>
                  </Pressable>
                </View>

                <View style={styles.calendarGrid}>
                  {visibleScheduleDays.map((day) => {
                    const selected = day.key === date;
                    const today = isSameDate(day.date, new Date());

                    return (
                      <Pressable
                        key={day.key}
                        accessibilityRole="button"
                        accessibilityState={{
                          selected,
                          disabled: !day.open,
                        }}
                        disabled={!day.open}
                        onPress={() => {
                          setDate(day.key);
                          setTime('');
                        }}
                        style={[
                          styles.dayCard,
                          selected && styles.dayCardSelected,
                          !day.open && styles.dayCardClosed,
                        ]}
                      >
                        <Text
                          style={[
                            styles.dayWeekday,
                            selected && styles.dayTextSelected,
                            !day.open && styles.dayTextClosed,
                          ]}
                        >
                          {SCHEDULE_WEEKDAY_LABELS[day.date.getDay()]}
                        </Text>
                        <Text
                          style={[
                            styles.dayNumber,
                            today && !selected && styles.dayNumberToday,
                            selected && styles.dayTextSelected,
                            !day.open && styles.dayTextClosed,
                          ]}
                        >
                          {day.date.getDate()}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>

                {selectedDay?.fallback ? (
                  <Text style={styles.calendarFallback}>
                    Este prestador todavía no publicó horarios. Te mostramos los
                    turnos estándar (09:00 a 18:00) y te confirmamos la cita al
                    aceptar.
                  </Text>
                ) : null}

                <Text style={[styles.fieldLabel, styles.fieldLabelSpaced]}>
                  Elegí el horario
                </Text>
                {!selectedDay ? (
                  <Text style={styles.fieldHelp}>
                    Primero tocá un día del calendario para ver los horarios
                    libres.
                  </Text>
                ) : daySlots.length === 0 ? (
                  <Text style={styles.slotsEmpty}>
                    No quedan horarios libres ese día. Probá con otro día.
                  </Text>
                ) : (
                  <View style={styles.slotsWrap}>
                    {daySlots.map((minutes) => {
                      const value = slotValue(minutes);
                      const selected = time === value;

                      return (
                        <Pressable
                          key={value}
                          accessibilityRole="radio"
                          accessibilityState={{ selected }}
                          onPress={() => setTime(value)}
                          style={[
                            styles.slotChip,
                            selected && styles.slotChipSelected,
                          ]}
                        >
                          <Text
                            style={[
                              styles.slotText,
                              selected && styles.slotTextSelected,
                            ]}
                          >
                            {slotLabel(minutes)}
                          </Text>
                        </Pressable>
                      );
                    })}
                  </View>
                )}
                {fieldErrors.scheduled_at?.[0] ? (
                  <Text style={styles.fieldError}>
                    {fieldErrors.scheduled_at[0]}
                  </Text>
                ) : null}

                <View style={styles.scheduledSelectors}>
                  <ComboField
                    label="Modalidad"
                    value={fulfillment}
                    options={[
                      { value: 'AT_HOME', label: 'A domicilio' },
                      {
                        value: 'AT_MERCHANT_LOCATION',
                        label: 'En el comercio',
                      },
                    ]}
                    onChange={setFulfillment}
                  />

                  <ComboField
                    label="Medio de pago"
                    value={scheduledPaymentMethod}
                    options={SCHEDULED_PAYMENT_METHODS.map((method) => ({
                      value: method.code,
                      label: method.label,
                    }))}
                    onChange={setScheduledPaymentMethod}
                  />

                  <ComboField
                    label="Pago"
                    value={paymentMode}
                    options={[
                      { value: 'DEPOSIT', label: 'Seña (50%)' },
                      { value: 'FULL', label: 'Pago total' },
                    ]}
                    onChange={setPaymentMode}
                  />
                </View>

                <View style={styles.policyCard}>
                  <View style={styles.policyHead}>
                    <View style={styles.policyIcon}>
                      <Text style={styles.policyIconText}>$</Text>
                    </View>
                    <View style={styles.policyHeadBody}>
                      <Text style={styles.policyTitle}>
                        La seña del 50% garantiza el turno y se descuenta del
                        saldo final
                      </Text>
                      <Text style={styles.policyText}>{depositNote}</Text>
                    </View>
                  </View>

                  <View style={styles.breakdown}>
                    <View style={styles.breakdownRow}>
                      <Text style={styles.breakdownLabel}>
                        Tarifa base de visita
                      </Text>
                      <Text style={styles.breakdownValue}>
                        {scheduledBaseLabel}
                      </Text>
                    </View>
                    <View style={[styles.breakdownRow, styles.breakdownRowLine]}>
                      <Text style={styles.breakdownLabel}>
                        {scheduledNowTitle}
                      </Text>
                      <Text style={styles.breakdownNow}>
                        {scheduledNowLabel}
                      </Text>
                    </View>
                    <View style={[styles.breakdownRow, styles.breakdownRowLine]}>
                      <Text style={styles.breakdownLabel}>
                        Restante a pagar en el domicilio
                      </Text>
                      <Text style={styles.breakdownValue}>
                        {scheduledRestLabel}
                      </Text>
                    </View>
                  </View>
                </View>

                <View style={styles.cancelBanner}>
                  <Text style={styles.cancelIcon}>🗓️</Text>
                  <Text style={styles.cancelText}>
                    Cancelación/Reprogramación gratuita hasta 24 hs antes
                  </Text>
                </View>

                <Text style={styles.sectionTitle}>DATOS DE ACCESO</Text>

                <TextField
                  label="Calle y número"
                  value={addressStreet}
                  onChangeText={setAddressStreet}
                  placeholder="Ej: Av. Rivadavia 1234"
                  maxLength={150}
                />
                <View style={styles.row}>
                  <View style={styles.half}>
                    <TextField
                      label="Piso / Depto"
                      value={addressUnit}
                      onChangeText={setAddressUnit}
                      placeholder="Ej: 4º B"
                      maxLength={50}
                    />
                  </View>
                  <View style={styles.half}>
                    <TextField
                      label="Barrio / Zona"
                      value={addressNeighborhood}
                      onChangeText={setAddressNeighborhood}
                      placeholder="Ej: Caballito"
                      maxLength={100}
                    />
                  </View>
                </View>

                <Text style={styles.fieldLabel}>
                  Canal de recordatorio de la cita
                </Text>
                <View style={styles.reminderRow}>
                  <Pressable
                    accessibilityRole="radio"
                    accessibilityState={{ selected: reminder === 'whatsapp' }}
                    onPress={() => setReminder('whatsapp')}
                    style={[
                      styles.reminderChip,
                      reminder === 'whatsapp' && styles.reminderChipSelected,
                    ]}
                  >
                    <Text style={styles.reminderIcon}>💬</Text>
                    <Text
                      style={[
                        styles.reminderText,
                        reminder === 'whatsapp' && styles.reminderTextSelected,
                      ]}
                    >
                      WhatsApp
                    </Text>
                  </Pressable>

                  <Pressable
                    accessibilityRole="radio"
                    accessibilityState={{ selected: reminder === 'email' }}
                    onPress={() => setReminder('email')}
                    style={[
                      styles.reminderChip,
                      reminder === 'email' && styles.reminderChipSelected,
                    ]}
                  >
                    <Text style={styles.reminderIcon}>✉️</Text>
                    <Text
                      style={[
                        styles.reminderText,
                        reminder === 'email' && styles.reminderTextSelected,
                      ]}
                    >
                      Email
                    </Text>
                  </Pressable>
                </View>
                <Text style={styles.reminderHint}>{reminderHint}</Text>

                <TextField
                  label="Notas e instrucciones de acceso (opcional)"
                  value={notes}
                  onChangeText={setNotes}
                  placeholder="Ej: Instrucciones para el profesional: tocar timbre del 4B, el portón está pintado de verde, hay perro (avisar al llegar), cochera disponible."
                  multiline
                  numberOfLines={3}
                  maxLength={1000}
                />
                <Text style={styles.fieldHelp}>
                  Contale al profesional cómo llegar y entrar (timbre, portón,
                  mascotas, estacionamiento).
                </Text>
              </>
            ) : null}

            {orderType === 'QUOTE' ? (
              <>
                <View style={styles.quoteContext}>
                  <View style={styles.quoteContextIcon}>
                    <Text style={styles.quoteContextIconText}>
                      {quoteConfig.icon}
                    </Text>
                  </View>
                  <View style={styles.quoteContextBody}>
                    <Text style={styles.quoteContextKicker}>RUBRO DETECTADO</Text>
                    <Text style={styles.quoteContextLabel}>
                      {quoteConfig.label}
                    </Text>
                  </View>
                </View>

                <Text style={styles.quoteFieldLabel}>
                  Atajos rápidos{' '}
                  <Text style={styles.quoteFieldHint}>
                    (tocá los que apliquen)
                  </Text>
                </Text>
                <View style={styles.quoteChips}>
                  {quoteConfig.quickTags.map((tag) => {
                    const selected = quoteTags.includes(tag);

                    return (
                      <Pressable
                        key={tag}
                        accessibilityRole="button"
                        accessibilityState={{ selected }}
                        onPress={() => toggleQuoteTag(tag)}
                        style={[
                          styles.quoteTag,
                          selected && styles.quoteTagSelected,
                        ]}
                      >
                        <Text
                          style={[
                            styles.quoteTagText,
                            selected && styles.quoteTagTextSelected,
                          ]}
                        >
                          {tag}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>

                {quoteConfig.questions.map((question) => (
                  <View key={question.id} style={styles.quoteQuestion}>
                    <Text style={styles.quoteFieldLabel}>
                      {question.label}
                    </Text>
                    <View style={styles.quoteOptions}>
                      {question.options.map((option) => {
                        const selected =
                          quoteAnswers[question.id]?.value === option.value;

                        return (
                          <Pressable
                            key={option.value}
                            accessibilityRole="radio"
                            accessibilityState={{ selected }}
                            onPress={() => pickQuoteAnswer(question, option)}
                            style={[
                              styles.quoteOption,
                              selected && styles.quoteOptionSelected,
                            ]}
                          >
                            <Text
                              style={[
                                styles.quoteOptionText,
                                selected && styles.quoteOptionTextSelected,
                              ]}
                            >
                              {option.label}
                            </Text>
                          </Pressable>
                        );
                      })}
                    </View>
                  </View>
                ))}

                <Text style={styles.quoteFieldLabel}>
                  ¿Cuándo necesitás el trabajo?
                </Text>
                <View style={styles.quoteOptions}>
                  {QUOTE_AVAILABILITY_OPTIONS.map((option) => {
                    const selected = quoteAvailability?.mode === option.mode;

                    return (
                      <Pressable
                        key={option.mode}
                        accessibilityRole="radio"
                        accessibilityState={{ selected }}
                        onPress={() => pickQuoteAvailability(option.mode)}
                        style={[
                          styles.quoteAvailability,
                          selected && styles.quoteAvailabilitySelected,
                        ]}
                      >
                        <Text
                          style={[
                            styles.quoteAvailabilityText,
                            selected && styles.quoteAvailabilityTextSelected,
                          ]}
                        >
                          {option.label}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>

                {quoteAvailability?.mode === 'ventana' ? (
                  <View style={styles.quoteWindow}>
                    <ComboField
                      label="Franja horaria"
                      value={quoteWindow}
                      options={QUOTE_WINDOW_OPTIONS.map((option) => ({
                        value: option.value,
                        label: option.label,
                      }))}
                      onChange={handleQuoteWindow}
                      title="Elegí la franja horaria"
                    />
                  </View>
                ) : null}

                <View style={styles.quoteQuality}>
                  <View style={styles.quoteQualityTop}>
                    <Text style={styles.quoteQualityTitle}>
                      Nivel de detalle de tu solicitud
                    </Text>
                    <View
                      style={[
                        styles.quoteQualityBadge,
                        {
                          backgroundColor: quoteQuality.bg,
                          borderColor: quoteQuality.border,
                        },
                      ]}
                    >
                      <Text
                        style={[
                          styles.quoteQualityBadgeText,
                          { color: quoteQuality.fg },
                        ]}
                      >
                        {quoteQuality.label}
                      </Text>
                    </View>
                  </View>
                  <View style={styles.quoteQualityTrack}>
                    <View
                      style={[
                        styles.quoteQualityFill,
                        {
                          backgroundColor: quoteQuality.fill,
                          width: `${quoteQuality.score}%`,
                        },
                      ]}
                    />
                  </View>
                  <Text style={styles.quoteQualityHint}>
                    {quoteQuality.hint}
                  </Text>
                </View>

                <TextField
                  label="Detalles del trabajo (obligatorio, máx 2000)"
                  value={details}
                  onChangeText={handleQuoteDetails}
                  placeholder={quoteConfig.placeholders.details}
                  multiline
                  numberOfLines={4}
                  maxLength={2000}
                  style={styles.quoteDetailsInput}
                  error={fieldErrors.details?.[0] ?? null}
                />

                {quoteSummaryRows.length ? (
                  <View style={styles.quoteSummary}>
                    <Text style={styles.quoteSummaryTitle}>
                      RESUMEN DE TU PEDIDO
                    </Text>
                    {quoteSummaryRows.map((row) => (
                      <Text key={row} style={styles.quoteSummaryRow}>
                        • {row}
                      </Text>
                    ))}
                  </View>
                ) : null}

                <Text style={styles.quoteFieldLabel}>Fotos y video</Text>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Tocá para seleccionar fotos y video"
                  onPress={openQuotePicker}
                  style={styles.quoteDropzone}
                >
                  <View style={styles.quoteDropzoneIcon}>
                    <Text style={styles.quoteDropzoneIconText}>⬆</Text>
                  </View>
                  <Text style={styles.quoteDropzoneTitle}>
                    Tocá para seleccionar fotos y video
                  </Text>
                  <Text style={styles.quoteDropzoneSub}>
                    Fotos {quotePhotos.length}/{MAX_QUOTE_PHOTOS} · Video{' '}
                    {quoteVideo ? '1/1' : '0/1'} · tocá para agregar más
                  </Text>
                </Pressable>

                {quotePhotos.length ? (
                  <View style={styles.quoteThumbs}>
                    {quotePhotos.map((item, index) => (
                      <View
                        key={`${item.uri}-${index}`}
                        style={styles.quoteThumbWrap}
                      >
                        <Image
                          source={{ uri: item.uri }}
                          style={styles.quoteThumb}
                        />
                        <Pressable
                          accessibilityRole="button"
                          accessibilityLabel="Quitar foto"
                          onPress={() =>
                            patchQuoteForm({
                              photos: quotePhotos.filter(
                                (_, i) => i !== index,
                              ),
                            })
                          }
                          style={styles.quoteThumbRemove}
                        >
                          <Text style={styles.quoteThumbRemoveText}>✕</Text>
                        </Pressable>
                      </View>
                    ))}

                    {quotePhotos.length < MAX_QUOTE_PHOTOS ? (
                      <Pressable
                        accessibilityRole="button"
                        accessibilityLabel="Agregar más fotos"
                        onPress={() => void addQuotePhotos()}
                        style={styles.quoteThumbAdd}
                      >
                        <Text style={styles.quoteThumbAddText}>+</Text>
                      </Pressable>
                    ) : null}
                  </View>
                ) : null}

                {quoteVideo ? (
                  <View style={styles.quoteVideo}>
                    <View style={styles.quoteVideoBox}>
                      <Text style={styles.quoteVideoBoxText}>🎬</Text>
                    </View>
                    <View style={styles.quoteVideoBody}>
                      <Text style={styles.quoteVideoName} numberOfLines={1}>
                        {quoteVideo.name}
                      </Text>
                      <Text style={styles.quoteVideoSize}>
                        {quoteVideo.size ? formatSize(quoteVideo.size) : 'Video'}{' '}
                        · Video
                      </Text>
                    </View>
                    <Pressable
                      accessibilityRole="button"
                      accessibilityLabel="Quitar video"
                      onPress={() => patchQuoteForm({ video: null })}
                      style={styles.quoteVideoRemove}
                    >
                      <Text style={styles.quoteVideoRemoveText}>Quitar</Text>
                    </Pressable>
                  </View>
                ) : null}
              </>
            ) : null}

            <View style={styles.submit}>
              <PrimaryButton
                title={
                  orderType === 'EMERGENCY'
                    ? '🚨 Solicitar urgencia ahora'
                    : orderType === 'SCHEDULED'
                      ? '🗓️ Reservar cita y pagar seña'
                      : '📄 Enviar Solicitud de Presupuesto'
                }
                onPress={handleSubmit}
                loading={submitting}
                disabled={
                  submitting ||
                  (providerLocked && !provider) ||
                  (orderType === 'EMERGENCY' && !photo) ||
                  (orderType === 'QUOTE' && !details.trim())
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
  quoteContext: {
    alignItems: 'center',
    backgroundColor: '#fff7ed',
    borderColor: '#fed7aa',
    borderRadius: radius.sm,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  quoteContextIcon: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#fed7aa',
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 36,
    justifyContent: 'center',
    width: 36,
  },
  quoteContextIconText: {
    fontSize: 17,
  },
  quoteContextBody: {
    flex: 1,
    minWidth: 0,
  },
  quoteContextKicker: {
    color: '#9a3412',
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 0.4,
  },
  quoteContextLabel: {
    color: '#0f172a',
    fontSize: 14,
    fontWeight: '700',
    marginTop: 1,
  },
  quoteFieldLabel: {
    color: '#6b7280',
    fontSize: 11,
    fontWeight: '600',
    marginBottom: spacing.sm,
    marginTop: spacing.lg,
  },
  quoteFieldHint: {
    color: '#94a3b8',
    fontWeight: '400',
  },
  quoteChips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  quoteTag: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 7,
  },
  quoteTagSelected: {
    backgroundColor: '#fff1eb',
    borderColor: '#D24C19',
  },
  quoteTagText: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '600',
  },
  quoteTagTextSelected: {
    color: '#D24C19',
  },
  quoteQuestion: {
    marginTop: spacing.lg,
  },
  quoteOptions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  quoteOption: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 6,
    borderWidth: 1.5,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  quoteOptionSelected: {
    backgroundColor: '#fff1eb',
    borderColor: '#D24C19',
  },
  quoteOptionText: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '600',
  },
  quoteOptionTextSelected: {
    color: '#D24C19',
  },
  quoteAvailability: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.pill,
    borderWidth: 1.5,
    paddingHorizontal: spacing.md,
    paddingVertical: 9,
  },
  quoteAvailabilitySelected: {
    backgroundColor: '#fff1eb',
    borderColor: '#D24C19',
  },
  quoteAvailabilityText: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '600',
  },
  quoteAvailabilityTextSelected: {
    color: '#D24C19',
  },
  quoteWindow: {
    marginTop: spacing.sm,
  },
  quoteQuality: {
    backgroundColor: '#f8fafc',
    borderColor: '#e2e8f0',
    borderRadius: radius.sm,
    borderWidth: 1,
    marginTop: spacing.lg,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  quoteQualityTop: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'space-between',
  },
  quoteQualityTitle: {
    color: '#0c2a4d',
    flex: 1,
    fontSize: 11,
    fontWeight: '700',
  },
  quoteQualityBadge: {
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: 8,
    paddingVertical: 2,
  },
  quoteQualityBadgeText: {
    fontSize: 11,
    fontWeight: '800',
  },
  quoteQualityTrack: {
    backgroundColor: '#e5e7eb',
    borderRadius: radius.pill,
    height: 7,
    marginTop: 7,
    overflow: 'hidden',
  },
  quoteQualityFill: {
    borderRadius: radius.pill,
    height: '100%',
  },
  quoteQualityHint: {
    color: '#6b7280',
    fontSize: 11,
    marginTop: 7,
  },
  quoteDetailsInput: {
    minHeight: 110,
    textAlignVertical: 'top',
  },
  quoteSummary: {
    backgroundColor: '#f8fafc',
    borderColor: '#e2e8f0',
    borderRadius: radius.sm,
    borderWidth: 1,
    marginTop: spacing.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  quoteSummaryTitle: {
    color: '#94a3b8',
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 0.4,
    marginBottom: 3,
  },
  quoteSummaryRow: {
    color: '#374151',
    fontSize: 12,
    marginTop: 1,
  },
  quoteDropzone: {
    alignItems: 'center',
    backgroundColor: '#f9fafb',
    borderColor: '#cbd5e1',
    borderRadius: radius.sm,
    borderStyle: 'dashed',
    borderWidth: 1.5,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.lg,
  },
  quoteDropzoneIcon: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#fed7aa',
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 46,
    justifyContent: 'center',
    marginBottom: spacing.sm,
    width: 46,
  },
  quoteDropzoneIconText: {
    color: '#D24C19',
    fontSize: 20,
    fontWeight: '700',
  },
  quoteDropzoneTitle: {
    color: '#0c2a4d',
    fontSize: 13,
    fontWeight: '700',
    textAlign: 'center',
  },
  quoteDropzoneSub: {
    color: '#6b7280',
    fontSize: 11,
    marginTop: spacing.xs,
    textAlign: 'center',
  },
  quoteThumbs: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  quoteThumbWrap: {
    height: 84,
    width: 84,
  },
  quoteThumb: {
    borderColor: '#e5e7eb',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: '100%',
    width: '100%',
  },
  quoteThumbRemove: {
    alignItems: 'center',
    backgroundColor: 'rgba(15,23,42,0.72)',
    borderRadius: radius.pill,
    height: 22,
    justifyContent: 'center',
    position: 'absolute',
    right: 3,
    top: 3,
    width: 22,
  },
  quoteThumbRemoveText: {
    color: colors.surface,
    fontSize: 11,
    fontWeight: '700',
    lineHeight: 13,
  },
  quoteThumbAdd: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#cbd5e1',
    borderRadius: radius.sm,
    borderStyle: 'dashed',
    borderWidth: 1.5,
    height: 84,
    justifyContent: 'center',
    width: 84,
  },
  quoteThumbAddText: {
    color: '#D24C19',
    fontSize: 24,
    fontWeight: '400',
  },
  quoteVideo: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.sm,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.md,
    padding: spacing.sm,
  },
  quoteVideoBox: {
    alignItems: 'center',
    backgroundColor: '#0c2a4d',
    borderRadius: 6,
    height: 64,
    justifyContent: 'center',
    width: 64,
  },
  quoteVideoBoxText: {
    fontSize: 24,
  },
  quoteVideoBody: {
    flex: 1,
    minWidth: 0,
  },
  quoteVideoName: {
    color: '#0f172a',
    fontSize: 12,
    fontWeight: '700',
  },
  quoteVideoSize: {
    color: '#6b7280',
    fontSize: 11,
    marginTop: 2,
  },
  quoteVideoRemove: {
    backgroundColor: '#fef2f2',
    borderColor: '#fecaca',
    borderRadius: 6,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 6,
  },
  quoteVideoRemoveText: {
    color: '#b91c1c',
    fontSize: 12,
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
  fieldLabel: {
    color: '#6b7280',
    fontSize: 11,
    fontWeight: '600',
    marginBottom: 5,
    marginTop: spacing.md,
  },
  fieldLabelSpaced: {
    marginTop: spacing.lg,
  },
  fieldHelp: {
    color: '#6b7280',
    fontSize: 12,
    marginBottom: spacing.sm,
    marginTop: 5,
  },
  calendarNav: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.sm,
  },
  calendarNavButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 6,
    borderWidth: 1,
    justifyContent: 'center',
    minWidth: 34,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  calendarNavButtonDisabled: {
    opacity: 0.4,
  },
  calendarNavText: {
    color: NAVY,
    fontSize: 16,
    fontWeight: '700',
    lineHeight: 18,
  },
  calendarRange: {
    color: '#64748b',
    flex: 1,
    fontSize: 12,
    fontWeight: '600',
    textAlign: 'center',
  },
  calendarGrid: {
    flexDirection: 'row',
    gap: 6,
  },
  dayCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: 6,
    borderWidth: 1,
    flex: 1,
    justifyContent: 'center',
    minWidth: 0,
    paddingVertical: spacing.sm,
  },
  dayCardSelected: {
    backgroundColor: '#D24C19',
    borderColor: '#D24C19',
  },
  dayCardClosed: {
    backgroundColor: '#f8fafc',
    borderColor: '#e2e8f0',
    borderStyle: 'dashed',
  },
  dayWeekday: {
    color: '#374151',
    fontSize: 10,
    fontWeight: '600',
    opacity: 0.72,
    textTransform: 'uppercase',
  },
  dayNumber: {
    color: '#374151',
    fontSize: 16,
    fontWeight: '700',
    lineHeight: 18,
  },
  dayNumberToday: {
    color: '#D24C19',
  },
  dayTextSelected: {
    color: '#ffffff',
    opacity: 1,
  },
  dayTextClosed: {
    color: '#cbd5e1',
  },
  calendarFallback: {
    backgroundColor: '#fffbeb',
    borderColor: '#fde68a',
    borderRadius: 6,
    borderWidth: 1,
    color: '#b45309',
    fontSize: 11,
    marginTop: spacing.sm,
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  slotsWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  slotsEmpty: {
    backgroundColor: '#fef2f2',
    borderColor: '#fecaca',
    borderRadius: 6,
    borderWidth: 1,
    color: '#b91c1c',
    fontSize: 12,
    paddingHorizontal: 12,
    paddingVertical: 9,
  },
  slotChip: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.pill,
    borderWidth: 1.5,
    paddingHorizontal: 15,
    paddingVertical: spacing.sm,
  },
  slotChipSelected: {
    backgroundColor: '#fff1eb',
    borderColor: '#D24C19',
  },
  slotText: {
    color: '#374151',
    fontSize: 12.5,
    fontWeight: '600',
  },
  slotTextSelected: {
    color: '#D24C19',
  },
  scheduledSelectors: {
    marginTop: spacing.md,
  },
  policyCard: {
    backgroundColor: '#fff7ed',
    borderColor: '#fed7aa',
    borderLeftColor: '#D24C19',
    borderLeftWidth: 4,
    borderRadius: 6,
    borderWidth: 1,
    marginTop: spacing.lg,
    padding: spacing.md,
  },
  policyHead: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: 10,
  },
  policyIcon: {
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderColor: '#fed7aa',
    borderRadius: radius.pill,
    borderWidth: 1,
    height: 28,
    justifyContent: 'center',
    width: 28,
  },
  policyIconText: {
    color: '#D24C19',
    fontSize: 15,
    fontWeight: '800',
  },
  policyHeadBody: {
    flex: 1,
    minWidth: 0,
  },
  policyTitle: {
    color: '#9a3412',
    fontSize: 12.5,
    fontWeight: '700',
  },
  policyText: {
    color: '#9a3412',
    fontSize: 12,
    marginTop: 4,
  },
  breakdown: {
    backgroundColor: '#ffffff',
    borderColor: '#fed7aa',
    borderRadius: 6,
    borderWidth: 1,
    marginTop: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 10,
  },
  breakdownRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 10,
    justifyContent: 'space-between',
    paddingVertical: 4,
  },
  breakdownRowLine: {
    borderStyle: 'dashed',
    borderTopColor: '#fed7aa',
    borderTopWidth: 1,
  },
  breakdownLabel: {
    color: '#6b7280',
    flexShrink: 1,
    fontSize: 12.5,
  },
  breakdownValue: {
    color: '#0f172a',
    fontSize: 13,
    fontWeight: '700',
  },
  breakdownNow: {
    color: '#D24C19',
    fontSize: 15,
    fontWeight: '800',
  },
  cancelBanner: {
    alignItems: 'center',
    backgroundColor: '#ecfdf5',
    borderColor: '#a7f3d0',
    borderRadius: 6,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 9,
    marginTop: 10,
    paddingHorizontal: spacing.md,
    paddingVertical: 10,
  },
  cancelIcon: {
    fontSize: 15,
  },
  cancelText: {
    color: '#047857',
    flex: 1,
    fontSize: 12.5,
    fontWeight: '700',
  },
  reminderRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  reminderChip: {
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderColor: '#d1d5db',
    borderRadius: radius.pill,
    borderWidth: 1.5,
    flexDirection: 'row',
    gap: 7,
    paddingHorizontal: 15,
    paddingVertical: spacing.sm,
  },
  reminderChipSelected: {
    backgroundColor: '#fff1eb',
    borderColor: '#D24C19',
  },
  reminderIcon: {
    fontSize: 13,
  },
  reminderText: {
    color: '#374151',
    fontSize: 12.5,
    fontWeight: '600',
  },
  reminderTextSelected: {
    color: '#D24C19',
  },
  reminderHint: {
    color: '#6b7280',
    fontSize: 11.5,
    marginBottom: spacing.sm,
    marginTop: 7,
  },
});
