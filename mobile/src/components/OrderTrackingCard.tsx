import { Fragment, useState } from 'react';
import {
  ActivityIndicator,
  Linking,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import { money } from '@/lib/money';
import {
  deliveryAddress,
  etaLabel,
  initials,
  isTakeaway,
  itemsResume,
  liveTitle,
  orderDateTime,
  paymentInfo,
  paymentMethodLabel,
  ratingLabel,
  statusInfo,
  stepSubtitle,
  trackSteps,
  whatsappUrl,
} from '@/lib/orderTracking';
import { colors, radius, spacing } from '@/theme';
import type { MarketMyOrder } from '@/types/api';

function StatusBadge({ status }: { status: string }) {
  const info = statusInfo(status);

  return (
    <View style={[styles.badge, { backgroundColor: info.background }]}>
      <Text style={[styles.badgeText, { color: info.color }]}>{info.label}</Text>
    </View>
  );
}

function ItemsBlock({ order }: { order: MarketMyOrder }) {
  return (
    <>
      <View style={styles.itemsDivider}>
        {order.items.map((item, index) => (
          <View key={`${item.id}-${index}`} style={styles.itemRow}>
            <View style={styles.qtyBadge}>
              <Text style={styles.qtyText}>{item.quantity}×</Text>
            </View>
            <View style={styles.itemText}>
              <Text style={styles.itemName}>{item.product_name}</Text>
              {item.variant_name ? (
                <Text style={styles.itemVariant}>{item.variant_name}</Text>
              ) : null}
              {item.options.map((option, optionIndex) => (
                <Text key={`${option.option_name}-${optionIndex}`} style={styles.itemOption}>
                  {option.group_name}: {option.option_name}
                  {option.extra_price > 0 ? ` (+${money(option.extra_price)})` : ''}
                </Text>
              ))}
            </View>
            <Text style={styles.itemTotal}>{money(item.total_price)}</Text>
          </View>
        ))}
      </View>

      <View style={styles.totals}>
        <View style={styles.totalRow}>
          <Text style={styles.totalLabel}>Subtotal</Text>
          <Text style={styles.totalValue}>{money(order.subtotal)}</Text>
        </View>
        <View style={styles.totalRow}>
          <Text style={styles.totalLabel}>
            {isTakeaway(order) ? 'Retiro en el local' : 'Envío'}
          </Text>
          <Text style={styles.totalValue}>
            {isTakeaway(order) ? 'Sin costo' : money(order.delivery_fee)}
          </Text>
        </View>
        <View style={styles.totalRow}>
          <Text style={styles.totalLabel}>Descuento</Text>
          <Text style={styles.totalValue}>-{money(order.discount)}</Text>
        </View>
        <View style={styles.grandTotal}>
          <Text style={styles.grandTotalLabel}>Total</Text>
          <Text style={styles.grandTotalValue}>{money(order.total)}</Text>
        </View>
      </View>
    </>
  );
}

function AddressBlock({ order }: { order: MarketMyOrder }) {
  const takeaway = isTakeaway(order);
  const shopName = order.provider?.business_name ?? 'Comercio';

  return (
    <View style={styles.block}>
      <View style={styles.blockIcon}>
        <Text style={styles.blockIconText}>{takeaway ? '🛍️' : '📍'}</Text>
      </View>
      <View style={styles.blockText}>
        <Text style={styles.eyebrow}>{takeaway ? 'RETIRO EN EL LOCAL' : 'DIRECCIÓN DE ENTREGA'}</Text>
        {takeaway ? (
          <>
            <Text style={styles.blockTitle}>{shopName}</Text>
            <Text style={styles.blockSub}>
              {order.pickup_address || 'Coordiná la dirección del local con el comercio.'}
            </Text>
          </>
        ) : (
          <>
            <Text style={styles.blockTitle}>{deliveryAddress(order)}</Text>
            <Text style={styles.blockSub}>
              {order.address?.postal_code
                ? `CP ${order.address.postal_code}`
                : 'Sin código postal'}
            </Text>
            {order.address?.notes ? (
              <View style={styles.noteChip}>
                <Text style={styles.noteChipText}>🔔 {order.address.notes}</Text>
              </View>
            ) : null}
          </>
        )}
      </View>
    </View>
  );
}

function PaymentBlock({ order }: { order: MarketMyOrder }) {
  const payment = paymentInfo(order.payments);

  return (
    <View style={styles.block}>
      <View style={[styles.blockIcon, styles.blockIconGreen]}>
        <Text style={styles.blockIconText}>💳</Text>
      </View>
      <View style={styles.blockText}>
        <Text style={styles.eyebrow}>FORMA DE PAGO</Text>
        <View style={styles.paymentRow}>
          <Text style={styles.blockTitle}>{paymentMethodLabel(order.payment_method)}</Text>
          {payment ? (
            <View style={[styles.badge, { backgroundColor: payment.background }]}>
              <Text style={[styles.badgeText, { color: payment.color }]}>{payment.label}</Text>
            </View>
          ) : null}
        </View>
        {order.payment_method === 'efectivo' ? (
          <Text style={styles.blockSub}>Prepará el importe exacto si podés.</Text>
        ) : null}
      </View>
    </View>
  );
}

/**
 * Tarjeta del pedido en curso: cabecera del local, estado en vivo con el
 * circuito (repartidor incluido), dirección, pago, productos y totales.
 */
export function OrderTrackingCard({ order }: { order: MarketMyOrder }) {
  const takeaway = isTakeaway(order);
  const eta = etaLabel(order);
  const steps = trackSteps(order);
  const current = steps.findIndex((step) => step.key === order.status);
  const shopPhone = order.provider?.user?.phone ?? null;
  const driver = order.driver;

  function contactShop() {
    if (!shopPhone) {
      return;
    }

    const message = `Hola ${order.provider?.business_name ?? ''}, te contacto por mi pedido ${order.order_number}.`;

    void Linking.openURL(whatsappUrl(shopPhone, message));
  }

  return (
    <View style={styles.card}>
      <View style={styles.cardHead}>
        <View style={styles.shopIcon}>
          <Text style={styles.shopIconText}>🏪</Text>
        </View>

        <View style={styles.headText}>
          <View style={styles.titleRow}>
            <Text style={styles.shopName} numberOfLines={1}>
              {order.provider?.business_name ?? 'Comercio'}
            </Text>
            <StatusBadge status={order.status} />
          </View>
          <View style={styles.metaRow}>
            <Text style={styles.orderNumber}>{order.order_number}</Text>
            <Text style={styles.metaDot}>·</Text>
            <Text style={styles.metaMuted}>{orderDateTime(order.created_at)}</Text>
          </View>
        </View>

        {shopPhone ? (
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Contactar al comercio"
            onPress={contactShop}
            style={({ pressed }) => [styles.helpButton, pressed && styles.pressed]}
          >
            <Text style={styles.helpText}>☎ Ayuda / Contacto</Text>
          </Pressable>
        ) : null}
      </View>

      <View style={styles.live}>
        <Text style={[styles.eyebrow, styles.eyebrowAccent]}>ESTADO EN TIEMPO REAL</Text>

        <View style={styles.liveRow}>
          <Text style={styles.liveTitle}>{liveTitle(order)}</Text>

          {eta ? (
            <View style={styles.etaWrap}>
              <Text style={styles.eyebrow}>
                {takeaway ? 'TIEMPO ESTIMADO DE PREPARACIÓN' : 'TIEMPO ESTIMADO DE ENTREGA'}
              </Text>
              <View style={styles.etaPill}>
                <Text style={styles.etaPillText}>🕒 {eta}</Text>
              </View>
            </View>
          ) : null}
        </View>

        {order.status === 'cancelled' ? (
          <View style={styles.cancelled}>
            <Text style={styles.cancelledText}>Pedido cancelado</Text>
          </View>
        ) : current >= 0 ? (
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.steps}
          >
            {steps.map((step, index) => {
              const done = index < current;
              const now = index === current;
              const icon =
                step.key === 'on_the_way' && driver && !takeaway
                  ? initials(driver.name)
                  : step.icon;

              return (
                <Fragment key={step.key}>
                  <View style={styles.step}>
                    <View style={styles.stepNowSlot}>
                      <Text style={[styles.stepNow, now && styles.stepNowActive]}>
                        {now ? 'AHORA' : ''}
                      </Text>
                    </View>
                    <View
                      style={[
                        styles.stepIcon,
                        done && styles.stepIconDone,
                        now && styles.stepIconNow,
                      ]}
                    >
                      <Text
                        style={[
                          styles.stepIconText,
                          (done || now) && styles.stepIconTextActive,
                        ]}
                      >
                        {icon}
                      </Text>
                    </View>
                    <Text
                      style={[
                        styles.stepLabel,
                        done && styles.stepLabelDone,
                        now && styles.stepLabelNow,
                      ]}
                    >
                      {step.label}
                    </Text>
                    <Text style={styles.stepSub}>{stepSubtitle(order, step.key)}</Text>
                  </View>
                  {index < steps.length - 1 ? (
                    <View style={[styles.stepLine, done && styles.stepLineDone]} />
                  ) : null}
                </Fragment>
              );
            })}
          </ScrollView>
        ) : null}

        {driver ? (
          <View style={styles.driverCard}>
            <View style={styles.driverAvatar}>
              <Text style={styles.driverAvatarText}>{initials(driver.name)}</Text>
            </View>
            <View style={styles.driverText}>
              <Text style={styles.driverName}>{driver.name || 'Repartidor'}</Text>
              <Text style={styles.driverSub}>Repartidor asignado a tu pedido</Text>
            </View>
            {driver.phone ? (
              <View style={styles.driverActions}>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Llamar al repartidor"
                  onPress={() =>
                    void Linking.openURL(
                      `tel:${String(driver.phone ?? '').replace(/[^\d+]/g, '')}`,
                    )
                  }
                  style={({ pressed }) => [styles.smallButton, pressed && styles.pressed]}
                >
                  <Text style={styles.smallButtonText}>☎ Llamar</Text>
                </Pressable>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Escribir al repartidor por WhatsApp"
                  onPress={() =>
                    void Linking.openURL(
                      whatsappUrl(
                        driver.phone,
                        `Hola ${driver.name}, ¿dónde está mi pedido ${order.order_number}?`,
                      ),
                    )
                  }
                  style={({ pressed }) => [
                    styles.smallButton,
                    styles.smallButtonBrand,
                    pressed && styles.pressed,
                  ]}
                >
                  <Text style={[styles.smallButtonText, styles.smallButtonTextBrand]}>
                    WhatsApp
                  </Text>
                </Pressable>
              </View>
            ) : null}
          </View>
        ) : null}
      </View>

      <View style={styles.body}>
        <AddressBlock order={order} />
        <PaymentBlock order={order} />

        {order.notes ? (
          <Text style={styles.noteText}>
            <Text style={styles.noteLabel}>Nota del pedido: </Text>
            {order.notes}
          </Text>
        ) : null}

        <ItemsBlock order={order} />
      </View>
    </View>
  );
}

