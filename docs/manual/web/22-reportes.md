# 24. Ventana web: Reportes

![Reportes](img/web-reportes.png)

## Qué es

Reportes del cliente/comercio: ahorro, suscripciones activas, histórico
y hábitos de compra, y resumen nutricional. Hay variantes por rol:
comercio, hogar (`reportes-hogar`) y repartidor (`reportes-repartidor`,
con espejo móvil).

## Cómo se usa

1. Las solapas cambian el informe; los números salen del historial real
   de pedidos, suscripciones y calificaciones.
2. Los reportes de repartidor muestran ganancias, viajes, aceptación y
   calificación del período.

## Detrás de escena (referencia técnica)

- Vistas: `dashboard.sections.reportes*`.
- API: `ReportController`, `ReporteHogarController`, reportes de reparto
  y sus espejos `mobile/v1` para la app.
