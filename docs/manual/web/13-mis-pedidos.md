# 7. Ventana web: Mis Pedidos

![Mis Pedidos](img/web-mis-pedidos.png)

## Qué es

El seguimiento de compras del cliente: qué pidió, en qué estado está cada
pedido y su historial con calificación y repetir pedido.

## Cómo se usa

1. **Buscador** por plato, local o código de pedido.
2. Pestañas **En curso** (pendiente, cocina, en camino) e **Historial**
   (entregados/cancelados, paginado de a 3) con filtro por estado.
3. Cada tarjeta muestra el tracking (En retiro / En entrega / Entregado),
   el repartidor y el total; desde el historial se califica y se repite.
4. Se actualiza en vivo mientras la ventana está visible.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.mis-pedidos`.
- JS: `loadMyOrders(true)` silencioso + `fdStartPolling('mis-pedidos')`,
  tarjetas en `OrderTrackingCard` (móvil) / render del panel (web).
- API: `/api/orders/mine`, `/api/providers/{id}/orders/{order}/status`
  (PATCH, flujo sin saltos, 422 si se salta un paso).
