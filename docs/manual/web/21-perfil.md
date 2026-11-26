# 23. Ventana web: Mi Perfil (detalle)

![Mi Perfil](img/web-perfil.png)

## Qué es

La ficha personal del usuario con sidebar por rol: datos del negocio,
horarios, servicios, publicidad, categorías, dirección, usuarios,
contraseñas y billetera. Arranca en el panel que corresponde a su rol
(negocio, dirección, vehículo o cuenta).

## Cómo se usa

1. **Mi Dirección**: país → provincia → calle/número/postal + notas.
   Sin dirección completa, el sistema bloquea la operatoria y trae acá.
2. El resto de los paneles dependen del rol (negocio y horarios para
   prestadores, vehículo para repartidores, etc.).
3. Cada panel guarda con su botón y muestra mensajes de éxito/error.

## Detrás de escena (referencia técnica)

- Vista: `dash-perfil` en `dashboard.blade.php`.
- API: `/profile/*` con `page:` y titularidad; panel inicial por
  `$perfilPanelInicial` (negocio/dirección/vehículo/cuenta).
