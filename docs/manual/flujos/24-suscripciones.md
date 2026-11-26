# 31. Flujo: suscripciones

## Paso a paso

1. **Oferta.** El comercio crea planes (título, frecuencia, precio,
   capacidad, productos incluidos) desde Suscripciones.
2. **Contratación.** El cliente elige plan desde la tarjeta del comercio
   (`fdSubscribeToProvider` fija el combo) o desde Mis Suscripciones.
3. **Ciclos.** Cada ciclo factura (con día/horario preferido de entrega,
   descuentos y tolerancias) y debita según el medio de pago.
4. **Distribución.** En el primer ciclo facturado se arman los sabores/
   productos propios del plan.
5. **Gestión.** Pausar, reanudar o cancelar; reportes de ingreso fijo
   proyectado, cobrado vs pendiente y por mes.

## Si algo falla

- **Sin cupo:** el límite frena altas nuevas, no cancela las vigentes.
- **Pago fallido:** queda pendiente con reintento; la tolerancia de
  ventana recupera clientes si el cron se detuvo.

## Referencia técnica

- API: planes, suscripciones de clientes, facturación recurrente,
  reportes de ingresos (`SubscriptionBilling*`, retentions y cron
  `marketing:run-retention`).
- Móvil: pantallas de suscripciones del cliente y del comercio.
