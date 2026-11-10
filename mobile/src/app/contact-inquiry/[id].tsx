import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  Alert,
  Linking,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ApiError, api } from '@/api/client';
import { ComboField } from '@/components/ComboField';
import { ErrorBanner } from '@/components/ErrorBanner';
import { LoadingScreen } from '@/components/LoadingScreen';
import { PrimaryButton } from '@/components/PrimaryButton';
import {
  formatInquiryDate,
  INQUIRY_STATUS_OPTIONS,
  inquiryStatusLabel,
  inquiryStatusStyle,
  inquiryTypeLabel,
  inquiryTypeStyle,
  replyMailto,
} from '@/lib/contactInquiries';
import { colors, radius, spacing } from '@/theme';
import type { ContactInquiry, ContactInquiryStatus } from '@/types/api';

type Feedback = {
  kind: 'success' | 'error';
  text: string;
};

function selectableStatus(status: string): ContactInquiryStatus {
  return INQUIRY_STATUS_OPTIONS.some((option) => option.value === status)
    ? (status as ContactInquiryStatus)
    : 'new';
}

export default function ContactInquiryDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const inquiryId = Number(id);
  const invalidId = !Number.isInteger(inquiryId) || inquiryId <= 0;

  const [inquiry, setInquiry] = useState<ContactInquiry | null>(null);
  const [selectedStatus, setSelectedStatus] = useState<ContactInquiryStatus>('new');
  const [loading, setLoading] = useState(!invalidId);
  const [refreshing, setRefreshing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(
    invalidId ? 'Consulta inválida.' : null,
  );
  const [feedback, setFeedback] = useState<Feedback | null>(null);

  useEffect(() => {
    if (invalidId) {
      return;
    }

    let active = true;

    api
      .contactInquiry(inquiryId)
      .then((response) => {
        if (!active) {
          return;
        }

        const current = response.data.contact_inquiry;

        setInquiry(current);
        setSelectedStatus(selectableStatus(current.status));
        setError(null);
      })
      .catch((caught: unknown) => {
        if (!active) {
          return;
        }

        setError(
          caught instanceof ApiError
            ? caught.message
            : 'No pudimos cargar la consulta.',
        );
      })
      .finally(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, [inquiryId, invalidId]);

  async function handleRefresh() {
    if (invalidId) {
      return;
    }

    setRefreshing(true);

    try {
      const response = await api.contactInquiry(inquiryId);
      const current = response.data.contact_inquiry;

      setInquiry(current);
      setSelectedStatus(selectableStatus(current.status));
      setError(null);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos cargar la consulta.',
      );
    } finally {
      setRefreshing(false);
    }
  }

  async function handleSave() {
    if (!inquiry) {
      return;
    }

    setSaving(true);
    setFeedback(null);

    try {
      const response = await api.updateContactInquiryStatus(inquiry.id, selectedStatus);

      setInquiry(response.contact_inquiry);
      setSelectedStatus(selectableStatus(response.contact_inquiry.status));
      setFeedback({ kind: 'success', text: response.message });
    } catch (caught) {
      setFeedback({
        kind: 'error',
        text:
          caught instanceof ApiError
            ? caught.message
            : 'No pudimos actualizar el estado.',
      });
    } finally {
      setSaving(false);
    }
  }

  async function handleReply() {
    if (!inquiry) {
      return;
    }

    try {
      await Linking.openURL(replyMailto(inquiry.email, inquiry.inquiry_type));
    } catch {
      Alert.alert(
        'No pudimos abrir el correo',
        'Verificá que tengas una aplicación de correo configurada.',
      );
    }
  }

  if (loading) {
    return <LoadingScreen message="Cargando consulta…" />;
  }

  const statusStyle = inquiry ? inquiryStatusStyle(inquiry.status) : null;
  const typeStyle = inquiry ? inquiryTypeStyle(inquiry.inquiry_type) : null;
  const dirty = inquiry ? selectedStatus !== selectableStatus(inquiry.status) : false;

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
        <Text style={styles.title}>Detalle</Text>
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
        <ErrorBanner message={error} />

        {inquiry && statusStyle && typeStyle ? (
          <>
            <View style={styles.card}>
              <View style={styles.badges}>
                <View
                  style={[
                    styles.badge,
                    { backgroundColor: typeStyle.background, borderColor: typeStyle.border },
                  ]}
                >
                  <Text style={[styles.badgeText, { color: typeStyle.foreground }]}>
                    {inquiryTypeLabel(inquiry.inquiry_type)}
                  </Text>
                </View>
                <View
                  style={[
                    styles.badge,
                    { backgroundColor: statusStyle.background, borderColor: statusStyle.border },
                  ]}
                >
                  <Text style={[styles.badgeText, { color: statusStyle.foreground }]}>
                    {inquiryStatusLabel(inquiry.status)}
                  </Text>
                </View>
              </View>

              <View style={styles.fields}>
                <View style={styles.field}>
                  <Text style={styles.label}>Cliente</Text>
                  <Text style={styles.value}>{inquiry.full_name}</Text>
                </View>
                <View style={styles.field}>
                  <Text style={styles.label}>Correo</Text>
                  <Pressable accessibilityRole="link" onPress={() => void handleReply()}>
                    <Text style={styles.link}>{inquiry.email}</Text>
                  </Pressable>
                </View>
                <View style={styles.field}>
                  <Text style={styles.label}>Fecha</Text>
                  <Text style={styles.value}>{formatInquiryDate(inquiry.created_at)}</Text>
                </View>
              </View>

              <Text style={styles.label}>Mensaje</Text>
              <View style={styles.messageBox}>
                <Text style={styles.message}>{inquiry.message}</Text>
              </View>
            </View>

            <View style={styles.card}>
              <Text style={styles.sectionTitle}>Estado</Text>
              <Text style={styles.helper}>
                Cambiá el estado para marcar cómo va la respuesta a esta consulta.
              </Text>

              <ComboField
                label="Nuevo estado"
                value={selectedStatus}
                options={INQUIRY_STATUS_OPTIONS.map((option) => ({
                  value: option.value,
                  label: option.label,
                }))}
                onChange={setSelectedStatus}
              />

              {feedback ? (
                <Text
                  style={[
                    styles.feedback,
                    feedback.kind === 'success' ? styles.feedbackSuccess : styles.feedbackError,
                  ]}
                >
                  {feedback.text}
                </Text>
              ) : null}

              <PrimaryButton
                title="Guardar estado"
                onPress={() => void handleSave()}
                loading={saving}
                disabled={!dirty}
              />
              <View style={styles.buttonGap} />
              <PrimaryButton
                title="Responder por correo"
                variant="ghost"
                onPress={() => void handleReply()}
              />
            </View>
          </>
        ) : null}
      </ScrollView>
    </SafeAreaView>
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
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.xxl,
  },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.lg,
    padding: spacing.lg,
  },
  badges: {
    flexDirection: 'row',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  badge: {
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  fields: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    marginBottom: spacing.md,
  },
  field: {
    flexBasis: 200,
    flexGrow: 1,
  },
  label: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.5,
    marginBottom: 2,
    textTransform: 'uppercase',
  },
  value: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  link: {
    color: colors.brandDark,
    fontSize: 14,
    fontWeight: '600',
  },
  messageBox: {
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: radius.md,
    borderWidth: 1,
    padding: spacing.md,
  },
  message: {
    color: colors.text,
    fontSize: 14,
    lineHeight: 21,
  },
  sectionTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '800',
    marginBottom: spacing.xs,
  },
  helper: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 19,
    marginBottom: spacing.md,
  },
  statusRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginBottom: spacing.md,
  },
  statusOption: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  statusOptionActive: {
    backgroundColor: colors.brand,
    borderColor: colors.brand,
  },
  statusOptionText: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
  },
  statusOptionTextActive: {
    color: colors.surface,
  },
  feedback: {
    fontSize: 13,
    marginBottom: spacing.md,
  },
  feedbackSuccess: {
    color: colors.success,
  },
  feedbackError: {
    color: colors.danger,
  },
  buttonGap: {
    height: spacing.sm,
  },
});
