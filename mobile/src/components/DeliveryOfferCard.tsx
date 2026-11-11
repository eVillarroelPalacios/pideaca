import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';

import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type { DriverOffer } from '@/types/api';

const ACCENT = '#D24C19';
const GREEN = '#16a34a';

const STATUS_LABELS: Record<string, string> = {
  pending: 'Pendiente',
  confirmed: 'Confirmado',
  in_preparation: 'En preparación',
  preparing: 'En preparación',
  preparation: 'En preparación',
  on_the_way: 'En camino',
  ready: 'Listo',
  ready_for_delivery: 'Listo para retirar',
  delivered: 'Entregado',
  cancelled: 'Cancelado',
};

const PAYMENT_LABELS: Record<string, string> = {
  cash: 'efectivo',
  card: 'tarjeta',
  credit_card: 'tarjeta',
  debit_card: 'tarjeta',
  transfer: 'transferencia',
  bank_transfer: 'transferencia',
  mercadopago: 'Mercado Pago',
  mercado_pago: 'Mercado Pago',
};

function statusLabel(status: string | null): string {
  if (!status) {
    return 'Pendiente';
  }

  return STATUS_LABELS[status] ?? status;
}

function paymentLabel(method: string | null): string {
  if (!method) {
    return 'efectivo';
  }

  return PAYMENT_LABELS[method.toLowerCase()] ?? method.replace(/_/g, ' ');
}

type Props = {
  offer: DriverOffer;
  busy: boolean;
  vehicleIcon: string;
  onTake: () => void;
};

/**
 * Tarjeta de oferta de viaje disponible. Espeja `cardHtml()` de
 * `repartidor.blade.php`: comercio, tarifa, ruta origen/destino, chips y
 * botón "Tomar pedido".
 */
