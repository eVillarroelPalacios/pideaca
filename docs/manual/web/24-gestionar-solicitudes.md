# 26. Ventana web: Gestionar Solicitudes

![Gestionar Solicitudes](img/web-gestionar-solicitudes.png)

## Qué es

La cola del prestador con las solicitudes de servicio recibidas:
pendientes, confirmadas, canceladas/rechazadas y todas.

## Cómo se usa

1. Pestañas por estado + Actualizar (además del auto-refresh).
2. Requiere perfil de prestador con comercio; si no, lo indica.
3. De cada solicitud se pasa al detalle operativo para presupuestar,
   aceptar o rechazar (ver capítulo 15, Órdenes y Gestión).

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.gestionar-solicitudes`.
- API: solicitudes del proveedor (pool del rubro + directas).
