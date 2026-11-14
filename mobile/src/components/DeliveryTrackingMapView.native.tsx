import { useEffect, useMemo, useRef } from 'react';
import { Platform, StyleSheet, Text, View } from 'react-native';
import MapView, { MAP_TYPES, Marker, Polyline, UrlTile } from 'react-native-maps';

import type { TrackLatLng } from '@/lib/orderTracking';

import type { TrackingMapViewProps } from './DeliveryTrackingMapView';

const ROUTE_COLOR = '#ea580c';

type DriverMarker = {
  animateMarkerToCoordinate?: (coordinate: TrackLatLng, duration?: number) => void;
};

/**
 * Mapa nativo (iOS/Android): teselas de CARTO Voyager (sin API key y sin
 * bloqueos por User-Agent) con atribución OSM + CARTO, marcadores de
 * comercio, domicilio y repartidor, y ruta naranja.
 *
 * Se desactivan todos los controles flotantes nativos (brújula, botón de
 * ubicación, zoom y toolbar) para que ningún botón circular quede flotando
 * sobre las tarjetas de dirección/pago de la pantalla de pedido.
 *
 * Base sin API key: en Android el mapa base de Google sin key queda gris,
 * así que se usa `mapType="none"` y las teselas CARTO son la base visible
 * (equivalente al `invalidateSize` + CSS de tiles de la web: el mapa siempre
 * pinta sobre un contenedor con tamaño explícito). En iOS se usa el base de
 * Apple, que no requiere key. `loadingEnabled` evita el vacío gris mientras
 * cargan las teselas.
 */
export function DeliveryTrackingMapView({
  origin,
  destination,
  driverPos,
  driverLabel,
  shopInitials,
}: TrackingMapViewProps) {
  const mapRef = useRef<MapView | null>(null);
  const driverRef = useRef<DriverMarker | null>(null);

  const initialRegion = useMemo(
    () => ({
      latitude: (origin.latitude + destination.latitude) / 2,
      longitude: (origin.longitude + destination.longitude) / 2,
      latitudeDelta: Math.max(Math.abs(origin.latitude - destination.latitude) * 2.2, 0.02),
      longitudeDelta: Math.max(Math.abs(origin.longitude - destination.longitude) * 2.2, 0.02),
    }),
    [origin, destination],
  );

  useEffect(() => {
    mapRef.current?.fitToCoordinates([origin, destination], {
      // Margen extra arriba/abajo para que la etiqueta del repartidor y el
      // pin de destino no queden cortados por los bordes del mapa.
      edgePadding: { top: 70, right: 50, bottom: 90, left: 50 },
      animated: false,
    });
  }, [origin, destination]);

  // Movimiento suave del repartidor entre cada polling (4,5 s).
  useEffect(() => {
    try {
      driverRef.current?.animateMarkerToCoordinate?.(driverPos, 4500);
    } catch {
      // Si la animación no está disponible, el marcador usa `coordinate`.
    }
  }, [driverPos]);

  return (
    <View style={styles.map}>
      <MapView
        ref={mapRef}
        style={styles.map}
        mapType={Platform.OS === 'android' ? MAP_TYPES.NONE : MAP_TYPES.STANDARD}
        loadingEnabled
        loadingBackgroundColor="#e2e8f0"
        initialRegion={initialRegion}
        rotateEnabled={false}
        pitchEnabled={false}
        toolbarEnabled={false}
        showsCompass={false}
        showsScale={false}
        showsTraffic={false}
        showsMyLocationButton={false}
        zoomControlEnabled={false}
        moveOnMarkerPress={false}
      >
        <UrlTile
          urlTemplate="https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png"
          maximumZ={20}
          tileSize={256}
          zIndex={-1}
          flipY={false}
        />
        <Polyline
          coordinates={[origin, driverPos, destination]}
          strokeColor={ROUTE_COLOR}
          strokeWidth={5}
          lineJoin="round"
        />
        <Marker coordinate={origin} anchor={{ x: 0.5, y: 0.5 }} tracksViewChanges={false}>
          <View style={[styles.pin, styles.pinOrigin]}>
            <Text style={styles.pinOriginText}>{shopInitials}</Text>
          </View>
        </Marker>
        <Marker
          coordinate={destination}
          anchor={{ x: 0.5, y: 1 }}
          tracksViewChanges={false}
        >
          <View style={[styles.pin, styles.pinDest]}>
            <Text style={styles.pinDestText}>🏠</Text>
          </View>
        </Marker>
        {/* El marcador del repartidor va último para que quede por encima del
            destino cuando ambas posiciones coinciden. */}
        <Marker
          ref={(instance) => {
            driverRef.current = instance as unknown as DriverMarker | null;
          }}
          coordinate={driverPos}
          anchor={{ x: 0.5, y: 1 }}
        >
          <View style={styles.driverWrap}>
            <View style={styles.driverLabel}>
              <Text style={styles.driverLabelText} numberOfLines={1} ellipsizeMode="tail">
                {driverLabel}
              </Text>
            </View>
            <View style={[styles.pin, styles.pinDriver]}>
              <Text style={styles.pinDriverText}>🛵</Text>
            </View>
          </View>
        </Marker>
      </MapView>
    </View>
  );
}

const styles = StyleSheet.create({
  map: {
    height: 320,
    width: '100%',
  },
  pin: {
    alignItems: 'center',
    borderColor: '#fff',
    borderRadius: 19,
    borderWidth: 3,
    elevation: 4,
    height: 38,
    justifyContent: 'center',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.35,
    shadowRadius: 4,
    width: 38,
  },
  pinOrigin: {
    backgroundColor: '#0c2a4d',
  },
  pinOriginText: {
    color: '#fff',
    fontSize: 13,
    fontWeight: '800',
  },
  pinDest: {
    backgroundColor: ROUTE_COLOR,
  },
  pinDestText: {
    fontSize: 17,
  },
  pinDriver: {
    backgroundColor: ROUTE_COLOR,
    borderRadius: 22,
    height: 44,
    width: 44,
  },
  pinDriverText: {
    fontSize: 21,
  },
  driverWrap: {
    alignItems: 'center',
    maxWidth: 240,
  },
  driverLabel: {
    backgroundColor: '#fff',
    borderColor: '#e2e8f0',
    borderRadius: 999,
    borderWidth: 1,
    elevation: 3,
    zIndex: 10,
    marginBottom: 10,
    maxWidth: 220,
    paddingHorizontal: 10,
    paddingVertical: 5,
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.22,
    shadowRadius: 6,
  },
  driverLabelText: {
    color: '#0f172a',
    fontSize: 11.5,
    fontWeight: '700',
    maxWidth: 200,
  },
});
