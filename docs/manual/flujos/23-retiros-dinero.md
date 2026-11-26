# 30. Flujo: retiros y dinero

## Paso a paso

1. **Billetera.** Cada usuario carga su billetera según el catálogo de
   su país (capítulo 25): CBU/alias, email, teléfono o banco.
2. **El dinero entra** por ventas (comercio), viajes (repartidor) o
   suscripciones, con la comisión de la regla vigente (capítulo 9).
3. **Retiro.** El usuario pide el retiro e indica el comprobante; el
   monto se descuenta de su billetera al solicitar.
4. **Aprobación (Admin/comercio).** En Gestión de Usuarios (capítulo 5)
   o Finanzas: aprobar confirma el pago, rechazar devuelve el monto.
5. **Conciliación.** Liquidaciones, cobros, efectivo y facturas cierran
   el circuito en el backoffice de Dinero.

## Referencia técnica

- API: billeteras por país, retiros (solicitar/aprobar/rechazar con
  comprobante), `admin/billing/*` (liquidaciones, ledger, facturas).
- Móvil: pantallas de Billetera y Dinero.
