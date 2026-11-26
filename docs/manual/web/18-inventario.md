# 13. Ventana web: Inventario

![Inventario](img/web-inventario.png)

## Qué es

Control de stock del comercio: qué hay, cuánto queda y movimientos de
entrada/salida. Con "Control de inventario" activo, la tienda respeta el
stock (sin stock no se puede agregar al carrito).

## Cómo se usa

1. Requiere comercio asociado (si no, lo indica como en la captura).
2. Tabla de productos con stock actual, ajustes manuales (+/- con motivo)
   e historial de movimientos.
3. En la tienda, los productos sin stock se muestran deshabilitados con
   aviso "Sin stock en este momento".

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.inventario`.
- API: `/api/v1/provider/inventory` (lista, ajustes, movimientos),
  `/api/v1/provider/inventory/settings` (activar control).
