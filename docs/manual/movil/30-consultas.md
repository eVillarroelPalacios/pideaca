# 4. Pantalla móvil: Consultas

![Consultas en la app](img/movil-consultas.png)

## Qué es

La bandeja de consultas y sugerencias en la app (espejo de la ventana web
del capítulo 2): lista filtrable por estado, detalle y cambio de estado.

## Cómo se usa

1. Los chips superiores filtran (Todas, Nuevas, En proceso, Resueltas).
2. Cada tarjeta muestra nombre, estado, tipo, fecha, mensaje y correo;
   tocarla abre el detalle (`/contact-inquiry/[id]`).
3. El botón naranja **"Ver Visitas por País"** abre el modal de
   estadísticas reales: KPIs, filtros Hoy/7d/Mensual, distribución por
   país con totales y promedio diario, mapa y barras por día.
4. **Actualizar** recarga manual; además la lista se sondea sola cada 8 s
   mientras la pantalla está enfocada (solo repinta si algo cambió).

## Referencia técnica

- Pantalla: `mobile/src/app/contact-inquiries.tsx` (Expo Router).
- Datos reales: `api.contactInquiryVisitStats()`
  (`GET /api/mobile/v1/contact-inquiries/visits-stats` con Bearer,
  misma fuente que el modal web).
- Sin librerías nativas nuevas: modal con `Modal` de React Native y mapa
  estilizado con vistas (para no exigir development build).
