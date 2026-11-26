# 16. Flujo: despacho y reparto

## Paso a paso

1. **El pedido queda listo.** El comercio lo marca listo en Pedidos
   (capítulo 6); según su modalidad sale a su **lista de confianza** o al
   **pool general** (nunca se libera solo por tiempo).
2. **El repartidor lo toma.** En su Terminal (capítulo 8) ve la oferta con
   origen, destino y tarifa; al aceptar pasa a En Curso.
3. **Retiro.** En el comercio da el **PIN** que ve el cliente; el sistema
   valida y marca retirado.
4. **Entrega y cobro.** Entrega en destino, confirma y cobra según el
   medio del pedido (el repartidor se queda con el 100% del envío).
5. **Calificación mutua** y cierre; el dinero va a billeteras (capítulo 9).

## Si algo falla

- **Sin ofertas:** verificar estar EN LÍNEA, vehículo configurado y zona
  de cobertura; en despacho cerrado, pedir al comercio que agregue a la
  lista de confianza.
- **PIN inválido:** lo ve el cliente en su pedido; reintentar con el
  vigente (es por pedido).

## Referencia técnica

- API: `/api/v1/delivery/orders` (disponibles), `/orders/mine`
  (asignados), take/pickup/confirm, wallet y retiros del repartidor.
- Móvil: pantallas de Reparto (mismo flujo con token Bearer).
