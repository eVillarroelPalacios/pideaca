// Paleta y medidas compartidas por toda la app.
// Mantiene los mismos naranjas de la web para que se sienta el mismo producto.

export const colors = {
  brand: '#f97316',
  brandDark: '#ea580c',
  brandSoft: '#fff7ed',
  text: '#0f172a',
  muted: '#64748b',
  border: '#e2e8f0',
  background: '#f8fafc',
  surface: '#ffffff',
  danger: '#dc2626',
  dangerSoft: '#fef2f2',
  success: '#16a34a',
  successSoft: '#f0fdf4',
} as const;

export const spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 24,
  xxl: 32,
} as const;

export const radius = {
  sm: 8,
  md: 12,
  lg: 16,
  pill: 999,
} as const;
