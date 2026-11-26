# 12. Ventana web: Mi catálogo

![Mi catálogo](img/web-mi-catalogo.png)

## Qué es

El espejo de la tienda tal como la ven los clientes, más la gestión de
productos, categorías y unidades de medida del comercio.

## Cómo se usa

1. Sin comercio configurado, pide crearlo desde Tu Perfil (como en la
   captura). Con comercio: vista previa del catálogo.
2. **Productos**: alta con nombre, precio, variantes (tamaños), opciones
   y adicionales, foto y disponibilidad; se paginan de a 10 y se reagrupan
   por categoría.
3. **Categorías** y **Uds. de medida**: catálogos propios del comercio.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.mi-catalogo` (+ `unidades-medida`).
- JS: `loadProviderCatalog()`, `fdCatGoPage`, `FD_CAT_PER_PAGE = 10`.
- API: `/api/providers/{id}/catalog`, CRUD de productos/variantes,
  categorías y unidades. Sin `provider` responde 403 con mensaje claro.
