# App móvil de PideAca (Expo + React Native + TypeScript)

Esta carpeta es la app móvil. Consume el API de Laravel que vive en
`../routes/api.php` bajo el prefijo `/api/mobile/v1`.

## Requisitos

- Node.js 20 o superior
- La API encendida (Apache/XAMPP o `php artisan serve`)
- La app **Expo Go** en el celular, o un emulador Android

## Instalar

```powershell
cd mobile
cmd /c "npx expo install"
```

> En esta PC los scripts de PowerShell (`npm.ps1`) están bloqueados, por eso
> los comandos se ejecutan con `cmd /c "..."`.

## Configurar la URL del API

La app deduce la URL automáticamente:

- **Emulador Android**: usa `10.0.2.2` (la PC vista desde el emulador).
- **Celular físico**: usa la IP de la PC que Expo/Metro ya conoce.
- **Web**: usa el host del navegador.

Si hace falta forzarla, copiá `.env.example` a `.env` y descomentá la línea:

```powershell
Copy-Item .env.example .env
```

Después de cambiar `.env`, recargá la app (shake → Reload).

## Correr la app

```powershell
cd mobile
cmd /c "npx expo start"
```

Escaneá el QR con Expo Go (Android) o presioná `a` para abrir el emulador.

## Probar el login

1. Abrí la app.
2. Ingresá con un correo/contraseña que exista en la base de datos web.
3. Si el login es correcto, el token queda guardado cifrado (SecureStore) y
   entrás al home.
4. El botón **Salir** revoca ese token en el servidor.

## Estructura

```
src/
  app/                 # Rutas (Expo Router). Cada archivo es una pantalla.
    _layout.tsx        # Provider de sesión + Stack de navegación
    index.tsx          # Redirige a /login o /home según la sesión
    login.tsx
    register.tsx
    home.tsx
  api/client.ts        # fetch con token, timeout y errores uniformes
  auth/AuthContext.tsx # Estado global de sesión (login, registro, logout)
  components/          # UI reutilizable
  lib/tokenStore.ts    # Guardado cifrado del token (SecureStore)
  types/api.ts         # Tipos de las respuestas del API
  config.ts            # Resolución de la URL del API
```

## Comandos útiles

```powershell
cmd /c "npx tsc --noEmit"   # verificar tipos
cmd /c "npx expo lint"      # lint (la primera vez configura ESLint)
cmd /c "npx expo-doctor"    # diagnosticar dependencias
```
