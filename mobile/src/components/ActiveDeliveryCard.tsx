import { useState } from 'react';
import {
  ActivityIndicator,
  Linking,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type { DriverActiveOrder, DriverOrderAddress } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';
const GREEN = '#16a34a';

const STEP_LABELS = ['1. En retiro', '2. En entrega', '3. Entregado'];

function stepIndex(status: string | null): number {
  if (status === 'delivered') {
    return 3;
  }

  if (status === 'picked_up' || status === 'on_the_way') {
    return 2;
  }

  return 1;
}

function destinationQuery(address: DriverOrderAddress | null): string | null {
  if (!address) {
    return null;
  }

  if (address.latitude !== null && address.longitude !== null) {
    return `${address.latitude},${address.longitude}`;
  }

  return address.full_line || null;
}

function googleMapsUrl(address: DriverOrderAddress | null): string | null {
  const query = destinationQuery(address);

  return query
    ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(query)}`
    : null;
}

function wazeUrl(address: DriverOrderAddress | null): string | null {
  if (!address) {
    return null;
  }

  if (address.latitude !== null && address.longitude !== null) {
    return `https://waze.com/ul?ll=${encodeURIComponent(
      `${address.latitude},${address.longitude}`,
    )}&navigate=yes`;
  }

  const query = destinationQuery(address);

  return query ? `https://waze.com/ul?q=${encodeURIComponent(query)}` : null;
}

function callUrl(phone: string | null): string | null {
  const digits = String(phone ?? '').replace(/[^\d+]/g, '');

  return digits ? `tel:${digits}` : null;
}

function openUrl(url: string | null): void {
  if (url) {
    void Linking.openURL(url).catch(() => undefined);
  }
}

type Props = {
  order: DriverActiveOrder;
  busy: boolean;
  onPickup: () => void;
  onConfirmDelivery: (pin: string) => void;
};

/**
 * Tarjeta de pedido asignado con stepper de 3 fases, direcciones de retiro y
 * entrega, deep links a Google Maps/Waze, llamada al cliente y confirmación
 * con PIN. Espeja `myCardHtml()` de `repartidor.blade.php`.
 */
