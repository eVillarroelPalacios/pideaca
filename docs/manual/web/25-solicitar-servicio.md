# 27. Ventana web: Solicitudes (buscar prestador)

![Solicitudes](img/web-solicitar-servicio.png)

## Qué es

El buscador de prestadores del cliente: tarjetas por rubro con foto,
calificación y promo, con favoritos, filtros y búsqueda. Desde acá nacen
las urgencias, citas y presupuestos.

## Cómo se usa

1. Buscador por texto + filtros; tabs de favoritos y todos.
2. Cada tarjeta lleva a pedir: urgencia directa, cita programada o
   presupuesto (con fotos/video y contexto dinámico del rubro).
3. El seguimiento posterior vive en Mis Pedidos / Mis Servicios.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.solicitar-servicio`.
- JS: `loadProviders()`, `fdSubscribeToProvider` en el bundle.
- API: `/api/v1/service-providers`, favoritos, creación de urgencias,
  citas y presupuestos con adjuntos (`UploadFiles` con reintento).
