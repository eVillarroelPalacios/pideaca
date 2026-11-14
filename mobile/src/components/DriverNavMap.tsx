import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import {
  etaMinutesForKm,
  formatRemainKm,
  haversineKm,
  initials,
  type TrackLatLng,
} from '@/lib/orderTracking';
import { colors, radius, spacing } from '@/theme';
import type { DriverActiveOrder } from '@/types/api';

import { DeliveryTrackingMapView } from './DeliveryTrackingMapView';

const ACCENT = '#D24C19';
const SIMULATED_OFFSET = 0.0045;

function num(value: unknown): number | null {
  const parsed = typeof value === 'number' ? value : parseFloat(String(value ?? ''));

  return Number.isFinite(parsed) ? parsed : null;
}

/**
 * Mapa de navegación en vivo del repartidor (paso 2. En entrega).
 *
 * Reutiliza `DeliveryTrackingMapView` (react-native-maps + teselas CARTO,
 * polilínea naranja) con la misma lógica que el mapa del cliente: el
 * marcador 🛵 es la posición GPS en vivo, el pin es el destino del cliente
 * y el badge muestra distancia restante + ETA recalculados en cada fix.
 *
 * Sin GPS se usa el punto de retiro (o una ubicación simulada cercana)
 * para que el mapa siempre se visualice sin romperse.
 */
export function DriverNavMap({
  order,
  driverPos,
}: {
  order: DriverActiveOrder;
  driverPos: TrackLatLng | null;
}) {
  const destLat = num(order.address?.latitude);
  const destLng = num(order.address?.longitude);

  if (destLat === null || destLng === null) {
    return (
      <View style={styles.empty}>
        <Text style={styles.emptyIcon}>🗺️</Text>
        <Text style={styles.emptyTitle}>Mapa no disponible todavía</Text>
        <Text style={styles.emptyText}>
          Faltan coordenadas del domicilio para trazar la ruta en vivo del pedido{' '}
          {order.order_number}.
        </Text>
      </View>
    );
  }

  const destination: TrackLatLng = { latitude: destLat, longitude: destLng };
  const pickupLat = num(order.provider.pickup_address?.latitude);
  const pickupLng = num(order.provider.pickup_address?.longitude);
  const pickup: TrackLatLng | null =
    pickupLat !== null && pickupLng !== null
      ? { latitude: pickupLat, longitude: pickupLng }
      : null;

  const simulated = driverPos === null;
  // Origen de la ruta: punto de retiro conocido (pin del comercio) o punto
  // simulado cercano si tampoco hay coordenadas de retiro.
  const origin: TrackLatLng =
    pickup ?? { latitude: destLat + SIMULATED_OFFSET, longitude: destLng + SIMULATED_OFFSET };
  // Posición del repartidor: GPS vivo, o el origen como fallback.
  const from: TrackLatLng = driverPos ?? origin;

  const km = haversineKm(from, destination);
  const mins = etaMinutesForKm(km);
  const shopName = order.provider.business_name || 'Comercio';

  return (
    <View style={styles.wrap}>
      <View style={styles.head}>
        <View style={[styles.badge, simulated && styles.badgeSim]}>
          <Text style={styles.badgeText}>
            🛵 {formatRemainKm(km)} · ~{mins} min
          </Text>
        </View>
        {simulated ? (
          <Text style={styles.simText}>
            📍 Sin GPS: ruta desde {pickup ? 'el punto de retiro' : 'ubicación simulada'}
          </Text>
        ) : null}
      </View>
      <DeliveryTrackingMapView
        origin={origin}
        destination={destination}
        driverPos={from}
        driverLabel={`Vos · ~${mins} min`}
        shopInitials={initials(shopName)}
        shopName={shopName}
      />
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Abrir ruta en el mapa externo"
        onPress={() => {
          void Linking.openURL(
            `https://www.openstreetmap.org/directions?to=${destination.latitude}%2C${destination.longitude}`,
          );
        }}
        style={({ pressed }) => [styles.osmLink, pressed && styles.pressed]}
      >
        <Text style={styles.osmLinkText}>© OpenStreetMap · © CARTO · Abrir ruta ↗</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  pressed: {
    opacity: 0.75,
  },
  wrap: {
    backgroundColor: '#0c2a4d',
    borderRadius: radius.md,
    marginTop: spacing.md,
    overflow: 'hidden',
  },
  head: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    padding: spacing.md,
  },
  badge: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: radius.pill,
    paddingHorizontal: spacing.lg,
    paddingVertical: 8,
  },
  badgeSim: {
    backgroundColor: 'rgba(255,255,255,0.14)',
    borderColor: 'rgba(255,255,255,0.25)',
    borderWidth: 1,
  },
  badgeText: {
    color: colors.surface,
    fontSize: 12.5,
    fontWeight: '800',
  },
  simText: {
    color: 'rgba(255,255,255,0.75)',
    fontSize: 11.5,
    fontWeight: '600',
    paddingVertical: 8,
  },
  empty: {
    alignItems: 'center',
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderStyle: 'dashed',
    borderWidth: 1,
    gap: spacing.sm,
    marginTop: spacing.md,
    padding: spacing.xl,
  },
  emptyIcon: {
    fontSize: 26,
  },
  emptyTitle: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
  },
  emptyText: {
    color: colors.muted,
    fontSize: 12.5,
    lineHeight: 18,
    textAlign: 'center',
  },
  osmLink: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderTopWidth: 1,
    paddingVertical: spacing.sm,
  },
  osmLinkText: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '600',
  },
});