/**
 * Tarjeta compacta del historial: local, estado, importe, resumen de
 * productos, calificación y botón para repetir el pedido.
 */
export function OrderHistoryCard({
  order,
  onRate,
  onRepeat,
}: {
  order: MarketMyOrder;
  onRate: (stars: number, comment: string) => Promise<string | null>;
  onRepeat: () => void;
}) {
  const [open, setOpen] = useState(false);
  const [stars, setStars] = useState(0);
  const [comment, setComment] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const delivered = order.status === 'delivered';
  const rated = delivered && order.customer_rated && order.customer_rating;

  async function send() {
    if (busy) {
      return;
    }

    if (stars < 1) {
      setError('Elegí una puntuación de 1 a 5 estrellas.');
      return;
    }

    setBusy(true);
    setError(null);

    const message = await onRate(stars, comment.trim());

    if (message) {
      setError(message);
    } else {
      setOpen(false);
      setStars(0);
      setComment('');
    }

    setBusy(false);
  }

  return (
    <View style={styles.card}>
      <View style={styles.historyHead}>
        <View style={styles.historyShopIcon}>
          <Text style={styles.historyShopIconText}>🏪</Text>
        </View>

        <View style={styles.headText}>
          <Text style={styles.historyShopName} numberOfLines={1}>
            {order.provider?.business_name ?? 'Comercio'}
          </Text>
          <View style={styles.metaRow}>
            <Text style={styles.metaMuted}>{orderDateTime(order.created_at)}</Text>
            <Text style={styles.metaDot}>·</Text>
            <Text style={styles.orderNumber}>{order.order_number}</Text>
          </View>
        </View>

        <View style={styles.historyRight}>
          <StatusBadge status={order.status} />
          {delivered ? <Text style={styles.historyTotal}>{money(order.total)}</Text> : null}
        </View>
      </View>

      <View style={styles.historyResume}>
        <Text style={styles.historyResumeText} numberOfLines={2}>
          {itemsResume(order)}
        </Text>
        {delivered ? (
          rated && order.customer_rating ? (
            <Text style={styles.ratedText}>
              ★ Calificaste: {Number(order.customer_rating.stars).toFixed(1)} (
              {ratingLabel(order.customer_rating.stars)})
            </Text>
          ) : (
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Calificar pedido"
              onPress={() => setOpen((value) => !value)}
            >
              <Text style={styles.rateLink}>Calificar pedido</Text>
            </Pressable>
          )
        ) : null}
      </View>

      <View style={styles.historyFoot}>
        <Text style={styles.historyPayment}>{paymentMethodLabel(order.payment_method)}</Text>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Repetir este pedido"
          onPress={onRepeat}
          style={({ pressed }) => [styles.repeatButton, pressed && styles.pressed]}
        >
          <Text style={styles.repeatText}>↻ Repetir este pedido</Text>
        </Pressable>
      </View>

      {open && delivered ? (
        <View style={styles.ratePanel}>
          <Text style={styles.rateLabel}>Puntuación al comercio</Text>
          <View style={styles.starsRow}>
            {[1, 2, 3, 4, 5].map((value) => (
              <Pressable
                key={value}
                accessibilityRole="button"
                accessibilityLabel={`${value} estrellas`}
                onPress={() => {
                  setStars(value);
                  setError(null);
                }}
                hitSlop={6}
              >
                <Text style={[styles.star, value <= stars && styles.starActive]}>★</Text>
              </Pressable>
            ))}
          </View>

          <Text style={[styles.rateLabel, styles.rateLabelSpaced]}>Comentario (opcional)</Text>
          <TextInput
            value={comment}
            onChangeText={setComment}
            placeholder="Excelente atención y entrega puntual…"
            placeholderTextColor={colors.muted}
            multiline
            maxLength={1000}
            style={styles.rateInput}
          />

          {error ? <Text style={styles.rateError}>{error}</Text> : null}

          <View style={styles.rateActions}>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Enviar calificación"
              disabled={busy}
              onPress={() => void send()}
              style={({ pressed }) => [
                styles.sendButton,
                (pressed || busy) && styles.pressed,
              ]}
            >
              {busy ? (
                <ActivityIndicator color={colors.surface} size="small" />
              ) : (
                <Text style={styles.sendText}>Enviar calificación</Text>
              )}
            </Pressable>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Cerrar calificación"
              disabled={busy}
              onPress={() => setOpen(false)}
              style={({ pressed }) => [styles.closeButton, pressed && styles.pressed]}
            >
              <Text style={styles.closeText}>Cerrar</Text>
            </Pressable>
          </View>
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.md,
    overflow: 'hidden',
  },
  pressed: {
    opacity: 0.75,
  },
  cardHead: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
    padding: spacing.lg,
  },
  shopIcon: {
    alignItems: 'center',
    backgroundColor: colors.brandSoft,
    borderRadius: radius.md,
    height: 44,
    justifyContent: 'center',
    width: 44,
  },
  shopIconText: {
    fontSize: 21,
  },
  headText: {
    flex: 1,
    minWidth: 0,
  },
  titleRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  shopName: {
    color: colors.text,
    flexShrink: 1,
    fontSize: 15,
    fontWeight: '800',
  },
  metaRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginTop: 3,
  },
  orderNumber: {
    color: colors.muted,
    fontFamily: 'monospace',
    fontSize: 11.5,
    fontWeight: '600',
  },
  metaDot: {
    color: colors.border,
  },
  metaMuted: {
    color: colors.muted,
    fontSize: 11.5,
  },
  badge: {
    borderRadius: radius.pill,
    paddingHorizontal: 9,
    paddingVertical: 3,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  helpButton: {
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  helpText: {
    color: '#334155',
    fontSize: 12,
    fontWeight: '600',
  },
  live: {
    backgroundColor: '#fff7ed',
    borderBottomColor: '#fed7aa',
    borderTopColor: '#fed7aa',
    borderBottomWidth: 1,
    borderTopWidth: 1,
    padding: spacing.lg,
  },
  eyebrow: {
    color: '#94a3b8',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.7,
  },
  eyebrowAccent: {
    color: '#D24C19',
  },
  liveRow: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    marginTop: 4,
  },
  liveTitle: {
    color: colors.text,
    flex: 1,
    fontSize: 17,
    fontWeight: '800',
    lineHeight: 23,
    minWidth: 200,
  },
  etaWrap: {
    alignItems: 'flex-end',
  },
  etaPill: {
    backgroundColor: colors.surface,
    borderColor: '#fdba74',
    borderRadius: radius.pill,
    borderWidth: 1,
    marginTop: 5,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  etaPillText: {
    color: '#D24C19',
    fontSize: 13,
    fontWeight: '800',
  },
  cancelled: {
    backgroundColor: '#fef2f2',
    borderRadius: radius.sm,
    marginTop: spacing.md,
    padding: spacing.md,
  },
  cancelledText: {
    color: '#b91c1c',
    fontSize: 13,
    fontWeight: '700',
  },
  steps: {
    alignItems: 'flex-start',
    paddingBottom: 2,
    paddingTop: spacing.lg,
  },
  step: {
    alignItems: 'center',
    width: 78,
  },
  stepNowSlot: {
    height: 13,
  },
  stepNow: {
    color: 'transparent',
    fontSize: 9,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  stepNowActive: {
    color: '#D24C19',
  },
  stepIcon: {
    alignItems: 'center',
    backgroundColor: '#e2e8f0',
    borderColor: '#e2e8f0',
    borderRadius: 17,
    borderWidth: 1,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  stepIconDone: {
    backgroundColor: '#D24C19',
    borderColor: '#D24C19',
  },
  stepIconNow: {
    backgroundColor: '#D24C19',
    borderColor: '#D24C19',
  },
  stepIconText: {
    color: '#64748b',
    fontSize: 12,
    fontWeight: '800',
  },
  stepIconTextActive: {
    color: colors.surface,
  },
  stepLabel: {
    color: '#94a3b8',
    fontSize: 11,
    fontWeight: '600',
    lineHeight: 14,
    marginTop: 7,
    textAlign: 'center',
  },
  stepLabelDone: {
    color: '#334155',
  },
  stepLabelNow: {
    color: '#D24C19',
    fontWeight: '800',
  },
  stepSub: {
    color: '#94a3b8',
    fontSize: 10,
    marginTop: 2,
    textAlign: 'center',
  },
  stepLine: {
    backgroundColor: '#e2e8f0',
    borderRadius: 2,
    flex: 1,
    height: 3,
    marginTop: 30,
    minWidth: 16,
  },
  stepLineDone: {
    backgroundColor: '#D24C19',
  },
  driverCard: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    marginTop: spacing.md,
    padding: spacing.md,
  },
  driverAvatar: {
    alignItems: 'center',
    backgroundColor: '#0c2a4d',
    borderRadius: 17,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  driverAvatarText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '800',
  },
  driverText: {
    flex: 1,
    minWidth: 120,
  },
  driverName: {
    color: colors.text,
    fontSize: 13.5,
    fontWeight: '700',
  },
  driverSub: {
    color: colors.muted,
    fontSize: 11.5,
  },
  driverActions: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  smallButton: {
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  smallButtonBrand: {
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
  },
  smallButtonText: {
    color: '#334155',
    fontSize: 12,
    fontWeight: '600',
  },
  smallButtonTextBrand: {
    color: '#D24C19',
  },
  body: {
    padding: spacing.lg,
  },
  block: {
    alignItems: 'flex-start',
    borderColor: colors.border,
    borderRadius: 6,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.md,
    padding: spacing.md,
  },
  blockIcon: {
    alignItems: 'center',
    backgroundColor: colors.brandSoft,
    borderRadius: radius.sm,
    height: 34,
    justifyContent: 'center',
    width: 34,
  },
  blockIconGreen: {
    backgroundColor: '#ecfdf5',
  },
  blockIconText: {
    fontSize: 16,
  },
  blockText: {
    flex: 1,
    minWidth: 0,
  },
  blockTitle: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
    marginTop: 3,
  },
  blockSub: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  paymentRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  noteChip: {
    alignSelf: 'flex-start',
    backgroundColor: '#fffbeb',
    borderColor: '#fde68a',
    borderRadius: radius.pill,
    borderWidth: 1,
    marginTop: 7,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  noteChipText: {
    color: '#92400e',
    fontSize: 11.5,
    fontWeight: '600',
  },
  noteText: {
    color: '#9a3412',
    fontSize: 12.5,
    lineHeight: 18,
    marginBottom: spacing.sm,
  },
  noteLabel: {
    fontWeight: '700',
  },
  itemsDivider: {
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    marginTop: 2,
    paddingTop: 4,
  },
  itemRow: {
    alignItems: 'flex-start',
    borderBottomColor: '#f1f5f9',
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    paddingVertical: 11,
  },
  qtyBadge: {
    alignItems: 'center',
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
    borderRadius: 4,
    borderWidth: 1,
    minWidth: 30,
    paddingHorizontal: 6,
    paddingVertical: 2,
  },
  qtyText: {
    color: '#D24C19',
    fontSize: 11,
    fontWeight: '800',
  },
  itemText: {
    flex: 1,
    minWidth: 0,
  },
  itemName: {
    color: colors.text,
    fontSize: 13.5,
    fontWeight: '600',
  },
  itemVariant: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 1,
  },
  itemOption: {
    color: colors.muted,
    fontSize: 11.5,
    marginTop: 1,
  },
  itemTotal: {
    color: colors.text,
    fontSize: 13.5,
    fontWeight: '700',
  },
  totals: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.sm,
  },
  totalRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 3,
  },
  totalLabel: {
    color: colors.muted,
    fontSize: 13,
  },
  totalValue: {
    color: colors.text,
    fontSize: 13,
  },
  grandTotal: {
    alignItems: 'baseline',
    borderTopColor: colors.border,
    borderTopWidth: 1,
    borderStyle: 'dashed',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: spacing.sm,
    paddingTop: spacing.md,
  },
  grandTotalLabel: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
  },
  grandTotalValue: {
    color: '#D24C19',
    fontSize: 18,
    fontWeight: '800',
  },
  historyHead: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
    padding: spacing.lg,
  },
  historyShopIcon: {
    alignItems: 'center',
    backgroundColor: colors.brandSoft,
    borderRadius: 9,
    height: 38,
    justifyContent: 'center',
    width: 38,
  },
  historyShopIconText: {
    fontSize: 18,
  },
  historyShopName: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '800',
  },
  historyRight: {
    alignItems: 'flex-end',
    gap: 4,
  },
  historyTotal: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '800',
  },
  historyResume: {
    alignItems: 'center',
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: 10,
  },
  historyResumeText: {
    color: '#475569',
    flex: 1,
    fontSize: 12.5,
    minWidth: 140,
  },
  ratedText: {
    color: '#92400e',
    fontSize: 12.5,
    fontWeight: '700',
  },
  rateLink: {
    color: '#D24C19',
    fontSize: 12.5,
    fontWeight: '700',
  },
  historyFoot: {
    alignItems: 'center',
    backgroundColor: '#f8fafc',
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: 10,
  },
  historyPayment: {
    color: '#94a3b8',
    fontSize: 11.5,
  },
  repeatButton: {
    backgroundColor: '#fff7ed',
    borderColor: '#fdba74',
    borderRadius: 4,
    borderWidth: 1,
    paddingHorizontal: 12,
    paddingVertical: 7,
  },
  repeatText: {
    color: '#D24C19',
    fontSize: 12,
    fontWeight: '600',
  },
  ratePanel: {
    borderTopColor: colors.border,
    borderTopWidth: 1,
    padding: spacing.lg,
  },
  rateLabel: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 4,
  },
  rateLabelSpaced: {
    marginTop: 10,
  },
  starsRow: {
    flexDirection: 'row',
    gap: 4,
  },
  star: {
    color: '#d1d5db',
    fontSize: 22,
    lineHeight: 26,
  },
  starActive: {
    color: '#D24C19',
  },
  rateInput: {
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    color: colors.text,
    fontSize: 13,
    minHeight: 56,
    paddingHorizontal: 10,
    paddingVertical: 8,
    textAlignVertical: 'top',
  },
  rateError: {
    color: colors.danger,
    fontSize: 12,
    marginTop: spacing.sm,
  },
  rateActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  sendButton: {
    alignItems: 'center',
    backgroundColor: '#D24C19',
    borderRadius: 4,
    justifyContent: 'center',
    minWidth: 140,
    paddingHorizontal: 16,
    paddingVertical: 9,
  },
  sendText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '600',
  },
  closeButton: {
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    paddingHorizontal: 16,
    paddingVertical: 9,
  },
  closeText: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '600',
  },
});
