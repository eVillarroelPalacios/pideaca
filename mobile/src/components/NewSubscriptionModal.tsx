import { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ComboField, type ComboOption } from '@/components/ComboField';
import { DatePickerModal } from '@/components/DatePickerModal';
import { money } from '@/lib/money';
import { colors, radius, spacing } from '@/theme';
import type { MarketAddress, MarketPlan, MarketShop, MarketSubscription } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type Props = {
  /** Comercio fijado desde la tarjeta de Comercios. */
  providerId?: number;
  providerName?: string;
  onClose: () => void;
  onCreated: (subscription: MarketSubscription) => void;
};

const FREQUENCY_LABEL: Record<string, string> = {
  DAILY: 'cada día',
  WEEKLY: 'cada semana',
  BIWEEKLY: 'cada dos semanas',
  MONTHLY: 'cada mes',
};

const DAY_OPTIONS: ComboOption<number>[] = [
  { value: 1, label: 'Lunes' },
  { value: 2, label: 'Martes' },
  { value: 3, label: 'Miércoles' },
  { value: 4, label: 'Jueves' },
  { value: 5, label: 'Viernes' },
  { value: 6, label: 'Sábado' },
  { value: 7, label: 'Domingo' },
];

const PAYMENT_OPTIONS: ComboOption<string>[] = [
  { value: 'cash', label: 'Efectivo en la entrega' },
  { value: 'transfer', label: 'Transferencia' },
];

/** Horarios cada 30 minutos entre 08:00 y 22:00. */
const TIME_OPTIONS: ComboOption<string>[] = Array.from({ length: 29 }, (_, index) => {
  const minutes = 8 * 60 + index * 30;
  const value = `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(
    minutes % 60,
  ).padStart(2, '0')}`;

  return { value, label: value };
});

function displayDate(ymd: string): string {
  if (!ymd) {
    return 'Elegir fecha';
  }

  const [y, m, d] = ymd.split('-');

  return y && m && d ? `${d}/${m}/${y}` : ymd;
}

function errorMessage(caught: unknown): string {
  if (caught instanceof ApiError) {
    if (caught.errors) {
      const first = Object.values(caught.errors)[0];

      if (first && first.length > 0) {
        return first[0];
      }
    }

    return caught.message;
  }

  return 'No pudimos crear la suscripción.';
}

/**
 * Alta de suscripción del cliente, con los mismos campos que el modal web:
 * comercio, plan, dirección, pago, primera entrega, día y horario preferidos.
 */
