/**
 * Formato de moneda compartido por las pantallas de Mercado.
 * Espeja `fdMoney` de la web: separador de miles con punto y decimales con
 * coma, con el símbolo antes del número (`$ 1.234,56`).
 */
export function money(value: number | string | null | undefined): string {
  const n = Number(value ?? 0);
  const negative = n < 0;
  const abs = Math.abs(Number.isNaN(n) ? 0 : n);
  const [intPart, decPart] = abs.toFixed(2).split('.');
  const grouped = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  const out = `$ ${grouped},${decPart ?? '00'}`;

  return negative ? `-${out}` : out;
}
