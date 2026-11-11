import type { MarketMyOrder } from '@/types/api';

/** Mismos textos y colores que `FD_ORDER_STATUS` de la web. */
export const ORDER_STATUS: Record<
  string,
  { label: string; color: string; background: string; dot: string }
> = {
  pending: { label: 'Pendiente', color: '#b45309', background: '#fef3c7', dot: '#f59e0b' },
  confirmed: { label: 'Confirmado', color: '#1d4ed8', background: '#dbeafe', dot: '#3b82f6' },
  in_preparation: {
    label: 'En preparación',
    color: '#6d28d9',
    background: '#ede9fe',
    dot: '#8b5cf6',
  },
  on_the_way: { label: 'En camino', color: '#0369a1', background: '#e0f2fe', dot: '#0ea5e9' },
  delivered: { label: 'Entregado', color: '#047857', background: '#d1fae5', dot: '#10b981' },
  cancelled: { label: 'Cancelado', color: '#b91c1c', background: '#fee2e2', dot: '#ef4444' },
};

export const ORDER_STATUS_ORDER = [
  'pending',
  'confirmed',
  'in_preparation',
  'on_the_way',
  'delivered',
  'cancelled',
];

/** "En curso" = todo lo que todavía no terminó. */
export const ACTIVE_STATUSES = ['pending', 'confirmed', 'in_preparation', 'on_the_way'];

export const PAYMENT_METHOD_LABELS: Record<string, string> = {
  efectivo: 'Efectivo',
  transfer: 'Transferencia',
  transferencia: 'Transferencia',
  transferencia_bancaria: 'Transferencia bancaria',
  mercado_pago: 'Mercado Pago',
  mercadopago: 'Mercado Pago',
};

export const PAYMENT_STATUS: Record<string, { label: string; color: string; background: string }> = {
  pending: { label: 'Pago pendiente', color: '#b45309', background: '#fef3c7' },
  processing: { label: 'Pago en proceso', color: '#1d4ed8', background: '#dbeafe' },
  paid: { label: 'Pagado', color: '#047857', background: '#d1fae5' },
  failed: { label: 'Pago fallido', color: '#b91c1c', background: '#fee2e2' },
  refunded: { label: 'Reembolsado', color: '#6b7280', background: '#f1f5f9' },
};

export type TrackStep = { key: string; label: string; icon: string };

const SHIPPING_STEPS: TrackStep[] = [
  { key: 'pending', label: 'Recibido', icon: '👤' },
  { key: 'confirmed', label: 'Confirmado', icon: '✓' },
  { key: 'in_preparation', label: 'En preparación', icon: '🔥' },
  { key: 'on_the_way', label: 'En camino', icon: '🛵' },
  { key: 'delivered', label: 'Entregado', icon: '🏠' },
];

const TAKEAWAY_STEPS: TrackStep[] = [
  { key: 'pending', label: 'Recibido', icon: '👤' },
  { key: 'confirmed', label: 'Confirmado', icon: '✓' },
  { key: 'in_preparation', label: 'En preparación', icon: '🔥' },
  { key: 'on_the_way', label: 'Listo para retirar', icon: '🛍️' },
  { key: 'delivered', label: 'Retirado', icon: '✓' },
];

export function isTakeaway(order: MarketMyOrder): boolean {
  return order.delivery_type === 'takeaway';
}

export function statusInfo(status: string) {
  return ORDER_STATUS[status] ?? {
    label: status || '-',
    color: '#334155',
    background: '#f1f5f9',
    dot: '#94a3b8',
  };
}

export function paymentMethodLabel(method: string | null): string {
  return PAYMENT_METHOD_LABELS[method ?? ''] ?? method ?? 'A acordar';
}

export function paymentInfo(payments: MarketMyOrder['payments']) {
  const payment = payments?.[0];

  if (!payment) {
    return null;
  }

  return {
    ...(PAYMENT_STATUS[payment.status] ?? {
      label: payment.status || '-',
      color: '#334155',
      background: '#f1f5f9',
    }),
  };
}

function parseDate(value: string | null | undefined): Date | null {
  if (!value) {
    return null;
  }

  const date = new Date(String(value).replace(' ', 'T'));

  return Number.isNaN(date.getTime()) ? null : date;
}

export function orderDateTime(value: string | null | undefined): string {
  const date = parseDate(value);

  return date
    ? date.toLocaleString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    : '';
}

export function orderDay(value: string | null | undefined): string {
  const date = parseDate(value);

  if (!date) {
    return '';
  }

  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');

  return `${y}-${m}-${d}`;
}

export function displayDate(ymd: string): string {
  if (!ymd) {
    return 'Elegir fecha';
  }

  const [y, m, d] = ymd.split('-');

  return y && m && d ? `${d}/${m}/${y}` : ymd;
}

export function initials(name: string | null | undefined): string {
  const parts = String(name ?? '')
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (parts.length === 0) {
    return '?';
  }

  return (parts[0][0] + (parts[1]?.[0] ?? '')).toUpperCase();
}

