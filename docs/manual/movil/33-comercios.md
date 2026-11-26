# 32. Pantalla móvil: Comercios

![Comercios en la app](img/movil-comercios.png)

## Qué es

La vidriera en la app: comercios con envío a domicilio, con buscador,
favoritos, foto, rubro, zona, calificación y promo. Espejo del capítulo 11.

## Cómo se usa

1. Buscar por nombre; tabs de favoritos y todos.
2. Cada tarjeta lleva a la tienda del comercio para armar el pedido.
3. La lista se carga del API y respeta favoritos del usuario.

## Referencia técnica

- Pantalla: `mobile/src/app/comercios.tsx` (Expo Router).
- API: `api.marketShops()` con Bearer.
