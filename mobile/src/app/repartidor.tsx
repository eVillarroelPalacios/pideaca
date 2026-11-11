import * as Location from 'expo-location';
import { setAudioModeAsync, useAudioPlayer } from 'expo-audio';
import { router, useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
  Alert,
  Linking,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ActiveDeliveryCard } from '@/components/ActiveDeliveryCard';
import { DeliveryOfferCard } from '@/components/DeliveryOfferCard';
import { DriverHeader } from '@/components/DriverHeader';
import { DriverKpisGrid } from '@/components/DriverKpisGrid';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { API_BASE_URL } from '@/config';
import { colors, radius, spacing } from '@/theme';
import type {
  DriverActiveOrder,
  DriverOffer,
  DriverStatsResponse,
  DriverStatusPayload,
} from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';
const GREEN = '#16a34a';
const AMBER = '#f59e0b';
const POLL_MS = 10000;

// El panel web vive fuera del prefijo del API móvil.
const PANEL_URL = API_BASE_URL.replace(/\/api\/mobile\/v1\/?$/, '/delivery');

type Tab = 'available' | 'inprogress';

type GpsState = {
  status: 'loading' | 'active' | 'off';
  latitude: number | null;
  longitude: number | null;
  accuracy: number | null;
};

function errorMessage(caught: unknown, fallback: string): string {
  if (caught instanceof ApiError) {
    const first = caught.errors ? Object.values(caught.errors)[0] : null;

    if (first && first.length > 0) {
      return first[0];
    }

    return caught.message;
  }

  return fallback;
}

function vehicleIcon(value: string | null): string {
  switch (value) {
    case 'moto_150':
    case 'moto_250':
      return '🏍️';
    case 'auto':
      return '🚗';
    case 'camioneta':
      return '🚙';
    case 'bici':
      return '🚲';
    case 'pie':
      return '🚶';
    default:
      return '🚗';
  }
}

function EmptyState({
  icon,
  title,
  subtitle,
}: {
  icon: string;
  title: string;
  subtitle: string;
}) {
  return (
    <View style={styles.empty}>
      <Text style={styles.emptyIcon}>{icon}</Text>
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySub}>{subtitle}</Text>
    </View>
  );
}

/**
 * Terminal Central de Repartos (Driver Pro).
 *
 * Espeja `dashboard.sections.repartidor`: cabecera del repartidor, telemetría
 * de vehículo/zona/radar/GPS, KPIs, ofertas disponibles y viajes en curso.
 * Las ofertas y los pedidos se refrescan cada 10 s mientras la pantalla está
 * enfocada y el repartidor está en línea, con un beep cuando entra una oferta
 * nueva y el Radar Sonoro está activo.
 */
