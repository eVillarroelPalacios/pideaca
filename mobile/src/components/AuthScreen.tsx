import type { ReactNode } from 'react';
import {
  Image,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { colors, radius, spacing } from '@/theme';

type Props = {
  /** Título del hero; si se omite solo se muestra el logo y el subtítulo. */
  title?: string;
  subtitle: string;
  children: ReactNode;
  footer?: ReactNode;
};

/**
 * Marco visual compartido por Login y Registro.
 */
export function AuthScreen({ title, subtitle, children, footer }: Props) {
  return (
    <SafeAreaView style={styles.safe}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.flex}
      >
        <ScrollView
          contentContainerStyle={styles.content}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.hero}>
            <Image
              source={require('../../assets/logo.png')}
              style={styles.logo}
              resizeMode="contain"
              accessibilityLabel="PideAca"
            />
            {title ? <Text style={styles.title}>{title}</Text> : null}
            <Text style={styles.subtitle}>{subtitle}</Text>
          </View>

          <View style={styles.card}>{children}</View>

          {footer ? <View style={styles.footer}>{footer}</View> : null}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    backgroundColor: colors.brandSoft,
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  content: {
    flexGrow: 1,
    padding: spacing.xl,
  },
  hero: {
    marginBottom: spacing.xl,
    marginTop: spacing.xl,
  },
  logo: {
    height: 96,
    marginBottom: spacing.lg,
    width: 118,
  },
  title: {
    color: colors.text,
    fontSize: 30,
    fontWeight: '800',
    marginBottom: spacing.sm,
  },
  subtitle: {
    color: colors.muted,
    fontSize: 16,
    lineHeight: 22,
  },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    elevation: 2,
    padding: spacing.xl,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.08,
    shadowRadius: 16,
  },
  footer: {
    alignItems: 'center',
    marginTop: spacing.xl,
  },
});