export function ActiveDeliveryCard({ order, busy, onPickup, onConfirmDelivery }: Props) {
  const [pinOpen, setPinOpen] = useState(false);
  const [pin, setPin] = useState('');

  const currentStep = stepIndex(order.delivery_status);
  const delivered = currentStep === 3;
  const shopName = order.provider.business_name || 'Comercio';
  const pickupLine =
    order.provider.pickup_address?.full_line ||
    order.provider.zone ||
    'Punto de retiro en el comercio';
  const customerName = order.customer.name || 'Cliente';
  const customerAddress = order.address?.full_line || 'Sin dirección cargada';
  const phone = callUrl(order.customer.phone);
  const maps = googleMapsUrl(order.address);
  const waze = wazeUrl(order.address);

  function handleComplete(): void {
    if (order.has_delivery_pin) {
      setPinOpen(true);
      return;
    }

    onConfirmDelivery('0');
  }

  function confirmWithPin(): void {
    const value = pin.trim();

    if (!value) {
      return;
    }

    onConfirmDelivery(value);
  }

  return (
    <View style={styles.card}>
      <View style={styles.top}>
        <View style={styles.tripLeft}>
          <View style={styles.progressBadge}>
            <View style={styles.pulseDot} />
            <Text style={styles.progressText}>
              {delivered ? 'Viaje Completado' : 'Viaje en Progreso'}
            </Text>
          </View>
          <View style={styles.orderNumberBadge}>
            <Text style={styles.orderNumber}>#{order.order_number}</Text>
          </View>
        </View>

        <View style={styles.gainBlock}>
          <Text style={styles.gainLabel}>Ganancia al finalizar:</Text>
          <Text style={styles.gainValue}>{money(order.delivery_fee)}</Text>
        </View>
      </View>

      <View style={styles.steps}>
        {STEP_LABELS.map((label, index) => {
          const active = index + 1 <= currentStep;

          return (
            <View key={label} style={[styles.step, active && styles.stepActive]}>
              <Text style={[styles.stepText, active && styles.stepTextActive]}>{label}</Text>
            </View>
          );
        })}
      </View>

      <View style={styles.info}>
        <View>
          <Text style={styles.infoLabel}>Retirar en</Text>
          <Text style={styles.infoName}>{shopName}</Text>
          <Text style={styles.infoLine}>{pickupLine}</Text>
        </View>

        <View style={styles.infoDivider}>
          <Text style={styles.infoLabel}>Entregar a</Text>
          <Text style={styles.infoName}>{customerName}</Text>
          <Text style={styles.infoLine}>{customerAddress}</Text>
          {phone ? (
            <Pressable
              accessibilityRole="link"
              accessibilityLabel={`Llamar a ${customerName}`}
              onPress={() => openUrl(phone)}
              style={({ pressed }) => [styles.phoneRow, pressed && styles.pressed]}
            >
              <Text style={styles.phoneText}>📞 Tel: {order.customer.phone}</Text>
            </Pressable>
          ) : null}
        </View>
      </View>

      <View style={styles.mapRow}>
        {maps ? (
          <Pressable
            accessibilityRole="link"
            accessibilityLabel="Abrir la entrega en Google Maps"
            onPress={() => openUrl(maps)}
            style={({ pressed }) => [styles.mapButton, styles.mapsButton, pressed && styles.pressed]}
          >
            <Text style={styles.mapsButtonText}>🗺️ Google Maps</Text>
          </Pressable>
        ) : null}

        {waze ? (
          <Pressable
            accessibilityRole="link"
            accessibilityLabel="Abrir la entrega en Waze"
            onPress={() => openUrl(waze)}
            style={({ pressed }) => [styles.mapButton, styles.wazeButton, pressed && styles.pressed]}
          >
            <Text style={styles.wazeButtonText}>🧭 Waze</Text>
          </Pressable>
        ) : null}
      </View>

      {delivered ? (
        <View style={styles.deliveredChip}>
          <Text style={styles.deliveredText}>✓ Entregado</Text>
        </View>
      ) : currentStep === 1 ? (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Marcar el pedido como retirado"
          disabled={busy}
          onPress={onPickup}
          style={({ pressed }) => [
            styles.actionButton,
            styles.pickupButton,
            pressed && styles.pressed,
            busy && styles.busy,
          ]}
        >
          {busy ? (
            <ActivityIndicator color={colors.surface} />
          ) : (
            <Text style={styles.actionText}>📍 Ya retiré el pedido</Text>
          )}
        </Pressable>
      ) : (
        <Pressable
          accessibilityRole="button"
          accessibilityLabel="Completar la entrega"
          disabled={busy}
          onPress={handleComplete}
          style={({ pressed }) => [
            styles.actionButton,
            styles.doneButton,
            pressed && styles.pressed,
            busy && styles.busy,
          ]}
        >
          {busy ? (
            <ActivityIndicator color={colors.surface} />
          ) : (
            <Text style={styles.actionText}>
              {order.has_delivery_pin ? '✓ Completar Entrega' : '✓ Confirmar Entrega'}
            </Text>
          )}
        </Pressable>
      )}

      {pinOpen && !delivered ? (
        <View style={styles.pinRow}>
          <Text style={styles.pinHint}>
            Pedile al cliente el PIN de entrega y ingresalo acá para confirmar.
          </Text>
          <TextInput
            autoCapitalize="none"
            autoComplete="off"
            editable={!busy}
            maxLength={12}
            onChangeText={setPin}
            placeholder="PIN de entrega"
            placeholderTextColor="#94a3b8"
            secureTextEntry
            style={styles.pinInput}
            value={pin}
          />
          <Pressable
            accessibilityRole="button"
            accessibilityLabel="Confirmar entrega con PIN"
            disabled={busy || pin.trim() === ''}
            onPress={confirmWithPin}
            style={({ pressed }) => [
              styles.actionButton,
              styles.doneButton,
              pressed && styles.pressed,
              (busy || pin.trim() === '') && styles.busy,
            ]}
          >
            {busy ? (
              <ActivityIndicator color={colors.surface} />
            ) : (
              <Text style={styles.actionText}>✓ Confirmar entrega</Text>
            )}
          </Pressable>
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: '#BBF7D0',
    borderRadius: 14,
    borderWidth: 2,
    padding: spacing.lg,
  },
  top: {
    alignItems: 'flex-start',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    justifyContent: 'space-between',
  },
  tripLeft: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    minWidth: 0,
  },
  progressBadge: {
    alignItems: 'center',
    backgroundColor: GREEN,
    borderRadius: radius.pill,
    flexDirection: 'row',
    gap: 6,
    paddingHorizontal: spacing.md,
    paddingVertical: 5,
  },
  pulseDot: {
    backgroundColor: colors.surface,
    borderRadius: radius.pill,
    height: 7,
    width: 7,
  },
  progressText: {
    color: colors.surface,
    fontSize: 11.5,
    fontWeight: '800',
  },
  orderNumberBadge: {
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
  gainBlock: {
    alignItems: 'flex-end',
  },
  gainLabel: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '600',
  },
  gainValue: {
    color: ACCENT,
    fontSize: 26,
    fontWeight: '800',
    letterSpacing: -0.9,
    lineHeight: 30,
  },
  steps: {
    flexDirection: 'row',
    gap: 6,
    marginBottom: spacing.lg,
    marginTop: spacing.lg,
  },
  step: {
    alignItems: 'center',
    backgroundColor: '#eef2f7',
    borderRadius: radius.pill,
    flex: 1,
    justifyContent: 'center',
    minHeight: 38,
    paddingHorizontal: 4,
    paddingVertical: 8,
  },
  stepActive: {
    backgroundColor: ACCENT,
  },
  stepText: {
    color: '#94a3b8',
    fontSize: 11,
    fontWeight: '800',
    textAlign: 'center',
  },
  stepTextActive: {
    color: colors.surface,
  },
  info: {
    backgroundColor: '#f8fafc',
    borderColor: '#eef2f7',
    borderRadius: radius.md,
    borderWidth: 1,
    gap: spacing.md,
    padding: spacing.lg,
  },
  infoDivider: {
    borderTopColor: '#e2e8f0',
    borderTopWidth: 1,
    paddingTop: spacing.md,
  },
  infoLabel: {
    color: '#94a3b8',
    fontSize: 9.5,
    fontWeight: '800',
    letterSpacing: 0.9,
    textTransform: 'uppercase',
  },
  infoName: {
    color: colors.text,
    fontSize: 14.5,
    fontWeight: '800',
    letterSpacing: -0.2,
    marginTop: 4,
  },
  infoLine: {
    color: colors.muted,
    fontSize: 12.5,
    lineHeight: 18,
    marginTop: 3,
  },
  phoneRow: {
    alignSelf: 'flex-start',
    marginTop: 6,
    paddingVertical: 2,
  },
  phoneText: {
    color: ACCENT,
    fontSize: 12.5,
    fontWeight: '700',
  },
  mapRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  mapButton: {
    alignItems: 'center',
    borderRadius: radius.pill,
    flex: 1,
    justifyContent: 'center',
    minHeight: 46,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  mapsButton: {
    backgroundColor: NAVY,
  },
  mapsButtonText: {
    color: colors.surface,
    fontSize: 12.5,
    fontWeight: '800',
  },
  wazeButton: {
    backgroundColor: colors.surface,
    borderColor: '#cbd5e1',
    borderWidth: 1.5,
  },
  wazeButtonText: {
    color: NAVY,
    fontSize: 12.5,
    fontWeight: '800',
  },
  actionButton: {
    alignItems: 'center',
    borderRadius: radius.pill,
    justifyContent: 'center',
    marginTop: spacing.md,
    minHeight: 48,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  pickupButton: {
    backgroundColor: ACCENT,
  },
  doneButton: {
    backgroundColor: GREEN,
  },
  actionText: {
    color: colors.surface,
    fontSize: 13.5,
    fontWeight: '800',
  },
  deliveredChip: {
    alignItems: 'center',
    alignSelf: 'flex-start',
    backgroundColor: '#F0FDF4',
    borderColor: '#BBF7D0',
    borderRadius: radius.pill,
    borderWidth: 1,
    marginTop: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  deliveredText: {
    color: GREEN,
    fontSize: 13,
    fontWeight: '800',
  },
  pinRow: {
    backgroundColor: '#F0FDF4',
    borderColor: '#BBF7D0',
    borderRadius: radius.md,
    borderStyle: 'dashed',
    borderWidth: 1,
    marginTop: spacing.md,
    padding: spacing.md,
  },
  pinHint: {
    color: colors.muted,
    fontSize: 11.5,
    fontWeight: '600',
  },
  pinInput: {
    backgroundColor: colors.surface,
    borderColor: '#cbd5e1',
    borderRadius: radius.md,
    borderWidth: 1,
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
    letterSpacing: 3,
    marginTop: spacing.sm,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    textAlign: 'center',
  },
  pressed: {
    opacity: 0.78,
  },
  busy: {
    opacity: 0.65,
  },
});
