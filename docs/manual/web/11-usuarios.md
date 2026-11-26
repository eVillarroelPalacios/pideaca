# 5. Ventana web: Gestión de Usuarios

![Gestión de Usuarios](img/web-usuarios.png)

## Qué es

Administración de los usuarios de la cuenta: el titular crea hasta 3
usuarios secundarios (ej: encargados) y les asigna permisos por página.
Abajo también se ven los **Retiros pendientes** de repartidores.

## Cómo se usa

1. **Agregar nuevo usuario**: nombre, correo (puede repetir el del titular
   para secundarios), clave y tipo. El secundario hereda las páginas del
   titular y opera sobre su negocio en modo solo-lectura donde corresponde.
2. **Asignar Permisos de Páginas**: se elige un usuario de la lista y se
   tildan las páginas de los módulos que podrá ver (misma fuente que usa
   el menú y la API: sin la página, no entra por ningún lado).
3. **Buscar Usuario** filtra la tabla por nombre o correo.
4. **Retiros pendientes**: aprobar confirma el pago al repartidor, rechazar
   devuelve el monto a su billetera.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.usuarios` + modales `dashboard.modals.users`.
- API: `/admin/users` (CRUD, direcciones, imágenes, páginas, retiros),
  protegida por `middleware page:usuarios`.
- Regla de seguridad: el bypass de cuenta principal exige secundarios
  reales (`User::hasSecondaryUsers`), si no, 403.
