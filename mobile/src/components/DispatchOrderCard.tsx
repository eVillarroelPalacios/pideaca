import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import { money } from '@/lib/money';
import {
  PAYMENT_METHOD_LABELS,
  initials,
  orderDateTime,
  paymentInfo,
  statusInfo,
} from '@/lib/orderTracking';
import { colors, radius, spacing } from '@/theme';
import type {
  ProviderDispatchDriver,
  ProviderDispatchItem,
  ProviderDispatchOrder,
  ProviderDispatchPayment,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

const DELIVERY_STATUS: Record<string, { label: string; color: string; background: string }> = {
  pending: { label: 'Esperando reparto', color: '#b45309', background: '#fef3c7' },
  assigned: { label: 'Repartidor asignado', color: '#1d4ed8', background: '#dbeafe' },
  picked_up: { label: 'Retiró el pedido', color: '#0369a1', background: '#e0f2fe' },
  delivered: { label: 'Entregado', color: '#047857', background: '#d1fae5' },
};

const FLOW: Record<string, { next: string; label: string }> = {
  pending: { next: 'confirmed', label: 'Confirmar pedido' },
  confirmed: { next: 'in_preparation', label: 'Marcar en preparación' },
  in_preparation: { next: 'on_the_way', label: 'Marcar en camino' },
  on_the_way: { next: 'delivered', label: 'Marcar entregado' },
};

const CANCELLABLE = ['pending', 'confirmed', 'in_preparation'];

function parseDate(value: string | null | undefined): Date | null {
  if (!value) {
    return null;
  }

  const date = new Date(String(value).replace(' ', 'T'));

  return Number.isNaN(date.getTime()) ? null : date;
}

function timeAgo(value: string | null | undefined): string {
  const date = parseDate(value);

  if (!date) {
    return '';
  }

  const diff = Date.now() - date.getTime();

  if (diff < 0) {
    return 'recién';
  }

  const minutes = Math.floor(diff / 60000);

  if (minutes < 1) {
    return 'recién';
  }

  if (minutes < 60) {
    return `hace ${minutes} min`;
  }

  const hours = Math.floor(minutes / 60);

  if (hours < 24) {
    return `hace ${hours} h`;
  }

  const days = Math.floor(hours / 24);

  return `hace ${days} ${days === 1 ? 'día' : 'días'}`;
}

function numberBadge(index: number): string {
  return index > 0 ? (index < 10 ? `0${index}` : String(index)) : '--';
}

function paymentMethodLabel(method: string | null): string {
  if (!method) {
    return '';
  }

  const labels = PAYMENT_METHOD_LABELS as Record<string, string>;

  if (labels[method]) {
    return labels[method];
  }

  return method
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

function paymentLine(order: ProviderDispatchOrder): string {
  const payment: ProviderDispatchPayment | undefined = order.payments?.[0];
  const method = payment?.provider || order.payment_method || '';
  const label = paymentMethodLabel(method);

  if (payment?.status === 'paid') {
    return label ? `Pagado online mediante ${label}` : 'Pago confirmado';
  }

  if (label) {
    return label;
  }

  return 'Sin datos de pago';
}

function waLink(phone: string | null, message: string): string {
  let digits = String(phone ?? '').replace(/\D/g, '');

  if (digits.length <= 10) {
    digits = `54${digits.replace(/^0+/, '')}`;
  }

  return `https://wa.me/${digits}?text=${encodeURIComponent(message)}`;
}

function stepIndex(status: string): number {
  if (status === 'delivered') {
    return 3;
  }

  if (status === 'on_the_way') {
    return 2;
  }

  if (status === 'confirmed' || status === 'in_preparation') {
    return 1;
  }

  return 0;
}

type Props = {
  order: ProviderDispatchOrder;
  index: number;
  trustedEnabled: boolean;
  collapsed: boolean;
  busy: boolean;
  onToggle: () => void;
  onNextStatus: (order: ProviderDispatchOrder, status: string, label: string) => void;
  onCancel: (order: ProviderDispatchOrder) => void;
  onSendToPool: (order: ProviderDispatchOrder) => void;
  onRate: (order: ProviderDispatchOrder) => void;
};

/**
 * Tarjeta de pedido del Centro de Despacho. Espeja `fdRenderOrder` de la web:
 * cliente, entrega, repartidor, productos, totales, calificacion y contacto.
 */
export function DispatchOrderCard({
  order,
  index,
  trustedEnabled,
  collapsed,
  busy,
  onToggle,
  onNextStatus,
  onCancel,
  onSendToPool,
  onRate,
}: Props) {
  const status = statusInfo(order.status);
  const client = order.user;
  const driver: ProviderDispatchDriver | null = order.driver;
  const takeaway = order.delivery_type === 'takeaway';
  const flow = FLOW[order.status];
  const deliveryInfo = DELIVERY_STATUS[order.delivery_status ?? ''] ?? DELIVERY_STATUS.pending;
  const payment = paymentInfo(order.payments);
  const canSendToPool =
    !order.driver_id &&
    !order.released_to_pool_at &&
    trustedEnabled &&
    !takeaway &&
    order.status !== 'delivered' &&
    order.status !== 'cancelled';

  let nextLabel = flow?.label ?? '';

  if (takeaway && order.status === 'in_preparation') {
    nextLabel = 'Marcar listo para retirar';
  }

  if (takeaway && order.status === 'on_the_way') {
    nextLabel = 'Marcar retirado';
  }

  const steps = takeaway
    ? ['Confirmado', 'En preparación', 'Listo para retirar']
    : ['Confirmado', 'En camino a local', 'A entrega'];
  const step = stepIndex(order.status);

  let driverHelp = 'Los repartidores toman el pedido desde su panel cuando está listo para retirar.';

  if (order.released_to_pool_at) {
    driverHelp = `Enviado al pool general el ${orderDateTime(order.released_to_pool_at)}. Cualquier repartidor puede tomarlo.`;
  } else if (trustedEnabled) {
    driverHelp =
      'Solo visible para tus repartidores de confianza. No se libera automáticamente: usá "Enviar al pool" para abrirlo a todos.';
  }

  function call(phone: string | null) {
    const digits = String(phone ?? '').replace(/[^\d+]/g, '');

    if (digits) {
      void Linking.openURL(`tel:${digits}`);
    }
  }

  function openWhatsApp() {
    if (!client?.phone) {
      return;
    }

    const message = `Hola ${client.name || ''}, te contactamos por tu pedido ${order.order_number}.`;
    void Linking.openURL(waLink(client.phone, message));
  }

  return (
    <View style={styles.card}>
      <View style={styles.header}>
        <View style={styles.headerTop}>
          <View style={styles.indexBadge}>
            <Text style={styles.indexText}>#{numberBadge(index)}</Text>
          </View>
          <Text selectable style={styles.orderNumber}>
            {order.order_number}
          </Text>
          <View style={[styles.statusBadge, { backgroundColor: status.background }]}>
            <Text style={[styles.statusText, { color: status.color }]}>{status.label}</Text>
          </View>
          <View style={styles.headerSpacer} />
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={collapsed ? 'Expandir pedido' : 'Contraer pedido'}
            onPress={onToggle}
            style={styles.collapseButton}
          >
            <Text style={styles.collapseText}>{collapsed ? '⌄' : '⌃'}</Text>
          </Pressable>
        </View>

        <View style={styles.actionsRow}>
          {flow ? (
            <Pressable
              accessibilityRole="button"
              disabled={busy}
              onPress={() => onNextStatus(order, flow.next, nextLabel)}
              style={[styles.actionButton, styles.actionPrimary, busy && styles.disabled]}
            >
              <Text style={styles.actionPrimaryText}>{nextLabel}</Text>
            </Pressable>
          ) : null}

          {CANCELLABLE.includes(order.status) ? (
            <Pressable
              accessibilityRole="button"
              disabled={busy}
              onPress={() => onCancel(order)}
              style={[styles.actionButton, styles.actionDanger, busy && styles.disabled]}
            >
              <Text style={styles.actionDangerText}>✕ Cancelar</Text>
            </Pressable>
          ) : null}

          {canSendToPool ? (
            <Pressable
              accessibilityRole="button"
              disabled={busy}
              onPress={() => onSendToPool(order)}
              style={[styles.actionButton, busy && styles.disabled]}
            >
              <Text style={styles.actionText}>➤ Enviar al pool</Text>
            </Pressable>
          ) : null}
        </View>

        <View style={styles.dateRow}>
          <Text style={styles.dateText}>🕒 {orderDateTime(order.created_at)}</Text>
          <Text style={styles.dateDot}>·</Text>
          <Text style={styles.dateText}>{timeAgo(order.created_at)}</Text>
        </View>
      </View>

      {collapsed ? null : (
        <View style={styles.body}>
          <View style={styles.block}>
            <Text style={styles.blockTitle}>Datos del cliente</Text>
            <View style={styles.infoRow}>
              <View style={styles.avatar}>
                <Text style={styles.avatarText}>{initials(client?.name)}</Text>
              </View>
              <View style={styles.infoTextWrap}>
                <Text style={styles.infoName}>{client?.name || 'Cliente'}</Text>
                {client?.email ? <Text style={styles.infoMeta}>{client.email}</Text> : null}
                {client?.phone ? (
                  <Pressable accessibilityRole="link" onPress={() => call(client.phone)}>
                    <Text style={styles.infoLink}>📞 {client.phone}</Text>
                  </Pressable>
                ) : null}
              </View>
            </View>

            {takeaway ? (
              <View style={styles.deliveryRow}>
                <Text style={styles.deliveryIcon}>🏪</Text>
                <View style={styles.infoTextWrap}>
                  <Text style={styles.deliveryTitle}>Retiro en el local</Text>
                  <Text style={styles.infoMeta}>
                    El cliente retira el pedido en el comercio. No requiere dirección de entrega.
                  </Text>
                </View>
              </View>
            ) : (
              <View style={styles.deliveryRow}>
                <Text style={styles.deliveryIcon}>📍</Text>
                <View style={styles.infoTextWrap}>
                  {order.address ? (
                    <>
                      <Text style={styles.deliveryTitle}>
                        {order.address.street} {order.address.number ?? ''}
                        {order.address.floor_apartment
                          ? ` · ${order.address.floor_apartment}`
                          : ''}
                        {order.address.postal_code ? ` · CP ${order.address.postal_code}` : ''}
                      </Text>
                      {order.address.notes ? (
                        <Text style={styles.noteChip}>🔔 {order.address.notes}</Text>
                      ) : null}
                    </>
                  ) : (
                    <Text style={styles.infoMeta}>Sin dirección registrada</Text>
                  )}
                </View>
              </View>
            )}

            {order.notes ? (
              <Text style={styles.orderNote}>
                <Text style={styles.orderNoteLabel}>Nota del pedido: </Text>
                {order.notes}
              </Text>
            ) : null}
          </View>

          <View style={styles.block}>
            <View style={styles.blockHeader}>
              <Text style={styles.blockTitle}>{takeaway ? 'Entrega' : 'Repartidor asignado'}</Text>
              {takeaway ? (
                <View style={styles.smallBadge}>
                  <Text style={styles.smallBadgeText}>Retiro en local</Text>
                </View>
              ) : (
                <View
                  style={[
                    styles.smallBadge,
                    {
                      backgroundColor: driver
                        ? deliveryInfo.background
                        : '#f1f5f9',
                    },
                  ]}
                >
                  <Text
                    style={[
                      styles.smallBadgeText,
                      { color: driver ? deliveryInfo.color : '#64748b' },
                    ]}
                  >
                    {driver ? deliveryInfo.label : 'Sin asignar'}
                  </Text>
                </View>
              )}
            </View>

            {takeaway ? (
              <View style={styles.driverRow}>
                <Text style={styles.driverIcon}>🏪</Text>
                <View style={styles.infoTextWrap}>
                  <Text style={styles.driverTitle}>Pedido para preparar en el comercio</Text>
                  <Text style={styles.infoMeta}>
                    No se envía a la cola de repartidores. El cliente lo retira cuando esté listo.
                  </Text>
                </View>
              </View>
            ) : driver ? (
              <View style={styles.driverRow}>
                <View style={[styles.avatar, styles.driverAvatar]}>
                  <Text style={[styles.avatarText, styles.driverAvatarText]}>
                    {initials(driver.name)}
                  </Text>
                </View>
                <View style={styles.infoTextWrap}>
                  <Text style={styles.infoName}>{driver.name || 'Repartidor'}</Text>
                  {driver.email ? <Text style={styles.infoMeta}>{driver.email}</Text> : null}
                  {driver.phone ? <Text style={styles.infoMeta}>{driver.phone}</Text> : null}
                </View>
              </View>
            ) : (
              <View style={styles.driverRow}>
                <Text style={styles.driverIcon}>🚫</Text>
                <View style={styles.infoTextWrap}>
                  <Text style={styles.driverTitle}>Todavía no hay repartidor asignado</Text>
                  <Text style={styles.infoMeta}>{driverHelp}</Text>
                </View>
              </View>
            )}

            {order.status === 'cancelled' ? (
              <Text style={styles.cancelledText}>
                Pedido cancelado: el reparto no continúa.
              </Text>
            ) : (
              <View style={styles.stepBar}>
                {steps.map((label, position) => {
                  const done = position < step;
                  const current = position === step;

                  return (
                    <View key={label} style={styles.stepItem}>
                      <Text
                        numberOfLines={1}
                        style={[
                          styles.stepLabel,
                          done && styles.stepLabelDone,
                          current && styles.stepLabelNow,
                        ]}
                      >
                        {position + 1}. {label}
                      </Text>
                      <View style={styles.stepTrack}>
                        <View style={[styles.stepFill, { width: done ? '100%' : '0%' }]} />
                      </View>
                    </View>
                  );
                })}
              </View>
            )}
          </View>

          <View style={styles.block}>
            <View style={styles.itemsHead}>
              <Text style={styles.itemsHeadText}>Detalle de productos</Text>
              <Text style={styles.itemsHeadText}>Subtotal</Text>
            </View>

            {order.items.map((item: ProviderDispatchItem) => (
              <View key={item.id} style={styles.itemRow}>
                <View style={styles.qtyBadge}>
                  <Text style={styles.qtyText}>{item.quantity}×</Text>
                </View>
                <View style={styles.itemInfo}>
                  <Text style={styles.itemName}>
                    {item.product_name}
                    {item.variant_name ? (
                      <Text style={styles.itemVariant}> ({item.variant_name})</Text>
                    ) : null}
                  </Text>
                  <Text style={styles.itemUnit}>{money(item.unit_price)} c/u</Text>
                  {item.options.length > 0 ? (
                    <Text style={styles.itemOptions}>
                      {item.options
                        .map(
                          (option) =>
                            `${option.option_name}${
                              Number(option.extra_price) > 0
                                ? ` +${money(option.extra_price)}`
                                : ''
                            }`,
                        )
                        .join(' · ')}
                    </Text>
                  ) : null}
                </View>
                <Text style={styles.itemTotal}>{money(item.total_price)}</Text>
              </View>
            ))}

            <View style={styles.totals}>
              <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>Subtotal</Text>
                <Text style={styles.totalValue}>{money(order.subtotal)}</Text>
              </View>
              <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>
                  {takeaway ? 'Retiro en el local' : 'Costo de envío'}
                </Text>
                <Text style={styles.totalValue}>
                  {takeaway ? 'Sin costo' : money(order.delivery_fee)}
                </Text>
              </View>
              <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>Descuento cupón</Text>
                <Text style={styles.totalValue}>-{money(order.discount)}</Text>
              </View>
              <View style={styles.totalFinal}>
                <Text style={styles.totalFinalLabel}>Total a cobrar</Text>
                <Text style={styles.totalFinalValue}>{money(order.total)}</Text>
              </View>
            </View>
          </View>

          {order.status === 'delivered' ? (
            order.provider_rated && order.provider_rating ? (
              <View style={[styles.block, styles.ratingBlock]}>
                <Text style={styles.blockTitle}>Tu calificación al cliente</Text>
                <Text style={styles.stars}>
                  {[1, 2, 3, 4, 5].map((star) => (
                    <Text
                      key={star}
                      style={{
                        color:
                          star <= Number(order.provider_rating?.stars ?? 0)
                            ? '#f59e0b'
                            : '#d1d5db',
                      }}
                    >
                      ★
                    </Text>
                  ))}
                </Text>
                {order.provider_rating.comment ? (
                  <Text style={styles.ratingComment}>“{order.provider_rating.comment}”</Text>
                ) : null}
              </View>
            ) : (
              <Pressable
                accessibilityRole="button"
                onPress={() => onRate(order)}
                style={styles.rateButton}
              >
                <Text style={styles.rateButtonText}>★ Calificar cliente</Text>
              </Pressable>
            )
          ) : null}

          <View style={styles.footer}>
            <View style={styles.payRow}>
              <Text style={styles.payIcon}>💳</Text>
              <Text style={styles.payText}>{paymentLine(order)}</Text>
              {payment ? (
                <View style={[styles.payBadge, { backgroundColor: payment.background }]}>
                  <Text style={[styles.payBadgeText, { color: payment.color }]}>
                    {payment.label}
                  </Text>
                </View>
              ) : null}
            </View>

            <View style={styles.contactRow}>
              {client?.phone ? (
                <Pressable
                  accessibilityRole="button"
                  onPress={openWhatsApp}
                  style={[styles.contactButton, styles.whatsappButton]}
                >
                  <Text style={styles.whatsappText}>WhatsApp Cliente</Text>
                </Pressable>
              ) : null}

              {driver?.phone ? (
                <Pressable
                  accessibilityRole="button"
                  onPress={() => call(driver.phone)}
                  style={styles.contactButton}
                >
                  <Text style={styles.contactText}>📞 Contactar repartidor</Text>
                </Pressable>
              ) : null}
            </View>
          </View>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    overflow: 'hidden',
  },
  header: {
    backgroundColor: '#f8fafc',
    borderBottomColor: '#e5e7eb',
    borderBottomWidth: 1,
    padding: spacing.md,
  },
  headerTop: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  indexBadge: {
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
    borderRadius: radius.sm,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  indexText: {
    color: ACCENT,
    fontSize: 11,
    fontWeight: '800',
  },
  orderNumber: {
    color: NAVY,
    fontSize: 13,
    fontWeight: '700',
  },
  statusBadge: {
    borderRadius: radius.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '700',
  },
  headerSpacer: {
    flex: 1,
  },
  collapseButton: {
    alignItems: 'center',
    borderColor: '#e5e7eb',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  collapseText: {
    color: colors.muted,
    fontSize: 16,
    fontWeight: '700',
  },
  actionsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  actionButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    justifyContent: 'center',
    minHeight: 34,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
  },
  actionPrimary: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  actionDanger: {
    backgroundColor: '#fff',
    borderColor: '#fecaca',
  },
  actionPrimaryText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
  actionText: {
    color: '#334155',
    fontSize: 12,
    fontWeight: '600',
  },
  actionDangerText: {
    color: '#b91c1c',
    fontSize: 12,
    fontWeight: '600',
  },
  disabled: {
    opacity: 0.5,
  },
  dateRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 4,
    marginTop: spacing.sm,
  },
  dateText: {
    color: '#94a3b8',
    fontSize: 11.5,
  },
  dateDot: {
    color: '#cbd5e1',
    fontSize: 11.5,
  },
  body: {
    padding: spacing.md,
  },
  block: {
    borderColor: '#e5e7eb',
    borderRadius: radius.sm,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  blockHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  blockTitle: {
    color: '#64748b',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.6,
    textTransform: 'uppercase',
  },
  infoRow: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.md,
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: '#eef2f7',
    borderRadius: radius.pill,
    height: 40,
    justifyContent: 'center',
    width: 40,
  },
  avatarText: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '800',
  },
  driverAvatar: {
    backgroundColor: '#e0f2fe',
  },
  driverAvatarText: {
    color: '#0369a1',
  },
  infoTextWrap: {
    flex: 1,
    minWidth: 0,
  },
  infoName: {
    color: '#0f172a',
    fontSize: 14,
    fontWeight: '700',
  },
  infoMeta: {
    color: '#64748b',
    fontSize: 12,
    marginTop: 2,
  },
  infoLink: {
    color: ACCENT,
    fontSize: 12.5,
    fontWeight: '600',
    marginTop: 4,
  },
  deliveryRow: {
    alignItems: 'flex-start',
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingTop: spacing.md,
  },
  deliveryIcon: {
    fontSize: 14,
    marginTop: 1,
  },
  deliveryTitle: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '600',
  },
  noteChip: {
    backgroundColor: '#fffbeb',
    borderRadius: radius.pill,
    color: '#92400e',
    fontSize: 11.5,
    fontWeight: '600',
    marginTop: spacing.sm,
    overflow: 'hidden',
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
  },
  orderNote: {
    color: '#9a3412',
    fontSize: 12,
    marginTop: spacing.sm,
  },
  orderNoteLabel: {
    fontWeight: '700',
  },
  smallBadge: {
    backgroundColor: '#ffedd5',
    borderRadius: radius.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  smallBadgeText: {
    color: '#9a3412',
    fontSize: 11,
    fontWeight: '600',
  },
  driverRow: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.md,
  },
  driverIcon: {
    fontSize: 16,
    marginTop: 1,
  },
  driverTitle: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '600',
  },
  cancelledText: {
    color: '#b91c1c',
    fontSize: 12,
    fontWeight: '600',
    marginTop: spacing.md,
  },
  stepBar: {
    flexDirection: 'row',
    gap: 6,
    marginTop: spacing.md,
  },
  stepItem: {
    flex: 1,
  },
  stepLabel: {
    color: '#94a3b8',
    fontSize: 10.5,
    fontWeight: '700',
    marginBottom: 5,
  },
  stepLabelDone: {
    color: '#047857',
  },
  stepLabelNow: {
    color: ACCENT,
  },
  stepTrack: {
    backgroundColor: '#e5e7eb',
    borderRadius: radius.pill,
    height: 5,
    overflow: 'hidden',
  },
  stepFill: {
    backgroundColor: '#047857',
    borderRadius: radius.pill,
    height: '100%',
  },
  itemsHead: {
    borderBottomColor: '#e5e7eb',
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingBottom: spacing.sm,
  },
  itemsHeadText: {
    color: '#64748b',
    fontSize: 10.5,
    fontWeight: '800',
    letterSpacing: 0.6,
    textTransform: 'uppercase',
  },
  itemRow: {
    alignItems: 'flex-start',
    borderBottomColor: '#f1f5f9',
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    paddingVertical: spacing.md,
  },
  qtyBadge: {
    alignItems: 'center',
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
    borderRadius: radius.sm,
    borderWidth: 1,
    justifyContent: 'center',
    minWidth: 30,
    paddingHorizontal: 6,
    paddingVertical: 3,
  },
  qtyText: {
    color: ACCENT,
    fontSize: 11,
    fontWeight: '800',
  },
  itemInfo: {
    flex: 1,
    minWidth: 0,
  },
  itemName: {
    color: '#0f172a',
    fontSize: 13.5,
    fontWeight: '600',
  },
  itemVariant: {
    color: '#94a3b8',
    fontWeight: '500',
  },
  itemUnit: {
    color: '#94a3b8',
    fontSize: 11.5,
    marginTop: 2,
  },
  itemOptions: {
    color: '#94a3b8',
    fontSize: 11,
    marginTop: 3,
  },
  itemTotal: {
    color: '#0f172a',
    fontSize: 13.5,
    fontWeight: '700',
  },
  totals: {
    paddingTop: spacing.md,
  },
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 3,
  },
  totalLabel: {
    color: '#64748b',
    fontSize: 13,
  },
  totalValue: {
    color: '#64748b',
    fontSize: 13,
  },
  totalFinal: {
    alignItems: 'baseline',
    borderTopColor: '#e2e8f0',
    borderTopWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.sm,
    paddingTop: spacing.sm,
  },
  totalFinalLabel: {
    color: '#0f172a',
    fontSize: 15,
    fontWeight: '800',
  },
  totalFinalValue: {
    color: ACCENT,
    fontSize: 18,
    fontWeight: '800',
  },
  ratingBlock: {
    backgroundColor: '#f8fafc',
    marginTop: spacing.md,
  },
  stars: {
    fontSize: 18,
    marginBottom: 5,
    marginTop: spacing.sm,
  },
  ratingComment: {
    color: '#475569',
    fontSize: 12,
    fontStyle: 'italic',
  },
  rateButton: {
    alignItems: 'center',
    alignSelf: 'flex-start',
    backgroundColor: ACCENT,
    borderRadius: radius.sm,
    marginTop: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  rateButtonText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
  footer: {
    backgroundColor: '#f8fafc',
    borderTopColor: '#e5e7eb',
    borderTopWidth: 1,
    marginHorizontal: -spacing.md,
    marginTop: spacing.md,
    padding: spacing.md,
  },
  payRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  payIcon: {
    fontSize: 14,
  },
  payText: {
    color: '#475569',
    fontSize: 12,
    fontWeight: '600',
  },
  payBadge: {
    borderRadius: radius.pill,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  payBadgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  contactRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  contactButton: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  contactText: {
    color: '#334155',
    fontSize: 12,
    fontWeight: '600',
  },
  whatsappButton: {
    backgroundColor: '#eff6ff',
    borderColor: '#bbf7d0',
  },
  whatsappText: {
    color: '#15803d',
    fontSize: 12,
    fontWeight: '600',
  },
});
