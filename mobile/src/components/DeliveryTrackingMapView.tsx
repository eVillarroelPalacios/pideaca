import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import type { TrackLatLng } from '@/lib/orderTracking';
import { colors, radius, spacing } from '@/theme';

export type TrackingMapViewProps = {
  origin: TrackLatLng;
  destination: TrackLatLng;
  driverPos: TrackLatLng;
  driverLabel: string;
  shopInitials: string;
  shopName: string;
};

/**
 * Web (react-native-web): react-native-maps no tiene implementación web,
 * así que se muestra una tarjeta con las coordenadas y un enlace externo.
 * En iOS/Android Metro usa DeliveryTrackingMapView.native.tsx.
 */
export function DeliveryTrackingMapView({ origin, destination }: TrackingMapViewProps) {
  const openExternal = () => {
    void Linking.openURL(
      `https://www.openstreetmap.org/directions?from=${origin.latitude}%2C${origin.longitude}&to=${destination.latitude}%2C${destination.longitude}`,
    );
  };

  return (
    <View style={styles.fallback}>
      <Text style={styles.fallbackIcon}>🗺️</Text>
      <Text style={styles.fallbackTitle}>Seguimiento en vivo</Text>
      <Text style={styles.fallbackText}>
        El mapa interactivo está disponible en la app móvil. Ruta de {origin.latitude.toFixed(4)},{' '}
        {origin.longitude.toFixed(4)} hacia {destination.latitude.toFixed(4)},{' '}
        {destination.longitude.toFixed(4)}.
      </Text>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Abrir ruta en OpenStreetMap"
        onPress={openExternal}
        style={({ pressed }) => [styles.fallbackButton, pressed && styles.pressed]}
      >
        <Text style={styles.fallbackButtonText}>Abrir ruta ↗</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  pressed: {
    opacity: 0.75,
  },
  fallback: {
    alignItems: 'center',
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderWidth: 1,
    gap: spacing.sm,
    height: 320,
    justifyContent: 'center',
    padding: spacing.xl,
  },
  fallbackIcon: {
    fontSize: 30,
  },
  fallbackTitle: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '800',
  },
  fallbackText: {
    color: colors.muted,
    fontSize: 12.5,
    lineHeight: 18,
    textAlign: 'center',
  },
  fallbackButton: {
    backgroundColor: '#ea580c',
    borderRadius: radius.sm,
    marginTop: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
  },
  fallbackButtonText: {
    color: '#fff',
    fontSize: 13,
    fontWeight: '700',
  },
});
