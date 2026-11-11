import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  View,
} from 'react-native';

import {
  ComboField,
  type ComboOption,
} from '@/components/ComboField';
import { colors, radius, spacing } from '@/theme';
import type { CampaignState } from '@/components/CampaignTabSelector';

const ACCENT = '#D24C19';

export type CampaignDraft = {
  is_enabled: boolean;
  days_inactive: string;
  day_of_week: number;
  discount_code: string;
  message_template: string;
};

type Notice = { text: string; error: boolean } | null;

type Props = {
  title: string;
  description: string;
  state: CampaignState;
  draft: CampaignDraft;
  /** Reactivación: muestra "Dias sin comprar". */
  requiresDays: boolean;
  /** Aviso de Envío: muestra el selector "Dia de la entrega". */
  requiresDay: boolean;
  dayOptions: ComboOption<number>[];
  /** Tokens que el mensaje no puede perder para este tipo de campaña. */
  requiredTokens: string[];
  saving: boolean;
  notice: Notice;
  onChange: (patch: Partial<CampaignDraft>) => void;
  onSave: () => void;
};

const BADGES: Record<
  CampaignState,
  { label: string; backgroundColor: string; color: string }
> = {
  active: { label: 'Activa', backgroundColor: '#d1fae5', color: '#047857' },
  inactive: { label: 'Inactiva', backgroundColor: '#f3f4f6', color: '#6b7280' },
  unconfigured: {
    label: 'Sin configurar',
    backgroundColor: '#fef3c7',
    color: '#b45309',
  },
};

/** Une los tokens como en la leyenda web: a, b y c. */
function tokensText(tokens: string[]): string {
  if (tokens.length === 0) {
    return '';
  }

  if (tokens.length === 1) {
    return tokens[0];
  }

  return `${tokens.slice(0, -1).join(', ')} y ${tokens[tokens.length - 1]}`;
}

/**
 * Tarjeta de una campaña de retención.
 *
 * Espeja la tarjeta que la web arma por campaña: badge de estado, switch
 * "Habilitar", campos propios (días o día de entrega), cupón, mensaje
 * multilínea con sus tokens obligatorios y botón cuadrado de guardar.
 */
