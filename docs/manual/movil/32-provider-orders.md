# 29. Pantalla móvil: Trabajos del proveedor

![Trabajos en la app](img/movil-provider-orders.png)

## Qué es

Los trabajos del prestador en la app, con filtros por ámbito (todos,
pool, míos), estado y tipo (urgencia, cita, presupuesto).

## Cómo se usa

1. Sin comercio asociado, la pantalla lo indica (como en la captura) y
   pide registrarse como prestador.
2. Con comercio: pool del rubro + asignados, con paginación y
   actualización por pull-to-refresh más sondeo en vivo enfocado.
3. Desde cada trabajo se presupuesta, acepta, califica y se cierra.

## Referencia técnica

- Pantalla: `mobile/src/app/provider-orders.tsx`.
- API: `api.providerOrders()` con Bearer; firma de cambios para repintar
  solo si algo cambió.
