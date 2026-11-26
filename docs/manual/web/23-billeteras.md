# 25. Ventana web: Billeteras por país

![Billeteras por país](img/web-billeteras.png)

## Qué es

Catálogo (Admin) de tipos de billetera por país: qué medios acepta cada
país y con qué campos (titular, CBU, alias, email, teléfono, banco...).
Lo desactivado no aparece en los combos de los usuarios.

## Cómo se usa

1. Tabla con país, nombre, código (único por país), campos y estado.
2. Alta con país, código, nombre visible y lista de campos; se activa o
   desactiva según corresponda. Los usuarios cargan la suya contra este
   catálogo y piden retiros.

## Detrás de escena (referencia técnica)

- Vista: `dashboard.sections.billeteras-pais`.
- API: CRUD Admin de tipos por país; billeteras de usuario y retiros
  con comprobante.
