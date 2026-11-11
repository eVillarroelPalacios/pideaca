import { Pressable, StyleSheet, Switch, Text, View } from 'react-native';

import { ComboField } from '@/components/ComboField';
import { initials } from '@/lib/orderTracking';
import { colors, radius, spacing } from '@/theme';
import type { TrustedDriverRow, TrustedDriversResponse } from '@/types/api';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

type Notice = { text: string; error: boolean } | null;

type Props = {
  data: TrustedDriversResponse | null;
  loading: boolean;
  toggling: boolean;
  adding: boolean;
  removingId: number | null;
  candidateId: number | null;
  notice: Notice;
  onCandidateChange: (value: number) => void;
  onCandidateClear: () => void;
  onToggle: (enabled: boolean) => void;
  onAdd: () => void;
  onRemove: (driver: TrustedDriverRow) => void;
};

/**
 * Card "Despacho de pedidos" con la modalidad de pool general / despacho
 * cerrado y la lista de confianza. Espeja la tarjeta de la vista web.
 */
export function DispatchTrustedCard({
  data,
  loading,
  toggling,
  adding,
  removingId,
  candidateId,
  notice,
  onCandidateChange,
  onCandidateClear,
  onToggle,
  onAdd,
  onRemove,
}: Props) {
  const enabled = data?.provider.trusted_drivers_enabled ?? false;
  const drivers = data?.drivers ?? [];
  const candidates = data?.candidates ?? [];
  const countLabel = `${drivers.length} ${drivers.length === 1 ? 'integrante' : 'integrantes'}`;

  const candidateOptions = candidates.map((candidate) => ({
    value: candidate.id,
    label: candidate.name || 'Repartidor',
    hint: candidate.vehicle_label ?? undefined,
  }));

  return (
    <View style={styles.card}>
      <Text style={styles.eyebrow}>Despacho de pedidos</Text>

      <View style={styles.modeRow}>
        <View
          style={[styles.dot, { backgroundColor: enabled ? ACCENT : '#94a3b8' }]}
        />
        <Text style={[styles.mode, enabled ? styles.modeClosed : styles.modeOpen]}>
          {enabled ? 'Solo repartidores de confianza' : 'Pool general'}
        </Text>
        <View style={styles.countBadge}>
          <Text style={styles.countText}>{countLabel}</Text>
        </View>
      </View>

      <Text style={styles.description}>
        Con la opción activa, tus pedidos solo los ven los repartidores de tu lista. No se liberan
        solos por tiempo: esperan a tu lista hasta que desactives la modalidad o envíes el pedido al
        pool general.
      </Text>

      <View style={styles.switchRow}>
        <Switch
          accessibilityLabel="Despachar solo a los repartidores de confianza"
          value={enabled}
          onValueChange={onToggle}
          disabled={loading || toggling}
          trackColor={{ false: '#cbd5e1', true: '#fdba74' }}
          thumbColor={enabled ? ACCENT : '#f8fafc'}
        />
        <Text style={styles.switchLabel}>Solo repartidores de confianza</Text>
      </View>

      {notice ? (
        <View style={[styles.notice, notice.error ? styles.noticeError : styles.noticeOk]}>
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

      <View style={styles.listSection}>
        <View style={styles.listHeader}>
          <Text style={styles.listTitle}>Lista de confianza</Text>
          <Text style={styles.listCount}>
            {loading ? 'Cargando…' : drivers.length ? 'lista activa' : 'sin integrantes'}
          </Text>
        </View>

        {loading && !data ? (
          <Text style={styles.empty}>Cargando repartidores…</Text>
        ) : drivers.length === 0 ? (
          <Text style={styles.empty}>
            Todavía no agregaste repartidores. Sumá al menos uno para poder activar la modalidad.
          </Text>
        ) : (
          drivers.map((driver) => (
            <View key={driver.id} style={styles.driverRow}>
              <View style={styles.avatar}>
                <Text style={styles.avatarText}>{initials(driver.name)}</Text>
              </View>
              <View style={styles.driverInfo}>
                <Text style={styles.driverName}>{driver.name || 'Repartidor'}</Text>
                {driver.vehicle_label || driver.phone ? (
                  <Text style={styles.driverMeta}>
                    {[driver.vehicle_label, driver.phone].filter(Boolean).join(' · ')}
                  </Text>
                ) : null}
              </View>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel={`Quitar a ${driver.name ?? 'este repartidor'} de la lista`}
                disabled={removingId === driver.id}
                onPress={() => onRemove(driver)}
                style={[styles.removeButton, removingId === driver.id && styles.disabled]}
              >
                <Text style={styles.removeText}>🗑</Text>
              </Pressable>
            </View>
          ))
        )}

        <View style={styles.addRow}>
          <View style={styles.combo}>
            <ComboField<number>
              label="Agregar repartidor"
              title="Repartidor para la lista de confianza"
              value={candidateId}
              options={candidateOptions}
              onChange={onCandidateChange}
              allowEmpty
              emptyLabel={
                candidates.length ? 'Seleccioná un repartidor…' : 'No hay repartidores disponibles'
              }
              onClear={() => onCandidateClear()}
              placeholder={
                candidates.length ? 'Seleccioná un repartidor…' : 'No hay repartidores disponibles'
              }
              disabled={loading || adding || candidates.length === 0}
            />
          </View>

          <Pressable
            accessibilityRole="button"
            disabled={loading || adding || candidateId === null || candidates.length === 0}
            onPress={onAdd}
            style={[
              styles.addButton,
              (loading || adding || candidateId === null || candidates.length === 0) &&
                styles.disabled,
            ]}
          >
            <Text style={styles.addButtonText}>
              {adding ? 'Agregando…' : '+ Agregar a la lista'}
            </Text>
          </Pressable>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: '#e5e7eb',
    borderRadius: radius.md,
    borderWidth: 1,
    padding: spacing.lg,
  },
  eyebrow: {
    color: '#94a3b8',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.9,
    textTransform: 'uppercase',
  },
  modeRow: {
    alignItems: 'center',
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  dot: {
    borderRadius: radius.pill,
    height: 8,
    width: 8,
  },
  mode: {
    fontSize: 14,
    fontWeight: '700',
  },
  modeOpen: {
    color: NAVY,
  },
  modeClosed: {
    color: ACCENT,
  },
  countBadge: {
    backgroundColor: '#f8fafc',
    borderColor: '#e5e7eb',
    borderRadius: radius.pill,
    borderWidth: 1,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
  },
  countText: {
    color: '#475569',
    fontSize: 11,
    fontWeight: '600',
  },
  description: {
    color: '#64748b',
    fontSize: 12,
    lineHeight: 18,
    marginTop: spacing.sm,
  },
  switchRow: {
    alignItems: 'center',
    alignSelf: 'flex-start',
    flexDirection: 'row',
    gap: spacing.sm,
    marginTop: spacing.md,
  },
  switchLabel: {
    color: '#334155',
    fontSize: 12,
    fontWeight: '700',
  },
  notice: {
    borderRadius: radius.sm,
    borderWidth: 1,
    marginTop: spacing.md,
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
    fontWeight: '600',
    lineHeight: 17,
  },
  listSection: {
    borderTopColor: '#f1f5f9',
    borderTopWidth: 1,
    marginTop: spacing.md,
    paddingTop: spacing.md,
  },
  listHeader: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  listTitle: {
    color: '#64748b',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.9,
    textTransform: 'uppercase',
  },
  listCount: {
    color: '#94a3b8',
    fontSize: 11.5,
    fontWeight: '600',
  },
  empty: {
    color: '#94a3b8',
    fontSize: 12.5,
    paddingVertical: spacing.sm,
  },
  driverRow: {
    alignItems: 'center',
    borderBottomColor: '#f1f5f9',
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    paddingVertical: spacing.sm,
  },
  avatar: {
    alignItems: 'center',
    backgroundColor: '#e0f2fe',
    borderRadius: radius.pill,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  avatarText: {
    color: '#0369a1',
    fontSize: 11,
    fontWeight: '800',
  },
  driverInfo: {
    flex: 1,
    minWidth: 0,
  },
  driverName: {
    color: '#0f172a',
    fontSize: 13,
    fontWeight: '700',
  },
  driverMeta: {
    color: '#64748b',
    fontSize: 11.5,
    marginTop: 1,
  },
  removeButton: {
    alignItems: 'center',
    borderColor: '#e5e7eb',
    borderRadius: radius.sm,
    borderWidth: 1,
    height: 32,
    justifyContent: 'center',
    width: 32,
  },
  removeText: {
    fontSize: 13,
  },
  addRow: {
    marginTop: spacing.sm,
  },
  combo: {
    marginBottom: spacing.xs,
  },
  addButton: {
    alignItems: 'center',
    backgroundColor: ACCENT,
    borderRadius: radius.sm,
    justifyContent: 'center',
    minHeight: 42,
    paddingHorizontal: spacing.md,
  },
  addButtonText: {
    color: colors.surface,
    fontSize: 12,
    fontWeight: '700',
  },
  disabled: {
    opacity: 0.5,
  },
});
