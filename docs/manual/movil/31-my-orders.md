# 28. Pantalla móvil: Mis Pedidos

![Mis Pedidos en la app](img/movil-my-orders.png)

## Qué es

El seguimiento de compras en la app: buscador, pestañas En curso e
Historial, filtro por estado y tarjetas con tracking. Espejo del
capítulo 7 (web).

## Cómo se usa

1. Buscar por plato, local o código; filtrar por estado.
2. En curso muestra lo activo con su estado; Historial, lo cerrado
   (paginado, con calificación y repetir pedido).
3. Sin pedidos, invita a ir a Comercios. Si la sesión expiró, lo avisa
   (cartel rojo) y hay que entrar de nuevo.
4. Se actualiza sola cada 8 s con la pantalla enfocada.

## Referencia técnica

- Pantalla: `mobile/src/app/my-orders.tsx` (Expo Router).
- API: `api.marketOrders()` con Bearer; polling con `useFocusEffect`.
