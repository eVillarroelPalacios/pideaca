# 27. Catálogos: Tipos, Monedas, Unidades y Estados

![Tipos de usuario](img/web-tipo-usuarios.png)
![Estados](img/web-estados-usuarios.png)

## Qué son

Tablas maestras del sistema, todas con el mismo patrón (lista + buscador +
alta/edición/baja + `can_delete` si están en uso):

- **Tipos de usuario**: Cliente, Prestador, Repartidor, Admin. Cada tipo
  trae su plantilla de páginas (lo que ve cada rol al registrarse).
- **Estados de usuarios** y **Estados de grupos**: Activo/Inactivo y
  equivalentes; lo inactivo se oculta donde corresponda.
- **Tipo de monedas**: una moneda por país (código, símbolo, posición).
- **Unidades de medida**: para productos del catálogo (kg, unidad...).

## Detrás de escena (referencia técnica)

- Vistas: `dashboard.sections.tipo-usuarios/estados-usuarios/
  estados-grupos/tipo-monedas/unidades-medida`.
- API web y móvil con resúmenes (`total`, `in_use`) y 409 si está en uso.