export function DeliveryOfferCard({ offer, busy, vehicleIcon, onTake }: Props) {
  const shopName = offer.provider.business_name || 'Comercio';
  const shopZone = offer.provider.zone || 'Zona de entrega';
  const destination = offer.address?.full_line || 'Sin dirección cargada';
  const destinationHint = offer.address?.postal_code || 'Entrega a domicilio';

  return (
    <View style={styles.card}>
      <View style={styles.top}>
        <View style={styles.shopBlock}>
          <View style={styles.orderNumberBadge}>
            <Text style={styles.orderNumber}>#{offer.order_number}</Text>
          </View>
          <Text style={styles.shopName}>{shopName}</Text>
          <Text style={styles.shopZone}>{shopZone}</Text>
        </View>

        <View style={styles.feeBlock}>
          <Text style={styles.feeValue}>{money(offer.delivery_fee)}</Text>
          <Text style={styles.feeLabel}>Tarifa de envío</Text>
        </View>
      </View>

      <View style={styles.route}>
        <View style={styles.rail}>
          <View style={[styles.node, styles.nodeOrigin]} />
          <View style={styles.line} />
          <View style={[styles.node, styles.nodeDest]} />
        </View>

        <View style={styles.legs}>
          <View>
            <Text style={styles.legLabel}>Origen · Recogida</Text>
            <Text style={styles.legMain} numberOfLines={2}>
              {shopName}
            </Text>
            <Text style={styles.legSub} numberOfLines={1}>
              {shopZone}
            </Text>
          </View>

          <View>
            <Text style={styles.legLabel}>Destino · Cliente</Text>
            <Text style={styles.legMain} numberOfLines={3}>
              {destination}
            </Text>
            <Text style={styles.legSub} numberOfLines={1}>
              {destinationHint}
            </Text>
          </View>
        </View>
      </View>

      <View style={styles.chips}>
        <View style={[styles.chip, styles.chipHot]}>
          <Text style={[styles.chipText, styles.chipHotText]}>{statusLabel(offer.status)}</Text>
        </View>
        <View style={styles.chip}>
          <Text style={styles.chipText}>Pago: {paymentLabel(offer.payment_method)}</Text>
        </View>
        {offer.total > 0 ? (
          <View style={styles.chip}>
            <Text style={styles.chipText}>Total {money(offer.total)}</Text>
          </View>
        ) : null}
      </View>

      <Pressable
        accessibilityRole="button"
        accessibilityLabel={`Tomar el pedido ${offer.order_number}`}
        disabled={busy}
        onPress={onTake}
        style={({ pressed }) => [
          styles.takeButton,
          pressed && styles.takeButtonPressed,
          busy && styles.takeButtonBusy,
        ]}
      >
        {busy ? (
          <ActivityIndicator color={colors.surface} />
        ) : (
          <>
            <Text style={styles.takeIcon}>{vehicleIcon}</Text>
            <Text style={styles.takeText}>Tomar pedido</Text>
          </>
        )}
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: 14,
    borderWidth: 1,
    padding: spacing.lg,
  },
  top: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'space-between',
  },
  shopBlock: {
    flex: 1,
    minWidth: 0,
  },
  orderNumberBadge: {
    alignSelf: 'flex-start',
    backgroundColor: '#FFF7ED',
    borderColor: '#FED7AA',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
  },
  orderNumber: {
    color: ACCENT,
    fontSize: 11.5,
    fontWeight: '800',
  },
  shopName: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
    letterSpacing: -0.2,
    marginTop: spacing.sm,
  },
  shopZone: {
    color: colors.muted,
    fontSize: 12.5,
    marginTop: 3,
  },
  feeBlock: {
    alignItems: 'flex-end',
    flexShrink: 0,
  },
  feeValue: {
    color: ACCENT,
    fontSize: 26,
    fontWeight: '800',
    letterSpacing: -1,
    lineHeight: 29,
  },
  feeLabel: {
    color: '#94a3b8',
    fontSize: 9,
    fontWeight: '700',
    letterSpacing: 0.6,
    marginTop: 5,
    textTransform: 'uppercase',
  },
  route: {
    backgroundColor: '#f8fafc',
    borderColor: '#eef2f7',
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.lg,
    padding: spacing.lg,
  },
  rail: {
    alignItems: 'center',
    paddingTop: 5,
  },
  node: {
    borderRadius: radius.pill,
    height: 13,
    width: 13,
  },
  nodeOrigin: {
    backgroundColor: GREEN,
  },
  nodeDest: {
    backgroundColor: ACCENT,
  },
  line: {
    borderColor: '#cbd5e1',
    borderLeftWidth: 2,
    borderStyle: 'dashed',
    flex: 1,
    marginVertical: 5,
    minHeight: 38,
    width: 0,
  },
  legs: {
    flex: 1,
    gap: spacing.lg,
    minWidth: 0,
  },
  legLabel: {
    color: '#94a3b8',
    fontSize: 9,
    fontWeight: '800',
    letterSpacing: 0.9,
    textTransform: 'uppercase',
  },
  legMain: {
    color: colors.text,
    fontSize: 13.5,
    fontWeight: '700',
    marginTop: 3,
  },
  legSub: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.lg,
    marginTop: spacing.md,
  },
  chip: {
    backgroundColor: '#f1f5f9',
    borderColor: '#e2e8f0',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 5,
  },
  chipHot: {
    backgroundColor: '#FFF7ED',
    borderColor: '#FED7AA',
  },
  chipText: {
    color: '#475569',
    fontSize: 11,
    fontWeight: '700',
  },
  chipHotText: {
    color: ACCENT,
  },
  takeButton: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: radius.sm,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'center',
    minHeight: 52,
    paddingHorizontal: spacing.lg,
  },
  takeButtonPressed: {
    backgroundColor: '#bd4115',
  },
  takeButtonBusy: {
    opacity: 0.8,
  },
  takeIcon: {
    fontSize: 16,
  },
  takeText: {
    color: colors.surface,
    fontSize: 15,
    fontWeight: '800',
    letterSpacing: 0.3,
  },
});