export function NewSubscriptionModal({
  providerId,
  providerName,
  onClose,
  onCreated,
}: Props) {
  const [providers, setProviders] = useState<MarketShop[]>([]);
  const [addresses, setAddresses] = useState<MarketAddress[]>([]);
  const [plans, setPlans] = useState<MarketPlan[]>([]);
  const [selectedProviderId, setSelectedProviderId] = useState<number | null>(
    providerId ?? null,
  );
  const [selectedPlanId, setSelectedPlanId] = useState<number | null>(null);
  const [addressId, setAddressId] = useState<number | null>(null);
  const [paymentMethod, setPaymentMethod] = useState<string>('cash');
  const [nextDeliveryDate, setNextDeliveryDate] = useState('');
  const [preferredDay, setPreferredDay] = useState<number | null>(null);
  const [preferredTime, setPreferredTime] = useState<string | null>(null);
  const [flavors, setFlavors] = useState<Record<number, string>>({});
  const [datePickerVisible, setDatePickerVisible] = useState(false);
  const [loadingLists, setLoadingLists] = useState(true);
  const [loadingPlans, setLoadingPlans] = useState(Boolean(providerId));
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    Promise.allSettled([
      api.marketShops({ only_with_plans: true }),
      api.marketAddresses(),
    ])
      .then(([shopsResult, addressResult]) => {
        if (!active) {
          return;
        }

        if (shopsResult.status === 'fulfilled') {
          setProviders(shopsResult.value.providers);
        }

        if (addressResult.status === 'fulfilled') {
          setAddresses(addressResult.value.addresses);
          const primary =
            addressResult.value.addresses.find((item) => item.is_primary) ??
            addressResult.value.addresses[0];

          if (primary) {
            setAddressId(primary.id);
          }
        }
      })
      .finally(() => {
        if (active) {
          setLoadingLists(false);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  useEffect(() => {
    if (!selectedProviderId) {
      return;
    }

    let active = true;

    api
      .marketPlans(selectedProviderId)
      .then((response) => {
        if (active) {
          setPlans(response.plans);
        }
      })
      .catch(() => {
        if (active) {
          setPlans([]);
        }
      })
      .finally(() => {
        if (active) {
          setLoadingPlans(false);
        }
      });

    return () => {
      active = false;
    };
  }, [selectedProviderId]);

  const selectedPlan = useMemo(
    () => plans.find((plan) => plan.id === selectedPlanId) ?? null,
    [plans, selectedPlanId],
  );

  const providerOptions = useMemo(() => {
    const options: ComboOption<number>[] = providers.map((provider) => ({
      value: provider.id,
      label: provider.business_name,
    }));

    if (
      providerId &&
      !options.some((option) => option.value === providerId)
    ) {
      options.unshift({
        value: providerId,
        label: providerName || 'Comercio',
      });
    }

    return options;
  }, [providers, providerId, providerName]);

  const planOptions = useMemo<ComboOption<number>[]>(
    () =>
      plans.map((plan) => {
        const noSlots =
          plan.capacity !== null &&
          plan.capacity !== undefined &&
          (plan.available_slots ?? 0) <= 0;

        return {
          value: plan.id,
          label: `${plan.title} — ${money(plan.charge_amount)}${
            noSlots ? ' (sin cupos)' : ''
          }`,
          hint: `${FREQUENCY_LABEL[plan.frequency] ?? plan.frequency}${
            plan.discount_amount > 0 ? ` · ahorrás ${money(plan.discount_amount)}` : ''
          }`,
          disabled: noSlots,
        };
      }),
    [plans],
  );

  const addressOptions = useMemo<ComboOption<number>[]>(
    () =>
      addresses.map((address) => ({
        value: address.id,
        label: `${address.formatted}${address.is_primary ? ' (principal)' : ''}`,
      })),
    [addresses],
  );

  const totalAllowed = selectedPlan
    ? selectedPlan.items.reduce((sum, item) => sum + item.quantity, 0)
    : 0;
  const totalChosen = Object.values(flavors).reduce(
    (sum, value) => sum + Math.max(0, Number.parseInt(value, 10) || 0),
    0,
  );

  function handleProviderChange(nextId: number) {
    setLoadingPlans(true);
    setPlans([]);
    setSelectedPlanId(null);
    setFlavors({});
    setSelectedProviderId(nextId);
  }

  function handlePlanChange(nextId: number) {
    const plan = plans.find((item) => item.id === nextId) ?? null;
    setSelectedPlanId(nextId);

    if (plan && plan.items.length > 1) {
      const next: Record<number, string> = {};

      plan.items.forEach((item) => {
        next[item.product_id] = String(item.quantity);
      });

      setFlavors(next);
    } else {
      setFlavors({});
    }
  }

  async function handleSubmit() {
    if (submitting) {
      return;
    }

    setError(null);

    if (!selectedPlanId) {
      setError('Elegí un plan.');
      return;
    }

    if (!addressId) {
      setError('Elegí una dirección de entrega.');
      return;
    }

    if (!nextDeliveryDate) {
      setError('Elegí la fecha de la primera entrega.');
      return;
    }

    let items: { product_id: number; quantity: number }[] | undefined;

    if (selectedPlan && selectedPlan.items.length > 1) {
      items = Object.entries(flavors)
        .map(([productId, quantity]) => ({
          product_id: Number(productId),
          quantity: Math.max(0, Number.parseInt(quantity, 10) || 0),
        }))
        .filter((item) => item.quantity > 0);

      if (totalChosen !== totalAllowed) {
        setError(
          `La suma tiene que ser ${totalAllowed} productos; ahora es ${totalChosen}.`,
        );
        return;
      }
    }

    setSubmitting(true);

    try {
      const response = await api.createMarketSubscription({
        subscription_plan_id: selectedPlanId,
        delivery_address_id: addressId,
        payment_method: paymentMethod,
        next_delivery_date: nextDeliveryDate,
        preferred_delivery_day: preferredDay,
        preferred_delivery_time: preferredTime,
        items,
      });

      onCreated(response.subscription);
    } catch (caught) {
      setError(errorMessage(caught));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Modal visible transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <SafeAreaView style={styles.shell} edges={['top', 'bottom']}>
          <View style={styles.header}>
            <Text style={styles.headerTitle}>Nueva suscripción</Text>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Cerrar"
              onPress={onClose}
              style={styles.closeButton}
            >
              <Text style={styles.closeText}>✕</Text>
            </Pressable>
          </View>

          {loadingLists ? (
            <View style={styles.loading}>
              <ActivityIndicator color={ACCENT} />
              <Text style={styles.loadingText}>Cargando comercios…</Text>
            </View>
          ) : (
            <ScrollView
              contentContainerStyle={styles.body}
              keyboardShouldPersistTaps="handled"
            >
              <ComboField<number>
                label="Comercio"
                value={selectedProviderId}
                options={providerOptions}
                onChange={handleProviderChange}
                placeholder={
                  providerOptions.length
                    ? 'Elegí un comercio'
                    : 'No hay comercios con planes disponibles'
                }
                disabled={Boolean(providerId)}
                searchable
              />

              <ComboField<number>
                label="Plan"
                value={selectedPlanId}
                options={planOptions}
                onChange={handlePlanChange}
                placeholder={
                  loadingPlans
                    ? 'Cargando planes…'
                    : planOptions.length
                      ? 'Elegí un plan'
                      : 'Sin planes disponibles'
                }
                disabled={loadingPlans || planOptions.length === 0}
              />

              {selectedPlan ? (
                <View style={styles.planDetail}>
                  <Text style={styles.planTitle}>
                    {selectedPlan.title} ·{' '}
                    {FREQUENCY_LABEL[selectedPlan.frequency] ?? selectedPlan.frequency}
                  </Text>
                  {selectedPlan.description ? (
                    <Text style={styles.planText}>{selectedPlan.description}</Text>
                  ) : null}
                  <Text style={styles.planText}>
                    {selectedPlan.items
                      .map((item) => `${item.name ?? 'Producto'} x${item.quantity}`)
                      .join(' · ')}
                  </Text>
                  {selectedPlan.discount_amount > 0 ? (
                    <Text style={styles.planDiscount}>
                      Descuento aplicado: -{money(selectedPlan.discount_amount)}
                    </Text>
                  ) : null}
                </View>
              ) : null}

              {selectedPlan && selectedPlan.items.length > 1 ? (
                <View style={styles.flavors}>
                  <Text style={styles.flavorsLabel}>
                    Distribución de sabores (opcional)
                  </Text>
                  {selectedPlan.items.map((item) => (
                    <View key={item.product_id} style={styles.flavorRow}>
                      <Text style={styles.flavorName}>{item.name ?? 'Producto'}</Text>
                      <TextInput
                        value={flavors[item.product_id] ?? ''}
                        onChangeText={(text) =>
                          setFlavors((current) => ({
                            ...current,
                            [item.product_id]: text.replace(/[^0-9]/g, ''),
                          }))
                        }
                        keyboardType="number-pad"
                        style={styles.flavorInput}
                      />
                    </View>
                  ))}
                  <Text
                    style={[
                      styles.flavorsHint,
                      totalChosen === totalAllowed
                        ? styles.hintOk
                        : totalChosen > totalAllowed
                          ? styles.hintError
                          : styles.hintPending,
                    ]}
                  >
                    {totalChosen === totalAllowed
                      ? `Distribución completa (${totalChosen} productos).`
                      : totalChosen > totalAllowed
                        ? `Te pasaste por ${totalChosen - totalAllowed} productos.`
                        : `Te faltan ${totalAllowed - totalChosen} productos por distribuir.`}
                  </Text>
                </View>
              ) : null}

              <ComboField<number>
                label="Dirección de entrega"
                value={addressId}
                options={addressOptions}
                onChange={setAddressId}
                placeholder={
                  addressOptions.length
                    ? 'Elegí una dirección'
                    : 'No tenés direcciones cargadas'
                }
                disabled={addressOptions.length === 0}
              />

              <ComboField<string>
                label="Forma de pago"
                value={paymentMethod}
                options={PAYMENT_OPTIONS}
                onChange={setPaymentMethod}
              />

              <View style={styles.dateField}>
                <Text style={styles.fieldLabel}>Primera entrega</Text>
                <Pressable
                  accessibilityRole="button"
                  onPress={() => setDatePickerVisible(true)}
                  style={styles.dateInput}
                >
                  <Text
                    style={[
                      styles.dateValue,
                      !nextDeliveryDate && styles.datePlaceholder,
                    ]}
                  >
                    {displayDate(nextDeliveryDate)}
                  </Text>
                  <Text style={styles.dateIcon}>📅</Text>
                </Pressable>
              </View>

              <ComboField<number>
                label="Día preferido"
                value={preferredDay}
                options={DAY_OPTIONS}
                onChange={setPreferredDay}
                allowEmpty
                emptyLabel="Sin preferencia"
                onClear={() => setPreferredDay(null)}
              />

              <ComboField<string>
                label="Horario preferido"
                value={preferredTime}
                options={TIME_OPTIONS}
                onChange={setPreferredTime}
                allowEmpty
                emptyLabel="Sin preferencia"
                onClear={() => setPreferredTime(null)}
              />

              {error ? <Text style={styles.error}>{error}</Text> : null}

              <View style={styles.footer}>
                <Pressable
                  accessibilityRole="button"
                  disabled={submitting}
                  onPress={() => void handleSubmit()}
                  style={[styles.confirm, submitting && styles.buttonDisabled]}
                >
                  <Text style={styles.confirmText}>
                    {submitting ? 'Creando…' : 'Confirmar suscripción'}
                  </Text>
                </Pressable>
                <Pressable
                  accessibilityRole="button"
                  disabled={submitting}
                  onPress={onClose}
                  style={styles.cancel}
                >
                  <Text style={styles.cancelText}>Cancelar</Text>
                </Pressable>
              </View>
            </ScrollView>
          )}
        </SafeAreaView>
      </View>

      <DatePickerModal
        visible={datePickerVisible}
        title="Primera entrega"
        initial={nextDeliveryDate}
        onPick={(ymd) => {
          setNextDeliveryDate(ymd);
          setDatePickerVisible(false);
        }}
        onClear={() => {
          setNextDeliveryDate('');
          setDatePickerVisible(false);
        }}
        onClose={() => setDatePickerVisible(false)}
      />
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    backgroundColor: 'rgba(15,23,42,0.6)',
    flex: 1,
  },
  shell: {
    backgroundColor: colors.surface,
    flex: 1,
  },
  header: {
    alignItems: 'center',
    backgroundColor: NAVY,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: 14,
  },
  headerTitle: {
    color: colors.surface,
    fontSize: 16,
    fontWeight: '800',
  },
  closeButton: {
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.12)',
    borderRadius: 8,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  closeText: {
    color: colors.surface,
    fontSize: 18,
  },
  loading: {
    alignItems: 'center',
    flex: 1,
    gap: spacing.sm,
    justifyContent: 'center',
  },
  loadingText: {
    color: colors.muted,
    fontSize: 13,
  },
  body: {
    padding: spacing.lg,
    paddingBottom: spacing.xxl,
  },
  planDetail: {
    backgroundColor: '#f9fafb',
    borderColor: colors.border,
    borderRadius: radius.sm,
    borderWidth: 1,
    marginBottom: spacing.lg,
    marginTop: -spacing.sm,
    padding: spacing.md,
  },
  planTitle: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '700',
    marginBottom: 4,
  },
  planText: {
    color: '#374151',
    fontSize: 13,
    marginTop: 2,
  },
  planDiscount: {
    color: '#047857',
    fontSize: 13,
    marginTop: 6,
  },
  flavors: {
    marginBottom: spacing.lg,
    marginTop: -spacing.sm,
  },
  flavorsLabel: {
    color: colors.muted,
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 6,
  },
  flavorRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.sm,
  },
  flavorName: {
    color: '#374151',
    flex: 1,
    fontSize: 13,
  },
  flavorInput: {
    borderColor: '#d1d5db',
    borderRadius: 4,
    borderWidth: 1,
    color: colors.text,
    fontSize: 13,
    paddingHorizontal: 10,
    paddingVertical: 7,
    textAlign: 'center',
    width: 70,
  },
  flavorsHint: {
    fontSize: 12,
    fontWeight: '700',
    marginTop: 4,
  },
  hintOk: {
    color: '#047857',
  },
  hintPending: {
    color: '#b45309',
  },
  hintError: {
    color: '#b91c1c',
  },
  dateField: {
    marginBottom: spacing.lg,
  },
  fieldLabel: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
    marginBottom: spacing.sm,
  },
  dateInput: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: 12,
  },
  dateValue: {
    color: colors.text,
    fontSize: 14,
  },
  datePlaceholder: {
    color: colors.muted,
  },
  dateIcon: {
    fontSize: 15,
  },
  error: {
    backgroundColor: colors.dangerSoft,
    borderColor: '#fecaca',
    borderRadius: radius.sm,
    borderWidth: 1,
    color: colors.danger,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: 9,
  },
  footer: {
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  confirm: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: 6,
    paddingVertical: 13,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  confirmText: {
    color: colors.surface,
    fontSize: 14,
    fontWeight: '800',
  },
  cancel: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 6,
    borderWidth: 1,
    paddingVertical: 12,
  },
  cancelText: {
    color: '#374151',
    fontSize: 13,
    fontWeight: '600',
  },
});
