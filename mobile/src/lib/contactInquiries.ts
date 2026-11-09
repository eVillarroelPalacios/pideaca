import type { ContactInquiryStatus, ContactInquiryType } from '@/types/api';

/**
 * Etiquetas y colores de "Consultas y sugerencias".
 *
 * Espeja los textos y la paleta de la seccion web para que el admin vea lo
 * mismo en la app y en el panel.
 */

export type InquiryFilter = ContactInquiryStatus | 'all';

export const INQUIRY_FILTERS: { value: InquiryFilter; label: string }[] = [
  { value: 'all', label: 'Todas' },
  { value: 'new', label: 'Nuevas' },
  { value: 'in_progress', label: 'En proceso' },
  { value: 'resolved', label: 'Resueltas' },
];

export const INQUIRY_STATUS_OPTIONS: { value: ContactInquiryStatus; label: string }[] = [
  { value: 'new', label: 'Nueva' },
  { value: 'in_progress', label: 'En proceso' },
  { value: 'resolved', label: 'Resuelta' },
];

type BadgeStyle = {
  background: string;
  border: string;
  foreground: string;
};

const STATUS_STYLES: Record<ContactInquiryStatus, BadgeStyle> = {
  new: { background: '#eff6ff', border: '#bfdbfe', foreground: '#1d4ed8' },
  in_progress: { background: '#fffbeb', border: '#fde68a', foreground: '#b45309' },
  resolved: { background: '#d1fae5', border: '#a7f3d0', foreground: '#047857' },
};

const TYPE_STYLES: Record<ContactInquiryType, BadgeStyle> = {
  consulta: { background: '#eef2ff', border: '#c7d2fe', foreground: '#4338ca' },
  sugerencia: { background: '#fdf4ff', border: '#f5d0fe', foreground: '#a21caf' },
  reclamo: { background: '#fef2f2', border: '#fecaca', foreground: '#b91c1c' },
};

const TYPE_LABELS: Record<ContactInquiryType, string> = {
  consulta: 'Consulta',
  sugerencia: 'Sugerencia',
  reclamo: 'Reclamo',
};

export function inquiryStatusLabel(status: string | null | undefined): string {
  return INQUIRY_STATUS_OPTIONS.find((option) => option.value === status)?.label ?? 'Nueva';
}

export function inquiryTypeLabel(type: string | null | undefined): string {
  return TYPE_LABELS[type as ContactInquiryType] ?? 'Consulta';
}

export function inquiryStatusStyle(status: string | null | undefined): BadgeStyle {
  return STATUS_STYLES[status as ContactInquiryStatus] ?? STATUS_STYLES.new;
}

export function inquiryTypeStyle(type: string | null | undefined): BadgeStyle {
  return TYPE_STYLES[type as ContactInquiryType] ?? TYPE_STYLES.consulta;
}

/** Fecha y hora con el mismo formato que usa la web (es-AR). */
export function formatInquiryDate(iso: string | null): string {
  if (!iso) {
    return '—';
  }

  const date = new Date(iso);

  if (Number.isNaN(date.getTime())) {
    return '—';
  }

  return date.toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

/** Enlace mailto con el asunto que arma la web ("Responder por correo"). */
export function replyMailto(email: string, type: string | null | undefined): string {
  const subject = encodeURIComponent(
    `Respuesta a tu ${inquiryTypeLabel(type).toLowerCase()} - PideAca`,
  );

  return `mailto:${email}?subject=${subject}`;
}
