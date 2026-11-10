import { useState } from 'react';
import {
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { PrimaryButton } from '@/components/PrimaryButton';
import { TextField } from '@/components/TextField';
import { colors, radius, spacing } from '@/theme';

type Props = {
  visible: boolean;
  title: string;
  subtitle?: string | null;
  submitting?: boolean;
  error?: string | null;
  onClose: () => void;
  onSubmit: (stars: number, comment: string) => void;
};

/**
 * Calificación rápida desde las listas de solicitudes/trabajos, sin tener
 * que entrar al detalle de la orden. Mismo formulario que la web: estrellas
 * y comentario opcional.
 */
export function RatingModal({
  visible,
  title,
  subtitle,
  submitting = false,
  error,
  onClose,
  onSubmit,
}: Props) {
  const [stars, setStars] = useState(5);
  const [comment, setComment] = useState('');

  return (
    <Modal
      animationType="fade"
      transparent
      visible={visible}
      onRequestClose={() => {
        if (!submitting) {
          onClose();
        }
      }}
    >
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.backdrop}
      >
        <View style={styles.sheet}>
          <ScrollView
            contentContainerStyle={styles.content}
            keyboardShouldPersistTaps="handled"
          >
            <Text style={styles.title}>{title}</Text>
            {subtitle ? (
              <Text style={styles.subtitle} numberOfLines={2}>
                {subtitle}
              </Text>
            ) : null}

            <View style={styles.stars}>
              {[1, 2, 3, 4, 5].map((value) => (
                <Pressable
                  key={value}
                  accessibilityRole="radio"
                  accessibilityLabel={`${value} estrellas`}
                  accessibilityState={{ selected: stars === value }}
                  disabled={submitting}
                  onPress={() => setStars(value)}
                  style={styles.star}
                >
                  <Text
                    style={[styles.starText, stars >= value && styles.starTextActive]}
                  >
                    ★
                  </Text>
                </Pressable>
              ))}
            </View>

            <TextField
              label="Comentario (opcional)"
              value={comment}
              onChangeText={setComment}
              placeholder="Contanos cómo te fue"
              multiline
              numberOfLines={3}
              maxLength={1000}
              editable={!submitting}
            />

            {error ? <Text style={styles.error}>{error}</Text> : null}

            <PrimaryButton
              title="Enviar calificación"
              onPress={() => onSubmit(stars, comment)}
              loading={submitting}
              disabled={submitting}
            />

            <Pressable
              accessibilityRole="button"
              disabled={submitting}
              onPress={onClose}
              style={styles.cancel}
            >
              <Text style={styles.cancelText}>Cancelar</Text>
            </Pressable>
          </ScrollView>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
    flex: 1,
    justifyContent: 'center',
    padding: spacing.lg,
  },
  sheet: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    maxHeight: '90%',
    overflow: 'hidden',
  },
  content: {
    padding: spacing.lg,
  },
  title: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
  },
  subtitle: {
    color: colors.muted,
    fontSize: 14,
    marginTop: spacing.xs,
  },
  stars: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginVertical: spacing.lg,
  },
  star: {
    paddingHorizontal: spacing.xs,
    paddingVertical: spacing.xs,
  },
  starText: {
    color: colors.border,
    fontSize: 38,
    lineHeight: 42,
  },
  starTextActive: {
    color: '#f59e0b',
  },
  error: {
    color: colors.danger,
    fontSize: 13,
    marginBottom: spacing.md,
    textAlign: 'center',
  },
  cancel: {
    alignItems: 'center',
    paddingVertical: spacing.md,
  },
  cancelText: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '700',
  },
});
