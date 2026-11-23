/**
 * País del usuario para la Billetera de cobro.
 *
 * Misma lógica que la web (fdPaisAutomatico + ipapi.co con timeout):
 * zona horaria/idioma como heurística local y confirmación por IP.
 * No pide permisos de ubicación.
 */
export type WalletCountry = 'AR' | 'ES';

function automaticCountry(): WalletCountry {
  try {
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone ?? '';
    const lang = (globalThis?.navigator?.language ?? '').toLowerCase();

    if (tz.startsWith('Europe/') || /(^|-)es(-|$)/.test(lang)) {
      return 'ES';
    }
  } catch {
    // Sin Intl disponible: Argentina por defecto.
  }

  return 'AR';
}

export function fallbackCountry(): WalletCountry {
  return automaticCountry();
}

export async function detectCountry(timeoutMs = 4000): Promise<WalletCountry> {
  const fallback = automaticCountry();

  try {
    const controller =
      typeof AbortController !== 'undefined' ? new AbortController() : null;
    const timer = controller
      ? setTimeout(() => controller.abort(), timeoutMs)
      : null;

    const response = await fetch('https://ipapi.co/json/', {
      headers: { Accept: 'application/json' },
      signal: controller?.signal,
    });

    if (timer) {
      clearTimeout(timer);
    }

    if (!response.ok) {
      return fallback;
    }

    const data = (await response.json()) as {
      country_code?: unknown;
    } | null;
    const code =
      typeof data?.country_code === 'string'
        ? data.country_code.toUpperCase()
        : '';

    return code === 'AR' || code === 'ES' ? code : fallback;
  } catch {
    return fallback;
  }
}
