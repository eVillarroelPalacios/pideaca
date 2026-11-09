import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  Alert,
  Image,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { appendPhoto, pickSingleImage, takePhoto, type LocalPhoto } from '@/lib/media';
import { colors, radius, spacing } from '@/theme';
import type { ServiceOrderItem } from '@/types/api';

const TYPE_LABELS: Record<string, string> = {
  EMERGENCY: '🚨 Urgencia',
  SCHEDULED: '📅 Cita',
  QUOTE: '🧾 Presupuesto',
};

type FormKind = 'propose' | 'estimate' | 'complete' | 'rate' | null;

function money(value: number | string | null): string | null {
  if (value === null || value === undefined) {
    return null;
  }

  const amount = Number(value);

  if (Number.isNaN(amount) || amount <= 0) {
    return null;
  }

  return `$${amount.toLocaleString('es-AR', { maximumFractionDigits: 0 })}`;
}

function formatDateTime(iso: string | null): string {
  if (!iso) {
    return '—';
  }

  const date = new Date(iso);

  if (Number.isNaN(date.getTime())) {
    return '—';
  }

  return date.toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export default function ProviderOrderDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = Number(id);
  const invalidId = !Number.isInteger(orderId) || orderId <= 0;

  const [order, setOrder] = useState<ServiceOrderItem | null>(null);
  const [loading, setLoading] = useState(!invalidId);
  const [refreshing, setRefreshing] = useState(false);
  const [acting, setActing] = useState(false);
  const [error, setError] = useState<string | null>(
    invalidId ? 'Solicitud inválida.' : null,
  );
  const [openForm, setOpenForm] = useState<FormKind>(null);

  // Formularios de las acciones.
  const [proposedPrice, setProposedPrice] = useState('');
  const [estimatedTime, setEstimatedTime] = useState('');
  const [proposalDetails, setProposalDetails] = useState('');
  const [estimateAmount, setEstimateAmount] = useState('');
  const [estimateDetails, setEstimateDetails] = useState('');
  const [completionPhoto, setCompletionPhoto] = useState<LocalPhoto | null>(null);
  const [photoSource, setPhotoSource] = useState<'gallery' | 'camera' | null>(null);
  const [ratingStars, setRatingStars] = useState(5);
  const [ratingComment, setRatingComment] = useState('');

  useEffect(() => {
    if (invalidId) {
      return;
    }

    let active = true;

    api
      .providerOrder(orderId)
      .then((response) => {
        if (active) {
          setOrder(response.data.order);
          setError(null);
        }
      })
      .catch((caught: unknown) => {
        if (active) {
          setError(
            caught instanceof ApiError
              ? caught.message
              : 'No pudimos cargar el trabajo.',
          );
        }
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, [orderId, invalidId]);

  async function handleRefresh() {
    if (invalidId) {
      return;
    }

    setRefreshing(true);

    try {
      const response = await api.providerOrder(orderId);
      setOrder(response.data.order);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError ? caught.message : 'No pudimos cargar el trabajo.',
      );
    } finally {
      setRefreshing(false);
    }
  }

  async function runAction(action: () => Promise<ServiceOrderItem>) {
    if (invalidId) {
      return;
    }

    setActing(true);
    setError(null);

    try {
      const updated = await action();
      setOrder(updated);
      setOpenForm(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError ? caught.message : 'No se pudo completar la acción.',
      );
    } finally {
      setActing(false);
    }
  }

  async function pickCompletionPhoto(from: 'gallery' | 'camera') {
    setPhotoSource(from);
    const picked = from === 'camera' ? await takePhoto() : await pickSingleImage();
    setPhotoSource(null);

    if (picked) {
      setCompletionPhoto(picked);
    }
  }

  function parseAmount(raw: string): number | null {
    const amount = Number(raw.replace(',', '.'));

    if (!Number.isFinite(amount) || amount <= 0) {
      return null;
    }

    return Math.round(amount * 100) / 100;
  }

  if (loading) {
    return <LoadingScreen message="Cargando trabajo…" />;
  }

  const isPool = order?.merchant_id === null || order?.merchant_id === undefined;
  const canAccept =
    order?.status === 'REQUESTED' &&
    (order.order_type === 'EMERGENCY' || order.order_type === 'SCHEDULED');
  const canPropose =
    order?.order_type === 'QUOTE' &&
    (order.status === 'REQUESTED' || order.status === 'QUOTED') &&
    isPool;
  const canDispatch =
    !isPool &&
    order?.status === 'ACCEPTED' &&
    (order?.order_type === 'SCHEDULED' || order?.order_type === 'QUOTE');
  const canEstimate =
    !isPool &&
    (order?.status === 'ACCEPTED' ||
      order?.status === 'PROVIDER_DISPATCHED' ||
      order?.status === 'QUOTED') &&
    (order?.order_type === 'EMERGENCY' || order?.order_type === 'SCHEDULED');
  const canComplete =
    !isPool &&
    (order?.status === 'ACCEPTED' ||
      order?.status === 'PROVIDER_DISPATCHED' ||
      order?.status === 'IN_PROGRESS');
  const canRateCustomer = !isPool && order?.status === 'COMPLETED' && !order?.provider_rated;

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <View style={styles.header}>
        <Pressable
          accessibilityRole="button"
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <Text style={styles.backText}>‹ Atrás</Text>
        </Pressable>
        <Text style={styles.title}>Trabajo #{order?.id ?? id}</Text>
        <View style={styles.headerSpacer} />
      </View>

      <ScrollView
        contentContainerStyle={styles.content}
        keyboardShouldPersistTaps="handled"
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={colors.brand}
            colors={[colors.brand]}
          />
        }
      >
        <ErrorBanner message={error} />

        {!order ? (
          <View style={styles.empty}>
            <Text style={styles.emptyText}>
              No encontramos este trabajo. Puede que ya no esté disponible.
            </Text>
          </View>
        ) : (
          <>
            <Text style={styles.type}>
              {TYPE_LABELS[order.order_type] ?? order.order_type}
            </Text>

            <View style={styles.statusRow}>
              <View style={styles.statusBadge}>
                <Text style={styles.statusText}>{order.status_label}</Text>
              </View>
              <Text style={styles.subgroup}>{order.sub_group.description}</Text>
            </View>

            {isPool ? (
              <View style={styles.poolCard}>
                <Text style={styles.poolText}>
                  🔍 Está en el pool de tu rubro: todavía nadie la tomó.
                </Text>
              </View>
            ) : null}

            <View style={styles.card}>
              <Text style={styles.cardTitle}>Cliente y ubicación</Text>
              <Row label="Dirección" value={order.address ?? '—'} />
              <Row
                label="Contacto"
                value={
                  [order.customer_id ? `Cliente #${order.customer_id}` : null]
                    .filter(Boolean)
                    .join(' · ') || '—'
                }
              />
              {order.latitude !== null && order.longitude !== null ? (
                <Row
                  label="GPS"
                  value={`${Number(order.latitude).toFixed(5)}, ${Number(order.longitude).toFixed(5)}`}
                />
              ) : null}
              {order.scheduled_at ? (
                <Row label="Fecha y hora" value={formatDateTime(order.scheduled_at)} />
              ) : null}
              <Row label="Descripción" value={order.description ?? '—'} last />
            </View>

            {order.photo_url ? (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>Foto del problema</Text>
                <Image source={{ uri: order.photo_url }} style={styles.photo} />
              </View>
            ) : null}

            {order.attachments.length > 0 ? (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>Adjuntos del cliente</Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                  {order.attachments
                    .filter((attachment) => attachment.url)
                    .map((attachment, index) => (
                      <Image
                        key={`${attachment.url}-${index}`}
                        source={{ uri: attachment.url as string }}
                        style={styles.photoSmall}
                      />
                    ))}
                </ScrollView>
              </View>
            ) : null}

            <View style={styles.card}>
              <Text style={styles.cardTitle}>Montos</Text>
              <Row label="Total" value={money(order.total_amount) ?? 'A convenir'} />
              <Row label="Seña" value={money(order.deposit_amount) ?? '—'} />
              <Row
                label="Medio de pago"
                value={order.payment_method ?? '—'}
                last
              />
            </View>

            {order.estimate_details || order.additional_cost ? (
              <View style={styles.card}>
                <Text style={styles.cardTitle}>Estimado cargado</Text>
                <Row
                  label="Adicional"
                  value={money(order.additional_cost) ?? '—'}
                />
                <Row label="Detalle" value={order.estimate_details ?? '—'} last />
              </View>
            ) : null}

            {canAccept ? (
              <PrimaryButton
                title={order.order_type === 'SCHEDULED' ? 'Aceptar cita' : 'Aceptar y salir'}
                onPress={() =>
                  Alert.alert(
                    'Aceptar trabajo',
                    isPool
                      ? 'La solicitud queda a tu nombre. ¿Confirmás?'
                      : 'Confirmás la solicitud directa. ¿Seguís?',
                    [
                      { text: 'Volver', style: 'cancel' },
                      {
                        text: 'Sí, aceptar',
                        onPress: () =>
                          void runAction(() =>
                            api.acceptProviderOrder(order.id).then((r) => r.data.order),
                          ),
                      },
                    ],
                  )
                }
                loading={acting}
                disabled={acting}
              />
            ) : null}

            {canDispatch ? (
              <View style={styles.actionGap}>
                <PrimaryButton
                  title="En camino"
                  onPress={() =>
                    void runAction(() =>
                      api.dispatchProviderOrder(order.id).then((r) => r.data.order),
                    )
                  }
                  loading={acting}
                  disabled={acting}
                />
              </View>
            ) : null}

            {canEstimate && openForm !== 'estimate' ? (
              <View style={styles.actionGap}>
                <PrimaryButton
                  title="Cargar estimado"
                  onPress={() => setOpenForm('estimate')}
                  variant="ghost"
                />
              </View>
            ) : null}

            {canPropose && openForm !== 'propose' ? (
              <View style={styles.actionGap}>
                <PrimaryButton
                  title="Enviar propuesta"
                  onPress={() => setOpenForm('propose')}
                />
              </View>
            ) : null}

            {canComplete && openForm !== 'complete' ? (
              <View style={styles.actionGap}>
                <PrimaryButton
                  title="Marcar completado"
                  onPress={() => setOpenForm('complete')}
                  variant="ghost"
                />
              </View>
            ) : null}

            {openForm === 'propose' ? (
              <View style={styles.formCard}>
                <Text style={styles.formTitle}>Tu propuesta</Text>
                <TextField
                  label="Precio *"
                  value={proposedPrice}
                  onChangeText={setProposedPrice}
                  placeholder="15000"
                  keyboardType="numeric"
                />
                <TextField
                  label="Tiempo estimado *"
                  value={estimatedTime}
                  onChangeText={setEstimatedTime}
                  placeholder="2 horas"
                />
                <TextField
                  label="Detalle (opcional)"
                  value={proposalDetails}
                  onChangeText={setProposalDetails}
                  placeholder="Qué incluye"
                  multiline
                  numberOfLines={2}
                />
                <PrimaryButton
                  title="Enviar propuesta"
                  onPress={() => {
                    const price = parseAmount(proposedPrice);

                    if (price === null) {
                      setError('Ingresá un precio válido mayor a cero.');
                      return;
                    }

                    if (!estimatedTime.trim()) {
                      setError('Indicá el tiempo estimado.');
                      return;
                    }

                    void runAction(() =>
                      api
                        .proposeProviderOrder(order.id, price, estimatedTime.trim(), proposalDetails)
                        .then((r) => r.data.order),
                    );
                  }}
                  loading={acting}
                  disabled={acting}
                />
                <Pressable
                  accessibilityRole="button"
                  onPress={() => setOpenForm(null)}
                  style={styles.cancelForm}
                >
                  <Text style={styles.cancelFormText}>Cerrar</Text>
                </Pressable>
              </View>
            ) : null}

            {openForm === 'estimate' ? (
              <View style={styles.formCard}>
                <Text style={styles.formTitle}>Estimado en el lugar</Text>
                <TextField
                  label="Costo adicional *"
                  value={estimateAmount}
                  onChangeText={setEstimateAmount}
                  placeholder="2500"
                  keyboardType="numeric"
                />
                <TextField
                  label="Detalle (opcional)"
                  value={estimateDetails}
                  onChangeText={setEstimateDetails}
                  placeholder="Repuesto, mano de obra…"
                  multiline
                  numberOfLines={2}
                />
                <PrimaryButton
                  title="Enviar estimado"
                  onPress={() => {
                    const amount = parseAmount(estimateAmount);

                    if (amount === null) {
                      setError('Ingresá un costo válido mayor a cero.');
                      return;
                    }

                    void runAction(() =>
                      api
                        .estimateProviderOrder(order.id, amount, estimateDetails)
                        .then((r) => r.data.order),
                    );
                  }}
                  loading={acting}
                  disabled={acting}
                />
                <Pressable
                  accessibilityRole="button"
                  onPress={() => setOpenForm(null)}
                  style={styles.cancelForm}
                >
                  <Text style={styles.cancelFormText}>Cerrar</Text>
                </Pressable>
              </View>
            ) : null}

            {openForm === 'complete' ? (
              <View style={styles.formCard}>
                <Text style={styles.formTitle}>Foto del trabajo terminado *</Text>
                {completionPhoto ? (
                  <Image source={{ uri: completionPhoto.uri }} style={styles.photo} />
                ) : (
                  <View style={styles.photoButtons}>
                    <Pressable
                      accessibilityRole="button"
                      disabled={photoSource !== null}
                      onPress={() => void pickCompletionPhoto('camera')}
                      style={styles.photoButton}
                    >
                      <Text style={styles.photoButtonText}>📷 Cámara</Text>
                    </Pressable>
                    <Pressable
                      accessibilityRole="button"
                      disabled={photoSource !== null}
                      onPress={() => void pickCompletionPhoto('gallery')}
                      style={styles.photoButton}
                    >
                      <Text style={styles.photoButtonText}>🖼️ Galería</Text>
                    </Pressable>
                  </View>
                )}
                <View style={styles.actionGap}>
                  <PrimaryButton
                    title="Completar trabajo"
                    onPress={() => {
                      if (!completionPhoto) {
                        setError('Necesitás la foto del trabajo terminado.');
                        return;
                      }

                      const form = new FormData();
                      appendPhoto(form, 'photo', completionPhoto);

                      void runAction(() =>
                        api
                          .completeProviderOrder(order.id, form)
                          .then((r) => r.data.order),
                      );
                    }}
                    loading={acting}
                    disabled={acting || !completionPhoto}
                  />
                </View>
                <Pressable
                  accessibilityRole="button"
                  onPress={() => {
                    setOpenForm(null);
                    setCompletionPhoto(null);
                  }}
                  style={styles.cancelForm}
                >
                  <Text style={styles.cancelFormText}>Cerrar</Text>
                </Pressable>
              </View>
            ) : null}

            {canRateCustomer ? (
              <View style={styles.formCard}>
                <Text style={styles.formTitle}>Calificá al cliente</Text>
                <RateStars value={ratingStars} onChange={setRatingStars} />
                <TextField
                  label="Comentario (opcional)"
                  value={ratingComment}
                  onChangeText={setRatingComment}
                  placeholder="Contanos cómo te fue"
                  multiline
                  numberOfLines={2}
                />
                <PrimaryButton
                  title="Enviar calificación"
                  onPress={() =>
                    void runAction(() =>
                      api
                        .rateServiceOrder(order.id, ratingStars, ratingComment)
                        .then((r) => r.data.order),
                    )
                  }
                  loading={acting}
                  disabled={acting}
                />
              </View>
            ) : null}

            {order.completion_photo_url ? (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>Foto de finalización</Text>
                <Image source={{ uri: order.completion_photo_url }} style={styles.photo} />
              </View>
            ) : null}
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

function RateStars({ value, onChange }: { value: number; onChange: (stars: number) => void }) {
  return (
    <View style={styles.stars}>
      {[1, 2, 3, 4, 5].map((stars) => (
        <Pressable
          key={stars}
          accessibilityRole="radio"
          accessibilityState={{ selected: value === stars }}
          onPress={() => onChange(stars)}
          style={styles.star}
        >
          <Text style={[styles.starText, value >= stars && styles.starTextActive]}>
            ★
          </Text>
        </Pressable>
      ))}
    </View>
  );
}

function Row({ label, value, last = false }: { label: string; value: string; last?: boolean }) {
  return (
    <View style={[styles.row, last && styles.rowLast]}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.background,
    flex: 1,
  },
  header: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  backButton: {
    minWidth: 70,
  },
  backText: {
    color: colors.brandDark,
    fontSize: 16,
    fontWeight: '600',
  },
  title: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
  },
  headerSpacer: {
    minWidth: 70,
  },
  content: {
    padding: spacing.lg,
    paddingBottom: spacing.xxl,
  },
  empty: {
    alignItems: 'center',
    paddingVertical: spacing.xxl,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 15,
    textAlign: 'center',
  },
  type: {
    color: colors.text,
    fontSize: 24,
    fontWeight: '800',
    marginBottom: spacing.sm,
  },
  statusRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.lg,
  },
  statusBadge: {
    backgroundColor: colors.brandSoft,
    borderRadius: radius.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 4,
  },
  statusText: {
    color: colors.brandDark,
    fontSize: 13,
    fontWeight: '700',
  },
  subgroup: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '600',
  },
  poolCard: {
    backgroundColor: colors.brandSoft,
    borderColor: '#fed7aa',
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.lg,
  },
  poolText: {
    color: '#9a3412',
    fontSize: 14,
    lineHeight: 20,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.lg,
  },
  cardTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
    marginBottom: spacing.sm,
  },
  row: {
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: spacing.sm,
  },
  rowLast: {
    borderBottomWidth: 0,
  },
  rowLabel: {
    color: colors.muted,
    flex: 1,
    fontSize: 14,
    marginRight: spacing.md,
  },
  rowValue: {
    color: colors.text,
    flex: 2,
    fontSize: 14,
    fontWeight: '600',
    textAlign: 'right',
  },
  section: {
    marginBottom: spacing.lg,
  },
  sectionTitle: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '800',
    marginBottom: spacing.md,
  },
  photo: {
    borderRadius: radius.md,
    height: 220,
    width: '100%',
  },
  photoSmall: {
    borderRadius: radius.md,
    height: 140,
    marginRight: spacing.md,
    width: 180,
  },
  actionGap: {
    marginTop: spacing.md,
  },
  formCard: {
    backgroundColor: colors.surface,
    borderColor: colors.brand,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginTop: spacing.lg,
    padding: spacing.lg,
  },
  formTitle: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '800',
    marginBottom: spacing.md,
  },
  cancelForm: {
    alignItems: 'center',
    paddingVertical: spacing.md,
  },
  cancelFormText: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '600',
  },
  photoButtons: {
    flexDirection: 'row',
    gap: spacing.md,
    marginBottom: spacing.md,
  },
  photoButton: {
    alignItems: 'center',
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    flex: 1,
    paddingVertical: spacing.lg,
  },
  photoButtonText: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
  },
  stars: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  star: {
    padding: spacing.xs,
  },
  starText: {
    color: colors.border,
    fontSize: 36,
  },
  starTextActive: {
    color: colors.brand,
  },
  hint: {
    color: colors.muted,
    fontSize: 13,
    marginTop: spacing.sm,
  },
});