export function CampaignFormCard({
  title,
  description,
  state,
  draft,
  requiresDays,
  requiresDay,
  dayOptions,
  requiredTokens,
  saving,
  notice,
  onChange,
  onSave,
}: Props) {
  const badge = BADGES[state];

  return (
    <View
      style={[
        styles.card,
        { borderLeftColor: draft.is_enabled ? ACCENT : '#d1d5db' },
      ]}
    >
      <View style={styles.head}>
        <Text style={styles.title}>{title}</Text>
        <Text style={styles.description}>{description}</Text>
      </View>

      <View style={styles.statusRow}>
        <View style={[styles.badge, { backgroundColor: badge.backgroundColor }]}>
          <Text style={[styles.badgeText, { color: badge.color }]}>{badge.label}</Text>
        </View>

        <View style={styles.switchRow}>
          <Switch
            accessibilityLabel={`Habilitar ${title}`}
            value={draft.is_enabled}
            onValueChange={(value) => onChange({ is_enabled: value })}
            disabled={saving}
            trackColor={{ false: '#cbd5e1', true: '#fdba74' }}
            thumbColor={draft.is_enabled ? ACCENT : '#f8fafc'}
          />
          <Text style={styles.switchLabel}>Habilitar</Text>
        </View>
      </View>

      {requiresDays ? (
        <View style={styles.field}>
          <Text style={styles.fieldLabel}>Dias sin comprar</Text>
          <TextInput
            value={draft.days_inactive}
            onChangeText={(value) =>
              onChange({ days_inactive: value.replace(/[^0-9]/g, '') })
            }
            keyboardType="number-pad"
            maxLength={3}
            placeholder="30"
            placeholderTextColor={colors.muted}
            style={styles.input}
            accessibilityLabel="Dias sin comprar"
          />
        </View>
      ) : null}

      {requiresDay ? (
        <ComboField<number>
          label="Dia de la entrega"
          title="Dia de la entrega"
          value={draft.day_of_week}
          options={dayOptions}
          onChange={(value) => onChange({ day_of_week: value })}
          disabled={saving}
        />
      ) : null}

      <View style={styles.field}>
        <Text style={styles.fieldLabel}>Cupon</Text>
        <TextInput
          value={draft.discount_code}
          onChangeText={(value) => onChange({ discount_code: value })}
          placeholder="Opcional"
          placeholderTextColor={colors.muted}
          autoCapitalize="characters"
          autoCorrect={false}
          maxLength={50}
          style={styles.input}
          accessibilityLabel="Cupon"
        />
      </View>

      <View style={styles.field}>
        <Text style={styles.fieldLabel}>Mensaje</Text>
        <TextInput
          value={draft.message_template}
          onChangeText={(value) => onChange({ message_template: value })}
          multiline
          maxLength={1000}
          textAlignVertical="top"
          style={[styles.input, styles.textarea]}
          accessibilityLabel="Mensaje"
        />
        <Text style={styles.hint}>
          {
            'Usá {nombre}, {comercio}, {dias} y {cupon}; se completan solos al enviar. '
          }
          {tokensText(requiredTokens)} son obligatorios y no se pueden borrar.
        </Text>
      </View>

      {notice ? (
        <View
          style={[
            styles.notice,
            notice.error ? styles.noticeError : styles.noticeOk,
          ]}
        >
          <Text
            style={[
              styles.noticeText,
              { color: notice.error ? '#991b1b' : '#166534' },
            ]}
          >
            {notice.text}
          </Text>
        </View>
      ) : null}

      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Guardar"
        accessibilityState={{ disabled: saving, busy: saving }}
        disabled={saving}
        onPress={onSave}
        style={[styles.saveButton, saving && styles.saveButtonDisabled]}
      >
        {saving ? (
          <ActivityIndicator color={colors.surface} size="small" />
        ) : (
          <Text style={styles.saveIcon}>✓</Text>
        )}
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderLeftWidth: 3,
    borderRadius: radius.md,
    borderWidth: 1,
    padding: spacing.lg,
  },
  head: {
    marginBottom: spacing.md,
  },
  title: {
    color: '#0c2a4d',
    fontSize: 16,
    fontWeight: '800',
  },
  description: {
    color: '#6b7280',
    fontSize: 12,
    lineHeight: 18,
    marginTop: 3,
  },
  statusRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  badge: {
    borderRadius: radius.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 5,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '800',
  },
  switchRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
  },
  switchLabel: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '600',
  },
  field: {
    marginBottom: spacing.md,
  },
  fieldLabel: {
    color: '#6b7280',
    fontSize: 11,
    fontWeight: '700',
    marginBottom: spacing.xs,
  },
  input: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: radius.sm,
    borderWidth: 1,
    color: '#334155',
    fontSize: 14,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  textarea: {
    minHeight: 96,
    paddingTop: spacing.sm,
  },
  hint: {
    color: '#9ca3af',
    fontSize: 11,
    lineHeight: 16,
    marginTop: spacing.xs,
  },
  notice: {
    borderRadius: radius.sm,
    borderWidth: 1,
    marginBottom: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  noticeOk: {
    backgroundColor: '#f0fdf4',
    borderColor: '#bbf7d0',
  },
  noticeError: {
    backgroundColor: '#fef2f2',
    borderColor: '#fecaca',
  },
  noticeText: {
    fontSize: 12,
    lineHeight: 18,
  },
  saveButton: {
    alignItems: 'center',
    alignSelf: 'flex-end',
    backgroundColor: colors.brand,
    borderRadius: radius.sm,
    height: 44,
    justifyContent: 'center',
    width: 44,
  },
  saveButtonDisabled: {
    opacity: 0.6,
  },
  saveIcon: {
    color: colors.surface,
    fontSize: 20,
    fontWeight: '800',
    lineHeight: 22,
  },
});
