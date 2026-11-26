# 36. Pantalla móvil: Prestadores (Solicitudes)

![Prestadores en la app](img/movil-providers.png)

## Qué es

El buscador de prestadores de servicios en la app: tarjetas con foto,
calificación, zona y promo, con pestañas de favoritos y todos, buscador
por nombre o rubro y filtro por distancia. Espeja la ventana web
Solicitudes.

## Cómo se usa

1. Buscar por texto o filtrar por cercanía (usa el GPS del teléfono).
2. Marcar favoritos con la estrella para verlos primero.
3. Tocar una tarjeta para ver el perfil y pedir presupuesto.

## Referencia técnica

- Pantalla: `mobile/src/app/providers.tsx` (Expo Router).
- API: `api.providers()` con Bearer; favoritos en el perfil.
