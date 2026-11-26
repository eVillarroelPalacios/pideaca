# 11. Ventana web: Comercios

![Comercios](img/web-comercios.png)

## Qué es

La vidriera del Mercado: tarjetas de comercios con envío a domicilio, con
foto, rubro, zona, calificación, promo, favorito y acceso al catálogo. Desde
una tarjeta se entra a la tienda o se contrata una suscripción.

## Cómo se usa

1. **Buscador** por nombre y **filtros** por rubro/grupo; pestañas de
   favoritos (estrella) y todos.
2. Cada tarjeta muestra productos/categorías, promo vigente y botones:
   estrella (favorito), suscribirse (plan del comercio) y abrir catálogo.
3. Clic en la tarjeta → tienda del comercio (capítulo 10, flujo de compra).

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.comercios`.
- JS: `loadComercios()` + `fdSubscribeToProvider()` en el bundle.
- API: `/api/providers` (filtros, favoritos, conteos), conteos solo de
  comercios (Hogar excluido, sin rubro visible).
