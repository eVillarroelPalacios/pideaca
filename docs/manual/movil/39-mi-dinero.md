# 39. Pantalla móvil: Mi Dinero

![Mi Dinero en la app](img/movil-mi-dinero.png)

## Qué es

El estado de cuenta personal en la app: liquidaciones, cobros y estado,
con el mismo aviso de deuda que el panel web cuando la cuenta no puede
operar. Espeja la ventana web de dinero del usuario.

## Cómo se usa

1. Revisar liquidaciones pendientes y su estado.
2. Pasar a Cobros y Estado para el detalle de movimientos.
3. Regularizar la deuda en Billetera si aparece el aviso.

## Referencia técnica

- Pantalla: `mobile/src/app/mi-dinero.tsx`.
- API: `/profile/billing/*` (liquidaciones, cobros y estado).
