# 1. Mapa general del sistema

## Qué es PideAca

Plataforma que conecta **clientes** con **comercios/prestadores** y **repartidores**:
el cliente compra o solicita servicios, paga por Mercado Pago, transferencia,
efectivo o billetera, y el comercio despacha (con repartidores propios, de
confianza o por delivery de la plataforma).

## Módulos (panel web)

| Módulo | Para quién | Qué incluye |
|---|---|---|
| Mercado | Cliente, Comercio | Comercios, catálogo, pedidos, mis pedidos, suscripciones, retiros |
| Hogar | Prestador de servicios | Solicitudes, órdenes de servicio, gestión, mis servicios, reportes hogar |
| Reparto | Repartidor | Panel de reparto, pedidos asignados, billetera, reportes |
| Dinero | Comercio, Prestador | Billeteras por país, movimientos, retiros |
| Administrar | Admin | Usuarios, módulos, páginas, tipos, grupos, monedas, consultas |

## Roles

| Rol | Descripción |
|---|---|
| Admin | Acceso total, gestiona usuarios y catálogos, ve reportes |
| Prestador | Comercio gastronómico o profesional de hogar/bienestar |
| Cliente | Compra, pide presupuestos, califica, se suscribe |
| Repartidor | Toma pedidos asignados o del pool, confirma entregas |

Los permisos se manejan por **página** (`middleware page:...`): lo que el
usuario no ve en el menú tampoco puede usar por API. Existen usuarios
secundarios que heredan las páginas del titular de la cuenta.

## Flujos principales

1. **Registro y activación** — el usuario se registra, recibe un correo con
   enlace único de 24 h, lo activa e inicia sesión. Ver capítulo 3.
2. **Compra en Mercado** — el cliente arma el carrito en la tienda del
   comercio, elige dirección y medio de pago, confirma; el comercio lo ve
   en Pedidos en segundos (auto-refresh cada 8 s).
3. **Servicios del hogar** — solicitud directa, cita programada o
   presupuesto con fotos; el prestador acepta, ejecuta y cobra (efectivo,
   transferencia o escrow con calificación mutua).
4. **Despacho y reparto** — el comercio despacha con repartidores de
   confianza o libera al pool; el repartidor retira y entrega con PIN.
5. **Dinero** — billeteras por país, retiros con comprobante, reportes.

## Aplicación móvil (Expo)

Espeja el panel: Consultas, Pedidos, Mis pedidos, Proveedores, Billetera,
Dinero, etc. Consume la API `mobile/v1` con token Bearer. Las listas con
seguimiento en vivo se actualizan solas mientras la pantalla está enfocada.
Los APK se distribuyen por descarga directa con aviso de actualización
dentro de la app. Ver capítulo 4.
