# 6. Ventana web: Pedidos (Centro de Despacho)

![Control de Pedidos](img/web-pedidos.png)

## Qué es

El tablero del comercio para preparar, asignar y entregar pedidos en tiempo
real. Arriba: contadores (pendientes, en cocina) y estado de sincronización.
Abajo: pestañas Hoy/Historial con filtros por estado y búsqueda.

## Cómo se usa

1. **Despacho de pedidos**: con "Solo repartidores de confianza" activo,
   los pedidos solo los ven los de la lista (no se liberan solos); si no,
   van al pool general. La **Lista de confianza** se arma buscando
   repartidores y con "Agregar a la lista".
2. Cada pedido avanza: pendiente → en cocina → listo → asignado →
   retirado → entregado. Las acciones las ejecuta el comercio o el
   repartidor según el paso (el repartidor confirma el retiro con PIN).
3. Todo se refresca solo cada 8 s sin recargar la página.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.pedidos`.
- JS: `loadProviderOrders(true)` en modo silencioso + `fdStartPolling`.
- API: `/api/providers/{id}/orders` (filtros, paginación),
  `/api/v1/provider/trusted-drivers` (lista de confianza, solo comercios).
