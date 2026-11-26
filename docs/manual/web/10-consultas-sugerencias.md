# 2. Ventana web: Consultas y sugerencias

![Consultas y sugerencias](img/web-consultas.png)

## Qué es

Bandeja donde el Admin ve los mensajes enviados desde el formulario de
contacto del sitio (consultas, sugerencias y reclamos). Cada mensaje se
gestiona con estados: **Nueva → En proceso → Resuelta**.

## Cómo se usa

1. Los filtros superiores separan por estado, con contadores.
2. La tabla muestra cliente, correo, tipo, mensaje, fecha y estado.
3. **Ver** abre el detalle: datos del cliente, mensaje completo, cambio de
   estado y botón "Responder por correo" (abre el mail con asunto armado).
4. **Guardar estado** persiste el cambio; la lista se refresca sola.

## Estadísticas de visitas por país

El botón naranja **"Ver Visitas por País"** (arriba a la derecha) abre un
modal con dona por país, barras de progreso, lista con bandera/visitas/% y
mapa mundial. Hoy usa datos de ejemplo; para conectarlo a datos reales basta
enviar `$countryVisits` desde el controlador con la misma estructura.

## Detrás de escena (referencia técnica)

- Vista: `resources/views/dashboard/sections/consultas-sugerencias.blade.php`
- JS: `loadContactInquiries()` / `renderContactInquiries()` en
  `public/js/dashboard-app.js` (sondeo silencioso cada 8 s solo si la
  ventana está visible y algo cambió).
- API: `GET /contact-inquiries` (página `consultas-sugerencias`),
  `POST /contact-inquiries/{id}/status`.
