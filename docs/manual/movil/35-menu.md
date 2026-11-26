# 35. Pantalla móvil: Menú principal y Mi Perfil

![Menú principal en la app](img/movil-menu.png)

![Mi Perfil en la app](img/movil-home.png)

## Qué es

El menú principal ("Mi cuenta") lista las secciones habilitadas para el
usuario, agrupadas por módulo (Hogar, Mercado, Reparto, Administrar), con
su ficha (nombre, correo, rol y estado). La ruta de inicio (`/home`)
muestra Mi Perfil: datos de la cuenta y dirección (país, provincia,
calle, número, piso, código postal).

## Cómo se usa

1. Abrir el menú para saltar a cualquier sección sin volver atrás.
2. En Mi Perfil se completa la dirección para presupuestos y envíos.
3. Cada fila del menú lleva a su pantalla con navegación interna.

## Referencia técnica

- Pantallas: `mobile/src/app/menu.tsx` (drawer) y `mobile/src/app/home.tsx`.
- API: `api.menu()` (módulos y páginas por rol) y perfil con Bearer.
