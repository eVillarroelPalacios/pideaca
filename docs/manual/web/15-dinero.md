# 9. Ventana web: Dinero (backoffice)

![Dinero](img/web-dinero.png)

## Qué es

El backoffice de dinero del Admin: reglas de comisión por módulo/país/
moneda, matriz de la compañía, liquidaciones, cobros, efectivo,
conciliación, planes y facturas.

## Cómo se usa

1. **Comisiones**: se define % y mínimo por módulo, país y moneda
   (vacío = todos). Rige para cobros futuros; lo ya cobrado conserva su
   foto. **Guardar regla** la deja activa (o no).
2. El resto de las solapas (Matriz, Liquidaciones, Cobros, Efectivo,
   Conciliación, Planes, Facturas) liquidan y concilian el dinero de
   comercios, repartidores y suscripciones.
3. Los comercios y repartidores ven su parte en **Billeteras por país**
   y piden **Retiros** con comprobante.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.dinero` (+ `billeteras-pais` para usuarios).
- API: `admin/billing/*` (fee-rules, company, settlements, ledger,
  status, plans) con verificación de Admin en el controlador.
- Monedas y billeteras por país: `tipo-monedas`, `billeteras-pais`.
