/**
 * Formatea cantidades de stock con hasta 3 decimales.
 * Espeja `fdInvNum` del panel web; los valores nulos se muestran como "—".
 */
export function stockNumber(value: number | null): string {
  if (value === null || Number.isNaN(value)) {
    return '—';
  }

  return Number.isInteger(value) ? String(value) : String(Number(value.toFixed(3)));
}
