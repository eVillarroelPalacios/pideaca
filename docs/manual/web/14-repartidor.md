# 8. Ventana web: Repartidor (Terminal Central)

![Terminal de Repartos](img/web-repartidor.png)

## Qué es

La terminal del repartidor: estado en línea, vehículo, ganancias del día,
ofertas de viaje disponibles y pedidos en curso.

## Cómo se usa

1. **EN LÍNEA / Pausar**: solo en línea recibe ofertas. Antes hay que
   **configurar el vehículo** (sin unidad no llegan pedidos).
2. **Zona de cobertura** y **Radar Sonoro** (aviso de nuevas ofertas).
3. **KPIs**: ganancias de hoy, meta del día, aceptación y calificación.
4. **Pedidos Disponibles**: ofertas del pool con tarifa de envío; se
   aceptan por cercanía/prioridad. **En Curso/Asignados**: retiro (con
   PIN del cliente) y entrega para cobrar.
5. Tarjetas con origen (recogida) y destino (entrega) por pedido.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.repartidor` (+ panel alternativo
  `delivery.panel` y "Panel clásico").
- API: `/api/v1/delivery/orders` (disponibles),
  `/api/v1/delivery/orders/mine` (asignados), take/pickup/confirm,
  `/api/driver/vehicle`, billetera y retiros del repartidor.
- Reglas: pool por rubro y cercanía; despacho cerrado respeta la lista
  de confianza del comercio; sin vehículo no hay ofertas.
