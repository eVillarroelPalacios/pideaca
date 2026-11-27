import { useEffect, useState } from 'react';
import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import { ApiError, api } from '@/api/client';
import type { MpSellerStatus } from '@/types/api';
import { colors, radius, spacing } from '@/theme';

/**
 * Conexión OAuth con Mercado Pago (split automático): los cobros con MP
 * entran directo a la cuenta del vendedor y la comisión va sola a PideAca.
 */
export function MpConnectCard() {
  const [status, setStatus] = useState<MpSellerStatus | null>(null);
  const [checking, setChecking] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    api
      .mpSellerStatus()
      .then((response) => {
        if (active) setStatus(response.data);
      })
      .catch((caught: unknown) => {
        if (active) {
          setError(
            caught instanceof ApiError
              ? caught.message
              : 'No pudimos verificar la conexión.',
          );
        }
      })
      .finally(() => {
        if (active) setChecking(false);
      });

    return () => {
      active = false;
    };
  }, []);

  async function handleConnect() {
    setError(null);
    setBusy(true);

    try {
      const response = await api.mpConnectUrl();
      await Linking.openURL(response.data.url);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos abrir Mercado Pago. Intentá de nuevo.',
      );
    } finally {
      setBusy(false);
    }
  }

  async function handleDisconnect() {
    setError(null);
    setBusy(true);

    try {
      await api.mpDisconnect();
      const response = await api.mpSellerStatus();
      setStatus(response.data);
    } catch (caught) {
      setError(
        caught instanceof ApiError
          ? caught.message
          : 'No pudimos desconectar. Intentá de nuevo.',
      );
    } finally {
      setBusy(false);
    }
  }

  return (
    <View style={styles.card}>
      <Text style={styles.title}>Cobros directos con Mercado Pago</Text>
      <Text style={styles.text}>
        Conectá tu cuenta para cobrar directo: lo que te paguen entra a vos y la comisión va sola a PideAca.
      </Text>
      {checking ? (
        <Text style={styles.status}>Verificando conexión…</Text>
      ) : (
        <Text style={status?.connected ? styles.connected : styles.disconnected}>
          {status?.connected
            ? `Conectada${status.mp_user_id ? ` (MP ${status.mp_user_id})` : ''}.`
            : 'Sin conectar.'}
        </Text>
      )}
      {error ? <Text style={styles.error}>{error}</Text> : null}
      <View style={styles.actions}>
        {!status?.connected ? (
          <Pressable
            accessibilityRole="button"
            onPress={() => void handleConnect()}
            disabled={busy}
            style={[styles.button, styles.buttonPrimary, busy && styles.buttonDisabled]}
          >
            <Text style={styles.buttonPrimaryText}>
              {busy ? 'Abriendo…' : 'Conectar Mercado Pago'}
            </Text>
          </Pressable>
        ) : (
          <Pressable
            accessibilityRole="button"
            onPress={() => void handleDisconnect()}
            disabled={busy}
            style={[styles.button, styles.buttonGhost, busy && styles.buttonDisabled]}
          >
            <Text style={styles.buttonGhostText}>Desconectar</Text>
          </Pressable>
        )}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: radius.lg,
    borderWidth: 1,
    marginBottom: spacing.md,
    padding: spacing.lg,
  },
  title: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '800',
    marginBottom: spacing.xs,
  },
  text: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 20,
    marginBottom: spacing.sm,
  },
  status: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: spacing.sm,
  },
  connected: {
    color: '#047857',
    fontSize: 13,
    fontWeight: '700',
    marginBottom: spacing.sm,
  },
  disconnected: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
    marginBottom: spacing.sm,
  },
  error: {
    color: '#dc2626',
    fontSize: 12,
    marginBottom: spacing.sm,
  },
  actions: {
    flexDirection: 'row',
    gap: spacing.sm,
  },
  button: {
    alignItems: 'center',
    borderRadius: radius.md,
    flex: 1,
    paddingVertical: 10,
  },
  buttonPrimary: {
    backgroundColor: '#009ee3',
  },
  buttonPrimaryText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '700',
  },
  buttonGhost: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderWidth: 1,
  },
  buttonGhostText: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
  },
  buttonDisabled: {
    opacity: 0.6,
  },
});
