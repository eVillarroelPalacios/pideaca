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
import { colors, radius, spacing } from '@/theme';
import type { ServiceOrderItem } from '@/types/api';

const TYPE_LABELS: Record<string, string> = {
  EMERGENCY: '🚨 Urgencia',
  SCHEDULED: '📅 Cita',
  QUOTE: '🧾 Presupuesto',
};

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

export default function RequestDetailScreen() {
  const { id, created } = useLocalSearchParams<{ id: string; created?: string }>();
  const orderId = Number(id);
  const invalidId = !Number.isInteger(orderId) || orderId <= 0;

  const [order, setOrder] = useState<ServiceOrderItem | null>(null);
  const [loading, setLoading] = useState(!invalidId);
  const [refreshing, setRefreshing] = useState(false);
  const [cancelling, setCancelling] = useState(false);
  const [actingOnProposal, setActingOnProposal] = useState<number | null>(null);
  const [confirming, setConfirming] = useState(false);
  const [ratingStars, setRatingStars] = useState(5);
  const [ratingComment, setRatingComment] = useState('');
  const [ratingSubmitting, setRatingSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(
    invalidId ? 'Solicitud inválida.' : null,
  );

  useEffect(() => {
    if (invalidId) {
      return;
    }

    let active = true;

    api
      .serviceOrder(orderId)
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
              : 'No pudimos cargar la solicitud.',
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
      const response = await api.serviceOrder(orderId);
      setOrder(response.data.order);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar la solicitud.',
      );
    } finally {
      setRefreshing(false);
    }
  }

  function confirmCancel() {
    Alert.alert(
      'Cancelar solicitud',
      '¿Seguro querés cancelar esta solicitud? Los fondos retenidos se devuelven.',
      [
        { text: 'Volver', style: 'cancel' },
        { text: 'Sí, cancelar', style: 'destructive', onPress: () => void doCancel() },
      ],
    );
  }

  async function doAcceptQuote(quoteId: number) {
    if (invalidId || !order) {
      return;
    }

    setActingOnProposal(quoteId);
    setError(null);

    try {
      const response = await api.acceptQuote(order.id, quoteId);
      setOrder(response.data.order);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos aceptar la propuesta.',
      );
    } finally {
      setActingOnProposal(null);
    }
  }

  function confirmAcceptQuote(quoteId: number, price: string) {
    Alert.alert(
      'Aceptar propuesta',
      `¿Confirmás el trabajo por ${price}? Los demás profesionales quedan descartados.`,
      [
        { text: 'Volver', style: 'cancel' },
        { text: 'Sí, aceptar', onPress: () => void doAcceptQuote(quoteId) },
      ],
    );
  }

  async function doRejectQuote(quoteId: number) {
    if (invalidId || !order) {
      return;
    }

    setActingOnProposal(quoteId);
    setError(null);

    try {
      const response = await api.rejectQuote(order.id, quoteId);
      setOrder(response.data.order);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos rechazar la propuesta.',
      );
    } finally {
      setActingOnProposal(null);
    }
  }

  async function handleRate() {
    if (invalidId || !order) {
      return;
    }

    setRatingSubmitting(true);
    setError(null);

    try {
      const response = await api.rateServiceOrder(order.id, ratingStars, ratingComment);
      setOrder(response.data.order);
      setRatingComment('');
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos registrar tu calificación.',
      );
    } finally {
      setRatingSubmitting(false);
    }
  }

  async function handleApproveEstimate() {
    if (invalidId || !order) {
      return;
    }

    setConfirming(true);
    setError(null);

    try {
      const response = await api.approveEstimate(order.id);
      setOrder(response.data.order);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos aprobar el estimado.',
      );
    } finally {
      setConfirming(false);
    }
  }

  function confirmRelease() {
    Alert.alert(
      'Liberar pago',
      'Confirmás que el trabajo está bien y liberás el pago al prestador.',
      [
        { text: 'Volver', style: 'cancel' },
        { text: 'Sí, liberar', onPress: () => void doConfirmRelease() },
      ],
    );
  }

  async function doConfirmRelease() {
    if (invalidId || !order) {
      return;
    }

    setConfirming(true);
    setError(null);

    try {
      const response = await api.confirmRelease(order.id);
      setOrder(response.data.order);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos liberar el pago.',
      );
    } finally {
      setConfirming(false);
    }
  }

  async function doCancel() {
    if (invalidId || !order) {
      return;
    }

    setCancelling(true);

    try {
      const response = await api.cancelServiceOrder(order.id);
      setOrder(response.data.order);
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cancelar la solicitud.',
      );
    } finally {
      setCancelling(false);
    }
  }

  if (loading) {
    return <LoadingScreen message="Cargando solicitud…" />;
  }

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
        <Text style={styles.title}>Solicitud #{order?.id ?? id}</Text>
        <View style={styles.headerSpacer} />
      </View>

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={handleRefresh}
            tintColor={colors.brand}
            colors={[colors.brand]}
          />
        }
      >
        {created === '1' ? (
          <View style={styles.successBanner}>
            <Text style={styles.successText}>
              ¡Solicitud enviada! Te avisamos cuando un profesional la tome.
            </Text>
          </View>
        ) : null}

        <ErrorBanner message={error} />

        {!order ? (
          <View style={styles.empty}>
            <Text style={styles.emptyText}>
              No encontramos esta solicitud. Puede que haya sido eliminada.
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

            {order.merchant ? (
              <View style={styles.card}>
                <Text style={styles.cardTitle}>Prestador</Text>
                <Text style={styles.merchantName}>
                  {order.merchant.is_verified ? '✓ ' : ''}
                  {order.merchant.business_name}
                </Text>
                {order.merchant.phone || order.merchant.whatsapp ? (
                  <Text style={styles.merchantContact}>
                    {[order.merchant.phone, order.merchant.whatsapp]
                      .filter(Boolean)
                      .join(' · ')}
                  </Text>
                ) : null}
              </View>
            ) : (
              <View style={styles.poolCard}>
                <Text style={styles.poolText}>
                  🔍 Buscando profesional del rubro… Te avisamos cuando alguien la tome.
                </Text>
              </View>
            )}

            {order.photo_url ? (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>Foto del problema</Text>
                <Image source={{ uri: order.photo_url }} style={styles.photo} />
              </View>
            ) : null}

            {order.attachments.length > 0 ? (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>Fotos del trabajo</Text>
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
              <Text style={styles.cardTitle}>Detalle</Text>
              <Row label="Descripción" value={order.description ?? '—'} />
              {order.scheduled_at ? (
                <Row label="Fecha y hora" value={formatDateTime(order.scheduled_at)} />
              ) : null}
              {order.address ? <Row label="Dirección" value={order.address} /> : null}
              {order.payment_method ? (
                <Row label="Medio de pago" value={order.payment_method} />
              ) : null}
              {money(order.total_amount) ? (
                <Row label="Total" value={money(order.total_amount) as string} />
              ) : null}
              <Row label="Creada" value={formatDateTime(order.created_at)} last />
            </View>

            {order.proposals.length > 0 ? (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>
                  Propuestas ({order.proposals.length})
                </Text>
                {order.proposals.map((proposal) => {
                  const price = money(proposal.proposed_price) ?? 'A convenir';
                  const busy = actingOnProposal === proposal.id;

                  return (
                    <View key={proposal.id} style={styles.proposal}>
                      <Text style={styles.proposalPrice}>{price}</Text>
                      <Text style={styles.proposalMeta}>
                        {proposal.provider?.business_name ?? 'Profesional'}
                        {proposal.estimated_time ? ` · ${proposal.estimated_time}` : ''}
                      </Text>
                      {proposal.details ? (
                        <Text style={styles.proposalDetails}>{proposal.details}</Text>
                      ) : null}
                      <View style={styles.proposalActions}>
                        <Pressable
                          accessibilityRole="button"
                          disabled={busy}
                          onPress={() => confirmAcceptQuote(proposal.id, price)}
                          style={[styles.acceptButton, busy && styles.actionDisabled]}
                        >
                          <Text style={styles.acceptText}>
                            {busy ? '…' : 'Aceptar'}
                          </Text>
                        </Pressable>
                        <Pressable
                          accessibilityRole="button"
                          disabled={busy}
                          onPress={() => void doRejectQuote(proposal.id)}
                          style={[styles.rejectButton, busy && styles.actionDisabled]}
                        >
                          <Text style={styles.rejectText}>
                            {busy ? '…' : 'Rechazar'}
                          </Text>
                        </Pressable>
                      </View>
                    </View>
                  );
                })}
              </View>
            ) : null}

            {(order.order_type === 'EMERGENCY' || order.order_type === 'SCHEDULED') &&
            order.status === 'QUOTED' &&
            Number(order.additional_cost) > 0 ? (
              <View style={styles.estimateCard}>
                <Text style={styles.estimateTitle}>
                  El prestador estimó {money(order.additional_cost)}
                </Text>
                {order.estimate_details ? (
                  <Text style={styles.estimateDetails}>{order.estimate_details}</Text>
                ) : null}
                <PrimaryButton
                  title="Aprobar estimado"
                  onPress={handleApproveEstimate}
                  loading={confirming}
                  disabled={confirming}
                />
              </View>
            ) : null}

            {order.status === 'COMPLETED' && order.escrow_status === 'HOLDING' ? (
              <View style={styles.estimateCard}>
                <Text style={styles.estimateTitle}>
                  ¿El trabajo quedó bien?
                </Text>
                <Text style={styles.estimateDetails}>
                  Al confirmar se libera el pago al prestador{money(order.total_amount) ? ` (${money(order.total_amount)})` : ''}.
                </Text>
                <PrimaryButton
                  title="Confirmar y liberar pago"
                  onPress={confirmRelease}
                  loading={confirming}
                  disabled={confirming}
                />
              </View>
            ) : null}

            {order.status === 'COMPLETED' ? (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>Calificación</Text>
                {order.customer_rated ? (
                  <View style={styles.ratedCard}>
                    <Text style={styles.ratedText}>
                      Calificaste este trabajo. ¡Gracias!
                    </Text>
                  </View>
                ) : (
                  <View style={styles.rateCard}>
                    <Text style={styles.rateHint}>
                      ¿Cómo te fue con {order.merchant?.business_name ?? 'el prestador'}?
                    </Text>
                    <View style={styles.stars}>
                      {[1, 2, 3, 4, 5].map((stars) => (
                        <Pressable
                          key={stars}
                          accessibilityRole="radio"
                          accessibilityState={{ selected: ratingStars === stars }}
                          onPress={() => setRatingStars(stars)}
                          style={styles.star}
                        >
                          <Text
                            style={[
                              styles.starText,
                              ratingStars >= stars && styles.starTextActive,
                            ]}
                          >
                            ★
                          </Text>
                        </Pressable>
                      ))}
                    </View>
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
                      onPress={handleRate}
                      loading={ratingSubmitting}
                      disabled={ratingSubmitting}
                    />
                  </View>
                )}
              </View>
            ) : null}

            {order.cancellable ? (
              <Pressable
                accessibilityRole="button"
                disabled={cancelling}
                onPress={confirmCancel}
                style={styles.cancelButton}
              >
                <Text style={styles.cancelText}>
                  {cancelling ? 'Cancelando…' : 'Cancelar solicitud'}
                </Text>
              </Pressable>
            ) : null}
          </>
        )}
      </ScrollView>
    </SafeAreaView>
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
  successBanner: {
    backgroundColor: colors.successSoft,
    borderColor: '#bbf7d0',
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.md,
  },
  successText: {
    color: colors.success,
    fontSize: 14,
    fontWeight: '600',
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
  merchantName: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '700',
  },
  merchantContact: {
    color: colors.muted,
    fontSize: 14,
    marginTop: 2,
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
  proposal: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    marginBottom: spacing.sm,
    padding: spacing.md,
  },
  proposalPrice: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
  },
  proposalMeta: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
  },
  proposalDetails: {
    color: colors.text,
    fontSize: 14,
    marginTop: spacing.xs,
  },
  estimateCard: {
    backgroundColor: colors.surface,
    borderColor: colors.brand,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.lg,
  },
  estimateTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
    marginBottom: spacing.xs,
  },
  estimateDetails: {
    color: colors.muted,
    fontSize: 14,
    marginBottom: spacing.md,
  },
  proposalActions: {
    flexDirection: 'row',
    gap: spacing.md,
    marginTop: spacing.md,
  },
  acceptButton: {
    alignItems: 'center',
    backgroundColor: colors.success,
    borderRadius: radius.md,
    flex: 1,
    paddingVertical: spacing.md,
  },
  acceptText: {
    color: colors.surface,
    fontSize: 15,
    fontWeight: '800',
  },
  rejectButton: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    flex: 1,
    paddingVertical: spacing.md,
  },
  rejectText: {
    color: colors.danger,
    fontSize: 15,
    fontWeight: '700',
  },
  actionDisabled: {
    opacity: 0.6,
  },
  ratedCard: {
    backgroundColor: colors.successSoft,
    borderRadius: radius.md,
    padding: spacing.lg,
  },
  ratedText: {
    color: colors.success,
    fontSize: 15,
    fontWeight: '600',
    textAlign: 'center',
  },
  rateCard: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    padding: spacing.lg,
  },
  rateHint: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '600',
    marginBottom: spacing.md,
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
  cancelButton: {
    alignItems: 'center',
    marginTop: spacing.md,
    paddingVertical: spacing.md,
  },
  cancelText: {
    color: colors.danger,
    fontSize: 15,
    fontWeight: '700',
  },
});
