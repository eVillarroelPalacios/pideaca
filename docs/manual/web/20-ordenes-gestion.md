# 15. Ventana web: Órdenes y Gestión

![Órdenes y Gestión](img/web-ordenes-gestion.png)

## Qué es

La vista unificada del prestador: servicios y pedidos del comercio en un
solo feed con pestañas (Todas, Pendientes, Confirmados, Completados,
Cancelados), buscador y filtros rápidos (hoy, 7/30 días, emergencias,
presupuestos).

## Cómo se usa

1. Las pestañas filtran en el servidor con contadores; la búsqueda filtra
   por cliente, ID, teléfono o descripción (insensible a mayúsculas).
2. Cada fila lleva a su detalle operativo (contacto, ubicación, adjuntos,
   timeline) para ejecutar el trabajo.
3. Se refresca sola mientras está visible (`fdOgLoad(true)` cada 8 s);
   el botón Actualizar queda de respaldo.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.ordenes-gestion` (+ `fdOg*` en el bundle).
- API: `/api/v1/provider/ordenes-gestion` (pestañas, búsqueda portable
  con `LOWER LIKE`, filtros rápidos, feed unificado servicio+comercio).
