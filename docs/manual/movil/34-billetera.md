# 33. Pantalla móvil: Billetera

![Billetera en la app](img/movil-billetera.png)

## Qué es

La billetera de cobro en la app: cuentas cargadas para liquidaciones,
con accesos a Mi Dinero (liquidaciones, cobros y estado de cuenta).

## Cómo se usa

1. Cargar las cuentas donde cobrar; la primera queda predeterminada.
2. Ver liquidaciones y cobros en Mi Dinero, igual que el panel web.
3. Los retiros se piden contra estas cuentas (ver capítulo 30, retiros).

## Referencia técnica

- Pantalla: `mobile/src/app/billetera.tsx` (+ `mi-dinero.tsx`).
- API: billeteras del usuario y movimientos, con Bearer.
