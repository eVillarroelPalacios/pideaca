# 26. Catálogos base: Grupos, Módulos y Páginas

![Grupos](img/web-grupos.png)
![Módulos](img/web-modulos.png)
![Páginas](img/web-paginas.png)

![Grupos](img/web-grupos.png)

## Qué son

La taxonomía del sistema: los **Módulos** agrupan **Páginas** (cada ventana
del panel es una página: `comercios`, `pedidos`, `repartidor`...), y los
**Grupos** organizan rubros y subgrupos de servicios.

## Cómo se usan

1. **Grupos**: alta con descripción y estado; buscador y acciones por fila.
   Los grupos inactivos se ocultan de la landing y los filtros.
2. **Módulos**: contenedores del menú (Mercado, Hogar, Reparto, Dinero,
   Administrar). Una página siempre cuelga de un módulo.
3. **Páginas**: URL + módulo + descripción. Son la unidad de permiso: el
   menú, el `middleware page:` y la app móvil usan la misma fuente.

## Detrás de escena (referencia técnica)

- Vistas: `dashboard.sections.grupos/modulos/paginas`.
- API web y móvil: CRUD con conteos (`pages_count`, `users_count`) y
  `can_delete` (no se borra lo que está en uso: 409).
