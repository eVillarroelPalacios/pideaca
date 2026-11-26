# 37. Pantalla móvil: Repartidor (Terminal Central)

![Terminal del repartidor en la app](img/movil-repartidor.png)

## Qué es

La terminal del repartidor en la app: estado en línea, legajo (ID),
vehículo, zona de cobertura, radar sonoro, GPS, ganancias del día, meta
de pedidos, aceptación, calificación y saldo disponible para retirar.
Espeja la ventana web Repartidor.

## Cómo se usa

1. Configurar vehículo y zona antes de ponerse en línea.
2. Aceptar ofertas del radar y completar las entregas.
3. Controlar ganancias y pedir el retiro del saldo disponible.

## Referencia técnica

- Pantalla: `mobile/src/app/repartidor.tsx` (polling cada 10 s).
- API: `driver/status`, `driver/stats` y `driver/orders/mine`.