export function whatsappUrl(phone: string | null | undefined, message: string): string {
  const digits = String(phone ?? '').replace(/\D/g, '');

  return `https://wa.me/${digits}?text=${encodeURIComponent(message)}`;
}

/** Titulo grande del bloque "estado en tiempo real". */
export function liveTitle(order: MarketMyOrder): string {
  const firstName = String(order.driver?.name ?? 'Tu repartidor').split(' ')[0];

  if (order.status === 'pending') {
    return 'El comercio está revisando tu pedido';
  }

  if (order.status === 'confirmed') {
    return '¡Pedido confirmado! Preparan todo';
  }

  if (isTakeaway(order)) {
    if (order.status === 'in_preparation') {
      return 'Tu pedido se está preparando para retirar';
    }

    if (order.status === 'on_the_way') {
      return '¡Tu pedido está listo para retirar!';
    }

    if (order.status === 'delivered') {
      return 'Pedido retirado';
    }
  }

  if (order.status === 'in_preparation') {
    return 'Tu pedido se encuentra en el horno';
  }

  if (order.status === 'on_the_way') {
    return order.driver
      ? `${firstName} va en camino con tu pedido`
      : 'Tu pedido va en camino hacia tu domicilio';
  }

  if (order.status === 'delivered') {
    return 'Pedido entregado';
  }

  if (order.status === 'cancelled') {
    return 'Pedido cancelado';
  }

  return 'Seguí tu pedido';
}

/** ETA aproximada según el estado (misma fórmula que la web). */
export function etaLabel(order: MarketMyOrder): string {
  if (!ACTIVE_STATUSES.includes(order.status)) {
    return '';
  }

  const minutes =
    { pending: 60, confirmed: 50, in_preparation: 35, on_the_way: 20 }[order.status] ?? 40;
  const created = parseDate(order.created_at);

  if (!created) {
    return '';
  }

  const arrive = new Date(created.getTime() + minutes * 60000);
  const takeaway = isTakeaway(order);

  if (arrive.getTime() - Date.now() <= 0) {
    return takeaway ? 'Listo en breve' : 'Llega en breve';
  }

  const hh = String(arrive.getHours()).padStart(2, '0');
  const mm = String(arrive.getMinutes()).padStart(2, '0');

  return `${takeaway ? 'Listo ≈ ' : 'Llega ≈ '}${hh}:${mm} hs`;
}

export function trackSteps(order: MarketMyOrder): TrackStep[] {
  return isTakeaway(order) ? TAKEAWAY_STEPS : SHIPPING_STEPS;
}

export function stepSubtitle(order: MarketMyOrder, key: string): string {
  if (key === 'pending') {
    return 'Esperando confirmación';
  }

  if (key === 'confirmed') {
    return 'Va a cocina';
  }

  if (key === 'in_preparation') {
    return 'En cocina';
  }

  if (key === 'on_the_way') {
    if (isTakeaway(order)) {
      return 'Te avisamos';
    }

    return order.driver ? `Con ${String(order.driver.name).split(' ')[0]}` : 'Próximamente';
  }

  if (key === 'delivered') {
    return isTakeaway(order) ? 'Retirado' : 'Destino';
  }

  return '';
}

/** Búsqueda por local, código o plato (sobre lo ya cargado). */
export function matchesOrderSearch(order: MarketMyOrder, text: string): boolean {
  const term = text.trim().toLowerCase();

  if (!term) {
    return true;
  }

  if (String(order.order_number ?? '').toLowerCase().includes(term)) {
    return true;
  }

  if (String(order.provider?.business_name ?? '').toLowerCase().includes(term)) {
    return true;
  }

  return (order.items ?? []).some(
    (item) =>
      String(item.product_name ?? '').toLowerCase().includes(term) ||
      String(item.variant_name ?? '').toLowerCase().includes(term),
  );
}

export function ratingLabel(stars: number): string {
  return { 5: 'Excelente', 4: 'Muy bueno', 3: 'Bueno', 2: 'Regular', 1: 'Malo' }[
    Number(stars)
  ] ?? '';
}

export function deliveryAddress(order: MarketMyOrder): string {
  const address = order.address;

  if (!address) {
    return 'Sin dirección registrada';
  }

  const line = [address.street, address.number].filter(Boolean).join(' ');
  const floor = address.floor_apartment ? `, ${address.floor_apartment}` : '';

  return `${line}${floor}`.trim() || 'Sin dirección registrada';
}

export function itemsResume(order: MarketMyOrder): string {
  const items = order.items ?? [];

  if (items.length === 0) {
    return 'Sin productos registrados';
  }

  const parts = items.slice(0, 3).map((item) => {
    const variant = item.variant_name ? ` (${item.variant_name})` : '';

    return `${item.quantity}× ${item.product_name}${variant}`;
  });

  if (items.length > 3) {
    parts.push(`+${items.length - 3} más`);
  }

  return parts.join(' + ');
}
