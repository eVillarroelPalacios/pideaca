import { StyleSheet, Text, View } from 'react-native';

import { colors } from '@/theme';

type Props = {
  text: string;
};

/**
 * Estado vacío con borde punteado de los reportes.
 * Espeja `repTable()` sin filas de `dashboard/sections/reportes.blade.php`.
 */
export function EmptyStateDashedContainer({ text }: Props) {
  return (
    <View style={styles.box}>
      <Text style={styles.text}>{text}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  box: {
    backgroundColor: colors.surface,
    borderColor: '#d1d5db',
    borderRadius: 12,
    borderStyle: 'dashed',
    borderWidth: 1,
    paddingHorizontal: 16,
    paddingVertical: 32,
  },
  text: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
  },
});