export default function RepartidorScreen() {
  const [stats, setStats] = useState<DriverStatsResponse | null>(null);
  const [offers, setOffers] = useState<DriverOffer[]>([]);
  const [activeOrders, setActiveOrders] = useState<DriverActiveOrder[]>([]);
  const [tab, setTab] = useState<Tab>('available');

  const [online, setOnline] = useState(true);
  const [zone, setZone] = useState('');
  const [radarOn, setRadarOn] = useState(true);
  const [gps, setGps] = useState<GpsState>({
    status: 'loading',
    latitude: null,
    longitude: null,
    accuracy: null,
  });

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [busyOfferId, setBusyOfferId] = useState<number | null>(null);
  const [busyOrderId, setBusyOrderId] = useState<number | null>(null);

  const [goal, setGoal] = useState(8);
  const [goalOpen, setGoalOpen] = useState(false);
  const [goalDraft, setGoalDraft] = useState('8');
  const [vehicleOpen, setVehicleOpen] = useState(false);
  const [savingVehicle, setSavingVehicle] = useState(false);

  const player = useAudioPlayer(require('../../assets/beep.wav'));

  const onlineRef = useRef(true);
  const radarRef = useRef(true);
  const zoneRef = useRef('');
  const gpsRef = useRef<{
    latitude: number | null;
    longitude: number | null;
    accuracy: number | null;
  }>({ latitude: null, longitude: null, accuracy: null });
  const lastAvailableIds = useRef<number[]>([]);
  const zoneTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const initialized = useRef(false);
  const mounted = useRef(true);

  useEffect(() => {
    mounted.current = true;

    return () => {
      mounted.current = false;

      if (zoneTimer.current) {
        clearTimeout(zoneTimer.current);
      }
    };
  }, []);

  useEffect(() => {
    void setAudioModeAsync({ playsInSilentMode: true }).catch(() => undefined);
  }, []);

  const playBeep = useCallback(async () => {
    if (!radarRef.current) {
      return;
    }

    try {
      await player.seekTo(0);
      player.play();
    } catch {
      // Sin audio disponible: la terminal sigue funcionando en silencio.
    }
  }, [player]);

  const persistStatus = useCallback(
    async (overrides: Partial<DriverStatusPayload> = {}) => {
      const payload: DriverStatusPayload = {
        online: onlineRef.current,
        zone: zoneRef.current.trim() === '' ? null : zoneRef.current.trim(),
        latitude: gpsRef.current.latitude,
        longitude: gpsRef.current.longitude,
        accuracy: gpsRef.current.accuracy,
        ...overrides,
      };

      try {
        await api.driverUpdateStatus(payload);
      } catch {
        // La telemetría del panel no debe bloquear la operación.
      }
    },
    [],
  );

  const refreshGps = useCallback(async () => {
    setGps((current) => ({ ...current, status: 'loading' }));

    try {
      const permission = await Location.requestForegroundPermissionsAsync();

      if (permission.status !== 'granted') {
        if (mounted.current) {
          setGps({ status: 'off', latitude: null, longitude: null, accuracy: null });
        }

        return;
      }

      const position = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.Balanced,
      });

      if (!mounted.current) {
        return;
      }

      const next: GpsState = {
        status: 'active',
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy,
      };

      gpsRef.current = {
        latitude: next.latitude,
        longitude: next.longitude,
        accuracy: next.accuracy,
      };
      setGps(next);
      void persistStatus();
    } catch {
      if (mounted.current) {
        setGps((current) => ({ ...current, status: 'off' }));
      }
    }
  }, [persistStatus]);

  const loadOffers = useCallback(
    async (silent = false) => {
      try {
        const response = await api.driverAvailableOrders();
        const ids = response.orders.map((offer) => offer.id);
        const previous = lastAvailableIds.current;
        const hasNew = ids.some((id) => !previous.includes(id));

        // Solo suena cuando ya había una carga previa, hay una oferta nueva y
        // el repartidor está en línea con el radar activo.
        if (previous.length > 0 && hasNew && onlineRef.current && radarRef.current) {
          void playBeep();
        }

        lastAvailableIds.current = ids;
        setOffers(response.orders);
      } catch (caught) {
        if (!silent) {
          setError(errorMessage(caught, 'No se pudieron cargar las ofertas.'));
        }
      }
    },
    [playBeep],
  );

  const loadMyOrders = useCallback(async (silent = false) => {
    try {
      const response = await api.driverMyOrders();
      setActiveOrders(response.orders);
    } catch (caught) {
      if (!silent) {
        setError(errorMessage(caught, 'No se pudieron cargar tus pedidos.'));
      }
    }
  }, []);

  const loadStats = useCallback(async (silent = false) => {
    try {
      const response = await api.driverStats();
      setStats(response);
    } catch (caught) {
      if (!silent) {
        setError(errorMessage(caught, 'No se pudieron cargar los indicadores.'));
      }
    }
  }, []);

  const bootstrap = useCallback(async () => {
    setLoading(true);

    try {
      const [statsResponse, statusResponse] = await Promise.all([
        api.driverStats(),
        api.driverStatus(),
      ]);

      if (!mounted.current) {
        return;
      }

      setStats(statsResponse);

      const nextOnline = statusResponse.status.online;
      const nextZone = statusResponse.status.zone ?? '';

      setOnline(nextOnline);
      onlineRef.current = nextOnline;
      setZone(nextZone);
      zoneRef.current = nextZone;

      if (
        statusResponse.status.latitude !== null &&
        statusResponse.status.longitude !== null
      ) {
        const cached: GpsState = {
          status: 'active',
          latitude: statusResponse.status.latitude,
          longitude: statusResponse.status.longitude,
          accuracy: statusResponse.status.accuracy,
        };

        gpsRef.current = {
          latitude: cached.latitude,
          longitude: cached.longitude,
          accuracy: cached.accuracy,
        };
        setGps(cached);
      }

      await Promise.all([loadOffers(true), loadMyOrders(true)]);

      if (mounted.current) {
        setError(null);
      }
    } catch (caught) {
      if (mounted.current) {
        setError(
          errorMessage(caught, 'No se pudo conectar con la terminal de repartos.'),
        );
      }
    } finally {
      if (mounted.current) {
        setLoading(false);
      }
    }
  }, [loadMyOrders, loadOffers]);

  useFocusEffect(
    useCallback(() => {
      if (!initialized.current) {
        initialized.current = true;
        void bootstrap();
      } else {
        void loadStats(true);
        void loadOffers(true);
        void loadMyOrders(true);
      }

      void refreshGps();

      const interval = setInterval(() => {
        if (onlineRef.current) {
          void loadOffers(true);
          void loadMyOrders(true);
        }
      }, POLL_MS);

      return () => clearInterval(interval);
    }, [bootstrap, loadMyOrders, loadOffers, loadStats, refreshGps]),
  );

  async function handleRefresh() {
    setRefreshing(true);

    try {
      await Promise.all([loadStats(true), loadOffers(true), loadMyOrders(true)]);
      setError(null);
    } finally {
      setRefreshing(false);
    }
  }

  function toggleConnection() {
    const next = !online;

    setOnline(next);
    onlineRef.current = next;
    void persistStatus({ online: next });

    if (next) {
      void loadOffers(true);
      void loadMyOrders(true);
    }
  }

  function toggleRadar() {
    const next = !radarOn;

    setRadarOn(next);
    radarRef.current = next;

    if (next) {
      void playBeep();
    }
  }

  function changeZone(text: string) {
    setZone(text);
    zoneRef.current = text;

    if (zoneTimer.current) {
      clearTimeout(zoneTimer.current);
    }

    zoneTimer.current = setTimeout(() => {
      void persistStatus();
    }, 700);
  }

  function openPanel() {
    void Linking.openURL(PANEL_URL).catch(() => {
      Alert.alert('No se pudo abrir el panel clásico', PANEL_URL);
    });
  }

  function openGoal() {
    setGoalDraft(String(goal));
    setGoalOpen(true);
  }

  function saveGoal() {
    const parsed = Number.parseInt(goalDraft.replace(/[^\d]/g, ''), 10);

    if (Number.isFinite(parsed) && parsed > 0) {
      setGoal(Math.min(parsed, 99));
    }

    setGoalOpen(false);
  }

  async function takeOffer(offer: DriverOffer) {
    if (!stats?.driver.has_vehicle) {
      Alert.alert(
        'Configurá tu vehículo',
        'Antes de tomar pedidos elegí tu unidad en el botón Configurar vehículo.',
      );
      setVehicleOpen(true);
      return;
    }

    setBusyOfferId(offer.id);

    try {
      await api.driverTakeOrder(offer.id);
      void playBeep();
      await Promise.all([loadOffers(true), loadMyOrders(true), loadStats(true)]);
      setTab('inprogress');
      setError(null);
      Alert.alert('Pedido tomado', 'Ya está en tus pedidos en curso.');
    } catch (caught) {
      Alert.alert(
        'No se pudo tomar el pedido',
        errorMessage(caught, 'Intentá de nuevo en unos segundos.'),
      );
    } finally {
      setBusyOfferId(null);
    }
  }

  async function pickupOrder(order: DriverActiveOrder) {
    setBusyOrderId(order.id);

    try {
      await api.driverPickupOrder(order.id);
      void playBeep();
      await Promise.all([loadMyOrders(true), loadStats(true)]);
      setError(null);
    } catch (caught) {
      Alert.alert(
        'No se pudo actualizar el pedido',
        errorMessage(caught, 'Intentá de nuevo en unos segundos.'),
      );
    } finally {
      setBusyOrderId(null);
    }
  }

  async function confirmDelivery(order: DriverActiveOrder, pin: string) {
    setBusyOrderId(order.id);

    try {
      const response = await api.driverConfirmDelivery(order.id, pin);
      void playBeep();
      await Promise.all([
        loadMyOrders(true),
        loadOffers(true),
        loadStats(true),
      ]);
      setError(null);
      Alert.alert(
        'Entrega confirmada',
        response.message || 'La entrega se confirmó correctamente.',
      );
    } catch (caught) {
      Alert.alert(
        'No se pudo confirmar la entrega',
        errorMessage(caught, 'Verificá el PIN e intentá de nuevo.'),
      );
    } finally {
      setBusyOrderId(null);
    }
  }

  async function chooseVehicle(vehicleType: string) {
    if (!stats) {
      return;
    }

    if (vehicleType === stats.driver.vehicle_type) {
      setVehicleOpen(false);
      return;
    }

    setSavingVehicle(true);

    try {
      const response = await api.updateVehicle(
        vehicleType,
        stats.driver.vehicle_plate ?? undefined,
      );

      setStats((current) =>
        current
          ? {
              ...current,
              driver: {
                ...current.driver,
                vehicle_type: response.vehicle_type,
                vehicle_label: response.label,
                vehicle_plate: response.vehicle_plate,
                has_vehicle: Boolean(response.vehicle_type),
              },
            }
          : current,
      );
      setVehicleOpen(false);
      Alert.alert(
        'Vehículo actualizado',
        response.message || 'Tu unidad ya está configurada.',
      );
    } catch (caught) {
      Alert.alert(
        'No se pudo cambiar el vehículo',
        errorMessage(caught, 'Intentá de nuevo.'),
      );
    } finally {
      setSavingVehicle(false);
    }
  }

  if (loading && !stats) {
    return <LoadingScreen message="Conectando con la central de repartos…" />;
  }

  if (!stats) {
    return (
      <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
        <View style={styles.fallback}>
          <Text style={styles.fallbackTitle}>Terminal no disponible</Text>
          <ErrorBanner
            message={error ?? 'No se pudo cargar la terminal de repartos.'}
          />
          <Pressable
            accessibilityRole="button"
            onPress={() => void bootstrap()}
            style={styles.primaryButton}
          >
            <Text style={styles.primaryButtonText}>Reintentar</Text>
          </Pressable>
          <Pressable
            accessibilityRole="button"
            onPress={() => router.back()}
            style={styles.secondaryLink}
          >
            <Text style={styles.secondaryLinkText}>‹ Volver</Text>
          </Pressable>
        </View>
      </SafeAreaView>
    );
  }

  const gpsLabel =
    gps.status === 'active'
      ? `GPS activo${gps.accuracy !== null ? ` · ±${Math.round(gps.accuracy)} m` : ''}`
      : gps.status === 'loading'
        ? 'Buscando GPS…'
        : 'GPS sin señal';

  const isAvailable = tab === 'available';

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => void handleRefresh()}
            colors={[colors.brand]}
            tintColor={colors.brand}
          />
        }
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.topBar}>
          <Pressable
            accessibilityRole="button"
            onPress={() => router.back()}
            style={styles.backButton}
          >
            <Text style={styles.backText}>‹ Atrás</Text>
          </Pressable>
        </View>

        <DriverHeader
          name={stats.driver.name}
          initials={stats.driver.initials}
          driverCode={stats.driver.code}
          online={online}
          onToggleConnection={toggleConnection}
          onTogglePause={toggleConnection}
        />

        <View style={styles.toolbar}>
          <View style={styles.toolRow}>
            <Text style={styles.toolLabel}>Vehículo:</Text>
            {stats.driver.has_vehicle ? (
              <>
                <View style={styles.vehicleBadge}>
                  <Text style={styles.vehicleBadgeText}>
                    {vehicleIcon(stats.driver.vehicle_type)} {stats.driver.vehicle_label}
                  </Text>
                  {stats.driver.vehicle_plate ? (
                    <View style={styles.plate}>
                      <Text style={styles.plateText}>{stats.driver.vehicle_plate}</Text>
                    </View>
                  ) : null}
                </View>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Cambiar vehículo"
                  onPress={() => setVehicleOpen(true)}
                  style={({ pressed }) => [
                    styles.vehicleEdit,
                    pressed && styles.pressed,
                  ]}
                >
                  <Text style={styles.vehicleEditText}>✏️ Cambiar</Text>
                </Pressable>
              </>
            ) : (
              <>
                <Pressable
                  accessibilityRole="button"
                  onPress={() => setVehicleOpen(true)}
                  style={({ pressed }) => [
                    styles.vehicleSetup,
                    pressed && styles.pressed,
                  ]}
                >
                  <Text style={styles.vehicleSetupText}>⚠️ Configurar vehículo</Text>
                </Pressable>
                <Text style={styles.vehicleWarn}>
                  Configurá tu unidad antes de recibir pedidos.
                </Text>
              </>
            )}
          </View>

          <View style={styles.toolRow}>
            <Text style={styles.toolLabel}>Zona de cobertura:</Text>
            <TextInput
              autoCapitalize="words"
              autoCorrect={false}
              onBlur={() => void persistStatus()}
              onChangeText={changeZone}
              placeholder="Ej: Almagro & Caballito"
              placeholderTextColor="#94a3b8"
              style={styles.zoneInput}
              value={zone}
            />
          </View>

          <View style={styles.toolRow}>
            <Pressable
              accessibilityRole="switch"
              accessibilityState={{ checked: radarOn }}
              onPress={toggleRadar}
              style={({ pressed }) => [
                styles.radar,
                !radarOn && styles.radarOff,
                pressed && styles.pressed,
              ]}
            >
              <Text style={[styles.radarText, !radarOn && styles.radarTextOff]}>
                🔊 Radar Sonoro DiDi
              </Text>
            </Pressable>

            <View style={styles.gps}>
              <View
                style={[
                  styles.gpsDot,
                  gps.status === 'active' ? styles.dotGreen : styles.dotAmber,
                ]}
              />
              <Text style={styles.gpsText}>{gpsLabel}</Text>
            </View>
          </View>
        </View>

        {error ? (
          <View style={styles.errorWrap}>
            <ErrorBanner message={error} />
          </View>
        ) : null}

        <DriverKpisGrid
          earningsToday={stats.stats.earnings_today}
          baseToday={stats.stats.base_today}
          tipsToday={stats.stats.tips_today}
          deliveredToday={stats.stats.delivered_today}
          acceptance={stats.stats.acceptance}
          rating={stats.stats.rating}
          goal={goal}
          onPressGoal={openGoal}
        />

        <View style={styles.tabsWrap}>
          <View style={styles.tabs}>
            <Pressable
              accessibilityRole="tab"
              accessibilityState={{ selected: isAvailable }}
              onPress={() => setTab('available')}
              style={[styles.tab, isAvailable && styles.tabActive]}
            >
              <Text
                numberOfLines={1}
                style={[styles.tabText, isAvailable && styles.tabTextActive]}
              >
                Pedidos Disponibles
              </Text>
              <View style={[styles.tabCount, isAvailable && styles.tabCountActive]}>
                <Text
                  style={[
                    styles.tabCountText,
                    isAvailable && styles.tabCountTextActive,
                  ]}
                >
                  {offers.length}
                </Text>
              </View>
            </Pressable>

            <Pressable
              accessibilityRole="tab"
              accessibilityState={{ selected: !isAvailable }}
              onPress={() => setTab('inprogress')}
              style={[styles.tab, !isAvailable && styles.tabActive]}
            >
              <Text
                numberOfLines={1}
                style={[styles.tabText, !isAvailable && styles.tabTextActive]}
              >
                En Curso / Asignados
              </Text>
              <View style={[styles.tabCount, !isAvailable && styles.tabCountActive]}>
                <Text
                  style={[
                    styles.tabCountText,
                    !isAvailable && styles.tabCountTextActive,
                  ]}
                >
                  {activeOrders.length}
                </Text>
              </View>
            </Pressable>
          </View>
        </View>

        <View style={styles.radarRow}>
          <View style={styles.live}>
            <View
              style={[
                styles.liveDot,
                { backgroundColor: online ? GREEN : AMBER },
              ]}
            />
            <Text style={styles.liveText}>Actualización en tiempo real</Text>
          </View>

          <Pressable
            accessibilityRole="link"
            accessibilityLabel="Abrir el panel clásico de repartos"
            onPress={openPanel}
            style={({ pressed }) => [styles.classicLink, pressed && styles.pressed]}
          >
            <Text style={styles.classicText}>Panel clásico ↗</Text>
          </Pressable>
        </View>

        <View style={styles.sectionHead}>
          <View
            style={[styles.sectionDot, !isAvailable && styles.sectionDotGreen]}
          />
          <Text style={styles.sectionTitle}>
            {isAvailable
              ? 'Ofertas de Viaje para Aceptar'
              : 'Pedidos en Curso Asignados a Ti'}
          </Text>
          <Text style={styles.sectionHint}>
            {isAvailable ? 'Prioridad según cercanía' : 'Sigue las instrucciones de entrega'}
          </Text>
        </View>

        <View style={styles.list}>
          {isAvailable ? (
            offers.length > 0 ? (
              offers.map((offer) => (
                <DeliveryOfferCard
                  key={offer.id}
                  offer={offer}
                  busy={busyOfferId === offer.id}
                  vehicleIcon={vehicleIcon(stats.driver.vehicle_type)}
                  onTake={() => void takeOffer(offer)}
                />
              ))
            ) : (
              <EmptyState
                icon="📭"
                title="No hay pedidos disponibles por ahora"
                subtitle={
                  online
                    ? 'Mantenete conectado en esta pantalla. Las solicitudes de pizzerías y restaurantes aparecerán acá al instante.'
                    : 'Estás desconectado. Activá EN LÍNEA para volver a recibir ofertas de viaje.'
                }
              />
            )
          ) : activeOrders.length > 0 ? (
            activeOrders.map((order) => (
              <ActiveDeliveryCard
                key={order.id}
                order={order}
                busy={busyOrderId === order.id}
                onPickup={() => void pickupOrder(order)}
                onConfirmDelivery={(pin) => void confirmDelivery(order, pin)}
              />
            ))
          ) : (
            <EmptyState
              icon="🛵"
              title="Todavía no tenés pedidos en curso"
              subtitle="Cuando tomes una oferta de la pestaña anterior, el pedido aparecerá acá para que puedas gestionarlo y confirmar la entrega."
            />
          )}
        </View>
      </ScrollView>

      <Modal
        visible={goalOpen}
        transparent
        animationType="fade"
        onRequestClose={() => setGoalOpen(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>Meta de pedidos del día</Text>
            <Text style={styles.modalSubtitle}>
              ¿Cuántos pedidos querés entregar hoy?
            </Text>
            <TextInput
              keyboardType="number-pad"
              maxLength={2}
              onChangeText={setGoalDraft}
              placeholder="8"
              placeholderTextColor="#94a3b8"
              style={styles.modalInput}
              value={goalDraft}
            />
            <View style={styles.modalActions}>
              <Pressable
                accessibilityRole="button"
                onPress={() => setGoalOpen(false)}
                style={[styles.modalButton, styles.modalCancel]}
              >
                <Text style={styles.modalCancelText}>Cancelar</Text>
              </Pressable>
              <Pressable
                accessibilityRole="button"
                onPress={saveGoal}
                style={[styles.modalButton, styles.modalSave]}
              >
                <Text style={styles.modalSaveText}>Guardar</Text>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      <Modal
        visible={vehicleOpen}
        transparent
        animationType="slide"
        onRequestClose={() => setVehicleOpen(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>Cambiar vehículo</Text>
            <Text style={styles.modalSubtitle}>
              Elegí la unidad con la que vas a repartir.
            </Text>

            {stats.vehicle_types.map((option) => {
              const selected = option.value === stats.driver.vehicle_type;

              return (
                <Pressable
                  key={option.value}
                  accessibilityRole="button"
                  accessibilityState={{ selected }}
                  disabled={savingVehicle}
                  onPress={() => void chooseVehicle(option.value)}
                  style={({ pressed }) => [
                    styles.vehicleOption,
                    selected && styles.vehicleOptionActive,
                    pressed && styles.pressed,
                    savingVehicle && styles.busy,
                  ]}
                >
                  <Text style={styles.vehicleOptionText}>
                    {vehicleIcon(option.value)} {option.label}
                  </Text>
                  {selected ? <Text style={styles.vehicleCheck}>✓</Text> : null}
                </Pressable>
              );
            })}

            <Pressable
              accessibilityRole="button"
              disabled={savingVehicle}
              onPress={() => setVehicleOpen(false)}
              style={[styles.modalButton, styles.modalCancel, styles.modalClose]}
            >
              <Text style={styles.modalCancelText}>
                {savingVehicle ? 'Guardando…' : 'Cerrar'}
              </Text>
            </Pressable>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: '#f1f5f9',
    flex: 1,
  },
  scroll: {
    flex: 1,
  },
  content: {
    paddingBottom: spacing.xxl,
  },
  topBar: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
  },
  backButton: {
    alignSelf: 'flex-start',
    paddingBottom: spacing.xs,
    paddingTop: spacing.sm,
  },
  backText: {
    color: colors.brandDark,
    fontSize: 16,
    fontWeight: '600',
  },
  toolbar: {
    backgroundColor: colors.surface,
    borderBottomColor: '#e8edf3',
    borderBottomWidth: 1,
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  toolRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  toolLabel: {
    color: '#94a3b8',
    fontSize: 10.5,
    fontWeight: '800',
    letterSpacing: 0.8,
    textTransform: 'uppercase',
  },
  vehicleBadge: {
    alignItems: 'center',
    backgroundColor: NAVY,
    borderRadius: radius.pill,
    flexDirection: 'row',
    gap: 6,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  vehicleBadgeText: {
    color: colors.surface,
    fontSize: 12.5,
    fontWeight: '800',
  },
  plate: {
    backgroundColor: 'rgba(255,255,255,0.18)',
    borderColor: 'rgba(255,255,255,0.25)',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: 8,
    paddingVertical: 2,
  },
  plateText: {
    color: colors.surface,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 1,
  },
  vehicleEdit: {
    borderColor: '#cbd5e1',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: 7,
  },
  vehicleEditText: {
    color: '#64748b',
    fontSize: 11.5,
    fontWeight: '700',
  },
  vehicleSetup: {
    backgroundColor: '#FEF2F2',
    borderColor: '#FECACA',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  vehicleSetupText: {
    color: '#b91c1c',
    fontSize: 12.5,
    fontWeight: '800',
  },
  vehicleWarn: {
    color: '#b91c1c',
    flexShrink: 1,
    fontSize: 11.5,
    fontWeight: '600',
  },
  zoneInput: {
    backgroundColor: '#f8fafc',
    borderColor: '#e2e8f0',
    borderRadius: radius.pill,
    borderWidth: 1,
    color: colors.text,
    flex: 1,
    fontSize: 13,
    fontWeight: '600',
    minHeight: 42,
    minWidth: 150,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  radar: {
    alignItems: 'center',
    backgroundColor: '#FFF7ED',
    borderColor: '#FED7AA',
    borderRadius: radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    minHeight: 40,
    paddingHorizontal: spacing.lg,
    paddingVertical: 8,
  },
  radarOff: {
    backgroundColor: colors.surface,
    borderColor: '#e2e8f0',
  },
  radarText: {
    color: ACCENT,
    fontSize: 12.5,
    fontWeight: '800',
  },
  radarTextOff: {
    color: '#94a3b8',
  },
  gps: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 6,
  },
  gpsDot: {
    borderRadius: radius.pill,
    height: 8,
    width: 8,
  },
  dotGreen: {
    backgroundColor: GREEN,
  },
  dotAmber: {
    backgroundColor: AMBER,
  },
  gpsText: {
    color: '#94a3b8',
    fontSize: 12,
    fontWeight: '500',
  },
  errorWrap: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
  },
  tabsWrap: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
  },
  tabs: {
    alignSelf: 'flex-start',
    backgroundColor: '#e8edf3',
    borderColor: '#e2e8f0',
    borderRadius: radius.pill,
    borderWidth: 1,
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 4,
    padding: 5,
  },
  tab: {
    alignItems: 'center',
    borderRadius: radius.pill,
    flexDirection: 'row',
    gap: 8,
    minHeight: 40,
    paddingHorizontal: spacing.lg,
    paddingVertical: 8,
  },
  tabActive: {
    backgroundColor: colors.surface,
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.12,
    shadowRadius: 8,
    elevation: 2,
  },
  tabText: {
    color: '#64748b',
    flexShrink: 1,
    fontSize: 12.5,
    fontWeight: '700',
  },
  tabTextActive: {
    color: ACCENT,
  },
  tabCount: {
    alignItems: 'center',
    backgroundColor: '#cbd5e1',
    borderRadius: radius.pill,
    justifyContent: 'center',
    minHeight: 22,
    minWidth: 22,
    paddingHorizontal: 7,
  },
  tabCountActive: {
    backgroundColor: ACCENT,
  },
  tabCountText: {
    color: '#475569',
    fontSize: 11,
    fontWeight: '800',
  },
  tabCountTextActive: {
    color: colors.surface,
  },
  radarRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
  },
  live: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: 7,
  },
  liveDot: {
    borderRadius: radius.pill,
    height: 8,
    width: 8,
  },
  liveText: {
    color: '#94a3b8',
    fontSize: 12.5,
    fontWeight: '500',
  },
  classicLink: {
    marginLeft: 'auto',
    paddingVertical: 2,
  },
  classicText: {
    color: ACCENT,
    fontSize: 12,
    fontWeight: '700',
  },
  sectionHead: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.xl,
  },
  sectionDot: {
    backgroundColor: ACCENT,
    borderRadius: radius.pill,
    height: 12,
    width: 12,
  },
  sectionDotGreen: {
    backgroundColor: GREEN,
  },
  sectionTitle: {
    color: colors.text,
    flexShrink: 1,
    fontSize: 16.5,
    fontWeight: '800',
    letterSpacing: -0.3,
  },
  sectionHint: {
    color: '#94a3b8',
    fontSize: 11.5,
    fontWeight: '500',
    marginLeft: 'auto',
  },
  list: {
    gap: spacing.lg,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
  },
  empty: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#e2e8f0',
    borderRadius: 14,
    borderStyle: 'dashed',
    borderWidth: 2,
    paddingHorizontal: spacing.xl,
    paddingVertical: 40,
  },
  emptyIcon: {
    fontSize: 34,
    marginBottom: spacing.md,
  },
  emptyTitle: {
    color: colors.text,
    fontSize: 16.5,
    fontWeight: '800',
    textAlign: 'center',
  },
  emptySub: {
    color: '#94a3b8',
    fontSize: 13,
    lineHeight: 20,
    marginTop: 8,
    textAlign: 'center',
  },
  pressed: {
    opacity: 0.75,
  },
  busy: {
    opacity: 0.65,
  },
  fallback: {
    flex: 1,
    justifyContent: 'center',
    padding: spacing.xl,
  },
  fallbackTitle: {
    color: NAVY,
    fontSize: 20,
    fontWeight: '800',
    marginBottom: spacing.lg,
    textAlign: 'center',
  },
  primaryButton: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: radius.md,
    marginTop: spacing.lg,
    paddingVertical: spacing.md,
  },
  primaryButtonText: {
    color: colors.surface,
    fontSize: 15,
    fontWeight: '800',
  },
  secondaryLink: {
    alignSelf: 'center',
    marginTop: spacing.lg,
    padding: spacing.sm,
  },
  secondaryLinkText: {
    color: colors.brandDark,
    fontSize: 14,
    fontWeight: '600',
  },
  modalBackdrop: {
    backgroundColor: 'rgba(15,23,42,0.55)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.xl,
  },
  modalCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    gap: spacing.sm,
    padding: spacing.xl,
  },
  modalTitle: {
    color: NAVY,
    fontSize: 18,
    fontWeight: '800',
  },
  modalSubtitle: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 19,
    marginBottom: spacing.sm,
  },
  modalInput: {
    backgroundColor: '#f8fafc',
    borderColor: '#cbd5e1',
    borderRadius: radius.md,
    borderWidth: 1,
    color: colors.text,
    fontSize: 20,
    fontWeight: '800',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    textAlign: 'center',
  },
  modalActions: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  modalButton: {
    alignItems: 'center',
    borderRadius: radius.md,
    flex: 1,
    paddingVertical: spacing.md,
  },
  modalCancel: {
    backgroundColor: '#f1f5f9',
  },
  modalCancelText: {
    color: '#475569',
    fontSize: 14,
    fontWeight: '700',
  },
  modalSave: {
    backgroundColor: ACCENT,
  },
  modalSaveText: {
    color: colors.surface,
    fontSize: 14,
    fontWeight: '800',
  },
  vehicleOption: {
    alignItems: 'center',
    backgroundColor: '#f8fafc',
    borderColor: '#e2e8f0',
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    minHeight: 48,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  vehicleOptionActive: {
    backgroundColor: '#FFF7ED',
    borderColor: '#FED7AA',
  },
  vehicleOptionText: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
  vehicleCheck: {
    color: ACCENT,
    fontSize: 16,
    fontWeight: '800',
  },
  modalClose: {
    marginTop: spacing.sm,
  },
});
