# Despliegue a producción — pideaca.com (FTP puerto 21 + Plesk)

Situación: el panel Plesk (`:8443`) no responde por bloqueo/timeout, pero el
FTP/FTPS (puerto 21) sí funciona. Este flujo no usa `:8443` para nada:
archivos por FTPS, resto por tareas programadas y administrador de archivos.

---

## 0. Requisitos previos (una sola vez)

1. **DNS**: `pideaca.com` (y `www`) apuntando al hosting. Verificar con
   `nslookup pideaca.com`.
2. **PHP 8.2+** con extensiones: `pdo_pgsql`, `pgsql`, `mbstring`,
   `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `zip`,
   `fileinfo`, `gd`, `bcmath`. (Pedirlo a soporte si el selector de PHP no
   está visible sin Plesk.)
3. **PostgreSQL**: crear DB + usuario + clave (ej. `pideaca`, usuario
   `pideaca_user`). Anotar host (suele ser `localhost`), puerto `5432`.
4. **Credenciales FTP** del correo de bienvenida: host (ej.
   `ftp.pideaca.com` o IP), usuario, clave. Puerto **21**, FTPS explícito.
5. **SSL**: emitir certificado (Let's Encrypt) para el dominio.

## 1. Subir el paquete (desde tu PC)

```powershell
cd C:\xampp\htdocs\pideaca
.\deploy-ftp.ps1 -FtpHost ftp.pideaca.com -FtpUser TU_USUARIO_FTP
# pide la clave de forma interactiva (no queda en historial)
```

El script crea `pideaca-deploy.zip` (excluye `.git`, `node_modules`,
`mobile/`, `.env`, logs y cachés) y lo sube a la raíz FTP.
Con variables de entorno (CI): `FTP_HOST`, `FTP_USER`, `FTP_PASS`.

## 2. Extraer y apuntar el dominio (administrador de archivos)

> Si el administrador de archivos web tampoco abre, pedir a soporte:
> "extraer `pideaca-deploy.zip` en `~/pideaca`". Todo lo demás sigue igual.

1. Extraer `pideaca-deploy.zip` de modo que quede `~/pideaca/app`,
   `~/pideaca/public`, etc. (carpeta `pideaca` a la misma altura que
   `httpdocs`, NO dentro).
2. Document root del dominio → `pideaca/public`. En Plesk: Sitios web y
   dominios → Hosting Settings → Document root.
   (El `.htaccess` de `public/` ya viene sin `RewriteBase`: funciona en raíz.
   En localhost usaba `/pideaca`.)
3. Permisos de escritura: `storage/` y `bootstrap/cache/` recursivos
   (propietario del hosting, 775 en carpetas / 664 en archivos).
4. Crear `~/pideaca/.env` copiando `.env.example` y completando:
   `APP_KEY` (paso 3), `DB_*` (paso 0.3), `MAIL_*`.

## 3. Comandos post-despliegue (Plesk → Tareas programadas → Añadir tarea → Ejecutar comando)

Usar el binario PHP del hosting, ej. `/opt/plesk/php/8.2/bin/php`
(confirmar la ruta con soporte). Directorio de trabajo: `~/pideaca`.
Crearlas como **ejecución única** (o "Ejecutar ahora") y en este orden:

```sh
php artisan key:generate --force
php artisan storage:link
php artisan migrate --force
```

Seeders de catálogo (producción limpia: NO correr `DatabaseSeeder` ni
`FastDeliverySeeder`, que cargan datos demo):

```sh
php artisan db:seed --class=CountriesAndRegionsSeeder --force
php artisan db:seed --class=CurrencySeeder --force
php artisan db:seed --class=ServiceTypeSeeder --force
php artisan db:seed --class=UnitOfMeasureSeeder --force
php artisan db:seed --class=UsuariosPageSeeder --force
php artisan db:seed --class=FastDeliveryPagesSeeder --force
php artisan db:seed --class=EstadosDeLosGruposPageSeeder --force
php artisan db:seed --class=UnidadesMedidaPageSeeder --force
php artisan db:seed --class=MonedasPageSeeder --force
php artisan db:seed --class=InventoryPagesSeeder --force
php artisan db:seed --class=ReportesPageSeeder --force
php artisan db:seed --class=ReportesHogarPageSeeder --force
php artisan db:seed --class=OrdenesGestionPageSeeder --force
```

Cachés (al final, y repetir tras cada cambio de `.env`):

```sh
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Cuenta admin inicial**: registrarse en `https://pideaca.com/register`
(ese será el usuario id 1), luego:

```sh
php artisan tinker --execute="$u=App\Models\User::findOrFail(1);$t=App\Models\TypeUser::firstOrCreate(['description'=>'Admin']);$u->update(['type_user_id'=>$t->id]);echo 'OK '.$u->email;"
php artisan db:seed --class=UsuariosPageSeeder --force
```

## 4. Verificación

- `https://pideaca.com` carga y el login funciona.
- `https://pideaca.com/api/mobile/v1/ping` → `{"success":true,...}`.
- Mi Panel → Retiros de repartidores (sin error de conexión).
- Probar registro, catálogo y un pedido de prueba.
- Imágenes: `https://pideaca.com/storage/...` responde (valida `storage:link`).

## 5. Base de datos — archivos incluidos

- `database/schema/pgsql-schema.sql`: estructura limpia (pg_dump
  `--schema-only --no-owner --no-acl`). Respaldo / importación manual.
- `database/schema/seed-migrations-table.sql`: marca las 83 migraciones
  como aplicadas. **Solo** si se importó el `.sql` a mano y NO se corrió
  `migrate` (si se corrió `migrate --force`, no usar este archivo).

## 6. Móvil contra producción

En `mobile/.env`: `EXPO_PUBLIC_API_URL=https://pideaca.com/api/mobile/v1`
(y `EXPO_PUBLIC_ASSET_URL` igual sin el sufijo). Republicar
(`eas update` o nueva build).

## 7. Rollback

Conservar el `pideaca-deploy.zip` anterior y un respaldo PG antes de cada
despliegue. Para volver atrás: re-extraer el zip previo y restaurar el
respaldo de la base.
