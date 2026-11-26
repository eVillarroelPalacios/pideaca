# 14. Ventana web: Órdenes de Servicio

![Órdenes de Servicio](img/web-ordenes-servicio.png)

## Qué es

La lista de trabajos del prestador de hogar: urgencias, citas y
presupuestos que le llegaron, con filtros y actualización.

## Cómo se usa

1. Requiere perfil de prestador (si no, lo indica como en la captura).
2. Filtro por estado y botón Actualizar (además del refresco automático).
3. Cada orden muestra cliente, dirección, tipo (urgencia/cita/
   presupuesto), estado y fecha; de ahí se acepta, se presupuesta o se
   ejecuta según el flujo que corresponda.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.ordenes-servicio`.
- API: órdenes de servicio del proveedor (pool del rubro + asignadas),
  con Same-gating de rubros: cada prestador ve su pool.
