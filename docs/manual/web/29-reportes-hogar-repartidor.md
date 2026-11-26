# 29. Ventana web: Reportes Hogar y Repartidor

![Reportes Hogar](img/web-reportes-hogar.png)

## Qué son

- **Reportes Hogar**: consolidado de servicios del módulo Hogar con
  KPIs (total gastado, seña en custodia, finalizados, reputación,
  cancelaciones), filtros por fecha/estado/modalidad, detalle de órdenes
  y exportación a PDF.
- **Reportes Repartidor**: ganancias, viajes, aceptación y calificación
  del repartidor en el período (con espejo móvil).

## Detrás de escena (referencia técnica)

- Vistas: `reports.hogar` (+ PDF), `dashboard.sections.reportes-hogar`,
  `reportes-repartidor`.
- API: reportes por rol con agregados por fecha; espejos `mobile/v1`
  para la app.
