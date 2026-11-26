# 10. Flujo: compra en Mercado

## Paso a paso

1. **Tienda.** El cliente entra al comercio, filtra por categorías y arma
   el carrito (variantes, agregados, cantidades; respeta stock y máximos).
2. **Dirección y entrega.** Elige dirección (o la carga) y modalidad de
   envío según lo que ofrezca el comercio.
3. **Pago.** Efectivo (paga al recibir), transferencia (adjunta
   comprobante con CBU visible) o Mercado Pago (checkout y retorno con
   polling de confirmación). Con billetera con saldo puede pagar directo.
4. **Confirmación.** El botón se habilita al cumplir dirección + pago y
   crea el pedido con número único (ej. PIDE-2026-0001).
5. **Seguimiento.** El pedido aparece en Mis Pedidos y al comercio en
   Pedidos en segundos (capítulos 6 y 7); avanza por estados sin saltos
   hasta la entrega y calificación mutua.

## Si algo falla

- **No avanza de estado:** el flujo no permite saltos (422) — seguir el
  orden pendiente → cocina → listo → asignado → retirado → entregado.
- **Pago pendiente:** Mercado Pago confirma por webhook/retorno; si queda
  colgado se reintenta desde Mis Pedidos.
- **Sin repartidor:** el pedido espera en pool o lista de confianza; el
  comercio puede liberarlo manualmente.

## Referencia técnica

- Tienda: sección `comercios` (`fd-shop-*` en `dashboard-app.js`).
- API: `/api/orders` (crear), `/api/orders/mine`, PATCH
  `/api/orders/{id}/status`, Mercado Pago retorno + polling.
- Móvil: pantallas de comercio y Mis pedidos (Expo, mismo flujo).
