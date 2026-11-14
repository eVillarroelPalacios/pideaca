import { useEffect, useMemo, useState } from 'react';
import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import { api } from '@/api/client';
import {
  initials,
  isTrackable,
  trackCoords,
  type TrackCoords,
  type TrackLatLng,
} from '@/lib/orderTracking';
import { colors, radius, spacing } from '@/theme';
import type { MarketMyOrder } from '@/types/api';

import { DeliveryTrackingMapView } from './DeliveryTrackingMapView';

const POLL_MS = 5000;

export type DriverTracking = {
  /** El pedido está en reparto con repartidor (corresponde mostrar el mapa). */
  available: boolean;
  /** Origen y destino si hay coordenadas para trazar (null = sin mapa). */
  coords: TrackCoords | null;
  /** Última posición conocida del repartidor (null = aún sin dato). */
  driverPos: TrackLatLng | null;
  /** Minutos estimados restantes (null = aún sin dato). */
  etaMin: number | null;
  driverName: string;
  shopName: string;
};

/**
 * Sigue al repartidor con polling cada 5 s al endpoint de ubicación.
 * Un solo intervalo por tarjeta: se crea al montar y se limpia al
 * desmontar, al cambiar de pedido o al salir del estado en camino.
 */
export function useDriverTracking(order: MarketMyOrder): DriverTracking {
  const coords = useMemo(() => trackCoords(order), [order]);
  const [driverPos, setDriverPos] = useState<TrackLatLng | null>(null);
  const [etaMin, setEtaMin] = useState<number | null>(null);
  const available = isTrackable(order);

  // Reset al cambiar de pedido o de disponibilidad (patrón "ajustar estado
  // durante el render": evita setState sincrónico dentro del efecto).
  const resetKey = `${order.id}:${available ? '1' : '0'}:${coords ? '1' : '0'}`;
  const [prevResetKey, setPrevResetKey] = useState(resetKey);

  if (prevResetKey !== resetKey) {
    setPrevResetKey(resetKey);
    setDriverPos(null);
    setEtaMin(null);
  }

  useEffect(() => {
    if (!available || !coords) {
      return;
    }

    let cancelled = false;

    const tick = () => {
      api
        .driverLocation(order.id)
        .then((response) => {
          if (cancelled) {
            return;
          }

          if (
            !response.success ||
            response.status !== 'on_the_way' ||
            !response.map_available ||
            !response.driver_location
          ) {
            return;
          }

          setDriverPos({
            latitude: response.driver_location.latitude,
            longitude: response.driver_location.longitude,
          });
          setEtaMin(typeof response.eta_minutos === 'number' ? response.eta_minutos : null);
        })
        .catch(() => {
          // El siguiente ciclo reintenta sin interrumpir la lectura.
        });
    };

    tick();
    const timer = setInterval(tick, POLL_MS);

    return () => {
      cancelled = true;
      clearInterval(timer);
    };
  }, [available, coords, order.id]);

  return {
    available,
    coords,
    driverPos,
    etaMin,
    driverName: order.driver?.name ?? '',
    shopName: order.provider?.business_name ?? 'Comercio',
  };
};

function firstName(name: string): string {
  return name.split(' ')[0] || name;
}

/**
 * Mapa en vivo estilo DiDi: comercio, domicilio, ruta naranja y repartidor
 * con etiqueta flotante. En web se muestra un aviso con enlace externo
 * (ver DeliveryTrackingMapView).
 */
export function DeliveryTrackingMap({
  tracking,
  orderNumber,
}: {
  tracking: DriverTracking;
  orderNumber: string;
}) {
  if (!tracking.available) {
    return null;
  }

  if (!tracking.coords) {
    return (
      <View style={styles.empty}>
        <Text style={styles.emptyIcon}>🗺️</Text>
        <Text style={styles.emptyTitle}>Mapa no disponible todavía</Text>
        <Text style={styles.emptyText}>
          Faltan coordenadas de ubicación para trazar la ruta en vivo del pedido {orderNumber}.
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.wrap}>
      <DeliveryTrackingMapView
        origin={tracking.coords.origin}
        destination={tracking.coords.destination}
        driverPos={tracking.driverPos ?? tracking.coords.origin}
        driverLabel={
          tracking.etaMin !== null
            ? `${firstName(tracking.driverName || 'Tu repartidor')} - ${tracking.etaMin} min`
            : firstName(tracking.driverName || 'Tu repartidor')
        }
        shopInitials={initials(tracking.shopName)}
        shopName={tracking.shopName}
      />
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Abrir ruta en el mapa externo"
        onPress={() => {
          const d = tracking.coords?.destination;
          if (d) {
            void Linking.openURL(
              `https://www.openstreetmap.org/directions?to=${d.latitude}%2C${d.longitude}`,
            );
          }
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
    borderRadius: radius.md,
    marginBottom: spacing.md,
    overflow: 'hidden',
  },
  empty: {
    alignItems: 'center',
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderStyle: 'dashed',
    borderWidth: 1,
    gap: spacing.sm,
    marginBottom: spacing.md,
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
