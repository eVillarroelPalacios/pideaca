import { Modal, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { colors, radius, spacing } from '@/theme';

const NAVY = '#0c2a4d';
const ACCENT = '#D24C19';

/** Radio máximo: 51 representa "50+ km". */
export const MAX_RADIUS = 51;

export function radiusLabel(km: number): string {
  return km >= MAX_RADIUS ? '50+ km' : `${km} km`;
}

export type ShopFilterMode = 'distance' | 'all';

type Props = {
  visible: boolean;
  mode: ShopFilterMode;
  radius: number;
  location: string;
  locating: boolean;
  geoStatus: string;
  onChangeMode: (mode: ShopFilterMode) => void;
  onChangeRadius: (radius: number) => void;
  onChangeLocation: (location: string) => void;
  onLocate: () => void;
  onApply: () => void;
  onClose: () => void;
};

/**
 * Modal de filtro de comercios por ubicación y distancia.
 *
 * Es controlado: el borrador vive en la pantalla que lo abre, así el
 * componente no guarda estado ni dispara efectos propios.
 */
export function ShopFilterModal({
  visible,
  mode,
  radius,
  location,
  locating,
  geoStatus,
  onChangeMode,
  onChangeRadius,
  onChangeLocation,
  onLocate,
  onApply,
  onClose,
}: Props) {
  const distance = mode === 'distance';
  const fill = Math.min(100, ((radius - 1) / (MAX_RADIUS - 1)) * 100);

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onClose}
      statusBarTranslucent
    >
      <View style={styles.backdrop}>
        <View style={styles.card}>
          <View style={styles.header}>
            <View style={styles.headerText}>
              <Text style={styles.title}>Filtrar comercios</Text>
              <Text style={styles.subtitle}>Ubicación y distancia</Text>
            </View>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="Cerrar filtros"
              onPress={onClose}
              style={styles.close}
            >
              <Text style={styles.closeText}>✕</Text>
            </Pressable>
          </View>

          <View style={styles.body}>
            <Pressable
              accessibilityRole="radio"
              accessibilityState={{ selected: distance }}
              onPress={() => onChangeMode('distance')}
              style={[styles.radioCard, distance && styles.radioCardSelected]}
            >
              <View style={[styles.dot, distance && styles.dotSelected]} />
              <View style={styles.radioText}>
                <Text style={styles.radioTitle}>Por distancia</Text>
                <Text style={styles.radioHint}>
                  Mostrar comercios dentro del radio elegido.
                </Text>
              </View>
            </Pressable>

            <View style={[styles.sliderWrap, !distance && styles.sliderDisabled]}>
              <View style={styles.sliderLabelRow}>
                <Text style={styles.sliderLabel}>Radio</Text>
                <Text style={styles.sliderValue}>{radiusLabel(radius)}</Text>
              </View>
              <View style={styles.sliderRow}>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Achicar radio"
                  disabled={!distance || radius <= 1}
                  onPress={() => onChangeRadius(Math.max(1, radius - 1))}
                  style={[styles.stepper, (!distance || radius <= 1) && styles.stepperDisabled]}
                >
                  <Text style={styles.stepperText}>−</Text>
                </Pressable>
                <View style={styles.track}>
                  <View style={[styles.trackFill, { width: `${fill}%` }]} />
                  <View style={[styles.thumb, { left: `${fill}%` }]} />
                </View>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Agrandar radio"
                  disabled={!distance || radius >= MAX_RADIUS}
                  onPress={() => onChangeRadius(Math.min(MAX_RADIUS, radius + 1))}
                  style={[
                    styles.stepper,
                    (!distance || radius >= MAX_RADIUS) && styles.stepperDisabled,
                  ]}
                >
                  <Text style={styles.stepperText}>+</Text>
                </Pressable>
              </View>
              <View style={styles.sliderEnds}>
                <Text style={styles.sliderEnd}>1 km</Text>
                <Text style={styles.sliderEnd}>50+ km</Text>
              </View>
            </View>

            <Pressable
              accessibilityRole="radio"
              accessibilityState={{ selected: !distance }}
              onPress={() => onChangeMode('all')}
              style={[styles.radioCard, !distance && styles.radioCardSelected]}
            >
              <View style={[styles.dot, !distance && styles.dotSelected]} />
              <View style={styles.radioText}>
                <Text style={styles.radioTitle}>Buscar en todas partes</Text>
                <Text style={styles.radioHint}>Sin límite de distancia.</Text>
              </View>
            </Pressable>

            <Text style={styles.locationLabel}>UBICACIÓN DE REFERENCIA (OPCIONAL)</Text>
            <View style={styles.locationRow}>
              <TextInput
                value={location}
                onChangeText={onChangeLocation}
                placeholder="Ej. Av. Siempre Viva 123"
                placeholderTextColor={colors.muted}
                autoCapitalize="none"
                autoCorrect={false}
                style={styles.locationInput}
              />
              <Pressable
                accessibilityRole="button"
                accessibilityLabel="Usar mi ubicación actual"
                disabled={locating}
                onPress={onLocate}
                style={styles.geoButton}
              >
                <Text style={styles.geoButtonText}>◎</Text>
              </Pressable>
            </View>
            {geoStatus ? <Text style={styles.geoStatus}>{geoStatus}</Text> : null}
          </View>

          <View style={styles.footer}>
            <Pressable accessibilityRole="button" onPress={onClose} style={styles.cancel}>
              <Text style={styles.cancelText}>Cancelar</Text>
            </Pressable>
            <Pressable accessibilityRole="button" onPress={onApply} style={styles.apply}>
              <Text style={styles.applyText}>Aplicar</Text>
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    alignItems: 'center',
    backgroundColor: 'rgba(7,26,48,0.55)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    maxWidth: 430,
    overflow: 'hidden',
    width: '100%',
  },
  header: {
    alignItems: 'center',
    backgroundColor: NAVY,
    flexDirection: 'row',
    gap: spacing.md,
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingVertical: 14,
  },
  headerText: {
    flex: 1,
  },
  title: {
    color: colors.surface,
    fontSize: 15,
    fontWeight: '700',
  },
  subtitle: {
    color: '#b9c7db',
    fontSize: 12,
    marginTop: 3,
  },
  close: {
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
    lineHeight: 20,
  },
  body: {
    padding: spacing.lg,
  },
  radioCard: {
    alignItems: 'flex-start',
    borderColor: colors.border,
    borderRadius: 9,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 10,
    marginBottom: 10,
    padding: spacing.md,
  },
  radioCardSelected: {
    backgroundColor: '#fff7f2',
    borderColor: ACCENT,
  },
  dot: {
    borderColor: '#cbd5e1',
    borderRadius: 999,
    borderWidth: 2,
    height: 16,
    marginTop: 2,
    width: 16,
  },
  dotSelected: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  radioText: {
    flex: 1,
  },
  radioTitle: {
    color: NAVY,
    fontSize: 14,
    fontWeight: '700',
  },
  radioHint: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 2,
  },
  sliderWrap: {
    paddingBottom: 14,
    paddingLeft: 34,
    paddingRight: 4,
  },
  sliderDisabled: {
    opacity: 0.45,
  },
  sliderLabelRow: {
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  sliderLabel: {
    color: '#374151',
    fontSize: 12,
  },
  sliderValue: {
    color: ACCENT,
    fontSize: 12,
    fontWeight: '800',
  },
  sliderRow: {
    alignItems: 'center',
    flexDirection: 'row',
    gap: spacing.sm,
  },
  stepper: {
    alignItems: 'center',
    backgroundColor: colors.background,
    borderColor: colors.border,
    borderRadius: 6,
    borderWidth: 1,
    height: 30,
    justifyContent: 'center',
    width: 30,
  },
  stepperDisabled: {
    opacity: 0.4,
  },
  stepperText: {
    color: '#374151',
    fontSize: 15,
    fontWeight: '700',
  },
  track: {
    backgroundColor: '#e2e8f0',
    borderRadius: 999,
    flex: 1,
    height: 6,
    justifyContent: 'center',
  },
  trackFill: {
    backgroundColor: ACCENT,
    borderRadius: 999,
    height: 6,
  },
  thumb: {
    backgroundColor: ACCENT,
    borderColor: colors.surface,
    borderRadius: 999,
    borderWidth: 2,
    height: 16,
    marginLeft: -8,
    position: 'absolute',
    width: 16,
  },
  sliderEnds: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 3,
  },
  sliderEnd: {
    color: '#9ca3af',
    fontSize: 11,
  },
  locationLabel: {
    color: '#374151',
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 0.4,
    marginBottom: 6,
  },
  locationRow: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  locationInput: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 7,
    borderWidth: 1,
    color: colors.text,
    flex: 1,
    fontSize: 13,
    paddingHorizontal: 10,
    paddingVertical: 9,
  },
  geoButton: {
    alignItems: 'center',
    backgroundColor: '#fff1eb',
    borderColor: '#f5c6ad',
    borderRadius: 7,
    borderWidth: 1,
    justifyContent: 'center',
    paddingHorizontal: 11,
  },
  geoButtonText: {
    color: ACCENT,
    fontSize: 16,
  },
  geoStatus: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 6,
  },
  footer: {
    backgroundColor: colors.background,
    borderTopColor: colors.border,
    borderTopWidth: 1,
    flexDirection: 'row',
    gap: spacing.sm,
    justifyContent: 'flex-end',
    paddingHorizontal: spacing.lg,
    paddingVertical: 14,
  },
  cancel: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 7,
    borderWidth: 1,
    paddingHorizontal: spacing.lg,
    paddingVertical: 9,
  },
  cancelText: {
    color: '#374151',
    fontSize: 13,
    fontWeight: '600',
  },
  apply: {
    backgroundColor: ACCENT,
    borderRadius: 7,
    paddingHorizontal: 18,
    paddingVertical: 9,
  },
  applyText: {
    color: colors.surface,
    fontSize: 13,
    fontWeight: '700',
  },
});
