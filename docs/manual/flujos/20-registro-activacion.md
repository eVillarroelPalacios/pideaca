# 3. Flujo: registro y activación de cuenta

## Paso a paso

1. **Registro.** El usuario completa nombre, correo, tipo de usuario y
   clave (web: formulario de registro; móvil: pantalla de registro).
2. **Alta pendiente.** El sistema crea el usuario **inactivo**, le asigna
   las páginas de su tipo, genera un token único (válido 24 h) y envía el
   correo **"Activá tu cuenta"** con el botón de activación.
3. **Correo.** Sale por Brevo (SMTP `smtp-relay.brevo.com`) desde
   `hola.pideaca@pideaca.com`, en versión HTML + texto plano, con
   SPF/DKIM/DMARC en PASS. El mensaje avisa que revise también spam.
4. **Activación.** Al tocar el botón, el enlace de un solo uso activa la
   cuenta. Si expiró, el usuario pide el reenvío (mismo mensaje siempre,
   con throttle anti-abuso).
5. **Login.** Recién entonces puede iniciar sesión (web con sesión,
   móvil con token Bearer). Sin activar muestra "revisá tu correo".

## Si algo falla

- **No llega el correo:** Brevo → Transactional → Logs muestra si salió,
  rebotó o se quejó el destinatario. Hotmail suele mandarlo a spam la
  primera vez (marcar "No es spam" lo entrena).
- **Enlace vencido/usado:** reenviar desde el login genera uno nuevo.
- **Registro duplicado:** el sistema responde EMAIL_TAKEN sin decir si
  la cuenta existe (no filtra información).

## Referencia técnica

- Web: `Auth\RegisterController@store` → `AccountActivationMail`.
- Móvil: `Api\V1\AuthController@register` (misma lógica y mensajes).
- Token: `account_activation_requests` (hash SHA-256, expira en 24 h).
- Vistas de correo: `resources/views/emails/account-activation*.blade.php`.
