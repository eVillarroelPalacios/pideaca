# 38. Pantalla móvil: Dinero (backoffice, solo Admin)

![Backoffice Dinero en la app](img/movil-dinero.png)

## Qué es

El backoffice de pagos en la app, solo para administradores: reglas de
comisión por módulo, matriz de cuentas, liquidaciones, cobros, efectivo,
conciliación, planes hogar, facturas y retiros de repartidores. Espeja
la ventana web Dinero, con las mismas reglas del servidor.

## Cómo se usa

1. Crear la regla de comisión por módulo (Mercado, Hogar, etc.).
2. Gestionar liquidaciones y verificar cobros pendientes.
3. Revisar retiros de repartidores y conciliar movimientos.

## Referencia técnica

- Pantalla: `mobile/src/app/dinero.tsx` (9 pestañas).
- API: `/admin/billing/*` (verifica rol Admin en el servidor).
