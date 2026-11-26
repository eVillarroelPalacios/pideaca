# 28. Ventana web: Mis Servicios

![Mis Servicios](img/web-mis-servicios.png)

## Qué es

Las solicitudes, citas y presupuestos del cliente con filtros por estado,
tipo y fecha. Es el "Mis Pedidos" del mundo hogar.

## Cómo se usa

1. Filtros por estado/tipo y rango de fechas; la lista muestra el estado
   de cada solicitud con su timeline.
2. Sin solicitudes, invita a pedir desde Solicitar Servicio.
3. Se actualiza sola con la ventana visible.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.mis-servicios`.
- API: solicitudes del cliente (urgencias, citas, presupuestos) con sus
  estados y transiciones.
